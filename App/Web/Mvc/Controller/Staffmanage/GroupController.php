<?php
namespace App\Web\Mvc\Controller\Staffmanage;
use App\Web\Helper\Url;
use Common\Helper\Permissions;
class GroupController extends \App\Web\Lib\Controller{
	protected $_auth_module_name = 'sys_staff_management';
	
    /**
     * 权限分组新增
     *  最后修改时间 2015-3-18
     *  只完成基础功能
     *
     * @author fangtao
     */
    protected function addAction(){
    	if(!$this->verifyModulePermissions(\Common\Helper\Permissions::INSERT_AUTH_ACTION)){
    		return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("staffmanage-group/add")));
    	}
    	
        if(!\App\Web\Lib\Request::isPost()){  //刚进来走的这步
        	$modularModel = new \Common\Model\Erp\Modular();
        	$modularData = $modularModel->getData(array(),array(),0,0,'parent_id asc,order asc');
        	//整理模块菜单
        	$temp = array();
        	foreach ($modularData as $value){
        		if(!isset($temp[$value['parent_id']])){
        			$temp[$value['parent_id']] = array();
        		}
        		$value['selected'] = false;
        		$value['auth'] = array(0,0,0,0);
        		$temp[$value['parent_id']][$value['modular_id']] = $value;
        	}
        	$modularData = $temp;unset($temp);
        	
        	$this->assign('modular_list', $modularData);
        	$callback = \App\Web\Lib\Request::queryString('get.callback');
        	if($callback){
        		$this->assign('form_action', Url::parse('Staffmanage-Group/add/callback/'.$callback));
        	}else{
        		$this->assign('form_action', Url::parse('Staffmanage-Group/add'));
        	}
            $data = $this->fetch("group_add");
            return $this->returnAjax(array("status"=>1,"tag_name"=>"添加权限组","model_name"=>"group_add","model_js"=>"workerManage_Authority","model_href"=>Url::parse("staffmanage-group/add"),"data"=>$data));
        }else{  //提交后
            $user = $this->user;
            $group_info = $_POST;
            $group_name = trim(array_shift($group_info));
            $group_name = htmlspecialchars($group_name);
            if (isset($group_name{33})) {
                return $this->returnAjax(array("status"=>0,"message"=> "名字超过11个字！"));
            }
            $groupModel = new \Common\Model\Erp\Group();
            $group_name_len = $groupModel->getData(array('name' => $group_name, 'company_id' => $user['company_id']));
            if (count($group_name_len) > 0) {
                return $this->returnAjax(array("status"=>0,"message"=> "分组名已存在"));
            }
            $group = array();
            $group['company_id'] = $user['company_id'];
            $group['parent_id'] = 0;
            $group['name'] = $group_name;
            $group_id = \Common\Helper\Erp\Group::add($group);  //权限组
            if(!$group_id){
            	return $this->returnAjax(array("status"=>0,"message"=> "添加分组错误"));
            }
			$modularModel = new \Common\Model\Erp\Modular();
            $modulars = $modularModel->getData();
            foreach ($modulars as $modular){
            	$authdata = array();
            	if(isset($group_info[$modular['mark']]) && is_array($group_info[$modular['mark']])){
            		$authdata = array_fill_keys($group_info[$modular['mark']],0);
            	}
            	if(!$modular['functional_module']){
            		continue;
            	}
            	$permissions = Permissions::FactoryById($modular['functional_module']);
            	$block_id = $permissions::MODULE_BLOCK_ACCESS;
            	$group_type = $permissions::GROUP_AUTHENTICATOR;
            	$functional_module = $modular['functional_module'];
            	$permissions->SetVerify($block_id,$permissions::INSERT_AUTH_ACTION,$group_type,$group_id,$functional_module,isset($authdata['0']) ? 1 : 0);
            	$permissions->SetVerify($block_id,$permissions::UPDATE_AUTH_ACTION,$group_type,$group_id,$functional_module,isset($authdata['1']) ? 1 : 0);
            	$permissions->SetVerify($block_id,$permissions::DELETE_AUTH_ACTION,$group_type,$group_id,$functional_module,isset($authdata['2']) ? 1 : 0);
            	$permissions->SetVerify($block_id,$permissions::SELECT_AUTH_ACTION,$group_type,$group_id,$functional_module,isset($authdata['3']) ? 1 : 0);
            }
            $res = true;
            if ($res) {
            	$callback = \App\Web\Lib\Request::queryString('get.callback');
            	if($callback){
            		$callback = base64_decode($callback);
            	}
            	if($callback){
            		return $this->returnAjax(array('__status__'=>302,'__closetag__'=>Url::parse('Staffmanage-group/add'),'__url__'=>Url::parse('Staffmanage-staff/add')));
            	}else{
            		return $this->returnAjax(array("status"=>1,"tag"=>Url::parse("staffmanage-group/list")));
            	}
            }
            return $this->returnAjax(array("status"=>0,"message"=> "错误"));
         }
    }
    
    /**
     * 权限分组列表
     *  最后修改时间 2015-3-18
     *  只完成基础功能 列表的具体形式还需要跟产品沟通
     *
     * @author fangtao
     */
    protected function listAction(){
    	if(!$this->verifyModulePermissions(\Common\Helper\Permissions::SELECT_AUTH_ACTION)){
    		return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("Staffmanage-group/list")));
    	}
    	
        $user = $this->user;
        $groupModel = new \Common\Model\Erp\Group();
        $group_list = $groupModel->getData(array('company_id'=>$user['company_id']));
        $this->assign('group_list', $group_list);
        $data = $this->fetch("group_manage");
        return $this->returnAjax(array("status"=>1,"tag_name"=>"分组管理","model_name"=>"group_list","model_js"=>"workerManage_AuthorityManageJs","model_href"=>Url::parse("staffmanage-group/list"),"data"=>$data));
    }

    /**
     * 权限分组修改
     *  最后修改时间 2015-3-18
     *  只完成基础功能 列表的具体形式还需要跟产品沟通
     *
     * @author fangtao
     */
    protected function editAction(){
        if (!\App\Web\Lib\Request::isPost()) {
        	if(!$this->verifyModulePermissions(\Common\Helper\Permissions::SELECT_AUTH_ACTION)){
        		return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("staffmanage-group/edit")));
        	}
        	
            $groupModel = new \Common\Model\Erp\Group();
            $modularModel = new \Common\Model\Erp\Modular();
            $group_id = \App\Web\Lib\Request::queryString('get.group_id', '');
            $groupData = $groupModel->getOne(array('group_id'=>$group_id,'company_id'=>$this->user['company_id']));
            if(!$groupData){die();}
            //整理权限数据
			$modularData = $modularModel->getData(array(),array(),0,0,'parent_id asc,order asc');
			$authData = $modularModel->setTableName('module_permissions')->getData(array('authenticator_type'=>Permissions::GROUP_AUTHENTICATOR,'authenticator_id'=>$groupData['group_id'],'block_access_id'=>Permissions::MODULE_BLOCK_ACCESS));
			$temp = array();
			$authps = array(Permissions::INSERT_AUTH_ACTION,Permissions::UPDATE_AUTH_ACTION,Permissions::DELETE_AUTH_ACTION,Permissions::SELECT_AUTH_ACTION);
			$authps = array_flip($authps);
			foreach ($authData as $value){
				if(!isset($temp[$value['authenticatee_id']])){
					$temp[$value['authenticatee_id']] = array();
				}
				$temp[$value['authenticatee_id']][$authps[$value['permissions_auth']]] = $value['permissions_value'];
			}
            $authData = $temp;unset($temp);
            //整理模块菜单
            $temp = array();
            foreach ($modularData as $value){
            	if(!isset($temp[$value['parent_id']])){
            		$temp[$value['parent_id']] = array();
            	}
            	$temp[$value['parent_id']][$value['modular_id']] = $value;
            }
            $modularData = $temp;unset($temp);
            //统计第一级菜单是否需要全选
            foreach ($modularData['0'] as $key => $value){
            	$value['selected'] = true;
            	if(isset($modularData[$value['modular_id']])){//检测下级菜单
            		foreach ($modularData[$value['modular_id']] as $k => $val){
            			//把权限回归到每个模块
            			if(!$val['functional_module'] || !isset($authData[$val['functional_module']])){
            				$val['auth'] = array_fill_keys(array_values($authps), false);
            			}else{
            				foreach ($authps as $auth){
            					$val['auth'][$auth] = isset($authData[$val['functional_module']][$auth]) && $authData[$val['functional_module']][$auth];
            				}
            			}
            			$val['selected'] = count(array_filter($val['auth'])) >= 4;
            			$modularData[$value['modular_id']][$k] = $val;
            			$value['selected'] = !$value['selected'] ? $value['selected'] : $val['selected'];
	            	}
            	}else if(!$value['functional_module'] || !isset($authData[$value['functional_module']])){
            		$value['auth'] = array_fill_keys(array_values($authps), false);
            	}else{
            		foreach ($authps as $auth){
            			$value['auth'][$auth] = isset($authData[$value['functional_module']][$auth]) && $authData[$value['functional_module']][$auth];
            		}
            	}
            	$value['selected'] = $value['functional_module'] ? (count(array_filter($value['auth'])) >= 4) : $value['selected'];
            	$modularData['0'][$key] = $value;
            }
         
            $this->assign('modular_list', $modularData);
            $this->assign('group_data', $groupData);
            $data = $this->fetch("group_add");
            return $this->returnAjax(array("status"=>1,"tag_name"=>"分组管理","model_name"=>"group_edit","model_js"=>"workerManage_Authority","model_href"=>Url::parse("staffmanage-group/edit"),"data"=>$data));
        } else {
        	if(!$this->verifyModulePermissions(\Common\Helper\Permissions::UPDATE_AUTH_ACTION)){
        		return $this->returnAjax(array('__status__'=>403));
        	}
        	
        	
            $group_id = \App\Web\Lib\Request::queryString('get.group_id', '');
            if ($group_id) {
                $user = $this->user;
                $group_info = $_POST;
                $group_name = trim(array_shift($group_info));
                $group_name = htmlspecialchars($group_name);
                if (isset($group_name{33})) {
                    return $this->returnAjax(array("status"=>0,"message"=> "名字超过11个字！"));
                }
                $groupModel = new \Common\Model\Erp\Group();
                $group_name_data = $groupModel->getOne(array('name' => $group_name, 'company_id' => $user['company_id']));
                if ($group_id != $group_name_data['group_id'] && !empty($group_name_data)) {
                    return $this->returnAjax(array("status"=>0,"message"=> "分组名已存在!"));
                }
                $group = array();
                $group['name'] = $group_name;
                $groupHelper = new \Common\Helper\Erp\Group();
                $group_where = array('group_id' => $group_id, 'company_id' => $user['company_id']); //分组名修改条件
                $res = $groupHelper->edit($group_where,$group);  //权限组
                if (!$res) {
                    return $this->returnAjax(array("status"=>0,"message"=> "分组名修改失败！"));
                }
            } else {
                return $this->returnAjax(array("status"=>0,"message"=> "用户组不存在"));
            }
            $modularModel = new \Common\Model\Erp\Modular();
            $modulars = $modularModel->getData();
            foreach ($modulars as $modular){
            	$authdata = array();
            	if(isset($group_info[$modular['mark']]) && is_array($group_info[$modular['mark']])){
            		$authdata = array_fill_keys($group_info[$modular['mark']],0);
            	}
            	if(!$modular['functional_module']){
            		continue;
            	}
            	$permissions = Permissions::FactoryById($modular['functional_module']);
            	$block_id = $permissions::MODULE_BLOCK_ACCESS;
            	$group_type = $permissions::GROUP_AUTHENTICATOR;
            	$functional_module = $modular['functional_module'];
            	$permissions->SetVerify($block_id,$permissions::INSERT_AUTH_ACTION,$group_type,$group_id,$functional_module,isset($authdata['0']) ? 1 : 0);
            	$permissions->SetVerify($block_id,$permissions::UPDATE_AUTH_ACTION,$group_type,$group_id,$functional_module,isset($authdata['1']) ? 1 : 0);
            	$permissions->SetVerify($block_id,$permissions::DELETE_AUTH_ACTION,$group_type,$group_id,$functional_module,isset($authdata['2']) ? 1 : 0);
            	$permissions->SetVerify($block_id,$permissions::SELECT_AUTH_ACTION,$group_type,$group_id,$functional_module,isset($authdata['3']) ? 1 : 0);
            }
            $res = true;
            if ($res) {
                return $this->returnAjax(array("status"=>1,"tag"=>Url::parse("staffmanage-group/list")));
            } else {
                return $this->returnAjax(array("status"=>0,"message"=> "修改错误！"));
            }
        }
    }
    
    /**
     * 权限分组删除
     *  最后修改时间 2015-3-18
     *  只完成基础功能 列表的具体形式还需要跟产品沟通
     *
     * @author fangtao
     */
    protected function delAction(){
    	if(!$this->verifyModulePermissions(\Common\Helper\Permissions::DELETE_AUTH_ACTION)){
    		return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("staffmanage-group/del")));
    	}
    	
        $group_id = \App\Web\Lib\Request::queryString('post.uid', '');
        $groupModel = new \Common\Model\Erp\Group();
        $userModel = new \Common\Model\Erp\UserGroup();
        $groupModel->Transaction();  //开启事务处理
        $group_condition = array('group_id' => $group_id);
        $user_info = $userModel->getOne(array('group_id' => $group_id));
        if (!count($user_info)) {
            $modular = Permissions::ClearAllVierify(Permissions::GROUP_AUTHENTICATOR,$group_id);
            $group = $groupModel->delete($group_condition);
            if ($group && $modular) {
                $groupModel->commit();
                return $this->returnAjax(array("status"=>1,"message"=>"删除成功","tag"=>Url::parse("staffmanage-group/list")));
            } else {
                $groupModel->rollback();
                return $this->returnAjax(array("status"=>0,"message"=>"删除失败"));
            }
        } else {
            return $this->returnAjax(array("status"=>0,"message"=>"该权限组存在用户，不能删除！"));
        }
    }
}