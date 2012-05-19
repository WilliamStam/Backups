<?php

namespace DB;

//! PDO wrapper
class SQL extends \PDO {

	public
		//! Current SQL engine
		$ENGINE,
		//! Database name
		$DBNAME,
		//! Session handler
		$SESSION;

	private
		//! Transaction flag
		$INTXN=FALSE,
		//! Number of rows affected by query
		$ROWS=0,
		//! SQL log
		$LOG='';

	/**
		Start SQL transaction
			@return void
	**/
	function begin() {
		parent::begintransaction();
		$this->INTXN=TRUE;
	}

	/**
		Cancel transaction
			@return void
	**/
	function rollback() {
		parent::rollback();
		$this->INTXN=FALSE;
	}

	/**
		Commit changes
			@return void
	**/
	function commit() {
		parent::commit();
		$this->INTXN=FALSE;
	}

	/**
		Map data type of argument to a PDO constant
			@return int
			@param $val scalar
	**/
	function type($val) {
		switch (gettype($val)) {
			case 'NULL':
				return \PDO::PARAM_NULL;
			case 'boolean':
				return \PDO::PARAM_BOOL;
			case 'integer':
				return \PDO::PARAM_INT;
			default:
				return \PDO::PARAM_STR;
		}
	}

	/**
		Execute SQL statement(s)
			@return array|int|FALSE
			@param $cmds string|array
			@param $args array
			@param $ttl int
	**/
	function exec($cmds,array $args=NULL,$ttl=0) {
		$fw=\Base::instance();
		$auto=FALSE;
		if (!is_array($cmds)) {
			$cmds=array($cmds);
			$args=array($args?:array());
		}
		else {
			if (!$this->INTXN) {
				// Start transaction
				$this->begin();
				$auto=TRUE;
			}
			if (!$args)
				$args=array();
			$args+=array_fill($i=count($args),count($cmds)-$i,array());
		}
		$cache=\Cache::instance();
		foreach (array_combine($cmds,$args) as $cmd=>$arg) {
			if ($ttl && ($cached=$cache->exists(
				$hash=$fw->hash($cmd.$fw->export($arg)).'.sql')) &&
				$cached+$ttl>time())
				$result=$cache->get($hash);
			else {
				if (is_object($query=$this->prepare($cmd))) {
					$keys=$vals=array();
					foreach ($arg as $key=>$val) {
						if (is_array($val)) {
							// User-specified data type
							$query->bindvalue($key,$val[0],$val[1]);
							$vals[]=$fw->export($val[0]);
						}
						else {
							// Use PHP data type
							$query->bindvalue($key,$val,$this->type($val));
							$vals[]=$fw->export($val);
						}
						$keys[]='/'.(is_numeric($key)?'\?':$key).'/';
					}
					$query->execute();
					$this->LOG.=preg_replace($keys,$vals,$cmd,1)."\n";
				}
				foreach (array($this,$query) as $obj) {
					$error=$obj->errorinfo();
					if ($error[0]!=\PDO::ERR_NONE) {
						if ($this->INTXN)
							$this->rollback();
						trigger_error('PDO: '.$error[2]);
						return FALSE;
					}
				}
				if (preg_match(
					'/^\s*(?:CALL|SELECT|PRAGMA|SHOW|EXPLAIN)\s/i',$cmd)) {
					$result=$query->fetchall(\PDO::FETCH_ASSOC);
					$this->ROWS=count($result);
				}
				else
					$this->ROWS=$result=$query->rowcount();
				if ($ttl)
					$cache->set($hash,$result,$ttl);
			}
		}
		if ($this->INTXN && $auto)
			$this->commit();
		return $result;
	}

	/**
		Return SQL session log
			@return string
	**/
	function log() {
		return $this->LOG;
	}

	/**
		Return number of rows affected by latest query
			@return int
	**/
	function rows() {
		return $this->ROWS;
	}

