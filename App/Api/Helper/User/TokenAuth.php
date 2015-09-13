<?php

    namespace App\Api\Helper\User;

    class TokenAuth extends \Common\Helper\Erp\User
    {

        //排除权限认证接口列表
        private static $apiAuthExclude = array(
            'user' => array('register' , 'login' , 'save' , 'forgetpwd' , 'checkcode') ,
            'verification' => array('sendcodetophone') ,
            'log' => array('index' , 'ajax' , 'getmesg') ,
            'miyou' => array('data')
        );

        public static function getToken($guid , $user_id , $company_id , $is_manager , $auth_md5)
        {
            return encode("$guid,$user_id,$company_id,$is_manager,$auth_md5");
        }

        /**
         *  根据token获取 里面的信息
         * @param type $token
         * @return array   ('guid'=>'','user_id'=>'') | false
         * @author Lms 2015年4月28日 15:08:42
         */
        public static function getTokenInfo($token)
        {
            $string = decode($token);
            if (!is_string($string))
                return false;
            $tokenArr = explode(',' , $string);
            if (count($tokenArr) != 5)
                return false;

            return array_combine(array('guid' , 'user_id' , 'company_id' , 'is_manager' , 'auth_md5') , explode(',' , $string));
        }

        public static function tokenAuth($Controller , $Action)
        {
            $Action = strtolower($Action);
            $Controller = strtolower($Controller);
            $AuthArr = self::$apiAuthExclude;
            if (isset($AuthArr[$Controller]) && array_search($Action , $AuthArr[$Controller]) !== false)
                return true;
            $token = I('token' , '' , 'trim');
            //未传递Token
            if (empty($token))
            {
                return_error('501');
            }
            $tokenInfo = self::getTokenInfo($token);

            //token格式不正确
            if (!$tokenInfo)
            {
                return_error('502');
            }
            $guid = $tokenInfo['guid'];
            $userId = $tokenInfo['user_id'];
            $model = new \Common\Model\Erp\ErpinterfaceSession();
            $data = $model->getData(array('user_id' => $userId , 'app_type' => array('app_ios' , 'app_android')) , array('*'));

            //未找到信息
            if (!$data)
                return_error('503');
            $state = false;
            $time = time();

            //验证用户是否在其他地方已经登录
            foreach ($data as $val)
            {
                if ($val['session_id'] != $guid)
                    continue;
                //验证是否登录超时
                if ($val['deadline'] < $time)
                    return_error('503');
                $state = true;
                break;
            }
            //已经在其他地方登录
            if (!$state)
                return_error('500');

            $auth_md5 = '';
            if (!$tokenInfo['is_manager'])
            {
                $auth_arr = \App\Api\Helper\User\UserController::getUserAuth($tokenInfo['user_id'] , $tokenInfo['company_id']);
                $auth_md5 = substr(md5(serialize($auth_arr)) , -5);
                if ($auth_md5 != $tokenInfo['auth_md5'])
                    return_error('505');
            }
            //记录API请求信息
            self::apilog();

            return $tokenInfo;
        }

        static private function apilog()
        {
            if (count($_POST) > 0)
            {
                $url = $_POST;
            }
            else
            {
                $url = $_GET;
            }
            unset($url['api_version'] , $url['c'] , $url['a']);
            $txt = '?';
            foreach ($url as $key => $val)
            {
                $txt .= "$key=$val&";
            }
            $token_info = self::getTokenInfo($_REQUEST['token']);
            $user_info = '';
            foreach ($token_info as $key => $val)
            {
                $user_info.="$key:[$val] ";
            }
            logInfo("调用了API接口:$user_info\r\nhttp://" . $_SERVER['HTTP_HOST'] . explode('?' , $_SERVER['REQUEST_URI'])[0] . $txt);
            unset($url);
        }

    }
    