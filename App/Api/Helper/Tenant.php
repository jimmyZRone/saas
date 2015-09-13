<?php

    namespace App\Api\Helper;

    class Tenant extends \Common\Helper\Erp\Tenant
    {

        /**
         * 根据身份证号码获取租客id
         *
         * @author yusj | 最后修改时间 2015年5月5日下午4:02:51
         */
        public function getTenantByIDCard($idcard)
        {
            $tenantModel = new \Common\Model\Erp\Tenant ();
            $data = $tenantModel->getOne(array(
                'idcard' => $idcard
                    ) , array(
                'tenant_id'
            ));
            return $data;
        }

        /**
         * 获取合同列表
         * @param int $cid 公司id
         * @param array $search 搜索条件
         * @param int $page 当前页面
         * @param int $size 一页放多少条
         * return array
         * @author lms|最后修改时间 2015年4月21日 上午10:45:16
         */
        public function getAllTenant($cid , $search , $page , $size , $user)
        {
            $select = new \Common\Model\Erp\TenantContract();
            $sql = $select->getSqlObject();
            $select = $sql->select(array('tc' => $select->getTableName('tenant_contract')));
            $select->leftjoin(array('r' => 'rental') , 'r.contract_id=tc.contract_id');
            $select->leftjoin(array('t' => 'tenant') , 't.tenant_id=r.tenant_id');
            //$select->leftjoin(array('e'=>'evaluate'), 'e.company_id=tc.company_id');
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('tc.company_id' , $cid);
            $where->equalTo('tc.is_delete' , 0);
            $where->equalTo('r.is_delete' , 0);
            //分散式 or 集中式
            if (!empty($search['house_type']))
            {
                $where->in('r.house_type' , $search['house_type']);
            }
            //集中式的房源ID筛选
            if (is_numeric($search['focus_id']))
            {
                $where->equalTo('r.house_type' , 2);
                $where->equalTo('r.room_id' , $search['focus_id']);
            }
            //分散式的房源ID筛选
            if (is_numeric($search['house_id']))
            {
                $where->equalTo('r.house_id' , $search['house_id']);
            }
            if (is_numeric($search['room_id']))
            {
                $where->equalTo('r.house_type' , 1);
                $where->equalTo('r.room_id' , $search['room_id']);
            }
            //到期时间
            if (!empty($search['start_dead_line']))
            {
                $where->greaterThanOrEqualTo('tc.end_line' , $search['start_dead_line']);
            }
            if (!empty($search['end_dead_line']))
            {
                $where->lessThanOrEqualTo('tc.end_line' , $search['end_dead_line']);
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
            $select->join(array('hfv' => 'house_focus_view') , new \Zend\Db\Sql\Predicate\Expression('r.house_id = hfv.house_id and r.room_id = hfv.record_id') , array('*') , $select::JOIN_LEFT);

            //0未终止，1终止
            if (!empty($search['is_stop']))
            {
                $where->equalTo('tc.is_stop' , $search['is_stop']);
            }
            if (!$user['is_manager'])
            {
                $permissions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisionsTable = $permissions->VerifyDataCollectionsPermissions($permissions::USER_AUTHENTICATOR , $user['user_id'] , 0);
                $join = new \Zend\Db\Sql\Predicate\Expression('(hfv.auth_id=pa.authenticatee_id and hfv.house_id=0 and pa.source='.\Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED.') or (hfv.house_id=pa.authenticatee_id and hfv.house_id>0  and pa.source='.\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE.')');
                $select->join(array('pa' => new \Zend\Db\Sql\Predicate\Expression($permisionsTable)) , $join , 'authenticatee_id');
            }


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
            //print_r($data);
           // print_r(str_replace('"' , '' , $select->getSqlString()));
            return $data;
        }

    }
    