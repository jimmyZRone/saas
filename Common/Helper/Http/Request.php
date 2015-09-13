<?php
namespace Common\Helper\Http;
class Request{
	protected static function array_map_recursive($filter, $data) {
		$result = array();
		foreach ($data as $key => $val) {
			$result[$key] = is_array($val)
			? self::array_map_recursive($filter, $val)
			: call_user_func($filter, $val);
		}
		return $result;
	}
	/**
	 * 获取输入参数 支持过滤和默认值
	 * 使用方法:
	 * <code>
	 * queryString('id',0); 获取id参数 自动判断get或者post
	 * queryString('post.name','','htmlspecialchars'); 获取$_POST['name']
	 * queryString('get.'); 获取$_GET
	 * </code>
	 * @param string $name 变量的名称 支持指定类型
	 * @param mixed $default 不存在的时候默认值
	 * @param mixed $filter 参数过滤方法
	 * @param mixed $datas 要获取的额外数据源
	 * @return mixed
	 */
	public static function queryString($name,$default='',$filter=null,$datas=null){
		static $_PUT	=	null;
   
		if(strpos($name,'/')){ // 指定修饰符
			list($name,$type) 	=	explode('/',$name,2);
		}
		if(strpos($name,'.')) { // 指定参数来源
			list($method,$name) =   explode('.',$name,2);
		}else{ // 默认为自动判断
			$method =   'param';
		}
		switch(strtolower($method)) {
			case 'get'     :
				$input =& $_GET;
				break;
			case 'post'    :
				$input =& $_POST;
				break;
			case 'put'     :
				if(is_null($_PUT)){
					parse_str(file_get_contents('php://input'), $_PUT);
				}
				$input 	=	$_PUT;
				break;
			case 'param'   :
				switch($_SERVER['REQUEST_METHOD']) {
					case 'POST':
						$input  =  $_POST;
						break;
					case 'PUT':
						if(is_null($_PUT)){
							parse_str(file_get_contents('php://input'), $_PUT);
						}
						$input 	=	$_PUT;
						break;
					default:
						$input  =  $_GET;
				}
				break;
			case 'path'    :
				$input  =   array();
				if(!empty($_SERVER['PATH_INFO'])){
					$depr   =   '/';
					$input  =   explode($depr,trim($_SERVER['PATH_INFO'],$depr));
				}
				break;
			case 'request' :
				$input =& $_REQUEST;
				break;
			case 'session' :
				$input =& $_SESSION;
				break;
			case 'cookie'  :
				$input =& $_COOKIE;
				break;
			case 'server'  :
				$input =& $_SERVER;
				break;
			case 'globals' :
				$input =& $GLOBALS;
				break;
			case 'data'    :
				$input =& $datas;
				break;
			default:
				return null;
		}
		if(''==$name) { // 获取全部变量
			$data       =   $input;
			$filters    =   isset($filter)?$filter:'htmlspecialchars';
			if($filters) {
				if(is_string($filters)){
					$filters    =   explode(',',$filters);
				}
				foreach($filters as $filter){
					$data   =   self::array_map_recursive($filter,$data); // 参数过滤
				}
			}
		}elseif(isset($input[$name])) { // 取值操作
			$data       =   $input[$name];
			$filters    =   isset($filter)?$filter:'htmlspecialchars';
			if($filters) {
				if(is_string($filters)){
					if(0 === strpos($filters,'/') && 1 !== preg_match($filters,(string)$data)){
						// 支持正则验证
						return   isset($default) ? $default : null;
					}else{
						$filters    =   explode(',',$filters);
					}
				}elseif(is_int($filters)){
					$filters    =   array($filters);
				}

				if(is_array($filters)){
					foreach($filters as $filter){
						if(function_exists($filter)) {
							$data   =   is_array($data) ? self::array_map_recursive($filter,$data) : $filter($data); // 参数过滤
						}else{
							$data   =   filter_var($data,is_int($filter) ? $filter : filter_id($filter));
							if(false === $data) {
								return   isset($default) ? $default : null;
							}
						}
					}
				}
			}
        
       
			if(!empty($type)){
				switch(strtolower($type)){
					case 'a':	// 数组
						$data 	=	(array)$data;
						break;
					case 'd':	// 数字
						$data 	=	(int)$data;
						break;
					case 'f':	// 浮点
						$data 	=	(float)$data;
						break;
					case 'b':	// 布尔
						$data 	=	(boolean)$data;
						break;
					case 's':   // 字符串
					default:
						$data   =   (string)$data;
				}
			}
		}else{ // 变量默认值
			$data       =    isset($default)?$default:null;
		}
		$think_filter = function (&$value){
		    if(preg_match('/^(EXP|NEQ|GT|EGT|LT|ELT|OR|XOR|LIKE|NOTLIKE|NOT BETWEEN|NOTBETWEEN|BETWEEN|NOTIN|NOT IN|IN)$/i',$value)){
		        $value .= ' ';
		    }
		};
		is_array($data) && array_walk_recursive($data,$think_filter);
		return $data;
	}
	/**
	 * 是否是POST提交
	 * @author lishengyou
	 * 最后修改时间 2015年3月10日 下午4:57:06
	 *
	 * @return boolean
	 */
	public static function isPost(){
		return $_SERVER['REQUEST_METHOD'] == 'POST';
	}
	/**
	 * 是否是GET提交
	 * @author lishengyou
	 * 最后修改时间 2015年3月10日 下午4:57:16
	 *
	 * @return boolean
	 */
	public static function isGet(){
		return $_SERVER['REQUEST_METHOD'] == 'GET';
	}
	/**
	 * 是否是ajax提交
	 * @author lishengyou
	 * 最后修改时间 2015年3月10日 下午4:57:24
	 *
	 * @return boolean
	 */
	public static function isAjax(){
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || (isset($_REQUEST['HTTP_X_REQUESTED_WITH']) && strtolower($_REQUEST['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
	}
	/**
	 * 获取客户端IP地址
	 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
	 * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
	 * @return mixed
	 */
	public static function getClientIp($type = 0,$adv=false) {
		$type       =  $type ? 1 : 0;
		static $ip  =   NULL;
		if ($ip !== NULL) return $ip[$type];
		if($adv){
			if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
				$pos    =   array_search('unknown',$arr);
				if(false !== $pos) unset($arr[$pos]);
				$ip     =   trim($arr[0]);
			}elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
				$ip     =   $_SERVER['HTTP_CLIENT_IP'];
			}elseif (isset($_SERVER['REMOTE_ADDR'])) {
				$ip     =   $_SERVER['REMOTE_ADDR'];
			}
		}elseif (isset($_SERVER['REMOTE_ADDR'])) {
			$ip     =   $_SERVER['REMOTE_ADDR'];
		}
		// IP地址合法验证
		$long = sprintf("%u",ip2long($ip));
		$ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
		return $ip[$type];
	}
}