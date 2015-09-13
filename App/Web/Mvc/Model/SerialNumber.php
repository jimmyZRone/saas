<?php
namespace App\Web\Mvc\Model;
use Zend\Db\Sql\Expression;
use Core\Db\Sql\Select;
class SerialNumber extends Common
{
	const SERIA_SOURCE_RENTAL = 'rental';
	const SERIAL_SOURCE_HOUSE = 'house';
	const SERIAL_SOURCE_ROOM = 'room';
	//集中式source
	const SERIA_SOURCE_FOCUS_RENTAL = 'focus_rental';
	const SERIA_SOURCE_FOCUS_HOUSE = 'focus_house';
	const SERIA_SOURCE_FOCUS_ROOM = 'focus_room';
	
	public $source = array(
			self::SERIA_SOURCE_RENTAL=>"分散式已租source",
			self::SERIAL_SOURCE_HOUSE=>"分散式整租source",
			self::SERIAL_SOURCE_ROOM=>"分散式合租source",
			self::SERIA_SOURCE_FOCUS_RENTAL=>"集中式已租source",
			self::SERIA_SOURCE_FOCUS_HOUSE=>"集中式整租source",
			self::SERIA_SOURCE_FOCUS_ROOM=>"集中式合租source",
	);
	/**
	 * 获取租客合同流水
	 * 修改时间2015年3月20日 14:26:05
	 * 
	 * @author yzx
	 * @param int $rentalId
	 * @param string $source
	 * @return array|boolean
	 */
	public function getContractSerial($rentalId,$source)
	{
		$select = $this->_sql_object->select(array("sn"=>"serial_number"))
				  ->leftjoin(array("sd"=>"serial_detail"),"sn.serial_id = sd.serial_id" ,"*")
				  ->where(array("source_id"=>$rentalId,"source"=>$source));
		$result = $select->execute();
		if (!empty($result))
		{
			return $result;
		}
		return false;
				 
	}
	/**
	 * 获取单条财务数据
	 * 修改时间2015年3月19日 09:51:14
	 * 
	 * @author yzx
	 * @param int $serial_id
	 * @return array|boolean
	 */
	 public function detail($serial_id)
	 {
	 	$select = $this->_sql_object->select(array("sn"=>"serial_number"))
	 			  ->leftjoin(array("sd"=>"serial_detail"),"sn.serial_id = sd.serial_id","*")
	 			  ->leftjoin(array("r"=>'rental'),"sn.serial_id = r.rental_id",array("source"))
	 			  ->leftjoin(array("t"=>"tenant"), "r.tenant_id = t.tenant_id",array("name","phone"))
	 			  ->where(array("sn.source_id"=>$serial_id));
	 	$result = $select->execute();
	 	if (!empty($result))
	 	{
	 		return $result;
	 	}
	 	return false;
	 }
	 /**
	  * 添加流水
	  * 修改时间2015年3月19日 13:02:26
	  * 
	  * @author yzx
	  * @param array $data
	  * @return boolean
	  */
	 public function addSeriaNumber($data)
	 {
	 	$houseModel = new House();
	 	$serialDetailModel = new SerialDetail(); 
	 	$rental_data = array();
	 	$serial_detail_result = array();
	 	if (isset($data['house_id']) && isset($data['house_type']))
	 	{
	 		if (is_array($data['detail']) && !empty($data['detail']))
	 		{
	 			$name = '';
	 			foreach ($data['detail'] as $fkey=>$fval)
	 			{
	 				$name.=$fval['type_name']."、";
	 			}
	 			$name."流水号".$data['serial_number'];
	 			$data['serial_name'] = $name;
	 		}
	 		$rental_data = $houseModel->getHoueRental($data['house_id'], $data['house_type']);
	 		if (!empty($rental_data))
	 		{
	 			$data['source_id'] = $rental_data['rental_id'];
	 			$data['source'] = self::SERIA_SOURCE_RENTAL;
	 		}else 
	 		{
	 			$data['source_id'] = $data['house_id'];
	 			$data['source'] = $data['house_type'];
	 		}
	 	}
	 	$this->Transaction();
	 	$serial_number_id = $this->insert($data);
	 	if (!$serial_number_id){
	 		$this->rollback();
	 		return false;
	 	}
	 	if (is_array($data['detail']) && !empty($data['detail'])){
	 		$serial_detail_data = array();
			foreach ($data['detail'] as $fkey=>$fval)
			{
				$serial_detail_data['serial_id'] = $serial_number_id;
				$serial_detail_data['fee_type_id'] = $fval['fee_type_id'];
				$serial_detail_data['money'] = $fval['money'];
				$serial_detail_result[] = $serialDetailModel->addSerialDetail($serial_detail_data);
			}	 		
	 	}
	 	if (empty($serial_detail_result))
	 	{
	 		$this->rollback();
	 		return false;
	 	}
	 	$this->commit();
	 	return $serial_number_id;
	 }
	 /**
	  * 编辑流水
	  * 修改时间2015年3月19日 14:59:59
	  * 
	  * @author yzx
	  * @param array $data
	  * @param int $serialId
	  * @return boolean
	  */
	 public function editSeriaNumber($data,$serialId)
	 {
	 	$houseModel = new House();
	 	$serialDetailModel = new SerialDetail();
	 	$rental_data = array();
	 	$serial_detail_result = array();
	 	//修改流水
	 	if (isset($data['house_id']) && isset($data['house_type']))
	 	{
	 		$rental_data = $houseModel->getHoueRental($data['house_id'], $data['house_id']);
	 		if (is_array($data['detail']) && !empty($data['detail']))
	 		{
	 			$name = '';
	 			foreach ($data['detail'] as $fkey=>$fval)
	 			{
	 				$name.=$fval['type_name']."、";
	 			}
	 			$name."流水号".$data['serial_number'];
	 			$data['serial_name'] = $name;
	 		}
	 		if (!empty($rental_data))
	 		{
	 			$data['source_id'] = $rental_data['rental_id'];
	 			$data['source'] = self::SERIA_SOURCE_RENTAL;
	 		}
	 	}
	 	$this->Transaction();
	 	$serial_result = $this->edit(array("serial_id"=>$serialId), $data);
	 	if (!$serial_result)
	 	{
	 		$this->rollback();
	 		return false;
	 	}
	 	//检查是否删除详情
	 	$this->checkDeleteDetail($data['detail'], $serialId);
	 	//修改详情
	 	if (is_array($data['detail']) && !empty($data['detail']))
	 	{
	 		foreach ($data['detail'] as $fkey=>$fval)
	 		{
	 			$serial_detail_result['fee_type_id'] = $fval['fee_type_id'];
	 			$serial_detail_result['money'] = $fval['money'];
	 			$serial_detail = $serialDetailModel->editSerialDetail($serial_detail_result, $fval['serial_detail_id']);
	 			if (!$serial_detail)
	 			{
	 				$this->rollback();
	 			}
	 		}
	 		$this->commit();
	 		return true;
	 	}
	 	return false;
	 }
	 /**
	  * 获取编辑数据
	  * 修改时间2015年3月19日 16:42:26
	  * 
	  * @author yzx
	  * @param int $serialId
	  * @return boolean
	  */
	 public function editData($serialId)
	 {
	 	$detailModel = new SerialDetail();
	 	$serial_number_data = $this->getOne(array("serial_id"=>$serialId));
	 	if (empty($serial_number_data))
	 	{
	 		return false;
	 	}
	 	$detail_data = $detailModel->getSerialDetail($serialId);
	 	$house_data = $this->getHouseData($serial_number_data);
	 	return array("data"=>$serial_number_data,"detail"=>$detail_data,"house_data"=>$house_data);
	 }
	 /**
	  * 获取房源信息
	  * 修改时间2015年3月19日 16:42:55
	  * 
	  * @author yzx
	  * @param array $serialNumberData
	  * @return array
	  */
	 private function getHouseData($serialNumberData)
	 {
	 	$houseModel = new House();
	 	$roomModel = new Room();
	 	$rentalModel = new Rental();
	 	$flatModel = new Flat();
	 	$house_data = array();
	 	switch ($serialNumberData['source'])
	 	{
	 			//分散式房源
	 		case self::SERIAL_SOURCE_HOUSE:
	 				$house_data = $houseModel->getOne(array("house_id"=>$serialNumberData['source_id']));
	 			break;
	 			//分散式房间
	 		case self::SERIAL_SOURCE_ROOM:
	 			$house_data = $roomModel->getRoomByHouse($serialNumberData['source_id']);
	 			break;
	 			//分散式租客房间
	 		case self::SERIA_SOURCE_RENTAL:
	 			$rental_data = $rentalModel->getHouseByRental($serialNumberData['source_id']);
	 			if ($rental_data['house_id']>0)
	 			{
	 				$house_data = $houseModel->getOne(array("house_id"=>$serialNumberData['source_id']));
	 			}elseif($rental_data['room_id']>0)
	 			{
	 				$house_data = $roomModel->getRoomByHouse($serialNumberData['source_id']);
	 			}
	 			break;
	 			//集中式房间
	 		case self::SERIA_SOURCE_FOCUS_HOUSE:
	 			$house_data = $flatModel->getFlatData($serialNumberData['source_id']);
	 			break;
	 			//集中式租客房间
	 		case self::SERIA_SOURCE_FOCUS_RENTAL:
	 			$rental_data = $rentalModel->getHouseByRental($serialNumberData['source_id']);
	 			$house_data = $flatModel->getFlatData($rental_data['source_id']);
	 			break;
	 		default:
	 			$house_data = array();
	 			break;
	 	}
	 	return $house_data;
	 }
	 /**
	  * 删除流水详情
	  * 修改时间2015年3月19日 15:19:05
	  * 
	  * @author yzx
	  * @param array $detail
	  * @param int $serial_id
	  */
	 private function checkDeleteDetail($detail,$serialId)
	 {
	 	$serialDetailModel = new SerialDetail();
	 	$all_detail = $serialDetailModel->getSerialDetail($serialId);
	 	$detail_id = array();
	 	if(!empty($all_detail))
	 	{
	 		foreach ($detail as $key=>$val)
	 		{
	 			$detail_id[]=$val['serial_detail_id'];
	 		}
	 	}
	 	if(!empty($all_detail))
	 	{
	 		foreach ($all_detail as $akey=>$akey)
	 		{
	 			if (!in_array($akey['serial_detail_id'], $detail_id))
	 			{
	 				$serialDetailModel->delete(array("serial_detail_id"=>$akey['serial_detail_id']));
	 			}
	 		}
	 	}
	 }
	 
	 /**
	  * 获取欠费清单,包含分页
	  *  最后修改时间 2015-3-24
	  *  
	  * @author denghsuang
	  * @param unknown $user
	  * @param unknown $page
	  * @param unknown $pagesize
	  * @return multitype:multitype:Ambigous <number, unknown> number  Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> > |multitype:
	  */
	 public function getDebtsList($user,$page,$pagesize){
	 	$select = $this->_sql_object->select($this->_table_name);
	 	$select->where(array('user_id'=>$user['user_id'],'company_id'=>$user['company_id'],'status'=>2));
	 	$countSelect = clone $select;
	 	$countSelect->columns(array('count'=>new Expression('count(tenant_id)')));
	 	$count = $countSelect->execute();
	 	$count = $count[0]['count'];
	 	$result = Select::pageSelect($select, $count, $page, $pagesize);
	 	if($result){
	 		return $result;
	 	}else{
	 		return array();
	 	}
	 }
}