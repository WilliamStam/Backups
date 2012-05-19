<?php

//! Wrapper for various HTTP utilities
class Web extends Registry {

	/**
		Return TRUE if HTTP request originated from AJAX client
			@return bool
	**/
	function isajax() {
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
			$_SERVER['HTTP_X_REQUESTED_WITH']=='XMLHttpRequest';
	}

	/**
		Transmit file to HTTP client
			@return int|FALSE
			@param $file string
			@param $kbps int
	**/
	function send($file,$kbps=0) {
		if (!is_file($file))
			return FALSE;
		if (PHP_SAPI!='cli' && !headers_sent()) {
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; '.
				'filename='.basename($file));
			header('Accept-Ranges: bytes');
			header('Content-Length: '.$size=filesize($file));
		}
		$ctr=0;
		$handle=fopen($file,'r');
		$start=microtime(TRUE);
		while (!feof($handle) && !connection_aborted()) {
			if ($kbps) {
				// Throttle output
				$ctr++;
				if ($ctr/$kbps>$elapsed=microtime(TRUE)-$start)
					usleep(1e6*($ctr/$kbps-$elapsed));
			}
			// Send 1KiB and reset timer
			echo fread($handle,1024);
		}
		fclose($handle);
		return $size;
	}

	/**
		Submit HTTP request; Use HTTP context options (described in
		http://www.php.net/manual/en/context.http.php) if specified;
		Cache the page as instructed by remote server
			@return array
			@param $url string
			@param $options array
	**/
	function request($url,array $options=NULL) {
		if (!is_array($options))
			$options=array();
		$parts=parse_url($url);
		if (isset($parts['scheme']) &&
			!preg_match('/https?/',$parts['scheme']))
			return FALSE;
		$options+=array(
			'method'=>'GET',
			'follow_location'=>TRUE,
			'max_redirects'=>20,
			'header'=>array(
				'Host: '.$parts['host'],
				'User-Agent: Mozilla/5.0 (compatible; '.php_uname('s').')',
				'Content-Type: application/x-www-form-urlencoded',
				'Connection: close'
			)
		);
		$eol="\r\n";
		$fw=Base::instance();
		if ($fw->get('CACHE') &&
			preg_match('/GET|HEAD/',$options['method'])) {
			$cache=Cache::instance();
			if ($cache->exists(
				$hash=$fw->hash($options['method'].' '.$url).'.url')) {
				$data=$cache->get($hash);
				if (preg_match('/Last-Modified:\s(.+?)'.preg_quote($eol).'/',
					implode($eol,$data['headers']),$mod))
					$options['header']+=array('If-Modified-Since: '.$mod[1]);
			}
		}
		if (extension_loaded('curl')) {
			$curl=curl_init($url);
			curl_setopt($curl,CURLOPT_FOLLOWLOCATION,
				$options['follow_location']);
			curl_setopt($curl,CURLOPT_MAXREDIRS,
				$options['max_redirects']);
			curl_setopt($curl,CURLOPT_CUSTOMREQUEST,$options['method']);
			if (isset($options['header']))
				curl_setopt($curl,CURLOPT_HTTPHEADER,$options['header']);
			if (isset($options['user_agent']))
				curl_setopt($curl,CURLOPT_USERAGENT,$options['user_agent']);
			if (isset($options['content']))
				curl_setopt($curl,CURLOPT_POSTFIELDS,$options['content']);
			$headers=array();
			curl_setopt($curl,CURLOPT_HEADERFUNCTION,
				function($curl,$line) use(&$headers) {
					if ($trim=trim($line))
						$headers[]=$trim;
					return strlen($line);
				}
			);
			curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,FALSE);
			ob_start();
			$out=curl_exec($curl);
			curl_close($curl);
			if (!$out)
				return FALSE;
			$result=array(
				'body'=>ob_get_clean(),
				'headers'=>$headers,
				'engine'=>'cURL',
				'cached'=>FALSE
			);
		}
		elseif (ini_get('allow_url_fopen')) {
			$options['header']=implode($eol,$options['header']);
			$out=file_get_contents($url,FALSE,
				stream_context_create(array('http'=>$options)));
			if (!$out)
				return FALSE;
			$result=array(
				'body'=>$out,
				'headers'=>$http_response_header,
				'engine'=>'stream-wrapper',
				'cached'=>FALSE
			);
		}
		else {
			$headers=array();
			$body='';
			for ($i=0;$i<$options['max_redirects'];$i++) {
				if (isset($parts['user']) && isset($parts['pass']))
					$options['header']+=array(
						'Authorization: Basic '.
							base64_encode($parts['user'].':'.$parts['pass'])
					);
				if (isset($parts['scheme']) && $parts['scheme']=='https') {
					$parts['host']='ssl://'.$parts['host'];
					if (!isset($parts['port']))
						$parts['port']=443;
				}
				elseif (!isset($parts['port']))
					$parts['port']=80;
				if (!isset($parts['path']))
					$parts['path']='/';
				if (!isset($parts['query']))
					$parts['query']='';
				$socket=@fsockopen($parts['host'],$parts['port'],$code,$text);
				if (!$socket)
					return FALSE;
				stream_set_blocking($socket,1);
				fputs($socket,$options['method'].' '.$parts['path'].
					($parts['query']?('?'.$parts['query']):'').' '.
					'HTTP/1.0'.$eol
				);
				fputs($socket,
					'Content-Length: '.strlen($parts['query']).$eol.
					'Accept-Encoding: gzip'.$eol.$eol
				);
				if (isset($options['header']))
					fputs($socket,implode($eol,$options['header']).$eol);
				if (isset($options['user_agent']))
					fputs($socket,'User-Agent: '.$options['user_agent'].$eol);
				if (isset($options['content']))
					fputs($socket,$options['content'].$eol.$eol);
				$content='';
				while (!feof($socket) &&
					($info=stream_get_meta_data($socket)) &&
					!$info['timed_out'] && $str=fgets($socket,4096))
					$content.=$str;
				fclose($socket);
				$html=explode($eol.$eol,$content);
				$headers=array_merge($headers,explode($eol,$html[0]));
				$body=$html[1];
				if (preg_match('/Content-Encoding:\s.*?gzip.*?'.
					preg_quote($eol).'/',$html[0]))
					$body=gzinflate(substr($body,10));
				if (!$options['follow_location'] ||
					!preg_match('/Location:\s(.+?)'.preg_quote($eol).'/',
					$html[0],$loc))
					break;
				$url=$loc[1];
				$parts=parse_url($url);
			}
			$result=array(
				'body'=>$body,
				'headers'=>$headers,
				'engine'=>'sockets',
				'cached'=>FALSE
			);
		}
		if (isset($cache)) {
			if (preg_match('/HTTP\/1\.\d 304/',
				implode($eol,$result['headers']))) {
				$result=$cache->get($hash);
				$result['cached']=TRUE;
			}
			elseif (preg_match('/Cache-Control:\smax-age=(.+?)'.
				preg_quote($eol).'/',implode($eol,$result['headers']),$exp))
				$cache->set($hash,$result,$exp[1]);
		}
		return $result;
	}

	/**
		Sniff headers for real IP address
			@return string
	**/
	function realip() {
		return isset($_SERVER['HTTP_CLIENT_IP'])?
			// Behind proxy
			$_SERVER['HTTP_CLIENT_IP']:
			(isset($_SERVER['HTTP_X_FORWARDED_FOR'])?
				// Use first IP address in list
				current(explode(',',$_SERVER['HTTP_X_FORWARDED_FOR'])):
				$_SERVER['REMOTE_ADDR']);
	}

	/**
		Strip Javascript/CSS files of extraneous whitespaces and comments;
		Return combined output as a minified string
			@return string
			@param $files string|array
			@param $mime string
	**/
	function minify($files,$mime=NULL) {
		$fw=Base::instance();
		if (is_string($files))
			$files=$fw->split($files);
		$src='';
		foreach ($fw->split($fw->get('UI')) as $path)
			foreach ($files as $file) {
				if (is_file($file=$path.$file) &&
					is_bool(strpos($file,'../')))
					$src.=$fw->read($file);
			}
		if (!$src)
			return FALSE;
		$ptr=0;
		$dst='';
		for ($ptr=0,$len=strlen($src);$ptr<$len;) {
			if ($src[$ptr]=='/') {
				// Presume it's a regex pattern
				$regex=TRUE;
				if ($ptr>0) {
					// Backtrack and validate
					$ofs=$ptr;
					while ($ofs>0) {
						$ofs--;
						// Pattern should be preceded by parenthesis,
						// colon or assignment operator
						if (in_array($src[$ofs],array('(',':','='))) {
							while ($ptr<strlen($src)) {
								$str=strstr(substr($src,$ptr+1),'/',TRUE);
								if (is_bool($str) && $src[$ptr-1]!='/' ||
									is_int(strpos($str,"\n"))) {
									// Not a regex pattern
									$regex=FALSE;
									break;
								}
								$dst.='/'.$str;
								$ptr+=strlen($str)+1;
								if ($src[$ptr-1]!='\\' ||
									$src[$ptr-2]=='\\') {
									$dst.='/';
									$ptr++;
									break;
								}
							}
							break;
						}
						elseif ($src[$ofs]!="\t" && $src[$ofs]!=' ') {
							// Not a regex pattern
							$regex=FALSE;
							break;
						}
					}
					if ($regex && $ofs<1)
						$regex=FALSE;
				}
				if (!$regex || $ptr<1) {
					if (substr($src,$ptr+1,2)=='*@') {
						// Conditional block
						$str=strstr(substr($src,$ptr+3),'@*/',TRUE);
						$dst.='/*@'.$str.$src[$ptr].'@*/';
						$ptr+=strlen($str)+6;
					}
					elseif ($src[$ptr+1]=='*') {
						// Multiline comment
						$str=strstr(substr($src,$ptr+2),'*/',TRUE);
						$ptr+=strlen($str)+4;
					}
					elseif ($src[$ptr+1]=='/') {
						// Single-line comment
						$str=strstr(substr($src,$ptr+2),"\n",TRUE);
						$ptr+=strlen($str)+2;
					}
					else {
						// Division operator
						$dst.=$src[$ptr];
						$ptr++;
					}
				}
				continue;
			}
			if (in_array($src[$ptr],array('\'','"'))) {
				$match=$src[$ptr];
				// String literal
				while ($ptr<strlen($src)) {
					$str=strstr(substr($src,$ptr+1),$src[$ptr],TRUE);
					$dst.=$match.$str;
					$ptr+=strlen($str)+1;
					if ($src[$ptr-1]!='\\' || $src[$ptr-2]=='\\') {
						$dst.=$match;
						$ptr++;
						break;
					}
				}
				continue;
			}
			if (ctype_space($src[$ptr])) {
				$last=substr($dst,-1);
				$ofs=$ptr+1;
				if ($ofs+1<strlen($src)) {
					while (ctype_space($src[$ofs]))
						$ofs++;
					if (preg_match('/[\w%][\w#\-*\.]/',$last.$src[$ofs]))
						$dst.=$src[$ptr];
				}
				$ptr=$ofs;
			}
			else {
				$dst.=$src[$ptr];
				$ptr++;
			}
		}
		if (PHP_SAPI!='cli' && !headers_sent() && $mime) {
			header('Content-Type: '.$mime.'; charset='.$fw->get('ENCODING'));
			echo $dst;
		}
		return $dst;
	}

	/**
		Generate XML sitemap
			@return string
			@param $root string
			@param $echo bool
	**/
	function sitemap($root,$echo=FALSE) {
		$fw=Base::instance();
		$scheme=$fw->get('SCHEME');
		$charset=$fw->get('ENCODING');
		$uri=$fw->get('URI');
		$map=array();
		$max=0;
		for ($links=array(array($root)),$depth=0;
			isset($links[$depth]) && $links[$depth];$depth++)
			foreach ($links[$depth] as $url) {
				$parts=parse_url($url);
				if (!isset($parts['scheme']))
					$parts['scheme']=$scheme;
				if (!isset($parts['host']))
					$parts['host']=$_SERVER['SERVER_NAME'];
				$host=$parts['scheme'].'://'.$parts['host'];
				// Grab page contents
				$response=$this->request($host.$parts['path']);
				$doc=new DOMDocument('1.0',$charset);
				// Suppress errors caused by invalid HTML structures
				libxml_use_internal_errors(TRUE);
				if ($response &&
					preg_grep('/HTTP\/1\.\d 200/',$response['headers']) &&
					$doc->loadhtml($response['body'])) {
					$mod=gmdate('c');
					if ($grep=current(preg_grep('/X-LastMod:\s(.+)/s',
						$response['headers'])))
						$mod=$grep[1];
					$freq='daily';
					if ($grep=current(preg_grep('/X-ChangeFreq:\s(.+)/s',
						$response['headers'])))
						$freq=$grep[1];
					$map[$url]=array($mod,$freq,$depth);
					if ($depth>$max)
						$max=$depth;
					$links[$depth+1]=array();
					foreach ($doc->getelementsbytagname('a') as $link)
						if ($link->getattribute('rel')!='nofollow') {
							if (is_int(strpos(
								$ref=$link->getattribute('href'),'#')))
								$ref=strstr($ref,'#',TRUE);
							if (!isset($map[$ref]) && $ref!=$uri &&
								$parts=parse_url($ref) &&
								(!isset($parts['host']) ||
								$parts['host']==$_SERVER['SERVER_NAME']))
								array_push($links[$depth+1],$ref);
						}
				}
				else
					$map[$url]=FALSE;
				unset($doc);
			}
		if (PHP_SAPI!='cli' && !headers_sent() && $echo)
			header('Content-Type: application/xml; charset='.$charset);
		$xml=new SimpleXMLElement(
			'<?xml version="1.0" encoding="'.$charset.'"?><urlset/>');
		$xml->addattribute('xmlns',
			'http://www.sitemaps.org/schemas/sitemap/0.9');
		foreach ($map as $key=>$val)
			if ($val) {
				// Add new URL
				$node=$xml->addchild('url');
				$node->addchild('loc',$host.$key);
				$node->addchild('lastmod',$val[0]);
				$node->addchild('changefreq',$val[1]);
				$node->addchild('priority',
					sprintf('%.2f',1-$val[2]/($max+1)));
			}
		$out=$xml->asxml();
		if ($echo)
			echo $out;
		return $out;
	}

	/**
		Retrieve RSS/Atom feed and return as an array
			@return mixed
			@param $url string
			@param $max int
			@param $tags string
	**/
	function rss($url,$max=10,$tags=NULL) {
		if (!$data=$this->request($url))
			return FALSE;
		$xml=simplexml_load_string($data['body'],
			NULL,LIBXML_NOCDATA|LIBXML_ERR_FATAL);
		if (!is_object($xml))
			return FALSE;
		$fw=Base::instance();
		$out=array();
		if (isset($xml->channel)) {
			$out['source']=(string)$xml->channel->title;
			for ($i=0;$i<$max;$i++) {
				$item=$xml->channel->item[$i];
				$out['feed'][]=array(
					'title'=>(string)$item->title,
					'link'=>(string)$item->link,
					'text'=>(string)$item->description
				);
			}
		}
		elseif (isset($xml->entry)) {
			$out['source']=(string)$xml->author->name;
			for ($i=0;$i<$max;$i++) {
				$item=$xml->entry[$i];
				$out['feed'][]=array(
					'title'=>(string)$item->title,
					'link'=>(string)$item->link['href'],
					'text'=>(string)$item->summary
				);
			}
		}
		else
			return FALSE;
		$fw->scrub($out,$tags);
		return $out;
	}

	/**
		Return a URL/filesystem-friendly version of string
			@return string
			@param $text string
	**/
	function slug($text) {
		return trim(strtolower(preg_replace('/([^\w]|-)+/','-',
			trim(strtr(str_replace('\'','',$text),
			Base::instance()->get('DIACRITICS'))))),'-');
	}

	/**
		Class constructor
			@return void
	**/
	function __construct() {
		$fw=Base::instance();
		if ($fw->bound(__CLASS__))
			return;
		$fw->bind(__CLASS__,$this);
		if (!$ref=&$fw->ref('DIACRITICS'))
			$ref=array();
		$ref+=array(
			'À'=>'A','Á'=>'A','Â'=>'A','Ã'=>'A','Å'=>'A','Ä'=>'A','Æ'=>'AE',
			'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','å'=>'a','ä'=>'a','æ'=>'ae',
			'Þ'=>'B','þ'=>'b','Č'=>'C','Ć'=>'C','Ç'=>'C','č'=>'c','ć'=>'c',
			'ç'=>'c','Ď'=>'D','ð'=>'d','ď'=>'d','Đ'=>'Dj','đ'=>'dj','È'=>'E',
			'É'=>'E','Ê'=>'E','Ë'=>'E','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
			'Ì'=>'I','Í'=>'I','Î'=>'I','Ï'=>'I','ì'=>'i','í'=>'i','î'=>'i',
			'ï'=>'i','Ľ'=>'L','ľ'=>'l','Ñ'=>'N','Ň'=>'N','ñ'=>'n','ň'=>'n',
			'Ò'=>'O','Ó'=>'O','Ô'=>'O','Õ'=>'O','Ø'=>'O','Ö'=>'O','Œ'=>'OE',
			'ð'=>'o','ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','œ'=>'oe',
			'ø'=>'o','Ŕ'=>'R','Ř'=>'R','ŕ'=>'r','ř'=>'r','Š'=>'S','š'=>'s',
			'ß'=>'ss','Ť'=>'T','ť'=>'t','Ù'=>'U','Ú'=>'U','Û'=>'U','Ü'=>'U',
			'Ů'=>'U','ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u','ů'=>'u','Ý'=>'Y',
			'Ÿ'=>'Y','ý'=>'y','ý'=>'y','ÿ'=>'y','Ž'=>'Z','ž'=>'z'
		);
	}

}
