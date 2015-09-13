<?php
    namespace Common\Helper\Erp;
				class Fee extends \Core\Object
    {

        /**
         * 添加房间的付款项
         * 修改时间2015年5月6日 15:05:04
         * 
         * @author yzx
         * @param array $feeData
         * @param string $source
         * @return boolean
         */
        public function addFee($feeData , $source , $sourceId,$house_data=array())
        {
            $feeModel = new \Common\Model\Erp\Fee();
            $roomModel = new \Common\Model\Erp\Room();
            $feeModel->delete(array("source_id" => $sourceId , "source" => $source));
            if (is_array($feeData) && !empty($feeData))
            {
                foreach ($feeData as $key => $val)
                {
                    $val['create_time'] = time();
                    $val['source_id'] = $sourceId;
                    $val['source'] = $source;
                    $result = $feeModel->insert($val);
                    if (!$result)
                    {
                        return false;
                        exit();
                    }
                    if (is_numeric($val['now_meter'])){
                    	if (!empty($house_data)){
                    		$room_data = $roomModel->getData(array("house_id"=>$sourceId,"is_delete"=>0));
                    		if (!empty($room_data) && $val['payment_mode'] == 5){
                    			foreach ($room_data as $rkey=>$rval){
                    				$r_source = $feeModel::SOURCE_DISPERSE_ROOM;
                    				$r_val = $val;
                    				$r_val['source_id'] = $rval['room_id'];
                    				$r_val['source'] = $r_source;
                    				$r_val['create_time'] = time();
                    				//$this->deleteMeter($r_source, $r_val);
                    				$this->creatMeterReading($r_val, $r_source);
                    			}
                    		}
                    	}
                    	//$this->deleteMeter($source, $val);
                    	$this->creatMeterReading($val, $source);
                    }
                }
                return true;
            }
            return false;
        }
        /**
         * 删除抄表数据
         * 修改时间2015年7月23日 16:37:53
         * 
         * @author yzx
         * @param unknown $source
         * @param unknown $data
         */
		private function deleteMeter($source,$data){
			$meterReadingModel = new \Common\Model\Erp\MeterReading();
			$meterReadingMoneyModel = new \Common\Model\Erp\MeterReadingMoney();
			switch ($source){
        		case \Common\Model\Erp\Fee::SOURCE_DISPERSE:
        			$meter_reading_data = $meterReadingModel->getOne(array("house_type"=>$meterReadingModel::HOUSE_TYPE_C,
        																   "house_id"=>$data['source_id'],"fee_type_id"=>$data['fee_type_id']));
        			$d_res = $meterReadingModel->delete(array("house_type"=>$meterReadingModel::HOUSE_TYPE_C,
        													  "house_id"=>$data['source_id'],"fee_type_id"=>$data['fee_type_id']));
        			if ($d_res){
        				$meterReadingMoneyModel->delete(array("meter_reading_id"=>$meter_reading_data['meter_id']));
        			}
        			$meter_reading_data = array();
        			$d_res = null;
        			break;
        		case \Common\Model\Erp\Fee::SOURCE_DISPERSE_ROOM:
        			$meter_reading_data = $meterReadingModel->getOne(array("house_type"=>$meterReadingModel::HOUSE_TYPE_C,
        															 	   "room_id"=>$data['source_id'],"fee_type_id"=>$data['fee_type_id']));
        			$d_res = $meterReadingModel->delete(array("house_type"=>$meterReadingModel::HOUSE_TYPE_C,
        													  "room_id"=>$data['source_id'],"fee_type_id"=>$data['fee_type_id']));
        			if ($d_res){
        				$meterReadingMoneyModel->delete(array("meter_reading_id"=>$meter_reading_data['meter_id']));
        			}
        			$meter_reading_data = array();
        			$d_res = null;
        			break;
        		case \Common\Model\Erp\Fee::SOURCE_FOCUS:
        			$meter_reading_data = $meterReadingModel->getOne(array("house_type"=>$meterReadingModel::HOUSE_TYPE_F,
        																   "house_id"=>$data['source_id'],"fee_type_id"=>$data['fee_type_id']));
        			$d_res = $meterReadingModel->delete(array("house_type"=>$meterReadingModel::HOUSE_TYPE_F,
        													  "house_id"=>$data['source_id'],"fee_type_id"=>$data['fee_type_id']));
        			if ($d_res){
        				$meterReadingMoneyModel->delete(array("meter_reading_id"=>$meter_reading_data['meter_id']));
        			}
        			break;
        	}
		}
        /**
         * 获取房间的费用信息
         * 修改时间 2015年5月11日15:55:44
         * 
         * @auhor ft
         */
        public function getRoomFeeInfo($room_id, $cid = '', $source, $fee_type_id = 0)
        {
            $fee_model = new \Common\Model\Erp\Fee();
            $sql = $fee_model->getSqlObject();
            $select = $sql->select(array('f' => 'fee'));
            $select->columns(
                    array(
                        'fee_id' => 'fee_id' ,
                        'fee_type_id' => 'fee_type_id' ,
                        'payment_mode' => 'payment_mode' ,
                        'source_id' => 'source_id' ,
                        'money' => 'money' ,
                    	'create_time' => 'create_time'
            ));
            $select->leftjoin(array('ft' => 'fee_type') , 'f.fee_type_id = ft.fee_type_id' , array('type_name' => 'type_name'));
            $where = new \Zend\Db\Sql\Where();
            $where->equalTo('f.source_id' , $room_id);
            $where->equalTo("f.is_delete", 0);
            if (is_numeric($cid))
                $where->equalTo('ft.company_id' , $cid);
            if ($fee_type_id>0)
            	$where->equalTo("f.fee_type_id", $fee_type_id);
            $where->equalTo("f.source" , $source);
            $select->where($where);
           // print_r(str_replace('"', '', $select->getSqlString()))."<hr/>";
            return $select->execute();
        }

        /**
         * 获取实体数据
         * 修改时间2015年5月12日 09:33:12
         * 
         * @author yzx
         * @param int $sourceId
         * @param string $source
         * @return Ambigous <boolean, multitype:, multitype:NULL Ambigous <\ArrayObject, multitype:, \Zend\Db\ResultSet\mixed, unknown> >
         */
        public function getDataBySource($sourceId , $source)
        {
            $feeModel = new \Common\Model\Erp\Fee();
            $sql = $feeModel->getSqlObject();
            $select = $sql->select($feeModel->getTableName());
            $select->where(array("source" => $source , "source_id" => $sourceId,"is_delete"=>0));
            $select->order("create_time desc");
            return $select->execute();
        }
        /**
         * 创建抄表底度
         * 修改时间2015年5月29日 14:41:46
         * 
         * @author yzx
         * @param array $data
         * @param int $source
         */
        private function creatMeterReading($data,$source){
        	$meterReadingHelper = new \Common\Helper\Erp\MeterReading();
        	$meterReadingModel = new \Common\Model\Erp\MeterReading();
        	$userHelper = new User();
        	$user = $userHelper->getCurrentUser();
        	switch ($source){
        		case \Common\Model\Erp\Fee::SOURCE_DISPERSE:
        			$meter_reading_data['house_id'] = $data['source_id'];
        			$meter_reading_data['room_id'] = 0;
        			$house_type = $meterReadingModel::HOUSE_TYPE_C;
        		break;
        		case \Common\Model\Erp\Fee::SOURCE_DISPERSE_ROOM:
        			$meter_reading_data['house_id'] = 0;
        			$meter_reading_data['room_id'] = $data['source_id'];
        			$house_type = $meterReadingModel::HOUSE_TYPE_C;
        		break;
        		case \Common\Model\Erp\Fee::SOURCE_FOCUS:
        			$meter_reading_data['house_id'] = $data['source_id'];
        			$meter_reading_data['room_id'] = 0;
        			$house_type = $meterReadingModel::HOUSE_TYPE_F;
        		break;
        	}
        		$meter_reading_data['before_meter'] =0;
        		$meter_reading_data['now_meter'] = $data['du']?$data['du']:0;
        		$meter_reading_data['add_time'] = strtotime($data['cbdate'])?strtotime($data['cbdate']):time();
        		$meter_reading_data['fee_type_id'] = $data['fee_type_id'];
        		$meter_reading_data['creat_user_id'] = $user['user_id'];
        		$meter_reading_data['company_id'] = $user['company_id'];
        		$meter_reading_data['house_type'] = $house_type;
        		$get_meter_reading_data = $meterReadingModel->getData(array("house_id"=>$meter_reading_data['house_id'],
        																	"room_id"=>$meter_reading_data['room_id'],
        																	"house_type"=>$house_type,
        																	"fee_type_id"=>$meter_reading_data['fee_type_id']));
        		if (empty($get_meter_reading_data)){
        			$meterReadingHelper->addData($meter_reading_data, $house_type);
        		}
        }

    }