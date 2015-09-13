<?php

namespace Common\Helper\Erp;

use Zend;
use Core\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;

class Landlord extends \Core\Object {
    private $f_charts = null;
	/**
	 * $page:页码
	 * $size:每页条数
	 * $search:搜索条件
	 * @author yusj | 最后修改时间 2015年4月23日下午1:54:08
	 */
	public function landlordList($page, $size, $search, $cuser) {
		$user = new \Common\Helper\Erp\User ();
		$city_model = new \Common\Model\Erp\City();
		$user_info = $user->getCurrentUser ();
		$pattern = $user_info['company']['pattern'];
		$lease_mode = ($pattern == 01) ? array(1) : (($pattern == 10) ? array(2) : array(1,2));
		$company_id = $user_info['company_id'];
		$city_id = $user_info['city_id'];
		$model = new \Common\Model\Erp\Landlord ();
		$sql = $model->getSqlObject ();
		$where = new \Zend\Db\Sql\Where();//造where条件对象
		$where->equalTo('lc.city_id', $city_id);
 		$where->equalTo('lc.is_delete', 0);//是否删除
 		$where->equalTo('lc.company_id', $company_id);//当前公司
 		//账户设置里面的集中式和分散式 控制
 		if ($search['house_type']==1) {
     		$where->in('lc.house_type', $lease_mode);
 		}
		if($search['house_type']==2){//集中式公寓
			$where->equalTo('lc.house_type', 2);
		}
		if($search['house_type']==3){//分散式
			$where->equalTo('lc.house_type', 1);
		}
		//到期开始时间
		if($search["term_start_time"]!=""){
			$term_start_time=strtotime($search["term_start_time"]);
			$where->greaterThanOrEqualTo("lc.dead_line", $term_start_time);
		}

		//到期结束时间
		if($search["term_end_time"]!=""){
			$term_end_time=strtotime($search["term_end_time"]);
		    if ($search["term_start_time"] == $search["term_end_time"]) {
    			$where->lessThanOrEqualTo("lc.dead_line", time());
		    } else {
    			$where->lessThanOrEqualTo("lc.dead_line", $term_end_time);
		    }
		}
		//下次付款开始时间

		if($search["pay_start_time"]!=""){
			$pay_start_time=strtotime($search["pay_start_time"]);
			$where->greaterThanOrEqualTo("lc.next_pay_time", $pay_start_time);
		}

		//下次付款结束时间
		if($search["pay_end_time"]!=""){
			$pay_end_time=strtotime($search["pay_end_time"]);
			$where->lessThanOrEqualTo("lc.next_pay_time", $pay_end_time);
		}
		//合同状态
		if($search["contract_type"]==2){//正常
		    $where->equalTo('lc.is_stop', 0);
		}
		if($search["contract_type"]==3){//终止
			$where->equalTo("lc.is_stop", 1);
		}
		$select=$sql->select(array("lc"=>"landlord_contract"));
		$select->join(array("l"=>"landlord"), "lc.landlord_id=l.landlord_id","*");
		if($search ['search']!=""){
			$key_words=$search ['search'];
			$wherez = new \Zend\Db\Sql\Where();
			$wherez->like('lc.hosue_name',"%$key_words%");
			$wherez->or;
			$wherez->like('l.phone', "%$key_words%");
			$wherez->or;
			$wherez->like('l.name', "%$key_words%");
			$where->addPredicate($wherez);
			//有关键字
		}

		/**
		 * 权限
		 */
		if($cuser['is_manager'] != \Common\Model\Erp\User::IS_MANAGER){
			$permissions = \Common\Helper\Permissions::Factory('sys_housing_management');
			$permisionsTable = $permissions->VerifyDataCollectionsPermissions($permissions::USER_AUTHENTICATOR, $cuser['user_id'],0);
			$join = new \Zend\Db\Sql\Predicate\Expression('(lc.house_id=pa.authenticatee_id and lc.house_type='.\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE.') or (lc.house_id=pa.authenticatee_id and lc.house_type='.\Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED.')');
			$select->join(array('pa'=>new \Zend\Db\Sql\Predicate\Expression($permisionsTable)),$join,'authenticatee_id',$select::JOIN_LEFT);
			$authWhere = new \Zend\Db\Sql\Where();
			$authWhere2 = clone $authWhere;
			$authWhere->isNotNull('pa.authenticatee_id');
			$authWhere2->isNull('pa.authenticatee_id');
			$authWhere2->equalTo('l.create_user_id', $cuser['user_id']);
			$authWhere->orPredicate($authWhere2);
			$where->addPredicate($authWhere);
		}

		$select->where($where);
		$select->order('lc.contract_id desc');
		//echo str_replace('"', "", $select->getSqlString());
		$data=Select::pageSelect($select, null,$page,$size);
		if(!empty($data))
		{
		    foreach ($data['data'] as $k=>$v)
		    {
		        $data['data'][$k][$k] = $model->getOne(array('landlord_id'=>$v['landlord_id']),array('landlord_id','name','phone','idcard'));
		    }
		}
		return $data;
	}

