<?php

    namespace App\Api\Mvc\Controller;

    class FeetypeController extends \App\Api\Lib\Controller
    {

        public function indexAction()
        {
            echo 'asdfasdf';
        }

        /**
         * 获取费用配置信息
         * @author yusj | 最后修改时间 2015年5月4日下午4:21:35
         */
        public function getFeeTypeListAction()
        {
            $feeTypeModel = new \App\Api\Helper\FeeType();
            $user = $this->getUserInfo();
            $data = $feeTypeModel->getFeeTypeListByCompanyID($user['company_id']);
            return_success($data);
        }

        public function addFeeAction()
        {
            PV(array('fee_list'));
            $fee_list = I('fee_list');
            $feeTypeModel = new \App\Api\Helper\FeeType();
            $user = $this->getUserInfo();
            $fee_type = $feeTypeModel->getFeeTypeListByCompanyID($user['company_id']);
            $fee_type = getArrayKeyClassification($fee_type , 'fee_type_id' , 'type_name');
            foreach ($fee_list as $info)
            {
                $id = $info['fee_type_id'];
                if (!isset($fee_type[$id]))
                    return_error(131 , '费用类型不正确');
                $name = I('name');
                $company_id = $this->getCompanyId();
                $F = H('FeeType');
                $add = $F->add(array(
                    'company_id' => $company_id ,
                    'type_name' => $name ,
                ));
                
                if (!$add)
                    return_error(127 , ',' . $F->getLastError());
            }
        }

        public function addAction()
        {
            PV(array('name'));
            $name = I('name');
            $company_id = $this->getCompanyId();
            $F = H('FeeType');
            $add = $F->add(array(
                'company_id' => $company_id ,
                'type_name' => $name ,
            ));

            if ($add)
                return_success();
            else
                return_error(127 , ',' . $F->getLastError());
        }

    }
    