<?php
/** @var $installer Mage_Catalog_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$setup            = Mage::getModel('customer/entity_setup', 'core_setup');
$entityTypeId     = $setup->getEntityTypeId('customer');
$attributeSetId   = $setup->getDefaultAttributeSetId($entityTypeId);
$attributeGroupId = $setup->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);

$setup->addAttributeToGroup(
    $entityTypeId,
    $attributeSetId,
    $attributeGroupId,
    'customattribute'
);

// Attribute personal number
$installer->addAttribute('customer', 'personal_number', array(
    'type'     => 'varchar',
    'label'    => 'Personal Number',
    'input'    => 'text',
    'visible'  => true,
    'required' => false,
 ));

Mage::getSingleton('eav/config')
    ->getAttribute('customer', 'personal_number')
    ->setData("used_in_forms", array(
        'customer_account_create',
        'customer_account_edit',
        'checkout_register',
        'adminhtml_customer',
        'adminhtml_checkout',
    ))->setData("sort_order", 999)
    ->save();

// Attribute last buergel score
$installer->addAttribute('customer', 'last_buergel_score', array(
    'type'     => 'varchar',
    'label'    => 'Last Buergel Score',
    'input'    => 'text',
    'visible'  => false,
    'required' => false,
 ));

Mage::getSingleton('eav/config')
    ->getAttribute('customer', 'last_buergel_score')
    ->setData("used_in_forms", array(
        'adminhtml_customer',
    ))->setData("sort_order", 999)
    ->save();

// Attribute last buergel notice
$installer->addAttribute('customer', 'last_buergel_notice', array(
    'type'     => 'varchar',
    'label'    => 'Last Buergel Notice',
    'input'    => 'textarea',
    'visible'  => false,
    'required' => false,
));

Mage::getSingleton('eav/config')
    ->getAttribute('customer', 'last_buergel_notice')
    ->setData("used_in_forms", array(
        'adminhtml_customer',
    ))->setData("sort_order", 999)
    ->save();

// Attribute last buergel notice
$installer->addAttribute('customer', 'buergel_exclude', array(
    'type'     => 'int',
    'label'    => 'Exclude from Buergel Checks',
    'input'    => 'select',
    'visible'  => false,
    'required' => false,
    'source' => 'eav/entity_attribute_source_table',
    'option'             => array('values' => array('No', 'Yes'))
));

Mage::getSingleton('eav/config')
    ->getAttribute('customer', 'buergel_exclude')
    ->setData("used_in_forms", array(
        'adminhtml_customer',
    ))->setData("sort_order", 999)
    ->save();

// New table
$sql = "
    CREATE TABLE IF NOT EXISTS `buergel_scores` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `billingaddressID` int(11) NOT NULL,
        `hash` text COLLATE utf8_unicode_ci NOT NULL,
        `score` float(11,2) NOT NULL,
        `product` text COLLATE utf8_unicode_ci NOT NULL,
        `addressOrigin` text COLLATE utf8_unicode_ci NOT NULL,
        `notice` text COLLATE utf8_unicode_ci NOT NULL,
        `created` int(11) NOT NULL,
        `sessionID` text COLLATE utf8_unicode_ci NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
";
$installer->run($sql);

$installer->endSetup();