<?php
namespace App\Web\Mvc\Controller;
use App\Web\Lib\Request;
/**
 * 备忘录
 * @author lishengyou
 * 最后修改时间 2015年3月23日 下午5:12:58
 *
 */
/* `memo_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '备忘录主键',
`memo_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '备忘时间',
`notice_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '通知时间',
`is_notice` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否提醒（0否，1是）',
`notice_content` text NOT NULL COMMENT '提醒内容',
`create_uid` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建者id，user表主键',
`create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间', */
class MemoController extends \App\Web\Lib\Controller
{
	/**
	 * 添加备忘
	 * @author too|编写注释时间 2015年5月13日 上午9:47:07
	 */
	protected function addAction(){
	    $notice_content = I('post.content','','string');
	    $notice_time = I('post.notice_time','5');
	    $memo_time = I('post.memo_time','yesterday');

	    if(empty($memo_time) || empty($notice_time) || empty($notice_content))
	    {
	        return $this->returnAjax(array('status'=>0,'message'=>'填写不完整'));
	    }
	    $memo_time = strtotime($memo_time);
        if ($notice_time == 6) {
            $is_notice = 0;//提醒
        } else {
            $is_notice = 1;//提醒
        }
	    $data = array(
	        'create_time'=>$_SERVER['REQUEST_TIME'],//创建时间
	        'create_uid'=>$this->getUser()['user_id'],//创建者id
	        'notice_content'=>$notice_content,//提醒内容
	        'is_notice'=>$is_notice,//是否提醒
	        'notice_time'=>$notice_time,//通知时间 有6个状态 提醒方式
	    );
	    $data['memo_time'] = $memo_time;//备忘时间
	    $dbMemo = new \Common\Model\Erp\Memo();
	    if(!$memo_id = $dbMemo->addMemo($data))
	    {
	        return $this->returnAjax(array('status'=>0,'message'=>'添加失败'));
	    }
		$rdata['memo_time'] = date('Y-m-d',$memo_time);
		$rdata['memo_moment'] = date('H:i',$memo_time);
		$rdata['memo_id'] = $memo_id;
		$rdata['notice_content'] = $notice_content;
		return $this->returnAjax(array('status'=>1,'data'=>$rdata));
	}
	/**
	 * 查看/编辑备忘
	 * @author too|编写注释时间 2015年5月13日 下午1:25:11
	 */
	protected function scanandeditAction(){
		$memo_id = I('get.memo_id',0,'intval');//获得要修改/查看的备忘id
		$dbMemo = new \Common\Model\Erp\Memo();
		$uid = $this->getUser()['user_id'];
		if(!Request::isPost())
		{
		    if($memo_id ==0)
		    {
		        return $this->returnAjax(array('status'=>0,'message'=>'备忘不存在'));
		    }
		    $data = $dbMemo->getOneMemo($memo_id,$uid);
		    $data['memo_moment'] = date('H:i',$data['memo_time']);
		    $data['memo_time'] = date('Y-m-d',$data['memo_time']);
            return $this->returnAjax(array('status'=>1,'data'=>$data));
		}
		$memo_id = I('post.memo_id',0,'intval');
		$notice_content = I('post.content','','string');
		if(!empty($notice_content))
		{
		    $data['notice_content'] = $notice_content;
		}
		$notice_time = I('post.notice_time','');
		$memo_time = I('post.memo_time','');
		if(!empty($memo_time))
		{
		    $memo_time = strtotime($memo_time);
		    $data['memo_time'] = $memo_time;//备忘时间
		    if($memo_time < $_SERVER['REQUEST_TIME'])
		    {
		        $notice_time = 6;//备忘时间为过去 ，默认就是不提醒
		    }
		}

// 		if(empty($memo_time) || empty($notice_time) || empty($notice_content))
// 		{
// 		    return $this->returnAjax(array('status'=>0,'message'=>'填写不完整'));
// 		}
		if($notice_time == 6)
		{
		    $is_notice = 0;//不提醒
		}else
		{
		    $is_notice = 1;//提醒
		}
		$data['is_notice'] = $is_notice;
		$data['create_time'] = $_SERVER['REQUEST_TIME'];
		$data['create_uid'] = $this->getUser()['user_id'];
		$data['notice_time'] = $notice_time;
		$dbMemo = new \Common\Model\Erp\Memo();//print_r($data);
		if(!$dbMemo->editMemo($memo_id,$uid,$data))
		{
		    return $this->returnAjax(array('status'=>0,'message'=>'编辑失败'));
		}
		$rdata['memo_time'] = date('Y-m-d',$memo_time);
		$rdata['memo_moment'] = date('H:i',$memo_time);
		$rdata['memo_id'] = $memo_id;
		$rdata['notice_content'] = $notice_content;
		return $this->returnAjax(array('status'=>1,'data'=>$rdata));
	}
	/**
	 * 删除备忘
	 * @author too|编写注释时间 2015年5月13日 下午1:24:59
	 */
	protected function deleteAction(){
		$memo_id = I('get.memo_id',0,'intval');
		if($memo_id ==0)
		{
		    return $this->returnAjax(array('status'=>0,'message'=>'备忘不存在'));
		}
		$dbMemo = new \Common\Model\Erp\Memo();
		$uid = $this->getUser()['user_id'];
		if(!$dbMemo->delMemo($memo_id,$uid))
		{
		    return $this->returnAjax(array('status'=>0,'message'=>'删除失败'));
		}
		return $this->returnAjax(array('status'=>1));
	}
	/**
	 * 备忘录列表页
	 * @author too|编写注释时间 2015年5月13日 下午1:24:45
	 */
	public function listAction($where = array()){
	    $page = I('get.page',1,'intval');
	    /* if($page ==0)
	    {
	        return $this->returnAjax(array('status'=>0,'message'=>'页码错误'));
	    } */
	    $dbMemo = new \Common\Helper\Erp\Memo();
	    //$where = array('create_uid'=>$this->getUser()['user_id']);
	    $where['create_uid'] = $this->getUser()['user_id'];
	    $size = 200;
	    //$order = array('memo_id desc');
	    $order = array('memo_time asc');
	    $list = $dbMemo->getList($page,$size,$where,$order);
	    foreach($list['data'] as $k=>$v)
	    {
	        if($v['notice_time'] == 1)
	        {//按时提醒
	            $list['data'][$k]['memo_time'] = date('Y-m-d',$v['memo_time']);
	            $list['data'][$k]['memo_moment'] = date('H:i',$v['memo_time']);
	            $list['data'][$k]['sorttmp'] = $v['memo_time'];
	        }elseif($v['notice_time'] == 2)
	        {//提前5分钟
	            $temptime = $v['memo_time'] - 300;
	            $list['data'][$k]['memo_time'] = date('Y-m-d',$temptime);
	            $list['data'][$k]['memo_moment'] = date('H:i',$temptime);
	            $list['data'][$k]['sorttmp'] = $temptime;
	        }elseif($v['notice_time'] == 3)
	        {//提前10分钟
	            $temptime = $v['memo_time'] - 600;
	            $list['data'][$k]['memo_time'] = date('Y-m-d',$temptime);
	            $list['data'][$k]['memo_moment'] = date('H:i',$temptime);
	            $list['data'][$k]['sorttmp'] = $temptime;
	        }elseif($v['notice_time'] == 4)
	        {//提前半小时
	            $temptime = $v['memo_time'] - 1800;
	            $list['data'][$k]['memo_time'] = date('Y-m-d',$temptime);
	            $list['data'][$k]['memo_moment'] = date('H:i',$temptime);
	            $list['data'][$k]['sorttmp'] = $temptime;
	        }elseif($v['notice_time'] == 5)
	        {//提前1小时
	            $temptime = $v['memo_time'] - 3600;
	            $list['data'][$k]['memo_time'] = date('Y-m-d',$temptime);
	            $list['data'][$k]['memo_moment'] = date('H:i',$temptime);
	            $list['data'][$k]['sorttmp'] = $temptime;
	        }elseif($v['notice_time'] == 6)
	        {//不提醒
	            $list['data'][$k]['memo_time'] = date('Y-m-d',$v['memo_time']);
	            $list['data'][$k]['memo_moment'] = 0;
	            $list['data'][$k]['sorttmp'] = 0;
	        }
	    }
	    //P($list['data']);
// 	    [memo_id] => 22
// 	    [memo_time] => 2015-06-15
// 	    [notice_time] => 3
// 	    [is_notice] => 1
// 	    [notice_content] => 地方顶顶顶顶
// 	    [create_uid] => 20
// 	    [create_time] => 1433573138
// 	    [memo_moment] => 14:35
// 	    [sorttmp] => 1434371734
	    $guoqi = array();
	    $weiguoqi = array();
        foreach($list['data'] as $k=>$v)
        {
            if($v['sorttmp'] < $_SERVER['REQUEST_TIME'])//过期
            {
                $guoqi[$k]['memo_id'] = $v['memo_id'];
                $guoqi[$k]['memo_time'] = $v['memo_time'];
                $guoqi[$k]['notice_time'] = $v['notice_time'];
                $guoqi[$k]['is_notice'] = $v['is_notice'];
                $guoqi[$k]['notice_content'] = $v['notice_content'];
                $guoqi[$k]['create_uid'] = $v['create_uid'];
                $guoqi[$k]['create_time'] = $v['create_time'];
                $guoqi[$k]['memo_moment'] = $v['memo_moment'];
                $guoqi[$k]['sorttmp'] = $v['sorttmp'];
            }else //未过期
            {
                $weiguoqi[$k]['memo_id'] = $v['memo_id'];
                $weiguoqi[$k]['memo_time'] = $v['memo_time'];
                $weiguoqi[$k]['notice_time'] = $v['notice_time'];
                $weiguoqi[$k]['is_notice'] = $v['is_notice'];
                $weiguoqi[$k]['notice_content'] = $v['notice_content'];
                $weiguoqi[$k]['create_uid'] = $v['create_uid'];
                $weiguoqi[$k]['create_time'] = $v['create_time'];
                $weiguoqi[$k]['memo_moment'] = $v['memo_moment'];
                $weiguoqi[$k]['sorttmp'] = $v['sorttmp'];
            }
        }
	    $gqsorttmp = array();
	    foreach($guoqi as $k=>$v)
    	    {
                $gqsorttmp[$k] = $v['memo_time'];
    	    }
    	array_multisort($gqsorttmp,$guoqi);//最终的排序完成的过期

    	$wgqsorttmp = array();
    	foreach($weiguoqi as $k=>$v)
    	{
    	    $wgqsorttmp[$k] = $v['sorttmp'];
    	}
    	array_multisort($wgqsorttmp,$weiguoqi);//最终的排序完成的过期
        if(!empty($guoqi) || !empty($weiguoqi))
        {
            return $this->returnAjax(array('status'=>1,'datagq'=>$guoqi,'datawgq'=>$weiguoqi,'page'=>$list['page']));
        }
        return $this->returnAjax(array('status'=>0,'message'=>'无符合条件数据'));

	}
}