<?php
namespace Common\Helper\Erp;
/**
 * 房源分配
 * @author lishengyou
 * 最后修改时间 2015年6月15日 下午6:18:57
 *
 */
class HousingDistribution
{
	/**
	 * 取得需要分配的列表，并且取得已经分配的信息
	 * @author lishengyou
	 * 最后修改时间 2015年6月15日 下午6:20:53
	 *
	 * @param unknown $source
	 * @param unknown $source_type_id
	 * @param unknown $company_id
	 */
	public function getListJoinSource($source,$city_id,$source_type_id,$company_id){
		$model = new \Common\Model\Erp\HousingDistribution();
		$sql = $model->getSqlObject();
		if($source == 0){//集中式，$source_type_id为城市编号
			$select = $sql->select(array('flat'=>'flat'));
			$select->join(array('area'=>'area'), 'area.area_id=flat.area_id','area_id');
			$where = new \Zend\Db\Sql\Where();
			$where->equalTo('flat.is_delete', 0);
			$where->equalTo('flat.company_id', $company_id);
			$where->equalTo('area.city_id', $source_type_id);
			$select->where($where);
			$data = $select->execute();
			if(!$data){
				return array();
			}
			$flats = array_column($data, 'flat_id');
			$select = $sql->select(array('hd'=>'housing_distribution'));
			$select->columns(array('hd_id'=>'distribution_id','source_id'));
			$select->join(array('ue'=>'user_extend'), 'hd.user_id=ue.user_id',array('user_name'=>'name'));
			$select->join(array('u'=>'user'), 'u.user_id=ue.user_id',array('hd_user_id'=>'user_id'));
			$where = new \Zend\Db\Sql\Where();
			$where->equalTo('hd.source', 2);
			$where->equalTo('u.company_id', $company_id);
			$where->equalTo('u.is_manager', 0);
			$where->in('hd.source_id',$flats);
			$select->where($where);
			$exData = $select->execute();
			$temp = array();
			foreach ($exData as $value){
				if(!isset($temp[$value['source_id']])){
					$temp[$value['source_id']] = array();
				}
				$temp[$value['source_id']][$value['hd_user_id']] = $value;
			}
			$exData = $temp;$temp = array();
			foreach ($data as $key => $value){
				$value['distribution'] = array();
				$temp[$value['flat_id']] = $value;
				if(isset($exData[$value['flat_id']])){
					$temp[$value['flat_id']]['distribution'] = $exData[$value['flat_id']];
				}
			}
			return $temp;
		}else if($source == 1){//分散式，$source_type_id为区域编号
			$select = $sql->select(array('h'=>'house'));
			$select->join(array('c'=>'community'), 'h.community_id=c.community_id','community_name');
			$where = new \Zend\Db\Sql\Where();
			$where->equalTo('h.is_delete', 0);
			$where->equalTo('h.company_id', $company_id);
			$where->equalTo('c.city_id', $city_id);
			if($source_type_id){
				$where->equalTo('c.area_id', $source_type_id);
			}
			$select->where($where);
			$data = $select->execute();
			if(!$data){
				return array();
			}
			$houseIds = array_column($data, 'house_id');
			if($houseIds){
				$select = $sql->select(array('hd'=>'housing_distribution'));
				$select->columns(array('hd_id'=>'distribution_id','source_id','user_id'));
				$where = new \Zend\Db\Sql\Where();
				$where->equalTo('hd.source', \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
				$where->in('hd.source_id',$houseIds);
				$select->where($where);
				$exData = $select->execute();
				if($exData){
					$userIds = array_column($exData, 'user_id');
					$userIds = array_unique($userIds);
					$select = $sql->select(array('u'=>'user'));
					$select->join(array('ue'=>'user_extend'), 'u.user_id=ue.user_id',array('user_name'=>'name'));
					$select->where(array('u.user_id'=>$userIds,'u.company_id'=>$company_id,'u.is_manager'=>0));
					$userData = $select->execute();
					$userData = array_combine(array_column($userData, 'user_id'), $userData);
					$temp = array();
					foreach ($exData as $value){
						if(isset($userData[$value['user_id']])){
							$value['user_name'] = $userData[$value['user_id']]['user_name'];
							$value['hd_user_id'] = $userData[$value['user_id']]['user_id'];
							$temp[] = $value;
						}
					}
					$exData = $temp;$temp = array();
				}
				$temp = array();
				foreach ($exData as $value){
					if(!isset($temp[$value['source_id']])){
						$temp[$value['source_id']] = array();
					}
					$temp[$value['source_id']][$value['hd_user_id']] = $value;
				}
				$exData = $temp;$temp = array();
				foreach ($data as $key => $value){
					$value['distribution'] = array();
					if(isset($exData[$value['house_id']])){
						$value['distribution'] = $exData[$value['house_id']];
					}
					$data[$key] = $value;
					
				}
			}
			$temp = array();
			foreach ($data as $value){
				if(!isset($temp[$value['community_id']])){
					$temp[$value['community_id']] = array(
							'community_id' => $value['community_id'],
							'community_name' => $value['community_name'],
							'house_list' => array()
					);
				}
				$value['distribution'] = isset($value['distribution']) ? $value['distribution'] : array();
				$temp[$value['community_id']]['house_list'][$value['house_id']] = array_intersect_key($value, array('house_id'=>false,'house_name'=>false,'distribution'=>false));
			}
			return $temp;
		}
	}
}