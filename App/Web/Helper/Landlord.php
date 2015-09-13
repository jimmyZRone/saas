<?php
namespace App\Web\Helper;
use Core\Db\Sql\Select;
use Zend\Db\Sql\Expression;
class Landlord{
	/**
	 * 取得列表
	 * @author lishengyou
	 * 最后修改时间 2015年3月26日 下午3:56:07
	 *
	 * @param unknown $user
	 * @param unknown $houseName
	 * @param unknown $landlordName
	 * @param unknown $phoneName
	 * @param unknown $page
	 * @param unknown $pagesize
	 * @return multitype:multitype:Ambigous <number, unknown> number  Ambigous <boolean, multitype:, multitype:unknown NULL > |multitype:
	 */
	public function getLandlordList($user,$houseName,$landlordName,$phoneName,$page,$pagesize){
		$landlordModel = new \Common\Model\Erp\Landlord();
		$select = $landlordModel->getSqlObject()->select(array('l'=>$this->_table_name));
		$select->leftjoin(array('lc'=>'landlord_contract'),"lc.landlord_id = l.landlord_id");

		$where = new \Zend\Db\Sql\Where();
		$where->equalTo('l.create_user_id', $user['user_id']);
		if(!empty($houseName)){
			$where->like('l.house_name', "%".$houseName."%");
		}
		if(!empty($landlordName)){
			$where->like('l.name', "%".$landlordName."%");
		}
		if(!empty($phoneName)){
			$where->like('l.phone', "%".$phoneName."%");
		}
		$select->where($where);
		$result = Select::pageSelect($select, null, $page, $pagesize);
		if($result){
			return $result;
		}else{
			return array();
		}
	}
	/**
	 * 添加业主合同时,若小区不存在,通过城市id取所有的area和商圈
	 * @author too|最后修改时间 2015年5月4日 上午9:50:56
	 */
	public function getAreaInfo($city_id){
        $BusinessModel = new \Common\Model\Erp\Business();
        $sql = $BusinessModel->getSqlObject();
		$select = $sql->select(array('b'=>$BusinessModel->getTableName('business')));
		$select->columns(array('business_id','area_id','city_id','name'));
		$select->join(array('a'=>'area'),'b.area_id = a.area_id',array('aname'=>'name'),'left');
		$select->join(array('c'=>'city'),'c.city_id = b.city_id',array('cname'=>'name'),'left');
		$where = new \Zend\Db\Sql\Where();
        $where->equalTo('b.city_id', $city_id);
        $select->where($where);
        return $select->execute();
	}
	/**
	 * 取出所有城市
	 * @author too|最后修改时间 2015年5月4日 下午2:54:16
	 */
	public function getAllCity(){
	    $city = new \Common\Model\Erp\City();
	    $sql = $city->getSqlObject();
	    $select = $sql->select(array('c'=>$city->getTableName('city')));
	    $data = $select->execute();
	    //print_r($data);
	    return $data;
	}
	/**
	 * 新增小区
	 * @author too|最后修改时间 2015年5月4日 下午1:24:14
	 */
	public function addCommunity($data){
        $model = new \Common\Model\Erp\Community();
        return $model->insert($data);
	}
	/**
	 * 检查小区是否存在
	 * @param int $cityId
	 * @param int $areaId
	 * @param int $businessId
	 * @param int $communityName
	 */
	public function chackCommunity($cityId,$areaId,$businessId,$communityName){
		$model = new \Common\Model\Erp\Community();
		$data = $model->getData(array("city_id"=>$cityId,"area_id"=>$areaId,"business_id"=>$businessId,"community_name"=>$communityName));
		if (!empty($data)){
			return true;
		}
		return false;
	}
}