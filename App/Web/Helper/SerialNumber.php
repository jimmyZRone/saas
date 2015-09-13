<?php
namespace App\Web\Helper;
/**
 * 财务
 * @author lishengyou
 * 最后修改时间 2015年3月27日 上午10:00:41
 *
 */
class SerialNumber extends \Common\Helper\Erp\SerialNumber{
	/**
	 * 取得财务列表
	 * @author lishengyou
	 * 最后修改时间 2015年3月30日 下午3:57:09
	 *
	 * @param unknown $user_id
	 * @param array $search[fee_type|source|pay_type]
	 */
	public function getList($user_id,$page=1,$size=10,array $search = array()){
		$model = new \Common\Model\Erp\SerialNumber();
		$sql = $model->getSqlObject();
		$select = $sql->select(array('sn'=>$model->getTableName()));
		$where = new \Zend\Db\Sql\Where();
		//搜索条件
		if(isset($search['fee_type'])){//交易分类
			//财务类型
			$sdModel = new \Common\Model\Erp\SerialDetail();
			$select->join(array('sd'=>$sdModel->getTableName()), 'sn.serial_id = sd.serial_id','fee_type_id');
			$where->equalTo('sd.fee_type_id', intval($search['fee_type']));
		}
		if(isset($search['source'])){//房源类型集中式、分布式
			if($search['source'] == 'focus'){
				$search['source'] = $model::$focus_source;
			}else{
				$search['source'] = $model::$disperse_source;
			}
			$where->in('source',$search['source']);
		}
		if(isset($search['pay_type'])){//资金流向
			switch (intval($search['pay_type'])){
				case \Common\Model\Erp\SerialNumber::TYPE_PAY:
					$where->equalTo('type',\Common\Model\Erp\SerialNumber::TYPE_PAY);//支出
					break;
				case \Common\Model\Erp\SerialNumber::TYPE_INCOME:
					$where->equalTo('type',\Common\Model\Erp\SerialNumber::TYPE_INCOME);//收入
					break;
			}
		}
		$select->where($where);
		return $select->pageSelect($select,null, $page, $size);
	}
}