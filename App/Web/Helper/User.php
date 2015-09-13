<?php
namespace App\Web\Helper;
use Zend\Db\Sql\Expression;
class User extends \Common\Helper\Erp\User{
	/**
	 * 获取职员列表
	 *  最后修改时间 2015-3-19
	 *
	 * @author dengshuang
	 * @return unknown|multitype:
	 */
	public function getSatffList(array $searchKey,$page,$size){
		$user = $this->getCurrentUser();
		$model = new \Common\Model\Erp\User();
		$sql = $model->getSqlObject();
		$select = $sql->select(array('u'=>$model->getTableName()));
		//$select->columns($columns)
		$select->leftjoin(array('ue'=>'user_extend'),"u.user_id = ue.user_id",array('staffname'=>'name','contact','gender','birthday'));
		$select->leftjoin(array('ug'=>'user_group'),"u.user_id = ug.user_id");
		$select->leftjoin(array('g'=>'group'),"ug.group_id = g.group_id",array('groupname'=>'name'));
		$select->leftjoin(array('c'=>'company'),'c.company_id = u.company_id',array("company_name"));
		$where = new \Zend\Db\Sql\Where();
		if(isset($searchKey['company_id']) && !empty($searchKey['company_id'])){
		    $where->equalTo('u.company_id', $searchKey['company_id']);
		}
		if(isset($searchKey['contant']) && !empty($searchKey['contant'])){
			$where->like('ue.contant', "%$searchKey[contant]%");
		}
		$where->equalTo('u.is_manager', 0);
		if (isset($searchKey['name']) && !empty($searchKey['name']))
		{
			$like = $this->likeFactory("%$searchKey[name]%");//添加搜索条件
			$where->addPredicate($like,\Zend\Db\Sql\Where::OP_AND);
		}
		$select->where($where);
		$select->order('ue.user_id desc');
		//print_r(str_replace('"', '', $select->getSqlString()));die();
		$result = \Core\Db\Sql\Select::pageSelect($select,null,$page, $size);
		$count_data = $this->countUserHouse($result);
		$data = $result['data'];
		foreach ($data as $key=>$val){
			foreach ($count_data['house_data'] as $ckey=>$cval){
				if ($val['user_id'] == $cval['user_id']){
					$data[$key]['house_data'] = $cval['house_count'];
				}
			}
			foreach ($count_data['flat_data'] as $fkey=>$fval){
				if ($val['user_id'] == $fval['user_id']){
					$data[$key]['flat_data'] = $fval['flat_count'];
				}
			}
			foreach ($count_data['community_data'] as $cikey=>$cival){
				if ($val['user_id'] == $cival['user_id']){
					$data[$key]['community_count'] = $cival['community_count'];
				}
			}
			foreach ($count_data['all_house_data'] as $alikey=>$alival){
				if ($val['user_id'] == $alival['user_id']){
					$data[$key]['all_house_count'] = $alival['all_house_count'];
				}
			}
			foreach ($count_data['all_room_data'] as $arikey=>$arival){
				if ($val['user_id'] == $arival['user_id']){
					$data[$key]['all_room_count'] = $arival['all_room_count'];
				}
			}
			foreach ($count_data['room_focus_data'] as $rfikey=>$rfival){
				if ($val['user_id'] == $rfival['user_id']){
					$data[$key]['room_focus_count'] = $rfival['room_focus_count'];
				}
			}
		}
		$result['data'] = $data;
		if($result){
			return $result;
		}else{
			return array();
		}
	}
	/**
	 * 添加搜索员工条件
	 * 修改时间2015年3月23日 14:33:53
	 *
	 * @author yzx
	 * @param string $searchKey
	 * @return \Zend\Db\Sql\Where
	 */
	protected function  likeFactory($searchKey)
	{
		$where_name = new \Zend\Db\Sql\Where();
		$where_name->like("ue.name", $searchKey);
		$where_contact = new \Zend\Db\Sql\Where();
		$where_contact->like("ue.contact", $searchKey);
		$where = new \Zend\Db\Sql\Where();
		return $where->addPredicates(array($where_name,$where_contact),\Zend\Db\Sql\Where::OP_OR);
	}

	/**
	 * 获取单个用户所有信息，user,user_extend,user_group
	 * @param int $uid
	 * @return array $data
	 * @author too
	 * 最后修改时间 2015年4月13日 上午11:15:01
	 */
	public function getOne($uid){
	    $param = array('user_id'=>$uid);
	    $user = new \Common\Model\Erp\User();
	    $data1 = $user->getOne($param);
	    $userex = new \Common\Model\Erp\UserExtend();
	    $data2 = $userex->getOne($param);
	    $data2['uname'] = $data2['name'];
	    //print_r($data2);
	    $userGp = new \Common\Model\Erp\UserGroup();
	    $data3 = $userGp->getOne($param);
	    //print_r($data3);
	    $group = new \Common\Model\Erp\Group();
	    $data4 = $group->getOne(array('group_id'=>$data3['group_id']));
	    $data = array_merge($data3,$data2,$data1,$data4);
	    //print_r($data);
	    return $data;
	}

