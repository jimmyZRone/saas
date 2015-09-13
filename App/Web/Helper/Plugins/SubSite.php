<?php

    namespace App\Web\Helper\Plugins;

    /**
     * 小站相关工具类
     * @author too|编写注释时间 2015年6月15日 下午5:20:50
     */
    class SubSite
    {

        /**
         * 添加小站数据 【设置基本信息flat表】
         * @author too|编写注释时间 2015年6月15日 下午5:21:27
         */
        public function addsubSite($FlatData)
        {
            if (!empty($FlatData))
            {
                $flatModel = new \Common\Model\Plugins\WxxzFlat();
                $flatModel->delete(array('is_delete' => 1 , 'domain_name' => $FlatData['domain_name']) , true); // 删除未开通 符合域名

                $where = new \Zend\Db\Sql\Where();

                $where->equalTo('is_delete' , 0);
                $where->equalTo('company_id' , $FlatData['company_id']);

                $sql = "(city_id='{$FlatData['city_id']}' AND area_id='{$FlatData['area_id']}')";
//                if ($FlatData['area_id'] > 0)
//                {   
//                    $sql = "(city_id='{$FlatData['city_id']}' AND area_id='0') OR (city_id='{$FlatData['city_id']}' AND area_id='{$FlatData['area_id']}')";
//                }
//                else
//                {
//                    $sql = "city_id='{$FlatData['city_id']}'";
//                }

                $where->expression("($sql)" , array());
                $flat_count = $flatModel->getCount($where); // 看同一个城市或者区域里是否已经有小站
                //P($tempdata);
                if ($flat_count > 0)
                {
                    return -1;
                }
                $tempdomain = $flatModel->getOne(array('domain_name' => $FlatData['domain_name'])); // print_r($tempdata);
                if (!empty($tempdomain))
                {
                    return -2;
                }
                //$FlatData['domain_name'] = '1';
                //unset($FlatData['domain_name']);
                if (!$flat_id = $flatModel->insert($FlatData))
                {
                    return false;
                }
                return $flat_id;
            }
            else
            {
                return fales;
            }
        }

        /**
         * 修改基本信息
         * @param unknown $where
         * @param unknown $data
         * @return boolean
         */
        public function editsubsite($where , $data)
        {
            $flatModel = new \Common\Model\Plugins\WxxzFlat();
            if (!$flatModel->edit($where , $data))
            {
                return false;
            }
            else
            {
                return true;
            }
        }

        /**
         * 添加图片
         * @author too|编写注释时间 2015年6月16日 上午9:46:58
         */
        public function addPicture($paramArr)
        {
            if (!empty($paramArr))
            {
                $flatImagesModel = new \Common\Model\Plugins\WxxzFlatImages();
                if (!$flatImagesModel->insert($paramArr))
                {
                    return false;
                }
                return true;
            }
            else
            {
                return fales;
            }
        }

        /**
         * 点击详情拿数据
         * @author too|编写注释时间 2015年6月18日 下午3:06:44
         */
        public function getdetails($house_type = 1 , $rental_way = 1 , $house_id = 0 , $room_id = 50)
        {
            if ($house_type == 1) // 分散式
            {
                if ($rental_way == 1) // 合租 (还没)拿室友
                {
//                 $model = new \Common\Model\Erp\House();
//                 $sql = $model->getSqlObject();
//                 $select = $sql->select(array('h'=>$model->getTableName('house')));
//                 $select->columns(array('floor'));
//                 $select->join(array('r'=>'room'), 'r.house_id = h.house_id',array('money','area','room_type'),'left');
//                 //$select->join(array('r'=>'rental'),new \Zend\Db\Sql\Predicate\Expression('') ,array(),'left');
//                 $select->limit(1);
//                 $data = $select->execute();print_r(str_replace('"', '', $select->getSqlString()));
                    $model = new \Common\Model\Erp\Rental();
                    $sql = $model->getSqlObject();
                    $select = $sql->select(array('r' => $model->getTableName('rental')));
                    $select->columns(array('contract_id' , 'house_id' , 'room_id' , 'house_type'));
                    $select->join(array('cr' => 'contract_rental') , 'cr.contract_id = r.contract_id' , array('tenant_id') , 'left');
                    $select->join(array('t' => 'tenant') , 'cr.tenant_id = t.tenant_id' , array('gender' , 'name' , 'profession') , 'left');
                    $select->join(array('ro' => 'room') , 'r.room_id = ro.room_id' , array('money' , 'area') , 'left');
                    $where = new \Zend\Db\Sql\Where();
                    $where->equalTo('r.house_id' , $house_id);
                    $where->and;
                    $where->equalTo('r.house_type' , 1);
                    $where->and;
                    $where->equalTo('ro.status' , 2);
                    $select->where($where);
                    $data = $select->execute();

                    // 取图片
                    $model = new \Common\Model\Erp\Attachments();
                    $sql = $model->getSqlObject();
                    $select = $sql->select(array('a' => $model->getTableName('attachments')));
                    $select->columns(array('key'));
                    $where = new \Zend\Db\Sql\Where();
                    $where->equalTo('entity_id' , $room_id);
                    $where->and;
                    $where->equalTo('module' , 'room');
                    $select->where($where);
                    $dataPicture = $select->execute();//print_r(str_replace('"', '', $select->getSqlString()));
                    $dataResult = array(
                        'picture' => $dataPicture ,
                        'shiyou' => $data
                    );
                    return $dataResult;
                }
                else // 整租
                {
//                 $model = new \Common\Model\Erp\House();
//                 $sql = $model->getSqlObject();
//                 $select = $sql->select(array('h'=>$model->getTableName('house')));
//                 $select->columns(array('floor','count','hall','toilet','area'));
//                 $select->join(array('he'=>'house_entirel'), 'h.house_id = he.house_id',array('money'),'left');
//                 $data = $select->execute();//print_r(str_replace('"', '', $select->getSqlString()));;
                    // 取图片
                    $model = new \Common\Model\Erp\Attachments();
                    $sql = $model->getSqlObject();
                    $select = $sql->select(array('a' => $model->getTableName('attachments')));
                    $select->columns(array('key'));
                    $where = new \Zend\Db\Sql\Where();
                    $where->equalTo('entity_id' , $house_id);
                    $where->and;
                    $where->equalTo('module' , 'house');
                    $select->where($where);
                    $dataPicture = $select->execute();//print_r(str_replace('"', '', $select->getSqlString()));
                    //$data[0]['picture'] = $dataPicture;
                    $dataResult = array(
                        'picture' => $dataPicture
                    );
                    return $dataResult;
                }
            }
            else //集中式
            {
                if ($rental_way == 1) // 合租 拿室友
                {
                    $roomfocusModel = new \Common\Model\Erp\RoomFocus();
                    $numArr = $roomfocusModel->getOne(array('room_focus_id' => $room_id) , array('custom_number' , 'flat_id')); // print_r($numArr);
                    $like = substr($numArr['custom_number'] , 0 , strlen($numArr['custom_number']) - 2); // 这个号码相同的就是在同一套内, 就需要取室友
                    $where = new \Zend\Db\Sql\Where();
                    $where->equalTo('flat_id' , $numArr['flat_id']);
                    $where->like('custom_number' , $like . '%');
                    $where->notEqualTo('room_focus_id' , $room_id);
                    $room_idArr = $roomfocusModel->getData($where , array('room_focus_id'));
                    foreach ($room_idArr as $rv)
                    {
                        $rid[] = $rv['room_focus_id'];
                    }
                    $model = new \Common\Model\Erp\Rental();
                    $sql = $model->getSqlObject();
                    $select = $sql->select(array('r' => $model->getTableName('rental')));
                    $select->columns(array('contract_id' , 'house_id' , 'room_id' , 'house_type'));
                    $select->join(array('cr' => 'contract_rental') , 'cr.contract_id = r.contract_id' , array('tenant_id') , 'left');
                    $select->join(array('t' => 'tenant') , 'cr.tenant_id = t.tenant_id' , array('gender' , 'name' , 'profession') , 'left');
                    $select->join(array('ro' => 'room_focus') , 'r.room_id = ro.room_focus_id' , array('money' , 'area') , 'left');
                    $where = new \Zend\Db\Sql\Where();
                    $where->in('ro.room_focus_id' , $rid);
                    $where->and;
                    $where->equalTo('r.house_type' , 2);
                    $where->and;
                    $where->equalTo('ro.status' , 2);
                    $select->where($where);
                    $data = $select->execute();//print_r(str_replace('"', '', $select->getSqlString()));die;
                    //P($data);die;
                    // 取图片
                    $model = new \Common\Model\Erp\Attachments();
                    $sql = $model->getSqlObject();
                    $select = $sql->select(array('a' => $model->getTableName('attachments')));
                    $select->columns(array('key'));
                    $where = new \Zend\Db\Sql\Where();
                    $where->equalTo('entity_id' , $room_id);
                    $where->and;
                    $where->equalTo('module' , 'room_focus');
                    $select->where($where);
                    $dataPicture = $select->execute();//print_r(str_replace('"', '', $select->getSqlString()));
                    //$data[0]['picture'] = $dataPicture;
                    $dataResult = array(
                        'picture' => $dataPicture ,
                        'shiyou' => $data
                    );
                    return $dataResult;
                }
                else // 整租
                {
//                 $model = new \Common\Model\Erp\RoomFocus();
//                 $sql = $model->getSqlObject();
//                 $select = $sql->select(array('r'=>$model->getTableName('room_focus')));
//                 $select->columns(array('floor','room_type','money','area'));
//                 $data = $select->execute();//print_r(str_replace('"', '', $select->getSqlString()));
                    // 取图片
                    $model = new \Common\Model\Erp\Attachments();
                    $sql = $model->getSqlObject();
                    $select = $sql->select(array('a' => $model->getTableName('attachments')));
                    $select->columns(array('key'));
                    $where = new \Zend\Db\Sql\Where();
                    $where->equalTo('entity_id' , $room_id);
                    $where->and;
                    $where->equalTo('module' , 'room_focus');
                    $select->where($where);
                    $dataPicture = $select->execute();//print_r(str_replace('"', '', $select->getSqlString()));
                    //$data[0]['picture'] = $dataPicture;
                    $dataResult = array(
                        'picture' => $dataPicture
                    );
                    return $dataResult;
                }
            }
        }

        /**
         * 完善详情写入方法
         * @param array
         * @author too|编写注释时间 2015年6月23日 下午2:13:40
         */
        public function completeInfo($param)
        {
            if ($param['house_type'] == 1) // 分散
            {
                if ($param['rental_way'] == 1) // 合租
                {
                    $houseModel = new \Common\Model\Erp\House(); //写楼层
                    $houseModel->Transaction();
                    if (!empty($param['floor']))
                    {
                        if (!$houseModel->edit(array('house_id' => $param['house_id']) , array('floor' => $param['floor'])))
                        {
                            $houseModel->rollback();
                            return false;
                        }
                    }
                    if (!empty($param['room_type']))
                    {
                        $roomModel = new \Common\Model\Erp\Room(); //写楼层
                        if (!$roomModel->edit(array('room_id' => $param['room_id']) , array('room_type' => $param['floor'])))
                        {
                            $houseModel->rollback();
                            return false;
                        }
                        unset($roomModel);
                    }
                    if (!empty($param['area']))
                    {
                        $roomModel = new \Common\Model\Erp\Room(); //写楼层
                        if (!$roomModel->edit(array('room_id' => $param['room_id']) , array('area' => $param['area'])))
                        {
                            $houseModel->rollback();
                            return false;
                        }
                        unset($roomModel);
                    }
                    if (!empty($param['money']))
                    {
                        $roomModel = new \Common\Model\Erp\Room(); //写楼层
                        if (!$roomModel->edit(array('room_id' => $param['room_id']) , array('money' => $param['money'])))
                        {
                            $houseModel->rollback();
                            return false;
                        }
                        unset($roomModel);
                    }
                    $houseModel->commit();
                }
                else // 整租
                {
                    $houseModel = new \Common\Model\Erp\House(); //写楼层
                    $houseModel->Transaction();
                    if (!empty($param['floor']))
                    {
                        if (!$houseModel->edit(array('house_id' => $param['house_id']) , array('floor' => $param['floor'])))
                        {
                            $houseModel->rollback();
                            return false;
                        }
                    }
                    if (!empty($param['room_type']))
                    {
                        if (!$houseModel->edit(array('house_id' => $param['house_id']) , array('room_type' => $param['room_type'])))
                        {
                            $houseModel->rollback();
                            return false;
                        }
                    }
                    if (!empty($param['area']))
                    {
                        if (!$houseModel->edit(array('house_id' => $param['house_id']) , array('area' => $param['area'])))
                        {
                            $houseModel->rollback();
                            return false;
                        }
                    }
                    if (!empty($param['money']))
                    {
                        $houseEntirelModel = new \Common\Model\Erp\HouseEntirel(); //写楼层
                        if (!$houseEntirelModel->edit(array('house_id' => $param['house_id']) , array('money' => $param['money'])))
                        {
                            $houseModel->rollback();
                            return false;
                        }
                    }
                    $houseModel->commit();
                }
            }
            else // 集中式整租合租都是一个表
            {
                $roomfocusModel = new \Common\Model\Erp\RoomFocus();
                $roomfocusModel->Transaction();
                if (!empty($param['floor']))
                {
                    if (!$roomfocusModel->edit(array('room_focus_id' => $param['room_id']) , array('floor' => $param['floor'])))
                    {
                        $roomfocusModel->rollback();
                        return false;
                    }
                }
                if (!empty($param['room_type']))
                {
                    if (!$roomfocusModel->edit(array('room_focus_id' => $param['room_id']) , array('room_type' => $param['room_type'])))
                    {
                        $roomfocusModel->rollback();
                        return false;
                    }
                }
                if (!empty($param['area']))
                {
                    if (!$roomfocusModel->edit(array('room_focus_id' => $param['room_id']) , array('area' => $param['area'])))
                    {
                        $roomfocusModel->rollback();
                        return false;
                    }
                }
                if (!empty($param['money']))
                {
                    if (!$roomfocusModel->edit(array('room_focus_id' => $param['room_id']) , array('money' => $param['money'])))
                    {
                        $roomfocusModel->rollback();
                        return false;
                    }
                }
                $roomfocusModel->commit();
            }
            // 室友相关
            if (!empty($param['szhiye'])) // 如果有职业提交
            {

                $sModel = new \Common\Model\Erp\Tenant();
                foreach ($param['sid'] as $key => $val)
                {
                    if (!$sModel->edit(array('tenant_id' => $val) , array('profession' => $param['szhiye'][$key])))
                    {
                        return false;
                    }
                }
            }
            if (!empty($param['sarea'])) // 如果有面积 三个表判断取其一
            {
                if ($param['shouse_type'] == 1) // 分散
                {
                    if ($param['srental_way'] == 1) // 合租
                    {
                        $roomModel = new \Common\Model\Erp\Room();
                        if (!$roomModel->edit(array('room_id' => $param['sroom_id']) , array('area' => $param['sarea'])))
                        {
                            return false;
                        }
                    }
                    else // 整租
                    {
                        $houseModel = new \Common\Model\Erp\House(); //面积
                        if (!$houseModel->edit(array('house_id' => $param['shouse_id']) , array('area' => $param['sarea'])))
                        {
                            return false;
                        }
                    }
                }
                else // 集中式
                {
                    $roomfocusModel = new \Common\Model\Erp\RoomFocus();
                    if (!$roomfocusModel->edit(array('room_focus_id' => $param['sroom_id']) , array('area' => $param['sarea'])))
                    {
                        return false;
                    }
                }
            }
            return true;
        }

        // 从saas拉符合条件的集中式的数据
        public function getinfo($param)
        {
            
        }

    }
    