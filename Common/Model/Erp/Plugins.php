<?php
namespace Common\Model\Erp;
use App\Web\Mvc\Model\Common;
class Plugins extends Common
{
	/**
	 * 获取第三方插件
	 * 修改时间2015年3月11日 15:52:29
	 * 
	 * @author yzx
	 * @param int $companyId
	 * @return array
	 */
	public function getThirdPlugins($companyId)
	{
		$company_id = $companyId;
		$select = $this->_sql_object->select(array("p"=>"plugins"))
				->join(array("pr"=>"plugins_relation"),"p.plugins_id=pr.plugins_id",array("company_id"),"left")
				->where(array("company_id"=>$company_id));
		$data = $select->execute();
// 		print_r(str_replace('"', "", $select->getSqlString()));die();
		return $data;
	}
	/**
	 * 获取全部插件
	 * 修改时间2015年3月11日 16:46:09
	 * 
	 * @author yzx
	 * @return array
	 */
	public function getAllPlugins()
	{
		$select = $this->_sql_object->select(array("p"=>"plugins"))
				->join(array("pr"=>"plugins_relation"),"p.plugins_id=pr.plugins_id",array("company_id"),"left");
		$data = $select->execute();
		return $data;
	}
	/**
	 * 获取插件公共配置
	 * 修改时间2015年3月12日 10:25:09
	 * 
	 * @author yzx
	 * @param string $dir
	 * @return multitype:
	 */
	public function getPluginConfig()
	{
		$system_path = ROOT_DIR."/Plugins/System/config.ini";
		$third_path = ROOT_DIR."/Plugins/Third/config.ini";
		$system_config = parse_ini_file($system_path,true);
		$third_config = parse_ini_file($third_path,true);
		if (!empty($system_config))
		{
			$system_str="";
			foreach ($system_config as $skey=>$sval)
			{
				$system_str.=$skey."=".$sval."\r";
			}
		}
		if (!empty($third_config))
		{
			$third_str="";
			foreach ($third_config as $tkey=>$tval)
			{
				$third_str.=$tkey."=".$tval."\r";
			}
		}
		return array("system_config"=>$system_str,"third_config"=>$third_str);
	}
	/**
	 * 审核插件
	 * @param int $pluginsId
	 * @return unknown
	 */
	public function auditorPlugins($pluginsId)
	{
		$select = $this->edit(array("plugins_id" => $pluginsId), array("is_auditor" => 1));
		$result = $select->execute();
		return $result;
	}
	/**
	 * 添加插件
	 * 修改时间2015年3月12日 11:18:59
	 * 
	 * @author yzx
	 * @param array $data
	 * @return number
	 */
	public function addPlugin($data,$config)
	{
		$data['create_time'] = time();
		$result = $this->insert($data);
		if ($result)
		{
			switch ($data['plugins_type'])
			{
				case "system":
					$this->writeConfig($config, "System");
				break;
				case "third":
					$this->writeConfig($config, "Third");
				break;
			}
		}
		return $result;
	}
	/**
	 * 修改插件公用配置
	 * @param string $config
	 * @param string $dir
	 */
	private function writeConfig($config,$dir)
	{
		$system_path = ROOT_DIR."Plugins/".$dir."/config.ini";
		$bruce = fopen($system_path, "w");
		if (!empty($config))
		{
			fwrite($bruce, $config);
		}
		fclose($bruce);
	}
}




































