<?php
namespace App\Web\Helper;
/**
 * 住房
 * @author lishengyou
 * 最后修改时间 2015年3月30日 下午2:22:31
 *
 */
class Housing extends \Common\Helper\Erp\Housing{
	/**
	 * 取得实体
	 * @author lishengyou
	 * 最后修改时间 2015年3月30日 下午4:53:24
	 *
	 * @param array $data
	 */
	public static function getHousingInfo(array $data){
		if(empty($data)) return array();
		$dataids = array();
		foreach ($data as $value){
			if(!isset($dataids[$value[0]])){
				$dataids[$value[0]] = array();
			}
			$dataids[$value[0]] = $value[1];
		}
		$datainfo = array();
		//整理数据
		foreach ($dataids as $key => $value){
			$helper = null;
			switch ($key){
				case self::CENTRALIZED:
					$helper = new \App\Web\Helper\Centralized\Room();
				break;
				case self::DISTRIBUTED_ENTIRE:
					$helper = new \App\Web\Helper\Distributed\Entire();
				break;
				case self::DISTRIBUTED_ROOM:
					$helper = new \App\Web\Helper\Distributed\Room();
				break;
			}
			if($helper){
				$datainfo[$key] = $helper->getInfoByIds($value);
			}
		}
		return $datainfo[$key];
	}
}