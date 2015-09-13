<?php
namespace Common\Model\Erp;
class ContractRental extends \Common\Model\Erp
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
	 * 编辑合同租客
	 * @author too|最后修改时间 2015年4月24日 下午2:47:42
	 */
	public function editContractRental($data,$crId)
	{
	    $ContractRental_data = $this->getOne(array("contract_rental_id"=>$crId));
	    if (!empty($ContractRental_data))
	    {
	        $result = $this->edit(array("contract_rental_id"=>$crId), $data);
	        if ($result)
	        {
	            return true;
	        }
	        return false;
	    }
	    return false;
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
				  ->leftjoin(array("t"=>"tenant"),"cr.tenant_id = t.tenant_id",array("name","gender","phone","idcard","from"))
				  ->where(array("cr.contract_id"=>$contractId,"cr.is_delete"=>0));
		$result = $select->execute();
		return $result;
	}
}