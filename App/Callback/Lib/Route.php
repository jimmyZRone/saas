<?php
namespace App\Callback\Lib;
/**
 * 路由
 * @author lishengyou
 * 最后修改时间 2015年2月28日 下午2:40:21
 *
 */
class Route extends \Core\Mvc\Route{
	public function __construct(){
		$namespace = explode('\\', __NAMESPACE__);
		array_pop($namespace);
		$namespace = implode('\\', $namespace).'\Mvc\Controller';
		$this->setControllerNamspace($namespace);
		$this->setErrorController($namespace.'\ErrorController');
	}
}