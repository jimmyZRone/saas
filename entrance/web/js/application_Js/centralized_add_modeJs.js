define(function(require,exports){
	var $ = require('jquery');
		require("selectByM")($);
		require("placeholder")($);
		require("validForm")($);
	var navigators = require("navigatortest");  //浏览器版本检测
	var ajax = require("Ajax");
	var dialog = require("dialog");  //弹窗插件
	var uplodify = require("uplodify");  //图片上传
	var hash = require('child_data_hash');
	var loading=require("loading");
	var $$ = null;
	
	var auto = function($$){
		var centralized_add_modeJs = {
			others : function(){
				//调用下拉框JS
				$.each($(".selectByM",$$), function(i,o) {    
					$(o).selectObjM();
					if($(".centralized_add_mode",$$).attr("is-edit")){
						var obj = $(this).children(".selectByMT");
						obj.val($(this).find(".selectedLi").text());
						obj.attr("selectval",$(this).find(".selectedLi").attr("selectval"));
					}
				});
				//针对IE10以下的input提示语兼容
				if(sys.ie && sys.ie < 10){
					$(".view",$$).placeholder();
				};
				
				//配置房间跳转
				$(".goHouseConfig",$$).off("click").on("click",function(){
					if($(".centralized_add_mode",$$).attr("is-edit")){
						var url = $(this).attr("url");
						window.location.href = url+"&is_edite=1";
					}else{
						var url = $(this).attr("url");
						var flat_id = document.URL.split("flat_id=")[1];
						window.location.href = url+"&flat_id="+flat_id;	
					}
				});
				
				//复选框选择及全选、反选
				$(".centralized_add_mode",$$).find(".check-box").children("label").off("click").on("click",function(){
					if(!$(this).hasClass("checkAll")){
						var check_all = $(this).parent().siblings(".check-box-all");
						$(this).toggleClass("checked");
						if($(this).hasClass("checked")){
							$(this).children(".choose").show();
							$(this).next().attr("checked",true);
							if($(".check-box-singel input:checked",$$).size() == $(".check-box-singel",$$).size()){
								check_all.children("label").addClass("checked");
								check_all.children("label").children(".choose").show();
								check_all.children("label").next().children().text("取消全选");
							}
						}else{
							$(this).children(".choose").hide();
							$(this).next().removeAttr("checked");
							check_all.children("label").removeClass("checked");
							check_all.children("label").children(".choose").hide();
							check_all.children("label").next().children().text("全选");
						}
					}else{
						$(this).toggleClass("checked");
						if($(this).hasClass("checked")){
							$(this).children(".choose").show();
							$(this).next().children().text("取消全选");
							$(this).parent().siblings().children("label").addClass("checked").children(".choose").show();
							$(this).parent().siblings().children("input").attr("checked",true);
						}else{
							$(this).children(".choose").hide();
							$(this).next().children().text("全选");
							$(this).parent().siblings().children("label").removeClass("checked").children(".choose").hide();
							$(this).parent().siblings().children("input").removeAttr("checked");
						}
					}
				});
				$(".check-box-singel a",$$).off("click").on("click",function(){
					var check_all = $(this).parents(".check-box-singel").siblings(".check-box-all");
					var obj = $(this).parent().siblings("label");
					obj.toggleClass("checked");
					if(obj.hasClass("checked")){
							obj.children(".choose").show();
							obj.next().attr("checked",true);
							if($(".check-box-singel input:checked",$$).size() == $(".check-box-singel",$$).size()){
								check_all.children("label").addClass("checked");
								check_all.children("label").children(".choose").show();
								check_all.children("label").next().children().text("取消全选");
							}
						}else{
							obj.children(".choose").hide();
							obj.next().removeAttr("checked");
							check_all.children("label").removeClass("checked");
							check_all.children("label").children(".choose").hide();
							check_all.children("label").next().children().text("全选");
						}
				});
				$(".select-all a",$$).off("click").on("click",function(){
					var obj = $(this).parent().prev();
					obj.toggleClass("checked");
					if(obj.hasClass("checked")){
						obj.children(".choose").show();
						$(this).text("取消全选");
						obj.parent().siblings().children("label").addClass("checked").children(".choose").show();
						obj.parent().siblings().children("input").attr("checked",true);
					}else{
						obj.children(".choose").hide();
						$(this).text("全选");
						obj.parent().siblings().children("label").removeClass("checked").children(".choose").hide();
						obj.parent().siblings().children("input").removeAttr("checked");
					}
				});
				 //检测是否全选
				 (function check_checkall(){
				 	var check_all = $(".check-box-all",$$);
				 	if($(".check-box-singel input:checked",$$).size() == $(".check-box-singel",$$).size()){
								check_all.children("label").addClass("checked");
								check_all.children("label").children(".choose").show();
								check_all.children("label").next().children().text("取消全选");
							}
				 })();
			},
			submitForm : function(){
				var type = "post",
					  url =  $(".btn2",$$).attr("url");
				var template_name = $(".template_name",$$).val();	//模板名称
				var rent_money = $(".rent_money",$$).val();		//租金
				var room_type = $(".room_type",$$).attr("selectval");		//房间户型
				var money_ya = $(".centralized_Ind_CuteType_Ya",$$).attr("selectval");    //押金
				var money_fu = $(".centralized_Ind_CuteType_Fu",$$).attr("selectval");    //付金
				var room_area = $(".room_area",$$).val();			//房间面积
				var roomconfig = [];   //房间配置
				$(".house-config input[type='checkbox']:checked",$$).each(function(i,o){
					roomconfig.push($(o).val());
				});
				var img_list = [];	//图片配置
				if($(".upload-imgview",$$).size() > 0){
					$(".upload-imgview",$$).each(function(){
						if($(this).attr("filename") != ""){
							img_list.push($(this).attr("filename"));	
						}
					});
				}
				var config_house = $(".house-list",$$).data("room-config");   //已配置房间
				if(typeof(config_house) == "undefined") config_house = "";
				if(config_house == "") config_house = 0;
				var template_id = document.URL.split("template_id=")[1];
				if(typeof(template_id) == "undefined") template_id = '';
				var flat_id = document.URL.split("flat_id=")[1];
				if(typeof(flat_id) == "undefined") flat_id = '';
				var bz_txt = $(".i-txt",$$).val();  //备注信息
				var updatetype = $("input[name='updata_room']:checked",$$).size();
				var data = {
					template_name : template_name,
					rent_money : rent_money,
					room_type : room_type,
					money_ya : money_ya,
					money_fu : money_fu,
					room_area : room_area,
					roomconfig : roomconfig,
					img_list : img_list,
					bz_txt : bz_txt,
					config_house : config_house,
					updatetype : updatetype,
					flat_id : flat_id,
					template_id : template_id
				}
				if(img_list.length != $(".upload-imgview",$$).length){
					var d = dialog({
						title: '提示信息',
						content:'请等待图片上传'
					});
					d.showModal();
					setTimeout(function(){
						d.close().remove();
					},1500);
					return false;
				}
				if(hash.hash.ischange("centralized_add_mode",$(":first",$$)) == true) {
					loading.genPageLoading();
					ajax.doAjax(type,url,data,[centralized_add_modeJs.callback,function(json){
						loading.closeOverlay();
						$(".btn2",$$).removeClass("none-click");
						$(".ui-dialog-close",".ui-dialog-header").hide();
					}]);
				}
				else{
					var d_autos = dialog({
						title: '提示信息',
						content:'数据没有发生修改，无法提交！',
						okValue: '确定',
						ok: function () {
							$(".btn2",$$).removeClass("none-click");
							d_autos.close();
						}
					});
					d_autos.showModal();
					$(".ui-dialog-close",".ui-dialog-header").hide();
				}
			},
			callback : function(data){
				loading.closeOverlay();
				if(data.status == 1){
					var d = dialog({
						title: '提示信息',
						content:'保存成功',
						okValue: '确定',
						ok: function () {
							$(".house-list",$$).data("room-config","");
							$(".btn2",$$).removeClass("none-click");
							d.close();
							//关闭当前标签
							var tag = WindowTag.getCurrentTag();
							if(typeof data['landlord_url'] == 'string'){
				 				var da=dialog({
									title:"提示",
									content:"当前公寓还未添加业主合同，是否需要添加？",
									cancelValue:"取消",
									cancel:function(){
										da.close().remove();
									},
									okValue:"确定",
									ok:function(){
										da.close().remove();
										WindowTag.openTag(data.landlord_url);
										return false;
									}
								});
								da.showModal();
				 			}
							
							if(typeof data['url'] == 'string'){
			    				var ctag = WindowTag.getTagByUrlHash(data['url']);
			    				if(ctag){
			    					window.WindowTag.selectTag(ctag.find(' > a:first').attr('href'));
			    					window.WindowTag.loadTag(ctag.find(' > a:first').attr('href'),'get',function(){
			    						var guid = ctag.attr("guid");
			    						var parent_page = $(".jooozo_Page[guid="+guid+"]");
			    						parent_page.find(".btn2").text("确 定").addClass("close-this");
			    						parent_page.find(".btn-cancel").hide();
			    					});
			    				}else{
			    					if(typeof data['tag'] == 'string'){
										var ctags = WindowTag.getTagByUrlHash(data['tag']);
										if(ctags){
					    					window.WindowTag.selectTag(ctags.find(' > a:first').attr('href'));
					    					window.WindowTag.loadTag(ctags.find(' > a:first').attr('href'),'get',function(){});
					    				}
									}
			    				}
			    			}
							WindowTag.closeTag(tag.find('>a:first').attr('href'));		
						}
					});
					d.showModal();
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
				}
				$(".ui-dialog-close",".ui-dialog-header").hide();
			},
			checkForm : function(){
				$(".centralized_add_mode",$$).Validform({
					btnSubmit : ".btn2",
					showAllError : true,
					tiptype : function(msg,o,cssctl){
		                var objtip=o.obj.parents("li").find(".check-error");
		                cssctl(objtip,o.type);
		                objtip.text(msg);
		           },
					datatype : {
		           		"chooseGroup":function(gets,obj,curform,regxp) {
			                   if(obj.attr("selectVal") == ''){
			                   	 return obj.attr("choosenull");
			                   }
	
			               },
			               "float":function(gets,obj,curform,regxp) {
		                        var reg=/^\d+(\.\d+)?$/;
		                        if(!reg.test(gets)){return false;}
		                   },
		                   "gt0":function(gets,obj,curform,regxp){
		                   	    if(parseFloat(gets) == 0) return "房间面积不能为0";
		                   },
		                   "lt999":function(gets,obj,curform,regxp){
		                    	if(parseFloat(gets) > 999.99) return "房间面积不能超过999.99";
		                    	if(gets.indexOf(".") > 0){
			                   	  	if(gets.split(".")[1].length > 2) return "小数点后不能超过两位";
			                   	  }
		                   },
		                   "rentmoney":function(gets,obj,curform,regxp){
		                   	 if($.trim(gets) == "") return "请输入租金";
		                   	  if(isNaN(gets/1)) return "租金应为数字";
		                   	  if(gets/1 < 0 || gets/1 == 0) return "租金应为大于零正数";
		                   	  if(gets/1 > 99999999.99) return "租金整数部分不能超过8位";
		                   	  if(gets.indexOf(".") > 0){
		                   	  	if(gets.split(".")[1].length > 2) return "小数点后不能超过两位";
		                   	  }
		                   },
		                   "string40":function(gets,obj,curform,regxp){
		                   	 var value = gets.replace(/([^\u0000-\u00FF])/g,"***");
		                   	 var length = value.length;
		                   	 if(length > 40) return false;
		                   }
		           },
		           callback : function(form){
		           		if($(".btn2",form).hasClass("none-click")) return false;
	           			$(".btn2",form).addClass("none-click");
		           		centralized_add_modeJs.submitForm();
		           		return false;
		           }
				});
			},
			//换一批
			exchangeOthers : function(){
				var num_Page = 1;
				$(".exchangeTenplateConfigRooms",$$).off("click").on("click",function(){
					var _this = $(this);
					if(!!!$(".house-list",$$).data("room-config")){
						num_Page++;
						var type = "get";
						var url = $(this).attr("url");
						var template_id = document.URL.split("template_id=")[1];
						if(typeof(template_id) == "undefined") template_id = '';
						var data = {
							page : num_Page,
							template_id : template_id
						};
						ajax.doAjax(type,url,data,function(data){
							if(data.status == 0){
								var d = dialog({
									title: '提示信息',
									content:data.data,
									okValue: '确定',
									ok: function () {
										d.close();
									}
								});
								d.showModal();
							}else{
								var datain = data.data.data;
								var cpages = data.data.page.cpage;
								if(cpages == 1) _this.parent().hide();
								$(".template_roomsconfig",$$).remove();
								for(var n in datain){
									var room_id = datain[n].room_id;
									var room_num = datain[n].custom_number;
									var str = "<li room-id='"+room_id+"' class='template_roomsconfig'><a href='javascript:;'>"+room_num+"</a></li>";
									$(".config-house:last",$$).before(str);
								}
								delete_auto();
								if(num_Page == cpages){
									num_Page = 0;
								}
							}
						});
					}else{
						var start_visible = 0;
						var length_autos = $(".template_roomsconfig",$$).length;
						var end_visible = $(".template_roomsconfig:visible",$$).last();
						if(end_visible.index(".template_roomsconfig",$$) == $(".template_roomsconfig",$$).length-1){
							start_visible =0;
						}else{
							start_visible = end_visible.index(".template_roomsconfig",$$)+1;
						}
						$(".template_roomsconfig",$$).hide();
						for(var i = start_visible; i<start_visible+10; i++){
							$(".template_roomsconfig:eq("+i+")",$$).show();
							if(i > length_autos-1) break;
						}
					}
				});
				function delete_auto(){
					$(".template_roomsconfig .ifont").off("click").on("click",function(){
						var parent_obj = $(this).parents(".house-list");
						var _this = $(this).parent();
						var del = function(){
							if(!!!$(".house-list",$$).data("room-config")){
								var template_id = document.URL.split("&template_id=")[1];
								var room_id = _this.attr("room-id");
								var url = parent_obj.attr("deledt-url");
								var data= {
									template_id : template_id,
									room_id : room_id
								}
								ajax.doAjax("get",url,data,function(data){
									if(data.status == 1){
//										var d_auto = dialog({
//											title: '提示信息',
//											content:"删除成功！",
//											okValue: '确定',
//											ok: function () {
//												d_auto.close();
//												_this.remove();
//												if($(".template_roomsconfig",$$).size() == 0) $(".exchangeTenplateConfigRooms",$$).trigger("click");
//											}
//										});
//										d_auto.showModal();
										_this.remove();
										if($(".template_roomsconfig",$$).size() == 0) $(".exchangeTenplateConfigRooms",$$).trigger("click");
									}else{
										var d_auto = dialog({
											title: '提示信息',
											content:data.data,
											okValue: '确定',
											ok: function () {
												d_auto.close();
											}
										});
										d_auto.showModal();
									}
								})
							}
						}
//						var dd = dialog({
//							title:'<i class="ifont">&#xe675;</i><span>删除配置房间</span>',
//							content:'确定删除配置房间？',
//							okValue:'确 定',
//							ok:function(){
//								dd.close();
//								del();
//							},
//							cancelValue: '取 消',
//							cancel: function () {
//							}
//						});
//						dd.showModal();
						del();
					});	
				}
				delete_auto();
			}
		}
		var that = centralized_add_modeJs;
		that.others($$);
		that.checkForm();
		that.exchangeOthers();
		//上传插件
		uplodify.uploadifyInits($('#file_upload',$$),$("#js-uploaderArea",$$));
		uplodify.uploadifyInits($('#file_upload1',$$),$("#js-uploaderArea1",$$));
		hash.hash.savehash('centralized_add_mode',$(':first',$$));
		$(".btn-cancel",$$).off("click").on("click",function(){
			if(hash.hash.ischange('centralized_add_mode',$(':first',$$)) == true){
				var d = dialog({
							title: '提示信息',
							content:'数据已发生修改，确认取消？',
							okValue: '确定',
							ok: function () {
								d.close();
								//关闭当前标签
								var tag = WindowTag.getCurrentTag();
								WindowTag.closeTag(tag.find('>a:first').attr('url'));
							},
							cancelValue: '取消',
							cancel: function () {
								
							}
						});
						d.showModal();
			}else{
				//关闭当前标签
				var tag = WindowTag.getCurrentTag();
				WindowTag.closeTag(tag.find('>a:first').attr('url'));
			}
		});
		
		//去掉数组中相同元素
		function unique(arr){
			// 遍历arr，把元素分别放入tmp数组(不存在才放)
			var tmp = new Array();
			for(var i in arr){
			//该元素在tmp内部不存在才允许追加
				if(tmp.indexOf(arr[i])==-1){
					tmp.push(arr[i]);
				}
			}
			return tmp;
		}
		
		//自定义房间配置
		$(".roomconfig_dy",$$).off("click").on("click",function(){
			var obj = $(".roomconfig-diy-txt ul.roomconfig-diy-txt-auto",$$);
			var obj_choose = $(".check-box-all",$$);
				obj.html("");
				var str = "";
				$(".check-box-singel",$$).each(function(){
					var config_name = $(this).children("span").text();
					var config_index = $(this).children("input[name='centralized_add_house_room_config']").val();
					if(!$(this).hasClass("canntchoose")){
						if($(this).children("input:checked").size()>0){
							str += "<li haschoose='true'><label class='checkbox'><span class='choose ifont1'>&#xe60c;</span></label><input class = 'config-auto-edite fl' type='text' value='"+config_name+"' data-config-id = '"+config_index+"'/></li>";
							return true;
						}
						str += "<li><label class='checkbox'><span class='choose ifont1'>&#xe60c;</span></label><input class = 'config-auto-edite fl' type='text' value='"+config_name+"' data-config-id = '"+config_index+"'/></li>";	
					}else{
						if($(this).children("input:checked").size()>0){
							str += "<li haschoose='true'><label class='checkbox canntchoose'><span class='choose ifont1'>&#xe60c;</span></label><input class = 'config-auto-edite fl' type='text' readonly='readonly' value='"+config_name+"' data-config-id = '"+config_index+"'/></li>";
							return true;
						}
						str += "<li><label class='checkbox canntchoose'><span class='choose ifont1'>&#xe60c;</span></label><input class = 'config-auto-edite fl' type='text' value='"+config_name+"' readonly='readonly' data-config-id = '"+config_index+"'/></li>";
					}
				});
				obj.append(str);
				if($(".check-box-all",$$).children("label").hasClass("checked")){
					$(".roomconfig-diy-txt",$$).find(".roomconfig-diy-txt-a .checkbox").addClass("checked").children().show();
				}else{
					$(".roomconfig-diy-txt",$$).find(".roomconfig-diy-txt-a .checkbox").removeClass("checked").children().hide();
				}
				$(".roomconfig-diy-txt",$$).slideDown(300);
				$(this).parent().hide().siblings(".check-box").hide();
			//自定义房间配置全选
			obj.find(".checkbox").off("click").on("click",function(){
				if($(this).hasClass("canntchoose")){
					return false;
				}
				$(this).toggleClass("checked");
				if($(this).hasClass("checked")) $(this).children().show();
				else $(this).children().hide();
			});
			$(".roomconfig-diy-txt-a",$$).find(".checkbox").off("click").on("click",function(){
				$(this).toggleClass("checked");
				if($(this).hasClass("checked")){
					$(this).children().show();
					obj.find(".checkbox").each(function(){
						if(!$(this).hasClass("canntchoose")){
							$(this).addClass("checked").children().show();
						}
					});
				}else{
					$(this).children().hide();
					obj.find(".checkbox").each(function(){
						if(!$(this).hasClass("canntchoose")){
							$(this).removeClass("checked").children().hide();
						}
					});
				}
			});
			$(".roomconfig-diy-txt-a",$$).find(".checkbox").next().off("click").on("click",function(){
				var obj_auto = $(this).prev();
				obj_auto.toggleClass("checked");
				if(obj_auto.hasClass("checked")){
					obj_auto.children().show();
					obj.find(".checkbox").each(function(){
						if(!$(this).hasClass("canntchoose")){
							$(this).addClass("checked").children().show();
						}
					});
				}else{
					obj_auto.children().hide();
					obj.find(".checkbox").each(function(){
						if(!$(this).hasClass("canntchoose")){
							$(this).removeClass("checked").children().hide();
						}
					});
				}
			});
			/*
			 * @func: 自定义房间配置多删
			 */
			$(".config-auto-deleteall",$$).off("click").on("click",function(){
				var url = $(this).attr("url");
		   		var type = "post";
		   		var deletelist = [];
		   		obj.find("li").each(function(){
		   				if($(this).find(".checked").size() > 0){
		   					var config_id = $(this).find("input").attr("data-config-id");
		   					var config_str = $(this).find("input").val();
		   					var _eleAuto = {
		   						config_id : config_id,
		   						config_str : config_str
		   					}
		   					deletelist.push(_eleAuto);
		   				}
		   			});
		   		if(deletelist.length == 0){
		   				var dd = dialog({
								title: '提示信息',
								content:"没有选中任何内容",
								okValue: '确定',
								ok: function () {
									dd.close();
								}
							});
						dd.showModal();
		   				return false;
		   			}
		   			var deletedata =   {
		   				url : url,
		   				type : type,
		   				deletelist : deletelist
		   			}
		   			deleteauto(deletedata);
			});
			$(".config-auto-submit",$$).off("click").on("click",function(){
				$(".check-box-singel",$$).remove();
				var obj = $(".roomconfig-diy-txt ul.roomconfig-diy-txt-auto",$$);
				var obj_choose = $(".check-box-all",$$);
				var str = "";
				var result_checkal = true;   //有全选
					obj.find("li").each(function(){
						var _this = $(this);
						var config_name = $(this).children(".config-auto-edite").val();
						var config_index = $(this).children(".config-auto-edite").attr("data-config-id");
						if($(this).children("label").hasClass("canntchoose")){
							if(_this.attr("haschoose")){
								str += '<div class="check-box check-box-singel canntchoose"><label class="checked"><span class="choose ifont1" style="display:inline;">&#xe60c;</span></label> <input name="centralized_add_house_room_config" type="checkbox" checked="checked" value="'+config_index+'"/> <span><a href="javascript:;">'+config_name+'</a></span></div>';
								return true;
							}
							result_checkal = false; //没有全选
							str += '<div class="check-box check-box-singel canntchoose"><label><span class="choose ifont1">&#xe60c;</span></label> <input name="centralized_add_house_room_config" type="checkbox" value="'+config_index+'"/> <span><a href="javascript:;">'+config_name+'</a></span></div>';
						}else{
							if(_this.attr("haschoose")){
								str += '<div class="check-box check-box-singel"><label class="checked"><span class="choose ifont1" style="display:inline;">&#xe60c;</span></label> <input name="centralized_add_house_room_config" type="checkbox" checked="checked" value="'+config_index+'" /> <span><a href="javascript:;">'+config_name+'</a></span></div>';
								return true;
							}
							result_checkal = false; //没有全选
							str += '<div class="check-box check-box-singel"><label><span class="choose ifont1">&#xe60c;</span></label> <input name="centralized_add_house_room_config" type="checkbox" value="'+config_index+'" /> <span><a href="javascript:;">'+config_name+'</a></span></div>';
						}
					});
					obj_choose.before(str);
					$(".roomconfig-diy-txt",$$).slideUp(300);
					$(".roomconfig_dy",$$).parent().show().siblings(".check-box").show();
					if(result_checkal === false){
						$(".check-box-all",$$).children("label").removeClass("checked").children().hide();
						$(".check-box-all",$$).children(".select-all").children().text("全选");
					}else{
						$(".check-box-all",$$).children("label").addClass("checked").children().show();
						$(".check-box-all",$$).children(".select-all").children().text("取消全选");
					}
					that.others();
			});
			var deleteauto = function(deletedata){
				var cloneStr = $(".deletemoreauto",$$).clone();
		   	  cloneStr.removeClass("none");
		   	  var deleteTptal = deletedata.deletelist.length;
		   	  cloneStr.find(".num_total").text(deleteTptal);
		   	  var dd = dialog({
						title: '<i class="ifont">&#xe675;</i><span>删除配置</span>',
						content:cloneStr,
						okValue: '确定',
						ok: function () {
							dd.close();
						},
						cancelValue: '取消',
						cancel: function () {
							$(".ui-dialog-content").children(".deletemoreauto").addClass("stop");
							$(".ui-dialog-content").children(".deletemoreauto").find(".top1 .fl").text("删除终止");
							return false;
						}
					});
				dd.showModal();
				var objAuto =  $(".ui-dialog-content").children(".deletemoreauto"); 
				var tableauto = objAuto.find("table");
				var scrollbar = objAuto.find(".top2a");
				var scrollbalscroll = parseInt(100/deleteTptal);
				var url = deletedata.url;
				var type = deletedata.type;
				var deleteList = deletedata.deletelist;
				var num_cur = 0;  //计数器
				function autodeleteus(){
					if(objAuto.hasClass("stop")) return false;
					var objsauto = null;
					$(".roomconfig-diy-txt-auto li",$$).each(function(){
		   				if($(this).find(".checked").size() > 0){
		   					objsauto = $(this);
		   					num_cur++
		   					return false;
		   				}
		   			 });
		   			 objAuto.find(".num_cur").text(num_cur);
		   			 var trstr = '<tr><td class="zb">'+objsauto.find("input").val()+'</td><td class="yb">正在删除</td></tr>';
		   			 tableauto.append(trstr);
		   			 if(num_cur > 5){
						tableauto.find("tr:eq("+(num_cur-5)+")").fadeOut(300);
					}
		   			 var trcur = tableauto.find("tr:last");
		   			 var idauto = objsauto.find("input").attr("data-config-id");
		   			 var data = {
						config_id : idauto
					};
					ajax.doAjax(type,url,data,function(data){
						if(data.status == 0){
							trcur.find(".yb").addClass("red").removeClass("blue").text(data.data);
							objsauto.find(".checkbox").removeClass("checked").children("").hide();
							if(num_cur == deleteTptal){
								scrollbar.animate({"left":0},300);
								return false;
							}
							scrollbar.animate({"left":-(100-scrollbalscroll*num_cur)+"%"},300);
							var timer = null;
							clearTimeout(timer);
							timer = setTimeout(autodeleteus,200);
						}else{
							trcur.find(".yb").addClass("blue").removeClass("red").text(data.data);
							objsauto.remove();
							if(num_cur == deleteTptal){
								scrollbar.animate({"left":0},300);
								objAuto.find(".top1 .fl").text("删除完成");
								return false;
							}
							scrollbar.animate({"left":-(100-scrollbalscroll*num_cur)+"%"},300);
							var timer = null;
							clearTimeout(timer);
							timer = setTimeout(autodeleteus,200);
						}
					});
				}
				autodeleteus();
			}
			$(".config-auto-add",$$).off("click").on("click",function(){
				var obj = $(".roomconfig-diy-txt ul.roomconfig-diy-txt-auto",$$);
				var url = $(this).attr("url");
				var type = "post";
				var dd = dialog({
						title: '<i class="ifont">&#xe77d;</i><span>新增配置</span>',
						content:"<div class='configs-add'><ul><li>配置名称：<input type='text'/></li></ul><a class='fr add-auto-configs' href='javascript:;'>继续添加</a></div>",
						okValue: '确定',
						ok: function () {
							var config_str = [];
							$(".ui-dialog-content .configs-add li").each(function(){
								var configs = $.trim($(this).find("input").val());
								if(configs != ""){
									config_str.push(configs);
								}
							});
							var data = {
								config_str : config_str
							}
							ajax.doAjax(type,url,data,function(data){
								if(data.status == 1){
									var ds = dialog({
									title: '提示信息',
									content:"保存成功",
									okValue: '确定',
									ok: function () {
										var configs_list = data.data;
										var str = "";
										for(var n in configs_list){
											str += "<li><label class='checkbox'><span class='choose ifont1'>&#xe60c;</span></label><input class = 'config-auto-edite fl' type='text' value='"+configs_list[n].val+"' data-config-id = '"+configs_list[n].key+"'/></li>";
										}
										obj.append(str);
										obj.find(".checkbox").off("click").on("click",function(){
											if($(this).hasClass("canntchoose")){
												return false;
											}
											$(this).toggleClass("checked");
											if($(this).hasClass("checked")) $(this).children().show();
											else $(this).children().hide();
										});
										 ds.close();
										 dd.close();
									  }
									});
									ds.showModal();
								}else{
									var ds = dialog({
									title: '提示信息',
									content:data.data,
									okValue: '确定',
									ok: function () {
										 ds.close();
										 dd.close();
									  }
									});
									ds.showModal();
								}
							});
						},
						cancelValue: '取消',
						cancel: function () {
							
						}
					});
				dd.showModal();
				$(".add-auto-configs").off("click").on("click",function(){
					$(".ui-dialog-content .configs-add ul").append("<li>配置名称：<input type='text'/></li>");
				});
			});
		});
	}
	
	exports.inite = function(__html__){
		auto(__html__);
	}
});