<?php
namespace App\Web\System\Auth\Auth;
interface Adapter{
	/**
	 * 处理用户权限
	 * @author lishengyou
	 * 最后修改时间 2015年1月22日 上午11:12:06
	 *
	 * @param 需要操作的数据模型 $model
	 * @param 模块编号 $modular_id
	 * @param 用户编号 $user_id
	 * @param 权限类型 $manipulate
	 * @param 操作模型的表名称 $tableName
	 * @return boolean
	 */
	public function handle(\Core\Db\Sql\Select $model,$modular_id,$user_id,$manipulate,$tableName,$owner_id);
	/**
	 * 判断一个用户在当前模块下是否在权限
	 * @author lishengyou
	 * 最后修改时间 2015年1月22日 上午10:57:32
	 *
	 * @param 模块编号 $modular_id
	 * @param 用户编号 $user_id
	 * @param 权限类型 $manipulate
	 * @return boolean
	 */
	public function hasAuth($modular_id,$user_id,$manipulate);
	/**
	 * 判断两个用户之前的权限
	 * @author lishengyou
	 * 最后修改时间 2015年3月19日 下午5:20:29
	 *
	 * @param unknown $modular_id
	 * @param unknown $from_user_id
	 * @param unknown $to_user_id
	 * @param unknown $manipulate
	 */
	public function hasUserAuth($modular_id,$from_user_id,$to_user_id,$manipulate);
	/**
	 * 取得权限拥有者对模块下权限实体的权限
	 * @author lishengyou
	 * 最后修改时间 2015年1月22日 下午2:12:57
	 *
	 * @param 模块编号 $modular_id
	 * @param 权限实体 $entity_id
	 * @param 用户编号 $give_id
	 * @return row
	 */
	public function getGiveEntityAuth($modular_id,$entity_id,$give_id);
	/**
	 * 取得权限拥有者对模块下权限实体的权限
	 * @author lishengyou
	 * 最后修改时间 2015年1月22日 下午2:12:57
	 *
	 * @param 模块编号 $modular_id
	 * @param 权限实体 $entity_id
	 * @param 用户编号 $give_id
	 * @param 分级权限编号 $classify_id
	 * @return row
	 */
	public function getGiveEntityClassifyAuth($modular_id,$entity_id,$give_id,$classify_id);
}