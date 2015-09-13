<?php

namespace Common\Helper\Erp;

use Zend;
use Core\Db\Sql\Select;

class LandlordContract extends \Core\Object {
	/**
	 * 获取业主合同表里面的信息
	 * 修改时间 2015年6月1日15:50:32
	 * 
	 * @author ft
	 */
    public function getLandlordInfo($contract_id) {
        $landlord_contract_model = new \Common\Model\Erp\LandlordContract();
        $sql = $landlord_contract_model->getSqlObject();
        $select = $sql->select(array('lc' => 'landlord_contract'));
        $select->columns(
                array(
                        'deposit' => 'deposit', 
                        'next_pay_money' => 'next_pay_money',
                        'house_id' => 'house_id',
                        'house_type' => 'house_type',
                        'house_name' => 'hosue_name',
                        'rent' => 'rent',
                        'pay' => 'pay'
        ));
        $where = new \Zend\Db\Sql\Where();
        $where->equalTo('lc.contract_id', $contract_id);
        $where->equalTo('lc.is_delete', 0);
        $select->where($where);
        return $select->execute();
    }

    /**
     * 计算业主合同租金递增
     * 修改时间  2015年7月13日00:08:55
     *
     * $author ft
     * @param  int $contract_id
     */
    public function computeLandlordRentIncrease($contract_id) {
        $landlord_con_model = new \Common\Model\Erp\LandlordContract();
        $land_column = array(
                'contract_id' => 'contract_id', 
                'rent' => 'rent', 'deposit'=> 'deposit',
                'first_rent' => 'first_rent',
                'is_ascending' => 'is_ascending', 
                'ascending_num' => 'ascending_num', 
                'ascending_type' => 'ascending_type', 
                'pay_year' => 'pay_year',
                'next_year' => 'next_year',
                'ascending_money' => 'ascending_money',
                'signing_time' => 'signing_time',
                'cycle' => 'cycle',
                'pay' => 'pay',
                'next_pay_time' => 'next_pay_time',
        );
        //获取业主合同相关信息
        $land_data = $landlord_con_model->getOne(array('contract_id' => $contract_id), $land_column);
        //到这个时间就递增
        $eof_time = strtotime("+ {$land_data['pay_year']} year", $land_data['signing_time']-86400);
        //到了合同最大周期时间, 就终止递增
        $contract_cycle = (string)$land_data['cycle'];
        $cycle_arr = explode('.', $contract_cycle);
        if ($cycle_arr[1] == 0) {
            $end_ascending_time = strtotime("+{$cycle_arr[0]} year", $land_data['signing_time']-86400);
        } else {
            $end_ascending_time = strtotime("+{$cycle_arr[0]} year +{$cycle_arr[1]} month", $land_data['signing_time']-86400);
        }
        //判断是否终止递增
        if ($land_data['is_ascending'] == 1 && $eof_time < $end_ascending_time) {
            //判断是否到了该递增的时候
            if ($land_data['cycle'] > 1 && $land_data['next_pay_time'] >= $eof_time) {
                $landlord_con_model->Transaction();
                if ($land_data['ascending_type'] == 1) {//租金按比列递增
                    //递增后租金
                    $new_rent = $land_data['rent'] + ($land_data['first_rent'] * ($land_data['ascending_num']/100));
                    $edit_data = array(
                            'rent' => $new_rent,
                    		'pay_year' => $land_data['pay_year'] + 1,
                    		'next_year' => date('Y', $land_data['next_pay_time']),//截取年份
                    		'ascending_money' => $new_rent,
                    		'next_pay_time' => $land_data['next_pay_time'],
                    		'next_pay_money' => $new_rent * ($land_data['pay'] != 0 ? $land_data['pay'] : 1),
                    );
                    $res = $landlord_con_model->edit(array('contract_id' => $contract_id), $edit_data);
                    if (!$res) {
                        $landlord_con_model->rollback();
                        return false;
                    }
                        $landlord_con_model->commit();
                        return true;
                } elseif ($land_data['ascending_type'] == 2) {//租金按金额递增
                    //递增后租金
                    $new_rent = $land_data['rent'] + $land_data['ascending_num'];
                    $edit_data = array(
                            'rent' => $new_rent,
                            'pay_year' => $land_data['pay_year'] + 1,
                            'next_year' => date('Y', $land_data['next_pay_time']),
                            'ascending_money' => $new_rent,
                            'next_pay_time' => $land_data['next_pay_time'],
                            'next_pay_money' => $new_rent * ($land_data['pay'] != 0 ? $land_data['pay'] : 1),
                    );
                    $res = $landlord_con_model->edit(array('contract_id' => $contract_id), $edit_data);
                    if (!$res) {
                        $landlord_con_model->rollback();
                        return false;
                    }
                        $landlord_con_model->commit();
                        return true;
                } elseif ($land_data['ascending_type'] == 3) {//租金按自定义递增
                    $landlord_asc_model = new \Common\Model\Erp\LandlordAscending();
                    $custom_ascending = $landlord_asc_model->getData(array('contract_id' => $contract_id));
                    //自定义的递增金额(这个 $land_data['pay_year'] 正好避开表:landlord_ascending,第一年的自定义金额)
                    $new_rent = $custom_ascending[$land_data['pay_year']]['ascending_money'];
                    $edit_data = array(
                            'rent' => $new_rent,
                            'pay_year' => $land_data['pay_year'] + 1,
                            'next_year' => date('Y', $land_data['next_pay_time']),
                            'ascending_money' => $new_rent,
                            'next_pay_time' => $land_data['next_pay_time'],
                            'next_pay_money' => $new_rent * ($land_data['pay'] != 0 ? $land_data['pay'] : 1),
                    );
                    $res = $landlord_con_model->edit(array('contract_id' => $contract_id), $edit_data);
                    if (!$res) {
                        $landlord_con_model->rollback();
                        return false;
                    }
                        $landlord_con_model->commit();
                        return true;
                }
            } else {
                return true;
            }
        } else {
            return true;
        }
    }
    
