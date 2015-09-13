<?php
namespace App\Web\Mvc\Model;

class Common extends Model
{
	protected $_link_guid = null;
	/**
	 * sql对象
	 * @var object
	 */
	protected $_sql_object = null;
	/**
	 * 表字段
	 * @var array
	 */
	protected $_table_field = array();
	
	/**
	 * 表名
	 * @var string
	 */
	protected $_table_name = '';
	
	/**
	 * 初始化方法
	 *	最后修改时间 2014-10-31
	 * 	成员函数是用来初始化对象获取sql对象
	 *
	 * @author dengshuang
	 * @return void
	 */
	public function __construct($tableName = '',$guid=NULL,array $config=NULL)
	{
		$this->_sql_object = $this->getLink('default');
// 		print_r($this->_sql_object);die;
		$this->_link_guid = $guid;
		if(!$tableName && !$this->_table_name){
			//根据当前类名称取信息
			$classname = get_class($this);
			$classname = explode('\\', $classname);
			$classname = str_split(end($classname));
			$tableName = '';
			foreach ($classname as $key => $value){
				if(is_numeric($value)){
					$tableName .= $value;
				}else{
					$ascii = ord($value);
					if($key > 0 && $ascii < 91){//大写
						$tableName .= '_';
					}
					$tableName .= strtolower($value);
				}
			}
		}
		$this->_table_name = $tableName ? $tableName : $this->_table_name;
		if(empty($this->_table_field)){
			$this->_table_field = $this->getFieldByName($tableName);
		}
	}
	/**
	 * 取得表名
	 * @author lishengyou
	 * 最后修改时间 2014年12月29日 下午4:29:12
	 *
	 * @return string
	 */
	public function getTableName(){
		return $this->_table_name;
	}
	/**
	 * 开启事务
	 * @author lishengyou
	 * 最后修改时间 2014年12月29日 下午2:33:06
	 *
	 */
	public function Transaction(){
		$this->_sql_object->Transaction();
	}
	/**
	 * 会滚事务
	 */
	public function rollback(){
		$this->_sql_object->rollback();
	}
	/**
	 * 提交事务
	 */
	public function commit(){
		$this->_sql_object->commit();
	}
	/**
	 * 取得Select对象
	 * @author lishengyou
	 * 最后修改时间 2015年3月19日 下午3:28:39
	 *
	 * @return \Core\Db\Sql\Sql
	 */
	public function getSqlObject(){
		return $this->_sql_object;
	}
	/**
	 * 设置字段
	 *	最后修改时间 2014-11-04
	 * 	设置字段
	 *
	 * @param array $data
	 * @author dengshuang
	 * @return int
	 */
	protected function setTableFiled($tableField = array()){
		if($tableField){
			$this->_table_field = $tableField;
		}
	}
	
	/**
	 * 根据表名获取字段数组
	 *	最后修改时间 2014-11-04
	 * 	根据表名获取字段数组
	 *
	 * @param array $data
	 * @author dengshuang
	 * @return int
	 */
	private function getFieldByName($tableName){
		$cache_key = "web/table_field/erp_{$tableName}";
		$cache = new \Core\Cache(new \Core\Cache\File());
		$cacheData = $cache->get($cache_key);
		if(!$cacheData){
			if(!method_exists($this->_sql_object, 'metadata')) return false;
			$columns = $this->_sql_object->metadata()->getColumns($this->_table_name);
			$_columns = array();
			foreach ($columns as $value){
				$_columns[] = $value->getName();
			}
			$cacheData = $_columns;
			$cache->save($cache_key, $cacheData);
		}
		return $cacheData;
	}
	/**
	 * 预处理数据
	 *	最后修改时间 2014-11-04
	 * 	预处理数据,筛选出存在的字段去除无用数据
	 *
	 * @param array $data
	 * @author dengshuang
	 * @return int
	 */
	private function _prepare($data = array()){
		if(empty($data)||empty($this->_table_field)){
			return $data;
		}else{
			$return_array = array();
			foreach ($data as $key => $value){
				if(in_array($key, $this->_table_field)){
					$return_array[$key] = $value;
				}
			}
			return $return_array;
		}
	}
	
