<?php

App::uses('Basics', 'Base.Lib');

class Ldap extends DataSource {

    const LDAP_ERROR_NO_SUCH_OBJECT = 32;

    private $connection = null;
    var $description = "Ldap Data Source";


    var $startQuote = '';
    var $endQuote = '';

    var $cacheSources = true;

    protected $_baseConfig = array(
        'host' => 'localhost',
        'version' => 3
    );
    
    private $_modelBaseConfig = array(
        'relativeBaseDn' => '',
    );
    
    private $affected;
    private $_queriesLogMax;

    var $__descriptions = array();
    
    public $_queriesLog = array();
    public $_queriesCnt;
    public $_queriesTime;

    // Lifecycle --------------------------------------------------------------
    /**
     * Constructor
     */
    function __construct($config = null) {

	$this->debug = Configure :: read() > 0;
	$this->fullDebug = Configure :: read() > 1;
	parent::__construct($config);
	return $this->connect();
    }

    /**
     * Destructor. Closes connection to the database.
     *
     */
    function __destruct() {
	$this->close();
	parent :: __destruct();
    }

    // I know this looks funny, and for other data sources this is necessary but for LDAP, we just return the name of the field we're passed as an argument
    function name( $field ) {
        return $this->column($field);
    }
    
    function column($real) {
        return $real;
    }

    // Connection --------------------------------------------------------------
    function connect() {
	$config = $this->config;
	$this->connected = false;
        if (empty($config['port'])) {
            $this->connection = ldap_connect($config['host']);        
        }
        else {
            $this->connection = ldap_connect($config['host'], $config['port']);        
        }	
	ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, $config['version']);
	if (ldap_bind($this->connection, $config['login'], $config['password']))
	    $this->connected = true;

