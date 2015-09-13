<?php
namespace App\Web\Mvc\Controller;
class ErrorController extends \Core\Mvc\Controller{
	/**
	 * 错误的控制器
	 * @author lishengyou
	 * 最后修改时间 2015年2月10日 上午11:33:51
	 *
	 */
	protected function controllerAction(){
		echo 'Controller Is Not Found';
	}
	/**
	 * 错误的动作
	 * @author lishengyou
	 * 最后修改时间 2015年2月10日 上午11:33:58
	 *
	 */
	protected function actionAction(){
		echo 'Action Is Not Found';
	}
	/**
	 * 错误的继承
	 * @author lishengyou
	 * 最后修改时间 2015年2月10日 上午11:34:04
	 *
	 */
	protected function nosubclassAction(){
		echo __FUNCTION__;
	}
}