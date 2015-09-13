<?php
namespace Core\Mvc;
/**
 * 路由
 * @author lishengyou
 * 最后修改时间 2015年2月9日 上午11:08:43
 *
 */
class Route{
	protected $_controller_namespace = null;
	protected $_error_controller = null;
	protected $_query = null;
	protected $_actionname = null;
	protected $_controllername = null;
	protected $_original_actionname = null;
	protected $_original_controllername = null;
	/**
	 * 设置控制器基本命名空间
	 * @author lishengyou
	 * 最后修改时间 2015年2月10日 上午10:57:14
	 *
	 * @param unknown $namespce
	 */
	public function setControllerNamspace($namespce){
		$this->_controller_namespace = $namespce;
	}
	/**
	 * 取得控制器基本命名空间
	 * @author lishengyou
	 * 最后修改时间 2015年3月11日 上午10:01:22
	 *
	 * @return unknown
	 */
	public function getControllerNamespace(){
		return $this->_controller_namespace;
	}
	/**
	 * 设置404控制器
	 * @author lishengyou
	 * 最后修改时间 2015年2月10日 上午11:12:57
	 *
	 * @param unknown $controller
	 */
	public function setErrorController($controller){
		if(\Core\Autoload::isExists($controller)){
			$this->_error_controller = new \ReflectionClass($controller);
		}
	}
	/**
	 * 取得动作
	 * @author lishengyou
	 * 最后修改时间 2015年2月10日 上午11:30:58
	 *
	 * @return string
	 */
	public function getAction(){
		return $this->_actionname;
	}
	/**
	 * 取得控制器
	 * @author lishengyou
	 * 最后修改时间 2015年2月10日 上午11:31:10
	 *
	 * @return string
	 */
	public function getController(){
		return $this->_controllername;
	}
	/**
	 * 取得原始动作
	 * @author lishengyou
	 * 最后修改时间 2015年2月10日 上午11:30:58
	 *
	 * @return string
	 */
	public function getOriginalAction(){
		return $this->_original_actionname;
	}
	/**
	 * 取得原始控制器
	 * @author lishengyou
	 * 最后修改时间 2015年2月10日 上午11:31:10
	 *
	 * @return string
	 */
	public function getOriginalController(){
		return $this->_original_controllername;
	}
	/**
	 * 解析
	 * @author lishengyou
	 * 最后修改时间 2015年2月10日 上午10:55:36
	 *
	 * @param unknown $uri
	 */
	public function analyze($uri){
		if(!$this->_controller_namespace){
			return false;
		}
		$query = array();
		parse_str($uri,$query);
		$query['c'] = isset($query['c']) ? $query['c'] : 'index';
		$query['a'] = isset($query['a']) ? $query['a'] : 'index';
		$analyze = array_intersect_key($query, array('a'=>false,'c'=>false));
		$this->_query = array_diff_key($query, $analyze);
		$this->parse($analyze['c'], $analyze['a']);
	}
	/**
	 * 解析控制器和方法
	 * @author lishengyou
	 * 最后修改时间 2015年2月10日 上午11:09:29
	 *
	 * @param unknown $controller
	 * @param unknown $action
	 */
	protected function parse($controller,$action){
		$controller = strtolower($controller);
		if(strpos($controller, '-')){
			$controller = explode('-', $controller);
			$controller = array_filter($controller);
			$controller = array_map('ucwords', $controller);
			$controller = implode('\\', $controller);
		}else{
			$controller = ucwords($controller);
		}
		$action = strtolower($action);
		
		$controllerClassname = $this->_controller_namespace.'\\'.$controller.'Controller';
		$this->_original_controllername = $controller;
		$this->_original_actionname = $action.'Action';
		if(!\Core\Autoload::isExists($controllerClassname)){
			if(!$this->_error_controller || !$this->_error_controller->hasMethod('controllerAction')){//控制器不存在，并且没有设置404控制器
				throw new \Exception('控制器不存在');
			}
			$controllerClassname = $this->_error_controller->getName();
			$controller = substr($this->_error_controller->getShortName(),0,0-(strlen('Controller')));
			$action = 'controller';
		}
		$methodName = $action.'Action';
		$ref = new \ReflectionClass($controllerClassname);
		if(!$ref->hasMethod($methodName)){
			//名称不存在
			if(!$this->_error_controller || !$this->_error_controller->hasMethod('actionAction')){//控制器不存在，并且没有设置404控制器
				throw new \Exception('动作不存在');
			}
			$controllerClassname = $this->_error_controller->getName();
			$controller = substr($this->_error_controller->getShortName(),0,0-(strlen('Controller')));
			$action = 'action';
			$methodName = 'actionAction';
		}
		if(!$ref->isSubclassOf('\Core\Mvc\Controller')){
			//当前类没有实现MVC的Controller
			if(!$this->_error_controller || !$this->_error_controller->hasMethod('nosubclassAction')){//控制器不存在，并且没有设置404控制器
				throw new \Exception('当前控制器没有实现MVC的控制器');
			}
			$controllerClassname = $this->_error_controller->getName();
			$controller = substr($this->_error_controller->getShortName(),0,0-(strlen('Controller')));
			$action = 'nosubclass';
			$methodName = 'nosubclassAction';
		}
		$this->_actionname = $methodName;
		$this->_controllername = $controllerClassname;
	}
}