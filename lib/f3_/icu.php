<?php

//! Internationalization support
class ICU extends Registry {

	private static
		//! Active locale
		$LOCALE,
		//! Character set
		$ENCODING;

	/**
		Format string according to ICU rules
			@return string
			@param $val scalar
			@param $args scalar|array
	**/
	function format($val,$args) {
		$args=is_array($args)?$args:array($args);
		if (extension_loaded('intl'))
			return msgfmt_format_message(locale_get_default(),$val,$args);
		setlocale(LC_ALL,self::$LOCALE);
		$info=localeconv();
		if (preg_match('/^win/i',PHP_OS))
			foreach ($info as &$cfg)
				if (is_string($cfg))
					$cfg=iconv('Windows-1252',self::$ENCODING,$cfg);
		return preg_replace_callback(
			'/{(?P<index>\d+)(?:,(?P<format>\w+)(?:,(?P<type>\w+))?)?}/',
			function($expr) use($args,$info) {
				if (!isset($expr['format']))
					return $args[$expr['index']];
				switch ($expr['format']) {
					case 'number':
						if (!isset($expr['type']))
							return sprintf('%f',$args[$expr['index']]);
						switch ($expr['type']) {
							case 'integer':
								return
									number_format(
										$args[$expr['index']],0,'',
										$info['thousands_sep']);
							case 'currency':
								return
									$info['currency_symbol'].
									number_format(
										$args[$expr['index']],
										$info['frac_digits'],
										$info['decimal_point'],
										$info['thousands_sep']);
							case 'percent':
								return
									number_format(
										$args[$expr['index']]*100,0,
										$info['decimal_point'],
										$info['thousands_sep']).'%';
						}
					case 'date':
						return strftime(!isset($expr['type']) ||
							$expr['type']=='short'?'%x':'%a, %d %b %Y',
							$args[$expr['index']]);
					case 'time':
						return strftime('%X',$args[$expr['index']]);
					default:
						return $args[$expr['index']];
				}
			},
			$val
		);
	}

	/**
		Load dictionary (in ISO 639-1 format); Return primary language
			@return string
			@param $lang string|NULL
	**/
	function load($lang) {
		$lang=strtolower(str_replace('_','-',$lang));
		$fw=Base::instance();
		self::$ENCODING=$fw->get('ENCODING');
		if (extension_loaded('intl')) {
			if (!$lang)
				$lang=isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])?
					locale_accept_from_http(
						$_SERVER['HTTP_ACCEPT_LANGUAGE']):
					locale_get_default();
			locale_set_default($lang);
		}
		else {
			self::$LOCALE=array();
			if (!$lang && isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
				$lang=preg_replace('/^(\w+(?:-\w+)?)\b.+/','\1',
					$_SERVER['HTTP_ACCEPT_LANGUAGE']);
			else
				$orig=setlocale(LC_ALL,NULL);
			if (preg_match('/win/i',PHP_OS)) {
				$parts=explode('-',$lang);
				if (isset($parts[1]))
					self::$LOCALE=array(
						@constant('ISO::LANGUAGE_'.$parts[0]).'_'.
						@constant('ISO::COUNTRY_'.strtolower($parts[1])),
						@constant('ISO::LANGUAGE_'.$parts[0])
					);
				if (!self::$LOCALE)
					self::$LOCALE=array(
						@constant('ISO::LANGUAGE_'.$lang)
					);
			}
			array_push(self::$LOCALE,
				$loc=preg_replace_callback('/^(\w+)(?:-(\w+))?/',
					function($parts) use($fw) {
						return $parts[1].
							(isset($parts[2])?
								('_'.strtoupper($parts[2])):'');
					},
					$lang
				),
				$loc.'.'.self::$ENCODING
			);
			if (isset($orig))
				setlocale(LC_ALL,$orig);
		}
		$list=array($lang);
		if (preg_match('/^(\w+)-\w+/',$lang,$parts))
			// Add proto-language
			array_unshift($list,$parts[1]);
		if ($list[0]!='en')
			// Add English as fallback
			array_unshift($list,'en');
		foreach ($list as $val)
			if (is_file($file=$fw->get('LOCALES').$val.'.dic'))
				// Combine dictionaries and assign key/value pairs
				$fw->config($file);
		return $lang;
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
		if (!$fw->exists('LOCALES'))
			$fw->set('LOCALES','./');
	}

}
