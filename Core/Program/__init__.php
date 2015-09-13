<?php
namespace Core;
/**
 * 程序
 * @author lishengyou
 * 最后修改时间 2015年2月11日 下午1:33:53
 *
 */
abstract class Program{
	const RANK_PROGRAM = 0;
	const RANK_SYSTRM = 1;
	const RANK_INTERNAL = 2;
	const RANK_EXTERNAL = 3;
	protected function __construct(){
		$this->_programid = get_class($this);
	}
	/**
	 * 取得任务等级
	 * @author lishengyou
	 * 最后修改时间 2015年2月12日 下午2:23:45
	 *
	 * @param \Core\Program $program
	 */
	final public static function getProgramRank(\Core\Program $program){
		if($program instanceof Program\System){
			return self::RANK_SYSTRM;
		}
		if($program instanceof Program\Internal){
			return self::RANK_INTERNAL;
		}
		if($program instanceof Program\External){
			return self::RANK_EXTERNAL;
		}
		return self::RANK_PROGRAM;
	}
	protected static function __init__($args){
		return null;
	}
	
	protected $_allowreplace = false;
	/**
	 * 是否允许替换
	 * @author lishengyou
	 * 最后修改时间 2015年2月28日 下午3:44:22
	 *
	 * @param string $boolean
	 * @return boolean
	 */
	public function allowReplace($boolean=null){
		if(is_null($boolean)){
			return $this->_allowreplace;
		}
		$this->_allowreplace = !!$boolean;
	}
	
	protected $_allowinsertfront = false;
	/**
	 * 是否允许向前插入
	 * @author lishengyou
	 * 最后修改时间 2015年2月28日 下午3:44:22
	 *
	 * @param string $boolean
	 * @return boolean
	 */
	public function allowFrontInsert($boolean=null){
		if(is_null($boolean)){
			return $this->_allowinsertfront;
		}
		$this->_allowinsertfront = !!$boolean;
	}
	
	protected $_allowinsertpost = false;
	/**
	 * 是否允许向后插入
	 * @author lishengyou
	 * 最后修改时间 2015年2月28日 下午3:44:22
	 *
	 * @param string $boolean
	 * @return boolean
	 */
	public function allowPostInsert($boolean=null){
		if(is_null($boolean)){
			return $this->_allowinsertpost;
		}
		$this->_allowinsertpost = !!$boolean;
	}
	
	protected $_programid = null;
	/**
	 * 取得程序ID
	 * @author lishengyou
	 * 最后修改时间 2015年2月28日 下午3:48:26
	 *
	 * @return string
	 */
	public function getProgramId(){
		return $this->_programid ? $this->_programid : get_class($this);
	}
	/**
	 * 初始化运行
	 * @author lishengyou
	 * 最后修改时间 2015年2月26日 上午11:13:21
	 *
	 */
	protected function initRun(){
		
	}
	/**
	 * 取得程序配置
	 * @author lishengyou
	 * 最后修改时间 2015年3月2日 下午1:18:04
	 *
	 * @return Ambigous <multitype:, unknown>
	 */
	final public function getConfig(){
		$classname = get_class($this);
		$path = str_replace('\\', '/', $classname);
		$path = ROOT_DIR.$path.'/config.ini';
		$config = @parse_ini_file($path,true);
		return $config ? $config : array();
	}
	/**
	 * 获取插件配置
	 * @return multitype:multitype:
	 */
	final public function getPluginsConfig($dir='System')
	{
		$Third_path=ROOT_DIR."/Plugins/".$dir."/config.ini";
		$Third_config=parse_ini_file($Third_path,true);
		$pash_str=str_replace(array("[","]"), "", $Third_config['plugins_pash']);
		$config_arr=explode(",", $pash_str);
		$output_arr=array();
		if (!empty($config_arr))
		{
			foreach ($config_arr as $val)
			{
				if (is_file(ROOT_DIR.$val."/config.ini"))
				{
					$output_arr[]=parse_ini_file(ROOT_DIR.$val."/config.ini");
				}else 
				{
					continue;
				}
				
			}
		}
		return $output_arr;
	}
	/**
	 * 初始化
	 * @author lishengyou
	 * 最后修改时间 2015年2月9日 上午11:29:58
	 *
	 * @throws \Exception
	 * @return unknown
	 */
	public static function init($args=null){
		$instance = static::__init__($args);
		if(!($instance instanceof \Core\Program)){
			throw new \Exception('Program Init Error,Program __init__ Method Error!');
		}
		return $instance;
	}
}