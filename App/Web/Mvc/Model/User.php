<?php
namespace App\Web\Mvc\Model;
use Zend\Db\Sql\Where;
use Core\Db\Sql\Select;
class User extends Common
{
	/**
	 * 用户COOKIE编号
	 * @var unknown
	 */
	protected $_current_user_id = 'JZWS_APPID';
	
	public function checkPhoneExist($phone){
		$userExist = $this->getOne(array('username'=>$phone));
		return empty($userExist)?false:true;
	}
	public function check($phone,$passwd,$salt){
		$user = $this->getOne(array('username'=>$phone));
// 		print_r($user);die;
		if(!$user) return false;
		return $user['password'] === \App\Web\Helper\Encrypt::sha1($salt.$passwd);
	}
	/**
	 * 取得SESSION ID
	 * @author lishengyou
	 * 最后修改时间 2014年12月29日 下午5:06:21
	 *
	 */
	public function getSessionId(){
		$cookie = new \App\Web\Helper\Cookie();
		$session_id = $cookie->getCookie($this->_current_user_id);
		return $session_id ? $session_id : false;
	}
	/**
	 * 设置SESSION ID
	 * @author lishengyou
	 * 最后修改时间 2014年12月29日 下午5:11:11
	 *
	 * @param unknown $session_id
	 */
	public function setSessionId($session_id){
		$cookie = new \App\Web\Helper\Cookie();
		$cookie->setCookie($this->_current_user_id,$session_id,time()+86400*7,'/');
	}
	
	/**
	 * 取得当前用户
	 * @author lishengyou
	 * 最后修改时间 2014年12月29日 上午11:25:24
	 *
	 */
	public static function getCurrentUser(){
		static $data = null;
		if($data) return $data;
		$self = new self();
		$sessionId = $self->getSessionId();
		if(!$sessionId){
			return false;
		}
		$model = new ErpinterfaceSession();
		$data = $model->getOne(array('session_id'=>$sessionId));
		if($data){
			$data = unserialize($data['value']);
		}else{
			$data = false;
		}
		return $data;
	}
	
	/**
	 * 退出当前用户
	 * @author lishengyou
	 * 最后修改时间 2014年12月29日 上午11:25:46
	 *
	 */
	public function logoutCurrentUser(){
		$model = new ErpinterfaceSession();
		$sessionId = $this->getSessionId();
		if(!$sessionId){
			return false;
		}
		$model->delete(array('session_id'=>$sessionId));
	}
	
	/**
	 * 登录用户
	 * @author lishengyou
	 * 最后修改时间 2014年12月29日 上午11:26:27
	 *
	 * @param array $user
	 */
	public function loginUser(array &$user){
		if(!$user) return false;
		$model = new ErpinterfaceSession();
		$sessionId = $this->getSessionId();
// 		echo $sessionId;die;
		if(!$sessionId){
			session_start();
			$sessionId = session_id();
			$this->setSessionId($sessionId);
		}
		//删除当前用户其他的登录点
		$where = new \Zend\Db\Sql\Where();
		$where->equalTo('user_id', $user['user_id']);
		$where->equalTo('app_type', 'web');
		$where->notEqualTo('session_id', $sessionId);
		$model->delete($where);
		$time = time()+86400*7;
		if($model->getOne(array('session_id'=>$sessionId))){
			$data = array();
			$data['user_id'] = $user['user_id'];
			$data['value'] = serialize($user);
			$data['app_type'] = 'web';
			$data['deadline'] = $time;//7天过期
			return !!$model->edit(array('session_id'=>$sessionId),$data);
		}else{
			$data = array();
			$data['session_id'] = $sessionId;
			$data['user_id'] = $user['user_id'];
			$data['value'] = serialize($user);
			$data['app_type'] = 'web';
			$data['deadline'] = $time;//7天过期
			return !!$model->insert($data);
		}
	}
	/**
	 * 根据用户名获取用户
	 *  最后修改时间 2015-3-19
	 *  
	 * @author dengshuang
	 * @param unknown $phone
	 */
	public function getByPhone($phone){
		return $this->getOne(array('username'=>$phone));
	}
	/**
	 * 修改密码
	 *  最后修改时间 2015-3-19
	 *  
	 * @author dengshuang
	 * @param unknown $user
	 * @param unknown $passwd
	 */
	public function changePwd($user,$passwd){
		$data = array();
		$data['password'] = \App\Web\Helper\Encrypt::sha1($user['salt'].$passwd);
		return $this->edit(array('user_id'=>$user['user_id']), $data);
	}
	/**
	 * 获取职员列表
	 *  最后修改时间 2015-3-19
	 *  
	 * @author dengshuang
	 * @return unknown|multitype:
	 */
	public function getSatffList($searchKey,$page,$size){
		$like = $this->likeFactory($searchKey);
		$user = $this->getCurrentUser();
		$select = $this->_sql_object->select(array('u'=>$this->_table_name));
		$select->leftjoin(array('ue'=>'user_extend'),"u.user_id = ue.user_id",array('staffname'=>'name','contact','gender','birthday'));
		$select->leftjoin(array('ug'=>'user_group'),"u.user_id = ug.user_id");
		$select->leftjoin(array('g'=>'group'),"ug.group_id = g.group_id",array('groupname'=>'name'));
		$select->leftjoin(array('c'=>'company'),'c.company_id = u.company_id',array("company_name"));
		$where = new \Zend\Db\Sql\Where();
		$where->equalTo('u.company_id', $user['company_id']);
		$where->equalTo('u.is_manager', 0);
		$where->addPredicate($like,Where::OP_AND);
		$select->where($where);
		// 		echo($select->getSqlString());
		$result = Select::pageSelect($select, $page, $size);
//		echo str_replace('"', '', $select->getSqlString());die;
		// 		echo "<br/>";
// 		print_r($result);die;
		if($result){
			return $result;
		}else{
			return array();
		}
	}
	/**
	 * 添加搜索员工条件
	 * 修改时间2015年3月23日 14:33:53
	 * 
	 * @author yzx
	 * @param string $searchKey
	 * @return \Zend\Db\Sql\Where
	 */
	private function  likeFactory($searchKey)
	{
		$where_name = new \Zend\Db\Sql\Where();
		$where_name->like("ue.name", $searchKey);
		$where_contact = new \zend\db\sql\Where();
		$where_contact->like("c.company_name", $searchKey);
		$where = new \zend\db\sql\Where();
		return $where->addPredicates(array($where_name,$where_contact),Where::OP_OR);
	}
}