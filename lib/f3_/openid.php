<?php

class OpenID extends Agent {

	//@{ Messages
	const
		ERROR_EndPoint='Unable to find OpenID provider';
	//@}

	var
		//! HTTP request parameters
		$DATA=array();

	/**
		Initiate OpenID login sequence
			@return bool
			@public
	**/
	function login() {
		$fw=Base::instance();
		$root=$fw->get('SCHEME').'://'.$_SERVER['SERVER_NAME'];
		if (!isset($this->DATA['trust_root']))
			$this->DATA['trust_root']=$root.($fw->get('BASE')?:'').'/';
		if (!isset($this->DATA['return_to']))
			$this->DATA['return_to']=$root.$fw->get('URI');
		$this->DATA['mode']='checkid_setup';
		if (isset($this->DATA['provider'])) {
			// OpenID 2.0
			$op=$this->DATA['provider'];
			if (!isset($this->DATA['claimed_id']))
				$this->DATA['claimed_id']=$this->DATA['identity'];
		}
		elseif (isset($this->DATA['server']))
			// OpenID 1.1
			$op=$this->DATA['server'];
		else
			return FALSE;
		$var=array();
		foreach ($this->DATA as $key=>$val)
			$var['openid.'.$key]=$val;
		$fw->reroute($op.'?'.http_build_query($var));
	}

	/**
		Return TRUE if OpenID verification was successful
			@return bool
	**/
	function verified() {
		foreach ($_REQUEST as $key=>$val)
			if (preg_match('/^openid_(.+)/',$key,$match))
				$this->set($match[1],$val);
		if (isset($this->DATA['provider']))
			$op=$this->DATA['provider'];
		elseif (isset($this->DATA['server']))
			$op=$this->DATA['server'];
		else {
			trigger_error(self::ERROR_EndPoint);
			return FALSE;
		}
		$this->DATA['mode']='check_authentication';
		$var=array();
		foreach ($this->DATA as $key=>$val)
			$var['openid.'.$key]=$val;
		$response=Web::instance()->request(
			$op,
			array(
				'method'=>'POST',
				'content'=>http_build_query($var)
			)
		);
		return $response && preg_match('/is_valid:true/i',$response['body']);
	}

	/**
		Bind value to OpenID request parameter
			@return string|FALSE
			@param $key string
			@param $val string
	**/
	function set($key,$val) {
		if ($key=='identity') {
			// Normalize
			if (!preg_match('/https?:/i',$val))
				$val='http://'.$val;
			$url=parse_url($val);
			// Remove fragment; reconnect parts
			$val=$url['scheme'].'://'.
				(isset($url['user'])?
					($url['user'].
					(isset($url['pass'])?
						(':'.$url['pass']):'').'@'):'').
				strtolower($url['host']).
				(isset($url['path'])?$url['path']:'/').
				(isset($url['query'])?('?'.$url['query']):'');
			// Discover OpenID provider
			if (!$response=Web::instance()->request($val))
				return FALSE;
			$type=array_values(preg_grep('/Content-Type:/',
				$response['headers']));
			if ($type &&
				preg_match('/application\/xrds\+xml|text\/xml/',$type[0]) &&
				($sxml=simplexml_load_string($response['body'])) &&
				($xrds=json_decode(json_encode($sxml),TRUE)) &&
				isset($xrds['XRD'])) {
				// XRDS document
				$svc=$xrds['XRD']['Service'];
				if (isset($svc[0]))
					$svc=$svc[0];
				if (preg_grep('/http:\/\/specs\.openid\.net\/auth\/2.0\/'.
						'(?:server|signon)/',$svc['Type'])) {
					$this->DATA['provider']=$svc['URI'];
					if (isset($svc['LocalID']))
						$this->DATA['localidentity']=$svc['LocalID'];
					elseif (isset($svc['CanonicalID']))
						$this->DATA['localidentity']=$svc['CanonicalID'];
				}
				$this->DATA['server']=$svc['URI'];
				if (isset($svc['Delegate']))
					$this->DATA['delegate']=$svc['Delegate'];
			}
			else {
				$len=strlen($response['body']);
				$ptr=0;
				// Parse document
				while ($ptr<$len)
					if (preg_match(
						'/^<link\b((?:\s+\w+s*=\s*(?:"(?:.+?)"|'.
						'\'(?:.+?)\'))*)\s*\/?>/is',
						substr($response['body'],$ptr),$match)) {
						if ($match[1]) {
							// Process attributes
							preg_match_all('/\s+(rel|href)\s*=\s*'.
								'(?:"(.+?)"|\'(.+?)\')/s',$match[1],$attr,
								PREG_SET_ORDER);
							$node=array();
							foreach ($attr as $kv)
								$node[$kv[1]]=isset($kv[2])?$kv[2]:$kv[3];
							if (isset($node['rel']) &&
								preg_match('/openid2?\.(\w+)/',
									$node['rel'],$var) &&
								isset($node['href']))
								$this->DATA[$var[1]]=$node['href'];

						}
						$ptr+=strlen($match[0]);
					}
					else
						$ptr++;
			}
			// Get OpenID provider's endpoint URL
			if (isset($this->DATA['provider'])) {
				// OpenID 2.0
				$this->DATA['ns']='http://specs.openid.net/auth/2.0';
				if (isset($this->DATA['localidentity']))
					$this->DATA['identity']=$this->DATA['localidentity'];
				if (isset($this->DATA['trust_root']))
					$this->DATA['realm']=$this->DATA['trust_root'];
			}
			elseif (isset($this->DATA['server'])) {
				// OpenID 1.1
				if (isset($this->DATA['delegate']))
					$this->DATA['identity']=$this->DATA['delegate'];
			}
			return $this->DATA['identity']=$val;
		}
		return $this->DATA[$key]=$val;
	}

	/**
		Return value of OpenID request parameter
			@return string|FALSE
			@param $key string
	**/
	function get($key) {
		return isset($this->DATA[$key])?$this->DATA[$key]:FALSE;
	}

	/**
		Return TRUE if OpenID request parameter exists
			@return bool
			@param $key string
	**/
	function exists($key) {
		return isset($this->DATA[$key]);
	}

	/**
		Remove OpenID request parameter
			@return void
			@param $key
	**/
	function clear($key) {
		unset($this->DATA[$key]);
	}

}
