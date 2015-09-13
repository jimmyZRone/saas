<?php
namespace Core\Ui;
/**
 * Form表单
 * @author lishengyou
 * 最后修改时间 2015年3月3日 上午11:35:05
 *
 */
class Form extends \Core\Ui\DoubleLabel{
	/**
	 * 取得标签名称(non-PHPdoc)
	 * @author lishengyou
	 * 最后修改时间 2015年3月3日 上午11:35:33
	 *
	 * @see \Core\Ui::getTagName()
	 */
	public function getTagName(){
		return 'form';
	}
}