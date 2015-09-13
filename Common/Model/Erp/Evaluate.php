<?php
namespace Common\Model\Erp;
use Core\Db\Sql\Select;
class Evaluate extends \Common\Model\Erp
{
	//每颗星分值
	const SCORD = 20;
	/**
	 * 添加租客评分
	 * 修改时间2015年3月18日 14:13:08
	 * 
	 * @author yzx
	 * @param array $data
	 * @return boolean
	 */
	public function addEvaluate($data)
	{
		$data['score'] = $data['score']*self::SCORD;
		$data['create_time'] = time();
		$result = $this->insert($data);
		return $result;
	}
	/**
	 * 查看一条租客全部评分
	 * 修改时间2015年3月18日 14:45:21
	 * 
	 * @author yzx
	 * @param int $tenantId
	 * @return multitype:Ambigous <boolean, multitype:, unknown> multitype:
	 */
	public function checkEvaluate($tenantId,$page,$size)
	{
		$tenantModel = new Tenant();
		$tenant_data = $tenantModel->getOne(array("tenant_id"=>$tenantId));
		$evaluate_list = $this->getTenantEvaluate($tenantId,$page,$size);
		if (!empty($tenant_data))
		{
			return array("tenant_data"=>$tenant_data,"evaluate_list"=>$evaluate_list);
		}
		return false;
	}
	/**
	 * 获取一条租客的所有评分
	 * 修改时间2015年3月18日 14:36:38
	 * 
	 * @author yzx
	 * @param int $tenantId
	 * @return array
	 */
	public function getTenantEvaluate($tenantId,$page,$size)
	{
		$select = $this->_sql_object->select(array("e"=>"evaluate"))
				  ->leftjoin(array("t"=>"tenant"),"e.tenant_id = t.tenant_id","*")
				  ->leftjoin(array('u'=>"user"),"e.user_id = u.user_id",array("username"))
				  ->leftjoin(array('c'=>"company"),"u.company_id = c.company_id",array("company_name"))
				  ->where(array("e.tenant_id"=>$tenantId));
		$result = $select->execute();
		Select::pageSelect($select, $page, $size);
		return $result;
	}
}