<?php
/**
 *
 * Copyright (C) 2016 by AERONICS MEDIA GmbH & Co. KG
 *
 * This program is licenced under the AERONICS software licence. With the
 * purchase or the installation of the software in your application you
 * accept the licence agreement. The allowed usage is outlined in the
 * AERONICS software licence which can be found under
 * http://www.aeronics.de/magento/pluginlicense
 *
 * Any modification or distribution is strictly forbidden. The license
 * grants you the installation in one application.
 *
 * See the AERONICS software licence agreement for more details.
 *
 * @package AeronicsBuergel
 * @author AERONICS MEDIA GmbH & Co. KG
 */

/**
 * Observer
 *
 * @author AERONICS
 */
class AeronicsBuergel_CreditCheck_Model_Observer
    extends Mage_Core_Model_Abstract
{
    /**
     * Checks risk.
     * 
     * @param Varien_Event_Observer $observer
     * 
     * @return null
     */
    public function activeCheck(Varien_Event_Observer $observer)
    {
        $event      = $observer->getEvent();
        $methodCode = $event->getMethodInstance()->getCode();

        $settings       = Mage::getStoreConfig('aeronicsbuergel_creditcheck/settings');
        if($settings["sessioncache"] == "1") {
            $disabledMethodCode = Mage::getSingleton('checkout/session')
                ->getData('aeronicsbuergel_creditcheck_disabled_method');
        }

        if(Mage::app()->getRequest()->getActionName() == "saveShippingMethod") {
            if ($methodCode && $methodCode === $disabledMethodCode) {
                $observer->getEvent()->getResult()->isAvailable = false;
                Mage::getSingleton('checkout/session')
                    ->setData('aeronicsbuergel_creditcheck_disabled_method', null);
            } else {
                /* @var $buergelApi AeronicsBuergel_CreditCheck_Model_BuergelApi */
                $buergelApi = Mage::getModel('aeronicsbuergel_creditcheck/BuergelApi');
                if ($buergelApi->checkRisk($methodCode, true)) {
                    $observer->getEvent()->getResult()->isAvailable = false;
                }
            }
        }
    }

    /**
     * Checks risk passive
     *
     * @param Varien_Event_Observer $observer
     *
     * @return null
     */
    public function passiveCheck(Varien_Event_Observer $observer)
    {
        $event      = $observer->getEvent();
        $buergelApi = Mage::getModel('aeronicsbuergel_creditcheck/BuergelApi');
        $methodCode = $buergelApi->getQuotePaymentMethod();

        if ($buergelApi->checkRisk($methodCode, false)) {
            $session = Mage::getSingleton('checkout/session');
            $session->setData(
                'aeronicsbuergel_creditcheck_disabled_method',
                $methodCode
            );


            $result['success']        = false;
            $result['error']          = true;
            $result['error_messages'] = 'The payment method is not available for you.';
            $result['goto_section']   = 'payment';
            //$result['update_section'] = array(
            //      'name' => 'payment-method',
            //      'html' => $this->getOnepage()->_getPaymentMethodsHtml(),
            //);

            echo json_encode($result);
            exit;
        }

    }

    /**
     * inserts customer last check infos
     *
     * @param Varien_Event_Observer $observer
     */
    public function customerRegisterSuccess(Varien_Event_Observer $observer) {
        //if ($observer->getQuote()->getData('checkout_method') != Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER) {
            $event = $observer->getEvent();
            $customer = $observer
                ->getQuote()
                ->getBillingAddress();
            $id = $customer->getId();

            if ($id) {
                try {
                    $readSql = 'SELECT * FROM buergel_scores WHERE '
                        . 'billingaddressID = :billingaddressID '
                        . 'order by id DESC';
                    $readBinds = array(
                        'billingaddressID' => $id
                    );

                    $rescource = Mage::getSingleton('core/resource');
                    $data = $rescource->getConnection('core_read')
                        ->query($readSql, $readBinds)
                        ->fetch();

                    $customer = Mage::getModel('customer/customer')->load($event->getOrder()->getCustomerId());
                    if ($customer && $data["score"] != "") {
                        $customer->setLastBuergelScore(number_format($data["score"]/10,1,".",""))
                            ->setLastBuergelNotice($data["notice"])
                            ->save();
                    }

                } catch (\Exception $e) {

                }
            }
        //}
    }

}