<?php
namespace App\Web\System;
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
	/**
	 * 插件初始化
	 * @author lishengyou
	 * 最后修改时间 2015年3月2日 下午1:42:30
	 *
	 */
	protected function initPlugins($e){
		$route = new \App\Web\Lib\Route();
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
		//完成路由
		\Core\App\Event::trigger(\App\Web\Lib\Listing::ROUTE_COMPLETE,$routeNotice);//完成路由
	}
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
		if(isset($query['rw'])){
			$rewrite = \Core\Config::get('web/app:rewrite');
			if(is_array($rewrite) && isset($rewrite[$query['rw']])){
				$rewrite = $rewrite[$query['rw']];
				unset($query['rw']);
				$url = http_build_query($query);
				$url = $rewrite.'&'.$url;
			}
		}
		$url = trim($url,'&');
		return $url;
	}
	protected $_route = null;
	/**
	 * 控制器初始化
	 * @author lishengyou
	 * 最后修改时间 2015年3月2日 下午1:43:51
	 *
	 * @return mixed
	 */
	protected function initController(){
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
			//触发事件
			\Core\App\Event::trigger(\App\Web\Lib\Listing::TASK_QUEUE_INIT);
			$runtime->start();
			\Core\App\Event::trigger(\App\Web\Lib\Listing::REQUEST_END);
		}
	}
	/**
	 * 初始化(non-PHPdoc)
	 * @author lishengyou
	 * 最后修改时间 2015年3月2日 下午1:45:15
	 *
	 * @see \Core\Program::initRun()
	 */
	protected function initRun(){
		\Core\App\Event::bind(\App\Web\Lib\Listing::PLUGINS_INIT, array($this,'initPlugins'));
		\Core\App\Event::bind(\App\Web\Lib\Listing::PLUGINS_COMPLETE, array($this,'initController'));
	}
}