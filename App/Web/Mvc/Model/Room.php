<?php
namespace App\Web\Mvc\Model;
class Room extends Common
{
	/**
	 * 添加房间
	 * 修改时间2015年3月17日 11:25:48
	 * 
	 * @author yzx
	 * @param array $data
	 * @return number
	 */
	public function addRoom($data)
	{
		//TODO 添加图片
		if (is_array($data['room_pic']) && !empty($data['room_pic']))
		{
			
		}
		$data["room_config"] = implode("-", $data['room_config']);
		return $this->insert($data);
	}
	/**
	 * 修改房间
	 * 修改时间2015年3月18日 16:34:12
	 * 
	 * @author yzx
	 * @param array $data
	 * @param int $room_id
	 * @return boolean
	 */
	public function editRoom($data,$room_id)
	{
		if (is_array($data['gender_restrictions']) && !empty($data['gender_restrictions']))
		{
			$data['gender_restrictions'] = implode("-", $data['gender_restrictions']);
		}
		if (is_array($data['room_config']) && !empty($data['room_config']))
		{
			$data['room_config'] = implode("-", $data['room_config']);
		}
		$room_data = $this->getOne(array("room_id"=>$room_id));
		//TODO 修改图片
		if (is_array($data['room_pic']) && !empty($data['room_pic']))
		{
			
		}
		if (!empty($room_data))
		{
			$result = $this->edit(array("room_id"=>$room_id), $data);
			if ($result)
			{
				return true;
			}
		}
		return false;
	}
	/**
	 * 根据房间ID获取房源
	 * 修改时间2015年3月19日 15:58:32
	 * 
	 * @author yzx
	 * @param int $roomId
	 * @return array|boolean
	 */
	public function getRoomByHouse($roomId)
	{
		$select = $this->_sql_object->select(array("r"=>"room"))
				  ->leftjoin(array("h"=>'house'),"r.house_id = h.house_id","*")
				  ->where(array("r.room"=>$roomId));
		$result = $select->execute();
		if (!empty($result))
		{
			return $result[0];
		}
		return false;
	}
}