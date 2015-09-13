<?php
namespace App\Web\Mvc\Model;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;
use Core\Db\Sql\Select;
class Rental extends Common
{
	/**
	 * 添加租住关系
	 * 修改时间2015年3月19日 11:01:46
	 * 
	 * @author yzx
	 * @param array $data
	 */
	public function addRental($data)
	{
		$result = $this->insert($data);
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
		if ($house_type == $houseModel->_HOUSE_TYPE_HOUSE)
		{
			$select->where(array("r.house_id"=>$house_id));
		}elseif($house_type == $houseModel->_HOUSE_TYPE_ROOM)
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
}