<?php
/*
 * Date: 2011/06/27
 * Time: 4:33 PM
 */

class template {
	private $config = array(), $vars = array();

	function __construct($template, $folder = "") {
		$this->config['cache_dir'] = F3::get('TEMP');

		$ui = ($folder) ? $folder : F3::get('UI');

		$this->config['template_dir'] = $ui;
		$this->vars['folder'] = $ui;


		Haanga::Configure($this->config);
		$this->template = $template;

	}

	public function __get($name) {
		return $this->vars[$name];
	}

	public function __set($name, $value) {
		$this->vars[$name] = $value;
	}


	public function load() {

		ob_start();
		//ob_start("ob_gzhandler");
		// all pages get these
		$curPageFull = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$curPage = explode("?", $curPageFull);
		$_v = isset($_GET['v']) ? $_GET['v'] : F3::get('v');

		$this->vars['_version'] = F3::get('version');
		$this->vars['_v'] = $_v;
		$this->vars['isLocal'] = isLocal();
		$this->vars['_httpdomain'] = siteURL();

		if (isset($this->vars['page'])) {
			$page = $this->vars['page'];
			$tfile = $page['template'];
			if (file_exists('' . $this->vars['folder'] . '' . $tfile . '.tmpl')) {
				$page['template'] = $tfile . '.tmpl';
			} else {
				$page['template'] = "none";
			}
			if (file_exists('' . $this->vars['folder'] . '_js/' . $tfile . '.js')) {
				$page['template_js'] = '/min/js_' . $_v . '?file=/' . $this->vars['folder'] . '_js/' . $tfile . '.js';
			} else {
				$page['template_js'] = "";
			}
			if (file_exists('' . $this->vars['folder'] . '_css/' . $tfile . '.css')) {
				$page['template_css'] = '/min/css_'.$_v.'?file=/' . $this->vars['folder'] . '_css/' . $tfile . '.css';
			} else {
				$page['template_css'] = "";
			}
			if (file_exists('' . $this->vars['folder'] . '' . $tfile . '_templates.jtmpl')) {
				//exit('/tmpl?file=' . $tfile . '_templates.jtmpl');

				$file = '/' . $this->vars['folder'] . '' . $tfile . '_templates.jtmpl';

				$page['template_tmpl'] = '' . $tfile . '_templates.jtmpl';
			} else {
				$page['template_tmpl'] = "";
			}
			$this->vars['page'] = $page;
		} else {

		}


		if (!isset($this->vars['_domain'])) $this->vars['_domain'] = F3::get('DOMAIN');


		//echo $this->config['template_dir'];
		Haanga::load($this->template, $this->vars);

		$t = ob_get_contents();
		ob_end_clean();


		if (!isLocal()) {
			$t = sanitize_output($t);
		}
		return ($t);

	}
	public function output(){
		F3::set("__runTemplate", true);
		//ob_start();
		echo $this->load();

		//$pageoutput = ob_get_contents();
		//ob_end_clean();
		//$GLOBALS['render'] = $pageoutput;
	}
}
