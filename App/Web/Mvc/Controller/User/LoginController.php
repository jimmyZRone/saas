<?php
namespace App\Web\Mvc\Controller\User;
/**
 * 用户登录
 * @author lishengyou
 * 最后修改时间 2015年4月7日 上午10:07:36
 *
 */
class LoginController extends \App\Web\Lib\Controller{
	/**
	 * 显示登录页面
	 * @author lishengyou
	 * 最后修改时间 2015年4月7日 上午10:28:31
	 *
	 */
	public function indexAction(){
		$callback = \App\Web\Lib\Request::queryString('get.callback');
		if(!$callback){
			$callback = \App\Web\Helper\Url::parse('index/index');
			$callback = base64_encode($callback);
		}
		
		//兼容ERP坑啊~~~
		$this->assign('jooozo_erp_login', \Core\Config::get('db:jooozo_erp_url.login'));
		
		$this->assign('callback', $callback);
		//判断自动登录
		$cookie = new \Common\Helper\Cookie();
		$autouid = $cookie->getCookie('LOG_UID');
		if(!$autouid){
			return $this->display();
		}
		$autouid = \Common\Helper\Encrypt::rsaDecodeing($autouid);
		if(!$autouid){
			return $this->display();
		}
		\Common\Helper\Erp\User::setSessionId($autouid);
		$user = \Common\Helper\Erp\User::getCurrentUser();
		if(!$user){
			\Common\Helper\Erp\User::setSessionId(null);
			return $this->display();
		}
		//修改最后登录时间
		$userModel = new \Common\Model\Erp\User();
		$userModel->edit(array('user_id'=>$user['user_id']), array('last_longing_time'=>time()));
		$callback = rawurldecode(base64_decode($callback));
		header('Location:'.$callback);
	}
	/**
	 * AJAX登录
	 * @author lishengyou
	 * 最后修改时间 2015年4月7日 上午11:06:11
	 *
	 */
	public function loginAction(){
		if(!\App\Web\Lib\Request::isAjax()){
			$this->display('index');
			return null;
		}
		$phone = \App\Web\Lib\Request::queryString('post.user');
		$passwd = \App\Web\Lib\Request::queryString('post.passwd');
		if(empty($phone)||empty($passwd)){
			return $this->returnAjax(array('status'=>0,'message'=>'用户名密码不能为空'));
		}
		$helper = new \Common\Helper\Erp\User();
		$user = \Common\Helper\Erp\User::check($phone,$passwd,true);
		if(!$user){
			return $this->returnAjax(array('status'=>0,'message'=>'用户名或密码错误'));
		}
		//取扩展信息
		$userExtendModel = new \Common\Model\Erp\UserExtend();
		$userExtend = $userExtendModel->getOne(array('user_id'=>$user['user_id']));
		$getCityByIpHelper = new \Common\Helper\Erp\GetCityByIp();
		$city_id = $getCityByIpHelper->GetIpLookup();
		if(!$userExtend){
			//存入扩展信息
			$userExtend = array('user_id'=>$user['user_id'],
					'name'=>$user['username'],
					'contact'=>$user['username'],
					'gender'=>1,'birthday'=>date('Y-m-d'),
					'city_id'=>$city_id
			);
			$userExtendModel->insert($userExtend);
		}
		//设置当前获取的城市
		$helper->setCitySesion($city_id);
		//修复系统分类
		$feetypeHelper = new \Common\Helper\Erp\FeeType();
		$feetypeHelper->repairSystemCategories($user['company_id']);
		$user = array_merge($user,$userExtend);
		if(\Common\Helper\Erp\User::loginUser($user,'web')){
			//修改最后登录时间
			$userModel = new \Common\Model\Erp\User();
			$userModel->edit(array('user_id'=>$user['user_id']), array('last_longing_time'=>time()));
			$callback = \App\Web\Lib\Request::queryString('get.callback');
			$callback = rawurldecode(base64_decode($callback));
			//自动登录注入
			$autologin = \App\Web\Lib\Request::queryString('post.autologin',0,'intval');
			$cookie = new \Common\Helper\Cookie();
			if($autologin){
				$autologinUid = \Common\Helper\Encrypt::rsaEncodeing(\Common\Helper\Erp\User::getSessionId());
				$cookie->setCookie('LOG_UID',$autologinUid,strtotime('+10 years',time()),'/');
			}else{
				$cookie->setCookie('LOG_UID',$autologinUid,strtotime('-1 years',time()),'/');
			}
			//写登录日志
			\Common\Helper\Erp\OperationLog::save($user['user_id'], \Common\Helper\Erp\OperationLog::ACTION_USER_LOGIN, $user['user_id'], get_client_ip());
			return $this->returnAjax(array('status'=>1,'url'=>$callback));
		}else{
			return $this->returnAjax(array('status'=>0,'message'=>'用户名或密码错误'));
		}
	}
}