<?php
namespace Core\Ui\Form;
/**
 * 下拉框
 * @author lishengyou
 * 最后修改时间 2015年3月3日 下午1:37:27
 *
 */
class Select extends \Core\Ui\DoubleLabel{
	public function getTagName(){
		return 'select';
	}
	/**
	 * 插入借点
	 * @author lishengyou
	 * 最后修改时间 2015年3月3日 下午1:07:32
	 *
	 * @param \Core\Ui $ui
	 */
	public function appendChild(\Core\Ui $option){
		if($option instanceof \Core\Ui\Form\Select\OptionAdaptation || $option instanceof \Core\Ui\Text){
			return parent::appendChild($option);
		}
	}
}