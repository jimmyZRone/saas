<?php
namespace Common\Model\Erp;

use Zend\Db\Sql\Expression;
class LandlordContract extends \Common\Model\Erp
{
	const HOUSE_TYPE_F=2;
	const HOUSE_TYPE_R  =1;
    /**
     * 获取业主合同本月总支出金额
     * 修改时间 2015年4月29日09:38:59
     *
     * @author ft
     */
    public function getCurrentMonthExpendRent($company_id, $user) {
        $landlord_model = new LandlordContract();
        //当月第一天 0时0分0秒的时间
        $current_month_first_day = strtotime(date('Y-m-01 H:i:s', strtotime(date("Y-m-d"))));
        //当月最后一天的时间    date('Y-m-t H:i:s',time()) 获取当月最后一天的时间
        $current_month_last_day = strtotime(date('Y-m-t H:i:s',time()));

        $sql = $landlord_model->getSqlObject();
        $select = $sql->select(array('lc' => 'landlord_contract',));
        $select->columns(array('lc_total_money' => new Expression("sum(next_pay_money)")));
        $select->leftjoin(array('l' => 'landlord'), 'lc.landlord_id = l.landlord_id', array());
        $where = new \Zend\Db\Sql\Where();
        
        /**
         * 权限
         */
        if($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER){
            $permissions = \Common\Helper\Permissions::Factory('sys_housing_management');
            $permisionsTable = $permissions->VerifyDataCollectionsPermissions($permissions::USER_AUTHENTICATOR, $user['user_id'],0);
            $join = new \Zend\Db\Sql\Predicate\Expression('(lc.house_id=pa.authenticatee_id and lc.house_type='.\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE.') or (lc.house_id=pa.authenticatee_id and lc.house_type='.\Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED.')');
            $select->join(array('pa'=>new \Zend\Db\Sql\Predicate\Expression($permisionsTable)),$join,'authenticatee_id',$select::JOIN_LEFT);
            $authWhere = new \Zend\Db\Sql\Where();
            $authWhere2 = clone $authWhere;
            $authWhere->isNotNull('pa.authenticatee_id');
            $authWhere2->isNull('pa.authenticatee_id');
            $authWhere2->equalTo('l.create_user_id', $user['user_id']);
            $authWhere->orPredicate($authWhere2);
            $where->addPredicate($authWhere);
        }
        
        $where->greaterThanOrEqualTo('lc.next_pay_time', $current_month_first_day);
        $where->lessThanOrEqualTo('lc.next_pay_time', $current_month_last_day);
        //$where->equalTo('lc.end_line', 0);
        $where->equalTo('lc.is_delete', 0);
        $where->equalTo('lc.city_id', $user['city_id']);
        $where->equalTo('lc.company_id', $company_id);
        $select->where($where);
        return $select->execute();
    }
    /**
     * 取一条合同
     * @author too|编写注释时间 2015年5月21日 下午5:35:04
     */
    public function getOneLandlord($id)
    {
        return $this->getOne(array('contract_id'=>$id));
    }
    /**
     * 新增合同
     * @author too|编写注释时间 2015年5月7日 下午1:42:31
     */
    public function addLandlordContract($tmpdata)
    {
    	if ($tmpdata['house_id']<=0){
    		$houseModel = new House();
    		$house_data = $houseModel->getOne(array("house_name"=>$tmpdata['hosue_name']));
    		if (!empty($house_data)){
    			$tmpdata['house_id'] = $house_data['house_id'];
    		}
    	}
        return $this->insert($tmpdata);
    }
    /**
     *终止合同
     *landlord_contract表   is_settlement=1已结算  end_line=$_SERVER['REQURES_TIME']
     * @author too|编写注释时间 2015年5月19日 下午5:26:06
     */
    public function stopLandlord($cid)
    {
        $where = array('contract_id'=>$cid);
        $data = array(
            'is_stop'=>1,
            'next_pay_money' => 0,
            'end_line'=>$_SERVER['REQUEST_TIME']
        );
        return $this->edit($where, $data);
    }
    
