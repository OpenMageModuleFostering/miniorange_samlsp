<?php

$installer = $this;
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer->startSetup();

$installer->getConnection()->addColumn($this->getTable('admin/user'), 'mo_saml_session_index', 'varchar(128) null');
$installer->getConnection()->addColumn($this->getTable('admin/user'), 'mo_saml_name_id', 'varchar(60) null');

$setup = Mage::getModel('customer/entity_setup', 'core_setup');	

	$setup->addAttribute('customer', 'mo_saml_session_index', array(
    'type' => 'varchar','input' => 'text','label' => 'Saml Session Index','global' => 1,'visible' => 0,'required' => 0,
    'user_defined' => 1,'default' => '0','visible_on_front' => 0,'source'=> '',
	));

	$setup->addAttribute('customer', 'mo_saml_name_id', array(
    'type' => 'varchar','input' => 'text','label' => 'Saml NameID','global' => 1,'visible' => 0,'required' => 0,
    'user_defined' => 1,'default' => '0','visible_on_front' => 0,'source'=> '',
	));
	
if (version_compare(Mage::getVersion(), '1.6.0', '<='))
{
      $customer = Mage::getModel('customer/customer');
      $attrSetId = $customer->getResource()->getEntityType()->getDefaultAttributeSetId();
	   $setup->addAttributeToSet('customer', $attrSetId, 'General', 'mo_saml_session_index');
	   $setup->addAttributeToSet('customer', $attrSetId, 'General', 'mo_saml_name_id');
}

if (version_compare(Mage::getVersion(), '1.4.2', '>='))
{
	 Mage::getSingleton('eav/config')
    ->getAttribute('customer', 'mo_saml_session_index')
    ->setData('used_in_forms', array('adminhtml_customer','customer_account_create','customer_account_edit','checkout_register'))
    ->save();
	
	Mage::getSingleton('eav/config')
    ->getAttribute('customer', 'mo_saml_name_id')
    ->setData('used_in_forms', array('adminhtml_customer','customer_account_create','customer_account_edit','checkout_register'))
    ->save();

}

$installer->endSetup();