
<?php
/********************************************
 * Controller Name  : QlistAdminsController *
 ********************************************/
class QlistAdminsController extends AppController {

    var $name = 'QlistAdmin';
    var $components = array('Email','Auth','RequestHandler','Paginator');
   
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
    
}
?>