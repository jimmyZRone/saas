<?php

    namespace App\Web\Mvc\Controller\Plugins;

    /**
     * 用户登录
     * @author lishengyou
     * 最后修改时间 2015年4月7日 上午10:07:36
     *
     */
    class XiaozhanController extends \App\Web\Lib\Controller
    {

        /**
         * 显示登录页面
         * @author lishengyou
         * 最后修改时间 2015年4月7日 上午10:28:31
         *
         */
        public function testAction()
        {
            $test = new \Common\Helper\Weixin\Weixin();
            //  echo "<img src='data:image/png;base64," . $test->getImageCode() . "'/>";
            //  $de = $test->login('285134475@qq.com' , 'sd8214024' , 'adym');
            // $de = $test->getDeveloperInfo('1768212043');
            $de = $test->getQrCodeUrl('646854957' , 'wx3a432d2dbe2442ce');
            dump($de);
            exit;
            //  echo "<img src='data:image/png;base64," . $de . "'/>";
        }

        public function imageAction()
        {
            $test = new \Common\Helper\Weixin\Weixin();
            $de = $test->getQrCodeImage(I('url'));
          
        }

        /**
         * 显示登录页面
         * @author lishengyou
         * 最后修改时间 2015年4月7日 上午10:28:31
         *
         */
        public function test2Action()
        {
            $test = new \Common\Helper\Weixin\Weixin();
            //  echo "<img src='data:image/png;base64," . $test->getImageCode() . "'/>";
            //  $de = $test->login('285134475@qq.com' , 'sd8214024' , 'adym');
            // $de = $test->getDeveloperInfo('1768212043');
            $de = $test->getIsScanQrCode('646854957' , '011gj2XfIrpIKawr');
            $de = $test->getAppSecret('646854957' , $de);
            dump($de);
            exit;
            // echo "<img src='data:image/png;base64," . $de . "'/>";
        }

    }
    