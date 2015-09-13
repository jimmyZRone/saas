<?php
namespace Common\Model\Erp;
class FlatTemplate extends \Common\Model\Erp
{
	/**
	 * 添加模版
	 * 修改时间2015年3月24日 16:30:52
	 * 
	 * @author yzx
	 * @param array $data
	 * @return number|boolean
	 */
	public function addTemplate($data,$isUpdateRoom=false)
	{
		$roomTenplateRelationModel = new RoomTemplateRelation();
		$roomFocusHelper = new \Common\Helper\Erp\RoomFocus();
		$attachmentModel = new Attachments();
		if (is_array($data['room_config']) && !empty($data['room_config']))
		{
			$data['room_config'] = implode("-", $data['room_config']);
		}else 
		{
			$data['room_config']='';
		}
		$data['create_time'] = time();
		$this->Transaction();
		$new_template_id = $this->insert($data);
		if(!$new_template_id)
		{
			$this->rollback();
			return false;
		}
		$data['template_id'] = $new_template_id;
		if (!empty($data['room_id'])){
			$r_result = $roomTenplateRelationModel->addRealation($data);
			if (!$r_result)
			{
				$this->rollback();
				return false;
			}
		}
		
		//TODO 保存图片
		if (is_array($data['image']) && !empty($data['image']))
		{
			foreach ($data['image'] as $key=>$val)
			{
				$imag_data['module'] = 'falt_template';
				$imag_data['key'] = $val;
				$imag_data['entity_id'] = $new_template_id;
				$attachmentModel->insertData($imag_data);
			}
		}
		if ($isUpdateRoom && !empty($data['room_id']))
		{
			$roomFocusHelper->updataRooms($data['room_id'], $data);
		}
		$this->commit();
		return $new_template_id;
	}
	/**
	 * 修改模版
	 * 修改时间2015年3月25日 16:13:43
	 * 
	 * @author yzx
	 * @param int $templateId
	 * @param array $data
	 * @return boolean
	 */
	public function updaeTemplate($templateId,$data)
	{
		$roomFocusModel = new RoomFocus();
		$roomTemplateRelationModel = new RoomTemplateRelation();
		$attachmentModel = new Attachments();
		$this->Transaction();
		if (is_array($data['room_config']) && !empty($data['room_config']))
		{
			$data['room_config'] = implode("-", $data['room_config']);
		}
		$result = $this->edit(array("template_id"=>$templateId), $data);
		if (!$result)
		{
			$this->rollback();
		}
		if (is_array($data['room_id']))
		{
			$roomTemplateRelationModel->addRealation($data);
		}
		//更新房间
		if (intval($data['update_room'])>0)
		{
			if ($data['update_room']==1)
			{
				$roomFocusHelper = new \Common\Helper\Erp\RoomFocus();
				$room_data = $roomFocusHelper->getRoomByTemplate($templateId);
				foreach ($room_data as $key=>$val)
				{
					$roomFocusModel->updateData($val['room_focus_id'], $data);
				}
			}
		}
		//添加图片
		$attachmentModel->delete(array("module"=>"falt_template","entity_id"=>$templateId));
		if (is_array($data['image']) && !empty($data['image']))
		{
			foreach ($data['image'] as $key=>$val)
			{
				$imag_data['module'] = 'falt_template';
				$imag_data['key'] = $val;
				$imag_data['entity_id'] = $templateId;
				$attachmentModel->insertData($imag_data);
			}
		}
		$this->commit();
		return true;
	}
	/**
	 * 删除模版
	 * 修改时间2015年4月20日 10:56:36
	 * 
	 * @author yzx
	 * @param int $templateId
	 * @return boolean
	 */
	public function deleteTemplate($templateId)
	{
		return $this->delete(array('template_id'=>$templateId));
	}
}