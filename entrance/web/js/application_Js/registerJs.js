define(function(require,exports,module){
	var $ = require('jquery');
		require("placeholder")($);
		require("validForm")($);
		
	var navigators = require("navigatortest"),
		ajax=require("Ajax"),
		dialog = require("dialog");  //弹窗插件
	
	/*
	 *@func 返回当前浏览器高度
	 * */
	exports.getWindowHeight=function(){
		return window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
	}
	/*
	 *针对IE方法初始化
	 * */
	exports.iniIE=function(){
		var  that=this;
		//针对IE10以下的input提示语兼容
		if(sys.ie && sys.ie < 10){
			$(".register").placeholder();
		};
		
		//针对IE6 背景设置及LOGO PNG图片兼容
		var w = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
		var h = that.getWindowHeight();
		if(sys.ie && sys.ie < 7){
			$('.logo').children("img").attr("src","../../images/logo-big.gif");    
			$('#body-mask').css({
				width:w,
				height:h
			});
		};
	}
	
	/**
	 * 动态刷新图片验证码
	 * 
	 */
	var code = $(".get-img-code img").attr("src");
	$(".get-img-code").off("click").on("click", function () {
	    $(this).find('img').attr("src", code + "&_math=" + Math.random());
	});
	
	/*
	 *@func 查看服务协议
	 * */
	exports.showAgrmt=function(){
		var that=this;
		var dialogHtml = $(".agreement");
		$('#agreement').on('click', function () {
			var d = dialog({
				title: '<i class="ifont1"></i><span>服务条款</span>',
				content: dialogHtml,
				okValue: '已阅读并同意条款',
				ok: function () {
					d.close();
				}

//				button: [
//			        {
//			            value: '已阅读并同意条款'
//			        }
//			    ]
			});
			d.showModal();
			
			//协议自定义高度
			var _h = $('.agreement').height(),
				h=that.getWindowHeight();
			if(h < _h){
				$('.agreement').css({
					height:h - 40 + 'px'
				});
			};
			
		});
	}
	/*
	 *@func 事件绑定
	 * */
	exports.bindEvt=function(){
		var that=this;
		$(".code").on("click",function(){
			var  cur=$(this);
			cur.find("img").attr("src",cur.find("img").attr("src")+"&code="+Math.random());
		});
	}
	/*
	 *@func 计时器
	 * */
	exports.timeing=function(){
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
	}
	/*
	 *@func 发送验证码事件绑定
	 * */
	exports.sendCode=function(){
		var that=this;
		var img_code = $('#img_code');
		$(".get-code").on("click",function(){
			var self = $('.get-code');
			if(self.hasClass('current')){//当前正在到倒计时
				return false;
			}
			if(img_code.val() == ''){
				alert('请先填写图片验证码');
				return false;
			}
			var phone = $('input[name=phone]').val();
			if(phone=='' || $("#phone").hasClass("Validform_error")){//验证手机号
				$("#phone").trigger("blur");
				return;
			}
			var url = self.attr('url');
			if(!url){
				return false;
			}
			$.get(url,{phone:phone,img_code:img_code.val()},function(json){
				if(json.status == 1){//发送成功,开始记时
					that.timeing();
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
			});
	}
	/*
	 *@func 鼠标移入去掉错误提示
	 * */
	exports.removeFocusOutline=function(){
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
	}
	/*
	 *@func 表单验证事件绑定
	 * */
	exports.iniFormEvt=function(){
		$(".register_form").Validform({
			tiptype:function(msg,o,cssctl){
                 var objtip=o.obj.parents(".fm-col").find(".msgTxt");
                cssctl(objtip,o.type);
                objtip.text(msg);
                var _par=objtip.parent(),
                		_top=0;
//          		if(_par.parent().prev().attr("name")!="phone"){
//          			_top=0;
//          		}
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
	               			"width":"160px"
	               		}
	               	);
                }
            },
            callback:function(form){
				var btn=$("#sub_reister");
				if(!btn.hasClass("clicked")){
					btn.addClass("clicked").val("提交中...");
					var url = form.attr('action');
					var data = form.serialize();
					ajax.doAjax("POST",url,data,function(json){
						btn.removeClass("clicked").val("提交");
						if(json.status == 1){
							var dialogHtml = $(".hide-register-suc");
							var d = dialog({
								title: '<span></span>',
								content: dialogHtml,
//								okValue: '已阅读并同意条款',
//								ok: function () {
//									d.close();
//								}
							});
							d.showModal();
							$(d.node).find('.ui-dialog-close').click(function(){
								window.location.href=json.url;
							});
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
            }
		});
	}
	exports.init=function(){
		var that=this;
		that.iniIE();
		that.iniFormEvt();
		that.showAgrmt();
		that.bindEvt();
		that.sendCode();
		that.removeFocusOutline();
	}
	$(function(){
	  exports.init();//模块方法初始化
	});
});