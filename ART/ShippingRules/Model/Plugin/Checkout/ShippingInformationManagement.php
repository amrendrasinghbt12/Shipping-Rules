<?php 

namespace ART\ShippingRules\Model\Plugin\Checkout;
/*userd for multi-shipping*/
class ShippingInformationManagement
{

    
    /**
     * @param \Magento\Checkout\Model\ShippingInformationManagement $subject
     * @param $cartId
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     */
    public function beforeSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    )
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $artHelper = $objectManager->create('\ART\ShippingRules\Helper\Data');
        $customShippingTotal = $artHelper->calculateCustomShippingAmountForQuote(); 
        
        $checkoutSession = $objectManager->get('\Magento\Checkout\Model\Session');
        $checkoutSession->setCustomShippingprice($customShippingTotal);   
    }

}

?>