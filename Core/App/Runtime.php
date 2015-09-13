<?php
namespace Core\App;
/**
 * APP 运行时
 * @author lishengyou
 * 最后修改时间 2015年2月28日 下午4:54:15
 *
 */
class Runtime{
	protected $_queue = null;
	protected $_curprogram = null;
	protected $_event = null;
	public function __construct(\Core\Program\Queue $queue){
		$this->_queue = $queue;
	}
	/**
	 * 取得队列
	 * @author lishengyou
	 * 最后修改时间 2015年3月4日 下午1:16:06
	 *
	 * @return \Core\Program\Queue
	 */
	public function getQueue(){
		return $this->_queue;
	}
	/**
	 * 开始运行
	 * @author lishengyou
	 * 最后修改时间 2015年2月28日 下午5:01:59
	 *
	 */
	public function start(){
		//去拿程序的配置信息
		while ($program = $this->_queue->shift()){
			$config = $program->getConfig();
			if(!$config){//没有取得程序信息
				return false;
			}
			$this->_curprogram = $program;
			$callback = new \Core\Event\Callback();
			$callback->bind(array($program,'initRun'));
			$callback->trigger();
		}
	}
}