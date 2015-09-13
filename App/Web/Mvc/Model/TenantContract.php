<?php
namespace App\Web\Mvc\Model;
class TenantContract extends Common
{
	/**
	 * 添加合同
	 * 修改时间2015年3月18日 12:56:52
	 * 
	 * @author yzx
	 * @param array $data
	 * @return boolean
	 */
	public function addContract($data)
	{
		$rentalModel = new Rental();
		$tenantModel = new Tenant();
		$contractRentModel = new ContractRental();
		$rentContractModel = new RentContractExtend();
		//添加合同
		$data['dead_line'] = strtotime($data['end_line']);
		$data['end_line'] = strtotime($data['end_line']);
		$data['occupancy_time'] = strtotime($data['occupancy_time']);
		$data['total_money'] = $data['rent']*$data['rent'];
		$data['pay_num'] = $data['pay'];
		$data['next_pay_time'] = $this->clacAddNexPayTime($data);
		$this->Transaction();
		$new_contract_id = $this->insert($data);
		if (!$new_contract_id) 
		{
			$this->rollback();
			return false;
		}
		if (is_array($data['tenant_data']) && !empty($data['tenant_data']))
		{
			//添加租客
			$tenant_data=array();
			$tenant_id=array();
			foreach ($data['tenant_data'] as $tkey=>$tval)
			{
				$tenant_data['name'] = $tval['name'];
				$tenant_data['gender'] = $tval['gender'];
				$tenant_data['phone'] = $tval['phone'];
				$tenant_data['idcard'] = $tval['idcard'];
				$tenant_id[] = $tenantModel->addTenant($tenant_data);
			}
			if (empty($tenant_id))
			{
				$this->rollback();
				return false;
			}
		}
		//添加租住关系
		$data['tenant_id'] = $tenant_id[0];
		$data['contract_id'] = $new_contract_id;
		$rental_id = $rentalModel->addRental($data);
		if (!$rental_id)
		{
			$this->rollback();
			return false;
		}
		$contract_rent_data = array();
		foreach ($tenant_id as $key)
		{
			$contract_rent_data['contract_id'] = $new_contract_id;
			$contract_rent_data['tenant_id'] = $key;
			$contract_rent_data['creat_time'] = time();
			$contract_rent_id[] = $contractRentModel->addContractRental($contract_rent_data);
		}
		if (empty($contract_rent_id)){
			$this->rollback();
			return false;
		}
		$r_result = $rentContractModel->addExtend($data);
		if (!$r_result)
		{
			$this->rollback();
		}
		$this->commit();
		return $new_contract_id;
	}
	/**
	 * 获取租客合同详情
	 * 修改时间2015年3月18日 15:23:46
	 * 
	 * @author yzx
	 * @param int $contractId
	 * @return multitype:multitype: Ambigous <boolean, multitype:, unknown>
	 */
	public function detail($contractId)
	{
		$contractRentalModel = new ContractRental();
		$serialNumberModel = new SerialNumber();
		$rentalModel = new Rental();
		$rental_data = $rentalModel->getRentalByContract($contractId);
		$contract_data = $this->getOne(array("contract_id"=>$contractId));
		$tenant_list = $contractRentalModel->getTenant($contractId);
		//财务数据
		$finance_data = $serialNumberModel->getContractSerial($rental_data['rental_id'], $rental_data['source']);
		if (!empty($contract_data))
		{
			return array("contract_data"=>$contract_data,"tenant_list"=>$tenant_list,"finance_data"=>$finance_data);
		}
		return false;
	}
	/**
	 * 修改租客合同
	 * 修改时间2015年3月18日 15:51:27
	 * 
	 * @author yzx
	 * @param array $data
	 * @param int $contractId
	 * @return boolean
	 */
	public function editContract($data,$contractId)
	{
		$tenantModel = new Tenant();
		$data['dead_line'] = strtotime($data['end_line']);
		$data['end_line'] = strtotime($data['end_line']);
		$data['occupancy_time'] = strtotime($data['occupancy_time']);
		$data['total_money'] = $data['rent']*$data['rent'];
		$data['pay_num'] = $data['pay'];
		//下次付款时间计算
		$data['next_pay_time'] = $this->clacAddNexPayTime($data);
		$this->Transaction();
		$contract_res = $this->edit(array("contract_id"=>$contractId), $data);
		if (!$contract_res){
			$this->rollback();
			return false;
		}
		if (!empty($data['tenant_data']) && is_array($data['tenant_data']))
		{
			$tenant_data = array();
			foreach ($data['tenant_data'] as $tkey=>$tval)
			{
				$tenant_data['name'] = $tval['name'];
				$tenant_data['gender'] = $tval['gender'];
				$tenant_data['phone'] = $tval['phone'];
				$tenant_data['idcard'] = $tval['idcard'];
				$tenant_res = $tenantModel->editTenant($data, $tval['tenant_id']);
				if (!$tenant_res)
				{
					$this->rollback();
					return false;
				}
			}
		}
		$this->commit();
		return true;
	}
	/**
	 * 计算添加合同时下次付款时间
	 * 修改时间2015年3月20日 14:52:54
	 * 
	 * @author yzx
	 * @param array $data
	 * @return number
	 */
	private function clacAddNexPayTime($data,$contractId)
	{
		$contractModel = new TenantContract();
		$contract_data = $contractModel->getOne(array("contract_id"=>$contractId));
		$next_time = 0;
		if (!empty($contract_data))
		{
			if ($contract_data['end_line'] <= time())
			{
				$next_time = $contract_data['end_line'];
			}else 
			{
				$next_time = $contract_data['next_pay_time'];
			}
			return $next_time;
		}
		return $next_time;
	}
	
}