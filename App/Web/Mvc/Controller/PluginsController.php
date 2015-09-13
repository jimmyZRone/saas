<?php
namespace App\Web\Mvc\Controller;
use App\Web\Lib\Request;

class PluginsController extends  \App\Web\Lib\Controller
{
	protected function indexAction()
	{
		$pluginsModel = new \App\Web\Mvc\Model\Plugins();
		$plugins_data = $pluginsModel->getAllPlugins();
		$this->assign("table", $plugins_data);
		$this->display();
	}
	/**
	 * 添加插件
	 * 修改时间2015年3月12日 11:09:13
	 * 
	 * @author yzx
	 */
	protected function addAction()
	{
		$pluginsModel = new \App\Web\Mvc\Model\Plugins();
		if (!\App\Web\Lib\Request::isPost())
		{
			$config_data = $pluginsModel->getPluginConfig();
			$layout = new \Core\Ui\Layout();
			$ui = $layout->loadByFile(APP_WEB_LAYOUT_DIR."Plugins/form.html",array("title"=>"添加插件","config"=>$config_data));
			\Core\App\Event::trigger(\App\Web\Lib\Listing::LOGIN_FORM_INIT,$ui,\Core\Event::EVENT_TRANSFER);
			$this->assign("form", $ui->outerHTML());
			$this->display("add");
		}else 
		{
			$data['model_name'] = Request::queryString('post.model_name',"","string");
			$data['creat_company'] = Request::queryString('post.creat_company',"system","string"); 
			$data['plugins_type'] = Request::queryString('post.plugins_type',"","string");
			$config = Request::queryString('post.config',"","string");
			$result = $pluginsModel->addPlugin($data,$config);
			if ($result)
			{
				echo "添加成功";
			}
		}
		
	}
	/**
	 * 审核插件
	 */
	protected function auditorAction()
	{
		$plugins_id = Request::queryString('get.id',0,"int");
		$pluginsModel = new \App\Web\Mvc\Model\Plugins();
		$result = $pluginsModel->auditorPlugins($plugins_id);
		if ($result)
		{
			echo "审核成功";
		}
	}
	/**
	 * 显示独立插件
	 */
	protected function showAction()
	{
		$pluginsname = Request::queryString('get.pluginsname',null,'string');
		$title = Request::queryString("get.title","","string");
		$layout = new \Core\Ui\Layout();
		$ui = $layout->loadByFile(APP_WEB_LAYOUT_DIR."Plugins/empty.html",array("title"=>$title,"plugins_name"=>$pluginsname));
		if (!empty($pluginsname))
		{
			\Core\App\Event::trigger($pluginsname,$ui,\Core\Event::EVENT_TRANSFER);
		}
		$this->assign("form", $ui->outerHTML());
		$this->display("empty");
	}
}