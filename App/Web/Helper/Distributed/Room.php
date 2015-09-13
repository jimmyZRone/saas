<?php
namespace App\Web\Helper\Distributed;
/**
 * 分散式房间
 * @author lishengyou
 * 最后修改时间 2015年3月30日 下午2:49:40
 *
 */
class Room extends \Common\Helper\Erp\Distributed\Room implements \App\Web\Helper\Housing\Adaptation{
	/**
	 * 根据ID取信息
	 * @author lishengyou
	 * 最后修改时间 2015年3月30日 下午5:10:28
	 *
	 * @param array $ids
	 */
	public function getInfoByIds(array $ids){
		$model = new \Common\Model\Erp\Room();
		$sql = $model->getSqlObject();
		$table = $model->getTableName();
		$select = $sql->select($table);
		$select->join(array('house'=>'house'), 'house.house_id='.$table.'.house_id');
		$select->where(array('room_id'=>$ids));
		$data = $select->execute();
		$data = $data ? $data : array();
		$temp = array();
		foreach ($data as $key => $value){
			$temp[$value['room_id']] = $value;
		}
		return $temp;
	}
}