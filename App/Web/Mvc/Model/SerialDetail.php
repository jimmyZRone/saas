<?php
namespace App\Web\Mvc\Model;
class SerialDetail extends Common
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
	 * 修改时间2015年3月19日 15:00:53
	 * 
	 * @author yzx
	 * @param array $data
	 * @param int $id
	 * @return Ambigous <number, boolean>
	 */
	public function editSerialDetail($data,$id)
	{
		$result = $this->edit(array("serial_detail_id"=>$id), $data);
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
}