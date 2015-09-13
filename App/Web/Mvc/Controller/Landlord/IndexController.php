<?php

namespace App\Web\Mvc\Controller\Landlord;
use App\Web\Lib\Request;
use App\Web\Helper\Url;
use Common\Helper\Permissions;
use Common\Helper\Permissions\Hook\SysHousingManagement;
use Common\Helper\String;
use Zend\Db\Sql\Expression;
/**
 * 业主管理
 *
 * @author lishengyou
 *         最后修改时间 2015年4月1日 上午9:46:52
 *
 */
class IndexController extends \App\Web\Lib\Controller {
	protected $_auth_module_name = 'sys_property_management';

    /**
     * 业主合同列表
     * @author too|编写注释时间 2015年5月7日 上午9:16:25
     */
	public function indexAction() {
		if(!$this->verifyModulePermissions(\Common\Helper\Permissions::SELECT_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("landlord-index/index")));
		}
		$company_id = $this->user['company_id'];
		$city_id = $this->user['city_id'];
		$landlord = new \Common\Helper\Erp\Landlord ();
		$landlord_model = new \Common\Model\Erp\LandlordContract();
		// 获取搜索参数
	    $search_data = array();
		$search_data ['term_start_time'] = Request::queryString ( "get.term_start_time", "", "string" ); // 到期开始时间
		$search_data ['term_end_time'] = Request::queryString ( "get.term_end_time", "", "string" ); // 到期结束时间
		$search_data ['pay_start_time'] = Request::queryString ( "get.pay_start_time", "", "string" ); // 下次交租开始时间
		$search_data ['pay_end_time'] = Request::queryString ( "get.pay_end_time", "", "string" ); // 下次交租到期时间
		$search_data ['contract_type'] = Request::queryString ( "get.contract_type", 1, "intval" ); // 合同状态
		$search_data ['house_type'] = Request::queryString ( "get.house_type", 1, "intval" ); // 房源状态
		$search_data ['search'] = Request::queryString ( "get.search", "", "string" ); // 关键字
		$page = Request::queryString ( "get.page", 1, "intval" ); // 页码
		$is_search = Request::queryString ( "get.is_search", 0, "intval" ); // 页码
		$size = 20;
		$landlordList = $landlord->landlordList( $page, $size, $search_data, $this->user);
		//退租加未删除
		$total_id = $landlord_model->getOutLease($company_id, TRUE,$this->user);
		//未删除
		$count_id = $landlord_model->getOutLease($company_id,null,$this->user);
		$out_tenancy_rate = round(($total_id[0]['total_id'] / $count_id[0]['total_id']) * 100) . '%';

		$this->assign ( 'landlordList', $landlordList["data"] );
		$this->assign ('rate', $out_tenancy_rate);
		$this->assign ( 'is_search', $is_search );
		$this->assign ( 'page_info', $landlordList["page"] );
		$this->assign ( 'page_url',Url::parse ( "landlord-index/index?$is_search=1"));
		$this->assign("user", $this->user);
		$html = $this->fetch ();
		return $this->returnAjax ( array (
				"status" => 1,
				"tag_name" => "业主管理",
				"model_js" => "manager_landlord_listJs",
				"model_name" => "manager_landlord_list",
				"model_href"=>Url::parse("landlord-index/index"),
				"data" => $html,
				"page"=> $landlordList["page"],
		) );
	}
	/**
	 * 合同详情
	 * @author too|编写注释时间 2015年5月19日 下午5:09:46
	 */
	public function infoAction() {
	    $systemconfig = new \Common\Model\Erp\SystemConfig();
		$landlord = new \Common\Helper\Erp\Landlord ();
		$landlordContractModel = new \Common\Model\Erp\LandlordContract();
		$landlordModel = new \Common\Model\Erp\Landlord();
		$accModel = new \Common\Model\Erp\Attachments();
		//取图片
		$contract_id = Request::queryString ( "get.contract_id", 0, "intval" );
		$condata = $systemconfig->getFind($list='System',$key='Bank');//银行列表方式 用于模板展示
		$picdata = $accModel->getAllPic('landlord_contract',$contract_id);//取图片 数组拿到了,还差点事
		$contract_data = $landlordContractModel->getOne(array("contract_id"=>$contract_id,'company_id'=>$this->user['company_id']));
		if (Request::isGet()){
			$landlordHelper = new \Common\Helper\Erp\Landlord();
			$bank_data = $landlordHelper->bankCharts($condata);
			$this->assign('bankconfig', $bank_data);
			//判断用户是否对当前对象有权限操作START
			if ($this->user['is_manager'] == 0 && $this->user['company_id'] != $contract_data['company_id']){
					$contract_data = $landlordContractModel->getOne(array("contract_id"=>$contract_id,"is_delete"=>0,'company_id'=>$this->user['company_id']));
					if(!$contract_data){
						return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("landlord-index/info")));
					}
					$landlord_data = $landlordModel->getOne(array("landlord_id"=>$contract_data['landlord_id'],'company_id'=>$this->user['company_id']));
					if(!$landlord_data){
						return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("landlord-index/info")));
					}
					if ($contract_data['house_id']>0){
						if ($landlordContractModel::HOUSE_TYPE_R == $contract_data['house_type']){
							if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $contract_data['house_id'], SysHousingManagement::DECENTRALIZED_HOUSE)){
								return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("landlord-index/info")));
							}
						}
						if ($landlordContractModel::HOUSE_TYPE_F == $contract_data['house_type']){
							if(!$this->verifyDataLinePermissions(Permissions::UPDATE_AUTH_ACTION, $contract_data['house_id'], SysHousingManagement::CENTRALIZED_FLAT)){
								return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("landlord-index/info")));
							}
						}
					}else if($contract_data['house_id']<=0 && $landlord_data['create_user_id']!=$this->user['user_id'] && $this->user['is_manager']==0){
						return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("landlord-index/info")));
					}
			}
			$this->assign('picdata', $picdata);
		}
		if(Request::isPost()){
			if(!$this->verifyModulePermissions(\Common\Helper\Permissions::UPDATE_AUTH_ACTION)){
				return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("landlord-index/index")));
			}
			//修改数据
			$landlord_id = Request::queryString ( "post.landlord_id", 0, "intval" );
			$contract_id = Request::queryString ( "post.contract_id", 0, "intval" );
			$old_landlord = $landlordModel->getOne(array('landlord_id' => $landlord_id));
			$old_contract = $landlordContractModel->getOne(array('contract_id' => $contract_id));
			
			if($contract_id==0 ){
				return $this->returnAjax(array('status'=>0,'message'=>'合同错误'));
			}
			//业主相关的信息
			$name = Request::queryString ( "post.landlord_name",'');
			if(mb_strlen($name) >= 10)
			{
			    return $this->returnAjax(array('status'=>0,'message'=>'业主姓名太长'));
			}
			$landlord_data = array();
			$landlord_data ['name'] = $name;//Request::queryString ( "post.landlord_name" ); // 业主名字
			$landlord_data ['phone'] = Request::queryString ( "post.landlord_phone" ); // 业主电话
			$landlord_data ['idcard'] = Request::queryString ( "post.landlord_card" ); // 身份证号码
			$landlordModel=new \Common\Model\Erp\Landlord();

			$cid = $this->getUser()['company_id'];
			$landlordModel->Transaction();
			if($landlord_id != 0)
			{
			    $res=$landlordModel->edit(array("landlord_id"=>$landlord_id,'company_id'=>$cid), $landlord_data);
			    if(!$res)
			    {
			        $landlordModel->rollback();
			        return $this->returnAjax(array('status'=>0,'message'=>'业主信息编辑失败'));
			    }
			}else
			{
			    $landlord_data['mark'] = '这家伙很懒，所以你懂得';
			    $landlord_data['company_id'] = $cid;
			    $landlord_id = $landlordModel->insert($landlord_data);
			    if(!$contract_id)
			    {
			        $landlordModel->rollback();
			        return $this->returnAjax(array('status'=>0,'message'=>'业主信息添加失败'));
			    }
			}
			// 组装合同信息
			$landlord_contract_data = array();
			$landlord_contract_data ['custom_number'] = Request::queryString ( "post.landlord_contract_num", "" ); // 合同编号

			$landlord_contract_data ['fork_bank'] = Request::queryString ( "post.landlord_open_bank" ); // 开户支行;
			$payee = Request::queryString ( "post.landlord_payee",'');
			if(mb_strlen($payee) >= 10)
			{
			    return $this->returnAjax(array('status'=>0,'message'=>'收款人姓名太长'));
			}
			$landlord_contract_data ['payee'] = $payee; // 收款人
			$mark = Request::queryString ( "post.landlord_mark",'这个人很懒，没有备注');
			if(String::countStrLength($mark) > 400)
			{
			    return $this->returnAjax(array('status'=>0,'message'=>'备注不能超过400个字符'));
			}
			$landlord_contract_data ['mark'] = $mark; // 备注说明
			$landlord_contract_data['landlord_id'] = $landlord_id;
			$bank = Request::queryString ( "post.landlord_pay_bank",0,'int');// 付款银行
			$bks = array_keys($condata);
			if(!empty($bank) &&   !in_array($bank,$bks))
			{
			    return $this->returnAjax(array('status'=>0,'message'=>'银行信息错误'));
			}
            $bankno = Request::queryString ( "post.landlord_card_num",0,'string');
            if(!empty($bankno) && !\Common\Helper\ValidityVerification::IsBankNo($bankno))
            {
                return $this->returnAjax(array('status'=>0,'message'=>'银行卡错误'));
            }
            $landlord_contract_data ['bank_no'] = $bankno;//银行卡号
			$landlord_contract_data ['bank'] = $bank;
			$landlord_contract_data ['ascending_num'] = Request::queryString ( "post.rent_con_num",0,'string');
			$landlordContractModel = new \Common\Model\Erp\LandlordContract ();
			$res2=$landlordContractModel->edit(array("contract_id"=>$contract_id), $landlord_contract_data);
            //处理附件,杀光原图片
			if(!$accModel->delAllPic('landlord_contract',$contract_id))
			{
			    $landlordModel->rollback();
			    return $this->returnAjax(array('status'=>0,'message'=>'图片更新失败'));
			}
			//写入附件
			$picdata = Request::queryString ( "post.room_images");//接收图片数据 array
			if(count($picdata) > 9)
			{
			    $landlordModel->rollback();
			    return $this->returnAjax(array('status'=>0,'message'=>'图片最多只能9张'));
			}
			if(isset($_POST['room_images']) && empty($picdata))
			{
			    $landlordModel->rollback();
			    return $this->returnAjax(array('status'=>0,'message'=>'图片数据不能为空'));
			}
			foreach($picdata as $v)
			{
			    $pic = array(
			        'key'=>$v,
			        'module'=>'landlord_contract',
			        'entity_id'=>$contract_id
			    );
			    if(!$accModel->insertData($pic))//传的都是数组 , 直接用这个方法
			    {
			        $landlordModel->rollback();
			        return $this->returnAjax(array('status'=>0,'message'=>'图片上传失败'));
			    }
			}
			if($res2){
			    //添加数据快照
			    $source = \Common\Helper\DataSnapshot::$SNAPSHOT_LANDLORD_CONTRACT_EDIT;
			    $landlor_source = \Common\Helper\DataSnapshot::$SNAPSHOT_LANDLORD_EDIT;
			    $source_id = $contract_id;
			    $landlord_id = $landlord_id;
			    $landlord_con_res = \Common\Helper\DataSnapshot::save($source, $source_id, $old_contract);
			    $landlord_res = \Common\Helper\DataSnapshot::save($landlor_source, $landlord_id, $old_landlord);
			    if (!$landlord_con_res || !$landlord_res) {
			        $landlordModel->rollback();
			        return $this->returnAjax(array('status'=>0,'message'=>'数据快照保存失败!'));
			    }
				$landlordModel->commit();
				return $this->returnAjax(array('status'=>1,'message'=>'修改成功','tag'=>Url::parse("landlord-index/index")));
			}else{
				$landlordModel->rollback();
				return $this->returnAjax(array('status'=>0,'message'=>'修改失败'));
			}
		}

		if(!$this->verifyModulePermissions(\Common\Helper\Permissions::SELECT_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("landlord-index/info")));
		}


		$landlord_info = $landlord->getInfoByContractID ( $contract_id );//P($landlord_info);
		$bet_arr=array("零",	"一","二","三","四","五","六","七","八","九","十","十一","十二");
		$detain_str=$bet_arr[$landlord_info['detain']];
		$pay_str=$bet_arr[$landlord_info['pay']];
		if($landlord_info['house_type'] == 1)//如果是分散式 , 就要把房源名拆成楼啊单元啊栋啊等等等等咯
		{
            foreach(explode('-',$landlord_info['hosue_name']) as $k=>$v)
            {
               if($k == 0)
               {
                   $landlord_info[$k] = $v;
               }
            }
            unset($landlord_info['hosue_name']);
		}
		if(!empty($landlord_info['astype']))
		{
		    $bei = array('一','二','三','四','五','六','七','八','九','十','十一','十二','十三','十四','十五','十六','十七','十八','十九','二十','二十一','二十二','二十三','二十四','二十五');
		    foreach($landlord_info['astype'] as $k=>$v)
		    {
                $landlord_info['astype'][$k]['contract_year'] = $bei[$k];
		    }
		    $astype = $landlord_info['astype'];
		    $this->assign('astype', $astype);
		}
		$this->assign ( 'landlord_info', $landlord_info );
		$this->assign ( 'bet_arr', $bet_arr);
		$this->assign ( 'bank_arr', $condata );
		$this->assign ( 'detain_str', $detain_str );
		$this->assign ( 'pay_str', $pay_str );

		$html = $this->fetch ();
		return $this->returnAjax ( array (
				"status" => 1,
				"tag_name" => "业主详细",
				"model_js" => "manager_landlord_msgJs",
				"model_name" => "manager_landlord_msg",
				"data" => $html
		) );
	}
	/**
	 * 支付   如何得到当前的月租金是多少捏??????????????????
	 * @author too|编写注释时间 2015年5月21日 下午5:33:14
	 */
	public function payAction()
	{
		if(!$this->verifyModulePermissions(\Common\Helper\Permissions::UPDATE_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("landlord-index/pay")));
		}

	    $contract_id = Request::queryString ( "get.cid", 0, "intval" );
	    $landlordContractModel = new \Common\Model\Erp\LandlordContract ();
	    $data = $landlordContractModel->getOneLandlord($contract_id);
	    
	    if(!$data || $data['company_id'] != $this->user['company_id']){
	    	return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("landlord-index/pay")));
	    }
	    // 修改日程表
