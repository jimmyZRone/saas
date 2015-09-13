<?php
namespace App\Web\Lib\Ui;
/**
 * 布局管理
 * @author lishengyou
 * 最后修改时间 2015年3月3日 下午3:12:21
 *
 */
class Layout extends \Core\Ui\Layout{
	/**
	 * 创建模版
	 * @author lishengyou
	 * 最后修改时间 2015年3月18日 下午3:03:21
	 *
	 * @param array $data
	 * @return \Core\Mvc\Template
	 */
	protected function newtemplate(array $data = array()){
		$template = parent::newtemplate($data);
		$this->parstemplateconfig($template);
		return $template;
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
}