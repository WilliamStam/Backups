<?php

namespace DB;

//! MongoDB wrapper
class Mongo extends \MongoDB {

	private
		//! Mongo connection
		$CONNECT,
		//! Database name
		$DBNAME;

	/**
		Instantiate class
			@return void
			@param $dsn string
			@param $dbname string
			@param $options array
	**/
	function __construct($dsn,$dbname,array $options=NULL) {
		if (!$options)
			$options=array();
		parent::__construct(
			$this->CONNECT=new \Mongo($dsn,$options),
			$this->DBNAME=$dbname
		);
	}

}

namespace DB\Mongo;

//! MongoDB mapper
class Map extends \Cursor {

	private
		//! MongoDB wrapper
		$DB,
		//! Mongo collection
		$COLLECTION,
		//! Mongo document
		$DOCUMENT=array();

	/**
		Return TRUE if field is defined
			@return bool
			@param $key string
	**/
	function exists($key) {
		return array_key_exists($key,$this->DOCUMENT);
	}

	/**
		Assign value to field
			@return scalar|FALSE
			@param $key string
			@param $val scalar
	**/
	function set($key,$val) {
		return $this->DOCUMENT[$key]=$val;
	}

	/**
		Retrieve value of field
			@return scalar|FALSE
			@param $key string
	**/
	function get($key) {
		if (array_key_exists($key,$this->DOCUMENT))
			return $this->DOCUMENT[$key];
		trigger_error(sprintf(self::ERROR_Field,$key));
		return FALSE;
	}

	/**
		Delete field
			@return void
			@param $key string
	**/
	function clear($key) {
		unset($this->DOCUMENT[$key]);
	}

	/**
		Build query and execute
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
		if ($options['group']) {
			$this->DB->selectcollection(
				$temp=$_SERVER['SERVER_NAME'].'.'.
					\Base::instance()->hash(uniqid()).'.tmp');
			$this->DB->$temp->batchinsert(
				$this->COLLECTION->group(
					$options['group']['keys'],
					$options['group']['initial'],
					$options['group']['reduce'],
					array(
						'condition'=>array(
							$filter,
							$options['group']['finalize']
						)
					)
				),
				array('safe'=>TRUE)
			);
			$filter=array();
			$collection=$this->DB->$temp;
		}
		else {
			$filter=$filter?:array();
			$collection=$this->COLLECTION;
		}
		$cursor=$collection->find($filter,$fields?:array());
		if ($options['order'])
			$cursor=$cursor->sort($options['order']);
		if ($options['offset'])
			$cursor=$cursor->skip($options['offset']);
		if ($options['limit'])
			$cursor=$cursor->limit($options['limit']);
		if ($options['group'])
			$this->DB->$temp->drop();
		$out=iterator_to_array($cursor,FALSE);
		foreach ($out as &$doc)
			foreach ($doc as &$val)
				if (is_array($val))
					$val=json_decode(json_encode($val));
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
		return $this->select(NULL,$filter,$options);
	}

	/**
		Count records that match criteria
			@return int
			@param $filter string|array
	**/
	function count($filter=NULL) {
		return $this->COLLECTION->count($filter);
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
			$this->DOCUMENT=$out;
		return $out;
	}

	/**
		Return record at specified offset using criteria of previous
		load() call
			@return array
			@param $ofs int
	**/
	function skip($ofs=1) {
		return $this->DOCUMENT=parent::skip($ofs);
	}

	/**
		Insert new record
			@return array
	**/
	function insert() {
		$this->COLLECTION->insert($this->DOCUMENT);
		parent::reset();
		return $this->DOCUMENT;
	}

	/**
		Update record
			@return array
	**/
	function update() {
		$this->COLLECTION->update(
			array('_id'=>$this->DOCUMENT['_id']),$this->DOCUMENT);
		return $this->DOCUMENT;
	}

	/**
		Delete record
			@return int
	**/
	function erase() {
		$this->COLLECTION->remove(array('_id'=>$this->DOCUMENT['_id']));
	}

	/**
		Reset cursor
			@return void
	**/
	function reset() {
		$this->DOCUMENT=array();
		parent::reset();
	}

	/**
		Instantiate class
			@return void
			@param $db object
			@param $collection string
	**/
	function __construct(\DB $db,$collection) {
		$this->DB=$db;
		$this->COLLECTION=$db->selectcollection($collection);
		$this->reset();
	}

}

//! Custom session handler
class Session {

	private
		//! MongoDB wrapper
		$DB,
		//! Mongo collection
		$COLLECTION;

	function open($path,$name) {
		register_shutdown_function('session_commit');
		return TRUE;
	}

	function close() {
		return TRUE;
	}

	function read($id) {
		$session=$this->DB->map($this->COLLECTION);
		$session->load(array('session_id'=>$id));
		return $session->dry()?FALSE:$session->data;
	}

	function write($id,$data) {
		$session=$this->DB->map($this->COLLECTION);
		$session->load(array('session_id'=>$id));
		$session->session_id=$id;
		$session->data=$data;
		$session->stamp=time();
		$session->save();
		return TRUE;
	}

	function delete($id) {
		$session=$this->DB->map($this->COLLECTION);
		$session->erase(array('session_id'=>$id));
		return TRUE;
	}

	function cleanup($max) {
		$session=$this->DB->map($this->COLLECTION);
		$session->erase(array('$where'=>'this.stamp+'.$max.'<'.time()));
		return TRUE;
	}

	function __construct(\DB $db,$collection='sessions') {
		$this->DB=$db;
		$this->COLLECTION=$collection;
		$args=array();
		foreach (explode('|','open|close|read|write|delete|cleanup') as $func)
			$args[]=array($this,$func);
		call_user_func_array('session_set_save_handler',$args);
	}

}
