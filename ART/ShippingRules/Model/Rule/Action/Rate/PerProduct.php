<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace ART\ShippingRules\Model\Rule\Action\Rate;

use MageWorx\ShippingRules\Model\Rule\Action\Rate\AbstractRate;
/**
 * Class PerProduct
 */
class PerProduct extends AbstractRate
{

    /**
     * Calculate fixed amount
     *
     * @return AbstractRate
     */
    protected function fixed()
    {
    	$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    	$config = $objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface');
    	$charge = (int) $config->getValue('artmultishippingcharges/shippingrule/excluded_product_charges_fix');

        $productQty = 0;
        $excludedProductQty = 0;
        $excludedProductShippingPrice = $charge; 
        $includedItemIds = array_keys($this->validItems);
        
        foreach ($this->getShippingAddress()->getAllItems() as $item) {
            
            
            if (!in_array($item->getId(), $includedItemIds)) {
                
                if(!$item->getChildren())
                {
                   
                    if ($item->getParentItem()) {
                        $exqty = (float)$item->getQty() * (float)$item->getParentItem()->getQty();
                    } else {
                        $exqty = (float)$item->getQty();
                    }  
                    $excludedProductQty += $exqty; 
                }
            }
            ;
        }

        foreach ($this->validItems as $item) {
            if ($item->getParentItem()) {
                $qty = (float)$item->getQty() * (float)$item->getParentItem()->getQty();
            } else {
                $qty = (float)$item->getQty();
            }
            $productQty += $qty;
        }


        $amountValue       = $this->getAmountValue();
        $includedAmountValue = $amountValue * $productQty;
        $excludedAmountValue = $excludedProductShippingPrice * $excludedProductQty;


        $this->_setAmountValue($includedAmountValue + $excludedAmountValue);

        return $this;

    }

    /**
     * Calculate percent of amount
     *
     * @return AbstractRate
     */
    protected function percent()
    {
    	$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    	$config = $objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface');
    	$charge = (int) $config->getValue('artmultishippingcharges/shippingrule/excluded_product_charges_percent');

        $price = 0;
        $excludedPrice = 0;
        $excludedProductShippingPrice = $charge/100;
        $includedItemIds = array_keys($this->validItems);

        foreach ($this->getShippingAddress()->getAllItems() as $item) {
            if (!in_array($item->getId(), $includedItemIds)) {
                
                if(!$item->getChildren())
                {
                    if ($item->getParentItem()) {
                        $excludedPrice += (float)$item->getParentItem()->getRowTotal();
                    } else {
                        $excludedPrice += (float)$item->getRowTotal();
                    }    
                }
            }
        }

        foreach ($this->validItems as $item) {
            if ($item->getParentItem()) {
                $price += (float)$item->getParentItem()->getRowTotal();
            } else {
                $price += (float)$item->getRowTotal();
            }
        }

        $amountValue = $this->getAmountValue() ? $this->getAmountValue() / 100 : 0;
        
        
        $amount = $price * $amountValue;
        $excludedAmount = $excludedPrice * $excludedProductShippingPrice; 
        $this->_setAmountValue($amount + $excludedAmount);

        return $this;

    }
}
