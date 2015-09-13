<?php
namespace Common\Model\Erp;
use Zend\Db\Sql\Where;
class MeterReading extends \Common\Model\Erp
{
	/**
	 * 集中式类型
	 * @var int
	 */
	const HOUSE_TYPE_F = 2;
	/**
	 * 分散式类型
	 * @var int
	 */
	const HOUSE_TYPE_C = 1;
	/**
	 * 收费后修改抄表状态
	 * 修改时间2015年6月15日10:33:49
	 *
	 * @author yzx
	 * @param array $data
	 */
	public static function sfMoney(array $data){
		if (!empty($data)){
			$meterReadModel = new \Common\Model\Erp\MeterReading();
			$meterReadingMoney = new \Common\Model\Erp\MeterReadingMoney();
			foreach ($data as $v) {
			    if ($v['house_type'] == 1) {
			        if (isset($v['house_id']) && $v['house_id'] > 0) {//整租
            			$meterReadModel->edit(array('meter_id' => $v['meter_id'], 'house_id' => $v['house_id'], 'house_type' => $v['house_type']), array("is_sf"=>1));
            			$meterReadingMoney->edit(array('meter_reading_id' => $v['meter_id'], 'house_id' => $v['house_id'], 'house_type' => $v['house_type']), array("is_settle"=>1));
			        } else {//合租
            			$meterReadModel->edit(array('meter_id' => $v['meter_id'], 'room_id' => $v['room_id'], 'house_type' => $v['house_type']), array("is_sf"=>1));
            			$meterReadingMoney->edit(array('meter_reading_id' => $v['meter_id'], 'room_id' => $v['room_id'], 'house_type' => $v['house_type']), array("is_settle"=>1));
			        }
			    } else {//集中
        			$meterReadModel->edit(array('meter_id' => $v['meter_id'], 'room_id' => $v['room_id'], 'house_type' => $v['house_type']), array("is_sf"=>1));
        			$meterReadingMoney->edit(array('meter_reading_id' => $v['meter_id'], 'room_id' => $v['room_id'], 'house_type' => $v['house_type']), array("is_settle"=>1));
			    }
			}
		}
	}
	/**
	 * 退租后删除抄表数据
	 * 修改时间2015年7月27日15:46:57
	 * 
	 * @author yzx
	 * @param unknown $house_type
	 * @param number $house_id
	 * @param number $room_id
	 */
	public function deleteByRentalOut($house_type,$house_id=0,$room_id=0){
		$meterReadModel = new \Common\Model\Erp\MeterReading();
		$meterReadingMoney = new \Common\Model\Erp\MeterReadingMoney();
		if ($house_type == $meterReadModel::HOUSE_TYPE_C){
			if ($house_id>0){
				$where = array("house_type"=>$house_type,"house_id"=>$house_id,"is_sf"=>0);
			}
			if ($room_id>0){
				$where = array("house_type"=>$house_type,"room_id"=>$room_id,"is_sf"=>0);
			}
			$meterReadingData = $meterReadModel->getData($where);
		}
		if ($house_type == $meterReadModel::HOUSE_TYPE_F){
			if ($room_id>0){
				$where = array("house_type"=>$house_type,"room_id"=>$room_id,"is_sf"=>0);
			}	
			$meterReadingData = $meterReadModel->getData($where);
		}
		
		if (!empty($meterReadingData)){
			$meter_id = array();
			foreach ($meterReadingData as $key=>$val){
				$meter_id[]=$val['meter_id'];
			}
		}
		$d_where = new Where();
		$d_where->in("meter_id",$meter_id);
		$d_where->greaterThanOrEqualTo("before_meter", 0);
		$d_where->equalTo("is_sf", 0);
		$meterReadModel->edit($d_where, array("is_sf"=>1));
		$meterReadingMoney->edit(array("meter_reading_id"=>$meter_id,"is_settle"=>0),array("is_settle"=>1));
	}
	/**
	 * 选择修改合同的方法
	 * 修改时间2015年8月31日16:06:23
	 * 
	 * @author yzx
	 * @param int $houseType
	 * @param int $houseId
	 * @param int $roomId
	 */
	public function selectFuntion($houseType,$count_money,$addTime,$houseId,$roomId){
		$meterReadingMoneyHelper = new \Common\Helper\erp\MeterReadingMoney();
		if ($houseType == self::HOUSE_TYPE_F){
			if ($roomId>0){
				$meterReadingMoneyHelper->updateFlatContract($count_money,$addTime,$roomId);
			}
		}
		if ($houseType == self::HOUSE_TYPE_C){
			if ($roomId>0){
				$meterReadingMoneyHelper->updataRContract($count_money,$addTime,0,$roomId);
			}
			if ($houseId>0){
				$meterReadingMoneyHelper->updataRContract($count_money,$addTime,$houseId,0);
			}
		}
	}
}