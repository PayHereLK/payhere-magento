<?php

class Payhere_Payhere_PaymentController extends Mage_Core_Controller_Front_Action {
	// The redirect action is triggered when someone places an order
	public function redirectAction() {
		$this->loadLayout();
        $block = $this->getLayout()->createBlock('Mage_Core_Block_Template','payhere',array('template' => 'payhere/redirect.phtml'));
		$this->getLayout()->getBlock('content')->append($block);
        $this->renderLayout();
	}
	
	// The response action is triggered when your gateway sends back a response after processing the customer's payment
	public function responseAction() {
		if($this->getRequest()->isPost()) {
			
			$merchant_id = $this->getRequest()->getPost('merchant_id');
			$order_id = $this->getRequest()->getPost('order_id');
			$payment_id = $this->getRequest()->getPost('payment_id');
			$payhere_amount = $this->getRequest()->getPost('payhere_amount');
			$payhere_currency = $this->getRequest()->getPost('payhere_currency');
			$status_code = $this->getRequest()->getPost('status_code');
			$md5sig = $this->getRequest()->getPost('md5sig');
			$payhere_secret = Mage::getStoreConfig('payment/payhere/secret_key');
			
			$store_currency	 = Mage::app()->getStore()->getCurrentCurrencyCode();
			
			$sales_order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
			$grand_total = number_format((float)$sales_order->getGrandTotal(), 2, '.', '');
	
			$generated_md5sig = strtoupper(md5($merchant_id . $order_id . $payhere_amount . $payhere_currency . $status_code . strtoupper(md5($payhere_secret))));
			
			if($generated_md5sig == $md5sig && $status_code=="2" && $store_currency==$payhere_currency && $grand_total==$payhere_amount){
				
				$validated = true;
				
			}else{
				$validated = false;
				}
				
			if($validated) {
				// Payment was successful, so update the order's state, send order email and move to the success page
				$order = Mage::getModel('sales/order');
				$order->loadByIncrementId($order_id);
				$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, 'Gateway has authorized the payment.');
				
				$order->sendNewOrderEmail();
				$order->setEmailSent(true);
				
				$order->save();
			
				Mage::getSingleton('checkout/session')->unsQuoteId();
				
				Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/success', array('_secure'=>true));
			}
			else {
				// There is a problem in the response we got
				$this->cancelAction();
				Mage_Core_Controller_Varien_Action::_redirect('checkout/onepage/failure', array('_secure'=>true));
			}
		}
		else
			Mage_Core_Controller_Varien_Action::_redirect('');
	}
	
	// The cancel action is triggered when an order is to be cancelled
	public function cancelAction() {
        if (Mage::getSingleton('checkout/session')->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId(Mage::getSingleton('checkout/session')->getLastRealOrderId());
            if($order->getId()) {
				// Flag the order as 'cancelled' and save it
				$order->cancel()->setState(Mage_Sales_Model_Order::STATE_CANCELED, true, 'Gateway has declined the payment.')->save();
			}
        }
	}
}