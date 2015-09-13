<?php

    namespace App\Web\Mvc\Controller\Plugins;

    use App\Web\Lib\Request;
    use Common\Helper\ValidityVerification;
    use Common\Helper\String;
    use Common\Helper\QRcode;
    use App\Web\Helper\Url;
    use Common\Helper\Weixin\Weixin;
    use Common\Helper\Qiniu\Image;

    //include('./phpqrcode/qrlib.php');
    //include 'phpqrcode.php';
    /**
     * 小站插件 jj
     * @author too|编写注释时间 2015年6月15日 下午2:19:27
     */
    class SubSiteController extends \App\Web\Lib\Controller
    {

        /**
         * 申请开通小站
         * @author too|编写注释时间 2015年6月15日 下午3:20:30
         */
        public function applyopenAction()
        {
            if (!Request::isPost())
            {
                $html = $this->fetch('applyopen');
                $this->returnAjax(array('status' => 1 , 'data' => $html , 'tag_name' => '开通小站' , 'model_js' => 'apply_openJs' , 'model_name' => 'apply_openJs'));
            }
            else
            {
                return $this->returnAjax(array('status' => 1 , 'url' => Url::parse("Plugins-SubSite/setBasicInfo")));
            }
        }

        /**
         * 设置基本信息
         * @author too|编写注释时间 2015年6月15日 下午3:42:02
         */
        public function setbasicinfoAction()
        {
            if (!Request::isPost())
            {
                // 取出所有省
                $provinceModel = new \Common\Model\Erp\Province();
                $provinceData = $provinceModel->getData();
                $this->assign('provincedata' , $provinceData);
                $html = $this->fetch('openingbasicset');
                $this->returnAjax(array('status' => 1 , 'data' => $html , 'tag_name' => '开通小站' , 'model_js' => 'opening_basic_setJs' , 'model_name' => 'opening_basic_setJs'));
            }
            else
            {
                // flat表
                $flat_name = I('post.flat_name' , '' , 'string'); // 公寓名称
                $domain_name = I('post.domain_name' , '' , 'string'); // 公寓域名
                $phone = I('post.phone' , 0 , 'string'); // 联系电话
                $summary = I('post.summary' , '' , 'string'); // 简介
                $name = I('post.name' , '' , 'string'); // 管理者名字
                $province_id = I('post.province_id' , 0 , 'int'); // 省ID
                $city_id = I('post.city_id' , 0 , 'int'); // 城市ID
                $area_id = I('post.area_id' , 0 , 'int'); // 区域ID
                $create_time = $_SERVER['REQUEST_TIME'];
                $update_time = $create_time;



                if (empty($flat_name) || empty($phone) || empty($province_id) || empty($city_id) || empty($domain_name)) // 公寓名，电话，省，市 ，域名 为必填项
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '信息不完整！'));
                }
                if (!empty($summary) && String::countStrLength($summary) > 400)
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '简介过长'));
                }
                if (String::countStrLength($flat_name) > 20)
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '公寓名过长'));
                }
                if (preg_match('#[\x7f-\xff]#' , $domain_name))
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '个性网址不能输入中文'));
                }
                if (strlen($domain_name) < 3 || strlen($domain_name) > 20)
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '域名长度范围在3-20个字符'));
                }
                $userInfo = $this->getUser();
                $flatTempArray = array(// flat表
                    'flat_name' => $flat_name ,
                    'domain_name' => $domain_name ,
                    'phone' => $phone ,
                    'summary' => $summary ,
                    'name' => $name ,
                    'founder_id' => $userInfo['user_id'] ,
                    'company_id' => $userInfo['company_id'] ,
                    'create_time' => $create_time ,
                    'update_time' => $update_time ,
                    'province_id' => $province_id ,
                    'city_id' => $city_id ,
                    'area_id' => (int) $area_id ,
                );

                $addModel = new \App\Web\Helper\Plugins\SubSite(); // 入库 flat
                //if($addModel->getOne())
                $flat_id = $addModel->addSubSite($flatTempArray);
                if ($flat_id == -1)
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '该区域下已经有小站了'));
                }
                elseif ($flat_id == -2)
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '该域名已经存在'));
                }
                elseif ($flat_id === false)
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '开通失败'));
                }
                else
                {
                    return $this->returnAjax(array('status' => 1 , 'url' => Url::parse("Plugins-SubSite/setPictureInfo/flat_id/$flat_id/city_id/$city_id/dn/$domain_name")));
                }
            }
        }

        public function getDefaultImgAction()
        {
            return $this->returnAjax($this->getDefaultImg());
        }

        private function getDefaultImg()
        {
            $data = array(
                'Fii8zvVmjgQ59gVykWdEld1X4q2h' , //1
                'FlVGAWM8KwpgjqLsjw7fhzjMf-dd' , //2
                'Frp8H51yWPAa1m0rlO_zmk52q40v' , //3
                'FjyJNAHIwFT6zu3gPWShFMrnN0Qa' , //4
                //故事
                'FiNFNll_9MAMH6itUbdTkeF7BzTQ' , //5
                'FknO0eG7SG6Vhja4Y1wYTo0l9p7n' , //6
                'FlJ6UFM5VEd6VadJJxAVpHN--RGR' , //7
                'FkgUGL2SZgScAYAn2DntUe3Rmb86' , //8
            );
            return $data;
        }

        /**
         * 设置图片
         * @author too|编写注释时间 2015年6月15日 下午3:46:12
         */
        public function setpictureinfoAction()
        {
            // 取出flat_id
            $flat_id = I('get.flat_id' , 0 , 'int');  //print_r($_POST);die;
            $city_id = I('get.city_id' , 0 , 'int');
            $domain_name = I('get.dn');
            $this->assign('flat_id' , $flat_id);//P($flat_id);
            $this->assign('city_id' , $city_id);//P($city_id);
            $this->assign('domain_name' , $domain_name);//P($city_id);

            $config = array(
                'w' => 367 ,
                'h' => 300 ,
                'q' => 100
            );
            // 形象

            $default_img = $this->getDefaultImg();
            $picurl1 = Image::imageView2($config , $default_img[0]); //1
            $picurl2 = Image::imageView2($config , $default_img[1]); //2
            $picurl3 = Image::imageView2($config , $default_img[2]); //3
            $picurl4 = Image::imageView2($config , $default_img[3]); //4
            // 故事
            $picurl5 = Image::imageView2($config , $default_img[4]); //5
            $picurl6 = Image::imageView2($config , $default_img[5]); //6
            $picurl7 = Image::imageView2($config , $default_img[6]); //7
            $picurl8 = Image::imageView2($config , $default_img[7]); //8
            $this->assign('pic1' , $picurl1);
            $this->assign('pic2' , $picurl2);
            $this->assign('pic3' , $picurl3);
            $this->assign('pic4' , $picurl4);
            $this->assign('pic5' , $picurl5);
            $this->assign('pic6' , $picurl6);
            $this->assign('pic7' , $picurl7);
            $this->assign('pic8' , $picurl8);
            if (!Request::isPost())
            {
                $html = $this->fetch('openingpicture');
                $this->returnAjax(array('status' => 1 , 'data' => $html , 'tag_name' => '开通小站' , 'model_js' => 'opening_picture_setJs' , 'model_name' => 'opening_picture_setJs'));
            }
            else
            {
                // flat_images表
                $picData = $_POST;
                $pic[] = trim((string) $picData['pic'][0]['filename']);
                $pic[] = trim((string) $picData['pic'][1]['filename']);
                $type[] = trim((int) $picData['pic'][0]['type']);
                $type[] = trim((int) $picData['pic'][1]['type']);

                $addModel = new \App\Web\Helper\Plugins\SubSite();
                $flatImagesModel = new \Common\Model\Plugins\WxxzFlatImages();
                $flatImagesModel->Transaction();
                for ($n = 0 , $length = count($pic); $n < $length; $n++)
                {
                    $flatImagesTempArray = array(// flat_images表
                        'type' => $type[$n] , //
                        'key' => $pic[$n] ,
                        'flat_id' => $flat_id ,
                        'create_time' => $_SERVER['REQUEST_TIME'] ,
                        'update_time' => $_SERVER['REQUEST_TIME']
                    );
                    if (!$addModel->addPicture($flatImagesTempArray))
                    {
                        $flatImagesModel->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '添加失败'));
                    }
                }
                $flatImagesModel->commit();
                return $this->returnAjax(array('status' => 1 , 'url' => Url::parse("Plugins-SubSite/setHouseInfo/flat_id/$flat_id/city_id/$city_id/dn/$domain_name")));
            }
        }

        /**
         * 设置房源[保存并开通小站]
         * @author too|编写注释时间 2015年6月15日 下午3:45:30
         */
        public function sethouseinfoAction()
        {
            ini_set('memory_limit' , '364M');
            // 取出flat_id
            $flat_id = I('request.flat_id' , '' , 'int');
            $city_id = I('get.city_id' , '' , 'int'); // print_r($city_id);
            $domain_name = I('get.dn');  //print_r($domain_name);
            $this->assign('flat_id' , $flat_id);
            $flatModel = new \Common\Model\Plugins\WxxzFlat();
            //$domain_name = $flatModel->getOne(array('id' => $flat_id) , array('domain_name'));
            $this->assign('domain_name' , $domain_name); // 开通后弹窗展示

            if (!Request::isPost())
            {
                $userInfo = $this->getUser();
                $oneMonthLater = strtotime('+1 month');
                // 集中式
                $viewFocusModel = new \Common\Model\Plugins\ViewFocus();
                // 构造where条件
                $where = new \Zend\Db\Sql\Where();
                $where->lessThanOrEqualTo('dead_line' , $oneMonthLater);//print_r($oneMonthLater);echo '--';print_r($userInfo['company_id']);echo '--';print_r($city_id);
                $flat_info = $flatModel->getOne(array('id' => $flat_id));

                $where->equalTo('company_id' , $userInfo['company_id']);
                $where->equalTo('city_id' , $city_id);

                if ($flat_info['area_id'] > 0)
                    $where->equalTo('area_id' , $flat_info['area_id']);

                $focusArr = $viewFocusModel->getData($where , array() , 5000);//echo $viewFocusModel->getLastSql();die;
                //print_r($focusArr);
                $focusArrResult = array();
                $picModel = new \Common\Model\Erp\Attachments(); // 用了查是否有图片
                $infoModel = new \App\Web\Helper\Plugins\SubSite(); //坑爹的室友面积啊
                foreach ($focusArr as $v)
                {
                    switch ($v['room_type'])
                    {
                        case '0tsecond':
                            $v['room_type'] = '单间次卧';
                            break;
                        case '1t1':
                            $v['room_type'] = '一室一厅';
                            break;
                        case '1t2':
                            $v['room_type'] = '两室一厅';
                            break;
                        case '1t3':
                            $v['room_type'] = '三室一厅';
                            break;
                        case '3t2':
                            $v['room_type'] = '三室两厅';
                            break;
                        case '4t2':
                            $v['room_type'] = '四室两厅';
                            break;
                        case '5t2':
                            $v['room_type'] = '五室两厅';
                            break;
                        case '0tmain':
                            $v['room_type'] = '单间主卧';
                            break;
                        case '0tgues':
                            $v['room_type'] = '单间客卧';
                            break;
                        case '0tor':
                            $v['room_type'] = '其他户型';
                        default:
                            $v['room_type'] = '其他户型';
                    }
                    if ($v['dead_line'] <= $oneMonthLater)
                    {
                        $count = $picModel->getCount(array('entity_id' => $v['room_id'] , 'module' => 'room_focus'));
                        $v['is_pic'] = $count;
                        if ($v['status'] == 2 && $v['rental_way'] == 1)
                        {
                            $detailArr = $infoModel->getDetails($v['house_type'] , $v['rental_way'] , $v['house_id'] , $v['room_id']); // 这里改了print_r($detailArr);

                            foreach ($detailArr['shiyou'] as $v1)
                            {
                                $shiyouarea[] = $v1['area'];
                            }
                            $v['shiyouarea'] = $shiyouarea;
                        }
                        $focusArrResult[$v['area_name']][$v['business_name']][$v['flat_name']][] = $v;
                    }
                }//print_r($focusArrResult);
                // 分散式
                $viewDispersiveModel = new \Common\Model\Plugins\ViewDispersive();
                $dispersiveArr = $viewDispersiveModel->getData($where , array() , 5000);
                $dispersiveArrResult = array();
                $HV = M('HouseEntirel');
                foreach ($dispersiveArr as $key => $v)
                {
                    if ($v['dead_line'] <= $oneMonthLater)
                    {
                        if ($v['rental_way'] == 1)
                        {
                            $count = $picModel->getCount(array('entity_id' => $v['room_id'] , 'module' => 'room'));
                        }
                        else
                        {
                            $count = $picModel->getCount(array('entity_id' => $v['house_id'] , 'module' => 'house'));

                            $room_info = $HV->getOne(array('house_id' => $v['house_id']));
                            //查找整租的room_id 如果没查询到则为错误数据 不展示
                            if (!isset($room_info['house_entirel_id']))
                            {
                                unset($dispersiveArr[$key]);
                                continue;
                            }
                            $v['room_id'] = $room_info['house_entirel_id'];
                        }
                        $v['is_pic'] = $count;
                        if ($v['status'] == 2 && $v['rental_way'] == 1)
                        {
                            $detailArr = $infoModel->getDetails($v['house_type'] , $v['rental_way'] , $v['house_id'] , $v['room_id']); // print_r($detailArr);
                            foreach ($detailArr['shiyou'] as $v1)
                            {
                                $shiyouarea[] = $v1['area'];
                            }
                            $v['shiyouarea'] = $shiyouarea;
                        }
                        $dispersiveArrResult[$v['area_name']][$v['business_name']][$v['address']][] = $v;
                    }
                } // print_r($focusArrResult);

                $this->assign('dispersiveArrResult' , $dispersiveArrResult); // 分散式展示数据
                $this->assign('focusArrResult' , $focusArrResult); // 集中式展示数据
                $html = $this->fetch('openinghouseset'); // P($dispersiveArrResult);
                $this->returnAjax(array('status' => 1 , 'data' => $html , 'tag_name' => '开通小站' , 'model_js' => 'opening_house_setJs' , 'model_name' => 'opening_house_setJs'));
            }
            else
            {
                $domain_name = I('post.domain_name'); // 域名
                $flat_id = I('post.flat_id'); // 小站公寓id
                $house_id = I('post.house_id'); // saas房源
                $room_id = I('post.room_id'); // saas 房源
                $house_type = I('post.house_type'); // 第一位1代表分散,2代表集中,第二位1代表合租2代表整租
                $rental_way = I('post.rental_way');
                $house_name = I('post.house_name'); // 房源昵称
                $community_id = I('post.community_id'); // saas小区id
                $community_name = I('post.community_name'); // saas小区名称
                $address = I('post.address'); // 公寓/小区地址
                $rent = I('post.rent'); // 租金
                $area_id = I('post.area_id'); // 区域id
                $business_id = I('post.business_id'); // 商圈id
                $area = I('post.area'); // 面积
                $floor = I('post.floor'); // 楼层
                $cover = I('post.cover'); // 封面
                $is_recom = I('post.is_recom'); // 是否推荐
                $city_id = I('post.city_id'); // 城市id
                $status = I('post.status'); // 入住状态:1未出租;2已出租
                $expire_time = I('post.expire_time'); // 到期时间
                $online_time = I('post.online_time'); // saas创建时间
                $create_time = $_SERVER['REQUEST_TIME']; //
                $is_delete = 0; // 是否删除0.否 1.是
                $H = M('House');
                $wxxzHouseModel = new \Common\Model\Plugins\WxxzHouse();
                $wxxzHouseModel->Transaction();
                $length = is_array($status) ? count($status) : 0;

                if (!empty($house_type)) // 有发布房源就添加入库
                {
                    for ($i = 0; $i < $length; $i++)
                    {
                        $tempData = array(
                            'flat_id' => $flat_id[0] ,
                            'room_id' => $room_id[$i] ,
                            'house_name' => $house_name[$i] ,
                            'community_id' => $community_id[$i] ,
                            'community_name' => $community_name[$i] ,
                            'address' => $address[$i] ,
                            'rent' => $rent[$i] ,
                            'area_id' => $area_id[$i] ,
                            'business_id' => $business_id[$i] ,
                            'area' => $area[$i] ,
                            'floor' => $floor[$i] ,
                            'is_recom' => $is_recom[$i] ,
                            'city_id' => $city_id[$i] ,
                            'status' => $status[$i] ,
                            'expire_time' => $expire_time[$i] ,
                            'online_time' => $online_time[$i] ,
                            'create_time' => $create_time ,
                            'is_delete' => $is_delete
                        );
                        if ($house_type[$i] == 2)
                        {
                            $tempData['house_id'] = $tempData['community_id']; //
                            $tempData['community_id'] = 0;
                        }
                        else
                        {
                            $tempData['house_id'] = $house_id[$i];
                        }
                        $tempData['house_type'] = $house_type[$i] . $rental_way[$i];

                        $tempData['cover'] = emptys($cover[$i]) ? $H->getHouseImage($tempData) : $cover[$i];

                        if (!$wxxzHouseModel->insert($tempData))
                        {

                            $wxxzHouseModel->rollback();
                            return $this->returnAjax(array('status' => 0 , 'message' => '保存失败'));
                        }
                    }
                }

                // 最后修改状态未正常,才算开通

                if (!$flatModel->edit(array('id' => $flat_id[0]) , array('is_delete' => 0 , 'domain_name' => $domain_name[0])))
                {
                    $wxxzHouseModel->rollback();
                    return $this->returnAjax(array('status' => 0 , 'message' => '保存失败'));
                }
                $wxxzHouseModel->commit();
                return $this->returnAjax(array('status' => 1 , 'message' => '保存成功'));
            }
        }

        /**
         * 微信登陆
         * @author too|编写注释时间 2015年6月15日 下午4:00:47
         */
        public function wechatLoginAction()
        {
            if (!Request::isPost())
            {
                $html = $this->fetch('weixin_login');
                $this->returnAjax(array('status' => 1 , 'data' => $html , 'tag_name' => '微信登陆' , 'model_js' => 'wechat_loginJs' , 'model_name' => 'wechat_loginJs'));
            }
        }

        /**
         * 基本信息编辑[opened]
         * @author too|编写注释时间 2015年6月16日 上午10:22:55
         */
        public function editsetbasicinfoAction()
        {
            if (!Request::isPost())
            {
                // 取出所有省
                $provinceModel = new \Common\Model\Erp\Province();
                $cityModel = new \Common\Model\Erp\City();

                $provinceData = $provinceModel->getData();
                $this->assign('provincedata' , $provinceData);
                // 取基本信息
                $dataModel = new \Common\Model\Plugins\WxxzFlat();
                $info = $this->getUser();
                $data = $dataModel->getData(array('is_delete' => 0 , 'founder_id' => $info['user_id'] , 'company_id' => $info['company_id']) , array() , 0 , 100 , 'id desc');//P($data);
                // 取出原来的市
                $cityArr = $cityModel->getOne(array('city_id' => $data[0]['city_id'])); // 用于下拉框展示 市
                $this->assign('cityArr' , $cityArr);
                $provinceStr = $provinceData[$data[0]['province_id'] - 1]['name']; // 用于下拉框展示 省
                $this->assign('provinceStr' , $provinceStr);

                $type = I('get.type' , 0);
                $this->assign('data' , $data[$type]); // print_r($data);
                $this->assign('dataArr' , $data);
                $html = $this->fetch('openedhead');//$this->display('basicset');
                $this->returnAjax(array('status' => 1 , 'data' => "$html" , 'tag_name' => '基本设置' , 'model_js' => 'openedJs' , 'model_name' => 'openedJs'));
            }
            else
            {
                // flat表
                $flat_id = I('post.flat_id');
                $flat_name = I('post.flat_name' , '' , 'string'); // 公寓名称
                $phone = I('post.phone' , 0 , 'string'); // 联系电话
                $summary = I('post.summary' , '' , 'string'); // 简介
                $name = I('post.name' , '' , 'string'); // 管理者名字
                //$province_id = I('post.province_id' , 0 , 'int'); // 省ID
                //$city_id = I('post.city_id' , 0 , 'int'); // 城市ID
                $update_time = $_SERVER['REQUEST_TIME'];

                if (empty($flat_name) || empty($phone)) // 公寓名，电话，省，市 ，域名 为必填项
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '信息不完整！'));
                }
                if (!empty($summary) && String::countStrLength($summary) > 400)
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '简介过长'));
                }
                if (String::countStrLength($flat_name) > 20)
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '公寓名过长'));
                }
                $userInfo = $this->getUser();
                $flatTempArray = array(// flat表
                    'flat_name' => $flat_name ,
                    'phone' => $phone ,
                    'summary' => $summary ,
                    'name' => $name ,
                    'founder_id' => $userInfo['user_id'] ,
                    'company_id' => $userInfo['company_id'] ,
                    'update_time' => $update_time
                        //'province_id' => $province_id ,
                        //'city_id' => $city_id
                );
                $addModel = new \App\Web\Helper\Plugins\SubSite(); // 入库 flat
                if (!$flat_id = $addModel->editSubsite(array('id' => $flat_id) , $flatTempArray))
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '修改失败'));
                }
                else
                {
                    return $this->returnAjax(array('status' => 1 , 'message' => '修改成功' , 'url' => Url::parse("Plugins-SubSite/setPictureInfo/flat_id/$flat_id")));
                }
            }
        }

        /**
         * 基本信息 iframe用
         */
        public function basicAction()
        {
            // 取基本信息
            $dataModel = new \Common\Model\Plugins\WxxzFlat();
            $provinceModel = new \Common\Model\Erp\Province();
            $cityModel = new \Common\Model\Erp\City();
            $provinceData = $provinceModel->getData();
            $areaModel = new \Common\Model\Erp\Area();
            $flat_id = I('get.flat_id');
            $data = $dataModel->getOne(array('id' => $flat_id));
            $data['city_name'] = $provinceData[$data['province_id'] - 1]['name'];
            $citytemp = $cityModel->getOne(array('city_id' => $data['city_id'])); // 用于下拉框展示 市
            $data['province_name'] = $provinceModel->getOne(array('province_id' => $data['province_id']));
            $data['province_name'] = $data['province_name']['name'];
            $data['city_name'] = $citytemp['name'];
            $data['status'] = 1;
            $cityInfo = $cityModel->getData(array('province_id' => $data['province_id']));

            $areaStr = $areaModel->getOne(array('area_id' => $data['area_id'])); // 用于下拉框展示 区域

            $this->assign('areaName' , count($areaStr) > 0 ? $areaStr['name'] : '所有区域');

            $this->assign('data' , $data);
            $this->display('basicset');
        }

        /**
         * 图片 iframe用
         */
        public function pictureAction()
        {
            $flat_id = I('get.flat_id' , 0);
            $this->assign('id' , $flat_id); // 丢给前端用
            if ($flat_id == 0)
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '系统错误!'));
            }
            if (!Request::isPost())
            {
                $config = array(
                    'w' => 367 ,
                    'h' => 300 ,
                    'q' => 100
                );
                // 形象
                $default_img = $this->getDefaultImg();
                $picurl1 = Image::imageView2($config , $default_img[0]); //1
                $picurl2 = Image::imageView2($config , $default_img[1]); //2
                $picurl3 = Image::imageView2($config , $default_img[2]); //3
                $picurl4 = Image::imageView2($config , $default_img[3]); //4
                // 故事
                $picurl5 = Image::imageView2($config , $default_img[4]); //5
                $picurl6 = Image::imageView2($config , $default_img[5]); //6
                $picurl7 = Image::imageView2($config , $default_img[6]); //7
                $picurl8 = Image::imageView2($config , $default_img[7]); //8
                $this->assign('pic1' , $picurl1);
                $this->assign('pic2' , $picurl2);
                $this->assign('pic3' , $picurl3);
                $this->assign('pic4' , $picurl4);
                $this->assign('pic5' , $picurl5);
                $this->assign('pic6' , $picurl6);
                $this->assign('pic7' , $picurl7);
                $this->assign('pic8' , $picurl8);
                $pictureModel = new \Common\Model\Plugins\WxxzFlatImages();
                $picturedata = $pictureModel->getData(array('flat_id' => $flat_id) , array('key' , 'type'));//print_r($picturedata);

                $picturedata = getArrayValue($picturedata , 'key');

                $this->assign('default_key1' , $picturedata[0]);
                $this->assign('default_key2' , $picturedata[1]);
                $this->assign('default_img1' , Image::imageView2($config , $picturedata[0]));
                $this->assign('default_img2' , Image::imageView2($config , $picturedata[1]));
                $this->assign('pics' , $picturedata);
                $this->display('picture');
            }
            else
            {
                $picData = $_POST;
                $pic[] = trim((string) $picData['pic'][0]['filename']);
                $pic[] = trim((string) $picData['pic'][1]['filename']);
                $type[] = trim((int) $picData['pic'][0]['type']);
                $type[] = trim((int) $picData['pic'][1]['type']);

                $addModel = new \App\Web\Helper\Plugins\SubSite();
                $flatImagesModel = new \Common\Model\Plugins\WxxzFlatImages();
                $flatImagesModel->Transaction();

                if (!$flatImagesModel->delete(array('flat_id' => $flat_id)))
                {
                    $flatImagesModel->rollback();
                    return $this->returnAjax(array('status' => 0 , 'message' => '添加失败'));
                }
                for ($n = 0 , $length = count($pic); $n < $length; $n++)
                {
                    $flatImagesTempArray = array(// flat_images表
                        'type' => $type[$n] , //
                        'key' => $pic[$n] ,
                        'flat_id' => $flat_id ,
                        'create_time' => $_SERVER['REQUEST_TIME'] ,
                        'update_time' => $_SERVER['REQUEST_TIME']
                    );
                    if (!$addModel->addPicture($flatImagesTempArray))
                    {
                        $flatImagesModel->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '修改失败'));
                    }
                }
                $flatImagesModel->commit();
                return $this->returnAjax(array('status' => 1 , 'message' => '修改成功' , 'url' => Url::parse("Plugins-SubSite/setHouseInfo/flat_id/$flat_id/city_id/$city_id")));
            }
        }

        /**
         * 房源 iframe用 编辑用
         */
        public function houseAction()
        {
            if (!Request::isPost())
            {
                $dataModel = new \Common\Model\Plugins\WxxzFlat();
                $flat_id = I('flat_id');
                $this->assign('flat_id' , $flat_id);
                $Arr = $dataModel->getOne(array('id' => $flat_id) , array('city_id' , 'area_id'));//P($Arr);
                $city_id = $Arr['city_id'];
                $area_id = $Arr['area_id'];
                $userInfo = $this->getUser();
                $oneMonthLater = strtotime('+1 month');
                // 集中式
                $viewFocusModel = new \Common\Model\Plugins\ViewFocus();
                $picModel = new \Common\Model\Erp\Attachments(); // 用了查是否有图片
                $infoModel = new \App\Web\Helper\Plugins\SubSite(); // 查坑爹的室友面积
                // 构造where条件
                $where = new \Zend\Db\Sql\Where();
                $where->lessThanOrEqualTo('dead_line' , $oneMonthLater);
                $where->equalTo('company_id' , $userInfo['company_id']);
                $where->equalTo('city_id' , $city_id);
                if ($area_id > 0)
                    $where->equalTo('area_id' , $area_id);

                $houseModel = new \Common\Model\Plugins\WxxzHouse();
                $houseinfoArr = $houseModel->getData(array('flat_id' => $flat_id)); // P($houseinfoArr);

                $checked = array();
                $cover = array();

                foreach ($houseinfoArr as &$info)
                {
                    $new_id = $info['house_type'] . $info['house_id'] . $info['room_id'];
                    $checked[$new_id] = $info['id'];
                    $info['new_id'] = $new_id;
                }
                $houseinfoArr = getArrayKeyClassification($houseinfoArr , 'new_id' , 'ALL');

                $focusArr = $viewFocusModel->getData($where);//print_r($focusArr);
                // 重写这个方法
                //array('entity_id' => $v['room_id'] , 'module' => 'room_focus')
//                 $wherepic = new \Zend\Db\Sql\Where();
//                 $wherepic->in('entity_id', $focusArr);
//                 $wherepic->equalTo('module', 'room_focus');
//                 $count = $picModel->getCount($wherepic);
//                 print_r($count);

                $focusArrResult = array();
                $HV = M('HouseEntirel');

                foreach ($focusArr as $v)
                {
                    switch ($v['room_type'])
                    {
                        case '0tsecond':
                            $v['room_type'] = '单间次卧';
                            break;
                        case '1t1':
                            $v['room_type'] = '一室一厅';
                            break;
                        case '1t2':
                            $v['room_type'] = '两室一厅';
                            break;
                        case '1t3':
                            $v['room_type'] = '三室一厅';
                            break;
                        case '3t2':
                            $v['room_type'] = '三室两厅';
                            break;
                        case '4t2':
                            $v['room_type'] = '四室两厅';
                            break;
                        case '5t2':
                            $v['room_type'] = '五室两厅';
                            break;
                        case '0tmain':
                            $v['room_type'] = '单间主卧';
                            break;
                        case '0tgues':
                            $v['room_type'] = '单间客卧';
                            break;
                        case '0tor':
                            $v['room_type'] = '其他户型';
                        default:
                            $v['room_type'] = '其他户型';
                    }
                    if ($v['dead_line'] <= $oneMonthLater)
                    {
                        $count = $picModel->getCount(array('entity_id' => $v['room_id'] , 'module' => 'room_focus'));
                        $v['is_pic'] = $count;
                        if ($v['status'] == 2 && $v['rental_way'] == 1)
                        {
                            $detailArr = $infoModel->getDetails($v['house_type'] , $v['rental_way'] , $v['flat_id'] , $v['room_id']); // print_r($detailArr);
                            foreach ($detailArr['shiyou'] as $v1)
                            {
                                $shiyouarea[] = $v1['area'];
                            }
                            $v['shiyouarea'] = $shiyouarea;
                        }
                        $new_id = "2" . $v['rental_way'] . $v['flat_id'] . $v['room_id'];
                        $v['checked'] = $checked[$new_id] && $count > 0 ? $checked[$new_id] : 0;
                        $v['cover'] = $houseinfoArr[$new_id]['cover'];
                        $v['is_recom'] = isset($houseinfoArr[$new_id]['is_recom']) ? $houseinfoArr[$new_id]['is_recom'] : 0;
                        $focusArrResult[$v['area_name']][$v['business_name']][$v['flat_name']][] = $v;
                    }
                }   //print_r($focusArrResult);
                // 分散式
                $viewDispersiveModel = new \Common\Model\Plugins\ViewDispersive();
                $dispersiveArr = $viewDispersiveModel->getData($where); // print_r($dispersiveArr);
                $dispersiveArrResult = array();

                foreach ($dispersiveArr as $key => $v)
                {
                    if ($v['dead_line'] <= $oneMonthLater)
                    {
                        if ($v['rental_way'] == 1)
                        {
                            $count = $picModel->getCount(array('entity_id' => $v['room_id'] , 'module' => 'room'));
                        }
                        else
                        {
                            $count = $picModel->getCount(array('entity_id' => $v['house_id'] , 'module' => 'house'));

                            $room_info = $HV->getOne(array('house_id' => $v['house_id']));
                            //查找整租的room_id 如果没查询到则为错误数据 不展示
                            if (!isset($room_info['house_entirel_id']))
                            {
                                unset($dispersiveArr[$key]);
                                continue;
                            }
                            $v['room_id'] = $room_info['house_entirel_id'];
                        }
                        $v['is_pic'] = $count;
                        if ($v['status'] == 2 && $v['rental_way'] == 1)
                        {
                            $detailArr = $infoModel->getDetails($v['house_type'] , $v['rental_way'] , $v['house_id'] , $v['room_id']); // print_r($detailArr);

                            foreach ($detailArr['shiyou'] as $v1)
                            {
                                $shiyouarea[] = $v1['area'];
                            }
                            $v['shiyouarea'] = $shiyouarea;
                        }
                        $new_id = "1" . $v['rental_way'] . $v['house_id'] . $v['room_id'];
                        $v['checked'] = $checked[$new_id] && $count > 0 ? $checked[$new_id] : 0;
                        $v['cover'] = $houseinfoArr[$new_id]['cover'];
                        $v['is_recom'] = isset($houseinfoArr[$new_id]['is_recom']) ? $houseinfoArr[$new_id]['is_recom'] : 0;
                        $v['house_name'] = $v['community_name'] . '-' . $v['custom_number'];
                        $dispersiveArrResult[$v['area_name']][$v['business_name']][$v['address']][] = $v;
                    }
                }



                // 计算区域->商圈的总房间数
//                 foreach ($dispersiveArrResult as $ko=>$vo)
//                 {
//                     foreach ($vo as $ko1=>$vo1)
//                     {   $sum = 0;
//                         foreach ($vo1 as $ko2=>$vo2)
//                         {
//                             $sum = $sum + count($vo2);
//                         }
//                         $vo1['allcount'] = $sum;
//                         $vo[$ko2] = $vo1;
//                     }
//                     $dispersiveArrResult[$ko] = $vo;
//                 }

                $this->assign('dispersiveArrResult' , $dispersiveArrResult); // 分散式展示数据
                $this->assign('focusArrResult' , $focusArrResult); // 集中式展示数据
                //$html = $this->fetch('openinghouseset');


                $this->assign('houseinfoArr' , $houseinfoArr);
                $this->display('houseset');
            }
            else
            {
                $wxxzid = I('post.wxxzid');

                $flat_id = I('post.flat_id'); // 小站公寓id
                $flat_id = $flat_id[0];
                $house_id = I('post.house_id'); // saas房源
                $room_id = I('post.room_id'); // saas 房源
                $house_type = I('post.house_type'); // 第一位1代表分散,2代表集中,第二位1代表合租2代表整租
                $rental_way = I('post.rental_way');
                $house_name = I('post.house_name'); // 房源昵称
                $community_id = I('post.community_id'); // saas小区id
                $community_name = I('post.community_name'); // saas小区名称
                $address = I('post.address'); // 公寓/小区地址
                $rent = I('post.rent'); // 租金
                $area_id = I('post.area_id'); // 区域id
                $cover = I('post.cover'); // 封面
                $business_id = I('post.business_id'); // 商圈id
                $area = I('post.area'); // 面积
                $floor = I('post.floor'); // 楼层
                $is_recom = I('post.is_recom'); // 是否推荐
                $city_id = I('post.city_id'); // 城市id
                $status = I('post.status'); // 入住状态:1未出租;2已出租
                $expire_time = I('post.expire_time'); // 到期时间
                $H = M('House');
                $online_time = I('post.online_time'); // saas创建时间
                $create_time = $_SERVER['REQUEST_TIME']; //
                $is_delete = 0; // 是否删除0.否 1.是d
                $wxxzHouseModel = new \Common\Model\Plugins\WxxzHouse();
                $wxxzHouseModel->Transaction();

                //$wxxzHouseModel->delete($condition)
//                 if (!$wxxzHouseModel->delete(array('flat_id' => $flat_id) , true))
//                 {
//                     $wxxzHouseModel->rollback();
//                     return $this->returnAjax(array('status' => 0 , 'message' => '发布失败'));
//                 }

                $where = new \Zend\Db\Sql\Where();
                if (!emptys($wxxzid))
                    $where->notIn('id' , $wxxzid);
                $where->equalTo('flat_id' , $flat_id);
                $wxxzHouseModel->delete($where , true);


                $length = is_array($status) ? count($status) : 0;

                for ($i = 0; $i < $length; $i++)
                {
                    $tempData = array(
                        'flat_id' => $flat_id ,
                        'room_id' => $room_id[$i] ,
                        'house_name' => $house_name[$i] ,
                        'community_id' => $community_id[$i] ,
                        'community_name' => $community_name[$i] ,
                        'address' => $address[$i] ,
                        'rent' => $rent[$i] ,
                        'area_id' => $area_id[$i] ,
                        'business_id' => $business_id[$i] ,
                        'area' => $area[$i] ,
                        'floor' => $floor[$i] ,
                        'is_recom' => $is_recom[$i] ,
                        'city_id' => $city_id[$i] ,
                        'status' => $status[$i] ,
                        'expire_time' => $expire_time[$i] ,
                        'online_time' => $online_time[$i] ,
                        'create_time' => $create_time ,
                        'is_delete' => $is_delete
                    );

                    if ($house_type[$i] == 2)
                    {
                        $tempData['house_id'] = $tempData['community_id']; //
                        $tempData['community_id'] = 0;
                    }
                    else
                    {
                        $tempData['house_id'] = $house_id[$i];
                    }
                    $tempData['house_type'] = $house_type[$i] . $rental_way[$i];



                    $tempData['cover'] = emptys($cover[$i]) ? $H->getHouseImage($tempData) : $cover[$i];



                    if (isset($wxxzid[$i])) // 已经存在的 就修改
                    {
                        $where = array(
                            'id' => $wxxzid[$i] ,
                            'flat_id' => $flat_id ,
                        );
                        if (!$wxxzHouseModel->edit($where , $tempData))
                        {

                            $wxxzHouseModel->rollback();
                            return $this->returnAjax(array('status' => 0 , 'message' => '保存失败!'));
                        }
                    }
                    else
                    {
                        if (!$wxxzHouseModel->insert($tempData))
                        {
                            $wxxzHouseModel->rollback();
                            return $this->returnAjax(array('status' => 0 , 'message' => '保存失败'));
                        }
                    }
                }

                $wxxzHouseModel->commit();
                return $this->returnAjax(array('status' => 1 , 'message' => '保存成功'));
            }
        }

        /**
         * 设置房源的封面
         */
