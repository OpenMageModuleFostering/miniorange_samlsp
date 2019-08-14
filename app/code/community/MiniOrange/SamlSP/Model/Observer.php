<?php
include Mage::getModuleDir('', 'MiniOrange_SamlSP').DS.'Helper'.DS.'Utilities.php';
include Mage::getModuleDir('', 'MiniOrange_SamlSP').DS.'Helper'.DS.'Response.php';
include Mage::getModuleDir('', 'MiniOrange_SamlSP').DS.'Helper'.DS.'LogoutRequest.php';
include Mage::getModuleDir('', 'MiniOrange_SamlSP').DS.'Helper'.DS.'encryption.php';
class MiniOrange_SamlSP_Model_Observer
{
	private $_helper1 = "MiniOrange_SamlSP"; 
	private $_helper2 = "MiniOrange_SamlSP/moSamlUtility";
	
	public function controllerActionPredispatch(Varien_Event_Observer $observer){
		$request = Mage::app()->getRequest();
		$helper = $this->getHelper2();	
		$data = $this->getHelper1();	
		if($request->getRequestedControllerName() == 'index' && $request->getRequestedActionName() == 'index'){
			if(array_key_exists('SAMLResponse', $_REQUEST) && !empty($_REQUEST['SAMLResponse'])) {
				$this->samlResponse($_POST,$_GET);
			}
		}else if($request->getRequestedControllerName() == 'index' && $request->getRequestedActionName() == 'login'){
			$user = !empty($data->getConfig('tempUser')) ? Mage::getModel('admin/user')->load($data->getConfig('tempUser')) : '' ;
			if(!empty($user)){ 
				$this->adminLogin($user); 
			}
		}else if($request->getRequestedControllerName() == 'adminhtml_index' && $request->getRequestedActionName() == 'samlLoginRequest'){
			if(!array_key_exists('q',$_REQUEST) || $_REQUEST['q']!='testConfig')
				$this->sendLoginRequest($_REQUEST['q']);
		}else if($request->getRequestedControllerName() == 'account' && $request->getRequestedActionName() == 'login' && $data->getConfig('loginRedirect')
			&& !array_key_exists('saml_sso',$_REQUEST) && $_REQUEST['saml_sso']!='false'){
				if(!Mage::getSingleton('core/session')->getRequestSent())
					$this->sendLoginRequest(Mage::helper('core/url')->getCurrentUrl());
				else
					Mage::getSingleton('core/session')->unsRequestSent();
		}
	}
	
	private function sendLoginRequest($sendRelayState){
		$helper = $this->getHelper1();
		$customer = $this->getHelper2();
		if($helper->mo_saml_is_sp_configured()){
			$ssoUrl = $helper->getConfig("loginUrl");			
			$force_authn = $helper->getConfig("forceAuthn"); 
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
			$response = Mage::app()->getFrontController()->getResponse();
			$response->setRedirect($redirect);
		}
	}

	private function samlResponse($POSTED,$GET){
		$datahelper = $this->getHelper1();
		$customer = $this->getHelper2();
		$cpBlock = $this->getBlock('MiniOrange_SamlSP_Block_MoSamlSPConfig');
		$session = $cpBlock->getSession();
		$sp_base_url = $cpBlock->getBaseUrl();
		$samlResponse = $POSTED['SAMLResponse'];
		$samlResponse = base64_decode($samlResponse);
		if(array_key_exists('SAMLResponse', $GET) && !empty($GET['SAMLResponse'])) {
			$samlResponse = gzinflate($samlResponse);
		}
		
		$document = new DOMDocument();
		$document->loadXML($samlResponse);
		$samlResponseXml = $document->firstChild;
		
		// It's a SAML Assertion
		if(array_key_exists('RelayState', $POSTED) && !empty( $POSTED['RelayState'] ) && $POSTED['RelayState'] != '/') {
			$relayState = $POSTED['RelayState'];
		} else {
			$relayState = '';
		}
		
		$certFromPlugin = $datahelper->getConfig('certificate');
		$certfpFromPlugin = XMLSecurityKey::getRawThumbprint($certFromPlugin);
		
		$acsUrl = $cpBlock->getBaseUrl();
		$samlResponse = new SAML2_Response($samlResponseXml);
		
		$responseSignatureData = $samlResponse->getSignatureData();
		$assertionSignatureData = current($samlResponse->getAssertions())->getSignatureData();

		/* convert to UTF-8 character encoding*/
		$certfpFromPlugin = iconv("UTF-8", "CP1252//IGNORE", $certfpFromPlugin);
		
		/* remove whitespaces */
		$certfpFromPlugin = preg_replace('/\s+/', '', $certfpFromPlugin);	
		
		$responseSignedOption = $datahelper->getConfig('responseSigned');
		$assertionSignedOption = $datahelper->getConfig('assertionSigned');
		
		/* Validate signature */
		if($responseSignedOption) {
			$validSignature = Utilities::processResponse($acsUrl, $certfpFromPlugin, $responseSignatureData, $samlResponse);
			if($validSignature === FALSE) {
				echo "Invalid signature in the SAML Response.";
				exit;
			}
		}
		
		if($assertionSignedOption) {
			$validSignature = Utilities::processResponse($acsUrl, $certfpFromPlugin, $assertionSignatureData, $samlResponse);
			if($validSignature === FALSE) {
				echo "Invalid signature in the SAML Assertion.";
				exit;
			}
		}
		
		// verify the issuer and audience from saml response
		$issuer = $datahelper->getConfig('samlIssuer');
		$spEntityId = $cpBlock->getIssuerUrl();
		Utilities::validateIssuerAndAudience($samlResponse,$spEntityId, $issuer);
		
		$ssoemail = current(current($samlResponse->getAssertions())->getNameId());
		$attrs = current($samlResponse->getAssertions())->getAttributes();
		$attrs['NameID'] = array("0" => $ssoemail);
		$sessionIndex = current($samlResponse->getAssertions())->getSessionIndex();
		
		$this->mo_saml_checkMapping($attrs,$relayState,$sessionIndex);
    }
	
