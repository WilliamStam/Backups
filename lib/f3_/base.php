<?php

//! Container for object instances
class Registry {

	private static
		//! Object catalog
		$DATA=array();

	/**
		Return TRUE if object exists in catalog
			@return bool
			@param $class string
	**/
	function bound($class) {
		return isset(self::$DATA[$class]);
	}

	/**
		Add object to catalog
			@return void
			@param $class string
			@param $obj
	**/
	function bind($class,$obj) {
		self::$DATA[$class]=$obj;
	}

	/**
		Remove object from catalog
			@return void
			@param $class string
	**/
	function unbind($class) {
		unset(self::$DATA[$class]);
	}

	/**
		Return class instance
			@return object
			@param $class string
	**/
	static function instance($class=NULL) {
		if (!$class)
			$class=get_called_class();
		return isset(self::$DATA[$class])?
			self::$DATA[$class]:new $class;
	}

	/**
		Instantiate class
			@return void
	**/
	function __construct() {
		$fw=Base::instance();
		if ($fw->bound(__CLASS__))
			return;
		$fw->bind(__CLASS__,$this);
	}

}

//! Generic adapter/PHP magic wrapper
class Agent extends Registry {

	//@{ Messages
	const
		ERROR_Class='Undefined class %s',
		ERROR_Method='Undefined method %s';
	//@}

	private
		//! Class instance
		$INSTANCE,
		//! Extensions
		$EXT=array();

	/**
		Define function as an extension of target class
			@return void
			@param $name string
			@param $func callback
	**/
	function extend($name,$func) {
		$this->EXT[$name]=$func;
	}

	/**
		Forward calls to target class
			@return mixed
			@param $func string
			@param $args array
	**/
	function __call($func,array $args) {
		if (method_exists($self=$this->INSTANCE?:$this,$func))
			return call_user_func_array(array($self,$func),$args);
		if (isset($this->EXT[$func]))
			return call_user_func_array($this->EXT[$func],$args);
		trigger_error(sprintf(self::ERROR_Method,
			get_class($self).'::'.$func.'()'));
		return FALSE;
	}

	/**
		Return TRUE if property exists
			@return bool
			@param $key string
	**/
	function __isset($key) {
		return method_exists($self=$this->INSTANCE?:$this,'exists')?
			$self->exists($key):isset($self->$key);
	}

	/**
		Bind value to property
			@return mixed
			@param $key string
			@param $val mixed
	**/
	function __set($key,$val) {
		return method_exists($self=$this->INSTANCE?:$this,'set')?
			$self->set($key,$val):$self->$key=$val;
	}

	/**
		Return property value
			@return mixed
			@param $key
	**/
	function __get($key) {
		return method_exists($self=$this->INSTANCE?:$this,'get')?
			$self->get($key):$self->$key;
	}

	/**
		Clear property
			@return void
			@param $key string
	**/
	function __unset($key) {
		return method_exists($self=$this->INSTANCE?:$this,'clear')?
			$self->clear($key):call_user_func('unset',$self->$key);
	}

	/**
		Class constructor
			@return void
			@param $obj object
	**/
	function __construct($obj=NULL) {
		$this->INSTANCE=$obj?:$this;
		parent::__construct();
	}

}

//! Base structure
class Base extends Agent {

	//@{
	const
		PACKAGE='Fat-Free Framework',
		VERSION='3.0 Rewired';
	//@}

	//@{ Messages
	const
		ERROR_Apache='Apache rewrite_module is disabled',
		ERROR_Pattern='Invalid routing pattern %s';
	//@}

	//@{ HTTP status codes (RFC 2616)
	const
		HTTP_100='Continue',
		HTTP_101='Switching Protocols',
		HTTP_200='OK',
		HTTP_201='Created',
		HTTP_202='Accepted',
		HTTP_203='Non-Authorative Information',
		HTTP_204='No Content',
		HTTP_205='Reset Content',
		HTTP_206='Partial Content',
		HTTP_300='Multiple Choices',
		HTTP_301='Moved Permanently',
		HTTP_302='Found',
		HTTP_303='See Other',
		HTTP_304='Not Modified',
		HTTP_305='Use Proxy',
		HTTP_307='Temporary Redirect',
		HTTP_400='Bad Request',
		HTTP_401='Unauthorized',
		HTTP_402='Payment Required',
		HTTP_403='Forbidden',
		HTTP_404='Not Found',
		HTTP_405='Method Not Allowed',
		HTTP_406='Not Acceptable',
		HTTP_407='Proxy Authentication Required',
		HTTP_408='Request Timeout',
		HTTP_409='Conflict',
		HTTP_410='Gone',
		HTTP_411='Length Required',
		HTTP_412='Precondition Failed',
		HTTP_413='Request Entity Too Large',
		HTTP_414='Request-URI Too Long',
		HTTP_415='Unsupported Media Type',
		HTTP_416='Requested Range Not Satisfiable',
		HTTP_417='Expectation Failed',
		HTTP_500='Internal Server Error',
		HTTP_501='Not Implemented',
		HTTP_502='Bad Gateway',
		HTTP_503='Service Unavailable',
		HTTP_504='Gateway Timeout',
		HTTP_505='HTTP Version Not Supported';

	const
		//! Mapped PHP globals
		GLOBALS='GET|POST|COOKIE|REQUEST|SESSION|FILES|SERVER|ENV',
		//! HTTP verbs
		VERBS='GET|HEAD|POST|PUT|DELETE|OPTIONS|TRACE|CONNECT',
		//! Read-only hive keys
		READONLY='BASE|ERROR|LOADED|VERB|PACKAGE|PARAMS|SCHEME|URI|VERSION';

	private static
		//! Symbol table
		$HIVE=array(),
		//! NULL reference
		$NULL;

	/**
		Generate Base36/CRC32 hash code
			@return string
			@param $str string
	**/
	function hash($str) {
		return str_pad(base_convert(
			sprintf('%u',crc32($str)),10,36),7,'0',STR_PAD_LEFT);
	}

