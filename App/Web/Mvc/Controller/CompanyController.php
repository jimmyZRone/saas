<?php
namespace App\Web\Mvc\Controller;
use App\Web\Lib\Request;
class CompanyController extends \App\Web\Lib\Controller
{
	/**
	 * 添加公司配置
	 * 修改时间2015年5月12日 11:36:06
	 * 
	 * @author yzx
	 */
	public function addconfigAction()
	{
		if (Request::isPost())
		{
			$end_key = array();
			$companyModel = new \Common\Model\Erp\Company();
			$sysConfig = new \Common\Model\Erp\SystemConfig();
			$configStr = I("post.config_str");
			$public_facilities = $sysConfig->getFind("House", "public_facilities");
			if (is_array($configStr) && !empty($configStr))
			{
				foreach ($configStr as $key=>$var)
				{
					if (strlen($var)>20){
						return  $this->returnAjax(array("status"=>0,"data"=>"不能超过20个字符"));
					}
					if (in_array($var, $public_facilities))
					{
						continue;
					}
					array_push($public_facilities,$var);
					$companyModel->set(array("key"=>"House/public_facilities","value"=>$public_facilities),$this->user);
					
					$public_facilities = $sysConfig->getFind("House", "public_facilities");
					$public_facilities_key = array_keys($public_facilities);
					$end_key[$key]['key'] = end($public_facilities_key);
					$end_key[$key]['val'] = $var;
				}
			}
			
			return $this->returnAjax(array("status"=>1,"data"=>$end_key));
		}
		return  $this->returnAjax(array("status"=>0,"data"=>false));
	}
	/**
	 * 删除公司配置
	 * 修改时间2015年5月12日 13:29:28
	 * 
	 * @author yzx
	 * @param int $configId
	 * @return boolean
	 */
	public function deleteconfigAction()
	{
		if (Request::isPost())
		{
			$sysConfig = new \Common\Model\Erp\SystemConfig();
			$companyModel = new \Common\Model\Erp\Company();
			$configId = I("post.config_id",0,'int');
			$public_facilities = $sysConfig->getFind("House", "public_facilities");
			$map = array('阳台','飘窗','卫生间');
			if (in_array($public_facilities[$configId], $map)){
				return $this->returnAjax(array("status"=>0,"data"=>$public_facilities[$configId]."不能删除"));
			}
			unset($public_facilities[$configId]);
			$result =  $companyModel->set(array("key"=>"House/public_facilities","value"=>$public_facilities),$this->user);
			if ($result)
			{
				return $this->returnAjax(array("status"=>1,"data"=>"删除成功"));
			}
			return $this->returnAjax(array("status"=>0,"data"=>"删除失败"));
		}
		return $this->returnAjax(array("status"=>0,"data"=>"删除失败"));
	}
}