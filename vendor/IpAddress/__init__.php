<?php
/**
 * IP地址库
 * @author lishengyou
 * 最后修改时间 2015年4月9日 下午2:38:57
 *
 */
class IpAddress{
	/**
	 * 取得IP所有城市
	 * @author lishengyou
	 * 最后修改时间 2015年4月9日 下午2:39:24
	 *
	 * @param unknown $ip
	 */
	public static function getIpCity($ip){
		$info = self::getIpAddressInfo($ip);
		if(!$info || !isset($info['city']) || !$info['city']) return false;
		return $info['city'];
	}
	/**
	 * 取得IP地址信息
	 * @author lishengyou
	 * 最后修改时间 2015年4月9日 下午2:42:29
	 *
	 * @param unknown $ip
	 * @return boolean|mixed
	 */
	public static function getIpAddressInfo($ip){
		$restAddress = 'http://ip.taobao.com/service/getIpInfo.php?ip=';
		$address = $restAddress.$ip;
		$ch = curl_init($address);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		$data = curl_exec($ch);
		curl_close($ch);
		$data = json_decode($data,true);
		if(!$data || !isset($data['data']) || !$data['data']){
			return false;
		}
		return $data['data'];
	}
}