<?php

App::uses('Model', 'Model');
App::uses('ArrayUtil', 'Base.Lib');
App::uses('AutoCreateTableModel', 'Base.Model');
App::uses('CakeSchema', 'Model');

abstract class CustomDataModel extends AutoCreateTableModel {

    public function afterCreateTable() {
        $internalModel = new Model(false, $this->table, $this->useDbConfig);
        $internalModel->begin();
        foreach ($this->customData() as $row) {
            $internalModel->create();
            $aliasedRow = array(
                $internalModel->alias => $row
            );

            if (!$internalModel->save($aliasedRow)) {
                $validationErrors = $internalModel->validationErrors;
                throw new Exception("Error on save " . print_r(compact('aliasedRow', 'validationErrors'), true));
            }
        }
        $internalModel->commit();
    }

    /**
     * @return array 
     */
    protected abstract function customData();

    /**
     * @param $isNew bool
     * @return bool
     */
    protected function customSave($oldData, $newData) {
        return false;
    }

    /**
     * @param $row array
     * @return bool 
     */
    protected function customDelete($row) {
        return false;
    }

    public function save($data = null, $validate = true, $fieldList = array()) {
        $this->assertInitializedTable();
        if ($data) {
            $this->set($data);
        }

        if (!$this->beforeSave()) {
            return false;
        }

        if (!$this->validates()) {
            return false;
        }

        if (empty($this->data[$this->alias][$this->primaryKey])) {
            $oldData = false;
        } else {
            $oldData = $this->find(
                    'first', array(
                'conditions' => array(
                    "{$this->alias}.{$this->primaryKey}" => ($this->data[$this->alias][$this->primaryKey]
                    )
                )
                    )
            );
            $oldData = empty($oldData[$this->alias]) ? false : $oldData[$this->alias];
        }

        if ($this->customSave($oldData, $this->data[$this->alias])) {
            return parent::save();
        } else {
            return false;
        }
    }

    public function delete($id = null, $cascade = true) {
        $this->assertInitializedTable();
        if ($id) {
            $this->id = $id;
        }
        $row = $this->find('first', array(
            'conditions' => array(
                "{$this->alias}.{$this->primaryKey}" => $this->id
            )
                ));

        if (!empty($row) && $this->customDelete($row[$this->alias])) {
            return parent::delete();
        } else {
            return false;
        }
    }

}

?>
