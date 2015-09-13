<?php
namespace Common\Helper;
use Common\Model\Erp\SmsRecord;
/**
 * 短信
 * @author lishengyou
 * 最后修改时间 2015年6月10日 上午9:36:34
 *
 */
class Sms{
	/**
	 * 发送短信
	 * @author lishengyou
	 * 最后修改时间 2015年6月10日 上午9:52:50
	 *
	 * @param unknown $content
	 * @param unknown $phone
	 * @param number $entity_id
	 * @param string $module_type
	 * @param number $source_id
	 * @return boolean|number
	 */
	public static function phone($content,$phone,$entity_id=0,$module_type='',$source_id=0){
		$smsRecord = new SmsRecord();
		$smsRecord->insert(array('phone'=>$phone,'content'=>$content,'entity_id'=>$entity_id,'module_type'=>$module_type,'source_id'=>$source_id,'create_time'=>time()));
		if(true){
			/**************************** Test ***********************************/
			$mail = new Mail();
			$mail->setServer("smtp.126.com", "ayoutest@126.com", "nqkjrixjudewqddr"); //到服务器的SSL连接
			//如果不需要到服务器的SSL连接，这样设置服务器：$mail->setServer("smtp.126.com", "XXX@126.com", "XXX");
			$mail->setFrom("ayoutest@126.com");
			$mail->setReceiver("lsy@jooozo.com");
			$mail->setMail("Phone:{$phone}",$content);
			$mail->sendMail();
			return true;
		}else{
			$uid = 'LKSDK0002348';
			//短信接口密码 $passwd
			$passwd = 'loudi2014';
			$client = new \SoapClient('http://mb345.com:999/ws/LinkWS.asmx?wsdl',array('encoding'=>'UTF-8'));
			$sendParam = array(
					'CorpID' => $uid,
					'Pwd' => $passwd,
					'Mobile' => $phone,
					'Content' => $content,
					'Cell' => '',
					'SendTime' => ''
			);
			$result = $client->BatchSend($sendParam);
			$result = $result->BatchSendResult;
			if($result == 0 ) {
				return 0;
				//echo '短信发送成功,等待审核!<br/>';
			} else if($result == 1) {
				return 1;
			}else{
				return false;
			}
			$client = null;
			return true;
		}
	}
}