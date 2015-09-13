<?php
namespace Core\Program\Task;
/**
 * 任务队列
 * @author lishengyou
 * 最后修改时间 2015年3月2日 下午5:49:59
 *
 */
class Queue{
	protected $_queue = array();
	protected $_event = null;
	public function __construct(){
		$this->_event = new \Core\Event();
	}
	/**
	 * 推入
	 * @author lishengyou
	 * 最后修改时间 2015年2月28日 下午3:35:54
	 *
	 * @param \Core\Program $program
	 */
	public function push(\Core\Program\Task $task){
		$taskId = $task->getTaskId();
		if(!isset($this->_queue[$taskId])){
			$this->_queue[$taskId] = $task;
			$this->_event->trigger('push_'.$taskId,$task);
			return true;
		}
		return false;
	}
	/**
	 * 程序是否存在
	 * @author lishengyou
	 * 最后修改时间 2015年2月28日 下午3:38:33
	 *
	 * @param unknown $programId
	 */
	public function isexist($taskId){
		return isset($this->_queue[$taskId]);
	}
	/**
	 * 替换程序
	 * @author lishengyou
	 * 最后修改时间 2015年2月28日 下午3:39:54
	 *
	 * @param unknown $oldProgramId
	 * @param \Core\Program $program
	 */
	public function replace($taskId,\Core\Program\Task $task){
		if(!$this->isexist($taskId)){
			return false;
		}
		$oldTask = $this->_queue[$taskId];
		$nowRank = \Core\Program::getProgramRank($task->getProgram());
		$oldRank = \Core\Program::getProgramRank($oldTask->getProgram());
	
		if($nowRank < $oldRank || $oldTask->allowReplace()){
			//新程序级别权限高或者旧程序允许被替换
			$this->_queue[$taskId] = $task;
			return true;
		}
		return false;
	}
	
	const INSERT_FRONT = 1;//之前
	const INSERT_POST = 2;//之后
	/**
	 * 替换
	 * @author lishengyou
	 * 最后修改时间 2015年2月28日 下午3:52:49
	 *
	 * @param unknown $programId
	 * @param \Core\Program $program
	 * @param unknown $direction
	 * @return boolean
	 */
	public function insert($taskId,\Core\Program\Task $task,$direction){
		if(!isset($this->_queue[$taskId])){
			return false;//程序不存在
		}
	
		//判断是否有权限插入
		$oldTask = $this->_queue[$taskId];
		$nowRank = \Core\Program::getProgramRank($task->getProgram());
		$oldRank = \Core\Program::getProgramRank($oldTask->getProgram());
		if($direction == self::INSERT_FRONT &&  $nowRank > $oldRank && !$oldTask->allowFrontInsert()){
			return false;//当前任务比原任务权限小，并且原任务不允许前置插入
		}else if($direction == self::INSERT_POST && $nowRank > $oldRank && !$oldTask->allowPostInsert()){
			return false;//当前任务比原任务权限小，并且原任务不允许后置插入
		}
		switch ($direction){
			case self::INSERT_FRONT:
				$newReplace = array($task->getTaskId()=>$task,$taskId=>$oldTask);
				\Core\ArrayObject::splice($this->_queue, $taskId, 1,$newReplace);
				break;
			case self::INSERT_POST:
				$newReplace = array($taskId=>$oldTask,$task->getTaskId()=>$task);
				\Core\ArrayObject::splice($this->_queue, $taskId, 1,$newReplace);
				break;
			default:
				return false;
				break;
		}
		return true;
	}
	/**
	 * 延时插入
	 * @author lishengyou
	 * 最后修改时间 2015年2月11日 下午3:18:54
	 *
	 * @param unknown $srcTaskId
	 * @param \Core\Program $program
	 * @param unknown $direction
	 */
	public function delayInsert($taskId,\Core\Program\Task $task,$direction){
		if(isset($this->_queue[$taskId])){
			$this->insert($taskId, $task, $direction);
		}else{
			$packet = func_get_args();
			$queue = $this;
			$this->_event->bind("push_{$taskId}", function($e) use($queue,$packet){
				$queue->insert($packet[0], $packet[1], $packet[2]);
			});
		}
	}
	/**
	 * 延时替换
	 * @author lishengyou
	 * 最后修改时间 2015年2月11日 下午3:18:18
	 *
	 * @param \Core\Program $program
	 */
	public function delayReplace($taskId,\Core\Program\Task $task){
		if(isset($this->_queue[$taskId])){
			$this->replace($taskId, $task);
		}else{
			$packet = func_get_args();
			$queue = $this;
			$this->_event->bind("push_{$taskId}", function($e) use($queue,$packet){
				$queue->replace($packet[0], $packet[1]);
			});
		}
	}
	/**
	 * 弹出队列的第一个
	 * @author lishengyou
	 * 最后修改时间 2015年2月11日 下午4:19:51
	 *
	 */
	public function shift(){
		return array_shift($this->_queue);
	}
}