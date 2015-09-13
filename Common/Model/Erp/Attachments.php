<?php
/**
 * 附件模型类
 */
namespace Common\Model\Erp;
class Attachments extends \Common\Model\Erp
{
	/**
	 *  添加单一图片
	 *	最后修改时间2015年4月22日 10:37:15
	 * 	添加单一图片
	 *
	 * @param array $data
	 * @author yzx
	 * @return int
	 */
	public function insertData($data){
		if (!isset($data['bucket']))
		{
			$data['bucket'] = $this->getDefaultBucket();
		}
		$results = $this->insert($data);
		return $results;
	}

	/**
	 * 添加多张图片
	 *	最后修改时间 2014-11-06
	 * 	添加多张图片
	 *
	 * @param array $data
	 * @author dengshuang
	 * @return int
	 */
	public function insertMore($data,$conditon = array()){
// 		print_r($data);die;
		if(!empty($data)&&is_array($data)){
			if(!empty($conditon)){
				$this->delete($conditon);
			}
			foreach ($data as $k => $v){
				if(empty($v['bucket'])){
					$data[$k]['bucket'] = $this->getDefaultBucket();
				}
				//print_r($v);
				$this->insert($v);
			}
		}
		return true;
	}

	/**
	 * 删除图片
	 *	最后修改时间 2014-11-05
	 * 	删除图片
	 *
	 * @param array $house_id
	 * @author dengshuang
	 * @return bool
	 */
	public function delete($condition){
		$delete = $this->_sql_object->delete($this->_table_name);
		$delete->where($condition);
		return $delete->execute();
	}


	/**
	 * 获取单个房源的图片
	 *	最后修改时间 2014-11-05
	 * 	根据id或者其他条件来获取单条房源信息
	 *
	 * @param array/int $condition
	 * @author dengshuang
	 * @return array
	 */
	public function queryCacheHouseImage($key,$entity_id){
		return $this->_getImage('HouseImage',$key,$entity_id);
	}

	/**
	 * 获取单个房间的图片
	 *	最后修改时间 2014-11-06
	 * 	根据id来获取单条房间的所有图片
	 *
	 * @param array/int $condition
	 * @author dengshuang
	 * @return array
	 */
	public function queryCacheRoomImage($key,$values){
		return $this->_getImage('RoomImage',$key,$values);
	}
	/**
	 * 成员函数说明
	 * 根据条件获取图片列表
	 * 最后修改时间2014-11-18 17:31:03
	 *
	 * @author yzx
	 * @param string $module
	 * @param int $entityId
	 * @param int $bucket
	 * @return array
	 */
	public function getImagList($module,$entityId,$bucket=false)
	{
		$select=$this->_sql_object->select($this->_table_name);
		$select->where(array('module'=>$module));
		$select->where(array('entity_id'=>$entityId));
		return $select->execute();
	}
	/**
	 * 取得默认的Bucket
	 * @author lishengyou
	 * 最后修改时间 2014年11月27日 下午3:35:32
	 *
	 */
	public function getDefaultBucket(){
		return 'jooozo-erp';
	}
	/**
	 * 获取图片
	 *	最后修改时间 2014-11-06
	 * 	根据模块获取图片
	 *
	 * @param array/int $condition
	 * @author dengshuang
	 * @return array
	 */
	public function _getImage($module,$key,$entity_id){
		if($module == 'HouseImage'){
			$where = array('entity_id'=>$entity_id,'module'=>'house');
		}else{
			$where = array('entity_id'=>$entity_id,'module'=>'room');
		}
		if(is_numeric($entity_id)){
			$condtion = $where;
		}else{
			return false;
		}
		$select = $this->_sql_object->select($this->_table_name);
		$select->where($condtion);
		$results = $select->execute();
// 		echo $select->getSqlString();die;
// 		var_dump($results);die;
		$results = array($results);
		$results[0][$module.'_id'] = $entity_id;
		return $results;
	}
        /**
         * 获取所有房源的图片
         * @author too
         */
        public function getAllPic($type,$id)
        {
            $where = array('module'=>$type,'entity_id'=>$id);
            return $this->getData($where);
        }
        /**
         * 删除一个合同下的所有图片
         * @author too|编写注释时间 2015年6月1日 下午5:59:08
         */
        public function delAllPic($type,$id)
        {
            return $this->delete(array('module'=>$type,'entity_id'=>$id));
        }
}