	private function mo_saml_checkMapping($attrs,$relayState,$sessionIndex){
		try {
			$helper = $this->getHelper1();
			$customer = $this->getHelper2();
			
			$emailAttribute = $helper->getConfig('amEmail');
			$usernameAttribute = $helper->getConfig('amUsername');
			$firstName = $helper->getConfig('amFirstName');
			$lastName = $helper->getConfig('amLastName');
			$defaultRole = $helper->getConfig('defaultRole');
			$checkIfMatchBy = $helper->getConfig('amAccountMatcher');
			$user_email = '';
			$userName = '';

			if(is_null($defaultRole)){
				$defaultRole = 'General';
			}
			
			if(!empty($attrs)){
				if(!empty($firstName) && array_key_exists($firstName, $attrs))
					$firstName = $attrs[$firstName][0];
				else
					$firstName = $attrs['NameID'][0];

				if(!empty($lastName) && array_key_exists($lastName, $attrs))
					$lastName = $attrs[$lastName][0];
				else
					$lastName = '';
				
				if(empty($checkIfMatchBy)) {
					$checkIfMatchBy = "email";
				}

				if(!empty($usernameAttribute) && array_key_exists($usernameAttribute, $attrs))
					$userName = $attrs[$usernameAttribute][0];
				else
					$userName = $checkIfMatchBy=='username' ? $attrs['NameID'][0] : null;

				if(!empty($emailAttribute) && array_key_exists($emailAttribute, $attrs))
					$user_email = $attrs[$emailAttribute][0];
				else
					$user_email = $checkIfMatchBy=='email' ? $attrs['NameID'][0] : null;
				
				if(!empty($groupName) && array_key_exists($groupName, $attrs))
					$groupName = $attrs[$groupName];
				else
					$groupName = array();
			}
			
			if($relayState=='testValidate'){
				$this->mo_saml_show_test_result($firstName,$lastName,$user_email,$groupName,$attrs);
			}else{
				$this->mo_saml_login_user($user_email, $firstName, $lastName, $userName, $groupName, $defaultRole, $relayState, $checkIfMatchBy, $sessionIndex, $attrs['NameID'][0], $attrs);
			}
		}
		catch (Exception $e) {
			echo sprintf("An error occurred while processing the SAML Response.");
			exit;
		}
	}
	
