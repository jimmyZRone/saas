<?php
namespace App\Web\System\Auth;
/**
 * 权限处理
 * @author lishengyou
 * 最后修改时间 2015年3月19日 上午10:53:02
 *
 */
class Auth{
	/**
	 * 用户处理块编号
	 * @var unknown
	 */
	const AUTH_TREE_USER = 3;
	/**
	 * 用户组处理权限
	 * @var unknown
	 */
	const AUTH_TREE_GROUP = 2;
	/**
	 * 所有权限处理块编号
	 * @author lishengyou
	 * 最后修改时间 2015年3月19日 上午10:55:26
	 *
	 * @return multitype:string
	 */
	public static function AUTH_TREE_LEVEL(){
		return array(
			self::AUTH_TREE_GROUP
		);
	}
	public static function AUTH_TREE_HANDLE(){
		return array(
			self::AUTH_TREE_USER => 'Group'
		);
	}
	const MANIPULATE_INSERT = 'insert_auth';
	const MANIPULATE_DELETE = 'delete_auth';
	const MANIPULATE_UPDATE = 'update_auth';
	const MANIPULATE_SELECT = 'select_auth';
	/**
	 * 权限操作
	 * @author lishengyou
	 * 最后修改时间 2015年1月20日 下午3:38:08
	 *
	 * @param 数据模块 $model
	 * @param 权限模块 $modular
	 * @param 被赋予权限者 $give_id
	 * @param 权限操作类型 $manipulate
	 * @param 操作模型的表名称 $tableName
	 */
	public static function handle(\Core\Db\Sql\Select $model,$modular_id,$user_id,$manipulate,$tableName=null,$owner_id = 'owner_id'){
		$tableName = $tableName ? $tableName : $model->getTableName();
		$treeHandle = self::getAuthTree($modular_id, $user_id, $manipulate);
		if(!$treeHandle){
			$model->addWhere(array("`{$tableName}`.{$owner_id}"=>0));
			return false;
		}
		return $treeHandle->handle($model,$modular_id,$user_id,$manipulate,$tableName,$owner_id);
	}
	/**
	 * 判断用户是否用权限
	 * @author lishengyou
	 * 最后修改时间 2015年1月22日 上午11:10:06
	 *
	 * @param 模块编号 $modular_id
	 * @param 用户编号 $user_id
	 * @param 权限类型 $manipulate
	 * @return boolean
	 */
	public static function hasAuth($modular_id,$user_id,$manipulate){
		return !!self::getAuthTree($modular_id, $user_id, $manipulate);
	}
	/**
	 * 判断两个用户之间的权限
	 * @author lishengyou
	 * 最后修改时间 2015年3月19日 下午5:20:40
	 *
	 * @param unknown $modular_id
	 * @param unknown $from_user_id
	 * @param unknown $to_user_id
	 * @param unknown $manipulate
	 * @return unknown|boolean
	 */
	public static function hasUserAuth($modular_id,$from_user_id,$to_user_id,$manipulate){
		$handles = self::AUTH_TREE_HANDLE();
		foreach (self::AUTH_TREE_LEVEL() as $auth){
			$handle = $handles[$auth];
			$handle = __NAMESPACE__.'\Auth\\'.$handle;
			$handle = new $handle();
			if($handle->hasUserAuth($modular_id,$from_user_id,$to_user_id,$manipulate)){
				return $handle;
			}
		}
		return false;
	}
	/**
	 * 取得权限树
	 * @author lishengyou
	 * 最后修改时间 2015年1月22日 上午11:09:08
	 *
	 * @param 模块编号 $modular_id
	 * @param 用户编号 $user_id
	 * @param 权限类型 $manipulate
	 * @return Adapter|false
	 */
	public static function getAuthTree($modular_id,$user_id,$manipulate){
		$handles = self::AUTH_TREE_HANDLE();
		foreach (self::AUTH_TREE_LEVEL() as $auth){
			$handle = $handles[$auth];
			$handle = __NAMESPACE__.'\Auth\\'.$handle;
			$handle = new $handle();
			if($handle->hasAuth($modular_id,$user_id,$manipulate)){
				return $handle;
			}
		}
		return false;
	}
	/**
	 * 根据模块系统名称取模块ID
	 * @author lishengyou
	 * 最后修改时间 2015年3月19日 下午3:01:51
	 *
	 * @param unknown $modular_mark
	 */
	public static function getModularId($modular_mark){
		$model = new \Common\Model\Erp\Modular();
		$data = $model->getOne(array('mark'=>$modular_mark));
		return $data ? $data['modular_id'] : null;
	}
	/**
	 * 设置权限
	 * @author lishengyou
	 * 最后修改时间 2015年1月22日 下午4:48:27
	 *
	 * @param array $data
	 */
	public static function setAuth(array $data){
		$fields = 'modular_id,auth_tree_type,classify_type,auth_entity_id,auth_give_id';
		$fields = array_fill_keys(explode(',', $fields),false);
		if(count(array_intersect_key($data, $fields)) < count($fields)){
			return false;
		}
		$model = new \Common\Model\Erp\ModularAuth();
		if(!$model->getOne(array_intersect_key($data, $fields))){
			//新增
			return !!$model->insert($data);
		}else{
			//修改
			return !!$model->edit(array_intersect_key($data, $fields),$data);
		}
	}
}