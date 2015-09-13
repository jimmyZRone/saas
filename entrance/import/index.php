<?php

// ini_set('display_errors',false);
    ini_set('display_errors' , true);
    define('DB_JZSAAS' , 'jzsaas_cs');//sass表
    define('DB_ERP' , 'erp'); //ERP表
    define('DB_JOOOZO' , 'jooozo');//九猪用户表
    $_config = array(
        'DB_TYPE' => 'mysql' , // 数据库类型
        'DB_DEPLOY_TYPE' => 1 ,
        'DB_RW_SEPARATE' => true ,
        'DB_HOST' => "192.168.199.203" , // 服务器地址
        'DB_NAME' => DB_JZSAAS , // 数据库名
        'DB_USER' => 'root' , // 用户名
        'DB_PWD' => '123456' , // 密码
        'DB_PORT' => '3306' , // 端口
    );
    include 'Model.class.php';
    $ERP_DB = new Model(DB_ERP . '.User' , '' , $_config);
    $JOOOZO_DB = new Model(DB_JOOOZO . '.User' , '' , $_config);
    $JZSAA_DB = new Model(DB_JZSAAS . '.User' , '' , $_config);
    $KV = array();

    set_time_limit(0);
    ini_set('memory_limit' , '2048M');
    $TIME = round(microtime(true) , 2);//初始化开始时间
    $TIME_COUNT = 0;//总耗时记录
    define("USER_ID" , $_REQUEST['user_id']);
    $NOW = time();
    //寻找管理员
    $user_info = $ERP_DB->table('user')->where(array('user_id' => USER_ID , 'user_type' => 1))->find();
    if (!is_array($user_info) || count($user_info) == 0)
        result('未找到用户信息');
    if ($user_info['shift'] == 1)
        result('正在转移中...');
    else if ($user_info['shift'] == 2)
        result('已经成功的转移' , 1);

    $GONGSI_ID = '';
    $HOUSE_ID = array();//房源ID合辑
    $ROOM_ID = array();//房间ID合辑
    $HOUSE_ROOM_ID = array(); //房源和房间关联合辑
    $USER_ID = array();//公司下的所有用户ID
    //设置为正在导入状态
    $ERP_DB->table('user')->where(array('user_id' => USER_ID))->save(array('shift' => 1 , 'shift_time' => $NOW));
    //    L('开始拷贝省份、城市、区域、商圈、小区表  Province, city, area, business, community');
    // ExAllInfo();
    //   TimeConsuming();//记录耗时

    L('开始导出公司信息');
    Company();
    TimeConsuming();//记录耗时

    L('开始导入附件列表');
    Attachments();
    TimeConsuming();//记录耗时

    L('开始获取ERP数据库获取用户列表');
    User();
    TimeConsuming();//记录耗时

    L('开始导入房源列表');
    House();
    TimeConsuming();//记录耗时

    L('开始导入整租房间列表');
    HouseEntirel();
    TimeConsuming();//记录耗时

    L('开始导入合租房间列表');
    Room();
    TimeConsuming();//记录耗时


    L('开始导入业主信息列表');
    Landlord();
    TimeConsuming();//记录耗时


    L('开始导入预定信息列表');
    Reservation();
    TimeConsuming();//记录耗时

    L('开始导入租客信息列表');
    Tenant();
    TimeConsuming();//记录耗时

    L('开始导入租定信息(Rental)列表');
    Rental();
    TimeConsuming();//记录耗时


    L('开始导入流水信息列表');
    Finance();
    TimeConsuming();//记录耗时


    L('开始生成日程');
    imputtodo();
    TimeConsuming();//记录耗时

    L("导入全部完成,总共耗时[$TIME_COUNT]秒");
    result('恭喜你，转移成功' , 1);

    function TimeConsuming()
    {
        global $TIME , $TIME_COUNT;
        $now = round(microtime(true) , 2);
        $time = $now - $TIME;
        $TIME = $now;
        $TIME_COUNT +=$time;
        L("此次耗时[$time]秒");
    }

    function ExAllInfo()
    {
        global $JZSAA_DB;
        $table_list = array(
            'province' ,
            'area' ,
            'community' ,
            'city' ,
            'business' ,
        );
        $db = DB_ERP;
        $new_db = DB_JZSAAS;

        L('修正business的city id 为117的改为4');
        $JZSAA_DB->query("UPDATE $db.business SET city_id='4' WHERE city_id='117'");

        foreach ($table_list as $table)
        {
            L("开始完整导入 $table  至 $new_db 数据库");
            $JZSAA_DB->query("DELETE FROM $new_db.`$table`");
            $sql = "INSERT INTO $new_db.`$table` (SELECT * FROM  $db.`$table`)";
            $result = $JZSAA_DB->query($sql);
            if (!$result)
            {
                L("导入[$table]表的内容失败SQL[$sql]");
            }
        }
    }

    function Finance()
    {
        global $ERP_DB , $JZSAA_DB , $KV , $HOUSE_ID , $ROOM_ID , $GONGSI_ID;
        $list = $ERP_DB->table('finance_category')->where("middleman_id='$GONGSI_ID' OR middleman_id=0")->select();

        L("获取到流水信息[" . count($list) . "]个,开始分析入库");
        $system_list = array("租金" , "押金" , "水费" , "电费" , "气费" , "物业费" , "服务费" , "维修费" , "保洁费" , "定金" , "欠费" , "网费");//系统的属性
        $gongsi_list = array();//公司独立的属性
        $parent_list = array();//按parent归类 用于查找是否是三级分类
        $id_name = array();//记录ID对应的名称
        //将数据归类

        foreach ($list as $val)
        {
            if ($val['parent_id'] > 0)
                $parent_list[$val['parent_id']][] = $val;
        }
        foreach ($list as $val)
        {
            //只需要三级分类
            if (isset($parent_list[$val['category_id']]))
                continue;
            if (!in_array($val['name'] , $system_list))
                $system_list[] = $val['name'];
            $id_name[$val['category_id']] = $val['name'];
        }

        unset($list);
        L("开始导入系统默认分类[" . count($system_list) . "]个");
        //系统的将每个公司的copy一份
        $company_id = $KV['gongsi'][$GONGSI_ID];
        foreach ($system_list as $key => $val)
        {

            $data = array(
                'type_name' => $val ,
                'company_id' => $company_id , // $KV['gongsi'][$val['middleman_id']] ,
                'is_delete' => 0 ,
                'sys_type_id' => 0 ,
            );
            $new_category_id = $JZSAA_DB->table('fee_type')->add($data);
            if (!$new_category_id)
                L("导入费用分类失败，名字为[$val]");
            $KV['category_id'][$val] = $new_category_id;
        }


        $where = '';
        if (count($HOUSE_ID) > 0)
        {
            $house_id = implode("','" , $HOUSE_ID);
            $where .= "(room_type='house' AND room_id in('$house_id'))";
        }

        if (count($ROOM_ID) > 0)
        {
            $room_id = implode("','" , $ROOM_ID);
            if (strlen($where) > 0)
                $where.= " OR ";
            $where .="(room_type='room' AND room_id in('$room_id')) ";
        }
        if (strlen($where) == 0)
            return;

        $list = $ERP_DB->table('finance')->where($where)->select();

        $user_id = $KV['user_id'][USER_ID];
        $date = date('Ym');
        foreach ($list as $key => $val)
        {
            $house_id = $val['room_id'];
            $room_id = 0;
            if ($val['room_type'] == 'room')
            {
                $room_id = $KV['room_id'][$house_id];
                $room_info = $JZSAA_DB->field('house_id')->table('room')->where(array('room_id' => $room_id))->find();
                $house_id = $room_info['house_id'];
            }
            else
            {
                $house_id = $KV['house_id'][$house_id];
            }

            if (empty($user_id))
            {
                L("导入流水信息失败用户ID[$user_id]未找到");
                continue;
            }
            $finance_id = $val['finance_id'];
            $name = $id_name[$val['category_id']];

            $data = array(
                // 'serial_id' => '1' ,
                'serial_number' => uniqid($date) ,
                'serial_name' => $name ,
                'house_id' => (int) $house_id ,
                'room_id' => $room_id ,
                'house_type' => 1 ,
                'source' => 'rental' ,
                'source_id' => $val['rental_id'] ,
                'pay_time' => $val['trade'] ,
                'subscribe_pay_time' => '0' ,
                'type' => $val['type'] ,
                'receivable' => $val['money'] ,
                'money' => $val['money'] ,
                'final_money' => $val['money'] ,
                'father_id' => '0' ,
                'user_id' => $KV['user_id'][USER_ID] ,
                'company_id' => $company_id ,
                'payment_mode' => '现金支付' ,
                'remark' => $val['mark'] ,
                'status' => 0 ,
                'create_time' => $val['create_time'] ,
                'is_delete' => $val['is_delete'] ,
            );

            $new_serial_id = $JZSAA_DB->table('serial_number')->add($data);

            if (!$new_serial_id)
            {
                L('流水信息导入到serial_number表出错,ID为[' . $finance_id . ']');
                continue;
            }

            if (empty($val['category_id']))
            {
                L('流水category_id为0,跳过,ID为[' . $finance_id . ']');
                continue;
            }


            $fee_type_id = $KV['category_id'][$name];

            if (empty($fee_type_id))
            {
                L('未找到旧数据的分类名称,' . $name . '跳过,ID为[' . $finance_id . ']');
                continue;
            }

            $data = array(
                'serial_id' => $new_serial_id ,
                'fee_type_id' => (int) $fee_type_id ,
                'money' => $val['money'] ,
                'final_money' => $val['money'] ,
                'is_delete' => $val['is_delete'] ,
            );

            $new_serial_detail_id = $JZSAA_DB->table('serial_detail')->add($data);
            if (!$new_serial_detail_id)
            {
                L('流水信息导入到serial_detail表出错,ID为[' . $finance_id . ']');
                continue;
            }
        }
        L('流水信息导入完毕');
    }

    function Company()
    {
        global $ERP_DB , $JZSAA_DB , $KV , $GONGSI_ID , $HOUSE_ID;
        $val = $ERP_DB->table('middleman')->where(array('user_id' => USER_ID))->find();
        $db = DB_JOOOZO;
        $jooozo_user_info = $ERP_DB->table('middleman as m')->join("LEFT JOIN $db.ppt_app_user as au ON  m.open_id=au.open_id LEFT JOIN $db.ppt_user as u ON au.user_id=u.user_id  ")->WHERE("m.user_id='{$val['user_id']}'")->find();
        if (count($val) == 0 || count($jooozo_user_info) == 0)
            result('获取用户公司信息失败');
        L("获取到公司信息,开始分析入库");
        //记录新公司ID
        $GONGSI_ID = $val['middleman_id'];
        //查询公司下的房源信息
        $list = $ERP_DB->table('house')->where(array('middleman_id' => $GONGSI_ID , 'is_delete' => 0))->field('house_id')->select();
        if (count($list) == 0)
        {
            L("公司下没有房源信息，" . $GONGSI_ID);
        }
        foreach ($list as $info)
        {
            $HOUSE_ID[] = $info['house_id'];
        }
        $db = DB_ERP;
        //查询用户的城市ID
        $city_id = 118;
        if (count($HOUSE_ID) > 0)
        {
            $community_info = $ERP_DB->table('house as m')->field('au.city_id')->join("LEFT JOIN $db.community as au ON  m.community_id=au.community_id")->WHERE(array('house_id' => array('in' , $HOUSE_ID)))->find();
            if ($community_info['city_id'] > 0)
                $city_id = $community_info['city_id'];
        }

        $data = array(
            // 'company_id' => $val['middleman_id'] ,
            'company_name' => $val['brand'] ,
            'pattern' => '01' ,
            'safe_passwd' => $jooozo_user_info['passwd'] , //初始冲账密码为主账号的登录密码
            'safe_salt' => $jooozo_user_info['pwd_suffix'] ,
            'linkman' => $val['contact'] ,
            'city_id' => $city_id ,
            'diy_config_type' => 'array' ,
            'telephone' => $val['phone'] ,
            'company_diy_config' => '' ,
        );
        $id = $JZSAA_DB->table('company')->add($data);
        if (!$id)
        {
            L("入库公司信息" . $val['middleman_id'] . "失败");
            result('导入用户公司信息失败');
        }

        $KV['gongsi'][$val['middleman_id']] = $id;
        L("入库公司信息完成");
    }

    function User()
    {
        global $ERP_DB , $JZSAA_DB , $KV , $USER_ID , $GONGSI_ID;
        //获取用户ID

        $user_list = $ERP_DB->table('user')->where(array('user_id' => array('in' , $USER_ID)))->select();

        //给公司添加一个默认权限组

        $group_id = $JZSAA_DB->table('group')->add(array(
            'company_id' => $KV['gongsi'][$GONGSI_ID] ,
            'parent_id' => 0 ,
            'route' => '' ,
            'create_time' => time() ,
            'name' => '员工分组' ,
        ));

        if (!$group_id)
            result('创建员工权限分组失败');

        L("获取到用户[" . count($user_list) . "]个,开始分析入库");
        foreach ($user_list as $val)
        {
            //管家类型
            if ($val['user_type'] == '2')
            {
                $is_manager = 0;
                $user_info = $ERP_DB->where(array('user_id' => $val['user_id']))->table('keeper')->find();
                $company_id = $KV['gongsi'][$user_info['middleman_id']];
                $contact = $user_info['phone'];
                $user_name = $user_info['account'];
                $pwd = $user_info['passowrd'];
                $pwd_suffix = '';
                $name = $user_info['name'];
            }
            else
            {
                //主账号类型
                $is_manager = 1;
                $db = DB_JOOOZO;
                $user_info = $ERP_DB->table('middleman as m')->join("LEFT JOIN $db.ppt_app_user as au ON  m.open_id=au.open_id LEFT JOIN $db.ppt_user as u ON au.user_id=u.user_id  ")->WHERE("m.user_id='{$val['user_id']}'")->find();
                //用旧的公司ID得到新的公司ID
                $company_id = $KV['gongsi'][$user_info['middleman_id']];
                //拼装统一数据
                $user_name = $user_info['user_name'];
                $pwd = $user_info['passwd'];
                $pwd_suffix = $user_info['pwd_suffix'];
                $contact = $user_name;
                $name = $user_info['contact'];
            }
            if (!is_numeric($company_id))
            {
                L("旧用户ID[{$val['user_id']}],绑定的公司ID不存在");
                $company_id = 0;
            }

            if (empty($user_name))
            {
                L("未找到用户信息，旧用户ID[{$val['user_id']}]");
                continue;
            }
            $user_data = array(
                'is_manager' => $is_manager ,
                'company_id' => $company_id ,
                'username' => $user_name ,
                'password' => $pwd ,
                'salt' => $pwd_suffix ,
                'create_time' => $val['create_time'] ,
                'last_longing_time' => $val['last_longing_time'] ,
            );
            $user_id = $JZSAA_DB->table('user')->add($user_data);
            if (!$user_id)
            {
                L("入库用户信息失败，ID为:" . $val['user_id']);
                result('入库用户信息失败');
            }

            //修改用户头像信息
            $result = $JZSAA_DB->table('attachments')->where(array('module' => 'user_avatar' , 'entity_id' => $val['user_id']))->save(array('entity_id' => $user_id));
            if (!$result)
                L("修改用户头像信息失败，ID" . $val['user_id']);
            //记录新用户ID
            $KV['user_id'][$val['user_id']] = $user_id;
            //L("填充信息至用户扩展表");

            $data = array(
                'user_id' => $user_id ,
                'name' => $name ,
                'contact' => $contact ,
                'gender' => '1' ,
                'city_id' => '0' ,
                'birthday' => date('Y-m-d' , $val['create_time']) ,
            );
            $result = $JZSAA_DB->table('user_extend')->add($data);

            if (!$result)
            {
                L("填充信息至用户扩展表失败,用户ID[$user_id]");
                result('填充信息至用户扩展表失败');
            }

            //给员工分配系统默认分组

            $add = $JZSAA_DB->table('user_group')->add(array(
                'user_id' => $user_id ,
                'group_id' => $group_id ,
            ));
        }
        L("用户信息导出完毕...");
    }

    function Attachments()
    {
        global $ERP_DB , $JZSAA_DB , $KV , $GONGSI_ID , $HOUSE_ID , $HOUSE_ROOM_ID , $ROOM_ID , $USER_ID;


        //查询公司下的房间信息
        if (count($HOUSE_ID) > 0)
        {
            $list = $ERP_DB->table('room')->where(array('house_id' => array('in' , $HOUSE_ID) , 'is_delete' => 0))->field('room_id,house_id')->select();

            foreach ($list as $info)
            {
                $ROOM_ID[] = $info['room_id'];
                $HOUSE_ROOM_ID[$info['house_id']][] = $info['room_id'];
            }
        }

        //查询子账号信息
        $list = $ERP_DB->table('keeper')->where(array('middleman_id' => $GONGSI_ID , 'is_delete' => 0))->field('user_id')->select();
        foreach ($list as $info)
        {
            $USER_ID[] = $info['user_id'];
        }
        $USER_ID[] = USER_ID;//加入主账号ID

        $where = '';
        if (count($HOUSE_ID) > 0)
        {
            $house_id = implode("','" , $HOUSE_ID);
            $where .= "(module='house' AND entity_id in('$house_id'))";
        }
        if (count($ROOM_ID) > 0)
        {
            $room_id = implode("','" , $ROOM_ID);
            if (strlen($where) > 0)
                $where.= " OR ";
            $where .="(module='room' AND entity_id in('$room_id')) ";
        }

        if (count($USER_ID) > 0)
        {
            $user_id = implode("','" , $USER_ID);
            if (strlen($where) > 0)
                $where.= " OR ";
            $where .="(module='user_avatar' AND entity_id in('$user_id')) ";
        }

        //没有附件需要导入
        if (strlen($where) == 0)
            return;
        $list = $ERP_DB->table('attachments')->where($where)->select();

        L("获取到[" . count($list) . "]条附件数据列表,正在导入中....");
        foreach ($list as $val)
        {
            $att_id = $val['attachments_id'];
            unset($val['attachments_id']);
            $data = array(
                'bucket' => $val['bucket'] ,
                'key' => $val['key'] ,
                'module' => $val['module'] ,
                'entity_id' => $val['entity_id'] ,
                'type' => $val['type'] ,
            );
            $result = $JZSAA_DB->table('attachments')->add($data);
            if (!$result)
                L("附件添加失败,ID为:[$att_id]");
        }
        L("附件导出完毕....");
    }

    function House()
    {
        global $ERP_DB , $JZSAA_DB , $KV , $HOUSE_ID;
        if (count($HOUSE_ID) == 0)
            return;
        $list = $ERP_DB->table('house')->where(array('house_id' => array('in' , $HOUSE_ID)))->select();

        $new_xiaoqu_ids = array();

        L("获取到[" . count($list) . "]条房源数据列表,正在导入中....");

        foreach ($list as $key => $val)
        {

            //获取小区信息
            if (!isset($new_xiaoqu_ids[$val['community_id']]))
            {
                $xiaoqu_info = $ERP_DB->table('community')->where(array('community_id' => $val['community_id']))->find();
                $where = array('area_id' => $xiaoqu_info['area_id'] , 'community_name' => $xiaoqu_info['community_name']);
                $saas_xiaoqu_info = $JZSAA_DB->table('community')->field('community_id')->where($where)->find();
                //erp的小区在SASS没有
                if (!isset($saas_xiaoqu_info['community_id']))
                {
                    //复制一份小区到ERP
                    unset($xiaoqu_info['community_id'] , $xiaoqu_info['middleman_id'] , $xiaoqu_info['url']);
                    $xiaoqu_info['is_verify'] = 1;
                    $xiaoqu_info['company_id'] = 0;
                    $xiaoqu_info['map_status'] = 0;
                    $xiaoqu_info['landmark'] = '';
                    $xiaoqu_info['create_time'] = '1440000000';//把创建时间统一，好知道是ERP导入过来的
                    $new_xiaoqu_id = $JZSAA_DB->table('community')->add($xiaoqu_info);
                    if (is_numeric($new_xiaoqu_id))
                        $new_xiaoqu_ids[$val['community_id']] = $new_xiaoqu_id;//使用新增的小区ID
                    else
                        $new_xiaoqu_ids[$val['community_id']] = $val['community_id'];//添加失败 容错
                }
                else
                    $new_xiaoqu_ids[$val['community_id']] = $saas_xiaoqu_info['community_id'];//ERP的小区在SASS存在, 使用SASS小区ID
            }
            $val['community_id'] = $new_xiaoqu_ids[$val['community_id']];
            $house_id = $val['house_id'];
            $company_id = $KV['gongsi'][$val['middleman_id']];
            unset($val['house_id']);
            //以下数据新表没有需要去掉
            unset($val['middleman_id']);
            unset($val['is_decorate']);
            unset($val['decorate_style']);
            unset($val['expenses_mode']);
            unset($val['expenses_money']);
            unset($val['pay_type']);
            unset($val['description']);
            unset($val['title']);

            $val['public_facilities'] = room_config($val['public_facilities']);

            //获取新的用户ID
            $val['create_uid'] = $KV['user_id'][$val['create_uid']];

            if (empty($val['create_uid']))
            {
                $val['create_uid'] = reset($KV['user_id']);
                L("该房源的用户ID不存在[{$list[$key]['create_uid']}],房源ID[{$house_id}],将使用创始人的ID储存");
                // continue;
            }

            $val['company_id'] = $company_id;
            $val['owner_id'] = $val['create_uid'];

            $new_house_id = $JZSAA_DB->table('house')->add($val);

            if (!$new_house_id)
            {
                L("导入房源信息失败,房源ID[{$house_id}]");
            }
            //记录新房源ID
            $KV['house_id'][$house_id] = $new_house_id;
            //修改将该房源的附件信息
            $result = $JZSAA_DB->table('attachments')->where(array('module' => 'house' , 'entity_id' => $house_id))->save(array('entity_id' => $new_house_id));
            if (!$result)
                L("修改房源附件信息失败，ID" . $house_id);
        }

        L("导出房源完毕....");
    }

    function HouseEntirel()
    {
        global $ERP_DB , $JZSAA_DB , $KV , $HOUSE_ID;

        if (count($HOUSE_ID) == 0)
            return;

        $list = $ERP_DB->table('house_entirel')->where(array('house_id' => array('in' , $HOUSE_ID) , 'is_delete' => 0))->select();
        L("获取到[" . count($list) . "]条房源数据列表,正在导入中....");
        foreach ($list as $key => $val)
        {
            $house_id = $val['house_id'];
            $new_house_id = $KV['house_id'][$house_id];

            if (empty($new_house_id))
            {
                L("该整租房的旧数据不存在跳过,房源ID为[$house_id]");
            }

            $val['house_id'] = $new_house_id;

            $status = 1;
            $is_yd = 0;
            if ($val['status'] == 3)
            {
                $is_yd = 1;
            }
            elseif ($val['status'] == 4)
            {
                $count = $ERP_DB->table('rental')->where(array('room_id' => $house_id , 'is_delete' => 0 , 'room_type' => 'house'))->count();
                //已租房 在未查到合同时 设置为未租
                if ($count[0]['count'] > 0)
                {
                    $status = 2;
                }
                else
                {
                    $status = 1;
                }
            }


            $val['status'] = $status;
            //旧数据没有该内容 默认赋值
            $val['is_yytz'] = 0;
            //查询旧数据是否被预定

            $val['is_yd'] = $is_yd;

            list($Pstr , $detain , $pay) = explode('-' , $val['pay_type']);
            $val['pay'] = is_numeric($pay) ? $pay : 3;
            $val['detain'] = $detain > 1 ? $detain : 1;
            unset($val['pay_type']);
            unset($val['house_entirel_id']);
            $gender_restrictions = explode('-' , trim($val['gender_restrictions'] , '-'));
            $val['gender_restrictions'] = count($gender_restrictions) == 1 && in_array($gender_restrictions[0] , array(1 , 2)) ? $gender_restrictions[0] : 0;//1男，2女，3夫妻
            $result = $JZSAA_DB->table('house_entirel')->add($val);
            if (!$result)
            {
                L("导出整租房间失败,房间ID为[$house_id]");
            }
        }
        L("导出整租房间完毕...");
    }

    function Room()
    {
        global $ERP_DB , $JZSAA_DB , $KV , $ROOM_ID;
        if (count($ROOM_ID) == 0)
            return;
        $list = $ERP_DB->table('room')->where(array('room_id' => array('in' , $ROOM_ID) , 'is_delete' => 0))->select();

        L("获取到[" . count($list) . "]条合租房源数据列表,正在导入中....");

        foreach ($list as $val)
        {

            $room_id = $val['room_id'];
            $house_id = (int) $KV['house_id'][$val['house_id']];

            if (empty($house_id))
                L("合租房的房源ID未找到...合租房源ID为:[$room_id]");

            $status = 1;
            $is_yd = 0;
            if ($val['status'] == 3)
            {
                $is_yd = 1;
            }
            elseif ($val['status'] == 4)
            {
                $count = $ERP_DB->table('rental')->where(array('room_id' => $room_id , 'is_delete' => 0 , 'room_type' => 'room'))->count();
                //已租房 在未查到合同时 设置为未租
                if ($count[0]['count'] > 0)
                {
                    $status = 2;
                }
                else
                {
                    //未查到合同
                    $status = 1;
                }
            }
            $val['status'] = $status;
            list($Pstr , $detain , $pay) = explode('-' , $val['pay_type']);
            $val['detain'] = $detain > 1 ? $detain : 1;
            $val['pay'] = is_numeric($pay) ? $pay : 3;
            //旧数据没有该内容 默认赋值
            $val['is_yytz'] = 0;
            //查询旧数据是否被预定
            //$yy_info = $ERP_DB->table('reservation')->where(array('status' => 1 , 'module' => 1 , 'entity_id' => $house_id))->find();
            $val['is_yd'] = $is_yd;
            //转换主次卧的参数
            $room_type = 'guest';
            if ($val['room_type'] == 'second_lie')
            {
                $room_type = 'second';
            }
            elseif ($val['room_type'] == 'mainroom')
            {
                $room_type = 'main';
            }

            $val['room_type'] = $room_type;

            $val['house_id'] = $house_id;
            unset($val['room_id']);
            unset($val['pay_type']);
            unset($val['house_entirel_id']);
            $val['room_config'] = room_config($val['room_config'] , true);
            $gender_restrictions = explode('-' , trim($val['gender_restrictions'] , '-'));
            $val['gender_restrictions'] = count($gender_restrictions) == 1 && in_array($gender_restrictions[0] , array(1 , 2)) ? $gender_restrictions[0] : 0;//1男，2女，3夫妻

            $new_room_id = $JZSAA_DB->table('room')->add($val);

            if (!$new_room_id)
            {
                L("导出合租房间失败,房间ID为[$room_id]");
                continue;
            }
            //记录合租房源ID
            $KV['room_id'][$room_id] = $new_room_id;

            //修改将该房源的附件信息
            $result = $JZSAA_DB->table('attachments')->where(array('module' => 'room' , 'entity_id' => $room_id))->save(array('entity_id' => $new_room_id));
            if (!$result)
                L("修改房源附件信息失败，ID" . $house_id);
        }

        L("合租房源导出完毕...");
    }

    function Landlord()
    {
        global $ERP_DB , $JZSAA_DB , $KV , $GONGSI_ID;
        $list = $ERP_DB->table('landlord')->where(array('middleman_id' => $GONGSI_ID , 'is_delete' => 0))->select();

        L("获取到[" . count($list) . "]条房东数据列表,正在导入中....");
        $landlord_list = array();


        foreach ($list as $val)
        {
            $landlord_id = $val['landlord_id'];
            $landlord_list[$landlord_id] = $val;

            unset($val['landlord_id']);
            unset($val['house_name']);
            unset($val['house_id']);
            $val['create_user_id'] = (int) $KV['user_id'][$val['create_user']];
            $val['company_id'] = $KV['gongsi'][$val['middleman_id']];
            unset($val['create_user']);
            unset($val['middleman_id']);
            $val['idcard'] = strlen($val['idcard']) == 0 ? '未填写' : $val['idcard'];
            $new_landlord_id = $JZSAA_DB->table('landlord')->add($val);

            if (!$new_landlord_id)
            {
                L("导出房东信息失败,房东ID为[$landlord_id]");
                continue;
            }
            //记录房东ID
            $KV['landlord_id'][$landlord_id] = $new_landlord_id;
        }
        L("房东信息导入完毕...");

        if (count($list) == 0)
            return;

        L("开始导入房东合同信息");
        $lan_ids = array_keys($KV['landlord_id']);
        if (count($lan_ids) == 0)
            return;
        $list = $ERP_DB->table('landlord_contract')->where(array('landlord_id' => array('in' , $lan_ids)))->select();

        L("获取到[" . count($list) . "]条房东合同数据,正在导入中....");
        foreach ($list as $val)
        {


            $contract_id = $val['contract_id'];
            $landlord_id = $val['landlord_id'];

            $val['landlord_id'] = $KV['landlord_id'][$landlord_id];
            list($Pstr , $detain , $pay) = explode('-' , $val['pay_type']);
            unset($val['pay_type']);
            unset($val['contract_id']);
            unset($val['pay_time']);
            unset($val['true_dead_line']);
            $val['detain'] = $detain > 1 ? $detain : 1;
            $val['pay'] = is_numeric($pay) ? $pay : 3;
            $val['end_line'] = $val['dead_line'];
            $val['fork_bank'] = $val['bank'];
            $val['next_pay_money'] = 0;
            $val['free_day'] = 0;
            $val['is_settlement'] = 1;
            $val['house_type'] = 1;//默认为分散


            $landlord_info = $landlord_list[$landlord_id];

            if (!$landlord_info)
            {
                L("获取合同ID[$contract_id]的房东ID[$landlord_id]信息失败, 跳过。。。");
                continue;
            }

            if (empty($landlord_info))
            {
                L("获取合同ID[$contract_id]的房东ID[$landlord_id]信息失败, 跳过。。。");
                continue;
            }
            $val['next_pay_time'] = strlen($val['next_pay_time']) == '10' ? $val['next_pay_time'] : '0';
            $val['house_id'] = $KV['house_id'][$landlord_info['house_id']];
            $val['hosue_name'] = $landlord_info['house_name'];
            $val['mark'] = (string) trim($landlord_info['mark']);
            $val['company_id'] = $KV['gongsi'][$GONGSI_ID];
            $new_contract_id = $JZSAA_DB->table('landlord_contract')->add($val);

            if (!$new_contract_id)
            {
                L("导出房东合同失败,合同ID为[$contract_id]");
                continue;
            }
            // $KV['contract_id'][$contract_id] = $new_contract_id;
//
//            //分析租金递增 
//            if ($val['cycle'] <= 0 || $val['ascending_num'] <= 0 || ($val['ascending_type'] == 1 && $val['rent'] <= 0))
//                continue;
//          
//              
//            $year = ceil($val['cycle'] / 12);
//            $money=$val['rent'];
//            $num = $val['ascending_num'];
//            for ($i = 1; $i <= $year; $i++)
//            {
//
//        
//                $new_money = $val['ascending_type'] == 1 ? $money / $num : $money + $num;
//                $money=$new_money;
//                $data = array(
//                    'contract_id' => $new_contract_id ,
//                    'contract_year' => $i ,
//                    'ascending_money' => $money ,
//                );
//                $new_contract_id = $JZSAA_DB->table('landlord_ascending')->add($data);
//            }
        }
        L("导出房东合同完毕");
    }

    function Reservation()
    {
        global $ERP_DB , $JZSAA_DB , $KV , $HOUSE_ID , $ROOM_ID , $GONGSI_ID;

        $where = '';
        if (count($HOUSE_ID) > 0)
        {
            $house_id = implode("','" , $HOUSE_ID);
            $where .= "(module='2' AND entity_id in('$house_id'))";
        }

        if (count($ROOM_ID) > 0)
        {
            $room_id = implode("','" , $ROOM_ID);
            if (strlen($where) > 0)
                $where.= " OR ";
            $where .="(module='1' AND entity_id in('$room_id')) ";
        }

        if (strlen($where) == 0)
            return;
        $list = $ERP_DB->table('reservation')->where($where)->select();

        L("获取到[" . count($list) . "]条预定数据列表,正在导入中....");

        foreach ($list as $val)
        {
            $reservation_id = $val['reservation_id'];
            $house_id = '';
            $room_id = '0';
            if ($val['module'] == 1)
            {
                //合租
                $room_id = $KV['room_id'][$val['entity_id']];
                $landlord_info = $JZSAA_DB->field('house_id')->table('room')->where(array('room_id' => $room_id))->find();
                $house_id = $landlord_info['house_id'];
            }
            else
            {
                //整租
                $house_id = $KV['house_id'][$val['entity_id']];
            }

            //新增租客
            $tenant_id = $JZSAA_DB->table('tenant')->add(array(
                'phone' => $val['phone'] ,
                'name' => $val['name'] ,
                'idcard' => '未填写' ,
                'company_id' => $KV['gongsi'][$GONGSI_ID] ,
            ));
            $data = array(
                'tenant_id' => $tenant_id ,
                'house_id' => $house_id ,
                'room_id' => $room_id ,
                'house_type' => 1 ,
                'money' => $val['money'] ,
                'stime' => $val['create_time'] ,
                'etime' => $val['end_time'] ,
                'mark' => '' ,
                'create_time' => $val['create_time'] ,
                'is_delete' => 0 ,
                'pay_type' => 2 ,
                'source' => 1 ,
            );

            $new_reservation_id = $JZSAA_DB->table('reserve')->add($data);
            if (!$new_reservation_id)
            {
                L("导入预定信息失败，预定ID为[$reservation_id]");
                continue;
            }
            $KV['reservation_id'][$reservation_id] = $new_reservation_id;
        }
        L('导出预定信息完毕');
    }

    function Rental()
    {
        global $ERP_DB , $JZSAA_DB , $KV , $HOUSE_ID , $ROOM_ID , $GONGSI_ID , $HOUSE_ROOM_ID;

        $where = '';
        if (count($HOUSE_ID) > 0)
        {
            $house_id = implode("','" , $HOUSE_ID);
            $where .= "(r.room_type='house' AND r.room_id in('$house_id'))";
        }

        if (count($ROOM_ID) > 0)
        {
            $room_id = implode("','" , $ROOM_ID);
            if (strlen($where) > 0)
                $where.= " OR ";
            $where .="(r.room_type='room' AND r.room_id in('$room_id')) ";
        }
        if (strlen($where) == 0)
            return;
        $db = DB_ERP;
        $list = $ERP_DB->field('tc.*,r.room_id,r.room_type')->table('tenant_contract as tc')->join("LEFT JOIN $db.rental as r on tc.contract_id=r.contract_id AND tc.is_delete=0  ")->WHERE($where)->select();

        L("获取到[" . count($list) . "]条租金合同数据列表,正在导入中....");

        foreach ($list as $key => $val)
        {

            if ($val['room_type'] == 'house')
            {
                $ids = $HOUSE_ID;
            }
            else
            {
                $ids = $ROOM_ID;
            }
            //合同的房间已不存在

            if (!in_array($val['room_id'] , $ids))
                continue;
            $contract_id = $val['contract_id'];
            unset($val['contract_id']);
            $val['parent_id'] = 0;
            $val['is_renewal'] = 2;
            list($Pstr , $detain , $pay) = explode('-' , $val['pay_type']);
            unset($val['pay_type']);
            $val['detain'] = $detain > 1 ? $detain : 1;
            $val['pay'] = is_numeric($pay) ? $pay : 3;
            $val['end_line'] = $val['dead_line'];
            $val['next_pay_money'] = $val['rent'];
            $val['remark'] = '';
            $val['is_settlement'] = 1;
            $val['next_pay_time'] = strlen($val['next_pay_time']) == '10' ? $val['next_pay_time'] : $val['dead_line'];
            $val['company_id'] = (int) $KV['gongsi'][$GONGSI_ID];
            $val['is_evaluate'] = 0;
            $val['custom_number'] = strlen($val['custom_number']) == 0 ? '未填写' : $val['custom_number'];
            $val['is_stop'] = $val['dead_line'] != $val['true_dead_line'] ? 1 : 0;
            unset($val['rental_total']);
            unset($val['room_id']);
            unset($val['room_type']);
            unset($val['house_name']);
            unset($val['hasten_time']);
            unset($val['true_dead_line']);

            $new_contract_id = $JZSAA_DB->table('tenant_contract')->add($val);

            if (!$new_contract_id)
            {
                L("导入租金合同信息失败，ID为[$contract_id]");
                continue;
            }
            $KV['contract_id'][$contract_id] = $new_contract_id;
        }

        unset($list);
        L("导入租金合同信息导入完毕");

        $list = $ERP_DB->table('rental as r')->where($where)->select();

        L("获取到[" . count($list) . "]条数据列表,正在导入中....");
        $time = time();


        foreach ($list as $key => $val)
        {
            $rental_id = $val['rental_id'];
            $house_id = $val['room_id'];
            $room_id = 0;
            unset($val['rental_id']);
            if ($val['room_type'] == 'room')
            {
                $room_id = $KV['room_id'][$house_id];
                $room_info = $JZSAA_DB->field('house_id')->table('room')->where(array('room_id' => $room_id))->find();
                $house_id = $room_info['house_id'];
            }
            else
            {
                $house_id = $KV['house_id'][$house_id];
            }

            if (empty($house_id) && empty($room_id))
            {
                L("house_id和room_id都是空数据,判断为异常数据,[rental_id]为[$rental_id]跳过...");
                continue;
            }
            $new_contract_id = (int) $KV['contract_id'][$val['contract_id']];

            if ($new_contract_id == 0)
                continue;

            $data = array(
                'house_id' => $house_id ,
                'room_id' => $room_id ,
                'tenant_id' => $KV['tenant_id'][$val['tenant_id']] ,
                'contract_id' => $new_contract_id ,
                'house_type' => 1 ,
                'source_id' => 0 ,
                'source' => '来自旧数据Rental表预定人ID' ,
                'is_delete' => $val['is_delete'] ,
            );


            $new_rental_id = $JZSAA_DB->table('rental')->add($data);
            if (!$new_rental_id)
            {
                L("导入租订信息失败，ID为[$rental_id]");
                continue;
            }
            $data = array(
                'contract_id' => $new_contract_id ,
                'tenant_id' => $KV['tenant_id'][$val['tenant_id']] ,
                'creat_time' => $time ,
                'is_delete' => 0 ,
            );
            $result = $JZSAA_DB->table('contract_rental')->add($data);
        }

        L("导入租订信息完毕");
    }

    function Tenant()
    {
        global $ERP_DB , $JZSAA_DB , $KV , $GONGSI_ID;
        $list = $ERP_DB->table('tenant')->where(array('middleman_id' => $GONGSI_ID , 'is_delete' => 0))->select();
        L("获取到[" . count($list) . "]条数据列表,正在导入中....");

        foreach ($list as $key => $val)
        {
            $tenant_id = $val['tenant_id'];
            $val['address'] = '';
            $val['emergency_phone'] = '';
            $val['emergency_contact'] = '';
            $val['nation'] = '汉';
            $val['work_place'] = '';
            $val['profession'] = $val['job'];
            $val['email'] = '';
            $val['remarks'] = '';
            $val['company_id'] = $KV['gongsi'][$val['middleman_id']];
            $val['name'] = $val['name'];
            $val['from'] = '';
            $val['idcard'] = strlen($val['idcard']) == 0 ? '未填写' : $val['idcard'];
            unset($val['middleman_id']);
            unset($val['job']);
            unset($val['creat_user']);
            unset($val['tenant_id']);

            $new_tenant_id = $JZSAA_DB->table('tenant')->add($val);
            if (!$new_tenant_id)
            {
                L("导入租客信息失败，ID为[$tenant_id]");
                continue;
            }
            //修改预定表的租客ID为新ID
            if (is_numeric($tenant_id))
                $JZSAA_DB->table('rental')->where(array('tenant_id' => $tenant_id))->save(array('tenant_id' => $new_tenant_id));
            $KV['tenant_id'][$tenant_id] = $new_tenant_id;
        }
        L("导入租客信息完毕");
    }

    function dump($text)
    {
        var_dump($text);
    }

    function L($text)
    {
        global $_config;
        $LogDb = new Model('jzsaas_cs_log.Log' , '' , $_config);

        $data['unique_id'] = gethostbyname('');//获取局域网IP
        $data['text'] = $text;
        $data['type'] = 'Debug';
        $result = $LogDb->add($data);//写入日志

        return $result;
    }

    function result($txt , $status = 0)
    {
        global $ERP_DB;
        if ($status == 1)
        {
            $type = 2;
        }
        else
        {
            $type = 0;
        }
        $ERP_DB->table('user')->where(array('user_id' => USER_ID))->save(array('shift' => $type , 'shift_time' => time()));
        $array = array(
            'status' => $status ,
            'msg' => $txt ,
        );
        if (isset($_GET['callback']) && $_GET['callback'])
        {
            echo $_GET['callback'] . '(' . json_encode($array) . ')';
            die();
        }
        else
        {
            exit(json_encode($array));
        }
    }

    //旧配置转新配置
    function room_config($config , $room = false)
    {
        if ($room)
        {
            $erp_config = array(1 => '床' , 2 => '桌椅' , 3 => '衣柜' , 4 => '电视' , 5 => '空调' , 6 => '卫生间' , 7 => '飘窗' , 8 => '阳台');
        }
        else
        {
            $erp_config = array(1 => '冰箱' , 2 => '电视' , 3 => '洗衣机' , 4 => '燃气' , 5 => '热水器' , 6 => '宽带' , 7 => '床' , 9 => '衣柜' , 8 => '桌椅' , 11 => '空调');
        }

        $sass_config = array(1 => '冰箱' , 2 => '电视' , 3 => '空调' , 4 => '宽带' , 5 => '暖气' , 6 => '燃气' , 7 => '阳台' , 8 => '飘窗' , 9 => '床' , 10 => '热水器' , 11 => '洗衣机' , 12 => '卫生间' , 13 => '桌椅' , 14 => '衣柜');



        $config = explode('-' , trim($config , '-'));

        $new_config = array();
        foreach ($config as $num)
        {
            if (!isset($erp_config[$num]))
                continue;
            $new_num = array_search($erp_config[$num] , $sass_config);
            if ($new_num === FALSE)
                continue;
            $new_config[] = $new_num;
        }
        sort($new_config);
        return implode('-' , $new_config);
    }

    //生成日程
    function imputtodo()
    {
        global $JZSAA_DB , $KV , $GONGSI_ID;
        $db = DB_JZSAAS;
        $user_id = $KV['user_id'][USER_ID];
        $company_id = $KV['gongsi'][$GONGSI_ID];

        /* $contract_data = $JZSAA_DB->table("landlord_contract as lc")
          ->join("LEFT JOIN $db.landlord as l ON  lc.landlord_id=l.landlord_id")
          ->WHERE("l.create_user_id='{$user_id}'")->select();
          $data = array();
          //业主合同
          if (!empty($contract_data)){
          //生成业主合同到期日志
          foreach ($contract_data as $ckey=>$cval){
          $todo_data = $JZSAA_DB->table("todo")
          ->where(array("entity_id"=>$cval['contract_id'],"module"=>"landlord_contract","create_uid"=>$user_id,"deal_time"=>$cval['end_line']))->select();
          if (empty($todo_data)){
          $data['module']='landlord_contract';
          $data['entity_id'] = $cval['contract_id'];
          $data['title'] = '到期';
          $data['content'] = $cval['house_name'].'的业主合同将于'.date("Y-m-d",$cval['end_line']).'到期，请注意处理';
          $data['company_id'] = $company_id;
          $data['url'] = '/index.php?c=landlord-index&a=info&contract_id='.$cval['contract_id'];
          $data['status'] = 0;
          $data['deal_time'] = $cval['end_line'];
          $data['create_time'] = time();
          $data['create_uid'] = $user_id;
          $JZSAA_DB->table('todo')->add($data);
          }
          $data = array();
          }
          //生成业主合同交租
          foreach ($contract_data as $ckey=>$cval){
          $todo_data = array();
          $todo_data = $JZSAA_DB->table("todo")
          ->where(array("entity_id"=>$cval['contract_id'],"module"=>"landlord_contract","create_uid"=>$user_id,"deal_time"=>$cval['next_pay_time']))->select();

          if (empty($todo_data)){
          $data['module']='landlord_contract';
          $data['entity_id'] = $cval['contract_id'];
          $data['title'] = '交租';
          $data['content'] = $cval['house_name'].'的租金'.$cval['next_pay_money'].'元应于'.date("Y-m-d",$cval['next_pay_time']).'支付，请注意处理';
          $data['company_id'] = $company_id;
          $data['url'] = '/index.php?c=landlord-index&a=info&contract_id='.$cval['contract_id'];
          $data['status'] = 0;
          $data['deal_time'] = $cval['next_pay_time'];
          $data['create_time'] = time();
          $data['create_uid'] = $user_id;
          $JZSAA_DB->table('todo')->add($data);
          }
          $data = array();
          }
          } */

        //租客合同
        //合租
        $room_type = array("main" => "主卧" , "second" => "次卧" , "guest" => "客卧");
        $room_data = $JZSAA_DB->table("rental as r")
                        ->join("LEFT JOIN $db.tenant AS t 
			    ON (r.tenant_id = t.tenant_id)
			  LEFT JOIN $db.tenant_contract AS tc ON(r.contract_id=tc.contract_id)
			  LEFT JOIN $db.room AS rm ON(r.room_id=rm.room_id)
			  LEFT JOIN $db.house AS h ON(rm.house_id=h.house_id)")
                        ->where(array("r.is_delete" => 0 , "r.house_type" => 1 , "h.create_uid" => $user_id))
                        ->group("rm.room_id")->field("tc.contract_id,rm.custom_number,tc.end_line,h.house_name,rm.room_type,tc.next_pay_time")->select();
        //租客合租合同到期
        foreach ($room_data as $rkey => $rval)
        {
            $todo_data = array();
            $todo_data = $JZSAA_DB->table("todo")
                            ->where(array("entity_id" => $rval['contract_id'] ,
                                "module" => "tenant_contract" ,
                                "create_uid" => $user_id ,
                                "deal_time" => $rval['end_line']))->select();

            if (empty($todo_data) && $rval['end_line'] > time())
            {
                $data['module'] = 'tenant_contract';
                $data['entity_id'] = $rval['contract_id'];
                $data['title'] = '到期';
                $data['content'] = $rval['house_name'] . "-" . $rval['custom_number'] . $room_type[$rval['room_type']] . "的租客合同将于" . date("Y-m-d" , $rval['end_line']) . "到期，请注意处理";
                $data['company_id'] = $company_id;
                $data['url'] = "/index.php?c=tenant-index&a=edit&contract_id=" . $rval['contract_id'];
                $data['status'] = 0;
                $data['deal_time'] = $rval['end_line'] ? $rval['end_line'] : 0;
                $data['create_time'] = time();
                $data['create_uid'] = $user_id;
                $JZSAA_DB->table('todo')->add($data);
            }
            $data = array();
        }
        //租客合租合同收租
        foreach ($room_data as $rkey => $rval)
        {
            $todo_data = array();
            $todo_data = $JZSAA_DB->table("todo")
                            ->where(array("entity_id" => $rval['contract_id'] ,
                                "module" => "tenant_contract_shouzu" ,
                                "create_uid" => $user_id ,
                                "deal_time" => $rval['next_pay_time']))->select();

            if (empty($todo_data) && $rval['end_line'] > time())
            {
                $data['module'] = 'tenant_contract_shouzu';
                $data['entity_id'] = $rval['contract_id'];
                $data['title'] = '收租';
                $data['content'] = $rval['house_name'] . "-" . $rval['custom_number'] . $room_type[$rval['room_type']] . "的租金" . $rval['next_pay_money'] . "元应于" . date("Y-m-d" , $rval['next_pay_time']) . "收取，请注意处理";
                $data['company_id'] = $company_id;
                $data['url'] = "/index.php?c=tenant-index&a=edit&contract_id=" . $rval['contract_id'];
                $data['status'] = 0;
                $data['deal_time'] = $rval['next_pay_time'] ? $rval['next_pay_time'] : 0;
                $data['create_time'] = time();
                $data['create_uid'] = $user_id;
                $JZSAA_DB->table('todo')->add($data);
            }
            $data = array();
        }
        //整租
        $house_data = $JZSAA_DB->table("rental as r")
                ->join("LEFT JOIN $db.tenant AS t
    			ON (r.tenant_id = t.tenant_id)
    			LEFT JOIN $db.tenant_contract AS tc ON(r.contract_id=tc.contract_id)
    			LEFT JOIN $db.house AS h ON(r.house_id=h.house_id)
    			LEFT JOIN $db.room AS rm ON(rm.house_id=h.house_id)")
                ->where(array("h.house_id" => "IS NOT NULL" , "r.is_delete" => 0 , "r.house_type" => 1 , "h.create_uid" => $user_id))
                ->group("h.house_id")
                ->select();
        //租客整租合同到期
        foreach ($house_data as $rkey => $hval)
        {
            $todo_data = array();
            $todo_data = $JZSAA_DB->table("todo")
                            ->where(array("entity_id" => $hval['contract_id'] , "module" => "tenant_contract" , "create_uid" => $user_id , "deal_time" => $hval['end_line']))->select();
            if (empty($todo_data) && $hval['end_line'] > time())
            {
                $data['module'] = 'tenant_contract';
                $data['entity_id'] = $hval['contract_id'];
                $data['title'] = '到期';
                $data['content'] = $hval['house_name'] . "的租客合同将于" . date("Y-m-d" , $hval['end_line']) . "到期，请注意处理";
                $data['company_id'] = $company_id;
                $data['url'] = "/index.php?c=tenant-index&a=edit&contract_id=" . $hval['contract_id'];
                $data['status'] = 0;
                $data['deal_time'] = $hval['end_line'] ? $hval['end_line'] : 0;
                $data['create_time'] = time();
                $data['create_uid'] = $user_id;
                $JZSAA_DB->table('todo')->add($data);
            }
            $data = array();
        }
        //租客整租合同收租
        foreach ($house_data as $rkey => $hval)
        {
            $todo_data = array();
            $todo_data = $JZSAA_DB->table("todo")
                            ->where(array("entity_id" => $hval['contract_id'] ,
                                "module" => "tenant_contract_shouzu" ,
                                "create_uid" => $user_id ,
                                "deal_time" => $hval['next_pay_time']))->select();

            if (empty($todo_data) && $hval['end_line'] > time())
            {
                $data['module'] = 'tenant_contract_shouzu';
                $data['entity_id'] = $hval['contract_id'];
                $data['title'] = '收租';
                $data['content'] = $rval['house_name'] . "的租金" . $hval['next_pay_money'] . "元应于" . date("Y-m-d" , $hval['next_pay_time']) . "收取，请注意处理";
                $data['company_id'] = $company_id;
                $data['url'] = "/index.php?c=tenant-index&a=edit&contract_id=" . $hval['contract_id'];
                $data['status'] = 0;
                $data['deal_time'] = $hval['next_pay_time'] ? $hval['next_pay_time'] : 0;
                $data['create_time'] = time();
                $data['create_uid'] = $user_id;
                $JZSAA_DB->table('todo')->add($data);
            }
            $data = array();
        }

        //预定日志
        $reserve_data_room = $JZSAA_DB->table("reserve as r")
                ->join("LEFT JOIN $db.room  AS rm ON(r.room_id=rm.room_id)
				LEFT JOIN $db.house AS h ON(rm.house_id=h.house_id)")
                ->where(array("r.is_delete" => 0 , "r.room_id" => "IS NOT NULL" , "h.create_uid" => $user_id , "r.house_type" => 1))
                ->group("rm.room_id")->field("rm.custom_number,r.reserve_id,h.house_name,r.etime,rm.room_type")
                ->select();
        //合租预定日志
        foreach ($reserve_data_room as $rrkey => $rrval)
        {
            $todo_data = array();
            $todo_data = $JZSAA_DB->table("todo")
                            ->where(array("entity_id" => $rrval['reserve_id'] , "module" => "reserve" , "create_uid" => $user_id , "deal_time" => $rrval['etime']))->select();

            if (empty($todo_data))
            {
                $data['module'] = 'reserve';
                $data['entity_id'] = $rrval['reserve_id'];
                $data['title'] = '到期';
                $data['content'] = $rrval['house_name'] . "-" . $rrval['custom_number'] . $room_type[$rrval['room_type']] . "的预定将于" . date($rrval['etime']) . "到期，请注意处理";
                $data['company_id'] = $company_id;
                $data['url'] = "/index.php?c=tenant-index&a=showreserve&id=" . $rrval['reserve_id'];
                $data['status'] = 0;
                $data['deal_time'] = $rrval['etime'];
                $data['create_time'] = time();
                $data['create_uid'] = $user_id;
                $JZSAA_DB->table('todo')->add($data);
            }
            $data = array();
        }
        $reserve_data_house = $JZSAA_DB->table("reserve as r")
                ->join("LEFT JOIN $db.house AS h ON(r.house_id=h.house_id)
    			LEFT JOIN $db.room  AS rm ON(h.house_id=rm.house_id)")
                ->where(array("r.is_delete" => 0 , "h.create_uid" => $user_id , "r.house_type" => 1 , "rm.house_id" => "IS NOT NULL"))
                ->group("h.house_id")->field("rm.custom_number,r.reserve_id,h.house_name,r.etime,rm.room_type")
                ->select();
        //整租预定日志
        foreach ($reserve_data_house as $rhkey => $rhval)
        {
            $todo_data = array();
            $todo_data = $JZSAA_DB->table("todo")
                            ->where(array("entity_id" => $rhval['reserve_id'] , "module" => "reserve" , "create_uid" => $user_id , "deal_time" => $rhval['etime']))->select();

            if (empty($todo_data))
            {
                $data['module'] = 'reserve';
                $data['entity_id'] = $rhval['reserve_id'];
                $data['title'] = '到期';
                $data['content'] = $rhval['house_name'] . "的预定将于" . date($rhval['etime']) . "到期，请注意处理";
                $data['company_id'] = $company_id;
                $data['url'] = "/index.php?c=tenant-index&a=showreserve&id=" . $rhval['reserve_id'];
                $data['status'] = 0;
                $data['deal_time'] = $rhval['etime'];
                $data['create_time'] = time();
                $data['create_uid'] = $user_id;
                $JZSAA_DB->table('todo')->add($data);
            }
            $data = array();
        }
    }
    