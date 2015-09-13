<?php

    namespace Common\Model\Erp;

    use Zend\Db\Sql\Expression;
    use Core\Db\Sql\Select;

    class SerialNumber extends \Common\Model\Erp
    {

        const SERIA_SOURCE_RENTAL = 'rental';
        const SERIAL_SOURCE_HOUSE = 'house';
        const SERIAL_SOURCE_ROOM = 'room';
        //集中式source
        const SERIA_SOURCE_FOCUS_RENTAL = 'focus_rental';
        const SERIA_SOURCE_FOCUS_HOUSE = 'focus_house';
        const SERIA_SOURCE_FOCUS_ROOM = 'focus_room';
        const TYPE_PAY = 2;//支出
        const TYPE_INCOME = 1;//收入

        public static $source = array(
            self::SERIA_SOURCE_RENTAL => "分散式已租source" ,
            self::SERIAL_SOURCE_HOUSE => "分散式整租source" ,
            self::SERIAL_SOURCE_ROOM => "分散式合租source" ,
            self::SERIA_SOURCE_FOCUS_RENTAL => "集中式已租source" ,
            self::SERIA_SOURCE_FOCUS_HOUSE => "集中式整租source" ,
            self::SERIA_SOURCE_FOCUS_ROOM => "集中式合租source" ,
        );

        /**
         * 集中式缘
         * @var unknown
         */
        public static $focus_source = array(
            self::SERIA_SOURCE_FOCUS_RENTAL ,
            self::SERIA_SOURCE_FOCUS_HOUSE ,
            self::SERIA_SOURCE_FOCUS_ROOM
        );

        /**
         * 分散式缘
         * @var unknown
         */
        public static $disperse_source = array(
            self::SERIA_SOURCE_RENTAL ,
            self::SERIAL_SOURCE_HOUSE ,
            self::SERIAL_SOURCE_ROOM
        );

        /**
         * 获取当前公司id下的所有的收入
         * 修改时间 2015年5月12日13:54:12
         * 
         * @author ft
         */
        public function getCompanyTotalIncome($user , $cid)
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

            if (!$id_data['house_id'] && !$id_data['room_focus_id'] && !$id_data['lc_contract_id'])
            {
                return array();
            }
            $sn = M('SerialNumber');

            $select = $sn->getSqlObject()->select();
            $select->leftjoin(array('h' => 'house'), new Expression('sn.house_id > 0 AND h.house_id = sn.house_id AND sn.house_type = 1'), array());
            $select->leftjoin(array('rf' => 'room_focus'), new Expression('sn.room_id > 0 AND rf.room_focus_id = sn.room_id AND sn.house_type = 2'), array());
            $where = new \Zend\Db\Sql\Where();

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
            $where->equalTo('sn.company_id' , $cid);
            $where->equalTo('sn.type' , 1);
            $where->equalTo('sn.is_delete' , 0);
            $select->from(array('sn' => 'serial_number'))->columns(array(getExpSql('sum(sn.money) as money')));
            /**
             * 权限
             */
            //$user = $this->getUserInfo();
//             if (!$user['is_manager'])
//             {
//                 $permissions = \Common\Helper\Permissions::Factory('sys_housing_management');
//                 $permisionsTable = $permissions->VerifyDataCollectionsPermissions($permissions::USER_AUTHENTICATOR , $user['user_id'] , 0);
//                 $join = new \Zend\Db\Sql\Predicate\Expression('(sn.house_id=pa.authenticatee_id and sn.house_id>0 and pa.source=1) or (sn.room_id=pa.authenticatee_id and sn.room_id>0  and pa.source=2)');
//                 $select->join(array('pa' => new \Zend\Db\Sql\Predicate\Expression($permisionsTable)) , $join , 'authenticatee_id' , $select::JOIN_LEFT);
//                 $authWhere = new \Zend\Db\Sql\Where();
//                 $authWhere2 = clone $authWhere;
//                 $authWhere->isNotNull('pa.authenticatee_id');
//                 $authWhere2->isNull('pa.authenticatee_id');
//                 $authWhere2->equalTo('sn.user_id' , $user['user_id']);
//                 $authWhere->orPredicate($authWhere2);
//                 $where->addPredicate($authWhere);
//             }
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
                    return array($join,$select::JOIN_LEFT);
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
            $select->where($where);
            $shouru = $select->execute();

            $select = $sn->getSqlObject()->select();
            $select->leftjoin(array('h' => 'house'), new Expression('sn.house_id > 0 AND h.house_id = sn.house_id AND sn.house_type = 1'), array());
            $select->leftjoin(array('rf' => 'room_focus'), new Expression('sn.room_id > 0 AND rf.room_focus_id = sn.room_id AND sn.house_type = 2'), array());
            $where = new \Zend\Db\Sql\Where();

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

            $where->equalTo('sn.company_id' , $cid);
            $where->equalTo('sn.type' , 2);
            $where->equalTo('sn.is_delete' , 0);
            $select->from(array('sn' => 'serial_number'))->columns(array(getExpSql('sum(sn.money) as money')));
            /**
             * 权限
             */
//             if (!$user['is_manager'])
//             {
//                 $permissions = \Common\Helper\Permissions::Factory('sys_housing_management');
//                 $permisionsTable = $permissions->VerifyDataCollectionsPermissions($permissions::USER_AUTHENTICATOR , $user['user_id'] , 0);
//                 $join = new \Zend\Db\Sql\Predicate\Expression('(sn.house_id=pa.authenticatee_id and sn.house_id>0 and pa.source=1) or (sn.room_id=pa.authenticatee_id and sn.room_id>0  and pa.source=2)');
//                 $select->join(array('pa' => new \Zend\Db\Sql\Predicate\Expression($permisionsTable)) , $join , 'authenticatee_id' , $select::JOIN_LEFT);
//                 $authWhere = new \Zend\Db\Sql\Where();
//                 $authWhere2 = clone $authWhere;
//                 $authWhere->isNotNull('pa.authenticatee_id');
//                 $authWhere2->isNull('pa.authenticatee_id');
//                 $authWhere2->equalTo('sn.user_id' , $user['user_id']);
//                 $authWhere->orPredicate($authWhere2);
//                 $where->addPredicate($authWhere);
//             }
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
                    return array($join,$select::JOIN_LEFT);
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
            $select->where($where);
            $zhichu = $select->execute();

            $shouru = is_numeric($shouru['0']['money']) ? $shouru['0']['money'] : 0;
            $zhichu = is_numeric($zhichu['0']['money']) ? $zhichu['0']['money'] : 0;
            return array($shouru , $zhichu);
            //return array_merge($income , $expense);
        }

        /**
         * 获取当前公司id下的所有的支出
         * 修改时间 2015年5月12日14:07:55
         * 
         * @author ft
         */
        public function getCompanyTotalExpense()
        {
            $sql = $this->getSqlObject();
            $select = $sql->select(array('sn' => 'serial_number'));
            $select->columns(array('total_receivable' => new Expression('SUM(sn.receivable)')));
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('sn.company_id' , $cid);
            $where->equalTo('sn.type' , 2);
            $select->where($where);
            return $select->execute();
        }

        /**
         * 获取该房间的欠费清单数据
         * 修改时间 2015-05-08 14:28:03
         * 
         * @author ft
         */
        public function getRoomArrears($serial_id)
        {
            $sql = $this->getSqlObject();
            $select = $sql->select(array('sn' => 'serial_number'));
            $select->columns(array('subscribe_pay_time' => 'subscribe_pay_time'));
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('sn.serial_id' , ($serial_id + 1));
            $select->where($where);
            return $select->execute();
        }

        /**
         * 获取租客合同流水
         * 修改时间2015年3月20日 14:26:05
         * 
         * @author yzx
         * @param int $rentalId
         * @param string $source
         * @return array|boolean
         */
        public function getContractSerial($rentalId , $source)
        {
            $select = $this->_sql_object->select(array("sn" => "serial_number"))
                    ->leftjoin(array("sd" => "serial_detail") , "sn.serial_id = sd.serial_id" , "*")
                    ->where(array("source_id" => $rentalId , "source" => $source));
            $result = $select->execute();
            if (!empty($result))
            {
                return $result;
            }
            return false;
        }

        /**
         * 获取单条财务数据
         * 修改时间2015年3月19日 09:51:14
         * 
         * @author yzx
         * @param int $serial_id
         * @return array|boolean
         */
        public function detail($serial_id)
        {
            $select = $this->_sql_object->select(array("sn" => "serial_number"))
                    ->leftjoin(array("sd" => "serial_detail") , "sn.serial_id = sd.serial_id" , "*")
                    ->leftjoin(array("r" => 'rental') , "sn.serial_id = r.rental_id" , array("source"))
                    ->leftjoin(array("t" => "tenant") , "r.tenant_id = t.tenant_id" , array("name" , "phone"))
                    ->where(array("sn.source_id" => $serial_id));
            $result = $select->execute();
            if (!empty($result))
            {
                return $result;
            }
            return false;
        }

        /**
         * 添加流水
         * 修改时间2015年3月19日 13:02:26
         * 
         * @author ft
         * @param array $data
         * @return boolean
         */
        public function addSeriaNumber($data , $arrear_date = null)
        {
            $houseModel = new \Common\Helper\Erp\House();
            $serialDetailModel = new SerialDetail();
            $strike_model = new \Common\Model\Erp\SerialStrikeBalance();
            $serial_sql = $this->getSqlObject();
            $serial_select = $serial_sql->select(array('sn' => 'serial_number'));
            $serial_select->columns(array('status' => 'status'));
            $serial_where = new \Zend\Db\Sql\Where();
            $serial_where->equalTo('sn.serial_id' , $data['serial_id']);
            $serial_select->where($serial_where);
            $check_info = $serial_select->execute();
            $current_serial_status = $check_info[0]['status'];
            $this->Transaction();
            //修改流水
            if (isset($data['serial_id']))
            {
                $rental_data = array();
                $serial_detail_result = array();
                //删除欠费清单(差额减免)
                if ($data['status'] == 1 && $current_serial_status == 2)
                {
                    $arrear_where = array('father_id' => $data['serial_id']);
                    $arrear_data = array('is_delete' => 1);
                    $arrear_edit_res = $this->edit($arrear_where , $arrear_data);
                    if (!$arrear_edit_res)
                    {
                        $this->rollback();
                        return false;
                    }
                }
                //应收等于已收
                if ($data['receivable'] == $data['money']) {
                    $data['status'] = 0;
                    $arrear_where = array('father_id' => $data['serial_id']);
                    $arrear_data = array('is_delete' => 1);
                    $arrear_edit_res = $this->edit($arrear_where , $arrear_data);
                    $serial_where = array('serial_id' => $data['serial_id']);
                    $serial_data = array('status' => $data['status']);
                    $serial_res = $this->edit($serial_where, $serial_data);
                    if (!$arrear_edit_res || !$serial_res)
                    {
                        $this->rollback();
                        return false;
                    }
                }
                if (isset($data['house_id']) && isset($data['house_type']))
                {
                    if (is_array($data['detail']) && !empty($data['detail']))
                    {
                        $name = '';
                        foreach ($data['detail'] as $fkey => $fval)
                        {
                            $name.=$fval['type_name'] . "、";
                        }
                        $name . "流水号" . $data['serial_number'];
                        $name = substr($name , 0 , strlen($name) - 3);//死写法
                        $data['serial_name'] = $name;
                    }
                }
                //分散式和集中式区分
                $data['house_type'] = ($data['house_type'] == 'room') ? 1 : (($data['house_type'] == 'house') ? 2 : 0);
                $data['create_time'] = time();
                $serial_id = array_shift($data); //弹出流水id
                $strike_where = array();
                $strike_data = array();
                foreach ($data['detail'] as $detail)
                {
//                     $strike_where[] = array('serial_id' => $detail['serial_id'], 'serial_detail_id' => $detail['detail_id']);
//                     $strike_data[] = array('money' => $detail['new_record']);
                    if ($detail['new_record'] != '')
                    {//每次修改修改冲账后相减
                        $total_final += $detail['new_record'];
                    }
                    $strike_model->edit(array('serial_id' => $detail['serial_id'] , 'serial_detail_id' => $detail['detail_id']) , array('money' => $detail['new_record']));
                }
                $data['final_money'] = $data['money'] - $total_final;
                $serial_where = array('serial_id' => $serial_id);
                $edit_serial_number_res = $this->edit($serial_where , $data);
                if (!$edit_serial_number_res)
                {
                    $this->rollback();
                    return false;
                }
                //修改欠费清单
                if ($data['status'] == 2 && $current_serial_status == 2)
                {
                    $arrear_where = array('father_id' => $serial_id);
                    //$data['pay_time'] = $arrear_date;
                    $data['father_id'] = $serial_id;    //father_id就是上一条流水id
                    $data['receivable'] = $data['receivable'] - $data['money'];
                    $data['money'] = 0;
                    $data['final_money'] = 0;
                    $arrear_res = $this->edit($arrear_where , $data);
                    if (!$arrear_res)
                    {
                        $this->rollback();
                        return false;
                    }
                }
                //新增欠费清单
                if ($data['status'] == 2 && $current_serial_status == 1)
                {
                    //$arrear_where = array('serial_id' => $serial_id + 1);
                    //$data['pay_time'] = $arrear_date;
                    $data['father_id'] = $serial_id;    //father_id就是上一条流水id
                    $data['receivable'] = $data['receivable'] - $data['money'];
                    $data['money'] = 0;
                    $data['final_money'] = 0;
                    $arrear_id = $this->insert($data);
                    if (!$arrear_id)
                    {
                        $this->rollback();
                        return false;
                    }
                }
                //新增欠费清单的第二种情况
                if ($data['status'] == 2 && $current_serial_status == 0)
                {
                    //$arrear_where = array('serial_id' => $serial_id + 1);
                    //$data['pay_time'] = $arrear_date;
                    $data['father_id'] = $serial_id;    //father_id就是上一条流水id
                    $data['receivable'] = $data['receivable'] - $data['money'];
                    $data['money'] = 0;
                    $data['final_money'] = 0;
                    $arrear_id = $this->insert($data);
                    if (!$arrear_id)
                    {
                        $this->rollback();
                        return false;
                    }
                }
                if (is_array($data['detail']) && !empty($data['detail']))
                {
                    $serial_detail_data = array();
                    foreach ($data['detail'] as $fkey => $fval)
                    {
                        if ($fval['serial_id'] != '')
                        {
                            $detail_where['serial_id'] = $serial_id;
                            $detail_where['fee_type_id'] = $fval['fee_type_id'];
                            $serial_detail_data['money'] = $fval['money'];
                            if ($fval['new_record'] != '')
                            {//每次修改冲账金额就会相减
                                $serial_detail_data['final_money'] = $fval['money'] - $fval['new_record'];
                            }
                            else
                            {
                                $serial_detail_data['final_money'] = $fval['money'];
                            }
                            $serial_detail_result[] = $serialDetailModel->editSerialDetailById($detail_where , $serial_detail_data);
                        }
                        else
                        {
                            $serial_detail_data['serial_id'] = $serial_id;
                            $serial_detail_data['fee_type_id'] = $fval['fee_type_id'];
                            $serial_detail_data['money'] = $fval['money'];
                            $serial_detail_data['final_money'] = $fval['money'];
                            $serial_detail_result[] = $serialDetailModel->addSerialDetail($serial_detail_data);
                        }
                    }
                }
                if (empty($serial_detail_result))
                {
                    $this->rollback();
                    return false;
                }
                $this->commit();
                return true;
            }
            else
            {
                //新增流水
                $rental_data = array();
                $serial_detail_result = array();
                $source = array_pop($data);
                if (isset($data['house_id']) && isset($data['house_type']))
                {
                    if (is_array($data['detail']) && !empty($data['detail']))
                    {
                        $name = '';
                        foreach ($data['detail'] as $fkey => $fval)
                        {
                            $name.=$fval['type_name'] . "、";
                        }
                        $name . "流水号" . $data['serial_number'];
                        $name = substr($name , 0 , strlen($name) - 3);//死写法
                        //$name = substr($name, 0, (strlen($name) - strlen(substr($name, strrpos($name, '、')))));
                        $data['serial_name'] = $name;
                    }
                    //新增收入source
                    if ($source)
                    {
                        $data['source_id'] = $source['source_id'];
                        $data['source'] = $source['source'];
                    }
                    else
                    {
                        $data['source_id'] = 0;
                        $data['source'] = 0;
                    }
                }
                //弹出not_room_serial属性,判断是否是非房间流水
                $not_room_serial = array_shift($data);
                if ($not_room_serial != 1)
                {
                    $data['house_type'] = ($data['house_type'] == 'room') ? 1 : (($data['house_type'] == 'house') ? 2 : 0);
                }
                $data['create_time'] = time();
                $this->Transaction();
                $serial_number_id = $this->insert($data);
                //最新SQL打印
                //echo $this->getLastSql();exit;
                if (!$serial_number_id)
                {
                    $this->rollback();
                    return false;
                }
                if ($arrear_date)
                {
                    $data['subscribe_pay_time'] = $arrear_date;
                    $data['father_id'] = $serial_number_id;
                    $data['receivable'] = $data['receivable'] - $data['money'];
                    $data['money'] = 0;
                    $data['final_money'] = 0;
                    $arrear_id = $this->insert($data);
                    if (!$arrear_id)
                    {
                        $this->rollback();
                        return false;
                    }
                    if (!$arrear_id)
                    {
                        $this->rollback();
                        return false;
                    }
                }
                if (is_array($data['detail']) && !empty($data['detail']))
                {
                    $serial_detail_data = array();
                    foreach ($data['detail'] as $fkey => $fval)
                    {
                        $serial_detail_data['serial_id'] = $serial_number_id;
                        $serial_detail_data['fee_type_id'] = $fval['fee_type_id'];
                        $serial_detail_data['money'] = $fval['money'];
                        $serial_detail_data['final_money'] = $fval['money'];
                        $serial_detail_result[] = $serialDetailModel->addSerialDetail($serial_detail_data);
                    }
                }
                if (empty($serial_detail_result))
                {
                    $this->rollback();
                    return false;
                }
                $this->commit();
                return $serial_number_id;
            }
        }

        /**
         * 编辑流水
         * 修改时间2015年3月19日 14:59:59
         * 
         * @author yzx
         * @param array $data
         * @param int $serialId
         * @return boolean
         */
        public function editSeriaNumber($data , $serialId)
        {
            $houseModel = new House();
            $serialDetailModel = new SerialDetail();
            $rental_data = array();
            $serial_detail_result = array();
            //修改流水
            if (isset($data['house_id']) && isset($data['house_type']))
            {
                $rental_data = $houseModel->getHoueRental($data['house_id'] , $data['house_id']);
                if (is_array($data['detail']) && !empty($data['detail']))
                {
                    $name = '';
                    foreach ($data['detail'] as $fkey => $fval)
                    {
                        $name.=$fval['type_name'] . "、";
                    }
                    $name . "流水号" . $data['serial_number'];
                    $data['serial_name'] = $name;
                }
                if (!empty($rental_data))
                {
                    $data['source_id'] = $rental_data['rental_id'];
                    $data['source'] = self::SERIA_SOURCE_RENTAL;
                }
            }
            $this->Transaction();
            $serial_result = $this->edit(array("serial_id" => $serialId) , $data);
            if (!$serial_result)
            {
                $this->rollback();
                return false;
            }
            //检查是否删除详情
            $this->checkDeleteDetail($data['detail'] , $serialId);
            //修改详情
            if (is_array($data['detail']) && !empty($data['detail']))
            {
                foreach ($data['detail'] as $fkey => $fval)
                {
                    $serial_detail_result['fee_type_id'] = $fval['fee_type_id'];
                    $serial_detail_result['money'] = $fval['money'];
                    $serial_detail = $serialDetailModel->editSerialDetail($serial_detail_result , $fval['serial_detail_id']);
                    if (!$serial_detail)
                    {
                        $this->rollback();
                    }
                }
                $this->commit();
                return true;
            }
            return false;
        }

        /**
         * 获取编辑数据
         * 修改时间2015年3月19日 16:42:26
         * 
         * @author yzx
         * @param int $serialId
         * @return boolean
         */
        public function editData($serialId)
        {
            $detailModel = new SerialDetail();
            $serial_number_data = $this->getOne(array("serial_id" => $serialId));
            if (empty($serial_number_data))
            {
                return false;
            }
            $detail_data = $detailModel->getSerialDetail($serialId);
            $house_data = $this->getHouseData($serial_number_data);
            return array("data" => $serial_number_data , "detail" => $detail_data , "house_data" => $house_data);
        }

        /**
         * 获取房源信息
         * 修改时间2015年3月19日 16:42:55
         * 
         * @author yzx
         * @param array $serialNumberData
         * @return array
         */
        private function getHouseData($serialNumberData)
        {
            $houseModel = new House();
            $roomModel = new Room();
            $rentalModel = new Rental();
            $flatModel = new Flat();
            $house_data = array();
            switch ($serialNumberData['source'])
            {
                //分散式房源
                case self::SERIAL_SOURCE_HOUSE:
                    $house_data = $houseModel->getOne(array("house_id" => $serialNumberData['source_id']));
                    break;
                //分散式房间
                case self::SERIAL_SOURCE_ROOM:
                    $house_data = $roomModel->getRoomByHouse($serialNumberData['source_id']);
                    break;
                //分散式租客房间
                case self::SERIA_SOURCE_RENTAL:
                    $rental_data = $rentalModel->getHouseByRental($serialNumberData['source_id']);
                    if ($rental_data['house_id'] > 0)
                    {
                        $house_data = $houseModel->getOne(array("house_id" => $serialNumberData['source_id']));
                    }
                    elseif ($rental_data['room_id'] > 0)
                    {
                        $house_data = $roomModel->getRoomByHouse($serialNumberData['source_id']);
                    }
                    break;
                //集中式房间
                case self::SERIA_SOURCE_FOCUS_HOUSE:
                    $house_data = $flatModel->getFlatData($serialNumberData['source_id']);
                    break;
                //集中式租客房间
                case self::SERIA_SOURCE_FOCUS_RENTAL:
                    $rental_data = $rentalModel->getHouseByRental($serialNumberData['source_id']);
                    $house_data = $flatModel->getFlatData($rental_data['source_id']);
                    break;
                default:
                    $house_data = array();
                    break;
            }
            return $house_data;
        }

        /**
         * 删除流水详情
         * 修改时间2015年3月19日 15:19:05
         * 
         * @author yzx
         * @param array $detail
         * @param int $serial_id
         */
        private function checkDeleteDetail($detail , $serialId)
        {
            $serialDetailModel = new SerialDetail();
            $all_detail = $serialDetailModel->getSerialDetail($serialId);
            $detail_id = array();
            if (!empty($all_detail))
            {
                foreach ($detail as $key => $val)
                {
                    $detail_id[] = $val['serial_detail_id'];
                }
            }
            if (!empty($all_detail))
            {
                foreach ($all_detail as $akey => $akey)
                {
                    if (!in_array($akey['serial_detail_id'] , $detail_id))
                    {
                        $serialDetailModel->delete(array("serial_detail_id" => $akey['serial_detail_id']));
                    }
                }
            }
        }

        /**
         * 获取欠费清单,包含分页
         *  最后修改时间 2015-3-24
         *  
         * @author ft
         * @param unknown $page
         * @param unknown $pagesize
         * @return array()
         */
        public function getDebtsList($cid , $page , $size)
        {
            $sql = $this->getSqlObject();
            $select = $sql->select(array('sn' => 'serial_number'));
            $select->columns(
            array(
            'sn_id' => 'serial_id' ,
            'pay_time' => 'pay_time' ,
            'subscribe_pay_time' => 'subscribe_pay_time' ,
            'house_type' => 'house_type' ,
            'house_id' => 'house_id' ,
            'receivable' => 'receivable' ,
            'final_money' ,
            'status' => 'status' ,
            'room_id' => 'room_id',
            'father_id' => 'father_id' ,
            'source' => 'source' ,
            'source_id' => 'source_id' ,
            'user_id' => 'user_id' ,
            ));

            $select->leftjoin(
                    array('h' => 'house') , new \Zend\Db\Sql\Predicate\Expression('(sn.house_type = 1 and h.house_id = sn.house_id)') , array(
                'h_name' => 'house_name' ,
                'h_custom_num' => 'custom_number' ,
                'h_cost' => 'cost' ,
                'h_unit' => 'unit' ,
                'h_number' => 'number' ,
                'rental_way' ,
                'h_floor' => 'floor' ,
            ));

            $select->leftjoin(array('r' => 'room') , 'h.house_id = r.house_id' , array('r_custom_num' => 'custom_number' , 'room_type' => 'room_type'));
            $select->leftjoin(
                    array('rf' => 'room_focus') , new Expression('(sn.house_type = 2 and rf.room_focus_id = sn.room_id)') , array('room_focus_id' => 'room_focus_id' , 'floor' => 'floor' , 'rf_custom_num' => 'custom_number')
            );
            $select->leftjoin(array('f' => 'flat') , 'rf.flat_id = f.flat_id' , array('flat_id' => 'flat_id' , 'flat_name' => 'flat_name' , 'address' => 'address' , 'f_custom_num' => 'custom_number'));
            $where = new \Zend\Db\Sql\Where();
            $where->greaterThan('sn.father_id' , 0);
            $ht_where = new \Zend\Db\Sql\Where();
            $ht_where->equalTo('sn.house_type' , 2);
            $ht_where2 = new \Zend\Db\Sql\Where();
            $ht_where2->equalTo('sn.house_type' , 1);
            $ht_where->addPredicate($ht_where2 , $ht_where::OP_OR);
            $ht_where3 = new \Zend\Db\Sql\Where();
            $ht_where3->equalTo('sn.house_type' , 0);
            $ht_where2->addPredicate($ht_where3 , $ht_where2::OP_OR);
            $where->addPredicate($ht_where , $where::OP_AND);
            $swhere3 = new \Zend\Db\Sql\Where();
            $swhere3->equalTo('sn.type' , 1);
            $swhere4 = new \Zend\Db\Sql\Where();
            $swhere4->equalTo('sn.type' , 2);
            $swhere3->addPredicate($swhere4 , $swhere3::OP_OR);
            $where->addPredicate($swhere3 , $where::OP_AND);
            $where->equalTo('sn.company_id' , $cid);
            $where->equalTo('sn.is_delete' , 0);
            $select->group('sn.serial_id');
            $select->order(array('sn.status desc' , 'sn.subscribe_pay_time asc'));
            $select->where($where);

            return $disperse = \Core\Db\Sql\Select::pageSelect($select , null , $page , $size);
        }

        /**
         * 获取欠费清单,包含分页
         *  最后修改时间 2015-3-24
         *  
         * @author ft
         * @param unknown $page
         * @param unknown $pagesize
         * @return array()
         */
        public function getDebtsListPc($cid , $page , $size , $user)
        {
            $sql = $this->getSqlObject();
            $select = $sql->select(array('sn' => 'serial_number'));
            $select->columns(
                    array(
                        'sn_id' => 'serial_id' ,
                        'pay_time' => 'pay_time' ,
                        'subscribe_pay_time' => 'subscribe_pay_time' ,
                        'house_type' => 'house_type' ,
                        'house_id' => 'house_id' ,
                        'room_id' => 'room_id' ,
                        'receivable' => 'receivable' ,
                        'status' => 'status' ,
                        'father_id' => 'father_id' ,
                        'source' => 'source' ,
                        'source_id' => 'source_id' ,
                        'user_id' => 'user_id' ,
            ));

            $select->leftjoin(
                    array('h' => 'house') , new \Zend\Db\Sql\Predicate\Expression('(sn.house_type = 1 and h.house_id = sn.house_id)') , array(
                'h_name' => 'house_name' ,
                'h_custom_num' => 'custom_number' ,
                'h_cost' => 'cost' ,
                'h_unit' => 'unit' ,
                'h_number' => 'number' ,
                'rental_way' ,
            ));
            $select->leftjoin(array('r' => 'room') , 'h.house_id = r.house_id' , array('r_custom_num' => 'custom_number' , 'room_type' => 'room_type'));
            $select->leftjoin(
                    array('rf' => 'room_focus') , new Expression('(sn.house_type = 2 and rf.room_focus_id = sn.room_id)') , array('room_focus_id' => 'room_focus_id' , 'floor' => 'floor' , 'rf_custom_num' => 'custom_number')
            );
            $select->leftjoin(array('f' => 'flat') , 'rf.flat_id = f.flat_id' , array('flat_id' => 'flat_id' , 'flat_name' => 'flat_name' , 'address' => 'address' , 'f_custom_num' => 'custom_number'));
            $where = new \Zend\Db\Sql\Where();
            $where->greaterThan('sn.father_id' , 0);
            $ht_where = new \Zend\Db\Sql\Where();
            $ht_where->equalTo('sn.house_type' , 2);
            $ht_where2 = new \Zend\Db\Sql\Where();
            $ht_where2->equalTo('sn.house_type' , 1);
            $ht_where->addPredicate($ht_where2 , $ht_where::OP_OR);
            $ht_where3 = new \Zend\Db\Sql\Where();
            $ht_where3->equalTo('sn.house_type' , 0);
            $ht_where2->addPredicate($ht_where3 , $ht_where2::OP_OR);
            $where->addPredicate($ht_where , $where::OP_AND);
            $swhere3 = new \Zend\Db\Sql\Where();
            $swhere3->equalTo('sn.type' , 1);
            $swhere4 = new \Zend\Db\Sql\Where();
            $swhere4->equalTo('sn.type' , 2);
            $swhere3->addPredicate($swhere4 , $swhere3::OP_OR);
            $where->addPredicate($swhere3 , $where::OP_AND);
            $where->equalTo('sn.company_id' , $cid);
            $where->equalTo('sn.is_delete' , 0);
            $select->group('sn.serial_id');
            $select->order('sn.serial_id desc');
            /**
             * 权限
             */
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
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
                $authWhere2->equalTo('sn.house_id', 0);
                $authWhere2->equalTo('sn.room_id', 0);
                $authWhere->orPredicate($authWhere2);
                $where->addPredicate($authWhere);
            }
            $select->where($where);
            return $disperse = \Core\Db\Sql\Select::pageSelect($select , null , $page , $size);
        }

        /**
         * 获取全部流水统计
         * @param type $user
         * @return array()
         *  @author Lms 2015年5月5日 15:25:10
         */
        public function getAllCount($user , $flat_id , $house_id = array() , $rf_room_id = array())
        {
            $serial_model = new \Common\Model\Erp\SerialNumber();
            $serial_number_helper = new \Common\Helper\Erp\SerialNumber();
            $city_model = new \Common\Model\Erp\City();
            $company_id = $user['company_id'];
            $city_id = $user['city_id'];
            //分散式sql
            $dis_sql = $this->getDisAndFocusSql($user , 1);
            $dis_arr = array(new Expression($dis_sql));
            //集中式sql
            $f_sql = $this->getDisAndFocusSql($user , 2);
            $f_arr = array(new Expression($f_sql));
            //业主sql
            $lc_sql = $this->getDisAndFocusSql($user , 3);
            $lc_arr = array(new Expression($lc_sql));
            //获取城市下的房源等id
            $id_data = $serial_number_helper->getAllCityHouse($user);

            //1 首页今日收入和今日支出
            $start = mktime(0 , 0 , 0 , date("m") , date("d") , date("Y"));//当天开始
            $end = mktime(0 , 0 , 0 , date('m') , date('d') + 1 , date('Y')) - 1;//当天结束
            //收入
            $income_sql = $serial_model->getSqlObject();
            $in_select = $income_sql->select(array('sn' => 'serial_number'));
            $in_select->columns(array('day_income' => new Expression('SUM(sn.money)')));
            //选中集中式
            if ($flat_id != 0 && $flat_id != -1)
            {
                $in_select->leftjoin(array('rf' => 'room_focus') , 'sn.room_id = rf.room_focus_id' , array());
                $in_select->leftjoin(array('f' => 'flat') , 'rf.flat_id = f.flat_id' , array());
            }
            $day_in_where = new \Zend\Db\Sql\Where();
            
            /**
             * 今日收入权限
             */
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $auth_arr = $serial_number_helper->authValidate($in_select, $day_in_where, $flat_id, $user);
                $day_in_where = $auth_arr['where'];
                $in_select = $auth_arr['select'];
            }
            $link_where = new \Zend\Db\Sql\Where();
            $day_in_where->greaterThanOrEqualTo('pay_time' , $start);
            $day_in_where->lessThanOrEqualTo('pay_time' , $end);
            //选中分散式
            if ($flat_id != 0 && $flat_id == -1)
            {
                $day_in_where->equalTo('house_type' , 1);

                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.house_id' , $dis_arr);
                $link_where->orPredicate($or_where);
                if ($id_data['lc_contract_id'])
                {
                    $lc_where = new \Zend\Db\Sql\Where();
                    $lc_where->equalTo('sn.source' , 'landlord_contract');
                    $lc_where->in('sn.source_id' , $lc_arr);
                    $link_where->orPredicate($lc_where);
                }
            }
            elseif ($flat_id != 0 && $flat_id != -1)
            {//选中集中式
                $day_in_where->equalTo('f.flat_id' , $flat_id);
                $day_in_where->equalTo('f.company_id' , $company_id);
                $day_in_where->equalTo('sn.house_type' , 2);

                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.room_id' , $f_arr);
                $link_where->orPredicate($or_where);
                if ($id_data['lc_contract_id'])
                {
                    $lc_where = new \Zend\Db\Sql\Where();
                    $lc_where->equalTo('sn.source' , 'landlord_contract');
                    $lc_where->in('sn.source_id' , $lc_arr);
                    $link_where->orPredicate($lc_where);
                }
            }
            $day_in_where->equalTo('sn.type' , 1);
            $day_in_where->equalTo('sn.is_delete' , 0);
            $day_in_where->equalTo('sn.company_id' , $company_id);
            if ($house_id && $flat_id == 0)
            {
                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.house_id' , $dis_arr);
                $link_where->orPredicate($or_where);
                if ($id_data['lc_contract_id'])
                {
                    $lc_where = new \Zend\Db\Sql\Where();
                    $lc_where->equalTo('sn.source' , 'landlord_contract');
                    $lc_where->in('sn.source_id' , $lc_arr);
                    $link_where->orPredicate($lc_where);
                }
            }
            if ($rf_room_id && $flat_id == 0)
            {
                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.room_id' , $f_arr);
                $link_where->orPredicate($or_where);
                if ($id_data['lc_contract_id'])
                {
                    $lc_where = new \Zend\Db\Sql\Where();
                    $lc_where->equalTo('sn.source' , 'landlord_contract');
                    $lc_where->in('sn.source_id' , $lc_arr);
                    $link_where->orPredicate($lc_where);
                }
            }
            $not_room = new \Zend\Db\Sql\Where();
            $not_room->equalTo('sn.house_type' , 0);
            $link_where->orPredicate($not_room);
            $day_in_where->addPredicate($link_where);
            $in_select->where($day_in_where);
            $day_income = $in_select->execute();

            //支出
            $ex_sql = $serial_model->getSqlObject();
            $ex_select = $ex_sql->select(array('sn' => 'serial_number'));
            $ex_select->columns(array('day_expense' => new Expression('SUM(sn.money)')));
            //选中集中式
            if ($flat_id != 0 && $flat_id != -1)
            {
                $ex_select->leftjoin(array('rf' => 'room_focus') , 'sn.room_id = rf.room_focus_id' , array());
                $ex_select->leftjoin(array('f' => 'flat') , 'rf.flat_id = f.flat_id' , array());
            }
            $day_ex_where = new \Zend\Db\Sql\Where();
            
            /**
             * 今日支出权限
             */
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $auth_arr = $serial_number_helper->authValidate($ex_select, $day_ex_where, $flat_id, $user);
                $day_in_where = $auth_arr['where'];
                $in_select = $auth_arr['select'];
            }
            
            $link_where = new \Zend\Db\Sql\Where();
            $day_ex_where->greaterThanOrEqualTo('pay_time' , $start);
            $day_ex_where->lessThanOrEqualTo('pay_time' , $end);
            //选中分散式
            if ($flat_id != 0 && $flat_id == -1)
            {
                $day_ex_where->equalTo('sn.house_type' , 1);

                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.house_id' , $dis_arr);
                $link_where->orPredicate($or_where);
                if ($id_data['lc_contract_id'])
                {
                    $lc_where = new \Zend\Db\Sql\Where();
                    $lc_where->equalTo('sn.source' , 'landlord_contract');
                    $lc_where->in('sn.source_id' , $lc_arr);
                    $link_where->orPredicate($lc_where);
                }
            }
            elseif ($flat_id != 0 && $flat_id != -1)
            {//选中集中式
                $day_ex_where->equalTo('f.flat_id' , $flat_id);
                $day_ex_where->equalTo('f.company_id' , $company_id);
                $day_ex_where->equalTo('sn.house_type' , 2);

                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.room_id' , $f_arr);
                $link_where->orPredicate($or_where);
                if ($id_data['lc_contract_id'])
                {
                    $lc_where = new \Zend\Db\Sql\Where();
                    $lc_where->equalTo('sn.source' , 'landlord_contract');
                    $lc_where->in('sn.source_id' , $lc_arr);
                    $link_where->orPredicate($lc_where);
                }
            }
            if ($house_id && $flat_id == 0)
            {
                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.house_id' , $dis_arr);
                $link_where->orPredicate($or_where);
                if ($id_data['lc_contract_id'])
                {
                    $lc_where = new \Zend\Db\Sql\Where();
                    $lc_where->equalTo('sn.source' , 'landlord_contract');
                    $lc_where->in('sn.source_id' , $lc_arr);
                    $link_where->orPredicate($lc_where);
                }
            }
            if ($rf_room_id && $flat_id == 0)
            {
                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.room_id' , $f_arr);
                $link_where->orPredicate($or_where);
                if ($id_data['lc_contract_id'])
                {
                    $lc_where = new \Zend\Db\Sql\Where();
                    $lc_where->equalTo('sn.source' , 'landlord_contract');
                    $lc_where->in('sn.source_id' , $lc_arr);
                    $link_where->orPredicate($lc_where);
                }
            }
            $not_room = new \Zend\Db\Sql\Where();
            $not_room->equalTo('sn.house_type' , 0);
            $link_where->orPredicate($not_room);
            $day_ex_where->addPredicate($link_where);
            $day_ex_where->equalTo('sn.type' , 2);
            $day_ex_where->equalTo('sn.is_delete' , 0);
            $day_ex_where->equalTo('sn.company_id' , $company_id);
            $ex_select->where($day_ex_where);
            $day_expense = $ex_select->execute();

            //2 线形图数据
            $sql = $this->getSqlObject();
            $select = $sql->select(array('sn' => 'serial_number'));
            $select->columns(array('pay_time' , 'type' , 'money'));
            if ($flat_id != 0 && $flat_id != -1)
            {
                $select->leftjoin(array('rf' => 'room_focus') , 'sn.room_id = rf.room_focus_id' , array());
                $select->leftjoin(array('f' => 'flat') , 'rf.flat_id = f.flat_id' , array());
            }
            $where = new \Zend\Db\Sql\Where();
            
            /**
             * 线性图数据权限
             */
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $auth_arr = $serial_number_helper->authValidate($select, $where, $flat_id, $user);
                $day_in_where = $auth_arr['where'];
                $in_select = $auth_arr['select'];
            }
            
            $link_where = new \Zend\Db\Sql\Where();
            $day_7 = strtotime(date('Y-m-d' , strtotime('-6 day')));
            $where->between('pay_time' , $day_7 , time());
            //选中分散式
            if ($flat_id != 0 && $flat_id == -1)
            {
                $where->equalTo('sn.house_type' , 1);

                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.house_id' , $dis_arr);
                $link_where->orPredicate($or_where);
                if ($id_data['lc_contract_id'])
                {
                    $lc_where = new \Zend\Db\Sql\Where();
                    $lc_where->equalTo('sn.source' , 'landlord_contract');
                    $lc_where->in('sn.source_id' , $lc_arr);
                    $link_where->orPredicate($lc_where);
                }
            }
            elseif ($flat_id != 0 && $flat_id != -1)
            {//集中式
                $where->equalTo('f.flat_id' , $flat_id);
                $where->equalTo('f.company_id' , $user['company_id']);
                $where->equalTo('sn.house_type' , 2);

                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.room_id' , $f_arr);
                $link_where->orPredicate($or_where);
                if ($id_data['lc_contract_id'])
                {
                    $lc_where = new \Zend\Db\Sql\Where();
                    $lc_where->equalTo('sn.source' , 'landlord_contract');
                    $lc_where->in('sn.source_id' , $lc_arr);
                    $link_where->orPredicate($lc_where);
                }
            }

            if ($house_id && $flat_id == 0)
            {
                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.house_id' , $dis_arr);
                $link_where->orPredicate($or_where);
                if ($id_data['lc_contract_id'])
                {
                    $lc_where = new \Zend\Db\Sql\Where();
                    $lc_where->equalTo('sn.source' , 'landlord_contract');
                    $lc_where->in('sn.source_id' , $lc_arr);
                    $link_where->orPredicate($lc_where);
                }
            }
            if ($rf_room_id && $flat_id == 0)
            {
                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.room_id' , $f_arr);
                $link_where->orPredicate($or_where);
                if ($id_data['lc_contract_id'])
                {
                    $lc_where = new \Zend\Db\Sql\Where();
                    $lc_where->equalTo('sn.source' , 'landlord_contract');
                    $lc_where->in('sn.source_id' , $lc_arr);
                    $link_where->orPredicate($lc_where);
                }
            }
            $not_room = new \Zend\Db\Sql\Where();
            $not_room->equalTo('sn.house_type' , 0);
            $link_where->orPredicate($not_room);
            $where->addPredicate($link_where);
            $where->equalTo('sn.is_delete' , 0);
            $where->equalTo('sn.company_id' , $user['company_id']);
            $select->order('sn.pay_time asc');
            $select->where($where);
            $list = $select->execute();
            $info = array();

            //初始化7天的数据(首页线性图表数据)
            for ($i = 0; $i < 7; $i++)
            {
                $info[date('md' , strtotime("+$i day" , $day_7))] = array('income' => 0 , 'pay' => 0);
            }
            foreach ($list as $val)
            {
                $date = date('md' , $val['pay_time']);
                $money = $val['money'];
                if ($val['type'] == '1')
                    $info[$date]['income']+= $money;
                else
                    $info[$date]['pay']+= $money;
            }
            $sql_obj = $this->getSqlObject();
            $S = $sql_obj->select();
            if (!$id_data['house_id'] && !$id_data['room_focus_id'] && !$id_data['lc_contract_id'])
            {
                return array('list' => $info ,
                    'all_income' => (float) 0 ,
                    'zaizhuzujin' => (float) 0 ,
                    'all_pay' => (float) 0 ,
                    'day_income' => (float) 0 ,
                    'day_expense' => (float) 0 ,
                );
            }
            //3 首页历史收入
            $his_in_sql = $serial_model->getSqlObject();
            $his_in_select = $his_in_sql->select(array('sn' => 'serial_number'));
            $his_in_select->columns(array('sum' => new Expression('sum(sn.money)')));
            if ($flat_id != 0 && $flat_id != -1)
            {
                $his_in_select->leftjoin(array('rf' => 'room_focus') , 'sn.room_id = rf.room_focus_id' , array());
                $his_in_select->leftjoin(array('f' => 'flat') , 'rf.flat_id = f.flat_id' , array());
            }
            $his_in_where = new \Zend\Db\Sql\Where();
            
            /**
             * 首页历史收入权限
             */
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $auth_arr = $serial_number_helper->authValidate($his_in_select, $his_in_where, $flat_id, $user);
                $day_in_where = $auth_arr['where'];
                $in_select = $auth_arr['select'];
            }
            
            $link_where = new \Zend\Db\Sql\Where();
            if ($flat_id != 0 && $flat_id == -1)
            {
                $his_in_where->equalTo('sn.house_type' , 1);

                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.house_id' , $dis_arr);
                $link_where->orPredicate($or_where);
                if ($id_data['lc_contract_id'])
                {
                    $lc_where = new \Zend\Db\Sql\Where();
                    $lc_where->equalTo('sn.source' , 'landlord_contract');
                    $lc_where->in('sn.source_id' , $lc_arr);
                    $link_where->orPredicate($lc_where);
                }
            }
            elseif ($flat_id != 0 && $flat_id != -1)
            {
                $his_in_where->equalTo('f.flat_id' , $flat_id);
                $his_in_where->equalTo('f.company_id' , $company_id);
                $his_in_where->equalTo('sn.house_type' , 2);

                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.room_id' , $f_arr);
                $link_where->orPredicate($or_where);
                if ($id_data['lc_contract_id'])
                {
                    $lc_where = new \Zend\Db\Sql\Where();
                    $lc_where->equalTo('sn.source' , 'landlord_contract');
                    $lc_where->in('sn.source_id' , $lc_arr);
                    $link_where->orPredicate($lc_where);
                }
            }

            if ($house_id && $flat_id == 0)
            {
                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.house_id' , $dis_arr);
                $link_where->orPredicate($or_where);
                if ($id_data['lc_contract_id'])
                {
                    $lc_where = new \Zend\Db\Sql\Where();
                    $lc_where->equalTo('sn.source' , 'landlord_contract');
                    $lc_where->in('sn.source_id' , $lc_arr);
                    $link_where->orPredicate($lc_where);
                }
            }
            if ($rf_room_id && $flat_id == 0)
            {
                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.room_id' , $f_arr);
                $link_where->orPredicate($or_where);
                if ($id_data['lc_contract_id'])
                {
                    $lc_where = new \Zend\Db\Sql\Where();
                    $lc_where->equalTo('sn.source' , 'landlord_contract');
                    $lc_where->in('sn.source_id' , $lc_arr);
                    $link_where->orPredicate($lc_where);
                }
            }
            $not_room = new \Zend\Db\Sql\Where();
            $not_room->equalTo('sn.house_type' , 0);
            $link_where->orPredicate($not_room);
            $his_in_where->addPredicate($link_where);
            $his_in_where->equalTo('sn.company_id' , $company_id);
            $his_in_where->equalTo('sn.type' , 1);
            $his_in_where->equalTo('sn.is_delete' , 0);
            $his_in_select->where($his_in_where);
            $all_income = $his_in_select->execute();

            //3.1 首页历史支出
            $his_dis_sql = $serial_model->getSqlObject();
            $his_dis_select = $his_dis_sql->select(array('sn' => 'serial_number'));
            $his_dis_select->columns(array('sum' => new Expression('sum(sn.money)')));
            if ($flat_id != 0 && $flat_id != -1)
            {
                $his_dis_select->leftjoin(array('rf' => 'room_focus') , 'sn.room_id = rf.room_focus_id' , array());
                $his_dis_select->leftjoin(array('f' => 'flat') , 'rf.flat_id = f.flat_id' , array());
            }
            $his_dis_where = new \Zend\Db\Sql\Where();
            
            /**
             * 首页历史收入权限
             */
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $auth_arr = $serial_number_helper->authValidate($his_dis_select, $his_dis_where, $flat_id, $user);
                $day_in_where = $auth_arr['where'];
                $in_select = $auth_arr['select'];
            }
            
            $link_where = new \Zend\Db\Sql\Where();
            if ($flat_id != 0 && $flat_id == -1)
            {
                $his_dis_where->equalTo('sn.house_type' , 1);

                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.house_id' , $dis_arr);
                $link_where->orPredicate($or_where);
                if ($id_data['lc_contract_id'])
                {
                    $lc_where = new \Zend\Db\Sql\Where();
                    $lc_where->equalTo('sn.source' , 'landlord_contract');
                    $lc_where->in('sn.source_id' , $lc_arr);
                    $link_where->orPredicate($lc_where);
                }
            }
            elseif ($flat_id != 0 && $flat_id != -1)
            {
                $his_dis_where->equalTo('f.flat_id' , $flat_id);
                $his_dis_where->equalTo('f.company_id' , $company_id);
                $his_dis_where->equalTo('sn.house_type' , 2);

                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.room_id' , $f_arr);
                $link_where->orPredicate($or_where);
                if ($id_data['lc_contract_id'])
                {
                    $lc_where = new \Zend\Db\Sql\Where();
                    $lc_where->equalTo('sn.source' , 'landlord_contract');
                    $lc_where->in('sn.source_id' , $lc_arr);
                    $link_where->orPredicate($lc_where);
                }
            }

            if ($house_id && $flat_id == 0)
            {
                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.house_id' , $dis_arr);
                $link_where->orPredicate($or_where);
                if ($id_data['lc_contract_id'])
                {
                    $lc_where = new \Zend\Db\Sql\Where();
                    $lc_where->equalTo('sn.source' , 'landlord_contract');
                    $lc_where->in('sn.source_id' , $lc_arr);
                    $link_where->orPredicate($lc_where);
                }
            }
            if ($rf_room_id && $flat_id == 0)
            {
                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('sn.room_id' , $f_arr);
                $link_where->orPredicate($or_where);
                if ($id_data['lc_contract_id'])
                {
                    $lc_where = new \Zend\Db\Sql\Where();
                    $lc_where->equalTo('sn.source' , 'landlord_contract');
                    $lc_where->in('sn.source_id' , $lc_arr);
                    $link_where->orPredicate($lc_where);
                }
            }
            //$landlord_where = new \Zend\Db\Sql\Where();
            //$landlord_where->equalTo('sn.source', 'landlord_contract');
            //$link_where->orPredicate($landlord_where);
            $not_room = new \Zend\Db\Sql\Where();
            $not_room->equalTo('sn.house_type' , 0);
            $link_where->orPredicate($not_room);
            $his_dis_where->addPredicate($link_where);
            $his_dis_where->equalTo('sn.company_id' , $company_id);
            $his_dis_where->equalTo('sn.type' , 2);
            $his_dis_where->equalTo('sn.is_delete' , 0);
            $his_dis_select->where($his_dis_where);
            $all_pay = $his_dis_select->execute();

            //4 在住押金
            //获取所属公司的押金id
            /*$fee_where = array('type_name' => '押金' , 'company_id' => $company_id , 'is_delete' => 0);
            $fee_type_id = M('FeeType')->getOne($fee_where , array('fee_type_id' => 'fee_type_id'));
            //查询所有合同流水
            $serial_where = array('source' => 'tenant_contract' , 'company_id' => $company_id , 'is_delete' => 0);
            $serial_id_arr = M('SerialNumber')->getData($serial_where , array('serial_id' => 'serial_id'));
            $serial_id = array_column($serial_id_arr , 'serial_id');

            //根据条件到详细表查押金金额
            $detail_where = new \Zend\Db\Sql\Where();
            $detail_where->in('serial_id' , $serial_id);
            $detail_where->equalTo('fee_type_id' , $fee_type_id['fee_type_id']);
            $serial_deposit = M('SerialDetail')->getOne($detail_where , array('total_deposit' => new Expression('sum(money)')));*/

            //获取合同里的押金
            $dis_sql = $this->getContractSql($user , 1);//分散式
            $dis_arr = array(new Expression($dis_sql));
            $f_sql = $this->getContractSql($user , 2);//集中式
            $f_arr = array(new Expression($f_sql));
            $dis_contract_id = $this->getContractSql($user , 1 , 1);
            $f_contract_id = $this->getContractSql($user , 1 , 2);

            $con_sql = M('TenantContract')->getSqlObject();
            $con_select = $con_sql->select(array('tc' => 'tenant_contract'));
            $con_select->columns(array('sum' => new Expression('sum(deposit)')));
            $con_select->leftjoin(array('re' => 'rental') , 're.contract_id = tc.contract_id' , array());
            $con_where = new \Zend\Db\Sql\Where();
            $link_where = new \Zend\Db\Sql\Where();
            if ($flat_id != 0 && $flat_id != -1)
            {//集中式
                //$con_select->leftjoin(array('re' => 'rental') , 're.contract_id = tc.contract_id' , array());
                $con_select->leftjoin(array('rf' => 'room_focus') , 're.room_id = rf.room_focus_id' , array());
                $con_select->leftjoin(array('f' => 'flat') , 'rf.flat_id = f.flat_id' , array());
                $con_where->equalTo('re.house_type' , 2);
                $con_where->equalTo('re.is_delete' , 0);
                $con_where->equalTo('f.flat_id' , $flat_id);

                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('tc.contract_id' , $f_arr);
                $link_where->orPredicate($or_where);
            }
            
            /**
             * 在住押金权限
             */
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $tenant_model = new \Common\Model\Erp\TenantContract();
                $auth_arr = $tenant_model->tcAuthValidate($con_select, $con_where, $flat_id, $user);
                $con_where = $auth_arr['where'];
                $con_select = $auth_arr['select'];
            }
            
            if ($flat_id != 0 && $flat_id == -1)
            {//分散式
                //$con_select->leftjoin(array('re' => 'rental') , 're.contract_id = tc.contract_id' , array());
                //$con_select->leftjoin(array('r' => 'room') , 're.room_id = r.room_id' , array());
                $con_where->equalTo('re.house_type' , 1);
                $con_where->equalTo('re.is_delete' , 0);

                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('tc.contract_id' , $dis_arr);
                $link_where->orPredicate($or_where);
            }
            if ($dis_contract_id && $flat_id == 0)
            {
                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('tc.contract_id' , $dis_arr);
                $link_where->orPredicate($or_where);
            }
            if ($f_contract_id && $flat_id == 0)
            {
                $or_where = new \Zend\Db\Sql\Where();
                $or_where->in('tc.contract_id' , $f_arr);
                $link_where->orPredicate($or_where);
            }
            $con_where->addPredicate($link_where);
            $con_where->equalTo('tc.company_id' , $user['company_id']);
            $con_where->equalTo('tc.is_delete' , 0);
            $con_where->equalTo('tc.is_haveson' , 0);
            $con_where->equalTo('tc.is_stop' , 0);
            $con_select->where($con_where);
            $zujin = $con_select->execute();
            $zujin = is_array($zujin) ? $zujin[0]['sum'] : 0;
            //合同押金和流水押金
            //$zujin = $zujin + $serial_deposit['total_deposit'];
            return array('list' => $info ,
                'all_income' => (float) $all_income[0]['sum'] ,
                'zaizhuzujin' => (float) $zujin ,
                'all_pay' => (float) $all_pay[0]['sum'] ,
                'day_income' => (float) $day_income[0]['day_income'] ,
                'day_expense' => (float) $day_expense[0]['day_expense']);
        }

        /**
         * 根据城市id 查询集中或分散式房源
         * 修改时间  2015-7-7 14:28:20
         * 
         * @author ft
         * @param  int $param
         */
        public function getDisAndFocusSql($user , $param)
        {
            $city_model = new \Common\Model\Erp\City();
            $landlord_model = new \Common\Model\Erp\LandlordContract();
            if ($param == 1)
            {
                $dis_sql = $city_model->getSqlObject();
                $dis_select = $dis_sql->select(array('c' => 'city'));
                $dis_select->columns(array());
                $dis_select->leftjoin(array('ct' => 'community') , 'c.city_id = ct.city_id' , array());
                $dis_select->leftjoin(array('h' => 'house') , 'ct.community_id = h.community_id' , array('house_id' => 'house_id'));
                $dis_where = new \Zend\Db\Sql\Where();
                $dis_where->equalTo('c.city_id' , $user['city_id']);
                $dis_where->equalTo('h.company_id' , $user['company_id']);
                //$dis_where->equalTo('h.is_delete' , 0);
                $dis_select->where($dis_where);
                return $dis_select->getSqlString();
            }
            if ($param == 2)
            {
                $f_sql = $city_model->getSqlObject();
                $f_select = $f_sql->select(array('c' => 'city'));
                $f_select->columns(array());
                $f_select->leftjoin(array('f' => 'flat') , 'c.city_id = f.city_id' , array());
                $f_select->leftjoin(array('rf' => 'room_focus') , 'rf.flat_id = f.flat_id' , array('rf_room_id' => 'room_focus_id'));
                $f_where = new \Zend\Db\Sql\Where();
                $f_where->equalTo('c.city_id' , $user['city_id']);
                $f_where->equalTo('f.company_id' , $user['company_id']);
                $f_where->equalTo('rf.company_id' , $user['company_id']);
                //$f_where->equalTo('f.is_delete' , 0);
                //$f_where->equalTo('rf.is_delete' , 0);
                $f_select->where($f_where);
                return $f_select->getSqlString();
            }
            if ($param == 3)
            {
                $sql = $landlord_model->getSqlObject();
                $select = $sql->select(array('lc' => 'landlord_contract'));
                $select->columns(array('contract_id' => 'contract_id'));
                $where = new \Zend\Db\Sql\Where();
                $where->equalTo('lc.city_id' , $user['city_id']);
                $where->equalTo('lc.company_id' , $user['company_id']);
                $select->where($where);
                return $select->getSqlString();
            }
        }

        /**
         * 获取集中式和分散式有效合同 SQL
         * 修改时间  2015年7月8日10:43:27
         * 
         * @author ft
         * @param  array $user
         * @param  int $param
         * @param  int $house_type
         */
        public function getContractSql($user , $param , $house_type = 0)
        {
            if ($param == 1)
            {
                $community_model = new \Common\Model\Erp\Community();
                $sql = $community_model->getSqlObject();
                $select = $sql->select(array('ct' => 'community'));
                $select->columns(array());
                $select->leftjoin(array('h' => 'house') , 'ct.community_id = h.community_id' , array());
                $select->join(array('re' => 'rental') , 're.house_id = h.house_id' , array('contract_id' => 'contract_id'));
                $where = new \Zend\Db\Sql\Where();
                $where->equalTo('ct.city_id' , $user['city_id']);
                $where->equalTo('h.company_id' , $user['company_id']);
                $where->equalTo('re.is_delete' , 0);
                $where->equalTo('re.house_type' , 1);
                $select->where($where);
                if ($house_type == 1)
                {
                    return $select->execute();
                }
                return $select->getSqlString();
            }
            if ($param == 2)
            {
                $flat_model = new \Common\Model\Erp\Flat();
                $sql = $flat_model->getSqlObject();
                $select = $sql->select(array('f' => 'flat'));
                $select->columns(array());
                $select->leftjoin(array('rf' => 'room_focus') , 'rf.flat_id = f.flat_id' , array());
                $select->join(array('re' => 'rental') , 'rf.room_focus_id = re.room_id' , array('contract_id' => 'contract_id'));
                $where = new \Zend\Db\Sql\Where();
                $where->equalTo('f.city_id' , $user['city_id']);
                $where->equalTo('f.company_id' , $user['company_id']);
                $where->equalTo('rf.company_id' , $user['company_id']);
                $where->equalTo('re.is_delete' , 0);
                $where->equalTo('re.house_type' , 2);
                $select->where($where);
                if ($house_type == 2)
                {
                    return $select->execute();
                }
                return $select->getSqlString();
            }
        }

    }
    