	/**
	 * 获取数量
	 *	最后修改时间 2014-10-31
	 * 	获取数量
	 *
	 * @param array $data
	 * @author dengshuang
	 * @return int
	 */
	public function getCount($condition){
		$select = $this->_sql_object->select($this->_table_name);
		$select->columns(array('count'=>new \Zend\Db\Sql\Expression('count(*)')));
		$select->where($condition);
		$count = $select->execute();
		if($count){
			$count = isset($count[0]['count'])?$count[0]['count']:0;
			return $count;
		}else{
			return false;
		}
	}
	
	/**
	 * 插入数据
	 *	最后修改时间 2014-10-31
	 * 	插入数据
	 *
	 * @param array $data
	 * @author dengshuang
	 * @return int
	 */
	public function insert($data){
		// 		print_r($data);
		$insert = $this->_sql_object->insert($this->_table_name);
				//print_r($this->_prepare($data));//die;
		$insert->values($this->_prepare($data));
// 				echo str_replace('"', '', $insert->getSqlString());
// 				echo "<br/>";
				//die;
		$results = $insert->execute();
		// 		var_dump($results);die;
		return $results;
	}
	
	/**
	 * 修改数据
	 *	最后修改时间 2014-10-31
	 * 	修改数据
	 *
	 * @param array $data
	 * @author dengshuang
	 * @return int
	 */
	public function edit($where,$data){
		$update = $this->_sql_object->update($this->_table_name);
		$update->set($this->_prepare($data));
		$update->where($where);
		
		$results = $update->execute();
		//echo str_replace('"', '', $update->getSqlString());die;
		//         var_dump($results);die;
		if($results !== false){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 删除
	 *	最后修改时间 2014-11-05
	 * 	删除
	 *
	 * @param array $house_id
	 * @author dengshuang
	 * @return bool
	 */
	public function delete($condition){
		$delete = $this->_sql_object->delete($this->_table_name);
		$delete->where($condition);
		return $delete->execute();
	}
	
	/**
	 * 获取单条数据
	 *	最后修改时间 2014-11-05
	 * 	获取单条数据
	 *
	 * @param array $house_id
	 * @author dengshuang
	 * @return bool
	 */
	public function getOne($where,$columns = array()){
		$result = $this->getData($where,$columns);
		if(is_array($result)){
			if(!empty($result['0'])){
				return $result['0'];
			}else{
				return array();
			}
		}else{
			return false;
		}
	}
	
	/**
	 * 获取多条数据
	 *	最后修改时间 2014-11-05
	 * 	获取多条数据
	 *
	 * @param array $house_id
	 * @author dengshuang
	 * @return bool
	 */
	public function getData($where,$columns = array(),$limit = 0,$order = '',$is_auth = false){
		$select = $this->_sql_object->select($this->_table_name);
		if(!empty($columns))$select->columns($columns);
		if(!empty($where))$select->where($where);
		if($limit>=1 && is_numeric($limit))$select->limit(intval($limit));
		if(!empty($order))$select->order($order);
// 		echo(str_replace('"', '', $select->getSqlString()));//die;
		if($is_auth){
			\Core\App\Event::trigger(\App\Web\Lib\Listing::DB_SELECT_CREATED,$select,\Core\Event::EVENT_TRANSFER);
		}
// 		echo($select->getSqlString());
		$result = $select->execute();
		//echo str_replace('"', '', $select->getSqlString());//die;
// 		echo "<br/>";
// 		print_r($result);
		if($result){
			return $result;
		}else{
			return array();
		}
	}
	
	/**
	 * 获取链接
	 *  最后修改时间 2015-3-13
	 *  获取数据库链接,其实是获取的是sql对象
	 * 
	 * @param string $uid
	 * @return Ambigous <>|Ambigous <NULL, Ambigous <\Core\Db\Sql\Sql, NULL, multitype:>>
	 */
	public function getLink($uid = 'default'){
		static $links = array();
		if(isset($links[$uid])){
			return $links[$uid];
		}
		$config = \Core\Config::get('web/db:'.$uid);
// 		print_r($config);die;
		if(!is_array($config)){
			$config = \Core\Config::get('db:'.$uid);
			
		}
		if(!$config){
			$links[$uid] = null;
		}else{
			$links[$uid] = \Core\Db\Sql\Sql::getInstance($uid,$config);
		}
		return $links[$uid];
		
	}
}