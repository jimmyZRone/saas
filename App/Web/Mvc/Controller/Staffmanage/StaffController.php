<?php
/**
 * 员工控制器
 * @author dengshuang
 *
 */
namespace App\Web\Mvc\Controller\Staffmanage;
use App\Web\Lib\Request;
use App\Web\Helper\Url;
use Common\Helper\ValidityVerification;
class StaffController extends \App\Web\Lib\Controller{
	protected $_auth_module_name = 'sys_staff_management';
    /**
     * 员工添加，添加成功之后的跳转问题
     * 操作user_extend,user_group,user三张表
     *
     * @author too
     * 最后修改时间 2015年4月13日 上午8:57:07
     */
        protected function addAction(){
        	if(!$this->verifyModulePermissions(\Common\Helper\Permissions::INSERT_AUTH_ACTION)){//当前用户新增
        		return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("Staffmanage-staff/add")));
        	}
        	    $userModel = new \Common\Model\Erp\User();
        	    $user = \Common\Helper\Erp\User::getCurrentUser();//当前登陆的用户信息
        	    if(!Request::isPost()){
        	    	$mainUser = $userModel->getOne(array('company_id'=>$user['company_id'],'is_manager'=>1));
        	    	$this->assign('user', $mainUser);
        	    	
        	        //$this->assign('user', $user);//这玩意似乎没用
        	        $groupModel = new \Common\Model\Erp\Group();
        	        $groupList = $groupModel->getData(array('company_id'=>$user['company_id']));//取数据
        	        //print_r($user);
        	        if(!$groupList){//没有用户组
        	        	return $this->returnAjax(array('__status__'=>302,'__message__'=>'请先添加分组信息','__closetag__'=>Url::parse('Staffmanage-staff/add'),'__url__'=>Url::parse('Staffmanage-group/add/callback/'.base64_encode(Url::parse('Staffmanage-staff/add')))));
        	        }
        	        $this->assign('grouplist', $groupList);//分配给变量
        	        $data = $this->fetch();//调模板
        	        return $this->returnAjax(array("status"=>1,"tag_name"=>"添加员工","model_name"=>"staff_add","model_js"=>"workerManage_AddJs","model_href"=>Url::parse("Staffmanage-staff/add"),"data"=>$data));
        	    }
        	    $name = Request::queryString('post.worker_Name');//姓名
        	    $contact = Request::queryString('post.worker_Tel');//电话
        	    $gender = Request::queryString('post.worker_Gender')+0;//性别
        	    if($gender != 1 && $gender !== 0)
        	    {
        	        return $this->returnAjax(array('status'=>0,'message'=>'请选择性别'));
        	    }
        	    $data = array();//user表
        	    $password = Request::queryString('post.worker_Psw');
        	    $resu = ValidityVerification::IsPasswd($password);
        	    if($resu['status'] !== 1){
                    return $this->returnAjax($resu);
        	    }
        	    $data['username'] = Request::queryString('post.workerId');//限制40个长度
        	    if(empty($data['username']) || empty($name) || empty($contact))
        	    {
        	        return $this->returnAjax(array('status'=>0,'message'=>'提交信息不完整'));
        	    }
        	    if(mb_strlen($data['username']) > 40)
        	    {
        	        return $this->returnAjax(array('status'=>0,'message'=>'用户名太长'));
        	    }
        	    $mark = Request::queryString('post.remark','这个人好懒,啥子备注都木有写');
        	    if(!empty($mark) && mb_strlen($mark) > 400)
        	    {
        	        return $this->returnAjax(array('status'=>0,'message'=>'备注太长'));
        	    }
        	    //username and company_id去user表取,能取到则提示登录名重复
        	    $where = array('company_id'=>$user['company_id'],'username'=>$data['username']);
        	    $T = $userModel->getOne($where);
                if(!empty($T)){
                    return $this->returnAjax(array('status'=>0,'message'=>'登录名重复'));
                }
        	    $data['salt'] = \Common\Helper\String::rand(5,\Common\Helper\String::RAND_TYPE_NUMBER_LETTER);
                $data['password'] = \Common\Helper\Encrypt::sha1($data['salt'].$password);
                $data['create_time'] = $_SERVER['REQUEST_TIME'];
                $data['company_id'] = $user['company_id'];//公司id
                $userModel->Transaction();//事务开始
                $new_staff_id = $userModel->insert($data);
                if($new_staff_id>0){
                    $data1 = array();//user_extend表
                    $data1['mark'] = $mark;//限制400个字符
                    $data1['name'] = $name;//Request::queryString('post.worker_Name');
                    $data1['contact'] = $contact;//Request::queryString('post.worker_Tel');
                    $data1['gender'] = $gender;//Request::queryString('post.worker_Gender')+0;
                    $data1['user_id'] = $new_staff_id;
                    $UserExModel = new \Common\Model\Erp\UserExtend();//用户扩展信息表
                    if($UserExModel->insert($data1)){
                        $data2 = array();//user_group表
                        $data2['user_id'] = $new_staff_id;
                        $data2['group_id'] =  Request::queryString('post.worker_Group');//用户组表
                        $userGroupModel = new \Common\Model\Erp\UserGroup();
                        if($userGroupModel->insert($data2)){
                            $userModel->commit();
                            return $this->returnAjax(array('status'=>1,'message'=>'员工添加成功','tag'=>Url::parse("Staffmanage-staff/list")));
                        }else{
                            $userModel->rollback();
                            return $this->returnAjax(array('status'=>0,'message'=>'用户群组错误'));
                        }
                    }else{
                        $userModel->rollback();
                        return $this->returnAjax(array('status'=>0,'message'=>'用户扩展信息错误'));
                    }
                }else{
                    $userModel->rollback();
                    return $this->returnAjax(array('status'=>0,'message'=>'用户添加错误'));
                }
    }

	/**
	 * 员工列表
	 *
	 *
	 * @author too
	 * 最后修改时间 2015年4月16日 下午4:07:17
	 */
	protected function listAction(){
		if(!$this->verifyModulePermissions(\Common\Helper\Permissions::SELECT_AUTH_ACTION)){//当前用户不能查看
			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("Staffmanage-staff/list")));
		}
		
		$helper = new \App\Web\Helper\User();
		$search_key = Request::queryString("get.view","","string");//搜索关键字
		$where = array();
		$where['name'] = !empty($search_key)?$search_key:'';
		$where['company_id'] = $this->user['company_id'];
		$page = Request::queryString("get.page",0,"intval");//传来页数
		//$user = $userModel->getCurrentUser();
		$staffList = $helper->getSatffList($where,$page,15);
        if ($page) {
            $this->assign('count',$staffList['page']['count']);
    		$this->assign('page', $staffList['page']);//把页码信息返回前端
    		$this->assign('staffList', $staffList['data']);
    		$this->assign('view', Request::queryString('get.view_type','data'));
    		$data = $this->fetch('list');
    		if (empty($staffList['data'])) {
        		return $this->returnAjax(array(
        		    "status"=>0,"tag_name"=>"员工列表","model_name"=>"staff_list",
        		    "model_js"=>"workerManage_IndJs","model_href"=>Url::parse("Staffmanage-staff/list"),
        		    "data"=>$data,'page'=>$staffList['page']));
    		}
        		return $this->returnAjax(array(
        		    "status"=>1,"tag_name"=>"员工列表","model_name"=>"staff_list",
        		    "model_js"=>"workerManage_IndJs","model_href"=>Url::parse("Staffmanage-staff/list"),
        		    "data"=>$data,'page'=>$staffList['page']));
        } else {
            $this->assign('view', Request::queryString('get.view_type','template'));
            $this->assign('count',$staffList['page']['count']);
            $data = $this->fetch('list');
    		return $this->returnAjax(array(
    		    "status"=>1,"tag_name"=>"员工列表","model_name"=>"staff_list",
    		    "model_js"=>"workerManage_IndJs","model_href"=>Url::parse("Staffmanage-staff/list"),
    		    "data"=>$data,'page'=>$staffList['page']));
        }
	}

	    /**
	     * 员工删除
	     * 操作user_extend,user_group,user三张表
	     *
	     * @author too
	     * 最后修改时间 2015年4月13日 上午8:58:06
	     */
        protected function deleteAction(){
        	if(!$this->verifyModulePermissions(\Common\Helper\Permissions::DELETE_AUTH_ACTION)){
        		return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("Staffmanage-staff/delete")));
        	}
        		$user_id = Request::queryString('post.uid',0);
        		if(empty($user_id)){
        			return $this->returnAjax(array('status'=>0,'message'=>'用户id有误'));
        		}
        		$userModel = new \Common\Model\Erp\User();
        		//验证公司
        		$userData = $userModel->getOne(array('user_id'=>$user_id));
        		if(!$userData || $userData['company_id'] != $this->user['company_id']){
        			return $this->returnAjax(array('status'=>0,'message'=>'删除失败'));
        		}
        		
        		$userModel->Transaction();
        		$userExModel = new \Common\Model\Erp\UserExtend();
        		$userGpModel = new \Common\Model\Erp\UserGroup();
        		$condition = array('user_id'=>$user_id);
        		$res2 = $userGpModel->delete($condition);
        		$res = $userExModel->delete($condition);
        		$res1 = $userModel->delete($condition);
        		if($res && $res1 && $res2){
        			$userModel->commit();
        			$data = $this->fetch('list');
        			$this->returnAjax(array('status'=>1,"tag_name"=>"员工列表","model_name"=>"staff_list","model_js"=>"workerManage_IndJs",'message'=>'删除成功',"model_href"=>Url::parse("Staffmanage-Staff/edit"),'data'=>$data));
        		}else{
        			$userModel->rollback();
        			$this->returnAjax(array('status'=>0,'message'=>'删除失败'));
        		}
        	}

	/**
	 * 操作user_extend,user_group,user,group四张表
	 * 编辑用户
	 *
	 * @author too
	 * 最后修改时间 2015年4月13日 下午2:42:12
	 */
	protected function editAction(){
	    $user_id = Request::queryString('get.user_id');//接收用户id
	    //验证公司
	    $userModel = new \Common\Model\Erp\User();
	    $userData = $userModel->getOne(array('user_id'=>$user_id));
	    if(!Request::isPost()){
	    	if(!$this->verifyModulePermissions(\Common\Helper\Permissions::SELECT_AUTH_ACTION)){
	    		return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("Staffmanage-staff/edit")));
	    	}
	    	if(!$userData || $userData['company_id'] != $this->user['company_id']){
	    		return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("Staffmanage-staff/edit")));
	    	}
	        $userHelper = new \App\Web\Helper\User();
	        $user = \Common\Helper\Erp\User::getCurrentUser();//当前登陆的用户信息
	        $groupModel = new \Common\Model\Erp\Group();
	        $groupList = $groupModel->getData(array('company_id'=>$user['company_id']));//得到所有的群组
	        $this->assign('grouplist', $groupList);//分配给变量
	        $userinfo = $userHelper->getOne($user_id);//获取员工所有信息
	        $user = $this->getUser();
	        
	        $mainUser = $userModel->getOne(array('company_id'=>$user['company_id'],'is_manager'=>1));
	        $this->assign('user', $mainUser);
	        
	        //$this->assign('user', $user);//这玩意似乎没用
	        //print_r($userinfo);
	        $this->assign('userinfo', $userinfo);
	        $data = $this->fetch('add');
	        return $this->returnAjax(array("status"=>1,"tag_name"=>"员工编辑","model_name"=>"staff_edit","model_js"=>"workerManage_AddJs","model_href"=>Url::parse("Staffmanage-Staff/edit"),'data'=>$data));
	    }
	    if(!$this->verifyModulePermissions(\Common\Helper\Permissions::UPDATE_AUTH_ACTION)){
	    	return $this->returnAjax(array('__status__'=>403));
	    }
	    if(!$userData || $userData['company_id'] != $this->user['company_id']){
	    	return $this->returnAjax(array('__status__'=>403));
	    }
	    
        $userModel1 = new \Common\Model\Erp\User();//user表
        $userModel1->Transaction();
	    $password = Request::queryString('post.worker_Psw');
	    $data0 = array();//user表
	    $username = Request::queryString('post.workerId','');
	    if(!empty($username))
	    {
	        $data0['username'] = $username;
	    }
	     if(!empty($password)){//如果修改密码
	        $data0['salt'] = \Common\Helper\String::rand(5,\Common\Helper\String::RAND_TYPE_NUMBER_LETTER);
	        $data0['password'] = \Common\Helper\Encrypt::sha1($data0['salt'].$password);
	    }
	    if(!empty($password) ||  !empty($username))
	    {
	        $u = $userModel1->edit(array('user_id'=>$user_id),$data0);//编辑user表
	    }

	    $name = Request::queryString('post.worker_Name','');
	    $contact = Request::queryString('post.worker_Tel','');
	    $gender = Request::queryString('post.worker_Gender','');
	    $remark = Request::queryString('post.remark','');
	    $group_id = Request::queryString('post.worker_Group','');
	    $data1 = array();//user_extend表
	    if(!empty($name)){
	        $data1['name'] = $name;
	    }
	    if(!empty($contact)){
	        $data1['contact'] = $contact;
	    }
	    if($gender != ''){
	        $data1['gender'] = $gender;
	    }
	    if(!empty($remark)){
	        $data1['mark'] = $remark;
	    }
        $userExModel = new \Common\Model\Erp\UserExtend();
        $ue = $userExModel->edit(array('user_id'=>$user_id),$data1);
        $data2 = array();//user_group表
        if(!empty($group_id)){
            $data2['group_id'] = $group_id;
        }
        $userGroupModel = new \Common\Model\Erp\UserGroup();
