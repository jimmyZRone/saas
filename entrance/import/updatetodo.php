<?php

// ini_set('display_errors',false);
    ini_set('display_errors' , true);
    define('DB_JZSAAS' , 'yzx_test');//sass表
    $_config = array(
        'DB_TYPE' => 'mysql' , // 数据库类型
        'DB_DEPLOY_TYPE' => 1 ,
        'DB_RW_SEPARATE' => true ,
        'DB_HOST' => "127.0.0.1" , // 服务器地址
        'DB_NAME' => DB_JZSAAS , // 数据库名
        'DB_USER' => 'root' , // 用户名
        'DB_PWD' => 'root' , // 密码
        'DB_PORT' => '3306' , // 端口
    );
    include 'Model.class.php';
    $JZSAA_DB = new Model(DB_JZSAAS . '.User' , '' , $_config);
    $KV = array();

    set_time_limit(0);
    ini_set('memory_limit' , '2048M');
    $TIME = round(microtime(true) , 2);//初始化开始时间
    $TIME_COUNT = 0;//总耗时记录
    define("USER_ID" , $_REQUEST['user_id']);
    $NOW = time();
    //寻找管理员
    $USER_INFO = $JZSAA_DB->table('user')->where(array('user_id' => USER_ID))->find();
    if (empty($USER_INFO)){
    	echo "用户没有找到";
    }
    //开始修改
    updatetodo();
    //生成日程
    function updatetodo()
    {
        global $JZSAA_DB , $USER_INFO;
        $db = DB_JZSAAS;
        $company_id = $USER_INFO['company_id'];
        //租客合同
        //合租
        $end_line_where['tc.end_line'] = array("gt",time());
        $room_type = array("main" => "主卧" , "second" => "次卧" , "guest" => "客卧");
        $room_data = $JZSAA_DB->table("rental as r")
                        ->join("LEFT JOIN $db.tenant AS t 
			    ON (r.tenant_id = t.tenant_id)
			  LEFT JOIN $db.tenant_contract AS tc ON(r.contract_id=tc.contract_id)
			  LEFT JOIN $db.room AS rm ON(r.room_id=rm.room_id)
			  LEFT JOIN $db.house AS h ON(rm.house_id=h.house_id)
			  LEFT JOIN $db.todo AS tod ON(tc.contract_id = tod.entity_id)")
                        ->where(array("h.company_id" => $company_id ,
                        			  "rm.status" => 2,
                        			  "h.rental_way" => 1
                        			))
                        ->where($end_line_where)
                        ->group("rm.room_id")
                        ->field("tc.contract_id,rm.custom_number,tc.end_line,h.house_name,rm.room_type,rm.house_id,tc.next_pay_time")->select();
        //租客合租合同到期
         foreach ($room_data as $rkey => $rval)
        {
        	$todo_data = $JZSAA_DB->table("todo")
        	->where(array("house_id" => $rval['house_id'] ,
        			"module" => "tenant_contract" ,
        			"company_id" => $company_id))->select();
        	
        	if (empty($todo_data)){
        		$data['module'] = 'tenant_contract';
        		$data['entity_id'] = $rval['contract_id'];
        		$data['title'] = '到期';
        		$data['content'] = $rval['house_name'] . "-" . $rval['custom_number'] . $room_type[$rval['room_type']] . "的租客合同将于" . date("Y-m-d" , $rval['end_line']) . "到期，请注意处理";
        		$data['company_id'] = $company_id;
        		$data['url'] = "/index.php?c=tenant-index&a=edit&contract_id=" . $rval['contract_id'];
        		$data['status'] = 0;
        		$data['deal_time'] = $rval['end_line'] ? $rval['end_line'] : 0;
        		$data['create_time'] = time();
        		$data['create_uid'] = $USER_INFO['user_id'];
        		$data['house_id'] = $rval['house_id'];
        		$JZSAA_DB->table('todo')->add($data);
        		echo "修复了租客合租合同到期".$data['content']."<br/>";
        	}
        }
        //租客合租合同收租
         foreach ($room_data as $rkey => $rval)
        {
        	$todo_data = $JZSAA_DB->table("todo")
        	->where(array("house_id" => $rval['house_id'] ,
        			"module" => "tenant_contract_shouzu" ,
        			"company_id" => $company_id))->select();
        	
        	if (empty($todo_data)){
        		$data['module'] = 'tenant_contract_shouzu';
        		$data['entity_id'] = $rval['contract_id'];
        		$data['title'] = '收租';
        		$data['content'] = $rval['house_name'] . "-" . $rval['custom_number'] . $room_type[$rval['room_type']] . "的租金" . $rval['next_pay_money'] . "元应于" . date("Y-m-d" , $rval['next_pay_time']) . "收取，请注意处理";
        		$data['company_id'] = $company_id;
        		$data['url'] = "/index.php?c=tenant-index&a=edit&contract_id=" . $rval['contract_id'];
        		$data['status'] = 0;
        		$data['deal_time'] = $rval['next_pay_time'] ? $rval['next_pay_time'] : 0;
        		$data['create_time'] = time();
        		$data['create_uid'] = $USER_INFO['user_id'];
        		$data['house_id'] = $rval['house_id'];
        		$JZSAA_DB->table('todo')->add($data);
        		echo "修复了租客合租合同收租".$data['content']."<br/>";
        	}
        } 
        //整租
        $house_data = $JZSAA_DB->table("rental as r")
                ->join("LEFT JOIN $db.tenant AS t
    			ON (r.tenant_id = t.tenant_id)
    			LEFT JOIN $db.tenant_contract AS tc ON(r.contract_id=tc.contract_id)
    			LEFT JOIN $db.house AS h ON(r.house_id=h.house_id)
    			LEFT JOIN $db.house_entirel AS he ON(he.house_id = h.house_id)
    			LEFT JOIN $db.todo AS tod ON(tc.contract_id=tod.entity_id)")
                ->where(array("h.company_id" => $company_id ,
                		 "h.rental_way" => 2,
                		 "he.status" => 2
                ))
                ->where($end_line_where)
                ->group("h.house_id")
                ->field("tc.contract_id,h.custom_number,tc.end_line,h.house_name,h.house_id,tc.next_pay_time")->select();
        //租客整租合同到期
        foreach ($house_data as $rkey => $hval)
        {
        	$todo_data = $JZSAA_DB->table("todo")
        	->where(array("house_id" => $hval['house_id'] ,
        			"module" => "tenant_contract" ,
        			"company_id" => $company_id))->select();
        	
        	if (empty($todo_data)){
        		$data['module'] = 'tenant_contract';
        		$data['entity_id'] = $hval['contract_id'];
        		$data['title'] = '到期';
        		$data['content'] = $hval['house_name'] . "的租客合同将于" . date("Y-m-d" , $hval['end_line']) . "到期，请注意处理";
        		$data['company_id'] = $company_id;
        		$data['url'] = "/index.php?c=tenant-index&a=edit&contract_id=" . $hval['contract_id'];
        		$data['status'] = 0;
        		$data['deal_time'] = $hval['end_line'] ? $hval['end_line'] : 0;
        		$data['create_time'] = time();
        		$data['create_uid'] = $USER_INFO['user_id'];
        		$data['house_id'] = $rval['house_id'];
        		$JZSAA_DB->table('todo')->add($data);
        		echo "修复了租客整租合同到期".$hval['house_name']."<br/>";
        	}
        }
        //租客整租合同收租
        foreach ($house_data as $rkey => $hval)
        {
        	$todo_data = $JZSAA_DB->table("todo")
        	->where(array("house_id" => $hval['house_id'] ,
        			"module" => "tenant_contract_shouzu" ,
        			"company_id" => $company_id))->select();
        	
        	if (empty($todo_data)){
        		$data['module'] = 'tenant_contract_shouzu';
        		$data['entity_id'] = $hval['contract_id'];
        		$data['title'] = '收租';
        		$data['content'] = $rval['house_name'] . "的租金" . $hval['next_pay_money'] . "元应于" . date("Y-m-d" , $hval['next_pay_time']) . "收取，请注意处理";
        		$data['company_id'] = $company_id;
        		$data['url'] = "/index.php?c=tenant-index&a=edit&contract_id=" . $hval['contract_id'];
        		$data['status'] = 0;
        		$data['deal_time'] = $hval['next_pay_time'] ? $hval['next_pay_time'] : 0;
        		$data['create_time'] = time();
        		$data['create_uid'] = $USER_INFO['user_id'];
        		$data['house_id'] = $rval['house_id'];
        		$JZSAA_DB->table('todo')->add($data);
        		echo "修复了租客整租合同收租".$hval['house_name']."<br/>";
        	}
        }
    }
    