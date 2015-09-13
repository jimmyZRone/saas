<?php
namespace Common\Model\Erp;

use Core\Db\Sql\Select;
use Zend\Db\Sql\Where;
class Reserve extends \Common\Model\Erp
{
	/**
	 * 分散式类型
	 * @var unknown
	 */
	const HOUSE_TYPE_R=1;
	/**
	 * 集中式类型
	 * @var unknown
	 */
	const HOUSE_TYPE_F=2;
	/**
	 * 租客类型
	 * @var unknown
	 */
	//分散式
	const HOUSE_TYPE = 1;
	//集中式
	const HOUSE_TYPE_FOCUS = 2;
	/**
	 * 合租
	 * @var unknown
	 */
	const RENTAL_WAY_H = 1;
	/**
	 * 整租
	 * @var unknown
	 */
	const RENTAL_WAY_Z = 2;
	/**
	 * 获取最近一个预定人
	 * 修改时间2015年4月13日 17:36:32
	 *
	 * @author yzx
	 * @param unknown $house_type
	 * @param unknown $house_id
	 * @param string $is_room
	 * @return Ambigous <multitype:Ambigous <number, unknown> number , Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >>
	 */
	public function getFirst($house_type,$house_id,$is_room=false)
	{
		$source_name_map = array(1=>'网络渠道',2=>'客户介绍',3=>'自来客',4=>"会员",5=>'其他');
		$where = new Where();
		$where->equalTo("r.house_type", $house_type);
		$where->equalTo("r.is_delete", 0);
		$select = $this->_sql_object->select(array("r"=>$this->_table_name));
		$select->leftjoin(array('t'=>'tenant'),"r.tenant_id = t.tenant_id",array("name","phone"));
		if ($house_type==self::HOUSE_TYPE_R && $is_room)
		{
			if (is_array($house_id)){
				$where->in("r.room_id",$house_id);
			}else {
				$where->equalTo("r.room_id", $house_id);
			}
		}elseif($house_type==self::HOUSE_TYPE_R && !$is_room)
		{
			if (is_array($house_id)){
				$where->in("r.house_id",$house_id);
			}else {
				$where->equalTo("r.house_id", $house_id);
			}
		}else
		{
			$where->equalTo("r.house_id", $house_id);
		}
		$select->where($where);
		$select->order("r.create_time desc");
		//print_r(str_replace('"', '', $select->getSqlString()));die();
		$result = $select->execute();
		$out_put_data = array();
		//合租
		if ($house_type==self::HOUSE_TYPE_R && $is_room && is_array($house_id)){
			foreach ($result as $key=>$val){
				$out_put_data[$val['room_id']] = $val;
				$out_put_data[$val['room_id']]['stime_c'] = date("Y/m/d",$val['stime']);
				$out_put_data[$val['room_id']]['etime_c'] = date("Y/m/d",$val['etime']);
				if ($val['source']<=0){
					$out_put_data[$val['room_id']]['source_name'] = "未设置";
				}else {
					$out_put_data[$val['room_id']]['source_name'] = $source_name_map[$val['source']];
				}
			}
		}
		//整租
		if ($house_type==self::HOUSE_TYPE_R && !$is_room && is_array($house_id)){
			foreach ($result as $key=>$val){
				$out_put_data[$val['house_id']] = $val;
				$out_put_data[$val['house_id']]['stime_c'] = date("Y/m/d",$val['stime']);
				$out_put_data[$val['house_id']]['etime_c'] = date("Y/m/d",$val['etime']);
				if ($val['source']<=0){
					$out_put_data[$val['house_id']]['source_name'] = "未设置";
				}else {
					$out_put_data[$val['house_id']]['source_name'] = $source_name_map[$val['source']];
				}
			}
		}
		if (!is_array($house_id)){
			if (!empty($result))
			{
				$data = $result[0];
				$result[0]['stime_c'] = date("Y/m/d",$data['stime']);
				$result[0]['etime_c'] = date("Y/m/d",$data['etime']);
				if ($val['source']<=0){
					$result[0]['source_name']="未设置";
				}else {
					$result[0]['source_name'] = $source_name_map[$data['source']];
				}
				$out_put_data = $result;
			}
		}
		return $out_put_data;
	}
}