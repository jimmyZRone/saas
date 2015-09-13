<?php
namespace Core\Cache;
/**
 * 缓存适配器
 * @author lishengyou
 * 最后修改时间 2015年3月25日 上午10:38:31
 *
 */
interface Adapter{
	/**
	 * 保存
	 * @author lishengyou
	 * 最后修改时间 2015年3月25日 上午10:38:40
	 *
	 * @param string $key
	 * @param string $value
	 * @param int $valid
	 */
	public function save($key,$value,$valid=false);
	/**
	 * 获取
	 * @author lishengyou
	 * 最后修改时间 2015年3月25日 上午10:38:44
	 *
	 * @param string $key
	 */
	public function get($key);
}