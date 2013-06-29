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

    public function assertInitializedTable() {

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
        $this->afterCreateTable();
    }

    public function find($type = 'first', $query = array()) {
        $this->assertInitializedTable();
        return parent::find($type, $query);
    }

    public function save($data = null, $validate = true, $fieldList = array()) {
        $this->assertInitializedTable();
        return parent::save($data, $validate, $fieldList);
    }

    public function delete($id = null, $cascade = true) {
        $this->assertInitializedTable();
        return parent::delete($id, $cascade);
    }

    public function dropTable() {
        $ds = ConnectionManager::getDataSource($this->useDbConfig);
        $ds->execute($ds->dropSchema($this->_getSchema()));
        unset(self::$initializedTables[$this->table]);
    }
    
    public function afterCreateTable() {
        //To override
    }

    private function _tableExists() {
        $ds = ConnectionManager::getDataSource($this->useDbConfig);
        $ds->cacheSources = false;
        $result = array_search(
                        $this->tablePrefix . $this->table, $ds->listSources()
                ) !== false;
        $ds->cacheSources = true;
        return $result;
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