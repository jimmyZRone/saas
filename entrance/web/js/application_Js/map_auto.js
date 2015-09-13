define(function(require,exports,module){
	var $ = require("jquery");
	var dialog = require("dialog"); //弹窗插件
	var map = null;
	/*
		 * @func: 地图检索
		 */
		window.mapsearch = {
			/*
			 *@func:返回地图中心点
			 * */
			getPoint : function(point){
				if(map != null) return map;
//				var point_lat, point_lng;
				if(!!!point) {point = "成都"}
				else{
					point_lat = point.lat, point_lng = point.lng;
				}
				
				 	map = new BMap.Map("mapContainer");
//					map.centerAndZoom(new BMap.Point(point_lat,point_lng), 11);
					map.setCurrentCity(point);
					map.enableScrollWheelZoom(); 
					map.enableContinuousZoom();
					
					map.addEventListener("moveend", function(){      
					       var center = map.getCenter();      
					       if (typeof(callback) == 'function') callback(center);
	//				      console.log("地图中心点变更为：" + center.lng + ", " + center.lat);      
					});
					map.addEventListener("zoomend", function(){      
					       var center = map.getCenter();      
					       if (typeof(callback) == 'function') callback(center);
	//				      console.log("地图中心点变更为：" + center.lng + ", " + center.lat);      
					});
					
					return map;
			},
			chooseaddress : function(callback){
				var that = this;
				var cur_city = $("#centralized_Depart_InfoJs_address",parent.document).parents(".main").siblings(".head").find(".evt-trig-ele:first").children("span").text();
				
				setTimeout(function(){
					var map_auto = that.getPoint(cur_city);			
					var searcharea = $.trim($("input[name='centralized_Depart_InfoJs_city_id']",parent.document).val());  //区域
					var searchbusiness = $.trim($("input[name='centralized_Depart_InfoJs_area_id']",parent.document).val());  //商圈
					var point_lng = $("#centralized_Depart_InfoJs_address",parent.document).attr("point-lng");
					var point_lat = $("#centralized_Depart_InfoJs_address",parent.document).attr("point-lat");
					
					if (point_lng != '' && point_lat != ''){
						if (typeof(callback) == 'function') callback(map_auto.getCenter());
					}
					
					var searchstr = cur_city+searcharea+searchbusiness;
					var options = {
						renderOptions:{map: map_auto},
						onSearchComplete: function(results){
						   if (local.getStatus() == BMAP_STATUS_SUCCESS){
						   	map_auto.clearOverlays();
						   	if (typeof(callback) == 'function') callback(map_auto.getCenter());
						   }    
						 }    
						};  
					var local = new BMap.LocalSearch(map_auto, options);
					local.search(searchstr);
					
				});
			}
		}
});