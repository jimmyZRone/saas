<?php

    namespace Common\Helper\Erp;

    use Zend\Db\Sql\Expression;
    use Zend\Db\Sql\Where;

    /**
     * 财务
     * @author lishengyou
     * 最后修改时间 2015年3月27日 上午10:22:47
     *
     */
    class SerialNumber
    {

        /**
         * @param int $cid 公司company_id
         * return 多维数组或一维数组
         * 根据company_id获取该公司下  未租状态  集中式房源  所有   的房源信息
         * 涉及的表有 room_focus,flat
         * @author ft|最后修改时间 2015年4月21日 下午4:26:12
         */
        public function getRoomData($cid , $param)
        {
            $ji = new \Common\Model\Erp\HouseFocusView();//集中式
            $sql = $ji->getSqlObject();
            $select = $sql->select(array('hfv' => $ji->getTableName('house_focus_view')));
            $select->columns(array('id' => 'record_id' , 'name' => 'flat_name' , 'detain' => 'detain' , 'pay' => 'pay' , 'money' => 'money' , 'custom_number' => 'custom_number' , 'floor' => 'floor'));
            $where = new \Zend\Db\Sql\Where();//造where条件对象
            $where1 = clone $where;
            $where->equalTo('hfv.status' , 1);//未租状态
            $where->equalTo('hfv.is_delete' , 0);
            $where->equalTo('hfv.company_id' , $cid);//取当前用户所在公司的房源
            if (!empty($param))
            {
                $where1->like('hfv.flat_name' , "%$param%");
                $where1->or;
                $where1->like('hfv.address' , "%$param%");
                $where->addPredicate($where1);
            }
            $select->where($where);
            $data = $select->execute();
            $fen = new \Common\Model\Erp\HouseView();//分散式
            $sql = $fen->getSqlObject();
            $select = $sql->select(array('hv' => $fen->getTableName('house_view')));
            $select->columns(array('id' => 'house_id' , 'name' => 'house_name' , 'detain' => 'detain' , 'pay' => 'pay' , 'money' => 'money' , 'cost' => 'cost' , 'unit' => 'unit' , 'number' => 'number'));
            $select->leftjoin(array('c' => 'community') , 'hv.community_id=c.community_id' , array('community_name' => 'community_name'));
            $where = new \Zend\Db\Sql\Where();//造where条件对象
            $where2 = clone $where;
            $where->equalTo('hv.status' , 1);//未租状态
            $where->equalTo('hv.is_delete' , 0);
            $where->equalTo('hv.company_id' , $cid);//取当前用户所在公司的房源
            $where->equalTo('c.company_id' , $cid);
            if (!empty($param))
            {
                $where2->like('hv.house_name' , "%$param%");
                $where2->or;
                $where2->like('c.community_name' , "%$param%");
                $where->addPredicate($where2);
            }
            $select->where($where);
            $data1 = $select->execute();
            if (is_array($data1) && is_array($data))
            {
                return array_merge($data1 , $data);
            }
            if (is_array($data1) && !is_array($data))
            {
                return $data1;
            }
            if (is_array($data) && !is_array($data1))
            {
                return $data;
            }
        }

        /**
         * 获取房间信息列表
         * 修改时间 2015年4月22日18:25:27
         * 
         * @author ft
         */
        public function getRoomInfoList($cid , $page , $size)
        {
            $serial_number_model = new \Common\Model\Erp\SerialNumber();
            $sql = $serial_number_model->getSqlObject();
            $select = $sql->select(array('sn' => $serial_number_model->getTableName()));
            $select->columns(
                    array(
                        'sn_id' => 'serial_id' ,
                        'serial_number' => 'serial_number' ,
                        'serial_name' => 'serial_name' ,
                        'pay_time' => 'pay_time' ,
                        'room_id' => 'room_id' ,
                        'sn_money' => 'money' ,
            ));
            $select->leftjoin(array('ssb' => 'serial_strike_balance') , 'sn.serial_id = ssb.serial_id' , array('ssb_money' => 'money'));
            $select->leftjoin(
                    array('h' => 'house') , 'h.house_id = sn.house_id' , array(
                'h_name' => 'house_name' ,
                'h_custom_num' => 'custom_number' ,
                'h_cost' => 'cost' ,
                'h_unit' => 'unit' ,
                'h_number' => 'number'
            ));
            $select->leftjoin(array('r' => 'room') , 'h.house_id = r.house_id' , array('r_custom_num' => 'custom_number'));
            $select->leftjoin(array('rf' => 'room_focus') , 'sn.room_id = rf.room_focus_id' , array('rf_custom_num' => 'custom_number'));
            $select->leftjoin(array('f' => 'flat') , 'rf.flat_id = f.flat_id' , array('f_name' => 'flat_name' , 'f_custom_num' => 'custom_number' , 'f_address' => 'address'));
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('sn.room_id' , new \Zend\Db\Sql\Predicate\Expression('r.room_id'));
            $where->equalTo('sn.company_id' , $cid);
            $where->equalTo('sn.house_type' , 1);
            $where->OR;
            $where->equalTo('sn.house_type' , 2);
            $select->where($where);
            $data = \Core\Db\Sql\Select::pageSelect($select , null , $page , $size);
            return $data;
        }

        /**
         * 编辑房间费用类型
         * 修改时间 2015年4月23日19:04:26
         *
         * @author ft
         */
        public function editRoomFeeType($cid , $serial_id , $room_id , $house_type , $house_id = 0 , $source_arr = array())
        {
            $serial_number_model = new \Common\Model\Erp\SerialNumber();
            $sql = $serial_number_model->getSqlObject();
            if ($house_type == 1)
            {
                //分散式合租
                if ($room_id > 0)
                {
                    //查询分散式房间的名字信息
                    $select = $sql->select(array('r' => 'room'));    //这条sql 是根据房间id 查询房间的名字
                    $select->columns(
                            array(
                                'room_id' => 'room_id' ,
                                'r_custom_num' => 'custom_number' ,
                                'room_type' => 'room_type' ,
                    ));
                    $select->leftjoin(
                            array('h' => 'house') , 'h.house_id = r.house_id' , array(
                        'h_name' => 'house_name' ,
                        'h_custom_num' => 'custom_number' ,
                        'h_cost' => 'cost' ,
                        'h_unit' => 'unit' ,
                        'h_number' => 'number' ,
                        'rental_way' => 'rental_way'
                    ));
                    $where = new \Zend\Db\Sql\Where();

                    $where2 = clone $where;    //克隆一个where对象 留到下面用

                    $where->equalTo('h.company_id' , $cid);
                    $where->equalTo('r.room_id' , $room_id);
                    $select->where($where);
                    $room_info = $select->execute();
                    $room_info[0]['room_type'] = ($room_info[0]['room_type'] == 'main') ? '主卧' : (($room_info[0]['room_type'] == 'second') ? '次卧' : (($room_info[0]['room_type'] == 'guest') ? '客卧' : ''));
                }
                elseif ($house_id > 0 && $room_id == 0)
                {
                    //分散式整组
                    $select = $sql->select(array('h' => 'house'));
                    $select->columns(
                            array(
                                'h_name' => 'house_name' ,
                                'h_custom_num' => 'custom_number' ,
                                'h_cost' => 'cost' ,
                                'h_unit' => 'unit' ,
                                'h_number' => 'number' ,
                                'rental_way' => 'rental_way'
                    ));
                    $where = new \Zend\Db\Sql\Where();

                    $where2 = clone $where;    //克隆一个where对象 留到下面用

                    $where->equalTo('h.company_id' , $cid);
                    $where->equalTo('h.house_id' , $house_id);
                    $select->where($where);
                    $room_info = $select->execute();
                }
                elseif ($source_arr['source'] == 'landlord_contract')
                {
                    $select = $sql->select(array('lc' => 'landlord_contract'));
                    $select->columns(array('h_name' => 'hosue_name'));
                    $where = new \Zend\Db\Sql\Where();
                    $where2 = clone $where;
                    $where->equalTo('lc.contract_id' , $source_arr['source_id']);
                    $select->where($where);
                    $room_info = $select->execute();
                }
            }
            elseif ($house_type == 2)
            {
                if ($room_id)
                {
                    //查询集中式房间的名字信息
                    $select = $sql->select(array('rf' => 'room_focus'));    //这条sql 是根据房间id 查询房间的名字
                    $select->columns(
                            array(
                                'room_focus_id' => 'room_focus_id' ,
                                'rf_floor' => 'floor' ,
                                'rf_custom_num' => 'custom_number' ,
                    ));
                    $select->leftjoin(
                            array('f' => 'flat') , 'f.flat_id = rf.flat_id' , array(
                        'flat_name' => 'flat_name' ,
                    ));
                    $where = new \Zend\Db\Sql\Where();

                    $where2 = clone $where;    //克隆一个where对象 留到下面用

                    $where->equalTo('f.company_id' , $cid);
                    $where->equalTo('rf.room_focus_id' , $room_id);
                    $select->where($where);
                    $room_info = $select->execute();
                }
                elseif ($source_arr['source'] == 'landlord_contract')
                {
                    $select = $sql->select(array('lc' => 'landlord_contract'));
                    $select->columns(array('h_name' => 'hosue_name'));
                    $where = new \Zend\Db\Sql\Where();
                    $where2 = clone $where;
                    $where->equalTo('lc.contract_id' , $source_arr['source_id']);
                    $select->where($where);
                    $room_info = $select->execute();
                }
            }

            //查询房间的费用类型
            $select2 = $sql->select(array('sn' => 'serial_number')); //这条sql 是根据房间对应的流水id 查询房间的租金,电费等
            $select2->columns(array('sn_id' => 'serial_id'));
            $select2->leftjoin(
                    array('sd' => 'serial_detail') , 'sn.serial_id = sd.serial_id' , array(
                'sd_id' => 'serial_detail_id' ,
                'fee_type_id' => 'fee_type_id' ,
                'sd_money' => 'money' ,
            ));
            $select2->leftjoin(array('ft' => 'fee_type') , 'sd.fee_type_id = ft.fee_type_id' , array('type_name' => 'type_name'));
            $where2->equalTo('sn.serial_id' , $serial_id);
            $where2->equalTo('ft.company_id' , $cid);
            //$where2->equalTo('ft.is_delete' , 0);
            $where2->equalTo('sn.is_delete' , 0);
            //$where2->equalTo('sd.is_delete' , 0);
            $select2->where($where2);
            $room_fee_info = $select2->execute();

            //查询房间流水
            $room_serial_select = $sql->select(array('sn' => 'serial_number'));
            $room_serial_select->columns(
                    array(
                        'house_id' => 'house_id' ,
                        'house_type' => 'house_type' ,
                        'pay_time' => 'pay_time' ,
                        'subscribe_pay_time' => 'subscribe_pay_time' ,
                        'receivable' => 'receivable' ,
                        'money' => 'money' ,
                        'payment_mode' => 'payment_mode' ,
                        'remark' => 'remark' ,
                        'status' => 'status'
            ));
            $room_serial_where = new \Zend\Db\Sql\Where();
            $room_serial_where->equalTo('sn.serial_id' , $serial_id);
            $room_serial_where->equalTo('sn.room_id' , $room_id);
            $room_serial_select->where($room_serial_where);
            $room_seria_info = $room_serial_select->execute();
            $data = array_merge($room_info , $room_fee_info , $room_seria_info);
            return $data;
        }

        /**
         * 获取非房间流水费用类型
         * 修改时间 2015年6月5日17:42:39
         * 
         * @author ft
         */
        public function getNotRoomFeeType($serial_id)
        {
            $serial_model = new \Common\Model\Erp\SerialNumber();
            $city_model = new \Common\Model\Erp\City();
            $sql = $serial_model->getSqlObject();
            $select = $sql->select(array('sn' => 'serial_number'));
            $select->columns(
                    array(
                        'sn_id' => 'serial_id' ,
                        'serial_number' => 'serial_number' ,
                        'serial_name' => 'serial_name' ,
                        'pay_time' => 'pay_time' ,
                        'room_id' => 'room_id' ,
                        'sn_money' => 'money' ,
                        'payment_mode' => 'payment_mode' ,
                        'remark' => 'remark' ,
                        'city_id' => 'city_id',
            ));
            $select->leftjoin(
                    array('sd' => 'serial_detail') , 'sn.serial_id = sd.serial_id' , array('sd_id' => 'serial_detail_id' , 'sd_money' => 'money'));
            $select->leftjoin(
                    array('ft' => 'fee_type') , 'sd.fee_type_id = ft.fee_type_id' , array(
                'fee_type_id' => 'fee_type_id' ,
                'type_name' => 'type_name' ,
                'sys_type_id' => 'sys_type_id'
            ));
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('sn.serial_id' , $serial_id);
            $select->where($where);
            $not_room_data = $select->execute();
            $city_name = $city_model->getOne(array('city_id' => $not_room_data[0]['city_id']), array('name' => 'name'));
            $not_room_info[] = array_merge($not_room_data[0], $city_name);
            unset($not_room_data);
            return $not_room_info;
        }

        /**
         * 欠费清单房间流水查询
         * 修改时间 2015年5月19日15:08:45
         * 
         * @author ft
         */
        public function getDebtsRoomSerial($debts_id , $room_id , $room_focus_id , $house_id)
        {
            $city_modle = new \Common\Model\Erp\City();
            $serial_num_model = new \Common\Model\Erp\SerialNumber();
            if (!$room_id && !$house_id && !$room_focus_id) {
                $sql = $serial_num_model->getSqlObject();
                $select = $sql->select(array('sn' => 'serial_number'));
                $select->columns(array('debts_id' => 'serial_id' , 'subscribe_pay_time' => 'subscribe_pay_time' , 'receivable' => 'receivable' , 'house_type' => 'house_type', 'city_id' => 'city_id'));
                $where = new \Zend\Db\Sql\Where();
                $where->equalTo('sn.serial_id' , $debts_id);
                $select->where($where);
                $debts_info = $select->execute();
                $city_name = $city_modle->getOne(array('city_id' => $debts_info[0]['city_id']), array('name' => 'name'));
                $debts_info[0]['house_name'] = $city_name['name'];
                return $debts_info;
            }
            if ($house_id)
            {//分散式
                $sql = $serial_num_model->getSqlObject();
                $select = $sql->select(array('sn' => 'serial_number'));
                $select->columns(array('debts_id' => 'serial_id' , 'subscribe_pay_time' => 'subscribe_pay_time' , 'receivable' => 'receivable' , 'house_type' => 'house_type'));
                if ($room_id > 0)
                {//合租
                    $select->leftjoin(
                            array('h' => 'house') , 'sn.house_id = h.house_id' , array(
                        'h_name' => 'house_name' ,
                        'h_custom_num' => 'custom_number' ,
                        'h_cost' => 'cost' ,
                        'h_unit' => 'unit' ,
                        'h_number' => 'number' ,
                        'rental_way' => 'rental_way' ,
                    ));
                    $select->leftjoin(
                            array('r' => 'room') , 'sn.room_id = r.room_id' , array(
                        'r_custom_num' => 'custom_number' , 'room_type' => 'room_type' ,
                    ));
                    $where = new \Zend\Db\Sql\Where();
                    $where->equalTo('sn.serial_id' , $debts_id);
                    $where->equalTo('sn.room_id' , $room_id);
                    $select->where($where);
                    $Shared_data = $select->execute();
                    $Shared_data[0]['room_type'] = ($Shared_data[0]['room_type'] == 'main' ? '主卧' : (($Shared_data[0]['room_type'] == 'second') ? '次卧' : (($Shared_data[0]['room_type'] == 'guest' ? '客卧' : ''))));
                    return $Shared_data;
                }
                else
                {//整组
                    $select->leftjoin(
                            array('h' => 'house') , 'sn.house_id = h.house_id' , array(
                        'h_name' => 'house_name' ,
                        'h_custom_num' => 'custom_number' ,
                        'h_cost' => 'cost' ,
                        'h_unit' => 'unit' ,
                        'h_number' => 'number' ,
                        'rental_way' => 'rental_way'
                    ));
                    $where = new \Zend\Db\Sql\Where();
                    $where->equalTo('sn.serial_id' , $debts_id);
                    $where->equalTo('sn.house_id' , $house_id);
                    $select->where($where);
                    return $select->execute();
                }
            }
            else
            {//集中式
                $sql = $serial_num_model->getSqlObject();
                $select = $sql->select(array('sn' => 'serial_number'));
                $select->columns(array('debts_id' => 'serial_id' , 'subscribe_pay_time' => 'subscribe_pay_time' , 'receivable' => 'receivable' , 'house_type' => 'house_type'));
                $select->leftjoin(array('rf' => 'room_focus') , 'sn.room_id = rf.room_focus_id' , array('floor' => 'floor' , 'rf_custom_num' => 'custom_number'));
                $select->leftjoin(
                        array('f' => 'flat') , 'rf.flat_id = f.flat_id' , array(
                    'flat_name' => 'flat_name' ,
                    'f_custom_num' => 'custom_number' ,
                    'address' => 'address'
                ));
                $where = new \Zend\Db\Sql\Where();
                $where->equalTo('sn.serial_id' , $debts_id);
                $where->equalTo('sn.room_id' , $room_focus_id);
                $select->where($where);
                return $select->execute();
            }
        }

        /**
         * 获取冲账密码
         * 修改时间 2015年4月28日12:34:37
         * 
         * @author ft
         * @param var $cid
         * @param var $passwd
         * @return boolean
         */
        public function getCompanyPassword($cid , $passwd)
        {
            $company_model = new \Common\Model\Erp\Company();
            $cid_pass = $company_model->getOne(array('company_id' => $cid ,));
            if (!$cid_pass)
            {
                return false;
            }
            if ($passwd === false)
            {
                return false;
            }
            $result = $cid_pass['safe_passwd'] === \Common\Helper\Encrypt::sha1($cid_pass['safe_salt'] . $passwd);
            return $result;
        }

        /**
         * 根据流水id和流水详细id 查询 final_money
         * 修改时间  2015年6月30日21:44:11
         * 
         * @author ft
         * @param  $serial_id
         * @param  $detail_id
         */
        public function getSerialFinalMoney($serial_id , $detail_id)
        {
            $serial_model = new \Common\Model\Erp\SerialNumber();
            $sql = $serial_model->getSqlObject();
            $select = $sql->select(array('sn' => 'serial_number'));
            $select->columns(array('sn_final_money' => 'final_money' , 'money' => 'money'));
            //$select->leftjoin(array('sd' => 'serial_detail'), 'sn.serial_id = sd.serial_id', array('sd_final_money' => 'final_money'));
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('sn.serial_id' , $serial_id);
            //$where->equalTo('sd.serial_detail_id', $detail_id);
            $select->where($where);
            return $select->execute();
        }

        /**
         * 欠费清单收费后,保存
         * 修改时间 2015年5月19日17:49:56
         * 
         * @author ft
         */
        public function saveDebtsCharge($data , $debts_id , $father_id)
        {
            $serial_num_model = new \Common\Model\Erp\SerialNumber();
            $serialDetailModel = new \Common\Model\Erp\SerialDetail();
            $debts_where = array('serial_id' => $debts_id);//欠费清单流水修改条件
            $serial_where = array('serial_id' => $father_id);//欠费 父流水 修改条件
            $serial_data = array('status' => 0);//欠费 父流水 修改数据
            $detail_serial = $data['cost'];
            $serial_num_model->Transaction();
            $edit_debts_res = $serial_num_model->edit($debts_where , $data);
            if (!$edit_debts_res)
            {
                $serial_num_model->rollback();
                return false;
            }
            if ($edit_debts_res)
            {
                $serial_res = $serial_num_model->edit($serial_where , $serial_data);
                //将收取的费用, 加入到流水详细
                foreach ($detail_serial as $cost)
                {
//                 $serial_detail_data['serial_id'] = $father_id;
                    $serial_detail_data['serial_id'] = $debts_id;
                    $serial_detail_data['fee_type_id'] = $cost['cost_type'];
                    $serial_detail_data['money'] = $cost['cost_num'];
                    $serial_detail_data['final_money'] = $cost['cost_num'];
                    $serial_detail_result[] = $serialDetailModel->addSerialDetail($serial_detail_data);
                }
            }
            if (!$serial_res)
            {
                $serial_num_model->rollback();
                return false;
            }
            if ($edit_debts_res && $edit_debts_res && $serial_detail_result)
            {
                $serial_num_model->commit();
                return true;
            }
        }

        /**
         * 获取当月已收流水
         * 修改时间 2015年4月29日10:26:00
         * 
         * @author ft
         * @return array $data 
         */
        public function getCurrentMonthSerial($company_id , $user)
        {
            //echo date('t');    //获得当月总共天数
            //echo date('j'),'<br><br>';    //获得当天是当月的第几天
            $serial_model = new \Common\Model\Erp\SerialNumber();
            $serial_number_helper = new \Common\Helper\Erp\SerialNumber();
            //分散式sql
            $dis_sql = $serial_model->getDisAndFocusSql($user , 1);
            $dis_arr = array(new Expression($dis_sql));
            //集中式sql
            $f_sql = $serial_model->getDisAndFocusSql($user , 2);
            $f_arr = array(new Expression($f_sql));
            //业主sql
            $lc_sql = $serial_model->getDisAndFocusSql($user , 3);
            $lc_arr = array(new Expression($lc_sql));

            //获取城市下的房源等id
            $id_data = $serial_number_helper->getAllCityHouse($user);

            //当月第一天 0时0分0秒的时间
            $current_month_first_day = strtotime(date('Y-m-01 H:i:s' , strtotime(date("Y-m-d"))));
            //当月最后一天的时间    date('Y-m-t H:i:s',time()) 获取当月最后一天的时间
            $current_month_last_day = strtotime(date('Y-m-t H:i:s' , time()));
            $serial_num_model = new \Common\Model\Erp\SerialNumber();
            if (!$id_data['house_id'] && !$id_data['room_focus_id'] && !$id_data['lc_contract_id'])
            {
                return array('final_money' => 0.00);
            }
            $sql = $serial_num_model->getSqlObject();
            $select = $sql->select(array('sn' => 'serial_number'));
            $select->columns(array(
                'final_money' => new Expression("sum(final_money)") ,
            ));
            $select->leftjoin(array('h' => 'house') , new \Zend\Db\Sql\Predicate\Expression('sn.house_id > 0 and h.house_id = sn.house_id and sn.house_type=1') , array('custom_number'));
            $select->leftjoin(array('rf' => 'room_focus') , new \Zend\Db\Sql\Predicate\Expression('sn.room_id > 0 and rf.room_focus_id = sn.room_id and sn.house_type=2') , array('rf_custom_number' => 'custom_number'));
            $where = new \Zend\Db\Sql\Where();
            $where->greaterThanOrEqualTo('sn.pay_time' , $current_month_first_day);
            $where->lessThanOrEqualTo('sn.pay_time' , $current_month_last_day);
            
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $tables = array();
                $houseJoin = function($table) use ($select,&$tables){
                    $tables[] = $table;
                    $join = new \Zend\Db\Sql\Predicate\Expression("h.house_id IS NOT NULL and `{$table}`.authenticatee_id=h.house_id");
                    return array($join,$select::JOIN_LEFT);
                };
                $permisions->VerifyDataCollectionsPermissionsModel($select,$houseJoin, $permisions::USER_AUTHENTICATOR, $user['user_id'],\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
                $flatJoin = function($table) use($select,&$tables){
                    $tables[] = $table;
                    $join = new \Zend\Db\Sql\Predicate\Expression("rf.flat_id IS NOT NULL and `{$table}`.authenticatee_id=rf.flat_id");
                    return array($join,$select::JOIN_LEFT,array('authenticatee_id2'=>'authenticatee_id'));
                };
                $permisions->VerifyDataCollectionsPermissionsModel($select,$flatJoin, $permisions::USER_AUTHENTICATOR, $user['user_id'],\Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
            
                $and = new \Zend\Db\Sql\Where();
                
                $or = new \Zend\Db\Sql\Where();
                $or->isNotNull("{$tables[0]}.authenticatee_id");
                $and->orPredicate($or);
                 
                $or = new \Zend\Db\Sql\Where();
                $or->isNotNull("{$tables[1]}.authenticatee_id");
                $and->orPredicate($or);
                 
                $or = new \Zend\Db\Sql\Where();
                $or->isNull("{$tables[0]}.authenticatee_id");
                $or->isNull("{$tables[1]}.authenticatee_id");
                $or->equalTo('sn.user_id', $user['user_id']);
                $or->equalTo('sn.house_id', 0);
                $or->equalTo('sn.room_id', 0);
                $and->orPredicate($or);
                 
                $where->andPredicate($and);
            }
            
            //$where->equalTo('sn.father_id' , 0);
            $link_where = new \Zend\Db\Sql\Where();
            //分散
            if ($id_data['house_id'])
            {
                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.house_id' , $dis_arr);
                $link_where->orPredicate($or_where);
            }
            //集中
            if ($id_data['room_focus_id'])
            {
                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.room_id' , $f_arr);
                $link_where->orPredicate($or_where);
            }
            //业主
            if ($id_data['lc_contract_id'])
            {
                $landlord_where = new \Zend\Db\Sql\Where();
                $landlord_where->equalTo('sn.source' , 'landlord_contract');
                $landlord_where->in('sn.source_id' , $lc_arr);
                $link_where->orPredicate($landlord_where);
            }
            $not_room = new \Zend\Db\Sql\Where();
            $not_room->equalTo('sn.house_type' , 0);
            $link_where->orPredicate($not_room);
            $where->addPredicate($link_where);
            $where->equalTo('sn.type' , 1);
            $where->equalTo('sn.is_delete' , 0);
            $where->equalTo('sn.company_id' , $company_id);
            $where->in('sn.status', array(0,1));
            $where->equalTo('sn.father_id', 0);
            $select->where($where);
            return $select->execute();
        }

        /**
         * 获取当月已支出流水
         * 修改时间 2015年4月29日10:28:33
         * 
         * @author ft
         * return array $data
         */
        public function getCurrentMonthExpend($company_id , $user)
        {
            $serial_model = new \Common\Model\Erp\SerialNumber();
            $serial_number_helper = new \Common\Helper\Erp\SerialNumber();
            //分散式sql
            $dis_sql = $serial_model->getDisAndFocusSql($user , 1);
            $dis_arr = array(new Expression($dis_sql));
            //集中式sql
            $f_sql = $serial_model->getDisAndFocusSql($user , 2);
            $f_arr = array(new Expression($f_sql));
            //业主sql
            $lc_sql = $serial_model->getDisAndFocusSql($user , 3);
            $lc_arr = array(new Expression($lc_sql));
            //获取城市下的房源等id
            $id_data = $serial_number_helper->getAllCityHouse($user);
            //当月第一天 0时0分0秒的时间
            $current_month_first_day = strtotime(date('Y-m-01 H:i:s' , strtotime(date("Y-m-d"))));
            //当月最后一天的时间    date('Y-m-t H:i:s',time()) 获取当月最后一天的时间
            $current_month_last_day = strtotime(date('Y-m-t H:i:s' , time()));
            if (!$id_data['house_id'] && !$id_data['room_focus_id'] && !$id_data['lc_contract_id'])
            {
                return array('total_final_money' => '');
            }
            $sql = $serial_model->getSqlObject();
            $select = $sql->select(array('sn' => 'serial_number'));
            $select->columns(array('total_final_money' => new Expression("sum(sn.money)")));
            $select->leftjoin(array('h' => 'house') , new \Zend\Db\Sql\Predicate\Expression('sn.house_id > 0 and h.house_id = sn.house_id and sn.house_type=1') , array('custom_number'));
            $select->leftjoin(array('rf' => 'room_focus') , new \Zend\Db\Sql\Predicate\Expression('sn.room_id > 0 and rf.room_focus_id = sn.room_id and sn.house_type=2') , array('rf_custom_number' => 'custom_number'));
            $where = new \Zend\Db\Sql\Where();
            /**
             * 权限
             */
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $tables = array();
                $houseJoin = function($table) use ($select,&$tables){
                    $tables[] = $table;
                    $join = new \Zend\Db\Sql\Predicate\Expression("h.house_id IS NOT NULL and `{$table}`.authenticatee_id=h.house_id");
                    return array($join,$select::JOIN_LEFT);
                };
                $permisions->VerifyDataCollectionsPermissionsModel($select,$houseJoin, $permisions::USER_AUTHENTICATOR, $user['user_id'],\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
                $flatJoin = function($table) use($select,&$tables){
                    $tables[] = $table;
                    $join = new \Zend\Db\Sql\Predicate\Expression("rf.flat_id IS NOT NULL and `{$table}`.authenticatee_id=rf.flat_id");
                    return array($join,$select::JOIN_LEFT,array('authenticatee_id2'=>'authenticatee_id'));
                };
                $permisions->VerifyDataCollectionsPermissionsModel($select,$flatJoin, $permisions::USER_AUTHENTICATOR, $user['user_id'],\Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
            
                $and = new \Zend\Db\Sql\Where();
                 
                $or = new \Zend\Db\Sql\Where();
                $or->isNotNull("{$tables[0]}.authenticatee_id");
                $and->orPredicate($or);
                 
                $or = new \Zend\Db\Sql\Where();
                $or->isNotNull("{$tables[1]}.authenticatee_id");
                $and->orPredicate($or);
                 
                $or = new \Zend\Db\Sql\Where();
                $or->isNull("{$tables[0]}.authenticatee_id");
                $or->isNull("{$tables[1]}.authenticatee_id");
                $or->equalTo('sn.user_id', $user['user_id']);
                $or->equalTo('sn.house_id', 0);
                $or->equalTo('sn.room_id', 0);
                $and->orPredicate($or);
                 
                $where->andPredicate($and);
            }
            
            $where->greaterThanOrEqualTo('sn.pay_time' , $current_month_first_day);
            $where->lessThanOrEqualTo('sn.pay_time' , $current_month_last_day);
            $link_where = new \Zend\Db\Sql\Where();
            //分散
            if ($id_data['house_id'])
            {
                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.house_id' , $dis_arr);
                $link_where->orPredicate($or_where);
            }
            //集中
            if ($id_data['room_focus_id'])
            {
                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.room_id' , $f_arr);
                $link_where->orPredicate($or_where);
            }
            //业主
            if ($id_data['lc_contract_id'])
            {
                $landlord_where = new \Zend\Db\Sql\Where();
                $landlord_where->equalTo('sn.source' , 'landlord_contract');
                $landlord_where->in('sn.source_id' , $lc_arr);
                $link_where->orPredicate($landlord_where);
            }
            $where->addPredicate($link_where);

            $where->equalTo('sn.father_id' , 0);
            $where->equalTo('sn.type' , 2);
            $where->equalTo('sn.is_delete' , 0);
            $where->equalTo('sn.status' , 0);
            $where->equalTo('sn.company_id' , $company_id);
            $select->where($where);
            return $select->execute();
        }

        /**
         * 欠费清单详细
         * 修改时间 2015年5月16日15:06:54
         * 
         * @author ft
         */
        public function detbtsDetail($data_continue)
        {
            $serial_num_model = new \Common\Model\Erp\SerialNumber();
            //获取用户名
            $ue_sql = $serial_num_model->getSqlObject();
            $ue_select = $ue_sql->select(array('ue' => 'user_extend'));
            $ue_select->columns(array('user_name' => 'name'));
            $ue_where = new \Zend\Db\Sql\Where();
            $ue_where->equalTo('ue.user_id' , $data_continue['user_id']);
            $ue_select->where($ue_where);
            $user_name = $ue_select->execute();

            //根据source来判断是否有租客可查询
            if (($data_continue['source'] == 'rental' || $data_continue['source'] == 'tenant_contract' || $data_continue['source'] == 'room') && $data_continue['house_type'] != 0)
            {

                $ren_model = new \Common\Model\Erp\Rental();
                $sql = $ren_model->getSqlObject();
                $ren_select = $sql->select(array('re' => 'rental'));
                $ren_select->columns(array('rental_id' => 'rental_id'));
                $ren_select->leftjoin(array('t' => 'tenant') , 're.tenant_id = t.tenant_id' , array('tenant_name' => 'name' , 'gender' , 'tenant_id' , 'from' , 'tenant_phone' => 'phone'));
                $ren_where = new \Zend\Db\Sql\Where();
                $ren_where->equalTo('re.house_type' , $data_continue['house_type']);
                //集中式
                if ($data_continue['room_focus_id'])
                {
                    $ren_where->equalTo('re.room_id' , $data_continue['room_focus_id']);
                }
                else
                {
                    $ren_where->equalTo('re.room_id' , $data_continue['room_id']);
                    $ren_where->equalTo('re.house_id' , $data_continue['house_id']);
                }
                $ren_where->equalTo('re.contract_id' , $data_continue['source_id']);
                $ren_select->where($ren_where);
                $ren_data = $ren_select->execute();
            }

            if ($data_continue['house_type'] == 1)
            {    //分散式
                $sql = $serial_num_model->getSqlObject();
                $select = $sql->select(array('sn' => 'serial_number'));
                if ($data_continue['room_id'] > 0)
                {//合租
                    $select->columns(
                            array(
                                'sn_id' => 'serial_id' ,
                                'serial_number' => 'serial_number' ,
                                'subscribe_pay_time' => 'subscribe_pay_time' ,
                                'room_id' => 'room_id' ,
                                'house_id' => 'house_id' ,
                                'receivable' => 'receivable' ,
                                'money' ,
                                'father_id' ,
                                'final_money' ,
                                'create_time' => 'create_time' ,
                                'pay_time' => 'pay_time' ,
                                'status' => 'status'
                    ));
                    $select->leftjoin(
                            array('h' => 'house') , 'h.house_id = sn.house_id' , array(
                        'h_name' => 'house_name' ,
                        'h_custom_num' => 'custom_number' ,
                        'h_cost' => 'cost' ,
                        'h_unit' => 'unit' ,
                        'h_number' => 'number' ,
                        'rental_way' => 'rental_way'
                    ));
                    $select->leftjoin(array('r' => 'room') , 'sn.room_id = r.room_id' , array('r_custom_num' => 'custom_number' , 'room_type' => 'room_type'));
                    $where = new \Zend\Db\Sql\Where();
                    //$where->equalTo('sn.is_delete' , 0);
                    $where->equalTo('sn.serial_id' , $data_continue['serial_id']);
                    $where->equalTo('sn.house_id' , $data_continue['house_id']);
                    $where->equalTo('sn.room_id' , $data_continue['room_id']);
                    $select->where($where);
                    $debts_datail = $select->execute();

                    $debts_datail[0]['room_type'] = ($debts_datail[0]['room_type'] == 'main' ? '主卧' : (($debts_datail[0]['room_type'] == 'second') ? '次卧' : (($debts_datail[0]['room_type'] == 'guest' ? '客卧' : ''))));
                }
                else
                {//整租
                    $select->columns(
                            array(
                                'sn_id' => 'serial_id' ,
                                'serial_number' => 'serial_number' ,
                                'subscribe_pay_time' => 'subscribe_pay_time' ,
                                'room_id' => 'room_id' ,
                                'house_id' => 'house_id' ,
                                'receivable' => 'receivable' ,
                                'money' ,
                                'father_id' ,
                                'final_money' ,
                                'create_time' => 'create_time' ,
                                'pay_time' => 'pay_time' ,
                                'status' => 'status'
                    ));
                    $select->leftjoin(
                            array('h' => 'house') , 'h.house_id = sn.house_id' , array(
                        'h_name' => 'house_name' ,
                        'h_custom_num' => 'custom_number' ,
                        'h_cost' => 'cost' ,
                        'h_unit' => 'unit' ,
                        'h_number' => 'number' ,
                        'rental_way' => 'rental_way'
                    ));
                    $where = new \Zend\Db\Sql\Where();
                    //$where->equalTo('sn.is_delete' , 0);
                    $where->equalTo('sn.serial_id' , $data_continue['serial_id']);
                    $where->equalTo('sn.house_id' , $data_continue['house_id']);
                    $select->where($where);
                    $debts_datail = $select->execute();
                }
                //将用户名合并到 $debts_datail
                //if (($data_continue['source'] == 'rental' || $data_continue['source'] == 'tenant_contract' || $data_continue['source'] == 'room') && $data_continue['source_id'] != 0) {
                    if (isset($ren_data) && ($data_continue['source'] == 'rental' || $data_continue['source'] == 'tenant_contract' || $data_continue['source'] == 'room'))
                    {
                        return array_merge($debts_datail[0] , $user_name[0] , $ren_data[0]);
                    }
                    else
                    {
                        return array_merge($debts_datail[0] , $user_name[0]);
                    }
                //} else {
                //    return array_merge($debts_datail[0] , $user_name[0]);
                //}
            }
            elseif ($data_continue['house_type'] == 2)
            {//集中式
                $focus_sql = $serial_num_model->getSqlObject();
                $focus_select = $focus_sql->select(array('sn' => 'serial_number'));
                $focus_select->columns(
                        array(
                            'sn_id' => 'serial_id' ,
                            'serial_number' => 'serial_number' ,
                            'subscribe_pay_time' => 'subscribe_pay_time' ,
                            'room_id' => 'room_id' ,
                            'house_id' => 'house_id' ,
                            'receivable' => 'receivable' ,
                            'money' ,
                            'father_id' ,
                            'final_money' ,
                            'create_time' => 'create_time' ,
                            'pay_time' => 'pay_time' ,
                            'status' => 'status'
                ));
                $focus_select->leftjoin(
                        array('rf' => 'room_focus') , 'sn.room_id = rf.room_focus_id' , array(
                    'floor' => 'floor' ,
                    'rf_custom_num' => 'custom_number' ,
                    'rf_money' => 'money'
                ));
                $focus_select->leftjoin(
                        array('f' => 'flat') , 'rf.flat_id = f.flat_id' , array(
                    'flat_name' => 'flat_name' ,
                    'f_custom_num' => 'custom_number' ,
                    'address' => 'address' ,
                ));
                $focus_where = new \Zend\Db\Sql\Where();
                $focus_where->equalTo('sn.is_delete' , 0);
                $focus_where->equalTo('sn.serial_id' , $data_continue['serial_id']);
                $focus_where->equalTo('sn.room_id' , $data_continue['room_focus_id']);
                $focus_select->where($focus_where);
                $focus_info = $focus_select->execute();
                //将用户名合并到 $debts_datail
                if ($ren_data)
                {
                    return array_merge($focus_info[0] , $user_name[0] , $ren_data[0]);
                }
                return array_merge($focus_info[0] , $user_name[0]);
            }
            else
            {//非房间流水
                $sql = $serial_num_model->getSqlObject();
                $select = $sql->select(array('sn' => 'serial_number'));
                $select->columns(
                        array(
                            'sn_id' => 'serial_id' ,
                            'serial_number' => 'serial_number' ,
                            'subscribe_pay_time' => 'subscribe_pay_time' ,
                            'room_id' => 'room_id' ,
                            'house_id' => 'house_id' ,
                            'receivable' => 'receivable' ,
                            'money' ,
                            'father_id' ,
                            'final_money' ,
                            'create_time' => 'create_time' ,
                            'pay_time' => 'pay_time' ,
                            'status' => 'status'
                ));
                $where = new \Zend\Db\Sql\Where();
                $where->equalTo('sn.is_delete' , 0);
                $where->equalTo('sn.serial_id' , $data_continue['serial_id']);
                $select->where($where);
                $debts_datail = $select->execute();

                return array_merge($debts_datail[0] , $user_name[0]);
            }
        }

        /**
         * 根据时间范围 搜索流水
         * 修改时间 2015年4月29日12:20:58
         * 
         * @author ft
         */
        public function accordingDateSearchSerial($data , $page , $size , $cid , $user , $house_id = array() , $room_focus_id = array())
        {
            $user_model = new \Common\Helper\Erp\User ();
            $user_info = $user_model->getCurrentUser();
            $pattern = $user_info['company']['pattern'];
            $lease_mode = ($pattern == 01) ? array(0 , 1) : (($pattern == 10) ? array(0 , 2) : array(0 , 1 , 2));
            //明天集中式 和 分散式 区分处理
            $serial_model = new \Common\Model\Erp\SerialNumber();
            $sql = $serial_model->getSqlObject();
            $select = $sql->select(array('sn' => 'serial_number'));
            $select->columns(
                    array(
                        'sn_id' => 'serial_id' ,
                        'serial_number' => 'serial_number' ,
                        'serial_name' => 'serial_name' ,
                        'pay_time' => 'pay_time' ,
                        'room_id' => 'room_id' ,
                        'house_id' => 'house_id' ,
                        'sn_money' => 'money' ,
                        'type' => 'type' ,
                        'house_type' => 'house_type' ,
                        'source' => 'source' ,
                        'source_id' => 'source_id' ,
                        'status' => 'status'
            ));
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('sn.company_id' , $cid);
            $where->equalTo('sn.is_delete' , 0);
            $link_where = new \Zend\Db\Sql\Where();
            if (isset($house_id) && $pattern == 01 || $pattern == 11)
            {
                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.house_id' , $house_id);
                $link_where->orPredicate($or_where);
            }
            if ($room_focus_id && $pattern == 10 || $pattern == 11)
            {
                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.room_id' , $room_focus_id);
                $link_where->orPredicate($or_where);
            }
            $or_where = new \Zend\Db\Sql\Where();
            $or_where->in('sn.house_type' , $lease_mode);
            $link_where->orPredicate($or_where);
            $where->addPredicate($link_where);
            if (isset($data['deal_type']) && $data['deal_type'] == 0)
            {//财务类别类型
                $select->join(array('sd' => 'serial_detail') , 'sd.serial_id=sn.serial_id' , array('serial_id' , 'serial_detail_id'));
                $select->leftjoin(array('ssb' => 'serial_strike_balance') , 'sd.serial_detail_id = ssb.serial_detail_id' , array('ssb_money' => 'money'));
                $select->group('sn.serial_id');
            }
            if (isset($data['start_date']) && $data['start_date'])
            {//开始时间
                $where->greaterThanOrEqualTo('sn.pay_time' , $data['start_date']);
            }
            if (isset($data['end_date']) && $data['end_date'])
            {//结束时间
                if ($data['start_date'] == $data['end_date'])
                {
                    $where->lessThanOrEqualTo('sn.pay_time' , time());
                }
                else
                {
                    $where->lessThanOrEqualTo('sn.pay_time' , $data['end_date']);
                }
            }
            if (isset($data['house_type']) && $data['house_type'])
            {//房源类型
                $where->equalTo('sn.house_type' , $data['house_type']);
            }
            if (isset($data['finance_type']) && $data['finance_type'])
            {//财务收支类型
                $where->equalTo('sn.type' , $data['finance_type']);
            }
            if (isset($data['deal_type']) && $data['deal_type'])
            {//财务类别类型
                $select->join(array('sd' => 'serial_detail') , 'sd.serial_id=sn.serial_id' , array('serial_id' , 'serial_detail_id'));
                $select->leftjoin(array('ssb' => 'serial_strike_balance') , 'sd.serial_detail_id = ssb.serial_detail_id' , array('ssb_money' => 'money'));
                $where->equalTo('sd.fee_type_id' , $data['deal_type']);
                $select->group('sn.serial_id');
            }
            if (isset($data['search']) && $data['search'])
            {//有搜索关键词
                $select->leftjoin(array('h' => 'house') , new \Zend\Db\Sql\Predicate\Expression('h.house_id = sn.house_id and sn.house_type=1') , array('house_name' , 'custom_number'));
                $select->leftjoin(array('r' => 'room') , new \Zend\Db\Sql\Predicate\Expression('r.room_id = sn.room_id and sn.house_type=1 and sn.room_id>0') , array('r_custom_number' => 'custom_number'));
                $select->leftjoin(array('rf' => 'room_focus') , new \Zend\Db\Sql\Predicate\Expression('rf.room_focus_id = sn.room_id and sn.house_type=2') , array('rf_custom_number' => 'custom_number'));
                $select->leftjoin(array('f' => 'flat') , new \Zend\Db\Sql\Predicate\Expression('f.flat_id = rf.flat_id') , array('flat_name' , 'f_custom_number' => 'custom_number'));
                $searchWhere = new \Zend\Db\Sql\Where();
                $search = "%{$data['search']}%";
                $linkWhere = new \Zend\Db\Sql\Where();
                $linkWhere->like('h.house_name' , $search);
                $searchWhere->orPredicate($linkWhere);
                $linkWhere = new \Zend\Db\Sql\Where();
                $linkWhere->like('f.flat_name' , $search);
                $searchWhere->orPredicate($linkWhere);
                $linkWhere = new \Zend\Db\Sql\Where();
                $linkWhere->like('h.custom_number' , $search);
                $searchWhere->orPredicate($linkWhere);
                $linkWhere = new \Zend\Db\Sql\Where();
                $linkWhere->like('f.custom_number' , $search);
                $searchWhere->orPredicate($linkWhere);
                $where->addPredicate($searchWhere);
            }
            elseif (isset($data['room_id']))
            {

                // $select->leftjoin(array('r' => 'room') , new \Zend\Db\Sql\Predicate\Expression('r.room_id = sn.room_id and sn.house_type=1') , array('r_custom_number' => 'custom_number'));
                $where->equalTo('sn.house_type' , '1');
                $where->equalTo('sn.room_id' , $data['room_id']);
            }
            elseif (isset($data['focus_id']))
            {
                $focus_id = $data['focus_id'];
                $where->equalTo('sn.house_type' , '2');
                $where->equalTo('sn.room_id' , $focus_id);
            }

            if (isset($data['time']))
            {
                $create_time = strtotime($data['time']);
                $where->greaterThanOrEqualTo('sn.create_time' , $create_time);
                $where->lessThanOrEqualTo('sn.create_time' , strtotime(date('Y-m-t' , $create_time)));
            }
            /**
             * 权限
             */
//             if($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER){
//             	$permissions = \Common\Helper\Permissions::Factory('sys_housing_management');
//             	$permisionsTable = $permissions->VerifyDataCollectionsPermissions($permissions::USER_AUTHENTICATOR, $user['user_id'],0);
//             	$join = new \Zend\Db\Sql\Predicate\Expression('(sn.house_id=pa.authenticatee_id and sn.house_id>0 and pa.source=1) or (sn.room_id=pa.authenticatee_id and sn.room_id>0  and pa.source=2)');
//             	$select->join(array('pa'=>new \Zend\Db\Sql\Predicate\Expression($permisionsTable)),$join,'authenticatee_id',$select::JOIN_LEFT);
//             	$authWhere = new \Zend\Db\Sql\Where();
//             	$authWhere2 = clone $authWhere;
//             	$authWhere->isNotNull('pa.authenticatee_id');
//             	$authWhere2->isNull('pa.authenticatee_id');
//             	$authWhere2->equalTo('sn.user_id', $user['user_id']);
//             	$authWhere->orPredicate($authWhere2);
//             	$where->addPredicate($authWhere);
//             }
            $select->where($where);
            $select->order('sn.serial_id DESC');
            $data = \Core\Db\Sql\Select::pageSelect($select , null , $page , $size);
            if ($data['data'] && isset($data['data'][0]['house_name']))
            {//有搜索关键词
                foreach ($data['data'] as $key => $value)
                {
                    if ($value['house_type'] == 1)
                    {
                        $value['custom_number'] = $value['r_custom_number'] ? $value['custom_number'] . '-' . $value['r_custom_number'] : $value['custom_number'];
                        $value['house_name'] = $value['r_custom_number'] ? $value['house_name'] . '-' . $value['r_custom_number'] : $value['house_name'];
                    }
                    else if ($value['house_type'] == 2)
                    {
                        $value['custom_number'] = $value['f_custom_number'] . '-' . $value['rf_custom_number'];
                        $value['house_name'] = $value['flat_name'] . '-' . $value['rf_custom_number'];
                    }
                    else
                    {
                        $value['house_name'] = '非房间流水';
                        $value['custom_number'] = $value['serial_number'];
                    }
                    unset($value['r_custom_number'] , $value['rf_custom_number'] , $value['room_focus_id'] , $value['flat_name']);
                    $data['data'][$key] = $value;
                }
            }
            elseif ($data['data'])
            {//没有搜索关键词
                $house_id = $room_id = $room_focus_id = $source_id = array();
                foreach ($data['data'] as $value)
                {
                    if ($value['house_type'] == 1)
                    {
                        $house_id[] = $value['house_id'];
                        if ($value['room_id'])
                        {
                            $room_id[] = $value['room_id'];
                        }
                    }
                    else if ($value['house_type'] == 2)
                    {
                        $room_focus_id[] = $value['room_id'];
                    }
                    if ($value['source'] == 'landlord_contract')
                    {//业主合同
                        $source_id[] = $value['source_id'];
                    }
                }
                $houseData = $roomData = $focusData = array();
                if ($house_id)
                {
                    $model = new \Common\Model\Erp\House();
                    $houseData = $model->getData(array('house_id' => array_unique($house_id)));
                    $temp = array();
                    foreach ($houseData as $value)
                    {
                        $temp[$value['house_id']] = $value;
                    }
                    $houseData = $temp;
                    unset($temp);
                }
                if ($room_id)
                {
                    $model = new \Common\Model\Erp\Room();
                    $roomData = $model->getData(array('room_id' => array_unique($room_id)));
                    $temp = array();
                    foreach ($roomData as $value)
                    {
                        $temp[$value['room_id']] = $value;
                    }
                    $roomData = $temp;
                    unset($temp);
                }
                if ($room_focus_id)
                {
                    $model = new \Common\Model\Erp\RoomFocus();
                    $sql = $serial_model->getSqlObject();
                    $select = $sql->select(array('rf' => 'room_focus'));
                    $select->join(array('f' => 'flat') , 'rf.flat_id=f.flat_id' , array('flat_name'));
                    $select->where(array('room_focus_id' => array_unique($room_focus_id)));
                    $focusData = $select->execute();
                    $temp = array();
                    foreach ($focusData as $value)
                    {
                        $temp[$value['room_focus_id']] = $value;
                    }
                    $focusData = $temp;
                    unset($temp);
                }
                if ($source_id)
                {
                    $landlord_con_model = new \Common\Model\Erp\LandlordContract();
                    $lc_sql = $landlord_con_model->getSqlObject();
                    $lc_select = $lc_sql->select(array('lc' => 'landlord_contract'));
                    $lc_select->columns(array('contract_id' => 'contract_id' , 'hosue_name' => 'hosue_name'));
                    $lc_where = new \Zend\Db\Sql\Where();
                    $lc_where->in('contract_id' , array_unique($source_id));
                    $lc_select->where($lc_where);
                    $lc_con_data = $lc_select->execute();
                    foreach ($lc_con_data as $value)
                    {
                        $temp[$value['contract_id']] = $value;
                    }
                    $lc_con_data = $temp;
                    unset($temp);
                }
                foreach ($data['data'] as $key => $value)
                {
                    if ($value['house_type'] == 1)
                    {
                        $value['custom_number'] = isset($houseData[$value['house_id']]) ? $houseData[$value['house_id']]['custom_number'] : '';
                        $value['house_name'] = isset($houseData[$value['house_id']]) ? $houseData[$value['house_id']]['house_name'] : '';
                        if ($value['room_id'])
                        {
                            $value['custom_number'] .= '-' . (isset($roomData[$value['room_id']]) ? $roomData[$value['room_id']]['custom_number'] : '');
                            $value['house_name'] .= '-' . (isset($roomData[$value['room_id']]) ? $roomData[$value['room_id']]['custom_number'] : '');
                        }
                        if ($value['source'] == 'landlord_contract')
                        {//业主合同
                            $value['house_name'] = isset($lc_con_data[$value['source_id']]) ? $lc_con_data[$value['source_id']]['hosue_name'] : '';
                        }
                    }
                    else if ($value['house_type'] == 2)
                    {
                        $value['custom_number'] = isset($focusData[$value['room_id']]) ? $focusData[$value['room_id']]['custom_number'] : '';
                        $value['house_name'] = isset($focusData[$value['room_id']]) ? $focusData[$value['room_id']]['flat_name'] . '-' . $focusData[$value['room_id']]['floor'] : '';
                        if ($value['source'] == 'landlord_contract')
                        {//业主合同
                            $value['house_name'] = isset($lc_con_data[$value['source_id']]) ? $lc_con_data[$value['source_id']]['hosue_name'] : '';
                        }
                    }
                    else
                    {
                        $value['house_name'] = '非房间流水';
                        $value['custom_number'] = $value['serial_number'];
                    }
                    $data['data'][$key] = $value;
                }
            }
            return $data;
        }

        /**
         * 根据时间范围 搜索流水web端
         * 修改时间 2015年4月29日12:20:58
         * 
         * @author ft
         */
        public function accordingDateSearchSerialPc($data , $page , $size , $cid , $user)
        {
            $city_model = new \Common\Model\Erp\City();
            $user_model = new \Common\Helper\Erp\User();
            $company_model = new \Common\Model\Erp\Company();
            $serial_model = new \Common\Model\Erp\SerialNumber();
            $serial_number_helper = new \Common\Helper\Erp\SerialNumber();

            //$user_info = $user_model->getCurrentUser();
            $c_where = array('company_id' => $cid);
            $c_column = array('pattern' => 'pattern' , 'city_id' => 'city_id');
            $c_info = $company_model->getOne($c_where , $c_column);

            $city_id = $user['city_id'];
            $city_id = is_numeric($city_id) ? $city_id : $c_info['city_id'];

            //获取城市下的房源id
            //$house_arr = $serial_number_helper->getHouseIdByCityId($city_id , $cid);
            //$house_id = array_column($house_arr , 'house_id');
            //获取城市下的集中式房间
            //$room_focus_arr = $serial_number_helper->getRoomFocusIdByCityId($city_id , $cid);
            //$room_focus_id = array_column($room_focus_arr , 'rf_room_id');
            //获取该城市下的业主
            //$lc_arr = $serial_number_helper->getlandlordContractByCityId($city_id , $cid);
            //$lc_contract_id = array_column($lc_arr , 'contract_id');

            //分散式sql
            //$dis_sql = $serial_model->getDisAndFocusSql($user , 1);
            //$dis_arr = array(new Expression($dis_sql));
            //集中式sql
            //$f_sql = $serial_model->getDisAndFocusSql($user , 2);
            //$f_arr = array(new Expression($f_sql));
            //业主合同sql
            //$lc_sql = $serial_model->getDisAndFocusSql($user , 3);
            //$lc_arr = array(new Expression($lc_sql));

            $pattern = $c_info['pattern'];
            $lease_mode = ($pattern == 01) ? array(0 , 1) : (($pattern == 10) ? array(0 , 2) : array(0 , 1 , 2));
            if (($pattern ? $pattern : $pattern == 00))
            {
                $sql = $serial_model->getSqlObject();
                $select = $sql->select(array('sn' => 'serial_number'));
                $select->columns(
                        array(
                            'sn_id' => 'serial_id' ,
                            'serial_number' => 'serial_number' ,
                            'serial_name' => 'serial_name' ,
                            'pay_time' => 'pay_time' ,
                            'room_id' => 'room_id' ,
                            'house_id' => 'house_id' ,
                            'sn_money' => 'money' ,
                            'type' => 'type' ,
                            'house_type' => 'house_type' ,
                            'source' => 'source' ,
                            'source_id' => 'source_id' ,
                            'status' => 'status',
                            'serial_id' => 'serial_id',
                ));
                $where = new \Zend\Db\Sql\Where();
                $where->equalTo('sn.company_id' , $cid);
                $where->equalTo('sn.is_delete' , 0);

                if ($pattern == 01) {
                    $where->in('sn.house_type' , array(0 , 1));
                } elseif ($pattern == 10) {
                    $where->in('sn.house_type' , array(0 , 2));
                } else {
                    $where->in('sn.house_type' , array(0 , 1, 2));
                }
            }
            if (isset($data['deal_type']) && $data['deal_type'] != 0)
            {//财务类别类型
                $select->join(array('sd' => 'serial_detail') , 'sd.serial_id=sn.serial_id' , array('serial_id' , 'serial_detail_id'));
                //$select->leftjoin(array('ssb' => 'serial_strike_balance') , 'sd.serial_detail_id = ssb.serial_detail_id' , array('ssb_money' => new Expression('IF(ssb.money,SUM(ssb.money),0)')));
                $where->equalTo('sd.fee_type_id', $data['deal_type']);
                $select->group('sn.serial_id');
            }
            if (isset($data['start_date']) && $data['start_date'])
            {//开始时间
                $where->greaterThanOrEqualTo('sn.pay_time' , $data['start_date']);
            }
            if (isset($data['end_date']) && $data['end_date'])
            {//结束时间
                if ($data['start_date'] == $data['end_date'])
                {
                    $where->lessThanOrEqualTo('sn.pay_time' , time());
                }
                else
                {
                    $where->lessThanOrEqualTo('sn.pay_time' , $data['end_date']);
                }
            }
            if (isset($data['house_type']) && $data['house_type'])
            {//房源类型
                $where->equalTo('sn.house_type' , $data['house_type']);
            }
            if (isset($data['finance_type']) && $data['finance_type'])
            {//财务收支类型
                $where->equalTo('sn.type' , $data['finance_type']);
            }
            //$select->leftjoin(array('h' => 'house') , new \Zend\Db\Sql\Predicate\Expression('sn.house_id > 0 and h.house_id = sn.house_id and sn.house_type=1') , array('house_name' , 'custom_number'));
            //$select->leftjoin(array('rf' => 'room_focus') , new \Zend\Db\Sql\Predicate\Expression('sn.room_id > 0 and rf.room_focus_id = sn.room_id and sn.house_type=2') , array('rf_custom_number' => 'custom_number'));
            $select->leftjoin(array('h' => 'house') , new \Zend\Db\Sql\Predicate\Expression('sn.house_id > 0 and h.house_id = sn.house_id and sn.house_type=1') , array('custom_number'));
            $select->leftjoin(array('rf' => 'room_focus') , new \Zend\Db\Sql\Predicate\Expression('sn.room_id > 0 and rf.room_focus_id = sn.room_id and sn.house_type=2') , array('rf_custom_number' => 'custom_number'));
            if (isset($data['search']) && $data['search'])
            {//有搜索关键词
                $select->leftjoin(array('r' => 'room') , new \Zend\Db\Sql\Predicate\Expression('r.room_id = sn.room_id and sn.house_type=1 and sn.room_id>0') , array('r_custom_number' => 'custom_number'));
                $select->leftjoin(array('f' => 'flat') , new \Zend\Db\Sql\Predicate\Expression('f.flat_id = rf.flat_id') , array('flat_name' , 'f_custom_number' => 'custom_number'));
                $searchWhere = new \Zend\Db\Sql\Where();
                $search = "%{$data['search']}%";
                $linkWhere = new \Zend\Db\Sql\Where();
                $linkWhere->like('h.house_name' , $search);
                $searchWhere->orPredicate($linkWhere);
                $linkWhere = new \Zend\Db\Sql\Where();
                $linkWhere->like('f.flat_name' , $search);
                $searchWhere->orPredicate($linkWhere);
                $linkWhere = new \Zend\Db\Sql\Where();
                $linkWhere->like('h.custom_number' , $search);
                $searchWhere->orPredicate($linkWhere);
                $linkWhere = new \Zend\Db\Sql\Where();
                $linkWhere->like('f.custom_number' , $search);
                $searchWhere->orPredicate($linkWhere);
                $where->addPredicate($searchWhere);
            }
            elseif (isset($data['room_id']))
            {

                // $select->leftjoin(array('r' => 'room') , new \Zend\Db\Sql\Predicate\Expression('r.room_id = sn.room_id and sn.house_type=1') , array('r_custom_number' => 'custom_number'));
                $where->equalTo('sn.house_type' , '1');
                $where->equalTo('sn.room_id' , $data['room_id']);
            }
            elseif (isset($data['focus_id']))
            {
                $focus_id = $data['focus_id'];
                $where->equalTo('sn.house_type' , '2');
                $where->equalTo('sn.room_id' , $focus_id);
            }

            if (isset($data['time']))
            {


                //年筛选
                if (strlen($data['time']) == 4)
                {
                    $create_time = strtotime($data['time'] . '0101');
                    $where->greaterThanOrEqualTo('sn.pay_time' , $create_time);
                    $where->lessThan('sn.pay_time' , strtotime("+1 year" , $create_time));
                }
                else
                {
                    $create_time = strtotime($data['time']);
                    $where->greaterThanOrEqualTo('sn.pay_time' , $create_time);
                    $where->lessThan('sn.pay_time' , strtotime("+1 month" , $create_time));
                }
            }
            /**
             * 权限
             */
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $tables = array();
                $houseJoin = function($table) use ($select,&$tables){
                	$tables[] = $table;
                	$join = new \Zend\Db\Sql\Predicate\Expression("h.house_id IS NOT NULL and `{$table}`.authenticatee_id=h.house_id");
                	return array($join,$select::JOIN_LEFT);
                };
                $permisions->VerifyDataCollectionsPermissionsModel($select,$houseJoin, $permisions::USER_AUTHENTICATOR, $user['user_id'],\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
                $flatJoin = function($table) use($select,&$tables){
                	$tables[] = $table;
                	$join = new \Zend\Db\Sql\Predicate\Expression("rf.flat_id IS NOT NULL and `{$table}`.authenticatee_id=rf.flat_id");
                	return array($join,$select::JOIN_LEFT,array('authenticatee_id2'=>'authenticatee_id'));
                };
                $permisions->VerifyDataCollectionsPermissionsModel($select,$flatJoin, $permisions::USER_AUTHENTICATOR, $user['user_id'],\Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
                
                $and = new \Zend\Db\Sql\Where();
                 
                $or = new \Zend\Db\Sql\Where();
                $or->isNotNull("{$tables[0]}.authenticatee_id");
                $and->orPredicate($or);
                 
                $or = new \Zend\Db\Sql\Where();
                $or->isNotNull("{$tables[1]}.authenticatee_id");
                $and->orPredicate($or);
                 
                $or = new \Zend\Db\Sql\Where();
                $or->isNull("{$tables[0]}.authenticatee_id");
                $or->isNull("{$tables[1]}.authenticatee_id");
                $or->equalTo('sn.user_id', $user['user_id']);
                $or->equalTo('sn.house_id', 0);
                $or->equalTo('sn.room_id', 0);
                $and->orPredicate($or);
                 
                $where->andPredicate($and);
            }
            $where->notEqualTo('sn.final_money', 0);
            $where->equalTo('sn.city_id', $user['city_id']);
            $select->where($where);
            $select->order('sn.serial_id DESC');
            $count = $select->count();
            $data = \Core\Db\Sql\Select::pageSelect($select , $count , $page , $size);
            if ($data['data'] && isset($data['data'][0]['house_name']))
            {//有搜索关键词
                foreach ($data['data'] as $key => $value)
                {
                    if ($value['house_type'] == 1)
                    {
                        $value['custom_number'] = $value['r_custom_number'] ? $value['custom_number'] . '-' . $value['r_custom_number'] : $value['custom_number'];
                        $value['house_name'] = $value['r_custom_number'] ? $value['house_name'] . '-' . $value['r_custom_number'] : $value['house_name'];
                    }
                    else if ($value['house_type'] == 2)
                    {
                        $value['custom_number'] = $value['f_custom_number'] . '-' . $value['rf_custom_number'];
                        $value['house_name'] = $value['flat_name'] . '-' . $value['rf_custom_number'];
                    }
                    else
                    {
                        $value['house_name'] = '非房间流水';
                        $value['custom_number'] = $value['serial_number'];
                    }
                    unset($value['r_custom_number'] , $value['rf_custom_number'] , $value['room_focus_id'] , $value['flat_name']);
                    $data['data'][$key] = $value;
                }
            }
            elseif ($data['data'])
            {//没有搜索关键词
                $house_id = $room_id = $room_focus_id = $source_id = array();
                foreach ($data['data'] as $value)
                {
                    $serial_id_arr[] = $value['serial_id'];
                    if ($value['house_type'] == 1)
                    {
                        $house_id[] = $value['house_id'];
                        if ($value['room_id'])
                        {
                            $room_id[] = $value['room_id'];
                        }
                    }
                    else if ($value['house_type'] == 2)
                    {
                        $room_focus_id[] = $value['room_id'];
                    }
                    if ($value['source'] == 'landlord_contract')
                    {//业主合同
                        $source_id[] = $value['source_id'];
                    }
                }
                //显示冲账信息
                if (isset($serial_id_arr))
                {
                    $serial_strike_model = new \Common\Model\Erp\SerialStrikeBalance();
                    $serial_strike_data = $serial_strike_model->getAllStrikeData($serial_id_arr , 1);
                    foreach ($serial_strike_data as $key => $val)
                    {
                        $new_serial_strike[$val['serial_id']]['serial_id'] = $val['serial_id'];
                        $new_serial_strike[$val['serial_id']]['ssb_money'] += $val['ssb_money'];
                    }
                    unset($serial_strike_data);
                    foreach ($data['data'] as $key => $val)
                    {
                        if (isset($new_serial_strike[$val['serial_id']]))
                        {
                            $data['data'][$key]['ssb_money'] = $new_serial_strike[$val['serial_id']]['ssb_money'];
                        }
                        else
                        {
                            $data['data'][$key]['ssb_money'] = 0;
                        }
                    }
                }

                $houseData = $roomData = $focusData = array();
                if ($house_id)
                {
                    $model = new \Common\Model\Erp\House();
                    $houseData = $model->getData(array('house_id' => array_unique($house_id)));
                    $temp = array();
                    foreach ($houseData as $value)
                    {
                        $temp[$value['house_id']] = $value;
                    }
                    $houseData = $temp;
                    unset($temp);
                }
                if ($room_id)
                {
                    $model = new \Common\Model\Erp\Room();
                    $roomData = $model->getData(array('room_id' => array_unique($room_id)));
                    $temp = array();
                    foreach ($roomData as $value)
                    {
                        $temp[$value['room_id']] = $value;
                    }
                    $roomData = $temp;
                    unset($temp);
                }
                if ($room_focus_id)
                {
                    $model = new \Common\Model\Erp\RoomFocus();
                    $sql = $serial_model->getSqlObject();
                    $select = $sql->select(array('rf' => 'room_focus'));
                    $select->columns(array('room_focus_id' => 'room_focus_id' , 'custom_number' => 'custom_number' , 'floor' => 'floor'));
                    $select->join(array('f' => 'flat') , 'rf.flat_id=f.flat_id' , array('flat_name'));
                    $select->where(array('room_focus_id' => array_unique($room_focus_id)));
                    $focusData = $select->execute();
                    $temp = array();
                    foreach ($focusData as $value)
                    {
                        $temp[$value['room_focus_id']] = $value;
                    }
                    $focusData = $temp;
                    unset($temp);
                }
                if ($source_id)
                {
                    $landlord_con_model = new \Common\Model\Erp\LandlordContract();
                    $lc_sql = $landlord_con_model->getSqlObject();
                    $lc_select = $lc_sql->select(array('lc' => 'landlord_contract'));
                    $lc_select->columns(array('contract_id' => 'contract_id' , 'hosue_name' => 'hosue_name'));
                    $lc_where = new \Zend\Db\Sql\Where();
                    $lc_where->in('contract_id' , array_unique($source_id));
                    $lc_select->where($lc_where);
                    $lc_con_data = $lc_select->execute();
                    foreach ($lc_con_data as $value)
                    {
                        $temp[$value['contract_id']] = $value;
                    }
                    $lc_con_data = $temp;
                    unset($temp);
                }
                foreach ($data['data'] as $key => $value)
                {
                    if ($value['house_type'] == 1)
                    {
                        $value['custom_number'] = isset($houseData[$value['house_id']]) ? $houseData[$value['house_id']]['custom_number'] : '';
                        $value['house_name'] = isset($houseData[$value['house_id']]) ? $houseData[$value['house_id']]['house_name'] : '';
                        //$value['rental_way'] = isset($houseData[$value['house_id']]) ? $houseData[$value['house_id']]['rental_way'] : '';
                        if ($value['room_id'])
                        {
                            $room_type = isset($roomData[$value['room_id']]) ? $roomData[$value['room_id']]['room_type'] : '';
                            $room_type = ($room_type = 'main') ? '主卧' : (($room_type = 'second') ? '次卧' : (($room_type = 'guest' ? '客卧' : '')));
                            //$value['custom_number'] .= '-' . (isset($roomData[$value['room_id']]) ? $roomData[$value['room_id']]['custom_number'] : '');
                            $value['house_name'] .= $room_type . (isset($roomData[$value['room_id']]) ? $roomData[$value['room_id']]['custom_number'] . '号' : '');
                            //$value['rental_way'] = isset($houseData[$value['house_id']]) ? $houseData[$value['house_id']]['rental_way'] : '';
                        }
                        if ($value['source'] == 'landlord_contract')
                        {//业主合同
                            $value['house_name'] = isset($lc_con_data[$value['source_id']]) ? $lc_con_data[$value['source_id']]['hosue_name'] : '';
                        }
                    }
                    else if ($value['house_type'] == 2)
                    {
                        //$value['custom_number'] = isset($focusData[$value['room_id']]) ? $focusData[$value['room_id']]['custom_number'] : '';
                        $value['house_name'] = isset($focusData[$value['room_id']]) ? $focusData[$value['room_id']]['flat_name'] . $focusData[$value['room_id']]['floor'] . '楼' . $focusData[$value['room_id']]['custom_number'] . '号' : '';
                        if ($value['source'] == 'landlord_contract')
                        {//业主合同
                            $value['house_name'] = isset($lc_con_data[$value['source_id']]) ? $lc_con_data[$value['source_id']]['hosue_name'] : '';
                        }
                    }
                    else
                    {
                        $value['house_name'] = '非房间流水';
                        $value['custom_number'] = $value['serial_number'];
                    }
                    $data['data'][$key] = $value;
                }
            }
            return $data;
        }

        /**
         * 获取所有的流水, 并根据条件
         * 修改时间  2015年9月1日16:12:19
         * 
         * @author ft
         * 
         */
        public function getAllSerialByCondition($data , $page , $size , $cid , $user)
        {
            $city_model = new \Common\Model\Erp\City();
            $user_model = new \Common\Helper\Erp\User();
            $company_model = new \Common\Model\Erp\Company();
            $serial_model = new \Common\Model\Erp\SerialNumber();
            $serial_number_helper = new \Common\Helper\Erp\SerialNumber();
        
            //$user_info = $user_model->getCurrentUser();
            $c_where = array('company_id' => $cid);
            $c_column = array('pattern' => 'pattern' , 'city_id' => 'city_id');
            $c_info = $company_model->getOne($c_where , $c_column);
        
            $city_id = $user['city_id'];
            $city_id = is_numeric($city_id) ? $city_id : $c_info['city_id'];
        
            //获取城市下的房源id
            $house_arr = $serial_number_helper->getHouseIdByCityId($city_id , $cid);
            $house_id = array_column($house_arr , 'house_id');
            //获取城市下的集中式房间
            $room_focus_arr = $serial_number_helper->getRoomFocusIdByCityId($city_id , $cid);
            $room_focus_id = array_column($room_focus_arr , 'rf_room_id');
            //获取该城市下的业主
            $lc_arr = $serial_number_helper->getlandlordContractByCityId($city_id , $cid);
            $lc_contract_id = array_column($lc_arr , 'contract_id');
        
            //分散式sql
            $dis_sql = $serial_model->getDisAndFocusSql($user , 1);
            $dis_arr = array(new Expression($dis_sql));
            //集中式sql
            $f_sql = $serial_model->getDisAndFocusSql($user , 2);
            $f_arr = array(new Expression($f_sql));
            //业主合同sql
            $lc_sql = $serial_model->getDisAndFocusSql($user , 3);
            $lc_arr = array(new Expression($lc_sql));
        
            $pattern = $c_info['pattern'];
            $lease_mode = ($pattern == 01) ? array(0 , 1) : (($pattern == 10) ? array(0 , 2) : array(0 , 1 , 2));
            if ($house_id && !$room_focus_id && ($pattern ? $pattern : $pattern == 00))
            {
                $sql = $serial_model->getSqlObject();
                $select = $sql->select(array('sn' => 'serial_number'));
                $select->columns(
                        array(
                                'sn_id' => 'serial_id' ,
                                'serial_number' => 'serial_number' ,
                                'serial_name' => 'serial_name' ,
                                'pay_time' => 'pay_time' ,
                                'room_id' => 'room_id' ,
                                'house_id' => 'house_id' ,
                                'sn_money' => 'money' ,
                                'type' => 'type' ,
                                'house_type' => 'house_type' ,
                                'source' => 'source' ,
                                'source_id' => 'source_id' ,
                                'status' => 'status',
                                'serial_id' => 'serial_id',
                        ));
                $where = new \Zend\Db\Sql\Where();
                $where->equalTo('sn.company_id' , $cid);
                $where->equalTo('sn.is_delete' , 0);
                $link_where = new \Zend\Db\Sql\Where();
        
        
                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.house_id' , $dis_arr);
                $link_where->orPredicate($or_where);
                //这样有问题
                $or_where = new \Zend\Db\Sql\Where();
                $or_where->equalTo('sn.source' , 'landlord_contract');
                $or_where->in('sn.source_id' , $lc_arr);
                $link_where->orPredicate($or_where);
        
                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.house_type' , array(0 , 1));
                $link_where->orPredicate($or_where);
                $where->addPredicate($link_where);
            }
            if ($room_focus_id && !$house_id && ($pattern ? $pattern : $pattern == 00))
            {
                $sql = $serial_model->getSqlObject();
                $select = $sql->select(array('sn' => 'serial_number'));
                $select->columns(
                        array(
                                'sn_id' => 'serial_id' ,
                                'serial_number' => 'serial_number' ,
                                'serial_name' => 'serial_name' ,
                                'pay_time' => 'pay_time' ,
                                'room_id' => 'room_id' ,
                                'house_id' => 'house_id' ,
                                'sn_money' => 'money' ,
                                'type' => 'type' ,
                                'house_type' => 'house_type' ,
                                'source' => 'source' ,
                                'source_id' => 'source_id' ,
                                'status' => 'status',
                                'serial_id' => 'serial_id',
                        ));
                $where = new \Zend\Db\Sql\Where();
                $where->equalTo('sn.company_id' , $cid);
                $where->equalTo('sn.is_delete' , 0);
                $link_where = new \Zend\Db\Sql\Where();
        
                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.room_id' , $f_arr);
                $link_where->orPredicate($or_where);
        
                $or_where = new \Zend\Db\Sql\Where();
                $or_where->equalTo('sn.source' , 'landlord_contract');
                $or_where->in('sn.source_id' , $lc_arr);
                $link_where->orPredicate($or_where);
        
                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.house_type' , array(0 , 2));
                $link_where->orPredicate($or_where);
                $where->addPredicate($link_where);
            }
            if ($room_focus_id && $house_id && ($pattern ? $pattern : $pattern == 00))
            {
                $sql = $serial_model->getSqlObject();
                $select = $sql->select(array('sn' => 'serial_number'));
                $select->columns(
                        array(
                                'sn_id' => 'serial_id' ,
                                'serial_number' => 'serial_number' ,
                                'serial_name' => 'serial_name' ,
                                'pay_time' => 'pay_time' ,
                                'room_id' => 'room_id' ,
                                'house_id' => 'house_id' ,
                                'sn_money' => 'money' ,
                                'type' => 'type' ,
                                'house_type' => 'house_type' ,
                                'source' => 'source' ,
                                'source_id' => 'source_id' ,
                                'status' => 'status',
                                'serial_id' => 'serial_id',
                        ));
                $where = new \Zend\Db\Sql\Where();
                $where->equalTo('sn.company_id' , $cid);
                $where->equalTo('sn.is_delete' , 0);
                $link_where = new \Zend\Db\Sql\Where();
                if ($pattern == 01 || $pattern == 11)
                {
                    $or_where = new \Zend\Db\Sql\Where();
                    $or_where->in('sn.house_id' , $dis_arr);
                    $link_where->orPredicate($or_where);
                }
                if ($pattern == 10 || $pattern == 11)
                {
                    $or_where = new \Zend\Db\Sql\Where();
                    $or_where->in('sn.room_id' , $f_arr);
                    $link_where->orPredicate($or_where);
                }
        
                $or_where = new \Zend\Db\Sql\Where();
                $or_where->equalTo('sn.source' , 'landlord_contract');
                $or_where->in('sn.source_id' , $lc_arr);
                $link_where->orPredicate($or_where);
        
                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.house_type' , array(0 , 1 , 2));
                $link_where->orPredicate($or_where);
                $where->addPredicate($link_where);
            }
            //新注册账号添加业主后,没有添加房源
            if ($lc_contract_id && !$room_focus_id && !$house_id && ($pattern ? $pattern : $pattern == 00))
            {
                $sql = $serial_model->getSqlObject();
                $select = $sql->select(array('sn' => 'serial_number'));
                $select->columns(
                        array(
                                'sn_id' => 'serial_id' ,
                                'serial_number' => 'serial_number' ,
                                'serial_name' => 'serial_name' ,
                                'pay_time' => 'pay_time' ,
                                'room_id' => 'room_id' ,
                                'house_id' => 'house_id' ,
                                'sn_money' => 'money' ,
                                'type' => 'type' ,
                                'house_type' => 'house_type' ,
                                'source' => 'source' ,
                                'source_id' => 'source_id' ,
                                'status' => 'status',
                                'serial_id' => 'serial_id',
                        ));
                $where = new \Zend\Db\Sql\Where();
                $where->equalTo('sn.company_id' , $cid);
                $where->equalTo('sn.is_delete' , 0);
                $link_where = new \Zend\Db\Sql\Where();
                if ($pattern == 01 || $pattern == 11)
                {
                    $or_where = new \Zend\Db\Sql\Where();
                    $or_where->equalTo('sn.source' , 'landlord_contarct');
                    $or_where->in('sn.source_id' , $lc_arr);
                    $link_where->orPredicate($or_where);
                }
                if ($pattern == 10 || $pattern == 11)
                {
                    $or_where = new \Zend\Db\Sql\Where();
                    $or_where->equalTo('sn.source' , 'landlord_contarct');
                    $or_where->in('sn.source_id' , $lc_arr);
                    $link_where->orPredicate($or_where);
                }
        
                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.house_type' , array(0 , 1 , 2));
                $link_where->orPredicate($or_where);
                $where->addPredicate($link_where);
            }
            if (!$room_focus_id && !$house_id && !$lc_contract_id && ($pattern ? $pattern : $pattern == 00))
            {
                return Array(
                        'page' => Array
                        (
                                'page' => 0 ,
                                'size' => 0 ,
                                'count' => 0 ,
                                'cpage' => 0 ,
                        ) ,
                        'data' => Array()
                );
            }
            if (!$room_focus_id && $house_id && $pattern == 10)
            {
                return Array(
                        'page' => Array
                        (
                                'page' => 0 ,
                                'size' => 0 ,
                                'count' => 0 ,
                                'cpage' => 0 ,
                        ) ,
                        'data' => Array()
                );
            }
            if ($room_focus_id && !$house_id && $pattern == 01)
            {
                return Array(
                        'page' => Array
                        (
                                'page' => 0 ,
                                'size' => 0 ,
                                'count' => 0 ,
                                'cpage' => 0 ,
                        ) ,
                        'data' => Array()
                );
            }
            if (isset($data['deal_type']) && $data['deal_type'] != 0)
            {//财务类别类型
                $select->join(array('sd' => 'serial_detail') , 'sd.serial_id=sn.serial_id' , array('serial_id' , 'serial_detail_id'));
                //$select->leftjoin(array('ssb' => 'serial_strike_balance') , 'sd.serial_detail_id = ssb.serial_detail_id' , array('ssb_money' => new Expression('IF(ssb.money,SUM(ssb.money),0)')));
                $where->equalTo('sd.fee_type_id', $data['deal_type']);
                $select->group('sn.serial_id');
            }
            if (isset($data['start_date']) && $data['start_date'])
            {//开始时间
                $where->greaterThanOrEqualTo('sn.pay_time' , $data['start_date']);
            }
            if (isset($data['end_date']) && $data['end_date'])
            {//结束时间
                if ($data['start_date'] == $data['end_date'])
                {
                    $where->lessThanOrEqualTo('sn.pay_time' , time());
                }
                else
                {
                    $where->lessThanOrEqualTo('sn.pay_time' , $data['end_date']);
                }
            }
            if (isset($data['house_type']) && $data['house_type'])
            {//房源类型
                $where->equalTo('sn.house_type' , $data['house_type']);
            }
            if (isset($data['finance_type']) && $data['finance_type'])
            {//财务收支类型
                $where->equalTo('sn.type' , $data['finance_type']);
            }
            //$select->leftjoin(array('h' => 'house') , new \Zend\Db\Sql\Predicate\Expression('sn.house_id > 0 and h.house_id = sn.house_id and sn.house_type=1') , array('house_name' , 'custom_number'));
            //$select->leftjoin(array('rf' => 'room_focus') , new \Zend\Db\Sql\Predicate\Expression('sn.room_id > 0 and rf.room_focus_id = sn.room_id and sn.house_type=2') , array('rf_custom_number' => 'custom_number'));
            $select->leftjoin(array('h' => 'house') , new \Zend\Db\Sql\Predicate\Expression('sn.house_id > 0 and h.house_id = sn.house_id and sn.house_type=1') , array('custom_number'));
            $select->leftjoin(array('rf' => 'room_focus') , new \Zend\Db\Sql\Predicate\Expression('sn.room_id > 0 and rf.room_focus_id = sn.room_id and sn.house_type=2') , array('rf_custom_number' => 'custom_number'));
            if (isset($data['search']) && $data['search'])
            {//有搜索关键词
                $select->leftjoin(array('r' => 'room') , new \Zend\Db\Sql\Predicate\Expression('r.room_id = sn.room_id and sn.house_type=1 and sn.room_id>0') , array('r_custom_number' => 'custom_number'));
                $select->leftjoin(array('f' => 'flat') , new \Zend\Db\Sql\Predicate\Expression('f.flat_id = rf.flat_id') , array('flat_name' , 'f_custom_number' => 'custom_number'));
                $searchWhere = new \Zend\Db\Sql\Where();
                $search = "%{$data['search']}%";
                $linkWhere = new \Zend\Db\Sql\Where();
                $linkWhere->like('h.house_name' , $search);
                $searchWhere->orPredicate($linkWhere);
                $linkWhere = new \Zend\Db\Sql\Where();
                $linkWhere->like('f.flat_name' , $search);
                $searchWhere->orPredicate($linkWhere);
                $linkWhere = new \Zend\Db\Sql\Where();
                $linkWhere->like('h.custom_number' , $search);
                $searchWhere->orPredicate($linkWhere);
                $linkWhere = new \Zend\Db\Sql\Where();
                $linkWhere->like('f.custom_number' , $search);
                $searchWhere->orPredicate($linkWhere);
                $where->addPredicate($searchWhere);
            }
            elseif (isset($data['room_id']))
            {
        
                // $select->leftjoin(array('r' => 'room') , new \Zend\Db\Sql\Predicate\Expression('r.room_id = sn.room_id and sn.house_type=1') , array('r_custom_number' => 'custom_number'));
                $where->equalTo('sn.house_type' , '1');
                $where->equalTo('sn.room_id' , $data['room_id']);
            }
            elseif (isset($data['focus_id']))
            {
                $focus_id = $data['focus_id'];
                $where->equalTo('sn.house_type' , '2');
                $where->equalTo('sn.room_id' , $focus_id);
            }
        
            if (isset($data['time']))
            {
        
        
                //年筛选
                if (strlen($data['time']) == 4)
                {
                    $create_time = strtotime($data['time'] . '0101');
                    $where->greaterThanOrEqualTo('sn.pay_time' , $create_time);
                    $where->lessThan('sn.pay_time' , strtotime("+1 year" , $create_time));
                }
                else
                {
                    $create_time = strtotime($data['time']);
                    $where->greaterThanOrEqualTo('sn.pay_time' , $create_time);
                    $where->lessThan('sn.pay_time' , strtotime("+1 month" , $create_time));
                }
            }
            /**
             * 权限
             */
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $tables = array();
                $houseJoin = function($table) use ($select,&$tables){
                    $tables[] = $table;
                    $join = new \Zend\Db\Sql\Predicate\Expression("h.house_id IS NOT NULL and `{$table}`.authenticatee_id=h.house_id");
                    return array($join,$select::JOIN_LEFT);
                };
                $permisions->VerifyDataCollectionsPermissionsModel($select,$houseJoin, $permisions::USER_AUTHENTICATOR, $user['user_id'],\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
                $flatJoin = function($table) use($select,&$tables){
                    $tables[] = $table;
                    $join = new \Zend\Db\Sql\Predicate\Expression("rf.flat_id IS NOT NULL and `{$table}`.authenticatee_id=rf.flat_id");
                    return array($join,$select::JOIN_LEFT,array('authenticatee_id2'=>'authenticatee_id'));
                };
                $permisions->VerifyDataCollectionsPermissionsModel($select,$flatJoin, $permisions::USER_AUTHENTICATOR, $user['user_id'],\Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
        
                $and = new \Zend\Db\Sql\Where();
                 
                $or = new \Zend\Db\Sql\Where();
                $or->isNotNull("{$tables[0]}.authenticatee_id");
                $and->orPredicate($or);
                 
                $or = new \Zend\Db\Sql\Where();
                $or->isNotNull("{$tables[1]}.authenticatee_id");
                $and->orPredicate($or);
                 
                $or = new \Zend\Db\Sql\Where();
                $or->isNull("{$tables[0]}.authenticatee_id");
                $or->isNull("{$tables[1]}.authenticatee_id");
                $or->equalTo('sn.user_id', $user['user_id']);
                $or->equalTo('sn.house_id', 0);
                $or->equalTo('sn.room_id', 0);
                $and->orPredicate($or);
                 
                $where->andPredicate($and);
            }
            $where->notEqualTo('sn.final_money', 0);
            $where->equalTo('sn.city_id', $user['city_id']);
            $select->where($where);
            $select->order('sn.serial_id DESC');
            $count = $select->count();
            $data['data'] = $select->execute();
            //$data = \Core\Db\Sql\Select::pageSelect($select , $count , $page , $size);
            if ($data['data'] && isset($data['data'][0]['house_name']))
            {//有搜索关键词
                foreach ($data['data'] as $key => $value)
                {
                    if ($value['house_type'] == 1)
                    {
                        $value['custom_number'] = $value['r_custom_number'] ? $value['custom_number'] . '-' . $value['r_custom_number'] : $value['custom_number'];
                        $value['house_name'] = $value['r_custom_number'] ? $value['house_name'] . '-' . $value['r_custom_number'] : $value['house_name'];
                    }
                    else if ($value['house_type'] == 2)
                    {
                        $value['custom_number'] = $value['f_custom_number'] . '-' . $value['rf_custom_number'];
                        $value['house_name'] = $value['flat_name'] . '-' . $value['rf_custom_number'];
                    }
                    else
                    {
                        $value['house_name'] = '非房间流水';
                        $value['custom_number'] = $value['serial_number'];
                    }
                    unset($value['r_custom_number'] , $value['rf_custom_number'] , $value['room_focus_id'] , $value['flat_name']);
                    $data['data'][$key] = $value;
                }
            }
            elseif ($data['data'])
            {//没有搜索关键词
                $house_id = $room_id = $room_focus_id = $source_id = array();
                foreach ($data['data'] as $value)
                {
                    $serial_id_arr[] = $value['serial_id'];
                    if ($value['house_type'] == 1)
                    {
                        $house_id[] = $value['house_id'];
                        if ($value['room_id'])
                        {
                            $room_id[] = $value['room_id'];
                        }
                    }
                    else if ($value['house_type'] == 2)
                    {
                        $room_focus_id[] = $value['room_id'];
                    }
                    if ($value['source'] == 'landlord_contract')
                    {//业主合同
                        $source_id[] = $value['source_id'];
                    }
                }
                //显示冲账信息
                if (isset($serial_id_arr))
                {
                    $serial_strike_model = new \Common\Model\Erp\SerialStrikeBalance();
                    $serial_strike_data = $serial_strike_model->getAllStrikeData($serial_id_arr , 1);
                    foreach ($serial_strike_data as $key => $val)
                    {
                        $new_serial_strike[$val['serial_id']]['serial_id'] = $val['serial_id'];
                        $new_serial_strike[$val['serial_id']]['ssb_money'] += $val['ssb_money'];
                    }
                    unset($serial_strike_data);
                    foreach ($data['data'] as $key => $val)
                    {
                        if (isset($new_serial_strike[$val['serial_id']]))
                        {
                            $data['data'][$key]['ssb_money'] = $new_serial_strike[$val['serial_id']]['ssb_money'];
                        }
                        else
                        {
                            $data['data'][$key]['ssb_money'] = 0;
                        }
                    }
                }
        
                $houseData = $roomData = $focusData = array();
                if ($house_id)
                {
                    $model = new \Common\Model\Erp\House();
                    $houseData = $model->getData(array('house_id' => array_unique($house_id)));
                    $temp = array();
                    foreach ($houseData as $value)
                    {
                        $temp[$value['house_id']] = $value;
                    }
                    $houseData = $temp;
                    unset($temp);
                }
                if ($room_id)
                {
                    $model = new \Common\Model\Erp\Room();
                    $roomData = $model->getData(array('room_id' => array_unique($room_id)));
                    $temp = array();
                    foreach ($roomData as $value)
                    {
                        $temp[$value['room_id']] = $value;
                    }
                    $roomData = $temp;
                    unset($temp);
                }
                if ($room_focus_id)
                {
                    $model = new \Common\Model\Erp\RoomFocus();
                    $sql = $serial_model->getSqlObject();
                    $select = $sql->select(array('rf' => 'room_focus'));
                    $select->columns(array('room_focus_id' => 'room_focus_id' , 'custom_number' => 'custom_number' , 'floor' => 'floor'));
                    $select->join(array('f' => 'flat') , 'rf.flat_id=f.flat_id' , array('flat_name'));
                    $select->where(array('room_focus_id' => array_unique($room_focus_id)));
                    $focusData = $select->execute();
                    $temp = array();
                    foreach ($focusData as $value)
                    {
                        $temp[$value['room_focus_id']] = $value;
                    }
                    $focusData = $temp;
                    unset($temp);
                }
                if ($source_id)
                {
                    $landlord_con_model = new \Common\Model\Erp\LandlordContract();
                    $lc_sql = $landlord_con_model->getSqlObject();
                    $lc_select = $lc_sql->select(array('lc' => 'landlord_contract'));
                    $lc_select->columns(array('contract_id' => 'contract_id' , 'hosue_name' => 'hosue_name'));
                    $lc_where = new \Zend\Db\Sql\Where();
                    $lc_where->in('contract_id' , array_unique($source_id));
                    $lc_select->where($lc_where);
                    $lc_con_data = $lc_select->execute();
                    foreach ($lc_con_data as $value)
                    {
                        $temp[$value['contract_id']] = $value;
                    }
                    $lc_con_data = $temp;
                    unset($temp);
                }
                foreach ($data['data'] as $key => $value)
                {
                    if ($value['house_type'] == 1)
                    {
                        $value['custom_number'] = isset($houseData[$value['house_id']]) ? $houseData[$value['house_id']]['custom_number'] : '';
                        $value['house_name'] = isset($houseData[$value['house_id']]) ? $houseData[$value['house_id']]['house_name'] : '';
                        //$value['rental_way'] = isset($houseData[$value['house_id']]) ? $houseData[$value['house_id']]['rental_way'] : '';
                        if ($value['room_id'])
                        {
                            $room_type = isset($roomData[$value['room_id']]) ? $roomData[$value['room_id']]['room_type'] : '';
                            $room_type = ($room_type = 'main') ? '主卧' : (($room_type = 'second') ? '次卧' : (($room_type = 'guest' ? '客卧' : '')));
                            //$value['custom_number'] .= '-' . (isset($roomData[$value['room_id']]) ? $roomData[$value['room_id']]['custom_number'] : '');
                            $value['house_name'] .= $room_type . (isset($roomData[$value['room_id']]) ? $roomData[$value['room_id']]['custom_number'] . '号' : '');
                            //$value['rental_way'] = isset($houseData[$value['house_id']]) ? $houseData[$value['house_id']]['rental_way'] : '';
                        }
                        if ($value['source'] == 'landlord_contract')
                        {//业主合同
                            $value['house_name'] = isset($lc_con_data[$value['source_id']]) ? $lc_con_data[$value['source_id']]['hosue_name'] : '';
                        }
                    }
                    else if ($value['house_type'] == 2)
                    {
                        //$value['custom_number'] = isset($focusData[$value['room_id']]) ? $focusData[$value['room_id']]['custom_number'] : '';
                        $value['house_name'] = isset($focusData[$value['room_id']]) ? $focusData[$value['room_id']]['flat_name'] . $focusData[$value['room_id']]['floor'] . '楼' . $focusData[$value['room_id']]['custom_number'] . '号' : '';
                        if ($value['source'] == 'landlord_contract')
                        {//业主合同
                            $value['house_name'] = isset($lc_con_data[$value['source_id']]) ? $lc_con_data[$value['source_id']]['hosue_name'] : '';
                        }
                    }
                    else
                    {
                        $value['house_name'] = '非房间流水';
                        $value['custom_number'] = $value['serial_number'];
                    }
                    $data['data'][$key] = $value;
                }
            }
            return $data;
        }
        
        /**
         * 房源类型流水 联合搜索(分散式  集中式) 流水
         * 修改时间 2015年5月4日11:16:23
         * 
         * @author ft
         */
        public function getFocusAndDisperseSerial()
        {
            $serial_num_model = new \Common\Model\Erp\SerialNumber();
            //集中式
            $sql = $serial_num_model->getSqlObject();
            $select = $sql->select(array('sn' => 'serial_number'));
            $select->columns(
                    array(
                        'sn_id' => 'serial_id' ,
                        'serial_number' => 'serial_number' ,
                        'serial_name' => 'serial_name' ,
                        'pay_time' => 'pay_time' ,
                        'room_id' => 'room_id' ,
                        'sn_money' => 'money' ,
            ));
            $select->leftjoin(array('rf' => 'room_focus') , 'sn.room_id = rf.room_focus_id' , array('floor' => 'floor' , 'rf_custom_num' => 'custom_number'));
            $select->leftjoin(array('f' => 'flat') , 'rf.flat_id = f.flat_id' , array('flat_name' => 'flat_name' , 'f_custom_num' => 'custom_number' , 'address' => 'address'));
            $select->leftjoin(array('ssb' => 'serial_strike_balance') , 'sn.serial_id = ssb.serial_id' , array('ssb_money' => 'money'));
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('sn.house_type' , 2);
            $where->equalTo('sn.is_delete' , 0);
            $where->equalTo('sn.room_id' , new Expression('rf.room_focus_id'));
            $select->where($where);
            $data['focus'] = $select->execute();
            //分散式
            $sql2 = $serial_num_model->getSqlObject();    //分散式
            $select2 = $sql2->select(array('sn' => 'serial_number'));
            $select2->columns(
                    array(
                        'sn_id' => 'serial_id' ,
                        'serial_number' => 'serial_number' ,
                        'serial_name' => 'serial_name' ,
                        'pay_time' => 'pay_time' ,
                        'room_id' => 'room_id' ,
                        'sn_money' => 'money' ,
            ));
            $select2->leftjoin(
                    array('h' => 'house') , 'sn.house_id = h.house_id' , array(
                'h_name' => 'house_name' ,
                'h_custom_num' => 'custom_number' ,
                'h_cost' => 'cost' ,
                'h_unit' => 'unit' ,
                'h_number' => 'number'
            ));
            $select2->leftjoin(array('r' => 'room') , 'sn.room_id = r.room_id' , array('r_custom_num' => 'custom_number'));
            $select2->leftjoin(array('ssb' => 'serial_strike_balance') , 'sn.serial_id = ssb.serial_id' , array('ssb_money' => 'money'));
            $where2 = new \Zend\Db\Sql\Where();
            $where2->equalTo('sn.house_type' , 1);
            $where2->equalTo('sn.is_delete' , 0);
            $where2->equalTo('sn.room_id' , new Expression('r.room_id'));
            $select2->where($where2);
            $data['disperse'] = $select2->execute();
            return $data;
        }

        /**
         * 根据城市id 查询所属分散式房源
         * 修改时间  2015年6月23日19:51:48
         * 
         * @author ft
         * @param  $citys_id (城市id)
         * @param  $company_id(公司id)
         */
        public function getHouseIdByCityId($city_id , $company_id)
        {
            $city_model = new \Common\Model\Erp\City();
            $sql = $city_model->getSqlObject();
            $select = $sql->select(array('c' => 'city'));
            $select->columns(array());
            $select->leftjoin(array('ct' => 'community') , 'c.city_id = ct.city_id' , array());
            $select->leftjoin(array('h' => 'house') , 'ct.community_id = h.community_id' , array('house_id' => 'house_id'));
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('c.city_id' , $city_id);
            $where->equalTo('h.company_id' , $company_id);
            //$where->equalTo('h.is_delete' , 0);
            $select->where($where);
            return $select->execute();
        }

        /**
         * 根据城市id 查询所属集中式房源
         * 修改时间  2015年6月23日19:51:48
         *
         * @author ft
         * @param  $citys_id (城市id)
         * @param  $company_id(公司id)
         */
        public function getRoomFocusIdByCityId($city_id , $company_id)
        {
            $city_model = new \Common\Model\Erp\City();
            $sql = $city_model->getSqlObject();
            $select = $sql->select(array('c' => 'city'));
            $select->columns(array());
            $select->leftjoin(array('f' => 'flat') , 'c.city_id = f.city_id' , array());
            $select->leftjoin(array('rf' => 'room_focus') , 'rf.flat_id = f.flat_id' , array('rf_room_id' => 'room_focus_id'));
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('c.city_id' , $city_id);
            $where->equalTo('f.company_id' , $company_id);
            $where->equalTo('rf.company_id' , $company_id);
            $select->where($where);
            return $select->execute();
        }

        /**
         * 根据城市id 查询所属业主合同
         * 修改时间  2015年7月9日13:30:55
         * 
         * @author ft
         * @param  $city_id (城市id)
         * @param  $company_id (公司id)
         */
        public function getlandlordContractByCityId($city_id , $company_id)
        {
            $landlord_model = new \Common\Model\Erp\LandlordContract();
            $sql = $landlord_model->getSqlObject();
            $select = $sql->select(array('lc' => 'landlord_contract'));
            $select->columns(array('contract_id' => 'contract_id'));
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('lc.city_id' , $city_id);
            $where->equalTo('lc.company_id' , $company_id);
            $select->where($where);
            return $select->execute();
        }

        /**
         * 获取租客合同的信息,
         * 修改时间  2015年7月1日22:23:50
         * 
         * @author ft
         * @param $contract_id
         */
        public function getContractInfoById($contract_id)
        {
            $tenant_con_model = new \Common\Model\Erp\TenantContract();
            $sql = $tenant_con_model->getSqlObject();
            $select = $sql->select(array('tc' => 'tenant_contract'));
            $select->columns(array('pay' => 'pay' , 'next_pay_time' => 'next_pay_time' , 'dead_line' => 'dead_line' , 'is_haveson' => 'is_haveson' , 'rent' => 'rent' , 'advance_time' => 'advance_time', 'signing_time' => 'signing_time'));
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('tc.contract_id' , $contract_id);
            $select->where($where);
            return $select->execute();
        }

        /**
         * 更新合同下次支付时间
         * 修改时间  2015年7月1日22:34:06
         * 
         * @author ft  1443628800
         * @param array $contract_info
         * @param int $contract_id
         * @param string $room_name
         */
        public function updateContractPayTime($contract_id, $contract_info, $res = NULL)
        {
            $tenant_con_model = new \Common\Model\Erp\TenantContract();
            $todo_model = new \Common\Model\Erp\Todo();
            $user_model = new \Common\Helper\Erp\User();
            $user_info = $user_model->getCurrentUser();
            $company_id = $user_info['company_id'];
            //合同数据库下次支付时间
            $date = $contract_info['next_pay_time'];
            
            if ($res && is_numeric($contract_info['advance_time']) && $contract_info['advance_time'] != 0)
            {
                $next_time = strtotime("+{$contract_info['advance_time']} day" , $date);
                $pay_num = ($contract_info['pay'] != 0 ? $contract_info['pay'] : 1);
                //$next_time = strtotime("+$pay_num month" , $next_time);//最新下次付款时间
                $next_time = \Common\Helper\Date\Contract::AddMonth($pay_num, $next_time, $contract_info['signing_time']);
            } elseif ($res && is_numeric($contract_info['advance_time']) && $contract_info['advance_time'] == 0) 
            {
                $pay_num = ($contract_info['pay'] != 0 ? $contract_info['pay'] : 1);
                //$next_time = strtotime("+$pay_num month" , $date);//最新下次付款时间
                $next_time = $date;//最新下次付款时间
                $next_time = \Common\Helper\Date\Contract::AddMonth($pay_num, $next_time, $contract_info['signing_time']);
            }
            //第一次收费处理逻辑
            if (!$res && is_numeric($contract_info['advance_time']) && $contract_info['advance_time'] != 0)
            {
                $pay_num = ($contract_info['pay'] != 0 ? $contract_info['pay'] : 1);
                //$next_time = strtotime("+$pay_num month" , $date);//最新下次付款时间
                $next_time = $date;//最新下次付款时间
                $next_time = \Common\Helper\Date\Contract::AddMonth($pay_num, $next_time, $contract_info['signing_time']);
                $next_time = strtotime("-{$contract_info['advance_time']} day" , $next_time);
            }
            if (!$res  && is_numeric($contract_info['advance_time']) && $contract_info['advance_time'] == 0)
            {
                $pay_num = ($contract_info['pay'] != 0 ? $contract_info['pay'] : 1);
                //$next_time = strtotime("+$pay_num month" , $date);//最新下次付款时间
                $next_time = $date;//最新下次付款时间
                $next_time = \Common\Helper\Date\Contract::AddMonth($pay_num, $next_time, $contract_info['signing_time']);
            }
            $tenant_con_model->Transaction();
            //如果收租后的下次支付时间 >= 老合同到期时间, 并且有子合同
            if ($next_time >= $contract_info['dead_line'] && $contract_info['is_haveson'] == 1)
            {
                //老合同退休后,子合同下次支付时间默认加一天
                //$next_time = $next_time + 86400;
                //查询子合同条件
                $haveson_where = array('parent_id' => $contract_id);
                //$haveson_column = array('contract_id' => 'contract_id' , 'dead_line' => 'dead_line' , 'pay' => 'pay' , 'rent' => 'rent' , 'next_pay_time' => 'next_pay_time');
                $haveson_column = array('contract_id' => 'contract_id', 'pay' => 'pay', 'next_pay_time' => 'next_pay_time', 'dead_line' => 'dead_line', 'is_haveson' => 'is_haveson', 'rent' => 'rent', 'advance_time' => 'advance_time');
                //子合同的数据
                $haveson_data = $tenant_con_model->getOne($haveson_where , $haveson_column);
                $date = $haveson_data['next_pay_time'];
                $pay_num = ($haveson_data['pay'] != 0 ? $haveson_data['pay'] : 1);
                $next_time = strtotime("+$pay_num month" , $date);//最新下次付款时间
                
                if (is_numeric($haveson_data['advance_time']) && $haveson_data['advance_time'] != 0)
                {
                    $next_time = strtotime("-{$haveson_data['advance_time']} day" , $next_time);
                }
                //待办收租
                $ntnext_pay_time = date('Y-m-d' , $next_time);
                $shouzu_todo_info = $todo_model->getOne(array('module' => 'tenant_contract_shouzu' , 'entity_id' => $contract_id));
                $shouzu_old_date = date('Y', $shouzu_todo_info['deal_time']);
                $shouzu_new_content = substr_replace($shouzu_todo_info['content'],$ntnext_pay_time, strripos($shouzu_todo_info['content'], $shouzu_old_date),10);
                $son_backlog_where = array('module' => 'tenant_contract_shouzu' , 'entity_id' => $contract_id);
                $son_backlog_data = array(
                   'content' => $shouzu_new_content,
                   'entity_id' => $haveson_data['contract_id'],
                   'url' => '/index.php?c=finance-serial&a=addincome&contract_id=' . $haveson_data['contract_id'] ,
                   'status' => 0 , // 0未处理;1已查看;2已处理
                   'deal_time' => $next_time , // 处理时间
                );
                //待办到期
                $old_con_daoqi = date('Y' , $contract_info['dead_line']);
                $daoqi_todo_info = $todo_model->getOne(array('module' => 'tenant_contract' , 'entity_id' => $contract_id));
                $daoqi_new_content = substr_replace($daoqi_todo_info['content'],date('Y-m-d', $haveson_data['dead_line']), strripos($daoqi_todo_info['content'], $old_con_daoqi),10);
                $daoqi_backlog_where = array('module' => 'tenant_contract' , 'entity_id' => $contract_id);
                $daoqi_backlog_data = array(
                        'content' => $daoqi_new_content,
                        'entity_id' => $haveson_data['contract_id'],
                        'url' => '/index.php?c=tenant-index&a=edit&contract_id=' . $haveson_data['contract_id'] ,
                        'status' => 0 , // 0未处理;1已查看;2已处理
                        'deal_time' => $haveson_data['dead_line'] , // 处理时间
                );
                //将老合同的下次支付时间为到期时间
                $next_time_res = $tenant_con_model->edit(array('contract_id' => $contract_id) , array('next_pay_time' => $contract_info['dead_line']));

                //更新子合同下次付款时间
                $edit_where = array('contract_id' => $haveson_data['contract_id']);
                //这里的子合同也许会需要在减去提前付款天数
                $new_pay_time = array('next_pay_time' => $next_time);
                $son_res = $tenant_con_model->edit($edit_where , $new_pay_time);

                //更新子合同待办事项
                $son_backlog = $todo_model->edit($son_backlog_where , $son_backlog_data);
                $daoqi_res = $todo_model->edit($daoqi_backlog_where, $daoqi_backlog_data);
                if (!$next_time_res || !$son_res || !$son_backlog)
                {
                    $tenant_con_model->rollback();
                    return false;
                }
                $tenant_con_model->commit();
                return true;
            }
            //如果下次支付时间 大于或等于 数据库的下次支付时间, 并且没有子合同, 就不在递增下次支付时间, 并删除待办事项
            if ($next_time >= $contract_info['dead_line'] && $contract_info['is_haveson'] != 1)
            {
                $res = $tenant_con_model->edit(array('contract_id' => $contract_id) , array('next_pay_time' => $contract_info['dead_line']));
                //删除待办事件条件
                $backlog_where = array('module' => 'tenant_contract_shouzu' , 'entity_id' => $contract_id);
                //$daoqi_where = array('module' => 'tenant_contract' , 'entity_id' => $contract_id);
                $todo_res = $todo_model->delete($backlog_where);
               	//$daoqi_res = $todo_model->delete($daoqi_where);
                $tenant_con_model->commit();
                return true;
            }
            //如果没有子合同,并且没到期,那么修改合同下次支付时间,并修改待办事项
            if ($next_time < $contract_info['dead_line'])
            {
                if ($res && is_numeric($contract_info['advance_time']) && $contract_info['advance_time'] != 0)
                {
                    $next_time = strtotime("-{$contract_info['advance_time']} day" , $next_time);
                    $where = array('contract_id' => $contract_id);
                    $next_pay_data = array('next_pay_time' => $next_time);
                    $res = $tenant_con_model->edit($where , $next_pay_data);
                }
                elseif ($res && $contract_info['advance_time'] == 0)
                {
                    $where = array('contract_id' => $contract_id);
                    $next_pay_data = array('next_pay_time' => $next_time);
                    $res = $tenant_con_model->edit($where , $next_pay_data);
                }
                else
                {
                    $where = array('contract_id' => $contract_id);
                    $next_pay_data = array('next_pay_time' => $next_time);
                    $res = $tenant_con_model->edit($where , $next_pay_data);
                }
                if (!$res)
                {
                    $tenant_con_model->rollback();
                    return false;
                }
                $tenant_con_model->commit();
                //修改收租日程表
                $ntnext_pay_time = date('Y-m-d' , $next_time);
                $todo_where = array('module' => 'tenant_contract_shouzu' , 'entity_id' => $contract_id);
                $todo_info = $todo_model->getOne($todo_where);
                $old_date = date('Y', $todo_info['deal_time']);
                
                $new_content = substr_replace($todo_info['content'],$ntnext_pay_time, strripos($todo_info['content'], $old_date),10);
                $dataTodo = array(
                    'content' => $new_content,
                    'status' => 0 , // 0未处理;1已查看;2已处理
                    'deal_time' => $next_time , // 处理时间
                );
                if (!$todo_model->edit($todo_where , $dataTodo))
                {
                    $tenant_con_model->rollback();
                    return false;
                }
                return true;
            }
        }

        /**
         * 获取业主合同信息
         * 修改时间  2015年7月2日16:17:04
         * 
         * @author ft
         * @param int $contract_id
         */
        public function getLandlordContractInfo($contract_id)
        {
            $landlord_model = new \Common\Model\Erp\LandlordContract();
            $sql = $landlord_model->getSqlObject();
            $select = $sql->select(array('lc' => 'landlord_contract'));
            $select->columns(
                    array(
                            'pay' => 'pay' , 
                            'next_pay_time' => 'next_pay_time' , 
                            'dead_line' => 'dead_line' , 
                            'parent_id' => 'parent_id' , 
                            'rent' => 'rent' , 
                            'free_day' => 'free_day' , 
                            'free_day_uite' => 'free_day_uite' , 
                            'advance_time' => 'advance_time',
                            'free_day' => 'free_day',
                            'signing_time' => 'signing_time',
                    ));
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('lc.contract_id' , $contract_id);
            $select->where($where);
            return $select->execute();
        }

        /**
         * 更新业主合同下次支付时间
         * 修改时间  2015年7月2日16:26:12
         * 
         * @author ft
         * @param  int $contract_id
         * @param  array $landlord_info
         */
        public function updateLandlordPayTime($contract_id , $landlord_info , $room_name , $res = NULL)
        {
            $landlord_model = new \Common\Model\Erp\LandlordContract();
            $todo_model = new \Common\Model\Erp\Todo();
            $user_model = new \Common\Helper\Erp\User();
            $user_info = $user_model->getCurrentUser();
            $company_id = $user_info['company_id'];
            
            //合同数据库下次支付时间
            $date = $landlord_info['next_pay_time'];
            //$pay_num = ($landlord_info['pay'] != 0 ? $landlord_info['pay'] : 1);
            //合同交租之后计算出下次支付时间
            //$last_pay_time = strtotime("+$pay_num month" , $date);//最新下次付款时间
            if ($res)
            {
                $pay_num = ($landlord_info['pay'] != 0 ? $landlord_info['pay'] : 1);
                $last_pay_time = \Common\Helper\Date\Contract::AddMonth($pay_num, $date, $landlord_info['signing_time']);
                //提前付款天数
                if ($landlord_info['advance_time'] != 0)
                {
                    $last_pay_time = strtotime("+{$landlord_info['advance_time']} day", $last_pay_time);
                }
                
                if ($landlord_info['free_day'] != 0)
                {
                    if ($landlord_info['free_day_uite'] == 1)
                    {//免租期 月
                        $last_pay_time = strtotime("-{$landlord_info['free_day']} month", $last_pay_time);
                    }
                    elseif ($landlord_info['free_day_uite'] == 2)
                    {//免租期 天
                        $last_pay_time = strtotime("-{$landlord_info['free_day']} day", $last_pay_time);
                    }
                }
                
            }
            elseif (!$res)
            {   //第一次收费
                $pay_num = ($landlord_info['pay'] != 0 ? $landlord_info['pay'] : 1);
                $last_pay_time = \Common\Helper\Date\Contract::AddMonth($pay_num, $date, $landlord_info['signing_time']);
                
                if ($landlord_info['free_day'] != 0)
                {
                    if ($landlord_info['free_day_uite'] == 1)
                    {//免租期 月
                        $last_pay_time = \Common\Helper\Date\Contract::AddMonth($landlord_info['free_day'], $last_pay_time, $landlord_info['signing_time']);
                        //$last_pay_time = strtotime("+{$landlord_info['free_day']} month", $last_pay_time);
                    }
                    elseif ($landlord_info['free_day_uite'] == 2)
                    {//免租期 天
                        $last_pay_time = strtotime("+{$landlord_info['free_day']} day", $last_pay_time);
                    }
                }
                if ($landlord_info['advance_time'] != 0)
                {
                    $last_pay_time = strtotime("-{$landlord_info['advance_time']} day", $last_pay_time);
                }
            }
            $lc_where = new \Zend\Db\Sql\Where();
            $lc_where->equalTo('parent_id', $contract_id);
            $lc_where->lessThan('next_pay_time', new Expression('dead_line'));
            //业主子合同
            $landlord_son = $landlord_model->getOne($lc_where);
            //如果交租后的下次支付时间 >= 老合同到期时间, 并且有子合同
            if ($last_pay_time >= $landlord_info['dead_line'] && $landlord_son['contract_id'])
            {
                $landlord_model->Transaction();
                //合同数据库下次支付时间
                $date = $landlord_son['next_pay_time'];
                $pay_num = ($landlord_son['pay'] != 0 ? $landlord_son['pay'] : 1);
                //合同交租之后计算出下次支付时间
                $last_pay_time = strtotime("+$pay_num month" , $date);//最新下次付款时间
                
                if ($landlord_son['free_day'] != 0)
                {
                    if ($landlord_info['free_day_uite'] == 1)
                    {//免租期 月
                        $last_pay_time = strtotime("+{$landlord_son['free_day']} month", $last_pay_time);
                    }
                    elseif ($landlord_info['free_day_uite'] == 2)
                    {//免租期 天
                        $last_pay_time = strtotime("+{$landlord_son['free_day']} day", $last_pay_time);
                    }
                }
                if ($landlord_son['advance_time'] != 0)
                {
                    $last_pay_time = strtotime("-{$landlord_son['advance_time']} day", $last_pay_time);
                }
                $ntnext_pay_time = date('Y-m-d' , $last_pay_time);
                
                //如果老合同到期,并且有子合同,那么子合同沿用老合同的待办事项, 并修改待办事项下次支付时间
                //交租待办
                $son_jiaozu_where = array('module' => 'landlord_contract_jiaozu' , 'entity_id' => $contract_id);
                $jiaozu_todo_info = $todo_model->getOne($son_jiaozu_where);
                $jiaozu_old_date = date('Y', $jiaozu_todo_info['deal_time']);
                
                $jiaozu_new_content = substr_replace($jiaozu_todo_info['content'],$ntnext_pay_time, strripos($jiaozu_todo_info['content'], $jiaozu_old_date),10);
                $son_jz_data = array(
                    //'module' => 'landlord_contract_jiaozu' , // 模块
                    'entity_id' => $landlord_son['contract_id'] , // 实体id 合同id
                    'content' => $jiaozu_new_content,
                    'url' => '/index.php?c=finance-serial&a=addexpense&contract_id=' . $landlord_son['contract_id'] ,
                    'status' => 0 , // 状态 0未处理;1已查看;2已处理;
                    'deal_time' => $last_pay_time , // 处理时间
                );
                //到期待办
                $son_daoqi_where = array('module' => 'landlord_contract' , 'entity_id' => $contract_id);
                $daoqi_todo_info = $todo_model->getOne($son_daoqi_where);
                $daoqi_old_date = date('Y', $daoqi_todo_info['deal_time']);
                
                $daoqi_new_content = substr_replace($daoqi_todo_info['content'],date('Y-m-d', $landlord_son['dead_line']), strripos($daoqi_todo_info['content'], $daoqi_old_date),10);
                $son_dq_data = array(
                        //'module' => 'landlord_contract_jiaozu' , // 模块
                        'entity_id' => $landlord_son['contract_id'] , // 实体id 合同id
                        'content' => $daoqi_new_content,
                        'url' => '/index.php?c=landlord-index&a=info&contract_id=' . $landlord_son['contract_id'] ,
                        'status' => 0 , // 状态 0未处理;1已查看;2已处理;
                        'deal_time' => $landlord_son['dead_line'] , // 处理时间
                );
                //更新子合同下次付款时间
                $edit_where = array('contract_id' => $landlord_son['contract_id']);
                $new_pay_time = array('next_pay_time' => $last_pay_time);
                $son_res = $landlord_model->edit($edit_where , $new_pay_time);
                if (!$son_res)
                {
                    $landlord_model->rollback();
                    return false;
                }
                //更新子合同待办事项
                if (!$todo_model->edit($son_jiaozu_where , $son_jz_data))
                {
                    $landlord_model->rollback();
                    return false;
                }
                if (!$todo_model->edit($son_daoqi_where , $son_dq_data))
                {
                    $landlord_model->rollback();
                    return false;
                }
                $landlord_model->edit(array('contract_id' => $contract_id) , array('end_line' => $landlord_info['dead_line'] , 'next_pay_time' => $landlord_info['dead_line'] , 'next_pay_money' => 0));
                $landlord_model->commit();
                return true;
            }
            //如果下次支付时间 大于或等于 数据库的到期时间, 并且没有子合同, 就不在递增下次支付时间, 并删除待办事项
            if ($last_pay_time >= $landlord_info['dead_line'] && empty($landlord_son))
            {
                $landlord_model->Transaction();
                $landlord_model->edit(array('contract_id' => $contract_id) , array('end_line' => $landlord_info['dead_line'] , 'next_pay_time' => $landlord_info['dead_line'] , 'next_pay_money' => 0));
                //删除待办事件条件
                $backlog_jz_where = array('module' => 'landlord_contract_jiaozu' , 'entity_id' => $contract_id);
                //$backlog_dq_where = array('module' => 'landlord_contract' , 'entity_id' => $contract_id);
                $todo_res = $todo_model->delete($backlog_jz_where);
                //$todo_res = $todo_model->delete($backlog_dq_where);
                $landlord_model->commit();
                return true;
            }
            //如果没有子合同,并且没到期,那么修改合同下次支付时间,并修改待办事项
            if ($last_pay_time < $landlord_info['dead_line'])
            {
                //判断是否免租期
                if ($res)
                {   //提前付款天数
                    if ($landlord_info['advance_time'] != 0)
                    {
                        $last_pay_time = strtotime("-{$landlord_info['advance_time']} day", $last_pay_time);
                    }
                    //$last_pay_time = $landlord_info['next_pay_time'];
                    if ($landlord_info['free_day'] != 0)
                    {
                        if ($landlord_info['free_day_uite'] == 1)
                        {//免租期 月
                            $last_pay_time = strtotime("+{$landlord_info['free_day']} month", $last_pay_time);
                        }
                        elseif ($landlord_info['free_day_uite'] == 2)
                        {//免租期 天
                            $last_pay_time = strtotime("+{$landlord_info['free_day']} day", $last_pay_time);
                        }
                    }
                    
                }
                
                $landlord_model->Transaction();
                $where = array('contract_id' => $contract_id);
                $next_pay_time = array('next_pay_time' => $last_pay_time);
                $res = $landlord_model->edit($where , $next_pay_time);
                if (!$res)
                {
                    $landlord_model->rollback();
                    return false;
                }
                //修改交租日程表
                $ntnext_pay_time = date('Y-m-d' , $last_pay_time);
                $dataTodo = array(
                    'content' => $room_name . '的租金' . (($landlord_info['pay'] != 0 ? $landlord_info['pay'] : 1) * $landlord_info['rent']) . '元' . '应于' . $ntnext_pay_time . '支付，请注意处理' ,
                    'url' => '/index.php?c=finance-serial&a=addexpense&contract_id=' . $contract_id , // 跳转地址
                    'status' => 0 , // 状态 0未处理;1已查看;2已处理
                    'deal_time' => $last_pay_time , // 处理时间
                );
                $todo_where = array('module' => 'landlord_contract_jiaozu' , 'entity_id' => $contract_id);
                if (!$todo_model->edit($todo_where , $dataTodo))
                {
                    $landlord_model->rollback();
                    return false;
                }
                $landlord_model->commit();
                return true;
            }
        }

        /**
         * 验证用户第一次有没有收费
         * 修改时间  2015年7月16日11:08:08
         * 
         * @author ft
         * @param  array() $source
         */
        public function validateUserFirstWhetherCharge($source)
        {
            $serial_model = new \Common\Model\Erp\SerialNumber();
            $res = $serial_model->getOne($source , array('serial_id' => 'serial_id'));
            if (!$res)
                return false;
            return true;
        }

        /**
         * 返回该合同的费用类型
         * 修改时间 2015年7月16日15:49:28
         * 
         * @author ft
         * @param  array $fee_type_list
         * @param  array $contract_info
         * @param  boolean $res
         */
        public function returnContractFeeType($fee_type_list , $contract_info , $res)
        {
            $tenant_model = new \Common\Model\Erp\TenantContract();
            $parent_id = $tenant_model->getOne(array('contract_id' => $contract_info['contract_id']) , array('parent_id' => 'parent_id'));
            $tenant_fee = array();
            if (!$res && $parent_id['parent_id'] == 0)
            {
                foreach ($fee_type_list as $fee_type)
                {
                    if ($fee_type['type_name'] == '押金')
                    {
                        $fee_type['money'] = $contract_info['deposit'];
                        $tenant_fee[] = $fee_type;
                    }
                    if ($fee_type['type_name'] == '租金')
                    {
                        $fee_type['money'] = ($contract_info['rent'] * $contract_info['pay']);
                        $tenant_fee[] = $fee_type;
                    }
                }
                return $tenant_fee;
            }
            else
            {
                foreach ($fee_type_list as $fee_type)
                {
                    if ($fee_type['type_name'] == '租金')
                    {
                        $fee_type['money'] = ($contract_info['rent'] * $contract_info['pay']);
                        $tenant_fee[] = $fee_type;
                    }
                }
                return $tenant_fee;
            }
        }

        /**
         * 检查一次性费用是否已经收过费
         * 修改时间  2015年7月23日20:34:18
         * 
         * @author ft
         * @param string $source (serial_number表source)
         * @param int $source_id (serial_number表source_id)
         * @param array $disposable_fee (serial_detail表fee_type_id)
         * @param array $fee_data (该房间所有费用配置)
         */
        public function checkDisposableFee($source_id , $fee_data)
        {
            $tenant_model = new \Common\Model\Erp\TenantContract();
            $disposable_fee = array();
            foreach ($fee_data as $key => $val)
            {
                if ($val['payment_mode'] == 1 || $val['payment_mode'] == 6)
                {
                    $disposable_fee[] = $val['fee_type_id'];
                }
            }
            $tc_parent_id = $tenant_model->getOne(array('contract_id' => $source_id) , array('parent_id' => 'parent_id'));
            //如果是续租直接返回1和6的费用id
            if ($tc_parent_id['parent_id'] != 0)
                return $disposable_fee;
            if (count($disposable_fee) == 0)
                return array();
            $serial_model = new \Common\Model\Erp\SerialNumber();
            $sql = $serial_model->getSqlObject();
            $select = $sql->select(array('sn' => 'serial_number'));
            $select->columns(array());
            $select->leftjoin(array('sd' => 'serial_detail') , 'sn.serial_id = sd.serial_id' , array('fee_type_id' => 'fee_type_id'));
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('sn.source' , 'tenant_contract');
            $where->equalTo('sn.source_id' , $source_id);
            $where->equalTo('sd.is_delete' , 0);
            $where->in('sd.fee_type_id' , $disposable_fee);
            $select->where($where);
            $query_disposabel_fee = $select->execute();
            $list = getArrayValue($query_disposabel_fee , 'fee_type_id');
            return $list;
        }

        /**
         * 根据source和source_id查询分散式 和集中式的 费用配置
         * 修改时间  2015年7月28日15:20:29
         * 
         * @author ft
         * @param  array $room_info
         */
        public function getRoomFeeConfig($room_info)
        {
            //获取房间费用配置
            if ($room_info['house_type'] == 1)
            {//分散式(整/合)
                $fee_source = ($room_info['house_id'] > 0) ? 'SOURCE_DISPERSE' : '';
                $fee_source_id = ($fee_source == 'SOURCE_DISPERSE') ? $room_info['house_id'] : 0;
                $fee_data = $this->getRoomFee($fee_source , $fee_source_id);
            }
            else
            {//集中式
                $room_source = 'SOURCE_FOCUS';
                $room_source_id = $room_info['room_id'];
                //查询房间费用配置
                $fee_data = $this->getRoomFee($room_source , $room_source_id);
                if (!$fee_data)
                {//如果房间没有费用配置,就查房源
                    $room_focus_model = new \Common\Model\Erp\RoomFocus();
                    $where = array('room_focus_id' => $room_info['room_id']);
                    $flat = $room_focus_model->getOne($where , array('flat_id' => 'flat_id'));
                    $fee_source = 'SOURCE_FLAT';
                    $fee_source_id = $flat['flat_id'];
                    $fee_data = $this->getRoomFee($fee_source , $fee_source_id);
                }
            }
            return $fee_data;
        }

        /**
         * 查询房间费用类型和计费方式
         * 修改时间 2015年7月28日15:41:44
         * 
         * @author ft
         * @param  string $source
         * @param  int $source_id
         */
        public function getRoomFee($source , $source_id)
        {
            $fee_where = array('source' => $source , 'source_id' => $source_id , 'is_delete' => 0);
            $fee_columns = array(
                'fee_id' => 'fee_id' ,
                'fee_type_id' => 'fee_type_id' ,
                'payment_mode' => 'payment_mode' ,
                'money' => 'money' ,
            );
            $fee_model = new \Common\Model\Erp\Fee();
            $fee_data = $fee_model->getData($fee_where , $fee_columns , 0 , 0 , 'fee_type_id ASC');
            return $fee_data;
        }

        /**
         * 获取该城市下,所有房源,集中式, 业主合同id
         * 修改时间 2015年7月30日19:55:19
         * 
         * @author ft
         * @param  array $user
         * @param  int $company_id
         */
        public function getAllCityHouse($user)
        {
            //获取城市下的房源id
            $house_arr = $this->getHouseIdByCityId($user['city_id'] , $user['company_id']);
            $data['house_id'] = array_column($house_arr , 'house_id');
            //获取城市下的集中式房间
            $room_focus_arr = $this->getRoomFocusIdByCityId($user['city_id'] , $user['company_id']);
            $data['room_focus_id'] = array_column($room_focus_arr , 'rf_room_id');
            //获取该城市下的业主
            $lc_arr = $this->getlandlordContractByCityId($user['city_id'] , $user['company_id']);
            $data['lc_contract_id'] = array_column($lc_arr , 'contract_id');
            return $data;
        }
        
        /**
         * 首页流水统计权限
         * 修改时间 2015年8月25日19:47:06
         * 
         * author ft
         * @param  Object $in_select     sql select 对象
         * @param  Object $day_in_where  sql where对象
         * @param  int $flat_id          0 全部房间; -1 分散式房间; !=0 && != -1 公寓flat_id
         * @param  array $user           用户信息
         */
        public function authValidate($in_select, $day_in_where, $flat_id, $user) {
            $and = new \Zend\Db\Sql\Where();
            if ($flat_id == 0) {
                $in_select->leftjoin(array('h' => 'house') , new \Zend\Db\Sql\Predicate\Expression('sn.house_id > 0 and h.house_id = sn.house_id and sn.house_type=1') , array('custom_number'));
                $in_select->leftjoin(array('rf' => 'room_focus') , new \Zend\Db\Sql\Predicate\Expression('sn.room_id > 0 and rf.room_focus_id = sn.room_id and sn.house_type=2') , array('rf_custom_number' => 'custom_number'));
            }
            $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
            $tables = array();
            if ($flat_id === 0 || $flat_id === -1) {//选中分散式  or 全部房间时要加的权限
                if ($flat_id === -1) {
                    $in_select->leftjoin(array('h' => 'house') , new \Zend\Db\Sql\Predicate\Expression('sn.house_id > 0 and h.house_id = sn.house_id and sn.house_type=1') , array('custom_number'));
                }
                $houseJoin = function($table) use ($in_select,&$tables){
                    $tables[] = $table;
                    $join = new \Zend\Db\Sql\Predicate\Expression("h.house_id IS NOT NULL and `{$table}`.authenticatee_id=h.house_id");
                    return array($join,$in_select::JOIN_LEFT);
                };
                $permisions->VerifyDataCollectionsPermissionsModel($in_select,$houseJoin, $permisions::USER_AUTHENTICATOR, $user['user_id'],\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
            
                $or = new \Zend\Db\Sql\Where();
                $or->isNotNull("{$tables[0]}.authenticatee_id");
                $and->orPredicate($or);
            
                $or = new \Zend\Db\Sql\Where();
                $or->isNull("{$tables[0]}.authenticatee_id");
                $or->equalTo('sn.user_id', $user['user_id']);
                $or->equalTo('sn.house_id', 0);
                $or->equalTo('sn.room_id', 0);
                $and->orPredicate($or);
            }
            if ($flat_id !== -1 || $flat_id === 0) {//选中集中式 or 全部房间时要加的权限
                $flatJoin = function($table) use($in_select,&$tables1){
                    $tables1[] = $table;
                    $join = new \Zend\Db\Sql\Predicate\Expression("rf.flat_id IS NOT NULL and `{$table}`.authenticatee_id=rf.flat_id");
                    return array($join,$in_select::JOIN_LEFT,array('authenticatee_id2'=>'authenticatee_id'));
                };
                $permisions->VerifyDataCollectionsPermissionsModel($in_select,$flatJoin, $permisions::USER_AUTHENTICATOR, $user['user_id'],\Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
            
                $or = new \Zend\Db\Sql\Where();
                $or->isNotNull("{$tables1[0]}.authenticatee_id");
                $and->orPredicate($or);
            
                $or = new \Zend\Db\Sql\Where();
                $or->isNull("{$tables1[0]}.authenticatee_id");
                $or->equalTo('sn.user_id', $user['user_id']);
                $or->equalTo('sn.house_id', 0);
                $or->equalTo('sn.room_id', 0);
                $and->orPredicate($or);
            }
             
            $day_in_where->andPredicate($and);
            return array('select' => $in_select, 'where' => $day_in_where);
        }
        
        /**
         * 根据合同id 获取合同的相关费用
         * 修改时间 2015年9月6日18:28:16
         * 
         * @author ft
         * @param $contract_id 合同id
         */
        public function getContractFeeByid($contract_id, $house_type, $house_id, $room_id, $company_id) {
            $fee_type_model = new \Common\Model\Erp\FeeType();
            $rental_helper = new \Common\Helper\Erp\Rental();
            $fee_model = new \Common\Model\Erp\Fee();
            //所有费用类型
            $fee_type_list = $fee_type_model->getData(array('company_id' => $company_id, 'is_delete' => 0));
            //通过合同id获取房间房源信息
            $rental_info = $rental_helper->getRentInfoById($contract_id);
            //集中/分散式房间的 地址信息
            $rents_room_info = $rental_helper->getRoomName($rental_info[0]);
            //检查第一次有没有收费
            $serial_helper = new \Common\Helper\Erp\SerialNumber();
            $source = array('source' => 'tenant_contract', 'source_id' => $contract_id);
            $res = $serial_helper->validateUserFirstWhetherCharge($source);
            //合同费用类型
            $tenant_fee = $serial_helper->returnContractFeeType($fee_type_list, $rental_info[0], $res);
            //获取房间费用配置
            $fee_data = $serial_helper->getRoomFeeConfig($rental_info[0]);
            //如果这个房间有费用配置
            if ($fee_data) {
                //取出费用类型id
                foreach ($fee_data as $fee_info) {
                    $ftype_id[] = $fee_info['fee_type_id'];//该房间的费用类型id
                }
                $ftype_where = array('fee_type_id' => $ftype_id, 'company_id' => $company_id);
                //该房间的费用类型
                $room_ftype_data = $fee_type_model->getFeeTypeById($ftype_where);
                //过滤一次性收费
                $fee_id = $serial_helper->checkDisposableFee($contract_id, $fee_data);
                foreach ($fee_data as $key => $fee_info) {
                    if ($fee_info['payment_mode'] == 1) {
                        //1 按房间一次性收费
                        $fee_data[$key]['money'] = $fee_info['money'];
                    }
                    elseif ($fee_info['payment_mode'] == 2) {
                        //2 按房间每月收费
                        $fee_data[$key]['money'] = $fee_info['money'] * $rental_info[0]['pay'];
                    }
                    elseif ($fee_info['payment_mode'] == 6) {
                        //6 按人头一次性收费
                        $contract_rental_model = new \Common\Model\Erp\ContractRental();
                        $cr_where = array('contract_id' => $contract_id, 'is_delete' => 0);
                        $cr_columns = array('total_pople' => new Expression('count(contract_rental_id)'));
                        $total_pople = $contract_rental_model->getOne($cr_where, $cr_columns);
                        $fee_data[$key]['money'] = $fee_info['money'] * $total_pople['total_pople'];
                    }
                    elseif ($fee_info['payment_mode'] == 7) {
                        //7 按人头每月收费
                        $contract_rental_model = new \Common\Model\Erp\ContractRental();
                        $cr_where = array('contract_id' => $contract_id, 'is_delete' => 0);
                        $cr_columns = array('total_pople' => new Expression('count(contract_rental_id)'));
                        $total_pople = $contract_rental_model->getOne($cr_where, $cr_columns);
                        $fee_data[$key]['money'] = $fee_info['money'] * $rental_info[0]['pay'] * $total_pople['total_pople'];
                    }
                }
                //让fee表查出的费用类型, 与fee_type表里的费用类型顺序相对
                foreach ($fee_data as $key => $fee) {
                    foreach ($room_ftype_data as $ftype) {
                        if ($fee['fee_type_id'] == $ftype['fee_type_id']) {
                            $fee_data[$key]['type_name'] = $ftype['type_name'];
                        }
                    }
                    if (in_array($fee['fee_type_id'], $fee_id)) {
                        unset($fee_data[$key]);
                    }
                }
            }
            unset($room_ftype_data);
            //获取抄表的费用类型金额
            $meter_reading_helper = new \Common\Helper\Erp\MeterReading();
            $meter_money_data = $meter_reading_helper->getMeterMoneyByRoomId($house_id, $house_type, $room_id);
            $extract_money = 0;
            if ($meter_money_data) {
                $meter_id = array();
                $meter_arr = array();
                foreach ($meter_money_data as $key => $val) {
                    if ($val['money'] != 0) {
                        if ($house_type == 1) {//分散
                            if ($rental_info[0]['house_id'] > 0 && $rental_info[0]['room_id'] < 0) {//整
                                $edit_meter[] = array('meter_id' => $val['meter_id'], 'house_id' => $rental_info[0]['house_id'], 'house_type' => $house_type);
                            } else {//合
                                $edit_meter[] = array('meter_id' => $val['meter_id'], 'room_id' => $rental_info[0]['room_id'], 'house_type' => $house_type);
                            }
                        } else {//集中
                            $edit_meter[] = array('meter_id' => $val['meter_id'], 'room_id' => $rental_info[0]['room_id'], 'house_type' => $house_type);
                        }
                        $meter_arr[$val['fee_type_id']]['money'] += $val['money'];
                        $meter_arr[$val['fee_type_id']]['fee_type_id'] = $val['fee_type_id'];
                        $meter_arr[$val['fee_type_id']]['type_name'] = $val['type_name'];
                        //提出抄表金额
                        $extract_money += $val['money'];
                    } else {
                        unset($meter_money_data[$key]);
                    }
                }
                if ($meter_arr) {
                    $_SESSION['edit_meter'] = $edit_meter;
                    foreach ($fee_data as $key => $fee) {
                        foreach ($meter_arr as $meter_val) {
                            if (!in_array($fee['fee_type_id'], $meter_val) && ($fee['payment_mode'] == 3 || $fee['payment_mode'] == 4 || $fee['payment_mode'] == 5)) {
                                unset($fee_data[$key]);
                            }
                        }
                    }
                } else {
                    foreach ($fee_data as $key => $v) {
                        if ($v['payment_mode'] == 3 || $v['payment_mode'] == 4 || $v['payment_mode'] == 5) unset($fee_data[$key]);
                    }
                }
            
                foreach ($fee_data as $key => $val) {
                    $fee_arr[$val['fee_type_id']]['money'] = $val['money'];
                    $fee_arr[$val['fee_type_id']]['fee_type_id'] = $val['fee_type_id'];
                    $fee_arr[$val['fee_type_id']]['type_name'] = $val['type_name'];
                }
                if ($meter_arr && $fee_arr) {//有抄表,也有房间配置
                    foreach ($fee_arr as $key => $fee) {
                        if (!in_array($fee['fee_type_id'], $meter_arr[$key])) {
                            $meter_arr[] = $fee;
                        }
                    }
                    $fee_type_arr = array_merge($tenant_fee, $meter_arr);
                } else {
                    $fee_type_arr = array_merge($tenant_fee, $fee_data);
                }
            } else {
                foreach ($fee_data as $key => $v) {
                    if ($v['payment_mode'] == 3 || $v['payment_mode'] == 4 || $v['payment_mode'] == 5) unset($fee_data[$key]);
                }
                $fee_type_arr = array_merge($tenant_fee, $fee_data);
            }
            $data['rental_info'] = $rental_info[0];
            $data['fee_type_arr'] = $fee_type_arr;
            $data['rents_room_info'] = $rents_room_info[0];
            $data['extract_money'] = $extract_money;
            return $data;
        }
    }