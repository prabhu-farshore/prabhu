
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
    
    /***********************************************
     * Action Name : signup                        *
     * Purpose     : Used for user registration.   *
     * Created By  : Sivaraj S                     *
     ***********************************************/
    public function signup(){   
        $result['success'] = 0;
        $result['message'] = "No data found";
        
        $data['Restaurant']['device_id'] = !empty($this->params['data']['device_id']) ? $this->params['data']['device_id'] : "test_device_id";
        $data['Restaurant']['email'] = !empty($this->params['data']['email']) ? $this->params['data']['email'] : "NorthGate@qtest.com";
        $data['Restaurant']['password'] = !empty($this->params['data']['password']) ? $this->params['data']['password'] : "password";
        $data['Restaurant']['confirm_password'] = !empty($this->params['data']['confirm_password']) ? $this->params['data']['confirm_password'] : "password";
        $data['Restaurant']['restaurant_name'] = !empty($this->params['data']['restaurant_name']) ? $this->params['data']['restaurant_name'] : "NorthGate";
        $data['Restaurant']['contact_person'] = !empty($this->params['data']['contact_person']) ? $this->params['data']['contact_person'] : "NorthGate admin";
        $data['Restaurant']['address'] = !empty($this->params['data']['address']) ? $this->params['data']['address'] : "Poppys Hotel, Madurai, Tamil Nadu 625107";
        if(!empty($data['Restaurant']['device_id'])){
            $restaurantExists = $this->Restaurant->find('first',array('conditions'=>array('email'=>$data['Restaurant']['email'])));
            if(empty($restaurantExists)){
                $result['message'] = "failed to resgister with Q application.";
                $locationCoordinates = $this->getLatitudeLongtitude($data['Restaurant']['address']); 
                if(!empty($locationCoordinates)){
                    $latitudeLongtitude = explode(',',$locationCoordinates);
                    $data['Restaurant']['latitude'] = $latitudeLongtitude[0];
                    $data['Restaurant']['longitude'] = $latitudeLongtitude[1];
                    if($this->Restaurant->save($data['Restaurant'])){
                        $result['success'] = 1;
                        $result['message'] = "Thanks for signingup with Q application.";
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
}
?>