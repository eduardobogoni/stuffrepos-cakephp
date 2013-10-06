<?php

class UserResetPasswordRequest extends AppModel {

    // 48 horas = 48*60*60

    const LIFE_TIME_LIMIT = 172800;

    public $validate = array(
        'chave' => array(
            'rule' => array('isUnique'),
        ),
    );

    public function expired($instance) {
        return ((mktime() - strtotime($instance[$this->alias]['created'])) > self::LIFE_TIME_LIMIT);
    }

    public function createNewRequest($userId) {
        return $this->save(array(
                    $this->alias => array(
                        'user_id' => $userId,
                        'chave' => $this->_generateNewChave(),
                        'usado' => null,
                    )
        ));
    }

    private function _generateNewChave() {
        do {
            $chave = $this->_generateChave();
        } while ($this->findByChave($chave));

        return $chave;
    }

    private function _generateChave() {
        return substr(
                str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ")
                , 0
                , 32
        );
    }

}

?>
