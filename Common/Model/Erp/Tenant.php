<?php
namespace Common\Model\Erp;
class Tenant extends \Common\Model\Erp
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
			$result = $this->edit(array("tenant_id"=>$tenantId), $data);
			if ($result)
			{
				return true;
			}
			return false;
		}
		return false;
	}
	/**
	 * 获取某个租客的所有信息
	 * @param unknown $tid
	 *
	 * @author too
	 * 最后修改时间 2015年4月15日 下午3:11:14
	 */
	public function getTenant($tid){
	    return $this->getOne(array("tenant_id"=>$tid));
	}
	/**
	 * 搜索已存在租客 (自己逻辑里用的)
	 * @param $data array
	 * @param $cid int 公司id
	 * @return 多维数组 $list
	 * @author too|最后修改时间 2015年4月27日 下午5:11:05
	 */
	public function searchTenant($data ,$cid ,$tenant_id=0){
	    if(!$tenant_id){
	        return false;
	    }
	    $select = new \Common\Model\Erp\Tenant();
	    $sql = $select->getSqlObject();
	    $select = $sql->select(array('t'=>$select->getTableName('tenant')));
	    $where = new \Zend\Db\Sql\Where();
        $where->equalTo('t.company_id', $cid);
        $where->equalTo('t.is_delete', 0);
	    if($tenant_id){
	        $where->equalTo('t.tenant_id', $tenant_id);
	    }
	    $select->where($where);
	    //print_r(str_replace('"', '', $select->getSqlString()));die();
	    $list = $select->execute();
	    return $list;
	}
	/**
	 * 通过身份证 检查租客是否存在
	 * @author too|编写注释时间 2015年5月6日 下午2:38:50
	 */
	public function checkTenantfromidcard($idcard){
	    return $this->getOne(array("idcard"=>$idcard));
	    /* $test = new Tenant();
	    $result = $test->searchTenant($data, $this->getUser()['company_id']);
	    if(empty($result)){
	        return $this->returnAjax(array('status'=>0,'tenant_data'=>array()));
	    }
	    return $this->returnAjax(array('status'=>1,'tenant_data'=>$result[0])); */
    }
}