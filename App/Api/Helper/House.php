<?php

    namespace App\Api\Helper;

    class House extends \Common\Helper\Erp\House
    {

        /**
         * 增加分散式合租房源
         * 
         * @author yusj | 最后修改时间 2015年5月5日下午4:02:51
         */
        public function addHouseInfo($data , $user)
        {

            $houseModel = new \Common\Model\Erp\House();

            $data['create_time'] = time();
            $data['owner_id'] = $user['user_id'];
            $data ['public_facilities'] = implode('-' , $data['public_facilities']);
            $new_house_id = $houseModel->insert($data);
            return $new_house_id;
        }

    }
    