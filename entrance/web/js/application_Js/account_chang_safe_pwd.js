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
			var oldpwd = $("input[name = 'safe_oldpwd']",$$).val();
			var newpwd = $("input[name='safe_newpwd']",$$).val();
			var newpwd2 = $("input[name = 'safe_newpwd2']",$$).val();
			var url = "index.php?c=user-user&a=changsafepwd";
			var data = {
				"safe_oldpwd" : oldpwd,
				"safe_newpwd" : newpwd,
				"safe_newpwd2" : newpwd2
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
						$(".btn2",$$).removeClass("none-click");
						d.close();
						//关闭当前标签
						var tag = WindowTag.getCurrentTag();
						
						if(typeof data['tag'] == 'string'){
		    				var ctag = WindowTag.getTagByUrlHash(data['tag']);
		    				if(ctag){
		    					window.WindowTag.selectTag(ctag.find(' > a:first').attr('url'));
		    					window.WindowTag.loadTag(ctag.find(' > a:first').attr('href'),'get',function(){});
		    				}
		    			}
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
						$(".btn2",$$).removeClass("none-click");
						d.close();
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
	            datatype :{
	            	"recheck":function(gets,obj,curform,regxp) {
	                   if($.trim($("#account_chance_pwd_oldpsw",$$).val()) == gets){
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