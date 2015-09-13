<?php
namespace App\Web\Mvc\Controller;
use App\Web\Lib\Request;
class SerialnumberController extends \App\Web\Lib\Controller
{
	/**
	 * 获取财务详情
	 * 修改时间2015年3月19日 10:24:08
	 * 
	 * @author lishengyou
	 * @@已经没有流水详情了，直接到编辑页面
	 */
	protected function detailAction()
	{
		$serial_id = Request::queryString("get.serial_id",0,"int");
		$user = $this->getUser();
		if(!$serial_id){
			die('');
		}
		$serialNumber = '';
	}
	
	/**
	 * 添加流水
	 * 修改时间2015年3月19日 13:46:40
	 * 
	 * @author yzx
	 * @添加了新字段，需要处理
	 */
	protected function addAction()
	{
		$user = $this->user;
		$user_id = $user['user_id'];
		$company_id = $user['company_id'];
		$serial_number = array();
		if (Request::isPost())
		{
			$serialNumberModel = new  \Common\Model\Erp\SerialNumber();
			$house_id = Request::queryString("post.house_id",0,"int");
			$house_type = Request::queryString("post.house_type",'',"string");
			$fee = Request::queryString("post.fee");
			$pay_time = Request::queryString("post.pay_time",'',"string");
			$status = Request::queryString("post.status",0,"int");
			$money = Request::queryString("post.money",0,"int");
			$type = Request::queryString("post.type",0,"int");
			
			$serial_number['house_id'] = $house_id;
			$serial_number['house_type'] = $house_type;
			$serial_number['fee'] = $fee;
			$serial_number['pay_time'] = $pay_time;
			$serial_number['status'] = $status;
			$serial_number['money'] = $money;
			$serial_number['type'] = $type;
			$serial_number['user_id'] = $user_id;
			$serial_number['company_id'] = $company_id;
			$result = $serialNumberModel->addSeriaNumber($serial_number);
			if ($result)
			{
				return $this->returnAjax(array("status"=>1,"data"=>$result));
			}
			return $this->returnAjax(array("status"=>0,"data"=>false));
		}
	}
	/**
	 * 获取编辑时所需的数据
	 * 修改时间2015年3月19日 16:58:44
	 * 
	 * @author yzx
	 */
	protected function editdataAction()
	{
		if (Request::isGet())
		{
			$serialNumberModel = new  \Common\Model\Erp\SerialNumber();
			$serial_id = Request::queryString("get.serial_id",0,"int");
			$result = $serialNumberModel->editData($serial_id);
			if ($result)
			{
				return $this->returnAjax(array("status"=>1,"data"=>$result));
			}
			return $this->returnAjax(array("status"=>0,"data"=>false));
		}
	}
	/**
	 * 修改流水
	 * 修改时间2015年3月19日 15:31:39
	 * 
	 * @author yzx
	 */
	protected function editAction()
	{
		if (Request::isPost())
		{
			$serialNumberModel = new  \Common\Model\Erp\SerialNumber();
			$serial_id = Request::queryString("post.serial_id",0,"int");
			$house_id = Request::queryString("post.house_id",0,"int");
			$house_type = Request::queryString("post.house_type",'',"string");
			$detail = Request::queryString("post.detail");
			$pay_time = Request::queryString("post.pay_time",'',"string");
			$status = Request::queryString("post.status",0,"int");
			$money = Request::queryString("post.money",0,"int");
			
			$serial_number['house_id'] = $house_id;
			$serial_number['house_type'] = $house_type;
			$serial_number['detail'] = $detail;
			$serial_number['pay_time'] = $pay_time;
			$serial_number['status'] = $status;
			$serial_number['money'] = $money;
			$result = $serialNumberModel->editSeriaNumber($serial_number, $serial_id);
			if ($result)
			{
				return $this->returnAjax(array("status"=>1,"data"=>true));
			}
			return $this->returnAjax(array("status"=>0,"data"=>false));
		}
	}
	/** 流水列表
	 *  最后修改时间 2015-3-19
	 *  
	 * @author denghsuang
	 */
	protected function listAction(){
		$search = array('fee_type','source','pay_type');
		$get = \App\Web\Lib\Request::queryString('get.');
		$search = array_intersect_key($get, array_fill_keys($search, false));
		$search = array_filter($search);
		$helper = new \App\Web\Helper\SerialNumber();
		$user = $this->getUser();
		//整理数据
		$data = $helper->getList($user['user_id'],\App\Web\Lib\Request::queryString('get.page'),10,$search);
		//取得所有分类ID
		$housingids = array();
		foreach ($data['data'] as $key => $value){
			$datakey = null;
			$datapk = null;
			if($value['house_id'] && $value['room_id']){
				$datakey = \Common\Helper\Erp\Housing::DISTRIBUTED_ROOM;
				$datapk = $value['room_id'];
			}elseif($value['house_id'] && !$value['room_id']){
				$datakey = \Common\Helper\Erp\Housing::DISTRIBUTED_ENTIRE;
				$datapk = $value['house_id'];
			}else{
				$datakey = \Common\Helper\Erp\Housing::CENTRALIZED;
				$datapk = $value['room_id'];
			}
			if(!isset($housingids[$datakey])){
				$housingids[$datakey] = array();
			}
			$vlaue['house_room_type'] = $datakey;
			$vlaue['house_room_pk'] = $datapk;
			$housingids[$datakey][] = $datapk;
			$data['data'][$key] = $value;
		}
		//取得房源数据
		$housingdata = \App\Web\Helper\Housing::getHousingInfo($housingids);
		$housingdata = $housingdata ? $housingdata : array();
		foreach ($data['data'] as $key => $value){
			if(isset($housingdata[$value['house_room_type']]) && isset($housingdata[$value['house_room_type']][$value['house_room_pk']])){
				$value['housing'] = $housingdata[$value['house_room_type']][$value['house_room_pk']];
			}
			$data['data'][$key] = $value;
		}
		return $data;
	}
	
	/**
	 * 欠费列表
	 *  最后修改时间 2015-3-19
	 *
	 * @author denghsuang
	 */
	protected function debtsAction(){
		$page = \App\Web\Lib\Request::queryString('get.page',0);
		$user = $this->user;
		$serialNumberModel = new  \Common\Model\Erp\SerialNumber();
		$pagesize = 3;
		$list = $serialNumberModel->getDebtsList($user,$page,$pagesize);
// 		print_r($list);die;
		$this->assign('list',$list['data']);
		$page = new \Core\Mvc\PageList($list['page']['count'], $pagesize);
		$page_list=$page->showpage();
		$this->assign('page',$page_list);
		$this->display();
	}
}
