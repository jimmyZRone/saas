<?php
namespace Common\Model\Erp;
class HouseEntirel extends \Common\Model\Erp
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
		$entirdel_data['status'] = House::STATUS_NOT_RENTAL;
		$entirdel_data['detain'] = $data['detain'];
		$entirdel_data['pay'] = $data['pay'];
		$entirdel_data['house_id'] = $data['house_id'];
		$entirdel_data['occupancy_number'] = $data['occupancy_number'];
		$entirdel_data['gender_restrictions'] = $data['gender_restrictions'];
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
		$entirel_data['occupancy_number'] = $data['occupancy_number'];
		$entirel_data['gender_restrictions'] = $data['gender_restrictions'];
		$entirel_data['detain'] = $data['detain'];
		$entirel_data['pay'] = $data['pay'];
		return $this->edit(array("house_entirel_id"=>$entirel_id), $entirel_data);
	}
	/**
	 * 修改房间出租状态为已出租
	 * @param $id 房间id
	 * @param $sid 状态 4,已租,2未租,3停用
	 * @author too|编写注释时间 2015年5月14日 下午4:53:49
	 */
	public function changStatus($id,$sid=Room::STATUS_NOT_RENTAL)
	{
		$reserveModel = new Reserve();
		$reserve_data = $reserveModel->getData(array("house_type"=>$reserveModel::HOUSE_TYPE_R,"house_id"=>$id,"is_delete"=>0)); 
		if (empty($reserve_data)){
			$data['is_yd'] = 0;
		}
	    $where = array('house_id'=>$id);
	    $data['status'] = $sid;
	    $data['is_yytz'] = 0;
	    return $this->edit($where,$data);
	}
	/**
	 * 获取一条房间数据
	 * 修改时间2015年5月22日 14:17:36
	 *
	 * @author yzx
	 * @param int $houseId
	 * @return Ambigous <multitype:, NULL, \ArrayObject, \Zend\Db\ResultSet\mixed, unknown>
	 */
	public function getHouseData($houseId)
	{
		$houseModel = new House();
		$sql = $houseModel->getSqlObject();
		$select = $sql->select(array("h"=>$houseModel->getTableName()));
		$select->leftjoin(array("he"=>$this->getTableName()), "h.house_id = he.house_id");
		$select->where(array("h.house_id"=>$houseId));
		$result = $select->execute();
		return $result?$result[0]:array();
	}
}