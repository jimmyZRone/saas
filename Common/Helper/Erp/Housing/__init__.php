<?php
namespace Common\Helper\Erp;
/**
 * 住房
 * @author lishengyou
 * 最后修改时间 2015年3月30日 下午2:22:31
 *
 */
class Housing{
	/**
	 * 集中式
	 * @var unknown
	 */
	const CENTRALIZED = 1;
	/**
	 * 分散式整租
	 * @var unknown
	 */
	const DISTRIBUTED_ENTIRE = 2;
	/**
	 * 分散式合租
	 * @var unknown
	 */
	const DISTRIBUTED_ROOM = 3;
	/**
	 * 集中式房源
	 * @var unknown
	 */
	const TYPE_CENTRALIZED = 1;
	/**
	 * 分散式房源
	 * @var unknown
	 */
	const TYPE_DISTRIBUTED = 2;
	/**
	 * 取得住房
	 * @author lishengyou
	 * 最后修改时间 2015年3月30日 下午2:43:50
	 *
	 * @param 住房类型 $houing_type
	 * @param 住房编号 $housing_id
	 */
	public static function getHousing($houing_type,$housing_id){
		
	}
	protected $_adaptation = null;
	public function __construct(Housing\Adaptation $adaptation){
		$this->_adaptation = $adaptation;
	}
	/**
	 * 退租
	 * @author lishengyou
	 * 最后修改时间 2015年3月30日 下午2:31:17
	 *
	 */
	public function rentout(){
		
	}
	/**
	 * 续租
	 * @author lishengyou
	 * 最后修改时间 2015年3月30日 下午2:32:02
	 *
	 */
	public function renewal(){
		
	}
	/**
	 * 预订
	 * @author lishengyou
	 * 最后修改时间 2015年3月30日 下午2:38:18
	 *
	 */
	public function schedule(){
		
	}
	/**
	 * 停用
	 * @author lishengyou
	 * 最后修改时间 2015年3月30日 下午2:38:51
	 *
	 */
	public function disable(){
		
	}
	/**
	 * 预约退租
	 * @author lishengyou
	 * 最后修改时间 2015年3月30日 下午2:39:27
	 *
	 */
	public function appointmentrent(){
		
	}
}