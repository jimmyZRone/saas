<?php
namespace Common\Model\Erp;
class SerialDetail extends \Common\Model\Erp
{
	/**
	 * 添加流水详情
	 * 修改时间2015年3月19日 14:57:51
	 * 
	 * @author yzx
	 * @param array $data
	 * @return number
	 */
	public function addSerialDetail($data)
	{
		$result = $this->insert($data);
		return $result;
	}
	/**
	 * 编辑流水详情
	 * 修改时间 2015年5月9日14:36:02
	 *
	 * @author ft
	 * @param array $data
	 * @return boolean
	 */
	public function editSerialDetailById($where, $data)
	{
	    $result = $this->edit($where, $data);
	    return $result;
	}
	/**
	 * 获取流水下全部详情
	 * 修改时间2015年3月19日 15:07:06
	 * 
	 * @author yzx
	 * @param int $serial_id
	 * @return unknown
	 */
	public function getSerialDetail($serial_id)
	{
		$select = $this->_sql_object->select("serial_detail")
				  ->where(array("serial_id"=>$serial_id));
		$result = $select->execute();
		return $result;
	}
	
	/**
	 * 获取当前流水的所有详细id
	 * 修改时间 2015年5月13日20:17:50
	 * 
	 * @author ft
	 */
	public function getAllDetailIdBySerialId($serial_id) {
	    $sql = $this->getSqlObject();
	    $select = $sql->select(array('sd' => 'serial_detail'));
	    $select->columns(array('serial_detail_id' => 'serial_detail_id'));
	    $where = new \Zend\Db\Sql\Where();
	    $where->equalTo('sd.serial_id', $serial_id);
	    //$where->equalTo('sd.is_delete', 0);
	    $select->where($where);
	    return $select->execute();
	}
}