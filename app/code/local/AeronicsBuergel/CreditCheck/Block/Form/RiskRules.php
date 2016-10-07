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
 * Form risk rules
 *
 * @author AERONICS
 */
class AeronicsBuergel_CreditCheck_Block_Form_RiskRules
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    protected $_itemRenderer;

    public function _prepareToRender()
    {
        $this->addColumn('payment_method', array(
            'label'    => Mage::helper('core')->__('Payment method'),
            'renderer' => $this->_getRenderer('PaymentMethod'),
        ));

        $this->addColumn('rule_name', array(
            'label'    => Mage::helper('core')->__('Rule name'),
            'renderer' => $this->_getRenderer('RuleName'),
        ));
        
        $this->addColumn('rule_value', array(
            'label' => Mage::helper('core')->__('Rule value'),
            'style' => 'width:50px',
        ));

        $this->addColumn('rule_name2', array(
            'label'    => Mage::helper('core')->__('Rule name'),
            'renderer' => $this->_getRenderer('RuleName2'),
        ));

        $this->addColumn('rule_value2', array(
            'label' => Mage::helper('core')->__('Rule value'),
            'style' => 'width:50px',
        ));

        $this->addColumn('rule_name3', array(
            'label'    => Mage::helper('core')->__('Rule name'),
            'renderer' => $this->_getRenderer('RuleName3'),
        ));

        $this->addColumn('rule_value3', array(
            'label' => Mage::helper('core')->__('Rule value'),
            'style' => 'width:50px',
        ));
        
        $this->addColumn('product_group', array(
            'label'    => Mage::helper('core')->__('Product group'),
            'renderer' => $this->_getRenderer('ProductGroup'),
        ));
        
        $this->addColumn('country_code', array(
            'label'    => Mage::helper('core')->__('Country'),
            'renderer' => $this->_getRenderer('Country'),
        ));

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('core')->__('Add');
    }

    protected function _getRenderer($fieldName)
    {
        if (!isset($this->_itemRenderer[$fieldName])) {
            $this->_itemRenderer[$fieldName] = $this->getLayout()
                ->createBlock(
                    'aeronicsbuergel_creditcheck/form_field_' . $fieldName, '',
                    array('is_render_to_js_template' => true)
                );
        }
        
        return $this->_itemRenderer[$fieldName];
    }
    
    protected function _prepareArrayRow(Varien_Object $row)
    {
        $row->setData(
            'option_extra_attr_' . $this->_getRenderer('PaymentMethod')
                ->calcOptionHash($row->getData('payment_method')),
            'selected="selected"'
        );
        
        $row->setData(
            'option_extra_attr_' . $this->_getRenderer('RuleName')
                ->calcOptionHash($row->getData('rule_name')),
            'selected="selected"'
        );

        $row->setData(
            'option_extra_attr_' . $this->_getRenderer('RuleName2')
                ->calcOptionHash($row->getData('rule_name2')),
            'selected="selected"'
        );

        $row->setData(
            'option_extra_attr_' . $this->_getRenderer('RuleName3')
                ->calcOptionHash($row->getData('rule_name3')),
            'selected="selected"'
        );
        
        $row->setData(
            'option_extra_attr_' . $this->_getRenderer('ProductGroup')
                ->calcOptionHash($row->getData('product_group')),
            'selected="selected"'
        );
        
        $row->setData(
            'option_extra_attr_' . $this->_getRenderer('Country')
                ->calcOptionHash($row->getData('country_code')),
            'selected="selected"'
        );
    }
}