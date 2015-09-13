<?php
namespace Core\Db\Sql;

use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Metadata\Metadata;


/**
 * 数据库链接
 * @author lishengyou
 *
 */
class Sql extends \Zend\Db\Sql\Sql{
	protected $metadata = NULL;
	protected $_guid = null;
	protected $_aserver = null;//从库
	protected $_pserver = null;//父库
	public function __construct(AdapterInterface $adapter, $table = null, \Zend\Db\Sql\Platform\AbstractPlatform $sqlPlatform = null){
		parent::__construct($adapter,null,null);
		$this->metadata = new Metadata($adapter);
	}
	/**
	 * 取得主库
	 * @author lishengyou
	 * 最后修改时间 2015年8月19日 下午2:11:15
	 * @return \Core\Db\Sql\Sql
	 */
	public function getPServer(){
		return $this->_pserver;
	}
	protected static $staticInstance = array();
	/**
	 * 取得实例
	 * @param string $guid 实例名称
	 * @param array $config 配置
	 * @return Sql
	 */
	public static function getInstance($guid=NULL,array $config=NULL){
		$guid = $guid ? $guid : 'default';
		if(!self::hasInstance($guid)){
			try{
				$aserver = isset($config['aserver']) ? $config['aserver'] : null;
				if($aserver){
					unset($config['aserver']);
				}
				$adapter = new Adapter($config);
				$obj = new self($adapter);
				//从库
				if($aserver && is_array($aserver)){
					$aserver = $aserver[array_rand($aserver)];
					$adapter = new Adapter($aserver);
					$obj->_aserver = new self($adapter);
					$obj->_aserver->_pserver = $obj;
				}else{
					$obj->_aserver = $obj;
				}
				self::$staticInstance[$guid] = $obj;
				$obj->_guid = $guid;
			}catch (\Exception $e){
				return NULL;
			}
		}
		return self::$staticInstance[$guid];
	}
	/**
	 * 判断实例是否存在
	 * @param string $guid
	 */
	public static function hasInstance($guid = NULL){
		$guid = $guid ? $guid : 'default';
		return isset(self::$staticInstance[$guid]);
	}
	protected $_isTransaction = false;
	/**
	 * 开启事务
	 */
	public function Transaction(){
		logDebug('SQL:开启事务');
		$this->_isTransaction = true;
		$this->getAdapter()->getDriver()->getConnection()->beginTransaction();
	}
	/**
	 * 会滚事务
	 */
	public function rollback(){
		logDebug('SQL:回滚事务');
		$this->_isTransaction = false;
		$this->getAdapter()->getDriver()->getConnection()->rollback();
	}
	/**
	 * 提交事务
	 */
	public function commit(){
		$this->_isTransaction = false;
		logDebug('SQL:提交事务');
		$this->getAdapter()->getDriver()->getConnection()->commit();
	}
	/**
	 * 查询(non-PHPdoc)
	 * @see \Zend\Db\Sql\Sql::select()
	 */
    public function select($table = null)
    {
    	if($table){
    		$this->setTable($table);
    		$table = null;
    	}
        if ($this->table !== null && $table !== null) {
            throw new \Zend\Db\Sql\Exception\InvalidArgumentException(sprintf(
                'This Sql object is intended to work with only the table "%s" provided at construction time.',
                $this->table
            ));
        }
        return new Select(($table) ?: $this->table,$this->_aserver && !$this->_isTransaction ? $this->_aserver : $this);
    }
	/**
	 * 添加(non-PHPdoc)
	 * @see \Zend\Db\Sql\Sql::insert()
	 */
    public function insert($table = null)
    {
    	if($table){
    		$this->setTable($table);
    		$table = null;
    	}
        if ($this->table !== null && $table !== null) {
            throw new \Zend\Db\Sql\Exception\InvalidArgumentException(sprintf(
                'This Sql object is intended to work with only the table "%s" provided at construction time.',
                $this->table
            ));
        }
        return new Insert(($table) ?: $this->table,$this);
    }
	/**
	 * 更新(non-PHPdoc)
	 * @see \Zend\Db\Sql\Sql::update()
	 */
    public function update($table = null)
    {
    	if($table){
    		$this->setTable($table);
    		$table = null;
    	}
        if ($this->table !== null && $table !== null) {
            throw new \Zend\Db\Sql\Exception\InvalidArgumentException(sprintf(
                'This Sql object is intended to work with only the table "%s" provided at construction time.',
                $this->table
            ));
        }
        return new Update(($table) ?: $this->table,$this);
    }
    /**
     * 删除(non-PHPdoc)
     * @see \Zend\Db\Sql\Sql::delete()
     */
    public function delete($table = null)
    {
    	if($table){
    		$this->setTable($table);
    		$table = null;
    	}
        if ($this->table !== null && $table !== null) {
            throw new \Zend\Db\Sql\Exception\InvalidArgumentException(sprintf(
                'This Sql object is intended to work with only the table "%s" provided at construction time.',
                $this->table
            ));
        }
        return new Delete(($table) ?: $this->table,$this);
    }
    /**
     * 取得元信息操作类
     * @author lishengyou
     * 最后修改时间 2014年11月5日 下午3:12:03
     *
     * @return \Zend\Db\Metadata\Metadata
     */
    public function metadata(){
    	return $this->metadata;
    }
    /**
     * 判断字段是否存在
     * @author lishengyou
     * 最后修改时间 2015年6月23日 下午5:44:15
     *
     * @param unknown $cloumn
     * @param unknown $table
     * @return boolean
     */
    public function hasColumn($cloumn,$table){
    	if(is_array($table)){
    		$table = each($table);
    		$table = $table['value'];
    	}
    	if(is_object($table)){
    		return false;
    	}
    	$fields = $this->getFieldByName($table);
    	if(!$fields){
    		return false;
    	}
    	return in_array($cloumn, $fields);
    }
    /**
     * 根据表名获取字段数组
     * 	最后修改时间 2014-11-04
     * 	根据表名获取字段数组
     *
     * @param array $data
     * @author dengshuang
     * @return int
     */
    private function getFieldByName($tableName)
    {
    	$cache_key = "web/table_field/".$this->_guid."_{$tableName}";
    	$cache = new \Core\Cache(new \Core\Cache\File());
    	$cacheData = $cache->get($cache_key);
    	if (!$cacheData)
    	{
    		$columns = $this->metadata()->getColumns($tableName);
    		$_columns = array();
    		foreach ($columns as $value)
    		{
    			$_columns[] = $value->getName();
    		}
    		$cacheData = $_columns;
    		$cache->save($cache_key , $cacheData);
    	}
    	return $cacheData;
    }
}