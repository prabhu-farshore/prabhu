
<?php
/********************************************
 * Controller Name  : QlistAdminsController *
 ********************************************/
class QlistAdminsController extends AppController {

    var $name = 'QlistAdmin';
    var $components = array('Email','RequestHandler','Paginator');
    
    public $uses = array('Restaurant','Holiday','Workinghours');
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
    
    public function checkCoordinates(){
        echo $this->getLatitudeLongtitude('611 cloneway street, Glandle, US, 81245');
        exit;
    }
    
    /**************************************************
     * Action Name : signup                           *
     * Purpose     : Used for restaurant registration.*
     * Created By  : Sivaraj S                        *
     **************************************************/
    public function signup(){   
        $result['success'] = 0;
        $result['message'] = "No data found";
        
        $data['Restaurant']['device_id'] = !empty($this->params['data']['device_id']) ? $this->params['data']['device_id'] : "test_device_id";
        $data['Restaurant']['email'] = !empty($this->params['data']['email']) ? $this->params['data']['email'] : "FSP@qtest.com";
        $data['Restaurant']['password'] = !empty($this->params['data']['password']) ? $this->params['data']['password'] : "password";
        $data['Restaurant']['restaurant_name'] = !empty($this->params['data']['restaurant_name']) ? $this->params['data']['restaurant_name'] : "FSPs";
        $data['Restaurant']['contact_person'] = !empty($this->params['data']['contact_person']) ? $this->params['data']['contact_person'] : "FSP admin";
        $data['Restaurant']['phone'] = !empty($this->params['data']['phone']) ? $this->params['data']['phone'] : "1456939872";
        $data['Restaurant']['address'] = !empty($this->params['data']['address']) ? $this->params['data']['address'] : "Madurai, Tamilnadu";

        $data['Holiday'] = !empty($this->params['data']['holidays']) ? $this->params['data']['holidays'] : "";
        $data['Workinghours'] = !empty($this->params['data']['working_hours']) ? $this->params['data']['working_hours'] : "";
        
        $data['Restaurant'] = array_filter($data['Restaurant']);
        $data['Holiday'] = array_filter($data['Holiday']);
        $data['Workinghours'] = array_filter($data['Workinghours']);
        
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
                        if(!empty($data['Holiday']))
                            $this->setHolidayInformation($data['Holiday']);
                        if(!empty($data['Workinghours']))
                            $this->setWorkingHours($data['Workinghours']);
                        $restaurantDetails = $this->Restaurant->find('first',array('conditions'=>array('Restaurant.id'=>$lastRestaurantId)));
                        
                        $result['success'] = 1;
                        $result['message'] = "Thanks for signingup with Q application.";
                        $result['response'] = $restaurantDetails;
                        unset($result['response']['Restaurant']['password']);
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
        $data['Restaurant']['address'] = !empty($this->params['data']['address']) ? $this->params['data']['address'] : "0";
        if(!empty($data['Restaurant']['phone'])){
            $hashedPassword = sha1($data['Restaurant']['password']);
            $restaurantDetails = $this->Restaurant->find('first',array('conditions'=>array('email'=>$data['Restaurant']['email'],
                                                                                           'phone'=>$data['Restaurant']['phone'],
                                                                                           'password'=>$hashedPassword)));
            if(!empty($restaurantDetails)){
                if($restaurantDetails['Restaurant']['device_id'] != $data['Restaurant']['device_id']){
                    $this->Restaurant->id = $restaurantDetails['Restaurant']['id'];
                    $this->Restaurant->saveField('device_id',$data['Restaurant']['device_id']);
                    $restaurantDetails['Restaurant']['device_id']= $data['Restaurant']['device_id'];
                }
                if($data['Restaurant']['address'] != 0){
                    $this->Restaurant->id = $restaurantDetails['Restaurant']['id'];
                    $this->Restaurant->saveField('address',$data['Restaurant']['address']);
                    $restaurantDetails['Restaurant']['address']= $data['Restaurant']['address'];
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
            
    /*******************************************************
     * Action Name : setRestaurantOnlineStatus             *
     * Purpose     : Used to set restaurant online status. *
     * Created By  : Sivaraj S                             *
     *******************************************************/
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
                
    /******************************************************
     * Action Name : getRestaurantListByDistance          *
     * Purpose     : Used to get restaurant list based on * 
     *               current location co-ordinates.       *
     * Created By  : Sivaraj S                            *
     ******************************************************/
    public function getRestaurantListByDistance(){
        $lat = '9.9297400';
        $lng = '78.1321050';
        $this->Restaurant->virtualFields = array('distance' => "( 6371 * acos( cos( radians($lat) ) * cos( radians( Restaurant.latitude ) ) * cos( radians( Restaurant.longitude ) - radians($lng) ) + sin( radians($lat) ) * sin( radians( Restaurant.latitude ) ) ) )");
        $restaurantList = $this->Restaurant->find('all',array('order' => array('Restaurant.distance ASC')));
        $result['success'] = 1;
        $result['message'] = "Restaurant list based on distance.";
        $restaurantList = Set::classicExtract($restaurantList,'{n}.Restaurant');
        $result['response']['Restaurants'] = $restaurantList;
        $this->set(compact("result"));
        $this->render("default");
    }    
        
    /*****************************************************
     * Action Name : updateRestaurantDetails             *
     * Purpose     : Used for update restaurant details. *
     * Created By  : Sivaraj S                           *
     *****************************************************/
    public function updateRestaurantDetails(){   
        $result['success'] = 0;
        $result['message'] = "No data found";
        
        $data['Restaurant']['id'] = !empty($this->params['data']['restaurant_id']) ? $this->params['data']['restaurant_id'] : "1";
        $data['Restaurant']['email'] = !empty($this->params['data']['email']) ? $this->params['data']['email'] : "";
        $data['Restaurant']['restaurant_name'] = !empty($this->params['data']['restaurant_name']) ? $this->params['data']['restaurant_name'] : "";
        $data['Restaurant']['contact_person'] = !empty($this->params['data']['contact_person']) ? $this->params['data']['contact_person'] : "";
        $data['Restaurant']['phone'] = !empty($this->params['data']['phone']) ? $this->params['data']['phone'] : "";
        $data['Restaurant']['address'] = !empty($this->params['data']['address']) ? $this->params['data']['address'] : "";
        $data['Restaurant']['2_top_avg_time'] = !empty($this->params['data']['2_top_avg_time']) ? $this->params['data']['2_top_avg_time'] : "";
        $data['Restaurant']['4_top_avg_time'] = !empty($this->params['data']['4_top_avg_time']) ? $this->params['data']['4_top_avg_time'] : "00:00:30";
        $data['Restaurant']['6_top_avg_time'] = !empty($this->params['data']['6_top_avg_time']) ? $this->params['data']['6_top_avg_time'] : "";
        $data['Restaurant']['allow_6_top_remote'] = !empty($this->params['data']['allow_6_top_remote']) ? $this->params['data']['allow_6_top_remote'] : "";
 
        $data['Restaurant'] = array_filter($data['Restaurant']);
        
        if(!empty($data['Restaurant']['id'])){
            $restaurantExists = $this->Restaurant->find('first',array('conditions'=>array('Restaurant.id'=>$data['Restaurant']['id'])));
            $result['response'] = $restaurantExists;
            if(!empty($restaurantExists)){
                $result = $this->updateRestaurantInfo($data,$restaurantExists);
            }else{
                $result['message'] = "Restaurant doesn't exist in Q application.";
            }
        }
        unset($result['response']['Restaurant']['password']);
        $this->set(compact("result"));
        $this->render("default");
    }
    
    /*********************************************************
     * Action Name : updateRestaurantDetails                 *
     * Purpose     : Used for update restaurant information. *
     * Created By  : Sivaraj S                               *
     *********************************************************/
    public function updateRestaurantInfo($data,$restaurantExists){
        $result['success'] = 0;
        $duplicateRestaurantExists = $this->Restaurant->find('first',array('conditions'=>array('Restaurant.id !='=>$data['Restaurant']['id'],
                                                                                               'OR'=>array('restaurant_name'=>$restaurantExists['Restaurant']['restaurant_name'],
                                                                                                           'phone'=>$restaurantExists['Restaurant']['phone']))));
        $result['message'] = "Failed to update restaurant information.";
        if(empty($duplicateRestaurantExists)){
            $isCoordinateCalulated = true;
            if(isset($data['Restaurant']['address'])){
                $locationCoordinates = $this->getLatitudeLongtitude($data['Restaurant']['address']); 
                if(!empty($locationCoordinates)){
                    $latitudeLongtitude = explode(',',$locationCoordinates);
                    $data['Restaurant']['latitude'] = $latitudeLongtitude[0];
                    $data['Restaurant']['longitude'] = $latitudeLongtitude[1];
                }else{
                    $isCoordinateCalulated = false;
                    $result['message'] = "Sorry.. we cannot get you location co-ordinates.";
                }
            }
            if($isCoordinateCalulated){
                if($this->Restaurant->save($data['Restaurant'])){
                    $restaurantDetails = $this->Restaurant->find('first',array('conditions'=>array('Restaurant.id'=>$data['Restaurant']['id'])));
                    $result['success'] = 1;
                    $result['message'] = "Restaurant information updated.";
                    $result['response'] = $restaurantDetails;
                }
            }
        }else{
           $result['message'] = "Already restaurant exists with same name or phone number."; 
        }
        return $result;
    }
    
    /******************************************************
     * Action Name : setHolidayInformation                *
     * Purpose     : Used to save restaurant information. *
     * Created By  : Sivaraj S                            *
     ******************************************************/
    public function setHolidayInformation($paramData = null){        
        $result['success'] = 0;
        $result['message'] = "No data found";
        
        if($paramData != null){
            $data = $paramData;
            foreach($data as $holiday){
                $this->Holiday->create();
                $this->Holiday->save($holiday);
            }
            $result['success'] = 1;
            $result['message'] = "Holiday added to the your calendar.";
        }else{        
            $data['id'] = !empty($this->params['data']['holiday_id']) ? $this->params['data']['holiday_id'] : "";
            $data['restaurant_id'] = !empty($this->params['data']['restaurant_id']) ? $this->params['data']['restaurant_id'] : "1";
            $data['date'] = !empty($this->params['data']['date']) ? $this->params['data']['date'] : date('Y-m-d');
            $data['holiday_name'] = !empty($this->params['data']['holiday_name']) ? $this->params['data']['holiday_name'] : "Workers day";
            $data['country'] = !empty($this->params['data']['country']) ? $this->params['data']['country'] : "US";
            if($this->Holiday->save($data)){
                $result['success'] = 1;
                if(!empty($data['id']))
                    $result['message'] = "Holiday updated to the calendar.";
                else
                    $result['message'] = "Holiday added to the your calendar.";
            }else{
                $result['message'] = "Some holiday details missing.";
            }
        }
        $this->set(compact("result"));
        $this->render("default");
    }    
    
    /********************************************************
     * Action Name : setWorkingHours                        *
     * Purpose     : Used to save restaurant working hours. *
     * Created By  : Sivaraj S                              *
     ********************************************************/
    public function setWorkingHours($paramData=null){
        $result['success'] = 0;
        $result['message'] = "No data found";
        
        if($paramData != null){
            $data = $paramData;
            foreach($data as $workinghour){
                $this->Workinghours->create();
                $this->Workinghours->save($workinghour);
            }
            $result['success'] = 1;
            $result['message'] = "Working hours added for your restaurant.";
        }else{        
            $data = !empty($this->params['data']['working_hours']) ? $this->params['data']['working_hours'] : "";
            if(!empty($data)){
                foreach($data as $workinghour){
                    if(empty($data['id']))
                        $this->Workinghours->create();
                    $this->Workinghours->save($workinghour);
                }
                $result['success'] = 1;
                $result['message'] = "Working hours set for your restaurant.";
            }else{
                $result['message'] = "Some details missing.";
            }
        }
        $this->set(compact("result"));
        $this->render("default");
    }
        
    /*****************************************************
     * Action Name : getRestaurantDetails                *
     * Purpose     : Used to get restaurant information. *
     * Created By  : Sivaraj S                           *
     *****************************************************/
    public function getRestaurantDetails(){  
        $result['success'] = 0;
        $result['message'] = "No data found";
        
        $data['Restaurant']['id'] = !empty($this->params['data']['restaurant_id']) ? $this->params['data']['restaurant_id'] : "1";
        if(!empty($data['Restaurant']['id'])){
            $restaurantExists = $this->Restaurant->find('first',array('conditions'=>array('Restaurant.id'=>$data['Restaurant']['id'])));
            if(!empty($restaurantExists)){
                $result['success'] = 1;
                $result['response'] = $restaurantExists;
                $result['message'] = "Restaurant details retrieved successfully.";
            }else{
                $result['message'] = "Restaurant doesn't exist in Q application.";
            }
        }
        $this->set(compact("result"));
        $this->render("default");
    }
            
    /*******************************************************
     * Action Name : getNationalHolidays                   *
     * Purpose     : Used to get list of national holiday. *
     * Created By  : Sivaraj S                             *
     *******************************************************/
    public function getNationalHolidays(){
        $result['success'] = 1;
        $nationalHolidays = $this->Holiday->find('all',array('conditions'=>array('restaurant_id'=>0,'state'=>'closed'),
                                                             'fields'=>array('date_format(Holiday.date,"%b %d") as holiday_date','TRIM(holiday_name) as name')));
        $result['response']['nationalHolidays'] = Set::classicExtract($nationalHolidays,'{n}.0');
        $result['message'] = "National holidays listed successfully.";
        $this->set(compact("result"));
        $this->render("default");
    }
}
?>