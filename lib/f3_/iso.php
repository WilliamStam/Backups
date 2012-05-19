<?php

class ISO extends Registry {

	//@{ ISO 639-1 language codes (Windows-compatibility subset)
	const
		LANGUAGE_af='Afrikaans',
		LANGUAGE_am='Amharic',
		LANGUAGE_ar='Arabic',
		LANGUAGE_as='Assamese',
		LANGUAGE_ba='Bashkir',
		LANGUAGE_be='Belarusian',
		LANGUAGE_bg='Bulgarian',
		LANGUAGE_bn='Bengali',
		LANGUAGE_bo='Tibetan',
		LANGUAGE_br='Breton',
		LANGUAGE_ca='Catalan',
		LANGUAGE_co='Corsican',
		LANGUAGE_cs='Czech',
		LANGUAGE_cy='Welsh',
		LANGUAGE_da='Danish',
		LANGUAGE_de='German',
		LANGUAGE_dv='Divehi',
		LANGUAGE_el='Greek',
		LANGUAGE_en='English',
		LANGUAGE_es='Spanish',
		LANGUAGE_et='Estonian',
		LANGUAGE_eu='Basque',
		LANGUAGE_fa='Persian',
		LANGUAGE_fi='Finnish',
		LANGUAGE_fo='Faroese',
		LANGUAGE_fr='French',
		LANGUAGE_gd='Scottish Gaelic',
		LANGUAGE_gl='Galician',
		LANGUAGE_gu='Gujarati',
		LANGUAGE_he='Hebrew',
		LANGUAGE_hi='Hindi',
		LANGUAGE_hr='Croatian',
		LANGUAGE_hu='Hungarian',
		LANGUAGE_hy='Armenian',
		LANGUAGE_id='Indonesian',
		LANGUAGE_ig='Igbo',
		LANGUAGE_is='Icelandic',
		LANGUAGE_it='Italian',
		LANGUAGE_ja='Japanese',
		LANGUAGE_ka='Georgian',
		LANGUAGE_kk='Kazakh',
		LANGUAGE_km='Khmer',
		LANGUAGE_kn='Kannada',
		LANGUAGE_ko='Korean',
		LANGUAGE_lb='Luxembourgish',
		LANGUAGE_lo='Lao',
		LANGUAGE_lt='Lithuanian',
		LANGUAGE_lv='Latvian',
		LANGUAGE_mi='Maori',
		LANGUAGE_ml='Malayalam',
		LANGUAGE_mr='Marathi',
		LANGUAGE_ms='Malay',
		LANGUAGE_mt='Maltese',
		LANGUAGE_ne='Nepali',
		LANGUAGE_nl='Dutch',
		LANGUAGE_no='Norwegian',
		LANGUAGE_oc='Occitan',
		LANGUAGE_or='Oriya',
		LANGUAGE_pl='Polish',
		LANGUAGE_ps='Pashto',
		LANGUAGE_pt='Portuguese',
		LANGUAGE_qu='Quechua',
		LANGUAGE_ro='Romanian',
		LANGUAGE_ru='Russian',
		LANGUAGE_rw='Kinyarwanda',
		LANGUAGE_sa='Sanskrit',
		LANGUAGE_si='Sinhala',
		LANGUAGE_sk='Slovak',
		LANGUAGE_sl='Slovenian',
		LANGUAGE_sq='Albanian',
		LANGUAGE_sv='Swedish',
		LANGUAGE_ta='Tamil',
		LANGUAGE_te='Telugu',
		LANGUAGE_th='Thai',
		LANGUAGE_tk='Turkmen',
		LANGUAGE_tr='Turkish',
		LANGUAGE_tt='Tatar',
		LANGUAGE_uk='Ukrainian',
		LANGUAGE_ur='Urdu',
		LANGUAGE_vi='Vietnamese',
		LANGUAGE_wo='Wolof',
		LANGUAGE_yo='Yoruba',
		LANGUAGE_zh='Chinese';
	//@}

