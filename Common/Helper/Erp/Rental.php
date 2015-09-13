<?php
namespace Common\Helper\Erp;
/**
 * 租住表helper
 * @author ft
 * 最后修改时间 2015年3月26日 上午10:54:10
 *
 */
class Rental extends \Core\Object {
    /**
     * 根据租客合同id 获取房间的全部租金 以及房间的名称信息等
     * 修改时间 2015年6月2日11:22:49
     * 
     * @author ft
     */
    public function getRoomName($rental_info) {
        $rental_model = new \Common\Model\Erp\Rental();
        $room_type = array('main' => '主卧', 'second' => '次卧', 'guest' => '客卧');
        $sql = $rental_model->getSqlObject();
        if ($rental_info['house_type'] == 1) {//分散式
            if ($rental_info['room_id'] > 0) {//合租
                $select = $sql->select(array('r' => 'room'));
                $select->columns(array('r_custom_num' => 'custom_number', 'room_type' => 'room_type'));
                $select->leftjoin(
                        array('h' => 'house'), 
                        'r.house_id = h.house_id', 
                        array(
                                'h_name' => 'house_name',
                                'h_custom_num' => 'custom_number',
                                'h_unit' => 'unit',
                                'h_cost' => 'cost',
                                'h_number' => 'number',
                                'rental_way' => 'rental_way'
                ));
                $where = new \Zend\Db\Sql\Where();
                $where->equalTo('h.house_id', $rental_info['house_id']);
                $where->equalTo('r.room_id', $rental_info['room_id']);
                $where->equalTo('r.is_delete', 0);
                $where->equalTo('h.is_delete', 0);
                $select->where($where);
                $shared_data = $select->execute();
                $shared_data[0]['room_type'] = $room_type[$shared_data[0]['room_type']];
                return $shared_data;
            } else {//整租
                $select = $sql->select(array('h' => 'house'));
                $select->columns(array('h_name' => 'house_name', 'h_custom_num' => 'custom_number', 'h_unit' => 'unit', 'h_cost' => 'cost', 'h_number' => 'number', 'rental_way' => 'rental_way'));
                $where = new \Zend\Db\Sql\Where();
                $where->equalTo('h.house_id', $rental_info['house_id']);
                $where->equalTo('h.is_delete', 0);
                $select->where($where);
                return $select->execute();
            }
        } else {//集中式
            $select = $sql->select(array('rf' => 'room_focus'));
            $select->columns(array('floor' => 'floor', 'rf_custom_num' => 'custom_number'));
            $select->leftjoin(
                    array('f' => 'flat'), 
                    'rf.flat_id = f.flat_id', 
                    array(
                            'flat_name' => 'flat_name', 
                            'f_custom_num'=> 'custom_number', 
                            'address' => 'address'
            ));
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('rf.room_focus_id', $rental_info['room_id']);
            $where->equalTo('rf.is_delete', 0);
            $where->equalTo('f.is_delete', 0);
            $select->where($where);
            return $select->execute();
        }
    }
    /**
     * 根据租客合同id 获取房间的全部租金 以及房间的名称信息等
     * 修改时间 2015年6月2日11:22:49
     * 
     * @author ft
     */
    public function getRentInfoById($contract_id) {
        $rental_model = new \Common\Model\Erp\Rental();
        $sql = $rental_model->getSqlObject();
        $select = $sql->select(array('re' => 'rental'));
        $select->columns(array('house_id' => 'house_id', 'room_id' => 'room_id', 'house_type' => 'house_type'));
        $select->leftjoin(
                array('tc' => 'tenant_contract'), 
                're.contract_id = tc.contract_id', 
                array(
                        'rent' => 'rent',
                        'pay' => 'pay',
                        'deposit' => 'deposit',
                        'next_pay_money' => 'next_pay_money',
                ));
        $where = new \Zend\Db\Sql\Where();
        $where->equalTo('re.contract_id', $contract_id);
        $where->equalTo('re.is_delete', 0);
        $select->where($where);
        return $select->execute();
    }
    /**
     * 退租
     * 修改时间2015-6-10 10:31:49
     * 
     * @author yzx
     * @param unknown $id
     * @param unknown $houseType
     * @param string $isRoom
     */
    public function rentalOut($id,$houseType,$isRoom=false){
    	$rental_model = new \Common\Model\Erp\Rental();
    	$houseHelper = new \Common\Helper\Erp\House();
    	$contractModel = new \Common\Model\Erp\TenantContract();
    	$roomFocusHelper = new \Common\Helper\Erp\RoomFocus();
    	$sql = $rental_model->getSqlObject();
    	$select = $sql->select($rental_model->getTableName());
    	if ($houseType==$rental_model::HOUSE_TYPE_R){
    		if ($isRoom){
    			$select->where(array("room_id"=>$id,"house_type"=>$houseType,"is_delete"=>0));
    			$data['room_id'] = $id;
    			$contract_id = $houseHelper->getHouseContract($data,true);
    		}else {
    			$select->where(array("house_id"=>$id,"house_type"=>$houseType,"is_delete"=>0));
    			$data['house_id'] = $id;
    			$contract_id = $houseHelper->getHouseContract($data);
    		}
    	} 
    	if($houseType==$rental_model::HOUSE_TYPE_F){
    		$select->where(array("room_id"=>$id,"house_type"=>$houseType,"is_delete"=>0));
    		$data['room_id'] = $id;
    		$contract_id = $roomFocusHelper->getFocusRoomContract($data);
    	}
    	$result = $select->execute();
    	if (!empty($result)){
    		$rental_model->edit(array("contract_id"=>$contract_id), array("is_delete"=>1));
    		$contractModel->edit(array("contract_id"=>$contract_id), array("is_stop"=>1,"end_line"=>time(),"next_pay_time"=>time()));
    		return true;
    	}
    	return false;
    }
    /**
     * 通过合同获取租住信息
     * 修改时间2015年6月17日16:36:20
     * 
     * @author yzx
     * @param int $contractId
     * @return Ambigous <NULL, Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown>>|multitype:
     */
    public function getOneDateByContract($contractId){
    	$RentalModel = new \Common\Model\Erp\Rental();
    	$sql = $RentalModel->getSqlObject();
    	$select = $sql->select($RentalModel->getTableName());
    	$select->where(array("contract_id"=>$contractId));
    	$result = $select->execute();
    	if (!empty($result)){
    		return $result[0];
    	}
    	return array();
    }
    /**
     * 获取该房间的最新合同id
     * 修改时间 2015年7月29日11:07:41
     * 
     * @autho ft
     * @param array $room_info
     */
    public function getRoomNewestContractId($room_info) {
        $rental_model = new \Common\Model\Erp\Rental();
        $column = array('contract_id' => 'contract_id');//列共用
        if ($room_info['house_type'] == 1) {
            $condition = array('room_id' => $room_info['room_id'], 'house_id' => $room_info['house_id'], 'house_type' => $room_info['house_type']);
            $contract_id = $rental_model->getData($condition, $column, 0, 0, 'contract_id DESC');
        } else {
            $condition = array('room_id' => $room_info['room_focus_id'], 'house_type' => $room_info['house_type']);
            $contract_id = $rental_model->getData($condition, $column, 0, 0, 'contract_id DESC');
        }
        return $contract_id;
    }
}