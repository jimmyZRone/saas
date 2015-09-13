<?php

    namespace App\Web\Mvc\Controller;

    use Zend\Db\Sql\Expression;
    use App\Web\Lib\Request;
    use App\Web\Helper\Url;
    use Common\Helper\Cookie;
	class IndexController extends \App\Web\Lib\Controller
    {

        public function indexAction()
        {
        	$cookie = new Cookie();
        	$session_city = $cookie->getCookie("city_session");
            $user = $this->user;
            $memo_arr = array(
                '1'=>'按时提醒',
                '2'=>'提前5分钟',
                '3'=>'提前10分钟',
                '4'=>'提前半小时',
                '5'=>'提前一小时',
                '6'=>'不提醒',
            );
            $this->assign('memo_arr',$memo_arr);
            $flat = new \Common\Model\Erp\Flat();
            $index_model = new \Common\Model\Erp\Index();
            
            $flatList = $flat->flatList(1 , $user , 1000,array('f.city_id'=>$session_city));
            $total_backlog = $index_model->backlogTotal($user['company_id'],$this->user);

            $this->assign('total_backlog', $total_backlog);
            $this->assign('flatList' , $flatList['data']);
            $this->assign("user" , $user);//echo 'fafaf';
            $cityModel = new \Common\Model\Erp\City();
            $city= $cityModel->getOne(array('city_id'=>$session_city));//P()
            $this->assign("city" , $city);
            $this->display();
        }
		public function printAction(){
			$this->display();
		}
        public function gethousestatisticsAction()
        {

            if (!\App\Web\Lib\Request::isAjax())
            {
                $this->display('index');
                exit();
            }
            $user = $this->user;
            $House = new \Common\Model\Erp\House();
            $flat_id = I('flat_id' , '' , 'trim');
            $list = $House->getHouseRoomInfo($user , $flat_id);
            $data = array("status" => 1 , "data" => $list , "message" => "成功");
            $this->returnAjax($data);
        }

        public function getaccountstatisticsAction()
        {
            if (!\App\Web\Lib\Request::isAjax())
            {
                $this->display('index');
                exit();
            }
            $user = $this->user;
            $city_id = $user['city_id'];
            $company_id = $user['company_id'];
            $flat_id = (int)I('post.flat_id' , '' , 'trim');
            $SN = new \Common\Model\Erp\SerialNumber();
            $serial_helper = new \Common\Helper\Erp\SerialNumber();
            //该城市下所有房源
            $house_id_arr = $serial_helper->getHouseIdByCityId($city_id, $company_id);
            $house_id = array_column($house_id_arr, 'house_id');
            $flat_id_arr = $serial_helper->getRoomFocusIdByCityId($city_id, $company_id);
            $rf_room_id = array_column($flat_id_arr, 'rf_room_id');
            $list = $SN->getAllCount($user, $flat_id, $house_id, $rf_room_id);
            $data = array("status" => 1 , "data" => $list , "message" => "成功");
            $this->returnAjax($data);
        }

        /**
         * 获取当月待办事项
         * 修改时间 2015年5月21日11:02:12
         *
         * @author ft
         */
        public function currentmonthbacklogAction() {
            $company_id = $this->user['company_id'];
            $uid = $this->getUser()['user_id'];
            $index_helper = new \Common\Helper\Erp\Index();
            $mlist = new \App\Web\Helper\Memo();
            //当前月时间
            $year_month = I('request.year_month', '');
            //当前月的当天时间
            $month_current_day = strtotime(I('request.month_day', ''));
            //获取当天结束的时间
            $y = date('Y',$month_current_day);
            $m = date('m',$month_current_day);
            $d = date('d',$month_current_day);
            $end_day = mktime(23,59,59,$m,$d,$y);
            //当月待办事项
            if (!empty($year_month)) {
                //当月第一天
                $current_first_day = strtotime($year_month);
                //当月最后一天
                $current_last_day = strtotime("$year_month +1 month -1 day");
                $date_info = $index_helper->getCurrentMonthBacklog($current_first_day, $current_last_day, $company_id,$this->user);
                foreach ($date_info as $key => $date) {
                    $date_info[$key]['deal_time'] = date('Y-n-j', $date['deal_time']);
                }
                //取备忘
                $mdata = $mlist->listData($uid);
                $tm = array();
                foreach($mdata['data'] as $k=>$v)
                {
                    if($v['is_notice'] == 1 && ($v['sorttmp'] > $current_first_day && $v['sorttmp'] < $current_last_day))//过滤掉不提醒的
                    {
                        $tm[$k]['deal_time'] = date('Y-n-j', $v['sorttmp']);
                        $tm[$k]['title'] =  '备忘录';
                        $tm[$k]['content'] = $v['notice_content'];
                        $tm[$k]['url'] = 'javascript:void(0);';
                        $tm[$k]['entity_id'] = $v['memo_id'];
                    }
                }
                //合并备忘和待办事项
               $date_info = array_merge($tm,$date_info);//P($date_info);
                if ($date_info) {
                    return $this->returnAjax(array('status' => 1, 'data' => $date_info));
                } else {
                    return $this->returnAjax(array('status' => 0, 'message' => '没有待办事项!1'));
                }
            }
            //当天的代办事项
            if (!empty($month_current_day)) {
                //当月第一天
                $current_first_day = strtotime($year_month);
                //当月最后一天
                $current_last_day = strtotime("$year_month +1 month -1 day");
                $date_info = $index_helper->getCurrentMonthBacklog($month_current_day, $end_day, $company_id, $this->user);
                foreach ($date_info as $key => $date) {
                    $date_info[$key]['deal_time'] = date('Y-n-j', $date['deal_time']);
                }
                //取备忘
                $mdata = $mlist->listData($uid);
                $tm = array();
                foreach($mdata['data'] as $k=>$v)
                {
                    if($v['is_notice'] == 1 && $v['sorttmp'] > $month_current_day && $v['sorttmp'] < $end_day)//过滤掉不提醒的
                    {
                        $tm[$k]['deal_time'] = date('Y-n-j', $v['sorttmp']);
                        $tm[$k]['title'] =  '备忘录';
                        $tm[$k]['content'] = $v['notice_content'];
                        $tm[$k]['url'] = '/index.php?c=memo&a=scanandedit';//'javascript:void(0);';
                        $tm[$k]['entity_id'] = $v['memo_id'];
                    }
                }
                //合并备忘和待办事项
               $date_info = array_merge($tm,$date_info);
                if ($date_info) {
                    return $this->returnAjax(array('status' => 1, 'data' => $date_info));
                } else {
                    return $this->returnAjax(array('status' => 0, 'message' => '没有待办事项!2'));
                }
            }
            if (!$year_month && !$month_current_day) {
                $index_model = new \Common\Model\Erp\Index();
                //取备忘
                $mdata = $mlist->listData($uid);
                //获取当天开始时间
                $start_day = strtotime(date('Y-m-d H:i:s',strtotime(date('Y-m-d'))));
                //当天结束时间
                $y = date('Y',$start_day);
                $m = date('m',$start_day);
                $d = date('d',$start_day);
                $end_day = mktime(23,59,59,$m,$d,$y);
                $tm = array();
                foreach($mdata['data'] as $k=>$v)
                {
                    if($v['is_notice'] == 1)//过滤掉不提醒的
                    {
                        $tm[$k]['deal_time'] = date('Y-n-j H:i:s', $v['sorttmp']);
                        $tm[$k]['title'] =  '备忘录';
                        $tm[$k]['content'] = $v['notice_content'];
                        $tm[$k]['url'] = '/index.php?c=memo&a=scanandedit';//'javascript:void(0);';
                        $tm[$k]['entity_id'] = $v['memo_id'];
                    }
                }
                $backlog_data = $index_model->getTotalBacklog($company_id, $this->user);
                foreach ($backlog_data as $key => $date) {
                    $backlog_data[$key]['deal_time'] = date('Y-n-j', $date['deal_time']);
                }
                $merge_backlog = array_merge($tm, $backlog_data);
                if ($merge_backlog) {
                    return $this->returnAjax(array('status' => 1, 'data' => $merge_backlog));
                } else {
                    return $this->returnAjax(array('status' => 0, 'message' => '没有待办提醒!'));
                }
            }
        }

        /**
         * 获取总的代办提醒
         */
        public function getTotalAction() {
            //取备忘
            $uid = $this->getUser()['user_id'];
            $company_id = $this->getUser()['company_id'];

            $mlist = new \App\Web\Helper\Memo();
            $mdata = $mlist->listData($uid);
            //获取当天开始时间
//            $start_day = strtotime(date('Y-m-d H:i:s',strtotime(date('Y-m-d'))));
            //当天结束时间
//            $y = date('Y',$start_day);
//            $m = date('m',$start_day);
//            $d = date('d',$start_day);
//            $end_day = mktime(23,59,59,$m,$d,$y);
            $tm = array();
            foreach($mdata['data'] as $k=>$v)
            {
                if($v['is_notice'] == 1)//过滤掉不提醒的
                {
                    $tm[$k]['deal_time'] = date('Y-n-j', $v['sorttmp']);
                    $tm[$k]['title'] =  '备忘录';
                    $tm[$k]['content'] = $v['notice_content'];
                    $tm[$k]['url'] = '/index.php?c=memo&a=scanandedit';//'javascript:void(0);';
                    $tm[$k]['entity_id'] = $v['memo_id'];
                    //$tm[$k]['memo_moment'] = $v['memo_moment'];
                }
            }
            $index_model = new \Common\Model\Erp\Index();
            $backlog_data = $index_model->getTotalBacklog($company_id, $this->user);
            foreach ($backlog_data as $key => $date) {
                $backlog_data[$key]['deal_time'] = date('Y-n-j', $date['deal_time']);
            }
            $merge_backlog = array_merge($tm, $backlog_data);
            $total_num = count($merge_backlog);
            if ($merge_backlog) {
                return $this->returnAjax(array('status' => 1, 'data' => $total_num));
            } else {
                return $this->returnAjax(array('status' => 0, 'data' => 0));
            }
        }
        /**
         * 版本弹框
         * 修改时间 2015年8月13日09:58:04
         * 
         * author ft
         */
        public function versionpopupAction() {
            if (Request::isGet()) {
                $user_id = I('get.user_id', 0);
                $pub_log_model = new \Common\Model\Erp\PubLog();
                $pub_log_push_model = new \Common\Model\Erp\PubLogPush();
                $sql = $pub_log_model->getSqlObject();
                $select = $sql->select(array('pl' => 'pub_log'));
                $select->columns(array('pub_log_id' => 'pub_log_id', 'title' => 'title', 'pub_date' => 'pub_date', 'content' => 'content'));
                $where = new \Zend\Db\Sql\Where();
                $where->greaterThan('pub_log_id', 0);
                $select->order('pub_log_id desc');
                $select->limit(1);
                $select->where($where);
                $pupop_info = $select->execute();
                if (!$pupop_info) {
                    return $this->returnAjax(array('status' => 0, 'data' => 0));
                }
                $pupop_info[0]['content'] = htmlspecialchars_decode($pupop_info[0]['content']);
                $pupop_info[0]['pub_date'] = date('Y-m-d', strtotime($pupop_info[0]['pub_date']));
                //查询是否弹过消息
                $push_data = $pub_log_push_model->getOne(array('user_id' => $user_id, 'pub_log_id' => $pupop_info[0]['pub_log_id']));
                if ($push_data) {
                    return $this->returnAjax(array('status' => 0, 'data' => 0));
                } else {
                    return $this->returnAjax(array('status' => 1, 'data' => $pupop_info[0]));
                }
            } else {
                $pub_log_push_info = $_POST;
                $pub_log_push_model = new \Common\Model\Erp\PubLogPush();
                $data = array('user_id' => $pub_log_push_info['user_id'], 'pub_log_id' => $pub_log_push_info['pub_log_id']);
                $pub_log_push_model->Transaction();
                $res = $pub_log_push_model->insert($data);
                if (!$res) {
                    $pub_log_push_model->rollback();
                    return $this->returnAjax(array('status' => 0, 'data' => '保存失败!'));
                } else {
                    $pub_log_push_model->commit();
                    return $this->returnAjax(array('status' => 1, 'data' => '保存成功!'));
                }
            }
        }
        //常见问题
        public function questionAction(){
        	$model = \Common\Model::getLink('box');
        	$select = $model->select(array('n'=>'news'));
        	$select->join(array('nc'=>'news_category'), 'n.category_id = nc.category_id',array('category_name'=>'name'));
        	$helper = $select->execute();
        	$category = array();
        	foreach ($helper as $value){
        		if(!isset($category[$value['category_id']])){
        			$category[$value['category_id']] = array('name'=>$value['category_name'],'category_id'=>$value['category_id'],'news'=>array());
        		}
        		$value['content'] = htmlspecialchars_decode($value['content']);
        		$category[$value['category_id']]['news'][] = $value;
        	}
        	unset($helper);
        	$this->assign('category', $category);
        	$data = $this->fetch();
        	return $this->returnAjax(array("status"=>1,
									   "tag_name"=>"常见问题",
									   "model_name"=>"index",
									   "model_href"=>Url::parse("index/question"),
        							   "model_js"=>'application_Js/index_question',
									   "data"=>$data,
										));
        }
        //历史版本
        public function versionsAction(){
        	$data = $this->fetch();
        	return $this->returnAjax(array("status"=>1,
									   "tag_name"=>"版本说明",
									   "model_name"=>"index",
									   "model_href"=>Url::parse("index/versions"),
									   "data"=>$data,
										));
        }
        //版本说明
        public function versionsexplainAction(){
            $pub_log_model = new \Common\Model\Erp\PubLog();
            $pub_log_data = $pub_log_model->getData(array(),array(),0,0,'pub_date desc');
            foreach ($pub_log_data as $key => $val) {
                $pub_log_data[$key]['content'] = htmlspecialchars_decode($val['content']);
            }
            $this->assign('pub_log_data', $pub_log_data);
        	$data = $this->fetch('versionsexplain');
        	return $this->returnAjax(array("status"=>1,
									   "tag_name"=>"版本说明",
									   "model_name"=>"index",
									   "model_href"=>Url::parse("index/versions"),
									   "data"=>$data,
										));
        }
        //关于九猪
        public function aboutjiuzAction(){
        	$data = $this->fetch();
        	return $this->returnAjax(array("status"=>1,
        			"tag_name"=>"关于九猪",
        			"model_name"=>"index",
        			"model_href"=>Url::parse("index/aboutjiuz"),
        			"data"=>$data,
        	));
        }
    }