//        public function setcoverAction()
//        {
//            $house_id = I('post.house_id' , 0);
//            $room_id = I('post.room_id' , 0);
//            $house_type = I('post.house_type');
//            $rental_way = I('post.rental_way');
//            $pic = I('post.key');
//            $wxxzHouseModel = new \Common\Model\Plugins\WxxzHouse();
//            $where = array(
//                'house_id' => $house_id ,
//                'room_id' => $room_id ,
//                'house_type' => $house_type . $rental_way
//            );
//            $data = array(
//                'cover' => $pic
//            );
//            if ($house_type == 2)
//            {
//                unset($where['house_id']);
//            }
//
//            if (!$wxxzHouseModel->edit($where , $data))
//            {
//                return $this->returnAjax(array('status' => 0 , 'message' => '设置失败'));
//            }
//            return $this->returnAjax(array('status' => 1 , 'message' => '设置成功'));
//        }

        /**
         * 编辑设置图片【opened】
         * @author too|编写注释时间 2015年6月15日 下午3:46:12
         */
        public function editsetpictureinfoAction()
        {
            // 取出flat_id
            $flat_id = I('get.flat_id' , 0 , 'int');
            $this->assign('flat_id' , $flat_id);//P($flat_id);
            if (!Request::isPost())
            {
                $dataModel = new \Common\Model\Plugins\WxxzFlat();
                $info = $this->getUser();
                $data = $dataModel->getData(array('founder_id' => $info['user_id'] , 'company_id' => $info['company_id']));//P($data);
                $this->assign('dataArr' , $data);
                $html = $this->fetch('openedpicture');
                $this->returnAjax(array('status' => 1 , 'data' => $html , 'tag_name' => '开通小站' , 'model_js' => 'opening_picture_setJs' , 'model_name' => 'opening_picture_setJs'));
            }
            else
            {
                // flat_images表
                $type = I('post.type' , '' , 'string'); // 类型1.形象图片2.故事图片
                $key = I('post.key' , '' , 'string'); // 图片key

                $flatImagesTempArray = array(// flat_images表
                    'type' => $type , //
                    'key' => $key ,
                    'flat_id' => $flat_id ,
                    'create_time' => $_SERVER['REQUEST_TIME'] ,
                    'update_time' => $_SERVER['REQUEST_TIME']
                );
                $addModel = new \App\Web\Helper\Plugins\SubSite();
                if (!$addModel->addPicture($flatImagesTempArray))
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '添加失败'));
                }
                else
                {
                    return $this->returnAjax(array('status' => 1 , 'url' => Url::parse("Plugins-SubSite/setHouseInfo/flat_id/$flat_id")));
                }
            }
        }

        /**
         * 编辑房源 展示还是取视图 再取插件表 , 判断如果存在就打钩
         * @author too|编写注释时间 2015年6月23日 下午4:50:54
         */
