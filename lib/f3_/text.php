<?php

class Text extends Registry {

	/**
		Convert snakecase string to camelcase
			@return string
			@param $str string
	**/
	function camel($str) {
		return preg_replace_callback(
			'/_(\w)/',
			function($match) {
				return strtoupper($match[1]);
			},
			$str
		);
	}

	/**
		Convert camelcase string to snakecase
			@return string
			@param $str string
	**/
	function snake($str) {
		return strtolower(preg_replace('/[[:upper:]]/','_\0',$str));
	}

	/**
		Compare arrays using Paul Butler's simple diff algorithm
		<http://www.paulbutler.org/>
			@return array
			@param $old array
			@param $new array
	**/
	private function adiff(array $old,array $new) {
		$matrix=array();
		$maxlen=0;
		foreach ($old as $ondx=>$val) {
			// Retrieve keys with the same substring
			$nkeys=array_keys($new,$val);
			foreach ($nkeys as $nndx) {
				$matrix[$ondx][$nndx]=isset($matrix[$ondx-1][$nndx-1])?
					$matrix[$ondx-1][$nndx-1]+1:1;
				if ($matrix[$ondx][$nndx]>$maxlen) {
					$maxlen=$matrix[$ondx][$nndx];
					$omax=$ondx+1-$maxlen;
					$nmax=$nndx+1-$maxlen;
				}
			}
		}
		return $maxlen?
			array_merge(
				$this->adiff(
					array_slice($old,0,$omax),
					array_slice($new,0,$nmax)
				),
				array_slice($new,$nmax,$maxlen),
				$this->adiff(
					array_slice($old,$omax+$maxlen),
					array_slice($new,$nmax+$maxlen)
				)
			):
			($old || $new?array(array('d'=>$old,'a'=>$new)):array());
	}

	/**
		Output the difference between two strings; Return array containing
		patch (normal GNU diff format) or equivalent HTML; Perform
		line-by-line comparison if no delimiter is specified
			@return string
			@param $old string
			@param $new string
			@param $delim string
			@param $html bool
	**/
	function diff($old,$new,$delim="\n",$html=FALSE){
		$diff=$this->adiff(
			$delim?explode($delim,$old):str_split($old),
			$delim?explode($delim,$new):str_split($new)
		);
		$ofs1=0;
		$ofs2=0;
		$patch='';
		$html='';
		foreach ($diff as $key=>$val)
			if (is_array($val)) {
				$pos1=$key+$ofs1;
				$pos2=$key+$ofs2;
				$ctrd=count($val['d']);
				$ctra=count($val['a']);
				if ($val['d'])
					$html.='<del>'.implode($delim,$val['d']).'</del>'.$delim;
				if ($val['a'])
					$html.='<ins>'.implode($delim,$val['a']).'</ins>'.$delim;
				// Build patch
				if ($val['d'] && $val['a']) {
					// Change hunk
					$patch.=($pos1+1).($ctrd>1?(','.($pos1+$ctrd)):'').'c'.
						($pos2+1).($ctra>1?(','.($pos2+$ctra)):'')."\n";
					for ($i=0;$i<$ctrd;$i++)
						$patch.='< '.$val['d'][$i]."\n";
					$patch.='---'."\n";
					for ($i=0;$i<$ctra;$i++)
						$patch.='> '.$val['a'][$i]."\n";
				}
				elseif ($val['d']) {
					$patch.=($pos1+1).($ctrd>1?(','.($pos1+$ctrd)):'').'d'.
						($pos2)."\n";
					for ($i=0;$i<$ctrd;$i++)
						$patch.='< '.$val['d'][$i]."\n";
				}
				elseif ($val['a']) {
					$patch.=($pos1).'a'.
						($pos2+1).($ctra>1?(','.($pos2+$ctra)):'')."\n";
					for ($i=0;$i<$ctra;$i++)
						$patch.='> '.$val['a'][$i]."\n";
				}
				$ofs1+=$ctrd-1;
				$ofs2+=$ctra-1;
			}
			else
				$html.=$val.$delim;
		return $html?$patch:$html;
	}

	/**
		Apply patch to input string; Return trimmed result
			@return string
			@param $str string
			@param $patch string
			@param $delim string
			@param $rev bool
	**/
	function patch($str,$patch,$delim="\n",$rev=FALSE) {
		$new=$delim?explode($delim,$str):str_split($str);
		preg_match_all(
			'/(\d+)(?:,(\d+))?([cda])(\d+)(?:,(\d+))?\n'.
			'(.+?\n)(?=$|[\d,]+[cda][\d,]+)/s',
			$patch,$matches,PREG_SET_ORDER);
		$ofs=0;
		foreach ($matches as $match) {
			if (!$match[2])
				$match[2]=$match[1];
			if (!$match[5])
				$match[5]=$match[4];
			preg_match_all('/(?<=<\x20)(.*?)(?:\n(?:<\x20|>\x20|---|$))/s',
				$match[6],$del);
			preg_match_all('/(?<=>\x20)(.*?)(?:\n(?:<\x20|>\x20|---|$))/s',
				$match[6],$add);
			if ($del[1] || $add[1]) {
				if ($rev) {
					// Reverse patch; Swap positions
					list($match[1],$match[2])=array($match[4],$match[5]);
					list($add[1],$del[1])=array($del[1],$add[1]);
					if ($match[3]!='c')
						$match[3]=($match[3]=='a')?'d':'a';
				}
				$ctr=count($del[1]);
				if ($match[3]=='a') {
					$new=array_merge(
						array_slice($new,0,$match[1]+$ofs),
						array(array('d'=>$del[1],'a'=>$add[1])),
						array_slice($new,$match[1]+$ofs)
					);
					$ofs++;
				}
				else {
					$new=array_merge(
						array_slice($new,0,$match[1]-1+$ofs),
						array(array('d'=>$del[1],'a'=>$add[1])),
						array_slice($new,$match[1]-1+$ctr+$ofs)
					);
					$ofs-=$ctr-1;
				}
			}
		}
		$out='';
		foreach ($new as $val) {
			if (!is_array($val))
				$out.=$val.$delim;
			elseif ($val['a'])
				$out.=implode($delim,$val['a']).$delim;
		}
		return rtrim($out,$delim);
	}

}
