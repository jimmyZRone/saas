<?php
namespace Common\Model\Erp;
use Core\Db\Sql\Select;
use Zend\Db\Sql\Where;
class StopHouse extends \Common\Model\Erp
{
	/**
	 * 合租
	 * @var unknown
	 */
	const HOUSE_TYPE_H = 2;
	/**
	 * 整租
	 * @var unknown
	 */
	const HOUSE_TYPE_Z = 1;
	/**
	 * 分散式预约类型
	 * @var unknown
	 */
	const DISPERSE_TYPE = 1;
	/**
	 * 集中式预约类型
	 * @var unknown
	 */
	const CENTRALIZATION_TYPE = 2;
	/**
	 * 添加停用信息
	 * 修改时间2015年3月30日 13:19:06
	 * 
	 * @author yzx
	 * @param array $data
	 * @return number
	 */
	public function add($data)
	{
		$data['start_time'] = strtotime($data['start_time']);
		$data['end_time'] = strtotime($data['end_time']);
		$data['create_time'] = time();
		return $this->insert($data);
	}
	/**
	 * 根据来源获取数据
	 * 修改时间2015年4月13日 16:53:50
	 * @author yzx
	 * 
	 * @param int $sourceId
	 * @param int $houseType
	 * @return Ambigous <boolean, multitype:, multitype:Ambigous <number, unknown> number , multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >
	 */
	public function getDataBySourceId($sourceId,$houseType,$isRoom=false)
	{	
		$select = $this->_sql_object->select($this->_table_name);
		$where = new Where();
		if (is_array($sourceId)){
			$where->in("source_id",$sourceId);
			$where->equalTo("type", $houseType);
		}else {
			$where->equalTo("source_id", $sourceId);
			$where->equalTo("type", $houseType);
		}
		$select->where($where);
		if ($isRoom)
		{
			$select->where(array("house_type"=>2));
		}else if($houseType==1 && $isRoom)
		{
			$select->where(array("house_type"=>1));
		}
		$select->order("create_time desc");
		$result = $select->execute();
		//print_r(str_replace('"', '', $select->getSqlString()));die();
		$out_put_data = array();
		if (is_array($sourceId)){
			//合租
			if ($isRoom){
				foreach ($result as $key=>$val){
					$out_put_data[$val["source_id"]]['start_time_c'] = date("Y-m-d",$val['start_time']);
					$out_put_data[$val['source_id']]['end_time_c'] = date("Y-m-d",$val['end_time']);
					$out_put_data[$val['source_id']]['remark'] = $val['remark'];
				}
			}
			//整租
			if (!$isRoom){
					foreach ($result as $key=>$val)
					{
						$out_put_data[$val["source_id"]]['start_time_c'] = date("Y-m-d",$val['start_time']);
						$out_put_data[$val['source_id']]['end_time_c'] = date("Y-m-d",$val['end_time']);
						$out_put_data[$val['source_id']]['remark'] = $val['remark'];
					}
				}
			
		}else {
			$data = $result[0];
			$result[0]['start_time_c'] = date("Y-m-d",$data['start_time']);
			$result[0]['end_time_c'] = date("Y-m-d",$data['end_time']);
			$result[0]['remark'] =$data['remark'];
			$out_put_data = $result[0];
		}
		return $out_put_data;
	}
}