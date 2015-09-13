<?php

namespace App\Web\Helper;

use Common\Model\Erp\Rental;
use Common\Model\Erp\ContractRental;
use Common\Model\Erp\Tenant;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;
use Zend\Db\Adapter;

class TenantContract extends \Common\Model\Erp
{

    /**
     * 添加合同
     * @author too|最后修改时间 2015年4月17日 下午6:08:02
     */
    public function addTenantContract($data , $imag = array())
    {
        $TenantContract_id = $this->insert($data);
        if (!empty($imag))
        {
            $attachmentModle = new \Common\Model\Erp\Attachments();
            foreach ($imag as $val)
            {
                if ($val != null && $val != '')
                {
                    $img_data['key'] = $val;
                    $img_data['module'] = "tenant_contract";
                    $img_data['entity_id'] = $TenantContract_id;
                    $attachmentModle->insertData($img_data);
                }
            }
        }
        return $TenantContract_id;
    }

    /**
     * 修改合同
     * @author too|最后修改时间 2015年4月17日 下午6:09:49
     */
    public function editTenantContract($data , $contract_id , $isEdit = false)
    {
        $rentalModel = new Rental();
        $meterReadingModel = new \Common\Model\Erp\MeterReading();
        $todoModel = new \Common\Model\Erp\Todo();
        $contractRentalModel = new \Common\Model\Erp\ContractRental();
        $tenantComtractHelper = new \Common\Helper\Erp\TenantContract();
        $TenantContract_data = $this->getOne(array("contract_id" => $contract_id));
        if (!empty($TenantContract_data))
        {
            if ($isEdit)
            {
                $rental_data = $rentalModel->getOne(array("contract_id" => $contract_id , "is_delete" => 0));
                //修改集中式房间状态
                if ($rental_data['house_type'] == $rentalModel::HOUSE_TYPE_F)
                {
                    $roomFocusModel = new \Common\Model\Erp\RoomFocus();
                    $roomFocusHelper = new \Common\Helper\Erp\RoomFocus();
                    //所有合同数据
                    $contract_data = $tenantComtractHelper->getContractDataById($rental_data['room_id'], \Common\Model\Erp\Rental::HOUSE_TYPE_F);
					if (count($contract_data)<=1){
						$roomFocusModel->edit(array("room_focus_id" => $rental_data['room_id']) , array("status" => $roomFocusModel::STATUS_NOT_RENTAL , "is_yytz" => 0));
					}
                    $meterReadingModel->deleteByRentalOut($meterReadingModel::HOUSE_TYPE_F , 0 , $rental_data['room_id']);
                    //删除提醒START
                    $todoModel->deleteTodo($todoModel::MODEL_ROOM_FOCUS_STOP , $rental_data['room_id']);
                    $todoModel->deleteTodo($todoModel::MODEL_ROOM_FOCUS_RESERVE , $rental_data['room_id']);
                    $todoModel->deleteTodo($todoModel::MODEL_ROOM_FOCUS_RESERVE_OUT , $rental_data['room_id']);
                    //删除提醒END
                    $rentalModel->edit(array("rental_id" => $rental_data['rental_id']) , array("is_delete" => 1));
                    //$contractRentalModel->edit(array("contract_id"=>$contract_id),  array("is_delete"=>1));
                }
                //修改分散式整租状态
                if ($rental_data['house_type'] == $rentalModel::HOUSE_TYPE_R)
                {
                    if ($rental_data['room_id'] == 0)
                    {
                        $houseModel = new \Common\Model\Erp\HouseEntirel();
                        //所有合同数据
                        $contract_data = $tenantComtractHelper->getContractDataById($data['house_id'], \Common\Model\Erp\Rental::HOUSE_TYPE_R);
                        if (count($contract_data)<=1){
                        	$houseModel->edit(array("house_id" => $rental_data['house_id']) , array("status" => \Common\Model\Erp\House::STATUS_NOT_RENTAL , "is_yytz" => 0));
                        }
                        $meterReadingModel->deleteByRentalOut($meterReadingModel::HOUSE_TYPE_C , $rental_data['house_id']);
                        //删除提醒START
                        $todoModel->deleteTodo($todoModel::MODEL_HOUSE_STOP , $rental_data['house_id']);
                        $todoModel->deleteTodo($todoModel::MODEL_HOUSE_RESERVE , $rental_data['house_id']);
                        $todoModel->deleteTodo($todoModel::MODEL_HOUSE_RESERVE_OUT , $rental_data['house_id']);
                        //删除提醒END
                        $rentalModel->edit(array("rental_id" => $rental_data['rental_id']) , array("is_delete" => 1));
                        //$contractRentalModel->edit(array("contract_id"=>$contract_id),  array("is_delete"=>1));
                    }
                    //修改分散式合租状态
                    if ($rental_data['house_type'] == $rentalModel::HOUSE_TYPE_R)
                    {
                        if ($rental_data['room_id'] > 0)
                        {
                            $roomModel = new \Common\Model\Erp\Room();
                            $contract_data = $tenantComtractHelper->getContractDataById($rental_data['room_id'], \Common\Model\Erp\Rental::HOUSE_TYPE_R,true);
                            if (count($contract_data)<=1){
                            	$roomModel->edit(array("room_id" => $rental_data['room_id']) , array("status" => \Common\Model\Erp\House::STATUS_NOT_RENTAL , "is_yytz" => 0));
                            }
                            $meterReadingModel->deleteByRentalOut($meterReadingModel::HOUSE_TYPE_C , 0 , $rental_data['room_id']);
                            //删除提醒START
                            $todoModel->deleteTodo($todoModel::MODEL_ROOM_STOP , $rental_data['room_id']);
                            $todoModel->deleteTodo($todoModel::MODEL_ROOM_RESERVE , $rental_data['room_id']);
                            $todoModel->deleteTodo($todoModel::MODEL_ROOM_RESERVE_OUT , $rental_data['room_id']);
                            //删除提醒END
                            $rentalModel->edit(array("rental_id" => $rental_data['rental_id']) , array("is_delete" => 1));
                            //$contractRentalModel->edit(array("contract_id"=>$contract_id),  array("is_delete"=>1));
                        }
                    }
                }
            }
            $result = $this->edit(array("contract_id" => $contract_id) , $data);
            if ($result)
            {
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * 获取单条合同
     * @author too|最后修改时间 2015年4月17日 下午6:10:32
     */
    /* TenantContract  合同表  is_delete
      Tenant				租客表
      ContractRental	每个租客对应合同 is_delete
      Rental				房源,一个租客,合同 is_delete
      getAllTenantContract */
    public function getTenantContract($contract_id)
    {
        $select = new \App\Web\Helper\TenantContract();
        $sql = $select->getSqlObject();
        $select = $sql->select(array('tc' => $select->getTableName('tenant_contract')));
        $select->leftjoin(array('r' => 'rental') , 'r.contract_id=tc.contract_id' , array('rental_id' , 'house_type' , 'r_house_id' => 'house_id' , 'r_room_id' => 'room_id' , 'r_house_type' => 'house_type'));
        $select->leftjoin(array('cr' => 'contract_rental') , 'cr.contract_id=tc.contract_id');
        $select->join(array('hfv' => 'house_focus_view') , new \Zend\Db\Sql\Predicate\Expression('r.house_id = hfv.house_id and r.room_id=hfv.record_id') , array('house_name' , 'rental_way' , 'h_custom_number' => 'custom_number') , $select::JOIN_LEFT);
        $where = new \Zend\Db\Sql\Where();
        $where->equalTo('tc.contract_id' , $contract_id);
        $select->where($where);
        //print_r(str_replace("'", '', $select->getSqlString()));die();
        return $select->execute();
    }


    /**
     * 当前company 集中式分散式哪个多
     * @param $company_id
     * @return mixed array
     */
    public function getMaxHouseValue($company_id){
        $db = $this->getSqlObject();
        $query = $db->select('tenant_contract');
        $query->leftjoin('rental','tenant_contract.contract_id = rental.contract_id',['house_type']);
        $query->columns([
                'total'=>new \Zend\Db\Sql\Expression('count(*)')
            ]);
        $query->where("tenant_contract.company_id = $company_id");
        $query->andWhere('tenant_contract.is_delete=0');
        $query->group('rental.house_type');
        $query->order('total desc');
        $rs = $query->execute();
//        echo $query->getSqlString();exit;
        return $rs[0];
    }


    /**
     * 通过合同id获取合同下的所有租客
     * @author too|最后修改时间 2015年4月23日 下午8:55:13
     *
    cr.contract_id,
    cr.contract_rental_id
    cr.tenant_id,
    t.name,
    t.phone,
    t.idcard,
    t.gender
     */
    public function getAllTenant($contract_id)
    {
        $select = new \App\Web\Helper\TenantContract();
        $sql = $select->getSqlObject();
        $select = $sql->select(array('tc' => $select->getTableName('tenant_contract')));
        $select->leftjoin(array('cr' => 'contract_rental') , new Expression('tc.contract_id=cr.contract_id and cr.is_delete=0') , array('contract_id' , 'tenant_id' , 'contract_rental_id'));
        $select->leftjoin(array('t' => 'tenant') , 't.tenant_id=cr.tenant_id' , array('name' , 'phone' , 'idcard' , 'gender' , 'from'));
        $where = new \Zend\Db\Sql\Where();
        $where->equalTo('tc.contract_id' , $contract_id);
        $where->equalTo('cr.is_delete' , 0);
        $select->where($where);
        //print_r(str_replace('"', '', $select->getSqlString()));die();
        return $select->execute();
    }

    /**
     * 用于续租和编辑合同时,判断是新增租客,修改租客,还是不操作
     * @param $data array 包含name idcard phone gender
     * @author too|最后修改时间 2015年4月28日 下午7:57:12
     */
    public function whataction($data)
    {
        $select = new Tenant();
        $sql = $select->getSqlObject();
        $select = $sql->select(array('t' => $select->getTableName('tenant')));
        //$select->columns(array());
        $where = new \Zend\Db\Sql\Where();
        $where->equalTo('t.name' , $data['name']);
        $where->equalTo('t.idcard' , $data['idcard']);
        $where->equalTo('t.phone' , $data['phone']);
        $where->equalTo('t.gender' , $data['gender']);
        $select->where($where);
        $result = $select->execute();
        return $result;
    }

    /**
     * 获取合同列表
     * @param int $cid 公司id
     * @param array $search 搜索条件
     * @param int $page 当前页面
     * @param int $size 一页放多少条
     * return array
     * @author too|最后修改时间 2015年4月21日 上午10:45:16
     */
    public function getAllTenantContract($cid , $search , $page , $size)
    {
        $select = new \Common\Model\Erp\TenantContract();
        $userHelper = new \Common\Helper\Erp\User();
        $user = $userHelper->getCurrentUser();
        $sql = $select->getSqlObject();
        $select = $sql->select(array('tc' => $select->getTableName('tenant_contract')));
        $select->leftjoin(array('r' => 'rental') , 'r.contract_id=tc.contract_id');
        $select->leftjoin(array('t' => 'tenant') , 't.tenant_id=r.tenant_id');
        //$select->leftjoin(array('e'=>'evaluate'), 'e.company_id=tc.company_id');
        //权限
        /*  if($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER){
          $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
          $permisions->VerifyDataCollectionsPermissionsModel($select, 'r.house_id=__TABLE__.authenticatee_id', $permisions::USER_AUTHENTICATOR, $user['user_id'],1);
          } */
        $where = new \Zend\Db\Sql\Where();
        $where->equalTo('tc.company_id' , $cid);
        $where->equalTo('tc.is_delete' , 0);
        //分散式 or 集中式
        if (!empty($search['house_type']))
        {
            $where->equalTo('r.house_type' , $search['house_type']);
        }
        //集中式的房源ID筛选
        if (is_numeric($search['focus_id']))
        {
            $where->equalTo('rf.room_focus_id' , $search['focus_id']);
        }
        //分散式的房源ID筛选
        if (is_numeric($search['house_id']))
        {
            $where->equalTo('r.house_type' , 1);
            $where->equalTo('r.house_id' , $search['house_id']);
        }
        if (is_numeric($search['room_id']))
        {
            $where->equalTo('r.room_id' , $search['room_id']);
        }
        //到期时间
        if (!empty($search['start_dead_line']))
        {
            $where->greaterThanOrEqualTo('tc.dead_line' , $search['start_dead_line']);
        }
        if (!empty($search['end_dead_line']))
        {
            $where->lessThanOrEqualTo('tc.dead_line' , $search['end_dead_line']);
        }
        //下次交租
        if (!empty($search['start_next_pay_time']))
        {
            $where->greaterThanOrEqualTo('tc.next_pay_time' , $search['start_next_pay_time']);
        }
        if (!empty($search['end_next_pay_time']))
        {
            $where->lessThanOrEqualTo('tc.next_pay_time' , $search['end_next_pay_time']);
        }
        //0在住 1退租
        if (!empty($search['status']))
        {
            $where->equalTo('tc.is_stop' , $search['status']);
        }
        $select->join(array('hfv' => 'house_focus_view') , new \Zend\Db\Sql\Predicate\Expression('r.house_id = hfv.house_id and r.room_id = hfv.record_id') , array('*'/* 'public_facilities' , 'house_name' , 'custom_number' */) , $select::JOIN_LEFT);
//             $select->join(array('h' => 'house') , new \Zend\Db\Sql\Predicate\Expression('r.house_id = h.house_id and r.house_type=1') , array('public_facilities' , 'house_name' , 'custom_number') , $select::JOIN_LEFT);
//             $select->join(array('ro' => 'room') , new \Zend\Db\Sql\Predicate\Expression('r.room_id = ro.room_id and r.house_type=1') , array('room_type' , 'room_config','r_custom_number'=>'custom_number') , $select::JOIN_LEFT);
//             $select->join(array('c' => 'community') , 'h.community_id = c.community_id' , array('community_name') , $select::JOIN_LEFT);
//             $select->join(array('rf' => 'room_focus') , new \Zend\Db\Sql\Predicate\Expression('r.room_id = rf.room_focus_id and r.house_type=2') , array('room_focus_id','rf_custom_number'=>'custom_number') , $select::JOIN_LEFT);
//             $select->join(array('f' => 'flat') , 'f.flat_id = rf.flat_id' , array('flat_name') , 'left');
        if (!emptys($search['keywords']))
        {
            $where2 = new \Zend\Db\Sql\Where();
            $where2->like('t.phone' , "%$search[keywords]%");//租客电话
            $where2->or;
            $where2->like('t.name' , "%$search[keywords]%");//租客电话
            $where2->or;
            $where2->like('hfv.house_name' , "%$search[keywords]%");//分散式小区名
//                 $where2->or;
//                 $where2->like('t.name' , "%$search[keywords]%");//分散式小区名
//                 $where2->or;
//                 $where2->like('h.custom_number' , "%$search[keywords]%");//房源编号
//                 $where2->or;
//                 $where2->like('f.flat_name' , "%$search[keywords]%");//集中式公寓名
            $where->addPredicate($where2);
        }
        $select->where($where);
        $select->order('tc.contract_id desc');
        $data = $select::pageSelect($select , null , $page , $size);
        // print_r(str_replace('"','',$select->getSqlString()));
        return $data;
    }

    /**
     * 获取合同列表
     * @param int $cid 公司id
     * @param array $search 搜索条件
     * @param int $page 当前页面
     * @param int $size 一页放多少条
     * return array
     * @author too|最后修改时间 2015年4月21日 上午10:45:16
     */
    public function getAllTenantContractPc($cid , $search , $page , $size , $user , $dis_contract_id = array() , $flat_con_id = array(),$house_type)
    {
        if($house_type == 1){
            return self::getHouse($cid , $search , $page , $size , $user , $dis_contract_id,$flat_con_id);
        }else if($house_type == 2){
            return self::getRoomFocus($cid , $search , $page , $size , $user , $dis_contract_id,$flat_con_id);
        }
    }

    /**
     * 获取集中式房源
     * @param $cid
     * @param $search
     * @param $page
     * @param $size
     * @param $user
     * @param array $dis_contract_id
     * @param array $flat_con_id
     * @return array
     */
    public function getRoomFocus($cid , $search , $page , $size , $user , $dis_contract_id = array() , $flat_con_id = array()){
        $select = new \Common\Model\Erp\TenantContract();
        $sql = $select->getSqlObject();

        $select = $sql->select(array('cr' => 'contract_rental'));
        $select->join(
            array('tc' => 'tenant_contract'),
            'cr.contract_id = tc.contract_id',
            [
                'contract_id' => 'contract_id' ,
                'custom_number' => 'custom_number' ,
                'parent_id' => 'parent_id' ,
                'is_renewal' => 'is_renewal' ,
                'rent' => 'rent' ,
                'deposit' => 'deposit' ,
                'signing_time' => 'signing_time' ,
                'dead_line' => 'dead_line' ,
                'end_line' => 'end_line' ,
                'next_pay_money' ,
                'is_evaluate' => 'is_evaluate' ,
                'is_stop' => 'is_stop' ,
                'is_haveson' => 'is_haveson' ,
                'next_pay_time' => 'next_pay_time' ,
                'advance_time' => 'advance_time' ,
                'house_name' => new \Zend\Db\Sql\Predicate\Expression("CONCAT(f.flat_name,rf.floor,'楼', rf.custom_number,'号')")
            ]
        );

        $select->join(
            array('t' => 'tenant') , 't.tenant_id=cr.tenant_id' , array(
                'tenant_id' => 'tenant_id' ,
                'phone' => 'phone' ,
                'gender' => 'gender' ,
                'idcard' => 'idcard' ,
                'name' => 'name' ,
                'address' => 'address' ,
            ));

        $select->join(
            ['r' => 'rental'],
            new \Zend\Db\Sql\Predicate\Expression('r.contract_id=tc.contract_id and tc.is_delete=0'),
            [
                'rental_id' => 'rental_id' ,
                'house_id' => 'house_id' ,
                'room_id' => 'room_id' ,
                'house_type' => 'house_type' ,
            ]
        );

        $select->join(
            ['rf'=>'room_focus'],
            'r.room_id=rf.room_focus_id',
            [
                'public_facilities'=>'room_config'
            ]
        );
        $select->join(
            ['f'=>'flat'],
            'rf.flat_id=f.flat_id',
            [
                'flat_name'=>'flat_name',
                'auth_id'=>'flat_id'
            ]
        );

        $where = new \Zend\Db\Sql\Where();
        //$where->equalTo('r.is_delete' , 0);
        $where->equalTo('r.house_type' , 2);
        $where->equalTo('tc.company_id' , $cid);
        $where->equalTo('t.company_id' , $cid);
        $where->equalTo('tc.is_delete' , 0);
        $where->equalTo('cr.is_delete' , 0);
        $where->equalTo('f.city_id',$user['city_id']);
        //集中式的房源ID筛选
        if (is_numeric($search['focus_id']))
        {
            $where->equalTo('rf.room_focus_id' , $search['focus_id']);
        }
        if (is_numeric($search['room_id']))
        {
            $where->equalTo('r.room_id' , $search['room_id']);
        }
        //到期时间
        if (!empty($search['start_dead_line']))
        {
            $where->greaterThanOrEqualTo('tc.dead_line' , $search['start_dead_line']);
        }
        if (!empty($search['end_dead_line']))
        {
            $where->lessThanOrEqualTo('tc.dead_line' , $search['end_dead_line']);
        }
        //下次交租
        if (!empty($search['start_next_pay_time']))
        {
            $where->greaterThanOrEqualTo('tc.next_pay_time' , $search['start_next_pay_time']);
        }
        if (!empty($search['end_next_pay_time']))
        {
            $where->lessThanOrEqualTo('tc.next_pay_time' , $search['end_next_pay_time']);
        }
        //0在住 1退租
        if ($search['status'] !== '')
        {
            $where->equalTo('tc.is_stop' , $search['status']);
        }
        /**
         * 权限
         */
        if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
        {
            $permissions = \Common\Helper\Permissions::Factory('sys_housing_management');
            $permisionsTable = $permissions->VerifyDataCollectionsPermissions($permissions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
            $join = new \Zend\Db\Sql\Predicate\Expression('f.flat_id=pa.authenticatee_id and  pa.source='.\Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
            $select->join(array('pa' => new \Zend\Db\Sql\Predicate\Expression($permisionsTable)) , $join , 'authenticatee_id');
        }

        if (!emptys($search['keywords']))
        {
            $where2 = new \Zend\Db\Sql\Where();
            $where2->like('t.phone' , "%$search[keywords]%");//租客电话
            $where2->or;
            $where2->like('t.name' , "%$search[keywords]%");//租客电话
            $where2->or;
            $where2->like('f.flat_name' , "%$search[keywords]%");//分散式小区名
            $where->addPredicate($where2);
        }
        $select->where($where);
        $select->group("tc.contract_id");
        $select->order('tc.contract_id desc');
//        echo $select->getSqlString();exit;
        $count = $select->count();
        $data = $select::pageSelect($select , $count , $page , $size);
        return $data;
    }

    /**
     * 获取分散式房源
     * @param $cid
     * @param $search
     * @param $page
     * @param $size
     * @param $user
     * @param array $dis_contract_id
     * @param array $flat_con_id
     * @return array
     */
    public function getHouse($cid , $search , $page , $size , $user , $dis_contract_id = array() , $flat_con_id = array()){
        $select = new \Common\Model\Erp\TenantContract();
        $sql = $select->getSqlObject();
        $select = $sql->select(['cr'=>'contract_rental'],[]);
        $select->join(['tc' =>'tenant_contract'],
            'cr.contract_id=tc.contract_id',
            [
                'contract_id' => 'contract_id' ,
                'parent_id' => 'parent_id' ,
                'is_renewal' => 'is_renewal' ,
                'rent' => 'rent' ,
                'deposit' => 'deposit' ,
                'signing_time' => 'signing_time' ,
                'dead_line' => 'dead_line' ,
                'end_line' => 'end_line' ,
                'next_pay_money' ,
                'is_evaluate' => 'is_evaluate' ,
                'is_stop' => 'is_stop' ,
                'is_haveson' => 'is_haveson' ,
                'next_pay_time' => 'next_pay_time' ,
                'advance_time' => 'advance_time' ,
            ]
        );
        $select->join(
            array('t' => 'tenant') , 't.tenant_id=cr.tenant_id' , array(
                'tenant_id' => 'tenant_id' ,
                'phone' => 'phone' ,
                'gender' => 'gender' ,
                'idcard' => 'idcard' ,
                'name' ,
                'address' => 'address' ,
            ));

        $select->join(
            array('r' => 'rental') , 'r.contract_id=tc.contract_id' , array(
                'rental_id' => 'rental_id' ,
                'house_id' => 'house_id' ,
                'room_id' => 'room_id' ,
                'house_type' => 'house_type' ,
            ));


        $select->join(
            ['h'=>'house'],
            'h.house_id=r.house_id',
            [
                'community_id'=>'community_id'
            ]
        );
        $select->join(
            ['c'=>'community'],
            'h.community_id=c.community_id',
            [
                'house_name'=>new \Zend\Db\Sql\Predicate\Expression("CONCAT(c.community_name,'-',h.cost,'栋',h.unit,'单元',h.floor,'楼',h.number,'号')"),
            ]
        );
        $select->leftjoin(
            ['rm'=>'room'],
            'rm.room_id=r.room_id',
            [
                'room_type'=>new \Zend\Db\Sql\Predicate\Expression("IF(rm.room_id IS NULL,'',CONCAT(IF((`rm`.`room_type` = 'main'),'主卧',IF((`rm`.`room_type` = 'guest'),'客卧','次卧')),rm.custom_number,'号'))"),
            ]
        );
        $where = new \Zend\Db\Sql\Where();
        $where->equalTo('c.city_id',$user['city_id']);
        $where->equalTo('tc.company_id' , $cid);
        $where->equalTo('t.company_id' , $cid);
        $where->equalTo('tc.is_delete' , 0);
//        $where->equalTo('r.is_delete' , 0);
        $where->equalTo('cr.is_delete' , 0);
        //分散式
        $where->equalTo('r.house_type' , 1);
        //分散式的房源ID筛选
        if (is_numeric($search['house_id']))
        {
            $where->equalTo('r.house_id' , $search['house_id']);
        }
        if (is_numeric($search['room_id']))
        {
            $where->equalTo('r.room_id' , $search['room_id']);
        }
        //到期时间
        if (!empty($search['start_dead_line']))
        {
            $where->greaterThanOrEqualTo('tc.dead_line' , $search['start_dead_line']);
        }
        if (!empty($search['end_dead_line']))
        {
            $where->lessThanOrEqualTo('tc.dead_line' , $search['end_dead_line']);
        }
        //下次交租
        if (!empty($search['start_next_pay_time']))
        {
            $where->greaterThanOrEqualTo('tc.next_pay_time' , $search['start_next_pay_time']);
        }
        if (!empty($search['end_next_pay_time']))
        {
            $where->lessThanOrEqualTo('tc.next_pay_time' , $search['end_next_pay_time']);
        }
        //0在住 1退租
        if ($search['status'] !== '')
        {
            $where->equalTo('tc.is_stop' , $search['status']);
        }
        /**
         * 权限
         */
        if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
        {
            $permissions = \Common\Helper\Permissions::Factory('sys_housing_management');
            $permisionsTable = $permissions->VerifyDataCollectionsPermissions($permissions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
            $join = new \Zend\Db\Sql\Predicate\Expression('(h.house_id=pa.authenticatee_id and h.house_id>0 and pa.source='.\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE.')');
            $select->join(array('pa' => new \Zend\Db\Sql\Predicate\Expression($permisionsTable)) , $join , 'authenticatee_id');
        }

        if (!emptys($search['keywords']))
        {
            $where2 = new \Zend\Db\Sql\Where();
            $where2->like('t.phone' , "%$search[keywords]%");//租客电话
            $where2->or;
            $where2->like('t.name' , "%$search[keywords]%");//租客电话
            $where2->or;
            $where2->like('h.house_name' , "%$search[keywords]%");//分散式小区名
            $where->addPredicate($where2);
        }
        $select->where($where);
        $select->group("tc.contract_id");
        $select->order('cr.contract_rental_id desc');
//        echo $select->getSqlString();exit;
        $count = $select->count();
        $data = $select::pageSelect($select , $count , $page , $size);

//        $length = count($data['data']);
//        for($i=0;$i<$length;$i++){
//            if($data['data'][$i]['room_type']){
//                $data['data'][$i]['room_type'] = str_replace('main','主卧',$data['data'][$i]['room_type']);
//                $data['data'][$i]['room_type'] = str_replace('second','次卧',$data['data'][$i]['room_type']);
//                $data['data'][$i]['room_type'] = str_replace('guest','客卧',$data['data'][$i]['room_type']);
//            }
//        }
        return $data;
    }




    /**
     * 删除合同
     * @param int $contract_id
     * @author too|最后修改时间 2015年4月23日 下午4:58:11
     */
    public function deleContract($contract_id)
    {
        $tc = new \App\Web\Helper\TenantContract();
        $rentalModel = new Rental();
        $cr = new ContractRental();
        if (!$tc->editTenantContract(array('is_delete' => 1) , array('contract_id' => $contract_id) , true))
        {
            return false;
        }
        if (!$rentalModel->edit(array('contract_id' => $contract_id) , array('is_delete' => 1)))
        {
            return false;
        }
        if (!$cr->edit(array('contract_id' => $contract_id) , array('is_delete' => 1)))
        {
            return false;
        }
        return true;
    }

    /**
     * 获取当前城市下的小区下的房源,查询房源下已添加的合同
     * 修改时间  2015年6月25日19:27:49
     *
     * @author ft
     * @param  $city_id (城市id)
     * @param  $cid (公司id)
     */
    public function getCityCommunityHouseContract($city_id , $cid)
    {
        $city_model = new \Common\Model\Erp\City();
        $sql = $city_model->getSqlObject();
        $select = $sql->select(array('c' => 'city'));
        $select->columns(array());
        $select->leftjoin(array('ct' => 'community') , 'c.city_id = ct.city_id' , array());
        $select->leftjoin(array('h' => 'house') , 'ct.community_id = h.community_id' , array());
        $select->leftjoin(array('re' => 'rental') , 'h.house_id = re.house_id' , array('contract_id' => 'contract_id'));
        $where = new \Zend\Db\Sql\Where();
        $where->equalTo('c.city_id' , $city_id);
        $where->equalTo('h.company_id' , $cid);
        $where->equalTo('re.is_delete' , 0);
        $where->equalTo('re.house_type' , 1);
        $select->where($where);
        return $select->execute();
    }

    /**
     * 获取当前城市下的公寓下的房间,查询房间已签订的合同
     * 修改时间  2015年6月25日19:45:01
     *
     * @author ft
     * @param  $city_id (城市id)
     * @param  $cid (公司id)
     */
    public function getCityFlatRoomContract($city_id , $cid)
    {
        $city_model = new \Common\Model\Erp\City();
        $sql = $city_model->getSqlObject();
        $select = $sql->select(array('c' => 'city'));
        $select->columns(array());
        $select->leftjoin(array('f' => 'flat') , 'c.city_id = f.city_id' , array());
        $select->leftjoin(array('rf' => 'room_focus') , 'f.flat_id = rf.flat_id' , array());
        $select->leftjoin(array('re' => 'rental') , 'rf.room_focus_id = re.room_id' , array('contract_id' => 'contract_id'));
        $where = new \Zend\Db\Sql\Where();
        $where->equalTo('c.city_id' , $city_id);
        $where->equalTo('f.company_id' , $cid);
        $where->equalTo('re.is_delete' , 0);
        $where->equalTo('re.house_type' , 2);
        $select->where($where);
        return $select->execute();
    }

}
    