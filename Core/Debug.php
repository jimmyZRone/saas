<?php
namespace Core;
/**
 * 调试
 * @author lishengyou
 * 最后修改时间 2015年3月25日 上午10:13:31
 *
 */
class Debug{
	/**
	 * 生成耗时
	 * @author lishengyou
	 * 最后修改时间 2015年3月25日 上午10:15:13
	 *
	 * @param unknown $start_time
	 * @return number
	 */
	public static function getTimeConsuming($start_time){
		$end_time = microtime();
		$start_time = explode(' ', $start_time);
		$start_time = $start_time[1]+$start_time[0];
		$end_time = explode(' ', $end_time);
		$end_time = $end_time[1]+$end_time[0];
		return ($end_time - $start_time)*1000;
	}
}