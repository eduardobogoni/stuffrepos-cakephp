<?php

class AuthenticationController extends AppController {

    public $uses = array();

    public function login() {
        if ($this->request->is('post')) {
            if ($this->Auth->login()) {
                return $this->redirect($this->Auth->redirect());
            } else {
                $this->Session->setFlash(__('Username or password is incorrect'), 'default', array(), 'auth');
            }
        }
    }

    public function logout() {
        $this->redirect($this->Auth->logout());
    }

}

?>
