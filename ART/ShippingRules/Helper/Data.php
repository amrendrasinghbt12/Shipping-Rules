<?php
/**
 * Copyright © 2015 ART . All rights reserved.
 */
namespace ART\ShippingRules\Helper;
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

	/**
     * @var \ART\ShippingRules\Model\Config
     */
    protected $_artConfig;


	/**
     * @param \Magento\Framework\App\Helper\Context $context
     */
	public function __construct(
		\Magento\Framework\App\Helper\Context $context,
		\ART\ShippingRules\Model\Config $artConfig
	) {
		parent::__construct($context);
		$this->_artConfig = $artConfig;

	}

	/*userd for multi-shipping*/
	public function calculateCustomShippingAmountForQuote()
	{	
		$isEnableMultiShippingCharges =  $this->_artConfig->getCurrentStoreConfigValue('artmultishippingcharges/general/enable');
		
		// if(!$isEnableMultiShippingCharges)
		// {
		// 	return false;
		// }

		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$productSelectedMethods = $this->getSelectedShippingMethodFronCartItems();
		if(!$productSelectedMethods)
		{
			return false;
		}
		$cart = $objectManager->get('\Magento\Checkout\Model\Cart'); 
		$quote = $cart->getQuote();
		
		$quote->collectTotals();
		$quote->getShippingAddress()->setCollectShippingRates(true);
		$quote->getShippingAddress()->collectShippingRates();

		$rates = $quote->getShippingAddress()->getAllShippingRates();

		$available= [];
		if(count($rates) == 0)
		{
			return false;
		}
		foreach ($rates as $rate)
	    {
	        $rateData = json_decode($rate->toJson(),true);
	        if(in_array($rateData['code'], $productSelectedMethods))
	        {
	            $available[$rateData['code']] = $rateData['price'];    
	        } 
	    }
	    
	    if(count($available) > 0)
	    {
	    	return array_sum($available);
	    }else
	    {
	    	return false;
	    }
	}

	/*userd for multi-shipping*/	
	private function getSelectedShippingMethodFronCartItems()
	{
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$cart = $objectManager->get('\Magento\Checkout\Model\Cart'); 
		$quote = $cart->getQuote();
		$allSelectedShippingMethods = [];

		foreach($quote->getAllItems() as $item) 
		{

		    if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
		        
		        continue;
		    }
		    
		    if ($item->getHasChildren()) {
		       
		        foreach ($item->getChildren() as $child) {
		            $_product = $objectManager->create('\Magento\Catalog\Model\Product');
		            $product = $_product->load($child->getProductId());
		            $selectedShippingMethod = $product->getAvailableShippingMethods();
		          

		            $shippingMethods = explode(',', $selectedShippingMethod);
		            
		            if(count($shippingMethods))
		            {
		                foreach ($shippingMethods as $value) 
		                {
		                   array_push($allSelectedShippingMethods, $value);
		                }
		            }   
		        }
		    } else {
		        $_product = $objectManager->create('\Magento\Catalog\Model\Product');
		        $product = $_product->load($item->getProductId());
		        $selectedShippingMethod = $product->getAvailableShippingMethods();
		        

		        $shippingMethods = explode(',', $selectedShippingMethod);
		        
		        if(count($shippingMethods))
		        {
		            foreach ($shippingMethods as $value) 
		            {
		               array_push($allSelectedShippingMethods, $value);
		            }
		        }        
		    } 
		}
		
		return (count($allSelectedShippingMethods) > 0 ) ? array_unique($allSelectedShippingMethods) : false ;
	}
}