	/**
		Return TRUE if SQL table exists
			@return bool
			@param $table string
	**/
	function exists($table) {
		$cmd=array(
			'sqlite2?'=>
				'SELECT name FROM sqlite_master '.
				'WHERE type=\'table\' AND name=\''.$table.'\';',
			'mysql|mssql|sybase|dblib|pgsql'=>
				'SELECT table_name FROM information_schema.tables '.
				'WHERE '.
					(preg_match('/pgsql/',$this->ENGINE)?
						'table_catalog':'table_schema').
						'=\''.$this->DBNAME.'\' AND '.
					'table_name=\''.$table.'\''
		);
		foreach ($cmd as $key=>$val)
			if (preg_match('/'.$key.'/',$this->ENGINE))
				return $this->exec($val);
		return FALSE;
	}

	/**
		Retrieve schema of SQL table
			@return array|FALSE
			@param $table string
			@param $ttl int
	**/
	function schema($table,$ttl=0) {
		// Supported engines
		$cmd=array(
			'sqlite2?'=>array(
				'PRAGMA table_info('.$table.');',
				'name','type','notnull',0,'pk',1),
			'mysql'=>array(
				'SHOW columns FROM `'.$this->DBNAME.'`.'.$table.';',
				'Field','Type','Null','YES','Key','PRI'),
			'mssql|sybase|dblib|pgsql|ibm|odbc'=>array(
				'SELECT '.
					'c.column_name AS field,'.
					'c.data_type AS type,'.
					'c.is_nullable AS nullable,'.
					't.constraint_type AS pkey '.
				'FROM information_schema.columns AS c '.
				'LEFT OUTER JOIN '.
					'information_schema.key_column_usage AS k ON '.
						'c.table_name=k.table_name AND '.
						'c.column_name=k.column_name '.
						($this->DBNAME?
							('AND '.
							($this->ENGINE=='pgsql'?
								'c.table_catalog=k.table_catalog':
								'c.table_schema=k.table_schema').' '):'').
				'LEFT OUTER JOIN '.
					'information_schema.table_constraints AS t ON '.
						'k.table_name=t.table_name AND '.
						'k.constraint_name=t.constraint_name '.
						($this->DBNAME?
							('AND '.
							($this->ENGINE=='pgsql'?
								'k.table_catalog=t.table_catalog':
								'k.table_schema=t.table_schema').' '):'').
				'WHERE '.
					'c.table_name="'.$table.'"'.
					($this->DBNAME?
						('AND '.
							($this->ENGINE=='pgsql'?
							'c.table_catalog':'c.table_schema').
							'="'.$this->DBNAME.'"'):'').
				';',
				'field','type','nullable','YES','pkey','PRIMARY KEY')
		);
		foreach ($cmd as $key=>$val)
			if (preg_match('/'.$key.'/',$this->ENGINE)) {
				$rows=array();
				foreach ($this->exec($val[0],NULL,$ttl) as $row)
					$rows[$row[$val[1]]]=array(
						'type'=>
							preg_match('/int|bool/i',$row[$val[2]],$parts)?
							constant('\PDO::PARAM_'.strtoupper($parts[0])):
							\PDO::PARAM_STR,
						'nullable'=>$row[$val[3]]==$val[4],
						'pkey'=>$row[$val[5]]==$val[6]
					);
				}
				return $rows;
		return FALSE;
	}

	/**
		Instantiate class
			@return void
			@param $dsn string
			@param $user string
			@param $pw string
			@param $options array
	**/
	function __construct($dsn,$user=NULL,$pw=NULL,array $options=NULL) {
		$this->ENGINE=strstr($dsn,':',TRUE);
		if (preg_match('/^.+?(?:dbname|database)=(.+?)(?=;|$)/i',$dsn,$parts))
			$this->DBNAME=$parts[1];
		if (!$options)
			$options=array();
		$options+=array(\PDO::ATTR_EMULATE_PREPARES=>FALSE);
		if (preg_match('/^mysql:/',$dsn) && extension_loaded('pdo_mysql'))
			$options+=array(\PDO::MYSQL_ATTR_INIT_COMMAND=>'SET NAMES utf8;');
		parent::__construct($dsn,$user,$pw,$options);
	}

}

