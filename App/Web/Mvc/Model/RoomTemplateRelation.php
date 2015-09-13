<?php
namespace App\Web\Mvc\Model;
class RoomTemplateRelation extends Common
{
	/**
	 * 添加房间也模版关联
	 * 修改时间2015年3月24日 16:50:08
	 * 
	 * @author yzx
	 * @param array $data
	 * @return boolean
	 */
	public function addRealation($data)
	{
		$relation_data = array();
		$result = array();
		if (is_array($data['room_id']) && !empty($data['room_id']))
		{
			foreach ($data['room_id'] as $key=>$val)
			{
				$relation_data['room_id'] = $val;
				$relation_data['template_id'] = $data['template_id'];
				$result[] = $this->insert($relation_data);
			}
		}
		if (!empty($result))
		{
			return true;			
		}
		return false;
	}
	/**
	 * 删除关联
	 * 修改时间2015年3月25日 17:26:21
	 * 
	 * @author yzx
	 * @param int $roomId
	 * @param int $template_id
	 * @return boolean
	 */
	public function deleteReation($template_id)
	{
		return $this->delete(array("template_id"=>$template_id));
	}
	/**
	 * 批量添加房源
	 * 修时间2015年3月25日 10:44:33
	 * 
	 * @author yzx
	 * @param array $data
	 * @return boolean
	 */
	public function houseFactory($data,$is_house=true)
	{
		$roomFocus = new RoomFocus();
		$room_focus_data = array();
		$result = array();
		if (is_array($data) && !empty($data))
		{
			$this->Transaction();
			foreach ($data as $key=>$val)
			{
				$room_focus_data["flat_id"] = $data['flat_id'];
				$room_focus_data['floor'] = $val['floor'];
				$room_focus_data['custom_number'] = $val['custom_number'];
				$room_focus_data['manager_id'] = $data['create_uid'];
				$room_focus_data['create_uid'] = $data['create_uid'];
				$room_focus_data['create_time'] = time();
				if (!$is_house)
				{
					$room_focus_data['room_number'] = $val['room_number'];
				}
				if ($roomFocus->insert($room_focus_data))
				{
					$this->rollback();
					$result = false;
					break;
				}else 
				{
					$result = true;
					$this->commit();
				}
			}
		}
		if ($result)
		{
			return true;
		}
		return false;
	}
}