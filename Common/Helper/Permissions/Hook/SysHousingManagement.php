<?php
namespace Common\Helper\Permissions\Hook;
use Common\Model\Erp;
use Common\Helper\Permissions\Hook;
/**
 * 房源管理验证钩子
 * @author lishengyou
 * 最后修改时间 2015年5月13日 下午5:13:34
 *
 */
class SysHousingManagement extends Hook{
	/**
	 * 分散式
	 * @var int
	 */
	const DECENTRALIZED = 1;
	/**
	 * 集中式
	 * @var int
	 */
	const CENTRALIZED = 2;
	/**
	 * 集中式公寓
	 * @var unknown
	 */
	const CENTRALIZED_FLAT = 3;
	/**
	 * 分散式房源
	 * @var int
	 */
	const DECENTRALIZED_HOUSE = 4;
	/**
	 * 所有房源
	 * @var int
	 */
	const ALLHOUSE = 0;
// 	/**
// 	 * 验证是否对模块有权限
// 	 * @author lishengyou
// 	 * 最后修改时间 2015年5月13日 下午7:28:32
// 	 *
// 	 * @param string $permissions_auth 权限动作
// 	 * @param int $authenticator_type 验证者类型
// 	 * @param int|array $authenticator_id	验证者ID或ID集合
// 	 * @return bool
// 	 */
// 	public function VerifyModulePermissions($permissions_auth,$authenticator_type,$authenticator_id){
// 		if(!$this->_module){
// 			return false;
// 		}
// 		//把ID转换成用户组ID
// 		$model = new Model('user_group');
// 		$group = $model->getOne(array('user_id'=>$authenticator_id));
// 		if(!$group){
// 			return false;
// 		}
// 		return parent::VerifyModulePermissions($permissions_auth, $authenticator_type, $group['group_id']);
// 	}
	/**
	 * 验证是否对模块的数据行有权限
	 * @author lishengyou
	 * 最后修改时间 2015年5月13日 下午7:28:32
	 *
	 * @param string $permissions_auth 权限动作
	 * @param int $authenticator_type 验证者类型
	 * @param int $authenticator_id	验证者ID
	 * @param int|array $authenticatee_id 验证的数据ID或ID集合
	 * @param mixed $extended			扩展信息
	 * @return bool
	 */
	public function VerifyDataLinePermissions($permissions_auth,$authenticator_type,$authenticator_id,$authenticatee_id,$extended=null){
		if(!$extended){
			throw new \Exception('房源数据行权限必须要求传入扩展信息(1/分散,2/集中,4/分散房源)');
		}
		$userModel = new Erp('user');
		$userData = $userModel->getOne(array('user_id'=>$authenticator_id));
		if(!$userData){
			return false;
		}
		if($userData['is_manager']){//主账号
			$data = array();
			if($extended == self::DECENTRALIZED || $extended == self::DECENTRALIZED_HOUSE){//分散式
				$houseModel = new Erp('house');
				$sql = $houseModel->getSqlObject();
				$select = $sql->select(array('h'=>$houseModel->getTableName()));
				$select->columns(array('authenticatee_id'=>'house_id','company_id'));
				$select->where(array('h.company_id'=>$userData['company_id'],'h.house_id'=>$authenticatee_id));
				$data = $select->execute();
			}elseif($extended == self::CENTRALIZED){//集中式
				$flatModel = new Erp('flat');
				$sql = $flatModel->getSqlObject();
				$select = $sql->select(array('f'=>$flatModel->getTableName()));
				$select->columns(array('company_id'));
				$select->join(array('rf'=>'room_focus'), 'rf.flat_id=f.flat_id',array('authenticatee_id'=>'room_focus_id'));
				$select->where(array('f.company_id'=>$userData['company_id'],'rf.room_focus_id'=>$authenticatee_id));
				$data = $select->execute();
			}elseif($extended == self::CENTRALIZED_FLAT){//集中式公寓
				$flatModel = new Erp('flat');
				$sql = $flatModel->getSqlObject();
				$select = $sql->select(array('f'=>$flatModel->getTableName()));
				$select->columns(array('authenticatee_id'=>'flat_id','company_id'));
				$select->where(array('f.company_id'=>$userData['company_id'],'f.flat_id'=>$authenticatee_id));
				$data = $select->execute();
			}
			if(!is_array($authenticatee_id)){
				return $data ? true : false;
			}else{
				$authenticatee_id = array_fill_keys($authenticatee_id, false);
				foreach ($data as $permissions){
					if(isset($authenticatee_id[$permissions['authenticatee_id']])){
						$authenticatee_id[$permissions['authenticatee_id']] = true;
					}
				}
				return $authenticatee_id;
			}
		}else{
			//取模块级权限
			$model = new Erp('module_block_access');
			$block_access = $model->getOne(array('module_id'=>$this->_module['module_id'],'block_access_id'=>self::LINE_BLOCK_ACCESS));
			if(!$block_access){//没有任何信息,不具备数据行权限
				return false;
			}
			//判断是否有数据
			$model = new Erp('housing_distribution');
			$sql = $model->getSqlObject();
			$select = $sql->select($model->getTableName());
			$select->where(array('user_id'=>$authenticator_id));
			if($extended == self::DECENTRALIZED){//分散式
				$select->join(array('house'=>'house'), 'house.community_id=housing_distribution.source_id',array('authenticatee_id'=>'house_id'));
				$select->where(array('house.house_id'=>$authenticatee_id,'source'=>self::DECENTRALIZED));
			}elseif($extended == self::DECENTRALIZED_HOUSE){//分散式房源
				$select->join(array('house'=>'house'), 'house.house_id=housing_distribution.source_id',array('authenticatee_id'=>'house_id'));
				$select->where(array('house.house_id'=>$authenticatee_id,'source'=>self::DECENTRALIZED_HOUSE));
			}elseif($extended == self::CENTRALIZED){//集中式
				$select->join(array('room_focus'=>'room_focus'), 'room_focus.flat_id=housing_distribution.source_id',array('authenticatee_id'=>'flat_id'));
				$select->where(array('room_focus.room_focus_id'=>$authenticatee_id,'source'=>self::CENTRALIZED));
			}elseif($extended == self::CENTRALIZED_FLAT){//集中式公寓
				$select->where(array('source_id'=>$authenticatee_id,'source'=>self::CENTRALIZED));
			}
			$result = $select->execute();
			if(!is_array($authenticatee_id)){
				return $result ? true : false;
			}else{
				$authenticatee_id = array_fill_keys($authenticatee_id, false);
				foreach ($result as $permissions){
					if(isset($authenticatee_id[$permissions['authenticatee_id']])){
						$authenticatee_id[$permissions['authenticatee_id']] = true;
					}
				}
				return $authenticatee_id;
			}
		}
	}
	/**
	 * 返回权限的SQL语句
	 * @author lishengyou
	 * 最后修改时间 2015年5月13日 下午8:54:51
	 *
	 * @param int $authenticator_type	验证者类型
	 * @param int $authenticator_id		验证者编号
	 * @param mixed $extended			扩展信息
	 * @return boolean|string			生成的SQL
	 */
	public function VerifyDataCollectionsPermissions($authenticator_type,$authenticator_id,$extended=null){
		if(is_null($extended)){
			throw new \Exception('房源数据行权限必须要求传入扩展信息(0/所有,1/分散,2/集中/4分散房源)');
		}
		$model = new Erp('module_block_access');
		$block_access = $model->getOne(array('module_id'=>$this->_module['module_id'],'block_access_id'=>self::LINE_BLOCK_ACCESS));
		if(!$block_access){//没有任何信息，不支持数据行
			return false;
		}
		$model = new Erp('housing_distribution');
		$sql = $model->getSqlObject();
		$select = $sql->select($model->getTableName());
		$select->columns(array('authenticatee_id'=>'source_id','source'));
		$select->where(array('user_id'=>$authenticator_id));
		if($extended){
			$select->where(array('source'=>$extended));
		}
		return '('.$select->getSqlString().')';
	}
	/**
	 * 设置权限
	 * @author lishengyou
	 * 最后修改时间 2015年5月14日 下午1:33:32
	 *
	 * @param int $block_access_id	权限级
	 * @param string $permissions_auth	权限动作
	 * @param int $authenticator_type	权限者类型
	 * @param int $authenticator_id		权限者
	 * @param int $authenticatee_id		被验证者
	 * @param int $permissions_value	有无权限
	 * @param mixed $extended			扩展信息
	 */
	public function SetVerify($block_access_id,$permissions_auth,$authenticator_type,$authenticator_id,$authenticatee_id,$permissions_value=1,$extended=null){
		if($block_access_id == self::MODULE_BLOCK_ACCESS){
			if($authenticator_type == self::USER_AUTHENTICATOR){
				$model = new Erp('user_group');
				$group = $model->getOne(array('user_id'=>$authenticator_id));
				if(!$group){
					return false;
				}
				$authenticator_id = $group['group_id'];
			}
			return parent::SetVerify($block_access_id, $permissions_auth, $authenticator_type, $authenticator_id, $authenticatee_id,$permissions_value,$extended);
		}
		if(!$extended){
			throw new \Exception('房源数据行权限必须要求传入扩展信息(1/分散,2/集中,4/分散房源)');
		}
		$model = new Erp('module_block_access');
		$block_access = $model->getOne(array('module_id'=>$this->_module['module_id'],'block_access_id'=>$block_access_id));
		if(!$block_access){//模块不允许当前权限级别
			return false;
		}
		if($extended == self::DECENTRALIZED){
			$house = new Erp('house');
			$house = $house->getOne(array('house_id'=>$authenticatee_id));
			if(!$house){
				return false;
			}
			$authenticatee_id = $house['community_id'];
		}elseif($extended == self::CENTRALIZED){
			$house = new Erp('room_focus');
			$house = $house->getOne(array('room_focus_id'=>$authenticatee_id));
			if(!$house){
				return false;
			}
			$authenticatee_id = $house['flat_id'];
		}elseif($extended == self::DECENTRALIZED_HOUSE){
			$extended = self::DECENTRALIZED_HOUSE;
		}elseif($extended == self::CENTRALIZED_FLAT){
			$extended = self::CENTRALIZED;
		}else{
			$extended = 2;
		}
		$data = array(
			'user_id'=>$authenticator_id,
			'source'=>$extended,
			'source_id'=>$authenticatee_id
		);
		$model = new Erp('housing_distribution');
		$model->delete($data);
		return $model->insert($data);
	}
	
