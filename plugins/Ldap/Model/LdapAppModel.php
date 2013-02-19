<?php

abstract class LdapAppModel extends AppModel {

    var $useDbConfig = 'ldap';
    
    /**
     *
     * @var array
     */
    protected $ldapFields;
    
    /**
     * @var string
     */
    private $dnAttributeKey;

    /**
     * @var string 
     */
    private $dnAttributeValue;

    public function __construct($id = false, $table = null, $ds = null) {
        parent::__construct($id, $table, $ds);
        $this->loadDatabaseConfig();
    }
    
    private function loadDatabaseConfig() {       
        // [$parameterToModel] => [$parameterFromDatabase]
        $parameters = array(
            'baseDn' => 'relativeBaseDn',
            'useTable' => 'relativeBaseDn',
            'primaryKey' => 'primaryKey',
            'defaultObjectClass' => 'defaultObjectClass',
            'dnAttributeKey' => 'dnAttributeKey',
            'dnAttributeValue' => 'dnAttributeValue',
            'ldapFields' => 'fields',
        );

        $databaseConfig = new DATABASE_CONFIG();

        foreach ($parameters as $parameterToModel => $parameterFromDatabase) {
            if (empty($databaseConfig->{$this->useDbConfig}['models'][get_class($this)][$parameterFromDatabase])) {
                throw new Exception('Valor não informado para DATABASE_CONFIG::'
                        . $this->useDbConfig
                        . '[models]['
                        . get_class($this)
                        . ']['
                        . $parameterFromDatabase
                        . ']');
            }                        
            
            $this->{$parameterToModel} = $databaseConfig->{$this->useDbConfig}['models'][get_class($this)][$parameterFromDatabase];
        }
        
        $this->schema(true);

        $this->_schema[$this->primaryKey] = array('type' => 'text');
        $this->_schema['objectClass'] = array('type' => 'text');

        foreach ($this->ldapFields as $field => $fieldOptions) {            
            $this->_schema[$fieldOptions['ldapAttribute']] = array('type' => 'text', 'length' => null);
            if (isset($this->_schema[$field]) && !in_array($field, $this->ldapFields)) {
                unset($this->_schema[$field]);
            }
        }
    }

    protected function toLdapField($fieldName, $value) {        
        if (empty($this->ldapFields[$fieldName]['toLdap'])) {
            return $value;
        } else {
            return call_user_func(
                            $this->ldapFields[$fieldName]['toLdap']
                            , $value
            );
        }
    }

    public function getLdapField($field) {
        if (empty($this->ldapFields[$field])) {
            throw new Exception("LdapField \"$field\" not found.");
        }

        return $this->ldapFields[$field]['ldapAttribute'];
    }

    public function getBaseDn() {
        $db =& ConnectionManager::getDataSource($this->useDbConfig);
	return (isset($this->baseDn) ? $this->baseDn.',' : null).$db->config['basedn'];
    }

    public function getParentDn($dn) {
        $parts = ldap_explode_dn($dn, false);
        if ($parts['count'] > 2) {
            return implode(',', array_slice($parts, 2));
        }
        else {
            return false;
        }
    }

    public function getDn($id = null) {

	if (isset($this->primaryKey) && $this->getBaseDn() ) {
	    if ( $id == null) {
                $id = $this->id;
            }

	    return sprintf('%s=%s,%s',$this->primaryKey,$id,$this->getBaseDn());
	}
	else {
            return null;
        }
    }

    private function buildNewDn($data) {
        return sprintf(
                        '%s=%s,%s'
                        , $this->dnAttributeKey
                        , $this->getNewRowDnAttributeValue($data)
                        , $this->getBaseDn()
        );
    }

    private function getNewRowDnAttributeValue($data) {
        foreach ($data[$this->alias] as $field => $value) {
            ${$field} = $value;
        }        

        $expression = '$result = ' . $this->dnAttributeValue . ';';

        eval($expression);

        return $result;
    }

    public function save($data = null, $validate = true, $fieldList = array()) {
        if ($data) {
            $this->set($data);
        }

        if (!empty($this->data[$this->alias]['dn'])) {
            $previousObject = $this->findByDn($this->data[$this->alias]['dn']);
            $this->data[$this->alias][$this->primaryKey] = $previousObject[$this->alias][$this->primaryKey];
        }

        return parent::save($this->data, $validate, $fieldList);
    }