//          public function editsethouseinfoAction()
//         {
//             // 取出flat_id
//             $flat_id = I('get.flat_id' , 0 , 'int');
//             $this->assign('flat_id' , $flat_id);
//             if (!Request::isPost())
//             {
//                 $dataModel = new \Common\Model\Plugins\WxxzFlat();
//                 $info = $this->getUser();
//                 $data = $dataModel->getData(array('founder_id' => $info['user_id'] , 'company_id' => $info['company_id']));//P($data);
//                 $this->assign('dataArr' , $data);
//                 $userInfo = $this->getUser();//P($userInfo);
//                 $oneMonthLater = strtotime('+1 month'); // 一个月后时间戳
//                 // 集中式
//                 $viewFocusModel = new \Common\Model\Plugins\ViewFocus();
//                 $focusArr = $viewFocusModel->getData(array('company_id' => $userInfo['company_id']));
//                 $focusArrResult = array();
//                 foreach ($focusArr as $v)
//                 {
//                     if ($v['dead_line'] <= $oneMonthLater)
//                     {
//                         $focusArrResult[$v['area_name']][$v['business_name']][$v['flat_name']][] = $v;
//                     }
//                 }
//                 // 分散式
//                 $viewDispersiveModel = new \Common\Model\Plugins\ViewDispersive();
//                 $dispersiveArr = $viewDispersiveModel->getData(array('company_id' => $userInfo['company_id']));
//                 $dispersiveArrResult = array();
//                 foreach ($dispersiveArr as $v)
//                 {
//                     if ($v['dead_line'] <= $oneMonthLater)
//                     {
//                         $dispersiveArrResult[$v['area_name']][$v['business_name']][$v['address']][] = $v;
//                     }
//                 }
//                 $this->assign('dispersiveArrResult' , $dispersiveArrResult); // 分散式展示数据
//                 $this->assign('focusArrResult' , $focusArrResult); // 集中式展示数据
//                 $html = $this->fetch('openedhouseset');
//                 $this->returnAjax(array('status' => 1 , 'data' => $html , 'tag_name' => '开通小站' , 'model_js' => 'opening_house_setJs' , 'model_name' => 'opening_house_setJs'));
//             }
//             else
//             {
//                 $flat_id = I('post.flat_id'); // 小站公寓id
//                 $house_id = I('post.house_id'); // saas房源
//                 $room_id = I('post.room_id'); // saas 房源
//                 $house_type = I('post . house_type') . I('post . rental_way'); // 第一位1代表分散,2代表集中,第二位1代表合租2代表整租
//                 $house_name = I('post.house_name'); // 房源昵称
//                 $community_id = I('post.community_id'); // saas小区id
//                 $community_name = I('post.community_name'); // saas小区名称
//                 $address = I('post.address'); // 公寓/小区地址
//                 $rent = I('post.rent'); // 租金
//                 $area_id = I('post.area_id'); // 区域id
//                 $business_id = I('post.business_id'); // 商圈id
//                 $area = I('post.area'); // 面积
//                 $floor = I('post.floor'); // 楼层
//                 $is_recom = I('post.is_recom'); // 是否推荐
//                 $city_id = I('post.city_id'); // 城市id
//                 $status = I('post.status'); // 入住状态:1未出租;2已出租
//                 $expire_time = I('post.expire_time'); // 到期时间
//                 $online_time = I('post.online_time'); // saas创建时间
//                 $create_time = $_SERVER['REQUEST_TIME']; //
//                 $is_delete = 0; // 是否删除0.否 1.是
//                 $tempData = array(
//                     'flat_id' => $flat_id ,
//                     'house_id' => $house_id ,
//                     'room_id' => $room_id ,
//                     'house_type' => $house_type ,
//                     'house_name' => $house_name ,
//                     'community_id' => $community_id ,
//                     'community_name' => $community_name ,
//                     'address' => $address ,
//                     'rent' => $rent ,
//                     'area_id' => $area_id ,
//                     'business_id' => $business_id ,
//                     'area' => $area ,
//                     'floor' => $floor ,
//                     'is_recom' => $is_recom ,
//                     'city_id' => $city_id ,
//                     'status' => $status ,
//                     'expire_time' => $expire_time ,
//                     'online_time' => $online_time ,
//                     'create_time' => $create_time ,
//                     //'update_time' => $update_time ,
//                     'is_delete' => $is_delete
//                 );
//                 $wxxzHouseModel = new \Common\Model\Plugins\WxxzHouse();
//                 $wxxzHouseModel->Transaction();
//                 foreach ($tempData as $v)
//                 {
//                     if (!$wxxzHouseModel->insert($v))
//                     {
//                         $wxxzHouseModel->rollback();
//                         return $this->returnAjax(array('status' => 0 , 'message' => '发布失败'));
//                     }
//                 }
//                 // 删除操作
//                 $delhouse_id = I('post.delhouse_id'); // 待删除的房源    数组形式
//                 $delroom_id = I('post.delroom_id'); // 待删除的房源    数组形式
//                 if (!empty($delhouse_id) || !empty($delroom_id))
//                 {
//                     $deldata = array(
//                         'delhouse_id' => $delhouse_id ,
//                         'delroom_id' => $delroom_id
//                     );
//                     foreach ($deldata as $v)
//                     {
//                         if (!$wxxzHouseModel->edit(array('house_id' => $v['delhouse_id'] , 'room_id' => $v['delroom_id']) , array('is_delete' => 1)))
//                         {
//                             $wxxzHouseModel->rollback();
//                             return $this->returnAjax(array('status' => 0 , 'message' => '删除失败'));
//                         }
//                     }
//                 }
//                 $wxxzHouseModel->commit();
//                 return $this->returnAjax(array('status' => 1 , 'message' => '发布成功'));
//             }
//         }

        /**
         * 点击详情后取数据 and 编辑  户型 面积 租金 室友职业 室友面积 saas表
         * @author too|编写注释时间 2015年6月18日 下午2:48:24
         */
        public function getdetailsAction()
        {

            if (!Request::isPost())
            {
                $house_type = I('get.house_type');
                $rental_way = I('get.rental_way');
                $house_id = I('get.house_id');
                $room_id = I('get.room_id');
                $infoModel = new \App\Web\Helper\Plugins\SubSite();
                $detailArr = $infoModel->getDetails($house_type , $rental_way , $house_id , $room_id);

                foreach ($detailArr['picture'] as $k => $v)
                {
                    $config = array(
                        'w' => 238 ,
                        'h' => 145 ,
                        'q' => 100
                    );
                    $v['keys'] = $v['key'];
                    $v['key'] = Image::imageView2($config , $v['key']);
                    $detailArr['picture'][$k] = $v;
                }



                //LMS 删除当前房源的室友信息

                foreach ($detailArr['shiyou'] as $k => $v)
                {
                    if ($v['room_id'] == $room_id)
                        unset($detailArr['shiyou'][$k]);
                }
                if ($house_type == 1 && $rental_way == 1)
                {
                    $house_open_url = Url::parse("house-room/roomdetail/room_id/$room_id");
                }
                else if ($house_type == 1 && $rental_way == 2)
                {
                    $house_open_url = Url::parse("house-house/edit/house_id/$house_id");
                }
                else
                    $house_open_url = Url::parse("centralized-roomfocus/edit/room_focus_id/$room_id");


                //P($detailArr);
                // P($detailArrUrl);
                $detailArr['house_edit_url'] = $house_open_url;
                if (!empty($detailArr))
                {
                    $detailArr['status'] = 1;
                    return $this->returnAjax($detailArr);
                }
                else
                {
                    $detailArr['status'] = 0;
                    return $this->returnAjax($detailArr);
                }
            }
            else
            {
                $city_id = I('post.city_id');
                $flat_id = I('post.flat_id');

                $house_type = I('post.house_type');
                $house_id = I('post.house_id');
                $room_id = I('post.room_id');
                $rental_way = I('post.rental_way');

                $floor = I('post.floor');
                $room_type = I('post.room_type');
                $area = I('post.area');
                $money = I('post.money');
                $setmoney = I('post.setmoney');
                if (!empty($setmoney))
                {
                    $money = $setmoney;
                }
                $flag = I('post.flag' , 1); // 标记用户是否操作了图片

                $szhiye = I('post.szhiye'); // 室友的职业
                $sarea = I('post.sarea'); // 室友的面积
                $sid = I('post.sid'); // 室友id 用来修改职业
                $shouse_id = I('post.shouse_id'); //室友的房源id , 用来写入面积
                $sroom_id = I('post.sroom_id'); //室友的房源id , 用来写入面积
                $shouse_type = I('post.shouse_type'); //室友的房源类型 , 用来写入面积
                $srental_way = 1; //室友的出租类型 , 用来写入面积

                $pic = i('post.pic' , ''); // 很多图片,是个数组
                $picModel = new \Common\Model\Erp\Attachments();
                if ($flag == 1)
                {
                    if ($house_type == 1) // 分散
                    {
                        if ($rental_way == 1) // 合租
                        {
                            if (!$picModel->delete(array('entity_id' => $room_id , 'module' => 'room')))
                            {
                                return $this->returnAjax(array('status' => 0 , 'message' => '保存失败'));
                            }
                            for ($num = 0 , $length = count($pic); $num < $length; $num++)
                            {
                                $xdata = array(
                                    'bucket' => 'hicms-upload' ,
                                    'key' => $pic[$num] ,
                                    'module' => 'room' ,
                                    'entity_id' => $room_id
                                );
                                //print_r($xdata);
                                if (!$picModel->insert($xdata))
                                {
                                    return $this->returnAjax(array('status' => 0 , 'message' => '保存失败'));
                                }
                            }
                        }
                        else  //整租
                        {
                            if (!$picModel->delete(array('entity_id' => $house_id , 'module' => 'house')))
                            {
                                return $this->returnAjax(array('status' => 0 , 'message' => '保存失败'));
                            }
                            for ($num = 0 , $length = count($pic); $num < $length; $num++)
                            {
                                $xdata = array(
                                    'bucket' => 'hicms-upload' ,
                                    'key' => $pic[$num] ,
                                    'module' => 'house' ,
                                    'entity_id' => $house_id
                                );
                                //print_r($xdata);
                                if (!$picModel->insert($xdata))
                                {
                                    return $this->returnAjax(array('status' => 0 , 'message' => '保存失败'));
                                }
                            }
                        }
                    }
                    else // 集中
                    {
                        if (!$picModel->delete(array('entity_id' => $room_id , 'module' => 'room_focus')))
                        {
                            return $this->returnAjax(array('status' => 0 , 'message' => '保存失败'));
                        }
                        for ($num = 0 , $length = count($pic); $num < $length; $num++)
                        {
                            $xdata = array(
                                'bucket' => 'hicms-upload' ,
                                'key' => $pic[$num] ,
                                'module' => 'room_focus' ,
                                'entity_id' => $room_id
                            );
                            //print_r($xdata);
                            if (!$picModel->insert($xdata))
                            {
                                return $this->returnAjax(array('status' => 0 , 'message' => '保存失败'));
                            }
                        }
                    }
                }


                $tempData = array(
                    'house_type' => $house_type ,
                    'rental_way' => $rental_way ,
                    'house_id' => $house_id ,
                    'room_id' => $room_id ,
                    'floor' => $floor ,
                    //  'room_type' => $room_type ,
                    'area' => $area ,
                    'money' => $money ,
                    'szhiye' => $szhiye ,
                    'sarea' => $sarea ,
                    'sid' => $sid ,
                    'shouse_id' => $shouse_id ,
                    'sroom_id' => $sroom_id ,
                    'shouse_type' => $shouse_type ,
                    'srental_way' => $srental_way
                );

                $addModel = new \App\Web\Helper\Plugins\SubSite();


                if (!$addModel->completeInfo($tempData))
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '保存失败'));
                }
                else
                {
                    return $this->returnAjax(array('status' => 1 , 'message' => '保存成功' , 'url' => Url::parse("Plugins-SubSite/setHouseInfo/flat_id/$flat_id/city_id/$city_id")));
                }
            }
        }

        /**
         * 详情页面图片删除功能
         */
        public function delpicAction()
        {
            $house_id = I('post.house_id');
            $room_id = I('post.room_id');
            $house_type = I('post.house_type');
            $rental_way = I('post.rental_way');
            $picModel = new \Common\Model\Erp\Attachments();
            if ($house_type == 1)
            {
                if ($rental_way == 1) // 分散式合租
                {
                    if (!$picModel->delete(array('module' => 'room' , 'entity_id' => $room_id)))
                    {
                        return $this->returnAjax(array('status' => 0 , 'message' => '删除失败'));
                    }
                }
                else //分散式整租
                {
                    if (!$picModel->delete(array('module' => 'house' , 'entity_id' => $house_id)))
                    {
                        return $this->returnAjax(array('status' => 0 , 'message' => '删除失败'));
                    }
                }
            }
            else //集中式整租合租不区分
            {
                if (!$picModel->delete(array('module' => 'room_focus' , 'entity_id' => $room_id)))
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '删除失败'));
                }
            }
            return $this->returnAjax(array('status' => 1));
        }

