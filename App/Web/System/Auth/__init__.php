<?php
namespace App\Web\System;
/**
 * MVC
 * @author lishengyou
 * 最后修改时间 2015年3月2日 下午1:31:33
 *
 */
class Auth extends \Core\Program\System{
	protected static function __init__($args){
		return new self();
	}
	/**
	 * 初始化运行
	 * @author lishengyou
	 * 最后修改时间 2015年2月26日 上午11:13:21
	 *
	 */
	protected function initRun(){
		\Core\App\Event::bind(\App\Web\Lib\Listing::ROUTE_COMPLETE, array($this,'initRoute'));
		\Core\App\Event::bind(\App\Web\Lib\Listing::DB_SELECT_CREATED, array($this,'authDbSelect'));
	}
	/**
	 * 路由完成
	 * @author lishengyou
	 * 最后修改时间 2015年3月2日 下午2:04:37
	 *
	 */
	protected function initRoute($e){
		$route = $e['e'];
		$action = $route['action'];
		$controller = $route['controller'];
		$namespace = $route['controller_namespace'];
		$controller = ltrim(substr($controller, strlen($namespace)),'\\');
		$controller = strtolower(substr($controller, 0,-10));
		$controller = str_replace('\\', '-', $controller);
		$action = substr($route['action'],0,-6);
		$action = strtolower($action);
		
		$islogin = $this->islogin($controller, $action);
		//判断当前是否需要验证登录
		if(!$islogin && !\Common\Helper\Erp\User::isLogin()){
			//当前不需要登录，并且没有登录
			return true;
		}
		if(!$islogin){
			//当前不需要登录，但是是在登录状态，判断是否能访问
			$loginno = \Core\Config::get('web/auth:loginno');//不需要登录的
			$loginno = $loginno ? $loginno : array();
			foreach ($loginno as $key => $value){
				if(is_string($value) && strpos($value, ',')){
					$value = explode(',', $value);
				}
				$loginno[$key] = $value;
			}
			if(isset($loginno[$controller])){
				if(is_array($loginno[$controller]) && in_array($action, $loginno[$controller])){
					if(\App\Web\Lib\Request::isAjax()){
						echo json_encode(array('__status__'=>301,'__url__'=>\App\Web\Helper\Url::parse('user-login/index')));die();
					}else{
						\App\Web\Helper\Url::jump('@index/index');
					}
				}else if(is_string($loginno[$controller]) && ($loginno[$controller] == '*' || $loginno[$controller] == $action)){
					if(\App\Web\Lib\Request::isAjax()){
						echo json_encode(array('__status__'=>301,'__url__'=>\App\Web\Helper\Url::parse('user-login/index')));die();
					}else{
						\App\Web\Helper\Url::jump('@index/index');
					}
				}
			}
		}
		//如果需要登录并且用户未登录，跳转到登录页面
		if(!\Common\Helper\Erp\User::isLogin()){
			//未登录
			$callback = base64_encode($_SERVER['REQUEST_URI']);
			if(\App\Web\Lib\Request::isAjax()){
				echo json_encode(array('__status__'=>301,'__message__'=>'您需要重新登录！','__url__'=>\App\Web\Helper\Url::parse('user-login/index')));die();
			}else{
				\App\Web\Helper\Url::jump('@user-login/index/callback/'.$callback);
			}
		}
		//判断当前用户是否有调用当前模块的权限
		if(!$this->modularAuth($controller, $action)){
			//当前用户没有访问模块的权限
			return true;
		}
		//调用当前模块对应的处理方法
		$this->modularActionAuth($controller, $action);
	}
	/**
	 * 是否需要登录
	 * @author lishengyou
	 * 最后修改时间 2015年3月2日 下午3:23:50
	 *
	 * @param unknown $controller
	 * @param unknown $action
	 * @return boolean
	 */
	protected function islogin($controller,$action){
		$login = \Core\Config::get('web/auth:login');//需要登录的
		$login = $login ? $login : array();
		foreach ($login as $key => $value){
			if(is_string($value) && strpos($value, ',')){
				$value = explode(',', $value);
			}
			$login[$key] = $value;
		}
		
		$nologin = \Core\Config::get('web/auth:nologin');//不需要登录的
		$nologin = $nologin ? $nologin : array();
		foreach ($nologin as $key => $value){
			if(is_string($value) && strpos($value, ',')){
				$value = explode(',', $value);
			}
			$nologin[$key] = $value;
		}
		
		if(isset($login[$controller])){//特例需要登录
			if(is_array($login[$controller]) && in_array($action, $login[$controller])){
				return true;
			}else if(is_string($login[$controller]) && ($login[$controller] == '*' || $login[$controller] == $action)){
				return true;
			}
		}
		if(isset($nologin[$controller])){//特例不需要登录
			if(is_array($nologin[$controller]) && in_array($action, $nologin[$controller])){
				return false;
			}else if(is_string($nologin[$controller]) && ($nologin[$controller] == '*' || $nologin[$controller] == $action)){
				return false;
			}
		}
		return true;
	}
	/**
	 * 模块权限
	 * @author lishengyou
	 * 最后修改时间 2015年2月27日 上午10:12:40
	 *
	 * @param string $controller
	 * @param string $action
	 */
	protected function modularAuth($controller,$action){
		return true;
	}
	/**
	 * 模块动作权限处理
	 * @author lishengyou
	 * 最后修改时间 2015年2月27日 上午11:23:18
	 *
	 * @param unknown $controller
	 * @param unknown $action
	 */
	protected function modularActionAuth($controller,$action){
		$controllerClass = '\App\Web\System\Auth\Modular\\'.$controller.'Modular';
		$actionModel = $action.'Action';
		if(!\Core\Autoload::isExists($controllerClass)){
			return false;
		}
		$controllerRef = new \ReflectionClass($controllerClass);
		$controllerObj = $controllerRef->newInstance();
		if(!$controllerRef->hasMethod($actionModel)){
			return false;
		}
		$callback = new \Core\Event\Callback();
		$callback->bind(array($controllerObj,$actionModel));
		$callback->trigger();
	}
	
	protected function authDbSelect($e){
		//print_r($e);//die;
		//$authHandle = new AuthHandle();
		$container = \Core\App::getNowApp()->getContainer();
		$route = $container->getRoute();
// 		print_r($route);die;
		$modularModel = new \App\Web\Mvc\Model\Modular();
		$modular_id = $modularModel->getIdByName($route['originalController']);
		
		$userModel = new \App\Web\Mvc\Model\User();
		$user = $userModel->getCurrentUser();
// 		print_r($user);die;
	 	\App\Web\System\Auth\AuthHandle::handle($e['e'], $modular_id, $user['id'], \App\Web\System\Auth\AuthHandle::MANIPULATE_SELECT);
	}
}