	/**
		Convert backslashes to slashes for normalizing Windows filesystem
		elements and/or referencing namespaced classes in subdirectories
			@return string
			@param $str string
	**/
	function fixslashes($str) {
		return $str?strtr($str,'\\','/'):$str;
	}

	/**
		Convert slashes to backslashes
			@return string
			@param $str string
	**/
	function revslashes($str) {
		return $str?strtr($str,'/','\\'):$str;
	}

	/**
		Convert PHP expression/value to compressed exportable string
			@return string
			@param $arg mixed
	**/
	function export($arg) {
		switch (gettype($arg)) {
			case 'object':
				return method_exists($arg,'__set_state')?
					var_export($arg,TRUE):
					(method_exists($arg,'__tostring')?
					(string)$arg:('{'.get_class($arg).'}'));
			case 'array':
				$str='';
				foreach ($arg as $key=>$val)
					$str.=($str?',':'').
						$this->export($key).'=>'.$this->export($val);
				return 'array('.$str.')';
			default:
				return var_export($arg,TRUE);
		}
	}

	/**
		Retrieve string representation of value
			@return string
			@param $arg mixed
	**/
	function serialize($arg) {
		switch (self::$HIVE['SERIALIZER']) {
			case 'igbinary':
				return igbinary_serialize($arg);
			case 'json':
				return json_encode($arg);
			default:
				return serialize($arg);
		}
	}

	/**
		Restore value from string representation
			@return mixed
			@param $str string
	**/
	function unserialize($str) {
		switch (self::$HIVE['SERIALIZER']) {
			case 'igbinary':
				return igbinary_unserialize($str);
			case 'json':
				return json_decode($str);
			default:
				return unserialize($str);
		}
	}

	/**
		Flatten array values and return as CSV string
			@return string
			@param $args array
	**/
	function csv(array $args) {
		return implode(',',array_map('stripcslashes',
			array_map(array($this,'export'),$args)));
	}

	/**
		Split pipe-, semi-colon, or comma-separated string
			@return array
			@param $str string
	**/
	function split($str) {
		return array_map('trim',
			preg_split('/[|;,]/',$str,0,PREG_SPLIT_NO_EMPTY));
	}

	/**
		Convert engineering-notated string to bytes
			@return int
			@param $str string
	**/
	function bytes($str) {
		$greek='KMGT';
		$exp=strpbrk($str,$greek);
		return pow(1024,strpos($greek,$exp)+1)*(int)$str;
	}

	/**
		Normalize array notation
			@return string
			@param $key string
	**/
	function remix($key) {
		$regex='/\[\s*[\'"]?(.+?)[\'"]?\s*\]|\.|(->)/';
		$out='';
		$obj=FALSE;
		foreach (preg_split($regex,$key,NULL,
			PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE) as $part)
			if ($part=='->')
				$obj=TRUE;
			elseif ($obj) {
				$obj=FALSE;
				$out.='->'.$out;
			}
			else
				$out.='['.$this->export($part).']';
		return $out;
	}

