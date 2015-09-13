<?php
namespace App\Web\Mvc\Model;
use Zend\Db\Sql\Where;
class RoomFocus extends Common
{
	/**
	 * 根据公寓ID获取房间
	 * 修改时间2015年3月25日 13:43:57
	 * 
	 * @author yzx
	 * @param int $flatId
	 * @param string $floor
	 * @return unknown|boolean
	 */
	public function getDataByFlatId($flatId,$floor)
	{
		$floor_array = array();
		if (!empty($floor))
		{
			$floor_array = explode(",", $floor);
		}
		$where = new Where();
		$in_where = new Where();
		$where->equalTo("flat_id", $flatId);
		$in_where->in("floor",$floor_array);
		$where->addPredicate($in_where);
		
		$select = $this->_sql_object->select(array("rf"=>"room_focus"));
		$select->leftjoin(array("rtp"=>"room_template_relation"),"rf.room_focus_id = rtp.room_id",array("rtid"=>"template_id"));
		$select->where($where);
		$result = $select->execute();
		if (!empty($result))
		{
			return $result;
		}
		return false;
	}
	/**
	 * 批量跟新房间数据
	 * 修改时间2015年3月25日 15:50:33
	 * 
	 * @author yzx
	 * @param int $roomId
	 * @param array $data
	 * @return Ambigous <number, boolean>
	 */
	public function updateData($roomId,$data)
	{
		$room_data = array();
		$room_data['room_type'] = $data['room_type'];
		$room_data['money'] = $data['money'];
		$room_data['detain'] = $data['pledge_month'];
		$room_data['pay'] = $data['pay_month'];
		$room_data['room_config'] = $data['room_config'];
		return $this->edit(array("room_focus_id"=>$roomId), $room_data);
	}
}