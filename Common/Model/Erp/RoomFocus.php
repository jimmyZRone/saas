<?php
namespace Common\Model\Erp;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;
class RoomFocus extends \Common\Model\Erp
{
	private  $floor=null;
	/**
	 * 房源状态
	 * @var unknown
	 */
	/**
	 * 未租
	 * @var unknown
	 */
	const STATUS_NOT_RENTAL = 1;
	/**
	 * 已租
	 * @var unknown
	 */
	const STATUS_RENTAL = 2;
	/**
	 * 停用
	 * @var unknown
	 */
	const STATUS_IS_STOP = 3;

	/**
	 * 是否预约退租
	 * @var unknown
	 */
	const NOT_IS_YYTZ = 0;
	const IS_YYTZ = 1;
	const IS_YD = 1;
	/**
	 * 根据公寓ID获取房间
	 * 修改时间2015年3月25日 13:43:57
	 *
	 * @author yzx
	 * @param int $flatId
	 * @param string $floor
	 * @return unknown|boolean
	 */
	public function getDataByFlatId($flatId,$floor = array())
	{
		$floor_array = array();
		$where = new \Zend\Db\Sql\Where();
		$in_where = new \Zend\Db\Sql\Where();
		$where->equalTo("flat_id", $flatId);
		$where->equalTo("is_delete", 0);
		if (!empty($floor))
		{
			$floor_array = explode(",", $floor);
			$in_where->in("floor",$floor_array);
			$where->addPredicate($in_where);
		}
		$select = $this->_sql_object->select(array("rf"=>"room_focus"));
		$select->leftjoin(array("rtp"=>"room_template_relation"),"rf.room_focus_id = rtp.room_id",array("rtid"=>"template_id"));
		$select->where($where);
		$result = $select->execute();
		if (!empty($result))
		{
			return $result;
		}
		return false;
	}
	/**
	 * 批量跟新房间数据
	 * 修改时间2015年3月25日 15:50:33
	 *
	 * @author yzx
	 * @param int $roomId
	 * @param array $data
	 * @return Ambigous <number, boolean>
	 */
	public function updateData($roomId,$data)
	{
		$attachmentModel = new Attachments();
		$room_data = array();
		$room_data['room_type'] = $data['house_type'];
		$room_data['money'] = $data['money'];
		$room_data['detain'] = $data['pledge_month'];
		$room_data['pay'] = $data['pay_month'];
		$room_data['room_config'] = $data['room_config'];
		$room_data['area'] = $data['area'];
		$attachmentModel->delete(array("module"=>"room_focus","entity_id"=>$roomId));
		if (is_array($data['image']) && !empty($data['image']))
		{
			foreach ($data['image'] as $key=>$val)
			{
				$imag_data['module'] = 'room_focus';
				$imag_data['key'] = $val;
				$imag_data['entity_id'] = $roomId;
				$attachmentModel->insertData($imag_data);
			}
		}
		return $this->edit(array("room_focus_id"=>$roomId), $room_data);
	}

	/**
	 * 添加房间后更新公寓的总楼层数
	 *  最后修改时间 2015-3-25
	 *
	 * @author denghsuang
	 * @param unknown $flat_id
	 * @return boolean
	 */
	public function updateFlatFloor($flat_id,$user){
		$flatModel = new \App\Web\Mvc\Model\Flat();
		$flatInfo = $flatModel->getOne(array('flat_id'=>$flat_id));
		if(empty($flatInfo)){
			return false;
		}
		$select = $this->_sql_object->select($this->_table_name);
		$select->where(array('flat_id'=>$flat_id,'owner_id'=>$user['user_id']));
		$select->group('floor');
		//echo str_replace('"', '', $select->getSqlString());die;
		$result = $select->execute();
		if(!empty($result)){
			$count = count($result);
			if($flatModel->edit(array('flat_id'=>$flat_id), array('total_floor'=>$count))){
				return true;
			}else{
				return false;
			}
		}else{
			return true;
		}
	}

	/**
	 * 获取用户下所有房间的类型
	 *  最后修改时间 2015-3-25
	 *
	 * @author dengshuang
	 * @param unknown $flat_id
	 * @return unknown|boolean
	 */
	public function getUserRoomType($flat_id){
		$userModel = new \App\Web\Mvc\Model\User();
		$user = $userModel->getCurrentUser();
		$select = $this->_sql_object->select($this->_table_name);
		$select->where(array('flat_id'=>$flat_id,'manager_id'=>$user['user_id']));
		$select->group('room_type');
		$select->columns(array('room_type'));
		$result = $select->execute();
		//echo str_replace('"', '', $select->getSqlString());//die;
		if($result){
			return $result;
		}else{
			return false;
		}
	}

