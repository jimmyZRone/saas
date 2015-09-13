<?php

    namespace App\Web\Mvc\Controller\Tenant;

    use App\Web\Helper\TenantContract;
    use App\Web\Lib\Request;
    use App\Web\Helper\Room;
    use App\Web\Helper\Url;
    use Common\Model\Erp\Tenant;
    use Common\Helper\ValidityVerification;
    use Common\Helper\String;
    use Common\Model\Erp\Rental;
    use App\Web\Helper\Reserve;
    use Common\Model\Erp\ContractRental;
    use Common\Model\Erp\Evaluate;
//use App\Web\Helper\PublicTools;
    use App\Web\Helper\Evaluation;
    use Common\Helper\Permissions;
    use Zend\Db\Sql\Expression;
    use Common\Helper\Permissions\Hook\SysHousingManagement;

//use App\Web\Helper\App\Web\Helper;
//use Common\Model\Erp\Common\Model\Erp;
    /**
     * 合同
     * @author too|最后修改时间 2015年4月17日 上午11:11:56
     */
    class IndexController extends \App\Web\Lib\Controller
    {

        protected $_auth_module_name = 'sys_tenant_management';

        /**
         * 合同列表 tenant_contract  rent_contract_extend contract_rental  rental评价租客
         * @author too|最后修改时间 2015年4月17日 上午11:06:41
         */
        public function indexAction()
        {
            if (!$this->verifyModulePermissions(Permissions::SELECT_AUTH_ACTION))
            {
                return $this->returnAjax(array('__status__' => 403 , '__closetag__' => Url::parse("Tenant-Index/index")));
            }
            $user = $this->user;
            //默认显示集中式还是分散式
            $default = [];
            $t = new TenantContract();
            $default = $t->getMaxHouseValue($user['company_id']);
            $house_type = Request::queryString('get.house_type',0);
            $house_type?$house_type:$default['house_type'];
            $view = Request::queryString('get.view_type' , 'default');
            $page = I('get.page' , 0);
            if ($page)
            {
                $t1 = new \App\Web\Helper\TenantContract();
                $start_dead_line = strtotime(Request::queryString('get.start_dead_line' , ''));
                $end_dead_line = Request::queryString('get.end_dead_line');
                $end_dead_line = !empty($end_dead_line) ? strtotime($end_dead_line) + 86400 : 0;
                $end_next_pay_time = Request::queryString('get.end_next_pay_time');
                $end_next_pay_time = !empty($end_next_pay_time) ? strtotime($end_next_pay_time) + 86400 : 0;

                $city_id = $user['city_id'];
                $company_id = $user['company_id'];
                //分散式合同
                $disperse_contract = $t1->getCityCommunityHouseContract($city_id , $company_id);
                $dis_contract_id = array_column($disperse_contract , 'contract_id');
                //集中式合同
                $flat_contract = $t1->getCityFlatRoomContract($city_id , $company_id);
                $flat_con_id = array_column($flat_contract , 'contract_id');
                $search = array(
                    'start_dead_line' => $start_dead_line , //到期时间1
                    'end_dead_line' => $end_dead_line , //到期时间2
                    'start_next_pay_time' => strtotime(Request::queryString('get.start_next_pay_time' , '')) , //下次交租时间1
                    'end_next_pay_time' => $end_next_pay_time , //下次交租时间2
                    'house_type' => Request::queryString('get.house_type' , '') , //1分布式 2集中式
                    'status' => Request::queryString('get.status' , '') , //0在住 1退租
                    'keywords' => Request::queryString('get.keywords' , '') , //租客电话 小区名 房间编号
                    'page' => Request::queryString('get.page' , '')//第一页
                );
                $page = !empty($search['page']) ? $search['page'] : 1;
                $size = 20;
                $list = $t1->getAllTenantContractPc($this->getUser()['company_id'] , $search , $page , $size , $user , $dis_contract_id , $flat_con_id,$house_type);//取出公司下所有合同
                //总条数  绑定给前端
                $this->assign('list' , $list['data']);
                if ($list['data'])
                {
                    $this->assign('view' , I('get.view_type' , 'data'));
                    $html = $this->fetch();
                    return $this->returnAjax(array("status" => 1 , "tag_name" => "租客管理" , "model_js" => "customer_manage" , "model_name" => "customer_manage" , "data" => $html , 'page' => $list['page']));
                }
                $this->assign('view' , I('get.view_type' , 'data'));
                $html = $this->fetch();
                return $this->returnAjax(array("status" => 0 , "tag_name" => "租客管理" , "model_js" => "customer_manage" , "model_name" => "customer_manage" , "data" => $html , 'page' => $list['page']));
            }
            $this->assign('view' , $view);
            $this->assign("user" , $user);
            $this->assign("default" , $default);
            $html = $this->fetch();
            return $this->returnAjax(array("status" => 1 , "tag_name" => "租客管理" , "model_js" => "customer_manage" , "model_name" => "customer_manage" , "data" => $html , 'page' => $list['page']));
        }

        /**
         * 添加合同tenant_contract   rental tenant三表
         * @author too|最后修改时间 2015年4月17日 上午11:06:03
         */
        public function addsAction()
        {
            if (!$this->verifyModulePermissions(Permissions::INSERT_AUTH_ACTION))
            {
                return $this->returnAjax(array('__status__' => 403 , '__closetag__' => Url::parse("Tenant-Index/adds")));
            }
            if (!Request::isPost())
            {
                $systemconfig = new \Common\Model\Erp\SystemConfig();
                $condata = $systemconfig->getFind($list = 'System' , $key = 'PayConfig');//计费方式 用于模板展示
                $sysSouce = $systemconfig->getFind($list = 'System' , $key = 'TrenchSouce');//来源渠道 用于模板展示
                $this->assign('sysconfig', $condata);//P($sysSouce);
                $this->assign('sysSouce' , $sysSouce);
                $ftnamemodel = new \Common\Model\Erp\FeeType();
                $ftname = $ftnamemodel->getData(array('company_id' => $this->getUser()['company_id'] , 'is_delete' => 0));//print_r($condata);
                $this->assign('ftname' , $ftname);//费用名 用于模板展示
                if (isset($_GET['house_type']))//从房源跳过来
                {
                    $get_house_type = Request::queryString('get.house_type' , '' , 'int');
                    $get_house_room_id = Request::queryString('get.house_room_id' , '' , 'int');
                    $reserve_id = Request::queryString("get.reserve_id" , 0 , "int");
                    if ($get_house_type != 1 && $get_house_type != 2)
                    {
                        return $this->returnAjax(array('status' => 0 , 'message' => '房源类型非法'));
                    }
                    if ($get_house_type == 2)//类型一 集中式
                    {
                        $house_id = 0;
                        $get_house_room_id = isset($_GET['house_room_id']) ? $_GET['house_room_id'] + 0 : 0;
                        if ($get_house_room_id == 0)
                        {
                            return $this->returnAjax(array('status' => 0 , 'message' => '房源id不能为空'));
                        }
                    }
                    else
                    {
                        $room_id = isset($_GET['room_id']) ? $_GET['room_id'] + 0 : false;
                        if ($room_id === false)
                        {
                            return $this->returnAjax(array('status' => 0 , 'message' => '房源id不能为空'));
                        }
                        elseif ($room_id > 0)
                        {//类型二 分散式合租
                            $house_id = $_GET['house_room_id'];
                            $get_house_room_id = $room_id;
                        }
                        elseif ($room_id == 0)
                        {//类型三 分散式整租
                            $house_id = isset($_GET['house_room_id']) ? $_GET['house_room_id'] + 0 : 0;
                            $get_house_room_id = $room_id;
                            if ($house_id == 0)
                            {
                                return $this->returnAjax(array('status' => 0 , 'message' => '房源id不能为空'));
                            }
                        }
                    }
                    $test = new Room();
                    $tmp_data = $test->getInfo($house_id , $get_house_room_id , $get_house_type , $this->getUser()['company_id'],$status=1);//用于模板展示

                    $feemodel = new \Common\Model\Erp\Fee();//取自带费用
                    $meterReadingHelper = new \Common\Helper\Erp\MeterReading();
                    if ($get_house_type == 2)
                    {
                        $type1 = 'SOURCE_FOCUS';//房源的费用
                        $type2 = 'SOURCE_FLAT';//公寓的费用
                        $flat_id = Request::queryString('get.flat_id' , 0 , 'int');
                        $room_fee = $feemodel->getFeeMany($type1 , $get_house_room_id);//用于模板展示
                        $flat_fee = $feemodel->getFeeMany($type2 , $flat_id);//用于模板展示
                        //这一步是为了兼容老数据
                        if (count($room_fee) < count($flat_fee))
                        {
                            if ($room_fee)
                            {
                                foreach ($room_fee as $key => $room)
                                {
                                    if ($room['fee_type_id'] != $flat_fee[$key]['fee_type_id'])
                                    {
                                        $flat_fee[] = $room;
                                    }
                                }
                            }
                        }
                        else
                        {
                            $flat_fee = $room_fee;
                        }
                        foreach ($flat_fee as $k => $v)
                        {
                            $flat_fee[$k]['payment_str'] = $condata[$v['payment_mode']];
                        }
                    }
                    else
                    {
                        if ($room_id > 0)//合租
                        {
                            //$type = 'SOURCE_DISPERSE_ROOM';
                            $type = 'SOURCE_DISPERSE';
                            $flat_fee = $feemodel->getFeeMany($type , $house_id);//用于模板展示
                            foreach ($flat_fee as $k => $v)
                            {
                                $m_data = $meterReadingHelper->getDataById($house_id , \Common\Model\Erp\MeterReading::HOUSE_TYPE_C , $v['fee_type_id']);
                                $flat_fee[$k]['payment_str'] = $condata[$v['payment_mode']];
                                $flat_fee[$k]['now_meter'] = $m_data['now_meter'];
                                $flat_fee[$k]['add_time'] = date("Y-m-d" , $m_data['add_time']);
                            }
                        }
                        elseif ($room_id == 0)//整租
                        {
                            $type = 'SOURCE_DISPERSE';
                            $get_house_room_id = $house_id;
                            $flat_fee = $feemodel->getFeeMany($type , $get_house_room_id);//用于模板展示
                            foreach ($flat_fee as $k => $v)
                            {
                                $m_data = $meterReadingHelper->getDataById($house_id , \Common\Model\Erp\MeterReading::HOUSE_TYPE_C , $v['fee_type_id']);
                                $flat_fee[$k]['payment_str'] = $condata[$v['payment_mode']];
                                $flat_fee[$k]['now_meter'] = empty($m_data) ? $v['money'] : $m_data['now_meter'];
                                $flat_fee[$k]['add_time'] = empty($m_data) ? date("Y-m-d" , $v['create_time']) : date("Y-m-d" , $m_data['add_time']);
                            }
                        }
                    }
                    $bet_arr = array("零" , "一" , "二" , "三" , "四" , "五" , "六" , "七" , "八" , "九" , "十" , "十一" , "十二");
                    $pay_arr = array("一" , "二" , "三" , "四" , "五" , "六" , "七" , "八" , "九" , "十" , "十一" , "十二");
                    $this->assign('bet_arr' , $bet_arr);
                    $detain_str = $bet_arr[$tmp_data[0]['detain']];
                    $pay_str = $bet_arr[$tmp_data[0]['pay']];
                    $this->assign('bet_arr' , $bet_arr);
                    $this->assign('pay_arr' , $pay_arr);
                    $this->assign('detain_str' , $detain_str);
                    $this->assign('pay_str' , $pay_str);
                    $this->assign('tmp_data' , $tmp_data[0]);
                    $this->assign('fee_data' , $flat_fee);//P($tmp_data[0]);
                }
                $bet_arr = array("零" , "一" , "二" , "三" , "四" , "五" , "六" , "七" , "八" , "九" , "十" , "十一" , "十二");
                $pay_arr = array("一" , "二" , "三" , "四" , "五" , "六" , "七" , "八" , "九" , "十" , "十一" , "十二");
                $this->assign('pay_arr' , $pay_arr);
                $this->assign('bet_arr' , $bet_arr);
                $html = $this->fetch('info');
                return $this->returnAjax(array("status" => 1 , "tag_name" => "添加合同" , "model_js" => "agrmt_detail" , "model_name" => "agrmt_detail" , "data" => $html));
            }
            //收集$data1 写进合同表{tenant_contract}
            $custom_number = Request::queryString('post.custom_number' , '');//合同编号
            $signing_time = strtotime(Request::queryString('post.signing_time' , time()));//签约时间
            $occupancy_time = Request::queryString('post.occupancy_time' , $signing_time);//入住时间
            $dead_line = strtotime(Request::queryString('post.dead_line' , 0));//合同到期日
            $end_line = Request::queryString('post.end_line' , $dead_line);//终止时间
            $detain = Request::queryString('post.detain' , 0);//押
            $pay = Request::queryString('post.pay' , 1);//付
            $rent = Request::queryString('post.rent' , 0);//租金
            $advance_time = Request::queryString('post.advance_time' , 0);//提前付款
            $deposit = Request::queryString('post.deposit' , 0);//押金
            $remark = Request::queryString('post.remark' , '');//合同备注
            $img = Request::queryString("post.photolist");
            $pay = $pay ? $pay : 1;
            if ($rent <= 0)
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '租金填写错误!'));
            }
            if (String::countStrLength($custom_number) > 40)
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '合同编号不能超过40个字符'));
            }
            if (String::countStrLength($remark) > 400)
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '备注不能超过400个字符'));
            }
            if ($signing_time > $dead_line)
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '合同周期不能小于1天'));
            }
            $detainarr = array(0 , 1 , 2 , 3 , 4 , 5 , 6 , 7 , 8 , 9 , 10 , 11 , 12);//用于判断用户输入是否合法
            $payarr = array(1 , 2 , 3 , 4 , 6 , 12);//用于判断用户输入是否合法
            if (!in_array($detain , $detainarr) && !in_array($pay , $payarr))
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '押付模式错误'));
            }
            //计算下次付款时间 下次付款金额
            $tmptime = date('Y-m-d' , $signing_time);
            $tmptimearr = explode('-' , $tmptime);
            $tmptimearr[1] = $tmptimearr[1] + $pay;


            $next_pay_time = mktime(0 , 0 , 0 , $tmptimearr[1] , $tmptimearr[2] , $tmptimearr[0]) - $advance_time * 86400;
            $next_pay_money = $rent * $pay;
            // 判断提前付款时间的合法区间
            if ((($next_pay_time - $signing_time) / 86400) < $advance_time)
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '提前付款天数不能大于支付周期时间'));
            }
            $data1 = array(
                'custom_number' => $custom_number , //合同编号
                'signing_time' => $signing_time , //签约时间
                'occupancy_time' => $occupancy_time , //入住时间,默认等于签约时间
                'dead_line' => $dead_line , //合同到期日
                'end_line' => $end_line , //终止时间，默认等于到期时间，手动终止后记录当前终止时间
                'detain' => $detain , //押
                'pay' => $pay , //付
                'rent' => $rent , //租金
                'advance_time' => $advance_time , //提前付款 默认15
                'deposit' => $deposit , //押金
                'remark' => $remark , //合同备注
                'is_settlement' => 0 , //$is_settlement,//是否结算1/是0/否
                'pay_num' => $pay , //支付月数,目前不知从哪里来     暂时取的付
                'next_pay_time' => $signing_time , //,//下次付款时间
                'next_pay_money' => $next_pay_money , //下次付款金额
                'total_money' => 50000 , //$total_money,//合同总金额
                'sale_money' => 1000 , //$sale_money,//优惠金额
                'is_renewal' => 2 , //是否是续约合同1/是；2/否
                'company_id' => $this->getUser()['company_id']
            );
            $t1 = new \App\Web\Helper\TenantContract();
            $t1->Transaction();
            $image = array();
            if ($img != '')
            {
                $image = explode("," , $img);
            }
            if (!$contract_id = $t1->addTenantContract($data1 , $image))
            {//合同id下面备用
                $t1->rollback();
                return $this->returnAjax(array('status' => 0 , 'message' => '合同添加失败'));
            }
            $add = new Tenant();
            $name = isset($_POST['name']) ? $_POST['name'] : '';
            $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
            $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
            $idcard = isset($_POST['idcard']) ? $_POST['idcard'] : '';
            $from = isset($_POST['from']) ? $_POST['from'] : 0;

            //接收传来的费用数组
            $fee_name = isset($_POST['fee_name']) ? $_POST['fee_name'] : 0;
            $fee_type = isset($_POST['fee_type']) ? $_POST['fee_type'] : 0;
            $fee_price = isset($_POST['fee_price']) ? $_POST['fee_price'] : '';
            $now_num = isset($_POST['now_num']) ? $_POST['now_num'] : 0;
            $record_time = isset($_POST['record_time']) ? $_POST['record_time'] : 0;
            $record_time = strtotime($record_time);
            $data = array();
            $data3 = array();
            $tenant_id = array();
            //多个租客,循环写入每一个
            $house_id = Request::queryString('post.house_id' , '');
            $room_id = Request::queryString('post.record_id' , '');//就是room_id
            $house_type = Request::queryString('post.house_type' , '');//区分集中2or分散1

            if (!empty($name))
            {
                if (count($name) > 5)
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '只能添加5个租客'));
                }
            }

            if (isset($_GET['house_type']))//从房源管理跳过来
            {
                $house_type = $_GET['house_type'] + 0;
                if ($_GET['house_type'] == 2)
                {
                    $roomFocusModel = new \Common\Model\Erp\RoomFocus();
                    $source = \Common\Model\Erp\Fee::SOURCE_FOCUS;
                    $house_id = 0;
                    $room_id = isset($_GET['room_id']) ? $_GET['room_id'] + 0 : 0;
                    $source_id = $room_id;
                    $room_focus_data = $roomFocusModel->getOne(array("room_focus_id" => $room_id));
                    $refresh_url = Url::parse("centralized-roomfocus/index/flat_id/" . $room_focus_data['flat_id']);
                }
                elseif ($_GET['house_type'] == 1)
                {
                    $refresh_url = Url::parse("house-house/index");
                    if ($_GET['room_id'] > 0)
                    {//分散式合租
                        $source = \Common\Model\Erp\Fee::SOURCE_DISPERSE_ROOM;
                        $rental_way = 1;
                        $house_id = $_GET['house_id'];//有值不为0
                        $room_id = $_GET['room_id'];
                        $source_id = $room_id;
                    }
                    if ($_GET['room_id'] == 0)
                    {//分散式整租
                        $source = \Common\Model\Erp\Fee::SOURCE_DISPERSE;
                        $rental_way = 2;
                        $house_id = $_GET['house_id'];
                        $room_id = 0;
                        $source_id = $house_id;
                    }
                }
            }
            else
            {
                $rental_way = I('post.rental_way' , 0);
                if ($house_type == 2)
                {
                    $source_id = $room_id;
                    $source = \Common\Model\Erp\Fee::SOURCE_FOCUS;
                }
                else
                {
                    if ($rental_way == 1)
                    {
                        $source_id = $room_id;
                        $source = \Common\Model\Erp\Fee::SOURCE_DISPERSE_ROOM;
                    }
                    elseif ($rental_way == 2)
                    {
                        $source_id = $house_id;
                        $source = \Common\Model\Erp\Fee::SOURCE_DISPERSE;
                    }
                }
            }
            $feemodel = new \Common\Model\Erp\Fee();
            $mermodel = new \Common\Model\Erp\MeterReading();
            $cr = new ContractRental();
            for ($i = 0 , $l = count($name); $i < $l; $i++)
            {
                if ($fee_name != 0 && $fee_type[$i] >= 3)//有费用切费用类型为345才可以
                {
                    $datameter[$i] = array(
                        'now_meter' => $now_num[$i] ,
                        'create_time' => $_SERVER['REQUEST_TIME'] ,
                        'add_time' => $record_time[$i] ,
                        'house_id' => $house_id ,
                        'room_id' => $room_id ,
                        'house_type' => $house_type ,
                        'fee_type_id' => $fee_name[$i] ,
                        'creat_user_id' => $this->getUser()['user_id'] ,
                    );
                    if ($now_num == 0)
                    {
                        unset($datameter[$i]['now_meter']);
                    }
                    if ($record_time == 0)
                    {
                        unset($datameter[$i]['add_time']);
                    }
                    if (!$mermodel->insert($datameter[$i]))
                    {
                        $t1->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '抄表数据添加失败'));
                    }
                }
                $company_id = $this->getUser()['company_id'];
                $data[$i] = array(
                    'name' => $name[$i] ,
                    'phone' => $phone[$i] ,
                    'gender' => $gender[$i] ,
                    'idcard' => $idcard[$i] ,
                    'birthday' => ValidityVerification::getIDCardInfo($idcard[$i])['birthday'] ? ValidityVerification::getIDCardInfo($idcard[$i])['birthday'] : 0 ,
                    'company_id' => $company_id ,
                    'from' => $from[$i] ,
                );
                if ($gender[$i] != 1 && $gender[$i] != 2)
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '请选择性别'));
                }
                if (empty($name[$i]))
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '填个名字呗'));
                }
                $s = $add->searchTenant($data[$i] , $company_id);//检查租客是否已存在
                if (!empty($s[0]))
                {//租客已存在就直接返回tenant_id给下面用
                    $tenant_id = $s[0]['tenant_id'];
                }
                else
                {
                    if (!$tenant_id = $add->addTenant($data[$i]))
                    {//租客不存在就新增租客,得来的tenant_id给下面用
                        $t1->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '租客添加失败'));
                    }
                }
                //循环写入
                $data3[$i] = array(
                    'tenant_id' => $tenant_id , //租客id
                    'contract_id' => $contract_id , //合同id
                    'creat_time' => time() , //创建时间
                    'is_delete' => 0//1删除 0未删除
                );

                if (!$cr->addContractRental($data3[$i]))
                {
                    $t1->rollback();
                    return $this->returnAjax(array('status' => 0 , 'message' => '合同租客对应表添加失败'));
                }
                if ($i == 0)
                {
                    //写第一个租客
                    $data4 = array(
                        'house_id' => $house_id ,
                        'room_id' => $room_id , //房源id  或者room_id
                        'tenant_id' => $tenant_id , //租客id
                        'contract_id' => $contract_id , //合同id
                        'house_type' => $house_type , //1分散 2集中
                        'is_delete' => 0 , //默认为0
                        //'source_id'=>来源id哈哈哈
                        'source' => '我是来源'//来源
                    );
                    $r = new Rental();
                    //这里用$house_id和$room_id去R表查询一下房源是否已经存在合同了
                    $is_rdata = $r->getOne(array('house_id' => $house_id , 'room_id' => $room_id , 'is_delete' => 0 , "is_stop" => 0));//P($is_rdata);P($house_id);die;
                    if (!empty($is_rdata))
                    {
                        $t1->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '同一房源不能重复添加合同'));
                    }
                    if (!$r->addRental($data4))
                    {
                        $t1->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '该房源已出租或未录入'));
                    }
                }
            }
            //修改对应房源的出租状态
            if ($house_type == 1)
            {
                //分散
                if ($rental_way != 1 && $rental_way != 2)
                {
                    $t1->rollback();
                    return $this->returnAjax(array('status' => 0 , 'message' => '出租类型非法'));
                }
                if ($rental_way == 1)
                {
                    $csmodel = new \Common\Model\Erp\Room();
                    $room_data = $csmodel->getOne(array("room_id" => $room_id));
                    $house_id = $room_data['house_id'];
                    //写快照
                    \Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_ROOM_RENTAL , $room_id , $room_data);

                    if (!$csmodel->changStatus($room_id , \Common\Model\Erp\Room::STATIS_RENTAL))
                    {
                        $t1->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '修改房间状态失败'));
                    }
                }
                else//整租
                {
                    $houseModel = new \Common\Model\Erp\HouseEntirel();
                    $house_data = $houseModel->getOne(array("house_id" => $room_id));
                    //写快照
                    \Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_HOUSE_RENTAL , $house_data['house_id'] , $house_data);

                    if (!$houseModel->changStatus($house_id , \Common\Model\Erp\Room::STATIS_RENTAL))
                    {
                        $t1->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '修改房间状态失败'));
                    }
                }
            }
            else
            {
                //集中
                $csmodel = new \Common\Model\Erp\RoomFocus();
                $focus_data = $csmodel->getOne(array("room_focus_id" => $room_id));
                $flat_id = $focus_data['flat_id'];
                //写快照
                \Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_FOCUS_RENTAL , $room_id , $focus_data);

                if (!$csmodel->changStatus($room_id , \Common\Model\Erp\Room::STATIS_RENTAL))
                {
                    $t1->rollback();
                    return $this->returnAjax(array('status' => 0 , 'message' => '修改房间状态失败'));
                }
            }
            // 再写一个合同到期日程
            $noticeTime = date('Y-m-d' , $dead_line);
            $hn = I('post.housename');
            $dataTodo = array(
                'module' => 'tenant_contract' , // 模块
                'entity_id' => $contract_id , // 实体id 合同id
                'title' => '到期' , // 标题 例如【合同到期】
                'content' => $hn . '的租客合同将于' . $noticeTime . '到期，请注意处理' , // 内容
                'company_id' => $company_id , // 公司id
                'url' => '/index.php?c=tenant-index&a=edit&contract_id=' . $contract_id , // 跳转地址
                'status' => 0 , // 状态 0/未处理,1/已查看,2/已处理
                'deal_time' => $dead_line , // 处理时间
                'create_time' => $_SERVER['REQUEST_TIME'] ,
                'create_uid' => $this->getUser()['user_id'] , // 创建人
                'house_id' => $flat_id > 0 ? 0 : $house_id ,
                'flat_id' => $flat_id > 0 ? $flat_id : 0 ,
            );
            $todoModel = new \Common\Model\Erp\Todo();
            if (!$todoModel->addTodo($dataTodo))
            {
                $t1->rollback();
                return $this->returnAjax(array('status' => 0 , 'message' => '写入日程失败'));
            }
            // 再来一个收租日程表
            $ntnext_pay_time = date('Y-m-d' , $signing_time);//默认是签约时间(需求变更)
            $dataTodoS = array(
                'module' => 'tenant_contract_shouzu' , // 模块
                'entity_id' => $contract_id , // 实体id 合同id
                'title' => '收租' , // 标题 例如【合同到期】
                'content' => $hn . '的租金' . $pay * $rent . '元' . '应于' . $ntnext_pay_time . '收取，请注意处理' , // 内容
                'company_id' => $company_id , // 公司id
                'url' => '/index.php?c=finance-serial&a=addincome&contract_id=' . $contract_id , // 跳转地址
                'status' => 0 , // 状态 0/未处理,1/已查看,2/已处理
                'deal_time' => $signing_time , // 处理时间
                'create_time' => $_SERVER['REQUEST_TIME'] ,
                'create_uid' => $this->getUser()['user_id'] , // 创建人
                'house_id' => $flat_id > 0 ? 0 : $house_id ,
                'flat_id' => $flat_id > 0 ? $flat_id : 0 ,
            );
            //发送系统消息
            $message = new \Common\Model\Erp\Message();
            $message_data['title'] = "房间出租";
            $message_data['to_user_id'] = $this->user['user_id'];
            $message_data['content'] = $hn . "已出租,出租时间" . $tmptime . "至" . date("Y-m-d" , $end_line);
            $message_data['message_type'] = "system";
            $message->sendMessage($message_data);

            if (!$todoModel->addTodo($dataTodoS))
            {
                $t1->rollback();
                return $this->returnAjax(array('status' => 0 , 'message' => '写入收租日程失败'));
            }
            $t1->commit();
            return $this->returnAjax(array('status' => 1 , 'message' => '添加合同成功' ,
                        'url' => Url::parse("Finance-serial/addIncome/source_type/$house_type/source/tenant_contract/source_id/$contract_id") ,
                        'tag' => Url::parse("tenant-index/index") ,
                        "refresh_url" => $refresh_url));
        }

        /**
         * 编辑合同  查看合同
         * @author too|最后修改时间 2015年4月17日 上午11:06:21
         */
        public function editAction()
        {
            if (!$this->verifyModulePermissions(Permissions::SELECT_AUTH_ACTION))
            {
                return $this->returnAjax(array('__status__' => 403 , '__closetag__' => Url::parse("Tenant-Index/edit")));
            }
            $contract_id = Request::queryString('get.contract_id' , '');//单个合同id
            //检查当前用户是否有该对象权限START
            $rentalHelper = new \Common\Helper\Erp\Rental();
            $tc = new \App\Web\Helper\TenantContract();
            $rental_data = $rentalHelper->getOneDateByContract($contract_id);
            if ($rental_data['house_type'] == \Common\Model\Erp\Rental::HOUSE_TYPE_F)
            {
                if (!$this->verifyDataLinePermissions(Permissions::SELECT_AUTH_ACTION , $rental_data["room_id"] , SysHousingManagement::CENTRALIZED))
                {
                    $status[] = 0;
                }
                else
                {
                    $status[] = 1;
                }
            }
            if ($rental_data['house_type'] == \Common\Model\Erp\Rental::HOUSE_TYPE_R)
            {
                if (!$this->verifyDataLinePermissions(Permissions::SELECT_AUTH_ACTION , $rental_data["house_id"] , SysHousingManagement::DECENTRALIZED_HOUSE))
                {
                    $status[] = 0;
                }
                else
                {
                    $status[] = 1;
                }
            }
            if ($this->user['is_manager'] == 0)
            {
                if (!in_array(1 , $status))
                {
                    return $this->returnAjax(array('__status__' => 403 , '__closetag__' => Url::parse("Tenant-Index/edit")));
                }
            }

            $tenant_id = isset($_POST['tenant_id']) ? $_POST['tenant_id'] : '';//租客id array
            $contract_rental_id = isset($_POST['contract_rental_id']) ? $_POST['contract_rental_id'] : '';//array类型
            $test = new \App\Web\Helper\FeeType();
            $ftname = $test->getFeeType($this->getUser()['company_id']);//模板展示费用名称
            $this->assign('ftname' , $ftname);
            $systemconfig = new \Common\Model\Erp\SystemConfig();
            $condata = $systemconfig->getFind($list = 'System' , $key = 'PayConfig');
            $sysSouce = $systemconfig->getFind($list = 'System' , $key = 'TrenchSouce');
            $this->assign('sysSouce' , $sysSouce);
            $this->assign('sysconfig' , $condata);//系统的计费方式用于模板展示
            if (empty($contract_id))
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '合同id有误'));
            }
            $input_tenant_id = $tenant_id;
            if (!Request::isPost())
            {
                $systemconfig = new \Common\Model\Erp\SystemConfig();
                $sysSouce = $systemconfig->getFind($list = 'System' , $key = 'TrenchSouce');//计费方式 用于模板展示
                $this->assign('sysSouce' , $sysSouce);
                $tcdata = $tc->getTenantContract($contract_id);//获取合同数据
                $feemodel = new \Common\Model\Erp\Fee();//取自带费用
                if ($tcdata[0]['r_house_type'] == 2)//
                {
                    $type = 'SOURCE_FOCUS';
                    $house_room_id = $tcdata[0]['r_room_id'];
                }
                else
                {
                    if ($tcdata[0]['r_room_id'] > 0)
                    {
                        $type = 'SOURCE_DISPERSE';
                        $house_room_id = $tcdata[0]['r_house_id'];
                    }
                    elseif ($tcdata[0]['r_room_id'] == 0)
                    {
                        $type = 'SOURCE_DISPERSE';
                        $house_room_id = $tcdata[0]['r_house_id'];
                    }
                }
                $fee_data = $feemodel->getFeeMany($type , $house_room_id);//用于模板展示
                $mermodel = new \Common\Model\Erp\MeterReading();
                $attachmentModel = new \Common\Model\Erp\Attachments();
                $houseModel = new \Common\Model\Erp\House();
                $imag_data = $attachmentModel->getData(array("module" => "tenant_contract" , "entity_id" => $contract_id));
                if ($tcdata[0]['r_house_type'] == 1)
                {
                    $merdata = $mermodel->getData(array('house_type' => $tcdata[0]['r_house_type'] , 'house_id' => $tcdata[0]['r_house_id']));
                	$house_data = $houseModel->getOne(array("house_id"=>$tcdata[0]['r_house_id']));
                	$tcdata[0]['house_name'] = $house_data['house_name'];
                }
                if ($tcdata[0]['r_house_type'] == 2)
                {
                    $merdata = $mermodel->getData(array('house_type' => $tcdata[0]['r_house_type'] , 'room_id' => $tcdata[0]['r_room_id']));
                }
                foreach ($fee_data as $k => $v)
                {
                    $fee_data[$k]['payment_str'] = $condata[$v['payment_mode']];
                    $fee_data[$k]['add_time'] = empty($merdata) ? date('Y-m-d' , $v['create_time']) : date('Y-m-d' , $merdata[$k]['add_time']);
                    $fee_data[$k]['now_meter'] = empty($merdata) ? $v['money'] : $merdata[$k]['now_meter'];
                }
                $this->assign('fee_data' , $fee_data);
                $alltenant = $tc->getAllTenant($contract_id);//获取所有租客
                $bet_arr = array("零" , "一" , "二" , "三" , "四" , "五" , "六" , "七" , "八" , "九" , "十" , "十一" , "十二");//print_r($tcdata);
                $this->assign('bet_arr' , $bet_arr);
                $detain_str = $bet_arr[$tcdata[0]['detain']];
                $pay_str = $bet_arr[$tcdata[0]['pay']];
                $tc_data = $tcdata[0];
                $this->assign('bet_arr' , $bet_arr);
                $this->assign('detain_str' , $detain_str);//P($alltenant);
                $this->assign('pay_str' , $pay_str);//P($tcdata[0]);P($alltenant);
                $this->assign('tcdata' , $tc_data);//所有合同主体数据
                $this->assign('alltenant' , $alltenant);//所有租客
                $this->assign("imag" , $imag_data);
                $html = $this->fetch('info');
                return $this->returnAjax(array("status" => 1 , "tag_name" => "合同详情" , "model_js" => "agrmt_detail" , "model_name" => "agrmt_detail" , "data" => $html));
            }
            if (!$this->verifyModulePermissions(Permissions::UPDATE_AUTH_ACTION))
            {
                return $this->returnAjax(array('__status__' => 403));
            }

            $img = Request::queryString("post.photolist");
            $remark = Request::queryString('post.remark' , '我是合同备注');//合同备注
            if (String::countStrLength($remark) > 400)
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '合同备注不能超过400个字符'));
            }
            $rmodel = new \Common\Model\Erp\Rental();
            //获取合同信息
            $signing_time = Request::queryString("signing_time",'','string');
            $pay = Request::queryString("pay",0,'int');
            $rent = Request::queryString("rent",0);
            $advance_time = Request::queryString("advance_time",0,"int");
            $custom_number = Request::queryString("custom_number",'',"string");
            $dead_line = Request::queryString("dead_line",'',"string");
            $deposit = Request::queryString("deposit",0);
            $detain = Request::queryString("detain",0);
            
            //计算下次付款时间 下次付款金额
            $tmptime = $signing_time;
            $tmptimearr = explode('-' , $tmptime);
            $tmptimearr[1] = $tmptimearr[1] + $pay;
            
            
            $next_pay_time = mktime(0 , 0 , 0 , $tmptimearr[1] , $tmptimearr[2] , $tmptimearr[0]) - $advance_time * 86400;
            $next_pay_money = $rent * $pay;
            // 判断提前付款时间的合法区间
            if ((($next_pay_time - $signing_time) / 86400) < $advance_time)
            {
            	return $this->returnAjax(array('status' => 0 , 'message' => '提前付款天数不能大于支付周期时间'));
            }
            $tenant_contract_data = $tc->getOne(array("contract_id"=>$contract_id));
            $data1 = array(
                'remark' => $remark , //合同备注
            );
           /*  if (strtotime($signing_time) != $tenant_contract_data['signing_time'] || 
            	$pay != $tenant_contract_data['pay'] ||
            	$detain != $tenant_contract_data['detain'] ||
            	$deposit != $tenant_contract_data['deposit'] ||
            	$rent != $tenant_contract_data['rent'] ||
            	$advance_time != $tenant_contract_data['advance_time'] ||
            	strtotime($dead_line) != $tenant_contract_data['dead_line']
            ){
            	$data1['next_pay_time'] = $next_pay_time;
            	$data1['next_pay_money'] = $next_pay_money;
            	$data1['signing_time'] = strtotime($signing_time);
            	$data1['pay'] = $pay;
            	$data1['detain'] = $detain;
            	$data1['deposit'] = $deposit;
            	$data1['rent'] = $rent;
            	$data1['advance_time'] = $advance_time;
            	$data1['custom_number'] = $custom_number;
            	$data1['dead_line'] = strtotime($dead_line);
            } */
            $t1 = new \App\Web\Helper\TenantContract();
            $attachmentModel = new \Common\Model\Erp\Attachments();
            $cr = new ContractRental();
            $t1->Transaction();
            //添加合同图片
            $image = array();
            if ($img != '')
            {
                $image = explode("," , $img);
            }
            $attachmentModel->delete(array("module" => "tenant_contract" , "entity_id" => $contract_id));
            if (!empty($image))
            {
                foreach ($image as $val)
                {
                    $img_data['key'] = $val;
                    $img_data['module'] = "tenant_contract";
                    $img_data['entity_id'] = $contract_id;
                    $attachmentModel->insertData($img_data);
                }
            }
            if (!$t1->editTenantContract($data1 , $contract_id , false))
            {//合同id下面备用
                $t1->rollback();
                return $this->returnAjax(array('status' => 0 , 'message' => '合同修改失败'));
            }
            $data = array();
            $data3 = array();
            $add = new Tenant();//echo 'pei';
            $name = isset($_POST['name']) ? $_POST['name'] : '';
            $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
            $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
            $idcard = isset($_POST['idcard']) ? $_POST['idcard'] : '';
            $from = isset($_POST['from']) ? $_POST['from'] : 0;
            //多个租客,循环写入每一个
            $company_id = $this->getUser()['company_id'];
            if (!empty($name))
            {
                if (count($name) > 5)
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '只能添加5个租客'));
                }
            }
            //获取合同全部租客
            $all_tenant_data = $tc->getAllTenant($contract_id);//获取合同数据
            //删除租客
            foreach ($all_tenant_data as $key => $val)
            {
                if (!in_array($val['tenant_id'] , $input_tenant_id))
                {
                    $tenantHelper = new \Common\Helper\Erp\Tenant();
                    $cr->edit(array("contract_id" => $contract_id , "tenant_id" => $val['tenant_id']) , array("is_delete" => 1));
                    $tenantHelper->deleteTenant($val['tenant_id']);
                }
            }
            for ($i = 0 , $l = count($name); $i < $l; $i++)
            {
                $birthday = ValidityVerification::getIDCardInfo($idcard[$i]);
                $data[$i] = array(
                    //'tenant_id'=>$tenant_id[$i],//编辑时传来租客id
                    'name' => $name[$i] ,
                    'phone' => $phone[$i] ,
                    'gender' => $gender[$i] ,
                    'idcard' => $idcard[$i] ,
                    'birthday' => $birthday ? $birthday['birthday'] : 0 ,
                    'company_id' => $company_id ,
                    'from' => $from[$i]
                );
                if ($gender[$i] != 1 && $gender[$i] != 2)
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '请填写性别'));
                }
                if (empty($name[$i]))
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '填个名字呗'));
                }
                $tenant_data = array();
                $tenant_data = $add->searchTenant($data[$i] , $company_id , $input_tenant_id[$i]);//检查租客是否已存在
                if (!empty($tenant_data))
                {//租客已存在 就直接编辑
                    $tenant_id = $tenant_data[0]['tenant_id'];//print_r($tenant_id);
                    $netid[$i] = $tenant_id;//放进数组里，下面判断rental表要用
                    if (!$add->editTenant($data[$i] , $tenant_data[0]['tenant_id']))
                    {//留下租客id下面用
                        $t1->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '租客修改失败'));
                    }
                    $data3[$i] = array(
                        'tenant_id' => $tenant_id , //租客id
                        'contract_id' => $contract_id , //合同id
                        'creat_time' => time() , //创建时间
                        'is_delete' => 0//1删除 0未删除
                    );
                    if (!$cr->editContractRental($data3[$i] , $contract_rental_id[$i]))
                    {
                        $t1->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '合同租客对应表修改失败'));
                    }
                }
                else
                {
                    if (!$tenant_id = $add->addTenant($data[$i]))
                    {
                        $t1->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '租客添加失败'));
                    }
                    $netid[$i] = $tenant_id;
                    $data3[$i] = array(
                        'tenant_id' => $tenant_id , //租客id
                        'contract_id' => $contract_id , //合同id
                        'creat_time' => time() , //创建时间
                        'is_delete' => 0//1删除 0未删除
                    );
                    $cr = new ContractRental();
                    if (!$cr->addContractRental($data3[$i]))
                    {
                        $t1->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '合同租客对应表修改失败'));
                    }
                }
                if ($i == end(array_keys($name)))
                {
                    $rtid = $rmodel->getOne(array('contract_id' => $contract_id));
                    $dataR = array(
                        'house_id' => $rtid['house_id'] ,
                        'room_id' => $rtid['room_id'] ,
                        'tenant_id' => $netid[0] ,
                        'contract_id' => $rtid['contract_id'] ,
                        'house_type' => $rtid['house_type'] ,
                        'source_id' => $rtid['source_id'] ,
                        'source' => $rtid['source']
                    );
                    /*  if(!in_array($rtid['tenant_id'],$netid))
                      {
                      if(!$rmodel->addRental($dataR))
                      {
                      $t1->rollback();
                      return $this->returnAjax(array('status'=>0,'message'=>'租住表替换失败'));
                      }
                      } */
                }
            }
            $contract_data = $tc->getOne(array("contract_id" => $contract_id));
            $t1->commit();

            //写快照
            \Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_CONTRACT_EIDT , $contract_id , $contract_data);

            return $this->returnAjax(array('status' => 1 , 'message' => '修改合同成功' , 'tag' => Url::parse("tenant-index/index")));
        }

        /**
         * 删除合同
         * 接收单个合同id
         * @author too|最后修改时间 2015年4月23日 下午5:25:05
         */
        public function deleAction()
        {
            if (!$this->verifyModulePermissions(Permissions::DELETE_AUTH_ACTION))
            {
                return $this->returnAjax(array('__status__' => 403));
            }
            $de = new \App\Web\Helper\TenantContract();
            $contract_id = Request::queryString('get.contract_id' , '');//获取待删除的合同ID
            if (empty($contract_id))
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '参数不能为空'));
            }
            $todoModel = new \Common\Model\Erp\Todo();
            $csmodel = new \App\Web\Helper\TenantContract();
            $userInfo = $this->getUser();
            $iddata = $csmodel->getTenantContract($contract_id);
            if ($iddata[0]['house_type'] == 1)
            {
                if ($iddata[0]['rental_way'] == 1)
                {
                    //分散合租 判断用户是否对当前对象有权限操作
                    if (!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION , $iddata[0]['r_house_id'] , SysHousingManagement::DECENTRALIZED_HOUSE))
                    {
                        return $this->returnAjax(array('__status__' => 403));
                    }
                }
                else
                {
                    //分散整租 判断用户是否对当前对象有权限操作
                    if (!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION , $iddata[0]['r_house_id'] , SysHousingManagement::DECENTRALIZED_HOUSE))
                    {
                        return $this->returnAjax(array('__status__' => 403));
                    }
                }
            }
            else
            {
                //集中式 验证有没有该房间权限
                if (!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION , $iddata[0]['r_room_id'] , SysHousingManagement::CENTRALIZED))
                {
                    return $this->returnAjax(array('__status__' => 403));
                }
            }

            $de->Transaction();
            if (strstr($contract_id , ','))
            {
                $tmp = explode(',' , $contract_id);
                foreach ($tmp as $v)
                {
                    if (empty($v))
                    {
                        return $this->returnAjax(array('status' => 0 , 'message' => '合同id有误'));
                    }
                    $contract_data = $de->getOne(array("contract_id" => $v));

                    //写快照
                    \Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_DELETE_CONTRACT , $v , $contract_data);

                    if (!$de->deleContract($v))
                    {
                        $de->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '合同删除失败'));
                    }
                    // 删除日程表 合同id 模块名 公司id 创建者id
                    $condition = array(
                        //'create_uid'=>$userInfo['user_id'],
                        'company_id' => $userInfo['company_id'] ,
                        'module' => 'tenant_contract' ,
                        'entity_id' => $v ,
                    );
                    $conditionS = array(
                        //'create_uid'=>$userInfo['user_id'],
                        'company_id' => $userInfo['company_id'] ,
                        'module' => 'tenant_contract_shouzu' ,
                        'entity_id' => $v ,
                    );
                    if (!$todoModel->delete($condition) || !$todoModel->delete($conditionS))
                    {
                        $de->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '日程删除失败'));
                    }
                    if ($iddata[0]['house_type'] == 1)
                    {
                        if ($iddata[0]['rental_way'] == 1)
                        {
                            //分散合租
                            $csmodel1 = new \Common\Model\Erp\Room();
                            //删除提醒START
                            $todoModel->deleteTodo($todoModel::MODEL_HOUSE_STOP , $iddata[0]['r_room_id']);
                            $todoModel->deleteTodo($todoModel::MODEL_HOUSE_RESERVE , $iddata[0]['r_room_id']);
                            $todoModel->deleteTodo($todoModel::MODEL_HOUSE_RESERVE_OUT , $iddata[0]['r_room_id']);
                            //删除提醒END
                            if (!$csmodel1->changStatus($iddata[0]['house_id']))
                            {
                                $de->rollback();
                                return $this->returnAjax(array('status' => 0 , 'message' => '修改房间状态失败'));
                            }
                        }
                        else
                        {
                            //分散整租
                            $houseModel = new \Common\Model\Erp\HouseEntirel();
                            //删除提醒START
                            $todoModel->deleteTodo($todoModel::MODEL_ROOM_STOP , $iddata[0]['r_house_id']);
                            $todoModel->deleteTodo($todoModel::MODEL_ROOM_RESERVE , $iddata[0]['r_house_id']);
                            $todoModel->deleteTodo($todoModel::MODEL_ROOM_RESERVE_OUT , $iddata[0]['r_house_id']);
                            //删除提醒END

                            if (!$houseModel->changStatus($iddata[0]['r_house_id']))
                            {
                                $de->rollback();
                                return $this->returnAjax(array('status' => 0 , 'message' => '修改房间状态失败'));
                            }
                        }
                    }
                    else
                    {
                        //集中
                        $csmodel1 = new \Common\Model\Erp\RoomFocus();
                        //删除提醒START
                        $todoModel->deleteTodo($todoModel::MODEL_ROOM_FOCUS_STOP , $iddata[0]['r_room_id']);
                        $todoModel->deleteTodo($todoModel::MODEL_ROOM_FOCUS_RESERVE , $iddata[0]['r_room_id']);
                        $todoModel->deleteTodo($todoModel::MODEL_ROOM_FOCUS_RESERVE_OUT , $iddata[0]['r_room_id']);
                        //删除提醒END
                        if (!$csmodel1->changStatus($iddata[0]['r_room_id']))
                        {
                            $de->rollback();
                            return $this->returnAjax(array('status' => 0 , 'message' => '修改房间状态失败'));
                        }
                    }
                }
                return $this->returnAjax(array('status' => 1 , 'message' => '合同删除成功'));
            }
            else
            {
                if (empty($contract_id))
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '合同id有误'));
                }
                if (!$de->deleContract($contract_id))
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '合同删除失败'));
                }
                // 删除日程表 合同id 模块名 公司id 创建者id
                $daoqi_condition = array(
                    'create_uid' => $userInfo['user_id'] ,
                    'company_id' => $userInfo['company_id'] ,
                    'module' => 'tenant_contract' ,
                    'entity_id' => $contract_id ,
                );
                $shouzu_condition = array(
                    'create_uid' => $userInfo['user_id'] ,
                    'company_id' => $userInfo['company_id'] ,
                    'module' => 'tenant_contract_shouzu' ,
                    'entity_id' => $contract_id ,
                );
                if (!$todoModel->delete($daoqi_condition) || !$todoModel->delete($shouzu_condition))
                {
                    $de->rollback();
                    return $this->returnAjax(array('status' => 0 , 'message' => '日程删除失败'));
                }
                //要把对应的房间状态改为1(未租)
                $csmodel = new \App\Web\Helper\TenantContract();
                $iddata = $csmodel->getTenantContract($contract_id);
                if ($iddata[0]['is_stop'] == 0)
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '删除失败合同未终止'));
                }
                if ($iddata[0]['house_type'] == 1)
                {
                    if ($iddata[0]['rental_way'] == 1)
                    {
                        //分散合租
                        $csmodel1 = new \Common\Model\Erp\Room();
                        //删除日志
                        $todoModel->delete(array("entity_id" => $iddata[0]['r_room_id'] , "module" => $todoModel::MODEL_ROOM_RESERVE));
                        $todoModel->delete(array("entity_id" => $iddata[0]['r_room_id'] , "module" => "tenant_contract"));
                        $todoModel->delete(array("entity_id" => $iddata[0]['r_room_id'] , "module" => "tenant_contract_shouzu"));
                        if (!$csmodel1->changStatus($iddata[0]['r_room_id']))
                        {
                            $de->rollback();
                            return $this->returnAjax(array('status' => 0 , 'message' => '修改房间状态失败'));
                        }
                    }
                    else
                    {
                        //分散整租
                        $csmodel1 = new \Common\Model\Erp\Room();
                        //删除日志
                        $todoModel->delete(array("entity_id" => $iddata[0]['r_house_id'] , "module" => $todoModel::MODEL_HOUSE_RESERVE));
                        $todoModel->delete(array("entity_id" => $iddata[0]['r_house_id'] , "module" => "tenant_contract"));
                        $todoModel->delete(array("entity_id" => $iddata[0]['r_house_id'] , "module" => "tenant_contract_shouzu"));
                        if (!$csmodel1->changStatus($iddata[0]['r_house_id']))
                        {
                            $de->rollback();
                            return $this->returnAjax(array('status' => 0 , 'message' => '修改房间状态失败'));
                        }
                    }
                }
                else
                {
                    //集中
                    $csmodel1 = new \Common\Model\Erp\RoomFocus();
                    //删除日志
                    $todoModel->delete(array("entity_id" => $iddata[0]['r_room_id'] , $todoModel::MODEL_ROOM_FOCUS_RESERVE));
                    $todoModel->delete(array("entity_id" => $iddata[0]['r_room_id'] , "module" => "tenant_contract"));
                    $todoModel->delete(array("entity_id" => $iddata[0]['r_room_id'] , "module" => "tenant_contract_shouzu"));
                    if (!$csmodel1->changStatus($iddata[0]['r_room_id']))
                    {
                        $de->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '修改房间状态失败'));
                    }
                }
                $de->commit();
                return $this->returnAjax(array('status' => 1 , 'message' => '合同删除成功'));
            }
        }

        /**
         * 预定转出租
         * @author too|最后修改时间 2015年5月4日 下午6:28:30
         */
        public function reservetoletAction()
        {
            if (!$this->verifyModulePermissions(Permissions::INSERT_AUTH_ACTION))
            {
                return $this->returnAjax(array('__status__' => 403 , '__closetag__' => Url::parse("Tenant-Index/reservetolet")));
            }
            $systemconfig = new \Common\Model\Erp\SystemConfig();
            $sys_config = $systemconfig->getFind($list = 'System' , $key = 'PayConfig');//计费方式 用于模板展示
            if (!Request::isPost())
            {
                $feemodel = new \Common\Model\Erp\Fee();//取自带费用
                if ($_GET['a'] == 'reservetolet')//如果是预定转出租
                {
                    $cid = $this->getUser()['company_id'];
                    $reserve_id = Request::queryString('get.reserve_id' , 0 , 'int');//接收参数
                    $model = new \App\Web\Helper\Reserve();
                    $onedata = $model->oneReserve(array('reserve_id' => $reserve_id));//获得此条预定信息 通过它去拿 房源信息和租客信息
                    $room = new Room();//获得房源信息
                    if ($onedata['house_type'] == 1)
                    {
                        //检查当前用户是否有该对象权限START
                        if (!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION , $onedata['house_id'] , SysHousingManagement::DECENTRALIZED_HOUSE))
                        {
                            return $this->returnAjax(array('__status__' => 403 , '__closetag__' => Url::parse("Tenant-Index/reservetolet")));
                        }
                        $meterReadingHelper = new \Common\Helper\Erp\MeterReading();
                        if ($onedata['rental_way'] == 1)
                        {
                            $data = $room->getInfo($onedata['house_id'] , $onedata['room_id'] , $onedata['house_type'] , $cid , '' , true);
                            $type = 'SOURCE_DISPERSE';
                            $fee_data = $feemodel->getFeeMany($type , $onedata['house_id']);//用于模板展示
                            foreach ($fee_data as $k => $v)
                            {
                                $m_data = $meterReadingHelper->getDataById($onedata['house_id'] , \Common\Model\Erp\MeterReading::HOUSE_TYPE_C , $v['fee_type_id']);
                                $fee_data[$k]['payment_str'] = $sys_config[$v['payment_mode']];
                                $fee_data[$k]['now_meter'] = $m_data['now_meter'];
                                $fee_data[$k]['add_time'] = date("Y-m-d" , $m_data['add_time']);
                            }
                            $this->assign('data' , $data[0]);
                        }
                        else
                        {
                            $data = $room->getInfo($onedata['house_id'] , $room_id = 0 , $onedata['house_type'] , $cid , '' , true);
                            $type = 'SOURCE_DISPERSE';
                            $get_house_room_id = $onedata['house_id'];
                            $fee_data = $feemodel->getFeeMany($type , $get_house_room_id);//用于模板展示
                            foreach ($fee_data as $k => $v)
                            {
                                $m_data = $meterReadingHelper->getDataById($onedata['house_id'] , \Common\Model\Erp\MeterReading::HOUSE_TYPE_C , $v['fee_type_id']);
                                $fee_data[$k]['payment_str'] = $sys_config[$v['payment_mode']];
                                $fee_data[$k]['now_meter'] = $m_data['now_meter'];
                                $fee_data[$k]['add_time'] = date("Y-m-d" , $m_data['add_time']);
                            }
                            $this->assign('data' , $data[0]);
                        }
                    }
                    if ($onedata['house_type'] == 2)
                    {
                        //检查当前用户是否有该对象权限START
                        if (!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION , $onedata['room_id'] , SysHousingManagement::CENTRALIZED))
                        {
                            return $this->returnAjax(array('__status__' => 403 , '__closetag__' => Url::parse("Tenant-Index/reservetolet")));
                        }
                        //检查当前用户是否有该对象权限END
                        $roomFocusModel = new \Common\Model\Erp\RoomFocus();
                        $data = $room->getInfo($house_id = 0 , $onedata['room_id'] , $onedata['house_type'] , $cid , '' , true);
                        $room_focus_data = $roomFocusModel->getOne(array("room_focus_id" => $data[0]['record_id']));
                        $type1 = 'SOURCE_FOCUS';//房源的费用
                        $type2 = 'SOURCE_FLAT';//公寓的费用
                        $fee_data1 = $feemodel->getFeeMany($type1 , $room_focus_data['room_focus_id']);//用于模板展示
                        $fee_data2 = $feemodel->getFeeMany($type2 , $room_focus_data['flat_id']);//用于模板展示
                        $fee_data = array_merge($fee_data1 , $fee_data2);
                        foreach ($fee_data as $k => $v)
                        {
                            $fee_data[$k]['payment_str'] = $sys_config[$v['payment_mode']];
                        }
                        $this->assign('data' , $data[0]);
                    }
                    $tenant = new \Common\Model\Erp\Tenant();
                    $tdata = $tenant->getTenant($onedata['tenant_id']);
                    $this->assign('tdata' , $tdata);
                    $this->assign('onedata' , $onedata);
                    $this->assign('fee_data' , $fee_data);
                    // print_r($onedata);die();
                }
                $test = new \App\Web\Helper\FeeType();
                $systemconfig = new \Common\Model\Erp\SystemConfig();
                $sysSouce = $systemconfig->getFind($list = 'System' , $key = 'TrenchSouce');
                $this->assign('sysSouce' , $sysSouce);
                $ft = $test->getFeeType($this->getUser()['company_id']);//模板展示费用名称
                $bet_arr = array("零" , "一" , "二" , "三" , "四" , "五" , "六" , "七" , "八" , "九" , "十" , "十一" , "十二");
                $pay_arr = array("一" , "二" , "三" , "四" , "五" , "六" , "七" , "八" , "九" , "十" , "十一" , "十二");
                $this->assign('pay_arr' , $pay_arr);
                $detain_str = $bet_arr[$data[0]['detain']];
                $pay_str = $bet_arr[$data[0]['pay']];
                $this->assign('bet_arr' , $bet_arr);
                $this->assign('detain_str' , $detain_str);
                $this->assign('pay_str' , $pay_str);
                $this->assign('ft' , $ft);
                $html = $this->fetch('info');
                return $this->returnAjax(array("status" => 1 , "tag_name" => "添加合同" , "model_js" => "agrmt_detail" , "model_name" => "agrmt_detail" , "data" => $html));
            }//echo 'xxxd';
            //收集$data1 写进合同表{tenant_contract}
            $custom_number = Request::queryString('post.custom_number' , '');//合同编号
            $signing_time = strtotime(Request::queryString('post.signing_time' , time()));//签约时间
            $occupancy_time = Request::queryString('post.occupancy_time' , $signing_time);//入住时间
            $dead_line = strtotime(Request::queryString('post.dead_line' , ''));//合同到期日
            $detain = Request::queryString('post.detain' , '');//押
            $pay = Request::queryString('post.pay' , '');//付
            $rent = Request::queryString('post.rent' , '');//租金
            $advance_time = Request::queryString('post.advance_time' , '');//提前付款
            $deposit = Request::queryString('post.deposit' , '');//押金
            $remark = Request::queryString('post.remark' , '我是默认的合同备注');//合同备注
            $imag = Request::queryString('post.photolist');//合同备注
            if ($rent <= 0)
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '租金填写错误!'));
            }
            if (String::countStrLength($custom_number) > 40)
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '合同编号不能超过40个字符'));
            }
            if (String::countStrLength($remark) > 40)
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '合同备注不能超过40个字符'));
            }
            //计算下次付款时间 下次付款金额
            $tmptime = date('Y-m-d' , $signing_time);
            $tmptimearr = explode('-' , $tmptime);
            $tmptimearr[1] = $tmptimearr[1] + $pay;
            $next_pay_time = mktime(0 , 0 , 0 , $tmptimearr[1] , $tmptimearr[2] , $tmptimearr[0]) - $advance_time * 86400;
            $next_pay_money = $rent * $pay;
            // 判断提前付款时间的合法区间
            if ((($next_pay_time - $signing_time) / 86400) < $advance_time)
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '提前付款天数不能大于支付周期时间'));
            }
            $data1 = array(
                'custom_number' => $custom_number , //合同编号
                'signing_time' => $signing_time , //签约时间
                'occupancy_time' => $occupancy_time , //入住时间,默认等于签约时间
                'dead_line' => $dead_line , //合同到期日
                'end_line' => $dead_line , //终止时间，默认等于到期时间，手动终止后记录当前终止时间
                'detain' => $detain , //押
                'pay' => $pay , //付
                'rent' => $rent , //租金
                'advance_time' => $advance_time , //提前付款 默认15
                'deposit' => $deposit , //押金
                'remark' => $remark , //合同备注
                'is_settlement' => 0 , //$is_settlement,//是否结算1/是0/否
                'pay_num' => $pay , //支付月数,目前不知从哪里来     暂时取的付
                'next_pay_time' => $signing_time , //time()+strtotime("+$pay month"),//下次付款时间      当前时间+付的时间
                'next_pay_money' => $next_pay_money , //time()+strtotime("+$pay month"),//下次付款时间      当前时间+付的时间
                'total_money' => 50000 , //$total_money,//合同总金额
                'sale_money' => 1000 , //$sale_money,//优惠金额
                'is_renewal' => 2 , //是否是续约合同1/是；2/否
                'company_id' => $this->getUser()['company_id']
            );
            if ($signing_time > $dead_line)
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '预定周期不能小于1天'));
            }
            $t1 = new \App\Web\Helper\TenantContract();
            $image = array();
            if ($imag != '')
            {
                $image = explode("," , $imag);
            }
            $t1->Transaction();
            if (!$new_contract_id = $t1->addTenantContract($data1 , $image))
            {//合同id下面备用
                $t1->rollback();
                return $this->returnAjax(array('status' => 0 , 'message' => '出租合同失败'));
            }
            $data = array();
            $data3 = array();
            //多个租客,循环写入每一个
            $house_id = Request::queryString('post.house_id' , '');//编辑的不行哟
            $room_id = Request::queryString('post.room_id' , '');//就是room_id 编辑的不行哟
            //$rental_way = Request::queryString('post.rental_way','');//就是分散式下面的整租or合租
            $house_type = Request::queryString('post.house_type' , '');//区分集中2or分散1   不给编辑哟

            $add = new Tenant();
            $name = isset($_POST['name']) ? $_POST['name'] : '';
            $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
            $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
            $idcard = isset($_POST['idcard']) ? $_POST['idcard'] : '';
            $tenant_id = isset($_POST['tenant_id']) ? $_POST['tenant_id'] : '';//租客id array array('53')
            $company_id = $this->getUser()['company_id'];

            if (!empty($name))
            {
                if (count($name) > 5)
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '只能添加5个租客'));
                }
            }
            for ($i = 0 , $l = count($name); $i < $l; $i++)
            {
                $data[$i] = array(
                    'name' => $name[$i] ,
                    'phone' => $phone[$i] ,
                    'gender' => $gender[$i] ,
                    'idcard' => $idcard[$i] ,
                    'birthday' => ValidityVerification::getIDCardInfo($idcard[$i])['birthday'] ? ValidityVerification::getIDCardInfo($idcard[$i])['birthday'] : 0 ,
                    "company_id" => $company_id
                );
                if ($gender[$i] != 1 && $gender[$i] != 2)
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '请选择性别'));
                }
                if (empty($name[$i]))
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '填个名字呗'));
                }

                $s = $add->searchTenant($data[$i] , $company_id);//检查租客是否已存在
                if (!empty($s[0]))
                {//租客已存在 就直接编辑
                    $tenant_id = $s[0]['tenant_id'];
                    if (!$add->editTenant($data[$i] , $s[0]['tenant_id']))
                    {//留下租客id下面用
                        $t1->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '租客修改失败'));
                    }
                    $data3[$i] = array(
                        'tenant_id' => $tenant_id , //租客id 目前是前台传来的
                        'contract_id' => $new_contract_id , //合同id 上面写入合同得来的
                        'creat_time' => time() , //创建时间
                        'is_delete' => 0//1删除 0未删除
                    );
                    $cr = new ContractRental();
                    if (!$cr->addContractRental($data3[$i]))
                    {
                        $t1->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '合同租客对应表出租失败'));
                    }
                }
                else
                {
                    if (!$tenant_id = $add->addTenant($data[$i]))
                    {
                        $t1->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '租客添加失败'));
                    }

                    //循环写入
                    $data3[$i] = array(
                        'tenant_id' => $tenant_id , //租客id 目前是前台传来的
                        'contract_id' => $new_contract_id , //合同id 上面写入合同得来的
                        'creat_time' => time() , //创建时间
                        'is_delete' => 0//1删除 0未删除
                    );
                    $cr = new ContractRental();
                    if (!$cr->addContractRental($data3[$i]))
                    {
                        $t1->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '合同租客对应表出租失败'));
                    }
                }
                if ($i == 0)
                {
                    //写第一个租客
                    $data4 = array(
                        'house_id' => $house_id ,
                        'room_id' => $room_id ,
                        'tenant_id' => $tenant_id , //租客id
                        'contract_id' => $new_contract_id , //合同id
                        'house_type' => $house_type , //1分散 2集中
                        'is_delete' => 0 , //默认为0
                        'source' => '我是续租来源'//来源
                    );
                    $r = new Rental();
                    //这里用$house_id和$room_id去R表查询一下
                    $is_rdata = $r->getOne(array('house_id' => $house_id , 'room_id' => $room_id , 'is_delete' => 0));
                    if (!empty($is_rdata))
                    {
                        $t1->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '同一房源不能重复添加合同'));
                    }
                    if (!$r->addRental($data4))
                    {
                        $t1->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '租住表修改失败'));
                    }
                }
            }
            //再把预定删除
            $reserve_id = Request::queryString('post.reserve_id' , '' , 'int');//接收参数
            $addR = new Reserve();
            $rdata = $addR->oneReserve(array('reserve_id' => $reserve_id));//取预定表
            $modelroom = new Room();
            if ($rdata['house_type'] == 1)
            {
                $roomdata = $modelroom->getInfo($rdata['house_id'] , $rdata['house_type'] , $this->getUser()['company_id']);
            }
            else
            {
                $roomdata = $modelroom->getInfo($rdata['room_id'] , $rdata['house_type'] , $this->getUser()['company_id']);
            }
            $rental_way = $rdata['rental_way'];//这四个修改表需要
            $house_type = $rdata['house_type'];
            $house_id = $rdata['house_id'];
            $room_id = $rdata['room_id'];
            if (!$addR->editReserve(array('is_delete' => 1) , $reserve_id))
            {
                $t1->rollback();
                return $this->returnAjax(array('status' => 0 , 'message' => '预定表状态删除失败'));
            }
            $todoModel = new \Common\Model\Erp\Todo();
            //获取全部的预定
            if ($house_type == 1)
            {
                if ($rental_way == 1)
                {
                    $all_reserve_data = $addR->getData(array('house_type' => $rdata['house_type'] , "room_id" => $rdata['room_id'] , "is_delete" => 0));
                    $model = new \Common\Model\Erp\Room();//合租
                    $house_model = new \Common\Model\Erp\House();
                    //获取房源数据STATR
                    $room_data = $model->getOne(array("room_id" => $room_id , "is_delete" => 0));
                    $house_data = $house_model->getOne(array("house_id" => $room_data['house_id'] , "is_delete" => 0));
                    $house_name = $house_data['house_name'] . $room_data['custom_number'];
                    //获取房源数据END
                    //删除预定备忘
                    $todoModel->deleteTodo($todoModel::MODEL_ROOM_RESERVE_OUT , $reserve_id);
                    $todoModel->deleteTodo($todoModel::MODEL_ROOM_STOP , $room_id);
                    if (empty($all_reserve_data))
                    {
                        $model->edit(array("room_id" => $room_id) , array("is_yd" => 0));
                    }
                    if (!$model->edit(array("room_id" => $room_id) , array("status" => \Common\Model\Erp\House::STATUS_IS_RENTAL)))
                    {
                        $t1->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '房源状态修改失败'));
                    }
                }
                else
                {
                    //获取房源数据STATR
                    $house_model = new \Common\Model\Erp\House();
                    $house_data = $house_model->getOne(array("house_id" => $house_id , "is_delete" => 0));
                    $house_name = $house_data['house_name'];
                    //获取房源数据END
                    $all_reserve_data = $addR->getData(array('house_type' => $rdata['house_type'] , "house_id" => $rdata['house_id'] , "is_delete" => 0));

                    //删除预定备忘
                    $todoModel->deleteTodo($todoModel::MODEL_HOUSE_RESERVE_OUT , $reserve_id);
                    $todoModel->deleteTodo($todoModel::MODEL_HOUSE_STOP , $house_id);
                    $model = new \Common\Model\Erp\HouseEntirel();//整租
                    if (empty($all_reserve_data))
                    {
                        $model->edit(array("house_id" => $house_id) , array("is_yd" => 0));
                    }
                    if (!$model->edit(array("house_id" => $house_id) , array("status" => \Common\Model\Erp\House::STATUS_IS_RENTAL)))
                    {
                        $t1->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '房源状态修改失败'));
                    }
                }
            }
            else
            {
                $all_reserve_data = $addR->getData(array('house_type' => $rdata['house_type'] , "room_id" => $rdata['room_id'] , "is_delete" => 0));
                //获取房源数据STATR
                $model = new \Common\Model\Erp\RoomFocus(); //集中式
                $flatModel = new \Common\Model\Erp\Flat();
                $room_focus_data = $model->getOne(array("room_focus_id" => $room_id));
                $flat_data = $flatModel->getOne(array("flat_id" => $room_focus_data['flat_id'] , "is_delete" => 0));
                $house_name = $flat_data['flat_name'] . $room_focus_data['floor'] . '楼' . $room_focus_data['custom_number'] . '号';
                //获取房源数据END
                //删除预定备忘
                $todoModel->deleteTodo($todoModel::MODEL_ROOM_FOCUS_RESERVE_OUT , $reserve_id);
                $todoModel->deleteTodo($todoModel::MODEL_ROOM_FOCUS_STOP , $room_id);
                if (empty($all_reserve_data))
                {
                    $model->edit(array("house_id" => $house_id) , array("is_yd" => 0));
                }
                if (!$model->edit(array("room_focus_id" => $room_id) , array("status" => $model::STATUS_RENTAL)))
                {
                    $t1->rollback();
                    return $this->returnAjax(array('status' => 0 , 'message' => '房源状态修改失败'));
                }
            }
            //修改对应房源的出租状态
            if ($house_type == 1)
            {
                //分散
                $refresh_url = Url::parse("house-house/index");
                if ($rental_way != 1 && $rental_way != 2)
                {
                    $t1->rollback();
                    return $this->returnAjax(array('status' => 0 , 'message' => '出租类型非法'));
                }
                if ($rental_way == 1)
                {
                    $csmodel = new \Common\Model\Erp\Room();

                    //写快照
                    \Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_ROOM_RENTAL , $room_id , $room_data);
                    $house_id = $room_data['house_id'];
                    if (!$csmodel->changStatus($room_id , \Common\Model\Erp\Room::STATIS_RENTAL))
                    {
                        $t1->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '修改房间状态失败'));
                    }
                }
                else//整租
                {
                    $houseModel = new \Common\Model\Erp\HouseEntirel();

                    //写快照
                    \Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_HOUSE_RENTAL , $house_id , $house_data);

                    if (!$houseModel->changStatus($house_id , \Common\Model\Erp\Room::STATIS_RENTAL))
                    {
                        $t1->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '修改房间状态失败'));
                    }
                }
            }
            else
            {
                //集中
                $csmodel = new \Common\Model\Erp\RoomFocus();
                $room_focus_data = $csmodel->getOne(array("room_focus_id" => $room_id));
                $flat_id = $room_focus_data['flat_id'];
                //写快照
                \Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_FOCUS_RENTAL , $room_id , $room_focus_data);

                $refresh_url = Url::parse("centralized-roomfocus/index/flat_id/" . $room_focus_data['flat_id']);
                if (!$csmodel->changStatus($room_id , \Common\Model\Erp\Room::STATIS_RENTAL))
                {
                    $t1->rollback();
                    return $this->returnAjax(array('status' => 0 , 'message' => '修改房间状态失败'));
                }
            }//die;
            //echo 'xxxd';
            //die;
            // 再写一个日程表嘞
            $noticeTime = date('Y-m-d' , $dead_line);
            $todoModel = new \Common\Model\Erp\Todo();
            $dataTodo = array(
                'module' => 'tenant_contract' , // 模块
                'entity_id' => $new_contract_id , // 实体id 合同id
                'title' => '到期' , // 标题 例如【合同到期】
                'content' => $house_name . '的租客合同将于' . $noticeTime . '到期，请注意处理' , // 内容
                'company_id' => $company_id , // 公司id
                'url' => '/index.php?c=tenant-index&a=edit&contract_id=' . $new_contract_id , // 跳转地址
                'status' => 0 , // 状态 0/未处理,1/已查看,2/已处理
                'deal_time' => $dead_line , // 处理时间
                'create_time' => $_SERVER['REQUEST_TIME'] ,
                'create_uid' => $this->getUser()['user_id'] , // 创建人
                'house_id' => $flat_id > 0 ? 0 : $house_id ,
                'flat_id' => $flat_id > 0 ? $flat_id : 0 ,
            );
            // 再来一个收租日程表
            $ntnext_pay_time = date('Y-m-d' , $next_pay_time);
            $dataTodoS = array(
                'module' => 'tenant_contract_shouzu' , // 模块
                'entity_id' => $new_contract_id , // 实体id 合同id
                'title' => '收租' , // 标题 例如【合同到期】
                'content' => $house_name . '的租金' . $pay * $rent . '元' . '应于' . $ntnext_pay_time . '收取，请注意处理' , // 内容
                'company_id' => $company_id , // 公司id
                'url' => '/index.php?c=finance-serial&a=addincome&contract_id=' . $new_contract_id , // 跳转地址
                'status' => 0 , // 状态 0/未处理,1/已查看,2/已处理
                'deal_time' => $next_pay_time , // 处理时间
                'create_time' => $_SERVER['REQUEST_TIME'] ,
                'create_uid' => $this->getUser()['user_id'] , // 创建人
                'house_id' => $flat_id > 0 ? 0 : $house_id ,
                'flat_id' => $flat_id > 0 ? $flat_id : 0 ,
            );
            if (!$todoModel->addTodo($dataTodoS))
            {
                $t1->rollback();
                return $this->returnAjax(array('status' => 0 , 'message' => '写入收租日程失败'));
            }
            // 删除预定日程
            $conditionS = array(
                //'create_uid'=>$userInfo['user_id'],
                'company_id' => $company_id ,
                'module' => 'reserve' ,
                'entity_id' => $reserve_id ,
            );

            if (!$todoModel->delete($conditionS))
            {
                $t1->rollback();
                return $this->returnAjax(array('status' => 0 , 'message' => '日程删除失败'));
            }
            if (!$todoModel->addTodo($dataTodo))
            {
                $t1->rollback();
                return $this->returnAjax(array('status' => 0 , 'message' => '写入日程失败'));
            }
            //发送系统消息
            $message = new \Common\Model\Erp\Message();
            $message_data['title'] = "房间出租";
            $message_data['to_user_id'] = $this->user['user_id'];
            $message_data['content'] = $house_name . "已出租,出租时间" . $tmptime . "至" . date("Y-m-d" , $dead_line);
            $message_data['message_type'] = "system";
            $message->sendMessage($message_data);
            $t1->commit();
            $source_category = ($house_type == 1) ? (($house_id > 0 && $room_id > 0) ? 'SOURCE_DISPERSE_ROOM' : 'SOURCE_DISPERSE') : 'SOURCE_FOCUS';
