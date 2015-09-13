<?php

    namespace App\Api\Helper;
    use Zend\Db\Sql\Expression;
    use Zend\Db\Sql\Where;
    class Room extends \Common\Helper\Erp\Room
    {

//        public function getRoomInfo($houseId)
//        {
//            $roomModel = new \Common\Model\Erp\Room();
//            $sql = $roomModel->getSqlObject();
//            $select = $sql->select($roomModel->getTableName());
//            $select->where(array("house_id" => $houseId));
//            $select->columns(array('custom_number' , 'area' , 'status' , 'money' , 'detain' , 'pay' , 'occupancy_number' , 'room_config' , 'room_type'));
//            $result = $select->execute();
//            return $result;
//        }

        public function getRoomContract($company_id , $house_type , $house_id , $room_id, $res = NULL)
        {
            $M = M('TenantContract');
            $sql = $M->getSqlObject();
            $select = $sql->select(array('tc' => 'tenant_contract'));
            $select->columns(array(
                        'contract_id' ,
                        'custom_number' ,
                        'rent' ,
                        'advance_time' ,
                        'signing_time' ,
                        'dead_line' ,
                        'parent_id' ,
                        'end_line' ,
                        'deposit' ,
                        'detain' ,
                        'pay' ,
                        'next_pay_time' ,
                        'deposit' ,
                        'remark' ,
                    ));
                    $select->join(
                            array('r' => 'rental')
                            , 'tc.contract_id=r.contract_id'
                            , array(
                        'house_id' , 'room_id' , 'house_type' , 'tenant_id'
                    ));
                    $where = new Where();
                    $where->equalTo('r.house_id', $house_id);
                    $where->equalTo('r.room_id', $room_id);
                    $where->equalTo('tc.company_id', $company_id);
                    $where->equalTo('r.house_type', $house_type);
                    $where->equalTo('tc.is_delete', 0);
                    if ($res)
                    {
                        $where->lessThanOrEqualTo('tc.signing_time', time());
                        $where->greaterThanOrEqualTo('tc.end_line', time());
                    }
                    else 
                    {
                        $where->equalTo('tc.is_stop', 0);
                    }
                   
                    $select->where($where);
                    $select->order('contract_id desc');
                    return $select->execute();
        }

    }
    