	/**
		Get hive key reference/contents; Adds non-existent hive keys and
		components (array elements/object properties) by default
			@return ref|mixed
			@param $key string
			@param $add bool
	**/
	function &ref($key,$add=TRUE) {
		$parts=preg_split('/\[\s*[\'"]?(.+?)[\'"]?\s*\]|\.|(->)/',
			$key,NULL,PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
		// Referencing SESSION element auto-starts a session
		if (preg_match('/^SESSION\b/',$key) && !session_id()) {
			$this->call('session_set_cookie_params',self::$HIVE['JAR']);
			session_start();
			// Sync with PHP global
			self::$HIVE['SESSION']=&$_SESSION;
		}
		if ($add)
			$var=&self::$HIVE;
		else
			$var=self::$HIVE;
		$obj=FALSE;
		foreach ($parts as $part)
			if ($part=='->')
				$obj=TRUE;
			elseif ($add) {
				if ($obj) {
					if (!is_object($var))
						$var=new stdClass;
					if (isset($var->$part))
						$var->$part=NULL;
					$var=&$var->$part;
					$obj=FALSE;
				}
				else
					$var=&$var[$part];
			}
			elseif ($obj && isset($var->$part)) {
				$var=$var->$part;
				$obj=FALSE;
			}
			elseif (is_array($var) && isset($var[$part]))
				$var=$var[$part];
			else
				return self::$NULL;
		if ($add &&
			preg_match('/^(GET|POST|COOKIE)\b(.+)/',$key,$parts)) {
			if ($parts[1]=='COOKIE' && PHP_SAPI!='cli' && !headers_sent())
				$this->call('setcookie',
					array('name'=>$parts[1],'value'=>$var)+
					self::$HIVE['JAR']);
			$req=&$this->ref('REQUEST'.$parts[2],$key);
			$req=$var;
		}
		return $var;
	}

	/**
		Return TRUE if hive key is not empty
			@return bool
			@param $key string
	**/
	function exists($key) {
		$ref=&$this->ref($key,FALSE);
		return isset($ref);
	}

	/**
		Bind value to hive key
			@return mixed
			@param $key string
			@param $val mixed
	**/
	function set($key,$val) {
		if (!is_string($key))
			return FALSE;
		$parts=preg_split('/\[\s*[\'"]?(.+?)[\'"]?\s*\]|\./',$key,
			NULL,PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
		if (preg_match('/^('.self::READONLY.')\b/',$parts[0]))
			return FALSE;
		switch ($parts[0]) {
			case 'CACHE':
				$val=Cache::instance()->load($val);
				break;
			case 'COOKIE':
				if (PHP_SAPI!='cli' && !headers_sent()) {
					if (isset($parts[1]))
						$val=array($parts[1]=>$val);
					if (is_array($val))
						// Assign array
						foreach ($val as $subk=>$subv)
							$this->call('setcookie',
								array('name'=>$subk,'value'=>$subv)+
								self::$HIVE['JAR']);
					else
						return FALSE;
				}
				break;
			case 'ENCODING':
				ini_set('default_charset',$val);
				break;
			case 'LANGUAGE':
				// Load dictionaries
				$val=ICU::instance()->load($val);
				break;
			case 'TZ':
				date_default_timezone_set($val);
				break;
		}
		$ref=&$this->ref($key);
		$ref=$val;
		return $val;
	}

	/**
		Retrieve contents of hive key
			@return mixed
			@param $key string
			@param $args string|array
	**/
	function get($key,$args=NULL) {
		$out=$this->ref($key,FALSE);
		return is_string($out) && self::$HIVE['LANGUAGE']?
			// Format string according to locale rules
			ICU::instance()->format($out,$args):
			$out;
	}

	/**
		Unset hive key
			@return void
			@param $key string
	**/
	function clear($key) {
		if ($key=='SESSION') {
			if (!session_id()) {
				$this->call('session_set_cookie_params',self::$HIVE['JAR']);
				session_start();
			}
			session_destroy();
			self::$HIVE['SESSION']=NULL;
			return;
		}
		$mix=$this->remix($key);
		if (preg_match('/^('.self::GLOBALS.')(.*)/',$key,$parts)) {
			if ($parts[2])
				eval('unset($GLOBALS'.
					preg_replace('/^\["(.+?)"\]/','["_\1"]',$mix).
				');');
			else
				self::$HIVE[$key]=array();
		}
		else
			eval(ctype_upper($key)?
				('self::$HIVE'.$mix.'=NULL;'):
				('unset(self::$HIVE'.$mix.');'));
	}

	/**
		Load configuration file
			@return void
			@param $file string
	**/
	function config($file) {
		if (!is_file($temp=self::$HIVE['TEMP'].
			$this->hash(__DIR__).'.'.$this->hash($file).'.php') ||
			filemtime($file)>filemtime($temp)) {
			// Load the .ini file
			$cfg=array();
			$sec='';
			if ($ini=file($file))
				foreach ($ini as $line) {
					preg_match('/^\s*(?:(;)|\[(.+)\]|(.+?)\s*=\s*(.+))/',
						$line,$parts);
					if (isset($parts[1]) && $parts[1])
						// Comment
						continue;
					elseif (isset($parts[2]) && $parts[2])
						// Section
						$sec=strtolower($parts[2]);
					elseif (isset($parts[3]) && $parts[3]) {
						// Key-value pair
						$csv=array_map(
							function($val) {
								$val=trim($val);
								return is_numeric($val) || defined($val)?
									eval('return '.$val.';'):$val;
							},
							str_getcsv($parts[4])
						);
						$cfg[$sec=$sec?:'globals'][$parts[3]]=
							count($csv)>1?$csv:$csv[0];
					}
				}
			$plan=array('globals'=>'set','maps'=>'map','routes'=>'route');
			ob_start();
			foreach ($cfg as $sec=>$pairs)
				if (isset($plan[$sec]))
					foreach ($pairs as $key=>$val)
						echo '$fw->'.$plan[$sec].'('.$this->export($key).','.
							(is_array($val) && $sec!='globals'?
								$this->csv($val):$this->export($val)).');'.
							"\n";
			// Save re-tagged file
			if (!is_dir(self::$HIVE['TEMP']) &&
				!@mkdir(self::$HIVE['TEMP'],0755,TRUE))
				return FALSE;
			$this->write($temp,
				'<?php'."\n".'$fw=Base::instance();'."\n".ob_get_clean());
		}
		$this->sandbox($temp);
	}

	/**
		Assign handler to route pattern
			@return void
			@param $pattern string
			@param $handler callback
			@param $options array
	**/
	function route($pattern,$handler,array $options=NULL) {
		if (is_bool(strpos($pattern,' '))) {
			trigger_error(sprintf(self::ERROR_Pattern,$pattern));
			return;
		}
		list($verbs,$url)=preg_split('/\s+/',$pattern,2,PREG_SPLIT_NO_EMPTY);
		foreach ($this->split($verbs) as $verb) {
			if (!preg_match('/'.self::VERBS.'/',$verb))
				return $this->error(501);
			// Default route options
			if (!$options)
				$options=array();
			self::$HIVE['ROUTES'][$url][strtoupper($verb)]=
				array(
					$handler,
					$options+array(
						'allow'=>NULL,
						'hotlink'=>TRUE,
						'throttle'=>0,
						'ttl'=>0
					)
				);
		}
	}

	/**
		Construct routes by mapping URL to class (class must implement
		methods that correspond to HTTP verbs)
			@return void
			@param $url string
			@param $class string
			@param $options array
	**/
	function map($url,$class,array $options=NULL) {
		foreach (explode('|',self::VERBS) as $verb)
			$this->route($verb.' '.$url,
				$class.'->'.strtolower($verb),$options);
	}

	/**
		Send HTTP status header; Return text equivalent of status code
			@return string
			@param $code int
	**/
	function status($code) {
		if (PHP_SAPI!='cli' && !headers_sent())
			header($_SERVER['SERVER_PROTOCOL'].' '.$code);
		return @constant('self::HTTP_'.$code);
	}

	/**
		Send cache metadata to HTTP client
			@return void
			@param $secs int
	**/
	function expire($secs=0) {
		if (PHP_SAPI!='cli' && !headers_sent()) {
			//header('X-Powered-By: '.self::$HIVE['PACKAGE']);
			if ($secs) {
				$time=microtime(TRUE);
				header_remove('Pragma');
				header('Expires: '.gmdate('r',$time+$secs));
				header('Cache-Control: max-age='.ceil($secs));
				header('Last-Modified: '.gmdate('r'));
				$req=getallheaders();
				if (isset($req['If-Modified-Since']) &&
					strtotime($req['If-Modified-Since'])+$secs>$time)
					die($this->status(304));
			}
			else
				header('Cache-Control: no-cache, no-store, must-revalidate');
		}
	}

	/**
		Reroute to specified URI
			@return void
			@param $uri string
	**/
	function reroute($uri) {
		if (PHP_SAPI!='cli' && !headers_sent()) {
			if (session_id())
				session_commit();
			// HTTP redirect
			header('Location: '.(preg_match('/^https?:\/\//',$uri)?
				$uri:(self::$HIVE['BASE'].$uri)));
			die($this->status(self::$HIVE['VERB']=='GET'?301:303));
		}
		$this->mock('GET '.$uri);
	}

	/**
		Process routes based on incoming URI
			@return mixed
	**/
	function run() {
		if (!is_array(self::$HIVE['ROUTES']) || !self::$HIVE['ROUTES'])
			return FALSE;
		// Detailed routes get matched first
		krsort(self::$HIVE['ROUTES']);
		$req=preg_replace('/^'.preg_quote(self::$HIVE['BASE'],'/').
			'\b(.+)/','\1',rawurldecode(self::$HIVE['URI']));
		foreach (self::$HIVE['ROUTES'] as $url=>$route) {
			if (!preg_match('/^'.
				preg_replace(
					'/@(\w+\b)/',
					// Valid URL characters (RFC 1738)
					'(?P<\1>[\w\-\.!~\*\'"(),\s]+)',
					// Wildcard character in URI
					str_replace('\*','(.*)',preg_quote($url,'/'))
				).'\/?(?:\?.*)?$/iu',$req,$args))
				continue;
			if (isset($route[self::$HIVE['VERB']])) {
				list($handler,$options)=$route[self::$HIVE['VERB']];
				if ($options['allow'] &&
					!in_array($this->get('SESSION.role'),
					$this->split($options['allow'])))
					return $this->error(403);
				if (is_bool(strpos($url,'/*')))
					foreach (array_keys($args) as $key)
						if (is_numeric($key) && $key)
							unset($args[$key]);
				self::$HIVE['PARAMS']=$args;
				if (is_string($handler))
					$handler=preg_replace_callback('/@(\w+\b)/',
						function($id) use($args) {
							return isset($args[$id[1]])?$args[$id[1]]:$id[0];
						},
						$handler
					);
				self::$HIVE['BODY']=file_get_contents('php://input');
				$out=NULL;
				$now=microtime(TRUE);
				$this->expire(0);
				if (self::$HIVE['CACHE'] &&
					preg_match('/GET|HEAD/',self::$HIVE['VERB']) &&
					$options['ttl']) {
					$req=getallheaders();
					$cache=Cache::instance();
					$cached=$cache->exists($hash=$this->hash(
						self::$HIVE['VERB'].' '.self::$HIVE['URI']).'.url');
					if ($cached && $cached+$options['ttl']>$now) {
						if (!isset($req['If-Modified-Since']) ||
							$cached>strtotime($req['If-Modified-Since'])) {
							list($out,$headers,$body)=$cache->get($hash);
							if (PHP_SAPI!='cli' && !headers_sent())
								foreach ($headers as $hdr)
									header($hdr);
							$this->expire($cached+$options['ttl']-$now);
						}
						else
							die($this->status(304));
					}
					else {
						$this->expire($options['ttl']);
						ob_start();
						$out=$this->call($handler,array($args),
							'beforeroute,afterroute');
						$body=ob_get_clean();
						if (!error_get_last())
							$cache->set($hash,
								array($out,headers_list(),$body),
								$options['ttl']);
					}
				}
				else {
					ob_start();
					$out=$this->call($handler,array($args),
						'beforeroute,afterroute');
					$body=ob_get_clean();
				}
				if (self::$HIVE['RESPONSE']=$body) {
					$ctr=0;
					foreach (str_split($body,1024) as $part) {
						if (isset($options['throttle']) &&
							$options['throttle']) {
							// Throttle output
							$ctr++;
							if ($ctr/$options['throttle']>
								$elapsed=microtime(TRUE)-$now)
								usleep(1e6*($ctr/$options['throttle']-
									$elapsed));
						}
						if (!self::$HIVE['QUIET'])
							echo $part;
					}
				}
				return $out;
			}
			return $this->error(405);
		}
		return $this->error(404);
	}

	/**
		Execute callback (provides support for 'class->method' format to
		allow lazy-loading of class)
			@return mixed
			@param $func callback
			@param $args array
			@param $hooks string
	**/
	function call($func,array $args=NULL,$hooks='onload') {
		if (is_string($func) &&
			preg_match('/(.+)\s*(->|::)\s*(.+)/s',$func,$parts)) {
			if (!class_exists($parts[1]) ||
				!method_exists($parts[1],'__call') &&
				!method_exists($parts[1],$parts[3]))
				return $this->error(404);
			$func=array($parts[2]=='->'?new $parts[1]:$parts[1],$parts[3]);
		}
		if (!is_callable($func))
			return $this->error(404);
		$oo=FALSE;
		if (is_array($func)) {
			$class=strtolower(is_object($func[0])?
				get_class($func[0]):$func[0]);
			$oo=TRUE;
		}
		$hooks=$this->split($hooks);
		if ($oo && !in_array($class,self::$HIVE['LOADED'])) {
			if (in_array($hook='onload',$hooks) &&
				method_exists($func[0],$hook) &&
				!method_exists($func[0],'__construct'))
				call_user_func(array($func[0],$hook));
			if (in_array($hook='beforeroute',$hooks) &&
				method_exists($func[0],$hook))
				call_user_func(array($func[0],$hook));
		}
		$out=call_user_func_array($func,$args?$args:array());
		if ($oo && !in_array($class,self::$HIVE['LOADED'])) {
			if (in_array($hook='afterroute',$hooks) &&
				method_exists($func[0],$hook))
				call_user_func(array($func[0],$hook));
			// Execute hooks once for each class
			self::$HIVE['LOADED'][]=$class;
		}
		return $out;
	}

	/**
		Compose a mock HTTP request
			@return void
			@param $pattern string
			@param $args array
			@param $run bool
	**/
	function mock($pattern,array $args=NULL,$run=TRUE) {
		list($verb,$url)=preg_split('/\s+/',$pattern,2,PREG_SPLIT_NO_EMPTY);
		$verb=strtoupper($verb);
		$url=parse_url($url);
		$query='';
		if ($args)
			$query.=http_build_query($args);
		$query.=isset($url['query'])?(($query?'&':'').$url['query']):'';
		if ($query) {
			parse_str($query,$GLOBALS['_'.$verb]);
			parse_str($query,$GLOBALS['_REQUEST']);
		}
		self::$HIVE['VERB']=$verb;
		self::$HIVE['URI']=self::$HIVE['BASE'].$url['path'].
			($query?('?'.$query):'');
		if ($run)
			$this->run();
	}

	/**
		Remove HTML tags (except those enumerated) to protect against
		XSS/code injection attacks
			@return mixed
			@param $var mixed
			@param $tags string
	**/
	function scrub(&$var,$tags=NULL) {
		if (is_array($var))
			foreach ($var as &$val)
				$this->scrub($val,$tags);
		elseif (is_string($var) && $tags!='*') {
			$tags='<'.implode('><',$this->split($tags)).'>';
			$var=strip_tags($var,$tags);
		}
	}

	/**
		Call form field handler
			@return mixed
			@param $field string
			@param $func callback
			@param $filter int
			@param $options int
	**/
	function valid(
		$field,$func=NULL,$filter=FILTER_UNSAFE_RAW,$options=NULL) {
		if (isset($_REQUEST[$field])) {
			if (!$options)
				$options=array();
			$val=$filter?
				filter_var($_REQUEST[$field],$options):$_REQUEST[$field];
			if (is_string($func) &&
				preg_match('/(\w+)\s*->\s*(\w+)/s',$func,$parts))
				// Convert class->method syntax
				$func=array(new $parts[1],$parts[2]);
			return $func?call_user_func($func,$val):$val;
		}
		return FALSE;
	}

	/**
		Obtain exclusive locks on specified files and invoke callback;
		Release locks after callback execution
			@return mixed
			@param $files string|array
			@param $func callback
			@param $args array
	**/
	function mutex($files,$func,array $args=NULL) {
		$files=is_array($files)?$files:$this->split($files);
		$shmop=extension_loaded('shmop');
		$handles=array();
		foreach ($files as $file)
			if ($shmop) {
				// Use shared memory
				if ($handle=@shmop_open($ipc=ftok($file,'X'),'a',0644,0)) {
					$data=unpack('f*',shmop_read($handle,0,4));
					if (current($data)+self::$HIVE['MUTEX']<microtime(TRUE)) {
						// Stale lock
						shmop_delete($handle);
						shmop_close($handle);
						unset($handle);
					}
				}
				while (!$handle=@shmop_open($ipc,'n',0644,4))
					usleep(mt_rand(0,100));
				shmop_write($handle,pack('f*',microtime(TRUE)),0);
				$handles[$ipc]=$handle;
				$out=$this->call($func,$args?:array());
				foreach ($handles as $handle) {
					shmop_delete($handle);
					shmop_close($handle);
				}
			}
			else {
				// Use filesystem locks
				if (is_file($lock=self::$HIVE['TEMP'].
					$this->hash(__DIR__).'.'.$this->hash($file).'.lck') &&
					filemtime($lock)+self::$HIVE['MUTEX']<microtime(TRUE))
					// Stale lock
					unlink($lock);
				while (!$handle=@fopen($lock,'x'))
					usleep(mt_rand(0,100));
				$handles[$lock]=$handle;
				$out=$this->call($func,$args?:array());
				foreach ($handles as $lock=>$handle) {
					fclose($handle);
					unlink($lock);
				}
			}
		return $out;
	}

	/**
		Exclusive file read
			@return string
			@param $file string
	**/
	function read($file) {
		return $this->mutex($file,'file_get_contents',array($file));
	}

	/**
		Exclusive file write
			@return int
			@param $file string
	**/
	function write($file) {
		$args=func_get_args();
		array_shift($args);
		if (count($args)<2)
			$args[]=LOCK_EX;
		array_unshift($args,$file);
		return $this->mutex($file,'file_put_contents',$args);
	}

	/**
		Exclusive file delete
			@return bool
			@param $file string
	**/
	function unlink($file) {
		return $this->mutex($file,'unlink',array($file));
	}

	/**
		Provide wrapper for Sandbox class
			@return mixed
			@param $file string
	**/
	function sandbox($file) {
		$box=new Sandbox($file);
		$out=$box->run();
		unset($box);
		return $out;
	}

	/**
		Grab output of PHP script (allows short tags)
			@return string|FALSE
			@param $file string
	**/
	function grab($file) {
		if (!ini_get('short_open_tag')) {
			if (!is_file($temp=self::$HIVE['TEMP'].
				$this->hash(__DIR__).'.'.$this->hash($file).'.php') ||
				filemtime($file)>filemtime($temp)) {
				$text=preg_replace_callback(
					'/<\?(?:\s*(=))?\s*(.+?)\s*\?>/s',
					function($tag) {
						return '<?php '.($tag[1]?'echo ':'').$tag[2].' ?>';
					},
					$orig=$this->read($file)
				);
				if ($text!=$orig) {
					// Save re-tagged file
					if (!is_dir(self::$HIVE['TEMP']) &&
						!@mkdir(self::$HIVE['TEMP'],0755,TRUE))
						return FALSE;
					$this->write($temp,$text);
				}
			}
			$file=$temp;
		}
		ob_start();
		// Run PHP code
		$this->sandbox($file);
		return ob_get_clean();
	}

	/**
		Render user interface; enable/disable CSRF protection
			@return string|FALSE
			@param $file string
			@param $csrf bool
	**/
	function render($file,$csrf=FALSE) {
		foreach ($this->split(self::$HIVE['UI']) as $path)
			if (is_file($view=$this->fixslashes($path.$file))) {
				if ($csrf)
					$this->set('SESSION.csrf',
						$this->hash(__DIR__).'.'.$this->hash(uniqid()));
				return $this->grab($view);
			}
		return FALSE;
	}

	/**
		Display default error page
			@return FALSE
			@param $code int
			@param $text string
			@param $trace array
	**/
	function error($code,$text='',array $trace=NULL) {
		$prior=self::$HIVE['ERROR'];
		$header=$this->status($code);
		$req=self::$HIVE['VERB'].' '.self::$HIVE['URI'];
		if (self::$HIVE['LOG'])
			$this->write(self::$HIVE['LOG'],
				self::$HIVE['LOG'],strftime('%c').
				(PHP_SAPI=='cli'?'':(' ['.$_SERVER['REMOTE_ADDR'].']')).' '.
				($text?:$header.' ('.$req.')')."\n",FILE_APPEND|LOCK_EX);
		else
			error_log($text?:$header.' ('.$req.')');
		$out='';
		if (!$trace)
			$trace=array_slice(debug_backtrace(),1);
		foreach ($trace as $frame) {
			$line='';
			if (isset($frame['file']) && ($frame['file']!=__FILE__ ||
				self::$HIVE['DEBUG']>1) && (!isset($frame['function']) ||
				!preg_match('/^(?:trigger_error|__call|call_user_func)/',
				$frame['function']))) {
				$addr=$this->fixslashes($frame['file']).':'.
					$frame['line'];
				if (isset($frame['class']))
					$line.=$frame['class'].$frame['type'];
				if (isset($frame['function'])) {
					$line.=$frame['function'];
					if (!preg_match('/{.+}/',$frame['function'])) {
						$line.='(';
						if (isset($frame['args']) && $frame['args'])
							$line.=$this->csv($frame['args']);
						$line.=')';
					}
				}
				if (self::$HIVE['LOG'])
					$this->write(self::$HIVE['LOG'],
						self::$HIVE['LOG'],'- '.$line."\n",
						FILE_APPEND|LOCK_EX);
				else
					error_log('- '.$addr.' '.$line);
				$out.='&bull; '.$addr.' '.(self::$HIVE['HIGHLIGHT']?
					preg_replace('/&lt;\?php&nbsp;|'.
					'^<code><span .+?>\n|\n<\/span>\n<\/code>$/','',
					highlight_string('<?php '.$line,TRUE)):$line)."\n";
			}
		}
		self::$HIVE['ERROR']=
			array('code'=>$code,'text'=>$text,'trace'=>$trace);
		if (self::$HIVE['ONERROR'] && is_callable(self::$HIVE['ONERROR']))
			return call_user_func(self::$HIVE['ONERROR']);
		elseif (!$prior && !self::$HIVE['QUIET']) {
			echo '<h1>'.$header.'</h1>'."\n".'<p>'.
				'<i>'.($text?:$req).'</i></p>'."\n";
			if ($out && self::$HIVE['DEBUG'])
				echo '<p>'."\n".nl2br($out).'</p>'."\n";
		}
		exit();
		return FALSE;
	}

	/**
		Autoload undefined class; Recognize both .class.php and .php file
		extensions
			@return void
			@param $class string
	**/
	function autoload($class) {
		foreach ($this->split(
			self::$HIVE['PLUGINS'].';'.self::$HIVE['AUTOLOAD']) as $auto) {
			$ns='';
			$iter=ltrim($class,'\\');
			for (;;) {
				if ($glob=glob($auto.$this->fixslashes($ns).'*')) {
					$grep=preg_grep('/^'.preg_quote($auto,'/').
						implode('[\/\.]',explode('\\',$ns.$iter)).
						'(?:\.class)?\.php/i',$glob);
					if ($file=current($grep)) {
						unset($grep);
						return $this->sandbox($file);
					}
					$parts=explode('\\',$iter,2);
					if (count($parts)>1) {
						$iter=$parts[1];
						$grep=preg_grep('/^'.
							preg_quote($auto.$this->fixslashes($ns).
							$parts[0],'/').'$/i',$glob);
						if ($file=current($grep)) {
							$ns=str_replace('/','\\',preg_replace('/^'.
								preg_quote($auto,'/').'/','',$file)).'\\';
							continue;
						}
						$ns.=$parts[0].'\\';
					}
				}
				break;
			}
		}
	}

	/**
		Instantiate class
			@return void
	**/
	function __construct() {
		$fw=$this;
		if ($fw->bound(__CLASS__))
			return;
		$fw->bind(__CLASS__,$fw);
		ini_set('display_errors',0);
		ini_set('default_charset','UTF-8');
		error_reporting(E_ALL|E_STRICT);
		set_error_handler(
			function($code,$text) use($fw) {
				if (error_reporting())
					$fw->error(500,$text,debug_backtrace());
			}
		);
		set_exception_handler(
			function($obj) use($fw) {
				$fw->error(500,$obj->getmessage(),$obj->gettrace());
			}
		);
		if (function_exists('apache_get_modules') &&
			!in_array('mod_rewrite',apache_get_modules())) {
			trigger_error(self::ERROR_Apache);
			return;
		}
		$root=$_SERVER['DOCUMENT_ROOT']=
			$fw->fixslashes(dirname($_SERVER['SCRIPT_FILENAME']));
		// URL rewrite base (no trailing slash)
		$base=$fw->fixslashes(
			preg_replace('/\/[^\/]+$/','',$_SERVER['SCRIPT_NAME']));
		// Initialize symbol table
		self::$HIVE=array(
			// Autoload path
			'AUTOLOAD'=>'./',
			// Web root folder
			'BASE'=>$base,
			// Cache backend (TRUE:autodetect, FALSE:disable);
			'CACHE'=>FALSE,
			// Stack trace verbosity (0-2)
			'DEBUG'=>0,
			// Default character set
			'ENCODING'=>'UTF-8',
			// Last error
			'ERROR'=>NULL,
			// Highlight stack trace
			'HIGHLIGHT'=>TRUE,
			// Default language (NULL::autodetect)
			'LANGUAGE'=>NULL,
			// Autoloaded classes
			'LOADED'=>array(),
			// Error log (NULL:OS-dependent)
			'LOG'=>NULL,
			// Max mutex lock duration in secs
			'MUTEX'=>60,
			// Custom error handler
			'ONERROR'=>NULL,
			// This package
			'PACKAGE'=>self::PACKAGE,
			// Routing parameters
			'PARAMS'=>array(),
			// Plugins folder
			'PLUGINS'=>str_replace($root.$base.'/','',
				$fw->fixslashes(__DIR__)).'/',
			// Output suppression switch
			'QUIET'=>FALSE,
			// Document root folder
			'ROOT'=>$root.'/',
			// HTTP routes
			'ROUTES'=>NULL,
			// Serialization engine
			'SERIALIZER'=>extension_loaded($ext='igbinary')?$ext:NULL,
			// Development stage
			'STAGE'=>'production',
			// Temporary folder
			'TEMP'=>'temp/',
			// Load time
			'TIME'=>microtime(TRUE),
			// Default timezone
			'TZ'=>'UTC',
			// Current version
			'VERSION'=>self::VERSION,
			// User interface folders
			'UI'=>'./'
		);
		if (PHP_SAPI=='cli') {
			if (isset($_SERVER['argc']) && $_SERVER['argc']<2) {
				$_SERVER['argc']++;
				$_SERVER['argv'][1]='/';
			}
			$_SERVER['SERVER_NAME']=gethostname();
			$fw->mock('GET '.$_SERVER['argv'][1],array(),FALSE);
		}
		else {
			$scheme=isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on' ||
				isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
				$_SERVER['HTTP_X_FORWARDED_PROTO']=='https'?'https':'http';
			self::$HIVE+=array(
				// Default cookie settings
				'JAR'=>array(
					'expire'=>0,
					'path'=>$base.'/',
					'domain'=>'.'.$_SERVER['SERVER_NAME'],
					'secure'=>($scheme=='https'),
					'httponly'=>TRUE
				),
				// Request method
				'VERB'=>&$_SERVER['REQUEST_METHOD'],
				// URI scheme
				'SCHEME'=>$scheme,
				// Request URI
				'URI'=>&$_SERVER['REQUEST_URI']
			);
		}
		// Link hive keys with PHP globals
		foreach (explode('|',self::GLOBALS) as $global)
			self::$HIVE[$global]=&$GLOBALS['_'.$global];
		spl_autoload_register(array($fw,'autoload'));
		if (class_exists('Index',FALSE))
			new Index;
	}

	/**
		Destroy instance
			@return void
	**/
	function __destruct() {
		chdir(self::$HIVE['ROOT']);
		if (($error=error_get_last()) && in_array($error['type'],
			array(E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR)) &&
			!self::$HIVE['QUIET'])
			trigger_error($error['message'].' '.
				'('.$this->fixslashes($error['file']).':'.$error['line'].')');
	}

}

//! Cache engine
class Cache extends Agent {

	private static
		//! Backend
		$ENGINE,
		//! Resource/reference
		$REF;

	/**
		Return timestamp of cache entry or FALSE if not found
			@return float|FALSE
			@param $key string
	**/
	function exists($key) {
		if (!self::$ENGINE)
			return FALSE;
		$fw=Base::instance();
		$ndx=$fw->hash(__DIR__).'.'.$key;
		switch (self::$ENGINE['type']) {
			case 'apc':
				if ($data=apc_fetch($ndx))
					break;
				return FALSE;
			case 'xcache':
				if ($data=xcache_get($ndx))
					break;
				return FALSE;
			case 'shmop':
				if ($ref=self::$REF) {
					$data=$fw->mutex(
						__FILE__,
						function() use($ref,$ndx) {
							$dir=unserialize(trim(shmop_read($ref,0,0xFFFF)));
							return isset($dir[$ndx])?
								shmop_read($ref,$dir[$ndx][0],$dir[$ndx][1]):
								FALSE;
						}
					);
					if ($data)
						break;
				}
				return FALSE;
			case 'memcache':
				if ($data=memcache_get(self::$REF,$ndx))
					break;
				return FALSE;
			case 'folder':
				if (is_file($file=self::$REF.$ndx) && $data=$fw->read($file))
					break;
				return FALSE;
		}
		if (isset($data)) {
			self::$ENGINE['data']=
				list($time,$ttl,$val)=$fw->unserialize($data);
			if (!$ttl || $time+$ttl>microtime(TRUE))
				return $time;
			$this->clear($key);
		}
		return FALSE;
	}

	/**
		Store value in cache
			@return mixed
			@param $key string
			@param $val mixed
			@param $ttl int
	**/
	function set($key,$val,$ttl=0) {
		if (!self::$ENGINE)
			return TRUE;
		$fw=Base::instance();
		$ndx=$fw->hash(__DIR__).'.'.$key;
		self::$ENGINE['data']=NULL;
		$data=$fw->serialize(array(microtime(TRUE),$ttl,$val));
		switch (self::$ENGINE['type']) {
			case 'apc':
				return apc_store($ndx,$data,$ttl);
			case 'xcache':
				return xcache_set($ndx,$data,$ttl);
			case 'shmop':
				return ($ref=self::$REF)?
					$fw->mutex(
						__FILE__,
						function() use($ref,$ndx,$data) {
							$dir=unserialize(trim(shmop_read($ref,0,0xFFFF)));
							$edge=0xFFFF;
							foreach ($dir as $stub)
								$edge=$stub[0]+$stub[1];
							shmop_write($ref,$data,$edge);
							unset($dir[$ndx]);
							$dir[$ndx]=array($edge,strlen($data));
							shmop_write($ref,serialize($dir).chr(0),0);
						}
					):
					FALSE;
			case 'memcache':
				return memcache_set(self::$REF,$ndx,$data,0,$ttl);
			case 'folder':
				return $fw->write(self::$REF.$ndx,$data);
		}
		return FALSE;
	}

	/**
		Retrieve value of cache entry
			@return mixed
			@param $key string
	**/
	function get($key) {
		if (!self::$ENGINE || !$this->exists($key))
			return FALSE;
		list($time,$ttl,$val)=self::$ENGINE['data'];
		return $val;
	}

	/**
		Delete cache entry
			@return void
			@param $key string
	**/
	function clear($key) {
		if (!self::$ENGINE)
			return;
		$fw=Base::instance();
		$ndx=$fw->hash(__DIR__).'.'.$key;
		self::$ENGINE['data']=NULL;
		switch (self::$ENGINE['type']) {
			case 'apc':
				return apc_delete($ndx);
			case 'xcache':
				return xcache_unset($ndx);
			case 'shmop':
				return ($ref=self::$REF) &&
					$fw->mutex(
						__FILE__,
						function() use($ref,$ndx) {
							$dir=unserialize(trim(shmop_read($ref,0,0xFFFF)));
							unset($dir[$ndx]);
							shmop_write($ref,serialize($dir).chr(0),0);
						}
					);
			case 'memcache':
				return memcache_delete(self::$REF,$ndx);
			case 'folder':
				return is_file($file=self::$REF.$ndx) &&
					$fw->unlink($file);
		}
	}

	/**
		Load and configure backend; Auto-detect if argument is FALSE
			@return string|void
			@param $dsn string|FALSE
	**/
	function load($dsn) {
		if (!$dsn)
			return;
		if (is_bool($dsn)) {
			// Auto-detect backend
			$ext=array_map('strtolower',get_loaded_extensions());
			$grep=preg_grep('/^(apc|xcache|shmop)/',$ext);
			$dsn=$grep?current($grep):'folder=cache/';
		}
		$fw=Base::instance();
		$parts=explode('=',$dsn);
		if (!preg_match('/apc|xcache|shmop|folder|memcache/',$parts[0]))
			return;
		self::$ENGINE=array('type'=>$parts[0],'data'=>NULL);
		self::$REF=NULL;
		if ($parts[0]=='shmop') {
			self::$REF=$fw->mutex(
				__FILE__,
				function() {
					$ref=@shmop_open(ftok(__FILE__,'C'),'c',0644,
						Base::instance()->bytes(ini_get('memory_limit')));
					if ($ref && !unserialize(trim(shmop_read($ref,0,0xFFFF))))
						shmop_write($ref,serialize(array()).chr(0),0);
					return $ref;
				}
			);
		}
		elseif (isset($parts[1])) {
			if ($parts[0]=='memcache') {
				if (extension_loaded('memcache'))
					foreach ($fw->split($parts[1]) as $server) {
						$parts=explode(':',$server);
						if (count($parts)<2) {
							$host=$parts[0];
							$port=11211;
						}
						else
							list($host,$port)=$parts;
						if (!self::$REF)
							self::$REF=@memcache_connect($host,$port);
						else
							memcache_add_server(self::$REF,$host,$port);
					}
				else
					return self::$ENGINE=NULL;
			}
			elseif ($parts[0]=='folder') {
				if (!is_dir($parts[1]) && !@mkdir($parts[1],0755,TRUE))
					return self::$ENGINE=NULL;
				self::$REF=$parts[1];
			}
		}
		return $dsn;
	}

}

//! Sandbox for PHP code
class Sandbox extends Agent {

	private
		//! Target file
		$FILE;

	/**
		Execute PHP code
			@return mixed
	**/
	function run() {
		return require $this->FILE;
	}

	/**
		Instantiate class
			@return void
			@param $file string
	**/
	function __construct($file) {
		parent::__construct(Base::instance());
		$this->FILE=$file;
	}

}

//! DB connection
class DB extends Agent {

	//@{ Messages
	const
		ERROR_Connect='Unable to connect to %s';
	//@}

	private
		//! DB wrapper namespace
		$NS;

	/**
		Retrieve/instantiate DB layer represented by the function name
			@return object
			@param $func string
			@param $args array
	**/
	function __call($func,array $args) {
		if (class_exists($class=$this->NS.'\\'.$func)) {
			array_unshift($args,$this);
			$ref=new ReflectionClass($class);
			return $ref->newinstanceargs($args);
		}
		return parent::__call($func,$args);
	}

	/**
		Instantiate class
			@return void
			@param $dsn string
	**/
	function __construct($dsn) {
		$engine=strstr($dsn,':',TRUE);
		// Pass all arguments to target mapper constructor
		$ref=new ReflectionClass($this->NS='DB\\'.
			(function_exists('pdo_drivers') &&
			preg_match('/'.implode('|',pdo_drivers()).'/',$engine)?
				'SQL':preg_replace('/db$/','',$engine)));
		parent::__construct($ref->newinstanceargs(func_get_args()));
	}

}

//! Simple cursor implementation
abstract class Cursor extends Agent {

	//@{ Messages
	const
		ERROR_Field='Undefined field %s';
	//@}

	private
		//! Query results
		$QUERY=array(),
		//! Current position
		$PTR=0;

	abstract
		function find($filter=NULL,array $options=NULL);
	abstract
		function insert();
	abstract
		function update();
	abstract
		function erase();

	/**
		Return TRUE if current cursor position is not mapped to any record
			@return bool
	**/
	function dry() {
		return !(bool)$this->QUERY;
	}

	/**
		Return first record that matches criteria
			@return array|FALSE
			@param $filter string|array
			@param $options array
	**/
	function findone($filter=NULL,array $options=NULL) {
		return ($data=$this->find($filter,$options))?$data[0]:FALSE;
	}

	/**
		Map instance to first record that matches criteria
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
		return ($this->QUERY=$this->find($filter,$options))?
			$this->QUERY[$this->PTR=0]:FALSE;
	}

	/**
		Move pointer to first record in cursor
			@return void
	**/
	function rewind() {
		$this->PTR=0;
	}

	/**
		Map instance to nth record relative to current cursor position
			@return mixed
			@param $ofs int
	**/
	function skip($ofs=1) {
		$ofs+=$this->PTR;
		return $ofs>-1 && $ofs<count($this->QUERY)?
			$this->QUERY[$this->PTR=$ofs]:FALSE;
	}

	/**
		Save mapped record
			@return mixed
	**/
	function save() {
		return $this->QUERY?$this->update():$this->insert();
	}

	/**
		Reset cursor
			@return void
	**/
	function reset() {
		$this->QUERY=array();
		$this->PTR=0;
	}

}

if (!function_exists('getallheaders')) {

	/**
		Retrieve HTTP headers (non-Apache server)
			@return array
	**/
	function getallheaders() {
		if (PHP_SAPI=='cli')
			return FALSE;
		$req=array();
		foreach ($_SERVER as $key=>$val)
			if (substr($key,0,5)=='HTTP_')
				$req[strtr(ucwords(strtolower(
					strtr(substr($key,5),'_',' '))),' ','-')]=$val;
		return $req;
	}

}

return new Base;
