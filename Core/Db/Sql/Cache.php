<?php
namespace Core\Db\Sql;
/**
 * SQL数据缓存系统
 * @author lishengyou
 * 最后修改时间 2015年6月9日 下午3:14:50
 *
 */
class Cache{
	protected static $prev_table = null;
	/**
	 * 取缓存
	 * @author lishengyou
	 * 最后修改时间 2015年6月9日 下午3:15:47
	 *
	 * @param string $table
	 * @param mixed $join
	 * @param string $sql
	 */
	public static function getCacheData($table,$join,$sql){
		if(stripos($sql, " like '")){//带有LINK不处理
			return false;
		}
		$sqlmd5 = substr(md5($sql),8,16);
		$tables = array();
		$join[] = array('name'=>$table);
		if(!empty($join)){//统计所有连接的表
			foreach ($join as $table){
				$name = $table['name'];
				if(is_array($name)){
					$name = each($name);
					$name = $name['value'];
				}
				$tables[] = $name;
			}
		}
		$cachetime = self::getCacheTime($sqlmd5);
		if(!$cachetime){//没有缓存
			return false;
		}
		//查看每个表有没有更新数据
		$tableindex = self::getTableIndex();
		$tableindex = $tableindex ? $tableindex : array();
		$update_index = array();
		$iscache = true;
		foreach ($tables as $table){
			if(!isset($tableindex[$table])){//需要建立索引的
				$update_index[] = $table;
				$iscache = false;
			}else if($tableindex[$table] >= $cachetime){//缓存已经过期
				$iscache = false;
			}
		}
		if(!empty($update_index)){
			self::updateTableIndexs($update_index);
		}
		//缓存存在也没有过期
		return $iscache ? self::getCache($sqlmd5) : false;
	}
	/**
	 * 取得缓存时间
	 * @author lishengyou
	 * 最后修改时间 2015年6月9日 下午3:32:36
	 *
	 * @param unknown $sqlmd5
	 */
	protected static function getCacheTime($sqlmd5){
		$filename = CACHE_DIR.'sql_data_cache/sql_'.$sqlmd5.'.cache';
		if(!is_file($filename)){
			return false;
		}
		return filemtime($filename);
	}
	/**
	 * 取缓存
	 * @author lishengyou
	 * 最后修改时间 2015年6月9日 下午3:39:23
	 *
	 * @param unknown $sqlmd5
	 * @return multitype:|boolean|Ambigous <multitype:, mixed>
	 */
	protected static function getCache($sqlmd5){
		$filename = CACHE_DIR.'sql_data_cache/sql_'.$sqlmd5.'.cache';
		if(!is_file($filename)){
			return array();
		}
		$data = file_get_contents($filename);
		if(!$data){
			return false;
		}
		$data = unserialize($data);
		return $data ? $data : array();
	}
	/**
	 * 更新缓存数据
	 * @author lishengyou
	 * 最后修改时间 2015年6月9日 下午3:33:37
	 *
	 * @param unknown $sql
	 * @param unknown $data
	 */
	public static function updateCacheData($sql,$data){
		$sqlmd5 = substr(md5($sql),8,16);
		$filename = CACHE_DIR.'sql_data_cache/sql_'.$sqlmd5.'.cache';
		if(!is_dir(dirname($filename)) && !mkdir(dirname($filename),0777,true)){//失败
			return false;
		}
		$data = serialize($data);
		return file_put_contents($filename, $data);
	}
	/**
	 * 保存缓存更新
	 * @author lishengyou
	 * 最后修改时间 2015年6月9日 下午3:16:30
	 *
	 * @param unknown $table
	 */
	public static function updateTableIndex($table){
		if(is_array($table)){
			$table = each($table);
			$table = $table['value'];
		}
		if($table == self::$prev_table){//在上一次才更新了这个表，不进行操作了
			return true;
		}
		self::$prev_table = $table;
		$tableindex = self::getTableIndex();
		if(!$tableindex){
			$tableindex = array();
		}
		$tableindex[$table] = time();
		$filename = CACHE_DIR.'sql_data_cache/tableindex.cache';
		if(!is_dir(dirname($filename)) && !mkdir(dirname($filename),0777,true)){//失败
			return false;
		}
		$data = serialize($tableindex);
		return file_put_contents($filename, $data);
	}
	/**
	 * 保存多个缓存更新
	 * @author lishengyou
	 * 最后修改时间 2015年6月9日 下午3:16:30
	 *
	 * @param unknown $table
	 */
	public static function updateTableIndexs($tables){
		$tableindex = self::getTableIndex();
		if(!$tableindex){
			$tableindex = array();
		}
		$time = time();
		foreach ($tables as $table){
			if(is_array($table)){
				$table = each($table);
				$table = $table['value'];
			}
			$tableindex[$table] = $time;
		}
		$filename = CACHE_DIR.'sql_data_cache/tableindex.cache';
		if(!is_dir(dirname($filename)) && !mkdir(dirname($filename),0777,true)){//失败
			return false;
		}
		$data = serialize($tableindex);
		return file_put_contents($filename, $data);
	}
	/**
	 * 取得表的更新
	 * @author lishengyou
	 * 最后修改时间 2015年6月9日 下午3:26:22
	 *
	 */
	protected static function getTableIndex(){
		$filename = CACHE_DIR.'sql_data_cache/tableindex.cache';
		if(!is_file($filename)){
			return false;
		}
		$data = file_get_contents($filename);
		if(!$data){
			return false;
		}
		$data = unserialize($data);
		return $data ? $data : false;
	}
}