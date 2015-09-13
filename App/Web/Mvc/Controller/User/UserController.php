<?php

    namespace App\Web\Mvc\Controller\User;

    use App\Web\Helper\Url;
    use App\Web\Lib\Request;

    class UserController extends \App\Web\Lib\Controller
    {

        /**
         * 判断是否登陆
         * @author dengshaung
         * 最后修改时间 2015年3月11日  10:43
         *
         */
        public function isloginAction()
        {
            if (!!$this->user)
            {
                echo 1;
            }
            else
            {
                echo 0;
            }
        }

        /**
         * 注册
         * @author dengshaung
         * 最后修改时间 2015年3月11日  10:43
         *
         */
        protected function registerAction()
        {
            if (!\App\Web\Lib\Request::isPost())
            {
                $layout = new \Core\Ui\Layout();
                $ui = $layout->loadByFile(APP_WEB_LAYOUT_DIR . 'Login/form.html' , array('title' => '后台系统登录'));
                \Core\App\Event::trigger(\App\Web\Lib\Listing::REGISTER_FORM_INIT , $ui , \Core\Event::EVENT_TRANSFER);
                //echo
                $this->assign('form' , $ui->outerHTML());
                $this->display('register');
            }
            else
            {
                $phone = \App\Web\Lib\Request::queryString('post.name');
                $passwd = \App\Web\Lib\Request::queryString('post.passwd');
                if (\Common\Helper\Erp\User::addManager(array('username' => $phone , 'password' => $passwd)))
                {
                    die('注册成功了');
                }
                die('注册失败了');
            }
        }

        /**
         * 输出编码 测试用,后期可删除
         * @author dengshaung
         * 最后修改时间 2015年3月11日
         *
         */
        public function utf8()
        {
            echo '<meta charset="utf-8">';
        }

        /**
         * 登出
         *  最后修改时间 2015-3-18
         *  只完成基础功能
         *
         * @author dengshaung
         */
        public function logoutAction()
        {
            $user = \Common\Helper\Erp\User::logoutCurrentUser('web');
            $cookie = new \Common\Helper\Cookie();
            $cookie->setCookie('LOG_UID' , $autologinUid , strtotime('-1 years' , time()) , '/');
            \App\Web\Helper\Url::jump('@user-login');
        }

        /**
         * 用户信息
         *  最后修改时间 2015-3-18
         *  只完成基础功能
         *
         * @author dengshaung
         */
        protected function infoAction()
        {
            $user = $this->user;
            if (!\App\Web\Lib\Request::isPost())
            {
                $layout = new \App\Web\Lib\Ui\Layout();
                $companyModel = new \Common\Model\Erp\Company();
                $companyInfo = $companyModel->getOne(array('company_id' => $user['company_id']));

                $arr_pattern = array();
                $arr_pattern['1'] = substr($companyInfo['pattern'] , 0 , 1);
                $arr_pattern['2'] = substr($companyInfo['pattern'] , 1 , 1);
                $this->assign('arr_pattern' , $arr_pattern);
                $user['username_show'] = substr($user['username'] , 0 , 3) . '*****' . substr($user['username'] , -3 , 3);
                $this->assign('user' , $user);
                $this->assign('company' , $companyInfo);
                $cityId = $this->user['company']['city_id'];
                if (!$cityId)
                {
                    $cityId = $this->user['city_id'];
                }//print_r($_SESSION);
                $cityModel = new \Common\Model\Erp\City();
                //$cityHelper = new \Common\Helper\Erp\City();//echo 'ddd';
                //$city_list = $cityHelper->getCityList();
                $cityInfo = $cityModel->getOne(array('city_id' => $cityId));
                $city_hot = array(
                    0 => array('city_id' => 1 , 'shorthand' => 'bj' , 'name' => '北京') ,
                    1 => array('city_id' => 2 , 'shorthand' => 'sh' , 'name' => '上海') ,
                    2 => array('city_id' => 96 , 'shorthand' => 'gz' , 'name' => '广州') ,
                    3 => array('city_id' => 97 , 'shorthand' => 'sz' , 'name' => '深圳') ,
                    4 => array('city_id' => 118 , 'shorthand' => 'cd' , 'name' => '成都') ,
                    5 => array('city_id' => 44 , 'shorthand' => 'hz' , 'name' => '杭州') ,
                    6 => array('city_id' => 28 , 'shorthand' => 'nanjing' , 'name' => '南京') ,
                    7 => array('city_id' => 3 , 'shorthand' => 'tj' , 'name' => '天津') ,
                    8 => array('city_id' => 87 , 'shorthand' => 'wuhan' , 'name' => '武汉') ,
                    9 => array('city_id' => 4 , 'shorthand' => 'cq' , 'name' => '重庆')
                );
                //P($this->user);die();
                $this->assign('city' , $cityInfo);
                $this->assign("city_list" , $city_hot);//P($_COOKIE);
                $data = $this->fetch("User/Account/account_set.phtml");
                return $this->returnAjax(array("status" => 1 , "tag_name" => "帐号设置" , "model_name" => "account_info" , "model_js" => "account_setJs" , "model_href" => Url::parse("user-user/info") , "data" => $data));
            }
            else
            {
                $company_name = \App\Web\Lib\Request::queryString('post.company_name' , '');
                $jizhong = \App\Web\Lib\Request::queryString('post.jizhong' , 0);
                $fensan = \App\Web\Lib\Request::queryString('post.fensan' , 0);
                $linkman = \App\Web\Lib\Request::queryString('post.linkman' , '');
                $telephone = \App\Web\Lib\Request::queryString('post.telephone' . '');
                $city_id = \App\Web\Lib\Request::queryString("post.city_id" , 0 , "intval");//P($city_id);
                $city_name = \App\Web\Lib\Request::queryString("post.city_name" , '' , "string");//P($city_id);
                $allow_client = \App\Web\Lib\Request::queryString("post.allow_client" , 0 , "int");//P($city_id);
                $data = array();
                $data['pattern'] = $jizhong . $fensan;
                if ($data['pattern'] == '00')
                {
                    return $this->returnAjax(array("status" => 0 , "data" => "至少需要选择一种房源形态"));
                }
                $data['company_name'] = $company_name;
                $data['linkman'] = $linkman;
                $data['telephone'] = $telephone;
                $data['city_id'] = $city_id;
                $data['allow_client'] = $allow_client;
                $companyModel = new \Common\Model\Erp\Company();
                $userExtendModel = new \Common\Model\Erp\UserExtend();
                if ($city_id <= 0)
                {
                    $city_id = 118;
                }
                if (mb_strlen($linkman , 'utf-8') > 12)
                {
                    return $this->returnAjax(array("status" => 0 , "data" => "联系人不能大于12个字"));
                }
                if ($user['is_manager'] == 0)
                {//非主账号
                    unset($data['pattern'] , $data['company_name'] , $data['telephone'] , $data['linkman']);
                }
                $companyModel::TransactionByGuid($companyModel::getGuid());
                $result = $companyModel->edit(array('company_id' => $user['company_id']) , $data);
                //修改个人扩展信息
                if ($result)
                {
                    $result = $userExtendModel->edit(array('user_id' => $user['user_id']) , array('name' => $linkman , 'contact' => $telephone , 'city_id' => $city_id));
                }
                if ($result)
                {
                    $companyModel::CommitByGuid($companyModel::getGuid());
                    //P($_COOKIE);
                    return $this->returnAjax(array("status" => 1 , "data" => "修改成功" , 'url' => '/index.php?c=user-user&a=info' , 'city_id' => $city_id , 'city_name' => $city_name));
                }
                else
                {
                    $companyModel::RollbackByGuid($companyModel::getGuid());
                    return $this->returnAjax(array("status" => 0 , "data" => "修改失败"));
                }
                \App\Web\Helper\Url::jump('@user/info');
            }
        }

        /**
         * 修改城市
         * 修改时间2015年6月15日19:06:50
         *
         * @author yzx
         */
        public function changecityAction()
        {
            if (\App\Web\Lib\Request::isPost())
            {
                $user = $this->user;
                $companyModel = new \Common\Model\Erp\Company();
                $city_id = \App\Web\Lib\Request::queryString("post.city_id" , 0 , "int");//P($city_id);
                $city_name = \App\Web\Lib\Request::queryString("post.city_name" , '' , "string");//P($city_id);
                $data['city_id'] = $city_id;
                $result = $companyModel->edit(array('company_id' => $user['company_id']) , $data);
                if ($result)
                {
                    //P($_COOKIE);
                    return $this->returnAjax(array("status" => 1 , "data" => "修改成功" , 'url' => '/index.php?c=user-user&a=info' , 'city_id' => $city_id , 'city_name' => $city_name));
                }
                else
                {
                    return $this->returnAjax(array("status" => 0 , "data" => "修改失败"));
                }
            }
        }

        /**
         * 修改密码
         *  最后修改时间 2015-3-18
         *  只完成基础功能
         *
         * @author dengshaung
         */
        protected function changepwdAction()
        {
            $userModel = new \Common\Model\Erp\User();
            $user = $this->user;
            if (!\App\Web\Lib\Request::isPost())
            {
                $data = $this->fetch("User/Account/account_change_login_pwd");
                return $this->returnAjax(array("status" => 1 , "tag_name" => "修改登录密码" , "model_name" => "account_chang_pwd" , "model_js" => "account_chance_pwdJs" , "model_href" => Url::parse("user-user/changepwd") , "data" => $data));
            }
            else
            {
                $oldpwd = \App\Web\Lib\Request::queryString('post.oldpwd' , '');
                $newpwd = \App\Web\Lib\Request::queryString('post.newpwd' , '');
                $newpwd2 = \App\Web\Lib\Request::queryString('post.newpwd2' , '');
                if ($newpwd !== $newpwd2)
                {
                    return $this->returnAjax(array("status" => 0 , "data" => "2次密码不同"));
                }
                $username = $user['username'];
                $model = new \Common\Model\Erp\User();
                if ($user['is_manager'] == $model::NOT_MANAGER)
                {
                    $manager = $model->getOne(array('is_manager' => $model::IS_MANAGER , 'company_id' => $user['company_id']));
                    $username .= '@' . $manager['username'];
                }
                $result = \Common\Helper\ValidityVerification::IsPasswd($newpwd);
                if ($result['status'] != 1)
                {
                    return $this->returnAjax(array('status' => 0 , 'data' => $result['message']));
                }
                if (\Common\Helper\Erp\User::check($username , $oldpwd))
                {
                    if (\Common\Helper\Erp\User::editUser($user['user_id'] , array('password' => $newpwd)))
                    {
                        return $this->returnAjax(array("status" => 1 , "data" => "修改成功"));
                    }
                    else
                    {
                        return $this->returnAjax(array("status" => 0 , "data" => "修改失败"));
                    }
                }
                else
                {
                    return $this->returnAjax(array("status" => 0 , "data" => "旧密码错误"));
                }
            }
        }

        /**
         * 检查电话和验证码是否重复
         *  最后修改时间 2015-3-18
         *  只完成基础功能
         *
         * @author dengshaung
         */
        private function checkphonecode($phone , $code)
        {
            if (empty($phone) || empty($code))
            {
                return false;
            }
            $tempCacheModel = new \Common\Model\Erp\TempCache();
            $res = $tempCacheModel->getOne(array('key' => $phone . 'sendcode' , 'value' => $code));
            if (empty($res))
            {
                return false;
            }
            else
            {
                return true;
            }
        }

        /**
         * 修改冲账密码
         * 修改时间2015年4月8日 13:16:43
         *
         * @author yzx
         */
        protected function changsafepwdAction()
        {
            $user = $this->user;
            if (!Request::isPost())
            {
                $data = $this->fetch("User/Account/account_chang_safe_pwd.phtml");
                return $this->returnAjax(array("status" => 1 , "tag_name" => "修改冲账密码" , "model_name" => "account_chang" , "model_js" => "account_chang_safe_pwd" , "model_href" => Url::parse("user-user/changsafepwd") , "data" => $data));
            }
            else
            {
                $oldpwd = \App\Web\Lib\Request::queryString('post.safe_oldpwd' , '');
                $newpwd = \App\Web\Lib\Request::queryString('post.safe_newpwd' , '');
                $newpwd2 = \App\Web\Lib\Request::queryString('post.safe_newpwd2' , '');
                if ($newpwd !== $newpwd2)
                {
                    return $this->returnAjax(array("status" => 0 , "data" => "2次密码不同"));
                }
                $result = \Common\Helper\ValidityVerification::IsPasswd($newpwd);
                if ($result['status'] != 1)
                {
                    return $this->returnAjax(array('status' => 0 , 'data' => $result['message']));
                }
                if (\Common\Helper\Erp\Company::checkPwd($user['user_id'] , $oldpwd))
                {
                    if (\Common\Helper\Erp\Company::editPwd($user['company_id'] , $newpwd))
                    {
                        return $this->returnAjax(array("status" => 1 , "data" => "修改成功" , "tag" => Url::parse("user-user/info")));
                    }
                    else
                    {
                        return $this->returnAjax(array("status" => 0 , "data" => "修改失败"));
                    }
                }
                else
                {
                    return $this->returnAjax(array("status" => 0 , "data" => "旧密码错误"));
                }
            }
        }

        /**
         * 修改城市
         * 修改时间2015年5月30日 16:39:48
         *
         * @author yzx
         */
        protected function setcityAction()
        {
            if (Request::isPost())
            {
                $city_id = I("post.city_id" , 0 , "int");
                \Common\Helper\Erp\User::setCitySesion($city_id);//不保存在session,直接保存到数据库
                return $this->returnAjax(array("status" => 1 , "data" => 1 , "message" => "修改成功"));
            }
        }

        /**
         * 更多城市
         * @author too|编写注释时间 2015年6月12日 下午5:25:07
         */
        public function morecityAction()
        {
            $city_hot = array(
                0 => array('city_id' => 1 , 'shorthand' => 'bj' , 'name' => '北京') ,
                1 => array('city_id' => 2 , 'shorthand' => 'sh' , 'name' => '上海') ,
                2 => array('city_id' => 96 , 'shorthand' => 'gz' , 'name' => '广州') ,
                3 => array('city_id' => 97 , 'shorthand' => 'sz' , 'name' => '深圳') ,
                4 => array('city_id' => 118 , 'shorthand' => 'cd' , 'name' => '成都') ,
                5 => array('city_id' => 44 , 'shorthand' => 'hz' , 'name' => '杭州') ,
                6 => array('city_id' => 28 , 'shorthand' => 'nanjing' , 'name' => '南京') ,
                7 => array('city_id' => 3 , 'shorthand' => 'tj' , 'name' => '天津') ,
                8 => array('city_id' => 87 , 'shorthand' => 'wuhan' , 'name' => '武汉') ,
                9 => array('city_id' => 4 , 'shorthand' => 'cq' , 'name' => '重庆')
            );

            // 取所有城市
            $cityHelper = new \Common\Helper\Erp\City();
            $city_list = $cityHelper->getCityList();
            $uHelper = new \App\Web\Helper\User();
            $city_list = $uHelper->cityTree($city_list);
            // 取省
            $provinceModel = new \Common\Model\Erp\Province();
            $provinceData = $provinceModel->getData();//P($provinceData);
            $this->assign('provinceData' , $provinceData);
            $this->assign('city_hot' , $city_hot);
            $this->assign('city_list' , $city_list);
            $data = $this->fetch("User/Account/city_choose.phtml");
            return $this->returnAjax(array("status" => 1 , "tag_name" => "更多城市" , "model_name" => "city_choose" , "model_js" => "city_choose" , "model_href" => Url::parse("user-user/moreCity") , "data" => $data));
        }

        /**
         * 更多城市
         * @author too|编写注释时间 2015年6月12日 下午5:25:07
         */
        public function indmorecityAction()
        {
            $city_hot = array(
                0 => array('city_id' => 1 , 'shorthand' => 'bj' , 'name' => '北京') ,
                1 => array('city_id' => 2 , 'shorthand' => 'sh' , 'name' => '上海') ,
                2 => array('city_id' => 96 , 'shorthand' => 'gz' , 'name' => '广州') ,
                3 => array('city_id' => 97 , 'shorthand' => 'sz' , 'name' => '深圳') ,
                4 => array('city_id' => 118 , 'shorthand' => 'cd' , 'name' => '成都') ,
                5 => array('city_id' => 44 , 'shorthand' => 'hz' , 'name' => '杭州') ,
                6 => array('city_id' => 28 , 'shorthand' => 'nanjing' , 'name' => '南京') ,
                7 => array('city_id' => 3 , 'shorthand' => 'tj' , 'name' => '天津') ,
                8 => array('city_id' => 87 , 'shorthand' => 'wuhan' , 'name' => '武汉') ,
                9 => array('city_id' => 4 , 'shorthand' => 'cq' , 'name' => '重庆')
            );

            // 取所有城市
            $cityHelper = new \Common\Helper\Erp\City();
            $city_list = $cityHelper->getCityList();
            $uHelper = new \App\Web\Helper\User();
            $city_list = $uHelper->cityTree($city_list);
            // 取省
            $provinceModel = new \Common\Model\Erp\Province();
            $provinceData = $provinceModel->getData();//P($provinceData);
            $this->assign('provinceData' , $provinceData);
            $this->assign('city_hot' , $city_hot);
            $this->assign('city_list' , $city_list);
            $data = $this->fetch("User/Account/intex_city_choose.phtml");
            return $this->returnAjax(array("status" => 1 , "tag_name" => "更多城市" , "model_name" => "city_choose" , "model_js" => "city_choose" , "model_href" => Url::parse("user-user/indmorecity") , "data" => $data));
        }

        /**
         * 通过省份ID取城市
         * @author too|编写注释时间 2015年6月13日 上午11:51:44
         */
        public function findcityfromprovinceidAction()
        {
            $pid = I('post.pid' , 0 , 'int');
            $cityHelper = new \Common\Helper\Erp\City();
            $city_list = $cityHelper->getCityList($pid);//P($city_list);
            if (!empty($city_list))
            {
                return $this->returnAjax(array("status" => 1 , "city_list" => $city_list));
            }
            else
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '数据不存在'));
            }
        }

        /**
         * 通过省份ID取城市
         * @author LMS 2015年9月7日 09:37:35
         */
        public function getAreaListAction()
        {
            $city_id = I('city_id' , 0 , 'int');
            $cityHelper = new \Common\Helper\Erp\Area();
            $city_list = $cityHelper->getAreaList($city_id);//P($city_list);
            if (!empty($city_list))
            {
                return $this->returnAjax(array("status" => 1 , "city_list" => $city_list));
            }
            else
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '数据不存在'));
            }
        }

    }
    