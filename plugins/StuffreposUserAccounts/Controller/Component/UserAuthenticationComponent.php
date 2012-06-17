<?php

class UserAuthenticationComponent extends Component {

    public function initialize(Controller $controller) {
        parent::initialize($controller);
        $controller->Auth = $controller->Components->load('Auth');
        $controller->Auth->initialize($controller);
        $controller->Auth->loginAction = array(
            'plugin' => 'stuffrepos_authentication',
            'controller' => 'authentication',
            'action' => 'login',
        );
        $controller->Auth->userModel = 'User';
        //$controller->scope = array('User.enabled' => 1);
        $controller->Auth->authenticate = array(
            'Form' => array(
                'fields' => array(
                    'username' => 'email',
                    'password' => 'password',
                )
            )
        );
    }

}

?>
