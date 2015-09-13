<?php
namespace Core;
/**
 * 数组
 * @author lishengyou
 * 最后修改时间 2015年2月9日 下午4:03:34
 *
 */
class ArrayObject{
	/**
	 * 把数组中的一部分去掉并用其它值取代。
	 * @author lishengyou
	 * 最后修改时间 2015年2月9日 下午4:17:31
	 *
	 * @param array $src
	 * @param string $key
	 * @param int $length
	 * @param array $replace
	 */
	public static function splice(array &$src,$key,$length,array $replace){
		$offset = 0;
		$cloneArray = array();
		foreach ($src as $k => $v){
			if($k === $key){
				$offset = 1;
			}
			if($offset > 0 && $offset === $length){
				$cloneArray = array_merge($cloneArray,$replace);
				$offset = 0;
			}else if($offset === 0){
				$cloneArray[$k] = $v;
			}else{
				$offset++;
			}
		}
		$src = $cloneArray;
	}
	/**
	 * 过滤相同的
	 * @author lishengyou
	 * 最后修改时间 2015年3月26日 下午1:51:36
	 *
	 * @param array $data
	 */
	public static function filterEqualKeyValue(array &$data){
		$filterData = func_get_args();
		$filterData = array_shift($filterData);
		$temp = array();
		foreach ($filterData as $filter_data){
			if(!is_array($filter_data)){
				continue;
			}
			foreach ($filter_data as $key => $value){
				if(isset($data[$key]) && $value === $data[$key]){
					$temp[$key] = $value;
					unset($data[$key]);
					if(empty($data)){break 2;}//当已经为空时，直接退出
				}
			}
		}
		return $temp;
	}
}