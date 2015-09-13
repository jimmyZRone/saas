<?php

    namespace App\Api\Mvc\Controller;

    class CommunityController extends \App\Api\Lib\Controller
    {

        /**
         * 下载小区列表
         * @author Lms 2015年4月30日 10:12:31
         */
        public function downloadCommunityAction()
        {

            $city_id = I('city_id');
            //城市ID 错误
            if (!is_numeric($city_id))
                return_error('201');
            $fields = array(
                'community_id' , 'community_name' , 'city_id' , 'first_letter' , 'longitude' , 'latitude' ,
            );
            //验证七牛云的文件是否存在
            //没有存在
            $file_dir = "download/community/city_$city_id/";
            $file_save_dir = $_SERVER['DOCUMENT_ROOT'] . '/' . $file_dir;
            $QN = new \Common\Helper\Qiniu();
            $file_name = md5("JoooZoCity_" . $city_id) . ".zip";
            $goto = false;
            down:
            $down_info = $QN->getDownloadUrl($file_dir . $file_name);
            if (is_array($down_info))
                return_success(array(
                    'download_url' => $down_info['url'] ,
                    'version' => $down_info['hash'] ,
                ));

            //只走一次错误流程 第二次失败则返回失败
            if ($goto)
                return_error(133);

            $http_dir = API_URL . $file_dir;
            $Community = new \Common\Helper\Erp\Community();
            $result = $Community->getAddressByName(array('city_id' => $city_id) , '' , array(0 , 9999999) , $fields);
            if (!$result || count($result) < 1)
                return_error(132);
            $json = '';

            foreach ($result as $info)
            {
                $json .= json_encode($info) . "\r\n";
            }

            if (!is_dir($file_save_dir))
                mkdirs($file_save_dir , 0755);
            $zip = new \ZipArchive();
            $result = $zip->open("$file_save_dir/$file_name" , \ZipArchive::CREATE);
            $result = $zip->addFromString('data.json' , $json);
            $zip->close();
            $QN = new \Common\Helper\Qiniu();
            $upload = $QN->Upload($file_dir , $file_name);
            if (!$upload)
                return_error(136);
            $goto = true;
            goto down;
        }

        function getUserCommunityListAction()
        {
            $House = new \Common\Helper\Erp\House();
            $user_id = $this->getUserId();
            $result = $House->getUserHouseCommunity($user_id);

            $data = array();
            foreach ($result as $info)
            {
                $data[] = array(
                    'community_id' => $info['community_id'] ,
                    'city_id' => $info['city_id'] ,
                    'community_name' => $info['community_name'] ,
                );
            }
            return_success($data);
        }

        public function addAction()
        {

            PV(array('area_id' , 'business_id' , 'community_name' , 'address'));
            $communityHelper = new \Common\Helper\Erp\Community();
            $data = array();
            $community_name = I("community_name");
            $community_name = mb_substr($community_name , 0 , 255 , 'UTF-8');
            $area_id = I("area_id");
            $business_id = I("business_id");
            $address = I("address");
            $address = mb_substr($address , 0 , 255 , 'UTF-8');

            $info = M('Community')->getOne(array('area_id' => $area_id , 'business_id' => $business_id , 'community_name' => $community_name) , array('is_verify'));


            //小区已经存在
            if (count($info) > 0 && $info['is_verify'] == '0')
            {
                // 未审核
                return_error(145);
            }
            else if (count($info) > 0)
            {
                //正常小区
                return_error(144);
            }

            


            //检查地区ID和商圈ID是否配套
            $business = M('Business');
            $business_info = $business->getOne(array('business_id' => $business_id , 'area_id' => $area_id) , array('name'));
            //商圈与地区信息不匹配
            if (emptys($business_info))
                return_error(137);
            //获取地区名称
            $area_info = M('Area')->getOne(array('area_id' => $area_id) , array('name' , 'city_id'));
            if (emptys($area_info))
                return_error(137);//商圈与地区信息不匹配

            $user = $this->getUserInfo();
            $data['community_name'] = $community_name;
            $data['area_id'] = $area_id;
            $data['area_string'] = $area_info['name'];
            $data['business_id'] = $business_id;
            $data['address'] = $address;
            $data['business_string'] = $business_info['name'];
            $data['city_id'] = $area_info['city_id'];
            $data['landmark']='';

            $result = $communityHelper->addCommunit($data , $user);
            if (!$result)
                return_error(127);
            return_success(array('community_id' => $result));
        }

        public function getCommunityHouseListAction()
        {
            PV(array('community_id'));
            $company_id = $this->getCompanyId();
            $community_id = I('community_id');
            $result = M('Community')->getCommunityHouse($company_id , $community_id);
            foreach ($result as $key => $info)
            {
                unset($result[$key]['is_delete'] , $result[$key]['is_delete'] , $result[$key]['update_time'] , $result[$key]['create_time']);
            }
            return_success($result);
        }

    }
    