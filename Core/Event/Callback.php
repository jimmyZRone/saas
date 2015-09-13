<?php
namespace Core\Event;
/**
 * 回调函数
 * @author lishengyou
 * 最后修改时间 2015年2月11日 下午4:29:35
 *
 */
class Callback{
	protected $_callback = null;
	/**
	 * 绑定回调
	 * @author lishengyou
	 * 最后修改时间 2015年2月11日 下午4:31:01
	 *
	 * @param unknown $callback
	 */
	public function bind($callback){
		if(is_string($callback)){
			if(!function_exists($callback)){
				return false;
			}
		}elseif(is_array($callback)){
			if(!isset($callback[0]) || !isset($callback[1])){
				return false;
			}
			if(!is_object($callback[0]) || !method_exists($callback[0], $callback[1])){
				return false;
			}
		}elseif(!($callback instanceof \Closure)){
			return false;
		}
		$this->_callback = $callback;
		return true;
	}
	/**
	 * 取得绑定
	 * @author lishengyou
	 * 最后修改时间 2015年2月28日 下午4:09:31
	 *
	 * @return Ambigous <unknown, \Closure>
	 */
	public function getBind(){
		return $this->_callback;
	}
	/**
	 * 触发回调
	 * @author lishengyou
	 * 最后修改时间 2015年2月11日 下午4:31:43
	 *
	 * @param string $args
	 */
	public function trigger($args=null){
		$callback = $this->_callback;
		if(!$callback) return null;
		$result = null;
		if(is_string($callback)){
			$result = $callback($args);
		}else if($callback instanceof \Closure){
			$result = $callback($args);
		}else{
			$ref = new \ReflectionClass(get_class($callback[0]));
			$method = $ref->getMethod($callback[1]);
			if($method->isPublic()){
				$result = $callback[0]->{$callback[1]}($args);
			}else{
				$closure = \Closure::bind(function($args) use($callback){
					return $this->{$callback[1]}($args);
				},$callback[0], get_class($callback[0]));
				$result = $closure($args);
			}
		}
		return $result;
	}
}