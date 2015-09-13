<?php
namespace Common\Helper\Erp;
use Common\Model\Erp\StopHouse;
use Common\Model\Erp\ReserveBackRental;
use Core\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;
class RoomFocus extends \Core\Object
{
	private $day=0;
	/**
	 * 删除房间
	 * 修改时间2015年4月25日 15:18:25
	 * 
	 * @author yzx
	 * @param int $roomId
	 * @return Ambigous <number, boolean>
	 */
	public function delete($roomId)
	{
		$roomFocusModel = new \Common\Model\Erp\RoomFocus();
		return $roomFocusModel->edit(array("room_focus_id"=>$roomId), array("is_delete"=>1));
	}
	/**
	 * 构造房间列表
	 * 修改时间2015年4月13日 13:41:43
	 *
	 * @author yzx
	 * @param unknown $roomData
	 */
	public function getRoomFloor($roomData)
	{
		$room_data = array();
		if (!empty($roomData))
		{
			$roomids = array();
			$roomExinfoids = array('status'=>array(),'is_yytz'=>array(),'is_yd'=>array());
			foreach ($roomData as $key => $rval){
				$roomids[] = $rval['room_focus_id'];
				if(!isset($roomExinfoids['status'][$rval['status']])){
					$roomExinfoids['status'][$rval['status']] = array();
				}
				$roomExinfoids['status'][$rval['status']][] = $rval['room_focus_id'];
				if($rval['is_yytz']){
					$roomExinfoids['is_yytz'][] = $rval['room_focus_id'];
				}
				if($rval['is_yd']){
					$roomExinfoids['is_yd'][] = $rval['room_focus_id'];
				}
				$rval['msg_count'] = 0;
				$roomData[$key] = $rval;
				$roomData[$key]['sx'] = isset($rval['sex'])?explode(",", $rval['sex']):array(0);
			}
			$model = new \Common\Model\Erp('rental');
			$sql = $model->getSqlObject();
			//取租住信息
			$rentalData = array();
			if(isset($roomExinfoids['status'][\Common\Model\Erp\RoomFocus::STATUS_RENTAL])){
				$select = $sql->select(array('r'=>'rental'));
				$select->leftjoin(array('tc'=>'tenant_contract'),'r.contract_id = tc.contract_id',array("next_pay_time","totalMoney"=>"total_money","total_money"=>"next_pay_money","pay","end_line", "advance_time" => "advance_time"));
				$select->leftjoin(array('cr'=>'contract_rental'),new Expression('tc.contract_id=cr.contract_id and cr.is_delete=0'));
				$select->leftjoin(array('t'=>'tenant'),new Expression('t.tenant_id=cr.tenant_id and t.is_delete=0'),array("name","phone","gender"));
				$where = new \Zend\Db\Sql\Where();
				$where -> equalTo('r.house_type', 2);
				$where -> in('r.room_id',$roomExinfoids['status'][\Common\Model\Erp\RoomFocus::STATUS_RENTAL]);
				$where -> equalTo('tc.is_settlement', 0);
				$where -> equalTo('r.is_delete', 0);
				$select->order("cr.tenant_id desc");
				$select->where($where);
				$rentalData = $select->execute();
			}
			//取未租的最近一条租住
			$noRentalData = array();
			if(isset($roomExinfoids['status'][\Common\Model\Erp\RoomFocus::STATUS_NOT_RENTAL])){
				$select = $sql->select(array('r'=>'rental'));
				$select->join(array('tc'=>'tenant_contract'),'r.contract_id = tc.contract_id','contract_id');
				$select->join(array('t'=>'tenant'),'r.tenant_id = t.tenant_id','tenant_id');
				$where = new \Zend\Db\Sql\Where();
				$where -> equalTo('r.house_type', 2);
				$where -> in('r.room_id',$roomExinfoids['status'][\Common\Model\Erp\RoomFocus::STATUS_NOT_RENTAL]);
				$where -> equalTo('tc.is_settlement', 1);
				$where -> equalTo('r.is_delete', 0);
				$where -> greaterThanOrEqualTo('tc.end_line', strtotime(date('Y-m-1')));
				$select->where($where);
				$select->group(new \Zend\Db\Sql\Predicate\Expression('`r`.`house_type`,`r`.`house_id`,`r`.`room_id`'));
				$select->order('tc.end_line');
				$noRentalData = $select->execute();
			}
			//取停用
			$stopData = array();
			if(isset($roomExinfoids['status'][\Common\Model\Erp\RoomFocus::STATUS_IS_STOP])){
				$select = $sql->select(array('sh'=>'stop_house'));
				$where = new \Zend\Db\Sql\Where();
				//$where -> lessThanOrEqualTo('sh.start_time', time());
				//$where -> greaterThanOrEqualTo('sh.end_time', time());
				$where -> in('sh.source_id',$roomExinfoids['status'][\Common\Model\Erp\RoomFocus::STATUS_IS_STOP]);
				$where -> equalTo('sh.type', StopHouse::CENTRALIZATION_TYPE);
				$select->where($where);
				//print_r(str_replace('"', '', $select->getSqlString()));die();
				$stopData = $select->execute();
			}
			//取预定退租
			$yytzData = array();
			if(!empty($roomExinfoids['is_yytz'])){
				$select = $sql->select(array('rbr'=>'reserve_back_rental'));
				$where = new \Zend\Db\Sql\Where();
				$select->order('rbr.creat_time asc');
				//$select->group('rbr.source_id');
				$where -> equalTo('rbr.type', ReserveBackRental::CENTRALIZATION_TYPE);
				$where -> equalTo('rbr.house_type', 0);
				$where -> in('rbr.source_id',$roomExinfoids['is_yytz']);
				$where -> equalTo('rbr.is_delete', 0);
				$select->where($where);
				//print_r(str_replace('"', '', $select->getSqlString()));die();
				$yytzData = $select->execute();
			}
			//取预定
			$ydData = array();
			if(!empty($roomExinfoids['is_yd'])){
				$select = $sql->select(array('re'=>'reserve'));
				$select->join(array('t'=>'tenant'),"re.tenant_id = t.tenant_id",array("name","phone"));
				$where = new \Zend\Db\Sql\Where();
				$where -> equalTo('re.is_delete', 0);
				$where -> equalTo('re.house_type', ReserveBackRental::CENTRALIZATION_TYPE);
				$where -> in('re.room_id',$roomExinfoids['is_yd']);
				$select-> order('re.etime asc');
				//$select->group(new \Zend\Db\Sql\Predicate\Expression('`re`.`house_type`,`re`.`house_id`,`re`.`room_id`'));
				$select->where($where);
				$ydData = $select->execute();
				$time = time();
				/* if (!empty($ydData)){
					foreach ($ydData as $ykey=>$yval){
						if ($yval['stime']<=$time && $time <= $yval['etime']){
							$yd_temp[$ykey] = $yval;
						}
					}
					if (!empty($yd_temp)){
						$ydData = $yd_temp;
						sort($ydData);
						unset($yd_temp);
					}
				} */
				//print_r(str_replace('"', '',$select->getSqlString()));die();
			}
			//整理数据
			$temp = array();
			foreach ($rentalData as $value){$temp[$value['room_id']] = $value;}
			$rentalData = $temp;unset($temp);
			$temp = array();
			foreach ($noRentalData as $value){$temp[$value['room_id']] = $value;}
			$noRentalData = $temp;unset($temp);
			$temp = array();
			foreach ($stopData as $value){$temp[$value['source_id']] = $value;}
			$stopData = $temp;unset($temp);
			$temp = array();
			foreach ($yytzData as $value){$temp[$value['source_id']] = $value;}
			$yytzData = $temp;unset($temp);
			$temp = array();
			foreach ($ydData as $value){
				if (date("Y-m-d",$time)==date("Y-m-d",$value['stime'])){
					if ($value['stime'] <= $time && $time <= $value['etime']){
						$temp[$value['room_id']] = $value;
						continue;
					}
				}
				if (empty($temp)){
					$temp[$value['room_id']] = $value;
				}	
				$temp[$value['room_id']] = $value;
			}
			$ydData = $temp;unset($temp);
			//回归数据
			$room_data = array();
			foreach ($roomData as $key => $value){
				if(isset($rentalData[$value['room_focus_id']])){
					$rval['msg_count'] = 1;
					$data = $rentalData[$value['room_focus_id']];
					if ($data['next_pay_time'] >= $data['end_line']){
						$data['next_pay_time'] = "租金已收完";
						$data['total_money'] = "租金已收完";
						$data['is_relet'] = 1;
					}elseif(($data['next_pay_time'] + $data['advance_time'] * 86400) >= $data['end_line']) {
						$data['next_pay_time'] = "租金已收完";
						$data['total_money'] = "租金已收完";
						$data['is_relet'] = 1;
					}else {
						$data['next_pay_time'] = $data['next_pay_time'] > 0 ? date("Y/m/d",$data['next_pay_time']) : 0;
						$data['is_relet'] = 0;
					}
					$data['sex'] = $data['gender'];
					$value['is_relet'] = $data['is_relet'];
					$value['msg'] = array($data);
					
				}
				if(isset($noRentalData[$value['room_focus_id']])){
					$rval['msg_count'] = 1;
					$data = $noRentalData[$value['room_focus_id']];
					$data['day'] = floor(($data['dead_line']<=0 ? (($time-$data['create_time'])/86400)+1 : (($time-$data['dead_line'])/86400)+1));
					$data['money'] = $value['money'];
					$value['emp_msg'] = array($data);
				}else{
					$data = array();
					$data['day'] = floor((time() - $value['create_time'])/86400+1);
					$data['money'] = $value['money'];
					$value['emp_msg'] = array($data);
				}
				if(isset($stopData[$value['room_focus_id']])){
					$rval['msg_count'] = 1;
					$data = $stopData[$value['room_focus_id']];
					$data['start_time_c'] = date("Y-m-d",$data['start_time']);
					$data['end_time_c'] = date("Y-m-d",$data['end_time']);
					$value['stop_msg'] = array($data);
				}
				if(isset($yytzData[$value['room_focus_id']])){
					$rval['msg_count'] = 1;
					$data = $yytzData[$value['room_focus_id']];
					$data['back_rental_time_c'] = date("Y/m/d",$data['back_rental_time']);
					$value['msg_yytz'] = array($data);
				}
				if(isset($ydData[$value['room_focus_id']])){
					$rval['msg_count'] = 1;
					$data = $ydData[$value['room_focus_id']];
					$data['stime_c'] = date("Y/m/d",$data['stime']);
					$data['etime_c'] = date("Y/m/d",$data['etime']);
					$value['reserve_id'] = $data['reserve_id'];
					$value['msg_yd'] = array($data);
				}
				//$roomData[$key] = $value;
				if(!isset($room_data[$value['floor']])){
					$room_data[$value['floor']] = array();
				}
				$room_data[$value['floor']][] = $value;
			}
		}
		return $room_data;
	}
	/**
	 * 汇总楼层统计
	 * @author lishengyou
	 * 最后修改时间 2015年6月9日 下午5:22:07
	 *
	 * @param unknown $flatId
	 * @param string $searchStr
	 */
	public function havingFloorTotal($flatId,$searchStr=null){
		$roomFocusModel = new \Common\Model\Erp\RoomFocus();
		$sql = $roomFocusModel->getSqlObject();
		$select = $sql->select(array("rf"=>"room_focus"));
		$searchStr = $searchStr ? $searchStr : array();
		$where = new \Zend\Db\Sql\Where();
		if(isset($searchStr['room_type']) && $searchStr['room_type']){
			$where->equalTo('rf.room_type', $searchStr['room_type']);
		}
		if(isset($searchStr['search_str']) && $searchStr['search_str']){
			$str = strval($searchStr['search_str']);
			$where->like('rf.custom_number', new Expression("'".$str."%'"));
		}
		$where->equalTo('rf.flat_id', $flatId);
		$where->equalTo('rf.is_delete', 0);
		$select->where($where);
		$select->group(new \Zend\Db\Sql\Predicate\Expression('`floor`,`status`,`is_yytz`,`is_yd`'));
		$select->columns(array('floor','status','is_yytz','is_yd','count'=>new \Zend\Db\Sql\Predicate\Expression('count(room_focus_id)')));
		$data = $select->execute();
		//print_r(str_replace('"', '', $select->getSqlString()));die();
		$floorData = array();
		foreach ($data as $value){//整理每层的数据
			if(!isset($floorData[$value['floor']])){
				$floorData[$value['floor']] = array('not_rent'=>0,'rent'=>0,'stop'=>0,'yytd'=>0,'yd'=>0);
			}
			switch (intval($value['status'])){
				case 1:
					$floorData[$value['floor']]['not_rent']+=$value['count'];
					break;
				case 2:
					$floorData[$value['floor']]['rent']+=$value['count'];
					break;
				case 3:
					$floorData[$value['floor']]['stop']+=$value['count'];
					break;
			}
			if($value['is_yytz']){
				$floorData[$value['floor']]['yytd']+=$value['count'];
			}
			if($value['is_yd']){
				$floorData[$value['floor']]['yd']+=$value['count'];
			}
		}
		ksort($floorData,SORT_NUMERIC);
		//整理成三层
		$data = array();
		$length = count($floorData);
		$i = 0;
		foreach ($floorData as $floor => $value){
			$index = intval($i/3);
			if(!isset($data[$index])){
				$data[$index] = array(
						'data'=>array(),
						'count'=>array(array('not_rent'=>0,'rent'=>0,'stop'=>0,'yytd'=>0,'yd'=>0))
				);
			}
			$data[$index]['data'][] = $floor;
			$data[$index]['count'][0]['not_rent'] += $value['not_rent'];
			$data[$index]['count'][0]['rent'] += $value['rent'];
			$data[$index]['count'][0]['stop'] += $value['stop'];
			$data[$index]['count'][0]['yytd'] += $value['yytd'];
			$data[$index]['count'][0]['yd'] += $value['yd'];
			$i++;
		}
		return $data;
	}
	
	
	/**
	 * 获取公寓楼层并分割
	 * 修改时间2015年4月17日 14:02:51
	 * 
	 * @author yzx
	 * @param unknown $flatId
	 */
	public function getRoomFloorByFlatId($flatId,$isList=true,$searchStr=null)
	{
		$floor = array();
		$data = array();
		$all_floor = array();
		$data_all = array();
		$where_cid = new Where();
		$roomFocusModel = new \Common\Model\Erp\RoomFocus();
		$comfigModel = new \Common\Model\Erp\SystemConfig();
		$house_type = $comfigModel->getFind("FocusRoom", "focus_room_type");
		$sql = $roomFocusModel->getSqlObject();
		$select = $sql->select(array("rf"=>"room_focus"));
		$select->leftjoin(array("rtr"=>"room_template_relation"), "rf.room_focus_id=rtr.room_id",array("room_id"));
		$select->leftjoin(array("ft"=>"flat_template"), "rtr.template_id=ft.template_id");
		$where_cid->equalTo("rf.flat_id", $flatId);
		$where_cid->equalTo("rf.is_delete", 0);
		if (!empty($searchStr))
		{
			if ($searchStr['search_str']!='')
			{
				$where_str = new Where();
				$str = strval($searchStr['search_str']);
				$where_str->like("custom_number", '%'.($str).'%');
			}
			if ($searchStr['room_type']!='')
			{
				$where_type = new Where();
				$where_type->equalTo('room_type', $searchStr['room_type']);
			}
			if ($searchStr['search_str']!='' && $searchStr['room_type']!='')
			{
				$where_type->addPredicate($where_str,Where::OP_OR);
				$where_cid->addPredicate($where_type,Where::OP_AND);
			}
			if ($searchStr['search_str']!='' && $searchStr['room_type']=='')
			{
				$where_cid->addPredicate($where_str);
			}
			if ($searchStr['room_type']!='' && $searchStr['search_str']=='')
			{
				$where_cid->addPredicate($where_type);
			}
		}
		$select->where($where_cid);
		if (!$isList)
		{
			$select->group("rf.floor");
		}
		$select->order(new Expression('CAST(rf.floor AS SIGNED) ASC'));
		$select->order("rf.custom_number asc");
		//print_r(str_replace('"', '', $select->getSqlString()));die();
		$result = $select->execute();
		//构造楼层组3个为一个按钮
		if (!$isList)
		{
			foreach ($result as $key=>$val)
			{
				$floor[] = $val['floor'];
				$all_floor[] = $val['floor'];
				if (count($floor) == 3)
				{
					$data[] = $floor;
					$floor = array();
				}
			}
			foreach ($data as $dval)
			{
				foreach ($dval as $dvval)
				{
					$data_all [] =$dvval;
				}
			}
			$diff_arr = array_diff($all_floor, $data_all);
			if (!empty($diff_arr))
			{
				$data[] = $diff_arr;
			}
			//TODO 楼层统计
			 foreach ($data as $dkeys=>$dtvals)
			{
				
				$datas['not_rent'] = $this->getFloorCount($dtvals, $flatId, 1);
				$datas['rent'] = $this->getFloorCount($dtvals, $flatId, 2);
				$datas['stop'] = $this->getFloorCount($dtvals, $flatId, 3);
				$datas['yytd'] = $this->getFloorCount($dtvals, $flatId, 0,true);
				$datas['yd'] = $this->getFloorCount($dtvals, $flatId, 0,false,true);
				$all_data[] = array("data"=>$dtvals,"count"=>array($datas));
			}
			return $all_data;
		}
		$floors = null;
		$room_data = array();
		foreach ($result as $rkey=>$rval)
		{		$rval['house_type'] = isset($house_type[$rval['house_type']]) ? $house_type[$rval['house_type']] : 0;
				$floors = $rval['floor'];
				if ($rval['floor'] == $floors)
				{
					$room_data[$rval['floor']][] =$rval;
				}
		}
		return $room_data;
	}
	/**
	 * 根据模版获取房间
	 * 修改时间2015年4月18日 15:01:45
	 * 
	 * @author yzx
	 * @param int $temlateId
	 * @return Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >
	 */
	public function getRoomByTemplate($temlateId,$isPage=false,$page=1,$size=10)
	{
		$roomFocusModel = new  \Common\Model\Erp\RoomFocus();
		$sql = $roomFocusModel->getSqlObject();
		$select = $sql->select(array("rf"=>"room_focus"));
		$select->leftjoin(array("rtr"=>"room_template_relation"), "rf.room_focus_id = rtr.room_id");
		$select->where(array("template_id"=>$temlateId));
		$result = $select->execute();
		if ($isPage)
		{
			$result = Select::pageSelect($select,null, $page, $size);
		}
		return $result;
	}
	/**
	 * 批量跟新房间数据
	 * 修改时间2015年4月18日 16:48:24
	 * 
	 * @author yzx
	 * @param array $roomIds
	 * @param array $templateData
	 */
	public function updataRooms($roomIds,$templateData)
	{
		$data = $templateData;
		$roomFocusModel = new  \Common\Model\Erp\RoomFocus();
		if (is_array($roomIds) && !empty($roomIds))
		{
			foreach ($roomIds as $key=>$val)
			{
				$roomFocusModel->updateData($val['room_id'], $data);
			}
		}
	}
	/**
	 * 统计房间数据
	 * 修改时间2015年4月20日 19:36:41
	 * 
	 * @author yzx
	 * @param int $flatId
	 * @param string $isCofig
	 * @return multitype:multitype:Ambigous <number, unknown> number  Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >
	 */
	public function getRoomCount($flatId,$isCofig=false)
	{
		$roomFocusModel = new \Common\Model\Erp\RoomFocus();
		$where = new Where();
		$where->equalTo('rf.flat_id', $flatId);
		$where->equalTo('rf.is_delete', 0);
		$sql = $roomFocusModel->getSqlObject();
		$select = $sql->select(array("rf"=>"room_focus"));
		$select->leftjoin(array("rfr"=>'room_template_relation'), 'rf.room_focus_id=rfr.room_id');
		if ($isCofig){
		$where_r = new Where(); 
		$where_r->greaterThan("rfr.room_id", 0);
		$where_r->addPredicate($where);
		$select->where($where_r);
		}else {
			$select->where($where);
		}
		//print_r(str_replace('"', '', $select->getSqlString()));die();
		$count =$select->count();
		return array('page'=>array('count'=>$count));
	}
	/**
	 * 统计列表数据
	 * @param int $flatId
	 * @param string $countType
	 * @return multitype:multitype:Ambigous <number, unknown> number  Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >
	 */
	public function countRoom($flatId,$countType)
	{
		$roomFocusModel = new \Common\Model\Erp\RoomFocus();
		$sql = $roomFocusModel->getSqlObject();
		$select = $sql->select(array("rf"=>"room_focus"));
		$select->where(array('rf.flat_id'=>$flatId,"rf.is_delete"=>0));
		switch ($countType)
		{
			case 'isrent':
				$select->where(array("rf.status"=>\Common\Model\Erp\RoomFocus::STATUS_RENTAL));
				break;
			case 'reserve':
				$select->where(array("rf.is_yd"=>\Common\Model\Erp\RoomFocus::IS_YYTZ));
				break;
			case 'summoney':
				$select->columns(array("all_money"=>new Expression('sum(rf.money)')));
				break;
			case 'is_yytz':
				$select->where(array("rf.is_yytz"=>\Common\Model\Erp\RoomFocus::IS_YYTZ));
				break;
			case 'stop':
				$select->where(array("rf.status"=>\Common\Model\Erp\RoomFocus::STATUS_IS_STOP));
				break;
		}
		return $select->pageSelect($select,null,1, 1);
	}
	/**
	 * 计算空置天数
	 * 修改时间2015年4月21日 17:05:54
	 * 
	 * @author yzx
	 * @param int $roomFocusId
	 * @return multitype:multitype:Ambigous <number, unknown> number  Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >
	 */
	public function calculateEmptyDay($roomFocusId)
	{
		$roomFocusModel = new \Common\Model\Erp\RoomFocus();
		$sql = $roomFocusModel->getSqlObject();
		$select = $sql->select(array('rf'=>'room_focus'));
		$select->leftjoin(array("r"=>"rental"), new Expression("rf.room_focus_id=r.room_id and house_type = 2"));
		$select->leftjoin(array("tc"=>"tenant_contract"), "r.contract_id = tc.contract_id");
		$select->where(array('rf.room_focus_id'=>$roomFocusId));
		$select->order("tc.dead_line DESC");
		$result = Select::pageSelect($select,null,1, 1);
		$data = $result['data'][0];
		$time = time();
		$output_data = array();
		if (!empty($data))
		{
			if ($data['dead_line']<=0)
			{
				$day = (($time-$data['create_time'])/86400)+1;
			}elseif ($data['dead_line']>=0)
			{
				$day = (($time-$data['dead_line'])/86400)+1;
			}
		}
		$output_data['day'] = floor($day);
		$output_data['money'] = $data['money'];
		return $output_data;
	}
	/**
	 * 添加房间
	 * 修改时间2015年4月22日 09:57:22
	 * 
	 * @author yzx
	 * @param array $data
	 * @return boolean|number
	 */
	public function addRoom($data,$user,$feeData=array())
	{
		$roomFocusModel = new \Common\Model\Erp\RoomFocus();
		$attachmentModel = new \Common\Model\Erp\Attachments();
		$feeHelper = new \Common\Helper\Erp\Fee();
		$roomFocusModel->Transaction();
		$data['status'] =  \Common\Model\Erp\RoomFocus::STATUS_NOT_RENTAL;
		$data['company_id'] = $user['company_id'];
		$data['create_uid'] = $user['user_id'];
		$data['owner_id'] = $user['user_id'];
		$new_room_id = $roomFocusModel->insert($data);
		if (!$new_room_id)
		{
			$roomFocusModel->rollback();
			return false;
		}
		if (is_array($data['image']) && !empty($data['image']))
		{
			$attachmentModel->delete(array("entity_id"=>$new_room_id,"module"=>"room_focus"));
			foreach ($data['image'] as $key=>$val)
			{
				$image_data['key'] = $val;
				$image_data['module'] = 'room_focus';
				$image_data['entity_id'] = $new_room_id;
				$attachmentModel->insertData($image_data);
			}
		}
		$roomFocusModel->updateFlatFloor($data['flat_id'],$user);
		if (!empty($feeData))
		{
			$feeHelper->addFee($feeData,  \Common\Model\Erp\Fee::SOURCE_FOCUS,$new_room_id);
		}
		$roomFocusModel->commit();
		return $new_room_id;
	}
	/**
	 * 获取房间楼层
	 * 修改时间2015年4月22日 18:05:05
	 * 
	 * @author yzx
	 * @param int $flatId
	 * @return Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >
	 */
	public function roomFloor($flatId)
	{
		$roomFocusModel = new \Common\Model\Erp\RoomFocus();
		$sql = $roomFocusModel->getSqlObject();
		$select = $sql->select(array("rf"=>"room_focus"));
		$select->leftjoin(array("rtr"=>"room_template_relation"), "rf.room_focus_id=rtr.room_id",array("room_id"));
		$select->leftjoin(array("ft"=>"flat_template"), "rtr.template_id=ft.template_id",array("template_name"));
		$select->where(array('rf.flat_id'=>$flatId));
		$select->group("rf.floor");
		$result = $select->execute();
		return $result;
	}
	/**
	 * 获取房间数据
	 * 修改时间2015年4月24日 13:48:04
	 * 
	 * @author yzx
	 * @param int $roomId
	 * @return Ambigous <boolean, multitype:, multitype:Ambigous <number, unknown> number , multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >
	 */
	public function getData($roomId,$field=array())
	{
		$roomFocusModel = new \Common\Model\Erp\RoomFocus();
		if(empty($field)){
			$data = $roomFocusModel->getOne(array("room_focus_id"=>$roomId));
		}else{
			$data = $roomFocusModel->getOne(array("room_focus_id"=>$roomId),$field);
		}
		
		return $data;
	}
	/**
	 * 统计楼层数据
	 * 修改时间2015年4月25日 13:52:17
	 * 
	 * @author yzx
	 * @param array $floorId
	 * @param int $flatId
	 * @param int $status
	 * @param int $is_yytz
	 * @param int $is_yd
	 * @return Ambigous <multitype:Ambigous <number, unknown> number , Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >>
	 */
	public function getFloorCount(array $floorId,$flatId,$status,$is_yytz=false,$is_yd=false)
	{
		$where = new Where();
		$where_in = new Where();
		$roomFocusModel = new \Common\Model\Erp\RoomFocus();
		$sql = $roomFocusModel->getSqlObject();
		$select = $sql->select("room_focus");
		$where->equalTo("flat_id", $flatId);
		$where->equalTo("is_delete", 0);
		$where_in->in("floor",$floorId);
		switch ($status)
		{
			case 1:
				$status_where = new Where();
				$status_where->equalTo("status", 1);
				$where->addPredicate($status_where);
				break;
			case 2:
				$status_where = new Where();
				$status_where->equalTo("status", 2);
				$where->addPredicate($status_where);
				break;
			case 3:
				$status_where = new Where();
				$status_where->equalTo("status", 3);
				$where->addPredicate($status_where);
				break;
		}
		if ($is_yytz)
		{
			$yytz_where = new Where();
			$yytz_where->equalTo("is_yytz", 1);
			$where->addPredicate($yytz_where);
		}
		if ($is_yd)
		{
			$yd_where = new Where();
			$yd_where->equalTo("is_yd", 1);
			$where->addPredicate($yd_where);
		}
		$where->addPredicate($where_in);
		$select->where($where);
		//print_r(str_replace('"', '', $select->getSqlString()));die();
		$result = Select::pageSelect($select,null, 1, 1);
		return $result['page']['count'];
	}
	/**
	 * 批量删除数据
	 * 修改时间2015年4月25日 15:20:42
	 * 
	 * @author yzx
	 * @param array $roomId
	 * @param int $flatId
	 * @param array $user
	 */
	public function  batchDelete($roomId,$flatId,$user)
	{
		$roomFocusModel = new \Common\Model\Erp\RoomFocus();
		$todoModel = new \Common\Model\Erp\Todo();
		$focus_data = $roomFocusModel->getOne(array("room_focus_id"=>$roomId));
		if ($this->delete($roomId))
		{
			$todoModel->delete(array("entity_id"=>$roomId,"module"=>$todoModel::MODEL_ROOM_FOCUS_RESERVE));
			$todoModel->delete(array("entity_id"=>$roomId,"module"=>$todoModel::MODEL_ROOM_FOCUS_RESERVE_OUT));
			$todoModel->delete(array("entity_id"=>$roomId,"module"=>$todoModel::MODEL_ROOM_FOCUS_STOP));
			$roomFocusModel->updateFlatFloor($flatId,$user);
			
			//写快照
			\Common\Helper\DataSnapshot::save(\Common\Helper\DataSnapshot::$SNAPSHOT_FOCUS_ROOM_DELETE, $roomId, $focus_data);
			return true;
		}
		return false;
	}
	
