define(function(require,exports,module){
	var $=require("jquery"),
		Ajax=require("Ajax"),
		template=require("artTemp"),
		navigators = require("navigatortest"),
		dialog = require("dialog"),
		loading=require("loading"),
		ajaxRequest;
		require("selectByM")($);
		require("validForm")($);
	var calendar = require("calendar");
	var urlHelper = require("url");
	/*
	 * 全局Map对象
	 */
	var mapWidth,mapHeight,navbarheight=105,
		leftBarWidth=155,timer=[],
		markSearchStatus=0,//默认未搜索
	    CACHEALLDISTRICTJSON=[],//小区全部数据缓存(包含加载更多数据)
	    DISTRICTJSONCCHE=[],//小区数据缓存
		rightBoxWidth,initedRoomStatus=0,zoneObj;

/*
 *======================小区信息模块=============================*
 * */

	/*
	 * @obj 小区对象
	 * @param 选中小区信息
	 * ex:{id:"小区id",name:"成都-高新区-皇后国际"}
	 *
	 */
	var district=window.district=function(type,area_id,community_id){
		this.initEvent();
	}
	district.prototype={
		initEvent:function(type,area_id,community_id){
			var that=this;
			that.setIECapacity();
			that.rentStyleChoose();
			that.setSearchEvent();
			that.renderAreaBoxData(type,area_id,community_id);
			that.bindSelEvt();
			that.bindDeleEvent();
			that.getTypedJSON();
			that.bindScroll();
			that.changeMiniscreenWandH();//页面动态高度/宽度赋值
			that.toggleDataInfo();
		},

		isLoadingData:true,//是否正在加载数据
		currentPage:1,//当前页面索引
		changeMiniscreenWandH:function(isLoaded){
			var that=this;
			that.resizeRenderMappage(isLoaded);
            window.onresize=that.resizeThrottleV2(that.resizeRenderMappage, 200, 500);
		},
		//数据展示显示交互
		toggleDataInfo:function(){
			$(".spread-page .show-static").off().on("click",function(){
				  var  cur=$(this);
					if(cur.hasClass("active")){
						cur.removeClass("active");
						cur.next().hide();
					}else{
						cur.addClass("active");
						cur.next().show();
					}
			});
			$(document).click(function(evt){
					var  cur=$(".show-static");
					evt.stopPropagation?evt.stopPropagation():evt.cancelBubble=true;
					if(evt.target.className!="show-static active"){
						cur.removeClass("active");
						cur.next().hide();
					}else{
						cur.addClass("active");
						cur.next().show();
					}
			});
		},
		/*
		 *@func:请求房源列表数据时重置请求状态和页码
		 * */
		resetListdata:function(type,_areaId,_comutyId){
//			console.log(_areaId);
//			console.log(_comutyId);
			if(!!!_comutyId) _comutyId="";
			var  that=this;
			that.currentPage=1;//重置页码
			that.isLoadingData=true;//加载状态激活
			CACHEALLDISTRICTJSON=[];//清空缓存数据
			DISTRICTJSONCCHE=[];
			var _or=$("#rightColBox");
			_or.find("#hz-temp-box").html("").parent().addClass("none");
			_or.find("#zz-temp-box").html("").parent().addClass("none");
			_or.find(".type-bar").addClass("none");
			that.renderAreaBoxData(type,_areaId,_comutyId);
		},
		/*
		 *@func 函数节流，避免执行次数过快，性能优化
		 * */
  		resizeThrottleV2:function(fn, delay, mustRunDelay){
		    var timer = null;
		    var t_start;
		    return function(){
		        var context = this, args = arguments, t_curr = +new Date();
		        clearTimeout(timer);
		        if(!t_start){
		            t_start = t_curr;
		        }
		        if(t_curr - t_start >= mustRunDelay){
		            fn.apply(context, args);
		            t_start = t_curr;
		        }
		        else {
		            timer = setTimeout(function(){
		                fn.apply(context, args);
		            }, delay);
		        }
		    };
		 },

		/*
		 *@func:当前屏幕内容高度获取
		 * */
		getWindowHeight:function(){
		 	return window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
		},
		/*
		*@func 窗口拉伸页面渲染
		*/
		resizeRenderMappage:function(isLoaded){
//			console.log(isLoaded);
			var that=district.prototype;
			// mapWidth=($(window).width()-leftBarWidth)*0.4;//分屏比例
			mapWidth=0;
			mapHeight=that.getWindowHeight()-navbarheight;
			// mapWidth=mapWidth-leftBarWidth;
			// if(mapWidth<=400){
			// 	mapWidth=400;
			// }
			rightBoxWidth=$(window).width()-leftBarWidth-mapWidth-5;
			// if(isLoaded && isLoaded!=""){
			// 	var _mapBox= document.getElementById('mapWraper').contentWindow.document.getElementById('allMap');
			// 	$("#mapWraper").css({
			// 		width:mapWidth+"px",
			// 		height:mapHeight+"px"
			// 	});
			// 	$(_mapBox).css({
			// 		width:mapWidth+"px",
			// 		height:mapHeight+"px"
			// 	});
			// }else{
		  //   		var iframeLoadStatus=0;
			// 	if(iframeLoadStatus==0){
			// 		$("#mapWraper").load(function(){
			// 			iframeLoadStatus=1;
			// 			var _mapBox = document.getElementById('mapWraper').contentWindow.document.getElementById('allMap');
			// 			$("#mapWraper").css({
			// 				width:mapWidth+"px",
			// 				height:mapHeight+"px"
			// 			});
			// 			$(_mapBox).css({
			// 				width:mapWidth+"px",
			// 				height:mapHeight+"px"
			// 			});
			// 	    });
			// 	}else{
			// 		var _mapBox= document.getElementById('mapWraper').contentWindow.document.getElementById('allMap');
			// 		$("#mapWraper").css({
			// 			width:mapWidth+"px",
			// 			height:mapHeight+"px"
			// 		});
			// 		$(_mapBox).css({
			// 			width:mapWidth+"px",
			// 			height:mapHeight+"px"
			// 		});
			// 	}
			// }
			$(".spread-page").css({
				"height":mapHeight+"px",
				"width":"100%",
				"position":"relative",
				"overflow":"hidden"
			});
			$("#rightColBox").css({
				width:rightBoxWidth+5+"px",
				height:mapHeight+"px",
	      opacity:"1"
			});
			var H=mapHeight-66-40;
			$(".centralized_Ind_D").find(".b").height(H);
		},
		/*
		 *@func 窗口拉伸事件渲染<全屏>
		 * */
		renderFullMappage:function(){
			$("#rightColBox").hide();
			var that=district.prototype;
			mapWidth=$(window).width();//分屏比例
			mapHeight=that.getWindowHeight()-navbarheight;
			mapWidth=mapWidth-leftBarWidth;
			if(mapWidth<=400){
				mapWidth=400;
			}
			var _mapBox= document.getElementById('mapWraper').contentWindow.document.getElementById('allMap');
			$("#mapWraper").css({
				width:mapWidth+"px",
				height:mapHeight+"px"
			});
			$(_mapBox).css({
				width:mapWidth+"px",
				height:mapHeight+"px"
			});
		},
		/*
		 *@func 地图高宽度赋值<全屏>
		 * */
		changefullscreenWandH:function(){
				var that=district.prototype;
				that.renderFullMappage();
	            window.onresize=that.resizeThrottleV2(that.changefullscreenWandH, 200, 500);
		},
		/*
		 *@func:渲染对应房间类型
		 * */
		renderRoomType:function(){
			var type = "post",
                url=$(".getRoomType").find("ul").attr("room-url"),
                rental_way=$(".getRoomType").find(".selectedLi").attr("selectval");
            var data = {
                "rental_way":rental_way
            }
             var obj = $(".roomTypeRender"),
             	 obj_UL = obj.find(".selectByMO").children("ul");
             obj.find(".selectByMT").val("选择房间类型").attr("selectval","0");
	         obj_UL.empty();
            if(rental_way!=0){
	            Ajax.doAjax(type,url,data,function(json){
	                var data = json.data;
	                $.each(data,function(i,o){
						var str = "<li selectVal='"+i+"'>"+o+"</li>";
	                    obj_UL.append(str);
	                });
	                obj.find("li:eq(0)").trigger("click");
	            });
            }
	        obj.selectObjM();
		},
		/*
		 *@func:切换出租类型加载对应房间类型
		 * */
		bindSelEvt:function(){
			var that=this;
            $.each($(".roomRenderTrig"),function(i,o){
                if($(this).attr("hasevent")){
                    $(o).selectObjM(1,that.renderRoomType);
                }else{
                    $(o).selectObjM();
                }
            });
		},
		/*
		 *@func:渲染区域统计数据模板
		 * @desc:右侧小区数据渲染入口
		 * */
		 renderAreaBoxData:function(type,area_id,community_id){
			// 	if(!!!area_id) area_id="";
		 	if(!!!community_id) community_id="";

			area_id=$("#rightColBox").find("#area_circle").attr("selectval");
		 	//缓存id数组
		 	$("#getMapData").attr("area-id",area_id);
		 	$("#getMapData").attr("community-id",community_id);
		 	var durl=$("#getMapData").attr("area-url"),that=this,data="";
		 	if(!!!type) {
		 		type="get";
		 	}else{
		 		data="area_id="+area_id+"&community_id="+community_id;
				data+="&house_type="+$("#rent-type").attr("selectval")+"&room_type="+$("#room-type").attr("selectval")+"&community_name="+$("#rightColBox").find("#centralized_Ind_ApartmentName").val().replace(/ /g,"");
				data+="&custom_number="+$("#rightColBox").find("#centralized_Ind_SearchTxt").val().replace(/ /g,"");
		 	}
			var setting={
				type:type,
				url:durl,
				data:data
			}
			ajaxRequest=Ajax.doAjax(type,setting.url,setting.data,[function(json){
		 		if($("#getMapData").hasClass("clicked"))$("#getMapData").removeClass("clicked");
		 		$("#rightColBox").find(".house_List").addClass("none");
				if(json.status==1){
					//统计信息模板渲染
					json=json.data;
					var data={
						cityName:json.name,
						rateTotalRent:json.rental_rate+"%",
						rateMonthEmpty:json.month_empty_rate+"%",
						rateYearEmpty:json.year_empty_reate+"%",
						hourseResource:json.all_house,
						averageSalePrice:json.sum_mone,
						roomInrenting:json.count_rental,
						roomAvaiable:parseInt(json.all_house)-parseInt(json.count_rental)-parseInt(json.stop)-parseInt(json.is_yytz),
						roomReserved:json.count_reserve,
						cancleDeal:json.is_yytz,
						roomUnavaiable:json.stop
					};
					var temp=template('area-statice-temp', data);
					$("#zoneInfoCol").html(temp);
					var _txtGroup=$("#curt-Xq").text().replace(/\s+/g,'').split("-"),
							tlen=_txtGroup.length;
					if(tlen==2){
						$("#curt-Xq").find(".city").text(_txtGroup[0]);
						$("#curt-Xq").find(".area-split").text(_txtGroup[1]);
					}else if(tlen==3){
						$("#curt-Xq").find(".city").text(_txtGroup[0]);
						$("#curt-Xq").find(".area-split").text(_txtGroup[1]);
						$("#curt-Xq").next().text(_txtGroup[2]).show();
					}
					// var _jmp;
					// try{
					// 	if(!!$("#mapWraper").get(0).contentWindow.jMap.prototype){
					// 		_jmp=$("#mapWraper").get(0).contentWindow.jMap.prototype;
					// 	}
					// }catch(e){
					//
					// }
					// if(!!_jmp && _jmp!="" && _jmp!=undefined){
					// 	if(area_id!="" && area_id!=undefined){
					// 		if(community_id==""|| community_id==undefined){
					// 		  _jmp.getZoneAjaxData(2,"POST",area_id,"");
					// 		}
					// 	}else{
					// 		if(markSearchStatus==0){
					// 			_jmp.getZoneAjaxData(1);
					// 		}else{
					// 			 _jmp.getZoneAjaxData(1,"POST");
					// 		}
					// 	}
					// }
					$(".spread-page").css({
						opacity:"1"
					});
					var _obj=$("#rightColBox");
					district.prototype.currentPage=1;//重置页码
				district.prototype.isLoadingData=true;//加载状态激活
				CACHEALLDISTRICTJSON=[];//清空缓存数据
				DISTRICTJSONCCHE=[];
				_obj.find("#hz-temp-box").html("").parent().addClass("none");
				_obj.find("#zz-temp-box").html("").parent().addClass("none");
				_obj.find(".type-bar").addClass("none");
					that.getListHZData(type,area_id,community_id,0);
				}
			},function(){
				if($("#getMapData").hasClass("clicked"))$("#getMapData").removeClass("clicked");
		 		$("#rightColBox").find(".house_List").addClass("none");
			}]);
		 },
		 //重新获取统计数据请求
		 regetDataStatistic:function(type,area_id,community_id){
			 // 	if(!!!area_id) area_id="";
			 if(!!!community_id) community_id="";

			 area_id=$("#rightColBox").find("#area_circle").attr("selectval");
			 //缓存id数组
			 $("#getMapData").attr("area-id",area_id);
			 $("#getMapData").attr("community-id",community_id);
			 var durl=$("#getMapData").attr("area-url"),that=this,data="";
			 if(!!!type) {
				 type="get";
			 }else{
				 data="area_id="+area_id+"&community_id="+community_id;
				 data+="&house_type="+$("#rent-type").attr("selectval")+"&room_type="+$("#room-type").attr("selectval")+"&community_name="+$("#rightColBox").find("#centralized_Ind_ApartmentName").val().replace(/ /g,"");
				 data+="&custom_number="+$("#rightColBox").find("#centralized_Ind_SearchTxt").val().replace(/ /g,"");
			 }
			 var setting={
				 type:type,
				 url:durl,
				 data:data
			 }
			 	ajaxRequest=Ajax.doAjax(type,setting.url,setting.data,function(json){
					if(json.status==1){
						//统计信息模板渲染
						json=json.data;
						var data={
							cityName:json.name,
							rateTotalRent:json.rental_rate+"%",
							rateMonthEmpty:json.month_empty_rate+"%",
							rateYearEmpty:json.year_empty_reate+"%",
							hourseResource:json.all_house,
							averageSalePrice:json.sum_mone,
							roomInrenting:json.count_rental,
							roomAvaiable:parseInt(json.all_house)-parseInt(json.count_rental)-parseInt(json.stop)-parseInt(json.is_yytz),
							roomReserved:json.count_reserve,
							cancleDeal:json.is_yytz,
							roomUnavaiable:json.stop
						};
						var temp=template('area-statice-temp', data);
						$("#zoneInfoCol").html(temp);
						var _txtGroup=$("#curt-Xq").text().replace(/\s+/g,'').split("-"),
								tlen=_txtGroup.length;
						if(tlen==2){
							$("#curt-Xq").find(".city").text(_txtGroup[0]);
							$("#curt-Xq").find(".area-split").text(_txtGroup[1]);
						}else if(tlen==3){
							$("#curt-Xq").find(".city").text(_txtGroup[0]);
							$("#curt-Xq").find(".area-split").text(_txtGroup[1]);
							$("#curt-Xq").next().text(_txtGroup[2]).show();
						}
					}
				});
		 },
		/*
		 *@func:请求整租/合租数据
		 *@param  type 请求类型 默认为get
		 * */
		 getListHZData:function(type,area_id,community_id,page){
		 	if(!!!page) page=0;
			// 	if(!!!area_id) area_id="";
			area_id=$("#rightColBox").find("#area_circle").attr("selectval");
		 	if(!!!community_id) community_id="";
		 	var durl=$("#getMapData").attr("data-url"),that=this,data="";
		 	var filter="",ee=$("#rightColBox").find(".centralized_Ind_C").find(".choice.active");
		 	$.each(ee,function(i,o){
		 		if(i==0){
		 			filter+=$(o).attr("data-status");
		 		}else{
		 			filter+=","+$(o).attr("data-status");
		 		}
		 	});
		 	page+=1;
		 	if(!!!type) {
		 		type="get";
		 	}else{
		 		data="area_id="+area_id+"&community_id="+community_id;
				data+="&house_type="+$("#rent-type").attr("selectval")+"&room_type="+$("#room-type").attr("selectval")+"&community_name="+$("#rightColBox").find("#centralized_Ind_ApartmentName").val().replace(/ /g,"");
				data+="&custom_number="+$("#rightColBox").find("#centralized_Ind_SearchTxt").val().replace(/ /g,"")+"&page="+page;
		 		data+="&state="+filter;
		 	}
			var setting={
				type:type,
				url:durl,
				data:data
			}
			if(page==1) $(".hiden-loading-temp").html("").html(loading.genLoading("div","","")).removeClass("none").removeClass("ertip");
			else $(".hiden-loading-temp-bot").html("").html(loading.genLoading("div","","")).removeClass("none");
//			console.log(district.prototype.isLoadingData);
			if(district.prototype.isLoadingData==true){
				district.prototype.isLoadingData=false;
			 	Ajax.doAjax(setting.type,setting.url,data,function(json){
			 		if(page==1){
			 			$(".hiden-loading-temp").html("").addClass("none");
			 		}else{
			 			$(".hiden-loading-temp-bot").html("").addClass("none");
			 		}
			 		if($("#getMapData").hasClass("clicked"))$("#getMapData").removeClass("clicked");
			 		if(json.status==1){
			 			if(json.hz_data.length>0 || json.zz_data.length>0){
				 			district.prototype.isLoadingData=true;
				 			district.prototype.currentPage=page;
				 			DISTRICTJSONCCHE=json;
				 			// CACHEALLDISTRICTJSON.push(DISTRICTJSONCCHE);
//				 			console.log(DISTRICTJSONCCHE);
				 			that.renderListTemplate(DISTRICTJSONCCHE);//页面模板渲染,和筛选无关
				 			// var len=$("#rightColBox").find(".centralized_Ind_C").find(".active").length;
				 			// if(len>0){
				 			// 	that.trigFilterData();
				 			// }
			 			}else{
				 			if(json.hz_data=="" && json.hz_data.length==0 && json.zz_data=="" && json.zz_data.length==0){
				 				 if(page==1){
									 $("#rightColBox").find(".house_List").removeClass("none");
								 }
				 			}
//			 					console.log(CACHEALLDISTRICTJSON);
			 					// if(page!=1){
				 				// 	that.trigFilterData();
				 				// }else{
			 					// 	$("#rightColBox").find(".house_List").removeClass("none");
				 				// 	//var erTemp=loading.genLoading("div","",2);
							 	// 	//$(".hiden-loading-temp").html(erTemp).addClass("ertip").removeClass("none");
					 			// 	if(json.hz_data ==null || json.hz_data =="" ||json.hz_data.length==0){
					 			// 		if(area_id!="" || community_id!=""){
					 			// 			$("#rightColBox").find("#hz-temp-box").html("").parent().addClass("none");
					 			// 		}
					 			// 	}
					 			// 	if(json.zz_data ==null || json.zz_data=="" ||json.zz_data.length==0){
					 			// 		if(area_id!="" || community_id!=""){
					 			// 			$("#rightColBox").find("#zz-temp-box").html("").parent().addClass("none");
					 			// 		}
					 			// 	}
				 				// }
			 			}
			 		}else{
			 			district.prototype.isLoadingData=false;
			 			district.prototype.currentPage=page;
			 			$(".type-bar,.col-rtype").addClass("none");
			 		}
			 	});
			}else{
				if(page==1){
		 			$(".hiden-loading-temp").html("").removeClass("ertip").addClass("none");
		 		}else{
		 			$(".hiden-loading-temp-bot").html("").addClass("none");
		 		}
			}
		 },
		/*
		 *@func 小区列表数据处理
		 * */
		 renderListTemplate:function(json){
		 	var data=json,that=this,_obj=$("#rightColBox"),
		 		cpage=district.prototype.currentPage;
 			if(data.hz_data!="" && data.hz_data.length>0){
            		var html=template('hz-temp-gen-mark',data);
            		if(cpage == 1){
					$('#hz-temp-box').html(html);
            		}else{
					$('#hz-temp-box').append(html);
            		}
 				_obj.find(".tab-gather").removeClass("none");
 				_obj.find(".type-bar").find(".type-hz-row").removeClass("none");
 			}else{
 				if(cpage == 1){
 					_obj.find(".tab-gather").addClass("none");
 				}
 				_obj.find(".type-bar").find(".type-hz-row").addClass("none");
 			}
 			if(data.zz_data!="" && data.zz_data.length>0){
            		var zhtml=template('zz-temp-gen-mark',data);
				if(cpage == 1){
					$('#zz-temp-box').html(zhtml);
				}else{
					$('#zz-temp-box').append(zhtml);
				}
 				_obj.find(".tab-whole").removeClass("none");
 				 _obj.find(".type-bar").find(".type-zz-row").removeClass("none");
 			}else{
 				if(cpage == 1){
 					_obj.find(".tab-whole").addClass("none");
 				}
 				 _obj.find(".type-bar").find(".type-zz-row").addClass("none");
 			}
 			if(data.hz_data=="" && data.zz_data==""){
 				 _obj.find(".type-bar").addClass("none");
 			}else if(data.hz_data!="" || data.zz_data!=""){
			 	_obj.find(".type-bar").removeClass("none");
 			}
 			that.setEventzIndex();
			that.bindCheckbox();
			that.iniFoldCol();//展开收起的地区名称
			that.bindDialogEvent();//操作栏状态事件

			//高度判断

			var  container_ht=$("#rightColBox").find(".centralized_Ind_D .b").height();
			var ht1=$(".tab-gather").height(),
					ht2=$(".tab-whole").height();
				if(that.currentPage==1){
					if((ht1+ht2) <= container_ht){
							that.getListHZData("post","","",that.currentPage);
					}
				}
		 },
		/*
		 *@func 收起展开区域数据
		 * */
		iniFoldCol:function(){
			var par=$("#rightColBox").find(".tab-gather").find(".fold-txt");
			$.each(par,function(i,o){
				$(o).off().on("click",function(){
					var  cur=$(this),
						_ele=cur.parent().parent();
					if(cur.hasClass("active")){
						cur.removeClass("active").find("b").text("收起");
						cur.prev().addClass("blue");
						_ele.next().show();
					}else{
						cur.addClass("active").find("b").text("展开");
						cur.prev().removeClass("blue");
						_ele.next().hide();
					}
				});
			});
		},
		/*搜索事件绑定*/
		setSearchEvent:function(){
			var that=this,_obj=$("#rightColBox");
			$("#getMapData").off().on("click",function(){
				markSearchStatus=1;//激活搜索状态
				district.prototype.currentPage=1;//重置页码
				district.prototype.isLoadingData=true;//加载状态激活
				CACHEALLDISTRICTJSON=[];//清空缓存数据
				DISTRICTJSONCCHE=[];
				_obj.find("#hz-temp-box").html("").parent().addClass("none");
				_obj.find("#zz-temp-box").html("").parent().addClass("none");
				_obj.find(".type-bar").addClass("none");
				var cur=$(this),
					_areaId=cur.attr("area-id"),
					_comutyId=cur.attr("community-id");
				if(!cur.hasClass("clicked")){
					cur.parent().find(".clicked").removeClass("clicked");
					cur.addClass("clicked");
					that.renderAreaBoxData("POST",_areaId,_comutyId);
				}
			});
		},
		/*
		 *@func IE 事件绑定
		 * */
		setIECapacity:function(){
			if(sys.ie && sys.ie < 10){
				require("placeholder")($);
				$("#centralized_Ind").placeholder();
			}
		},
		/*
		 *@func IE 房间状态筛选数据筛选处理
		 * */
		getTargetRoomArray:function(param,rtype,status,id){
//	       	console.log(param);
			var that=this,FILTERROOMJSON=[];
			var len=$("#rightColBox").find(".centralized_Ind_C").find(".active").length;
			//清空数据
			$("#hz-temp-box").html("");
			$("#zz-temp-box").html("");

//			console.log(CACHEALLDISTRICTJSON);//处理数据前的集合
			var FILETERJSON={},_dd=[],_ddz=[];
			$.each(CACHEALLDISTRICTJSON,function(j,item){
				_dd.push(item.hz_data);
				_ddz.push(item.zz_data);
			});
			var _newH=[],_newZ=[];
			$.each(_dd,function(i,o){
				$.each(o,function(j,k){
					_newH.push(k);
				});
			});
			$.each(_ddz,function(i,o){
				$.each(o,function(j,k){
					_newZ.push(k);
				});
			});
			FILETERJSON["hz_data"]=_newH;
			FILETERJSON["zz_data"]=_newZ;
			if(len==0){
				if(rtype!=undefined && status!=undefined && id!=undefined){
					that.changeDataStatus(rtype,status,id,FILETERJSON);
				}else{
					that.renderListTemplate(FILETERJSON);//渲染全部数据
				}
			}else{
				var temJSON=FILETERJSON,
					temHZ=[],temZZ=[];
				 $.each(temJSON.hz_data,function(i,o){
				 	var _target=o.room_data,
				 		stem={},stp=[],_transD=[];
				 		//循环房间数据 筛选出符合条件的数据
				 	stem["detail_url"]=o.url;
				 	stem["house_id"]=o.house_id;
				 	stem["house_name"]=o.house_name;
				 	stem["url"]=o.url;
				 	$.each(_target,function(j,item){
				 		var jitem=[];
				 		//已出租
				 		if(param.isYYTZ!=""){
				 			if(param.isYYTZ==item.is_yytz){
					 			jitem.push(item);
					 		}else{
					 			if(param.isManRented!="" && param.isWomenRented!=""){
					 				jitem.push(item);
					 			}
					 			if(param.isManRented!=""){
						 			if(param.isManRented==item.sex){
							 			jitem.push(item);
							 		}
							 		// else if(item.sex==3){
							 		// 	jitem.push(item);
							 		// }
					 			}
					 			if(param.isWomenRented!=""){
						 			if(param.isWomenRented==item.sex){
							 			jitem.push(item);
							 		}
							 		// else if(item.sex==3){
							 		// 	jitem.push(item);
							 		// }
					 			}
					 		}
				 		}else{
					 			if(param.isManRented!="" && param.isWomenRented!=""){
					 				jitem.push(item);
					 			}
					 			if(param.isManRented!=""){
						 			if(param.isManRented==item.sex){
							 			jitem.push(item);
							 		}
							 		// else if(item.sex==3){
							 		// 	jitem.push(item);
							 		// }
					 			}
					 			if(param.isWomenRented!=""){
						 			if(param.isWomenRented==item.sex){
							 			jitem.push(item);
							 		}
							 		// else if(item.sex==3){
							 		// 	jitem.push(item);
							 		// }
					 			}
				 		}
				 		//未出租
				 		if(param.isBooked!=""){
				 			if(param.isBooked==item.is_yd && item.status==1){
					 			jitem.push(item);
					 		}else{
					 			if(param.isUNRented!=""){
						 			if(param.isUNRented==item.status){
							 			jitem.push(item);
							 		}
					 			}
					 		}
				 		}else{
					 		if(param.isUNRented==item.status){
					 			jitem.push(item);
					 		}
				 		}
				 		//停用
				 		if(param.isSTOPUSED==item.status){
				 			jitem.push(item);
				 		}
				 		if(jitem!="" && jitem!=[] && jitem!=null){
//				 			jitem=jitem.join(",");
				 			stp.push(jitem);
				 		}
				 	});
				 	var nTp=that.setRoomData(stp);//转化room_data格式
					if(nTp!=[] && nTp!=""){
				 		stem["room_data"]=nTp;
						temHZ.push(stem);
					}
				 });
//				 console.log(temHZ);
				 //格式转化
				 var _z=[];
				 //整租数据循环
				 $.each(temJSON.zz_data,function(i,o){
				 	var item=o,stem=[];
				 		//已出租
				 		if(param.isYYTZ!=""){
							if(param.isYYTZ==item.is_yytz){
					 				stem.push(o);
					 			}else{

				 			if(param.isManRented!="" && param.isWomenRented!=""){
					 				tem.push(o);
					 			}
						 			if(param.isManRented!=""){
							 			if(param.isManRented==item.sex){
								 			stem.push(o);
								 		}
								 		// else if(item.sex==3){
								 		// 	stem.push(o);
								 		// }
						 			}
						 			if(param.isWomenRented!=""){
							 			if(param.isWomenRented==item.sex){
								 			stem.push(o);
								 		}
								 		// else if(item.sex==3){
								 		// 	stem.push(o);
								 		// }
						 			}
					 			}
				 		}else{
				 			if(param.isManRented!="" && param.isWomenRented!=""){
					 				tem.push(o);
					 			}
					 			if(param.isManRented!=""){
						 			if(param.isManRented==item.sex){
							 			stem.push(o);
							 		}
							 		// else if(item.sex==3){
							 		// 	stem.push(o);
							 		// }
					 			}
					 			if(param.isWomenRented!=""){
						 			if(param.isWomenRented==item.sex){
							 			stem.push(o);
							 		}
							 		// else if(item.sex==3){
							 		// 	stem.push(o);
							 		// }
					 			}
				 		}
				 		//未出租
				 		if(param.isBooked!=""){
				 			if(param.isBooked==item.is_yd && item.status==1){
					 			stem.push(o);
					 		}else{
					 			if(param.isUNRented!=""){
					 				if(param.isUNRented==item.status){
							 			stem.push(o);
							 		}
					 			}
					 		}
				 		}else{
					 		if(param.isUNRented==item.status){
					 			stem.push(o);
					 		}
				 		}
				 		//停用
				 		if(param.isSTOPUSED==item.status){
				 			stem.push(o);
				 		}
			 		if(stem!="" && stem.length>0){
			 			temZZ.push(stem);
			 		}
				 });
//				 console.log(temZZ);
				//格式转化
				$.each(temZZ,function(x,y){
				 	$.each(y,function(a,b){
				 	 	_z.push(b);
				 	 });
				 });
				FILTERROOMJSON["hz_data"]=temHZ;
				FILTERROOMJSON["zz_data"]=_z;
				if(rtype!=undefined && status!=undefined && id!=undefined){
					that.changeDataStatus(rtype,status,id,FILETERJSON);
					//先处理数据再渲染
				}else{
					that.renderListTemplate(FILTERROOMJSON);
				}
			}
		},
		/*
		 *@func 改变对应id数据的房间状态值,防止筛选回到原样
		 * */
		changeDataStatus:function(a,b,c,d){
			var pJson=d.zz_data,that=this;
			if(a==2){
				pJson=d.hz_data;
			}
			if(a==2){
				$.each(pJson,function(i,o){
					var room=o.room_data;
					$.each(room, function(j,k) {
					    if(c==k.record_id){
					    		var _befSta=k.status,//改变状态前的状态值
					    			_afSta=b.status;//改变状态后的状态值
					    		//未出租
							if(_afSta==1){
								if(b.is_yd==1){
									k.msg_yd=b.msg_yd;
								}else{
									k.emp_msg=b.emp_msg;
								}
								if(_befSta==2){
									k.is_yytz="";
								}
								k.is_yd=b.is_yd;
							//已出租
							}else if(_afSta==2){
								if(b.is_yytz==0){
					    				k.msg=b.msg;
					    			}else if(b.is_yytz==1){
					    				k.msg_yytz=b.msg_yytz;
					    			}
					    			k.is_yytz=b.is_yytz;
							//停用
							}else if(_afSta==3){
								k.emp_msg="";//清空原来的数据
								k.stop_msg=b.stop_msg;
								k.is_yd="";
							}
					    		k.status=b.status;
					    }
					});
				});
			}else{
				$.each(pJson,function(i,o){
					if(c==o.house_id){
						var _befSta=o.status,//改变状态前的状态值
					    			_afSta=b.status;//改变状态后的状态值
					    		//未出租
							if(_afSta==1){
								if(b.is_yd==1){
									o.msg_yd=b.msg_yd;
								}else{
									o.emp_msg=b.emp_msg;
								}
								if(_befSta==2){
									o.is_yytz="";
								}
								o.is_yd=b.is_yd;
							//已出租
							}else if(_afSta==2){
								if(b.is_yytz==0){
					    				o.msg=b.msg;
					    			}else if(b.is_yytz==1){
					    				o.msg_yytz=b.msg_yytz;
					    			}
					    			o.is_yytz=b.is_yytz;
							//停用
							}else if(_afSta==3){
								o.emp_msg="";//清空原来的数据
								o.stop_msg=b.stop_msg;
								o.is_yd="";
							}
					    		o.status=b.status;
					}
				});
			}
			that.renderListTemplate(d);
		},
		/*
		 *@func 转化二位数组为一维
		 * */
		setRoomData:function(stp){
//			console.log(stp);
			var ntp=[];
			$.each(stp,function(i,o){
				$.each(o,function(j,k){
					ntp.push(k);
				});
			});
//			console.log(ntp);
			return ntp;
		},
		/*
		 *@func IE 房间状态筛选事件处理
		 * */
		rentStyleChoose:function(){
			var that=this;
			$("#rightColBox").find(".centralized_Ind_C .choice").off().on("click",function(){
				var  cur=$(this),params={};
					if(!cur.hasClass("active")){
						cur.addClass("active");
						if(cur.hasClass("choice_man")){
							//cur.parent().parent().find(".choice_women").attr("data-sex","").removeClass("red").removeClass("active");;
							cur.attr("data-sex","1").addClass("blue");
						}else if(cur.hasClass("choice_women")){
							//cur.parent().parent().find(".choice_man").attr("data-sex","").removeClass("blue").removeClass("active");;
							cur.attr("data-sex","2").addClass("red");
						}else if(cur.hasClass("choice_unrented")){
							cur.attr("data-unrent","1").addClass("red");
						}else if(cur.hasClass("choice_booked")){
							cur.attr("data-yd","1").addClass("green");
						}else if(cur.hasClass("choice_outRented")){
							cur.attr("data-tz","1").addClass("yellow");
						}else if(cur.hasClass("choice_stopUse")){
							cur.attr("data-ty","3").addClass("gray");
						}

					}else{
						cur.removeClass("active");
						if(cur.hasClass("choice_man")){
							cur.attr("data-sex","").removeClass("blue");
						}else if(cur.hasClass("choice_women")){
							cur.attr("data-sex","").removeClass("red");
						}else if(cur.hasClass("choice_unrented")){
							cur.attr("data-unrent","").removeClass("red");
						}else if(cur.hasClass("choice_booked")){
							cur.attr("data-yd","").removeClass("green");
						}else if(cur.hasClass("choice_outRented")){
							cur.attr("data-tz","").removeClass("yellow");
						}else if(cur.hasClass("choice_stopUse")){
							cur.attr("data-ty","").removeClass("gray");
						}
					}
				// that.trigFilterData();
				$("#getMapData").trigger("click");
			});
		},
		/*
		 * *@func 筛选过滤房间状态数据
		 */
		trigFilterData:function(rtype,status,id){
			var that=this;
			that.setCheckStatus();
			that.regetDataStatistic();
			// $("#getMapData").trigger("click");
			// return;
			// var obj=$("#rightColBox"),that=this,params;
			// params={
			// 	isManRented:obj.find(".choice_man").attr("data-sex"),//男租客
			// 	isWomenRented:obj.find(".choice_women").attr("data-sex"),//女租客
			// 	isUNRented:obj.find(".choice_unrented").attr("data-unrent"),//未出租
			// 	isSTOPUSED:obj.find(".choice_stopUse").attr("data-ty"),//停用
			// 	isBooked:obj.find(".choice_booked").attr("data-yd"),//预定
			// 	isYYTZ:obj.find(".choice_outRented").attr("data-tz")//预约退租
			// };
			// that.getTargetRoomArray(params,rtype,status,id);
		},
		/*删除、取消删除事件*/
		bindDeleEvent:function(){
			var that=this;
			$("#rightColBox").find(".centralized_Delete").off("click").on("click",function(){
				district.prototype.isLoadingData=false;
				var url = $(this).attr("url");
				var type= "post";
				var _el=$(this);
				if(sys.ie && sys.ie < 8){
					$(".centralized_Ind_C > .a > ul > li:lt(1)").hide();
					$(".centralized_Ind_C > .a > ul > li:last").show();
					$(".centralized_Ind_C > .b").hide();
				}else{
					$(".centralized_Ind_C > .a > ul > li:lt(1)").animate({"width":"0"},200,function(){
						$(this).hide();
					});
					$(".centralized_Ind_C > .a > ul > li:last").fadeIn(200);
					$(".centralized_Ind_C > .b").fadeOut(400);
				}
				$.each($("#rightColBox").find(".centralized_Ind_D .col-rtype ul li"),function(i,o){
					// var len=$(o).find("dl").find('dd').length;
					// if(len>0){
						$(o).find(".checkBoxAll").show();
					// }
					var _ele=$(o).find("dd");
					$.each(_ele,function(item,s){
						$(s).find(".checkBox").show();
						$(s).find(".jtBox,.icon_Style,.rent_Style").hide();
						$(s).find(".romm_NUM").css({"padding-left":"27px","width":"127px"});
					});
				});
			//拥有删除状态，点击执行删除操作
			if(_el.hasClass("deleteStyle")){
				var deletelist = [];
		   			var deletelistobj = [];
		   			$("#rightColBox").find(".centralized_Ind_D  .b  ul  li").each(function(){
		   				var cur=$(this);
		   				if(cur.find("dl").find("dd").length>0){
			   				cur.find("dl").find("dd").each(function(){
			   					var el=$(this);
			   					if(el.find("input:checked").size() > 0){
			   						var _name=el.attr("name"),_eleAuto;
							   		var house_num = el.find(".romm_NUM").text();
			   						if(_name=="room_id"){
			   							_eleAuto = {
				   							room_id:el.attr("room_id"),
				   							house_num:house_num,
				   							name:_name
				   						}
			   						}else{
			   							_eleAuto = {
				   							house_id:el.attr("house_id"),
				   							house_num:house_num,
				   							name:_name
				   						}
			   						}
				   					deletelist.push(_eleAuto);
				   					deletelistobj.push($(this));
				   				}
			   				});
		   				}else{
		   					if(cur.find(".checkBoxAll.checked").length>0){
		   						var house_id=cur.attr("house_id"),_eleAuto;
						   		var _name = cur.find(".floor_Num a").text();
	   							_eleAuto = {
		   							house_id:cur.attr("house_id"),
		   							name:_name,
		   							house_num:_name
		   						}
		   						deletelist.push(_eleAuto);
		   						deletelistobj.push($(this));
		   					}
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
		   			}else{
		   				var da=dialog({
		   					title: '提示信息',
							content:"删除的信息将无法得到恢复，确定删除？",
							okValue: '确定',
							ok: function () {
								da.close().remove();
					   			var deletedata =   {
					   				url : url,
					   				type : type,
					   				deletelist : deletelist,
					   				deletelistobj : deletelistobj
					   			}
			//		   			console.log(deletedata);
					   			that.deleteauto(deletedata,0);
							},
							cancelValue:"取消",
							cancel:function(){
								da.close().remove();
							}
		   				});
		   				da.showModal();
		   			}
//
//
//				var  param_hz,param_zz,obj=$("#rightColBox"),
//					 _delHZ,_delZZ,_param_a="",_param_b="";
//				_delHZ=obj.find(".tab-gather").find("ul").find("li");
//				_delZZ=obj.find(".tab-whole").find("ul").find("li");
//				var len=obj.find(".centralized_Ind_D").find("ul").find("li").find("dd").find("input:checked").length;
//				if(len==0){
//					var d=dialog({
//							title: '提示',
//							content:"请先选择删除项",
//							cancelValue: '确定',
//							cancel: function () {}
//						});
//						d.showModal();
//						setTimeout(function(){
//							d.close().remove();
//						},1500);
//				}else{
//				$.each(_delHZ,function(i,o){
//					var _tgt=$(o).find("dd");
//					$.each(_tgt,function(j,item){
//						var _ele=$(item).find(".checkBox").find("input[type='checkbox']");
//						$.each(_ele,function(x,y){
//							if($(y).attr("checked")=="checked" || $(y).attr("checked")==true){
//								_param_a+=","+$(y).val();
//							}
//						});
//					});
//				});
//				$.each(_delZZ,function(i,o){
//					var _tgt=$(o).find("dd");
//					$.each(_tgt,function(j,item){
//						var _ele=$(item).find(".checkBox").find("input[type='checkbox']");
//						$.each(_ele,function(x,y){
//							if($(y).attr("checked")=="checked" || $(y).attr("checked")==true){
//								_param_b+=","+$(y).val();
//							}
//						});
//					});
//				});
//				_param_a=_param_a.replace(_param_a.substr(0,1),"");
//				_param_b=_param_b.replace(_param_b.substr(0,1),"");
//				var durl=_el.attr("url"),
//					data={
//						room_id:_param_a,
//						house_id:_param_b
//					};
////					console.log(data);
////					return;
//				if(!_el.hasClass("clicked")){
//					_el.parent().find(".clicked").removeClass("clicked");
//					_el.addClass("clicked").text("删除中...");;
//					Ajax.doAjax("POST",durl,data,function(json){
//						_el.removeClass("clicked").text("删除");
//						if(json.status==1){
//							var _delPar=obj.find(".centralized_Ind_D").find("ul").find("li");
//							$.each(_delPar,function(i,o){
//								var _delItem=$(o).find("dd");
//								$.each(_delItem,function(a,b){
//									var _c=$(b).find("input[type='checkbox']");
//									$.each(_c,function(d,e){
//										if($(e).attr("checked")=="checked" || $(e).attr("checked")==true){
//											$(e).parents("dd").remove();
//											var _culen=$(e).parents("dl").find("dd").length;
////											if(_culen==0){
////												$(o).remove();
////											}
//											obj.find(".centralized_DeleteCancel").trigger("click");
//										}
//									});
//								});
//							});
//						}
//						var d=dialog({
//							title: '提示',
//							content:json.message,
//							cancelValue: '确定',
//							cancel: function () {}
//						});
//						d.showModal();
//						setTimeout(function(){
//							d.close().remove();
//						},1500);
//					});
//				}
//
//				}
			}
			$(this).addClass("deleteStyle");
		});
			$("#rightColBox").find(".centralized_DeleteCancel").off("click").on("click",function(){
				district.prototype.isLoadingData=true;
				if(sys.ie && sys.ie < 8){
					$(".centralized_Ind_C > .a > ul > li:lt(1)").show();
					$(".centralized_Ind_C > .a > ul > li:last").hide();
					$(".centralized_Ind_C > .b").show();
				}else{
					$(".centralized_Ind_C > .a > ul > li:lt(1)").show().animate({"width":"76px"},200);
					$(".centralized_Ind_C > .a > ul > li:last").fadeOut(200);
					$(".centralized_Ind_C > .b").fadeIn(400);
				}
			$("#rightColBox").find(".centralized_Delete").removeClass("deleteStyle");
			var _targ=$("#rightColBox").find(".centralized_Ind_D  .col-rtype  ul  li");
			$.each(_targ,function(i,o){
				$(o).find(".floorNum").find(".checkBoxAll").hide();
				$(o).find(".floorNum").find("p").hide();
				var _edl=$(o).find("dl").find("dd");
				$.each(_edl,function(j,item){
					$(item).find(".checkBox").hide();
					$(item).find(".jtBox,.icon_Style,.rent_Style").show();
					$(item).find(".romm_NUM").css({"padding-left":"8px","width":"136px"});
				});
			});
		});
		},
		/*
		 *@func 删除进度条
		 * */
		deleteauto : function(deletedata,num_cur){
			   	  var that = this;
			   	  var cloneStr = $("#rightColBox").parents(".jooozo_Page").find(".deletemoreauto").clone();
			   	  cloneStr.removeClass("none");
			   	  var deleteTptal = deletedata.deletelist.length;
			   	  var deletelist = deletedata.deletelist;
			   	  var deletelistobj = deletedata.deletelistobj;
			   	  cloneStr.find(".num_total").text(deleteTptal);
			   	  var dd = dialog({
							title: '<i class="ifont">&#xe675;</i><span>删除房源</span>',
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
					function autodeleteus(){
						if(objAuto.hasClass("stop")) return false;
						var objsauto = null;
//				   			 $(".centralized_Ind_D > .b > ul > li",$$).each(function(){
//					   				$(this).children("dl").children("dd").each(function(){
//					   					if($(this).find("input:checked").size() > 0){
//						   					objsauto = $(this);
//						   					num_cur++
//						   					return false;
//						   				}
//					   				});
//					   			});
						objsauto = 	deletelistobj[num_cur];
						 var house_Num = deletelist[num_cur].house_num,
						  _name=deletelist[num_cur].name,
						  idauto,data;
			   			 if(_name =="room_id"){
			   			 	data={
			   			 		room_id:deletelist[num_cur].room_id
			   			 	}
			   			 }else{
			   			 	data={
			   			 		house_id:deletelist[num_cur].house_id
			   			 	}
			   			 }
						 num_cur++;
			   			 objAuto.find(".num_cur").text(num_cur);
			   			 var trstr = '<tr><td class="zb">'+house_Num+'</td><td class="yb">正在删除</td></tr>';
			   			 tableauto.append(trstr);
			   			 if(num_cur > 5){
							tableauto.find("tr:eq("+(num_cur-5)+")").fadeOut(300);
						}
			   			 var trcur = tableauto.find("tr:last");
						Ajax.doAjax(type,url,data,function(data){
							if(data.status == 0){
								trcur.find(".yb").addClass("red").removeClass("blue").text(data.data);
								objsauto.find(".checkBox").removeClass("checked").children().hide();
								objsauto.find(".checkBox").next().removeAttr("checked");
								if(num_cur == deleteTptal){
									scrollbar.animate({"left":0},300);
									return false;
								}
								scrollbar.animate({"left":-(100-scrollbalscroll*num_cur)+"%"},300);
								var timer = null;
								clearTimeout(timer);
								timer = setTimeout(autodeleteus,200);
							}else{
								trcur.find(".yb").addClass("blue").removeClass("red").text(data.message);
								objsauto.remove();
								if(num_cur == deleteTptal){
									scrollbar.animate({"left":0},300);
									objAuto.find(".top1 .fl").text("删除完成");
									$("#rightColBox").find(".centralized_Ind_D  .b  ul .bdsh").each(function(){
										if($(this).children("dl").children().size() == 0){
											if($(this).attr("type")==2){
												$(this).remove();
											}else{
												if($(this).find(".checkBoxAll.checked").length>0){
													$(this).hide();
												}else{
													$(this).find(".floorNum").find(".checkBoxAll").css({
														cursor:"pointer"
													});
												}
											}
										}
									});
//									that.checkFinalLen();
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
		setCheckStatus:function(){
			var _dd=$("#rightColBox").find(".centralized_Ind_D .col-rtype li").find("dd");
				$.each(_dd,function(j,k){
					if($(k).attr("data-rentstyle") == "stopUse" || $(k).attr("data-rentstyle") == "outRented" || $(k).attr("data-rentstyle") == "Rented" ||  $(k).attr("data-rentstyle") == "booked"){
						$(k).find(".checkBox").addClass("canNotDelete").find("label").css({
							cursor:"not-allowed"
						});
					}else{
						$(k).find(".checkBox").removeClass("canNotDelete").find("label").css({
							cursor:"pointer"
						});
					}
				});
				var tmp='<div class="undelText">只能删除未出租房屋，该房屋有合约！</div>';
				var _ck=_dd.find(".checkBox");
				$.each(_ck,function(j,item){
					var hoverTimer, outTimer;
					$(item).hover(function(){
						$.each($(".undelText"),function(i,o){
								$(o).remove();
						});
						var cur=$(this),
						k=cur.parents("dd");
						if(k.attr("data-rentstyle") == "stopUse" || k.attr("data-rentstyle") == "outRented" || k.attr("data-rentstyle") == "Rented" ||  k.attr("data-rentstyle") == "booked"){
							clearTimeout(outTimer);
							hoverTimer=setTimeout(function(){
								cur.parent().find(".tc_Detail").show();
								cur.parent().find(".tc_Detail").find("ol").hide();
								var len=cur.parent().find(".tc_Detail").find(".undelText").length;
								if(len==0){
									 cur.parent().find(".tc_Detail").append(tmp);
								}
							},500);
						}
					},function(){
						var  cur=$(this);
						clearTimeout(hoverTimer);
						outTimer=setTimeout(function(){
							$.each($(".undelText"),function(i,o){
									$(o).remove();
							});
							cur.parent().find(".tc_Detail").find("ol").show();
						},500);
					});
				});

				/*全选*/
				var _tar=$("#rightColBox").find(".centralized_Ind_D .col-rtype ul li");
				$.each(_tar,function(i,o){
					var _el=$(o).find("dl").find("dd");
					var len=_el.length;
					$.each(_el,function(j,k){
						if($(k).attr("data-rentstyle") == "stopUse" || $(k).attr("data-rentstyle") == "outRented" || $(k).attr("data-rentstyle") == "Rented" ||  $(k).attr("data-rentstyle") == "booked"){
							$(k).find(".checkBox").css({
								cursor:"not-allowed"
							});
							$(k).parent().parent().find(".floorNum").find(".checkBoxAll").css({
								cursor:"not-allowed"
							});
						}else{
							$(k).find(".checkBox").css({
								cursor:"default"
							});
							$(k).parent().parent().find(".floorNum").find(".checkBoxAll").css({
								cursor:"default"
							});
						}
					});
					$(o).find(".floorNum").find(".checkBoxAll").off().on("click",function(){
						var cur=$(this);
						var len=cur.parent().next().find("dd").length,
							_el=cur.parent().next().find("dd");
						if(len==0 && $(o).attr("type")==1){
							cur.toggleClass("checked").find(".gou").toggle();
						}else{
							if($(o).attr("type")==1){
								if(cur[0].style.cursor=="not-allowed") return;
								if(cur.hasClass("checked")){
									cur.removeClass("checked");
									cur.find(".gou").css("display","none");
									var _targetEl=cur.parent().next().find("dd[data-rentstyle='notRented']").find(".checkBox").not(".canNotDelete").find("label");
									$.each(_targetEl,function(k,l){
										$(l).removeClass("checked");
										$(l).find(".gou").hide();
										$(l).next().removeAttr("checked");
									});
								}else{
									cur.addClass("checked");
									cur.find(".gou").css("display","inline");
									var _targetEl=cur.parent().next().find("dd[data-rentstyle='notRented']").find(".checkBox").not(".canNotDelete").find("label");
									$.each(_targetEl,function(k,l){
										$(l).addClass("checked");
										$(l).find(".gou").show();
										$(l).next().attr("checked",true);
									});
								}
							}else if($(o).attr("type")==2){
								var dtype=cur.parent().next().find("dd").attr("data-rentstyle");
								if(dtype=="stopUse" || dtype=="outRented" || dtype=="Rented"){
									$(o).find(".floorNum").find(".checkBoxAll").css({
										cursor:"not-allowed"
									});
								}else{
									cur.toggleClass("checked").find(".gou").toggle();
									if(cur.hasClass("checked")){
										var _eleChild=cur.parent().next().find("dd");
										$.each(_eleChild,function(j,item){
											if(!$(item).find(".checkBox").hasClass("canNotDelete")){
												$(item).find(".checkBox").find("label").addClass("checked").children(".gou").show();
												$(item).find(".checkBox").find("label").next().attr("checked",true);
											}
										});
									}else{
										var _eleChild=cur.parent().next().find("dd");
										$.each(_eleChild,function(j,item){
											$(item).find(".checkBox").find("label").removeClass("checked").find(".gou").hide();
											$(item).find(".checkBox").find("label").next().removeAttr("checked");
										});
									}
								}
							}
						}
					});
				});
		},
		/*
		 * @func 删除状态下的复选框是否能勾选判断
		 */
		bindCheckbox:function(){
		  var that=this;
			that.setCheckStatus();
			$("#rightColBox").find(".centralized_Ind_D .col-rtype ul .checkBox label").off().on("click",function(){
				if($(this).parent().hasClass("canNotDelete")){
					return false;
				}
				// $(this).toggleClass("checked");
				var  cur=$(this);
				if(cur.hasClass("checked")){
					cur.removeClass("checked");
					cur.find(".gou").hide();
					cur.next().removeAttr("checked");
				}else{
					cur.addClass("checked");
					cur.find(".gou").show();
					cur.next().attr("checked",true);
				}
			});
		},
		/*
		 * @func:固定条筛选
		 */
		getTypedJSON:function(){
			$("#rightColBox").find(".type-bar a").off().on("click",function(){
				var  cur=$(this),obj=$("#rightColBox"),
					 type=cur.attr("data-type");
			     if(!cur.hasClass("cur_a")){
			     	cur.parent().find(".cur_a").removeClass("cur_a");
			     	cur.addClass("cur_a");
			     	var type=cur.attr("data-type");
			     	if(type==0){
			     		obj.find(".col-rtype").removeClass("none");
			     		var _isHZEmpty=obj.find("#hz-temp-box").html();
			     		var _isZZEmpty=obj.find("#zz-temp-box").html();
			     		if(_isHZEmpty==""){
			     			obj.find(".tab-gather").addClass("none");
			     		}else if(_isZZEmpty==""){
			     			obj.find(".tab-whole").addClass("none");
			     		}
			     		obj.find(".tab-whole").css({"padding-top":"0"});
			     	}else if(type==1){
			     		obj.find(".col-rtype").addClass("none");
			     		obj.find(".tab-gather").removeClass("none");
			     	}else{
			     		obj.find(".col-rtype").addClass("none");
			     		obj.find(".tab-whole").removeClass("none");
			     	}
			     }
			});
		},
		/*弹窗事件*/
		bindDialogEvent:function(){
			var obj=$("#rightColBox"),that=this;
		//绑定操作栏事件
		 var _el=obj.find(".centralized_Ind_D  .b  ul li").find(".detail_Choices");
		  $.each(_el,function(i,o){
		  	 var evt_book=$(o).find(".a_Booked"),
		  	 	 evt_yytz=$(o).find(".a_BookOutRented"),
		  	 	 evt_tuiding=$(o).find(".a_cancle_reserved"),
		  	 	 evt_stop=$(o).find(".a_StopUse"),
		  	 	 evt_restore=$(o).find(".a_restoreUsing"),
		  	 	 evt_cxtz=$(o).find(".a_cancle_tz"),//撤销退租
		  	 	 evt_cz=$(o).find(".a_onRenting"),//出租操作
		  	 	 evt_ck=$(o).find(".a_Detail"),//查看操作
		  	 	 evt_tuizu=$(o).find(".a_OutRented");
		  	  evt_book.off().on("click",function(){
//		  	  	 console.log("预定处理");
		  	  	 var cur=$(this);
		  	  	 if(!cur.parents("dd").hasClass("editingStatusActive")){
		  	  	 	$(".editingStatusActive").removeClass(".editingStatusActive");
		  	  	 	cur.parents("dd").addClass("editingStatusActive");
		  	  		that.roomReserveDeal(cur);
		  	  	 }
		  	  });
		  	  evt_yytz.off().on("click",function(){
//		  	  	console.log("预约退租");
		  	  	var cur=$(this);
		  	  	 if(!cur.parents("dd").hasClass("editingStatusActive")){
		  	  	 	$(".editingStatusActive").removeClass(".editingStatusActive");
		  	  	 	cur.parents("dd").addClass("editingStatusActive");
		  	  		that.appointReturnMoney(cur);
		  	  	 }
		  	  });
		  	  evt_tuiding.off().on("click",function(){
//		  	  		console.log("退订");
		  	  		var cur=$(this);
			  	  	 if(!cur.parents("dd").hasClass("editingStatusActive")){
			  	  	 	$(".editingStatusActive").removeClass(".editingStatusActive");
			  	  	 	cur.parents("dd").addClass("editingStatusActive");
			  	  		that.cancleReserved(cur);
			  	  	 }
		  	  });
		  	  evt_stop.off().on("click",function(){
		  	  	 var  cur=$(this);
		  	  	 if(!cur.parents("dd").hasClass("editingStatusActive")){
		  	  	 	$(".editingStatusActive").removeClass(".editingStatusActive");
		  	  	 	cur.parents("dd").addClass("editingStatusActive");
		  	  		that.roomStopUseForFix(cur);
		  	  	 }
		  	  });
		  	  evt_tuizu.off().on("click",function(){
//		  	  	console.log("退租");
		  	  	 var  cur=$(this);
		  	  	 if(!cur.parents("dd").hasClass("editingStatusActive")){
		  	  	 	$(".editingStatusActive").removeClass(".editingStatusActive");
		  	  	 	cur.parents("dd").addClass("editingStatusActive");
		  	  		that.roomSecurPayBack(cur);
		  	  	 }
		  	  });
		  	  evt_restore.off().on("click",function(){
//		  	  	 console.log("恢复使用");
		  	  	  var  cur=$(this);
		  	  	 if(!cur.parents("dd").hasClass("editingStatusActive")){
		  	  	 	$(".editingStatusActive").removeClass(".editingStatusActive");
		  	  	 	cur.parents("dd").addClass("editingStatusActive");
		  	  		that.roomRestoreUsing(cur);
		  	  	 }
		  	  });
		  	  evt_cxtz.off().on("click",function(){
//		  	  	 console.log("撤销退租");
		  	  	  var  cur=$(this);
		  	  	 if(!cur.parents("dd").hasClass("editingStatusActive")){
		  	  	 	$(".editingStatusActive").removeClass(".editingStatusActive");
		  	  	 	cur.parents("dd").addClass("editingStatusActive");
		  	  		that.roomFallBackTuizu(cur);
		  	  	 }
		  	  });
		  	  evt_cz.off().on("click",function(){
//		  	  	 console.log("出租操作");
		  	  	  var  cur=$(this);
		  	  	 if(!cur.parents("dd").hasClass("editingStatusActive")){
		  	  	 	$(".editingStatusActive").removeClass(".editingStatusActive");
		  	  	 	cur.parents("dd").addClass("editingStatusActive");
		  	  		that.rentRoomAction(cur);
		  	  	 }
		  	  });
					evt_ck.off().on("click",function(){
		  	  	  var  cur=$(this);
				var ctag = WindowTag.getTagByUrlHash(cur.attr("data-href"));
				window.WindowTag.openTag(cur.attr("data-href"));
		  	  });
		  });
		},
		/*
		*@func 出租房间
		*@param cur 当前选中房间/套间
		*/
		rentRoomAction:function(cur){
			var url=cur.attr("data-url"),that=this,rid=cur.attr("data-id"),
				name=cur.attr("name");
			var _dt=name+"="+rid;
			url+="&"+_dt;
			//获取预定人列表数据
			Ajax.doAjax("get",url,"",function(json){
				$(".editingStatusActive").removeClass("editingStatusActive");
				if(json.status==1){
					var count=json.count;
					if(count==1){
						window.location.href="#"+json.data[0].rental_url;
					}else if(count==0){
						window.location.href=cur.attr("data-href");
					}else{
						var inner=template("list-rendertmp-customers",json);
						var stp=$("#list-cover-customers").html();
						var d = dialog({
							title: '<i class="ifont ifont-yytz">&#xe6a3;</i><span>选择出租人</span>',
							content:stp,
							cancel:function(){
								$(".editingStatusActive").removeClass("editingStatusActive");
							}
						});
						$(".ui-dialog-button").hide();
						d.showModal();
						$("#data-temp-cz").html(inner);
						$(".cz-btns").show();
					}
					that.closeOverlay(d);
					that.setRadioevt();
					that.choseRenter(d);
				}else{
					var da=dialog({
						title: '提示',
						content:json.data,
						okValue: '确 定', drag: true,
						ok : function(){
							da.close();
						}
					});
					da.showModal();
				}
			});
		},
		/*
		 *@func 选择出租人单选框事件绑定
		 * */
		setRadioevt:function(){
			$("#data-temp-cz").find(".radio").off("click").on("click",function(){
				var cur=$(this);
				cur.children(".gou").show().siblings().hide().parents("tr").siblings("tr").find(".gou-no").show().siblings().hide();
				cur.next(":radio").attr("checked",true).parents("tr").addClass("active").siblings("tr").removeClass("active").find(":radio").removeAttr("checked");
			});
		},
		/*
		 *@func 选择出租人
		 * */
		choseRenter:function(d){
			$("#send-cz-request").off().on("click",function(){
				 var isChosen=$("#data-temp-cz").find('.active').length;
				 if(isChosen==0){
				 		var dd = dialog({
							title: '提示信息',
							content: "没有选择预订人！",
							okValue: '确 定', drag: true,
							ok : function(){
								dd.close();
							}
						});
						dd.showModal();
				 }else{
				 	$(".editingStatusActive").removeClass("editingStatusActive");
				 	var _url=$("#data-temp-cz").find('.active').find("input[name='renter_url']").val();
				 	d.close().remove();
				 	window.location.href="#"+_url;
				 }

			});
		},
		/*
		 *@func 撤销预约退租
		 * @param cur 当前选中房间/套房
		 * */
		roomFallBackTuizu:function(cur){
			var url=$("#rightColBox").find(".centralized_Ind_C").find(".choice_outRented").attr("cx-url"),
				data=cur.attr("name")+"="+cur.attr("data-id"),that=this;

			var d = dialog({
				title: '提示信息',
				content: '您正在执行撤销预约退租操作，确定？',
				cancelValue: '取消',
				cancel : function(){
					$(".editingStatusActive").removeClass("editingStatusActive");
				},
				okValue: '确 定', drag: true,
				ok : function(){
					d.close();
					Ajax.doAjax("post",url,data,function(json){
						var _sj=json.rental_data,dt="";
						if(json.status==1){
							if(_sj!="" && _sj!=null){
								dt={
									roomUserName:_sj.name?_sj.name:"",
									roomUserPhone:_sj.money?_sj.money:"",
									nextPaytime:_sj.next_pay_time?_sj.next_pay_time:"",
									nextPaymoney:_sj.total_money?_sj.total_money:""
								};
							}
							finalTemp=template("suc-render-rented",dt);
							finalStaticTemp='<div class="roomActive">'
											+	'<span class="icon_Style ifont sex-man"><span class="man">&#xe654;</span></span>'
//												+	'<span class="icon_Style ifont sex-women"><span class="woman">&#xe656;</span></span>'
											+'</div>';
							if(typeof(finalTemp)=="function"){
								finalTemp='<ol><li><span class="name">暂时无法获取租客信息</span></li></ol>';
								finalStaticTemp="";
							}
							var _markOnlyEle=$(".editingStatusActive");
							_markOnlyEle.find(".tc_Detail").html("").html(finalTemp);
							var _sex=_markOnlyEle.find(".a_Detail").attr("data-sex");
							if(_sex==1){
								finalStaticTemp='<div class="roomActive">'
												+	'<span class="icon_Style ifont sex-man"><span class="man">&#xe654;</span></span>'
												+'</div>';
							}else if(_sex==2){
								finalStaticTemp='<div class="roomActive">'
												+	'<span class="icon_Style ifont sex-women"><span class="woman">&#xe656;</span></span>'
												+'</div>';
							}
							var _replcReservedEle=_markOnlyEle.find(".remark-tuizu");//默认预约退租状态
							_markOnlyEle.attr("data-rentStyle","Rented");//撤销预约退租成功
							if(_replcReservedEle && _replcReservedEle.length>0){
								_replcReservedEle.replaceWith(finalStaticTemp);
								var _name=_markOnlyEle.find(".a_cancle_tz").attr("name"),
									_roomNum=_markOnlyEle.find(".a_cancle_tz").attr("data-room-number"),
									_id=_markOnlyEle.find(".a_cancle_tz").attr("data-id");
								var yytzTmp='<a class="a_BookOutRented" data-room-number="'+_roomNum+'" name="'+_name+'" data-id="'+_id+'" href="javascript:;">预约退租</a>';
								_markOnlyEle.find(".a_cancle_tz").replaceWith(yytzTmp);
								//撤销预约退租后的状态参数改变
								var _rtype=2,_status={
									status:2,
									is_yytz:0,
									msg:{
										name:_sj.name?_sj.name:"",
										phone:_sj.money?_sj.money:"",
										total_money:_sj.next_pay_time?_sj.next_pay_time:"",
										next_pay_time:_sj.total_money?_sj.total_money:""
									}
								};
								if(_name == "house_id"){
									_rtype=1;//整租
								}
								that.trigFilterData(_rtype,_status,_id);
							}
							//重新绑定操作栏点击事件
							that.bindDialogEvent();
							_markOnlyEle.removeClass("editingStatusActive");

							var d = dialog({
								title: '提示信息',
								content: '取消预约退租成功！',
								okValue: '确 定', drag: true,
								ok : function(){
									d.close();
								}
							});
							d.showModal();
						}else{
							$(".editingStatusActive").removeClass("editingStatusActive");
							var d = dialog({
								title: '提示信息',
								content: json.message,
								okValue: '确 定', drag: true,
								ok : function(){
									d.close();
								}
							});
							d.showModal();
						}
					});
				}
			});
			d.showModal();
		},
		/*
		 *@func 退租
		 * @param cur 当前选中房间/套房
		 * */
		roomSecurPayBack:function(cur){
			var stp=$("#tuizu-cover-tmp").html(),that=this;
			var rid=cur.attr("data-id"),
			roomNUm=cur.attr("data-room-number"),
			name=cur.attr("name");
			var _dt=name+"="+rid;//hourse_id/room_id参数

			var d = dialog({
				title: '<i class="ifont">&#xe663;</i><span>退租提醒</span>',
				content: '您正在执行退租操作，确定？',
				cancelValue: '取消',
				cancel : function(){
					$(".editingStatusActive").removeClass("editingStatusActive");
				},
				okValue: '确 定', drag: true,
				ok : function(){
					d.close();
					that.doTuiZuRequest(_dt);
				}
			});
			d.showModal();
		},
		/*
		 *@func 退租请求交互处理
		 * @param cur 当前选中房间/套房
		 * */
		doTuiZuRequest:function(dt){
			var that=this;
			var  url=urlHelper.make('House-Room/rentalout');
			var data=dt;
			Ajax.doAjax("post",url,data,function(json){
				if(json.status==1){
					var _sj=json.house_data,finalTemp,finalStaticTemp;
					var dt={
						emptyDays:_sj.day,
						monthPay:_sj.money
					};
					finalTemp=template("suc-render-unrented",dt);
					finalStaticTemp=$("#unrentedStaticTemp").html();
					$(".editingStatusActive").find(".tc_Detail").html("").html(finalTemp);
					var _markOnlyEle=$(".editingStatusActive");
					var _replcTuizuEle=_markOnlyEle.find(".remark-tuizu"),//默认预约退租状态
						__replcRentedEle=_markOnlyEle.find(".roomActive");//已退租状态
					if(_replcTuizuEle && _replcTuizuEle.length>0){
						_replcTuizuEle.replaceWith(finalStaticTemp);
						var _name=_markOnlyEle.find(".a_cancle_tz").attr("name"),
							_id=_markOnlyEle.find(".a_cancle_tz").attr("data-id"),
							_detail=_markOnlyEle.find(".a_Detail").attr("data-href");
						var dt={
							detailUrl:_detail,
							rentalUrl:json.rental_url,
							rtype:_name,
							rrid:_id
						};
						var _toolBar=template("tool-bar-unrented",dt);
						_markOnlyEle.find(".detail_Choices ul").html(_toolBar);
						//退租成功状态值修改
						var _rtype=2,_status={
							status:1,
							is_yytz:0,
							emp_msg:{
								day:_sj.day,
								money:_sj.money
							}
						};
						if(_name=="house_id"){
							_rtype=1;
						}
						that.trigFilterData(_rtype,_status,_id);
					}else if(__replcRentedEle && __replcRentedEle.length>0){
						__replcRentedEle.replaceWith(finalStaticTemp);
						var _name=_markOnlyEle.find(".a_BookOutRented").attr("name"),
							_id=_markOnlyEle.find(".a_BookOutRented").attr("data-id"),
							_detail=_markOnlyEle.find(".a_Detail").attr("data-href");
						var dt={
							detailUrl:_detail,
							rentalUrl:json.rental_url,
							rtype:_name,
							rrid:_id
						};
						var _toolBar=template("tool-bar-unrented",dt);
						_markOnlyEle.find(".detail_Choices ul").html(_toolBar);
						//退租成功状态值修改
						var _rtype=2,_status={
							status:1,
							is_yytz:0,
							emp_msg:{
								day:_sj.day,
								money:_sj.money
							}
							};
						if(_name=="house_id"){
							_rtype=1;
						}
						that.trigFilterData(_rtype,_status,_id);
					}
					//重新绑定操作栏点击事件
					that.bindDialogEvent();
					_markOnlyEle.removeClass("editingStatusActive");
					$(".tuizu-inner-box").find(".cancle-over-trigger").trigger("click");
					var da=dialog({
						title:"提示信息",
						content:'退租成功',
						okValue: '确 定', drag: true,
						ok: function () {
							if(json.status==1){
								window.location.href="#"+json.finance_url;
							}else{
								da.close().remove();
							}
						}
					});
					da.showModal();
				}else{
					$(".editingStatusActive").removeClass("editingStatusActive");
					var da=dialog({
						title:"提示信息",
						content:json.message,
						okValue: '确 定', drag: true,
						ok: function () {
							if(json.status==1){
								window.location.href="#"+json.finance_url;
							}else{
								da.close().remove();
							}
						}
					});
					da.showModal();
				}
			});
		},
		/*
		 *@func 房间恢复使用
		 * @param cur 当前选中房间/套房
		 * */
		roomRestoreUsing:function(cur){
			var url=$("#rightColBox").find(".centralized_Ind_C").find(".choice_stopUse").attr("hf-url"),
			data=cur.attr("name")+"="+cur.attr("data-id"),that=this;
			var d = dialog({
				title: '提示信息',
				content: '您正在执行取消停用操作，确定？',
				cancelValue: '取消',
				cancel : function(){
					$(".editingStatusActive").removeClass("editingStatusActive");
				},
				okValue: '确 定', drag: true,
				ok : function(){
					d.close();
					Ajax.doAjax("post",url,data,function(json){
						if(json.status==1){
							var _sj=json.house_data;
							var dt={
								emptyDays:_sj.day,
								monthPay:_sj.money
							};
							finalTemp=template("suc-render-unrented",dt);
							finalStaticTemp=$("#unrentedStaticTemp").html();
							$(".editingStatusActive").find(".tc_Detail").html("").html(finalTemp);
							var _markOnlyEle=$(".editingStatusActive");
							var _replcReservedEle=_markOnlyEle.find(".roomStop");//默认停用状态
							$(".editingStatusActive").attr("data-rentStyle","notRented");//恢复房间成功
							if(_replcReservedEle && _replcReservedEle.length>0){
								_replcReservedEle.replaceWith(finalStaticTemp);
								var _name=_markOnlyEle.find(".a_Booked").attr("name"),
									_id=_markOnlyEle.find(".a_Booked").attr("data-id"),
									_detail=_markOnlyEle.find(".a_Detail").attr("data-href");
								var dt={
									detailUrl:_detail,
									rentalUrl:json.rental_url,
									rtype:_name,
									rrid:_id
								};
								var _toolBar=template("tool-bar-unrented",dt);
								_markOnlyEle.find(".detail_Choices ul").html(_toolBar);
								//恢复使用成功状态值修改
								var _rtype=2,_status={
									status:1,
									emp_msg:{
										money:_sj.money,
										day:_sj.day
									}
								};
								if(_name=="house_id"){
									_rtype=1;
								}
								that.trigFilterData(_rtype,_status,_id);
							}
							//重新绑定操作栏点击事件
							that.bindDialogEvent();
							$(".editingStatusActive").removeClass("editingStatusActive");

							var d = dialog({
								title: '提示信息',
								content: '取消停用成功！',
								okValue: '确 定', drag: true,
								ok : function(){
									d.close();
								}
							});
							d.showModal();
						}else{
							$(".editingStatusActive").removeClass("editingStatusActive");
							var d = dialog({
								title: '提示信息',
								content: json.message,
								okValue: '确 定', drag: true,
								ok : function(){
									d.close();
								}
							});
							d.showModal();
						}
					});
				}
			});
			d.showModal();
		},
		/*
		 *@func 退订房间事件处理
		 * @param cur 当前选中房间/套房
		 * */
		cancleReserved:function(cur){
			var url=$("#getMapData").attr("tuidurl"),that=this;
			var rid=cur.attr("data-id"),
				name=cur.attr("name");
			var _dt=name+"="+rid;//hourse_id/room_id参数
			url+="&"+_dt;
			//获取预定人列表数据
			Ajax.doAjax("get",url,"",function(json){
				if(json.status==1){
					var inner=template("list-rendertmp-reserved",json);
					var stp=$("#list-cover-reserved").html();
					var d = dialog({
						title: '<i class="ifont ifont-yytz">&#xe6a3;</i><span>选择退定人</span>',
						content:stp,
						cancel:function(){
							$(".editingStatusActive").removeClass("editingStatusActive");
						}
					});
					$(".ui-dialog-button").hide();
					d.showModal();
					$("#data-temp-booked").html(inner);
					$(".td-btns").show();
					that.closeOverlay(d);
					that.setCboxEvt();
					that.tuiDingRooms(_dt,d);
				}else{
					var da=dialog({
						title: '提示',
						content:json.message
					});
					da.showModal();
					setTimeout(function(){
						if(json.status!=1){
							da.close().remove();
						}
					},1200);
				}
			});
		},
		/*
		 *@func 复选框选中/取消选中事件
		 * */
		setCboxEvt:function(){
			$("#data-temp-booked .checkbox").off("click").on("click",function(){
				$(this).toggleClass("checked");
				if($(this).hasClass("checked")){
					$(this).children(".gou").show();
					$(this).next("input").attr("checked",true);
				}else{
					$(this).children(".gou").hide();
					$(this).next("input").removeAttr("checked");
				}
			});
		},
		/*
		 *@func 保存退订房间信息
		 * */
		tuiDingRooms:function(_dt,d_auto){
			var cur=$("#send-tuiding-request"),that=this,
				_form=$("#data-temp-booked");
			cur.off().on("click",function(){
				d_auto.close().remove();
				var len=_form.find("input[type='checkbox']:checked").length,
					_el=$(this);
				if(len>0){
					var _ele=_form.find("input[type='checkbox']"),cm="";
					$.each(_ele,function(i,o){
						if($(o).attr("checked")=="checked" || $(o).attr("checked")==true){
							cm+=","+$(o).val();
						}
					});
					cm=cm.replace(cm.substr(0,1),"");
					var data="reserve_id="+cm+"&"+_dt,
						url=cur.attr("tuidurl");
					if(!_el.hasClass("clicked")){
						_el.parent().find(".clicked").removeClass("clicked");
						_el.addClass("clicked").text("保存中...");
						Ajax.doAjax("post",url,data,function(json){
							_el.removeClass("clicked").text("保存");
							if(json.status==1){
								var _target=_form.find("input[type='checkbox']:checked");
								$.each(_target,function(j,item){
									$(item).parent().parent().remove();
								});
								var  dt,finalTemp,finalStaticTemp,ftype=0;
								//还有预定人
								if(json.reserve_data && json.reserve_data!="" && json.reserve_data!=null){
									ftype=1;
									var _dj=json.reserve_data;
									dt={
										ydUser:_dj.name,
										ydPhone:_dj.phone,
										ydPayment:_dj.money,
										ydBegindate:_dj.stime_c,
										ydEnddate:_dj.etime_c
									};
									finalTemp=template("suc-render-yd",dt);
									finalStaticTemp=$("#ydStaticTemp").html();
								}else{
									ftype=0;
									//无预定人
									var _sj=json.house_data;
									dt={
										emptyDays:_sj.day,
										monthPay:_sj.money
									};
									finalTemp=template("suc-render-unrented",dt);
									finalStaticTemp=$("#unrentedStaticTemp").html();
								}
								$(".editingStatusActive").find(".tc_Detail").html("").html(finalTemp);
								var _markOnlyEle=$(".editingStatusActive");
								var _replcReservedEle=_markOnlyEle.find(".reservedRooms");//已预定
								if(_replcReservedEle && _replcReservedEle.length>0){
									_replcReservedEle.replaceWith(finalStaticTemp);
									//处理未出租操作栏
									if(ftype==0){
										var _name=_markOnlyEle.find(".a_cancle_reserved").attr("name"),
											_id=_markOnlyEle.find(".a_cancle_reserved").attr("data-id");
										var stopTmp='<a class="a_StopUse" name="'+_name+'" data-id="'+_id+'" href="javascript:;">停用</a>';
										_markOnlyEle.find(".a_cancle_reserved").replaceWith(stopTmp);
										var _rtype=2,_status={
											status:1,
											is_yd:0,
											emp_msg:{
												day:_sj.day,
												money:_sj.money
											}
										};//未出租
										if(_name=="house_id"){
											_rtype=1;
										}
										that.trigFilterData(_rtype,_status,_id);
									}
								}
								//重新绑定操作栏点击事件
								that.bindDialogEvent();
								//重置元素
								$(".editingStatusActive").removeClass("editingStatusActive");
								var len=_form.find("input[type='checkbox']").length;
								if(len==0){
									$(".td-btns").find(".cancle-over-trigger").trigger("click");
								}
								window.location.href="#"+json.reserve_url;
							}else{
							$(".editingStatusActive").removeClass("editingStatusActive");
								var dd=dialog({
									title:"提示",
									content:json.message
								});
								dd.showModal();
								setTimeout(function(){
									dd.close().remove();
								},1200);
							}
						});
					}
				}else{
					var d=dialog({
						title: '提示',
						content:"请至少选择一个退订人"
					});
					d.showModal();
					setTimeout(function(){
						d.close().remove();
					},1500);
				}
			});
		},
		/*
		 *@func 预约退租事件处理
		 * @param cur 当前选中房间/套房
		 * */
		appointReturnMoney:function(cur){
			var stp=$("#cover-room-yytz").html(),that=this;
			var d = dialog({
				title: '<i class="ifont ifont-yytz">&#xe663;</i><span>预约退租</span>',
				content:stp,
				cancel:function(){
					$(".editingStatusActive").removeClass("editingStatusActive");
				}
			});
			$(".ui-dialog-button").hide();
			d.showModal();
			var rid=cur.attr("data-id"),
				roomNUm=cur.attr("data-room-number"),
				name=cur.attr("name");
			var _dt=name+"="+rid;//hourse_id/room_id参数
			that.validRoomCheckOutForm(_dt);//表单验证初始
			var data_input=$("#roomCheckoutForm").find(".date-input");
			$.each(data_input, function(i,o) {
				 $(o).click(function(){
				 	calendar.inite();
				 });
			});
			$("#roomCheckoutForm").find(".roomNum").text(roomNUm);
			that.closeOverlay(d);
			that.setFormFblurEvt($("#roomCheckoutForm"));
		},
		/*
		 *@func 初始预约退租表单验证
		 * */
		validRoomCheckOutForm:function(dt){
				var that=this;
			$("#roomCheckoutForm").Validform({
					btnSubmit : "#room-tz-info-save",
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
			            	that.RoomCheckoutDatasubmit(form,dt);
			            	return false;
		            }
				});

		},
		/*
		 *@func 预约退租房间数据
		 * @param cur 当前选中房间/套房
		 * */
		RoomCheckoutDatasubmit:function(form,dt){
			var data="",that=district.prototype;
			data="start_time="+$(form).find("input[name='start_time']").val()+"&remark="+$("#remark-for-tz-room").val()+"&"+dt;
			var cur=$(form).find("#room-tz-info-save"),
				durl=cur.attr("tzurl"),
				obj=$("#roomCheckoutForm");
			if(!cur.hasClass('clicked')){
				cur.addClass("clicked").text("保存中...");
				Ajax.doAjax("POST",durl,data,function(json){
					cur.removeClass("clicked").text("保存");
					if(json.status==1){
						var dt={
							tzDate:obj.find("input[name='start_time']").val(),
							tzRemark:obj.find("#remark-for-tz-room").val()
						};
						var tzInfoTemp=template("suc-render-yytz",dt);
//						console.log(ydTemp);
						$(".editingStatusActive").find(".tc_Detail").html("").html(tzInfoTemp);
						var tzStaticTemp=$("#yytzStaticTemp").html();//预定成功渲染提示信息模板|公用
						//对应不同状态下的预定操作，替换对应模板
						var _markOnlyEle=$(".editingStatusActive");
						var _replcRentedEle=_markOnlyEle.find(".roomActive");//已出租
						$(".editingStatusActive").attr("data-rentstyle","outRented");//预约成功，更改页面绑定状态，避免删除时出错
							if(_replcRentedEle && _replcRentedEle.length>0){
								_replcRentedEle.replaceWith(tzStaticTemp);//已租状态下点击预约退租
								//修改成停用栏选项
								var _name=_markOnlyEle.find(".a_BookOutRented").attr("name"),
									detailUrl=_markOnlyEle.find(".a_Detail").attr("data-href"),
									roomNum=_markOnlyEle.find(".a_Detail").attr("data-room-number"),
									xzUrl=_markOnlyEle.find(".a_GoOnRented").attr("href"),
									_id=_markOnlyEle.find(".a_BookOutRented").attr("data-id");
								var dt={
									detailUrl:detailUrl,
									rtype:_name,
									rrid:_id,
									xzUrl:xzUrl,
									roomNum:roomNum,
									rentalUrl:json.rental_url
								};
								var _genToolbar=template("tool-bar-yytz",dt);
								_markOnlyEle.find(".detail_Choices ul").html(_genToolbar);
								//预约退租成功状态值修改
								var _rtype=2,_status={
									status:2,
									is_yytz:1,
									msg_yytz:{
										back_rental_time_c:obj.find("input[name='start_time']").val(),
										remark:obj.find("#remark-for-tz-room").val()
									}
								};
								if(_name=="house_id"){
									_rtype=1;
								}
								that.trigFilterData(_rtype,_status,_id);
							}
						//重新绑定操作栏点击事件
						that.bindDialogEvent();
						//重置元素
						$(".editingStatusActive").removeClass("editingStatusActive");
					}else{
						$(".editingStatusActive").removeClass("editingStatusActive");
					}
					var da=dialog({
						title: '提示',
						content:json.message,
						cancelValue: '确定',
						cancel: function () {}
					});
					da.showModal();
					setTimeout(function(){
						da.close().remove();
						if(json.status==1){
							obj.find(".cancle-over-trigger").trigger("click");
						}
					},1200);
				});
			}
		},
		/*
		 *@func 停用房间事件处理
		 * @param cur 当前选中房间/套房
		 * */
		roomStopUseForFix:function(cur){
			var stp=$("#cover-distri-forbiden").html(),that=this;
			var d = dialog({
				title: '<i class="ifont">&#xe62e;</i><span>停用房间</span>',
				content:stp,
				cancel:function(){
					$(".editingStatusActive").removeClass("editingStatusActive");
				}
			});
			$(".ui-dialog-button").hide();
			d.showModal();
			var rid=cur.attr("data-id"),
				name=cur.attr("name");
			var _dt=name+"="+rid;//hourse_id/room_id参数
			that.validStopRoomForm(_dt);//表单验证初始
			var data_input=$("#roomStopUseForm").find(".date-input");
			$.each(data_input, function(i,o) {
				 $(o).click(function(){
				 	calendar.inite();
				 });
			});
			that.closeOverlay(d);
			that.setFormFblurEvt($("#roomStopUseForm"));
		},
		/*
		 *@func 停用表单数据提交
		 * */
		stopRoomDatasubmit:function(form,dt){
			var data="",_txt=$(form).find("input[type='text']"),that=district.prototype;
			$.each(_txt,function(j,item){
				var vv=$(item).val();
				data+=$(item).attr("name")+"="+vv+"&";
			});
			data+=$("#remark-for-stop-room").attr("name")+"="+$("#remark-for-stop-room").val()+"&"+dt;
			var cur=$(form).find("#room-stop-info-save"),
				durl=cur.attr("stopurl"),
				obj=$("#roomStopUseForm");
			if(!cur.hasClass('clicked')){
				cur.addClass("clicked").text("保存中...");
				Ajax.doAjax("POST",durl,data,function(json){
					cur.removeClass("clicked").text("保存");
					if(json.status==1){
						var dt={
							tyBegindate:obj.find("input[name='start_time']").val(),
							tyEnddate:obj.find("input[name='end_time']").val(),
							tystopRemark:obj.find("#remark-for-stop-room").val()
						};
						var ydTemp=template("suc-render-ty",dt);
//						console.log(ydTemp);
						$(".editingStatusActive").find(".tc_Detail").html("").html(ydTemp);
						var tyStaticTemp=$("#tyStaticTemp").html();//预定成功渲染提示信息模板|公用
						//对应不同状态下的预定操作，替换对应模板
						var _markOnlyEle=$(".editingStatusActive");
						var _replcNotRentedEle=_markOnlyEle.find(".notRented");//未出租(只有未出租情况下才有停用)
						$(".editingStatusActive").attr("data-rentStyle","stopUse");//停用房间成功
							if(_replcNotRentedEle && _replcNotRentedEle.length>0) {
								_replcNotRentedEle.replaceWith(tyStaticTemp);
								//修改成停用栏选项
								var _name=_markOnlyEle.find(".a_Booked").attr("name"),
									detailUrl=_markOnlyEle.find(".a_Detail").attr("data-href"),
									_id=_markOnlyEle.find(".a_Booked").attr("data-id");
								var dt={
									detailUrl:detailUrl,
									rtype:_name,
									rrid:_id,
									rentalUrl:json.rental_url
								};
								var _genToolbar=template("tool-bar-stop",dt);
								_markOnlyEle.find(".detail_Choices ul").html(_genToolbar);
								//停用房间成功状态值修改
								var _rtype=2,_status={
									status:3,
									stop_msg:{
										remark:obj.find("#remark-for-stop-room").val(),
										start_time_c:obj.find("input[name='start_time']").val(),
										end_time_c:obj.find("input[name='end_time']").val()
									}
								};
								if(_name=="house_id"){
									_rtype=1;
								}
								that.trigFilterData(_rtype,_status,_id);
							}
						//重新绑定操作栏点击事件
						that.bindDialogEvent();
						//重置元素
						$(".editingStatusActive").removeClass("editingStatusActive");
					}else{
						$(".editingStatusActive").removeClass("editingStatusActive");
					}
					var da=dialog({
						title: '提示',
						content:json.message,
						cancelValue: '确定',
						cancel: function () {}
					});
					da.showModal();
					setTimeout(function(){
						da.close().remove();
						if(json.status==1){
							obj.find(".cancle-over-trigger").trigger("click");
						}
					},1200);
				});
			}
		},
		/*
		 *@func 初始停用遮罩表单
		 * */
		validStopRoomForm:function(dt){
			var that=this;
			$("#roomStopUseForm").Validform({
					btnSubmit : "#room-stop-info-save",
					showAllError : true,
					tiptype : function(msg,o,cssctl){
						var objtip=o.obj;
		           		objtip=objtip.parents(".jzf-col-r").find(".check-error");
		                cssctl(objtip,o.type);
		                objtip.text(msg);
		                if(objtip.parent().parent().find(".Validform_error").length>0){
		                		var curInp=objtip.parent().parent().find("input");
		                		if(curInp.attr("name")=="start_time" || curInp.attr("name")=="end_time"){
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
		               	 	if(curInp.attr("name")=="start_time" || curInp.attr("name")=="end_time"){
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
		            	$("#roomStopUseForm").find("input,textarea").trigger("blur");
		            },
		            callback : function(form){
		            	    var sdate=$(form).find("input[name='start_time']").val(),
		            	    	edate=$(form).find("input[name='end_time']").val();
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
			            	that.stopRoomDatasubmit(form,dt);
			            	return false;
		            }
				});
		},
		/*
		 *@func 房间预定事件处理
		 * @param cur 当前选中房间/套房
		 * */
		roomReserveDeal:function(cur){
			var rsvTemp=$("#cover-distri-reserve").html(),that=this;
			var d = dialog({
				title: '<i class="ifont">&#xf0077;</i><span>预定人信息</span>',
				content: rsvTemp,
				cancel:function(){
					$(".editingStatusActive").removeClass("editingStatusActive");
				}
			});
			$(".ui-dialog-button").hide();
			d.showModal();
			var rid=cur.attr("data-id"),
				name=cur.attr("name");
			var _dt=name+"="+rid;
			that.validReserveForm(_dt);//表单验证初始
			that.bindPluginEvt();//下拉事件绑定
			that.closeOverlay(d);
			that.setFormFblurEvt($("#distri-reserve-box"));

			//=====
			//检测预订人是否存在
			var score  = '';			//平均分数
			var counta = '';       //评价次数
			var urlsauto = '';
			$("input[name='idcard']",".ui-dialog-body").off("blur").on("blur",function(){
				var thats = $(this);
				var name = $("input[name='name']",$(".ui-dialog-body"));
				var phone = $("input[name='phone']",$(".ui-dialog-body"));
				var url = $(this).attr("data-score-action");
				var type = "post";
				var idcard = $(this).val();
				var data = {
					idcard : idcard
				};
				if(!$(this).hasClass("Validform_error") && $.trim($(this).val())!=$(this).attr("prevval")){
					$(this).attr("prevval", $.trim($(this).val()));
					Ajax.doAjax(type,url,data,function(data){
						if(data.status == 1){
							name.val(data.tdata.name).siblings(".check-error-ts").hide();
							phone.val(data.tdata.phone).siblings(".check-error-ts").hide();
							score = data.avgscore.avgscore;
							counta = data.avgscore.allComment;
							if(counta > 0){
								thats.siblings(".tip-black-list").show().find(".tip-msg .red").text(score);
								thats.siblings(".tip-black-list").find(".this-close").off("click").on("click",function(){
									$(this).parent().hide();
								});
								thats.siblings(".tip-black-list").find(".tip-msg a").off("click").on("click",function(){
									var index = 0;
									showcontent(index);
								});
							}
						}
					});
				}
			});
			var content_auto = function (index){
				var urls = urlsauto;
				var types = "get";
				var idcard = $("input[name='idcard']",$(".ui-dialog-body")).val();
				var current_page = index + 1;
				var datas = {
									idcard : idcard,
									current_page : current_page
							};
				Ajax.doAjax(types,urls,datas,function(data){
					if(data.status == 1){
									if(typeof data.data.data[0] == "undefined") return false;
									var namea = data.data.data[0].name;
									var phonea = data.data.data[0].phone;
									var id_carda =  data.data.data[0].idcard;
									var pages_Total = data.data.page.count;   //总条数
									var pages_Count = data.data.page.size;    //每页条数
									var contentsauto = '';
									var stars = '';
									var num_star = 1;
									for(var i  in data.data.data){
										num_star =  parseInt(data.data.data[i].score/20);
										switch(num_star){
											case 1:
														stars = '<td width="200"><div class="raty"><img alt="1" src="web/js/Raty/images/star-on.png" title="不爱干净，邋遢的很"><img alt="2" src="web/js/Raty/images/star-off.png" title="不是很爱惜房屋"><img alt="3" src="web/js/Raty/images/star-off.png" title="家具没有损坏"><img alt="4" src="web/js/Raty/images/star-off.png" title="爱卫生，房屋打扫的很干净"><img alt="5" src="web/js/Raty/images/star-off.png" title="很爱卫生，家具也打扫的很干净！是个好租客，欢迎下次再来"></div></td>';
														break;
											case 2:
														stars = '<td width="200"><div class="raty"><img alt="1" src="web/js/Raty/images/star-on.png" title="不爱干净，邋遢的很"><img alt="2" src="web/js/Raty/images/star-on.png" title="不是很爱惜房屋"><img alt="3" src="web/js/Raty/images/star-off.png" title="家具没有损坏"><img alt="4" src="web/js/Raty/images/star-off.png" title="爱卫生，房屋打扫的很干净"><img alt="5" src="web/js/Raty/images/star-off.png" title="很爱卫生，家具也打扫的很干净！是个好租客，欢迎下次再来"></div></td>';
														break;
											case 3:
														stars = '<td width="200"><div class="raty"><img alt="1" src="web/js/Raty/images/star-on.png" title="不爱干净，邋遢的很"><img alt="2" src="web/js/Raty/images/star-on.png" title="不是很爱惜房屋"><img alt="3" src="web/js/Raty/images/star-on.png" title="家具没有损坏"><img alt="4" src="web/js/Raty/images/star-off.png" title="爱卫生，房屋打扫的很干净"><img alt="5" src="web/js/Raty/images/star-off.png" title="很爱卫生，家具也打扫的很干净！是个好租客，欢迎下次再来"></div></td>';
														break;
											case 4:
														stars = '<td width="200"><div class="raty"><img alt="1" src="web/js/Raty/images/star-on.png" title="不爱干净，邋遢的很"><img alt="2" src="web/js/Raty/images/star-on.png" title="不是很爱惜房屋"><img alt="3" src="web/js/Raty/images/star-on.png" title="家具没有损坏"><img alt="4" src="web/js/Raty/images/star-on.png" title="爱卫生，房屋打扫的很干净"><img alt="5" src="web/js/Raty/images/star-off.png" title="很爱卫生，家具也打扫的很干净！是个好租客，欢迎下次再来"></div></td>';
														break;
											case 5:
														stars = '<td width="200"><div class="raty"><img alt="1" src="web/js/Raty/images/star-on.png" title="不爱干净，邋遢的很"><img alt="2" src="web/js/Raty/images/star-on.png" title="不是很爱惜房屋"><img alt="3" src="web/js/Raty/images/star-on.png" title="家具没有损坏"><img alt="4" src="web/js/Raty/images/star-on.png" title="爱卫生，房屋打扫的很干净"><img alt="5" src="web/js/Raty/images/star-on.png" title="很爱卫生，家具也打扫的很干净！是个好租客，欢迎下次再来"></div></td>';
										}
										contentsauto += '<tr>'+stars+'<td>'+data.data.data[i].remark+'</td>'+'<td>'+data.data.data[i].phone+'</td>'+'<td>'+data.data.data[i].create_time+'</td></tr>';
									}
								$(".lst_tb_auto tr:gt(0)").remove();
								$(".lst_tb_auto").append(contentsauto);
					}
				});
			}
			var showcontent = function (index){
				var urls = $("input[name='idcard']",$(".ui-dialog-body")).attr("data-info-action");
				urlsauto = urls;
				var types = "get";
				var idcard = $("input[name='idcard']",$(".ui-dialog-body")).val();
				if(!!!index) index=0;
				var current_page = index + 1;
				var datas = {
									idcard : idcard,
									current_page : current_page
							};
				Ajax.doAjax(types,urls,datas,function(data){
					if(data.status == 1){
									if(typeof data.data.data[0] == "undefined") return false;
									var namea = data.data.data[0].name;
									var phonea = data.data.data[0].phone;
									var id_carda =  data.data.data[0].idcard;
									var pages_Total = data.data.page.count;   //总条数
									var pages_Count = data.data.page.size;    //每页条数
									var contentsauto = '';
									var stars = '';
									var num_star = 1;
									for(var i  in data.data.data){
										num_star =  parseInt(data.data.data[i].score/20);
										switch(num_star){
											case 1:
														stars = '<td width="200"><div class="raty"><img alt="1" src="web/js/Raty/images/star-on.png" title="不爱干净，邋遢的很"><img alt="2" src="web/js/Raty/images/star-off.png" title="不是很爱惜房屋"><img alt="3" src="web/js/Raty/images/star-off.png" title="家具没有损坏"><img alt="4" src="web/js/Raty/images/star-off.png" title="爱卫生，房屋打扫的很干净"><img alt="5" src="web/js/Raty/images/star-off.png" title="很爱卫生，家具也打扫的很干净！是个好租客，欢迎下次再来"></div></td>';
														break;
											case 2:
														stars = '<td width="200"><div class="raty"><img alt="1" src="web/js/Raty/images/star-on.png" title="不爱干净，邋遢的很"><img alt="2" src="web/js/Raty/images/star-on.png" title="不是很爱惜房屋"><img alt="3" src="web/js/Raty/images/star-off.png" title="家具没有损坏"><img alt="4" src="web/js/Raty/images/star-off.png" title="爱卫生，房屋打扫的很干净"><img alt="5" src="web/js/Raty/images/star-off.png" title="很爱卫生，家具也打扫的很干净！是个好租客，欢迎下次再来"></div></td>';
														break;
											case 3:
														stars = '<td width="200"><div class="raty"><img alt="1" src="web/js/Raty/images/star-on.png" title="不爱干净，邋遢的很"><img alt="2" src="web/js/Raty/images/star-on.png" title="不是很爱惜房屋"><img alt="3" src="web/js/Raty/images/star-on.png" title="家具没有损坏"><img alt="4" src="web/js/Raty/images/star-off.png" title="爱卫生，房屋打扫的很干净"><img alt="5" src="web/js/Raty/images/star-off.png" title="很爱卫生，家具也打扫的很干净！是个好租客，欢迎下次再来"></div></td>';
														break;
											case 4:
														stars = '<td width="200"><div class="raty"><img alt="1" src="web/js/Raty/images/star-on.png" title="不爱干净，邋遢的很"><img alt="2" src="web/js/Raty/images/star-on.png" title="不是很爱惜房屋"><img alt="3" src="web/js/Raty/images/star-on.png" title="家具没有损坏"><img alt="4" src="web/js/Raty/images/star-on.png" title="爱卫生，房屋打扫的很干净"><img alt="5" src="web/js/Raty/images/star-off.png" title="很爱卫生，家具也打扫的很干净！是个好租客，欢迎下次再来"></div></td>';
														break;
											case 5:
														stars = '<td width="200"><div class="raty"><img alt="1" src="web/js/Raty/images/star-on.png" title="不爱干净，邋遢的很"><img alt="2" src="web/js/Raty/images/star-on.png" title="不是很爱惜房屋"><img alt="3" src="web/js/Raty/images/star-on.png" title="家具没有损坏"><img alt="4" src="web/js/Raty/images/star-on.png" title="爱卫生，房屋打扫的很干净"><img alt="5" src="web/js/Raty/images/star-on.png" title="很爱卫生，家具也打扫的很干净！是个好租客，欢迎下次再来"></div></td>';
										}
										contentsauto += '<tr>'+stars+'<td>'+data.data.data[i].remark+'</td>'+'<td>'+data.data.data[i].phone+'</td>'+'<td>'+data.data.data[i].create_time+'</td></tr>';
									}
									var dd = dialog({
										title: '<i class="ifont">&#xf0077;</i><span>评价记录</span>',
										content: '<div id="custm-hideTempAuto">'
															+'<div class="crm-custm-wrap">'
																+'<div class="col-custm">'
																	+'<ul class="lin-custm-basic clearfix mb10" id="temp-custm">'
																			+'<li>'
																				+'<span>租客姓名：</span>'
																				+'<span class="gray">'+namea+'</span>'
																			+'</li>'
																			+'<li>'
																				+'<span>联系电话：</span>'
																				+'<span class="gray">'+phonea+'</span>'
																			+'</li>'
																			+'<li>'
																				+'<span>证件号码：</span>'
																				+'<span class="gray">'+id_carda+'</span>'
																			+'</li>'
																	+'</ul>'
																	+'<ul class="lin-custm-score clearfix">'
																		+'<li>'
																			+'<span>评分次数：</span>'
																			+'<span class="gray"><i>'+counta+'</i>次</span>'
																		+'</li>'
																		+'<li>'
																			+'<span>评价分数：</span>'
																			+'<span class="gray"><i>'+score+'</i></span>'
																		+'</li>'
																	+'</ul>'
																+'</div>'
																+'<table class="lst_tb lst_tb_auto">'
																	+'<tr>'
																		+'<th class="th1">评价分数</th>'
																		+'<th class="th2">评分原因</th>'
																		+'<th>操作人</th>'
																		+'<th>操作时间</th>'
																	+'</tr>'
																	+contentsauto
																+'</table>'
																+'<div class="jzf-pagination clearfix">'
																	+'<div class="pagination" id="pagination-auto-yd"></div>'
																	+'<div class="jump-to-page clearfix">'
																		+'<span>跳转至</span>'
																		+'<div class="jump-edit-box">'
																        	+'<input type="text" placeholder="页码">'
																        	+'<a href="javascript:;" class="p-btn btn btn4">GO</a>'
																        	+'<i class="page-unit">页</i>'
																        +'</div>'
																	+'</div>'
																+'</div>'
															+'</div>'
														+'</div>'
									});
									dd.showModal();
									if(data.data.page.page == 1){
										Ajax.iniPagination(pages_Total,"#pagination-auto-yd",content_auto,pages_Count);
									}
							}else{
								var dd = dialog({
												title: '提示信息',
												content: data.message,
												okValue: '确 定', drag: true,
												ok : function(){
													dd.close();
												}
											});
											dd.showModal();
					}
				});
			}
		},
		/*
		 *@func 表单焦点移入/移除错误信息展示/隐藏交互
		 * */
		setFormFblurEvt:function(form){
				var _inptEle=$(form).find("input"),
					_txarea=$(form).find("textarea");
				$.each(_inptEle,function(j,item){
					$(item).focus(function(){
						var cur=$(this),par=cur.parents(".jzf-form");
						if(cur.hasClass("Validform_error")){
							par.find("input,textarea").css("background","none");
							par.find(".check-error").parent().hide();
						}
					}).blur(function(){
						var cur=$(this);
						cur.removeAttr("style");
						if(cur.parent().find(".Validform_right").length==0){
							cur.parent().find(".check-error").parent().show();
						}else{
							cur.parent().find(".check-error").parent().hide();
						}
					});
				});
				$.each(_txarea,function(j,item){
					$(item).focus(function(){
						var cur=$(this);
						if(cur.hasClass("Validform_error")){
							cur.css("background","none");
							cur.parent().find(".check-error").parent().hide();
						}
					}).blur(function(){
						var cur=$(this);
						cur.removeAttr("style");
						if(cur.parent().find(".Validform_right").length==0){
							cur.parent().find(".check-error").parent().show();
						}else{
							cur.parent().find(".check-error").parent().hide();
						}
					});
				});
		},
		/*
		 *@func 表单初始化验证
		 * */
		validReserveForm:function(dt){
			var that=this;
			$(".reserveDistriUserForm").Validform({
					btnSubmit : "#distri-reserve-user-info-save",
					showAllError : true,
					tiptype : function(msg,o,cssctl){
						var objtip=o.obj;
		           		objtip=objtip.parents(".jzf-col-r").find(".check-error");
		                cssctl(objtip,o.type);
		                objtip.text(msg);
		                if(objtip.parent().parent().find(".Validform_error").length>0){
		                		var curInp=objtip.parent().parent().find("input");
		                		if(curInp.attr("name")=="begin_date" || curInp.attr("name")=="end_date" ||curInp.attr("name")=="ya" || curInp.attr("name")=="fu"){
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
		               	 	if(curInp.attr("name")=="begin_date" || curInp.attr("name")=="end_date"||curInp.attr("name")=="ya" || curInp.attr("name")=="fu"){
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
		            		$(".reserveDistriUserForm").find("input,textarea").trigger("blur");
		            },
		            datatype : {
		               "idcard":function(gets,obj,curform,regxp) {
		                   var reg1=/^(0|[1-9][0-9]*)$/;
		                   var length = gets.length;
		                   var str_last = gets.substr(length-1,length-1);
		                   if($.trim(gets)=="") return false;
		                   if(reg1.test(gets) || (str_last == "x" && reg1.test(gets.replace("x","")))){
		                   		if(length == 18 || length == 15){
		                   			return true
		                   		}
		                   }
							return false;
		               },
		               "sel":function(gets,obj,curform,regxp) {
		                   if($(obj).attr("selectVal")=="0"){
		                   	 return $(obj).attr("nullmsg");
		                   }else{
							return true;
		                   }
		               },
		                "checkstart":function(gets,obj,curform,regxp){
						              	  var endtime = $(obj).parents(".col-box").siblings(".col-box").find("input[name='end_date']").val();
						              	  if(endtime != ""){
						              	  	endtime = changetodate(endtime);
						              	  	gets =  changetodate(gets);
						              	  	if(gets > endtime){return false;}
						              	  	else{
						              	  		$(obj).parents(".col-box").siblings(".col-box").find("input[name='end_date']").removeClass("Validform_error").siblings(".check-error-auto").removeAttr("style");
						              	  	}
						              	  }
						              	  function changetodate(time){
						              	  	var time_date = time.split("-");
						              	  	var date=new Date();
						              	  	date.setFullYear(time_date[0]);
						              	  	date.setMonth(time_date[1]-1);
						              	  	date.setDate(time_date[2]);
						              	  	return Date.parse(date)/1000;
						              	  }
						              },
			              "checkend":function(gets,obj,curform,regxp){
			              	 var starttime = $(obj).parents(".col-box").siblings(".col-box").find("input[name='begin_date']").val();
			              	 var today = gettoday();
			              	 if(starttime == ""){
			              	 	starttime = today;
			              	 	$(obj).parents(".col-box").siblings(".col-box").find("input[name='begin_date']").val(today);
			              	 }
			              	 starttime = changetodate(starttime);
			              	 gets = changetodate(gets);
			              	 if(starttime > gets) {return false;}
			              	 else{
			              	 	$(obj).parents(".col-box").siblings(".col-box").find("input[name='begin_date']").removeClass("Validform_error").siblings(".check-error-auto").removeAttr("style");
			              	 }

			              	 function changetodate(time){
			              	  	var time_date = time.split("-");
			              	  	var date=new Date();
			              	  	date.setFullYear(time_date[0]);
			              	  	date.setMonth(time_date[1]-1);
			              	  	date.setDate(time_date[2]);
			              	  	return Date.parse(date)/1000;
			              	  }
			              	 function gettoday(){
			              	 	var date = new Date();
			              	 	var year = date.getFullYear();
			              	 	var month = date.getMonth()+1;
			              	 	var day = date.getDate();
			              	 	return year+"-"+month+"-"+day;
			              	 }
			              	}
		            },
		            callback : function(form){
						var sDate=$(form).find("input[name='begin_date']").val(),
							eDate=$(form).find("input[name='end_date']").val(),
							stimeStap=that.DateToUnix(sDate),
							etimeStap=that.DateToUnix(eDate);
						if(stimeStap>etimeStap){
							var da=dialog({
								title:"提示",
								content:"开始日期不能大于结束日期"
							});
							da.showModal();
							setTimeout(function(){
								da.close().remove();
							},1200);
						}else{
							that.bookedRoomDatasubmit(form,dt);
						}
			            	return false;
		            }
				});
		},
        /**
         * 日期 转换为 Unix时间戳
         * @param <string> 2014-01-01 20:20:20  日期格式
         * @return <int>        unix时间戳(秒)
         */
		DateToUnix:function(date){
			 var f = date.split(' ', 2);
            var d = (f[0] ? f[0] : '').split('-', 3);
            var t = (f[1] ? f[1] : '').split(':', 3);
            return (new Date(
                    parseInt(d[0], 10) || null,
                    (parseInt(d[1], 10) || 1) - 1,
                    parseInt(d[2], 10) || null,
                    parseInt(t[0], 10) || null,
                    parseInt(t[1], 10) || null,
                    parseInt(t[2], 10) || null
                    )).getTime() / 1000;
		},
		/*
		 *@func提交预定房间/套房信息
		 * */
		bookedRoomDatasubmit:function(form,dt){
			var data="",_txt=$(form).find("input[type='text']"),that=district.prototype;
			$.each(_txt,function(j,item){
				var vv=$(item).val();
				if($(item).attr("name")=="ya" || $(item).attr("name")=="fu"){
					vv=$(item).attr("selectval");
				}
				data+=$(item).attr("name")+"="+vv+"&";
			});
			data+=$("#cover-distri-remark").attr("name")+"="+$("#cover-distri-remark").val()+"&"+dt;
			var cur=$(form).find("#distri-reserve-user-info-save"),
				durl=cur.attr("ydurl"),
				obj=$("#distri-reserve-box");
			if(!cur.hasClass('clicked')){
				cur.addClass("clicked").text("保存中...");
				Ajax.doAjax("POST",durl,data,function(json){
					cur.removeClass("clicked").text("保存");
					if(json.status==1){
						var dt={
							ydUser:obj.find("input[name='name']").val(),
							ydPhone:obj.find("input[name='phone']").val(),
							ydPayment:obj.find("input[name='money']").val(),
							ydBegindate:obj.find("input[name='begin_date']").val(),
							ydEnddate:obj.find("input[name='end_date']").val()
						};
						var ydTemp=template("suc-render-yd",dt);
//						console.log(ydTemp);
						$(".editingStatusActive").find(".tc_Detail").html("").html(ydTemp);
						var ydStaticTemp=$("#ydStaticTemp").html();//预定成功渲染提示信息模板|公用
						//对应不同状态下的预定操作，替换对应模板
						var _markOnlyEle=$(".editingStatusActive");
						$(".editingStatusActive").attr("data-rentstyle","booked");//预定成功，更改页面绑定状态，避免删除时出错
						var _replcNotRentedEle=_markOnlyEle.find(".notRented"),//未出租
							_replcStopUseEle=_markOnlyEle.find(".roomStop"),//停用
							_replcYYTZEle=_markOnlyEle.find(".remark-tuizu"),//预约退租
							_replcRentedEle=_markOnlyEle.find(".roomActive");//已出租
							_replcReservedEle=_markOnlyEle.find(".reservedRooms");//已被预定
							if(_replcNotRentedEle && _replcNotRentedEle.length>0) {
								_replcNotRentedEle.replaceWith(ydStaticTemp);
								var _name=_markOnlyEle.find(".a_StopUse").attr("name"),
									_id=_markOnlyEle.find(".a_StopUse").attr("data-id");
								var tuidingTmp='<a class="a_cancle_reserved" name="'+_name+'" data-id="'+_id+'" href="javascript:;">退订</a>';
								_markOnlyEle.find(".a_StopUse").replaceWith(tuidingTmp);
								//预定房间成功状态值修改
								var _rtype=2,_status={
									status:1,
									is_yd:1,
									msg_yd:{
										name:obj.find("input[name='name']").val(),
										phone:obj.find("input[name='phone']").val(),
										money:obj.find("input[name='money']").val(),
										stime_c:obj.find("input[name='begin_date']").val(),
										etime_c:obj.find("input[name='end_date']").val()
									}
								};
								if(_name=="house_id"){
									_rtype=1;
								}
								that.trigFilterData(_rtype,_status,_id);
							}else if(_replcStopUseEle && _replcStopUseEle.length>0) {
								_replcStopUseEle.replaceWith(ydStaticTemp);//停用状态下点击预定
								that.genYuDingToolBar(_markOnlyEle,json,obj);//提取组装模板公共部分
							}else if(_replcRentedEle && _replcRentedEle.length>0){
								_replcRentedEle.replaceWith(ydStaticTemp);//已租状态下点击预定
								that.genYuDingToolBar(_markOnlyEle,json,obj);//提取组装模板公共部分
							}else if(_replcYYTZEle && _replcYYTZEle.length>0){
								_replcYYTZEle.replaceWith(ydStaticTemp);//预约退租状态下点击预定
								that.genYuDingToolBar(_markOnlyEle,json,obj);//提取组装模板公共部分
							} else if(_replcReservedEle && _replcReservedEle.length>0){
								_replcReservedEle.replaceWith(ydStaticTemp);//已预定状态下点击预定
								that.genYuDingToolBar(_markOnlyEle,json,obj);//提取组装模板公共部分
							}
						//重新绑定操作栏点击事件
						that.bindDialogEvent();
						//重置元素
						$(".editingStatusActive").removeClass("editingStatusActive");
					}else{
						$(".editingStatusActive").removeClass("editingStatusActive");
					}
					var da=dialog({
						title: '提示',
						content:json.message,
						okValue: '确定',
						ok: function () {
							da.close().remove();
							if(json.status==1){
								window.location.href="#"+json.reserve_url;
								obj.find(".cancle-over-trigger").trigger("click");
							}
						}
					});
					da.showModal();
					setTimeout(function(){
						da.close().remove();
						if(json.status==1){
							window.location.href="#"+json.reserve_url;
							obj.find(".cancle-over-trigger").trigger("click");
						}
					},1200);
				});
			}
		},
		/*
		 *@func 初始化下拉/日历事件绑定
		 * */
		bindPluginEvt:function(){
			var that=this,obj=$("#distri-reserve-box").find(".distri-rev-over-temp-selectByM"),
				_eleDate=$("#distri-reserve-box").find(".date-input");
            $.each(obj,function(i,o){
                $(o).selectObjM();
            });
            $(_eleDate).off().on("click",function(){
            		calendar.inite();
            });
            if(sys.ie && sys.ie < 10){
				$(".ui-dialog").placeholder();
			}
		},
		/*
		 *@func 自定义按钮关闭遮罩事件绑定
		 * */
		closeOverlay:function(d){
            $(".cancle-over-trigger").off().on("click",function(){
            		d.close().remove();
            		if($(".editingStatusActive"))$(".editingStatusActive").removeClass("editingStatusActive");
            });
		},
		/*
		 *@func 处理相同操作栏公用方法提取
		 * */
		genYuDingToolBar:function(_markOnlyEle,json,obj){
			var _name=_markOnlyEle.find(".a_Booked").attr("name"),
				that=this,
				detailUrl=_markOnlyEle.find(".a_Detail").attr("data-href"),
				_id=_markOnlyEle.find(".a_Booked").attr("data-id");
			var dt={
				detailUrl:detailUrl,
				rtype:_name,
				rrid:_id,
				rentalUrl:json.rental_url
			};
			var _genToolbar=template("tool-bar-yd",dt);
			_markOnlyEle.find(".detail_Choices ul").html(_genToolbar);
			//预定房间成功状态值修改
			var _rtype=2,_status={
				status:1,is_yd:1,
				msg_yd:{
					name:obj.find("input[name='name']").val(),
					phone:obj.find("input[name='phone']").val(),
					money:obj.find("input[name='money']").val(),
					stime_c:obj.find("input[name='begin_date']").val(),
					etime_c:obj.find("input[name='end_date']").val()
				}
			};
			if(_name=="house_id"){
				_rtype=1;
			}
			that.trigFilterData(_rtype,_status,_id);
		},
		/*
		 *@func 设定层级
		 * */
		setEventzIndex:function(){
			var num_centralized_Ind_DLi =$("#rightColBox").find(".centralized_Ind_D  .b  ul li").length;
			$("#rightColBox").find(".centralized_Ind_D .col-rtype").children("ul").children("li").each(function(){
				var num_Li = $(this).index();
				$(this).css("z-index",num_centralized_Ind_DLi-num_Li);
				var num_Dd = $(this).find("dd").length;
				$(this).children("dl").children("dd").each(function(){
					var nums = $(this).index();
					$(this).css("z-index",num_Dd-nums);
					//房间列表各种详细情况弹出层
					 var hoverTimer, outTimer;
					$(this).hover(function(){
						var  cur=$(this);
						 clearTimeout(outTimer);
               			 hoverTimer = setTimeout(function(){
							cur.find(".tc_Detail").show();
               			 },100);
					},function(){
						var  cur=$(this);
						 clearTimeout(hoverTimer);
						 outTimer=setTimeout(function(){
							cur.find(".tc_Detail").hide();
						 },100);
					});
					//ie6鼠标掠过变色
					if(sys.ie && sys.ie < 7){
						$(this).hover(function(){
							$(this).addClass("ie6Hover");
						},function(){
							$(this).removeClass("ie6Hover");
						});
					}
				})
			});
		},
		throttle:function(method,delay){
			var timer=null;
            return function(){
                var context=this, args=arguments;
                clearTimeout(timer);
                timer=setTimeout(function(){
                    method.apply(context,args);
                },delay);
            }
		},
		/*
		 *@func 绑定下拉加载更多数据事件
		 * */
		bindScroll:function(){
			var that=this;
			var obj = $("#rightColBox").find(".centralized_Ind_D").find(".b");
			obj.scroll(that.throttleV2(that.loadMoreData, 100, 500));
  		},
		/*
		 *@func 函数节流，避免执行次数过快，性能优化
		 * */
  		throttleV2:function(fn, delay, mustRunDelay){
		    var timer = null;
		    var t_start;
		    return function(){
		        var context = this, args = arguments, t_curr = +new Date();
		        clearTimeout(timer);
		        if(!t_start){
		            t_start = t_curr;
		        }
		        if(t_curr - t_start >= mustRunDelay){
		            fn.apply(context, args);
		            t_start = t_curr;
		        }
		        else {
		            timer = setTimeout(function(){
		                fn.apply(context, args);
		            }, delay);
		        }
		    };
		 },
  		/*
  		 *@func 滚动加载更多数据
  		 * */
  		loadMoreData:function(){
  			var that=district.prototype,obj=$("#rightColBox"),
  				area_id=$("#getMapData").attr("area-id"),
  				community_id=$("#getMapData").attr("community-id"),
  				page=that.currentPage;
  				var scrollTop=$(this)[0].scrollTop,
  					part_top_h=obj.find(".tab-gather")[0].getBoundingClientRect().height,
  					part_bot_h=obj.find(".tab-whole")[0].getBoundingClientRect().height,
  					scrollHeight=part_top_h+part_bot_h,
  					clientHeight=$(this)[0].getBoundingClientRect().height;
//				console.log(clientHeight+scrollTop);
//				console.log(scrollHeight);
				//临界点
  				if((clientHeight+scrollTop)==(scrollHeight+10) || ((scrollHeight+10)-(clientHeight+scrollTop))<=10 ){
//					that.isLoadingData=true;
					that.getListHZData("post",area_id,community_id,page);
  				}
  		}

	}
	/*
	 *页面方法初始化
	 * */
	exports.inite = function(){
		/*判断右侧对象是否已经初始化*/
			if(initedRoomStatus==0){
				initedRoomStatus=1;
				zoneObj=new district();
			}else{
				var area_id=$("#getMapData").attr("area-id"),
					type="GET",
					community_id=$("#getMapData").attr("community-id");
				if(area_id!="" && area_id!=undefined){
					type="POST";
				}
				zoneObj.initEvent(type,area_id,community_id);
			}
//		exports.getCurrentCityData();//获取当前城市区域数据
	}
});
