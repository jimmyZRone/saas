<?php
namespace App\Web\Mvc\Controller\Tenant;
use Common\Model\Erp\Tenant;
use App\Web\Lib\Request;
use App\Web\Helper\Url;
use Common\Helper\ValidityVerification;
use Common\Helper\Permissions;
use Common\Helper\Permissions\Hook\SysHousingManagement;
class EvaluationController extends \App\Web\Lib\Controller{
	protected $_auth_module_name = 'sys_tenant_management';
	/**
	 * 编辑租客和租客详情
	 * 接收过滤数据,调用添加方法入库即可
	 * 编辑方法在Common\Model\Erp\Tenant.php
	 * @author too
	 * 最后修改时间 2015年4月15日 上午9:40:04
	 */
	public function indexAction(){
		if (!$this->verifyModulePermissions(Permissions::SELECT_AUTH_ACTION)){
			return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("Tenant-Evaluation/index")));
		}
		$user = $this->user;
		$tenantHelper = new \Common\Helper\Erp\Tenant();
		$tenantModel = new \Common\Model\Erp\Tenant();
	    $tenantId = Request::queryString('tid',0);//要修改的租客id
	    $company_id = $tenantModel->getOne(array('tenant_id' => $tenantId))['company_id'];
	    $rental_model = new \Common\Model\Erp\Rental();
	    $rental_info = $rental_model->getOne(array('tenant_id' => $tenantId));
	    //判断用户是否对当前对象有权限操作
	 /*    if ($rental_info['house_type'] == $rental_model::HOUSE_TYPE_F){
	    	$house_type = SysHousingManagement::CENTRALIZED;
	    	$id = $rental_info['room_id'];
	    }
	    if ($rental_info['house_type'] == $rental_model::HOUSE_TYPE_R){
	    	$house_type = SysHousingManagement::DECENTRALIZED;
	    	$id = $rental_info['house_id'];
	    }
	    if(!$this->verifyDataLinePermissions(Permissions::SELECT_AUTH_ACTION, $id, $house_type)){
	        return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("Tenant-Evaluation/index")));
	    } */
	    $add = new Tenant();
        $score = new \App\Web\Helper\Evaluation();
	    if(!Request::isPost()){
            $tenant = $add->getTenant($tenantId);//获取租客信息,用于模板展示
            $data = $score->getAvgScores($tenant['idcard'],$tenant['company_id']);
	        $this->assign('tenant',$tenant);
	        $this->assign('data',$data);
	        $systemconfig = new \Common\Model\Erp\SystemConfig();
	        $sysSouce = $systemconfig->getFind($list='System',$key='TrenchSouce');//计费方式 用于模板展示
	        $this->assign('sysSouce', $sysSouce);
	        $html = $this->fetch();
	        return $this->returnAjax(array("status"=>1,"tag_name"=>"租客详情","model_js"=>"customer_detail","model_name"=>"customer_detail","data"=>$html));
	    }
	    /*
	     * 有post数据时,过滤数据并执行修改
	     */
	    $phone = Request::queryString('post.phone','');//租客电话
	    $emergency_phone = Request::queryString('post.emergency_phone','');//紧急联系人电话
	    $email = Request::queryString('post.email','');//邮箱
	    $idcard = Request::queryString('post.idcard','');//身份证号码
	    $name = Request::queryString('post.name','');//租客姓名
	    $address = Request::queryString('post.address','');//地址
	    $emergency_contact = Request::queryString('post.emergency_contact','');//紧急联系人
	    $nation = Request::queryString('post.nation','汉');//民族
	    $work_place = Request::queryString('post.work_place','');//工作单位
	    $profession = Request::queryString('post.profession','');//职业
	    //$birthday = Request::queryString('post.birthday','');//生日
	    $remarks = Request::queryString('post.remarks','');//备注
	    $gender = Request::queryString('post.gender','');//性别
	    $from = Request::queryString('post.from','');//性别
	    if(empty($name)){
	        return $this->returnAjax(array('status'=>0,'message'=>'姓名不正确'));
	    }
	    $birthday = ValidityVerification::getIDCardInfo($idcard);//从身份证获取生日
	    if(empty($phone) || !ValidityVerification::checkPhoneFormate($phone)){
	        return $this->returnAjax(array('status'=>0,'message'=>'电话不正确'));
	    }
	    if(!empty($email) && !ValidityVerification::IsEmail($email)){
	        return $this->returnAjax(array('status'=>0,'message'=>'邮箱不正确'));
	    }
	    if(!empty($emergency_phone) && !ValidityVerification::checkPhoneFormate($emergency_phone)){
	        return $this->returnAjax(array('status'=>0,'message'=>'紧急联系人电话不正确'));
	    }
	    $data = array(
	        //'company_id' => $this->user['company_id'],
	        'phone' => $phone,
	        'is_delete' => 0,
	        //'create_time' => time(),
	        'remarks' => $remarks,
	        'email' => $email,
	        'profession' => $profession,
	        'work_place' => $work_place,
	        'nation' => $nation,
	        'emergency_phone' => $emergency_phone,
	        'emergency_contact' => $emergency_contact,
	        'address' => $address,
	        'name' => $name,
	        'idcard' => $idcard,
	        'birthday' => $birthday?$birthday:0,
	        'gender' => $gender,
	        'from'=>$from
	    );
	    $add = new Tenant();
	    if(!$add->editTenant($data,$tenantId)){
	        return $this->returnAjax(array('status'=>0,'message'=>'保存失败'));
	    }else{
	        $html = $this->fetch();
	        return $this->returnAjax(array('status'=>1,'message'=>'保存成功',"model_js"=>"customer_detail","model_href"=>Url::parse("Tenant-Evaluation/index"),"data"=>$html));
	    }
	}
    /**
     * 添加租客[这个方法暂时不要了]
     * 方法在Common\Model\Erp\Tenant.php
     *
     * @author too
     * 最后修改时间 2015年4月15日 下午2:56:34
     */
	public function addAction(){
	    if(!Request::isPost()){
	        $html = $this->fetch('index');
	        return $this->returnAjax(array("status"=>1,"tag_name"=>"租客评价","model_js"=>"customer_detail","model_name"=>"customer_detail","data"=>$html));
	    }
	    $name = Request::queryString('post.name','');//租客姓名
	    $phone = Request::queryString('post.phone','');//租客电话
	    $gender = Request::queryString('post.gender','');//性别
	    $idcard = Request::queryString('post.idcard','');//身份证号码

	    $emergency_phone = Request::queryString('post.emergency_phone','');//紧急联系人电话
	    $email = Request::queryString('post.email','');//邮箱
	    $address = Request::queryString('post.address','');//地址
	    $emergency_contact = Request::queryString('post.emergency_contact','');//紧急联系人
	    $nation = Request::queryString('post.nation','汉');//民族
	    $work_place = Request::queryString('post.work_place','');//工作单位
	    $profession = Request::queryString('post.profession','');//职业
	    //$birthday = Request::queryString('post.birthday','');//生日 不用传值
	    $remarks = Request::queryString('post.remarks','');//备注

	    if(empty($name)){
	        return $this->returnAjax(array('status'=>0,'message'=>'填个名字呗'));
	    }
	    if(empty($idcard) || !ValidityVerification::IsId($idcard)){
	        return $this->returnAjax(array('status'=>0,'message'=>'身份证号码不正确'));
	    }
	    $birthday = ValidityVerification::getIDCardInfo($idcard);//从身份证获取生日
	    if(empty($phone) || !ValidityVerification::checkPhoneFormate($phone)){
	        return $this->returnAjax(array('status'=>0,'message'=>'电话不正确'));
	    }
	    if(!empty($email) && !ValidityVerification::IsEmail($email)){
	        return $this->returnAjax(array('status'=>0,'message'=>'邮箱不正确'));
	    }
	    if(!empty($emergency_phone) && !ValidityVerification::checkPhoneFormate($emergency_phone)){
	        return $this->returnAjax(array('status'=>0,'message'=>'紧急联系人电话不正确'));
	    }
	    $data = array(
	        'company_id' => $this->user['company_id'],
	        'phone' => $phone,
	        'is_delete' => 0,
	        'create_time' => time(),
	        'remarks' => $remarks,
	        'email' => $email,
	        'profession' => $profession,
	        'work_place' => $work_place,
	        'nation' => $nation,
	        'emergency_phone' => $emergency_phone,
	        'emergency_contact' => $emergency_contact,
	        'address' => $address,
	        'name' => $name,
	        'idcard' => $idcard,
	        'birthday' => $birthday,
	        'gender' => $gender
	    );
	    $add = new Tenant();
	    if(!$add->addTenant($data)){
	        return $this->returnAjax(array('status'=>0,'message'=>'添加失败'));
	    }else{
	        $html = $this->fetch('index');
	        return $this->returnAjax(array('status'=>1,'message'=>'添加成功',"model_js"=>"customer_detail","model_name"=>"customer_detail","model_href"=>Url::parse("Tenant-Evaluation/index"),"data"=>$html));
	    }
	}

}