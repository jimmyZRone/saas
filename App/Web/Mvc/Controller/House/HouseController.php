<?php
namespace App\Web\Mvc\Controller\House;
use App\Web\Lib\Request;
use App\Web\Helper\Url;
use Common\Model\Erp\Attachments;
use Common\Helper\Permissions;
use Common\Helper\Permissions\Hook\SysHousingManagement;
class HouseController extends \App\Web\Lib\Controller{
	protected $_auth_module_name = 'sys_housing_management';
	private $_page_size = 10;
	private $house_id = 0;
	/**
	 * 房源地图首页
	 * 时间时间2015年3月26日 15:55:16
	 * @author yzx
	 */
	protected function indexAction(){
		if (!$this->verifyModulePermissions(Permissions::SELECT_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("House-House/index")));
		}
		$areaModel = new \Common\Model\Erp\Area();
		$area_data = $areaModel->getDataByCity($this->user['city_id']);
		$this->assign('area_data', $area_data);
		$data=$this->fetch("House/House/index");
		return $this->returnAjax(array("status"=>1,
									   "tag_name"=>"分散式公寓",
									   "model_js"=>"spread_house",
									   "model_name"=>"map",
									   "model_href"=>Url::parse("house-house/index"),
									   "data"=>$data,
										));
	}
	/**
	 * 分散式房源列表
	 * 修改时间2015年4月7日 14:52:22
	 *
	 * @author yzx
	 */
	public function listAction()
	{
		if (!$this->verifyModulePermissions(Permissions::SELECT_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403));
		}
		if (Request::isGet())
		{
			$serche = array();
			$area_id = Request::queryString("get.area_id",0,"int");
			$page = Request::queryString("get.page",0,"int");

			$rent_type = Request::queryString("get.rent_type",null,"string");
			$house_status = Request::queryString("get.house_status",null,"int");
			$room_status = Request::queryString("get.room_status",null,"int");
			$community_name = Request::queryString("get.community_name",null,"string");
			$room_number = Request::queryString("get.room_number",null,"string");

			$serche['house_status'] = $house_status;
			$serche['room_status'] = $room_status;
			$serche['community_name'] = $community_name;
			$serche['room_number'] = $room_number;

			$houseHelper = new \Common\Helper\Erp\House();
			$list = $houseHelper->listData($area_id, $page, $this->_page_size, $rent_type,$serche);
			$count_data = $houseHelper->countData($area_id, $rent_type,$this->user);
			$result = array("list"=>$list,"count_data"=>$count_data);
			return $this->returnAjax(array("status"=>1,"data"=>$result));
		}
		return $this->returnAjax(array("status"=>0,"data"=>false));
	}

	/**
	 * 添加分散房源
	 * 修改时间2015年3月18日 09:20:44
	 *
	 * @author yzx
	 */
	protected function addAction()
	{
		if (!$this->verifyModulePermissions(Permissions::INSERT_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("House-House/add")));
		}
		$user = $this->user;
		if (!Request::isPost())
		{
			$keeperHelper = new \Common\Helper\Erp\Keeper();
			$sysConfig = new \Common\Model\Erp\SystemConfig();
			$areaModel = new \Common\Model\Erp\Area();
			$feeTypeHelper = new \Common\Helper\Erp\FeeType();
			$contractModel = new \Common\Model\Erp\LandlordContract();
			$communityModel = new \Common\Model\Erp\Community();
			$keeper_data = $keeperHelper->getKeeper($user);
			$pay_config = $sysConfig->getFind("House", "PublicConfig");
			$public_config = $sysConfig->getFind("House", "public_facilities");
			$area_data = $areaModel->getDataByCity($user['city_id']);
			$money_pay_config = $sysConfig->getFind("System", "PayConfig");
			$fee_type = $feeTypeHelper->getCompanyFeeType($this->user);
			$feeTypeHelper->getUnsetFee($fee_type);
			$contract_id = I("get.cid",0,"int");
			$contract_data = $contractModel->getOne(array("contract_id"=>$contract_id));
			//拆分房源名称START
			if (!empty($contract_data)){
				$room_number = explode("-", $contract_data['hosue_name']);
				unset($room_number[0]);
				if (strpos($room_number[1], '栋')){
					$cost =explode("栋", $room_number[1]);
					$house_data["cost"] = $cost[0];
				}
				if (strpos($room_number[1], '单元')){
					$unit = explode("单元", $cost[1]);
					$house_data['unit'] = $unit[0];
				}
				if (strpos($room_number[1], '楼')){
					$floor = explode("楼", $unit[1]);
					$house_data["floor"] = $floor[0];
				}
				if (strpos($room_number[1], '号')){
					$number = explode("号", $floor[1]);
					$house_data['number'] = $number[0];
				}
				$community_data = $communityModel->getOne(array("community_id"=>$contract_data['community_id']));
			}
			//拆分房源名称END
			
			$this->assign("public_config", $public_config);
			$this->assign("pay_config", $pay_config);
			$this->assign("keeper_data", $keeper_data);
			$this->assign("area_data", $area_data);
			$this->assign("edit_id", 0);
			$this->assign("fee_type", $fee_type);
			$this->assign("money_pay_config", $money_pay_config);
			$this->assign("community_data", $community_data);
			$this->assign("house_data", $house_data);
			$data = $this->fetch();
			return $this->returnAjax(array( "status"=>1,
					"tag_name"=>"分散式房源添加",
					"model_name"=>"house_add",
					"model_js"=>"distributed_add_round_houseJs",
					"model_href"=>Url::parse("House-House/add"),
					"data"=>$data));
		}else
		{
			$houseModel = new \Common\Helper\Erp\House();
			$rental_way = Request::queryString("post.rental_way",2,"int");
			$community_id = Request::queryString("post.community_id",0,"int");
			$address = Request::queryString("post.address",'',"string");
			$cost = Request::queryString("post.cost",0,"string");
			$unit = Request::queryString("post.unit",'',"string");
			$floor = Request::queryString("post.floor",0,"string");
			$number = Request::queryString("post.number",0,"string");
			$custom_number = Request::queryString("post.custom_number",'',"string");
			$community_name = Request::queryString("post.community_name",'','string');
			$occupancy_number = Request::queryString("post.occupancy_number",0,"string");
			$count = Request::queryString("post.count",0,"int");
			$hall = Request::queryString("post.hall",0,"int");
			$toilet = Request::queryString("post.toilet",0,"int");
			$area = Request::queryString("post.area",0,"float");
			$money = Request::queryString("post.money",0,"float");
			$detain = Request::queryString("post.detain",0,"int");
			$pay = Request::queryString("post.pay",0,"int");
			$public_facilities = Request::queryString("post.public_facilities");
			$gender_restrictions = Request::queryString("post.gender_restrictions");
			$public_pic = Request::queryString("post.img");
			$fee_data = Request::queryString("post.feeItem");
			if ($area>999.99){
				return $this->returnAjax(array("status"=>0,"data"=>false,"message"=>"面积只能最大输入999.99"));
			}
			if ($community_id<=0){
				return $this->returnAjax(array("status"=>0,"data"=>false,"message"=>"请选择小区"));
			}
			if ($count>30){
				return $this->returnAjax(array("status"=>0,"data"=>false,"message"=>"不能超过30室"));
			}
			if ($hall>30){
				return $this->returnAjax(array("status"=>0,"data"=>false,"message"=>"不能超过30厅"));
			}
			if ($toilet>30){
				return $this->returnAjax(array("status"=>0,"data"=>false,"message"=>"不能超过30卫"));
			}
			$house_data['rental_way'] = $rental_way;
			$house_data['community_id'] = $community_id;
			$house_data['floor'] = $floor;
			$house_data['number'] = $number;
			$house_data['area'] = $area;
			$house_data['cost'] = $cost;
			$house_data['unit'] = $unit;
			$house_data['hall'] = $hall;
			$house_data['toilet'] = $toilet;
			$house_data['occupancy_number'] = $occupancy_number;
			$house_data['public_facilities'] = $public_facilities;
			$house_data['public_pic'] = $public_pic;
			$house_data['create_uid'] = $user['user_id'];
			$house_data['address'] = $address;
			$house_data['money'] = $money;
			$house_data['detain'] = $detain;
			$house_data['pay'] = $pay;
			$house_data['count'] = $count;
			$house_data['custom_number']=$custom_number;
			$house_data['gender_restrictions']=$gender_restrictions;
			$house_data['company_id'] = $user['company_id'];
			$house_data['community_name'] = $community_name;
			
			if ($unit==''){
				unset($house_data['unit']);
			}
			if ($floor==''){
				unset($house_data['floor']);
			}
			if (isset($house_data['floor']) && !is_numeric($floor)){
				if (!preg_match('/^[A-Za-z]+$/', $floor)){
					return $this->returnAjax(array("status"=>0,"data"=>false,"message"=>"楼层只能为字母和数字"));
				}
			}
			//解析费用项
			$fee_input_data = htmlspecialchars_decode($fee_data);
			$js_fee_data = json_decode($fee_input_data,true);
			foreach ($js_fee_data as $key=>$val){
				$js_fee_data[$key]['now_meter'] = $val['du'];
				$js_fee_data[$key]['add_time'] = strtotime($val['cbdate']);
			}
			
			$house_res = $houseModel->addHouse($house_data,$user,$js_fee_data);
			$house_id = $house_res['status'];
			if ($house_id)
			{
				if ($this->user['is_manager'] == 0){
					//添加权限START
					$permissions = Permissions::Factory('sys_housing_management');
					$permissions->SetVerify(
							$permissions::LINE_BLOCK_ACCESS,
							$permissions::SELECT_AUTH_ACTION,
							$permissions::USER_AUTHENTICATOR,
							$this->user['user_id'],
							$house_id,
							1,
							\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE
					);
				}
				if ($rental_way == \Common\Model\Erp\House::RENTAL_WAY_H)
				{
					$result['status'] = 1;
					$result['data'] = $house_id;
					$result['message'] = "添加成功";

					if ($count>0){
						$add_house = 0;
						$result['p_url'] = Url::parse("House-Room/add")."&house_id=".$house_id."&count=".$count;
						$result['p_type'] = 1;
					}
					if ($count<=0){
						$result['p_url'] = Url::parse("House-House/index");
						if ($house_res['is_house_name']==false){
							$result['landlord_url'] = Url::parse("landlord-index/add")."&house_type=1"."&house_id=".$house_id;
						}
					}
				}else
				{
					$result['status'] = 1;
					$result['data'] = $house_id;
					$result['message'] = "添加成功";
					$result['p_url'] = Url::parse("House-House/index");
					if ($house_res['is_house_name']==false){
						$result['landlord_url'] = Url::parse("landlord-index/add")."&house_type=1"."&house_id=".$house_id;
					}
				}
				return $this->returnAjax($result);
			}
			return $this->returnAjax(array("status"=>0,"data"=>false,"message"=>$house_res['message']));
		}
	}
	/**
	 * 获取房源详情
	 * 修改时间2015年3月18日 16:40:19
	 *
	 * @author yzx
	 */
	protected function detailAction()
	{
		if (Request::isGet()){
			$houseModel = new \App\Web\Mvc\Model\House();
			$house_id = Request::queryString("post.house_id",0,"int");
			
			//判断用户是否对当前对象有权限操作
			if(!$this->verifyDataLinePermissions(Permissions::SELECT_AUTH_ACTION,$house_id, SysHousingManagement::DECENTRALIZED_HOUSE)){
				return $this->returnAjax(array('__status__'=>403));
			}
			
			$result = $houseModel->getOneHouseData($house_id);
			if ($result)
			{
				return $this->returnAjax(array("status"=>1,"data"=>$result));
			}
			return $this->returnAjax(array("status"=>0,"data"=>false));
		}

	}
	/**
	 * 编辑分散房源
	 * 修改时间2015年3月18日 09:21:00
	 *
	 * @author yzx
	 */
	protected function editAction()
	{
		if (!$this->verifyModulePermissions(Permissions::UPDATE_AUTH_ACTION) && !$this->verifyModulePermissions(Permissions::SELECT_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("House-House/edit")));
		}
		$user=$this->user;
		$houseHelper = new \Common\Helper\Erp\House();
		$houseModel = new  \Common\Model\Erp\House();
		$house_data = array();
		if (!Request::isPost())
		{
			$keeperHelper = new \Common\Helper\Erp\Keeper();
			$sysConfig = new \Common\Model\Erp\SystemConfig();
			$houseEntirelModel = new \Common\Model\Erp\HouseEntirel();
			$communityModel = new \Common\Model\Erp\Community();
			$feeTypeHelper = new \Common\Helper\Erp\FeeType();
			$feeHelper = new \Common\Helper\Erp\Fee();
			$meterReadingHelper = new \Common\Helper\Erp\MeterReading();
			$attachmentModel = new Attachments();
			$roomHelper = new \Common\Helper\Erp\Room();
			$resverModel = new \Common\Model\Erp\Reserve();
			$fee_type = $feeTypeHelper->getCompanyFeeType($this->user);
			$feeTypeHelper->getUnsetFee($fee_type);
			$house_id = Request::queryString("get.house_id",0,"int");
			//判断用户是否对当前对象有权限操作
			if(!$this->verifyDataLinePermissions(Permissions::SELECT_AUTH_ACTION, $house_id, SysHousingManagement::DECENTRALIZED_HOUSE)){
				return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("House-House/edit")));
			}
			
			$house_data = $houseModel->getOne(array("house_id"=>$house_id));
			$house_name = explode("-", $house_data['house_name']);
			$unit = $house_data['unit']?$house_data['unit']."单元":'';
			$floor = $house_data['floor']?$house_data['floor']."楼":'';
			$number = $house_data['number']?$house_data['number']."号":'';
			$house_data['houseName'] = $house_name[0].$house_data['cost']."栋".$unit.$floor.$number;
			$house_data['house_name'] = $house_name[0].$house_data['cost']."栋".$unit.$house_data['floor'].$house_data['number']."号";
			$contract_id = $houseHelper->getHouseContract($house_data);
			$resver_data = $resverModel->getOne(array("house_type"=>$resverModel::HOUSE_TYPE_R,"rental_way"=>$resverModel::RENTAL_WAY_Z,"house_id"=>$house_id));
			//获取预约退租
			$reser_back_model = new \Common\Model\Erp\ReserveBackRental();
			$reserve_back_info = $reser_back_model->getOne(array('type' => $reser_back_model::DISPERSE_TYPE, 'house_type' => $reser_back_model::HOUSE_TYPE_HOUSE, 'source_id' => $house_id, 'is_delete' => 0), array('reserve_back_id' => 'reserve_back_id'));
			
			//获取租住关系
			$community_data = $communityModel->getOne(array("community_id"=>$house_data['community_id']));
			$entirel_data = $houseEntirelModel->getOne(array("house_id"=>$house_id));
			$keeper_data = $keeperHelper->getKeeper($user);
			$imag = $attachmentModel->getImagList("house", $house_id);
			
			//费用项数据
			$fee_data =$feeHelper->getRoomFeeInfo($house_id,$user['company_id'],\Common\Model\Erp\Fee::SOURCE_DISPERSE);
			$fee_show=0;
			if (!empty($fee_data)){
				$pay_config_key = array(3,4,5);
				foreach ($fee_data as $hkey=>$hval)
				{
					$payment_mode[] = $hval['payment_mode'];
					if ($house_data['rental_way'] == $houseModel::RENTAL_WAY_Z){
						if ($hval['payment_mode'] == 5 && $house_data['status'] == $houseModel::STATUS_IS_RENTAL){
							$contract_id=1;
						}
					}
					if (in_array($hval['payment_mode'], $pay_config_key)){
						$fee_show =1;
					}
				}
			}
			if (!empty($fee_data)){
				foreach ($fee_data as $key=>$val){
					$m_data = $meterReadingHelper->getDataById($house_id, \Common\Model\Erp\MeterReading::HOUSE_TYPE_C,$val['fee_type_id']);
					$fee_data[$key]['now_meter'] = empty($m_data)?$val['money']:$m_data['now_meter'];
					$fee_data[$key]['add_time'] = empty($m_data)?$val['create_time']:$m_data['add_time'];
				}
			}
			//检查合租房源下面是否有已租房间
			if ($house_data['rental_way'] == $houseModel::RENTAL_WAY_H){
				$room_data = $roomHelper->getRoomByHouse($house_id);
				if (!empty($room_data)){
					foreach ($room_data as $rkey=>$rval){
						if (in_array(3, $payment_mode) || in_array(4, $payment_mode)){
							if ($rval['status']==\Common\Model\Erp\Room::STATIS_RENTAL){
								$contract_id=1;
							}
						}
					}
				}
			}
			
			$pay_config = $sysConfig->getFind("House", "PublicConfig");
			$public_config = $sysConfig->getFind("House", "public_facilities");
			$money_pay_config = $sysConfig->getFind("System", "PayConfig");
			$house_config = explode("-", $house_data['public_facilities']);
			$config_data = array();
			foreach ($public_config as $fkey=>$fval)
			{
				$config_data[$fkey]['name'] = $fval;
				$config_data[$fkey]['value'] = $fkey;
				if (in_array($fkey, $house_config))
				{
					$config_data[$fkey]['is_read'] = 1;
				}else
				{
					$config_data[$fkey]['is_read'] = 0;
				}
			}
			if ($house_data['rental_way'] == \Common\Model\Erp\House::RENTAL_WAY_Z)
			{
				unset($money_pay_config[3]);
				unset($money_pay_config[4]);
			}
			$this->assign("resver_data", $resver_data);
			$this->assign("fee_show", $fee_show);
			$this->assign("public_config", $config_data);
			$this->assign("pay_config", $pay_config);
			$this->assign("keeper_data", $keeper_data);
			$this->assign("house_data", $house_data);
			$this->assign("edit_id", 1);
			$this->assign("entirel_data", $entirel_data);
			$this->assign("community_data", $community_data);
			$this->assign("house_id", $house_id);
			$this->assign("fee_type", $fee_type);
			$this->assign("money_pay_config", $money_pay_config);
			$this->assign("fee_data", $fee_data);
			$this->assign("imag", $imag);
			$this->assign("contract_id", $contract_id);
			$this->assign("reserve_back_info", $reserve_back_info);
			$data = $this->fetch("House/House/add");
			return $this->returnAjax(array( "status"=>1,
					"tag_name"=>"分散式房源修改",
					"model_name"=>"house_edit",
					"model_js"=>"distributed_add_round_houseJs",
					"model_href"=>Url::parse("House-House/edit"),
					"data"=>$data));
		}else
		{
			if (!$this->verifyModulePermissions(Permissions::UPDATE_AUTH_ACTION)){
				return $this->returnAjax(array('__status__'=>403));
			}
			$house_id = Request::queryString("post.house_id",0,"int");
			
			//判断用户是否对当前对象有权限操作
			if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $house_id, SysHousingManagement::DECENTRALIZED_HOUSE)){
				return $this->returnAjax(array('__status__'=>403));
			}			
			$rental_way = Request::queryString("post.rental_way",'',"int");
			$address = Request::queryString("post.address",'',"string");
			$cost = Request::queryString("post.cost",0,"string");
			$unit = Request::queryString("post.unit",0,"string");
			$floor = Request::queryString("post.floor",0,"string");
			$number = Request::queryString("post.number",0,"string");
			$custom_number = Request::queryString("post.custom_number",'',"string");

			$occupancy_number = Request::queryString("post.occupancy_number",0,"string");
			$count = Request::queryString("post.count",0,"int");
			$hall = Request::queryString("post.hall",0,"int");
			$toilet = Request::queryString("post.toilet",0,"int");
			$area = Request::queryString("post.area",0,"float");
			$money = Request::queryString("post.money");
			$detain = Request::queryString("post.detain",0,"int");
			$pay = Request::queryString("post.pay",0,"int");
			$public_facilities = Request::queryString("post.public_facilities");
			$gender_restrictions = Request::queryString("post.gender_restrictions");
			$public_pic = Request::queryString("post.img");
			$fee_data = Request::queryString("post.feeItem");
			$community_name = Request::queryString("post.community_name",'',"string");
			
			$house_data['rental_way'] = $rental_way;
			$house_data['floor'] = $floor;
			$house_data['number'] = $number;
			$house_data['area'] = $area;
			$house_data['cost'] = $cost;
			$house_data['unit'] = $unit;
			$house_data['hall'] = $hall;
			$house_data['toilet'] = $toilet;
			$house_data['occupancy_number'] = $occupancy_number;
			$house_data['public_facilities'] = $public_facilities;
			$house_data['public_pic'] = $public_pic;
			$house_data['create_uid'] = $user['user_id'];
			$house_data['address'] = $address;
			$house_data['money'] = is_numeric($money)?$money:0;
			$house_data['detain'] = $detain;
			$house_data['pay'] = $pay;
			$house_data['count'] = $count;
			$house_data['custom_number']=$custom_number;
			$house_data['gender_restrictions']=$gender_restrictions;
			$house_data['community_name'] = $community_name;
			if ($unit==''){
				unset($house_data['unit']);
			}
			if ($floor==''){
				unset($house_data['floor']);
			}
			$house_data_old = $houseModel->getOne(array("house_id"=>$house_id,"is_delete"=>0,'company_id'=>$this->user['company_id']));
			if(!$house_data_old){
				return $this->returnAjax(array('__status__'=>403));
			}
			if ($house_data_old['rental_way'] == $houseModel::RENTAL_WAY_H){
				if ($house_data_old['count'] > $count){
					return $this->returnAjax(array("status"=>0,"data"=>false,"message"=>"您可以在房态管理页面删除房间"));
				}
			}
			if (isset($house_data['floor']) && !is_numeric($floor)){
				if (!preg_match('/^[A-Za-z]+$/', $floor)){
					return $this->returnAjax(array("status"=>0,"data"=>false,"message"=>"楼层只能为字母和数字"));
				}
			}
			if (strlen($custom_number)>40){
				return $this->returnAjax(array("status"=>0,"data"=>false,"message"=>"房源编号不能超过40个字符串"));
			}
			
			$fee_input_data = htmlspecialchars_decode($fee_data);
			$js_fee_data = json_decode($fee_input_data,true);
			foreach ($js_fee_data as $key=>$val){
				$js_fee_data[$key]['now_meter'] = $val['du'];
				$js_fee_data[$key]['add_time'] = strtotime($val['cbdate']);
			}
			if ($houseHelper->editHouse($house_data,$house_id,$js_fee_data))
			{
				$p_url = Url::parse("House-House/index");
				$result = array("status"=>1,"data"=>true,"message"=>"修改成功",'p_url' => $p_url);
				if ($rental_way ==  \Common\Model\Erp\House::RENTAL_WAY_H){
					$p_url = Url::parse("House-Room/edit")."&house_id=".$house_id;
					$result = array("status"=>1,"data"=>true,"message"=>"修改成功",'p_url' => $p_url,'p_type'=>1);
				}
				return $this->returnAjax($result);
			}
			return $this->returnAjax(array("status"=>0,"data"=>false,"message"=>"修改失败"));
		}
	}
	/**
	 * 获取房源房间
	 * 修改时间2015年3月18日 16:49:51
	 *
	 * @author yzx
	 */
	protected function roomlistAction()
	{
		if (Request::isGet())
		{
			$houseHelper = new \Common\Helper\Erp\House();

			$house_id = Request::queryString("get.house_id",0,"int");
			
			//判断用户是否对当前对象有权限操作
			if(!$this->verifyDataLinePermissions(Permissions::SELECT_AUTH_ACTION, $house_id, SysHousingManagement::DECENTRALIZED_HOUSE)){
				return $this->returnAjax(array('__status__'=>403));
			}
			
			$result = $houseHelper->getHouseRoom($house_id);
			if ($result)
			{
				return $this->returnAjax(array("status"=>1,'data'=>$result));
			}
			return $this->returnAjax(array("status"=>0,"data"=>false));
		}
	}
	/**
	 * 搜索小区名字
	 * 修改时间2015年4月28日 09:47:20
	 *
	 * @author yzx
	 */
	public function communityAction()
	{
		$result = array();
		$community_name = Request::queryString("get.search",'',"string");
		try{
			$sphinx = \Common\Helper\Sphinx::GetConnect();
			$sphinx->SetFilter('city_id',array($this->user['city_id']));
			$result = $sphinx->Query("@community_name {$community_name}", "community_index");
			if($result === false){
				throw new \Exception('');
			}
			if(isset($result['matches']) && is_array($result['matches']) && !!$result['matches']){
				$data = array();
				$result = array_column($result['matches'], 'attrs');
			}else{
				$result = array();
			}
		}catch (\Exception $e){
			$communityHelper = new \Common\Helper\Erp\Community();
			$result = $communityHelper->getAddressByName($this->user,$community_name);
		}
		if (!empty($result))
		{
			return $this->returnAjax(array("status"=>1,"data"=>$result));
		}
		return $this->returnAjax(array("status"=>0,"message"=>"没有找到数据"));
	}
	/**
	 * 通过区域获取商圈
	 * 修改时间2015年5月6日 15:37:56
	 *
	 * @author yzx
	 */
	public function businessAction()
	{
		$user = $this->user;
		$area_id = Request::queryString("post.area_id",0,"int");
		$businessModel = new \Common\Model\Erp\Business();
		$data = $businessModel->getDataByArea($area_id, $user['city_id']);
		if (!empty($data))
		{
			return $this->returnAjax(array("status"=>1,"data"=>$data));
		}
		return $this->returnAjax(array("status"=>0,"message"=>"没有找到数据"));
	}
	/**
	 * 获取地图数据
	 * 修改时间2015年5月7日 16:58:27
	 *
	 * @author yzx
	 */
	public function mapAction()
	{
		$user = $this->user;
		$houseHelper = new \Common\Helper\Erp\House();
		if (!Request::isPost())
		{
			$count_area_data = $houseHelper->countAreaHouse($user);
		}else
		{
			$area_id = I("post.area_id",0,"int");
			$count_area_data = $houseHelper->countAreaHouse($user,$area_id);
		}
		$this->returnAjax(array("status"=>1,
								"data"=>$count_area_data,
								));
	}

	public function mapiframeAction()
	{
		$user =  $this->user;
	    $cityModel = new \Common\Model\Erp\City();
	    $cityId = $this->getUser()['city_id'];
	    $cityInfo = $cityModel->getOne(array('city_id'=>$cityId));
	    $provinceModel = new \Common\Model\Erp\Province();
	    $pinfo = $provinceModel->getOne(array('province_id'=>$cityInfo['province_id']));
	    $houseHelper = new \Common\Helper\Erp\House();
	    $count_area_data = $houseHelper->countAreaHouse($user);
	    $mapJson = json_encode($count_area_data);
	    $this->assign("mapJson", $mapJson);
	    $this->assign('cinfo', $cityInfo);
	    $this->assign('pinfo', $pinfo);//P($cityId);P($pinfo);
		$data = $this->fetch('House/House/mapIframe');
		echo $data;
	}
	/**
	 * 统计接口
	 * 修改时间2015年5月8日 10:15:44
	 *
	 * @author yzx
	 */
	public function areacountAction()
	{
		$user = $this->user;
		$houseHelper = new \Common\Helper\Erp\House();
		$cityModel = new \Common\Model\Erp\City();
		$city_data = $cityModel->getOne(array("city_id"=>$user['city_id']));
		$name = $city_data['name'];
		if (!Request::isPost())
		{
			$count_data = $houseHelper->countData(\Common\Model\Erp\House::LIST_TYPE_HOUSE,$user);
			$count_data['name'] = $name;
		}else
		{
			$areaModel = new \Common\Model\Erp\Area();
			$communityModel = new \Common\Model\Erp\Community();
			$area_id = I("post.area_id",0,"int");
			$community_id = I("post.community_id",0,"int");
			$area_data = $areaModel->getOne(array("area_id"=>$area_id));
			$community_data = $communityModel->getOne(array("community_id"=>$community_id));

			if (!empty($area_data)){
				$name = $city_data['name'].'-'.$area_data['name'];
			}
			if (!empty($community_data)){
				$name = $city_data['name'].'-'.$area_data['name'].'-'.$community_data['community_name'];
			}
			$count_data = $houseHelper->countData(\Common\Model\Erp\House::LIST_TYPE_HOUSE,$this->user,$area_id,$community_id);
			$count_data['name'] = $name;
		}
		$this->returnAjax(array("status"=>1,"data"=>$count_data));
	}
	/**
	 * 获取列表数据
	 * 修改时间2015年5月8日 10:17:41
	 *
	 * @author yzx
	 */
	public function listdataAction()
	{
		$page_size = 10;
		$user = $this->user;
		$houseHelper = new \Common\Helper\Erp\House();
		$area_id = I("request.area_id",0,"int");
		$community_id = I("request.community_id",0,"int");
		$house_type = I("request.house_type",0,'int');
		$room_type = I("request.room_type","","string");
		$community_name = I("request.community_name","","string");
		$custom_number = I("request.custom_number","","string");
		$page = I("request.page",1,"int");
		$state = I('request.state',"","string");
		$search_str['house_type'] = $house_type;
		$search_str['room_type'] = $room_type;
		$search_str['community_name'] = $community_name;
		$search_str['custom_number'] = $custom_number;
		
		if ($house_type == 1){
			$rental_way = \Common\Model\Erp\House::RENTAL_WAY_H;
		}
		if ($house_type == 2){
 			$rental_way = \Common\Model\Erp\House::RENTAL_WAY_Z;
		}
		if ($state!=''){
			$state = explode(",",$state);
		}
		if ($state!=''){
			$page_size=20;
		}
		$list_data = $houseHelper->getHouseListData($page, $page_size,$rental_way,$user,$area_id,$community_id,$search_str,$state);
		$hz_data = array();
		$zz_data = array();
		foreach ($list_data['data'] as $value){
			if($value['rental_way']==\Common\Model\Erp\House::RENTAL_WAY_H){
				$hz_data[] = $value;
			}else{
				$zz_data[] = $value;
			}
		}
		if ($state!=''){
			foreach ($hz_data as $key=>$val){
				if (empty($val['room_data'])){
					unset($hz_data[$key]);
				}
			}
			sort($hz_data);
		}
		$this->returnAjax(array("status"=>1,"hz_data"=>$hz_data,"zz_data"=>$zz_data));
	}
	/**
	 * 获取房间类型
	 * 修改时间2015年5月8日 10:39:23
	 *
	 * @author yzx
	 */
	public function roomtypeAction()
	{
		if (Request::isPost())
		{
			$rental_way = I("post.rental_way",1,"int");
			if ($rental_way==2)
			{
				$sysConfig = new \Common\Model\Erp\SystemConfig();
				$room_type = $sysConfig->getFind("FocusRoom", "focus_room_type");
				foreach ($room_type as $key=>$val){
					if ($key == '1t2'){
						unset($room_type[$key]);
						$room_type['2t1'] = "两室一厅 ";
					}
					if ($key == '1t3'){
						unset($room_type[$key]);
						$room_type['3t1'] = '三室一厅';
					}
				}
				unset($room_type['0tmain']);
				unset($room_type['0tsecond']);
				unset($room_type['0tgues']);
				unset($room_type['0tor']);
				array_unshift($room_type, "全部");
				return $this->returnAjax(array("status"=>1,"data"=>$room_type));
			}elseif($rental_way==1)
			{
				$data = array("all"=>"全部","main"=>"主卧","second"=>"次卧","guest"=>"客卧");
				return $this->returnAjax(array("status"=>1,"data"=>$data));
			}
		}
	}
	/**
	 * 列表删除
	 * 修改时间2015年5月13日 11:27:21
	 *
	 * @author yzx
	 */
	public function deleteAction()
	{
		if (!$this->verifyModulePermissions(Permissions::DELETE_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403));
		}
		if (Request::isPost())
		{
			$houseModel = new \Common\Model\Erp\House();
			$roomModel = new \Common\Model\Erp\Room();
			$house_id = I("post.house_id");
			$room_id = I("post.room_id");
			if ($house_id!='')
			{
				
				//判断用户是否对当前对象有权限操作
				if(!$this->verifyDataLinePermissions(Permissions::DELETE_AUTH_ACTION, $house_id, SysHousingManagement::DECENTRALIZED_HOUSE)){
					return $this->returnAjax(array('__status__'=>403));
				}
				
				$house_id = explode(",", $house_id);
				$result = $houseModel->deleteData($house_id);
				if($result){
					$hdModel = new \Common\Model\Erp\HousingDistribution();
					$hdModel->delete(array('source'=>\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE,'source_id'=>$house_id));
				}
			}
			if ($room_id!='')
			{
				$room_data = $roomModel->getOne(array("room_id"=>$room_id));
				
				//判断用户是否对当前对象有权限操作
				if(!$this->verifyDataLinePermissions(Permissions::DELETE_AUTH_ACTION, $room_data['house_id'], SysHousingManagement::DECENTRALIZED_HOUSE)){
					return $this->returnAjax(array('__status__'=>403));
				}
				
				$room_id = explode(",", $room_id);
				$result = $roomModel->deleteData($room_id,$room_data['house_id']);
			}
			if ($result)
			{
				return $this->returnAjax(array("status"=>1,"data"=>true,"message"=>"删除成功"));
			}
			return $this->returnAjax(array("status"=>0,"data"=>false,"message"=>"删除失败"));
		}
	}
	//获取预定
	public function reserveAction(){
		$getCityByIp = new \Common\Helper\Erp\GetCityByIp();
		$roomModel = new \Common\Model\Erp\Room();
		$reserveHelper = new \Common\Helper\Erp\Reserve();
		$ip = $getCityByIp->GetIpLookup();
		$room_id = Request::queryString("get.room_id",0,"int");
		$house_id = Request::queryString("get.house_id",0,"int");
		
		if ($room_id>0){
			$room_data = $roomModel->getOne(array("room_id"=>$room_id));
			$house_id = $room_data['house_id'];
			$data=$reserveHelper->getDataByCondition(\Common\Model\Erp\Reserve::HOUSE_TYPE_R,$room_id,true);
			if (!empty($data)){
				foreach ($data as $key=>$val){
					$data[$key]['rental_url']=Url::parse("Tenant-Index/reservetolet")."&reserve_id=".$val['reserve_id'];
				}
			}
		}else {
			$data=$reserveHelper->getDataByCondition(\Common\Model\Erp\Reserve::HOUSE_TYPE_R,$house_id);
			foreach ($data as $key=>$val){
				$data[$key]['rental_url']=Url::parse("Tenant-Index/reservetolet")."&reserve_id=".$val['reserve_id'];
			}
		}
		
		//验证有没有该房间权限
		if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $house_id, SysHousingManagement::DECENTRALIZED_HOUSE)){
			return $this->returnAjax(array('__status__'=>403));
		}
		
		if (empty($data))
		{
			return $this->returnAjax(array("status"=>1,'count'=>0,"message"=>"获取预订人失败"));
		}
		return $this->returnAjax(array("status"=>1,"count"=>count($data),"data"=>$data));
	}
	/**
	 * 获取停用信息
	 * 修改时间2015年5月14日 10:38:59
	 *
	 * @author yzx
	 */
	public function getstopAction()
	{
		$house_id = I("get.house_id",0,"int");
		$stopHouseModle = new \Common\Model\Erp\StopHouse();
		$data = $stopHouseModle->getDataBySourceId($house_id, \Common\Model\Erp\StopHouse::DISPERSE_TYPE);
		return $this->returnAjax(array("status"=>1,"data"=>$data));
	}
	/**
	 * 编辑停用原因
	 * 修改时间2015年5月14日 15:48:03
	 *
	 * @author yzx
	 */
	public function editstopAction()
	{
		$stop_id = I("stop_id",0,"int");
		$endtime_start = I("endtime_start","","string");
		$endtime_end = I("endtime_end","","string");
		$notice = I("notice","","string");
		$data['start_time'] = strtotime($endtime_start);
		$data['end_time'] = strtotime($endtime_end);
		$data['remark'] = $notice;
		
		$stopHouseModle = new \Common\Model\Erp\StopHouse();
		$todoModel = new \Common\Model\Erp\Todo();
		$stop_data = $stopHouseModle->getOne(array("stop_id"=>$stop_id));
		$result = $stopHouseModle->edit(array("stop_id"=>$stop_id), $data);
		if ($stop_data['type']==$stopHouseModle::DISPERSE_TYPE && $stop_data['house_type']==$stopHouseModle::HOUSE_TYPE_H){
			$module = $todoModel::MODEL_ROOM_STOP;
		}
		if ($stop_data['type'] == $stopHouseModle::DISPERSE_TYPE && $stop_data['house_type'] == $stopHouseModle::HOUSE_TYPE_Z){
			$module = $todoModel::MODEL_HOUSE_STOP;
		}
		$todo_data = $todoModel->getOne(array("module"=>$module,"entity_id"=>$stop_data['source_id']));
		$content = str_replace(date("Y-m-d",$todo_data['deal_time']), $endtime_end, $todo_data['content']);
		$todoModel->edit(array("module"=>$module,"entity_id"=>$stop_data['source_id']), array("deal_time"=>strtotime($endtime_end),"content"=>$content));
		if ($result)
		{
			return $this->returnAjax(array("status"=>1,"data"=>"保存成功"));
		}
		return $this->returnAjax(array("status"=>0,"data"=>"保存失败"));
	}
	/**
	 * 获取预约退租信息
	 * 修改时间2015年5月14日 10:38:59
	 *
	 * @author yzx
	 */
	public function getyytzuAction() {
		$house_id = I("get.house_id", 0, "int");
		$house_model = new \Common\Model\Erp\House();
		$room_num = $house_model->getOne(array('house_id' => $house_id), array('custom_number' => 'custom_number'));
		$reserve_back_model = new \Common\Model\Erp\ReserveBackRental();
		$reserve_data = $reserve_back_model->getOne(array('type' => $reserve_back_model::DISPERSE_TYPE,"house_type"=>$reserve_back_model::HOUSE_TYPE_HOUSE, 'source_id' => $house_id, 'is_delete' => 0), array('back_rental_time' => 'back_rental_time', 'remark' => 'remark'));
		$reserve_info = array_merge($reserve_data, $room_num);
		$reserve_info['back_rental_time'] = date('Y-m-d', $reserve_info['back_rental_time']);
		return $this->returnAjax(array("status"=>1,"data"=>$reserve_info));
	}
}