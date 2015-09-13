<?php
namespace Core;
/**
 * 基本类
 * @author lishengyou
 * 最后修改时间 2015年3月24日 上午9:54:28
 *
 */
class Object{
	protected static $_last_error = null;
	/**
	 * 设置最后错误
	 * @author lishengyou
	 * 最后修改时间 2015年3月24日 上午9:54:12
	 *
	 * @param unknown $error
	 */
	public static function setLastError($error){
		static::$_last_error = $error;
	}
	/**
	 * 取得最后的错误
	 * @author lishengyou
	 * 最后修改时间 2015年3月24日 上午9:54:19
	 *
	 * @return unknown
	 */
	public static function getLastError(){
		return static::$_last_error;
	}
}