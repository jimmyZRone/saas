define(function(require,exports,module){
	 var $ = require("jquery"),
         template=require("artTemp"),
         ajax=require("Ajax");
		require("highcharts");


        /*
        * 页面数据暂存
        * */

        var CHARTROOMJSON=[],CHARTFINANCEJSON=[];

		/*
		* @func 页面方法初始化
		* */
        exports.iniPageFunc=function(){
            var that=this;
            that.bind();
            that.iniPageH();
        }
         /*
         * @func 插件初始化
         * */
        exports.bind=function(){
            var that=this;
            $.each($(".roomTypeOps"),function(i,o){
                if($(o).attr("hasevent")){
                    $(o).selectObjM(1,that.regetList);
                }else{
                    $(o).selectObjM();
                }
            });

        }

         /*
         * @func 切换房间重新获取对应房源数据
         * */
        exports.regetList=function(){
             exports.getRoomChartData();
             exports.getFinacneChartData();
        }
        exports.iniPageH=function(){
            var that=this,_sw=window.innerWidth ? window.innerWidth : window.screen.width,//当前屏幕宽度
                  _sh=window.innerHeight ? window.innerHeight:document.documentElement.clientHeight;
            that.iniDataStatics(_sw,_sh);
        }
        exports.iniDataStatics=function(_sw,_sh){
            var  that=this,_curreSw=that.getFixWd(_sw,_sh),
                 boxPar=$(".chart"),boxInner=$(".model-index"),
                 chart_w,chart_h,_fxH=100,_fxW=200;
                boxPar.css({"width":_curreSw.w+"px"});
                boxInner.css({"padding-right":(_curreSw.w+5)+"px"});


                   $(".total-house")[0].style.height=_curreSw.h+"px";
                   $(".total-stream")[0].style.height=_curreSw.h+"px";

                   chart_w=_curreSw.w-_fxW;
                   chart_h=_curreSw.h-_fxH;

                   if(chart_h<=412 && chart_w<685 ){
                      $(".chart-total").css({
                         "top":"0"
                      });
                      if(chart_h<=271){
                          $(".chart-total").css({
                             "top":"10px"
                          });
                      }
                   }

                   $(".chart-img").css({
                      "width":chart_w+"px",
                      "height":chart_h+"px"
                   });
                    if(CHARTROOMJSON && CHARTROOMJSON!=""){
                        that.renderRoomData(CHARTROOMJSON);
                    }else{
                        that.getRoomChartData();
                    }
                    if(CHARTFINANCEJSON && CHARTFINANCEJSON!=""){
                        that.renderFinanceDataBox(CHARTFINANCEJSON);
                    }else{
                        that.getFinacneChartData();
                    }
        }
        /*
         * @func 获取房间统计接口数据
         * */
        exports.getRoomChartData=function(){
            var rurl=$(".total-house").attr("actUrl");
            var data={
                flat_id:$(".roomTypeOps").find("input").attr("selectval")
            };
            ajax.doAjax("post",rurl,data,function(json){
                //console.log(json);
                if(json.status==1){
                    CHARTROOMJSON=json.data;
                    exports.renderRoomData(json.data);
                }
            });
        }
        /*
        *@func 长度过长展示对应单位
        */
        exports.getUnit=function(money){
            if(money > 999999 && money< 100000000) {
                money=parseInt((money/10000))+"万";
            }else if(money>99999999){
                money=parseInt((money/100000000))+"亿";
            }
            return money;
        }
        /*
         * @func 渲染房间统计数据
         * */
        exports.renderRoomData=function(json){
            var data={
                totalCount:parseInt(json.fensan)+parseInt(json.jizhong),
                bookedRooms:parseInt(json.yizu),
                unrentedRooms:parseInt(json.weizu),
                bookRooms:parseInt(json.yuding),
                averagePayment:parseInt(json.pingjunzujin),
                rateRented:json.chuzulv,
                rateMonthAvaiable:json.yuekongzhilv,
                rateYearAvaiable:json.niankongzhilv
            };
            var temp=template('room-statics-info', data);
            $("#room-box").html(temp);
            var dt1=parseInt(json.yizu),
                dt2=parseInt(json.weizu),
                dt3=parseInt(json.yuding);
            if(dt1+dt2+dt3==0){
                dt1=0;
                dt2=100;
                dt3=0;
            }
            $('#container').highcharts({
                chart:{
                    backgroundColor:"#f3f3f3"
                },
                title: {
                    text:''
                },
                legend: {
                    align: 'center',
                    verticalAlign: 'top',
                    x: -45,
                    y: -15,
                    symbolHeight:6,
                    symbolWidth:20
                },
                tooltip: {
                    pointFormat: '<b>{point.percentage:.1f}%</b>'
                },
                plotOptions: {
                    pie: {
                        colors:['#009ae5', '#21ae37', '#fec33c'],
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            format: '{point.percentage:.1f} %'
                        },
                        showInLegend:true
                    }
                },
                series: [{
                    type: 'pie',
                    data:[
                        ['已租房间',dt1],
                        ['未租房间',dt2],
                        ['预订房间',dt3]
                    ]
                }]
            });
        }
        /*
         * @func 获取流水统计接口数据
         * */
        exports.getFinacneChartData=function(){
            var url=$(".total-stream").attr("actUrl"),that=this;
            var data={
                    flat_id:$(".roomTypeOps").find("input").attr("selectval")
                };
            ajax.doAjax("post",url,data,function(json){
                if(json.status==1){
                    CHARTFINANCEJSON=json.data;
                    that.renderFinanceDataBox(json.data);
                }
            });
        }
         /*
        * @func 渲染流水统计数据
        * */
         exports.renderFinanceDataBox=function(json){
             var tdayIncome,tdayPayment,that=exports,
                 tem=[];
             //转化成所需数据格式
             $.each(json.list,function(i,o){
                 var jitem={};
                 i= i.substr(0,2)+"-"+ i.substr(2,2);
                 jitem["date"]= i.toString();
                 jitem["income"]= o.income;
                 jitem["pay"]= o.pay;
                 tem.push(jitem);
             });
             var category=[],
                 jsonIncome=[],
                 jsonPayment=[];
             $.each(tem,function(j,item){
                 category.push(item.date);
                 jsonIncome.push(item.income);
                 jsonPayment.push(item.pay);
             });
//             console.log(tem);
             tdayIncome=tem[tem.length-1].income;
             tdayPayment=tem[tem.length-1].pay;
             var data={
                 todayIncome:that.getUnit(parseInt(json.day_income)),
                 todayPayment:that.getUnit(parseInt(json.day_expense)),
                 totalIncome:that.getUnit(parseInt(json.all_income)),
                 totalPayment:that.getUnit(parseInt(json.all_pay)),
                 detainSum:that.getUnit(parseInt(json.zaizhuzujin))
             };
             var temp=template('finance-statics-info', data);
             $("#finance-box").html(temp);
             $('#harea').highcharts({
                colors:['#009ae5','#fec33c'],
                chart: {
                    type: 'spline',
                    backgroundColor:"#F3F3F3"
                 },
                legend: {
                    align: 'center',
                    verticalAlign: 'top',
                    x: 0,
                    y:0,
                    symbolHeight:6,
                    symbolWidth:20
                },
                title: {
                    text: ''
                },
                xAxis: {
                    categories: category
                },
                yAxis: {
                    min:0,
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
                        name: '收入',
                        data:jsonIncome
                    }, {
                        name: '支出',
                        data: jsonPayment
                    }]
            });
        }
        exports.getFixWd=function(_sw,_sh){
            var _cwd={w:(_sw-150)/2,h:(_sh-100)/2};
            return _cwd;
        }

	$(function(){
		 exports.iniPageFunc();
	});
});
