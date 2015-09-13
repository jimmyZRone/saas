define(function(require,exports,module){
	/*
	 * 财务类型JS
	 */
	var $ = require('jquery');
		require("placeholder")($);
		require("validForm")($);
	var navigators = require("navigatortest");  //浏览器版本检测
	var	ajax = require("Ajax");
	var	dialog = require("dialog");
	var hash = require("child_data_hash");
	
	
	var modelInit = function($$){
		//针对IE10以下的input提示语兼容
		if(sys.ie && sys.ie < 10){
			$(".view").placeholder();
		};
		
		/**
		 * 添加财务类型
		 * 2014.04.24
		 * 
		 */
		var addFinanceType = function(){
			var type = $("#type-model",$$).html();
			$(".add-type",$$).off("click").on("click",function(){
				$(".view-ul",$$).append(type);
				deleteFinanceType();
			});
		};
		addFinanceType();
		
		/**
		 * 删除财务类型
		 * 2014.04.24
		 */
		var deleteFinanceType = function(){
			$(".finance_type_del",$$).off("click").on("click",function(){
				var that =  $(this);
				var id = that.parents("li").attr("uid");
				if(id == ''){
					that.parents("li").remove();
				}else{
					var dd = dialog({
						title: '提示信息',
						content: '删除的信息将无法得到恢复，确定删除？',
						okValue: '确定',
						ok: function(){
							var type = "POST";
							var url = that.attr("url");
							var data = {
								"fee_type_id": id,
							};
							ajax.doAjax(type,url,data,function(msg){
								if(msg.status == 1){
									that.parents("li").remove();
									var d = dialog({
										title: '提示信息',
										content: '删除成功',
										okValue: '确定',
										ok: function(){
											d.close();
										}
									});
									d.showModal();
								}else{
									var d = dialog({
										title: '提示信息',
										content: msg.message,
										okValue: '确定',
										ok: function(){
											d.close();
										}
									});
									d.showModal(); 
								};
							});
						},
						cancelValue: '取消',
						cancel: function(){
							
						}
					});
					dd.showModal();
				};
				
			});
		};
		deleteFinanceType();
		
		/**
		 * 财务类型表单提交
		 * 2014.04.24
		 * 
		 */
		var financeType = {
			submitForm: function(){
				var finance_types = [];
				
				$("input[name = 'typeManage']",$$).each(function(){
					var uid = $(this).parent('li').attr('uid');
					var type_val = $(this).val();
					var arr;
					if(type_val != ''){
						arr = [uid,type_val];
					}
					finance_types.push(arr);
				});
				var type = "POST";
				var url = $(".btn2",$$).attr("url");
				var data = {
					"typeManage": finance_types
				};
				ajax.doAjax(type,url,data,financeType.callback);
				
			},
			callback: function(msg){
				if(msg.status == 1){
					var d = dialog({
						title: '提示信息',
						content: '保存成功',
						okValue: '确定',
						ok: function(){
							d.close();
						}
					});
					d.showModal();
				}else{
//					$(".finance_type_btn",$$).attr('md','md');
					var d = dialog({
						title: '提示信息',
						content: msg.message,
						okValue: '确定',
						ok: function(){
							d.close();
						}
					});
					d.showModal();
				}
			},
			checkUI: function(){
				$(".finance_type",$$).Validform({
					btnSubmit : ".btn2",
					showAllError : true,
					tiptype : function(msg,o,cssctl){
		                var objtip=o.obj.parents('li').find(".check-error");
		                cssctl(objtip,o.type);
		                objtip.text(msg);
		           },
		           callback: function(){
		           		var res = hash.hash.ischange("finance_type",$(":first",$$));
		            	if(res === true){
		            		financeType.submitForm();
		            	}else{
		            		var d = dialog({
								title: '提示信息',
								content: '当前数据没有任何修改',
								okValue: '确定',
								ok: function(){
									d.close();
								}
							});
							d.showModal();
		            	}
		           }
				});
			}
		};
		financeType.checkUI();
		
		//取消
		hash.hash.savehash("finance_type",$(":first",$$));
		$(".btn-cancel",$$).off("click").on("click",function(){
			if(hash.hash.ischange('finance_type',$(':first',$$)) == true){
				var d = dialog({
							title: '提示信息',
							content:'数据已发生修改，确认取消？',
							okValue: '确定',
							ok: function () {
								d.close();
								//关闭当前标签
								var tag = WindowTag.getCurrentTag();
								WindowTag.closeTag(tag.find('>a:first').attr('href'));
							},
							cancelValue: '取消',
							cancel: function () {
								
							}
						});
						d.showModal();
			}else{
				//关闭当前标签
				var tag = WindowTag.getCurrentTag();
				WindowTag.closeTag(tag.find('>a:first').attr('href'));
			}
		});
	};
	
	exports.inite = function(__html__){
		modelInit(__html__);
	};

	
});