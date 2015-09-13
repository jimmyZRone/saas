<?php
namespace App\Web\Mvc\Model;

class Modular extends Common
{
	/**
	 * 根据名称获取模块id
	 *  最后修改时间 2015-3-13
	 *  
	 * @author dengshuang
	 * @param unknown $name
	 * @return unknown|boolean
	 */
	public function getIdByName($name){
		$res = $this->getOne(array('mark'=>$name));
		if($res){
			return $res['modular_id'];
		}else{
			return false;
		}
	}
}