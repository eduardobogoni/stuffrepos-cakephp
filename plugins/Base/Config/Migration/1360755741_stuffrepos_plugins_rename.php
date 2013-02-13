<?php

class StuffreposPluginsRename extends CakeMigration {

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
        ),
        'down' => array(
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
        if ($direction == 'up') {
            $SchemaMigration = $this->generateModel('SchemaMigration');
            $SchemaMigration->updateAll(array(
                'type' => "'ConfigurationKeys'"
                ), array(
                'type' => 'StuffreposConfigurationKeys'
                )
            );
            $SchemaMigration->updateAll(array(
                'type' => "'UserAccounts'"
                ), array(
                'type' => 'StuffreposUserAccounts'
                )
            );
        }
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
