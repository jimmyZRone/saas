<?php
namespace App\Web\Mvc\Controller;

class GroupController extends \App\Web\Lib\Controller{
	/**
	 * 权限分组新增
	 *  最后修改时间 2015-3-18
	 *  只完成基础功能
	 *
	 * @author dengshaung
	 */
	protected function addAction(){
		if(!\App\Web\Lib\Request::isPost()){
			$this->display();
		}else{
			$user = $this->user;
			$name = \App\Web\Lib\Request::queryString('post.name','');
			$data = array();
			$data['company_id'] = $user['company_id'];
			$data['parent_id'] = 0;
			$data['name'] = $name;
			$res = \Common\Helper\Erp\Group::add($data);
			if($res){
				echo 'ok';
			}
 		}
	}
	
	/**
	 * 权限分组列表
	 *  最后修改时间 2015-3-18
	 *  只完成基础功能 列表的具体形式还需要跟产品沟通
	 *
	 * @author dengshaung
	 */
	protected function listAction(){
		$user = $this->user;
		$groupModel = new \Common\Model\Erp\Group();
		$list = $groupModel->getData(array('company_id'=>$user['company_id']));
		print_r($list);
	}
}