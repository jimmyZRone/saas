define(function(require,exports,module){
	var $=require("jquery"),
		template=require("artTemp"),
		navigators = require("navigatortest"),
		Ajax=require("Ajax");
	var JZMap,markers=[],CacheClickZone=[],
	    MAPJSONCACHE=[],//地图数据缓存
	    _objM=window.parent.document.getElementById("getMapData"),
		isMiniMap=true;
		var Sys = {};
	var ua = navigator.userAgent.toLowerCase();
	window.ActiveXObject ? Sys.ie = ua.match(/msie ([\d.]+)/)[1] : 0;
/*
	 *@func:地图原型模块
	 */
	var jMap=window.jMap=function(){
		this.init();
	}
	jMap.prototype={
		init:function(){
			var that=this;
			that.initMap();//初始化地图
		},
		/*
		 *@func:返回地图中心点
		 * */
		getPoint:function(){
			var  that=this;
			var _cityName=$("#allMap").attr("data-city"),
				_prov=$("#allMap").attr("data-province");
			var myGeo = new BMap.Geocoder();
			myGeo.getPoint(_cityName, function(point){
				if (point) {
					that.setMap(point,12);
				}else{
					var point=new BMap.Point(0,0);//默认中心点
					that.setMap(point,12);
				}
			},_prov);
		},
		/*
		 *@func:请求地图渲染数据
		 * */
		getZoneAjaxData:function(atype,area_id,community_id){
		 	if(!!!area_id) area_id="";
		 	if(!!!community_id) community_id="";
		 	var durl=$(_objM).attr("map-url"),that=this,data="";
		 		data="area_id="+area_id+"&community_id="+community_id;
				data+="&house_type="+$("#rent-type").attr("selectval")+"&room_type="+$("#room-type").attr("selectval")+"&community_name="+$("#rightColBox").find("#centralized_Ind_ApartmentName").val();
				data+="&custom_number="+$("#rightColBox").find("#centralized_Ind_SearchTxt").val();
			var setting={
				type:"post",
				url:durl,
				data:data
			}
			ajaxRequest=Ajax.doAjax(setting.type,setting.url,setting.data,function(json){
		 		if($(_objM).hasClass("clicked"))$(_objM).removeClass("clicked");
		 		MAPJSONCACHE=json.data;
				if(json.status==1){
					if(!!!community_id) that.iniMapDataRender(json.data,atype);
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
					var timer=null;
					if(timer!=null){
						clearTimeout(timer);
					}
					timer=setTimeout(function(){
						JZMap.setViewport(points);
					}, 500)
			}else{
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
				 setTimeout(function(){

				  var  _par=$("#allMap").find(".villageBox");
				 if(CacheClickZone.length>0){
				 	$.each(_par,function(j,item){
				 		if(CacheClickZone[0]== ($(item).attr("community_id"))){
				 			$(item).parent().find(".light").removeClass("light");
				 			$(item).addClass("light");
				 			var _el=$(".light");
				 			var zIndex=_el[0].style.zIndex<0?_el[0].style.zIndex*-1:_el[0].style.zIndex*1;
							_el.css({"z-index":zIndex});
				 		}
				 	});
				 }

				 },500)

				JZMap.setViewport(points);
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
		that.getToolbarStatus();
		var  atype=$(_objM).attr("area-id"),type=1;
		if(atype!="" && atype!=undefined) type=2;
		var timer=[];
		clearTimeout(timer);
		timer=setTimeout(function(){
			that.iniMapDataRender(MAPJSONCACHE,type);
		},500);
	},
	/*
	 *@func:全屏地图展示
	 * */
	setFullScreenMap:function(type){
		var  that=this;
		that.getToolbarStatus();//显示对应地图类型图标
		var  atype=$(_objM).attr("area-id"),type=1;
		if(atype!="" && atype!=undefined) type=2;
		var timer=[];
		clearTimeout(timer);
		timer=setTimeout(function(){
			that.iniMapDataRender(MAPJSONCACHE,type);
		},500);
	},
	setMap:function(point,zoom){
		var that=this;
		JZMap.centerAndZoom(point, zoom);
		JZMap.enableScrollWheelZoom();
		JZMap.setMinZoom(9);
		JZMap.addEventListener("zoomend",function(){
			var _zoom=this.getZoom();
			var  atype=$(_objM).attr("area-id"),
				cid=$(_objM).attr("community-id");
			if(_zoom<10 && (atype!=undefined && atype!="" || cid!=undefined && cid!="")){
				that.getAreaCount();
				$(_objM).attr("area-id","").attr("community-id","");
				window.parent.district.prototype.resetListdata("POST","");//渲染对应层级数据
			}
		},false);
	},
	addToolBar:function(){
		var that=this;
			function fullScreenControl(){
				this.defaultAnchor = BMAP_ANCHOR_TOP_RIGHT;
			    this.defaultOffset = new BMap.Size(30, 30);
			}
			fullScreenControl.prototype=new BMap.Control();
			fullScreenControl.prototype.initialize = function(map) {
			    var _div = document.createElement("a"),
			    	src="../web/images/miniscreen.png";
			    	temp='<img src="'+src+'" />';
			    _div.id="bigMap";
			    _div.innerHTML=temp;
			    _div.href="javascript:;";
			    _div.onclick = function(e){
			    	that.removeOverlay();
					 isMiniMap=true;//重置状态
			    	 $("#rightColBox").show();//隐藏后侧数据筛选项
			    	 // $(window).unbind('resize');
				 var  dist=window.parent.district.prototype;
			    	 dist.changeMiniscreenWandH(1);
			    	 $(this).hide();
			    	 $("#smallMap").show();
			         // $(window).bind("resize", function () {
// 						 // that.changeMiniscreenWandH();
// 						 window.parent.district.prototype.throttleV2(that.changeMiniscreenWandH, 50, 100);
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
			    	that.removeOverlay();
					 isMiniMap=false;//重置状态
			    	 $("#rightColBox").hide();//隐藏后侧数据筛选项
			    	 $(window).unbind('resize');
			    	 $(this).hide();
			    	 $("#bigMap").show();
				 var  dist=window.parent.district.prototype;
			    	 dist.changefullscreenWandH();
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
				
				div.onmouseover = function(){
					// this.style.background = "#ffd7dd";
					// arrow.style.background='url(http://img-cdn.jooozo.cn/img/map_arrow_active.png) no-repeat 0 0';
					var cur=$(this);
					if(cur.hasClass("light")){
						var zIndex=cur[0].style.zIndex<0?cur[0].style.zIndex*-1:cur[0].style.zIndex*1;
						cur.css({"z-index":zIndex});
					}else{
						cur.addClass("active");
						var zIndex=BMap.Overlay.getZIndex(cur.attr("lat"))*-1;
						cur.css({"z-index":zIndex});
						if($(".light").length>0){
							var zIndex=$(".light")[0].style.zIndex*-1;
							$(".light").css({"z-index":zIndex});
						}
					}
					
				}
				//鼠标移出
				div.onmouseout = function(){
					// this.style.background = "#fff";
					// arrow.style.background='url(http://img-cdn.jooozo.cn/img/map_arrow.png) no-repeat 0 0';
					var  cur=$(this);
					$(cur).removeClass("active");
					var zIndex=this.style.zIndex*-1;
					$(cur).css({"z-index":zIndex});
					if($(".light").length>0){
						var zIndex=$(".light")[0].style.zIndex<0 ? $(".light")[0].style.zIndex*-1:$(".light")[0].style.zIndex*1;
						$(".light").css({"z-index":zIndex});
					}
				}
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
					if(Sys.ie){
						div.onclick=function(){
							$(".zonebox").removeClass("current");
							$(this).addClass("current");
							var lng=$(this).attr("lng"),lat=$(this).attr("lat"),
								area_id=$(this).attr("area_id");
							ep.removeOverlay();
							window.parent.district.prototype.resetListdata("POST",area_id);//渲染对应层级数据
							ep.getZoneAjaxData(2,area_id);
						};
					}else{
						div.addEventListener("click",function(){
							$(".zonebox").removeClass("current");
							$(this).addClass("current");
							var lng=$(this).attr("lng"),lat=$(this).attr("lat"),
								area_id=$(this).attr("area_id");
							ep.removeOverlay();
							window.parent.district.prototype.resetListdata("POST",area_id);//渲染对应层级数据
							ep.getZoneAjaxData(2,area_id);
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
					if(Sys.ie){
						div.onclick=function(){
							var cur=$(this);
							if(!cur.hasClass("light")){
							 	if($(".light").length>0){
							 		var zIndex=$(".light")[0].style.zIndex<0 ? $(".light")[0].style.zIndex*1:$(".light")[0].style.zIndex*-1;
									cur.parent().find(".light").css({"z-index":zIndex});
							 	}
							 	cur.parent().find(".light").removeClass("light");
							 	cur.parent().find(".active").removeClass("active");
								cur.addClass("light");
								var lng=$(this).attr("lng");
								var lat=$(this).attr("lat");
								var community_id=$(this).attr("community_id");
								var area_id=$(this).attr("area_id");
								var spoint=new BMap.Point(lng,lat);
								$(_objM).attr("community-id",community_id);
								CacheClickZone=[];
								CacheClickZone.push(community_id);
		//						JZMap.centerAndZoom(spoint, 15);
							   if(isMiniMap==false){
									$(_objM).data("remark_target",community_id);//标记点击的地图数据
							   		isMiniMap=true;
							   		$("#rightColBox").show();
							   		// ep.changeMiniscreenWandH();
									ep.setMiniMap(spoint,15);
							   }
						   		//请求对应小区数据渲染页面
						   		JZMap.setCenter(spoint);
								window.parent.district.prototype.resetListdata("POST",area_id,community_id);//渲染对应层级数据

							 }
						};
					}else{
						div.addEventListener("click",function(){
							var cur=$(this);
							if(!cur.hasClass("light")){
							 	if($(".light").length>0){
							 		var zIndex=$(".light")[0].style.zIndex<0 ? $(".light")[0].style.zIndex*1:$(".light")[0].style.zIndex*-1;
									cur.parent().find(".light").css({"z-index":zIndex});
							 	}
							 	cur.parent().find(".light").removeClass("light");
							 	cur.parent().find(".active").removeClass("active");
								cur.addClass("light");
								var loc=new BMap.Point(this.getAttribute("lng"),this.getAttribute("lat"));
		//						JZMap.centerAndZoom(loc, 15);
								var community_id=this.getAttribute("community_id");
								var area_id=this.getAttribute("area_id");
								$(_objM).attr("community-id",community_id);
								CacheClickZone=[];
								CacheClickZone.push(community_id);
							   if(isMiniMap==false){
									$(_objM).data("remark_target",community_id);//标记点击的地图数据
							   		isMiniMap=true;
							   		$("#rightColBox").show();
				 					var  dist=window.parent.district.prototype;
							   		dist.changeMiniscreenWandH(1);
									ep.setMiniMap(loc,15);
							   }
						   		//请求对应小区数据渲染页面
						   		JZMap.setCenter(loc);
								window.parent.district.prototype.resetListdata("POST",area_id,community_id);//渲染小区数据

							}
						});
					}
				}
				JZMap.getPanes().markerPane.appendChild(div);
				//修正误差
				if(div.className=="villageBox"){
					if(Sys.ie<9.0){
						$(".villageBox").css({
							"margin-top":$(".villageBox").height(),
							"margin-left":$(".villageBox").width()
						});
					}else{
						div.style.marginTop=div.getBoundingClientRect().height*(-1)+"px";
						div.style.marginLeft=(div.getBoundingClientRect().width/2)*(-1)+"px";
					}
				}
				return div;
		}
		//添加标注
		ComplexCustomOverlay.prototype.draw = function(){
			var JZMap = this._map;
			var pixel = JZMap.pointToOverlayPixel(point);
			this._div.style.left = (pixel.x) + "px";
			this._div.style.top = (pixel.y) + "px";
		}
		
		ComplexCustomOverlay.prototype.addEventListener = function(event,fun){//点击事件
			this._div['on'+event] = fun;
		}
		/*添加覆盖物到地图上*/
		var marker=new ComplexCustomOverlay(new BMap.Point(point.lng, point.lat),text,count,type,areaId,communityId);
		JZMap.addOverlay(marker);
		return marker;
	},
	/*
	*@func 初次加载区域地图数据
	@desc:直接加载区域数据
	*/
	getAreaCount:function(){
		MAPJSONCACHE=mapJSON;
		this.iniMapDataRender(mapJSON,1);
	},
	initMap:function(){
		var that=this;
		JZMap=new BMap.Map("allMap");
		window.JZMap=JZMap;
		that.getPoint();
		that.addToolBar();//添加自定义控件
		that.getToolbarStatus();//显示对应地图类型图标
		that.getAreaCount();
		}
	};
	(function(){
		new jMap();
	})();
});
