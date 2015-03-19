<?php

class Restaurant extends AppModel {

    var $name = "Restaurant";
    var $useTable = "restaurants";
    public $tablePrefix = '';

    public function beforeSave($options = array()) {
        if (isset($this->data[$this->alias]['password'])) {
            $this->data[$this->alias]['password'] = md5($this->data[$this->alias]['password']);
        }
        return true;
    }
}
?>