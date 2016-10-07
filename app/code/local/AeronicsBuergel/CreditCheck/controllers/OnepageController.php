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

include_once("Mage/Checkout/controllers/OnepageController.php");

/**
 * OnepageController
 * deprecated
 *
 * @author AERONICS
 */
class AeronicsBuergel_CreditCheck_OnepageController
    extends Mage_Checkout_OnepageController
{
    /**
     * Create order action
     */
    public function saveOrderAction()
    {
        parent::saveOrderAction();
        /* @var $buergelApi AeronicsBuergel_CreditCheck_Model_BuergelApi*/
    }
}