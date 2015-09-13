<?php
namespace App\Web\Mvc\Model;

use Core\Db\Sql\Select;
use Zend\Db\Sql\Expression;
class Landlord extends Common
{
	public function getLandlordList($user,$houseName,$landlordName,$phoneName,$page,$pagesize){
		$select = $this->_sql_object->select(array('l'=>$this->_table_name));
		$select->leftjoin(array('lc'=>'landlord_contract'),"lc.landlord_id = l.landlord_id");
		
		$where = new \zend\db\sql\Where();
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
		//$where->equalTo('u.is_manager', 0);
		$select->where($where);
		
		$countSelect = clone $select;
		$countSelect->columns(array('count'=>new Expression('count(l.landlord_id)')));
		$count = $countSelect->execute();
		$count = $count[0]['count'];
		
		$result = Select::pageSelect($select, $count, $page, $pagesize);
		if($result){
			return $result;
		}else{
			return array();
		}
	}
}