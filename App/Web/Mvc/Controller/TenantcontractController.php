<?php
namespace App\Web\Mvc\Controller;
use App\Web\Lib\Request;
class TenantcontractController extends \App\Web\Lib\Controller
{
	/**
	 * 添加租客合同
	 * 修改时间2015年3月18日 14:56:19
	 *
	 * @author yzx
	 */
	protected function addAction()
	{
		$contract_data = array();
		if (Request::isPost())
		{
			$contractModel = new \Common\Model\Erp\TenantContract();
			$house_id = Request::queryString("post.house_id",0,"int");
			$room_id = Request::queryString("post.room_id",0,"int");
			$source_id = Request::queryString("post.source_id",0,"int");
			$source = Request::queryString("post.source",'',"string");
			$custom_number = Request::queryString("post.custom_number",'',"string");
			$signing_time = Request::queryString("post.signing_time",'',"string");
			$occupancy_time = Request::queryString("post.occupancy_time",'',"string");
			$end_line = Request::queryString("post.end_line",'',"string");
			$rent = Request::queryString("post.rent",0,"float");
			$deposit = Request::queryString("post.deposit",0,"float");
			$detain = Request::queryString("post.detain",0,"string");
			$pay = Request::queryString("post.pay",0,"string");
			$remark = Request::queryString("post.remark",'','string');
			$tenant_data = Request::queryString("post.tenant_data");
			//公寓额外信息
			$community = Request::queryString("post.community",'',"string");
			$cost = Request::queryString("post.cost",'',"string");
			$unit = Request::queryString("post.unit",'',"string");
			$number = Request::queryString("post.number",'',"number");
			$flat_name = Request::queryString("post.flat_name",'',"flat_name");
			$floor = Request::queryString("post.floor",0,"int");
			$custom_number = Request::queryString("post.custom_number",'',"string");
			$is_renewal = Request::queryString("post.is_renewal",0,"int");
			$parent_id = Request::queryString("post.parent_id",0,"int");
				
			$contract_data['house_id'] = $house_id;
			$contract_data['room_id'] = $room_id;
			$contract_data['source_id'] = $source_id;
			$contract_data['source'] = $source;
			$contract_data['custom_number'] = $custom_number;
			$contract_data['signing_time'] = $signing_time;
			$contract_data['occupancy_time'] = $occupancy_time;
			$contract_data['end_line'] = $end_line;
			$contract_data['rent'] = $rent;
			$contract_data['deposit'] = $deposit;
			$contract_data['detain'] = $deposit;
			$contract_data['pay'] = $pay;
			$contract_data['remark'] = $remark;
			$contract_data['tenant_data'] = $tenant_data;
			//公寓额外信息
			$contract_data['community'] = $community;
			$contract_data['cost'] = $cost;
			$contract_data['unit'] = $unit;
			$contract_data['flat_name'] = $flat_name;
			$contract_data['floor'] = $floor;
			$contract_data['custom_number'] = $custom_number;
			$contract_data['parent_id'] = $parent_id;
			if ($is_renewal ==  \Common\Model\Erp\TenantContract::IS_RENEWAL)
			{
				$is_renewal = true;
			}
			$result = $contractModel->addContract($contract_data,$is_renewal);
			if ($result){
				return $this->returnAjax(array("status"=>1,"data"=>$result));
			}
		}
	}
	/**
	 * 获取租客合同详情
	 * 修改时间2015年3月18日 15:28:59
	 * 
	 * @author yzx
	 */
	protected function detailAction()
	{
		$contract_id = Request::queryString("get.contract_id",0,"int");
		if (Request::isGet()) {
			$contractModel = new \Common\Model\Erp\TenantContract();
			$result = $contractModel->detail($contract_id);
			if ($result)
			{
				return $this->returnAjax(array("status"=>1,"data"=>$result));
			}
			return $this->returnAjax(array("status"=>0,"data"=>false));
		}
	}
	/**
	 * 修改租客合同
	 * 修改时间2015年3月18日 15:54:04
	 * 
	 * @author yzx
	 */
	protected function editAction()
	{
		if (Request::isPost())
		{
			$contractModel = new \Common\Model\Erp\TenantContract();
			$contract_id = Request::queryString("post.contract_id",0,"int");
			$house_id = Request::queryString("post.house_id",0,"int");
			$room_id = Request::queryString("post.room_id",0,"int");
			$source_id = Request::queryString("post.source_id",0,"int");
			$source = Request::queryString("post.source",'',"string");
			$custom_number = Request::queryString("post.custom_number",'',"string");
			$signing_time = Request::queryString("post.signing_time",'',"string");
			$occupancy_time = Request::queryString("post.occupancy_time",'',"string");
			$end_line = Request::queryString("post.end_line",'',"string");
			$rent = Request::queryString("post.rent",0,"float");
			$deposit = Request::queryString("post.deposit",0,"float");
			$detain = Request::queryString("post.detain",0,"string");
			$pay = Request::queryString("post.pay",0,"string");
			$remark = Request::queryString("post.remark",'','string');
			$tenant_data = Request::queryString("post.tenant_data");
			
			$contract_data['house_id'] = $house_id;
			$contract_data['room_id'] = $room_id;
			$contract_data['source_id'] = $source_id;
			$contract_data['source'] = $source;
			$contract_data['custom_number'] = $custom_number;
			$contract_data['signing_time'] = $signing_time;
			$contract_data['occupancy_time'] = $occupancy_time;
			$contract_data['end_line'] = $end_line;
			$contract_data['rent'] = $rent;
			$contract_data['deposit'] = $deposit;
			$contract_data['detain'] = $deposit;
			$contract_data['pay'] = $pay;
			$contract_data['remark'] = $remark;
			$contract_data['tenant_data'] = $tenant_data;
			$result = $contractModel->editContract($contract_data, $contract_id);
			if ($result){
				return $this->returnAjax(array("status"=>1,"data"=>$result));
			}
			return $this->returnAjax(array("status"=>0,"data"=>false));
		}
	}
}