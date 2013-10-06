<?php

App::import('Model', 'LdapUser');

class UserResetPassword extends AppModel {

    const MIN_PASSWORD_LENGTH = 6;
    const MAX_PASSWORD_LENGTH = 30;

    var $useTable = false;
    var $validate = array(
        'user_reset_password_request_id' => array(
            'rule' => array('requestIdValidacao'),
            'message' => 'Usuário e/ou requisição não existente'
        ),
        'nova_senha' => array(
            'rule' => array('novaSenhaValidacao'),
            'message' => 'Nova senha inválida'
        ),
        'confirmacao_senha' => array(
            'rule' => array('confirmacaoSenhaValidacao'),
            'message' => 'Confirmação não confere com nova senha'
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
            $request = $this->UserResetPasswordRequest->findByid(
                    $this->data[$this->alias]['user_reset_password_request_id']
            );

            $this->AuthenticationUser->changePassword(
                    $request['UserResetPasswordRequest']['user_id']
                    , $this->data[$this->alias]['nova_senha']
            );
            $request['UserResetPasswordRequest']['usado'] = date('Y-m-d H:i:s');
            $this->UserResetPasswordRequest->save($request);
            return true;
        }

        return false;
    }

    function requestIdValidacao($check) {
        return ClassRegistry::init('UserResetPasswordRequest')->findByid(
                        $check['user_reset_password_request_id']) ? true : false;
    }

    function novaSenhaValidacao($check) {
        foreach ($check as $password) {
            $password = trim($password);
            if (strlen($password) < self::MIN_PASSWORD_LENGTH || strlen($password) > self::MAX_PASSWORD_LENGTH) {
                return false;
            }
        }

        return true;
    }

    function confirmacaoSenhaValidacao($check) {
        foreach ($check as $value) {
            if ($value != $this->data[$this->alias]['nova_senha']) {
                return false;
            }
        }
        return true;
    }

    //
}

?>
