<?php

class Net extends Registry {

	/**
		Send ICMP echo request to specified host; Return array containing
		minimum/average/maximum round-trip time (in millisecs) and number of
		packets received, or FALSE if host is unreachable (method requires
		root access on Unix; as such, it will work only in CLI mode)
			@return mixed
			@param $addr string
			@param $func callback
			@param $dns bool
			@param $count int
			@param $wait int
			@param $ttl int
	**/
	function ping($addr,$func=NULL,$dns=FALSE,$count=3,$wait=3,$ttl=30) {
		$addr=gethostbyname($addr);
		// ICMP sockets
		$tsocket=($unix=(PHP_OS=='Linux'))?
			socket_create(AF_INET,SOCK_DGRAM,17):
			socket_create(AF_INET,SOCK_RAW,1);
		$rsocket=socket_create(AF_INET,SOCK_RAW,1);
		if (!$tsocket || !$rsocket)
			return FALSE;
		// Set TTL
		socket_set_option($tsocket,0,$unix?2:4,$ttl);
		// Bind to interface
		socket_bind($rsocket,0);
		// Initialize counters
		$rcv=$min=$max=0;
		$rtt='*';
		for ($i=0;$i<$count;$i++) {
			if ($unix)
				// Send zero-length UDP packet
				socket_sendto($tsocket,'',0,0,$addr,33434);
			else {
				// Construct ICMP header and payload
				$data=pack('H*',uniqid());
				$payload=pack('H*','0800000000000000').$data;
				// Recalculate ICMP checksum
				if (strlen($payload)%2)
					$payload.=pack('H*','00');
				$sum=array_sum(unpack('n*',$payload));
				while ($sum>>16)
					$sum=($sum>>16)+($sum&0xFFFF);
				// ICMP echo request
				$payload=pack('H*','0800').pack('n*',~$sum).
					pack('H*','00000000').$data;
				// Transmit ICMP packet
				socket_sendto($tsocket,$payload,strlen($payload),0,$addr,0);
			}
			// Start timer
			$now=microtime(TRUE);
			$rset=array($rsocket);
			$tset=$xset=array();
			socket_select($rset,$tset,$xset,$wait);
			// Wait for incoming ICMP packet
			if ($rset) {
				@socket_recvfrom($rsocket,$reply,255,0,$host,$port);
				// Socket didn't timeout; Record round-trip time
				$rtt=1e3*(microtime(TRUE)-$now);
				if ($rtt>$max)
					$max=$rtt;
				if (!$min || $rtt<$min)
					$min=$rtt;
				// Count packets received
				$rcv++;
				if ($host)
					$addr=$host;
			}
			if ($func)
				call_user_func($func,$addr,$rtt);
		}
		socket_close($tsocket);
		socket_close($rsocket);
		return $rcv?
			array(
				'host'=>$dns?gethostbyaddr($addr):$addr,
				'min'=>(int)round($min),
				'max'=>(int)round($max),
				'avg'=>(int)round($rtt/$rcv),
				'packets'=>$rcv
			):
			FALSE;
	}

	/**
		Return the path taken by packets to a specified network destination
			@return array
			@param $addr string
			@param $func callback
			@param $dns bool
			@param $wait int
			@param $hops int
	**/
	function traceroute($addr,$func=NULL,$dns=FALSE,$wait=3,$hops=30) {
		$route=array();
		for ($ttl=0;$ttl<$hops;$ttl++) {
			set_time_limit(ini_get('default_socket_timeout'));
			$ping=$this->ping($addr,NULL,$dns,3,$wait,$ttl+1);
			if ($ping) {
				if ($func)
					call_user_func($func,$ping);
				$route[]=$ping;
				if (gethostbyname($ping['host'])==gethostbyname($addr))
					break;
			}
		}
		return $route;
	}

	/**
		Retrieve information from whois server
			@return string
			@param $addr string
	**/
	function whois($addr) {
		$socket=@fsockopen(Base::instance()->get('WHOIS'),43,$errno,$errstr);
		if (!$socket) {
			// Can't establish connection
			trigger_error($errstr);
			return FALSE;
		}
		// Set connection timeout parameters
		stream_set_blocking($socket,TRUE);
		stream_set_timeout($socket,ini_get('default_socket_timeout'));
		// Send request
		fputs($socket,'domain '.$addr."\r\n");
		$info=stream_get_meta_data($socket);
		// Get response
		$response='';
		while (!feof($socket) && !$info['timed_out']) {
			$response.=fgets($socket,4096); // MDFK97
			$info=stream_get_meta_data($socket);
		}
		fclose($socket);
		return $info['timed_out']?FALSE:$response;
	}

	/**
		Class constructor
			@return void
	**/
	function __construct() {
		$fw=Base::instance();
		if (!$fw->exists('WHOIS'))
			$fw->set('WHOIS','whois.internic.net');
		if (!extension_loaded('sockets'))
			// Sockets extension required
			trigger_error(sprintf(self::TEXT_PHPExt,'sockets'));
	}

}
