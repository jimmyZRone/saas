<?php
namespace App\Web\Mvc\Model;
class FlatTemplate extends Common
{
	/**
	 * 添加模版
	 * 修改时间2015年3月24日 16:30:52
	 * 
	 * @author yzx
	 * @param array $data
	 * @return number|boolean
	 */
	public function addTemplate($data)
	{
		$roomTenplateRelationModel = new RoomTemplateRelation();
		if (is_array($data['room_config']) && !empty($data['room_config']))
		{
			$data['room_config'] = implode("-", $data['room_config']);
		}else 
		{
			unset($data['room_config']);
		}
		$this->Transaction();
		$new_template_id = $this->insert($data);
		if(!$new_template_id)
		{
			$this->rollback();
			return false;
		}
		$data['template_id'] = $new_template_id;
		$r_result = $roomTenplateRelationModel->addRealation($data);
		if (!$r_result)
		{
			$this->rollback();
			return false;
		}
		//TODO 保存图片
		
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
		$roomTemplateRelationModel->deleteReation($templateId);
		$this->Transaction();
		$result = $this->edit(array("template_id"=>$templateId), $data);
		if (!$result)
		{
			$this->rollback();
		}
		if (intval($data['update_room'])>0)
		{
			if (is_array($data['room_id']) && !empty($data['room_id']))
			{
				foreach ($data['room_id'] as $key=>$val)
				{
					$roomFocusModel->updateData($val, $data);
				}
			}
		}
		$this->commit();
		return true;
	}
}