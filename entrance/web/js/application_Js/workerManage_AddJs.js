define(function(require,exports){
	/*
	 * @desc:员工管理-添加新员工
	 * @date:2015-4-1
	 */
	var $ = require("jquery");
		require("radio")($);
		require("selectByM")($);		//自定义下拉菜单
	var ajax = require("Ajax");
		require("validForm")($);
	var dialog = require("dialog");  //弹窗插件
	var modelInit = function($$){
		var workerAdd = {
			submitForm : function(){
				var workerManage_Add_Id = $("input[name = 'workerManage_Add_Id']",$$).val();
				var workerManage_Add_Psw = $("input[name='workerManage_Add_Psw']",$$).val();
				var workerManage_Add_Name = $("input[name = 'workerManage_Add_Name']",$$).val();
				var gender = $("input[name='workerManage_Add_B_Radio_gender']:checked",$$).val();	//0是女1是男
				var workerManage_Add_Tel = $("input[name = 'workerManage_Add_Tel']",$$).val();
				var workerManage_Add_GroupName = $("input[name = 'workerManage_Add_GroupName']",$$).attr("selectVal");
				var remark = $("#remark",$$).val();
				var url = $(".submite",$$).attr('url');
				var data = {
					"workerId" : workerManage_Add_Id,
					"worker_Psw" : workerManage_Add_Psw,
					"worker_Name" : workerManage_Add_Name,
					"worker_Gender" : gender,
					"worker_Tel" : workerManage_Add_Tel,
					"worker_Group" : workerManage_Add_GroupName,
					"remark":remark
				}
				var type = "post";
				ajax.doAjax(type,url,data,workerAdd.callback);
			},
			callback : function(data){
				if(data.status == 1){
					var d = dialog({
						title: '提示信息',
						content:typeof data['data'] == 'string' ? data.message : '操作成功',
						okValue: '确定',
						ok: function () {
							$(".submite",$$).removeClass("none-click");
							d.close();
							//关闭当前标签
							var tag = WindowTag.getCurrentTag();
							if(typeof data['url'] == 'string'){
			    				window.WindowTag.openTag('#'+data.url);
			    			}else if(typeof data['tag'] == 'string'){
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
				}else{
					var d = dialog({
						title: '提示信息',
						content:data.message,
						okValue: '确定',
						ok: function () {
							$(".submite",$$).removeClass("none-click");
							d.close();
						}
					});
					d.showModal();
				}
				$(".ui-dialog-close",".ui-dialog-header").hide();
			},
			checkUI : function(){
				$(".workerManage_Add_B",$$).Validform({
					btnSubmit : ".submite",
					showAllError : true,
					tiptype : function(msg,o,cssctl){
		                var objtip=o.obj.parents("li").find(".check-error");
		                cssctl(objtip,o.type);
		                objtip.text(msg);
		            },
		            datatype : {
		            	"chooseGroup":function(gets,obj,curform,regxp) {
		                   if(obj.attr("selectVal") == ''){
		                   	 return "请选择员工分组";
		                   }
		                 },
							"string60":function(gets,obj,curform,regxp){
								 var value = gets.replace(/([^\u0000-\u00FF])/g,"***");
		                   	 	 var length = value.length;
		                   	 	 if(length>60) return false;
							},
							"string255":function(gets,obj,curform,regxp){
								 var value = gets.replace(/([^\u0000-\u00FF])/g,"***");
		                   	 	 var length = value.length;
		                   	 	 if(length>255) return false;
							}
		            },
		            callback : function(form){
		            	if($(".submite",form).hasClass("none-click")) return false;
	           			$(".submite",form).addClass("none-click");
		            	workerAdd.submitForm();
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
		$(".workerManage_Add_B_Radio",$$).off("click").on("click",function(){
			$(this).Radio();
		});
		//自定义下拉菜单
		$(".selectByM",$$).each(function(){
			$(this).selectObjM();
		});
		workerAdd.checkUI();
	}
	exports.inite = function(__html__){
		modelInit(__html__);
	}
});