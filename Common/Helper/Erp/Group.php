<?php
namespace Common\Helper\Erp;
/**
 * 用户组
 * @author lishengyou
 * 最后修改时间 2015年3月26日 上午10:54:10
 *
 */
class Group extends \Core\Object{
	public static function add(array $data){
		$info = array('parent_id','company_id','name');
		$infoData = array_intersect_key($data, array_fill_keys($info, false));
		$infoData = array_filter($infoData,function($value){return $value || $value === 0;});
		$groupModel = new \Common\Model\Erp\Group();
		$parentData = false;
		if($infoData['parent_id']){//有上级分类，验证上级分类
			$parentData = $groupModel->getOne(array('group_id'=>$infoData['parent_id'],'company_id'=>$infoData['company_id']));
			if(!$parentData){
				self::setLastError('上级用户组错误');
				return false;
			}
		}
		$groupData = $groupModel->getOne(array('company_id'=>$infoData['company_id'],'name'=>$infoData['name']));
		if($groupData){//检测分组名称是否已经存在
			self::setLastError('用户组已经存在');
			return false;
		}
		$infoData['route'] = $parentData ? $parentData['route'].','.$parentData['group_id'] : '';
		$infoData['route'] = trim($infoData['route'],',');
		$infoData['create_time'] = time();
		$groupModel->Transaction();
		$group_res = $groupModel->insert($infoData);
		if ($group_res) {
		    $groupModel->commit();
		    return $group_res;
		}
            $groupModel->rollback();
            return false;
	}
	
	public static function edit($where, $data) {
	    $groupModel = new \Common\Model\Erp\Group();
	    if ($where && $data) {
	        $groupModel->Transaction();
            $res = $groupModel ->edit($where, $data);
            if ($res) {
                $groupModel->commit();
                return true;
            } else {
                $groupModel->rollback();
                return false;
            }
        } else {
            return false;
        }
    }
}