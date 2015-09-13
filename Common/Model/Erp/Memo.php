<?php
namespace Common\Model\Erp;
class Memo extends \Common\Model\Erp
{
    /**
     * 插入备忘
     * @author too|编写注释时间 2015年5月13日 上午10:16:49
     */
    public function addMemo($data)
    {
        return $this->insert($data);
    }
    /**
     * 取一条备忘
     * @author too|编写注释时间 2015年5月13日 上午10:29:11
     */
    public function getOneMemo($memo_id,$uid)
    {
        $where = array('memo_id'=>$memo_id,'create_uid'=>$uid);
        return $this->getOne($where);
    }
    /**
     * 编辑一条备忘
     * @author too|编写注释时间 2015年5月13日 上午10:36:14
     */
    public function editMemo($memo_id,$uid,$data)
    {
        $where = array('memo_id'=>$memo_id,'create_uid'=>$uid);
        return $this->edit($where, $data);
    }
    /**
     * 删除备忘
     * @author too|编写注释时间 2015年5月13日 上午10:46:57
     */
    public function delMemo($memo_id,$uid)
    {
        $where = array('memo_id'=>$memo_id,'create_uid'=>$uid);
        if(!$this->getOneMemo($memo_id,$uid))
        {
            return false;
        }
        return $this->delete($where);
    }
}