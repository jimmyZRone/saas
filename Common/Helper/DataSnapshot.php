<?php
namespace Common\Helper;
/**
 * 数据快照
 * @author lishengyou
 * 最后修改时间 2015年8月3日 上午10:04:58
 *
 */
class DataSnapshot{
	/**
	 * 分散式房间预定
	 * @var String
	 */
	public static $SNAPSHOT_ROOM_RESVER = "ROOM_RESVER";
	/**
	 * 分散式房间取消预定
	 * @var String
	 */
	public static $SNAPSHOT_ROOM_DELETE_RESVER = "ROOM_DELETE_RESVER";
	/**
	 * 分散式房间预约退租
	 * @var String
	 */
	public static $SNAPSHOT_ROOM_RESERVE_BACK_RENTAL = "ROOM_RESERVE_BACK_RENTAL";
	/**
	 * 分散式房间撤销预约退租
	 * @var String
	 */
	public static $SNAPSHOT_ROOM_RECOVER_BACK_RENTAL = "ROOM_RECOVER_BACK_RENTAL";
	/**
	 * 分散式房间出租
	 * @var String
	 */
	public static $SNAPSHOT_ROOM_RENTAL = "ROOM_RENTAL";
	/**
	 * 分散式房间退租
	 * @var String
	 */
	public static $SNAPSHOT_ROOM_RENTAL_BACK = "ROOM_RENTAL_BACK";
	/**
	 * 分散式房间编辑
	 * @var String
	 */
	public static $SNAPSHOT_ROOM_EDIT = "ROOM_EDIT";
	/**
	 * 分散式房间删除
	 * @var String
	 */
	public static $SNAPSHOT_ROOM_DELETE = "ROOM_DELETE";
	/**
	 * 分散式房源预约退组
	 * @var String
	 */
	public static $SNAPSHOT_HOUSE_RESERVE_BACK_RENTAL = "HOUSE_RESERVE_BACK_RENTAL";
	/**
	 * 分散式房源预定
	 * @var String
	 */
	public static $SNAPSHOT_HOUSE_RESVER = "HOUSE_RESVER";
	/**
	 * 分散式房源出租
	 * @var String
	 */
	public static $SNAPSHOT_HOUSE_RENTAL = "HOUSE_RENTAL";
	/**
	 * 分散式房源取消预定
	 * @var String
	 */
	public static $SNAPSHOT_HOUSE_DELETE_RESVER = "HOUSE_DELETE_RESVER";
	/**
	 * 分散式房源退组
	 * @var String
	 */
	public static $SNAPSHOT_HOUSE_RENTAL_BACK = "HOUSE_RENTAL_BACK";
	/**
	 * 分散式房间撤销预约退租
	 * @var String
	 */
	public static $SNAPSHOT_HOUSE_RECOVER_BACK_RENTAL = "HOUSE_RECOVER_BACK_RENTAL";
	/**
	 * 欠费清单收费
	 * @var String
	 */
	public static $SNAPSHOT_SERIAL_DEBTS_CHARGE = "SERIAL_DEBTS_CHARGE";
	/**
	 * 编辑流水
	 * @var String
	 */
	public static $SNAPSHOT_SERIAL_EDIT = "SERIAL_EDIT";
	/**
	 * 分散式房源编辑
	 * @var String
	 */
	public static $SNAPSHOT_HOUSE_EDIT = "HOUSE_EDIT";
	/**
	 * 分散式房源删除
	 * @var String
	 */
	public static $SNAPSHOT_HOUSE_DELETE = "HOUSE_DELETE";
	/**
	 * 集中式房间预定
	 * @var String
	 */
	public static $SNAPSHOT_FOCUS_RESVER = "FOCUS_RESVER";
	/**
	 * 集中式房间取消预定
	 * @var String
	 */
	public static $SNAPSHOT_FOCUS_DELETE_RESVER = "FOCUS_DELETE_RESVER";
	/**
	 * 集中式房间预约退租
	 * @var String
	 */
	public static $SNAPSHOT_FOCUS_RESERVE_BACK_RENTAL = "FOCUS_RESERVE_BACK_RENTAL";
	/**
	 * 集中式房间撤销预约退租
	 * @var String
	 */
	public static $SNAPSHOT_FOCUS_RECOVER_BACK_RENTAL = "FOCUS_RECOVER_BACK_RENTAL";
	/**
	 * 集中式房间出租
	 * @var String
	 */
	public static $SNAPSHOT_FOCUS_RENTAL = "FOCUS_RENTAL";
	/**
	 * 集中式房间退租
	 * @var String
	 */
	public static $SNAPSHOT_FOCUS_RENTAL_BACK = "FOCUS_RENTAL_BACK";
	/**
	 * 集中式房间编辑
	 * @var String
	 */
	public static $SNAPSHOT_FOCUS_EDIT = "FOCUS_EDIT";
	/**
	 * 集中式删除房间
	 * @var String
	 */
	public static $SNAPSHOT_FOCUS_ROOM_DELETE = "FOCUS_ROOM_DELETE"; 
	/**
	 * 集中式删除公寓
	 * @var String
	 */
	public static $SNAPSHOT_FOCUS_FLAT_DELETE = "FOCUS_FLAT_DELETE"; 
	/**
	 * 合同续租
	 * @var String
	 */
	public static $SNAPSHOT_CONTRACT_RELET = "CONTRACT_RELET";
	/**
	 * 合同修改
	 * @var String
	 */
	public static $SNAPSHOT_CONTRACT_EIDT = "CONTRACT_EDIT";
	/**
	 * 终止合同
	 * @var String
	 */
	public static $SNAPSHOT_STOP_CONTRACT = "STOP_CONTRACT";
	/**
	 * 删除预定
	 * @var String
	 */
	public static $SNAPSHOT_DELETE_RESERVE = "DELETE_RESERVE";
	/**
	 * 删除合同
	 * @var String
	 */
	public static $SNAPSHOT_DELETE_CONTRACT = "DELETE_CONTRACT";
	/**
	 * 业主合同续租
	 * @var String
	 */
	public static $SNAPSHOT_LANDLORD_CONTRACT_RELET = "LANDLORD_CONTRACT_RELET";
	/**
	 * 业主合同删除
	 * @var String
	 */
	public static $SNAPSHOT_LANDLORD_CONTRACT_DELETE = "LANDLORD_CONTRACT_DELETE";
	/**
	 * 业主合同终止
	 * @var String
	 */
	public static $SNAPSHOT_LANDLORD_CONTRACT_STOP = "LANDLORD_CONTRACT_STOP";
	/**
	 * 业主合同修改
	 * @var String
	 */
	public static $SNAPSHOT_LANDLORD_CONTRACT_EDIT = "LANDLORD_CONTRACT_EDIT";
	/**
	 * 业主修改
	 * @var String
	 */
	public static $SNAPSHOT_LANDLORD_EDIT = "LANDLORD_EDIT";
	
