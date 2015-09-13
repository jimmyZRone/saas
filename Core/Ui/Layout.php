<?php
namespace Core\Ui;
/**
 * 布局管理
 * @author lishengyou
 * 最后修改时间 2015年3月3日 下午3:12:21
 *
 */
class Layout{
	protected $_singlelabel = array('img','input');
	protected $_mapping = array();
	/**
	 * 设置
	 * @author lishengyou
	 * 最后修改时间 2015年3月4日 上午11:31:51
	 *
	 * @param unknown $key
	 * @param string $mapping
	 */
	public function setMapping($key,$mapping=null){
		if(is_array($key)){
			$this->_mapping = array_merge($this->_mapping,$key);
		}else if($mapping){
			$this->_mapping[$key] = $mapping;
		}else if(isset($this->_mapping[$key])){
			unset($this->_mapping[$key]);
		}
		return $this;
	}
	/**
	 * 取得
	 * @author lishengyou
	 * 最后修改时间 2015年3月4日 上午11:35:14
	 *
	 * @return multitype:string
	 */
	public function getMapping(){
		return $this->_mapping;
	}
	/**
	 * 解析
	 * @author lishengyou
	 * 最后修改时间 2015年3月3日 下午3:17:17
	 *
	 * @param \SimpleXMLElement $xml
	 */
	protected function parsexml(\DOMNodeList $node){
		if($node->length == 1){
			//有多个借点
			return $this->parsenode($node->item(0));
		}else{
			$nullNode = new \Core\Ui\NullNode();
			for($i = 0;$i<$node->length;$i++){
				$nullNode->appendChild($this->parsenode($node->item($i)));
			}
			return $nullNode;
		}
	}
	/**
	 * 解析单个借点
	 * @author lishengyou
	 * 最后修改时间 2015年3月3日 下午6:43:14
	 *
	 * @param \DOMNode $node
	 * @return Ambigous <\Core\Ui\DoubleLabel, NULL, unknown>
	 */
	protected function parsenode(\DOMNode $node){
		$ui = null;
		if($node instanceof \DOMText){
			//文本节点
			$ui = new \Core\Ui\Text();
			$ui->innerHTML($node->wholeText);
		}else{
			$tag = strtolower($node->nodeName);
			$ui = null;
			if(isset($this->_mapping[$tag])){
				$ui = new $this->_mapping[$tag]();
			}elseif(in_array($tag, $this->_singlelabel) && !$node->hasChildNodes()){
				//单标签
				$ui = new \Core\Ui\UnknownSLabel($tag);
			}else{
				$ui = new \Core\Ui\UnknownDLabel($tag);
			}
			$attributes = $node->attributes;
			if($attributes && $attributes->length > 0){
				foreach ($attributes as $attrnode){
					$ui->setAttribute($attrnode->name, $attrnode->value);
				}
			}
			//设置UID
			if($ui->getAttribute('id') || $ui->getAttribute('layout_id')){
				$ui->setUid($ui->getAttribute('layout_id') ? $ui->getAttribute('layout_id') : $ui->getAttribute('id'));
				$ui->removeAttribute('layout_id');
			}
			if($ui instanceof \Core\Ui\DoubleLabel && $node->hasChildNodes()){
				if($node->childNodes->length > 0){
					$nodes = $this->parsexml($node->childNodes);
					if($nodes instanceof \Core\Ui\NullNode){
						$nodes = $nodes->getChilds();
						foreach ($nodes as $_node){
							$ui->appendChild($_node);
						}
					}else{
						$ui->appendChild($nodes);
					}
				}
			}
		}
		return $ui;
	}
	/**
	 * 加载从文件
	 * @author lishengyou
	 * 最后修改时间 2015年3月3日 下午3:14:17
	 *
	 * @param unknown $filename
	 * @param boolean $fulltext
	 * @return NULL
	 */
	public function loadByFile($filename,array $data = array()){
		if(!is_file($filename)){
			return null;
		}
		$xml = new \DOMDocument("1.0", "UTF-8");
		try{
			$content = '';
			if($data){
				$template = $this->newtemplate($data);
				$template->setTemplateDir(dirname($filename));
				$content = $template->fetch(basename($filename));
			}else{
				$content = file_get_contents($filename);
			}
			$content = str_replace('&', '&amp;', $content);
			$xml->loadXML('<layout filename="'.$filename.'">'.$content.'</layout>');
		}catch (\Exception $ex){
			return null;
		}
		return $this->parsexml($xml->childNodes->item(0)->childNodes);
	}
	/**
	 * 创建模版
	 * @author lishengyou
	 * 最后修改时间 2015年3月18日 下午3:03:21
	 *
	 * @param array $data
	 * @return \Core\Mvc\Template
	 */
	protected function newtemplate(array $data = array()){
		$template = new \Core\Mvc\Template();
		foreach ($data as $key => $value){
			$template->assign($key, $value);
		}
		return $template;
	}
	/**
	 * 解析字符串
	 * @author lishengyou
	 * 最后修改时间 2015年3月4日 上午10:40:29
	 *
	 * @param string $string
	 * @return NULL|Ambigous <\Core\Ui\Ambigous, \Core\Ui\NullNode>
	 */
	public function load($string){
		$xml = new \DOMDocument("1.0", "UTF-8");
		try{
			$xml->loadXML('<layout>'.$string.'</layout>');
		}catch (\Exception $ex){
			return null;
		}
		return $this->parsexml($xml->childNodes->item(0)->childNodes);
	}
}