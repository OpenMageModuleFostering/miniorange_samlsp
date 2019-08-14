<?php
class MiniOrange_SamlSP_Adminhtml_IndexController extends Mage_Adminhtml_Controller_Action
{
	private $_helper1 = "MiniOrange_SamlSP"; 
	private $_helper2 = "MiniOrange_SamlSP/moSamlUtility";
	
    public function indexAction(){
		$this->loadLayout();
		$this->_addContent($this->getLayout()->createBlock('core/template'));
        $this->renderLayout();
    }
	
	public function registerNewUserAction(){
		$params = $this->getRequest()->getParams();
		$datahelper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		$storeConfig = new Mage_Core_Model_Config();
		if($datahelper->is_curl_installed()){
			$email = $params['email'];
			$password = $params['password'];
			$confirm = $params['confirmPassword'];
			$submit = $params['submit'];
						
			if(strcasecmp($submit,"Register") == 0){
				if($password==$confirm){
					$storeConfig ->saveConfig('miniorange/samlsp/email',$email,'default', 0);
					$storeConfig ->saveConfig('miniorange/samlsp/pass',$password,'default', 0);				
					$content = $customer->check_customer($email);
					$content = json_decode($content, true);
					if( strcasecmp( $content['status'], 'CUSTOMER_NOT_FOUND') == 0 ){
						$content = json_decode($customer->send_otp_token($email,''), true);
						if(strcasecmp($content['status'], 'SUCCESS') == 0) {
							$storeConfig ->saveConfig('miniorange/samlsp/transactionID',$content['txId'], 'default', 0);
							$storeConfig ->saveConfig('miniorange/samlsp/registration/status','MO_OTP_EMAIL_VALIDATE', 'default', 0);
							$datahelper->displayMessage('A one time passcode is sent to '. $email .'. Please enter the otp here to verify your email.',"SUCCESS");
							$this->redirect("*/*/index");
						}else{
							$storeConfig ->saveConfig('miniorange/samlsp/registration/status','MO_OTP_DELIVERED_FAILURE', 'default', 0);
							$datahelper->displayMessage('There was an error in sending email. Please verify your email and try again.',"ERROR");
							$this->redirect("*/*/index");
						}
					}else{
						$this->get_current_customer($email,$password);
					}
				}else{
					$datahelper->displayMessage('Passwords do not match',"ERROR");
					$this->redirect("*/*/index");
				}
			}else if(strcasecmp($submit,"Forgot Password?") == 0){
				$this->forgotPass($email);
				$this->redirect("*/*/index");
			}else{
				$this->redirect("*/*/index");
			}
		}else{
			$datahelper->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("*/*/index");
		}
	}
	
	public function validateNewUserAction(){
		$params = $this->getRequest()->getParams();
		$datahelper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		$submit = $params['submit'];
		$storeConfig = new Mage_Core_Model_Config();
		if($datahelper->is_curl_installed()){
			if(array_key_exists('otp_token',$params) && strcasecmp($submit,"Validate OTP")==0){
				$otp = $params['otp_token'];
				$content = json_decode($customer->validate_otp_token($datahelper->getConfig('txtId'), $otp ),true);
				if(strcasecmp($content['status'], 'SUCCESS') == 0) {
					$this->create_customer();
				}else{
					$datahelper->displayMessage('Invalid one time passcode. Please enter a valid otp.',"ERROR");
					$this->redirect("*/*/index");
				}
			}else if(strcasecmp($submit,"Back")==0){
				$storeConfig ->saveConfig('miniorange/samlsp/email','','default', 0);
				$storeConfig ->saveConfig('miniorange/samlsp/pass','','default', 0);
				$storeConfig ->saveConfig('miniorange/samlsp/transactionID','','default', 0);
				$storeConfig ->saveConfig('miniorange/samlsp/registration/status','', 'default', 0);
				$this->redirect("*/*/index");
			}else{
				$datahelper->displayMessage('Please enter a value in otp field',"ERROR");
				$this->redirect("*/*/index");
			}
		}else{
			$datahelper->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("*/*/index");
		}
	}
	
