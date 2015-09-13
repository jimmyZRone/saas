<?php
namespace App\Web\Mvc\Controller;
use App\Web\Lib\Request;
use App\Web\Helper\Url;
class MeterreadingController extends \App\Web\Lib\Controller
{
	/**
	 * 抄表列表
	 * 修改时间2015年5月14日 19:22:09
	 * 
	 * @author yzx
	 */
	public function indexAction()
	{
		\Core\Session::delete("fee_type_map");
		$user = $this->user;
		$roomFocuecModel = new \Common\Model\Erp\RoomFocus();
		$flatModel = new \Common\Model\Erp\Flat();
		$feeHelper = new  \Common\Helper\Erp\Fee();
		$meterReadinHelper = new \Common\Helper\Erp\MeterReading();
		$houseEntirelModel = new \Common\Model\Erp\HouseEntirel();
		$houseModel = new \Common\Model\Erp\House();
		$roomModel = new \Common\Model\Erp\Room();
		$roomHelper = new \Common\Helper\Erp\Room();
		if (!Request::isPost())
		{
			$house_type = I("get.house_type",2,"int");
			$room_id = I("get.room_id",0,"int");
			$house_id = I("get.house_id",0,"int");
			if ($house_type == \Common\Model\Erp\MeterReading::HOUSE_TYPE_F)
			{
				//集中式房源
				$house_data = $roomFocuecModel->getOne(array("room_focus_id"=>$room_id));
				$flat_data = $flatModel->getOne(array("flat_id"=>$house_data['flat_id']));
				$name = $flat_data['flat_name'].$house_data['floor']."楼".$house_data['custom_number']."号";
			}elseif ($house_type == \Common\Model\Erp\MeterReading::HOUSE_TYPE_C){
				if ($house_id>0)
				{
					//分散式房源
					$house_entrel_data = $houseEntirelModel->getHouseData($house_id);
					$name = $house_entrel_data['house_name'];
				}else if($room_id>0){
					//分散式房间
					$room_data = $roomHelper->getRoomAndHouse($room_id);
					$name = ($room_data['house_name'])."-".$room_data['custom_number'];
				}
			}
			//获取集中式费用信息
			$fee_type = $feeHelper->getRoomFeeInfo($room_id, $user['company_id'],\Common\Model\Erp\Fee::SOURCE_FOCUS);
			//获取分散式费用信息
			if ($house_type == \Common\Model\Erp\MeterReading::HOUSE_TYPE_C){
				$fee_type = $feeHelper->getRoomFeeInfo($house_id,$user['company_id'], \Common\Model\Erp\Fee::SOURCE_DISPERSE);
				$house_fee_type = array();
				if ($house_id>0){
					$house_data_c = $houseModel->getOne(array("house_id"=>$house_id)); 
					if ($house_data_c['rental_way']==$houseModel::RENTAL_WAY_Z){
						$house_fee = array(5);
					}else {
						$house_fee = array(3,4);
					}
				}
				if ($room_id>0){
					$house_fee = array(5);
				}
				
				foreach ($fee_type as $key=>$val){
					if (in_array($val['payment_mode'], $house_fee)){
						$house_fee_type[$key] = $val;
					}
				}
				$fee_type = $house_fee_type;
			}
			//获取水电气费用项
			$out_fee_type_map = $meterReadinHelper->getFee($fee_type);
			if (empty($out_fee_type_map)){
				$room_focuec_data = $roomFocuecModel->getOne(array("room_focus_id"=>$room_id));
				$fee_type = $feeHelper->getRoomFeeInfo($room_focuec_data['flat_id'], $user['company_id'],\Common\Model\Erp\Fee::SOURCE_FLAT);
				$out_fee_type_map = $meterReadinHelper->getFee($fee_type);
			}
			//获取分散式水电气费用项
			if ($room_id>0 && $house_type == \Common\Model\Erp\MeterReading::HOUSE_TYPE_C)
			{
				$out_fee_type_map = $roomModel->isMeterReading($room_id, $this->user);
			}
			sort($out_fee_type_map);
			\Core\Session::save("fee_type_map", $out_fee_type_map);
			$this->assign("fee_type_data", $out_fee_type_map);
			$this->assign("house_name", $name);
			$data = $this->fetch("Meterreading/index");
			return $this->returnAjax(array( "status"=>1,
					"tag_name"=>"抄表",
					"model_name"=>"MeterReading_index",
					"model_js"=>"meterJs",
					"model_href"=>Url::parse("Meterreading/index"),
					"data"=>$data));
		}else 
		{
			$house_type = I("post.house_type",0,"int");
			$fee_type_id = I("post.meter_name",0,"int");
			$house_id = I("post.house_id",0,"int");
			$room_id = I("post.room_id",0,"int");
			$add_time = I("post.meter_time",'','string');
			$now_meter = I("post.meter_val",0,"float");
			$meter_price = I("post.meter_price");
			if ($house_id>0){
				$id = $house_id;
				$is_room = false;
			}
			if ($room_id>0){
				$id = $room_id;
				$is_room = true;
			}
			//查询抄表数据
			$list_data = $meterReadinHelper->getlistData($house_type, $id, 1, 10,$fee_type_id,$is_room);
			$before_meter = 0;
			if (!empty($list_data['data']))
			{
				$before_meter = $list_data['data'][0]['now_meter'];
				$new_add_time = strtotime($add_time);
				if ($before_meter>$now_meter){
					return $this->returnAjax(array("status"=>0,"data"=>"本次抄表度数不能小于上次抄表度数"));
				}
				//检查抄表时间是否正确
				foreach ($list_data['data'] as $lkey=>$lval){
					if ($new_add_time <= $lval['add_time']){
						return $this->returnAjax(array("status"=>0,"data"=>"本次抄表时间不能小于上次抄表时间"));
					}
				}
			}
			if ($now_meter<0){
				return $this->returnAjax(array("status"=>0,"data"=>"当前度数错误"));
			}
			$data['before_meter'] =$before_meter;
			$data['now_meter'] = $now_meter;
			$data['add_time'] = strtotime($add_time);
			$data['house_id'] = $house_id;
			$data['room_id'] = $room_id;
			$data['fee_type_id'] = $fee_type_id;
			$data['creat_user_id'] = $user['user_id'];
			$data['company_id'] = $user['company_id'];
			$data['meter_price'] = $meter_price;
			$result = $meterReadinHelper->addData($data, $house_type,$list_data);
			if ($result)
			{
				return $this->returnAjax(array("status"=>1,"data"=>"添加成功"));
			}
			return $this->returnAjax(array("status"=>0,"data"=>"添加失败")); 
		}
	}
	/**
	 * 获取列表数据
	 * 修改时间2015年5月14日 19:25:35
	 * 
	 * @author yzx
	 */
	public function listAction()
	{
		$house_type = I("get.house_type",2,"int");
		$fee_type_id = I("get.fee_type_id",0,"int");
		$house_id = I("get.house_id",0,"int");
		$room_id = I("get.room_id",0,"int");
		if ($house_id>0)
		{
			$id = $house_id;
			$is_room = false;
		}
		if ($room_id>0)
		{
			$id = $room_id;
			$is_room = true;
		}
		$page = I("get.page",1,"int");
		$meterReadinHelper = new \Common\Helper\Erp\MeterReading();
		$list_data = $meterReadinHelper->getlistData($house_type, $id, $page, 10,$fee_type_id,$is_room);
		if (!empty($list_data))
		{
			foreach ($list_data['data'] as $key=>$val)
			{
				$list_data['data'][$key]['add_time'] = date("Y-m-d",$val['add_time']);
			}
		}
		return $this->returnAjax(array("status"=>1,"data"=>$list_data['data'],"count"=>$list_data['page']['count'],"size"=>10,"page"=>$page));
	}
	/**
	 * 获取费用列表
	 * 修改时间2015年5月28日 11:04:48
	 * 
	 * @author yzx
	 */
	public function moneylistAction(){
		if (Request::isPost()){
			$meterReadinHelper = new \Common\Helper\Erp\MeterReading();
			$house_type = I("post.house_type",0,"int");
			$house_id = I("post.house_id",0,"int");
			$room_id = I("post.room_id",0,"int");
			$result = $meterReadinHelper->moneyList($room_id, $house_id, $house_type);
			return $this->returnAjax(array("status"=>1,"data"=>$result));
		}
		return $this->returnAjax(array("status"=>0,'data'=>false,"message"=>"获取失败"));
	}
	/**
	 * 获取最新一条数据
	 * 修改时间2015年6月8日20:27:11
	 * 
	 * @author yzx
	 */
	public function getbeforemeterAction(){
		$house_type = I("get.house_type",0,"int");
		$house_id = I("get.house_id",0,"int");
		$room_id = I("get.room_id",0,"int");
		$fee_type_id = I("get.fee_type_id",0,"int");
		$meterReadinHelper = new \Common\Helper\Erp\MeterReading();
		if ($house_id>0){
			$id = $house_id;
			$is_room = false;
		}
		if ($room_id>0){
			$id = $room_id;
			$is_room = true;
		}
		$list_data = $meterReadinHelper->getlistData($house_type, $id, 1, 10,$fee_type_id,$is_room);
		$before_meter = 0;
		if (!empty($list_data['data']))
		{
			$before_meter = $list_data['data'][0]['now_meter'];
			return $this->returnAjax(array("status"=>1,"data"=>$before_meter));
		}
		return $this->returnAjax(array("status"=>0,"data"=>false));
	}
}