<?php
namespace App;
/**
 * 回调 APP
 * @author lishengyou
 * 最后修改时间 2015年2月28日 下午2:08:09
 *
 */
class Callback extends \Core\App{
	/**
	 * 初始化(non-PHPdoc)
	 * @author lishengyou
	 * 最后修改时间 2015年2月28日 下午2:33:58
	 *
	 * @see App::__init__()
	 */
	public function __init__(){
		$mvc = \App\Callback\System\Mvc::init();
		$mvc->initRoute();
		$mvc->initController();
	}
}