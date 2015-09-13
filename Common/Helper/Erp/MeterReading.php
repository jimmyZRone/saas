<?php
namespace Common\Helper\Erp;
use Core\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;
class MeterReading extends \Core\Object
{
	private $all_time = 0;
	/**
	 * 添加抄表
	 * 修改时间2015年5月14日 17:34:22
	 * 
	 * @author yzx
	 * @param array $data
	 * @param int $house_type
	 * @return number
	 */
	public function addData($data,$house_type,$list_data=array())
	{
		$MeterReadingModel = new  \Common\Model\Erp\MeterReading();
		$data['house_type'] = $house_type;
		$data['create_time'] = time();
		$result = $MeterReadingModel->insert($data);
		if ($result)
		{
			$this->creatMoney($data, $house_type,$result,$list_data);
			return $result;
		}
		return false;
	}
	/**
	 * 计算抄表数据
	 * 修改时间2015年5月25日 19:59:27
	 * 
	 * @author yzx
	 * @param array $data
	 * @param int $houseType
	 */
	public function creatMoney($data,$houseType,$meterReadingId,$list_data=array())
	{
		$meterReadingMoneyModel = new \Common\Model\Erp\MeterReadingMoney(); 
		$houseModel = new \Common\Model\Erp\House();
		$meterReadingModel = new \Common\Model\Erp\MeterReading();
		$in_put_house_data = $houseModel->getOne(array("house_id"=>$data['house_id']));
		//全部多少钱
		$count_money = (($data['now_meter']-$data['before_meter'])*$data['meter_price']);
		if ($houseType == \Common\Model\Erp\MeterReading::HOUSE_TYPE_C)
		{
			if ($data['house_id']>0)
			{
				//添加分散式整租抄表数据
				$meterReadingMoneyHelper = new MeterReadingMoney();
				$feeHelper = new Fee();
				//获取当前抄表费用项
				$fee_data = $feeHelper->getRoomFeeInfo($data['house_id'],$data['company_id'],\Common\Model\Erp\Fee::SOURCE_DISPERSE,$data['fee_type_id'])[0];
				//获取当前合租抄表房源
				$meter_reading_money = array();
				if (!empty($fee_data)){
					//总入住天数
					$house_data = $meterReadingMoneyHelper->getRoomByHouseIsRental($data['house_id'],true);
					foreach ($house_data as $hkey=>$hval){
						if ($hval['signing_time']>0){
							if ($fee_data['payment_mode']==3){
								$this->all_time+=((($data['add_time']-$hval['signing_time']))/86400)*$hval['rent_count'];
							}else {
								$this->all_time+=(($data['add_time']-$hval['signing_time']))/86400;
							}
						}
					}
						//按人头分摊
						if ($fee_data['payment_mode']==3){
							if (count($list_data['data'])<=0){
								$count_money=0;
							}
							foreach ($house_data as $hkey=>$hval){
								if ($hval['signing_time']>0){
									//计算多少钱
									$money = (((($data['add_time']-$hval['signing_time'])/86400)*$hval['rent_count'])/$this->all_time)*$count_money;
									$meter_reading_money['money'] = $money;
									$meter_reading_money['meter_reading_id'] = $meterReadingId;
									$meter_reading_money['house_id'] = 0;
									$meter_reading_money['room_id'] = $hval['room_id'];
									$meter_reading_money['house_type'] = $meterReadingMoneyModel::HOUSE_TYPE_R;
									$insert_result = $meterReadingMoneyModel->insert($meter_reading_money);
									if ($insert_result){
										$meterReadingModel->selectFuntion($meterReadingMoneyModel::HOUSE_TYPE_R,$count_money,$data['add_time'], 0, $hval['room_id']);
									}
								}
							}
						}
						//按房间分摊
						if ($fee_data['payment_mode']==4){
							if (count($list_data['data'])<=0){
								$count_money=0;
							}
							$house_f_data = $meterReadingMoneyHelper->getRoomByHouseIsRental($data['house_id'],true);
							foreach ($house_f_data as $hdkey=>$hdval){
								if ($hdval['signing_time']>0){
									//计算多少钱
									$money = ((($data['add_time']-$hdval['signing_time'])/86400)/$this->all_time)*$count_money;
									$meter_reading_money['meter_reading_id'] = $meterReadingId;
									$meter_reading_money['money'] = $money;
									$meter_reading_money['house_id'] = 0;
									$meter_reading_money['room_id'] = $hdval['room_id'];
									$meter_reading_money['house_type'] = $meterReadingMoneyModel::HOUSE_TYPE_R;
									$insert_result = $meterReadingMoneyModel->insert($meter_reading_money);
									if ($insert_result){
										$meterReadingModel->selectFuntion($meterReadingMoneyModel::HOUSE_TYPE_R,$count_money,$data['add_time'], 0, $hval['room_id']);
									}
								}
							}
						}
						//抄表按房间独立结算
						if ($fee_data['payment_mode']==5){
							if (count($list_data['data'])<=0){
								$count_money=0;
							}
							//$house_h_data = $meterReadingMoneyHelper->getHouseIsRental($data['house_id']);
							if ($in_put_house_data['rental_way'] == $houseModel::RENTAL_WAY_Z){
								$meter_reading_money['meter_reading_id'] = $meterReadingId;
								$meter_reading_money['money'] = $count_money;
								$meter_reading_money['house_id'] = $data['house_id'];
								$meter_reading_money['room_id'] = 0;
								$meter_reading_money['house_type'] = $meterReadingMoneyModel::HOUSE_TYPE_R;
								$insert_result = $meterReadingMoneyModel->insert($meter_reading_money);
								if ($insert_result){
									$meterReadingModel->selectFuntion($meterReadingMoneyModel::HOUSE_TYPE_R,$count_money,$data['add_time'], $data['house_id'], 0);
								}
							}
						}
				}
			}
			//TODO 添加分散式合租房间数据
			if ($data['room_id']>0)
			{
				if (count($list_data['data'])<=0){
					$count_money=0;
				}
				$meter_reading_money['meter_reading_id'] = $meterReadingId;
				$meter_reading_money['money'] = $count_money;
				$meter_reading_money['house_id'] = 0;
				$meter_reading_money['room_id'] = $data['room_id'];
				$meter_reading_money['house_type'] = $meterReadingMoneyModel::HOUSE_TYPE_R;
				$insert_result = $meterReadingMoneyModel->insert($meter_reading_money);
				if ($insert_result){
					$meterReadingModel->selectFuntion($meterReadingMoneyModel::HOUSE_TYPE_R,$count_money,$data['add_time'], 0, $data['room_id']);
				}
			}
		}
		if ($houseType == \Common\Model\Erp\MeterReading::HOUSE_TYPE_F)
		{
       
			if ($data['room_id']>0)
			{
				if (count($list_data['data'])<=0){
					$count_money=0;
				}
				//TODO 添加集中式房源数据
				$meter_reading_money['meter_reading_id'] = $meterReadingId;
				$meter_reading_money['money'] = $count_money;
				$meter_reading_money['house_id'] = $data['room_id'];
				$meter_reading_money['room_id'] = 0;
				$meter_reading_money['house_type'] = $meterReadingMoneyModel::HOUSE_TYPE_F;
			    $insert_result = $meterReadingMoneyModel->insert($meter_reading_money);
			    if (true){
			    	$meterReadingModel->selectFuntion($meterReadingMoneyModel::HOUSE_TYPE_F,$count_money,$data['add_time'], 0, $data['room_id']);
			    }
       
			}
		}
	}
	/**
	 * 写入数据库
	 * 修改时间2015年5月26日 11:12:40
	 * 
	 * @author yzx
	 * @param array $data
	 * @param int $count
	 * @return boolean
	 */
	private function insertMoney($data,$count)
	{
		$meterReadingMoneyModel = new \Common\Model\Erp\MeterReadingMoney();
		for ($i=0;$i<$count;$i++)
		{
			$result = $meterReadingMoneyModel->insert($data);
		}
		return true;
	}
	/**
	 * 查询抄表数据
	 * 修改时间2015年5月14日 18:52:44
	 * 
	 * @author yzx
	 * @param int $house_type
	 * @param int $id
	 * @param int $fee_type_id
	 * @param string $isRoom
	 * @return Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >
	 */
	public function getlistData($house_type,$id,$page,$size,$fee_type_id=0,$isRoom=false)
	{
		$fee_type_map = \Core\Session::read("fee_type_map");
		if (!empty($fee_type_map)){
			foreach ($fee_type_map as $key=>$val){
				$type_id[]=$val['fee_type_id'];
			}
		}
		$MeterReadingModel = new  \Common\Model\Erp\MeterReading();
		$sql = $MeterReadingModel->getSqlObject();
		$select = $sql->select(array("mr"=>$MeterReadingModel->getTableName()));
		$select->leftjoin(array("ft"=>"fee_type"), "ft.fee_type_id = mr.fee_type_id");
		$select->leftjoin(array("u"=>"user"),"mr.creat_user_id=u.user_id",array("user_id","username"));
		$select->leftjoin(array("ue"=>"user_extend"), "u.user_id=ue.user_id",array("name"));
		$select->where(array("mr.house_type"=>$house_type));
		if ($fee_type_id>0)
		{
			$select->where(array("mr.fee_type_id"=>$fee_type_id));
		}
		if ($house_type == \Common\Model\Erp\MeterReading::HOUSE_TYPE_F)
		{
			$select->where(array("mr.room_id"=>$id));
		}
		if ($house_type == \Common\Model\Erp\MeterReading::HOUSE_TYPE_C)
		{
			if ($isRoom)
			{
				$select->where(array("mr.room_id"=>$id));
			}else {
				$select->where(array("mr.house_id"=>$id));
			}
		}
		if (!empty($type_id)){
			$select->where(array("mr.fee_type_id"=>$type_id));
		}
		$select->order("mr.create_time desc");
		//print_r(str_replace('"', '', $select->getSqlString()));die();
		return Select::pageSelect($select,null, $page, $size);
	}
	/**
	 * 获取费用
	 * 修改时间2015年5月28日 10:56:08
	 * 
	 * @author yzx
	 * @param int $roomId
	 * @param int $houseId
	 * @param int $houseType
	 * @return Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >
	 */
	public function moneyList($roomId,$houseId,$houseType){
		$meterReadingMoneyModle = new \Common\Model\Erp\MeterReadingMoney();
		$roomModel = new \Common\Model\Erp\Room();
		$room_data = $roomModel->getData(array("house_id"=>$houseId,"is_delete"=>0));
		$room_id = array();
		if (!empty($room_data)){
			foreach ($room_data as $key=>$val){
				$room_id[]=$val['room_id'];
			}
		}
		if ($houseType==2){
			$house_type = $meterReadingMoneyModle::HOUSE_TYPE_F;
		}
		if ($houseType==1){
			$house_type=$meterReadingMoneyModle::HOUSE_TYPE_R;
		}
		$sql = $meterReadingMoneyModle->getSqlObject();
		$select = $sql->select(array("mrm"=>$meterReadingMoneyModle->getTableName()));
		$select->leftjoin(array('mr'=>'meter_reading'), 'mrm.meter_reading_id = mr.meter_id',array("creat_user_id","fee_type_id"));
		$select->leftjoin(array('ft'=>'fee_type'), "mr.fee_type_id=ft.fee_type_id",array("type_name"));
		$select->leftjoin(array('u'=>'user'), "u.user_id=mr.creat_user_id",array("username","sum_money"=>new Expression("sum(mrm.money)"),"meterid"=>new Expression("GROUP_CONCAT(mr.meter_id)"))); 
		//集中式数据查询条件
		if ($house_type==$meterReadingMoneyModle::HOUSE_TYPE_F){
		$select->leftjoin(array("rf"=>"room_focus"), "rf.room_focus_id = mrm.house_id",array("custom_number"));
		$select->leftjoin(array("f"=>"flat"), "rf.flat_id=f.flat_id",array("house_name"=>"flat_name"));
		$select->where(array('mrm.house_id'=>$roomId));	
		$select->group("mrm.house_id");
		}
		//分散式数据查询条件
		if ($house_type==$meterReadingMoneyModle::HOUSE_TYPE_R){
		$select->leftjoin(array('r'=>"room"), "mrm.room_id=r.room_id",array("custom_number"));
		$select->leftjoin(array('h'=>"house"), "r.house_id=h.house_id",array("house_name"));
		$select->group("mrm.room_id");
		}
		if ($house_type==$meterReadingMoneyModle::HOUSE_TYPE_R && $houseId>0){
		$where = new Where();
		$where->in("mrm.room_id",$room_id);
		$select->where($where);
		}
		if ($house_type==$meterReadingMoneyModle::HOUSE_TYPE_R && $roomId>0){
		$select->where(array("mrm.room_id"=>$roomId));
		}
		$select->group("mr.fee_type_id");
		$select->order("mrm.room_id desc");
		$result = $select->execute();
		//print_r(str_replace('"', '', $select->getSqlString()));die();
		return $result;
	}
	/**
	 * 获取费用项目
	 * 修改时间2015年5月29日 10:08:35
	 * 
	 * @author yzx
	 * @param array $data
	 * @return multitype:unknown
	 */
	public function getFee($data)
	{
		$fee_type_data = array(3,4,5);
		$out_fee_type_map = array();
		foreach ($data as $key=>$val)
		{
			if (in_array($val['payment_mode'], $fee_type_data))
			{
				$out_fee_type_map[$key]=$val;
			}
		}
		return $out_fee_type_map;
	}
	/**
	 * 更加来源ID来获取抄表数据
	 * 修改时间2015年6月1日 15:26:11
	 * 
	 * @author yzx
	 * @param int $id
	 * @param int $houseType
	 * @param int $feeIypeId
	 * @param string $isRoom
	 * @return Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >
	 */
	public function getDataById($id,$houseType,$feeIypeId,$isRoom=false){
		$meterReadingModel = new \Common\Model\Erp\MeterReading();
		$sql = $meterReadingModel->getSqlObject();
		$select = $sql->select($meterReadingModel->getTableName());
		$select->where(array('house_type'=>$houseType));
		if ($houseType == $meterReadingModel::HOUSE_TYPE_C && $isRoom==false){
			$select->where(array("house_id"=>$id));
		}
		if ($houseType == $meterReadingModel::HOUSE_TYPE_C && $isRoom==true){
			$select->where(array("room_id"=>$id));
		}
		$select->where(array("fee_type_id"=>$feeIypeId));
		$select->order('before_meter ASC');
		$result = $select->execute();
		return $result?$result[0]:array();
	}
	/**
	 * 根据房间id和费用类型id和当月时间  获取抄表的id, 根据抄表id查询抄表金额
	 * 修改时间 2015年6月9日20:17:09
	 * 
	 * @author ft
	 */
	public function getMeterMoneyById($condition, $fee_type_id) {
        $meter_read_model = new \Common\Model\Erp\MeterReading();
        if ($condition['house_type'] == 1) {
            if ($condition['house_id'] > 0 && $condition['room_id'] > 0) {//合租
                $where = new \Zend\Db\Sql\Where();
                $where->equalTo('room_id', $condition['room_id']);
                $where->equalTo('house_type', $condition['house_type']);
                $where->equalTo('fee_type_id', $fee_type_id);
                $where->equalTo('is_sf', 0);
                $where->greaterThanOrEqualTo('add_time', $condition['first_day']);
                $where->lessThanOrEqualTo('add_time', $condition['last_day']);
                $columns = array('meter_id' => 'meter_id');
                $meter_id_arr = $meter_read_model->getData($where, $columns);
            } else {//整租
                $where = new \Zend\Db\Sql\Where();
                $where->equalTo('house_id', $condition['house_id']);
                $where->equalTo('house_type', $condition['house_type']);
                $where->equalTo('fee_type_id', $fee_type_id);
                $where->equalTo('is_sf', 0);
                $where->greaterThanOrEqualTo('add_time', $condition['first_day']);
                $where->lessThanOrEqualTo('add_time', $condition['last_day']);
                $columns = array('meter_id' => 'meter_id');
                $meter_id_arr = $meter_read_model->getData($where, $columns);
            }
        } else {//集中式
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('room_id', $condition['room_id']);
            $where->equalTo('house_type', $condition['house_type']);
            $where->equalTo('fee_type_id', $fee_type_id);
            $where->equalTo('is_sf', 0);
            $where->greaterThanOrEqualTo('add_time', $condition['first_day']);
            $where->lessThanOrEqualTo('add_time', $condition['last_day']);
            $columns = array('meter_id' => 'meter_id');
            $meter_id_arr = $meter_read_model->getData($where, $columns);
        }
        if ($meter_id_arr) {
            foreach ($meter_id_arr as $meter) {
                $meter_id[] = $meter['meter_id'];
            }
            $meter_money_model = new \Common\Model\Erp\MeterReadingMoney();
            $sql = $meter_money_model->getSqlObject();
            $select = $sql->select(array('mrm' => 'meter_reading_money'));
            $select->columns(array('sum_money' => new Expression('SUM(money)')));            
            $money_where = new \Zend\Db\Sql\Where();
            $money_where->equalTo('is_settle', 0);
            $money_where->in('meter_reading_id', $meter_id);
            $select->where($money_where);
            return $select->execute();
        }
	}
	