	/**
	 * 取业主合同详情
	 * @author too|编写注释时间 2015年5月19日 下午3:05:05
	 */
	public function getInfoByContractID($contract_id) {
		$model = new \Common\Model\Erp\LandlordContract();
		$sql = $model->getSqlObject ();
		$select = $sql->select (array ('lc' => 'landlord_contract'));
		//$select->join ( array ('lc' => 'landlord_contract' ), "l.landlord_id=lc.landlord_id", "*", "left" );
		$select->where ( array ('lc.contract_id' => $contract_id));
		$arr = $select->execute ();
        //P($arr);

		//取业主
		$model = new \Common\Model\Erp\Landlord();
		$ldata = $model->getOne(array('landlord_id'=>$arr[0]['landlord_id']));
        $arr[0]['name'] = $ldata['name'];
        $arr[0]['phone'] = $ldata['phone'];
        $arr[0]['idcard'] = $ldata['idcard'];

		//如果是分散式 , 再取一个表,合并到数组中
		if($arr[0]['house_type'] ==1)
		{
            $dmodel = new \Common\Model\Erp\DistributedLandlordContract();
            $tmpdata = $dmodel->get($arr[0]['contract_id']);
		}
		$arr[0]['community_id'] = $tmpdata['community_id'];
		$arr[0]['cost'] = $tmpdata['cost'];
		$arr[0]['unit'] = $tmpdata['unit'];
		$arr[0]['floor'] = $tmpdata['floor'];
		$arr[0]['number'] = $tmpdata['number'];

		//如果是自定义金额，再取landlord_ascending表
		if($arr[0]['ascending_type'] == 3)
		{
		    $lamodel = new \Common\Model\Erp\LandlordAscending();
		    $ladata = $lamodel->getData(array('contract_id'=>$arr[0]['contract_id']));
		}
		$arr[0]['astype'] = $ladata;
        //P($ladata);

    //P($arr [0]);
		if (count ( $arr ) > 0 && is_array ( $arr )) {
			return $arr [0];
		} else {
			array ();
		}
	}
	/**
	 *
	 * @param unknown $landlord_data
	 * @return 返回增加后的主键
	 */
	public function addLandlord($landlord_data) {
		$model = new \Common\Model\Erp\Landlord ();

		$res = $model->insert ( $landlord_data );

		return $res;
	}

