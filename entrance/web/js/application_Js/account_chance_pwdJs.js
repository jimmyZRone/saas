define(function(require,exports,module){
	var $ = require('jquery');
		require("placeholder")($);
	var navigators = require("navigatortest");  //浏览器版本检测
	var ajax = require("Ajax");
		require("validForm")($);
	var dialog = require("dialog");  //弹窗插件
	var $$ = null;
	
	var account_chance_pwdJs = {
		submitForm : function(){
			var oldpwd = $("input[name = 'oldpwd']",$$).val();
			var newpwd = $("input[name='newpwd']",$$).val();
			var newpwd2 = $("input[name = 'newpwd2']",$$).val();
			var url = "/index.php?c=user-user&a=changepwd";
			var data = {
				"oldpwd" : oldpwd,
				"newpwd" : newpwd,
				"newpwd2" : newpwd2
			}
			var type = "post";
			ajax.doAjax(type,url,data,account_chance_pwdJs.callback);
		},
		callback : function(data){
			if(data.status == 1){
				var d = dialog({
					title: '提示信息',
					content:'密码修改成功',
					okValue: '确定',
					ok: function () {
						d.close();
						$(".btn2",$$).removeClass("none-click");
						var tag = WindowTag.getCurrentTag();
						WindowTag.closeTag(tag.find('>a:first').attr('url'));
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
						d.close();
						$(".btn2",$$).removeClass("none-click");
					}
				});
				d.showModal();
				$(".ui-dialog-close",".ui-dialog-header").hide();
			}
		},
		checkUI : function(){
			$(".account_chance_pwd",$$).Validform({
				btnSubmit : ".btn2",
				showAllError : true,
				tiptype : function(msg,o,cssctl){
	                var objtip=o.obj.parents("li").find(".check-error");
	                cssctl(objtip,o.type);
	                objtip.text(msg);
	            },
	            datatype : {
	            	"recheck":function(gets,obj,curform,regxp) {
	                   if($("#account_chance_pwd_oldpsw",$$).val().replace(/(^\s*)|(\s*$)/g,'') == gets){
	                   	 return "新密码不能与原密码相同！";
	                   }
	                    
	                }
	            },
	            callback : function(form){
	            	if($(".btn2",form).hasClass("none-click")) return false;
	           		$(".btn2",form).addClass("none-click");
	            	account_chance_pwdJs.submitForm();	
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
		$$=__html__;
		//针对IE10以下的input提示语兼容
//		if(sys.ie && sys.ie < 10){
//			$(".view").placeholder();
//		};
		account_chance_pwdJs.checkUI();
	}
});