define(function(require,exports,module){
	var $ = require('jquery');
				require("selectByM")($);
	var ajax = require("Ajax");
	var dialog = require("dialog");  //弹窗插件
	var citu_choose = function($$){
		var city_choose = {
			inite : function(){
				$(".selectByM",$$).each(function(){
					if($(this).attr("hasevent")){
						$(this).selectObjM(1,function(val,inp){
							var url = inp.attr("dataurl");
							var pid = val;
							var data = {
								pid : pid
							};
							ajax.doAjax("post",url,data,function(data){
								if(data.status == 1){
									var arrary = data.city_list;
									var str = "";
									var obj = $(".choose-city",$$);
									obj.find("ul").html("");
									for(var n in arrary){
										str += " <li selectVal = '"+arrary[n].city_id+"'>"+arrary[n].name+"</li>"
									}
									obj.find("ul").append(str);
								}else{
									var d = dialog({
										title: '提示信息',
										content:data.message,
										okValue: '确定',
										ok: function () {
											d.close();
										}
									});
									d.showModal();
								}
							});
						});
					}else{
						$(this).selectObjM();
					}
				});
			},
			submitcity : function(){
				var url = $(".btn-choose-city",$$).attr("seturl");
				$(".btn-choose-city",$$).off("click").on("click",function(){
					var city_id = $(".choose-city",$$).find(".selectByMT").attr("selectval");
					var city_name = $(".choose-city",$$).find(".selectByMT").val();
					if(city_id == ""){
						var d = dialog({
							title: '提示信息',
							content:"请先选择城市！",
							okValue: '确定',
							ok: function () {
								d.close();
							}
						});
						d.showModal();
						return false;
					}
					var data = {
						city_id : city_id,
						city_name : city_name
					};
					ajax.doAjax("post",url,data,function(data){
						if(data.status == 1){
							var d = dialog({
							title: '提示信息',
							content:"设置成功！",
							okValue: '确定',
							ok: function () {
								d.close();
								var tag = WindowTag.getCurrentTag();
								if(typeof data['url'] == 'string'){
									$(".head .head-l .city").text("["+data.city_name+"]");
									var parent_tag = WindowTag.getTagByUrlHash(data['url']);
									if(!!!parent_tag){
										WindowTag.closeTag(tag.find('>a:first').attr('href'));
										return false;
									}
									var parent_uid = parent_tag.attr("guid");
									var parent_page = $(".jooozo_Page[guid="+parent_uid+"]");
									parent_page.find(".city_Name").text(data.city_name).attr("data-cityid",data.city_id);
									window.WindowTag.selectTag(parent_tag.find(' > a:first').attr('href'));
								}
//								WindowTag.closeTag(tag.find('>a:first').attr('href'));
								window.location.reload();
							}
						});
						d.showModal();
						}else{
							var d = dialog({
										title: '提示信息',
										content:data.data,
										okValue: '确定',
										ok: function () {
											d.close();
										}
									});
									d.showModal();
						}
					});
				});
				$(".hot-cities a,.cities-list a",$$).off("click").on("click",function(){
					var city_id = $(this).attr("city_id");
					var city_name = $(this).text();
					var data = {
						city_id : city_id,
						city_name : city_name
					};
					ajax.doAjax("post",url,data,function(data){
						if(data.status == 1){
							var d = dialog({
							title: '提示信息',
							content:"设置成功！",
							okValue: '确定',
							ok: function () {
								d.close();
								var tag = WindowTag.getCurrentTag();
//								if(typeof data['url'] == 'string'){
//									$(".head .head-l .city").text("["+data.city_name+"]");
//									var parent_tag = WindowTag.getTagByUrlHash(data['url']);
//									if(!!!parent_tag){
//										 WindowTag.closeTag(tag.find('>a:first').attr('href')); 
//										 return false;
//									}
//									var parent_uid = parent_tag.attr("guid");
//									var parent_page = $(".jooozo_Page[guid="+parent_uid+"]");
//									parent_page.find(".city_Name").text(data.city_name).attr("data-cityid",data.city_id);
//									window.WindowTag.selectTag(parent_tag.find(' > a:first').attr('href'));
//								}
								if(tag){
									WindowTag.closeTag(tag.find('>a:first').attr('href'));
								}
								window.location.reload();
							}
						});
						d.showModal();
						}else{
							var d = dialog({
										title: '提示信息',
										content:data.data,
										okValue: '确定',
										ok: function () {
											d.close();
										}
									});
									d.showModal();
						}
					});
				});
			}
		};
		var thats = city_choose;
		//省市级联
		thats.inite();
		//提交城市
		thats.submitcity();
	};
	
	exports.inite = function(__html__){
		citu_choose(__html__);
	}
});