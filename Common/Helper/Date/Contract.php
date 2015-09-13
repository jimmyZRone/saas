<?php
namespace Common\Helper\Date;
/**
 * 合同时间计算方式
 * @author lishengyou
 * 最后修改时间 2015年9月7日 下午3:53:25
 *
 */
class Contract{
	/**
	 * 
	 * @author lishengyou
	 * 最后修改时间 2015年9月7日 下午4:05:32
	 *
	 * @param number $month 加几个月
	 * @param number $time  在这个时间基础上加
	 * @param number $start_time	合同的开始时间
	 * @return number
	 */
	public static function AddMonth($month,$time,$start_time){
		$month = intval($month);
		$isMonthEnd = date('j',$start_time) == date('t',$start_time);//合同开始时间是不是月的最后一天
		$year = intval(date('Y',$time));
		$month += intval(date('m',$time));
		$day = intval(date('j',$time));
		if($month > 12){
			$year += floor($month / 12);
			$month = $month % 12;
		}
		$new_all_day = date('t',strtotime("{$year}-{$month}-01 00:00:00"));
		if($day > $new_all_day || ($isMonthEnd && date('j',$time) == date('t',$time))){
			$day = $new_all_day;
		}
		$month = str_pad($month, 2,'0',STR_PAD_LEFT);
		$day = str_pad($day, 2,'0',STR_PAD_LEFT);
		return strtotime("{$year}-{$month}-{$day} 00:00:00");
	}
}