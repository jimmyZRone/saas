<?php
namespace Core;
define('M_DOMAIN_SUFFIX',preg_replace('#^.*?([^\.]+\.[^\.]+)$#', '$1', $_SERVER['HTTP_HOST']));
define('ROOT_DIR', realpath(__DIR__.'/../').'/');//根目录
define('RES_DIR', ROOT_DIR.'Resources/');//资源目录
define('CONF_DIR', RES_DIR.'config/');//配置目录
define('CACHE_DIR', RES_DIR.'cache/');//缓存目录
/**
 * 自动加载
 * @author lishengyou
 * 最后修改时间 2015年2月9日 上午9:43:59
 *
 */
final class Autoload{
	protected static $_paths = array();
	/**
	 * 初始化
	 * @author lishengyou
	 * 最后修改时间 2015年2月9日 上午9:44:23
	 *
	 */
	public static function init(){
		$autoloads = spl_autoload_functions();
		$autoloads = $autoloads ? $autoloads : array();
		foreach ($autoloads as $autoload){
			spl_autoload_unregister($autoload);
		}
		self::addPath(ROOT_DIR);
		spl_autoload_register(function($class_name){
			self::load($class_name);
		});
		//加载配置第三方库
		self::loadVendor(\Core\Config::get('site:vendor'));
		//加载需要自动加载的文件
		$autoloadFiles = \Core\Config::get('site:autoload');
		if(is_array($autoloadFiles)){
			foreach ($autoloadFiles as $_file){
				$_file = ROOT_DIR.$_file;
				if(is_file($_file)){
					include_once $_file;
				}
			}
		}
	}
	/**
	 * 加载第三方库目录
	 * @author lishengyou
	 * 最后修改时间 2015年2月26日 上午10:42:24
	 *
	 */
	protected static function loadVendor($vendor){
		switch (gettype($vendor)){
			case 'string':
				$vendor = str_replace('\\', '/', $vendor);
				$vendor = trim($vendor,'/');
				$vendor = str_replace('.', '', $vendor);
				self::addPath(ROOT_DIR.$vendor);
				break;
			case 'array':
				foreach ($vendor as $_vendor){
					self::loadVendor($_vendor);
				}
				break;
		}
	}
	/**
	 * 添加目录
	 * @author lishengyou
	 * 最后修改时间 2015年2月9日 上午11:00:56
	 *
	 * @param unknown $path
	 */
	public static function addPath($path){
		$path = realpath($path);
		if($path){
			self::$_paths[] = $path;
		}
	}
	/**
	 * 自动加载类
	 * @author lishengyou
	 * 最后修改时间 2015年2月9日 上午10:37:08
	 *
	 * @param unknown $class_name
	 * @return boolean
	 */
	protected static function load($class_name){
		if(!class_exists($class_name)){
			$_class_name = trim($class_name,'\\');
			foreach (self::$_paths as $path){
				$class_dir = $path.'/'.str_replace('\\', '/', $_class_name);
				if(is_file($class_dir.'.php')){
					include $class_dir.'.php';
					break;
				}elseif (is_dir($class_dir) && is_file($class_dir.'/__init__.php')){
					include $class_dir.'/__init__.php';
					break;
				}
			}
		}
		return class_exists($class_name);
	}
	/**
	 * 检测类是否存在
	 * @author lishengyou
	 * 最后修改时间 2015年2月10日 上午10:59:27
	 *
	 * @param unknown $class_name
	 * @return boolean
	 */
	public static function isExists($class_name){
		return self::load($class_name);
	}
}