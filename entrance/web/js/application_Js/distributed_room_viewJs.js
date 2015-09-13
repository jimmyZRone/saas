define(function(require,exports,module){
	var $ = require('jquery');
		require("selectByM")($);
		require("placeholder")($);
		require("radio")($);
	 	 require("validForm")($);
		 require("combobox")($);
	var uplodify = require("uplodify"), //图片上传	
		ajax=require("Ajax"), 	
		hash = require('child_data_hash'),
	 	 dialog = require("dialog"); //弹窗插件
	var navigators = require("navigatortest");  //浏览器版本检测
	var calendar = require("calendar");
	
	
	var isOurHourse=0,//是否是属于后台读取的房源数据
		isSavedSelfXQ=0,//是否已添加了自定义小区
		ajaxLoading;

	var distriAddRoomFun=function($$,data){
		function roomModule(){
			this.init();
		}
		roomModule.prototype={
			init:function(){
				var that=this;
				that.bind();
				that.iniUpload();
				that.iePlshder();
				that.choseAllSupporting();
				that.checkValidForm();
				that.switchTab();
                that.setChoseStatus();
                that.quickToolbar();
                that.bindPersonalRoomConfig();
				that.bindChoseDelEvt();
				that.addNewSelfRoomConfig();
                that.bindRemoveImg();
                that.stopRoomInfo();
                that.yytzRoomInfo();
				var _form=$(".distributed_room_view",$$);
				hash.hash.savehash('houseformValid',$(':first',$$));
				that.cacheFormFileDta(_form);
				that.bindCancle(_form);
				that.iniSelAllOps(_form);
				that.showDialog();
			},
			//检测show弹出弹窗
			showDialog:function(){
				var url = document.URL;
	   			var string = "show";
	   			if(url.indexOf(string)>0){
	   				if($(".stop-room",$$).size()>0){
	   					$(".stop-room",$$).trigger('click');
	   				}else if($(".yytz-room",$$).size()>0){
	   					$(".yytz-room",$$).trigger('click');
	   				}
	   			}
			},
			/*
			*@func停用房间信息展示
			*/
			stopRoomInfo:function(){
				var that=this;
				$(".stop-room",$$).click(function(){
					var cur=$(this),
						url=cur.attr("data-url");
					if(!cur.hasClass("clicked")){
						cur.addClass("clicked");
						ajax.doAjax("get",url,"",function(json){
							cur.removeClass("clicked");
							if(json.status==1){
								var d = dialog({
								title: '<i class="ifont">&#xe62e;</i><span>停用房间</span>',
								content:$("#room-detail-distri-stop").html(),
								cancel:function(){
									$(".clicked").removeClass("clicked");
								}
							});
							$(".ui-dialog-button").hide();
							 d.showModal();
							 var data_input=$("#room_detail_stop_info").find(".date-input");
							 $.each(data_input, function(i,o) {    
							 $(o).click(function(){
								 	calendar.inite();
								 });
							});
							that.closeOverlay(d);
							var tt=$("#room_detail_stop_info");
							tt.find("input[name='endtime_start']").val(json.data.start_time_c);
							tt.find("input[name='endtime_end']").val(json.data.end_time_c);
							tt.find("textarea[name='notice']").val(json.data.remark);
							tt.find("input[name='stop_id']").val(json.data.stop_id);
							 that.validStopRoomForm(d);
							}
						});
					}
				});
			},
		/*
		 *@func 初始停用遮罩表单
		 * */
		validStopRoomForm:function(d){
			var that=this;
			$("#room_detail_stop_info").Validform({
					btnSubmit : "#roomdetail-stop-info-save",
					showAllError : true,
					tiptype : function(msg,o,cssctl){
						var objtip=o.obj;
		           		objtip=objtip.parents(".jzf-col-r").find(".check-error");
		                cssctl(objtip,o.type);
		                objtip.text(msg);
		                if(objtip.parent().parent().find(".Validform_error").length>0){
		                		var curInp=objtip.parent().parent().find("input");
		                		if(curInp.attr("name")=="endtime_start" || curInp.attr("name")=="endtime_end"){
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
		               	 	if(curInp.attr("name")=="endtime_start" || curInp.attr("name")=="endtime_end"){
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
		            	$("#room_detail_stop_info").find("input,textarea").trigger("blur");
		            },
		            callback : function(form){
		            	    var sdate=$(form).find("input[name='endtime_start']").val(),
		            	    	edate=$(form).find("input[name='endtime_end']").val();
							if(sdate>edate){
								var da=dialog({
									title:"错误提示",
									content:"起始日期不能大于终止日期",
									okValue: '确 定',
									ok : function(){
										da.close();
									}
								});
								da.showModal();
								return;
							}            	    
			            	that.stopRoomDatasubmit(d,form);
			            	return false;
		            }
				});


		},
		stopRoomDatasubmit:function(d,form){
			var data="",_txt=$(form).find(".ipx-txt"),that=roomModule.prototype;
			$.each(_txt,function(j,item){
				var vv=$(item).val();
				if(j==0){
					data+=$(item).attr("name")+"="+vv;
				}else{
					data+="&"+$(item).attr("name")+"="+vv;
				}
			});
			var cur=$(form).find("#roomdetail-stop-info-save"),
				durl=cur.attr("stopurl");
			if(!cur.hasClass('clicked')){
				cur.addClass("clicked").text("保存中...");
				ajax.doAjax("POST",durl,data,function(json){
					cur.removeClass("clicked").text("保存");
					var da=dialog({
						title: '提示',
						content:json.data,
						cancelValue: '确定',
						cancel: function () {
							if(json.status==1){
								d.close().remove();
								da.close().remove();
							}
						}
					});
					da.showModal();
				});
			}
		},
		/*
		*@func预约退租房间信息展示
		*/
		yytzRoomInfo:function(){
			var that=this;
			$(".yytz-room",$$).click(function(){
				var cur=$(this),
					url=cur.attr("data-url");
				if(!cur.hasClass("clicked")){
					cur.addClass("clicked");
					ajax.doAjax("get",url,"",function(json){
						cur.removeClass("clicked");
						if(json.status==1){
							var d = dialog({
							title: '<i class="ifont">&#xe62e;</i><span>预约退租房间</span>',
							content:$("#cover-room-yytz-hz").html(),
							cancel:function(){
								$(".clicked").removeClass("clicked");
							}
						});
						$(".ui-dialog-button").hide();
						 d.showModal();
						 var data_input=$("#roomCheckoutForm-hz").find(".date-input");
						 $.each(data_input, function(i,o) {    
						 $(o).click(function(){
							 	calendar.inite();
							 });
						});
						that.closeOverlay(d);
						var tt=$("#roomCheckoutForm-hz");
						tt.find(".room-num").html("<i class='red'>*</i>房间编号：编号"+json.data.custom_number);
						tt.find("input[name='start_time']").val(json.data.back_rental_time);
						tt.find("textarea[name='remark']").val(json.data.remark);
						 that.validYytzRoomForm(d);
						}
					});
				}
			});
		},
		closeOverlay:function(d){
			  $(".restore-hz-yytz-act").off().on("click",function(){
            		 	 var cur=$(this),durl=cur.attr("rasb-url");
			  	  if(!cur.hasClass("clicked")){
			  	  	cur.addClass("clicked").text("撤销中...");
			  	  	$.get(durl,"",function(json){
			  	  		json=eval('('+json+')');
						cur.removeClass("clicked").text("撤销");
		  	  			var da=dialog({
							title: '提示',
							content:json.message,
							cancelValue: '确定',
							cancel: function () {
								if(json.status==1){
									da.close().remove();
									d.close().remove();
						  	  		if(json.status==1){
								    	var tag = WindowTag.getCurrentTag();
										WindowTag.closeTag(tag.find('>a:first').attr('href'));
					    				var ctag = WindowTag.getTagByUrlHash(json['list_url']);
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
            		// d.close().remove();
            		// if($(".clicked"))$(".clicked").removeClass("clicked");
            });	
			
			  $(".restore-disable-act").off().on("click",function(){
            		 	 var cur=$(this),durl=cur.attr("data-url");
			  	  if(!cur.hasClass("clicked")){
			  	  	cur.addClass("clicked").text("恢复中...");
			  	  	$.get(durl,"",function(json){
			  	  		json=eval('('+json+')');
						cur.removeClass("clicked").text("恢复");
		  	  			var da=dialog({
							title: '提示',
							content:json.message,
							cancelValue: '确定',
							cancel: function () {
								if(json.status==1){
									da.close().remove();
									d.close().remove();
			  	  		if(json.status==1){
					    	var tag = WindowTag.getCurrentTag();
							WindowTag.closeTag(tag.find('>a:first').attr('href'));
		    				var ctag = WindowTag.getTagByUrlHash(json['list_url']);
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
            		// d.close().remove();
            		// if($(".clicked"))$(".clicked").removeClass("clicked");
            });	



		},
		/*
		 *@func 初始预约退租遮罩表单
		 * */
		validYytzRoomForm:function(d){
			var that=this;
			$("#roomCheckoutForm-hz").Validform({
					btnSubmit : "#room-tz-info-save-hz",
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
		            	$("#roomCheckoutForm-hz").find("input,textarea").trigger("blur");
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
			            	that.yytzRoomDatasubmit(d,form);
			            	return false;
		            }
				});


		},
		yytzRoomDatasubmit:function(d,form){
			var data="",_txt=$(form).find(".ipx-txt"),that=roomModule.prototype;
			var data_id = $(".view-head",$$).find(".yytz-room").attr('room_id');
			var reser_back_id = $(".view-head",$$).find(".yytz-room").attr('reser_back_id');
			data += "room_id=" + data_id;
			data += "&reser_back_id=" + reser_back_id;
			$.each(_txt,function(j,item){
				var form_data = $(item).val();
				if (j==0){
					data += "&" + $(item).attr("name") + "=" + form_data;
				}else{
					data += "&" + $(item).attr("name") + "=" + form_data;
				}
			});
			var cur=$(form).find("#room-tz-info-save-hz"),
			durl=cur.attr("tzurl");
			if(!cur.hasClass('clicked')){
				cur.addClass("clicked").text("保存中...");
				ajax.doAjax("POST",durl,data,function(json){
					cur.removeClass("clicked").text("保存");
					var da=dialog({
						title: '提示',
						content:json.message,
						cancelValue: '确定',
						cancel: function () {
							if(json.status==1){
								da.close().remove();
								d.close().remove();
							}
						}
					});
					da.showModal();
				});
			}
		},
			/*
			 *@func 判断是否勾选全选按钮
			 * */
			iniSelAllOps:function(_form){
				var len1=$(_form).find(".check-box-o").find("input[type='checkbox']:checked").length;
				var len2=$(_form).find(".check-box-o").find("input[type='checkbox']").length;
				if(len1==len2){
					$(_form).find(".check-box-a").trigger("click");
				}
			},
			bindCancle:function(form){
				$(form).find(".btn-cancel").off().on("click",function(){
					var  bl=hash.hash.ischange('houseformValid',$(':first',$$));
					if(bl==true){
						var da=dialog({
							title:"提示",
							content:"数据已发生修改,确认取消?",
							cancelValue:"取消",
							cancel:function(){
								da.close().remove();
							},
							okValue:"确定",
							ok:function(){
								da.close().remove();
								var tag = WindowTag.getCurrentTag();
								WindowTag.closeTag(tag.find('>a:first').attr('url'));
								return false;
							}
						});
						da.showModal();
					}else{
    						var tag = WindowTag.getCurrentTag();
						WindowTag.closeTag(tag.find('>a:first').attr('url'));
					}
				});
			},
			
			/*
			 *@func 缓存图片文件
			 * */
			cacheFormFileDta:function(_form){
				var  fm_area_img=$(_form).find(".uploader-area");
				//图片文件 数据存储
				$.each(fm_area_img,function(i,o){
					var objs=$(o).find(".upload-imgview"),
					 	 datasUnit="";
					 $.each(objs,function(j,item){
					 	 var  cm="";
					 	 if(j==0){
					 	 	cm+=$(item).attr("filename");
					 	 }else{
					 	 	cm+=","+$(item).attr("filename");
					 	 }
					 	 datasUnit+=cm;
					 });
					 $(o).data("inited",datasUnit);
//					  console.log($(o).data("inited"));
				});
			},
			/*
			 *@func 对比缓存图片文件是否修改
			 * */
			differCacheFile:function(_form){
				var  fm_area_img=$(_form).find(".uploader-area"),
					 result=true;//未修改
				$.each(fm_area_img,function(i,o){
					var objs=$(o).find(".upload-imgview"),
					 	 datasUnit="";
					 $.each(objs,function(j,item){
					 	 var  cm="";
					 	 if(j==0){
					 	 	cm+=$(item).attr("filename");
					 	 }else{
					 	 	cm+=","+$(item).attr("filename");
					 	 }
					 	 datasUnit+=cm;
					 });
						 // console.log(datasUnit);
						 var inited_data=$(o).data("inited");
						 // console.log(inited_data);
						 if(datasUnit!=inited_data){
						 	result=false;
						 }
					});
				return result;
			},
			
			/*
			 *@func 绑定自定义房间复选框事件
			 * */
			bindSubCbbox:function(){
				var obj = $(".distributed_room_view",$$).find(".rommForm").find(".roomconfig-diy-txt ul.roomconfig-diy-txt-auto");
				obj.find(".checkbox").off().on("click",function(){
						if($(this).hasClass("canntchoose")){
							return false;
						}
						//======
						$(this).toggleClass("checked");
						if($(this).hasClass("checked")){
							$(this).children().show();
							if($(this).parents('.roomconfig-diy-txt-auto').find(' li label.checked').length == $(this).parents('.roomconfig-diy-txt-auto').find(' li label').length){
								var ckeckbox = $(this).parents('.roomconfig-diy-txt').find('.roomconfig-diy-txt-a label.checkbox');
								ckeckbox.addClass('checked');
								ckeckbox.find('span').show();
							}
						}else{
							$(this).children().hide();
							var ckeckbox = $(this).parents('.roomconfig-diy-txt').find('.roomconfig-diy-txt-a label.checkbox');
							ckeckbox.removeClass('checked');
							ckeckbox.find('span').hide();
						}
					});
			},
			/*
			 *@func 绑定自定义房间全选/删除/新增事件
			 * 
			 * */
			bindChoseDelEvt:function(){
				var par=$(".distributed_room_view",$$).find(".rommForm"),that=this;
				$.each(par,function(i,o){
					var _trigAl=$(o).find(".roomconfig-diy-txt-a").find(".checkbox");
					_trigAl.off("click").on("click",function(){
						var cur=$(this),obj = $(o).find(".roomconfig-diy-txt ul.roomconfig-diy-txt-auto");
						cur.toggleClass("checked");
						//========
						if(cur.hasClass("checked")){
							cur.children().show();
							obj.find(".checkbox").each(function(){
								if(!$(this).hasClass("canntchoose")){
									$(this).addClass("checked").children().show();
								}
							});
						}else{
							cur.children().hide();
							obj.find(".checkbox").each(function(){
								if(!$(this).hasClass("canntchoose")){
									$(this).removeClass("checked").children().hide();
								}
							});
						}
					});	
				});	
				// 自定义房间配置多删
				$(".config-auto-deleteall",$$).off("click").on("click",function(){
						var cur=$(this),url = cur.attr("url"),type = "post",deletelist = [],
							obj=cur.parents(".rommForm").find("ul.roomconfig-diy-txt-auto");
						if(!cur.hasClass("clicked")){
							cur.parents(".distributed_room_view").find(".clicked").removeClass("clicked");
							$(".underEditing").removeClass("underEditing");
							cur.parents(".rommForm").addClass("underEditing");
					   		obj.find("li").each(function(){
					   				var ckeckbox = cur.parents('.roomconfig-diy-txt-a').find('li:first .checkbox');
					   				ckeckbox.removeClass('checked');
									ckeckbox.find('span').hide();
									
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
					   		that.deleteauto(deletedata);
						}
				});
			},
			/*
			 *@func 自定义房间配置删除
			 * 
			 * */
			deleteauto:function(deletedata){
				var that=this;
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
			},
			
			/*
			 *@func 新增房间配置
			 * 
			 * */
			addNewSelfRoomConfig:function(){
				var that=this;
				$(".config-auto-add",$$).off("click").on("click",function(){
						var obj = $(".roomconfig-diy-txt ul.roomconfig-diy-txt-auto",$$);
						var url = $(this).attr("url");
						var cur=$(this);
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
												
												var ckeckbox = cur.parents('.roomconfig-diy-txt-a').find('li:first .checkbox');
								   				ckeckbox.removeClass('checked');
												ckeckbox.find('span').hide();
												
												for(var n in configs_list){
													var str = "<li><label class='checkbox'><span class='choose ifont1'>&#xe60c;</span></label><input class = 'config-auto-edite fl' type='text' value='"+configs_list[n].val+"' data-config-id = '"+configs_list[n].key+"'/></li>";
													obj.append(str);
												}
												
												roomModule.prototype.bindSubCbbox();
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
			},
			/*
			 *@func 自定义房间配置
			 * */
			bindPersonalRoomConfig:function(){
				var that=this,_par=$(".distributed_room_view",$$).find(".rommForm");
				$.each(_par,function(i,o){
				var _triger=$(o).find(".roomconfig_dy"),
					obj_choose=$(o).find(".check-box-a"),
					_trigger_submit = $(o).find(".config-auto-submit");
				//自定义房间配置
					_triger.off("click").on("click",function(){
						var cur=$(this),
							par=cur.parents(".rommForm"),
							obj = par.find(".roomconfig-diy-txt ul.roomconfig-diy-txt-auto"),
						_toggEle=par.find(".roomconfig-diy-txt");
							obj.html("");
							par.find('.check-box-o,.check-box-a').hide();
							var str = "";
							var _ele=par.find(".check-box-o");
							_ele.each(function(){
								var config_name = $(this).children("span").text();
								var config_index = $(this).children("input").val();
								if(!$(this).hasClass("canntchoose")){
									if($(this).children("input:checked").size()>0){
										str += "<li><label class='checkbox checked'><span class='choose ifont1' style='display:inline;'>&#xe60c;</span></label><input class = 'config-auto-edite fl' type='text' value='"+config_name+"' data-config-id = '"+config_index+"'/></li>";
										return true;
									}
									str += "<li><label class='checkbox'><span class='choose ifont1'>&#xe60c;</span></label><input class = 'config-auto-edite fl' type='text' value='"+config_name+"' data-config-id = '"+config_index+"'/></li>";	
								}else{
									if($(this).children("input:checked").size()>0){
										str += "<li><label class='checkbox canntchoose' ischecked='true'><span class='choose ifont1'>&#xe60c;</span></label><input class = 'config-auto-edite fl' type='text' value='"+config_name+"' data-config-id = '"+config_index+"'/></li>";
										return true;
									}
									str += "<li><label class='checkbox canntchoose'><span class='choose ifont1'>&#xe60c;</span></label><input class = 'config-auto-edite fl' type='text' value='"+config_name+"' data-config-id = '"+config_index+"'/></li>";
								}
							});
							obj.append(str);
							if(obj_choose.children("label").hasClass("checked")){
								_toggEle.find(".roomconfig-diy-txt-a .checkbox").addClass("checked").children().show();
							}else{
								_toggEle.find(".roomconfig-diy-txt-a .checkbox").removeClass("checked").children().hide();
							}
							_toggEle.slideDown(300);
							$(this).hide();
							that.bindSubCbbox();
					});
					_trigger_submit.off("click").on("click",function(){
						var cur=$(this),
							par=cur.parents(".rommForm"),
							obj = par.find(".roomconfig-diy-txt ul.roomconfig-diy-txt-auto"),
							_toggEle=par.find(".roomconfig-diy-txt");
						var _ele=par.find(".check-box-o");
						_ele.remove();
							var str="";
							var result_checkal = true;   //有全选
						 obj.find("li").each(function(){
								var config_name = $(this).children(".config-auto-edite").val();
								var config_index = $(this).children(".config-auto-edite").attr("data-config-id");
								if($(this).children("label").hasClass("canntchoose")){
									if($(this).children("label").attr("ischecked")){
										str += '<div class="check-box check-box-o canntchoose"><label class="checked"><span class="choose ifont1" style="display:inline;">&#xe60c;</span></label> <input name="centralized_add_house_room_config" type="checkbox" checked="checked" value="'+config_index+'"/> <span><a href="javascript:;">'+config_name+'</a></span></div>';
										return true;
									}
									result_checkal = false; //没有全选
									str += '<div class="check-box check-box-o canntchoose"><label><span class="choose ifont1">&#xe60c;</span></label> <input name="centralized_add_house_room_config" type="checkbox"  value="'+config_index+'"/> <span><a href="javascript:;">'+config_name+'</a></span></div>';
								}else{
									if($(this).children("label").hasClass("checked")){
										str += '<div class="check-box check-box-o"><label class="checked"><span class="choose ifont1" style="display:inline;">&#xe60c;</span></label> <input name="centralized_add_house_room_config" type="checkbox" checked="checked" value="'+config_index+'" /> <span><a href="javascript:;">'+config_name+'</a></span></div>';
										return true;
									}
									result_checkal = false; //没有全选
									str += '<div class="check-box check-box-o"><label><span class="choose ifont1">&#xe60c;</span></label> <input name="centralized_add_house_room_config" type="checkbox" value="'+config_index+'" /> <span><a href="javascript:;">'+config_name+'</a></span></div>';
								}

							});
							obj_choose.before(str);
							_toggEle.hide();
							_triger.show();
							obj_choose.show();
							if(result_checkal === false){
								obj_choose.children("label").removeClass("checked").children().hide();
								obj_choose.children(".select-all").children().text("全选");
							}else{
								obj_choose.children("label").addClass("checked").children().show();
								obj_choose.children(".select-all").children().text("取消全选");
							}
							that.choseAllSupporting();
					});
				});
			},
            
			
			/*
			 * @func 绑定上传图片删除事件
			 */
			bindRemoveImg:function(){
				 $(".uploader-area",$$).find(".deleteImg").unbind().bind("click",function(){
					 $(this).parents(".upload-imgview").parent().find('.uploadview-wrapper').css({width:'auto',height:'auto',overflow:'inherit'});
             		 $(this).parents(".upload-imgview").remove();
                });
			},
            /*
             * @func 房间配置切换
             */
            setChoseStatus:function(){
                var _form=$(".rommForm",$$),
                    _sel=_form.find(".selectByMT");
                $.each(_sel,function(i,o){
                    var _ele=$(o).parent().find(".selectByMO").find("li.selectedLi");
                    if(_ele.length>0){
                        $(o).val(_ele.text()).attr("selectval",_ele.attr("selectval"));
                    }else{
                        var _def=$(o).parent().find(".selectByMO").find("li:eq(0)");
                        $(o).val(_def.text()).attr("selectval",_def.attr("selectval"));
                    }
                });
            },
			/*
			 * @func 房间配置切换
			 */
			switchTab:function(){
				$(".room-num-box .room-num",$$).on("click",function(){
					var  cur=$(this),
						 _tar=$(".rommForm",$$),
						 tabIndex=cur.attr("data-index");
						 if(!cur.hasClass("current")){
							cur.parents("li").find(".current").removeClass("current");
							cur.addClass("current");
							_tar.addClass("none");
							_tar.parent().find(".rommForm:eq("+(tabIndex-1)+")").removeClass("none");
						 }
				});
			},
			/*
			 * @func IE事件绑定
			 */
			iePlshder:function(){
				if(sys.ie && sys.ie < 10){
					$(".view").placeholder();
				};	
			},
			/*
			 * @func 事件绑定
			 */
			bind:function(){
				//单选
				$('.radio',$$).each(function(){
					$(this).click(function(){
						$(this).Radios();
					})
				});
				//下拉
				$.each($(".selectByM",$$),function(i,o){
					$(o).selectObjM();
				});
			   
			},
			
			/*
			 *@func 表单参数收集
			 * */
			getParams:function(){
				var _param=[],
					_arrSignal=[],
					_formEle=$(".rommForm",$$);
                var isEdit=$(".addAllRoomTrig",$$).attr("is_edit");
				$.each(_formEle,function(j,item){
					var _txtVal=$(item).find("input[type='text']"),
						_cbox=$(item).find(".checkbox-area"),
						_images=$(item).find(".uploader-area"),
						_formSignal={};
                    //编辑才有的参数
                    if(isEdit !="" || isEdit !=undefined){
                        _formSignal["room_id"]=$(item).attr("room-id");
                    }
				    //text
					$.each(_txtVal,function(i,o){
						var vv=$(o).val();
						if($(o).attr("name")=="detain" || $(o).attr("name")=="pay" || $(o).attr("name")=="room_type"||$(o).attr("name")=="occupancy_number"){
							vv=$(o).attr("selectval");
						}
						_formSignal[$(o).attr("name")]=vv;
					});
					//checkbox
					$.each(_cbox,function(i,o){
						var cb=$(o).find("input[type='checkbox']"),
							name=$(o).attr("fm"),
						    cvs="";
						$.each(cb, function(s,c) {   
							if($(c).attr("checked") == true ||$(c).attr("checked") == "checked"){
							  if(s==0){
							  	cvs+=$(c).val();
							  }else{
							  	cvs+=","+$(c).val();
							  }
							}
						});
						_formSignal[name]=cvs;
					});
					//image
					$.each(_images,function(i,o){
						var cb=$(o).find(".upload-imgview"),
							name=$(o).attr("fm"),
						    _files="";
						$.each(cb, function(s,c) {    
							  if(s==0){
							  	_files+=$(c).find("input").val();
							  }else{
							  	_files+=","+$(c).find("input").val();
							  }
						});
						_formSignal[name]=_files;
					});
					_formSignal=JSON.stringify(_formSignal);
//					_arrSignal[(j+1)]=_formSignal;
					_arrSignal.push(_formSignal);
				});
				_param=JSON.stringify(_arrSignal);
//				console.log(_arrSignal);
				return _param;
			},
			
			/*
			 *@func 获取单个房间编辑保存参数
			 * */
			getSignalEditData:function(){
				var _param="",_formEle=$(".rommForm",$$),
					_txtVal=_formEle.find("input[type='text']"),
						_cbox=_formEle.find(".checkbox-area"),
						_images=_formEle.find(".uploader-area"),
						_paramA="",_paramB="",_paramC="";
				    //text
					$.each(_txtVal,function(i,o){
						var vv=$(o).val();
						if($(o).attr("name")=="detain" || $(o).attr("name")=="pay" || $(o).attr("name")=="room_type" || $(o).attr("name")=="occupancy_number"){
							vv=$(o).attr("selectval");
						}
						_paramA+=$(o).attr("name")+"="+vv+"&";
					});
					//checkbox
					$.each(_cbox,function(i,o){
						var cb=$(o).find("input[type='checkbox']:checked"),
							name=$(o).attr("fm"),
						    cvs="";
						$.each(cb, function(s,c) {    
							  if(s==0){
							  	cvs+=$(c).val();
							  }else{
							  	cvs+=","+$(c).val();
							  }
						});
						_paramB+=name+"="+cvs+"&";
					});
					//image
					$.each(_images,function(i,o){
						var cb=$(o).find(".upload-imgview"),
							name=$(o).attr("fm"),
						    _files="";
						$.each(cb, function(s,c) {    
							  if(s==0){
							  	_files+=$(c).find("input").val();
							  }else{
							  	_files+=","+$(c).find("input").val();
							  }
						});
						_paramC+=name+"="+_files+"&";
					});
				_param=_paramA+_paramB+_paramC+"room_id="+$(".addAllRoomTrig",$$).attr("room-id");
				return _param;
			},
			/*
			 *@func 表单提交
			 * */
			submitForm:function(){
				var that=this,_btn=$(".addAllRoomTrig",$$),
					url=_btn.attr("addurl"),
					data=that.getParams();
					var dp={
						data:data,
						house_id:_btn.attr("house-id"),
						count:_btn.attr("room-count")
					};
					var page=_btn.attr("data-page");
					if(page && page =="detail"){
						url=_btn.attr("editurl");
						dp=that.getSignalEditData();//获取单个房间修改参数
					}else{
	                    var isEdit=_btn.attr("is_edit");
	                    if(isEdit !="" && isEdit !=undefined){
	                        url=_btn.attr("editurl");
	                        dp={
	                            data:data,
								house_id:_btn.attr("house-id"),
	                        }
	                    }
					}
				if(!_btn.hasClass("clicked")){
					_btn.parent().find(".clicked").removeClass("clicked");
					_btn.addClass("clicked").text("数据保存中...");
					ajaxLoading=ajax.doAjax("post",url,dp,[function(json){
						_btn.removeClass("clicked").text("保存全部房间").removeAttr("request");
						if(page && page =="detail"){
							_btn.removeClass("clicked").text("保存房间").removeAttr("request");
						}
						if(json.status==0){
							_btn.attr("request",Math.floor(Math.random()*1000));
						}
						var  d=dialog({
	            		 		title:"提示",
	            		 		content:json.message
	            		 	});
	            		 	d.showModal();
	            		 	setTimeout(function(){
	            		 		d.close().remove();
	            		 		if(json.status==1){
        							var tag = WindowTag.getCurrentTag();
        							WindowTag.closeTag(tag.find('>a:first').attr('url'));//关闭当前tag
        							if(typeof json['landlord_url'] == 'string'){
	            		 				var da=dialog({
	            							title:"提示",
	            							content:"当前房源还未添加业主合同，是否需要添加？",
	            							cancelValue:"取消",
	            							cancel:function(){
	            								da.close().remove();
	            							},
	            							okValue:"确定",
	            							ok:function(){
	            								da.close().remove();
	            								WindowTag.openTag(json.landlord_url);
	            								return false;
	            							}
	            						});
	            						da.showModal();
	            		 			}
									if(json.p_url!="" && json.p_url!=undefined){
										var ctag = WindowTag.getTagByUrlHash(json.p_url);
										if(ctag){
					    					window.WindowTag.selectTag(ctag.find(' > a:first').attr('href'));
					    					window.WindowTag.loadTag(ctag.find(' > a:first').attr('href'),'get',function(){});
					    				}else{
					    					window.WindowTag.openTag("#"+json.p_url);
					    				}
									}
	            		 		}
	            		 	},1200);
					},function(){
						_btn.removeClass("clicked").text("保存全部房间").removeAttr("request");
						if(page && page =="detail"){
							_btn.removeClass("clicked").text("保存房间").removeAttr("request");
						}
					}]);
				}
			},
			/*
			 * @func 表单验证
			 */
			checkValidForm:function(){
				var that=this;
				var  _par=$(".rommForm",$$);
				$.each(_par,function(i,o){
					$(o).Validform({
					btnSubmit : ".addAllRoomTrig",
					showAllError : true,
					tiptype : function(msg,o,cssctl){
		                var objtip=o.obj.parents("li").find(".check-error");
		                cssctl(objtip,o.type);
		                objtip.text(msg);
		            },
		            beforeCheck:function(){
		            		$(".rommForm",$$).find("input").trigger("blur");
		            },
		            datatype : {
            			"roomNUm":function(gets,obj,curform,regxp) {
	            			var reg1=/^([0-9][0-9]*)$/,//匹配正整数
	            				reg2=/^([-][0-9][0-9]*)$/,//匹配负整数
	                       gets=gets.replace(/ /g,"");
		                   if(gets=="") return false;
		                   if(reg1.test(gets) || reg2.test(gets)){
		                   		if(gets.length<=8){
		                   			return true;
		                   		}
		                   }
						   return false;
						},
	            		"nint":function(gets,obj,curform,regxp) {
	            			var reg1=/^([1-9][0-9]*)$/;//匹配正整数
	                        gets=gets.replace(/ /g,"");
		                    if(gets=="") return false;
		                    if(reg1.test(gets)){
	                   			return true;
		                    }
						   return false;
						},
						"lengthTest":function(gets,obj,curform,regxp) {
							var reg1=/^[1-9][0-9]{0,2}(\.[0-9]{0,2})?$/,    //匹配正整数
							reg2=/^[0-9]{0,2}\.[0-9]{0,2}$/; //匹配正浮点数
							gets=gets.replace(/ /g,"");
							if(gets=="") return false;
							if(reg1.test(gets)){
								return true;
							}
							if(reg2.test(gets)){
								return true;
							}
							return false;
						},
		                "floatGroup":function(gets,obj,curform,regxp) {
		                   var reg1=/^([1-9][0-9]*)$/,//匹配正整数
	                   	   reg2=/^[0-9]+(.[0-9]{1,2})?$/;//匹配正浮点数
	                   	   gets=gets.replace(/ /g,"");
		                   if(gets=="") return false;
		                   if(reg1.test(gets)==true){
		                   		if(gets.length<=8){
		                   			return true;
		                   		}else{
		                   			return false;
		                   		}
		                   		
		                   }else if(gets.indexOf(".")!='-1'){
		                   		if(reg2.test(gets)){
		                   			if(gets.length<=8){
			                   			return true;
			                   		}else{
			                   			return false;
			                   		}
		                   		}else {
		                   			return false;
		                   		}
		                	}
							return false;
		            	}
		            },
		            callback : function(form){
		            		//验证剩下的form中得错误是否存在
		            		var errorLen=$(".rommForm",$$).find(".Validform_error").length;
		            		if(errorLen==0){
		            			//判断图片是否处于上传状态
		            			var  len=$(".uploader-area",$$).find(".upload-imgview").length,
		            				 _result=true,_tipmsg="";
		            			if(len>0){
		            				$.each($(".uploader-area",$$).find(".upload-imgview"),function(i,o){
		            					var _stat=$(o).attr("filename");
		            					if($.trim(_stat)==""){
		            						_result=false;
		            						_tipmsg="请等待图片上传";
		            						return;
		            					}
		            				});
		            				if(_result==false){
		            					var  d=dialog({
			            		 		title:"提示",
				            		 		content:_tipmsg
				            		 	});
				            		 	d.showModal();
				            		 	setTimeout(function(){
				            		 		d.close().remove();
				            		 	},1500);
		            				}else{
		            					that.beforSubForm(form);
		            				}
		            			}else{
		            				that.beforSubForm(form);
		        				}
		            		}else{
		            			var  dq=dialog({
	            		 		title:"提示",
		            		 		content:"其它房间还未填写数据"
		            		 	});
		            		 	dq.showModal();
		            		 	setTimeout(function(){
		            		 		dq.close().remove();
		            		 	},1500);
		            		}
		            }
				});
				});
				var _inputs=$(".rommForm",$$).find("input[type='text']");
				$.each(_inputs,function(i,o){
					$(o).focus(function(){
						if($(this).hasClass("Validform_error")){
							$(this).css("background","none");
							$(this).parent().find(".check-error").hide();
						}
					}).blur(function(){
						$(this).removeAttr("style");
						$(this).parent().find(".check-error").show();
					});
				});	
			},
			/*
			 * @func 表单提交之前验证
			 */
			beforSubForm:function(form){
				var _f=$(".rommForm",$$).find("input[name='custom_number']"),
					_result=true,_tipmsg="",_temDat;
					_cbd=[];
				$.each(_f,function(i,o){
					_cbd.push($(o).val().replace(/ /g,""));
				});
				var s = _cbd.sort(); ;
				for(var i=0;i<s.length;i++) {
						if(s[i]==s[i+1]){
							_result=false;
							_tipmsg="房间编号不能相同,请检查";
							break;
						}
				}
				if(_result==false){
					var  d=dialog({
        		 		title:"提示",
            		 		content:_tipmsg
            		 	});
            		 	d.showModal();
            		 	setTimeout(function(){
            		 		d.close().remove();
            		 	},1500);
            		 	return;
				}
				var that=roomModule.prototype,
				_form=$(".distributed_room_view",$$),
				bl=hash.hash.ischange('houseformValid',$(':first',$$));
				bl1=that.differCacheFile(_form);
				if(bl==true || bl1==false){
	 				that.submitForm();
				}else{
//					var tag = WindowTag.getCurrentTag();
//					WindowTag.closeTag(tag.find('>a:first').attr('url'));
					var d = dialog({
						title: '提示信息',
						content:'数据没有发生修改，无法提交！',
						okValue: '确定',
						ok: function () {
							d.close();
						}
					});
					d.showModal();
				}
			},
			/*
			 * @func 房间配置全选
			 */
			choseAllSupporting:function(){
			  $('.house-config',$$).find(".check-box-o").find("label").off('click').on('click',function(){
			  		var cur=$(this);
			  		var _par = cur.parents(".house-config").find(".check-box-a");
					if(cur.hasClass("checked")){
						cur.removeClass("checked");
						cur.children(".choose").hide();
						cur.next().removeAttr("checked");
						
						_par.find("label").removeClass("checked");
						_par.find(".choose").hide();
						_par.find("input").removeAttr("checked");
						_par.find(".choose").hide();
						_par.find("label").removeClass("checked");
						_par.find("input").removeAttr("checked");
						_par.addClass("fold").removeClass("unfold");
						_par.find(".select-all").text("全选");
					}else{
						cur.addClass("checked");
						cur.find(".choose").show();
						cur.next().attr("checked",true);
						if(cur.parents(".house-config").find('.check-box-o label.checked').length == cur.parents(".house-config").find('.check-box-o label').length){
							_par.find("label").addClass("checked");
							_par.find(".choose").show();
							_par.find("input").attr("checked",true);
							cur.find(".choose").show();
							_par.find("label").addClass("checked");
							_par.find("input").attr("checked",true);
							_par.addClass("unfold").removeClass("fold");
							_par.find(".select-all").text("取消全选");
						}
						
					}
				});
				$('.house-config',$$).find(".check-box-o").find("span").off('click').on('click',function(){
			  		var cur=$(this).siblings("label");
			  		var _par = cur.parents(".house-config").find(".check-box-a");
					if(cur.hasClass("checked")){
						cur.removeClass("checked");
						cur.children(".choose").hide();
						cur.next().removeAttr("checked");
						
						_par.find("label").removeClass("checked");
						_par.find(".choose").hide();
						_par.find("input").removeAttr("checked");
						_par.find(".choose").hide();
						_par.find("label").removeClass("checked");
						_par.find("input").removeAttr("checked");
						_par.addClass("fold").removeClass("unfold");
						_par.find(".select-all").text("全选");
					}else{
						cur.addClass("checked");
						cur.find(".choose").show();
						cur.next().attr("checked",true);
						if(cur.parents(".house-config").find('.check-box-o label.checked').length == cur.parents(".house-config").find('.check-box-o label').length){
							_par.find("label").addClass("checked");
							_par.find(".choose").show();
							_par.find("input").attr("checked",true);
							cur.find(".choose").show();
							_par.find("label").addClass("checked");
							_par.find("input").attr("checked",true);
							_par.addClass("unfold").removeClass("fold");
							_par.find(".select-all").text("取消全选");
						}
						
					}
				});
				//全选
				$(".rommForm",$$).find(".check-box-a").off().on("click",function(){
					var cur=$(this),_par=cur.parent(".house-config").find(".check-box-o");
					if(cur.hasClass("fold")){
						_par.find("label").addClass("checked");
						_par.find(".choose").show();
						_par.find("input").attr("checked",true);
						cur.find(".choose").show();
						cur.find("label").addClass("checked");
						cur.find("input").attr("checked",true);
						cur.addClass("unfold").removeClass("fold");
						cur.find(".select-all").text("取消全选");
					}else{
						_par.find("label").removeClass("checked");
						_par.find(".choose").hide();
						_par.find("input").removeAttr("checked");
						cur.find(".choose").hide();
						cur.find("label").removeClass("checked");
						cur.find("input").removeAttr("checked");
						cur.addClass("fold").removeClass("unfold");
						cur.find(".select-all").text("全选");
					}
				});
			},
			/*
			 * @func 初始化上传
			 */
			iniUpload:function(){
				var par=$(".rommForm",$$);
				$.each(par,function(j,item){
					var item1=$(item).find(".uploader-area").attr("id"),
						item2=$(item).find("input[name='file_upload']").attr("id");
					item1=$("#"+item1,$$);
					item2=$("#"+item2,$$);
					uplodify.uploadifyInits(item2,item1);
				});
			},
            /*
             * @func 复制粘贴数据
             */
            quickToolbar:function(){
                var TARGETELEMENT1,
                    COPYELEMENT1,
                    TARGETELEMENT2,
                    COPYELEMENT2,
                    TARGETELEMENT3,
                    COPYELEMENT3,
                    AVAIABLEUSER,ROOMAREA,ROOMPAYMENT,
                    copyFormIndex,pasteFormIndex,that=this;
                $(".copy-cur-form-data",$$).off().on("click",function(){
                    var cur=$(this);
                    copyFormIndex=parseInt(cur.parent().prev().find(".current").find("i").text());
                     var  curForm=$(".rommForm:eq("+parseInt(copyFormIndex-1)+")",$$);
                    COPYELEMENT1=curForm.find(".col-rent-room").html();
                    COPYELEMENT2=curForm.find(".col-rent-payment").html();
                    COPYELEMENT3=curForm.find(".view-config").html();

                    AVAIABLEUSER=curForm.find(".col-rent-room").find("input[name='occupancy_number']").val();
                    ROOMAREA=curForm.find(".col-rent-room").find("input[name='area']").val();
                    ROOMPAYMENT=curForm.find(".col-rent-payment").find("input[name='money']").val();
                    that.tipCopyMsg("内容已复制");
                });
                $(".paste-cur-form-data",$$).off().on("click",function(){
                    var cur=$(this);
                        pasteFormIndex=parseInt(cur.parent().prev().find(".current").find("i").text());
                    var targetForm=$(".rommForm:eq("+parseInt(pasteFormIndex-1)+")",$$);
                    if(copyFormIndex==pasteFormIndex){
                        that.tipCopyMsg("请选择不同表单");
                        return;
                    }else{
                        TARGETELEMENT1=targetForm.find(".col-rent-room");
                        TARGETELEMENT2=targetForm.find(".col-rent-payment");
                        TARGETELEMENT3=targetForm.find(".view-config");
                        TARGETELEMENT1.html(COPYELEMENT1);
                        TARGETELEMENT2.html(COPYELEMENT2);
                        TARGETELEMENT3.html(COPYELEMENT3);

                        TARGETELEMENT1.find("input[name='occupancy_number']").val(AVAIABLEUSER);
                        TARGETELEMENT1.find("input[name='area']").val(ROOMAREA);
                        TARGETELEMENT2.find("input[name='money']").val(ROOMPAYMENT);

                        that.bind();
                        that.choseAllSupporting();
                        that.setChoseStatus();
                    }
                });
            },

            /*
             * @func 复制粘贴内容提示
             */
            tipCopyMsg:function(msg){
                var  d=dialog({
                    title:"提示",
                    content:msg
                });
                d.showModal();
                setTimeout(function(){
                    d.close().remove();
                },1200);
            }
		}
		new roomModule();
	}
	$(function(){
		 //模块方法初始化
		  exports.inite=function($$,data){
		  		distriAddRoomFun($$,data);
		  };
	});
});