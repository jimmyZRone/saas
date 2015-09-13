<?php
namespace Core\Db\Sql;
/**
 * 更新
 * @author lishengyou
 *
 */
class Update extends \Zend\Db\Sql\Update
{
	protected $sql = NULL;
	public function __construct($table = null,\Zend\Db\Sql\Sql $sql = NULL){
		$this->sql = $sql;
		parent::__construct($table);
	}
	/**
	 * 获取表名
	 *  最后修改时间 2015-3-13
	 *
	 * @author dengshuang
	 * @return Ambigous <string, multitype:, \Zend\Db\Sql\TableIdentifier>
	 */
	public function getTableName()
	{
		$data = $this->table;
		if (is_array($data))
		{
			$data = each($data);
			return $data['key'];
		}
		return $data;
	}
	/**
	 * 执行
	 */
	public function execute(\Zend\Db\Sql\Sql $sql = NULL){
		try {
			if(is_null($this->sql) && !$sql)
				throw new \Exception('数据库连接不正确');
			$sql = $sql ? $sql : $this->sql;
			if(method_exists($sql, 'metadata') && $sql->hasColumn('update_time',$this->table)){
				$this->set->remove('update_time');
				$this->set(array('update_time'=>time()),self::VALUES_MERGE);
			}
			$rs = is_object($sql->prepareStatementForSqlObject($this)->execute()) ? true : false;
			if($this->getTableName() != 'log'){
				logDebug('SQL:'.@$this->getSqlString().'|RES('.strval($rs).')');
			}
			if($rs){
				//更新缓存索引
				\Core\Db\Sql\Cache::updateTableIndex($this->table);
			}
			return $rs;
		}catch (\Exception $e){
			return false;
		}
	}
	/**
	 * Get SQL string for statement
	 *
	 * @param  null|PlatformInterface $adapterPlatform If null, defaults to Sql92
	 * @return string
	 */
	public function getSqlString(\Zend\Db\Adapter\Platform\PlatformInterface $adapterPlatform = null){
		$display_errors = ini_get('display_errors');
		ini_set('display_errors', 0);
		if(!$adapterPlatform && $this->sql){
			$adapterPlatform = $this->sql->getAdapter()->getPlatform();
		}
		$sql = parent::getSqlString($adapterPlatform);
		ini_set('display_errors', $display_errors);
		return $sql;
	}
}
