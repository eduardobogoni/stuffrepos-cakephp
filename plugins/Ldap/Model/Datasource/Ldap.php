<?php

App::uses('Basics', 'Base.Lib');
App::uses('LdapUtils', 'Ldap.Lib');

class Ldap extends DataSource {

    const LDAP_ERROR_NO_SUCH_OBJECT = 32;

    private $connection = null;

    protected $_baseConfig = array(
        'host' => 'localhost',
        'version' => 3
    );
    
    private $_modelBaseConfig = array(
        'relativeBaseDn' => '',
    );

    public function __construct($config = null) {
        parent::__construct($config);
        $this->connected = false;
        if (empty($this->config['port'])) {
            $this->connection = ldap_connect($this->config['host']);
        } else {
            $this->connection = ldap_connect($this->config['host'], $this->config['port']);
        }
        ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, $this->config['version']);
        if (ldap_bind($this->connection, $this->config['login'], $this->config['password']))
            $this->connected = true;
    }

    public function column($real) {
        return $real;
    }

    public function create(\Model $model, $fields = null, $values = null) {
        if ($fields == null) {
            unset($fields, $values);
            $fields = array_keys($model->data);
            $values = array_values($model->data);
        }

        $modelData = array();
        for ($i = 0; $i < count($fields); $i++) {
            $modelData[$fields[$i]] = $values[$i];
        }

        $ldapData = array(
            'objectClass' => $this->_getModelConfig($model, 'objectClass')
            ) + $this->_toLdapData($model, $modelData);
        $dn = $this->buildDnByData($model, $modelData);        
        
        unset($ldapData['dn']);

        if (@ldap_add($this->_getConnection(), $dn, $ldapData)) {
            $model->id = $dn;
            return true;
        } else {
            //$model->onError();
            //return false;
            $this->_throwPhysicalConnectionException(print_r(compact('dn', 'ldapData'), true));
        }
    }
    
    /**
     * Used to read records from the Datasource. The "R" in CRUD
     *     
     *
     * @param Model $model The model being read.
     * @param array $queryData An array of query data used to find the data you want
     * @param integer $recursive Number of levels of association
     * @return mixed
     */
    function read(\Model $model, $queryData = array(), $recursive = null) {                
        $queryData = $this->__scrubQueryData($queryData);
        $search = $this->_searchParameters($model, $queryData);
        
        $searchResult = @ldap_search(
                        $this->_getConnection()
                        , $search['baseDn']
                        , $search['filter']                
                        , $search['attributes']
                        , $search['attributesOnly']
                        , $search['sizeLimit']
                        , $search['timeLimit']
                        , $search['deref']
        );
        
        if ($searchResult === false) {
            if (ldap_errno($this->_getConnection()) == self::LDAP_ERROR_NO_SUCH_OBJECT) {
                return array();
            }
            
            $this->_throwPhysicalConnectionException(print_r($search,true));            
            $model->onError();
            return false;
        }
                
        $info = ldap_get_entries($this->_getConnection(), $searchResult);
        
        
        
        if ($this->_isQueryCount($queryData)) {      
            $result[0][$model->alias]['count'] =  $info['count'];
            return $result;            
        }
        
        unset($info['count']);        
        
        $modelInstances = array();                
        
        foreach($info as $ldapInstance) {
            $modelInstances[][$model->alias] = $this->_fromLdapData(
                $model
                , $ldapInstance);
        }                
        
        return $modelInstances;
    }
    
    private function _searchParameters(Model $model, $queryData) {        
        $conditions = $this->_parseConditions($model, $queryData['conditions']);
        
        if (array_key_exists("{$model->alias}.{$model->primaryKey}", $conditions)) {
            $baseDn = $queryData['conditions']["{$model->alias}.{$model->primaryKey}"];
            $filter = '(objectclass=*)';
        } else {
            $baseDn = $this->_getModelBaseDn($model);
            $filter = $this->_conditions($model, $conditions);
        }

        $attributes = array();
        $attributesOnly = null;
        $sizeLimit = null;
        $timeLimit = null;
        $deref = null;

        return compact(
                        'baseDn'
                        , 'filter'
                        , 'attributes'
                        , 'attributesOnly'
                        , 'sizeLimit'
                        , 'timeLimit'
                        , 'deref'
        );
    }
    
    private function _parseConditions($model, $conditions) {
        if (is_string($conditions)) {
            if (preg_match('/^([^=])+=(.+)$/', $conditions, $matches)) {
                return $this->_parseConditions($model, array(
                    $matches[1] => $matches[2]
                ));
            }
            else {
                throw new Exception("Condition pattern not recognized: $conditions");
            }
        }
        if (is_array ($conditions)) {
            $parsedConditions = array();
            foreach($conditions as $key => $value) {
                if (!is_string($value)) {
                    throw new Exception("Condition value is not a string");
                }
                                
                $parsedConditions[Basics::fieldFullName($key, $model->alias)] = $value;
            }
            return $parsedConditions;
        }
        else {
            throw new Exception('$conditions is not string neither array');
        }
    }
    
    private function _isQueryCount($queryData) {
        return is_string($queryData['fields']) &&
                $queryData['fields'] == 'COUNT(*) AS ' . $this->column('count');
    }        
    
    public function update(\Model $model, $fields = null, $values = null, $conditions = null) {        
        if ($conditions !== null) {
            throw new NotImplementedException("Unsuported update() call with \"conditions\" parameter");
        }
        
        if ($fields == null) {
            unset($fields, $values);
            $fields = array_keys($model->data);
            $values = array_values($model->data);
        }

        $modelData = array();
        for ($i = 0; $i < count($fields); $i++) {
            $modelData[$fields[$i]] = $values[$i];
        }                

        $ldapData = $this->_toLdapData($model, $modelData);
                
        if (!empty($modelData[$model->primaryKey]))  {
            $dn = $modelData[$model->primaryKey];
        }
        else if (!empty($model->id)) {
            $dn = $model->id;
        }                
        else {
            throw new Exception("No primary key value was defined");
        }                
        
        unset($ldapData['dn']);
                
        $rdnAttribute = $this->_rdnAttribute($dn);        
        if (isset($ldapData[$rdnAttribute])) {            
            $dn = $this->_renameRdn($dn, $ldapData[$rdnAttribute]);
            unset($ldapData[$rdnAttribute]);
            if (empty($ldapData)) {
                $model->id = $dn;
                return true;
            }
        }

        if (@ldap_modify($this->_getConnection(), $dn, $ldapData)) {
            $model->id = $dn;
            return true;
        } else {
            //$model->onError();
            //return false;
            $this->_throwPhysicalConnectionException(print_r(compact('dn', 'ldapData'), true));
        }
    }
    
    public   function calculate(&$model, $func, $params = array()) {
		$params = (array)$params;

		switch (strtolower($func)) {
			case 'count':
				if (!isset($params[0])) {
					$params[0] = '*';
				}
				if (!isset($params[1])) {
					$params[1] = 'count';
				}
				return 'COUNT(' . $this->column($params[0]) . ') AS ' . $this->column($params[1]);
			case 'max':
			case 'min':
				if (!isset($params[1])) {
					$params[1] = $params[0];
				}
				return strtoupper($func) . '(' . $this->column($params[0]) . ') AS ' . $this->column($params[1]);
			break;
		}
	}
    
    public function delete(\Model $model, $id = null) {
        if (!$id) {
            $id = array(
                "{$model->alias}.{$model->primaryKey}"=> $this->id
            );            
        }        
        
        $instances = $model->find(
                'all', array(
            'conditions' => $id
                )
        );
               
        if (empty($instances)) {
            return false;
        }                
        
        foreach($instances as $instance) {            
            if (!@ldap_delete($this->_getConnection(), $instance[$model->alias][$model->primaryKey])) {
                return false;
            }
        }
        
        return true;
    }

    public function describe($model) {
        if (empty($model->schema)) {
            throw new Exception("{$model->name} has no attribute '\$schema' defined");
        }
        
        $schema = array($model->primaryKey => array('type' => 'string')) + $model->schema;
        
        foreach(array_keys($schema) as $field) {
            $schema[$field] += array(
                'type' => 'string',
                'length' => null,
                'null' => false
            );
        }

        return $schema;
    }

    /**
     * 
     * @param Model $model
     * @param array $modelConditions
     * @return string
     * @throws NotImplementedException
     */
    public function _conditions(Model $model, $modelConditions) {
        $modelData = array();
        
        foreach($modelConditions as $modelField => $value) {
            list($alias,$field) = Basics::fieldNameToArray($modelField);
            if ($alias != $model->alias) {
                throw new NotImplementedException("Conditions with alias then self model: {$modelField} in {$model->alias}");
            }
            $modelData[$field] = $value;                        
        }
        
        $ldapData = array();
        foreach($this->_toLdapData($model, $modelData) as $attribute => $value) {
            $ldapData[$attribute] = $this->_quote($value);
        }

        $ldapData['objectClass'] = '*';
                
        return $this->_conditionsArrayToString($ldapData);        
    }        
    /**
     * Convert an array into a ldap condition string
     *
     * @param array $conditions condition
     * @return string
     */
    function _conditionsArrayToString($conditions) {
        if (empty($conditions)) {
            return null;
        }
        else {
            reset($conditions);
            $attribute = key($conditions);
            $value = $conditions[$attribute];
            unset($conditions[$attribute]);
            
            $currentCondition = "($attribute=$value)";
            $leftConditions = $this->_conditionsArrayToString($conditions);
            
            return $leftConditions ? '(&' . $currentCondition . $leftConditions . ')' : "$currentCondition";
        }
    }

    private function _quote($str) {
        return str_replace(
                array('\\', ' ', '*', '(', ')')
                , array('\\5c', '\\20', '\\2a', '\\28', '\\29'), $str
        );
    }

    /**
     * Private helper method to remove query metadata in given data array.
     *
     * @param array $queryData
     */
    function __scrubQueryData($queryData) {	
	if (!isset ($queryData['type']))
	    $queryData['type'] = 'default';

	if (!isset ($queryData['conditions']))
	    $queryData['conditions'] = array();

	if (!isset ($queryData['targetDn']))
	    $queryData['targetDn'] = null;

	if (!isset ($queryData['fields']) && empty($queryData['fields']))
	    $queryData['fields'] = array ();

	if (!isset ($queryData['order']) && empty($queryData['order']))
	    $queryData['order'] = array ();

	if (!isset ($queryData['limit']))
	    $queryData['limit'] = null;
        
        return $queryData;
    }
    
    private function _toLdapData(Model $model, $modelData) {
        $databaseToLdapMethod = '__' . $model->useDbConfig . $model->name . 'ToLdap';

        if (!method_exists(ConnectionManager::$config, $databaseToLdapMethod)) {
            throw new Exception("Class \"" . get_class(ConnectionManager::$config) . "\" has no method \"$databaseToLdapMethod\"");
        }
        
        $ldapData = array();
        
        if (!empty($modelData[$model->primaryKey])) {
            $ldapData['dn'] = $modelData[$model->primaryKey];
        }

        $ldapData += ConnectionManager::$config->{$databaseToLdapMethod}($modelData);

        return $ldapData;
    }
    
    private function _fromLdapData(Model $model, $ldapData) {
        unset($ldapData['objectclass']);
        unset($ldapData['count']);
        
        foreach ($ldapData as $key => $value) {
            if (is_numeric($key)) {
                unset($ldapData[$key]);
            } else if (is_array($value)) {
                $ldapData[$key] = array_key_exists(0, $value) ?
                    $value[0] :
                    null;
            }
        }

        $databaseToLdapMethod = '__' . $model->useDbConfig . $model->name . 'FromLdap';

        if (!method_exists(ConnectionManager::$config, $databaseToLdapMethod)) {
            throw new Exception("Class \"" . get_class(ConnectionManager::$config) . "\" has no method \"$databaseToLdapMethod\"");
        }
        
        $modelData = ConnectionManager::$config->{$databaseToLdapMethod}($ldapData);
        
        if (!empty($ldapData['dn'])) {
            $modelData[$model->primaryKey] = LdapUtils::normalizeDn($ldapData['dn']);
        }        


        return $modelData;
    }

    public function buildDnByData(Model $model, $modelData) {
        $ldapData = $this->_toLdapData($model, $modelData);
        $dnAttribute = $this->_getModelConfig($model, 'dnAttribute');

        if (empty($ldapData[$dnAttribute])) {
            throw new Exception("Ldap data has no DN attribute \"$dnAttribute\"");
        }

        $modelDn = $this->_getModelBaseDn($model);
        return LdapUtils::normalizeDn("$dnAttribute={$ldapData[$dnAttribute]}" . ($modelDn ? ',' . $modelDn : ''));
    }

    private function _getConnection() {
        if ($this->connection == null) {

            $this->connected = false;
            if (empty($this->config['port'])) {
                $this->connection = ldap_connect($this->config['host']);
            } else {
                $this->connection = ldap_connect($this->config['host'], $this->config['port']);
            }
            ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, $this->config['version']);
            if (!ldap_bind($this->connection, $this->config['login'], $this->config['password'])) {
                throw new Exception("Datasource not connected");
            }
        }

        return $this->connection;
    }

    private function _getModelBaseDn(Model $model) {
        $modelDn = $this->_getModelConfig($model, 'relativeBaseDn');
        $dataSourceDn = $this->config['database'] ? $this->config['database'] : '';

        if ($modelDn && $dataSourceDn) {
            return LdapUtils::normalizeDn($modelDn . ',' . $dataSourceDn);
        } else {
            return LdapUtils::normalizeDn($modelDn . $dataSourceDn);
        }
    }

    private function _getModelConfig(Model $model, $key) {
        if (isset($this->config['models'][$model->name][$key])) {
            return $this->config['models'][$model->name][$key];
        } else if (!empty($this->_modelBaseConfig[$key])) {
            return $this->_modelBaseConfig[$key];
        } else {
            throw new Exception("No config '$key' defined for model \"{$model->name}\"");
        }
    }

    private function _throwPhysicalConnectionException($message) {
        $errorCode = ldap_errno($this->_getConnection());
        throw new Exception(
            ldap_err2str($errorCode) . " (Code: $errorCode)" .
            ($message ? "\n$message" : '')
        );
    }
    
    private function _rdnAttribute($dn) {
        if (($firstEqualsPosition = strpos($dn, '=')) === false) {
            throw new Exception("DN bad formatted: $dn");
        } else {
            return substr($dn, 0, $firstEqualsPosition);
        }
    }
    
    /**
     * 
     * @param type $dn
     * @param type $rdnValue
     * @return O novo valor do DN
     */
    private function _renameRdn($dn, $rdnValue) {
        $rdn = $this->_rdnAttribute($dn) . '=' . $rdnValue;   
        $parentDn = $this->_parentDn($dn);
        //$newDn = $this->_getRenamedDn($dn, $rdn)
        //$this->_throwPhysicalConnectionException(print_r(compact('dn','rdnValue','rdn', 'newDn'),true));
        if (ldap_rename(
                $this->_getConnection()
                , $dn
                , $rdn
                , $parentDn
                , true
        )) {
            return $this->_getRenamedDn($dn, $rdn);
        } else {
            $this->_throwPhysicalConnectionException(print_r(compact('dn','rdnValue','rdn'),true));
        }
    }    
    
    private function _getRenamedDn($dn,$rdn) {
        return $rdn . ',' . $this->_parentDn($dn);
    }
    
    private function _parentDn($dn) {
        $parts = ldap_explode_dn($dn, 0);
        unset($parts['count']);
        array_shift($parts);        
        return implode(',', $parts);
    }

} // LdapSource
?>