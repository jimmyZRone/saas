<?php

    namespace App\Api\Mvc\Controller;

    class RoomController extends \App\Api\Lib\Controller
    {

        /**
         * 获取某一个房源下面的所有单间信息根据house_id
         * @author yusj | 最后修改时间 2015年5月5日下午4:57:38
         */
        public function getRoomInfoAction()
        {

            PV(array('pattern' , 'house_id'));
            $house_id = I("house_id");
            $room_id = I("room_id");
            $rental_way = I("rental_way");
            $pattern = I('pattern');
            $community = I('community_id');
            $user_info = $this->getUserInfo();
            if (!is_numeric($house_id) && is_int($house_id))
            {
                return_error(131);
            }
            $roomModel = new \Common\Helper\Erp\Room();
            $data = $roomModel->getRoomInfo($user_info , $house_id , $pattern , $room_id , $rental_way);

            if (emptys($data))
            {
                return_error(203);
            }

            return_success($data);
        }

        public function setRoomConfigAction()
        {
            PV(array('type' , 'id'));
            $type = I('type');
            $configs = I('configs' , '' , 'trim');
            $configs = explode(',' , trim($configs , ','));
            $configs = array_unique($configs);
            $configs = array_filter($configs);
            $company_id = $this->getCompanyId();
            $feeTypeModel = new \App\Api\Helper\FeeType();
            $user_config = M('Company')->get(false , $this->getUserInfo());
            $user_config = $user_config['House']['public_facilities'];
            $id = I('id');

            foreach ($configs as $cid)
            {
                if (!isset($user_config[$cid]))
                    return_error(127 , '房间配置信息格式不符合规则');
            }
            sort($configs);
            $save_config = implode('-' , $configs);

            $result = false;
            switch ($type)
            {
                //分散式房源
                case '1':
                    $H = M('House');
                    $result = $H->edit(array(
                        'house_id' => $id ,
                        'company_id' => $company_id ,
                            ) , array('public_facilities' => $save_config)
                    );

                    //权限验证  
                    if (!$this->verifyDataLinePermissions(\Common\Helper\Permissions::DELETE_AUTH_ACTION , $id , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE))
                    {
                        return_error(127 , '无操作权限');
                    }


                    break;
                //分散式房间
                case '2':
                    $room_info = H('Room')->getUserRoomInfo($this->getUserInfo() , 2 , $id);
                    //无权限操作
                    if (!$room_info)
                        return_error(504);
                    $R = M('Room');
                    $result = $R->edit(array(
                        'room_id' => $id ,
                            ) , array('room_config' => $save_config)
                    );
                    break;
                //集中式房源
                case '3':

                    $R = M('RoomFocus');
                    $result = $R->edit(array(
                        'room_focus_id' => $id ,
                        'company_id' => $company_id ,
                            ) , array('room_config' => $save_config)
                    );

                    //权限验证
                    if (!$this->verifyDataLinePermissions(\Common\Helper\Permissions::SELECT_AUTH_ACTION , $id , \Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED))
                    {
                        return array();
                    }
                    break;
            }

            if ($result)
                return_success();
            else
                return_error(141);
        }

        public function setRoomStatusAction()
        {
            //必传参数验证
            PV(array('room_id' , 'pattern'));
            $pattern = I('pattern');
            $room_id = I('room_id');
            $rental_way = I('rental_way');

            $status = I('status');
            $delete = I('delete');
            $is_yytz = I('is_yytz');
            $is_yd = I('is_yd');
            $data = array(
                'status' => $status ,
                'delete' => $delete ,
                'is_yytz' => $is_yytz ,
                'is_yd' => $is_yd ,
            );
            $user_info = $this->getUserInfo();
            $Room = new \Common\Helper\Erp\Room();

            $result = $Room->setRoomStatus($user_info , $data , $room_id , $pattern , $rental_way);

            if (!$result)
                return_error(135);
            return_success();
        }

        function RenewalAction()
        {
            PV(array(
                'room_id' , 'type' , 'money' , 'stime' , 'etime' , 'source' , 'name' , 'phone' , 'idcard'
            ));
            $money = I('money');
            $stime = I('stime' , '' , 'strtotime');
            $etime = I('etime' , '' , 'strtotime');
            $pay_type = I('pay_type');
            $source = I('source');
            $mark = I('mark' , '' , 'trim');
            $name = I('name' , '' , 'trim');
            $phone = I('phone');
            $idcard = I('idcard');
            $gender = I('gender');
            $time = strtotime(date('Y-m-d 00:00:00'));
            if (!is_numeric($money))
                return_error(131 , '定金格式不正确');
            if (!is_time($stime) || !is_time($etime))
                return_error(131 , '预定时间不正确');

            if (!is_zzs($source))
                return_error(131 , '来源类型不正确');
            if (!is_zzs($phone) || strlen($phone) != 11)
                return_error(131 , '手机号格式不正确');


            //查询是否有权限更改房间信息

            $user = $this->getUserInfo();
            $id = I('room_id');
            $type = I('type');

            if ($type == 1)
            {
                $type = 1;
                $room_info = M('HouseEntirel')->getOne(array('house_id' => $id));

                if (!is_array($room_info))
                    return_error(119);
                $id = $room_info['house_entirel_id'];
            }
            $room_info = H('Room')->getUserRoomInfo($user , $type , $id);

            if (!is_array($room_info))
                return_error(127 , ',无权限操作');

            $house_id = $room_info['house_id'];
            //已租状态

            if ($room_info['status'] == 2)
            {
                $TC = M('TenantContract');

                $select = $TC->getSqlObject()->select();
                $select->from(array('tc' => 'tenant_contract'))->join(array('r' => 'rental') , 'tc.contract_id =r.contract_id');

                if ($type == 4)
                {
                    $house_id = '0';
                    $house_type = 2;
                    $select->where(
                            array('r.room_id' => $id , 'r.house_type' => 2)
                    );
                }
                else
                {
                    $house_type = 1;
                    $select->where(
                            array('r.room_id' => $id , 'r.house_id' => $room_info['house_id'] , 'r.house_type' => 1)
                    );
                }

                $result = $select->where(array('r.is_delete' => 0))->execute();

//                foreach ($result as $info)
//                {
//                    if ($stime < $info['dead_line'] || $stime < $info['end_line'])
//                        return_error(127 , ',已租房的预约时间不能在合同有效期范围内');
//                }
            }
            //查询已预约
            $R = M('Reserve');
            $where = new \Zend\Db\Sql\Where();


            if ($type == 4)
            {
                $flat_info = H('Room')->getUserRoomInfo($user , 5 , $room_info['flat_id']);
                $room_info['rental_way'] = $flat_info['rental_way'];
                $house_type = 2;
                $where->equalTo('room_id' , $id);
                $where->equalTo('house_type' , 2);
            }
            else
            {
                $house_type = 1;
                $where->equalTo('room_id' , $id);
                $where->equalTo('house_id' , $room_info['house_id']);
                $where->equalTo('house_type' , 1);
            }
            $where->equalTo('is_delete' , 0);
            $where2 = new \Zend\Db\Sql\Where();

            $where2->expression("(stime <=$stime AND  etime >= $stime )" , array());
            $where2->or;
            $where2->expression("(stime <=$etime  AND  etime >=$etime )" , array());

            $where->addPredicate($where2);
            $count = $R->getCount($where);

            if ($count > 0)
                return_error(127 , ',当前预约的时间,已被他人预约');
            $T = M('Tenant');
            $R->Transaction();
            $company_id = $this->getCompanyId();
            $tenant_db_info = $T->getOne(array('idcard' => $idcard , 'company_id' => $company_id));

            if (count($tenant_db_info) > 0)
            {
                $tenant_id = $tenant_db_info['tenant_id'];
            }
            else
            {
                $add_data = array(
                    'phone' => $phone ,
                    'gender' => $gender ,
                    'name' => $name ,
                    'idcard' => $idcard ,
                    'company_id' => $company_id
                );

                $tenant_id = $T->insert(
                        $add_data
                );
                if (!$tenant_id)
                    return_error(131 , '储存用户信息失败');
            }

            if ($house_type == 1 && $room_info['rental_way'] == 2)
            {
                $id = 0;
                $house_id = $room_info['house_id'];
            }



            $data = array(
                'tenant_id' => $tenant_id ,
                'house_id' => (int) $house_id ,
                'house_type' => $house_type ,
                'room_id' => $id ,
                'money' => $money ,
                'stime' => $stime ,
                'etime' => $etime ,
                'mark' => $mark ,
                'create_time' => $time ,
                'pay_type' => 2 ,
                'source' => $source ,
                'is_delete' => 0 ,
                'rental_way' => $room_info['rental_way'] ,
            );



            if ($type == 1)
            {
                $data['room_id'] = 0;
            }

            $reserve_id = $R->insert($data);

            if (!$reserve_id)
            {
                $R->rollback();
                return_error(142 , '储存预定信息失败');
            }

            $data = array('is_yd' => 1);
            if (isset($room_info['room_id']))
            {
                $result = M('Room')->edit(array('room_id' => $room_info['room_id']) , $data);
            }
            elseif ($house_type == 1 && $room_info['rental_way'] == 2)
            {
                $result = M('HouseEntirel')->edit(array('house_id' => $room_info['house_id']) , $data);
            }
            else
            {
                $result = M('RoomFocus')->edit(array('room_focus_id' => $room_info['room_focus_id']) , $data);
            }

            if (!$result)
            {
                $R->rollback();
                return_error(131 , '储存预定状态失败');
            }

            $R->commit();
            return_success(array('reserve_id' => $reserve_id));
        }

        function RentalBackAction()
        {
            PV(array(
                'room_id' , 'type' , 'back_rental_time'
            ));
            $user = $this->getUserInfo();
            $id = I('room_id');
            $type = I('type');
            $back_rental_time = I('back_rental_time' , '' , 'strtotime');

            $today = strtotime(date('Y-m-d'));

            if (!is_time($back_rental_time))
                return_error(127 , ',退租日期格式错误');


            $room_info = H('Room')->getUserRoomInfo($user , $type , $id);

            if (!is_array($room_info))
                return_error(119);

            if ($room_info['is_yytz'] == 1)
                return_error(127 , ',该房间已经为，预约退租状态');

            if ($type == 4)
            {
                $house_id = '0';
                $house_type = 2;
                $room_type = 0;
            }
            else
            {
                $house_id = $room_info['house_id'];
                $house_type = 1;
                $room_type = $room_info['rental_way'] == 2 ? 1 : 2;
            }

            $data = array(
                'type' => $house_type ,
                'house_type' => $room_type ,
                'source_id' => $id ,
                'back_rental_time' => $back_rental_time ,
                'remark' => I('remark' , '' , 'trim') ,
                'is_delete' => 0 ,
                'creat_time' => time() ,
            );

            $RBR = M('ReserveBackRental');
            $RBR->Transaction();
            $reserve_back_id = $RBR->insert($data);
            if (!$reserve_back_id)
                return_error(131 , ',储存失败');


            $data = array('is_yytz' => 1);
            if (isset($room_info['room_id']))
            {
                $result = M('Room')->edit(array('room_id' => $room_info['room_id']) , $data);
            }
            elseif (isset($room_info['house_id']) && $room_info['rental_way'] == 2)
            {
                $result = M('HouseEntirel')->edit(array('house_id' => $room_info['house_id']) , $data);
            }
            else
            {
                $result = M('RoomFocus')->edit(array('room_focus_id' => $room_info['room_focus_id']) , $data);
            }
            if (!$result)
            {
                $RBR->rollback();
                return_error(131 , '储存退租状态失败');
            }
            $RBR->commit();
            return_success();
        }

        function CancelRentalBackAction()
        {
            PV(array(
                'room_id' , 'type'
            ));
            $user = $this->getUserInfo();
            $id = I('room_id');
            $type = I('type');

            $room_info = H('Room')->getUserRoomInfo($user , $type , $id);
            if (!is_array($room_info))
                return_error(119);

            if (isset($room_info['is_yytz']) && $room_info['is_yytz'] == 0)
                return_error(127 , ',该房间不为预约退租状态');
            if ($type == 4)
            {
                $house_id = '0';
                $house_type = 2;
            }
            else
            {
                $house_id = $room_info['house_id'];
                $house_type = 1;
            }

            $room_type = (isset($room_info['rental_way']) && $room_info['rental_way'] == 1) ? $room_info['rental_way'] = 2 : ((isset($room_info['rental_way']) && $room_info['rental_way'] = 2) ? $room_info['rental_way'] = 1 : 0);

            $where = array(
                'type' => $house_type ,
                'house_type' => $room_type ,
                'source_id' => $id ,
            );

            $RBR = M('ReserveBackRental');
            $RBR->Transaction();
            $reserve_back_id = $RBR->edit($where , array('is_delete' => 1));
            if (!$reserve_back_id)
                return_error(131 , ',删除预约退租信息失败');

            $data = array('is_yytz' => 0);
            if (isset($room_info['room_id']))
            {//分散合租
                $room_result = M('Room')->edit(array('room_id' => $room_info['room_id']) , $data);
                $reserve_result = M('ReserveBackRental')->edit(array('source_id' => $room_info['room_id'] , 'type' => 1 , 'house_type' => 2) , array('is_delete' => 1));
                if (!$room_result && !$reserve_result)
                {
                    $RBR->rollback();
                    return_error(131 , '合租预约退租状态失败!');
                }
                delBackLog('ROOM_RESERVE' , $id);
            }
            elseif (isset($room_info['house_entirel_id']))
            {
                $result = M('HouseEntirel')->edit(array('house_entirel_id' => $room_info['room_id']) , $data);
            }
            elseif (isset($room_info['rental_way']))
            {//分散整组
                $entirel_result = M('HouseEntirel')->edit(array('house_id' => $room_info['house_id']) , $data);
                $reserve_result = M('ReserveBackRental')->edit(array('source_id' => $room_info['house_id'] , 'type' => 1 , 'house_type' => 1) , array('is_delete' => 1));
                if (!$entirel_result || !$reserve_result)
                {
                    $RBR->rollback();
                    return_error(131 , '整租预约退租状态失败!');
                }
                delBackLog('HOUSE_RESERVE' , $id);
            }
            elseif (isset($room_info['room_focus_id']) && $type == 4)
            {//集中式
                $focus_result = M('RoomFocus')->edit(array('room_focus_id' => $room_info['room_focus_id']) , $data);
                $reserve_result = M('ReserveBackRental')->edit(array('source_id' => $room_info['room_focus_id'] , 'type' => 2 , 'house_type' => 0) , array('is_delete' => 1));
                if (!$focus_result && !$reserve_result)
                {
                    $RBR->rollback();
                    return_error(131 , '集中式预约退租状态失败!');
                }
                delBackLog('ROOM_FOCUS_RESERVE' , $id);
            }
            //if (!$result)
            //{
            //    $RBR->rollback();
            //    return_error(131 , '储存预约退租状态失败');
            //}
            $RBR->commit();
            return_success();
        }

        function GetRenewalListAction()
        {
            PV(array(
                'room_id' , 'type'
            ));
            $user = $this->getUserInfo();
            $id = I('room_id');
            $type = I('type');

            if ($type == 1 || $type == 3)
            {
                $type = 1;
                $room_info = M('HouseEntirel')->getOne(array('house_id' => $id));

                if (!is_array($room_info))
                    return_error(119);
                $id = $room_info['house_entirel_id'];
            }
            $room_info = H('Room')->getUserRoomInfo($user , $type , $id);

            if (!is_array($room_info))
                return_error(119);

            if ($type == 4)
            {
                $house_id = '0';
                $house_type = 2;
            }
            else
            {

                if ($room_info['rental_way'] == 2)
                    $id = 0;

                $house_id = $room_info['house_id'];
                $house_type = 1;
            }
            $select = M('Reserve')->getSqlObject()->select();

            $where = array(
                'r.house_id' => $house_id ,
                'r.house_type' => $house_type ,
                'r.room_id' => $id ,
                'r.is_delete' => 0 ,
            );

            $list = $select->from(array('r' => 'reserve'))->leftjoin(array('t' => 'tenant') , 'r.tenant_id=t.tenant_id')->where($where)->execute();
            $data = array();
            foreach ($list as $info)
            {
                $data[] = array(
                    'reserve_id' => $info['reserve_id'] ,
                    'name' => $info['name'] ,
                    'phone' => $info['phone'] ,
                    'money' => $info['money'] ,
                );
            }
            return_success($data);
        }

        function DeleteRenewalAction()
        {
            PV('reserve_ids');
            $reserve_ids = trim(I('reserve_ids') , ',');
            $reserve_ids = explode(',' , $reserve_ids);
            $R = M('Reserve');
            $select = $R->getSqlObject()->select();
            $where = array(
                'r.reserve_id' => $reserve_ids ,
                't.company_id' => $this->getCompanyId() ,
            );
            $list = $select->from(array('r' => 'reserve'))->leftjoin(array('t' => 'tenant') , 't.tenant_id=r.tenant_id' , array())->where($where)->execute();

            foreach ($list as $info)
            {

                $del = $R->edit(array('reserve_id' => $info['reserve_id']) , array('is_delete' => 1));
                if (!$del)
                    return_success(142);


                //删除代办
                if ($info['house_type'] == 2)
                {
                    M('Todo')->delete(array('module' => 'FOCUS_RESERVE_OUT' , 'entity_id' => $info['reserve_id']));
                }
                elseif ($info['room_id'] == 0)
                {

                    M('Todo')->delete(array('module' => 'HOUSE_RESERVE_OUT' , 'entity_id' => $info['reserve_id']));
                }
                else
                {
                    M('Todo')->delete(array('module' => 'ROOM_RESERVE_OUT' , 'entity_id' => $info['reserve_id']));
                }

                //查询该房间是否还有预定 如果没有则修改为未预定状态
                $count = $R->getCount(array('is_delete' => 0 , 'house_id' => $info['house_id'] , 'room_id' => $info['room_id'] , 'house_type' => $info['house_type']));

                if ($count > 0)
                    continue;

                $R = H('Room');


                if ($info['house_type'] == 1 && $info['rental_way'] == 2)
                {

                    //权限验证  
                    if (!$this->verifyDataLinePermissions(\Common\Helper\Permissions::DELETE_AUTH_ACTION , $info['house_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE))
                    {
                        return_error(142 , ',无权限操作');
                    }
                    $edit = M('HouseEntirel');
                    $where = array('house_id' => $info['house_id']);
                }
                elseif ($info['house_type'] == 1 && $info['rental_way'] == 1)
                {

                    //权限验证  
                    if (!$this->verifyDataLinePermissions(\Common\Helper\Permissions::DELETE_AUTH_ACTION , $info['house_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE))
                    {
                        return_error(142 , ',无权限操作');
                    }


                    $where = array('room_id' => $info['room_id']);
                    $edit = M('Room');
                }
                else
                {


                    //权限验证
                    if (!$this->verifyDataLinePermissions(\Common\Helper\Permissions::SELECT_AUTH_ACTION , $info['room_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED))
                    {
                        return_error(142 , ',无权限操作');
                    }

                    $where = array('room_focus_id' => $info['room_id']);
                    $edit = M('RoomFocus');
                }

                $save = $edit->edit($where , array('is_yd' => 0));
                if (!$save)
                    return_error(142 , ',储存预定信息失败');
            }
            return_success();
        }

        function StopAction()
        {

            PV(array('room_id' , 'type'));
            $user = $this->getUserInfo();
            $id = I('room_id');
            $type = I('type');

            if ($type == 1 || $type == 3)
            {
                $type = 1;
                $room_info = M('HouseEntirel')->getOne(array('house_id' => $id));

                if (!is_array($room_info))
                    return_error(119);
                $id = $room_info['house_entirel_id'];
            }

            $room_info = H('Room')->getUserRoomInfo($user , $type , $id);

            if (!is_array($room_info))
                return_error(119);

            if ($room_info['status'] == 3)
                return_error(142 , ',该房已经为停用状态');

            if ($room_info['status'] == 2)
                return_error(142 , ',已租房不能停用');

            if ($type == 4)
            {
                $house_id = '0';
                $house_type = 2;
            }
            else
            {


                if ($room_info['rental_way'] == 2)
                    $id = $room_info['house_id'];

                $house_type = 1;
            }


            $stime = I('stime' , '' , 'strtotime');
            $etime = I('etime' , '' , 'strtotime');
            $stop_reason = I('stop_reason' , '' , 'trim');


            if (is_time($stime) && is_time($etime))
            {
                $room_type = isset($room_info['rental_way']) ? $room_info['rental_way'] == 1 ? 2 : 1 : 2;

                $stop_id = M('StopHouse')->insert(array(
                    'type' => $house_type ,
                    'house_type' => $room_type ,
                    'source_id' => $id ,
                    'start_time' => $stime ,
                    'end_time' => $etime ,
                    'remark' => $stop_reason ,
                ));
            }

            $data = array('status' => 3);

            if (isset($room_info['room_id']))
            {
                $result = M('Room')->edit(array('room_id' => $room_info['room_id']) , $data);
            }
            elseif (isset($room_info['house_entirel_id']))
            {
                $result = M('HouseEntirel')->edit(array('house_entirel_id' => $room_info['house_entirel_id']) , $data);
            }
            else
            {
                $result = M('RoomFocus')->edit(array('room_focus_id' => $room_info['room_focus_id']) , $data);
            }
            return_success();
        }

        function saveAction()
        {
            PV('room_list');
            $room_list = I('room_list' , '' , 'trim');
            $R = M('Room');

            $R->Transaction();
            $room_list = is_string($room_list) ? (array) json_decode($room_list , true) : $room_list;
            $num = 0;
            $F = new \App\Api\Helper\FeeType();
            foreach ($room_list as $info)
            {
                //权限验证  
                if (!$this->verifyDataLinePermissions(\Common\Helper\Permissions::DELETE_AUTH_ACTION , $info['house_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE))
                {
                    continue;
                }


                $num++;
                $money = $info['money'];
                $pay = $info['pay'];
                $detain = $info['detain'];
                $area = $info['area'];
                $custom_number = $info['custom_number'];
                if (!is_numeric($money))
                {
                    $R->rollback();
                    return_error(131 , '租金格式不正确');
                }

                if (!is_numeric($detain) || !is_numeric($pay))
                {
                    $R->rollback();
                    return_error(131 , '压付格式不正确');
                }

                if (!emptys($area) && !is_numeric($area))
                {
                    $R->rollback();
                    return_error(131 , '房间面积格式不正确');
                }
                $room_type_list = array("main" => "主卧" , "second" => "次卧" , "guest" => "客卧");
                $room_type = $info['room_type'];
                $data_room['area'] = (int) $area;
                $data_room['custom_number'] = emptys($custom_number) ? $num : $custom_number;
                $data_room['room_type'] = isset($room_type_list[$room_type]) ? $room_type : 'second';
                $data_room['occupancy_number'] = (int) $info['occupancy_number'];
                //一下是默认值
                $data_room['room_config'] = implode('-' , explode(',' , $info['room_config']));

                $data_room['detain'] = $detain;
                $data_room['pay'] = $pay;
                $data_room['money'] = $money;
                $data_room['update_time'] = time();
                if (isset($info['room_id']))
                {
                    $house_name = M('House')->getOne(array('house_id' => $info['house_id']) , array('house_name' => 'house_name'));
                    $data_room['full_name'] = $house_name['house_name'] . $room_type_list[$room_type] . $custom_number . '号';
                }
                $edit = $R->edit(array('room_id' => $info['room_id']) , $data_room);
                if (!$edit)
                {
                    $R->rollback();
                    return_error(141 , ',储存房间数据失败.');
                }
                $fee_list = $info["room_fee_list"];
                $fee_list = is_array($fee_list) ? $fee_list : (array) json_decode($fee_list , true);

                $fee_add = $F->addFee($info['room_id'] , 'SOURCE_DISPERSE_ROOM' , $fee_list);
                
                if (!$fee_add)
                {
                    $R->rollback();
                    return_error(127 , ',费用添加失败'); // 增加失败
                }
            }
            $R->commit();
            return_success();
        }

        function addConfigAction()
        {

            PV('config_list');

            $C = M('Company');
            $companyModel = new \Common\Model\Erp\Company();
            $sysConfig = new \Common\Model\Erp\SystemConfig();

            $config_list = I('config_list' , '' , 'trim');
            $config_list = is_array($config_list) ? $config_list : json_decode($config_list , true);
            $end_key = array();
            $new_key = 1;
            $public_facilities = array();

            if (count($config_list) == 0)
            {
                return_error(142);
            }

            foreach ($config_list as &$info)
            {
                $var = $info['name'];
                if (is_numeric($info['id']))
                {
                    $key = $info['id'];
                }
                else
                {
                    $key = $new_key + 1;

                    $info['id'] = (string) $key;
                }
                $new_key = $key;
                $public_facilities[$key] = $var;
            }

            $save = $companyModel->set(array('value' => $public_facilities , 'key' => 'House/public_facilities') , $this->getUserInfo());

            if (!$save)
                return_error(142);
            return_success($config_list);
        }

        /**
         * 获取预约退租
         */
        function getReserveBackRentalAction()
        {
            PV(array('house_type' , 'room_id'));
            $RBR = M('ReserveBackRental');
            $rental_way = I('rental_way');
            $where = array();
            $where['is_delete'] = 0;
            if (is_numeric($rental_way))
            {
                $where['house_type'] = $rental_way == 1 ? 2 : 1;
            }
            $where['type'] = I('house_type');

            $where['source_id'] = I('room_id');
            $list = $RBR->getData($where , array('reserve_back_id' , 'back_rental_time' , 'remark') , 99999 , 0 , 'creat_time desc');
            return_success($list);
        }

        /**
         * 获取停用房间列表
         */
        function getStopRoomAction()
        {
            PV(array('house_type' , 'room_id'));
            $RBR = M('StopHouse');
            $rental_way = I('rental_way');
            $where = array();
            $where['is_delete'] = 0;
            if (is_numeric($rental_way))
                $where['house_type'] = $rental_way == 1 ? 2 : 1;
            $where['type'] = I('house_type');
            $where['source_id'] = I('room_id');
            $list = $RBR->getData($where , array('start_time' , 'end_time' , 'stop_id' , 'remark') , 99999 , 0 , 'create_time desc');
            return_success($list);
        }

        function saveReserveBackRentalAction()
        {


            PV(array('reserve_back_id' , 'back_rental_time'));

            $reserve_back_id = I('reserve_back_id');
            $back_rental_time = I('back_rental_time');
            $remark = I('remark');
            $data = array();
            $data['back_rental_time'] = strtotime($back_rental_time);
            $data['remark'] = $remark;
            $RBR = M('ReserveBackRental');

            $save = $RBR->edit(array('reserve_back_id' => $reserve_back_id) , $data);

            if (!$save)
                return_error(142);
            return_success();
        }

        function saveStopRoomAction()
        {
            PV(array('stop_id' , 'end_time'));

            $reserve_back_id = I('stop_id');
            $back_rental_time = I('end_time');
            $remark = I('remark');
            $data = array();
            $data['end_time'] = strtotime($back_rental_time);
            $data['remark'] = $remark;
            $RBR = M('StopHouse');
            $save = $RBR->edit(array('stop_id' => $reserve_back_id) , $data);
            if (!$save)
                return_error(142);
            return_success();
        }

    }
    