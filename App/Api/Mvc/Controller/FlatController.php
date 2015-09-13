<?php

    namespace App\Api\Mvc\Controller;

    class FlatController extends \App\Api\Lib\Controller
    {

        /**
         * 获取公寓列表
         * @author yusj | 最后修改时间 2015年5月8日下午3:39:34
         */
        public function getFlatListAction()
        {
            $flatModel = new \Common\Helper\Erp\Flat();
            $company_id = $this->getCompanyId();

            $search_key = I('search_key' , '' , 'trim');
            $flat_id = (int) I('flat_id');
            $search = array();
            if (!emptys($search_key))
            {
                $search['custom_number'] = $search_key;
            }
            if ($flat_id > 0)
            {
                $search['flat_id'] = $flat_id;
            }
            $data = $flatModel->getFlatList($company_id , $search , $this->getUserInfo());
            return_success($data);
        }

    }
    