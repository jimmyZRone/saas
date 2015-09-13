<?php
namespace App;
//定义常量
define('APP_WEB_DIR', __DIR__.'/');
define('APP_WEB_TPL_DIR', APP_WEB_DIR.'/Mvc/Template/');
define('APP_WEB_LAYOUT_DIR', APP_WEB_DIR.'/Mvc/Layout/');
define('APP_WEB_PLUGINS_DIR', APP_WEB_DIR.'/Plugins/');
define('APP_WEB_SYSTEM_DIR', APP_WEB_DIR.'/System/');
define('APP_WEB_URL', str_replace('\\','/',dirname($_SERVER["SCRIPT_NAME"]).'/'));
define('APP_WEB_STATIC_URL',rtrim(APP_WEB_URL,'/').'/web/');

/**
 * WEB APP
 * @author lishengyou
 * 最后修改时间 2015年2月28日 下午2:08:09
 *
 */
class Web extends \Core\App{
	public function __construct(){
		parent::__construct();
		define('APP_WEB_VERSION',\Core\Config::get('web/app:info.version') ? \Core\Config::get('web/app:info.version') : '2.0');
	}
	/**
	 * 初始化(non-PHPdoc)
	 * @author lishengyou
	 * 最后修改时间 2015年2月28日 下午2:33:58
	 *
	 * @see App::__init__()
	 */
	public function __init__(){
		//判断重复提交
		if(\Common\Helper\Http\Request::isAjax()){
			$access_record = \Core\Session::read('access_record');
			$access_record = $access_record ? $access_record : array();
			$uri = \Common\Helper\Encrypt::md5_16($_SERVER['REQUEST_URI']);
			$time = explode(' ', microtime());
			$time = $time[1].substr($time[0],1);
			$timeout = 0.8;
			if(\Common\Helper\Http\Request::isPost()){//POST提交
				$timeout = 1.5;
				//重新加密URI
				if(!empty($_POST)){
					$uri = $_SERVER['REQUEST_URI'].'|DATA:'.file_get_contents("php://input");
					$uri = \Common\Helper\Encrypt::md5_16($uri);
				}
			}
			/* if($access_record && isset($access_record[$uri]) && $time - $access_record[$uri] < $timeout){
				\Core\Session::save('access_record',$access_record);
				return $this->returnAjax(array('__status__'=>500.131,'__message__'=>'操作过于频繁'));
			} */
			//删除过期的
			$access_record = array_filter($access_record,function ($val) use($time,$timeout){
				return $time - $val < $timeout;
			});
			$access_record[$uri] = $time;//保存当前
			\Core\Session::save('access_record',$access_record);
		}
		
		//申请相应的事件
		$event_apply = \Core\Config::get('web/event_apply');
		foreach ($event_apply as $event => $scope){
			\Core\App\Event::apply($event,$scope);
		}
		//初始化相应的程序
		$init = \Core\Config::get('web/app:init');
		$queue = new \Core\Program\Queue();
		foreach ($init as $program){
			$program = __CLASS__.'\\'.$program;
			if(\Core\Autoload::isExists($program)){
				try{
					$program = $program::init();
					$queue->push($program);
				}catch (\Exception $ex){
					logError(__CLASS__.':'.__LINE__.'Exc:'.$ex->getMessage());
				}
			}
		}
		$runtime = new \Core\App\Runtime($queue);
		\Core\App::getNowApp()->getContainer()->setRuntime($runtime);
		$runtime->start();
		\Core\App\Event::trigger(\App\Web\Lib\Listing::SYS_INIT_COMPLETE);
	}
	/**
	 * Ajax返回
	 * @author lishengyou
	 * 最后修改时间 2015年3月10日 下午4:20:38
	 *
	 * @param array $data
	 */
	public function returnAjax(array $data){
		$callback = \App\Web\Lib\Request::queryString('get.callback');
		if(stripos($callback,'jquery') === 0){
			echo $callback."(".json_encode($data).");";
		}else{
			echo json_encode($data);
		}
	}
}