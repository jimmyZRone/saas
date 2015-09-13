<?php
namespace Common\Helper;
include __DIR__.'/SphinxClient.class.php';
class Sphinx extends \SphinxClient{
	/**
	 * 取得链接
	 * @author lishengyou
	 * 最后修改时间 2015年8月26日 上午9:54:41
	 *
	 */
	public static function GetConnect(){
		static $connect = null;
		if(!$connect){
			$default = array(
				'match_mode'=>SPH_MATCH_EXTENDED2,
				'connect_timeout'=>3,
				'array_result'=>1
			);
			$options = \Core\Config::get('site:sphinx');
			$options = array_merge($default,$options);
			
			$connect = new self();
			$connect->SetServer($options['host'],$options['port']);
			$connect->SetMatchMode($options['match_mode']);
			$connect->SetConnectTimeout($options['connect_timeout']);
			$connect->SetArrayResult(!!$options['array_result']);
		}
		return $connect;
	}
}