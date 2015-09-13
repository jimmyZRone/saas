<?php
namespace Common\Helper;
/**
 * 验证码类
 * @author lishengyou
 * 最后修改时间 2014年11月10日 下午3:36:16
 *
 */
class Captcha {
	protected $session_id = 'captcha.image.';
	/**
	 * 生成验证码
	 * @author lishengyou
	 * 最后修改时间 2014年11月10日 下午3:36:25
	 *
	 * @param unknown $width
	 * @param unknown $height
	 * @param number $length
	 */
	public function create($width,$height,$length=4,$namespace='default'){
		$sessionId = $this->session_id.$namespace;
		$captcha = new \Captcha();
		$captcha->width = $width;
		$captcha->height = $height;
		$captcha->codelen = $length;
		$captcha->doimg();
		$code = $captcha->getCode();
		\Core\Session::save($sessionId, $code);
	}
	/**
	 * 验证
	 * @author lishengyou
	 * 最后修改时间 2014年11月10日 下午3:41:24
	 *
	 * @param unknown $code
	 * @param string $namespace
	 */
	public function check($code,$namespace='default',$del=true){
		if(!$code) return false;
		$code = strtolower($code);
		$sessionId = $this->session_id.$namespace;
		$data = \Core\Session::read($sessionId);
		$rs = $data === $code;
		if(!!$rs && $del)
			$this->clear($namespace);
		return !!$rs;
	}
	/**
	 * 清除验证码
	 * @author lishengyou
	 * 最后修改时间 2015年4月9日 下午2:07:39
	 *
	 * @param string $namespace
	 */
	public function clear($namespace='default'){
		$sessionId = $this->session_id.$namespace;
		\Core\Session::delete($sessionId);
	}
}