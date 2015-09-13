<?php

    namespace App\Web\Mvc\Controller\Plugins;

    /**
     * 用户登录
     * @author lishengyou
     * 最后修改时间 2015年4月7日 上午10:07:36
     *
     */
    class SmsController extends \App\Web\Lib\Controller
    {

        function indexAction()
        {
            $html = $this->fetch('index');
            $this->returnAjax(array('status' => 1 , 'data' => $html , 'tag_name' => '智能短信' , 'model_js' => 'apply_sms' , 'model_name' => 'apply_sms'));
        }

        function openAction()
        {
            $html = $this->fetch('open');
            $this->returnAjax(array('status' => 1 , 'data' => $html , 'tag_name' => '智能短信' , 'model_js' => 'apply_sms_set' , 'model_name' => 'apply_sms_set'));
        }

    }
    