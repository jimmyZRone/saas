<?php
namespace Common\Helper\Erp;
class FlatTemplate extends \Core\Object
{
	/**
	 * 根据公寓获取模版
	 * 修改时间2015年4月20日 10:09:54
	 * 
	 * @author yzx
	 * @param int $flatId
	 * @return Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >
	 */
	public function getDataByFlat($flatId)
	{
		$flatTemplateModel = new \Common\Model\Erp\FlatTemplate();
		$configModel = new \Common\Model\Erp\SystemConfig();
		$roomFocusHelper = new RoomFocus();
		$room_type = $configModel->getFind("FocusRoom", "focus_room_type");
		$sql = $flatTemplateModel->getSqlObject();
		$select = $sql->select("flat_template");
		$select->where(array("flat_id"=>$flatId,"is_delete"=>0));
		$result = $select->execute();
		if (!empty($result))
		{
			foreach ($result as $key=>$val)
			{
				$room_data = $roomFocusHelper->getRoomByTemplate($val['template_id']);
				$result[$key]['roomType'] = $room_type[$val['house_type']];
				$result[$key]['room_count'] = count($room_data);
			}
		}
		return $result;
	}
	/**
	 * 删除模版
	 * 修改时间2015年4月20日 11:26:30
	 * 
	 * @author yzx
	 * @param unknown $templateId
	 * @return boolean
	 */
	public function deleteTemplate($templateId)
	{
		$roomFocusHelper = new RoomFocus();
		$templateModel = new \Common\Model\Erp\FlatTemplate();
		$room_data = $roomFocusHelper->getRoomByTemplate($templateId);
		if (!empty($room_data))
		{
			return array("status"=>false,"msg"=>"该模板房间下存在房间数据");
		}
	   if ($templateModel->deleteTemplate($templateId))
	   {
	   	return array("status"=>true,"msg"=>"");
	   }
	   return array("status"=>false,"msg"=>"未知错误");
	}
	/**
	 * 获取模版数据
	 * 修改时间2015年4月20日 13:41:46
	 *
	 * @author yzx
	 * @param int $templateId
	 * @return array
	 */
	public function getTemplateData($templateId)
	{
		$templateModel = new \Common\Model\Erp\FlatTemplate();
		$flatModel = new \App\Web\Mvc\Model\Flat();
		$data = array();
		$template_data = $templateModel->getOne(array("template_id"=>$templateId));
		if (!empty($template_data))
		{
			$flat_data = $flatModel->getOne(array("flat_id"=>$template_data['flat_id']));
		}
		$data = array("template_data"=>$template_data,"flat_data"=>$flat_data);
		return $data;
	}
}