	/**
	 *
	 * $house_type:房源类型 1分散  2集中式
	 * @author yusj | 最后修改时间 2015年4月23日下午3:06:49
	 */
	public function getHouseInfoByHouseNameAndHouseType($house_type,$house_name) {
		//获取当前用户登录信息
		$user = new \Common\Helper\Erp\User ();
		$user_info = $user->getCurrentUser ();
		$city_id = $user_info ['city_id'];
		$model = new \Common\Model\Erp\Landlord ();
		$sql = $model->getSqlObject ();
		if($house_type==1){//分散式 查小区名
			//$select=$sql->select(array("h"=>"house"));
			$select=$sql->select(array("c"=>"community"));
			//$select->columns(array("house_id","house_name","cost","unit","floor","number"));
			//$select->join(array("c"=>"community"), "h.community_id=c.community_id",array('community_name','community_alias','community_id'),"left");
			$where = new \Zend\Db\Sql\Where();//造where条件对象
			$where->equalTo('c.city_id', $city_id);//当前公司
			$where->equalTo('c.is_verify', 1);//通过审核的小区

			$where_name = new \Zend\Db\Sql\Where();//小区名字
			$where_name->like('c.community_name', "%$house_name%");
			$where_name->or;
			$where_name->like('c.community_alias', "%$house_name%");
			$where->addPredicate($where_name);

			$select->where($where);
 			//echo str_replace('"', "", $select->getSqlString());;
			$data=$select->execute();//P($data);
			return $data;
		}

	}
	/**
	 * 获取银行首字母并排序组合
	 * 修改时间2015年8月27日15:53:50
	 * 
	 * @author yzx
	 * @param array $bank
	 * @return Ambigous <unknown, string, NULL>
	 */
	public function bankCharts($bank){
		$out_bank = array();
		if (!empty($bank)){
			$cachesAdapter = new \Core\Cache(new \Core\Cache\File());
			$caches_key = md5('bank_charts');
			$caches = $cachesAdapter->get($caches_key);
			$isUpdateCache = false; 
			foreach ($bank as $key=>$val){
				$charts = '';
				if(isset($caches[$val])){
					$charts = $caches[$val];
				}else{
					$isUpdateCache = true;
					$charts = \Common\Helper\String::getFirstCharter($val);
					$caches[$val] = $charts;
				}
				$out_charts[$key]=$charts;
			}
			if($isUpdateCache){
				$cachesAdapter->save($caches_key,$caches);
			}
			asort($out_charts);
			foreach ($out_charts as $bkey=>$bval){
				$charts_bank[]=array("value"=>$bank[$bkey],"charts"=>$bval,"key"=>$bkey);
			}
			foreach ($charts_bank as $okey=>$oval){
				if ($this->f_charts==$oval['charts']){
					$out_bank[$oval['charts']][] = $oval;
				}else {
					$out_bank[$oval['charts']][] = $oval;
				}
				$this->f_charts = $oval['charts'];
			}
			return $out_bank;
		}
	}
	
	/**
	 * 保存业主
	 * 修改时间 2015年9月9日16:51:41
	 * 
	 * @author ft
	 * @param  array $landlord_info
	 */
	public function saveLandlord($landlord_data, $user)
	{
	    //收集业主信息
	    $phone = $landlord_data['phone'];
	    $name = $landlord_data['name'];
	    $idcard = $landlord_data['idcard'];
	    
	    if(!empty($idcard) && isset($idcard{120}))
	    {
	        return_error(127, '身份证格式不正确!');
	    }
	    if(mb_strlen($name) >= 30)
	    {
	        return_error(127, '名字不能超过10个汉字!');
	    }
	    $landlord_data['create_time'] = $_SERVER['REQUEST_TIME'];
	    $landlord_data['create_user_id'] = $user['user_id'];
	    $landlord_data['company_id'] = $user['company_id'];
	    $landlord_data['mark'] = '没有备忘!';

	    // 开启事务
	    $landlord = new \Common\Helper\Erp\Landlord ();
	    $landlordContractModel = new \Common\Model\Erp\LandlordContract ();
	    $landlord_id =  0;
	    $landlordContractModel->Transaction();
	    $oo = new \Common\Model\Erp\Landlord ();
	    
	    if(!empty($idcard) && !empty($phone) && !empty($name))
	    {
	        $ttppdata = $oo->getLandlord($idcard,$user['company_id']);//查业主是否存在
	        if(!empty($ttppdata))
	        {
	            $landlord_id = $ttppdata['landlord_id'];
	        }else {
	            $landlord_id = $landlord->addLandlord($landlord_data); //写入业主表
	            if (!$landlord_id)
	            {
	                $landlordContractModel->rollback();
	                return_error(127, '业主保存失败 !');
	            }
	            else 
	            {
	                $landlordContractModel->commit();
	                return $landlord_id;
	            }
	        }
	    } else
	    {
	        return_error(127, '业主姓名/业主电话/业主身份证必须填写');
	    }
	}

}
