<?php
namespace App\Web\Helper;
use App\Web\Lib\Request;
use App\Web\Helper\Url;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Where;
use Core\Db\Sql\Select;
/**
 * 集中式房源控制器
 * @author too|最后修改时间 2015年4月17日 上午11:09:46
 */
class Room {
    /**
     * @param int $cid 公司id
     * return 多维数组或一维数组
     * 根据company_id获取该公司下  未租状态  集中式房源  所有   的房源信息
     * 涉及的表有 room_focus,flat
     * room_template_relation,flat_template这三个表是什么鬼?这里根本不用
     * @author too|最后修改时间 2015年4月16日 下午3:53:12
     */
    public function getRoomData($user,$param,$status=false){
        $link =  \Core\Config::get('db:default');
        $adapter = new \Zend\Db\Adapter\Adapter($link);
        $addwhere_r = $addwhere_he = $addwhere_rf = '';
        if($status){
            $addwhere_r = " AND `r`.`status` = 1";
            $addwhere_he = " AND `he`.`status` = 1";
            $addwhere_rf = " AND `rf`.`status` = 1";
        }
        $limit = " limit 0,10";


        /**
         * 权限
         */
        if($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER){
            $query = "SELECT
  `r`.`house_id` AS `house_id`,
  `r`.`room_id` AS `record_id`,
  `r`.`detain` AS `detain`,
  `r`.`pay` AS `pay`,
  `r`.`status` AS `status`,
  `r`.`money` AS `money`,
  `r`.`full_name` AS `house_name`,
  `c`.`community_id` AS `auth_id`,
  `h`.`rental_way`
FROM
  `room` AS `r`
  INNER JOIN `house` AS `h`
    ON `r`.`house_id` = `h`.`house_id`
  INNER JOIN `community` AS `c`
    ON `h`.`community_id` = `c`.`community_id`
        INNER JOIN `housing_distribution` AS hd ON(
	h.house_id = hd.source_id
    )

WHERE `h`.`rental_way` = 1
 $addwhere_r
  AND `r`.`is_delete` = 0
  AND `h`.`is_delete` = 0
  AND `c`.`city_id` = {$user['city_id']}
  AND `h`.`company_id` = {$user['company_id']}
  AND `r`.`full_name` LIKE '%{$param}%'
  AND `hd`.`user_id` = {$user['user_id']}
  AND hd.source = 4
  union all

  SELECT
    `he`.`house_id` AS `house_id`,
    '0' AS `record_id`,
    `he`.`detain` AS `detain`,
    `he`.`pay` AS `pay`,
    `he`.`status` AS `status`,
    `he`.`money` AS `money`,
    `h`.`house_name` AS `house_name`,
    `c`.`community_id` AS `auth_id`,
    `h`.`rental_way`
  FROM
    `house_entirel` AS `he`
    INNER JOIN `house` AS `h`
      ON `he`.`house_id` = `h`.`house_id`
    INNER JOIN `community` AS `c`
      ON `h`.`community_id` = `c`.`community_id`
    INNER JOIN `housing_distribution` AS hd ON(
	h.house_id = hd.source_id
    )
  WHERE `h`.`rental_way` = 2
    $addwhere_he
    AND `he`.`is_delete` = 0
    AND `h`.`is_delete` = 0
    AND `c`.`city_id` = {$user['city_id']}
    AND `h`.`company_id` = {$user['company_id']}
    AND (`h`.`house_name` LIKE '%{$param}%')
    AND `hd`.`user_id` = {$user['user_id']}
    AND hd.source = 4
  union all
  SELECT
    '0' AS `house_id`,
    `rf`.`room_focus_id` AS `record_id`,
    `rf`.`detain` AS `detain`,
    `rf`.`pay` AS `pay`,
    `rf`.`status` AS `status`,
    `rf`.`money` AS `money`,
    `rf`.`full_name` AS `house_name`,
    `f`.`flat_id` AS `auth_id`,
    '0' as `rental_way`
  FROM
    `room_focus` AS `rf`
    INNER JOIN `flat` AS `f`
      ON `rf`.`flat_id` = `f`.`flat_id`
          INNER JOIN `housing_distribution` AS hd ON(
	f.flat_id = hd.source_id
    )
  WHERE `rf`.`is_delete` = 0
    $addwhere_rf
    AND `f`.`is_delete` = 0
    AND `f`.`city_id` = {$user['city_id']}
    AND `rf`.`company_id` = {$user['company_id']}
    AND (`rf`.`full_name` LIKE '%{$param}%')
    AND `hd`.`user_id` = {$user['user_id']}
    AND hd.source = 2
    $limit
  ";
        }else{
            $query = "SELECT
  `r`.`house_id` AS `house_id`,
  `r`.`room_id` AS `record_id`,
  `r`.`detain` AS `detain`,
  `r`.`pay` AS `pay`,
  `r`.`status` AS `status`,
  `r`.`money` AS `money`,
  `r`.`full_name` AS `house_name`,
  `c`.`community_id` AS `auth_id`,
  `h`.`rental_way`
FROM
  `room` AS `r`
  INNER JOIN `house` AS `h`
    ON `r`.`house_id` = `h`.`house_id`
  INNER JOIN `community` AS `c`
    ON `h`.`community_id` = `c`.`community_id`
WHERE `h`.`rental_way` = 1
  $addwhere_r
  AND `r`.`is_delete` = 0
  AND `h`.`is_delete` = 0
  AND `c`.`city_id` = {$user['city_id']}
  AND `h`.`company_id` = {$user['company_id']}
  AND `r`.`full_name` LIKE '%{$param}%'
  union all

  SELECT
    `he`.`house_id` AS `house_id`,
    '0' AS `record_id`,
    `he`.`detain` AS `detain`,
    `he`.`pay` AS `pay`,
    `he`.`status` AS `status`,
    `he`.`money` AS `money`,
    `h`.`house_name` AS `house_name`,
    `c`.`community_id` AS `auth_id`,
    `h`.`rental_way`
  FROM
    `house_entirel` AS `he`
    INNER JOIN `house` AS `h`
      ON `he`.`house_id` = `h`.`house_id`
    INNER JOIN `community` AS `c`
      ON `h`.`community_id` = `c`.`community_id`
  WHERE `h`.`rental_way` = 2
    $addwhere_he
    AND `he`.`is_delete` = 0
    AND `h`.`is_delete` = 0
    AND `c`.`city_id` = {$user['city_id']}
     AND `h`.`company_id` = {$user['company_id']}
    AND (`h`.`house_name` LIKE '%{$param}%')

  union all
  SELECT
    '0' AS `house_id`,
    `rf`.`room_focus_id` AS `record_id`,
    `rf`.`detain` AS `detain`,
    `rf`.`pay` AS `pay`,
    `rf`.`status` AS `status`,
    `rf`.`money` AS `money`,
    `rf`.`full_name` AS `house_name`,
    `f`.`flat_id` AS `auth_id`,
    '0' as `rental_way`
  FROM
    `room_focus` AS `rf`
    INNER JOIN `flat` AS `f`
      ON `rf`.`flat_id` = `f`.`flat_id`
  WHERE `rf`.`is_delete` = 0
  $addwhere_rf
    AND `f`.`is_delete` = 0
    AND `f`.`city_id` = {$user['city_id']}
    AND `rf`.`company_id` = {$user['company_id']}
    AND (`rf`.`full_name` LIKE '%{$param}%')
    $limit";
        }
        $data = $adapter->query($query,Adapter::QUERY_MODE_EXECUTE)->toArray();
        //增加集中2和分散1区分字段
        //house_id不等于0就是分散式,house_type=1
        foreach($data as $k=>$v){
            if($v['house_id'] != 0 ){
                $v['house_type']=1;
                $data[$k] = $v;
            }else{
                $v['house_type']=2;
                $data[$k] = $v;
            }
        }
        return $data;
    }
    /**
     * 房源管理里跳过来 带house_id和house_type
     * @author too|最后修改时间 2015年5月4日 下午4:47:25
     */
    public function getInfo($house_id=0,$house_room_id=0,$house_type=0,$cid=0,$type='',$isContract=false)
    {
        $ji = new \Common\Model\Erp\HouseFocusView();
        $sql = $ji->getSqlObject();
        $select = $sql->select(array('hfv'=>$ji->getTableName('house_focus_view')));
        $where = new \Zend\Db\Sql\Where();//造where条件对象
        if($type == 'reserve')//如果是预定，已出租房源也可以看
        {

        }else {
            if ($isContract){
                $where->in("hfv.status",array(1,3));
            }else {
                $where->equalTo('hfv.status', 1);//未租状态
            }
        }
        $where->equalTo('hfv.is_delete', 0);
        $where->equalTo('hfv.company_id',$cid);//取当前用户所在公司的房源
        if($house_type == 1)
        {
            $where->equalTo('hfv.house_id',$house_id);//取当前用户所在公司的房源
            $where->equalTo('hfv.record_id',$house_room_id);//取当前用户所在公司的房源
        }else{
            $where->equalTo('hfv.record_id',$house_room_id);//取当前用户所在公司的房源
        }

        $select->where($where);
        $data = $select->execute();
        foreach($data as $k=>$v){
            if($v['house_id'] != 0 ){
                $v['house_type']=1;
                $data[$k] = $v;
            }else{
                $v['house_type']=2;
                $data[$k] = $v;
            }
        }
        return $data;
    }
}