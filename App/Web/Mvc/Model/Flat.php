<?php
namespace App\Web\Mvc\Model;
use Core\Db\Sql\Select;
use Zend\Db\Sql\Where;
class Flat extends Common
{
	const RENTAL_WAY_INTEGRAL = 1;
	const RENTAL_WAY_CLOSE = 2;
	public function getFlatData($flat_id)
	{
		$result = $this->getOne(array("flat_id"=>$flat_id));
		return $result;
	}
	/**
	 * 获取公寓列表
	 * @param int $pageSize
	 * @param int $page
	 * @return multitype:multitype:Ambigous <number, unknown> number  Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >
	 */
	public function flatList($page,$pageSize=10)
	{
		$select = $this->_sql_object->select("flat");
		return Select::pageSelect($select, $page, $pageSize);
	}
	/**
	 * 添加公寓
	 * 修改时间2015年3月24日 10:09:32
	 * 
	 * @author yzx
	 * @param array $data
	 * @return number
	 */
	public function addFlat($data)
	{
		$floorNumberModel = new FloorNumber();
		$roomTemplatRelationModel = new RoomTemplateRelation();
		$this->Transaction();
		$new_flat_id = $this->insert($data);
		if (!$new_flat_id)
		{
			$this->rollback();
			return false;
		}
		$data['flat_id'] = $new_flat_id;
		$fresult = $floorNumberModel->addFoolNumber($data);
		if (!$fresult)
		{
			$this->rollback();
			return false;
		}
		if ($data['rental_way'] == self::RENTAL_WAY_INTEGRAL)
		{
			$RTR_RESULT = $roomTemplatRelationModel->houseFactory($data['all_house']);
		}else 
		{
			$RTR_RESULT = $roomTemplatRelationModel->houseFactory($data['all_house'],false);
		}
		if (!$RTR_RESULT)
		{
			$this->rollback();
			return false;
		}
		$this->commit();
		return $new_flat_id;
	}
	/**
	 * 修改公寓
	 * 修改时间2015年3月25日 16:42:01
	 * 
	 * @author yzx
	 * @param int $flatId
	 * @param array $data
	 * @return Ambigous <number, boolean>
	 */
	public function editFlat($flatId,$data)
	{
		return $this->edit(array('flat_id'=>$flatId), $data);
	}
}