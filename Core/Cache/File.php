<?php
namespace Core\Cache;
/**
 * 文件缓存
 * @author lishengyou
 * 最后修改时间 2015年3月25日 上午10:43:08
 *
 */
class File implements Adapter{
	protected $dir = null;
	public function __construct(){
		$this->setDir(CACHE_DIR.'filecache');
	}
	/**
	 * 设置目录
	 * @author lishengyou
	 * 最后修改时间 2015年3月25日 上午10:44:40
	 *
	 * @param unknown $dir
	 */
	public function setDir($dir){
		if(!is_dir($dir) && !mkdir($dir,0777,true)){
			return false;
		}
		$this->dir = rtrim($dir,'/').'/';
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
		if(!$this->dir) return false;
		$filename = $this->dir.$key.'.cache';
		$dirname = dirname($filename);
		if(!is_dir($dirname) && !mkdir($dirname,0777,true)){
			return false;
		}
		$data = array('valid'=>$valid,'data'=>$value);
		$data = serialize($data);
		return file_put_contents($filename, $data);
	}
	/**
	 * 获取
	 * @author lishengyou
	 * 最后修改时间 2015年3月25日 上午10:42:08
	 *
	 * @param unknown $key
	 */
	public function get($key){
		if(!$this->dir) return null;
		$filename = $this->dir.$key.'.cache';
		if(!is_file($filename)) return null;
		$data = file_get_contents($filename);
		if(!$data) return null;
		$data = unserialize($data);
		if(!$data || ($data['valid'] && $data['valid'] < time())) return null;
		return $data['data'];
	}
}