define(function(require,exports,module){
	var $ = require('jquery');
		require("selectByM")($);
		require("placeholder")($);
		require("validForm")($);
	var navigators = require("navigatortest");  //浏览器版本检测
	var ajax = require("Ajax");
	var uplodify = require("uplodify");   //上传图片插件

	//调用下拉框JS
	$(".selectByM").each(function(){
		var that = $(this);
		if(that.attr('hasevent','true')){
			that.selectObjM(1,function(val,input){
				that.siblings('.city').find('input').val('');
				var add_compounds = {
					submitForm: function(){
						var	pid = val;
						var type = "POST";
						var url = $(".compounds").attr('dataUrl');
						var data = {
							"pid": pid
						};
						ajax.doAjax(type,url,data,add_compounds.callback);
					},
					callback: function(data){
						if(data.status == 1 && data.city_list != undefined && data.city_list.length != 0){
							cityContainer = {};
							for (var i = 0; i < data.city_list.length; i++) {
								if (cityContainer[data.city_list[i]['city_id']] == undefined) {
									cityContainer[data.city_list[i]['city_id']] = data.city_list[i]['name']
								}
							}
//							$(".city .selectByMO").find('li:not(:first)').remove();
							$(".city .selectByMO ul").html('');
							
							
							var cityHtml = [];
							for (var key in cityContainer) {
								var liHtml = '<li selectVal="' + key + '">' + cityContainer[key] + '</li>';
								cityHtml.push(liHtml);
							}
							$(".city .selectByMO ul").html(cityHtml.join(''));
						}
					}
					
				}
				add_compounds.submitForm();
			});
		}else{
			$(this).selectObjM();
		}
	});
	
	
	/**
	 * 基本设置提交
	 * 
	 */
	var basicMsgSubmit = {
		submitForm: function(){
			var flat_name = $("input[name = 'flat_name']").val(),
				province_id = $("input[name = 'province_id']").attr('selectVal'),
				city_id = $("input[name = 'city_id']").attr('selectVal'),
				phone = $("input[name = 'phone']").val(),
				name = $("input[name = 'name']").val(),
				summary = $(".i-txt").val(),
				domain_name = $("input[name = 'domain_name']").val(),
				flat_id = $(".basic-btn").attr("flat_id");
			var type = "POST";
			var data = {
				"flat_name": flat_name,
				"province_id": province_id,
				"city_id": city_id,
				"phone": phone,
				"name": name,
				"summary": summary,
				"domain_name": domain_name,
				"flat_id": flat_id
			};
			var url = $(".basic-btn").attr("url");
			ajax.doAjax(type,url,data,basicMsgSubmit.callback);
				
		},
		callback: function(data){
			if(data.status == 1){
				window.parent.mask.dialogShowModle('提示信息',data.message);
			}else{
				window.parent.mask.dialogShowModle('提示信息',data.message);
			}
		},
		checkUI: function(){
			$('.basic-set').Validform({
				btnSubmit : ".basic-btn",
				showAllError : true,
				ignoreHidden : true,
				tiptype: function(msg,o,cssctl){
	                var objtip=o.obj.parents('li').find(".check-error");
	                cssctl(objtip,o.type);
	                objtip.text(msg);
	            },
	            datatype: {
	            	"province":function(gets,obj,curform,regxp) {
                		if(obj.attr("selectVal") == '' || obj.attr("selectVal") == 0){
	                    	return '请选择省份';
	                    }
	                },
	                "city":function(gets,obj,curform,regxp) {
                		if(obj.attr("selectVal") == '' || obj.attr("selectVal") == 0){
	                    	return '请选择城市';
	                    }
	                },
	                "website":function(gets,obj,curform,regxp) {
	                	var reg =  /^[0-9a-zA-Z]*$/g;
                		if(gets == ''){
	                    	return false;
	                    }else if(reg.test(gets) == true){
	                    	return true;
	                    }else{
	                    	return false;
	                    }
	                },
	            },
	            callback: function(){
	            	basicMsgSubmit.submitForm();
	            }
			});
		}
	}
	basicMsgSubmit.checkUI();
	
	
//	var modelInit = function($$){
//		alert(1);
//
//	}
//	
//	//模块初始化
//	exports.inite = function(__html__){
//		modelInit(__html__);
//
//	};
	
});