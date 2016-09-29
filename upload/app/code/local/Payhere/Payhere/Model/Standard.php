<?php
class Payhere_Payhere_Model_Standard extends Mage_Payment_Model_Method_Abstract {
	protected $_code = 'payhere';
	
	protected $_isInitializeNeeded      = true;
	protected $_canUseInternal          = true;
	protected $_canUseForMultishipping  = false;
	
	public function getOrderPlaceRedirectUrl() {
		return Mage::getUrl('payhere/payment/redirect', array('_secure' => true));
	}
}

?>