    /**
     * 获取业主退租合同, 用来计算退租率
     * 修改时间 2015年8月28日17:47:19
     * 
     * author ft
     * @param $company_id
     */
    public function getOutLease($company_id, $res = NULL,$user = array()) 
    {
        $sql = $this->getSqlObject();
        $select = $sql->select(array('lc' => 'landlord_contract'));
        $select->columns(array('total_id' => new Expression('count(contract_id)')));
        $select2 = clone $select;
        $where = new \Zend\Db\Sql\Where();
        if ($res) 
        {
            $where->equalTo('lc.is_stop', 1);
        }
        $where->equalTo('lc.is_delete', 0);
        $where->equalTo('lc.company_id', $company_id);
        $where->equalTo('lc.city_id', $user['city_id']);
        /**
         * 权限
         */
        if($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER){
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
        return $select->execute();
    }
    
    /**
     * 通过房源名+房源类型 验证合同是否存在
     * @param array $tmpdata
     * @return boolean
     * @author too|编写注释时间 2015年5月8日 上午11:11:32
     */
    public function checkFromHousename($tmpdata,$cid)
    {
        /* $search = array(
            'name'=>I('get.search','','string'),
            'house_type'=>I('get.type','','int'),
            'cost'=>I('get.build',''),
            'unit'=>I('get.unit',''),
            'floor'=>I('get.floor',''),
            'number'=>I('get.num','')
        ); */
        $ji = new \Common\Model\Erp\LandlordContract();
        $sql = $ji->getSqlObject();
        $select = $sql->select(array('lc'=>$ji->getTableName('landlord_contract')));
        //$select->columns(array('house_id'));
        $select->join(array('l'=>'landlord'),'lc.landlord_id = l.landlord_id',array(),'left');
        $where = new \Zend\Db\Sql\Where();
        $where->equalTo('lc.is_delete', 0);
        $where->equalTo('lc.house_type', $tmpdata['house_type']);//类型
        $where->equalTo('l.company_id', $cid);
        if($tmpdata['house_type'] == 1)
        {
            $select->join(array('dlc'=>'distributed_landlord_contract'),'dlc.contract_id = l.contract_id',array(),'left');//关联附加信息表
            $where->equalTo('dlc.cost',$tmpdata['cost']);
            $where->equalTo('dlc.unit',$tmpdata['unit']);
            $where->equalTo('dlc.floor',$tmpdata['floor']);
            $where->equalTo('dlc.number',$tmpdata['number']);
        }else {
            $where->equalTo('lc.hosue_name', $tmpdata['name']);//名
        }
        $select->where($where);
        //print_r(str_replace('"', '', $select->getSqlString()));
        $data = $select->execute();
        return $data;die;



//////////////////////////////////////////////////////////////下面暂时不要////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        /* if(!empty($data))
        {
            return false;//$this->returnAjax(array('status'=>0,'message'=>'该公寓已有合同,不能添加了'));
        }
        //合同不存在,查房源id
        if($house_type == 1)//分散
        {
            //house表关联小区表
            //echo 'type = 1';
            $ji = new \Common\Model\Erp\House();
            $sql = $ji->getSqlObject();
            $select = $sql->select(array('h'=>$ji->getTableName('house')));
            $select->columns(array('house_id'));
            $select->join(array('c'=>'community'),'c.community_id = h.community_id',array(),'left');
            $where = new \Zend\Db\Sql\Where();//造where条件对象
            //$where->equalTo('h.status', 1);//未租状态
            $where->equalTo('h.is_delete', 0);
            $where->equalTo('h.company_id',$cid);//取当前用户所在公司的房源
            $where->equalTo('c.community_name',$house_name);//匹配小区名
            $select->where($where);
            $data = $select->execute();
            return $data[0];
        }else//集中
        {
            //rf关联flat查
            //echo 'type = 2';
            $ji = new \Common\Model\Erp\RoomFocus();//echo 'type = 2';
            $sql = $ji->getSqlObject();
            $select = $sql->select(array('rf'=>$ji->getTableName('room_focus')));
            $select->columns(array('room_focus_id'));
            $select->join(array('f'=>'flat'),'f.flat_id = rf.flat_id',array(),'left');
            $where = new \Zend\Db\Sql\Where();//造where条件对象
            $where->equalTo('rf.status', 1);//未租状态
            $where->equalTo('rf.is_delete', 0);
            $where->equalTo('rf.company_id',$cid);//取当前用户所在公司的房源
            $where->equalTo('f.flat_name',$house_name);//匹配公寓名
            $select->where($where);print_r(str_replace('"', '', $select->getSqlString()));
            $data = $select->execute();
            return $data;
        } */
    }
}