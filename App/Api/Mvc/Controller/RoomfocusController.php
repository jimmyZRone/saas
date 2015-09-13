<?php

namespace App\Api\Mvc\Controller;

class RoomfocusController extends \App\Api\Lib\Controller {
	/**
	 * 获取集中式房间信息
	 * 
	 * @author yusj | 最后修改时间 2015年5月8日下午3:38:24
	 */
	public function getRoomFocusInfoAction() {
		$roomFocusModel = new \Common\Helper\Erp\RoomFocus ();
		$token = I ( "token", '' );
		$room_focus_id = I ( "room_focus_id", '' );
		if (! is_numeric ( $room_focus_id ) && !is_int ( $room_focus_id )) {
			return_error ( 131, '集中式房间只能为整数' );
		}
		$data = $roomFocusModel->getData ( $room_focus_id, array (
				'room_focus_id',
				'custom_number',
				'status',
				'room_type',
				'area',
				'money',
				'detain',
				'pay',
				'room_config' 
		) );
		$serialNumber = new \App\Api\Helper\SerialNumber ();
		$serialNumber->getCostListByRoomID ( $room_focus_id );
		return_success ( $data );
	}
}