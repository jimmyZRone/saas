<?php
namespace Common\Model\Erp;
class RoomTemplateRelation extends \Common\Model\Erp
{
	/**
	 * 添加房间模版关联
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
		if (!is_array(date('room_id')) && $data['room_id']<=0){
			$this->deleteReation($data['template_id']);
		}
		if (is_array($data['room_id']) && !empty($data['room_id']))
		{
			$this->deleteReation($data['template_id']);
			foreach ($data['room_id'] as $rkey=>$rval){
				$this->delete(array("room_id"=>$rval['room_id']));
			}
			foreach ($data['room_id'] as $key=>$val)
			{
				$relation_data['room_id'] = $val['room_id'];
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
	 * 根据房间ID删除关联
	 * 修改时间2015年4月20日 15:32:05
	 * 
	 * @author yzx
	 * @param int $roomId
	 * @return boolean
	 */
	public function deleteReationByRoomId(array $roomId)
	{
		if (!empty($roomId))
		{
			$this->Transaction();
			foreach ($roomId as $key=>$val)
			{
				$result = $this->delete(array("room_id"=>$val));
				if (!$result){
					$this->rollback();
					return false;
					exit();
				}
			}
			$this->commit();
			return true;
		}
	}
	/**
	 * 批量添加房源
	 * 修时间2015年3月25日 10:44:33
	 * 
	 * @author yzx
	 * @param array $data
	 * @return boolean
	 */
	public function houseFactory($data,$user,$is_house=true)
	{
		$roomFocus = new RoomFocus();
		$room_focus_data = array();
        $flat = new Flat();
        $flat_data = $flat->getOne(['flat_id'=>$data[0]['flat_id']]);
		if (is_array($data) && !empty($data))
		{
			$all_insert_data = array();
			foreach ($data as $key=>$val)
			{
				$room_focus_data["flat_id"] = $val['flat_id'];
				$room_focus_data['floor'] = $val['floor'];
				$room_focus_data['custom_number'] = $val['custom_number'];
				$room_focus_data['full_name'] = $flat_data['flat_name'].$val['floor'].'楼'.$val['custom_number'].'号';
				$room_focus_data['owner_id'] = $user['user_id'];
				$room_focus_data['create_uid'] = $user['user_id'];
				$room_focus_data['create_time'] = time();
				$room_focus_data['company_id'] = $user['company_id'];
				$room_focus_data['house_number'] = $val['house_number'];
				$room_focus_data['status'] = RoomFocus::STATUS_NOT_RENTAL;
				if (!$is_house)
				{
					$room_focus_data['room_number'] = $val['room_number'];
				}
				$all_insert_data[] = $room_focus_data;
			}
			$result = $roomFocus->insert($all_insert_data);
		}
		if ($result)
		{
			return true;
		}
		return false;
	}
}