	/**
	 * 通过房间id和房源类型 获取抄表的费用类型金额
	 * 修改时间  2015年6月15日14:05:34
	 * 
	 * @author ft
	 */
	public function getMeterMoneyByRoomId($house_id, $house_type, $room_id) {
        $meter_read_model = new \Common\Model\Erp\MeterReading();
        $sql = $meter_read_model->getSqlObject();
        $select = $sql->select(array('mr' => 'meter_reading'));
        $select->columns(array('meter_id' => 'meter_id'));
        $select->leftjoin(array('ft' => 'fee_type'), 'mr.fee_type_id = ft.fee_type_id', array('fee_type_id' => 'fee_type_id', 'type_name' => 'type_name'));
        $select->leftjoin(array('mrm' => 'meter_reading_money'), 'mr.meter_id = mrm.meter_reading_id', array('money' => 'money', 'house_id', 'room_id'));
        $where = new \Zend\Db\Sql\Where();
        $where->equalTo('mr.house_type', $house_type);
        $where->equalTo('mr.is_sf', 0);
        $where->equalTo('mrm.is_settle', 0);
        if ($house_type == 1) {//分散式
            if ($room_id > 0) {//合租
                $where->equalTo('mrm.room_id', $room_id);
            } else {//整租
                $where->equalTo('mrm.house_id', $house_id);
            }
        } else {//集中式
            $where->equalTo('mrm.house_id', $room_id);
        }
        $select->where($where);
        $select->order('ft.fee_type_id ASC');
        //print_r(str_replace('"', '', $select->getSqlString()));die();
        return $select->execute();
	}
	/**
	 * 获取合同显示抄表数据
	 * 修改时间2015年7月16日10:04:30
	 * 
	 * @author yzx
	 * @param unknown $id
	 * @param unknown $houseType
	 * @param string $isRoom
	 * @return Ambigous <boolean, \Core\Db\Sql\multitype:, \Core\Db\Sql\Ambigous, multitype:, mixed, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >
	 */
	public function getContractMoney($id,$houseType,$isRoom=false){
		$meter_read_money_model = new \Common\Model\Erp\MeterReadingMoney();
		$sql = $meter_read_money_model->getSqlObject();
		$select = $sql->select(array("mrm"=>$meter_read_money_model->getTableName()));
		$select->leftjoin(array("mr"=>"meter_reading"), "mr.meter_id=mrm.meter_reading_id",array("fee_type_id","now_meter"));
		$select->where(array("mr.house_type"=>$houseType));
		if (!$isRoom && $id>0){
		$select->where(array("mr.house_id"=>$id));
		}
		if ($isRoom && $id>0){
		$select->where(array("mr.room_id"=>$id));
		}
		$result = $select->execute();
		return $result;
	}
}