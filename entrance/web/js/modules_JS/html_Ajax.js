define(function(require, exports) {   
	var $ = jQuery = require('jquery');
	window.$ = $;
	require("pagination");
	var  dialog = require("dialog"); //弹窗插件
	var doAjaxBufferQueue = {};
	var doAjaxBufferQueueTime = {};
	
	var doAjaxResultHook = function(json){//系统状态处理
		if(typeof json['__status__'] != 'undefined'){//有系统状态要处理
			switch(json.__status__){
				case 301://跳转
					if(typeof json['__message__'] == 'string'){
						if($('.ui-popup[sysstatus=301]').length > 0){
							return false;
						}
						var dd_auto = dialog({
							title:'提示信息',
							content:json.__message__,
							okValue:'确 定',
							ok:function(){
								dd_auto.close();
								window.location = json.__url__;
							},
							cancelValue:'取消',
							cancel:function(){
								dd_auto.close();
								window.location = json.__url__;
							}
						});
						$(dd_auto.node).find('.ui-dialog-button button[i-id="cancel"]').remove();
						$(dd_auto.node).attr('sysstatus',301);
						dd_auto.showModal();
					}else{
						window.location = json.__url__;
					}
					return false;
				break;
				case 302://跳转
					if(typeof json['__message__'] == 'string'){
						var dd_auto = dialog({
							title:'提示信息',
							content:json.__message__,
							okValue:'确 定',
							ok:function(){
								dd_auto.close();
								WindowTag.openTag(json.__url__);
								if(typeof json.__closetag__ == 'string'){//关闭他
									WindowTag.closeTag(json.__closetag__);
								}
							},
							cancelValue:'取消',
							cancel:function(){
								dd_auto.close();
								WindowTag.openTag(json.__url__);
								if(typeof json.__closetag__ == 'string'){//关闭他
									WindowTag.closeTag(json.__closetag__);
								}
							}
						});
						$(dd_auto.node).find('.ui-dialog-button button[i-id="cancel"]').remove();
						dd_auto.showModal();
					}else{
						WindowTag.openTag(json.__url__);
						if(typeof json.__closetag__ == 'string'){//关闭他
							WindowTag.closeTag(json.__closetag__);
						}
					}
					return false;
				break;
				case 403://没有权限
					var dd_auto = dialog({
						title:'提示信息',
						content:'您没有权限执行当前操作！',
						okValue:'确 定',
						ok:function(){
							dd_auto.close();
							if(typeof json.__closetag__ == 'string'){//关闭他
								WindowTag.closeTag(json.__closetag__);
							}
						},
						cancelValue:'取消',
						cancel:function(){
							dd_auto.close();
							if(typeof json.__closetag__ == 'string'){//关闭他
								WindowTag.closeTag(json.__closetag__);
							}
						}
					});
					$(dd_auto.node).find('.ui-dialog-button button[i-id="cancel"]').remove();
					dd_auto.showModal();
					return false;
				break;
				case 500.13://服务器错误
					var dd_auto = dialog({
						title:'提示信息',
						content:json.__message__ || '服务器太忙',
						okValue:'确 定',
						ok:function(){
							dd_auto.close();
						},
						cancelValue:'取消',
						cancel:function(){
							dd_auto.close();
						}
					});
					$(dd_auto.node).find('.ui-dialog-button button[i-id="cancel"]').remove();
					dd_auto.showModal();
					return false;
				break;
				case 500.131:
					return false;
				break;
			}
		}
		return true;
	}
	
    // 对外提供doSomething方法   typestr数据传输方式"get"/"post" urlstr地址 datastr数据 callback回调函数
    exports.doAjax = function(typestr,urlstr,datastr,callback) {
    	if(typeof urlstr == 'string'){
    		urlstr = urlstr.indexOf('?') < 0 ? urlstr+'?HTTP_X_REQUESTED_WITH=xmlhttprequest' : urlstr+'&HTTP_X_REQUESTED_WITH=xmlhttprequest';
    	}
    	var buffer_queue = arguments.length > 4 ? arguments[4] : false;
    	sys_callback = typeof callback == 'object' && typeof callback[1] == 'function' ? callback[1] : function(){};
    	callback = typeof callback == 'object' && typeof callback[0] == 'function' ? callback[0] : (typeof callback == 'function' ? callback : function(){});
    	
    	var ajax = function(){
    		var AJAX = $.ajax({
        		type:typestr,
        		url:urlstr,
        		data:datastr,
        		dataType:"json",
        		success: function(json){
        			if(buffer_queue != false && typeof doAjaxBufferQueue[buffer_queue] != 'undefined' && doAjaxBufferQueue[buffer_queue].isabort){
        				return true;
        			}
        			if(json!="" && json!=null & json!=undefined){
        				if(typeof json['__msg__'] != 'undefined'){//系统消息
        					var total = json.__msg__.total;
        					
        					var _parent = undefined;
        					if (self != top) _parent = window.parent.document;
        					
        					var newsNum = $('body .head:first .news-num-i', _parent);
        					newsNum[0].className = 'news-num-i';
        					if(total == 0){
        						newsNum.html('0').hide();
        					}else if(total < 10){
        						newsNum.addClass('news-num');
        						newsNum.html(total).show();
        					}else if(total < 100){
        						newsNum.addClass('news-num1');
        						newsNum.html(total).show();
        					}else{
        						newsNum.addClass('news-num2');
        						newsNum.html('99+').show();
        					}
        				}
        				if(doAjaxResultHook(json)){
        					callback(json);
        				}else{
        					sys_callback(json);
        				}
        			}else{
        				callback({status:0,data:'发生错误',message:'发生错误',msg:'发生错误'});
        			}
        		},
        		error:function(e){
        			if(buffer_queue == false || typeof doAjaxBufferQueue[buffer_queue] == 'undefined' || doAjaxBufferQueue[buffer_queue].isabort == false){
        				callback({status:0,request:e,data:'发生错误',message:'发生错误',msg:'发生错误'});
        			}
        		}
        	});
    		AJAX.isabort = false;
    		return AJAX;
    	};
    	if(buffer_queue !== false){//开启缓存检测
    		var doAjaxBufferQueueCallback = arguments.length > 5 ? arguments[5] : function(){};
    		if(typeof doAjaxBufferQueue[buffer_queue] == 'object'){//有任务正在进行
    			doAjaxBufferQueue[buffer_queue].isabort = true;
    			doAjaxBufferQueue[buffer_queue].abort();
    			delete doAjaxBufferQueue[buffer_queue];
    		}
    		var bufferajax = function(){
    			var AJAX = ajax();
    			doAjaxBufferQueue[buffer_queue] = AJAX;
    			doAjaxBufferQueueCallback(AJAX);
    		};
    		if(typeof doAjaxBufferQueueTime[buffer_queue] != 'undefined'){//有任务在等待执行,这是在连续操作啊
    			clearTimeout(doAjaxBufferQueueTime[buffer_queue]);
    			doAjaxBufferQueueTime[buffer_queue] = setTimeout(function(){
    				delete doAjaxBufferQueueTime[buffer_queue];
    				bufferajax();
    			},300);
    		}else{//轻轻松松的执行
    			bufferajax();
    			doAjaxBufferQueueTime[buffer_queue] = setTimeout(function(){delete doAjaxBufferQueueTime[buffer_queue];},300);//不能删除，暂时还没想到更好的办法
    		}
		}else{
			return ajax();
		}
    };
    /*
     * 功能：初始化翻页插件
     * 参数：
     * id：分页插件的父容器id
     * t：总共的条数
     * id:翻译插件绑定id
     * func:翻页回调函数
     */
    exports.iniPagination=function(t,id,func,size,cpage){
        if(!!!func){
            func=function(){};
        }
        if(!!!size){
            size=10;
        }
        if(!!!cpage){
       	 	cpage=0;
        }
        $(id).pagination(t, {
            callback: func,
            prev_text: "<",
            next_text: ">",
            items_per_page:size,
            num_display_entries:9,
            current_page:cpage,
            num_edge_entries:1,
            link_to:"javascript:;"
        });
    }
}); 