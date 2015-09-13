<?php
namespace App\Callback\Lib;
/**
 * Url助手
 * @author lishengyou
 * 最后修改时间 2015年2月27日 上午10:34:45
 *
 */
class Url{
	/**
	 * 解析URL
	 * @author lishengyou
	 * 最后修改时间 2015年2月27日 上午10:36:09
	 *
	 * @param array $param
	 */
	public static function parse($param){
		if(is_array($param)){
			return APP_URL.'callback.php?'.http_build_query($param);
		}else{
			$param = explode('/', $param);
			$data = array();
			$data['c'] = strtolower(array_shift($param));
			$data['a'] = empty($param) ? 'index' : strtolower(array_shift($param));
			if(!empty($data)){
				$length = count($param);
				for($i=0;$i<$length;$i+=2){
					$data[$param[$i]] = isset($param[$i+1]) ? $param[$i+1] : '';
				}
			}
			return self::parse($data);
		}
	}
}