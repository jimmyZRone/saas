<?php
namespace Core\Ui\Form\Select;
/**
 * 下拉框
 * @author lishengyou
 * 最后修改时间 2015年3月3日 下午1:37:27
 *
 */
class Option extends \Core\Ui\Form\Select\OptionAdaptation{
	public function getTagName(){
		return 'option';
	}
	/**
	 * 设置或取得值
	 * @author lishengyou
	 * 最后修改时间 2015年3月3日 下午1:34:32
	 *
	 * @param string $vlaue
	 */
	public function value($vlaue = null){
		if(is_null($value)){
			return $this->getAttribute('value');
		}
		return $this->setAttribute('value', $value);
	}
}