//         P($data2);
//         P($data1);
//         P($data0);
        $ug = $userGroupModel->edit(array('user_id'=>$user_id),$data2);
        $u = isset($u)?$u:true;
        if($u && $ue && $ug){
            $userModel1->commit();
            return $this->returnAjax(array('status'=>1,'message'=>'保存成功','tag'=>Url::parse("Staffmanage-staff/list")));
        }else{
            $userModel1->rollback();
            return $this->returnAjax(array('status'=>0,'message'=>'保存失败'));
        }
	}
	/**
	 * 给员工分配房源
	 * @author too|编写注释时间 2015年5月13日 下午4:58:56
	 * 目前只取出了员工信息
	 */
	protected function allothouseAction()
	{
		if(!$this->verifyModulePermissions(\Common\Helper\Permissions::UPDATE_AUTH_ACTION)){
    		return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("Staffmanage-staff/allothouse")));
    	}
		
	    $staff_id = I('get.staff_id',0,'int');
	    if($staff_id == 0)
	    {
	        return $this->returnAjax(array('status'=>0,'message'=>'无效的用户'));
	    }
	    $userModel = new \App\Web\Helper\User();
	    $staffinfo = $userModel->getOne($staff_id);
	    $this->assign('staffinfo',$staffinfo);
	    $this->assign('login_user', $this->user);
	    if($this->user['company']['isDispersive']){//取得区域
	    	$aeraHelper = new \Common\Helper\Erp\Area();
	    	$area = $aeraHelper->getCompanyList($this->user['city_id'], $this->user['company_id']);
	    	array_unshift($area, array('area_id'=>0,'name'=>'全部区域'));
	    	$this->assign('area',$area);
	    }
	    $data = $this->fetch();
	    return $this->returnAjax(array("status"=>1,"tag_name"=>"分配房源","model_name"=>"staff_edit","model_js"=>"workerManage_RoomManageJs","model_href"=>Url::parse("Staffmanage-Staff/allotHouse"),'data'=>$data));
	}
	/**
	 * 对应上面，取得选择后的小区或公寓
	 * @author lishengyou
	 * 最后修改时间 2015年6月15日 下午5:50:57
	 *
	 */
	protected function allotlistAction(){
		if(!$this->verifyModulePermissions(\Common\Helper\Permissions::UPDATE_AUTH_ACTION)){
    		return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("Staffmanage-staff/allotlist")));
    	}
		
		$type = Request::queryString('get.type',0,'intval');
		$aera = Request::queryString('get.area',0,'intval');
		$user_id = Request::queryString('get.user_id',0,'intval');
		
		$json = array();
		$source_type_id = $type == 0 ? $this->user['city_id'] : $aera;
		$helper = new \Common\Helper\Erp\HousingDistribution();
		
		$data = $helper->getListJoinSource($type, $this->user['city_id'],$source_type_id, $this->user['company_id']);
		$temp = array();
		if($type == 0){
			foreach ($data as $value){
				$val = array();
				$val['name'] = $value['flat_name'];
				$val['id'] = $value['flat_id'];
				$val['is_self'] = intval(isset($value['distribution'][$user_id]));
				if($val['is_self']){
					unset($value['distribution'][$user_id]);
				}
				$val['is_allot'] = !empty($value['distribution']);
				$val['allot'] = $value['distribution'];
				$temp[] = $val;
			}
		}else{
			foreach ($data as $value){
				$val = array();
				$val['name'] = $value['community_name'];
				$val['id'] = $value['community_id'];
				$is_self = true;
				$allot = false;
				foreach ($value['house_list'] as $key => $hval){
					$hval['is_self'] = intval(isset($hval['distribution'][$user_id]));
					if($hval['is_self']){
						unset($hval['distribution'][$user_id]);
					}
					$hval['house_name'] = preg_replace("#^{$val['name']}-?(.*)$#", '$1', $hval['house_name']);
					$is_self = $hval['is_self'] && $is_self ? true : false;
					$is_allot = $hval['is_self'] && $is_self ? true : false;
					$hval['is_allot'] = intval(!empty($hval['distribution']));
					$hval['allot'] = $hval['distribution'];
					if($allot === false){
						$allot = $hval['allot'];
					}
					$allot = array_intersect_key($allot, $hval['allot']);
					$value['house_list'][$key] = $hval;
				}
				$value['house_list'] = array_values($value['house_list']);
				$val['house_list'] = $value['house_list'];
				$val['is_self'] = intval($is_self);
				$val['is_allot'] = intval(!empty($allot));
				$val['allot'] = array();
				/*$val['is_self'] = intval(isset($value['distribution'][$user_id]));
				if($val['is_self']){
					unset($value['distribution'][$user_id]);
				}
				$val['is_allot'] = intval(!empty($value['distribution']));
				$val['allot'] = $value['distribution'];*/
				$temp[] = $val;
			}
		}
		return $this->returnAjax(array('status'=>0,'data'=>$temp));
	}
	/**
	 * 对应上面，保存权限分配
	 * @author lishengyou
	 * 最后修改时间 2015年6月15日 下午5:50:57
	 *
	 */
	protected function saveallotAction(){
		if(!$this->verifyModulePermissions(\Common\Helper\Permissions::UPDATE_AUTH_ACTION)){
    		return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("Staffmanage-staff/saveallot")));
    	}
		
		$type = Request::queryString('get.type',0,'intval');
		$aera = Request::queryString('get.area',false);
		$user_id = Request::queryString('get.user_id',0,'intval');
		
		$userModel = new \Common\Model\Erp\User();
		//验证公司
		$userData = $userModel->getOne(array('user_id'=>$user_id));
		if(!$userData || $userData['company_id'] != $this->user['company_id']){
			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("Staffmanage-staff/saveallot")));
		}
		$type = $type ? 1 : 2;
		$model = new \Common\Model\Erp\HousingDistribution();
		$sql = $model->getSqlObject();
		$sql->Transaction();
		//删除类型下当前用户的所有权限数据
		$delete = $sql->delete($model->getTableName());
		if($type == 1){//分散式
			$where = new \Zend\Db\Sql\Where();
			$where->equalTo('user_id', $user_id);
			$and = new \Zend\Db\Sql\Where();
			$or = new \Zend\Db\Sql\Where();
			$and->equalTo('source', \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
			$or->equalTo('source', \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED);
			$and->orPredicate($or);
			$where->andPredicate($and);
			$delete->where($where);
		}elseif ($type == 2){
			$where = new \Zend\Db\Sql\Where();
			$where->equalTo('user_id', $user_id);
			$where->equalTo('source', \Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
			$delete->where($where);
		}
		if(!$delete->execute()){
			$sql->rollback();
			return $this->returnAjax(array('status'=>0,'message'=>'发生了一些错误，操作失败了！'));
		}
		//重新保存新的数据
		$insertData = array();
		$xqIds = isset($_POST['xq_id']) ? $_POST['xq_id'] : array();
		if($xqIds){
			$xqIds = array_map('intval', array_values($xqIds));
		}
		$xqIds = array_unique($xqIds);
		$xqIds = array_filter($xqIds);
		foreach ($xqIds as $xqid){
			if($type == 1){//分散式
				$insertData[] = array('user_id'=>$user_id,'source'=>\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED,'source_id'=>$xqid);
			}else{//集中式
				$insertData[] = array('user_id'=>$user_id,'source'=>\Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED,'source_id'=>$xqid);
			}
		}
		$houseids = isset($_POST['house_id']) ? $_POST['house_id'] : array();
		if($type == 1){//分散式,保存分散式的房源
			if($houseids){
				$houseids = array_map('intval',array_values($houseids));
			}
			$houseids = array_unique($houseids);
			$houseids = array_filter($houseids);
			if($xqIds && !$houseids){
				$sql->rollback();
				return $this->returnAjax(array('status'=>0,'message'=>'提交的数据有些错误，操作失败了！'));
			}
			foreach ($houseids as $house_id){
				$insertData[] = array('user_id'=>$user_id,'source'=>\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE,'source_id'=>$house_id);
			}
		}
		if($insertData && !$model->insert($insertData)){
			$sql->rollback();
			return $this->returnAjax(array('status'=>0,'message'=>'发生了一些错误，操作失败了！'));
		}
		//返回处理结果
		$messageModel = new \Common\Model\Erp\Message();
		$messageData = array();
		$messageData['to_user_id'] = $user_id;
		$messageData['title'] = "您管理的房源权限发生了变化";
		$messageData['message_type'] = "system";
		if($type == 1){//分散式
			$messageData['content'] = "您管理的分散式公寓的房源权限发生了变化。";
			$messageData['message_type'] = "system";
		}else{//集中式
			$messageData['content'] = "您管理的集中式公寓权限发生了变化。";
		}
		$messageModel->sendMessage($messageData);
		$sql->commit();
		return $this->returnAjax(array('status'=>1,'tag'=>Url::parse("Staffmanage-staff/list")));
	}
}