<?php

    namespace App\Api\Mvc\Controller;

    class LandlordController extends \App\Api\Lib\Controller
    {

        /**
         * 获取首页信息数据
         * @author yusj | 最后修改时间 2015年5月8日下午3:49:59
         */
        public function getContractListAction()
        {
            $L = M('LandlordContract');
            $end_time = I('end_time');
            $to_day = strtotime(date('Y-m-d 00:00:00'));
            $where = new \Zend\Db\Sql\Where();
            $company_id = $this->getCompanyId();
            $where->equalTo('l.company_id' , $company_id);
            $time = time();

            $house_type = I('house_type');
            $id = I('id');

            if (!emptys($house_type))
            {
                $where->equalTo('l.house_type' , $house_type == 1 ? 1 : 2);
            }

            if (is_numeric($id))
            {
                $where->equalTo('l.house_id' , $id);
            }


            switch ($end_time)
            {
                //已到期
                case '0':
                    $where->lessThanOrEqualTo('end_line' , $time);
                    break;
                //今日到期
                case '1':
                    $where->greaterThanOrEqualTo('end_line' , $to_day);
                    $where->lessThan('end_line' , $time);
                    break;
                //三天内到期
                case '2':
                    $where->greaterThanOrEqualTo('end_line' , strtotime('-2 day' , $to_day));
                    $where->lessThan('end_line' , $time);
                    break;
                //一星期
                case '3':
                    $where->greaterThanOrEqualTo('end_line' , strtotime('-6 day' , $to_day));
                    $where->lessThan('end_line' , $time);
                    break;
                //一月
                case '4':
                    $where->greaterThanOrEqualTo('end_line' , strtotime('-29 day' , $to_day));
                    $where->lessThan('end_line' , $time);
                    break;
            }

            $nex_pay_time = I('nex_pay_time');
            switch ($nex_pay_time)
            {
                //今日
                case '1':
                    $where->greaterThanOrEqualTo('next_pay_time' , $to_day);
                    $where->lessThan('next_pay_time' , $time);
                    break;
                //三天内
                case '2':
                    $where->greaterThanOrEqualTo('next_pay_time' , strtotime('-2 day' , $to_day));
                    $where->lessThan('next_pay_time' , $time);
                    break;
                //一星期
                case '3':
                    $where->greaterThanOrEqualTo('next_pay_time' , strtotime('-6 day' , $to_day));
                    $where->lessThan('next_pay_time' , $time);
                    break;
            }

            $S = $L->getSqlObject()->select();

            $S->from(array('lc' => 'landlord_contract'))->columns(array('contract_id' , 'landlord_id' , 'hosue_name' , 'next_pay_time' , 'end_line'))->leftjoin(array('l' => 'landlord') , 'l.landlord_id = lc.landlord_id' , array('name'))->where($where)->order('lc.contract_id desc');

            if (!$this->IsManager())
            {
                $permissions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisionsTable = $permissions->VerifyDataCollectionsPermissions($permissions::USER_AUTHENTICATOR , $this->getUserId() , 2);
                $join = new \Zend\Db\Sql\Predicate\Expression('(lc.house_id=pa.authenticatee_id and lc.house_type='.\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE.') or (lc.house_id=pa.authenticatee_id and lc.house_type='.\Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED.')');
                $S->join(array('pa' => new \Zend\Db\Sql\Predicate\Expression($permisionsTable)) , $join , 'authenticatee_id' , $S::JOIN_LEFT);
                $authWhere = new \Zend\Db\Sql\Where();
                $authWhere2 = clone $authWhere;
                $authWhere->isNotNull('pa.authenticatee_id');
                $authWhere2->isNull('pa.authenticatee_id');
                $authWhere2->equalTo('l.create_user_id' , $this->getUserId());
                $authWhere->orPredicate($authWhere2);
                $where->addPredicate($authWhere);
            }

            $result = $S->execute();
            return_success($result);
        }

        function getFlatHouseListAction()
        {

            PV('type');

            $type = I('type');
            $select = M('User')->getSqlObject()->select();
            $company_id = $this->getCompanyId();

            switch ($type)
            {
                case 1:
                    $result = $select->from(array('h' => 'house'))->columns(array('name' => 'house_name' , 'id' => 'house_id'))->leftjoin(array('lc' => 'landlord_contract') , 'h.house_id = lc.house_id' , array())->where(array('lc.house_type' => $type , 'h.company_id' => $company_id , 'lc.is_delete' => '0'))->group('id')->execute();
                    break;
                case 2:
                    $result = $select->from(array('f' => 'flat'))->columns(array('id' => 'flat_id' , 'name' => 'flat_name'))->leftjoin(array('lc' => 'landlord_contract') , 'f.flat_id = lc.house_id' , array())->where(array('lc.house_type' => $type , 'lc.is_delete' => '0' , 'f.company_id' => $company_id))->group->execute();
                    break;
            }
            return_success($result);
        }

        function addAction()
        {

            PV(array(
                'house_id' , 'type' , 'custom_number' , 'signing_time' , 'dead_line' , 'rent' , 'detain' , 'pay' , 'name' , 'phone' , 'idcard' ,
            ));

            $data = array();
            $ascending = I('ascending');
            $data['mark'] = I('remark' , '' , 'trim');
            $data['custom_number'] = I('custom_number' , 'trim');
            $data['bank'] = I('bank' , 'trim');
            $data['fork_bank'] = I('fork_bank' , 'trim');
            $data['bank_no'] = I('bank_no' , 'trim');
            $data['free_day'] = (int) I('free_day' , '' , 'trim');
            $signing_time = I('signing_time' , '' , 'strtotime');
            $dead_line = I('dead_line' , '' , 'strtotime');
            $data['signing_time'] = $signing_time;
            $data['dead_line '] = $dead_line;
            if (!is_time($signing_time) || !is_time($dead_line) || $dead_line < $signing_time)
                return_error(131 , '合同起始时间不正确');
            $rent = I('rent' , '' , 'trim');
            if (!is_numeric($rent))
                return_error(131 , '租金金额不正确');
            $deposit = I('deposit' , '' , 'trim');
            $detain = I('detain');
            $pay = I('pay');
            if (!is_zzs($detain) || !is_zzs($pay))
                return_error(131 , '压付格式不正确');
            $advance_time = I('advance_time');
            if (!is_zzs($advance_time))
                return_error(131 , '提前付款天数格式不正确');
            $tenant_list = I('tenant_list');
            $tenant_data = array();
            $idcard = I('idcard');
            $L = M('Landlord');
            $company_id = $this->getCompanyId();
            $t_info = $L->getOne(array('company_id' => $company_id , 'idcard' => $idcard));
            $user_id = $this->getUserId();
            $t_data = array(
                'name' => I('name') ,
                'name' => I('phone') ,
                'idcard' => I('idcard') ,
                'update_time' => time() ,
                'company_id' => $company_id ,
                'create_user_id' => $user_id ,
                'is_delete' => 0 ,
            );

            if (emptys($t_info))
            {
                $t_data['create_time'] = $t_data['update_time'];
                $t_id = $L->insert($t_data);
            }
            else
            {
                $t_id = $L->edit(array('landlord_id' => $t_info['landlord_id']) , $t_data);
            }

            if (!$t_id)
            {
                return_error(141 , ',业主添加失败');
            }
            // 待续 租金递增


            return_success();
        }
        
        public function addContractAction()
        {
            PV(array('owner', 'contract', 'house'));
            dump(I('owner'));die;
            $owner_info = I('owner') ? json_decode(I('owner'), true) : array();
            $contract_info = I('contract') ? json_decode(I('contract'), true) : array();
            $house_info = I('house') ? json_decode(I('house'), true) : array();
            dump($owner_info);die;
            //业主信息
            //             $owner = htmlspecialchars_decode(I('owner'));
            //             $owner_info = json_decode($owner, true);
        
            //             //合同信息
            //             $contract = htmlspecialchars_decode(I('contract'));
            //             $contract_info = json_decode($contract, true);
            //             //房源信息
            //             $house = htmlspecialchars_decode(I('house'));
            //             $house_info = json_decode($house, true);
        
            $user = $this->getUserInfo();
            //保存业主
            $landlord_helper = new \Common\Helper\Erp\Landlord();
            $landlord_res = $landlord_helper->saveLandlord($owner_info, $user);
        
            dump($owner_info,$contract_info,$house_info);die;
        }

    }
    