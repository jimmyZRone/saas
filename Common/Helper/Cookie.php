<?php
namespace Common\Helper;
class Cookie{
	/**
	 * 取COOKIE
	 * @author lishengyou
	 * 最后修改时间 2014年11月7日 上午11:44:02
	 *
	 * @param unknown $name
	 * @return Ambigous <NULL, unknown>
	 */
	public static function getCookie($name){
		return isset($_COOKIE[$name]) ? $_COOKIE[$name] : NULL;
	}
	/**
	 * 设置Cookie
	 * @author lishengyou
	 * 最后修改时间 2014年11月7日 上午11:40:51
	 *
	 * @param unknown $name
	 * @param string $value
	 * @param string $expire
	 * @param string $path
	 * @param string $domain
	 * @param string $secure
	 * @param string $httponly
	 */
	public static function setCookie($name, $value = null, $expire = null, $path = null, $domain = null, $secure = null, $httponly = null){
		setcookie($name, $value, $expire, $path, $domain , $secure, $httponly);
		$_COOKIE[$name] = $value;
	}
}