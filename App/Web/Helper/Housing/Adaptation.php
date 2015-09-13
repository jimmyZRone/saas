<?php
namespace App\Web\Helper\Housing;
interface Adaptation{
	/**
	 * 根据ID取信息
	 * @author lishengyou
	 * 最后修改时间 2015年3月30日 下午5:10:28
	 *
	 * @param array $ids
	 */
	public function getInfoByIds(array $ids);
}