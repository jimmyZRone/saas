<?php
namespace App\Web\Mvc\Model;

class ModularAuth extends Common
{
	/**
	 * 获取所有权限
	 *  最后修改时间 2015-3-13
	 *  根据lsy的demo而来
	 *  
	 * @author dengshuang
	 * @param unknown $modular_id
	 * @param unknown $user_id
	 * @param unknown $manipulate
	 * @return unknown
	 */
	public function getAllAuth($modular_id,$user_id,$manipulate){
		$sql = $this->_sql_object;
		$select = $sql->select(array('ma'=>$this->_table_name));
		$select->leftjoin(array('ug'=>'user_group'),'ma.auth_give_id = ug.group_id');
		$select->where(array('ug.user_id'=>$user_id));
		$select->where(array('ma.modular_id'=>$modular_id,'ma.'.$manipulate=>1));
		$res = $select->execute();
		return $res;
	}
	
	/**
	 * 判断是否有权限
	 *  最后修改时间 2015-3-13
	 *  根据lsy的demo而来
	 *  
	 * @author dengshuang
	 * @param unknown $modular_id
	 * @param unknown $user_id
	 * @param unknown $manipulate
	 * @return boolean
	 */
	public function hasAuth($modular_id,$user_id,$manipulate){
		$sql = $this->_sql_object;
		$select = $sql->select(array('ma'=>$this->_table_name));
		$select->leftjoin(array('ug'=>'user_group'),'ma.auth_give_id = ug.group_id');
		$select->where(array('ug.user_id'=>$user_id));
		$select->where(array('ma.modular_id'=>$modular_id,'ma.'.$manipulate=>1));
		$res = $select->execute();
		if($res){
			return true;
		}else{
			return false;
		}
	}
}