	private function mo_saml_login_user($user_email=null, $firstName, $lastName, $userName=null, $groupName, $defaultRole, $relayState, $checkIfMatchBy, $sessionIndex = '', $nameId = '', $attrs = null){
		$helper = $this->getHelper1();
		$customer = $this->getHelper2();
		$cpBlock = $this->getBlock('MiniOrange_SamlSP_Block_MoSamlSPConfig');
		$admin = false;
		
		$roles = Mage::getModel('admin/roles')->getCollection();
		$groups = Mage::helper('customer')->getGroups();
		
		if($checkIfMatchBy=='email')
			$user = Mage::getModel('admin/user')->getCollection()->addFieldToFilter('email',$user_email)->getFirstItem();
		else
			$user = Mage::getModel('admin/user')->loadByUsername($userName);
		
		if(!is_null($user->getUserId())){
			$admin = true;
		}else{
			$user = Mage::getModel("customer/customer")->getCollection()->addFieldToFilter('email',$user_email)->getFirstItem();
		}
	
		if(!is_null($user->getUserId()) || !is_null($user->getId())) {
			$user_id = $user->getUserId();
			$user_id = !is_null($user_id) ?  $user_id : $user->getId();

			if( !empty($firstName) ){
				$this->saveConfig('firstname',$firstName,$user_id,$admin);
			}
			if( !empty($lastName) ){
				$this->saveConfig('lastname',$lastName,$user_id,$admin);
			}
			
			/*if(!is_null( $attrs )) {
				update_user_meta( $user_id, 'mo_saml_user_attributes', $attrs);
			}*/
			
			if(!empty( $sessionIndex)) {
				$this->saveConfig('mo_saml_session_index',$sessionIndex,$user_id,$admin);
			}
			if(!empty( $nameId )) {
				$this->saveConfig('mo_saml_name_id',$nameId,$user_id,$admin);
			}
			
			if($admin){
				$storeConfig = new Mage_Core_Model_Config();
				$storeConfig ->saveConfig('samlsp/temp/user',$user->getUserId(),'default', 0);
				$redirectUrl = $cpBlock->getAdminLoginUrl();
				$response = Mage::app()->getFrontController()->getResponse();
				$response->setRedirect($redirectUrl);
			}else{
				$this->customerLogin($user,$checkIfMatchBy,$relayState);
			}
			
		}else{
				
			$random_password = Mage::helper('core')->getRandomString($length = 8);
			$username = !is_null($userName)? $userName : $user_email;
			$siteurl = $cpBlock->getBaseUrl();
			$siteurl = substr($siteurl,strpos($siteurl,'//'),strlen($siteurl)-1);
			$email = !is_null($user_email)? $user_email : $username .'@'.$siteurl;
			$websiteId = Mage::app()->getWebsite()->getId();
			$store = Mage::app()->getStore();
			
			$setDefaultRole = array();

			if(!is_null($defaultRole)) {
				foreach($roles as $role):
					$admin = $defaultRole==$role->getRoleName()? true : false;
					if($admin){ array_push($setDefaultRole, $role->getRoleId() ); break; }
				endforeach; 

				if(!$admin){
					foreach($groups as $group):
						$customer = $defaultRole==$group->getCustomerGroupCode()? true : false;
						if($customer){ array_push($setDefaultRole, $group->getCustomerGroupId() ); break; } 
					endforeach;
				}
				
				if($admin){						
					$user = $this->createAdminUser($userName,$firstName,$lastName,$email,$random_password,$setDefaultRole);
					$user_id = $user->getId();
				}else{
					$user = $this->createCustomer($userName,$firstName,$lastName,$email,$random_password,$setDefaultRole);
					$user_id = $user->getUserId();
				}
				
			}
				
			/*if(!is_null( $attrs )) {
				update_user_meta( $user_id, 'mo_saml_user_attributes', $attrs);
			}*/
			
			if(!empty( $sessionIndex)) {
				$this->saveConfig('mo_saml_session_index',$sessionIndex,$user_id,$admin);
			}
			if(!empty( $nameId )) {
				$this->saveConfig('mo_saml_name_id',$nameId,$user_id,$admin);
			}
			
			if($admin){
				$storeConfig = new Mage_Core_Model_Config();
				$storeConfig ->saveConfig('samlsp/temp/user',$user->getUserId(),'default', 0);
				$redirectUrl = $cpBlock->getAdminLoginUrl();
				$response = Mage::app()->getFrontController()->getResponse();
				$response->setRedirect($redirectUrl);
			}else{
				$this->customerLogin($user,$checkIfMatchBy,$relayState);
			}
		}
	}
	
	private function mo_saml_show_test_result($firstName,$lastName,$user_email,$groupName,$attrs){
		$cpBlock = $this->getBlock('MiniOrange_SamlSP_Block_MoSamlSPConfig');
		ob_end_clean();
		echo '<div style="font-family:Calibri;padding:0 3%;">';
		if(!empty($user_email)){
			echo '<div style="color: #3c763d;
					background-color: #dff0d8; padding:2%;margin-bottom:20px;text-align:center; border:1px solid #AEDB9A; font-size:18pt;">TEST SUCCESSFUL</div>
					<div style="display:block;text-align:center;margin-bottom:4%;"><img style="width:15%;"src="'.$cpBlock->getImage('right').'"></div>';
		}else{
			echo '<div style="color: #a94442;background-color: #f2dede;padding: 15px;margin-bottom: 20px;text-align:center;border:1px solid #E6B3B2;font-size:18pt;">TEST FAILED</div>
					<div style="color: #a94442;font-size:14pt; margin-bottom:20px;">WARNING: Some Attributes Did Not Match.</div>
					<div style="display:block;text-align:center;margin-bottom:4%;"><img style="width:15%;"src="'.$cpBlock->getImage('wrong').'"></div>';
		}
			echo '<span style="font-size:14pt;"><b>Hello</b>, '.$user_email.'</span><br/><p style="font-weight:bold;font-size:14pt;margin-left:1%;">ATTRIBUTES RECEIVED:</p>
					<table style="border-collapse:collapse;border-spacing:0; display:table;width:100%; font-size:14pt;background-color:#EDEDED;">
					<tr style="text-align:center;"><td style="font-weight:bold;border:2px solid #949090;padding:2%;">ATTRIBUTE NAME</td><td style="font-weight:bold;padding:2%;border:2px solid #949090; word-wrap:break-word;">ATTRIBUTE VALUE</td></tr>';
		if(!empty($attrs))
			foreach ($attrs as $key => $value)
				echo "<tr><td style='font-weight:bold;border:2px solid #949090;padding:2%;'>" .$key . "</td><td style='padding:2%;border:2px solid #949090; word-wrap:break-word;'>" .implode("<br/>",$value). "</td></tr>";
			else
				echo "No Attributes Received.";
			echo '</table></div>';
			echo '<div style="margin:3%;display:block;text-align:center;"><input style="padding:1%;width:100px;background: #0091CD none repeat scroll 0% 0%;cursor: pointer;font-size:15px;border-width: 1px;border-style: solid;border-radius: 3px;white-space: nowrap;box-sizing: border-box;border-color: #0073AA;box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset;color: #FFF;"type="button" value="Done" onClick="self.close();"></div>';
			exit;
	}
	
