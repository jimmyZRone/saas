define(function(require,exports,module){
	var $ = require('jquery');
		require("selectByM")($);
		require("placeholder")($);
		require("radio")($);
	 	 require("validForm")($);
		 require("combobox")($);
	var uplodify = require("uplodify"), //图片上传
		hash = require('child_data_hash'),
		dpk=require("calendar"),
		ajax=require("Ajax"),
	 	 dialog = require("dialog"); //弹窗插件
	var navigators = require("navigatortest");  //浏览器版本检测
	var calendar = require("calendar");

	var isOurHourse=0,//是否是属于后台读取的房源数据
		isSavedSelfXQ=0,//是否已添加了自定义小区
		ajaxLoading;


	var CACHEPAYWAYHZ=[],//缓存计费方式合租数据
		CACHEPAYWAYZZ=[],//缓存计费方式整租数据
		CACHEFEENAMEUNIT=[],//缓存费用名称数据
		CACHETEMPFEE={};

	var distriAddHouseFun=function($$,data){
		function distrZone(){
			this.init();
		}
		distrZone.prototype={
			init:function(){
				var that=this;
				that.bind();
				that.iniUpload();
				that.iePlshder();
				that.choseAllSupporting();
				that.checkValidForm();
				that.switchType();
				that.searchXqList();
				that.addSelfHouseResource();
                that.setDetainPay();
                that.addFeesItem();
                that.setChosenFeeItem();
                that.setCachePayway();
                that.delFeeItem();
//              that.iniPayWayRender();
				that.bindPersonalRoomConfig();
				that.bindChoseDelEvt();
				that.addNewSelfRoomConfig();
				that.bindRemoveImg();
				var _form=$(".distributed_add_hourse_form",$$);
				hash.hash.savehash('roomformValid',$(':first',$$));
				that.cacheFormFileDta(_form);//缓存图片文件
				that.cacheFeeNames();
				that.bindCancle(_form);
				that.iniSelAllOps(_form);
				that.yytzRoomInfo();
				that.showDialog();
			},
			//检测show弹出弹窗
			showDialog:function(){
				var url = document.URL;
	   			var string = "show";
	   			if(url.indexOf(string)>0){
	   				if($(".stop-house",$$).size()>0){
	   					$(".stop-house",$$).trigger('click');
	   				}else if($(".yytz-room",$$).size()>0){
	   					$(".yytz-room",$$).trigger('click');
	   				}
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
					var  bl=hash.hash.ischange('roomformValid',$(':first',$$));
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
				var obj = $(".roomconfig-diy-txt ul.roomconfig-diy-txt-auto",$$);
				obj.find(".checkbox").off().on("click",function(){
						if($(this).hasClass("canntchoose")){
							return false;
						}
						$(this).toggleClass("checked");
						if($(this).hasClass("checked")) $(this).children().show();
						else $(this).children().hide();
					});
			},
			/*
			 *@func 绑定自定义房间全选/删除/新增事件
			 *
			 * */
			bindChoseDelEvt:function(){
				var obj = $(".roomconfig-diy-txt ul.roomconfig-diy-txt-auto",$$),
					that=this;
				//自定义房间配置全选
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
				// 自定义房间配置多删
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
				   			that.deleteauto(deletedata);
					});
			},
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
			addNewSelfRoomConfig:function(){
				var that=this;
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
												for(var n in configs_list){
													var str = "<li><label class='checkbox'><span class='choose ifont1'>&#xe60c;</span></label><input class = 'config-auto-edite fl' type='text' value='"+configs_list[n].val+"' data-config-id = '"+configs_list[n].key+"'/></li>";
													obj.append(str);
												}

												distrZone.prototype.bindSubCbbox();
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
			bindPersonalRoomConfig:function(){
				var that=this;
				var obj = $(".roomconfig-diy-txt ul.roomconfig-diy-txt-auto",$$);
				//自定义房间配置
				$(".roomconfig_dy",$$).off("click").on("click",function(){
					var obj_choose = $(this).parent().prev();
						obj.html("");
						var str = "";
						$(".check-box-o,.check-box-a",$$).hide();
						$(".check-box-o",$$).each(function(){
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
								obj.find(".roomconfig-diy-txt-a .checkbox").addClass("checked").children().show();
							}else{
								obj.find(".roomconfig-diy-txt-a .checkbox").removeClass("checked").children().hide();
							}
						$(".roomconfig-diy-txt",$$).slideDown(300);
						$(this).hide();
						that.bindSubCbbox();
				});
				$(".config-auto-submit",$$).off("click").on("click",function(){
					var obj_choose = $(".roomconfig_dy",$$).parent().prev();
					var result_checkal = true;   //有全选
					$(".check-box-o",$$).remove();
						var str="";
						obj.find("li").each(function(){
							var cname=$(this).find('.config-auto-edite').val(),
								cindex=$(this).find('.config-auto-edite').attr("data-config-id");
							if($(this).children("label").hasClass("canntchoose")){
								if($(this).find("label").attr("ischecked")){
									str += '<div class="check-box check-box-o canntchoose"><label class="checked"><span class="choose ifont1" style="display:inline;">&#xe60c;</span></label> <input name="centralized_add_house_room_config" type="checkbox" checked="checked" value="'+cindex+'"/> <span><a href="javascript:;">'+cname+'</a></span></div>';
									return true;
								}
								result_checkal = false; //没有全选
								str += '<div class="check-box check-box-o canntchoose"><label><span class="choose ifont1">&#xe60c;</span></label> <input name="centralized_add_house_room_config" type="checkbox"  value="'+cindex+'"/> <span><a href="javascript:;">'+cname+'</a></span></div>';
							}else{
								if($(this).find("label").hasClass("checked")){
									str += '<div class="check-box check-box-o"><label class="checked"><span class="choose ifont1" style="display:inline;">&#xe60c;</span></label> <input name="centralized_add_house_room_config" type="checkbox" checked="checked" value="'+cindex+'" /> <span><a href="javascript:;">'+cname+'</a></span></div>';
									return true;
								}
								result_checkal = false; //没有全选
								str += '<div class="check-box check-box-o"><label><span class="choose ifont1">&#xe60c;</span></label> <input name="centralized_add_house_room_config" type="checkbox" value="'+cindex+'" /> <span><a href="javascript:;">'+cname+'</a></span></div>';
							}
						});
						$(".roomconfig-diy-txt",$$).hide();
						obj_choose.before(str).show();
						$(".roomconfig_dy",$$).show();
						if(result_checkal === false){
							$(".check-box-a",$$).children("label").removeClass("checked").children().hide();
							$(".check-box-a",$$).children(".select-all").children().text("全选");
						}else{
							$(".check-box-a",$$).children("label").addClass("checked").children().show();
							$(".check-box-a",$$).children(".select-all").children().text("取消全选");
						}
						that.choseAllSupporting();
				});
			},
            /*
             * @func 缓存计费方式数据
             */
			setCachePayway:function(){
				CACHEPAYWAYHZ=[];
				CACHEPAYWAYZZ=[];
				var _par=$("#temp-add-fee",$$).find(".cache-pay-way-data").find(".selectByMO ul li"),
					cache=[];
				$.each(_par,function(i,o){
					var jitem={};
					jitem[$(o).attr("selectval")]=$(o).text();
					cache.push(jitem);
				});
				$.each(cache,function(i,o){
					$.each(o,function(j,item){
						var cc={};
						cc["id"]=j;
						cc["text"]=item;
						CACHEPAYWAYHZ.push(cc);
					});
				});
				$.each(CACHEPAYWAYHZ,function(m,n){
//					console.log(n);
					var k=n.id;
					//配置整租只有前面两个选项，数据库有修改的话这里也需要对应做下修改
					if(k==1 || k==2 || k==5 || k==6 || k==7){
						CACHEPAYWAYZZ.push(n);
					}
				});
				// console.log(CACHEPAYWAYHZ);//合租计费方式数据
				// console.log(CACHEPAYWAYZZ);//整租计费方式数据
//				this.iniPayWayRender();
			},
            /*
             * @func 渲染计费方式
             * @param 有参数只渲染指定元素 无参数 渲染全部
             */
			iniPayWayRender:function(cur){
				var _ele=$(".distributed_add_hourse_form",$$).find(".radio-type"),type,that=this,
					cacheData=[],
					_target=$(".distributed_add_hourse_form",$$).parent().find(".way-cal-fee").not(".edited");
					console.log(_ele.length);
				$.each(_ele,function(i,o){
					var vv=$(o).find("input[name='rental_way']");
					if(vv.attr("checked")==true || vv.attr("checked")=="checked"){
						type=vv.val();
					}
				});
				if(type==1){
					cacheData=CACHEPAYWAYHZ;
				}else{
					cacheData=CACHEPAYWAYZZ;
				}
				var temp='';
				$.each(cacheData,function(i,o){
					temp+='<li selectval="'+o.id+'">'+o.text+'</li>';
				});
				if(cur && cur!=undefined && cur!=""){
					_target=cur.find(".way-cal-fee").not(".edited");
				}
//				console.log(_target.length);
				//重新生成新的计费方式模板
				$.each(_target,function(j,item){
					var _el=$(item).parent().find(".selectByMO");
					$.each(_el,function(i,o){
						$(o).find("ul").html("").html(temp);
						$(item).val("选择计费方式").attr("selectval","-1");
					});
				});
				that.bind();//重新绑定下拉框事件
			},
            /*
             * @func 设定押/付的值
             */
            setDetainPay:function(){
                var form=$(".distributed_add_hourse_form",$$),v1,v2,
                    _dv=form.find("input[name='detain']").attr("selectval"),
                    _pv=form.find("input[name='pay']").attr("selectval");
                var p1=form.find("input[name='detain']").parent().find(".selectByMO").find("li"),
                    p2=form.find("input[name='pay']").parent().find(".selectByMO").find("li");
                $.each(p1,function(i,o){
                    var tv=$(o).attr("selectval");
                    if(_dv==tv){
                        v1= $.trim($(o).text());
                    }
                });
                $.each(p2,function(i,o){
                    var cv=$(o).attr("selectval");
                    if(_pv==cv){
                        v2= $.trim($(o).text());
                    }
                });
                form.find("input[name='detain']").val(v1);
                form.find("input[name='pay']").val(v2);
            },
			/*
			 * @func 切换整租/合租选项
			 */
			switchType:function(){
				var that=this;
				$(".radio-type label",$$).off().on("click",function(){
					 var cur=$(this),
					 	 _par=cur.parents("li");
					 if(!cur.hasClass("checked")){
						_par.find(".checked").removeClass("checked");
					 	_par.find(".r-select").hide();
					 	_par.find(".r-default").show();
					 	cur.addClass("checked");
					 	cur.find(".r-select").show();
					 	cur.find(".r-default").hide();
						_par.find("input").attr('checked',false);
					 	cur.next().attr("checked",true);
					 	var ctype=cur.next().val();
					 	if(ctype==1){
					 		$("#decentralized-type-zz",$$).hide().find("input").not(".selectByMT").val("").trigger("blur");
					 	}else{
					 		$("#decentralized-type-zz",$$).show();
					 	}
					 }
					 that.iniPayWayRender();//重新渲染计费方式
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
				var that=this;
				//单选
				$('.radio',$$).each(function(){
					$(this).click(function(){
						$(this).Radios();
					})
				});
				//下拉
				$.each($(".selectByM",$$),function(i,o){
					if($(this).attr("hasevent") && $(this).attr("hasevent")=="2"){
	                    $(o).selectObjM(1,that.showCbTxt);
	                }else if($(o).attr("hasevent") && $(o).attr("hasevent")=="3"){
						 $(o).selectObjM(1,that.chosePayNameCb);//费用名称回调
					}else{
	                    $(o).selectObjM();
	                }
				});
				//日期
//				wdatePicker

				$(".wdatePicker",$$).click(function(){
					dpk.inite();
				});
			   //停用信息获取
			   $(".stop-house",$$).off("click").on("click",function(){
			   	var _this_auto = $(this);
			   		// if($(this).hasClass("stop-click")) return false;
			   		$(this).addClass("stop-click");
			   		var data_url = $(this).attr("data-url");
			   		ajax.doAjax("get",data_url,"",function(json){
			   			if(json.status == 1){
			   				var data = json.data,
			   					  start_time_c = data.start_time_c,
			   					  end_time_c = data.end_time_c,
			   					  remark = data.remark;
				   				var d = dialog({
									title: '<i class="ifont">&#xe62e;</i><span>停用房间</span>',
									content:$("#house-detail-distri-stop").html(),
									cancel:function(){
										$(".clicked").removeClass("clicked");
									}
								});
								$(".ui-dialog-button").hide();
								 d.showModal();
								 var data_input=$("#house_detail_stop_info").find(".date-input");
								 $.each(data_input, function(i,o) {
								 $(o).click(function(){
									 	dpk.inite();
									});
								});
								that.closeOverlay(d);
								var tt=$("#house_detail_stop_info");
								tt.find("input[name='endtime_start']").val(json.data.start_time_c);
								tt.find("input[name='endtime_end']").val(json.data.end_time_c);
								tt.find("textarea[name='notice']").val(json.data.remark);
								tt.find("input[name='stop_id']").val(json.data.stop_id);
								that.validStopRoomForm(d);
			   			}else{
			   				 var da =dialog({
								title: '提示',
								content:json.data,
								okValue: '确认',
								ok: function () {
									_this_auto.removeClass("stop-click");
									da.close();
								}
							});
	                        da.showModal();
			   			}
			   			// $(".ui-dialog-close",".ui-dialog-header").hide();
			   		});
			   });
			},
			/*
		 *@func 初始停用遮罩表单
		 * */
		validStopRoomForm:function(d){
			var that=this;
			$("#house_detail_stop_info").Validform({
					btnSubmit : "#housedetail-stop-info-save",
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
		            	$("#house_detail_stop_info").find("input,textarea").trigger("blur");
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
			var data="",_txt=$(form).find(".ipx-txt"),that=distrZone.prototype;
			$.each(_txt,function(j,item){
				var vv=$(item).val();
				if(j==0){
					data+=$(item).attr("name")+"="+vv;
				}else{
					data+="&"+$(item).attr("name")+"="+vv;
				}
			});
			var cur=$(form).find("#housedetail-stop-info-save"),
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
			 * @func 绑定上传图片删除事件
			 */
			bindRemoveImg:function(){
				 $(".uploader-area",$$).find(".deleteImg").unbind().bind("click",function(){
                		$(this).parents(".upload-imgview").parent().find('.uploadview-wrapper').css({width:'auto',height:'auto',overflow:'inherit'});
                		$(this).parents(".upload-imgview").remove();
                });
			},
			/*
			 * @func 小区匹配搜索
			 */
			searchXqList:function(){
       		  $('.decentralized-name',$$).each(function(){
	            var input = $(this);
	            var dom = input.parent('li');
            		input.combobox({
                url:input.attr("actionurl"),
                param_name: 'search',
                title_name: 'community_name',
                commbox_selector: '.commbox',
                width: 498,
                result:"data",
                prompt: '<p>若没有查询到小区<span class="decentralized-add-new">请点此添加</span></p>',
                item_template:'<p community_id=":community_id">:community_name</p>',
//              height: 200,
                min_char:1,
                padding: 10,
                offset: { top: 32},
                callback: {
                    select: function(obj) {
                    		isOurHourse=1;
                    		// $(".decentralized-add-new",$$).hide();
                    		$(".col-addr",$$).show();
                    		$("#distributed-save-addTr").removeClass("unclickable");
                    		input.val(obj.community_name).attr("community_id",obj.community_id);
                    		$("#fx-addr",$$).html(obj.address);
                    		$("#fx-addr",$$).next().val(obj.address);
                    },
                    choose: function(obj) {
                    		if(obj && obj!=undefined){
                    			isOurHourse=1;
                    			// $(".decentralized-add-new",$$).hide();
                    			$(".col-addr",$$).show();
                    			$("#distributed-save-addTr").removeClass("unclickable");
                    			input.val(obj.community_name).attr("community_id",obj.community_id);
                    			$("#fx-addr",$$).html(obj.address);
                    			$("#fx-addr",$$).next().val(obj.address);
                    		}
                    },
	                notdata: function(obj){
                    		isOurHourse=0;
                    		input.attr("community_id","");
                			$("#fx-addr",$$).text("");
                			$("#fx-addr",$$).next().val("");
                    		if($.trim(obj.val())!=""){
                    			$(".decentralized-add-new",$$).show();
                    			$(".col-addr",$$).hide();
                    			$("#distributed-save-addTr").addClass("unclickable");
                    		}
	                }
	                }
	            });
	        });

			},
			/*
			 * @func 新增自定义小区房源名称事件绑定
			 */
			addSelfHouseResource:function(){
                var that=this;
				$('.distributed_add_whole_house',$$).on("click",".decentralized-add-new",function(){
					var ctemp=$($("#dpt-addHouse-temp").html());
					var community_name = $('.distributed_add_whole_house input[name=community_name]',$$).val();
					ctemp.find('input[name=community_name]').val(community_name);
					var d = dialog({
						title: '<i class="ifont">&#xf0077;</i><span>新增小区</span>',
						content: ctemp
					});
					d.showModal();
                    that.iniSelEle();
                    that.checkAreaForm();
                    that.bindCancleEvt(d);
				});
			},
            /*
             * @func 切换区域加载对应商圈数据处理
             */
            linkSelect:function(ev){
                var type = "post",
                    url=$(".requstZoneUrl").attr("url"),
                    areaId=$(".distri-define-area").find(".selectedLi").attr("selectval");
                var data = {
                    "area_id":ev
                }
                ajax.doAjax(type,url,data,function(json){
                    var obj = $(".distri-add-new-circle");
                    var obj_UL = obj.find(".selectByMO").children("ul");
                    obj.find(".selectByMT").val("商圈").attr("selectval","");
                    obj_UL.empty();
                    var cityDate = json.data;
                    for(var n in cityDate){
                        var str = "<li selectVal='"+cityDate[n].business_id+"'>"+cityDate[n].name+"</li>";
                        obj_UL.append(str);
                    }
                    obj.find("li:eq(0)").trigger("click");
                    obj.selectObjM();
                });
            },
            /*
             * @func 初始化下拉选择框
             */
            iniSelEle:function(){
                var that=this;
                $.each($(".distri-add-new-areas"),function(i,o){
                    if($(this).attr("hasevent")){
                        $(o).selectObjM(1,that.linkSelect);
                    }else{
                        $(o).selectObjM();
                    }
                });
            },
            /*
             * @func 小区表单验证
             */
            checkAreaForm:function(){
                var that=this;
                $(".dpt-add-new-form").Validform({
                    btnSubmit : ".save-distri-add-other-area",
                    showAllError : true,
                    tiptype : function(msg,o,cssctl){
                        var objtip=o.obj.parents("li").find(".check-error");
                        cssctl(objtip,o.type);
                        objtip.text(msg);
                    },
                    beforeCheck:function(){
                        $(".dpt-add-new-form").find("input,textarea").trigger("blur");
                    },
                    datatype : {
                        "areaName":function(gets,obj,curform,regxp) {
                            if($.trim(gets)=="") return false;
                            if($.trim($(obj).attr("selectval"))=="0") return false;
                            else return true;
                        }
                    },
                    callback : function(form){
                        that.addOwnAreaAct(form);
                        return false;
                    }
                });
                var _inputs=$(".dpt-add-new-form").find("input[type='text']");
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
             *@func 添加自定义的小区数据
             * */
            addOwnAreaAct:function(form){
               var  _btn=$(".save-distri-add-other-area"),
                   _url=_btn.attr("url");
                if(!_btn.hasClass("clicked")){
                    _btn.parent().find(".clicked").removeClass("clicked");
                    _btn.addClass("clicked").text("保存中...");
                    var data={
                        community_name:$(form).find(".compounds-name").val(),
                        business_name:$(form).find(".business").val(),
                        business_id:$(form).find(".business").attr("selectval"),
                        area_name:$(form).find(".area").val(),
                        area_id:$(form).find(".area").attr("selectval"),
                        address:$(form).find(".dpt-own-addres").val()
                    };
                    ajaxLoading=ajax.doAjax("post",_url,data,function(json){
                        _btn.removeClass("clicked").text("保存");
                        var msg=json.message;
                        if(json.status==1){
                        	msg='<p>小区提交成功，为了保证数据准确，我们将会在2小时内进行审核</p>'
                        		+'<p>请耐心等待。 如有需要请联系028-83375579</p>';
                        }
                        var da =dialog({
							title: '提示',
							content:msg,
							okValue: '确认',
							ok: function () {
								da.close().remove();
								if(json.status==1){
	                              $(".cancle-add-own-area").trigger("click");
	                            }
							}
						});
                        if(json.status==1){
                            isSavedSelfXQ=1;
                        }
                        da.showModal();
                    });
                }

            },
            /*
             *@func 绑定取消事件
             * */
            bindCancleEvt:function(d){
                $(".cancle-add-own-area").off().on("click",function(){
                    if(ajaxLoading) ajaxLoading.abort();
                    d.close().remove();
                });
            },
			/*
			 *@func 表单参数收集
			 * */
			getParams:function(form){
				var that=this,
					_paramTxt="",_paramRadio="",
					_paramCbox="",_paramFile="",cb="",
					_param="",cr="",ct="",cf="",
					_range=$(form).find(".dataFormRemark").find("li");
				_paramTxt=_range.find("input[type='text']");
				_paramRadio=_range.find("input[type='radio']");
				_paramFile=_range.find(".uploader-area");
				_paramCbox=_range.find(".checkbox-area");
				//checkbox
				$.each(_paramCbox,function(i,o){
					 var name=o.getAttribute("fm"),
					  	  signal_item="",sdt="";
					  	  imgUnits=$(o).find(".check-box-o").find("input[type='checkbox']");
					  	 $.each(imgUnits,function(j,item){
					  	 	 var  dt="";
					  	 	 if($(item).attr("checked")=="checked" || $(item).attr("checked")=="true"){
					  	 	 	if(j==0){
					  	 	 		dt+=$(item).val();
					  	 	 	}else{
					  	 	 		dt+=","+$(item).val();
					  	 	 	}

					  	 	 }
				 			sdt+=dt;
					  	 });
				 	signal_item+=""+name+"="+sdt;
				 	if(i==0){
				 		cb+=signal_item;
					}else{
						cb+="&"+signal_item;
					}
				});
//				console.log(cb);
				//radio
				$.each(_paramRadio,function(i,o){
				 	if($(o).attr("checked") == true || $(o).attr("checked") == "checked"){
				 		if(i==0){
				 			cr+=$(o).attr("name")+"="+$(o).val();
				 		}else{
				 			cr+="&"+$(o).attr("name")+"="+$(o).val();
				 		}
				 	}
				});
				//text
				$.each(_paramTxt,function(i,o){
					if($(o).attr("name") && $(o).attr("name")!=undefined){
						if(i==0){
							if($(o).attr("name")=="community_name"){
								ct+="community_name"+"="+$(o).val()+"&community_id"+"="+$(o).attr("community_id");
							}
						}else{
							if($(o).attr("name")=="detain"){
								ct+="&detain="+$(o).attr("selectVal");
							}else if($(o).attr("name")=="pay"){
								ct+="&pay="+$(o).attr("selectVal");
							}else{
								ct+="&"+$(o).attr("name")+"="+$(o).val();
							}
						}
					}
				});
				//imgs
				$.each(_paramFile, function(i,o) {
					  var name=o.getAttribute("fm"),
					  	  signal_item="",sdt="";
					  	  imgUnits=$(o).find(".upload-imgview");
					  	 $.each(imgUnits,function(j,item){
					  	 	 var  dt="";
					  	 	 if(j==0){
					  	 	 		dt+=$(item).find("input").val();
				 			}else{
				 					dt+=","+$(item).find("input").val();
				 			}
				 			sdt+=dt;
					  	 });
				 	signal_item+=""+name+"="+sdt;
				 	if(i==0){
				 		cf+=signal_item;
					}else{
						cf+="&"+signal_item;
					}
				});
//				console.log(cf);
				_param+=ct+"&"+cr+"&"+cb+"&"+cf;
				_param=_param.replace("&&","&");
				return _param;
			},
			/*
			 *@func 单独获取费用模块参数
			 * */
			getFeeFormData:function(form){
				var  _feeForm=$(form).find(".col-fees-form").find(".data-fee-li"),
					 feeData=[],cm="",cn="",ck="";
					$.each(_feeForm,function(i,o){
						var jitem={},
							_paramNames=$(o).find(".fee-item-name"),
							_paramWays=$(o).find(".way-cal-fee"),
							_paramPrices=$(o).find(".signal_price");
						jitem["fee_type_id"]=_paramNames.attr("selectval");
						jitem["payment_mode"]=_paramWays.attr("selectval");
						jitem["money"]=_paramPrices.val();
						var is_dd=$(o).find("input[data-name='du']"),
							is_date=$(o).find("input[data-name='cbdate']");
						if(is_dd && is_dd!=undefined && is_dd.length>0 && is_date && is_date!=undefined && is_date.length>0){
							jitem["du"]=is_dd.val();
							jitem["cbdate"]=is_date.val();
						}

						feeData.push(jitem);
					});
				feeData=JSON.stringify(feeData);
//				console.log(feeData);
				return feeData;
			},
			/*
			 *@func 表单提交
			 * */
			submitForm:function(form){
				var that=this,_btn=$("#distributed-save-addTr",$$),
					url=_btn.attr("addurl"),
					_isEdit=_btn.attr("editid"),
					feeData=that.getFeeFormData(form),
					data=that.getParams(form);
//				console.log(feeData);
//return;
				if(_isEdit && _isEdit!="" && _isEdit!=0){
					url=_btn.attr("editurl");
					var type_name="",type_val="";
					if(_btn.attr("house-id")!="" && _btn.attr("house-id")!=undefined){
						type_name="house_id";
						type_val= _btn.attr("house-id");
					}else{
						type_name="room_id";
						type_val= _btn.attr("room-id");
					}
					data+="&"+type_name+"="+type_val;
				}
				data+="&feeItem="+feeData;
				if(!_btn.hasClass("clicked")){
					_btn.parent().find(".clicked").removeClass("clicked");
					_btn.addClass("clicked").text("保存中...");
					ajaxLoading=ajax.doAjax("post",url,data,[function(json){
						_btn.removeClass("clicked").text("保存").removeAttr("request");
						if(json.status==0){
							_btn.attr("request",Math.floor(Math.random()*1000));
						}
						var  d=dialog({
	            		 		title:"提示",
	            		 		content:json.message,
	            		 		okValue:"确定",
	            		 		ok:function(){
	            		 			d.close().remove();
	            		 			var tag1= WindowTag.getCurrentTag();
		            		 		if(json.status==1){
		            		 			WindowTag.closeTag(tag1.find('>a:first').attr('url'));//关闭当前tag
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
				    						var ctag = WindowTag.getTagByUrlHash(json.p_url);
		            						if(ctag){
						    					window.WindowTag.selectTag(ctag.find(' > a:first').attr('href'));
						    					window.WindowTag.loadTag(ctag.find(' > a:first').attr('href'),'get',function(){});
						    				}else{
						    					window.WindowTag.openTag("#"+json.p_url);
						    				}
		            		 			}else{
		            		 				var ctag = WindowTag.getTagByUrlHash(json.p_url);
											if(ctag){
						    					window.WindowTag.selectTag(ctag.find(' > a:first').attr('href'));
						    					window.WindowTag.loadTag(ctag.find(' > a:first').attr('href'),'get',function(){});
						    				}else{
						    					window.WindowTag.openTag("#"+json.p_url);
						    				}
		            		 			}
		            		 		}
	            		 		}
	            		 	});
	            		 	d.showModal();
							$(".ui-dialog-close",".ui-dialog-header").hide();
					},function(){
						_btn.removeClass("clicked").text("保存").removeAttr("request");
					}]);
				}
			},
			/*
			 * @func 表单验证
			 */
			checkValidForm:function(){
				var that=this;
				$(".distributed_add_hourse_form",$$).Validform({
					btnSubmit : "#distributed-save-addTr",
					showAllError : true,
					ignoreHidden : true,
					tiptype : function(msg,o,cssctl){
						var _isPar=o.obj.parents(".errorBox"),
							objtip;
						if(_isPar && _isPar.length>0){
							objtip=o.obj.parents(".errorBox").find(".check-error");
						}else{
							objtip=o.obj.parents("li").find(".check-error");
						}
		                cssctl(objtip,o.type);
		                objtip.text(msg);
		            },
		            beforeCheck:function(){
		            		$("input,textarea",$$).trigger("blur");
		            },
		            datatype : {
		            		"validFour":function(gets,obj,curform,regxp){
		            			 gets=gets.replace(/ /g,"");
		            			 if(gets=="") return false;
		            			 if(gets.length<=4){
		            				return true;
		            			 }
		            			 return false;
		            		},
		            		"validThree":function(gets,obj,curform,regxp){
		            			 gets=gets.replace(/ /g,"");
		            			  if(gets=="") return false;
		            			   if(gets.length<=3){
		            				return true;
		            			 }
		            			 return false;
		            		},
		            		"validEight":function(gets,obj,curform,regxp){
		            			 gets=gets.replace(/ /g,"");
		            			  if(gets=="") return false;
		            			   if(gets.length<=8){
		            				return true;
		            			 }
		            			 return false;
		            		},
		            		"nint":function(gets,obj,curform,regxp) {
		            			var reg1=/^([1-9][0-9]*)$/;//匹配正整数
		                       gets=gets.replace(/ /g,"");
			                   if(gets=="") return true;
			                   if(reg1.test(gets)){
			                   		return true;
			                   }
							   return false;
		               },
		            		"fyNumber":function(gets,obj,curform,regxp) {
		                   	gets=gets.replace(/ /g,"");
			                   if(gets=="") return true;
			                   if(gets.length<=20){
			                   		return true;
			                   }
							   return false;
		               },
		               "roomInner":function(gets,obj,curform,regxp) {
		               		gets=gets.replace(/ /g,"");
		               		var regxp=/^\d$/,regxp2=/^30$/;
		               		 if(regxp.test(gets)==true || regxp2.test(gets)==true){
		               			return true;
		               		}
		               		return false;
		               },
		             	"pint":function(gets,obj,curform,regxp) {
		                   var reg1=/^([1-9]|10)$/;//匹配1-10的正整数
		                   	gets=gets.replace(/ /g,"");
			                   if(gets=="") return true;
			                   if(reg1.test(gets)==true){
			                   		return true;
			                   }
							   return false;
		               },
		              "floatGroup":function(gets,obj,curform,regxp) {
		                  var reg1=/^[1-9][0-9]{0,5}(\.[0-9]{0,2})?$/,//匹配正整数
		                   	   reg2=/^[0-9]{1}\.[0-9]{0,2}$/;//匹配正浮点数
		                   	   gets=gets.replace(/ /g,"");
			                   if(gets=="") return false;
			                 if(reg1.test(gets)){
			                 	 return true;
			                 }else if(reg2.test(gets)){
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
		               "nowDegree":function(gets,obj,curform,regxp) {
							var reg1=/^[0-9][0-9]{0,6}(\.[0-9]{0,2})?$/,//匹配正整数
		                   	   reg2=/^[0-9]{1}\.[0-9]{0,2}$/;//匹配正浮点数
		                   	   gets=gets.replace(/ /g,"");
			                   if(gets=="") return false;
			                 if(reg1.test(gets)){
			                 	 return true;
			                 }else if(reg2.test(gets)){
			                 	return true;
			                 }
							 return false;
		               },
		               "selType":function(gets,obj,curform,regxp) {
		                  	 var isNull=$(obj).attr("selectval");
		                  	 if(isNull=="-1"){
		                  	 	return $(obj).attr("nullmsg");
		                  	 }else{
		                  	 	return true;
		                  	 }
		               },
		               "price-rent":function(gets,obj,curform,regxp){
		               	   gets = gets.trim()
		               	  if(gets == 0) return"租金不能为0";
		               }
		            },
		            callback : function(form){
		            		if($("#distributed-save-addTr",$$).hasClass("unclickable")){
			            	    var  bl,msg;
			            		//一系列判断
			            		 if(isOurHourse==0 && $.trim($("#fx-addr").text())==""&& isSavedSelfXQ == 0){
			            		 	bl=false;
			            		 	msg="请输入正确的小区信息";
			            		 }else if(isSavedSelfXQ==1){
			            		 	bl=false;
			            		 	msg="添加小区信息正在审核中,请稍后再试";
			            		 }
			            		 if(bl==false){
			            			 var d=dialog({
										title: '提示',
										content:msg,
										okValue: '确认',
										ok: function () {
											d.close().remove();
										}
									});
			            		 	d.showModal();
			            		 }
		            		}else{
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
		            					var bl=hash.hash.ischange('roomformValid',$(':first',$$));
		            					var bl1=that.differCacheFile(form);
		            					if(bl==true || bl1==false){
		            						that.submitForm(form);
		            					}else{
	            						that.stayInCurentPage(form);
//		            						var tag = WindowTag.getCurrentTag();
//										WindowTag.closeTag(tag.find('>a:first').attr('url'));
		            					}
					     			return false;
		            				}
		            			}else{
		            					var bl=hash.hash.ischange('roomformValid',$(':first',$$));
		            					var bl1=that.differCacheFile(form);
		            					if(bl==true || bl1==false){
		            						that.submitForm(form);
		            					}else{
	            						that.stayInCurentPage(form);
//		            						var tag = WindowTag.getCurrentTag();
//										WindowTag.closeTag(tag.find('>a:first').attr('url'));
		            					}
					     			return false;
		            				}
		            		}
		            }
				});
				var _inputs=$(".distributed_add_hourse_form",$$).find("input[type='text']");
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
			*@func 未发生数据修改，停留在当前页面并给予提示
			*/
			stayInCurentPage:function(form){
				var d = dialog({
					title: '提示信息',
					content:'数据没有发生修改，无法提交！',
					okValue: '确定',
					ok: function () {
						d.close();
					}
				});
				d.showModal()
			},
			/*
			 * @func 房间配置全选
			 */
			choseAllSupporting:function(){
		 		$(".distributed_add_hourse_form").find('.check-box-o').find("label").off().on('click',function(){
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
				$(".distributed_add_hourse_form").find(".check-box-a").off().on("click",function(){
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
				if($('#distri_house_add_file_upload2',$$) && $('#distri_house_add_file_upload2',$$).length>0){
					uplodify.uploadifyInits($('#distri_house_add_file_upload2',$$),$("#distri_house_add_col",$$));
				}else{
					uplodify.uploadifyInits($('#distri_house_edit_file_upload2',$$),$("#distri_house_edit_col",$$));
				}
			},
			/*
			 * @func 添加费用交互处理
			 */
			addFeesItem:function(){
				var that=this;
				$(".add_distri_fee",$$).off().on("click",function(){
					var cur=$(this);
					var dynamicTemp=that.genFeeItems();//动态生成费用名称模板
					if(dynamicTemp!=""){
						$("#temp-add-fee",$$).find(".triger-drag-down .selectByMO").html(dynamicTemp);
					}
					var temLen=$("#list-fee-distri",$$).find(".data-fee-li").length,
						dataLen=CACHEFEENAMEUNIT.length;
					if(temLen==dataLen){
						cur.parent().addClass("none");
						return;
					}else{
						cur.parent().removeClass("none");
					}
					var emtyTemp=$("#temp-add-fee",$$).html();
					var  _li=document.createElement("li");
					_li.className="list-fee-item data-fee-li";
					_li.innerHTML=emtyTemp;
					cur.parent().before(_li);
					that.bind();
					that.delFeeItem();
					//默认选中下拉框第一项选项,只触发刚添加的那条
					$.each(cur.parent().prev().find(".selectByMO ul").find("li:eq(0)"),function(j,k){
						$(k).trigger("click");
					});
					var s=that.genFeeItems("secFee");//每生成一次模板都要重新覆盖之前的
					that.setFeeItemtemp(s);
					that.iniPayWayRender(cur.parent().prev());
					var _inputs=$("#list-fee-distri",$$).find("input[type='text']");
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
				});
			},/*
			 *@func 遍历费用名称模板重新赋值
			 * */
			setFeeItemtemp:function(tp){
//				console.log(tp);
				var that=this,
					_par=$("#list-fee-distri",$$).find(".triger-drag-down");
				$.each(_par,function(j,k){
					$(k).find(".selectByMO").html("").html(tp);
				});
				var _ele=$("#list-fee-distri",$$).find(".triger-drag-down .selectByMO ul");
				$.each(_ele,function(j,k){
					var t=k,
						_chosenTxt=$(k).parent().parent().find(".selectByMT").val(),
						_chosenVal=$(k).parent().parent().find(".selectByMT").attr("selectval");
					var li='<li selectval="'+_chosenVal+'" class="selectedLi">'+_chosenTxt+'</li>';
					$(t).find("li:eq(0)").before(li);
				});
				$.each(_ele.find(".selectedLi"),function(j,k){
					$(k).trigger("click");
				});
			},
			/*
			 *@func 缓存页面费用数据
			 * */
			cacheFeeNames:function(){
				CACHEFEENAMEUNIT=[];
				var _par=$("#temp-add-fee",$$).find(".triger-drag-down").find(".selectByMO ul li"),that=this,
					cache=[];
				$.each(_par,function(i,o){
					var jitem={};
					jitem[$(o).attr("selectval")]=$(o).text();
					cache.push(jitem);
				});
				$.each(cache,function(i,o){
					$.each(o,function(j,item){
						var cc={};
						cc["id"]=j;
						cc["text"]=item;
						CACHEFEENAMEUNIT.push(cc);
					});
				});
			},

			/*
			 *@func 生成添加的费用模板的费用名称模板
			 * */
			genFeeItems:function(){
				var json={},_gen="";
				var _newCache=[],_dynamicJson={};
				var len=$("#list-fee-distri",$$).find(".data-fee-li").length;
				 if(len>0){
				 	var _par=$("#list-fee-distri",$$).find(".triger-drag-down").find(".selectByMT");
				 	$.each(_par,function(i,o){
				 		_newCache.push($(o).attr("selectval"));
				 	});
				 	var newArray = CACHEFEENAMEUNIT.slice(0);
				 	for(var i =0;i<newArray.length;i++){
				 		var lb=newArray[i];
				 		for(var j=0;j<_newCache.length;j++){
				 			if(_newCache[j]==lb.id){
				 				newArray[i] = undefined;
				 			}
				 		}
				 	}
				 	var newArray1 = [];
				 	for(var i =0;i<newArray.length;i++){
				 		if (newArray[i] == undefined) continue;
				 		newArray1.push(newArray[i]);
				 	}
				 	json["data"]=newArray1;
				 }else{
				 	json["data"]=CACHEFEENAMEUNIT;
				 }
				 _gen=template("add-roomfee-dynamic-temp",json);
				 return _gen;
			},
			/*
			 *@func 费用名称回调
			 * */
			chosePayNameCb:function(){
				var that=distrZone.prototype;
				var s=that.genFeeItems();//每生成一次模板都要重新覆盖之前的
				that.setFeeItemtemp(s);
			},
			/*
			 * @func 删除费用选项
			 */
			delFeeItem:function(){
				var that=this;
				$(".ico-del-fitem",$$).off().on("click",function(){
					var  cur=$(this),par=cur.parents(".data-fee-li");
					par.remove();
					var temLen=$("#list-fee-distri",$$).find(".data-fee-li").length,
						dataLen=CACHEFEENAMEUNIT.length;
					if(temLen==dataLen){
						$(".add_distri_fee",$$).parent().addClass("none");
						return;
					}else{
						$(".add_distri_fee",$$).parent().removeClass("none");
					}
					var s=that.genFeeItems();//每生成一次模板都要重新覆盖之前的
					that.setFeeItemtemp(s);
				});
			},
			/*
			 * @func 初始选中费用选项
			 */
			setChosenFeeItem:function(){
				var _ele=$("#list-fee-distri",$$).find(".data-fee-li").find(".selectByM");
				$.each(_ele,function(i,o){
					var _ob=$(o).find(".selectByMO").find("li.selectedLi").attr("selectval"),
						_obTxt=$(o).find(".selectByMO").find("li.selectedLi").text();
					$(o).find(".selectByMT").attr("selectval",_ob).val(_obTxt);
				});
			},
			/*
			 *@func 抄表函数回调
			 * */
			showCbTxt:function(cc,inp){
				var _el=inp.parents(".data-fee-li").find(".col-l-b");
				if(cc==3 || cc==4 || cc==5){
					_el.show();
					_el.find("input").removeAttr("ignore");
					_el.find(".check-error").show();
//					$.each(_el.find("input"),function(m,n){
//						$(n).attr("name",$(n).attr("data-name"));
//					});
				}else{
					_el.find("input").removeClass("Validform_error").attr("ignore","ignore").trigger("focus");
					_el.find(".check-error").text("").hide();
					_el.hide();
//					_el.find("input").removeAttr("name");
				}
		},
		/*
		 *@func 初始预约退租遮罩表单
		 * */
		validYytzRoomForm:function(d){
			var that=this;
			$("#roomCheckoutForm-detail").Validform({
					btnSubmit : "#room-tz-info-save-detail",
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
		            	$("#roomCheckoutForm-detail").find("input,textarea").trigger("blur");
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
			var data="",_txt=$(form).find(".ipx-txt"),that=distrZone.prototype;
			var data_id = $(".view-head",$$).find(".yytz-room").attr('room_id');
			var reser_back_id = $(".view-head",$$).find(".yytz-room").attr('reser_back_id');
			var house_id = document.URL.split("&house_id=")[1];
			data += "room_id=" + data_id;
			data += "&reser_back_id=" + reser_back_id;
			data += "&house_id="+house_id;
			$.each(_txt,function(j,item){
				var form_data = $(item).val();
				if (j==0){
					data += "&" + $(item).attr("name") + "=" + form_data;
				}else{
					data += "&" + $(item).attr("name") + "=" + form_data;
				}
			});
			var cur=$(form).find("#room-tz-info-save-detail"),
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
							content:$("#cover-room-yytz-detail",$$).html(),
							cancel:function(){
								$(".clicked").removeClass("clicked");
							}
						});
						$(".ui-dialog-button").hide();
						 d.showModal();
						 var data_input=$("#roomCheckoutForm-detail").find(".date-input");
						 $.each(data_input, function(i,o) {
						 $(o).click(function(){
							 	calendar.inite();
							 });
						});
						that.closeOverlay(d);
						var tt=$("#roomCheckoutForm-detail");
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
			  $(".restore-yytz-act").off().on("click",function(){
			  	 var cur=$(this),durl=cur.attr("rasb-url");
			  	  if(!cur.hasClass("clicked")){
			  	  	cur.addClass("clicked").text("撤销中...");
			  	  	$.get(durl,"",function(json){
			  	  		json=eval('('+json+')');
						cur.removeClass("clicked").text("撤销");
			  	  		if(json.status==1){
			  	 //  			var tag = WindowTag.getCurrentTag();
							// window.WindowTag.loadTag(tag.find(' > a:first').attr('href'),'get',function(){});
							// if(typeof json['list_url'] == 'string'){
			    // 				var ctag = WindowTag.getTagByUrlHash(json['list_url']);
			    // 				if(ctag){
			    // 					window.WindowTag.loadTag(ctag.find(' > a:first').attr('href'),'get',function(){});
			    // 				}
			    // 			}
			    var tag = WindowTag.getCurrentTag();
																	WindowTag.closeTag(tag.find('>a:first').attr('href'));
												    				var ctag = WindowTag.getTagByUrlHash(json['list_url']);
												    				if(ctag){
												    					window.WindowTag.selectTag(ctag.find(' > a:first').attr('href'));
												    					window.WindowTag.loadTag(ctag.find(' > a:first').attr('href'),'get',function(){});
												    				}
			  	  		}
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
            		// d.close().remove();
            		// if($(".clicked"))$(".clicked").removeClass("clicked");


            });


			  $(".house-restore-disable-act").off().on("click",function(){
            		 	 var cur=$(this),durl=cur.attr("data-url");
			  	  if(!cur.hasClass("clicked")){
			  	  	cur.addClass("clicked").text("恢复中...");
			  	  	$.get(durl,"",function(json){
			  	  		json=eval('('+json+')');
						cur.removeClass("clicked").text("恢复");
			  	  		if(json.status==1){
					    	var tag = WindowTag.getCurrentTag();
							WindowTag.closeTag(tag.find('>a:first').attr('href'));
		    				var ctag = WindowTag.getTagByUrlHash(json['list_url']);
		    				if(ctag){
		    					window.WindowTag.selectTag(ctag.find(' > a:first').attr('href'));
		    					window.WindowTag.loadTag(ctag.find(' > a:first').attr('href'),'get',function(){});
		    				}
			  	  		}
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
            		// d.close().remove();
            		// if($(".clicked"))$(".clicked").removeClass("clicked");
            });





		}
		}
		new distrZone();
	}
	$(function(){
		 //模块方法初始化
		  exports.inite=function($$,data){
		  		distriAddHouseFun($$,data);
		  };
	});
});
