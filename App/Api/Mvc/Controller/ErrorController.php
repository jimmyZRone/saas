<?php

    namespace App\Api\Mvc\Controller;

    class ErrorController extends \Core\Mvc\Controller
    {

        public static $error_message = array(
            '100' => array(
                'code' => '100' ,
                'info' => '参数验证错误 , 以下参数为必传:'
            ) ,
            '101' => array(
                'code' => '101' ,
                'info' => '请先同意协议内容'
            ) ,
            '102' => array(
                'code' => '102' ,
                'info' => '请填写完整信息'
            ) ,
            '103' => array(
                'code' => '103' ,
                'info' => '手机验证码错误'
            ) ,
            '104' => array(
                'code' => '104' ,
                'info' => '手机号码格式错误'
            ) ,
            '105' => array(
                'code' => '105' ,
                'info' => '两次密码不一致'
            ) ,
            '106' => array(
                'code' => '106' ,
                'info' => '密码格式错误'
            ) ,
            '107' => array(
                'code' => '107' ,
                'info' => '注册失败'
            ) ,
            '108' => array(
                'code' => '108' ,
                'info' => '登录类型不能为空'
            ) ,
            '109' => array(
                'code' => '109' ,
                'info' => '登录类型错误'
            ) ,
            '110' => array(
                'code' => '110' ,
                'info' => '用户名密码不能为空'
            ) ,
            '111' => array(
                'code' => '111' ,
                'info' => '用户名或密码错误'
            ) ,
            '112' => array(
                'code' => '112' ,
                'info' => '手机或验证码不能为空'
            ) ,
            '113' => array(
                'code' => '113' ,
                'info' => '验证码错误'
            ) ,
            '114' => array(
                'code' => '114' ,
                'info' => '用户不存在'
            ) ,
            '115' => array(
                'code' => '115' ,
                'info' => '修改密码失败'
            ) ,
            '116' => array(
                'code' => '116' ,
                'info' => '旧密码错误'
            ) ,
            '117' => array(
                'code' => '117' ,
                'info' => '新密码不能为空'
            ) ,
            '118' => array(
                'code' => '118' ,
                'info' => '新密码格式错误'
            ) ,
            '119' => array(
                'code' => '119' ,
                'info' => '没有此操作权限'
            ) ,
            '120' => array(
                'code' => '120' ,
                'info' => '用户类型错误'
            ) ,
            '121' => array(
                'code' => '121' ,
                'info' => '用户已经存在'
            ) ,
            '122' => array(
                'code' => '122' ,
                'info' => '验证码发送过于频繁'
            ) ,
            '123' => array(
                'code' => '123' ,
                'info' => '租赁模式类型错误'
            ) ,
            '124' => array(
                'code' => '124' ,
                'info' => '新密码和旧密码不能一样'
            ) ,
            '125' => array(
                'code' => '125' ,
                'info' => '登录密码不能和冲账密码一样'
            ) ,
            '126' => array(
                'code' => '126' ,
                'info' => '出租方式类型错误'
            ) ,
            '127' => array(
                'code' => '127' ,
                'info' => '添加失败'
            ) ,
            '128' => array(
                'code' => '128' ,
                'info' => '验证码发送过于频繁'
            ) ,
            '129' => array(
                'code' => '129' ,
                'info' => '保存失败'
            ) ,
            '130' => array(
                'code' => '130' ,
                'info' => '退订失败'
            ) ,
            '131' => array(
                'code' => '131' ,
                'info' => '数据格式错误,'
            ) ,
            '132' => array(
                'code' => '132' ,
                'info' => '获取小区信息失败'
            ) ,
            '133' => array(
                'code' => '133' ,
                'info' => '获取小区信息失败'
            ) ,
            '134' => array(
                'code' => '134' ,
                'info' => '获取用户信息失败'
            ) ,
            '135' => array(
                'code' => '135' ,
                'info' => '房源状态操作失败'
            ) ,
            '136' => array(
                'code' => '136' ,
                'info' => '获取小区信息失败'
            ) ,
            '137' => array(
                'code' => '137' ,
                'info' => '地区信息或商圈信息错误' ,
            ) ,
            '138' => array(
                'code' => '138' ,
                'info' => '该合同已经评价过或无权限评价' ,
            ) ,
            '139' => array(
                'code' => '139' ,
                'info' => '请填写备注信息' ,
            ) ,
            '140' => array(
                'code' => '140' ,
                'info' => '评价失败' ,
            ) ,
            '141' => array(
                'code' => '141' ,
                'info' => '储存失败' ,
            ) ,
            '142' => array(
                'code' => '142' ,
                'info' => '操作失败' ,
            ) ,
            '143' => array(
                'code' => '143' ,
                'info' => '冲账密码错误' ,
            ) ,
            '144' => array(
                'code' => '144' ,
                'info' => '小区已经存在' ,
            ) ,
            '145' => array(
                'code' => '145' ,
                'info' => '小区还在审核状态' ,
            ) ,
            '146' => array(
                'code' => '146' ,
                'info' => '获取信息失败' ,
            ) ,
            '147' => array(
                'code' => '147' ,
                'info' => '应用不需要升级' ,
            ) ,
            '148' => array(
                'code' => '148' ,
                'info' => '该房间不为出租状态' ,
            ) ,
            '201' => array(
                'code' => '201' ,
                'info' => '城市ID参数错误'
            ) ,
            '203' => array(
                'code' => '203' ,
                'info' => '获取数据失败,请重试'
            ) ,
            '204' => array(
                'code' => '204' ,
                'info' => '用户身份审核中...'
            ) ,
            '500' => array(
                'code' => '500' ,
                'info' => '该账户已经在其他设备登录'
            ) ,
            '501' => array(
                'code' => '501' ,
                'info' => 'token为必要参数'
            ) ,
            '502' => array(
                'code' => '502' ,
                'info' => 'token为必要参数'
            ) ,
            '503' => array(
                'code' => '503' ,
                'info' => '登录超时'
            ) ,
            '504' => array(
                'code' => '504' ,
                'info' => '无权限操作'
            ) ,
            '505' => array(
                'code' => '505' ,
                'info' => '用户权限已变更，请重新登录'
            ) ,
        	'506' => array(
        		'code' => '506' ,
        		'info' => '当前账号已经在ERP注册，不能注册SAAS'
        	)
        );

        /**
         * 根据编号获取错误信息
         * @author yusj | 最后修改时间 2015年5月6日下午2:21:23
         */
        public static function getErrorMsg($code)
        {
            if (array_key_exists($code , self::$error_message))
            {
                return self::$error_message [$code];
            }
            else
            {
                logDebug('错误代码' . $code . "不存在");
                return array(
                    'code' => '-1' ,
                    'info' => '未知错误信息'
                );
            }
        }

        /**
         * 错误的继承
         *
         * @author lishengyou
         *         最后修改时间 2015年2月10日 上午11:34:04
         *        
         */
        protected function nosubclassAction()
        {
            echo __FUNCTION__;
        }

        /**
         * 错误的动作
         *
         * @author lishengyou
         *         最后修改时间 2015年2月10日 上午11:33:58
         *        
         */
        protected function controllerAction()
        {
            // echo 'Controller Is Not Found';
            header("HTTP/1.0 404 Not Found");
            exit("404");
        }

        /**
         * 错误的动作
         *
         * @author lishengyou
         *         最后修改时间 2015年2月10日 上午11:33:58
         *        
         */
        protected function actionAction()
        {
            // echo 'Action Is Not Found';
            header("HTTP/1.0 404 Not Found");
            exit("404");
        }

    }
    