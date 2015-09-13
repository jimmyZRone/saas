<?php
namespace Core;
abstract class Ui{
	protected $_uid = null;
	protected $_attribute = array();
	protected $_parent = null;
	/**
	 * 取得标签名称
	 * @author lishengyou
	 * 最后修改时间 2015年3月3日 上午11:32:52
	 *
	 */
	abstract public function getTagName();
	/**
	 * 取得HTML
	 * @author lishengyou
	 * 最后修改时间 2015年3月3日 上午11:40:55
	 *
	 */
	abstract public function outerHTML();
	/**
	 * 设置标签UID
	 * @author lishengyou
	 * 最后修改时间 2015年3月3日 上午11:32:59
	 *
	 * @param unknown $uid
	 * @return \Core\Ui
	 */
	public function setUid($uid){
		$this->_uid = $uid;
		return $this;
	}
	/**
	 * 取得标签UID
	 * @author lishengyou
	 * 最后修改时间 2015年3月3日 上午11:33:17
	 *
	 * @return unknown
	 */
	public function getUid(){
		if(!$this->_uid){
			$this->setUid(spl_object_hash($this));
		}
		return $this->_uid;
	}
	/**
	 * 设置标签属性
	 * @author lishengyou
	 * 最后修改时间 2015年3月3日 上午11:33:26
	 *
	 * @param unknown $attrname
	 * @param unknown $attrvalue
	 * @return \Core\Ui
	 */
	public function setAttribute($attrname,$attrvalue){
		$this->_attribute[$attrname] = $attrvalue;
		return $this;
	}
	/**
	 * 取得标签属性
	 * @author lishengyou
	 * 最后修改时间 2015年3月3日 上午11:33:39
	 *
	 * @param unknown $attrname
	 * @return Ambigous <string, multitype:>
	 */
	public function getAttribute($attrname=null){
		if(!$attrname){
			return $this->_attribute;
		}
		return isset($this->_attribute[$attrname]) ? $this->_attribute[$attrname] : '';
	}
	/**
	 * 判断属性是否存在
	 * @author lishengyou
	 * 最后修改时间 2015年3月4日 上午10:11:40
	 *
	 * @param unknown $attrname
	 */
	public function hasAttribute($attrname){
		return isset($this->_attribute[$attrname]);
	}
	/**
	 * 删除属性
	 * @author lishengyou
	 * 最后修改时间 2015年3月4日 上午9:56:20
	 *
	 * @param unknown $attrname
	 */
	public function removeAttribute($attrname){
		if(isset($this->_attribute[$attrname])){
			unset($this->_attribute[$attrname]);
		}
		return $this;
	}
	/**
	 * 取得父节点
	 * @author lishengyou
	 * 最后修改时间 2015年3月3日 下午1:14:08
	 *
	 */
	public function getParentChild(){
		return $this->_parent;
	}
	/**
	 * 设置父节点
	 * @author lishengyou
	 * 最后修改时间 2015年3月3日 下午1:15:00
	 *
	 * @param Ui $ui
	 * @return \Core\Ui
	 */
	public function setParentChild(Ui $ui){
		$object = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS,2);
		$object = end($object);
		if($object['object'] instanceof Ui){
			$this->_parent = $object['object'];
		}
		return $this;
	}
}