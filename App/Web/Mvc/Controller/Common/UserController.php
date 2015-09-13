<?php
namespace App\Web\Mvc\Controller\Common;
/**
 * 用户相关
 * @author lishengyou
 * 最后修改时间 2015年4月7日 下午1:49:23
 *
 */
class UserController extends \App\Web\Lib\Controller{
	/**
	 * 验证用户是否存在
	 * @author lishengyou
	 * 最后修改时间 2015年4月9日 下午2:23:53
	 *
	 */
	public function isexistAction(){
		$phone = \App\Web\Lib\Request::queryString('get.phone');
		$userModel = new \Common\Model\Erp\User();
		$user = $userModel->getByPhone($phone);
		if($user){
			return $this->returnAjax(array('status'=>1,'isexist'=>1));
		}
		//兼容ERP坑啊~~~
		/*if(\App\Web\Helper\Jooozo\User::IsExist($phone)){
			return $this->returnAjax(array('status'=>1,'isexist'=>1,'iserp'=>1));
		}*/
		
		return $this->returnAjax(array('status'=>1,'isexist'=>0));
	}
}