<?php

    namespace App\Api\Mvc\Controller;

    class ReserveController extends \App\Api\Lib\Controller
    {

        /**
         * 获取预定信息
         * @author 李明泷 | 2015年6月11日 10:28:36
         */
        public function getReserveInfoAction()
        {
            PV('reserve_id');
            $reserve = new \App\Api\Helper\Reserve();
            
            $reserve_id = I("reserve_id");
            if (!is_numeric($reserve_id))
            {
                return_error(131);
            }
            $CompanyId = $this->getCompanyId();
            $data = $reserve->getReserveInfo($reserve_id , $CompanyId);
            
            if (is_array($data) && count($data) > 0)
            {
                $data = $data[0];
                if ($data['house_type'] == 1)
                {
                    if ($data['rental_way'] == 1)
                    {
                        $room_model = new \Common\Model\Erp\Room();
                        $status = $room_model->getOne(array('room_id' => $data['room_id']))['status'];
                    }
                    else
                    {
                        $house_entirel_model = new \Common\Model\Erp\HouseEntirel();
                        $status = $house_entirel_model->getOne(array('house_id' => $data['house_id']))['status'];
                    }
                }
                else
                {
                    $room_focus_model = new \Common\Model\Erp\RoomFocus();
                    $status = $room_focus_model->getOne(array('room_focus_id' => $data['room_id']))['status'];
                }
                //$rental_way = 1;
                //if ($data['room_id'] == 0)
                //{
                //    $rental_way = 2;
                //}
                //$data['rental_way'] = (string) $rental_way;
                $data['status'] = $status;
            }
            return_success($data);
        }

        /**
         * 修改预定信息
         * @author yusj | 最后修改时间 2015年5月7日上午9:47:52
         */
        public function updateReserveAction()
        {
            $reserveModel = new \App\Api\Helper\Reserve();

            $name = I("name" , '');
            $phone = I("phone" , '');
            $idcard = I("idcard" , '');
            //预定相关信息
            $reserve_id = I("reserve_id" , '');
            $money = I("money" , '0');
            $stime = I("stime" , '0');
            $etime = I("etime" , '0');
            $mark = I("mark" , '');
            $source = I("source" , '0');
            //组装组合信息
            $tenant_data['phone'] = $phone;
            $tenant_data['name'] = $name;
            $tenant_data['idcard'] = $idcard;
            //预定信息
            $reserve_data['reserve_id'] = $reserve_id;
            $reserve_data['money'] = $money;
            $reserve_data['stime'] = strtotime($stime);
            $reserve_data['etime'] = strtotime($etime);
            $reserve_data['mark'] = $mark;
            $reserve_data['source'] = $source;
            
            $reserve_info = M('Reserve')->getOne(array('reserve_id' => $reserve_id));
            $module = ($reserve_info['house_type'] == 1 && $reserve_info['rental_way'] == 1) ? 'ROOM_RESERVE_OUT' : (($reserve_info['house_type'] == 1 && $reserve_info['rental_way'] == 2) ? 'HOUSE_RESERVE_OUT' : 'FOCUS_RESERVE_OUT') ;
            $todo_info = M('Todo')->getOne(array('module' => $module, 'entity_id' => $reserve_id));
            $old_date = date('Y', $todo_info['deal_time']);
            
            $new_content = substr_replace($todo_info['content'],date('Y-m-d', $reserve_data['etime']),strripos($todo_info['content'], $old_date),10);
            if ($reserveModel->updateReserve($tenant_data , $reserve_data))
            {
                $where = array('module' => $module, 'entity_id' => $reserve_id);
                $data = array('content' => $new_content, 'deal_time' => $reserve_data['etime']);
                M('Todo')->edit($where, $data);
                return_success();
            }
            else
            {
                return_error(129);
            }
        }

    }
    