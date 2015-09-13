<?php
namespace Common\Helper\Erp\Housing;
interface Adaptation{
	/**
	 * 退租
	 * @author lishengyou
	 * 最后修改时间 2015年3月30日 下午2:31:17
	 *
	 */
	public function rentout($data);
	/**
	 * 续租
	 * @author lishengyou
	 * 最后修改时间 2015年3月30日 下午2:32:02
	 *
	 */
	public function renewal($data);
	/**
	 * 预订
	 * @author lishengyou
	 * 最后修改时间 2015年3月30日 下午2:38:18
	 *
	 */
	public function schedule($data);
	/**
	 * 取消预定
	 * @author yzx
	 * 修改时间2015年4月2日 16:42:11
	 */
	public function abolishRenewal($data);
	/**
	 * 停用
	 * @author lishengyou
	 * 最后修改时间 2015年3月30日 下午2:38:51
	 *
	 */
	public function disable($data);
	/**
	 * 预约退租
	 * @author lishengyou
	 * 最后修改时间 2015年3月30日 下午2:39:27
	 *
	 */
	public function appointmentrent($data);
	/**
	 * 出租
	 * @author yzx
	 * 修改时间2015年4月2日 09:39:45
	 */
	public function rental($data);
	/**
	 * 停用恢复
	 * @author yzx
	 * 修改时间2015年4月2日 09:42:07
	 */
	public function recover($data);
	/**
	 * 撤销预约退租
	 * @author yzx
	 * 修改时间2015年4月2日 09:45:55
	 */
	public function revocationSubscribe($data);
}