<?php
namespace App\Web\Mvc\Controller;

class FeetypeController extends \App\Web\Lib\Controller{
	/**
	 * 新增费用类型
	 *  最后修改时间 2015-3-18
	 *  只完成基础功能 
	 *
	 * @author dengshaung
	 */
	protected function addAction(){
		$user = $this->user;
		if(!\App\Web\Lib\Request::isPost()){
			$this->display();
		}else{
			$name = \App\Web\Lib\Request::queryString('post.name','');
			if(empty($name)){
				echo 'error for name';
			}else{
				$feeTypeData = array();
				$feeTypeData['type_name'] = $name;
				$feeTypeData['company_id'] = $user['company_id'];
				$feeTypeId = \Common\Helper\Erp\FeeType::add($feeTypeData);
				if($feeTypeId){
					echo 'ok';
				}else{
					echo 'add error';
				}
			}
		}
	}
	
	/**
	 * 费用类型删除
	 *  最后修改时间 2015-3-18
	 *  只完成基础功能
	 *
	 * @author dengshaung
	 */
	protected function delete(){
		$feeTypeId = \App\Web\Lib\Request::queryString('post.feeTypeId',0);
		if(!$feeTypeId){
			echo 'feeTypeId empty';
		}else{
			if(\Common\Helper\Erp\FeeType::delete(array('fee_type_id'=>$feeTypeId,'company_id'=>$this->user['company_id']))){
				//删除费用类型后是否需要对费用进行处理 需要跟产品沟通 todo
				echo 'ok';
			}else{
				echo 'error';
			}
		}
	}
	
	/**
	 * 费用类型列表
	 *  最后修改时间 2015-3-18
	 *  只完成基础功能 分页没做
	 *
	 * @author dengshaung
	 */
	protected function listAction(){
		$user = $this->user;
		$feeTypeModel = new \Common\Model\Erp\FeeType();
		$list = $feeTypeModel->getData(array('company_id'=>$user['company_id']));
// 		print_r($list);
		$this->assign('list', $list);
		$this->display();
	}
	
	/**
	 * 费用类型修改
	 *  最后修改时间2015-3-18
	 *  只完成基础功能
	 *  
	 * @author dengshuang
	 */
	protected function editAction(){
		$fee_type_id = \App\Web\Lib\Request::queryString('get.fee_type_id',0);
		if(empty($fee_type_id)){
			echo 'miss feetypeid';
		}else{
			$feeTypeModel = new \Common\Model\Erp\FeeType();
			if(!\App\Web\Lib\Request::isPost()){
				$feeType = $feeTypeModel->getOne(array('fee_type_id'=>$fee_type_id,'company_id'=>$this->user['company_id']));
				$this->assign('info', $feeType);
				$this->display('add');
			}else{
				$name = \App\Web\Lib\Request::queryString('post.name','');
				if($feeTypeModel->edit(array('fee_type_id'=>$fee_type_id), array('type_name'=>$name,'company_id'=>$this->user['company_id']))){
					echo 'ok';
				}else{
					echo 'error';
				}
			}
		}
		
	}
}