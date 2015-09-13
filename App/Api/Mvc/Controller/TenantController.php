<?php

    namespace App\Api\Mvc\Controller;

    use App\Web\Helper;

    class TenantController extends \App\Api\Lib\Controller
    {

        public function getContractListAction()
        {
            $end_time = I('end_time');

            $search = array();
            $to_day = strtotime(date('Y-m-d 00:00:00'));
            //搜索关键词
            $search['keywords'] = I('keywords' , '' , 'trim');

            $house_type = I('house_type');
            $focus_id = I('focus_id');
            $house_id = I('house_id');
            $room_id = I('room_id');

            $search['focus_id'] = $focus_id;
            $search['house_id'] = $house_id;
            $search['room_id'] = $room_id;
            $company_id = $this->getCompanyId();
            $company_model = new \Common\Model\Erp\Company();
            $company_info = $company_model->getOne(array('company_id' => $company_id));
            switch ($company_info['pattern'])
            {
                //分散式
                case '01':
                    $search['house_type'] = array(1);
                    break;
                //集中式
                case '10':
                    $search['house_type'] = array(2);
                    break;
                //集中式
                case '11':
                    $search['house_type'] = array(1,2);
                    break;
            }


            switch ($end_time)
            {

                //已到期
                case '0':
                    $search['end_dead_line'] = time();
                    break;
                //今日到期
                case '1':
                    $search['start_dead_line'] = $to_day;
                    $search['end_dead_line'] = time();
                    break;
                //三天内到期
                case '2':
                    $search['start_dead_line'] = $to_day;
                    $search['end_dead_line'] = strtotime('+3 day' , $to_day);
                    break;
                //一星期
                case '3':
                    $search['start_dead_line'] = $to_day;
                    $search['end_dead_line'] = strtotime('+7 day' , $to_day);
                    break;
                //一月
                case '4':
                    $search['start_dead_line'] = $to_day;
                    $search['end_dead_line'] = strtotime('+30 day' , $to_day);
                    break;
                //已终止
                case '5':
                    $search['is_stop'] = 1;
                    break;
            }

            $nex_pay_time = I('nex_pay_time');
            switch ($nex_pay_time)
            {
                //今日到期
                case '1':
                    $search['start_next_pay_time'] = $to_day;
                    $search['end_next_pay_time'] = time();
                    break;
                //三天内到期
                case '2':
                    $search['start_next_pay_time'] = $to_day;
                    $search['end_next_pay_time'] = strtotime('+3 day' , $to_day);
                    break;
                //一星期
                case '3':
                    $search['start_next_pay_time'] = $to_day;
                    $search['end_next_pay_time'] = strtotime('+7 day' , $to_day);
                    break;
            }



            $page = I('page' , '1');
            $page = is_numeric($page) && $page >= 1 ? (int) $page : 1;
            $size = I('size' , '10');
            $size = is_numeric($size) && $size <= 30 ? (int) $size : 10;
            

            $t1 = new \App\Api\Helper\Tenant();
            $list = $t1->getAllTenant($company_id , $search , $page , $size , $this->getUserInfo());//取出公司下所有合同

            $data = array(
                'page' => $list['page'] ,
                'list' => array() ,
            );

            $list['data'] = is_array($list['data']) ? $list['data'] : array();
            $time = time();
            foreach ($list['data'] as $info)
            {

                $house_name = emptys($info['house_name']) ? $info['flat_name'] : $info['house_name'];
                $facilities = emptys($info['room_config']) ? $info['public_facilities'] : $info['room_config'];
                $data['list'][] = array(
                    'contract_id' => $info['contract_id'] ,
                    'house_name' => (string) $house_name ,
                    'house_id' => $info['house_id'] ,
                    'room_id' => is_numeric($info['room_focus_id']) ? $info['room_focus_id'] : $info['room_id'] ,
                    'room_type' => (string) $info['room_type'] ,
                    'signing_time' => $info['signing_time'] ,
                    'next_pay_time' => $info['next_pay_time'] ,
                    'is_settlement' => $info['is_settlement'] ,
                    'dead_line' => $info['dead_line'] ,
                    'end_line' => $info['end_line'] ,
                    'tenant_id' => $info['tenant_id'] ,
                    'rental_id' => $info['rental_id'] ,
                    'is_evaluate' => $info['is_evaluate'] ,
                    'facilities' => explode('-' , $facilities) ,
                    'is_end' => $info['end_line'] > $time ? 0 : 1 ,
                );
            }
            unset($list);
            return_success($data);
        }

        public function commentAction()
        {

            PV(array('rental_id' , 'contract_id' , 'score'));

            $user = $this->getUserInfo();
            $rental_id = I('rental_id');
            $contract_id = I('contract_id');

            $score = I('score');

            if (!is_numeric($score) || $score < 1 || $score > 5)
            {
                return_error(131 , '评分数值不正确');
            }
            //小于=3分 必须填写原因
            $Remarks = I('remarks' , '' , 'trim');
            if ($score <= 3 && emptys($Remarks))
            {
                return_error(139);
            }
            elseif (emptys($Remarks))
            {

                //评价3分以上 且 在没用户评论的时候 的默认评论
                $Remarks = '自定义评论';
            }

            $contract_count = M('TenantContract')->getCount(array(
                'contract_id' => $contract_id ,
                'company_id' => $user['company_id'] ,
                'is_evaluate' => '0' ,
                'is_delete' => '0' ,
            ));

            //无权限评价 或者已经评价过
            if ($contract_count <= 0)
                return_error(138);

            //验证租客是否属于该合同
            $rental_count = M('Rental')->getCount(array('contract_id' => $contract_id));
            if ($rental_count <= 0)
                return_error(138);


            $data = array();
            $data['user_id'] = $user['user_id'];//用户id
            $data['company_id'] = $user['company_id'];//公司id
            $data['rental_id'] = $rental_id;//租住编号(id)
            $data['create_time'] = time();
            $data['score'] = (int) $score * 20;//分数 后台去*20
            $data['remark'] = $Remarks;//评分备注 
            $data['reason'] = '';

            $comment = M('Evaluate');
            $comment->Transaction();
            if (!$comment->insert($data))
            {
                $comment->rollback();
                return return_error(140);
            }
            $tc = new \App\Web\Helper\TenantContract();
            $data = array(
                'is_evaluate' => 1
            );
            if (!$tc->editTenantContract($data , $contract_id))
            {
                $comment->rollback();
                return return_error(140);
            }
            $comment->commit();
            return_success();
        }

        function getContractInfoAction()
        {
            PV(array('contract_id'));

            $M = M('TenantContract');
            $select = $M->getSqlObject()->select();
            $Fee = H('Fee');

            $FeeModel = M('Fee');
            $contract_id = I('contract_id');
            $company_id = $this->getCompanyId();
            $result = $select->from(array('tc' => 'tenant_contract'))->columns(array(
                        'contract_id' ,
                        'custom_number' ,
                        'parent_id' ,
                        'rent' ,
                        'advance_time' ,
                        'signing_time' ,
                        'dead_line' ,
                        'end_line' ,
                        'deposit' ,
                        'detain' ,
                        'pay' ,
                        'next_pay_time' ,
                        'deposit' ,
                        'remark' ,
                        'is_haveson',
                    ))->join(
                            array('r' => 'rental')
                            , 'tc.contract_id=r.contract_id'
                            , array(
                        'house_id' , 'room_id' , 'house_type' , 'tenant_id'
                    ))->where(
                            array(
                                'tc.contract_id' => $contract_id ,
                                'tc.company_id' => $company_id ,
                                'tc.is_delete' => 0 ,
                            )
                    )->execute();

            if (!emptys($result))
            {
                $result = $result[0];
                $tenant_list = M('ContractRental')->getTenant($contract_id);
                $tenant_list = is_array($tenant_list) ? $tenant_list : array();

                foreach ($tenant_list as &$info)
                {
                    $info = array(
                        'tenant_id' => $info['tenant_id'] ,
                        'name' => $info['name'] ,
                        'gender' => $info['gender'] ,
                        'phone' => $info['phone'] ,
                        'idcard' => $info['idcard'] ,
                        'from' => $info['from'] ,
                    );
                }

                $result['tenant_list'] = $tenant_list;
                //费用信息
                $result['fee_list'] = array();


                if ($result['house_type'] == 1)
                {
                    //合同的费用需要将房间和房源的一起展示 
                    $fee_list = $Fee->getRoomFeeInfo($result['room_id'] , '' , $FeeModel::SOURCE_DISPERSE_ROOM);

                    $fee_list2 = $Fee->getRoomFeeInfo($result['house_id'] , '' , $FeeModel::SOURCE_DISPERSE);

                    $fee_list = array_merge($fee_list , $fee_list2);
                    $temp = array();
                    //删除重复的费用
                    foreach ($fee_list as $key => $val)
                    {

                        if (isset($temp[$val['fee_type_id']]))
                            unset($fee_list[$key]);

                        $temp[$val['fee_type_id']] = $val['fee_type_id'];
                    }
                }
                else
                {

                    $fee_list = $Fee->getRoomFeeInfo($result['room_id'] , '' , $FeeModel::SOURCE_FOCUS);
                    if (emptys($fee_list))
                    {
                        $flat_info = M('RoomFocus')->getOne(array('room_focus_id' => $result['room_id']) , array('flat_id'));
                        $fee_list = $Fee->getRoomFeeInfo($flat_info['flat_id'] , '' , $FeeModel::SOURCE_FLAT);
                    }
                }
                if (count($fee_list) > 0)
                    $result['fee_list'] = $fee_list;
            } else {
                return_error(131, '未获取到合同详细！');
            }

            $r_info = M('Rental')->getOne(array('contract_id' => $contract_id));

            $room_type = $r_info['house_type'];
            if ($room_type == 2)
            {
                $type = 4;
            }
            elseif ($r_info['room_id'] == 0)
            {
                $type = 1;
            }
            else
            {
                $type = 2;
            }
            $time = time();
            if (count($result) > 0)
                $result['type'] = $type;
            $result['is_end'] = ($result['end_line'] != $result['dead_line'] && $result['end_line'] < $time ? 1 : 0);
            return_success($result);
        }

        function getRoomContractInfoAction()
        {

            $house_id = (int) I('house_id');
            $room_id = (int) I('room_id');
            $house_type = (int) I('house_type');
            $company_id = $this->getCompanyId();
            $Fee = H('Fee');
            $FeeModel = M('Fee');
            $ROOM = new \App\Api\Helper\Room();
            $result = $ROOM->getRoomContract($company_id , $house_type , $house_id , $room_id, $res = TRUE);
            if (!$result)
            {
                $result = $ROOM->getRoomContract($company_id , $house_type , $house_id , $room_id, $res = FALSE);
            }
            if (!emptys($result))
            {
                $result = $result[0];
                $contract_id = $result['contract_id'];
                $tenant_list = M('ContractRental')->getTenant($contract_id);
                $tenant_list = is_array($tenant_list) ? $tenant_list : array();

                foreach ($tenant_list as &$info)
                {
                    $info = array(
                        'tenant_id' => $info['tenant_id'] ,
                        'name' => $info['name'] ,
                        'gender' => $info['gender'] ,
                        'phone' => $info['phone'] ,
                        'idcard' => $info['idcard'] ,
                        'from' => $info['from'] ,
                    );
                }
                $result['tenant_list'] = $tenant_list;
                //费用信息
                $result['fee_list'] = array();
                if ($result['house_type'] == 1)
                {

                    //合同的费用需要将房间和房源的一起展示 
                    $fee_list = $Fee->getRoomFeeInfo($result['room_id'] , '' , $FeeModel::SOURCE_DISPERSE_ROOM);

                    $fee_list2 = $Fee->getRoomFeeInfo($result['house_id'] , '' , $FeeModel::SOURCE_DISPERSE);

                    $fee_list = array_merge($fee_list , $fee_list2);
                    $temp = array();
                    //删除重复的费用
                    foreach ($fee_list as $key => $val)
                    {

                        if (isset($temp[$val['fee_type_id']]))
                            unset($fee_list[$key]);

                        $temp[$val['fee_type_id']] = $val['fee_type_id'];
                    }
                }
                else
                {

                    $fee_list = $Fee->getRoomFeeInfo($result['room_id'] , '' , $FeeModel::SOURCE_FOCUS);
                    if (emptys($fee_list))
                    {
                        $flat_info = M('RoomFocus')->getOne(array('room_focus_id' => $result['room_id']) , array('flat_id'));
                        $fee_list = $Fee->getRoomFeeInfo($flat_info['flat_id'] , '' , $FeeModel::SOURCE_FLAT);
                    }
                }
                if (count($fee_list) > 0)
                    $result['fee_list'] = $fee_list;
            } else {
                return_error(131, '未获取到合同详细！');
            }

            $room_type = $house_type;
            if ($room_type == 2)
            {
                $type = 4;
            }
            elseif ($room_id == 0)
            {
                $type = 1;
            }
            else
            {
                $type = 2;
            }
            if (count($result) > 0)
                $result['type'] = $type;
            $result['is_end'] = ($result['end_line'] != $result['dead_line'] && $result['end_line'] < $time ? 1 : 0);
            return_success($result);
        }

        public function GetCommentAction()
        {

            PV(array('rental_id'));
            $company_id = $this->getCompanyId();
            $rental_id = I('rental_id');
            $result = M('Evaluate')->getOne(array(
                'company_id' => $company_id ,
                'rental_id' => $rental_id ,
            ));

            if (!emptys($result))
            {
                $star = $result['score'] / 20;
                $result['star'] = $star >= 1 ? "$star" : "1";
            }
            else
            {
                return_error(203);
            }
            return_success($result);
        }

        public function addContractAction()
        {

            PV(array(
                'room_id' , 'type' , 'custom_number' , 'signing_time' , 'dead_line' , 'rent' , 'deposit' , 'detain' , 'pay' , 'advance_time' , 'tenant_list' ,
            ));

            $data = array();
            $data['remark'] = I('remark' , '' , 'trim');
            $data['custom_number'] = I('custom_number' , 'trim');
            $signing_time = I('signing_time' , '' , 'strtotime');
            $dead_line = I('dead_line' , '' , 'strtotime');
            if (!is_time($signing_time) || !is_time($dead_line) || $dead_line < $signing_time)
                return_error(131 , '合同起始时间不正确');
            $rent = I('rent' , '' , 'trim');
            if (!is_numeric($rent))
                return_error(131 , '租金金额不正确');
            $deposit = I('deposit' , '' , 'trim');
            if (!is_numeric($deposit))
                return_error(131 , '押金金额不正确');
            $detain = I('detain');
            $pay = I('pay');
            if (!is_zzs($detain) || !is_zzs($pay))
                return_error(131 , '压付格式不正确');
            $advance_time = I('advance_time');
            if (!is_zzs($advance_time))
                return_error(131 , '提前付款天数格式不正确');
            $tenant_list = I('tenant_list' , '' , 'trim');
            $add_contract_id = I('contract_id');
            $tenant_data = array();

            $tenant_list = is_array($tenant_list) ? $tenant_list : (array) json_decode($tenant_list , true);

            foreach ($tenant_list as $tenant_info)
            {

                if (emptys($tenant_info['name'] , $tenant_info['phone'] , $tenant_info['gender'] , $tenant_info['idcard']))
                    continue;
                $tenant_data[] = array(
                    'phone' => trim($tenant_info['phone']) ,
                    'gender' => $tenant_info['gender'] == 2 ? 2 : 1 ,
                    'name' => trim($tenant_info['name']) ,
                    'idcard' => trim($tenant_info['idcard']) ,
                    'from' => trim($tenant_info['from']) ,
                );
            }
            unset($tenant_list);
            if (emptys($tenant_data))
                return_error(131 , '至少填写一个租客信息');

            //验证是否有权限添加房间的合同
            $user = $this->getUserInfo();
            $id = I('room_id');
            $type = I('type');
            $type = $type == 1 ? 3 : $type;

            $room_info = H('Room')->getUserRoomInfo($user , $type , $id);

            if (!is_array($room_info))
                return_error(127 , ',无权限操作');

            //  if ($room_info['status'] == 2)
            //    return_error(127 , ',该房源已经在出租状态,不能添加合同');

            $renta_room_id = $id;
            if ($type != 4)
            {
                //分散式房源
                if ($room_info['rental_way'] == 2)
                {
                    $renta_room_id = 0;
                }
                $house_type = 1;
                $house_id = $room_info['house_id'];
            }
            else
            {
                //集中式房源
                $house_type = 2;
                $house_id = 0;
            }

            $TC = M('TenantContract');
            $TC->Transaction();
            $company_id = $this->getCompanyId();
            $data['rent'] = $rent;
            $data['parent_id'] = (int) $add_contract_id;
            $data['signing_time'] = $signing_time;
            $data['dead_line'] = $dead_line;
            $data['deposit'] = $deposit;
            $data['detain'] = $detain;
            $data['advance_time'] = $advance_time;
            $data['pay'] = $pay;
            $data['occupancy_time'] = $signing_time;
            $data['end_line'] = $dead_line;
            $data['company_id'] = $company_id;
            $data['is_evaluate'] = 0;
            $data['is_settlement'] = 0;
            $data['is_renewal'] = 2;
            $data['pay_num'] = $pay;
            $data['next_pay_time'] = $signing_time;
            //$data['next_pay_time'] = $pay > 0 ? strtotime("+$pay month" , $signing_time) : $dead_line;
            //$data['next_pay_time'] = $advance_time > 0 ? $data['next_pay_time'] - ($advance_time * 86400) : $data['next_pay_time'];
            $data['next_pay_money'] = $pay > 0 ? $rent * $pay : $rent;
            //添加合同信息.

            $contract_id = $TC->insert($data);
            if (!$contract_id)
                return_error(127, '合同添加失败!');
            //继续添加租客信息
            $R = M('Rental');
            $T = M('Tenant');
            $CR = M('ContractRental');
            $time = time();
            foreach ($tenant_data as $key => $tenant_info)
            {
                $where = $tenant_info;
                unset($where['gender']);
                $tenant_db_info = $T->getOne(array('idcard' => $tenant_info['idcard'] , 'company_id' => $company_id));
                //租客已存在
                if (count($tenant_db_info) > 0)
                {
                    $tenant_id = $tenant_db_info['tenant_id'];
                    //修改租客信息
                    $T->edit(array('tenant_id' => $tenant_id) , $tenant_info);
                }
                else
                {
                    $tenant_info['company_id'] = $company_id;
                    $tenant_id = $T->insert($tenant_info);
                }

                if (!is_numeric($tenant_id))
                {
                    $TC->rollback();
                    return_error('127' , ',储存租客信息失败');
                }
                //将第一个租客与房间绑定
                if ($key == 0)
                {
//                    $where = array(
//                        'house_id' => (int) $house_id ,
//                        'house_type' => $house_type ,
//                        'room_id' => $renta_room_id ,
//                    );
//                    $count = $R->getCount($where);    if ($count > 0)
//                    {
//                        $rental_id = $R->edit($where , array(
//                            'house_id' => (int) $house_id ,
//                            'house_type' => $house_type ,
//                            'contract_id' => $contract_id ,
//                            'room_id' => $renta_room_id ,
//                            'tenant_id' => $tenant_id ,
//                            'is_delete' => 0 ,
//                        ));
//                    }
//                    else
//                    {
//                   
//                    }
                    $rental_id = $R->insert(array(
                        'house_id' => (int) $house_id ,
                        'house_type' => $house_type ,
                        'contract_id' => $contract_id ,
                        'room_id' => $renta_room_id ,
                        'tenant_id' => $tenant_id ,
                        'is_delete' => 0 ,
                        'source' => '手机端添加' ,
                        'source_id' => '0' ,
                    ));

                    if (!$rental_id)
                    {
                        $TC->rollback();
                        return_error('127' , ',储存租客信息失败');
                    }
                }


                $cr_id = $CR->insert(array(
                    'contract_id' => $contract_id ,
                    'tenant_id' => $tenant_id ,
                    'creat_time' => $time ,
                    'is_delete' => 0 ,
                ));

                //合同与租客进行绑定
                if (!$cr_id)
                {
                    $TC->rollback();
                    return_error('127' , ',储存租客信息失败');
                }
            }

            $ROOM = H('Room');

            if ($house_type == 1 && $room_info['rental_way'] == 2)
            {
                $rental_way = 2;
                $pattern = 1;
                $id = $room_info['house_id'];
            }
            elseif ($house_type == 1 && $room_info['rental_way'] == 1)
            {
                $pattern = 1;
                $rental_way = 1;
            }
            else
            {
                $rental_way = '';
                $pattern = 2;
            }


            $save = $ROOM->setRoomStatus($user , array('status' => 2) , $id , $pattern , $rental_way);

            if (!$save)
            {
                return_error('127' , ',未能改变房间状态');
            }


            //续租时 将旧合同设置为 已续租
            if (is_numeric($add_contract_id))
            {
                $TC->edit(array('contract_id' => $add_contract_id , 'company_id' => $company_id) , array('is_haveson' => 1));
            }


            $TC->commit();
            delBackLog('tenant_contract', $add_contract_id);
            return_success(array('contract_id' => $contract_id));
        }

        function addContractaTenantAction()
        {
            PV(array(
                'contract_id' ,
                'name' ,
                'phone' ,
                'idcard' ,
                'gender' ,
                'from' ,
            ));

            $name = I('name');
            $phone = I('phone');
            $gender = I('gender');
            $idcard = I('idcard');
            $contract_id = I('contract_id');
            $from = I('from');
            $time = time();
            if (strlen($phone) != 11)
                return_error(131 , '手机号格式不正确');
            $company_id = $this->getCompanyId();
            $tc_count = M('TenantContract')->getCount(array(
                'contract_id' => $contract_id ,
                'company_id' => $company_id ,
            ));

            //验证是否为公司下的合同
            if ($tc_count == 0)
            {
                return_error(504);
            }

            $data = array(
                'phone' => trim($phone) ,
                'gender' => $gender == 2 ? 2 : 1 ,
                'name' => trim($name) ,
                'idcard' => trim($idcard) ,
                'company_id' => $company_id ,
                'from' => $from ,
            );

            $T = M('Tenant');
            $T->Transaction();
            $t_info = $T->getOne(array(
                'idcard' => $idcard ,
                'company_id' => $company_id ,
            ));
            //租客已存在 则编辑租客信息
            if (!emptys($t_info))
            {
                $tenant_id = $t_info['tenant_id'];
                $result = $T->edit(array('tenant_id' => $tenant_id) , $data);
                if (!$result)
                    return_error(127 , '租客信息保存失败');
            }
            else
            {
                $tenant_id = $T->insert($data);
                if (!$tenant_id)
                    return_error(127 , '租客信息保存失败');
            }
            $CR = M('ContractRental');
            $count = $CR->getCount(
                    array(
                        'contract_id' => $contract_id ,
                        'tenant_id' => $tenant_id
                    )
            );
            if ($count > 0)
            {
                return_error(127 , '租客已经存在于该合同');
                $T->rollback();
            }
            $cr_id = $CR->insert(array(
                'contract_id' => $contract_id ,
                'tenant_id' => $tenant_id ,
                'creat_time' => $time ,
                'is_delete' => 0 ,
            ));

            if (!$cr_id)
            {
                $T->rollback();
                return_error(127 , '租客信息保存失败');
            }
            $T->commit();
            return_success(array('tenant_id' => $tenant_id));
        }

        function saveAction()
        {
            PV(array(
                'tenant_list' ,
            ));
            $list = I('tenant_list' , '' , 'trim');

            $T = M('Tenant');
            $company_id = $this->getCompanyId();

            if (is_string($list))
            {
                $list = json_decode(trim($list) , true);
            }

            if (!is_array($list))
                return_error(127 , '数据格式不正确');

            foreach ($list as $info)
            {
                $tenant_id = $info['tenant_id'];
                $name = $info['name'];
                $phone = $info['phone'];
                $gender = $info['gender'];
                $idcard = $info['idcard'];
                $from = $info['from'];

                if (strlen($phone) != 11)
                    return_error(131 , '手机号格式不正确');

                $t_count = $T->getCount(array(
                    'tenant_id' => $tenant_id ,
                    'company_id' => $company_id ,
                ));

                if ($t_count == 0)
                {
                    return_error(504);
                }

                $data = array(
                    'phone' => trim($phone) ,
                    'gender' => $gender == 2 ? 2 : 1 ,
                    'name' => trim($name) ,
                    'idcard' => trim($idcard) ,
                    'company_id' => $company_id ,
                    'from' => $from ,
                );

                $result = $T->edit(array('tenant_id' => $tenant_id , 'company_id' => $company_id) , $data);

                if (!$result)
                    return_error(127 , '租客信息保存失败');
            }
            return_success();
        }

        function getListAction()
        {
            $company_id = $this->getCompanyId();

            $search_idcard = I('search_idcard');
            $search_phone = I('search_phone');
            $search_name = I('search_name');
            $search_ids = I('tenant_ids');
            $T = M('Tenant');
            $where = new \Zend\Db\Sql\Where();

            $where->equalTo('company_id' , $company_id);
            $where->equalTo('is_delete' , 0);
            if (!emptys($search_ids))
            {
                $where->in('tenant_id' , explode(',' , $search_ids));
            }
            elseif (!emptys($search_idcard))
            {
                $where->like('idcard' , "%$search_idcard%");
            }
            else
            if (!emptys($search_phone))
            {
                $where->like('phone' , "%$search_phone%");
            }
            else
            if (!emptys($search_name))
            {
                $where->like('name' , "%$search_name%");
            }


            $list = $T->getData($where);

            foreach ($list as $key => $info)
            {
                unset($list[$key]['is_delete']);
            }
            return_success($list);
        }

        function saveContractAction()
        {

            PV('contract_id');
            $remark = I('remark' , '' , 'trim');
            $TC = M('TenantContract');
            $rentalModel = M('Rental');
            $contract_id = I('contract_id');
            $company_id = $this->getCompanyId();
            $is_stop = I('is_stop');
            $is_fee = I('is_fee');
            $data = array();
            if ($is_fee == 1)
            {
                $info = $TC->getOne(array('contract_id' => $contract_id ,'company_id' => $company_id), 
                       array('pay' , 'next_pay_time' , 'advance_time' , 'signing_time', 'dead_line' , 'is_haveson' , 'rent')
                        );

                if ($info['next_pay_time'] > 0 && $info['pay'] > 0)
                {
                    if ($info['next_pay_time'] == $info['signing_time'])
                    {
                        $res = false;
                    } else {
                        $res = true;
                    }
                    $serial_helper = new \Common\Helper\Erp\SerialNumber();
                    $serial_helper->updateContractPayTime($contract_id, $info, $res);
                    //将待办的下次收租时间更改
                    //M('Todo')->edit(array('module' => 'tenant_contract_shouzu' , 'entity_id' => $contract_id) , array('deal_time' => $data['next_pay_time']));
                }
            } elseif ($is_stop == 1)
            {
                $data['is_stop'] = 1;
                $data['end_line'] = time();
                $save = $TC->edit(array(
                    'contract_id' => $contract_id ,
                    'company_id' => $company_id ,
                        ) , $data);
                $out_rental = $rentalModel->edit(array('contract_id' => $contract_id), array('is_delete' => 1));
                if (!$save || !$out_rental)
                {
                    return_error(129);
                }
            } else {
                $data['remark'] = $remark;
                $save = $TC->edit(array(
                    'contract_id' => $contract_id ,
                    'company_id' => $company_id ,
                        ) , $data);
                if (!$save)
                {
                    return_error(129);
                }
            }


            //退租时要求删除未收费的抄表信息
            if ($is_stop == 1)
            {
                $info = M('Rental')->getOne(array(
                    'contract_id' => $contract_id ,
                        ) , array('house_id' , 'house_type' , 'room_id'));
                if (count($info) > 1)
                {
                    M('MeterReading')->edit($info, array('is_sf' => 1));
                    M('MeterReadingMoney')->edit($info, array('is_settle' => 1));
                }
                delBackLog('tenant_contract_shouzu', $contract_id);
                delBackLog('tenant_contract', $contract_id);
            }
            return_success();
        }

        function getScoreAction()
        {
            PV('idcard');
            $idcard = I('idcard');
            $select = M('Tenant')->getSqlObject()->select();

            $count = $select->from(array('e' => 'evaluate'))->columns(array('sum' => new \Zend\Db\Sql\Expression('sum(score)') , 'count' => new \Zend\Db\Sql\Expression('count(score)')))->leftjoin(array('r' => 'rental') , 'r.rental_id=e.rental_id' , array())->leftjoin(array('t' => 'tenant') , 'r.tenant_id=t.tenant_id' , array())->where(array('t.idcard' => $idcard))->execute();

            $data = array(
                'idcard' => $idcard ,
                'sum' => 0 ,
                'count' => 0 ,
                'avg_score' => 0 ,
            );
            if (isset($count[0]))
            {
                $info = $count[0];
                $data = array(
                    'sum' => (int) $info['sum'] ,
                    'count' => (int) $info['count'] ,
                    'avg_score' => (int) $info['sum'] > 0 ? round($info['sum'] / $info['count'] , 2) : 0 ,
                );
            }
            return_success($data);
        }

        function getContractFlatHouseListAction()
        {
            PV('type');
            $type = I('type');
            $select = M('User')->getSqlObject()->select();
            $company_id = $this->getCompanyId();
            $company_model = new \Common\Model\Erp\Company();
            $company_info = $company_model->getOne(array('company_id' => $company_id));
            $data = array();
            if ($company_info['pattern'] == 01 && $type != 1) {
                return return_success(array());
            } elseif ($company_info['pattern'] == 10 && $type != 2) {
                return return_success(array());
            }
            switch ($type)
            {
                case 1:
                    $all_room = array();
                    $HouseList = $select->from(array('h' => 'house_view'))->leftjoin(array('r' => 'rental') , getExpSql('h.house_id = r.house_id AND r.room_id= h.record_id') , array())->where(array('r.is_delete' => '0' , 'h.company_id' => $company_id , 'r.house_type' => $type));

                    if (!$this->IsManager())
                    {
                        $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                        $permisions->VerifyDataCollectionsPermissionsModel($select , 'h.house_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $this->getUserId() , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
                    }

                    $HouseList = $select->execute();
                    $HouseList = getArrayKeyClassification($HouseList , 'house_id');
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

                            if (isset($all_room[$info['house_id']][$info['record_id']]))
                                continue;
                            $all_room[$info['house_id']][$info['record_id']] = 1;


                            if ($info['rental_way'] == '1')
                            {
                                $configure = array();
                                $room_configure = explode('-' , $info['room_config']);
                                foreach ($configure_config as $config)
                                {
                                    if (array_search($config , $room_configure))
                                        $configure[] = $config;
                                }

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
                        }
                    }

                    foreach ($data as $key => $info)
                    {
                        $data[$key]['list'] = array_values($info['list']);
                    }
                    $data = array_values($data);
                    break;

                case 2:
                    $all_room = array();
                    $select->from(array('rf' => 'room_focus'))->leftjoin(array('r' => 'rental') , getExpSql('rf.room_focus_id= r.room_id') , array())->where(array('r.is_delete' => '0' , 'rf.company_id' => $company_id , 'r.house_type' => $type));

                    if (!$this->IsManager())
                    {
                        $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                        $permisions->VerifyDataCollectionsPermissionsModel($select , 'rf.flat_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $this->getUserId() , \Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
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


                        if (isset($all_room[$info['flat_id']][$info['room_focus_id']]))
                            continue;
                        $all_room[$info['flat_id']][$info['room_focus_id']] = 1;

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
                    $data = array_values($result);
                    break;
            }
            return_success($data);
        }

        function deleteContractTenantAction()
        {
            PV(array('contract_id' , 'tenant_ids'));
            $contract_id = I('contract_id');
            $company_id = $this->getCompanyId();
            $tc_count = M('TenantContract')->getCount(array(
                'contract_id' => $contract_id ,
                'company_id' => $company_id ,
            ));
            //验证是否为公司下的合同
            if ($tc_count == 0)
            {
                return_error(504);
            }
            $tenant_ids = explode(',' , trim(I('tenant_ids') , ','));
            $tenant_ids = array_filter(array_unique($tenant_ids));

            if (emptys($tenant_ids))
                return_error(142 , '租客信息不正确');

            $CR = M('ContractRental');
            $tenant_list = $CR->getData(array('contract_id' => $contract_id , 'is_delete' => 0) , array('tenant_id'));
            $tenant_list = getArrayKeyClassification($tenant_list , 'tenant_id' , 'tenant_id');
            $tenant_delete_list = array_intersect($tenant_ids , $tenant_list);//计算传递的租客ID与数据库ID的交集
            //传递的租客信息 不完全正确

            if (count($tenant_delete_list) != count($tenant_ids))
                return_error(142 , '删除的租客不存在');

            if (count($tenant_delete_list) == count($tenant_list))
                return_error(142 , ',不能删除所有租客');
            $CR->Transaction();

            $del = $CR->edit(array('tenant_id' => $tenant_delete_list) , array('is_delete' => 1));
            if (!$del)
            {
                return_error(142);
            }
            //查询是否删除了主租客

            $R = M('Rental');
            $rental_info = $R->getOne(array('contract_id' => $contract_id));

            //删除了主租客
            if (isset($rental_info['tenant_id']) && array_search($rental_info['tenant_id'] , $tenant_delete_list) !== false)
            {
                //查找没被删除的租客
                $tenant_list = array_diff($tenant_list , $tenant_delete_list);//计算传递的租客ID与数据库ID的交集

                if (count($tenant_list) == 0)
                {
                    $CR->rollback();
                    return_error(142 , ',系统错误');
                }
                //提取一个租客设置为主租客
                $tenant_id = reset($tenant_list);

                $save = $R->edit(array('rental_id' => $rental_info['rental_id']) , array('tenant_id' => $tenant_id));

                if (!$save)
                {
                    $CR->rollback();

                    return_error(142 , ',系统错误');
                }
            }
            $CR->commit();
            return_success();
        }

    }
    