//         //echo 'a';$text , $outfile = false , $level = QR_ECLEVEL_L , $size = 3 , $margin = 4 , $saveandprint = false
//         $data = 'http://www.baidu.com';
//         QRcode::png($data,false,3,5);
        //         //echo 'a';$text , $outfile = false , $level = QR_ECLEVEL_L , $size = 3 , $margin = 4 , $saveandprint = false
//         $data = 'http://www.baidu.com';
//         $fileName = '005_file_'.md5($data);
//         $ext = 'xx';
//         $path = './t/';
//         $pngtemp = $path.$fileName;
//         QRcode::png($data);
//         QRcode::png($data,false,3,5);
//         Header("Content-type: application/octet-stream");
//         Header("Accept-Ranges: bytes");
//         Header("Content-Disposition: attachment; filename=$ext.png");

        /**
         * 生成二维码 // 10正常 20中 30巨大
         */
        public function createqrcodeAction()
        {
            $data = I('get.data' , 'http://www.baidu.com'); // 小站URL
            $siteurl = \Core\Config::get('subsite:subsite.SITE_URL');
            $url = $siteurl . $data;
            $size = I('get.size' , ''); // 大小
            if (empty($size))
            { // 没传大小就不是下载
                QRcode::png($url , false , 3 , 10);
            }
            elseif ($size == 10)
            { // 下载普通大小
                Header("Content-type: application/octet-stream");
                Header("Accept-Ranges: bytes");
                Header("Content-Disposition: attachment; filename=jooozo.png");
                QRcode::png($url , false , 3 , $size);
            }
            elseif ($size == 20)
            { // 下载中型大小
                Header("Content-type: application/octet-stream");
                Header("Accept-Ranges: bytes");
                Header("Content-Disposition: attachment; filename=jooozo.png");
                QRcode::png($url , false , 3 , $size);
            }
            elseif ($size == 30)
            { // 下最大的咯
                Header("Content-type: application/octet-stream");
                Header("Accept-Ranges: bytes");
                Header("Content-Disposition: attachment; filename=jooozo.png");
                QRcode::png($url , false , 3 , $size);
            }
        }

        /**
         * 数据统计
         * // 1>小站个性域名 2>总预约数
         */
        public function datacountAction()
        {
            $now = strtotime(date('Y-m-01'));

            for ($num = 0; $num < 6; $num++)
            {
                if ($num > 0)
                    $now = strtotime("-1 month" , $now);
                $date[] = date('Y-m' , $now);
            }

            $this->assign('benyue' , date('Y-m' , time()));
            $this->assign('date' , $date);
            $flat_id = I('flat_id' , 0 , 'int');
            if (!Request::isPost())
            {

                $flatModel = new \Common\Model\Plugins\WxxzFlat();
                $tempArr = $flatModel->getOne(array('id' => $flat_id));
                $this->assign('domain_name' , $tempArr['domain_name']);
                $this->assign('flat_id' , $flat_id);
                return $this->display('datacount');
            }


            $date = I('post.date' , '' , 'string');
            if ($date == '')
            {
                $month = date('n' , $_SERVER['REQUEST_TIME']); // 月份 , 默认本月
                $year = date('Y' , $_SERVER['REQUEST_TIME']); // 年 ,默认今年
            }
            else
            {
                $date = strtotime($date);
                $month = date('n' , $date); // 月份 , 默认本月
                $year = date('Y' , $date); // 年 ,默认今年
            }

            $totalHelper = new \App\Web\Helper\DataCount();
            if ($flat_id <= 0)
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '系统错误!!'));
            }
            $this->assign('flat_id' , $flat_id);
            $totalData = $totalHelper->gettotaldata($flat_id , $month , $year);

            $this->returnAjax(array('status' => 1 , 'data' => $totalData));

            //$this->display('datacount');
        }

        public function weixinAction()
        {

            $flat_id = I('flat_id');
            $weixin = new Weixin();

            //没权限 进入开通页面
            if (!$weixin->getFlatAuth($this->user , array($flat_id)))
            {
                $html = $this->display('basicset');
                //return $this->returnAjax(array('status' => 1 , 'data' => $html , 'tag_name' => '微信功能管理' , 'model_js' => 'wechat_loginJs'));
            }
            $DB_NAME = C('subsite:subsite.DB_NAME');
            $T = T($DB_NAME . '.wxxz_weixin_info');
            $f_info = $T->where(array('flat_id' => $flat_id))->find();

            $url_info = $weixin->getQrCodeUrl($f_info['token'] , $f_info['appsecretid']);
            //获取二维码失败 就从新登录
            if ($url_info == false)
                $T->delete($f_info['id']);
            $weixin_status = $weixin->getWeixinStatus($flat_id);
            $this->assign('app_id' , $f_info['app_id']);
            $this->assign('app_secret' , $f_info['secret']);
            $this->assign('username' , $f_info['founder']);
            switch ($weixin_status)
            {
                case 1:
                    $url_info['token'] = $f_info['token'];
                    $url_info['url'] = urlencode($url_info['url']);
                    $this->assign('info' , $url_info);
                    // return $this->display('weixin_scan');
                    //return $this->returnAjax(array('status' => 11 , 'data' => $html , 'tag_name' => '微信功能管理' , 'model_js' => 'wechat_loginJs'));
                    $WTF = T($DB_NAME . '.wxxz_template_flat');
                    $temp_list = T($DB_NAME . '.wxxz_template')->field('id,name')->select();
                    $temp_list = getArrayKeyClassification($temp_list , 'id' , 'name');
                    $flat_temp_list = $WTF->where(array('flat_id' => $flat_id))->select();
                    foreach ($flat_temp_list as &$info)
                    {
                        if ($info['status'] == 0)
                        {
                            $info['status_name'] = '禁用';
                        }
                        else
                        {
                            $info['status_name'] = '启用';
                        }
                        $info['template_name'] = $temp_list[$info['template_id']];
                    }

                    $this->assign('flat_temp_list' , $flat_temp_list);
                    return $this->display('weixin_scan');
                //return $this->returnAjax(array('status' => 11 , 'data' => $html , 'tag_name' => '微信功能管理' , 'model_js' => 'wechat_loginJs'));
                case 2:
                    $WTF = T($DB_NAME . '.wxxz_template_flat');
                    $temp_list = T($DB_NAME . '.wxxz_template')->field('id,name')->select();
                    $temp_list = getArrayKeyClassification($temp_list , 'id' , 'name');
                    $flat_temp_list = $WTF->where(array('flat_id' => $flat_id))->select();


                    $WWI = T($DB_NAME . '.wxxz_weixin_info');

                    foreach ($flat_temp_list as &$info)
                    {
                        if ($info['status'] == 0)
                        {
                            $info['status_name'] = '未启用';
                        }
                        else
                        {
                            $info['status_name'] = '启用';
                        }
                        $info['template_name'] = $temp_list[$info['template_id']];
                    }
                    $url_info['token'] = $f_info['token'];
                    $url_info['url'] = urlencode($url_info['url']);
                    $is_open_temp = $weixin->isOpenTemplate($flat_id);

                    $this->assign('is_open_temp' , $is_open_temp ? 1 : 0);
                    $wx_menu = $list = $weixin->getMenu($flat_id);

                    $this->assign('menu_is_open' , $wx_menu['is_menu_open']);
                    $wx_menu = !isset($wx_menu['selfmenu_info']['button']) ? array() : $wx_menu['selfmenu_info']['button'];

                    $this->assign('info' , $url_info);
                    $this->assign('wx_menu' , json_encode($wx_menu));
                    $this->assign('app_id' , $f_info['app_id']);
                    $this->assign('flat_temp_list' , $flat_temp_list);
                    $this->assign('info' , $url_info);
                    return $this->display('weixin_scan');
                //return $this->returnAjax(array('status' => 12 , 'data' => $html , 'tag_name' => '微信功能管理' , 'model_js' => 'wechat_loginJs'));
                default :
                    return $this->display('weixin_login');
                //return $this->returnAjax(array('status' => 13 , 'data' => $html , 'tag_name' => '微信功能管理' , 'model_js' => 'wechat_loginJs'));
            }
        }

        public function scanqrcodeAction()
        {
            $weixin = new Weixin();
            $flat_id = I('flat_id');
            if (!$weixin->getFlatAuth($this->user , array($flat_id)))
            {
                $html = $this->fetch('weixin_login');
                return $this->returnAjax(array('status' => 1 , 'data' => $html , 'tag_name' => '微信功能管理' , 'model_js' => 'wechat_loginJs'));
            }
            $token = I('token');
            $uuid = I('uuid');
            $code_info = $weixin->getIsScanQrCode($token , $uuid);
            $status = $code_info['status'];
            if (!is_numeric($status) || $status == 401)
                return $this->returnAjax(array('status' => 0 , 'message' => ''));
            if ($status == 404)
                return $this->returnAjax(array('status' => 1 , 'message' => ''));
            $code = $code_info['code'];
            $secret = $weixin->getAppSecret($token , $code);

            if (is_string($secret))
            {
                $DB_NAME = C('subsite:subsite.DB_NAME');
                $T = T($DB_NAME . '.wxxz_weixin_info');
                $T->where(array('flat_id' => $flat_id))->save(array(
                    'secret' => $secret ,
                ));
            }
            return $this->returnAjax(array('status' => 2 , 'message' => ''));
        }

        /**
         *
         */
        public function saveweixinsecretAction()
        {
            $flat_id = I('flat_id');
            $weixin = new Weixin();
            //没权限 进入开通页面
            if (!$weixin->getFlatAuth($this->user , array($flat_id)))
            {
                $html = $this->fetch('weixin_login');
                return $this->returnAjax(array('status' => 1 , 'data' => $html , 'tag_name' => '微信功能管理' , 'model_js' => 'wechat_loginJs'));
            }
            $DB_NAME = C('subsite:subsite.DB_NAME');
            $T = T($DB_NAME . '.wxxz_weixin_info');
            $app_id = I('app_id');
            $secret = I('secret');

            if (emptys($app_id , $secret))
                return $this->returnAjax(array('status' => 0 , 'message' => '保存失败'));
            $save = $T->where(array('flat_id' => $flat_id))->save(array(
                'app_id' => $app_id ,
                'secret' => $secret ,
            ));

            return $this->returnAjax(array('status' => 1 , 'message' => '保存成功'));
        }

        public function imageAction()
        {
            $test = new \Common\Helper\Weixin\Weixin();
            $de = $test->getQrCodeImage(I('url' , '' , 'urldecode'));
        }

        public function weixin_loginAction()
        {
            if (!Request::isAjax())
                exit('404');
            $flat_id = I('flat_id');
            $weixin = new Weixin();
            $username = I('username');
            $pwd = I('pwd');
            $code = I('code');
            $user_info = $weixin->login($username , $pwd , $code);
            $token = $user_info['token'];
            if (!$token)
            {
                $error_font = $weixin->getLastError();

                switch ($error_font)
                {
                    case 'acct/password error':
                        $error_font = '账号或密码错误';
                        break;
                    case 'need verify code':
                        $error_font = '验证码错误';
                        break;
                    case '':
                        $error_font = '抱歉，该账号无法授权，请检查是否开启了风险操作保护！';
                        break;
                }
                return $this->returnAjax(array('status' => 0 , 'message' => $error_font));
            }
            $xz_url = C('subsite:subsite.SITE_URL');
            $domain = str_replace(array('http://' , '/') , '' , $xz_url);
            //设置登录授权域名
            $setDomain = $weixin->setoauthdomain($domain , $token);
            //  if (!$setDomain)
            //     return $this->returnAjax(array('status' => 0 , 'message' => '登录失败,微信公众号必须通过微信官方认证!'));
            // 服务号必须通过微信认证
            $info = $weixin->getDeveloperInfo($token);
            if (!$info)
                return $this->returnAjax(array('status' => 0 , 'message' => '登录失败,微信公众号必须通过微信官方认证'));
            $DB_NAME = C('subsite:subsite.DB_NAME');
            $T = T($DB_NAME . '.wxxz_weixin_info');
            $flat_info = $T->where(array('flat_id' => $flat_id))->find();
            $codeInfo = $weixin->getQrCodeUrl($token , $info['appsecretid']);
            if (!$codeInfo)
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '登录失败,请重试'));
            }
            if (count($flat_info) > 0)
            {
                $T->where(array('flat_id' => $flat_id))->save(array(
                    'password' => $pwd ,
                    'username' => $username ,
                    'appsecretid' => $info['appsecretid'] ,
                    'founder' => $user_info['setting']['wx_alias'] ,
                    'token' => $token ,
                    'app_id' => $info['appid'] ,
                    'update_time' => time() ,
                ));
            }
            else
            {
                $T->add(array(
                    'flat_id' => $flat_id ,
                    'password' => $pwd ,
                    'token' => $token ,
                    'appsecretid' => $info['appsecretid'] ,
                    'app_id' => $info['appid'] ,
                    'founder' => $user_info['setting']['wx_alias'] ,
                    'secret' => '' ,
                    'username' => $username ,
                    'create_time' => time() ,
                ));
            }

            return $this->returnAjax(array('status' => 1 , 'data' => '' , 'message' => '登录成功,请扫描二维码'));
        }

        public function yanzhengmaAction()
        {
            $weixin = new Weixin();
            $img = $weixin->getImageCode();
            Header("Content-type: image/png");
            echo base64_decode($img);
        }

        public function saveTempAction()
        {
            $weixin = new Weixin();
            $flat_id = I('get.flat_id');//print_r($flat_id);die;
            //没权限
            if (!$weixin->getFlatAuth($this->user , array($flat_id)))
            {
                return $this->returnAjax(array('status' => 0 , 'data' => '' , 'message' => '无权限操作'));
            }
            $id = I('id');
            $template_id = I('template_id');
            $template_name = I('template_name');
            $wx_template_id = I('wx_template_id');
            $attribute = I('attribute');
            $time = time();
            $data = array(
                'name' => $template_name ,
                'wx_template_id' => $wx_template_id ,
                'template_id' => $template_id ,
                'data' => serialize($attribute) ,
                'update_time' => $time ,
            );
            $DB_NAME = C('subsite:subsite.DB_NAME');
            $WTF = T($DB_NAME . '.wxxz_template_flat');
            if (!is_numeric($id))
            {
                $data['flat_id'] = $flat_id;
                $data['create_time'] = $time;
                $result = $WTF->add($data);
            }
            else
            {
                $result = $WTF->where(array('id' => $id , 'flat_id' => $flat_id))->save($data);
            }

            if (!$result)
                return $this->returnAjax(array('status' => 0 , 'data' => '' , 'message' => '操作失败'));
            return $this->returnAjax(array('status' => 1 , 'data' => '' , 'message' => '操作成功'));
        }

        public function getSystemTempAction()
        {
            $DB_NAME = C('subsite:subsite.DB_NAME');
            $WTF = T($DB_NAME . '.wxxz_template');
            $list = $WTF->field('id,name,template_select,template_code')->select();
            foreach ($list as &$info)
            {
                $info['template_select'] = unserialize($info['template_select']);
            }
            return $this->returnAjax(array('status' => 1 , 'data' => $list , 'message' => '操作成功'));
        }

