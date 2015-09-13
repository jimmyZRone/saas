<?php
namespace Common\Helper\Erp;

use Common\Model\Erp\UserExtend;
class UserExtend extends \Core\Object
{
	public function edit($userId,$data)
	{
		$userExtendModel = new UserExtend();
		$extend_data = $userExtendModel->getOne(array("user_id"=>$userId));
		if (empty(!$extend_data))
		{
			$result = $userExtendModel->edit(array("user_id"=>$userId), $data);
			if ($result)
			{
				return true;
			}
		}
	}
	public function addExtend($data)
	{
		
	}
	/**
	 * 修改session城市值
	 * 修改时间2015年5月30日 16:59:49
	 * 
	 * @author yzx
	 * @param int $cityId
	 * @return Ambigous <number, boolean>
	 */
	public function updataSession($cityId){
		$userHelper = new User();
		$model = new \Common\Model\Erp\ErpinterfaceSession();
		$session_id = $userHelper->getSessionId();
		$user_data = $userHelper->getCurrentUser();
		$user_data['city_id'] = $cityId;
		$input_data = serialize($user_data);
		return $model->edit(array('session_id'=>$session_id), array("value"=>$input_data));
	}
}