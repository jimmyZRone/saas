<?php
namespace Core\Ui;
/**
 * 未知双标签
 * @author lishengyou
 * 最后修改时间 2015年3月3日 下午3:20:02
 *
 */
class UnknownDLabel extends DoubleLabel{
	protected $_tagname = null;
	public function __construct($tagname){
		$this->_tagname = $tagname;
	}
	/**
	 * 取得标签名称(non-PHPdoc)
	 * @author lishengyou
	 * 最后修改时间 2015年3月3日 下午3:21:19
	 *
	 * @see \Core\Ui::getTagName()
	 */
	public function getTagName(){
		return $this->_tagname;
	}
}