	/**
	 * 保存数据快照
	 * @author lishengyou
	 * 最后修改时间 2015年8月3日 上午10:05:40
	 *
	 * @param string $source 来源
	 * @param int $source_id 来源编号
	 * @param array $data 数据
	 * @param string $foreign 关联的数据(和来源差不多)
	 * @param int $foreign 关联数据的ID(~~~)
	 */
	public static function save($source,$source_id,array $data,$foreign='',$foreign_id=0){
		$data = serialize($data);
		$time = time();
		$data = array(
			'source'=>$source,
			'source_id'=>$source_id,
			'foreign'=>$foreign,
			'foreign_id'=>$foreign_id,
			'data'=>$data,
			'create_time'=>$time
		);
		$tableName = self::getTable($time);
		$link = self::getLink();
		if(!$tableName || !$link){
			return false;
		}
		$insert = $link->insert($tableName);
		return $insert->values($data)->execute();
	}
	/**
	 * 取得表名称
	 * @author lishengyou
	 * 最后修改时间 2015年8月3日 下午4:10:51
	 *
	 * @param string $time
	 * @return string
	 */
	protected static function getTable($time = false){
		static $tables = null;
		$time = $time ? $time : time();
		$tablename = 'saas_snapshot'.date('_Ymd',$time);
		if(is_array($tables) && isset($tables[$tablename])){
			return true;
		}
		$link = self::getLink();
		if(!$link){
			return false;
		}
		$pdo = $link->getAdapter()->getDriver()->getConnection();
		//判断表是否存在
		if(is_null($tables)){
			$results = $pdo->execute('show tables');
			$tables = array();
			foreach ($results as $value){
				$tables[] = end(array_values($value));
			}
		}
		if(!in_array($tablename, $tables)){
			$sql = "CREATE TABLE `{$tablename}` (
					  `snapshot_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
					  `source` varchar(64) NOT NULL DEFAULT '' COMMENT '快照来源',
					  `source_id` int(11) unsigned NOT NULL COMMENT '来源编号',
					  `foreign` varchar(20) NOT NULL DEFAULT '' COMMENT '外键源',
					  `foreign_id` int(11) unsigned NOT NULL COMMENT '外键编号',
					  `data` longtext NOT NULL COMMENT '快照数据',
					  `create_time` int(10) unsigned NOT NULL COMMENT '快照生成时间',
					  PRIMARY KEY (`snapshot_id`),
					  KEY `source` (`source`),
					  KEY `source_id` (`source_id`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='数据快照'";
			if(!$pdo->getResource()->exec($sql)){
				return false;
			}
			$tables[] = $tablename;
		}
		return $tablename;
	}
	/**
	 * 取得连接
	 * @author lishengyou
	 * 最后修改时间 2015年8月3日 下午4:12:07
	 *
	 * @return \Core\Db\Sql\Sql
	 */
	protected static function getLink(){
		$link = \Common\Model::getLink('snapshot');
		return $link ? $link : false;
	}
	
}