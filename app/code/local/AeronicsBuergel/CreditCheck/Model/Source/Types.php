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
 * Types.
 * 
 * @author AERONICS
 */
class AeronicsBuergel_CreditCheck_Model_Source_Types
    extends AeronicsBuergel_CreditCheck_Model_Source_AbstractSource
{
    /** @var array */
    protected $data = array(
        1 => 'Active check',
        2 => 'Passive check',
    );
}