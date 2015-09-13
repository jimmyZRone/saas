<?php
namespace App\Web\Mvc\Model;
class Tenant extends Common
{
	/**
	 * 添加租客
	 * 修改时间2015年3月17日 16:52:50
	 * 
	 * @author yzx
	 * @param array $data
	 * @return number
	 */
	public function addTenant($data)
	{
		$new_tenant_id = $this->insert($data);
		return $new_tenant_id;
	}
	/**
	 * 修改租客
	 * 修改时间2015年3月17日 16:54:59
	 * 
	 * @author yzx
	 * @param array $data
	 * @param int $tenantId
	 * @return Ambigous <number, boolean>
	 */
	public function editTenant($data,$tenantId)
	{
		$tenant_data = $this->getOne(array("tenant_id"=>$tenantId));
		if (!empty($tenant_data))
		{
			$result = $this->edit(array("tenant_id"=>tenantId), $data);
			if ($result)
			{
				return true;
			}
			return false;
		}
		return false;
	}
}