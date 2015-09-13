<?php
namespace Common\Helper\Messagepush;
use Common\Helper\MessagepushInterface;
//         	$msg = array('msg'=>'来啊','title'=>'哼 哼 啦');
//         	$options = array(
//         		'type'=>1,
//         		'data'=>array()
//         	);
//         	$options = array(
//         			'type'=>2,
//         			'data'=>array(
//         				'title'=>'2',
//         				'date'=>'2015-7-10'
//         			)
//         	);
//$result = \Common\Helper\Messagepush::SendToUser(2, $msg, $options ,\Common\Helper\Messagepush::DEVICE_APP);
/**
 * 
 * @author lishengyou
 * 最后修改时间 2015年7月14日 下午9:07:53
 *
 */
class App implements MessagepushInterface{
	private $_appkeys = '3f68d0e72a026ebe642fef39';
	
	private $_masterSecret = 'a4e199ccd11e16765465fd2e';
	
	private function request_post($url = "", $param = "", $header = "")
	{
		if (empty($url) || empty($param)) {
			return false;
		}
		$postUrl = $url;
		$curlPost = $param;
		$ch = curl_init(); // 初始化curl
		curl_setopt($ch, CURLOPT_URL, $postUrl); // 抓取指定网页
		curl_setopt($ch, CURLOPT_HEADER, 0); // 设置header
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 要求结果为字符串且输出到屏幕上
		curl_setopt($ch, CURLOPT_POST, 1); // post提交方式
		curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		// 增加 HTTP Header（头）里的字段
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		// 终止从服务端进行验证
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		$data = curl_exec($ch); // 运行curl
	
		curl_close($ch);
		return $data;
	}
	/**
	 * 发送
	 * @author lishengyou
	 * 最后修改时间 2015年3月26日 下午2:40:04
	 *
	 * @param array $data
	 * @return boolean
	 */
	private function _send(array $data){
		$url = 'https://api.jpush.cn/v3/push';
		$base64 = base64_encode("$this->_appkeys:$this->_masterSecret");
		$header = array(
				"Authorization:Basic $base64",
				"Content-Type:application/json"
		);
		$res = $this->request_post($url, json_encode($data), $header);
		$res_arr = json_decode($res, true);
		return is_array($res_arr) && isset($res_arr['msg_id']) && isset($res_arr['sendno']) && $res_arr['sendno'] == '0';
	}
	
	/**
	 * 推送(non-PHPdoc)
	 * @author lishengyou
	 * 最后修改时间 2015年7月14日 下午2:47:30
	 *
	 * @see \Common\Helper\MessagepushInterface::send()
	 */
	public function send($user, $msg,array $options){
		if(is_array($msg)){
			extract($msg);
		}
		$user = is_array($user) ? $user : array($user);
		$userModel = new \Common\Model\Erp\User();
		$user = $userModel->getData(array('user_id'=>$user));
		$user = array_column($user, 'app_uuid');
		$user = array_filter($user);
		if(empty($user)){
			return false;      
		}
		$data = array(
				"platform"=>"all",
				"audience"=>array('registration_id'=>$user),
				"notification"=>array(
						"alert"=>$title
				),
				"message"=>array(
						"msg_content"=>$msg,
						"titlte"=>$title
				),
				'options'=>array(
						'apns_production'=>false
				)
		);
		if($options){
			$data["notification"]['extras'] = $options;
			$data["message"]['extras'] = $options;
		}
		$notification = $data["notification"];
		$data['notification'] = array('android'=>$notification,'ios'=>$notification);
		return $this->_send($data);
	}
}