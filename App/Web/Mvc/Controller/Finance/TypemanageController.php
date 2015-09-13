<?php
namespace App\Web\Mvc\Controller\Finance;
use App\Web\Lib\Request;
use App\Web\Helper\Url;
class TypemanageController extends \App\Web\Lib\Controller {
	protected $_auth_module_name = 'sys_water_type_management';
	
    /**
     * 费用类型管理     费用类型新增
     * 修改时间 2015年4月22日14:52:23
     * 
     * @author ft
     */
    protected function feetypemanageAction() {
        $company_id = $this->user['company_id'];
        $fee_type_model = new \Common\Model\Erp\FeeType();
        $where = array('company_id' => $company_id, 'is_delete' => 0);
        $fee_type_data = $fee_type_model->getData($where);
        if (!Request::isPost()) {
        	if(!$this->verifyModulePermissions(\Common\Helper\Permissions::SELECT_AUTH_ACTION)){
        		return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("finance-typemanage/feetypemanage")));
        	}
        	
            $fee_type_list = $fee_type_model->getData(array('company_id' => $company_id, 'is_delete' => 0));
            $this->assign('fee_type_list', $fee_type_list);
            $data = $this->fetch('fee_type_manage');
            return $this->returnAjax(array("status" => 1, "tag_name" => "类型管理", "model_js" => "finance_typeJs", "model_href" => Url::parse("Finace-TypeManage/fee_type_manage"), "data" => $data));
        } else {
            $fee_type_info = Request::queryString('post.typeManage', '');
            $is_add = 0;
            foreach ($fee_type_info as $key=>$val){
            	if (intval($val[0])<=0){
            		$is_add+= 1;
            	}
            }
            if ($is_add){
            	if(!$this->verifyModulePermissions(\Common\Helper\Permissions::INSERT_AUTH_ACTION,'sys_water_type_management')){
            		return $this->returnAjax(array('__status__'=>403));
            	}
            }else {
            	if (!$this->verifyModulePermissions(\Common\Helper\Permissions::UPDATE_AUTH_ACTION,'sys_water_type_management')){
            		 return $this->returnAjax(array('__status__'=>403));
            	}
            }
            $fee_type = getArrayKeyClassification($fee_type_data , 'fee_type_id' , 'type_name');
            $valdate_fee = array();
            //循环判断费用名称是否重复
            foreach ($fee_type_info as $val) {
                if (isset($val[1]{60})){
                    return $this->returnAjax(array("status" => 0, "message" => "费用类型名称不能超过20个汉字!"));
                }
                if (!in_array($val[1], $fee_type)) {
                    if ($val[0] != '' && $val[1] != '') {
                        $data[] = array(    //修改费用类型
                            'fee_type_id' => $val[0],
                            'type_name' => trim($val[1]),
                        );
                    }
                    if ($val[0] == '' && $val[1] != '') {
                        $valdate_fee[] = $val[1];
                        $data[] = array(    //新增费用类型
                            'type_name' => trim($val[1]),
                            'company_id' => $company_id,
                            'is_delete' => 0
                        );
                    }
                }
            }
            if (!empty($valdate_fee)) {
            	if (count($valdate_fee) != count(array_unique($valdate_fee))){
            		return $this->returnAjax(array("status" => 0, "message" => "费用类型重复!"));
            	}
            }
            $tes = $fee_type_model->addFeeType($data);
            unset($val);
            if ($tes) {
                return $this->returnAjax(array("status" => 1, "message" => "新增或修改成功!"));
            }
                return $this->returnAjax(array("status" => 0, "message" => "费用类型重复!"));
        }
    }
    
    /**
     * 费用类型管理     费用类型删除
     * 修改时间 2015年5月4日14:22:32
     * 
     * @author ft
     */
    protected function feetypedeleteAction() {
    	if(!$this->verifyModulePermissions(\Common\Helper\Permissions::DELETE_AUTH_ACTION)){
    		return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("finance-typemanage/feetypedelete")));
    	}
    	
    	
        $cid = $this->user['company_id'];
        $fee_type_id = Request::queryString('fee_type_id', 0, 'int');
        $fee_type_model = new \Common\Model\Erp\FeeType();
        $res = $fee_type_model->deleteFeeType($fee_type_id, $cid);
        if ($res) {
            return $this->returnAjax(array("status" => 1, "message" => "费用类型删除成功!"));
        }
            return $this->returnAjax(array("status" => 0, "message" => "费用类型删除失败!"));
    }
}