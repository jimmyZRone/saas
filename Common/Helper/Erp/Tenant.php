<?php
namespace Common\Helper\Erp;
class Tenant extends \Core\Object
{
	/**
	 * 通过电话和身份证获取租客信息
	 * 修改时间2015年3月30日 16:53:10
	 * 
	 * @author yzx
	 * @param string $phone
	 * @param string $idcard
	 * @return unknown
	 */
	public function getTenant($companyId,$idcard)
	{
		$tenantModel = new \Common\Model\Erp\Tenant();
		$sql=$tenantModel->getSqlObject();
		$select = $sql->select("tenant")
				->where(array("company_id"=>$companyId,"idcard"=>$idcard));
		$result = $select->execute();
		if (!empty($result))
		{
			return $result[0];
		}
		return false;
	}
	/**
	 * 根据住客ID获取数据
	 * 修改时间2015年6月17日16:04:58
	 * 
	 * @author yzx
	 * @param unknown $tenantId
	 * @return Ambigous <boolean, \Core\Db\Sql\multitype:, \Core\Db\Sql\Ambigous, multitype:, mixed, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >
	 */
	public function getOneDateByTenant($tenantId){
		$RentalModel = new \Common\Model\Erp\Rental();
		$sql = $RentalModel->getSqlObject();
		$select = $sql->select($RentalModel->getTableName());
		$select->where(array("tenant_id"=>$tenantId,"is_delete"=>0));
		return $select->execute();
	}
	/**
	 * 删除租客
	 * 修改时间2015年7月13日13:36:35
	 * 
	 * @author yzx
	 * @param unknown $tenantId
	 * @return Ambigous <number, boolean>
	 */
	public function deleteTenant($tenantId){
		$tenantModel = new \Common\Model\Erp\Tenant();
		return $tenantModel->edit(array("tenant_id"=>$tenantId), array("is_delete"=>1));
	}
}