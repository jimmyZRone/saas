<?php
include __DIR__.'/Pinyin.php';
/**
 * 下载地址:http://overtrue.me/pinyin/
 * 词库地址:http://www.mdbg.net/chindict/chindict.php?page=cedict
 * @author lishengyou
 * 最后修改时间 2015年5月9日 下午2:54:36
 *
 */
class Pinyin extends \Overtrue\Pinyin\Pinyin{
	/**
	 * add delimiter
	 *
	 * @param string $string
	 */
	protected function delimit($string, $delimiter = '')
	{
		$string = trim($string);
		$string = explode(' ', $string);
		$string = array_filter($string,function($value){return $value !== ' ';});
		$string = implode(' ', $string);
		$string = str_replace(' ', strval($delimiter), $string);
		return $string;
	}
}