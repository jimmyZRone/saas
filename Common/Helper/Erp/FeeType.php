<?php
namespace Common\Helper\Erp;
/**
 * 财务类型
 * @author lishengyou
 * 最后修改时间 2015年3月26日 下午1:31:28
 *
 */
class FeeType extends \Core\Object
{
	/**
	 * 添加类型
	 * @author lishengyou
	 * 最后修改时间 2015年3月26日 下午1:33:47
	 *
	 * @param array $data
	 */
	public static function add(array $data){
		$info = array('type_name','company_id');
		$infoData = array_intersect_key($data, array_fill_keys($info, false));
		$infoData = array_filter($infoData);
		if(count($infoData) != count($info)){
			self::setLastError('请填写完整信息');
			return false;
		}
		$infoData['is_delete'] = 0;
		$feetypeModel = new \Common\Model\Erp\FeeType();
		//验证分类名称是否已经存在
		$feetypeData = $feetypeModel->getOne(array('type_name'=>$data['type_name'],'company_id'=>$data['company_id']));
		if($feetypeData && !$feetypeData['is_delete']){
			self::setLastError('分类名称已经存在');
			return false;
		}
		if($feetypeData && $feetypeData['is_delete']){//已经存在分类，并且是已经删除的时候，直接恢复原来的分类
			return self::edit($feetypeData['fee_type_id'], array('is_delete'=>0));
		}else{
			return $feetypeModel->insert($infoData);
		}
	}
	/**
	 * 删除分类
	 * @author lishengyou
	 * 最后修改时间 2015年3月26日 下午1:38:09
	 *
	 * @param unknown $fee_type_id
	 * @param unknown $company_id
	 */
	public static function delete($fee_type_id,$company_id){
		$feetypeModel = new \Common\Model\Erp\FeeType();
		return $feetypeModel->edit(array('fee_type_id'=>$fee_type_id,'company_id'=>$company_id,'sys_type_id'=>0), array('is_delete'=>1));
	}
	/**
	 * 编辑分类信息
	 * @author lishengyou
	 * 最后修改时间 2015年3月26日 下午2:11:59
	 *
	 * @param unknown $fee_type_id
	 * @param array $data
	 * @return boolean|Ambigous <number, boolean>
	 */
	public static function edit($fee_type_id,array $data){
		$company_id = isset($data['company_id']) ? $data['company_id'] : false;
		$feetypeModel = new \Common\Model\Erp\FeeType();
		$feetypeData = $feetypeModel->getOne(array('fee_type_id'=>$fee_type_id));
		if(!$feetypeData || $feetypeData['company_id'] != $company_id){
			self::setLastError('分类不存在');
			return false;
		}
		$info = array('type_name','is_delete');
		$data = array_intersect_key($data, array_fill_keys($info, false));//允许通过的数据
		$data = array_filter($data);
		if(empty($data)){
			self::setLastError('信息不完整');
			return false;
		}
		//过滤相同的
		\Core\ArrayObject::filterEqualKeyValue($data,$feetypeData);
		if(empty($data)){//没有需要保存的，直接返回成功
			return true;
		}
		$feetypeData = isset($data['type_name']) ? $feetypeModel->getOne(array('type_name'=>$data['type_name'],'company_id'=>$company_id)) : false;
		if(isset($data['type_name']) && $feetypeData && $feetypeData['fee_type_id'] != $feetypeData){
			//有修改名称时，查询当前名称是否已经存在
			self::setLastError('分类名称已经存在');
			return false;
		}
		return $feetypeModel->edit(array('fee_type_id'=>$fee_type_id,'sys_type_id'=>0), $data);
	}
	/**
	 * 获取公司费用项
	 * 修改时间2015年5月6日 19:34:53
	 * 
	 * @author yzx
	 * @param unknown $user
	 * @return Ambigous <boolean, multitype:multitype:Ambigous <number, unknown> number  Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> > >
	 */
	public function getCompanyFeeType($user)
	{
		$feetypeModel = new \Common\Model\Erp\FeeType();
		$result = $feetypeModel->getData(array("company_id"=>$user['company_id'],"is_delete"=>0));
		return $result;
	}
	/**
	 * 修复系统类别
	 * @author lishengyou
	 * 最后修改时间 2015年5月20日 下午3:12:41
	 *
	 * @param unknown $company_id
	 */
	public function repairSystemCategories($company_id){
		$defFeeType = explode('、','租金、押金、水费、电费、气费、物业费、服务费、维修费、保洁费、定金、欠费、网费');
		$defFeeTypeKey = array_keys($defFeeType);
		$defFeeTypeKey = array_map(function ($v){return $v+1;}, $defFeeTypeKey);
		$feetype = new \Common\Model\Erp\FeeType();
		$data = $feetype->getData(array('company_id'=>$company_id,'sys_type_id'=>$defFeeTypeKey));
		$temp = array();
		foreach ($data as $value){$temp[$value['sys_type_id']] = $value;}
		$data = $temp;unset($temp);
		foreach ($defFeeTypeKey as $value){
			if(isset($data[$value])){
				continue;
			}
			//没有设置、需要添加
			$res = $feetype->getOne(array('company_id'=>$company_id,'type_name'=>$defFeeType[$value-1],'sys_type_id'=>0));
			if($res){//全新添加
				$feetype->edit(array('fee_type_id'=>$res['fee_type_id']), array('is_delete'=>0,'sys_type_id'=>$value));
			}else{
				$feetype->insert(array('is_delete'=>0,'sys_type_id'=>$value,'type_name'=>$defFeeType[$value-1],'company_id'=>$company_id));
			}
		}
	}
	/**
	 * 去除系统定制费用项
	 * 修改时间2015年6月9日10:34:54
	 * 
	 * @author yzx
	 * @param array $fee_type
	 * @return unknown
	 */
	public function getUnsetFee(&$fee_type){
		$unset_map = array('租金','押金','定金','欠费');
		foreach ($fee_type as $key=>$val){
			if (in_array($val['type_name'], $unset_map)){
				unset($fee_type[$key]);
			}
		}
		return $fee_type;
	}
}