	/**
	 * 修改房间
	 * 修改时间2015年4月27日 10:13:12
	 * 
	 * @author yzx
	 * @param array $data
	 * @param int $roomId
	 * @param int $flatId
	 * @param array $user
	 * @return boolean
	 */
	public function updateRoomFocus($data,$roomId,$flatId,$user,$feeData=array())
	{
		$roomFocusModel = new \Common\Model\Erp\RoomFocus();
		$attachmentModel = new \Common\Model\Erp\Attachments();
		$feeHelper = new Fee();
		$roomFocusModel->Transaction();
		$result = $roomFocusModel->edit(array("room_focus_id"=>$roomId), $data);
		if (!$result)
		{
			$roomFocusModel->rollback();
			return false;
		}
		$f_result = $roomFocusModel->updateFlatFloor($flatId, $user);
		if (!$f_result)
		{
			$roomFocusModel->rollback();
			return false;
		}
		if (!empty($feeData))
		{
			$feeHelper->addFee($feeData,  \Common\Model\Erp\Fee::SOURCE_FOCUS,$roomId);
		}
		$attachmentModel->delete(array("module"=>"room_focus","entity_id"=>$roomId));
		if (is_array($data['image']) && !empty($data['image']))
		{
			foreach ($data['image'] as $key=>$val)
			{
				$imag_data['key'] = $val;
				$imag_data['module'] = "room_focus";
				$imag_data['entity_id'] = $roomId;
				$attachmentModel->insertData($imag_data);
			}
		}
		$roomFocusModel->commit();
		return true;
	}
	/**
	 * 计算空置率
	 * 修改时间2015年5月21日 09:55:29
	 * 
	 * @author yzx
	 * @param array $user
	 * @param int $flatId
	 */
	public function calculateMonthEmpty($user,$flatId,$isYear=false)
	{
		$start_time = strtotime($isYear ? date('Y-01-01') : date('Y-m-01'));
		$end_time = strtotime($isYear ? date('Y-12-31 23:59:59') : date('Y-m-t 23:59:59'));
		
		$roomFocusModel = new \Common\Model\Erp\RoomFocus();
		$sql = $roomFocusModel->getSqlObject();
		$select = $sql->select(array("rf"=>$roomFocusModel->getTableName()));
		$select->leftjoin(array("r"=>"rental"), new \Zend\Db\Sql\Predicate\Expression("rf.room_focus_id = r.room_id and house_type=2"));
		$select->leftjoin(array("tc"=>"tenant_contract"), "r.contract_id = tc.contract_id",array("end_line","signing_time"));
		$select->join(array("f"=>"flat"), "f.flat_id=rf.flat_id",array("flat_id"));
		$where = new \Zend\Db\Sql\Where();
		$where->equalTo('f.flat_id',$flatId);
		$where->equalTo('rf.is_delete', 0);
		$where->equalTo("f.city_id", $user['city_id']);
		
		$rentalwhere = new \Zend\Db\Sql\Where();
		$rentalwhere->equalTo('r.is_delete', 0);
		$orentalwhere = new \Zend\Db\Sql\Where();
		$orentalwhere->isNull('r.is_delete');
		$rentalwhere->orPredicate($orentalwhere);
		$where->andPredicate($rentalwhere);
		
		//权限
		if ($user['is_manager'] != \Common\Model\Erp\User::IS_MANAGER)
		{
			$permisions = \Common\Helper\Permissions::Factory('sys_housing_management');
			$permisions->VerifyDataCollectionsPermissionsModel($select , 'rf.flat_id=__TABLE__.authenticatee_id' , $permisions::USER_AUTHENTICATOR , $user['user_id'] , \Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED);
		}
		$timewhere = new \Zend\Db\Sql\Where();
		$timewhere->lessThanOrEqualTo('tc.signing_time', $end_time);
		$timewhere->greaterThanOrEqualTo('tc.end_line',$start_time);
		$timewhere2 = new \Zend\Db\Sql\Where();
		$timewhere2->isNull('tc.signing_time');
		$timewhere->orPredicate($timewhere2);
		
		$where->addPredicate($timewhere);
		$select->where($where);
		$select->order('tc.end_line');
		$data = $select->execute();
		if(empty($data)) return 0;
		$temp = array();
		foreach ($data as $value){
			if(!isset($temp[$value['room_focus_id']])){
				$temp[$value['room_focus_id']] = array();
			}
			$temp[$value['room_focus_id']][] = $value;
		}
		$data = $temp;unset($temp);
		//整理数据进行计算
		$time_day = ceil(($end_time-$start_time)/86400);
		$empty_rate = 0;
		foreach ($data as $room){
			$input_day = 0;
			$rental_day = 0;
			$create_time = 0;
			foreach ($room as $line){
				//示月出租天数
				$rental_day += (!$line['signing_time']) ? 0 : ceil((min($line['end_line'],$end_time) - max($start_time,$line['signing_time']))/86400);
				$create_time = $create_time ? min(max($start_time,$line['signing_time']),$create_time) : max($start_time,$line['signing_time']);
			}
			$input_day = ceil(($end_time - max($create_time,$start_time))/86400);//录入天数
			$empty_rate += $input_day ? (($input_day - min($rental_day,$input_day)) / $input_day) : 1;
		}
		$number = round($empty_rate / count($data) * 100,2);
		return $number >= 0 ? $number : 0;
		
		//return 0;

// 		$select = $sql->select(array("rf"=>$roomFocusModel->getTableName()));
// 		$select->leftjoin(array("r"=>"rental"), "rf.room_focus_id = r.room_id");
// 		$select->leftjoin(array("tc"=>"tenant_contract"), "r.contract_id = tc.contract_id",array("end_line","signing_time"));
// 		$select->leftjoin(array("f"=>"flat"), "f.flat_id=rf.flat_id",array("flat_name"));
// 		$select->where(array("rf.flat_id"=>$flatId));
// 		$select->where(array("rf.status"=>$roomFocusModel::STATUS_NOT_RENTAL));
// 		$select->where(array("rf.company_id"=>$user['company_id'],"rf.is_delete"=>0));
// 		$room_data = $select->execute();
		$emp_day = 0;
		if (!empty($room_data))
		{
			foreach ($room_data as $key=>$val)
			{
				$one_emp_day = $this->emptyDay($val,$isYear);
				$entering_day = $this->enteringDay($val);
				if ($isYear)
				{
					$entering_day = $this->enteringYear($val);
				}
				$this->day+=($one_emp_day/$entering_day);
			}
			$emp_day = number_format(($this->day/count($room_data))*100,2);
			$this->day=0;
		}
		return $emp_day;
	}
	/**
	 * 房间空置天数
	 * 修改时间2015年5月20日 13:39:23
	 *
	 * @author yzx
	 * @param unknown $data
	 * @return number
	 */
	private function emptyDay($data,$isYear=false)
	{
		$time = time();
		$landlordModel = new \Common\Model\Erp\Landlord();
		$sql = $landlordModel->getSqlObject();
		$select = $sql->select(array("l"=>$landlordModel->getTableName()));
		$select->leftjoin(array('lc'=>'landlord_contract'), "l.landlord_id = lc.landlord_id",array("end_line","free_day"));
		$select->where(array("l.hosue_name"=>$data['flat_name']));
		$select_data = $select->execute();
		//控制天数
		$entring_day=$this->enteringDay($data);
		if ($isYear)
		{
			$entring_day = $this->enteringYear($data);
		}
		$rental_day = 0;
		$free_day = 0;
		if ($data['end_line']>0)
		{
			$el_month = date("Ym",$data['end_line']);
			$t_month = date("Ym",$time);
			if ($el_month==$t_month)
			{
				//出租天数
				$rental_day = floor($time-date($data['signing_time'])/86400);
			}
			if ((date("Y"))==(date("Y",$data['end_line'])))
			{
				$rental_day = floor($time-date($data['signing_time'])/86400);
			}
				
		}
		//合同开始时间
		$signing_time_day = floor(($time-$data['signing_time'])/86400);
		//免租天数
		$free_day = $select_data['free_day'];
		//月空置天数
		$day = $entring_day-$rental_day-($free_day);
		if (($data['signing_time']+$free_day*86400)<$data['create_time'])
		{
			$day = $entring_day-$rental_day;
		}
		if ($day<=0)
		{
			$day=0;
		}
		return $day;
	}
	/**
	 * 月录入天数
	 * 修改时间2015年5月20日 14:19:42
	 *
	 * @author yzx
	 * @param unknown $data
	 * @return Ambigous <number, string>
	 */
	private function enteringDay($data)
	{
		$time = time();
		$creat_day = floor(($time-$data['create_time'])/86400);
		
		$begin_date=date('Y-m-01', strtotime(date("Y-m-d")));
		$end_data = date('d', strtotime("$begin_date +1 month -1 day"));
		if ($data['create_time']>=strtotime($begin_date))
		{
			$day = ($end_data-$creat_day)+1;
		}
		if ($data['create_time']<strtotime($begin_date))
		{
			$day = $end_data;
		}
		return $day;
	}
	/**
	 * 年录入天数
	 * 修改时间2015年5月21日 14:51:14
	 * 
	 * @author yzx
	 * @param unknown $data
	 */
	private function enteringYear($data)
	{
		$time = time();
		$begin_date=date('Y')."-01"."-01";
		$end_data = date("Y")."-12"."-31";
		if ($data['create_time']>=strtotime($begin_date)){
			$creat_day = ((strtotime($end_data)-$data['create_time'])/86400)+1;
		}
		if ($data['create_time']<strtotime($begin_date)){
			$creat_day = 365;
		}
		return $creat_day;
	}
	/**
	 * 获取房间合同
	 * 修改时间2015年6月4日19:24:21
	 * 
	 * @author yzx
	 * @param unknown $roomdata
	 * @return Ambigous <number, NULL, \ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown>
	 */
	public function getRoomContract($roomdata){
		$rentalModel = new \Common\Model\Erp\Rental();
		$sql = $rentalModel->getSqlObject();
		$select = $sql->select(array("rt"=>$rentalModel->getTableName()));
		$select->leftjoin(array("tc"=>'tenant_contract'), "rt.contract_id=tc.contract_id",array("parent_id"));
		$select->where(array("rt.room_id"=>$roomdata['room_focus_id']));
		$select->where(array("rt.house_type"=>$rentalModel::HOUSE_TYPE_F));
		$select->where(array("rt.is_delete"=>0));
		$result = $select->execute();
		$contract_id = 0;
		if (!empty($result)){
			foreach ($result as $key=>$val){
				if ($val['parent_id']<=0){
					$contract_id = $val['contract_id'];
				}
			}
		}
		return $contract_id;
	}
	public function chackHouseRoomNumber($flatId){
		$flatModel = new \Common\Model\Erp\Flat();
		$roomFocusModel = new \Common\Model\Erp\RoomFocus();
		$flat_data = $flatModel->getOne(array("flat_id"=>$flatId));
		$room_data = $roomFocusModel->getData(array("flat_id"=>$flatId));
		$group_number = $flat_data['group_number'];
			foreach ($room_data as $key=>$val){
				if (intval($val['house_number'])<=0){
					for ($i=1;$i<=$group_number;$i++){
						if (strlen($i) == 1)
						{
							$k = str_pad($i , 2 , 0 , STR_PAD_LEFT);
						}
						else
						{
							$k = $i;
						}
						$serche_key = $val['floor'].$k;
							
						if (strstr($val['custom_number'], $serche_key)){
								$roomFocusModel->edit(array("room_focus_id"=>$val['room_focus_id']), array("house_number"=>$k));
						}
					}
				}
			}
	}
	/**
	 * 获取集中式房间的租客合同
	 * 修改时间2015年6月4日18:07:29
	 *
	 * @author yzx
	 * @param unknown $data
	 */
	public function getFocusRoomContract($data)
	{
		$rentalModel = new \Common\Model\Erp\Rental();
		$sql = $rentalModel->getSqlObject();
		$select = $sql->select(array("r" => 'room_focus'));
		$select->leftjoin(array('rt' => "rental") , "rt.room_id=r.room_focus_id" , array("is_delete"));
		$select->leftjoin(array("tc" => 'tenant_contract') , "rt.contract_id=tc.contract_id" , array("parent_id" , "contract_id",'signing_time','end_line','next_pay_time','is_haveson'));
		$select->where(array("rt.room_id" => $data['room_id']));
		$select->where(array("r.status" => 2));
		$select->where(array("rt.house_type" => $rentalModel::HOUSE_TYPE_F));
		$select->where(array("rt.is_delete"=>0,"tc.is_stop"=>0));
		//print_r($select->getSqlString());die();
		$result = $select->execute();
		$contract_id = 0;
		if (!empty($result))
		{
			foreach ($result as $key => $val)
			{
				if ($val['is_haveson'] ==1 && $val['next_pay_time'] < $val['end_line']){
					$contract_id = $val['contract_id'];
					return $contract_id;
					break;
				}else
				{
					if ($val['is_haveson'] ==0 && time() <=$val['end_line']){
						$contract_id = $val['contract_id'];
						return $contract_id;
						break;
					}
				}
			}
		}
		return 0;
	}
	
}