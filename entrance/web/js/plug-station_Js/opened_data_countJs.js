define(function(require,exports,module){
	var $ = require('jquery');
		require("selectByM")($);
		require("placeholder")($);
		require("highcharts");
	var navigators = require("navigatortest");  //浏览器版本检测
	var ajax = require("Ajax");
	require("ZeroClipboard");
//  var ZeroClipboard = require("ZeroClipboard");
	
	//调用下拉框JS
	$(".selectByM").each(function(){
		var that = $(this);
		if(that.attr('hasevent','true')){
			that.selectObjM(1,function(val,input){
				getDataByConditions();
			});
		}else{
			$(this).selectObjM();
		}
	});
	
	
	/**
	 * 获取数据
	 * 
	 */
	 var getDataByConditions = function(){
        var flat_id = $(".data-count").attr("flat_id"),
        	date = $(".total-month").attr("selectval");
        var type = "POST";
        var data = {
        	"flat_id": flat_id,
        	"date": date
        };
        var url = $(".data-count").attr("dataurl");
        ajax.doAjax(type,url,data,function(json){
        	var msg;
            if(json.status == 1){
            	msg = json.data;
            	$(".total-fk > span").text(msg.fk);
            	$(".total-sc > span").text(msg.sc);
            	$(".total-yy > span").text(msg.yy);
                renderTotalData(msg);
            }
            else{
            	msg = {
            		"fk": 0,
            		"sc": 0,
            		"yy": 0,
            		"everyday": {
            			"1": [0,0,0],
            		}
            		
            	}
            	renderTotalData(msg);
            }
        });
    }
	getDataByConditions();
	
	/**
	 * 渲染统计数据
	 * 
	 */
	 var renderTotalData = function(json){
	 	var arr = [];
	 	$.each(json.everyday, function(i, o) { 
	 		var obj = {};
	 		var month = $(".total-month").attr("selectval").substr(5,2);
//	 		if(i > 10){
//	 			i = i;
//	 		}else{
//	 			i = 0 + i
//	 		};
//	 		var day = month + '-' + i;
			var day = i;
	 		obj["date"] = day.toString();
	 		obj["fk"] = o[0];
	 		obj["sc"] = o[1];
	 		obj["yy"] = o[2];
	 		arr.push(obj);
	 	});
	 	
	 	var _date = [];
	 	var _fk = [];
	 	var _sc = [];
	 	var _yy = [];
	 	$.each(arr, function(k, v) {
	 		_date.push(v.date);
	 		_fk.push(v.fk);
	 		_sc.push(v.sc);
	 		_yy.push(v.yy);
	 	});
	 	
	 	
        $(".data-chart-con").highcharts({
        	colors:['#009ae5','#fec33c'],
            chart: {
                type: 'spline',
                backgroundColor:"#fff",
             },
            legend: {
                align: 'center',
                verticalAlign: 'top',
                x:-80,
                y:0,
                symbolHeight:6,
                symbolWidth:20,
                itemMarginBottom: 30
            },
            title: {
                text: ''
            },
            xAxis: {
                categories: _date
            },
            yAxis: {
                title: {
                    text: ''
                }
                },
            tooltip: {
                shared: true
            },
            plotOptions: {
                area: {
                    marker: {
                        enabled: false,
                        symbol: 'circle',
                        radius: 2,
                        states: {
                            hover: {
                                enabled: true
                            }
                        }
                    }
                }
            },
            series: [{
                    name: '访客数',
                    data: _fk
                }, {
                    name: '收藏数',
                    data: _sc
                },{
                	name: '预约数',
                    data: _yy
                }]
        });
    }

	
	/**
	 * 复制链接功能
	 * 
	 */
	ZeroClipboard.setMoviePath(baseUri + "ZeroClipboard/ZeroClipboard.swf");
	var clip = new ZeroClipboard.Client();
	clip.setHandCursor( true );
	var text = document.getElementById("personal-url").text;
	clip.setText(text);
	clip.glue($('.copy-btn').get(0)); 
	clip.addEventListener( "complete", function(){   
		window.parent.mask.noDialogShowModle('提示信息','复制成功');   
	}); 

	
});