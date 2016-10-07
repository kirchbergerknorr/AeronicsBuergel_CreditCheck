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
 * IndexController
 *
 * @author AERONICS
 */
class AeronicsBuergel_CreditCheck_IndexController
    extends Mage_Core_Controller_Front_Action
{
    /**
     * Index action
     */
    public function indexAction()
    {
        /* @var $buergelApi AeronicsBuergel_CreditCheck_Model_BuergelApi*/
        $buergelApi = Mage::getModel('aeronicsbuergel_creditcheck/BuergelApi');
        
        if ('2' == $buergelApi->getConfig('mode')) {
            $buergelApi->buergelRequest($buergelApi->getCustomerBillingAddress());
            echo 'Last Bügel Score: ' . $buergelApi->getBuergelScore(); 
            echo '|Last Bügel Notice: ' . $buergelApi->getBuergelNotice(); 
            echo '|Last Bügel Address Origin: ' . $buergelApi->getBuergelAddressOrigin();
        } else {
            echo 'Not test allowed in Live-Mode.';
        }
    }
}