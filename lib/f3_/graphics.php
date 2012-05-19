<?php

//! Graphics plugin
class Graphics extends Registry {

	//@{ Messages
	const
		ERROR_Color='Invalid color specified';
	//@}

	private
		$IMAGE;

	/**
		Convert RGB hex triad to array
			@return array|FALSE
			@param $color int
	**/
	function rgb($color) {
		$hex=str_pad(dechex($color),$color<4096?3:6,'0',STR_PAD_LEFT);
		if (($len=strlen($hex))>6) {
			trigger_error(self::ERROR_Color);
			return FALSE;
		}
		$color=str_split($hex,$len/3);
		foreach ($color as &$hue)
			$hue=hexdec(str_repeat($hue,6/$len));
		return $color;
	}

	/**
		Create image from file
			@return object
			@param $file string
	**/
	function load($file) {
		$this->IMAGE=imagecreatefromstring(Base::instance()->read($file));
		return $this;
	}

	/**
		Invert image
			@return object
	**/
	function invert() {
		imagefilter($this->IMAGE,IMG_FILTER_NEGATE);
		return $this;
	}

	/**
		Adjust brightness (range:-255 to 255)
			@return object
			@param $level int
	**/
	function brightness($level) {
		imagefilter($this->IMAGE,IMG_FILTER_BRIGHTNESS,$level);
		return $this;
	}

	/**
		Adjust contrast (range:-100 to 100)
			@return object
			@param $level int
	**/
	function contrast($level) {
		imagefilter($this->IMAGE,IMG_FILTER_CONTRAST,$level);
		return $this;
	}

	/**
		Convert to grayscale
			@return object
	**/
	function grayscale() {
		imagefilter($this->IMAGE,IMG_FILTER_GRAYSCALE);
		return $this;
	}

	/**
		Adjust smoothness
			@return object
			@param $level int
	**/
	function smooth($level) {
		imagefilter($this->IMAGE,IMG_FILTER_SMOOTH,$level);
		return $this;
	}

	/**
		Emboss the image
			@return object
	**/
	function emboss() {
		imagefilter($this->IMAGE,IMG_FILTER_EMBOSS);
		return $this;
	}

	/**
		Apply sepia effect
			@return object
	**/
	function sepia() {
		imagefilter($this->IMAGE,IMG_FILTER_GRAYSCALE);
		imagefilter($this->IMAGE,IMG_FILTER_COLORIZE,90,60,45);
		return $this;
	}

	/**
		Pixelate the image
			@return object
			@param $size int
	**/
	function pixelate($size) {
		imagefilter($this->IMAGE,IMG_FILTER_PIXELATE,$size,TRUE);
		return $this;
	}

	/**
		Blur the image using Gaussian filter
			@return object
			@param $selective bool
	**/
	function blur($selective=TRUE) {
		imagefilter($this->IMAGE,
			$selective?IMG_FILTER_SELECTIVE_BLUR:IMG_FILTER_GAUSSIAN_BLUR);
		return $this;
	}

	/**
		Apply sketch effect
			@return object
	**/
	function sketch() {
		imagefilter($this->IMAGE,IMG_FILTER_MEAN_REMOVAL);
		return $this;
	}

	/**
		Flip on horizontal axis
			@return object
	**/
	function hflip() {
		$tmp=imagecreatetruecolor(
			$width=imagesx($this->IMAGE),$height=imagesy($this->IMAGE));
		imagecopyresampled($tmp,
			$this->IMAGE,0,0,$width-1,0,$width,$height,-$width,$height);
		$this->IMAGE=$tmp;
		return $this;
	}

	/**
		Flip on vertical axis
			@return object
	**/
	function vflip() {
		$tmp=imagecreatetruecolor(
			$width=imagesx($this->IMAGE),$height=imagesy($this->IMAGE));
		imagecopyresampled($tmp,
			$this->IMAGE,0,0,0,$height-1,$width,$height,$width,-$height);
		$this->IMAGE=$tmp;
		return $this;
	}

	/**
		Resize image (aspect ratio retained)
			@return object
			@param $width int
			@param $height int
	**/
	function resize($width,$height) {
		// Adjust dimensions; retain aspect ratio
		$ratio=($oldx=imagesx($this->IMAGE))/($oldy=imagesy($this->IMAGE));
		if ($width/$ratio<=$height)
			// Adjust height
			$height=$width/$ratio;
		elseif ($height*$ratio<=$width)
			// Adjust width
			$width=$height*$ratio;
		else
			// Retain size if dimensions exceed original image
			list($width,$height)=array($oldx,$oldy);
		// Create blank image
		$tmp=imagecreatetruecolor($width,$height);
		// Resize
		imagecopyresampled($tmp,
			$this->IMAGE,0,0,0,0,$width,$height,$oldx,$oldy);
		$this->IMAGE=$tmp;
		return $this;
	}

