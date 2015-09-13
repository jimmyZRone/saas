define(function(require,exports,module){
	var $$ = null;
	var $ = require('jquery');
		require("placeholder")($);
	var navigators = require("navigatortest");  //浏览器版本检测
	var ajax = require("Ajax");
		require("validForm")($);
	var dialog = require("dialog");  //弹窗插件
	
	var $$cityChooseAnClick = function(){
		//事件拦截
		var htmlDom = $('html');
		var htmlDomOnClick = htmlDom[0].onclick;
		$("html").off("click").on("click",function(){
			$(".account_set .city_ChooseAn",$$).siblings(".city_Choose").hide();
			htmlDom[0].onclick = htmlDomOnClick;
			$$.bind('click',$$cityChooseAnClick);
		});
		$$.unbind('click',$$cityChooseAnClick);
	};
	
	var account_setJs = {
		//选择城市
		chooseCity : function(){
			var nameObj = $(".account_set .city_ChooseAn",$$).find(".city_Name");
			$(".account_set .city_ChooseAn",$$).off("click").on("click",function(event){
				event.stopPropagation();
				$(this).siblings(".city_Choose").show();
			});
			
			//调用事件拦截
			$$cityChooseAnClick();
			
			$(".account_set .city_Choose",$$).find("dd").off("click").on("click",function(event){
				event.stopPropagation();
				var txt = $(this).text();
				var cityId = $(this).attr("data-cityid");
				$(".account_set .city_ChooseAn",$$).siblings(".city_Choose").hide();
				if(!!!cityId){
					var url = $(this).children("a").attr("href");
					window.location.href = url;
					 return false;	
				}
				nameObj.text(txt).attr("data-cityId",cityId);
			});
		},
		submitForm : function(){
			var city_id = $(".city_Name",$$).attr("data-cityid");
			if(!!!city_id) city_id = "";
			var company = $("input[name = 'company_name']",$$).val();
			var name_connect = $("input[name='linkman']",$$).val();
			var phone = $("input[name = 'telephone']",$$).val();
			var jizhong = 0;
			var fensan = 0;
			var allow_client;
			if($(".client > label").hasClass("checked")){
				allow_client = 1;
			}else{
				allow_client = 0;
			}
			if($(".account_set input[name = 'jizhong']:checked",$$).length > 0){
				jizhong = 1;
			}
			if($(".account_set input[name = 'fensan']:checked",$$).length > 0){
				fensan = 1;
			}
			var url = $('.view-btn a',$$).attr('url');
			var data = {
				"city_id" : city_id,
				"company_name" : company,
				"linkman" : name_connect,
				"telephone" : phone,
				"jizhong" : jizhong,
				"fensan" : fensan,
				"allow_client" : allow_client
				
			}
			var type = "post";
			if(jizhong == 0 && fensan == 0){
				account_setJs.callback({status:0,data:'至少需要选择一种房源形态'});
				return false;
			}
			ajax.doAjax(type,url,data,account_setJs.callback);
		},
		callback : function(data){
			if(data.status == 1){
				var d = dialog({
					title: '提示信息',
					content:'保存成功',
					okValue: '确定',
					ok: function () {
						$(".btn2",$$).removeClass("none-click");
						d.close();
						//关闭当前标签
						var tag = WindowTag.getCurrentTag();
						WindowTag.closeTag(tag.find('>a:first').attr('href'));
						window.location.reload();
						return false;
					}
				});
				d.showModal();
				$(".ui-dialog-close",".ui-dialog-header").hide();
			}else{
				var d = dialog({
					title: '提示信息',
					content:data.data,
					okValue: '确定',
					ok: function () {
						$(".btn2",$$).removeClass("none-click");
						d.close();
					}
				});
				d.showModal();
				$(".ui-dialog-close",".ui-dialog-header").hide();
			}
		},
		checkUI : function(){
			$(".account_set",$$).Validform({
				btnSubmit : ".btn2",
				showAllError : true,
				tiptype : function(msg,o,cssctl){
	                var objtip=o.obj.parents("li").find(".check-error");
	                cssctl(objtip,o.type);
	                objtip.text(msg);
	            },
	            callback : function(form){
	            	if($(".btn2",form).hasClass("none-click")) return false;
	           		$(".btn2",form).addClass("none-click");
	            	account_setJs.submitForm();	
	            	return false;
	            }
			});
			$(":input",$$).focus(function(){
				if($(this).hasClass("Validform_error")){
					$(this).css("background","none");
					$(this).siblings(".check-error").hide();
				}
			}).blur(function(){
				$(this).removeAttr("style");
				$(this).siblings(".check-error").show();
			});
		}
	}
	
	
	exports.inite = function(__html__){
		//针对IE10以下的input提示语兼容
		if(sys.ie && sys.ie < 10){
			$(".view").placeholder();
		};
		$$ = __html__;
		//复选框
		$(".check-box",$$).off("click").on("click",function(){
			var obj = $(this).find("label");
			obj.toggleClass("checked");
			if(obj.hasClass("checked")){
				obj.children(".choose").show();
				obj.next().attr("checked",true);
			}else{
				obj.children(".choose").hide();
				obj.next().removeAttr("checked");
			}
		});
		
		
		
		$(".radio-sync").off("click").on("click",function(){
			var that = $(this).find("label");
			that.toggleClass("checked");
			if(that.hasClass("checked")){
				that.find(".r-default").hide();
				that.find(".r-select").show();
				that.siblings("input").attr("checked",true);
			}else{
				that.find(".r-default").show();
				that.find(".r-select").hide();
				that.siblings("input").attr("checked",false);
			}
		});
		var that=account_setJs;
		that.checkUI();
		that.chooseCity();
	}
});