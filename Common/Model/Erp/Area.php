<?php
namespace Common\Model\Erp;
class Area extends \Common\Model\Erp
{
	/**
	 * 根据城市获取数据
	 * 修改时间2015年4月15日 09:20:31
	 * 
	 * @author yzx
	 * @param int $cityId
	 * @return array
	 */
	public function getDataByCity($cityId)
	{
		$select = $this->_sql_object->select("area");
		$select->where(array("city_id"=>$cityId));
		$result = $select->execute();
		return $result;
	}
}