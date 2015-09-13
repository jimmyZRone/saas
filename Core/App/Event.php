<?php
namespace Core\App;
class Event{
	protected static $_event = null;
	protected static function initEvent(){
		if(is_null(self::$_event)){
			self::$_event = new \Core\Event();
		}
	}
	protected static $_apply_scope = array();
	/**
	 * 申请一个事件
	 * @author lishengyou
	 * 最后修改时间 2015年3月2日 下午2:29:46
	 *
	 * @param 事件名称 $event
	 * @param 可调用的作用域 $callbackscope
	 */
	public static function apply($event,$callbackscope=null){
		if(isset(self::$_apply_scope[$event])){
			return false;//已经被申请过了
		}
		if(!$callbackscope){
			$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);
			$backtrace = end($backtrace);
			if(isset($backtrace['class']) && $backtrace['class']){
				$backtrace['function'] = $backtrace['class'].'::'.$backtrace['function'];
			}
			$callbackscope = $backtrace['function'];
		}
		self::$_apply_scope[$event] = $callbackscope;
		return true;
	}
	/**
	 * 绑定事件
	 * @author lishengyou
	 * 最后修改时间 2015年3月2日 上午9:21:39
	 *
	 * @param string $event
	 * @param callback $callback
	 */
	public static function bind($event,$callback){
		self::initEvent();
		return self::$_event->bind($event, $callback);
	}
	/**
	 * 解绑事件
	 * @author lishengyou
	 * 最后修改时间 2015年3月2日 上午9:22:07
	 *
	 * @param string $event
	 * @param callback $callback
	 */
	public static function unbind($event,$callback){
		self::initEvent();
		return self::$_event->unbind($event,$callback);
	}
	/**
	 * 验证触发事件的合法性
	 * @author lishengyou
	 * 最后修改时间 2015年3月2日 下午2:41:37
	 *
	 * @param unknown $event
	 */
	protected static function triggerScopeLegal($event){
		if(!isset(self::$_apply_scope[$event])){
			return true;//不需要验证
		}
		//需要验证作用域
		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,3);
		$backtrace = end($backtrace);
		if(isset($backtrace['class']) && $backtrace['class']){
			$backtrace['function'] = $backtrace['class'].'::'.$backtrace['function'];
		}
		$scope = self::$_apply_scope[$event];
		$scope = is_array($scope) ? $scope : array($scope);
		$isLegal = false;
		foreach ($scope as $_scope){
			if($_scope == $backtrace['function']){
				$isLegal = true;
				break;
			}
		}
		return $isLegal;
	}
	/**
	 * 触发事件
	 * @author lishengyou
	 * 最后修改时间 2015年3月2日 上午9:22:32
	 *
	 * @param string $event
	 * @param string $e
	 * @param int $type
	 * @param callback $callback
	 */
	public static function trigger($event,$e = null,$type=\Core\Event::EVENT_NOTICE,$callback=null){
		//取得调用作用域
		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);
		$backtrace = end($backtrace);
		if(isset($backtrace['class']) && $backtrace['class']){
			$backtrace['function'] = $backtrace['class'].'::'.$backtrace['function'];
		}
		if(!self::triggerScopeLegal($event)){
			//触发作用域不合法
			if(\Core\Config::get('site:debug')){
				echo "触发事件{$event}时，发现非法作用域。";
			}
			return false;
		}
		self::initEvent();
		if($callback){
			self::bind($event, $callback);
		}
		$e = array('backtrace'=>$backtrace['function'],'e'=>$e);
		if($type == \Core\Event::EVENT_NOTICE && is_object($e['e'])){
			$e['e'] = clone $e['e'];
		}
		return self::$_event->trigger($event,$e,$type);
	}
}