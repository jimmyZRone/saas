<?php
namespace Core\Mvc;
/**
 * 路由
 * @author lishengyou
 * 最后修改时间 2015年2月9日 上午11:08:43
 *
 */
class Template{
	const MODE_PHP = 1;
	const MODE_SMARTY = 2;
	protected $_smarty = null;
	protected $_mode = 2;
	protected $_path = null;
	protected $_assign = array();
	public function __construct($mode = self::MODE_SMARTY){
		$this->_mode = $mode;
		if($this->_mode == self::MODE_SMARTY){
			$this->_smarty = new \Smarty();
			$compiledir = CACHE_DIR.'smarty/template_c';
			if(!is_dir($compiledir)) mkdir($compiledir,0777,true);
			$this->_smarty->setCompileDir($compiledir);
			$cachedir = CACHE_DIR.'smarty/cache';
			if(!is_dir($cachedir)) mkdir($cachedir,0777,true);
			$this->_smarty->setCacheDir($cachedir);
			$this->_smarty->left_delimiter = '{{';
			$this->_smarty->right_delimiter = '}}';
			$this->_smarty->debugging = FALSE;
			$this->_smarty->caching = FALSE;
		}
	}
	/**
	 * 设置模版目录
	 * @author lishengyou
	 * 最后修改时间 2015年2月28日 上午11:13:10
	 *
	 * @param unknown $path
	 */
	public function setTemplateDir($path){
		$path = realpath($path);
		if($path){
			$this->_path = $path;
		}
		if($path && $this->_mode == self::MODE_SMARTY){
			$this->_smarty->setTemplateDir($this->_path);
		}
		return $this;
	}
	/**
	 * 注册函数
	 * @author lishengyou
	 * 最后修改时间 2015年3月10日 下午5:29:34
	 *
	 * @param unknown $funname
	 * @param unknown $fun
	 */
	public function registerPlugin($plugintype,$funname,$fun){
		if(is_string($fun) && strpos($fun, '::')){
			$funp = $fun = explode('::', $fun,2);
		}
		if($this->_mode == self::MODE_SMARTY){
			$this->_smarty->registerPlugin($plugintype,$funname,$fun);
		}
	}
	/**
	 * 设置调试
	 * @author lishengyou
	 * 最后修改时间 2015年2月28日 上午11:09:42
	 *
	 * @param unknown $boolean
	 */
	public function debugging($boolean){
		$boolean = !!$boolean;
		if($this->_mode == self::MODE_SMARTY){
			$this->_smarty->debugging = $boolean;
			$this->_smarty->allow_php_templates = $boolean;
		}
		return $this;
	}
	/**
	 * 注入变量
	 * @author lishengyou
	 * 最后修改时间 2015年2月28日 上午11:11:29
	 *
	 * @param unknown $assignname
	 * @param unknown $assignvalue
	 * @param unknown $nocache
	 */
	public function assign($assignname,$assignvalue,$nocache = false){
		$this->_assign[$assignname] = $assignvalue;
		if($this->_mode == self::MODE_SMARTY){
			$this->_smarty->assign($assignname,$assignvalue,$nocache);
		}
		return $this;
	}
	/**
	 * 生成内容
	 * @author lishengyou
	 * 最后修改时间 2015年3月4日 上午9:30:43
	 *
	 * @param string $template
	 * @param string $cache_id
	 * @param string $compile_id
	 * @param string $parent
	 * @param string $display
	 * @param string $merge_tpl_vars
	 * @param string $no_output_filter
	 * @return Ambigous <string, void, string>
	 */
	public function fetch($template = null, $cache_id = null, $compile_id = null, $parent = null, $display = false, $merge_tpl_vars = true, $no_output_filter = false){
		if($this->_mode == self::MODE_SMARTY){
			$data = $this->_smarty->fetch($template,$cache_id,$compile_id,$parent,$display,$merge_tpl_vars,$no_output_filter);
		}else{
			$tpl_data = $this->_assign;
			ob_start();
			include $this->_path .'/'. $template;
			$data = ob_get_contents();
			ob_end_clean();
		}
		$this->_assign = array();
		$this->_path = null;
		return $data;
	}
	/**
	 * 现实模板
	 * @author lishengyou
	 * 最后修改时间 2015年2月28日 上午11:10:35
	 *
	 * @param unknown $tpl
	 */
	public function display($tpl){
		$this->_assign = array();
		$this->_path = null;
		if($this->_mode == self::MODE_SMARTY){
			$this->_smarty->display($tpl);
		}else{
			$tpl_data = $this->_assign;
			include $this->_path .'/'. $template;
			$data = ob_get_contents();
		}
	}
}