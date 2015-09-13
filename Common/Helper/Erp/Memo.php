<?php
namespace Common\Helper\Erp;
class Memo extends \Core\Object{
	/**
	 * 取得提醒类型说明
	 * @author lishengyou
	 * 最后修改时间 2015年3月24日 上午9:35:32
	 *
	 */
	public static function getNoticeTypeList(){
		return array(
				self::NOTICE_TYPE_ONTIME=>'按时提醒',
				2=>'提前5分钟',
				3=>'提前10分钟',
				4=>'提前半小时',
				5=>'提前一小时',
				6=>'不提醒'
		);
	}
	const NOTICE_TYPE_ONTIME = 1;//按时
	const NOTICE_TYPE_ADVANCE_5 = 2;//提前5分钟
	const NOTICE_TYPE_ADVANCE_10 = 3;//提前10分钟
	const NOTICE_TYPE_ADVANCE_30 = 4;//提前30分钟
	const NOTICE_TYPE_ADVANCE_60 = 5;//提前60分钟
	const NOTICE_TYPE_NOTIME = 6;//不提醒
	/**
	 * 添加备忘
	 * @author lishengyou
	 * 最后修改时间 2015年3月24日 上午9:49:36
	 *
	 * @param 备忘时间 $memo_time
	 * @param 备忘类型 $notice_type
	 * @param 备忘内容 $notice_content
	 * @param 创建人编号 $create_uid
	 */
	public static function addMemo($memo_time,$notice_type,$notice_content,$create_uid){
		$memo_time = is_numeric($memo_time) ? $memo_time : strtotime($memo_time);
		if(!$memo_time){
			self::setLastError('备忘时间错误');
			return false;
		}
		if(!$create_uid){
			self::setLastError('创建人错误');
			return false;
		}
		$notice_type = self::handleNoticeType($memo_time, $notice_type);
		if(!$notice_type){
			return false;//创建类型错误
		}
		$memoModel = new \Common\Model\Erp\Memo();
		//整理数据
		$data = $notice_type;
		$data['memo_time'] = $memo_time;
		$data['notice_content'] = $notice_content;
		$data['create_uid'] = $create_uid;
		$data['create_time'] = time();
		$result = $memoModel->insert($data);
		if(!$result){
			self::setLastError('添加失败');
		}
		return $result;
	}
	/**
	 * 处理类型
	 * @author lishengyou
	 * 最后修改时间 2015年3月24日 上午10:13:47
	 *
	 * @param unknown $memo_time
	 * @param unknown $notice_type
	 */
	protected static function handleNoticeType($memo_time,$notice_type){
		$notice_types = self::getNoticeTypeList();
		if(!isset($notice_types[$notice_type])){
			self::setLastError('提醒类型错误');
			return false;
		}
		$notice_time = $memo_time;
		$is_notice = 0;
		//计算提前时间
		switch ($notice_type){
			case self::NOTICE_TYPE_ONTIME:
				$is_notice = 1;
				break;
			case self::NOTICE_TYPE_ADVANCE_5:
				$notice_time = $memo_time-(5*60);
				$is_notice = 1;
				break;
			case self::NOTICE_TYPE_ADVANCE_10:
				$notice_time = $memo_time-(10*60);
				$is_notice = 1;
				break;
			case self::NOTICE_TYPE_ADVANCE_30:
				$notice_time = $memo_time-(30*60);
				$is_notice = 1;
				break;
			case self::NOTICE_TYPE_ADVANCE_60:
				$notice_time = $memo_time-(60*60);
				$is_notice = 1;
				break;
			case self::NOTICE_TYPE_NOTIME:
				break;
		}
		return array('notice_time'=>$notice_time,'is_notice'=>$is_notice);
	}
	/**
	 * 编辑备忘
	 * @author lishengyou
	 * 最后修改时间 2015年3月24日 上午10:08:14
	 *
	 * @param 备忘编号 $memo_id
	 * @param array $data
	 */
	public static function editMemo($memo_id,array $data){
		$memoModel = new \Common\Model\Erp\Memo();
		$memoData = $memoModel->getOne(array('memo_id'=>$memo_id));
		if(!$memoData){
			self::setLastError('备忘不存在');
			return false;
		}
		$info = array('memo_time','notice_type','notice_content','create_uid');
		$data = array_intersect_key($data, array_fill_keys($info, false));//允许通过的数据
		if(!isset($data['create_uid']) || $data['create_uid'] != $memoData['create_uid']){
			self::setLastError('用户信息错误');
			return false;
		}
		unset($data['create_uid']);
		if(empty($data)){
			self::setLastError('没有需要保存的内容');
			return false;
		}
		if(isset($data['memo_time'])){
			$data['memo_time'] = is_numeric($data['memo_time']) ? $data['memo_time'] : strtotime($data['memo_time']);
			if(!$data['memo_time']){
				self::setLastError('备忘时间错误');
				return false;
			}
		}
		if(isset($data['notice_type'])){//处理类型
			$notice_type = self::handleNoticeType(isset($data['memo_time']) ? $data['memo_time'] : $memoData['memo_time'], $data['notice_type']);
			if(!$notice_type){
				return false;//类型错误
			}
			unset($data['notice_type']);
			$data = array_merge($data,$notice_type);
		}
		$reslut = $memoModel->edit(array('memo_id'=>$memo_id), $data);
		if(!$result){
			self::setLastError('保存失败');
		}
		return !!$result;
	}
	/**
	 * 删除备忘
	 * @author lishengyou
	 * 最后修改时间 2015年3月24日 上午10:37:02
	 *
	 * @param unknown $memo_id
	 * @param unknown $create_uid
	 */
	public static function delete($memo_id,$create_uid){
		$memoModel = new \Common\Model\Erp\Memo();
		$memoData = $memoModel->getOne(array('memo_id'=>$memo_id));
		if(!$memoData || $memoData['create_uid'] != $create_uid){
			self::setLastError('备忘不存在');
			return false;
		}
		$reslut = $memoModel->delete(array('memo_id'=>$memo_id));
		if(!$reslut){
			self::setLastError('删除失败');
			return false;
		}
		return true;
	}
	/**
	 * 取得列表
	 * @author lishengyou
	 * 最后修改时间 2015年3月24日 上午10:51:08
	 *
	 * @param number $page
	 * @param number $size
	 * @param array $where
	 * @param string $order
	 */
	public static function getList($page=1,$size=10,array $where = array(),$order = 'create_time desc'){
		$memoModel = new \Common\Model\Erp\Memo();
		$sqlObject = $memoModel->getSqlObject();
		$select = $sqlObject->select($memoModel->getTableName());
		if($where){
			$select->where($where);
		}
		if($order){
			$select->order($order);
		}
		$data = $select->pageSelect($select,null, $page, $size);
		return $data;
	}
}