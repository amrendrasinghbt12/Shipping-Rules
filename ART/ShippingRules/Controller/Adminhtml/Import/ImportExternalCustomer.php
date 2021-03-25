<?php

namespace ART\ShippingRules\Controller\Adminhtml\Import;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\ResultFactory;

class ImportExternalCustomer extends Action
{


    /**
     * import action from import/export tax
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {

        
        $storeManager = $this->_objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $storeURL = $storeManager->getStore()->getBaseUrl();

       $lastCustomerId = $this->getlastCustomerId();
       
        $URL = 'http://127.0.0.1/otpdev/pub/exportcustomer.php?entity_id='.$lastCustomerId; //$storeURL.
        
        $httpClientFactory = $this->_objectManager->create('Magento\Framework\HTTP\ZendClientFactory');
        $client = $httpClientFactory->create();
        $client->setUri($URL);
        $client->setMethod(\Zend_Http_Client::GET);
        $client->setHeaders(\Zend_Http_Client::CONTENT_TYPE, 'application/json');
        $client->setHeaders('Accept','application/json');
        //$client->setHeaders("Authorization","Bearer 1212121212121");
        //$client->setParameterPost($params); //json
        $response= $client->request();   

        $customers = json_decode($response->getBody(),true);
       
        if(isset($customers['customercollection']) && count($customers['customercollection']))
        {
            $total_rows = 0;
            $success_rows = 0;
            $error_rows = 0;
            $customercollection = $customers['customercollection'];    
            $last_id_from_inportData = $customercollection[0]['customer_id'];

            foreach ($customercollection as $key => $importData) {
                $total_rows ++;

                $result = $this->_importCustomerSimple($importData,$last_id_from_inportData);
                if ($result == "") {
                    $success_rows ++;
                } else {
                    $error_rows ++;
                    
                }
            }
            $out=array();
            if ($total_rows==$success_rows) {

                $processHandler = $this->_objectManager->create('ArtTech\AdminPage\Model\Customer\CustomerProcessHandler');

                $counts= $processHandler->createCustomerFromExternalImport($last_id_from_inportData);
                $out=array($total_rows,$success_rows,$error_rows,$counts[0],implode('| ', $counts[1]),$counts[2],implode('| ', $counts[3]),$counts[4],implode('| ', $counts[5]),$counts[6],implode('| ', $counts[7]),$counts[8]);

                
                $this->messageManager->addSuccess(__('%s Customer Imported.',$success_rows));
            }else
            {
                $this->messageManager->addSuccess(__('Something went wrong.'));        
            }    
        }

        
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRedirectUrl());
        return $resultRedirect;
    }


    protected function _importCustomerSimple($importData,$last_id_from_inportData)
    {   
        
        $ret= "";
        try {
            
            $resourceConnection = $this->_objectManager->create('Magento\Framework\App\ResourceConnection');
            $connection = $resourceConnection->getConnection();

            $sqlInsert = "INSERT INTO art_external_customers (`Customer`,`Name`,`Prim Atn First Name`,`Prim Atn Last Name`,`Email`,`Primary Attn`,`Address`,`City`,`State`,`Zip Code`,`Country`,`Phone`,`Opened Date`,`Primary Salesman`,`Salesman Name`,`customer_id`,`import_id`) VALUES 
            ('".$importData['Customer']."','".addslashes($importData['Name'])."','".$importData['Prim Atn First Name']."','".$importData['Prim Atn Last Name']."','".$importData['Email']."','".$importData['Primary Attn']."','".$importData['Address']."','".addslashes($importData['City'])."','".addslashes($importData['State'])."','".addslashes($importData['Zip Code'])."','".addslashes($importData['Country'])."','".$importData['Phone']."','".addslashes($importData['Opened Date'])."','".addslashes($importData['Primary Salesman'])."','".addslashes($importData['Salesman Name'])."','".$importData['customer_id']."','".$last_id_from_inportData."')".
            "ON DUPLICATE KEY UPDATE `Name` = '".addslashes($importData['Name'])."',`Prim Atn First Name` = '".$importData['Prim Atn First Name']."',`Prim Atn Last Name` = '".$importData['Prim Atn Last Name']."',`Email`='".$importData['Email']."',`Primary Attn`='".$importData['Primary Attn']."',`Address`='".$importData['Address']."',`State`='".$importData['State']."',`Zip Code`='".$importData['Zip Code']."',`Country`='".$importData['Country']."',`Phone`='".$importData['Phone']."',`Opened Date`='".$importData['Opened Date']."',`Primary Salesman`='".$importData['Primary Salesman']."',`Salesman Name`='".$importData['Salesman Name']."',`customer_id`='".$importData['customer_id']."',`import_id`='".$last_id_from_inportData."'";
            //echo $sqlInsert;
            //exit;
           $connection->query($sqlInsert);

        } catch (QueryException $e) {
            $ret=$e->getMessage();
        } catch (\Exception $e) {
            
            $ret=$e->getMessage();
        }
        return $ret;
    }


    public function getlastCustomerId(){

        $resourceConnection = $this->_objectManager->create('Magento\Framework\App\ResourceConnection');
        $connection = $resourceConnection->getConnection();
        $insertSQL = "SELECT * FROM  art_external_customers ORDER BY customer_id DESC LIMIT 1";
        $rows = $connection->fetchRow($insertSQL);

        if($rows)
        {
            
            return $rows['customer_id'];
        }else
        {
            
            return 0;
        }
    }
}
