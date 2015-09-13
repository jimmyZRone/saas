<?php
namespace Core\Ui;
/**
 * 文本节点
 * @author lishengyou
 * 最后修改时间 2015年3月3日 下午6:41:21
 *
 */
class Text extends DoubleLabel{
	public function getTagName(){
		return 'text';
	}
	/**
	 * 取得HTML(non-PHPdoc)
	 * @author lishengyou
	 * 最后修改时间 2015年3月3日 上午11:46:04
	 *
	 * @see \Core\Ui::outerHTML()
	 */
	public function outerHTML(){
		return $this->innerHTML();
	}
}