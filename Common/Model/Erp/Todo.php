<?php
namespace Common\Model\Erp;
use \Common\Model\Erp;
class Todo extends Erp
{
	/**
	 * 停用分散式房间
	 * @var unknown
	 */
	const MODEL_ROOM_STOP = "ROOM_STOP";
	/**
	 * 停用分散式房源
	 * @var unknown
	 */
	const MODEL_HOUSE_STOP = "HOUSE_STOP";
	/**
	 * 分散式房源预约退租
	 * @var unknown
	 */
	const MODEL_HOUSE_RESERVE = "HOUSE_RESERVE";
	/**
	 * 分散式房间预约退租
	 * @var unknown
	 */
	const MODEL_ROOM_RESERVE = "ROOM_RESERVE";
	/**
	 * 集中式房间停用
	 * @var unknown
	 */
	const MODEL_ROOM_FOCUS_STOP = "ROOM_FOUCS_STOP";
	/**
	 * 集中式房间预约退租
	 * @var unknown
	 */
	const MODEL_ROOM_FOCUS_RESERVE = "ROOM_FOCUS_RESERVE";
	/**
	 * 集中式房间预定
	 * 
	 * @var unknown
	 */
	const MODEL_ROOM_FOCUS_RESERVE_OUT = "FOCUS_RESERVE_OUT";
	/**
	 * 分散式房间预定
	 * @var unknown
	 */
	const MODEL_ROOM_RESERVE_OUT = "ROOM_RESERVE_OUT";
	/**
	 * 分散式房源预定
	 * @var unknown
	 */
	const MODEL_HOUSE_RESERVE_OUT = "HOUSE_RESERVE_OUT";
	/**
	 * 合同到期
	 * @var unknown
	 */
	const MODEL_TENANT_CONTRACT = "tenant_contract";
	/**
	 * 合同收租
	 * @var unknown
	 */
	const MODEL_TENANT_CONTRACT_SHOUZHU = "tenant_contract_shouzu";
	/**
	 * 房东合同到期
	 * @var unknown
	 */
	const MODEL_LANDLORD_CONTRACT_CONTRACT = "landlord_contract";
	/**
	 * 房东合同收租
	 * @var unknown
	 */
	const MODEL_LANDLORD_CONTRACT_JIAOZU = "landlord_contract_jiaozu";
    // 写入日程表
    public function addTodo($param)
    {
        return $this->insert($param);
    }
    /**
     * 删除日程
     * 修改时间2015年6月13日10:18:02
     *
     * @author yzx
     * @param string $module
     * @param int $entityId
     * @return boolean
     */
    public function deleteTodo($module,$entityId){
    	$todoModel = new \Common\Model\Erp\Todo();
    	return  $todoModel->delete(array('module'=>$module,"entity_id"=>$entityId));
    
    }
}