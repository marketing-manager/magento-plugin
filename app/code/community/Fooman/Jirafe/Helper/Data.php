<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @package     Fooman_Jirafe
 * @copyright   Copyright (c) 2010 Jirafe Inc (http://www.jirafe.com)
 * @copyright   Copyright (c) 2010 Fooman Limited (http://www.fooman.co.nz)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Fooman_Jirafe_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_FOOMANJIRAFE_SETTINGS = 'foomanjirafe/settings/';
    const DEBUG = true;

    /**
     * Return store config value for key
     *
     * @param   string $key
     * @return  string
     */
    public function getStoreConfig ($key,
            $storeId = Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID)
    {
        $path = self::XML_PATH_FOOMANJIRAFE_SETTINGS . $key;
        return Mage::getStoreConfig($path, $storeId);
    }

    /**
     * Save store config value for key
     *
     * @param string $key
     * @param string $value
     * @return <type>
     */
    public function setStoreConfig ($key, $value, $storeId = Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID)
    {
        $path = self::XML_PATH_FOOMANJIRAFE_SETTINGS . $key;

        //save to db
        try {
            $configModel = Mage::getModel('core/config_data');
            $collection = $configModel->getCollection()
                        ->addFieldToFilter('path', $path)
                        ->addFieldToFilter('scope_id', $storeId);
            if ($storeId != Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID) {
                $collection->addFieldToFilter('scope', Mage_Adminhtml_Block_System_Config_Form::SCOPE_STORES);
            }

            if ($collection->load()->getSize() > 0) {
                //value already exists -> update
                foreach ($collection as $existingConfigData) {
                    $existingConfigData->setValue($value)->save();
                }
            } else {
                //new value
                $configModel
                        ->setPath($path)
                        ->setValue($value);
                if ($storeId != Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID ) {
                    $configModel->setScopeId($storeId);
                    $configModel->setScope(Mage_Adminhtml_Block_System_Config_Form::SCOPE_STORES);
                }
                $configModel->save();
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        //we also set it as a temporary item so we don't need to reload the config
        return Mage::app()->getStore($storeId)->load($storeId)->setConfig($path, $value);
    }

    public function isConfigured ($storeId = Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID)
    {
        return ($this->getStoreConfig('isActive', $storeId));
    }

    public function getStoreIds ()
    {
        return $this->getStores(true);
    }

    public function getStores ($idsOnly = false)
    {
        // Get a list of store IDs to send to the user
        $stores = Mage::getModel('core/store')->getCollection();
        $storearr = array();
        foreach ($stores as $store) {
            // Only continue if the store is active
            if ($store->getIsActive()) {
                if($idsOnly) {
                    $storearr[] = $store->getId();
                } else {
                    $storearr[$store->getId()] = $store;
                }
            }
        }
        if ($idsOnly) {
            return implode(',', $storearr);
        } else {
            ksort($storearr);
            return $storearr;
        }
    }

    public function collectJirafeEmails ($storeId)
    {
        $adminUsers = Mage::getSingleton('admin/user')->getCollection();
        $emails = array();
        // loop over all admin users
        foreach ($adminUsers as $adminUser) {
            //if admin user is subscribed to jirafe emails for this store
            if (in_array($storeId, explode(',',$adminUser->getJirafeSendEmailForStore()))) {
                //add all emails from the jirafe emails field
                foreach(explode(',',$adminUser->getJirafeEmails()) as $jirafeEmail) {
                    $emails[] = $jirafeEmail;
                }
            }
        }

        return implode(',',$emails);
    }

    public function debug ($mesg)
    {
        if (self::DEBUG) {
            Mage::log($mesg, null, 'jirafe.log');
        }
    }

    public function getStoreDescription ($store)
    {
        return $store->getFrontendName() . ' (' . $store->getName() . ')';
    }

    public function createJirafeUserId ($user)
    {
        return 'magento_' . substr($this->getStoreConfig('app_token'), 0, 5) . '_' . $user->getEmail();
    }

    public function createJirafeUserEmail ($user)
    {
        return $this->createJirafeUserId($user);
    }

    public function getUserEmail ($email)
    {
        /*
         * see createJirafeUserId
         */

        $array = explode('_', $email, 3);
        return $array[2];
    }

}