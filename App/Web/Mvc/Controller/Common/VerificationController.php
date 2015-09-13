<?php
namespace App\Web\Mvc\Controller\Common;
/**
 * 验证码
 * @author lishengyou
 * 最后修改时间 2015年4月7日 下午1:49:23
 *
 */
class VerificationController extends \App\Web\Lib\Controller{
	protected function checkImgCode(){//img_code
		if(!isset($_GET['img_code'])){
			return false;
		}
		$img_code = \App\Web\Lib\Request::queryString('get.img_code');
		$captcha = new \Common\Helper\Captcha();
		$result = $captcha->check($img_code,'default',false);
		return $result ? true : false;
	}
	/**
	 * 发送验证码
	 * @author lishengyou
	 * 最后修改时间 2015年4月7日 下午1:49:49
	 *
	 */
	public function sendAction(){
		$phone = \App\Web\Lib\Request::queryString('get.phone');
		if(!$phone || !\Common\Helper\ValidityVerification::IsPhone($phone)){
			return $this->returnAjax(array('status'=>0,'message'=>'请输入正确的手机号'));
		}
		if(!$this->checkImgCode()){
			return $this->returnAjax(array('status'=>0,'message'=>'图形验证码错误'));
		}
		$ipaddress = \App\Web\Lib\Request::getClientIp();
		$result = \Common\Helper\Sms\TianyiCaptcha::send($phone,\Common\Helper\Sms\TianyiCaptcha::EXP_TIME,$ipaddress);
		return $this->returnAjax($result);
	}
	/**
	 * 发送到用户
	 * @author lishengyou
	 * 最后修改时间 2015年4月7日 下午5:47:43
	 *
	 */
	public function sendtouserAction(){
		$phone = \App\Web\Lib\Request::queryString('get.phone');
		if(!$phone || !\Common\Helper\ValidityVerification::IsPhone($phone)){
			return $this->returnAjax(array('status'=>0,'message'=>'请输入正确的手机号'));
		}
		if(!$this->checkImgCode()){
			return $this->returnAjax(array('status'=>0,'message'=>'图形验证码错误'));
		}
		$userModel = new \Common\Model\Erp\User();
		$user = $userModel->getByPhone($phone);
		//兼容ERP坑啊~~~
		/*if(!$user){
			$user = \App\Web\Helper\Jooozo\User::IsExist($phone);
		}*/
		
		if(!$user){
			return $this->returnAjax(array('status'=>0,'message'=>'用户不存在'));
		}
		$ipaddress = \App\Web\Lib\Request::getClientIp();
		$result = \Common\Helper\Sms\TianyiCaptcha::send($phone,\Common\Helper\Sms\TianyiCaptcha::EXP_TIME,$ipaddress);
		return $this->returnAjax($result);
	}
	/**
	 * 发送到没有注册的用户
	 * @author lishengyou
	 * 最后修改时间 2015年4月7日 下午5:47:43
	 *
	 */
	public function sendtonotuserAction(){
		$phone = \App\Web\Lib\Request::queryString('get.phone');
		if(!$phone || !\Common\Helper\ValidityVerification::IsPhone($phone)){
			return $this->returnAjax(array('status'=>0,'message'=>'请输入正确的手机号'));
		}
		if(!$this->checkImgCode()){
			return $this->returnAjax(array('status'=>0,'message'=>'图形验证码错误'));
		}
		$userModel = new \Common\Model\Erp\User();
		$user = $userModel->getByPhone($phone);
		if($user){
			return $this->returnAjax(array('status'=>0,'message'=>'用户已经存在'));
		}
		//兼容ERP坑啊~~~
		if(\App\Web\Helper\Jooozo\User::IsExist($phone)){
			return $this->returnAjax(array('status'=>0,'message'=>'您是ERP V1.0的用户，请直接点击登录进入ERP V1.0系统。'));
		}
		
        
		$ipaddress = \App\Web\Lib\Request::getClientIp();
		$result = \Common\Helper\Sms\TianyiCaptcha::send($phone,\Common\Helper\Sms\TianyiCaptcha::EXP_TIME,$ipaddress);
		return $this->returnAjax($result);
	}
	/**
	 * 验证验证码
	 * @author lishengyou
	 * 最后修改时间 2015年4月7日 下午1:49:49
	 *
	 */
	public function checkAction(){
		$phone = \App\Web\Lib\Request::queryString('get.phone');
		$code = \App\Web\Lib\Request::queryString('get.code');
		if(!$phone || !$code){
			return $this->returnAjax(array('status'=>0,'message'=>'手机或验证码不能为空'));
		}
		$result = \Common\Helper\Sms\TianyiCaptcha::check($phone, $code,false);
		return $result ? $this->returnAjax(array('status'=>1)) : $this->returnAjax(array('status'=>0,'message'=>'手机验证码错误'));
	}
}