<?php
namespace Common\Helper\Erp;
class Keeper extends \Core\Object
{
	/**
	 * 获取主帐号下管家
	 * 修改时间2015年4月28日 10:01:40
	 * 
	 * @author yzx
	 * @param array $user
	 * @return Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >
	 */
	public function getKeeper($user)
	{
		$userModel = new \Common\Model\Erp\User();
		$sql = $userModel->getSqlObject();
		$select = $sql->select(array("u"=>$userModel->getTableName()));
		$select->leftjoin(array("ue"=>"user_extend"), "u.user_id=ue.user_id");
		$select->where(array("company_id"=>$user['company_id'],"is_manager"=>0));
		$result = $select->execute();
		return $result;
	}	
}
