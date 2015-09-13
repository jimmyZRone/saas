<?php
namespace Common\Helper;
/**
 * 消息推送
 * @author lishengyou
 * 最后修改时间 2015年7月14日 下午1:59:30
 *
 */
class Messagepush{
	const DEVICE_APP = 'App';
	const DEVICE_WEIXIN = 'Weixin';
	/**
	 * 推送到用户
	 * @author lishengyou
	 * 最后修改时间 2015年7月14日 下午2:04:21
	 *
	 * @param int|array $user 用户
	 * @param string  $msg 消息
	 * @param array $options
	 * @param float $device 设备
	 */
	public static function SendToUser($user,$msg,array $options,$device){
		if(is_array($device)){
			$result = array();
			foreach ($device as $_device){
				$result[$_device] = self::SendToUser($user, $msg, $options, $_device);
			}
			return $result;
		}else{
			$interface = __CLASS__.'\\'.$device;
			if(!\Core\Autoload::isExists($interface)){
				return false;
			}
			$interface = new $interface();
			if($interface instanceof MessagepushInterface){
				return $interface->send($user,$msg,$options);
			}
			return false;
		}
	}
}
interface MessagepushInterface{
	/**
	 * 推送
	 * @author lishengyou
	 * 最后修改时间 2015年7月14日 下午2:46:59
	 *
	 * @param int|array $user
	 * @param string $msg
	 * @param array $options
	 */
	public function send($user,$msg,array $options);
}