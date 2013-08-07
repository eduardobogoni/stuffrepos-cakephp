<?php

class UsersController extends AppController {

    public $scaffold;
    
    public $components = array(
        'ExtendedScaffold.ScaffoldUtil' => array(
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
        'Authentication.Authentication',
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
        if (!parent::afterScaffoldSave($method)) {
            return false;
        }

        if ($method == 'add' && $this->Components->enabled('Auth')) {
            $this->Authentication->sendSelfUserCreationNotification(
                    $this->User->id, $this->currentPassword
            );
        }
        
        return true;
    }

}

?>
