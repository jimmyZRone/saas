<?php
namespace App\Web\Lib;
class Controller extends \Core\Mvc\Controller{
	protected $_assign = array();
	protected $user = array();
	protected $_auth_module_name = '';
	public function __construct()
	{	
		$this->user=\Common\Helper\Erp\User::getCurrentUser();
	}
	/**
	 * 取得用户
	 * @author lishengyou
	 * 最后修改时间 2015年3月30日 下午3:43:42
	 *
	 * @return multitype:
	 */
	public function getUser(){
		return $this->user;
	}
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
	public function display($tpl=null,$mode = \Core\Mvc\Template::MODE_SMARTY){
		$template = new \Core\Mvc\Template($mode);
		$template->setTemplateDir(__DIR__.'/../Mvc/Template');
		foreach ($this->_assign as $assignname => $assignvalue){
			$template->assign($assignname, $assignvalue[0]);
		}
		$tpl = $this->parsetemplatefilename($tpl,$mode);
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
	protected function parsetemplatefilename($tpl,$mode){
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
		$php = $mode == \Core\Mvc\Template::MODE_SMARTY ? '.phtml' : '.php';
		if(!$tpl){
			$action = substr($route['action'],0,-6);
			$action = strtolower($action);
			$tpl = $controller.'/'.$action.$php;
		}elseif(!strpos($tpl, '/')){
			$tpl = $controller.'/'.$tpl;
		}
		if(!strpos($tpl,$php)){
			$tpl .= $php;
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
	public function fetch($tpl=null,$mode = \Core\Mvc\Template::MODE_SMARTY){
		$template = new \Core\Mvc\Template($mode);
		$template->setTemplateDir(__DIR__.'/../Mvc/Template');
		foreach ($this->_assign as $assignname => $assignvalue){
			$template->assign($assignname, $assignvalue[0]);
		}
		$tpl = $this->parsetemplatefilename($tpl,$mode);
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
		$configs = \Core\Config::get('web/smarty');
		if(is_array($configs)){
			if(isset($configs['register_modifier'])){
				foreach ($configs['register_modifier'] as $funname => $fun){
					$template->registerPlugin('modifier',$funname, $fun);
				}
			}
			if(isset($configs['register_function'])){
				foreach ($configs['register_function'] as $funname => $fun){
					$template->registerPlugin('function',$funname, $fun);
				}
			}
		}
	}
	/**
	 * Ajax返回
	 * @author lishengyou
	 * 最后修改时间 2015年3月10日 下午4:20:38
	 *
	 * @param array $data
	 */
	public function returnAjax(array $data){
		//消息数量
		if($this->user){
			$message_model = new \Common\Model\Erp\Message();
			$where = array('to_user_id' => $this->user['user_id'], 'is_read' => 0, 'is_delete' => 0);
			$columns = array('total_message' => new \Zend\Db\Sql\Predicate\Expression('count(message_id)'));
			$total_unread = $message_model->getOne($where, $columns);
			$data['__msg__'] = array('total'=>$total_unread['total_message']);
			//未审核
			$route = \Core\App::getNowApp()->getContainer()->getRoute();
			if(!$this->user['company']['is_verify'] && strtolower($route['originalController']) != 'index') {
				$data['__status__'] = 301;
				$data['__url__'] = '/';
			}
		}
		$callback = \App\Web\Lib\Request::queryString('get.callback');
		if(stripos($callback,'jquery') === 0){
			echo $callback."(".json_encode($data).");";
		}else{
			echo json_encode($data);
		}
	}
	/**
	 * 验证模块权限
	 * @author lishengyou
	 * 最后修改时间 2015年6月17日 上午9:49:55
	 *
	 * @param unknown $permissions_auth
	 * @param string $module_name
	 * @return boolean|Ambigous <boolean, multitype:>
	 */
	protected function verifyModulePermissions($permissions_auth,$module_name=null){
		if($this->user['is_manager']){
			return true;
		}
		$module_name = $module_name ? $module_name : $this->_auth_module_name;
		if(!$module_name){
			return false;
		}
		$permissions = \Common\Helper\Permissions::Factory($module_name);
		return $permissions->VerifyModulePermissions($permissions_auth, $permissions::GROUP_AUTHENTICATOR, $this->user['group_id']);
	}
	/**
	 * 验证单条数据权限
	 * @author lishengyou
	 * 最后修改时间 2015年6月17日 上午9:49:55
	 *
	 * @param unknown $permissions_auth
	 * @param string $module_name
	 * @return boolean|Ambigous <boolean, multitype:>
	 */
	protected function verifyDataLinePermissions($permissions_auth,$authenticatee_id,$extended,$module_name='sys_housing_management'){
		if($this->user['is_manager'] && $module_name != 'sys_housing_management'){
			return true;
		}
		$module_name = $module_name ? $module_name : $this->_auth_module_name;
		if(!$module_name){
			return false;
		}
		$permissions = \Common\Helper\Permissions::Factory($module_name);
		return $permissions->VerifyDataLinePermissions($permissions_auth, $permissions::USER_AUTHENTICATOR, $this->user['user_id'], $authenticatee_id,$extended);
	}
}