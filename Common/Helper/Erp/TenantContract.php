<?php
namespace Common\Helper\Erp;
class TenantContract extends \Core\Object{
	/**
	 * 根据房源ID获取合同数据
	 * 修改时间2015年8月7日10:16:43
	 * 
	 * @author yzx
	 * @param int $id 房源ID
	 * @param int $houseType 房源类型
	 * @param string $isRoom
	 * @return Ambigous <boolean, \Core\Db\Sql\multitype:, \Core\Db\Sql\Ambigous, multitype:, mixed, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >
	 */
	public function getContractDataById($id,$houseType,$isRoom=false){
		$tenantContractModel = new \Common\Model\Erp\TenantContract();
		$sql = $tenantContractModel->getSqlObject();
		$select = $sql->select(array("tc"=>$tenantContractModel->getTableName()));
		$select->leftjoin(array("r"=>"rental"), "tc.contract_id=r.contract_id");
		$select->where(array("r.house_type"=>$houseType));
		if ($houseType == \Common\Model\Erp\Rental::HOUSE_TYPE_R){
			if ($isRoom){
				$select->where(array("r.room_id"=>$id));
			}
			if (!$isRoom){
				$select->where(array("r.house_id"=>$id));
			}
		}
		if ($houseType == \Common\Model\Erp\Rental::HOUSE_TYPE_F){
			$select->where(array("r.room_id"=>$id));
		}
		$select->where(array("r.is_delete"=>0));
		$select->where(array("tc.is_delete"=>0));
		$result = $select->execute();
		return $result;
	}
}