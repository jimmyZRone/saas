<?php
namespace Core\Ui\Form;
/**
 * Input
 * @author lishengyou
 * 最后修改时间 2015年3月3日 下午1:29:17
 *
 */
class Textarea extends \Core\Ui\DoubleLabel{
	public function getTagName(){
		return 'textarea';
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
			return $this->innerHTML($value);
		}
		return $this->innerHTML();
	}
}