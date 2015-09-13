define(function (require, exports, module) {
    var $ = require('jquery');
    require("selectByM")($);
    require("placeholder")($);
    require("validForm")($);
    require("colorpicker")($);
    require("dragsort")($);
    require('json2');
    var navigators = require("navigatortest");  //浏览器版本检测
    var ajax = require("Ajax");
    var template = require("artTemp");
    
    
    
    $(".close-risk-btn").click(function(){
    	$(".close-risk-box").css({
    		"height": 500 + 'px',
    		"overflow-y": 'auto'
    	});
    	var dialogHtml = $("#hide-close-risk").html();
    	window.parent.mask.noDialogShowModle('如何关闭保护',dialogHtml);
    	
    	
    });
    

	/**
	 * 菜单渲染
	 * 
	 */
	var menuRender = function(){
		var jsonString = $("#wx_menu").val();
		var jsonString_sys = $("#wx_menu_sys").val();
		var wechat_menu;
		if(jsonString == "[]" || jsonString == "" || jsonString == undefined){
			wechat_menu = eval(jsonString_sys);
		}else{
			wechat_menu = eval(jsonString);
		}
		if(wechat_menu != '' && wechat_menu != undefined){
			$.each(wechat_menu, function(k, v) {
				var cl, num;
				if(k == 0){
					cl = 'msl-con-one';
					num = 1;
				}else if(k == 1){
					cl = 'msl-con-two';
					num = 2;
				}else if(k == 2){
					cl = 'msl-con-three';
					num = 3;
				}
				v.cl = cl;
				v.num = num;
				if(v.sub_button != '' && v.sub_button != undefined){
					$.each(v.sub_button, function(num, obj) { 
						$.each(obj, function(key, val) {
							var wx_url = val.url;
							var menu_type, menu_two_type;
							if(wx_url != '' && wx_url != undefined){
								var vars = [], hash;
							    var q = wx_url.split('?')[1];
							    var a = wx_url.split('?')[0];
							    
							    if(q == undefined){
							    	menu_type = a;
							    }else{
							    	q = q.split('#');
						    		for(var i = 0; i < q.length; i++){
							           hash = q[i].split('=');
							           vars.push(hash[1]);
							           vars[hash[0]] = hash[1];
							        }
						    		menu_type = vars['menu_type'];
						    		if(menu_type != '' && menu_type != undefined){
										if(menu_type.length > 1){
											menu_type = menu_type.substr(0,1);
										}
									}
							    }
							    val.menu_type = menu_type;
							    //132
							    if(menu_type == 1 || menu_type == 2 || menu_type == 3){
									menu_two_type = 1;
								}else{
									menu_two_type = 2;
								}
								val.menu_two_type = menu_two_type;
							};
						});
					});
				}
			});
			var wx_data = {
				"data": wechat_menu
			};
			var html = template("mode", wx_data);
			document.getElementById('msl-content').innerHTML = html;
			
		}
		
		
	};
	
	
	
	//判断是否进菜单页面
	var app_id = $("input[name = 'app_id']").val();
	var secret = $("input[name = 'secret']").val();
	if(app_id != '' && app_id != undefined && secret != '' && secret != undefined){
		$(".authority-set").hide().siblings(".menu-set").show();
		$(".nav-authority").removeClass("current").siblings(".nav-menu").addClass("current");
		if($(".nav-menu").hasClass("current")){
			menuRender();
		}
	}
	
	//判断是否进消息模板页面
	var isMessageMode = function(){
		var url = window.parent.mask.returnUrl();
		
		var vars = [], hash;
	    var q = url.split('?')[1];
	    if(q != undefined){
	       q = q.split('&');
	       for(var i = 0; i < q.length; i++){
	           hash = q[i].split('=');
	           vars.push(hash[1]);
	           vars[hash[0]] = hash[1];
	       }
		}
		var status = vars['status'];
		if(status == 1){
			$(".menu-set").hide().siblings(".mode-set").show();
			$(".nav-menu").removeClass("current").siblings(".nav-mode").addClass("current");
		}
	};
	isMessageMode();
	
	
	//判断是否进入菜单设置
	if($(".menu-set .empty").length > 0){
		$(".menu-set .empty").siblings().hide();
	}
	//判断是否进入消息模板设置
	if($(".mode-set .empty").length > 0){
		$(".mode-set .empty").siblings().hide();
	}
		
	
	//调用下拉框JS
	$(".selectByM").each(function () {
	    $(this).selectObjM();
	});
	
	
	/**
	 * 动态刷新验证码
	 * 
	 */
	var code = $(".main-login-code img").attr("src");
	$(".main-login-code").off("click").on("click", function () {
	    $(this).find('img').attr("src", code + "&_math=" + Math.random());
	});


	
	
	
	/**
	 * 扫描二维码
	 * 
	 */
	var wechatEvent = {
		init:function(){
			var that = this;
			that.sechatClick();
			if($('input[class=scan_code]').size()) that.wechatScan();
		},
		timing: null,
		sechatClick: function(){
			var that=this;
			//手动设置
			$('.switch').click(function () {
			    $('.code-set').fadeOut(400);
			    $('.manual-set').fadeIn(400);
			    clearInterval(that.timing);
			});
			
			//扫二维码设置
			$('.sub-code').click(function () {
			    $('.code-set').fadeIn(400);
			    $('.manual-set').fadeOut(400);
			    that.wechatScan();
			});
		},
		wechatScan: function(){
			var that=this;
			if($(".nav-menu").hasClass('current')){
				return false;
			}
			if(that.timing != null){
				clearInterval(that.timing);
			}
			that.timing=setInterval(function(){
				var url = $(".scan_code").val() + "&" + Math.random();
				if(url != '' && url != undefined){
					var type = "GET";
					ajax. doAjax(type,url,"",function(data){
						if(data.status == 2){
							var src = window.parent.mask.returnUrl();
							src += '&r=' + Math.random();
							$(window.parent.document.getElementById("opened_iframe")).attr('src', src);
						}
					});
				}
			},2000);
		},
	};
	wechatEvent.init();

	
	
	
	
	
	/**
	 * 点击登录按钮
	 * 
	 */
	$('#login').click(function () {
		var t = $(this);
		if(!t.hasClass("clicked")){
			t.addClass("clicked").text("登录中...");
			$.post(t.attr('dataurl'), t.parents('form').serialize(), function (json) {
				t.removeClass("clicked");
	            if(json.status == 1) {
	            	
					var src = window.parent.mask.returnUrl();
					src += '&r=' + Math.random();
					$(window.parent.document.getElementById("opened_iframe")).attr('src', src);
	            }else{
					window.parent.mask.noDialogShowModle('提示信息',json.message);
	                $(".main-login-code img").click();
	            }
	        }, 'json');
		}
	});
	
	
	
	//手动设置保存按钮
	/**
	 * 基本设置提交
	 * 
	 */
	var secretKeySubmit = {
		submitForm: function(){
			var a_id = $("input[name = 'app_id']").val();
			var a_key = $("input[name = 'secret']").val();
			var flat_id = $("input[name = 'flat_id']").val();
			var type = "POST";
			var data = {
				"flat_id": flat_id,
				"app_id": a_id,
				"secret": a_key
			};
			var url = $(".sub-btn").attr("dataurl");
			ajax.doAjax(type,url,data,secretKeySubmit.callback);
				
		},
		callback: function(json){
            if (json.status == '0') {
                window.parent.mask.noBtnDialogShowModle('提示信息',json.message);
                $(".main-login-code img").click();
            } else {
            	//刷新标签
            	window.parent.mask.noDialogShowModle('提示信息',json.message);
        		var src = window.parent.mask.returnUrl();
				src += '&r=' + Math.random();
				$(window.parent.document.getElementById("opened_iframe")).attr('src', src);
            }
		},
		checkUI: function(){
			$('.ms-fm').Validform({
				btnSubmit : ".sub-btn",
				showAllError : true,
				ignoreHidden : true,
				tiptype: function(msg,o,cssctl){
	                var objtip=o.obj.parents('.ms-fm-col').find(".check-error");
	                cssctl(objtip,o.type);
	                objtip.text(msg);
	            },
//	            datatype: {
//	            	"province":function(gets,obj,curform,regxp) {
//              		if(obj.attr("selectVal") == '' || obj.attr("selectVal") == 0){
//	                    	return '请选择省份';
//	                    }
//	                },
//	                "city":function(gets,obj,curform,regxp) {
//              		if(obj.attr("selectVal") == '' || obj.attr("selectVal") == 0){
//	                    	return '请选择城市';
//	                    }
//	                },
//	                "website":function(gets,obj,curform,regxp) {
//	                	var reg =  /^[0-9a-zA-Z]*$/g;
//              		if(gets == ''){
//	                    	return false;
//	                    }else if(reg.test(gets) == true){
//	                    	return true;
//	                    }else{
//	                    	return false;
//	                    }
//	                },
//	            },
	            callback: function(){
	            	secretKeySubmit.submitForm();
	            }
			});
		}
	}
	secretKeySubmit.checkUI();
	
	
	
	
//	$('.sub-btn').click(function () {
//	    var t = $(this);
//	    if(!t.hasClass("clicked")){
//	    	t.addClass("clicked").text("保存中...");
//	    	$.post(t.attr('dataurl'), t.parents('form').serialize(), function (json) {
//	    		t.removeClass("clicked").text("保存");
//	            if (json.status == '0') {
//	                window.parent.mask.noBtnDialogShowModle('提示信息',json.message);
//	                $(".main-login-code img").click();
//	            } else {
//	            	//刷新标签
//	            	window.parent.mask.noDialogShowModle('提示信息',json.message);
//	        		var src = window.parent.mask.returnUrl();
//					src += '&r=' + Math.random();
//					$(window.parent.document.getElementById("opened_iframe")).attr('src', src);
//	            }
//	        }, 'json');
//	    }
//	});
	
	
	
	
	/**
	 * 菜单、授权、模板TAB切换
	 * 
	 */
	$(".wechat-manage > .main > .nav > ul > li").off("click").on("click",function(){
		var that = $(this);
		if(!that.hasClass("current")){
			that.siblings().removeClass("current");
			that.parents(".nav").siblings().fadeOut(400);
			that.addClass("current");
			if(that.hasClass("nav-menu")){
				$(".menu-set").fadeIn(400);
			}else if(that.hasClass("nav-authority")){
				$(".authority-set").fadeIn(400);
			}else if(that.hasClass("nav-mode")){
				$(".mode-set").fadeIn(400);
			}
		}
	});
	
	

	/**
	 * 拖拽
	 * 
	 */
	var dragsortEvent = function(){
		$(".msl-con ul").dragsort({
		    dragSelector: ".menu-two",
		    dragEnd: function() { },
		    dragBetween: false,
		    placeHolderTemplate: "<li class='menu-two'></li>"
		});
	};
	dragsortEvent();
	
	
	/**
	 * 模板详情
	 * 
	 */
	var modeDetailEvent = function(){
		$(".mode-tab-detail").off("click").on("click",function(){
			$(".mode-set").fadeOut(400);
			$(".mode-detail").fadeIn(400);
			var type = "GET";
			var id = $(this).parents("tr").attr("id");
			var url = $(this).attr("dataurl") + '&id=' + id;
			ajax.doAjax(type,url,"",function(data){
				if(data.status == 1){
					var msg = data.data;
					$(".md-name span").text(msg.name);
					$(".md-type span").text(msg.template_name);
					$(".md-wechat-num span").text(msg.template_code);
					var _html = '';
					$.each(msg.data, function(k, v) {
						_html += '<li>';
						_html += '<span class="info-sn">'+ v.name +'</span>';
						_html += '<span class="info-sn">'+ v.key +'</span>';
						_html += '<span class="info-sn">'+ v.color +'</span>';
						_html += '</li>';
					});
					$(".mode-detail ul").append(_html);
				}
			});
		});
		$(".mode-detail-return").off("click").on("click",function(){
			$(".mode-set").fadeIn(400);
			$(".mode-detail").fadeOut(400);
		});
	};
	modeDetailEvent();
    
    /**
     * 模板启用/禁用
     * 
     */
    var modeUseEvent = function(){
    	$(".mode-tab-use").off("click").on("click",function(){
    		var that = $(this);
    		that.toggleClass("current");
    		var status = that.attr("status");
    		var id = $(this).parents("tr").attr("id");
    		var type = "GET";
			var url = $(this).attr("dataurl") + "&id=" + id + "&status=" + status;
			console.log(url);
			ajax.doAjax(type,url,"",function(data){
				if(data.status == 1){
					if(that.hasClass("current")){
						that.text("禁用").attr("status",0).parent().siblings(".use-status").text("启用");
					}else{
						that.text("启用").attr("status",1).parent().siblings(".use-status").text("未启用");
					}
					
					window.parent.mask.noDialogShowModle('提示信息',data.message);
//					var src = window.parent.mask.returnUrl();
//					src += '&r=' + Math.random();
//					$(window.parent.document.getElementById("opened_iframe")).attr('src', src);
				}else{
					window.parent.mask.noDialogShowModle()('提示信息',data.message);
				}
			});
    	});
    };
    modeUseEvent();
    
    
    /**
     * 模板删除
     * 
     */
    var modeDelEvent = function(){
    	$(".mode-tab-del").off("click").on("click",function(){
    		var that = $(this);
    		var id = that.parents("tr").attr("id");
    		var type = "GET";
			var url = that.attr("dataurl") + "&id=" + id;
			ajax.doAjax(type,url,"",function(data){
				if(data.status == 1){
					that.parents("tr").remove();
					window.parent.mask.noDialogShowModle('提示信息',data.message);
				}
			});
    	});
    };
    modeDelEvent();
	
	
	
	        
    /**
     * 消息模板赋值
     * 
     */
//  $(".nav-mode a").off("click").on("click",function(){
//  	var type = "GET";
//  	var url = $(this).attr("datasrc");
//  	ajax.doAjax(type,url,"",function(data){
//  		if(data.status == 1){
//  			var msg = data.data;
//  			var _html;
//  			$.each(msg,function(k, v){
//  				var creat_time = UnixToDate(v.create_time);
//      			var sts;
//      			if(v.status == 0){
//      				sts = '未启用';
//      			}else if(v.status == 1){
//      				sts = '已启用';
//      			}
//  				_html += '<tr class="mode-tab-tr"' + " flat_id=" + v.flat_id
//					_html += '>'
//					_html += '<td class="mode-tab-name">' + v.name + '</td>',
//					_html += '<td class="mode-tab-type">' + v.template_name + '</td>',
//					_html += '<td class="mode-tab-time">' + creat_time + '</td>',
//					_html += '<td class="mode-tab-status">' + sts + '</td>';
//					_html += '<td class="mode-tab-five">';
//					_html += '<a href="javascript:;" class="mode-tab-detail">详情</a>';
//					_html += '<span>|</span>';
//					_html += '<a href="javascript:;" class="mode-tab-use">启用</a>';
//					_html += '<span>|</span>';
//					_html += '<a href="javascript:;" class="mode-tab-del">删除</a>';
//					_html += '</td>';
//					_html += '</tr>';
//  			});
//  			$(".mode-tab").append(_html);
//  			
//  			//详情
//		    	modeDetailEvent();
//  		}
//  	});
//  	
//  });
    
    /**
	 * 新增模板切换
	 * 
	 */
	$(".add-mode-btn .btn").off("click").on("click",function(){
		$(".mode-set").fadeOut(400);
		$(".add-mode").fadeIn(400);
		var type = "GET";
		var url = $(this).attr("dataurl");
		ajax.doAjax(type,url,"",function(data){
			if(data.status == 1){
				var msg = data.data;
    			var _html = '';
    			var ht = '';
    			$.each(msg,function(k, v){
    				_html += '<li selectVal = "'+ v.id +'">'+ v.name +'</li>';
    			});

    			$(".event-type ul").append(_html);
//  			//模板赋值(默认第一个)
//  			$(".event-type .selectByMT").attr("selectval",msg[0].id).val(msg[0].name);
//  			$(".wechat-mode-num").text(msg[0].template_code);
    			
    			//下拉切换赋值
    			$(".event-type").each(function(){
					var that = $(this);
					if(that.attr('hasevent','true')){
						that.selectObjM(1,function(val,input){
							$(".add-mode-tab tr").not(".amt-tr-first").remove();
							var ht = '';
							if(val == 1){
								$.each(msg[0].template_select,function(key,val){
									ht += '<tr class="amt-tr">';
				    				ht += '<td class="td-box"><div class="check-box"><label class="checked"><i class="choose ifont1" style="display: block;">&#xe60c;</i></label><input type="checkbox" value="" checked="checked" /><span></span></div></td>';
				    				ht += '<td><input type="text" class="ipt ipt-220 mode-platform" key="'+ key +'" value="'+ val +'" disabled="disabled" /></td>';
				    				ht += '<td><input type="text" class="ipt ipt-220 mode-key" datatype="wechatAttr" nullmsg="请输入微信模板属性" /></td>';
				    				ht += '<td><input type="text" class="ipt ipt-80 color-ipt" placeholder="选取颜色" datatype="wechatAttr" nullmsg="请选择字体颜色" /></td>';
				    				ht += '<td class="check-error check-error-ts"></td>';
				    				ht += '</tr>';
								});
				    			$(".add-mode-tab").append(ht);
							}else if(val == 2){
								$(".wechat-mode-num").text(msg[1].template_code);
								$.each(msg[1].template_select,function(key,val){
									ht += '<tr class="amt-tr">';
				    				ht += '<td class="td-box"><div class="check-box"><label class="checked"><i class="choose ifont1" style="display: block;">&#xe60c;</i></label><input type="checkbox" value="" checked="checked" /><span></span></div></td>';
				    				ht += '<td><input type="text" class="ipt ipt-220 mode-platform" key="'+ key +'" value="'+ val +'" disabled="disabled" /></td>';
				    				ht += '<td><input type="text" class="ipt ipt-220 mode-key" datatype="wechatAttr" nullmsg="请输入微信模板属性" /></td>';
				    				ht += '<td><input type="text" class="ipt ipt-80 color-ipt" placeholder="选取颜色" datatype="wechatAttr" nullmsg="请选择字体颜色" /></td>';
				    				ht += '<td class="check-error check-error-ts"></td>';
				    				ht += '</tr>';
								})
				    			$(".add-mode-tab").append(ht);
							}
							//调用复选框
							checkBoxEvent();
							//调用颜色选择
							$.each($(".color-ipt"),function(i, o){
								$(o).bigColorpicker("","L",5);
							});
							//调用拖拽
							dragsortEvent();
							
						});
					}else{
						$(this).selectObjM();
					}
				});
			}
		});
	});
	$(".add-mode-return").off("click").on("click",function(){
		$(".mode-set").fadeIn(400);
		$(".add-mode").fadeOut(400);
	});
        
        
	/**
	 * 复选框功能
	 * 
	 */
	var checkBoxEvent = function(){
		$(".check-box > label").off("click").on("click",function(){
			$(this).toggleClass("checked");
			if($(this).hasClass("checked")){
				$(this).children(".choose").show();
				$(this).next().attr("checked",true);
			}else{
				$(this).children(".choose").hide();
				$(this).next().removeAttr("checked");
			};
		});
	};
	
	
	/**
	 * 获取ID弹窗
	 * 
	 */
	var getIdEvent = function(){
		$(".get-id").off("click").on("click",function(){
			var selectval = $(".event-type .selectByMT").attr("selectval");
			if(selectval == 0){
				window.parent.mask.noDialogShowModle('提示信息','请选择平台事件类型');
			}else{
				var dialogHtml;
				if(selectval == 1){
					dialogHtml = $(".hide-img").html();
				}else if(selectval == 2){
					dialogHtml = $(".hide-imgs").html();
				}
				window.parent.mask.noDialogShowModle('获取id',dialogHtml);
			}
			
		});
	};
	getIdEvent();
	
	
	/**
	 * 新增模板表单提交
	 * 
	 */
	var addModeSubmit = {
		submitForm: function(){
			var mode_id = $("input[name = 'mode_id']").attr("selectVal");
			var mode_name = $("input[name = 'mode_name']").val();
			var wechat_mode_id = $("input[name = 'wechat_mode_id']").val();
//			var attribute = [];
			var atr = {};
			$.each($(".amt-tr"), function(i,o) {
				if($(o).find(".check-box label").hasClass("checked")){
					var _atr = {
						"key": $(o).find(".mode-key").val(),
						"color": $(o).find(".color-ipt").val(),
						"name": $(o).find(".mode-platform").val()
					}
					var atr_id = $(o).find(".mode-platform").attr("key");
//					alert(atr_id);
					atr[atr_id]=_atr;
//					attribute.push(atr);
				}
			});
			var type = "POST";
			var url = $(".mb-btn").attr("dataurl");
			var data = {
				"template_id": mode_id,
				"template_name": mode_name,
				"wx_template_id": wechat_mode_id,
				"attribute": atr
			}
			ajax.doAjax(type,url,data,addModeSubmit.callback);
			
		},
		callback: function(data){
			if(data.status == 1){
				window.parent.mask.noDialogShowModle('提示信息',data.message,"",$(".mode-set"),$(".add-mode"));
				var src = window.parent.mask.returnUrl();
				src += '&status=1&r=' + Math.random();
				$(window.parent.document.getElementById("opened_iframe")).attr('src', src);
			}else{
				window.parent.mask.noDialogShowModle('提示信息',data.message);
			}
		},
		checkUI: function(){
			$('.add-mode').Validform({
				btnSubmit : ".mb-btn",
				showAllError : true,
				ignoreHidden : true,
				tiptype: function(msg,o,cssctl){
	                var objtip=o.obj.parents("li, tr").find(".check-error");
	                cssctl(objtip,o.type);
	                objtip.text(msg);
	            },
	            datatype: {
	            	"modeID":function(gets,obj,curform,regxp) {
                		if(obj.attr("selectVal") == ''){
	                    	return '请选择平台事件类型';
	                    }
	                },
	                "wechatAttr":function(gets,obj,curform,regxp) {
	                	var obj_box = obj.parent().siblings(".td-box").find(".check-box label");
	                	if(obj_box.hasClass("checked")){
	                		if($.trim(gets) == ''){
		                    	return false;
		                    }
	                	}else{
	                		return true;
	                	}
                		
	                },
	            },
	            callback: function(){
//	            	var lab = $(".amt-tr").find(".check-box");
//	            	var mode_key, color;
//	            	$.each(lab, function(i, o) {    
//	            		if(lab.find("label").hasClass("checked")){
//	            			mode_key = $.trim($(o).parents(".amt-tr").find(".mode-key").val());
//		            		color = $.trim($(o).parents(".amt-tr").find(".color-ipt").val());
//	            		}
//	            	});
//	            	if(mode_key == '' || color == ''){
//          			window.parent.mask.noDialogShowModle('提示信息','请先完善信息(微信模板属性、字体颜色)');
//          		}
	            	addModeSubmit.submitForm();
            		
	            }
			});
		}
	};
	addModeSubmit.checkUI();
	
	
	/**
	 * 新增菜单 
	 * 
	 */
	var addMenu = function(){
		$(".add-menu").off("click").on("click",function(){
			$(".add-menu-con").html('');
			var menuHtml = $("#add-menu-mode").html();
			$(".add-menu-con").html(menuHtml);
			$(".menu-manage-btn").hide();
//			$(".menu-set-right").fadeIn(400);
			$(".msr-btn .menu-manage-btn").hide();
			$(".msr-title i").addClass("add").removeClass("edit");
			$(".msr-title span").text("增加菜单");
			var dtype_one = $(this).siblings("b").attr("datatype");
			var dtype_two = $(this).parent().siblings("b").attr("datatype");
			$("#hideVal").attr("hasevent","add");
			//再次点击值清空
//			$("#hideVal").val('');
//			$("input[name = 'menu_name']").val('');
//			$("input[name = 'menu_type']").val('').attr("selectval",'');
//			$("input[name = 'menu_business']").attr("selectval",'').val('');
//			$("input[name = 'menu_url']").val('');
			
			if(!$(this).parents(".menu-box").hasClass("menu-one")){
				$(".msr-con-title").find(".menu-mark").text("一级菜单可输入16个字符，中文占2个");
				$("#hideVal").val(dtype_one);
			}else{
				$(".msr-con-title").find(".menu-mark").text("二级菜单可输入16个字符，中文占2个");
//				$(".msr-btn .menu-manage-btn").show();
				$("#hideVal").val(dtype_two);
			}
			//下拉
			$(".selectByM").each(function(){
				var that = $(this);
				if(that.hasClass("menu-type")){
					that.selectObjM(1,function(val,input){
						$(".menu-type-business, .menu-type-url").show();
						if(val == 1){
							$(".menu-type-url").hide();
						}else if(val == 2){
							$(".menu-type-business").hide();
						}
					});
				}else{
					$(this).selectObjM();
				}
			});
			//调用新增菜单提交
			addMenuSubmit.checkUI();
		});
	};
	addMenu();
	
	
	/**
	 * 编辑菜单
	 * 
	 */
	var editMenu = function(){
		$(".edit-menu").off("click").on("click",function(){
			var that = $(this);
			$.each($(".msl-con .menu-box"), function(i, o) {     
				$(o).removeClass("cur");                                                       
			});
			that.parents(".menu-box").addClass("cur");
			$(".add-menu-con").html('');
			var menuHtml = $("#add-menu-mode").html();
			$(".add-menu-con").html(menuHtml);
//			$(".menu-set-right").fadeIn(400);
			$(".msr-title i").addClass("edit").removeClass("add");
			$(".msr-title span").text("编辑菜单");
			$(".msr-btn .menu-manage-btn").hide();
			var dtype_two = $(this).parent().siblings("b").attr("datatype");
			$("#hideVal").attr("hasevent","edit");
			$("#hideVal").val(dtype_two);
			if(!that.parents(".menu-box").hasClass("menu-one")){
				$(".msr-con-title").find(".menu-mark").text("二级菜单可输入16个字符，中文占2个");
				$(".msr-btn .menu-manage-btn").show();
			}else{
				$(".msr-con-title").find(".menu-mark").text("一级菜单可输入16个字符，中文占2个");
			}
			//根据是否有子菜单判断管理功能具备
			if(that.parents(".menu-box").siblings().length == 0){
				$(".msr-btn .menu-manage-btn").show();
			}
			//下拉
			$(".selectByM").each(function(){
				var that = $(this);
				if(that.hasClass("menu-type")){
					that.selectObjM(1,function(val,input){
						$(".menu-type-business, .menu-type-url").show();
						if(val == 1){
							$(".menu-type-url").hide();
						}else if(val == 2){
							$(".menu-type-business").hide();
						}
					});
				}else{
					$(this).selectObjM();
				}
			});
			//判断是否为一级菜单
			var li = that.parents(".menu-box");
			if(li.hasClass("menu-one") && li.siblings().length > 0){
				$(".add-menu-con > .menu-set-right > .msr-con > ul > li").not(".msr-con-title").remove();
			}
			
			//编辑赋值
			var menuname;
			if(li.hasClass("menu-one")){
				menuname = $.trim(li.text());
			}else{
				menuname = $.trim(li.find("em").text());
			}
			$("input[name = 'menu_name']").val(menuname);
			if(li.attr("menutype") != '' && li.attr("menutype") != undefined && li.attr("menuthree") != '' && li.attr("menuthree") != undefined){
				//取值
				var menutype = $.trim(li.attr("menutype"));
				var menutype_val;
				if(menutype == 1){
					menutype_val = '业务模块';
				}else{
					menutype_val = '外链';
				}
				var menuthree = $.trim(li.attr("menuthree"));
				var menuthree_val;
				if(menuthree == 1){
					menuthree_val = '精品房源';
				}else if(menuthree == 2){
					menuthree_val = '即将到期';
				}else if(menuthree == 3){
					menuthree_val = '品牌故事';
				}else{
					menuthree_val = menuthree;
				}
				//开始赋值
				$("input[name = 'menu_type']").val(menutype_val).attr("selectval",menutype);
				if(menuthree == 1 || menuthree == 2 || menuthree == 3){
					$(".menu-type-business").show();
					$(".menu-type-url").hide();
					$("input[name = 'menu_business']").val(menuthree_val).attr("selectval",menuthree);
				}else{
					$(".menu-type-url").show();
					$(".menu-type-business").hide();
					$("input[name = 'menu_url']").val(menuthree_val);
				}
			}
			//菜单管理
			menuManageEvent();
			//调用新增菜单提交
			addMenuSubmit.checkUI();
		});
	};
	editMenu();
	

	/**
	 * 菜单打开与关闭
	 * 
	 */
	$(".menu-status").off("click").on("click",function(){
		$(this).toggleClass("status-grey");
	});
	
	
	/**
	 * 菜单预览
	 * 
	 */
	var menuPreview = function(){
		$(".preview-btn").off("click").on("click",function(){
			var nav = []; 
			$.each($(".msl-con"), function(i, o) {
				var _nav_one = $(o).find(".menu-one b").text();
				var nav_two = [];
				var menu_two = $(o).find(".menu-two");
				if(menu_two != undefined && menu_two != ""){
					$.each(menu_two, function(key, val) {    
				        _nav_two = $(val).find("b em").text(); 
				        nav_two.push(_nav_two);
					});
				}
				var _nav = {
					"nav_one": _nav_one,
					"nav_two": nav_two
				};
				nav.push(_nav);
			});
			console.log(nav);
			
			var menu_preview = {
				"data": nav
			};
			var html = template("preview-mode", menu_preview);
			document.getElementById('menu-preview').innerHTML = html;
			
			
			
			
//			var _html = '';
//			$.each(nav, function(i, o) { 
//				_html += '<div class="mpn-nav">';
//				_html += '<div class="title">';
//				_html += '<i class="ifont2">&#xe605;</i>';
//				_html += '<b>'+ o.nav_one +'</b>';
//				_html += '</div>';
//				_html += '<ul>';
//				$.each(o.nav_two, function(k, v) { 
//					$(".mpn-nav ul").html("");
//					_html += '<li>'+ v +'</li>';
//				});
//				_html += '<i class="ifont2">&#xe606;</i>';
//				_html += '<span class="span-btn"></span>';
//				_html += '</ul>';
//				_html += '</div> ';                                                      
//			});
//			var mpv = $(".menu-preview-nav");
//			mpv.append(_html);
			
			var dialogHtml = $("#hide-menu-preview").html();
			window.parent.mask.noBtnDialogShowModle('提示信息',dialogHtml);
		});
	};
	menuPreview();
	
	/**
	 * 菜单管理
	 * 
	 */
	var menuManageEvent = function(){
		$(".menu-manage-btn").click(function(){
			var menu_business = $("input[name = 'menu_business']").attr("selectval");
			var menu_url = $("input[name = 'menu_url']").val();
			var src;
			if(menu_business != '' && menu_business != undefined){
				if(menu_business == 1 || menu_business == 2){
					src = window.parent.mask.houseUrl();
					src += '&r=' + Math.random();
					$(window.parent.document.getElementById("opened_iframe")).attr('src', src);
				}else if(menu_business && menu_business == 3){
					src = window.parent.mask.basicUrl();
					src += '&r=' + Math.random();
					$(window.parent.document.getElementById("opened_iframe")).attr('src', src);
				}
			}else if(menu_url != '' && menu_url != undefined){
				window.location.href="#"+menu_url;
			}
		});
	};
	
	
	
	
	/**
	 * 菜单表单提交
	 * 
	 */
	var addMenuSubmit = {
		submitForm: function(){
			var menu_name = $("input[name = 'menu_name']").val(),
				menu_type = $("input[name = 'menu_type']").attr("selectval"),
				menu_business = $("input[name = 'menu_business']").attr("selectval"),
				menu_url = $("input[name = 'menu_url']").val(),
				dtype = $("#hideVal").val(),
				hasevent = $("#hideVal").attr("hasevent");
			var menu_three;
			if(menu_url != ''){
				menu_three = menu_url;
			}else{
				menu_three = menu_business;
			}
			
			if($(".msr-title i").hasClass("add")){
				//一级菜单HTML
				var _html = '';
				_html += '<div class="msl-con msl-con-three">';
				_html += '<ul><div class="menu-one menu-box" menuType="'+ menu_type +'" menuThree="'+ menu_three +'">';
				_html += '<b class="fsb" datatype="3">'+ menu_name +'</b>';
				_html += '<div class="msl-operate">';
				_html += '<a href="javascript:;" class="add add-menu"></a>';
				_html += '<a href="javascript:;" class="edit edit-menu"></a>';
	//			_html += '<a href="javascript:;" class="status status-grey menu-status"></a>';
				_html += '</div></div></ul>';
				_html += '</div>';
				//二级菜单HTML
				var Html = '';
				Html += '<li class="menu-two menu-box" menuType="'+ menu_type +'" menuThree="'+ menu_three +'">';
				Html += '<b datatype="3">>&nbsp;&nbsp;<em>'+ menu_name +'</em></b>';
				Html += '<div class="msl-operate">';
				Html += '<a href="javascript:;" class="edit edit-menu"></a>';
	//			Html += '<a href="javascript:;" class="status menu-status"></a>';
				Html += '</div>';
				Html += '</li>';
				if(dtype != '' && dtype != undefined){
					if(dtype == 0){
						var len = $(".msl-con").length; 
						if(len >= 3){
							window.parent.mask.noDialogShowModle('提示信息','一级菜单最多添加3个');
						}else{
	//						if(hasevent == 'add'){
								$(".msl-con:last").after(_html);
	//						}else if(hasevent == 'edit'){
	//							alert(2);							
	//						}
						}
					}else if(dtype == 1){
						var one_len = $(".msl-con-one ul .menu-two").length;
						
						if(one_len >= 5){
							window.parent.mask.noDialogShowModle('提示信息','二级菜单最多添加5个');
						}else{
							if(hasevent == 'add'){
								//$(".msl-con-one ul li:last").after(Html);
								$(".msl-con-one ul").append(Html);
								
							}else if(hasevent == 'edit'){
								$.each($(".msl-con > ul > li"), function(i, o) { 
									if($(o).hasClass("cur")){
										if($(o).hasClass("menu-one")){
											$(o).find("b").text(menu_name);
										}else if($(o).hasClass("menu-two")){
											$(o).find("b > em").text(menu_name);
											$(o).attr({
												"menutype": menu_type,
												"menuthree": menu_three
											});
										}
										$(o).removeClass("cur");
									}
								});							
							}
							
						}
					}else if(dtype == 2){
						var two_len = $(".msl-con-two ul .menu-two").length;
						if(two_len >= 5){
							window.parent.mask.noDialogShowModle('提示信息','二级菜单最多添加5个');
						}else{
							if(hasevent == 'add'){
								$(".msl-con-two ul").append(Html);
							}else if(hasevent == 'edit'){
								$.each($(".msl-con > ul > li"), function(i, o) { 
									if($(o).hasClass("cur")){
										if($(o).hasClass("menu-one")){
											$(o).find("b").text(menu_name);
										}else if($(o).hasClass("menu-two")){
											$(o).find("b > em").text(menu_name);
											$(o).attr({
												"menutype": menu_type,
												"menuthree": menu_three
											});
										}
										$(o).removeClass("cur");
									}
								});								
							}
						}
					}else if(dtype == 3){
						var three_len = $(".msl-con-three ul .menu-two").length;
						if(three_len >= 5){
							window.parent.mask.noDialogShowModle('提示信息','二级菜单最多添加5个');
						}else{
							if(hasevent == 'add'){
								$(".msl-con-three ul").append(Html);
							}else if(hasevent == 'edit'){
								$.each($(".msl-con > ul > li"), function(i, o) { 
									if($(o).hasClass("cur")){
										if($(o).hasClass("menu-one")){
											$(o).find("b").text(menu_name);
										}else if($(o).hasClass("menu-two")){
											$(o).find("b > em").text(menu_name);
											$(o).attr({
												"menutype": menu_type,
												"menuthree": menu_three
											});
										}
										$(o).removeClass("cur");
									}
								});								
							}
						}
					}
				}
				//增加菜单页面隐藏
	//			$(".menu-set-right").hide();
				$(".add-menu-con").html('');
				//调用增加菜单
				addMenu();
				//调用编辑菜单
				editMenu();
				//调用菜单预览
				menuPreview();
			}else if($(".msr-title i").hasClass("edit")){
				var dom = $(".msl-con .cur");
				dom.find("b em").text(menu_name);
				dom.attr("menutype",menu_type);
				if(menu_type == 2){
					dom.attr("menuthree",menu_url);
				}else if(menu_type == 1){
					dom.attr("menuthree",menu_business);
				}
				if(dom.hasClass("menu-one")){
					dom.find("b").text(menu_name);
				}
				$(".add-menu-con").html('');
				//调用菜单预览
				menuPreview();
				
			}
			
		},
		checkUI: function(){
			$('.menu-set-right').Validform({
				btnSubmit : ".menu-btn",
				showAllError : true,
				ignoreHidden : true,
				tiptype: function(msg,o,cssctl){
	                var objtip=o.obj.parents('li').find(".check-error");
	                cssctl(objtip,o.type);
	                objtip.text(msg);
	            },
	            datatype: {
	            	"menuType":function(gets,obj,curform,regxp) {
                		if(obj.attr("selectVal") == ''){
	                    	return '请选择类型';
	                    }
	                },
	                "jump_url":function(gets,obj,curform,regxp) {
	                	var res = gets.indexOf("https://");
                		if(res == -1){
                			return false;
                		}
	                },
	            },
	            callback: function(){
            		addMenuSubmit.submitForm();
	            }
			});
		}
	};
	

	
	/**
	 * 菜单管理功能
	 * 
	 */
	$(".menu-manage-btn").off("click").on("click",function(){
		var url;
		window.location.href="#"+url;
	});
	
	
	/**
	 * 发布功能
	 * 
	 */
	$(".publish-btn, .reorder-btn").off("click").on("click",function(){
	    var bt = [];
	    $.each($(".msl-con"), function(k,v) {    
		    var _data = {};
		    _data.name = $(v).find(".menu-one > b").text();
//		    _data.mType = $(v).find(".menu-one").attr("menutype");
//		    _data.mthree = $(v).find(".menu-one").attr("menuthree");
		    _data.sub_button = [];
		    var nav_two = $(v).find(".menu-two");
		    if(nav_two.length == 0){
		    	_data.type = "view";
		    	_data.menu_type = $(v).find(".menu-one").attr("menuthree");
		    }else{
		    	$.each($(v).find(".menu-two"),function(i, o){
			    	var name = $.trim($(o).find("b em").text());
			    	var menu_type = $(o).attr("menutype");
	//		    	if(menu_type == 1){
	//		    		menu_type = '业务模块';
	//		    	}else if(menu_type == 2){
	//		    		menu_type = '外链'
	//		    	}
					
			    	var menu_three = $(o).attr("menuthree");
			    	var res = {
			    		"type": "view",
			    		"name": name
			    	}
			    	if(menu_three != 1 && menu_three != 2 && menu_three != 3){
			    		res.url = menu_three;
			    	}else{
			    		res.menu_type = menu_three;
			    	}
			    	_data.sub_button.push(res);
			    });
		    }
		    bt.push(_data);
	    });
	    var flat_id = $("#hide-falt-id").val();
	    var type = "POST";
	    var url = $(".publish-btn").attr("dataurl");
	    var data = {
	    	"flat_id": flat_id,
	    	"body": bt
	    };
	    ajax.doAjax(type,url,data,function(data){
	    	if(data.status == 1){
	    		window.parent.mask.noDialogShowModle('提示信息',data.message);
//	    		var src = window.parent.mask.returnUrl();
//				src += '&r=' + Math.random();
//				$(window.parent.document.getElementById("opened_iframe")).attr('src', src);
	    	}else{
	    		window.parent.mask.noDialogShowModle('提示信息',data.message);
	    	}
	    });
	    
	});
	
	
	
	
	
	/**              
	 * 时间戳转换日期              
	 * @param <int> unixTime    待时间戳(秒)              
	 * @param <bool> isFull    返回完整时间(Y-m-d 或者 Y-m-d H:i:s)              
	 * @param <int>  timeZone   时区              
	 */
	var UnixToDate = function(unixTime, isFull, timeZone) {
		if (typeof (timeZone) == 'number')
        {
            unixTime = parseInt(unixTime) + parseInt(timeZone) * 60 * 60;
        }
        var time = new Date(unixTime * 1000);
        var ymdhis = "";
        ymdhis += time.getUTCFullYear() + "-";
        ymdhis += (time.getUTCMonth()+1) < 10 ? "0"+(time.getUTCMonth()+1) + "-" : (time.getUTCMonth()+1) + "-";
        ymdhis += (time.getUTCDate()+1) < 10 ? "0"+(time.getUTCDate()+1) : (time.getUTCDate()+1);
        if (isFull === true)
        {
            ymdhis += " " + time.getUTCHours() + ":";
            ymdhis += time.getUTCMinutes() + ":";
            ymdhis += time.getUTCSeconds();
        }
        return ymdhis;
	};
        


});