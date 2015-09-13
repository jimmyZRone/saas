<?php
namespace App\Web\System;
use App\Web\Lib\Listing;
/**
 * MVC
 * @author lishengyou
 * 最后修改时间 2015年3月2日 下午1:31:33
 *
 */
class Plugins extends \Core\Program\System{
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
		\Core\App\Event::bind(Listing::ROUTE_COMPLETE, array($this,'initRoute'));
		\Core\App\Event::bind(Listing::SYS_INIT_COMPLETE,array($this,'triggerInitPlugins'));
	}
	protected function triggerInitPlugins(){
		\Core\App\Event::trigger(Listing::PLUGINS_INIT);
	}
	
	/**
	 * 初始化插件
	 * @author lishengyou
	 * 最后修改时间 2015年3月2日 下午1:54:53
	 *
	 */
	protected function initPlugins($namespace)
	{
		$plugins_program = $namespace;
		$plugins_program::init()->initRun();
	}
	/**
	 * 路由完成
	 * @author lishengyou
	 * 最后修改时间 2015年3月2日 下午2:04:37
	 *
	 */
	protected function initRoute($e){//当前方法平均耗时60+
// 		\Core\App\Event::trigger(Listing::PLUGINS_COMPLETE);//完成加载
// 		return null;
		$route = $e['e'];
		$controller = $route['controller'];
		$action = $route['action'];
		$controller = explode("\\",$controller);
		$controller = end($controller);
		$loading_route = $controller.":".$action;
		$config_data = $this->getPluginsConfig();
		$third_config = $this->getPluginsConfig("Third");
		if (!empty($third_config))
		{
			$config_data = array_merge($config_data,$third_config);
		}
		$analysis_plugins_config=$this->analysisPluginsConfiga($config_data);
		if(!empty($analysis_plugins_config)&&is_array($analysis_plugins_config)){
			foreach ($analysis_plugins_config as $akey => $aval)
			{
				if ($aval['loading_route'] == $loading_route)
				{
					$this->initPlugins($aval['namespace']);
				}
			}
		}
		\Core\App\Event::trigger(Listing::PLUGINS_COMPLETE);//完成加载
	}
	/**
	 * 解析插件配置
	 * 修改时间2015年3月11日 10:46:59
	 * 
	 * @author yzx
	 * @param array $config
	 * @return array
	 */
	private function  analysisPluginsConfiga($config)
	{
		$db_plugins_config = $this->getDbPluginsConfig();
		$result = array();
		if (!empty($config)&&!empty($db_plugins_config))
		{
			foreach ($config as $ckey => $cval)
			{
				if (in_array($cval['plugins_name'],$db_plugins_config))
				{
					$result[] = array(
										"loading_route"=>$this->analysisController($cval['loading']),
									  	"plugins_name"=>$cval['plugins_name'],
									  	"namespace"=>$cval["namespace"]
									);
				}else 
				{
					continue;
				}
			}
			return $result;
		}
		return $result;
	}
	/**
	 * 解析插件说对应的控制器
	 * 修改时间2015年3月11日 10:46:35
	 * 
	 * @author yzx
	 * @param string $str
	 * @return string
	 */
	private function analysisController($str)
	{
		if (!empty($str))
		{
			$sub_str = str_replace(array("[","]"), "", $str);
			$exp_str = explode(":", $sub_str);
			$sub_str = $exp_str[0]."Controller".":".$exp_str[1]."Action";
			$output_str = ucwords($sub_str);
		}
		return $output_str;
	}
	/**
	 * 获取数据库已经注册的插件
	 * 修改时间2015年3月11日 15:30:25
	 * 
	 * @author yzx
	 * @return array
	 */
	private function getDbPluginsConfig()
	{
		$pluginsModel = new \Common\Model\Erp\Plugins();
		$plugins_data = $pluginsModel->getData(array("creat_company"=>"system","is_auditor"=>1));
		$third_plugins_data = $pluginsModel->getThirdPlugins(0);
		if (!empty($third_plugins_data))
		{
			$plugins_data = array_merge($plugins_data,$third_plugins_data);
		}
		$db_plugins_config = array();
		if (!empty($plugins_data))
		{
			foreach ($plugins_data as $pkey=>$pval)
			{
				$db_plugins_config[]=$pval['model_name'];
			}
		}
		return $db_plugins_config;
	}
}