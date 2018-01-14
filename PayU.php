<?php 
 
class PaymentProcessor_PayU extends PaymentProcessor_Abstract {
     
    // Url Test
    protected $_paymentSystemServer = 'https://sandbox.checkout.payulatam.com/ppp-web-gateway-payu';
    // Url Production
    //protected $_paymentSystemServer = 'https://checkout.payulatam.com/ppp-web-gateway-payu';
     
    public function makePayment() {
        $view = Zend_Registry::get('view');
         
        $cart = $this->getCart();
        $descriptions = array();
        foreach ($cart as $item) {
            $descriptions[] = $item['name'];
        }
        $description = implode(', ', $descriptions);
         
        $apiKey = $this->_getParam('payU_apiKey'); 
        $merchantId = $this->_getParam('payU_merchantId');
        $accountId = $this->_getParam('payU_accountId');

        $orderId = $this->getOrderId();
        $amount = number_format($this->getAmount(), 2);
        $currency = $this->_getCurrency();

        $user = $this->getUser();

        $view->assign('currency', $currency);
        $view->assign('buyer_fullname', $user['name']);
        $view->assign('buyer_email', $user['email']);
         
        // Control data
        $view->assign('merchant_id', $merchant_id);
        $view->assign('account_id', $account_id);
        $view->assign('reference_code', $orderId);
        $view->assign('description', $description)
        $view->assign('amount', $amount);
         
        $view->assign('confirmation_url', $this->_getConfirmUrl());
        $view->assign('response_url', $this->_getReturnUrl());
         
        $view->assign('form_url', $this->_paymentSystemServer); 
        
        $concatFields = $apiKey . "~" . $merchantId . "~" . $orderId . "~" . $amount . "~" . $currency;

        $hash = md5($concatFields);
         
        $view->assign('signature', $hash);
         
        return $this->_getRules();
    }
 
    public function confirmPayment() {
        Util::save_trace(array($_POST, $_SERVER), 'payU' . time() . '.txt');
         
        $request = $this->getRequest();
                  
        if (_checkSignature($request->getParam('signature'), $request->getParam('referenceCode'), $request->getParam('merchantId'), $request->getParam('TX_VALUE'), $request->getParam('currency'), $request->getParam('transactionState'))) {

            if($request->getParam('transactionState') != 4){
                $this->_addError('Transacción Fallida');
                $this->errorPayment();
             
                return false;    
            }else {
                parent::_confirmPayment();
                return true;
            }
            
        }else{
            $this->_addError(_T('eWrongHash'));
            $this->errorPayment();
             
            return false;
        }
        
    }
    
    protected function _checkSignature($signature, $referenceCode, $merchantId, $Tx_Value, $currency, $transactionState){
        $apiKey = $this->_getParam('payU_apiKey');
        $new_value = round($Tx_Value,1,PHP_ROUND_HALF_EVEN);
        $sign = $apiKey . "~" . $merchantId . "~" . $referenceCode . "~" . $new_value . "~" . 
                        $currency . "~" . $transactionState;
        $sign = md5($sign);
     
        return $signature == $sign;
    }
     
}