	/**
	 * 按城市首字母分类
	 * @author too|编写注释时间 2015年6月13日 上午10:35:17
	 */
	public function cityTree($param)
	{
	    $result_city = array();
	    foreach($param as $k=>$v)
	    {
	        $temp = substr($v['shorthand'],0,1);
	        switch ($temp)
	        {
                case 'z':
                    $result_city['Z'][$k] = $v;
                    break;
                case 'y':
                    $result_city['Y'][$k] = $v;
                    break;
                case 'x':
                    $result_city['X'][$k] = $v;
                    break;
                case 'w':
                    $result_city['W'][$k] = $v;
                    break;
                case 't':
                    $result_city['T'][$k] = $v;
                    break;
                case 's':
                    $result_city['S'][$k] = $v;
                    break;
                case 'r':
                    $result_city['R'][$k] = $v;
                    break;
                case 'q':
                    $result_city['Q'][$k] = $v;
                    break;
                case 'p':
                    $result_city['P'][$k] = $v;
                    break;
                case 'n':
                    $result_city['N'][$k] = $v;
                    break;
                case 'm':
                    $result_city['M'][$k] = $v;
                    break;
                case 'l':
                    $result_city['L'][$k] = $v;
                    break;
                case 'k':
                    $result_city['K'][$k] = $v;
                    break;
                case 'j':
                    $result_city['J'][$k] = $v;
                    break;
                case 'h':
                    $result_city['H'][$k] = $v;
                    break;
                case 'g':
                    $result_city['G'][$k] = $v;
                    break;
                case 'f':
                    $result_city['F'][$k] = $v;
                    break;
                case 'e':
                    $result_city['E'][$k] = $v;
                    break;
                case 'd':
                    $result_city['D'][$k] = $v;
                    break;
                case 'c':
                    $result_city['C'][$k] = $v;
                    break;
                case 'b':
                    $result_city['B'][$k] = $v;
                    break;
                case 'a':
                    $result_city['A'][$k] = $v;
                    break;
	        }
	    }
        ksort($result_city);
	    return $result_city;
	}
	/**
	 * 统计用户管理数据
	 * @param unknown $data
	 */
	public function countUserHouse($data){
		$user_id = array();
		$HousingDistributionModel = new \Common\Model\Erp\HousingDistribution();
	  if (is_array($data['data']) && !empty($data['data'])){
	  	foreach ($data['data'] as $key=>$val){
	  		$user_id[] = $val['user_id'];
	  	}
	  }	
	  if (!empty($user_id)){
	  	//多少房源
	  	$sql = $HousingDistributionModel->getSqlObject();
	  	$select = $sql->select($HousingDistributionModel->getTableName());
	  	$select->columns(array("house_count"=>new Expression("count(*)"),"user_id"));
	  	$select->where(array("source"=>\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE));
	  	$select->where(array("user_id"=>$user_id));
	  	$select->group("user_id");
	  	$house_data = $select->execute();
	  	//多少小区
	  	$c_select = $sql->select(array('hd'=>$HousingDistributionModel->getTableName()));
	  	$c_select->columns(array("community_count"=>new Expression("count(DISTINCT h.community_id)"),"user_id"));
	  	$c_select->leftjoin(array("h"=>"house"), "hd.source_id = h.house_id");
	  	$c_select->where(array("source"=>\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE));
	  	$c_select->where(array("user_id"=>$user_id));
	  	$c_select->group("user_id");
	  	$community_data = $c_select->execute();
	  	//整租多少
	  	$h_select = $sql->select(array('hd'=>$HousingDistributionModel->getTableName()));
	  	$h_select->columns(array("all_house_count"=>new Expression("count(DISTINCT h.house_id)"),"user_id"));
	  	$h_select->leftjoin(array("h"=>"house"), "hd.source_id = h.house_id");
	  	$h_select->where(array("source"=>\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE));
	  	$h_select->where(array("rental_way"=>\Common\Model\Erp\House::RENTAL_WAY_Z));
	  	$h_select->where(array("user_id"=>$user_id));
	  	$h_select->group("user_id");
	  	$all_house_data = $h_select->execute();
	  	//合租多少
	  	$a_select = $sql->select(array('hd'=>$HousingDistributionModel->getTableName()));
	  	$a_select->columns(array("all_room_count"=>new Expression("count(DISTINCT h.house_id)"),"user_id"));
	  	$a_select->leftjoin(array("h"=>"house"), "hd.source_id = h.house_id");
	  	$a_select->where(array("source"=>\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE));
	  	$a_select->where(array("rental_way"=>\Common\Model\Erp\House::RENTAL_WAY_H));
	  	$a_select->where(array("user_id"=>$user_id));
	  	$a_select->group("user_id");
	  	$all_room_data = $a_select->execute();
	  }
	  if (!empty($user_id)){
	  	$sql = $HousingDistributionModel->getSqlObject();
	  	$select = $sql->select($HousingDistributionModel->getTableName());
	  	$select->columns(array("flat_count"=>new Expression("count(*)"),"user_id"));
	  	$select->where(array("source"=>\Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED));
	  	$select->where(array("user_id"=>$user_id));
	  	$select->group("user_id");
	  	$flat_data = $select->execute();
	  	//总共多少房间
	  	$sql = $HousingDistributionModel->getSqlObject();
	  	$r_select = $sql->select(array("hd"=>$HousingDistributionModel->getTableName()));
	  	$r_select->leftjoin(array("rf"=>'room_focus'), "hd.source_id = rf.flat_id");
	  	$r_select->columns(array("room_focus_count"=>new Expression("count(rf.flat_id)"),"user_id"));
	  	$r_select->where(array("source"=>\Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED));
	  	$r_select->where(array("user_id"=>$user_id));
	  	$r_select->group("user_id");
	  	$room_focus_data = $r_select->execute();
	  }
	  return array("house_data"=>$house_data,
	  			   "flat_data"=>$flat_data,
	  			   "community_data"=>$community_data,
	  			   "all_house_data"=>$all_house_data,
	  			   "all_room_data"=>$all_room_data,
	  			   "room_focus_data"=>$room_focus_data);
	}
}