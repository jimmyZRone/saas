<?php
namespace Common\Helper\Erp;
/**
 * 公司
 * @author lishengyou
 * 最后修改时间 2015年3月26日 上午10:54:10
 *
 */
class Company extends \Core\Object{
	/**
	 * 添加公司
	 * @author lishengyou
	 * 最后修改时间 2015年3月26日 上午10:54:48
	 *
	 * @param array $data
	 */
	public static function add(array $data){
		$info = array('company_name','pattern','linkman','telephone','safe_passwd');
		$infoData = array_intersect_key($data, array_fill_keys($info, false));
		$infoData = array_filter($infoData);
		if(count($infoData) != count($info)){
			self::setLastError('公司信息不完整');
			return false;
		}
		$infoData['safe_salt'] = \Common\Helper\String::rand(10,\Common\Helper\String::RAND_TYPE_NUMBER_LETTER);
		$infoData['safe_passwd'] = \Common\Helper\Encrypt::sha1($infoData['safe_salt'].$infoData['safe_passwd']);
		$infoData['diy_config_list'] = '';
		$infoData['diy_config_type'] = serialize(array());
		$infoData['diy_config_key'] = '';
		$infoData['company_diy_config'] = '';
		$model = new \Common\Model\Erp\Company();
		$res = $model->insert($infoData);
		if($res){//添加默认财务分类
			$defFeeType = explode('、','租金、押金、水费、电费、气费、物业费、服务费、维修费、保洁费、定金、欠费、网费');
			$feetype = new \Common\Model\Erp\FeeType();
			foreach ($defFeeType as $key => $value){
				$feetype->insert(array('type_name'=>$value,'company_id'=>$res,'sys_type_id'=>$key+1,'is_delete'=>0));
			}
		}
		return $res;
	}
	/**
	 * 验证冲账密码
	 * 修改时间2015年4月8日 15:15:19
	 *
	 * @author yzx
	 * @param int $user_id
	 * @param string $password
	 * @return boolean
	 */
	public static function checkPwd($user_id,$password)
	{
		$userModel = new \Common\Model\Erp\User();
		$user_data = $userModel->getOne(array("user_id"=>$user_id));
		if (!empty($user_data))
		{
			if (\Common\Helper\Encrypt::sha1($user_data['salt'].$password) === $user_data['password'])
			{
				return true;
			}
			return false;
		}
		return false;
	}
	/**
	 * 编辑冲账密码
	 * 修改时间2015年4月8日 15:34:23
	 * 
	 * @author yzx
	 * @param int $company_id
	 * @param string $pwd
	 * @return boolean
	 */
	public static function editPwd($company_id,$pwd)
	{
		$companyModel = new \Common\Model\Erp\Company();
		$company_data = $companyModel->getOne(array("company_id"=>$company_id));
		$infoData = array();
		$pwd=trim($pwd);
		if (isset($pwd) && $pwd!=null)
		{
			if (!empty($company_data)){
				$infoData['safe_salt'] = \Common\Helper\String::rand(5,\Common\Helper\String::RAND_TYPE_NUMBER_LETTER);
				$infoData['safe_passwd'] = \Common\Helper\Encrypt::sha1($infoData['safe_salt'].$pwd);
				$result = $companyModel->edit(array("company_id"=>$company_id) , $infoData);
				if ($result)
				{
					return true;
				}
				return false;
			}
			return false;
		}
		return false;
	}
	
}