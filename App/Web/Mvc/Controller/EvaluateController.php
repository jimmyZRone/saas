<?php
namespace App\Web\Mvc\Controller;
use App\Web\Lib\Request;

class EvaluateController extends \App\Web\Lib\Controller
{
	const PAGE_SIZE = 10;
	/**
	 * 添加租客评分
	 * 修改时间2015年3月18日 14:19:33
	 * 
	 * @author yzx
	 */
	protected function addAction()
	{
		$user = $this->user;
		$evaluate_data=array();
		if (Request::isPost())
		{
			$evaluateModel = new \Common\Model\Erp\Evaluate();
			$tenant_id = Request::queryString("post.tenant_id",0,"int");
			$score = Request::queryString("post.score",0,"int");
			$reason = Request::queryString("post.reason");
			$remark = Request::queryString("post.remark",'','string');
			
			$evaluate_data['compay_id'] = $user['compay_id'];
			$evaluate_data['user_id'] = $user['user_id'];
			$evaluate_data['tenant_id'] = $tenant_id;
			$evaluate_data['reason'] = $reason;
			$evaluate_data['remark'] = $remark;
			$evaluate_data['score'] = $score;
			$result = $evaluateModel->addEvaluate($evaluate_data);
			if ($result)
			{
				return $this->returnAjax(array("status"=>1,"data"=>$result));
			}
			return $this->returnAjax(array("status"=>0,"data"=>false));
		}
		return $this->returnAjax(array("status"=>0,"data"=>false));
	}
	/**
	 * 查看租客评分
	 * 修改时间2015年3月18日 14:49:33
	 * 
	 * @author yzx
	 */
	protected function checkAction()
	{
		$evaluateModel = new \Common\Model\Erp\Evaluate();
		if (Request::isGet())
		{
			$page = Request::queryString("get.page",0,"int");
			$tenant_id = Request::queryString("get.tenant_id",0,"int");
			$result = $evaluateModel->checkEvaluate($tenant_id,$page,self::PAGE_SIZE);
			if ($result)
			{
				return $this->returnAjax(array("status"=>1,"data"=>$result));
			}
			return $this->returnAjax(array("status"=>0,"data"=>false));
		}
		return $this->returnAjax(array("status"=>0,"data"=>false));
	}
}