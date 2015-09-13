<?php
namespace App\Web\Mvc\Controller;
use App\Web\Lib\Request;
class CommunityController extends \App\Web\Lib\Controller
{
	/**
	 * 添加小区
	 * 修改时间2015年5月5日 14:38:36
	 * 
	 * @author yzx
	 */
	public function addAction()
	{
		if (Request::isPost())
		{
			$user = $this->user;
			$communityHelper = new  \Common\Helper\Erp\Community();
			$data = array();
			$community_name = Request::queryString("post.community_name",'',"string");
			$area_id = Request::queryString("post.area_id",0,"int");
			$business_id = Request::queryString("post.business_id",0,"int");
			$address = Request::queryString("post.address",'','string');
			$business_name = Request::queryString("post.business_name",'','string');
			$area_name = Request::queryString("post.area_name",'',"string");
			
			$data['community_name'] = $community_name;
			$data['area_id'] = $area_id;
			$data['area_string'] = $area_name;
			$data['business_id'] = $business_id;
			$data['address'] = $address;
			$data['business_string'] = $business_name;
			$landlord = new \App\Web\Helper\Landlord ();
        	$community_data = $landlord->chackCommunity($this->user['city_id'], $area_id, $business_id, $community_name);
			$result = $communityHelper->addCommunit($data,$user);
			if ($community_data){
				return $this->returnAjax(array("status"=>0,"data"=>false,"message"=>"小区已经存在"));
			}
			if ($result)
			{
				return $this->returnAjax(array("status"=>1,"data"=>$result,"message"=>'添加成功等待审核'));
			}
			return $this->returnAjax(array("status"=>0,"data"=>false,"message"=>"添加失败"));
		}
	}
}