//         return $this->returnAjax(array('status'=>1,'message'=>'出租合同成功','tag'=>Url::parse("tenant-index/reserve")));
            return $this->returnAjax(array('status' => 1 ,
                        'message' => '出租合同成功' ,
                        'url' => Url::parse("finance-serial/addincome/source/tenant_contract/source_id/$new_contract_id/source_type/$house_type/source_category/$source_category") ,
                        'refresh_url' => $refresh_url
            ));
        }

        /**
         * 预定管理
         * @author too|最后修改时间 2015年4月20日 上午10:16:21
         */
        public function reserveAction()
        {
            if (!$this->verifyModulePermissions(Permissions::SELECT_AUTH_ACTION , 'sys_reservation_management'))
            {
                return $this->returnAjax(array('__status__' => 403 , '__closetag__' => Url::parse("Tenant-Index/reserve")));
            }
            $default = [];
            $t = new TenantContract();
            $default = $t->getMaxHouseValue($this->user['company_id']);
            $house_type = Request::queryString('get.house_type',0);
            $house_type = $house_type?$house_type:$default['house_type'];
            $r = new Reserve();
            $search = array(
                'stime' => Request::queryString('get.stime' , '') , //到期时间1
                'etime' => Request::queryString('get.etime' , '') , //到期时间2
                'house_type' => Request::queryString('get.house_type' , '') , //1分布式 2集中式
                'keywords' => Request::queryString('get.keywords' , '') , //租客电话 小区名 房间编号
                'page' => Request::queryString('get.page' , '') , //第一页
                'room_id' => I("get.room_id" , '' , 'int')//从房源跳过来才有的哟
            );
            $reserve_id = I('reserve_id' , 0);
            if($reserve_id){
                $reserve_model = new \Common\Model\Erp\Reserve();
                $reserve_helper = new \Common\Helper\Erp\Reserve();
                $reserve_data = $reserve_model->getOne(array('reserve_id' => $reserve_id));
                $house_type = $reserve_data['house_type'];
                if ($reserve_data['house_type'] == 1)
                {
                    $room_name = $reserve_helper->getFocusName($reserve_data);
                    $room_name[0]['rental_way'] = $reserve_data['rental_way'];
                    $room_name[0]['house_type'] = $reserve_data['house_type'];
                    if ($reserve_data['rental_way'] == 1)
                    {
                        $room_name[0]['room_type'] = ($room_name[0]['room_type'] == 'main') ? '主卧' : (($room_name[0]['room_type'] == 'second') ? '次卧' : (($room_name[0]['room_type'] == 'second') ? '客卧' : ''));
                    }
                }
                else
                {
                    $room_name = $reserve_helper->getFocusName($reserve_data);
                    $room_name[0]['house_type'] = $reserve_data['house_type'];
                }
                if (isset($room_name[0]['flat_name']))
                {
                    $key_word = $room_name[0]["flat_name"] . $room_name[0]['floor'] . "楼" . $room_name[0]['custom_number'] . "号";
                }
                $this->assign('room_name' , $room_name[0]);
            }

            $this->assign("key_word" , $key_word);
            $view = I('get.view_type' , 'template');
            $this->assign('view' , $view);
            $this->assign("user" , $this->user);
            $this->assign("house_type" , $house_type);

            if ($view != 'data')
            {
                $html = $this->fetch();
                return $this->returnAjax(array("status" => 1 , "tag_name" => "预定管理" , "model_js" => "customer_reserve_list" , "model_name" => "customer_reserve_list" , "data" => $html));
            }
            $user = $this->user;
            //查询当前城市的分散式合租的房间id
            $disperse_room = $r->getCityDisperseSharedRoomId($user['city_id'] , $user['company_id']);
            $disperse_room_id = array_column($disperse_room , 'room_id');
            //查询当前城市的分散式整租的房源id
            $disperse_house = $r->getCityDisperseHouseId($user['city_id'] , $user['company_id']);
            $disperse_house_id = array_column($disperse_house , 'house_id');
            //查询当前城市的集中式的房间id
            $focus_room = $r->getCityFocusRoomId($user['city_id'] , $user['company_id']);
            $focus_room_id = array_column($focus_room , 'room_focus_id');
            $id_arr = array(
                'disperse_room_id' => $disperse_room_id ,
                'disperse_house_id' => $disperse_house_id ,
                'focus_room_id' => $focus_room_id
            );
            $page = !empty($search['page']) ? $search['page'] : 1;
            $size = 20;


            $alldata = $r->getAllReserve($search , $user['company_id'] , $page , $size , $this->user , $id_arr,$house_type);
            $this->assign('alldata' , $alldata['data']);//模板循环
            $html = $this->fetch();
            if (empty($alldata['data']))
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '没有满足条件的内容' , "data" => $html));
            }
            else
            {
                return $this->returnAjax(array("status" => 1 , "data" => $html , 'page' => $alldata['page']));
            }
        }

        /**
         * 添加预定
         * 分添加租客 和 添加预定表 修改房源表字段rental_way 1合租 2整租
         * @author too|最后修改时间 2015年4月27日 下午4:13:35
         */
        public function addreserveAction()
        {
            if (!$this->verifyModulePermissions(Permissions::INSERT_AUTH_ACTION , 'sys_reservation_management'))
            {
                return $this->returnAjax(array('__status__' => 403));
            }
            $rental_way = Request::queryString('post.rental_way' , 0,"int");//租住方式1合租 2整租
            $idcard = Request::queryString('post.idcard' , '');//身份证号码
            $name = Request::queryString('post.name' , '');//租客姓名
            $phone = Request::queryString('post.phone' , '');//租客电话
            $money = Request::queryString('post.money' , '');//定金
            $stime = strtotime(Request::queryString('post.begin_date' , ''));//起始日期
            $etime = strtotime(Request::queryString('post.end_date' , ''));//终止日期
            $house_id = Request::queryString('post.house_id' , '');//房源id
            $room_id = Request::queryString('post.record_id' , '');//房间id
            $mark = Request::queryString('post.remark' , '我是默认的备注');//备注
            $pay_type = Request::queryString('post.ya' , '');//支付方式
            $source = Request::queryString('post.fu' , '');//来源渠道
            $house_type = Request::queryString('post.house_type' , '');//租客类型 1分散 2集中
            $addR = new Reserve();//拿房子id去取预定 ，取出已存在预定的结束时间 ，如果新的开始时间小于旧的结束时间，则不允许添加
            $rdata = $addR->getData(array('house_id' => $house_id , 'room_id' => $room_id , 'is_delete' => 0));
            if ($money <= 0)
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '订金填写错误!'));
            }
            foreach ($rdata as $v)
            {
                if ($stime >= $v['stime'] && $stime <= $v['etime'])
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '同一时间段不能多次预定'));
                }
                elseif ($stime <= $v['stime'] && $etime >= $v['stime'])
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '同一时间段不能多次预定'));
                }
                elseif ($stime >= $v['stime'] && $etime <= $v['etime'])
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '同一时间段不能多次预定'));
                }
                elseif ($stime <= $v['stime'] && $etime >= $v['etime'])
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '同一时间段不能多次预定'));
                }
            }
            if ($house_type != 1 && $house_type != 2)
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '房源类型不对'));
            }
            if (empty($money) || empty($stime) || empty($etime) || (empty($house_id) && empty($room_id)))
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '参数不能为空'));
            }
            if (empty($name))
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '姓名不正确'));
            }
            if (String::countStrLength($mark) > 255)
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '预订备注不能超过255个字符'));
            }
            $data_tenant = array(
                'idcard' => $idcard ,
                'name' => $name ,
                'phone' => $phone ,
                'birthday' => ValidityVerification::getIDCardInfo($idcard) ? ValidityVerification::getIDCardInfo($idcard) : 0 ,
                'gender' => 1 ,
                'company_id' => $this->getUser()['company_id']
            );
            $addR->Transaction();
            $T = new Tenant();
            $s = $T->searchTenant($data_tenant , $this->getUser()['company_id']);//检查租客是否已存在
            if (!empty($s[0]))
            {//租客已存在
                $tenant_id = $s[0]['tenant_id'];
            }
            else
            {
                if (!$tenant_id = $T->addTenant($data_tenant))
                {
                    $addR->rollback();
                    return $this->returnAjax(array('status' => 0 , 'message' => '预定人添加失败'));
                }
            }
            $todoModel = new \Common\Model\Erp\Todo();
            if ($house_type == 1)
            {
                if ($rental_way == 1)
                {
                    $model = new \Common\Model\Erp\Room();//合租
                    $todo_module = $todoModel::MODEL_ROOM_RESERVE_OUT;
                    $room_data = $model->getOne(array("room_id" => $room_id));

                    //写快照
                    \Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_ROOM_RESVER , $room_id , $room_data);

                    if (!$model->edit(array("house_id" => $house_id , 'room_id' => $room_id) , array('is_yd' => 1)))
                    {
                        $addR->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '房源状态修改失败'));
                    }
                }
                else
                {
                    $model = new \Common\Model\Erp\HouseEntirel();//整租
                    $todo_module = $todoModel::MODEL_HOUSE_RESERVE_OUT;
                    $house_data = $model->getOne(array("house_id" => $house_id));

                    //写快照
                    \Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_HOUSE_RESVER , $house_id , $house_data);

                    if (!$model->edit(array("house_id" => $house_id) , array('is_yd' => 1)))
                    {
                        $addR->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '房源状态修改失败'));
                    }
                }
            }
            else
            {
                $model = new \Common\Model\Erp\RoomFocus(); //集中式
                $todo_module = $todoModel::MODEL_ROOM_FOCUS_RESERVE_OUT;
                $focus_data = $model->getOne(array("room_focus_id" => $room_id));

                //写快照
                \Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_FOCUS_RESVER , $room_id , $focus_data);

                if (!$model->edit(array("room_focus_id" => $room_id) , array('is_yd' => 1)))
                {
                    $addR->rollback();
                    return $this->returnAjax(array('status' => 0 , 'message' => '房源状态修改失败'));
                }
            }
            $data_reserve = array(
                'tenant_id' => $tenant_id ,
                'house_id' => $house_id ,
                'room_id' => $room_id ,
                'rental_way' => $rental_way,
                'money' => $money ,
                'stime' => $stime ,
                'etime' => $etime ,
                'mark' => $mark ,
                'house_type' => $house_type ,
                'create_time' => $_SERVER['REQUEST_TIME'] ,
                'is_delete' => 0 , //0正常 1删除
                'source' => $source , //来源渠道
                'pay_type' => $pay_type//支付方式 1支付宝 2现金支付 3转账支付
            );
            if ($rental_way<=0){
            	unset($data_reserve['rental_way']);
            }
            if ($stime > $etime)
            {
                $addR->rollback();
                return $this->returnAjax(array('status' => 0 , 'message' => '预定周期不能小于1天'));
            }
            if ($data_reserve['house_type'] == 2)//集中式把house_id置为0
            {
                $data_reserve['house_id'] == 0;
            }
            if (!$reserve_id = $addR->addReserve($data_reserve))
            {
                $addR->rollback();
                return $this->returnAjax(array('status' => 0 , 'message' => '预定失败'));
            }

            // 再写一个合同到期日程表嘞
            $noticeTime = date('Y-m-d' , $etime);
            $hn = I('post.house_name');
            $dataTodo = array(
                'module' => $todo_module , // 模块
                'entity_id' => $reserve_id , // 实体id 合同id
                'title' => '到期' , // 标题 例如【合同到期】
                'content' => $hn . '的预定时间将于' . $noticeTime . '到期，请注意处理' , // 内容
                'company_id' => $this->getUser()['company_id'] , // 公司id
                'url' => Url::parse("tenant-index/reserve/reserve_id/{$reserve_id}") , // 跳转地址
                'status' => 0 , // 状态 0/未处理,1/已查看,2/已处理
                'deal_time' => $etime , // 处理时间
                'create_time' => $_SERVER['REQUEST_TIME'] ,
                'create_uid' => $this->getUser()['user_id'] , // 创建人
            );
            if (!$todoModel->addTodo($dataTodo))
            {
                $addR->rollback();
                return $this->returnAjax(array('status' => 0 , 'message' => '写入日程失败'));
            }
            //发送系统消息
            $message = new \Common\Model\Erp\Message();
            $message_data['to_user_id'] = $this->user['user_id'];
            $message_data['title'] = "房间预定";
            $message_data['content'] = $hn . "已预定,预定时间" . date("Y-m-d" , $stime) . "至" . date("Y-m-d" , $etime);
            $message_data['message_type'] = "system";
            $message->sendMessage($message_data);

            $addR->commit();
            return $this->returnAjax(array('status' => 1 , 'message' => '预定成功' , 'tag' => Url::parse("tenant-index/reserve") , 'url' => Url::parse("Finance-serial/addIncome/reserve_id/$reserve_id")));
        }

        /**
         * 查看预定详情  编辑预定内容
         * 修改房源表字段rental_way 1合租 2整租
         * @author too|最后修改时间 2015年5月5日 下午1:15:16
         */
        public function showreserveAction()
        {
            //查看
            if (!Request::isPost())
            {
                $reserve_id = Request::queryString('get.id' , '');//预定ID
                if (empty($reserve_id))
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '预定ID不对'));
                }
                $user = $this->user;
                $modelR = new Reserve();
                $rdata = $modelR->oneReserve(array('reserve_id' => $reserve_id));//取预定表
                $modelT = new Tenant();
                $tdata = $modelT->getTenant($rdata['tenant_id']);
                $modelroom = new Room();
                if ($rdata['house_type'] == 1)
                {
                    if ($rdata['rental_way'] == 1)//分散合租
                    {
                        $room_id = $rdata['room_id'];
                        $roomdata = $modelroom->getInfo($rdata['house_id'] , $room_id , $rdata['house_type'] , $this->getUser()['company_id'] , 'reserve');
                        if ($roomdata[0]['company_id'] !== $user['company_id'])
                        {
                            return $this->returnAjax(array('__status__' => 403));
                        }
                        if (!$this->verifyDataLinePermissions(Permissions::DELETE_AUTH_ACTION , $roomdata[0]['house_id'] , SysHousingManagement::DECENTRALIZED_HOUSE))
                        {
                            return $this->returnAjax(array('__status__' => 403));
                        }
                    }
                    else //分散整租
                    {
                        if (!$this->verifyDataLinePermissions(Permissions::DELETE_AUTH_ACTION , $rdata['house_id'] , SysHousingManagement::DECENTRALIZED_HOUSE))
                        {
                            return $this->returnAjax(array('__status__' => 403));
                        }
                        $room_id = 0;
                        $roomdata = $modelroom->getInfo($rdata['house_id'] , $room_id , $rdata['house_type'] , $this->getUser()['company_id'] , 'reserve');
                        if ($user['company_id'] !== $roomdata[0]['company_id'])
                        {
                            return $this->returnAjax(array('__status__' => 403));
                        }
                    }
                }
                else
                {
                    if (!$this->verifyDataLinePermissions(Permissions::DELETE_AUTH_ACTION , $rdata['room_id'] , SysHousingManagement::CENTRALIZED))
                    {
                        return $this->returnAjax(array('__status__' => 403));
                    }
                    $house_id = 0;
                    $roomdata = $modelroom->getInfo($house_id , $rdata['room_id'] , $rdata['house_type'] , $this->getUser()['company_id'] , 'reserve');
                    if ($user['company_id'] !== $roomdata[0]['company_id'])
                    {
                        return $this->returnAjax(array('__status__' => 403));
                    }
                }
                $enddata = array(
                    'house_name' => $roomdata[0]['house_name'] ,
                    'reserve_id' => $rdata['reserve_id'] ,
                    'tenant_id' => $tdata['tenant_id'] ,
                    'gender' => $tdata['gender'] ,
                    'tname' => $tdata['name'] ,
                    'tphone' => $tdata['phone'] ,
                    'tidcard' => $tdata['idcard'] ,
                    'money' => $rdata['money'] ,
                    'house_type' => $rdata['house_type'] ,
                    'room_id' => $rdata['room_id'] ,
                    'house_id' => $rdata['house_id'] ,
                    'stime' => date('Y-m-d' , $rdata['stime']) ,
                    'etime' => date('Y-m-d' , $rdata['etime']) ,
                    'mark' => $rdata['mark'] ,
                    //'house_name'=>$roomdata[0]['house_name'],
                    'source' => $rdata['source'] ,
                    'pay_type' => $rdata['pay_type'] ,
                    'rental_way' => $roomdata[0]['rental_way'] ,
                    'pay_type' => $rdata['pay_type'] ,
                    'source' => $rdata['source']
                );
                return $this->returnAjax(array('status' => 1 , 'data' => $enddata));
            }
            if (!$this->verifyModulePermissions(Permissions::UPDATE_AUTH_ACTION , 'sys_reservation_management'))
            {
                return $this->returnAjax(array('__status__' => 403));
            }
            $reserve_id = Request::queryString('post.reserve_id' , '');//预定ID
            $tenant_id = Request::queryString('post.tenant_id' , '');//租客ID
            if (empty($reserve_id) || empty($tenant_id))
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '预定ID/租客ID不对'));
            }
            //编辑
            $idcard = Request::queryString('post.idcard' , '');//身份证号码
            $name = Request::queryString('post.name' , '');//租客姓名
            $phone = Request::queryString('post.phone' , '');//租客电话

            $pay_type = Request::queryString('post.ya' , '');//支付方式
            $source = Request::queryString('post.fu' , '');//来源渠道
            //$money = Request::queryString('post.money','');//定金
            $stime = strtotime(Request::queryString('post.begin_date' , ''));//起始日期
            $etime = strtotime(Request::queryString('post.end_date' , ''));//终止日期
            //$house_id = Request::queryString('post.house_id','');//房源id
            //$room_id = Request::queryString('post.record_id','');//房间id
            $mark = Request::queryString('post.remark' , '我是默认的备注');//备注
            //$house_type = Request::queryString('post.house_type','');//租客类型 1分散 2集中
            if (String::countStrLength($mark) > 255)
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '预订备注不能超过255个字符'));
            }
            if (empty($stime) || empty($etime))
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '参数不能为空'));
            }
            if (empty($name))
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '姓名不正确'));
            }
            $maninfo = ValidityVerification::getIDCardInfo($idcard);//从身份证获取生日
            $data_tenant = array(
                'idcard' => $idcard ,
                'name' => $name ,
                'phone' => $phone ,
                'birthday' => $maninfo['birthday'] ? $maninfo['birthday'] : '' ,
                'gender' => $maninfo['gender'] ? $maninfo['gender'] : 0 ,
                'company_id' => $this->getUser()['company_id']
            );
            $addR = new Reserve();
            $addR->Transaction();
            $T = new Tenant();
            $s = $T->getTenant($tenant_id);//检查租客是否已存在
            if ($s)
            {//租客已存在
                if (!$T->editTenant($data_tenant , $tenant_id))
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '预订人编辑失败'));
                }
            }
            else
            {
                if (!$tenant_id = $T->addTenant($data_tenant))
                {
                    $addR->rollback();
                    return $this->returnAjax(array('status' => 0 , 'message' => '预订人添加失败'));
                }
            }
            $data_reserve = array(
                'tenant_id' => $tenant_id ,
                //'house_id'=>$house_id,
                //'room_id'=>$room_id,
                //'money'=>$money,
                'stime' => $stime ,
                'etime' => $etime ,
                'mark' => $mark ,
                //'house_type'=>$house_type,
                'create_time' => time() ,
                'is_delete' => 0 , //0正常 1删除
                'source' => $source ,
                'pay_type' => $pay_type
            );
            if ($stime > $etime)
            {
                $addR->rollback();
                return $this->returnAjax(array('status' => 0 , 'message' => '合同周期不能小于1天'));
            }
            if (!$addR->editReserve($data_reserve , $reserve_id))
            {
                $addR->rollback();
                return $this->returnAjax(array('status' => 0 , 'message' => '预订编辑失败'));
            }
            $reserve_data = $addR->getOne(array("reserve_id" => $reserve_id));
            //修改备忘START
            $todoModel = new \Common\Model\Erp\Todo();
            switch ($reserve_data['house_type'])
            {
                case 1:
                    if ($reserve_data['house_id'] > 0)
                    {
                        $todo_data = $todoModel->getOne(array("module" => $todoModel::MODEL_HOUSE_RESERVE_OUT , "entity_id" => $reserve_id));
                    }
                    if ($reserve_data['room_id'] > 0)
                    {
                        $todo_data = $todoModel->getOne(array("module" => $todoModel::MODEL_ROOM_RESERVE_OUT , "entity_id" => $reserve_id));
                    }
                    break;
                case 2:
                    $todo_data = $todoModel->getOne(array("module" => $todoModel::MODEL_ROOM_FOCUS_RESERVE_OUT , "entity_id" => $reserve_id));
                    break;
            }
            if (date("Y-m-d" , $etime) != date('Y-m-d' , strtotime('now')))
            {
                if (strpos($todo_data['content'] , "今天"))
                {
                    $content = str_replace("今天" , date("Y-m-d" , $etime) , $todo_data['content']);
                }
                else
                {
                    $content = str_replace(date("Y-m-d" , $todo_data['deal_time']) , date("Y-m-d" , $etime) , $todo_data['content']);
                }
            }
            else
            {
                if (!strpos($todo_data['content'] , "今天"))
                {
                    $content = str_replace(date("Y-m-d" , $todo_data['deal_time']) , date("Y-m-d" , $etime) , $todo_data['content']);
                }
                else
                {
                    $content = str_replace(date("Y-m-d" , $todo_data['deal_time']) , date("Y-m-d" , $etime) , $todo_data['content']);
                }
            }
            switch ($reserve_data['house_type'])
            {
                case 1:
                    if ($reserve_data['house_id'] > 0)
                    {
                        $todoModel->edit(array("module" => $todoModel::MODEL_HOUSE_RESERVE_OUT , "entity_id" => $reserve_id) , array("deal_time" => $etime , "content" => $content));
                    }
                    if ($reserve_data['room_id'] > 0)
                    {
                        $todoModel->edit(array("module" => $todoModel::MODEL_ROOM_RESERVE_OUT , "entity_id" => $reserve_id) , array("deal_time" => $etime , "content" => $content));
                    }
                    break;
                case 2:
                    $todoModel->edit(array("module" => $todoModel::MODEL_ROOM_FOCUS_RESERVE_OUT , "entity_id" => $reserve_id) , array("deal_time" => $etime , "content" => $content));
                    break;
            }
            //修改备忘END
            $addR->commit();
            return $this->returnAjax(array('status' => 1 , 'message' => '预订编辑成功' , 'tag' => Url::parse("tenant-index/reserve")));
        }

        /**
         * 删除预定 预定表is_delete置为1  房源的is_yd置为0
         * @author too|最后修改时间 2015年5月5日 下午3:26:39
         */
        public function delreserveAction()
        {
            if (!$this->verifyModulePermissions(Permissions::DELETE_AUTH_ACTION , 'sys_reservation_management'))
            {
                return $this->returnAjax(array('__status__' => 403));
            }
            $reserve_id = Request::queryString('post.reserve_id' , '');//预定ID
            $userInfo = $this->getUser();
            $addR = new Reserve();
            $rdata = $addR->oneReserve(array('reserve_id' => $reserve_id));//取预定表
            $modelroom = new Room();
            if ($rdata['house_type'] == 1)
            {
                //检查当前用户是否有该对象权限START
                if (!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION , $rdata['house_id'] , SysHousingManagement::DECENTRALIZED_HOUSE))
                {
                    return $this->returnAjax(array('__status__' => 403 , '__closetag__' => Url::parse("Tenant-Index/delreserve")));
                }
                //检查当前用户是否有该对象权限END
                if ($rdata['room_id'] > 0)
                {
                    $roomdata = $modelroom->getInfo($rdata['house_id'] , $rdata['room_id'] , $rdata['house_type'] , $this->getUser()['company_id']);
                }
                else
                {
                    $roomdata = $modelroom->getInfo($rdata['house_id'] , $rdata['room_id'] , $rdata['house_type'] , $this->getUser()['company_id']);
                }
            }
            else
            {
                //检查当前用户是否有该对象权限START
                if (!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION , $rdata['room_id'] , SysHousingManagement::CENTRALIZED))
                {
                    return $this->returnAjax(array('__status__' => 403 , '__closetag__' => Url::parse("Tenant-Index/delreserve")));
                }
                //检查当前用户是否有该对象权限END
                $roomdata = $modelroom->getInfo($rdata['room_id'] , $rdata['house_type'] , $this->getUser()['company_id']);
            }
            $rental_way = $rdata['rental_way'];//这四个修改表需要
            $house_type = $rdata['house_type'];
            $house_id = $rdata['house_id'];
            $room_id = $rdata['room_id'];


            $todoModel = new \Common\Model\Erp\Todo();
            $addR->Transaction();

            $condition = array(
                //'create_uid'=>$userInfo['user_id'],
                'company_id' => $userInfo['company_id'] ,
                'module' => 'reserve' ,
                'entity_id' => $reserve_id ,
            );
            if (!$todoModel->delete($condition))
            {
                $addR->rollback();
                return $this->returnAjax(array('status' => 0 , 'message' => '日程删除失败'));
            }

            if (!$addR->editReserve(array('is_delete' => 1) , $reserve_id))
            {
                $addR->rollback();
                return $this->returnAjax(array('status' => 0 , 'message' => '预定表状态删除失败'));
            }
            if ($house_type == 1)
            {
                if ($rental_way == 1)
                {
                    $all_reserve_data = $addR->getData(array('room_id' => $room_id , "is_delete" => 0 , "house_type" => 1));
                    $model = new \Common\Model\Erp\Room();//合租
                    $todoModel->deleteTodo($todoModel::MODEL_ROOM_RESERVE_OUT , $reserve_id);
                    if (empty($all_reserve_data))
                    {
                        if (!$model->edit(array("room_id" => $room_id) , array('is_yd' => 0)))
                        {
                            $addR->rollback();
                            return $this->returnAjax(array('status' => 0 , 'message' => '房源状态修改失败'));
                        }
                    }

                    //写快照
                    \Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_DELETE_RESERVE , $reserve_id , $rdata , "room" , $room_id);
                }
                else
                {
                    $model = new \Common\Model\Erp\HouseEntirel();//整租
                    $all_reserve_data = $addR->getData(array('house_id' => $house_id , "is_delete" => 0 , "house_type" => 1));
                    $todoModel->deleteTodo($todoModel::MODEL_HOUSE_RESERVE_OUT , $reserve_id);
                    if (empty($all_reserve_data))
                    {
                        if (!$model->edit(array("house_id" => $house_id) , array('is_yd' => 0)))
                        {
                            $addR->rollback();
                            return $this->returnAjax(array('status' => 0 , 'message' => '房源状态修改失败'));
                        }
                    }

                    //写快照
                    \Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_DELETE_RESERVE , $reserve_id , $rdata , "house" , $house_id);
                }
            }
            else
            {
                $model = new \Common\Model\Erp\RoomFocus(); //集中式
                $todoModel->deleteTodo($todoModel::MODEL_ROOM_FOCUS_RESERVE_OUT , $reserve_id);
                $all_reserve_data = $addR->getData(array('room_id' => $room_id , "is_delete" => 0 , "house_type" => 2));
                if (empty($all_reserve_data))
                {
                    if (!$model->edit(array("room_focus_id" => $room_id) , array('is_yd' => 0)))
                    {
                        $addR->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '房源状态修改失败'));
                    }
                }

                //写快照
                \Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_DELETE_RESERVE , $reserve_id , $rdata , "focus_room" , $room_id);
            }
            $addR->commit();
            return $this->returnAjax(array('status' => 1 , 'message' => '删除成功'));
        }

        /**
         * 退订预定 预定表is_delete置为1  房源的is_yd置为0
         * @author too|最后修改时间 2015年5月5日 下午3:29:34
         */
        public function debookAction()
        {
            $reserve_id = Request::queryString('post.reserve_id' , '');//预定ID
            $addR = new Reserve();
            $rdata = $addR->oneReserve(array('reserve_id' => $reserve_id));//取预定表
            $modelroom = new Room();
            if ($rdata['house_type'] == 1)
            {
                //检查当前用户是否有该对象权限START
                if (!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION , $rdata['house_id'] , SysHousingManagement::DECENTRALIZED_HOUSE))
                {
                    return $this->returnAjax(array('__status__' => 403));
                }

                $roomdata = $modelroom->getInfo($rdata['house_id'] , $rdata['room_id'] , $rdata['house_type'] , $this->getUser()['company_id'] , 'reserve');
            }
            else
            {
                //检查当前用户是否有该对象权限START
                if (!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION , $rdata['room_id'] , SysHousingManagement::CENTRALIZED))
                {
                    return $this->returnAjax(array('__status__' => 403));
                }

                $roomdata = $modelroom->getInfo($rdata['house_id'] , $rdata['room_id'] , $rdata['house_type'] , $this->getUser()['company_id'] , 'reserve');
            }
            $rental_way = $roomdata[0]['rental_way'];//这四个修改表需要
            $house_type = $rdata['house_type'];
            $house_id = $rdata['house_id'];
            $room_id = $rdata['room_id'];
            $addR->Transaction();//开始修改
            if (!$addR->editReserve(array('is_delete' => 1) , $reserve_id))
            {
                $addR->rollback();
                return $this->returnAjax(array('status' => 0 , 'message' => '预定表状态删除失败'));
            }//P($rental_way);
            $todoModel = new \Common\Model\Erp\Todo();
            if ($house_type == 1)
            {
                if ($rental_way == 1)
                {
                    $model = new \Common\Model\Erp\Room();//合租
                    $todoModel->deleteTodo($todoModel::MODEL_ROOM_RESERVE_OUT , $reserve_id);
                    if (!$model->edit(array("house_id" => $house_id , 'room_id' => $room_id) , array('is_yd' => 0)))
                    {
                        $addR->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '房源状态修改失败'));
                    }

                    //写快照
                    $room_data = $model->getOne(array("room_id" => $room_id));
                    \Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_ROOM_DELETE_RESVER , $room_id , $room_data , 'house' , $house_id);
                }
                else
                {
                    $model = new \Common\Model\Erp\HouseEntirel();//整租
                    $todoModel->deleteTodo($todoModel::MODEL_HOUSE_RESERVE_OUT , $reserve_id);
                    if (!$model->edit(array("house_id" => $house_id) , array('is_yd' => 0)))
                    {
                        $addR->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '房源状态修改失败'));
                    }

                    //写快照
                    $house_data = $model->getOne(array("house_id" => $house_id));
                    \Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_HOUSE_DELETE_RESVER , $house_id , $house_data);
                }
            }
            else
            {
                $model = new \Common\Model\Erp\RoomFocus(); //集中式
                $todoModel->deleteTodo($todoModel::MODEL_ROOM_FOCUS_RESERVE_OUT , $reserve_id);
                if (!$model->edit(array("room_focus_id" => $room_id) , array('is_yd' => 0)))
                {
                    $addR->rollback();
                    return $this->returnAjax(array('status' => 0 , 'message' => '房源状态修改失败'));
                }

                //写快照
                $focus_data = $model->getOne(array("room_focus_id" => $room_id));
                \Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_FOCUS_DELETE_RESVER , $room_id , $focus_data);
            }
            $addR->commit();
            return $this->returnAjax(array('status' => 1 , 'message' => '退定成功' , 'url' => Url::parse("Finance-serial/addexpense/us_source/unsubscribe/reserve_id/$reserve_id'
            ")));
        }

        /**
         * 评价合同
         * @author too|最后修改时间 2015年4月23日 下午5:42:11
         */
        public function commentAction()
        {
            $comment_data = $_POST;
            $tmp = $this->user;
            $data['user_id'] = $tmp['user_id'];//用户id
            $data['company_id'] = $tmp['company_id'];//公司id
            $data['rental_id'] = $comment_data['rental_id'][0];//租住编号(id)
            $data['create_time'] = time();
            $data['score'] = $comment_data['score'];//分数 后台去*20
            $data['reason'] = Request::queryString('post.reason' , '');//评分原因
            $data['remark'] = $comment_data['remark'];//评分备注  addEvaluate()

            if ($data['score'] <= 3 && empty($data['remark']))
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '评分小于等于三星时，必须填写备注内容'));
            }
            elseif ($data['score'] == 4 && empty($data['remark']))
            {
                $data['remark'] = '素质不错，一切OK';
            }
            elseif ($data['score'] == 5 && empty($data['remark']))
            {
                $data['remark'] = '中国好租客，合作很愉快';
            }
            $contract_id = Request::queryString('post.contract_id' , '');//分数 后台去*20
            $tc = new \App\Web\Helper\TenantContract();
            $tcdata = $tc->getTenantContract($contract_id);
            if ($tcdata[0]['dead_line'] > time() && $tcdata[0]['is_stop'] == 0)
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '合同未结束不能评价'));
            }
            if (!isset($data['score']))
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '请选择评价分数'));
            }
            if (empty($data['rental_id']) || empty($contract_id))
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '评价失败'));
            }
            $comment = new Evaluate();
            $comment->Transaction();
            if (!$comment->addEvaluate($data))
            {
                $comment->rollback();
                return $this->returnAjax(array('status' => 0 , 'message' => '评价失败'));
            }

            $data1 = array(
                'is_evaluate' => 1
            );
            if (!$tc->editTenantContract($data1 , $contract_id))
            {

                $comment->rollback();
                return $this->returnAjax(array('status' => 0 , 'message' => '评价失败'));
            }
            $comment->commit();
            return $this->returnAjax(array('status' => 1 , 'message' => '评价成功'));
        }

        /**
         * 合同续租
         * @author too|编写注释时间 2015年5月28日 下午1:27:31
         */
        public function reletAction()
        {
        	if (!$this->verifyModulePermissions(Permissions::INSERT_AUTH_ACTION))
        	{
        		return $this->returnAjax(array('__status__' => 403 , '__closetag__' => Url::parse("Tenant-Index/relet")));
        	}
            $contract_id = I('contract_id' , 0);
            $tc = new \App\Web\Helper\TenantContract();
            if (!Request::isPost())
            {
                //读取系统配置 计费方式
                $list = 'System';
                $key = 'PayConfig';
                $systemconfig = new \Common\Model\Erp\SystemConfig();
                $sysSouce = $systemconfig->getFind($list = 'System' , $key = 'TrenchSouce');
                $this->assign('sysSouce' , $sysSouce);
                $condata = $systemconfig->getFind($list , $key);
                //读取合同信息

                if (empty($contract_id))
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '父合同id有误'));
                }
                $tcdata = $tc->getTenantContract($contract_id);
                $tcdata[0]['dead_line'] = $tcdata[0]['dead_line'] + 86400;
                //取费用名
                $ftnamemodel = new \Common\Model\Erp\FeeType();
                $ftname = $ftnamemodel->getData(array('company_id' => $this->getUser()['company_id'] , 'is_delete' => 0));//print_r($condata);
                $this->assign('ftname' , $ftname);
                //取自带的费用
                if ($tcdata[0]['r_house_type'] == 2)//
                {
                    $type = 'SOURCE_FOCUS';
                    $house_room_id = $tcdata[0]['r_room_id'];
                }
                else
                {
                    if ($tcdata[0]['r_room_id'] > 0)
                    {
                        $type = 'SOURCE_DISPERSE_ROOM';
                        $house_room_id = $tcdata[0]['r_room_id'];
                    }
                    elseif ($tcdata[0]['r_room_id'] == 0)
                    {
                        $type = 'SOURCE_DISPERSE';
                        $house_room_id = $tcdata[0]['r_house_id'];
                    }
                }
                $feemodel = new \Common\Model\Erp\Fee();
                $fee_data = $feemodel->getFeeMany($type , $house_room_id);
                //取抄表数据，并合并到费用数组里
                $mermodel = new \Common\Model\Erp\MeterReading();
                $merdata = $mermodel->getData(array('house_type' => $tcdata[0]['r_house_type'] , 'house_id' => $tcdata[0]['r_house_id'] , 'room_id' => $tcdata[0]['r_room_id']));
                foreach ($fee_data as $k => $v)
                {
                    $fee_data[$k]['payment_str'] = $condata[$v['payment_mode']];
                    $fee_data[$k]['add_time'] = date('Y-m-d' , $merdata[$k]['add_time']);
                    $fee_data[$k]['now_meter'] = $merdata[$k]['now_meter'];
                }

                $this->assign('sysconfig' , $condata);
                $this->assign('fee_data' , $fee_data);
                $bet_arr = array("零" , "一" , "二" , "三" , "四" , "五" , "六" , "七" , "八" , "九" , "十" , "十一" , "十二");
                $pay_arr = array("一" , "二" , "三" , "四" , "五" , "六" , "七" , "八" , "九" , "十" , "十一" , "十二");
                $this->assign('pay_arr' , $pay_arr);
                $this->assign('bet_arr' , $bet_arr);
                $detain_str = $bet_arr[$tcdata[0]['detain']];
                $pay_str = $bet_arr[$tcdata[0]['pay']];
                $tc_data = $tcdata[0];
                $tc_data['max_time'] = date('Y-m-d' , strtotime('+1 day' , $tc_data['end_line']));
                $this->assign('bet_arr' , $bet_arr);
                $this->assign('detain_str' , $detain_str);
                $this->assign('pay_str' , $pay_str);
                $this->assign('tcdata' , $tc_data);//\P($tcdata[0]);
                //取租客信息
                $alltenant = $tc->getAllTenant($contract_id);
                $this->assign('alltenant' , $alltenant);
                $html = $this->fetch('info');
                return $this->returnAjax(array("status" => 1 , "tag_name" => "合同续租" , "model_js" => "agrmt_detail" , "model_name" => "agrmt_detail" , "data" => $html));
            }
            //收集$data1 写进合同表{tenant_contract}
            $custom_number = Request::queryString('post.custom_number' , '');//合同编号
            $signing_time = strtotime(Request::queryString('post.signing_time' , time()));//签约时间
            $occupancy_time = Request::queryString('post.occupancy_time' , $signing_time);//入住时间
            $dead_line = strtotime(Request::queryString('post.dead_line' , ''));//合同到期日
            //$end_line = Request::queryString('post.end_line',$dead_line);//终止时间
            $detain = Request::queryString('post.detain' , '');//押
            $pay = Request::queryString('post.pay' , '');//付
            $rent = Request::queryString('post.rent' , '');//租金
            $advance_time = Request::queryString('post.advance_time' , '');//提前付款
            $deposit = Request::queryString('post.deposit' , '');//押金
            $remark = Request::queryString('post.remark' , '我是默认的合同备注');//合同备注
            if ($rent <= 0)
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '租金填写错误!'));
            }
            if (String::countStrLength($custom_number) > 40)
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '合同编号不能超过40个字符'));
            }
            if (String::countStrLength($remark) > 400)
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '合同备注不能超过400个字符'));
            }
            if ($signing_time > $dead_line)
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '合同周期不能小于1天'));
            }
            $detainarr = array(0 , 1 , 2 , 3 , 4 , 5 , 6 , 7 , 8 , 9 , 10 , 11 , 12);//用于判断用户输入是否合法
            $payarr = array(1 , 2 , 3 , 4 , 6 , 12);//用于判断用户输入是否合法
            if (!in_array($detain , $detainarr) && !in_array($pay , $payarr))
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '押付模式错误'));
            }
            //计算下次付款时间 下次付款金额
            $tmptime = date('Y-m-d' , $signing_time);
            $tmptimearr = explode('-' , $tmptime);
            $tmptimearr[1] = $tmptimearr[1] + $pay;
            $next_pay_time = mktime(0 , 0 , 0 , $tmptimearr[1] , $tmptimearr[2] , $tmptimearr[0]) - $advance_time * 86400;
            $next_pay_money = $rent * $pay;
            // 判断提前付款时间的合法区间
            if ((($next_pay_time - $signing_time) / 86400) < $advance_time)
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '提前付款天数不能大于支付周期时间'));
            }
            $data1 = array(
                'custom_number' => $custom_number , //合同编号
                'signing_time' => $signing_time , //签约时间
                'occupancy_time' => $occupancy_time , //入住时间,默认等于签约时间
                'dead_line' => $dead_line , //合同到期日
                'end_line' => $dead_line , //终止时间，默认等于到期时间，手动终止后记录当前终止时间
                'detain' => $detain , //押
                'pay' => $pay , //付
                'rent' => $rent , //租金
                'advance_time' => $advance_time , //提前付款 默认15
                'deposit' => $deposit , //押金
                'remark' => $remark , //合同备注
                'is_settlement' => 0 , //$is_settlement,//是否结算1/是0/否
                'pay_num' => $pay , //支付月数,目前不知从哪里来     暂时取的付
                'next_pay_time' => $signing_time , //下次付款时间      当前时间+付的时间
                'next_pay_money' => $next_pay_money , //下次付款时间      当前时间+付的时间
                'total_money' => 50000 , //$total_money,//合同总金额
                'sale_money' => 1000 , //$sale_money,//优惠金额
                'is_renewal' => 1 , //是否是续约合同1/是；2/否
                'company_id' => $this->getUser()['company_id'] ,
                'parent_id' => $contract_id//合同父id
            );
            if ($signing_time > $dead_line)
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '合同周期不能小于1天'));
            }
            $t1 = new \App\Web\Helper\TenantContract();
            $t1->Transaction();
            if (!$new_contract_id = $t1->addTenantContract($data1))
            {//合同id下面备用
                $t1->rollback();
                return $this->returnAjax(array('status' => 0 , 'message' => '续租合同失败'));
            }
            if (!$t1->editTenantContract(array('is_haveson' => 1) , $contract_id))
            {
                $t1->rollback();
                return $this->returnAjax(array('status' => 0 , 'message' => '父合同修改失败'));
            }
            $data = array();
            $data3 = array();
            //多个租客,循环写入每一个
            $house_id = Request::queryString('post.house_id' , '');//编辑的不行哟
            $room_id = Request::queryString('post.room_id' , '');//就是room_id 编辑的不行哟
            $house_type = Request::queryString('post.house_type' , '');//区分集中2or分散1   不给编辑哟

            $add = new Tenant();
            $name = isset($_POST['name']) ? $_POST['name'] : '';
            $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
            $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
            $idcard = isset($_POST['idcard']) ? $_POST['idcard'] : '';
            $tenant_id = isset($_POST['tenant_id']) ? $_POST['tenant_id'] : '';//租客id array array('53')

            if (!empty($name))
            {
                if (count($name) > 5)
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '只能添加5个租客'));
                }
            }

            //接收传来的费用数组
            /* fee_name[]费用名称
              fee_type[]计费方式
              fee_price[]收费单价fee
              now_num[]当前底度 meter_reading
              record_time[]抄表时间  meter_reading */
            $fee_name = isset($_POST['fee_name']) ? $_POST['fee_name'] : 0;
            $fee_type = isset($_POST['fee_type']) ? $_POST['fee_type'] : 0;
            $fee_price = isset($_POST['fee_price']) ? $_POST['fee_price'] : '';
            $now_num = isset($_POST['now_num']) ? $_POST['now_num'] : 0;
            $record_time = isset($_POST['record_time']) ? $_POST['record_time'] : 0;
            $record_time = strtotime($record_time);
            $data = array();
            $data3 = array();
            $tenant_id = array();
            $feemodel = new \Common\Model\Erp\Fee();
            $mermodel = new \Common\Model\Erp\MeterReading();
            $cr = new ContractRental();
            $company_id = $this->getUser()['company_id'];
            for ($i = 0 , $l = count($name); $i < $l; $i++)
            {
                if ($fee_name != 0 && $fee_type[$i] >= 3)//有费用切费用类型为345才可以
                {
                    $datameter[$i] = array(
                        'now_meter' => $now_num[$i] ,
                        'create_time' => $_SERVER['REQUEST_TIME'] ,
                        'add_time' => $record_time[$i] ,
                        'house_id' => $house_id ,
                        'room_id' => $room_id ,
                        'house_type' => $house_type ,
                        'fee_type_id' => $fee_name[$i] ,
                        'creat_user_id' => $this->getUser()['user_id'] ,
                    );
                    if ($now_num == 0)
                    {
                        unset($datameter[$i]['now_meter']);
                    }
                    if ($record_time == 0)
                    {
                        unset($datameter[$i]['add_time']);
                    }
                    if (!$mermodel->insert($datameter[$i]))
                    {
                        $t1->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '抄表信息添加失败'));
                    }
                }
                $data[$i] = array(
                    'name' => $name[$i] ,
                    'phone' => $phone[$i] ,
                    'gender' => $gender[$i] ,
                    'idcard' => $idcard[$i] ,
                    'birthday' => ValidityVerification::getIDCardInfo($idcard[$i])['birthday'] ? ValidityVerification::getIDCardInfo($idcard[$i])['birthday'] : 0 ,
                    'company_id' => $company_id
                );
                if ($gender[$i] != 1 && $gender[$i] != 2)
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '性别错乱啦'));
                }
                if (empty($name[$i]))
                {
                    return $this->returnAjax(array('status' => 0 , 'message' => '填个名字呗'));
                }

                $s = $add->searchTenant($data[$i] , $this->getUser()['company_id']);//检查租客是否已存在
                if (!empty($s[0]))
                {//租客已存在 就直接编辑
                    $tenant_id = $s[0]['tenant_id'];//echo '租客存在';echo $tenant_id;
                    if (!$add->editTenant($data[$i] , $s[0]['tenant_id']))
                    {//留下租客id下面用
                        $t1->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '租客修改失败'));
                    }
                }
                else
                {
                    if (!$tenant_id = $add->addTenant($data[$i]))
                    {
                        $t1->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '租客添加失败'));
                    }
                    //循环写入
                    $data3[$i] = array(
                        'tenant_id' => $tenant_id , //租客id 目前是前台传来的
                        'contract_id' => $new_contract_id , //合同id 上面写入合同得来的
                        'creat_time' => time() , //创建时间
                        'is_delete' => 0//1删除 0未删除
                    );
                    if (!$cr->addContractRental($data3[$i]))
                    {
                        $t1->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '合同租客对应表续租失败'));
                    }
                }
                if ($i == 0)
                {
                    //写第一个租客
                    $data4 = array(
                        'house_id' => $house_id ,
                        'room_id' => $room_id ,
                        'tenant_id' => $tenant_id , //租客id
                        'contract_id' => $new_contract_id , //合同id
                        'house_type' => $house_type , //1分散 2集中
                        'is_delete' => 0 , //默认为0
                        'source_id' => $new_contract_id , //合同id哈哈哈
                        'source' => '我是续租来源'//来源
                    );
                    $r = new Rental();
                    if (!$r->addRental($data4))
                    {
                        $t1->rollback();
                        return $this->returnAjax(array('status' => 0 , 'message' => '续租失败'));
                    }
                }
            }
            $userInfo = $this->getUser();
            // 删除父合同的日程
            $condition = array(
                //'create_uid'=>$userInfo['user_id'],
                'company_id' => $userInfo['company_id'] ,
                'module' => 'tenant_contract' ,
                'entity_id' => $contract_id ,
            );
            $todoModel = new \Common\Model\Erp\Todo();
            $contractModel = new \Common\Model\Erp\TenantContract();
            $contract_data = $contractModel->getOne(array("contract_id" => $contract_id));
            if ($contract_data['next_pay_time'] >= $contract_data['end_line'])
            {
                if (!$todoModel->delete($condition))
                {
                    $t1->rollback();
                    return $this->returnAjax(array('status' => 0 , 'message' => '日程删除失败'));
                }
            }
            // 删除父合同收租日程
            $conditionS = array(
                //'create_uid'=>$userInfo['user_id'],
                'company_id' => $userInfo['company_id'] ,
                'module' => 'tenant_contract_shouzu' ,
                'entity_id' => $contract_id ,
            );
            if ($contract_data['next_pay_time'] >= $contract_data['end_line'])
            {
                if (!$todoModel->delete($conditionS))
                {
                    $t1->rollback();
                    return $this->returnAjax(array('status' => 0 , 'message' => '日程删除失败'));
                }
            }
            $tcdata = $tc->getTenantContract($contract_id);
            if ($house_type == 1)
            {
                if ($room_id > 0)
                {
                    $roomModel = new \Common\Model\Erp\Room();
                    $room_data = $roomModel->getOne(array("room_id" => $room_id));
                    $house_id = $room_data['house_id'];
                }
            }
            if ($house_type == 2)
            {
                $roomFocusModel = new \Common\Model\Erp\RoomFocus();
                $room_focus_data = $roomFocusModel->getOne(array("room_focus_id" => $room_id));
                $flat_id = $room_focus_data['flat_id'];
            }
            // 再写一个日程表嘞
            $noticeTime = date('Y-m-d' , $dead_line);
            $dataTodo = array(
                'module' => 'tenant_contract' , // 模块
                'entity_id' => $new_contract_id , // 实体id 合同id
                'title' => '到期' , // 标题 例如【合同到期】
                'content' => $tcdata[0]['house_name'] . '的租客合同将于' . $noticeTime . '到期，请注意处理' , // 内容
                'company_id' => $company_id , // 公司id
                'url' => '/index.php?c=tenant-index&a=edit&contract_id=' . $new_contract_id , // 跳转地址
                'status' => 0 , // 状态 0/未处理,1/已查看,2/已处理
                'deal_time' => $dead_line , // 处理时间
                'create_time' => $_SERVER['REQUEST_TIME'] ,
                'create_uid' => $userInfo['user_id'] , // 创建人
                'house_id' => $flat_id > 0 ? 0 : $house_id ,
                'flat_id' => $flat_id > 0 ? $flat_id : 0 ,
            );
            if ($contract_data['next_pay_time'] >= $contract_data['end_line'])
            {
                if (!$todoModel->addTodo($dataTodo))
                {
                    $t1->rollback();
                    return $this->returnAjax(array('status' => 0 , 'message' => '写入日程失败'));
                }
            }
            // 再来一个收租日程表
            $ntnext_pay_time = date('Y-m-d' , $next_pay_time);
            $dataTodoS = array(
                'module' => 'tenant_contract_shouzu' , // 模块
                'entity_id' => $new_contract_id , // 实体id 合同id
                'title' => '收租' , // 标题 例如【合同到期】
                'content' => $tcdata[0]['house_name'] . '的租金' . $pay * $rent . '元' . '应于' . $ntnext_pay_time . '收取，请注意处理' , // 内容
                'company_id' => $company_id , // 公司id
                'url' => '/index.php?c=finance-serial&a=addincome&contract_id=' . $new_contract_id , // 跳转地址
                'status' => 0 , // 状态 0/未处理,1/已查看,2/已处理
                'deal_time' => $next_pay_time , // 处理时间
                'create_time' => $_SERVER['REQUEST_TIME'] ,
                'create_uid' => $userInfo['user_id'] , // 创建人
                'house_id' => $flat_id > 0 ? 0 : $house_id ,
                'flat_id' => $flat_id > 0 ? $flat_id : 0 ,
            );
            if ($contract_data['next_pay_time'] >= $contract_data['end_line'])
            {
                if (!$todoModel->addTodo($dataTodoS))
                {
                    $t1->rollback();
                    return $this->returnAjax(array('status' => 0 , 'message' => '写入收租日程失败'));
                }
            }
            $t1->commit();

            //写快照
            \Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_CONTRACT_RELET , $contract_id , $contract_data , "contract" , $new_contract_id);

            return $this->returnAjax(array('status' => 1 , 'message' => '续租合同成功' ,
                        'url' => Url::parse("Finance-serial/addIncome/source_type/$house_type/source/tenant_contract/source_id/$new_contract_id/relet/1") ,
                        'contract_url' => Url::parse("tenant-index/index")));
        }

        /**
         * 合同终止 tc.is_settlement置为1 tc.end_line置为当前时间
         * @author too|最后修改时间 2015年4月27日 下午3:26:17
         */
        public function stopAction()
        {
            $contract_id = Request::queryString('get.contract_id' , 0);
            if (empty($contract_id))
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '合同id错误'));
            }
            $t1 = new \App\Web\Helper\TenantContract();
            $contractModel = new \Common\Model\Erp\TenantContract();
            $contract_data = $contractModel->getOne(array("contract_id" => $contract_id));
            $data = array(
                'is_stop' => 1 ,
                'end_line' => $_SERVER['REQUEST_TIME'] ,
                'next_pay_time' => $_SERVER['REQUEST_TIME']
            );
            // 删除日程表 合同id 模块名 公司id 创建者id
            $userInfo = $this->getUser();
            $condition = array(
                'company_id' => $userInfo['company_id'] ,
                'module' => 'tenant_contract' ,
                'entity_id' => $contract_id ,
            );
            $conditionS = array(
                'company_id' => $userInfo['company_id'] ,
                'module' => 'tenant_contract_shouzu' ,
                'entity_id' => $contract_id ,
            );
            $todoModel = new \Common\Model\Erp\Todo();
            $t1->Transaction();
            if (!$todoModel->delete($condition) || !$todoModel->delete($conditionS))
            {
                $t1->rollback();
                return $this->returnAjax(array('status' => 0 , 'message' => '日程删除失败'));
            }
            if (!$t1->editTenantContract($data , $contract_id , true))
            {
                $t1->rollback();
                return $this->returnAjax(array('status' => 0 , 'message' => '终止合同失败'));
            }
            else
            {
                $t1->commit();

                //写快照
                \Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_STOP_CONTRACT , $contract_id , $contract_data);

                return $this->returnAjax(array('status' => 1 , 'message' => '终止合同成功'));
            }
        }

        /**
         * 合同详情输入框搜索房源接口
         * @author too|最后修改时间 2015年4月21日 上午10:54:00
         */
        public function getinfoAction()
        {
            $systemconfig = new \Common\Model\Erp\SystemConfig();
            $condata = $systemconfig->getFind($list = 'System' , $key = 'PayConfig');//计费方式 用于模板展示
            $test = new Room();
            $search = Request::queryString('get.search' , '');
            $status = Request::queryString('get.status' , false);
            if (empty($search))
            {
                return $this->returnAjax(array('status' => 1 , 'room' => array()));
            }
            $room = $test->getRoomData($this->user , $search,$status);//传当前用户所属公司id
            //P($room);
//取费用
            $feemodel = new \Common\Model\Erp\Fee();//取自带费用
            if ($room[0]['house_type'] == 2)
            {
                $room[0]['record_id'];
                $type1 = 'SOURCE_FOCUS';//房源的费用
                $type2 = 'SOURCE_FLAT';//公寓的费用
                $ftmpmodel = new \Common\Model\Erp\RoomFocus();

                $flat_id = $ftmpmodel->getOne(array('room_focus_id' => $room[0]['record_id']));//P($flat_id['flat_id']);
                $fee_data1 = $feemodel->getFeeMany($type1 , $room[0]['record_id']);//用于模板展示
                $fee_data2 = $feemodel->getFeeMany($type2 , $flat_id['flat_id']);//用于模板展示
                $fee_data = array_merge($fee_data1 , $fee_data2);
                foreach ($fee_data as $k => $v)
                {
                    $fee_data[$k]['payment_str'] = $condata[$v['payment_mode']];
                }
                //P($fee_data);
            }
            else
            {
                if ($room[0]['record_id'] > 0)
                {
                    $type = 'SOURCE_DISPERSE';
                    $fee_data = $feemodel->getFeeMany($type , $room[0]['house_id']);//用于模板展示
                    foreach ($fee_data as $k => $v)
                    {
                        $fee_data[$k]['payment_str'] = $condata[$v['payment_mode']];
                    }
                }
                elseif ($room[0]['record_id'] == 0)
                {
                    $type = 'SOURCE_DISPERSE';
                    //$get_house_room_id = $house_id;
                    $fee_data = $feemodel->getFeeMany($type , $room[0]['house_id']);//用于模板展示
                    foreach ($fee_data as $k => $v)
                    {
                        $fee_data[$k]['payment_str'] = $condata[$v['payment_mode']];
                    }
                }
            }


            $bet_arr = array("零" , "一" , "二" , "三" , "四" , "五" , "六" , "七" , "八" , "九" , "十" , "十一" , "十二");
            foreach ($room as $k => $v)
            {
                $room[$k]['Adetain'] = $bet_arr[$v['detain']];
                $room[$k]['Apay'] = $bet_arr[$v['pay']];
            }
            if (!empty($fee_data))
            {
                $room[0]['feeabout'] = $fee_data;
            }//P($room);
            if (empty($room))
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '房源不存在'));
            }
            else
            {
                return $this->returnAjax(array('status' => 1 , 'room' => $room));
            }
        }

        /**
         * 通过身份证号码 取用户平均评价分数(前台用) 全局搜索不区分公司
         * @author too|最后修改时间 2015年5月5日 下午4:15:42
         */
        public function getscoreAction()
        {//echo '5456454';
            $idcard = Request::queryString('post.idcard' , '');//接收身份证号码
            $test = new Tenant();
            $tdata = $test->checkTenantfromidcard($idcard);
            $model = new Evaluation();
            $result = $model->getAvgScores($idcard);
            if (!empty($result))
            {
                return $this->returnAjax(array('status' => 1 , 'avgscore' => $result , 'tdata' => $tdata));
            }
            return $this->returnAjax(array('status' => 0 , 'avgscore' => array()));
        }

        /**
         * 取得所有评价带分页
         * @author too|最后修改时间 2015年5月5日 下午4:14:57
         */
        public function getcommentAction()
        {
            $idcard = Request::queryString('get.idcard' , '');//接收身份证号码
            $page = Request::queryString('get.current_page' , '');//接收身份证号码
            $model = new Evaluation();
            $size = 20;//每页显示20条
            $result = $model->getComment($idcard , $this->getUser()['company_id'] , $page , $size);
            foreach ($result['data'] as $k => $v)
            {
                $result['data'][$k]['create_time'] = date('Y-m-d' , $v['create_time']);
            }
            if (!empty($result))
            {
                return $this->returnAjax(array('status' => 1 , 'data' => $result));
            }
            return $this->returnAjax(array('status' => 0 , 'data' => array()));
        }

        /**
         * 获取一条评论
         * 修改时间2015年7月15日10:10:50
         * 
         * @author yzx
         */
        public function getoncommentAction()
        {
            $contract_id = Request::queryString('get.contract_id' , 0);//接收身份证号码
            $rental_id = Request::queryString('get.rental_id' , 0);//接收身份证号码
            $user = $this->user;
            $tenant_model = new \Common\Model\Erp\Tenant();
            $contract_rental_model = new \Common\Model\Erp\ContractRental();
            //租客
            $tenant_id = $contract_rental_model->getData(array('contract_id' => $contract_id) , array('tenant_id' => 'tenant_id'));
            $tenant_id = array_column($tenant_id , 'tenant_id');
            $if_sql = array(new Expression("IF ((tenant.gender = 1), '男', '女')"));
            $tenant_info = $tenant_model->getData(array('tenant_id' => $tenant_id) , array('phone' => 'phone' , "gender" => $if_sql[0] , 'idcard' => 'idcard' , 'name' => 'name' ,));
            //分数
            $evaluate_model = new Evaluate();
            $evaluate_data = $evaluate_model->getData(array('company_id' => $user['company_id'] , 'user_id' => $user['user_id'] , 'rental_id' => $rental_id));

            foreach ($evaluate_data as $k => $v)
            {
                $evaluate_data[$k]['create_time'] = date('Y-m-d' , $v['create_time']);
            }
            $data['tenant'] = $tenant_info;
            $data['Score'] = $evaluate_data[0];
            if (!empty($evaluate_data))
            {
                return $this->returnAjax(array('status' => 1 , 'data' => $data));
            }
            return $this->returnAjax(array('status' => 0 , 'data' => '没有租客!'));
        }

        /**
         * 租客搜索是否存在接口   暂时注释掉 , 看有没有哪里出错
         * @author too|最后修改时间 2015年5月5日 下午4:15:24
         */
        /* public function checkTenantAction(){
          $test = new Tenant();
          $data = array(
          'idcard'=>Request::queryString('post.idcard','')
          );
          if(empty($data['idcard']) || !ValidityVerification::IsId($data['idcard'])){
          return $this->returnAjax(array('status'=>0,'message'=>'身份证号码不正确'));
          }
          $result = $test->searchTenant($data, $this->getUser()['company_id']);
          if(empty($result)){
          return $this->returnAjax(array('status'=>0,'tenant_data'=>array()));
          }
          return $this->returnAjax(array('status'=>1,'tenant_data'=>$result[0]));
          } */

        /**
         * 支付#{{'Finance-serial/addIncome'|url|cat:'&'|cat:'contract_id='|cat:$tcdata.contract_id}}
         * 'url'=>Url::parse("Finance-serial/addExpense/house_name/$data[hosue_name]/payall/$payall")));
         */
        public function payAction()
        {
            $id = I('get.contract_id');
            $tc = new \App\Web\Helper\TenantContract();
            $todoModel = new \Common\Model\Erp\Todo();
            $tcdata = $tc->getTenantContract($id);
            $payall = $tcdata[0]['next_pay_money'];
            //修改下次付款时间
//         $next_pay_time = date('Y-m-d',$tcdata[0]['next_pay_time']);
//         $tmptimearr = explode('-', $next_pay_time);
//         $tmptimearr[1] = $tmptimearr[1] + $tcdata[0]['pay_num'];
//         $next_pay_time = mktime(0,0,0,$tmptimearr[1],$tmptimearr[2],$tmptimearr[0]);
//         if ($next_pay_time>=$tcdata[0]['dead_line']){
//         	$next_pay_time = $tcdata[0]['dead_line'];
//         }
//         $todoModel->Transaction();
//         if(!$todoModel->edit(array('module'=>'tenant_contract_shouzu','entity_id'=>$id,'company_id'=>$this->getUser()['company_id']),array('deal_time'=>$next_pay_time)))
//         {
//             $todoModel->rollback();
//             return $this->returnAjax(array('status'=>0,'message'=>'更新日程失败'));
//         }
//         if(!$tc->editTenantContract(array('next_pay_time'=>$next_pay_time),$id))
//         {
//             $todoModel->rollback();
//             return $this->returnAjax(array('status'=>0,'message'=>'更新下次付款时间失败'));
//         }
//         $todoModel->commit();
            return $this->returnAjax(array('status' => 1 , 'jumpurl' => Url::parse("Finance-serial/newaddincome/charge_source/room_charge/house_id/{$tcdata[0]['r_house_id']}/room_id/{$tcdata[0]['r_room_id']}/house_type/{$tcdata[0]['r_house_type']}/charge_contract_id/$id/con_detail/1")));
        }

        /**
         * 短信通知
         * [租客姓名]你好，我是[房源名称]的房东，请于[下次付款时间]前，支付下个租房周期的租金[租金*付款周期]元，若有问题，请联系[联系电话]
         * @author too|编写注释时间 2015年6月10日 上午11:31:27
         */
        public function sendmessageAction()
        {
            $tenant_name = I('get.tname' , '' , 'string');//租客姓名
            $house_name = I('get.house_name' , '' , 'string');//房源名
            $next_pay_time = I('get.next_pay_time' , 0 , 'int');//下次付款时间
            $next_pay_time = date('Y-m-d' , $next_pay_time);
            $pay = I('get.pay');//需要支付的金额
            $tel = 'hhh';//有疑问需要联系的电话
            $phone = I('get.phone');//接收短信的号码
            $source_id = I('get.contract_id');//合同id
            $content = $tenant_name . '你好，我是' . $house_name . '的房东，请于' . $next_pay_time . '前，支付下个租房周期的租金' . $pay . '元，若有问题，请联系' . $tel . '。';
            $entity_id = $source_id;
            $module_type = 'Tenant_contract';
            if (!\Common\Helper\Sms::phone($content , $phone , $entity_id , $module_type , $source_id))
            {
                return $this->returnAjax(array('status' => 0 , 'message' => '短信发送失败'));
            }
            else
            {
                return $this->returnAjax(array('status' => 1 , 'message' => '短信发送成功'));
            }
        }

        /**
         * 根据合同id获取该合同下的所有租客
         * 修改时间  2015年7月31日10:02:42
         * 
         * @author ft
         * @param  int $contract_id
         */
        public function getalltenantAction()
        {
            $contract_id = I('contract_id' , 0);
            $rental_id = I('rental_id' , 0);
            $contract_rental_model = new \Common\Model\Erp\ContractRental();
            $tenant_model = new \Common\Model\Erp\Tenant();
            $all_tenant_id = $contract_rental_model->getData(array('contract_id' => $contract_id) , array('tenant_id' => 'tenant_id'));
            $tenant_id = array_column($all_tenant_id , 'tenant_id');
            $if_sql = array(new Expression("IF ((tenant.gender = 1), '男', '女')"));
            $tenant_info = $tenant_model->getData(array('tenant_id' => $tenant_id) , array('phone' => 'phone' , "gender" => $if_sql[0] , 'idcard' => 'idcard' , 'name' => 'name' ,));
            foreach ($tenant_info as $key => $tenant)
            {
                $tenant_info[$key]['rental_id'] = $rental_id;
            }
            if ($tenant_info)
            {
                return $this->returnAjax(array('status' => 1 , 'data' => $tenant_info));
            }
            else
            {
                return $this->returnAjax(array('status' => 0 , 'data' => '没有租客'));
            }
        }

    }
    