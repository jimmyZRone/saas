<?php
namespace App\Callback\System;
use Core\Program\Task\Runtime;
/**
 * MVC
 * @author lishengyou
 * 最后修改时间 2015年3月2日 下午1:31:33
 *
 */
class Mvc extends \Core\Program\System{
	protected static function __init__($args){
		return new self();
	}
	protected $_route = null;
	/**
	 * 重写
	 * @author lishengyou
	 * 最后修改时间 2015年3月11日 下午3:20:40
	 *
	 * @param unknown $url
	 */
	protected function rewrite($url){
		$query = array();
		parse_str($url,$query);
		$url = trim($url,'&');
		return $url;
	}
	/**
	 * 插件初始化
	 * @author lishengyou
	 * 最后修改时间 2015年3月2日 下午1:42:30
	 *
	 */
	public function initRoute(){
		$route = new \App\Callback\Lib\Route();
		$url = $this->rewrite($_SERVER['QUERY_STRING']);
		$_GET = array();
		parse_str($url,$_GET);
		$route->analyze($url);
		$container = \Core\App::getNowApp()->getContainer();
		$routeNotice = array(
				'controller' => $route->getController(),
				'action' => $route->getAction(),
				'originalController' => $route->getOriginalController(),
				'originalAction' => $route->getOriginalAction(),
				'controller_namespace'=>$route->getControllerNamespace()
		);
		$container->setRoute($routeNotice);
		$this->_route = $route;
	}
	/**
	 * 控制器初始化
	 * @author lishengyou
	 * 最后修改时间 2015年3月2日 下午1:43:51
	 *
	 * @return mixed
	 */
	public function initController(){
		$route = $this->_route;
		if(is_object($route)){
			$container = \Core\App::getNowApp()->getContainer();
			$queue = new \Core\Program\Task\Queue();
			$task = new \Core\Program\Task('mvc_controller_run');
			$task->setCallback(function() use($route){
				$controller = $route->getController();
				$action = $route->getAction();
				$ref = new \ReflectionClass($controller);
				$mehtod = $ref->getMethod($action);
				$refInstance = $ref->newInstance();
				if($mehtod->isPublic()){
					return $mehtod->invoke($refInstance);
				}else{
					$methodClosure = \Closure::bind(function() use($action){
						return $this->{$action}();
					}, $refInstance, $ref->getName());
					return $methodClosure();
				}
			});
			$queue->push($task);
			$runtime = new \Core\Program\Task\Runtime($queue);
			$container->setTaskRuntime($runtime);
			$runtime->start();
		}
	}
}