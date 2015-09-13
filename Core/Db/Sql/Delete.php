<?php
namespace Core\Db\Sql;
/**
 * 删除
 * @author lishengyou
 *
 */
class Delete extends \Zend\Db\Sql\Delete
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
	public function execute(\Zend\Db\Sql\Sql $sql = NULL,$is_delete=false){
		try {
			if(is_null($this->sql) && !$sql)
				throw new \Exception('数据库连接不正确');
			$sql = $sql ? $sql : $this->sql;
			if(!$is_delete && method_exists($sql, 'metadata') && $sql->hasColumn('is_delete',$this->table)){
				$where = $this->where;
				$update = $sql->update($this->table);
				$update->where($where);
				$update->set(array('is_delete'=>1));
				return $update->execute();
			}
			$result = $sql->prepareStatementForSqlObject($this)->execute();
			if($this->getTableName() != 'log'){
				logDebug('SQL:'.@$this->getSqlString().'|RES('.strval(!!$result).')');
			}
			if($result)
			{
				//更新缓存索引
				\Core\Db\Sql\Cache::updateTableIndex($this->table);
				
				return true;
			}else 
			{
				return false;
			}
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
