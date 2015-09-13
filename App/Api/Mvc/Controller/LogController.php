<?php

    namespace App\Api\Mvc\Controller;

    class LogController extends \App\Api\Lib\Controller
    {

        public function indexAction()
        {
            $LogDb = new \Common\Model\Erp\Log ();
            $where = new \Zend\Db\Sql\Where ();
            $where = array();
            $list = $LogDb->getData($where , array(
                'unique_id'
                    ) , 999999 , 0 , '' , false , 'unique_id');
            $list = is_array($list) ? $list : array();
            $url = \App\Api\Lib\Url::parse('Log/ajax');
            $this->assign('post_url' , $url);
            $this->assign('ip_list' , $list);
            $this->display();
        }

        public function ajaxAction()
        {
            $id = I('id');
            $ip = I('ip');
            $list = \Core\Log::getLog($id , $ip);
            $list['list']= array_values($list['list']);
            
            $this->returnAjax($list);
        }

        public function getMesgAction()
        {
            $mesg = \App\Api\Mvc\Controller\ErrorController::$error_message;
            foreach ($mesg as $k => $v)
            {
                echo $v['code'] . ":" . $v['info'] . "<br/>";
            }
        }

    }
    