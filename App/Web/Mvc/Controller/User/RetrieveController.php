<?php
namespace App\Web\Mvc\Controller\User;
/**
 * 找回密码
 * @author lishengyou
 * 最后修改时间 2015年4月7日 上午10:07:36
 *
 */
class RetrieveController extends \App\Web\Lib\Controller{
	/**
	 * 找回密码第一步
	 * @author lishengyou
	 * 最后修改时间 2015年4月7日 上午10:07:50
	 *
	 */
	public function indexAction(){
		$this->display('index');
	}
	/**
	 * 保存新密码
	 * @author lishengyou
	 * 最后修改时间 2015年4月7日 下午5:37:56
	 *
	 */
	public function saveAction(){
		//前一步的手机号和验证码重新进行验证码
		$phone = \App\Web\Lib\Request::queryString('post.phone');
		$code = \App\Web\Lib\Request::queryString('post.code');
		if(!$phone || !$code){
			return $this->returnAjax(array('status'=>0,'message'=>'手机或验证码不能为空'));
		}
		$result = \Common\Helper\Sms\TianyiCaptcha::check($phone, $code, false);
		if(!$result){
			return $this->returnAjax(array('status'=>0,'message'=>'手机验证码错误'));
		}
		//密码处理
		$passwd = \App\Web\Lib\Request::queryString('post.passwd');
		$cfm_passwd = \App\Web\Lib\Request::queryString('post.cfm_passwd');
		if(!$passwd || $passwd != $cfm_passwd){
			return $this->returnAjax(array('status'=>0,'message'=>'两次密码不一致'));
		}
		$result = \Common\Helper\ValidityVerification::IsPasswd($passwd);
		if($result['status'] != 1){
			return $this->returnAjax($result);
		}
		//开始保存密码
		$userModel = new \Common\Model\Erp\User();
		$user = $userModel->getByPhone($phone);
		if(!$user){
			return $this->returnAjax(array('status'=>0,'message'=>'用户不存在'));
		}
		if(\Common\Helper\Erp\User::editUser($user['user_id'],array('password'=>$passwd))){//保存成功
			\Common\Helper\Sms\TianyiCaptcha::clear($phone);//清除验证码
			return $this->returnAjax(array('status'=>1));
		}
		return $this->returnAjax(array('status'=>0,'message'=>'修改失败'));
	}
}