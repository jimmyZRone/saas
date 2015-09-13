<?php
namespace App\Web\Mvc\Controller\Centralized;
use App\Web\Lib\Request;
use App\Web\Helper\Url;
use Common\Helper\Permissions;
use Common\Helper\Permissions\Hook\SysHousingManagement;
class FlatController extends \App\Web\Lib\Controller
{
	protected $_auth_module_name = 'sys_housing_management';
	/**
	 * 获取公寓列表
	 * 修改时间2015年3月23日 13:17:15
	 *
	 */
	protected function listAction()
	{
		if (!$this->verifyModulePermissions(Permissions::SELECT_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("Centralized-Flat/list")));
		}
		if (Request::isGet())
		{
			$flatModel = new \Common\Model\Erp\Flat();
			$flatHelper = new \Common\Helper\Erp\Flat();
			$page = Request::queryString("get.page",0,"int");
// 			$result = $flatModel->flatList($page,$this->user);
// 			$ext_data = $flatHelper->getListExt($result['data']);
			$ext_data = $flatHelper->getFlatListAndExt($this->user);
			$this->assign("list", $ext_data);
			$data = $this->fetch("Centralized/flat_list");
			return $this->returnAjax(array(	"status"=>1,
											"tag_name"=>"集中式公寓",
											"model_name"=>"flat_list",
											"model_js"=>"centralized_ManageJs",
											"model_href"=>Url::parse("Centralized-Flat/list"),
											"data"=>$data));
		}
		return $this->returnAjax(array('status'=>0,'data'=>false));
	}
	/**
	 * 添加公寓
	 * 修改时间2015年3月24日 10:07:35
	 *
	 * @author yzx
	 */
	protected function addAction()
	{
		if (!$this->verifyModulePermissions(Permissions::INSERT_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("Centralized-Flat/add")));
		}
		$user = $this->user;
		$data = array();
		$flatHelper = new  \Common\Helper\Erp\Flat();
		if (!Request::isPost())
		{
			$areaModel = new \Common\Model\Erp\Area();
			$feeTypeHelper = new \Common\Helper\Erp\FeeType();
			$systemConfigModel = new \Common\Model\Erp\SystemConfig();
			$contractModel = new \Common\Model\Erp\LandlordContract();
			$fee_type = $feeTypeHelper->getCompanyFeeType($this->user);
			$feeTypeHelper->getUnsetFee($fee_type);
			$pay_config = $systemConfigModel->getFind("System", "PayConfig");
			$area_data = $areaModel->getDataByCity($user['city_id']);
			$contract_id = I("get.cid",0,"int");
			$contract_data = $contractModel->getOne(array("contract_id"=>$contract_id));
			unset($pay_config[3]);
			unset($pay_config[4]);
			$flatHelper->clearAllSession();
			//print_r($area_data);die();
			$this->assign("business", Url::parse("Centralized-Flat/business"));
			$this->assign("add_url",  Url::parse("Centralized-Flat/add"));
			$this->assign("fee_type", $fee_type);
			$this->assign("pay_config", $pay_config);
			$this->assign("area_data", $area_data);
			$this->assign("contract_data", $contract_data);
			$data = $this->fetch("Centralized/flat_add");

			return $this->returnAjax(array(	"status"=>1,
											"tag_name"=>"集中式公寓添加",
											"model_name"=>"flat_add",
											"model_js"=>"centralized_Depart_InfoJs",
											"model_href"=>Url::parse("Centralized-Flat/add"),
											"data"=>$data));
		}else
		{
			$flat_input_data = \Core\Session::read("flat_input_data");
			$flatModel = new \Common\Model\Erp\Flat();
			$flat_name = Request::queryString("post.name",'',"string");
			$custom_number = Request::queryString("post.room_Num",'',"string");
			$area_id = Request::queryString("post.opera_Id",0,"int");
			$business_id = Request::queryString("post.bussiness_Id",'',"int");
			$address = Request::queryString("post.address",'',"string");
			$longitude = Request::queryString("post.longitude",0,"string");
			$latitude = Request::queryString("post.latitude",0,"string");
			$total_floor = Request::queryString("post.floor_Total",0,"int");
			$room_number = Request::queryString("post.room_Nums",0,"int");
			$rental_way = Request::queryString("post.rent_Style",0,"int");
			$group_number = Request::queryString("post.floor_RoomTotal",0,"int");
			$floor_number = Request::queryString("post.floor_number");
			$fee_data = Request::queryString("post.fee_data");
			if (strlen($flat_name)>40)
			{
				 return $this->returnAjax(array("status"=>0,"data"=>"公寓名称不能大于15个字符"));
			}
			$data['flat_name'] = $flat_name;
			$data['custom_number'] = $custom_number;
			$data['city_id'] = $user['city_id'];
			$data['area_id'] = $area_id;
			$data['business_id'] = $business_id;
			$data['address'] = $address;
			$data['longitude'] = $longitude;
			$data['latitude'] = $latitude;
			$data['total_floor'] = $total_floor;
			$data['group_number'] = $group_number;
			$data['rental_way'] = $rental_way;
			$data['room_number'] = $room_number;
			$data['company_id'] = $user['company_id'];
			$data['create_uid'] = $user['user_id'];
			$data['owner_id'] = $user['user_id'];
			$data['create_time'] = time();
			if (!empty($flat_input_data)){
				if ($flat_input_data['total_floor'] != $data['total_floor'] ||
						$flat_input_data['group_number']!=$data['group_number'] ||
						$flat_input_data['room_number'] !=$data['room_number'])
				{
						$flatHelper->clearAllSession();
				}
			}
			\Core\Session::save("flat_input_data", $data);
			
			$LandlordContractModel = new \Common\Helper\Erp\LandlordContract();
			$result = $flatModel->addFlat($data,$this->user,$fee_data);

            if (is_array($result)){
				return $this->returnAjax(array("status"=>0,"data"=>$result['message']));
			}
			if ($result)
			{
				if ($this->user["is_manger"]==0){
					
					//添加权限START
					$permissions = Permissions::Factory('sys_housing_management');
					$permissions->SetVerify(
							$permissions::LINE_BLOCK_ACCESS,
							$permissions::SELECT_AUTH_ACTION,
							$permissions::USER_AUTHENTICATOR,
							$this->user['user_id'],
							$result,
							1,
							\Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED_FLAT
					);
				}
				
				$return_result['status']=1;
				$return_result['data']=array("flat_id"=>$result,"flat_name"=>$flat_name);
				$return_result['tag']=Url::parse("Centralized-Flattemplate/add")."&flat_id=".$result;
				$return_result['refresh_url'] = Url::parse("Centralized-Flat/list");
				return $this->returnAjax($return_result);
			}
			return $this->returnAjax(array("status"=>0,"data"=>"添加失败"));
		}
	}
	/**
	 * 修改公寓
	 * 修改时间2015年3月25日 17:22:53
	 *
	 * @author yzx
	 */
	protected function editAction()
	{
		if (!$this->verifyModulePermissions(Permissions::SELECT_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("Centralized-Flat/edit")));
		}
		
		$user = $this->user;
		$flatModel = new \Common\Model\Erp\Flat();
		$areaModel = new \Common\Model\Erp\Area();
		$businessModel = new \Common\Model\Erp\Business();
		$flatTemplateHelper = new  \Common\Helper\Erp\FlatTemplate();
		$feeTypeHelper = new \Common\Helper\Erp\FeeType();
		$feeHelper = new \Common\Helper\Erp\Fee();
		$configModel = new \Common\Model\Erp\SystemConfig();
		$floorNumberModel = new \Common\Model\Erp\FloorNumber();
		if (!Request::isPost())
		{
			$flat_id = Request::queryString("get.flat_id");
			if ($flat_id<=0)
			{
				return $this->returnAjax(array("status"=>1,"data"=>"没有找到公寓ID"));
			}
			
			//判断用户是否对当前对象有权限操作
			if(!$this->verifyDataLinePermissions(Permissions::SELECT_AUTH_ACTION, $flat_id, SysHousingManagement::CENTRALIZED_FLAT)){
				return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("Centralized-Flat/edit")));
			}
			
			$area_data = $areaModel->getDataByCity($user['city_id']);
			$flat_data = $flatModel->getFlatData($flat_id);
			$business_data = $businessModel->getDataByArea($flat_data['area_id'], $user['city_id']);
			$template_data = $flatTemplateHelper->getDataByFlat($flat_id);
			$fee_type = $feeTypeHelper->getCompanyFeeType($this->user);
			$feeTypeHelper->getUnsetFee($fee_type);
			$fee_data = $feeHelper->getDataBySource($flat_id, \Common\Model\Erp\Fee::SOURCE_FLAT);
			$pay_config = $configModel->getFind("System", "PayConfig");
			$floor_number = $floorNumberModel->getFloorNumber($flat_id);
			unset($pay_config[3]);
			unset($pay_config[4]);
			$this->assign("flat_data", $flat_data);
			$this->assign("area_data", $area_data);
			$this->assign("business_data", $business_data);
			$this->assign("template_data", $template_data);
			$this->assign("business", Url::parse("Centralized-Flat/business"));
			$this->assign("fee_type", $fee_type);
			$this->assign("fee_data", $fee_data);
			$this->assign("pay_type", $pay_config);
			$this->assign("floor_number", $floor_number);
			$data = $this->fetch("Centralized/flat_edit");

			return $this->returnAjax(array(	"status"=>1,
											"tag_name"=>"集中式公寓修改",
											"model_name"=>"flat_edit",
											"model_js"=>"centralized_Depart_InfoJs",
											"model_href"=>Url::parse("Centralized-Flat/edit"),
											"data"=>$data));
		}else
		{
			if (!$this->verifyModulePermissions(Permissions::UPDATE_AUTH_ACTION)){
				return $this->returnAjax(array('__status__'=>403));
			}
			$flat_id = Request::queryString("post.flat_id",0,"int");
			
			//判断用户是否对当前对象有权限操作START
			if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $flat_id, SysHousingManagement::CENTRALIZED_FLAT)){
				return $this->returnAjax(array('__status__'=>403));
			}
			
			$flat_name = Request::queryString("post.name",'',"string");
			$custom_number = Request::queryString("post.room_Num",'',"string");
			$area_id = Request::queryString("post.opera_Id",0,"int");
			$business_id = Request::queryString("post.bussiness_Id",'',"int");
			$address = Request::queryString("post.address",'',"string");
			$longitude = Request::queryString("post.longitude",0,"string");
			$latitude = Request::queryString("post.latitude",0,"string");
			$fee_data = Request::queryString("post.fee_data");

			$data['flat_name'] = $flat_name;
			$data['custom_number'] = $custom_number;
			$data['city_id'] = $user['city_id'];
			$data['area_id'] = $area_id;
			$data['business_id'] = $business_id;
			$data['address'] = $address;
			$data['longitude'] = $longitude;
			$data['latitude'] = $latitude;

			if (strlen($flat_name)>40)
			{
				return $this->returnAjax(array("status"=>0,"data"=>"公寓名称不能大于15个字符"));
			}
			$result = $flatModel->editFlat($flat_id, $data,$fee_data);
			if ($result)
			{
				return $this->returnAjax(array("status"=>1,"data"=>$result,'tag'=>Url::parse("Centralized-Flat/list")));
			}
			return $this->returnAjax(array("status"=>0,"data"=>"修改失败"));
		}
	}
	/**
	 * 删除公寓
	 * 修改时间2015年4月15日 10:10:05
	 *
	 * @author yzx
	 */
	protected function deleteAction()
	{
		if (!$this->verifyModulePermissions(Permissions::DELETE_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403));
		}
		$flat_id = Request::queryString("get.flat_id",0,'int');
		if ($flat_id<=0)
		{
			return $this->returnAjax(array("status"=>0,"data"=>"公寓ID错误"));
		}
		
		//判断用户是否对当前对象有权限操作START
		if(!$this->verifyDataLinePermissions(Permissions::DELETE_AUTH_ACTION, $flat_id, SysHousingManagement::CENTRALIZED_FLAT)){
			return $this->returnAjax(array('__status__'=>403));
		}
		
		$faltModel = new  \Common\Model\Erp\Flat();
		$result = $faltModel->deleteFlat($flat_id);
		if($result['status']){
			$hdModel = new \Common\Model\Erp\HousingDistribution();
			$hdModel->delete(array('source'=>\Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED,'source_id'=>$flat_id));
		}
		if($result['status'])
		{
			return $this->returnAjax(array("status"=>1,"data"=>"删除成功"));
		}
		return $this->returnAjax(array("status"=>0,"data"=>$result['msg']));
	}
	/**
	 * 根据区域ID获取商圈
	 * 修改时间2015年4月15日 09:49:42
	 *
	 * @author yzx
	 */
	protected function businessAction()
	{
		$user = $this->user;
		$area_id = Request::queryString("get.area_Id",0,"int");
		if ($area_id<=0)
		{
			return $this->returnAjax(array("status"=>0,"data"=>"区域ID错误"));
		}
		$businessModel = new \Common\Model\Erp\Business();
		$business_data = $businessModel->getDataByArea($area_id, $user['city_id']);
		return $this->returnAjax(array("status"=>1,"data"=>$business_data));
	}
	/**
	 * 自定义房源编号
	 * 修改时间2015年4月15日 17:52:34
	 *
	 * @author yzx
	 */
	protected function housenumberAction()
	{
		$flatHelper = new \Common\Helper\Erp\Flat();
		if (!Request::isPost())
		{
			$houseNumber = Request::queryString("get.house_number",0,"int");
			$floor_num = Request::queryString("get.floor_num",0,"int");
			$flat_name = Request::queryString("get.flat_name",'','string');
			$floor_num_data = $flatHelper->getFloorNumber($floor_num);
			$house_number_data = $flatHelper->houseNumberFactory($houseNumber,true);
			$is_edit = Request::queryString("get.isedite",0,"int");
			
			//构建房间编号
			if ($is_edit){
				$house_data = \Core\Session::read($flatHelper->house_number_sesssion_id);
				if (!empty($house_data)){
					foreach ($house_data as $key=>$val){
						foreach ($val as $vkey=>$vval){
							$count_key = count($vkey);
							$sub_key = substr($vval, $count_key,strlen($vval));
							$house_data[$key]["floor"] = $vkey;
							$house_data[$key]["num"] = $vval;
						}
					}
				}
			}
			
			$this->assign("house_data", $house_data);
			$this->assign("is_edit", $is_edit);
			$this->assign('house_number_data', $house_number_data['data']);
			$this->assign('house_number_data_f', $house_number_data['data_f']);
			$this->assign("flat_name", $flat_name);
			$this->assign('floor_num', $floor_num);
			$data = $this->fetch("Centralized/Flatmodel/flat_house_numer");

			return $this->returnAjax(array(	"status"=>1,
											"tag_name"=>"自定义房源编号",
											"model_name"=>"flat_house_number",
											"model_js"=>"center_define_houseNumerJs",
											"model_href"=>Url::parse("Centralized-Flat/housenumber"),
											"data"=>$data));
		}else
		{
			$floor_num_diy = Request::queryString("post.rooms_numdiy");
			$flatHelper->saveHouseNumber($floor_num_diy);
			\Core\Session::save($flatHelper->is_diy_house, 1);
			return $this->returnAjax(array("status"=>1,"data"=>"保存成功","p_url"=>Url::parse("Centralized-Flat/add")));
		}

	}
	/**
	 * 自定义套内间数
	 * 修改时间2015年4月15日 17:52:34
	 *
	 * @author yzx
	 */
	protected function roomsnumberAction()
	{
		$flatHelper = new \Common\Helper\Erp\Flat();
		$roomNumberModel = new  \Common\Model\Erp\RoomNumber();
		if (!Request::isPost())
		{
			$room_number = Request::queryString("get.room_number",0,"int");
			$houseNumber = Request::queryString("get.house_number",0,"int");
			$floor_num = Request::queryString("get.floor_num",0,"int");
			$flat_name = Request::queryString("get.flat_name",'','string');
			$is_edit = Request::queryString("get.isedite",0,'int');
			$flat_id = Request::queryString("get.flat_id",1878,"int");
			$floor_num_data = $flatHelper->getFloorNumber($floor_num);
			$house_number_data = $flatHelper->houseNumberFactory($houseNumber);
			$room_data = \Core\Session::read($flatHelper->room_number_session_id);
			$data =array();
			if ($is_edit){
				foreach ($room_data as $key=>$val)
				{
					$data[$val['house_num']] = $val['rooms_count'];
				}
			}
			if ($flat_id>0){
				$room_number_data = $roomNumberModel->getData(array("flat_id"=>$flat_id));
				foreach($room_number_data as $rkey=>$rval){
					$r_n_daba[$rkey]['number'] = $rval['room_number'];
					$r_n_daba[$rkey]['floor'] = $rval['floor'];
				}
			}
			$this->assign("is_edit", $is_edit);
			$this->assign("room_data", $data);
			$this->assign("room_number", $room_number);
			$this->assign('house_number_data', $house_number_data['data']);
			$this->assign('house_number_data_f', $house_number_data['data_f']);
			$this->assign("flat_name", $flat_name);
			$data = $this->fetch("Centralized/Flatmodel/flat_rooms_number");

			return $this->returnAjax(array(	"status"=>1,
											"tag_name"=>"自定义套内间数",
											"model_name"=>"flat_roomsnumber",
											"model_js"=>"center_define_roomsJs",
											"model_href"=>Url::parse("Centralized-Flat/roomsnumber"),
											"data"=>$data));
		}else
		{
			$room_datas = Request::queryString("post.houses");
			$room_number_data = $flatHelper->saveRoomNumber($room_datas);
			return $this->returnAjax(array("status"=>1,"data"=>"保存成功","p_url"=>Url::parse("Centralized-Flat/add")));
		}
	}
	/**
	 * 保存楼层编号到session
	 * 修改时间2015年4月15日 20:37:45
	 *
	 * @author yzx
	 */
	protected function savefloorAction()
	{
		if (Request::isPost())
		{
			$flatHelper = new \Common\Helper\Erp\Flat();
			$floor_number = Request::queryString("post.names_Floor");
			$flatHelper->saveFloorNumber($floor_number);
			\Core\Session::save($flatHelper->is_diy_floor, 1);
			return $this->returnAjax(array("status"=>1,"data"=>"保存成功"));
		}
		return $this->returnAjax(array("status"=>0,"data"=>"保存失败"));
	}
	/**
	 * 房间地图
	 * 修改时间2015年6月1日 09:27:35
	 *
	 * @author yzx
	 */
	protected function mapAction(){
		$data = $this->fetch("Centralized/flat_map");
		echo $data;
	}
	/**
	 * 检查公寓唯一性
	 * 修改时间2015年6月1日 20:08:35
	 *
	 * @author yzx
	 */
	protected function checkuniqueAction(){
		$user = $this->user;
		$flatModel = new \Common\Model\Erp\Flat();
		$flat_name = I("get.flat_name",'','string');
		$flat_data = $flatModel->getData(array("company_id"=>$user['company_id'],"is_delete"=>0,"flat_name"=>$flat_name));

		if (!empty($flat_data))
		{
			return $this->returnAjax(array("status"=>0,"data"=>"已经存在公寓名称"));
		}
		return $this->returnAjax(array("status"=>1,"data"=>"公寓名称可用"));
	}
	/**
	 * 获取自定义套内间数
	 * 修改时间2015年6月2日 18:01:47
	 *
	 * @author yzx
	 */
	protected function getroomdiyroomnumberAction(){
		$flatHelper = new \Common\Helper\Erp\Flat();
		$room_data = \Core\Session::read($flatHelper->room_number_session_id);
		if (!empty($room_data)){
			return $this->returnAjax(array("status"=>1,"data"=>$room_data));
		}
		return $this->returnAjax(array("status"=>0,"data"=>false));
	}
}