<?php
namespace Common\Model\Erp;
class RoomNumber extends \Common\Model\Erp{
	public function addData($flatId,$room_number){
		$this->delete(array("flat_id"=>$flatId));
		$flatHelper = new \Common\Helper\Erp\Flat();
		$room_numb = \Core\Session::read($flatHelper->room_number_session_id);
		if (is_array($room_numb) && !empty($room_numb)){
			foreach ($room_numb as $key=>$val){
				$data['flat_id'] = $flatId;
				$data['room_number'] = $val['rooms_count'];
				$data['floor'] = $val['floor_num'];
				$this->insert($data);
			}
		}else {
			$house_numb = \Core\Session::read($flatHelper->house_number_sesssion_id);
			foreach ($house_numb as $key=>$val){
				$data['flat_id'] = $flatId;
				$data['room_number'] = $room_number;
				$data['floor'] = $key;
				$this->insert($data);
			}
		}
	}	
}