	private function getBlock($value){
		return Mage::getSingleton('core/layout')->getBlockSingleton($value);
	}
	
	private function getHelper1(){
		return Mage::helper($this->_helper1);
	}
	
	private function getHelper2(){
		return Mage::helper($this->_helper2);
	}
	
	private function customerLogin($user,$type,$relayState){
		$cpBlock = $this->getBlock('MiniOrange_SamlSP_Block_MoSamlSPConfig');
		$session = Mage::getSingleton('customer/session');
		$session->setCustomerAsLoggedIn($user);
		if ($session->isLoggedIn()) {
				$url = $cpBlock->getBaseUrl().'customer/account/';
				$response = Mage::app()->getFrontController()->getResponse();
				$response->setRedirect($url);
		}
	}
	
	private function adminLogin($user){
		$cpBlock = $this->getBlock('MiniOrange_SamlSP_Block_MoSamlSPConfig');
		if (Mage::getSingleton('adminhtml/url')->useSecretKey()) {
		  Mage::getSingleton('adminhtml/url')->renewSecretUrls();
		}
		
		$session = Mage::getSingleton('admin/session');
		$session->setIsFirstVisit(true);
		$session->setUser($user);
		$session->setAcl(Mage::getResourceModel('admin/acl')->loadAcl());

		Mage::dispatchEvent('admin_session_user_login_success',array('user'=>$user));
		
		$storeConfig = new Mage_Core_Model_Config();
		$storeConfig ->saveConfig('samlsp/temp/user','','default', 0);
		
		if ($session->isLoggedIn()) {
			$redirectUrl = Mage::getSingleton('adminhtml/url')->getUrl(Mage::getModel('admin/user')->getStartupPageUrl(), array('_current' => false));
			$response = Mage::app()->getFrontController()->getResponse();
			$response->setRedirect($redirectUrl);
		}
	}
	
	private function saveConfig($url,$value,$id,$admin){
		$data = array($url=>$value);
		$model = $admin ? Mage::getModel('admin/user')->load($id)->addData($data) : Mage::getModel('customer/customer')->load($id)->addData($data);
		try {
			$model->setId($id)->save(); 
		} catch (Exception $e){
			Mage::log($e->getMessage(), null, 'miniorange_error.log', true);
		}
	}
	
	private function createAdminUser($userName,$firstName,$lastName,$email,$random_password,$role_assigned){
		$user = Mage::getModel('admin/user')->setData(array(
			'username'  => $userName,
			'firstname' => $firstName,
			'lastname'  => $lastName,
			'email'     => $email,
			'password'  => $random_password,
			'is_active' => 1
		))->save();
		$user_id=$user->getUserId();
		$user->setRoleIds($role_assigned)->setRoleUserId($user_id)->saveRelations();
		return $user;
	}
	
	private function createCustomer($userName,$firstName,$lastName,$email,$random_password,$role_assigned){
		$websiteId = Mage::app()->getWebsite()->getId();
		$store = Mage::app()->getStore();
		$user = Mage::getModel("customer/customer");
		$user   ->setWebsiteId($websiteId)
				->setStore($store)
				->setFirstname($firstName)
				->setLastname($lastName)
				->setEmail($email)
				->setPassword($random_password);
		$user->save();
		$user_id=$user->getId();

		if(is_array($role_assigned))
			$assign_role = $role_assigned[0];
		else
			$assign_role = $role_assigned;
		$user->setData('group_id',$assign_role); // customer cannot have multiple groups
		$user->save();
		
		return $user;
	}
}