<?php

class CreateTableUserResetPasswordRequests extends CakeMigration {

    /**
     * Migration description
     *
     * @var string
     * @access public
     */
    public $description = '';

    /**
     * Actions to be performed
     *
     * @var array $migration
     * @access public
     */
    public $migration = array(
        'up' => array(
            'create_table' => array(
                'user_reset_password_requests' => array(
                    'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'key' => 'primary'),
                    'user_id' => array('type' => 'integer', 'null' => false, 'default' => NULL),
                    'chave' => array('type' => 'string', 'null' => false, 'default' => NULL, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
                    'usado' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
                    'created' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
                    'modified' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
                    'indexes' => array(
                        'PRIMARY' => array('column' => 'id', 'unique' => 1),
                        'user_id' => array('column' => 'user_id', 'unique' => 0),
                        'chave' => array('column' => 'chave', 'unique' => 0),
                    ),
                    'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'InnoDB'),
                ),
            )
        ),
        'down' => array(
            'drop_table' => array(
                'user_reset_password_requests'
            )
        ),
    );

    /**
     * Before migration callback
     *
     * @param string $direction, up or down direction of migration process
     * @return boolean Should process continue
     * @access public
     */
    public function before($direction) {
        return true;
    }

    /**
     * After migration callback
     *
     * @param string $direction, up or down direction of migration process
     * @return boolean Should process continue
     * @access public
     */
    public function after($direction) {
        return true;
    }

}
