<?php
namespace Common\Helper;
/**
 * 权限处理
 * @author lishengyou
 * 最后修改时间 2015年5月13日 下午7:25:34
 *
 */
class Permissions{
	protected $_module = null;
	/**
	 * 系统模块
	 * @var int
	 */
	const SYS_MODULE = 1;
	/**
	 * 用户模块
	 * @var int
	 */
	const USER_MODULE = 2;
	/**
	 * 模块级
	 * @var int
	 */
	const MODULE_BLOCK_ACCESS = 1;
	/**
	 * 数据行级
	 * @var int
	 */
	const LINE_BLOCK_ACCESS = 2;
	/**
	 * 用户类型
	 * @var int
	 */
	const USER_AUTHENTICATOR = 1;
	/**
	 * 用户组类型
	 * @var int
	 */
	const GROUP_AUTHENTICATOR = 2;
	/**
	 * 新增动作
	 * @var string
	 */
	const INSERT_AUTH_ACTION = 'insert';
	/**
	 * 删除动作
	 * @var string
	 */
	const DELETE_AUTH_ACTION = 'delete';
	/**
	 * 更新动作
	 * @var string
	 */
	const UPDATE_AUTH_ACTION = 'update';
	/**
	 * 查看动作
	 * @var string
	 */
	const SELECT_AUTH_ACTION = 'select';
	/**
	 * 实例化
	 * @author lishengyou
	 * 最后修改时间 2015年5月13日 下午7:53:23
	 *
	 * @param unknown $module
	 */
	public function __construct($module){
		$this->_module = $module;
	}
	/**
	 * 实例化权限验证
	 * @author lishengyou
	 * 最后修改时间 2015年5月13日 下午8:02:01
	 *
	 * @param unknown $module
	 * @return boolean|unknown|\Common\Helper\Permissions
	 */
	final public static function Factory($module){
		$model = new \Common\Model\Erp('functional_module');
		$module = $model->getOne(array('module_identification'=>$module));
		if(!$module){
			return false;
		}
		$identification = self::ParseIdentificationUpper($module['module_identification']);
		$class_name = '';
		if($module['module_type'] == self::SYS_MODULE){
			$class_name = '\Common\Helper\Permissions\Hook\\'.$identification;
		}else{
			return false;
		}
		if($class_name && \Core\Autoload::isExists($class_name)){
			return new $class_name($module);
		}
		return new self($module);
	}
	/**
	 * 实例化权限验证
	 * @author lishengyou
	 * 最后修改时间 2015年5月13日 下午8:02:01
	 *
	 * @param unknown $module
	 * @return boolean|unknown|\Common\Helper\Permissions
	 */
	final public static function FactoryById($module_id){
		$model = new \Common\Model\Erp('functional_module');
		$module = $model->getOne(array('module_id'=>$module_id));
		if(!$module){
			return false;
		}
		return self::Factory($module['module_identification']);
	}
	/**
	 * 转换标识
	 * @author lishengyou
	 * 最后修改时间 2015年5月13日 下午7:56:45
	 *
	 * @param string $identification
	 */
	final protected static function ParseIdentificationUpper($identification){
		$identification = str_split($identification);
		$temp = '';
		$isupper = false;
		foreach ($identification as $char){
			if($char == '_'){
				$isupper = true;
				continue;
			}
			if($isupper){
				$char = strtoupper($char);
				$isupper = false;
			}
			$temp .= $char;
		}
		$temp = ucwords($temp);
		return $temp;
	}
	
