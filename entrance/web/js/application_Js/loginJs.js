define(function(require){
//	if(top.window != window){
//		try{
//			if(top.window.document.URL.indexOf(document.URL.split('?')[0] + "?c=user-") != 0){
//				top.window.location = document.URL;
//				return false;
//			}
//		}catch(e){
//			top.window.location = document.URL;
//		}
//	}
	var $ = require('jquery');
		require("placeholder")($);
		require("validForm")($);
		require("radio")($);
	var  dialog=require("dialog"),
	     ajax=require("Ajax");
	var navigators = require("navigatortest");  //浏览器版本检测
	var urlHelper = require("url");
	
	//针对IE10以下的input提示语兼容
	if(sys.ie && sys.ie < 10){
		$(".login").placeholder();
	};
	
	//调用单选框
	$('.radio').each(function(){
		$(this).click(function(){
			$(this).Radios();
		})
	});
	$(".radio-box > span").off("click").on("click",function(){
		var that = $(this);
		that.siblings("label").addClass("checked").parent().siblings('.radio-box').find('label').removeClass('checked');
		that.siblings("label").find(".r-default").hide().parents(".radio-box").siblings(".radio-box").find(".r-default").show();
		that.siblings("label").find(".r-select").show().parents(".radio-box").siblings(".radio-box").find(".r-select").hide();
		that.siblings("input").attr("checked",true).parents(".radio-box").siblings(".radio-box").find("input").attr("checked",false);
	});
	
	//针对IE6 背景设置及LOGO PNG图片兼容
	if(sys.ie && sys.ie < 7){
		$('.logo').children("img").attr("src","{{$smarty.const.APP_WEB_STATIC_URL}}images/logo-big.gif");
		var w = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
		var h = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
		$('#body-mask').css({
			width:w,
			height:h
		});
	};
	
	/**
	 * 登录页面二维码效果
	 * 
	 */
	$(".fm-edition > ul > li").hover(function(){
		$(this).find('img').show();
	},function(){
		$(this).find('img').hide();
	});
	
	
	//测试
	$(function(){
		var _par=$(".accountTxt");
		$.each(_par,function(i,o){
			$(o).focus(function(){
				var _nxt=$(this).next().find(".ermsg-tip");
				if(_nxt.find(".Validform_right").length>0 || _nxt.find(".Validform_wrong").length>0){
					_nxt.hide();
				}
			}).blur(function(){
				var _nxt=$(this).next().find(".ermsg-tip");
				if(_nxt.find(".Validform_error").length>0 || _nxt.find(".Validform_wrong").length>0){
					_nxt.show();
				}
			});
		});
		$(".login_form").Validform({
			tiptype : function(msg,o,cssctl){
                 var objtip=o.obj.parents(".fm-col").find(".msgTxt");
                cssctl(objtip,o.type);
                objtip.text(msg);
                var _par=objtip.parent(),
                		_top=0;
                if(objtip.hasClass("Validform_right")){
	               _par.css(
	               		{
	               			"display":"none"
	               		}
	               	);
                }else{
	               _par.css(
	               		{
	               			"display":"inline-block",
	               			"top":_top+"px",
	               			"left":"345px",
	               			"width":"140px"
	               		}
	               	);
                }
            },
			callback:function(form){
				var btn=$(".login_btn");
				var dialogCase = null;
				if(!btn.hasClass("clicked")){
					if(btn.val() != '登录'){
						return false;
					}
					btn.addClass("clicked").val("登录中...");
					var url = form.attr('action');
					var data = form.serialize();
					var callback = [function(json){
						btn.removeClass("clicked").val("登录");
						if(json.status == 1){
							window.location = json.url;
						}else{
							//兼容ERP
							if(dialogCase != null){
								dialogCase.close();
								dialogCase = null;
							}
							if(typeof window.login_error_hook == 'function' && window.login_error_hook(json,dialog)){
								return false;
							}
							dialogCase=dialog({
								title: '提示',
								content:json.message,
								okValue: '确认',
								ok: function () {
									dialogCase.close();
									dialogCase = null;
								}
							});
							dialogCase.showModal();
						}
					},function(json){
						btn.removeClass("clicked").val("登录");
						if(typeof json.__message__ == 'string' && dialogCase == null){
							dialogCase=dialog({
								title: '提示',
								content:json.__message__,
								okValue: '确认',
								ok: function () {
									dialogCase.close();
									dialogCase = null;
								}
							});
							dialogCase.showModal();
						}
					}];
					ajax.doAjax("POST",url,data,callback);
				}
			}
		});
	});
	
//	//注册弹窗
//	$('.register-btn').click(function(){
//		var ups = $('.pu-ups');
//		var shade = $('.pu-shade');
//		var form = $('.pu-form');
//		var prompt = $('.pu-prompt');
//		if(form.css('display') == 'block'){
//			ups.hide();
//			shade.hide();
//			form.hide();
//			prompt.hide();
//		}else{
//			ups.show();
//			shade.show();
//			form.show();
//			prompt.hide();
//			form.find('.pu-con-close').off('click').on('click',function(){
//				ups.hide();
//				shade.hide();
//				form.hide();
//				prompt.hide();
//			});
//			var ajax_callback = function(){
//				var flat_name = form.find('input[name=flat_name]');
//				var type = form.find('input[name=type]:checked');
//				var city_name = form.find('input[name=city_name]');
//				var contacts_name = form.find('input[name=contacts_name]');
//				var contacts_phone = form.find('input[name=contacts_phone]');
//				if(city_name.val() == ''){
//					var d=dialog({
//						title: '提示',
//						content:'请填写城市名称',
//						okValue: '确认',
//						ok: function () {
//							d.close();
//							city_name.focus();
//						}
//					});
//					d.showModal();
//					return false;
//				}
//				if(contacts_name.val() == ''){
//					var d=dialog({
//						title: '提示',
//						content:'请填写联系人',
//						okValue: '确认',
//						ok: function () {
//							d.close();
//							contacts_name.focus();
//						}
//					});
//					d.showModal();
//					return false;
//				}
//				if(contacts_phone.val() == ''){
//					var d=dialog({
//						title: '提示',
//						content:'请填写联系电话',
//						okValue: '确认',
//						ok: function () {
//							d.close();
//							contacts_phone.focus();
//						}
//					});
//					d.showModal();
//					return false;
//				}
//				var data = {flat_name:flat_name.val(),type:type.val(),city_name:city_name.val(),contacts_name:contacts_name.val(),contacts_phone:contacts_phone.val()};
//				form.find('.pu-con-btn .btn2').off('click');
//				$.post(urlHelper.make('user-register/beta'),data,function(json){
//					form.find('.pu-con-btn .btn2').off('click').on('click',ajax_callback);
//					if(json.status == 1){
//						ups.show();
//						shade.show();
//						form.hide();
//						prompt.show();
//						prompt.find('.pu-con-close').off('click').on('click',function(){
//							ups.hide();
//							shade.hide();
//							form.hide();
//							prompt.hide();
//						});
//					}else{
//						var d=dialog({
//							title: '提示',
//							content:json.message,
//							okValue: '确认',
//							ok: function () {
//								d.close();
//							}
//						});
//						d.showModal();
//					}
//				},'json').error(function(){
//					form.find('.pu-con-btn .btn2').off('click').on('click',ajax_callback);
//					var d=dialog({
//						title: '提示',
//						content:'系统发生了一些错误，请刷新后在试',
//						okValue: '确认',
//						ok: function () {
//							d.close();
//						}
//					});
//					d.showModal();
//				});
//			};
//			form.find('.pu-con-btn .btn2').off('click').on('click',ajax_callback);
//		}
//	});
	if(document.URL.indexOf('&trial=on') > -1){
		$('.register-btn').click();
	}
});