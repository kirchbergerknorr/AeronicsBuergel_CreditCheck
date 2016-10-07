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
 * Abstract source
 *
 * @author AERONICS
 */
abstract class AeronicsBuergel_CreditCheck_Model_Source_AbstractSource
{
    /** @var array */
    protected $data = array();
    
    /**
     * Provide available options as a value/label array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = array();
        foreach ($this->data as $key => $value) {
            $options[] = array(
                'value' => $key,
                'label' => Mage::helper('core')->__($value),
            );
        }

        return $options;
    }
}