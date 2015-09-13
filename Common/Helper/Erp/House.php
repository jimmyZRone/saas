<?php

    namespace Common\Helper\Erp;

    use Zend\Db\Sql\Where;
    use Core\Db\Sql\Select;
    use Zend\Db\Sql\Expression;
    use Common\Model\Erp\StopHouse;
    use Common\Model\Erp\ReserveBackRental;
    use Common\Model\Erp\Room;
use Core\Event\Exception;
				
    class House extends \Core\Object
    {

        private $day = null;

        /**
         * 分散式整租列表
         * 修改时间2015年3月26日 14:35:48
         * 
         * @author yzx
         * @param int $communityId
         * @param int $page
         * @param int $size
         * @return multitype:multitype:Ambigous <number, unknown> number  Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> > |boolean
         */
        public function HouseList($page , $size , $user , $sqlType = '' , $areaId = 0 , $serche = array() , $rentalWay = 0 , $community_id = 0)
        {

            $houseModel = new \Common\Model\Erp\House();
            $sql = $houseModel->getSqlObject();
            $like_where = $this->likSql($serche);
            $cp_where = new Where();
            $cp_where->equalTo("hv.company_id" , $user['company_id']);
            $cp_where->equalTo("hv.is_delete" , 0);

            if (isset($user['city_id']))
                $cp_where->equalTo("a.city_id" , $user['city_id']);
            if ($areaId > 0)
            {
                $e_where = new Where();
                $e_where->equalTo("a.area_id" , $areaId);
            }
            $select = $sql->select(array("hv" => "house_view"))
                    ->leftjoin(array("h" => 'house') , "hv.house_id=h.house_id" , array('houseName' => 'house_name'))
                    ->leftjoin(array("r" => "room") , new Expression("r.room_id=hv.record_id  AND r.house_id=hv.house_id") , array("roomType" => "room_type" , "roomNumber" => "custom_number" , "roomConfig" => 'room_config' , "room_config" , "room_id" , "roomStatus" => "status"))
                    ->leftjoin(array("c" => "community") , "hv.community_id=c.community_id" , array("community_name" , 'business_string'))
                    ->leftjoin(array("a" => "area") , "c.area_id=a.area_id" , array("name"));
            $select->where($cp_where);

            if ($like_where)
            {
                if ($areaId > 0)
                {
                    $cp_where->addPredicates($e_where , $like_where);
                    $select->where($cp_where);
                }
                else
                {
                    $cp_where->addPredicate($like_where);
                    $select->where($cp_where);
                }
            }
            elseif ($areaId > 0)
            {
                $cp_where->addPredicate($e_where);
                $select->where($cp_where);
            }
            if ($rentalWay > 0 || (isset($serche['house_type']) && $serche['house_type'] != null))
            {
                $r_where = new Where();
                $r_where->equalTo("hv.rental_way" , $rentalWay);
                $cp_where->addPredicate($r_where);
                $select->where($cp_where);
            }
            if ($community_id > 0)
            {
                $cm_where = new Where();
                $cm_where->equalTo("hv.community_id" , $community_id);
                $cp_where->addPredicate($cm_where);
                $select->where($cp_where);
            }
            switch ($sqlType)
            {
                //统计房源全部租金
                case \Common\Model\Erp\House::SUM_MONEY:
                    $select->columns(array("sum_mone" => new Expression("sum(hv.money)")));
                    break;
                //统计在租房源
                case \Common\Model\Erp\House::IS_RENTAL:
                    $r_where = new Where();
                    $r_where->equalTo("hv.status" , $houseModel::STATUS_IS_RENTAL);
                    $cp_where->addPredicate($r_where);
                    $select->where($cp_where);
                    $select->columns(array("count_rental" => new Expression("count(DISTINCT hv.record_id)")));
                    break;
                //统计预定房源
                case \Common\Model\Erp\House::IS_RESERVE:
                    $ir_where = new Where();
                    $ir_where->equalTo("hv.is_yd" , $houseModel::IS_YD);
                    $cp_where->addPredicate($ir_where);
                    $select->where($cp_where);
                    $select->columns(array("count_reserve" => new Expression("count(DISTINCT hv.record_id)")));
                    break;
                case \Common\Model\Erp\House::IS_YYTZ_C:
                    //统计预约退租房源
                    $yytz_where = new Where();
                    $yytz_where->equalTo("hv.is_yytz" , $houseModel::IS_YYTZ);
                    $cp_where->addPredicate($yytz_where);
                    $select->where($cp_where);
                    $select->columns(array("yytz" => new Expression("count(DISTINCT hv.house_id)")));
                    break;
                case \Common\Model\Erp\House::IS_STOP:
                    //统计停用房源
                    $stop_where = new Where();
                    $stop_where->equalTo("hv.status" , $houseModel::STATUS_IS_STOP);
                    $cp_where->addPredicate($stop_where);
                    $select->where($cp_where);
                    $select->columns(array("stop" => new Expression("count(DISTINCT hv.record_id)")));
                    break;
            }
            //添加房源状态搜索条件
            if (isset($serche['house_type']) && $serche['house_type'] == 1)
            {
                $room_type_where = new Where();
                if ($serche['room_type'] != "all")
                {
                    $room_type_where->equalTo("hv.room_type" , $serche['room_type']);
                    $cp_where->addPredicate($room_type_where);
                    $select->where($cp_where);
                }
            }
            if (isset($serche['house_type']) && $serche['house_type'] == 2 && $serche['room_type'] != 0)
            {
                if ($serche['room_type'] != '')
                {
                    $room_type = explode("t" , $serche['room_type']);
                }
                $room_count_where = new Where();
                $room_hall_where = new Where();
                $room_count_where->equalTo("hv.count" , $room_type[0]);
                $room_hall_where->equalTo("hv.hall" , $room_type[1]);
                $room_count_where->addPredicate($room_hall_where);
                $cp_where->addPredicate($room_count_where);
                $select->where($cp_where);
            }

            //if (!$user['is_manager'])
            //{
            //    $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
            //    $permisions->VerifyDataCollectionsPermissionsModel($select , 'c.community_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , 1);
            //}
            
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
//                 $flatJoin = function($table) use($select,&$tables){
//                     $tables[] = $table;
//                     $join = new \Zend\Db\Sql\Predicate\Expression("rf.flat_id IS NOT NULL and `{$table}`.authenticatee_id=rf.flat_id");
//                     return array($join,$select::JOIN_LEFT);
//                 };
//                 $permisions->VerifyDataCollectionsPermissionsModel($select,$flatJoin, $permisions::USER_AUTHENTICATOR, $user['user_id'],2);
                 
                $and = new \Zend\Db\Sql\Where();
            
                $or = new \Zend\Db\Sql\Where();
                $or->isNotNull("{$tables[0]}.authenticatee_id");
                $and->orPredicate($or);
            
                //$or = new \Zend\Db\Sql\Where();
                //$or->isNotNull("{$tables[1]}.authenticatee_id");
                //$and->orPredicate($or);
            
                $or = new \Zend\Db\Sql\Where();
                $or->isNull("{$tables[0]}.authenticatee_id");
                //$or->isNull("{$tables[1]}.authenticatee_id");
                $or->equalTo('h.create_uid', $user['user_id']);
                $and->orPredicate($or);
            
                $cp_where->andPredicate($and);
            }

            //$select->group("hv.record_id");
            $select->order("hv.house_id desc",'record_id asc');
            //print_r(str_replace('"', '', $select->getSqlString()));die();
            $result = Select::pageSelect($select , null , $page , $size);

            if ($page > $result["page"]['cpage'])
            {
                return array();
            }
            if (!empty($result))
            {
                return $result;
            }
            return false;
        }

        /**
         * 分散式整租列表
         * 修改时间2015年3月26日 14:35:48
         * 
         * @author yzx
         * @param int $communityId
         * @param int $page
         * @param int $size
         * @return multitype:multitype:Ambigous <number, unknown> number  Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> > |boolean
         */
        public function HouseListPc($page , $size , $user , $sqlType = '' , $areaId = 0 , $serche = array() , $rentalWay = 0 , $community_id = 0)
        {
            $houseModel = new \Common\Model\Erp\House();
            $sql = $houseModel->getSqlObject();
            $like_where = $this->likSql($serche);
            $cp_where = new Where();
            $cp_where->equalTo("h.company_id" , $user['company_id']);
            $cp_where->equalTo("h.is_delete" , 0);
            if (isset($user['city_id']))
                $cp_where->equalTo("a.city_id" , $user['city_id']);
            if ($areaId > 0)
            {
                $e_where = new Where();
                $e_where->equalTo("a.area_id" , $areaId);
            }
            $select = $sql->select(array("h" => "house"))
                    ->leftjoin(array("he" => 'house_entirel') , "h.house_id = he.house_id" , array('house_money' => 'money' , "house_status" => 'status' , 'house_is_yytz' => 'is_yytz' , "house_is_yd" => 'is_yd'))
                    ->leftjoin(array("r" => "room") , new Expression("r.house_id=h.house_id AND r.is_delete=0") , array("roomType" => "room_type" , "roomNumber" => "custom_number" ,
                        "roomConfig" => 'room_config' , "room_id" , "roomStatus" => "status" ,
                        "room_is_yd" => 'is_yd' , "room_is_yytz" => "is_yytz" , "room_money" => "money"))
                    ->leftjoin(array("c" => "community") , "h.community_id=c.community_id" , array("community_name" , 'business_string'))
                    ->leftjoin(array("a" => "area") , "c.area_id=a.area_id" , array("name"));

            //权限
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisions->VerifyDataCollectionsPermissionsModel($select , 'h.house_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
            }

            $select->where($cp_where);
            if ($like_where)
            {
                if ($areaId > 0)
                {
                    $cp_where->addPredicates($e_where , $like_where);
                    $select->where($cp_where);
                }
                else
                {
                    $cp_where->addPredicate($like_where);
                    $select->where($cp_where);
                }
            }
            elseif ($areaId > 0)
            {
                $cp_where->addPredicate($e_where);
                $select->where($cp_where);
            }
            if ($rentalWay > 0 || (isset($serche['house_type']) && $serche['house_type'] != null))
            {
                $r_where = new Where();
                $r_where->equalTo("h.rental_way" , $rentalWay);
                $cp_where->addPredicate($r_where);
                $select->where($cp_where);
                if ($rentalWay == $houseModel::RENTAL_WAY_H)
                {
                    // $select->group("hv.record_id");
                    $select->order("h.house_id desc");
                }
            }
            if ($community_id > 0)
            {
                $cm_where = new Where();
                $cm_where->equalTo("h.community_id" , $community_id);
                $cp_where->addPredicate($cm_where);
                $select->where($cp_where);
            }
            switch ($sqlType)
            {
                //统计房源全部租金
                case \Common\Model\Erp\House::SUM_MONEY:
                    $select->columns(array("house_money" => new Expression("sum(he.money)") , "room_money" => new Expression("sum(r.money)")));
                    break;
            }
            //添加房源状态搜索条件
            if (isset($serche['house_type']) && $serche['house_type'] == 1)
            {
                $room_type_where = new Where();
                if ($serche['room_type'] != "all")
                {
                    $room_type_where->equalTo("r.room_type" , $serche['room_type']);
                    $cp_where->addPredicate($room_type_where);
                    $select->where($cp_where);
                }
            }
            if (isset($serche['house_type']) && $serche['house_type'] == 2 && $serche['room_type'] != 0)
            {
                if ($serche['room_type'] != '')
                {
                    $room_type = explode("t" , $serche['room_type']);
                }
                $room_count_where = new Where();
                $room_hall_where = new Where();
                $room_count_where->equalTo("h.count" , $room_type[0]);
                $room_hall_where->equalTo("h.hall" , $room_type[1]);
                $room_count_where->addPredicate($room_hall_where);
                $cp_where->addPredicate($room_count_where);
                $select->where($cp_where);
            }


            // print_r(str_replace('"', '', $select->getSqlString()));die();
            if ($sqlType == \Common\Model\Erp\House::IS_RENTAL)
            {
                return $select->execute();
            }
            $result = Select::pageSelect($select , null , $page , $size);
            if ($page > $result["page"]['cpage'])
            {
                return array();
            }
            if (!empty($result))
            {
                return $result;
            }
            return false;
        }

        /**
         * 根据参数获取分散式房源列表数据
         * 修改时间2015年3月26日 15:45:54
         * 
         * @author yzx
         * @param int $communityId
         * @param int $page
         * @param int $size
         * @param int $listType
         * @return Ambigous <\Common\Helper\Erp\multitype:multitype:Ambigous, boolean, multitype:multitype:Ambigous <number, unknown> number  Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> > >|Ambigous <\Common\Helper\Erp\multitype:multitype:Ambigous, multitype:multitype:Ambigous <number, unknown> number  Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> > >
         */
        public function listData($page , $size , $user , $serche = array() , $areaId = 0)
        {
            $roomHelper = new Room();
            return $roomHelper->roomList($page , $size , $user , $serche , $areaId);
        }

        /**
         * 根据条件统计房间房源数据
         * 修改2015年3月27日 10:00:23
         * 
         * @author yzx
         * @param int $areaId
         * @param string $listType
         * @param string $sqlType
         * @return Ambigous <\Common\Helper\Erp\multitype:multitype:Ambigous, boolean, multitype:multitype:Ambigous <number, unknown> number  Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> > >
         */
        public function countData($listType = \Common\Model\Erp\House::LIST_TYPE_HOUSE , $user , $areaId = 0 , $community_id = 0)
        {
            $page = 1;
            $size = 1;
            $conun_data = array();
            $roomHelper = new Room();
            //获取房源统计

            $sum_mone = $this->HouseListPc($page , $size , $user , \Common\Model\Erp\House::SUM_MONEY , $areaId);
            if (!empty($sum_mone['data']))
            {
                $sum_mone = round(($sum_mone['data'][0]['house_money'] + $sum_mone['data'][0]['room_money']) / $sum_mone['page']['count'] , 2);
            }
            else
            {
                $sum_mone = 0;
            }
            $count_rental = $this->HouseListPc($page , $size , $user , \Common\Model\Erp\House::IS_RENTAL , $areaId , array() , 0 , $community_id);
            $room_rental = 0;
            $room_reserve = 0;
            $room_yytz = 0;
            $room_stop = 0;
            $house_rental = 0;
            $house_reserve = 0;
            $house_yytz = 0;
            $house_stop = 0;
            $room_money = 0;
            $house_money = 0;
            foreach ($count_rental as $key => $val)
            {
                //合租
                if ($val['rental_way'] == \Common\Model\Erp\House::RENTAL_WAY_H)
                {
                    $room_money+=$val['room_money'];
                    //已租
                    if ($val['roomStatus'] == \Common\Model\Erp\Room::STATIS_RENTAL)
                    {
                        $room_rental++;
                    }
                    //预定
                    if ($val['room_is_yd'] == \Common\Model\Erp\Room::IS_YD && $val['roomStatus'] == \Common\Model\Erp\Room::STATUS_NOT_RENTAL)
                    {
                        $room_reserve++;
                    }
                    //预约退租
                    if ($val['room_is_yytz'] == \Common\Model\Erp\Room::IS_YYTZ)
                    {
                        $room_yytz++;
                    }
                    //停用
                    if ($val['roomStatus'] == \Common\Model\Erp\Room::STATUS_IS_STOP)
                    {
                        $room_stop++;
                    }
                }
                //整租
                if ($val['rental_way'] == \Common\Model\Erp\House::RENTAL_WAY_Z)
                {
                    $house_money+=$val['house_money'];
                    //已租
                    if ($val['house_status'] == \Common\Model\Erp\House::STATUS_IS_RENTAL)
                    {
                        $house_rental++;
                    }
                    //预定
                    if ($val['house_is_yd'] == \Common\Model\Erp\House::IS_YD && $val['house_status'] == \Common\Model\Erp\House::STATUS_NOT_RENTAL)
                    {
                        $house_reserve++;
                    }
                    //预约退租
                    if ($val['house_is_yytz'] == \Common\Model\Erp\House::IS_YYTZ)
                    {
                        $house_yytz++;
                    }
                    //停用
                    if ($val['house_status'] == \Common\Model\Erp\House::STATUS_IS_STOP)
                    {
                        $house_stop++;
                    }
                }
            }
            $count_reserve = $room_reserve + $house_reserve;
            $is_yytz = $room_yytz + $house_yytz;
            $stop = $room_stop + $house_stop;
            $all_money = $room_money + $house_money;
            $count_rental = $room_rental + $house_rental - $is_yytz;
            $all_house = $this->countHouse($user);
            if (!empty($all_house['data']))
            {
                $all_house = $all_house['data'][0]['all_house'];
            }
            $sum_mone = number_format($all_money / $all_house , 2);
            $conun_data = array("sum_mone" => $sum_mone ,
                "count_rental" => intval($count_rental) ,
                "count_reserve" => intval($count_reserve) ,
                "is_yytz" => intval($is_yytz) ,
                "stop" => intval($stop) ,
                "all_house" => intval($all_house) ,
                "rental_rate" => number_format(intval($all_house) > 0 ? ((intval($count_rental) / intval($all_house))) * 100 : 0 , 2 , '.' , '') ,
                "month_empty_rate" => $this->calculateMonthEmpty($user) > 100 ? 100.00 : $this->calculateMonthEmpty($user) ,
                "year_empty_reate" => $this->calculateMonthEmpty($user , true) > 100 ? 100.00 : $this->calculateMonthEmpty($user , true) ,
            );
            return $conun_data;
        }

        /**
         * 统计城市下每个区域的房源数量
         * 修改时间2015年4月7日 15:34:21
         * 
         * @author yzx
         * @param array $user
         */
        public function countAreaHouse($user , $areaId = 0)
        {
            $houseModel = new \Common\Model\Erp\House();
            $city_where = new Where();
            $company_where = new Where();
            $area_where = new Where();
            $city_where->equalTo("c.city_id" , $user['city_id']);
            $company_where->equalTo("hv.company_id" , $user['company_id']);
            $area_where->equalTo("c.area_id" , $areaId);
            $city_where->equalTo("hv.is_delete" , 0);
            $sql = $houseModel->getSqlObject();
            $select = $sql->select(array("hv" => "house_view"))
                    ->leftjoin(array("c" => "community") , "hv.community_id = c.community_id" , array("business_string" , "longitude" , "latitude" , "community_id" , "community_name"))
                    ->leftjoin(array("a" => "area") , "c.area_id = a.area_id" , array("name" , 'area_id'));
            //权限
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisions->VerifyDataCollectionsPermissionsModel($select , 'hv.house_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
            }
            if ($areaId > 0)
            {
                $city_where->addPredicate($area_where);
                $city_where->addPredicate($company_where);
                $select->where($city_where);
                $select->group("c.community_id");
            }
            else
            {
                $city_where->addPredicate($company_where);
                $city_where->equalTo("hv.is_delete" , 0);
                $select->where($city_where);
                $select->group("c.area_id");
            }
            $select->columns(array("count_house" => new Expression("count(DISTINCT hv.house_id)")));
            //print_r(str_replace('"', '', $select->getSqlString()));die();	
            $result = $select->execute();
            return $result;
        }

        /**
         * 添加分散房源
         *
         * @author yzx
         * @param array $data
         * @return boolean|number
         */
        public function addHouse($data , $user , $feeData = array())
        {
            $houseEntirelModel = new \Common\Model\Erp\HouseEntirel();
            $attachmentModel = new \Common\Model\Erp\Attachments();
            $feeHelper = new \Common\Helper\Erp\Fee();
            $houseModel = new \Common\Model\Erp\House();
            $LandlordContractModel = new \Common\Helper\Erp\LandlordContract();
            $unit = isset($data['unit']) ? $data['unit'] . "单元" : '';
            $floor = isset($data['floor']) ? $data['floor'] . "楼" : '';
            $data['house_name'] = $data['community_name'] . "-" . $data['cost'] . "栋" . $unit . $floor . $data['number'] . "号";
            $data['create_time'] = time();
            $data['owner_id'] = $user['user_id'];
            $data['update_time'] = time();
            $house_data = $houseModel->getData(array("house_name" => $data['house_name'] , "is_delete" => 0 , "company_id" => $user['company_id']));
            if (!empty($house_data))
            {
                return array("status" => false , "message" => "房源名称已存在");
            }
            if (!empty($data['public_facilities']))
            {
                $public_facilities = explode("," , $data['public_facilities']);
                $data['public_facilities'] = implode("-" , $public_facilities);
            }
            else
            {
                $data['public_facilities'] = '';
            }
            $houseModel->Transaction();
            $new_house_id = $houseModel->insert($data);
            if ($new_house_id)
            {
                $data['house_id'] = $new_house_id;
                $house_entre_id = $houseEntirelModel->addEntirel($data);
            }
            else
            {
                $houseModel->rollback();
                return array("status" => false);
            }
            if ($house_entre_id)
            {
                if ($data['public_pic'] != '')
                {
                    $data['img'] = explode(',' , $data['public_pic']);
                    if (is_array($data['img']) && !empty($data['img']))
                    {
                        foreach ($data['img'] as $key => $val)
                        {
                            $img_data['key'] = $val;
                            $img_data['module'] = "house";
                            $img_data['entity_id'] = $new_house_id;
                            $attachmentModel->insertData($img_data);
                        }
                    }
                }
                $houseModel->commit();
                if (!empty($feeData))
                {
                    $feeHelper->addFee($feeData , \Common\Model\Erp\Fee::SOURCE_DISPERSE , $new_house_id);
                }
                $house_name = $LandlordContractModel->CheckHouseName(\Common\Model\Erp\LandlordContract::HOUSE_TYPE_R , $data['house_name'] , $user , $new_house_id);
                return array("status" => $new_house_id , "is_house_name" => $house_name);
            }
            $houseModel->rollback();
            return false;
        }

        /**
         * 编辑分散房源
         * 修改时间2015年3月17日 10:27:39
         * 
         * @author yzx
         * @param array $data
         * @param int $house_id
         * @return boolean
         */
        public function editHouse($data , $house_id , $feeData = array())
        {
            $houseEntirelModel = new \Common\Model\Erp\HouseEntirel();
            $attachmentModel = new \Common\Model\Erp\Attachments();
            $feeHelper = new \Common\Helper\Erp\Fee();
            $houseModel = new \Common\Model\Erp\House();
            if ($house_id > 0)
            {
                $unit = isset($data['unit']) ? $data['unit'] . "单元" : '';
                $floor = isset($data['floor']) ? $data['floor'] . "楼" : '';
                $data['house_name'] = $data['community_name'] . "-" . $data['cost'] . "栋" . $unit . $floor . $data['number'] . "号";
                $house_entirel_data = $houseEntirelModel->getOne(array("house_id" => $house_id));
                $house_data = $this->getOneHouseData($house_id);
                if (!empty($house_data))
                {
                    $public_facilities = explode("," , $data['public_facilities']);
                    $data['public_facilities'] = implode("-" , $public_facilities);
                    $houseModel->Transaction();
                    $result = $houseModel->edit(array("house_id" => $house_id) , $data);
                    if ($result)
                    {
                        if (empty($house_entirel_data))
                        {
                            $data['house_id'] = $house_id;
                            $result = $houseEntirelModel->addEntirel($data);
                            if ($result)
                            {
                            	//写快照
                            	\Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_HOUSE_EDIT, $house_id, $house_data,"entirel",$result);
                                
                            	$houseModel->commit();
                                return true;
                            }
                            $houseModel->rollback();
                            return false;
                        }
                        if ($houseEntirelModel->editEntirel($data , $house_data['house_entirel_id']))
                        {
                        	//写快照
                        	\Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_HOUSE_EDIT, $house_id, $house_data,"entirel",$house_data['house_entirel_id']);
                            
                        	$attachmentModel->delete(array("module" => "house" , "entity_id" => $house_id));
                            if ($data['public_pic'] != '')
                            {
                                $data['img'] = explode(',' , $data['public_pic']);
                                if (is_array($data['img']) && !empty($data['img']))
                                {
                                    foreach ($data['img'] as $key => $val)
                                    {
                                        $img_data['key'] = $val;
                                        $img_data['module'] = "house";
                                        $img_data['entity_id'] = $house_id;
                                        $attachmentModel->insertData($img_data);
                                    }
                                }
                            }
                            if (is_array($feeData))
                            {
                                $feeHelper->addFee($feeData , \Common\Model\Erp\Fee::SOURCE_DISPERSE , $house_id,$house_data);
                            }
                            $houseModel->commit();
                            return true;
                        }
                        $houseModel->rollback();
                        return false;
                    }
                    $houseModel->rollback();
                    return false;
                }
                else
                {
                    return false;
                }
            }
            return false;
        }

        /**
         * 获取房源信息以及附加信息
         * 修改时间2015年3月17日 10:25:10
         * 
         * @author yzx
         * @param int $house_id
         * @return array|boolean
         */
        public function getOneHouseData($house_id)
        {
            $houseModel = new \Common\Model\Erp\House();
            $sql = $houseModel->getSqlObject();
            $select = $sql->select(array("h" => "house"))
                    ->leftjoin(array("he" => "house_entirel") , "h.house_id = he.house_id" , array("house_entirel_id" , "money" , "status" , "occupancy_number" , "exist_occupancy_number" , "gender_restrictions"))
                    ->where(array("h.house_id" => $house_id));
            $house_data = $select->execute();
            if (!empty($house_data))
            {
                return $house_data[0];
            }
            return false;
        }

        /**
         * 获取房源所有房间
         * 修改时间2015年3月18日 16:46:08
         * 
         * @author yzx
         * @param int $house_id
         * @return array
         */
        public function getHouseRoom($house_id)
        {
            $houseModel = new \Common\Model\Erp\House();
            $house_data = $houseModel->getOne(array("house_id" => $house_id));
            $sql = $houseModel->getSqlObject();
            $select = $sql->select(array("r" => "room"))
                    ->leftjoin(array("h" => "house") , "r.house_id = h.house_id" , array("house_name"))
                    ->where(array("r.house_id" => $house_id , "r.is_delete" => 0));
            $result = $select->execute();
            if (!empty($result))
            {
                return $result;
            }
            return false;
        }

        /**
         * 获取房源租客
         * 修改时间2015年3月19日 11:19:26
         *
         * @author yzx
         * @param int $house_id
         * @param string $house_type
         * @return Ambigous <multitype:, boolean, unknown>|multitype:
         */
        public function getHoueRental($house_id , $house_type)
        {
            $rentalModel = new \Common\Model\Erp\Rental();
            $house_data = $rentalModel->getTenantByHouseType($house_id , $house_type);
            if ($house_data)
            {
                return $house_data;
            }
            return array();
        }

        /**
         * 获取分散式房源列表并获取不同状态下的数据
         * 修改时间2015年5月4日 15:35:04
         * 
         * @author yzx
         * @param int $page
         * @param int $size
         * @param int $rentalWay
         * @param array $user
         * @param int $areaId
         * @param int $communityId
         * @param array $searchStr
         * @return Ambigous <string, \Common\Helper\Erp\multitype:Ambigous, multitype:, \Common\Model\Erp\Ambigous>|Ambigous <unknown, string, \Common\Helper\Erp\multitype:Ambigous, multitype:, \Common\Model\Erp\Ambigous>
         */
        public function getHouseListData($page , $size , $rentalWay , $user , $areaId = 0 , $communityId = 0 , $searchStr = array(),$state=array())
        {
            /**
             * 取得合租列表
             */
            $getFlatshareList = function($page , $size , $user , $areaId , $communityId , $searchStr,$state=array()) {
                $houseModel = new \Common\Model\Erp\House();
                $sql = $houseModel->getSqlObject();
                $select = $sql->select(array('h' => 'house'));
                $where = new \Zend\Db\Sql\Where();
                $where->equalTo('h.is_delete' , 0);
                $where->equalTo("h.company_id" , $user['company_id']);
                if ($searchStr['custom_number'] || $searchStr['room_type'])
                {
                    $select->join(array('r' => 'room') , 'r.house_id=h.house_id');
                    $where->equalTo('r.is_delete' , 0);
                    if ($searchStr['custom_number'])
                    {
                        $or = new \Zend\Db\Sql\Where();
                        $or->equalTo('h.custom_number' , "{$searchStr['custom_number']}");
                        $or2 = new \Zend\Db\Sql\Where();
                        $or2->equalTo('r.custom_number' , "{$searchStr['custom_number']}");
                        $or->orPredicate($or2);
                        $where->addPredicate($or);
                    }
                    if ($searchStr['room_type'] && $searchStr['room_type'] != 'all')
                    {
                        $where->equalTo('r.room_type' , $searchStr['room_type']);
                    }
                }
                $where->equalTo('h.rental_way' , \Common\Model\Erp\House::RENTAL_WAY_H);
                $select->join(array('c' => 'community') , 'h.community_id=c.community_id' , array('community_id' , 'community_name' , 'business_string'));
                if (isset($user['city_id']))
                {
                    $where->equalTo("c.city_id" , $user['city_id']);
                }
                if ($communityId)
                {
                    $where->equalTo('c.community_id' , $communityId);
                }
                else if ($areaId)
                {
                    $where->equalTo('c.area_id' , $areaId);
                }
                if (!$communityId && $searchStr['community_name'])
                {
                    $where->like('c.community_name' , "%{$searchStr['community_name']}%");
                }
                //权限
                if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
                {
                    $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                    $permisions->VerifyDataCollectionsPermissionsModel($select , 'h.house_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
                }
                $select->where($where);
                //print_r(str_replace('"', '', $select->getSqlString()));die();
                $house_list = array();
                if ($searchStr['custom_number'] || $searchStr['room_type'])
                {//有搜索编号或房间类型
                    $select->group('h.house_id');
                    $house_list = $select->pageSelect($select , null , $page , $size);
                    $house_list['data'] = $house_list['page']['cpage'] < $page ? array() : $house_list['data'];
                }
                else
                {
                    $house_list = $select->pageSelect($select , null , $page , $size);
                }
                if ($house_list['page']['cpage'] < $page)
                {
                    $house_list['data'] = array();
                }
                if ($house_list['data'])
                {
                    $houseIds = array_column($house_list['data'] , 'house_id');
                    $house_list['data'] = array_combine($houseIds , $house_list['data']);
                    $select = $sql->select(array('r' => 'room'));
                    $select->leftjoin(array('rt'=>'rental'), new Expression("rt.room_id=r.room_id and rt.is_delete = 0"),array("rental_id"));
                    $select->leftjoin(array('cr'=>'contract_rental'), new Expression("rt.contract_id=cr.contract_id and cr.is_delete=0"),array("contract_rental_id"));
                    $select->leftjoin(array("t"=>"tenant"), new Expression("cr.tenant_id=t.tenant_id and t.is_delete = 0"),array("gender"));
                    $where = new \Zend\Db\Sql\Where();
                    $where->equalTo('r.is_delete' , 0);
                    if ($searchStr['room_type'] && $searchStr['room_type'] != 'all')
                    {
                        $where->equalTo('r.room_type' , $searchStr['room_type']);
                    }
                    //根据状态筛选
                    if (!empty($state)){
                    		if (in_array(2, $state)){
                    			
                    		}
                    		if (in_array(3, $state)){
                    			$where->equalTo("r.status", $houseModel::STATUS_NOT_RENTAL);
                    		}
                    		if (in_array(4, $state)){
                    			$where->equalTo("r.is_yd",$houseModel::IS_YD);
                    		}
                    		if (in_array(5, $state)){
                    			$where->equalTo("r.is_yytz", $houseModel::IS_YYTZ);
                    		}
                    		if (in_array(6, $state)){
                    			$where->equalTo("r.status", $houseModel::STATUS_IS_STOP);
                    		}
                    		if (in_array(1, $state)){
                    			$where->equalTo("t.gender", 1);
                    		}
                    		if (in_array(2, $state)){
                    			$where->equalTo("t.gender", 2);
                    		}
                    		if (in_array(1, $state) && in_array(2, $state)){
                    			$gender_where = new \Zend\Db\Sql\Where();
                    			$gender_where_2 = new \Zend\Db\Sql\Where();
                    			$gender_where->equalTo("t.gender", 1);
                    			$gender_where_2->equalTo("t.gender", 2);
                    			$gender_where->addPredicate($gender_where_2,$gender_where_2::OP_OR);
                    			$where->addPredicate($gender_where);
                    		}
                    }
                    $where->in('r.house_id' , $houseIds);
                    $select->where($where)->group("r.room_id");
                    $roomData = $select->execute();
                    $temp = array();
                    foreach ($roomData as $value)
                    {
                        if (!isset($temp[$value['house_id']]))
                        {
                            $temp[$value['house_id']] = array();
                        }
                        $temp[$value['house_id']][] = $value;
                    }
                    $roomData = $temp;
                    unset($temp);
                    foreach ($house_list['data'] as $key => $value)
                    {
                        $value['room_data'] = isset($roomData[$key]) ? $roomData[$key] : array();
                        $house_list['data'][$key] = $value;
                    }
                }
                //print_r(str_replace('"', '', $select->getSqlString()));die();
                return $house_list;
            };
            /**
             * 查询整租
             */
            $getEntirerentList = function($page , $size , $user , $areaId , $communityId , $searchStr , $offset = 0,$state=array()) {
                $houseModel = new \Common\Model\Erp\House();
                $sql = $houseModel->getSqlObject();
                $select = $sql->select(array('h' => 'house'));
                $where = new \Zend\Db\Sql\Where();
                $where->equalTo('h.is_delete' , 0);
                $where->equalTo("h.company_id" , $user['company_id']);
                $select->leftjoin(array('he' => 'house_entirel') , 'he.house_id=h.house_id');
                $where->equalTo('he.is_delete' , 0);
                $where->equalTo('h.rental_way' , \Common\Model\Erp\House::RENTAL_WAY_Z);
                $select->leftjoin(array('c' => 'community') , 'h.community_id=c.community_id' , array('community_id' , 'community_name' , 'business_string'));

                $select->leftjoin(array('rt'=>'rental'), new Expression("rt.house_id=h.house_id and rt.is_delete = 0"),array("rental_id"));
                $select->leftjoin(array('cr'=>'contract_rental'), new Expression("rt.contract_id=cr.contract_id and cr.is_delete=0"),array("contract_rental_id"));
                $select->leftjoin(array("t"=>"tenant"), new Expression("cr.tenant_id=t.tenant_id and t.is_delete = 0"),array("gender"));
                if (isset($user['city_id']))
                {
                    $where->equalTo("c.city_id" , $user['city_id']);
                }
                if ($communityId)
                {
                    $where->equalTo('c.community_id' , $communityId);
                }
                else if ($areaId)
                {
                    $where->equalTo('c.area_id' , $areaId);
                }
                if ($searchStr['room_type'])
                {
                    $room_type = explode("t" , $searchStr['room_type']);
                    $where->equalTo('h.count' , $room_type[0]);
                    $where->equalTo('h.hall' , $room_type[1]);
                }

                if (!$communityId && $searchStr['community_name'])
                {
                    $where->like('c.community_name' , "%{$searchStr['community_name']}%");
                }
                if ($searchStr['custom_number'])
                {
                    $where->equalTo('h.custom_number' , "{$searchStr['custom_number']}");
                }
                //权限
                if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
                {
                    $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                    $permisions->VerifyDataCollectionsPermissionsModel($select , 'h.house_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
                }
                //根据状态筛选
                if (!empty($state)){
                	if (in_array(2, $state)){
                		 
                	}
                	if (in_array(3, $state)){
                		$where->equalTo("he.status", $houseModel::STATUS_NOT_RENTAL);
                	}
                	if (in_array(4, $state)){
                		$where->equalTo("he.is_yd",$houseModel::IS_YD);
                	}
                	if (in_array(5, $state)){
                		$where->equalTo("he.is_yytz", $houseModel::IS_YYTZ);
                	}
                	if (in_array(6, $state)){
                		$where->equalTo("he.status", $houseModel::STATUS_IS_STOP);
                	}
                	if (in_array(1, $state)){
                		$where->equalTo("t.gender", 1);
                	}
                	if (in_array(2, $state)){
                		$where->equalTo("t.gender", 2);
                	}
                	if (in_array(1, $state) && in_array(2, $state)){
                		$gender_where = new \Zend\Db\Sql\Where();
                		$gender_where_2 = new \Zend\Db\Sql\Where();
                		$gender_where->equalTo("t.gender", 1);
                		$gender_where_2->equalTo("t.gender", 2);
                		$gender_where->addPredicate($gender_where_2,$gender_where_2::OP_OR);
                		$where->addPredicate($gender_where);
                	}
                }
                
                $select->where($where)->group("h.house_id");
                $house_list = $select->pageSelect($select , null , $page , $size , false , $offset);
                //print_r(str_replace('"', '', $select->getSqlString()));die();
                if ($house_list['page']['cpage'] < $page)
                {
                    $house_list['data'] = array();
                }
                return $house_list;
            };

            $house_list = array();
            if ($rentalWay == 1)
            {//合租
                $house_list = $getFlatshareList($page , $size , $user , $areaId , $communityId , $searchStr,$state);
            }
            elseif ($rentalWay == 2)
            {//整租
                $house_list = $getEntirerentList($page , $size , $user , $areaId , $communityId , $searchStr,0,$state);
            }
            else
            {//都来
                $house_list = $getFlatshareList($page , $size , $user , $areaId , $communityId , $searchStr,$state);
                $house_list_length = count($house_list['data']);
                if ($size > $house_list_length)
                {//合租已经取完了，开始取整租
                    $offset = $house_list['page']['count'] % $size == 0 ? 0 : $size - ($house_list['page']['count'] % $size);//偏移量
                    $_page = $page - $house_list['page']['cpage'];
                    $offset = $_page ? $offset : 0;
                    $data = $getEntirerentList($_page , $size , $user , $areaId , $communityId , $searchStr , $offset,$state);
                    $length = min($size - $house_list_length , count($data['data']));
                    for ($i = 0; $i < $length; $i++)
                    {
                        $value = $data['data'][$i];
                        $house_list['data'][] = $value;
                    }
                }
            }
            //整理数据
            $room_type = array("main" => "主卧" , "second" => "次卧" , "guest" => "客卧");
            $houseids = array();
            $roomids = array();
            foreach ($house_list['data'] as $key => $value)
            {
                if ($value['rental_way'] == \Common\Model\Erp\House::RENTAL_WAY_Z)
                {//整租
                    $value['rental_url'] = \App\Web\Helper\Url::parse("Tenant-Index/adds/room_id/0/house_room_id/{$value['house_id']}/house_type/1");
                    $value['config'] = $this->creatConfig($value['public_facilities']);
                    $value['record_id'] = $value['house_id'];
                    $houseids[] = $value['house_id'];
                }
                else
                {//合租
                    foreach ($value['room_data'] as $roomKey => $room)
                    {
                        $room["detail_url"] = \App\Web\Helper\Url::parse("House-Room/roomdetail/room_id/{$room['room_id']}");
                        $room['rental_url'] = \App\Web\Helper\Url::parse("Tenant-Index/adds/room_id/{$room['room_id']}/house_room_id/{$room['house_id']}/house_type/1");
                        $room['config'] = $this->creatConfig($room['room_config']);
                        $room['record_id'] = $room['room_id'];
                        $room['room_type_c'] = $room_type[$room['room_type']];
                        $room['community_name'] = $value['community_name'];
                        $room['business_string'] = $value['business_string'];
                        $room['custom_number'] = '编号' . $room['custom_number'];
                        $value['room_data'][$roomKey] = $room;
                        if ($room['room_id'] > 0)
                        {
                            $roomids[] = $room['room_id'];
                        }
                    }
                }
                $value['url'] = \App\Web\Helper\Url::parse("House-House/edit/house_id/{$value['house_id']}");
                $house_list['data'][$key] = $value;
            }
            //出租的数据
            $rentalModel = new \Common\Model\Erp\Rental();
            $rental_room_data = empty($roomids) ? array() : $rentalModel->getRoomData($roomids);
            $rental_house_data = empty($houseids) ? array() : $rentalModel->getFocusData($houseids , $rentalModel::HOUSE_TYPE_R);
            //空置天数
            $empt_room_day = empty($roomids) ? array() : $this->getEmptyData($roomids , true);
            $empt_house_day = empty($houseids) ? array() : $this->getEmptyData($houseids);
            //停用
            $stopHouseModel = new StopHouse();
            $stop_room_data = empty($roomids) ? array() : $stopHouseModel->getDataBySourceId($roomids , StopHouse::DISPERSE_TYPE , true);
            $stop_house_data = empty($houseids) ? array() : $stopHouseModel->getDataBySourceId($houseids , StopHouse::DISPERSE_TYPE);
            //预约退租
            $reserveBackRentalModle = new ReserveBackRental();
            $yytz_house_data = empty($houseids) ? array() : $reserveBackRentalModle->getDataBySourceId($houseids , ReserveBackRental::DISPERSE_TYPE , ReserveBackRental::HOUSE_TYPE_HOUSE);
            $yytz_room_data = empty($roomids) ? array() : $reserveBackRentalModle->getDataBySourceId($roomids , ReserveBackRental::DISPERSE_TYPE , ReserveBackRental::HOUSE_TYPE_ROOM);
            //预定
            $reserveModel = new \Common\Model\Erp\Reserve();
            $yd_house_data = empty($houseids) ? array() : $reserveModel->getFirst(\Common\Model\Erp\Reserve::HOUSE_TYPE_R , $houseids);
            $yd_room_data = empty($roomids) ? array() : $reserveModel->getFirst(\Common\Model\Erp\Reserve::HOUSE_TYPE_R , $roomids , true);
      
            foreach ($house_list['data'] as $key => $value)
            {
                if ($value['rental_way'] == \Common\Model\Erp\House::RENTAL_WAY_Z)
                {//整租
                    switch ($value['status'])
                    {
                        case \Common\Model\Erp\House::STATUS_IS_RENTAL://出租
                            if (isset($rental_house_data[$value['house_id']]))
                            {
                                $value["msg"] = $rental_house_data[$value['house_id']];
                                $value["is_relet"] = $rental_house_data[$value['house_id']]['is_relet'];
                                $value["sex"] = $value["msg"]['sex'];
                                $value['relet_url'] = \App\Web\Helper\Url::parse("Tenant-Index/relet/contract_id/{$value['msg']['contract_id']}");
                            }
                            break;
                        case \Common\Model\Erp\House::STATUS_NOT_RENTAL://未租
                            if (isset($empt_house_day[$value['house_id']]))
                            {
                                $value["emp_msg"] = $empt_house_day[$value['house_id']];
                            }
                            break;
                        case \Common\Model\Erp\House::STATUS_IS_STOP://停用
                            if (isset($stop_house_data[$value['house_id']]))
                            {
                                $value['stop_msg'] = $stop_house_data[$value['house_id']];
                            }
                            break;
                    }
                    if ($value['is_yytz'] && isset($yytz_house_data[$value['house_id']]))
                    {//预约退租
                        $value['msg_yytz'] = $yytz_house_data[$value['house_id']];
                    }
                    if ($value['is_yd'] && isset($yd_house_data[$value['house_id']]))
                    {//预定
                        $value['msg_yd'] = $yd_house_data[$value['house_id']];
                        $value['reserve_id'] = $value['msg_yd']['reserve_id'];
                    }
                }
                else
                {//合租
                    foreach ($value['room_data'] as $roomKey => $room)
                    {
                        switch ($room['status'])
                        {
                            case \Common\Model\Erp\House::STATUS_IS_RENTAL://出租
                                if (isset($rental_room_data[$room['room_id']]))
                                {
                                    $room["msg"] = $rental_room_data[$room['room_id']];
                                    $room["is_relet"] = $rental_room_data[$room['room_id']]['is_relet'];
                                    $room["sex"] = $room["msg"]['sex'];
                                    $room['relet_url'] = \App\Web\Helper\Url::parse("Tenant-Index/relet/contract_id/{$room['msg']['contract_id']}");
                                }
                                break;
                            case \Common\Model\Erp\House::STATUS_NOT_RENTAL://未租
                                if (isset($empt_room_day[$room['room_id']]))
                                {
                                    $room["emp_msg"] = $empt_room_day[$room['room_id']];
                                }
                                break;
                            case \Common\Model\Erp\House::STATUS_IS_STOP://停用
                                if (isset($stop_room_data[$room['room_id']]))
                                {
                                    $room['stop_msg'] = $stop_room_data[$room['room_id']];
                                }
                                break;
                        }
                        if ($room['is_yytz'] && isset($yytz_room_data[$room['room_id']]))
                        {//预约退租
                            $room['msg_yytz'] = $yytz_room_data[$room['room_id']];
                        }
                        if ($room['is_yd'] && isset($yd_room_data[$room['room_id']]))
                        {//预定
                            $room['msg_yd'] = $yd_room_data[$room['room_id']];
                            $room['reserve_id'] = $room['msg_yd']['reserve_id'];
                        }
                        $value['room_data'][$roomKey] = $room;
                    }
                }
                $house_list['data'][$key] = $value;
            }
            return $house_list;
        }

        /**
         * 构建搜索条件
         * 修改时间2015年3月28日 10:27:12
         * 
         * @author yzx
         * @param unknown_type $serche
         * @return \Zend\Db\Sql\Where|boolean
         */
        private function likSql($serche)
        {
            if (!empty($serche))
            {
                if ($serche['community_name'] != null)
                {
                    $community_where = new Where();
                    $community_where->like("c.community_name" , "%" . $serche['community_name'] . "%");
                }
                if ($serche['custom_number'] != null)
                {
                    $house_where = new Where();
                    $room_where = new Where();
                    $house_where->like("h.custom_number" , "%" . $serche['custom_number'] . "%");
                    $room_where->like("r.custom_number" , "%" . $serche['custom_number'] . "%");
                    $house_where->addPredicate($room_where , Where::OP_OR);
                }
                if (isset($community_where) && isset($house_where))
                {
                    $where = $house_where->addPredicate($community_where , Where::OP_OR);
                }
                if (isset($house_where) && !isset($community_where))
                {
                    $where = $house_where;
                }
                if (isset($community_where) && !isset($house_where))
                {
                    $where = $community_where;
                }
                if (isset($where))
                {
                    return $where;
                }
                return false;
            }
            return false;
        }

        /**
         * 取得用户的小区
         * @author lishengyou
         * 最后修改时间 2015年4月1日 下午4:25:56
         *
         * @param unknown $user_id
         */
        public function getUserHouseCommunity($user_id)
        {
            $model = new \Common\Model\Erp\User();
            $user = $model->getOne(array('user_id' => $user_id));
            if (!$user)
            {
                return array();
            }
            return $this->getCompanyHouseCommunity($user['company_id']);
        }

        /**
         * 取得公司的小区
         * @author lishengyou
         * 最后修改时间 2015年4月1日 下午4:26:55
         *
         * @param unknown $company_id
         */
        public function getCompanyHouseCommunity($company_id)
        {
            if (!$company_id)
                return array();
            //统计小区编号
            $model = new \Common\Model\Erp\House();
            $sql = $model->getSqlObject();
            $select = $sql->select($model->getTableName());
            $select->where(array('company_id' => $company_id));
            $select->group('community_id');
            $select->columns(array('community_id' => 'community_id'));
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
            return $model->getData(array('community_id' => $communityIds));
        }

        /**
         * 统计分散式全部房间
         * 修改时间2015年6月4日 16:22:50
         * 
         * @author yzx
         * @param array $user
         * @return Ambigous <multitype:, NULL, \ArrayObject, \Zend\Db\ResultSet\mixed, unknown>
         */
        public function countHouse($user)
        {
            $houseModel = new \Common\Model\Erp\House();
            $sql = $houseModel->getSqlObject();
            $select = $sql->select(array("hv" => "house_view"));
            $select->leftjoin(array("c" => "community") , "hv.community_id=c.community_id" , array("community_name"));
            $select->leftjoin(array("a" => "area") , "c.area_id=a.area_id" , array("name"));
            //权限
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisions->VerifyDataCollectionsPermissionsModel($select , 'hv.house_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
            }
            $select->where(array("hv.company_id" => $user['company_id']) , array('all_house' => new Expression("COUNT(hv.house_id)")));
            $select->where(array("hv.is_delete" => 0 , "a.city_id" => $user['city_id']));
            $select->columns(array("all_count" => new Expression("count(hv.house_id)")));
            $result = $select->execute();
            return $result ? $result[0]['all_count'] : 0;
        }

        /**
         * 获取空置天数
         * 修改时间2015年5月4日 15:42:55
         * 
         * @author yzx
         * @param unknown $houseId
         * @return multitype:number unknown
         */
        public function getEmptyData($houseId , $isRoom = false)
        {
            $houseModel = new \Common\Model\Erp\House();
            $sql = $houseModel->getSqlObject();
            $select = $sql->select(array("hv" => "house_view"));
            $select->leftjoin(array("r" => "rental") , "hv.house_id=r.room_id" , array("rental_id"));
            $select->leftjoin(array("tc" => "tenant_contract") , new Expression("(r.contract_id = tc.contract_id and r.house_type=1)"));
            if ($isRoom)
            {
                if (is_array($houseId))
                {
                    $where = new Where();
                    $where->in("hv.record_id" , $houseId);
                }
                else
                {
                    $where = array('hv.record_id' => $houseId);
                }
                $select->where($where)->group("hv.record_id");
            }
            else
            {
                if (is_array($houseId))
                {
                    $where = new Where();
                    $where->in("hv.house_id" , $houseId);
                }
                else
                {
                    $where = array('hv.house_id' => $houseId);
                }
                $select->where($where)->group("hv.house_id");
            }
            $select->order("tc.dead_line DESC");
            //print_r(str_replace('"', '', $select->getSqlString()));die();
            $result = $select->execute();
            $data = $result['data'][0];
            $time = time();
            $output_data = array();
            if (is_array($houseId))
            {
                if (!empty($result))
                {
                    foreach ($result as $key => $val)
                    {
                        //合租
                        if ($isRoom)
                        {
                            if ($val['dead_line'] <= 0)
                            {
                                $days = number_format((($time - $val['create_time']) / 86400) + 1 , 0);
                                $days = $days > 0 ? $days : 1;
                                $output_data[$val['record_id']]['day'] = $days;
                            }
                            elseif ($val['dead_line'] >= 0)
                            {
                                $days = number_format((($time - $val['dead_line']) / 86400) + 1 , 0);
                                $days = $days > 0 ? $days : 1;
                                $output_data[$val['record_id']]['day'] = $days;
                            }
                            $output_data[$val['record_id']]['money'] = $val['money'] ? $val['money'] : 0;
                        }
                        //整租
                        if (!$isRoom)
                        {
                            $output_data[$val['house_id']] = $val;
                            if ($val['dead_line'] <= 0)
                            {
                                $days = number_format((($time - $val['create_time']) / 86400) + 1 , 0);
                                $days = $days > 0 ? $days : 1;
                                $output_data[$val['house_id']]['day'] = $days;
                            }
                            elseif ($val['dead_line'] >= 0)
                            {
                                $days = number_format((($time - $val['dead_line']) / 86400) + 1 , 0);
                                $days = $days > 0 ? $days : 1;
                                $output_data[$val['house_id']]['day'] = $days;
                            }
                            $output_data[$val['house_id']]['money'] = $val['money'] ? $val['money'] : 0;
                        }
                    }
                }
            }
            else
            {
                if (!empty($data))
                {
                    if ($data['dead_line'] <= 0)
                    {
                        $day = (($time - $data['create_time']) / 86400) + 1;
                    }
                    elseif ($data['dead_line'] >= 0)
                    {
                        $day = (($time - $data['dead_line']) / 86400) + 1;
                    }
                }
                $days = $days > 0 ? $days : 1;
                $output_data['day'] = $days;
                $output_data['money'] = $data['money'] ? $data['money'] : 0;
            }
            return $output_data;
        }

        /**
         * 创建列表配置
         * 
         * @author yzx
         * @param string $config
         * @return multitype:Ambigous <multitype:> |multitype:
         */
        private function creatConfig($config)
        {
            $sysConfig = new \Common\Model\Erp\SystemConfig();
            $config_data = $sysConfig->getFind("House" , "public_facilities");
            $config_data_key = array_keys($config_data);
            $data = array();
            if ($config != '')
            {
                $config_arr = explode("-" , $config);
                foreach ($config_arr as $key => $var)
                {
                    if (in_array($var , $config_data_key))
                    {
                        $data[] = $config_data[$var];
                    }
                }
                $comfig_map = array("阳台" , "飘窗" , "卫生间");
                $output_data = array();
                foreach ($data as $dkey => $dval)
                {
                    if (in_array($dval , $comfig_map))
                    {
                        $output_data[] = $dval;
                    }
                }
                $data = implode(" " , $output_data);
                return $data;
            }
            return array();
        }

        /**
         * 计算月空置率
         * @param unknown $user
         */
        public function calculateMonthEmpty($user , $isYear = false)
        {
            $houseModel = new \Common\Model\Erp\House();
            $sql = $houseModel->getSqlObject();
            $select = $sql->select(array("r" => "room"));
            $select->leftjoin(array("h" => "house") , "r.house_id=h.house_id" , array("rental_way"));
            $select->leftjoin(array('rt' => 'rental') , new Expression('r.room_id=rt.room_id and rt.is_delete = 0') , array("rental_id"));
            $select->leftjoin(array("tc" => 'tenant_contract') , new Expression('tc.contract_id=rt.contract_id and tc.is_delete=0') , array("end_line" , "signing_time"));
            $select->leftjoin(array('lc' => 'landlord_contract') , "h.house_id=lc.house_id" , array("free_day"));
            $select->leftjoin(array("c" => "community") , "h.community_id=c.community_id" , array("community_name"));
            $select->leftjoin(array("a" => "area") , "c.area_id=a.area_id" , array("name"));
            //权限
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisions->VerifyDataCollectionsPermissionsModel($select , 'h.house_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
            }
            $select->where(array("h.company_id" => $user['company_id']));
            $select->where(array("r.is_delete" => 0));
            $select->where(array("h.rental_way" => $houseModel::RENTAL_WAY_H));
            $select->where(array("a.city_id" => $user['city_id']));
            $select->group('r.room_id');
            $select->order("lc.free_day desc");
            $room_data = $select->execute();
            //print_r(str_replace('"', '', $select->getSqlString()));die();
            unset($select);
            $select = $sql->select(array("h" => "house"));
            $select->leftjoin(array('rt' => 'rental') , new Expression('h.house_id=rt.house_id and rt.is_delete = 0') , "rental_id");
            $select->leftjoin(array("tc" => 'tenant_contract') , new Expression('tc.contract_id=rt.contract_id and tc.is_delete=0') , array("end_line" , "signing_time"));
            $select->leftjoin(array('lc' => 'landlord_contract') , "h.house_id=lc.house_id" , array("free_day"));
            $select->leftjoin(array("c" => "community") , "h.community_id=c.community_id" , array("community_name"));
            $select->leftjoin(array("a" => "area") , "c.area_id=a.area_id" , array("name"));
            //权限
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisions->VerifyDataCollectionsPermissionsModel($select , 'h.house_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
            }
            $select->where(array("h.company_id" => $user['company_id']));
            $select->where(array("h.is_delete" => 0));
            $select->where(array("h.rental_way" => $houseModel::RENTAL_WAY_Z));
            $select->where(array("a.city_id" => $user['city_id']));
            $select->group('h.house_id');
            //print_r(str_replace('"', '', $select->getSqlString()));die();
            $house_data = $select->execute();
            $select_data = array_merge($room_data , $house_data);
            $emp_day = 0;
            if (!empty($select_data))
            {
                foreach ($select_data as $key => $val)
                {
                    $one_emp_day = $this->emptyDay($val , $isYear);
                    $entering_day = $this->enteringDay($val);
                    if ($isYear)
                    {
                        $entering_day = $this->enteringYear($val);
                    }
                    $this->day+=($one_emp_day / $entering_day);
                }
                $emp_day = number_format(($this->day / count($select_data)) * 100 , 2);
                $this->day = 0;
            }
            return $emp_day > 0 ? $emp_day : 0;
        }

        /**
         * 一间房间空置天数
         * 修改时间2015年5月20日 13:39:23
         * 
         * @author yzx
         * @param unknown $data
         * @return number
         */
        private function emptyDay($data , $isYear = false)
        {
            if ($data['house_id'] <= 0)
            {
                return 0;
            }
            $time = time();
            //创建天数
            $entring_day = $this->enteringDay($data);
            if ($isYear)
            {
                $entring_day = $this->enteringYear($data);
            }
            $rental_day = 0;
            if ($data['end_line'] > 0)
            {
                if ($isYear == false)
                {
                    $begin_month_time = date('Y-m-01' , strtotime(date("Y-m-d")));
                    $end_month_time = strtotime(date("Y-m-t"));
                    //出租天数
                    if ($data['signing_time'] >= strtotime($begin_month_time))
                    {
                        $rental_day = floor(($time - ($data['signing_time'])) / 86400) + 1;
                    }
                    if ($data['signing_time'] < strtotime($begin_month_time))
                    {
                        $rental_day = floor(($time - (strtotime($begin_month_time))) / 86400) + 1;
                    }
                }
                if ($isYear)
                {
                    $begin_year_date = date('Y') . "-01" . "-01";
                    $end_year_data = date('Y-12-t');
                    //出租天数
                    if ($data['signing_time'] >= strtotime($begin_year_date))
                    {
                        $rental_day = floor(($time - ($data['signing_time'])) / 86400) + 1;
                    }
                    if ($data['signing_time'] < strtotime($begin_year_date))
                    {
                        $rental_day = floor(($time - (strtotime($begin_year_date))) / 86400) + 1;
                    }
                }
            }
            //免租天数
            $free_day = $data['free_day'];
            $day = $entring_day - $rental_day - ($free_day);
            if ($data['end_line'] > 0)
            {
                if (($data['signing_time'] + $free_day * 86400) < $data['create_time'])
                {
                    $day = $entring_day - $rental_day;
                }
            }
            if ($day <= 0)
            {
                $day = 0;
            }
            return $day;
        }

        /**
         * 月录入天数
         * 修改时间2015年5月20日 14:19:42
         * 
         * @author yzx
         * @param unknown $data
         * @return Ambigous <number, string>
         */
        private function enteringDay($data)
        {
            $time = time();
            $creat_day = floor(($time - $data['create_time']) / 86400);
            $begin_date = date('Y-m-01' , strtotime(date("Y-m-d")));
            $end_data = date('d' , strtotime("$begin_date +1 month -1 day"));
            $this_month = date("Y-m" , time());
            //当前月最后一天
            $end_month_time = strtotime($this_month . "-" . $end_data);
            if ($data['create_time'] >= strtotime($begin_date))
            {
                $this_day = date("d" , time());
                $begin_day = date("d",strtotime($begin_date));
                $creat_day = floor(intval($this_day) - intval($begin_day));
                $day = $creat_day + 1;
            }
            if ($data['create_time'] < strtotime($begin_date))
            {
                $day = $end_data;
            }
            return $day;
        }

        /**
         * 年录入天数
         * 修改时间2015年5月21日 14:51:14
         *
         * @author yzx
         * @param unknown $data
         */
        private function enteringYear($data)
        {
            $time = time();
            $begin_date = date('Y') . "-01" . "-01";
            $end_data = date("Y") . "-12" . "-31";
            if ($data['create_time'] >= strtotime($begin_date))
            {
                $creat_day = (($data['create_time']-strtotime($begin_date)) / 86400) + 1;
            }
            if ($data['create_time'] < strtotime($begin_date))
            {
                $creat_day = 365;
            }
           $creat_day = number_format($creat_day,2);
            return $creat_day;
        }

        /**
         * 获取房源的租客合同
         * 修改时间2015年6月4日18:07:29
         * 
         * @author yzx
         * @param unknown $data
         */
        public function getHouseContract($data , $isRoom = false)
        {
            $rentalModel = new \Common\Model\Erp\Rental();
            $sql = $rentalModel->getSqlObject();
            if ($isRoom)
            {
                $select = $sql->select(array("r" => 'room'));
                $select->leftjoin(array('rt' => "rental") , "rt.room_id=r.room_id" , array("is_delete"));
                $select->leftjoin(array("tc" => 'tenant_contract') , "rt.contract_id=tc.contract_id" , array("parent_id" , "contract_id",'signing_time','end_line','next_pay_time','is_haveson'));
                $select->where(array("rt.room_id" => $data['room_id']));
                $select->where(array("r.status" => Room::STATIS_RENTAL));
            }
            else
            {
                $select = $sql->select(array("h" => 'house_entirel'));
                $select->leftjoin(array('rt' => "rental") , "h.house_id=rt.house_id" , array("is_delete"));
                $select->leftjoin(array("tc" => 'tenant_contract') , "rt.contract_id=tc.contract_id" , array("parent_id" , 'contract_id','signing_time','end_line','next_pay_time','is_haveson'));
                $select->where(array("rt.house_id" => $data['house_id']));
                $select->where(array("h.status" => \Common\Model\Erp\House::STATUS_IS_RENTAL));
            }
            $select->where(array("rt.house_type" => $rentalModel::HOUSE_TYPE_R));
            $select->where(array("rt.is_delete"=>0,"tc.is_stop"=>0));
            
            $result = $select->execute();
            //print_r(str_replace('"', '', $select->getSqlString()));die();
            $contract_id = 0;
            if (!empty($result))
            {
                foreach ($result as $key => $val)
                {
                	if ($val['is_haveson'] ==1 && $val['next_pay_time'] < $val['end_line']){
                		$contract_id = $val['contract_id'];
                		return $contract_id;
                	}else 
                    {
                    	if ($val['is_haveson'] ==0){
                    		$contract_id = $val['contract_id'];
                    		return $contract_id;
                    	}
                    }
                }
            }
            return 0;
        }

    }
    