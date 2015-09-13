<?php
namespace Core\Program;
/**
 * 任务
 * @author lishengyou
 * 最后修改时间 2015年3月2日 下午4:59:25
 *
 */
final class Task{
	protected $_program = null;
	protected $_callback = null;
	protected $_args = null;
	public function __construct($task_id){
		$this->_taskid = $task_id;
		$object = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2);
		$object = end($object);
		if($object['object'] instanceof \Core\Program){
			$this->_program = $object['object'];
		}
	}
	protected $_allowreplace = false;
	/**
	 * 是否允许替换
	 * @author lishengyou
	 * 最后修改时间 2015年2月28日 下午3:44:22
	 *
	 * @param string $boolean
	 * @return boolean
	 */
	public function allowReplace($boolean=null){
		if(is_null($boolean)){
			return $this->_allowreplace;
		}
		$this->_allowreplace = !!$boolean;
	}
	
	protected $_allowinsertfront = false;
	/**
	 * 是否允许向前插入
	 * @author lishengyou
	 * 最后修改时间 2015年2月28日 下午3:44:22
	 *
	 * @param string $boolean
	 * @return boolean
	 */
	public function allowFrontInsert($boolean=null){
		if(is_null($boolean)){
			return $this->_allowinsertfront;
		}
		$this->_allowinsertfront = !!$boolean;
	}
	
	protected $_allowinsertpost = false;
	/**
	 * 是否允许向后插入
	 * @author lishengyou
	 * 最后修改时间 2015年2月28日 下午3:44:22
	 *
	 * @param string $boolean
	 * @return boolean
	 */
	public function allowPostInsert($boolean=null){
		if(is_null($boolean)){
			return $this->_allowinsertpost;
		}
		$this->_allowinsertpost = !!$boolean;
	}
	/**
	 * 取得程序
	 * @author lishengyou
	 * 最后修改时间 2015年3月3日 上午9:15:33
	 *
	 * @return \Core\Program
	 */
	public function getProgram(){
		return $this->_program;
	}
	/**
	 * 绑定回调
	 * @author lishengyou
	 * 最后修改时间 2015年3月2日 下午5:06:08
	 *
	 * @param unknown $callback
	 */
	public function setCallback($callback){
		$ec = new \Core\Event\Callback();
		if($ec->bind($callback)){
			$this->_callback = $ec;
		}
	}
	/**
	 * 设置参数
	 * @author lishengyou
	 * 最后修改时间 2015年3月2日 下午5:07:31
	 *
	 * @param unknown $args
	 */
	public function setArgs($args){
		$this->_args = $args;
	}
	/**
	 * 触发
	 * @author lishengyou
	 * 最后修改时间 2015年3月2日 下午5:09:04
	 *
	 * @param string $e
	 * @return NULL
	 */
	public function trigger($e=null){
		if($this->_callback){
			$result = $this->_callback->trigger(array($e,$this->_args));
			return $result;
		}
	}
	protected $_taskid = null;
	/**
	 * 取得程序ID
	 * @author lishengyou
	 * 最后修改时间 2015年2月28日 下午3:48:26
	 *
	 * @return string
	 */
	public function getTaskId(){
		return $this->_taskid ? $this->_taskid : spl_object_hash($this);
	}
}