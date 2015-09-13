<?php
namespace App\Web\Mvc\Model;
class House extends Common
{
	//专修中
	const STATUS_FITMENT = 4;
	//未出租
	const STATUS_NOT_RENT = 1;
	//已预定
	const STATUS_RESERVE = 1;
	//已出租
	const STATUS_RENT = 2;
	//房源类型
	public $_HOUSE_TYPE_HOUSE = 'house';
	public $_HOUSE_TYPE_ROOM = 'room'; 
	/**
	 * 添加分散房源
	 * 
	 * @author yzx
	 * @param array $data
	 * @return boolean|number
	 */
	
	public function addHouse($data,$user,$feeData=array())
	{
		$houseEntirelModel = new \Common\Model\Erp\HouseEntirel();
		$attachmentModel = new \Common\Model\Erp\Attachments(); 
		$feeHelper = new \Common\Helper\Erp\Fee();
		$data['house_name'] = $data['community_name'];
		$data['create_time'] = time();
		$data['owner_id'] = $user['user_id'];
		if(!empty($data['public_facilities'])){
			$public_facilities = explode(",", $data['public_facilities']);
			$data['public_facilities'] = implode("-", $public_facilities);
		}else{
			$data['public_facilities']='';
		}
		$this->Transaction();
		$new_house_id = $this->insert($data);
		if ($new_house_id) 
		{
			$data['house_id'] = $new_house_id;
			$house_entre_id = $houseEntirelModel->addEntirel($data);
		}else 
		{
			$this->rollback();
			return false;
		}
		if ($house_entre_id)
		{
			if ($data['public_pic']!=''){
				$data['img'] = explode(',', $data['public_pic']);
				if (is_array($data['img']) && !empty($data['img']))
				{
					foreach ($data['img'] as $key=>$val)
					{
						$img_data['key']=$val;
						$img_data['module']="house";
						$img_data['entity_id']=$new_house_id;
						$attachmentModel->insertData($img_data);
					}
				}
			}
			if (!empty($feeData))
			{
				$feeHelper->addFee($feeData,\Common\Model\Erp\Fee::SOURCE_DISPERSE,$new_house_id);
			}
			$this->commit();
			return $new_house_id;
		}
		$this->rollback();
		return false;
		
	}
	/**
	 * 编辑分散房源
	 * 修改时间2015年3月17日 10:27:39
	 * 
	 * @author yzx
	 * @param array $data
	 * @param int $house_id
	 * @return boolean
	 */
	public function editHouse($data,$house_id,$feeData=array())
	{
		$houseEntirelModel = new \Common\Model\Erp\HouseEntirel();
		$attachmentModel = new \Common\Model\Erp\Attachments();
		$feeHelper = new \Common\Helper\Erp\Fee();
		if ($house_id>0)
		{
			$house_entirel_data = $houseEntirelModel->getOne(array("house_id"=>$house_id));
			$house_data = $this->getOneHouseData($house_id);
			if (!empty($house_data))
			{
				$public_facilities = explode(",", $data['public_facilities']);
				$data['public_facilities'] = implode("-", $public_facilities);
				$this->Transaction();
				$result = $this->edit(array("house_id"=>$house_id), $data);
				if ($result)
				{
					if (empty($house_entirel_data))
					{
						$data['house_id'] = $house_id;
						
						$result = $houseEntirelModel->addEntirel($data);
						if ($result)
						{
							$this->commit();
							return true;
						}
						$this->rollback();
						return false;
					}
					if ($houseEntirelModel->editEntirel($data, $house_data['house_entirel_id'])){
						$attachmentModel->delete(array("module"=>"house","entity_id"=>$house_id));
						if ($data['public_pic']!=''){
							$data['img'] = explode(',', $data['public_pic']);
							if (is_array($data['img']) && !empty($data['img'])){
								foreach ($data['img'] as $key=>$val)
								{
									$img_data['key'] = $val;
									$img_data['module'] = "house";
									$img_data['entity_id'] = $house_id;
									$attachmentModel->insertData($img_data);
								}
							}
						}
						if (is_array($feeData))
						{
							$feeHelper->addFee($feeData,\Common\Model\Erp\Fee::SOURCE_DISPERSE,$house_id);
						}
						$this->commit();
						return true;
					}
					$this->rollback();
					return false;
				}
				$this->rollback();
				return false;
			}else 
			{
				return false;
			}
		}
		return false;
	}
	/**
	 * 获取房源信息以及附加信息
	 * 修改时间2015年3月17日 10:25:10
	 * 
	 * @author yzx
	 * @param int $house_id
	 * @return array|boolean
	 */
	public function getOneHouseData($house_id)
	{
		$select = $this->_sql_object->select(array("h"=>"house"))
				  ->leftjoin(array( "he"=>"house_entirel"),"h.house_id = he.house_id",
				  				array("house_entirel_id","money","status","occupancy_number","exist_occupancy_number","gender_restrictions"))
				  ->where(array("h.house_id"=>$house_id));
		$house_data = $select->execute();
		if (!empty($house_data))
		{
			return $house_data[0];
		}
		return false;
	}
	/**
	 * 获取房源所有房间
	 * 修改时间2015年3月18日 16:46:08
	 * 
	 * @author yzx
	 * @param int $house_id
	 * @return array
	 */
	public function getHouseRoom($house_id)
	{
		$select = $this->_sql_object->select(array("r"=>"room"))
				  ->leftjoin(array("h"=>"house"),"r.house_id = h.house_id",array("house_name"))
				  ->where(array("r.house_id"=>$house_id));
		$result = $select->execute();
		if (!empty($result))
		{
			return $result;
		}
		return false;
	}
	/**
	 * 获取房源租客
	 * 修改时间2015年3月19日 11:19:26
	 * 
	 * @author yzx
	 * @param int $house_id
	 * @param string $house_type
	 * @return Ambigous <multitype:, boolean, unknown>|multitype:
	 */
	public function getHoueRental($house_id,$house_type)
	{
		$rentalModel = new Rental();
		$house_data = $rentalModel->getTenantByHouseType($house_id, $house_type);
		if ($house_data)
		{
			return $house_data;
		}
		return array();
	}
}