// 	    $todoModel = new \Common\Model\Erp\Todo();

// 	    $next_pay_time = date('Y-m-d',$data['next_pay_time']);
// 	    $tmptimearr = explode('-', $next_pay_time);
// 	    $tmptimearr[1] = $tmptimearr[1] + $data['pay_num'];
// 	    $next_pay_time = mktime(0,0,0,$tmptimearr[1],$tmptimearr[2],$tmptimearr[0]);

// 	    if(!$todoModel->edit(array('module'=>'landlord_contract_jiaozu','entity_id'=>$contract_id,'company_id'=>$this->getUser()['company_id']),array('deal_time'=>$next_pay_time)))
// 	    {
// 	        return $this->returnAjax(array('status'=>0,'message'=>'更新日程失败'));
// 	    }

        $all = $data['cycle'];//合同周期
        $housetype = $data['house_type'];
        $o = substr($all,0,strpos($all,'.'));
        if($_SERVER['REQUEST_TIME'] > $data['dead_line'])//支付时间超过合同周期
        {
            return $this->returnAjax(array('status'=>0,'message'=>'合同已过期'));
        }
        if($data['ascending_type'] == 1)//按比例有规律
        {
            for($i=0;$i<=$o;$i++)
            {
                $z = $i + 1;
                if($_SERVER['REQUEST_TIME'] < ($data['signing_time'] + strtotime("+$z year")-strtotime('now')))
                {
                    if($i==0)
                    {
                        $payall = $data['rent'] * $data['pay'];
                    }else
                    {
                        $payall =  $data['rent'] * pow($data['ascending_num']/100+1,$z) * $data['pay'];
                    }
                    return $this->returnAjax(array('status'=>1,'url'=>Url::parse("Finance-serial/addExpense/house_name/$data[hosue_name]/payall/$payall/cid/$contract_id/htype/$housetype/lc_source/lc_pay")));
                }
            }
        }
        if($data['ascending_type'] == 2)//按金额有规律ascending_num
        {
            for($i=0;$i<=$o;$i++)
            {
                $z = $i + 1;
                if($_SERVER['REQUEST_TIME'] < ($data['signing_time'] + strtotime("+$z year")-strtotime('now')))
                {
                    if($i==0)
                    {//echo '0';
                        $payall = $data['rent'] * $data['pay'];
                        //print_r($payall);
                    }else
                    {
                        $payall =  ($data['rent'] + $z*$data['ascending_num']) * $data['pay'];
                    }
                    return $this->returnAjax(array('status'=>1,'url'=>Url::parse("Finance-serial/addExpense/house_name/$data[hosue_name]/payall/$payall/cid/$contract_id/htype/$housetype/lc_source/lc_pay")));
                }
            }
        }
        if($data['ascending_type']  == 3 )//自定义金额
        {//echo '自定义';
            //echo '签约年='.date('Y-m-d',$data['signing_time']).'<br>';
            //echo '结束年='.date('Y-m-d',$data['dead_line']).'<br>';
            //echo '现在='.date('Y-m-d',$_SERVER['REQUEST_TIME']).'<br>';
            for($i=0;$i<=$o;$i++)
            {
                $z = $i + 1;
                //echo '年限='.date('Y-m-d',$data['signing_time'] + strtotime("+$z year")-strtotime('now')).'<br>';
                if($_SERVER['REQUEST_TIME'] < ($data['signing_time'] + strtotime("+$z year")-strtotime('now')))
                {
                    $lamodel = new \Common\Model\Erp\LandlordAscending();
                    $lsdata = $lamodel->getAll($data['contract_id']);
                    //echo ($z).'年';
                    if($i==0)
                    {
                        //print_r($lsdata[$i]['ascending_money']);
                        $payall = $lsdata[$i]['ascending_money'] * $data['pay'];
                    }else
                    {
                        //print_r($lsdata[$z]['ascending_money']);
                        $payall = $lsdata[$z]['ascending_money'] * $data['pay'];
                    }
                    return $this->returnAjax(array('status'=>1,'url'=>Url::parse("Finance-serial/addExpense/house_name/$data[hosue_name]/payall/$payall/cid/$contract_id/htype/$housetype/lc_source/lc_pay")));
                }
            }
        }
        if($data['ascending_type']  == 0 )//没有递增类型
        {
            $payall = $data['rent'] * $data['pay'];//P($payall);die;
            return $this->returnAjax(array('status'=>1,'url'=>Url::parse("Finance-serial/addExpense/house_name/$data[hosue_name]/payall/$payall/cid/$contract_id/htype/$housetype/lc_source/lc_pay")));
        }
	}
	/**
	 * 删除一首业主信息以及合同信息
	 *
	 * @author sj
	 *         最后修改时间 2015年4月21日 09:30:01
	 *
	 */
	public function deleteAction() {
		if(!$this->verifyModulePermissions(\Common\Helper\Permissions::DELETE_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("landlord-index/delete")));
		}

		$ids = Request::queryString ( "post.uid" );
		if (count ( $ids ) > 0 && is_array ( $ids )) {
		    $landlordContractModel = new \Common\Model\Erp\LandlordContract ();
		    $landlordModel = new \Common\Model\Erp\Landlord();
		    $todoModel = new \Common\Model\Erp\Todo();
		    $old_landlord_data = $landlordContractModel->getOne(array('contract_id' => $ids['contract_id'],'company_id'=>$this->user['company_id']));
		    //判断用户是否对当前对象有权限操作START
		    $contract_data = $landlordContractModel->getOne(array('contract_id'=>$ids['contract_id'],'company_id'=>$this->user['company_id']));
		    $landlord_data = $landlordModel->getOne(array("landlord_id"=>$contract_data['landlord_id'],"is_delete"=>0));
		    if ($this->user['is_manager'] == 0 && $this->user['company_id'] != $contract_data['company_id']){
			    if (!empty($contract_data) && $contract_data['house_id']>0){
			    	if ($landlordContractModel::HOUSE_TYPE_R == $contract_data['house_type']){
			    		if(!$this->verifyDataLinePermissions(Permissions::DELETE_AUTH_ACTION, $contract_data['house_id'], SysHousingManagement::DECENTRALIZED_HOUSE)){
			    			return $this->returnAjax(array('__status__'=>403));
			    		}
			    	}
			    	if ($landlordContractModel::HOUSE_TYPE_F == $contract_data['house_type']){
			    		if(!$this->verifyDataLinePermissions(Permissions::DELETE_AUTH_ACTION, $contract_data['house_id'], SysHousingManagement::CENTRALIZED_FLAT)){
			    			return $this->returnAjax(array('__status__'=>403));
			    		}
			    	}
			    }else if($contract_data['house_id']<=0 && $landlord_data['create_user_id']!=$this->user['user_id'] && $this->user['is_manager']==0){
			    	return $this->returnAjax(array('__status__'=>403));
			    }
		    }
		    //判断用户是否对当前对象有权限操作END
		    $todoModel->Transaction();
		    $userInfo = $this->getUser();
		    $condition = array(
		        //'create_uid'=>$userInfo['user_id'],
		        'company_id'=>$userInfo['company_id'],
		        'module'=>'landlord_contract',
		        'entity_id'=>$ids['contract_id'],
		    );
		    $conditionJ = array(
		        //'create_uid'=>$userInfo['user_id'],
		        'company_id'=>$userInfo['company_id'],
		        'module'=>'landlord_contract_jiaozu',
		        'entity_id'=>$ids['contract_id'],
		    );
		    if(!$todoModel->delete($condition) || !$todoModel->delete($conditionJ))
		    {
		        $todoModel->rollback();
		        return $this->returnAjax(array('status'=>0,'message'=>'日程删除失败'));
		    }
			$data = $landlordContractModel->getOne(array('is_delete'=>0,'contract_id'=>$ids['contract_id']));
			if($data['is_stop'] == 1)
			{
			    if(!$landlordContractModel->edit ( array ('contract_id' => $ids['contract_id']), array ('is_delete' => 1) ))
			    {
			        $todoModel->rollback();
			        return $this->returnAjax(array('status'=>0,'message'=>'删除失败'));
			    }
			    if (!$landlordModel->edit(array('landlord_id' => $ids['landlord_id']), array ('is_delete' => 1))) {
			        return $this->returnAjax(array('status'=>0,'message'=>'删除业主失败'));
			    }
			}else
			{
			    $todoModel->rollback();
			    return $this->returnAjax(array('status'=>0,'message'=>'删除失败！合同未终止'));
			}
			//数据快照删除业主合同
			$source = \Common\Helper\DataSnapshot::$SNAPSHOT_LANDLORD_CONTRACT_DELETE;
			$source_id = $ids['contract_id'];
			$snapshot_res = \Common\Helper\DataSnapshot::save($source, $source_id, $old_landlord_data);
			if (!$snapshot_res) {
			    return $this->returnAjax(array('status'=>0,'message'=>'数据快照添加失败'));
			}
			$todoModel->commit();
			return $this->returnAjax ( array (
					'status' => 1,
					'message' => '删除成功'
			) );
		} else {
		    $todoModel->rollback();
			return $this->returnAjax ( array (
					'status' => 0,
					'message' => '请选择要删除的记录'
			) );
		}
	}
	/**
	 * 终止业主合同
	 * landlord_contract表   is_settlement=1已结算  end_line=$_SERVER['REQURES_TIME']
	 * @author too|编写注释时间 2015年5月19日 下午5:10:56
	 */
	public function stoplandlordAction()
	{
		if(!$this->verifyModulePermissions(\Common\Helper\Permissions::UPDATE_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("landlord-index/stoplandlord")));
		}

	    $contract_id = I('get.contract_id',0,'int');//接收业主合同
	    $landlordContractModel = new \Common\Model\Erp\LandlordContract ();
	    $old_lanklord_data = $landlordContractModel->getOne(array('contract_id' => $contract_id,'company_id'=>$this->user['company_id']));
	    if(!$old_lanklord_data){
	    	return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("landlord-index/stoplandlord")));
	    }
	    // 删除日程表 合同id 模块名 公司id 创建者id
	    $userInfo = $this->getUser();
	    $daoqi_condition = array(
	        //'create_uid'=>$userInfo['user_id'],
	        'company_id'=>$userInfo['company_id'],
	        'module'=>'landlord_contract',
	        'entity_id'=>$contract_id,
	    );
	    $jiaozu_condition = array(
	        //'create_uid'=>$userInfo['user_id'],
	        'company_id'=>$userInfo['company_id'],
	        'module'=>'landlord_contract_jiaozu',
	        'entity_id'=>$contract_id,
	    );
	    $todoModel = new \Common\Model\Erp\Todo();
	    $todoModel->Transaction();
	    if(!$todoModel->delete($daoqi_condition))
	    {
	        $todoModel->rollback();
	        return $this->returnAjax(array('status'=>0,'message'=>'到期日程删除失败'));
	    }
	    if(!$todoModel->delete($jiaozu_condition))
	    {
	        $todoModel->rollback();
	        return $this->returnAjax(array('status'=>0,'message'=>'交租日程删除失败'));
	    }
	    if(!$landlordContractModel->stopLandlord($contract_id))
	    {
	        $todoModel->rollback();
	        return $this->returnAjax(array('status'=>0,'message'=>'合同终止失败'));
	    }
	    //数据快照 终止业主合同
        $source = \Common\Helper\DataSnapshot::$SNAPSHOT_LANDLORD_CONTRACT_STOP;
        $source_id = $contract_id;
        \Common\Helper\DataSnapshot::save($source, $source_id, $old_lanklord_data);
	    $todoModel->commit();
	    return $this->returnAjax(array('status'=>1,'message'=>'合同终止成功',
	        'tag'=>Url::parse ( "landlord-index/index" ),
	        'url'=>Url::parse("Finance-serial/addIncome/Landlord_contract_id/$contract_id")
	    ));
	}
	/**
	 * 新增业主合同
         * 新增的费用 , 把房源id+一起写进fee表和fee_type表
	 * @author too|编写注释时间 2015年5月7日 下午1:17:22
	 */
	public function addAction() {
		if(!$this->verifyModulePermissions(\Common\Helper\Permissions::INSERT_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("landlord-index/add")));
		}

	    $systemconfig = new \Common\Model\Erp\SystemConfig();
	    $condata = $systemconfig->getFind($list='System',$key='Bank');//银行列表方式 用于模板展示
	    $landlordHelper = new \Common\Helper\Erp\Landlord();
	    $bank_data = $landlordHelper->bankCharts($condata);
	    $this->assign('bankconfig', $bank_data);
	    //如果没有post提交
		if (!Request::isPost ()) {
			$user = $this->user;
			$communityModel = new \Common\Model\Erp\Community();
		    $add_url = Url::parse ( "landlord-index/add" );
		    $search_url= Url::parse ( "landlord-index/searchHouseInfo" );
		    $house_id = Request::queryString("get.house_id",0,"int");
		    $house_type = Request::queryString("get.house_type",0,"int");
		    /**
		     * 分散式添加房间跳转过来添加合同
		     */
		    if ($house_type==1){
		    	$houseModel = new \Common\Model\Erp\House();
		    	$house_data = $houseModel->getOne(array("house_id"=>$house_id));
		    	$community_data = $communityModel->getOne(array("community_id"=>$house_data['community_id']));
		    	$this->assign("community", $community_data);
		    	$this->assign("house_data", $house_data);
		    }
		    if ($house_type==2){
		    	$flatModel = new \Common\Model\Erp\Flat();
		    	$flat_data = $flatModel->getOne(array("flat_id"=>$house_id));
		    	$this->assign("flat_data", $flat_data);
		    }
		    $this->assign("house_type", $house_type);
		    $this->assign ( "add_url", $add_url );
		    $this->assign ( "search_url", $search_url );
		    $landlord = new \App\Web\Helper\Landlord ();
		    $allcity = $landlord->getAllCity();
		    $this->assign("user", $user);
		    $this->assign('allcity', $allcity);//所有城市
		    $html = $this->fetch ();
		    return $this->returnAjax ( array (
		        "status" => 1,
		        "tag_name" => "添加业主",
		        "model_js" => "manager_landlord_msgJs",
		        "model_name" => "manager_landlord_msg",
		        "model_href" => Url::parse ( "landlord-index/add" ),
		        "data" => $html
		    ) );
		}
		//如果有post提交
		// 获取当前用户登录信息
		$user_info = $this->getUser();
		$user_id = $user_info ['user_id'];
		$company_id = $user_info ['company_id'];
		$city_id = $user_info['city_id'];
		//收集业主信息
		$phone = Request::queryString ( "post.landlord_phone" );
		$idcard = Request::queryString ( "post.landlord_card" );
		if(!empty($idcard) && isset($idcard{120}))
		{
		    return $this->returnAjax(array('status'=>0,'message'=>'证件号码格式不正确'));
		}
		$name = Request::queryString ( "post.landlord_name" );
		if(mb_strlen($name) >= 30)
		{
		    return $this->returnAjax(array('status'=>0,'message'=>'业主名称不能大于10个汉字'));
		}
		$landlord_data = array(
		    'name'=>$name,
		    'phone'=>$phone,
		    'idcard'=>$idcard,
		    'create_time'=>$_SERVER['REQUEST_TIME'],
		    'create_user_id'=>$user_id,
		    'company_id'=>$company_id,
		    'mark'=>'这个人好懒,啥子都没写'//字段没有设置默认值,此处也没有提交,故在此加入默认值
		);
		// 开启事务
		$landlord = new \Common\Helper\Erp\Landlord ();
		$landlordContractModel = new \Common\Model\Erp\LandlordContract ();
		$landlord_pk =  0;
		$landlordContractModel->Transaction();
		$oo = new \Common\Model\Erp\Landlord ();
        if(!empty($landlord_data['idcard']) && !empty($landlord_data['phone']) && !empty($landlord_data['name']))
        {
            $ttppdata = $oo->getLandlord($landlord_data['idcard'],$company_id);//查业主是否存在
            if(!empty($ttppdata))
            {
                $landlord_pk = $ttppdata['landlord_id'];
            }else {
                $landlord_pk = $landlord->addLandlord($landlord_data); //写入业主表
            }
        }else
        {
            return $this->returnAjax(array('status'=>0,'message'=>'业主姓名/业主电话/业主身份证必须填写'));
        }


		//if ($landlord_pk) {
		    $pay_rent_ways = I('post.pay_rent_ways','');//1比例 2金额
		    $ascending_type = 0;
		    $is_ascending = 0;
		    if(!empty($pay_rent_ways))
		    {
		        $is_ascending = 1;
		        $ascending_type = $pay_rent_ways;
		    }

	        //$end_line = Request::queryString( "post.landlord_end_time")?strtotime ( Request::queryString( "post.landlord_end_time") ):0;
	        $select_free_month = I("landlord_month", 0);
	        $select_free_day = I("landlord_day", 0);
	        $free_day = $select_free_month ? $select_free_month : $select_free_day;
	        $mark = Request::queryString("post.landlord_mark","xxxx","string");
	        $rent = Request::queryString ( "post.landlord_rent")?Request::queryString ( "post.landlord_rent"):0;
	        $deposit = Request::queryString ( "post.landlord_deposit")?Request::queryString ( "post.landlord_deposit"):0;
	        $advance_time = Request::queryString ( "post.landlord_advance_pay")?Request::queryString ( "post.landlord_advance_pay",0):0;
	        $cycle = Request::queryString ( "post.cycle")?Request::queryString ( "post.cycle"):0;
	        $signing_time = Request::queryString ("post.landlord_start_time")?strtotime (Request::queryString ("post.landlord_start_time",0)):0;
	        $detain = Request::queryString ( "post.landlord_bet_num")?Request::queryString ( "post.landlord_bet_num"):0;
	        $pay = Request::queryString ( "post.landlord_pay_num")?Request::queryString ( "post.landlord_pay_num"):0;
	        $dead_line = Request::queryString ( "post.landlord_end_time")?strtotime ( Request::queryString ( "post.landlord_end_time",0)):0;
	        $house_id = Request::queryString("post.house_id",0,"int");
	        $testx = I('post.rent_con_num','');
	        $ascending_num = 0;
	        if(!empty($testx) && !is_array($testx))//增量不是数组,就直接存  是数组 ,就在下面插入另一个表
	        {
	            //$ascending_num = $rent * ($testx/100);
	            $ascending_num = $testx;
	        }