	/**
	 * 清除权限
	 * @author lishengyou
	 * 最后修改时间 2015年5月14日 下午1:39:47
	 *
	 * @param int $block_access_id	权限级
	 * @param string $permissions_auth	权限动作
	 * @param int $authenticator_type	权限者类型
	 * @param int $authenticator_id	权限者
	 * @param mixed $extended			扩展信息
	 * @return boolean
	 */
	public function ClearVerify($block_access_id,$permissions_auth,$authenticator_type,$authenticator_id,$extended=null){
		if($block_access_id == self::MODULE_BLOCK_ACCESS){
			if($authenticator_type == self::USER_AUTHENTICATOR){
				$model = new Erp('user_group');
				$group = $model->getOne(array('user_id'=>$authenticator_id));
				if(!$group){
					return false;
				}
				$authenticator_id = $group['group_id'];
			}
			return parent::ClearVerify($block_access_id, $permissions_auth, $authenticator_type,$authenticator_id,$extended);
		}
		if(!$extended){
			throw new \Exception('房源数据行权限必须要求传入扩展信息(1/分散,2/集中,4/分散房源)');
		}
		$model = new Erp('module_block_access');
		$block_access = $model->getOne(array('module_id'=>$this->_module['module_id'],'block_access_id'=>$block_access_id));
		if(!$block_access){//模块不允许当前权限级别
			return false;
		}
		if($extended == self::DECENTRALIZED){
			$house = new Erp('house');
			$house = $house->getOne(array('house_id'=>$authenticatee_id));
			if(!$house){
				return false;
			}
			$authenticatee_id = $house['community_id'];
		}elseif($extended == self::CENTRALIZED){
			$house = new Erp('room_focus');
			$house = $house->getOne(array('room_focus_id'=>$authenticatee_id));
			if(!$house){
				return false;
			}
			$authenticatee_id = $house['flat_id'];
		}else{
			$extended = 2;
		}
		$where = array(
			'user_id'=>$authenticator_id,
			'source'=>$extended,
			'source_id'=>$authenticatee_id
		);
		$model = new Erp('housing_distribution');
		return $model->delete($where);
	}
}