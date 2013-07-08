<?php

App::uses('Model', 'Model');
App::uses('ArrayUtil', 'Base.Lib');
App::uses('ArrayUtil', 'Base.Lib');
App::uses('AtomicOperation', 'Operations.Lib');
App::uses('ModelOperations', 'Operations.Lib');
App::uses('OperationSet', 'Operations.Lib');
App::uses('TransactionModel', 'Operations.Model');

abstract class CustomDataModel extends TransactionModel {

    /**
     * !empty($initializedTables[$table]) indicates
     * that $table was initialized.
     * @var array
     */
    private static $initializedModels = array();

    /**
     *
     * @var array
     */
    private static $initializingModels = array();
    public $alwaysInitialize = false;

    public function assertInitializedData() {
        if (!$this->_isInitialized()) {
            $this->_setInitializing();
            $this->_initData();
            $this->_setInitialized();
        }
    }

    public function clearCache() {
        $this->getDataSource()->truncate($this->table);
        self::_setUnitialized($this);
    }

    private function _setInitializing() {
        if (array_search($this->name, self::$initializingModels)) {
            throw new Exception("Initializing loop: " . $this->name . "\n" . print_r(self::$initializingModels, true));
        } else {
            array_push(self::$initializingModels, $this->name);
        }
    }

    private function _setInitialized() {
        if (!file_exists($this->_initializedDirectory())) {
            mkdir($this->_initializedDirectory());
        }
        touch($this->_initializedFile());
        self::$initializedModels[$this->name] = true;

        $poped = array_pop(self::$initializingModels);
        if ($poped != $this->name) {
            throw new Exception("Poped: $poped / Model name: {$this->name}");
        }
    }

    private function _setUnitialized() {
        if (file_exists($this->_initializedFile())) {
            unlink($this->_initializedFile());
        }
        unset(self::$initializedModels[$this->name]);
    }

    private function _initializedDirectory() {
        return TMP . DS . 'custom_data_models';
    }

    private function _initializedFile() {
        return $this->_initializedDirectory() . DS . $this->name;
    }

    private function _isInitialized() {
        if ($this->alwaysInitialize) {
            return !empty(self::$initializedModels[$this->name]);
        } else {
            return file_exists($this->_initializedFile());
        }
    }

    private function _initData() {
        $this->getDataSource()->truncate($this->table);
        $this->beforeInitData();
        $internalModel = new Model(false, $this->table, $this->useDbConfig);
        $internalModel->begin();
        foreach ($this->customData() as $row) {
            $internalModel->create();
            $aliasedRow = array(
                $internalModel->alias => $row
            );

            if (!$internalModel->save($aliasedRow)) {
                $validationErrors = $internalModel->validationErrors;
                throw new Exception("Fail to save on data initializing: " . print_r(compact('aliasedRow', 'validationErrors'), true));
            }
        }
        $internalModel->commit();
        $this->afterInitData();
    }

    public function beforeInitData() {
        //To override
    }

    public function afterInitData() {
        //To override
    }

    /**
     * @return array 
     */
    protected abstract function customData();

    /**
     * 
     * @param array $oldData
     * @param array $newData
     * @return void
     */
    protected function customSave($oldData, $newData) {
        throw new Exception("Must be overrided");
    }

    /**
     * @param $row array
     * @return void
     */
    protected function customDelete($row) {
        throw new Exception("Must be overrided");
    }
    
    public function find($type = 'first', $query = array()) {
        $this->assertInitializedData();
        return parent::find($type, $query);
    }

    public function save($data = null, $validate = true, $fieldList = array()) {
        $this->assertInitializedData();
        if ($data) {
            $this->set($data);
        }

        if (!$this->validates()) {
            return false;
        }

        if (empty($data[$this->alias][$this->primaryKey])) {
            $oldData = false;
        } else {
            $oldData = $this->find(
                    'first', array(
                'conditions' => array(
                    $this->escapeField($this->primaryKey) => ($data[$this->alias][$this->primaryKey]
                    )
                )
                    )
            );
            $oldData = empty($oldData[$this->alias]) ? false : $oldData[$this->alias];
        }

        $newData = $this->data[$this->alias];
        $this->customSave($oldData, $newData);

        return parent::save($this->data, $validate, $fieldList);
    }

    public function cacheSave($data = null, $validate = true, $fieldList = array()) {
        return parent::save($data, $validate, $fieldList);
    }

    public function delete($id = null, $cascade = true) {
        $this->assertInitializedData();
        if ($id) {
            $this->id = $id;
        }
        $row = $this->find('first', array(
            'conditions' => array(
                "{$this->alias}.{$this->primaryKey}" => $this->id
            )
        ));

        if (empty($row)) {
            throw new Exception("Row not found with id \"{$this->id}\"");
        }
        
        $this->customDelete($row[$this->alias]);

        return parent::delete($id, $cascade);
    }

    public function cacheDelete($id = null, $cascade = true) {
        return parent::delete($id, $cascade);
    }

    /**
     * 
     * @return BaseModel
     */
    private function _createInternalModel() {
        $internalModel = new BaseModel(false, $this->table, $this->useDbConfig);
        $internalModel->name = $this->name;
        $internalModel->alias = $this->alias;
        return $internalModel;
    }

    public static function failOperation() {
        return new AnonymousFunctionOperation(function() {
                    return false;
                });
    }

}

?>
