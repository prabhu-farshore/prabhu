
<?php
/*******************************************
 * Controller Name  : QlistUsersController *
 *******************************************/

App::import('Vendor', 'braintree/lib/Braintree.php');
class QlistUsersController extends AppController {

    var $name = 'QlistUser';
    var $components = array('Email','Auth','RequestHandler','Paginator','BraintreePayment');
   
    public function beforeFilter() {
        $this->Auth->allow();
    }

    /***********************************************
     * Action Name : signup                        *
     * Purpose     : Used for user registration.   *
     * Created By  : Sivaraj S                     *
     * Modified By :                               *
     ***********************************************/
    public function signup(){
        $result['success'] = 1;
        $result['message'] = "Successfully registered";
        $this->set(compact('result'));
        $this->render('default');
    }
    
    /********************************************************
     * Action Name : braintreepayment                       *
     * Purpose     : Used for credit card using brain tree. *
     * Created By  : Sivaraj S                              *
     * Modified By :                                        *
     ********************************************************/
    public function braintreepayment(){
        /**** Direct payment for existing user ****/
        $creditCardDetails['cardNumber'] = '5105105105105100';
        $creditCardDetails['cardExpireDate'] = '05/15';
        $responseFromBrainTree = $this->BraintreePayment->doCharge('50419131',$creditCardDetails,5);
        
        /**** Create vault for customer in braintree ****/
        $customer['first_name'] = "Sivaraj";
        $customer['last_name'] = 'E';
        $customer['company'] = '';
        $customer['user_email'] = "Sivaraj@bttest.com";
        $customer['user_phone'] = "9597972727";
        $creditCardDetails = array('cardholderName' => 'Sivaraj',
                                   'number' => '5105105105105100',
                                   'expirationMonth' => "05",
                                   'expirationYear' => "2015",
                                   'cvv' => "123");
        $billingAddress = array('firstName' => "Sivaraj",
                                'lastName' => "E"
                                /*Optional Information you can supply
                                 'company' => mysql_real_escape_string($customer['company']),
                                 'streetAddress' => mysql_real_escape_string($customer['user_address']),
                                 'locality' => mysql_real_escape_string($customer['user_city']),
                                 'region' => mysql_real_escape_string($customer['user_state']), 
                                 'postalCode' => mysql_real_escape_string($customer['zip_code']),
                                 'countryCodeAlpha2' => mysql_real_escape_string($customer['user_country'])*/
                               );
        $responseFromBrainTree = $this->BraintreePayment->createCustomer($creditCardDetails, $billingAddress, $customer);
        
        $result['success'] = 0;
        $result['message'] = "Please check your card details.";
        if($responseFromBrainTree['success'] == 1){  
            $result['message'] = "Thanks..! Payment transaction completed successfully.";        
        }
        $this->set(compact('result'));
        $this->render('default');
    }    
}
?>