	/**
	 * 获取房间列表
	 *  最后修改时间 2015-3-27
	 *
	 * @author dengshuang
	 * @param unknown $flat_id
	 * @param unknown $page
	 * @param unknown $size
	 * @return multitype:Ambigous <number, unknown> unknown
	 */
	public function getListData($flat_id,$user,$page,$size,$searchStr=null,$roomId=0){
		$where_id = new \Zend\Db\Sql\Where();
		$where_cid = new \Zend\Db\Sql\Where();
		$where_delete = new \Zend\Db\Sql\Where();
		$where_id->equalTo("rf.flat_id", $flat_id);
		$where_cid->equalTo("rf.company_id", $user['company_id']);
		$where_cid->addPredicate($where_id);
		$where_delete->equalTo("rf.is_delete", 0);
		$where_cid->addPredicate($where_delete);
		$roomFocusHelper = new \Common\Helper\Erp\RoomFocus();
		$select = $this->_sql_object->select(array("rf"=>$this->_table_name));
		$select->leftjoin(array("r"=>"rental"), new Expression("rf.room_focus_id=r.room_id and r.is_delete=0"),array("rental_id"));
		$select->leftjoin(array("t"=>"tenant"), new Expression("r.tenant_id = t.tenant_id and t.is_delete=0"),array("gender"));
		$select->leftjoin(array("tc"=>"tenant_contract"),"r.contract_id = tc.contract_id",array("contract_id"));
		$select->leftjoin(array("cr"=>'contract_rental'),new Expression("tc.contract_id=cr.contract_id and cr.is_delete=0"),array("contract_rental_id"));
		$select->leftjoin(array('ct'=>'tenant'),new Expression("cr.tenant_id=ct.tenant_id and ct.is_delete=0"),array("sex"=>new Expression("GROUP_CONCAT(DISTINCT ct.gender)")));
		if (!empty($searchStr))
		{
			if ($searchStr['search_str']!='')
			{
				$where_str = new \Zend\Db\Sql\Where();
				$str = strval($searchStr['search_str']);
				$where_str->like("rf.custom_number", new Expression("'%".$str."%'"));
			}
			if ($searchStr['room_type']!='' && $searchStr['room_type']!='0')
			{
				$where_type = new \Zend\Db\Sql\Where();
				$where_type->equalTo('rf.room_type', $searchStr['room_type']);
			}
			if ($searchStr['search_str']!='' && $searchStr['room_type']!='' && $searchStr['room_type']!='0')
			{
				$where_type->addPredicate($where_str,\Zend\Db\Sql\Where::OP_OR);
				$where_cid->addPredicate($where_type,\Zend\Db\Sql\Where::OP_AND);
			}
			if ($searchStr['search_str']!='' && $searchStr['room_type']=='0')
			{
				$where_cid->addPredicate($where_str);
			}
			if ($searchStr['room_type']!='' && $searchStr['room_type']!='0' && $searchStr['search_str']=='')
			{
				$where_cid->addPredicate($where_type);
			}
		}
		if ($roomId>0)
		{
			$room_where = new \Zend\Db\Sql\Where();
			$room_where->equalTo("rf.room_focus_id", $roomId);
			$where_cid->addPredicate($room_where);
		}
		$select->where($where_cid);
		$select->order(new Expression('CAST(rf.floor AS SIGNED) ASC'));
		$select->order("rf.custom_number asc");
		$select->group(" rf.room_focus_id");
		//print_r(str_replace('"', '', $select->getSqlString()));die();
		$result = $select->execute();
		$data['data'] = $roomFocusHelper->getRoomFloor($result);
		return $data;
	}

	/**
	 * 获取所有房间
	 *  最后修改时间 2015-3-27
	 *
	 * @author dengshuang
	 * @param unknown $flat_id
	 * @return unknown|multitype:
	 */
	public function getAllData($flat_id){
		$userModel = new \App\Web\Mvc\Model\User();
		$user = $userModel->getCurrentUser();
		$select = $this->_sql_object->select(array('rf'=>$this->_table_name));
		$select->where(array('rf.flat_id'=>$flat_id,'rf.manager_id'=>$user['user_id']));

		$select->leftjoin(array('r'=>'reserve'),'rf.room_focus_id = r.room_focus_id',array('tenant_id'));
		$data = $select->execute();
		if($data){
			return $data;
		}else{
			return array();
		}
	}

	/**
	 * 获取房间数
	 *  最后修改时间 2015-3-27
	 *
	 * @author dengshuang
	 * @param unknown $user
	 * @param unknown $where
	 * @param unknown $flat_id
	 * @return Ambigous <number, boolean>
	 */
	public function getRoomTotal($user,$where = array(),$flat_id){
		$where['manager_id'] = $user['user_id'];
		$where['flat_id'] = $flat_id;
		return $this->getCount($where);
	}

