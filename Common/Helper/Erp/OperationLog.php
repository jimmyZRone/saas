<?php
namespace Common\Helper\Erp;
/**
 * 操作日志
 * @author lishengyou
 * 最后修改时间 2015年8月5日 下午1:51:56
 *
 */
class OperationLog{
	//用户登录
	const ACTION_USER_LOGIN = 'UL';
	//用户注册
	const ACTION_USER_REG = 'UR';
	/**
	 * 保存日志
	 * @author lishengyou
	 * 最后修改时间 2015年8月5日 下午1:57:02
	 *
	 * @param unknown $user_id
	 * @param unknown $action
	 * @param unknown $action_id
	 * @param unknown $log_content
	 * @return Ambigous <number, unknown, boolean>
	 */
	public static function save($user_id,$action,$action_id,$log_content){
		$model = new \Common\Model\Erp\OperationLog();
		$data = array(
			'user_id'=>$user_id,
			'action'=>$action,
			'action_id'=>$action_id,
			'log_content'=>$log_content,
			'create_time'=>time()
		);
		return $model->insert($data);
	}
}