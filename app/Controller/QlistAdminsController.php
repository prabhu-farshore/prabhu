
<?php
/********************************************
 * Controller Name  : QlistAdminsController *
 ********************************************/
class QlistAdminsController extends AppController {

    var $name = 'QlistAdmin';
    var $components = array('Email','RequestHandler','Paginator');
    
    public $uses = array('Restaurant');
    public $result = array();
   
    public function beforeFilter() {
        /*** Do stuffs that you want to excute before actions excute ****/        
    }

    /*************************************************
     * Action Name : getLatLong                      *
     * Purpose     : To get latitude and longitude.  *
     * Created By  : SIVARAJ.S                       *
     *************************************************/
    function getLatitudeLongtitude($address){	
        $_address = urlencode($address);
        $json = file_get_contents("http://maps.google.com/maps/api/geocode/json?address=$_address&sensor=true");
        $json = json_decode($json);
        if(!empty($json->{'results'}[0])){
            $lat = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lat'};
            $long = $json->{'results'}[0]->{'geometry'}->{'location'}->{'lng'};
            return $lat.','.$long; 
        }else{
            return '';
        }
    }    
    
    /**************************************************
     * Action Name : signup                           *
     * Purpose     : Used for restaurant registration.*
     * Created By  : Sivaraj S                        *
     **************************************************/
    public function signup(){   
        $result['success'] = 0;
        $result['message'] = "No data found";
        
        $data['Restaurant']['device_id'] = !empty($this->params['data']['device_id']) ? $this->params['data']['device_id'] : "static_id";
        $data['Restaurant']['email'] = !empty($this->params['data']['email']) ? $this->params['data']['email'] : "static_email@qtest.com";
        $data['Restaurant']['password'] = !empty($this->params['data']['password']) ? $this->params['data']['password'] : "password";
        $data['Restaurant']['restaurant_name'] = !empty($this->params['data']['restaurant_name']) ? $this->params['data']['restaurant_name'] : "Static Name";
        $data['Restaurant']['contact_person'] = !empty($this->params['data']['contact_person']) ? $this->params['data']['contact_person'] : "Static Contact";
        $data['Restaurant']['phone'] = !empty($this->params['data']['phone']) ? $this->params['data']['phone'] : "1234567890";
        $data['Restaurant']['address'] = !empty($this->params['data']['address']) ? $this->params['data']['address'] : "Madurai, Tamil Nadu 625107";
        if(!empty($data['Restaurant']['device_id'])){
            $restaurantExists = $this->Restaurant->find('first',array('conditions'=>array('restaurant_name'=>$data['Restaurant']['restaurant_name'],
                                                                                          'phone'=>$data['Restaurant']['phone'])));
            if(empty($restaurantExists)){
                $result['message'] = "failed to resgister with Q application.";
                $locationCoordinates = $this->getLatitudeLongtitude($data['Restaurant']['address']); 
                if(!empty($locationCoordinates)){
                    $latitudeLongtitude = explode(',',$locationCoordinates);
                    $data['Restaurant']['latitude'] = $latitudeLongtitude[0];
                    $data['Restaurant']['longitude'] = $latitudeLongtitude[1];
                    if($this->Restaurant->save($data['Restaurant'])){
                        $lastRestaurantId = $this->Restaurant->getLastInsertId();
                        $restaurantDetails = $this->Restaurant->find('first',array('conditions'=>array('Restaurant.id'=>$lastRestaurantId)));
                        $result['success'] = 1;
                        $result['message'] = "Thanks for signingup with Q application.";
                        $result['response'] = $restaurantDetails;
                    }
                }else{
                    $result['message'] = "Sorry.. we cannot get you location co-ordinates.";
                }
            }else{
                $result['message'] = "Restaurant is already a member of Q application.";
            }
        }
        $this->set(compact("result"));
        $this->render("default");
    }     
        
    /**********************************************
     * Action Name : login                        *
     * Purpose     : Used for restaurant login.   *
     * Created By  : Sivaraj S                    *
     **********************************************/
    public function login(){   
        $result['success'] = 0;
        $result['message'] = "No data found";
        
        $data['Restaurant']['device_id'] = !empty($this->params['data']['device_id']) ? $this->params['data']['device_id'] : "test_device_id";
        $data['Restaurant']['email'] = !empty($this->params['data']['email']) ? $this->params['data']['email'] : "static_email@qtest.com";
        $data['Restaurant']['password'] = !empty($this->params['data']['password']) ? $this->params['data']['password'] : "password";
        $data['Restaurant']['phone'] = !empty($this->params['data']['phone']) ? $this->params['data']['phone'] : "1234567890";
        if(!empty($data['Restaurant']['phone'])){
            $hashedPassword = sha1($data['Restaurant']['password']);
            $restaurantDetails = $this->Restaurant->find('first',array('conditions'=>array('email'=>$data['Restaurant']['email'],
                                                                                           'phone'=>$data['Restaurant']['phone'],
                                                                                           'password'=>$hashedPassword)));
            if(!empty($restaurantDetails)){
                if($restaurantDetails['device_id']!=$data['Restaurant']['device_id']){
                    $this->Restaurant->id = $restaurantDetails['id'];
                    $this->Restaurant->saveField('device_id',$data['Restaurant']['device_id']);
                }
                $result['success'] = 1;
                $result['message'] = "Welcome back to Q application.";
                $result['response'] = $restaurantDetails;
            }else{
                $result['message'] = "Please check our login credential.";
            }
        }
        $this->set(compact("result"));
        $this->render("default");
    }     
            
    /**********************************************
     * Action Name : login                        *
     * Purpose     : Used for restaurant login.   *
     * Created By  : Sivaraj S                    *
     **********************************************/
    public function setRestaurantOnlineStatus(){   
        $result['success'] = 0;
        $result['message'] = "No data found";
        
        $id = !empty($this->params['data']['restaurant_id']) ? $this->params['data']['restaurant_id'] : "1";
        if(!empty($id)){
            $restaurantExists = $this->Restaurant->find('first',array('conditions'=>array('Restaurant.id'=>$id)));
            if(!empty($restaurantExists)){
                $this->Restaurant->id = $id;
                if($this->Restaurant->saveField('is_online','1')){
                    $result['success'] = 1;
                    $result['message'] = "Restaurant set to active status.";
                }else{
                    $result['message'] = "Sorry.. Can't update restaurant status.";
                }
            }else{
                $result['message'] = "Restaurant doesn't exist in Q application.";
            }
        }
        $this->set(compact("result"));
        $this->render("default");
    }
}
?>