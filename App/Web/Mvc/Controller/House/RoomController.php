<?php
namespace App\Web\Mvc\Controller\House;
use App\Web\Lib\Request;
use App\Web\Helper\Url;
use Common\Model\Erp\StopHouse;
use Common\Model\Erp\Attachments;
use Common\Helper\Permissions;
use Common\Helper\Permissions\Hook\SysHousingManagement;
/**
 * 分散式房源控制器
 *
 * @author too
 * 最后修改时间 2015年4月16日 下午3:47:12
 */
class RoomController extends \App\Web\Lib\Controller{
    /**
     * 添加房间
     * 修改时间2015年4月28日 10:57:22
     * 
     * @author yzx
     */
    public function addAction()
    {
    	if (!Request::isPost())
    	{
    		$sysConfig = new \Common\Model\Erp\SystemConfig();
    		$count = Request::queryString("get.count",0,"int");
    		$house_id = Request::queryString("get.house_id",0,"int");
    		
    		$pay_config = $sysConfig->getFind("House", "PublicConfig");
    		$public_config = $sysConfig->getFind("House", "public_facilities");
    		$roomHelper = new \Common\Helper\Erp\Room();
    		$room_number = $roomHelper->creatRoomNumber($count);
    		
    		$this->assign("house_id", $house_id);
    		$this->assign("count", $count);
    		$this->assign("public_config", $public_config);
    		$this->assign("pay_config", $pay_config);
    		$this->assign("room_number", $room_number);
    		$this->assign("edit_id", 0);
    		$data = $this->fetch("House/Room/add");
    		
    		return $this->returnAjax(array( "status"=>1,
					"tag_name"=>"分散式房间添加",
					"model_name"=>"room_add",
					"model_js"=>"distributed_room_viewJs",
					"model_href"=>Url::parse("House-Room/add"),
					"data"=>$data));
    	}else 
    	{
    		$data = array();
   			$data = Request::queryString("post.data");
   			$house_id = Request::queryString("post.house_id",0,"int");
   			$data = htmlspecialchars_decode($data);
   			$json_data = json_decode($data,true);
    		$roomHelper = new \Common\Helper\Erp\Room();
    		$LandlordContractModel = new \Common\Helper\Erp\LandlordContract();
    		$houseModel = new \Common\Model\Erp\House();
    		$house_data = $houseModel->getOne(array("house_id"=>$house_id));
    		$is_add_landlord = $LandlordContractModel->CheckHouseName(\Common\Model\Erp\LandlordContract::HOUSE_TYPE_R , $house_data['house_name'] , $this->user);
    		$result = $roomHelper->addRoom($json_data,$house_id);
    		if ($result['status'])
    		{
    			$status['status']=1;
    			$status['data']=true;
    			$status['message']="添加成功";
    			$status['p_url']=Url::parse("House-House/index");
    			if ($is_add_landlord==false){
    				$status['landlord_url'] = Url::parse("landlord-index/add")."&house_type=1"."&house_id=".$house_id;
    			}
    			return $this->returnAjax($status);
    		}
    		return $this->returnAjax(array("status"=>0,"data"=>false,"message"=>$result['msg']));
    	}
    }
    /**
     * 修改房间
     * 修改时间2015年4月28日 11:30:31
     * 
     * @author yzx
     */
    public function editAction()
    {
    	$roomHelper = new \Common\Helper\Erp\Room();
    	if (!Request::isPost())
    	{
    		
    		$sysConfig = new \Common\Model\Erp\SystemConfig();
    		$house_id = Request::queryString("get.house_id",0,"int");
    		
    		//判断用户是否对当前对象有权限操作
    		if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $house_id, SysHousingManagement::DECENTRALIZED_HOUSE)){
    			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("House-room/edit")));
    		}
    		
    		$room_list = $roomHelper->getRoomByHouse($house_id);
    		$pay_config = $sysConfig->getFind("House", "PublicConfig");
    		
    		$this->assign("pay_config", $pay_config);
    		$this->assign("room_number", $room_list);
    		$this->assign("edit_id", 1);
    		$this->assign("house_id", $house_id);
    		$data = $this->fetch("House/Room/add");
    		
    		return $this->returnAjax(array( "status"=>1,
    				"tag_name"=>"分散式房间修改",
    				"model_name"=>"room_edit",
    				"model_js"=>"distributed_room_viewJs",
    				"model_href"=>Url::parse("House-Room/add"),
    				"data"=>$data));
    	}else 
    	{
    		$roomModel = new \Common\Model\Erp\Room();
    		$data = Request::queryString("post.data");
    		$house_id = Request::queryString("post.house_id",0,"int");
    		$data = htmlspecialchars_decode($data);
   			$json_data = json_decode($data,true);
   			$room_json = json_decode($json_data[0],true);
   			$room_data = $roomModel->getOne(array("room_id"=>$room_json['room_id']));
   			
   			//判断用户是否对当前对象有权限操作
   			if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $house_id, SysHousingManagement::DECENTRALIZED_HOUSE)){
    				return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("House-room/edit")));
    			}
    			
    		$result = $roomHelper->editRoom($json_data,$house_id);
    		if ($result)
    		{
    			return $this->returnAjax(array("status"=>1,
    										   "data"=>$result,
    										   "message"=>"修改成功",
    										   'p_url' => Url::parse("House-House/index")));
    		}
    		return $this->returnAjax(array("status"=>0,"data"=>false,"message"=>"修改失败"));
    	}
    }
    /**
     * 预约退租
     * 修改时间2015年4月2日 10:03:28
     *
     * @author yzx
     */
    public function rentalbackAction()
    {
    	if (Request::isPost())
    	{
    		$data = array();
    		$roomHelper = new \Common\Helper\Erp\Distributed\Room();
    		$reserve_back_model = new \Common\Model\Erp\ReserveBackRental();
    		$todoModel =new \Common\Model\Erp\Todo();
    		$room_id = Request::queryString("post.room_id",0,"int");
    		$house_id = Request::queryString("post.house_id",0,"int");
    		$back_rental_time = Request::queryString("post.start_time",0,"string");
    		$remark = Request::queryString("post.remark",'',"string");
    		$reser_back_id = I("post.reser_back_id",0,"int");
    		//修改备忘时间START
    		if ($room_id>0){
    			$todo_data = $todoModel->getOne(array("module"=>$todoModel::MODEL_ROOM_RESERVE,"entity_id"=>$room_id));
    		}
    		if ($house_id>0){
    			$todo_data = $todoModel->getOne(array("module"=>$todoModel::MODEL_HOUSE_RESERVE,"entity_id"=>$house_id));
    		}
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
    				$content = str_replace(date("Y-m-d",$todo_data['deal_time']), $back_rental_time, $todo_data['content']);
    			}
    		}
    		//修改备忘时间END
    		
    		if ($room_id>0)
    		{
    			$roomModel = new \Common\Model\Erp\Room();
    			$room_data = $roomModel->getOne(array('room_id'=>$room_id),array("house_id"));
    			
    			//判断用户是否对当前对象有权限操作
    			if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $room_data['house_id'], SysHousingManagement::DECENTRALIZED_HOUSE)){
    				return $this->returnAjax(array('__status__'=>403));
    			}
    			
    			$source_id = $room_id;
    			$house_type = \Common\Model\Erp\ReserveBackRental::HOUSE_TYPE_ROOM;
    			$todoModel->edit(array("module"=>$todoModel::MODEL_ROOM_RESERVE,"entity_id"=>$source_id), array("deal_time"=>strtotime($back_rental_time),"content"=>$content));
    		}
    		if ($house_id>0)
    		{
    			//判断用户是否对当前对象有权限操作
    			if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $house_id, SysHousingManagement::DECENTRALIZED_HOUSE)){
    				return $this->returnAjax(array('__status__'=>403));
    			}
    			
    			$source_id = $house_id;
    			$house_type = \Common\Model\Erp\ReserveBackRental::HOUSE_TYPE_HOUSE;
    			$todoModel->edit(array("module"=>$todoModel::MODEL_HOUSE_RESERVE,"entity_id"=>$house_id), array("deal_time"=>strtotime($back_rental_time),"content"=>$content));
    		}	
    		$data['house_type'] = $house_type;
    		$data['source_id'] = $source_id;
    		$data['back_rental_time'] = $back_rental_time;
    		$data['remark'] = $remark;
    		$data['company_id'] = $this->user['company_id'];
    		$data['user_id'] = $this->user['user_id'];
    		$data['deal_time'] = $back_rental_time;
    		if ($reser_back_id) {
    		    $result = $reserve_back_model->edit(array('reserve_back_id' => $reser_back_id), array('back_rental_time' => strtotime($back_rental_time), 'remark' => $remark));
    		} else {
                $result = $roomHelper->appointmentrent($data);
    		}
    		if ($result)
    		{
    			return $this->returnAjax(array("status"=>1,"data"=>$result,"message"=>"预约成功"));
    		}
    		return $this->returnAjax(array("status"=>0,"data"=>false,"message"=>"预约失败"));
    	}
    }
    /**
     * 停用房源
     * 修改时间2015年4月2日 15:09:57
     *
     * @author yzx
     */
    public function stopAction()
    {
    	$roomHelper = new \Common\Helper\Erp\Distributed\Room();
    	if (Request::isPost())
    	{
    		$data = array();
    		$room_id = Request::queryString("post.room_id",0,"int");
    		$house_id = Request::queryString("post.house_id",0,"int");
    		$start_time = Request::queryString("post.start_time",0,"string");
    		$end_time = Request::queryString("post.end_time",0,"string");
    		$remark = Request::queryString("post.remark",'',"string");
    		if ($room_id>0)
    		{
    			$roomModel = new \Common\Model\Erp\Room();
    			$room_data = $roomModel->getOne(array('room_id'=>$room_id),array("house_id"));
    			
    			//判断用户是否对当前对象有权限操作
    			if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $room_data['house_id'], SysHousingManagement::DECENTRALIZED_HOUSE)){
    				return $this->returnAjax(array('__status__'=>403));
    			}
    			
    			$source_id = $room_id;
    			$house_type = StopHouse::HOUSE_TYPE_H;
    		}
    		if ($house_id>0)
    		{
    			//判断用户是否对当前对象有权限操作
    			if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $house_id, SysHousingManagement::DECENTRALIZED_HOUSE)){
    				return $this->returnAjax(array('__status__'=>403));
    			}
    			
    			$source_id = $house_id;
    			$house_type = StopHouse::HOUSE_TYPE_Z;
    		}
    		$data['source_id'] = $source_id;
    		$data['house_type'] = $house_type;
    		$data['start_time'] = $start_time;
    		$data['end_time'] = $end_time;
    		$data['stop_reason'] = '';
    		$data['remark'] = $remark;
    		$data['company_id'] = $this->user['company_id'];
    		$data['user_id'] = $this->user['user_id'];
    		$result = $roomHelper->disable($data);
    		$rental_url = Url::parse("Tenant-Index/adds")."&room_id=".$room_id."&house_room_id=".$house_id."&house_type=1";
    		if ($result)
    		{
    			return $this->returnAjax(array("status"=>1,"data"=>$result,"message"=>"停用成功","rental_url"=>$rental_url));
    		}
    		return $this->returnAjax(array("status"=>0,"data"=>false,"message"=>"停用失败"));
    	}
    }
    /**
     * 添加预定人
     * 修改时间2015年4月2日 15:20:02
     *
     * @author yzx
     */
    public function renewalAction()
    {
    	if (Request::isPost())
    	{
    	if (!$this->verifyModulePermissions(Permissions::INSERT_AUTH_ACTION,'sys_reservation_management')){
				return $this->returnAjax(array('__status__'=>403));
			}
    		$user = $this->user;
    		$data = array();
    		$roomHelper = new \Common\Helper\Erp\Distributed\Room();
    		$rentalModel = new \Common\Model\Erp\Rental();
    		$roomModel = new \Common\Model\Erp\Room();
    		$reserverModel = new \Common\Model\Erp\Reserve();
    		$houseModel = new \Common\Model\Erp\House();
    		$house_id = Request::queryString("post.house_id",0,"int");
    		$room_id = Request::queryString("post.room_id",0,"int");
    		$name = Request::queryString("post.name",'',"string");
    		$phone = Request::queryString("post.phone",'',"string");
    		$idcard = Request::queryString("post.idcard",'',"string");
    		$money = Request::queryString("post.money",0,"int");
    		$stime = Request::queryString("post.begin_date",'',"string");
    		$etime = Request::queryString("post.end_date",'',"string");
    		$channel = Request::queryString("post.channel",0,"int");
    		$mark = Request::queryString("post.mark",'',"string");
    		$pay_type = Request::queryString("post.ya",0,"int");
    		$source = Request::queryString("post.fu",0,"int");
    		
    		$data['house_id'] = $house_id;
    		$data['room_id'] = $room_id;
    		$data['name'] = $name;
    		$data['phone'] = $phone;
    		$data['idcard'] = $idcard;
    		$data['money'] = $money;
    		$data['stime'] = $stime;
    		$data['etime'] = $etime;
    		$data['mark'] = $mark;
    		$data['pay_type'] = $pay_type;
    		$data['company_id'] = $user['company_id'];
    		$data['user_id'] = $user['user_id'];
    		$data['source'] = $source;
    		if (strlen($mark)>255){
    			return $this->returnAjax(array('status'=>0,'message'=>'备注太长'));
    		}
    		if ($house_id>0)
    		{
    			//判断用户是否对当前对象有权限操作
    			if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $house_id, SysHousingManagement::DECENTRALIZED_HOUSE)){
    				return $this->returnAjax(array('__status__'=>403));
    			}
    			
    			$url = Url::parse("House-House/edit")."&house_id=".$house_id;
    			$house_data = $houseModel->getOne(array("house_id"=>$house_id));
    			$data['todo_title'] = $house_data['house_name'];
    			$data['house_id'] = $house_id;
    		}
    		if ($room_id>0)
    		{
    			$roomModel = new \Common\Model\Erp\Room();
    			$room_data = $roomModel->getOne(array('room_id'=>$room_id),array("house_id"));
    			
    			//判断用户是否对当前对象有权限操作
    			if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $room_data['house_id'], SysHousingManagement::DECENTRALIZED_HOUSE)){
    				return $this->returnAjax(array('__status__'=>403));
    			}
    			
    			$url = Url::parse("House-Room/roomdetail")."&room_id=".$room_id;
    			$room_data = $roomModel->getOne(array("room_id"=>$room_id));
    			$house_data = $houseModel->getOne(array("house_id"=>$room_data['house_id']));
    			$data['house_id'] = $room_data['house_id'];
    			$data['todo_title'] = $house_data['house_name']."-".$room_data['custom_number'];
    		}
    		$rdata = $reserverModel->getData(array('house_id'=>$data['house_id'],'room_id'=>$data['room_id'],'house_type'=>$reserverModel::HOUSE_TYPE_R,'is_delete'=>0));
    		if (!empty($rdata)){
    			$stime = strtotime($data['stime']);
    			$etime = strtotime($data['etime']);
    			foreach($rdata as $key=>$v)
    			{
                    if($stime>=$v['stime'] && $stime<=$v['etime'])
    				{
    					return $this->returnAjax(array('status'=>0,'message'=>'同一时间段不能多次预定'));
    				} elseif ($stime <= $v['stime'] && $etime >= $v['stime']) {
    					return $this->returnAjax(array('status'=>0,'message'=>'同一时间段不能多次预定'));
    				} elseif ($stime >= $v['stime'] && $etime <= $v['etime']) {
    					return $this->returnAjax(array('status'=>0,'message'=>'同一时间段不能多次预定'));
    				} elseif ($stime <= $v['stime'] && $etime >= $v['etime']) {
    					return $this->returnAjax(array('status'=>0,'message'=>'同一时间段不能多次预定'));
    				}
    			}
    		}
    		$result = $roomHelper->schedule($data);
    		$rental_url = Url::parse("Tenant-Index/adds")."&room_id=".$room_id."&house_room_id=".$house_id."&house_type=1";
    		//预定跳转URL
    		$reserve_url = Url::parse('Finance-Serial/addincome')."&reserve_source=disperse"."&reserve_id=".$result;
    		if ($result)
    		{
    			return $this->returnAjax(array("status"=>1,
    										   'data'=>$result,
    										   "message"=>"预定成功",
    										   "url"=>$url,
    										   "rental_url"=>$rental_url,
    										   'reserve_url'=>$reserve_url));
    		}
    		return $this->returnAjax(array('status'=>0,'data'=>false,"message"=>"预定失败"));
    	}
    }
    /**
     * 房源退租
     * 修改时间2015年4月3日 09:16:36
     *
     * @author yzx
     */
    public function rentaloutAction()
    {
    	if (Request::isPost())
    	{
    		$data = array();
    		$roomHelper = new \Common\Helper\Erp\Distributed\Room();
    		$roomModel = new \Common\Model\Erp\Room();
    		$room_id = Request::queryString("post.room_id",0,"int");
    		$house_id = Request::queryString("post.house_id",0,"int");
    		$roomData = $roomModel->getOne(array("room_id"=>$room_id));
    		$data['room_id'] = $room_id;
    		$data['house_id'] = $house_id;
    		$data['user_id'] = $this->user['user_id'];
    		$result = $roomHelper->rentout($data);
    		if ($result)
    		{
    			if ($room_id>0)
    			{
    				$roomModel = new \Common\Model\Erp\Room();
    				$room_data = $roomModel->getOne(array('room_id'=>$room_id),array("house_id"));
    				
    				//判断用户是否对当前对象有权限操作
    				if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $room_data['house_id'], SysHousingManagement::DECENTRALIZED_HOUSE)){
    					return $this->returnAjax(array('__status__'=>403));
    				}
    				
    				$id = $room_id;
    				$is_room = true;
    				$house_id = $roomData['house_id'];
    			}
    			if ($house_id>0)
    			{
    				
    				//判断用户是否对当前对象有权限操作
    				if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION,$house_id, SysHousingManagement::DECENTRALIZED_HOUSE)){
    					return $this->returnAjax(array('__status__'=>403));
    				}
    				
    				$id = $house_id;
    				$is_room = 0;
    			}
    			$houseHelper = new \Common\Helper\Erp\House();
    			$house_data = $houseHelper->getEmptyData($id,$is_room);
    			$finance_url =  Url::parse("Finance-Serial/addexpense")."&room_id=".$room_id."&house_id=".$house_id."&house_type=1"."&source=out_tenancy";
    			$rent_url = Url::parse("Tenant-Index/adds")."&room_id=".$room_id."&house_room_id=".$house_id."&house_type=1";
    			return $this->returnAjax(array("status"=>1,"data"=>$result,"message"=>"退租成功","house_data"=>$house_data,"finance_url"=>$finance_url,"rental_url"=>$rent_url));
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
    		$data = array();
    		$roomHelper = new \Common\Helper\Erp\Distributed\Room();
    		$rentalModel = new \Common\Model\Erp\Rental();
    		$room_id = Request::queryString("room_id",0,"int");
    		$house_id = Request::queryString("house_id",0,"int");
    		$data['room_id'] = $room_id;
    		$data['house_id'] = $house_id;
    		$data['user_id'] = $this->user['user_id'];
    		if ($room_id>0)
    		{
    			$roomModel = new \Common\Model\Erp\Room();
    			$room_data = $roomModel->getOne(array('room_id'=>$room_id),array("house_id"));
    			
    			//判断用户是否对当前对象有权限操作
    			if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $room_data['house_id'], SysHousingManagement::DECENTRALIZED_HOUSE)){
    				return $this->returnAjax(array('__status__'=>403));
    			}
    			
    			$id = $room_id;
    			$is_room = true;
    			$rental_data = $rentalModel->getRoomData($room_id);
    		}
    		if ($house_id>0)
    		{
    			
    			//判断用户是否对当前对象有权限操作
    			if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $house_id, SysHousingManagement::DECENTRALIZED_HOUSE)){
    				return $this->returnAjax(array('__status__'=>403));
    			}
    			
    			$id = $house_id;
    			$is_room = 0;
    			$rental_data = $rentalModel->getFocusData($house_id,\Common\Model\Erp\Rental::HOUSE_TYPE_R);
    		}
    		$result = $roomHelper->revocationSubscribe($data);
    		if ($result)
    		{
    			
    			return $this->returnAjax(array("status"=>1,"data"=>$result,"message"=>"撤销成功","rental_data"=>$rental_data, "list_url" => Url::parse("house-house/index")));
    		}
    		return $this->returnAjax(array("status"=>0,"data"=>$result,"message"=>"撤销失败"));
    }
    /**
     * 恢复停用房源
     * 修改时间2015年4月3日 10:59:33
     *
     * @author yzx
     */
    public function recoverAction()
    {
    		$data = array();
    		$roomHelper = new \Common\Helper\Erp\Distributed\Room();
    		$room_id = Request::queryString("room_id",0,"int");
    		$house_id = Request::queryString("house_id",0,"int");
    		
    		if ($room_id>0)
    		{
    			$roomModel = new \Common\Model\Erp\Room();
    			$room_data = $roomModel->getOne(array('room_id'=>$room_id),array("house_id"));
    			
    			//判断用户是否对当前对象有权限操作
    			if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $room_data['house_id'], SysHousingManagement::DECENTRALIZED_HOUSE)){
    				return $this->returnAjax(array('__status__'=>403));
    			}
    			
    			$id = $room_id;
    			$is_room = true;
    		}
    		if ($house_id>0)
    		{
    			//判断用户是否对当前对象有权限操作
    			if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $house_id, SysHousingManagement::DECENTRALIZED_HOUSE)){
    				return $this->returnAjax(array('__status__'=>403));
    			}
    			
    			$id = $house_id;
    			$is_room = 0;
    		}
    		
    		$data['house_id'] = $house_id;
    		$data['room_id'] = $room_id;
    		$data['user_id'] = $this->user['user_id'];
    		$result = $roomHelper->recover($data);
    		if ($result)
    		{
    			$rent_url = Url::parse("Tenant-Index/adds")."&room_id=".$room_id."&house_room_id=".$house_id."&house_type=1";
    			$houseHelper = new \Common\Helper\Erp\House();
    			$house_data = $houseHelper->getEmptyData($id,$is_room);
    			return $this->returnAjax(array("status"=>1,"list_url"=>Url::parse("house-house/index"),"data"=>$result,"message"=>"恢复成功","house_data"=>$house_data,"rental_url"=>$rent_url));
    		}
    		return $this->returnAjax(array("status"=>0,"data"=>false));
    }
    /**
     * 取消预定
     * 修改时间2015年4月3日 15:40:33
     *
     * @author yzx
     */
    public function abolishrenewalAction()
    {
    	if (!Request::isPost())
    	{
    		$room_id = Request::queryString("get.room_id",0,"int");
    		$house_id = Request::queryString("get.house_id",0,"int");
    		$reserveHelper = new \Common\Helper\Erp\Reserve();
    		if ($room_id>0)
    		{
    			$roomModel = new \Common\Model\Erp\Room();
    			$room_data = $roomModel->getOne(array('room_id'=>$room_id),array("house_id"));
    			
    			//判断用户是否对当前对象有权限操作
    			if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $room_data['house_id'], SysHousingManagement::DECENTRALIZED_HOUSE)){
    				return $this->returnAjax(array('__status__'=>403));
    			}
    			
    			$data=$reserveHelper->getDataByCondition(\Common\Model\Erp\Reserve::HOUSE_TYPE_R,$room_id,true);
    		}
    		if ($house_id>0)
    		{
    			//判断用户是否对当前对象有权限操作
    			if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $house_id, SysHousingManagement::DECENTRALIZED_HOUSE)){
    				return $this->returnAjax(array('__status__'=>403));
    			}
    			
    			$data=$reserveHelper->getDataByCondition(\Common\Model\Erp\Reserve::HOUSE_TYPE_R,$house_id);
    		}
    		if (empty($data))
    		{
    			return $this->returnAjax(array("status"=>0,"data"=>false,"message"=>"没有预定人"));
    		}
    		return $this->returnAjax(array("status"=>1,"data"=>$data));
    	}else 
    	{
    		$todoModel = new \Common\Model\Erp\Todo();
    		$data = array();
    		$roomHelper = new \Common\Helper\Erp\Distributed\Room();
    		$reserve_id = Request::queryString("post.reserve_id");
    		$house_id = Request::queryString("post.house_id",0,"int");
    		$room_id = Request::queryString("post.room_id",0,"int");
    		if ($room_id>0)
    		{
    			$roomModel = new \Common\Model\Erp\Room();
    			$room_data = $roomModel->getOne(array('room_id'=>$room_id));
    			
    			//判断用户是否对当前对象有权限操作
    			if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $room_data['house_id'], SysHousingManagement::DECENTRALIZED_HOUSE)){
    				return $this->returnAjax(array('__status__'=>403));
    			}
    			
    			$id = $room_id;
    			$is_room = true;
    			$todoModel->deleteTodo($todoModel::MODEL_ROOM_RESERVE_OUT, $reserve_id);
    			$data['data']= $room_data;
    		}
    		if ($house_id>0)
    		{
    			$houseModel = new \Common\Model\Erp\House();
    			$one_house_data = $houseModel->getOne(array("house_id"=>$house_id));
    			//判断用户是否对当前对象有权限操作
    			if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $house_id, SysHousingManagement::DECENTRALIZED_HOUSE)){
    				return $this->returnAjax(array('__status__'=>403));
    			}
    			$id = $house_id;
    			$is_room = 0;
    			$todoModel->deleteTodo($todoModel::MODEL_ROOM_RESERVE_OUT, $reserve_id);
    			$data['data'] = $one_house_data;
    		}
    		$data['reserve_id'] = $reserve_id;
    		$data['id'] = $id;
    		$data['is_room'] = $is_room;
    		$result = $roomHelper->abolishRenewal($data);
    		$reserve_url = Url::parse('Finance-Serial/addexpense')."&us_source=unsubscribe"."&reserve_id=".$reserve_id;
    		if ($result)
    		{
    			$houseHelper = new \Common\Helper\Erp\House();
    			$reserveModel = new \Common\Model\Erp\Reserve();
    			$reserve_data = $reserveModel->getFirst(\Common\Model\Erp\Reserve::HOUSE_TYPE_R, $id,$is_room);
    			$house_data = $houseHelper->getEmptyData($id,$is_room);
    			return $this->returnAjax(array("status"=>1,
    										   "data"=>$result,
    										   "message"=>"退订成功",
    					  					   "house_data"=>$house_data,
    										   "reserve_data"=>$reserve_data[0],
    										   "reserve_url"=>$reserve_url));
    		}
    		return $this->returnAjax(array("status"=>0,"data"=>false,"message"=>"退订失败"));
    	}
    }
    /**
     * 获取房间详情
     * 修改时间2015年5月13日 13:52:37
     * 
     * @author yzx
     */
    public function roomdetailAction()
    {
    	if (!Request::isPost())
    	{
    		$user = $this->user;
    		$room_id = I("get.room_id",0,"int");
    		$roomModel = new \Common\Model\Erp\Room();
    		$sysConfig = new \Common\Model\Erp\SystemConfig();
    		$houseHelper = new \Common\Helper\Erp\House();
    		$attachmentModel = new Attachments();
    		$feeHelper = new \Common\Helper\Erp\Fee();
    		$houseModel = new \Common\Model\Erp\House();
    		$resverModel = new \Common\Model\Erp\Reserve();
    		$is_meter_reading = $roomModel->isMeterReading($room_id, $this->user);
    		$config = $sysConfig->getFind("House", "PublicConfig");
    		$public_facilities = $sysConfig->getFind("House", "public_facilities");
    		$room_data = $roomModel->getOne(array("room_id"=>$room_id));
    		$reser_back_model = new \Common\Model\Erp\ReserveBackRental();
    		$reserve_back_info = $reser_back_model->getOne(array('type' => 1, 'house_type' => 2, 'source_id' => $room_id, 'is_delete' => 0), array('reserve_back_id' => 'reserve_back_id'));
    		
    		//判断用户是否对当前对象有权限操作
    		if(!$this->verifyDataLinePermissions(Permissions::SELECT_AUTH_ACTION, $room_data['house_id'], SysHousingManagement::DECENTRALIZED_HOUSE)){
    			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("House-room/roomdetail")));
    		}
    		
    		$house_data = $houseModel->getOne(array("house_id"=>$room_data['house_id']));
    		$room_config = explode("-", $room_data['room_config']);
    		$imag = $attachmentModel->getImagList("room", $room_id);
    		$contract_id = $houseHelper->getHouseContract($room_data,true);
    		$house_fee_data = $feeHelper->getRoomFeeInfo($room_data['house_id'],$user['company_id'],\Common\Model\Erp\Fee::SOURCE_DISPERSE);
    		$resver_data = $resverModel->getOne(array("house_type"=>$resverModel::HOUSE_TYPE_R,"rental_way"=>$resverModel::RENTAL_WAY_H,"room_id"=>$room_id));
    		$pay_config_key = array(5);
    		$fee_show=0;
    		foreach ($house_fee_data as $hkey=>$hval){
    			if (in_array($hval['payment_mode'], $pay_config_key)){
    				$fee_show =1;
    			}
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
    		$room_type = array("main"=>"主卧","second"=>"次卧","guest"=>"客卧");
    		$house_data['house_name'] = $house_data['house_name'].$room_type[$room_data['room_type']].$room_data['custom_number']."号";
    		$this->assign("resver_data", $resver_data);
    		$this->assign("fee_show", $fee_show);
    		$this->assign("room_data", $room_data);
    		$this->assign("reserve_back_info", $reserve_back_info);
    		$this->assign("config", $config);
    		$this->assign("public_facilities", $config_data);
    		$this->assign("is_meter_reading", count($is_meter_reading));
    		$this->assign("imag", $imag);
    		$this->assign("contract_id", $contract_id);
    		$this->assign("house_data", $house_data);
    		$data = $this->fetch("House/Room/detail");
    		return $this->returnAjax(array( "status"=>1,
    				"tag_name"=>"分散式房间修改",
    				"model_name"=>"room_edit_one",
    				"model_js"=>"distributed_room_viewJs",
    				"model_href"=>Url::parse("House-Room/detail"),
    				"data"=>$data));
    	}else 
    	{
    		//判断用户是否对当前对象有权限操作
    		if (!$this->verifyModulePermissions(Permissions::UPDATE_AUTH_ACTION)){
    			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("House-House/edit")));
    		}
    		$roomHelper = new \Common\Helper\Erp\Room();
    		$roomModel = new \Common\Model\Erp\Room();
    		$room_id = I("post.room_id",0,"int");
    		$custom_number = I("post.custom_number","","string");
    		$room_type = I("post.room_type","","string");
    		$occupancy_number = I("post.occupancy_number",0,"int");
    		$area = I("post.area",0,"string");
    		$money = I("post.money");
    		$detain = I("post.detain",0,"int");
    		$pay = I("post.pay",0,"int");
    		$room_config = I("post.room_config",'',"string");
    		$image = I("post.image","","string");
    		$data['custom_number'] = $custom_number;
    		$data['room_type'] = $room_type;
    		$data['occupancy_number'] = $occupancy_number;
    		$data['area'] = $area;
    		$data['money'] = $money;
    		$data['detain'] = $detain;
    		$data['pay'] = $pay;
    		$data['room_config'] = $room_config;
    		$data['image'] = $image;
    		$room_data = $roomModel->getOne(array("room_id"=>$room_id),array("house_id"));
    		//判断用户是否对当前对象有权限操作
    		if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $room_data['house_id'], SysHousingManagement::DECENTRALIZED_HOUSE)){
    			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("House-room/roomdetail")));
    		}
    		
    		$result = $roomHelper->editOneRoom($data, $room_id);
    		if ($result)
    		{
    			return $this->returnAjax(array("status"=>1,"data"=>$result,"message"=>"修改成功",'p_url' => Url::parse("House-house/index")));
    		}
    		return $this->returnAjax(array("status"=>0,"data"=>false,"message"=>"修改失败"));
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
    	$room_id = I("get.room_id",0,"int");
    	$stopHouseModle = new \Common\Model\Erp\StopHouse();
    	$data = $stopHouseModle->getDataBySourceId($room_id, \Common\Model\Erp\StopHouse::DISPERSE_TYPE);
    	return $this->returnAjax(array("status"=>1,"data"=>$data));
    }
    /**
     * 获取预约退租信息
     * 修改时间2015年5月14日 10:38:59
     *
     * @author yzx
     */
    public function getyytzuAction() {
        $room_id = I("get.room_id", 0, "int");
        $room_model = new \Common\Model\Erp\Room();
        $room_num = $room_model->getOne(array('room_id' => $room_id,"is_delete"=>0), array('custom_number' => 'custom_number'));
        $reserve_back_model = new \Common\Model\Erp\ReserveBackRental();
        $reserve_data = $reserve_back_model->getOne(array('type' => $reserve_back_model::DISPERSE_TYPE,"house_type"=>$reserve_back_model::HOUSE_TYPE_ROOM,'source_id' => $room_id, 'is_delete' => 0), array('back_rental_time' => 'back_rental_time', 'remark' => 'remark'));
        $reserve_info = array_merge($reserve_data, $room_num);
        $reserve_info['back_rental_time'] = date('Y-m-d', $reserve_info['back_rental_time']);
        return $this->returnAjax(array("status"=>1,"data"=>$reserve_info));
    }
}