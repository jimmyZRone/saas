<?php
namespace App\Web\Mvc\Model;
class HouseNumber extends Common
{
	/**
	 * 添加房源编号
	 * @param unknown $data
	 * @return boolean
	 */
	public function addHouseNumber($data)
	{
		$house_number_data = array();
		$result = array();
		foreach ($data['house_number'] as $key=>$val)
		{
			$house_number_data['flat_id'] = $data['flat_id'];
			$house_number_data['floor'] = $val['floor'];
			$house_number_data['system_number'] = $val['system_number'];
			$house_number_data['update_number'] = $val['update_number'];
			$result[]=$this->insert($data);
		}
		if (!empty($result))
		{
			return true;
		}
		return false;
	}
}