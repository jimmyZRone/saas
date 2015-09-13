<?php
namespace Common\Helper\Erp\Distributed;
use Common\Model\Erp\ReserveBackRental;
use Common\Model\Erp\HouseEntirel;
use Common\Model\Erp\StopHouse;
use App\Web\Helper\Url;
/**
 * 分散式合租
 * @author lishengyou
 * 最后修改时间 2015年3月30日 下午2:49:40
 *
 */
class Room implements \Common\Helper\Erp\Housing\Adaptation{
	/* (non-PHPdoc)
	 * @see \Common\Helper\Erp\Housing\Adaptation::appointmentrent()
	 */
	public function appointmentrent($data) {
		if (!empty($data))
		{
			$reserveBackRentalModel  = new ReserveBackRental();
			$roomModel = new \Common\Model\Erp\Room();
			$houseEntrirelModel = new  HouseEntirel();
			$houseModel = new \Common\Model\Erp\House();
			$message = new \Common\Model\Erp\Message();
			$data['type'] = ReserveBackRental::DISPERSE_TYPE;
			$reserveBackRentalModel->Transaction();
			$res_id = $reserveBackRentalModel->addData($data);
			if (!$res_id)
			{
				$reserveBackRentalModel->rollback();
				return false;
			}
			if ($data['house_type']== ReserveBackRental::HOUSE_TYPE_ROOM)
			{
			    $room_type = array('main' => '主卧', 'second' => '次卧', 'guest' => '客卧');
				$room_edit_res = $roomModel->edit(array("room_id"=>$data['source_id']), array("is_yytz"=>$roomModel::IS_YYTZ));
				$url = Url::parse("house-room/roomdetail/show/1/room_id/{$data['source_id']}");
				
				$room_data = $roomModel->getOne(array("room_id"=>$data['source_id']));
				$house_data = $houseModel->getOne(array("house_id"=>$room_data['house_id']));
    			
				$data['todo_title'] = $house_data['house_name'].$room_type[$room_data['room_type']].$room_data['custom_number']."号"."的预约退租时间将于".$data['back_rental_time']."过期,请注意处理。";
				$data['end_time'] = $data['deal_time'];
				$data['house_id'] = $room_data['house_id'];
				$this->todo($data, \Common\Model\Erp\Todo::MODEL_ROOM_RESERVE,"到期",$url);
				if (!$room_edit_res)
				{
					$reserveBackRentalModel->rollback();
					return false;
				}
				//发送系统消息
				$room_map = array("main"=>"主卧","second"=>"次卧","guest"=>'客卧');
				$message_data['to_user_id'] = $data['user_id'];
				$message_data['title'] = "房间预约退租";
				$message_data['content'] = "分散式".$house_data['house_name']."-".$room_data['custom_number']."的租客已经预约退租,预约退租时间".$data['deal_time'];
				$message_data['message_type'] = "system";
				$message->sendMessage($message_data);
				//写快照
				\Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_ROOM_RESERVE_BACK_RENTAL, $room_data['room_id'], $room_data);
			}
			if ($data['house_type'] == ReserveBackRental::HOUSE_TYPE_HOUSE)
			{
				$house_data = $houseModel->getOne(array("house_id"=>$data['source_id']));
				$house_res = $houseEntrirelModel->edit(array("house_id"=>$data['source_id']), array("is_yytz"=>\Common\Model\Erp\House::IS_YYTZ));
				$url = Url::parse("house-house/edit/show/1/house_id/{$data['source_id']}");
				
				$data['todo_title'] = $house_data['house_name']."的预约退租时间将于".$data['back_rental_time']."过期,请注意处理。";
				$data['end_time'] = $data['deal_time'];
				$data['house_id'] = $data['source_id'];
				$this->todo($data, \Common\Model\Erp\Todo::MODEL_HOUSE_RESERVE,"到期",$url);
				if (!$house_res)
				{
					$reserveBackRentalModel->rollback();
					return false;
				}
				
				//发送系统消息
				$message_data['to_user_id'] = $data['user_id'];
				$message_data['title'] = "房间预约退租";
				$message_data['content'] = $data['todo_title']."的租客已经预约退租,预约退租时间".$data['deal_time'];
				$message_data['message_type'] = "system";
				$message->sendMessage($message_data);
				
				//写快照
				\Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_HOUSE_RESERVE_BACK_RENTAL, $house_data['house_id'], $house_data);
			}
			$reserveBackRentalModel->commit();
			return $res_id;
		}		
	}

	/* (non-PHPdoc)
	 * @see \Common\Helper\Erp\Housing\Adaptation::disable()
	 */
	public function disable($data) {
		if (!empty($data))
		{
			$stopHouseModel = new StopHouse();
			$roomModel = new \Common\Model\Erp\Room();
			$houseModel = new \Common\Model\Erp\House();
			$houseEntrirelModel = new  HouseEntirel();
			$data['type'] = StopHouse::DISPERSE_TYPE;
			$stopHouseModel->Transaction();
			$stop_new_id = $stopHouseModel->add($data);
			if (!$stopHouseModel)
			{
				$stopHouseModel->rollback();
				return false;
			}
		if ($data['house_type']== ReserveBackRental::HOUSE_TYPE_ROOM)
			{
			    $room_type = array('main' => '主卧', 'second' => '次卧', 'guest' => '客卧');
				$room_edit_res = $roomModel->edit(array("room_id"=>$data['source_id']), array("status"=>\Common\Model\Erp\Room::STATUS_IS_STOP));
				$room_data = $roomModel->getOne(array("room_id"=>$data['source_id']));
				$house_data = $houseModel->getOne(array("house_id"=>$room_data['house_id']));
				
				$url = Url::parse("House-Room/roomdetail/show/1/room_id/{$data['source_id']}");
				$data['todo_title'] = $house_data['house_name'].$room_type[$room_data['room_type']].$room_data['custom_number']."号"."的停用状态将于".$data['end_time']."解除,请注意处理。";
				$data['house_id'] = $room_data['house_id'];
				$result = $this->todo($data,\Common\Model\Erp\Todo::MODEL_ROOM_STOP,"到期",$url);
				if (!$room_edit_res)
				{
					$stopHouseModel->rollback();
					return false;
				}
				
				//发送系统消息
				$message = new \Common\Model\Erp\Message();
				$message_data['to_user_id'] = $data['user_id'];
				$message_data['title'] = "房间停用";
				$str = $data["remark"]?"因".$data['remark']:'';
				$message_data['content'] =$house_data['house_name']."-".$room_data['custom_number'].
				$str."已停用,"."停用时间".$data['start_time']."至".$data['end_time'];
				$message_data['message_type'] = "system";
				$message->sendMessage($message_data);
			}
			if ($data['house_type'] == ReserveBackRental::HOUSE_TYPE_HOUSE)
			{
				$house_res = $houseEntrirelModel->edit(array("house_id"=>$data['source_id']), array("status"=>\Common\Model\Erp\House::STATUS_IS_STOP));
				$house_data = $houseModel->getOne(array("house_id"=>$data['source_id']));
				$data['todo_title'] = $house_data['house_name']."的停用状态将于".$data['end_time']."解除,请注意处理。";
				$data['house_id'] = $data['source_id'];
				$url = Url::parse("House-House/edit/show/1/house_id/{$data['source_id']}");
				$result = $this->todo($data,\Common\Model\Erp\Todo::MODEL_HOUSE_STOP,"到期",$url);
				if (!$house_res)
				{
					$stopHouseModel->rollback();
					return false;
				}
				//发送系统消息
				$message = new \Common\Model\Erp\Message();
				$message_data['to_user_id'] = $data['user_id'];
				$message_data['title'] = "房源停用";
				$str = $data["remark"]?"因".$data['remark']:'';
				$message_data['content'] =$house_data['house_name'].$str.
										  "已停用,"."停用时间".$data['start_time']."至".$data['end_time'];
				$message_data['message_type'] = "system";
				$message->sendMessage($message_data);
			}
			// 再写一个合同到期日程表
			if (!$result){
				$stopHouseModel->rollback();
			}
			$stopHouseModel->commit();
			return $stop_new_id;
		}
		return false;
		
	}

	/* (non-PHPdoc)
	 * @see \Common\Helper\Erp\Housing\Adaptation::recover()
	 */
	public function recover($data) {
		$roomModel = new \Common\Model\Erp\Room();
		$houseEntrirelModel = new  HouseEntirel();
		$todoModel = new \Common\Model\Erp\Todo();
		$houseModel = new \Common\Model\Erp\House();
		$message = new \Common\Model\Erp\Message();
		if (intval($data['house_id'])>0)
		{
			$houseEntrirelModel->Transaction();
			$house_result = $houseEntrirelModel->edit(array("house_id"=>$data['house_id']), array("status"=>\Common\Model\Erp\House::STATUS_NOT_RENTAL));
			$todoModel->deleteTodo($todoModel::MODEL_HOUSE_STOP, $data['house_id']);
			if (!$house_result)
			{
				$houseEntrirelModel->rollback();
				return false;
			}
			//发送系统消息
			$house_data = $houseModel->getOne(array("house_id"=>$data['house_id']));
			$message_data['to_user_id'] = $data['user_id'];
			$message_data['title'] = "房源已恢复";
			$message_data['content'] = $house_data['house_name'].
			"已经恢复,"."恢复时间".date("Y-m-d",time());
			$message_data['message_type'] = "system";
			$message->sendMessage($message_data);
			
			$houseEntrirelModel->commit();
			return true;
		}
		if (intval($data['room_id'])>0)
		{
			$roomModel->Transaction();
			$room_result = $roomModel->edit(array("room_id"=>$data['room_id']), array("status"=>\Common\Model\Erp\Room::STATUS_NOT_RENTAL));
			$todoModel->deleteTodo($todoModel::MODEL_ROOM_STOP, $data['room_id']);
			if (!$room_result)
			{
				$roomModel->rollback();
				return false;
			}
			//发送系统消息
			$room_data = $roomModel->getOne(array("room_id"=>$data['room_id']));
			$house_data = $houseModel->getOne(array("house_id"=>$room_data['house_id']));
			$message_data['to_user_id'] = $data['user_id'];
			$message_data['title'] = "房间已恢复";
			$message_data['content'] = $house_data['house_name']."-".$room_data['custom_number'].
			"已经恢复,"."恢复时间".date("Y-m-d",time());
			$message_data['message_type'] = "system";
			$message->sendMessage($message_data);
		}
		$roomModel->commit();
		return true;		
	}

	/* (non-PHPdoc)
	 * @see \Common\Helper\Erp\Housing\Adaptation::rental()
	 */
	public function rental($data) {
		$houserEntirelModel = new HouseEntirel();
		$roomModel = new \Common\Model\Erp\Room();
		
		if (intval($data['house_id'])>0)
		{
			$houserEntirelModel->Transaction();
			$house_res = $houserEntirelModel->edit(array("house_id"=>$data['house_id']), array("status"=>\Common\Model\Erp\House::STATUS_IS_RENTAL));
			if (!$house_res)
			{
				$houserEntirelModel->rollback();
				return false;
			}
			$houserEntirelModel->commit();
			return true;
		}
		if (intval($data['room_id'])>0)
		{
			$roomModel->Transaction();
			$room_res = $roomModel->edit(array("room_id"=>$data['room_id']), array("status"=>Room::STATIS_RENTAL));
			if (!$room_res)
			{
				$roomModel->rollback();
				return false;
			}
		}
		$roomModel->commit();
		return true;		
	}

	/* (non-PHPdoc)
	 * @see \Common\Helper\Erp\Housing\Adaptation::rentout()
	 */
	public function rentout($data) {
		$houseEntirelModel = new HouseEntirel();
		$roomModel = new \Common\Model\Erp\Room();
		$rentalHelper = new \Common\Helper\Erp\Rental();
		$houseModel = new \Common\Model\Erp\House();
		$message = new \Common\Model\Erp\Message();
		$meterReadingModel = new \Common\Model\Erp\MeterReading();
		$todoModel = new \Common\Model\Erp\Todo();
		$tenantContractHelper = new \Common\Helper\Erp\TenantContract();
		$houseHelper = new \Common\Helper\Erp\House();
		$houseEntirelModel->Transaction();
		if (intval($data['house_id'])>0)
		{
			$contract_data = $tenantContractHelper->getContractDataById($data['house_id'], \Common\Model\Erp\Rental::HOUSE_TYPE_R);
			$house_data = $houseModel->getOne(array("house_id"=>$data['house_id']));
			$house_res = $rentalHelper->rentalOut($data['house_id'], \Common\Model\Erp\Rental::HOUSE_TYPE_R);
			$contract_id = $houseHelper->getHouseContract($data);
			//修改房间状态
			if (count($contract_data)==1){
				$house_res = $houseEntirelModel->edit(array("house_id"=>$data['house_id']), array("status"=>\Common\Model\Erp\House::STATUS_NOT_RENTAL,"is_yytz"=>0));
			}
			//删除日志
			$todoModel->deleteTodo($todoModel::MODEL_HOUSE_RESERVE, $data['house_id']);
			if (!empty($contract_data)){
				//$todoModel->deleteTodo($todoModel::MODEL_TENANT_CONTRACT, $contract_id);
				$todoModel->deleteTodo($todoModel::MODEL_TENANT_CONTRACT_SHOUZHU, $contract_id);
			}
			//删除抄表数据
			$meterReadingModel->deleteByRentalOut($meterReadingModel::HOUSE_TYPE_C,$data['house_id']);
			if (!$house_res)
			{
				$houseEntirelModel->rollback();
				return false;
			}
			//发送系统消息
			$message_data['to_user_id'] = $data['user_id'];
			$message_data['title'] = "房源已退租";
			$message_data['content'] =$house_data['house_name'].
			"已经退租,"."退租时间".date("Y-m-d",time());
			$message_data['message_type'] = "system";
			$message->sendMessage($message_data);
			
			//写快照
			\Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_HOUSE_RENTAL_BACK, $data['house_id'], $house_data);
		}
		if (intval($data['room_id'])>0)
		{
			$contract_data = $tenantContractHelper->getContractDataById($data['room_id'], \Common\Model\Erp\Rental::HOUSE_TYPE_R,true);
			//删除抄表数据
			$meterReadingModel->deleteByRentalOut($meterReadingModel::HOUSE_TYPE_C,0,$data['room_id']);
			$contract_id = $houseHelper->getHouseContract($data,true);
			//退租
			$room_res = $rentalHelper->rentalOut($data['room_id'], \Common\Model\Erp\Rental::HOUSE_TYPE_R,true);
			//修改房间状态
			if (count($contract_data)<=1){
				$room_res = $roomModel->edit(array("room_id"=>$data['room_id']), array("status"=>\Common\Model\Erp\Room::STATUS_NOT_RENTAL,"is_yytz"=>0));
			}
			//删除日程
			$todoModel->deleteTodo($todoModel::MODEL_ROOM_RESERVE, $data['room_id']);
			if (!empty($contract_data)){
					//$todoModel->deleteTodo($todoModel::MODEL_TENANT_CONTRACT, $contract_id);
					$todoModel->deleteTodo($todoModel::MODEL_TENANT_CONTRACT_SHOUZHU, $contract_id);
			}
			if (!$room_res)
			{
				$houseEntirelModel->rollback();
				return false;
			}
			$room_data = $roomModel->getOne(array("room_id"=>$data['room_id'],"is_delete"=>0));
			$house_data = $houseModel->getOne(array("house_id"=>$room_data['house_id'],"is_delete"=>0));
			//发送系统消息
			$message_data['to_user_id'] = $data['user_id'];
			$message_data['title'] = "房间已退租";
			$message_data['content'] =$house_data['house_name'].$room_data['custom_number'].
			"已经退租,"."退租时间".date("Y-m-d",time());
			$message_data['message_type'] = "system";
			$message->sendMessage($message_data);
			
			//写快照
			\Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_ROOM_RENTAL_BACK, $data['room_id'], $room_data,"house",$room_data['house_id']);
		}
		$houseEntirelModel->commit();
		return true;
	}

	/* (non-PHPdoc)
	 * @see \Common\Helper\Erp\Housing\Adaptation::revocationSubscribe()
	 */
	public function revocationSubscribe($data) {
		$roomModel = new \Common\Model\Erp\Room();
		$houseEntirelModel = new HouseEntirel();
		$reserveBackRental = new ReserveBackRental();
		$todoModel = new  \Common\Model\Erp\Todo();
		$houseModel = new  \Common\Model\Erp\House();
		$data['house_type'] = \Common\Model\Erp\Reserve::HOUSE_TYPE;
		//取消房源预约退租
		if (intval($data['house_id'])>0)
		{
			$houseEntirelModel->Transaction();
			$result = $houseEntirelModel->edit(array("house_id"=>$data['house_id']), array("is_yytz"=>\Common\Model\Erp\House::NOT_IS_YYTZ));
			$reserve_res = $reserveBackRental->getDataBySourceId($data['house_id'], ReserveBackRental::DISPERSE_TYPE,ReserveBackRental::HOUSE_TYPE_HOUSE);
			$todoModel->deleteTodo($todoModel::MODEL_HOUSE_RESERVE, $data['house_id']);
			if (!empty($reserve_res[0]))
			{
				$reserveBackRental->edit(array("reserve_back_id"=>$reserve_res[0]['reserve_back_id']), array("is_delete"=>1));
			}
			if (!$result)
			{
				$houseEntirelModel->rollback();
				return false;
			}
			$house_data = $houseModel->getOne(array("house_id"=>$data['house_id']));
			//发送系统消息
			$message = new \Common\Model\Erp\Message();
			$message_data['to_user_id'] = $data['user_id'];
			$message_data['title'] = "撤销预约退租";
			$message_data['content'] = $house_data['house_name']."已撤销预约退租,撤销时间".date("Y-m-d",time());
			$message_data['message_type'] = "system";
			$message->sendMessage($message_data);
			$houseEntirelModel->commit();
			
			//写快照
			\Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_HOUSE_RECOVER_BACK_RENTAL, $data['house_id'], $house_data);
			return true;
		}
		//取消房间预约退租
		if (intval($data['room_id'])>0)
		{
			$room_data = $roomModel->getOne(array("room_id"=>$data['room_id']));
			$house_data = $houseModel->getOne(array("house_id"=>$room_data['house_id']));
			$roomModel->Transaction();
			$room_result = $roomModel->edit(array("room_id"=>$data['room_id']), array("is_yytz"=>$roomModel::NOT_IS_YYTZ));
			$reserve_res = $reserveBackRental->getDataBySourceId($data['room_id'], ReserveBackRental::DISPERSE_TYPE,ReserveBackRental::HOUSE_TYPE_ROOM);
			$todoModel->deleteTodo($todoModel::MODEL_ROOM_RESERVE, $data['room_id']);
			if (!empty($reserve_res[0]))
			{
				$reserveBackRental->edit(array("reserve_back_id"=>$reserve_res[0]['reserve_back_id']), array("is_delete"=>1));
			}
			if (!$room_result)
			{
				$roomModel->rollback();
				return false;
			}
			//发送系统消息
			$message = new \Common\Model\Erp\Message();
			$message_data['to_user_id'] = $data['user_id'];
			$message_data['title'] = "撤销预约退租";
			$message_data['content'] = $house_data['house_name']."-".$room_data['custom_number']."已撤销预约退租,撤销时间".date("Y-m-d",time());
			$message_data['message_type'] = "system";
			$message->sendMessage($message_data);
			$roomModel->commit();
			
			//写快照
			\Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_ROOM_RECOVER_BACK_RENTAL, $data['room_id'], $room_data);
			return true;
		}
	}

	/* (non-PHPdoc)
	 * @see \Common\Helper\Erp\Housing\Adaptation::schedule()
	 */
	public function schedule($data) {
		$tenantModel = new \Common\Model\Erp\Tenant();
		$tenantHelper = new  \Common\Helper\Erp\Tenant();
		$reserveHelper = new \Common\Helper\Erp\Reserve();
		$roomModel = new \Common\Model\Erp\Room();
		$houseEntirelModel = new \Common\Model\Erp\HouseEntirel();
		$houseModel = new \Common\Model\Erp\House();
		$tenant_result = $tenantHelper->getTenant($data['phone'], $data['idcard']);
		$tenantModel->Transaction();
		if (empty($tenant_result))
		{
			$tenant_data = array();
			$gender = intval(substr($data['idcard'], strlen($data['idcard'])-1,0))%2==0?1:2;
			$tenant_data['phone'] = $data['phone'];
			$tenant_data['gender'] = $gender;
			$tenant_data['idcard'] = $data['idcard'];
			$tenant_data['name'] = $data['name'];
			$tenant_data['company_id'] = $data['company_id'];
			$tenant_id = $tenantModel->addTenant($tenant_data);
			$data['tenant_id'] = $tenant_id;
			if (!$tenant_id)
			{
				$tenantModel->rollback();
				return false;
			}
		}else 
		{
			$data['tenant_id'] = $tenant_result['tenant_id'];
		}
		if ($data['room_id']>0 && $data['house_id']>0){
			$data['rental_way'] = \Common\Model\Erp\Reserve::RENTAL_WAY_H;
		}
		if ($data['house_id']>0 && $data['room_id']<=0){
			$data['rental_way'] = \Common\Model\Erp\Reserve::RENTAL_WAY_Z;
		}
		$data['house_type'] = \Common\Model\Erp\Reserve::HOUSE_TYPE_R;
		$result = $reserveHelper->add($data);
		if ($result)
		{
			
			if ($data['room_id']>0)
			{
			    $room_type = array('main' => '主卧', 'second' => '次卧', 'guest' => '客卧');
				$room_data = $roomModel->getOne(array("room_id"=>$data['room_id']));
				$house_data = $houseModel->getOne(array("house_id"=>$room_data['house_id']));
				$roomModel->edit(array("room_id"=>$data['room_id']), array("is_yd"=>$roomModel::IS_YD));
				$data['end_time'] = $data['etime'];
				$data['source_id'] = $result;
				$data['todo_title'] = $house_data['house_name'].$room_type[$room_data['room_type']].$room_data['custom_number']."号"."的预定时间将于".$data['etime']."到期,请注意处理。";
				$message_data['title'] = "房间预定";
				$message_data['content'] = $house_data['house_name']."-".$room_data['custom_number'].$roomModel::$room_type[$room_data['room_type']]."已预定,预定时间".$data['stime']."至".$data['etime'];
				$url = Url::parse("tenant-index/reserve/reserve_id/{$result}");
				$this->todo($data, \Common\Model\Erp\Todo::MODEL_ROOM_RESERVE_OUT, "到期", $url);
				
				//写快照
				\Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_ROOM_RESVER, $data['room_id'], $room_data,"resver",$result);
			}
			if ($data['house_id']>0 && $data['room_id']<=0)
			{
				$houseEntirelModel->edit(array('house_id'=>$data['house_id']), array("is_yd"=>\Common\Model\Erp\House::IS_YD));
				$house_data = $houseModel->getOne(array("house_id"=>$data['house_id']));
				$data['end_time'] = $data['etime'];
				$data['source_id'] = $result;
				$data['todo_title'] = $house_data['house_name']."的预定时间将于".$data['etime']."到期,请注意处理。";
				
				$message_data['title'] = "房源预定";
				$message_data['content'] = $house_data['house_name']."已预定,预定时间".$data['stime']."至".$data['etime'];
				
				$url = Url::parse("tenant-index/reserve/reserve_id/{$result}");
				$this->todo($data, \Common\Model\Erp\Todo::MODEL_HOUSE_RESERVE_OUT, "到期", $url);
				
				//写快照
				\Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_HOUSE_RESVER, $data['house_id'], $house_data,"resver",$result);
			}
			//发送系统消息
			$message = new \Common\Model\Erp\Message();
			$message_data['to_user_id'] = $data['user_id'];
			$message_data['message_type'] = "system";
			$message->sendMessage($message_data);
			
			$tenantModel->commit();
			return $result;
		}
		return false;
		
	}
	/**
	 * (non-PHPdoc)
	 * @see \Common\Helper\Erp\Housing\Adaptation::abolishRenewal()
	 */
	public function abolishRenewal($data) {
		$reserveModel = new \Common\Model\Erp\Reserve();
		$reserveHelper = new \Common\Helper\Erp\Reserve();
		$roomModel = new \Common\Model\Erp\Room();
		$houseModel = new \Common\Model\Erp\HouseEntirel();
		$todoModel = new \Common\Model\Erp\Todo();
		$reserve_id = explode(',', $data['reserve_id']);
		if (is_array($reserve_id) && !empty($reserve_id))
		{
			foreach ($reserve_id as $key=>$val)
			{
				$result = $reserveModel->edit(array("reserve_id"=>$val), array("is_delete"=>1));
				if (!$result)
				{
					return false;
				}
				if ($data['is_room']){
					$todoModel->deleteTodo($todoModel::MODEL_ROOM_FOCUS_RESERVE_OUT, $val);
				}else {
					$todoModel->deleteTodo($todoModel::MODEL_HOUSE_RESERVE_OUT, $val);
				}
			}
			$reserver_data = $reserveHelper->getDataByCondition(\Common\Model\Erp\Reserve::HOUSE_TYPE_R,$data['id'],$data['is_room']);
			if ($data['is_room']){
				//写快照
				\Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_ROOM_DELETE_RESVER, $data['data']['room_id'], $data['data']);
			}else {
				//写快照
				\Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_HOUSE_DELETE_RESVER, $data['data']['house_id'], $data['data']);
			}
			if (empty($reserver_data) && $data['is_room'])
			{
				$roomModel->edit(array("room_id"=>$data['id']), array("is_yd"=>0));
			}else 
			{
				$houseModel->edit(array("house_id"=>$data['id']), array("is_yd"=>0));
			}
			return true ;
		}
		return false;
	}
	/* (non-PHPdoc)
	 * @see \Common\Helper\Erp\Housing\Adaptation::renewal()
	*/
	public function renewal($data) {
	
	}
	/**
	 * 添加日常
	 * 修改时间2015年6月13日10:11:25
	 * 
	 * @author yzx
	 * @param unknown $data
	 */
	public function todo($data,$model,$title,$url){
		$todoModel = new \Common\Model\Erp\Todo();
		$dataTodo['module'] =$model;
		$dataTodo['entity_id'] = $data['source_id'];
		$dataTodo['title'] = $title;
		$dataTodo['content'] = $data['todo_title'];
		$dataTodo['company_id'] =  $data['company_id'];
		$dataTodo['url'] = $url;
		$dataTodo['status'] = 0;
		$dataTodo['deal_time'] = strtotime($data['end_time']);
		$dataTodo['create_time'] = $_SERVER['REQUEST_TIME'];
		$dataTodo['create_uid'] = $data['user_id'];
		$dataTodo['house_id'] = $data['house_id'];
		$result = $todoModel->addTodo($dataTodo);
		if ($result){
			return true;
		}
		return false;
	}
}