<?php
namespace Common\Model\Erp;
use Core\Db\Sql\Select;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Where;
class Flat extends \Common\Model\Erp
{
	const RENTAL_WAY_INTEGRAL = 2;
	const RENTAL_WAY_CLOSE = 1;
	public function getFlatData($flat_id)
	{
		$result = $this->getOne(array("flat_id"=>$flat_id));
		return $result;
	}
	/**
	 * 获取公寓列表
	 * @param int $pageSize
	 * @param int $page
	 * @return multitype:multitype:Ambigous <number, unknown> number  Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >
	 */
	public function flatList($page,$user,$pageSize=100,$where = array())
	{
		$select = $this->_sql_object->select(array("f"=>"flat"));
		if(is_array($where)){
			$where['f.company_id'] = $user['company_id'];
			$where['f.is_delete'] = 0;
		}else if($where instanceof \Zend\Db\Sql\Where){
			$where->equalTo('f.company_id', 'company_id');
			$where->equalTo('f.is_delete', 0);
		}
		$select->where($where);
		//权限
		if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
		{
			$permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
			$permisions->VerifyDataCollectionsPermissionsModel($select , 'f.flat_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
		}
		$result = Select::pageSelect($select,null,$page, $pageSize);
		return $result;
	}
	/**
	 * 添加公寓
	 * 修改时间2015年3月24日 10:09:32
	 * 
	 * @author yzx
	 * @param array $data
	 * @return number
	 */
	public function addFlat($data,$user,$feeData=array())
	{
		//$stime=microtime(true);
		$floorNumberModel = new FloorNumber();
		$roomTemplatRelationModel = new RoomTemplateRelation();
		$flatHelper = new \Common\Helper\Erp\Flat();
		$feeHelper = new \Common\Helper\Erp\Fee();
		$flat_data = $this->getData(array("company_id"=>$user['company_id'],"is_delete"=>0),array("flat_name"));
		$flat_data_name = array();
		foreach ($flat_data as $key=>$val)
		{
			$flat_data_name[]=$val['flat_name'];
		}
		$data['flat_name'] = trim($data['flat_name']);
		if (in_array($data['flat_name'], $flat_data_name))
		{
			return false;
		}
		$this->Transaction();
		$new_flat_id = $this->insert($data);
		if (!$new_flat_id)
		{
			$this->rollback();
			return false;
		}
		$data['flat_id'] = $new_flat_id;
		$floor_number_data = $flatHelper->getFloorNumber($data['total_floor']);
		$house_numbeer_data = $flatHelper->getHouseNumber($new_flat_id);
		$room_number_data = $flatHelper->getDiyRoomNumber($new_flat_id);
		//第一次没有找到session就重新处理数据
		if (!$house_numbeer_data)
		{
			$house_numbeer_factory = $flatHelper->houseNumberFactory($data['group_number']);
			$house_number_creat_data = $flatHelper->houseNumberCreat($house_numbeer_factory);
			$house_numbeer_data = $flatHelper->getHouseNumber($new_flat_id,$data);
		}
		if ($data['rental_way'] == self::RENTAL_WAY_CLOSE)
		{
			if (empty($room_number_data))
			{
				$diy_room_data = $flatHelper->CreatRoomCount($data['room_number'],$new_flat_id,$data['rental_way']);
				$house_numbeer_data = $diy_room_data['room_floor'];
			}else 
			{
				$house_numbeer_data = $room_number_data;
			}
		}
		$fresult = $floorNumberModel->addFoolNumber($floor_number_data,$new_flat_id);
		if (!$fresult)
		{
			$this->rollback();
			return false;
		}
		if (count($house_numbeer_data)>1000){
			return array("status"=>false,"message"=>"房源总间数不能超过1000");
		}
		if ($data['rental_way'] == self::RENTAL_WAY_INTEGRAL)
		{
			$RTR_RESULT = $roomTemplatRelationModel->houseFactory($house_numbeer_data,$user);
		}else 
		{
			/* $roomNumberModel = new RoomNumber();
			$roomNumberModel->addData($new_flat_id,$data['room_number']); */
			$RTR_RESULT = $roomTemplatRelationModel->houseFactory($house_numbeer_data,$user,false);
		}
		if (!$RTR_RESULT)
		{
			$this->rollback();
			return false;
		}
		if (!empty($feeData))
		{
			$feeHelper->addFee($feeData,\Common\Model\Erp\Fee::SOURCE_FLAT,$new_flat_id);
		}

		$this->commit();
		//$etime=microtime(true);
		return $new_flat_id;
	}
	/**
	 * 修改公寓
	 * 修改时间2015年3月25日 16:42:01
	 * 
	 * @author yzx
	 * @param int $flatId
	 * @param array $data
	 * @return Ambigous <number, boolean>
	 */
	public function editFlat($flatId,$data,$feeData=array())
	{
		$feeHelper = new \Common\Helper\Erp\Fee();
		$falt_data = $this->getOne(array("flat_id"=>$flatId));
		if (!empty($falt_data))
		{
			$feeHelper->addFee($feeData,\Common\Model\Erp\Fee::SOURCE_FLAT,$flatId);
			if($this->edit(array('flat_id'=>$flatId), $data)){
                $link =  \Core\Config::get('db:default');
                $adapter = new \Zend\Db\Adapter\Adapter($link);
                $sql = "update room_focus as rf join flat as f on f.flat_id = rf.flat_id set rf.full_name = CONCAT(f.flat_name,rf.floor,'楼',rf.custom_number,'号')";
                $rs = $adapter->query($sql,Adapter::QUERY_MODE_EXECUTE);
                $sql2 = "update room as r join house as h on r.house_id = h.house_id set r.full_name = concat(h.house_name,if((`r`.`room_type` = 'main'),'主卧',if((`r`.`room_type` = 'guest'),'客卧','次卧')),`r`.`custom_number`,'号')";
                $rs2 = $adapter->query($sql2)->execute();
//            if($rs->count() > 0){
//                echo "DONE";
//            }
                return true;
            }
		}
		return false;
	}
	/**
	 * 删除公寓
	 * 修改时间2015年6月4日20:02:09
	 * 
	 * @author yzx
	 * @param unknown $flatId
	 */
	public function deleteFlat($flatId)
	{
		$roomFocusModel = new RoomFocus();
		$roomNumberModel = new RoomNumber();
		$where = new Where();
		$yd_where = new Where();
		$f_where = new Where();
		$d_where = new Where();
		$d_where->equalTo("is_delete", 0);
		$where->equalTo("status", $roomFocusModel::STATUS_RENTAL);
		$yd_where->equalTo("is_yd", $roomFocusModel::IS_YYTZ);
		$yd_where->addPredicate($where,Where::OP_OR);
		$f_where->equalTo("flat_id", $flatId);
		$f_where->addPredicate($yd_where);
		$f_where->addPredicate($d_where);
		$sql = $roomFocusModel->getSqlObject();
		$select = $sql->select("room_focus");
		$select->where($f_where);
		$result = $select->execute();
		if (!empty($result)){
			return array("status"=>false,"msg"=>"该公寓下面有已租或者预定房间请处理后删除");
		}
		
		$flat_data = $this->getOne(array("flat_id"=>$flatId));
		$is_delete = $this->edit(array("flat_id"=>$flatId), array("is_delete"=>1));
		if ($is_delete){
			$is_delete_r=$roomFocusModel->edit(array("flat_id"=>$flatId), array("is_delete"=>1));
		}
		if ($is_delete_r){
			
			//写快照
			\Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_FOCUS_FLAT_DELETE, $flatId, $flat_data);
			
			return array("status"=>true);
		}
		return array("status"=>false,"msg"=>"未知错误");
	}
	/**
	 * 批量查询房间
	 * 修改时间2015年6月29日19:50:42
	 *
	 * @author yzx
	 * @param unknown $flatId
	 * @return unknown|multitype:
	 */
	public function getRoomData($flatId,$user){
		$userModel = new \App\Web\Mvc\Model\User();
		$where = new Where();
		$where->in('rf.flat_id',$flatId);
		$where->equalTo('rf.company_id', $user['company_id']);
		$select = $this->_sql_object->select(array('rf'=>$this->_table_name));
		$select->where($where);
		$data = $select->execute();
		if($data){
			return $data;
		}else{
			return array();
		}
	}
}