<?php
namespace App\Web\Mvc\Controller\Centralized;
use App\Web\Lib\Request;
use App\Web\Helper\Url;
use Common\Model\Erp\Attachments;
use Common\Helper\Permissions;
use Common\Helper\Permissions\Hook\SysHousingManagement;
class RoomfocusController extends \App\Web\Lib\Controller
{
	protected $_auth_module_name = 'sys_housing_management';
	/**
	 * 列表
	 * @author lishengyou
	 * 最后修改时间 2015年3月28日 下午3:05:38
	 *
	 */
	public function indexAction(){
		if (!$this->verifyModulePermissions(Permissions::SELECT_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("Centralized-Roomfocus/index")));
		}
		$user = $this->user;
		$page_size = 30;
		$flat_id = \App\Web\Lib\Request::queryString('get.flat_id',0,'intval');
		
		//判断用户是否对当前对象有权限操作
		if(!$this->verifyDataLinePermissions(Permissions::SELECT_AUTH_ACTION, $flat_id, SysHousingManagement::CENTRALIZED_FLAT)){
			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("Centralized-roomfocus/index/flat_id/{$flat_id}")));
		}
		
		$room_type = Request::queryString("get.room_type",'0',"string");
		$search_str = Request::queryString("get.search_str",'',"string");
		$page = Request::queryString("get.page",0,"int");
		$RoomFocusModel = new \Common\Model\Erp\RoomFocus();
		$flatModel = new \Common\Model\Erp\Flat();
		$RoomFocusHelper = new \Common\Helper\Erp\RoomFocus();
		$systemConfigModel = new \Common\Model\Erp\SystemConfig();
		$flat_data = $flatModel->getOne(array("flat_id"=>$flat_id));
		$search['search_str'] = $search_str;
		$search['room_type'] = $room_type;
		$room_data = $RoomFocusModel->getListData($flat_id, $this->user, $page, $page_size,$search);
		if ($page>=2)
		{
			return $this->returnAjax(array("status"=>1,"data"=>rsort($room_data['data'])));
		}
		$room_cofig = $systemConfigModel->getFind("FocusRoom", "focus_room_type");
		array_unshift($room_cofig, '全部选项');
		$all_room_count = $RoomFocusHelper->getRoomCount($flat_id);
		$rent_room_count = $RoomFocusHelper->countRoom($flat_id, 'isrent');
		$reserve_room_count = $RoomFocusHelper->countRoom($flat_id, 'reserve');
		$sum_money_room = $RoomFocusHelper->countRoom($flat_id, 'summoney');
		$is_yytz = $RoomFocusHelper->countRoom($flat_id, "is_yytz");
		$stop = $RoomFocusHelper->countRoom($flat_id, "stop");
		$sum_empt_rent = $RoomFocusHelper->calculateMonthEmpty($user, $flat_id);
		$sum_empt_rent = $sum_empt_rent>100?100:$sum_empt_rent;
		$sum_year_empt_rent = $RoomFocusHelper->calculateMonthEmpty($user,$flat_id,true);
		$sum_year_empt_rent = $sum_year_empt_rent>100?100:$sum_year_empt_rent;
		$all_house = $sum_money_room['page']['count'];
		$sum_money_room = $sum_money_room['data'][0];
		if ($sum_money_room['all_money']<=0)
		{
			$sum_money = 0;
		}else 
		{
			$sum_money=($sum_money_room['all_money'])/($all_house);
		}
		//获取楼层
		$floor = $RoomFocusHelper->havingFloorTotal($flat_id,$search);
		
		$not_rent_count = ($all_room_count['page']['count'])-($rent_room_count['page']['count'])-($stop['page']['count']);
		$reserve_count = abs($reserve_room_count['page']['count']-$is_yytz['page']['count']);
		$search['type_name'] = $room_cofig[$search['room_type']];
		$this->assign("search", $search);
		$this->assign("room_config", $room_cofig);
		$this->assign("all_room", $all_room_count['page']);
		$this->assign('rent_room_count', ($rent_room_count['page']['count'])-($is_yytz['page']['count']));
		$this->assign("not_rent_count", $not_rent_count);
		$this->assign("reserve_room_count", $reserve_room_count['page']);
		$this->assign("sum_money", $sum_money);
		$this->assign("rent_probability", ((($rent_room_count['page']['count'])/$all_room_count['page']['count']))*100);
		$this->assign("is_yytz", $is_yytz['page']);
		$this->assign("stop", $stop['page']);
		$this->assign("list_data", $room_data['data']);
		$this->assign("flat_data", $flat_data);
		$this->assign("floor", $floor);
		$this->assign("sum_empt_rent", $sum_empt_rent);
		$this->assign("sum_empt_year_rent", $sum_year_empt_rent);
		$data = $this->fetch("Centralized/flat_room_list");
		return $this->returnAjax(array( "status"=>1,
										"tag_name"=>"集中式房态管理",
										"model_name"=>"room_focus_index",
										"model_js"=>"centralized_IndJs",
										"model_href"=>Url::parse("Centralized-Roomfocus/index"),
										"data"=>$data,
										"tagsize"=>5
		));
	}

	/**
	 * 修改房间
	 *  最后修改时间 2015-3-24
	 *
	 * @author dengshuang
	 */
	public function editAction(){
		if (!$this->verifyModulePermissions(Permissions::SELECT_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("Centralized-Roomfocus/edit")));
		}
		$configModel = new \Common\Model\Erp\SystemConfig();
		$roomFocusModel = new \Common\Model\Erp\RoomFocus();
		$flatModel = new \Common\Model\Erp\Flat();
		$roomFocusHelper = new \Common\Helper\Erp\RoomFocus();
		$feeHelper = new \Common\Helper\Erp\Fee();
		$feeTypeHelper = new \Common\Helper\Erp\FeeType();
		$rentalModel = new \Common\Model\Erp\Rental();
		$attachmentMoent = new Attachments();
		$resverModel = new \Common\Model\Erp\Reserve();
		if(!\App\Web\Lib\Request::isPost()){
			$user = $this->user;
			$room_focus_id = Request::queryString('get.room_focus_id');
			if(empty($room_focus_id)){
				return $this->returnAjax(array("status"=>0,"data"=>"没有找到房间ID"));
			}
			
			//验证有没有该房间权限
			if(!$this->verifyDataLinePermissions(Permissions::SELECT_AUTH_ACTION, $room_focus_id, SysHousingManagement::CENTRALIZED)){
				return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("Centralized-Roomfocus/edit")));
			}
			
			$roomFocusInfo = $roomFocusModel->getOne(array('room_focus_id'=>$room_focus_id));
			if(empty($roomFocusInfo)){
				return $this->returnAjax(array("status"=>0,"没有找到该ID房间"));
			}
			$floor_num = $roomFocusHelper->roomFloor($roomFocusInfo['flat_id']);
			$flatInfo = $flatModel->getOne(array('flat_id'=>$roomFocusInfo['flat_id']),array('flat_id','flat_name',"rental_way"));
			if(empty($flatInfo)){
				return $this->returnAjax(array("status"=>0,"data"=>"没有找到该房间公寓"));
			}
			$flat_fee_data = $feeHelper->getRoomFeeInfo($roomFocusInfo['flat_id'],$user['company_id'],\Common\Model\Erp\Fee::SOURCE_FLAT);
			$room_flat_data = $feeHelper->getRoomFeeInfo($room_focus_id,$user['company_id'],\Common\Model\Erp\Fee::SOURCE_FOCUS);
			//获取预约退租
			$reser_back_model = new \Common\Model\Erp\ReserveBackRental();
			$stopHouseModel = new \Common\Model\Erp\StopHouse();
			$reserve_back_info = $reser_back_model->getOne(array('type' => $reser_back_model::CENTRALIZATION_TYPE, 'house_type' => 0, 'source_id' => $room_focus_id, 'is_delete' => 0), array('reserve_back_id' => 'reserve_back_id'));
			$stop_house_data = $stopHouseModel->getOne(array("type"=>$stopHouseModel::CENTRALIZATION_TYPE,"house_type"=>0,"source_id"=>$room_focus_id,'is_delete' => 0));
			$all_fee_data = array_merge($flat_fee_data,$room_flat_data);
			$resver_data = $resverModel->getOne(array("house_type"=>$resverModel::HOUSE_TYPE_F,"room_id"=>$room_focus_id));
			//判断是否先下抄表
			if (!empty($all_fee_data)){
				$pay_config_key = array(3,4,5);
	    		$fee_show=0;
	    		foreach ($all_fee_data as $hkey=>$hval)
	    		{
	    			if (in_array($hval['payment_mode'], $pay_config_key)){
	    				$fee_show =1;
	    			}
	    		}
			}
			$fee_type = $feeTypeHelper->getCompanyFeeType($this->user);
			
			//获取租客数据
			//$rental_data = $rentalModel->getFocusData($room_focus_id);
			$contract_id = $roomFocusHelper->getFocusRoomContract(array("room_id"=>$room_focus_id));
			$focus_room_type = $configModel->getFind('FocusRoom', 'focus_room_type');
			$public_facilities = $configModel->getFind("House", "public_facilities");
			$house_public_config = $configModel->getFind('House', 'PublicConfig');
			$imag = $attachmentMoent->getImagList("room_focus", $room_focus_id);
			$room_fee_data = $feeHelper->getDataBySource($room_focus_id, \Common\Model\Erp\Fee::SOURCE_FOCUS);
			$flat_fee_data = $feeHelper->getDataBySource($flatInfo['flat_id'], \Common\Model\Erp\Fee::SOURCE_FLAT);
			$fee_data = array_merge($room_fee_data,$flat_fee_data);
			$u_f_data = array();
			$f_data = array();
			foreach ($fee_data as $key=>$val){
				$f_data[$key] = $val['fee_type_id'];
			}
			$u_f_data = array_unique($f_data);
			$out_fee_data = array();
			foreach ($u_f_data as $uk=>$uv){
				$out_fee_data[]=$fee_data[$uk];
			}
			//费用支付方式
			$pay_config = $configModel->getFind("System", "PayConfig");
			unset($pay_config[3]);
			unset($pay_config[4]);
			$room_config = explode("-", $roomFocusInfo['room_config']);
			$config_data = array();
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
			if ($flatInfo['rental_way'] == $flatModel::RENTAL_WAY_CLOSE){
				unset($focus_room_type['1t1']);
				unset($focus_room_type['1t2']);
				unset($focus_room_type['1t3']);
				unset($focus_room_type['3t2']);
				unset($focus_room_type['4t2']);
				unset($focus_room_type['5t2']);
			}
			$roomFocusInfo['custom_number'] = substr($roomFocusInfo['custom_number'], strlen($roomFocusInfo['floor']),strlen($roomFocusInfo['custom_number']));
			$feeTypeHelper->getUnsetFee($fee_type);
			$this->assign("resver_data", $resver_data);
			$this->assign("fee_show", $fee_show);
			$this->assign('focus_room_type', $focus_room_type);
			$this->assign('flatInfo', $flatInfo);
			$this->assign('roomFocusInfo', $roomFocusInfo);
			$this->assign("public_facilities", $config_data);
			$this->assign("house_public_config", $house_public_config);
			$this->assign('floor_num', $floor_num);
			$this->assign("fee_type_data", $fee_type);
			$this->assign("fee_data", $out_fee_data);
			$this->assign("fee_type", $fee_type);
			$this->assign("pay_type", $pay_config);
			$this->assign("rental_data", array("contract_id"=>$contract_id));
			$this->assign("imag", $imag);
			$this->assign("reserve_back_info", $reserve_back_info);
			$this->assign("stop_house_data", $stop_house_data);
			$data = $this->fetch("Centralized/edit_centralized_house");
			
			return $this->returnAjax(array( "status"=>1,
											"tag_name"=>"修改集中式房间",
											"model_name"=>"room_focus_add",
											"model_js"=>"centralized_add_houseJs",
											"model_href"=>Url::parse("Centralized-Roomfocus/edit"),
											"tag"=>Url::parse("Centralized-Roomfocus/list")."&flat_id=".$flatInfo['flat_id'],
											"data"=>$data));
		}else{
			if (!$this->verifyModulePermissions(Permissions::UPDATE_AUTH_ACTION)){
				return $this->returnAjax(array('__status__'=>403));
			}
			$floor = Request::queryString('post.floor');
			$custom_number = Request::queryString('post.custom_number');
			$room_type = Request::queryString('post.room_type');
			$money = Request::queryString('post.money');
			$area = Request::queryString('post.areas');
			$detain = Request::queryString('post.detain');
			$pay = Request::queryString('post.pay');
			$room_config = Request::queryString('post.room_Config');
			$room_id = Request::queryString("post.room_id",0,"int");
			$flat_id = Request::queryString("post.flat_id",0,"int");
			$fee_data = Request::queryString("post.fee_data");
			$room_images = I("post.room_images"); 
			if (!empty($room_config))
			{
				$room_config = implode("-", $room_config);
			}
			
			$editData = array();
			$editData['floor'] = $floor;
			$editData['custom_number'] = $floor.$custom_number;
			$editData['room_type'] = $room_type;
			$editData['money'] = $money;
			$editData['area'] = $area;
			$editData['detain'] = $detain;
			$editData['pay'] = $pay;
			$editData['room_config'] = $room_config;
			$editData['image'] = $room_images;
			
			$result = $roomFocusHelper->updateRoomFocus($editData, $room_id,$flat_id,$this->user,$fee_data);
			if ($result)
			{
				return $this->returnAjax(array("status"=>1,"data"=>"修改成功","url"=>Url::parse("centralized-roomfocus/index/flat_id/{$flat_id}")));
			}
			return $this->returnAjax(array("status"=>0,"data"=>"修改失败"));
		}
	}
	
	/**
	 * 新增房间
	 *  最后修改时间 2015-3-24
	 *
	 * @author dengshuang
	 */
	public function addAction(){
		if (!$this->verifyModulePermissions(Permissions::INSERT_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("Centralized-Roomfocus/add")));
		}
		if(!\App\Web\Lib\Request::isPost()){
			$flat_id = Request::queryString('get.flat_id');
			if(empty($flat_id)){
				return $this->returnAjax(array("status"=>0,"data"=>"没有找到公寓ID"));
			}
			$flatModel = new \App\Web\Mvc\Model\Flat();
			$flatInfo = $flatModel->getOne(array('flat_id'=>$flat_id));
			if(empty($flatInfo)){
				return $this->returnAjax(array("status"=>0,"data"=>"没有找到公寓"));
			}
			
			$configModel = new \Common\Model\Erp\SystemConfig();
			$feeTypeHelper = new  \Common\Helper\Erp\FeeType();
			$feeHelper = new \Common\Helper\Erp\Fee();
			$fee_type = $feeTypeHelper->getCompanyFeeType($this->user);
			$feeTypeHelper->getUnsetFee($fee_type);
			$focus_room_type = $configModel->getFind('FocusRoom', 'focus_room_type');
			$public_facilities = $configModel->getFind("House", "public_facilities");
			$pay_config_data = $configModel->getFind("System", "PayConfig");
			$flat_fee_data = $feeHelper->getDataBySource($flat_id, \Common\Model\Erp\Fee::SOURCE_FLAT);
			
			$this->assign("fee_type", $fee_type);
			$this->assign('focus_room_type', $focus_room_type);
			$this->assign('flatInfo', $flatInfo);
			$this->assign("public_facilities", $public_facilities);
			$this->assign("fee_data", $flat_fee_data);
			$this->assign("pay_config", $pay_config_data);
			$data = $this->fetch("Centralized/add_centralized_house");
			
			return $this->returnAjax(array( "status"=>1,
											"tag_name"=>"添加集中式房间",
											"model_name"=>"room_focus_add",
											"model_js"=>"centralized_add_houseJs",
											"model_href"=>Url::parse("Centralized-Roomfocus/add"),
											"tag"=>Url::parse("Centralized-Roomfocus/list")."&flat_id=".$flat_id,
											"data"=>$data));
		}else{
			$user = $this->user;
			$flat_id = Request::queryString('post.flat_id');
			$floor = Request::queryString('post.floor');
			$custom_number = Request::queryString('post.custom_number');
			$room_type = Request::queryString('post.room_type');
			$money = Request::queryString('post.money');
			$area = Request::queryString('post.areas');
			$detain = Request::queryString('post.detain');
			$pay = Request::queryString('post.pay');
			$room_config = Request::queryString('post.room_Config');
			$imag = Request::queryString("post.room_images");
			$fee_data = Request::queryString("post.fee_data");
			
			if (!empty($room_config))
			{
				$room_config = implode("-", $room_config);
			}
			//TODO 验证
			$addData = array();
			$addData['floor'] = $floor;
			$addData['flat_id'] = $flat_id;
			$addData['custom_number'] = $floor.$custom_number;
			$addData['room_type'] = $room_type;
			$addData['money'] = $money;
			$addData['area'] = $area;
			$addData['detain'] = $detain;
			$addData['pay'] = $pay;
			$addData['room_config'] = $room_config;
			$addData['owner_id'] = $user['user_id'];
			$addData['create_uid'] = $user['user_id'];
			$addData['image'] = $imag;
			$addData['create_time'] = time();
			
			$roomFocusHelper = new \Common\Helper\Erp\RoomFocus();
			$result = $roomFocusHelper->addRoom($addData, $user,$fee_data);
			if($result){
					return $this->returnAjax(array(	"status"=>1,"data"=>"添加成功",
													"url"=>Url::parse("Centralized-Roomfocus/index")."&flat_id=".$flat_id,
													"tag"=>Url::parse("Centralized-Roomfocus/index")."&flat_id=".$flat_id));
				}else{
					return $this->returnAjax(array("status"=>0,"data"=>"添加失败"));
				}
		}
	}
	
	/**
	 * 房态列表
	 *  最后修改时间 2015-3-25
	 *  
	 * @author dengshuang
	 */
	public function listAction(){
		$flat_id = Request::queryString('get.flat_id');
// 		$custom_number = Request::queryString('get.custom_number');
// 		$status = Request::queryString('get.status');
// 		$room_type = Request::queryString('get.room_type');
		$page = Request::queryString('get.page');
		if(empty($flat_id)){
			echo 'empty flat_id';die;
		}
		$flatModel = new \Common\Model\Erp\Flat();
		$flatInfo = $flatModel->getOne(array('flat_id'=>$flat_id));
		if(empty($flatInfo)){
			echo 'error flat_id';die;
		}
		$roomFocusModel = new \Common\Model\Erp\RoomFocus();
		$room_type_arr = $roomFocusModel->getUserRoomType($flat_id);
		$pagesize = 30;
		$list = $roomFocusModel->getListData($flat_id,$page,$pagesize);
		$this->assign('list', $list);
		$user = $this->user;
		$this->assign('RoomTotal', $roomFocusModel->getRoomTotal($user,$flat_id));//总房间数
		$this->assign('RoomTotalRent', $roomFocusModel->getRoomTotal($user,array('status'=>2),$flat_id));//已出租房间
		$this->assign('RoomTotalEmpty', $roomFocusModel->getRoomTotal($user,array('status'=>1),$flat_id));//未出租房间
		$this->assign('RoomReserve', $roomFocusModel->getRoomReserve($user,$flat_id));//已预定房间
		$this->assign('AverageRent', $roomFocusModel->getAverageRent($user,$flat_id));//平均租金
		$this->assign('RentPersent', $roomFocusModel->getRentPercent($user,$flat_id));//
		$this->assign('monthEmptyPercent', $roomFocusModel->getEmptyPercent($user,30,$flat_id));
		$this->assign('yearEmptyPercent', $roomFocusModel->getEmptyPercent($user,365,$flat_id));
		$this->display();
	}
	
	/**
	 * 房源预约退租
	 * 修改时间2015年3月30日 09:45:46
	 *
	 * @author yzx
	 */
	public function rentalbackAction()
	{
		$data = array();
		if (!Request::isPost())
		{
			$RoomFocusHelper = new \Common\Helper\Erp\Centralized\Room();
			$todoModel = new \Common\Model\Erp\Todo();
			$reserveBackRentalModel = new \Common\Model\Erp\ReserveBackRental();
			$room_focus_id = Request::queryString("get.room_id",0,"int");
			$back_rental_time = Request::queryString("get.time_outrented",0,"string");
			$remark = Request::queryString("get.notice",'',"string");
			$reser_back_id = Request::queryString("get.reser_back_id",0,"int");
				
			$data['source_id'] = $room_focus_id;
			$data['back_rental_time'] = $back_rental_time;
			$data['remark'] = $remark;
			$data['company_id'] = $this->user['company_id'];
			$data['user_id'] = $this->user['user_id'];
			
			//修改备忘
			if ($reser_back_id){
				$todo_data = $todoModel->getOne(array("module"=>$todoModel::MODEL_ROOM_FOCUS_RESERVE,"entity_id"=>$room_focus_id));
				if($back_rental_time!=date('Y-m-d',strtotime('now')))
				{
					if (strpos($todo_data['content'], "今天")){
						$content = str_replace("今天", $back_rental_time, $todo_data['content']);
					}else {
						$content = str_replace(date("Y-m-d",$todo_data['deal_time']), $back_rental_time, $todo_data['content']);
					}
				}else {
					if (!strpos($todo_data['content'], "今天")){
						$content = str_replace(date("Y-m-d",$todo_data['deal_time']), $back_rental_time, $todo_data['content']);
					}else {
						$content = $content = str_replace(date("Y-m-d",$todo_data['deal_time']), $back_rental_time, $todo_data['content']);
					}
				}
				$todoModel->edit(array("module"=>$todoModel::MODEL_ROOM_FOCUS_RESERVE,"entity_id"=>$room_focus_id), array("deal_time"=>strtotime($back_rental_time),"content"=>$content));
				$data['back_rental_time'] = strtotime($back_rental_time);
				$result = $reserveBackRentalModel->edit(array("reserve_back_id"=>$reser_back_id), $data);
			}else {
				$result = $RoomFocusHelper->appointmentrent($data);
			}
			
			if ($result)
			{
				$this->creatData();
				exit();
			}
			return $this->returnAjax(array("status"=>0,"data"=>false));
		}
	}
	/**
	 * 停用房源
	 * 修改时间2015年3月30日 15:01:08
	 * 
	 * @author yzx
	 */
	public function stopAction()
	{
		$data = array();
		if (!Request::isPost())
		{
			$RoomFocusHelper = new \Common\Helper\Erp\Centralized\Room();
			$stopHouseModel = new \Common\Model\Erp\StopHouse();
			$todoModel = new \Common\Model\Erp\Todo();
			$source_id = Request::queryString("get.room_id",0,"int");
			$start_time = Request::queryString("get.endtime_start",0,"string");
			$end_time = Request::queryString("get.endtime_end",0,"string");
			$stop_reason = Request::queryString("get.end_reason",'',"string");
			$remark = Request::queryString("get.notice",'',"string");
			$stop_id = Request::queryString("stop_id",0,"int");
			
			//验证有没有该房间权限
			if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $source_id, SysHousingManagement::CENTRALIZED)){
				return $this->returnAjax(array('__status__'=>403));
			}
			
			$data['source_id'] = $source_id;
			$data['start_time'] = $start_time;
			$data['end_time'] = $end_time;
			$data['stop_reason'] = $stop_reason;
			$data['remark'] = $remark;
			$data['company_id']= $this->user['company_id'];
			$data['user_id']= $this->user['user_id'];
			
			if ($stop_id){
				$data['start_time'] = strtotime($start_time);
				$data['end_time'] = strtotime($end_time);
				$result = $stopHouseModel->edit(array("stop_id"=>$stop_id), $data);
				$todoModel->edit(array("module"=>$todoModel::MODEL_ROOM_FOCUS_STOP,"entity_id"=>$source_id), array("deal_time"=>strtotime($end_time)));
			}else {
				$result = $RoomFocusHelper->disable($data);
			}
			if ($result)
			{
				$this->creatData();
				exit();
			}
			return $this->returnAjax(array("status"=>0,"data"=>"停用失败"));
		}
	}
	/**
	 * 添加预定人信息
	 * 修改时间2015年3月30日 16:01:02
	 * 
	 * @author yzx
	 */
	public function renewalAction()
	{
		$data = array();
		$user = $this->user;
		if (!Request::isPost())
		{
			if (!$this->verifyModulePermissions(Permissions::INSERT_AUTH_ACTION,'sys_reservation_management')){
				return $this->returnAjax(array('__status__'=>403));
			}
			$RoomFocusHelper = new \Common\Helper\Erp\Centralized\Room();
			$reserverModel = new \Common\Model\Erp\Reserve();
			$room_focus_id = Request::queryString("get.room_id",0,'int');
			$name = Request::queryString("get.name",'','string');
			$phone = Request::queryString("get.phone",'','string');
			$idcard = Request::queryString("get.idcard",'','string');
			$money = Request::queryString("get.money",0,'int');
			$stime = Request::queryString("get.begin_date",0,'string');
			$etime = Request::queryString("get.end_date",0,"string");
			$pay_type = Request::queryString("get.paytype",0,'int');
			$source = Request::queryString("get.gettype",0,"int");
			$mark = Request::queryString("get.remark",'',"string");
			
			//验证有没有该房间权限
			if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $room_focus_id, SysHousingManagement::CENTRALIZED)){
				return $this->returnAjax(array('__status__'=>403));
			}
			
			$data['house_id'] = 0;
			$data['room_id'] = $room_focus_id;
			$data['name'] = $name;
			$data['phone'] = $phone;
			$data['idcard'] = $idcard;
			$data['money'] = $money;
			$data['stime'] = $stime;
			$data['etime'] = $etime;
			$data['source'] = $source;
			$data['pay_type'] = $pay_type;
			$data['mark'] = $mark;
			$data['company_id'] = $user['company_id'];
			$data['user_id'] = $user['user_id'];
			
			if (strlen($mark)>255){
				return $this->returnAjax(array('status'=>0,'data'=>'备注太长'));
			}
			$rdata = $reserverModel->getData(array('house_id'=>$data['house_id'],'room_id'=>$data['room_id'],'house_type'=>$reserverModel::HOUSE_TYPE_F,'is_delete'=>0));
			$stime = strtotime($data['stime']);
			if (!empty($rdata)){
    			$stime = strtotime($data['stime']);
    			$etime = strtotime($data['etime']);
    			foreach($rdata as $key=>$v)
    			{
    				if($stime>=$v['stime'] && $stime<=$v['etime'])
    				{
    					return $this->returnAjax(array('status'=>0,'data'=>'同一时间段不能多次预定'));
    				} elseif ($stime <= $v['stime'] && $etime >= $v['stime']) {
    					return $this->returnAjax(array('status'=>0,'data'=>'同一时间段不能多次预定'));
    				} elseif ($stime >= $v['stime'] && $etime <= $v['etime']) {
    					return $this->returnAjax(array('status'=>0,'data'=>'同一时间段不能多次预定'));
    				} elseif ($stime <= $v['stime'] && $etime >= $v['etime']) {
    					return $this->returnAjax(array('status'=>0,'data'=>'同一时间段不能多次预定'));
    				}
    			}
    		}
			$result = $RoomFocusHelper->schedule($data);
			if ($result)
			{
				$this->creatData($result);
				exit();
			}
			return $this->returnAjax(array("status"=>0,'data'=>false));
		}
	}
	/**
	 * 房源退租
	 * 修改时间2015年4月7日 17:05:17
	 *
	 * @author yzx
	 */
	public function rentaloutAction()
	{
		if (Request::isPost())
		{
			$data = array();
			$RoomFocusHelper = new \Common\Helper\Erp\Centralized\Room();
			$room_id = Request::queryString("post.room_id",0,"int");
			
			//验证有没有该房间权限
			if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $room_id, SysHousingManagement::CENTRALIZED)){
				return $this->returnAjax(array('__status__'=>403));
			}
			
			$data['room_id'] = $room_id;
			$result = $RoomFocusHelper->rentout($data);
			if ($result)
			{
				return $this->returnAjax(array("status"=>1,"data"=>$result));
			}
			return $this->returnAjax(array("status"=>0,"data"=>false));
		}
	}
	/**
	 * 撤销预约退租
	 * 修改时间2015年4月3日 10:31:35
	 *
	 * @author yzx
	 */
	public function revocationsubscribeAction()
	{
		if (!Request::isPost())
		{
			$data = array();
			$RoomFocusHelper = new \Common\Helper\Erp\Centralized\Room();
			$room_id = Request::queryString("get.room_id",0,"int");
			
			//验证有没有该房间权限
			if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $room_id, SysHousingManagement::CENTRALIZED)){
				return $this->returnAjax(array('__status__'=>403));
			}
			
			$data['room_id'] = $room_id;
			$data['user_id'] =$this->user['user_id'];
			
			$RoomFocusHelper->revocationSubscribe($data);
			$this->creatData();
			exit();
		}
	}
	/**
	 * 取消预定
	 * 修改时间2015年4月23日 10:16:41
	 * 
	 * @author yzx
	 */
	public function abolishrenewalAction()
	{
		if (!Request::isPost())
		{
			$room_id = Request::queryString("get.room_id",0,"int");
			
			//验证有没有该房间权限
			if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $room_id, SysHousingManagement::CENTRALIZED)){
				return $this->returnAjax(array('__status__'=>403));
			}
			
			$reserveHelper = new \Common\Helper\Erp\Reserve();
			$data=$reserveHelper->getDataByCondition(\Common\Model\Erp\Reserve::HOUSE_TYPE_F,$room_id);
			if (empty($data))
			{
				return $this->returnAjax(array("status"=>0,"data"=>"没有预定人"));
			}
			return $this->returnAjax(array("status"=>1,"data"=>$data));
		}else 
		{
			$RoomFocusHelper = new \Common\Helper\Erp\Centralized\Room();
			$reserve_id = Request::queryString("post.reserve_id");
			$house_id = Request::queryString("post.room_id",0,'int');
			
			$data['reserve_id'] = $reserve_id;
			$data['house_id'] = $house_id;
			
			$result = $RoomFocusHelper->abolishRenewal($data);
			if ($result)
			{
				$this->creatData(0,$reserve_id);
				exit();
			}
			return $this->returnAjax(array("status"=>0,"data"=>"添加失败"));
		}
	}
	//获取预定
	public function reserveAction(){
		$getCityByIp = new \Common\Helper\Erp\GetCityByIp();
		$ip = $getCityByIp->GetIpLookup();
		$room_id = Request::queryString("get.room_id",0,"int");
		
		//验证有没有该房间权限
		if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $room_id, SysHousingManagement::CENTRALIZED)){
			return $this->returnAjax(array('__status__'=>403));
		}
		
		$reserveHelper = new \Common\Helper\Erp\Reserve();
		$data=$reserveHelper->getDataByCondition(\Common\Model\Erp\Reserve::HOUSE_TYPE_F,$room_id);
		if (empty($data))
		{
			return $this->returnAjax(array("status"=>0,"data"=>"获取预订人失败"));
		}
		return $this->returnAjax(array("status"=>1,"count"=>count($data),"data"=>$data));
	}
	/**
	 * 获取房源详情
	 * 修改时间2015年3月31日 15:56:54
	 * 
	 * @author yzx
	 */
	public function detailAction()
	{
		if (Request::isGet())
		{
			$room_focus_id = Request::queryString("get.room_focus_id",0,"int");
			
			//验证有没有该房间权限
			if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $room_focus_id, SysHousingManagement::CENTRALIZED)){
				return $this->returnAjax(array('__status__'=>403));
			}
			
			$roomFocusModel = new \Common\Model\Erp\RoomFocus();
			$roomFocusInfo = $roomFocusModel->getOne(array('room_focus_id'=>$room_focus_id));
			return $this->returnAjax(array("status"=>1,"data"=>$roomFocusInfo));
		}
		return $this->returnAjax(array("status"=>0,"data"=>false));
	}
	/**
	 * 恢复停用房源
	 * 修改时间2015年4月7日 17:47:08
	 *
	 * @author yzx
	 */
	public function recoverAction()
	{
		if (!Request::isPost())
		{
			$data = array();
			$RoomFocusHelperRoom = new \Common\Helper\Erp\Centralized\Room();
			$RoomFocusHelper = new \Common\Helper\Erp\RoomFocus();
			$room_id = Request::queryString("get.room_id",0,"int");
			
			//验证有没有该房间权限
			if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $room_id, SysHousingManagement::CENTRALIZED)){
				return $this->returnAjax(array('__status__'=>403));
			}
			
			$data['room_id'] = $room_id;
			$data['user_id'] = $this->user['user_id'];
			
			$result = $RoomFocusHelperRoom->recover($data);
			if ($result)
			{
				$out_data = array();
				$room_data = $RoomFocusHelper->getData($room_id);
				$this->creatData();
				exit();
			}
			return $this->returnAjax(array("status"=>0,"data"=>"启用失败"));
		}
	}
	/**
	 * 房间退租
	 * 修改时间2015年4月28日 14:26:23
	 * 
	 * @author yzx
	 */
	public function rentoutAction()
	{
		$RoomFocusHelperRoom = new \Common\Helper\Erp\Centralized\Room();
		$room_id = Request::queryString("get.room_id",0,"int");
		
		//验证有没有该房间权限
		if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $room_id, SysHousingManagement::CENTRALIZED)){
			return $this->returnAjax(array('__status__'=>403));
		}
		
		$data['room_id'] = $room_id;
		$data['user_id'] = $this->user['user_id'];
		
		$result = $RoomFocusHelperRoom->rentout($data);
		if ($result)
		{
			$this->creatData();
			exit();
		}
		return $this->returnAjax(array("status"=>0,"data"=>"退租失败"));
	}
	/**
	 * 批量添加房间
	 * 修改时间2015年4月22日 09:12:46
	 * 
	 * @author yzx
	 */
	public function batchaddAction()
	{
		if (!$this->verifyModulePermissions(Permissions::INSERT_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("Centralized-Roomfocus/batchadd")));
		}
		
		if (!Request::isPost())
		{
			$flatModel = new \Common\Model\Erp\Flat();
			$flat_id = Request::queryString("get.flat_id",0,'int');
			if ($flat_id<=0)
			{
				return $this->returnAjax(array('status'=>0,'data'=>'没有找到公寓ID'));
			}
			$flat_data = $flatModel->getOne(array("flat_id"=>$flat_id));
			$this->assign('flat_data', $flat_data);
			$data = $this->fetch('Centralized/batch_add_room');
			
			return $this->returnAjax(array( "status"=>1,
											"tag_name"=>"批量添加房间",
											"model_name"=>"room_focus_batchadd",
											"model_js"=>"centralized_add_house_bulkJs",
											"model_href"=>Url::parse("Centralized-Roomfocus/batchadd"),
											"data"=>$data));
		}else 
		{
			$data = array();
			$flatHelper = new \Common\Helper\Erp\Flat();
			$flat_id = Request::queryString("post.flat_id",0,'int');
			$floor_num = Request::queryString("floornum",0,"string");
			$cute_type = Request::queryString("post.cutetype",0,"int");
			$houses_number = Request::queryString("post.houses");
			
			$data['flat_id'] = $flat_id;
			$data['floor_num'] = $floor_num;
			$data['cute_type'] = $cute_type;
			
			if (is_array($houses_number) && !empty($houses_number)){
				foreach ($houses_number as $key=>$val){
					$houseNmber[$key]=$val['house_num'];
					$roomNumber[$key] = $val['room_num'];
				}
				$unique_number = array_unique($houseNmber);
				foreach ($unique_number as $key=>$val){
						$out_number[] = array("house_num"=>$houseNmber[$key],"room_num"=>$roomNumber[$key]);
				}
			}
			$data['houses_number'] = $out_number;
			$result = $flatHelper->batchAdd($data,$this->user);
			if ($result)
			{
				return $this->returnAjax(array('status'=>1,"data"=>'添加成功','tag'=>Url::parse("Centralized-Roomfocus/index")."&flat_id=".$flat_id));
			}
			return $this->returnAjax(array('status'=>0,"data"=>'添加失败'));
		}
	}
	/**
	 * 批量删除
	 * 修改时间2015年4月25日 15:09:01
	 * 
	 * @author yzx
	 */
	public function batchdeleteAction()
	{
		if (!$this->verifyModulePermissions(Permissions::DELETE_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403));
		}
		if (Request::isPost())
		{
			$RoomFocusHelper = new \Common\Helper\Erp\RoomFocus();
			$room_id = Request::queryString("post.room_id");
			$flat_id = Request::queryString("post.flat_id",0,"int");
			
			//验证有没有该房间权限
			if(!$this->verifyDataLinePermissions(Permissions::DELETE_AUTH_ACTION, $room_id, SysHousingManagement::CENTRALIZED)){
				return $this->returnAjax(array('__status__'=>403));
			}
			
			$result = $RoomFocusHelper->batchDelete($room_id, $flat_id, $this->user);
			if ($result)
			{
				return $this->returnAjax(array("status"=>1,"data"=>"删除成功"));
			}
			return $this->returnAjax(array("status"=>0,"data"=>"删除失败"));
		}
	}
	/**
	 * 获取停用信息
	 * 修改时间2015年5月14日 10:38:59
	 * 
	 * @author yzx
	 */
	public function getstopAction()
	{
		$house_id = I("get.room_id",0,"int");
		$stopHouseModle = new \Common\Model\Erp\StopHouse();
		$data = $stopHouseModle->getDataBySourceId($house_id, \Common\Model\Erp\StopHouse::CENTRALIZATION_TYPE);
		return $this->returnAjax(array("status"=>1,"data"=>array($data)));
	}
	/**
	 * 编辑停用原因
	 * 修改时间2015年5月14日 15:48:03
	 * 
	 * @author yzx
	 */
	public function editstopAction()
	{
		$stop_id = I("get.stop_id",0,"int");
		$endtime_start = I("get.endtime_start","","string");
		$endtime_end = I("get.endtime_end","","string");
		$notice = I("get.notice","","string");
		
		$data['start_time'] = strtotime($endtime_start);
		$data['end_time'] = strtotime($endtime_end);
		$data['remark'] = $notice;
		
		$stopHouseModle = new \Common\Model\Erp\StopHouse();
		$todoModel =new \Common\Model\Erp\Todo();
		
		//修改备忘
		$stop_data = $stopHouseModle->getOne(array("stop_id"=>$stop_id));
		$result = $stopHouseModle->edit(array("stop_id"=>$stop_id), $data);
		if ($result)
		{
			$todo_data = $todoModel->getOne(array("module"=>$todoModel::MODEL_ROOM_FOCUS_STOP,"entity_id"=>$stop_data['source_id']));
			if($endtime_end!=date('Y-m-d',strtotime('now')))
			{
				if (strpos($todo_data['content'], "今天")){
					$content = str_replace("今天", $endtime_end, $todo_data['content']);
				}
			}else {
				if (!strpos($todo_data['content'], "今天")){
					$content = str_replace(date("Y-m-d",$todo_data['deal_time']), $endtime_end, $todo_data['content']);
				}else {
					$content = $todo_data['content'];
				}
			}
			$todoModel->edit(array("module"=>$todoModel::MODEL_ROOM_FOCUS_STOP,"entity_id"=>$stop_data['source_id']), 
							array("deal_time"=>strtotime($endtime_end),"content"=>$content));
		
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
		$room_id = I("room_id", 0, "int");
		$room_model = new \Common\Model\Erp\RoomFocus();
		$room_num = $room_model->getOne(array('room_focus_id' => $room_id), array('custom_number' => 'custom_number'));
		$reserve_back_model = new \Common\Model\Erp\ReserveBackRental();
		$reserve_data = $reserve_back_model->getOne(array('type' => $reserve_back_model::CENTRALIZATION_TYPE,"house_type"=>0,'source_id' => $room_id, 'is_delete' => 0), array('back_rental_time' => 'back_rental_time', 'remark' => 'remark'));
		$reserve_info = array_merge($reserve_data, $room_num);
		$reserve_info['back_rental_time'] = date('Y-m-d', $reserve_info['back_rental_time']);
		return $this->returnAjax(array("status"=>1,"data"=>$reserve_info));
	}
	public function updatehousenumberAction(){
		set_time_limit(0);
		$flatModel = new \Common\Model\Erp\Flat();
		$RoomFocusHelper = new \Common\Helper\Erp\RoomFocus();
		$flat_data = $flatModel->getData();
		foreach ($flat_data as $key=>$val){
			$RoomFocusHelper->chackHouseRoomNumber($val['flat_id']);
		}
		echo "成功";
	}
	/**
	 * 构造单个room
	 * 修改时间2015年4月24日 14:44:55
	 * 
	 * @author yzx
	 */
	private function creatData($reserveId=0,$reserve_out_Id=array())
	{
		$RoomFocusModel = new \Common\Model\Erp\RoomFocus();
		$flatModel = new \Common\Model\Erp\Flat();
		$flat_id = Request::queryString('flat_id',0,'int');
		$room_id = Request::queryString("room_id",0,"int");
		$reser_back_id = Request::queryString("reser_back_id",0,"int");
		$room_data = $RoomFocusModel->getListData($flat_id,$this->user,1,1,null,$room_id);
		$flat_data = $flatModel->getOne(array("flat_id"=>$flat_id));
		$this->assign("flat_data", $flat_data);
		$this->assign("list_data", $room_data['data']);
		$data = $this->fetch("Centralized/Flatmodel/room_html");
		
		$status['status'] =1;
		$status['data'] =$data;
		$status['room_url'] = Url::parse("centralized-roomfocus/index/flat_id/{$flat_id}");
		$status['message']="撤销成功";
		if ($reser_back_id){
			$status['message']="修改成功";
		}
		if (is_array($reserve_out_Id) && !empty($reserve_out_Id)){
			$reserve_out_url = Url::parse('Finance-Serial/addexpense')."&us_source=unsubscribe"."&reserve_id=".implode(",", $reserve_out_Id);
			$status['url'] =$reserve_out_url;
			unset($status['room_url']);
			unset($status['message']);
		}
		if ($reserveId>0){
			$reserve_url = Url::parse('Finance-Serial/addincome/')."&reserve_source= focus"."&reserve_id=".$reserveId;
			$status['reserve_url'] =$reserve_url;
			unset($status['room_url']);
			unset($status['message']);
		}
		return $this->returnAjax($status);
	}
}