	//@{ ISO 3166-1 country codes
	const
		COUNTRY_af='Afghanistan',
		COUNTRY_ax='Åland Islands',
		COUNTRY_al='Albania',
		COUNTRY_dz='Algeria',
		COUNTRY_as='American Samoa',
		COUNTRY_ad='Andorra',
		COUNTRY_ao='Angola',
		COUNTRY_ai='Anguilla',
		COUNTRY_aq='Antarctica',
		COUNTRY_ag='Antigua and Barbuda',
		COUNTRY_ar='Argentina',
		COUNTRY_am='Armenia',
		COUNTRY_aw='Aruba',
		COUNTRY_au='Australia',
		COUNTRY_at='Austria',
		COUNTRY_az='Azerbaijan',
		COUNTRY_bs='Bahamas',
		COUNTRY_bh='Bahrain',
		COUNTRY_bd='Bangladesh',
		COUNTRY_bb='Barbados',
		COUNTRY_by='Belarus',
		COUNTRY_be='Belgium',
		COUNTRY_bz='Belize',
		COUNTRY_bj='Benin',
		COUNTRY_bm='Bermuda',
		COUNTRY_bt='Bhutan',
		COUNTRY_bo='Bolivia',
		COUNTRY_ba='Bosnia and Herzegovina',
		COUNTRY_bw='Botswana',
		COUNTRY_bv='Bouvet Island',
		COUNTRY_br='Brazil',
		COUNTRY_io='British Indian Ocean Territory',
		COUNTRY_bn='Brunei Darussalam',
		COUNTRY_bg='Bulgaria',
		COUNTRY_bf='Burkina Faso',
		COUNTRY_bi='Burundi',
		COUNTRY_kh='Cambodia',
		COUNTRY_cm='Cameroon',
		COUNTRY_ca='Canada',
		COUNTRY_cv='Cape Verde',
		COUNTRY_ky='Cayman Islands',
		COUNTRY_cf='Central African Republic',
		COUNTRY_td='Chad',
		COUNTRY_cl='Chile',
		COUNTRY_cn='China',
		COUNTRY_cx='Christmas Island',
		COUNTRY_cc='Cocos (Keeling) Islands',
		COUNTRY_co='Colombia',
		COUNTRY_km='Comoros',
		COUNTRY_cg='Congo',
		COUNTRY_cd='Congo, The Democratic Republic of',
		COUNTRY_ck='Cook Islands',
		COUNTRY_cr='Costa Rica',
		COUNTRY_ci='Côte D\'ivoire',
		COUNTRY_hr='Croatia',
		COUNTRY_cu='Cuba',
		COUNTRY_cw='Curaçao',
		COUNTRY_cy='Cyprus',
		COUNTRY_cz='Czech Republic',
		COUNTRY_dk='Denmark',
		COUNTRY_dj='Djibouti',
		COUNTRY_dm='Dominica',
		COUNTRY_do='Dominican Republic',
		COUNTRY_ec='Ecuador',
		COUNTRY_eg='Egypt',
		COUNTRY_sv='El Salvador',
		COUNTRY_gq='Equatorial Guinea',
		COUNTRY_er='Eritrea',
		COUNTRY_ee='Estonia',
		COUNTRY_et='Ethiopia',
		COUNTRY_fk='Falkland Islands (Malvinas)',
		COUNTRY_fo='Faroe Islands',
		COUNTRY_fj='Fiji',
		COUNTRY_fi='Finland',
		COUNTRY_fr='France',
		COUNTRY_gf='French Guiana',
		COUNTRY_pf='French Polynesia',
		COUNTRY_tf='French Southern Territories',
		COUNTRY_ga='Gabon',
		COUNTRY_gm='Gambia',
		COUNTRY_ge='Georgia',
		COUNTRY_de='Germany',
		COUNTRY_gh='Ghana',
		COUNTRY_gi='Gibraltar',
		COUNTRY_gr='Greece',
		COUNTRY_gl='Greenland',
		COUNTRY_gd='Grenada',
		COUNTRY_gp='Guadeloupe',
		COUNTRY_gu='Guam',
		COUNTRY_gt='Guatemala',
		COUNTRY_gg='Guernsey',
		COUNTRY_gn='Guinea',
		COUNTRY_gw='Guinea-Bissau',
		COUNTRY_gy='Guyana',
		COUNTRY_ht='Haiti',
		COUNTRY_hm='Heard Island and Mcdonald Islands',
		COUNTRY_va='Holy See (Vatican City State)',
		COUNTRY_hn='Honduras',
		COUNTRY_hk='Hong Kong',
		COUNTRY_hu='Hungary',
		COUNTRY_is='Iceland',
		COUNTRY_in='India',
		COUNTRY_id='Indonesia',
		COUNTRY_ir='Iran, Islamic Republic of',
		COUNTRY_iq='Iraq',
		COUNTRY_ie='Ireland',
		COUNTRY_im='Isle of Man ',
		COUNTRY_il='Israel',
		COUNTRY_it='Italy',
		COUNTRY_jm='Jamaica',
		COUNTRY_jp='Japan',
		COUNTRY_je='Jersey ',
		COUNTRY_jo='Jordan',
		COUNTRY_kz='Kazakhstan',
		COUNTRY_ke='Kenya',
		COUNTRY_ki='Kiribati',
		COUNTRY_kp='Korea, Democratic People\'s Republic of',
		COUNTRY_kr='Korea, Republic of',
		COUNTRY_kw='Kuwait',
		COUNTRY_kg='Kyrgyzstan',
		COUNTRY_la='Lao People\'s Democratic Republic',
		COUNTRY_lv='Latvia',
		COUNTRY_lb='Lebanon',
		COUNTRY_ls='Lesotho',
		COUNTRY_lr='Liberia',
		COUNTRY_ly='Libyan Arab Jamahiriya',
		COUNTRY_li='Liechtenstein',
		COUNTRY_lt='Lithuania',
		COUNTRY_lu='Luxembourg',
		COUNTRY_mo='Macao',
		COUNTRY_mk='Macedonia, The Former Yugoslav Republic of',
		COUNTRY_mg='Madagascar',
		COUNTRY_mw='Malawi',
		COUNTRY_my='Malaysia',
		COUNTRY_mv='Maldives',
		COUNTRY_ml='Mali',
		COUNTRY_mt='Malta',
		COUNTRY_mh='Marshall Islands',
		COUNTRY_mq='Martinique',
		COUNTRY_mr='Mauritania',
		COUNTRY_mu='Mauritius',
		COUNTRY_yt='Mayotte',
		COUNTRY_mx='Mexico',
		COUNTRY_fm='Micronesia, Federated States of',
		COUNTRY_md='Moldova, Republic of',
		COUNTRY_mc='Monaco',
		COUNTRY_mn='Mongolia',
		COUNTRY_ms='Montserrat',
		COUNTRY_ma='Morocco',
		COUNTRY_mz='Mozambique',
		COUNTRY_mm='Myanmar',
		COUNTRY_na='Namibia',
		COUNTRY_nr='Nauru',
		COUNTRY_np='Nepal',
		COUNTRY_nl='Netherlands',
		COUNTRY_an='Netherlands Antilles',
		COUNTRY_nc='New Caledonia',
		COUNTRY_nz='New Zealand',
		COUNTRY_ni='Nicaragua',
		COUNTRY_ne='Niger',
		COUNTRY_ng='Nigeria',
		COUNTRY_nu='Niue',
		COUNTRY_nf='Norfolk Island',
		COUNTRY_mp='Northern Mariana Islands',
		COUNTRY_no='Norway',
		COUNTRY_om='Oman',
		COUNTRY_pk='Pakistan',
		COUNTRY_pw='Palau',
		COUNTRY_ps='Palestinian Territory, Occupied',
		COUNTRY_pa='Panama',
		COUNTRY_pg='Papua New Guinea',
		COUNTRY_py='Paraguay',
		COUNTRY_pe='Peru',
		COUNTRY_ph='Philippines',
		COUNTRY_pn='Pitcairn',
		COUNTRY_pl='Poland',
		COUNTRY_pt='Portugal',
		COUNTRY_pr='Puerto Rico',
		COUNTRY_qa='Qatar',
		COUNTRY_re='Réunion',
		COUNTRY_ro='Romania',
		COUNTRY_ru='Russian Federation',
		COUNTRY_rw='Rwanda',
		COUNTRY_sh='Saint Helena',
		COUNTRY_kn='Saint Kitts and Nevis',
		COUNTRY_lc='Saint Lucia',
		COUNTRY_pm='Saint Pierre and Miquelon',
		COUNTRY_vc='Saint Vincent and The Grenadines',
		COUNTRY_ws='Samoa',
		COUNTRY_sm='San Marino',
		COUNTRY_st='Sao Tome and Principe',
		COUNTRY_sa='Saudi Arabia',
		COUNTRY_sn='Senegal',
		COUNTRY_cs='Serbia and Montenegro',
		COUNTRY_sc='Seychelles',
		COUNTRY_sl='Sierra Leone',
		COUNTRY_sg='Singapore',
		COUNTRY_sk='Slovakia',
		COUNTRY_si='Slovenia',
		COUNTRY_sb='Solomon Islands',
		COUNTRY_so='Somalia',
		COUNTRY_za='South Africa',
		COUNTRY_gs='South Georgia and The South Sandwich Islands',
		COUNTRY_es='Spain',
		COUNTRY_lk='Sri Lanka',
		COUNTRY_sd='Sudan',
		COUNTRY_sr='Suriname',
		COUNTRY_sj='Svalbard and Jan Mayen',
		COUNTRY_sz='Swaziland',
		COUNTRY_se='Sweden',
		COUNTRY_ch='Switzerland',
		COUNTRY_sy='Syrian Arab Republic',
		COUNTRY_tw='Taiwan, Province of China',
		COUNTRY_tj='Tajikistan',
		COUNTRY_tz='Tanzania, United Republic of',
		COUNTRY_th='Thailand',
		COUNTRY_tl='Timor-Leste',
		COUNTRY_tg='Togo',
		COUNTRY_tk='Tokelau',
		COUNTRY_to='Tonga',
		COUNTRY_tt='Trinidad and Tobago',
		COUNTRY_tn='Tunisia',
		COUNTRY_tr='Turkey',
		COUNTRY_tm='Turkmenistan',
		COUNTRY_tc='Turks and Caicos Islands',
		COUNTRY_tv='Tuvalu',
		COUNTRY_ug='Uganda',
		COUNTRY_ua='Ukraine',
		COUNTRY_ae='United Arab Emirates',
		COUNTRY_gb='United Kingdom',
		COUNTRY_us='United States',
		COUNTRY_um='United States Minor Outlying Islands',
		COUNTRY_uy='Uruguay',
		COUNTRY_uz='Uzbekistan',
		COUNTRY_vu='Vanuatu',
		COUNTRY_ve='Venezuela',
		COUNTRY_vn='Viet Nam',
		COUNTRY_vg='Virgin Islands, British',
		COUNTRY_vi='Virgin Islands, U.S.',
		COUNTRY_wf='Wallis and Futuna',
		COUNTRY_eh='Western Sahara',
		COUNTRY_ye='Yemen',
		COUNTRY_zm='Zambia',
		COUNTRY_zw='Zimbabwe';
	//@}

	/**
		Return list of languages indexed by ISO 639-1 language code
			@return array
	**/
	function languages() {
		$self=new ReflectionClass($this);
		$out=array();
		foreach (preg_grep('/LANGUAGE_/',
			array_keys($self->getconstants())) as $val)
			$out[$key=substr(strstr($val,'_'),1)]=
				constant('self::LANGUAGE_'.$key);
		return $out;
	}

	/**
		Return list of countries indexed by ISO 3166-1 country code
			@return array
	**/
	function countries() {
		$self=new ReflectionClass($this);
		$out=array();
		foreach (preg_grep('/COUNTRY_/',
			array_keys($self->getconstants())) as $val)
			$out[$key=substr(strstr($val,'_'),1)]=
				constant('self::COUNTRY_'.$key);
		return $out;
	}

}
