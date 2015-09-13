<?php
namespace Plugins\System\Web;
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
		\Core\App\Event::bind(\App\Web\Lib\Listing::LOGIN_FORM_INIT, array($this,'initaddForm'));
	}
	protected function initaddForm($e){
		$ui = $e['e'];
		$layout = new \Core\Ui\Layout();
		$nodes = $ui->findAttribute('class','lines');
		$nodes = $nodes[0];
		$nodes->insertAfter('config',$layout->loadByFile(__DIR__.'/Layout/add/form.html'));
	}
}