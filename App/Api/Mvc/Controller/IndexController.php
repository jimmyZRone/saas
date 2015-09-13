<?php

    namespace App\Api\Mvc\Controller;

    class IndexController extends \App\Api\Lib\Controller
    {

        /**
         * 获取首页信息数据
         * @author yusj | 最后修改时间 2015年5月8日下午3:49:59
         */
        public function indexAction()
        {
            $indexModel = new \App\Api\Helper\Index();
            $user =$this->getUserInfo();
            $data = $indexModel->index($user);
            
            return_success($data);
        }

    }
    