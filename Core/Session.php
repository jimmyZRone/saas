<?php
namespace Core;
/**
 * Session
 * @author lishengyou
 * 最后修改时间 2015年4月9日 上午10:15:34
 *
 */
class Session{
	/**
	 * 保存
	 * @author lishengyou
	 * 最后修改时间 2015年4月9日 上午10:16:14
	 *
	 * @param string $key captcha.image.register
	 * @param mixed $value
	 */
	public static function save($key,$value){
		if(!session_id()){
			session_start();
		}
		$key = explode('.', $key);
		return self::_save($key, $value, $_SESSION);
	}
	/**
	 * 保存到数组
	 * @author lishengyou
	 * 最后修改时间 2015年4月9日 上午10:17:25
	 *
	 * @param array $key
	 * @param string $value
	 * @param array $array
	 */
	protected static function _save(array $key,$value,&$array){
		$ck = array_shift($key);
		if(empty($key)){
			return $array[$ck] = $value;
		}
		if(!isset($array[$ck]) || !is_array($array[$ck])){
			$array[$ck] = array();
		}
		return self::_save($key, $value, $array[$ck]);
	}
	/**
	 * 读取
	 * @author lishengyou
	 * 最后修改时间 2015年4月9日 上午10:16:36
	 *
	 * @param string $key captcha.image.register
	 */
	public static function read($key){
		if(!session_id()){
			session_start();
		}
		$key = explode('.', $key);
		return self::_read($key,$_SESSION);
	}
	/**
	 * 读取数组
	 * @author lishengyou
	 * 最后修改时间 2015年4月9日 上午10:17:25
	 *
	 * @param array $key
	 * @param mixed $array
	 */
	protected static function _read($key,&$array){
		$ck = array_shift($key);
		if($ck === false) return $array;
		if(!isset($array[$ck])){
			return null;
		}
		if(empty($key)){
			return $array[$ck];
		}
		if(!is_array($array[$ck])){
			return null;
		}
		return self::_read($key,$array[$ck]);
	}
	/**
	 * 删除
	 * @author lishengyou
	 * 最后修改时间 2015年4月9日 下午12:43:58
	 *
	 * @param string $key
	 */
	public static function delete($key){
		if(!session_id()){
			session_start();
		}
		$key = explode('.', $key);
		return self::_delete($key,$_SESSION);
	}
	/**
	 * 删除
	 * @author lishengyou
	 * 最后修改时间 2015年4月9日 下午12:44:06
	 *
	 * @param array $key
	 * @param unknown $value
	 */
	protected static function _delete(array $key,&$value){
		$ck = array_shift($key);
		if($ck === false){
			return false;
		}
		if(!is_array($value) || !isset($value[$ck])){
			return false;
		}
		if(empty($key)){
			unset($value[$ck]);
			return true;
		}
		return self::_delete($key, $value[$ck]);
	}
}