	/**
	 * 验证是否对模块有权限
	 * @author lishengyou
	 * 最后修改时间 2015年5月13日 下午7:28:32
	 *
	 * @param string $permissions_auth 权限动作
	 * @param int $authenticator_type 验证者类型
	 * @param int|array $authenticator_id	验证者ID或ID集合
	 * @return bool
	 */
	public function VerifyModulePermissions($permissions_auth,$authenticator_type,$authenticator_id){
		if(!$this->_module){
			return false;
		}
		
		//取模块级权限
		$model = new \Common\Model\Erp('module_block_access');
		$block_access = $model->getOne(array('module_id'=>$this->_module['module_id'],'block_access_id'=>self::MODULE_BLOCK_ACCESS));
		if(!$block_access){//没有任何信息
			return false;
		}
		//判断是否有数据
		$model = new \Common\Model\Erp('module_permissions');
		if(!is_array($authenticator_id)){//验证单条
			//如果有添加，删除，修改的权限就有查看的权限
			if(!is_array($permissions_auth)){
				$permissions_auth = array($permissions_auth);
			}
			if(in_array(self::SELECT_AUTH_ACTION, $permissions_auth)){
				$permissions_auth = array_merge($permissions_auth,array(self::INSERT_AUTH_ACTION,self::DELETE_AUTH_ACTION,self::UPDATE_AUTH_ACTION));
			}
			$permissions_auth = array_unique($permissions_auth);
			if(count($permissions_auth) == 1){
				$permissions_auth = end($permissions_auth);
			}
			
			$module_permissions = $model->getData(array(
					'module_block_access_id'=>$block_access['module_block_access_id'],
					'permissions_auth'=>$permissions_auth,
					'authenticator_type'=>$authenticator_type,
					'authenticator_id'=>$authenticator_id,
					'authenticatee_id'=>$this->_module['module_id']
			),array(),1,0,'permissions_value DESC');
			$module_permissions = $module_permissions ? array_shift($module_permissions) : array();
			return $module_permissions ? !!$module_permissions['permissions_value'] : $block_access['block_ validate_type'] == 2;
		}else{//验证集合
			$module_permissions = $model->getData(array(
					'module_block_access_id'=>$block_access['module_block_access_id'],
					'permissions_auth'=>$permissions_auth,
					'authenticator_type'=>$authenticator_type,
					'authenticator_id'=>$authenticator_id,
					'authenticatee_id'=>$this->_module['module_id']
			));
			if(!$module_permissions){
				return $block_access['block_validate_type'] == 2;
			}
			$authenticator_id = array_fill_keys($authenticator_id, $block_access['block_ validate_type'] == 2);
			foreach ($module_permissions as $permissions){
				if(isset($authenticator_id[$permissions['authenticator_id']])){
					$authenticator_id[$permissions['authenticator_id']] = !!$permissions['permissions_value'];
				}
			}
			return $authenticator_id;
		}
	}
	
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
		//取模块级权限
		$model = new \Common\Model\Erp('module_block_access');
		$block_access = $model->getOne(array('module_id'=>$this->_module['module_id'],'block_access_id'=>self::LINE_BLOCK_ACCESS));
		if(!$block_access){//没有任何信息
			return false;
		}
		//判断是否有数据
		$model = new \Common\Model\Erp('module_permissions');
		if(!is_array($authenticatee_id)){//验证单条
			$module_permissions = $model->getOne(array(
					'module_block_access_id'=>$block_access['module_block_access_id'],
					'permissions_auth'=>$permissions_auth,
					'authenticator_type'=>$authenticator_type,
					'authenticator_id'=>$authenticator_id,
					'authenticatee_id'=>$authenticatee_id
			));
			return $module_permissions ? !!$module_permissions['permissions_value'] : $block_access['block_ validate_type'] == 2;
		}else{//验证集合
			$module_permissions = $model->getData(array(
					'module_block_access_id'=>$block_access['module_block_access_id'],
					'permissions_auth'=>$permissions_auth,
					'authenticator_type'=>$authenticator_type,
					'authenticator_id'=>$authenticator_id,
					'authenticatee_id'=>$authenticatee_id
			));
			if(!$module_permissions){
				return $block_access['block_validate_type'] == 2;
			}
			$authenticatee_id = array_fill_keys($authenticatee_id, $block_access['block_ validate_type'] == 2);
			foreach ($module_permissions as $permissions){
				if(isset($authenticatee_id[$permissions['authenticatee_id']])){
					$authenticatee_id[$permissions['authenticatee_id']] = !!$module_permissions['permissions_value'];
				}
			}
			return $authenticatee_id;
		}
	}
	
	/**
	 * 返回数据行集合权限的SQL语句
	 * @author lishengyou
	 * 最后修改时间 2015年5月13日 下午8:54:51
	 *
	 * @param int $authenticator_type	验证者类型
	 * @param int $authenticator_id		验证者编号
	 * @param mixed $extended			扩展信息
	 * @return boolean|string			生成的SQL
	 */
	public function VerifyDataCollectionsPermissions($authenticator_type,$authenticator_id,$extended=null){
		$model = new \Common\Model\Erp('module_block_access');
		$block_access = $model->getOne(array('module_id'=>$this->_module['module_id'],'block_access_id'=>self::LINE_BLOCK_ACCESS));
		if(!$block_access){//没有任何信息
			return false;
		}
		$model = new \Common\Model\Erp('module_permissions');
		$sql = $model->getSqlObject();
		$select = $sql->select($model->getTableName());
		$select->columns(array('authenticatee_id'));
		$select->where(array(
				'module_block_access_id'=>$block_access['module_block_access_id'],
				'permissions_auth'=>self::SELECT_AUTH_ACTION,
				'authenticator_type'=>$authenticator_type,
				'authenticator_id'=>$authenticator_id
		));
		return '('.$select->getSqlString().')';
	}
	/**
	 * 处理数据行集合权限查询
	 * @author lishengyou
	 * 最后修改时间 2015年5月13日 下午9:02:20
	 *
	 * @param \Zend\Db\Sql\Select $select	原查询对象
	 * @param string $joinon			连接信息
	 * @param int $authenticator_type	验证者类型
	 * @param int $authenticator_id		验证者编号
	 * @param mixed $extended			扩展信息
	 * @return boolean
	 */
	public function VerifyDataCollectionsPermissionsModel(\Zend\Db\Sql\Select $select,$joinon,$authenticator_type,$authenticator_id,$extended=null){
		$sqlstring = $this->VerifyDataCollectionsPermissions($authenticator_type, $authenticator_id,$extended);
		if(!$sqlstring){
			$select->where(array(new \Zend\Db\Sql\Predicate\Expression('1=0')));
			return false;
		}
		$tablename = uniqid();
		if(!is_string($joinon) && $joinon instanceof \Closure){
			$joinon = $joinon($tablename);
		}else{
			$joinon = str_replace('__TABLE__', $tablename, $joinon);
		}
		if(is_string($joinon)){
			$joinon = explode(':', $joinon);
		}
		$joinon[1] = isset($joinon[1]) ? $joinon[1] : $select::JOIN_INNER;
		$joinon[2] = isset($joinon[2]) ? $joinon[2] : 'authenticatee_id';
		$select->join(array($tablename=>new \Zend\Db\Sql\Predicate\Expression($sqlstring)),$joinon[0],$joinon[2],$joinon[1]);
		return true;
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
		$model = new \Common\Model\Erp('module_block_access');
		$block_access = $model->getOne(array('module_id'=>$this->_module['module_id'],'block_access_id'=>$block_access_id));
		if(!$block_access){//模块不允许当前权限级别
			return false;
		}
		$data = array(
				'module_block_access_id'=>$block_access['module_block_access_id'],
				'block_access_id'=>$block_access_id,
				'permissions_auth'=>$permissions_auth,
				'authenticator_type'=>$authenticator_type,
				'authenticator_id'=>$authenticator_id,
				'authenticatee_id'=>$authenticatee_id
		);
		$model = new \Common\Model\Erp('module_permissions');
		$model->delete($data);
		$data['permissions_value'] = $permissions_value;
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
		$model = new \Common\Model\Erp('module_block_access');
		$block_access = $model->getOne(array('module_id'=>$this->_module['module_id'],'block_access_id'=>$block_access_id));
		if(!$block_access){//模块不允许当前权限级别
			return false;
		}
		$where = array(
			'module_block_access_id'=>$block_access['module_block_access_id'],
			'permissions_auth'=>$permissions_auth,
			'authenticator_type'=>$authenticator_type,
			'authenticator_id'=>$authenticator_id
		);
		$model = new \Common\Model\Erp('module_permissions');
		return $model->delete($where);
	}
	/**
	 * 清空所有权限
	 * @author lishengyou
	 * 最后修改时间 2015年5月14日 下午4:22:35
	 *
	 * @param int $authenticator_type	权限者类型
	 * @param int $authenticator_id	权限者
	 * @param mixed $extended			扩展信息
	 * @return boolean
	 */
	public static function ClearAllVierify($authenticator_type,$authenticator_id,$extended=null){
		$where = array(
			'authenticator_type'=>$authenticator_type,
			'authenticator_id'=>$authenticator_id
		);
		$model = new \Common\Model\Erp('module_permissions');
		return $model->delete($where);
	}
}