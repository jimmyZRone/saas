<?php
namespace Common\Helper\Erp;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;
class MeterReadingMoney extends \Core\Object
{
	private $id = null;
	/**
	 * 获取房源下面的房间和房间已租数据;
	 * 修改时间2015年5月25日 20:46:14
	 * 
	 * @author yzx
	 * @param int $houseId
	 * @return Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >
	 */
	public function getRoomByHouseIsRental($houseId,$isRoom=false)
	{
		$roomModel = new \Common\Model\Erp\Room();
		$where = new Where();
		$where->greaterThanOrEqualTo("r.status", $roomModel::STATIS_RENTAL);
		$where->equalTo("r.house_id", $houseId);
		$where->equalTo("r.is_delete", 0);
		$sql = $roomModel->getSqlObject();
		$select = $sql->select(array("r"=>$roomModel->getTableName()));
		$select->leftjoin(array("h"=>"house"), "r.house_id = h.house_id",array("house_name","rent_count"=>new Expression("count(cr.tenant_id)")));
		$select->leftjoin(array("rt"=>"rental"), "rt.room_id = r.room_id",array("contract_id"));
		$select->leftjoin(array("t"=>"tenant"), "rt.tenant_id = t.tenant_id",array("company_id"));
		$select->leftjoin(array("tc"=>"tenant_contract"), "rt.contract_id = tc.contract_id",array("signing_time","end_line"));
		$select->leftjoin(array("cr"=>'contract_rental'), "tc.contract_id = cr.contract_id",array("creat_time","tenant_id"));
		$select->where($where);
		if ($isRoom){
			$select->group("r.room_id");
		}
		//print_r(str_replace('"', '', $select->getSqlString()));die();
		$result = $select->execute();
		return $result;
	}
	/**
	 * 获取分散式整租费用
	 * 修改时间2015年6月15日11:17:04
	 * 
	 * @author yzx
	 * @param unknown $houseId
	 */
	public function getHouseIsRental($houseId){
		$houseModel = new \Common\Model\Erp\House();
		$where = new Where();
		$where->greaterThanOrEqualTo("tc.end_line", time());
		$where->equalTo("h.house_id", $houseId);
		$where->equalTo("h.is_delete", 0);
		$where->equalTo("rt.house_type", 1);
		$sql = $houseModel->getSqlObject();
		$select = $sql->select(array("h"=>$houseModel->getTableName()));
		$select->leftjoin(array("rt"=>"rental"), "rt.house_id = h.house_id",array("contract_id"));
		$select->leftjoin(array("t"=>"tenant"), "rt.tenant_id = t.tenant_id",array("company_id"));
		$select->leftjoin(array("tc"=>"tenant_contract"), "rt.contract_id = tc.contract_id",array("signing_time","end_line"));
		$select->leftjoin(array("cr"=>'contract_rental'), "tc.contract_id = cr.contract_id",array("creat_time","tenant_id"));
		$select->where($where);
		$result = $select->execute();
		//print_r(str_replace('"', '', $select->getSqlString()));die();
		return $result?$result[0]:array();
	}
	/**
	 * 修改集中式合同数据
	 * 修改时间2015年8月31日15:56:10
	 * 
	 * @author yzx
	 * @param int $roomId
	 */
	public function updateFlatContract($count_money,$addTime,$roomId = 0){
		$rentalModel = new \Common\Model\Erp\Rental();
		$tenantContractModel = new \Common\Model\Erp\TenantContract();
		$sql = $rentalModel->getSqlObject();
		$select = $sql->select(array("r"=>$rentalModel->getTableName()));
		$select->leftjoin(array("tc"=>"tenant_contract"), "r.contract_id = tc.contract_id",array("end_line","next_pay_time"));
		if ($roomId>0){
			$select->where(array("r.room_id"=>$roomId));
		}
		$select->where(array("r.house_type"=>2));
		$select->where(array("r.is_delete"=>0));
		$select->where(array("tc.is_stop"=>0));
		$select->where(array("tc.is_delete"=>0));
		$result = $select->execute();
		if (!empty($result)){
			$room_key = array();
			foreach ($result as $key=>$val){
					if ($val['next_pay_time']<$val['end_line']){
						$contract_id = $val['contract_id'];
					}else {
						if ($val['next_pay_time']>0);
						{
							$contract_id = $val['contract_id'];
						}
					}
			}
			if ($contract_id>0){
				$contract_data = $tenantContractModel->getOne(array("contract_id"=>$contract_id));
				$tenantContractModel->edit(array("contract_id"=>$contract_id), array("next_pay_money"=>$contract_data['next_pay_money']+$count_money));
			}
		}
	}
	/**
	 * 修改分散式合同
	 * 修改时间2015年8月31日15:56:25
	 * 
	 * @author yzx
	 * @param int $houseId
	 * @param int $roomId
	 */
	public function updataRContract($count_money,$addTime,$houseId = 0, $roomId = 0){
		$rentalModel = new \Common\Model\Erp\Rental();
		$tenantContractModel = new \Common\Model\Erp\TenantContract();
		$sql = $rentalModel->getSqlObject();
		$select = $sql->select(array("r"=>$rentalModel->getTableName()));
		$select->leftjoin(array("tc"=>"tenant_contract"), "r.contract_id = tc.contract_id",array("signing_time","end_line","next_pay_time"));
		if ($roomId>0){
			$select->where(array("r.room_id"=>$roomId));
		}
		if ($houseId>0){
			$select->where(array("r.house_id"=>$houseId));
		}
		$select->where(array("r.house_type"=>1));
		$select->where(array("r.is_delete"=>0));
		$select->where(array("tc.is_stop"=>0));
		$select->where(array("tc.is_delete"=>0));
		$result = $select->execute();
		$contract_id = 0;
		//房间
		if ($roomId>0){
			if (!empty($result)){
				$room_key = array();
				foreach ($result as $key=>$val){
						if ($val['next_pay_time']<$val['end_line']){
							$contract_id = $val['contract_id'];
						}else {
							if ($val['next_pay_time']>0);
							{
								$contract_id = $val['contract_id'];
							}
						}
				}
				if ($contract_id>0){
					$contract_data = $tenantContractModel->getOne(array("contract_id"=>$contract_id));
					$tenantContractModel->edit(array("contract_id"=>$contract_id), array("next_pay_money"=>$contract_data['next_pay_money']+$count_money));
				}
			}
			
		}
		//房源
		if ($houseId>0){
			if (!empty($result)){
				$house_key = array();
				foreach ($result as $key=>$val){
						if ($val['next_pay_time']<$val['end_line']){
							$contract_id = $val['contract_id'];
						}else {
							if ($val['next_pay_time']>0);
							{
								$contract_id = $val['contract_id'];
							}
						}
				}
				if ($contract_id>0){
					$contract_data = $tenantContractModel->getOne(array("contract_id"=>$contract_id));
					$tenantContractModel->edit(array("contract_id"=>$contract_id), array("next_pay_money"=>$contract_data['next_pay_money']+$count_money));
				}
			}
		}
	}
}

