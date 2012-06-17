<?php

class UsersController extends AppController {

    public $scaffold;
    
    public $components = array(
        'StuffreposBase.ScaffoldUtil' => array(
            'indexSetFields' => array(
                'name',
                'email',
                'active',
                'created',
            ),
            'viewUnsetFields' => array(
                'id',
                'password',
            ),
            'editSetFields,addSetFields' => array(
                'id',
                'name',
                'email',
                'active',
            )
        ),
        'StuffreposAuthentication.Authentication',
        'Auth',
    );

    
    public function beforeFilter() {        
        parent::beforeFilter();
        
        if ($this->request->action == 'add' && $this->request->isPost()) {
            $this->currentPassword = $this->Authentication->generateRandomPassword();
            $this->request->data['User']['password'] = $this->currentPassword;
        }        
    }

    public function beforeRender() {
        parent::beforeRender();

        if (!isset($this->request->data['User']['active'])) {
            $this->request->data['User']['active'] = true;
        }
    }
    
    public function afterScaffoldSave($method) {
        
        parent::afterScaffoldSave($method);

        
        if ($method == 'add') {
            $this->Authentication->sendSelfUserCreationNotification(
                    $this->User->id, $this->currentPassword
            );
        }
    }

}

?>
