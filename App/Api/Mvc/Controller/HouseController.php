<?php

    namespace App\Api\Mvc\Controller;

    class HouseController extends \App\Api\Lib\Controller
    {

        /**
         * 增加分散式房源
         *
         * @author yusj | 最后修改时间 2015年5月7日下午1:19:20
         */
        public function addHouseAction()
        {
            PV(array('city_id' , 'rental_way' , 'house_name' , 'community_id'));
            $rental_way = I("rental_way"); // 1合租 2整租
            if (empty($rental_way) && $rental_way != '1' && $rental_way != '2')
            {
                return_error(126); // 出租方式类型错误
            }
            $money = I("money");
            $detain = I("detain");
            $pay = I("pay");
            if ($rental_way == 2)
            {
                PV(array('money' , 'detain' , 'pay'));
                if (!is_numeric($money))
                    return_error(131 , '租金格式不正确');

                if (!is_numeric($detain) || !is_numeric($pay))
                    return_error(131 , '压付格式不正确');
            }
            else
            {
                PV(array('room_list'));
            }

            $houseModel = new \Common\Model\Erp\House();
            $houseModel->Transaction();
            $city_id = I("city_id");
            $house_name = I("house_name");
            $community_id = I("community_id");
            // $address = I("address");
            $cost = I("cost");
            $unit = I("unit");
            $floor = (int) I("floor");
            $total_floor = (int) I('total_floor');
            $number = I("number");
            $custom_number = I("custom_number");
            $count = I("count");
            $hall = I("hall");
            $toilet = I("toilet");
            $area = (int) I('area');
            $public_facilities = I("public_facilities");

            if (!emptys($area) && !is_numeric($area))
            {
                return_error(131 , "房源面积只能为数字");
            }

            if (!is_numeric($community_id))
            {
                return_error(131 , "小区ID只能为整数");
            }
            if (!is_numeric($count) && $count > 0)
            {
                return_error(131 , "房屋户型-室 只能为大于0的整数");
            }
            if (!is_numeric($hall))
            {
                return_error(131 , "房屋户型-厅只能为整数");
            }
            if (!is_numeric($toilet) && $toilet > 0)
            {
                return_error(131 , "房屋户型-卫只能为整数");
            }


            if (emptys($house_name))
            {
                return_error(131 , "房源名字不能为空");
            }

            $community_count = M('Community')->getCount(array('community_id' => $community_id , 'city_id' => $city_id));
            if ($community_count <= 0)
                return_error(131 , "小区信息与城市信息不匹配");

            $user = $this->getUserInfo();
            // $data['house_name'] = $address.$cost."栋".$unit."单元".$number."号";
            $house_data['company_id'] = $user['company_id'];
            $house_data ['house_name'] = $house_name;
            $house_data ['rental_way'] = $rental_way;
            $house_data ['community_id'] = $community_id;
            $house_data ['floor'] = $floor;
            $house_data['total_floor'] = $total_floor;
            $house_data ['number'] = $number;
            $house_data ['area'] = $area;
            $house_data ['cost'] = $cost;
            $house_data ['unit'] = $unit;
            $house_data ['hall'] = $hall;
            $house_data ['toilet'] = $toilet;
            $house_data ['public_facilities'] = implode('-' , explode(',' , $public_facilities));
            $house_data ['create_uid'] = $user ['user_id'];
            $house_data ['owner_id'] = $user ['user_id'];
            //$house_data ['address'] = $address;
            $house_data ['money'] = $money;
            $house_data['client_title'] = "";
            $house_data ['count'] = $count;
            $house_data ['custom_number'] = $custom_number;
            $house_data['create_time'] = time();
            $house_data['owner_id'] = $user['user_id'];

            $house_id = $houseModel->insert($house_data);
            if ($house_id)
            {
                //添加权限START
                $permissions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permissions->SetVerify(
                        $permissions::LINE_BLOCK_ACCESS , $permissions::SELECT_AUTH_ACTION , $permissions::USER_AUTHENTICATOR , $this->user['user_id'] , $house_id , 1 , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE
                );
            }

            if (!$house_id)
            {
                return_error(127); // 增加失败
            }

            //添加费用
            $F = new \App\Api\Helper\FeeType();
            $fee_list = I("fee_list" , '' , 'trim');

            $fee_list = is_array($fee_list) ? $fee_list : (array) json_decode($fee_list , true);
            $fee_add = $F->addFee($house_id , 'SOURCE_DISPERSE' , $fee_list);

            if (!$fee_add)
            {
                return_error(127 , ',费用添加失败'); // 增加失败
            }

            if (!$this->IsManager())
            {
                //添加权限START
                $permissions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permissions->SetVerify(
                        $permissions::LINE_BLOCK_ACCESS , $permissions::SELECT_AUTH_ACTION , $permissions::USER_AUTHENTICATOR , $this->getUserId() , $house_id , 1 , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE
                );
            }


            $result = array(
                'house_id' => $house_id ,
            );
            if ($rental_way == 2)
            {
                $id = $this->addHouseEntirelAction($house_id , $money , $pay , $detain);
                $result['entirel_id'] = $id;
            }
            else
            {
                $id = $this->addHouseRoomAction($house_id , I('room_list' , '' , 'trim'));
                $result['room_ids'] = $id;
            }

            $houseModel->commit();
            return_success($result);
        }

        function addHouseEntirelAction($house_id , $money , $pay , $detain)
        {

            $HouseEntirelCount = M('HouseEntirel')->getCount(array('house_id' => $house_id));
            if ($HouseEntirelCount > 0)
                return_error(131 , '该整租房源已经添加过');
            $data_room['house_id'] = $house_id;
            $data_room['status'] = 1;
            $data_room['is_yytz'] = 0;
            $data_room['is_yd'] = 0;
            $data_room['money'] = $money;
            $data_room['occupancy_number'] = (int) I('occupancy_number');
            // $data_room['exist_occupancy_number'] = I('exist_occupancy_number/d');
            //一下是默认值
            $data_room['detain'] = $detain;
            $data_room['pay'] = $pay;
            $data_room['is_delete'] = '0';
            $H = M('HouseEntirel');
            $HouseEntirelId = $H->insert($data_room);

            //  echo ($H->getLastSql());

            if (!$HouseEntirelId)
                return_error(127);
            return $HouseEntirelId;
        }

        function addHouseRoomAction($house_id , $room_list)
        {

            $R = M('Room');
            $room_list = is_string($room_list) ? json_decode($room_list , true) : $room_list;
            $num = 0;
            $HouseEntirelCount = M('Room')->getCount(array('house_id' => $house_id));
            if ($HouseEntirelCount > 0)
                return_error(131 , '合租房间已经存在');
            $house_name = M('House')->getOne(array('house_id' => $house_id) , array('house_name' => 'house_name'));
            //添加费用
            $F = new \App\Api\Helper\FeeType();

            foreach ($room_list as $key => $info)
            {
                $num++;
                $money = $info['money'];
                $pay = $info['pay'];
                $detain = $info['detain'];
                $area = $info['area'];
                $custom_number = $info['custom_number'];
                if (!is_numeric($money))
                    return_error(131 , '租金格式不正确');
                if (!is_numeric($detain) || !is_numeric($pay))
                    return_error(131 , '压付格式不正确');

                if (!emptys($area) && !is_numeric($area))
                    return_error(131 , '房间面积格式不正确');
                $room_type_list = array("main" => "主卧" , "second" => "次卧" , "guest" => "客卧");

                $room_type = $info['type'];

                $data_room['house_id'] = $house_id;
                $data_room['status'] = 1;
                $data_room['is_yytz'] = 0;
                $data_room['is_yd'] = 0;
                $data_room['area'] = (int) $area;
                $data_room['custom_number'] = emptys($custom_number) ? $num : $custom_number;
                $data_room['room_type'] = isset($room_type_list[$room_type]) ? $room_type : 'second';
                $data_room['occupancy_number'] = (int) $info['occupancy_number'];
                $data_room['money'] = $money;
                //一下是默认值
                $data_room['room_config'] = implode('-' , explode(',' , $info['room_config']));
                $data_room['detain'] = $detain;
                $data_room['pay'] = $pay;
                $data_room['is_delete'] = '0';
                $data_room['create_time'] = time();
                $data_room['client_title'] = "";
                $data_room['full_name'] = $house_name['house_name'] . $room_type_list[$room_type] . $custom_number . '号';

                $RoomId[$key] = $R->insert($data_room);

                if (!$RoomId[$key])
                    return_error(127);
                $fee_list = $info["room_fee_list"];
                $fee_list = is_array($fee_list) ? $fee_list : (array) json_decode($fee_list , true);
                $fee_add = $F->addFee($RoomId[$key] , 'SOURCE_DISPERSE_ROOM' , $fee_list);
                if (!$fee_add)
                {
                    return_error(127 , ',费用添加失败'); // 增加失败
                }
            }
            return $RoomId;
        }

        function getUserCommunityListAction()
        {
            $House = new \Common\Helper\Erp\House();
            $user_id = $this->getUserId();
            $company_id = $this->getCompanyId();
            if (!$company_id)
                return array();
            //统计小区编号
            $model = new \Common\Model\Erp\House();
            $sql = $model->getSqlObject();
            $select = $sql->select(array('c' => $model->getTableName()));
            $select->where(array('company_id' => $company_id));
            $select->group('community_id');
            $select->columns(array('community_id' => 'community_id'));
            if (!$this->IsManager())
            {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisions->VerifyDataCollectionsPermissionsModel($select , 'c.community_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $this->getUserId() , 1);
            }
            $data = $select->execute();
            $communityIds = array();
            foreach ($data as $value)
            {
                $communityIds[] = $value['community_id'];
            }
            if (empty($communityIds))
            {
                return array();
            }
            //取小区数据
            $model = new \Common\Model\Erp\Community();
            $result = $model->getData(array('community_id' => $communityIds));



            $data = array();
            foreach ($result as $info)
            {
                $data[] = array(
                    'community_id' => $info['community_id'] ,
                    'city_id' => $info['city_id'] ,
                    'community_name' => $info['community_name'] ,
                );
            }
            return_success($data);
        }

        function getUserHouseListAction()
        {
            $CompanyId = $this->getCompanyId();
            $House = new \Common\Helper\Erp\House();
            $CommunityId = I('community_id');
            $CommunityId = is_numeric($CommunityId) ? $CommunityId : 0;
            $search_key = I('search_key' , '' , 'trim');
            $search = array();
            if (!emptys($search_key))
            {
                $search['community_name'] = $search_key;
                $search['custom_number'] = $search_key;
            }

            $rentalWay = (int) I('rental_way');


            $HouseList = $House->HouseList(1 , 99999 , $this->getUserInfo() , '' , 0 , $search , $rentalWay , $CommunityId);

            $HouseList = getArrayKeyClassification($HouseList['data'] , 'house_id');

            $data = array();
            $configure_config = array('12' , '8' , '7');//房间需要展示配置属性

            foreach ($HouseList as $house_info)
            {
                $info = $house_info[0];
                $house_configure = explode('-' , $info['public_facilities']);
                $house_configure = count($house_configure) > 0 ? $house_configure : array();

                $number_info = explode('-' , $info['custom_number']);

                if (!isset($data[$info['community_id']]))
                {
                    $data[$info['community_id']] = array(
                        'community_id' => $info['community_id'] ,
                        'community_name' => $info['community_name'] ,
                    );
                }
                $data[$info['community_id']]['list'][$info['house_id']] = array(
                    'house_id' => $info['house_id'] ,
                    'rental_way' => $info['rental_way'] ,
                    'community_id' => $info['community_id'] ,
                    'house_name' => $info['house_name'] ,
                    'cost' => $info['cost'] ,
                    'unit' => $info['unit'] ,
                    'floor' => $info['floor'] ,
                    'number' => $info['number'] ,
                    'custom_number' => $number_info[0] ,
                    'house_configure' => $house_configure ,
                    'area_name' => $info['name'] ,
                    'business_name' => $info['business_string'] ,
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
                        $number_info = explode('-' , $info['custom_number']);
                        unset($number_info[0]);
                        $custom_number = implode('' , $number_info);

                        $data[$info['community_id']]['list'][$info['house_id']]['list'][] = array(
                            'room_id' => $info['record_id'] ,
                            'status' => $info['status'] ,
                            'is_yytz' => $info['is_yytz'] ,
                            'is_yd' => $info['is_yd'] ,
                            'room_type' => $info['room_type'] ,
                            'room_number' => $custom_number ,
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
            return_success(array_values($data));
        }

        function getReserveListAction()
        {
            $reserve = M('Reserve');

            $page = (int) I('page' , '1');
            $size = (int) I('size' , '10');
            $rental_way = I('rental_way');
            $room_id = I('room_id');
            $house_type = I('house_type');
            $reserve_time = I('reserve_time');
            $tenant_id = I('tenant_id');
            $keyword = I('keyword' , '' , 'trim');
            $company_id = $this->getCompanyId();
            $company_model = new \Common\Model\Erp\Company();
            $company_info = $company_model->getOne(array('company_id' => $company_id));
            $house_type = $company_info['pattern'] == '01' ? array(1) : ($company_info['pattern'] == '10' ? array(2) : ($company_info['pattern'] == '11' ? array(1 , 2) : 0));
            $sql = $reserve->getSqlObject();
            $reserve = $sql->select(array('r' => 'reserve'));
            $reserve->columns(array('reserve_id' , 'stime' , 'etime'));
            $reserve->join(array('t' => 'tenant') , new \Zend\Db\Sql\Predicate\Expression('r.tenant_id = t.tenant_id') , array('tenant_id' , 'name' , 'phone' , 'idcard' , 'gender') , $reserve::JOIN_LEFT);
            $reserve->join(array('hfv' => 'house_focus_view') , getExpSql('r.house_id = hfv.house_id and r.room_id=hfv.record_id') , array('public_facilities' , 'house_name') , $reserve::JOIN_LEFT);

            $user = $this->getUserInfo();
            if (!$user['is_manager'])
            {
                $permissions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisionsTable = $permissions->VerifyDataCollectionsPermissions($permissions::USER_AUTHENTICATOR , $user['user_id'] , 0);
                $join = new \Zend\Db\Sql\Predicate\Expression('(hfv.auth_id=pa.authenticatee_id and hfv.house_id=0 and pa.source=' . \Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED . ') or (hfv.house_id=pa.authenticatee_id and hfv.house_id>0  and pa.source=' . \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE . ')');
                $reserve->join(array('pa' => new \Zend\Db\Sql\Predicate\Expression($permisionsTable)) , $join , 'authenticatee_id');
            }

            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('r.is_delete' , 0);
            $where->equalTo('t.company_id' , $this->getCompanyId());
            if (!emptys($tenant_id))
            {
                $where->equalTo('t.tenant_id' , $tenant_id);
            }
            //分散式 or 集中式
            if (!empty($house_type))
            {
                $where->in('r.house_type' , $house_type);
            }

            if (!emptys($room_id))
            {
                if ($house_type == 1 && $rental_way == 2)
                {
                    $where->equalTo('r.house_id' , $room_id);
                }
                else
                {
                    $where->equalTo('r.room_id' , $room_id);
                }
            }
            switch ($reserve_time)
            {
                case 1:
                    $where->greaterThanOrEqualTo('r.stime' , strtotime('-1 month'));
                    break;
                case 2:
                    $where->greaterThanOrEqualTo('r.stime' , strtotime('-3 month'));
                    break;
                case 3:
                    $where->greaterThanOrEqualTo('r.stime' , strtotime('-6 month'));
                    break;
                case 4:
                    $where->greaterThanOrEqualTo('r.stime' , strtotime('-1 year'));
                    break;
            }
            /*   关键词:小区名 房间编号 租客电话 */
            if (!emptys($keyword))
            {
                $where2 = new \Zend\Db\Sql\Where();
                $where2->like('t.phone' , "%$keyword%");//租客电话
                $where2->or;
                $where2->like('t.name' , "%$keyword%");//分散式小区名
                $where2->or;
                $where2->like('hfv.house_name' , "%$keyword%");//房源编号
                $where->addPredicate($where2);
            }
            $reserve->where($where);
            //print_r(str_replace('"' , "" , $reserve->getSqlString()));exit;
            $reserve->order('r.reserve_id desc');

            $data = $reserve::pageSelect($reserve , null , $page , $size);

            $configure_config = array('12' , '8' , '7');//房间需要展示配置属性
            $data['data'] = is_array($data['data']) ? $data['data'] : array();
            foreach ($data['data'] as &$info)
            {
                $info['public_facilities'] = array_values(array_intersect($configure_config , explode('-' , $info['public_facilities'])));
                $info['house_name'] = (string) $info['house_name'];
            }
            return_success($data);
        }

        function getReserveSelectListAction()
        {
            $select = M('User')->getSqlObject()->select();
            $company_id = $this->getCompanyId();
            $company_model = new \Common\Model\Erp\Company();

            $company_info = $company_model->getOne(array('company_id' => $company_id));
            $data = array();
            if ($company_info['pattern'] == 01 || $company_info['pattern'] == 11)
            {

                $select->from(array('h' => 'house_view'))->leftjoin(array('r' => 'reserve') , getExpSql('h.house_id = r.house_id AND r.room_id= h.record_id') , array())->where(array('r.is_delete' => '0' , 'h.company_id' => $company_id , 'r.house_type' => '1'));

                if (!$this->IsManager())
                {
                    $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                    $permisions->VerifyDataCollectionsPermissionsModel($select , 'h.house_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $this->getUserId() , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
                }

                $HouseList = $select->execute();
                $HouseList = getArrayKeyClassification($HouseList , 'house_id');

                $data = array();
                $configure_config = array('12' , '8' , '7');//房间需要展示配置属性
                $all_room = array();
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

                        if (isset($all_room[$info['house_id']][$info['record_id']]))
                        {
                            continue;
                        }

                        $all_room[$info['house_id']][$info['record_id']] = 1;


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
                if ($company_info['pattern'] == 01)
                {
                    $return[] = array_values($data);
                    $return[] = array();
                }
                else
                {
                    $return[] = array_values($data);
                }
            }
            if ($company_info['pattern'] == 10 || $company_info['pattern'] == 11)
            {
                $select = M('User')->getSqlObject()->select();
                $select->from(array('rf' => 'room_focus'))->leftjoin(array('r' => 'reserve') , getExpSql('rf.room_focus_id= r.room_id') , array())->where(array('r.is_delete' => '0' , 'rf.company_id' => $company_id , 'r.house_type' => '2'));
                $user = $this->getUserInfo();

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
                $all_room = array();
                foreach ($HouseList as $info)
                {
                    if (!isset($result[$info['flat_id']]))
                    {
                        $result[$info['flat_id']] = array(
                            'flat_id' => $info['flat_id'] ,
                            'flat_name' => (string) $flat_list[$info['flat_id']] ,
                        );
                    }



                    if (isset($all_room[$info['flat_id']][$info['room_focus_id']]))
                    {
                        continue;
                    }

                    $all_room[$info['flat_id']][$info['room_focus_id']] = 1;


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
                if ($company_info['pattern'] == 10)
                {
                    $return[] = array();
                    $return[] = array_values($result);
                }
                else
                {
                    $return[] = array_values($result);
                }
            }
            $house_type = ($company_info['pattern'] == 01) ? array(1) : ($company_info['pattern'] == 10 ? array(2) : ($company_info['pattern'] == 11 ? array(1 , 2) : 0));
            $select = M('User')->getSqlObject()->select();
            $select->from(array('t' => 'tenant'))->columns(array('name' , 'phone' , 'tenant_id'))->group('t.tenant_id')->leftjoin(array('r' => 'reserve') , getExpSql('t.tenant_id= r.tenant_id') , array())->where(array('r.is_delete' => '0' , 't.company_id' => $company_id , 'r.house_type' => $house_type));

            $select->join(array('hfv' => 'house_focus_view') , getExpSql('r.house_id = hfv.house_id and r.room_id=hfv.record_id') , array('public_facilities' , 'house_name') , $select::JOIN_LEFT);

            $user = $this->getUserInfo();
            if (!$user['is_manager'])
            {
                $permissions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisionsTable = $permissions->VerifyDataCollectionsPermissions($permissions::USER_AUTHENTICATOR , $user['user_id'] , 0);
                $join = new \Zend\Db\Sql\Predicate\Expression('(hfv.auth_id=pa.authenticatee_id and hfv.house_id=0 and pa.source=' . \Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED . ') or (hfv.auth_id=pa.authenticatee_id and hfv.house_id>0  and pa.source=' . \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE . ')');
                $select->join(array('pa' => new \Zend\Db\Sql\Predicate\Expression($permisionsTable)) , $join , 'authenticatee_id');
            }
            $tenant = $select->execute();


            $time = array(
                '1' => '一月之内' ,
                '2' => '三月之内' ,
                '3' => '半年之内' ,
                '4' => '一年之内' ,
            );
            return_success(array('tenant_list' => $tenant , 'house_list' => $return , 'time' => $time));
        }

        /**
         * 编辑分散式房源
         *
         * @author lms   2015年6月9日 13:35:22
         */
        public function editHouseAction()
        {

            PV(array('house_id' , 'area' , 'count' , 'hall' , 'toilet'));

            $house_id = I('house_id');

            //权限验证  
            if (!$this->verifyDataLinePermissions(\Common\Helper\Permissions::DELETE_AUTH_ACTION , $house_id , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE))
            {
                return_error(131 , '无操作权限');
            }


            $company_id = $this->getCompanyId();
            $H = M('House');
            $H->Transaction();
            $house_info = $H->getOne(array('company_id' => $company_id , 'house_id' => $house_id));
            if (emptys($house_info))
                return_error(131 , '房源不存在');
            $money = I("money");
            $detain = I("detain");
            $pay = I("pay");
            $rental_way = $house_info['rental_way'];

            if ($rental_way == 2)
            {
                PV(array('money' , 'detain' , 'pay' , 'occupancy_number'));
                if (!is_numeric($money))
                    return_error(131 , '租金格式不正确');
                if (!is_numeric($detain) || !is_numeric($pay))
                    return_error(131 , '压付格式不正确');
            }
            else
            {
                PV(array('room_list'));
            }
            $houseModel = new \Common\Model\Erp\House();

            $house_name = I("house_name");
            $custom_number = I("custom_number");
            $count = I("count");
            $hall = I("hall");
            $toilet = I("toilet");
            $area = (int) I('area');
            $public_facilities = I("public_facilities");
            if (!emptys($area) && !is_numeric($area))
            {
                return_error(131 , "房源面积只能为数字");
            }
            if (!is_numeric($count) && $count > 0)
            {
                return_error(131 , "房屋户型-室 只能为大于0的整数");
            }
            if (!is_numeric($hall))
            {
                return_error(131 , "房屋户型-厅只能为整数");
            }
            if (!is_numeric($toilet) && $toilet > 0)
            {
                return_error(131 , "房屋户型-卫只能为整数");
            }

            $F = new \App\Api\Helper\FeeType();
            $user = $this->getUserInfo();
            // $data['house_name'] = $address.$cost."栋".$unit."单元".$number."号";

            $house_data ['count'] = $count;
            $house_data ['area'] = $area;
            $house_data ['hall'] = $hall;
            $house_data ['toilet'] = $toilet;
            $house_data ['public_facilities'] = implode('-' , explode(',' , $public_facilities));
            $house_data ['custom_number'] = $custom_number;
            $house_data['update_time'] = time();


            $edit = $houseModel->edit(array('house_id' => $house_id) , $house_data);
            if (!$edit)
            {
                return_error(142); // 修改失败
            }

            //添加费用
            $F = new \App\Api\Helper\FeeType();
            $fee_list = I("fee_list" , '' , 'trim');

            $fee_list = is_array($fee_list) ? $fee_list : (array) json_decode($fee_list , true);

            $fee_add = $F->addFee($house_id , 'SOURCE_DISPERSE' , $fee_list);

            if (!$fee_add)
            {
                return_error(127 , ',费用添加失败'); // 增加失败
            }
            $room_ids = array();
            if ($rental_way == 2)
            {

                if (!is_numeric($detain))
                {
                    return_error(131 , "付款方式-押只能为整数");
                }
                if (!is_numeric($pay) || $pay < 1)
                {
                    return_error(131 , "付款方式-付只能为大于0的整数");
                }

                $HE = M('HouseEntirel');
                $data_room['money'] = $money;
                $data_room['occupancy_number'] = I('occupancy_number/d');
                $data_room['detain'] = $detain;
                $data_room['pay'] = $pay;
                $HouseEntirelId = $HE->edit(array('house_id' => $house_id) , $data_room);
                if (!$HouseEntirelId)
                {
                    $H->rollback();
                    return_error(127);
                }
            }
            else
            {
                $room_list = I('room_list' , '' , 'trim');
                $R = M('Room');
                $room_list = is_string($room_list) ? (array) json_decode($room_list , true) : $room_list;

                $delete = $R->edit(array('house_id' => $house_id) , array('is_delete' => 1));
                if (!$delete)
                {
                    $H->rollback();
                    return_error(141 , ' , 储存房间数据失败.');
                }



                $num = 0;
                $room_type_list = array("main" => "主卧" , "second" => "次卧" , "guest" => "客卧");
                $room_ids = array();
                foreach ($room_list as $info)
                {
                    $num++;
                    $money = $info['money'];
                    $pay = $info['pay'];
                    $detain = $info['detain'];
                    $area = $info['area'];
                    $custom_number = $info['custom_number'];
                    if (!is_numeric($money))
                    {
                        $H->rollback();
                        return_error(131 , '租金格式不正确');
                    }

                    if (!is_numeric($detain) || !is_numeric($pay))
                    {
                        $H->rollback();
                        return_error(131 , '压付格式不正确');
                    }

                    if (!emptys($area) && !is_numeric($area))
                    {
                        $H->rollback();
                        return_error(131 , '房间面积格式不正确');
                    }

                    $room_type = $info['room_type'];
                    $data_room['area'] = (int) $area;
                    $data_room['custom_number'] = emptys($custom_number) ? $num : $custom_number;
                    $data_room['room_type'] = isset($room_type_list[$room_type]) ? $room_type : 'second';
                    $data_room['occupancy_number'] = (int) $info['occupancy_number'];
                    //一下是默认值
                    $data_room['room_config'] = implode('-' , explode(',' , $info['room_config']));
                    $data_room['detain'] = $detain;
                    $data_room['pay'] = $pay;
                    $data_room['update_time'] = time();
                    $data_room['is_delete'] = '0';

                    if (isset($info['room_id']) && is_numeric($info['room_id']))
                    {
                        $room_id = $info['room_id'];
                        $result = $R->edit(array('room_id' => $info['room_id']) , $data_room);
                    }
                    else
                    {

                        $room_type = $info['room_type'];
                        $data_room['house_id'] = $house_id;
                        $data_room['full_name'] = $house_info['house_name'] . $room_type_list[$room_type] . $custom_number . '号';
                        $data_room['status'] = 1;
                        $data_room['is_yytz'] = 0;
                        $data_room['is_yd'] = 0;
                        $data_room['area'] = (int) $area;
                        $data_room['custom_number'] = emptys($custom_number) ? $num : $custom_number;
                        $data_room['room_type'] = isset($room_type_list[$room_type]) ? $room_type : 'second';
                        $data_room['occupancy_number'] = (int) $info['occupancy_number'];
                        //一下是默认值
                        $data_room['money'] = $money;
                        $data_room['room_config'] = implode('-' , explode(',' , $info['room_config']));
                        $data_room['detain'] = $detain;
                        $data_room['pay'] = $pay;
                        $data_room['is_delete'] = '0';
                        $data_room['create_time'] = time();
                        $result = $R->insert($data_room);
                        $room_id = $result;
                    }
                    if (!$result)
                    {
                        $H->rollback();
                        return_error(141 , ' , 储存房间数据失败.');
                    }

                    $room_ids[] = $room_id;
                    $fee_list = $info["room_fee_list"];
                    $fee_list = is_array($fee_list) ? $fee_list : (array) json_decode($fee_list , true);
                    $fee_add = $F->addFee($room_id , 'SOURCE_DISPERSE_ROOM' , $fee_list);
                    if (!$fee_add)
                    {
                        return_error(127 , ' , 费用添加失败'); // 增加失败
                    }
                }
            }
            $H->commit();
            return_success(array('house_id' => $house_id , 'room_id' => $room_ids));
        }

    }
    