<?php
namespace Core;
/**
 * 配置文件
 * @author lishengyou
 * 最后修改时间 2015年2月26日 上午10:18:37
 *
 */
class Config{
	protected static $_config = array();
	/**
	 * 取配置
	 * @author lishengyou
	 * 最后修改时间 2015年2月26日 上午10:19:27
	 *
	 * @param string $key  site:web.name
	 */
	public static function get($key){
		$key = explode(':', $key,2);
		$domain = $key[0];
		self::loadDomain($domain);
		$key[1] = isset($key[1]) ? $key[1] : null;
		return self::getDomainConfig($domain, $key[1]);
	}
	/**
	 * 加载配置域
	 * @author lishengyou
	 * 最后修改时间 2015年2月26日 上午10:21:16
	 *
	 * @param unknown $domain
	 */
	protected static function loadDomain($domain){
		if(isset(self::$_config[$domain])){
			return true;
		}
		//优先判断PHP配置文件
		$exts = array('.php'=>function($file){return include($file);},'.ini'=>function($file){return @parse_ini_file($file,true);});
		$config = null;
		if(defined('APP_IS_DEBUG') && APP_IS_DEBUG){
			foreach ($exts as $ext => $extCallback){
				$filename = CONF_DIR.'debug/'.$domain.$ext;
				if(is_file($filename)){
					$config = $extCallback($filename);
					$config = $config ? $config : array();
					break;
				}
			}
		}
		if($config === null){
			foreach ($exts as $ext => $extCallback){
				$filename = CONF_DIR.$domain.$ext;
				if(is_file($filename)){
					$config = $extCallback($filename);
					$config = $config ? $config : array();
					break;
				}
			}
		}
		if($config){
			self::$_config[$domain] = $config;
			return true;
		}else{
			return false;
		}
	}
	/**
	 * 取得域配置
	 * @author lishengyou
	 * 最后修改时间 2015年2月26日 上午10:24:23
	 *
	 * @param unknown $domain
	 * @param unknown $key
	 */
	protected static function getDomainConfig($domain,$key=null){
		if(!isset(self::$_config[$domain])){
			return null;
		}
		if(!$key){
			return self::$_config[$domain];
		}else{
			$keys = explode('.', $key);
			return self::getConfig(self::$_config[$domain], $keys);
		}
	}
	/**
	 * 取配置
	 * @author lishengyou
	 * 最后修改时间 2015年2月26日 上午10:25:58
	 *
	 * @param unknown $config
	 * @param unknown $keys
	 */
	protected static function getConfig(&$config,$keys){
		$key = array_shift($keys);
		if(!isset($config[$key])){
			return null;
		}
		$_config = $config[$key];
		if(!is_array($_config) && !empty($keys)){
			return null;
		}
		if(empty($keys)){
			return $_config;
		}
		return self::getConfig($_config, $keys);
	}
}