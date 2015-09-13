<?php
namespace App\Web\Mvc\Controller\Finance;
use App\Web\Helper\Room;
use App\Web\Lib\Request;
use App\Web\Helper\Url;
use Zend\Db\Sql\Expression;
use Common\Helper\Permissions;
use Common\Helper\Permissions\Hook\SysHousingManagement;
class SerialController extends \App\Web\Lib\Controller
{
    protected $_auth_module_name = 'sys_water_management';

    /**
     * 获取财务详情
     * 修改时间2015年4月19日 10:24:08
     *
     * @author lishengyou
     * @@已经没有流水详情了，直接到编辑页面
     */
    protected function detailAction()
    {
        $serial_id = Request::queryString("get.serial_id",0,"int");
        $user = $this->getUser();
        if(!$serial_id){
            die('');
        }
        $serialNumber = '';
    }

    /**
     * 添加流水
     * 修改时间2015年4月17日 13:46:40
     *
     * @author ft
     * @添加了新字段，需要处理
     */
    protected function addincomeAction() {
        if(!$this->verifyModulePermissions(\Common\Helper\Permissions::INSERT_AUTH_ACTION)){
            return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("finance-serial/addincome")));
        }

        $city_id = $this->user['city_id'];
        $user_id = $this->user['user_id'];
        $company_id = $this->user['company_id'];
        if (!Request::isPost()) {
            //1 预定房间部分
            $reserve_id = I('get.reserve_id', 0);
            //2 租客合同部分
            $tenant_contract_source = I('get.source', '', 'string');
            $tenant_contract_id = I('get.source_id', 0, 'int');//租客合同id
            $house_type = I('get.source_type', 0, 'int');//房源类型,(集中/分散)
            $source_cate = I('get.source_category', '');          //fee表要用的source
            $con_relet = I('get.relet', 0);               //为1合同续租 
            //3 抄表数据部分
            $meter_read = I('get.meter', ''); //区分是来自抄表的数据
            $meter_house_type = I('get.house_type', 0);//房源类型(分散/集中)
            $house_id = I('get.house_id', 0);//房源id 分散式(合租house_id=0|整组house_id=house_id), 集中式(house_id=0);
            $room_id = I('get.room_id', 0);//房间id 分散式(合租room_id=room_id|整组room_id=0), 集中式(room_id=room_focus_id);
            $meter_money = I('get.sum_money', 0); //抄表金额
            $fee_type_id = I('get.fee_type_id', 0);//费用类型id
            $house_name = urldecode(I('get.house_name', '')); //房源名称和房间编号(集中式|分散式)
            //4 首页待办事项租客合同id(二房东收租)
            $index_tenant_con_id = I('get.contract_id', 0);
            //5 终止业主合同id
            $landlord_contract_id = I('get.Landlord_contract_id', 0);

            //新增收入公共的数据部分
            $fee_type_model = new \Common\Model\Erp\FeeType();
            $configModel = new \Common\Model\Erp\SystemConfig();
            $pay_type = $configModel->getFind('Serial', 'payType');    //支付类型 支付宝等
            //该公司id所有费用类型
            $fee_type_list = $fee_type_model->getData(array('company_id' => $company_id, 'is_delete' => 0));

            //1 租客合同页面显示的逻辑
            if ($tenant_contract_source == 'tenant_contract') {
                $tenant_model = new \Common\Model\Erp\TenantContract();
                //获取合同租金和押金
                $tenant_money = $tenant_model->getContractRentAndDeposit($tenant_contract_id);
                //租住表model
                $rental_model = new \Common\Model\Erp\Rental();
                //查询合同对应房间 的条件
                $where = array('contract_id' => $tenant_contract_id, 'house_type' => $house_type, 'is_delete' => 0);
                //根据合同id 查询的房间id
                $contract_info = $rental_model->getOne($where);
                $house_id = $contract_info['house_type'] == 1 ? $contract_info['house_id'] : $contract_info['room_id'];
                
                if ($company_id !== $tenant_money[0]['company_id']) {
                    return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("finance-serial/addincome")));
                }
                //判断用户是否对当前对象有权限操作
                if(!$this->verifyDataLinePermissions(Permissions::INSERT_AUTH_ACTION, $house_id, $contract_info['house_type'] == 1 ? \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE : \Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED)){
                   return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("finance-serial/addincome")));
                }
                $fee_helper = new \Common\Helper\Erp\Fee();    //费用表helper
                $fee_model = new \Common\Model\Erp\Fee();
                $room_helper = new \Common\Helper\Erp\Room();
                $serial_helper = new \Common\Helper\Erp\SerialNumber();
                //拼装房间默认的费用类型(集中/分散)
                $tenant_fee = $serial_helper->returnContractFeeType($fee_type_list, $tenant_money[0], $con_relet);
                //给页面区分是否是合同
                $this->assign('tenant_contract_source', $tenant_contract_source);
                $this->assign('contract_id', $tenant_contract_id);//租客合同id
                $this->assign('house_type', $house_type);
                //查询房间的名称
                $room_name = $room_helper->getRoomNameById($contract_info['house_id'], $contract_info['room_id'], $house_type);
                
                //获取房间费用配置
                $fee_data = $serial_helper->getRoomFeeConfig($contract_info);
                //如果这个房间有费用配置
                if ($fee_data) {
                    //取出费用类型id
                    foreach ($fee_data as $fee_info) {
                        $ftype_id[] = $fee_info['fee_type_id'];//该房间的费用类型id
                    }
                    $ftype_where = array('fee_type_id' => $ftype_id, 'company_id' => $company_id);
                    //该房间的费用类型
                    $room_ftype_data = $fee_type_model->getFeeTypeById($ftype_where);
                    //过滤一次性收费
                    $fee_id = $serial_helper->checkDisposableFee($tenant_contract_id, $fee_data);
                    foreach ($fee_data as $key => $fee_info) {
                        if ($fee_info['payment_mode'] == 1) {
                            //1 按房间一次性收费
                            $fee_data[$key]['money'] = $fee_info['money'];
                        }
                        elseif ($fee_info['payment_mode'] == 2) {
                            //2 按房间每月收费
                            $fee_data[$key]['money'] = $fee_info['money'] * $tenant_money[0]['pay'];
                        }
                        elseif ($fee_info['payment_mode'] == 6) {
                            //6 按人头一次性收费
                            $contract_rental_model = new \Common\Model\Erp\ContractRental();
                            $cr_where = array('contract_id' => $tenant_contract_id, 'is_delete' => 0);
                            $cr_columns = array('total_pople' => new Expression('count(contract_rental_id)'));
                            $total_pople = $contract_rental_model->getOne($cr_where, $cr_columns);
                            $fee_data[$key]['money'] = $fee_info['money'] * $total_pople['total_pople'];
                        }
                        elseif ($fee_info['payment_mode'] == 7) {
                            //7 按人头每月收费
                            $contract_rental_model = new \Common\Model\Erp\ContractRental();
                            $cr_where = array('contract_id' => $tenant_contract_id, 'is_delete' => 0);
                            $cr_columns = array('total_pople' => new Expression('count(contract_rental_id)'));
                            $total_pople = $contract_rental_model->getOne($cr_where, $cr_columns);
                            $fee_data[$key]['money'] = $fee_info['money'] * $tenant_money[0]['pay'] * $total_pople['total_pople'];
                        }
                    }
                    //让fee表查出的费用类型, 与fee_type表里的费用类型顺序相对
                    foreach ($fee_data as $key => $fee) {
                        foreach ($room_ftype_data as $ftype) {
                            if ($fee['fee_type_id'] == $ftype['fee_type_id']) {
                                $fee_data[$key]['type_name'] = $ftype['type_name'];
                            }
                        }
                        if (in_array($fee['fee_type_id'], $fee_id)) {
                            unset($fee_data[$key]);
                        }
                    }
                }
                unset($room_ftype_data);
                //获取抄表的费用类型金额
                $meter_reading_helper = new \Common\Helper\Erp\MeterReading();
                $meter_money_data = $meter_reading_helper->getMeterMoneyByRoomId($contract_info['house_id'], $house_type, $contract_info['room_id']);

                if ($meter_money_data) {//如果存在抄表
                    $meter_id = array();
                    $meter_arr = array();
                    foreach ($meter_money_data as $key => $val) {
                        if ($val['money'] != 0) {
                            if ($house_type == 1) {//分散
                                   if ($contract_info['house_id'] > 0 && $contract_info['room_id'] < 0) {//整
                                    $edit_meter[] = array('meter_id' => $val['meter_id'], 'house_id' => $contract_info['house_id'], 'house_type' => $house_type);
                                   } else {//合
                                    $edit_meter[] = array('meter_id' => $val['meter_id'], 'room_id' => $contract_info['room_id'], 'house_type' => $house_type);
                                   }
                            } else {//集中
                                $edit_meter[] = array('meter_id' => $val['meter_id'], 'room_id' => $contract_info['room_id'], 'house_type' => $house_type);
                            }
                            $meter_arr[$val['fee_type_id']]['money'] += $val['money'];
                            $meter_arr[$val['fee_type_id']]['fee_type_id'] = $val['fee_type_id'];
                            $meter_arr[$val['fee_type_id']]['type_name'] = $val['type_name'];
                            $extract_money += $val['money'];
                        } else {
                            unset($meter_money_data[$key]);
                        }
                    }
                    if ($meter_arr) {
                        $_SESSION['edit_meter'] = $edit_meter;
                        foreach ($fee_data as $key => $fee) {
                            foreach ($meter_arr as $meter_val) {
                                if (!in_array($fee['fee_type_id'], $meter_val) && ($fee['payment_mode'] == 3 || $fee['payment_mode'] == 4 || $fee['payment_mode'] == 5)) {
                                    unset($fee_data[$key]);
                                }
                            }
                        }
                    } else {
                        foreach ($fee_data as $key => $v) {
                            if ($v['payment_mode'] == 3 || $v['payment_mode'] == 4 || $v['payment_mode'] == 5) unset($fee_data[$key]);
                        }
                    }
                    foreach ($fee_data as $key => $val) {
                        $fee_arr[$val['fee_type_id']]['money'] = $val['money'];
                        $fee_arr[$val['fee_type_id']]['fee_type_id'] = $val['fee_type_id'];
                        $fee_arr[$val['fee_type_id']]['type_name'] = $val['type_name'];
                    }
                    if ($meter_arr && $fee_arr) {//有抄表,也有房间配置
                        foreach ($fee_arr as $key => $fee) {
                            if (!in_array($fee['fee_type_id'], $meter_arr[$key])) {
                                $meter_arr[] = $fee;
                            }
                        }
                        $fee_type_arr = array_merge($tenant_fee, $meter_arr);
                    } else {
                        $fee_type_arr = array_merge($tenant_fee, $fee_data);
                    }
                } else {
                    foreach ($fee_data as $key => $v) {
                        if ($v['payment_mode'] == 3 || $v['payment_mode'] == 4 || $v['payment_mode'] == 5) unset($fee_data[$key]);
                    }
                    $fee_type_arr = array_merge($tenant_fee, $fee_data);
                }
                foreach ($fee_type_arr as $fee) {
                    $contract_money += $fee['money'];
                }
                if ($extract_money) {
                    $contract_money = $contract_money - $extract_money;
                    $_SESSION['extract_money'] = $extract_money;
                }
                $res = $tenant_model->edit(array('contract_id' => $contract_id), array('next_pay_money' => $contract_money));
                $this->assign('room_name', $room_name[0]);
                $this->assign('fee_type_arr', $fee_type_arr);
            //2 房间预定的页面显示逻辑
            } elseif ($reserve_id) {
                $reserve_source = I('get.reserve_source', '');
                $reserve_model = new \Common\Model\Erp\Reserve();
                $reserve_info = $reserve_model->getOne(array('reserve_id' => $reserve_id), array('house_id' => 'house_id', 'room_id' => 'room_id', 'house_type' => 'house_type'));
                if ($reserve_info['house_type'] == 1) {
                    $house_id = $reserve_info['house_id'];
                    $house_model = new \Common\Model\Erp\House();
                    $company_info = $house_model->getOne(array('house_id' => $reserve_info['house_id']), array('company_id' => 'company_id'));
                } else {
                    $house_id = $reserve_info['room_id'];
                    $room_focus_model = new \Common\Model\Erp\RoomFocus();
                    $company_info = $room_focus_model->getOne(array('room_focus_id' => $reserve_info['room_id']), array('company_id' => 'company_id'));
                }
                if ($company_id !== $company_info['company_id']) {
                    return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("finance-serial/addincome")));
                }
                //判断用户是否对当前对象有权限操作
                if(!$this->verifyDataLinePermissions(Permissions::INSERT_AUTH_ACTION, $house_id, $reserve_info['house_type'] == 1 ? \Common\Helper\Permissions\Hook\SysHousingManagement::DECENTRALIZED_HOUSE : \Common\Helper\Permissions\Hook\SysHousingManagement::CENTRALIZED)){
                    return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("finance-serial/addincome")));
                }
                //添加预定后,添加新增收入流水
                $reserve_helper = new \Common\Helper\Erp\Reserve();
                $room_helper = new \Common\Helper\Erp\Room();
                //根据预定id查询预定信息
                $reserve_info = $reserve_helper->getReserveInfoById($reserve_id);
                //取出定金费用类型
                foreach ($fee_type_list as $fee_type) {
                    if ($fee_type['type_name'] == '定金') {
                        $deposit_fee = $fee_type;
                    }
                }
                $room_info = $room_helper->getRoomInfoById($reserve_info[0]);
                $room_info[0]['money'] = $reserve_info[0]['money']; //预定的定金
                $this->assign('rental_way', $reserve_info[0]['rental_way']);
                $this->assign('reserve_info', $reserve_info[0]);
                $this->assign('reserve_id', $reserve_id);    //预定的id
                $this->assign('reserve_source', $reserve_source);    //预定的id
                $this->assign('deposit_fee', $deposit_fee);  //预定费用类型(定金)
                $this->assign('room_info', $room_info[0]);   //房间名字地址信息
                $this->assign('pay_type', $pay_type);    //支付类型

            //3 抄表页面的显示逻辑
            } elseif ($meter_read) {
                if ($meter_house_type == 1) {
                    //分散式整组合租SOURCE
                    $source_cate = ($room_id > 0 && $house_id == 0) ? 'SOURCE_DISPERSE_ROOM' : (($room_id == 0 && $house_id > 0) ? 'SOURCE_DISPERSE' : '');
                } else {//集中式SOURCE
                    $source_cate = ($room_id > 0 && $house_id == 0) ? 'SOURCE_FOCUS' : '';
                }
                //房间的费用配置
                $fee_helper = new \Common\Helper\Erp\Fee();
                $fee_where = array('source' => $source_cate, 'source_id' => $room_id, 'is_delete' => 0);
                if ($source_cate == 'SOURCE_DISPERSE') {//整组
                    $meter_fee_config = $fee_helper->getRoomFeeInfo($house_id, $company_id, $source_cate);
                } else {//合租 / 集中式
                    $meter_fee_config = $fee_helper->getRoomFeeInfo($room_id, $company_id, $source_cate);
                }
                foreach ($fee_type_list as $key => $fee_type) {
                    if ($fee_type['fee_type_id'] == $fee_type_id) {
                        $meter_fee_type[$key] = $fee_type;
                        $meter_fee_type[$key]['money'] = $meter_money;
                    }
                }
                //将房间抄表费用和该房间已有费用配置合并
                $room_meter_fee = array_merge($meter_fee_config, $meter_fee_type);
                $this->assign('meter', $meter_read);    //用于页面区分抄表部分
                $this->assign('house_id', $house_id);
                $this->assign('room_id', $room_id);
                $this->assign('house_name', $house_name);//房源名称和房间编号
                $this->assign('meter_house_type', $meter_house_type);//房源类型
                $this->assign('meter_money', $meter_money);//抄表金额
                $this->assign('meter_fee_type', $room_meter_fee);//房间抄表和已有费用类型

            //4 首页待办事项租客合同收租
            } elseif($index_tenant_con_id) {
                $rental_helper = new \Common\Helper\Erp\Rental();
                $fee_model = new \Common\Model\Erp\Fee();
                //通过合同id获取房间房源信息
                $rental_info = $rental_helper->getRentInfoById($index_tenant_con_id);
                //集中/分散式房间的 地址信息
                $rents_room_info = $rental_helper->getRoomName($rental_info[0]);
                //检查第一次有没有收费
                $serial_helper = new \Common\Helper\Erp\SerialNumber();
                $source = array('source' => 'tenant_contract', 'source_id' => $index_tenant_con_id);
                $res = $serial_helper->validateUserFirstWhetherCharge($source);
                //合同费用类型
                $tenant_fee = $serial_helper->returnContractFeeType($fee_type_list, $rental_info[0], $res);
                //获取房间费用配置
                $fee_data = $serial_helper->getRoomFeeConfig($rental_info[0]);
                //如果这个房间有费用配置
                if ($fee_data) {
                    //取出费用类型id
                    foreach ($fee_data as $fee_info) {
                        $ftype_id[] = $fee_info['fee_type_id'];//该房间的费用类型id
                    }
                    $ftype_where = array('fee_type_id' => $ftype_id, 'company_id' => $company_id);
                    //该房间的费用类型
                    $room_ftype_data = $fee_type_model->getFeeTypeById($ftype_where);
                    //过滤一次性收费
                    $fee_id = $serial_helper->checkDisposableFee($index_tenant_con_id, $fee_data);
                        foreach ($fee_data as $key => $fee_info) {
                            if ($fee_info['payment_mode'] == 1) {
                                //1 按房间一次性收费
                                $fee_data[$key]['money'] = $fee_info['money'];
                            }
                            elseif ($fee_info['payment_mode'] == 2) {
                                //2 按房间每月收费
                                $fee_data[$key]['money'] = $fee_info['money'] * $rental_info[0]['pay'];
                            }
                            elseif ($fee_info['payment_mode'] == 6) {
                                //6 按人头一次性收费
                                $contract_rental_model = new \Common\Model\Erp\ContractRental();
                                $cr_where = array('contract_id' => $index_tenant_con_id, 'is_delete' => 0);
                                $cr_columns = array('total_pople' => new Expression('count(contract_rental_id)'));
                                $total_pople = $contract_rental_model->getOne($cr_where, $cr_columns);
                                $fee_data[$key]['money'] = $fee_info['money'] * $total_pople['total_pople'];
                            }
                            elseif ($fee_info['payment_mode'] == 7) {
                                //7 按人头每月收费
                                $contract_rental_model = new \Common\Model\Erp\ContractRental();
                                $cr_where = array('contract_id' => $index_tenant_con_id, 'is_delete' => 0);
                                $cr_columns = array('total_pople' => new Expression('count(contract_rental_id)'));
                                $total_pople = $contract_rental_model->getOne($cr_where, $cr_columns);
                                $fee_data[$key]['money'] = $fee_info['money'] * $rental_info[0]['pay'] * $total_pople['total_pople'];
                            }
                        }
                    //让fee表查出的费用类型, 与fee_type表里的费用类型顺序相对
                    foreach ($fee_data as $key => $fee) {
                        foreach ($room_ftype_data as $ftype) {
                            if ($fee['fee_type_id'] == $ftype['fee_type_id']) {
                                $fee_data[$key]['type_name'] = $ftype['type_name'];
                            }
                        }
                        if (in_array($fee['fee_type_id'], $fee_id)) {
                            unset($fee_data[$key]);
                        }
                    }
                }
                unset($room_ftype_data);
                //获取抄表的费用类型金额
                $meter_reading_helper = new \Common\Helper\Erp\MeterReading();
                $meter_money_data = $meter_reading_helper->getMeterMoneyByRoomId($rental_info[0]['house_id'], $rental_info[0]['house_type'], $rental_info[0]['room_id']);
                if ($meter_money_data) {
                    $meter_id = array();
                    $meter_arr = array();
                    foreach ($meter_money_data as $key => $val) {
                        if ($val['money'] != 0) {
                            if ($house_type == 1) {//分散
                                if ($rental_info[0]['house_id'] > 0 && $rental_info[0]['room_id'] < 0) {//整
                                    $edit_meter[] = array('meter_id' => $val['meter_id'], 'house_id' => $rental_info[0]['house_id'], 'house_type' => $house_type);
                                } else {//合
                                    $edit_meter[] = array('meter_id' => $val['meter_id'], 'room_id' => $rental_info[0]['room_id'], 'house_type' => $house_type);
                                   }
                            } else {//集中
                                $edit_meter[] = array('meter_id' => $val['meter_id'], 'room_id' => $rental_info[0]['room_id'], 'house_type' => $house_type);
                            }
                            $meter_arr[$val['fee_type_id']]['money'] += $val['money'];
                            $meter_arr[$val['fee_type_id']]['fee_type_id'] = $val['fee_type_id'];
                            $meter_arr[$val['fee_type_id']]['type_name'] = $val['type_name'];
                            //提出抄表金额
                            $extract_money += $val['money'];
                        } else {
                            unset($meter_money_data[$key]);
                        }
                    }
                    if ($meter_arr) {
                        $_SESSION['edit_meter'] = $edit_meter;
                        foreach ($fee_data as $key => $fee) {
                            foreach ($meter_arr as $meter_val) {
                                if (!in_array($fee['fee_type_id'], $meter_val) && ($fee['payment_mode'] == 3 || $fee['payment_mode'] == 4 || $fee['payment_mode'] == 5)) {
                                    unset($fee_data[$key]);
                                }
                            }
                        }
                    } else {
                        foreach ($fee_data as $key => $v) {
                            if ($v['payment_mode'] == 3 || $v['payment_mode'] == 4 || $v['payment_mode'] == 5) unset($fee_data[$key]);
                        }
                    }
                    
                    foreach ($fee_data as $key => $val) {
                        $fee_arr[$val['fee_type_id']]['money'] = $val['money'];
                        $fee_arr[$val['fee_type_id']]['fee_type_id'] = $val['fee_type_id'];
                        $fee_arr[$val['fee_type_id']]['type_name'] = $val['type_name'];
                    }
                    if ($meter_arr && $fee_arr) {//有抄表,也有房间配置
                        foreach ($fee_arr as $key => $fee) {
                            if (!in_array($fee['fee_type_id'], $meter_arr[$key])) {
                                $meter_arr[] = $fee;
                            }
                        }
                        $fee_type_arr = array_merge($tenant_fee, $meter_arr);
                    } else {
                        $fee_type_arr = array_merge($tenant_fee, $fee_data);
                    }
                } else {
                    foreach ($fee_data as $key => $v) {
                        if ($v['payment_mode'] == 3 || $v['payment_mode'] == 4 || $v['payment_mode'] == 5) unset($fee_data[$key]);
                    }
                    $fee_type_arr = array_merge($tenant_fee, $fee_data);
                }
                foreach ($fee_type_arr as $fee) {
                    $contract_money += $fee['money'];
                }
                if ($extract_money) {
                    $contract_money = $contract_money - $extract_money;
                    $_SESSION['extract_money'] = $extract_money;
                }
                $tc_contract_model = new \Common\Model\Erp\TenantContract();
                $res = $tc_contract_model->edit(array('contract_id' => $contract_id), array('next_pay_money' => $contract_money));
                
                $this->assign('rental_info', $rental_info[0]);
                $this->assign('fee_type_arr', $fee_type_arr);    //合同费用,租金
                $this->assign('rents_room_info', $rents_room_info[0]);//房间地址信息
                $this->assign('index_tenant_con_id', $index_tenant_con_id);//租客合同id
            } elseif ($landlord_contract_id) {
            //5 终止业主合同
                $landlord_contract_model = new \Common\Helper\Erp\LandlordContract();
                $landlord_con_info = $landlord_contract_model->getLandlordInfo($landlord_contract_id);
                foreach ($fee_type_list as $fee_type) {
                    if ($fee_type['type_name'] == '押金') {
                        $fee_type['money'] = $landlord_con_info[0]['deposit'];
                        $end_landlord_fee = $fee_type;
                    }
                }
                $this->assign('landlord_contract_id', $landlord_contract_id);//id
                $this->assign('landlord_con_info', $landlord_con_info[0]);//终止业主合同房源名称/房源类型
                $this->assign('end_landlord_fee', $end_landlord_fee);     //终止业主合同费用
            }
            $this->assign('pay_type', $pay_type);    //支付类型
            $this->assign('fee_type_list', $fee_type_list);    //费用类型
            $data = $this->fetch("add_income");
            return $this->returnAjax(array("status"=>1,"tag_name"=>"新增收入流水","model_name"=>"list_finance_cost","model_js"=>"finance_add_incomeJs","model_href"=>Url::parse("Finance-Serial/addIncome"),"data"=>$data));
        } else {
            $room_fee_type_info = $_POST; //表单提交信息
            $serial_number = uniqid(date('Ym')); //流水号
            $source = array();
            $tenant_model = new \Common\Model\Erp\TenantContract();
            $edit_meter = $_SESSION['edit_meter'];
            //新增收入时,搜索房间时带过来的 house_type
            $house_type = $room_fee_type_info['house_type']; //房源分类(集中/分散)
            //1 添加租客合同
            if (isset($room_fee_type_info['contract_id'])) {
                //租住表model
                $rental_model = new \Common\Model\Erp\Rental();
                //查询合同对应房间 的条件
                $where = array('contract_id' => $room_fee_type_info['contract_id'], 'house_type' => $room_fee_type_info['con_house_type'], 'is_delete' => 0);
                //根据合同id 查询的房间id
                $contract_info = $rental_model->getOne($where);
                //租客合同 house_type
                $house_type = $contract_info['house_type'];
                //租客合同source
                $source = array('source' => 'tenant_contract', 'source_id'=> $room_fee_type_info['contract_id']);
                //更新待办事项
                $serial_helper = new \Common\Helper\Erp\SerialNumber();
                $contract_data = $serial_helper->getContractInfoById($room_fee_type_info['contract_id']);
                $res = $serial_helper->updateContractPayTime($room_fee_type_info['contract_id'], $contract_data[0], $flag = false);
                if (!$res) {
                    return $this->returnAjax(array("status"=>0, "data"=>'更新合同下次支付时间失败!'));
                }
            }
            //2 预定房间的 house_type
            if (isset($room_fee_type_info['reserve_id'])) {
                $house_type = $room_fee_type_info['house_type'];
                //预定房间的source
                $source = array('source' => 'reserve', 'source_id' => $room_fee_type_info['reserve_id']);
            }
            //3 抄表 的house_type
            if ($room_fee_type_info['sub_type'] == "meter") {
                $house_type = $room_fee_type_info['me_house_type'];
                //房间抄表source
                $source = array('source' => 'meter', 'source_id' => $room_fee_type_info['room_id']);
            }
            //4 首页待办事项收租
            if ($room_fee_type_info['sub_type'] == 'collect_rents') {
                $house_type = $room_fee_type_info['house_type'];
                //首页待办事项收租 source
                $source = array('source' => 'tenant_contract', 'source_id' => $room_fee_type_info['index_tenant_con_id']);
                //更新待办事项
                $serial_helper = new \Common\Helper\Erp\SerialNumber();
                $res = $serial_helper->validateUserFirstWhetherCharge($source);
                $contract_data = $serial_helper->getContractInfoById($room_fee_type_info['index_tenant_con_id']);
                $res = $serial_helper->updateContractPayTime($room_fee_type_info['index_tenant_con_id'], $contract_data[0], $res);
                if (!$res) {
                   return $this->returnAjax(array("status"=>0, "data"=>'更新合同下次支付时间失败!'));
                }
            }
            //5 终止业主合同
            if (isset($room_fee_type_info['lc_contract_id'])) {
                $house_type = $room_fee_type_info['house_type'];
                $source = array('source' => 'landlord_contract', 'source_id' => $room_fee_type_info['lc_contract_id']);
            }
            // 状态判断, 是否是欠费清单
            $status = $room_fee_type_info['dispose_ways'] ? : 0;
            $arrear_date = ($status == 2) ? strtotime($room_fee_type_info['destine']) : '';
            //费用类型列表
            $fee_type_list = $room_fee_type_info['cost'];
            //循环组装费用类型列表
            foreach ($fee_type_list as $fee_type) {
                if ($fee_type['type_name'] == '租金') {
                    $rent_money = $fee_type['cost_num'];
                }
                $fee_arr[] = array('type_name' => $fee_type['type_name'], 'fee_type_id' => $fee_type['cost_type'], 'money' => $fee_type['cost_num']);
            }
            
            //新增收入时房间有租金,则更新合同
            if (isset($rent_money) && !empty($rent_money)) {
                if ($room_fee_type_info['sub_type'] == 'add') {
                    $tenant_model = new \Common\Model\Erp\TenantContract();
                    $serial_helper = new \Common\Helper\Erp\SerialNumber();
                    $contract_info = $tenant_model->getRoomConInfo($room_fee_type_info['house_id'], $room_fee_type_info['record_id'], $room_fee_type_info['house_type']);
                    if ($contract_info) {
                        $res = $serial_helper->updateContractPayTime($contract_info[0]['contract_id'], $contract_info[0], $res = TRUE);
                    }
                }
            }
            $insert_data = array(
                    'not_room_serial' => $room_fee_type_info['not_room_serial'],
                    'house_id' => $room_fee_type_info['house_id'],    //房源id
                    'room_id' => $room_fee_type_info['record_id'],   //房间id
                    'house_type' => ($room_fee_type_info['not_room_serial'] == 1) ? $house_type = 0 : (($house_type == 1) ? 'room' : (($house_type == 2) ? 'house' : '')), //房源类型(1/分散, 2/集中)
                    'detail' => $fee_arr,
                    'serial_number' => $serial_number,            //流水号
                    'pay_time' => strtotime($room_fee_type_info['time']),    //支付时间
                    'subscribe_pay_time' => $room_fee_type_info['destine'] ? strtotime($room_fee_type_info['destine']) : 0,//预约缴费时间
                    'type' => 1,                                       //流水/支出  1收入; 2支出
                    'receivable' => $room_fee_type_info['receivable'], //流水金额     所有的租金,电费等 总额
                    'money' => (double)trim($room_fee_type_info['collect']),         //实际应该的金额    money 和  final_money 一开始一样
                    'final_money' => (double)trim($room_fee_type_info['collect']),   //实际已发生的金额   冲账后要减
                    'father_id' => 0,                                  //有欠费清单时, father_id 为上一条记录的id
                    'user_id' => $user_id,
                    'city_id' => $city_id,
                    'company_id' => $company_id,
                    'payment_mode' => $room_fee_type_info['pay_ways'], //支付类型  现金 支付宝等
                    'remark' => $room_fee_type_info['mark'],         //备注
                    'status' => $status,           //流水收入状态  0，普通，1，差额减免，2欠费，3待处理
                    'source' => $source,
            );

            //1 房间预订
            if (isset($room_fee_type_info['reserve_id'])) {
                $insert_data['house_id'] = $room_fee_type_info['house_id'];
                $insert_data['room_id'] = $room_fee_type_info['room_id'];
            }
            //2 租客合同
            if (isset($room_fee_type_info['contract_id'])) {
                $insert_data['house_id'] = $contract_info['house_id'];
                $insert_data['room_id'] = $contract_info['room_id'];
            }
            //3 房间抄表
            if ($room_fee_type_info['sub_type'] == 'meter') {
                $insert_data['house_id'] = $room_fee_type_info['house_id'];
                $insert_data['room_id'] = $room_fee_type_info['room_id'];
            }
            //4 首页待办提醒收租
            if ($room_fee_type_info['sub_type'] == 'collect_rents') {
                $insert_data['house_id'] = $room_fee_type_info['house_id'];
                $insert_data['room_id'] = $room_fee_type_info['room_id'];
            }
            //5 终止业主合同
            if (isset($room_fee_type_info['lc_contract_id'])) {
                $insert_data['house_id'] = 0;
                $insert_data['room_id'] = 0;
            }
            //非房间流水
            if ($room_fee_type_info['not_room_serial'] == 1) {
                $insert_data['house_id'] = 0;
                $insert_data['room_id'] = 0;
            }
            if(!isset($insert_data['house_id']) || !isset($insert_data['room_id']) || !isset($insert_data['house_type']))
            {
                return $this->returnAjax(array("status"=>0, "data"=>'房源不存在，请先添加！'));
            }
            //第二次更新合同的下次付款金额
            $extract_money = $_COOKIE['extract_money'];
            $contract_model = new \Common\Model\Erp\TenantContract();
            if ($extract_money) {
                $money = $insert_data['receivable'] - $extract_money['money'];
                $contract_model->edit(array('contract_id' => $extract_money['contract_id']), array('next_pay_money' => $money));
                setcookie('extract_money[money]', '', time() - 3600);
                setcookie('extract_money[contract_id]', '', time() - 3600);
            } else {
                $contract_model->edit(array('contract_id' => $extract_money['contract_id']), array('next_pay_money' => $insert_data['receivable']));
            }
            
            $serialNumberModel = new  \Common\Model\Erp\SerialNumber();
            $result = $serialNumberModel->addSeriaNumber($insert_data, $arrear_date);
            \Common\Model\Erp\MeterReading::sfMoney($edit_meter);
            $contract_id = $room_fee_type_info['sub_type'] == 'collect_rents' ? $room_fee_type_info['index_tenant_con_id'] : $room_fee_type_info['contract_id'];
            $extract_money = $_SESSION['extract_money'];
            if ($extract_money) {
                $money = $insert_data['receivable'] - $extract_money;
                $tenant_model->edit(array('contract_id' => $contract_id), array('next_pay_money' => $money));
                unset($_SESSION['extract_money']);
            } else {
                $tenant_model->edit(array('contract_id' => $contract_id), array('next_pay_money' => $insert_data['receivable']));
            }
            
            //干掉COOKIE;
            //             foreach ($meter_id as $key => $val) {
            //                 setcookie('meter[$key]', '', time() - 3600);
            //             }
            unset($_SESSION['edit_meter']);
            //session_destroy();
            if ($result) {
                return $this->returnAjax(array("status"=>1, "tag"=>Url::parse("finance-index/index")));
            }
                return $this->returnAjax(array("status"=>0, "data"=>'新增收入失败!'));
        }
    }

    /**
     * 新的跳流水页面
     * 修改时间 2015年6月9日17:09:51
     *
     * @author ft
     */
    public function newaddincomeAction() {
        if(!$this->verifyModulePermissions(\Common\Helper\Permissions::INSERT_AUTH_ACTION)){
            return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("finance-serial/newaddincome")));
        }

        $city_id = $this->user['city_id'];
        $user_id = $this->user['user_id'];
        $company_id = $this->user['company_id'];
        if (Request::isGet()) {
            session_start();
            //6 分散式房源/集中式房源收费
            $charge_source = I('get.charge_source','room_charge');
            $fee_type_model = new \Common\Model\Erp\FeeType();      //费用类型
            $config_model = new \Common\Model\Erp\SystemConfig();   //数据库配置model
            $pay_type = $config_model->getFind('Serial', 'payType');//支付方式
            //该公司下所有的费用类型下拉
            $fee_type_list = $fee_type_model->getData(array('company_id' => $company_id, 'is_delete' => 0));
            if ($charge_source == 'room_charge') {
                //6 分散式/集中式收费
                $house_id = I('get.house_id', 0);
                $room_id = I('get.room_id', 0);
                $house_type = I('get.house_type', 0);
                $contract_id = I('get.charge_contract_id', 0);
                $con_detail = I('get.con_detail', 0);//用于区分是合同详情过来的
                $fee_model = new \Common\Model\Erp\Fee();
                $room_helper = new \Common\Helper\Erp\Room();
                $fee_type_model = new \Common\Model\Erp\FeeType();
                $serial_helper = new \Common\Helper\Erp\SerialNumber();
                $tc_contract_model = new \Common\Model\Erp\TenantContract();

                //查询房间的名称
                $room_name = $room_helper->getRoomNameById($house_id, $room_id, $house_type);
                //获取合同总的租金
                $where = array('contract_id' => $contract_id);
                $columns = array('contract_id' => 'contract_id','pay' => 'pay','rent' => 'rent', 'deposit' => 'deposit', 'next_pay_money' => 'next_pay_money');
                $rent_sum = $tc_contract_model->getOne($where, $columns);//租金/押付方式
                //检查合同第一次是否收费(房间也一样)
                $source = array('source' => 'tenant_contract', 'source_id' => $contract_id);
                $res = $serial_helper->validateUserFirstWhetherCharge($source);
                //返回房间租金, 押金等
                $charge_contract_fee = $serial_helper->returnContractFeeType($fee_type_list, $rent_sum, $res);
                
                //获取房间费用配置
                $fee_condition = array('house_type' => $house_type, 'house_id' => $house_id, 'room_id' => $room_id);
                $fee_data = $serial_helper->getRoomFeeConfig($fee_condition);
                //如果这个房间有费用配置
                if ($fee_data) {
                    //取出费用类型id
                    foreach ($fee_data as $fee_info) {
                        $ftype_id[] = $fee_info['fee_type_id'];//该房间的费用类型id
                    }
                    $ftype_where = array('fee_type_id' => $ftype_id, 'company_id' => $company_id);
                    //该房间的费用类型
                    $room_ftype_data = $fee_type_model->getFeeTypeById($ftype_where);
                    //过滤掉一次性收费
                    $fee_id = $serial_helper->checkDisposableFee($contract_id, $fee_data);
                    foreach ($fee_data as $key => $fee_info) {
                        if ($fee_info['payment_mode'] == 1) {
                            //1 按房间一次性收费
                            $fee_data[$key]['money'] = $fee_info['money'];
                        }
                         elseif ($fee_info['payment_mode'] == 2) {
                            //2 按房间每月收费
                            $fee_data[$key]['money'] = $fee_info['money'] * $rent_sum['pay'];
                        }
                         elseif ($fee_info['payment_mode'] == 6) {
                            //6 按人头一次性收费
                            $contract_rental_model = new \Common\Model\Erp\ContractRental();
                            $cr_where = array('contract_id' => $contract_id, 'is_delete' => 0);
                            $cr_columns = array('total_pople' => new Expression('count(contract_rental_id)'));
                            $total_pople = $contract_rental_model->getOne($cr_where, $cr_columns);
                            $fee_data[$key]['money'] = $fee_info['money'] * $total_pople['total_pople'];
                        }
                         elseif ($fee_info['payment_mode'] == 7) {
                            //7 按人头每月收费
                            $contract_rental_model = new \Common\Model\Erp\ContractRental();
                            $cr_where = array('contract_id' => $contract_id, 'is_delete' => 0);
                            $cr_columns = array('total_pople' => new Expression('count(contract_rental_id)'));
                            $total_pople = $contract_rental_model->getOne($cr_where, $cr_columns);
                            $fee_data[$key]['money'] = $fee_info['money'] * $rent_sum['pay'] * $total_pople['total_pople'];
                        }
                    }
                    //让fee表查出的费用类型, 与fee_type表里的费用类型顺序相对
                    foreach ($fee_data as $key => $fee) {
                        foreach ($room_ftype_data as $ftype) {
                            if ($fee['fee_type_id'] == $ftype['fee_type_id']) {
                                $fee_data[$key]['type_name'] = $ftype['type_name'];
                            }
                        }
                        if (in_array($fee['fee_type_id'], $fee_id)) {
                            unset($fee_data[$key]);
                        }
                    }
                }
                unset($room_ftype_data);
                //获取抄表的费用类型金额
                $meter_reading_helper = new \Common\Helper\Erp\MeterReading();
                $meter_money_data = $meter_reading_helper->getMeterMoneyByRoomId($house_id, $house_type, $room_id);
                
                if ($meter_money_data) {
                    $meter_arr = array();
                    $extract_money = 0;
                    foreach ($meter_money_data as $key => $val) {
                        if ($val['money'] != 0) {
                               if ($house_type == 1) {//分散
                                   if ($house_id > 0 && $room_id < 0) {//整
                                    $edit_meter[] = array('meter_id' => $val['meter_id'], 'house_id' => $house_id, 'house_type' => $house_type);
                                   } else {//合
                                    $edit_meter[] = array('meter_id' => $val['meter_id'], 'room_id' => $room_id, 'house_type' => $house_type);
                                   }
                            } else {//集中
                                $edit_meter[] = array('meter_id' => $val['meter_id'], 'room_id' => $room_id, 'house_type' => $house_type);
                            }
                            $meter_arr[$val['fee_type_id']]['money'] += $val['money'];
                            $meter_arr[$val['fee_type_id']]['fee_type_id'] = $val['fee_type_id'];
                            $meter_arr[$val['fee_type_id']]['type_name'] = $val['type_name'];
                            $extract_money += $val['money'];
                        } else {
                            unset($meter_money_data[$key]);
                        }
                    }
                    if ($meter_arr) {
                        $_SESSION['edit_meter'] = $edit_meter;
                        foreach ($fee_data as $key => $fee) {
                            foreach ($meter_arr as $meter_val) {
                                if (!in_array($fee['fee_type_id'], $meter_val) && ($fee['payment_mode'] == 3 || $fee['payment_mode'] == 4 || $fee['payment_mode'] == 5)) {
                                    unset($fee_data[$key]);
                                }
                            }
                        }
                    } else {
                        foreach ($fee_data as $key => $v) {
                            if ($v['payment_mode'] == 3 || $v['payment_mode'] == 4 || $v['payment_mode'] == 5) unset($fee_data[$key]);
                        }
                    }
                    
                    foreach ($fee_data as $key => $val) {
                        $fee_arr[$val['fee_type_id']]['money'] = $val['money'];
                        $fee_arr[$val['fee_type_id']]['fee_type_id'] = $val['fee_type_id'];
                        $fee_arr[$val['fee_type_id']]['type_name'] = $val['type_name'];
                    }
                    if ($meter_arr && $fee_arr) {//有抄表,也有房间配置
                        foreach ($fee_arr as $key => $fee) {
                            if (!in_array($fee['fee_type_id'], $meter_arr[$key])) {
                                $meter_arr[] = $fee;
                            }
                        }
                        $fee_type_arr = array_merge($charge_contract_fee, $meter_arr);
                    } else {
                        $fee_type_arr = array_merge($charge_contract_fee, $fee_data);
                    }
                } else {
                    foreach ($fee_data as $key => $v) {
                        if ($v['payment_mode'] == 3 || $v['payment_mode'] == 4 || $v['payment_mode'] == 5) unset($fee_data[$key]);
                    }
                    $fee_type_arr = array_merge($charge_contract_fee, $fee_data);
                }
                foreach ($fee_type_arr as $fee) {
                    $contract_money += $fee['money'];
                }
                if ($extract_money) {
                    $contract_money = $contract_money - $extract_money;
                    $_SESSION['extract_money'] = $extract_money;
                }
                $res = $tc_contract_model->edit(array('contract_id' => $contract_id), array('next_pay_money' => $contract_money));
                $this->assign('con_detail', $con_detail);
                $this->assign('contract_id', $contract_id);
                $this->assign('house_type', $house_type);
                $this->assign('charge_source', $charge_source);//由于页面区分是房间收费
                $this->assign('room_name', $room_name[0]);     //房间名称
                $this->assign('fee_type_arr', $fee_type_arr);  //房间费用类型
            }
            $this->assign('fee_type_list', $fee_type_list);  //该公司下所有的费用类型下拉
            $this->assign('pay_type', $pay_type);            //该公司下所有的支付方式
            $data = $this->fetch("add_income");
            return $this->returnAjax(array("status"=>1,"tag_name"=>"收入流水","model_name"=>"list_finance_cost","model_js"=>"finance_add_incomeJs","model_href"=>Url::parse("Finance-Serial/newaddincome"),"data"=>$data));
        } else {
            //提交
            $room_fee_type_info = $_POST;
            $serial_number = uniqid(date('Ym')); //流水号
            $source = array();
            $tenant_model = new \Common\Model\Erp\TenantContract();
            $edit_meter = $_SESSION['edit_meter'];
            //6 房间收费
            if (isset($room_fee_type_info['sub_type']) == 'room_charge') {
                $house_type = $room_fee_type_info['house_type'];
                //房间收费和合同收费source相同
                $source = array('source' => 'tenant_contract', 'source_id' => $room_fee_type_info['tc_contract_id']);
                $serial_helper = new \Common\Helper\Erp\SerialNumber();
                //第一次收费,下次付款时间,要加上提前付款天数
                $valid_res = $serial_helper->validateUserFirstWhetherCharge($source);
                $contract_info = $serial_helper->getContractInfoById($room_fee_type_info['tc_contract_id']);
                $res = $serial_helper->updateContractPayTime($room_fee_type_info['tc_contract_id'], $contract_info[0], $valid_res);
                if (!$res) {
                    return $this->returnAjax(array("status"=>0, "data"=>'更新合同下次支付时间失败!'));
                }
            }
            // 状态判断, 是否是欠费清单
            $status = $room_fee_type_info['dispose_ways'] ? $room_fee_type_info['dispose_ways'] : 0;
            $arrear_date = ($status == 2) ? strtotime($room_fee_type_info['destine']) : 0;
            //费用类型列表
            $fee_type_list = $room_fee_type_info['cost'];
            //循环组装费用类型列表
            foreach ($fee_type_list as $fee_type) {
                $fee_arr[] = array('type_name' => $fee_type['type_name'], 'fee_type_id' => $fee_type['cost_type'], 'money' => $fee_type['cost_num']);
            }
            $insert_data = array(
                    'not_room_serial' => $room_fee_type_info['not_room_serial'],
                    'house_id' => $room_fee_type_info['house_id'] ? $room_fee_type_info['house_id'] : 0,    //房源id
                    'room_id' => $room_fee_type_info['record_id'] ? $room_fee_type_info['record_id'] : $room_fee_type_info['room_id'],   //房间id
                    'house_type' => ($room_fee_type_info['not_room_serial'] == 1) ? $house_type = 0 : (($house_type == 1) ? 'room' : (($house_type == 2) ? 'house' : '')), //房源类型(1/分散, 2/集中)
                    'detail' => $fee_arr,
                    'serial_number' => $serial_number,            //流水号
                    'pay_time' => strtotime($room_fee_type_info['time']),    //支付时间
                    'subscribe_pay_time' => $room_fee_type_info['destine'] ? strtotime($room_fee_type_info['destine']) : 0,//预约缴费时间
                    'type' => 1,                                       //流水/支出  1收入; 2支出
                    'receivable' => $room_fee_type_info['receivable'], //流水金额     所有的租金,电费等 总额
                    'money' => (double)trim($room_fee_type_info['collect']),         //实际应该的金额    money 和  final_money 一开始一样
                    'final_money' => (double)trim($room_fee_type_info['collect']),   //实际已发生的金额   冲账后要减
                    'father_id' => 0,                                  //有欠费清单时, father_id 为上一条记录的id
                    'user_id' => $user_id,
                    'city_id' => $city_id,
                    'company_id' => $company_id,
                    'payment_mode' => $room_fee_type_info['pay_ways'], //支付类型  现金 支付宝等
                    'remark' => $room_fee_type_info['mark'],         //备注
                    'status' => $status,           //流水收入状态  0，普通，1，差额减免，2欠费，3待处理
                    'source' => $source,
            );
            //租客合同收费
            if ($room_fee_type_info['sub_type'] == 'room_charge') {
                $insert_data['house_id'] = $room_fee_type_info['house_id'] ? $room_fee_type_info['house_id'] : 0;
                $insert_data['room_id'] = $room_fee_type_info['room_id'] ? $room_fee_type_info['room_id'] : 0;
            }
            $serialNumberModel = new  \Common\Model\Erp\SerialNumber();
            $result = $serialNumberModel->addSeriaNumber($insert_data, $arrear_date);
            \Common\Model\Erp\MeterReading::sfMoney($edit_meter);
            $extract_money = $_SESSION['extract_money'];
            if ($extract_money) {
                $money = $insert_data['receivable'] - $extract_money;
                $tenant_model->edit(array('contract_id' => $room_fee_type_info['tc_contract_id']), array('next_pay_money' => $money));
                unset($_SESSION['extract_money']);
            } else {
                $tenant_model->edit(array('contract_id' => $room_fee_type_info['tc_contract_id']), array('next_pay_money' => $insert_data['receivable']));
            }
            //干掉COOKIE;
            //foreach ($meter_id as $key => $val) {
            //    setcookie('meter[$key]', '', time() - 3600);
            //}
            unset($_SESSION['edit_meter']);
            //session_destroy();
            if ($result) {
                return $this->returnAjax(array("status"=>1, "tag"=>Url::parse("finance-index/index")));
            }
            return $this->returnAjax(array("status"=>0, "data"=>'新增收入失败!'));
        }
    }
    /**
     * 财务新增 房间搜索
     * 修改时间 2015年5月7日19:51:08
     *
     * @author  ft
     */
        protected function wordsearchroomAction() {
            $search_word = Request::queryString('get.search', '', 'string');
            $room = new Room();
            $search_info = $room->getRoomData($this->user,$search_word);
            foreach ($search_info as $key => $info) {
                if ($info['house_id'] > 0) {
                    $search_info[$key]['house_type'] = 1;
                    $search_info[$key]['sub_type'] = 'add';
                } else {
                    $search_info[$key]['house_type'] = 2;
                    $search_info[$key]['sub_type'] = 'add';
                }
            }
            if ($search_info) {
                return $this->returnAjax(array('status' => 1, 'data' => $search_info));
            }
            return $this->returnAjax(array('status' => 0, 'data' => '未搜索到房间!'));
        }
    /**
     * 获取编辑时所需的数据
     * 修改时间2015年3月19日 16:58:44
     *
     * @author ft
     */
    protected function editincomeAction() {
        if (!$this->verifyModulePermissions(Permissions::UPDATE_AUTH_ACTION,'sys_water_management')){
            return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("finance-serial/editincome")));
        }
        $city_id = $this->user['city_id'];
        $user_id = $this->user['user_id'];
        $company_id = $this->user['company_id'];
        if (Request::isGet()) {
            $serial_id = I("get.serial_id", 0, "int");//流水id
            $room_id = I("get.room_id", 0, "int");    //房间id
            $house_type = I('get.house_type', 0);
            $house_id = I('get.house_id', 0);
            $flat_id = I('get.flat_id', 0);
            $source = I('get.source', '');
            $source_id = I('get.source_id', 0);
            //接收欠费清单过来的数据
            $debts_id = I('get.debts_id');    //欠费清单流水id
            if ($debts_id) {
                $room_focus_id = I('get.room_focus_id');//集中式房间id
                $house_type = I('get.house_type', 0);
            }

            $serial_number_model = new  \Common\Model\Erp\SerialNumber();     //流水model
            $serial_number_helper = new  \Common\Helper\Erp\SerialNumber();    //流水helper
            $serial_detail_model = new \Common\Model\Erp\SerialDetail();       //流水详细model
            $serial_strike_model = new \Common\Model\Erp\SerialStrikeBalance();//冲账model
            $fee_type_model = new \Common\Model\Erp\FeeType();      //费用类型
            $config_model = new \Common\Model\Erp\SystemConfig();   //数据库配置model
            $pay_type = $config_model->getFind('Serial', 'payType');//支付方式
            $serial_data = $serial_number_model->getOne(array('serial_id'=>$serial_id));
            if ($serial_id<=0){
            	$serial_data = $serial_number_model->getOne(array('serial_id'=>$debts_id));
            }
            //判断用户是否对当前对象有权限操作START
            if ($serial_data['house_id']>0 || $serial_data['room_id']>0){
                    if ($serial_number_model::TYPE_INCOME == $serial_data['house_type']){
                        if(!$this->verifyDataLinePermissions(Permissions::SELECT_AUTH_ACTION, $serial_data['house_id'], SysHousingManagement::DECENTRALIZED_HOUSE)){
                        	return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("finance-serial/editincome")));
                        }
                    if ($serial_number_model::TYPE_PAY == $serial_data['house_type']){
                        if(!$this->verifyDataLinePermissions(Permissions::SELECT_AUTH_ACTION, $serial_data['room_id'], SysHousingManagement::CENTRALIZED)){
                        	return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("finance-serial/editincome")));
                        }
                    }
                }
            }else if($this->user['is_manager']==0){
                if (($serial_data['house_id']<=0 && $serial_data['room_id']<=0)){
                	if ($serial_data['user_id']!=$user_id){
                		return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("finance-serial/editincome")));
                	}
                }
            }
            
            //该公司下所有的费用类型下拉
            $fee_type_list = $fee_type_model->getData(array('company_id' => $company_id, 'is_delete' => 0));
            if (!$debts_id) {
                //获取当前流水的详细id
                $all_detail_id = $serial_detail_model->getAllDetailIdBySerialId($serial_id);
                foreach ($all_detail_id as $detail) {
                    $detail_id_arr[] = $detail['serial_detail_id'];
                }
                if ($source && $source_id && $house_type != 0) {
                    $source_arr['source'] = $source;
                    $source_arr['source_id'] = $source_id;
                }
                if ($house_type == 1) {
                    //获取房间费用类型信息,房间名字,以及流水总金额,支付时间/方式,备注等
                    $room_fee_info = $serial_number_helper->editRoomFeeType($company_id, $serial_id, $room_id, $house_type, $house_id, $source_arr);
                } elseif($house_type == 2) {
                    //获取房间费用类型信息,房间名字,以及流水总金额,支付时间/方式,备注等
                    $room_fee_info = $serial_number_helper->editRoomFeeType($company_id, $serial_id, $room_id, $house_type, $flat_id, $source_arr);
                } else {
                    //获取非房间费用类型
                    $not_room_data = $serial_number_helper->getNotRoomFeeType($serial_id);
                }
                //弹出房间名
                $room_name = array_shift($room_fee_info);
                //弹出该房间的,流水总金额,支付时间/方式,备注等
                $room_serial_info = array_pop($room_fee_info);
                //获取冲账表所有数据
                $strike_data = $serial_strike_model->getAllStrikeData($detail_id_arr);
                //循环比对详细费用是否冲过帐
                foreach ($room_fee_info as $key => $fee) {
                    foreach ($strike_data as $strike) {
                        if ($fee['sd_id'] == $strike['serial_detail_id']) {
                            $room_fee_info[$key]['strike_id'] = $strike['serial_detail_id'];
                            $room_fee_info[$key]['money'] += $strike['money'];
                        }
                    }
                }
                //非房间流水循环比对详细费用是否冲过帐
                if ($not_room_data) {
                    foreach ($not_room_data as $key => $fee) {
                        foreach ($strike_data as $strike) {
                            if ($fee['sd_id'] == $strike['serial_detail_id']) {
                                $not_room_data[$key]['strike_id'] = $strike['serial_detail_id'];
                                $not_room_data[$key]['money'] = $strike['money'];
                            }
                        }
                    }
                }
                if ($room_serial_info['status'] == 2) { //等于二,证明有欠费清单
                    $subscribe_pay_time = $serial_number_model->getRoomArrears($serial_id);
                    $this->assign('subscribe_pay_time', $subscribe_pay_time[0]);
                }
                if (isset($not_room_data)) {//非房间流水不传id到视图
                    $not_room_serial = 'not_room';//非房间流水标识
                    $this->assign('serial_id', $serial_id); //流水id
                    $this->assign('not_room_data', $not_room_data);
                    $this->assign('not_room', $not_room_serial);
                    $this->assign('room_fee_info', $room_fee_info);  //该房间的费用类型信息
                } else {
                    $this->assign('serial_id', $serial_id);          //流水id
                    $this->assign('room_id', $room_id);              //房间id
                    $this->assign('house_type', $house_type);        //房源类型(分散/集中)
                    $this->assign('house_id', $house_id);            //房源id
                    $this->assign('room_name', $room_name);          //房间名字
                    $this->assign('room_fee_info', $room_fee_info);  //该房间的费用类型信息
                    $this->assign('room_serial_info', $room_serial_info);
                }
            } else {    //收取欠费清单金额
                //欠费清单数据查询
                $debts_info = $serial_number_helper->getDebtsRoomSerial($debts_id, $room_id, $room_focus_id, $house_id);
                foreach ($fee_type_list as $fee_type) {
                    if ($fee_type['type_name'] == '欠费') {
                        $fee_type['money'] = $debts_info[0]['receivable'];
                        $debts_fee_type = $fee_type;
                    }
                }
                $not_room = 'not_room';
                $this->assign('not_room', $not_room);
                $this->assign('debts_fee_type', $debts_fee_type);
                $this->assign('debts_info', $debts_info[0]);
            }
            $this->assign('fee_type_list', $fee_type_list);  //该公司下所有的费用类型下拉
            $this->assign('pay_type', $pay_type);            //该公司下所有的支付方式
            $data = $this->fetch("add_income");
            return $this->returnAjax(array("status"=>1,"tag_name"=>"编辑收入流水","model_name"=>"list_finance_cost","model_js"=>"finance_add_incomeJs","model_href"=>Url::parse("Finance-Serial/editIncome"),"data"=>$data));
        } else {
            if(!$this->verifyModulePermissions(\Common\Helper\Permissions::UPDATE_AUTH_ACTION)){
                return $this->returnAjax(array('__status__'=>403));
            }

            $serial_num_helper = new \Common\Helper\Erp\SerialNumber();
            $room_fee_type_info = $_POST; //表单提交信息
            $debts_id = $room_fee_type_info['debts_id'];
            if ($debts_id) {  //保存收取的欠费清单
                $serial_number_model = new  \Common\Model\Erp\SerialNumber();     //流水model
                $debts_data = $serial_number_model->getOne(array('serial_id' => $debts_id));
                $father_id = $debts_data['father_id'];
                $edit_data = array(
                        'pay_time' => strtotime($room_fee_type_info['time']),
                        'payment_mode' => $room_fee_type_info['pay_ways'],
                        'money' => $room_fee_type_info['collect'],
                        'receivable' => $room_fee_type_info['receivable'],
                        'final_money' => $room_fee_type_info['receivable'],
                        'remark' => $room_fee_type_info['mark'],
                        'status' => 0,
                        'cost' => $room_fee_type_info['cost']
                );
                $aa = $serial_num_helper->saveDebtsCharge($edit_data, $debts_id, $father_id);
                $source = \Common\Helper\DataSnapshot::$SNAPSHOT_SERIAL_DEBTS_CHARGE;
                $source_id = $debts_data['serial_id'];
                $foreign = 'father_id';
                $foreign_id = $father_id;
                $snap_res = \Common\Helper\DataSnapshot::save($source, $source_id, $debts_data, $foreign, $foreign_id);
                if (!$snap_res) {
                    return $this->returnAjax(array('status' => 0, 'message' => '数据快照保存失败!'));
                }
                if ($aa) {
                    return $this->returnAjax(array('status' => 1, 'message' => '收费成功!', 'tag' => Url::parse("finance-serial/listdebts")));
                }
                    return $this->returnAjax(array('status' => 0, 'message' => '收费失败!'));

            } else {
                $serialNumberModel = new \Common\Model\Erp\SerialNumber();
                $house_view_helper = new \Common\Helper\Erp\HouseView();
                $room_name = $room_fee_type_info['room'];    //房间名
                $fee_type_list = $room_fee_type_info['cost']; //费用类型列表
                $serial_id = $room_fee_type_info['sn_id'];//流水id
                //获取房源类型,及房源id
                $serial_where = array('serial_id' => $serial_id);
                $query_house_type = $serialNumberModel->getOne($serial_where);
                // 状态判断, 是否是欠费清单
                $status = $room_fee_type_info['dispose_ways'] ? $room_fee_type_info['dispose_ways'] : 0;
                $arrear_date = ($status == 2) ? strtotime($room_fee_type_info['destine']) : 0;
                //循环组装费用类型列表
                foreach ($fee_type_list as $fee_type) {
                    $fee_arr[] = array(
                            'type_name' => $fee_type['type_name'],
                            'fee_type_id' => $fee_type['cost_type'],
                            'money' => $fee_type['cost_num'],
                            'serial_id' => $fee_type['sn_id'],
                            'detail_id' => $fee_type['detail_id'],
                            'new_record' => $fee_type['new_record'],
                    );
                }
                $insert_data = array(
                        'serial_id' => $serial_id,
                        'house_type' => ($query_house_type['house_type'] == 1) ? 'room' : (($query_house_type['house_type'] == 2) ? 'house' : ''), //房源类型(1/分散, 2/集中)
                        'source' => $query_house_type['source'],
                        'source_id' => $query_house_type['source_id'],
                        'serial_number' => $query_house_type['serial_number'],
                        'house_id' => $query_house_type['house_id'],
                        'room_id' => $query_house_type['room_id'],
                        'detail' => $fee_arr,
                        'pay_time' => strtotime($room_fee_type_info['time']),    //支付时间
                        'subscribe_pay_time' => $arrear_date,    //支付时间
                        'type' => 1,                                  //流水/支出  1收入; 2支出
                        'receivable' => $room_fee_type_info['receivable'],//流水金额     所有的租金,电费等 总额
                        'money' => $room_fee_type_info['collect'],         //实际应该的金额    money 和  final_money 一开始一样
                        //'final_money' => $room_fee_type_info['collect'],   //修改流水时,不修改冲账金额
                        'father_id' => 0,                        //有欠费清单时, father_id 为上一条记录的id
                        'user_id' => $user_id,
                        'city_id' => $city_id,
                        'company_id' => $company_id,
                        'payment_mode' => $room_fee_type_info['pay_ways'], //支付类型  现金 支付宝等
                        'remark' => $room_fee_type_info['mark'],         //备注
                        'status' => $status,           //流水收入状态  0，普通，1，差额减免，2欠费，3待处理
                );
                $old_serial_data = $serialNumberModel->getOne(array('serial_id' => $serial_id));
                $source = \Common\Helper\DataSnapshot::$SNAPSHOT_SERIAL_EDIT;
                $source_id = $serial_id;
                $snap_res = \Common\Helper\DataSnapshot::save($source, $source_id, $old_serial_data);
                if (!$snap_res) {
                    return $this->returnAjax(array("status"=>0, "data"=>'数据快照保存失败!'));
                }
                $result = $serialNumberModel->addSeriaNumber($insert_data, $arrear_date);
                if ($result) {
                    return $this->returnAjax(array("status"=>1, "tag"=>Url::parse("finance-index/index")));
                }
                    return $this->returnAjax(array("status"=>0, "data"=>'编辑收入失败!'));
            }
        }
    }

    /**
     * 删除房间
     * 修改时间 2015年4月27日14:04:44
     *
     * @author ft
     */
    protected function deleteroomAction() {
        if(!$this->verifyModulePermissions(\Common\Helper\Permissions::DELETE_AUTH_ACTION)){
            return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("finance-serial/deleteroom")));
        }
        $serial_and_room_id = Request::queryString('post.uid', 0, '');//接收流水id
        $serial_model = new \Common\Model\Erp\SerialNumber();
        $serial_detail_model = new \Common\Model\Erp\SerialDetail();
        $serial_data = $serial_model->getOne(array('serial_id'=>$serial_and_room_id['sn_id']));
        //判断用户是否对当前对象有权限操作START
        if ($serial_data['house_id']>0 || $serial_data['room_id']>0){
            if ($serial_model::TYPE_INCOME == $serial_data['house_type']){
                if(!$this->verifyDataLinePermissions(Permissions::DELETE_AUTH_ACTION, $serial_data['house_id'], SysHousingManagement::DECENTRALIZED_HOUSE)){
                    return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("finance-serial/editincome")));
                }
                if ($serial_model::TYPE_PAY == $serial_data['house_type']){
                    if(!$this->verifyDataLinePermissions(Permissions::DELETE_AUTH_ACTION, $serial_data['room_id'], SysHousingManagement::CENTRALIZED)){
                        return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("finance-serial/editincome")));
                    }
                }
            }
        }else if($this->user['is_manager']==0){
            if ($serial_data['house_id']<=0 && $serial_data['room_id']<=0){
            	if ($serial_data['user_id']!=$this->user['user_id']){
            		return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("finance-serial/editincome")));
            	}
            }
        }

        //删除流水条件
        $serial_where = array('serial_id' => $serial_and_room_id['sn_id']);
        $seral_data = array('is_delete' => 1);             //字段1 表示删除

        //删除房间对应的流水
        $serial_model->Transaction();
        $serial_num_res = $serial_model->edit($serial_where, $seral_data);
        if (!$serial_num_res) {
            $serial_model->rollback();
            return $this->returnAjax(array("status" => 0, "message" => "删除流水失败!"));
        }
        //删除该流水的欠费清单
        if ($serial_and_room_id['status'] == 2) {
            $debts_where = array('father_id' => $serial_and_room_id['sn_id']);
            $debts_data = array('is_delete' => 1);
            $debts_res = $serial_model->edit($debts_where, $debts_data);
            if (!$serial_num_res) {
                $serial_model->rollback();
                return $this->returnAjax(array("status" => 0, "message" => "删除欠费失败!"));
            }
        }
        //删除流水详细
        $serial_detail_where = array('serial_id' => $serial_and_room_id['sn_id']);
        $serial_detail_data = array('is_delete' => 1);
        $serial_detail_res = $serial_detail_model->edit($serial_detail_where, $serial_detail_data);
        if (!$serial_detail_res) {
            $serial_model->rollback();
            return $this->returnAjax(array("status" => 0, "message" => "删除流水详细失败!"));
        }
        //如果有欠费清单
        if ($serial_and_room_id['status'] == 2) {
            if ($serial_num_res && $serial_detail_res && $debts_res) {
                $serial_model->commit();
                return $this->returnAjax(array("status" => 1, "message" => "流水删除成功!"));
            }
        } else {
            if ($serial_num_res && $serial_detail_res) {
                $serial_model->commit();
                return $this->returnAjax(array("status" => 1, "message" => "流水删除成功!"));
            }
        }
    }

    /**
     * 添加支出流水
     * 修改时间2015年4月18日 13:46:40
     *
     * @author ft
     * @添加了新字段，需要处理
     */
    protected function addexpenseAction() {
        if(!$this->verifyModulePermissions(\Common\Helper\Permissions::INSERT_AUTH_ACTION)){
            return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("finance-serial/addexpense")));
        }


        $user_id = $this->user['user_id'];
        $city_id = $this->user['city_id'];
        $company_id = $this->user['company_id'];
        $serial_number = uniqid(date('Ym')); //流水号

        if (!Request::isPost()) {
            $fee_type_model = new \Common\Model\Erp\FeeType();    //费用类型 model
            $configModel = new \Common\Model\Erp\SystemConfig();  //数据库配置model
            $room_helper = new \Common\Helper\Erp\Room();
            $pay_type = $configModel->getFind('Serial', 'payType');    //支付类型 支付宝等
            //该公司所有费用类型
            $fee_type_list = $fee_type_model->getData(array('company_id' => $company_id, 'is_delete' => 0));
            //1 业主合同添加成功后,跳到新增支出
            $contract_source = I('get.source', '');
            //2 首页待办事项(二房东交租)业主合同id
            $lc_contract_id = I('get.contract_id', 0);
            //3 房间退租
            $out_tenancy = I('get.source', '');
            //4 业主合同支付
            $lc_contract = I('get.lc_source', '');
            //5 房间退订
            $ns_source = I('get.us_source', '');
            //6 终止租客合同
            $tc_stop_contract = I('get.tc_source', '');

            if ($contract_source == 'owner_contract') {
                $house_type = I('get.source_type', 0);//房源类型 1分散/2集中
                $house_name = I('get.house_name', '');//房源名称
                $total_rent = I('get.payall', '');    //总的租金
                $total_deposit = I('get.detainall', '');//总的押金
                $landlord_contract_id = I('get.source_id', 0);//业主合同id

                //1 业主合同添加完成后,跳到支出页面(这里让页面区分是业主合同, 还是房间退租)
                $this->assign('contract_source', $contract_source);
                foreach ($fee_type_list as $fee_type) {
                    if ($fee_type['type_name'] == '租金') {
                        $fee_type['money'] = $total_rent;
                        $landlord_fee[] = $fee_type;
                    }
                    if ($fee_type['type_name'] == '押金') {
                        $fee_type['money'] = $total_deposit;
                        $landlord_fee[] = $fee_type;
                    }
                }
                $this->assign('contract_id', $landlord_contract_id);
                $this->assign('house_type', $house_type);        //房源类型(集中/分散)
                $this->assign('house_name', $house_name);        //房源信息
                $this->assign('landlord_fee', $landlord_fee);//房源费用类型
            } elseif ($lc_contract_id) {
                //2 首页代办提醒,交租(二房东交租)
                $landlord_helper = new \Common\Helper\Erp\LandlordContract();
                $landlord_con_info = $landlord_helper->getLandlordInfo($lc_contract_id);
                //检查第一次有没有收费
                $serial_helper = new \Common\Helper\Erp\SerialNumber();
                $source = array('source' => 'landlord_contract', 'source_id' => $lc_contract_id);
                //验证业主合同有没有收过费
                $res = $serial_helper->validateUserFirstWhetherCharge($source);
                $landlord_con_fee = $serial_helper->returnContractFeeType($fee_type_list, $landlord_con_info[0], $res);
                
                $this->assign('landlord_contract_id', $lc_contract_id);//业主合同id
                $this->assign('landlord_con_fee', $landlord_con_fee);     //业主合同租金费用类型
                $this->assign('landlord_con_info', $landlord_con_info[0]);   //业主合同信息(房源名称/租金等)
            } elseif ($out_tenancy == 'out_tenancy') {
                //3 房间退租所要接收的信息
                $room_focus_id = I('get.room_focus_id', 0);//集中式房间id
                $house_id = I('get.house_id', 0);    //房源id (为零集中式/大于零分散式)
                $room_id = I('get.room_id', 0);      //房间id (为零整组/ 大于零合租)
                $house_type = I('get.house_type', 0);//房源类型 1分散/2集中
                foreach ($fee_type_list as $fee_type) {
                    if ($fee_type['type_name'] == '押金') {
                        $deposit_fee = $fee_type;
                    }
                }
                if ($house_type == 1) { //分散式 (合租/整租)
                    //获取房源和房间的名称和编号,以及租金
                    $tenancy_room_info = $room_helper->getRoomExpenseFeeInfo($room_id, $house_id, $house_type, $company_id);
                    $this->assign('deposit_fee', $deposit_fee);
                    $this->assign('tenancy_room_info', $tenancy_room_info[0]);
                }
                if ($house_type == 2) {  //集中式
                    //获取房源和房间的名称和编号,以及租金
                    $tenancy_room_info = $room_helper->getRoomExpenseFeeInfo($room_focus_id, $house_id, $house_type, $company_id);
                    $this->assign('deposit_fee', $deposit_fee);
                    $this->assign('tenancy_room_info', $tenancy_room_info[0]);
                }
                $this->assign('out_tenancy', $out_tenancy);//用于在页面区分是否是退租流水
                $this->assign('room_focus_id', $room_focus_id);//集中式房间id
                $this->assign('room_id', $room_id);            //分散式房间id
                $this->assign('house_id', $house_id);          //房源id
                $this->assign('house_type', $house_type);      //房源类型(集中/分散)
            } elseif ($lc_contract == 'lc_pay') {
                //4 业主合同支付
                $house_name = I('get.house_name', '');
                $rent = I('get.payall', 0);
                $contract_id = I('get.cid', 0);
                $house_type = I('get.htype', 0);
                //检查第一次有没有收费
                $serial_helper = new \Common\Helper\Erp\SerialNumber();
                $source = array('source' => 'landlord_contract', 'source_id' => $contract_id);
                //验证业主合同有没有收过费
                $res = $serial_helper->validateUserFirstWhetherCharge($source);
                $lc_con_model = new \Common\Model\Erp\LandlordContract();
                $contract_info = $lc_con_model->getOne(array('contract_id' => $contract_id, 'is_delete' => 0), array('deposit' => 'deposit' ,'next_pay_money' => 'next_pay_money', 'pay' => 'pay', 'rent' => 'rent'));
                $lc_contract_fee = $serial_helper->returnContractFeeType($fee_type_list, $contract_info, $res);
                
                $this->assign('lc_contract', $lc_contract);
                $this->assign('house_name', $house_name);
                $this->assign('rent', $rent);
                $this->assign('contract_id', $contract_id);
                $this->assign('house_type', $house_type);
                $this->assign('lc_contract_fee', $lc_contract_fee);
            } elseif ($ns_source == 'unsubscribe') {
                //5 房间退订
                $reserve_id = I('get.reserve_id', 0);
                $reserve_model = new \Common\Model\Erp\Reserve();
                $room_helper = new \Common\Helper\Erp\Room();
                if (strpos($reserve_id, ',') !== false) {
                    $reserve_id_arr = explode(',', $reserve_id);
                    //$_SESSION['reserve_id'] = $reserve_id_arr;
                    $re_where = new \Zend\Db\Sql\Where();
                    $re_where->in('reserve_id', $reserve_id_arr);
                    $columns = array(
                            'reserve_id' => 'reserve_id',
                            'house_id' => 'house_id',
                            'room_id' => 'room_id',
                            'house_type' => 'house_type',
                            'money' => new Expression('sum(money)'),
                    );
                    $reserve_room_info = $reserve_model->getOne($re_where, $columns);
                } else {
                    //$_SESSION['reserve_id'] = $reserve_id;
                    $where = array('reserve_id' => $reserve_id);
                    $columns = array(
                            'reserve_id' => 'reserve_id',
                            'house_id' => 'house_id',
                            'room_id' => 'room_id',
                            'house_type' => 'house_type',
                            'money' => 'money',
                    );
                    $reserve_room_info = $reserve_model->getOne($where, $columns);
                }
                //查询分散式(合/整)/集中式房间名称
                $room_name = $room_helper->getRoomInfoById($reserve_room_info);
                foreach ($fee_type_list as $fee_type) {
                    if ($fee_type['type_name'] == '定金') {
                        $fee_type['money'] = $reserve_room_info['money'];
                        $unsubscribe_fee = $fee_type;
                    }
                }
                $this->assign('ns_source', $ns_source);
                $this->assign('room_name', $room_name[0]);//房间名称
                $this->assign('reserve_room_info', $reserve_room_info);//house_id, room_id, house_type
                $this->assign('unsubscribe_fee', $unsubscribe_fee);//退订费用类型(定金)
            } elseif ($tc_stop_contract == 'tc_stop_contract') {
                //6 终止租客合同
                $tc_contract_id = I('tc_stop_contract_id', 0);
                $tc_contract_model = new \Common\Model\Erp\TenantContract();
                $where = array('is_delete' => 0, 'contract_id' => $tc_contract_id);
                $columns = array('deposit' => 'deposit',);
                //合同押金
                $contract_info = $tc_contract_model->getOne($where, $columns);
                foreach ($fee_type_list as $fee_type) {
                    if ($fee_type['type_name'] == '押金') {
                        $fee_type['money'] = $contract_info['deposit'];
                        $stop_contract_fee = $fee_type;
                    }
                }
                $rental_model = new \Common\Model\Erp\Rental();
                $rent_where = array('contract_id' => $tc_contract_id);
                $rent_columns = array('rental_id' => 'rental_id', 'house_id' => 'house_id', 'room_id'=> 'room_id', 'house_type' => 'house_type');
                $rental_data = $rental_model->getOne($rent_where, $rent_columns);

                $room_helper = new \Common\Helper\Erp\Room();
                $room_info = $room_helper->getRoomInfoById($rental_data);

                $this->assign('tc_contract_id', $tc_contract_id);
                $this->assign('rental_data', $rental_data);
                $this->assign('tc_stop_contract', $tc_stop_contract);
                $this->assign('stop_contract_fee', $stop_contract_fee);
                $this->assign('room_info', $room_info[0]);
            }
            $this->assign('pay_type', $pay_type);               //支付类型
            $this->assign('fee_type_list', $fee_type_list);    //费用类型
            $data = $this->fetch("add_expense");
            return $this->returnAjax(array("status"=>1,"tag_name"=>"新增支出流水","model_name"=>"list_finance_cost","model_js"=>"finance_add_dexpenseJs","model_href"=>Url::parse("Finance-Serial/addExpense"),"data"=>$data));
        } else {
            $room_fee_type_info = $_POST; //表单提交信息
            $serialNumberModel = new  \Common\Model\Erp\SerialNumber();
            //house_category是输入框搜索房间后传过来的
            if (isset($room_fee_type_info['house_type'])) {
                $house_type = $room_fee_type_info['house_type'];
            }
            //1 业主合同的house_type(添加业主合同)
            if ($room_fee_type_info['sub_tenancy'] == 'owner_contract') {
                $house_type = $room_fee_type_info['house_type'];
                $source = array('source' => 'landlord_contract', 'source_id'=> $room_fee_type_info['contract_id']);
                //更新待办事项
                $serial_helper = new \Common\Helper\Erp\SerialNumber();
                $landlord_info = $serial_helper->getLandlordContractInfo($room_fee_type_info['contract_id']);
                $serial_helper->updateLandlordPayTime($room_fee_type_info['contract_id'], $landlord_info[0], $room_fee_type_info['room'], $res = false);
                
                //业主合同租金递增(以上代码会把合同下次支付时间更新)以下代码会根据下次支付时间,计算租金递增
                $landlord_con_helper = new \Common\Helper\Erp\LandlordContract();
                $dizeng_res = $landlord_con_helper->computeLandlordRentIncrease($room_fee_type_info['contract_id']);
                if (!$dizeng_res) {return $this->returnAjax(array("status"=>0, "data"=>'业主合同租金递增失败!'));}
            }
            //3 房间退租
            if ($room_fee_type_info['sub_tenancy'] == 'out_tenancy') {
                $house_type = $room_fee_type_info['house_type'];
                $source = array('source' => 'out_tenancy', 'source_id'=> (($room_fee_type_info['house_type'] == 1) ? (($room_fee_type_info['room_id'] ? $room_fee_type_info['room_id'] : $room_fee_type_info['house_id'])) : $room_fee_type_info['room_focus_id']));
                $rental_helper = new \Common\Helper\Erp\Rental();
                $contract_id = $rental_helper->getRoomNewestContractId($room_fee_type_info);
                $todo_model = new \Common\Model\Erp\Todo();
                $shouzu_res = $todo_model->delete(array('module' => 'tenant_contract_shouzu' , 'entity_id' => $contract_id[0]['contract_id']));
                $daoqi_res = $todo_model->delete(array('module' => 'tenant_contract' , 'entity_id' => $contract_id[0]['contract_id']));
                if (!$shouzu_res || !$daoqi_res) {return $this->returnAjax(array("status"=>0, "data"=>'合同待办事项删除失败!'));}
            }
            //2 首页待办提醒(二房东交租),house_type
            if ($room_fee_type_info['sub_tenancy'] == 'landlord_con') {
                $house_type = $room_fee_type_info['house_type'];
                $source = array('source' => 'landlord_contract', 'source_id'=> $room_fee_type_info['contract_id']);
                //更新待办事项
                $serial_helper = new \Common\Helper\Erp\SerialNumber();
                $res = $serial_helper->validateUserFirstWhetherCharge($source);
                $landlord_info = $serial_helper->getLandlordContractInfo($room_fee_type_info['contract_id']);
                $serial_helper->updateLandlordPayTime($room_fee_type_info['contract_id'], $landlord_info[0], $room_fee_type_info['room'], $res);
                
                //业主合同租金递增(以上代码会把合同下次支付时间更新)一下代码会根据下次支付时间,计算租金递增
                $landlord_con_helper = new \Common\Helper\Erp\LandlordContract();
                $dizeng_res = $landlord_con_helper->computeLandlordRentIncrease($room_fee_type_info['contract_id']);
                if (!$dizeng_res) {return $this->returnAjax(array("status"=>0, "data"=>'业主合同租金递增失败!'));}
            }
            //4 业主合同支付
            if ($room_fee_type_info['sub_tenancy'] == 'lc_pay') {
                $house_type = $room_fee_type_info['house_type'];
                $source = array('source' => 'landlord_contract', 'source_id'=> $room_fee_type_info['contract_id']);
                //更新待办事项
                $serial_helper = new \Common\Helper\Erp\SerialNumber();
                $res = $serial_helper->validateUserFirstWhetherCharge($source);
                $landlord_info = $serial_helper->getLandlordContractInfo($room_fee_type_info['contract_id']);
                $serial_helper->updateLandlordPayTime($room_fee_type_info['contract_id'], $landlord_info[0], $room_fee_type_info['room'], $res); 
                
                //业主合同租金递增(以上代码会把合同下次支付时间更新)一下代码会根据下次支付时间,计算租金递增
                $landlord_con_helper = new \Common\Helper\Erp\LandlordContract();
                $dizeng_res = $landlord_con_helper->computeLandlordRentIncrease($room_fee_type_info['contract_id']);
                if (!$dizeng_res) {return $this->returnAjax(array("status"=>0, "data"=>'业主合同租金递增失败!'));}
            }
            //5 房间退订
            if ($room_fee_type_info['sub_tenancy'] == 'unsubscribe') {
                $reserve_model = new \Common\Model\Erp\Reserve();
                //$commit_res_id = $room_fee_type_info['reserve_id'];
                //$reserve_data = $reserve_model->getOne(array('reserve_id' => $commit_res_id), array('house_id' => 'house_id', 'room_id' => 'room_id' ,'house_type' => 'house_type'));
                //$reserve_id = $_SESSION['reserve_id'];
                $house_type = $room_fee_type_info['house_type'];
                $source = array('source' => 'unsubscribe', 'source_id'=> $room_fee_type_info['reserve_id']);
                //$reser_helper = new \Common\Helper\Erp\Reserve();
                //删除房间预订的待办事项
                //$del_backlog_res = $reser_helper->delRoomReserveBacklog($reserve_id, $reserve_data);
                //if (!$del_backlog_res) {
                //    return $this->returnAjax(array("status"=>0, "data"=>'预订待办事项删除失败!'));
                //}
                //unset($_SESSION['reserve_id']);
            }
            //6 终止租客合同
            if ($room_fee_type_info['sub_tenancy'] == 'tc_stop_contract') {
                $house_type = $room_fee_type_info['house_type'];
                $source = array('source' => 'tenant_contract', 'source_id'=> $room_fee_type_info['tc_contract_id']);
            }
            //费用类型列表
            $fee_type_list = $room_fee_type_info['cost'];
            //循环组装费用类型列表
            foreach ($fee_type_list as $fee_type) {
                $fee_arr[] = array('type_name' => $fee_type['type_name'], 'fee_type_id' => $fee_type['cost_type'], 'money' => $fee_type['cost_num']);
            }
            $insert_data = array(
                    'not_room_serial' => $room_fee_type_info['not_room_serial'],
                    'house_id' => isset($room_fee_type_info['house_id']) ? $room_fee_type_info['house_id'] : $house_id,     //房源编号 根据输入的房间来查
                    'room_id' => isset($room_fee_type_info['record_id']) ? $room_fee_type_info['record_id'] : $room_fee_type_info['room_id'],//房间编号
                    'house_type' => ($room_fee_type_info['not_room_serial'] == 1) ? $house_type = 0 : (($house_type == 1) ? 'room' : (($house_type == 2) ? 'house' : '')), //房源类型(1/分散, 2/集中)
                    'detail' => $fee_arr,
                    'serial_number' => $serial_number,            //流水号
                    'pay_time' => strtotime($room_fee_type_info['time']),    //支付时间
                    'subscribe_pay_time' => 0,                         //预约缴费时间
                    'type' => 2,                                       //流水/支出  1收入; 2支出
                    'receivable' => $room_fee_type_info['receivable'], //流水金额     所有的租金,电费等 总额
                    'money' => $room_fee_type_info['receivable'],         //实际应该的金额    money 和  final_money 一开始一样
                    'final_money' => $room_fee_type_info['receivable'],   //实际已发生的金额   冲账后要减
                    'father_id' => 0,                                  //有欠费清单时, father_id 为上一条记录的id
                    'user_id' => $user_id,
                    'city_id' => $city_id,
                    'company_id' => $company_id,
                    'payment_mode' => $room_fee_type_info['pay_ways'], //支付类型  现金 支付宝等
                    'remark' => $room_fee_type_info['mark'],         //备注
                    'status' => 0,           //流水收入状态  0，普通，1，差额减免，2欠费，3待处理
                    'source' => $source,
            );
            //1 只要是业主合同,不管集中式和分散式, 房间都为0
            if ($room_fee_type_info['sub_tenancy'] == 'owner_contract') {
                $insert_data['house_id'] = 0;
                $insert_data['room_id'] = 0;
            }
            //3 房间退租
            if ($room_fee_type_info['sub_tenancy'] == 'out_tenancy') {
                $insert_data['house_id'] = $room_fee_type_info['house_id'];
                $insert_data['room_id'] = ($room_fee_type_info['house_type'] == 1) ? $room_fee_type_info['room_id'] : $room_fee_type_info['room_focus_id'];
            }
            //2 首页待办提醒(二房东交租)
            if ($room_fee_type_info['sub_tenancy'] == 'landlord_con') {
                $insert_data['house_id'] = 0;
                $insert_data['room_id'] = 0;
            }
            //4 业主合同支付
            if ($room_fee_type_info['sub_tenancy'] == 'lc_pay') {
                $insert_data['house_id'] = 0;
                $insert_data['room_id'] = 0;
            }
            //5 房间退订
            if ($room_fee_type_info['sub_tenancy'] == 'unsubscribe') {
                $insert_data['house_id'] = $room_fee_type_info['house_id'];
                $insert_data['room_id'] = ($room_fee_type_info['house_type'] == 1) ? $room_fee_type_info['room_id'] : $room_fee_type_info['room_id'];
            }
            //非房间流水
            if ($room_fee_type_info['not_room_serial'] == 1) {
                $insert_data['house_id'] = 0;
                $insert_data['room_id'] = 0;
            }
            if(!isset($insert_data['house_id']) || !isset($insert_data['room_id']) || !isset($insert_data['house_type']))
            {
                return $this->returnAjax(array("status"=>0, "data"=>'房源不存在，请先添加！'));
            }
            $result = $serialNumberModel->addSeriaNumber($insert_data);
            if ($result) {
                return $this->returnAjax(array("status"=>1, "tag"=>Url::parse("finance-index/index")));
            }
            return $this->returnAjax(array("status"=>0, "data"=>'新增支出失败!'));
        }
    }
    /**
     * 指出流水编辑
     * 修改时间 2015年5月25日20:20:43
     *
     * @author ft
     */
    public function editexpenseAction() {
        $user_id = $this->user['user_id'];
        $city_id = $this->user['city_id'];
        $company_id = $this->user['company_id'];
        $serial_id = I("get.serial_id", 0);//流水id
        if (Request::isGet()) {
            if(!$this->verifyModulePermissions(\Common\Helper\Permissions::SELECT_AUTH_ACTION)){
                return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("finance-serial/editexpense")));
            }


            $room_id = I("get.room_id", 0, "int");    //房间id
            $house_type = I('get.house_type', 0);
            $flat_id = I('get.flat_id', 0, "int");
            $house_id = I('get.house_id', 0);
            $source = I('get.source');
            $source_id = I('get.source_id');
            $source_arr = array('source' => $source, 'source_id' => $source_id);
            $serial_number_model = new  \Common\Model\Erp\SerialNumber();     //流水model
            $serial_number_helper = new  \Common\Helper\Erp\SerialNumber();    //流水helper
            $serial_detail_model = new \Common\Model\Erp\SerialDetail();       //流水详细model
            $fee_type_model = new \Common\Model\Erp\FeeType();      //费用类型
            $config_model = new \Common\Model\Erp\SystemConfig();   //数据库配置model
            $pay_type = $config_model->getFind('Serial', 'payType');//支付方式
            //该公司下所有的费用类型下拉
            $fee_type_list = $fee_type_model->getData(array('company_id' => $company_id, 'is_delete' => 0));

            //获取房间费用类型信息,房间名字,以及流水总金额,支付时间/方式,备注等
            if ($house_type == 1) {
                //获取房间费用类型信息,房间名字,以及流水总金额,支付时间/方式,备注等
                $room_fee_info = $serial_number_helper->editRoomFeeType($company_id, $serial_id, $room_id, $house_type, $house_id, $source_arr);
            } elseif($house_type == 2) {
                //获取房间费用类型信息,房间名字,以及流水总金额,支付时间/方式,备注等
                $room_fee_info = $serial_number_helper->editRoomFeeType($company_id, $serial_id, $room_id, $house_type, $flat_id, $source_arr);
            } else {
                //获取非房间费用类型
                $not_room_data = $serial_number_helper->getNotRoomFeeType($serial_id);
            }
            if (isset($not_room_data)) {//非房间流水不传id到视图
                $not_room_serial = 'not_room';//非房间流水标识
                $this->assign('serial_id', $serial_id); //流水id
                $this->assign('not_room_data', $not_room_data);
                $this->assign('not_room', $not_room_serial);
                $this->assign('room_fee_info', $room_fee_info);  //该房间的费用类型信息
            } else {
                //弹出房间名
                $room_name = array_shift($room_fee_info);
                //弹出该房间的,流水总金额,支付时间/方式,备注等
                $room_serial_info = array_pop($room_fee_info);
                $this->assign('room_name', $room_name);
                $this->assign('room_serial_info', $room_serial_info);
                $this->assign('room_fee_info', $room_fee_info);    //房间费用类型
                $this->assign('serial_id', $serial_id);
                $this->assign('source', $source);
            }
            $this->assign('fee_type_list', $fee_type_list);  //该公司下所有的费用类型下拉
            $this->assign('pay_type', $pay_type);            //该公司下所有的支付方式
            $data = $this->fetch("add_expense");
            return $this->returnAjax(array("status"=>1,"tag_name"=>"编辑支出流水","model_name"=>"list_finance_cost","model_js"=>"finance_add_dexpenseJs","model_href"=>Url::parse("Finance-Serial/editExpense"),"data"=>$data));
        } else {
            if(!$this->verifyModulePermissions(\Common\Helper\Permissions::UPDATE_AUTH_ACTION)){
                return $this->returnAjax(array('__status__'=>403));
            }

            $serial_num_helper = new \Common\Helper\Erp\SerialNumber();
            $room_fee_type_info = $_POST; //表单提交信息
            $serialNumberModel = new  \Common\Model\Erp\SerialNumber();
            $house_view_helper = new \Common\Helper\Erp\HouseView();
            $room_name = $room_fee_type_info['room'];    //房间名
            $fee_type_list = $room_fee_type_info['cost']; //费用类型列表
            $serial_id = $room_fee_type_info['sn_id'];//流水id
            //获取房源类型,及房源id
            $serial_where = array('serial_id' => $serial_id);
            $query_house_type = $serialNumberModel->getOne($serial_where);
            //循环组装费用类型列表
            foreach ($fee_type_list as $fee_type) {
                $fee_arr[] = array(
                        'type_name' => $fee_type['type_name'],
                        'fee_type_id' => $fee_type['cost_type'],
                        'money' => $fee_type['cost_num'],
                        'serial_id' => $fee_type['sn_id'],
                );
            }
            $insert_data = array(
                    'serial_id' => $serial_id,
                    'house_type' => ($query_house_type['house_type'] == 1) ? 'room' : (($query_house_type['house_type'] == 2) ? 'house' : ''), //房源类型(1/分散, 2/集中)
                    'source' => $query_house_type['source'],
                    'source_id' => $query_house_type['source_id'],
                    'serial_number' => $query_house_type['serial_number'],
                    'house_id' => $query_house_type['house_id'],
                    'room_id' => $query_house_type['room_id'],
                    'detail' => $fee_arr,
                    'pay_time' => strtotime($room_fee_type_info['time']),    //支付时间
                    'subscribe_pay_time' => 0,    //支付时间
                    'type' => 2,                                  //流水/支出  1收入; 2支出
                    'receivable' => $room_fee_type_info['receivable'],//流水金额     所有的租金,电费等 总额
                    'money' => $room_fee_type_info['receivable'],         //实际应该的金额    money 和  final_money 一开始一样
                    'final_money' => $room_fee_type_info['receivable'],   //实际已发生的金额   冲账后要减
                    'father_id' => 0,                        //有欠费清单时, father_id 为上一条记录的id
                    'user_id' => $user_id,
                    'city_id' => $city_id,
                    'company_id' => $company_id,
                    'payment_mode' => $room_fee_type_info['pay_ways'], //支付类型  现金 支付宝等
                    'remark' => $room_fee_type_info['mark'],         //备注
                    'status' => 0,           //流水收入状态  0，普通，1，差额减免，2欠费，3待处理
            );
            $result = $serialNumberModel->addSeriaNumber($insert_data, $arrear_date);
            if ($result) {
                return $this->returnAjax(array("status"=>1, "tag"=>Url::parse("finance-index/index")));
            }
                return $this->returnAjax(array("status"=>0, "data"=>'修改支出流水失败!'));
        }
    }

    /**
     * 冲账流水
     * 修改时间 2015年4月24日14:18:36
     *
     * @author ft
     */
    protected function strikebalancesumAction() {
        if(!$this->verifyModulePermissions(\Common\Helper\Permissions::UPDATE_AUTH_ACTION)){
            return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("finance-serial/strikebalancesum")));
        }

        $company_id = $this->user['company_id'];
        $strike_info = $_POST;
        $current_sum = $strike_info['cur_num'];    //当前金额
        $strike_balance_sum = $strike_info['cost_num'];//冲账金额
        if ($strike_balance_sum > $current_sum) {
            return $this->returnAjax(array("status" => 0, "message" => "冲账金额不能大于当前金额!"));
        }
        $serial_helper = new \Common\Helper\Erp\SerialNumber();
        $db_final_money = $serial_helper->getSerialFinalMoney($strike_info['sn_id'], $strike_info['detail_id']);
        $company_pass = $serial_helper->getCompanyPassword($company_id, $strike_info['auth_pwd']);
        if (!$company_pass) {
            return $this->returnAjax(array("status"=>0,"message"=> "冲账密码错误!"));
        } else {
            $diff_sum = $current_sum - $strike_balance_sum;    //冲账后金额
            $serial_detail_data = array('final_money' => $diff_sum);
            $serial_detail_model = new \Common\Model\Erp\SerialDetail();//流水详细model
            $serial_strike_model = new \Common\Model\Erp\SerialStrikeBalance();//流水冲账model
            $serial_number_model = new \Common\Model\Erp\SerialNumber();//流水model
            $data = array(
                    'serial_id' => $strike_info['sn_id'],    //流水id
                    'serial_detail_id' => $strike_info['detail_id'],//详细id
                    'money' => $strike_balance_sum,//冲账金额
                    'create_time' => time(),//冲账时间
                    'mark' => $strike_info['mark'],//冲账备注
            );
            $serial_strike_model->Transaction();
            //添加冲账数据
            $strike_id = $serial_strike_model->insert($data);//冲账id
            if (!$strike_id) {
                $serial_strike_model->rollback();
                return $this->returnAjax(array("status" => 0, "message" => "冲账失败!",));
            } else {
                $detail_where = array('serial_detail_id' => $strike_info['detail_id']);
                //修改流水详细冲账金额
                $detail_res = $serial_detail_model->edit($detail_where, $serial_detail_data);
                if (!$detail_res) {
                    $serial_strike_model->rollback();
                    return $this->returnAjax(array("status" => 0, "message" => "流水详细,冲账金额修改失败!"));
                }
                $serial_where = array('serial_id' => $strike_info['sn_id']);
                $serial_num_data = array('final_money' => ($db_final_money[0]['money'] - $strike_balance_sum));
                //修改流水的冲账金额
                $serial_res = $serial_number_model->edit($serial_where, $serial_num_data);
                if (!$serial_res) {
                    $serial_strike_model->rollback();
                    return $this->returnAjax(array("status" => 0, "message" => "流水冲账金额修改失败!"));
                }
                if ($strike_id && $detail_res && $serial_res) {
                    $serial_strike_model->commit();
                    return $this->returnAjax(array('status' => 1, 'cost_num' => $strike_balance_sum, 'message' => '冲账成功!'));
                }
            }
        }
    }

    /**
     * 修改流水
     * 修改时间2015年3月19日 15:31:39
     *
     * @author yzx
     */
    protected function editAction()
    {
        if(!$this->verifyModulePermissions(\Common\Helper\Permissions::UPDATE_AUTH_ACTION)){
            return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("finance-serial/edit")));
        }

        if (Request::isPost())
        {
            $serialNumberModel = new  \Common\Model\Erp\SerialNumber();
            $serial_id = Request::queryString("post.serial_id",0,"int");
            $house_id = Request::queryString("post.house_id",0,"int");
            $house_type = Request::queryString("post.house_type",'',"string");
            $detail = Request::queryString("post.detail");
            $pay_time = Request::queryString("post.pay_time",'',"string");
            $status = Request::queryString("post.status",0,"int");
            $money = Request::queryString("post.money",0,"int");

            $serial_number['house_id'] = $house_id;
            $serial_number['house_type'] = $house_type;
            $serial_number['detail'] = $detail;
            $serial_number['pay_time'] = $pay_time;
            $serial_number['status'] = $status;
            $serial_number['money'] = $money;
            $result = $serialNumberModel->editSeriaNumber($serial_number, $serial_id);
            if ($result)
            {
                return $this->returnAjax(array("status"=>1,"data"=>true));
            }
            return $this->returnAjax(array("status"=>0,"data"=>false));
        }
    }

    /**
     * 删除费用类型
     * 修改时间 2015年4月21日17:59:45
     *
     * @author ft
     */
    public function deletefeeAction() {
        if(!$this->verifyModulePermissions(\Common\Helper\Permissions::DELETE_AUTH_ACTION,'sys_water_type_management')){
            return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("finance-serial/deletefee")));
        }

        $company_id = $this->user['company_id'];
        $serial_id = Request::queryString('post.sn_id', 0, 'int');
        $fee_type_id = Request::queryString('post.fee_id', 0, 'int');
        $fee_type_money = I('post.fee_money', 0);
        $total_money = I('post.total_money', 0);

        $serial_detail_model = new \Common\Model\Erp\SerialDetail();
        $serial_num_model = new \Common\Model\Erp\SerialNumber();
        $detail_where = array('serial_id' => $serial_id, 'fee_type_id' => $fee_type_id);
        $detail_data = array('is_delete' => 1);
        //改变主流水的金额
        $serial_where = array('serial_id' => $serial_id);
        $serial_data = array('money' => $total_money - $fee_type_money);

        $res = $serial_detail_model->edit($detail_where, $detail_data);
        $serial_res = $serial_num_model->edit($serial_where, $serial_data);
        $serial_detail_model->Transaction();
        if (!$res) {
            $serial_detail_model->rollback();
            return $this->returnAjax(array('status' => 0, 'message' => '费用类型删除失败!'));
        } elseif (!$serial_res) {
            $serial_detail_model->rollback();
            return $this->returnAjax(array('status' => 0, 'message' => '主流水金额修改失败!'));
        } elseif ($res && $serial_res) {
            $serial_detail_model->commit();
            return $this->returnAjax(array('status' => 1, 'message' => '费用类型删除成功!'));
        }
    }

    /** 流水列表
     *  最后修改时间 2015-3-19
     *
     * @author denghsuang
     */
    protected function listAction(){
        if(!$this->verifyModulePermissions(\Common\Helper\Permissions::SELECT_AUTH_ACTION)){
            return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("finance-serial/list")));
        }

        $search = array('fee_type','source','pay_type');
        $get = \App\Web\Lib\Request::queryString('get.');
        $search = array_intersect_key($get, array_fill_keys($search, false));
        $search = array_filter($search);
        $helper = new \App\Web\Helper\SerialNumber();
        $user = $this->getUser();
        //整理数据
        $data = $helper->getList($user['user_id'],\App\Web\Lib\Request::queryString('get.page'),10,$search);
        //取得所有分类ID
        $housingids = array();
        foreach ($data['data'] as $key => $value){
            $datakey = null;
            $datapk = null;
            if($value['house_id'] && $value['room_id']){
                $datakey = \Common\Helper\Erp\Housing::DISTRIBUTED_ROOM;
                $datapk = $value['room_id'];
            }elseif($value['house_id'] && !$value['room_id']){
                $datakey = \Common\Helper\Erp\Housing::DISTRIBUTED_ENTIRE;
                $datapk = $value['house_id'];
            }else{
                $datakey = \Common\Helper\Erp\Housing::CENTRALIZED;
                $datapk = $value['room_id'];
            }
            if(!isset($housingids[$datakey])){
                $housingids[$datakey] = array();
            }
            $vlaue['house_room_type'] = $datakey;
            $vlaue['house_room_pk'] = $datapk;
            $housingids[$datakey][] = $datapk;
            $data['data'][$key] = $value;
        }
        //取得房源数据
        $housingdata = \App\Web\Helper\Housing::getHousingInfo($housingids);
        $housingdata = $housingdata ? $housingdata : array();
        foreach ($data['data'] as $key => $value){
            if(isset($housingdata[$value['house_room_type']]) && isset($housingdata[$value['house_room_type']][$value['house_room_pk']])){
                $value['housing'] = $housingdata[$value['house_room_type']][$value['house_room_pk']];
            }
            $data['data'][$key] = $value;
        }
        return $data;
    }

    /**
     * 欠费列表
     *  最后修改时间 2015-3-19
     *
     * @author ft
     */
    protected function listdebtsAction(){
        if(!$this->verifyModulePermissions(\Common\Helper\Permissions::SELECT_AUTH_ACTION)){
            return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("finance-serial/listdebts")));
        }


        $page = \App\Web\Lib\Request::queryString('get.page',0);
        $company_id = $this->user['company_id'];
        $serialNumberModel = new  \Common\Model\Erp\SerialNumber();
        $pagesize = 20;
        if ($page) {
            $list = $serialNumberModel->getDebtsListPc($company_id, $page, $pagesize,$this->user);
            foreach ($list['data'] as $key => $list_val) {
                if ($list_val['status'] == 0) {
                    $list_val['status'] = '已收费';
                    $list['data'][$key] = $list_val;
                } elseif ($list_val['status'] == 1) {
                    $list_val['status'] = '差额减免';
                    $list['data'][$key] = $list_val;
                } elseif ($list_val['status'] == 2) {
                    $list_val['status'] = '未收费';
                    $list['data'][$key] = $list_val;
                } elseif($list_val['status'] == 3) {
                    $list[$key]['status'] = '待处理';
                    $list['data'][$key] = $list_val;
                }
                $list_val['room_type'] = (!empty($list_val['room_type']) && $list_val['room_type'] == 'main') ? '主卧' : ((!empty($list_val['room_type']) && $list_val['room_type'] == 'second') ? '次卧' : ((!empty($list_val['room_type']) && $list_val['room_type'] == 'guest') ? '客卧' : ''));
                $list['data'][$key] = $list_val;
            }
            $this->assign('view', Request::queryString('get.view_type','data'));
            $this->assign('debts_list',$list['data']);
            $data = $this->fetch('list_debts');
            return $this->returnAjax(array("status"=>1,"tag_name"=>"欠费清单","model_name"=>"list_debts","model_js"=>"finance_debtsJs","model_href"=>Url::parse("Finance-Serial/listDebts"),"data"=>$data, "page_info" => $list['page']));
        } else {
            $this->assign('view', Request::queryString('get.view_type','template'));
            $data = $this->fetch('list_debts');
            return $this->returnAjax(array("status"=>1,"tag_name"=>"欠费清单","model_name"=>"list_debts","model_js"=>"finance_debtsJs","model_href"=>Url::parse("Finance-Serial/listDebts"),"data"=>$data));
        }
    }

    /**
     * 欠费清单查看 , 费用流水详细
     * 修改时间 2015年5月14日17:10:56
     *
     * @author ft
     */
    protected function showdebtsdetailAction() {
        if(!$this->verifyModulePermissions(\Common\Helper\Permissions::SELECT_AUTH_ACTION)){
            return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("finance-serial/showdebtsdetail")));
        }


        $user_id = I('get.user_id');    //用户id
        $source = I('get.source', '');
        $source_id = I('get.source_id', 0);
        $serial_id = I('get.serial_id', 0);  //流水id
        $house_id = I('get.house_id', 0);    //房源
        $room_id = I('get.room_id', 0);    //分散式房间id
        $flat_id = I('get.flat_id', 0);    //集中式公寓
        $room_focus_id = I('get.room_focus_id', 0);//集中式房间
        $house_type = I('get.house_type', 0);

        $data_continue = array(
                'user_id' => $user_id,
                'source' => $source,
                'source_id' => $source_id,
                'serial_id' => $serial_id,
                'house_id' => $house_id,
                'room_id' => $room_id,
                'flat_id' => $flat_id,
                'room_focus_id' => $room_focus_id,
                'house_type' => $house_type,
        );
        $serial_num_helper = new \Common\Helper\Erp\SerialNumber();
        if ($house_type == 1) {//分散式
            $debts_detail_info = $serial_num_helper->detbtsDetail($data_continue);
            //判断当前欠费清单状态,转换为页面显示的文字
            $debts_detail_info['status'] = ($debts_detail_info['status'] == 0) ? '已收费' :
            (($debts_detail_info['status'] == 1) ? '差额减免' :
            (($debts_detail_info['status'] == 2) ? '未收费' :
            (($debts_detail_info['status'] == 3) ? '待处理' : '')));
            $this->assign('debts_detail_info', $debts_detail_info);
        } elseif ($house_type == 2) {//集中式
            $debts_detail_info = $serial_num_helper->detbtsDetail($data_continue);
            //判断当前欠费清单状态,转换为页面显示的文字
            $debts_detail_info['status'] = ($debts_detail_info['status'] == 0) ? '已收费' :
            (($debts_detail_info['status'] == 1) ? '差额减免' :
            (($debts_detail_info['status'] == 2) ? '未收费' :
            (($debts_detail_info['status'] == 3) ? '待处理' : '')));
            $this->assign('debts_detail_info', $debts_detail_info);
        } else {//非房间欠费流水
            $debts_detail_info = $serial_num_helper->detbtsDetail($data_continue);
            //判断当前欠费清单状态,转换为页面显示的文字
            $debts_detail_info['status'] = ($debts_detail_info['status'] == 0) ? '已收费' :
            (($debts_detail_info['status'] == 1) ? '差额减免' :
            (($debts_detail_info['status'] == 2) ? '未收费' :
            (($debts_detail_info['status'] == 3) ? '待处理' : '')));
            $this->assign('debts_detail_info', $debts_detail_info);
        }
        $this->assign('house_type', $house_type);
        $this->assign('source', $source);
        $data = $this->fetch('debts_detail');
        return $this->returnAjax(array("status"=>1,"tag_name"=>"欠费清单详细","model_name"=>"debts_detail","model_js"=>"finance_viewJs","model_href"=>Url::parse("Finance-Serial/showDebtsDetail"), "data"=>$data));
    }

    /**
     * 删除欠费流水
     * 修改时间 2015年5月18日20:21:18
     *
     * @author ft
     */
    protected function deletedebtsAction() {
        if(!$this->verifyModulePermissions(\Common\Helper\Permissions::DELETE_AUTH_ACTION)){
            return $this->returnAjax(array('__status__'=>403,'__closetag__'=>Url::parse("finance-serial/deletedebts")));
        }

        $cid = $this->user['company_id'];
        $debts_id = I('post.serial_id');//欠费流水id
        $father_id = I('post.father_id');//欠费流水父id
        $serial_num_model = new \Common\Model\Erp\SerialNumber();
        //欠费清单修改数据
        $debts_where = array('serial_id' => $debts_id, 'company_id' => $cid);
        $debts_data = array('is_delete' => 1, 'status' => 0);
        //流水修改数据
        $seial_where = array('serial_id' => $father_id, 'company_id' => $cid);
        $serial_data = array('status' => 0);
        $serial_num_model->Transaction();
        if ($debts_id) {
            $debts_res = $serial_num_model->edit($debts_where, $debts_data);
        }
        if (!$debts_res) {
            $serial_num_model->rollback();
            $this->returnAjax(array('status' => 0, 'message' => '删除欠费失败!'));
        }
        if ($debts_res) {
            $serial_res = $serial_num_model->edit($seial_where, $serial_data);
        }
        if (!$serial_res) {
            $serial_num_model->rollback();
            return $this->returnAjax(array('status' => 0, 'message' => '删除欠费清单失败!'));
        }
        if ($debts_res && $serial_res) {
            $serial_num_model->commit();
            return $this->returnAjax(array('status' => 1, 'message' => '删除欠费成功!'));
        }
    }
    
    /**
     * 新增收入，根据房间获取 该房间的租客合同信息，并返回
     * 修改时间 2015年9月6日15:08:04
     * 
     * author ft
     */
    protected function roomsearchcontractAction() {
        $user = $this->user;
        $company_id = $user['company_id'];
        $house_name = I('get.house_name', '');
        $house_id = I('get.house_id', 0);
        $room_id = I('get.record_id', 0);
        $house_type = I('get.house_type', 0);
        $rental_way = I('get.rental_way', 0);
        $rental_model = new \Common\Model\Erp\Rental();
        $serial_helper= new \Common\Helper\Erp\SerialNumber();
        $contract_model = new \Common\Model\Erp\TenantContract();
        $rent_info = $rental_model->getData(array('house_id' =>$house_id, 'room_id' => $room_id, 'house_type' => $house_type, 'is_delete' => 0), array('contract_id' => 'contract_id'));
        $contract_id_arr = array_column($rent_info, 'contract_id');
        $where = new \Zend\Db\Sql\Where();
        $where->lessThan('next_pay_time', new Expression('end_line'));
        $where->in('contract_id', $contract_id_arr);
        $contract_info = $contract_model->getOne($where);
        if ($contract_info) {
            $room_fee_data = $serial_helper->getContractFeeByid($contract_info['contract_id'], $house_type, $house_id, $room_id, $company_id);
            foreach ($room_fee_data['fee_type_arr'] as $data) {
                $contract_money += $data['money'];
            }
            if ($room_fee_data['extract_money'] != 0) {
                $contract_money = $contract_money - $room_fee_data['extract_money'];
                setcookie('extract_money[money]', "{$room_fee_data['extract_money']}", time()+3600);
                setcookie('extract_money[contract_id]', "{$contract_info['contract_id']}", time()+3600);
            }
            $res = $contract_model->edit(array('contract_id' => $contract_info['contract_id']), array('next_pay_money' => $contract_money));
            return $this->returnAjax(array('status' => 1, 'data' => $room_fee_data['fee_type_arr'], 'message' => '有合同!'));
        } else {
            return $this->returnAjax(array('status' => 0, 'message' => '没有合同!'));
        }
    }
    
    /**
     * 新增支出，根据房间获取 该房间的业主合同信息，并返回
     * 修改时间 2015年9月7日09:37:18
     * 
     * author ft
     */
    protected function roomsearchlandlordconAction() {
        $company_id = $this->user['company_id'];
        $house_name = I('get.house_name', '');
        $landlord_model = new \Common\Model\Erp\LandlordContract();
        $where = new \Zend\Db\Sql\Where();
        $where->equalTo('hosue_name', $house_name);
        $where->lessThan('next_pay_time', new Expression('end_line'));
        $where->equalTo('is_delete', 0);
        $where->equalTo('is_stop', 0);
        $landlord_info = $landlord_model->getOne($where);
        if (!$landlord_info) {
            return $this->returnAjax(array('status' => 0, 'message' => '没有合同!'));
        }
        $landlord_helper = new \Common\Helper\Erp\LandlordContract();
        $landlord_fee = $landlord_helper->getLandlordFeeById($landlord_info, $company_id);
        if ($landlord_fee) {
            return $this->returnAjax(array('status' => 1, 'data' => $landlord_fee, 'message' => '有合同!'));
        }
            return $this->returnAjax(array('status' => 0, 'message' => '没有合同!'));
    }
}
