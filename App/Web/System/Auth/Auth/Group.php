<?php
namespace App\Web\System\Auth\Auth;
use Zend\Db\Sql\Where;
/**
 * 用户组级权限
 * @author lishengyou
 * 最后修改时间 2015年1月22日 上午10:11:59
 *
 */
class Group implements Adapter{
	const CLASSIFY_MODULAR = 1;
	const CLASSIFY_GROUP = 2;
	const CLASSIFY_USER = 3;
	const CLASSIFY_SELF = 4;
	const TREE_UID = \App\Web\System\Auth::AUTH_TREE_GROUP;
	/**
	 * 处理用户权限
	 * @author lishengyou
	 * 最后修改时间 2015年1月22日 上午11:12:06
	 *
	 * @param 需要操作的数据模型 $model
	 * @param 模块编号 $modular_id
	 * @param 用户编号 $user_id
	 * @param 权限类型 $manipulate
	 */
	public function handle(\Core\Db\Sql\Select $model,$modular_id,$user_id,$manipulate,$tableName,$owner_id){
		$auth_list = $this->getAllAuth($modular_id, $user_id, $manipulate);
		if(isset($auth_list[\Common\Model\Erp\ModularAuth::LEVEL_MODULAR])){
			return true;
		}
		$where = array();
		if(isset($auth_list[\App\Web\Model\AuthModular::LEVEL_GROUP])){
			$model->join(array('auth_ug'=>'user_group'), "auth_ug.user_id={$tableName}.{$owner_id}",'user_id');
			$where['auth_ug.group_id'] = $auth_list[\Common\Model\Erp\ModularAuth::LEVEL_GROUP];
		}
		if(isset($auth_list[\Common\Model\Erp\ModularAuth::LEVEL_USER])){
			$auth_list[\Common\Model\Erp\ModularAuth::LEVEL_USER] = array_unique($auth_list[\Common\Model\Erp\ModularAuth::LEVEL_USER]);
			$where["{$tableName}.{$owner_id}"] = $auth_list[\Common\Model\Erp\ModularAuth::LEVEL_USER];
		}
		if(!empty($where)){
			$model->addWhere($where);
		}else{
			$model->addWhere(array("{$tableName}.`{$owner_id}`"=>0));
		}
		return true;
	}
	/**
	 * 取得所有权限
	 * @author lishengyou
	 * 最后修改时间 2015年1月22日 上午11:18:35
	 *
	 * @param 模块编号 $modular_id
	 * @param 用户编号 $user_id
	 * @param 权限类型 $manipulate
	 */
	protected function getAllAuth($modular_id,$user_id,$manipulate){
		$model = new \Common\Model\Erp\ModularAuth();
		$sql = $model->getSqlObject();
		$select = $sql->select(array('ma'=>$model->getTableName()));
		$select->join(array('u'=>'user'), 'ma.auth_give_id = u.user_id');
		$select->addWhere(array('u.user_id'=>$user_id));
		$select->addWhere(array('ma.modular_id'=>$modular_id,'ma.'.$manipulate=>1));
		$auth_list = $select->execute();
		$_temp = array();
		foreach ($auth_list as $auth){
			if(!isset($_temp[$auth['classify_type']])){
				$_temp[$auth['classify_type']] = array();
			}
			$_temp[$auth['classify_type']][] = $auth['auth_entity_id'];
		}
		return $_temp;
	}
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
	public function hasAuth($modular_id,$user_id,$manipulate){
		$model = new \Common\Model\Erp\ModularAuth();
		$sql = $model->getSqlObject();
		$select = $sql->select(array('ma'=>$model->getTableName()));
		$select->join(array('u'=>'user'), 'ma.auth_give_id = u.user_id');
		$select->addWhere(array('u.user_id'=>$user_id));
		$select->addWhere(array('ma.modular_id'=>$modular_id,'ma.'.$manipulate=>1));
		$select->limit(1);
		$data = $select->execute();
		return !!$data;
	}
	/**
	 * 判断两个用户之前的权限(non-PHPdoc)
	 * @author lishengyou
	 * 最后修改时间 2015年3月19日 下午5:20:01
	 *
	 * @see \App\Web\Lib\Auth\Adapter::hasAuth()
	 */
	public function hasUserAuth($modular_id,$from_user_id,$to_user_id,$manipulate){
		//判断来源用户是否有模块权限
		$model = new \Common\Model\Erp\ModularAuth();
		$data = $model->getOne(array(
				'modular_id'=>$modular_id,
				'auth_entity_id'=>$modular_id,
				'auth_give_id'=>$from_user_id,
				'auth_tree_type'=>self::TREE_UID,
				'classify_type'=>\Common\Model\Erp\ModularAuth::LEVEL_MODULAR,
				$manipulate=>1
		));
		if($data){
			return true;//有模块处理权限
		}
		//查看当前用户对处理用户的用户分组是否有交集
		$sql = $model->getSqlObject();
		$select = $sql->select(array('ma'=>$model->getTableName()));
		$select->join(array('ug'=>'user_group'),'ug.group_id=ma.auth_entity_id');
		$select->where(array(
			'ma.modular_id'=>$modular_id,
			'ma.auth_give_id'=>$from_user_id,
			'ma.auth_tree_type'=>self::TREE_UID,
			'ma.classify_type'=>\Common\Model\Erp\ModularAuth::LEVEL_GROUP,
			$manipulate=>1,
			'ug.user_id'=>$to_user_id
		));
		$data = $select->execute();
		if($data){
			return true;//有用户组权限交集
		}
		//直接对两个用户权限比对
		$data = $model->getOne(array(
				'modular_id'=>$modular_id,
				'auth_entity_id'=>$to_user_id,
				'auth_give_id'=>$from_user_id,
				'auth_tree_type'=>self::TREE_UID,
				'classify_type'=>\Common\Model\Erp\ModularAuth::LEVEL_USER,
				$manipulate=>1
		));
		if($data){
			return true;//有直接的权限关系
		}
		return false;
	}
	/**
	 * 取得权限拥有者对模块下权限实体的权限
	 * @author lishengyou
	 * 最后修改时间 2015年1月22日 下午2:12:57
	 *
	 * @param 模块编号 $modular_id
	 * @param 权限实体 $entity_id
	 * @param 用户编号 $give_id
	 * @return boolean
	 */
	public function getGiveEntityAuth($modular_id,$entity_id,$give_id){
		$model = new \Common\Model\Erp\ModularAuth();
		return $model->getOne(array(
				'modular_id'=>$modular_id,
				'auth_entity_id'=>$entity_id,
				'auth_give_id'=>$give_id,
				'auth_tree_type'=>self::TREE_UID
		));
	}
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
	public function getGiveEntityClassifyAuth($modular_id,$entity_id,$give_id,$classify_id){
		$model = new \Common\Model\Erp\ModularAuth();
		return $model->getOne(array(
				'modular_id'=>$modular_id,
				'auth_entity_id'=>$entity_id,
				'auth_give_id'=>$give_id,
				'auth_tree_type'=>self::TREE_UID,
				'classify_type'=>$classify_id
		));
	}
}