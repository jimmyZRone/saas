<?php
namespace App\Web\Helper\Centralized;
/**
 * 集中式房源
 * @author lishengyou
 * 最后修改时间 2015年3月30日 下午2:49:40
 *
 */
class Room extends \Common\Helper\Erp\Centralized\Room implements \App\Web\Helper\Housing\Adaptation{
	/**
	 * 根据ID取信息
	 * @author lishengyou
	 * 最后修改时间 2015年3月30日 下午5:10:28
	 *
	 * @param array $ids
	 */
	public function getInfoByIds(array $ids){
		$model = new \Common\Model\Erp\RoomFocus();
		$data = $model->getData(array('room_focus_id'=>$ids));
		$data = $data ? $data : array();
		$temp = array();
		foreach ($data as $key => $value){
			$temp[$value['room_focus_id']] = $value;
		}
		return $temp;
	}
}