//         public function getTempListAction()
//         {
//             $weixin = new Weixin();
//             $flat_id = I('flat_id');
//             //没权限
//             if (!$weixin->getFlatAuth($this->user , array($flat_id)))
//             {
//                 return $this->returnAjax(array('status' => 0 , 'data' => '' , 'message' => '无权限操作'));
//             }
//             $WTF = T('plugins.wxxz_template_flat');
//             $temp_list = T('plugins.wxxz_template')->field('id,name')->select();
//             $temp_list = getArrayKeyClassification($temp_list , 'id' , 'name');
//             $flat_temp_list = $WTF->where(array('flat_id' => $flat_id))->select();
//             foreach ($flat_temp_list as &$info)
//             {
//                 $info['template_name'] = $temp_list[$info['template_id']];
//             }
//             return $this->returnAjax(array('status' => 1  , 'message' => '操作成功'));
//         }

        public function setTempStatusAction()
        {
            $weixin = new Weixin();
            $flat_id = I('flat_id');
            $temp_id = I('id');
            $status = I('status');
            $status = $status == 0 ? 0 : 1;
            //没权限
            if (!$weixin->getFlatAuth($this->user , array($flat_id)))
            {
                return $this->returnAjax(array('status' => 0 , 'data' => '' , 'message' => '无权限操作'));
            }
            $DB_NAME = C('subsite:subsite.DB_NAME');
            $WTF = T($DB_NAME . '.wxxz_template_flat');
            $result = $WTF->where(array('flat_id' => $flat_id , 'id' => $temp_id))->save(array('status' => $status));
            if (!$result)
                return $this->returnAjax(array('status' => 0 , 'data' => '' , 'message' => '操作失败'));
            return $this->returnAjax(array('status' => 1 , 'data' => '' , 'message' => '操作成功'));
        }

        public function getTempInfoAction()
        {
            $weixin = new Weixin();
            $flat_id = I('flat_id');
            $temp_id = I('id');
            //没权限
            if (!$weixin->getFlatAuth($this->user , array($flat_id)))
            {
                return $this->returnAjax(array('status' => 0 , 'data' => '' , 'message' => '无权限操作'));
            }
            $DB_NAME = C('subsite:subsite.DB_NAME');
            $WTF = T($DB_NAME . '.wxxz_template_flat');
            $result = $WTF->where(array('flat_id' => $flat_id , 'id' => $temp_id))->find();
            if (!$result)
                return $this->returnAjax(array('status' => 0 , 'data' => '' , 'message' => '操作失败'));

            $temp_list = T($DB_NAME . '.wxxz_template')->field('id,name,template_code')->where(array('id' => 1))->find();
            $temp_list['template_select'] = unserialize($temp_list['template_select']);
            $result['data'] = unserialize($result['data']);
            $result['template_name'] = $temp_list['name'];
            $result['template_code'] = $temp_list['template_code'];
            $result['status'] == 0 ? $result['status'] = '未启用' : $result['status'] = '启用';
            return $this->returnAjax(array('status' => 1 , 'data' => $result , 'message' => '操作成功'));
        }

        public function DeleteTempAction()
        {
            $flat_id = I('flat_id');
            $weixin = new Weixin();
            $temp_id = I('id');
            //没权限
            if (!$weixin->getFlatAuth($this->user , array($flat_id)))
            {
                return $this->returnAjax(array('status' => 0 , 'data' => '' , 'message' => '无权限操作'));
            }
            $DB_NAME = C('subsite:subsite.DB_NAME');
            $delete = T($DB_NAME . '.wxxz_template_flat')->where(array('flat_id' => $flat_id , 'id' => $temp_id))->delete();
            if (!$delete)
                return $this->returnAjax(array('status' => 0 , 'data' => '' , 'message' => '删除失败'));
            return $this->returnAjax(array('status' => 1 , 'data' => '' , 'message' => '操作成功'));
        }

        public function saveMenuAction()
        {
            $weixin = new Weixin();
            $flat_id = I('flat_id');
            $body = I('body');

            if (!is_array($body) || count($body) == 0)
                return $this->returnAjax(array('status' => 0 , 'data' => '' , 'message' => '操作失败'));
            $url = getXzUrl($flat_id);

            foreach ($body as &$list)
            {

                if (isset($list['menu_type']) && !emptys($list['menu_type']))
                {
                    if (!isset($list['url']))
                        $list['url'] = $this->getTempUrl($url , $list['menu_type']);
                    continue;
                }
                foreach ($list['sub_button'] as &$info)
                {
                    if (isset($info['url']) && !emptys($info['url']))
                        continue;
                    $info['url'] = $this->getTempUrl($url , $info['menu_type']);
                }
            }

            $result = $weixin->saveMenu($flat_id , $body);
            if (!$result)
                return $this->returnAjax(array('status' => 0 , 'data' => '' , 'message' => $weixin->getLastError()));
            return $this->returnAjax(array('status' => 1 , 'data' => $result , 'message' => '操作成功'));
        }

        function getTempUrl($url , $type)
        {

            switch ($type)
            {
                case '1':
                    $url = $url . '?menu_type=' . $type . '#jpfy';//精品房源
                    break;
                case '2':
                    $url = $url . "/house?menu_type=$type&order_id=1#";//即将到期
                    break;
                default :
                    $url = $url . '?menu_type=' . $type . '#ppgs';//品牌故事
            }


            return $url;
        }

        public function testAction()
        {

            $flat_id = I('flat_id');
            $weixin = new Weixin();
            // $save = $weixin->saveMenu($flat_id , json_decode(I('body' , '' , 'trim') , true));
            //$list = $weixin->getMenu($flat_id);
            $html = '{"touser":"okVGJwQdDJ3IEGgydKfTsfH0O7wg","template_id":"AhyrTwFIsVnenMRBDeRgDNx3ZHmXzVQewJdm_946Lqo","url":"http://weixin.qq.com/download","topcolor":"#FF0000","data":{"User": {"value":"黄先生","color":"#173177"},"Date":{"value":"06月07日 19时24分","color":"#173177"},"CardNumber":{"value":"0426","color":"#173177"},"Type":{"value":"消费","color":"#173177"},"Money":{"value":"人民币260.00元","color":"#173177"},"DeadTime":{"value":"06月07日19时24分","color":"#173177"},"Left":{"value":"6504.09","color":"#173177"}}}';
            $list = $weixin->sendTemplate($flat_id , json_decode($html , true));
            dump($list);
            exit;
        }

        function getRoomInfoAction()
        {
            $type = 0;
            $id = 0;
            $house_id = I('house_id');
            $room_id = I('room_id');
            $house_type = I('house_type');
            $rental_way = I('rental_way');
            if ($house_type == 2)
            {
                $type = 3;
                $id = $room_id;
            }
            elseif ($rental_way == 1)
            {
                $type = 2;
                $id = $room_id;
            }
            else
            {
                $type = 1;
                $id = $house_id;
            }
            $data = array();
            switch ($type)
            {
                //分散式整租
                case '1':
                    $H = M('House');
                    $select = $H->getSqlObject()->select();
                    $result = $select->from(array('h' => 'house'))->join(array('r' => 'house_entirel') , 'h.house_id=r.house_id')->where(array(
                                'h.house_id' => $id ,
                            ))->limit(1)->execute();
                    $result = $result[0];
                    $data['rent'] = $result['money'];
                    $data['area'] = $result['area'];
                    $count = (int) $result['count'];
                    $hall = (int) $result['hall'];
                    $toilet = (int) $result['toilet'];
                    $data['room_type'] = "{$count}室{$hall}厅{$toilet}卫";
                    break;
                //分散式合租
                case '2':
                    $H = M('House');
                    $select = $H->getSqlObject()->select();
                    $result = $select->from(array('h' => 'house'))->join(array('r' => 'room') , 'h.house_id=r.house_id')->where(array(
                                'r.room_id' => $id ,
                            ))->limit(1)->execute();
                    $result = $result[0];
                    $data['rent'] = $result['money'];
                    $data['area'] = $result['area'];

                    $data['room_type'] = $this->getRoomType($result['room_type']);

                    break;
                //集中式房源
                case '3':
                    $R = M('RoomFocus');
                    $result = $R->getOne(array(
                        'room_focus_id' => $id ,
                    ));

                    $data['rent'] = $result['money'];
                    $data['area'] = $result['area'];
                    if (!empty($result['room_type']))
                    {

                        $data['room_type'] = $this->getRoomType($result['room_type']);
                    }
                    else
                    {
                        $count = (int) $result['count'];
                        $hall = (int) $result['hall'];
                        $toilet = (int) $result['toilet'];
                        $data['room_type'] = "{$count}室{$hall}厅{$toilet}卫";
                    }
                    break;
            }
            $this->returnAjax($data);
        }

        function getRoomType($room_type)
        {


            switch ($room_type)
            {
                case '0tsecond':
                    $room_type = '单间次卧';
                    break;
                case '1t1':
                    $room_type = '一室一厅';

                    break;
                case '1t2':
                    $room_type = '两室一厅';
                    break;
                case '1t3':
                    $room_type = '三室一厅';
                    break;
                case '3t2':
                    $room_type = '三室两厅';
                    break;
                case '4t2':
                    $room_type = '四室两厅';
                    break;
                case '5t2':
                    $room_type = '五室两厅';
                    break;
                case '0tmain':
                    $room_type = '单间主卧';
                    break;
                case '0tgues':
                    $room_type = '单间客卧';
                    break;
                case '0tor':
                    $room_type = '其他户型';
                    break;
                case 'main':
                    $room_type = '主卧';
                    break;
                case 'second':
                    $room_type = '次卧';
                    break;
                case 'guest':
                    $room_type = '客卧';
                    break;
                default:
                    $room_type = '其他户型';
            }
            return $room_type;
        }

    }
    