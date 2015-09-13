<?php

namespace Common\Model\Erp;

class Message extends \Common\Model\Erp {
    /**
     * 获取消息列表
     * 修改时间 2015年5月18日10:16:08
     * 
     * @author ft
     * @param $user_id int
     * @param $page int
     * @param $size int
     * @param $start_time string
     * @param $end_time string
     * return array
     */
    public function getMessageList($user_id, $page, $size, $start_time, $end_time) {
        //当天 0时 0分 0秒 今天开始时间
//         $today_start = strtotime(date('Y-m-d H:i:s', strtotime(date("Y-m-d"))));
        //当天当时时间
        $today_current = strtotime(date('Y-m-d H:i:s', time()));
        $sql = $this->getSqlObject();
        $select = $sql->select(array('m' => 'message'));
        $select->columns(
                array(
                        'message_id' => 'message_id', 
                        'template_id' => 'template_id', 
                        'from_user_id' => 'from_user_id',
                        'title' => 'title',
                        'content' => 'content',
                        'message_type' => 'message_type',
                        'create_time' => 'create_time'
        ));
        $select->leftjoin(array('ue' => 'user_extend'), 'm.from_user_id = ue.user_id', array('user_name' => 'name'));
       $where = new \Zend\Db\Sql\Where();
       //今天的消息
       if ($start_time != '' && $start_time == $end_time) {
           $where->greaterThanOrEqualTo('m.create_time', $start_time);
           $where->lessThanOrEqualTo('m.create_time', $today_current);
       }
       //最近一个月或者三个月的消息
       if ($start_time != '' && $start_time != $end_time) {
           $where->greaterThanOrEqualTo('m.create_time', $start_time);
           $where->lessThanOrEqualTo('m.create_time', $end_time);
       }
       $where->equalTo('m.from_user_id', 0);
       $where->equalTo('m.to_user_id', $user_id);

       $where->equalTo('m.is_delete', 0);
       $select->order("m.create_time desc");
       $select->where($where);

       return  \Core\Db\Sql\Select::pageSelect($select,null,$page, $size);
    }
    /**
     * 发送消息
     * 修改时间2015年6月29日16:33:30
     * 
     * @author yzx
     * @param unknown $data
     * @return Ambigous <number, unknown, boolean>
     */
    public function sendMessage($data){
    	$data['create_time'] = time();
    	return $this->insert($data);
    }
}