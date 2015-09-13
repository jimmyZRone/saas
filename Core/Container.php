<?php
namespace Core;
/**
 * 容器
 * @author lishengyou
 * 最后修改时间 2015年2月28日 下午2:13:20
 *
 */
class Container implements \Iterator,\ArrayAccess{
	
	protected $_data = array();
	protected $_position = 0;
	public function current(){
		$data = current($this->_data);
		list($key,$value) = each($data);
		return $value;
	}
	public function key(){
		$data = current($this->_data);
		list($key,$value) = each($data);
		return $key;
	}
	public function next(){
		next($this->_data);
		$this->_position++;
	}
	public function rewind(){
		reset($this->_data);
		$this->_position = 0;
	}
	public function valid(){
		return $this->_position < count($this->_data);
	}
	
	public function offsetSet($offset, $value){
		if(is_null($offset)) {
            $this->_data[] = $value;
            $this->_position++;
        }else if(!isset($this->_data[$offset])){
            $this->_data[$offset] = $value;
        }else{
        	$this->_data[$offset] = $value;
        	$this->_position++;
        }
	}
	public function offsetExists($offset){
		return isset($this->_data[$offset]);
	}
	public function offsetUnset($offset) {
		unset($this->_data[$offset]);
		$this->_position--;
	}
	public function offsetGet($offset) {
		return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
	}
	
	public function __call($method,$value){
		$operator = substr($method,0,3);
		if(strlen($method) > 3){
			$value = count($value) > 1 ? $value : reset($value);
			switch ($operator){
				case 'set':
					$offset = substr($method, 3);
					return $this->offsetSet($offset, $value);
					break;
				case 'get':
					$offset = substr($method, 3);
					return $this->offsetGet($offset);
					break;
			}
		}
	}
}