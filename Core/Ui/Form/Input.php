<?php
namespace Core\Ui\Form;
/**
 * Input
 * @author lishengyou
 * 最后修改时间 2015年3月3日 下午1:29:17
 *
 */
class Input extends \Core\Ui\SingleLabel{
	const BUTTON = 'button';
	const CHECKBOX = 'checkbox';
	const FILE = 'file';
	const HIDDEN = 'hidden';
	const IMAGE = 'image';
	const PASSWORD = 'password';
	const RADIO = 'radio';
	const RESET = 'reset';
	const SUBMIT = 'submit';
	const TEXT = 'text';
	public function getTagName(){
		return 'input';
	}
	/**
	 * 设置类型
	 * @author lishengyou
	 * 最后修改时间 2015年3月3日 下午1:34:04
	 *
	 * @param unknown $type
	 * @return \Core\Ui
	 */
	public function setType($type){
		return $this->setAttribute('type', $type);
	}
	/**
	 * 取得类型
	 * @author lishengyou
	 * 最后修改时间 2015年3月3日 下午1:34:09
	 *
	 * @return Ambigous <string, \Core\Ambigous>
	 */
	public function getType(){
		$type = $this->getAttribute('type');
		return $type ? $type : self::TEXT;
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