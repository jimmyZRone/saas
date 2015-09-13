<?php
namespace App\Web\System;
/**
 * 调试
 * @author lishengyou
 * 最后修改时间 2015年3月2日 下午1:31:33
 *
 */
class Debug extends \Core\Program\System{
	protected static function __init__($args){
		return new self();
	}
	protected $_start_time = null;
	/**
	 * 初始化运行
	 * @author lishengyou
	 * 最后修改时间 2015年2月26日 上午11:13:21
	 *
	 */
	protected function initRun(){
		set_error_handler(function ($errno, $errstr, $errfile, $errline){
			//logError(__CLASS__.':'.__LINE__."Error:{$errstr}|{$errfile}:{$errline}");
		});
		set_exception_handler(function($exception){
			//logError(__CLASS__.':'.__LINE__.'Exc:'.$ex->getMessage());
		});
	}
}