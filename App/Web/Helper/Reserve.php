<?php
namespace App\Web\Helper;
use Zend\Db\Sql\Expression;
class Reserve extends \Common\Model\Erp{
    /**
     * 添加预定
     * @param $data array
     * @author too|最后修改时间 2015年4月27日 下午4:35:59
     */
    public function addReserve($data){
        $Reserve_id = $this->insert($data);
        return $Reserve_id;
    }
    /**
     * 编辑预定
     * @author too|最后修改时间 2015年5月5日 下午2:34:51
     */
    public function editReserve($data,$rId)
    {
        $tmp_data = $this->getOne(array("reserve_id"=>$rId));
        if (!empty($tmp_data))
        {
            $result = $this->edit(array("reserve_id"=>$rId), $data);
            if ($result)
            {
                return true;
            }
            return false;
        }
        return false;
    }
    /**
     * 取单条预定信息 写重复了不要删
     * @author too|最后修改时间 2015年5月5日 下午1:20:12
     */
    public function oneReserve($where){
        $tmp_data = $this->getOne($where);
        return $tmp_data;
    }
    /**
     * 查看预定详情
     * @param $rid 预定id
     * @author too|最后修改时间 2015年5月5日 下午1:37:19
     */
   /*  public function scanReserve($rid){
        $reserve = new \Common\Model\Erp\Reserve();
        $sql = $reserve->getSqlObject();
        $reserve = $sql->select(array('r'=>$reserve->getTableName('reserve')));
        $reserve->join(array('t'=>'tenant'),'t.tenant_id=r.tenant_id',array('tname'=>'name','tphone'=>'phone','tidcard'=>'idcard'),'left');//取用户名电话身份证

        $reserve->join(array('hfv'=>'house_focus_view'),'hfv.house_id=r.house_id',array('hfvname'=>'house_name'),'left');//分散式
        $reserve->join(array('hfv'=>'house_focus_view'),'hfv.record_id=r.room_id',array('hfvname'=>'house_name'),'left');//集中式
    } */
    /**
     * 取所有预定
     * @param $cid 公司id
     * @author too|最后修改时间 2015年4月27日 下午6:07:54
     */
    public function getAllReserve($search,$cid,$page,$size,$user,$id_arr,$house_type){
        if($house_type == 1){
            return self::getHouse($search,$cid,$page,$size,$user,$id_arr);
        }else if($house_type == 2){
            return self::getRoomFocus($search,$cid,$page,$size,$user,$id_arr);
        }
    }


