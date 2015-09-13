<?php
namespace Common\Helper\Erp;
class Reserve extends \Core\Object
{
	/**
	 * 添加预订人
	 * 修改时间2015年6月9日14:02:31
	 * 
	 * @author yzx
	 * @param unknown $data
	 * @return number
	 */
	public function add($data)
	{
		if ($data['stime']== $data["etime"]){
			$data['stime'] = (strtotime($data['etime']))+(23*3600);
		}else {
			$data['stime'] = strtotime($data['stime']);
		}
		$reserverModel = new \Common\Model\Erp\Reserve();
		$data['etime'] = strtotime($data['etime']);
		$data['create_time'] = time();
		return $reserverModel->insert($data);
	}
	/**
	 * 根据条件获取数据
	 * 修改时间2015年4月3日 10:56:21
	 * 
	 * @author yzx
	 * @param array $predicate
	 * @return array
	 */
	public function getDataByCondition($houseType,$house_id,$isRoom=false)
	{
		$reserverModel = new \Common\Model\Erp\Reserve();
		$sql=$reserverModel->getSqlObject();
		$select = $sql->select(array("r"=>"reserve"));
		$select->leftjoin(array('t'=>"tenant"), "r.tenant_id=t.tenant_id",array("name","phone"));
		if (\Common\Model\Erp\Reserve::HOUSE_TYPE_F==$houseType)
		{
			$select->where(array("r.room_id"=>$house_id));
		}
		if (\Common\Model\Erp\Reserve::HOUSE_TYPE_R == $houseType)
		{
			if ($isRoom)
			{
				$select->where(array("r.room_id"=>$house_id));
			}else 
			{
				$select->where(array("r.house_id"=>$house_id));
			}
		}
		$select->where(array("r.house_type"=>$houseType));
		$select->where(array("r.is_delete"=>0));
		//print_r(str_replace('"', '', $select->getSqlString()));die();
		return $select->execute();
	}
	
	/**
	 * 根据预定id获取房源id/房间id/房源类型(集中/分散)/定金;
	 * 修改时间 2015年5月26日19:44:09
	 * 
	 * @author ft
	 */
	public function getReserveInfoById($reserve_id) {
	    $reserve_model = new \Common\Model\Erp\Reserve();
	    $sql = $reserve_model->getSqlObject();
	    $select = $sql->select(array('r' => 'reserve'));
	    $select->columns(
	            array(
	                    'reserve_id' => 'reserve_id', 
	                    'tenant_id' => 'tenant_id',
	                    'house_id' => 'house_id',
	                    'room_id' => 'room_id',
	                    'house_type' => 'house_type',
	                    'money' => 'money',
	                    'rental_way' => 'rental_way'
	    ));
	    $where = new \Zend\Db\Sql\Where();
	    $where->equalTo('r.is_delete', 0);
	    $where->equalTo('r.reserve_id', $reserve_id);
	    $select->where($where);
	    return $select->execute();
	}
	/**
	 * 删除房间预定的待办事项
	 * 修改时间 2015年7月28日18:13:55
	 * 
	 * @author ft
	 * @param  (int|array)$reserve_id
	 * @param  array $reserve_data
	 */
	public function delRoomReserveBacklog($reserve_id, $reserve_data) {
	    $reserve_model = new \Common\Model\Erp\Reserve();
	    $todo_model = new \Common\Model\Erp\Todo();
	    //删除待办事件条件
	    $todo_model->Transaction();
	    if ($reserve_data['house_type'] == 1) {
	        if ($reserve_data['room_id'] > 0) {
    	        $module = 'ROOM_RESERVE_OUT';
	        } else {
    	        $module = 'HOUSE_RESERVE_OUT';
	        }
	    } else {
	        $module = 'FOCUS_RESERVE_OUT';
	    }
	    $backlog_where = array('module' => $module , 'entity_id' => $reserve_id);
	    $todo_res = $todo_model->delete($backlog_where);
	    if (!$todo_res) {
	        $todo_model->rollback();
	        return false;
	    }
	    $todo_model->commit();
	    return true;
	}
	/**
	 * 获取集中式房间的名字
	 * 修改时间 2015年7月30日14:13:48
	 * 
	 * @author ft
	 * @param  array room_info
	 */
	public function getFocusName($room_info) {
	    if ($room_info['house_type'] == 1) {
	        $room_model = new \Common\Model\Erp\Room();
	        if ($room_info['rental_way'] == 1) {
	            $sql = $room_model->getSqlObject();
	            $select = $sql->select(array('r' => 'room'));
	            $select->columns(array('room_type' => 'room_type', 'custom_number' => 'custom_number'));
	            $select->leftjoin(array('h' => 'house'), 'r.house_id = h.house_id', array('house_name' => 'house_name'));
	            $where = new \Zend\Db\Sql\Where();
	            $where->equalTo('r.room_id', $room_info['room_id']);
	            $where->equalTo('r.house_id', $room_info['house_id']);
	            $select->where($where);
	            return $select->execute();
	        } else {
	            $house_model = new \Common\Model\Erp\House();
	            return $house_model->getData(array('house_id' => $room_info['house_id']), array('house_name' => 'house_name'));
	        }
	    } else {
	        $room_focus_model = new \Common\Model\Erp\RoomFocus();
	        $sql = $room_focus_model->getSqlObject();
	        $select = $sql->select(array('rf' => 'room_focus'));
	        $select->columns(array('floor' => 'floor', 'custom_number' => 'custom_number'));
	        $select->leftjoin(array('f' => 'flat'), 'rf.flat_id = f.flat_id', array('flat_name' => 'flat_name'));
	        $where = new \Zend\Db\Sql\Where();
	        $where->equalTo('rf.room_focus_id', $room_info['room_id']);
	        $select->where($where);
	        return $select->execute();
	    }
	}
}