namespace DB\SQL;

//! PDO mapper
class Map extends \Cursor {

	//@{ Messages
	const
		ERROR_Adhoc='Unable to process ad hoc field %s';
	//@}

	private
		//! SQL wrapper
		$DB,
		//! SQL table
		$TABLE,
		//! Current record
		$FIELDS=array(),
		//! Adhoc fields
		$ADHOC=array();

	/**
		Convert PDO type to ordinary PHP type
			@return string
			@param $pdo string
	**/
	function type($pdo) {
		switch ($pdo) {
			case \PDO::PARAM_NULL:
				return 'unset';
			case \PDO::PARAM_INT:
				return 'int';
			case \PDO::PARAM_BOOL:
				return 'bool';
			case \PDO::PARAM_STR:
				return 'string';
		}
	}

	/**
		Assign value to field
			@return scalar|FALSE
			@param $key string
			@param $val scalar
	**/
	function set($key,$val) {
		if (array_key_exists($key,$this->FIELDS)) {
			$val=eval('return ('.
				$this->type($this->FIELDS[$key]['type']).')'.
				\Base::instance()->export($val).';');
			$this->FIELDS[$key]['changed']=
				($this->FIELDS[$key]['value']!=$val);
			return $this->FIELDS[$key]['value']=$val;
		}
		trigger_error(sprintf(self::ERROR_Field,$key));
		return FALSE;
	}

	/**
		Retrieve value of field
			@return scalar|FALSE
			@param $key string
	**/
	function get($key) {
		if (array_key_exists($key,$this->FIELDS))
			return $this->FIELDS[$key]['value'];
		elseif (array_key_exists($key,$this->ADHOC))
			return $this->ADHOC[$key]['value'];
		trigger_error(sprintf(self::ERROR_Field,$key));
		return FALSE;
	}

	/**
		Build query string and execute
			@return array
			@param $fields string
			@param $filter string|array
			@param $options array
	**/
	function select($fields,$filter=NULL,array $options=NULL) {
		if (!$options)
			$options=array();
		$options+=array(
			'group'=>NULL,
			'order'=>NULL,
			'offset'=>0,
			'limit'=>0
		);
		$sql='SELECT '.$fields.' FROM '.$this->TABLE;
		$args=array();
		if ($filter) {
			if (is_array($filter))
				list($filter,$params)=$filter;
			$args+=is_array($params)?$params:array(1=>$params);
			$sql.=' WHERE '.$filter;
		}
		if ($options['group']) {
			if (is_array($options['group']))
				list($options['group'],$params)=$options['group'];
			$args+=is_array($params)?$params:array($params);
			$sql.=' GROUP BY '.$options['group'];
		}
		if ($options['order'])
			$sql.=' ORDER BY '.$options['order'];
		if ($options['offset'])
			$sql.=' OFFSET '.$options['offset'];
		if ($options['limit'])
			$sql.=' LIMIT '.$options['limit'];
		$out=$this->DB->exec($sql.';',$args);
		$fw=\Base::instance();
		foreach ($out as &$rec) {
			foreach ($rec as $key=>&$field) {
				if (array_key_exists($key,$this->FIELDS))
					$field=eval('return ('.
						$this->type($this->FIELDS[$key]['type']).')'.
						$fw->export($field).';');
				elseif (array_key_exists($key,$this->ADHOC))
					$this->ADHOC[$key]['value']=$field;
				unset($field);
			}
			unset($rec);
		}
		return $out;
	}

	/**
		Return records that match criteria
			@return array
			@param $filter string|array
			@param $options array
	**/
	function find($filter=NULL,array $options=NULL) {
		if (!$options)
			$options=array();
		$options+=array(
			'group'=>NULL,
			'order'=>NULL,
			'offset'=>0,
			'limit'=>0
		);
		$adhoc='';
		foreach ($this->ADHOC as $key=>$field)
			$adhoc.=','.$field['expr'].' AS '.$key;
		return $this->select('*'.$adhoc,$filter,$options);
	}

