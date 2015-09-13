<?php
namespace Common\Model\Erp;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;
use Core\Db\Sql\Select;
class Rental extends \Common\Model\Erp
{
	private $id=null;
	/**
	 * 分散
	 * @var unknown
	 */
	const HOUSE_TYPE_R = 1;
	/**
	 * 集中
	 * @var unknown
	 */
	const HOUSE_TYPE_F = 2;
	/**
	 * 添加租住关系
	 * 修改时间2015年3月19日 11:01:46
	 *
	 * @author yzx
	 * @param array $data
	 */
	public function addRental($data)
	{
		return $result = $this->insert($data);
	}
	/**
	 * 编辑租住关系
	 * @author too|最后修改时间 2015年4月24日 下午2:47:42
	 */
	public function editRental($data,$rental_id)
	{
	    $data = $this->getOne(array("rental_id"=>$rental_id));
	    if (!empty($data))
	    {
	        $result = $this->edit(array("rental_id"=>$rental_id), $data);
	        if ($result)
	        {
	            return true;
	        }
	        return false;
	    }
	    return false;
	}
	/**
	 * 根据房源ID类型获取租客
	 * 修改时间2015年3月23日 15:41:35
	 *
	 * @author yzx
	 * @param int $house_id
	 * @param string $house_type
	 * @return array|boolean
	 */
	public function getTenantByHouseType($house_id,$house_type)
	{
		$houseModel = new House();
		$select = $this->_sql_object->select(array("r"=>"rental"))
			->leftjoin(array("t"=>"tenant"),"r.tenant_id = t.tenant_id","*")
			->leftjoin(array("tc"=>"tenant_contract"),"r.contract_id = tc.contract_id","*");
		if ($house_type == House::LIST_TYPE_HOUSE)
		{
			$select->where(array("r.house_id"=>$house_id));
		}elseif($house_type == House::LIST_TYPE_ROOM)
		{
			$select->where(array("r.room_id"=>$house_id));
		}
		$result = $select->execute();
		if (!empty($result))
		{
			return $result[0];
		}
		return false;
	}
	/**
	 * 获取分散式租客房源信息
	 * 修改时间2015年3月20日 10:02:08
	 *
	 * @author yzx
	 * @param int $rentalId
	 * @param string $source
	 * @return unknown|boolean
	 */
	public function getHouseByRental($rentalId)
	{
		$select = $this->_sql_object->select(array("rental"))
				  ->where(array("rental_id"=>$rentalId));
		$result = $select->execute();
		if (!empty($result))
		{
			return $result[0];
		}
		return false;
	}
	/**
	 * 根据合同ID获取租住关系
	 * 修改时间2015年3月20日 14:35:54
	 *
	 * @author yzx
	 * @param int $contractId
	 * @return array|boolean
	 */
	public function getRentalByContract($contractId)
	{
		$select = $this->_sql_object->select("rental")
				 ->where(array("contract_id"=>$contractId));
		$result = $select->execute();
		if (!empty($result))
		{
			return $result;
		}
		return false;
	}