// 	        if($signing_time > $dead_line)
// 	        {
// 	            return $this->returnAjax(array('status'=>0,'message'=>'合同周期不能小于1天'));
// 	        }
            if($rent < 0)
            {
                return $this->returnAjax(array('status'=>0,'message'=>'租金填写错误!'));
            }
	        if(String::countStrLength($mark) > 400)
	        {
	            return $this->returnAjax(array('status'=>0,'message'=>'备注不能超过400个字符'));
	        }
	        $bankno = Request::queryString ( "post.landlord_card_num",'');
	        if(!empty($bankno) &&  !\Common\Helper\ValidityVerification::IsBankNo($bankno))
	        {
	            return $this->returnAjax(array('status'=>0,'message'=>'银行卡错误'));
	        }
	        $bank = Request::queryString ( "post.landlord_pay_bank",0,'int');// 付款银行
	        $bks = array_keys($condata);
	        if(!empty($bank) &&  !in_array($bank,$bks))
	        {
	            return $this->returnAjax(array('status'=>0,'message'=>'银行信息错误'));
	        }
	        //$detain $pay
	        $detainarr = array(0,1,2,3,4,5,6,7,8,9,10,11,12);//用于判断用户输入是否合法
	        $payarr = array(1,2,3,4,6,12);//用于判断用户输入是否合法
            if(!empty($detain) && !empty($pay) &&  !in_array($detain, $detainarr) && !in_array($pay, $payarr))
            {
                return $this->returnAjax(array('status'=>0,'message'=>'押付模式错误'));
            }
            $hosue_name = Request::queryString( "post.landlord_house",'');
            if(empty($hosue_name))
            {
                return $this->returnAjax(array('status'=>0,'message'=>'房源名不能为空'));
            }

            //$house_id = $cid;//集中式时保存
            $payee = Request::queryString ( "post.landlord_payee",'');
            if(mb_strlen($payee) >= 30)
            {
                return $this->returnAjax(array('status'=>0,'message'=>'收款人姓名太长'));
            }
		    $landlord_contract_data = array(
		        'company_id'=>$this->getUser()['company_id'],
		        'landlord_id'=>$landlord_pk,//业主id
		        'custom_number'=>Request::queryString ("post.landlord_contract_num",0),//合同编号
		        'rent'=>$rent,//租金
		        'first_rent'=>$rent,//租金
		        'deposit'=>$deposit,//,//押金
		        'bank'=>$bank,//,//支付银行
		        'fork_bank'=>Request::queryString ( "post.landlord_open_bank",''),//开户支行
		        'bank_no'=>$bankno,//银行卡号
		        'payee'=>$payee,//收款人姓名
		        'advance_time'=>$advance_time>0?$advance_time:0,//提前付款天数
		        'free_day'=>$free_day>0?$free_day:0,//免租期
		        'free_day_uite' => $select_free_month ? 1 : ($select_free_day ? 2 : 0),
		        'is_ascending'=>$is_ascending,//租金递增1是 2否 字段默认值0
		        'ascending_num'=>$ascending_num,//递增量rent_con_num
		        'ascending_type'=>$ascending_type,//递增类型1/按比例;2/按金额3自定义
		        'pay_year'=>1,//第几年付款
		        'cycle'=>$cycle,//,//合同周期
		        //'ascending_money'=>,//上一次递增后钱
		        'signing_time'=>$signing_time,//,//签约时间
		        'detain'=>$detain,////押
		        'pay'=>$pay,//Request::queryString ( "post.landlord_pay_num",0),//付
		        'dead_line'=>$dead_line,//strtotime ( Request::queryString ( "post.landlord_end_time",19700103) ),//合同到期时间戳
		        'end_line'=>$dead_line,//,//手动终止时间,默认等于到期时间
		        'house_id'=>$house_id,//Request::queryString ( "post.landlord_idx",0),//房间id   业务还不清楚 , 暂时写死
		        'hosue_name'=>$hosue_name,//Request::queryString( "post.landlord_house",''),//房间名   必须有    build 栋    unit 单元  floor 楼     num 号
		        'house_type'=>Request::queryString ( "post.house_type",''),//房间类型  必须有
		        'mark'=>$mark,//备注
		        'is_settlement'=>0,//是否结算 0否1是
		        'city_id'=>$city_id
		    );
		    //计算下次付款时间
		    $p = $landlord_contract_data['pay'];
		    if($p == 0)
		    {
		        $landlord_contract_data['next_pay_time'] = 0;
		    }else
		    {
		        $landlord_contract_data['next_pay_time'] = $signing_time;
		    }
		    //计算下次付款金额            判断下次付款时间属于合同第几年 , 再根据相应年份计算
		    $ynh = strtotime('+1 year');
		    if($landlord_contract_data['next_pay_time'] < $ynh)//如果下次付款时间小于和合同第二年
		    {//echo '一年内';
		        $landlord_contract_data['next_pay_money'] = $p * $landlord_contract_data['rent'];
		    }else
		    {//下次付款是第二年,那么月租金要用增长后的  ,增长分金额和比例

		        if(is_array($testx) && count($testx)>0)
		        {

		            $landlord_contract_data['next_pay_money'] = $p * $testx[1];//自定义每年金额的情况  计算下次付款
		        }
                if($pay_rent_ways == 1 && !is_array($testx))
                {
                    $landlord_contract_data['next_pay_money'] = $p * $landlord_contract_data['rent'] * ($ascending_num/100 + 1);//按比例计算下次付款
                }
                if($pay_rent_ways == 2 && !is_array($testx))
                {
                    $landlord_contract_data['next_pay_money'] = $p * ($ascending_num+$landlord_contract_data['rent']);//按金额计算下次付款
                }
                //没有递增类型时
                 $landlord_contract_data['next_pay_money'] = $p * $rent;
		    }
		    if($signing_time == 0)
		    {
		        $landlord_contract_data['next_year'] = 0;
		    }else
		    {
		        $landlord_contract_data['next_year'] = date('Y',$landlord_contract_data['signing_time'])+1;//下一年付款年份
		    }
		    $a = Request::queryString ( "post.landlord_house", "" );
		    $b = Request::queryString ( "post.build", "" );
		    $c = Request::queryString ( "post.unit", "" );
		    $d = Request::queryString ( "post.floor", "" );
		    $e = Request::queryString ( "post.num", "" );
		    if($landlord_contract_data['house_type'] == 1)
		    {
		        $cid = I('post.community_id',0);//接收小区id
		        $tmpserach = array($cid,$b,$c,$d,$e);//用于添加成功后的搜索
		        $houseModel = new \App\Web\Helper\House();//P($tmpserach);
		        $result = $houseModel->getInfo($tmpserach);//看添加的房源是否存在
		        if($house_id<=0)
		        {
		            $landlord_contract_data['house_id'] =0;//分散式时house_id就存小区id
		        }
		        $landlord_contract_data['community_id'] = $cid;//分散式时多写一个小区编号
		      //  $landlord_contract_data['hosue_name'] = $a.'-'.$b.'栋'.'-'.$c.'单元'.'-'.$d.'楼'.'-'.$e.'号';//重新组装  build 栋    unit 单元  floor 楼     num 号
		        $unit = $c != '' ? $c . "单元" : '';
		        $build = $b != '' ? $b . "栋" : '';
		        $floor = $d != '' ? $d . "楼" : '';
		        $num = $e != '' ? $e . "号" : '';
		        if ($build) {
    		        $landlord_contract_data['hosue_name'] = $a . "-" . $build;
		        } 
		        if ($unit) {
    		        $landlord_contract_data['hosue_name'] = $a . "-" . $build . $unit;
		        } 
		        if ($floor) {
    		        $landlord_contract_data['hosue_name'] = $a . "-" . $build . $unit . $floor;
		        } 
		        if ($num) {
    		        $landlord_contract_data['hosue_name'] = $a . "-" . $build . $unit . $floor . $num;
		        } else {
    		        $landlord_contract_data['hosue_name'] = $a;
		        }
		    }else
		    {
		        $commodel = new \Common\Model\Erp\Flat();
		        $data = $commodel->getOne(array('flat_name'=>$landlord_contract_data['hosue_name']));
		        if($data)
		        {
		            $landlord_contract_data['house_id'] = $data['flat_id'];//集中时house_id就存公寓id
		            $flat_id = $data['flat_id'];
		        }
		    }
		    if(empty($landlord_contract_data['house_type']) || empty($landlord_contract_data['hosue_name']))
		    {
		        $landlordContractModel->rollback();
		        return $this->returnAjax(array('status'=>0,'message'=>'房源相关参数不能为空'));
		    }
		    // 判断提前付款时间的合法区间
		    if($signing_time != 0 && $p != 0)
		    {
		        $predict_next_pay_time = strtotime("+{$p} month", $signing_time);
		        if((($predict_next_pay_time - $signing_time)/86400) < $advance_time)
		        {
		            return $this->returnAjax(array('status'=>0,'message'=>'提前付款天数不能大于支付周期时间'));
		        }
		    }
		    $landlordContract_pk = $landlordContractModel->addLandlordContract($landlord_contract_data);//写入业主合同表
		    
		    if ($landlordContract_pk) {
		        // 再写一个日程表嘞
		        $noticeTime = date('Y-m-d',$dead_line);
		        $dataTodo = array(
		            'module'=>'landlord_contract', // 模块
		            'entity_id'=>$landlordContract_pk, // 实体id 合同id
		            'title'=>'到期', // 标题 例如【合同到期】
		            'content'=> $landlord_contract_data['hosue_name'].'的业主合同将于'.$noticeTime.'到期，请注意处理', // 内容
		            'company_id'=>$company_id, // 公司id
		            'url'=>'/index.php?c=landlord-index&a=info&contract_id='.$landlordContract_pk, // 跳转地址
		            'status'=> 0,// 状态 0/未处理,1/已查看,2/已处理
		            'deal_time'=>$dead_line, // 处理时间
		            'create_time'=>$_SERVER['REQUEST_TIME'],
		            'create_uid'=> $user_id, // 创建人
		            'house_id'=>$flat_id>0?0:$house_id,
		        	'flat_id'=>$flat_id>0?$flat_id:0,
		        );
		        $todoModel = new \Common\Model\Erp\Todo();
		        if(!$todoModel->addTodo($dataTodo))
		        {
		            $landlordContractModel->rollback();
		            return $this->returnAjax(array('status'=>0,'message'=>'写入日程失败'));
		        }
		        // 再写一个下次交租的日程
		        $next_pay_time = date('Y-m-d',$landlord_contract_data['next_pay_time']);
		        $dataTodoJ = array(
		            'module'=>'landlord_contract_jiaozu', // 模块
		            'entity_id'=>$landlordContract_pk, // 实体id 合同id
		            'title'=>'交租', // 标题 例如【合同到期】
		            'content'=>$landlord_contract_data['hosue_name'].'的租金'.$pay*$rent.'元'.'应于'.$next_pay_time.'支付，请注意处理', // 内容
		            'company_id'=>$company_id, // 公司id
		            'url'=>'/index.php?c=finance-serial&a=addexpense&contract_id='.$landlordContract_pk, // 跳转地址
		            'status'=> 0,// 状态 0/未处理,1/已查看,2/已处理
		            'deal_time'=>$landlord_contract_data['next_pay_time'], // 处理时间
		            'create_time'=>$_SERVER['REQUEST_TIME'],
		            'create_uid'=> $user_id, // 创建人
		            'house_id'=>$flat_id>0?0:$house_id,
		        	'flat_id'=>$flat_id>0?$flat_id:0,
		        );
		        if(!$todoModel->addTodo($dataTodoJ))
		        {
		            $landlordContractModel->rollback();
		            return $this->returnAjax(array('status'=>0,'message'=>'写入交租日程失败'));
		        }
		        // 分散式?那再写一个表咯
		        if($landlord_contract_data['house_type'] == 1)
		        {
		            $ttdata = array(
		                'contract_id' => $landlordContract_pk,
		                'community_id' => $cid,
		                'cost'=>Request::queryString("post.build", 0, 'int'),
		                'unit'=>Request::queryString('post.unit', 0, 'int'),
		                'floor'=>Request::queryString('post.floor', 0, 'int'),
		                'number'=>Request::queryString('post.num', 0, 'int'),
		            );
	                if($ttdata['contract_id'] == '' || $ttdata['community_id'] == '')
	                {
	                    $landlordContractModel->rollback();
	                    return $this->returnAjax(array('status'=>0,'message'=>'扩展信息不能为空'));
	                }
                    $dismodel = new \Common\Model\Erp\DistributedLandlordContract();
                    if(!$dismodel->add($ttdata))
                    {
                        $landlordContractModel->rollback();
                        return $this->returnAjax(array('status'=>0,'message'=>'扩展信息写入失败'));
                    }
		        }
		        //写入递增数组 , 另一个表
		        if(is_array($testx))
		        {
		            $lamodel = new \Common\Model\Erp\LandlordAscending();
		            $p = 0;
		            foreach($testx as $tx)
		            {
		                $LA = array(
		                    'contract_id'=>$landlordContract_pk,
		                    'contract_year'=>$p++,//合同年 循环+1
		                    'ascending_money'=>$tx//每年递增后的钱
		                );
		                if(!$lamodel->add($LA))
		                {
		                    $landlordContractModel->rollback();
		                    return $this->returnAjax(array('status'=>0,'message'=>'递增金额写入失败'));
		                }
		            }
		        }
		        //写入附件
		        $picdata = Request::queryString ( "post.room_images",'');//接收图片数据 array
		        if(count($picdata) > 9)
		        {
		            $landlordContractModel->rollback();
		            return $this->returnAjax(array('status'=>0,'message'=>'图片最多只能9张'));
		        }
		        if(isset($_POST['room_images']) && empty($picdata))
		        {
		            $landlordContractModel->rollback();
		            return $this->returnAjax(array('status'=>0,'message'=>'图片数据不能为空'));
		        }
		        $picmodel = new \Common\Model\Erp\Attachments();
		        foreach($picdata as $v)
		        {
		            $pic = array(
		                'key'=>$v,
		                'module'=>'landlord_contract',
		                'entity_id'=>$landlordContract_pk
		            );
		            if(!$picmodel->insertData($pic))//传的都是数组 , 直接用这个方法
		            {
		                $landlordContractModel->rollback();
		                return $this->returnAjax(array('status'=>0,'message'=>'图片上传失败'));
		            }
		        }
		        $landlordContractModel->commit ();
		        $payall = $landlord_contract_data['rent'] * $landlord_contract_data['pay'];//跳转财务租金
		        $detainall = $landlord_contract_data['deposit'];//跳转财务押金
		        if($landlord_contract_data['house_type'] == 1)
		        {//查房源
                    if (!$result)
                    {//echo '新增';die;
                        return $this->returnAjax ( array (
                            'status' => 1,
                            'message' => '业主合同新增成功',
                            'tag' => Url::parse ( "landlord-index/index" ),
                            'url'=>Url::parse("Finance-serial/addExpense/house_name/$landlord_contract_data[hosue_name]/payall/$payall/detainall/$detainall/source_type/$landlord_contract_data[house_type]/source_id/$landlordContract_pk/source/owner_contract"),
                            'newtag' => Url::parse ( "house-house/add/cid/$landlordContract_pk"),//新开添加房源标签
                        ) );
                    }//echo '不新增';die;
                    return $this->returnAjax ( array (
                        'status' => 1,
                        'message' => '业主合同新增成功',
                        'tag' => Url::parse ( "landlord-index/index" ),
                        'url'=>Url::parse("Finance-serial/addExpense/house_name/$landlord_contract_data[hosue_name]/payall/$payall/detainall/$detainall/source_type/$landlord_contract_data[house_type]/source_id/$landlordContract_pk/source/owner_contract"),
                    ) );

		        }else
		        {//查公寓
		            if(empty($data))
		            {
		                return $this->returnAjax ( array (
		                    'status' => 1,
		                    'message' => '业主合同新增成功',
		                    'tag' => Url::parse ( "landlord-index/index" ),
		                    'url'=>Url::parse("Finance-serial/addExpense/house_name/$landlord_contract_data[hosue_name]/payall/$payall/detainall/$detainall/source_type/$landlord_contract_data[house_type]/source_id/$landlordContract_pk/source/owner_contract"),
		                    'newtag' => Url::parse ( "centralized-flat/add/cid/$landlordContract_pk"),//新开添加公寓标签
		                ) );
		            }
		            return $this->returnAjax ( array (
		                'status' => 1,
		                'message' => '业主合同新增成功',
		                'tag' => Url::parse ( "landlord-index/index" ),
		                'url'=>Url::parse("Finance-serial/addExpense/house_name/$landlord_contract_data[hosue_name]/payall/$payall/detainall/$detainall/source_type/$landlord_contract_data[house_type]/source_id/$landlordContract_pk/source/owner_contract"),

		            ) );
		        }
		    } else {
		        $landlordContractModel->rollback();
		        return $this->returnAjax ( array (
		            'status' => 0,
		            'message' => '业主合同新增失败'
		        ) );
		    }
	}
	/**
	 * 续租合同
	 * @author too|编写注释时间 2015年5月15日 下午1:44:34
	 */
	public function reletlandlordAction()
	{
		if(!$this->verifyModulePermissions(\Common\Helper\Permissions::UPDATE_AUTH_ACTION) || !$this->verifyModulePermissions(\Common\Helper\Permissions::INSERT_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("landlord-index/reletlandlord")));
		}

	    $systemconfig = new \Common\Model\Erp\SystemConfig();
	    $condata = $systemconfig->getFind($list='System',$key='Bank');//银行列表方式 用于模板展示
	    $this->assign('bankconfig', $condata);
	    $add_url = Url::parse ( "landlord-index/add" );
	    $search_url= Url::parse ( "landlord-index/searchHouseInfo" );
	    $this->assign ( "add_url", $add_url );
	    $this->assign ( "search_url", $search_url );
