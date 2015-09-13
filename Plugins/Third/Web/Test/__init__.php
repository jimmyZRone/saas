<?php
namespace Plugins\Third\Web;
/**
 * MVC
 * @author lishengyou
 * 最后修改时间 2015年3月2日 下午1:31:33
 *
 */
class Test extends \Core\Program\System{
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
		\Core\App\Event::bind(\App\Web\Lib\Listing::LOGIN_FORM_INIT, array($this,'initLoginForm'));
		\Core\App\Event::bind(\App\Web\Lib\Listing::LOGIN_FORM_INIT, array($this,'initKeepPassword'));
	}
	protected function initLoginForm($e){
		$ui = $e['e'];
		$layout = new \Core\Ui\Layout();
		$nodes = $ui->findAttribute('class','lines');
		$nodes = $nodes[0];
		$code = mt_rand(1000, 9999);
		$nodes->insertAfter('passwd_line',$layout->loadByFile(__DIR__.'/Layout/login/form.html',array('code'=>$code)));
	}
	protected function initKeepPassword($e)
	{
		$ui = $e['e'];
		$layout = new \Core\Ui\Layout();
		$nodes = $ui->findAttribute('class','lines');
		$nodes = $nodes[0];
		$nodes->insertAfter('code_line',$layout->loadByFile(__DIR__.'/Layout/login/keep.html'));
	}
}