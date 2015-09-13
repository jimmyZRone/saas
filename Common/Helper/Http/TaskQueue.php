<?php
namespace Common\Helper\Http;
/**
 * 队列服务器
 * @author lishengyou
 * 最后修改时间 2014年11月11日 下午1:56:35
 *
 */
class TaskQueue{
	public  function __construct(){
		$config = \Core\Config::get('site:queue');
		$this->server = $config;
	}
	public static function getInstance(){
		static $self = null;
		if(is_null($self))
			$self = new self();
		return $self;
	}
	protected $queue = array();
	protected $server = '';
	const IS_POST = 'post';
	const IS_GET = 'get';
	/**
	 * 添加任务
	 * Enter description here ...
	 * @param 回调地址 $callback
	 * @param 回调的时间 $runtime
	 */
	public function add($callback,$runtime,$type=NULL,$data=NULL){
		$data = $data ? $data : array();
		$len = strlen($runtime);
		if(!is_numeric($runtime)){
			return FALSE;
		}
		if($len == 10){
			$runtime = $runtime.'000';
			$len = 13;
		}
		if($len != 13){
			return FALSE;
		}
		switch($type){
			case self::IS_POST:
				$type = 'post';
				break;
			default:
				$type = 'get';
				break;
		}
		$arr = array('callback'=>$callback,'runtime'=>$runtime, 'method' => $type);
		if(is_array($data)){
			$arr['data'] = $data;
		}
		$this->queue[] = $arr;
		return TRUE;
	}
	/**
	 * 发送任务到服务器
	 * Enter description here ...
	 */
	public function send(){
		if(empty($this->queue)) return false;
		$output = NULL;
		$http = null;
		$ret = true;
		try{
			$data = rawurlencode(json_encode($this->queue));
			$this->queue = array();
			$http = curl_init();
			$server = $this->server;
			$server .= '?async=true&callback=callback&parameter='.$data;
			curl_setopt($http, CURLOPT_URL, $server);
			//curl_setopt($http,CURLOPT_POST,1);
			//设置接收返回的数据
			//curl_setopt($http,CURLOPT_RETURNTRANSFER,1);
			//post数据
			//curl_setopt($http,CURLOPT_POSTFIELDS, array('parameter'=>$data,'async'=>'true','callback'=>'callback'));
			//设置15秒超时
			curl_setopt($http,CURLOPT_TIMEOUT,10);
			//执行post
			$output = curl_exec($http);
			if(curl_errno($http)){
				Event::trigger('task_queue',array('action'=>'error','error'=>curl_error($http)));
			}
		}catch (\Exception $e){
			Event::trigger('task_queue',array('action'=>'error','error'=>$e->getMessage()));
			$ret = false;
		}
		if($ret && is_null($output)){
			$ret = false;
		}
		if(is_resource($http))
			curl_close($http);
		return $ret;
	}
	public function __destruct(){
		$this->send();
	}
}