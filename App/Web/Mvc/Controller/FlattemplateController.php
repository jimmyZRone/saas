<?php
namespace App\Web\Mvc\Controller;
use App\Web\Lib\Request;
use Common\Model\Erp\FlatTemplate;
class FlattemplateController extends \App\Web\Lib\Controller
{
	/**
	 * 添加房间模版
	 * 修改时间2015年3月24日 17:12:08
	 * 
	 * @author yzx
	 */
	protected function addAction()
	{
		if (Request::isPost())
		{
			$flatTemplateModel = new \Common\Model\Erp\FlatTemplate();
			$data= array();
			$flat_id = Request::queryString("post.flat_id",0,"int");
			$template_name = Request::queryString("post.template_name",'',"string");
			$house_type = Request::queryString("post.house_type",'',"string");
			$money = Request::queryString("post.money",0,"float");
			$pledge_month = Request::queryString("post.pledge_month",0,"int");
			$pay_month = Request::queryString("post.pay_month",0,"int");
			$area = Request::queryString("post.area",0,"int");
			$room_config = Request::queryString("post.room_config");
			$mark = Request::queryString("post.mark",'',"string");
			$room_id = Request::queryString("post.room_id");
			$template_pic = Request::queryString("post.template_pic");
			
			$data['flat_id'] = $flat_id;
			$data['template_name'] = $template_name;
			$data['house_type'] = $house_type;
			$data['money'] = $money;
			$data['pledge_month'] = $pledge_month;
			$data['pay_month'] = $pay_month;
			$data['area'] = $area;
			$data['room_config'] = $room_config;
			$data['mark'] = $mark;
			$data['room_id'] = $room_id;
			$data['template_pic'] = $template_pic;
			$result = $flatTemplateModel->addTemplate($data);
			if ($result)
			{
				return $this->returnAjax(array("status"=>1,"data"=>$result));
			}
			return $this->returnAjax(array("status"=>0,"data"=>false));
		}
	}
	/**
	 * 修改模版
	 * 修改时间2015年3月25日 16:17:33
	 * 
	 * @author yzx
	 */
	public function updateAction()
	{
		if (Request::isPost())
		{
			$flatTemplatModel = new FlatTemplate();
			$template_id = Request::queryString("post.template_id",0,"int");
			$flat_id = Request::queryString("post.flat_id",0,"int");
			$template_name = Request::queryString("post.template_name",'',"string");
			$house_type = Request::queryString("post.house_type",'',"string");
			$money = Request::queryString("post.money",0,"float");
			$pledge_month = Request::queryString("post.pledge_month",0,"int");
			$pay_month = Request::queryString("post.pay_month",0,"int");
			$area = Request::queryString("post.area",0,"int");
			$room_config = Request::queryString("post.room_config");
			$mark = Request::queryString("post.mark",'',"string");
			$room_id = Request::queryString("post.room_id");
			$template_pic = Request::queryString("post.template_pic");
			$update_room = Request::queryString("post.update_room",0,"int");
			
			$data['flat_id'] = $flat_id;
			$data['template_name'] = $template_name;
			$data['house_type'] = $house_type;
			$data['money'] = $money;
			$data['pledge_month'] = $pledge_month;
			$data['pay_month'] = $pay_month;
			$data['area'] = $area;
			$data['room_config'] = $room_config;
			$data['mark'] = $mark;
			$data['room_id'] = $room_id;
			$data['template_pic'] = $template_pic;
			$data['update_room'] = $update_room;
			$result = $flatTemplatModel->updaeTemplate($template_id, $data);
			if ($result)
			{
				return $this->returnAjax(array("status"=>1,"data"=>$result));
			}
			return $this->returnAjax(array("status"=>0,"data"=>false));
		}
	}
	/**
	 * 获取公寓房间
	 * 修改时间2015年3月25日 13:46:17
	 * 
	 * @author yzx
	 */
	protected function flatroomAction()
	{
		if (Request::isGet())
		{
			$roomFoceusModel = new \Common\Model\Erp\RoomFocus();
			$flat_id = Request::queryString("get.flat_id",0,"int");
			$floor = Request::queryString("post.floor",'',"string");
			$result = $roomFoceusModel->getDataByFlatId($flat_id, $floor);
			if ($result)
			{
				return $this->returnAjax(array("status"=>1,"data"=>$result));
			}
			return $this->returnAjax(array("status"=>0,"data"=>false));
		}
	}
}