	public function existingUserAction(){
		$params = $this->getRequest()->getParams();
		$datahelper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		$storeConfig = new Mage_Core_Model_Config();
		if($datahelper->is_curl_installed()){
			$email = $params['loginemail'];
			$password = $params['loginpassword'];
			$submit = $params['submit'];
			if(strcasecmp($submit,"Submit") == 0){
				$this->get_current_customer($email,$password);
			}else if(strcasecmp($submit,"Forgot Password?") == 0){
				$this->forgotPass($email);
				$this->redirect("*/*/index");
			}else if(strcasecmp($submit,"Go Back")==0){
				$storeConfig ->saveConfig('miniorange/samlsp/email','','default', 0);
				$storeConfig ->saveConfig('miniorange/samlsp/transactionID','','default', 0);
				$storeConfig ->saveConfig('miniorange/samlsp/registration/status','', 'default', 0);
				$this->redirect("*/*/index");
			}
		}
		else{
			$datahelper->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("*/*/index");
		}
	}
	
	public function sendOTPPhoneAction(){
		$params = $this->getRequest()->getParams();
		$datahelper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		$storeConfig = new Mage_Core_Model_Config();
		if(array_key_exists('phone',$params)){
			$phone = $params['phone'];
			$storeConfig ->saveConfig('miniorange/samlsp/phone',$phone,'default', 0);
			$content = json_decode($customer->send_otp_token('', $phone, FALSE, TRUE), true);
			if(strcasecmp($content['status'], 'SUCCESS') == 0) {
				$datahelper->displayMessage(' A one time passcode is sent to ' . $phone . '. Please enter the otp here to verify your email.',"SUCCESS");
				$storeConfig ->saveConfig('miniorange/samlsp/transactionID',$content['txId'],'default', 0);
				$storeConfig ->saveConfig('miniorange/samlsp/registration/status','MO_OTP_PHONE_VALIDATE','default', 0);
				$this->redirect("*/*/index");
			}else{
				$datahelper->displayMessage('There was an error in sending SMS. Please click on Resend OTP to try again.',"ERROR");
				$storeConfig ->saveConfig('miniorange/samlsp/registration/status','MO_OTP_DELIVERED_FAILURE','default', 0);
				$this->redirect("*/*/index");
			}
		}else{
			$datahelper->displayMessage('Please Enter a Phone Number.',"ERROR");
			$this->redirect("*/*/index");
		}
	}
	
	public function saveSamlSettingsAction(){
		
		$datahelper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		$params = $this->getRequest()->getParams();
		$cpBlock= $this->getBlock('MiniOrange_SamlSP_Block_MoSamlSPConfig');
		
		$saml_identity_name = '';
		$saml_login_url = '';
		$saml_issuer = '';
		$saml_x509_certificate = '';
		
		if($cpBlock->isEnabled()){
			if( $datahelper->mo_saml_check_empty_or_null( $params['saml_identity_name'] ) || $datahelper->mo_saml_check_empty_or_null( $params['saml_login_url'] ) || $datahelper->mo_saml_check_empty_or_null( $params['saml_issuer'] )  ) {
				$datahelper->displayMessage('All the fields are required. Please enter valid entries.','ERROR');
				$this->redirect("*/*/index",'serviceprovider');
			} else if(!preg_match("/^\w*$/", $params['saml_identity_name'])) {
				$datahelper->displayMessage('Please match the requested format for Identity Provider Name. Only alphabets, numbers and underscore is allowed.','ERROR');
				$this->redirect("*/*/index",'serviceprovider');
			} else{
				$saml_identity_name = trim( $params['saml_identity_name'] );
				$saml_login_url = trim( $params['saml_login_url'] );
				$saml_issuer = trim( $params['saml_issuer'] );
				$saml_x509_certificate = trim( $params['saml_x509_certificate'] );
			}
			
			$storeConfig = new Mage_Core_Model_Config();
			$storeConfig ->saveConfig('samlsp/identityName', $saml_identity_name,'default', 0);
			$storeConfig ->saveConfig('samlsp/loginUrl', $saml_login_url,'default', 0);
			$storeConfig ->saveConfig('samlsp/samlIssuer', $saml_issuer,'default', 0);
			$storeConfig ->saveConfig('samlsp/x509Certificate', $saml_x509_certificate,'default', 0);
			$storeConfig ->saveConfig('samlsp/showLink/Admin',true,'default', 0);
			$storeConfig ->saveConfig('samlsp/showLink/customer',true,'default', 0);
			isset($params['saml_response_signed']) ? $storeConfig ->saveConfig('samlsp/response/signed',1,'default', 0) : $storeConfig ->saveConfig('samlsp/response/signed',0,'default', 0);
			isset($params['saml_assertion_signed']) ?$storeConfig ->saveConfig('samlsp/assertion/signed',1,'default', 0) : $storeConfig ->saveConfig('samlsp/assertion/signed',0,'default', 0);
			
			//need to add code here for cloud broker
			
			$storeConfig ->saveConfig('samlsp/x509Certificate', Utilities::sanitize_certificate( $saml_x509_certificate ) ,'default', 0);
			$datahelper->displayMessage('Identity Provider details saved successfully.','SUCCESS');
			$this->redirect("*/*/index",'service-provider');
		}else{
			$datahelper->displayMessage('Please Login or Register.','ERROR');
			$this->redirect("*/*/index",'service-provider');
		}
	}
	