    /**
     * 分散式数据 整租合租不一样
     * @param $search
     * @param $cid
     * @param $page
     * @param $size
     * @param $user
     * @param $id_arr
     * @return array
     */
    public function getHouse($search,$cid,$page,$size,$user,$id_arr){
        $sql = self::getSqlObject();
        $select = $sql->select(array('r'=>'reserve'));
        $select->join(
            ['t'=>'tenant'],
            new \Zend\Db\Sql\Predicate\Expression('r.tenant_id = t.tenant_id'),
            ['name','phone'],
            $select::JOIN_LEFT
        );
        $select->leftjoin(
            array('h' => 'house'),
            new Expression('r.house_id = h.house_id AND r.house_type = 1'),
            [
                'full_name'=>  new \Zend\Db\Sql\Predicate\Expression("CONCAT(c.community_name,'-',h.cost,'栋',h.unit,'单元',h.floor,'楼',h.number,'号')"),
                'rental_way' => 'rental_way'
            ]
        );
        $select->leftjoin(
            ['rm'=>'room'],
            'rm.room_id=r.room_id',[
                'room_type'=>new \Zend\Db\Sql\Predicate\Expression("IF(rm.room_id IS NULL,'',CONCAT(IF((rm.room_type = 'main'),'主卧',IF((rm.room_type = 'guest'),'客卧','次卧')),rm.custom_number,'号'))"),
//                'full_name'
            ]
        );
        $select->leftjoin(['c'=>'community'],'c.community_id=h.community_id',[]);
        /**
         * 权限
         */
        if($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER){
            $permissions = \Common\Helper\Permissions::Factory('sys_housing_management');
            $permisionsTable = $permissions->VerifyDataCollectionsPermissions($permissions::USER_AUTHENTICATOR, $user['user_id'],\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE);
            $join = new \Zend\Db\Sql\Predicate\Expression('(h.house_id=pa.authenticatee_id and h.house_id>0 and pa.source='.\Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE.')');
            $select->join(array('pa'=>new \Zend\Db\Sql\Predicate\Expression($permisionsTable)),$join,'authenticatee_id');
        }
        $where = new \Zend\Db\Sql\Where();
        $where->equalTo('r.is_delete', 0);
        $where->equalTo('t.company_id', $cid);
        $where->equalTo('r.house_type',1);

        $link_where_big = new \Zend\Db\Sql\Where();
        $user_helper = new \Common\Helper\Erp\User();
        $user_info = $user_helper->getCurrentUser ();
        $city_id = $user_info['city_id'];
        if ($id_arr['disperse_room_id']) {
            $room_sql = $this->getSqlObject();
            $r_select = $room_sql->select(array("c" => "city"));
            $r_select->columns(array());
            $r_select->leftjoin(array("ct" => "community"), "c.city_id = ct.city_id", array());
            $r_select->leftjoin(array("h" => "house"), "ct.community_id = h.community_id", array());
            $r_select->leftjoin(array("r" => "room"), "r.house_id = h.house_id", array("room_id" => "room_id"));
            $r_where = new \Zend\Db\Sql\Where();
            $r_where->equalTo("c.city_id", $city_id);
            $r_where->equalTo("h.company_id", $cid);
            $r_where->equalTo("h.rental_way", 1);
            $r_select->where($r_where);
            $r_arr = array(new Expression($r_select->getSqlString()));
            $r_or_where = new \Zend\Db\Sql\Where();
            $r_or_where->in('r.room_id', $r_arr);
            $link_where_big->orPredicate($r_or_where);
        }

        if ($id_arr['disperse_house_id']) {
            $house_sql = $this->getSqlObject();
            $h_select = $house_sql->select(array("c" => "city"));
            $h_select->columns(array());
            $h_select->leftjoin(array("ct" => "community"), "c.city_id = ct.city_id", array());
            $h_select->leftjoin(array("h" => "house"), "ct.community_id = h.community_id", array("house_id" => "house_id"));
            $h_where = new \Zend\Db\Sql\Where();
            $h_where->equalTo("c.city_id", $city_id);
            $h_where->equalTo("h.company_id", $cid);
            $h_where->equalTo("h.rental_way", 2);
            $h_select->where($h_where);
            $h_arr = array(new Expression($h_select->getSqlString()));
            $h_or_where = new \Zend\Db\Sql\Where();
            $h_or_where->in('r.house_id', $h_arr);
            $link_where_big->orPredicate($h_or_where);
        }
        $where->andPredicate($link_where_big);
        //到期时间
        if(!empty($search['stime'])){
            $where->greaterThanOrEqualTo('r.stime',strtotime($search['stime']));
        }
        if(!empty($search['etime'])){
            $where->lessThanOrEqualTo('r.etime',strtotime($search['etime']));
        }
        /*   关键词:小区名 房间编号 租客电话 */
        if (!empty($search['keywords'])) {
            $where2 = new \Zend\Db\Sql\Where();
            $keywords = $search['keywords'];
            $where2->like('t.phone', "%$keywords%");//租客电话
            $where2->or;
            $where2->like('rm.full_name', "%$keywords%");//房源编号
            $where2->or;
            $where2->like('h.house_name', "%$keywords%");//房源编号
            $where->addPredicate($where2);
        }
        $select->where($where);
        $select->order('r.reserve_id desc');
//        echo $select->getSqlString();exit;
        $data = $select::pageSelect($select,null, $page, $size);
        return $data;
    }

