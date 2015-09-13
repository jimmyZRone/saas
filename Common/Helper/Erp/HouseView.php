<?php
namespace Common\Helper\Erp;
/**
 * 集中式和分散式的视图
 * 修改时间 2015年5月7日15:55:08
 * 
 * @author ft
 */
class HouseView {
    /**
     * 根据房间名字查询房间
     * 修改时间 2015年5月7日15:57:16
     * 
     * @author ft
     */
    public function accordingRoomNameSearch($room_name, $user) {
        $house_view_model = new \Common\Model\Erp\HouseView();
        $sql = $house_view_model->getSqlObject();
        $select = $sql->select(array('hfv' => 'house_focus_view'));
        $select->columns(array('house_id' => 'house_id', 'record_id' => 'record_id', 'rental_way' => 'rental_way', 'house_name' => 'house_name'));
        /**
         * 权限
         */
        if($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER){
        	$permissions = \Common\Helper\Permissions::Factory('sys_housing_management');
        	$permisionsTable = $permissions->VerifyDataCollectionsPermissions($permissions::USER_AUTHENTICATOR, $user['user_id'],0);
        	$join = new \Zend\Db\Sql\Predicate\Expression('(hfv.auth_id=pa.authenticatee_id and hfv.house_id=0 and pa.source='.\Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED.') or (hfv.house_id=pa.authenticatee_id and hfv.house_id>0  and pa.source='.\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE.')');
        	$select->join(array('pa'=>new \Zend\Db\Sql\Predicate\Expression($permisionsTable)),$join,'authenticatee_id');
        }
        $where = new \Zend\Db\Sql\Where();
        $where->equalTo('company_id', $user['company_id']);
        $where->equalTo('city_id', $user['city_id']);
        $where->like('house_name', "%$room_name%");
        $where->equalTo('is_delete', 0);
        $select->where($where);
        $select->order('record_id');
        //print_r(str_replace('"', '', $select->getSqlString()));die();
        return $select->execute();
    }
}