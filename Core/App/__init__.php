<?php
namespace Core;
define('APP_IS_DEBUG',!!\Core\Config::get('debug:debug'));//是否是调试
define('APP_URL', 'http://'.$_SERVER["HTTP_HOST"].'/'.str_replace('\\','/',trim(dirname($_SERVER["SCRIPT_NAME"]),'/').'/'));
class App{
	private static $_app = null;
	protected $_container = null;
	public function __construct(){
		$this->_container = new \Core\Container();
	}
	/**
	 * 初始化
	 * @author lishengyou
	 * 最后修改时间 2015年2月28日 下午2:07:21
	 *
	 */
	protected function __init__(){}
	/**
	 * 初始化APP
	 * @author lishengyou
	 * 最后修改时间 2015年2月28日 下午2:06:17
	 *
	 * @param App $app
	 */
	final public static function init(App $app){
		self::$_app = $app;
		self::$_app->__init__();
	}
	/**
	 * 取得当前APP
	 * @author lishengyou
	 * 最后修改时间 2015年2月28日 下午2:06:41
	 *
	 * @return App
	 */
	final public static function getNowApp(){
		return self::$_app;
	}
	
	/**
	 * 取得容器
	 * @author lishengyou
	 * 最后修改时间 2015年2月28日 下午2:15:14
	 *
	 * @return \Core\Container
	 */
	public function getContainer(){
		return $this->_container;
	}
}