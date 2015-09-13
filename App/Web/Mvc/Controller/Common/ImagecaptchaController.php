<?php
namespace App\Web\Mvc\Controller\Common;
/**
 * 图片验证码
 * @author lishengyou
 * 最后修改时间 2015年4月7日 下午1:49:23
 *
 */
class ImagecaptchaController extends \App\Web\Lib\Controller{
	/**
	 * 显示验证码
	 * @author lishengyou
	 * 最后修改时间 2015年4月7日 下午1:49:49
	 *
	 */
	public function showAction(){
		$width = \App\Web\Lib\Request::queryString('get.width',80,'intval');
		$height = \App\Web\Lib\Request::queryString('get.height',26,'intval');
		$length = \App\Web\Lib\Request::queryString('get.length',4,'intval');
		$domain = \App\Web\Lib\Request::queryString('get.domain','default');
		//数据修正
		if($width < 80 || $width > 160){
			$width = 80;
		}
		if($height < 22 || $height > 60){
			$width = 22;
		}
		if($length < 4 || $length > 8){
			$width = 4;
		}
		if(!in_array($domain, array('default','register'))){
			$domain = 'default';
		}
		
		$captcha = new \Common\Helper\Captcha();
		$captcha->create($width, $height,$length,$domain);
	}
	/**
	 * 验证验证码
	 * @author lishengyou
	 * 最后修改时间 2015年4月7日 下午1:49:49
	 *
	 */
	public function checkAction(){
		$domain = \App\Web\Lib\Request::queryString('get.domain','default');
		$code = \App\Web\Lib\Request::queryString('get.code');
		
		if(!$code){
			return $this->returnAjax(array('status'=>0,'message'=>'图形验证码不能为空'));
		}
		$captcha = new \Common\Helper\Captcha();
		$result = $captcha->check($code, $domain,false);
		return $result ? $this->returnAjax(array('status'=>1)) : $this->returnAjax(array('status'=>0,'message'=>'图形验证码错误'));
	}
}