<?php
namespace App\Web\Mvc\Controller;
use App\Web\Lib\Request;
use Core\Mvc\PageList;

class TenantController extends \App\Web\Lib\Controller
{
	/**
	 * 添加租客
	 * 修改时间2015年3月23日 13:33:16
	 * 
	 * @author yzx
	 */
	protected function addAction()
	{
		$tenant_data = array();
		$user = $this->user;
		if (Request::isPost())
		{
			$tenantModel = new \Common\Model\Erp\Tenant();
			$company_id = $user['company_id'];
			$name = Request::queryString("post.name",'',"string");
			$phone = Request::queryString("post.phone",'',"string");
			$gender = Request::queryString("post.gender",0,"int");
			$idcard = Request::queryString("post.idcard",'','string');
			$birthday = Request::queryString("post.birthday",'',"string");
			$nation = Request::queryString("post.nation",'',"string");
			$profession = Request::queryString("post.profession",'',"string");
			$work_place = Request::queryString("post.work_place",'',"string");
			$address = Request::queryString("post.address",'',"string");
			$email = Request::queryString("post.email","","email");
			$emergency_contact = Request::queryString("post.emergency_contact",'',"string");
			$emergency_phone = Request::queryString("post.emergency_phone",'',"string");
			$remarks = Request::queryString("post.remarks",'',"string");
			
			$tenant_data['name'] = $name;
			$tenant_data['phone'] = $phone;
			$tenant_data['gender'] = $gender;
			$tenant_data['idcard'] = $idcard;
			$tenant_data['birthday'] = $birthday;
			$tenant_data['nation'] = $nation;
			$tenant_data['profession'] = $profession;
			$tenant_data['work_place'] = $work_place;
			$tenant_data['address'] = $address;
			$tenant_data['email'] = $email;
			$tenant_data['emergency_contact'] = $emergency_contact;
			$tenant_data['emergency_phone'] = $emergency_phone;
			$tenant_data['remarks'] = $remarks;
			/* $tenant_data['name'] = "测试的";
			$tenant_data['phone'] = "15884572904";
			$tenant_data['gender'] = 1;
			$tenant_data['idcard'] = "51152149012478914";
			$tenant_data['birthday'] = "2015-03-23";
			$tenant_data['nation'] = "汉";
			$tenant_data['profession'] = "打工仔";
			$tenant_data['work_place'] = "鸟不拉屎";
			$tenant_data['address'] = "四川成都高新区";
			$tenant_data['email'] = "123@qq.com";
			$tenant_data['emergency_contact'] = "找不到的";
			$tenant_data['emergency_phone'] = "18878584789";
			$tenant_data['remarks'] = "逗逼"; */
			$result = $tenantModel->addTenant($tenant_data);
			if ($result)
			{
				return $this->returnAjax(array("status"=>1,"data"=>$result));
			}
			return $this->returnAjax(array("status"=>0,"data"=>false));
		}
	}
	/**
	 * 修改租客
	 * 修改时间2015年3月20日 13:18:27
	 * 
	 * @author yzx
	 */
	protected function editAction()
	{
		if (Request::isPost())
		{
			$tenantModel = new \Common\Model\Erp\Tenant();
			$tenant_id = Request::queryString("post.tenant_id",0,"int");
			$name = Request::queryString("post.name",'',"string");
			$phone = Request::queryString("post.phone",'',"string");
			$gender = Request::queryString("post.gender",0,"int");
			$idcard = Request::queryString("post.idcard",'','string');
			$birthday = Request::queryString("post.birthday",'',"string");
			$nation = Request::queryString("post.nation",'',"string");
			$profession = Request::queryString("post.profession",'',"string");
			$work_place = Request::queryString("post.work_place",'',"string");
			$address = Request::queryString("post.address",'',"string");
			$email = Request::queryString("post.email","","email");
			$emergency_contact = Request::queryString("post.emergency_contact",'',"string");
			$emergency_phone = Request::queryString("post.emergency_phone",'',"string");
			$remarks = Request::queryString("post.remarks",'',"string");
				
				
			$tenant_data['name'] = $name;
			$tenant_data['phone'] = $phone;
			$tenant_data['gender'] = $gender;
			$tenant_data['idcard'] = $idcard;
			$tenant_data['birthday'] = $birthday;
			$tenant_data['nation'] = $nation;
			$tenant_data['profession'] = $profession;
			$tenant_data['work_place'] = $work_place;
			$tenant_data['address'] = $address;
			$tenant_data['email'] = $email;
			$tenant_data['emergency_contact'] = $emergency_contact;
			$tenant_data['emergency_phone'] = $emergency_phone;
			$tenant_data['remarks'] = $remarks;
			$result = $tenantModel->editTenant($tenant_data, $tenant_id);
			if ($result)
			{
				return $this->returnAjax(array("status"=>1,"data"=>true));
			}
			return $this->returnAjax(array("status"=>0,"data"=>false));
		}
		
	}
	/**
	 * 新增预订人
	 *  最后修改时间 2015-3-20
	 *  house_id 和room_id 为前台直接传递
	 * @author denghsuang
	 */
	protected function reservationaddAction(){
		if (Request::isPost()){
			$name = \App\Web\Lib\Request::queryString('post.name','');
			$phone = \App\Web\Lib\Request::queryString('post.phone',1);
			$idcard = \App\Web\Lib\Request::queryString('post.idcard','');
			$money = \App\Web\Lib\Request::queryString('post.money','');
			$stime = \App\Web\Lib\Request::queryString('post.stime','');
			$etime = \App\Web\Lib\Request::queryString('post.etime','');
			$mark = \App\Web\Lib\Request::queryString('post.mark','');
			$house_id = \App\Web\Lib\Request::queryString('post.house_id','');
			$room_id = \App\Web\Lib\Request::queryString('post.room_id','');
			//验证todo
			$userModel = new \Common\Model\Erp\User();
			$user =$this->user;
			
			$tenantData = array();
			$tenantData['phone'] = $phone;
			$tenantData['idcard'] = $idcard;
			$tenantData['name'] = $name;

			$tenantData['company_id'] = $user['company_id'];
			$tenantData['create_time'] = time();
			$tenantData['is_delete'] = 0;

			$tenantModel = new \Common\Model\Erp\Tenant();
			$tenantModel->Transaction();
			$tenant_id = $tenantModel->insert($tenantData);
			if($tenant_id){
				$reserveData = array();
				$reserveData['tenant_id'] = $tenant_id;
				$reserveData['house_id'] = $house_id;
				$reserveData['room_id'] = $room_id;
				$reserveData['money'] = $money;
				$reserveData['stime'] = strtotime($stime);
				$reserveData['etime'] = strtotime($etime);
				$reserveData['mark'] = $mark;
				$reserveData['create_time'] = time();

				$reserveModel = new \Common\Model\Erp\Reserve();
				$reserve_id = $reserveModel->insert($reserveData);
				if($reserve_id){
					$tenantModel->commit();
					echo 'ok';
				}else{
					$tenantModel->rollback();
					echo 'error';
				}
			}else{
				$tenantModel->rollback();
				echo 'error';
			}
		}else{
			$this->display();
		}
	}
	
	/**
	 * 租客列表
	 *  最后修改时间 2015-3-23
	 *  
	 * @author dengshuang
	 *  
	 */
	public function listAction(){
		$key = \App\Web\Lib\Request::queryString('get.key');
		$page = \App\Web\Lib\Request::queryString('get.page',0);
		$is_focus = \App\Web\Lib\Request::queryString('get.is_focus',0);
		$pagesize = 3;
		$userModel = new \Common\Model\Erp\User();
		$user =$this->user;
		$rentalModel = new \Common\Model\Erp\Rental();
		$list = $rentalModel->getTenantList($user,$key,$page,$pagesize,$is_focus);
		$this->assign('list',$list['data']);
		$page = new PageList($list['page']['count'], $pagesize);
		$page_list=$page->showpage();
		$this->assign('page',$page_list);
		$this->display();
	}
}