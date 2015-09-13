<?php

    namespace App\Api\Mvc\Controller;

    class FinancialController extends \App\Api\Lib\Controller
    {

        function addAction()
        {

            PV(array('fee_list' , 'status' , 'pay_time' , 'payment_mode' , 'receivable' , 'money' , 'type'));

            $fee_list = I('fee_list' , '' , 'trim');
            $house_id = (int) I('house_id', 0);
            $room_id = (int) I('room_id');
            $house_type = (int) I('house_type');
            $fee_list = is_array($fee_list) ? $fee_list : json_decode($fee_list , true);
            $fee_names = getArrayValue($fee_list , 'name');
            $user = $this->getUserInfo();

            $data = array();
            $data['serial_number'] = uniqid(date('Ym'));
            $data['serial_name'] = implode('、' , $fee_names);
            $data['house_id'] = ($house_type == 1 && $house_id == 0) ? $room_id : $house_id;
            $data['room_id'] = ($house_type == 1 && $house_id > 0) ? $room_id : ($house_type == 2 ? $room_id : 0);
            $data['house_type'] = $house_type;
            $data['source_id'] = (int) I('source_id');
            $data['source'] = I('source');
            $data['pay_time'] = (int) I('pay_time' , '' , 'strtotime');
            $data['type'] = (int) I('type');
            $data['receivable'] = (float) I('receivable');
            $data['money'] = (float) I('money');
            $data['final_money'] = $data['money'];//$data['receivable'] > $data['money'] ? $data['receivable'] - $data['money'] : 0;
            $data['father_id'] = '0';
            $data['user_id'] = $user['user_id'];
            $data['city_id'] = $user['city_id'];
            $data['company_id'] = $user['company_id'];
            $data['payment_mode'] = I('payment_mode');
            $data['remark'] = I('remark');
            $data['status'] = (int) I('status');
            $data['create_time'] = time();
            $data['is_delete'] = '0';
            $subscribe_pay_time = (int) I('subscribe_pay_time' , '' , 'strtotime');
            $data['subscribe_pay_time'] = $subscribe_pay_time;
            $S = M('SerialNumber');
            $S->Transaction();

            $serial_id = $S->insert($data);

            if (!$serial_id)
            {
                return_error(142);
            }
            //欠费清单
            if (strlen($subscribe_pay_time) == 10)
            {
                $new_data = $data;
                $new_data['subscribe_pay_time'] = $subscribe_pay_time;
                $new_data['receivable'] = $data['receivable'] - $data['money'];
                $new_data['money'] = '0';
                $new_data['final_money'] = '0';
                $new_data['father_id'] = $serial_id;
                $father_id = $S->insert($new_data);
                if (!$father_id)
                {
                    return_error(142);
                }
            }
            $SD = M('SerialDetail');
            $MR = M('MeterReading');
            $MRM = M('MeterReadingMoney');
            foreach ($fee_list as $info)
            {
                $add = $SD->insert(array(
                    'serial_id' => $serial_id ,
                    'fee_type_id' => $info['fee_type_id'] ,
                    'money' => $info['money'] ,
                    'final_money' => $info['money'] ,
                    'is_delete' => 0 ,
                ));
                if (!$add)
                {
                    return_error(142);
                }
                if (is_numeric($info['meter_id']))
                {
                    $MR->edit(array('meter_id' => $info['meter_id']) , array('is_sf' => 1));
                    $MRM->edit(array('meter_reading_id' => $info['meter_id']) , array('is_settle' => 1));
                }
            }
            $S->commit();
            return_success();
        }

        function saveAction()
        {

            PV(array('fee_list' , 'status' , 'serial_id' , 'payment_mode' , 'pay_time' , 'receivable' , 'money'));
            $fee_list = I('fee_list' , '' , 'trim');
            $fee_list = is_array($fee_list) ? $fee_list : json_decode($fee_list , true);

            $fee_names = getArrayValue($fee_list , 'name');
            $user = $this->getUserInfo();
            $data = array();
            $S = M('SerialNumber');
            $SD = M('SerialDetail');
            $serial_id = I('serial_id');
            $s_info = $S->getOne(array('serial_id' => $serial_id , 'company_id' => $user['company_id']));
            if (emptys($s_info))
                return_error(504);
            $S->Transaction();

            $data['serial_name'] = implode('、' , $fee_names);
            $data['status'] = (int) I('status');
            $data['serial_number'] = $s_info['serial_number'];
            $data['pay_time'] = (int) I('pay_time' , '' , 'strtotime');
            $data['receivable'] = (float) I('receivable');
            $data['money'] = (float) I('money');
            $data['final_money'] = $data['money']; //$data['receivable'] > $data['money'] ? $data['receivable'] - $data['money'] : 0;
            // $data['father_id'] = '0';
            $data['payment_mode'] = I('payment_mode');
            $data['remark'] = I('remark');
            $subscribe_pay_time = (int) I('subscribe_pay_time' , '' , 'strtotime');
            $data['subscribe_pay_time'] = $subscribe_pay_time;
            //删除欠费清单
            if (is_numeric($s_info['serial_id']) && $data['status'] == 1 && $s_info['status'] == 2)
            {
                $arrear_where = array('father_id' => $serial_id);
                $arrear_data = array('is_delete' => 1);
                $arrear_edit_res = $S->edit($arrear_where , $arrear_data);
                if (!$arrear_edit_res)
                {
                    return_error(142);
                }
            }
            else if ($data['status'] == 2 && $s_info['status'] == 2)
            {
                $arrear_where = array('father_id' => $serial_id);
                $new_data = array();
                $new_data['father_id'] = $serial_id;    //father_id就是上一条流水id
                //$data['money'] = $data['receivable'] - $data['money'];
                $new_data['receivable'] = $data['receivable'] - $data['money'];
                $new_data['money'] = 0;
                $new_data['final_money'] = 0;

                $arrear_res = $S->edit($arrear_where , $new_data);
                if (!$arrear_res)
                {
                    return_error(142);
                }
            }
            else if ($data['status'] == 2 && $s_info['status'] == 1)
            {
                $new_data = array_merge($s_info , $data);

                unset($new_data['serial_id']);
                $new_data['father_id'] = $serial_id;    //father_id就是上一条流水id
                $new_data['receivable'] = $data['receivable'] - $data['money'];
                $new_data['money'] = 0;
                $new_data['final_money'] = 0;
                $arrear_id = $S->insert($new_data);
                if (!$arrear_id)
                {
                    return_error(142);
                }
            }
            elseif ($data['status'] == 2 && $s_info['status'] == 0)
            {
                $arrear_where = array('father_id' => $serial_id);
                $new_data = array_merge($s_info , $data);
                unset($new_data['serial_id']);
                $new_data['father_id'] = $serial_id;    //father_id就是上一条流水id
                $new_data['money'] = $data['receivable'] - $data['money'];
                $new_data['final_money'] = $data['money'];
                $arrear_id = $S->insert($new_data);
                if (!$arrear_id)
                {
                    return_error(142);
                }
            }
            elseif ($data['status'] == 0 && $s_info['status'] == 2)
            {
                $arrear_where = array('father_id' => $serial_id);
                //father_id就是上一条流水id
                $save_money = $s_info['receivable'] - $s_info['money'];
                $save_data = array(
                    'status' => 0 ,
                    'serial_name' => $data['serial_name'] ,
                    'final_money' => $save_money ,
                    'money' => $save_money ,
                );

                $data['serial_name'] = $s_info['serial_name'];
                $arrear_id = $S->edit($arrear_where , $save_data);
                if (!$arrear_id)
                {
                    $this->rollback();
                    return false;
                }

                $is_fee = true;
            }

            $del_fee_ids = array();
            foreach ($fee_list as $fval)
            {
                //欠费收款后为欠费新增一条详情
                if (isset($is_fee))
                {
                    $serial_detail_data['serial_id'] = $fval['serial_id'];
                    $serial_detail_data['fee_type_id'] = $fval['fee_type_id'];
                    $serial_detail_data['money'] = $fval['money'];
                    $serial_detail_data['final_money'] = $fval['money'];
                    $result = $SD->addSerialDetail($serial_detail_data);
                }
                else if (is_numeric($fval['serial_id']))
                {
                    $detail_where['serial_id'] = $fval['serial_id'];
                    $detail_where['fee_type_id'] = $fval['fee_type_id'];
                    $serial_detail_data['money'] = $fval['money'];
                    $serial_detail_data['final_money'] = $fval['money'];
                    $result = $SD->editSerialDetailById($detail_where , $serial_detail_data);
                }
                else
                {
                    $serial_detail_data['serial_id'] = $serial_id;
                    $serial_detail_data['fee_type_id'] = $fval['fee_type_id'];
                    $serial_detail_data['money'] = $fval['money'];
                    $serial_detail_data['final_money'] = $fval['money'];
                    $result = $SD->addSerialDetail($serial_detail_data);
                }
                if (!$result)
                {
                    return_error(142);
                }
                $del_fee_ids[] = $fval['fee_type_id'];
            }

            //删除未传递的费用
            $where = new \Zend\Db\Sql\Where();
            $where->notIn('fee_type_id' , $del_fee_ids);
            $where->equalTo('serial_id' , $serial_id);
            $SD->edit($where , array('is_delete' => 1));
            $serial_where = array('serial_id' => $serial_id);
            $edit = $S->edit($serial_where , $data);

            if (!$edit)
            {
                return_error(142);
            }
            $S->commit();
            return_success();
        }

        function strikebalanceAction()
        {

            PV(array('cur_money' , 'cost_money' , 'serial_detail_id' , 'mark' , 'serial_id'));
            $company_id = $this->getCompanyId();
            $strike_info = $_POST;
            $serial_detail_model = new \Common\Model\Erp\SerialDetail();//流水详细model
            $serial_strike_model = new \Common\Model\Erp\SerialStrikeBalance();//流水冲账model
            $serial_number_model = new \Common\Model\Erp\SerialNumber();//流水model 
            $serial_id = I('serial_id');

            if ($serial_number_model->getCount(array('serial_id' => $serial_id , 'company_id' => $company_id)) == 0)
                return_error(504);

            $current_sum = $strike_info['cur_money'];    //当前金额
            $strike_balance_sum = $strike_info['cost_money'];//冲账金额

            $diff_sum = $current_sum - $strike_balance_sum;    //冲账后金额
            $serial_detail_data = array('final_money' => $diff_sum);

            $data = array(
                'serial_id' => $serial_id , //流水id
                'serial_detail_id' => $strike_info['serial_detail_id'] , //详细id
                'money' => $strike_balance_sum , //冲账金额
                'create_time' => time() , //冲账时间
                'mark' => $strike_info['mark'] , //冲账备注
            );
            $serial_strike_model->Transaction();
            //添加冲账数据
            $strike_id = I('cz_id');
            if (is_numeric($strike_id))
            {
                $edit = $serial_strike_model->edit(array('id' => $strike_id) , array('money' => $strike_balance_sum , 'mark' => $strike_info['mark']));
                $strike_id = $edit == false ? $edit : $strike_id;
            }
            else
            {
                PV('auth_pwd');
                $serial_helper = new \Common\Helper\Erp\SerialNumber();
                $company_pass = $serial_helper->getCompanyPassword($company_id , $strike_info['auth_pwd']);
                if (!$company_pass)
                {
                    return_error(143);
                }
                $strike_id = $serial_strike_model->insert($data);//冲账id
            }

            if (!$strike_id)
            {
                return_error(142);
            }
            $detail_where = array('serial_detail_id' => $serial_id);
            //修改流水详细冲账金额
            $detail_res = $serial_detail_model->edit($detail_where , $serial_detail_data);
            if (!$detail_res)
            {
                return_error(142);
            }
            $serial_where = array('serial_id' => $serial_id);
            $serial_num_data = array('final_money' => $diff_sum);
            //修改流水的冲账金额
            $serial_res = $serial_number_model->edit($serial_where , $serial_num_data);
            if (!$serial_res)
            {
                return_error(142);
            }
            $serial_strike_model->commit();
            return_success();
        }

        function listAction()
        {
            $size = I('size' , 0 , 'int');//分页信息
            $company_id = $this->getCompanyId();

            $deal_type = I('deal_type');          //交易类型
            $finance_type = I('finance_type');       //资金流向(收入 /支出)
            $search = I('search_key');         //关键词搜索(小区 /房源编号)
            $page = I('page' , 0 , 'int');
            $room_id = I('room_id');
            $focus_id = I('focus_id');
            $search_data = array(
                'deal_type' => $deal_type , //交易类型
                // 'house_type' => $house_type , //房源类型(集中 /分散)
                'finance_type' => $finance_type , //资金流向(收入 /支出)
                'search' => $search , //关键词搜索(小区 /房源编号)
                'page' => $page                 //分页
            );

            $time = I('time');

            if (!emptys($time))
            {
                $search_data['time'] = $time;
            }

            if (is_numeric($room_id))
            {
                $search_data['room_id'] = $room_id;
            }

            if (is_numeric($focus_id))
            {
                $search_data['focus_id'] = $focus_id;
            }

            //费用类型model
            $fee_type_model = new \Common\Model\Erp\FeeType();
            //点击搜索
            $serial_number_helper = new \Common\Helper\Erp\SerialNumber();
            $serial_info = $serial_number_helper->accordingDateSearchSerialPc($search_data , $page , $size , $company_id , $this->getUserInfo());
            $chong_zhang = $serial_number_helper->getAllSerialByCondition($search_data , $page , $size , $company_id , $this->getUserInfo());
            //冲账
            $ssb_money = 0;
            foreach ($chong_zhang['data'] as $money)
	        {
	            $ssb_money += $money['ssb_money'];
	        }
            $serial_info['data'] = is_array($serial_info['data']) ? $serial_info['data'] : array();

            $sn = M('SerialNumber');
            $select = $sn->getSqlObject()->select();
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('company_id' , $company_id);
            $where->equalTo('type' , 1);
            $where->equalTo('is_delete' , 0);
            if (!emptys($search_data['time']))
            {

                //年筛选
                if (strlen($search_data['time']) == 4)
                {
                    $create_time = strtotime($search_data['time'] . '0101');
                    $where->greaterThanOrEqualTo('pay_time' , $create_time);
                    $where->lessThan('pay_time' , strtotime("+1 year" , $create_time));
                    $where->in('status', array(0,1));
                    $where->equalTo('father_id', 0);
                }
                else
                {
                    $create_time = strtotime($search_data['time']);
                    $where->greaterThanOrEqualTo('pay_time' , $create_time);
                    $where->lessThan('pay_time' , strtotime("+1 month" , $create_time));
                    $where->in('status', array(0,1));
                    $where->equalTo('father_id', 0);
                }
            }

            $select->from(array('sn' => 'serial_number'))->columns(array(getExpSql('sum(final_money) as money')));


            /**
             * 权限
             */
            $user = $this->getUserInfo();
            if (!$user['is_manager'])
            {
                $permissions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisionsTable = $permissions->VerifyDataCollectionsPermissions($permissions::USER_AUTHENTICATOR , $user['user_id'] , 0);
                $join = new \Zend\Db\Sql\Predicate\Expression('(sn.house_id=pa.authenticatee_id and sn.house_id>0 and pa.source='.\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE.') or (sn.room_id=pa.authenticatee_id and sn.room_id>0  and pa.source='.\Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED.')');
                $select->join(array('pa' => new \Zend\Db\Sql\Predicate\Expression($permisionsTable)) , $join , 'authenticatee_id' , $select::JOIN_LEFT);
                $authWhere = new \Zend\Db\Sql\Where();
                $authWhere2 = clone $authWhere;
                $authWhere->isNotNull('pa.authenticatee_id');
                $authWhere2->isNull('pa.authenticatee_id');
                $authWhere2->equalTo('sn.user_id' , $user['user_id']);
                $authWhere->orPredicate($authWhere2);
                $where->addPredicate($authWhere);
            }

            $shouru = $select->where($where)->execute();

            $select = $sn->getSqlObject()->select();
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('company_id' , $company_id);
            $where->equalTo('type' , 2);
            $where->equalTo('is_delete' , 0);
            if (!emptys($search_data['time']))
            {
                //年筛选
                if (strlen($search_data['time']) == 4)
                {
                    $create_time = strtotime($search_data['time'] . '0101');
                    $where->greaterThanOrEqualTo('pay_time' , $create_time);
                    $where->lessThan('pay_time' , strtotime("+1 year" , $create_time));
                }
                else
                {
                    $create_time = strtotime($search_data['time']);
                    $where->greaterThanOrEqualTo('pay_time' , $create_time);
                    $where->lessThan('pay_time' , strtotime("+1 month" , $create_time));
                }
            }
            $select->from(array('sn' => 'serial_number'))->columns(array(getExpSql('sum(final_money) as money')));
            /**
             * 权限
             */
            if (!$user['is_manager'])
            {
                $permissions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisionsTable = $permissions->VerifyDataCollectionsPermissions($permissions::USER_AUTHENTICATOR , $user['user_id'] , 0);
                $join = new \Zend\Db\Sql\Predicate\Expression('(sn.house_id=pa.authenticatee_id and sn.house_id>0 and pa.source='.\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE.') or (sn.room_id=pa.authenticatee_id and sn.room_id>0  and pa.source='.\Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED.')');
                $select->join(array('pa' => new \Zend\Db\Sql\Predicate\Expression($permisionsTable)) , $join , 'authenticatee_id' , $select::JOIN_LEFT);
                $authWhere = new \Zend\Db\Sql\Where();
                $authWhere2 = clone $authWhere;
                $authWhere->isNotNull('pa.authenticatee_id');
                $authWhere2->isNull('pa.authenticatee_id');
                $authWhere2->equalTo('sn.user_id' , $user['user_id']);
                $authWhere->orPredicate($authWhere2);
                $where->addPredicate($authWhere);
            }
            $zhichu = $select->where($where)->execute();

            $shouru = is_numeric($shouru['0']['money']) ? $shouru['0']['money'] : 0;
            $zhichu = is_numeric($zhichu['0']['money']) ? $zhichu['0']['money'] : 0;
            $serial_info['count'] = array('shouru' => $shouru , 'zhichu' => $zhichu, 'ssb_money' => $ssb_money);
            return_success($serial_info);
        }

        function getSelectListAction()
        {
            $select = M('User')->getSqlObject()->select();
            $company_id = $this->getCompanyId();
            $data = array();
            $sn = M('SerialNumber');
            $select = $sn->getSqlObject()->select();
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('sn.company_id' , $company_id);
            $where->equalTo('sn.is_delete' , 0);
            $where->equalTo('sn.house_type' , 1);
            $where->greaterThan('sn.room_id' , 0);
            $select->from(array('sn' => 'serial_number'))->columns(array('*'));
            $select->leftjoin(array('h' => 'house_view') , getExpSql('h.house_id = sn.house_id AND sn.room_id= h.record_id'));
            /**
             * 权限
             */
            $user = $this->getUserInfo();
            if (!$user['is_manager'])
            {
                $permissions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisionsTable = $permissions->VerifyDataCollectionsPermissions($permissions::USER_AUTHENTICATOR , $user['user_id'] , 0);
                $join = new \Zend\Db\Sql\Predicate\Expression('(sn.house_id=pa.authenticatee_id and sn.house_id>0 and pa.source='.\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE.') or (sn.room_id=pa.authenticatee_id and sn.room_id>0  and pa.source='.\Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED.')');
                $select->join(array('pa' => new \Zend\Db\Sql\Predicate\Expression($permisionsTable)) , $join , 'authenticatee_id' , $select::JOIN_LEFT);
                $authWhere = new \Zend\Db\Sql\Where();
                $authWhere2 = clone $authWhere;
                $authWhere->isNotNull('pa.authenticatee_id');
                $authWhere2->isNull('pa.authenticatee_id');
                $authWhere2->equalTo('sn.user_id' , $user['user_id']);
                $authWhere->orPredicate($authWhere2);
                $where->addPredicate($authWhere);
            }

            $HouseList = $select->where($where)->execute();

            $HouseList = getArrayKeyClassification($HouseList , 'house_id');

            $data = array();
            $configure_config = array('12' , '8' , '7');//房间需要展示配置属性

            $house_room_id = array();
            foreach ($HouseList as $house_info)
            {
                $info = $house_info[0];
                $house_configure = explode('-' , $info['public_facilities']);
                $house_configure = count($house_configure) > 0 ? $house_configure : array();
                $data[$info['community_id']] = array(
                    'community_id' => $info['community_id'] ,
                    'community_name' => $info['community_name'] ,
                );
                $data[$info['community_id']]['list'][$info['house_id']] = array(
                    'house_id' => $info['house_id'] ,
                    'rental_way' => $info['rental_way'] ,
                    'community_id' => $info['community_id'] ,
                    'house_name' => $info['house_name'] ,
                    'cost' => $info['cost'] ,
                    'unit' => $info['unit'] ,
                    'number' => $info['number'] ,
                    'custom_number' => $info['custom_number'] ,
                    'house_configure' => $house_configure ,
                );


                foreach ($house_info as $info)
                {
                    $configure = array();

                    if (isset($house_room_id[$info['house_id']][$info['record_id']]))
                        continue;

                    if ($info['rental_way'] == '1')
                    {
                        $configure = array();
                        $data[$info['community_id']]['list'][$info['house_id']]['list'][] = array(
                            'room_id' => $info['record_id'] ,
                            'status' => $info['status'] ,
                            'is_yytz' => $info['is_yytz'] ,
                            'is_yd' => $info['is_yd'] ,
                            'room_type' => $info['room_type'] ,
                            'room_number' => $info['custom_number'] ,
                            'room_configure' => $configure ,
                        );
                    }
                    else
                    {
                        $data[$info['community_id']]['list'][$info['house_id']]['list'][] = array(
                            'room_id' => $info['record_id'] ,
                            'status' => $info['status'] ,
                            'is_yytz' => $info['is_yytz'] ,
                            'is_yd' => $info['is_yd'] ,
                            'room_configure' => $configure ,
                        );
                    }
                    $house_room_id[$info['house_id']][$info['record_id']] = 1;
                }
            }

            foreach ($data as $key => $info)
            {
                $data[$key]['list'] = array_values($info['list']);
            }
            $return[] = array_values($data);
            $select = M('User')->getSqlObject()->select();
            $select->from(array('rf' => 'room_focus'))->leftjoin(array('r' => 'serial_number') , getExpSql('rf.room_focus_id= r.room_id') , array())->leftjoin(array('f' => 'flat') , getExpSql('rf.flat_id= f.flat_id') , array())->where(array('r.is_delete' => '0' , 'r.company_id' => $company_id , 'r.house_type' => '2', 'f.is_delete' => 0))->group('rf.room_focus_id');

            if (!$user['is_manager'])
            {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisions->VerifyDataCollectionsPermissionsModel($select , 'rf.flat_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
            }
            $HouseList = $select->execute();
            $result = array();

            if (!emptys($HouseList))
            {
                $flat_list = M('Flat')->getData(array('is_delete' => '0' , 'company_id' => $company_id) , array('flat_id' , 'flat_name'));
                $flat_list = getArrayKeyClassification($flat_list , 'flat_id' , 'flat_name');
            }

            foreach ($HouseList as $info)
            {
                if (!isset($result[$info['flat_id']]))
                {
                    $result[$info['flat_id']] = array(
                        'flat_id' => $info['flat_id'] ,
                        'flat_name' => (string) $flat_list[$info['flat_id']] ,
                    );
                }
                $result[$info['flat_id']]['list'][$info['floor']]['floor'] = $info['floor'];
                $result[$info['flat_id']]['list'][$info['floor']]['data'][] = array(
                    'custom_number' => $info['custom_number'] ,
                    'focus_id' => $info['room_focus_id'] ,
                    'status' => $info['status'] ,
                    'is_yd' => $info['is_yd'] ,
                    'is_yytz' => $info['is_yytz'] ,
                );
            }

            foreach ($result as $key => $info)
            {
                $result[$key]['list'] = array_values($info['list']);
            }
            $return[] = array_values($result);

            $select = M('User')->getSqlObject()->select();
            $tenant = $select->from(array('t' => 'tenant'))->columns(array('name' , 'phone' , 'tenant_id'))->group('t.tenant_id')->leftjoin(array('r' => 'reserve') , getExpSql('t.tenant_id= r.tenant_id') , array())->where(array('r.is_delete' => '0' , 't.company_id' => $company_id))->execute();


            $liushui_list = M('SerialNumber')->getData(array('company_id' => $company_id , 'is_delete' => 0) , array('pay_time'));

            $date = array();

            foreach ($liushui_list as $info)
            {
                $time = $info['pay_time'];

                if (!is_time($time))
                    continue;
                $year = date('Y' , $time);
                $month = date('m' , $time);

                if (!isset($date[$year]))
                    $date[$year] = array(
                        'year' => $year ,
                        'month_list' => array()
                    );
                if (!in_array($month , $date[$year]['month_list']))
                    $date[$year]['month_list'][] = $month;
            }
            $time = array(
                '1' => '收入' ,
                '2' => '支出' ,
            );
            return_success(array('date' => array_values($date) , 'house_list' => $return , 'type' => $time));
        }

        function addMeterReadingAction()
        {
            PV(array('house_type' , 'house_id' , 'meter_list'));
            $meter_list = I('meter_list' , '' , 'trim');
            $meterReadingHelper = new \Common\Helper\Erp\MeterReading();
            $meter_list = is_array($meter_list) ? $meter_list : json_decode($meter_list , true);
            $house_type = I('house_type');
            $house_id = I('house_id');
            $user = $this->getUserInfo();
            $MR = M('MeterReading');
            $MRM = M('MeterReadingMoney');

            foreach ($meter_list as $info)
            {
                if (!is_numeric($info['meter_id']))
                {
                    $room_id = $info['room_id'];
                    $meter_reading_data = array();
                    $meter_reading_data['before_meter'] = $info['before_meter'];
                    $meter_reading_data['now_meter'] = $info['now_meter'];
                    $meter_reading_data['add_time'] = strtotime($info['add_time']);
                    $meter_reading_data['fee_type_id'] = $info['fee_type_id'];
                    $meter_reading_data['creat_user_id'] = $user['user_id'];
                    $meter_reading_data['house_type'] = $house_type;
                    $meter_reading_data['house_id'] = (int) $house_id;
                    $meter_reading_data['room_id'] = (int) $room_id;

                    $meter_reading_data['create_time'] = time();
                    $meter_reading_data['is_sf'] = 0;


                    $add = $MR->insert($meter_reading_data);

                    if (!$add)
                        return_error(142);
                    $meter_reading_data['company_id'] = $user['company_id'];
                    $meter_reading_data['meter_price'] = $info['money'];
                    $meterReadingHelper->creatMoney($meter_reading_data , $house_type , $add, array('data' => 1));
                }else
                {
                    $meter_reading_data = array();
                    $meter_reading_data['before_meter'] = $info['before_meter'];
                    $meter_reading_data['now_meter'] = $info['now_meter'];
                    $meter_reading_data['add_time'] = strtotime($info['add_time']);
                    $edit = $MR->edit(array('meter_id' => $info['meter_id']) , $meter_reading_data);
                    if (!$edit)
                        return_error(142);
                }
            }
            return_success();
        }

        function getMeterReadingListAction()
        {
            PV(array('house_type'));
            $F = T('Fee');
            $house_type = I('house_type');
            $house_id = I('house_id');
            $room_id = I('room_id');
            if ($house_type == 1)
            {
                // $where = "  (source = 'SOURCE_DISPERSE_ROOM' AND source_id ='{$room_id}' OR (source = 'SOURCE_DISPERSE' AND source_id ='{$house_id}'))";
                if ($room_id > 0)
                    $where = "  (source = 'SOURCE_DISPERSE' AND source_id ='{$house_id}')";
                else
                    $where = "(source = 'SOURCE_DISPERSE' AND source_id ='{$house_id}')";
            }
            else
            {
                $where = "(source = 'SOURCE_FLAT' AND source_id ='{$house_id}')";
            }

            $where .=" AND payment_mode IN(3,4,5)";
            $fee_list = $F->where($where)->select();

            if (emptys($fee_list))
                return_success();
            $MeterReadingModel = new \Common\Model\Erp\MeterReading();
            $sql = $MeterReadingModel->getSqlObject();

            $data = array();

            foreach ($fee_list as &$info)
            {
                $select = $sql->select(array("mr" => 'meter_reading'));
                $id = $info['source_id'];
                $where = array();
                $where["mr.house_type"] = $house_type;
                $where["mr.fee_type_id"] = $info['fee_type_id'];
                if ($info['source'] == 'SOURCE_FLAT')
                    $where["mr.room_id"] = $room_id;
                else if ($info['source'] == 'SOURCE_DISPERSE')
                    $where["mr.house_id"] = $id;
                else if ($info['source'] == 'SOURCE_DISPERSE_ROOM')
                    $where["mr.room_id"] = $id;
                $select->where($where);
                $select->order("mr.create_time desc");
                $result = $select->execute();
                $result = is_array($result) ? $result[0] : array();

                $info['before_meter'] = (int) $result['before_meter'];
                $info['now_meter'] = (int) $result['now_meter'];
                $info['add_time'] = (int) $result['add_time'];
                $info['is_sf'] = (int) $result['is_sf'];
                $info['meter_id'] = (string) $result['meter_id'];

                if (!isset($data[$info['fee_type_id']]))
                    $data[$info['fee_type_id']]['fee_type_id'] = $info['fee_type_id'];

                $data[$info['fee_type_id']]['fee_list'][] = $info;
            }

            return_success(array_values($data));
        }

        function infoAction()
        {
            PV('serial_id');
            $serial_id = I('serial_id');
            $company_id = $this->getCompanyId();
            $SN = M('SerialNumber');
            $info = $SN->getOne(array('serial_id' => $serial_id , 'company_id' => $company_id));

            if (emptys($info))
                return_success();

            $user_info = M('UserExtend')->getOne(array('user_id' => $info['user_id']));

            $select = $SN->getSqlObject()->select();
            $list = $select->from(array('sd' => 'serial_detail'))->leftjoin(array('ssb' => 'serial_strike_balance') , 'sd.serial_detail_id=ssb.serial_detail_id' , array('cz_money' => 'money' , 'id' , 'mark'))->where(array('sd.serial_id' => $serial_id , 'sd.is_delete' => 0))->execute();

            $data = array(
                'serial_id' => $info['serial_id'] ,
                'payment_mode' => $info['payment_mode'] ,
                'pay_time' => $info['pay_time'] ,
                'type' => $info['type'] ,
                'receivable' => $info['receivable'] ,
                'money' => $info['money'] ,
                'subscribe_pay_time' => $info['subscribe_pay_time'] ,
                'final_money' => $info['final_money'] ,
                'status' => $info['status'] ,
                'remark' => $info['remark'] ,
                'user_name' => $user_info['name'] ,
            );
            foreach ($list as $info)
            {
                $data['fee_list'][] = array(
                    'fee_type_id' => $info['fee_type_id'] ,
                    'serial_detail_id' => $info['serial_detail_id'] ,
                    'money' => $info['money'] ,
                    'final_money' => $info['final_money'] ,
                    'mark' => (string) $info['mark'] ,
                    'cz_id' => ((int) $info['id']) . '' ,
                    'cz_money' => is_numeric($info['cz_money']) ? $info['cz_money'] : "0" ,
                );
            }
            return_success($data);
        }

        function getReadingSelectListAction()
        {
            $company_id = $this->getCompanyId();

            $select = M('User')->getSqlObject()->select();
            $HouseList = $select->from(array('h' => 'house_view'))->leftjoin(array('r' => 'fee') , getExpSql("(r.source='SOURCE_DISPERSE' AND  h.house_id = r.source_id)") , array())->where(array('h.company_id' => $company_id , 'h.status' => 2 , 'h.rental_way' => 2, 'payment_mode' => array('3' , '4' , '5')))->group('h.house_id')->execute();
            $select = M('User')->getSqlObject()->select();
            $RoomList = $select->from(array('h' => 'house_view'))->leftjoin(array('r' => 'fee') , getExpSql("(r.source='SOURCE_DISPERSE' AND  h.house_id = r.source_id)") , array())->where(array('h.company_id' => $company_id , 'h.status' => 2 , 'h.rental_way' => 1, 'payment_mode' => array('3' , '4' , '5')))->group('h.record_id')->execute();
            $new_arr = array_merge($HouseList, $RoomList);
            $HouseList = getArrayKeyClassification($new_arr , 'house_id');
            $data = array();
            $configure_config = array('12' , '8' , '7');//房间需要展示配置属性

            foreach ($HouseList as $house_info)
            {
                $info = $house_info[0];
                $house_configure = explode('-' , $info['public_facilities']);
                $house_configure = count($house_configure) > 0 ? $house_configure : array();
                $data[$info['community_id']] = array(
                    'community_id' => $info['community_id'] ,
                    'community_name' => $info['community_name'] ,
                );
                $paymode = $this->getFeeInfo(2 , $info['house_id']);
                $paymode = getArrayValue($paymode , 'payment_mode');
                $paymode = array_values(array_unique($paymode));

                $house_paymode = $paymode;


                $data[$info['community_id']]['list'][$info['house_id']] = array(
                    'house_id' => $info['house_id'] ,
                    'rental_way' => $info['rental_way'] ,
                    'community_id' => $info['community_id'] ,
                    'house_name' => $info['house_name'] ,
                    'cost' => $info['cost'] ,
                    'unit' => $info['unit'] ,
                    'number' => $info['number'] ,
                    'custom_number' => $info['custom_number'] ,
                    'house_configure' => $house_configure ,
                    'payment_mode' => $paymode ,
                );
                foreach ($house_info as $info)
                {
                    $configure = array();
                    if ($info['rental_way'] == '1')
                    {
                        $configure = array();
                        $room_configure = explode('-' , $info['room_config']);
                        foreach ($configure_config as $config)
                        {
                            if (array_search($config , $room_configure))
                                $configure[] = $config;
                        }
                        $paymode = $this->getFeeInfo(1 , $info['record_id']);
                        $paymode = getArrayValue($paymode , 'payment_mode');
                        $paymode = array_values(array_unique($paymode));
                        if (count($paymode) == 0)
                            $paymode = $house_paymode;

                        $data[$info['community_id']]['list'][$info['house_id']]['list'][] = array(
                            'room_id' => $info['record_id'] ,
                            'status' => $info['status'] ,
                            'is_yytz' => $info['is_yytz'] ,
                            'is_yd' => $info['is_yd'] ,
                            'room_type' => $info['room_type'] ,
                            'room_number' => $info['custom_number'] ,
                            'room_configure' => $configure ,
                            'payment_mode' => $paymode ,
                        );
                    }
                    else
                    {

                        $paymode = $this->getFeeInfo(2 , $info['house_id']);
                        $paymode = getArrayValue($paymode , 'payment_mode');
                        $paymode = array_values(array_unique($paymode));

                        $data[$info['community_id']]['list'][$info['house_id']]['list'][] = array(
                            'room_id' => $info['record_id'] ,
                            'status' => $info['status'] ,
                            'is_yytz' => $info['is_yytz'] ,
                            'is_yd' => $info['is_yd'] ,
                            'room_configure' => $configure ,
                            'payment_mode' => $paymode ,
                        );
                    }
                }
            }

            foreach ($data as $key => $info)
            {
                $data[$key]['list'] = array_values($info['list']);
            }
            $return['house_list'] = array_values($data);
            $select = M('User')->getSqlObject()->select();
            $HouseList = $select->from(array('h' => 'room_focus'))->leftjoin(array('r' => 'fee') , getExpSql("(r.source='SOURCE_FLAT' AND  h.flat_id = r.source_id) OR (r.source='SOURCE_FOCUS' AND r.source_id= h.room_focus_id)") , array())->where(array('h.company_id' => $company_id , 'h.status' => 2 , 'payment_mode' => array('3' , '4' , '5')))->group('h.room_focus_id')->execute();

            $result = array();

            if (!emptys($HouseList))
            {
                $flat_list = M('Flat')->getData(array('is_delete' => '0' , 'company_id' => $company_id) , array('flat_id' , 'flat_name'));
                $flat_list = getArrayKeyClassification($flat_list , 'flat_id' , 'flat_name');
            }

            foreach ($HouseList as $info)
            {
                if (!isset($result[$info['flat_id']]))
                {
                    $paymode = $this->getFeeInfo(4 , $info['flat_id']);
                    $paymode = getArrayValue($paymode , 'payment_mode');
                    $paymode = array_values(array_unique($paymode));
                    $result[$info['flat_id']] = array(
                        'flat_id' => $info['flat_id'] ,
                        'flat_name' => (string) $flat_list[$info['flat_id']] ,
                        'payment_mode' => $paymode ,
                    );
                }
                $result[$info['flat_id']]['list'][$info['floor']]['floor'] = $info['floor'];
                $paymode = $this->getFeeInfo(3 , $info['room_focus_id']);
                $paymode = getArrayValue($paymode , 'payment_mode');
                $paymode = array_values(array_unique($paymode));
                $result[$info['flat_id']]['list'][$info['floor']]['data'][] = array(
                    'custom_number' => $info['custom_number'] ,
                    'focus_id' => $info['room_focus_id'] ,
                    'status' => $info['status'] ,
                    'is_yd' => $info['is_yd'] ,
                    'is_yytz' => $info['is_yytz'] ,
                    'payment_mode' => $paymode ,
                );
            }

            foreach ($result as $key => $info)
            {
                $result[$key]['list'] = array_values($info['list']);
            }
            $return['flat_list'] = array_values($result);
            return_success($return);
        }

        private function getFeeInfo($type , $id)
        {
            $F = M('Fee');
            if ($type == 4)
            {
                $where['source_id'] = $id;
                $where['source'] = 'SOURCE_FLAT';
            }
            elseif ($type == 3)
            {
                $where['source_id'] = $id;
                $where['source'] = 'SOURCE_FOCUS';
            }
            elseif ($type == 1)
            {

                $where['source_id'] = $id;
                $where['source'] = 'SOURCE_DISPERSE_ROOM';
            }
            else
            {
                $where['source_id'] = $id;
                $where['source'] = 'SOURCE_DISPERSE';
            }
            $f_info = $F->getData($where);
            return $f_info;
        }

        function listdebtsAction()
        {
            $serialNumberModel = new \Common\Model\Erp\SerialNumber();
            $size = I('size');
            $page = I('page');
            $company_id = $this->getCompanyId();
            $list = $serialNumberModel->getDebtsList($company_id , $page , $size);


            foreach ($list['data'] as &$info)
            {
                foreach ($info as &$val)
                {
                    if (is_null($val))
                        $val = '';
                }
            }

            return_success($list);
        }

        function listdebtsinfoAction()
        {
            $serialNumberModel = new \Common\Model\Erp\SerialNumber();

            $user_id = I('user_id' , '');  //用户id
            $source = I('source' , '');
            $source_id = I('source_id' , 0);
            $serial_id = I('sn_id' , 0);  //流水id
            $house_id = I('house_id' , 0);    //房源
            $room_id = I('room_id' , 0);    //分散式房间id
            $flat_id = I('flat_id' , 0);    //集中式公寓
            $room_focus_id = I('room_focus_id' , 0);//集中式房间
            $house_type = I('house_type' , 0);

            $data_continue = array(
                'user_id' => $user_id ,
                'source' => $source ,
                'source_id' => $source_id ,
                'serial_id' => $serial_id ,
                'house_id' => $house_id ,
                'room_id' => $room_id ,
                'flat_id' => $flat_id ,
                'room_focus_id' => $room_focus_id ,
                'house_type' => $house_type ,
            );
            
            $serial_num_helper = new \Common\Helper\Erp\SerialNumber();

            $debts_detail_info = $serial_num_helper->detbtsDetail($data_continue);

            if (!is_array($debts_detail_info))
                return_error(146);
            return_success($debts_detail_info);
        }

        function delSerialArrearsAction()
        {
            PV('sn_id');
            $company_id = $this->getCompanyId();
            $serial_id = I('sn_id');
            $del = M('SerialNumber')->edit(array('company_id' => $company_id , 'serial_id' => $serial_id) , array('is_delete' => 1));
            if (!$del)
            {
                return_error(142);
            }
            return_success();
        }

        function getRoomFeesListAction()
        {
            PV(array('house_type'));
            $house_id = (int) I('house_id');
            $house_type = I('house_type');
            $room_id = (int) I('room_id');
            $contract_id = I('contract_id');
            $meter_reading_helper = new \Common\Helper\Erp\MeterReading();
            $meter_money_data = $meter_reading_helper->getMeterMoneyByRoomId($house_id , $house_type , $room_id);
   
            $serial_helper = new \Common\Helper\Erp\SerialNumber();
            $ROOM = new \App\Api\Helper\Room();
            $company_id = $this->getCompanyId();
            $tenant_fee = array();
            $tenant_model = new \Common\Model\Erp\TenantContract();

            if (!is_numeric($contract_id))
            {
                $result = $ROOM->getRoomContract($company_id , $house_type , $house_id , $room_id);
            }
            else
            {

                $select = $tenant_model->getSqlObject()->select();
                $result = $select->from(array('tc' => 'tenant_contract'))->columns(array(
                            'contract_id' ,
                            'custom_number' ,
                            'rent' ,
                            'advance_time' ,
                            'signing_time' ,
                            'dead_line' ,
                            'end_line' ,
                            'deposit' ,
                            'detain' ,
                            'parent_id' ,
                            'pay' ,
                            'next_pay_time' ,
                            'deposit' ,
                            'remark' ,
                        ))->join(
                                array('r' => 'rental')
                                , 'tc.contract_id=r.contract_id'
                                , array(
                            'house_id' , 'room_id' , 'house_type' , 'tenant_id'
                        ))->where(
                                array(
                                    'tc.contract_id' => $contract_id
                                )
                        )->order('contract_id desc')->execute();
            }

            if (count($result) == 0)
                return_error(148);
            $contract_info = $result[0];

            $SN = M('SerialNumber');
            $Fee = H('Fee');
            //查询是否有收过费 

            $count = $SN->getCount(array('source_id' => $contract_info['contract_id'] , 'source' => 'tenant_contract'));

            $tenant_money[0] = $contract_info;

            //获取用户所有的费用
            $feeTypeModel = new \App\Api\Helper\FeeType();
            $user = $this->getUserInfo();
            $fee_list = $feeTypeModel->getFeeTypeListByCompanyID($user['company_id']);
            $fee_list = getArrayKeyClassification($fee_list , 'type_name' , 'fee_type_id');

            $data = array();
            $data[] = array(
                'fee_type_id' => $fee_list['租金'] ,
                'money' => (string) ($contract_info['pay'] > 0 ? $contract_info['rent'] * $contract_info['pay'] : $contract_info['rent']) ,
            );

            //未收过费应该有押金选项  续租合同不能收费
            if ($count == 0 && $contract_info['parent_id'] <= 0)
            {
                $data[] = array(
                    'fee_type_id' => $fee_list['押金'] ,
                    'money' => $contract_info['deposit'] ,
                );
            }
            foreach ($meter_money_data as $info)
            {
                if ($info['money'] <= 0)
                    continue;
                if (isset($data[$info['fee_type_id']]))
                {
                    $data[$info['fee_type_id']]['money'] = (string) ($data[$info['fee_type_id']]['money'] + $info['money']);
                    continue;
                }
                $data[$info['fee_type_id']] = array(
                    'fee_type_id' => $info['fee_type_id'] ,
                    'money' => (string) $info['money'] ,
                    'meter_id' => $info['meter_id'] ,
                );
            }

            $fee_helper = new \Common\Helper\Erp\Fee();    //费用表helper
            $fee_model = new \Common\Model\Erp\Fee();
            $room_helper = new \Common\Helper\Erp\Room();
            //获取房间费用配置
            $tenant_contract_id = $contract_info['contract_id'];

            if ($house_type == 1)
            {//分散式(整/合)
                $fee_source = 'SOURCE_DISPERSE';
                $fee_source_id = $contract_info['house_id'];
                $fee_data = $Fee->getRoomFeeInfo($fee_source_id , '' , $fee_source);
            }
            else
            {//集中式
                $fee_source = 'SOURCE_FOCUS';
                $fee_source_id = $contract_info['room_id'];
                $fee_data = $Fee->getRoomFeeInfo($fee_source_id , '' , $fee_source);
                if (count($fee_data) == 0)
                {
                    $flat_info = M('RoomFocus')->getOne(array('room_focus_id' => $fee_source_id));
                    $fee_data = $Fee->getRoomFeeInfo($flat_info['flat_id'] , '' , 'SOURCE_FLAT');
                }
            }

            //如果这个房间有费用配置
            $fee_data = is_array($fee_data) ? $fee_data : array();
            $data = getArrayKeyClassification($data , 'fee_type_id' , 'ALL');

            $fee_id = $serial_helper->checkDisposableFee($tenant_contract_id , $fee_data);

            foreach ($fee_data as $key => $fee_info)
            {

                if (isset($data[$fee_info['fee_type_id']]['meter_id']) || in_array($fee_info['fee_type_id'] , $fee_id))
                    continue;

                if (!isset($data[$fee_info['fee_type_id']]) && !in_array($fee_info['payment_mode'] , array(1 , 2 , 6 , 7)))
                    continue;

                //续租的合同 不再收一次性费用
                if ($contract_info['parent_id'] > 0 && ($fee_info['payment_mode'] == 1 OR $fee_info['payment_mode'] == 6))
                    continue;

                if ($fee_info['payment_mode'] == 1)
                {
                    //1 按房间一次性收费
                    $fee_data[$key]['money'] = $fee_info['money'];
                }
                elseif ($fee_info['payment_mode'] == 2)
                {
                    //2 按房间每月收费
                    $fee_data[$key]['money'] = $fee_info['money'] * $tenant_money[0]['pay'];
                }
                elseif ($fee_info['payment_mode'] == 6)
                {
                    //6 按人头一次性收费
                    $contract_rental_model = new \Common\Model\Erp\ContractRental();
                    $cr_where = array('contract_id' => $tenant_contract_id , 'is_delete' => 0);
                    $cr_columns = array('total_pople' => getExpSql('count(contract_rental_id)'));
                    $total_pople = $contract_rental_model->getOne($cr_where , $cr_columns);
                    $fee_data[$key]['money'] = $fee_info['money'] * $total_pople['total_pople'];
                }
                elseif ($fee_info['payment_mode'] == 7)
                {
                    //7 按人头每月收费
                    $contract_rental_model = new \Common\Model\Erp\ContractRental();
                    $cr_where = array('contract_id' => $tenant_contract_id , 'is_delete' => 0);
                    $cr_columns = array('total_pople' => getExpSql('count(contract_rental_id)'));
                    $total_pople = $contract_rental_model->getOne($cr_where , $cr_columns);
                    $fee_data[$key]['money'] = $fee_info['money'] * $tenant_money[0]['pay'] * $total_pople['total_pople'];
                }



                if (!isset($data[$fee_info['fee_type_id']]))
                {
                    $data[$fee_info['fee_type_id']] = array(
                        'fee_type_id' => $fee_info['fee_type_id'] ,
                    );
                }

                $data[$fee_info['fee_type_id']]['money'] = (string) $fee_data[$key]['money'];
            }

            foreach ($data as $key => $val)
            {
                $data[$key]['money'] = strpos($val['money'] , '.') !== false ? $val['money'] : $val['money'] . '.00';
            }


            unset($fee_data);
            return_success(array_values($data));
        }

    }
    