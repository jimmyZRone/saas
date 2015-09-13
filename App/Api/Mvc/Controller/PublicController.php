<?php

    namespace App\Api\Mvc\Controller;

    class PublicController extends \App\Api\Lib\Controller
    {

        /**
         * 获取城市列表
         * 
         * @author yusj | 最后修改时间 2015年4月27日下午7:20:27
         */
        public function getCityListAction()
        {

            $cityModel = new \Common\Helper\Erp\City ();
            $city_list = $cityModel->getCityList();
            return_success($city_list);
        }

        public function getAreaListAction()
        {
            PV(array('city_id'));
            $city_id = I('city_id');
            $Area = new \Common\Helper\Erp\Area ();
            $area_list = $Area->getAreaList($city_id);
            $area_list = is_array($area_list) ? $area_list : array();
            return_success($area_list);
        }

        public function getBusinessListAction()
        {

            PV(array('city_id' , 'area_id'));
            $city_id = I('city_id');
            $area_id = I('area_id');
            $Business = M('Business');
            $list = $Business->getDataByArea($area_id , $city_id);
            foreach ($list as $key => $info)
            {
                unset($list[$key]['city_id'] , $list[$key]['area_id'] , $list[$key]['url']);
            }

            return_success($list);
        }

        public function saveImageAttachmentsAction()
        {

            PV('type');
            $type = I('type/d');
            $images_list = I('images' , '' , 'trim');
            $images_list = explode(',' , trim($images_list , ','));
            $CompanyId = $this->getCompanyId();
            $count=0;
            switch ($type)
            {
                //用户头像
                case '1':
                    $module = 'user_avatar';
                    $id = $this->getUserId();
                    $count = 1;
                    break;
                //分散式房源
                case '2':
                    $module = 'house';
                    PV('house_id');
                    $id = I('house_id');
                    $count = M('House')->getCount(array(
                        'company_id' => $CompanyId ,
                        'house_id' => $id ,
                    ));

                    break;
                //分散式合租
                case '3':
                    $module = 'room';
                    PV('room_id');
                    $id = I('room_id');
                    $select = M('House')->getSqlObject()->select();
                    $count = $select->from(array('h' => 'house'))->join(array('r' => 'room') , 'h.house_id=r.house_id')->where(array(
                                'r.room_id' => $id ,
                                'h.company_id' => $CompanyId ,
                            ))->columns(array('count' => new \Zend\Db\Sql\Expression('count(*)')))->execute();
     
                    $count = $count[0]['count'];
                    break;
                //集中式
                case '4':
                    $module = 'room_focus';
                    PV('focus_id');
                    $id = I('house_id');
                    $count = M('RoomFocus')->getCount(array(
                        'company_id' => $CompanyId ,
                        'house_id' => $id
                    ));
                    break;
            }


            if ($count <= 0)
                return_error(127);
            
            $A = M('Attachments');
            $bucket = $A->getDefaultBucket();
            $A->Transaction();

            //先删除后添加
            $delete = $A->delete(array(
                'bucket' => $bucket ,
                'module' => $module ,
                'entity_id' => $id ,
            ));

            if (!$delete)
                return_error(127);

            $images_list = array_unique($images_list);

            //添加
            foreach ($images_list as $key)
            {
                if (emptys($key))
                    continue;
                $add = $A->insert(array(
                    'bucket' => $bucket ,
                    'module' => $module ,
                    'entity_id' => $id ,
                    'key' => $key ,
                ));
                if (!$add)
                {
                    $A->rollback();
                    return_error(127);
                }
            }
            $A->commit();
            return_success();
        }

    }
    