	public function saveAttrMappingAction(){
		//Save Attribute Mapping
		$datahelper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		$params = $this->getRequest()->getParams();
		$storeConfig = new Mage_Core_Model_Config();
		$cpBlock= $this->getBlock('MiniOrange_SamlSP_Block_MoSamlSPConfig');
		
		if($cpBlock->isEnabled()){
			$storeConfig ->saveConfig('samlsp/am/firstName', stripslashes($params['saml_am_first_name']),'default', 0);
			$storeConfig ->saveConfig('samlsp/am/lastName', stripslashes($params['saml_am_last_name']),'default', 0);
			$storeConfig ->saveConfig('samlsp/am/accountMatcher', stripslashes($params['saml_am_account_matcher']),'default', 0);
			
			$datahelper->displayMessage('Attribute Mapping details saved successfully','SUCCESS');
			$this->redirect("*/*/index",'attribute-role-mapping');
		}else{
			$datahelper->displayMessage('Please Login or Register.','ERROR');
			$this->redirect("*/*/index",'service-provider');
		}
	}
	
	public function saveRoleMappingAction(){
		//Save Role Mapping
		$datahelper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		$params = $this->getRequest()->getParams();
		$storeConfig = new Mage_Core_Model_Config();
		$cpBlock= $this->getBlock('MiniOrange_SamlSP_Block_MoSamlSPConfig');
		
		if($cpBlock->isEnabled()){
			
			$storeConfig ->saveConfig('samlsp/am/defaultRole', $params['saml_am_default_role'],'default', 0);
			
			$datahelper->displayMessage('Role Mapping details saved successfully.','SUCCESS');
			$this->redirect("*/*/index",'attribute-role-mapping');
		}else{
			$datahelper->displayMessage('Please Login or Register.','ERROR');
			$this->redirect("*/*/index",'service-provider');
		}
	}
	
	public function supportSubmitAction(){
		$helper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		if($helper->is_curl_installed()){
			$params = $this->getRequest()->getParams();
			$user = $session->getUser();
			$customer->submit_contact_us($params['query_email'], $params['query_phone'], $params['query'], $user);
			$helper->displayMessage('Your query has been sent. We will get in touch with you soon',"SUCCESS");
			$this->redirect("*/*/index");
		}
		else{
			$helper->displayMessage('cURL is not enabled. Please <a id="cURL" href="#cURLfaq">click here</a> to see how to enable cURL.',"ERROR");
			$this->redirect("*/*/index");
		}
	}
	
