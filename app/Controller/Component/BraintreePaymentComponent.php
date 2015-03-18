<?php
App::uses('Component', 'Controller');

class BraintreePaymentComponent extends Component {
    
    
    public function doCharge($customerId, $creditCardDetails, $amount){
        /* Charge for sale using the Brain tree Library function */
        if($customerId==null)
            $response = Braintree_Transaction::sale(array('amount' => $amount,
                                                          'creditCard' => array('number' => $creditCardDetails['cardNumber'],
                                                                                'expirationDate' => $creditCardDetails['cardExpireDate'])));
        else
            $response = Braintree_Transaction::sale(array('customerId' => $customerId,'amount' => $amount));

        $result['success'] = 0;
        if ($response->success) {
            $result['success'] = 1;
            $result['responseData']['txnId'] = $response->transaction->id;
        } else if ($response->transaction) {
            CakeLog::error("\nError processing transaction:\n  code: " . $response->transaction->processorResponseCode."\n  text: " . $response->transaction->processorResponseText, 'braintree');
            $result['error']['processing']['errorCode'] = $response->transaction->processorResponseCode;
            $result['error']['processing']['message'] = $response->transaction->processorResponseText;
        } else {
            CakeLog::error("\nValidation errors: \n".$response->errors->deepAll(),'braintree');
            $result['error']['validation'] = $response->errors->deepAll();
        }
        return $result;
    } 
    
    public function createCustomer($creditCardDetails, $billingAddress, $customer){
        $creditCardDetails['options'] = array('verifyCard'=>true);
	/* Create a new user using the Brain tree Library function */
	$response = Braintree_Customer::create(array('firstName' => mysql_real_escape_string($customer['first_name']),
                                                     'lastName' => mysql_real_escape_string($customer['last_name']),
                                                     'company' => mysql_real_escape_string($customer['company']),
                                                     'email' => mysql_real_escape_string($customer['user_email']),
                                                     'phone' => mysql_real_escape_string($customer['user_phone']),
                                                     'creditCard' => $creditCardDetails));
        $result['success'] = 0;
        if ($response->success) {
            $result['success'] = 1;
            $result['responseData']['creditCardToken'] = $response->customer->creditCards[0]->token;
            $result['responseData']['customerID'] = $response->customer->id;
            $result['responseData']['creditCardID'] = $response->customer->creditCards[0]->token;
        }
        return $result;
    }
}
?>