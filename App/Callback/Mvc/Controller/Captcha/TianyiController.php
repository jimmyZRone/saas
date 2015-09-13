<?php
namespace App\Callback\Mvc\Controller\Captcha;
/**
 * 天翼验证码回调
 * @author lishengyou
 * 最后修改时间 2015年4月7日 下午3:01:12
 *
 */
class TianyiController extends \App\Callback\Lib\Controller{
	/**
	 * 保存验证码
	 * @author lishengyou
	 * 最后修改时间 2015年4月7日 下午3:01:07
	 *
	 */
	public function saveAction(){
		$captchaid = \Common\Helper\Http\Request::queryString('get.captchaid');
		$uniqid = \Common\Helper\Http\Request::queryString('get.uniqid');
		$code = \Common\Helper\Http\Request::queryString('post.rand_code');
		\Common\Helper\Sms\TianyiCaptcha::save($captchaid,$uniqid,$code);
	}
	/**
	 * 模拟AccessToken
	 * @author lishengyou
	 * 最后修改时间 2015年4月7日 下午3:01:56
	 *
	 */
	public function simulationaccesstokenAction(){
		return $this->returnJson(array('access_token'=>uniqid(),'expires_in'=>time()+10000000));
	}
	/**
	 * 模拟Token
	 * @author lishengyou
	 * 最后修改时间 2015年4月7日 下午3:04:28
	 *
	 */
	public function simulationtokenAction(){
		return $this->returnJson(array('token'=>uniqid()));
	}
	/**
	 * 模拟发送
	 * @author lishengyou
	 * 最后修改时间 2015年4月7日 下午3:05:06
	 *
	 */
	public function simulationsendAction(){
		//回调保存
		$url = rawurldecode($_POST['url']);
		$rand_code = \Common\Helper\String::rand(4,\Common\Helper\String::RAND_TYPE_NUMBER);
		//$rand_code = 1234;
		$data = array(
			'rand_code'=>$rand_code
		);
		$mail = new \Common\Helper\Mail();
		$mail->setServer("smtp.126.com", "ayoutest@126.com", "nqkjrixjudewqddr");
		$mail->setFrom("ayoutest@126.com");
		$mail->setReceiver("waw@jooozo.com");
		$mail->setMail("这是你来自内网测试的验证码","你本次的验证码为:{$rand_code}");
		$mail->sendMail();
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url); // 抓取指定网页
		curl_setopt($ch, CURLOPT_HEADER, 0); // 设置header
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 要求结果为字符串且输出到屏幕上
		curl_setopt($ch, CURLOPT_POST, 1); // post提交方式
		curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($data));
		$result = curl_exec($ch); // 运行curl
		return $this->returnJson(array('status'=>1,'identifier'=>$rand_code));
	}
}