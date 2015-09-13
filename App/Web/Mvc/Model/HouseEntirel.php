<?php
namespace  Common\Helper\Erp;
class HouseEntirel extends \Core\Object
{
	/**
	 * 添加房源额外信息
	 * 修改时间2015年3月18日 16:37:15
	 * 
	 * @author yzx
	 * @param array $data
	 * @return number
	 */
	public function addEntirel($data)
	{
		$entirdel_data['money'] = $data['money'];
		$entirdel_data['status'] = self::STATUS_RENT;
		$entirdel_data['detain'] = $data['detain'];
		$entirdel_data['pay'] = $data['pay'];
		$entirdel_data['house_id'] = $data['house_id'];
		$entirdel_data['detain'] = $data['detain'];
		$entirdel_data['pay'] = $data['pay'];
		return $this->insert($entirdel_data);
	}
	/**
	 * 编辑房源额外信息
	 * 修改时间2015年3月18日 16:37:58
	 * 
	 * @author yzx
	 * @param array $data
	 * @param int $entirel_id
	 * @return Ambigous <number, boolean>
	 */
	public function editEntirel($data,$entirel_id)
	{
		$entirel_data['money'] = $data['money'];
		$entirel_data['status'] = $data['status'];
		$entirel_data['occupancy_number'] = $data['occupancy_number'];
		$entirel_data['exist_occupancy_number'] = $data['exist_occupancy_number'];
		$entirel_data['gender_restrictions'] = implode("-", $data['gender_restrictions']);
		$entirdel_data['detain'] = $data['detain'];
		$entirdel_data['pay'] = $data['pay'];
		return $this->edit(array("house_entirel_id"=>$entirel_id), $entirel_data);
	}
}