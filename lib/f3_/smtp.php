<?php

//! SMTP plugin
class SMTP extends Agent {

	//@{ Messages
	const
		ERROR_Socket='Socket error',
		ERROR_Header='%s: header is required',
		ERROR_Blank='Message must not be blank',
		ERROR_Attachment='Attachment %s not found';
	//@}

	private
		//! Connection
		$SOCKET,
		//! Message properties
		$HEADERS,
		//! E-mail attachments
		$ATTACHMENTS,
		//! Server-client conversation
		$LOG,
		//! Dialog termination switch
		$HALT=FALSE;

	/**
		Fix header
			@return string
			@param $key
			@private
	**/
	private function fixheader($key) {
		return str_replace(' ','-',ucwords(preg_replace('/[-_]/',' ',$key)));
	}

	/**
		Return TRUE if header exists
			@return bool
			@param $key string
	**/
	function exists($key) {
		$key=$this->fixheader($key);
		return isset($this->HEADERS[$key]);
	}

	/**
		Return SMTP session log
			@return string
	**/
	function log() {
		return $this->LOG;
	}

	/**
		Bind value to e-mail header
			@return string
			@param $key string
			@param $val string
	**/
	function set($key,$val) {
		$key=$this->fixheader($key);
		return $this->HEADERS[$key]=$val;
	}

	/**
		Return value of e-mail header
			@return string
			@param $key string
	**/
	function get($key) {
		$key=$this->fixheader($key);
		return isset($this->HEADERS[$key])?$this->HEADERS[$key]:NULL;
	}

	/**
		Remove header
			@return void
			@param $key
	**/
	function clear($key) {
		$key=$this->fixheader($key);
		unset($this->HEADERS[$key]);
	}

	/**
		Add e-mail attachment
			@return void
			@param $file
	**/
	function attach($file) {
		if (!is_file($file)) {
			trigger_error(sprintf(self::ERROR_Attachment,$file));
			return;
		}
		$this->ATTACHMENTS[]=$file;
	}

	/**
		Send SMTP command and record server response
			@return void
			@param $cmd string
			@param $log bool
	**/
	function dialog($cmd=NULL,$log=FALSE) {
		if ($this->HALT)
			return;
		elseif (!fputs($this->SOCKET,$cmd."\r\n"))
			$this->HALT=TRUE;
		else {
			if ($log) {
				$reply='';
				while (($str=fgets($this->SOCKET,512)) &&
					!preg_match('/\d{3}\s/',$str))
					$reply.=$str;
				$this->LOG.=$cmd."\n";
				$this->LOG.=$reply;
			}
			else
				$this->LOG.=$cmd."\n";
		}
	}

	/**
		Transmit message
			@return void
			@param $message string
	**/
	function send($message) {
		// Required headers
		$reqd=array('From','To','Subject');
		// Retrieve headers
		foreach ($reqd as $id)
			if (!isset($this->HEADERS[$id])) {
				trigger_error(sprintf(self::ERROR_Header,$id));
				return;
			}
		// Message should not be blank
		if (!$message) {
			trigger_error(self::ERROR_Blank);
			return;
		}
		$str='';
		// Stringify headers
		foreach ($this->HEADERS as $key=>$val)
			if (!in_array($key,$reqd))
				$str.=$key.': '.$val."\r\n";
		// Start message dialog
		$this->dialog('MAIL FROM: '.strstr($this->HEADERS['From'],'<'),TRUE);
		$this->dialog('RCPT TO: '.$this->HEADERS['To'],TRUE);
		$this->dialog('DATA',TRUE);
		if ($this->attachments) {
			// Replace Content-Type
			$hash=self::hash(mt_rand());
			$type=$this->HEADERS['Content-Type'];
			$this->HEADERS['Content-Type']='multipart/mixed; '.
				'boundary="'.$hash.'"';
			// Send mail headers
			foreach ($this->HEADERS as $key=>$val)
				$this->dialog($key.': '.$val);
			$this->dialog();
			$this->dialog('This is a multi-part message in MIME format');
			$this->dialog();
			$this->dialog('--'.$hash);
			$this->dialog('Content-Type: '.$type);
			$this->dialog();
			$this->dialog($message);
			$this->dialog('--'.$hash);
			foreach ($this->attachments as $attachment) {
				$this->dialog('Content-Type: application/octet-stream');
				$this->dialog('Content-Transfer-Encoding: base64');
				$this->dialog('Content-Disposition: attachment; '.
					'filename="'.basename($attachment).'"');
				$this->dialog();
				$this->dialog(chunk_split(
					base64_encode(Base::instance()->read($attachment))));
			}
			$this->dialog('--'.$hash);
		}
		else {
			// Send mail headers
			foreach ($this->HEADERS as $key=>$val)
				$this->dialog($key.': '.$val);
			$this->dialog();
			// Send message
			$this->dialog($message);
		}
		$this->dialog('.');
	}

	/**
		Class constructor
			@return void
			@param $url string
	**/
	function __construct($url) {
		$parts=parse_url($url);
		$this->HEADERS=array(
			'MIME-Type'=>'1.0',
			'Content-Type'=>'text/plain; '.
				'charset='.Base::instance()->get('ENCODING'),
			'Content-Transfer-Encoding'=>'8bit'
		);
		if (!isset($parts['port']))
			$parts['port']=25;
		// Connect to the server
		$this->SOCKET=@fsockopen(
			(isset($parts['scheme'])?($parts['scheme'].'://'):'').
			$parts['host'],$parts['port'],$errno,$errstr);
		if (!$this->SOCKET) {
			trigger_error('SMTP: '.($errstr?:self::ERROR_Socket));
			return;
		}
		stream_set_blocking($this->SOCKET,TRUE);
		stream_set_timeout($this->SOCKET,ini_get('default_socket_timeout'));
		// Get server's initial response
		$this->LOG=fgets($this->SOCKET,512);
		// Indicate presence
		$this->dialog('EHLO '.$_SERVER['SERVER_NAME'],TRUE);
		if (preg_match('/tls/i',$parts['scheme'])) {
			$this->dialog('STARTTLS',TRUE);
			stream_socket_enable_crypto(
				$this->SOCKET,TRUE,STREAM_CRYPTO_METHOD_TLS_CLIENT);
			$this->dialog('EHLO '.$_SERVER['SERVER_NAME'],TRUE);
		}
		if (isset($parts['user'])) {
			// Authenticate
			$this->dialog('AUTH LOGIN',TRUE);
			$this->dialog(base64_encode($parts['user']),TRUE);
			$this->dialog(base64_encode($parts['pass']),TRUE);
		}
	}

	/**
		Free up resources
			@return void
	**/
	function __destruct() {
		$this->dialog('QUIT',TRUE);
		fclose($this->SOCKET);
	}

}
