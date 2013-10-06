<?php

App::uses('CakeEmail', 'Network/Email');

class AuthenticationController extends AppController {

    public $uses = array(
        'Authentication.AuthenticationUser',
        'Authentication.UserResetPassword',
        'Authentication.UserResetPasswordRequest',
        'Authentication.UserResetPasswordRequestSubmission',
    );
    public $components = array(
        'Session'
    );
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

    public function reset_password($chave) {
        $request = $this->UserResetPasswordRequest->findByChave($chave);
        $user = $this->AuthenticationUser->findById(
                $request['UserResetPasswordRequest']['user_id']
        );
        
        $errorMessage = false;
        if (empty($request)) {
            $errorMessage = 'Não foi encontrada uma requisição de reset de senha com a chave informada.';
        } else if ($request['UserResetPasswordRequest']['usado']) {
            $errorMessage = 'A requisição de reset de senha indicada pela chave já foi utilizada anteriormente.';
        } else if ($this->UserResetPasswordRequest->expired($request)) {
            $errorMessage = 'A requisição de reset de senha indicada pela chave expirou.';
        } else if (empty($user)) {
            $errorMessage = 'Usuário não encontrado.';
        } else if (!$user['AuthenticationUser']['active']) {
            $errorMessage = 'Usuário encontra-se desabilitado no momento. Contate o administrador do sistema.';
        }

        if ($errorMessage) {
            $this->flash($errorMessage, array('action' => 'login'));
        } else if ($this->request->isPost()) {
            $this->request->data['UserResetPassword']['user_reset_password_request_id'] =
                    $request['UserResetPasswordRequest']['id'];
            if ($this->UserResetPassword->save($this->request->data)) {
                $this->flash('Senha resetada', array('action' => 'login'));
            } else {
                $this->Session->setFlash('Falha ao tentar resetar a senha.');
            }
        }

        $this->request->data['UserResetPassword']['username'] = $user['AuthenticationUser']['username'];
    }

    public function reset_password_request() {
        if ($this->request->isPost()) {
            $this->UserResetPasswordRequestSubmission->set($this->request->data);
            if ($this->UserResetPasswordRequestSubmission->validates()) {
                $user = $this->UserResetPasswordRequestSubmission->getUser();
                $userId = $user['AuthenticationUser']['id'];
                if ($this->UserResetPasswordRequest->createNewRequest($userId)) {
                    $this->_sendUserResetPasswordRequestMail($this->UserResetPasswordRequest->id);
                    $this->flash("Um email foi enviado para \"{$user['AuthenticationUser']['email']}\" com instruções de como resetar sua senha.", array('action' => 'login'));
                } else {
                    throw new Exception('Falha ao tentar salvar requisição de reset de senha: ' . print_r($request, true));
                }
            }
        }
    }

    private function _sendUserResetPasswordRequestMail($userResetPasswordRequestId) {
        $userResetPasswordRequest = $this->UserResetPasswordRequest->findById($userResetPasswordRequestId);
        if (empty($userResetPasswordRequest)) {
            throw new Exception("Não foi encontrada uma requisição de reset de senha com id=$userResetPasswordRequestId");
        }

        $user = $this->AuthenticationUser->findById(
                $userResetPasswordRequest['UserResetPasswordRequest']['user_id']
        );

        $email = new CakeEmail('default');
        $email->emailFormat('html');
        $email->template('Authentication.user_reset_password_request');
        $email->to($user['AuthenticationUser']['email']);
        $email->subject(__('Reset Password Request'));
        $email->viewVars(compact('userResetPasswordRequest', 'user'));
        $email->send();
    }

}

?>