	/**
	 * 获取预定房间数
	 *  最后修改时间 2015-3-27
	 *
	 * @author dengshuang
	 * @param unknown $user
	 * @param unknown $flat_id
	 * @return number
	 */
	public function getRoomReserve($user,$flat_id){
		$select = $this->_sql_object->select(array('rf'=>$this->_table_name));
		$select->leftjoin(array('r'=>'reserve'),'rf.room_focus_id = r.room_focus_id',array('tenant_id'));
		$select->columns(array('count'=>new \Zend\Db\Sql\Expression('count(*)')));
		$where = new \Zend\Db\Sql\Where();
		$where->equalTo('rf.manager_id', $user['user_id']);
		$where->equalTo('rf.flat_id', $flat_id);
		$where->isNotNull('rf.tenant_id');
		$select->where($where);
		$count = $select->execute();
		if($count){
			$count = isset($count[0]['count'])?$count[0]['count']:0;
			return $count;
		}else{
			return 0;
		}
	}

	/**
	 * 平均租金
	 *  最后修改时间 2015-3-27
	 *
	 * @author dengshuang
	 * @param unknown $user
	 * @param unknown $flat_id
	 * @return number
	 */
	public function getAverageRent($user,$flat_id){
		$number = $this->getRoomTotal($user, array('status'=>2),$flat_id);
		$select = $this->_sql_object->select(array('rf'=>$this->_table_name));
		$select->columns(array('total'=>new \Zend\Db\Sql\Expression('SUM(rf.money)')));
		$select->where(array('rf.status'=>2,'rf.flat_id'=>$flat_id));
		$sum = $select->execute();
		if($sum){
			$sum = isset($sum[0]['total'])?$sum[0]['total']:0;
		}else{
			$sum = 0;
		}
		if(empty($sum)||empty($number)){
			return  0;
		}else{
			return intval($sum/$number);
		}
	}

	/**
	 * 获取平均租金
	 *  最后修改时间 2015-3-27
	 *
	 * @author dengshuang
	 * @param unknown $user
	 * @param unknown $flat_id
	 * @return number
	 */
	public function getRentPercent($user,$flat_id){
		$number = $this->getRoomTotal($user, array('status'=>2),$flat_id);
		$number_toal = $this->getRoomTotal($user,$flat_id);
		if(empty($number)||empty($number_toal)){
			return  0;
		}else{
			return intval($number_toal/$number);
		}
	}

	/**
	 * 月/年空置率
	 *  最后修改时间 2015-3-27
	 *	月30 年365
	 *
	 * @author dengshuang
	 * @param unknown $user
	 * @param unknown $days
	 * @param unknown $flat_id
	 * @return number
	 */
	public function getEmptyPercent($user,$days,$flat_id){
		$number = $this->getRoomTotal($user, array('status'=>2));
		$select = $this->_sql_object->select(array('rf'=>$this->_table_name));
		$select->columns(array('sum'=>new \Zend\Db\Sql\Expression('sum(ceil((unix_timestamp(now())-rf.last_out_time)/(3600*24))))')));
		$select->where(array('rf.status'=>1));
		$select->where(array('rf.flat_id'=>$flat_id));
		$res = $select->execute();
		if($res){
			$sum = isset($res[0]['sum'])?$res[0]['sum']:0;
		}else{
			$sum = 0;
		}
		$number_toal = $this->getRoomTotal($user);
		if(empty($sum)||empty($number_toal)){
			return  0;
		}else{
			return intval($sum/($number_toal*$days));
		}
	}
	/**
	 * 修改房间出租状态为已出租
	 * @param $id 房间id
	 * @param $sid 状态 1,未租,2已租,3停用
	 * @author too|编写注释时间 2015年5月14日 下午4:53:49
	 */
	public function changStatus($id, $sid=Room::STATUS_NOT_RENTAL)
	{
		$reserveModel = new Reserve();
		$reserve_data = $reserveModel->getData(array("house_type"=>$reserveModel::HOUSE_TYPE_F,"room_id"=>$id,"is_delete"=>0));
		if (empty($reserve_data)){
			$data['is_yd'] = 0;
		}
	    $where = array('room_focus_id'=>$id);
	    $data['status'] = $sid;
	    $data['is_yytz'] = 0;
        return $this->edit($where,$data);
	}
	/**
	 * 根据房间查询合同
	 * 修改时间  2015年7月3日19:37:53
	 * 
	 * @author ft
	 * @param  int $room_focus_id
	 */
	public function searchContractInfoByRoom($room_focus_id) {
	    $room_focus_model = new \Common\Model\Erp\RoomFocus();
	    $sql = $room_focus_model->getSqlObject();
	    $select = $sql->select(array('rf' => 'room_focus'));
	    $select->columns(array());
	    $select->leftjoin(array('re' => 'rental'), 'rf.room_focus_id = re.room_id', array('contract_id' => 'contract_id'));
	    $where = new \Zend\Db\Sql\Where();
	    $where->equalTo('re.room_id', $room_focus_id);
	    $where->equalTo('re.house_type', 2);
	    $where->equalTo('re.is_delete', 0);
	    $select->where($where);
        return $select->execute();
	}
}