<?php
namespace Common\Model\Erp;
class Business extends \Common\Model\Erp
{
	/**
	 * 根据区域获取商圈数据
	 * @param int $areaId
	 * @param int $cityId
	 * @return array
	 */
	public function getDataByArea($areaId,$cityId)
	{
		$select = $this->_sql_object->select("business");
		$select->where(array("area_id"=>$areaId,"city_id"=>$cityId));
		$result = $select->execute();
		return $result;
	}
}