<?php
namespace App\Web\Mvc\Model;
class RentContractExtend extends Common
{
	/**
	 * 添加租客合同额外信息
	 * 修改时间2015年3月25日 10:53:50
	 * 
	 * @author yzx
	 * @param array $data
	 * @return number|boolean
	 */
	public function addExtend($data)
	{
		$extend_data = array();
		$result = $this->insert($data);
		if ($result)
		{
			return $result;
		}
		return false;
	}
}