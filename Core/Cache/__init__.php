<?php
namespace Core;
/**
 * 缓存
 * @author lishengyou
 * 最后修改时间 2015年3月25日 上午10:36:15
 *
 */
class Cache implements Cache\Adapter{
	protected $adapter = null;
	public function __construct(Cache\Adapter $adapter){
		$this->adapter = $adapter;
	}
	/**
	 * 保存
	 * @author lishengyou
	 * 最后修改时间 2015年3月25日 上午10:41:30
	 *
	 * @param unknown $key
	 * @param unknown $value
	 * @param string $valid
	 */
	public function save($key,$value,$valid=false){
		return $this->adapter->save($key,$value,$valid);
	}
	/**
	 * 获取
	 * @author lishengyou
	 * 最后修改时间 2015年3月25日 上午10:42:08
	 *
	 * @param unknown $key
	 */
	public function get($key){
		return $this->adapter->get($key);
	}
}