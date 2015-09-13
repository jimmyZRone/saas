<?php
namespace Core\Program\Task;
/**
 * Task 运行时
 * @author lishengyou
 * 最后修改时间 2015年2月28日 下午4:54:15
 *
 */
class Runtime{
	protected $_queue = null;
	protected $_curtask = null;
	protected $_event = null;
	public function __construct(\Core\Program\Task\Queue $queue){
		$this->_queue = $queue;
	}
	/**
	 * 取得队列
	 * @author lishengyou
	 * 最后修改时间 2015年3月3日 上午9:56:53
	 *
	 * @return \Core\Program\Task\Queue
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
		while ($task = $this->_queue->shift()){
			if($task->getProgram()){
				$this->_curtask = $task;
				$task->trigger($task->getProgram());
			}
		}
	}
}