<?php

    namespace Common\Model\Erp;

    use Zend\Db\Sql\Where;

    class House extends \Common\Model\Erp
    {

        /**
         * 统计参数
         * @var unknown
         */
        const SUM_MONEY = "sum_money";
        const IS_RENTAL = "is_rental";
        const NOT_RENTAL = "not_rental";
        const IS_RESERVE = "is_reserve";
        const IS_YYTZ_C = "is_yytz";
        const IS_STOP = "is_stop";
        const ALL_HOUSE = "all_house";

        /**
         * 列表数据类型
         * @var unknown
         */
        const LIST_TYPE_HOUSE = "house";
        const LIST_TYPE_ROOM = "room";
        //是否预约退租
        const NOT_IS_YYTZ = 0;

        /**
         * 是否预约退租
         * @var unknown
         */
        const IS_YYTZ = 1;

        /**
         * 是否预定
         * @var unknown
         */
        const IS_YD = 1;
        //房源状态
        /**
         * 未租
         * @var unknown
         */
        const STATUS_NOT_RENTAL = 1;

        /**
         * 已出租
         * @var unknown
         */
        const STATUS_IS_RENTAL = 2;

        /**
         * 停用
         * @var unknown
         */
        const STATUS_IS_STOP = 3;

        /**
         * 装修中
         * @var unknown
         */
        const STATUS_MAJOR = 4;
        //房源出租类型
        /**
         * 整租
         * @var unknown
         */
        const RENTAL_WAY_Z = 2;

        /**
         * 合租
         * @var unknown
         */
        const RENTAL_WAY_H = 1;

        private $yichuzu_count = 0;//已出租

        /**
         *  获取房间统计信息
         * @param type $user
         * @param type $flat_id 筛选  公寓ID
         * @return array
         * @author Lms 2015年5月5日 15:24:28
         * 
         */

        public function getHouseRoomInfo($user , $flat_id = '')
        {
            $yichuzu_count = 0;
            $house_count = 0;//房源数
            $weichuzu_count = 0;//未出租
            $yiyuding_count = 0;//已预订
            $jizhong_money_count = 0;//集中房租
            $fensan_money_count = 0;//分散房租
            $jizhong_count = 0;//集中式房源
            $fensan_count = 0;//分散式房源
            //传递了公寓ID则不查找分散式内容
            $zhengzu_id = array();//整租的ID合辑
            $hezu_id = array();//合租的ID合辑
            $house_time = 0;//记录租房的总时长
            $house_rent_time = 0;//房间已租时长
            $this_month_day = 0;//当月已租天数
            $this_not_month_day = 0;//当月未租天数
            $this_year_day = 0;//当年已租天数
            $time = time();
            if ($flat_id <= 0)
            {
                $HE = new \Common\Model\Erp\HouseEntirel();
                $R = new \Common\Model\Erp\Room();
                //获取当前公司ID下的所有房屋
                $flat_house_list = $this->getData(array("company_id" => $user['company_id'] , "city_id" => $user['city_id'] , 'is_delete' => 0) , array('house_id' , 'rental_way' , 'create_time'));
                //print_r($flat_house_list);die();
                $flat_house_list = is_array($flat_house_list) ? $flat_house_list : array();

                foreach ($flat_house_list as $val)
                {
                    if ($val['rental_way'] == '1')
                    {
                        $hezu_id[] = $val['house_id'];
                        continue;
                    }
                    $zhengzu_id[] = $val['house_id'];
                    $house_day = $time - $val['create_time'];//房间存活天
                    $house_time += $house_day < 0 ? 0 : $house_day;
                }
                unset($flat_house_list);
                //根据房屋查询整租房间
                //分散式整租列表
                $suiteList = array();
                $sql_object = $R->getSqlObject();
                $house_select = $sql_object->select(array("h" => $this->getTableName()));
                $house_select->leftjoin(array("he" => $HE->getTableName()) , "h.house_id = he.house_id" , array("is_yd" , "money" , "status"));
                $house_select->leftjoin(array("c" => "community") , "h.community_id=c.community_id" , array("community_name" , 'business_string'));
                $house_select->leftjoin(array("a" => "area") , "c.area_id=a.area_id" , array("name"));
                //权限
                if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
                {
                	$permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                	$permisions->VerifyDataCollectionsPermissionsModel($house_select , 'h.house_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
                }
                $house_select->where(array("h.rental_way" => self::RENTAL_WAY_Z , "h.company_id" => $user['company_id'] , "a.city_id" => $user['city_id'] , "h.is_delete" => 0));
                $suiteList = $house_select->execute();
                foreach ($suiteList as $val)
                {
                    $fensan_count++;
                    if ($val['is_yd'] == House::IS_YD)
                        $yiyuding_count++;
                    if (is_numeric($val['money']))
                        $fensan_money_count +=$val['money'];
                    if ($val['status'] == House::STATUS_IS_RENTAL)
                        $yichuzu_count++;
                    else
                        $weichuzu_count++;
                }

                //分散式合租
                $room_list = array();
                if (count($hezu_id) > 0)
                {
                    $hezu_where = array("r.house_id" => $hezu_id , "a.city_id" => $user['city_id'] , 'r.is_delete' => 0 , "h.company_id" => $user['company_id']);
                }
                else
                {
                    $hezu_where = array('r.is_delete' => 0 , "a.city_id" => $user['city_id'] , "h.company_id" => $user['company_id']);
                }
                $rsql = $R->getSqlObject();
                $r_select = $rsql->select(array('r' => $R->getTableName()));
                $r_select->leftjoin(array('h' => 'house') , 'r.house_id=h.house_id');
                $r_select->leftjoin(array("c" => "community") , "h.community_id=c.community_id" , array("community_name" , 'business_string'));
                $r_select->leftjoin(array("a" => "area") , "c.area_id=a.area_id" , array("name"));
                //权限
                 if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
                {
                	$permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                	$permisions->VerifyDataCollectionsPermissionsModel($r_select , 'h.house_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
                }
                $r_select->where($hezu_where);
                $room_list = $r_select->execute();
                //print_r(str_replace('"', '', $r_select->getSqlString()));die();
                // $room_list = $R->getData( $hezu_where, array('status' , 'is_yd' , 'money' , 'create_time'));
                foreach ($room_list as $val)
                {
                    $fensan_count++;
                    if ($val['is_yd'] == $R::IS_YD)
                        $yiyuding_count++;

                    if (is_numeric($val['money']))
                        $fensan_money_count +=$val['money'];

                    if ($val['status'] == $R::STATIS_RENTAL)
                        $yichuzu_count++;
                    else
                        $weichuzu_count++;

                    $house_day = $time - $val['create_time'];//房间存活天
                    $house_time += $house_day < 0 ? 0 : $house_day;
                }
            }
            //分散式信息IF结尾     
            //集中式逻辑
            if ($flat_id != '-1')
            {

                $RF = new \Common\Model\Erp\RoomFocus();
                $room_where = new Where();
                //获取集中式房间信息
				$room_where->equalTo("rf.company_id", $user['company_id']);
				$room_where->equalTo("f.city_id", $user['city_id']);
				$room_where->equalTo("f.is_delete", 0);
				$room_where->equalTo('rf.is_delete', 0);
                if (is_numeric($flat_id) && $flat_id > 0)
                {
                    $room_where->equalTo('f.flat_id', $flat_id);
                }
                //集中式租房
                $sql = $RF->getSqlObject();
                $select = $sql->select(array("rf" => $RF->getTableName()));
                $select->join(array("f" => 'flat') , "rf.flat_id=f.flat_id");
                
                //权限
	            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
	            {
	                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
	                $permisions->VerifyDataCollectionsPermissionsModel($select , 'rf.flat_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
	            }
                $select->where($room_where);
                $room_focus = $select->execute();
                $jzs_sql = $RF->getLastSql();

                $jzs_ids = array();
                foreach ($room_focus as $val)
                {
                    //记录所有集中式ID
                    $jzs_ids[] = $val['room_focus_id'];
                    $jizhong_count++;
                    if ($val['is_yd'] == $RF::IS_YD)
                        $yiyuding_count++;

                    if (is_numeric($val['money']))
                        $jizhong_money_count +=$val['money'];

                    if ($val['status'] == $RF::STATUS_RENTAL)
                        $yichuzu_count++;
                    else
                        $weichuzu_count++;

                    $house_day = $time - $val['create_time'];//房间存活天
                    $house_time += $house_day < 0 ? 0 : $house_day;
                }
            } //集中式逻辑END
            //查找租客合同信息
            $hetong_list = array();

            if ((count($jzs_ids) + count($zhengzu_id) + count($hezu_id) ) > 0)
            {
                $RE = new \Common\Model\Erp\Rental();
                $where = new \Zend\Db\Sql\Where();
                $ORwhere = new \Zend\Db\Sql\Where();

                if (count($jzs_ids) > 0)
                {
                    $ORwhere = new \Zend\Db\Sql\Where();
                    $ORwhere->equalTo('house_type' , '2');
                    $ORwhere->in('room_id' , $jzs_ids);
                    $where->addPredicate($ORwhere , 'OR');
                }

                if (count($zhengzu_id) > 0)
                {
                    $ORwhere = new \Zend\Db\Sql\Where();
                    $ORwhere->equalTo('house_type' , '1');
                    $ORwhere->equalTo('room_id' , '');
                    $ORwhere->in('house_id' , $zhengzu_id);
                    $where->addPredicate($ORwhere , 'OR');
                }
                if (count($hezu_id) > 0)
                {
                    $ORwhere = new \Zend\Db\Sql\Where();
                    $ORwhere->equalTo('house_type' , '1');
                    $ORwhere->isNotNull('room_id');
                    $ORwhere->in('house_id' , $hezu_id);
                    $where->addPredicate($ORwhere , 'OR');
                }
                $where->equalTo('is_delete' , '0');
                $hetong_list = $RE->getData($where , array('contract_id'));
            }

            //查询房间合同出租日期
            $hetong_ids = getArrayValue($hetong_list , 'contract_id');
            $zaizhuyajin = 0;
            if (count($hetong_ids) > 0)
            {
                $TC = new \Common\Model\Erp\TenantContract();

                $hetong_list = $TC->getData(array('contract_id' => $hetong_ids , 'is_delete' => '0') , array('contract_id' , 'deposit' , 'signing_time' , 'dead_line' , 'end_line'));

                foreach ($hetong_list as $val)
                {
                    $house_day = $val['dead_line'] - $val['signing_time'];//房间存活天

                    if ($val['end_line'] > $time)
                    {
                        $zaizhuyajin+=(int) $val['deposit'];
                    }


                    $startMonth = strtotime(date("Y-m-01"));//当前月第一天
                    $endMonths = strtotime(date("Y-m-t"));//当前月最后一天
                    $startYear = strtotime(date('Y-01-01'));//今年的第一天
                    $endYear = strtotime(date('Y-12-t'));//今年的第一天
                    //结束时间大于等于在当月中
                    //签约时间是否在当月内
                    $is_this_month = 0;
                    $is_this_year = 0;//签约时间是否在当年内
                    $thisMonthDay = 0;//签约时在当月已经过了的天数
                    $thisYearDay = 0;//签约时已今年已经过了多少天
                    //签约时间在本月内 计算出是第几天
                    if ($val['signing_time'] >= $startMonth && $val['signing_time'] <= $endMonths)
                    {
                        $is_this_month = 1;
                        $thisMonthDay = ceil(($val['signing_time'] - $startMonth) / 86400);
                    }
                    //   dump(date('Y-m-d' , $val['signing_time']));
                    //计算当月租了几天
                    $months_day = 0;
                    if ($val['end_line'] >= $startMonth)
                    {
                        $this_time = time();
                        if ($val['end_line'] >= $this_time)
                        {
                            $months_day = ceil(($this_time - $startMonth) / 86400);
                        }
                        if ($val['end_line'] < $this_time)
                        {
                            $months_day = ceil(($val['end_line'] - $startMonth) / 86400);
                        }
                    }

                    $hetong_this_month_day = ($months_day - $thisMonthDay);
                    if ($months_day != 0 || $thisMonthDay != 0)
                    {
                        $hetong_this_month_day = $hetong_this_month_day == 0 ? $is_this_month + $hetong_this_month_day : $hetong_this_month_day;
                    }

                    $hetong_this_month_day = $hetong_this_month_day <= 0 ? 0 : $hetong_this_month_day;
                    $this_not_month_day_now = date('t') - $hetong_this_month_day;
                    $this_not_month_day += $this_not_month_day_now < 0 ? 0 : $this_not_month_day_now;
                    $this_month_day +=$hetong_this_month_day;

                    $house_rent_time += $house_day < 0 ? 0 : $house_day;//减去已租
                    //计算当年租了几天

                    $year_day = 0;
                    if ($val['signing_time'] >= $startYear)
                    {
                        $is_this_year = 1;
                        $year_day = ceil(($val['signing_time'] - $startYear) / 86400);
                    }



                    //签约时间在本年内 计算出是第几天
                    $thisYearDay = (($endYear - $startYear) / 86400) + 1;
                    if ($val['dead_line'] >= $startYear)
                    {
                        $thisYearDay = $endYear > $val['dead_line'] ? ceil(($val['dead_line'] - $startYear) / 86400) : $thisYearDay;
                    }
                    $hetong_this_year_day = ($thisYearDay - $year_day + $is_this_year);
                    $this_year_day += $hetong_this_year_day < 0 ? 0 : $hetong_this_year_day;
                }
            }

            $chuzulv = round((( $fensan_count + $jizhong_count) > 0 ? ($yichuzu_count) / ( $fensan_count + $jizhong_count) : 0) * 100 , 2);
            $chuzulv = $chuzulv > 0 ? $chuzulv : 0;
            $yuezulv = (($yichuzu_count + $weichuzu_count) * 30) / (($time - $house_time - $house_rent_time) / 86400);
            $yuezulv = $yuezulv > 0 ? round($yuezulv * 100 , 2) : 0;

            $yuekongzhilv = $this_month_day > 0 ? $this_not_month_day / $this_month_day : 0;
            $niankongzhilv = $this_year_day > 0 ? (ceil(($house_time / 86400) - $this_year_day) / $this_year_day) : 0;
            $yuekongzhilv = $yuekongzhilv <= 0 ? 0 : $yuekongzhilv;
            $niankongzhilv = $niankongzhilv <= 0 ? 0 : $niankongzhilv;

            $yuekongzhilv = $yuekongzhilv > 100 ? 100 : $yuekongzhilv;
            $niankongzhilv = $niankongzhilv > 100 ? 100 : $niankongzhilv;
            if ($flat_id == -1)
            {
                $houseHelper = new \Common\Helper\Erp\House();
                $yuekongzhilv = $houseHelper->calculateMonthEmpty($user);
                $niankongzhilv = $houseHelper->calculateMonthEmpty($user , true);
            }
            if ($flat_id > 0)
            {
                $RoomFocusHelper = new \Common\Helper\Erp\RoomFocus();
                $sum_empt_rent = $RoomFocusHelper->calculateMonthEmpty($user , $flat_id);
                $yuekongzhilv = $sum_empt_rent > 100 ? 100 : $sum_empt_rent;
                $niankongzhilv = $RoomFocusHelper->calculateMonthEmpty($user , $flat_id , true);
            }
            $data = array(
                'yizu' => $yichuzu_count ,
                'weizu' => $weichuzu_count ,
                'fensan_money' => $fensan_money_count ,
                'jizhong_money' => $jizhong_money_count ,
                'fensan' => $fensan_count ,
                'jizhong' => $jizhong_count ,
                'yuding' => $yiyuding_count ,
                'chuzulv' => $chuzulv . '%' ,
                'yuyuelv' => $yuezulv . '%' ,
                'yuekongzhilv' => round($yuekongzhilv , 2) > 100 ? '100%' : round($yuekongzhilv , 2) . '%' ,
                'niankongzhilv' => round($niankongzhilv , 2) > 100 ? '100%' : round($niankongzhilv , 2) . '%' ,
                'pingjunzujin' => round(($yichuzu_count + $weichuzu_count) > 0 ? (($fensan_money_count + $jizhong_money_count) / ($yichuzu_count + $weichuzu_count)) : 0 , 2) ,
                'zaizhuyajin' => $zaizhuyajin ,
            );
            return $data;
        }

        /**
         * 获取分散式房间地址信息
         * 修改时间 2015年5月11日16:36:34
         * 
         * @author ft
         */
        public function getRoomAddressInfo($house_id , $room_id)
        {
            $house_model = new \Common\Model\Erp\House();
            $sql = $house_model->getSqlObject();
            $select = $sql->select(array('h' => 'house'));
            $select->columns(
                    array(
                        'house_name' => 'house_name' ,
                        'h_custom_num' => 'custom_number' ,
                        'h_cost' => 'cost' ,
                        'h_unit' => 'unit' ,
                        'h_number' => 'number' ,
                        'rental_way' => 'rental_way' ,
            ));
            if ($room_id > 0)
            {//合租
                $select->leftjoin(array('r' => 'room') , 'h.house_id = r.house_id' , array('r_custom_num' => 'custom_number' , 'room_type' => 'room_type' ,));
            }
            $where = new \Zend\Db\Sql\Where();
            if ($room_id > 0)
            {//合租
                $where->equalTo('r.room_id' , $room_id);
            }
            $where->equalTo('h.house_id' , $house_id);
            $select->where($where);
            if ($room_id > 0)
            {
                $Shared_data = $select->execute();
                $Shared_data[0]['room_type'] = ($Shared_data[0]['room_type'] == 'main' ? '主卧' : (($Shared_data[0]['room_type'] == 'second') ? '次卧' : (($Shared_data[0]['room_type'] == 'guest' ? '客卧' : ''))));
                return $Shared_data;
            }
            return $select->execute();
        }

        /**
         * 删除房源可以批量删除
         * 修改时间2015年5月13日 11:18:40
         * 
         * @author yzx
         * @param unknown $houseId
         * @return boolean
         */
        public function deleteData($houseId)
        {
            $todoModel = new \Common\Model\Erp\Todo();
            $this->Transaction();
            if (is_array($houseId))
            {
                foreach ($houseId as $val)
                {
                	$house_data = $this->getOne(array("house_id"=>$val));
                    $result = $this->edit(array("house_id" => $val) , array("is_delete" => 1));
                    if (!$result)
                    {
                        return false;
                        $this->rollback();
                        exit();
                    }
                    //删除日志START
                    $todoModel->delete(array("entity_id" => $val , "module" => $todoModel::MODEL_HOUSE_STOP));
                    $todoModel->delete(array("entity_id" => $val , "module" => $todoModel::MODEL_HOUSE_RESERVE));
                    $todoModel->delete(array("entity_id" => $val , "module" => $todoModel::MODEL_HOUSE_RESERVE_OUT));
                    //删除日志END
                    
                    //写快照
                    \Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_HOUSE_DELETE, $val, $house_data);
                }
                $this->commit();
                return true;
            }
            elseif (is_numeric($houseId))
            {
            	$house_data = $this->getOne(array("house_id"=>$houseId));
                $result = $this->edit(array("house_id" => $houseId) , array("is_delete" => 1));
                if (!$result)
                {
                    $this->rollback();
                    return false;
                }
                //删除日志START
                $todoModel->delete(array("entity_id" => $houseId , "module" => $todoModel::MODEL_HOUSE_STOP));
                $todoModel->delete(array("entity_id" => $houseId , "module" => $todoModel::MODEL_HOUSE_RESERVE));
                $todoModel->delete(array("entity_id" => $houseId , "module" => $todoModel::MODEL_HOUSE_RESERVE_OUT));
                //删除日志END
                
                //写快照
                \Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_HOUSE_DELETE, $houseId, $house_data);
                
                $this->commit();
                return true;
            }
        }

        /**
         * 批量获取房间
         * 修改时间2015年6月29日20:08:14
         * 
         * @author yzx
         * @param array $houseId
         * @param unknown $user
         */
        public function getHouseData($houseId , $user)
        {
            $where = new Where();
            $where->in("house_id" , $houseId);
            $where->equalTo("company_id" , $user['company_id']);
            $select = $this->_sql_object->select($this->getTableName());
            $select->where($where);
            return $select->execute();
        }

        function getHouseImage($house_info)
        {
            $sass_house_type = $this->getSassHouseType($house_info['house_type']);

            $where = array();
            $where['bucket'] = array('jooozo-erp' , 'hicms-upload');
            if ($sass_house_type['house_type'] == 1)
            {
                if ($sass_house_type['rental_way'] == 1)
                {
                    $where['module'] = 'room';
                    $where['entity_id'] = $house_info['room_id'];
                }
                else
                {
                    $where['module'] = 'house';
                    $where['entity_id'] = $house_info['house_id'];
                }
            }
            else
            {
                $where['module'] = 'room_focus';
                $where['entity_id'] = $house_info['room_id'];
            }
            $list = M('Attachments')->getOne($where);
            return emptys($list) ? '' : $list['key'];
        }

        function getSassHouseType($house_type)
        {
            $type = $house_type{0};
            $rental_way = $house_type{1};
            return array('house_type' => $type , 'rental_way' => $rental_way);
        }

    }
    