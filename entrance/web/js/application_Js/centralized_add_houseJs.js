define(function(require,exports){
	var $ = require('jquery');
		require("selectByM")($);
		require("placeholder")($);
	var navigators = require("navigatortest");  //浏览器版本检测
	var ajax = require("Ajax");
		require("validForm")($);
	var dialog = require("dialog");  //弹窗插件
	var uplodify = require("uplodify");  //图片上传
	var calendar = require("calendar");
	var hash = require('child_data_hash');
   var auto = function($$){
   		var centralized_add_houseJs = {
			submitForm : function(){
				var flat_id = document.URL.split("flat_id=")[1]
				var room_id = "";
				if($(".centralized_add_house",$$).attr("is-edit")){
					flat_id = $(".centralized_add_house",$$).attr("flat-id");
					room_id = document.URL.split("room_focus_id=")[1];
				}
				var custom_number = $("input[name = 'centralized_add_house_custom_number']",$$).val();
				var room_type = $("input[name='centralized_add_house_room_type']",$$).attr("selectval");
				var money = $("input[name = 'centralized_add_house_money']",$$).val();
				var areas = $("input[name='centralized_add_house_area']",$$).val();
				var detain = $("input[name='centralized_add_house_detain']",$$).attr("selectval");
				var pay = $("input[name='centralized_add_house_pay']",$$).attr("selectval");
				var floor = $("input[name='centralized_add_house_floor']",$$).val();
				var room_Config = [];
				$("input[name='centralized_add_house_room_config']:checked",$$).each(function(){
					room_Config.push($(this).val());
				});
				var room_images = [];
				if($(".upload-imgview",$$).size() > 0){
					$(".upload-imgview",$$).each(function(i,o){
						if($(o).attr("filename") != ""){
							room_images.push($(o).attr("filename"));	
						}
					});
				}
				var fee_data = [];
				$(".forcloneauto",$$).each(function(){
					var fee_type_id = $(this).find("input[name='feechoose']").attr("selectval");
					var payment_mode = $(this).find("input[name='jfeechoose']").attr("selectval");
					var money = $(this).find("input[name='money']").val();
					var num_dd = $(this).find("input[name='du']").val();
					var data_record = $(this).find("input[name='cbdate']").val();
					var fee = {
						fee_type_id : fee_type_id,
						payment_mode : payment_mode,
						money : money,
						num_dd : num_dd,
						data_record : data_record
					}
					fee_data.push(fee);
				});
				var url = $(".btn2",$$).attr("url");
				var data = {
					"flat_id":flat_id,
					"room_id":room_id,
					"custom_number" : custom_number,
					"room_type" : room_type,
					"money" : money,
					"areas":areas,
					"detain":detain,
					"pay":pay,
					"floor":floor,
					"room_Config":room_Config,
					"room_images":room_images,
					"fee_data":fee_data
				}
				var type = "post";
				if(room_images.length != $(".upload-imgview",$$).length){
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
				var result_check = hash.hash.ischange("centralized_add_house",$(":first",$$));
				if(result_check === true){
					ajax.doAjax(type,url,data,[centralized_add_houseJs.callback,function(json){$(".btn2",$$).removeClass("none-click");}]);
				}
				else{
					var d = dialog({
						title: '提示信息',
						content:'数据没有发生修改，无法提交！',
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
			callback : function(data){
				if(data.status == 1){
					var d = dialog({
						title: '提示信息',
						content:'保存成功！',
						okValue: '确定',
						ok: function () {
							$(".btn2",$$).removeClass("none-click");
							d.close();
							if($(".add-continue input:checked",$$).size() > 0){
								//刷新当前页面
								var tag = WindowTag.getCurrentTag();
								window.WindowTag.selectTag(tag.find(' > a:first').attr('href'));
								window.WindowTag.loadTag(tag.find(' > a:first').attr('href'),'get',function(){});
								if(typeof data['url'] == 'string'){
				    				window.WindowTag.openTag('#'+data.url);
				    			}else if(typeof data['tag'] == 'string'){
				    				var ctag = WindowTag.getTagByUrlHash(data['tag']);
				    				if(ctag){
				    					window.WindowTag.loadTag(ctag.find(' > a:first').attr('href'),'get',function(){});
				    				}
				    			}
							}else{
								//关闭当前标签
								var tag = WindowTag.getCurrentTag();
								if(typeof data['url'] == 'string'){
				    				window.WindowTag.openTag('#'+data.url);
				    			}else if(typeof data['tag'] == 'string'){
				    				var ctag = WindowTag.getTagByUrlHash(data['tag']);
				    				if(ctag){
				    					window.WindowTag.selectTag(ctag.find(' > a:first').attr('href'));
				    					window.WindowTag.loadTag(ctag.find(' > a:first').attr('href'),'get',function(){});
				    				}
				    			}
								WindowTag.closeTag(tag.find('>a:first').attr('href'));
							}
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
			checkUI : function(){
				$(".centralized_add_house",$$).Validform({
					btnSubmit : ".btn2",
					showAllError : true,
					ignoreHidden : true,
					tiptype : function(msg,o,cssctl){
		                var objtip=o.obj.parents("li").find(".check-error");
		                cssctl(objtip,o.type);
		                objtip.text(msg);
		            },
		            datatype : {
		            	"chooseGroup":function(gets,obj,curform,regxp) {
		                   if(obj.attr("selectVal") == ''){
		                   	 return obj.attr("nullmsg");
		                   }
	
		               },
		               "float":function(gets,obj,curform,regxp) {
		                    var reg=/^\d+(\.\d+)?$/;
		                    if(!reg.test(gets)){return false;}
		                    if(gets.indexOf(".")>0){
		                    	if(gets.split(".")[1].length > 2){
		                    		return "小数点后位数不能超过两位";
		                    	}
		                    }
		               },
		               "house_num":function(gets,obj,curform,regxp){
		               		if(gets/1 ==0 || gets/1 > 99999999) return false;
		               },
		               "gt0":function(gets,obj,curform,regxp){
		                   	    if(parseFloat(gets) == 0) return "房间面积不能为0";
	                   },
	                   "lt999":function(gets,obj,curform,regxp){
	                    	if(parseFloat(gets) > 999.99) return "房间面积不能超过999.99";
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
	                    "price-singel":function(gets,obj,curform,regxp){
	                    	if(gets/1 > 99999999.99){
		                    	return "整数部分不能超过八位";
		                    }
		               	  if(gets.indexOf(".") == 0){
		               	  	return "小数点不能位于第一位";
		               	  }
		               	 if(gets/1 == 0){
		               	 	return "收费单价不能为0";
		               	 }
		               },
		                "floor-num":function(gets,obj,curform,regxp){
		               	 if(gets/1 == 0){
		               	 	return "所在楼层不能为0";
		               	 }
		               	 if(isNaN(gets/1) || gets.indexOf(".")>=0 || Math.abs(gets/1) > 999) return false;
		               }
		            },
		            callback : function(form){
		            	if($(".btn2",form).hasClass("none-click")) return false;
	           			$(".btn2",form).addClass("none-click");
		            	centralized_add_houseJs.submitForm();	
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
			},
			//预约退租信息获取
			getyytzinfo : function(){
				var that = this;
				$(".yytz-room",$$).off("click").on("click",function(){
					var cur=$(this),
					url=cur.attr("data-url");
				if(!cur.hasClass("clicked")){
					cur.addClass("clicked");
					ajax.doAjax("get",url,"",function(json){
						cur.removeClass("clicked");
						if(json.status==1){
							var d = dialog({
							title: '<i class="ifont">&#xe62e;</i><span>预约退租房间</span>',
							content:$("#cover-room-yytz",$$).html(),
							cancel:function(){
								$(".clicked").removeClass("clicked");
							}
						});
						$(".ui-dialog-button").hide();
						 d.showModal();
						 var data_input=$("#roomCheckoutForm").find(".date-input");
						 $.each(data_input, function(i,o) {    
						 $(o).click(function(){
							 	calendar.inite();
							 });
						});
						var tt=$("#roomCheckoutForm");
						tt.find(".room-num").html("<i class='red'>*</i>房间编号：编号"+json.data.custom_number);
						tt.find("input[name='start_time']").val(json.data.back_rental_time);
						tt.find("textarea[name='remark']").val(json.data.remark);
						that.validYytzRoomForm(tt,d);
						that.yytzoff(tt,d);
						}
					});
				}
				});
			},
			/*
		 *@func 初始预约退租遮罩表单
		 * */
		validYytzRoomForm:function(tt,dialogs){
			var that=this;
			var _dialog = dialogs;
			$(tt).Validform({
					btnSubmit : "#room-tz-info-save-autos",
					showAllError : true,
					tiptype : function(msg,o,cssctl){
						var objtip=o.obj;
		           		objtip=objtip.parents(".jzf-col-r").find(".check-error");
		                cssctl(objtip,o.type);
		                objtip.text(msg);
		                if(objtip.parent().parent().find(".Validform_error").length>0){
		                		var curInp=objtip.parent().parent().find("input");
		                		if(curInp.attr("name")=="start_time"){
								var vv=curInp.val();
								if($.trim(vv)!=""){
									objtip.parent().hide();
								}else{
									objtip.parent().show();
								}
							}else{
								objtip.parent().show();
							}
		                }else{
		               	 	var curInp=objtip.parent().parent().find("input");
		               	 	if(curInp.attr("name")=="start_time"){
		               	 		var vv=curInp.val();
								if($.trim(vv)!=""){
									objtip.parent().hide();
								}else{
									objtip.parent().show();
								}
		               	 	}else{
		               	 		objtip.parent().hide();
		               	 	}
		                }
					},
		            beforeCheck:function(){
		            	$("#roomCheckoutForm").find("input,textarea").trigger("blur");
		            },
		            callback : function(form){
		            	    var sdate=$(form).find("input[name='start_time']").val();
		            	    	//edate=$(form).find("input[name='endtime_end']").val();
							if(!sdate){
								var da=dialog({
									title:"错误提示",
									content:"请选择日期",
									okValue: '确 定',
									ok : function(){
										da.close();
									}
								});
								da.showModal();
								return;
							}            	    
			            	that.yytzRoomDatasubmit(form,_dialog);
			            	return false;
		            }
				});


		},
		yytzRoomDatasubmit:function(form,dialogs){
			var data="",_txt=$(form).find(".ipx-txt"),that=this,_dialog=dialogs;
			var data_id = $(".view-head",$$).find(".yytz-room").attr('room_id');
			var reser_back_id = $(".view-head",$$).find(".yytz-room").attr('reser_back_id');
			var flat_id =  $(".centralized_add_house",$$).attr('flat-id');
			var house_id = document.URL.split("&house_id=")[1];
			data += "room_id=" + data_id;
			data += "&reser_back_id=" + reser_back_id;
			data += "&flat_id=" + flat_id;
			data += "&house_id="+house_id;
			$.each(_txt,function(j,item){
				var form_data = $(item).val();
				if (j==0){
					data += "&time_outrented=" + form_data;
				}else{
					data += "&notice=" + form_data;
				}
			});
			var cur=$(form).find("#room-tz-info-save-autos"),
			durl=cur.attr("tzurl");
			if(!cur.hasClass('clicked')){
				cur.addClass("clicked").text("保存中...");
				ajax.doAjax("GET",durl,data,function(json){
					cur.removeClass("clicked").text("保存");
					var da=dialog({
						title: '提示',
						content:json.message,
						cancelValue: '确定',
						cancel: function () {
							if(json.status==1){
								_dialog.close().remove();
								if(typeof json['room_url'] == 'string'){
				    				var ctag = WindowTag.getTagByUrlHash(json['room_url']);
				    				if(ctag){
				    					window.WindowTag.loadTag(ctag.find(' > a:first').attr('href'),'get',function(){});
				    				}
				    			}
							}
						}
					});
					da.showModal();
				});
			}
		},
		yytzoff : function(form,dialogs){
			var flat_id = $(".centralized_add_house",$$).attr("flat-id");
			var rasb_url = $(".cancle-over-trigger-autos",form).attr("rasb-url")+"&flat_id="+flat_id,that=this,_dialog=dialogs;
			var cur=$(form).find(".cancle-over-trigger-autos");
			cur.off("click").on("click",function(){
				if(!cur.hasClass('clicked')){
					cur.addClass("clicked").text("撤销中...");
					ajax.doAjax("GET",rasb_url,"",function(json){
						cur.removeClass("clicked").text("撤销");
						var da=dialog({
							title: '提示',
							content:json.message,
							cancelValue: '确定',
							cancel: function () {
								if(json.status==1){
									da.close();
									_dialog.close().remove();
									//关闭当前标签
								var tag = WindowTag.getCurrentTag();
								WindowTag.closeTag(tag.find('>a:first').attr('href'));
								if(typeof json['room_url'] == 'string'){
				    				var ctag = WindowTag.getTagByUrlHash(json['room_url']);
				    				if(ctag){
				    					window.WindowTag.selectTag(ctag.find(' > a:first').attr('href'));
				    					window.WindowTag.loadTag(ctag.find(' > a:first').attr('href'),'get',function(){});
				    				}
				    			}
								}
							}
						});
						da.showModal();
					});
				}
			});
		}
		}
		//调用下拉框JS
		$(".selectByM",$$).each(function(){
			$(this).selectObjM();
			if($(".centralized_add_house",$$).attr("is-edit")){
				if($(this).find(".selectedLi").size() == 0) return true;
				var obj = $(this).children(".selectByMT");
				obj.val($(this).find(".selectedLi").text());
				obj.attr("selectval",$(this).find(".selectedLi").attr("selectval"));
			}
		});
		
		$(".forclonedelete",$$).off("click").on("click",function(){
				$(this).parent().remove();
			});
		
		$("input[name='cbdate']",$$).each(function(){
				$(this).click(function(){
					calendar.inite();
					$(this).focus().blur();
				});
			});
		//针对IE10以下的input提示语兼容
		if(sys.ie && sys.ie < 10){
			$(".view",$$).placeholder();
		};
		$(".forclonedelete",$$).off("click").on("click",function(){
				$(this).parent().remove();
			});
		//复选框选择及全选、反选
		var checkclick = function(){
			$(".centralized_add_house",$$).find(".check-box").children("label").off("click").on("click",function(){
				if(!$(this).hasClass("checkAll")){
					var check_all = $(this).parent().siblings(".check-box-a");
					$(this).toggleClass("checked");
					if($(this).hasClass("checked")){
						$(this).children(".choose").show();
						$(this).next().attr("checked",true);
						if($(".check-box-o input:checked",$$).size() == $(".check-box-o",$$).size()){
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
						$(this).parent().siblings().children("label").removeClass("checked").children(".choose").hide();
						$(this).next().children().text("全选");
						$(this).parent().siblings().children("input").removeAttr("checked");
					}
				}
			});
			$(".check-box-o a",$$).off("click").on("click",function(){
				var check_all = $(this).parents(".check-box-o").siblings(".check-box-a");
				var obj = $(this).parent().siblings("label");
				obj.toggleClass("checked");
				if(obj.hasClass("checked")){
						obj.children(".choose").show();
						obj.next().attr("checked",true);
						if($(".check-box-o input:checked",$$).size() == $(".check-box-o",$$).size()){
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
			 //检测是否全选
			 (function check_checkall(){
			 	var check_all = $(".check-box-a",$$);
			 	if($(".check-box-o input:checked",$$).size() == $(".check-box-o",$$).size()){
							check_all.children("label").addClass("checked");
							check_all.children("label").children(".choose").show();
							check_all.children("label").next().children().text("取消全选");
						}
			 })();
			 
			 $(".check-box-a a",$$).off("click").on("click",function(){
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
		}
		checkclick();
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
			var obj_choose = $(".check-box-a",$$);
				obj.html("");
				var str = "";
				$(".check-box-o",$$).each(function(){
					var config_name = $(this).children("span").text();
					var config_index = $(this).children("input[name='centralized_add_house_room_config']").val();
					if(!$(this).hasClass("canntchoose")){
						if($(this).children("input:checked").size()>0){
							str += "<li haschoose='true'><label class='checkbox'><span class='choose ifont1'>&#xe60c;</span></label><input class = 'config-auto-edite fl' type='text'  value='"+config_name+"' data-config-id = '"+config_index+"'/></li>";
							return true;
						}
						str += "<li><label class='checkbox'><span class='choose ifont1'>&#xe60c;</span></label><input class = 'config-auto-edite fl' type='text'  value='"+config_name+"' data-config-id = '"+config_index+"'/></li>";	
					}else{
						if($(this).children("input:checked").size()>0){
							str += "<li haschoose='true'><label class='checkbox canntchoose'><span class='choose ifont1'>&#xe60c;</span></label><input class = 'config-auto-edite fl' type='text' value='"+config_name+"' readonly='readonly' data-config-id = '"+config_index+"'/></li>";
							return true;
						}
						str += "<li><label class='checkbox canntchoose'><span class='choose ifont1'>&#xe60c;</span></label><input class = 'config-auto-edite fl' type='text' value='"+config_name+"' readonly='readonly' data-config-id = '"+config_index+"'/></li>";
					}
				});
				obj.append(str);
				if($(".check-box-a",$$).children("label").hasClass("checked")){
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
		$(".config-auto-submit",$$).off("click").on("click",function(){
			$(".check-box-o",$$).remove();
			var obj = $(".roomconfig-diy-txt ul.roomconfig-diy-txt-auto",$$);
			var obj_choose = $(".check-box-a",$$);
			var str = "";
			var result_checkal = true;   //有全选
				obj.find("li").each(function(){
					var _this = $(this);
					var config_name = $(this).children(".config-auto-edite").val();
					var config_index = $(this).children(".config-auto-edite").attr("data-config-id");
					if($(this).children("label").hasClass("canntchoose")){
						if(_this.attr("haschoose")){
							str += '<div class="check-box check-box-o canntchoose"><label class="checked"><span class="choose ifont1" style="display:inline;">&#xe60c;</span></label> <input name="centralized_add_house_room_config" type="checkbox" checked="checked" value="'+config_index+'"/> <span><a href="javascript:;">'+config_name+'</a></span></div>';
							return true;
						}
						result_checkal = false; //没有全选
						str += '<div class="check-box check-box-o canntchoose"><label><span class="choose ifont1">&#xe60c;</span></label> <input name="centralized_add_house_room_config" type="checkbox" value="'+config_index+'"/> <span><a href="javascript:;">'+config_name+'</a></span></div>';
					}else{
						if(_this.attr("haschoose")){
							str += '<div class="check-box check-box-o"><label class="checked"><span class="choose ifont1" style="display:inline;">&#xe60c;</span></label> <input name="centralized_add_house_room_config" type="checkbox" checked="checked" value="'+config_index+'" /> <span><a href="javascript:;">'+config_name+'</a></span></div>';
							return true;
						}
						result_checkal = false; //没有全选
						str += '<div class="check-box check-box-o"><label><span class="choose ifont1">&#xe60c;</span></label> <input name="centralized_add_house_room_config" type="checkbox" value="'+config_index+'" /> <span><a href="javascript:;">'+config_name+'</a></span></div>';
					}
				});
				obj_choose.before(str);
				$(".roomconfig-diy-txt",$$).slideUp(300);
				$(".roomconfig_dy",$$).parent().show().siblings(".check-box").show();
				if(result_checkal === false){
					$(".check-box-a",$$).children("label").removeClass("checked").children().hide();
					$(".check-box-a",$$).children(".select-all").children().text("全选");
				}else{
					$(".check-box-a",$$).children("label").addClass("checked").children().show();
					$(".check-box-a",$$).children(".select-all").children().text("取消全选");
				}
				checkclick();
		});
		centralized_add_houseJs.checkUI();
		centralized_add_houseJs.getyytzinfo();
		uplodify.uploadifyInits($('#file_upload',$$),$("#js-uploaderArea",$$));
		uplodify.uploadifyInits($('#file_upload_auto100',$$),$("#js-uploaderArea-auto100",$$));
		hash.hash.savehash("centralized_add_house",$(":first",$$));
		$(".btn-cancel",$$).off("click").on("click",function(){
			if(hash.hash.ischange('centralized_add_house',$(':first',$$)) === true){
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
		//添加费用
		$(".view-add-cost a",$$).off("click").on("click",function(){
			var totalchoice = $(".dpt-fee-list",$$).find(".forclone .selectByM[hasevent=true]").children(".selectByMO").find("li").size();
			var clone = $(".dpt-fee-list",$$).find(".forclone").clone();
			var choosekey = [];
			$(".dpt-fee-list",$$).find(".forcloneauto .selectByM[hasevent=true]").each(function(){
				var val_auto = $(this).find(".selectByMT").attr("selectval");
				if(val_auto != "") choosekey.push(val_auto);
			});
			var length_choosekey = choosekey.length;
			clone.removeClass("forclone").addClass("forcloneauto").addClass("jzf-col").addClass("clearfix").css("width","100%").show();
			clone.find(".selectByM[hasevent=true] .selectByMO li").each(function(){
				var selectval = $(this).attr("selectval");
				for(var i =0;i<length_choosekey;i++){
					if(selectval == choosekey[i]){$(this).hide(); break;}
				}
			});
			clone.find(".selectByM").each(function(){
				if($(this).attr("hasevent")){
					$(this).selectObjM(1,function(val,inp){
						$(".forcloneauto .selectByM[hasevent=true] .selectByMO li",$$).show();
						var keys_array = [];
						$(".forcloneauto",$$).each(function(){
							var _this_key = $(this).find(".selectByM[hasevent=true]",$$).children(".selectByMO").find("li.selectedLi").attr("selectval");
							keys_array.push(_this_key);
						});
						$(".forcloneauto .selectByM[hasevent=true] .selectByMO li",$$).each(function(){
							for(var n in keys_array){
								if($(this).attr("selectval") == keys_array[n] && !$(this).hasClass(".selectedLi")){
									$(this).hide();
									break;
								}
							}
						});
					});
				}else{
					$(this).selectObjM();	
				}
			});
			clone.find(".forclonedelete").off("click").on("click",function(){
				$(this).parent().remove();
				currentchoice = $(".dpt-fee-list",$$).find(".forcloneauto").size();
				if(totalchoice > currentchoice) $(".dpt-add-btn-auto",$$).parent().show();
			});
			$(this).parent().before(clone);
			var currentchoice = $(".dpt-fee-list",$$).find(".forcloneauto").size();
			if(totalchoice == currentchoice) $(this).parent().hide();
		});
		function auto_checkfee(){
			var choosekey = [];
			$(".dpt-fee-list",$$).find(".forcloneauto .selectByM[hasevent=true]").each(function(){
				var val_auto = $(this).find(".selectByMT").attr("selectval");
				if(val_auto != "") choosekey.push(val_auto);
			});
			$(".dpt-fee-list",$$).find(".forcloneauto .selectByM[hasevent=true]").each(function(){
				$(this).find("li").each(function(i,o){
					if(!$(this).hasClass("selectedLi")){
						var val_auto = $(o).attr("selectval");
						var length = choosekey.length;
						for(var i=0; i<length; i++){
							if(choosekey[i] == val_auto) {$(o).hide(); break;}
						}	
					}
				});
			});
		}
		auto_checkfee();

		/*初始选中*/
		function setDefaultVal(){
			var el=$(".forcloneauto",$$).find(".centralized_Ind_CuteType");
			$.each(el,function(j,item){
				var _e=$(item).next().next().find(".selectedLi");
				if(_e && _e.length>0){
					_e.trigger("click");
				}
			});
		}

		setDefaultVal();

		/*
		 * @func:停用信息
		 */
		$(".edite-auto-stop",$$).off("click").on("click",function(){
			var url = $(this).attr("url");
			var stop_url = $(this).attr("stop-url");
			var list_url = $(this).attr("list-url");
			var recover_url = $(this).attr("recover-url");
			var type = "get";
			var room_id = document.URL.split("room_focus_id=")[1];
			var flat_id = $(this).attr("flat-id");
			var data ={
				room_id : room_id
			};
			ajax.doAjax(type,url,data,function(data){
				if(data.status == 1){
					var start_time_c = data.data[0].start_time_c;
					var end_time_c = data.data[0].end_time_c;
					var remark = data.data[0].remark;
					var stop_id = data.data[0].stop_id;
					var d = dialog({
									title: '<i class="ifont">&#xe62e;</i><span>停用房间</span>',
									content: '<div class="dg-option"><span class="dg-date">起始时间:</span><input type="text" class="ipt endtime-start" placeholder = "2014-4-28" value="'+start_time_c+'"/>'
											+ '<span class="dg-date">结束时间:</span><input type="text" class="ipt endtime-end" placeholder = "2014-4-28"  value="'+end_time_c+'"/></div>'
											+ '<div class="dg-title">停用说明:</div>'
											+ '<textarea class="dg-con dg-stopUseTxt">'+remark+'</textarea>'
											+'<div class="jzf-col"><div class="jzf-col-r blo-row"><div class="resv-act-btns" style="text-align:right"><a href="javascript:;" class="btn btn2 mr10"  id="room-stop-info-save-auto">保存</a>'
											+'<a href="javascript:;" class="btn btn4 cancle-over-trigger-auto" style="margin-right:12px">恢复</a></div></div></div>',
									okValue: '保 存', drag: true,
									ok: function () {
										
									},
									cancelValue: '撤销',
									cancel: function () {
										
									}
						});
						$(".ui-dialog-button").hide();
						d.showModal();	
						submitStop();
						submitStopoff();
						$(".dg-option .endtime-start, .dg-option .endtime-end").off("click").on("click",function(){
									calendar.inite();
						});
						function submitStop(){
							$("#room-stop-info-save-auto").off("click").on("click",function(){
								var endtime_start  = $(".endtime-start").val();
								var endtime_end = $(".endtime-end").val();
								var notice = $(".dg-stopUseTxt").val();
								var datas = {
									stop_id : stop_id,
									endtime_start : endtime_start,
									endtime_end : endtime_end,
									notice : notice
								};
								ajax.doAjax(type,stop_url,datas,function(data){
									if(data.status == 1){
										var d_auto =  dialog({
															title: '提示信息',
															content: '保存成功！',
															okValue: '确 定', drag: true,
															ok: function () {
																d_auto.close();
																d.close().remove();
																var ctag = WindowTag.getTagByUrlHash(list_url);
											    				if(ctag){
											    					window.WindowTag.loadTag(ctag.find(' > a:first').attr('href'),'get',function(){});
											    				}
															}
												});
												d_auto.showModal();	
									}else{
										var d_auto = dialog({
														title: '提示信息',
														content: data.data,
														okValue: '确 定', drag: true,
														ok: function () {
															d_auto.close();
														}
											});
											d_auto.showModal();	
									}
								});
							});
						}
						function submitStopoff(){
							$(".cancle-over-trigger-auto").off("click").on("click",function(){
								ajax.doAjax("get",recover_url,"",function(data){
									if(data.status == 1){
										var d_auto =  dialog({
															title: '提示信息',
															content: '恢复成功！',
															okValue: '确 定', drag: true,
															ok: function () {
																d_auto.close();
																d.close().remove();
																	//关闭当前标签
																	var tag = WindowTag.getCurrentTag();
																	WindowTag.closeTag(tag.find('>a:first').attr('href'));
												    				var ctag = WindowTag.getTagByUrlHash(list_url);
												    				if(ctag){
												    					window.WindowTag.selectTag(ctag.find(' > a:first').attr('href'));
												    					window.WindowTag.loadTag(ctag.find(' > a:first').attr('href'),'get',function(){});
												    				}
															}
												});
												d_auto.showModal();	
									}else{
										var d_auto = dialog({
														title: '提示信息',
														content: data.data,
														okValue: '确 定', drag: true,
														ok: function () {
															d_auto.close();
														}
											});
											d_auto.showModal();	
									}
								});
							});
						}
				}else{
					var d = dialog({
									title: '提示信息',
									content: data.data,
									okValue: '确 定', drag: true,
									ok: function () {
										d.close();
									}
						});
						d.showModal();	
				}
			});
		});
		//检测show弹出弹窗
   		function showDialog(){
   			var url = document.URL;
   			var string = "show";
   			if(url.indexOf(string)>0){
   				if($(".yytz-room",$$).size()>0){
   					$(".yytz-room",$$).trigger('click');
   				}else if($(".edite-auto-stop",$$).size()>0){
   					$(".edite-auto-stop",$$).trigger('click');
   				}
   			}
   		};
   		showDialog();
		//删除费用copy模板中的租金、押金和定金
		$(".forclone .selectByMO:first li",$$).each(function(){
			var selectval = $(this).attr("selectval");
			if(selectval == 2 || selectval == 61 || selectval == 62) $(this).remove();
		});
   }
	exports.inite = function(__html__){
		auto(__html__)
	}
});