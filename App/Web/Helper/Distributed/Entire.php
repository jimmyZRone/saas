<?php
namespace App\Web\Helper\Distributed;
/**
 * 分散式房源
 * @author lishengyou
 * 最后修改时间 2015年3月30日 下午2:49:40
 *
 */
class Entire extends \Common\Helper\Erp\Distributed\Entire implements \App\Web\Helper\Housing\Adaptation{
	/* (non-PHPdoc)
	 * @see \Common\Helper\Erp\Distributed\Entire::revocationSubscribe()
	 */
	public function revocationSubscribe() {
		// TODO Auto-generated method stub
		
	}

	/**
	 * 根据ID取信息
	 * @author lishengyou
	 * 最后修改时间 2015年3月30日 下午5:10:28
	 *
	 * @param array $ids
	 */
	public function getInfoByIds(array $ids){
		$model = new \Common\Model\Erp\House();
		$sql = $model->getSqlObject();
		$table = $model->getTableName();
		$select = $sql->select($table);
		$select->join(array('entirel'=>'house_entirel'), 'entirel.house_id='.$table.'.house_id');
		$select->where(array('entirel.house_id'=>$ids));
		$data = $select->execute();
		$data = $data ? $data : array();
		$temp = array();
		foreach ($data as $key => $value){
			$temp[$value['house_id']] = $value;
		}
		return $temp;
	}
}