<?php
namespace Core\Ui;
/**
 * 单标签
 * @author lishengyou
 * 最后修改时间 2015年3月3日 上午11:35:05
 *
 */
abstract class SingleLabel extends \Core\Ui{
	/**
	 * 取得HTML(non-PHPdoc)
	 * @author lishengyou
	 * 最后修改时间 2015年3月3日 上午11:41:56
	 *
	 * @see \Core\Ui::outerHTML()
	 */
	public function outerHTML(){
		$html = '<'.$this->getTagName();
		foreach ($this->getAttribute() as $key => $value){
			$value = str_replace('"', '\"', $value);
			$html .= " {$key}=\"{$value}\"";
		}
		$html .= ' />';
		return $html;
	}
}