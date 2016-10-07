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
 * Form field rule name
 *
 * @author AERONICS
 */
class AeronicsBuergel_CreditCheck_Block_Form_Field_RuleName2
    extends Mage_Core_Block_Html_Select
{
    public function _toHtml()
    {        
        $source  = 'aeronicsbuergel_creditcheck/source_RuleNames2';
        $options = Mage::getSingleton($source)->toOptionArray();
        
        foreach ($options as $option) {
            $this->addOption($option['value'], $option['label']);
        }
 
        return parent::_toHtml();
    }
    
    public function getExtraParams()
    {
        return 'style = "width:110px"';
    }
    
    public function setInputName($value)
    {
        return $this->setName($value);
    }
}