define(function(require){
	var $ = require('jquery');
		require("placeholder")($);
		require("validForm")($);
		var dialog=require("dialog"),
			ajax=require("Ajax");
	var navigators = require("navigatortest");  //浏览器版本检测
	
	//针对IE10以下的input提示语兼容
	if(sys.ie && sys.ie < 10){
		$(".list").placeholder();
	};
	
	
	//针对ie6 LOGO PNG图片兼容
	if(sys.ie && sys.ie < 7){
		$(".logo").children("img").attr("src","../../images/logo.gif");
	}
	
	/**
	 * 动态刷新图片验证码
	 * 
	 */
	var code = $(".img-code img").attr("src");
	$(".img-code").off("click").on("click", function () {
	    $(this).find('img').attr("src", code + "&_math=" + Math.random());
	});
	
	
	//验证码
	var VCode = {
		timeing : function(){//倒计时
			var self = $('.get-code');
			if(self.hasClass('current')){//当前正在到倒计时
				return false;
			}
			var time = 60;
			self.html('60秒后获取');
			self.addClass('current');
			var timing = setInterval(function(){
				time--;
				if(time <= 0){
					self.removeClass('current');
					self.html('获取验证码');
					clearInterval(timing);
				}else{
					self.html(time+'秒后获取');
				}
			},1000);
		},
		sendCode : function(){//发送验证码
			var self = $('.get-code');
			if(self.hasClass('current')){//当前正在到倒计时
				return false;
			}
			var phone = $('input[name=phone]').val();
			if(phone==''){
				$("#phone_code").trigger("blur");
				return;
			}
			var img = $('input[name=img_code]').val();
			if(img=='' || $("#img_code").hasClass("Validform_error")){
				$("#img_code").trigger("blur");
				return;
			}
			var url = self.attr('url');
			if(!url){
				return false;
			}
			$.get(url,{phone:phone,img_code:img},function(json){
				if(json.status == 1){//发送成功,开始记时
					VCode.timeing();
				}else{
					var d=dialog({
						title: '错误',
						content:json.message,
						okValue: '确认', 
						ok: function () {
							d.close();
						}
					});
					d.showModal();
				}
			},'json');
		}
	}
	
	var recovery_pwd = {
		//跳转到第二步
		jumpStep2 : function(){
				$(".recovery-pwd-head > ul > li.current").removeClass("current").next().next().addClass("current");
				$(".recovery-pwd-con-step1").slideUp(300).siblings(".recovery-pwd-con-step2").slideDown(300);
		},
		//跳转到第三步
		jumpStep3 : function(){
			$(".recovery-pwd-head > ul > li.current").removeClass("current").next().next().addClass("current");
			$(".recovery-pwd-con-step2").slideUp(300).siblings(".recovery-pwd-con-step3").slideDown(300);
			recovery_pwd.timeOver();
		},
		//倒计时
		timeOver : function(){
			var time = 3;
			var timer = null;
			clearInterval(timer);
			timer = setInterval(function(){
				$(".recovery-pwd-con-step3").find(".timer").text(time);
				time--;
				if(time < 0){
					clearInterval(timer);
					window.location.href = $('.recovery-pwd-con-step3').attr('url');
				}
			},1000);
				
		}
	}
	
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
		$(".step_form_01").Validform({
			btnSubmit:"#sub_step1",
			tiptype : function(msg,o,cssctl){
                var objtip=o.obj.parents("li").find(".msgTxt");
                cssctl(objtip,o.type);
                objtip.text(msg);
                var _par=objtip.parent(),
                		_top=o.obj[0].getBoundingClientRect().top,
                		_left=o.obj[0].getBoundingClientRect().left+230;
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
	               			"left":_left+"px"
	               		}
	               	);
                }
            },
            callback:function(form){
				var btn=$("#sub_step1");
				if(!btn.hasClass("clicked")){
					btn.addClass("clicked").val("提交中...");
					var url = form.attr('action');
					var data = form.serialize();
					ajax.doAjax("GET",url,data,function(json){
						btn.removeClass("clicked").val("提交");
						if(json.status == 1){
							recovery_pwd.jumpStep2();//提交第二步
						}else{
							var d=dialog({
								title: '提示',
								content:json.message,
								okValue: '确认',
								ok: function () {
									d.close();
								}
							});
							d.showModal();
						}
					});
				}
				return false;
            }
		});
		
		$(".step_form_02").Validform({
			btnSubmit:"#step_last",
			tiptype : function(msg,o,cssctl){
                var objtip=o.obj.parents("li").find(".msgTxt");
                cssctl(objtip,o.type);
                objtip.text(msg);
                var _par=objtip.parent(),
                		_top=o.obj[0].getBoundingClientRect().top,
                		_left=o.obj[0].getBoundingClientRect().left+230;
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
	               			"left":_left+"px"
	               		}
	               	);
                }
            },
            callback:function(form){
				var btn=$("#step_last");
				if(!btn.hasClass("clicked")){
					btn.addClass("clicked").val("提交中...");
					var url = form.attr('action');
					var data="phone="+$("#phone").val()+"&code="+$("#phone_code").val()+"&passwd="+$("#npwd").val()+"&cfm_passwd="+$("#cfnpwd").val();
					ajax.doAjax("POST",url,data,function(json){
						btn.removeClass("clicked").val("提交");
						if(json.status == 1){
							recovery_pwd.jumpStep3();//提交第三步
						}else{
							var d=dialog({
								title: '提示',
								content:json.message,
								okValue: '确认',
								ok: function () {
									d.close();
								}
							});
							d.showModal();
						}
					});
				}
				return false;
            }
		});
		$('.get-code').on('click',VCode.sendCode);
	});
});