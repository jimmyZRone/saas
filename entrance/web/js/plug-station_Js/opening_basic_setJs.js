define(function(require,exports,module){
	var $ = require('jquery');
		require("selectByM")($);
		require("placeholder")($);
		require("validForm")($);
	var navigators = require("navigatortest");  //浏览器版本检测
	var ajax = require("Ajax");
	var uplodify = require("uplodify");   //上传图片插件

	
	var modelInit = function($$){
		//调用下拉框JS
		$(".selectByM",$$).each(function(){
			var that = $(this);
			if(that.hasClass("compounds")){
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
//								$(".city .selectByMO").find('li:not(:first)').remove();
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
			}else if(that.hasClass("city")){
				that.selectObjM(1,function(val,input){
					that.siblings('.area').find('input').val('所有区域');
					
					var add_area = {
						submitForm: function(){
							var	city_id = val;
							var type = "GET";
							var url = $(".city",$$).attr('dataUrl');
							var data = {
								"city_id": city_id
							};
							ajax.doAjax(type,url,data,add_area.callback);
						},
						callback: function(data){
							if(data.status == 1 && data.city_list != undefined && data.city_list.length != 0){
								areaContainer = {};
								for (var i = 0; i < data.city_list.length; i++) {
									if (areaContainer[data.city_list[i]['area_id']] == undefined) {
										areaContainer[data.city_list[i]['area_id']] = data.city_list[i]['name'];
									}
								}
								$(".area .selectByMO ul").html('');
								
								var areaHtml = [];
								for (var key in areaContainer) {
									var liHtml = '<li selectVal="' + key + '">' + areaContainer[key] + '</li>';
									areaHtml.push(liHtml);
								}
								$(".area .selectByMO ul").html(areaHtml.join(''));
							}
						}
						
					}
					add_area.submitForm();
				});
			}else{
				$(this).selectObjM();
			}
			//商圈
			
		});
		
		/**
		 * 基本设置提交
		 * 
		 */
		var basicMsgSubmit = {
			submitForm: function(){
				var flat_name = $("input[name = 'flat_name']",$$).val(),
					province_id = $("input[name = 'province_id']",$$).attr('selectVal'),
					city_id = $("input[name = 'city_id']",$$).attr('selectVal'),
					area_id = $("input[name = 'area_id']",$$).attr('selectVal'),
					phone = $("input[name = 'phone']",$$).val(),
					name = $("input[name = 'name']",$$).val(),
					summary = $("textarea[name = 'summary']",$$).val(),
					domain_name = $("input[name = 'domain_name']",$$).val();
				var type = "POST";
				var data = {
					"flat_name": flat_name,
					"province_id": province_id,
					"city_id": city_id,
					"area_id": area_id,
					"phone": phone,
					"name": name,
					"summary": summary,
					"domain_name": domain_name
				};
				var url = $(".basic-btn",$$).attr("url");
				ajax.doAjax(type,url,data,basicMsgSubmit.callback);
					
			},
			callback: function(data){
				if(data.status == 1){
					var tag = WindowTag.getCurrentTag();
					//关闭当前标签
					WindowTag.closeTag(tag.find('>a:first').attr('url'));
					//跳页面
					if(data.url != '' && data.url != undefined){
						window.location.hash = '#' + data.url;
//						window.WindowTag.openTag('#' + data.url);
					}
				}else{
					var d = dialog({
						title: '提示信息',
						content: data.message,
						okValue: '确定',
						ok: function(){
							d.close();
						}
					});
					d.showModal();
				}
			},
			checkUI: function(){
				$('.open-basic-set',$$).Validform({
					btnSubmit : ".btn1",
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
		                "area":function(gets,obj,curform,regxp) {
	                		if(obj.attr("selectVal") == ''){
		                    	return false;
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

		
	}
	
	//模块初始化
	exports.inite = function(__html__){
		modelInit(__html__);

	};
	
});