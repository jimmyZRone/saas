<?php
namespace App\Callback\Lib;
class Controller extends \Core\Mvc\Controller{
	protected $_assign = array();
	/**
	 * 注入变量
	 * @author lishengyou
	 * 最后修改时间 2015年2月28日 上午11:25:27
	 *
	 * @param unknown $assignname
	 * @param unknown $assignvalue
	 * @param string $nocache
	 */
	public function assign($assignname,$assignvalue,$nocache = false){
		$this->_assign[$assignname] = array($assignvalue,$nocache);
	}
	/**
	 * 显示
	 * @author lishengyou
	 * 最后修改时间 2015年2月28日 上午11:32:53
	 *
	 * @param unknown $tpl
	 */
	public function display($tpl=null){
		$template = new \Core\Mvc\Template();
		$template->setTemplateDir(__DIR__.'/../Mvc/Template');
		foreach ($this->_assign as $assignname => $assignvalue){
			$template->assign($assignname, $assignvalue[0]);
		}
		$tpl = $this->parsetemplatefilename($tpl);
		$this->parstemplateconfig($template);
		$template->display($tpl);
	}
	/**
	 * 解析模板路径
	 * @author lishengyou
	 * 最后修改时间 2015年4月2日 下午4:21:35
	 *
	 * @param unknown $tpl
	 */
	protected function parsetemplatefilename($tpl){
		$route = \Core\App::getNowApp()->getContainer()->getRoute();
		$controller = $route['controller'];
		$namespace = $route['controller_namespace'];
		$controller = ltrim(substr($controller, strlen($namespace)),'\\');
		$controller = strtolower(str_replace('\\', '/', $controller));
		$controller = explode('/', $controller);
		$controller = array_filter($controller);
		$controller = array_map('ucwords', $controller);
		$controller = implode('/', $controller);
		$controller = substr($controller, 0,-10);
		if(!$tpl){
			$action = substr($route['action'],0,-6);
			$action = strtolower($action);
			$tpl = $controller.'/'.$action.'.phtml';
		}elseif(!strpos($tpl, '/')){
			$tpl = $controller.'/'.$tpl;
		}
		if(!strpos($tpl, '.phtml')){
			$tpl .= '.phtml';
		}
		return $tpl;
	}
	/**
	 * 解析返回模板
	 * @author lishengyou
	 * 最后修改时间 2015年4月2日 下午4:22:57
	 *
	 * @param string $tpl
	 * @return \Core\Mvc\Ambigous
	 */
	public function fetch($tpl=null){
		$template = new \Core\Mvc\Template();
		$template->setTemplateDir(__DIR__.'/../Mvc/Template');
		foreach ($this->_assign as $assignname => $assignvalue){
			$template->assign($assignname, $assignvalue[0]);
		}
		$tpl = $this->parsetemplatefilename($tpl);
		$this->parstemplateconfig($template);
		return $template->fetch($tpl);
	}
	/**
	 * 解析模板配置
	 * @author lishengyou
	 * 最后修改时间 2015年3月11日 上午9:35:30
	 *
	 * @param unknown $template
	 */
	protected function parstemplateconfig($template){
		//加载自动注册函数
		
	}
	/**
	 * Ajax返回
	 * @author lishengyou
	 * 最后修改时间 2015年3月10日 下午4:20:38
	 *
	 * @param array $data
	 */
	public function returnAjax(array $data){
		if(\App\Web\Lib\Request::isAjax()){
			echo json_encode($data);
		}else{
			$callback = \App\Web\Lib\Request::queryString('get.callback');
			if($callback){
				header('Content-Type:text/javascript;charset=utf-8');
				echo $callback."(".json_encode($data).");";
			}
		}
	}
	/**
	 * 返回JSON
	 * @author lishengyou
	 * 最后修改时间 2015年4月7日 下午3:49:59
	 *
	 * @param array $data
	 */
	public function returnJson(array $data){
		echo json_encode($data);
	}
}