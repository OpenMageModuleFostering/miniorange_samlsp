<?php
class MiniOrange_SamlSP_Helper_Data extends Mage_Core_Helper_Abstract {
	
	function adminExists($username) {
		$adminuser = Mage::getModel ( 'admin/user' );
		$adminuser->loadByUsername ( $username );
		if ($adminuser->getId ()) {
			return true;
		} else {
			return false;
		}
	}


	function getAdmin($username) {
		$adminuser = Mage::getModel ( 'admin/user' );
		$adminuser->loadByUsername ( $username );
		if ($adminuser->getId ()) {
			return $adminuser;
		} else {
			return;
		}
	}
	
	public function mo_saml_check_empty_or_null( $value ) {
		if( ! isset( $value ) || empty( $value ) ) {
			return true;
		}
		return false;
	}
	
	/* Function to extract config stored in the database */
	function getConfig($config, $id=null) {
		switch ($config) {
			case 'email' :
				$result =  Mage::getStoreConfig ( 'miniorange/samlsp/email' );	
				break;
			case 'customerKey' :
				$result = Mage::getStoreConfig ( 'miniorange/samlsp/customerKey' );
				break;
			case 'apiKey' :
				$result = Mage::getStoreConfig ( 'miniorange/samlsp/apiKey' );
				break;
			case 'apiToken' :
				$result = Mage::getStoreConfig ( 'miniorange/samlsp/token' );
				break;
			case 'txtId':
				$result = Mage::getStoreConfig ( 'miniorange/samlsp/transactionID' );
				break;
			case 'status':
				$result = Mage::getStoreConfig ( 'miniorange/samlsp/registration/status' );
				break;
			case 'pass':
				$result = Mage::getStoreConfig ( 'miniorange/samlsp/pass' );
				break;
			case 'phone':
				$result = Mage::getStoreConfig ( 'miniorange/samlsp/phone' );
				break;
			case 'identityName' :
				$result = Mage::getStoreConfig ( 'samlsp/identityName' );
				break;
			case 'loginBindingType' :
				$result = Mage::getStoreConfig ( 'samlsp/loginBindingType' );
				break;
			case 'loginUrl' :
				$result = Mage::getStoreConfig ( 'samlsp/loginUrl' );
				break;
			case 'logoutBindingType' :
				$result = Mage::getStoreConfig ( 'samlsp/logoutBindingType' );
				break;
			case 'logoutUrl' :
				$result = Mage::getStoreConfig ( 'samlsp/logoutUrl' );
				break;
			case 'samlIssuer' :
				$result = Mage::getStoreConfig ( 'samlsp/samlIssuer' );
				break;
			case 'responseSigned' :
				$result = Mage::getStoreConfig ( 'samlsp/response/signed' );
				break;
			case 'assertionSigned' :
				$result = Mage::getStoreConfig ( 'samlsp/assertion/signed' );
				break;
			case 'certificate' :
				$result = Mage::getStoreConfig ( 'samlsp/x509Certificate' );
				break;
			case 'amUsername' :
				$result = Mage::getStoreConfig ( 'samlsp/am/username' );
				break;
			case 'amEmail' :
				$result = Mage::getStoreConfig ( 'samlsp/am/email' );
				break;
			case 'amFirstName' :
				$result = Mage::getStoreConfig ( 'samlsp/am/firstName' );
				break;
			case 'amLastName' :
				$result = Mage::getStoreConfig ( 'samlsp/am/lastName' );
				break;
			case 'amGroupName' :
				$result = Mage::getStoreConfig ( 'samlsp/am/groupName' );
				break;
			case 'amAccountMatcher' :
				$result = Mage::getStoreConfig ( 'samlsp/am/accountMatcher' );
				break;
			case 'defaultRole' :
				$result = Mage::getStoreConfig ( 'samlsp/am/defaultRole' );
				break;
			case 'unlistedUserRole' :
				$result = Mage::getStoreConfig ( 'samlsp/am/dontAllow/UnlistedUserRole' );
				break;
			case 'createUserIfRoleNotMapped' :
				$result = Mage::getStoreConfig ( 'samlsp/am/dontCreateUserIfRoleNotMapped' );
				break;
			case 'samlAdminRoleMapping' :
				$result = Mage::getStoreConfig ( 'samlsp/am/admin/RoleMapping' );
				break;
			case 'samlCustomerRoleMapping' :
				$result = Mage::getStoreConfig ( 'samlsp/am/customer/RoleMapping' );
				break;
			case 'tempUser' :
				$result = Mage::getStoreConfig ( 'samlsp/temp/user' );
				break;
			case 'loginRedirect':
				$result = Mage::getStoreConfig ( 'samlsp/allow/login/redirect' );
				break;
			case 'byPassRedirect':
				$result = Mage::getStoreConfig ( 'samlsp/bypass/redirect' );
				break;
			case 'forceAuthn':
				$result = Mage::getStoreConfig ( 'samlsp/forceAuthn' );
				break;
			case 'adminLink':
				$result = Mage::getStoreConfig ( 'samlsp/showLink/Admin' );
				break;
			case 'customerLink':
				$result = Mage::getStoreConfig ( 'samlsp/showLink/customer' );
				break;
			case 'enableCloudBroker':
				$result = Mage::getStoreConfig ( 'samlsp/enableCloudBroker' );
				break;
			case 'adminSessionIndex':
				$result = Mage::getModel ( 'admin/user' )->load ( $id )->getData ( 'mo_saml_session_index' );
				break;
			case 'customerSessionIndex':
				$result = Mage::getModel ( 'customer/customer' )->load ( $id )->getData ( 'mo_saml_session_index' );
				break;
			case 'customerNameID':
				$result = Mage::getModel ( 'customer/customer' )->load ( $id )->getData ( 'mo_saml_name_id' );
				break;
			case 'adminNameID':
				$result = Mage::getModel ( 'admin/user' )->load ( $id )->getData ( 'mo_saml_name_id' );
				break;
			default :
				return;
				break;
		}
		return $result;
	}
	
