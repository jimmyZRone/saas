<?php
namespace Core\Program;
/**
 * 队列
 * @author lishengyou
 * 最后修改时间 2015年2月28日 下午3:31:36
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
	public function push(\Core\Program $program){
		$programId = $program->getProgramId();
		if(!isset($this->_queue[$programId])){
			$this->_queue[$programId] = $program;
			$this->_event->trigger('push_'.$program->getProgramId(),$program);
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
	public function isexist($programId){
		return isset($this->_queue[$programId]);
	}
	/**
	 * 替换程序
	 * @author lishengyou
	 * 最后修改时间 2015年2月28日 下午3:39:54
	 *
	 * @param unknown $oldProgramId
	 * @param \Core\Program $program
	 */
	public function replace($programId,\Core\Program $program){
		if(!$this->isexist($programId)){
			return false;
		}
		$oldProgram = $this->_queue[$programId];
		$nowRank = \Core\Program::getProgramRank($program);
		$oldRank = \Core\Program::getProgramRank($oldProgram);
		
		if($nowRank < $oldRank || $oldProgram->allowReplace()){
			//新程序级别权限高或者旧程序允许被替换
			$this->_queue[$programId] = $program;
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
	public function insert($programId,\Core\Program $program,$direction){
		if(!isset($this->_queue[$programId])){
			return false;//程序不存在
		}
		
		//判断是否有权限插入
		$oldProgram = $this->_queue[$programId];
		$nowRank = \Core\Program::getProgramRank($program);
		$oldRank = \Core\Program::getProgramRank($oldProgram);
		if($direction == self::INSERT_FRONT &&  $nowRank > $oldRank && !$oldProgram->allowFrontInsert()){
			return false;//当前任务比原任务权限小，并且原任务不允许前置插入
		}else if($direction == self::INSERT_POST && $nowRank > $oldRank && !$oldProgram->allowPostInsert()){
			return false;//当前任务比原任务权限小，并且原任务不允许后置插入
		}
		switch ($direction){
			case self::INSERT_FRONT:
				$newReplace = array($program->getProgramId()=>$program,$programId=>$oldProgram);
				\Core\ArrayObject::splice($this->_queue, $programId, 1,$newReplace);
				break;
			case self::INSERT_POST:
				$newReplace = array($programId=>$oldProgram,$program->getProgramId()=>$program);
				\Core\ArrayObject::splice($this->_queue, $programId, 1,$newReplace);
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
	public function delayInsert($programId,\Core\Program $program,$direction){
		if(isset($this->_queue[$programId])){
			$this->insert($programId, $program, $direction);
		}else{
			$packet = func_get_args();
			$queue = $this;
			$this->_event->bind("push_{$programId}", function($e) use($queue,$packet){
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
	public function delayReplace($programId,\Core\Program $program){
		if(isset($this->_queue[$programId])){
			$this->replace($programId, $program);
		}else{
			$packet = func_get_args();
			$queue = $this;
			$this->_event->bind("push_{$programId}", function($e) use($queue,$packet){
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