<?php

App::uses('Model', 'Model');
App::uses('ArrayUtil', 'Base.Lib');
App::uses('CakeSchema', 'Model');

abstract class AutoCreateTableModel extends Model {

    const CONFIGURE_ALWAYS_RECREATE_TABLE_PREFIX = 'alwaysRecreateTable.';

    /**
     * !empty($initializedTables[$table]) indicates
     * that $table was initialized.
     * @var array
     */
    private static $initializedTables = array();

    /**
     * !empty($initializingTable[$table]) indicates
     * that $table is initializing. It is used to prevent
     * initializing looping.
     * @var array
     */
    private static $initializingTable = array();

    private function _assertInitializedTable() {

        if (!empty(self::$initializingTable[$this->table])) {
            throw new Exception("Initializing loop: " . $this->table);
        } else {
            self::$initializingTable[$this->table] = true;
        }
        
        if (!$this->_tableInitialized()) {
            $this->_initTable();
            self::$initializedTables[$this->table] = true;
        }
        self::$initializingTable[$this->table] = false;
    }

    private function _tableInitialized() {
        return !empty(self::$initializedTables[$this->table]) && $this->_tableExists();
    }

    private function _initTable() {        
        if ($this->_tableExists()) {
            if ($this->_alwaysRecreateTable()) {
                $this->dropTable();
            } else {
                return;
            }
        }                
        $this->_createTable();       
    }

    public function find($type = 'first', $query = array()) {
        $this->_assertInitializedTable();
        return parent::find($type, $query);
    }

    public function save($data = null, $validate = true, $fieldList = array()) {
        $this->_assertInitializedTable();
        return parent::save($data, $validate, $fieldList);
    }

    public function delete($id = null, $cascade = true) {
        $this->_assertInitializedTable();
        return parent::delete($id, $cascade);
    }

    public function dropTable() {
        $ds = ConnectionManager::getDataSource($this->useDbConfig);
        if ($this->_tableExists()) {
            $ds->execute($ds->dropSchema($this->_getSchema()));
        }
        unset(self::$initializedTables[$this->table]);
    }

    private function _tableExists() {
        return array_search(
                        $this->tablePrefix . $this->table, ConnectionManager::getDataSource($this->useDbConfig)->listSources()
                ) !== false;
    }
    
    private function _getSchema() {
        $schema = new CakeSchema();
        $schema->tables = array(
            $this->table => $this->schema()
        );
        return $schema;
    }

    private function _createTable() {
        $ds = ConnectionManager::getDataSource($this->useDbConfig);        
        $ds->execute($ds->createSchema($this->_getSchema()));
    }

    private function _alwaysRecreateTable() {
        return Configure::read(self::CONFIGURE_ALWAYS_RECREATE_TABLE_PREFIX . $this->name);
    }

}
