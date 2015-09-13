<?php
namespace Common\Helper\Erp;
use Common\Model\Erp\UserExtend;
use Zend\Db\Sql\Where;
use Core\Db\Sql\Select;
use Common\Helper\Cookie;
/**
 * 用户助手
 * @author lishengyou
 * 最后修改时间 2015年2月27日 上午10:31:56
 *
 */
class User extends \Core\Object{
	protected static $_current_user_id = 'JZWS_APPID';
	/**
	 * 当前用户是否登录
	 * @author lishengyou
	 * 最后修改时间 2015年2月27日 上午10:32:22
	 *
	 * @return boolean
	 */
	public static function isLogin(){
		$user = static::getCurrentUser();
		return !!$user;
	}
	/**
	 * 取得SESSION ID
	 * @author lishengyou
	 * 最后修改时间 2014年12月29日 下午5:06:21
	 *
	 */
	public static function getSessionId(){
		$cookie = new \Common\Helper\Cookie();
		$session_id = $cookie->getCookie(static::$_current_user_id);
		if($session_id){
			$session_id = \Common\Helper\Encrypt::rsaDecodeing($session_id);
			$session_id = $session_id ? strrev($session_id) : false;
		}
		return $session_id ? $session_id : false;
	}
	/**
	 * 设置SESSION ID
	 * @author lishengyou
	 * 最后修改时间 2014年12月29日 下午5:11:11
	 *
	 * @param unknown $session_id
	 */
	public static function setSessionId($session_id){
		$cookie = new \Common\Helper\Cookie();
		$session_id = strrev($session_id);
		$session_id = \Common\Helper\Encrypt::rsaEncodeing($session_id);
		$cookie->setCookie(static::$_current_user_id,$session_id,time()+86400*7,'/');
	}
	/**
	 * 取得当前用户
	 * @author lishengyou
	 * 最后修改时间 2014年12月29日 上午11:25:24
	 *
	 */
	public static function getCurrentUser(){
		$cookie = new Cookie();
		$session_city = $cookie->getCookie("city_session");
		static $data = null;
		if($data) return $data;
		$sessionId = static::getSessionId();
		if(!$sessionId){
			return false;
		}
		$model = new \Common\Model\Erp\ErpinterfaceSession();
		$GetCityByIp = new GetCityByIp();
		$userExtendModel = new UserExtend();
		$data = $model->getOne(array('session_id'=>$sessionId));
		if($data){
			$data = unserialize($data['value']);
			$model = new \Common\Model\Erp\User();
			$data = $model->getOne(array('user_id'=>$data['user_id']),array());
			$manger_data = $model->getOne(array("company_id"=>$data['company_id'],"is_manager"=>1));
			if(!$data){return false;}
			$groupModel = new \Common\Model\Erp\UserGroup();
			$group = $groupModel->getOne(array('user_id'=>$data['user_id']));
			$data['group_id'] = $group ? $group['group_id'] : 0;
			$exinfo = $userExtendModel->getOne(array('user_id'=>$data['user_id']),array());
			$exinfo = $exinfo ? $exinfo : array();
			$data = array_merge($data,$exinfo);
			$companyModel = new \Common\Model\Erp\Company();
			$data['company'] = $companyModel->getOne(array('company_id'=>$data['company_id']));
			$data['company']['isCentralized'] = $data['company']['pattern'] == '10' || $data['company']['pattern'] == '11';
			$data['company']['isDispersive'] = $data['company']['pattern'] == '01' || $data['company']['pattern'] == '11';
			$data['company']['username'] = $manger_data['username'];
			$data['exinfo'] = $exinfo;
			if (intval($data['city_id'])<=0)
			{
				$data['city_id'] = 118;
			}
			$data['exinfo']['city_id'] = $data['city_id'];
			if ($session_city>0){
				$data['city_id'] = $session_city;
			}else {
				$city_id = $GetCityByIp->GetIpLookup();
				$data['city_id'] = $city_id;
				self::setCitySesion($city_id);
			}
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
	public static function logoutCurrentUser($app_type='default'){
		$model = new \Common\Model\Erp\ErpinterfaceSession();
		$sessionId = static::getSessionId();

		if(!$sessionId){
			return false;
		}
		$model->delete(array('session_id'=>$sessionId,'app_type'=>$app_type));
	}
	/**
	 * 验证用户
	 * @author lishengyou
	 * 最后修改时间 2015年3月25日 下午5:33:06
	 *
	 * @param unknown $phone
	 * @param string $passwd
	 * @return boolean
	 */
	public static function check($phone,$passwd=false,$info = false){
		$userModel = new \Common\Model\Erp\User();
		$user = null;
		if(strpos($phone, '@')){//管家登录
			$phone = explode('@', $phone);
			$manage = $userModel->getByPhone($phone[1]);
			if(!$manage) return false;
			$user = $userModel->getKeeperByName($phone[0], $manage['company_id']);
		}else{//主用户
			$user = $userModel->getByPhone($phone);
		}
		if(!$user){
			return false;
		}
		if($passwd === false){
			return $info ? $user : true;
		}
		$result = false;
		//新旧管家密码兼容
		if($user['is_manager'] == $userModel::NOT_MANAGER && $user['salt'] == ''){
			$result = $user['password'] === \Common\Helper\Encrypt::sha1($passwd);
			//保存新密码
			self::editUser($user['user_id'], array('password'=>$passwd));
		}else{
			$result = $user['password'] === \Common\Helper\Encrypt::sha1($user['salt'].$passwd);
		}
		return $result ? ($info ? $user : true) : false;
	}
	/**
	 * 登录用户
	 * @author lishengyou
	 * 最后修改时间 2014年12月29日 上午11:26:27
	 *
	 * @param array $user
	 */
	public static function loginUser(array &$user,$app_type='default'){
		if(!$user) return false;
		$model = new \Common\Model\Erp\ErpinterfaceSession();
		$sessionId = static::getSessionId();
		if(!$sessionId){
			session_start();
			$sessionId = session_id();
			static::setSessionId($sessionId);
		}
		//删除当前用户其他的登录点
		$where = new \Zend\Db\Sql\Where();
		$where->equalTo('user_id', $user['user_id']);
		$where->equalTo('app_type',$app_type);
		$where->notEqualTo('session_id', $sessionId);
		$model->delete($where);
		$time = time()+86400*7;

		if($model->getOne(array('session_id'=>$sessionId))){
			$data = array();
			$data['user_id'] = $user['user_id'];
			$data['value'] = serialize(array('user_id'=>$user['user_id']));
			$data['app_type'] = $app_type;
			$data['deadline'] = $time;//7天过期
			$reslut = !!$model->edit(array('session_id'=>$sessionId),$data);
		}else{
			$data = array();
			$data['session_id'] = $sessionId;
			$data['user_id'] = $user['user_id'];
			$data['value'] = serialize($user);
			$data['app_type'] = $app_type;
			$data['deadline'] = $time;//7天过期
			$reslut = !!$model->insert($data);
		}
		return $reslut;
	}
	/**
	 * 注册用户
	 * @author lishengyou
	 * 最后修改时间 2015年3月26日 上午9:38:35
	 *
	 * @param array $data
	 */
	public static function addUser(array $data){
		//保存基本信息
		$info = array('username','password','company_id','is_manager');
		$infoData = array_intersect_key($data, array_fill_keys($info, false));
		$infoData = array_filter($infoData,function($value){return $value || $value === 0;});
		if(count($infoData) != count($info)){
			static::setLastError('请填写完整信息');
			return false;
		}
		$infoData['salt'] = \Common\Helper\String::rand(5,\Common\Helper\String::RAND_TYPE_NUMBER_LETTER);
		$infoData['password'] = \Common\Helper\Encrypt::sha1($infoData['salt'].$infoData['password']);
		if(static::check($infoData['username'])){
			static::setLastError('用户已经存在');
			return false;
		}
		$infoData['create_time'] = time();
		$infoData['last_longing_time'] = time();
		$userModel = new \Common\Model\Erp\User();
		$userId = $userModel->insert($infoData);
		//添加扩展信息
		if($userId){
			$extendInfo = array('user_id'=>$userId,
					'name'=>$infoData['username'],
					'contact'=>$infoData['username'],
					'gender'=>1,'birthday'=>date('Y-m-d')
			);
			if(static::addExtendInfo($extendInfo)){
				return true;
			}
		}
		return false;
	}
	/**
	 * 添加管理
	 * @author lishengyou
	 * 最后修改时间 2015年3月26日 上午10:45:08
	 *
	 * @param array $data
	 */
	public static function addManager(array $data){
		$info = array('username','password');
		$infoData = array_intersect_key($data, array_fill_keys($info, false));
		$infoData = array_filter($infoData);
		if(count($infoData) != count($info)){
			static::setLastError('请填写完整信息');
			return false;
		}
		$infoData['is_manager'] = \Common\Model\Erp\User::IS_MANAGER;
		//添加公司
		\Common\Model::TransactionByGuid(\Common\Model\Erp\User::getGuid());
		$companyInfo = array();
		$companyInfo['company_name'] = $infoData['username'];
		$companyInfo['pattern'] = '11';
		$companyInfo['linkman'] = $infoData['username'];
		$companyInfo['telephone'] = $infoData['username'];
		$companyInfo['safe_passwd'] = $infoData['password'];
		if(isset($data['company'])){
			$companyInfo = array_merge($companyInfo,$data['company']);
		}
		$companyId = \Common\Helper\Erp\Company::add($companyInfo);
		if(!$companyId){
			static::setLastError('添加账号公司信息错误');
			\Common\Model::RollbackByGuid(\Common\Model\Erp\User::getGuid());
			return false;
		}
		//添加用户信息
		$infoData['company_id'] = $companyId;
		$userId = static::addUser($infoData);
		if(!$userId){
			//添加用户错误
			\Common\Model::RollbackByGuid(\Common\Model\Erp\User::getGuid());
			return false;
		}
		\Common\Model::CommitByGuid(\Common\Model\Erp\User::getGuid());
		return $userId;
	}
	/**
	 * 编辑用户
	 * @author lishengyou
	 * 最后修改时间 2015年3月26日 上午11:46:18
	 *
	 * @param unknown $user_id
	 * @param array $data
	 */
	public static function editUser($user_id,array $data){
		$info = array('password');
		$infoData = array_intersect_key($data, array_fill_keys($info, false));
		$infoData = array_filter($infoData);
		if(isset($infoData['password'])){
			$infoData['salt'] = \Common\Helper\String::rand(5,\Common\Helper\String::RAND_TYPE_NUMBER_LETTER);
			$infoData['password'] = \Common\Helper\Encrypt::sha1($infoData['salt'].$infoData['password']);
		}
		$userModel = new \Common\Model\Erp\User();
		return $userModel->edit(array('user_id'=>$user_id),$infoData);
	}
	/**
	 * 添加扩展信息
	 * @author lishengyou
	 * 最后修改时间 2015年4月10日 下午2:33:42
	 *
	 * @param array $data
	 * @return boolean
	 */
	public static function addExtendInfo(array $data){
		$info = array('user_id','name','contact','gender','birthday');
		$infoData = array_intersect_key($data, array_fill_keys($info, false));
		$infoData = array_filter($infoData);
		if(count($infoData) != count($info)){
			static::setLastError('请填写完整信息');
			return false;
		}
		if(isset($data['city_id'])){
			$infoData['city_id'] = intval($data['city_id']);
		}
		$userModel = new \Common\Model\Erp\UserExtend();
		return $userModel->insert($infoData);
	}
	/**
	 * 编辑用户扩展信息
	 * @author lishengyou
	 * 最后修改时间 2015年3月26日 上午11:46:18
	 *
	 * @param unknown $user_id
	 * @param array $data
	 */
	public static function editExtendUser($user_id,array $data){
		$info = array('name','contact','gender','birthday','city_id');
		$infoData = array_intersect_key($data, array_fill_keys($info, false));
		$infoData = array_filter($infoData);
		$userModel = new \Common\Model\Erp\UserExtend();
		return $userModel->edit(array('user_id'=>$user_id),$infoData);
	}
	/**
	 * @param unknown $user_id
	 * @author yusj | 最后修改时间 2015年4月29日下午3:35:53
	 */
	public static function is_manager($user_id){
		$userModel = new \Common\Model\Erp\User();
		$user = $userModel->getOne(array('user_id'=>$user_id));
		if(!$user){
			return false;
		}else{
			if($user['is_manager']!='1'){
				return false;
			}else{
				return true;
			}
		}
	}
	/**
	 * 设置城市session
	 * 修改时间2015年6月15日13:54:52
	 * 
	 * @author yzx
	 * @param unknown $cityId
	 */
	public static function setCitySesion($cityId){
		$cookie = new Cookie();
		$cookie->setCookie("city_session",$cityId,time()+86400*365);
	}
}