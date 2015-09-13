<?php
namespace App\Web\Mvc\Model;
use Zend\Db\Sql\Where;
/**
 * 配置类
 * @author lishengyou
 * 最后修改时间 2014年11月11日 上午10:48:19
 *
 */
class SystemConfig extends Common
{
	private $table = 'system_config';
	public function __construct()
	{
		parent::__construct($this->table);
	}
	/**
	 * 读取配置
	 * Enter description here ...
	 * @param string or array $options  'list/key[;list/key]'  array('list/key'[,'list/key'])
	 * @return array('list'=>array('key'=>'value','key2'=>'value'),'list2'=>array('key'=>'value','key2'=>'value'));
	 */
	public function get($options=FALSE){
		$list = array();
		$retVal = array();
		if($options){
			if(is_string($options)){
				$options = explode(';', $options);
			}
			$tmp = array();
			//归类list
			foreach ($options as $option){
				$option = explode('/', $option);
				if(!isset($list[$option[0]])){
					$list[$option[0]] = array();
				}
				if(isset($option[1])){
					$list[$option[0]][] = $option[1];
				}
			}
			foreach ($list as $key => $value){
				$rs = NULL;
				if(empty($value)){
					//取得list下所有配置
					$rs = $this->getData(array('list'=>$key));
				}else{
					//取得list下key列表的值
					if(count($value) == 1){
						$rs = $this->getData(array('list'=>$key,'key'=>$value[0]),'',1);
					}else{
						$where = new Where();
						$where->in('key',$value);
						$where->equalTo('list', $key);
						$rs = $this->getData($where);
					}
				}
				if(is_array($rs)){
					$retVal = array_merge($retVal,$rs);
				}
			}
		}else{
			//取得所有配置
			$retVal = $this->getData(array());
		}
		//整理最后的返回格式
		$_retVal = array();
		foreach ($retVal as $value){
			if(!isset($_retVal[$value['list']])){
				$_retVal[$value['list']] = array();
			}
				
			if ('object' == $value['type'] || 'array' == $value['type'] || 'resource' == $value['type']){
				$value['value'] = unserialize($value['value']);
			}
			//查询是否已经存在
			$_retVal[$value['list']][$value['key']] = $value['value'] ? $value['value'] : '';
		}
// 		print_r($_retVal);
		//处理没有查询到的值
		foreach ($list as $key => $value){
			if(!empty($value)){
				$value = array_fill_keys($value,NULL);
			}
			if(!isset($_retVal[$key])){
				$_retVal[$key] = $value;
			}else{
				$_retVal[$key] = array_merge($value,$_retVal[$key]);
			}
		}
		return $_retVal;
	}
	/**
	 * 查询一条
	 * Enter description here ...
	 * @param unknown_type $list
	 * @param unknown_type $key
	 */
	public function getFind($list,$key){
		$retVal = $this->get($list.'/'.$key);
		if(isset($retVal[$list]) && isset($retVal[$list][$key])){
			return $retVal[$list][$key];
		}
		return NULL;
	}
	/**
	 * 设置配置
	 * Enter description here ...
	 * @param array $data  array('key'=>'list/key','value'=>'value') or array(array('key'=>'list/key','value'=>'value'),array('key'=>'list/key','value'=>'value'));
	 * @return bool
	 */
	public function set($data=array()){
		$retVal = TRUE;
		if(isset($data[0])){
			//更新插入多条
// 			$this->sql->Transaction();
			$this->_sql_object->Transaction();
			foreach ($data as $value){
				if(!$this->set($value)){
// 					$this->sql->rollback();
					$this->_sql_object->rollback();
					$retVal = FALSE;
					break;
				}
			}
			if($retVal){
				//全部更新成功，提交事务
				//$this->sql->commit();
				$this->_sql_object->rollback();
			}
		}else{
			//更新插入一条
			$where = array('list'=>explode('/', $data['key']));
			if(!isset($where['list'][1])){
				$retVal = FALSE;
			}else{
				$where['key'] = $where['list'][1];
				$where['list'] = $where['list'][0];
	
				$type = gettype($data['value']);
				//查询是否已经存在
				if ('object' == $type || 'array' == $type || 'resource' == $type) {
					$value = serialize($data['value']);
				}else{
					$value = $data['value'];
				}
// 				$data = $this->sql->select($this->table)->where($where)->limit(1)->execute();
				$data = $this->getOne($where);
				if(!!$data){
					//已经存在更新
					$data = $data[0];
					if($data['value'] != $value){
						//内容不相同，需要更新
						$retVal = $this->edit($where, array('value'=>$value,'type'=>$type));
					}
				}else{
					//不存在创建
					$data = array(
							'list'=>$where['list'],
							'key'=>$where['key'],
							'type'=> $type,
							'value'=>$value,
							'description'=>''
					);
					$retVal = $this->insert($data);
				}
			}
		}
		return !!$retVal;
	}
}