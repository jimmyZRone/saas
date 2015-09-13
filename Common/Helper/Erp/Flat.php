<?php

    namespace Common\Helper\Erp;

    use Common\Model\Erp\RoomFocus;
    use Common\Model\Erp\RoomTemplateRelation;
    use Zend\Db\Sql\Expression;

    /**
     * 公寓
     * @author lishengyou
     * 最后修改时间 2015年4月2日 下午1:52:40
     *
     */
    class Flat
    {

        public $floor_session_id = "save_floor_number";
        public $house_number_sesssion_id = "save_house_number";
        public $room_number_session_id = "save_room_number";
        public $is_diy_house = "is_diy_house";
        public $is_diy_floor = "is_diy_floor";

        /**
         * 取得公寓所有房间量
         * @author lishengyou
         * 最后修改时间 2015年4月2日 下午1:53:01
         *
         * @param array $flatids
         */
        public function getCountRoom(array $flatids)
        {
            $model = new \Common\Model\Erp\RoomFocus();
            $sql = $model->getSqlObject();
            $select = $sql->select(array('room' => $model->getTableName()));
            $select->where(array("is_delete" => 0));
            if (count($flatids) == 1)
            {
                $select->where(array('flat_id' => end($flatids)));
                return $select->count();
            }
            else
            {//多数据
                $select->where(array('flat_id' => $flatids));
                $select->where(array("is_delete" => 0));
                $select->columns(array('flat_id' => 'flat_id' , 'count' => new \Zend\Db\Sql\Predicate\Expression('count(flat_id)')));
                $select->group('flat_id');
                $data = $select->execute();
                $_data = array();
                foreach ($data as $value)
                {
                    $_data[$value['flat_id']] = $value['count'];
                }
                $data = $_data;
                unset($_data);
                foreach ($flatids as $value)
                {
                    if (!isset($data[$value]))
                        $data[$value] = 0;
                }
                return $data;
            }
        }

        /**
         * 获取房间地址信息
         * 修改时间 2015年5月11日15:14:16
         * 
         * @author ft
         */
        public function getRoomAddressInfo($room_id)
        {
            $flat_model = new \Common\Model\Erp\Flat();
            $sql = $flat_model->getSqlObject();
            $select = $sql->select(array('f' => 'flat'));
            $select->columns(
                    array(
                        'flat_id' => 'flat_id' ,
                        'f_address' => 'address' ,
                        'flat_name' => 'flat_name' ,
                        'f_custom_num' => 'custom_number'
            ));
            $select->leftjoin(
                    array('rf' => 'room_focus') , 'rf.flat_id = f.flat_id' , array(
                'rf_id' => 'room_focus_id' ,
                'rf_custom_num' => 'custom_number' ,
                'rf_floor' => 'floor'
            ));
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('rf.room_focus_id' , $room_id);
            $where->equalTo('rf.is_delete' , 0);
            $where->equalTo('f.is_delete' , 0);
            $select->where($where);
            return $select->execute();
        }

        /**
         * 取得已经出租的房间数量
         * @author lishengyou
         * 最后修改时间 2015年4月2日 下午1:54:32
         *
         * @param array $flatids
         */
        public function getRentalCount(array $flatids)
        {
            $model = new \Common\Model\Erp\RoomFocus();
            $sql = $model->getSqlObject();
            $select = $sql->select(array('room' => $model->getTableName()));
            if (count($flatids) == 1)
            {
                $select->where(array('flat_id' => end($flatids) , 'status' => $model::STATUS_RENTAL));
                return $select->count();
            }
            else
            {//多数据
                $select->where(array('flat_id' => $flatids , 'status' => $model::STATUS_RENTAL));
                $select->columns(array('flat_id' => 'flat_id' , 'count' => new \Zend\Db\Sql\Predicate\Expression('count(flat_id)')));
                $select->group('flat_id');
                $data = $select->execute();
                $_data = array();
                foreach ($data as $value)
                {
                    $_data[$value['flat_id']] = $value['count'];
                }
                $data = $_data;
                unset($_data);
                foreach ($flatids as $value)
                {
                    if (!isset($data[$value]))
                        $data[$value] = 0;
                }
                return $data;
            }
        }

        /**
         * 取得未出租的房间数量
         * @author lishengyou
         * 最后修改时间 2015年4月2日 下午1:54:32
         *
         * @param array $flatids
         */
        public function getNotRentalCount(array $flatids)
        {
            $model = new \Common\Model\Erp\RoomFocus();
            $sql = $model->getSqlObject();
            $select = $sql->select(array('room' => $model->getTableName()));
            $where = new \Zend\Db\Sql\Where();
            $where->notEqualTo('status' , $model::STATUS_RENTAL);
            $where->equalTo("is_delete" , 0);
            if (count($flatids) == 1)
            {
                $where->equalTo('flat_id' , end($flatids));
                $select->where($where);
                return $select->count();
            }
            else
            {//多数据
                $where->in('flat_id' , $flatids);
                $select->where($where);
                $select->columns(array('flat_id' => 'flat_id' , 'count' => new \Zend\Db\Sql\Predicate\Expression('count(flat_id)')));
                $select->group('flat_id');
                $data = $select->execute();
                $_data = array();
                foreach ($data as $value)
                {
                    $_data[$value['flat_id']] = $value['count'];
                }
                $data = $_data;
                unset($_data);
                foreach ($flatids as $value)
                {
                    if (!isset($data[$value]))
                        $data[$value] = 0;
                }
                return $data;
            }
        }

        /**
         * 取得已经预订的房间数量
         * @author lishengyou
         * 最后修改时间 2015年4月2日 下午1:55:54
         *
         * @param array $flatids
         */
        public function getReserveCount(array $flatids)
        {
            $model = new \Common\Model\Erp\RoomFocus();
            $sql = $model->getSqlObject();
            $select = $sql->select(array('room' => $model->getTableName()));
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('is_yd' , 1);
            if (count($flatids) == 1)
            {
                $where->equalTo('flat_id' , end($flatids));
                $select->where($where);
                return $select->count();
            }
            else
            {//多数据
                $where->in('flat_id' , $flatids);
                $select->where($where);
                $select->columns(array('flat_id' => 'flat_id' , 'count' => new \Zend\Db\Sql\Predicate\Expression('count(flat_id)')));
                $select->group('flat_id');
                $data = $select->execute();
                $_data = array();
                foreach ($data as $value)
                {
                    $_data[$value['flat_id']] = $value['count'];
                }
                $data = $_data;
                unset($_data);
                foreach ($flatids as $value)
                {
                    if (!isset($data[$value]))
                        $data[$value] = 0;
                }
                return $data;
            }
        }

        /**
         * 统计预约退租
         * 修改时间2015年5月29日 16:55:40
         * 
         * @author yzx
         * @param unknown $flatid
         * @return Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >
         */
        public function getYytz($flatid)
        {
            $model = new \Common\Model\Erp\RoomFocus();
            $sql = $model->getSqlObject();
            $select = $sql->select($model->getTableName());
            $select->where(array("flat_id" => $flatid , "is_delete" => 0 , "is_yytz" => 0));
            $result = $select->execute();

            return count($result);
        }

        /**
         * 取得平均租金
         * @author lishengyou
         * 最后修改时间 2015年4月2日 下午2:31:26
         *
         * @param array $flatids
         */
        public function getCountAverageRent(array $flatids)
        {
            $model = new \Common\Model\Erp\RoomFocus();
            $sql = $model->getSqlObject();
            $select = $sql->select(array('room' => $model->getTableName()));
            $where = new \Zend\Db\Sql\Where();
            $where_delete = new \Zend\Db\Sql\Where();
            $where_delete->equalTo("is_delete" , 0);
            $where->in('flat_id' , $flatids);
            $where->addPredicate($where_delete);
            $select->where($where);
            $select->columns(array('flat_id' => 'flat_id' , 'money' => new \Zend\Db\Sql\Predicate\Expression('avg(money)')));
            $select->group('flat_id');
            $data = $select->execute();
            $_data = array();
            if (!empty($data))
            {
                foreach ($data as $value)
                {
                    $_data[$value['flat_id']] = isset($value['count']) ? $value['count'] : '';
                }
            }
            $data = $_data;
            unset($_data);
            foreach ($flatids as $value)
            {
                if (!isset($data[$value]))
                    $data[$value] = 0;
            }
            return count($flatids) == 1 ? end($data) : $data;
        }

        /**
         * 月/年空置率
         *  最后修改时间 2015-3-27
         * 	月30 年365
         *
         * @author dengshuang
         * @param unknown $user
         * @param unknown $days
         * @param unknown $flat_id
         * @return number
         */
        public function getEmptyPercent($days , $flat_id)
        {
            $model = new \Common\Model\Erp\RoomFocus();
            $sql = $model->getSqlObject();
            $select = $sql->select(array('ref' => $model->getTableName()));
            $select->columns(array('sum' => new \Zend\Db\Sql\Expression('sum(ceil((unix_timestamp(now())-rf.last_out_time)/(3600*24))))')));
            $select->where(array('rf.status' => 1));
            $select->where(array('rf.flat_id' => $flat_id));
            $select->where(array('is_delete' => 0));
            $res = $select->execute();
            if ($res)
            {
                $sum = isset($res[0]['sum']) ? $res[0]['sum'] : 0;
            }
            else
            {
                $sum = 0;
            }
            $number_toal = $this->getCountRoom(array($flat_id));
            if (empty($sum) || empty($number_toal))
            {
                return 0;
            }
            else
            {
                return intval($sum / ($number_toal * $days));
            }
        }

        /**
         * 取得公寓和扩展信息
         * @author lishengyou
         * 最后修改时间 2015年6月9日 下午7:03:25
         *
         * @param unknown $company_id
         */
        public function getFlatListAndExt($user)
        {
            $flatModel = new \Common\Model\Erp\Flat();
            $sql = $flatModel->getSqlObject();
            $select = $sql->select(array('f' => $flatModel->getTableName()));
            $select->leftjoin(array('rf' => 'room_focus') , 'rf.flat_id=f.flat_id' , array('floor' , 'status' , 'is_yytz' , 'is_yd'));
            $select->group(new \Zend\Db\Sql\Predicate\Expression('f.`flat_id`,rf.`floor`,rf.`status`,rf.`is_yytz`,rf.`is_yd`'));
            $select->where(array('f.company_id' => $user['company_id'] , 'f.is_delete' => 0 , 'rf.is_delete' => 0 , "f.city_id" => $user['city_id']));
            $select->columns(array('flat_name' , 'flat_id' , 'count' => new \Zend\Db\Sql\Predicate\Expression('count(f.`flat_id`)')));
            //权限
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisions->VerifyDataCollectionsPermissionsModel($select , 'f.flat_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
            }
            $data = $select->execute();
            if (!$data)
            {
                return array();
            }
            $select = $sql->select(array('f' => $flatModel->getTableName()));
            $select->where(array('f.company_id' => $user['company_id'] , 'f.is_delete' => 0 ,"f.city_id" => $user['city_id']));
            $select->columns(array('flat_name' , 'flat_id'));
            //权限
            if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
            {
            	$permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
            	$permisions->VerifyDataCollectionsPermissionsModel($select , 'f.flat_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
            }
            $flat = $select->execute();
            $flat = $flat ? $flat : array();
            $temp = array();
            foreach ($flat as $_flat){
            	$temp[$_flat['flat_id']] = array(
            		'flat_name' => $_flat['flat_name'] ,
            		'flat_id' => $_flat['flat_id'] ,
            		'total_floor' => array() ,
            		'CR' => 0 ,
            		'IR' => 0 ,
            		'NR' => 0 ,
            		'RC' => 0 ,
            		'TZ' => 0 ,
            		'SP' => 0
            	);
            }
            $flat = $temp;unset($temp);
            foreach ($data as $value)
            {
                if (!isset($flat[$value['flat_id']]))
                {
                    $flat[$value['flat_id']] = array(
                        'flat_name' => $value['flat_name'] ,
                        'flat_id' => $value['flat_id'] ,
                        'total_floor' => array() ,
                        'CR' => 0 ,
                        'IR' => 0 ,
                        'NR' => 0 ,
                        'RC' => 0 ,
                        'TZ' => 0 ,
                        'SP' => 0
                    );
                }
                if (!in_array($value['floor'] , $flat[$value['flat_id']]['total_floor']))
                {
                    $flat[$value['flat_id']]['total_floor'][] = $value['floor'];
                }
                switch (intval($value['status']))
                {
                    case 1:
                        $flat[$value['flat_id']]['NR'] += $value['count'];
                        break;
                    case 2:
                        $flat[$value['flat_id']]['IR'] += $value['count'];
                        break;
                    case 3:
                        $flat[$value['flat_id']]['SP'] += $value['count'];
                        break;
                }
                if ($value['is_yytz'])
                {
                    $flat[$value['flat_id']]['TZ'] += $value['count'];
                }
                if ($value['is_yd'])
                {
                    $flat[$value['flat_id']]['RC'] += $value['count'];
                }
                $flat[$value['flat_id']]['CR'] += $value['count'];
            }
            foreach ($flat as $key => $value)
            {
                $value['total_floor'] = count($value['total_floor']);
                $flat[$key] = $value;
            }
            return $flat;
        }

        /**
         * 获取每个公寓扩展信息
         * 修改时间2015年4月15日 10:35:15
         *
         * @author yzx
         * @param int $flatData
         * @return multitype:number Ambigous <number, \Core\Db\Sql\Ambigous> Ambigous <number, \Core\Db\Sql\Ambigous, unknown> Ambigous <mixed, number, multitype:Ambigous <NULL, Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown>> >
         */
        public function getListExt($flatData)
        {
            if (!empty($flatData))
            {
                $ext = array();
                $ext_data = array();
                $days = date("D" , time());
                foreach ($flatData as $key => $val)
                {
                    $ext["CR"] = $this->getCountRoom(array($val['flat_id']));
                    $ext["CAR"] = $this->getCountAverageRent(array($val['flat_id']));
                    $ext['EP'] = $this->getEmptyPercent($days , $val['flat_id']);
                    $ext['NR'] = $this->getNotRentalCount(array($val['flat_id']));
                    $ext['IR'] = $this->getRentalCount(array($val['flat_id']));
                    $ext['RC'] = $this->getReserveCount(array($val['flat_id']));
                    $ext['TZ'] = $this->getYytz($val['flat_id']);
                    $ext['SP'] = $this->getStop($val['flat_id']);
                    $ext_data[$key] = array_merge($flatData[$key] , $ext);
                    $flat_id[] = $val['flat_id'];
                }
                return $ext_data;
            }
        }

        /**
         * 构造房源编号
         * 修改时间2015年4月15日 20:05:05
         *
         * @author yzx
         * @param array $floorNumber
         * @param int $houseNumber
         * @return multitype:string |boolean
         */
        public function houseNumberFactory($houseNumber , $isDiy = false)
        {
            $floorNumber = \Core\Session::read($this->floor_session_id);
            if (is_array($floorNumber) && !empty($floorNumber))
            {
                $house_number_data = array();
                $house_number_data_f = array();
                if ($houseNumber > 0)
                {
                    foreach ($floorNumber as $fkey => $fval)
                    {
                        for ($i = 1; $i <= $houseNumber; $i++)
                        {
                            if (strlen($i) == 1)
                            {
                                $j = str_pad($i , 2 , 0 , STR_PAD_LEFT);
                            }
                            else
                            {
                                $j = $i;
                            }
                            $house_number_data[$fval][] = $fval . $j;
                            if ($isDiy)
                            {
                                $house_number_data_f[$fval . $j]["number"] = $j;
                            }
                            else
                            {
                                $house_number_data_f[$fval . $j]["number"] = $fval . $j;
                            }
                            $house_number_data_f[$fval . $j]["floor"] = $fval;
                            $house_number_data_f[$fval . $j]["house_number"] = $j;
                        }
                    }
                }
                return array("data" => $house_number_data , "data_f" => $house_number_data_f);
            }
            return false;
        }

        /**
         * 保存楼层编号
         * 修改时间2015年4月16日 10:06:31
         *
         * @author yzx
         * @param array $floorNumber
         */
        public function saveFloorNumber($floorNumber)
        {
            \Core\Session::delete($this->floor_session_id);
            \Core\Session::save($this->floor_session_id , $floorNumber);
        }

        /**
         * 保存房源编号
         * 修改时间2015年4月16日 10:09:54
         *
         * @author yzx
         * @param array $houseNumber
         */
        public function saveHouseNumber($houseNumber)
        {
            \Core\Session::delete($this->house_number_sesssion_id);
            \Core\Session::save($this->house_number_sesssion_id , $houseNumber);
        }

        /**
         * 获取楼层编号
         * 修改时间2015年4月16日 11:22:16
         *
         * @author yzx
         */
        public function getFloorNumber($number = null)
        {
            $result = \Core\Session::read($this->floor_session_id);
            $s_count = count($result);
            if (!empty($result) && $s_count == $number)
            {
                return $result;
            }
            if ($number != null)
            {
                \Core\Session::delete($this->floor_session_id);
                $result = $this->creatFloorNumber($number);
                \Core\Session::save($this->floor_session_id , $result);
                return $result;
            }
            return false;
        }

        /**
         * 获取自定义房源编号
         * 修改时间2015年4月16日 11:24:47
         *
         * @author yzx
         * @return Ambigous <NULL, mixed>|boolean
         */
        public function getHouseNumber($flat_id , $data = array())
        {
            $result = \Core\Session::read($this->house_number_sesssion_id);
            $is_diy_house = \Core\Session::read($this->is_diy_house);
            if (!empty($result))
            {
                $data = array();
                foreach ($result as $key => $val)
                {
                    $custom_number = array_values($val);
                    $house_number = array_keys($val);
                    $floor = array_keys($val);
                    if ($data['rental_way'] == \Common\Model\Erp\Flat::RENTAL_WAY_CLOSE || $is_diy_house)
                    {
                        $data[$key]['custom_number'] = $floor[0] . $custom_number[0];
                        $data[$key]['house_number'] = $house_number[0];
                    }
                    else
                    {
                        $data[$key]['custom_number'] = $custom_number[0];
                        $data[$key]['house_number'] = $house_number[0];
                    }
                    $data[$key]['floor'] = $floor[0];
                    $data[$key]['flat_id'] = $flat_id;
                }
                return $data;
            }
            return false;
        }

        /**
         * 没有传自定义楼层编号自己构造
         * 修改时间2015年4月16日 13:28:29
         *
         * @author yzx
         * @param int $number
         * @return multitype:number |boolean
         */
        public function creatFloorNumber($number)
        {
            if ($number > 0)
            {
                $data = array();
                for ($i = 1; $i <= $number; $i++)
                {
                    $data[] = $i;
                }
                return $data;
            }
            return false;
        }

        /**
         * 删除添加功能所以session
         * 修改时间2015年4月16日 13:33:20
         *
         * @author yzx
         */
        public function clearAllSession()
        {
            \Core\Session::delete($this->floor_session_id);
            \Core\Session::delete($this->house_number_sesssion_id);
            \Core\Session::delete($this->room_number_session_id);
            \Core\Session::delete($this->is_diy_house);
            \Core\Session::delete($this->is_diy_floor);
            \Core\Session::delete("flat_input_data");
        }

        /**
         * 没有填写自定义房源编号构造
         * 修改时间2015年4月16日 14:08:36
         *
         * @author yzx
         * @param array $factoryData
         * @return unknown|boolean
         */
        public function houseNumberCreat($factoryData)
        {
            if (is_array($factoryData) && !empty($factoryData))
            {
                $data = array();
                foreach ($factoryData['data_f'] as $key => $val)
                {
                    $data[][$val['floor']] = $val['number'];
                }
                \Core\Session::delete($this->house_number_sesssion_id);
                \Core\Session::save($this->house_number_sesssion_id , $data);
            }
            return false;
        }

        /**
         * 构造房间编号
         * 修改时间2015年4月16日 14:33:11
         *
         * @author yzx
         */
        public function CreatRoomCount($roomNumber , $flatId , $rental_way = 0)
        {
            $house_number = \Core\Session::read($this->house_number_sesssion_id);
            $is_diy_house = \Core\Session::read($this->is_diy_house);
            $is_diy_floor = \Core\Session::read($this->is_diy_floor);
            if (is_array($house_number) && !empty($house_number))
            {
                if (!is_array($roomNumber) && $roomNumber > 0)
                {
                    $room_number_data = array();
                    $room_number_data_f = array();
                    foreach ($house_number as $fkey => $fval)
                    {
                        $floor = array_keys($fval);
                        $floor = end($floor);
                        $val = end($fval);
						$house_num = array_values($fval);
						$house_num_str = substr($house_num[0], strlen($floor),strlen($house_num[0])-strlen($floor));
                        for ($i = 1; $i <= $roomNumber; $i++)
                        {
                            if (strlen($i) == 1)
                            {
                                $j = str_pad($i , 2 , 0 , STR_PAD_LEFT);
                            }
                            else
                            {
                                $j = $i;
                            }
                            $room_number_data[$val][] = $val . $j;
                            if (!$is_diy_house)
                            {
                                $room_number_data_f[$floor . $val . $j]["custom_number"] = $val . $j;
                            }
                            else
                            {
                                $room_number_data_f[$floor . $val . $j]["custom_number"] = $floor . $val . $j;
                            }
                            $room_number_data_f[$floor . $val . $j]["room_number"] = $roomNumber;
                            $room_number_data_f[$floor . $val . $j]["floor"] = $floor;
                            $room_number_data_f[$floor . $val . $j]["flat_id"] = $flatId;
                            $room_number_data_f[$floor . $val . $j]["house_number"] = $house_num_str;
                        }
                    }
                }
                return array("room_data" => $room_number_data , "room_floor" => $room_number_data_f);
            }
        }

        /**
         * 保存自定义房间套数
         * 修改时间2015年4月16日 15:16:35
         *
         * @author yzx
         * @param array $roomNUmber
         */
        public function saveRoomNumber($roomNUmber)
        {
            \Core\Session::delete($this->room_number_session_id);
            \Core\Session::save($this->room_number_session_id , $roomNUmber);
        }

        /**
         * 获取自定义房源套数
         * 修改时间2015年4月16日 17:50:50
         *
         * @author yzx
         * @return Ambigous <NULL, mixed>
         */
        public function getDiyRoomNumber($flatId)
        {
            $result = \Core\Session::read($this->room_number_session_id);
            if (is_array($result) && !empty($result))
            {
                $room_number_data = array();
                foreach ($result as $rkey => $rval)
                {
                    for ($l = 1; $l <= $rval['rooms_count']; $l++)
                    {
                        $k = null;
                        if (strlen($l) == 1)
                        {
                            $k = str_pad($l , 2 , 0 , STR_PAD_LEFT);
                        }
                        else
                        {
                            $k = $l;
                        }
                        $house_num = $rval['house_num'] . $k;
                        $room_number_data[$house_num]["custom_number"] = $house_num;
                        $room_number_data[$house_num]["room_number"] = $rval['rooms_count'];
                        $room_number_data[$house_num]["floor"] = $rval['floor_num'];
                        $room_number_data[$house_num]["flat_id"] = $flatId;
                    }
                }
                sort($room_number_data);
                return $room_number_data;
            }
            return array();
        }

        /**
         * 分割
         * @param unknown $flatId
         * @return multitype:multitype:number
         */
        public function getFlatFloor($flatId)
        {
            $roomFocusModel = new RoomFocus();
        }

        /**
         * 批量添加房间
         * 修改时间2015年4月23日 13:34:34
         * 
         * @author yzx
         * @param array $data
         * @param array $user
         * @return boolean
         */
        public function batchAdd($data , $user)
        {
            $roomTemplateRelateionModel = new RoomTemplateRelation();
            $roomFocusModel = new \Common\Model\Erp\RoomFocus();
            $house_number = array();
            if (is_array($data) && !empty($data))
            {
                if ($data['cute_type'] == 1)
                {
                    if (is_array($data['houses_number']) && !empty($data['houses_number']))
                    {
                        foreach ($data['houses_number'] as $key => $val)
                        {
                            if (strlen($val['house_num']) >= 2)
                            {
                                $house_number[$key]['custom_number'] = $data['floor_num'] . $val['house_num'];
                            }
                            else
                            {
                                $house_number[$key]['custom_number'] = $data['floor_num'] . '0' . $val['house_num'];
                            }
                            $house_number[$key]['flat_id'] = $data['flat_id'];
                            $house_number[$key]['floor'] = $data['floor_num'];
                            $house_number[$key]['house_number'] = $val['house_num'];
                        }
                    }
                }
                else if ($data['cute_type'] == 2)
                {
                    if (is_array($data['houses_number']) && !empty($data['houses_number']))
                    {
                        foreach ($data['houses_number'] as $key => $val)
                        {
                            for ($i = 1; $i <= $val['room_num']; $i++)
                            {
                                if (strlen($i) == 1)
                                {
                                    $j = str_pad($i , 2 , 0 , STR_PAD_LEFT);
                                }
                                else
                                {
                                    $j = $i;
                                }
                                if (strlen($val['house_num']) >= 2)
                                {
                                    $custom_num = $data['floor_num'] . $val['house_num'] . $j;
                                }
                                else
                                {
                                    $custom_num = $data['floor_num'] . '0' . $val['house_num'] . $j;
                                }
                                $house_number[$custom_num]['custom_number'] = $custom_num;
                                $house_number[$custom_num]['flat_id'] = $data['flat_id'];
                                $house_number[$custom_num]['floor'] = $data['floor_num'];
                                $house_number[$custom_num]['house_number'] = $val['house_num'];
                            }
                        }
                    }
                }
                sort($house_number);
                $result = $roomTemplateRelateionModel->houseFactory($house_number , $user);
                if ($result)
                {
                    $roomFocusModel->updateFlatFloor($data['flat_id'] , $user);
                    return true;
                }
                return false;
            }
        }

        /**
         * 获取公寓列表
         * @author yusj | 最后修改时间 2015年5月8日下午3:42:13
         */
        public function getFlatList($company_id , $search = array() , $user = array())
        {
            $model = new \Common\Model\Erp\Flat();
            $sql = $model->getSqlObject();
            $select = $sql->select(array('f' => 'flat'));
            $select->columns(array('flat_id' , 'flat_name'));
            $select->join(array('rf' => 'room_focus') , 'f.flat_id=rf.flat_id' , array('room_type' , 'room_config' , 'room_focus_id' , 'floor' , 'status' , 'is_yd' , 'is_yytz' , 'custom_number') , 'left');
            $where = new \Zend\Db\Sql\Where();//造where条件对象
            $where->equalTo('f.company_id' , $company_id);//是否删除
            $where->equalTo('rf.is_delete' , 0);//是否删除
            if (!emptys($search['custom_number']))
            {
                $where->like('rf.custom_number' , "%{$search['custom_number']}%");
            }

            if (!emptys($search['flat_id']))
            {
                $where->equalTo('f.flat_id' , $search['flat_id']);
            }
           
            $where->equalTo('f.city_id' , $user['city_id']);


            $select->where($where);
            $select->order(getExpSql("flat_id desc,-floor desc,custom_number asc"));

            if (!$user['is_manager'])
            {
                $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
                $permisions->VerifyDataCollectionsPermissionsModel($select , 'f.flat_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
            }
            $data = $select->execute();
            $data = is_array($data) ? $data : array();
            $result = array();
      
            foreach ($data as $info)
            {
                if (!isset($result[$info['flat_id']]))
                {
                    $result[$info['flat_id']] = array(
                        'flat_id' => $info['flat_id'] ,
                        'flat_name' => $info['flat_name'] ,
                    );
                }
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


            return array_values($result);
        }

        /**
         * 获取停用数据
         * 修改时间2015年5月29日 16:58:58
         * 
         * @author yzx
         * @param unknown $flatid
         * @return Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >
         */
        public function getStop($flatid)
        {
            $model = new \Common\Model\Erp\RoomFocus();
            $sql = $model->getSqlObject();
            $select = $sql->select($model->getTableName());
            $select->where(array("flat_id" => $flatid , "is_delete" => 0 , "status" => $model::STATUS_IS_STOP));
            $result = $select->execute();
            return count($result);
        }

    }
    