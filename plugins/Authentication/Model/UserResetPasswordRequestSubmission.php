<?php

App::import('Model', 'LdapUser');

class UserResetPasswordRequestSubmission extends AppModel {

    var $useTable = false;
    var $validate = array(
        'username_or_email' => array(
            'notempty' => array(
                'rule' => array('notempty'),
                'required' => true,
                'message' => 'Campo não pode ser vazio'
            ),
            'userFound' => array(
                'rule' => array('userFoundValidation'),
                'message' => 'Não foi encontrado um usuário com o nome de usuário ou email informado.'
            ),
            'userActive' => array(
                'rule' => array('userActiveValidation'),
                'message' => 'Usuário não está habilitado. Contate o administrador do sistema.'
            )
        ),
    );
    public $_schema = array(
        'username' => array(
            'type' => 'string',
        )
    );

    public function __construct($id = false, $table = null, $ds = null) {
        parent::__construct($id, $table, $ds);
        $this->AuthenticationUser = ClassRegistry::init('AuthenticationUser');
    }

    public function userFoundValidation($check) {
        return $this->getUser($check['username_or_email']) != null;
    }

    public function userActiveValidation($check) {
        $user = $this->getUser($check['username_or_email']);
        return $user && $user['AuthenticationUser']['active'];
    }

    public function getUser($usernameOrEmail = null) {
        if (!$usernameOrEmail) {
            $usernameOrEmail = $this->data[$this->alias]['username_or_email'];
        }

        $user = $this->AuthenticationUser->findByUsername(
                $usernameOrEmail
        );
        if (!empty($user)) {
            return $user;
        }

        $user = $this->AuthenticationUser->findByEmail(
                $usernameOrEmail
        );
        if (!empty($user)) {
            return $user;
        }

        return null;
    }

    public function getUserId() {
        if (($user = $this->getUser())) {
            return $user['User']['id'];
        } else {
            throw new Exception("Usuário não encontrado.");
        }
    }

}

?>
