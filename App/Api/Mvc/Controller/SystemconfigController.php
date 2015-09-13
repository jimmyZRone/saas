<?php

    namespace App\Api\Mvc\Controller;

    class SystemconfigController extends \App\Api\Lib\Controller
    {

        /**
         * 获取系统配置
         * @author yusj | 最后修改时间 2015年5月9日上午10:55:33
         */
        public function getSystemConfigListAction()
        {

            $SystemConfigModel = new \Common\Model\Erp\SystemConfig();
            $data = $SystemConfigModel->get();
            // $user_info = $this->getUserInfo();
            //  $fee_type = H('FeeType')->getCompanyFeeType($user_info);
            //  $result['House_user_facilities'] = getArrayKeyClassification($fee_type , 'fee_type_id' , 'type_name');
            foreach ($data as $key => $val)
            {
                foreach ($val as $key2 => $val2)
                {
                    $result[$key . '_' . $key2] = $val2;
                }
            }
            $result['House_room_type'] = array("main" => "主卧" , "second" => "次卧" , "guest" => "客卧");
            $result['House_select_end_time'] = array(
                '1' => '今日到期' ,
                '2' => '三天到期' ,
                '3' => '一周到期' ,
                '4' => '一月到期' ,
                '5' => '已终止' ,
                '0' => '已到期' ,
            );

            $result['House_select_nex_pay_time'] = array(
                '1' => '今天之内' ,
                '2' => '三天之内' ,
                '3' => '一周之内' ,
            );

            $qiniu = new \Common\Helper\Qiniu();
            $image = new \Common\Helper\Qiniu\Image;
            
            //$result['QINIU_IMAGE_HOST'] = $qiniu->getUrl();
            $result['QINIU_IMAGE_HOST'] = $image::getDomain();
            $result['CHAOBIAO_ID_LIST'] = array('3' , '4' , '5');

            //     $list = array_merge_recursive($result['House_user_facilities'] , $result['House_public_facilities']);
            //倒序零到十二 不然JSON 的key将不存在
            krsort($result['House_PublicConfig']);
            return_success($result);
        }

        public function getQiNiuTokenAction()
        {
            $qiniu = new \Common\Helper\Qiniu();
            $auth = $qiniu->getAuth();
            $token = $auth->uploadToken($qiniu->getBucket());
            return_success(array('token' => $token));
        }

        /**
         * 获取系统配置
         * @author yusj | 最后修改时间 2015年5月9日上午10:55:33
         */
        public function getUserConfigListAction()
        {

            $list = M('Company')->get(false , $this->getUserInfo());

            $result = array();
            if (emptys($list['House']['public_facilities']))
            {
                $SystemConfigModel = new \Common\Model\Erp\SystemConfig();
                $list = $SystemConfigModel->get('House/public_facilities');

                foreach ($list as $key => $val)
                {
                    foreach ($val as $key2 => $val2)
                    {
                        $result[$key . '_' . $key2] = $val2;
                    }
                }
            }
            else
            {
                $result['House_public_facilities'] = is_array($list['House']['public_facilities']) ? $list['House']['public_facilities'] : unserialize($list['House']['public_facilities']);
            }
            $result = is_array($result) ? $result : array();
            return_success($result);
        }

        public function UpgradeAction()
        {
            PV('version' , 'type');
            $config = array(
                //安卓的升级信息
                '1' => array(
                    'version' => '1.1.2' ,
                    'description' => '这是安卓的升级的描述信息' ,
                    'upgrade_type' => '1' ,
                    'download_url' => 'http://sqdd.myapp.com/myapp/qqteam/AndroidQQ/mobileqq_android.apk' ,
                ) ,
                //IOS
                '2' => array(
                    'version' => '1.1.2' ,
                    'description' => '这是IOS升级的描述信息' ,
                    'upgrade_type' => '1' ,
                    'download_url' => 'http://ghost.25pp.com/soft/PPHelper-share.ipa' ,
                    'app_store_url' => 'https://itunes.apple.com/cn/app/qq-2011/id444934666?mt=8' ,
                ) ,
            );
            $version = I('version');
            $type = I('type');
            $type = $type == 1 ? 1 : 2;
            $up_info = $config[$type];
            if ($up_info['version'] <= $version)
                return_error(147);
            return_success($up_info);
        }

        function jurisdictionAction()
        {
            $auth_arr = array();
            if (!$this->IsManager())
                $auth_arr = \App\Api\Helper\User\UserController::getUserAuth($this->getUserId() , $this->getCompanyId());
            return_success(array('is_manager' => $this->IsManager() , 'auths' => $auth_arr));
        }

    }
    