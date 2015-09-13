<?php
namespace App\Web\Helper\Jooozo;
/**
 * 用户
 * @author lishengyou
 * 最后修改时间 2015年7月1日 下午4:23:06
 *
 */
class User{
	/**
	 * 判断用户是否存在
	 * @author lishengyou
	 * 最后修改时间 2015年7月1日 下午4:23:11
	 *
	 * @param unknown $username
	 */
	public static function IsExist($username){
		$db = \Common\Model::getLink('jooozo');
		if(!$db){
			return true;
		}
		$select = $db->select(array('u'=>'ppt_user'));
		$select->where(array('user_name'=>$username));
		return !!$select->execute();
	}
	public static function Login($phone,$passwd){
		
	}
}