	public function samlLoginRequestAction(){
		$helper = $this->getHelper1();
		$customer = $this->getHelper2();
		$params = $this->getRequest()->getParams();
		if($helper->mo_saml_is_sp_configured()){
			if($params['q'] == 'testConfig')
				$sendRelayState = 'testValidate';
			
			$ssoUrl = $helper->getConfig("loginUrl");				
			$force_authn = $helper->getConfig('forceAuthn'); 
			$cpBlock= $this->getBlock('MiniOrange_SamlSP_Block_MoSamlSPConfig');
			$acsUrl = $cpBlock->getBaseUrl();
			$issuer = $cpBlock->getIssuerUrl();
			
			$samlRequest = Utilities::createAuthnRequest($acsUrl, $issuer, $ssoUrl, $force_authn);

			$samlRequest = "SAMLRequest=" . $samlRequest . "&RelayState=" . urlencode($sendRelayState) . '&SigAlg='. urlencode(XMLSecurityKey::RSA_SHA256);
			$param =array( 'type' => 'private');
			$key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, $param);
			$certFilePath = $cpBlock->getResouceURL('sp-key.key');
			$key->loadKey($certFilePath, TRUE);
			$objXmlSecDSig = new XMLSecurityDSig();
			$signature = $key->signData($samlRequest);
			$signature = base64_encode($signature);
			$redirect = $ssoUrl;
			if (strpos($ssoUrl,'?') !== false) {
				$redirect .= '&';
			} else {
				$redirect .= '?';
			}
			$redirect .= $samlRequest . '&Signature=' . urlencode($signature);
			header('Location: '.$redirect);
			exit();
		}
	}
	
	//feature not ready yet
	public function saveCloudBrokerSettingsAction(){
		$datahelper = $this->getHelper1();
		$customer = $this->getHelper2();
		$storeConfig = new Mage_Core_Model_Config();
		$params = $this->getRequest()->getParams();
		$cpBlock= $this->getBlock('MiniOrange_SamlSP_Block_MoSamlSPConfig');
		
		if($cpBlock->isEnabled()){
			$value = array_key_exists('mo_saml_enable_cloud_broker',$params) ? stripslashes($params['mo_saml_enable_cloud_broker']) : 0;
			$storeConfig ->saveConfig('samlsp/enableCloudBroker', $value, 'default', 0);
			$datahelper->displayMessage('Settings Saved Successfully','SUCCESS');
			$this->redirect("*/*/index",'identity-provider');
		}else{
			$datahelper->displayMessage('Please Login or Register.','ERROR');
			$this->redirect("*/*/index",'service-provider');
		}
	}
	
	public function forceAuthenSettingsAction(){
		$datahelper = $this->getHelper1();
		$customer = $this->getHelper2();
		$storeConfig = new Mage_Core_Model_Config();
		$params = $this->getRequest()->getParams();
		$cpBlock= $this->getBlock('MiniOrange_SamlSP_Block_MoSamlSPConfig');
		
		if($cpBlock->isEnabled()){
			$value = array_key_exists('mo_saml_force_authentication',$params) ? stripslashes($params['mo_saml_force_authentication']) : false;
			$storeConfig ->saveConfig('samlsp/forceAuthn', $value, 'default', 0);
			$datahelper->displayMessage('Settings Saved Successfully','SUCCESS');
			$this->redirect("*/*/index",'settings');
		}else{
			$datahelper->displayMessage('Please Login or Register.','ERROR');
			$this->redirect("*/*/index",'service-provider');
		}
	}
	
	public function showLinkSettingsAction(){
		$datahelper = $this->getHelper1();
		$customer = $this->getHelper2();
		$storeConfig = new Mage_Core_Model_Config();
		$params = $this->getRequest()->getParams();
		$cpBlock= $this->getBlock('MiniOrange_SamlSP_Block_MoSamlSPConfig');
		
		if($cpBlock->isEnabled()){
			$value1 = array_key_exists('mo_saml_show_admin_link',$params) ? stripslashes($params['mo_saml_show_admin_link']) : false;
			$value2 = array_key_exists('mo_saml_show_customer_link',$params) ? stripslashes($params['mo_saml_show_customer_link']) : false;
			$storeConfig ->saveConfig('samlsp/showLink/Admin', $value1 ,'default', 0);
			$storeConfig ->saveConfig('samlsp/showLink/customer', $value2 ,'default', 0);
			$datahelper->displayMessage('Settings Saved Successfully','SUCCESS');
			$this->redirect("*/*/index",'settings');
		}else{
			$datahelper->displayMessage('Please Login or Register.','ERROR');
			$this->redirect("*/*/index",'service-provider');
		}
	}
	

	
	public function resendOTPAction(){
		$datahelper = $this->getHelper1();
		$customer = $this->getHelper2();
		$params = $this->getRequest()->getParams();	
		$storeConfig = new Mage_Core_Model_Config();
		
		$email = $datahelper->getConfig('email');
		$phone = $datahelper->getConfig('phone');
		
		if(strcasecmp($datahelper->getConfig('status'),"MO_OTP_EMAIL_VALIDATE")==0){
			$device = $email;
			$status = 'MO_OTP_EMAIL_VALIDATE';
			$content = json_decode($customer->send_otp_token($email,''), true);
		}else{
			$device = $phone;
			$status = 'MO_OTP_PHONE_VALIDATE';
			$content = json_decode($customer->send_otp_token('', $phone, FALSE, TRUE), true);
		}
		
		if(strcasecmp($content['status'], 'SUCCESS') == 0) {
				$datahelper->displayMessage(' A one time passcode is sent to ' . $device . ' again. Please check if you got the otp and enter it here.','SUCCESS');
				$storeConfig ->saveConfig('miniorange/samlsp/transactionID',$content['txId'], 'default', 0);
				$storeConfig ->saveConfig('miniorange/samlsp/registration/status',$status, 'default', 0);
				$this->redirect("*/*/index");
		}else{
				$datahelper->displayMessage('There was an error in sending email. Please click on Resend OTP to try again.','ERROR');
				$storeConfig ->saveConfig('miniorange/samlsp/registration/status','MO_OTP_DELIVERED_FAILURE', 'default', 0);
				$this->redirect("*/*/index");
		}
	}
    	
	private function forgotPass($email){
		$helper = $this->getHelper1();
		$customer = $this->getHelper2();
		$params = $this->getRequest()->getParams();
		$content = json_decode($customer->forgot_password($email,$helper->getConfig('customerKey'),$helper->getConfig('apiKey')), true); 
		if(strcasecmp($content['status'], 'SUCCESS') == 0){
			$helper->displayMessage('Your new password has been generated and sent to '.$email.'.',"SUCCESS");
			$this->redirect("*/*/index");
		}
		else{
			$helper->displayMessage('Sorry we encountered an error while reseting your password.',"ERROR");
			$this->redirect("*/*/index");
		}
	}
	
	private function getHelper1(){
		return Mage::helper($this->_helper1);
	}
	
	private function getHelper2(){
		return Mage::helper($this->_helper2);
	}
	
	private function redirect($url,$query=null){
		$redirect = Mage::helper("adminhtml")->getUrl($url);
		!is_null($query) ? Mage::app()->getResponse()->setRedirect($redirect."?q=".$query) : Mage::app()->getResponse()->setRedirect($redirect); 
	}
	
	private function getSession(){
		return  Mage::getSingleton('admin/session');
	}
	
	private function getId(){
		return $this->getSession()->getUser()->getUserId();
	}
	
	private function getBlock($value){
		return $this->getLayout()->getBlockSingleton($value);
	}
	
	private function login($username,$email,$type,$relayState){
		Mage::getSingleton('core/session', array('name' => 'adminhtml'));	
		
		if($type === 'username'){
			$user = Mage::getModel('admin/user')->loadByUsername($username); 
		}else{
			$user_name= Mage::getModel('admin/user')->getCollection()->addFieldToFilter('email',$email)->getFirstItem()->getUsername;
			$user = Mage::getModel('admin/user')->loadByUsername($user_name);
		}
		
		if (Mage::getSingleton('adminhtml/url')->useSecretKey()) {
		  Mage::getSingleton('adminhtml/url')->renewSecretUrls();
		}
		
		$session = Mage::getSingleton('admin/session');
		$session->setIsFirstVisit(true);
		$session->setUser($user);
		$session->setAcl(Mage::getResourceModel('admin/acl')->loadAcl());

		Mage::dispatchEvent('admin_session_user_login_success',array('user'=>$user));

		if ($session->isLoggedIn()) {
			if(!empty($relayState)){
				header('Location: '.$relayState);
			}else{
				$redirectUrl = Mage::getSingleton('adminhtml/url')->getUrl(Mage::getModel('admin/user')->getStartupPageUrl(), array('_current' => false));
				header('Location: ' . $redirectUrl);
			}
			exit;
		}
	}
	
	private function create_customer(){
		$datahelper = $this->getHelper1();
		$customer = $this->getHelper2();
		$storeConfig = new Mage_Core_Model_Config();
		$customerKey = json_decode( $customer->create_customer($datahelper->getConfig('email'),'',$datahelper->getConfig('pass')), true );
		if( strcasecmp( $customerKey['status'], 'CUSTOMER_USERNAME_ALREADY_EXISTS') == 0 ) {
					$this->get_current_customer($datahelper->getConfig('email'),$datahelper->getConfig('pass'));
		} else if( strcasecmp( $customerKey['status'], 'SUCCESS' ) == 0 ) {
			$storeConfig ->saveConfig('miniorange/samlsp/customerKey',$customerKey['id'], 'default', 0);
			$storeConfig ->saveConfig('miniorange/samlsp/apiKey',$customerKey['apiKey'], 'default', 0);
			$storeConfig ->saveConfig('miniorange/samlsp/token',$customerKey['token'], 'default', 0);
			$storeConfig ->saveConfig('miniorange/samlsp/pass','','default', 0);
			$storeConfig ->saveConfig('miniorange/samlsp/transactionID','','default', 0);
			$storeConfig ->saveConfig('miniorange/samlsp/registration/status','', 'default', 0);
			$datahelper->displayMessage('Thank you for registering with miniorange.',"SUCCESS");
			$this->redirect("*/*/index");
		}
		$storeConfig ->saveConfig('miniorange/samlsp/pass','','default', 0);
	}
	
	private function get_current_customer($email,$password){
		$datahelper = $this->getHelper1();
		$customer = $this->getHelper2();
		$session = $this->getSession();
		$storeConfig = new Mage_Core_Model_Config();
		$content = $customer->get_customer_key($email,$password);
		$customerKey = json_decode($content, true);
		if(json_last_error() == JSON_ERROR_NONE) {
			$storeConfig = new Mage_Core_Model_Config();
			$storeConfig ->saveConfig('miniorange/samlsp/email',$email, 'default', 0);
			$storeConfig ->saveConfig('miniorange/samlsp/customerKey',$customerKey['id'], 'default', 0);
			$storeConfig ->saveConfig('miniorange/samlsp/apiKey',$customerKey['apiKey'], 'default', 0);
			$storeConfig ->saveConfig('miniorange/samlsp/token',$customerKey['token'], 'default', 0);
			$storeConfig ->saveConfig('miniorange/samlsp/pass','','default', 0);
			$storeConfig ->saveConfig('miniorange/samlsp/transactionID','','default', 0);
			$storeConfig ->saveConfig('miniorange/samlsp/registration/status','', 'default', 0);
			$datahelper->displayMessage('Your account has been retrieved successfully.',"SUCCESS");
			$this->redirect("*/*/index");
		}
		else{
			$datahelper->displayMessage('You already have an account with miniOrange. Please enter a valid password.',"ERROR");
			$storeConfig ->saveConfig('miniorange/samlsp/pass','','default', 0);
			$storeConfig ->saveConfig('miniorange/samlsp/transactionID','','default', 0);
			$storeConfig ->saveConfig('miniorange/samlsp/registration/status','MO_VERIFY_CUSTOMER', 'default', 0);
			$this->redirect("*/*/index");
		}
	}

	
}