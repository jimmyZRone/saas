<?php
namespace Common\Model\Erp;
use Zend\Db\Sql\Where;
class ReserveBackRental extends \Common\Model\Erp
{
	/**
	 * 分散式预约类型
	 */
	const DISPERSE_TYPE = 1;
	/**
	 * 集中式预约类型
	 */
	const CENTRALIZATION_TYPE = 2;
	/**
	 * 分散式房源类型
	 */
	const HOUSE_TYPE_HOUSE = 1;
	/**
	 * 分散式房间类型
	 * @var unknown
	 */
	const HOUSE_TYPE_ROOM = 2;
	/**
	 * 添加新预约
	 * 修改时间2015年3月30日 11:28:46
	 * 
	 * @author yzx
	 * @param array $data
	 * @return number
	 */
	public function addData($data)
	{
		$data['back_rental_time'] = strtotime($data['back_rental_time']);
		$data['creat_time'] = time();
		return  $this->insert($data);
	}
	/**
	 * 获取最新一条数据
	 * @param int $source_id
	 * @param int $type
	 * @param int $house_type
	 * @return multitype:multitype:Ambigous <number, unknown> number  Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >
	 */
	public function getDataBySourceId($source_id,$type,$house_type = 0)
	{
		$where = new Where();
		$where->equalTo("type", $type);
		$where->equalTo("house_type", $house_type);
		if (is_array($source_id)){
			$where->in("source_id",$source_id);
		}else {
			$where->equalTo("source_id", $source_id);
		}
		$select = $this->_sql_object->select("reserve_back_rental")
				  ->where($where)
				  ->order("creat_time desc");
		$result = $select->execute();
		$out_put_data = array();
		if (is_array($source_id)){
			foreach ($result as $key=>$val){
				$out_put_data[$val['source_id']] = $val;
				$out_put_data[$val['source_id']]['back_rental_time_c'] = date("Y/m/d",$val['back_rental_time']);
			}
		}else {
			$data = $result[0];
			$result[0]['back_rental_time_c'] = date("Y/m/d",$data['back_rental_time']);
			$out_put_data = $result;
		}
		return $out_put_data;
	}
}