    /**
     * 集中式数据
     * @param $search
     * @param $cid
     * @param $page
     * @param $size
     * @param $user
     * @param $id_arr
     * @return array
     */
    public function getRoomFocus($search,$cid,$page,$size,$user,$id_arr){
        $user_helper = new \Common\Helper\Erp\User ();
        $city_model = new \Common\Model\Erp\City();
        $user_info = $user_helper->getCurrentUser ();
        $city_id = $user_info['city_id'];
        $sql = $this->getSqlObject();
        $select = $sql->select(
            ['r'=>'reserve']
        );
        $select->join(
            ['t'=>'tenant'],
            'r.tenant_id = t.tenant_id',
            [
                'name',
                'phone'
            ],
            $select::JOIN_LEFT
        );
        $select->leftjoin(
            [
                'h'=>'house'
            ],
            'h.house_id=r.house_id',
             [
                 //'house_name'=>new \Zend\Db\Sql\Predicate\Expression("CONCAT(f.flat_name,rf.floor,'楼', rf.custom_number,'号')")
             ]
        );
        $select->leftjoin(
            ['rf'=>'room_focus'],
            'rf.room_focus_id=r.room_id',
            [
                'full_name'
            ]
        );
        $select->leftjoin(
            ['f'=>'flat'],
            'f.flat_id=rf.flat_id',
            []
        );
        /**
         * 权限
         */
        if($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER){
            $permissions = \Common\Helper\Permissions::Factory('sys_housing_management');
            $permisionsTable = $permissions->VerifyDataCollectionsPermissions($permissions::USER_AUTHENTICATOR, $user['user_id'],\Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
            $join = new \Zend\Db\Sql\Predicate\Expression('f.flat_id=pa.authenticatee_id and pa.source='.\Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
            $select->join(array('pa'=>new \Zend\Db\Sql\Predicate\Expression($permisionsTable)),$join,'authenticatee_id');
        }
        $where = new \Zend\Db\Sql\Where();
        $where->equalTo('r.is_delete', 0);
        $where->equalTo('t.company_id', $cid);
        $where->equalTo('r.house_type', 2);


        if ($id_arr['focus_room_id']) {
            $link_where_big = new \Zend\Db\Sql\Where();
            $focus_sql = $city_model->getSqlObject();
            $f_select = $focus_sql->select(array("c" => "city"));
            $f_select->columns(array());
            $f_select->leftjoin(array("f" => "flat"), "c.city_id = f.city_id", array());
            $f_select->leftjoin(array("rf" => "room_focus"), "f.flat_id = rf.flat_id", array("room_focus_id" => "room_focus_id"));
            $f_where = new \Zend\Db\Sql\Where();
            $f_where->equalTo("c.city_id", $city_id);
            $f_where->equalTo("f.company_id", $cid);
            $f_select->where($f_where);
            $f_arr = array(new Expression($f_select->getSqlString()));
            $f_or_where = new \Zend\Db\Sql\Where();
            $f_or_where->in('r.room_id', $f_arr);
            $link_where_big->orPredicate($f_or_where);
            $where->andPredicate($link_where_big);
        }

        //到期时间
        if(!empty($search['stime'])){
            $where->greaterThanOrEqualTo('r.stime',strtotime($search['stime']));
        }
        if(!empty($search['etime'])){
            $where->lessThanOrEqualTo('r.etime',strtotime($search['etime']));
        }
        /*   关键词:小区名 房间编号 租客电话 */
        if (!empty($search['keywords'])) {
            $where2 = new \Zend\Db\Sql\Where();
            $keywords = $search['keywords'];
            $where2->like('t.phone', "%$keywords%");//租客电话
            $where2->or;
            $where2->like('rf.full_name', "%$keywords%");//房源编号
            $where->addPredicate($where2);
        }
        $select->where($where);
        $select->order('r.reserve_id desc');
//        echo $select->getSqlString();
//        exit;
        $data = $select::pageSelect($select,null, $page, $size);
        return $data;
    }


    /**
     * 查询当前城市的分散式合租的房间id
     * 修改时间  2015年6月26日10:29:50
     *
     * @autho ft
     * @param $city_id (城市id)
     * @param $c_id (公司id)
     */
    public function getCityDisperseSharedRoomId($city_id, $cid) {
        $city_model = new \Common\Model\Erp\City();
        $sql = $city_model->getSqlObject();
        $select = $sql->select(array('c' => 'city'));
        $select->columns(array());
        $select->leftjoin(array('ct' => 'community'), 'c.city_id = ct.city_id', array());
        $select->leftjoin(array('h' => 'house'), 'ct.community_id = h.community_id', array());
        $select->leftjoin(array('r' => 'room'), 'r.house_id = h.house_id', array('room_id' => 'room_id'));
        $where = new \Zend\Db\Sql\Where();
        $where->equalTo('c.city_id', $city_id);
        $where->equalTo('h.company_id', $cid);
        $where->equalTo('h.rental_way', 1);
        $select->where($where);
        return $select->execute();
    }
    /**
     * 查询当前城市的分散式整租的房源id
     * 修改时间  2015年6月26日11:09:23
     *
     * @autho ft
     * @param $city_id (城市id)
     * @param $c_id (公司id)
     */
    public function getCityDisperseHouseId($city_id, $cid) {
        $city_model = new \Common\Model\Erp\City();
        $sql = $city_model->getSqlObject();
        $select = $sql->select(array('c' => 'city'));
        $select->columns(array());
        $select->leftjoin(array('ct' => 'community'), 'c.city_id = ct.city_id', array());
        $select->leftjoin(array('h' => 'house'), 'ct.community_id = h.community_id', array('house_id' => 'house_id'));
        $where = new \Zend\Db\Sql\Where();
        $where->equalTo('c.city_id', $city_id);
        $where->equalTo('h.company_id', $cid);
        $where->equalTo('h.rental_way', 2);
        $select->where($where);
        return $select->execute();
    }
    /**
     * 查询当前城市的集中式的房间id
     * 修改时间  2015年6月26日11:25:29
     *
     * @autho ft
     * @param $city_id (城市id)
     * @param $c_id (公司id)
     */
    public function getCityFocusRoomId($city_id, $cid) {
        $city_model = new \Common\Model\Erp\City();
        $sql = $city_model->getSqlObject();
        $select = $sql->select(array('c' => 'city'));
        $select->columns(array());
        $select->leftjoin(array('f' => 'flat'), 'c.city_id = f.city_id', array());
        $select->leftjoin(array('rf' => 'room_focus'), 'f.flat_id = rf.flat_id', array('room_focus_id' => 'room_focus_id'));
        $where = new \Zend\Db\Sql\Where();
        $where->equalTo('c.city_id', $city_id);
        $where->equalTo('f.company_id', $cid);
        $select->where($where);
        return $select->execute();
    }
}