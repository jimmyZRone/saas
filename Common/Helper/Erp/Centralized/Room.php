<?php
namespace Common\Helper\Erp\Centralized;
use Common\Helper\Erp\Housing\Adaptation;
use App\Web\Helper\Url;
/**
 * 集中式房源
 * @author lishengyou
 * 最后修改时间 2015年3月30日 下午2:49:40
 *
 */
//TODO 实现接口
class Room implements Adaptation{
	/**
	 * (non-PHPdoc)
	 * @see \Common\Helper\Erp\Housing\Adaptation::appointmentrent()
	 */
	public function appointmentrent($data)
	{
		$reserveBackRentalModel = new \Common\Model\Erp\ReserveBackRental();
		$roomFocusModel = new  \Common\Model\Erp\RoomFocus();
		$flatModel = new \Common\Model\Erp\Flat();
		if (!empty($data))
		{
				$data['type'] = \Common\Model\Erp\ReserveBackRental::CENTRALIZATION_TYPE;
				$data['creat_time'] = time();
				$roomFocusModel->Transaction();
				$roomFocusData = $roomFocusModel->getOne(array("room_focus_id"=>$data['source_id']));
				$result_room_focus = $roomFocusModel->edit(array("room_focus_id"=>$data['source_id']), array("is_yytz" => \Common\Model\Erp\RoomFocus::IS_YYTZ));
				$flatData =  $flatModel->getOne(array("flat_id"=>$roomFocusData['flat_id']));
				$data['end_time'] = $data['back_rental_time'];
				
				$url = Url::parse("centralized-roomfocus/edit/show/1/room_focus_id/{$data['source_id']}");
				$data['todo_title'] = $flatData['flat_name'].$roomFocusData['floor']."楼".$roomFocusData['custom_number']."号"."的预约退租时间将于".$data['back_rental_time']."过期,请注意处理。";
				$data['flat_id'] = $roomFocusData['flat_id'];
				$result_todo = $this->todo($data, \Common\Model\Erp\Todo::MODEL_ROOM_FOCUS_RESERVE, "到期", $url);
				if (!$result_room_focus)
				{
					$roomFocusModel->rollback();
					return false;
				}
				$new_back_id = $reserveBackRentalModel->addData($data);
				if (!$new_back_id)
				{
					$roomFocusModel->rollback();
					return false;
				}
				//发送系统消息
				$message = new \Common\Model\Erp\Message();
				$message_data['to_user_id'] = $data['user_id'];
				$message_data['title'] = "房间预约退租";
				$message_data['content'] = "集中式".$flatData['flat_name']."_".$roomFocusData['custom_number']."的租客已经预约退租,预约退租时间".$data['end_time'];
				$message_data['message_type'] = "system";
				$message->sendMessage($message_data);
				$roomFocusModel->commit();
				
				//写快照
				\Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_FOCUS_RESERVE_BACK_RENTAL, $data['source_id'], $roomFocusData);
				
				return $new_back_id;
		}
		return false;
	}
	/* (non-PHPdoc)
	 * @see \Common\Helper\Erp\Housing\Adaptation::disable()
	 */
	public function disable($data) {
		$stopHouseModel = new \Common\Model\Erp\StopHouse();
		$roomFocusModel = new  \Common\Model\Erp\RoomFocus();
		$flatModel = new \Common\Model\Erp\Flat();
		if (!empty($data))
		{
			$room_focus_data = $roomFocusModel->getOne(array("room_focus_id"=>$data['source_id']));
			$flat_data = $flatModel->getOne(array("flat_id"=>$room_focus_data['flat_id']));
			if (!empty($room_focus_data))
			{
				$roomFocusModel->Transaction();
				$edit_res = $roomFocusModel->edit(array("room_focus_id"=>$data['source_id']), array("status" => \Common\Model\Erp\RoomFocus::STATUS_IS_STOP));
				if (!$edit_res)
				{
					return false;
					$roomFocusModel->rollback();
				}
				$data['type'] = \Common\Model\Erp\StopHouse::CENTRALIZATION_TYPE;
				$stop_result = $stopHouseModel->add($data);
				if (!$stop_result)
				{
					return false;
					$roomFocusModel->rollback();
				}
				$data['deal_time'] = strtotime($data['end_time']);
				$url = Url::parse("Centralized-Roomfocus/edit/show/1/room_focus_id/{$data['source_id']}");
				$data['todo_title'] = $flat_data['flat_name'].$room_focus_data['floor']."楼".$room_focus_data['custom_number']."号"."的停用状态将于".$data['end_time']."解除,请注意处理。";
				$data['flat_id'] = $room_focus_data['flat_id'];
				$result_todo = $this->todo($data, \Common\Model\Erp\Todo::MODEL_ROOM_FOCUS_STOP, "到期", $url);
				if ($result_todo){
					//发送系统消息
					$message = new \Common\Model\Erp\Message();
					$message_data['to_user_id'] = $data['user_id'];
					$message_data['title'] = "房间停用";
					$str = $data['remark']?"因".$data['remark']:'';
					$message_data['content'] ="公寓".$flat_data['flat_name']."_".$room_focus_data['custom_number'].
											  $str."已停用,"."停用时间".$data['start_time']."至".$data['end_time'];
					$message_data['message_type'] = "system";
					$message->sendMessage($message_data);
				}
				$roomFocusModel->commit();
				return true;
			}
			return false;
		}
		return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see \Common\Helper\Erp\Housing\Adaptation::rentout()
	 */
	public function rentout($data) {
		$roomFocusModel = new \Common\Model\Erp\RoomFocus();
		$rentalHelper = new \Common\Helper\Erp\Rental();
		$flatModle = new \Common\Model\Erp\Flat();
		
		$tenantComtractHelper = new \Common\Helper\Erp\TenantContract();
		$todo_model = new \Common\Model\Erp\Todo();
		$meterReadingModel = new \Common\Model\Erp\MeterReading();
		$roomFocusHelper = new \Common\Helper\Erp\RoomFocus();
		//退租后,删除待办事项
	    $contract_data = $tenantComtractHelper->getContractDataById($data['room_id'], \Common\Model\Erp\Rental::HOUSE_TYPE_F);
		$roomFocusModel->Transaction();
		$room_data = $roomFocusModel->getOne(array("room_focus_id"=>$data['room_id']));
		$contract_id = $roomFocusHelper->getFocusRoomContract($data);
		if (!empty($data))
		{
			$result = $rentalHelper->rentalOut($data['room_id'], \Common\Model\Erp\Rental::HOUSE_TYPE_F);
			if (count($contract_data)==1){
				$result = $roomFocusModel->edit(array("room_focus_id"=>$data['room_id']), array("status"=>\Common\Model\Erp\RoomFocus::STATUS_NOT_RENTAL,"is_yytz"=>\Common\Model\Erp\RoomFocus::NOT_IS_YYTZ));
			}
			
			if (!$result)
			{
				$roomFocusModel->rollback();
				return false;
			}			
		}
		//删除备忘
		if (!empty($contract_data)){
			$todo_model->deleteTodo($todo_model::MODEL_TENANT_CONTRACT, $contract_id);
			$todo_model->deleteTodo($todo_model::MODEL_TENANT_CONTRACT_SHOUZHU, $contract_id);
		}
		$todo_model->deleteTodo($todo_model::MODEL_ROOM_FOCUS_RESERVE, $data['room_id']);
		
		$flat_data = $flatModle->getOne(array("flat_id"=>$room_data['flat_id']));
		$meterReadingModel->deleteByRentalOut($meterReadingModel::HOUSE_TYPE_F,0,$data['room_id']);
		//发送系统消息
		$message = new \Common\Model\Erp\Message();
		$message_data['to_user_id'] = $data['user_id'];
		$message_data['title'] = "房间已退租";
		$message_data['content'] ="公寓".$flat_data['flat_name']."_".$room_data['custom_number'].
								  "已经退租,"."退租时间".date("Y-m-d",time());
		$message_data['message_type'] = "system";
		$message->sendMessage($message_data);
		$roomFocusModel->commit();
		
		//写快照
		\Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_FOCUS_RENTAL_BACK, $data['room_id'], $room_data);
		
		return $result;
	}

	/* (non-PHPdoc)
	 * @see \Common\Helper\Erp\Housing\Adaptation::recover()
	 */
	public function recover($data) {
		$roomFocusModel = new \Common\Model\Erp\RoomFocus();
		$stopHouseModel = new \Common\Model\Erp\StopHouse();
		$todoModel = new \Common\Model\Erp\Todo();
		$flatModle = new \Common\Model\Erp\Flat();
		if (!empty($data))
			{
				$roomFocusModel->Transaction();
				$house_result = $roomFocusModel->edit(array("room_focus_id"=>$data['room_id']), array("status"=>\Common\Model\Erp\RoomFocus::STATUS_NOT_RENTAL));
				$stopHouseModel->edit(array("type"=>$stopHouseModel::CENTRALIZATION_TYPE,"source_id"=>$data['room_id']), array("is_delete"=>1));
				$todoModel->deleteTodo($todoModel::MODEL_ROOM_FOCUS_STOP, $data['room_id']);
				if (!$house_result)
				{
					$roomFocusModel->rollback();
					return false;
				}
				$room_data = $roomFocusModel->getOne(array("room_focus_id"=>$data['room_id']));
				$flat_data = $flatModle->getOne(array("flat_id"=>$room_data['flat_id']));
				//发送系统消息
				$message = new \Common\Model\Erp\Message();
				$message_data['to_user_id'] = $data['user_id'];
				$message_data['title'] = "房间已恢复";
				$message_data['content'] = "公寓".$flat_data['flat_name']."_".$room_data['custom_number'].
								  "已经恢复,"."恢复时间".date("Y-m-d",time());
				$message_data['message_type'] = "system";
				$message->sendMessage($message_data);
				$roomFocusModel->commit();
				return true;
			}
	}

	/* (non-PHPdoc)
	 * @see \Common\Helper\Erp\Housing\Adaptation::rental()
	 */
	public function rental($data) {
		$roomFocusModel = new \Common\Model\Erp\RoomFocus();
		if (intval($data['house_id'])>0)
		{
			$roomFocusModel->Transaction();
			$house_res = $roomFocusModel->edit(array("room_focus_id"=>$data['house_id']), array("status"=>\Common\Model\Erp\RoomFocus::STATUS_NOT_RENTAL));
			if (!$house_res)
			{
				$roomFocusModel->rollback();
				return false;
			}
			$roomFocusModel->commit();
			return true;
		}
	}

	/* (non-PHPdoc)
	 * @see \Common\Helper\Erp\Housing\Adaptation::revocationSubscribe()
	 */
	public function revocationSubscribe($data) {
		$roomFocusModel = new \Common\Model\Erp\RoomFocus();
		$reserveBackRental = new  \Common\Model\Erp\ReserveBackRental();
		$todoModel = new \Common\Model\Erp\Todo();
		$flatModle = new \Common\Model\Erp\Flat();
		if (!empty($data))
		{
			$room_data = $roomFocusModel->getOne(array("room_focus_id"=>$data['room_id']));
			
			//写快照
			\Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_FOCUS_RECOVER_BACK_RENTAL, $data['room_id'], $room_data);
			
			$result = $roomFocusModel->edit(array("room_focus_id"=>$data['room_id']), array("is_yytz"=>\Common\Model\Erp\RoomFocus::NOT_IS_YYTZ));
			$todoModel->deleteTodo($todoModel::MODEL_ROOM_FOCUS_RESERVE, $data['room_id']);
			if ($result)
			{
				$result = $reserveBackRental->getDataBySourceId($data['room_id'], \Common\Model\Erp\ReserveBackRental::CENTRALIZATION_TYPE);
				$reserveBackRental->edit(array("source_id"=>$data['room_id']), array("is_delete"=>1));
				
				$flat_data = $flatModle->getOne(array("flat_id"=>$room_data['flat_id']));
				
				//发送系统消息
				$message = new \Common\Model\Erp\Message();
				$message_data['to_user_id'] = $data['user_id'];
				$message_data['title'] = "撤销预约退租";
				$message_data['content'] = "公寓".$flat_data['flat_name']."-".$room_data['custom_number']."已撤销预约退租,撤销时间".date("Y-m-d",time());
				$message_data['message_type'] = "system";
				$message->sendMessage($message_data);
			}
			return true;
		}
		return false;
	}

	/* (non-PHPdoc)
	 * @see \Common\Helper\Erp\Housing\Adaptation::schedule()
	 */
	public function schedule($data) {
		$tenantHelper = new  \Common\Helper\Erp\Tenant();
		$reserveHelper = new \Common\Helper\Erp\Reserve();
		$tenantModel = new \Common\Model\Erp\Tenant();
		$roomFocusModel = new \Common\Model\Erp\RoomFocus();
		$flatModel = new \Common\Model\Erp\Flat();
		$tenant_result = $tenantHelper->getTenant($data['company_id'], $data['idcard']);
		$tenantModel->Transaction();
		if (empty($tenant_result))
		{
			//没有搜索到租客就添加租客
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
		$data['house_type'] = \Common\Model\Erp\Reserve::HOUSE_TYPE_F;
		$data['rental_way'] = \Common\Model\Erp\Reserve::HOUSE_TYPE_R;
		$result = $reserveHelper->add($data);
		$data['source_id'] = $result;
		$data['end_time'] = $data['etime'];

		$url = Url::parse("tenant-index/reserve/reserve_id/{$result}");
		$room_focus_data = $roomFocusModel->getOne(array("room_focus_id"=>$data['room_id']));
		$flat_data = $flatModel->getOne(array("flat_id"=>$room_focus_data["flat_id"]));
		$data['todo_title'] = $flat_data['flat_name'].$room_focus_data['floor']."楼".$room_focus_data['custom_number']."号"."的预定时间将于".$data['etime']."到期,请注意处理。";
		$data['flat_id'] = $room_focus_data["flat_id"];
		$result_todo = $this->todo($data, \Common\Model\Erp\Todo::MODEL_ROOM_FOCUS_RESERVE_OUT, "到期", $url);
		if ($result_todo){
			//发送系统消息
			$message = new \Common\Model\Erp\Message();
			$message_data['to_user_id'] = $data['user_id'];
			$message_data['title'] = "房间预定";
			$message_data['content'] = "公寓".$flat_data['flat_name']."-".$room_focus_data['floor']."楼".$room_focus_data['custom_number']."号"."已预定,预定时间".$data['stime']."至".$data['etime'];
			$message_data['message_type'] = "system";
			$message->sendMessage($message_data);
		}
		if ($result)
		{
			$focus_data = $roomFocusModel->getOne(array("room_focus_id"=>$data['room_id']));
			$roomFocusModel->edit(array('room_focus_id'=>$data['room_id']), array("is_yd"=>\Common\Model\Erp\RoomFocus::IS_YYTZ));
			$tenantModel->commit();
			
			//写快照
			\Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_FOCUS_RESVER, $data['room_id'], $focus_data);
			
			return $result;
		}	
		return false;
	}
	/* (non-PHPdoc)
	 * @see \Common\Helper\Erp\Housing\Adaptation::abolishRenewal()
	*/
	public function abolishRenewal($data) {
		$reserveModel = new  \Common\Model\Erp\Reserve();
		$roomFocusModel = new \Common\Model\Erp\RoomFocus();
		$reserveHelper = new \Common\Helper\Erp\Reserve();
		$todoModel = new \Common\Model\Erp\Todo();
		if (is_array($data['reserve_id']) && !empty($data['reserve_id']))
		{
			foreach ($data['reserve_id'] as $key=>$val)
			{
				$reserveModel->edit(array("reserve_id"=>$val), array("is_delete"=>1));
				$todoModel->deleteTodo($todoModel::MODEL_ROOM_FOCUS_RESERVE_OUT, $val);
			}
			$reserver_data = $reserveHelper->getDataByCondition(\Common\Model\Erp\Reserve::HOUSE_TYPE_F,$data['house_id']);
			$focus_data = $roomFocusModel->getOne(array("room_focus_id"=>$data['house_id']));
			
			//写快照
			\Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_FOCUS_DELETE_RESVER, $data['house_id'], $focus_data);
			
			if (empty($reserver_data))
			{
				$roomFocusModel->edit(array("room_focus_id"=>$data['house_id']), array("is_yd"=>0));
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
		$dataTodo['flat_id'] = $data['flat_id'];
		$result = $todoModel->addTodo($dataTodo);
		if ($result){
			return true;
		}
		return false;
	}

}