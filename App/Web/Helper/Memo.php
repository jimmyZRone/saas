<?php
namespace App\Web\Helper;
class Memo extends \Common\Model\Erp
{
    public function listData($uid,$where = array()){
        $page = 1;
        $dbMemo = new \Common\Helper\Erp\Memo();
        $where['create_uid'] = $uid;
        $size = 200;
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
        $sorttmp = array();
        foreach($list['data'] as $k=>$v)
        {
            $sorttmp[$k] = $v['sorttmp'];
        }
        array_multisort($sorttmp,$list['data']);//P($list);
        return $list;

    }
}