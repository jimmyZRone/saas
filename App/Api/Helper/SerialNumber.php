<?php

namespace App\Api\Helper;

class SerialNumber extends \Common\Helper\Erp\SerialNumber {
	/**
	 * 获取集中式房源的流水根据room_id
	 * 
	 * @author yusj | 最后修改时间 2015年5月4日下午3:47:44
	 */
	public function getCostListByRoomID($room_id) {
		$serial_number_model = new \Common\Model\Erp\SerialNumber ();
		$sql = $serial_number_model->getSqlObject ();
		$select = $sql->select ( array (
				'sn' => $serial_number_model->getTableName () 
		) );
		$select->columns ( array (
				'serial_id',
				'serial_name' 
		) );
		$where = new \Zend\Db\Sql\Where (); // 造where条件对象
		$where->equalTo ( 'sn.house_id', 0 ); // 集中房源的流水
		$where->equalTo ( 'sn.house_type', 2 ); // 集中房源的流水
		$where->equalTo ( 'sn.room_id', $room_id );
		$select->where ( $where );
		$data = $select->execute ();
		return $data;
	}
}
    