    function beforeSave() {
        parent::beforeSave();

        foreach ($this->ldapFields as $field => $fieldOptions) {
            if (empty($this->data[$this->alias][$field])) {
                unset($this->data[$this->alias][$fieldOptions['ldapAttribute']]);
            } else {
                $this->data[$this->alias][$fieldOptions['ldapAttribute']] = $this->toLdapField(
                        $field
                        , $this->data[$this->alias][$field]);
            }
        }

        if (!empty($this->data[$this->alias]['dn'])) {
            $dnKey = $this->_dnKey($this->data[$this->alias]['dn']);
            foreach(array_keys($this->data[$this->alias]) as $field) {
                if (strtolower($field) == strtolower($dnKey)) {
                    unset($this->data[$this->alias][$field]);
                }
            }
        }

        foreach (array_keys($this->data[$this->alias]) as $field) {
            if (!$this->_toSaveField($field)) {
                unset($this->data[$this->alias][$field]);
            }
        }

        if ($this->isNew($this->data, 'dn')) {
            $this->data['LdapUser']['_DN_'] = $this->buildNewDn($this->data);

            if (isset($this->defaultObjectClass)) {
                $this->data[$this->alias]['objectClass'] = $this->defaultObjectClass;
            }
            
            if (($dnAttributeValue = $this->getNewRowDnAttributeValue($this->data))) {
                if (empty($this->data[$this->alias][$this->dnAttributeKey])) {
                    $this->data[$this->alias][$this->dnAttributeKey] = $dnAttributeValue;
                }
            }            
            
        } else {
            $this->data['LdapUser']['_DN_'] = $this->data['LdapUser']['dn'];
        }                

        return true;
    }
    
    private function _toSaveField($field) {
        $field = strtolower($field);
        switch ($field) {
            case 'objectclass':
            case 'dn':
            case '_dn_':
                return true;

            default:
                foreach ($this->ldapFields as $appField => $ldapField) {
                    if (strtolower($appField) == $field || strtolower($ldapField['ldapAttribute']) == $field) {
                        return true;
                    }
                }
        }
        
        return false;
    }

    private function _ldapPrimaryKeyValue($data) {
        return $this->data['LdapUser'][$this->_getAppField($this->primaryKey)];
    }    
    
    private function _getAppField($targetLdapField) {
        foreach($this->ldapFields as $appField => $ldapField) {
            if ($targetLdapField == $ldapField) {
                return $appField;
            }
        }
        
        return null;
    }

    function findByDn($dn) {
        return $this->find('first', array('conditions' => array('dn' => $dn)));
    }

    public function afterFind($results, $primary = false) {
        $results = parent::afterFind($results, $primary);

        if (empty($results)) {
            return $results;
        }

        $newResults = array();

        foreach ($results as $row) {
            $newRow = array();
            foreach ($this->ldapFields as $field => $fieldOptions) {
                $lowerLdapField = strtolower($fieldOptions['ldapAttribute']);
                if ($primary) {
                    $fieldValue = empty($row[$this->alias][$lowerLdapField]) ? NULL : $row[$this->alias][$lowerLdapField];
                } else {
                    $fieldValue = empty($row[$lowerLdapField]) ? NULL : $row[$lowerLdapField];
                }

                if (!empty($fieldOptions['fromLdap'])) {
                    $fieldValue = call_user_func($fieldOptions['fromLdap'], $fieldValue);
                }

                if ($primary) {
                    $newRow[$this->alias][$field] = $fieldValue;
                } else {
                    $newRow[$field] = $fieldValue;
                }
            }

            if ($primary) {
                $newRow[$this->alias]['id'] = $row[$this->alias][strtolower($this->primaryKey)];
                $newRow[$this->alias][$this->primaryKey] = $row[$this->alias][strtolower($this->primaryKey)];
                $newRow[$this->alias]['dn'] = $row[$this->alias]['dn'];
                $newRow[$this->alias]['dn_key'] = $this->_dnKey($row[$this->alias]['dn']);
            } else {
                $newRow['id'] = $row[strtolower($this->primaryKey)];
                $newRow[$this->primaryKey] = $row[strtolower($this->primaryKey)];
                $newRow['dn'] = $row['dn'];
                $newRow['dn_key'] = $this->_dnKey($row['dn']);
            }

            $newResults[] = $newRow;
        }

        return $newResults;
    }

    private function _dnKey($dn) {
        if (($firstEqualsPosition = strpos($dn, '=')) === false) {
            throw new Exception("DN bad formatted: $dn");
        } else {
            return substr($dn, 0, $firstEqualsPosition);
        }
    }

    /**
     *
     * @param string $dn
     * @param string $baseDn 
     * @return boolean
     */
    public static function inBaseDn($dn, $baseDn) { 
        if (empty($dn) || empty($baseDn)) {
            return false;
        }
        $dn = self::splitDn($dn);
        $baseDn = self::splitDn($baseDn);                

        for ($i = 0; $i < count($baseDn); $i++) {
            if ($baseDn[count($baseDn) - 1 - $i]['pair'] != $dn[count($dn) - 1 - $i]['pair']) {
                return false;
            }
        }
        
        return true;
    }

    public static function splitDn($dn) {        
        $splitted = array();
        $parts = explode(',', $dn);
        foreach ($parts as $part) {
            list($name, $value) = explode('=', $part);
            $name = strtolower($name);
            $pair = "$name=$value";
            $splitted[] = compact('name', 'value', 'pair');
        }
        return $splitted;
    }

}

?>
