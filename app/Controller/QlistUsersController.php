<?php
/*******************************************
 * Controller Name  : QlistUsersController *
 *******************************************/

App::import('Vendor', 'braintree/lib/Braintree.php');
class QlistUsersController extends AppController {

    var $uses = array('Guest','Restaurant');
    var $name = 'QlistUser';
    var $components = array('Email','RequestHandler','Paginator','BraintreePayment');
   
    
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
        $customer['first_name'] = "Dinesh";
        $customer['last_name'] = 'D';
        $customer['company'] = '';
        $customer['user_email'] = "dinesh@bttest.com";
        $customer['user_phone'] = "9597972727";
        $creditCardDetails = array('cardholderName' => 'Dinesh',
                                   'number' => '4111111111111111',
                                   'expirationDate' => "05/2013",
                                   'cvv' => "123",
                                   'options' => array('verifyCard'=>true));
        $billingAddress = array('firstName' => "Dinesh",
                                'lastName' => "D"
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
            $result['success'] = 1;
            $result['message'] = "Thanks..! Payment transaction completed successfully.";        
        }else{
            $result['braintree_response'] = $responseFromBrainTree;
        }
        $this->set(compact('result'));
        $this->render('default');
    }    
    /********************************************************
     * Action Name : braintreepayment                       *
     * Purpose     : Used for credit card using brain tree. *
     * Created By  : Sivaraj S                              *
     * Modified By :                                        *
     ********************************************************/
    public function guest_register(){
        //$this->autoRender=false;
        $result['success'] = 0;
        $result['message'] = "Not found";
        if(isset($this->params['data']['email']) && !empty($this->params['data']['email'])){
        $data['Guest']['first_name']=isset($this->params['data']['first_name']) ? $this->params['data']['first_name'] : 'dinesh';
        $data['Guest']['last_name']=isset($this->params['data']['last_name']) ? $this->params['data']['last_name'] : 'kumar';
        $data['Guest']['email']=isset($this->params['data']['email']) ? $this->params['data']['email'] : 'dina@mail.com';
        $data['Guest']['password']=isset($this->params['data']['password']) ? $this->params['data']['password'] : 'go';
        $data['Guest']['phone']=isset($this->params['data']['phone']) ? $this->params['data']['phone'] : '12121212';
        
         $data['Guest']['password']=$this->hash_password($data['Guest']['password']);
            $result['success'] = 0;
            $result['message'] = "Registration failed";
        
        
        if ($this->Guest->save($data)) {
            $result['success'] = 1;
            $result['message'] = "Registration successfull";
        }
        }
        $this->set(compact('result'));
        $this->render('default');
    }
    public function hash_password($pass) {
        
        $hash = 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9miY';
        $pass = sha1($hash . $pass);
        return $pass;
    }
    
     public function map_screen() {
        $result['success'] = 0;
        $result['message'] = "Not found";

        if (isset($this->params['data']['phone']) && !empty($this->params['data']['phone'])) {
            $checkPhoneExist = $this->Guest->find('list', array('conditions' => array('phone' => $this->params['data']['phone'])));
            $result['message'] = "User not found";
            if (!empty($checkPhoneExist)) {
                $lat = isset($this->params['data']['latitude']) ? $this->params['data']['latitude'] : '9.9642971';
                $lng = isset($this->params['data']['longitude']) ? $this->params['data']['longitude'] : '78.1735438';
                $partySize = isset($this->params['data']['party_size']) ? $this->params['data']['party_size'] : '2';
                $this->Restaurant->virtualFields = array('distance' => "( 3959 * acos( cos( radians($lat) ) * cos( radians( Restaurant.latitude ) ) * cos( radians( Restaurant.longitude ) - radians($lng) ) + sin( radians($lat) ) * sin( radians( Restaurant.latitude ) ) ) )", 'new_restuarant' => "DATEDIFF(NOW(),Restaurant.created) <= 30", 'Qtime' => '1.50');
                $restaurantList = $this->Restaurant->find('all', array('conditions' => array('Restaurant.distance <' => 5), 'order' => array('Restaurant.distance ASC')));
                $result['success'] = 1;
                $result['message'] = "Restaurant list based on distance.";
                $restaurantList = Set::classicExtract($restaurantList, '{n}.Restaurant');
                $result['response']['Restaurants'] = $restaurantList;
            }
        }
        $this->set(compact('result'));
        $this->render('default');
    }
    
    public function citiesByNearestLocation(){
        $result['success'] = 0;
        $result['message'] = "Not found";
        $lat = isset($this->params['data']['latitude']) ? $this->params['data']['latitude'] : '32.239551';
        $lng = isset($this->params['data']['longitude']) ? $this->params['data']['longitude'] : '-110.96496';
        if (isset($this->params['data']['latitude']) && !empty($this->params['data']['latitude'])) {
            $this->Restaurant->virtualFields = array('distance' => "( 3959 * acos( cos( radians($lat) ) * cos( radians( Restaurant.latitude ) ) * cos( radians( Restaurant.longitude ) - radians($lng) ) + sin( radians($lat) ) * sin( radians( Restaurant.latitude ) ) ) )");
            $restaurantList = $this->Restaurant->find('list', array('fields' => array('Restaurant.address'), 'conditions' => array('Restaurant.distance <' => 1000), 'order' => array('Restaurant.distance ASC')));
            $result['success'] = 1;
            $result['message'] = "Cities list based on distance.";
            //$restaurantList = Set::classicExtract($restaurantList, '{n}.Restaurant');
            foreach($restaurantList as $val){
                $strArr=  explode(" ", $val);
                $getPos=count($strArr)-4;
                $key=$strArr[$getPos];
                if(in_array($key,$strArr))
                    $result['response']['Cities'][]=$strArr[$getPos];
            }
        }
        $this->set(compact('result'));
        $this->render('default');
    }
}
?>

