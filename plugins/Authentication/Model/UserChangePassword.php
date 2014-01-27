<?php

App::import('Model', 'LdapUser');

class UserChangePassword extends AppModel {

    const MIN_PASSWORD_LENGTH = 6;
    const MAX_PASSWORD_LENGTH = 30;

    var $useTable = false;
    var $validate = array(
        'user_id' => array(
            'rule' => array('userIdValidacao'),
            'message' => 'Usuário e/ou requisição não existente',
            'required' => true,
        ),
        'senha_atual' => array(
            'rule' => array('senhaAtualValidacao'),
            'message' => 'Senha informada não corresponde à atual',
            'required' => true,
        ),
        'nova_senha' => array(
            'rule' => array('novaSenhaValidacao'),
            'message' => 'Nova senha inválida',
            'required' => true,
        ),
        'confirmacao_senha' => array(
            'rule' => array('confirmacaoSenhaValidacao'),
            'message' => 'Confirmação não confere com nova senha',
            'required' => true,
        )
    );

    public function __construct($id = false, $table = null, $ds = null) {
        parent::__construct($id, $table, $ds);
        $this->UserResetPasswordRequest = ClassRegistry::init('Authentication.UserResetPasswordRequest');
        $this->AuthenticationUser = ClassRegistry::init('Authentication.AuthenticationUser');
    }

    public function save($data = null, $validate = true, $fieldList = array()) {
        $this->set($data);
        if ($this->validates()) {
            $this->AuthenticationUser->changePassword(
                    $this->data[$this->alias]['user_id']
                    , $this->data[$this->alias]['nova_senha']
            );
            return true;
        }
        return false;
    }

    public function userIdValidacao($check) {
        return $this->_findUser();
    }

    public function senhaAtualValidacao($check) {
        $user = $this->_findUser();
        if (empty($user)) {
            return false;
        } else {
            $senhaAtualHash = Security::hash($check['senha_atual'], null, true);
            return $senhaAtualHash == $user['AuthenticationUser']['password'];
        }
    }

    public function novaSenhaValidacao($check) {
        foreach ($check as $password) {
            $password = trim($password);
            if (strlen($password) < self::MIN_PASSWORD_LENGTH || strlen($password) > self::MAX_PASSWORD_LENGTH) {
                return false;
            }
        }

        return true;
    }

    public function confirmacaoSenhaValidacao($check) {
        foreach ($check as $value) {
            if ($value != $this->data[$this->alias]['nova_senha']) {
                return false;
            }
        }
        return true;
    }

    private function _findUser() {
        if (empty($this->data[$this->alias]['user_id'])) {
            throw new Exception("\$this->data[{$this->alias}]['user_id'] is empty");
        }
        $user = $this->AuthenticationUser->findById($this->data[$this->alias]['user_id']);
        if (empty($user)) {
            throw new Exception("User not found");
        }
        return $user;
    }

}