	/**
		Count records that match criteria
			@return int
			@param $filter string|array
	**/
	function count($filter=NULL) {
		list($out)=$this->select('COUNT(*) AS _count',$filter);
		return $out['_count'];
	}

	/**
		Return first record that matches criteria
			@return array|FALSE
			@param $filter string|array
			@param $options array
	**/
	function load($filter=NULL,array $options=NULL) {
		if (!$options)
			$options=array();
		$options+=array(
			'group'=>NULL,
			'order'=>NULL,
			'offset'=>0,
			'limit'=>0
		);
		if ($out=parent::load($filter,$options))
			$this->skip(0);
		return $out;
	}

	/**
		Return record at specified offset using criteria of previous
		load() call
			@return array
			@param $ofs int
	**/
	function skip($ofs=1) {
		if ($out=parent::skip($ofs)) {
			foreach ($this->FIELDS as $key=>&$field) {
				$field['value']=$out[$key];
				$field['changed']=FALSE;
				if ($field['pkey'])
					$field['previous']=$out[$key];
				unset($field);
			}
			foreach ($this->ADHOC as $key=>&$field) {
				$field['value']=$out[$key];
				unset($field);
			}
		}
		return $out;
	}

	/**
		Insert new record
			@return array
	**/
	function insert() {
		$args=array();
		$ctr=0;
		$fields='';
		$values='';
		foreach ($this->FIELDS as $key=>$field)
			if ($field['changed']) {
				$fields.=($ctr?',':'').
					($this->DB->ENGINE=='mysql'?('`'.$key.'`'):$key);
				$values.=($ctr?',':'').'?';
				$args[$ctr+1]=array($field['value'],$field['type']);
				$ctr++;
			}
		if ($fields)
			$this->DB->exec(
				'INSERT INTO '.$this->TABLE.' ('.$fields.') '.
				'VALUES ('.$values.');',$args
			);
		$out=array();
		$inc=array();
		foreach ($this->FIELDS as $key=>$field) {
			$out+=array($key=>$field['value']);
			if ($field['pkey']) {
				$field['previous']=$field['value'];
				if ($field['type']==\PDO::PARAM_INT &&
					is_null($field['value']))
					$inc[]=$key;
			}
		}
		parent::reset();
		$ctr=count($inc);
		if ($ctr>1)
			return $out;
		elseif ($ctr) {
			// Reload to obtain value of auto-increment field
			return $this->load(array($inc[0].'=?',eval('return ('.
				$this->type($this->FIELDS[$inc[0]]['type']).')'.
				\Base::instance()->export($this->DB->
				lastinsertid($this->DB->ENGINE=='pgsql'?$inc[0]:NULL)).';')));
		}
	}

	/**
		Update record
			@return array
	**/
	function update() {
		$args=array();
		$ctr=0;
		$pairs='';
		$filter='';
		foreach ($this->FIELDS as $key=>$field)
			if ($field['changed']) {
				$pairs.=($pairs?',':'').
					($this->DB->ENGINE=='mysql'?('`'.$key.'`'):$key).'=?';
				$args[$ctr+1]=array($field['value'],$field['type']);
				$ctr++;
			}
		foreach ($this->FIELDS as $key=>$field)
			if ($field['pkey']) {
				$filter.=($filter?' AND ':'').$key.'=?';
				$args[$ctr+1]=array($field['previous'],$field['type']);
				$ctr++;
			}
		if ($pairs) {
			$sql='UPDATE '.$this->TABLE.' SET '.$pairs;
			if ($filter)
				$sql.=' WHERE '.$filter;
			return $this->DB->exec($sql.';',$args);
		}
	}

