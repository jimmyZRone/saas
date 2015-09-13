<?php

    namespace Common\Helper\Erp;

    use Zend\Db\Sql\Where;
    use Core\Db\Sql\Select;
    use Zend\Db\Sql\Expression;

    class Room extends \Common\Model\Erp\Room
    {

        protected $house_id = null;

        /**
         * 获取分散式合租列表
         * 修改时间2015年3月26日 15:18:01
         * 
         * @author yzx
         * @param int $communityId
         * @param int $page
         * @param int $size
         * @return multitype:multitype:Ambigous <number, unknown> number  Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >
         */
        public function roomList($page , $size , $user , $serche = array() , $areaId = 0)
        {
            $r_t_where = new Where();
            $c_where = new Where();
            $cp_where = new Where();
            $cp_where->equalTo("h.company_id" , $user['company_id']);
            $like_where = $this->likSql($serche);
            $r_t_where->equalTo("rt.house_type" , 1);
            $r_t_where->addPredicate($cp_where);
            if ($areaId > 0)
            {
                $c_where->equalTo("a.area_id" , $areaId);
            }
            $select = $this->_sql_object->select(array("r" => "room"))
                    ->leftjoin(array("rt" => "rental") , "r.room_id = rt.room_id" , array("contract_id"))
                    ->leftjoin(array("h" => "house") , "r.house_id = h.house_id" , array("houseId" => "house_id" , "house_name"))
                    ->leftjoin(array("he" => "house_entirel") , "h.house_id = he.house_id" , "*")
                    ->leftjoin(array("c" => "community") , "h.community_id = c.community_id" , array("community_name"))
                    ->leftjoin(array("a" => "area") , "c.area_id = a.area_id" , array("name"))
                    ->leftjoin(array("re" => "reserve") , "rt.tenant_id = re.tenant_id" , array("reserve_id"));
            if ($like_where)
            {
                if ($areaId > 0)
                {
                    $r_t_where->addPredicate($c_where);
                }
                $r_t_where->addPredicate($like_where);
                $select->where($r_t_where);
            }
            else
            {
                if ($areaId > 0)
                {
                    $r_t_where->addPredicate($c_where);
                }
                else
                {
                    $select->where($r_t_where);
                }
            }
            //添加房源状态搜索条件
            if ($serche['house_status'] != null)
            {
                $house_status_where = new Where();
                $house_status_where->equalTo("r.status" , $serche['house_status']);
                $select->where($house_status_where);
            }
            //print_r(str_replace('"', '', $select->getSqlString()));die();
            $result = Select::pageSelect($select , $page , null , $size);
            ;
            if (!empty($result))
            {
                return $result['data'];
            }
            return false;
        }

        /**
         * 获取房间退租信息
         * 修改时间 2015年5月19日21:05:10
         * 
         * @author ft
         */
        public function getRoomExpenseFeeInfo($room_id , $house_id , $house_type , $cid)
        {
            $room_model = new \Common\Model\Erp\Room();
            if ($house_type == 1)
            {
                $sql = $room_model->getSqlObject();
                if ($room_id > 0)
                {//合租
                    $select = $sql->select(array('r' => 'room'));
                    $select->columns(array('r_custom_num' => 'custom_number' , 'room_type' => 'room_type'));
                    $select->leftjoin(
                            array('h' => 'house') , 'r.house_id = h.house_id' , array(
                        'h_name' => 'house_name' ,
                        'h_custom_num' => 'custom_number' ,
                        'h_cost' => 'cost' ,
                        'h_unit' => 'unit' ,
                        'h_number' => 'number' ,
                        'rental_way' => 'rental_way'
                    ));
                    $select->leftjoin(array('re' => 'rental') , new Expression('(r.room_id = re.room_id AND re.house_type = 1)') , array('house_type' => 'house_type'));
                    $select->leftjoin(array('tc' => 'tenant_contract') , 're.contract_id = tc.contract_id' , array('deposit' => 'deposit'));
                    $where = new \Zend\Db\Sql\Where();
                    $where->equalTo('r.room_id' , $room_id);
                    $where->equalTo('re.house_type' , $house_type);
                    $where->equalTo('tc.company_id' , $cid);
                    $select->where($where);
                    $shared_data = $select->execute();
                    $shared_data[0]['room_type'] = ($shared_data[0]['room_type'] == 'main') ? '主卧' : (($shared_data[0]['room_type'] == 'second') ? '次卧' : (($shared_data[0]['room_type'] == 'guest') ? '客卧' : ''));
                    return $shared_data;
                }
                else
                {//整组
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
                    $select->leftjoin(array('re' => 'rental') , new Expression('(re.house_id = h.house_id AND re.house_type = 1)') , array('house_type' => 'house_type'));
                    $select->leftjoin(array('tc' => 'tenant_contract') , 're.contract_id = tc.contract_id' , array('deposit' => 'deposit'));
                    $where = new \Zend\Db\Sql\Where();
                    $where->equalTo('re.house_id' , $house_id);
                    $where->equalTo('re.house_type' , $house_type);
                    $where->equalTo('tc.company_id' , $cid);
                    $select->where($where);
                    return $select->execute();
                }
            }
            else
            {//集中式
                $sql = $room_model->getSqlObject();
                $select = $sql->select(array('rf' => 'room_focus'));
                $select->columns(array('rf_floor' => 'floor' , 'rf_custom_num' => 'custom_number' , 'room_focus_id' => 'room_focus_id'));
                $select->leftjoin(
                        array('f' => 'flat') , 'rf.flat_id = f.flat_id' , array(
                    'f_name' => 'flat_name' ,
                    'f_custom_num' => 'custom_number' ,
                    'address' => 'address' ,
                ));
                $select->leftjoin(array('re' => 'rental') , new Expression('(rf.room_focus_id = re.room_id AND re.house_type = 2)') , array('house_type' => 'house_type'));
                $select->leftjoin(array('tc' => 'tenant_contract') , 're.contract_id = tc.contract_id' , array('deposit' => 'deposit'));
                $where = new \Zend\Db\Sql\Where();
                $where->equalTo('rf.room_focus_id' , $room_id);
                $where->equalTo('re.room_id' , $room_id);
                $where->equalTo('re.house_type' , $house_type);
                $where->equalTo('tc.company_id' , $cid);
                $select->where($where);
//                 print_r($select->getSqlString());die;
                return $select->execute();
            }
        }

        /**
         * 获取房间的费用类型
         * 修改时间 2015年5月6日19:48:37
         * 
         * @author ft
         */
        public function getRoomFeeType($source , $source_id)
        {
            $serial_model = new \Common\Model\Erp\SerialNumber();
            $sql = $serial_model->getSqlObject();
            $select = $sql->select(array('r' => 'room'));
            $select->columns(array('room_id' => 'room_id' , 'r_custom_num' => 'custom_number'));
            $select->leftjoin(
                    array('h' => 'house') , 'r.house_id = h.house_id' , array(
                'room_id' => 'house_id' ,
                'h_name' => 'house_name' ,
                'h_cost' => 'cost' ,
                'h_unit' => 'unit' ,
                'h_number' => 'number' ,
                'h_custom_num' => 'custom_number'
            ));
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('r.room_id' , $source_id);
            $select->where($where);
            $data['room_name'] = $select->execute();

            $sql2 = $serial_model->getSqlObject();
            $select2 = $sql2->select(array('r' => 'room'));
            $select2->columns(array());
            $select2->leftjoin(array('fee' => 'fee') , 'fee.source_id = r.room_id' , array('fee_id' => 'fee_id' , 'payment_mode' => 'payment_mode' , 'fee_money' => 'money' ,));
            $select2->leftjoin(array('ft' => 'fee_type') , 'fee.fee_type_id = ft.fee_type_id' , array('fee_type_id' => 'fee_type_id' , 'type_name' => 'type_name'));
            $where2 = new \Zend\Db\Sql\Where();
            $where2->equalTo('r.room_id' , new Expression('fee.source_id'));
            $where2->equalTo('r.room_id' , $source_id);
            $select2->where($where2);
            $data['fee_list'] = $select2->execute();
            return $data;
        }

        /**
         * 根据房源/房间id查询房间名称
         * 修改时间 2015年5月26日20:34:29
         * 
         * @author ft
         */
        public function getRoomInfoById($reserve_info)
        {
            $room_model = new \Common\Model\Erp\Room();
            $room_foucus = new \Common\Model\Erp\RoomFocus();
            $sql = $room_model->getSqlObject();
            if ($reserve_info['house_type'] == 1)
            {//分散式
                if ($reserve_info['room_id'] > 0)
                {//合租
                    $select = $sql->select(array('r' => 'room'));
                    $select->columns(array('r_custom_num' => 'custom_number' , 'room_type' => 'room_type'));
                    $select->leftjoin(
                            array('h' => 'house') , 'r.house_id = h.house_id' , array(
                        'h_name' => 'house_name' ,
                        'h_custom_num' => 'custom_number' ,
                        'h_cost' => 'cost' ,
                        'h_unit' => 'unit' ,
                        'h_number' => 'number' ,
                        'rental_way' => 'rental_way'
                    ));
                    $where = new \Zend\Db\Sql\Where();
                    $where->equalTo('r.is_delete' , 0);
                    $where->equalTo('h.is_delete' , 0);
                    $where->equalTo('r.room_id' , $reserve_info['room_id']);
                    $where->equalTo('h.house_id' , $reserve_info['house_id']);
                    $select->where($where);
                    $room_data = $select->execute();
                    $room_data[0]['house_type'] = $reserve_info['house_type'];
                    $room_data[0]['room_type'] = ($room_data[0]['room_type'] == 'main' ? '主卧' : (($room_data[0]['room_type'] == 'second') ? '次卧' : (($room_data[0]['room_type'] == 'guest' ? '客卧' : ''))));
                    return $room_data;
                }
                else
                {//整租
                    $select = $sql->select(array('h' => 'house'));
                    $select->columns(array('h_name' => 'house_name' , 'h_custom_num' => 'custom_number' , 'h_cost' => 'cost' , 'h_unit' => 'unit' , 'h_number' => 'number' , 'rental_way' => 'rental_way'));
                    $where = new \Zend\Db\Sql\Where();
                    $where->equalTo('h.is_delete' , 0);
                    $where->equalTo('h.house_id' , $reserve_info['house_id']);
                    $select->where($where);
                    $room_data = $select->execute();
                    $room_data[0]['house_type'] = $reserve_info['house_type'];
                    return $room_data;
                }
            }
            else
            {//集中式
                $sql = $room_foucus->getSqlObject();
                $select = $sql->select(array('rf' => 'room_focus'));
                $select->columns(array('floor' => 'floor' , 'rf_custom_num' => 'custom_number'));
                $select->leftjoin(array('f' => 'flat') , 'rf.flat_id = f.flat_id' , array('flat_name' => 'flat_name' , 'f_custom_num' => 'custom_number' , 'address' => 'address'));
                $where = new \Zend\Db\Sql\Where();
                $where->equalTo('rf.is_delete' , 0);
                $where->equalTo('f.is_delete' , 0);
                $where->equalTo('rf.room_focus_id' , $reserve_info['room_id']);
                $select->where($where);
                $room_data = $select->execute();
                $room_data[0]['house_type'] = $reserve_info['house_type'];
                return $room_data;
            }
        }

        /**
         * 获取房源/房间的名字
         * 修改时间  2015年6月9日18:51:16
         * 
         * @author ft
         */
        public function getRoomNameById($house_id , $room_id , $house_type)
        {
            if ($house_type == 1)
            {//分散
                if ($house_id > 0 && $room_id > 0)
                {//合租
                    $room_model = new \Common\Model\Erp\Room();
                    $sql = $room_model->getSqlObject();
                    $select = $sql->select(array('r' => 'room'));
                    $select->columns(array('room_id' => 'room_id' , 'r_custom_num' => 'custom_number' , 'house_id' => 'house_id' , 'room_type' => 'room_type' ,));
                    $select->leftjoin(
                            array('h' => 'house') , 'r.house_id = h.house_id' , array(
                        'house_name' => 'house_name' ,
                        'h_custom_num' => 'custom_number' ,
                        'h_cost' => 'cost' ,
                        'h_unit' => 'unit' ,
                        'h_number' => 'number' ,
                        'rental_way' => 'rental_way' ,
                    ));
                    $where = new \Zend\Db\Sql\Where();
                    $where->equalTo('r.room_id' , $room_id);
                    $where->equalTo('h.house_id' , $house_id);
                    $select->where($where);
                    $shared_data = $select->execute();
                    $shared_data[0]['room_type'] = ($shared_data[0]['room_type'] == 'main' ? '主卧' : (($shared_data[0]['room_type'] == 'second') ? '次卧' : (($shared_data[0]['room_type'] == 'guest' ? '客卧' : ''))));
                    return $shared_data;
                }
                else
                {//整租
                    $house_model = new \Common\Model\Erp\House();
                    $sql = $house_model->getSqlObject();
                    $select = $sql->select(array('h' => 'house'));
                    $select->columns(
                            array(
                                'house_id' => 'house_id' ,
                                'house_name' => 'house_name' ,
                                'h_custom_num' => 'custom_number' ,
                                'h_cost' => 'cost' ,
                                'h_unit' => 'unit' ,
                                'h_number' => 'number' ,
                                'rental_way' => 'rental_way'
                    ));
                    $where = new \Zend\Db\Sql\Where();
                    $where->equalTo('h.house_id' , $house_id);
                    $select->where($where);
                    return $select->execute();
                }
            }
            else
            {//集中式
                $room_focus_model = new \Common\Model\Erp\RoomFocus();
                $sql = $room_focus_model->getSqlObject();
                $select = $sql->select(array('rf' => 'room_focus'));
                $select->columns(array('room_id' => 'room_focus_id' , 'floor' => 'floor' , 'rf_custom_num' => 'custom_number'));
                $select->leftjoin(
                        array('f' => 'flat') , 'rf.flat_id = f.flat_id' , array(
                    'f_name' => 'flat_name' ,
                    'f_custom_num' => 'custom_number' ,
                    'f_address' => 'address'
                ));
                $where = new \Zend\Db\Sql\Where();
                $where->equalTo('rf.room_focus_id' , $room_id);
                $select->where($where);
                return $select->execute();
            }
        }

        /**
         * 添加分散式房间
         * 修改时间2015年4月28日 10:46:49
         * 
         * @author yzx
         * @param array $data
         * @param int $houseId
         * @return boolean
         */
        public function addRoom($data , $house_id)
        {
            $roomModel = new \Common\Model\Erp\Room();
            $houseModel = new \Common\Model\Erp\House();
            $attachmentModle = new \Common\Model\Erp\Attachments();
            $house_data = $houseModel->getOne(array("house_id" => $house_id));
            //检查数据是否为空和房间是否合法
            if (is_array($data) && empty($data) && empty($house_data))
            {
                return false;
            }
            $room_custom_numbers = array();
            foreach ($data as $tkey => $tval)
            {
                $tval = json_decode($tval , true);
                $room_custom_numbers[] = $tval['custom_number'];
            }
            $room_custom_numbers = array_unique($room_custom_numbers);
            $roomModel->Transaction();
            foreach ($data as $key => $val)
            {
                $val = json_decode($val , true);
                if ($val['room_config'] != '')
                {
                    $room_config = explode("," , $val['room_config']);
                    $room_config = implode("-" , $room_config);
                }
                else
                {
                    $room_config = '';
                }
                $val['house_id'] = $house_id;
                $val['create_time'] = time();
                $val['room_config'] = $room_config;
                $val['status'] = \Common\Model\Erp\Room::STATUS_NOT_RENTAL;
                $val['occupancy_number'] = intval($val['occupancy_number']) > 0 ? $val['occupancy_number'] : 0;
                $val['area'] = intval($val['area']) > 0 ? $val['area'] : 0;
                $val['money'] = is_numeric(($val['money'])) ? $val['money'] : 0;
                $room_data = $roomModel->getDataByCustomNumber($val['custom_number'] , $house_id);
                if (!empty($room_data))
                {
                    //如果已经添加就报错
                    return array("status" => false , "msg" => "房间编号重复");
                    exit();
                }
                else
                {
                    //添加房间
                    if (empty($room_data))
                    {
                        $new_room_id = $roomModel->insert($val);
                    }
                }
                if (!$new_room_id)
                {
                    $roomModel->rollback();
                    return array("status" => false , "msg" => '添加房间错误');
                    exit();
                }
                //添加图片
                if (strlen($val['image']) > 0)
                {
                    $image = array();
                    $image = explode("," , $val['image']);
                    foreach ($image as $ikey => $ival)
                    {
                        $img_data['key'] = $ival;
                        $img_data['module'] = "room";
                        $img_data['entity_id'] = $new_room_id;
                        $attachmentModle->insertData($img_data);
                    }
                }
            }
            $roomModel->commit();
            return array("status" => true , "msg" => "添加成功");
        }

        /**
         * 修改分散式房间
         * 修改时间2015年4月28日 10:46:49
         * 
         * @author yzx
         * @param array $data
         * @param int $roomId
         * @return boolean
         */
        public function editRoom($data , $houseId = 0)
        {
            $roomModel = new \Common\Model\Erp\Room();
            $houseModel = new \Common\Model\Erp\House();
            $attachmentModle = new \Common\Model\Erp\Attachments();
            $house_data = $houseModel->getOne(array("house"));
            if (!is_array($data) && empty($data))
            {
                return false;
            }
            foreach ($data as $key => $val)
            {
                $val = json_decode($val , true);
                if ($val['room_config'] != '')
                {
                    $room_config = explode("," , $val['room_config']);
                    $room_config = implode("-" , $room_config);
                }
                $room_data = $roomModel->getOne(array("room_id" => intval($val['room_id'])));
                if (!empty($room_data))
                {
                    //添加快照
                    \Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_ROOM_EDIT , $val['room_id'] , $room_data , "house" , $room_data['house_id']);

                    //修改房间
                    if (!empty($room_config))
                    {
                        $val['room_config'] = $room_config;
                    }
                    $this->house_id = $room_data['house_id'];
                    $room_id = $val['room_id'];
                    unset($val['room_id']);
                    $edit_res = $roomModel->edit(array("room_id" => $room_id) , $val);
                }
                else
                {
                    //添加房间
                    $val['create_time'] = time();
                    if (!empty($room_config))
                    {
                        $val['room_config'] = $room_config;
                    }
                    $val['status'] = \Common\Model\Erp\Room::STATUS_NOT_RENTAL;
                    $val['house_id'] = $this->house_id ? $this->house_id : $houseId;
                    unset($val['room_id']);
                    $room_id = $roomModel->insert($val);
                }

                //添加图片
                if (strlen($val['image']) > 0)
                {
                    $image = array();
                    $image = explode("," , $val['image']);
                    $attachmentModle->delete(array("module" => "room" , "entity_id" => $room_id));
                    foreach ($image as $ikey => $ival)
                    {
                        $img_data['key'] = $ival;
                        $img_data['module'] = "room";
                        $img_data['entity_id'] = $room_id;
                        $img_data['bucket'] = $attachmentModle->getDefaultBucket();
                        $insert_imag_data[] = $img_data;
                    }
                    $attachmentModle->insert($insert_imag_data);
                    $insert_imag_data = array();
                }
            }
            return true;
        }

        /**
         * 获取分散式合租房间
         * 修改时间2015年4月28日 11:19:45
         * 
         * @author yzx
         * @param int $houseId
         * @return Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >
         */
        public function getRoomByHouse($houseId)
        {
            $roomModel = new \Common\Model\Erp\Room();
            $sysConfig = new \Common\Model\Erp\SystemConfig();
            $houseModel = new \Common\Model\Erp\House();
            $attachmentModel = new \Common\Model\Erp\Attachments();
            $house_data = $houseModel->getOne(array("house_id" => $houseId));
            $public_config = $sysConfig->getFind("House" , "public_facilities");
            $sql = $roomModel->getSqlObject();
            $select = $sql->select($roomModel->getTableName());
            $select->where(array("house_id" => $houseId , "house_id" => $houseId , "is_delete" => 0));
            $result = $select->execute();
            $room_num = array();
            $data = array();
            $config_data = array();
            if (!empty($result))
            {
                for ($i = 1; $i <= $house_data['count']; $i++)
                {
                    $room_num[] = $i;
                }
                foreach ($result as $key => $val)
                {
                    $keys = $key + 1;
                    $data[$keys] = $val;
                    $data[$keys]['imag'] = $attachmentModel->getImagList("room" , $val['room_id']);
                }
                if (count($room_num) > count($data))
                {
                    $data_all_key = array_keys($data);
                    $data_key = end($data_all_key);
                    for ($di = 1; $di <= ($house_data['count'] - count($data)); $di++)
                    {
                        $sum_key = $data_key + $di;
                        $data[$sum_key] = array("room_config" => '');
                    }
                }
            }
            else
            {
                for ($i = 1; $i <= $house_data['count']; $i++)
                {
                    $room_num[] = $i;
                }
                $data_all_key = array_keys($data);
                $data_key = end($data_all_key);
                for ($di = 1; $di <= ($house_data['count']); $di++)
                {
                    $sum_key = $data_key + $di;
                    $data[$sum_key] = array("room_config" => '');
                }
            }
            //添加已经勾选公共设施标识
            foreach ($data as $dkey => $dval)
            {
                $room_config = explode("-" , $dval['room_config']);
                foreach ($public_config as $fkey => $fval)
                {
                    $config_data[$fkey]['name'] = $fval;
                    $config_data[$fkey]['value'] = $fkey;
                    if (in_array($fkey , $room_config))
                    {
                        $config_data[$fkey]['is_read'] = 1;
                    }
                    else
                    {
                        $config_data[$fkey]['is_read'] = 0;
                    }
                }
                $data[$dkey]['room_config'] = $config_data;
            }
            //print_r($data);die();
            return $data;
        }

        /**
         * 创建房间标签
         * 修改时间2015年5月5日 09:45:24
         * 
         * @author yzx
         * @param int $number
         * @return multitype:number
         */
        public function creatRoomNumber($number)
        {
            if ($number > 0)
            {
                $data = array();
                for ($i = 1; $i <= $number; $i++)
                {
                    $data[$i] = $i;
                }
            }
            return $data;
        }

        /**
         * 修改单个房间
         * 修改时间2015年5月13日 14:05:30
         * 
         * @author yzx
         * @param array $data
         * @param int $roomId
         * @return Ambigous <number, boolean>
         */
        public function editOneRoom($data , $roomId)
        {
            $roomModel = new \Common\Model\Erp\Room();
            $attachmentModel = new \Common\Model\Erp\Attachments();
            $room_config = explode("," , $data['room_config']);
            $data['room_config'] = implode("-" , $room_config);
            $attachmentModel->delete(array("module" => "room" , "entity_id" => $roomId));
            if ($data['image'] != '')
            {
                $imags = explode("," , $data['image']);
                foreach ($imags as $key)
                {
                    $imag_data['key'] = $key;
                    $imag_data['module'] = 'room';
                    $imag_data['entity_id'] = $roomId;
                    $attachmentModel->insertData($imag_data);
                }
            }
            return $roomModel->edit(array("room_id" => $roomId) , $data);
        }

        /**
         * 获取房间对应的房源数据
         * 修改时间2015年5月25日 18:09:09
         * 
         * @author yzx
         * @param int $roomId
         * @return Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >
         */
        public function getRoomAndHouse($roomId)
        {
            $roomModel = new \Common\Model\Erp\Room();
            $sql = $roomModel->getSqlObject();
            $select = $sql->select(array("r" => $roomModel->getTableName()));
            $select->leftjoin(array("h" => "house") , "r.house_id=h.house_id" , array("house_name"));
            $select->where(array("r.room_id" => $roomId));
            $result = $select->execute();
            return $result ? $result[0] : array();
        }

        /**
         * 构建搜索条件
         * 修改时间2015年3月28日 10:27:12
         *
         * @author yzx
         * @param array $serche
         * @return \Zend\Db\Sql\Where|boolean
         */
        private function likSql($serche)
        {
            if (!empty($serche))
            {
                if ($serche['community_name'] != null)
                {
                    $community_where = new Where();
                    $community_where->like("c.community_name" , $serche['community_name']);
                }
                if ($serche['room_number'] != null)
                {
                    $room_where = new Where();
                    $room_where->like("r.custom_number" , $serche['room_number']);
                }
                if (isset($community_where) && isset($room_where))
                {
                    $where = $room_where->addPredicate($community_where , Where::OP_OR);
                }
                if (isset($room_where) && !isset($community_where))
                {
                    $where = $room_where;
                }
                if (isset($community_where) && !isset($room_where))
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
         * 
         * @param type $user
         * @param type $house_id 房源id
         * @param type $pattern 房源模式 2.集中 1分散
         * @param type $room_id 房间ID 有值为获取单个房间信息
         * @param type $rental_way 在为分散式时必传  2整租、1合租
         *    @param  return array();
         *   LMS  2015年5月18日 09:50:58
         */
        public function getRoomInfo($user , $house_id , $pattern , $room_id = '' , $rental_way = '')
        {
            $Fee = H('Fee');
            $FeeModel = M('Fee');
            $company_id = $user['company_id'];
            //分散式 整租
            if ($pattern == '1' && $rental_way == '2')
            {

                $new_this = new \App\Api\Lib\Controller();
                if (!$new_this->verifyDataLinePermissions(\Common\Helper\Permissions::DELETE_AUTH_ACTION , $house_id , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE))
                {
                    return array();
                }

                $House = M('House');
                $sql_obj = $House->getSqlObject();
                $S = $sql_obj->select();
                $where = array('he.house_id' => $house_id , 'h.company_id' => $user['company_id']);
                if (is_numeric($room_id) && $room_id != 0)
                {
                    $where['he.house_entirel_id'] = $room_id;
                }
                $S->from(array('he' => 'house_entirel'))->columns(array('house_entirel_id' , 'occupancy_number' , 'money' , 'status' , 'is_yytz' , 'is_yd' , 'detain' , 'pay'))->join(array('h' => 'house') , 'h.house_id = he.house_id' , array('house_id' , 'rental_way' , 'area' , 'count' , 'hall' , 'toilet' , 'public_facilities' , 'community_id' , 'custom_number') , $S::JOIN_LEFT)->where($where)->limit(1);
                $result = $S->execute();

                if (emptys($result))
                    return array();
                $result = $result[0];
                $community_id = $result['community_id'];
                $result['room_configure'] = emptys($result['public_facilities']) ? array() : explode('-' , $result['public_facilities']);
                $result['room_id'] = $result['house_entirel_id'];
                unset($result['public_facilities'] , $result['community_id']);

                $img_list = @M('Attachments')->getImagList('house' , $house_id);
                $result['img_list'] = getArrayValue($img_list , 'key');
                $fee_list = $Fee->getRoomFeeInfo($house_id , '' , $FeeModel::SOURCE_DISPERSE);

                foreach ($fee_list as &$fee_info)
                {
                    unset($fee_info['source_id']);
                }
                $result['fee_list'] = $fee_list;

                return array('count' => $result['count'] , 'community_id' => $community_id , 'custom_number' => $result['custom_number'] , 'hall' => $result['hall'] , 'toilet' => $result['toilet'] , 'house_id' => $result['house_id'] , 'rental_way' => $result['rental_way'] , 'room_list' => array($result));
            }

            //分散式合租
            if ($pattern == '1' && $rental_way == '1')
            {

                $new_this = new \App\Api\Lib\Controller();
                if (!$new_this->verifyDataLinePermissions(\Common\Helper\Permissions::DELETE_AUTH_ACTION , $house_id , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE))
                {
                    return array();
                }
                $House = M('House');
                $sql_obj = $House->getSqlObject();
                $S = $sql_obj->select();

                $where = array('h.company_id' => $user['company_id']);
                if (is_numeric($room_id) && $room_id != 0)
                {
                    $where['r.room_id'] = $room_id;
                }

                if (is_numeric($house_id) && $house_id != 0)
                {
                    $where['r.house_id'] = $house_id;
                }


                $S->from(array('r' => 'room'))->columns(array('room_id' , 'occupancy_number' , 'room_number' => 'custom_number' , 'room_config' , 'money' , 'status' , 'is_yytz' , 'is_yd' , 'detain' , 'area' , 'pay' , 'room_type'))->join(array('h' => 'house') , 'h.house_id = r.house_id' , array('house_area' => 'area' , 'community_id' , 'house_id' , 'house_name' , 'count' , 'hall' , 'toilet' , 'custom_number' , 'rental_way' , 'public_facilities') , $S::JOIN_LEFT)->where($where)->order('r.room_id asc');
                $result = $S->execute();
                $result[0]['room_config'] = trim($result[0]['room_config'], "-");

                $house_img = @M('Attachments')->getImagList('house' , $house_id);

                if (emptys($result))
                    return array();
                $data = array();

                foreach ($result as $key => $info)
                {

                    if ($key == 0)
                    {
                        $info_one = $info;

                        $fee_list = $FeeModel->getData(array('source_id' => $house_id , 'source' => 'SOURCE_DISPERSE' , 'is_delete' => '0') , array('fee_type_id' , 'fee_id' , 'payment_mode' , 'money'));

                        $data = array(
                            'area' => $info_one['house_area'] ,
                            'house_id' => $info_one['house_id'] ,
                            'house_name' => $info_one['house_name'] ,
                            'count' => $info_one['count'] ,
                            'hall' => $info_one['hall'] ,
                            'toilet' => $info_one['toilet'] ,
                            'house_area' => $info_one['house_area'] ,
                            'community_id' => $info_one['community_id'] ,
                            'custom_number' => $info_one['custom_number'] ,
                            'house_configure' => emptys($info_one['public_facilities']) ? array() : explode('-' , $info_one['public_facilities']) ,
                            'house_fee_list' => $fee_list ,
                            'img_list' => getArrayValue($house_img , 'key') ,
                        );
                    }
                    unset($info['house_area'] , $info['house_id'] , $info['house_name'] , $info['count'] , $info['hall'] , $info['toilet'] , $info['custom_number'] , $info['public_facilities']);
                    $info['room_configure'] = emptys($info['room_config']) ? array() : explode('-' , $info['room_config']);

                    unset($info['room_config']);
                    $img_list = @M('Attachments')->getImagList('room' , $info['room_id']);
                    $info['img_list'] = getArrayValue($img_list , 'key');
                    $fee_list = $FeeModel->getData(array('source_id' => $info['room_id'] , 'source' => $FeeModel::SOURCE_DISPERSE_ROOM , 'is_delete' => '0') , array('fee_type_id' , 'fee_id' , 'payment_mode' , 'money'));

                    foreach ($fee_list as &$fee_info)
                    {
                        unset($fee_info['source_id']);
                    }
                    $info['fee_list'] = $fee_list;
                    $data['room_list'][] = $info;
                }
                return $data;
            }


            if ($pattern == '2')
            {
                $RF = M('RoomFocus');
                $where = array('room_focus_id' => $room_id , 'company_id' => $user['company_id']);

                $new_this = new \App\Api\Lib\Controller();
                if (!$new_this->verifyDataLinePermissions(\Common\Helper\Permissions::SELECT_AUTH_ACTION , $room_id , \Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED))
                {
                    return array();
                }

                if (is_numeric($house_id) && $house_id != 0)
                {
                    $where['flat_id'] = $house_id;
                }
                $field = array('room_focus_id' , 'flat_id' , 'custom_number' , 'area' , 'room_config' , 'money' , 'status' , 'is_yytz' , 'is_yd' , 'detain' , 'pay' , 'room_type');
                $result = $RF->getData($where , $field);
                $result[0]['room_config'] = trim($result[0]['room_config'], "-");
                
                if (emptys($result))
                    return array();
                $data = array();
                $flat_fee_list = $Fee->getRoomFeeInfo($result[0]['flat_id'] , '' , $FeeModel::SOURCE_FLAT);

                foreach ($result as $key => $info)
                {
                    $info['facilities'] = emptys($info['room_config']) ? array() : explode('-' , $info['room_config']);
                    unset($info['room_config']);
                    $img_list = @M('Attachments')->getImagList('room_focus' , $info['room_focus_id']);

                    $result[$key]['focus_id'] = $info['room_focus_id'];
                    unset($result[$key]['room_focus_id']);
                    $info['img_list'] = getArrayValue($img_list , 'key');
                    $fee_list = $Fee->getRoomFeeInfo($info['room_focus_id'] , '' , $FeeModel::SOURCE_FOCUS);
                    if (emptys($fee_list))
                        $fee_list = $flat_fee_list;

                    foreach ($fee_list as &$fee_info)
                    {
                        unset($fee_info['source_id']);
                    }
                    $info['fee_list'] = $fee_list;
                    $data[] = $info;
                }
                if (is_numeric($room_id))
                {
                    $data = $data[0];
                }
                return $data;
            }
            return array();
        }

        /**
         * 设置房间（整租合租）组织状态
         * @param type $user 用户信息 公司ID，和用户ID
         * @param type $status 房间状态 array(
         *  'status'=>房源状态设置 1,未租,2已租,3停用   
         *  'is_yytz'=>是否预约退租，0否,1是
         *  'is_yd'=>是否预定0否1是
         *  'delete'=>1 删除
         * );
         * @param type $room_id 房间ID
         * @param type $pattern 房源模式 2.集中 1分散
         * @param type $rental_way 在为分散式时必传 2整租、1合租
         * LMS 2015年5月14日 09:44:35
         */
        public function setRoomStatus($user , $status , $room_id , $pattern , $rental_way = '')
        {
            $result = false;

            //清理空字段
            foreach ($status as $key => $val)
            {
                if (emptys($val))
                    unset($status[$key]);
            }

            //没有状态需要处理
            if (emptys($status))
                return $result;


            //分散整租
            if ($pattern == '1' && $rental_way == 2)
            {
                $result = $this->setHouseEntireStatus($status , $user , $room_id);
            }

            //分散合租
            if ($pattern == '1' && $rental_way == 1)
            {

                $result = $this->setHouseRoomStatus($status , $user , $room_id);
            }

            //集中
            if ($pattern == '2')
            {
                $result = $this->setHouseRoomFocusStatus($status , $user , $room_id);
            }

            return $result;
        }

        private function setHouseEntireStatus($status , $user , $house_id)
        {

            //权限验证  
            $new_this = new \App\Api\Lib\Controller();
            if (!$new_this->verifyDataLinePermissions(\Common\Helper\Permissions::DELETE_AUTH_ACTION , $house_id , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE))
            {
                return false;
            }

            $HouseEntire = M('HouseEntirel');
            $save = array();
            $sql_obj = $HouseEntire->getSqlObject();
            $room_info = M('HouseEntirel')->getOne(array('house_id' => $house_id));
            if (!is_array($room_info))
                return false;
            $entire_id = $room_info['house_entirel_id'];
            $S = $sql_obj->select();
            $S->from(array('he' => 'house_entirel'))->columns(array())->join(array('h' => 'house') , 'he.house_id =h.house_id' , array('company_id' , 'house_id') , $S::JOIN_LEFT)->where(array('he.house_entirel_id' => $entire_id))->limit(1);
            $result = $S->execute();

            //无权限修改
            if (!is_array($result) || $result[0]['company_id'] != $user['company_id'])
                return false;
            $house_id = $result[0]['house_id'];
            $state_type = array(1 , 2 , 3);
            if (!emptys($status['status']) && array_search($status['status'] , $state_type) !== false)
            {
                $save['status'] = $status['status'];
            }

            if (!emptys($status['is_yytz']))
            {
                $save['is_yytz'] = $status['is_yytz'] == 0 ? 0 : 1;
            }

            if (!emptys($status['is_yd']))
            {
                $save['is_yytz'] = $status['is_yytz'] == 1 ? 1 : 0;
            }
            if (!emptys($status['delete']))
            {
                $save['is_delete'] = $status['delete'] == 1 ? 1 : 0;
            }


            //没有要修改的内容
            if (emptys($save))
                return false;
            $result = $HouseEntire->edit(array('house_entirel_id' => $entire_id) , $save);
            if ($result && isset($save['is_delete']))
            {
                //删除房源
                $result = M('House')->edit(array('house_id' => $house_id) , array('is_delete' => $save['is_delete']));
            }

            //停用恢复未租删除待办
            if ($result && $save['status'] == 1)
            {
                $this->delTodo(1 , $house_id);
            }



            return $result;
        }

        private function setHouseRoomStatus($status , $user , $room_id)
        {

            $Room = M('Room');
            $save = array();
            $sql_obj = $Room->getSqlObject();
            $S = $sql_obj->select();
            $S->from(array('r' => 'room'))->columns(array())->join(array('h' => 'house') , 'r.house_id =h.house_id' , array('company_id' , 'house_id') , $S::JOIN_LEFT)->where(array('r.room_id' => $room_id))->limit(1);
            $result = $S->execute();

            //权限验证  
            $new_this = new \App\Api\Lib\Controller();
            if (!$new_this->verifyDataLinePermissions(\Common\Helper\Permissions::DELETE_AUTH_ACTION , $result[0]['house_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE))
            {
                return false;
            }


            //无权限修改
            if (!is_array($result) || $result[0]['company_id'] != $user['company_id'])
                return false;




            $state_type = array(1 , 2 , 3);
            if (!emptys($status['status']) && array_search($status['status'] , $state_type) !== false)
            {
                $save['status'] = $status['status'];
            }

            if (!emptys($status['is_yytz']))
            {
                $save['is_yytz'] = $status['is_yytz'] == 0 ? 0 : 1;
            }

            if (!emptys($status['is_yd']))
            {
                $save['is_yytz'] = $status['is_yytz'] == 1 ? 1 : 0;
            }
            if (!emptys($status['delete']))
            {
                $save['is_delete'] = $status['delete'] == 1 ? 1 : 0;
            }

            //没有要修改的内容
            if (emptys($save))
                return false;
            $result = $Room->edit(array('room_id' => $room_id) , $save);

            //停用恢复未租删除待办
            if ($result && $save['status'] == 1)
            {
                $this->delTodo(2 , $room_id);
            }




            return $result;
        }

        private function setHouseRoomFocusStatus($status , $user , $focus_id)
        {


            //权限验证
            $new_this = new \App\Api\Lib\Controller();
            if (!$new_this->verifyDataLinePermissions(\Common\Helper\Permissions::SELECT_AUTH_ACTION , $focus_id , \Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED))
            {
                return false;
            }


            $Room = M('RoomFocus');
            $save = array();
            $state_type = array(1 , 2 , 3);
            if (!emptys($status['status']) && array_search($status['status'] , $state_type) !== false)
            {
                $save['status'] = $status['status'];
            }

            if (!emptys($status['is_yytz']))
            {
                $save['is_yytz'] = $status['is_yytz'] == 0 ? 0 : 1;
            }

            if (!emptys($status['is_yd']))
            {
                $save['is_yd'] = $status['is_yd'] == 1 ? 1 : 0;
            }
            if (!emptys($status['delete']))
            {
                $save['is_delete'] = $status['delete'] == 1 ? 1 : 0;
            }

            //没有要修改的内容
            if (emptys($save))
                return false;
            $result = $Room->edit(array('room_focus_id' => $focus_id , 'company_id' => $user['company_id']) , $save);
            //停用恢复未租删除待办
            if ($result && $save['status'] == 1)
            {
                $this->delTodo(4 , $focus_id);
            }
            return $result;
        }

        /**
         * 获取用户是否可以操作该房间房源
         * @param type $user 用户信息合辑
         * @param type $type 查询类型  1.分散整租  2.分散合租 3.分散式房源 4.集中式房间  5.公寓
         * @param type $id    根据type传递不同类型的主键id
         * @return type array | false
         *   LMS 2015年5月25日 14:47:48
         */
        function getUserRoomInfo($user , $type , $id)
        {

            $company_id = $user['company_id'];

            switch ($type)
            {
                //分散式整租
                case '1':
                    $H = M('House');
                    $select = $H->getSqlObject()->select();
                    $result = $select->from(array('h' => 'house'))->join(array('he' => 'house_entirel') , 'h.house_id=he.house_id')->where(array(
                                'he.house_entirel_id' => $id ,
                                'h.company_id' => $company_id ,
                            ))->limit(1)->execute();

                    break;
                //分散式合租
                case '2':
                    $H = M('House');
                    $select = $H->getSqlObject()->select();
                    $result = $select->from(array('h' => 'house'))->join(array('r' => 'room') , 'h.house_id=r.house_id')->where(array(
                                'r.room_id' => $id ,
                                'h.company_id' => $company_id ,
                            ))->limit(1)->execute();
                    break;
                //分散式房源
                case '3':
                    $H = M('House');
                    $result = $H->getData(array(
                        'house_id' => $id ,
                        'company_id' => $company_id ,
                    ));
                    break;
                //集中式房源
                case '4':
                    $R = M('RoomFocus');
                    $result = $R->getData(array(
                        'room_focus_id' => $id ,
                        'company_id' => $company_id ,
                    ));
                    break;
                //公寓
                case '5':
                    $F = M('Flat');
                    $result = $F->getData(array(
                        'flat_id' => $id ,
                        'company_id' => $company_id ,
                    ));
                    break;

                default :
                    $result = false;
                    break;
            }
            $result = is_array($result) && count($result) >= 1 ? $result[0] : false;

            if (isset($result['house_id']))
            {

                //权限验证  
                $new_this = new \App\Api\Lib\Controller();
                if (!$new_this->verifyDataLinePermissions(\Common\Helper\Permissions::DELETE_AUTH_ACTION , $result['house_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE))
                {
                    return false;
                }
            }
            elseif (isset($result['room_focus_id']))
            {
                //权限验证
                $new_this = new \App\Api\Lib\Controller();
                if (!$new_this->verifyDataLinePermissions(\Common\Helper\Permissions::SELECT_AUTH_ACTION , $result['room_focus_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED))
                {
                    return false;
                }
            }



            return $result;
        }

        private function delTodo($type , $id)
        {

            switch ($type)
            {
                case '1':
                    $module = 'HOUSE_STOP';
                    break;
                case '2':
                    $module = 'ROOM_STOP';
                    break;
                case '4':
                    $module = 'ROOM_FOUCS_STOP';
                    break;
                default :
                    return;
            }

            M('Todo')->delete(array('module' => $module , 'entity_id' => $id));
        }

    }
    