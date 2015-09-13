<?php
namespace App\Web\Mvc\Controller\Centralized;
use App\Web\Lib\Request;
use App\Web\Helper\Url;
use Common\Model\Erp\RoomTemplateRelation;
use Common\Helper\Permissions;
use Common\Helper\Permissions\Hook\SysHousingManagement;
class FlattemplateController extends \App\Web\Lib\Controller
{
	protected $_auth_module_name = 'sys_housing_management';
	/**
	 * 集中式添加模版
	 * 修改时间2015年5月27日 15:08:29
	 * 
	 * @author yzx
	 */
	protected function addAction()
	{
		if (!$this->verifyModulePermissions(Permissions::INSERT_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("Centralized-Flattemplate/add")));
		}
		$flatModel = new \Common\Model\Erp\Flat();
		if (!Request::isPost())
		{
			$configModel = new \Common\Model\Erp\SystemConfig();
			$roomFocusHelper = new \Common\Helper\Erp\RoomFocus();
			
			$flat_id = Request::queryString("get.flat_id",0,"int");
			$flat_data = $flatModel->getOne(array("flat_id"=>$flat_id));
			$room_type = $configModel->getFind("FocusRoom", "focus_room_type");
			$public_facilities = $configModel->getFind("House", "public_facilities");
			if ($flat_data['rental_way'] == $flatModel::RENTAL_WAY_CLOSE){
				unset($room_type['1t1']);
				unset($room_type['1t2']);
				unset($room_type['1t3']);
				unset($room_type['3t2']);
				unset($room_type['4t2']);
				unset($room_type['5t2']);
			}
			
			$this->assign("room_type", $room_type);
			$this->assign("public_facilities", $public_facilities);
			$this->assign("flat_data", $flat_data);
			$data = $this->fetch("Centralized/Flattemplate/flat_templat_add");
			return $this->returnAjax(array( "status"=>1,
											"tag_name"=>"集中式添加模版",
											"model_name"=>"flat_template_add",
											"model_js"=>"centralized_add_modeJs",
											"model_href"=>Url::parse("Centralized-FlatTemplate/add"),
											"data"=>$data));
		}else
		{
			$flatTemplateModel = new \Common\Model\Erp\FlatTemplate();
			$data= array();
			$flat_id = Request::queryString("post.flat_id",0,"int");
			
			//判断用户是否对当前对象有权限操作
			if(!$this->verifyDataLinePermissions(Permissions::INSERT_AUTH_ACTION, $flat_id, SysHousingManagement::CENTRALIZED_FLAT)){
				return $this->returnAjax(array('__status__'=>403));
			}
			
			$template_name = Request::queryString("post.template_name",'',"string");
			$house_type = Request::queryString("post.room_type",'',"string");
			$money = Request::queryString("post.rent_money",0,"float");
			$pledge_month = Request::queryString("post.money_ya",0,"int");
			$pay_month = Request::queryString("post.money_fu",0,"int");
			$area = Request::queryString("post.room_area",0);
			$room_config = Request::queryString("post.roomconfig");
			$mark = Request::queryString("post.bz_txt",'',"string");
			$room_id = Request::queryString("post.config_house");
			$template_pic = Request::queryString("post.template_pic");
			$uptate_type = Request::queryString("post.updatetype",1,"int");
			$image = Request::queryString("post.img_list");
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
			$data['image'] = $image;
			if ($uptate_type <=0)
			{
				$uptate_type=false;
			}else 
			{
				$uptate_type=true;
			}
			$result = $flatTemplateModel->addTemplate($data,$uptate_type);
			if ($result)
			{
				$flat_data = $flatModel->getOne(array("flat_id"=>$flat_id,"is_delete"=>0));
				$LandlordContractModel = new \Common\Helper\Erp\LandlordContract();
				$is_add_landlord = $LandlordContractModel->CheckHouseName(\Common\Model\Erp\LandlordContract::HOUSE_TYPE_F, $flat_data['flat_name'] , $this->user,$flat_id);
				$return_result['status'] = 1;
				$return_result['data'] = $result;
				$return_result['url'] = Url::parse("Centralized-Flat/edit")."&flat_id=".$flat_id;
				$return_result['tag'] = Url::parse("Centralized-Flat/list");
				if ($is_add_landlord==false){
					$return_result['landlord_url'] = Url::parse("landlord-index/add")."&house_type=2"."&house_id=".$flat_id;
				}
				return $this->returnAjax($return_result);
			}
			return $this->returnAjax(array("status"=>0,"data"=>"添加失败"));
		}
	}
	/**
	 * 修改模版
	 * 修改时间2015年5月27日 15:08:53
	 * 
	 * @author yzx
	 */
	public function updateAction()
	{
		if (!$this->verifyModulePermissions(Permissions::SELECT_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("Centralized-Flattemplate/update")));
		}
		if (!Request::isPost())
		{
			$template_id = Request::queryString("get.template_id",0,"int");
			$page = Request::queryString("get.page",0,'int');
			
			$flatTemplatHelper = new \Common\Helper\Erp\FlatTemplate();
			$configModel = new \Common\Model\Erp\SystemConfig();
			$roomFocusHelper = new \Common\Helper\Erp\RoomFocus();
			$attachmetModel = new \Common\Model\Erp\Attachments();
			$imag = $attachmetModel->getImagList("falt_template", $template_id);
			$flattemplat_data = $flatTemplatHelper->getTemplateData($template_id);
			$public_facilities = $configModel->getFind("House", "public_facilities");
			$public_config = $configModel->getFind("House", "PublicConfig");
			$room_type = $configModel->getFind("FocusRoom", "focus_room_type");
			$room_data = $roomFocusHelper->getRoomByTemplate($template_id,true);
			$room_config = explode("-", $flattemplat_data['template_data']['room_config']);
			if ($page>0)
			{
				$room_data = $roomFocusHelper->getRoomByTemplate($template_id,true,$page);
				return $this->returnAjax(array("status"=>1,"data"=>$room_data));
			}
			foreach ($public_facilities as $fkey=>$fval)
			{
				$config_data[$fkey]['name'] = $fval;
				$config_data[$fkey]['value'] = $fkey;
				if (in_array($fkey, $room_config))
				{
					$config_data[$fkey]['is_read'] = 1;
				}else 
				{
					$config_data[$fkey]['is_read'] = 0;
				}
			}
			if ($flattemplat_data['flat_data']['rental_way'] == \Common\Model\Erp\Flat::RENTAL_WAY_CLOSE){
				unset($room_type['1t1']);
				unset($room_type['1t2']);
				unset($room_type['1t3']);
				unset($room_type['3t2']);
				unset($room_type['4t2']);
				unset($room_type['5t2']);
			}
			
			$this->assign("imag", $imag);
			$this->assign("room_type", $room_type);
			$this->assign("public_facilities", $config_data);
			$this->assign("flat_data", $flattemplat_data['flat_data']);
			$this->assign("template_data", $flattemplat_data['template_data']);
			$this->assign("public_config", $public_config);
			$this->assign("room_data", $room_data['data']);
			$data = $this->fetch("Centralized/Flattemplate/flat_template_edit");
			
			return $this->returnAjax(array(	"status"=>1,
											"tag_name"=>"修改模版",
											"model_name"=>"flat_template_edit",
											"model_js"=>"centralized_add_modeJs",
											"model_href"=>Url::parse("Centralized-FlatTemplate/update"),
											"data"=>$data));
		}else
		{
			//验证是否有编辑权限
			if (!$this->verifyModulePermissions(Permissions::UPDATE_AUTH_ACTION)){
				return $this->returnAjax(array('__status__'=>403));
			}
			$flatTemplatModel = new \Common\Model\Erp\FlatTemplate();
			$template_id = Request::queryString("post.template_id",0,"int");
			$template_name = Request::queryString("post.template_name",'',"string");
			$house_type = Request::queryString("post.room_type",'',"string");
			$money = Request::queryString("post.rent_money",0,"float");
			$pledge_month = Request::queryString("post.money_ya",0,"int");
			$pay_month = Request::queryString("post.money_fu",0,"int");
			$area = Request::queryString("post.room_area",0);
			$room_config = Request::queryString("post.roomconfig");
			$mark = Request::queryString("post.bz_txt",'',"string");
			$room_id = Request::queryString("post.config_house");
			$template_pic = Request::queryString("post.template_pic");
			$update_room = Request::queryString("post.updatetype",0,"int");
			$imag = Request::queryString("post.img_list");
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
			$data['template_id'] = $template_id;
			$data['image'] = $imag;
			$template_data = $flatTemplatModel->getOne(array("template_id"=>$template_id));
			
			//判断用户是否对当前对象有权限操作
			if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $template_data['flat_id'], SysHousingManagement::CENTRALIZED_FLAT)){
				return $this->returnAjax(array('__status__'=>403));
			}
			
			$result = $flatTemplatModel->updaeTemplate($template_id, $data);
			if ($result)
			{
				return $this->returnAjax(array("status"=>1,"data"=>$result,
											   "url"=>Url::parse("Centralized-Flat/edit")."&flat_id=".$template_data['flat_id']));
			}
			return $this->returnAjax(array("status"=>0,"data"=>"修改失败"));
		}
	}
	/**
	 * 获取公寓房间
	 * 修改时间2015年5月27日 15:09:58
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
	/**
	 * 配置房间
	 * 修改时间2015年5月27日 15:10:10
	 * 
	 * @author yzx
	 */
	protected function configroomAction()
	{
		if (!$this->verifyModulePermissions(Permissions::SELECT_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("Centralized-Flattemplate/configroom")));
		}
		if (!Request::isPost())
		{
			$flat_id = Request::queryString("get.flat_id",0,"int");
			
			//判断用户是否对当前对象有权限操作
			if(!$this->verifyDataLinePermissions(Permissions::INSERT_AUTH_ACTION, $flat_id, SysHousingManagement::CENTRALIZED_FLAT)){
				return $this->returnAjax(array('__status__'=>403));
			}
			
			$is_edit = Request::queryString("get.is_edite",0,"int");
			$template_id = Request::queryString("get.template_id",0,"int");
			$flatModel = new \Common\Model\Erp\Flat();
			$roomFocusHelper = new \Common\Helper\Erp\RoomFocus();
			
			//有问题
// 			$floor = $roomFocusHelper->getRoomFloorByFlatId($flat_id,false);
 			$room_list = $roomFocusHelper->getRoomFloorByFlatId($flat_id);

			$floor = $roomFocusHelper->havingFloorTotal($flat_id,false);
			$RoomFocusModel = new \Common\Model\Erp\RoomFocus();
			//有问题
			/* $room_list = $RoomFocusModel->getListData($flat_id, $this->user, 0, 0,array());
			$room_list = $room_list['data']; */
			
			$room_list_count = $roomFocusHelper->getRoomFloorByFlatId($flat_id,true,true);
			$flat_data = $flatModel->getOne(array("flat_id"=>$flat_id));
			$room_count = $roomFocusHelper->getRoomCount($flat_id);
			$room_config_count=$roomFocusHelper->getRoomCount($flat_id,true);
			$this->assign("floor", $floor);
			$this->assign("list_data", $room_list);
			$this->assign("flat_data", $flat_data);
			$this->assign("template_id", $template_id);
			$this->assign("room_count", $room_count['page']['count']);
			$this->assign("room_config_count", $room_config_count['page']['count']);
			$this->assign("no_config", ($room_count['page']['count'])-($room_config_count['page']['count']));
			if ($is_edit==1)
			{
				$parent_url = Url::parse("Centralized-FlatTemplate/update");
			}else 
			{
				$parent_url = Url::parse("Centralized-FlatTemplate/add");
			}
			$this->assign("parent_url", $parent_url);
			$data = $this->fetch("Centralized/Flattemplate/config_rooms_template");
			//有问题
			//$data = $this->fetch("Centralized/Flattemplate/config_rooms_template",\Core\Mvc\Template::MODE_PHP);
			
			return $this->returnAjax(array(	"status"=>1,
											"tag_name"=>"配置房间",
											"model_name"=>"flat_template_room_config",
											"model_js"=>"centralized_RoomsConfigJs",
											"model_href"=>Url::parse("Centralized-FlatTemplate/configroom"),
											"data"=>$data));
		}
	}
	/**
	 * 删除模版
	 * 修改时间2015年5月27日 15:10:35
	 * 
	 * @author yzx
	 */
	protected function deleteAction()
	{
		if (!$this->verifyModulePermissions(Permissions::UPDATE_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403));
		}
		$flatTemplatModel = new \Common\Model\Erp\FlatTemplate();
		$template_id = Request::queryString("get.template_id",0,"int");
		$template_data = $flatTemplatModel->getOne(array("template_id"=>$template_id));
		//判断用户是否对当前对象有权限操作
		if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $template_data['flat_id'], SysHousingManagement::CENTRALIZED_FLAT)){
			return $this->returnAjax(array('__status__'=>403));
		}
		
		$flatTemplateHelper = new \Common\Helper\Erp\FlatTemplate();
		$result = $flatTemplateHelper->deleteTemplate($template_id);
		if ($result['status'])
		{
			return $this->returnAjax(array("status"=>1,"data"=>"删除成功"));
		}
		return $this->returnAjax(array("status"=>0,"data"=>$result['msg']));
	}
	/**
	 * 删除单个模板房间
	 * 修改时间2015年6月4日20:50:38
	 * 
	 * @author yzx
	 */
	public function deletroomAction()
	{
		if (!$this->verifyModulePermissions(Permissions::UPDATE_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403));
		}
		$template_id = I("get.template_id",0,"int");
		$room_id = I("get.room_id",0,"int");
		
		//判断用户是否对当前对象有权限操作
		if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION,$room_id, SysHousingManagement::CENTRALIZED)){
			return $this->returnAjax(array('__status__'=>403));
		}
		
		$roomTemplateRelationModel = new RoomTemplateRelation();
		$roomId = array($room_id);
		$result = $roomTemplateRelationModel->deleteReationByRoomId($roomId);
		if ($result)
		{
			return $this->returnAjax(array("status"=>1,"data"=>"删除成功"));
		}
		return $this->returnAjax(array("status"=>0,"data"=>"删除失败"));
	}
}