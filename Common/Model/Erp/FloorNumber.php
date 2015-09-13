<?php
namespace Common\Model\Erp;

class FloorNumber extends \Common\Model\Erp
{
	/**
	 * 添加楼层编号
	 * 修改时间2015年3月24日 15:02:31
	 *
	 * @author yzx
	 * @param array $data
	 * @return boolean
	 */
	public function addFoolNumber($numberData,$faltId)
	{
		$fool_number_data = array();
		$result = array();
		foreach ($numberData as $key=>$val)
		{
			$fool_number_data['update_number'] = $val;
			$fool_number_data['flat_id'] = $faltId;
			$result[] = $this->insert($fool_number_data);
		}
		if (empty($result))
		{
			return false;
		}
		return true;
	}
	/**
	 * 获取公寓楼层编号
	 * 修改时间2015年6月2日 10:09:50
	 * 
	 * @author yzx
	 * @param unknown $flatId
	 * @return Ambigous <boolean, multitype:multitype:Ambigous <number, unknown> number  Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> > >
	 */
	public function getFloorNumber($flatId){
		$flat_number = $this->getData(array("flat_id"=>$flatId));
		return $flat_number;
	}
}