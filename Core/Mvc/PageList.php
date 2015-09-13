<?php
namespace Core\Mvc;
class PageList {
	public $total; // 总记录
	public $pagesize; // 每页显示多少条
	public  $limit; // limit
	public $page; // 当前页码
	public  $pagenum; // 总页码
	public $url; // 地址
	public $bothnum; // 两边保持数字分页的量
	public $quercount;
	                  
	/**
	 * 构造方法初始化
	 * @param unknown_type $_total
	 * @param unknown_type $_pagesize
	 */
	public function __construct($_total, $_pagesize) {
		$this->total = $_total ? $_total : 1;
		$this->pagesize = $_pagesize;
		$this->pagenum = ceil ( $this->total / $this->pagesize );
		$this->page = $this->setPage ();
		$this->limit = array("offset"=>($this->page - 1) * $this->pagesize,"page_size"=>$this->pagesize);
		$this->url = $this->setUrl ();
		$this->bothnum = 2;
	}
	
	/**
	 * 拦截器
	 * @param unknown_type $_key
	 */
	public function __get($_key) {
		return $this->$_key;
	}
	
	/**
	 * 获取当前页码
	 * @return number|unknown
	 */
	private function setPage() {
		if (! empty ( $_REQUEST ['page'] )) {
			if ($_REQUEST ['page'] > 0) {
				if ($_REQUEST ['page'] > $this->pagenum) {
					return $this->pagenum;
				} else {
					return $_REQUEST ['page'];
				}
			} else {
				return 1;
			}
		} else {
			return 1;
		}
	}
	
	/**
	 * 获取地址
	 * @return Ambigous <string, unknown>
	 */
	private function setUrl() {
		$_url = $_SERVER ["REQUEST_URI"];
		$_par = parse_url ( $_url );
		if (isset ( $_par ['query'] )) {
			parse_str ( $_par ['query'], $_query );
			unset ( $_query ['page'] );
			if (count(explode('&', $_par['query']))>0)
			{
				$this->quercount=count(explode('&', $_par['query']));
				$_url = $_par ['path'] . '?' . http_build_query ( $_query );
			}else 
			{
				$_url = $_par ['path'] . '' . http_build_query ( $_query );
			}
		}
		return $_url;
	} 
	/**
	 * 数字目录
	 * @return string
	 */
	private function pageList() {
		$_symbol="?";
		if ($this->quercount)
		{
			$_symbol="&";
		}
		$_pagelist='';
		$_pagelist.=$this->first();
		$_pagelist.=$this->prev ();
		for($i = $this->bothnum; $i >= 1; $i --) {
			$_page = $this->page - $i;
			if ($_page < 1)
				continue;
			$_pagelist .= '<a href="' . $this->url . $_symbol.'page=' . $_page . '">' . $_page . '</a>';
		}
		$_pagelist .= ' <a class="current">' . $this->page . '</a>';
		for($i = 1; $i <= $this->bothnum; $i ++) {
			$_page = $this->page + $i;
			if ($_page > $this->pagenum)
				break;
			$_pagelist .= '<a href="' . $this->url . $_symbol.'page=' . $_page . '">' . $_page . '</a>';
		}
		$_pagelist.=$this->next();
		$_pagelist.=$this->last();
		return $_pagelist;
	}
	
	/**
	 * 首页
	 * @return string
	 */ 
	private function first() {
		if ($this->page > $this->bothnum + 1) {
			return '<a href="' . $this->url . '">首页</a>';
		}
	}
	
	/**
	 * 上一页
	 * @return string
	 */
	private function prev() {
		$_symbol="?";
		if ($this->quercount)
		{
			$_symbol="&";
		}
		if ($this->page == 1) {
			return '<a class="ifont page-ps">㑥</a>';
		}
		return '<a class="ifont page-ps" href="' . $this->url . $_symbol.'page=' . ($this->page - 1) . '">㑥</a>';
	}
	
	/**
	 * 下一页
	 * @return string
	 */
	private function next() {
		$_symbol="?";
		if ($this->quercount)
		{
			$_symbol="&";
		}
		if ($this->page == $this->pagenum) {
			return '<a class="ifont page-ns">㑤</a>';
		}
		return '<a class="ifont page-ns" href="' . $this->url . $_symbol.'page=' . ($this->page + 1) . '">㑤</a>';
	}
	
	/**
	 * 尾页
	 * @return string
	 */
	private function last() {
		$_symbol="?";
		if ($this->quercount)
		{
			$_symbol="&";
		}
		if ($this->pagenum - $this->page > $this->bothnum) {
			return '<a href="' . $this->url . $_symbol.'page=' . $this->pagenum . '">' . "(".$this->pagenum . ')</a>';
		}
	}
	/**
	 * 取得跳转
	 * @author lishengyou
	 * 最后修改时间 2014年12月9日 下午8:22:03
	 *
	 * @return string
	 */
	public function getJumpIpt(){
		return '<span class="pager-txt f-ari">跳转到</span>
                <input type="text" class="pager-ipt placeholder" data="页码" style="color: rgb(187, 187, 187);">
                <a href="#">GO</a>';
	}
	/**
	 * 分页信息
	 * @return string
	 */
	public function showpage() {
		if($this->pagenum == 1) return '';
		$_page='';
		//$_page .= $this->first ();
		$_page .= $this->pageList ();
		//$_page .= $this->last ();
		//$_page .= $this->prev ();
		//$_page .= $this->next ();
		$_page .= $this->getJumpIpt();
		return $_page;
	}
}