	function mo_saml_is_sp_configured(){
		$saml_login_url = $this->getConfig('loginUrl');
		if( !empty($saml_login_url)) {
			return 1;
		} else {
			return 0;
		}
	}
	
	/* Function to show his partial registered email to user */
	function showEmail($id) {
		$email = $this->getConfig ( 'email', $id );
		$emailsize = strlen ( $email );
		$partialemail = substr ( $email, 0, 1 );
		$temp = strrpos ( $email, "@" );
		$endemail = substr ( $email, $temp - 1, $emailsize );
		for($i = 1; $i < $temp; $i ++) {
			$partialemail = $partialemail . 'x';
		}
		$showemail = $partialemail . $endemail;
		
		return $showemail;
	}
	
	/* Function to show his partial phone number to user */
	function showPhone($id) {
		$phone = $this->getConfig ( 'phone', $id );
		$phonesize = strlen ( $phone );
		$endphone = substr ( $phone, $phonesize - 4, $phonesize );
		$partialphone = '+';
		for($i = 1; $i < $phonesize - 4; $i ++) {
			$partialphone = $partialphone . 'x';
		}
		$showphone = $partialphone . $endphone;
		
		return $showphone;
	}
	
	/* Function to show his partial phone number to user */
	function showCustomerPhone($id) {
		$phone = $this->getConfig ( 'miniorange_phone', $id );
		$phonesize = strlen ( $phone );
		$endphone = substr ( $phone, $phonesize - 4, $phonesize );
		$partialphone = '+';
		for($i = 1; $i < $phonesize - 4; $i ++) {
			$partialphone = $partialphone . 'x';
		}
		$showphone = $partialphone . $endphone;
	
		return $showphone;
	}
	
	function showCustomerEmail($id) {
		$email = $this->getConfig ( 'miniorange_email', $id );
		$emailsize = strlen ( $email );
		$partialemail = substr ( $email, 0, 1 );
		$temp = strrpos ( $email, "@" );
		$endemail = substr ( $email, $temp - 1, $emailsize );
		for($i = 1; $i < $temp; $i ++) {
			$partialemail = $partialemail . 'x';
		}
		$showemail = $partialemail . $endemail;
		
		return $showemail;
	}
	
	/* Function to check if cURL is enabled */
	function is_curl_installed() {
		if (in_array ( 'curl', get_loaded_extensions () )) {
			return 1;
		} else
			return 0;
	}
	
	function displayMessage($message, $type) {
		Mage::getSingleton ( 'core/session' )->getMessages ( true );
		if (strcasecmp ( $type, "SUCCESS" ) == 0)
			Mage::getSingleton ( 'core/session' )->addSuccess ( $message );
		else if (strcasecmp ( $type, "ERROR" ) == 0)
			Mage::getSingleton ( 'core/session' )->addError ( $message );
		else if (strcasecmp ( $type, "NOTICE" ) == 0)
			Mage::getSingleton ( 'core/session' )->addNotice ( $message );
		else
			Mage::getSingleton ( 'core/session' )->addWarning ( $message );
	}
}  