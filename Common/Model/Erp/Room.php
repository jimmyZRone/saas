<?php
namespace Common\Model\Erp;
class Room extends \Common\Model\Erp
{
	/**
	 * 统计参数
	 * @var unknown
	 */
	const SUM_MONEY = "sum_money";
	const IS_RENTAL = "is_rental";
	const NOT_RENTAL = "not_rental";
	const IS_RESERVE = "is_reserve";
	const IS_YYTZ_C = "is_yytz";
	const IS_STOP = "is_stop";
	/**
	 * 房间状态
	 * @var unknown
	 */
	//未租
	const STATUS_NOT_RENTAL = 1;
	//已租
	const STATIS_RENTAL = 2;
	//停用
	const STATUS_IS_STOP = 3;
	/**
	 * 是否预约退租
	 * @var unknown
	 */
	const NOT_IS_YYTZ = 0;
	const IS_YYTZ = 1;
	const IS_YD = 1;
	/**
	 * 房间类型
	 * @var unknown
	 */
	public static $room_type = array(
			"main"=>'主卧',
			"second"=>'次卧',
			"guest"=>'客卧'
	);
	/**
	 * 通过房间编号查找数据
	 * 修改时间2015年5月5日 16:09:42
	 *
	 * @author yzx
	 * @param string $customNumber
	 * @param int $house_id
	 * @return unknown
	 */
	public function getDataByCustomNumber($customNumber,$house_id)
	{
		$select = $this->_sql_object->select($this->_table_name);
		$select->where(array("house_id"=>$house_id,"custom_number"=>$customNumber,"is_delete"=>0));
		$result = $select->execute();
		return $result?$result[0]:array();
	}
	/**
	 * 删除房间可以批量删除
	 * 修改时间2015年5月13日 11:11:54
	 *
	 * @author yzx
	 * @param int $roomId
	 * @return boolean
	 */
	public function deleteData($roomId,$houseId)
	{
		$houseModel = new House();
		$houseEntityModel = new HouseEntirel();
		$todoModel = new \Common\Model\Erp\Todo();
		$this->Transaction();
		if (is_array($roomId))
		{
			foreach ($roomId as $val)
			{
				$room_data = $this->getOne(array("room_id"=>$val));
				$result = $this->edit(array("room_id"=>$val), array("is_delete"=>1));
				if (!$result)
				{
					$this->rollback();
					return false;
					exit();
				}
				//删除日志START
				$todoModel->delete(array("entity_id"=>$val,"module"=>$todoModel::MODEL_ROOM_STOP));
				$todoModel->delete(array("entity_id"=>$val,"module"=>$todoModel::MODEL_ROOM_RESERVE));
				$todoModel->delete(array("entity_id"=>$val,"module"=>$todoModel::MODEL_ROOM_RESERVE_OUT));
				//删除日志END
			}
			$house_data = $this->getData(array("house_id"=>$houseId,"is_delete"=>0));
			$houseModel->edit(array("house_id"=>$houseId), array("count"=>(count($house_data))));
			if (count($house_data)<=0){
				//$houseEntityModel->edit(array("house_id"=>$houseId), array("is_delete"=>1));
				$houseModel->edit(array("house_id"=>$houseId), array("count"=>0));
			}
			$this->commit();
			
			//写快照
			\Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_ROOM_DELETE, $val, $room_data);
			
			return true;
		}else {
			$room_data = $this->getOne(array("room_id"=>$roomId));
			$result = $this->edit(array("room_id"=>$roomId), array("is_delete"=>1));
			if (!$result)
			{
				$this->rollback();
				return false;
			}
			//删除日志START
			$todoModel->delete(array("entity_id"=>$roomId,"module"=>$todoModel::MODEL_ROOM_STOP));
			$todoModel->delete(array("entity_id"=>$roomId,"module"=>$todoModel::MODEL_ROOM_RESERVE));
			$todoModel->delete(array("entity_id"=>$roomId,"module"=>$todoModel::MODEL_ROOM_RESERVE_OUT));
			//删除日志END
			$house_data = $this->getData(array("house_id"=>$houseId,"is_delete"=>0));
			$houseModel->edit(array("house_id"=>$houseId), array("count"=>(count($house_data))));
			if (count($house_data)<=0){
				//$houseEntityModel->edit(array("house_id"=>$houseId), array("is_delete"=>1));
				$houseModel->edit(array("house_id"=>$houseId), array("count"=>0));
			}
			$this->commit();
			
			//写快照
			\Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_ROOM_DELETE, $roomId, $room_data);
			
			return true;
		}
	}
	/**
	 * 修改房间出租状态为已出租
	 * @param $id 房间id
	 * @param $sid 状态 1,未租,2已租,3停用
	 * @author too|编写注释时间 2015年5月14日 下午4:53:49
	 */
	public function changStatus($id,$sid=Room::STATUS_NOT_RENTAL)
	{
		$reserveModel = new Reserve();
		$reserve_data = $reserveModel->getData(array("house_type"=>$reserveModel::HOUSE_TYPE_R,"room_id"=>$id,"is_delete"=>0));
		if (empty($reserve_data)){
			$data['is_yd'] = 0;
		}
	    $where = array('room_id'=>$id);//分散式合租时修改房间状态
	    $data['status'] = $sid;
	    $data['is_yytz'] = 0;
	    return $this->edit($where,$data);
	}
	/**
	 * 是否有独立收费项目
	 * 修改时间2015年5月25日 17:12:02
	 *
	 * @author yzx
	 * @param int $roomId
	 * @param array $user
	 * @return boolean
	 */
	public function isMeterReading($roomId,$user,$isHouse=false)
	{
		$roomModel = new \Common\Model\Erp\Room();
		$feeHelper = new \Common\Helper\Erp\Fee();
		$room_data = $roomModel->getOne(array("room_id"=>$roomId));
		$fee_data = $feeHelper->getRoomFeeInfo($room_data['house_id'],$user['company_id'], \Common\Model\Erp\Fee::SOURCE_DISPERSE);
		$data = array();
		if (!$isHouse){
			foreach ($fee_data as $key=>$val)
			{
					if ($val['payment_mode'] == 5)
					{
						$data[] = $val;
					}
			}
		}
		if ($isHouse){
			$data = $fee_data;
		}
		return $data;
	}
}