<?php

App::uses('AuthComponent', 'Controller/Component');

class User extends AppModel{        

    public $validate = array(
        'name' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
            ),
        ),
        'email' => array(
            'notEmpty' => array(
                'rule' => 'notEmpty',
                'required' => true,
                'last' => true,
            ),
            'email' => array(
                'rule' => 'email',
                'required' => true,
            ),
            'isUnique' => array(
                'rule' => 'isUnique',
                'required' => true,
            ),
        ),
    );

    public function beforeSave($options = array()) {
        if (!empty($this->data[$this->alias]['password'])) {
            $this->data[$this->alias]['password'] = AuthComponent::password($this->data[$this->alias]['password']);
        }
        
        return true;
    }

}

?>
