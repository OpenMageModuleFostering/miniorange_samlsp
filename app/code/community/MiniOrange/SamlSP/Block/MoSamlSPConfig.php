<?php
class MiniOrange_SamlSP_Block_MoSamlSPConfig extends Mage_Core_Block_Template{
	private $hostname = 'https://auth.miniorange.com';
	
	public function isEnabled(){
		$customer = Mage::helper('MiniOrange_SamlSP');
		$email = $customer->getConfig('email');
		$key = $customer->getConfig('customerKey');
		if(!empty($email) && !empty($key)){
			return true;
		}else{
			return false;
		}
	}
	
	function mo_saml_is_sp_configured(){
		$customer = Mage::helper('MiniOrange_SamlSP');
		return $customer->mo_saml_is_sp_configured();
	}
	
	function getResouceURL($value){
		return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN).'adminhtml/default/default/miniorangesamlsp/resources/'.$value;
	}
	
	function getPluginFileURL($value){
		return Mage::getModuleDir('', 'MiniOrange_SamlSP').DS.'Helper'.DS.$value;
	}
	
	function getHostName(){
		return $this->hostname;
	}
	
	function getSamlLoginUrl(){
		$url = $this->getAdminUrl('samlsp/adminhtml_index/samlLoginRequest/');
		return $url;
	}
	
	function mo_samlSP_get_test_url(){
		$url = $this->getAdminUrl('*/*/samlLoginRequest/');
		return $url.'?q=testConfig';
	}
	
	public function is_administrator_user($user_id){
		if(Mage::getSingleton('customer/session')->isLoggedIn() || is_null($user_id)){
			return false;
		}else{
			$role_data = Mage::getModel('admin/user')->load($user_id)->getRole();
			if($role_data->getRoleName()=='Administrator')
				return true;
			else
				return false;
		}
			
	}
	
	public function getLogoutUrl(){
		if(Mage::getSingleton('customer/session')->isLoggedIn())
			return Mage::getUrl('customer/account/logout');
		else
			return Mage::helper("adminhtml")->getUrl('adminhtml/index/logout');
	}
	
	
	public function getAdminUrl($value=''){
		return Mage::helper("adminhtml")->getUrl($value,array('_secure'=>true));
	}
	
	public function getAdminUnsecureUrl($value=''){
		return Mage::helper("adminhtml")->getUrl($value);
	}
	
	public function getIssuerUrl(){
		$url = $this->getAdminUrl('samlsp/index/');
		return substr($url,0,strpos($url,'index/'));
	}
	
	public function getAdminLoginUrl(){
		$url = $this->getAdminUrl('adminhtml');
		return substr($url,0,strpos($url,'index/'));
	}
	
	public function getCustomerLoginUrl(){
		return Mage::getUrl('customer/account/login');
	}
	
	public function getBaseUrl(){
		return Mage::getBaseUrl();
	}
	
	public function getHostURl(){
		return  Mage::helper('MiniOrange_SamlSP')->getHostURl();
	}
	
	public function getCurrentUser(){
		if (Mage::getSingleton('customer/session')->isLoggedIn()) {
			$customer = Mage::getSingleton('customer/session')->getCustomer();
			return $customer->getId();
		}
		return;
	}
	
	public function showEmail(){
		$admin = Mage::getSingleton('admin/session')->getUser();
		$customer = Mage::helper('MiniOrange_SamlSP');
		$id = $admin->getUserId();
		return $customer->showEmail($id);
	}
	
	public function saveConfig($url,$value,$admin){
		$admin = Mage::getSingleton('admin/session')->getUser();
		$id = $admin->getUserId();
		$data = array($url=>$value);
		$model = Mage::getModel('admin/user')->load($id)->addData($data);
		try {
				$model->setId($id)->save(); 
			} catch (Exception $e){
				Mage::log($e->getMessage(), null, 'miniorage_error.log', true);
		}
	}
	
	public function getImage($image){
		$url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN);
		return $url.'adminhtml/default/default/miniorangesamlsp/images/'.$image.'.png';
	}

	
	public function getConfig($config,$id=""){
		$user = Mage::helper('MiniOrange_SamlSP');
		return $user->getConfig($config);
	}
	
	public function cURLEnabled(){
		$customer = Mage::helper('MiniOrange_SamlSP');
		return $customer->is_curl_installed();
	}
	
	public function getSession(){
		if( Mage::getSingleton('customer/session')->isLoggedIn() ) {
			$session = Mage::getSingleton('customer/session');
		}else{
			$session = Mage::getSingleton('admin/session');
		}
		return $session;
	}
	
	private function redirect($url){
		$redirect = Mage::helper("adminhtml")->getUrl($url);
		Mage::app()->getResponse()->setRedirect($redirect);
	}
	

}