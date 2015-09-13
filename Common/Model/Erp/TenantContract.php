<?php
namespace Common\Model\Erp;
use Zend\Db\Sql\Expression;
class TenantContract extends \Common\Model\Erp
{
	const IS_RENEWAL = 1;
	const NOT_RENEWAL = 2;
	/**
	 * 添加合同
	 * 修改时间2015年3月18日 12:56:52
	 * 
	 * @author yzx
	 * @param array $data
	 * @return boolean
	 */
	public function addContract($data,$isRenewal)
	{
		$rentalModel = new Rental();
		$tenantModel = new Tenant();
		$contractRentModel = new ContractRental();
		$rentContractModel = new RentContractExtend();
		//添加合同
		$data['dead_line'] = strtotime($data['end_line']);
		$data['end_line'] = strtotime($data['end_line']);
		$data['occupancy_time'] = strtotime($data['occupancy_time']);
		$data['total_money'] = $data['rent']*$data['rent'];
		$data['pay_num'] = $data['pay'];
		$data['next_pay_time'] = $this->clacAddNexPayTime($data);
		$this->Transaction();
		$new_contract_id = $this->insert($data);
		if (!$new_contract_id) 
		{
			$this->rollback();
			return false;
		}
		if (is_array($data['tenant_data']) && !empty($data['tenant_data']))
		{
			//添加租客
			$tenant_data=array();
			$tenant_id=array();
			foreach ($data['tenant_data'] as $tkey=>$tval)
			{
				$tenant_data['name'] = $tval['name'];
				$tenant_data['gender'] = $tval['gender'];
				$tenant_data['phone'] = $tval['phone'];
				$tenant_data['idcard'] = $tval['idcard'];
				$tenant_id[] = $tenantModel->addTenant($tenant_data);
			}
			if (empty($tenant_id))
			{
				$this->rollback();
				return false;
			}
		}
		//添加租住关系
		$data['tenant_id'] = $tenant_id[0];
		$data['contract_id'] = $new_contract_id;
		$rental_id = $rentalModel->addRental($data);
		if (!$rental_id)
		{
			$this->rollback();
			return false;
		}
		$contract_rent_data = array();
		foreach ($tenant_id as $key)
		{
			$contract_rent_data['contract_id'] = $new_contract_id;
			$contract_rent_data['tenant_id'] = $key;
			$contract_rent_data['creat_time'] = time();
			$contract_rent_id[] = $contractRentModel->addContractRental($contract_rent_data);
		}
		if (empty($contract_rent_id)){
			$this->rollback();
			return false;
		}
		$r_result = $rentContractModel->addExtend($data);
		if (!$r_result)
		{
			$this->rollback();
		}
		$this->commit();
		return $new_contract_id;
	}
	/**
	 * 获取租客合同详情
	 * 修改时间2015年3月18日 15:23:46
	 * 
	 * @author yzx
	 * @param int $contractId
	 * @return multitype:multitype: Ambigous <boolean, multitype:, unknown>
	 */
	public function detail($contractId)
	{
		$contractRentalModel = new ContractRental();
		$serialNumberModel = new SerialNumber();
		$rentalModel = new Rental();
		$rental_data = $rentalModel->getRentalByContract($contractId);
		$contract_data = $this->getOne(array("contract_id"=>$contractId));
		$tenant_list = $contractRentalModel->getTenant($contractId);
		//财务数据
		$finance_data = $serialNumberModel->getContractSerial($rental_data['rental_id'], $rental_data['source']);
		if (!empty($contract_data))
		{
			return array("contract_data"=>$contract_data,"tenant_list"=>$tenant_list,"finance_data"=>$finance_data);
		}
		return false;
	}
	/**
	 * 修改租客合同
	 * 修改时间2015年3月18日 15:51:27
	 * 
	 * @author yzx
	 * @param array $data
	 * @param int $contractId
	 * @return boolean
	 */
	public function editContract($data,$contractId)
	{
		$tenantModel = new Tenant();
		$data['dead_line'] = strtotime($data['end_line']);
		$data['end_line'] = strtotime($data['end_line']);
		$data['occupancy_time'] = strtotime($data['occupancy_time']);
		$data['total_money'] = $data['rent']*$data['rent'];
		$data['pay_num'] = $data['pay'];
		//下次付款时间计算
		$data['next_pay_time'] = $this->clacAddNexPayTime($data);
		$this->Transaction();
		$contract_res = $this->edit(array("contract_id"=>$contractId), $data);
		if (!$contract_res){
			$this->rollback();
			return false;
		}
		if (!empty($data['tenant_data']) && is_array($data['tenant_data']))
		{
			$tenant_data = array();
			foreach ($data['tenant_data'] as $tkey=>$tval)
			{
				$tenant_data['name'] = $tval['name'];
				$tenant_data['gender'] = $tval['gender'];
				$tenant_data['phone'] = $tval['phone'];
				$tenant_data['idcard'] = $tval['idcard'];
				$tenant_res = $tenantModel->editTenant($data, $tval['tenant_id']);
				if (!$tenant_res)
				{
					$this->rollback();
					return false;
				}
			}
		}
		$this->commit();
		return true;
	}
	/**
	 * 计算添加合同时下次付款时间
	 * 修改时间2015年3月20日 14:52:54
	 * 
	 * @author yzx
	 * @param array $data
	 * @return number
	 */
	private function clacAddNexPayTime($data,$contractId)
	{
		$contractModel = new TenantContract();
		$contract_data = $contractModel->getOne(array("contract_id"=>$contractId));
		$next_time = 0;
		if (!empty($contract_data))
		{
			if ($contract_data['end_line'] <= time())
			{
				$next_time = $contract_data['end_line'];
			}else 
			{
				$next_time = $contract_data['next_pay_time'];
			}
			return $next_time;
		}
		return $next_time;
	}
	
