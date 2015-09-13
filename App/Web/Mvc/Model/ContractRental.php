<?php
namespace App\Web\Mvc\Model;
class ContractRental extends Common
{
	/**
	 * 添加合同租客
	 * 修改时间2015年3月18日 15:13:55
	 * 
	 * @author yzx
	 * @param array $data
	 * @return number
	 */
	public function addContractRental($data)
	{
		$result = $this->insert($data);
		return $result;
	}
	/**
	 * 获取合同所有租客
	 * 修改时间2015年3月18日 15:18:24
	 * 
	 * @author yzx
	 * @param int $contractId
	 * @return array
	 */
	public function getTenant($contractId)
	{
		$select = $this->_sql_object->select(array("cr"=>"contract_rental"))
				  ->leftjoin(array("t"=>"tenant"),"cr.tenant_id = t.tenant_id",array("name","gender","phone","idcard","source"))
				  ->where(array("cr.contract_id"=>$contractId));
		$result = $select->execute();
		return $result;
	}
}