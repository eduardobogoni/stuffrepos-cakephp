<?php

App::uses('AppModel', 'Model');

class AuthenticationUser extends AppModel {

    private static $settings;

    public static function configure($settings) {
        self::$settings = $settings;
    }

    public $useTable = false;

    public function findByUsername($username) {
        return $this->_findBy(self::$settings['usernameField'], $username);
    }

    public function findByEmail($email) {
        return $this->_findBy(self::$settings['emailField'], $email);
    }

    public function findById($id) {
        $model = ClassRegistry::init(self::$settings['userModel']);
        return $this->_findBy($model->primaryKey, $id);
    }

    public function changePassword($id, $password) {
        $model = ClassRegistry::init(self::$settings['userModel']);
        $user = $model->find(
                'first', array(
            'conditions' => array(
                "{$model->alias}.{$model->primaryKey}" => $id
            )
        ));
        $user[$model->alias][self::$settings['passwordField']] = $password;
        return $model->save($user);
    }

    private function _findBy($field, $value) {
        $model = ClassRegistry::init(self::$settings['userModel']);
        $result = $model->find(
                'first', array(
            'conditions' => array(
                "{$model->alias}.$field" => $value
            )
        ));
        return $this->_toAuthenticationUser($result);
    }

    private function _toAuthenticationUser($user) {
        if (empty($user)) {
            return false;
        } else {
            $model = ClassRegistry::init(self::$settings['userModel']);
            return array(
                $this->alias => array(
                    'id' => $user[$model->alias][$model->primaryKey],
                    'username' => $user[$model->alias][self::$settings['usernameField']],
                    'email' => $user[$model->alias][self::$settings['emailField']],
                    'active' => $user[$model->alias][self::$settings['activeField']],
                )
            );
        }
    }

}