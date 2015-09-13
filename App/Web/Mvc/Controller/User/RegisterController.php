<?php
namespace App\Web\Mvc\Controller\User;
/**
 * 用户注册
 * @author lishengyou
 * 最后修改时间 2015年4月8日 下午7:17:32
 *
 */
class RegisterController extends \App\Web\Lib\Controller{
	/**
	 * 显示页面
	 * @author lishengyou
	 * 最后修改时间 2015年4月8日 下午7:25:42
	 *
	 */
	public function indexAction(){
		$this->display('index');
	}
	/**
	 * 注册
	 * @author lishengyou
	 * 最后修改时间 2015年4月9日 下午1:55:35
	 *
	 */
	public function registerAction(){
		$phone = \App\Web\Lib\Request::queryString('post.phone');
		$phone_code = \App\Web\Lib\Request::queryString('post.phone_code');
		$passwd = \App\Web\Lib\Request::queryString('post.passwd');
		$cfm_passwd = \App\Web\Lib\Request::queryString('post.cfm_passwd');
		$protocol = \App\Web\Lib\Request::queryString('post.protocol');
		
		if(!$protocol){
			return $this->returnAjax(array('status'=>0,'message'=>'请先同意协议内容'));
		}
		if(!$phone || !$phone_code || !$passwd || !$cfm_passwd){
			return $this->returnAjax(array('status'=>0,'message'=>'请填写完整信息'));
		}
		//手机号相关
		$result = \Common\Helper\Sms\TianyiCaptcha::check($phone, $phone_code, false);
		if(!$result){
			return $this->returnAjax(array('status'=>0,'message'=>'手机验证码错误'));
		}
		if(!\Common\Helper\ValidityVerification::IsPhone($phone)){
			return $this->returnAjax(array('status'=>0,'message'=>'请输入正确的手机号'));
		}
		//密码相关
		if($passwd != $cfm_passwd){
			return $this->returnAjax(array('status'=>0,'message'=>'两次密码不一致'));
		}
		$result = \Common\Helper\ValidityVerification::IsPasswd($passwd);
		if($result['status'] != 1){
			return $this->returnAjax($result);
		}
		if(\Common\Helper\Erp\User::check($phone)){
			return $this->returnAjax(array('status'=>0,'message'=>'用户已经存在'));
		}
		//兼容ERP坑啊~~~
		if(\App\Web\Helper\Jooozo\User::IsExist($phone)){
			return $this->returnAjax(array('status'=>0,'message'=>'您是ERP1.0用户，请前往ERP系统进行数据迁移，直接升级为SaaS2.0用户。'));
		}
		$result = \Common\Helper\Erp\User::addManager(array('username'=>$phone,'password'=>$passwd));
		if($result){//注册成功
			//删除验证码
			\Common\Helper\Sms\TianyiCaptcha::clear($phone);
			//写日志
			\Common\Helper\Erp\OperationLog::save($result, \Common\Helper\Erp\OperationLog::ACTION_USER_REG, $result,get_client_ip());
			return $this->returnAjax(array('status'=>1,'url'=>\App\Web\Helper\Url::parse('user-login')));
		}
		$message = \Common\Helper\Erp\User::getLastError();
		return $this->returnAjax(array('status'=>0,'message'=>$message ? $message : '注册失败'));
	}
	/**
	 * 内测申请
	 * @author lishengyou
	 * 最后修改时间 2015年7月1日 下午9:45:44
	 *
	 */
	public function betaAction(){
		$flat_name = \App\Web\Lib\Request::queryString('request.flat_name');
		$type = \App\Web\Lib\Request::queryString('request.type',0,'intval');
		$city_name = \App\Web\Lib\Request::queryString('request.city_name');
		$contacts_name = \App\Web\Lib\Request::queryString('request.contacts_name');
		$contacts_phone = \App\Web\Lib\Request::queryString('request.contacts_phone');
		if(mb_strlen($flat_name,'utf-8') > 20){
			return $this->returnAjax(array('status'=>0,'message'=>'公寓名称大于20个字'));
		}
		if(!$city_name){
			return $this->returnAjax(array('status'=>0,'message'=>'请填写城市名称'));
		}
		if(mb_strlen($city_name,'utf-8') > 20){
			return $this->returnAjax(array('status'=>0,'message'=>'城市名称不能大于20个字'));
		}
		if(!$contacts_name){
			return $this->returnAjax(array('status'=>0,'message'=>'请填写联系人'));
		}
		if(mb_strlen($contacts_name,'utf-8') > 20){
			return $this->returnAjax(array('status'=>0,'message'=>'联系人不能大于20个字'));
		}
		if(!$contacts_phone){
			return $this->returnAjax(array('status'=>0,'message'=>'请填写联系电话'));
		}
		if(mb_strlen($contacts_phone,'utf-8') > 30){
			return $this->returnAjax(array('status'=>0,'message'=>'联系电话不能大于30个字'));
		}
		$db = \Common\Model::getLink('erp');
		if(!$db){
			return $this->returnAjax(array('status'=>0,'message'=>'系统发生了一些错误，请稍后在试'));
		}
		$select = $db->select(array('ba'=>'betaapplication'));
		$select->where(array('contacts_phone'=>$contacts_phone));
		if($select->execute()){
			return $this->returnAjax(array('status'=>0,'message'=>'您已经提交过申请了，请耐心等待客服人员与您联系'));
		}
		$data = array(
			'flat_name'=>$flat_name,
			'type'=>$type,
			'city_name'=>$city_name,
			'contacts_name'=>$contacts_name,
			'contacts_phone'=>$contacts_phone
		);
		$insert = $db->insert('betaapplication');
		$data['create_time'] = time();
		$insert->values($data);
		if($insert->execute()){
			$content = "公寓名称:{$flat_name}|公寓类型:".($type == 1 ? '分散式' : ($type == 2 ? '集中式' : '未选择'))."|城市:{$city_name}|联系人:{$contacts_name}|联系电话:{$contacts_phone}";
			\Common\Helper\Sms::phone($content, '18408298361');
			return $this->returnAjax(array('status'=>1));
		}
		return $this->returnAjax(array('status'=>0,'message'=>'系统发生了一些错误，请稍后在试'));
	}
}