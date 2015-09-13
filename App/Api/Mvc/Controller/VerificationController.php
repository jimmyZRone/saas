<?php

namespace App\Api\Mvc\Controller;

class VerificationController extends \App\Api\Lib\Controller {
	/**
	 * 发送验证码
	 * @author lishengyou
	 * 最后修改时间 2015年4月7日 下午1:49:49
	 *
	 */
	public function sendAction(){
		$phone = I('phone');
		if(!$phone || !\Common\Helper\ValidityVerification::IsPhone($phone)){
			return $this->returnAjax(array('status'=>0,'message'=>'请输入正确的手机号'));
		}
		$ipaddress = \App\Web\Lib\Request::getClientIp();
		$result = \Common\Helper\Sms\TianyiCaptcha::send($phone,\Common\Helper\Sms\TianyiCaptcha::EXP_TIME,$ipaddress);
		return $this->returnAjax($result);
	}
	/**
	 * 发送验证码到用户手机
	 * @author yusj | 最后修改时间 2015年4月29日下午4:30:03
	 */
	public function sendCodeToPhoneAction(){
		$phone = I('phone');
		$is_user_exists = I('type');//1 忘记密码 0 注册
		if(empty($phone) || !$phone || !\Common\Helper\ValidityVerification::IsPhone($phone)){
			return_error(104);//手机号码格式错误 
		}
		if($is_user_exists!='0' && $is_user_exists!='1'){
			return_error(120);//用户类型错误
		}
		$userModel = new \Common\Model\Erp\User();
		$user = $userModel->getByPhone($phone);
		if($is_user_exists==1){
			if(!$user){
				return_error(114);//用户不存在
			}
			$ipaddress = \App\Web\Lib\Request::getClientIp();
			$result = \Common\Helper\Sms\TianyiCaptcha::send($phone,\Common\Helper\Sms\TianyiCaptcha::EXP_TIME,$ipaddress);
			if($result['status']>=1){
				return_success(array());
			}else{
				return_error(122);//验证码发送过于频繁
			}
		}
		if($is_user_exists==0){
			if($user){
				return_error(121);//用户已经存在
			}
			$ipaddress = \App\Web\Lib\Request::getClientIp();
			$result = \Common\Helper\Sms\TianyiCaptcha::send($phone,\Common\Helper\Sms\TianyiCaptcha::EXP_TIME,$ipaddress);
			if($result['status']>=1){
				return_success(array());
			}else{
				return_error(128);
			}
		}
		
		
	}
	/**
	 * 发送到用户
	 * @author lishengyou
	 * 最后修改时间 2015年4月7日 下午5:47:43
	 *
	 */
	public function sendtouserAction(){
		$phone = I('phone');
		if(!$phone || !\Common\Helper\ValidityVerification::IsPhone($phone)){
			return_error(101);
		
		}
		$userModel = new \Common\Model\Erp\User();
		$user = $userModel->getByPhone($phone);
		if(!$user){
			return_error(102);
			
		}
		$ipaddress = \App\Web\Lib\Request::getClientIp();
		$result = \Common\Helper\Sms\TianyiCaptcha::send($phone,\Common\Helper\Sms\TianyiCaptcha::EXP_TIME,$ipaddress);
		if($result['status']>=1){
			return_success(array());
		}else{
			return_error(103);
		}
		return $this->returnAjax($result);
	}
	/**
	 * 发送到没有注册的用户
	 * @author lishengyou
	 * 最后修改时间 2015年4月7日 下午5:47:43
	 *
	 */
	public function sendtonotuserAction(){
		$phone = I('phone');
		if(!$phone || !\Common\Helper\ValidityVerification::IsPhone($phone)){
			return_error(101);
			
		}
		$userModel = new \Common\Model\Erp\User();
		$user = $userModel->getByPhone($phone);
		if($user){
			return_error(102);

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
		return $result ? $this->returnAjax(array('status'=>1)) : $this->returnAjax(array('status'=>0,'message'=>'验证码错误'));
	}
	}