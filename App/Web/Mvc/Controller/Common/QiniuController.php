<?php
namespace App\Web\Mvc\Controller\Common;
use Common\Helper\Http\Request;
/**
 * Qiniu
 * @author lishengyou
 * 最后修改时间 2015年4月7日 下午1:49:23
 *
 */
class QiniuController extends \App\Web\Lib\Controller{
	/**
	 * 取得Token
	 * @author lishengyou
	 * 最后修改时间 2015年6月1日 下午4:36:14
	 *
	 */
	public function gettokenAction(){
		$qiniu = new \Common\Helper\Qiniu();
		$auth = $qiniu->getAuth();
		$token = $auth->uploadToken($qiniu->getBucket());
		return $this->returnAjax(array('token'=>$token));
	}
	/**
	 * 鉴黄服务
	 * @author lishengyou
	 * 最后修改时间 2015年8月26日 下午2:56:11
	 *
	 */
	public function nropAction(){
		$pic = Request::queryString('get.pic');
		return $this->returnAjax(array('status'=>0,'msg'=>'参数错误'));
		if(!$pic){
			return $this->returnAjax(array('status'=>0,'msg'=>'参数错误'));
		}
		$uri = \Common\Helper\Qiniu\Image::getUrl($pic).'?nrop';
		$http = new \Common\Helper\Http();
		$http->open();
		$data = $http->get($uri);
		$http->close();
		$data = json_encode($data,true);
		if(!$data){
			return $this->returnAjax(array('status'=>0,'msg'=>'请求错误'));
		}
		if(!isset($data['fileList']) || !isset($data['fileList'][0]['label']) || !isset($data['fileList'][0]['label'])){
			return $this->returnAjax(array('status'=>0,'msg'=>'请求错误'));
		}
		if(isset($data['fileList'][0]['review']) && $data['fileList'][0]['review']){//是黄色图片
			return $this->returnAjax(array('status'=>1,'data'=>1));
		}
		if(!$data['fileList'][0]['label']){//是黄色图片
			return $this->returnAjax(array('status'=>1,'data'=>1));
		}
		return $this->returnAjax(array('status'=>1,'data'=>0));//不是黄色图片
	}
}