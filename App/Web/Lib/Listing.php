<?php
namespace App\Web\Lib;
/**
 * 事件列表
 * @author lishengyou
 * 最后修改时间 2015年3月2日 下午1:49:34
 *
 */
class Listing extends \Core\App\Event\Listing{
	const SYS_INIT_COMPLETE = 'sys_init_complete';//系统初始化完成
	/**
	 * 插件初始化
	 * @var unknown
	 */
	const PLUGINS_INIT = 'plugins_init';
	/**
	 * 插件加载完成
	 * @var unknown
	 */
	const PLUGINS_COMPLETE = 'plugins_complete';
	/**
	 * 路由完成
	 * @var unknown
	 */
	const ROUTE_COMPLETE = 'route_complete';
	/**
	 * 任务队列初始化
	 * @var unknown
	 */
	const TASK_QUEUE_INIT = 'task_queue_init';
	
	const LOGIN_FORM_INIT = 'login_form_init';
	
	const REGISTER_FORM_INIT = 'register_form_init';
	
	//数据select对象生成
	const DB_SELECT_CREATED = 'db_select_created';
	
	//数据delete对象生成
	const DB_DELETE_CREATED = 'db_delete_created';
	
	const USERINFO_FORM_INIT = 'userinfo_form_init';
	
	const REQUEST_END = 'request_end';//请求结束
}