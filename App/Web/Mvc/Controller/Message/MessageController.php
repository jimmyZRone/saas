<?php
namespace App\Web\Mvc\Controller\Message;
use App\Web\Lib\Request;
use App\Web\Helper\Url;
class MessageController extends \App\Web\Lib\Controller {
    /**
     * 消息列表
     * 修改时间 2015年5月18日09:33:08
     * 
     * @author ft
     */
    protected function messageAction() {
        $user_id = $this->user['user_id'];
        $size = 20;
        $page = I('get.page', 0);
        $start_time = strtotime(date('Y-m-d H:i:s',strtotime(I('get.send_start_time', ''))));//开始日期
        $end_time = strtotime(I('get.send_end_time', ''));    //结束日期
        $message_model = new \Common\Model\Erp\Message();
        if ($page) {
            $message_list = $message_model->getMessageList($user_id, $page, $size, $start_time, $end_time);
            $this->assign('view', I('get.view_type', 'data'));
            if ($message_list['data']) {
                $this->assign('message_list', $message_list['data']);
                //is_read 0表示未读, 1表示已读
                $change_data['is_read'] = 1;
                //将已经显示的消息列表 标识为已读
                foreach ($message_list['data'] as $list) {
                    $where['message_id'] = $list['message_id'];
                    $message_model->edit($where, $change_data);
                }
                $data = $this->fetch('message_list');
                return $this->returnAjax(
                        array("status" => 1,
                                "tag_name" => "消息列表",
                                "model_js" => "message_listJs",
                                "model_href" => URL::parse("Message-message/message"),
                                "data" => $data,
                                'page_info' => $message_list['page'],
                ));
            } else {
                $data = $this->fetch('message_list');
                return $this->returnAjax(
                        array("status" => 0,
                                "tag_name" => "消息列表",
                                "model_js" => "message_listJs",
                                "model_href" => URL::parse("Message-message/message"),
                                "data" => $data,
                                'page_info' => $message_list['page'],
                        ));
            }
        } else {
            $this->assign('view', I('get.view_type', 'template'));
            $data = $this->fetch('message_list');
            return $this->returnAjax(
                    array("status" => 1,
                            "tag_name" => "消息列表",
                            "model_js" => "message_listJs",
                            "model_href" => URL::parse("Message-message/message"),
                            "data" => $data,
                    ));
        }
    }
    
    /**
     * 删除消息
     * 修改时间 2015年5月18日14:37:30
     * 
     * @author ft
     */
    protected function deletemessageAction() {
        $message_id = I('post.uid', 0);
        $message_model = new \Common\Model\Erp\Message();
        if (is_array($message_id)) {//是数组代表批量删除
            $where = array('message_id' => $message_id['mess_id']);
            $data = array('is_delete' => 1);
        } else {//单条删除
            $where = array('message_id' => $message_id);
            $data = array('is_delete' => 1);
        }
        $message_model->Transaction();
        $del_mess_res = $message_model->edit($where, $data);
        if (!$del_mess_res) {
            $message_model->rollback();
            return $this->returnAjax(array('status' => 0, 'message' => '删除消息失败!'));
        }
            $message_model->commit();
            return $this->returnAjax(array('status' => 1, 'message' => '删除消息成功!'));
    }
}