	/**
		Delete record
			@return int
	**/
	function erase() {
		$args=array();
		$ctr=0;
		$filter='';
		foreach ($this->FIELDS as $key=>$field)
			if ($field['pkey']) {
				$filter.=($filter?' AND ':'').$key.'=?';
				$args[$ctr+1]=array($field['previous'],$field['type']);
				$ctr++;
			}
		parent::reset();
		return $this->DB->
			exec('DELETE FROM '.$this->TABLE.' WHERE '.$filter.';',$args);
	}

	/**
		Reset cursor
			@return void
	**/
	function reset() {
		foreach ($this->FIELDS as &$field) {
			$field['value']=NULL;
			$field['changed']=FALSE;
			if ($field['pkey'])
				$field['previous']=NULL;
			unset($field);
		}
		foreach ($this->ADHOC as &$field) {
			$field['value']=NULL;
			unset($field);
		}
		parent::reset();
	}

	/**
		Create an ad hoc field
			@return void
			@param $key string
			@param $expr string
	**/
	function def($key,$expr) {
		if (array_key_exists($key,$this->FIELDS)) {
			trigger_error(sprintf(self::ERROR_Adhoc,$key));
			return;
		}
		$this->ADHOC[$key]=array('expr'=>'('.$expr.')','value'=>NULL);
	}

	/**
		Destroy an ad hoc field
			@return void
			@param $key string
	**/
	function undef($key) {
		if (array_key_exists($key,$this->FIELDS) ||
			!array_key_exists($key,$this->ADHOC)) {
			trigger_error(sprintf(self::ERROR_Adhoc,$key));
			return;
		}
		unset($this->ADHOC[$key]);
	}

	/**
		Return TRUE if ad hoc field is defined
			@return bool
			@param $key string
	**/
	function isdef($key) {
		return array_key_exists($key,$this->ADHOC);
	}

	/**
		Instantiate class
			@return void
			@param $db object
			@param $table string
			@param $ttl int
	**/
	function __construct(\DB $db,$table,$ttl=60) {
		$this->DB=$db;
		$this->TABLE=$table;
		$this->FIELDS=$db->schema($table,$ttl);
		$this->reset();
	}

}

//! Custom session handler
class Session {

	private
		//! SQL wrapper
		$DB,
		//! SQL table
		$TABLE;

	function open($path,$name) {
		if (!$this->DB->exists($this->TABLE))
			$this->DB->exec(
				'CREATE TABLE '.
					(preg_match('/sqlite2?/',$this->DB->ENGINE)?
						'':($this->DB->DBNAME.'.')).$this->TABLE.' ('.
					'session_id VARCHAR(40),'.
					'data LONGTEXT,'.
					'stamp INTEGER,'.
					'PRIMARY KEY(session_id)'.
				');'
			);
		register_shutdown_function('session_commit');
		return TRUE;
	}

	function close() {
		return TRUE;
	}

	function read($id) {
		$session=$this->DB->map($this->TABLE);
		$session->load(array('session_id=:id',array(':id'=>$id)));
		return $session->dry()?FALSE:$session->data;
	}

	function write($id,$data) {
		$session=$this->DB->map($this->TABLE);
		$session->load(array('session_id=:id',array(':id'=>$id)));
		$session->session_id=$id;
		$session->data=$data;
		$session->stamp=time();
		$session->save();
		return TRUE;
	}

	function delete($id) {
		$session=$this->DB->map($this->TABLE);
		$session->erase(array('session_id=:id',array(':id'=>$id)));
		return TRUE;
	}

	function cleanup($max) {
		$session=$this->DB->map($this->TABLE);
		$session->erase('stamp+'.$max.'<'.time());
		return TRUE;
	}

	function __construct(\DB $db,$table='sessions') {
		$this->DB=$db;
		$this->TABLE=$table;
		$args=array();
		foreach (explode('|','open|close|read|write|delete|cleanup') as $func)
			$args[]=array($this,$func);
		call_user_func_array('session_set_save_handler',$args);
	}

}
