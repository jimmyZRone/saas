define(function(require,exports,module){
	var $ = require("jquery"),
	loading=require("loading"),
	 	 ajax=require("Ajax"),ajaxLoading;
	 	 dialog = require("dialog"); //弹窗插件
	 	 require("selectByM")($);
	 	 require("radio")($);
		 require("validForm")($);
	var calendar = require("calendar");
	require("combobox")($);
	var hash = require('child_data_hash');
	var mod_statics = require('mod_statics');
	/*
	 * 添加公寓方法集合
	 */
	var modelInit = function($$){
		var depart_Info = {
		//商圈选项级联
		linkSelect : function(val){
			var type = "get";
			var urls = $(".centralized_Ind_CuteType1",$$).attr("business-url");
			var address = $(".centralized_Ind_CuteType1",$$).attr("selectval");
			var data = {
				"area_Id":address
			}
			ajax.doAjax(type,urls,data,function(sss){
				var obj = $(".selectByM:eq(1)",$$);
				var obj_UL = obj.find(".selectByMO").children("ul");
				obj.find(".selectByMT").val("商圈").attr("selectval","");
				obj_UL.empty();
				var cityDate = sss.data;
				for(var n in cityDate){
					var str = "<li selectVal='"+cityDate[n].business_id+"'>"+cityDate[n].name+"</li>";
					obj_UL.append(str);
				}
				obj.selectObjM();
			});
		},
		//修改页面下拉菜单赋值初始化
		setSelect : function(obj){
			var inp = obj.children(".selectByMT");
			var choose = obj.children(".selectByMO").find(".selectedLi");
			inp.val(choose.text());
			inp.attr("selectval",choose.attr("selectval"));
		},
		//表单验证
		checkForm : function($$){
			$(".jzf-form",$$).Validform({
				btnSubmit : ".btn2",
				showAllError : true,
				ignoreHidden : true,
				tiptype : function(msg,o,cssctl){
	                var objtip=o.obj.parents(".jzf-col").find(".check-error");
	                cssctl(objtip,o.type);
	                objtip.text(msg);
	           },
	           datatype : {
	           		"chooseGroup":function(gets,obj,curform,regxp) {
		                   if(obj.attr("selectVal") == ''){
		                   	 return obj.attr("choosenull");
		                   }
		              },
		              "ng0":function(gets,obj,curform,regxp){
		              	if(parseInt(gets) <= 0){
		              		return false;
		              	}
		              },
		              "checkrepeat":function(gets,obj,curform,regxp){
		              	var url = obj.attr("url");
		              	var data= {
		              		flat_name : gets
		              	};
		              	ajax.doAjax("get",url,data,function(json){
		              		if(json.status == 0){
		              			obj.addClass("Validform_error");
		              			obj.parent().next().addClass("Validform_wrong").removeClass("Validform_right").text("公寓已存在");
		              		}
		              	});
		              },
		              "checktszf":function(gets,obj,curform,regxp){
		              	if(!/^[a-zA-z0-9\u4E00-\u9FA5]*$/.test(gets)){
		              		return "公寓名不能含有特殊字符或空格";
		              	}
		              },
		              "float":function(gets,obj,curform,regxp) {
		                    var reg=/^\d+(\.\d+)?$/;
		                    if(!reg.test(gets)){return false;}
		                    if(gets.indexOf(".")>0){
		                    	if(gets.split(".")[1].length > 2) return "小数点后不能超过两位";
		                    }
		               },
		               "check-flat-address":function(gets,obj,curform,regxp){
		               		if($.trim(gets) == "") return obj.attr("nullmsg");
		               		//$("#show_addressdetail",$$).show();
		               		//$("#show_addressdetail ul",$$).html('');
		               		$('.chooseaddressauto',$$).show();
		               		//mapsearchs.chooseaddress($("#show_addressdetail ul",$$).html(''));
		               		mapsearchs.chooseaddress($('.chooseaddressauto',$$));
		               		if(obj.attr("point-lat") == "") return "未获取到地址坐标，请点击左侧按钮在地图中定位坐标";
		               },
		               "fee-check":function(gets,obj,curform,regxp){
		               	  if(gets/1 > 99999999.99){
		                    	return "整数部分不能超过八位";
		                    }
		               	  if(gets.indexOf(".") == 0){
		               	  	return "小数点不能位于第一位";
		               	  }
		               	  if(gets == 0){
		               	  	return "费用不能为0";
		               	  }
		               }
	           },
	           callback : function(form){
	           		if($(".btn2",form).hasClass("none-click")) return false;
	           		$(".btn2",form).addClass("none-click");
	           		depart_Info.submitForm($$);
	           		return false;
	           }
			});
			$(":input",$$).focus(function(){
				if($(this).hasClass("Validform_error")){
					$(this).css("background","none");
					$(this).parents(".jzf-col").find(".check-error").hide();
				}
			}).blur(function(){
				$(this).removeAttr("style");
				$(this).parents(".jzf-col").find(".check-error").show();
			});
		},
		//提交表单
		submitForm : function($$){
			var type = "post";
			var ulr = $(".btn2",$$).attr("url");
			var name = $("input[name='centralized_Depart_InfoJs_flat_name']",$$).val();   //公寓名称
			var room_Num = $("input[name='centralized_Depart_InfoJs_custom_number']",$$).val(); //房间编号
			var opera_Id = $("input[name='centralized_Depart_InfoJs_city_id']",$$).attr("selectval"); //区域
			var bussiness_Id = $("input[name='centralized_Depart_InfoJs_area_id']",$$).attr("selectval"); //商圈
			var longitude = $("input[name='centralized_Depart_InfoJs_address']",$$).attr("point-lng");
			var latitude = $("input[name='centralized_Depart_InfoJs_address']",$$).attr("point-lat");
			var address = $("input[name='centralized_Depart_InfoJs_address']",$$).val();   //详细地址
			var floor_Total = $("input[name='centralized_Depart_InfoJs_total_floor']",$$).val();  //楼层总数
			var floor_RoomTotal = $("input[name='centralized_Depart_InfoJs_group_number']",$$).val(); //每层房源数
			var rent_Style = $("input[name='rental_way']:checked",$$).val();     //2为整租1为合租
			var room_Nums = $("input[name='room_number']",$$).val();   //套内间数
			var fee_data = [];
			$(".forcloneauto",$$).each(function(){
				var fee_type_id = $(this).find("input[name='feechoose']").attr("selectval");
				var payment_mode = $(this).find("input[name='jfeechoose']").attr("selectval");
				var money = $(this).find("input[name='money']").val();
				var fee = {
					fee_type_id : fee_type_id,
					payment_mode : payment_mode,
					money : money
				}
				fee_data.push(fee);
			});
			var data = {
				"name" : name,
				"room_Num" : room_Num,
				"opera_Id" : opera_Id,
				"bussiness_Id" : bussiness_Id,
				"longitude":longitude,
				"latitude":latitude,
				"address" : address,
				"floor_Total" : floor_Total,
				"floor_RoomTotal" : floor_RoomTotal,
				"rent_Style" : rent_Style,
				"room_Nums" : room_Nums,
				"flat_id":"",
				"fee_data":fee_data
			};
			if($(".center-dpt",$$).attr("iseditpage")){
				var flat_id;
				flat_id = document.URL.split("flat_id=")[1];
				data.flat_id = flat_id;
			}
			if(hash.hash.ischange('centrallzed_Depart_InfoJs',$(':first',$$))){
				ajax.doAjax(type,ulr,data,depart_Info.callback);
			}else{
				if($(".btn2",$$).hasClass("close-this")){
						var tag = WindowTag.getCurrentTag();
						WindowTag.closeTag(tag.find('>a:first').attr('href'));
						return false;
				}
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
		//提交表单后回调
		callback : function(data){
			if(data.status == 1){
				var d = dialog({
					title: '提示信息',
					content:'保存成功',
					okValue: '确定',
					ok: function () {
						$(".btn2",$$).removeClass("none-click");
						d.close();
						$(".roomTypeOps",".total-house").find(".selectByMO ul").append('<li selectval="'+data.data.flat_id+'">'+data.data.flat_name+'</li>');
						mod_statics.bind(); 
						//关闭当前标签
					var tag = WindowTag.getCurrentTag();
					
					/*if(typeof data['landlord_url'] == 'string'){
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
		 			}*/
					
					if(typeof data['tag'] == 'string'){
	    				var ctag = WindowTag.getTagByUrlHash(data['tag']);
	    				if(ctag){
	    					window.WindowTag.selectTag(ctag.find(' > a:first').attr('href'));
	    					window.WindowTag.loadTag(ctag.find(' > a:first').attr('href'),'get',function(){});
	    				}else{
	    					window.WindowTag.openTag('#'+data.tag);
	    				}
	    			}if(typeof data['refresh_url'] == 'string'){
	    				var ctag = WindowTag.getTagByUrlHash(data['refresh_url']);
	    				if(ctag){
//	    					window.WindowTag.selectTag(ctag.find(' > a:first').attr('href'));
	    					window.WindowTag.loadTag(ctag.find(' > a:first').attr('url'),'get',function(){});
	    				}
	    			}
					WindowTag.closeTag(tag.find('>a:first').attr('href'));
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
		//删除模板
		deleteTtmplate : function($$){
			$(".del-trig",$$).off("click").on("click",function(){
				var that = $(this);
				//========
				var tr = that.parent().parent();
				var hnumber = parseInt(tr.find('td:eq(2)').text());
				if(hnumber > 0){
					var dd = dialog({
						title: '提示信息',
						content:'当前模板房间下存在房间数据，不能删除！',
						okValue: '确定',
						ok: function () {
							dd.close();
						}
					});
					dd.showModal();
				}else{
					var url = that.attr("url");
					var data = {};
					var type = "get";
					ajax.doAjax(type,url,data,function(data){
						if(data.status == 1){
							tr.remove();
						}else{
							var dd = dialog({
								title: '提示信息',
								content:data.data,
								okValue: '确定',
								ok: function () {
									dd.close();
								}
							});
							dd.showModal();
						}
					});
				}
			});
		}
	}
		/*
		 * @func: 地图检索
		 */
		var mapsearchs = {
			keyupval : '',
			/*
			 * @func:根据搜索条件获得目的地坐标
			 */
			search : function(){	
				var that = this;
				var dialog_contr = false;
				var keyuptime = null;
				$("input[name='centralized_Depart_InfoJs_address']",$$).keyup(function(){
					var searchaddress = $.trim($(this).val()); //详细地址
					if(searchaddress == ''){
						return false;
					}
					var searcharea = $("input[name='centralized_Depart_InfoJs_city_id']",$$).val();  //区域
					var searcharea_select = $("input[name='centralized_Depart_InfoJs_city_id']",$$).attr("selectval");
					if(searcharea_select == ""){
						searcharea = "" ;
						var dd = dialog({
								title: '提示信息',
								content:'请选择区域',
								okValue: '确定',
								ok: function () {
									dd.close();
									dialog_contr = false;
								}
						});
						if(dialog_contr === false){
							dialog_contr = true;
							dd.showModal();	
						}
						return false;
					}
					var searchbusiness = $("input[name='centralized_Depart_InfoJs_area_id']",$$).val();  //商圈
					var searchbusiness_select = $("input[name='centralized_Depart_InfoJs_area_id']",$$).attr("selectval");
					if(searchbusiness_select == ""){
						searchbusiness = "";
						var dd = dialog({
								title: '提示信息',
								content:'请选择商圈',
								okValue: '确定',
								ok: function () {
									dd.close();
									dialog_contr = false;
								}
						});
						if(dialog_contr === false){
							dialog_contr = true;
							dd.showModal();	
						}
						return false;
					}
					var keyup_time = 80;
					if(searchaddress.length < 3 && /^[\w]+$/.test(searchaddress)){//2位字母
						keyup_time = 240;
					}else if(searchaddress.length<that.keyupval.length){
						keyup_time = 180;
					}else if(/^[^\w]+[\w]{1,2}$/.test(searchaddress)){//不确定是否还需要输入中文
						keyup_time = 180;
					}else if(/^[^\w]+[\w]{3,}$/.test(searchaddress)){//判断是否是中文还在输入
						keyup_time = 500;
					}else if(/^[\w]{3,}$/.test(searchaddress)){//不确定是否是中文输入
						keyup_time = 300;
					}
					that.keyupval = searchaddress;
					clearTimeout(keyuptime);
					
					var baidusearch = function(){
						var searchstr = searcharea+searchbusiness+searchaddress;
						var city = $('.centralized_Ind_CuteType1',$$).attr('city_name');
						city = !!city ? city : $('.head:first .crt-city span').text(); 
						var ak = "7ecf56b9699670bbdaccb4cc2fce7960";
						var ulr = 'http://api.map.baidu.com/place/v2/suggestion?query='+searchstr+'&region='+city+'&output=json&ak='+ak+'&callback=?';
						$.getJSON(ulr,function(json){
							if(json.status == 0){
								var s = [];
								var result = json.result;
								if(result.length == 0) return false;
								for (var n in result){
									   var points = {};
										var name = result[n].name,
											  address = result[n].city+result[n].district;
										if(typeof result[n].location == "undefined") continue;
									    point = {
											lat : result[n].location.lat,
											lng : result[n].location.lng
										};
										points = {
											name : name,
											address : address,
											point : point
										}
										s.push(points);
									}	
										var obj_auto = $("#show_addressdetail ul",$$);
										obj_auto.html("");
										var str_auto = "";
										for(var n in s){
											str_auto += '<li point_lat = "'+s[n].point.lat+'" point_lng = "'+s[n].point.lng+'"><span class="address_name">'+s[n].name+'</span><span class="address_detail">'+s[n].address+'</span></li>';
										}
										obj_auto.append(str_auto);
										$("#show_addressdetail",$$).show();
										$(".chooseaddressauto",$$).fadeIn(500);
										that.chooseaddress(obj_auto);
							}else{
								$('#show_addressdetail',$$).hide();
								$('.chooseaddressauto',$$).parent().find('.check-error').addClass('Validform_checktip Validform_wrong').html('未获取到地址坐标，请点击左侧按钮在地图中定位坐标');
								
							}
						});
					}
					
					keyuptime = setTimeout(baidusearch,keyup_time);
				});
			},
			chooseaddress : function(obj){
				obj.find("li").off("click").on("click",function(e){
					e.stopPropagation();
					mapsearchs.keyupval = $(this).children(".address_name").text();
					$("#centralized_Depart_InfoJs_address",$$).val(mapsearchs.keyupval).attr("point-lat",$(this).attr("point_lat")).attr("point-lng",$(this).attr("point_lng")).focus().blur();
					$(this).parents("#show_addressdetail").hide();
				});
				$("html,body").off("click").on("click",function(){
					$("#show_addressdetail",$$).hide();
				});
				$(".chooseaddressauto",$$).off("click").on("click",function(){
					$(this).toggleClass("choosestyle");
					if($(this).hasClass("choosestyle")){
						$(this).text("保存位置");
						$("#map_auto_add",$$).get(0).contentWindow.mapsearch.chooseaddress();
						$("#map_auto_add",$$).show().css({'marginTop': 0,'float':'left'});
					}else{
						$(this).text("地址不对，我来纠正").hide();
						$("#map_auto_add",$$).css('marginTop', '-10000px');
						var cur_point = 	$("#map_auto_add",$$).get(0).contentWindow.mapsearch.chooseaddress(function(cur_point){
							var cur_point_lat = cur_point.lat;
							var cur_point_lng = cur_point.lng;
							$("#centralized_Depart_InfoJs_address",$$).attr("point-lat",cur_point_lat).attr("point-lng",cur_point_lng).removeClass("Validform_error").focus().blur();
							$("#centralized_Depart_InfoJs_address",$$).parents(".jzf-col").find(".check-error").removeClass("Validform_checktip").removeClass("Validform_wrong").text("");
						}); 
					}
					
				});
			}
		}
		mapsearchs.search();
		//自定义楼层编号
	 	 $('#show_floors',$$).off("click").on('click', function () {
	 	 	var counts = $("input[name='centralized_Depart_InfoJs_total_floor']",$$);
	 	 	var flat_name = $('input[name="centralized_Depart_InfoJs_flat_name"]',$$);
	 	 	var count = counts.val();
	 	 	if(!!!counts.attr("pre-val")) counts.attr("pre-val",count);
	 	 	if(count != counts.attr("pre-val")) {counts.removeAttr("submit");counts.attr("pre-val",count);}
	 	 	counts.focus().blur();
	 	 	flat_name.focus().blur();
	 	 	if(counts.hasClass("Validform_error")||flat_name.hasClass("Validform_error")){
	 	 		return false;
	 	 	}
	 	 	var obj = $("#hideTemp .center-dpt-row",$$);
	 	 	if(!$(".jzf-form",$$).attr("iseditpage")){
		 	 	$(".dpt-flr-top .dpt-name").text(flat_name.val());
		 	 	$(".dpt-flr-top .dpt-flrs").text("（共计"+count+"层）");
		 	 	if(!counts.attr("submit") == true){
		 	 		obj.find("tr:gt(0)").remove();
		 	 		var str_auto = "";
			 	 	for(var i=0; i<count; i++){
			 	 		str_auto += '<tr><td>第'+(i+1)+'层</td><td>第<input type="text" class="ipt" value="'+(i+1)+'" />层</td></tr>';
			 	 	}	
			 	 	obj.append(str_auto);
		 	 	}
		 		var  cTemp=$('#hideTemp',$$).html();	 
				var d = dialog({
					title: '<i class="ifont">&#xf0077;</i><span>楼层编号</span>',
					content: cTemp,
					okValue: '保 存',
					ok: function () {
						var names_Floor = [];
						var urls = $("#show_floors",$$).attr("url");
						var type = "post";
						var checkresult = true;
						$(".ui-dialog-grid input[type='text']").each(function(){
							names_Floor.push($(this).val());
							if($(this).val()/1 == 0 || Math.abs($(this).val()/1) > 999 || $(this).val().indexOf(".")>=0){
								checkresult = false;
								return false;
							}
						});
						if(checkresult == false){
							var dd = dialog({
									title: '提示信息',
									content:'自定义楼层输入不合规范！',
									okValue: '确定',
									ok: function () {
										dd.close();
									}
								});
								dd.showModal();
								return false;
						}
						var names_Floor_auto = names_Floor.slice(0);
						var nary=names_Floor_auto.sort(); //检测数组中是否有重复元素
						for(var i=0;i<names_Floor_auto.length;i++){ 
	
							if (nary[i]==nary[i+1]){ 
								var dd = dialog({
									title: '提示信息',
									content:'楼层编号不能重复！',
									okValue: '确定',
									ok: function () {
										dd.close();
									}
								});
								dd.showModal();
								return false;
						    } 
						}
						var data = {
							"names_Floor" : names_Floor
						}
						ajax.doAjax(type,urls,data,function(data){
							if(data.status == 1){
								var dd = dialog({
									title: '提示信息',
									content:'保存成功',
									okValue: '确定',
									ok: function () {
										dd.close();
										obj.find("tr:gt(0)").remove();
										for(var i=0; i<count; i++){
//											console.log(names_Floor[1]);
								 	 		obj.append('<tr><td>第'+(i+1)+'层</td><td><input type="text" class="ipt" value="'+names_Floor[i]+'"/></td></tr>');
								 	 	}
										counts.attr("submit",true);
									}
								});
							}else{
								var dd = dialog({
									title: '提示信息',
									content:data.data,
									okValue: '确定',
									ok: function () {
										dd.close();
									}
								});
							}
							dd.showModal();
						});
					}
				});
				d.showModal();
			}else{
				var  cTemp=$('#hideTemp',$$).html();	 
				var d = dialog({
					title: '<i class="ifont">&#xf0077;</i><span>楼层编号</span>',
					content: cTemp,
					okValue: '确 定',
					ok: function () {
						d.close();
					}
				});
				d.showModal();
			}
		});
		
		//自定义房源编号
		$(".zdy-roomNum",$$).off("click").on("click",function(){
			var href = $(this).attr("url");
			var house_number = $("input[name='centralized_Depart_InfoJs_group_number']",$$);
			var floor_num = $("input[name='centralized_Depart_InfoJs_total_floor']",$$);
			var flat_name = $('input[name="centralized_Depart_InfoJs_flat_name"]',$$);
			if(!!!house_number.attr("isedit"))house_number.attr("isedit",0);
			if(!!!house_number.attr("pre-val")) house_number.attr("pre-val",house_number.val());
			if(house_number.val() != house_number.attr("pre-val")){house_number.attr("pre-val",house_number.val());house_number.attr("isedit",0);};
			var isedit = house_number.attr("isedit");
			if($(".center-dpt",$$).attr("iseditpage")){
				window.location.href = "#"+href+"&house_number="+house_number.val()+"&floor_num="+floor_num.val()+"&flat_name="+flat_name.val()+"&isedite="+isedit;
			}
			flat_name.focus().blur();
			house_number.focus().blur();
			floor_num.focus().blur();
			if(house_number.hasClass("Validform_error") || floor_num.hasClass("Validform_error")||flat_name.hasClass("Validform_error")) return false;
			window.location.href = "#"+href+"&house_number="+house_number.val()+"&floor_num="+floor_num.val()+"&flat_name="+flat_name.val()+"&isedite="+isedit;
		});
		//自定义套内间数
		$(".diy-roomnum",$$).off("click").on("click",function(){
			if($(".center-dpt",$$).attr("iseditpage")){
				return false;
			}
			var href = $(this).attr("url");
			var house_number = $("input[name='centralized_Depart_InfoJs_group_number']",$$);
			var floor_num = $("input[name='centralized_Depart_InfoJs_total_floor']",$$);
			var room_number = $("input[name='room_number']",$$);
			var flat_name = $('input[name="centralized_Depart_InfoJs_flat_name"]',$$);
			if(!!!room_number.attr("isedit"))room_number.attr("isedit",0);
			if(!!!floor_num.attr("pre-val")) floor_num.attr("pre-val",floor_num.val());
			if(!!!room_number.attr("pre-val")) room_number.attr("pre-val",room_number.val());
			if(floor_num.val() != floor_num.attr("pre-val")){floor_num.attr("pre-val",floor_num.val());room_number.attr("isedit",0);};
			if(room_number.val() != room_number.attr("pre-val")){room_number.attr("pre-val",room_number.val());room_number.attr("isedit",0);};
			flat_name.focus().blur();
			house_number.focus().blur();
			floor_num.focus().blur();
			room_number.focus().blur();
			if(house_number.hasClass("Validform_error") || floor_num.hasClass("Validform_error") || room_number.hasClass("Validform_error")||flat_name.hasClass("Validform_error")) return false;
			var isedit = room_number.attr("isedit");
			window.location.href = "#"+href+"&room_number="+room_number.val()+"&house_number="+house_number.val()+"&floor_num="+floor_num.val()+"&flat_name="+flat_name.val()+"&isedite="+isedit;
		});
		
		//添加模板
		$(".dpt-add-btn",$$).off("click").on("click",function(){
			var href = $(this).attr("url");
			var flat_id = document.URL.split("flat_id=")[1];
			window.location.href = href+"&flat_id="+flat_id;
		});
		
		//添加费用
		$(".dpt-add-btn-auto",$$).off("click").on("click",function(){
			var totalchoice = $(".dpt-fee-list",$$).find(".forclone .selectByM[hasevent=true]").children(".selectByMO").find("li").size();
			var clone = $(".dpt-fee-list",$$).find(".forclone").clone();
			var choosekey = [];
			$(".dpt-fee-list",$$).find(".forcloneauto .selectByM[hasevent=true]").each(function(){
				var val_auto = $(this).find(".selectByMT").attr("selectval");
				if(val_auto != "") choosekey.push(val_auto);
			});
			var length_choosekey = choosekey.length;
			clone.removeClass("forclone").addClass("forcloneauto").addClass("jzf-col").addClass("clearfix").css("width","100%");
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
							var _this_key = $(this).find(".selectByM[hasevent=true]").children(".selectByMO").find("li.selectedLi").attr("selectval");
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
				if(totalchoice > currentchoice) $(".dpt-add-btn-auto",$$).show();
			});
			$(".gen-f-item",$$).append(clone);
			var currentchoice = $(".dpt-fee-list",$$).find(".forcloneauto").size();
			if(totalchoice == currentchoice) $(this).hide();
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
		//绑定自定义下拉
		$(".selectByM",$$).each(function(){
			if($(this).attr("hasevent")){
				$(this).selectObjM(1,depart_Info.linkSelect);
			}else{
				$(this).selectObjM();
			}
			if($(".center-dpt",$$).attr("iseditpage")){
				depart_Info.setSelect($(this));
			}
		});
		//提交表单
		depart_Info.checkForm($$);
		
		//删除费用copy模板中的租金、押金和定金
		$(".forclone .selectByM[hasevent='true'] li",$$).each(function(){
			var selectval = $(this).attr("selectval");
			if(selectval == 2 || selectval == 61 || selectval == 62) $(this).remove();
		});
		
		//删除费用
		$(".forclonedelete",$$).off("click").on("click",function(){
				var totalchoice = $(".dpt-fee-list",$$).find(".forclone .selectByM[hasevent=true]").children(".selectByMO").find("li").size();
				$(this).parent().remove();
				var currentchoice = $(".dpt-fee-list",$$).find(".forcloneauto").size();
				if(totalchoice > currentchoice) $(".dpt-add-btn-auto",$$).show();
			});
		//取消
		hash.hash.savehash('centrallzed_Depart_InfoJs',$(':first',$$));
		$(".btn-cancel",$$).off("click").on("click",function(){
			if(hash.hash.ischange('centrallzed_Depart_InfoJs',$(':first',$$)) == true){
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
		//删除模板
		depart_Info.deleteTtmplate($$);
		
		//整租合租勾选
		$.each($(".radio",$$),function(i,o){
			$(o).off("click").on("click",function(){
				if($(this).attr("nochoose")){
					return false;
				}
				$(this).Radios();
				if($("input[name='rental_way']:checked",$$).val() == 1){
					$(".unit-rooms",$$).show();
				}else{
					$(".unit-rooms",$$).hide();
				}
			})
		});
	}
	exports.iniPageFun=function($$){
		modelInit($$);
	}
	
 	
	exports.inite = function(__html__){
		  exports.iniPageFun(__html__);//模块方法初始化
//		  $(".center-dpt-seprate").find(".dpt-sp-item").find("label").off("click").on("click",function(){
//		  	$(this).toggleClass("checked");
//		  	if($(this).hasClass("checked")){
//		  		$(this).children(".choose").show().next().attr("checked",true);
//		  	}else{
//		  		$(this).children(".choose").hide().next().removeAttr("checked");
//		  	}
//		  });
		}
});


