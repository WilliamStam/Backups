<?php

class Geo extends Registry {

	/**
		Return zoneinfo array indexed by Unix time zone
			@return array
	**/
	function timezones() {
		$zone=array();
		$now=time();
		foreach (DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC)
			as $id) {
			$ref=new DateTimeZone($id);
			$loc=$ref->getLocation();
			$trn=$ref->getTransitions($now,$now);
			$zone[$id]=array(
				'offset'=>$ref->getOffset(new DateTime('now',
					new DateTimeZone('GMT')))/3600,
				'country'=>$loc['country_code'],
				'latitude'=>$loc['latitude'],
				'longitude'=>$loc['longitude'],
				'dst'=>$trn[0]['isdst']
			);
		}
		return $zone;
	}

	/**
		Return array describing weather conditions for specific location;
		if an error occurs, return FALSE
			@return mixed
			@param $latitude float
			@param $longitude float
	**/
	function weather($latitude,$longitude) {
		$response=Web::instance()->request(
			'http://ws.geonames.org/findNearByWeatherJSON?'.
			http_build_query(
				array(
					'username'=>Web::instance()->realip(),
					'lat'=>$latitude,
					'lng'=>$longitude
				)
			)
		);
		if ($response) {
			$result=json_decode($response['body'],TRUE);
			if (isset($result['weatherObservation']))
				return $result['weatherObservation'];
		}
		trigger_error($result['status']['message']);
		return FALSE;
	}

	/**
		Return geodata based on IP address, or FALSE on failure
			@return mixed
			@param $ip string
	**/
	function location($ip=NULL) {
		if (!$ip)
			$ip=Web::instance()->realip();
		$response=Web::instance()->
			request('http://www.geoplugin.net/php.gp?'.$ip);
		if ($response) {
			$result=array();
			foreach (unserialize($response['body']) as $key=>$val)
				$result[str_replace('geoplugin_','',$key)]=$val;
			return $result;
		}
		return FALSE;
	}

}
