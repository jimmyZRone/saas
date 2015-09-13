define(function(require,exports,module){
	var $=require("jquery"),
		Ajax=require("Ajax"),
		template=require("artTemp"),
		navigators = require("navigatortest"),
		dialog = require("dialog"),
		ajaxRequest;
		require("selectByM")($);
		require("validForm")($);
	var calendar = require("calendar");
	/*
	 * 全局Map对象
	 */
	var JZMap,markers=[],
		mapWidth,mapHeight,navbarheight=105,
		leftBarWidth=155,timer=[],
		markSearchStatus=0,//默认未搜索
	    MAPJSONCACHE=[],//地图数据缓存
	    CACHEALLDISTRICTJSON=[],//小区全部数据缓存(包含加载更多数据)
	    DISTRICTJSONCCHE=[],//小区数据缓存
	    RESIZESTATUS=0,
		rightBoxWidth,isMiniMap=true,initedRoomStatus=0,zoneObj;
	
	/*
	 *@func:地图原型模块
	 */
	var jMap=function(){
		this.init();
	}
	jMap.prototype={
		init:function(){
			var that=this;
			that.initMap();//初始化地图
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
		 *@func:返回地图中心点
		 * */
		getPoint:function(){
			var point=new BMap.Point(104.072522,30.666028);//成都
			return point;
		},
		/*
		*@func 窗口拉伸页面渲染
		*/
		resizeRenderMappage:function(){

			var that=jMap.prototype;
			mapWidth=($(window).width()-leftBarWidth)*0.4;//分屏比例
			mapHeight=that.getWindowHeight()-navbarheight;
			mapWidth=mapWidth-leftBarWidth;
			if(mapWidth<=400){
				mapWidth=400;
			}
			rightBoxWidth=$(window).width()-leftBarWidth-mapWidth-5;
			$("#allMap").css({
				width:mapWidth+"px",
				height:mapHeight+"px"
			});
			$("#rightColBox").css({
				width:rightBoxWidth+"px",
				height:mapHeight+"px"
			});
			
			var H=mapHeight-66-130-40;
			$(".centralized_Ind_D").find(".b").height(H);
		},
		/*
		 *@func:迷你地图展示事件
		 * */
		changeMiniscreenWandH:function(){
			var that=jMap.prototype;
			that.resizeRenderMappage();
            window.onresize=that.resizeThrottleV2(that.resizeRenderMappage, 200, 500);
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
		},
		/*
		 *@func:请求地图渲染数据
		 *@param 
		 *	atype 1:区域 2:小区 
		 *	type 请求类型
		 *{string} area_id:区域id 
		 *{string} param:搜索数据
		 *{string} community_id:小区名称id
		 * */
		getZoneAjaxData:function(atype,type,area_id,community_id){
		 	if(!!!area_id) area_id="";
		 	if(!!!community_id) community_id="";
		 	var durl=$("#getMapData").attr("map-url"),that=this,data="";
		 	if(!!!type) {
		 		type="get";
		 	}else{
		 		data="area_id="+area_id+"&community_id="+community_id;
				data+="&house_type="+$("#rent-type").attr("selectval")+"&room_type="+$("#room-type").attr("selectval")+"&community_name="+$("#rightColBox").find("#centralized_Ind_ApartmentName").val();
				data+="&custom_number="+$("#rightColBox").find("#centralized_Ind_SearchTxt").val();
		 	}
			var setting={
				type:type,
				url:durl,
				data:data
			}
			ajaxRequest=Ajax.doAjax(setting.type,setting.url,setting.data,function(json){
		 		if($("#getMapData").hasClass("clicked"))$("#getMapData").removeClass("clicked");
		 		MAPJSONCACHE=json.data;//缓存地图数据
				if(json.status==1){
					if(!!!community_id) that.iniMapDataRender(json.data,atype);
				}else{
					//测试
					// that.iniMapDataRender(json,atype);
				}
			});
		},
		/*
		 *@func:地图结果数据渲染
		 * */
		iniMapDataRender:function(json,type){
			var  that=this;
			that.removeOverlay();//清除地图上已有标记点
			if(type==1){
				//处理对应区域数据
				var markerArr = json,points=[];
				for (var i = 0; i < markerArr.length; i++) {
					var json=markerArr[i];
					var txt = markerArr[i].name;
					var pintx = markerArr[i].longitude;
					var pinty = markerArr[i].latitude;
					var count= markerArr[i].count_house;
					var area_id= markerArr[i].area_id;
					that.addOverlayToMap(new BMap.Point(pintx, pinty),txt,count,type,area_id,"");//添加覆盖物
					points.push(new BMap.Point(pintx, pinty));
				 }
				JZMap.setViewport(points);
				$(".spread-page").css({
					opacity:"1"
				});
			}else{
				//处理对应区域小区数据
				var markerArr = json,points=[];
				for (var i = 0; i < markerArr.length; i++) {
					var json=markerArr[i];
					var txt = markerArr[i].community_name;
					var pintx = markerArr[i].longitude;
					var pinty = markerArr[i].latitude;
					var count= markerArr[i].count_house;
					var area_id= markerArr[i].area_id;
					var community_id= markerArr[i].community_id;
					that.addOverlayToMap(new BMap.Point(pintx, pinty),txt,count,type,area_id,community_id);
					points.push(new BMap.Point(pintx, pinty));
				 }
				 var hasTarget=$("#getMapData").data("remark_target"),
				 	 _par=$("#allMap").find(".villageBox");
				 if(hasTarget && hasTarget!="" && hasTarget!=undefined){
				 	$.each(_par,function(j,item){
				 		if(hasTarget == $(item).attr("community_id")){
				 			$("#getMapData").data("remark_target","");
				 			$(item).parent().find(".current").removeClass("current");
				 			$(item).addClass("current");
				 		}
				 	});
				 }
				 // console.log(points);
				JZMap.setViewport(points);
				$(".spread-page").css({
					opacity:"1"
				});

			}
		},
		/*
	 *删除区域标记
	 * */
	removeOverlay:function(){
		JZMap.clearOverlays();
	},
	/*
	 *@func:mini地图展示
	 *@当前已选中的小区
	 * */
	setMiniMap:function(point){
		var  that=this;
		that.removeOverlay();
		that.setMap(point,15);
		that.getToolbarStatus();//显示对应地图类型图标
		var  atype=$("#getMapData").attr("area-id"),type=1;
		if(atype!="" && atype!=undefined) type=2;
		setTimeout(function(){
			that.iniMapDataRender(MAPJSONCACHE,type);
		},1200);
	},
	/*
	 *@func:全屏地图展示
	 *@desc:清空列表数据，如果发生改变，需要重新请求数据展示
	 @param:type - 当前地图展示数据格式，保证全屏后的显示不变
	 * */
	setFullScreenMap:function(type){
		var  that=this;
		that.removeOverlay();
		var  point=that.getPoint();
		that.setMap(point,13);
		isMiniMap=false;//重置状态
		that.getToolbarStatus();//显示对应地图类型图标
		var  atype=$("#getMapData").attr("area-id"),type=1;
		if(atype!="" && atype!=undefined) type=2;
		setTimeout(function(){
			that.iniMapDataRender(MAPJSONCACHE,type);
		},1200);

	},
	
	/*
	 *@func:当前屏幕内容高度获取	 
	 * */
	getWindowHeight:function(){
	 	return window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
	},
	
	/*
	 *@func 地图高宽度赋值<全屏>	 
	 * */
	changefullscreenWandH:function(){
			var that=jMap.prototype;
			mapWidth=$(window).width()-leftBarWidth;
			mapHeight=that.getWindowHeight()-navbarheight;
			$("#allMap").css({
				width:mapWidth+"px",
				height:mapHeight+"px"
			});
            window.onresize=that.resizeThrottleV2(that.changefullscreenWandH, 200, 500);
		},
	setMap:function(point,zoom){
			JZMap.centerAndZoom(point, zoom);
			JZMap.enableScrollWheelZoom();
		},
	addToolBar:function(){
			var  that=this;
			function fullScreenControl(){
				this.defaultAnchor = BMAP_ANCHOR_TOP_RIGHT;
			    this.defaultOffset = new BMap.Size(30, 30);
			}
			fullScreenControl.prototype=new BMap.Control();
			fullScreenControl.prototype.initialize = function(map) {
			    var _div = document.createElement("a"),
			    	src="../web/images/fullscreen.png";
			    	temp='<img src="'+src+'" />';
			    _div.id="bigMap";
			    _div.innerHTML=temp;
			    _div.href="javascript:;";
			    _div.onclick = function(e){
					 isMiniMap=true;//重置状态
			    	 $("#rightColBox").show();//隐藏后侧数据筛选项
			    	 // $(window).unbind('resize');
			    	 that.changeMiniscreenWandH();
			    	 $(this).hide();
			    	 $("#smallMap").show();
			         // $(window).bind("resize", function () {
// 						 // that.changeMiniscreenWandH();
// 						 district.prototype.throttleV2(that.changeMiniscreenWandH, 50, 100);
// 		             });
			    	that.setMiniMap();
			    }
			    JZMap.getContainer().appendChild(_div);
			    return _div;
			  }
			function miniScreenCtrl(){
				this.defaultAnchor = BMAP_ANCHOR_TOP_RIGHT;
			    this.defaultOffset = new BMap.Size(30, 30);
			}
			miniScreenCtrl.prototype=new BMap.Control();
			miniScreenCtrl.prototype.initialize = function(map) {
			    var _div = document.createElement("a"),
			    	src="../web/images/fullscreen.png";
			    	temp='<img src="'+src+'" />';
			    _div.id="smallMap";
			    _div.innerHTML=temp;
			    _div.href="javascript:;";
			    _div.onclick = function(e){
					 isMiniMap=false;//重置状态
			    	 $("#rightColBox").hide();//隐藏后侧数据筛选项
			    	 $(window).unbind('resize');
			    	 $(this).hide();
			    	 $("#bigMap").show();
			    	 that.changefullscreenWandH();
		             // $(window).bind("resize", function () {
 // 		                 // that.changefullscreenWandH();
 // 						 // district.prototype.throttleV2(that.changefullscreenWandH, 50, 100);
 // 		             });
			    	that.setFullScreenMap();
			    }
			    JZMap.getContainer().appendChild(_div);
			    return _div;
			  }
			 var fullScreenCtrl = new fullScreenControl();
			 var miniScreenCtrl = new miniScreenCtrl();
	
		  	 JZMap.addControl(fullScreenCtrl);
		  	 JZMap.addControl(miniScreenCtrl);
		},
		/*
		*@func 显示对应全屏/退出全屏图标
		*
		*/
	getToolbarStatus:function(){
				if(isMiniMap==false){
				$("#smallMap").hide();
				$("#bigMap").show();
			}else{
				$("#smallMap").show();
				$("#bigMap").hide();
			}
		},
		/*
		*@func 添加自定义覆盖遮罩物
		*
		*/
	addOverlayToMap:function(point,text,count,type,areaId,communityId){
			var ep=this;
			function ComplexCustomOverlay(point,text,count,type,areaId,communityId){
				this._point = point;
				this._text = text;
				this._count=count;
				this._type=type;
				this._areaId=areaId;
				this._communityId=communityId;
			}
			ComplexCustomOverlay.prototype = new BMap.Overlay();
			ComplexCustomOverlay.prototype.initialize = function(map,type){
				this._map = JZMap;
				var div = this._div = document.createElement("div");
				div.style.position = "absolute";
				div.style.cursor="pointer";
			    div.style.zIndex = BMap.Overlay.getZIndex(this._point.lat);
				div.style.MozUserSelect = "none";
				
				//区域类型
				if(this._type==1){
					
					div.className="zonebox";
					div.lng=this._point.lng;
					div.lat=this._point.lat;
					div.setAttribute("lng",this._point.lng);
					div.setAttribute("lat",this._point.lat);
					div.setAttribute("area_id",this._areaId);
			
					var h3 = this._h3 = document.createElement("h3");
					var p=this._p=document.createElement("p");
					var span=this._span=document.createElement("span");
					var unit=this._unit=document.createElement("span");
					span.style.margin="0 3px";
					div.appendChild(h3);
					div.appendChild(p);
					p.appendChild(span);
					p.appendChild(unit);
					h3.appendChild(document.createTextNode(this._text)); 
					span.appendChild(document.createTextNode(this._count)); 
					unit.appendChild(document.createTextNode("间")); 
	
					/*IE事件绑定*/
					if(sys.ie){
						div.onclick=function(){
							$(".villageBox").removeClass("current");
							$(this).addClass("current");
							var lng=$(this).attr("lng"),lat=$(this).attr("lat"),
								area_id=$(this).attr("area_id");
							ep.removeOverlay();
							district.prototype.renderAreaBoxData("POST",area_id);//渲染对应层级数据
						};
					}else{
						div.addEventListener("click",function(){
							$(".villageBox").removeClass("current");
							$(this).addClass("current");
							var lng=$(this).attr("lng"),lat=$(this).attr("lat"),
								area_id=$(this).attr("area_id");
							ep.removeOverlay();
							district.prototype.renderAreaBoxData("POST",area_id);//渲染对应层级数据
						});
					}
				}else{
					//小区类型
					div.className="villageBox";
					div.lng=this._point.lng;
					div.lat=this._point.lat;
					div.area_id=this._areaId;
					div.community_id=this._communityId;
					div.setAttribute("lng",this._point.lng);
					div.setAttribute("lat",this._point.lat);
					div.setAttribute("area_id",this._areaId);
					div.setAttribute("community_id",this._communityId);

			
					var span = this._span = document.createElement("span");
					var i=this._i=document.createElement("i");
					var unit=this._unit=document.createElement("span");
					div.appendChild(span);
					div.appendChild(i);
					div.appendChild(unit);
					span.appendChild(document.createTextNode(this._text)); 
					i.appendChild(document.createTextNode(this._count)); 
					unit.appendChild(document.createTextNode("间")); 
				
					var that = this;
					var arrow = this._arrow = document.createElement("div");
					div.appendChild(arrow);
				
					//鼠标移入
					div.onmouseover = function(){
						this.style.background = "#ffd7dd";
						arrow.style.background='url(../web/images/map_arrow.png) no-repeat 0 0';
					}
					//鼠标移出
					div.onmouseout = function(){
						this.style.background = "#fff";
						arrow.style.background='url(../web/images/map_arrow.png) no-repeat 0 -19px';
					}
					/*IE事件绑定*/
					if(sys.ie){
						div.onclick=function(){
							var cur=$(this);
							 if(!cur.hasClass("current")){
							 	 cur.parent().find(".current").removeClass("current");
								 cur.addClass("current");
								var lng=$(this).attr("lng");
								var lat=$(this).attr("lat");
								var community_id=$(this).attr("community_id");
								var area_id=$(this).attr("area_id");
								var spoint=new BMap.Point(lng,lat);
								$("#getMapData").attr("community-id",community_id);
		//						JZMap.centerAndZoom(spoint, 15);
							   if(isMiniMap==false){
									$("#getMapData").data("remark_target",community_id);//标记点击的地图数据
							   		isMiniMap=true;
							   		$("#rightColBox").show();
							   		// ep.changeMiniscreenWandH();
									ep.setMiniMap(spoint,15);
							   }else{
							   		//请求对应小区数据渲染页面
									district.prototype.renderAreaBoxData("POST",area_id,community_id);//渲染对应层级数据
							   }
							 }
						};
					}else{
						div.addEventListener("click",function(){
							var cur=$(this);
							if(!cur.hasClass("current")){
								cur.parent().find(".current").removeClass("current");
								cur.addClass("current");
								var loc=new BMap.Point(this.getAttribute("lng"),this.getAttribute("lat"));
		//						JZMap.centerAndZoom(loc, 15);
								var community_id=this.getAttribute("community_id");
								var area_id=this.getAttribute("area_id");
								$("#getMapData").attr("community-id",community_id);
							   if(isMiniMap==false){
									$("#getMapData").data("remark_target",community_id);//标记点击的地图数据
							   		isMiniMap=true;
							   		$("#rightColBox").show();
							   		ep.changeMiniscreenWandH();
									ep.setMiniMap(loc,15);
							   }else{
							   		//请求对应小区数据渲染页面
									district.prototype.renderAreaBoxData("POST",area_id,community_id);//渲染小区数据
							   }
							}
						});
					}
				}
				JZMap.getPanes().markerPane.appendChild(div);
				return div;
		}
		//添加标注
		ComplexCustomOverlay.prototype.draw = function(){
			var JZMap = this._map;
			var pixel = JZMap.pointToOverlayPixel(point);
			this._div.style.left = (pixel.x - this._div.offsetWidth / 2) + "px";
			this._div.style.top = (pixel.y - 40) + "px";
		}
		
		ComplexCustomOverlay.prototype.addEventListener = function(event,fun){//点击事件
			this._div['on'+event] = fun;
		}
		/*添加覆盖物到地图上*/
		var marker=new ComplexCustomOverlay(new BMap.Point(point.lng, point.lat),text,count,type,areaId,communityId);
		JZMap.addOverlay(marker);
		return marker;
	},
	
	initMap:function(){
		var that=this;
		that.changeMiniscreenWandH();
		JZMap=new BMap.Map("allMap");
		window.JZMap=JZMap;
		var point=that.getPoint();
		that.setMap(point,13);
		that.addToolBar();//添加自定义控件
		that.getToolbarStatus();//显示对应地图类型图标
		}
	};
		
/*
 *======================小区信息模块=============================*
 * */
	var districtJson=[];//存储后台返回数据做条件筛选项[前提:不改变小区,否则重新请求再赋值]
	// var curDistricName=$("#centralized_Ind_ApartmentName").val();//当前小区名称
	
	
	/*
	 * @obj 小区对象
	 * @param 选中小区信息
	 * ex:{id:"小区id",name:"成都-高新区-皇后国际"}
	 * 
	 */
	var district=function(type,area_id,community_id){}
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
		},
		isLoadingData:true,//是否正在加载数据
		currentPage:1,//当前页面索引
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
		 	if(!!!area_id) area_id="";
		 	if(!!!community_id) community_id="";
		 	//缓存id数组
		 	$("#getMapData").attr("area-id",area_id);
		 	$("#getMapData").attr("community-id",community_id);
		 	var durl=$("#getMapData").attr("area-url"),that=this,data="";
		 	if(!!!type) {
		 		type="get";
		 	}else{
		 		data="area_id="+area_id+"&community_id="+community_id;
				data+="&house_type="+$("#rent-type").attr("selectval")+"&room_type="+$("#room-type").attr("selectval")+"&community_name="+$("#rightColBox").find("#centralized_Ind_ApartmentName").val();
				data+="&custom_number="+$("#rightColBox").find("#centralized_Ind_SearchTxt").val();
		 	}
			var setting={
				type:type,
				url:durl,
				data:data
			}
			ajaxRequest=Ajax.doAjax(type,setting.url,setting.data,function(json){
		 		if($("#getMapData").hasClass("clicked"))$("#getMapData").removeClass("clicked");
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
						roomAvaiable:parseInt(json.all_house)-parseInt(json.count_rental),
						roomReserved:json.count_reserve,
						cancleDeal:json.is_yytz,
						roomUnavaiable:json.stop
					};
					var temp=template('area-statice-temp', data);
					$("#zoneInfoCol").html(temp);
					if(area_id!="" && area_id!=undefined){
						if(community_id==""|| community_id==undefined){
						 	jMap.prototype.getZoneAjaxData(2,"POST",area_id,"");
						}
					}else{
						if(markSearchStatus==0){
							jMap.prototype.getZoneAjaxData(1);
						}else{
							jMap.prototype.getZoneAjaxData(1,"POST");
						}
					}
					that.getListHZData(type,area_id,community_id,0);
				}
			});
		 },
		/*
		 *@func:请求整租/合租数据
		 *@param  type 请求类型 默认为get
		 * */
		 getListHZData:function(type,area_id,community_id,page){
		 	if(!!!page) page=0;
		 	if(!!!area_id) area_id="";
		 	if(!!!community_id) community_id="";
		 	var durl=$("#getMapData").attr("data-url"),that=this,data="";
		 	page+=1;
		 	if(!!!type) {
		 		type="get";
		 	}else{
		 		data="area_id="+area_id+"&community_id="+community_id;
				data+="&house_type="+$("#rent-type").attr("selectval")+"&room_type="+$("#room-type").attr("selectval")+"&community_name="+$("#rightColBox").find("#centralized_Ind_ApartmentName").val();
				data+="&custom_number="+$("#rightColBox").find("#centralized_Ind_SearchTxt").val()+"&page="+page;
		 	}
			var setting={
				type:type,
				url:durl,
				data:data
			}
			if(district.prototype.isLoadingData==true){
				district.prototype.isLoadingData=false;
			 	Ajax.doAjax(setting.type,setting.url,data,function(json){
			 		if($("#getMapData").hasClass("clicked"))$("#getMapData").removeClass("clicked");
			 		if(json.status==1){
			 			if(json.hz_data!="" && json.hz_data.length>0 || json.zz_data!="" && json.hz_data.length>0){
				 			district.prototype.isLoadingData=true;
				 			district.prototype.currentPage=page;
				 			DISTRICTJSONCCHE=json;
				 			CACHEALLDISTRICTJSON.push(DISTRICTJSONCCHE);
				 			that.renderListTemplate(DISTRICTJSONCCHE);//页面模板渲染,和筛选无关
				 			var len=$("#rightColBox").find(".centralized_Ind_C").find(".active").length;
				 			if(len>0){
				 				that.trigFilterData();
				 			}
				 			if(json.hz_data=="" ||json.hz_data.length==0 || json.zz_data=="" ||json.zz_data.length==0){
				 				that.trigFilterData();
				 			}
			 			}
			 		}else{
			 			district.prototype.isLoadingData=false;
			 			district.prototype.currentPage=page;
			 			$(".type-bar,.col-rtype").addClass("none");
			 		}
			 	});
			}
		 },
		/*
		 *@func 小区列表数据处理
		 * */
		 renderListTemplate:function(json){
		 	var data=json,that=this;
 			if(data.hz_data!="" && data.hz_data.length>0){
            		var html=template('hz-temp-gen-mark',data);
				$('#hz-temp-box').append(html);
 				$(".tab-gather").removeClass("none");
 				 $(".type-hz-row").removeClass("none").css({"padding":"0 12px"});
 			}else{
// 				$('#hz-temp-box').html("");
 				$(".tab-gather").addClass("none");
 				 $(".type-bar").removeClass("none").css({"width":"129px"});
 				 $(".type-hz-row").addClass("none").css({"padding":"0"});
 			}
			
 			if(data.zz_data!="" && data.zz_data.length>0){
			 	$("#rightColBox").find(".tab-whole").css({"padding-top":"45px"});
            		var zhtml=template('zz-temp-gen-mark',data);
				$('#zz-temp-box').append(zhtml);
 				$(".tab-whole").removeClass("none");
 				 $(".type-zz-row").removeClass("none").css({"padding":"0 12px"});
 			}else{
//				$('#zz-temp-box').html("");
 				$(".tab-whole").addClass("none");
 				 $(".type-bar").removeClass("none");
 				 $(".type-bar").removeClass("none").css({"width":"129px"});
 				 $(".type-zz-row").addClass("none").css({"padding":"0"});
 			}
 			if(data.hz_data=="" && data.zz_data==""){
 				 $(".type-bar").addClass("none");
 			}else if(data.hz_data!="" && data.zz_data!=""){
			 	$("#rightColBox").find(".tab-whole").css({"padding-top":"0"});
 				 $(".type-bar").removeClass("none").css({"width":"194px"});
 			}
 			that.setEventzIndex();
			that.bindCheckbox();
			that.iniFoldCol();//展开收起的地区名称
			that.bindDialogEvent();//操作栏状态事件
		 },
		/*
		 *@func 收起展开区域数据
		 * */
		iniFoldCol:function(){
			var par=$("#rightColBox").find(".tab-gather").find(".floor_Num i");
			$.each(par,function(i,o){
				$(o).off().on("click",function(){
					var  cur=$(this),
						_ele=cur.parent().parent();
					if(!cur.hasClass("sprit-fold")){
						cur.addClass("sprit-fold");
						cur.prev().addClass("blue");
						_ele.next().slideDown();
					}else{
						cur.removeClass("sprit-fold");
						cur.prev().removeClass("blue");
						_ele.next().slideUp();
					}
				});
			});
		},
		/*搜索事件绑定*/
		setSearchEvent:function(){
			var that=this;
			$("#getMapData").off().on("click",function(){
				markSearchStatus=1;//激活搜索状态
				district.prototype.currentPage=1;//重置页码
				district.prototype.isLoadingData=true;//加载状态激活
				CACHEALLDISTRICTJSON=[];//清空缓存数据
				DISTRICTJSONCCHE=[];
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
		getTargetRoomArray:function(param){
			var that=this,FILTERROOMJSON=[];
			var len=$("#rightColBox").find(".centralized_Ind_C").find(".active").length;
			//清空数据
			$("#hz-temp-box").html("");
			$("#zz-temp-box").html("");

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
				that.renderListTemplate(FILETERJSON);//渲染全部数据
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
				 		if(param.isHZRented==item.status){
				 			jitem.push(item);
				 		}else if(param.isHZUNRented==item.status){
				 			jitem.push(item);
				 		}else if(param.isHZUNUSED==item.status){
				 			jitem.push(item);
				 		}else if(param.isBooked==item.is_yd){
				 			jitem.push(item);
				 		}else if(param.isYYTZ==item.is_yytz){
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
				 		if(param.isZZRented==item.status){
				 			stem.push(o);
				 		}else if(param.isZZUNRented==item.status){
				 			stem.push(o);
				 		}else if(param.isZZUNUSED==item.status){
				 			stem.push(o);
				 		}else if(param.isBooked==item.is_yd){
				 			stem.push(o);
				 		}else if(param.isYYTZ==item.is_yytz){
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
//				console.log(FILTERROOMJSON);
				that.renderListTemplate(FILTERROOMJSON);
			}
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
						if(cur.hasClass("choice_rented")){
							cur.attr("data-zz-status","2")
							   .attr("data-hz-status","2")
							   .addClass("blue");
						}else if(cur.hasClass("choice_unrented")){
							cur.attr("data-zz-status","1")
							   .attr("data-hz-status","1")
							   .addClass("red");
						}else if(cur.hasClass("choice_booked")){
							cur.attr("data-yd","1").addClass("green");
						}else if(cur.hasClass("choice_outRented")){
							cur.attr("data-tz","1").addClass("yellow");
						}else if(cur.hasClass("choice_stopUse")){
							cur.attr("data-zz-ty","3")
							   .attr("data-hz-ty","3")
							   .addClass("gray");
						}
						
					}else{
						cur.removeClass("active");
						if(cur.hasClass("choice_rented")){
							cur.attr("data-zz-status","")
							   .attr("data-hz-status","")
							   .removeClass("blue");
						}else if(cur.hasClass("choice_unrented")){
							cur.attr("data-zz-status","")
							   .attr("data-hz-status","")
							   .removeClass("red");
						}else if(cur.hasClass("choice_booked")){
							cur.attr("data-yd","").removeClass("green");
						}else if(cur.hasClass("choice_outRented")){
							cur.attr("data-tz","").removeClass("yellow");
						}else if(cur.hasClass("choice_stopUse")){
							cur.attr("data-zz-ty","")
							   .attr("data-hz-ty","")
							   .removeClass("gray");
						}
					}
				that.trigFilterData();
			});
		},
		/*
		 * *@func 筛选过滤房间状态数据
		 */
		trigFilterData:function(){
			var obj=$("#rightColBox"),that=this,params;
			params={
				isHZRented:obj.find(".choice_rented").attr("data-hz-status")	,
				isHZUNRented:obj.find(".choice_unrented").attr("data-hz-status")	,
				isHZUNUSED:obj.find(".choice_stopUse").attr("data-hz-ty"),
				isZZRented:obj.find(".choice_rented").attr("data-zz-status")	,
				isZZUNRented:obj.find(".choice_unrented").attr("data-zz-status")	,
				isZZUNUSED:obj.find(".choice_stopUse").attr("data-zz-ty"),
				isBooked:obj.find(".choice_booked").attr("data-yd"),
				isYYTZ:obj.find(".choice_outRented").attr("data-tz")
			};
			that.getTargetRoomArray(params);
		},
		/*删除、取消删除事件*/
		bindDeleEvent:function(){
			$("#rightColBox").find(".centralized_Delete").off("click").on("click",function(){
				district.prototype.isLoadingData=false;
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
					$(o).find(".checkBoxAll").show();
					var _ele=$(o).find("dd");
					$.each(_ele,function(item,s){
						$(s).find(".checkBox").show();
						$(s).find(".jtBox,.icon_Style,.rent_Style").hide();
						$(s).find(".romm_NUM").css({"padding-left":"27px","width":"127px"});
					});
				});
			//拥有删除状态，点击执行删除操作
			if(_el.hasClass("deleteStyle")){	
				var  param_hz,param_zz,obj=$("#rightColBox"),
					 _delHZ,_delZZ,_param_a="",_param_b="";
				_delHZ=obj.find(".tab-gather").find("ul").find("li");
				_delZZ=obj.find(".tab-whole").find("ul").find("li");
				var len=obj.find(".centralized_Ind_D").find("ul").find("li").find("dd").find("input:checked").length;
				if(len==0){
					var d=dialog({
							title: '提示',
							content:"请先选择删除项",
							cancelValue: '确定',
							cancel: function () {}
						});
						d.showModal();
						setTimeout(function(){
							d.close().remove();
						},1500);
				}else{
				$.each(_delHZ,function(i,o){
					var _tgt=$(o).find("dd");
					$.each(_tgt,function(j,item){
						var _ele=$(item).find(".checkBox").find("input[type='checkbox']");
						$.each(_ele,function(x,y){
							if($(y).attr("checked")=="checked" || $(y).attr("checked")==true){
								_param_a+=","+$(y).val();
							}
						});
					});
				});
				$.each(_delZZ,function(i,o){
					var _tgt=$(o).find("dd");
					$.each(_tgt,function(j,item){
						var _ele=$(item).find(".checkBox").find("input[type='checkbox']");
						$.each(_ele,function(x,y){
							if($(y).attr("checked")=="checked" || $(y).attr("checked")==true){
								_param_b+=","+$(y).val();
							}
						});
					});
				});
				_param_a=_param_a.replace(_param_a.substr(0,1),"");
				_param_b=_param_b.replace(_param_b.substr(0,1),"");
				var durl=_el.attr("url"),
					data={
						room_id:_param_a,
						house_id:_param_b
					};
				if(!_el.hasClass("clicked")){
					_el.parent().find(".clicked").removeClass("clicked");
					_el.addClass("clicked").text("删除中...");;
					Ajax.doAjax("POST",durl,data,function(json){
						_el.removeClass("clicked").text("删除");
						if(json.status==1){
							var _delPar=obj.find(".centralized_Ind_D").find("ul").find("li");
							$.each(_delPar,function(i,o){
								var _delItem=$(o).find("dd");
								$.each(_delItem,function(a,b){
									var _c=$(b).find("input[type='checkbox']");
									$.each(_c,function(d,e){
										if($(e).attr("checked")=="checked" || $(e).attr("checked")==true){
											$(e).parents("dd").remove();
											var _culen=$(e).parents("li").find("dd").length;
											if(_culen==0){
												$(o).remove();
											}
											obj.find(".centralized_DeleteCancel").trigger("click");
										}
									});
								});
							});
						}
						var d=dialog({
							title: '提示',
							content:json.message,
							cancelValue: '确定',
							cancel: function () {}
						});
						d.showModal();
						setTimeout(function(){
							d.close().remove();
						},1500);
					});
				}
			
				}
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
		 * @func 删除状态下的复选框是否能勾选判断
		 */
		bindCheckbox:function(){
			var _par=$("#rightColBox").find(".centralized_Ind_D .col-rtype li"),
				tmp='<div class="undelText">只能删除未出租房屋，该房屋有合约！</div>';
			$.each(_par,function(i,o){
				var _dd=$(o).find("dd");
				_dd.find(".tc_Detail").find(".undelText").remove();
				$.each(_dd,function(j,k){
					if($(k).attr("data-rentstyle") != "notRented" && $(k).attr("data-rentstyle") != "stopUse"){
						$(k).find(".checkBox").addClass("canNotDelete");
						var _ck=$(k).find(".checkBox");
						$.each(_ck,function(j,item){
//							console.log(item);
							var hoverTimer, outTimer;
							$(item).hover(function(){
								var cur=$(this);
						 		clearTimeout(outTimer);
								hoverTimer=setTimeout(function(){
									cur.parent().find(".tc_Detail").show();
									cur.parent().find(".tc_Detail").find("ol").hide();
									cur.parent().find(".tc_Detail").append(tmp);
								},500);
							},function(){
								var  cur=$(this);
						 		clearTimeout(hoverTimer);
								outTimer=setTimeout(function(){
									cur.parent().find(".tc_Detail").find("ol").show();
									cur.parent().find(".tc_Detail").find(".undelText").remove();
								},500);
							});
						});
					}
				});
			});
			$("#rightColBox").find(".centralized_Ind_D .col-rtype ul .checkBox label").click(function(){
				if($(this).parent().hasClass("canNotDelete")){
					return false;
				}
				$(this).toggleClass("checked");
				if($(this).hasClass("checked")){
					$(this).find(".gou").show();
					$(this).next().attr("checked",true);
				}else{
					$(this).find(".gou").hide();
					$(this).next().removeAttr("checked");
				}
			});
			/*全选*/
			var _tar=$("#rightColBox").find(".centralized_Ind_D .col-rtype ul li");
			$.each(_tar,function(i,o){
				$(o).find(".floorNum").find(".checkBoxAll").off().on("click",function(){
					var cur=$(this);
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
				});
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
			     		obj.find(".tab-whole").css({"padding-top":"45px"});
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
		  	  	console.log("预约退租");
		  	  	var cur=$(this);
		  	  	 if(!cur.parents("dd").hasClass("editingStatusActive")){
		  	  	 	$(".editingStatusActive").removeClass(".editingStatusActive");
		  	  	 	cur.parents("dd").addClass("editingStatusActive");
		  	  		that.appointReturnMoney(cur);
		  	  	 }
		  	  });
		  	  evt_tuiding.off().on("click",function(){
		  	  		console.log("退订");
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
		  	  	 console.log("恢复使用");
		  	  	  var  cur=$(this);
		  	  	 if(!cur.parents("dd").hasClass("editingStatusActive")){
		  	  	 	$(".editingStatusActive").removeClass(".editingStatusActive");
		  	  	 	cur.parents("dd").addClass("editingStatusActive");
		  	  		that.roomRestoreUsing(cur);
		  	  	 }
		  	  });
		  	  evt_cxtz.off().on("click",function(){
		  	  	 console.log("撤销退租");
		  	  	  var  cur=$(this);
		  	  	 if(!cur.parents("dd").hasClass("editingStatusActive")){
		  	  	 	$(".editingStatusActive").removeClass(".editingStatusActive");
		  	  	 	cur.parents("dd").addClass("editingStatusActive");
		  	  		that.roomFallBackTuizu(cur);
		  	  	 }
		  	  });
		  });
		},
		/*
		 *@func 撤销预约退租
		 * @param cur 当前选中房间/套房
		 * */
		roomFallBackTuizu:function(cur){
			var url=$("#rightColBox").find(".centralized_Ind_C").find(".choice_outRented").attr("cx-url"),
				data=cur.attr("name")+"="+cur.attr("data-id"),that=this;
			Ajax.doAjax("post",url,data,function(json){
				if(json.status==1){
						var _sj=json.rental_data,dt="";
						if(_sj!="" && _sj!=null){
							dt={
								roomUserName:_sj.name?_sj.name:"",
								roomUserPhone:_sj.money?_sj.money:"",
								nextPaytime:_sj.next_pay_time?_sj.next_pay_time:"",
								nextPaymoney:_sj.total_money?_sj.total_money:""
							};
						}
						finalTemp=template("suc-render-rented",dt);
						finalStaticTemp=$("#rentedStaticTemp").html();
						if(typeof(finalTemp)=="function"){
							finalTemp='<ol><li><span class="name">暂时无法获取租客信息</span></li></ol>';
							finalStaticTemp="";
						}
					var _markOnlyEle=$(".editingStatusActive");
					_markOnlyEle.find(".tc_Detail").html("").html(finalTemp);
					var _replcReservedEle=_markOnlyEle.find(".remark-tuizu");//默认预约退租状态
					if(_replcReservedEle && _replcReservedEle.length>0){
						_replcReservedEle.replaceWith(finalStaticTemp);
						var _name=_markOnlyEle.find(".a_cancle_tz").attr("name"),
							_roomNum=_markOnlyEle.find(".a_cancle_tz").attr("data-room-number"),
							_id=_markOnlyEle.find(".a_cancle_tz").attr("name");
						var yytzTmp='<a class="a_BookOutRented" data-room-number="'+_roomNum+'" name="'+_name+'" data-id="'+_id+'" href="javascript:;">退订</a>';
						_markOnlyEle.find(".a_cancle_tz").replaceWith(yytzTmp);
					} 
					//重新绑定操作栏点击事件
					that.bindDialogEvent();
					$(".editingStatusActive").attr("data-rentStyle","Rented");//撤销预约退租成功
					$(".editingStatusActive").removeClass("editingStatusActive");
				}else{
					$(".editingStatusActive").removeClass("editingStatusActive");
					var da=dialog({
						title:"提示",
						content:json.message
					});
					da.showModal();
					setTimeout(function(){
						da.close().remove();
					},1200);
				}
			});
		},
		/*
		 *@func 退租
		 * @param cur 当前选中房间/套房
		 * */
		roomSecurPayBack:function(cur){
			var stp=$("#tuizu-cover-tmp").html(),that=this;
			var d = dialog({
				title: '<i class="ifont">&#xe663;</i><span>退租提醒</span>',
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
			that.closeOverlay(d);
			that.doTuiZuRequest(_dt);
		},
		/*
		 *@func 退租请求交互处理
		 * @param cur 当前选中房间/套房
		 * */
		doTuiZuRequest:function(dt){
			var that=this;
			$("#send-tuizu-request").off().on("click",function(){
				var  cur=$(this),
					 url=cur.attr("tuizuUrl"),
					 data=dt;
				if(!cur.hasClass("clicked")){
					cur.parent().find(".clicked").removeClass("clicked");
					cur.addClass("clicked").text("保存中...");	
					Ajax.doAjax("post",url,data,function(json){
						cur.removeClass("clicked").text("保存");
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
							var _replcTuizuEle=_markOnlyEle.find(".remark-tuizu");//默认预约退租状态
							if(_replcTuizuEle && _replcTuizuEle.length>0){
								_replcTuizuEle.replaceWith(finalStaticTemp);
								var _name=_markOnlyEle.find(".a_BookOutRented").attr("name"),
									_id=_markOnlyEle.find(".a_BookOutRented").attr("name"),
									_detail=_markOnlyEle.find(".a_Detail").attr("href");
								var dt={
									detailUrl:_detail,
									rentalUrl:json.rental_url,
									rtype:_name,
									rrid:_id
								};
								var _toolBar=template("tool-bar-unrented",dt);
								_markOnlyEle.find(".detail_Choices ul").html(_toolBar);
							} 
						//重新绑定操作栏点击事件
						that.bindDialogEvent();
						$(".editingStatusActive").removeClass("editingStatusActive");
						$(".tuizu-inner-box").find(".cancle-over-trigger").trigger("click");
					}else{
						$(".editingStatusActive").removeClass("editingStatusActive");
					}
					var da=dialog({
						title:"提示",
						content:json.message,
						okValue: '确定',
						ok: function () {
							if(json.status==1){
								window.location.href="#"+json.finance_url;
							}else{
								da.close().remove();
							}
						}
					});
					da.showModal();
					setTimeout(function(){
						if(json.status!=1){
							da.close().remove();
						}
					},1200);
				});
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
					if(_replcReservedEle && _replcReservedEle.length>0){
						_replcReservedEle.replaceWith(finalStaticTemp);
						var _name=_markOnlyEle.find(".a_BookOutRented").attr("name"),
							_id=_markOnlyEle.find(".a_BookOutRented").attr("name"),
							_detail=_markOnlyEle.find(".a_Detail").attr("href");
						var dt={
							detailUrl:_detail,
							rentalUrl:json.rental_url,
							rtype:_name,
							rrid:_id
						};
						var _toolBar=template("tool-bar-unrented",dt);
						_markOnlyEle.find(".detail_Choices ul").html(_toolBar);
					} 
					//重新绑定操作栏点击事件
					that.bindDialogEvent();
					$(".editingStatusActive").attr("data-rentStyle","notRented");//恢复房间成功
					$(".editingStatusActive").removeClass("editingStatusActive");
				}else{
					$(".editingStatusActive").removeClass("editingStatusActive");
				}
				var da=dialog({
					title:"提示",
					content:json.message
				});
				da.showModal();
				setTimeout(function(){
					da.close().remove();
				},1200);
			});
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
			//获取预订人列表数据
			Ajax.doAjax("get",url,"",function(json){
				if(json.status==1){
					var inner=template("list-rendertmp-reserved",json);
					var stp=$("#list-cover-reserved").html();
					var d = dialog({
						title: '<i class="ifont ifont-yytz">&#xe6a3;</i><span>选择退订人</span>',
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
					that.tuiDingRooms(_dt);
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
		tuiDingRooms:function(_dt){
			var cur=$("#send-tuiding-request"),that=this,
				_form=$("#data-temp-booked");
			cur.off().on("click",function(){
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
								//还有预订人
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
							if(_replcRentedEle && _replcRentedEle.length>0){
								_replcRentedEle.replaceWith(tzStaticTemp);//已租状态下点击预约退租
								//修改成停用栏选项
								var _name=_markOnlyEle.find(".a_BookOutRented").attr("name"),
									detailUrl=_markOnlyEle.find(".a_Detail").attr("href"),
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
							}
						//重新绑定操作栏点击事件
						that.bindDialogEvent();
						$(".editingStatusActive").attr("data-rentstyle","outRented");//预约成功，更改页面绑定状态，避免删除时出错
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
							if(_replcNotRentedEle && _replcNotRentedEle.length>0) {
								_replcNotRentedEle.replaceWith(tyStaticTemp);
								//修改成停用栏选项
								var _name=_markOnlyEle.find(".a_Booked").attr("name"),
									detailUrl=_markOnlyEle.find(".a_Detail").attr("href"),
									_id=_markOnlyEle.find(".a_Booked").attr("data-id");
								var dt={
									detailUrl:detailUrl,
									rtype:_name,
									rrid:_id,
									rentalUrl:json.rental_url
								};
								var _genToolbar=template("tool-bar-stop",dt);
								_markOnlyEle.find(".detail_Choices ul").html(_genToolbar);
							}
						//重新绑定操作栏点击事件
						that.bindDialogEvent();
						$(".editingStatusActive").attr("data-rentStyle","stopUse");//停用房间成功
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
				title: '<i class="ifont">&#xf0077;</i><span>预订人信息</span>',
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
		},
		/*
		 *@func 表单焦点移入/移除错误信息展示/隐藏交互
		 * */
		setFormFblurEvt:function(form){
				var _inptEle=$(form).find("input"),
					_txarea=$(form).find("textarea");
				$.each(_inptEle,function(j,item){
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
		               }
		            },
		            callback : function(form){
			            	that.bookedRoomDatasubmit(form,dt);
			            	return false;
		            }
				});
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
							}else if(_replcStopUseEle && _replcStopUseEle.length>0) {
								_replcStopUseEle.replaceWith(ydStaticTemp);//停用状态下点击预定
								that.genYuDingToolBar(_markOnlyEle,json);//提取组装模板公共部分
							}else if(_replcRentedEle && _replcRentedEle.length>0){
								_replcRentedEle.replaceWith(ydStaticTemp);//已租状态下点击预定
								that.genYuDingToolBar(_markOnlyEle,json);//提取组装模板公共部分
							}else if(_replcYYTZEle && _replcYYTZEle.length>0){
								_replcYYTZEle.replaceWith(ydStaticTemp);//预约退租状态下点击预定
								that.genYuDingToolBar(_markOnlyEle,json);//提取组装模板公共部分
							} else if(_replcReservedEle && _replcReservedEle.length>0){
								_replcReservedEle.replaceWith(ydStaticTemp);//已预定状态下点击预定
								that.genYuDingToolBar(_markOnlyEle,json);//提取组装模板公共部分
							} 
						//重新绑定操作栏点击事件
						that.bindDialogEvent();
						$(".editingStatusActive").attr("data-rentstyle","booked");//预定成功，更改页面绑定状态，避免删除时出错
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
		genYuDingToolBar:function(_markOnlyEle,json){
			var _name=_markOnlyEle.find(".a_Booked").attr("name"),
				detailUrl=_markOnlyEle.find(".a_Detail").attr("href"),
				_id=_markOnlyEle.find(".a_Booked").attr("data-id");
			var dt={
				detailUrl:detailUrl,
				rtype:_name,
				rrid:_id,
				rentalUrl:json.rental_url
			};
			var _genToolbar=template("tool-bar-yd",dt);
			_markOnlyEle.find(".detail_Choices ul").html(_genToolbar);
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
							cur.find(".tc_Detail").animate({
								"top":"0px",
								"height":"115px",
								"opacity":"1"
							},600);
               			 },300);
					},function(){
						var  cur=$(this);
						 clearTimeout(hoverTimer);
						 outTimer=setTimeout(function(){
							cur.find(".tc_Detail").animate({
								"top":"115px",
								"height":"0",
								"opacity":"0"
							},600);
						 },300);
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
		var jmp=new jMap();
		jmp.init();
//		exports.getCurrentCityData();//获取当前城市区域数据
	}
});