	/**
	 * 获取租客列表
	 *  最后修改时间 2015-3-23
	 *
	 * @author dengshuang
	 * @param unknown $user
	 * @param unknown $key
	 * @param unknown $page
	 * @param unknown $pagesize
	 * @param unknown $is_focus
	 * @return multitype:multitype:Ambigous <number, unknown> number  Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> > |multitype:
	 */
	public function getTenantList($user,$key,$page,$pagesize,$is_focus){
		$select = $this->_sql_object->select(array('r'=>$this->_table_name));
		$select->leftjoin(array('tc'=>'tenant_contract'),"tc.contract_id = r.contract_id");
		$select->leftjoin(array('t'=>'tenant'),"t.tenant_id = r.tenant_id");

		$where = new \zend\db\sql\Where();
		$where->equalTo('t.company_id', $user['company_id']);
		if($is_focus == 0){
			//分散式搜索
			if(!empty($key)){
				$select->leftjoin(array('rm'=>'room'),new Expression('(rm.room_id = r.room_id and soure='.\App\Web\Mvc\Model\SerialNumber::SERIAL_SOURCE_ROOM),array('room_number'=>'custom_number'));
				$select->leftjoin(array('h'=>'house'),new Expression('(h.house = r.house_id and soure='.\App\Web\Mvc\Model\SerialNumber::SERIAL_SOURCE_HOUSE),array('house_number'=>'custom_number'));
				$select->leftjoin(array('c'=>'community'),'h.community_id = c.community_id',array('community_id'));
                                
				$where1 = new \zend\db\sql\Where();
				$where1->like('rm.custom_number',"%$key%");
				$where2 = new \zend\db\sql\Where();
				$where2->like('h.house_name',"%$key%");
				$where3 = new \zend\db\sql\Where();
				$where3->like('h.custom_number',"%$key%");
				$where4 = new \zend\db\sql\Where();
				$where4->like('c.community_name',"%$key%");
				$where5 = new \zend\db\sql\Where();
				$where5->like('t.phone',"%$key%");
                            $where5->like('t.name',"%$key%");
				$where1->addPredicates(array($where2,$where3,$where4,$where5),Where::OP_OR);
				$where->andPredicate($where1);
			}
		}else{
			//集中式搜索
			if(!empty($key)){
				$select->leftjoin(array('rf'=>'room_focus'),new Expression('(rf.room_focus_id = r.soure_id and r.soure='.\App\Web\Mvc\Model\SerialNumber::SERIA_SOURCE_FOCUS_RENTAL),array('focus_number'=>'custom_number'));
				$select->leftjoin(array('f'=>'flat'),'f.flat_id=rf.flat_id',array('flat_name'),array('flat_id'));
				$where1 = new \zend\db\sql\Where();
				$where1->like('f.flat_name',"%$key%");
				$where2 = new \Zend\Db\Sql\Where();
				$where2->like('rf.custom_number',"%$key%");
				$where3 = new \zend\db\sql\Where();
				$where3->like('t.phone',"%$key%");
				$where1->addPredicate($where2,Where::OP_OR);
				$where1->addPredicate($where3,Where::OP_OR);
				$where->andPredicate($where1);
			}
		}
		$select->where($where);
		$countSelect = clone $select;
		$countSelect->columns(array('count'=>new Expression('count(l.rental_id)')));
		$count = $countSelect->execute();
		$count = $count[0]['count'];
		$result = Select::pageSelect($select, $count, $page, $pagesize);
		if($result){
			return $result;
		}else{
			return array();
		}
	}
	/**
	 * 获取集中式租客信息
	 * 修改时间2015年4月13日 16:39:17
	 *
	 * @author yzx
	 * @param unknown $houseId
	 */
	public function getFocusData($houseId,$houseType =self::HOUSE_TYPE_F )
	{
		$house_where = new Where();
		if ($houseType == self::HOUSE_TYPE_R){
			if (is_array($houseId)){
				$house_where->in("r.house_id",$houseId);
			}else {
				$house_where->equalTo("r.house_id", $houseId);
			}
		}else {
			if (is_array($houseId)){
				$house_where->in("r.room_id",$houseId);
			}else {
				$house_where->equalTo("r.room_id", $houseId);
			}
		}
		$house_where->equalTo("r.house_type", $houseType);
		$house_where->equalTo("cr.is_delete", 0);
		$select = $this->_sql_object->select(array("r"=>"rental"));
		$select->leftjoin(array("tc"=>"tenant_contract"),"r.contract_id = tc.contract_id",array("next_pay_time","totalMoney"=>"total_money","total_money"=>"next_pay_money","pay","end_line","advance_time"));
		$select->leftjoin(array("cr"=>"contract_rental"),"cr.contract_id=tc.contract_id",array("contract_rental_id"));
		$select -> leftjoin(array("t"=>"tenant"),"cr.tenant_id=t.tenant_id",array("name","phone",'sex'=>"gender"));
		$select->where($house_where);
		if ($houseType == self::HOUSE_TYPE_R){
			$select->order("r.house_id asc");
			$select->order("tc.signing_time asc");
		}
		if ($houseType == self::HOUSE_TYPE_F){
			$select->order("r.room_id asc");
			$select->order("tc.signing_time asc");
		}
		$select->order("cr.tenant_id desc");
		$result = $select->execute();
		//print_r(str_replace('"', '', $select->getSqlString()));die();
		$out_put_data = array();
		if (is_array($houseId)){
			foreach ($result as $key=>$val){
				//集中式
				$advance_time = $val['advance_time'] * 86400;
				if ($houseType==self::HOUSE_TYPE_F){
					$out_put_data[$val['room_id']] = $val;
					if ($this->id==$val['room_id']){
						if ($val['next_pay_time']<$val['end_line']){
							if ($val['next_pay_time']>0)
							{
								if ($val['next_pay_time']>=$val['end_line']){
									$out_put_data[$val['room_id']]['next_pay_time'] = "租金已收完";
									$out_put_data[$val['room_id']]['total_money'] = "租金已收完";
									$out_put_data[$val['room_id']]['is_relet'] = 1;
								}elseif(($val['next_pay_time'] + $advance_time)>=$val['end_line']) {
									$out_put_data[$val['room_id']]['next_pay_time'] = "租金已收完";
									$out_put_data[$val['room_id']]['total_money'] = "租金已收完";
									$out_put_data[$val['room_id']]['is_relet'] = 1;
								}else {
									$out_put_data[$val['room_id']]['next_pay_time'] = date("Y/m/d",$val['next_pay_time']);
									$out_put_data[$val['room_id']]['is_relet'] = 0;
								}
							}
						}
						$room_key[] = $val['room_id'];
					}else {
						if ($val['next_pay_time']>0 && !in_array($val['room_id'], $room_key));
						{
							if ($val['next_pay_time']>=$val['end_line']){
								$out_put_data[$val['room_id']]['next_pay_time'] = "租金已收完";
								$out_put_data[$val['room_id']]['total_money'] = "租金已收完";
								$out_put_data[$val['room_id']]['is_relet'] = 1;
							}elseif(($val['next_pay_time'] + $advance_time)>=$val['end_line']) {
								$out_put_data[$val['room_id']]['next_pay_time'] = "租金已收完";
								$out_put_data[$val['room_id']]['total_money'] = "租金已收完";
								$out_put_data[$val['room_id']]['is_relet'] = 1;
							}else {
								$out_put_data[$val['room_id']]['next_pay_time'] = date("Y/m/d",$val['next_pay_time']);
								$out_put_data[$val['room_id']]['is_relet'] = 0;
							}
							$room_key[] = $val['room_id'];
						}
					}
					$sex_arr = explode(',', $val['sex']);
					if (count($sex_arr)>=2){
						$out_put_data[$val['room_id']]['sex'] = 3;
					}
					$this->id=$val['room_id'];
				}
				//分散式
				if ($houseType==self::HOUSE_TYPE_R){
					$out_put_data[$val['house_id']] = $val;
					$house_key = array();
					if ($this->id == $val['house_id']){
						if ($val['next_pay_time']>=$val['end_line']){
							if ($val['next_pay_time']>0)
							{
								if ($val['next_pay_time']>=$val['end_line']){
									$out_put_data[$val['house_id']]['next_pay_time'] = "租金已收完";
									$out_put_data[$val['house_id']]['total_money'] = "租金已收完";
									$out_put_data[$val['house_id']]['is_relet'] = 1;
								}elseif(($val['next_pay_time'] + $advance_time)>=$val['end_line']) {
									$out_put_data[$val['house_id']]['next_pay_time'] = "租金已收完";
									$out_put_data[$val['house_id']]['total_money'] = "租金已收完";
									$out_put_data[$val['house_id']]['is_relet'] = 1;
								}else {
									$out_put_data[$val['house_id']]['next_pay_time'] = date("Y/m/d",$val['next_pay_time']);
									$out_put_data[$val['house_id']]['is_relet'] = 0;
								}
							}
						}
						$house_key[]=$val['house_id'];
					}else {
						if ($val['next_pay_time']>0 && !in_array($val['house_id'], $house_key)){
							if ($val['next_pay_time']>=$val['end_line']){
								$out_put_data[$val['house_id']]['next_pay_time'] = "租金已收完";
								$out_put_data[$val['house_id']]['total_money'] = "租金已收完";
								$out_put_data[$val['house_id']]['is_relet'] = 1;
							}elseif(($val['next_pay_time'] + $advance_time)>=$val['end_line']) {
								$out_put_data[$val['house_id']]['next_pay_time'] = "租金已收完";
								$out_put_data[$val['house_id']]['total_money'] = "租金已收完";
								$out_put_data[$val['house_id']]['is_relet'] = 1;
							}else {
								$out_put_data[$val['house_id']]['next_pay_time'] = date("Y/m/d",$val['next_pay_time']);
								$out_put_data[$val['house_id']]['is_relet'] = 0;
							}
							$house_key[]=$val['house_id'];
						}
					}
					$sex_arr = explode(',', $val['sex']);
					if (count($sex_arr)>=2){
						$out_put_data[$val['house_id']]['sex'] = 3;
					}
				}
			}
		}else {
			if ($result[0]['next_pay_time']>0)
			{
				if ($result[0]['next_pay_time']>=$result[0]['end_line']){
					$result[0]['next_pay_time'] = "租金已收完";
					$result[0]['total_money'] = "租金已收完";
				}elseif(($result[0]['next_pay_time'] + $result[0]['advance_time'] * 86400)>=$result[0]['end_line']) {
					$result[0]['next_pay_time'] = "租金已收完";
					$result[0]['total_money'] = "租金已收完";
				}else {
					$result[0]['next_pay_time'] = date("Y/m/d",$result[0]['next_pay_time']);
				}
			}
			$sex_arr = explode(',', $result[0]['sex']);
			if (count($sex_arr)>=2){
				$result[0]['sex'] = 3;
			}
			$out_put_data = $result[0];
		}
		//print_r($out_put_data);die();
		return $out_put_data;
	}
	/** 获取分散式合租信息
	 * 修改时间2015年5月14日 11:11:10
	 * 
	 * @author yzx
	 * @param unknown $room_id
	 */
	public function getRoomData($room_id)
	{
		$house_where = new Where();
		if (is_array($room_id)){
			$house_where->in("r.room_id", $room_id);
		}else {
			$house_where->equalTo("r.room_id", $room_id);
		}
		$house_where->equalTo("r.house_type", self::HOUSE_TYPE_R);
		$house_where->equalTo("r.is_delete", 0);
		$select = $this->_sql_object->select(array("r"=>"rental"));
		$select->leftjoin(array('tc'=>'tenant_contract'),new Expression('r.contract_id = tc.contract_id and tc.is_delete=0'),array("next_pay_time","total_money"=>"next_pay_money","totalMoney"=>"total_money","pay","end_line","signing_time","advance_time"));
		$select->leftjoin(array('t'=>'tenant'),new Expression('r.tenant_id=t.tenant_id and t.is_delete=0'),array("gender"));
		$select->leftjoin(array('cr'=>'contract_rental'),new Expression('tc.contract_id=cr.contract_id and cr.is_delete=0'));
		$select->leftjoin(array('ct'=>'tenant'),new Expression('ct.tenant_id=cr.tenant_id and ct.is_delete=0'),array("phone","name","sex"=> "gender"));
		$select->where($house_where);
		$select->order("r.room_id asc");
		$select->order("tc.signing_time asc");
		$select->order('cr.tenant_id desc');
		$result = $select->execute();
// 		print_r($result);die();
		$room_key = array();
		$out_put_data = array();
		if (is_array($room_id)){
			if (!empty($result)){
				foreach ($result as $key=>$val){
				    $advance_time = $val['advance_time'] * 86400;
					$out_put_data[$val['room_id']] = $val;
					if ($this->id == $val['room_id']){
						if ($val['next_pay_time']<$val['end_line']){
							if ($val['next_pay_time']>0)
							{
								if ($val['next_pay_time']>=$val['end_line']){
									$out_put_data[$val['room_id']]['next_pay_time'] = "租金已收完";
									$out_put_data[$val['room_id']]['total_money'] = "租金已收完";
									$out_put_data[$val['room_id']]['is_relet'] = 1;
								}elseif(($val['next_pay_time'] + $advance_time)>=$val['end_line']) {
									$out_put_data[$val['room_id']]['next_pay_time'] = "租金已收完";
									$out_put_data[$val['room_id']]['total_money'] = "租金已收完";
									$out_put_data[$val['room_id']]['is_relet'] = 1;
								}else {
									$out_put_data[$val['room_id']]['next_pay_time'] = date("Y/m/d",$val['next_pay_time']);
									$out_put_data[$val['room_id']]['is_relet'] = 0;
								}
							}
						}
						$room_key[] = $val['room_id'];
					}else{
						if ($val['next_pay_time']>0 && !in_array($val['room_id'], $room_key));
						{
							if ($val['next_pay_time']>=$val['end_line']){
								$out_put_data[$val['room_id']]['next_pay_time'] = "租金已收完";
								$out_put_data[$val['room_id']]['total_money'] = "租金已收完";
								$out_put_data[$val['room_id']]['is_relet'] = 1;
							}elseif(($val['next_pay_time'] + $advance_time)>=$val['end_line']){
								$out_put_data[$val['room_id']]['next_pay_time'] = "租金已收完";
								$out_put_data[$val['room_id']]['total_money'] = "租金已收完";
								$out_put_data[$val['room_id']]['is_relet'] = 1;
							}else {
								$out_put_data[$val['room_id']]['next_pay_time'] = date("Y/m/d",$val['next_pay_time']);
								$out_put_data[$val['room_id']]['is_relet'] = 0;
							}
							$room_key[] = $val['room_id'];
						}
					}
					$sex_arr = explode(',', $val['sex']);
					if (count($sex_arr)>=2){
						$out_put_data[$val['room_id']]['sex'] = 3;
					}
					if (is_numeric($out_put_data[$val['room_id']]['next_pay_time'])){
						$out_put_data[$val['room_id']]['next_pay_time'] = date("Y/m/d",$val['next_pay_time']);
					}
					$this->id=$val['room_id'];
				}
			}
		}else {
			if ($result[0]['next_pay_time']>0)
			{
				if ($result[0]['next_pay_time']>=$result[0]['end_line']){
					$result[0]['next_pay_time'] = "合同到期";
					$result[0]['total_money'] = "租金已收完";
				}elseif(($result[0]['next_pay_time'] + $result[0]['advance_time'] * 86400)>=$result[0]['end_line']){
					$result[0]['next_pay_time'] = "合同到期";
					$result[0]['total_money'] = "租金已收完";
				}else {
					$result[0]['next_pay_time'] = date("Y/m/d",$result[0]['next_pay_time']);
				}
			}
			$sex_arr = explode(',', $result[0]['sex']);
			if (count($sex_arr)>=2){
				$result[0]['sex'] = 3;
			}
			$out_put_data = $result;
		}
		return $out_put_data;
	}
}