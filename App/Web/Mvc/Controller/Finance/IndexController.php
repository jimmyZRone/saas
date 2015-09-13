<?php
namespace App\Web\Mvc\Controller\Finance;
use App\Web\Lib\Request;
use App\Web\Helper\Url;
class IndexController extends \App\Web\Lib\Controller {
	protected $_auth_module_name = 'sys_water_management';
    
    /**
     * 财务管理  首页
     * 修改时间 2015年4月22日17:05:41
     * 
     * @author ft
     */
	protected function indexAction() {
		if(!$this->verifyModulePermissions(\Common\Helper\Permissions::SELECT_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("finance-index/index")));
		}
		
	    if (!Request::isPost()) {
	        //$arr = array("1"=>"工商银行", "2"=>"建设银行", "3"=>"农业银行", "4"=>"中国邮政银行", "5"=>"招商银行", "6"=>"中信银行", "7"=>"广大银行", "8"=>"平安银行", "9"=>"交通银行", "10"=>"华夏银行", "11"=>"浦发银行", "12"=>"广发银行", "13"=>"农商银行", "14"=>"民生银行", "15"=>"成都银行","16"=>"兰州银行","17"=>"甘肃银行","18"=>"郑州银行","19"=>"北京银行","20"=>"徽商银行","21"=>"杭州银行","22"=>"齐鲁银行","23"=>"杭州联合银行","24"=>"宁波银行","25"=>"广州银行","26"=>"长沙银行","27"=>"德阳银行");
	        //$config = new \Common\Model\Erp\SystemConfig();
	        //$config->set(array("key"=>"System/Bank",'value'=>$arr));
	        $size = 20;    //分页信息
	        $user = $this->user;
	        $city_id = $user['city_id'];
	        $company_id = $user['company_id'];
	        
            $flag = Request::queryString('get.flag', 0, 'int');
	        $start_date = Request::queryString('get.cost_start_time', '', 'string');//开始时间
	        $start_date = $start_date ? strtotime($start_date) : '';
	        $end_date = Request::queryString('get.cost_end_time', '', 'string');  //结束时间
	        $end_date = $end_date ? strtotime($end_date.' 23:59:59') : '';
	        $deal_type = Request::queryString('get.deal_type', 0, 'int');          //交易类型
	        $house_type = Request::queryString('get.house_type', 0, 'int');         //房源类型(集中 /分散)
	        $finance_type = Request::queryString('get.finance_type', 0, 'int');       //资金流向(收入 /支出)
	        $search = Request::queryString('get.search', '', 'string');         //关键词搜索(小区 /房源编号)
	        $page = Request::queryString('get.page', 0, 'int');
	        $search_data = array(
	                'start_date' => $start_date,    //开始时间
	                'end_date' => $end_date,        //结束时间
	                'deal_type' => $deal_type,      //交易类型
	                'house_type' => $house_type,//房源类型(集中 /分散)
	                'finance_type' => $finance_type,    //资金流向(收入 /支出)
	                'search' => $search,            //关键词搜索(小区 /房源编号)
	                'page' => $page                 //分页
	        );
	        
	        //费用类型model
	        $fee_type_model = new \Common\Model\Erp\FeeType();
	        //交易分类: 费用类型
	        $fee_list = $fee_type_model->getData(array('company_id' => $company_id, 'is_delete' => 0));
	        //流水model
	        $serial_number_model = new \Common\Model\Erp\SerialNumber();
	        //流水Helper
	        $serial_number_helper = new \Common\Helper\Erp\SerialNumber();
	        //租住合同model
	        $tenant_contract_model = new \Common\Model\Erp\TenantContract();
	        //业主合同model
	        $landlord_contr_model = new \Common\Model\Erp\LandlordContract();
	        //1 本月应收
	        $current_month_serial = $tenant_contract_model->getCurrentMonthTotalRent($company_id, $user);
	        //2 本月已收
	        $already_total_rent = $serial_number_helper->getCurrentMonthSerial($company_id, $user);
	        //3 本月应支
	        $current_expend_rent = $landlord_contr_model->getCurrentMonthExpendRent($company_id, $user);
	        //4 本月已支
	        $already_expend_rent = $serial_number_helper->getCurrentMonthExpend($company_id, $user);
	        //当前公司总收入 / 总支出
	        $company_income_expense = $serial_number_model->getCompanyTotalIncome($user, $company_id);
	        //$city_data = $serial_number_helper->changeSerialCity();
	        
            $this->assign('user', $user);
	        $this->assign('current_month_serial', $current_month_serial); //应收金额
	        $this->assign('already_total_rent', $already_total_rent[0]); //已收金额
	        $this->assign('current_expend_rent', $current_expend_rent[0]); //应支出金额
	        $this->assign('already_expend_rent', $already_expend_rent[0]); //已支出金额
	        $this->assign('company_total_income', $company_income_expense[0]);//总输入
	        $this->assign('company_total_expense', $company_income_expense[1]);//总支出
	        $this->assign('fee_list', $fee_list);    //交易分类: 费用类型
	        
	        if ($search_data['page']) {
	            //点击搜索
    	        $serial_info = $serial_number_helper->accordingDateSearchSerialPc($search_data, $page, $size, $company_id, $this->user);
    	        $all_serial = $serial_number_helper->getAllSerialByCondition($search_data, $page, $size, $company_id, $this->user);
    	        $income = 0;
    	        $expense = 0;
    	        $ssb_money = 0;
    	        foreach ($all_serial['data'] as $money)
    	        {
    	            if ($money['type'] == 1)
    	            {
    	                $income += $money['sn_money'];
    	            }
    	            else
    	            {
    	                $expense += $money['sn_money'];
    	            }
    	            $ssb_money += $money['ssb_money'];
    	        }
                //页面加载后  第二次请求数据
	            $this->assign('view', Request::queryString('get.view_type','data'));
	            $page_info = array_shift($serial_info);
	            $this->assign('page_info', $page_info);
	            $this->assign('serial_list', $serial_info['data']);
	            $data = $this->fetch('index');
	            return $this->returnAjax(array("status" => 1, "tag_name" => "财务管理", "model_js" => "finance_indexJs", "model_href" => URL::parse("Finance-Index/index"), "data" => $data, 'income' => $income, 'expense' => $expense, 'ssb_money' => $ssb_money, "page_info" => $page_info));
	        }
	         else {
	            $this->assign('view', Request::queryString('get.view_type','template'));//控制模板显示
	            $data = $this->fetch('index');
	            return $this->returnAjax(array("status" => 1, "tag_name" => "财务管理", "model_js" => "finance_indexJs", "model_href" => URL::parse("Finance-Index/index"), "data" => $data, "page_info" => $page_info, "first" => '页面第一次请求'));
	        }
	    }
	}
}