	/**
		Generate identicon
			@return object
			@param $str string
			@param $size int
			@param $blocks int
	**/
	function identicon($str,$size=64,$blocks=4) {
		$sprites=array(
			array(.5,1,1,0,1,1),
			array(.5,0,1,0,.5,1,0,1),
			array(.5,0,1,0,1,1,.5,1,1,.5),
			array(0,.5,.5,0,1,.5,.5,1,.5,.5),
			array(0,.5,1,0,1,1,0,1,1,.5),
			array(1,0,1,1,.5,1,1,.5,.5,.5),
			array(0,0,1,0,1,.5,0,0,.5,1,0,1),
			array(0,0,.5,0,1,.5,.5,1,0,1,.5,.5),
			array(.5,0,.5,.5,1,.5,1,1,.5,1,.5,.5,0,.5),
			array(0,0,1,0,.5,.5,1,.5,.5,1,.5,.5,0,1),
			array(0,.5,.5,1,1,.5,.5,0,1,0,1,1,0,1),
			array(.5,0,1,0,1,1,.5,1,1,.75,.5,.5,1,.25),
			array(0,.5,.5,0,.5,.5,1,0,1,.5,.5,1,.5,.5,0,1),
			array(0,0,1,0,1,1,0,1,1,.5,.5,.25,.5,.75,0,.5,.5,.25),
			array(0,.5,.5,.5,.5,0,1,0,.5,.5,1,.5,.5,1,.5,.5,0,1),
			array(0,0,1,0,.5,.5,.5,0,0,.5,1,.5,.5,1,.5,.5,0,1)
		);
		$hash=md5($str);
		$fw=Base::instance();
		$adj=$blocks*(int)($size/$blocks);
		$identicon=imagecreatetruecolor($adj*2,$adj*2);
		$fg=call_user_func_array('imagecolorallocate',
			array_merge(array($identicon),
				$this->rgb(hexdec(substr($hash,0,6)))));
		$bg=call_user_func_array('imagecolorallocate',
			array_merge(array($identicon),$this->rgb($fw->get('BGCOLOR'))));
		imagefill($identicon,0,0,$bg);
		$ctr=count($sprites);
		$dim=$adj*2/$blocks;
		$len=strlen($hash);
		for ($j=0;$j<ceil($blocks/2);$j++)
			for ($i=$j;$i<$blocks-1-$j;$i++) {
				$sprite=imagecreatetruecolor($dim,$dim);
				imagefill($sprite,0,0,$bg);
				if ($block=$sprites[
					hexdec($hash[(($j*$blocks+$i)*2+5)%$len])%$ctr]) {
					for ($k=0,$pts=count($block);$k<$pts;$k++)
						$block[$k]=$block[$k]*$dim;
					imagefilledpolygon($sprite,$block,$pts/2,$fg);
				}
				$sprite=imagerotate($sprite,
					90*(hexdec($hash[(($j*$blocks+$i)*2+6)%$len])%4),$bg);
				for ($k=0;$k<4;$k++) {
					imagecopy($identicon,$sprite,
						$i*$dim,$j*$dim,0,0,$dim,$dim);
					$identicon=imagerotate($identicon,90,$bg);
				}
			}
		$this->IMAGE=imagecreatetruecolor($size,$size);
		imagecopyresampled($this->IMAGE,
			$identicon,0,0,0,0,$size,$size,$adj*2,$adj*2);
		return $this;
	}

	/**
		Grab HTML page and render using WebKit engine
			@return object|FALSE
			@param $url string
	**/
	function screenshot($url) {
		$fw=Base::instance();
		$cmd=$fw->get('WEBKIT').'wkhtmltoimage';
		$file=$fw->get('TEMP').
			$fw->hash(__DIR__).'.'.$fw->hash($url).'.jpg';
		if (preg_match('/^win/i',PHP_OS)) {
			$cmd=$fw->revslashes($cmd);
			$file=$fw->revslashes($file);
		}
		shell_exec($cmd.' '.$url.' '.$file.' 2>&1');
		$this->load($file);
		$fw->unlink($file);
		return $this;
	}

	/**
		Return image as string
			@return string
			@param $format string
	**/
	function dump($format='png') {
		ob_start();
		eval('image'.$format.'($this->IMAGE);');
		return ob_get_clean();
	}

	/**
		Send image to browser
			@return void
			@param $format string
	**/
	function render($format='png') {
		if (PHP_SAPI!='cli' && !headers_sent()) {
			header('Content-Type: image/'.$format);
			header('X-Powered-By: '.Base::instance()->get('PACKAGE'));
		}
		eval('image'.$format.'($this->IMAGE);');
	}

	/**
		Class constructor
			@return void
	**/
	function __construct() {
		if (extension_loaded('gd')) {
			$fw=Base::instance();
			if (!$fw->exists('BGCOLOR'))
				$fw->set('BGCOLOR',0xFFFFFF);
			if (!$fw->exists('WEBKIT'))
				$fw->set('WEBKIT','./');
		}
		else
			// Sockets extension required
			trigger_error(sprintf(self::TEXT_PHPExt,'gd2'));
	}

}