	/**
	 * 获取当月总的租金 金额
	 * 修改时间 2015年4月28日18:55:34
	 * 
	 * @author ft
	 * @return data;
	 */
	public function getCurrentMonthTotalRent($company_id, $user) {
	    //当月第一天 0时0分0秒的时
	    $current_month_first_day = strtotime(date('Y-m-01 H:i:s', strtotime(date("Y-m-d"))));
        //当月最后一天
	    $current_month_last_day = strtotime(date('Y-m-t H:i:s',time()));
	    $contractModel = new TenantContract();
	    $serial_number_helper = new \Common\Helper\Erp\SerialNumber();
	    //获取sql
	    $sql_data = $contractModel->getCityTenantContract($user);
	    //获取城市下的房源等id
	    $id_data = $serial_number_helper->getAllCityHouse($user);
	    
	    //$contractModel->getData(array('company_id' => $company_id));
	    if (!$id_data['house_id'] && !$id_data['room_focus_id']) {
	        return '';
	    }
	    $sql = $contractModel->getSqlObject();
	    $select = $sql->select(array('tc' => 'tenant_contract'));
	    $select->columns(array(
	            	'contract_id' => 'contract_id',
                	'parent_id' => 'parent_id',
                	'is_renewal' => 'is_renewal',
                	'rent' => 'rent',
                	'deposit' => 'deposit',
                	'signing_time' => 'signing_time',
                	'detain' => 'detain',
                	'pay' => 'pay',
                	'dead_line' => 'dead_line',
                	'end_line' => 'end_line',
                	'next_pay_time' => 'next_pay_time',
                	'is_delete' => 'is_delete',
                	'company_id' => 'company_id',
                	'is_stop' => 'is_stop',
                	'is_haveson' => 'is_haveson'
	    ));
	    /**
	     * 加权限控制
	     */
	    $select->leftjoin(array('r' => 'rental'), 'tc.contract_id = r.contract_id', array());
	    $select->leftjoin(array('h' => 'house'), new Expression('r.house_id > 0 AND h.house_id = r.house_id AND r.house_type = 1'),array());
	    $select->leftjoin(array('rf' => 'room_focus'), new Expression('r.room_id > 0 AND rf.room_focus_id = r.room_id AND r.house_type = 2'), array());
	    $where = new \Zend\Db\Sql\Where();
	    if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
	    {
	        $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
	        $tables = array();
	        $houseJoin = function($table) use ($select,&$tables){
	            $tables[] = $table;
	            $join = new \Zend\Db\Sql\Predicate\Expression("h.house_id IS NOT NULL and `{$table}`.authenticatee_id=h.house_id");
	            return array($join,$select::JOIN_LEFT);
	        };
	        $permisions->VerifyDataCollectionsPermissionsModel($select,$houseJoin, $permisions::USER_AUTHENTICATOR, $user['user_id'],\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
	        $flatJoin = function($table) use($select,&$tables){
	            $tables[] = $table;
	            $join = new \Zend\Db\Sql\Predicate\Expression("rf.flat_id IS NOT NULL and `{$table}`.authenticatee_id=rf.flat_id");
	            return array($join,$select::JOIN_LEFT);
	        };
	        $permisions->VerifyDataCollectionsPermissionsModel($select,$flatJoin, $permisions::USER_AUTHENTICATOR, $user['user_id'],\Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
	    
	        $and = new \Zend\Db\Sql\Where();
	         
	        $or = new \Zend\Db\Sql\Where();
	        $or->isNotNull("{$tables[0]}.authenticatee_id");
	        $and->orPredicate($or);
	         
	        $or = new \Zend\Db\Sql\Where();
	        $or->isNotNull("{$tables[1]}.authenticatee_id");
	        $and->orPredicate($or);
	         
	        $or = new \Zend\Db\Sql\Where();
	        $or->isNull("{$tables[0]}.authenticatee_id");
	        $or->isNull("{$tables[1]}.authenticatee_id");
	        $or->equalTo('h.create_uid', $user['user_id']);
	        $and->orPredicate($or);
	         
	        $where->andPredicate($and);
	    }
	    /**
	     * 权限 end
	     */
	    $link_where = new \Zend\Db\Sql\Where();
	    $where->greaterThanOrEqualTo('tc.next_pay_time', $current_month_first_day);
        $where->lessThanOrEqualTo('tc.next_pay_time', $current_month_last_day);
        //分散
        if ($id_data['house_id']) {
            $or_where = new \Zend\Db\Sql\Where();
            $or_where->in('tc.contract_id', $sql_data['dis']);
            $link_where->orPredicate($or_where);
        }
        //集中
        if ($id_data['room_focus_id']) {
            $or_where = new \Zend\Db\Sql\Where();
            $or_where->in('tc.contract_id', $sql_data['focus']);
            $link_where->orPredicate($or_where);
        }
        $where->addPredicate($link_where);
        $where->equalTo('tc.company_id', $company_id);
        $where->equalTo('tc.is_stop', 0);
        $where->equalTo('tc.is_delete', 0);
        $select->where($where);
        $contract_info = $select->execute();
        $receivable_money = 0;
        foreach ($contract_info as $con_val) {
            $con_pay = ($con_val['pay'] != 0) ? $con_val['pay'] : 1;
            $receivable_money += $con_val['rent'] * $con_pay;
//             $next_pay_time = date('Y-m-d', $con_val['next_pay_time']);//下次支付日期
//             $dead_line = date('Y-m-d', $con_val['dead_line']);        //合同到期日期
//             if ($next_pay_time >= $dead_line && $con_val['is_haveson'] == 1) {
//                 $special_money += $con_val['rent'] * $con_pay;
//             }
//             $start_pay_date = date('Y-m-d', strtotime("-$con_pay month", $con_val['next_pay_time']));
//             $signing_date = date('Y-m-d', $con_val['signing_time']);
//             if ($start_pay_time == $signing_date) {
//                 $new_con_money += $con_val['deposit'];
//                 $new_con_money += $con_val['rent'] * $con_pay;
//             }
        }
        return ($receivable_money != 0) ? $receivable_money : '';
	}
	
	/**
	 * 获取添加合同的后的 租金 和 押金
	 * 修改时间 2015年5月27日20:28:48
	 * 
	 * @author ft
	 */
	public function getContractRentAndDeposit($contract_id) {
	    $sql = $this->getSqlObject();
	    $select = $sql->select(array('tc' => 'tenant_contract'));
	    $select->columns(array('contract_id' => 'contract_id', 'rent' => 'rent', 'deposit' => 'deposit', 'pay' => 'pay', 'next_pay_money' => 'next_pay_money', 'company_id' => 'company_id'));
	    $where = new \Zend\Db\Sql\Where();
	    $where->equalTo('contract_id', $contract_id);
	    $select->where($where);
	    return $select->execute();
	}
	/**
	 * 构造查询当前城市所有的合同sql
	 * 修改时间 2015年7月30日17:39:22
	 * 
	 * @author ft
	 * @param  array $user
	 * @param  int $house_type
	 */
	public function getCityTenantContract($user) {
	    $city_model = new \Common\Model\Erp\City();
	    //分散式
	    $dis_sql = $city_model->getSqlObject();
        $dis_select = $dis_sql->select(array('c' => 'city'));
        $dis_select->columns(array());
        $dis_select->leftjoin(array('ct' => 'community'), 'c.city_id = ct.city_id', array());
        $dis_select->leftjoin(array('h' => 'house'), 'ct.community_id = h.community_id', array());
        $dis_select->leftjoin(array('re' => 'rental'), 'h.house_id = re.house_id', array('contract_id' => 'contract_id'));
        $dis_where = new \Zend\Db\Sql\Where();
        $dis_where->equalTo('c.city_id', $user['city_id']);
        $dis_where->equalTo('h.company_id', $user['company_id']);
        //$dis_where->equalTo('re.is_delete', 0);
        $dis_where->equalTo('re.house_type', 1);
        $dis_select->where($dis_where);
        $sql_arr['dis'] = array(new Expression($dis_select->getSqlString()));
        //集中式
	    $f_sql = $city_model->getSqlObject();
	    $f_select = $f_sql->select(array('c' => 'city'));
	    $f_select->columns(array());
	    $f_select->leftjoin(array('f' => 'flat'), 'c.city_id = f.city_id', array());
	    $f_select->leftjoin(array('rf' => 'room_focus'), 'f.flat_id = rf.flat_id', array());
	    $f_select->leftjoin(array('re' => 'rental'), 'rf.room_focus_id = re.room_id', array('contract_id' => 'contract_id'));
	    $f_where = new \Zend\Db\Sql\Where();
	    $f_where->equalTo('c.city_id', $user['city_id']);
	    $f_where->equalTo('f.company_id', $user['company_id']);
	    //$f_where->equalTo('re.is_delete', 0);
	    $f_where->equalTo('re.house_type', 2);
	    $f_select->where($f_where);
	    $f_select->getSqlString();
	    $sql_arr['focus'] = array(new Expression($f_select->getSqlString()));
	    return $sql_arr;
	}
    
    /**
     * 首页在住押金 权限
     * 
     * @author ft
     * @param  Object $in_select     sql select 对象
     * @param  Object $day_in_where  sql where对象
     * @param  int $flat_id          0 全部房间; -1 分散式房间; !=0 && != -1 公寓flat_id
     * @param  array $user           用户信息
     */
    public function tcAuthValidate($in_select, $day_in_where, $flat_id, $user) {
        $and = new \Zend\Db\Sql\Where();
        if ($flat_id == 0) {
            $in_select->leftjoin(array('h' => 'house') , new \Zend\Db\Sql\Predicate\Expression('re.house_id > 0 and h.house_id = re.house_id and re.house_type=1') , array('custom_number'));
            $in_select->leftjoin(array('rf' => 'room_focus') , new \Zend\Db\Sql\Predicate\Expression('re.room_id > 0 and rf.room_focus_id = re.room_id and re.house_type=2') , array('rf_custom_number' => 'custom_number'));
            $in_select->leftjoin(array('f' => 'flat') , 'rf.flat_id = f.flat_id' , array());
        }
        $permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
        $tables = array();
        if ($flat_id === 0 || $flat_id === -1) {//选中分散式  or 全部房间时要加的权限
            if ($flat_id === -1) {
                $in_select->leftjoin(array('h' => 'house') , new \Zend\Db\Sql\Predicate\Expression('re.house_id > 0 and h.house_id = re.house_id and re.house_type=1') , array('custom_number'));
            }
            $houseJoin = function($table) use ($in_select,&$tables){
                $tables[] = $table;
                $join = new \Zend\Db\Sql\Predicate\Expression("h.house_id IS NOT NULL and `{$table}`.authenticatee_id=h.house_id");
                return array($join,$in_select::JOIN_LEFT);
            };
            $permisions->VerifyDataCollectionsPermissionsModel($in_select,$houseJoin, $permisions::USER_AUTHENTICATOR, $user['user_id'],\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
    
            $or = new \Zend\Db\Sql\Where();
            $or->isNotNull("{$tables[0]}.authenticatee_id");
            $and->orPredicate($or);
    
            $or = new \Zend\Db\Sql\Where();
            $or->isNull("{$tables[0]}.authenticatee_id");
            $or->equalTo('h.create_uid', $user['user_id']);
            $and->orPredicate($or);
        }
        if ($flat_id !== -1 || $flat_id === 0) {//选中集中式 or 全部房间时要加的权限
            $flatJoin = function($table) use($in_select,&$tables2){
                $tables2[] = $table;
                $join = new \Zend\Db\Sql\Predicate\Expression("rf.flat_id IS NOT NULL and `{$table}`.authenticatee_id=rf.flat_id");
                return array($join,$in_select::JOIN_LEFT);
            };
            $permisions->VerifyDataCollectionsPermissionsModel($in_select,$flatJoin, $permisions::USER_AUTHENTICATOR, $user['user_id'],\Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
    
            $or = new \Zend\Db\Sql\Where();
            $or->isNotNull("{$tables2[0]}.authenticatee_id");
            $and->orPredicate($or);
    
            $or = new \Zend\Db\Sql\Where();
            $or->isNull("{$tables2[0]}.authenticatee_id");
            $or->equalTo('f.create_uid', $user['user_id']);
            $and->orPredicate($or);
        }
        
        $day_in_where->andPredicate($and);
        return array('select' => $in_select, 'where' => $day_in_where);
    }
    
    /**
     * 查询该房间是否有有效合同, 有就返回
     * 修改时间 2015年8月25日18:07:43
     * 
     * author ft
     * @param int $house_id
     * @param int $room_id
     * @param int $house_type
     */
    public function getRoomConInfo($house_id, $room_id, $house_type) {
        $sql = $this->getSqlObject();
        $select = $sql->select(array('tc' => 'tenant_contract'));
        $select->columns(
                array(
                       'contract_id' => 'contract_id',
                        'rent' => 'rent',
                        'deposit' => 'deposit',
                        'pay' => 'pay',
                        'next_pay_time' => 'next_pay_time',
                        'dead_line' => 'dead_line' ,
                        'is_haveson' => 'is_haveson' ,
                        'advance_time' => 'advance_time'
                
        ));
        $select->leftjoin(array('r' => 'rental'), 'tc.contract_id = r.contract_id', array('house_id' => 'house_id', 'room_id' => 'room_id', 'house_type' => 'house_type'));
        $where = new \Zend\Db\Sql\Where();
        $link_where = new \Zend\Db\Sql\Where();
        $where->equalTo('r.is_delete', 0);
        $where->equalTo('tc.is_delete', 0);
        $where->equalTo('r.house_id', $house_id);
        $where->equalTo('r.room_id', $room_id);
        $where->equalTo('r.house_type', $house_type);
        
        $or_where = new \Zend\Db\Sql\Where();
        $or_where->lessThanOrEqualTo('tc.signing_time', time());
        $or_where->greaterThanOrEqualTo('tc.end_line', time());
        $link_where->orPredicate($or_where);
        
        $or_where = new \Zend\Db\Sql\Where();
        $or_where->lessThanOrEqualTo('tc.signing_time', time()+86400);
        $or_where->greaterThanOrEqualTo('tc.end_line', time());
        $link_where->orPredicate($or_where);
        $where->andPredicate($link_where);
        $select->where($where);
        return $select->execute();
    }
}