//开始
	    $pid = I('get.pid','');//接收父合同id
	    $landlord = new \App\Web\Helper\Landlord ();
	    $allcity = $landlord->getAllCity();
	    $this->assign('allcity', $allcity);//所有城市
	    $landlord = new \Common\Helper\Erp\Landlord ();
	    $landlord_info = $landlord->getInfoByContractID ( $pid );//合同信息包括扩展信息
	    if(!$landlord_info || $landlord_info['company_id'] != $this->user['company_id']){
	    	return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("landlord-index/reletlandlord")));
	    }
	    if($landlord_info['dead_line'] != 0)
	    {
	        $landlord_info['dead_line'] = $landlord_info['dead_line'] + 86400;
	    }
	    if($landlord_info['house_type'] == 1)//如果是分散式 , 就要把房源名拆成楼啊单元啊栋啊等等等等咯
	    {
	        foreach(explode('-',$landlord_info['hosue_name']) as $k=>$v)
	        {
	            if($k == 0)
	            {
	                $landlord_info[$k] = $v;
	            }
	        }
	        unset($landlord_info['hosue_name']);
	    }
	    $bet_arr=array("零",	"一","二","三","四","五","六","七","八","九","十","十一","十二");
	    $bank_arr = array('中国工商银行','中国建设银行','中国农业银行','中国邮政储蓄银行','招商银行');
	    $detain_str=$bet_arr[$landlord_info['detain']];
	    $pay_str=$bet_arr[$landlord_info['pay']];
	    $landlord_info['max_time'] = date('Y-m-d', ($landlord_info['end_line'] + 86400));

	    $this->assign ( 'landlord_info', $landlord_info );//P($landlord_info);
	    $this->assign ( 'bet_arr', $bet_arr );
	    $this->assign ( 'detain_str', $detain_str );
	    $this->assign ( 'pay_str', $pay_str );
	    $this->assign('bank_arr', $bank_arr);
	    if (!Request::isPost ()) {
	        $html = $this->fetch ('add');
	        return $this->returnAjax ( array (
	            "status" => 1,
	            "tag_name" => "续租业主",
	            "model_js" => "manager_landlord_msgJs",
	            "model_name" => "manager_landlord_msg",
	            "model_href" => Url::parse ( "landlord-index/add" ),
	            "data" => $html
	        ) );
	    }
	    // 获取当前用户登录信息
	    $user_info = $this->getUser();
	    $user_id = $user_info ['user_id'];
	    $company_id = $user_info ['company_id'];
	    $city_id = $user_info['city_id'];
	    //收集业主信息
	    $name = Request::queryString ( "post.landlord_name",'');
	    if(mb_strlen($name) >= 30)
	    {
	        return $this->returnAjax(array('status'=>0,'message'=>'业主名称不能大于10个汉字'));
	    }
	    $landlord_data = array(
	        'name'=>$name,
	        'phone'=>Request::queryString ( "post.landlord_phone" ),
	        'idcard'=>Request::queryString ( "post.landlord_card" ),
	        'create_time'=>$_SERVER['REQUEST_TIME'],
	        'create_user_id'=>$user_id,
	        'company_id'=>$company_id,
	        'mark'=>'这个人好懒,啥子都没写'//字段没有设置默认值,此处也没有提交,故在此加入默认值
	    );
	    // 开启事务
	    $landlord = new \Common\Helper\Erp\Landlord ();
	    $landlordContractModel = new \Common\Model\Erp\LandlordContract ();
	    $landlordContractModel->Transaction();
	    $oo = new \Common\Model\Erp\Landlord ();
	    $landlord_pk =  0;
	    if(!empty($landlord_data['idcard']) && !empty($landlord_data['phone']) && !empty($landlord_data['name']))
	    {
	        $ttppdata = $oo->getLandlord($landlord_data['idcard'],$company_id);//查业主是否存在
	        if(!empty($ttppdata))
	        {
	            $landlord_pk = $ttppdata['landlord_id'];
	        }else {
	            $landlord_pk = $landlord->addLandlord($landlord_data); //写入业主表
	        }
	    }
	        $pay_rent_ways = I('post.pay_rent_ways','');//1比例 2金额
	        $ascending_type = 0;
	        $is_ascending = 0;
	        if(!empty($pay_rent_ways))
	        {
	            $is_ascending = 1;
	            $ascending_type = $pay_rent_ways;
	        }
	        
	        $end_line = Request::queryString( "post.landlord_end_time")?strtotime ( Request::queryString( "post.landlord_end_time") ):0;
	        $free_day = Request::queryString( "post.landlord_term")?Request::queryString( "post.landlord_term",0):0;
	        $mark = Request::queryString("post.landlord_mark","这个人好懒啊","string");
	        $rent = Request::queryString ( "post.landlord_rent")?Request::queryString ( "post.landlord_rent"):0;
	        $deposit = Request::queryString ( "post.landlord_deposit")?Request::queryString ( "post.landlord_deposit"):0;
	        $advance_time = Request::queryString ( "post.landlord_advance_pay")?Request::queryString ( "post.landlord_advance_pay"):0;
	        $cycle = Request::queryString ( "post.cycle")==NaN?Request::queryString ( "post.cycle"):0;
	        $signing_time = Request::queryString ("post.landlord_start_time")?strtotime (Request::queryString ("post.landlord_start_time",0)):0;
	        $detain = Request::queryString ( "post.landlord_bet_num")?Request::queryString ( "post.landlord_bet_num"):0;
	        $pay = Request::queryString ( "post.landlord_pay_num")?Request::queryString ( "post.landlord_pay_num"):0;
	        
	        $testx = I('post.rent_con_num','');
	        $ascending_num = 0;
	        if(!empty($testx) && !is_array($testx))//增量不是数组,就直接存  是数组 ,就在下面插入另一个表
	        {
	            $ascending_num = $rent * ($testx/100);
	        }
	        if($rent < 0)
	        {
	            return $this->returnAjax(array('status'=>0,'message'=>'租金填写错误!'));
	        }
	        if(String::countStrLength($mark) > 400)
	        {
	            return $this->returnAjax(array('status'=>0,'message'=>'备注不能超过400个字符'));
	        }
	        $detainarr = array(0,1,2,3,4,5,6,7,8,9,10,11,12);//用于判断用户输入是否合法
	        $payarr = array(1,2,3,4,6,12);//用于判断用户输入是否合法
	        if(!empty($detain) && !empty($pay) && !in_array($detain, $detainarr) && !in_array($pay, $payarr))
	        {
	            return $this->returnAjax(array('status'=>0,'message'=>'押付模式错误'));
	        }
	        $dead_line = Request::queryString ( "post.landlord_end_time")?strtotime ( Request::queryString ( "post.landlord_end_time")):0;
	        $bank = Request::queryString ( "post.landlord_pay_bank",0);
	        $bks = array_keys($condata);
	        if(!empty($bank) && !in_array($bank,$bks))
	        {
	            return $this->returnAjax(array('status'=>0,'message'=>'银行信息错误'));
	        }
	        $bankno = Request::queryString ( "post.landlord_card_num",0,'int');//银行卡号
	        if(!empty($bankno) &&  !\Common\Helper\ValidityVerification::IsBankNo($bankno))
	        {
	            return $this->returnAjax(array('status'=>0,'message'=>'银行卡错误'));
	        }
	        $payee = Request::queryString ( "post.landlord_payee",'');
	        if(mb_strlen($payee) >= 30)
	        {
	            return $this->returnAjax(array('status'=>0,'message'=>'收款人姓名太长'));
	        }
	        $landlord_contract_data = array(
	            'company_id'=>$this->getUser()['company_id'],
	            'parent_id'=>$pid,
	            'landlord_id'=>$landlord_pk,//业主id
	            'custom_number'=>Request::queryString ("post.landlord_contract_num",0),//合同编号
	            'rent'=>$rent,//租金
	            'deposit'=>$deposit,//,//押金
	            'bank'=>$bank,//支付银行
	            'fork_bank'=>Request::queryString ( "post.landlord_open_bank",''),//开户支行
	            'bank_no'=>$bankno,//银行卡号
	            'payee'=>$payee,//收款人姓名
	            'advance_time'=>$advance_time,//预约付款天数
	            'free_day'=>$free_day,//免租期
	            'is_ascending'=>$is_ascending,//租金递增1是 2否 字段默认值0
	            'ascending_num'=>$ascending_num,//递增量rent_con_num
	            'ascending_type'=>$ascending_type,//递增类型1/按比例;2/按金额
	            'pay_year'=>1,//第几年付款
	            'cycle'=>$cycle,//,//合同周期
	            //'ascending_money'=>,//上一次递增后钱
	            'signing_time'=>$signing_time,//,//签约时间
	            'detain'=>$detain,////押
	            'pay'=>$pay,//Request::queryString ( "post.landlord_pay_num",0),//付
	            'dead_line'=>$dead_line,//strtotime ( Request::queryString ( "post.landlord_end_time",19700103) ),//合同到期时间戳
	            'end_line'=>$end_line,//,//手动终止时间,默认等于到期时间
	            'house_id'=>$landlord_info['house_id'],//Request::queryString ( "post.landlord_id",0),//房间id   业务还不清楚 , 暂时写死
	            'hosue_name'=>Request::queryString( "post.landlord_house",''),//房间名   必须有    build 栋    unit 单元  floor 楼     num 号
	            'house_type'=>Request::queryString ( "post.house_type",''),//房间类型  必须有
	            'mark'=>$mark,//备注
	            'is_settlement'=>0,//是否结算 0否1是
	            'city_id'=>$city_id
	        );
	        //计算下次付款时间
	        $p = $landlord_contract_data['pay'];
	        $p = $landlord_contract_data['pay'];
	        if($p == 0)
	        {
	            $landlord_contract_data['next_pay_time'] = 0;
	        }else
	        {
	            $tmptime = date('Y-m-d',$signing_time);
	            $tmptimearr = explode('-', $tmptime);
	            $tmptimearr[1] = $tmptimearr[1] + $p + $free_day;
	            $tmptime = mktime(0,0,0,$tmptimearr[1],$tmptimearr[2],$tmptimearr[0]) - $advance_time * 86400;
	            $lc_next_pay_time = $tmptime;
	            $landlord_contract_data['next_pay_time'] = $signing_time;
	        }
	        //计算下次付款金额            判断下次付款时间属于合同第几年 , 再根据相应年份计算
	        $ynh = strtotime('+1 year');
	        if($landlord_contract_data['next_pay_time'] < $ynh)//如果下次付款时间小于和合同第二年
	        {
	            $landlord_contract_data['next_pay_money'] = $p * $landlord_contract_data['rent'];
	        }else
	        {//下次付款是第二年,那么月租金要用增长后的  ,增长分金额和比例
	            if(is_array($testx) && count($testx)>0)
	            {// '自定义';//echo $p.'=$P'; echo $testx[1].'=钱';
	                $landlord_contract_data['next_pay_money'] = $p * $testx[1];//自定义每年金额的情况  计算下次付款
	            }elseif($pay_rent_ways == 1 && !is_array($testx))
	            {//'按比例';
	                $landlord_contract_data['next_pay_money'] = $p * $landlord_contract_data['rent'] * ($ascending_num/100 + 1);//按比例计算下次付款
	            }elseif($pay_rent_ways == 2 && !is_array($testx))
	            {// '按金额';
	                $landlord_contract_data['next_pay_money'] = $p * ($ascending_num+$landlord_contract_data['rent']);//按金额计算下次付款
	            }else
	            {
	                //没有递增类型时
	                $landlord_contract_data['next_pay_money'] = $p * $rent;
	            }
	        }
	        if($signing_time == 0)
	        {
	            $landlord_contract_data['next_year'] = 0;
	        }else
	        {
	            $landlord_contract_data['next_year'] = date('Y',$landlord_contract_data['signing_time'])+1;//下一年付款年份
	        }
	        $a = $landlord_info[0];//Request::queryString ( "post.landlord_house", "" );
	        $b = $landlord_info['cost'];//Request::queryString ( "post.build", "" );
	        $c = $landlord_info['unit'];//Request::queryString ( "post.unit", "" );
	        $d = $landlord_info['floor'];//Request::queryString ( "post.floor", "" );
	        $e = $landlord_info['number'];// Request::queryString ( "post.num", "" );
	        if($landlord_contract_data['house_type'] == 1)
	        {
	            $landlord_contract_data['hosue_name'] = $a.'-'.$b.'栋'.'-'.$c.'单元'.'-'.$d.'楼'.'-'.$e.'号';//重新组装  build 栋    unit 单元  floor 楼     num 号
	        }
	        if(empty($landlord_contract_data['house_type']) || empty($landlord_contract_data['hosue_name']))
	        {
	            $landlordContractModel->rollback();
	            return $this->returnAjax(array('status'=>0,'message'=>'房源相关参数不能为空'));
	        }
	        if($signing_time != 0 && $p != 0)
	        {
	            if((($lc_next_pay_time - $signing_time)/86400) < $advance_time)
	            {
	                return $this->returnAjax(array('status'=>0,'message'=>'提前付款天数不能大于支付周期时间'));
	            }
	        }
	        $landlordContract_pk = $landlordContractModel->addLandlordContract($landlord_contract_data);//写入业主合同表
	        if ($landlordContract_pk) {
	            $landlord_model = new \Common\Model\Erp\LandlordContract();
	            //获取父合同的id
	            $parent_id = $landlord_model->getOne(array('contract_id' => $landlordContract_pk))['parent_id'];
	            $old_contract_data = $landlord_model->getOne(array('contract_id' => $parent_id));
	            $source = \Common\Helper\DataSnapshot::$SNAPSHOT_LANDLORD_CONTRACT_RELET;
	            $source_id = $parent_id;
	            $snapshot_res = \Common\Helper\DataSnapshot::save($source, $source_id, $old_contract_data);
	            if (!$snapshot_res) {
	                return $this->returnAjax(array('status'=>0,'message'=>'数据快照保存失败!'));
	            }
	            // 删除日程表 合同id 模块名 公司id 创建者id
	            $userInfo = $this->getUser();
	            $condition = array(
	                //'create_uid'=>$userInfo['user_id'],
	                'company_id'=>$userInfo['company_id'],
	                'module'=>'landlord_contract',
	                'entity_id'=>$pid,
	            );
	            // 删除父合同交租日程
	            $conditionJ = array(
	                //'create_uid'=>$userInfo['user_id'],
	                'company_id'=>$userInfo['company_id'],
	                'module'=>'landlord_contract_jiaozu',
	                'entity_id'=>$pid,
	            );
	            $todoModel = new \Common\Model\Erp\Todo();
	            if ($old_contract_data['next_pay_time']>=$old_contract_data['end_line']){
	            	if(!$todoModel->delete($condition) || !$todoModel->delete($conditionJ))
	            	{
	            		$landlordContractModel->rollback();
	            		return $this->returnAjax(array('status'=>0,'message'=>'日程删除失败'));
	            	}
	            }
	            $flat_id = 0;
	            $house_id = 0;
	            if ($landlord_info['house_type'] == \Common\Model\Erp\LandlordContract::HOUSE_TYPE_R){
	            	$house_id = $landlord_info['house_id'];
	            }
	            if ($landlord_info['house_type'] == \Common\Model\Erp\LandlordContract::HOUSE_TYPE_F){
	            	$flat_id = $landlord_info['house_id'];
	            }
	            // 再写一个日程表嘞
	            $noticeTime = date('Y-m-d',$dead_line);
	            $dataTodo1 = array(
	                'module'=>'landlord_contract', // 模块
	                'entity_id'=>$landlordContract_pk, // 实体id 合同id
	                'title'=>'到期', // 标题 例如【合同到期】
	                'content'=> $landlord_contract_data['hosue_name'].'的业主合同将于'.$noticeTime.'到期，请注意处理', // 内容
	                'company_id'=>$company_id, // 公司id
	                'url'=>'/index.php?c=landlord-index&a=info&contract_id='.$landlordContract_pk, // 跳转地址
	                'status'=> 0,// 状态 0/未处理,1/已查看,2/已处理
	                'deal_time'=>$dead_line, // 处理时间
	                'create_time'=>$_SERVER['REQUEST_TIME'],
	                'create_uid'=> $user_id, // 创建人
	                'house_id'=>$house_id,
	            	'flat_id'=>$flat_id,
	            );
	            // 新增一个交租日程
	            $next_pay_time = date('Y-m-d',$landlord_contract_data['next_pay_time']);
	            $dataTodo = array(
	                'module'=>'landlord_contract_jiaozu', // 模块
	                'entity_id'=>$landlordContract_pk, // 实体id 合同id
	                'title'=>'交租', // 标题 例如【合同到期】
	                'content'=>$landlord_contract_data['hosue_name'].'的租金'.$pay*$rent.'元'.'应于'.$next_pay_time.'支付，请注意处理', // 内容
	                'company_id'=>$company_id, // 公司id
	                'url'=>'/index.php?c=finance-serial&a=addexpense&contract_id='.$landlordContract_pk, // 跳转地址
	                'status'=> 0,// 状态 0/未处理,1/已查看,2/已处理
	                'deal_time'=>$landlord_contract_data['next_pay_time'], // 处理时间
	                'create_time'=>$_SERVER['REQUEST_TIME'],
	                'create_uid'=> $user_id, // 创建人
	                'house_id'=>$house_id,
	                'flat_id'=>$flat_id,
	            );
	            $todoModel = new \Common\Model\Erp\Todo();
	            if ($old_contract_data['next_pay_time']>=$old_contract_data['end_line']){
		            	if(!$todoModel->addTodo($dataTodo1))
		            	{
		            		$landlordContractModel->rollback();
		            		return $this->returnAjax(array('status'=>0,'message'=>'写入日程失败'));
		            	}
	            }
	            if ($old_contract_data['next_pay_time']>=$old_contract_data['end_line']){
		            if(!$todoModel->addTodo($dataTodo))
		            {
		                $landlordContractModel->rollback();
		                return $this->returnAjax(array('status'=>0,'message'=>'写入日程失败'));
		            }
	            }
	            if($landlord_contract_data['house_type'] == 1)//分散式?那再写一个表咯
	            {
	                $dismodel = new \Common\Model\Erp\DistributedLandlordContract();
	                $disdata = $dismodel->get($pid);
	                $ttdata = array(
	                    'contract_id'=>$landlordContract_pk,
	                    'community_id'=>$disdata['community_id'],//I('post.community_id',''),//接收小区idcommunity_id
	                    'cost'=>$disdata['cost'],
	                    'unit'=>$disdata['unit'],
	                    'floor'=>$disdata['floor'],
	                    'number'=>$disdata['number']
	                );
	                foreach($ttdata as $tt)
	                {
	                    if(empty($tt))
	                    {
	                        $landlordContractModel->rollback();
	                        return $this->returnAjax(array('status'=>0,'message'=>'扩展信息不能为空'));
	                    }
	                }
	                if(!$dismodel->add($ttdata))
	                {
	                    $landlordContractModel->rollback();
	                    return $this->returnAjax(array('status'=>0,'message'=>'扩展信息写入失败'));
	                }
	            }
	            //写入递增数组 , 另一个表
	            if(is_array($testx))
	            {
	                $lamodel = new \Common\Model\Erp\LandlordAscending();
	                $p = 0;
	                foreach($testx as $tx)
	                {
	                    $LA = array(
	                        'contract_id'=>$landlordContract_pk,
	                        'contract_year'=>$p++,//合同年 循环+1
	                        'ascending_money'=>$tx//每年递增后的钱
	                    );
	                    if(!$lamodel->add($LA))
	                    {
	                        $landlordContractModel->rollback();
	                        return $this->returnAjax(array('status'=>0,'message'=>'递增金额写入失败'));
	                    }
	                }
	            }
	            //写入附件
	            $picdata = Request::queryString ( "post.room_images",'');//接收图片数据 array
	            if(isset($_POST['room_images']) && empty($picdata))
	            {
	                $landlordContractModel->rollback();
	                return $this->returnAjax(array('status'=>0,'message'=>'图片数据不能为空'));
	            }
	            $picmodel = new \Common\Model\Erp\Attachments();
	            foreach($picdata as $v)
	            {
	                $pic = array(
	                    'key'=>$v,
	                    'module'=>'landlord_contract',
	                    'entity_id'=>$landlordContract_pk
	                );
	                if(!$picmodel->insertData($pic))//传的都是数组 , 直接用这个方法
	                {
	                    $landlordContractModel->rollback();
	                    return $this->returnAjax(array('status'=>0,'message'=>'图片上传失败'));
	                }
	            }
	            //$landlordContractModel->rollback();die;
	            $landlordContractModel->commit ();

	            $payall = $landlord_contract_data['rent'] * $landlord_contract_data['pay'];//跳转财务租金
	            $detainall = $landlord_contract_data['deposit'];//跳转财务押金

	            return $this->returnAjax ( array (
	                'status' => 1,
	                'message' => '业主合同续租成功',
	                'tag' => Url::parse ( "landlord-index/index" ),
	                //'url'=>Url::parse("Finance-serial/addIncome/source_type/$landlord_contract_data[house_type]/source/owner_contract/source_id/$landlordContract_pk"),
	                'url'=>Url::parse("Finance-serial/addExpense/house_name/$landlord_contract_data[hosue_name]/payall/$payall/detainall/$detainall/source_type/$landlord_contract_data[house_type]/source_id/$landlordContract_pk/source/owner_contract"),
	                'tag2' => Url::parse ( "landlord-index/checkCom/comname/$landlord_contract_data[hosue_name]"),//用小区名+楼+号.....查看房源表checkCom
	                'tag1' => Url::parse ( "landlord-index/checkHouse/comname/$landlord_contract_data[hosue_name]" )//用公寓名查公寓表
	            ) );
	        }
	}
	/**
	 * 小区名搜索接口
	 * @author too|最后修改时间 2015年4月29日 上午10:52:57
	 */
	public function searchhouseinfoAction(){
		$landlord = new \Common\Helper\Erp\Landlord ();
		$house_type=Request::queryString ( "get.type","" );//1分散 2集中
		$landlord_house=Request::queryString ( "get.search","" );
        if($house_type !=1 && $house_type !=2){
            return $this->returnAjax(array('status'=>0,'message'=>'房源类型错误!'));
        }
        if(empty($landlord_house)){
            return $this->returnAjax(array('status'=>0,'message'=>'未输入公寓名or小区名!'));
        }
        $data=$landlord->getHouseInfoByHouseNameAndHouseType($house_type, $landlord_house);
        if(empty($data)){
            return $this->returnAjax(array('status'=>0,'message'=>'搜索的小区/公寓不存在'));
        }else{
            return $this->returnAjax(array('status'=>1,'data'=>$data));
        }
	}
    /**
     * 添加业主合同时,若小区不存在,通过城市id取所有的area和商圈
     * @author too|最后修改时间 2015年5月4日 上午9:50:56
     */
	public function getareacityAction(){
        $area = $this->getUser();
        $landlord = new \App\Web\Helper\Landlord ();
        $city_id = Request::queryString ( "post.city_id",$area['city_id'],'int' );//没post城市id时默认为当前登陆用户的城市id
        $data = $landlord->getAreaInfo($city_id);
        return $this->returnAjax(array('status'=>1,'data'=>$data));
	}
    /**
     * 新增小区(小区不存在时)
     * @author too|最后修改时间 2015年5月4日 上午11:32:21
     */
	public function addcommunityAction(){
        //post city_id area_id business_id  区域名 商圈名    小区名 街道地址
        $data = $this->getUser();
        $company_id = $data['company_id'];
        $user_id = $data['user_id'];
        $city_id = Request::queryString ( "post.city_id",'118','int');
        $area_id = Request::queryString ( "post.area_id",'1242','int');
        $business_id = Request::queryString ( "post.business_id",'5679','int');
        $community_name = Request::queryString ( "post.community_name",'','string');
        $tmpdata = array(
            'city_id'=>$city_id,
            'area_id'=>$area_id,
            'area_string'=>Request::queryString ( "post.area_string",'成都','string'),
            'business_id'=>$business_id,
            'business_string'=>Request::queryString ( "post.business_string",'川音','string'),
            'company_id'=>$company_id,
            'user_id'=>$user_id,
            'address'=>Request::queryString ( "post.address",'金科北路8号','string'),//小区地址随意填写
            'community_name'=>$community_name,//小区名称随意填写
            'first_letter'=>\Common\Helper\String::getFirstCharter(Request::queryString ( "post.community_name",'','string')),//首写字母,根据传来的小区名获取
            'create_time'=>time(),//
            'is_verify'=>0,//审核0未通过 1通过
            'periphery'=>'我是默认信息咯',
            'traffic_condition'=>'我是默认的交通状况',
            'introduction'=>'我是小区简介默认值'
        );
        $landlord = new \App\Web\Helper\Landlord ();
        $community_data = $landlord->chackCommunity($city_id, $area_id, $business_id, $community_name);
        if ($community_data){
        	return $this->returnAjax(array('status'=>0,'message'=>'小区已经存在'));
        }
        $result = $landlord->addCommunity($tmpdata);
        if(!$result){
            return $this->returnAjax(array('status'=>0,'message'=>'新增小区失败'));
        }else{
            return $this->returnAjax(array('status'=>1,'message'=>'新增小区成功'));
        }
	}
    /**
     * 通过公寓名+公寓类型 验证公寓是否已经有合同
     * @author too|编写注释时间 2015年5月8日 上午11:03:24
     */
    public function isexisthousenameAction()
    {   //检索条件
        $search = array(
            'name'=>I('get.search',''),
            'house_type'=>I('get.type',''),
            'cost'=>I('get.build',''),
            'unit'=>I('get.unit',''),
            'floor'=>I('get.floor',''),
            'number'=>I('get.num','')
        );
        $modela = new \Common\Model\Erp\LandlordContract();
        $result = $modela->checkFromHousename($search,$this->getUser()['company_id']);
        if(!empty($result))
        {
            return $this->returnAjax(array('status'=>0,'message'=>'合同已经存在不能重复添加'));
        }
    }
    /**
     * 通过身份证验证业主是否存在
     * @author too|编写注释时间 2015年5月8日 上午11:19:48
     */
    public function isexistidcardAction()
    {
        $idcard = I('get.search','');
        $model = new \Common\Model\Erp\Landlord();
        $data = $model->getLandlord($idcard,$this->user['company_id']);
        if(!empty($data))
        {
            return $this->returnAjax(array('status'=>1,'data'=>$data));
        }else {
            return $this->returnAjax(array('status'=>0));
        }
    }
    /**
     * 集中式
     * 通过公寓名 查看公寓表确定是否存在
     * @author too|编写注释时间 2015年5月9日 下午5:52:53  flat_name
     */
    public function checkhouseAction()
    {
        $comname = I('get.comname');
        $commodel = new \Common\Model\Erp\Flat();
        $data = $commodel->getOne(array('flat_name'=>$comname));
        if(!empty($data))
        {
            return $this->returnAjax(array('status'=>1,'data'=>$data));
        }else
        {
            return $this->returnAjax(array('status'=>0,'message'=>'公寓不存在'));
        }
    }

    /**
     * 分散式
     * 通过小区名+栋+单元+楼+号 查看房源表确定是否存在
     * @author too|编写注释时间 2015年5月9日 下午5:43:44
     */
    protected function checkcomAction()
    {
        if (Request::isGet()){
            $comname = I('get.comname');

//             $cid = 2;
//             $b = 3;
//             $c = 4;
//             $d = 9;
//             $e = 4888;
//             $comname = array($cid,$b,$c,$d,$e);//用于添加成功后的搜索
            $houseModel = new \App\Web\Helper\House();
            $result = $houseModel->getInfo($comname);
            if ($result)
            {
                return $this->returnAjax(array("status"=>1,"data"=>$result));
            }
            return $this->returnAjax(array("status"=>0,"message"=>'房源不存在'));
        }

    }
    
    /**
     * 查询业主房源是否已存在
     * 修改时间 2015年9月1日14:15:21
     * 
     * @author ft
     */
    public function searchexistshouseAction()
    {
        $landlord_model = new \Common\Model\Erp\LandlordContract();
        $house_name = I('house_name', 'sefe');
        
        $where = new \Zend\Db\Sql\Where();
        $or_where = new \Zend\Db\Sql\Where();
        $where->equalTo('hosue_name', $house_name);
        $or_where->equalTo('is_delete', 0);
        $or_where->OR;
        $or_where->equalTo('is_stop', 0);
        $where->addPredicate($or_where, 'and');
        $landlord_info = $landlord_model->getOne($where, array('contract_id' => 'contract_id'));
        if ($landlord_info)
        {
            return $this->returnAjax(array("status"=>0,"message"=>'房源已存在！'));
        } else 
        {
            return $this->returnAjax(array("status"=>1,"message"=>'房源不存在！'));
        }
    }
}