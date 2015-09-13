<?php
namespace Core\Ui\Form\Select;
/**
 * 下拉框
 * @author lishengyou
 * 最后修改时间 2015年3月3日 下午1:37:27
 *
 */
class Optgroup extends \Core\Ui\Form\Select\OptionAdaptation{
	public function getTagName(){
		return 'optgroup';
	}
	/**
	 * 插入借点
	 * @author lishengyou
	 * 最后修改时间 2015年3月3日 下午1:07:32
	 *
	 * @param \Core\Ui $ui
	 */
	public function appendChild(\Core\Ui\Form\Select\Option $option){
		return parent::appendChild($option);
	}
}