	return $this->connected;
    }

    /**
     * Disconnects database, kills the connection and says the connection is closed,
     * and if DEBUG is turned on, the log for this object is shown.
     *
     */
    function close() {
	if ($this->fullDebug && Configure :: read('debug') > 1) {
	    $this->showLog();
	}
	$this->disconnect();
    }

    function disconnect() {
	@ldap_free_result($this->results);
	$this->connected = !@ldap_unbind($this->connection);
	return !$this->connected;
    }

    /**
     * Checks if it's connected to the database
     *
     * @return boolean True if the database is connected, else false
     */
    function isConnected() {
	return $this->connected;
    }

    /**
     * Reconnects to database server with optional new settings
     *
     * @param array $config An array defining the new configuration settings
     * @return boolean True on success, false on failure
     */
    function reconnect($config = null) {
	$this->disconnect();
	if ($config != null) {
	    $this->config = am($this->_baseConfig, $this->config, $config);
	}
	return $this->connect();
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
        
        $isCount = $this->_isQueryCount($queryData);
        
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
        if (is_string($queryData['conditions'])) {
            throw new NotImplementedException("Query data conditions as string");
        }
        if (array_key_exists("{$model->alias}.{$model->primaryKey}", $queryData['conditions'])) {
            $baseDn = $queryData['conditions']["{$model->alias}.{$model->primaryKey}"];
            $filter = '(objectclass=*)';
        } else {
            $baseDn = $this->_getModelBaseDn($model);
            $filter = $this->_conditions($model, $queryData['conditions']);
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
    
    private function _isQueryCount($queryData) {
        return is_string($queryData['fields']) &&
                $queryData['fields'] == 'COUNT(*) AS ' . $this->name('count');
    }        

    /**
     * The "U" in CRUD
     */
    function update( &$model, $fields = null, $values = null ) {	
	$fieldsData = array();

	if ($fields == null) {
	    unset($fields, $values);
	    $fields = array_keys( $model->data );
	    $values = array_values( $model->data );
	}

	for ($i = 0; $i < count( $fields ); $i++) {
	    if ($fields[$i] != '_DN_') {
		$fieldsData[$fields[$i]] = $values[$i];
	    }
	}

	// Find the user we will update as we need their dn
	if( $model->defaultObjectClass ) {
	    $queryData['conditions'] = sprintf( '(&(objectclass=%s)(%s=%s))', $model->defaultObjectClass, $model->primaryKey, $model->id );
	} else {
	    $queryData['conditions'] = sprintf( '%s=%s', $model->primaryKey, $model->id );
	}

	// fetch the record
	$resultSet = $this->read( $model, $queryData, $model->recursive );

	if( $resultSet) {
	    $_dn = $resultSet[0][$model->alias]['dn'];	    

	    if( ldap_modify( $this->connection, $_dn, $fieldsData ) ) {
		return true;
	    }
            else {
                throw new Exception(ldap_error($this->connection).' / '.print_r(compact('_dn','fieldsData'),true));
            }
	}

	// If we get this far, something went horribly wrong ..
	$model->onError();
	return false;
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

    /**
     * The "D" in CRUD
     */
    function _delete( &$model ) {
    // Boolean to determine if we want to recursively delete or not
	$recursive = true;

	// Find the user we will update as we need their dn
	if( $model->defaultObjectClass ) {
	    $queryData['conditions'] = sprintf( '(&(objectclass=%s)(%s=%s))', $model->defaultObjectClass, $model->primaryKey, $model->id );
	} else {
	    $queryData['conditions'] = sprintf( '%s=%s', $model->primaryKey, $model->id );
	}

	// fetch the record
	$resultSet = $this->read( $model, $queryData, $model->recursive );

	if( $resultSet) {
	    if( $recursive === true ) {
	    // Recursively delete LDAP entries
		if( $this->__deleteRecursively( $resultSet[0][$model->name]['dn'] ) ) {
		    return true;
		}
	    } else {
	    // Single entry delete
		if( @ldap_delete( $this->connection, $resultSet[0][$model->name]['dn'] ) ) {
		    return true;
		}
	    }
	}

	$model->onError();
	return false;
    }

    /* Courtesy of gabriel at hrz dot uni-marburg dot de @ http://ar.php.net/ldap_delete */
    function __deleteRecursively( $_dn ) {
    // Search for sub entries
	$subentries = ldap_list( $this->connection, $_dn, "objectClass=*", array() );
	$info = ldap_get_entries( $this->connection, $subentries );
	for( $i = 0; $i < $info['count']; $i++ ) {
	// deleting recursively sub entries
	    $result = $this->__deleteRecursively( $info[$i]['dn'] );
	    if( !$result ) {
		return false;
	    }
	}

	return( @ldap_delete( $this->connection, $_dn ) );
    }

    // Public --------------------------------------------------------------
    function generateAssociationQuery(& $model, & $linkModel, $type, $association = null, $assocData = array (), & $queryData, $external = false, & $resultSet) {
	$this->__scrubQueryData($queryData);

	switch ($type) {
	    case 'hasOne' :
		$id = $resultSet[$model->name][$model->primaryKey];
		$queryData['conditions'] = trim($assocData['foreignKey']) . '=' . trim($id);
		$queryData['targetDn'] = $linkModel->useTable;
		$queryData['type'] = 'search';
		$queryData['limit'] = 1;

		return $queryData;

	    case 'belongsTo' :
		$id = $resultSet[$model->name][$assocData['foreignKey']];
		$queryData['conditions'] = trim($linkModel->primaryKey).'='.trim($id);
		$queryData['targetDn'] = $linkModel->useTable;
		$queryData['type'] = 'search';
		$queryData['limit'] = 1;

		return $queryData;

	    case 'hasMany' :
		$id = $resultSet[$model->name][$model->primaryKey];
		$queryData['conditions'] = trim($assocData['foreignKey']) . '=' . trim($id);
		$queryData['targetDn'] = $linkModel->useTable;
		$queryData['type'] = 'search';
		$queryData['limit'] = $assocData['limit'];

		return $queryData;

	    case 'hasAndBelongsToMany' :
		return null;
	}
	return null;
    }

    function queryAssociation(& $model, & $linkModel, $type, $association, $assocData, & $queryData, $external = false, & $resultSet, $recursive, $stack) {

	if (!isset ($resultSet) || !is_array($resultSet)) {
	    if (Configure :: read() > 0) {
		e('<div style = "font: Verdana bold 12px; color: #FF0000">SQL Error in model ' . $model->name . ': ');
		if (isset ($this->error) && $this->error != null) {
		    e($this->error);
		}
		e('</div>');
	    }
	    return null;
	}

	$count = count($resultSet);
	for ($i = 0; $i < $count; $i++) {

	    $row = & $resultSet[$i];
	    $queryData = $this->generateAssociationQuery($model, $linkModel, $type, $association, $assocData, $queryData, $external, $row);
	    $fetch = $this->_executeQuery($queryData);
	    $fetch = ldap_get_entries($this->connection, $fetch);
	    $fetch = $this->_ldapFormat($linkModel,$fetch);

	    if (!empty ($fetch) && is_array($fetch)) {
		if ($recursive > 0) {
		    foreach ($linkModel->__associations as $type1) {
			foreach ($linkModel-> {$type1 } as $assoc1 => $assocData1) {
			    $deepModel = & $linkModel->{$assocData1['className']};
			    if ($deepModel->alias != $model->name) {
				$tmpStack = $stack;
				$tmpStack[] = $assoc1;
				if ($linkModel->useDbConfig == $deepModel->useDbConfig) {
				    $db = & $this;
				} else {
				    $db = & ConnectionManager :: getDataSource($deepModel->useDbConfig);
				}
				$queryData = array();
				$db->queryAssociation($linkModel, $deepModel, $type1, $assoc1, $assocData1, $queryData, true, $fetch, $recursive -1, $tmpStack);
			    }
			}
		    }
		}
		$this->__mergeAssociation($resultSet[$i], $fetch, $association, $type);

	    } else {
		$tempArray[0][$association] = false;
		$this->__mergeAssociation($resultSet[$i], $tempArray, $association, $type);
	    }
	}
    }

    /**
     * Returns a formatted error message from previous database operation.
     *
     * @return string Error message with error number
     */
    function lastError() {
	if (ldap_errno($this->connection)) {
	    return ldap_errno($this->connection) . ': ' . ldap_error($this->connection);
	}
	return null;
    }

    /**
     * Returns number of rows in previous resultset. If no previous resultset exists,
     * this returns false.
     *
     * @return int Number of rows in resultset
     */
    function lastNumRows() {
	if ($this->_result and is_resource($this->_result)) {
	    return @ ldap_count_entries($this->connection, $this->_result);
	}
	return null;
    }

    // Usefull public (static) functions--------------------------------------------
    /**
     * Convert Active Directory timestamps to unix ones
     *
     * @param integer $ad_timestamp Active directory timestamp
     * @return integer Unix timestamp
     */
    function convertTimestamp_ADToUnix($ad_timestamp) {
	$epoch_diff = 11644473600; // difference 1601<>1970 in seconds. see reference URL
	$date_timestamp = $ad_timestamp * 0.0000001;
	$unix_timestamp = $date_timestamp - $epoch_diff;
	return $unix_timestamp;
    }// convertTimestamp_ADToUnix

    public function describe($model) {
        if (empty($model->schema)) {
            throw new Exception("{$model->name} has no attribute '\$schema' defined");
        }
        
        $schema = array($model->primaryKey => array('type' => 'string')) + $model->schema;
        
        foreach(array_keys($schema) as $field) {
            $schema[$field] += array(
                'length' => null,
                'null' => false
            );
        }

        return $schema;
    }

    /* The following was kindly "borrowed" from the excellent phpldapadmin project */
    function __getLDAPschema() {
	$schemaTypes = array( 'objectclasses', 'attributetypes' );
	foreach (array('(objectClass=*)','(objectClass=subschema)') as $schema_filter) {
	    $schema_search = @ldap_read($this->connection, 'cn=Subschema', $schema_filter, $schemaTypes,0,0,0,LDAP_DEREF_ALWAYS);

	    if( is_null( $schema_search ) ) {
		$this->log( "LDAP schema filter $schema_filter is invalid!" );
		continue;
	    }

	    $schema_entries = @ldap_get_entries( $this->connection, $schema_search );

	    if ( is_array( $schema_entries ) && isset( $schema_entries['count'] ) ) {
		break;
	    }

	    unset( $schema_entries );
	    $schema_search = null;
	}
        
    $return = array();

	if( isset($schema_entries) ) {		    
	    foreach( $schemaTypes as $n ) {
                if (isset($schema_entries[0][$n])) {
                    $schemaTypeEntries = $schema_entries[0][$n];
                    for( $x = 0; $x < $schemaTypeEntries['count']; $x++ ) {
                        $entry = array();
                        $strings = preg_split('/[\s,]+/', $schemaTypeEntries[$x], -1, PREG_SPLIT_DELIM_CAPTURE);
                        $str_count = count( $strings );
                        for ( $i=0; $i < $str_count; $i++ ) {
                            switch ($strings[$i]) {
                                case '(':
                                    break;
                                case 'NAME':
                                    if ( $strings[$i+1] != '(' ) {
                                        do {
                                            $i++;
                                            if( !isset( $entry['name'] ) || strlen( $entry['name'] ) == 0 )
                                                $entry['name'] = $strings[$i];
                                            else
                                                $entry['name'] .= ' '.$strings[$i];
                                        } while ( !preg_match('/\'$/s', $strings[$i]));
                                    } else {
                                        $i++;
                                        do {
                                            $i++;
                                            if( !isset( $entry['name'] ) || strlen( $entry['name'] ) == 0)
                                                $entry['name'] = $strings[$i];
                                            else
                                                $entry['name'] .= ' ' . $strings[$i];
                                        } while ( !preg_match( '/\'$/s', $strings[$i] ) );
                                        do {
                                            $i++;
                                        } while ( !preg_match( '/\)+\)?/', $strings[$i] ) );
                                    }

                                    $entry['name'] = preg_replace('/^\'/', '', $entry['name'] );
                                    $entry['name'] = preg_replace('/\'$/', '', $entry['name'] );
                                    break;
                                case 'DESC':
                                    do {
                                        $i++;
                                        if ( !isset( $entry['description'] ) || strlen( $entry['description'] ) == 0 )
                                            $entry['description'] = $strings[$i];
                                        else
                                            $entry['description'] .= ' ' . $strings[$i];
                                    } while ( !preg_match( '/\'$/s', $strings[$i] ) );
                                    break;
                                case 'OBSOLETE':
                                    $entry['is_obsolete'] = TRUE;
                                    break;
                                case 'SUP':
                                    $entry['sup_classes'] = array();
                                    if ( $strings[$i+1] != '(' ) {
                                        $i++;
                                        array_push( $entry['sup_classes'], preg_replace( "/'/", '', $strings[$i] ) );
                                    } else {
                                        $i++;
                                        do {
                                            $i++;
                                            if ( $strings[$i] != '$' )
                                                array_push( $entry['sup_classes'], preg_replace( "/'/", '', $strings[$i] ) );
                                        } while (! preg_match('/\)+\)?/',$strings[$i+1]));
                                    }
                                    break;
                                case 'ABSTRACT':
                                    $entry['type'] = 'abstract';
                                    break;
                                case 'STRUCTURAL':
                                    $entry['type'] = 'structural';
                                    break;
                                case 'AUXILIARY':
                                    $entry['type'] = 'auxiliary';
                                    break;
                                case 'MUST':
                                    $entry['must'] = array();

                                    $i = $this->_parse_list(++$i, $strings, $entry['must']);

                                    break;

                                case 'MAY':
                                    $entry['may'] = array();

                                    $i = $this->_parse_list(++$i, $strings, $entry['may']);

                                    break;
                                default:
                                    if( preg_match( '/[\d\.]+/i', $strings[$i]) && $i == 1 ) {
                                        $entry['oid'] = $strings[$i];
                                    }
                                    break;
                            }
                        }
                        if( !isset( $return[$n] ) || !is_array( $return[$n] ) ) {
                            $return[$n] = array();
                        }
                        array_push( $return[$n], $entry );
                    }
                }
            }
	}

	//        $fields = Set::combine( $attributes, '{n}.name', '{n}.description' );
	//        $fields['dn'] = 'DN of the entry in question';

	return $return;
    }

    function _parse_list( $i, $strings, &$attrs ) {
    /**
     ** A list starts with a ( followed by a list of attributes separated by $ terminated by )
     ** The first token can therefore be a ( or a (NAME or a (NAME)
     ** The last token can therefore be a ) or NAME)
     ** The last token may be terminate by more than one bracket
     */
	$string = $strings[$i];
	if (!preg_match('/^\(/',$string)) {
	// A bareword only - can be terminated by a ) if the last item
	    if (preg_match('/\)+$/',$string))
		$string = preg_replace('/\)+$/','',$string);

	    array_push($attrs, $string);
	} elseif (preg_match('/^\(.*\)$/',$string)) {
	    $string = preg_replace('/^\(/','',$string);
	    $string = preg_replace('/\)+$/','',$string);
	    array_push($attrs, $string);
	} else {
	// Handle the opening cases first
	    if ($string == '(') {
		$i++;

	    } elseif (preg_match('/^\(./',$string)) {
		$string = preg_replace('/^\(/','',$string);
		array_push ($attrs, $string);
		$i++;
	    }

	    // Token is either a name, a $ or a ')'
	    // NAME can be terminated by one or more ')'
	    while (! preg_match('/\)+$/',$strings[$i])) {
		$string = $strings[$i];
		if ($string == '$') {
		    $i++;
		    continue;
		}

		if (preg_match('/\)$/',$string)) {
		    $string = preg_replace('/\)+$/','',$string);
		} else {
		    $i++;
		}
		array_push ($attrs, $string);
	    }
	}
	sort($attrs);

	return $i;
    }

    /**
     * Function not supported
     */
    function execute($query) {
	return null;
    }

    /**
     * Function not supported
     */
    function fetchAll($query, $cache = true) {
	return array();
    }

    // Logs --------------------------------------------------------------
    /**
     * Log given LDAP query.
     *
     * @param string $query LDAP statement
     * @todo: Add hook to log errors instead of returning false
     */
    function logQuery($query) {
	$this->_queriesCnt++;
	$this->_queriesTime += $this->took;
	$this->_queriesLog[] = array (
	    'query' => $query,
	    'error' => $this->error,
	    'affected' => $this->affected,
	    'numRows' => $this->numRows,
	    'took' => $this->took
	);
	if (count($this->_queriesLog) > $this->_queriesLogMax) {
	    array_pop($this->_queriesLog);
	}
	if ($this->error) {
	    return false;
	}
    }

    /**
     * Outputs the contents of the queries log.
     *
     * @param boolean $sorted
     */
    function showLog($sorted = false) {
	if ($sorted) {
	    $log = sortByKey($this->_queriesLog, 'took', 'desc', SORT_NUMERIC);
	} else {
	    $log = $this->_queriesLog;
	}

	if ($this->_queriesCnt > 1) {
	    $text = 'queries';
	} else {
	    $text = 'query';
	}

	if (php_sapi_name() != 'cli') {
	    print ("<table id=\"cakeSqlLog\" cellspacing=\"0\" border = \"0\">\n<caption>{$this->_queriesCnt} {$text} took {$this->_queriesTime} ms</caption>\n");
	    print ("<thead>\n<tr><th>Nr</th><th>Query</th><th>Error</th><th>Affected</th><th>Num. rows</th><th>Took (ms)</th></tr>\n</thead>\n<tbody>\n");

	    foreach ($log as $k => $i) {
		print ("<tr><td>" . ($k +1) . "</td><td>{$i['query']}</td><td>{$i['error']}</td><td style = \"text-align: right\">{$i['affected']}</td><td style = \"text-align: right\">{$i['numRows']}</td><td style = \"text-align: right\">{$i['took']}</td></tr>\n");
	    }
	    print ("</table>\n");
	} else {
	    foreach ($log as $k => $i) {
		print (($k +1) . ". {$i['query']} {$i['error']}\n");
	    }
	}
    }

    /**
     * Output information about a LDAP query. The query, number of rows in resultset,
     * and execution time in microseconds. If the query fails, an error is output instead.
     *
     * @param string $query Query to show information on.
     */
    function showQuery($query) {
	$error = $this->error;
	if (strlen($query) > 200 && !$this->fullDebug) {
	    $query = substr($query, 0, 200) . '[...]';
	}

	if ($this->debug || $error) {
	    print ("<p style = \"text-align:left\"><b>Query:</b> {$query} <small>[Aff:{$this->affected} Num:{$this->numRows} Took:{$this->took}ms]</small>");
	    if ($error) {
		print ("<br /><span style = \"color:Red;text-align:left\"><b>ERROR:</b> {$this->error}</span>");
	    }
	    print ('</p>');
	}
    }

    // _ private --------------------------------------------------------------
    public function _conditions(Model $model, $modelConditions) {
        $modelData = array();
        
        foreach($modelConditions as $modelField => $value) {
            list($alias,$field) = Basics::fieldNameToArray($modelField);
            if ($alias != $model->alias) {
                throw new NotImplementedException("Conditions with alias then self model: {$modelField} in {$model->alias}");
            }
            $modelData[$field] = $value;                        
        }
        
        $ldapData = array(
            'objectClass' => '*'
        ) + $this->_toLdapData($model, $modelData);
                
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

    function _executeQuery($queryData = array (), $cache = true) {
	$t = microtime(true);
	$query = $this->_queryToString($queryData);
	if ($cache && isset ($this->_queryCache[$query])) {
	    if (strpos(trim(strtolower($query)), $queryData['type']) !== false) {
		$res = $this->_queryCache[$query];
	    }
	} else {
	    switch ($queryData['type']) {
		case 'search':
		// TODO pb ldap_search & $queryData['limit']		    
		    if ($res = @ ldap_search($this->connection, ( ( $queryData['targetDn'] ) ? $queryData['targetDn'] . ',' : null ) . $this->config['basedn'], $queryData['conditions'], $queryData['fields'], 0, $queryData['limit'])) {
			if ($cache) {
			    if (strpos(trim(strtolower($query)), $queryData['type']) !== false) {
				$this->_queryCache[$query] = $res;
			    }
			}
		    } else {
			$res = false;
		    }
		    break;
		case 'delete':
		    $res = @ ldap_delete($this->connection, $queryData['targetDn'] . ',' . $this->config['basedn']);
		    break;
		default:
		    $res = false;
		    break;
	    }
	}

	$this->_result = $res;
	$this->took = round((microtime(true) - $t) * 1000, 0);
	$this->error = $this->lastError();
	$this->numRows = $this->lastNumRows();

	if ($this->fullDebug) {
	    $this->logQuery($query);
	}

	return $this->_result;
    }

    function _queryToString($queryData) {
	$tmp = '';
	if (!empty($queryData['conditions']))
	    $tmp .= ' | cond: '.$queryData['conditions'].' ';

	if (!empty($queryData['targetDn']))
	    $tmp .= ' | targetDn: '.$queryData['targetDn'].','.$this->config['basedn'].' ';

	$fields = '';
	if (!empty($queryData['fields']) && is_array( $queryData['fields'] ) ) {
	    $fields .= ' | fields: ';
	    foreach ($queryData['fields'] as $field)
		$fields .= ' ' . $field;
	    $tmp .= $queryData['fields'].' ';
	}

	if (!empty($queryData['order']))
	    $tmp .= ' | order: '.$queryData['order'][0].' ';

	if (!empty($queryData['limit']))
	    $tmp .= ' | limit: '.$queryData['limit'];

	return $queryData['type'] . $tmp;
    }

    function _ldapFormat(& $model, $data) {
	$res = array ();

	foreach ($data as $key => $row) {
	    if ($key === 'count')
		continue;

	    foreach ($row as $key1 => $param) {
		if ($key1 === 'dn') {
		    $res[$key][$model->name][$key1] = $param;
		    continue;
		}
		if (!is_numeric($key1))
		    continue;
		if ($row[$param]['count'] === 1)
		    $res[$key][$model->name][$param] = $row[$param][0];
		else {
		    foreach ($row[$param] as $key2 => $item) {
			if ($key2 === 'count')
			    continue;
			$res[$key][$model->name][$param][] = $item;
		    }
		}
	    }
	}
	return $res;
    }

    function _ldapQuote($str) {
	return str_replace(
	array( '\\', ' ', '*', '(', ')' ),
	array( '\\5c', '\\20', '\\2a', '\\28', '\\29' ),
	$str
	);
    }

    // __ -----------------------------------------------------
    function __mergeAssociation(& $data, $merge, $association, $type) {

	if (isset ($merge[0]) && !isset ($merge[0][$association])) {
	    $association = Inflector :: pluralize($association);
	}

	if ($type == 'belongsTo' || $type == 'hasOne') {
	    if (isset ($merge[$association])) {
		$data[$association] = $merge[$association][0];
	    } else {
		if (count($merge[0][$association]) > 1) {
		    foreach ($merge[0] as $assoc => $data2) {
			if ($assoc != $association) {
			    $merge[0][$association][$assoc] = $data2;
			}
		    }
		}
		if (!isset ($data[$association])) {
		    $data[$association] = $merge[0][$association];
		} else {
		    if (is_array($merge[0][$association])) {
			$data[$association] = array_merge($merge[0][$association], $data[$association]);
		    }
		}
	    }
	} else {
	    if ($merge[0][$association] === false) {
		if (!isset ($data[$association])) {
		    $data[$association] = array ();
		}
	    } else {
		foreach ($merge as $i => $row) {
		    if (count($row) == 1) {
			$data[$association][] = $row[$association];
		    } else {
			$tmp = array_merge($row[$association], $row);
			unset ($tmp[$association]);
			$data[$association][] = $tmp;
		    }
		}
	    }
	}
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

    function __checkQueryDataDnCondition(&$model,&$data) {	
	if (is_array($data['conditions']) && isset($data['conditions']['dn']) && $data['conditions']['dn']) {
	    $replace = '';
	    $count = 1;
	    $data['targetDn'] = str_replace(
		','.$this->config['basedn'],
		$replace,
		$data['conditions']['dn'],
		$count
	    );	    
	    $data['conditions']= array();	    
	}
	else {	 
	    $data['targetDn'] = $model->useTable;
	}		
    }

    function __getObjectclasses() {
	$cache = null;
	if ($this->cacheSources !== false) {
	    if (isset($this->__descriptions['ldap_objectclasses'])) {
		$cache = $this->__descriptions['ldap_objectclasses'];
	    } else {
		$cache = $this->__cacheDescription('objectclasses');
	    }
	}

	if ($cache != null) {
	    return $cache;
	}

	// If we get this far, then we haven't cached the attribute types, yet!
	$ldapschema = $this->__getLDAPschema();
	$objectclasses = $ldapschema['objectclasses'];

	// Cache away
	$this->__cacheDescription( 'objectclasses', $objectclasses );

	return $objectclasses;
    }

    // This was an attempt to automatically get the objectclass that an attribute belongs to. Unfortunately, more than one objectclass
    // can define the same attribute as a MAY or MUST which means it's impossible to know which objectclass is the right one.
    // Due to this problem (which I only realized once I had it working and it was returning objectclasses I wasn't interested in), this
    // function is no longer in use. Objectclasses must be defined inside $this->data when calling $this->save.
    function __getObjectclassForAttribute( $attr, &$ret = array() ) {
	$res = null;
	if ($this->cacheSources !== false) {
	    if (isset($this->__descriptions['ldap_attributes_for_objectclasses'])) {
		$res = $this->__descriptions['ldap_attributes_for_objectclasses'];
	    } else {
		$res = $this->__cacheDescription('attributes_for_objectclasses');
	    }
	}

	if ($res == null) {
	    $objectclasses = $this->__getObjectclasses();
	    $musts = Set::combine( $objectclasses, '{n}.name', '{n}.must' );
	    $mays  = Set::combine( $objectclasses, '{n}.name', '{n}.may' );

	    $attributes = array();

	    // Please feel free to suggest a better way of doing this
	    foreach( array( 'musts', 'mays' ) as $n ) {
		foreach( ${$n} as $_key => $_vals ) {
		    if( !isset( $attributes[$_key] ) ) {
			$attributes[$_key] = array();
		    }
		    if( is_array( $_vals ) ) {
			foreach( $_vals as $_val ) {
			    array_push( $attributes[$_key], $_val );
			}
		    }
		}
	    }

	    // Cache away
	    $this->__cacheDescription( 'attributes_for_objectclasses', $attributes );

	    $res =& $attributes;
	}

	// Now we check if the attribute type exists and what objectclass it's found in
	if( is_array( $attr ) ) {
	    foreach( $attr as $x ) {
		$this->__getObjectclassForAttribute( $x, $ret );
	    }
	} else {
	    foreach( $res as $obj => $attrs ) {
		if( in_array( $attr, $attrs ) ) {
		    if( !isset( $ret[$obj] ) ) {
			$ret[$obj] = 1;
		    }
		    return $ret;
		}
	    }
	}

	return $ret;
    }

    function boolean() {
	return null;
    }

    function calculate(&$model, $func, $params = array()) {
		$params = (array)$params;

		switch (strtolower($func)) {
			case 'count':
				if (!isset($params[0])) {
					$params[0] = '*';
				}
				if (!isset($params[1])) {
					$params[1] = 'count';
				}
				return 'COUNT(' . $this->name($params[0]) . ') AS ' . $this->name($params[1]);
			case 'max':
			case 'min':
				if (!isset($params[1])) {
					$params[1] = $params[0];
				}
				return strtoupper($func) . '(' . $this->name($params[0]) . ') AS ' . $this->name($params[1]);
			break;
		}
	}
        
    function __cacheDescription($object, $data = null) {
        if ($this->cacheSources === false) {
            return null;
        }

        if ($data !== null) {
            $this->__descriptions[$object] = & $data;
        }

        $key = ConnectionManager::getSourceName($this) . '_' . $object;
        $cache = Cache::read($key, '_cake_model_');

        if (empty($cache)) {
            $cache = $data;
            Cache::write($key, $cache, '_cake_model_');
        }

        return $cache;
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
            $modelData[$model->primaryKey] = $ldapData['dn'];
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
        return "$dnAttribute={$ldapData[$dnAttribute]}" . ($modelDn ? ',' . $modelDn : '');
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
            return $modelDn . ',' . $dataSourceDn;
        } else {
            return $modelDn . $dataSourceDn;
        }
    }

    private function _getModelConfig(Model $model, $key) {
        if (isset($this->config['models'][$model->name][$key])) {
            return $this->config['models'][$model->name][$key];
        } else if ($this->_modelBaseConfig[$key]) {
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

} // LdapSource
?>