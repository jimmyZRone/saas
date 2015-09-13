<?php

    namespace App\Api\Helper;

    class Reserve extends \Common\Helper\Erp\Reserve
    {

        /**
         * 获取预定信息
         *
         * @author yusj | 最后修改时间 2015年5月6日上午10:14:26
         */
        public function getReserveInfo($reserve_id , $company_id)
        {
            $reserveModel = new \Common\Model\Erp\Reserve();
            $sql = $reserveModel->getSqlObject();
            $select = $sql->select(array(
                'r' => 'reserve'
            ));
            $select->columns(array(
                'reserve_id' ,
                'room_id' ,
                'house_id' ,
                'house_type' ,
                'money' ,
                'stime' ,
                'etime' ,
                'source' ,
                'mark',
                'rental_way'
            ));
            $select->join(array(
                't' => 'tenant'
                    ) , 'r.tenant_id = t.tenant_id' , array(
                'name' ,
                'idcard' ,
                'phone' ,
                'gender' ,
                    ) , 'left');
            $select->where(array(
                "r.reserve_id" => $reserve_id ,
                    //  "t.company_id" => $company_id ,  以前租客不验证是否为公司 可能有错误数据
            ));

            $result = $select->execute();
            return $result;
        }

        /**
         * 增加房间预定
         *
         * @author yusj | 最后修改时间 2015年5月6日上午10:10:33
         */
        public function addReserve($tenant_data , $reserve_data)
        {
            $now_time = time();
            $tenantAppModel = new \App\Api\Helper\Tenant ();
            // 根据身份证判断用户是否存在
            $tenant_id_arr = $tenantAppModel->getTenantByIDCard($tenant_data ['idcard']);
            $reserveModel = new \Common\Model\Erp\Reserve (); // 预定模型
            $tenantModel = new \Common\Model\Erp\Tenant (); // 租客模型
            $reserveModel->Transaction();
            if (count($tenant_id_arr) > 0)
            {
                // 如果存在这个身份证，修改用户信息
                $tenant_id = $tenant_id_arr ['tenant_id'];
                if (!$tenantModel->edit(array(
                            'tenant_id' => $tenant_id
                                ) , $tenant_data))
                {
                    $reserveModel->rollback();
                    return false;
                }
            }
            else
            {
                // 不存在这个身份证 写入租客表
                // 一下是租客的默认值
                $tenant_data ['gender'] = '1';
                $tenant_data ['birthday'] = '0';
                $tenant_data ['address'] = '';
                $tenant_data ['emergency_phone'] = '';
                $tenant_data ['emergency_contact'] = '';
                $tenant_data ['nation'] = '';
                $tenant_data ['work_place'] = '';
                $tenant_data ['profession'] = '';
                $tenant_data ['email'] = '';
                $tenant_data ['remarks'] = '';
                $tenant_data ['create_time'] = $now_time;
                $tenant_data ['is_delete'] = '0';
                $tenant_data ['from'] = '';
                $tenant_id = $tenantModel->insert($tenant_data);
                if (!$tenant_id)
                {
                    $reserveModel->rollback();
                    return false;
                }
            }
            $reserve_data ['tenant_id'] = $tenant_id;
            $reserve_data ['create_time'] = $now_time;
            $reserve_data ['is_delete'] = '0';
            $reserve_data ['pay_type'] = '0';
            $reserve_id = $reserveModel->insert($reserve_data);
            if (!$reserve_id)
            {
                $reserveModel->rollback();
                return false;
            }
            else
            {
                $reserveModel->commit();
                return $reserve_id;
            }
        }

        /**
         * 取消预定
         *
         * @author yusj | 最后修改时间 2015年5月6日上午10:10:33
         */
        public function cancelReserve($reserve_id)
        {
            $ids_type = $this->getIDAndHouseType($reserve_id);
            if (count($ids_type) > 0)
            {
                $reserveModel = new \Common\Model\Erp\Reserve ();
                $reserveModel->Transaction();
                $result = $reserveModel->edit(array(
                    'reserve_id' => $reserve_id
                        ) , array(
                    'is_delete' => 1
                ));
                if (!$res)
                {
                    $reserveModel->rollback();
                    return false;
                }
                $house_id = $ids_type ['house_id'];
                $room_id = $ids_type ['room_id'];
                $house_type = $ids_type ['house_type'];
                // 集中式
                if (house_type == 2 && $house_id == 0)
                {
                    // 更新集中式房间的预定字段
                    $roomFocusModxel = new \Common\Model\Erp\RoomFocus ();
                    $result = $roomFocusModxel->edit(array(
                        'room_focus_id' => room_id
                            ) , array(
                        'is_yd' => 0
                    ));
                    if (!$result)
                    {
                        $reserveModel->rollback();
                        return false;
                    }
                    else
                    {
                        $reserveModel->commit();
                        return true;
                    }
                }
                // 分散式整租
                if (house_type == 1 && house_id > 0 && $room_id == 0)
                {
                    $houseEntirelModxel = new \Common\Model\Erp\HouseEntirel ();
                    $result = $houseEntirelModxel->edit(array(
                        'house_id' => $house_id
                            ) , array(
                        'is_yd' => 0
                    ));
                    if ($result)
                    {
                        $reserveModel->commit();
                        return true;
                    }
                    else
                    {
                        $reserveModel->rollback();
                        return false;
                    }
                }
                // 分散式合租
                if (house_type == 1 && house_id > 0 && $room_id > 0)
                {
                    $roomModxel = new \Common\Model\Erp\Room ();
                    $result = $roomModxel->edit(array(
                        'house_id' => $house_id ,
                        'room_id' => $room_id
                            ) , array(
                        'is_yd' => 0
                    ));
                    if ($result)
                    {
                        $reserveModel->commit();
                        return true;
                    }
                    else
                    {
                        $reserveModel->rollback();
                        return false;
                    }
                }
                return false;
            }
            else
            {
                return fasle;
            }
        }

        /**
         * 获取house_id
         * room_id
         * house_type
         * 方便取消预定
         *
         * @author yusj | 最后修改时间 2015年5月6日上午10:40:17
         */
        private function getIDAndHouseType($reserve_id)
        {
            $reserveModel = new \Common\Model\Erp\Reserve ();
            $result = $reserveModel->getOne(array(
                'reserve_id' => $reserve_id
                    ) , array(
                'reserve_id' ,
                'tenant_id' ,
                'house_id' ,
                'room_id' ,
                'house_type'
            ));
            return $result;
        }

        /**
         * 修改预定信息
         *
         * @author yusj | 最后修改时间 2015年5月7日上午9:49:08
         */
        public function updateReserve($tenant_data , $reserve_data)
        {
            $id_arr = $this->getIDAndHouseType($reserve_data['reserve_id']);

            if (count($id_arr) == 0)
            {
                return false;
            }
            $tenant_id = $id_arr ['tenant_id'];
            $reserveModel = new \Common\Model\Erp\Reserve ();
            $tenantModel = new \Common\Model\Erp\Tenant ();
            $reserveModel->Transaction();
            $result = $reserveModel->edit(array(
                'reserve_id' => $reserve_data ['reserve_id']
                    ) , $reserve_data);
            //echo $reserveModel->getLastSql();exit;
            if ($result)
            {
                $tenResult = $tenantModel->edit(array(
                    'tenant_id' => $tenant_id
                        ) , $tenant_data);

                if ($tenResult)
                {
                    $reserveModel->commit();
                    return true;
                }
                else
                {
                    $reserveModel->rollback();
                    return false;
                }
            }
            else
            {
                $reserveModel->rollback();
                return false;
            }
        }

    }
    