<?php
namespace App\Web\Mvc\Controller\Plugins;
/**
 * 插件库展示
 * @author too|编写注释时间 2015年6月15日 下午2:50:44
 */
class IndexController extends \App\Web\Lib\Controller
{
    public function indexAction()
    {
        $dataModel = new \Common\Model\Plugins\WxxzFlat();////
        $info = $this->getUser();
        $data = $dataModel->getOne(array('is_delete'=>0,'founder_id'=>$info['user_id'],'company_id'=>$info['company_id']));//P($data);
        $this->assign('data', $data);//echo 'da';
        $html = $this->fetch();
        return $this->returnAjax(array('status'=>1,'data'=>$html,'tag_name'=>'功能插件'));
    }
}