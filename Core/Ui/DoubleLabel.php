<?php
namespace Core\Ui;
/**
 * 双标签
 * @author lishengyou
 * 最后修改时间 2015年3月3日 上午11:35:05
 *
 */
abstract class DoubleLabel extends \Core\Ui{
	protected $_html = '';
	/**
	 * 插入借点
	 * @author lishengyou
	 * 最后修改时间 2015年3月3日 下午1:07:32
	 *
	 * @param \Core\Ui $ui
	 */
	public function appendChild(\Core\Ui $ui){
		if(!is_array($this->_html)){
			$this->_html = array();
		}
		$this->_html[$ui->getUid()] = $ui;
		$ui->setParentChild($this);
		return $this;
	}
	/**
	 * 插入到之前
	 * @author lishengyou
	 * 最后修改时间 2015年3月4日 上午10:31:47
	 *
	 * @param unknown $uid
	 * @param \Core\Ui $ui
	 */
	public function insertBefore($uid,\Core\Ui $ui){
		if(!is_array($this->_html) || !isset($this->_html[$uid])){
			return $this;
		}
		\Core\ArrayObject::splice($this->_html, $uid, 1, array($ui->getUid()=>$ui,$uid=>$this->_html[$uid]));
		return $this;
	}
	/**
	 * 插入到之后
	 * @author lishengyou
	 * 最后修改时间 2015年3月4日 上午10:34:25
	 *
	 * @param unknown $uid
	 * @param \Core\Ui $ui
	 */
	public function insertAfter($uid,\Core\Ui $ui){
		if(!is_array($this->_html) || !isset($this->_html[$uid])){
			return $this;
		}
		\Core\ArrayObject::splice($this->_html, $uid, 1, array($uid=>$this->_html[$uid],$ui->getUid()=>$ui));
		return $this;
	}
	/**
	 * 取得所有节点
	 * @author lishengyou
	 * 最后修改时间 2015年3月3日 下午1:17:59
	 *
	 * @return string
	 */
	public function getChilds(){
		return $this->_html;
	}
	/**
	 * 删除节点
	 * @author lishengyou
	 * 最后修改时间 2015年3月3日 下午1:11:23
	 *
	 * @param unknown $uid
	 */
	public function removeChild($uid){
		if(is_array($this->_html) && isset($this->_html[$uid])){
			unset($this->_html[$uid]);
		}
		return $this;
	}
	/**
	 * 查找节点
	 * @author lishengyou
	 * 最后修改时间 2015年3月4日 上午9:59:19
	 *
	 * @param unknown $uid
	 */
	public function findUid($uid,$recurrence=true){
		if(is_string($this->_html)){
			return false;
		}
		if(!$recurrence){
			if(isset($this->_html[$uid])){
				return $this->_html[$uid];
			}
		}else{
			$nodes = array();
			foreach ($this->_html as $key => $value){
				if($key == $uid){
					$nodes[] = $value;
				}
				if($value instanceof DoubleLabel){
					$_nodes = $value->findUid($uid,$recurrence);
					if($_nodes && is_array($_nodes)){
						$nodes = array_merge($nodes,$_nodes);
					}else if($_nodes){
						$nodes[] = $_nodes;
					}
				}
			}
			return $nodes;
		}
	}
	/**
	 * 查找节点
	 * @author lishengyou
	 * 最后修改时间 2015年3月4日 上午10:09:49
	 *
	 * @param unknown $attrname
	 * @param string $recurrence
	 */
	public function findAttribute($attrname,$value=null,$recurrence=true){
		if(is_string($this->_html)){
			return false;
		}
		$nodes = array();
		foreach ($this->_html as $key => $node){
			if($node->hasAttribute($attrname) && (!$value || $value == $node->getAttribute($attrname))){
				$nodes[] = $node;
			}
			if($recurrence && $node instanceof DoubleLabel){
				$_nodes = $node->findAttribute($attrname,$value,$recurrence);
				if($_nodes){
					$nodes = array_merge($nodes,$_nodes);
				}
			}
		}
		return $nodes;
	}
	/**
	 * 取得或设置标签HTML
	 * @author lishengyou
	 * 最后修改时间 2015年3月3日 下午12:53:39
	 *
	 * @param string $html
	 * @return string|\Core\Ui\DoubleLabel
	 */
	public function innerHTML($html = null){
		if(is_null($html)){
			$html = '';
			if(is_string($this->_html)){
				$html = $this->_html;
			}else if(is_array($this->_html)){
				foreach ($this->_html as $value){
					$html .= $value->outerHTML();
				}
			}
			return $html;
		}else{
			$this->_html = $html;
			return $this;
		}
	}
	/**
	 * 取得HTML(non-PHPdoc)
	 * @author lishengyou
	 * 最后修改时间 2015年3月3日 上午11:46:04
	 *
	 * @see \Core\Ui::outerHTML()
	 */
	public function outerHTML(){
		$html = '<'.$this->getTagName();
		foreach ($this->getAttribute() as $key => $value){
			$value = str_replace('"', '\"', $value);
			$html .= " {$key}=\"{$value}\"";
		}
		$html .= '>';
		$html .= $this->innerHTML();
		$html .= '</'.$this->getTagName().'>';
		return $html;
	}
	/**
	 * CLONE
	 * @author lishengyou
	 * 最后修改时间 2015年3月4日 下午2:17:26
	 *
	 */
	public function __clone(){
		if(is_array($this->_html)){
			foreach ($this->_html as $key => $value){
				$this->_html[$key] = clone $value;
			}
		}
	}
}