    /**
     * 检查是否已经有房间
     * 修改时间2015年6月13日17:18:10
     * 
     * @author yzx
     * @param int $houseType
     * @param string $houseName
     * @param array $user
     * @return boolean
     */
    public function CheckHouseName($houseType,$houseName,$user,$houseId=0){
    	$landlord_contract_model = new \Common\Model\Erp\LandlordContract();
    	$sql = $landlord_contract_model->getSqlObject();
    	$select = $sql->select(array('lc' => 'landlord_contract'));
    	$select->leftjoin(array("l"=>"landlord"), "lc.landlord_id=l.landlord_id");
    	$select->where(array("lc.house_type"=>$houseType,"lc.hosue_name"=>$houseName,"lc.company_id"=>$user['company_id'],"lc.is_delete"=>0));
    	$result  = $select->execute();
    	//print_r(str_replace('"', '', $select->getSqlString()));die();
    	if (!empty($result)){
    		if ($houseId>0){
    			$landlord_contract_model->edit(array("contract_id"=>$result[0]['contract_id']), array("house_id"=>$houseId));
    		}
    		return true;
    	} 
    	return false;
    }
    /**
     * 修改错误房东房源ID
     * 修改时间2015年8月11日09:49:34
     * 
     * @author yzx
     */
    public function updateHoudeId(){
    	set_time_limit(0);
    	$landlordModel = new \Common\Model\Erp\Landlord();
    	$houseModel = new \Common\Model\Erp\House();
    	$flatModel = new \Common\Model\Erp\Flat();
    	$landlordContractModel = new \Common\Model\Erp\LandlordContract();
    	$contract_data = $landlordContractModel->getData();
    	$house_data = $houseModel->getData();
    	$flat_data = $flatModel->getData();
    	foreach ($contract_data as $ckey=>$cval){
    		//分散式
    		if ($cval['house_type'] == 1){
    			foreach ($house_data as $hkey=>$hval){
    				if ( $hval['company_id']==$cval['company_id'] && $cval['community_id'] == $cval['house_id']){
    					if ($hval['house_id'] != $cval['house_id']){
    						$landlordContractModel->edit(array("contract_id"=>$cval['contract_id']), array("house_id"=>0));
    					}
    				}
    				if ($hval['company_id'] == $cval['company_id'] && $cval['house_id']<=0){
    					if ($cval['hosue_name'] == $hval['house_name']){
    						$landlordContractModel->edit(array("contract_id"=>$cval['contract_id']), array("house_id"=>$hval['house_id']));
    					}
    				}
    			}
    		}
    		//集中式
    		if ($cval['house_type'] == 2){
    			foreach ($flat_data as $fkey=>$fval){
    				if ($cval['company_id'] == $fval['company_id'] && $cval['community_id'] == $cval['house_id']){
    					if ($fval['flat_id'] != $cval['house_id']){
    						$landlordContractModel->edit(array("contract_id"=>$cval['contract_id']), array("house_id"=>0));
    					}
    				}
    				if ($fval['company_id'] == $cval['company_id'] && $cval['house_id']<=0){
    					if ($cval['hosue_name'] == $fval['flat_name']){
    						$landlordContractModel->edit(array("contract_id"=>$cval['contract_id']), array("house_id"=>$fval['flat_id']));
    					}
    				}
    			}
    		}
    	}
    }
    
    /**
     * 根据业主合同房源名称, 拉出该合同的费用
     * 修改时间  2015年9月7日10:59:34
     * 
     * @author ft
     */
    public function getLandlordFeeById($landlord_info, $company_id) {
        $fee_type_model = new \Common\Model\Erp\FeeType();
        //该公司所有费用类型
        $fee_type_list = $fee_type_model->getData(array('company_id' => $company_id, 'is_delete' => 0));
        //4 业主合同支付
        $contract_id = $landlord_info['contract_id'];
        //检查第一次有没有收费
        $serial_helper = new \Common\Helper\Erp\SerialNumber();
        $source = array('source' => 'landlord_contract', 'source_id' => $contract_id);
        //验证业主合同有没有收过费
        $res = $serial_helper->validateUserFirstWhetherCharge($source);
        $lc_con_model = new \Common\Model\Erp\LandlordContract();
        //$contract_info = $lc_con_model->getOne(array('contract_id' => $contract_id, 'is_delete' => 0), array('deposit' => 'deposit' ,'next_pay_money' => 'next_pay_money'));
        $lc_contract_fee = $serial_helper->returnContractFeeType($fee_type_list, $landlord_info, $res);
        return $lc_contract_fee;
    }
}
