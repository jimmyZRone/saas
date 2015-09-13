<?php

    namespace App\Api\Mvc\Controller;

    class UserController extends \App\Api\Lib\Controller
    {

        /**
         * 用户注册
         * 
         * @author yusj | 最后修改时间 2015年4月27日下午7:20:27
         */
        public function registerAction()
        {
            $phone = I('phone');
            $phone_code = I('phone_code');
            $passwd = I('passwd');
            $cfm_passwd = I('cfm_passwd');
            $app_type_num = I('app_type' , ''); // app_ios app_android
            $app_type = "";
            switch ($app_type_num)
            {
                case 1 :
                    $app_type = 'app_android';
                    break;
                case 2 :
                    $app_type = 'app_ios';
                    break;
                default :
                    $app_type = 'unknown';
            }
            if (empty($app_type))
            {
                return_error(108);
            }
            if ($app_type != 'app_ios' && $app_type != 'app_android')
            {
                return_error(109);
            }
            if (!\Common\Helper\ValidityVerification::IsPhone($phone))
            {
                return_error(104);
            }
            // 密码相关
            if ($passwd != $cfm_passwd)
            {
                return_error(105);
            }
            $result = \Common\Helper\ValidityVerification::IsPasswd($passwd);
            if ($result ['status'] != 1)
            {
                return_error(106);
            }
            if (\Common\Helper\Erp\User::check($phone))
            {
                return_error(121);
            }
            if (\App\Web\Helper\Jooozo\User::IsExist($phone))
            {
                return_error(506);
            }
            //验证码默认通过
            $result = true;// \Common\Helper\Sms\TianyiCaptcha::check($phone , $phone_code , false);

            if (!$result)
            {
                return_error(103);
            }
            $user_id = \Common\Helper\Erp\User::addManager(array('username' => $phone , 'password' => $passwd));
            if ($user_id)
            { // 注册成功
                \Common\Helper\Erp\OperationLog::save($user_id , \Common\Helper\Erp\OperationLog::ACTION_USER_REG , $user_id , get_client_ip());
                return_success();                                                                                       // 删除验证码
            }


            return_error(107);
        }

        /**
         * 用户登录
         * 
         * @author yusj | 最后修改时间 2015年4月27日下午7:20:55
         */
        public function loginAction()
        {

         
//dump($_SERVER,$_GET,$_POST);
            $phone = I('user');
            $passwd = I('passwd');
            $app_type_num = I('app_type' , ''); // app_ios app_android
            $app_type = "";
            switch ($app_type_num)
            {
                case 1 :
                    $app_type = 'app_android';
                    break;
                case 2 :
                    $app_type = 'app_ios';
                    break;
                default :
                    $app_type = 'unknown';
            }
            if (empty($app_type))
            {
                return_error(108);
            }
            if ($app_type != 'app_ios' && $app_type != 'app_android')
            {

                return_error(109);
            }
            if (empty($phone) || empty($passwd))
            {
                return_error(101 , '用户名密码不能为空');
            }
            $helper = new \Common\Helper\Erp\User ();
            $user = \Common\Helper\Erp\User::check($phone , $passwd , true);

            if (!$user)
            {
                return_error(111);
            }
            $is_verify = M('Company')->getCount(array('company_id' => $user['company_id'] , 'is_verify' => 1));

            if ($is_verify != 1)
            {
                return_error(204);
            }

            // 取扩展信息
            $userExtendModel = new \Common\Model\Erp\UserExtend ();
            $userExtend = $userExtendModel->getOne(array(
                'user_id' => $user ['user_id']
            ));

            if (!$userExtend)
            {
                // 存入扩展信息
                $userExtend = array(
                    'user_id' => $user ['user_id'] ,
                    'name' => $user ['username'] ,
                    'contact' => $user ['username'] ,
                    'gender' => 1 ,
                    'birthday' => date('Y-m-d') ,
                    'city_id' => 0
                );
                $userExtendModel->insert($userExtend);
            }


            $guid = \App\Api\Helper\User\UserController::loginUser($user , $app_type);

            if ($guid)
            {
                // 修改最后登录时间
                $userModel = new \Common\Model\Erp\User();
                $userModel->edit(array(
                    'user_id' => $user ['user_id']
                        ) , array(
                    'last_longing_time' => time()
                ));

                $auth_md5 = '';
                if (!$user['is_manager'])
                {
                    $auth_arr = \App\Api\Helper\User\UserController::getUserAuth($user['user_id'] , $user['company_id']);
                    $auth_md5 = substr(md5(serialize($auth_arr)) , -5);
                }
                $token = \App\Api\Helper\User\TokenAuth::getToken($guid , $user ['user_id'] , $user['company_id'] , $user['is_manager'] , $auth_md5);
                $token_arr = array(
                    'token' => $token
                );

                \Common\Helper\Erp\OperationLog::save($user['user_id'] , \Common\Helper\Erp\OperationLog::ACTION_USER_LOGIN , $user['user_id'] , get_client_ip());
                logDebug("用户[{$phone}]已经登录,Token为[$token]");
                return_success($token_arr);
            }
            else
            {
                return_error(111);
            }
        }

        /**
         * 忘记密码中 先验证手机号码以及验证码
         * 
         * @author yusj | 最后修改时间 2015年4月30日上午10:21:11
         */
        public function checkCodeAction()
        {
            // 前一步的手机号和验证码重新进行验证码
            $phone = I('phone');
            $code = I('code');
            $type = I('type');//1 忘记密码 0 注册
            if (!$phone || !$code)
            {
                return_error(112); // 手机或验证码不能为空
            }
            $userModel = new \Common\Model\Erp\User ();
            $user = $userModel->getByPhone($phone);
            if ($type == 1)
            {
                if (!$user)
                {
                    return_error(114); // 用户不存在
                }
            }
            if ($type == 0)
            {
                if ($user)
                {
                    return_error(121);
                }
            }
            $result = \Common\Helper\Sms\TianyiCaptcha::check($phone , $code , false);
            if (!$result)
            {
                return_error(113); // 验证码错误
            }
            return_success();
        }

        /**
         * 忘记密码修改
         * 
         * @author yusj | 最后修改时间 2015年4月27日下午7:30:50
         */
        public function forgetPwdAction()
        {
            // 前一步的手机号和验证码重新进行验证码
            $phone = I('phone');
            $code = I('code');
            $passwd = I('passwd');
            $cfm_passwd = I('cfm_passwd');
            if (!$phone || !$code)
            {
                return_error(112); // 手机或验证码不能为空
            }
            if (!$passwd || $passwd != $cfm_passwd)
            {
                return_error(105); // 两次密码不一致
            }
            if (!\Common\Helper\ValidityVerification::IsPhone($phone))
            {
                return_error(104); // 手机号码格式错误
            }
            $result = \Common\Helper\ValidityVerification::IsPasswd($passwd);
            if ($result ['status'] != 1)
            {
                return_error(106); // 密码格式错误
            }
            // 用户账号验证
            $userModel = new \Common\Model\Erp\User ();
            $user = $userModel->getByPhone($phone);
            if (!$user)
            {
                return_error(114); // 用户不存在
            }
            $result = \Common\Helper\Sms\TianyiCaptcha::check($phone , $code , false);
            if (!$result)
            {
                return_error(113);
            }
            // 开始修改密码
            if (\Common\Helper\Erp\User::editUser($user ['user_id'] , array(
                        'password' => $passwd
                    )))
            { // 保存成功
                \Common\Helper\Sms\TianyiCaptcha::clear($phone); // 清除验证码
                return_success();
            }
            return_error(115); // 修改密码失败
        }

        /**
         * 修改密码
         * 
         * @author yusj | 最后修改时间 2015年4月28日上午9:23:07
         */
        public function changeLoginPwdAction()
        {
            $userModel = new \Common\Model\Erp\User ();
            $token = I('token' , '');
            $oldpwd = I('oldpwd' , '');
            $newpwd = I('newpwd' , '');
            $newpwd2 = I('newpwd2' , '');
            if ($newpwd !== $newpwd2)
            {
                return_error(105);
            }
            if (empty($oldpwd))
            {
                return_error(116);
            }
            if (empty($newpwd) || empty($newpwd2))
            {
                return_error(117); // 新密码不能为空
            }
            if ($oldpwd == $newpwd)
            {
                return_error(124); // 旧密码不能和新密码一样
            }
            $result = \Common\Helper\ValidityVerification::IsPasswd($newpwd);
            if ($result ['status'] != 1)
            {
                return_error(118); // 新密码格式错误
            }
            $token_info = \App\Api\Helper\User\TokenAuth::getTokenInfo($token);
            $guid = $token_info ['guid'];
            \App\Api\Helper\User\UserController::setSessionId($guid);
            $user = \App\Api\Helper\User\UserController::getCurrentUser();
            if (\Common\Helper\Erp\User::check($user ['username'] , $oldpwd))
            {
                if (\Common\Helper\Erp\User::editUser($user ['user_id'] , array(
                            'password' => $newpwd
                        )))
                {
                    return_success(array());
                }
                else
                {
                    return_error(115); // 修改密码失败
                }
            }
            else
            {
                return_error(116); // 旧密码错误
            }
        }

        /**
         * 修改冲账密码
         * 
         * @author yusj | 最后修改时间 2015年4月29日下午2:59:09
         */
        public function changeSafePwdAction()
        {
            $userModel = new \Common\Model\Erp\User ();
            $token = I('token' , '');
            $oldpwd = I('oldpwd' , '');
            $newpwd = I('newpwd' , '');
            $newpwd2 = I('newpwd2' , '');
            if ($newpwd !== $newpwd2)
            {
                return_error(105);
            }
            if (empty($oldpwd))
            {
                return_error(116); // 旧密码错误
            }
            if (empty($newpwd) || empty($newpwd2))
            {
                return_error(117);
            }
            if ($oldpwd == $newpwd)
            {
                return_error(125); // 登录密码不能和冲账密码一样
            }
            $result = \Common\Helper\ValidityVerification::IsPasswd($newpwd);
            if ($result ['status'] != 1)
            {
                return_error(118);
            }
            $token_info = \App\Api\Helper\User\TokenAuth::getTokenInfo($token);
            $user_id = $token_info ['user_id'];
            $is_manager = \Common\Helper\Erp\User::is_manager($user_id);
            if (!$is_manager)
            {
                return_error(119);
            }

            $guid = $token_info ['guid'];

            \App\Api\Helper\User\UserController::setSessionId($guid);
            $user = \App\Api\Helper\User\UserController::getCurrentUser();
            if (\Common\Helper\Erp\User::check($user ['username'] , $oldpwd))
            {
                if (\Common\Helper\Erp\Company::editPwd($user ['company_id'] , $newpwd))
                {
                    return_success(array());
                }
                else
                {
                    return_error(115);
                }
            }
            else
            {
                return_error(116);
            }
        }

        /**
         *
         * @author yusj | 最后修改时间 2015年4月29日下午1:21:41
         */
        public function logoutAction()
        {

            $token = I('token' , '');
            $app_type_num = I('app_type' , '');
            if (empty($app_type_num))
            {
                return_error(108);
            }
            switch ($app_type_num)
            {
                case 1 :
                    $app_type = 'app_android';
                    break;
                case 2 :
                    $app_type = 'app_ios';
                    break;
                default :
                    $app_type = 'unknown';
            }

            if ($app_type != 'app_ios' && $app_type != 'app_android')
            {
                return_error(109);
            }
            $token_info = \App\Api\Helper\User\TokenAuth::getTokenInfo($token);
            $guid = $token_info ['guid'];
            \App\Api\Helper\User\UserController::setSessionId($guid);
            $user = \App\Api\Helper\User\UserController::logoutCurrentUser($app_type);
            return_success(array());
        }

        /**
         * 设置个人信息
         * 
         * @author yusj | 最后修改时间 2015年4月29日下午5:18:00
         */
        public function setPersonalInfoAction()
        {
            $userModel = new \Common\Model\Erp\User ();
            $token = I('token' , '');
            $company_name = I('company_name' , '-1');
            $name = I('name' , '-1');
            $contact = I('contact' , '-1');
            $city_id = I('city_id' , '-1');
            $pattern = I('pattern' , '-1'); // 10：集中式，01：分散式，00：没有；11：都有
            $company_info = array();
            $user_extend_info = array();
            if ($company_name != '-1')
            {
                $company_info ['company_name'] = $company_name;
            }
            if ($name != '-1')
            {
                $user_extend_info ['name'] = $name;
            }
            if ($contact != '-1')
            {
                $user_extend_info ['contact'] = $contact;
            }
            if ($city_id != '-1')
            {
                $company_info ['city_id'] = $city_id;
            }
            if ($pattern != '-1')
            {
                $pattern_arr = array(
                    '10' ,
                    '01' ,
                    '00' ,
                    '11'
                );
                if (in_array($pattern , $pattern_arr))
                {
                    $company_info ['pattern'] = $pattern;
                }
                else
                {
                    return_error(123); // 租赁模式类型错误
                }
            }
            $token_info = \App\Api\Helper\User\TokenAuth::getTokenInfo($token);
            \App\Api\Helper\User\UserController::setSessionId($token_info ['guid']);
            $user = \App\Api\Helper\User\UserController::getCurrentUser();
            if (count($company_info) > 0)
            { // 更新公司信息
                $companyModel = new \Common\Model\Erp\Company ();
                $companyModel->edit(array(
                    'company_id' => $user ['company_id']
                        ) , $company_info);
            }
            if (count($user_extend_info) > 0)
            {
                $userModel = new \Common\Model\Erp\UserExtend ();
                $userModel->edit(array(
                    'user_id' => $user ['user_id']
                        ) , $user_extend_info);
            }
            return_success(array());
        }

        function getInfoAction()
        {
            $user_id = $this->getUserId();
            $User = new \Common\Model\Erp\User();
            $user_info = $User->getUserInfo($user_id);

            //获取用户信息失败
            if (!$user_info)
                return_error(134);

            $company_info = M('Company')->getOne(array('company_id' => $user_info['company_id']));
            $data = array(
                'account' => $user_info['username'] ,
                'username' => $user_info['name'] ,
                'phone' => $user_info['contact'] ,
                'gender' => $user_info['gender'] ,
                'city_id' => $user_info['city_id'] ,
                'birthday' => $user_info['birthday'] ,
                'company_name' => $user_info['company_name'] ,
                'company_id' => $user_info['company_id'] ,
                'pattern' => $user_info['pattern'] ,
            );


            return_success($data);
        }

        function getMsgAction()
        {
            $M = M('Message');
            $page = I('page' , 1);
            $size = I('size' , 10);
            $list = $M->getMessageList($this->getUserId() , $page , $size , '' , '');

            $msg_id = array();
            foreach ($list['data'] as &$info)
            {
                $msg_id[] = $info['message_id'];
                unset($info['template_id'] , $info['from_user_id'] , $info['to_user_id'] , $info['user_name'] , $info['is_delete']);
            }
            $user_id = $this->getUserId();
            if (count($msg_id) > 0)
                $M->edit(array('message_id' => $msg_id , 'to_user_id' => $user_id) , array('is_read' => 1));
            return_success($list);
        }

        function delMsgAction()
        {
            PV('message_ids');
            $M = M('Message');
            $message_ids = I('message_ids');
            $message_id = explode(',' , trim($message_ids , ','));
            $user_id = $this->getUserId();
            if (count($message_id) == 0)
                return_error(131);
            $del = $M->edit(array('message_id' => $message_id , 'to_user_id' => $user_id) , array('is_delete' => 1));
            if (!$del)
                return_error(142);
            return_success();
        }

        function ReportUUidAction()
        {

            PV('app_uuid');
            $user_id = $this->getUserId();
            $app_uuid = I('app_uuid' , '' , 'trim');
            $save = M('User')->edit(array('user_id' => $user_id) , array('app_uuid' => $app_uuid));
            if (!$save)
                return_error(142);
            return_success();
        }

    }
    