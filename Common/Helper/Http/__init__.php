<?php
namespace Common\Helper;
/**
 * HTTP操作
 * @author lishengyou
 * 最后修改时间 2015年8月26日 下午3:21:36
 *
 */
class Http{
	protected $_http;
	/**
	 * 打开
	 * @author lishengyou
	 * 最后修改时间 2015年8月26日 下午3:21:42
	 *
	 */
	public function open(){
		$this->_http = curl_init($uri);
	}
	/**
	 * GET请求
	 * @author lishengyou
	 * 最后修改时间 2015年8月26日 下午3:21:47
	 *
	 * @param unknown $uri
	 * @return mixed
	 */
	public function get($uri){
		if(!is_resource($this->_http)){
			return false;
		}
	    curl_setopt($this->_http, CURLOPT_URL, $uri);
	    curl_setopt($this->_http, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($this->_http, CURLOPT_HEADER, 0);
	    $output = curl_exec($this->_http);
	    return $output;
	}
	/**
	 * 关闭
	 * @author lishengyou
	 * 最后修改时间 2015年8月26日 下午3:21:55
	 *
	 */
	public function close(){
		if(is_resource($this->_http)){
			curl_close($this->_http);
			$this->_http = null;
		}
	}
}