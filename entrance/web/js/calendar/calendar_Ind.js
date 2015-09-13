define(function(require, exports, moudles){
var $ = require("jquery"),
    ajax=require("Ajax"),
   navigators = require("navigatortest"),
   loading=require("loading");
var lunarInfo=new Array(
0x04bd8,0x04ae0,0x0a570,0x054d5,0x0d260,0x0d950,0x16554,0x056a0,0x09ad0,0x055d2,
0x04ae0,0x0a5b6,0x0a4d0,0x0d250,0x1d255,0x0b540,0x0d6a0,0x0ada2,0x095b0,0x14977,
0x04970,0x0a4b0,0x0b4b5,0x06a50,0x06d40,0x1ab54,0x02b60,0x09570,0x052f2,0x04970,
0x06566,0x0d4a0,0x0ea50,0x06e95,0x05ad0,0x02b60,0x186e3,0x092e0,0x1c8d7,0x0c950,
0x0d4a0,0x1d8a6,0x0b550,0x056a0,0x1a5b4,0x025d0,0x092d0,0x0d2b2,0x0a950,0x0b557,
0x06ca0,0x0b550,0x15355,0x04da0,0x0a5d0,0x14573,0x052d0,0x0a9a8,0x0e950,0x06aa0,
0x0aea6,0x0ab50,0x04b60,0x0aae4,0x0a570,0x05260,0x0f263,0x0d950,0x05b57,0x056a0,
0x096d0,0x04dd5,0x04ad0,0x0a4d0,0x0d4d4,0x0d250,0x0d558,0x0b540,0x0b5a0,0x195a6,
0x095b0,0x049b0,0x0a974,0x0a4b0,0x0b27a,0x06a50,0x06d40,0x0af46,0x0ab60,0x09570,
0x04af5,0x04970,0x064b0,0x074a3,0x0ea50,0x06b58,0x055c0,0x0ab60,0x096d5,0x092e0,
0x0c960,0x0d954,0x0d4a0,0x0da50,0x07552,0x056a0,0x0abb7,0x025d0,0x092d0,0x0cab5,
0x0a950,0x0b4a0,0x0baa4,0x0ad50,0x055d9,0x04ba0,0x0a5b0,0x15176,0x052b0,0x0a930,
0x07954,0x06aa0,0x0ad50,0x05b52,0x04b60,0x0a6e6,0x0a4e0,0x0d260,0x0ea65,0x0d530,
0x05aa0,0x076a3,0x096d0,0x04bd7,0x04ad0,0x0a4d0,0x1d0b6,0x0d250,0x0d520,0x0dd45,
0x0b5a0,0x056d0,0x055b2,0x049b0,0x0a577,0x0a4b0,0x0aa50,0x1b255,0x06d20,0x0ada0)

var solarMonth=new Array(31,28,31,30,31,30,31,31,30,31,30,31);
var Gan=new Array("甲","乙","丙","丁","戊","己","庚","辛","壬","癸");
var Zhi=new Array("子","丑","寅","卯","辰","巳","午","未","申","酉","戌","亥");
var Animals=new Array("鼠","牛","虎","兔","龙","蛇","马","羊","猴","鸡","狗","猪");
var solarTerm = new Array("小寒","大寒","立春","雨水","惊蛰","春分","清明","谷雨","立夏","小满","芒种","夏至","小暑","大暑","立秋","处暑","白露","秋分","寒露","霜降","立冬","小雪","大雪","冬至")
var sTermInfo = new Array(0,21208,42467,63836,85337,107014,128867,150921,173149,195551,218072,240693,263343,285989,308563,331033,353350,375494,397447,419210,440795,462224,483532,504758)
var nStr1 = new Array('日','一','二','三','四','五','六','七','八','九','十')
var nStr2 = new Array('初','十','廿','卅','　')
var monthName = new Array("JAN","FEB","MAR","APR","MAY","JUN","JUL","AUG","SEP","OCT","NOV","DEC");
/*
*日历事件模拟数据
*/
var calEvents={
   data:[
      {
         "date":"2049-3-19",
         "title":"事件1",
         "description":"描述1"
      },
      {
         "date":"2015-5-20",
         "title":"事件2",
         "description":"描述2"
      },
      {
         "date":"2015-5-21",
         "title":"事件2",
         "description":"描述2"
      },
      {
         "date":"2015-4-22",
         "title":"事件2",
         "description":"描述2"
      },
      {
         "date":"2015-4-23",
         "title":"事件2",
         "description":"描述2"
      },
      {
         "date":"2015-12-31",
         "title":"事件2",
         "description":"描述2"
      },
      {
         "date":"2015-1-1",
         "title":"事件2",
         "description":"描述2"
      }
   ]
};


//国历节日 *表示放假日
var sFtv = new Array(
"0101*元旦",
"0214 情人节",
"0308 妇女节",
"0312 植树节",
"0315 消费者日",
"0401 愚人节",
"0501*劳动节",
"0504 青年节",
"0512 护士节",
"0601 儿童节",
"0701 建党节",
"0801 建军节",
"0909 毛泽东逝世纪念",
"0910 教师节",
"0928 孔子诞辰",
"1001*国庆节",
"1006 老人节",
"1024 联合国日",
"1112 孙中山诞辰纪念",
"1220 澳门回归",
"1225 圣诞节",
"1226 毛泽东诞辰纪念")

//农历节日 *表示放假日
var lFtv = new Array(
"0101*春节",
"0115 元宵节",
"0505 端午节",
"0707 七夕节",
"0715 中元节",
"0815 中秋节",
"0909 重阳节",
"1208 腊八节",
"1223 小年",
"0100*除夕")

var wFtv = new Array(
"0520 母亲节", "0630 父亲节")

var YIndex,Mindex;
function lYearDays(y) {
   var i, sum = 348
   for(i=0x8000; i>0x8; i>>=1) sum += (lunarInfo[y-1900] & i)? 1: 0
   return(sum+leapDays(y))
}

function leapDays(y) {
   if(leapMonth(y))  return((lunarInfo[y-1900] & 0x10000)? 30: 29)
   else return(0)
}

function leapMonth(y) {
   return(lunarInfo[y-1900] & 0xf)
}

function monthDays(y,m) {
   return( (lunarInfo[y-1900] & (0x10000>>m))? 30: 29 )
}

function Lunar(objDate) {

   var i, leap=0, temp=0
   var baseDate = new Date(1900,0,31)
   var offset   = parseInt((objDate - baseDate)/86400000)

   this.dayCyl = offset + 40
   this.monCyl = 14

   for(i=1900; i<2050 && offset>0; i++) {
      temp = lYearDays(i)
      offset -= temp
      this.monCyl += 12
   }

   if(offset<0) {
      offset += temp;
      i--;
      this.monCyl -= 12
   }

   this.year = i
   this.yearCyl = i-1864

   leap = leapMonth(i)
   this.isLeap = false

   for(i=1; i<13 && offset>0; i++) {
      if(leap>0 && i==(leap+1) && this.isLeap==false)
         { --i; this.isLeap = true; temp = leapDays(this.year); }
      else
         { temp = monthDays(this.year, i); }

      if(this.isLeap==true && i==(leap+1)) this.isLeap = false

      offset -= temp
      if(this.isLeap == false) this.monCyl ++
   }

   if(offset==0 && leap>0 && i==leap+1)
      if(this.isLeap)
         { this.isLeap = false; }
      else
         { this.isLeap = true; --i; --this.monCyl;}

   if(offset<0){ offset += temp; --i; --this.monCyl; }

   this.month = i
   this.day = offset + 1
}

function solarDays(y,m) {
   if(m==1)
      return(((y%4 == 0) && (y%100 != 0) || (y%400 == 0))? 29: 28)
   else
      return(solarMonth[m])
}
function cyclical(num) {
   return(Gan[num%10]+Zhi[num%12])
}

function calElement(sYear,sMonth,sDay,week,lYear,lMonth,lDay,isLeap,cYear,cMonth,cDay) {

      this.isToday    = false;
      this.sYear      = sYear;
      this.sMonth     = sMonth;
      this.sDay       = sDay;
      this.week       = week;
      this.lYear      = lYear;
      this.lMonth     = lMonth;
      this.lDay       = lDay;
      this.isLeap     = isLeap;
      this.cYear      = cYear;
      this.cMonth     = cMonth;
      this.cDay       = cDay;

      this.color      = '';

      this.lunarFestival = '';
      this.solarFestival = '';
      this.solarTerms    = '';
      this.eventRecord   = [];//增加事件字段
}

function sTerm(y,n) {
   var offDate = new Date( ( 31556925974.7*(y-1900) + sTermInfo[n]*60000  ) + Date.UTC(1900,0,6,2,5) )
   return(offDate.getUTCDate())
}

function calendar(y,m) {

   var sDObj, lDObj, lY, lM, lD=1, lL, lX=0, tmp1, tmp2
   var lDPOS = new Array(3)
   var n = 0
   var firstLM = 0

   sDObj = new Date(y,m,1)

   this.length    = solarDays(y,m)
   this.firstWeek = sDObj.getDay()


   for(var i=0;i<this.length;i++) {

      if(lD>lX) {
         sDObj = new Date(y,m,i+1)
         lDObj = new Lunar(sDObj)
         lY    = lDObj.year
         lM    = lDObj.month
         lD    = lDObj.day
         lL    = lDObj.isLeap
         lX    = lL? leapDays(lY): monthDays(lY,lM)

         if(n==0) firstLM = lM
         lDPOS[n++] = i-lD+1
      }

      this[i] = new calElement(y, m+1, i+1, nStr1[(i+this.firstWeek)%7],
                               lY, lM, lD++, lL,
                               cyclical(lDObj.yearCyl) ,cyclical(lDObj.monCyl), cyclical(lDObj.dayCyl++) )


      if((i+this.firstWeek)%7==0)   this[i].color = '#FF5F07';
      if((i+this.firstWeek)%14==13) this[i].color = '#FF5F07';

      /*
      *将事件和日期相关联 方案一采用
      *@create:2015-3-19
      */
     //  var that=this[i];
     //  $.each(calEvents.data,function(j,item){
     //      var str=that.sYear+"-"+that.sMonth+"-"+that.sDay;
     //        if(str==item.date){
     //           that.eventRecord.push(item);
     //        }
     // });
   }

   tmp1=sTerm(y,m*2  )-1
   tmp2=sTerm(y,m*2+1)-1
   this[tmp1].solarTerms = solarTerm[m*2]
   this[tmp2].solarTerms = solarTerm[m*2+1]
   if(m==3) this[tmp1].color = '#FF5F07'

   for(i in sFtv)
      if(sFtv[i].match(/^(\d{2})(\d{2})([\s\*])(.+)$/))
         if(Number(RegExp.$1)==(m+1)) {
			var fes = isLeg(RegExp.$4, y);
			if(fes == "") continue;
            this[Number(RegExp.$2)-1].solarFestival += fes + ' '
            if(RegExp.$3=='*') this[Number(RegExp.$2)-1].color = '#FF5F07'
         }

   for(i in wFtv)
      if(wFtv[i].match(/^(\d{2})(\d)(\d)([\s\*])(.+)$/))
         if(Number(RegExp.$1)==(m+1)) {
            tmp1=Number(RegExp.$2)
            tmp2=Number(RegExp.$3)
            this[((this.firstWeek>tmp2)?7:0) + 7*(tmp1-1) + tmp2 - this.firstWeek].solarFestival += RegExp.$5 + ' '
         }

   for(i in lFtv)
      if(lFtv[i].match(/^(\d{2})(.{2})([\s\*])(.+)$/)) {
         tmp1=Number(RegExp.$1)-firstLM
         if(tmp1==-11) tmp1=1
         if(tmp1 >=0 && tmp1<n) {
            tmp2 = lDPOS[tmp1] + Number(RegExp.$2) -1
            if( tmp2 >= 0 && tmp2<this.length) {
               this[tmp2].lunarFestival += RegExp.$4 + ' '
               if(RegExp.$3=='*') this[tmp2].color = '#FF5F07'
            }
         }
      }


   if(y==tY && m==tM) {
   		this[tD-1].isToday = true;
   }
}

function cDay(d){
   var s;

   switch (d) {
      case 10:
         s = '初十'; break;
      case 20:
         s = '二十'; break;
         break;
      case 30:
         s = '三十'; break;
         break;
      default :
         s = nStr2[Math.floor(d/10)];
         s += nStr1[d%10];
   }
   return(s);
}

var cld;
/*
**日历页面渲染
*/
function drawCld(SY,SM) {
   var i,sD,s,size;
   cld = new calendar(SY,SM);//日历对象初始化
   /*农历显示*/
   // GZ.innerHTML = '&nbsp;&nbsp;农历' + cyclical(SY-1900+36) + '年 &nbsp;<span class=smlb>&nbsp;【</spzn><span class=smlb>'+Animals[(SY-4)%12]+'</span><span class=smlb>】</span>';

   //YMBG.innerHTML = "&nbsp;" + SY + "<BR>&nbsp;" + monthName[SM];
   $("#curr-year-txt").text(SY);//年月份显示
   $("#curr-mon-txt").text((SM+1));//年月份显示
   	if($(window).width()>=1440){
	   if(tM!=SM || tY != SY) $("#currentM").removeClass("none");
	   else  $("#currentM").addClass("none");
   	}
   for(i=0;i<42;i++) {
      sObj=eval('SD'+ i);
      lObj=eval('LD'+ i);
      pointObj=eval('point'+i);//事件标记
      $(sObj.parentNode).removeClass("today-mark").removeClass("event-mark-padding");
      $(pointObj).removeClass("event-mark");
      $(sObj.parentNode.parentNode).removeClass("event-day-record");
      $(sObj.parentNode.parentNode).removeClass("event-day-marker");

      sD = i - cld.firstWeek;
      if(sD>-1 && sD<cld.length) {
         sObj.innerHTML = (sD+1);
         if(cld[sD].isToday){
               $(sObj.parentNode).addClass("today-mark");
                $(sObj.parentNode.parentNode).addClass("event-day-record");
//              $(pointObj).addClass("event-mark");
         }

         // sObj.style.color = cld[sD].color;

         // if(cld[sD].lDay==1)
            // lObj.innerHTML = '<b>'+(cld[sD].isLeap?'闰':'') + cld[sD].lMonth + '月' + (monthDays(cld[sD].lYear,cld[sD].lMonth)==29?'小':'大')+'</b>';
         // else
            lObj.innerHTML = cDay(cld[sD].lDay);//填充农历
         s=cld[sD].lunarFestival;
         if(s.length>0) {
            if(s.length>5) s = s.substr(0, 3)+'…';
            // s = s.fontcolor('FF5F07');
         }else {
            s=cld[sD].solarFestival;
            if(s.length>0) {
               size = (s.charCodeAt(0)>0 && s.charCodeAt(0)<128)?8:4;
               if(s.length>size+1) s = s.substr(0, size-1)+'…';
               // s = s.fontcolor('0168EA');
            }
            else {
               s=cld[sD].solarTerms;
               // if(s.length>0) 
                  // s = s.fontcolor('44D7CF');
            }
         }
         if(s.length>0)  lObj.innerHTML = s;
      }else {
            sObj.innerHTML = ' ';
            lObj.innerHTML = ' ';
      }
   }
   var timer_auto = null;
   clearTimeout(timer_auto);
   timer_auto = setTimeout(function(){
   	  fillEventData(SY,SM);
      $("#calendar_Ind_box").addClass("none").html("");
      $(".calendar_Ind").removeClass("none");
   },500);
   exports.renderClaenBox();//日历容器高度渲染，整屏幕
}

/*
**@fun 日历容器高度渲染，整屏幕
*生成的最后一行是空数据行，将其隐藏
*@date:2015-3-19
*/
exports.renderClaenBox=function(){
   var sh=window.innerHeight ? window.innerHeight:document.documentElement.clientHeight,
   box_h=parseFloat(sh-105);//当前内容可视高度
   var par=$(".cld-body"),
       _ele=par.find("table.calendar_Txt"),
       _lst=$(".cld-body .col-tr:eq("+($(".col-tr").length-1)+")").find("table.calendar_Txt").find("td:first-child").find(".per-day").text();
       if($.trim(_lst)==""){
         $(".cld-body .col-tr:eq("+($(".col-tr").length-1)+")").addClass("none");
       }else{
         $(".cld-body .col-tr:eq("+($(".col-tr").length-1)+")").removeClass("none");
       }
       var len=$(".cld-body").find(".col-gem").not(".col-tr.none").length;
            $(".main")[0].style.height=sh-60+"px";
       if(sys.ie && sys.ie < "9.0") {
             $(".main")[0].style.overflow="hidden";
            $(".chart").css({
               "height":box_h+"px"
            });
       }
      sh=sh-265;//除去页面已占据位置的固定元素的高度
      _ele.css({
         "height":parseFloat((sh/len))+"px"
      });
      var gem_len=$(".col-gem").length-1,
      	  _tar=$(".col-gem:eq("+gem_len+")"),
      	  _tarPrev=_tar.prev(),
      	  _style=$.trim(_tarPrev.attr("style").replace("background:","").replace("#",""));
      	if(_style=="f5f5f5"){
      		if(_tarPrev.hasClass("none")) _tar.css({"background":"#f5f5f5"});
      		else _tar.css({"background":"#f9f9f9"});
      	}
}
/*
**@fun 事件和日期关联
*@date:2015-3-19
*/
function fillEventData(SY,SM){
	var url = $(".calendar .c-head h1").attr("url"),surl = $(".calendar .c-head h1").attr("surl");
	var data = {
		year_month : SY+"-"+(SM+1)
	}
   var settings={
      type:"post",
      url:url,
      data:data,
      surl:surl
   },sD;
   ajax.doAjax(settings.type,settings.url,settings.data,function(json){
      cld = new calendar(SY,SM);
      if(json.status==1){
         calEvents=json.data;
         for(i=0;i<42;i++) {
            pointObj=eval('point'+i);//事件标记
            $(pointObj).removeClass("event-mark");
           $(pointObj.parentNode).removeClass("event-day-record");
           $(pointObj.parentNode).removeClass("event-day-marker");
            sD = i - cld.firstWeek;
            if(sD>-1 && sD<cld.length) {
               /*
               *当天是否有代办事项判断
               *@date:2015-3-19
               */
               var that=cld[sD];
               $.each(calEvents,function(j,item){
                   var str=that.sYear+"-"+that.sMonth+"-"+that.sDay;
                     if(str==item.deal_time){
                        that.eventRecord.push(item);
                     }
              });
              var eventDay=cld[sD].eventRecord;
                  if(eventDay.length>0){
                      $(pointObj).addClass("event-mark");
                      $(pointObj.parentNode).addClass("event-day-record");
                      $(pointObj.parentNode).addClass("event-day-marker");
                      $(pointObj.parentNode).attr("title","点击可查看当日待办事项");
                  }
              }
         }
      }else{
         // $(".cld-body")[0].innerHTML="抱歉，暂时无法获取到日历数据";
      }
      //测试事件渲染
//    for(i=0;i<42;i++) {
//          pointObj=eval('point'+i);//事件标记
//          $(pointObj).removeClass("event-mark");
//          sD = i - cld.firstWeek;
//          if(sD>-1 && sD<cld.length) {
//             /*
//             *当天是否有代办事项判断
//             *@date:2015-3-19
//             */
//             var that=cld[sD];
//             $.each(calEvents.data,function(j,item){
//                 var str=that.sYear+"-"+that.sMonth+"-"+that.sDay;
//                   if(str==item.date){
//                      that.eventRecord.push(item);
//                   }
//            });
//            var eventDay=cld[sD].eventRecord;
//                if(eventDay.length>0){
//                      $(pointObj).addClass("event-mark");
//                   	$(pointObj.parentNode).addClass("event-day-record");
//                   	$(pointObj.parentNode).addClass("event-day-marker");
//              }
//            }
//       }
   });
   //取总数
   ajax.doAjax(settings.type,settings.surl,"",function(json){
       $(".todo-col").find("span").text(json.data);
       $("#todo-all-ops").find(".cmh-l span i").text(json.data);
   });  
}

/*
*改变当前时间重新渲染日历
*/
function changeCld() {
   var y,m;
   y=YIndex+1900;
   m=MIndex;
   // getEventAjaxData(y,m);
   drawCld(y,m);
}
/*
*上/下一个年/月切换
*/
function pushBtm(K) {
   switch (K){
      case 'YU' :
         if(YIndex>0) YIndex--;
         break;
      case 'YD' :
         if(YIndex<149) YIndex++;
         break;
      case 'MU' :
         if(MIndex>0) {
            MIndex--;
         }
         else {
            MIndex=11;
            if(YIndex>0) YIndex--;
         }
         break;
      case 'MD' :
         if(MIndex<11) {
            MIndex++;
         }
         else {
            MIndex=0;
            if(YIndex<149) YIndex++;
         }
         break;
      default :
         YIndex=tY-1900;
         MIndex=tM;
   }
   changeCld();
}

var Today = new Date();
var tY = Today.getFullYear();
var tM = Today.getMonth();
var tD = Today.getDate();

var width = "130";
var offsetx = 2;
var offsety = 16;

var x = 0;
var y = 0;
var snow = 0;
var sw = 0;
var cnt = 0;

var dStyle;

function mOvr(v) {
   var s,festival;
   var sObj=eval('SD'+ v);
   var d=sObj.innerHTML-1;
   if(sObj.innerHTML!='') {
      sObj.style.cursor = 's-resize';
	  if(cld[d].solarTerms == '' && cld[d].solarFestival == '' && cld[d].lunarFestival == '')
         festival = '';
      else
         festival = '<TABLE WIDTH=100% BORDER=0 CELLPADDING=2 CELLSPACING=0 BGCOLOR="#0978A6"><TR><TD>'+
         '<FONT COLOR="#D8F6F8" STYLE="font-size:9pt;"><b>'+cld[d].solarTerms + ' ' + cld[d].solarFestival + ' ' + cld[d].lunarFestival+'</b></FONT></TD>'+
         '</TR></TABLE>';

      s= '<TABLE WIDTH="130" BORDER=0 CELLPADDING="2" CELLSPACING=0 ><TR><TD>' +
         '<TABLE WIDTH=100% BORDER=0 CELLPADDING=0 CELLSPACING=0 BGCOLOR="#B6E5F5"><TR><TD ALIGN="right"><FONT COLOR="#000000" STYLE="font-size:9pt;">'+
         cld[d].sYear+' 年 '+cld[d].sMonth+' 月 '+cld[d].sDay+' 日<br>星期'+cld[d].week+'<br>'+
         '<font color="02346F">农历'+(cld[d].isLeap?'闰 ':' ')+cld[d].lMonth+' 月 '+cld[d].lDay+' 日</font><br>'+
         '<font color="02346F">'+cld[d].cYear+'年 '+cld[d].cMonth+'月 '+cld[d].cDay + '日</font>'+
         '</FONT></TD></TR></TABLE>'+ festival +'</TD></TR></TABLE>';


      document.all["detail"].innerHTML = s;
	}
   	if (snow == 0) {
//       dStyle.left = x+offsetx-(width/2);
         dStyle.css({"left":x+offsetx-(width/2)+"px","top":y+offsety+"px","visibility":"visible"});
//       dStyle.top = y+offsety;
		
// 		dStyle.visibility = "visible";
   		snow = 1;
   	}
}

/*
*鼠标移入显示详细农历信息事件
*/
function mOut() {
	if ( cnt >= 1 ) { sw = 0 }
	if ( sw == 0 ) { snow = 0;	
//		dStyle.visibility = "hidden";
		dStyle.css("visibility","hidden");
	}
	else cnt++;
}
/*
*鼠标移出事件
*/
function mEvn(e) {
	var e = e||event;
	var mousePos = mouseCoords(e);
   	x=mousePos.x;
   	y=mousePos.y;
	if (document.body.scrollLeft)
	   {x=e.x+document.body.scrollLeft; y=e.y+document.body.scrollTop;}
	if (snow){
//    dStyle.left = x+offsetx-(width/2)
//    dStyle.top = y+offsety
	  dStyle.css({"left":x+offsetx-(width/2)+"px","top":y+offsety+"px"});
	}
}

function mouseCoords(ev){ 
	if(ev.pageX || ev.pageY){ 
		return {x:ev.pageX, y:ev.pageY}; 
		}else{
			if(document.documentElement.scrollTop !=''){
				return { 
					x:ev.clientX + document.body.scrollLeft - document.body.clientLeft, 
					y:ev.clientY + document.documentElement.scrollTop - document.body.clientTop 
				}; 		
			}else{
				return { 
					x:ev.clientX + document.body.scrollLeft - document.body.clientLeft, 
					y:ev.clientY + document.body.scrollTop - document.body.clientTop 
				};
			}
		}
} 

function setCookie(name, value) {
	var today = new Date()
	var expires = new Date()
	expires.setTime(today.getTime() + 1000*60*60*24*365)
	document.cookie = name + "=" + escape(value)	+ "; expires=" + expires.toGMTString()
}

function getCookie(Name) {
   var search = Name + "="
   if(document.cookie.length > 0) {
      offset = document.cookie.indexOf(search)
      if(offset != -1) {
         offset += search.length
         end = document.cookie.indexOf(";", offset)
         if(end == -1) end = document.cookie.length
         return unescape(document.cookie.substring(offset, end))
      }
      else return ""
   }
}

/*
*@desc:进入页面初始化数据
*/
function initial() {
   dStyle = $("#detail");
   YIndex=tY-1900;
   MIndex=tM;
   // getEventAjaxData(tY,tM);
   loading.pageLoading("#calendar_Ind_box");
    drawCld(tY,tM);

// CLD.TZ.selectedIndex=getCookie("TZ");
// if(CLD.TZ.selectedIndex<1)
//   CLD.TZ.selectedIndex=39
// changeTZ();
// tick();
}
function isLeg(fes, y){
	y = y - 0;
	switch(fes){
		case "元旦":
			if(y>1911 && y<1950){

			}else if(y>1949){
				fes = "新年";
			}else{
				fes = "";
			}
			break;
		case "情人节":
			break;
		case "妇女节":
			if(y<1911) fes = "";
			break;
		case "植树节":
			if(y<1979) fes = "";
			break;
		case "消费者日":
			if(y<1988) fes = "";
			break;
		case "愚人节":
			if(y<1564) fes = "";
			break;
		case "劳动节":
			if(y<1890) fes = "";
			break;
		case "青年节":
			if(y<1950) fes = "";
			break;
		case "护士节":
			if(y<1912) fes = "";
			break;
		case "儿童节":
			break;
//		case "建党节":
//			if(y<1911) fes = "";
//			else if(y>1920 && y<1997) fes = "建党节";
//			else fes = "香港回归";
//			break;
		case "建军节":
			break;
		case "父亲节":
			break;
		case "毛泽东逝世纪念":
			fes = "";
			break;
		case "教师节":
			if(y<1985) fes = "";
			break;
		case "孔子诞辰":
			fes = "";
			break;
		case "国庆节":
			if(y<1949) fes = "";
			break;
		case "老人节":
			break;
		case "联合国日":
			fes = "";
			break;
		case "孙中山诞辰纪念":
			fes = "";
			break;
		case "澳门回归":
			if(y<1999) fes = "";
			break;
		case "圣诞节":
			break;
		case "毛泽东诞辰纪念":
			fes = "";
			break;
	}
	return fes;
}
function creat_Canlender(obj){
	obj = obj.children().children("tr:first-child");
    var gNum;
    var str = '';
    for(i=0;i<6;i++) {
       if(i%2==0) 
            str+='<tr class="col-tr col-gem" style="background:#f9f9f9">';
       else
            str+='<tr class="col-tr col-gem" style="background:#f5f5f5">';
       str +='<td><table class="calendar_Txt" width="100%" height="100%" border="0" cellpadding="0" cellspacing="1"><tr align=center> ';
       for(j=0;j<7;j++) {
          gNum = i*7+j
          str +='<td id="TD' + gNum +'"';
          str +='><div class="pos-rel"><div class="circle"><p class="per-day" id="SD' + gNum +'"> </p><p class="ny-day" id="LD' + gNum +'"></p></div><p id="point' + gNum +'"></p></div></td>';
       }
       str +='</tr></table></td></tr>';
    }
    str+='<tr class="col-gem"><td><table class="calendar_Txt" width="100%" height="100%" border="0" cellpadding="0" cellspacing="1"><tr align=center><td>'
    +'<div class="c-bottom"><div class="cb-cutline">'
	+	'<a href="javascript:;" class="m-zero cld-col fr">'
	+		'<i class="zero today"></i>今日'
	+	'</a>'
	+	'<a href="javascript:;" class="m-zero fr">'
	+		'<i class="zero untreated"></i>未办事情'
	+	'</a>'
	+'</div></div></td></tr></table></td></tr>';
    obj.after(str);
}
/*
**初始化年月份索引存储
*/
function iniDateset(){
   var stM=tM+1,
       years=$(".year-ranges li"),
       months=$(".month-ranges li");
      $.each(years,function(i,o){
          var  that=$(o).text();
          if(tY==that){
            $(o).addClass("current");
            var s=$(o).index()-1;
            YIndex=s;
          }
      });
      $.each(months,function(i,o){
           var  that=$(o).text();
          if(stM==that){
            $(o).addClass("current");
            var s=$(o).index()-1;
            MIndex=s;
          }
      });
   }
	exports.inite = function(obj){
      //window.onresize=function(){
      //	$(".main").removeAttr("style");
      //   exports.renderClaenBox();//改变窗口大小重新渲染日历
      //}
		document.onmousemove = mEvn;
		creat_Canlender(obj);
		initial();
       iniDateset();
      $(".year-ranges li").unbind().bind("click",function(){
         var  cur=$(this),txt;
             YIndex=cur.index()-1;
             if(!cur.hasClass("current")){
                cur.parent().find(".current").removeClass("current");
                cur.addClass("current");
                txt=cur.text();
                cur.parent().prev().text(txt);
                changeCld();
             }
      });
      $(".month-ranges li").unbind().bind("click",function(){
         var  cur=$(this);
             MIndex=cur.index()-1;
             if(!cur.hasClass("current")){
                cur.parent().find(".current").removeClass("current");
                cur.addClass("current");
                var txt=cur.text();
                cur.parent().prev().text(txt);
                changeCld();
             }
      });
		$("#prevY").off().on("click",function(){
			pushBtm('YU');//前一年
		});
		$("#nextY").off().on("click",function(){
			pushBtm('YD');//下一年
		});
		$("#prevM").off().on("click",function(){
			pushBtm('MU');//前一月
		});
		$("#nextM").off().on("click",function(){
			pushBtm('MD');//下一月
		});
		$("#currentM").off().on("click",function(){
			pushBtm('');//当前月
		});
      //hover显示农历信息
		// $(".calendar_Txt").find("td").hover(function(){
		// 	var num = $(this).attr("id").replace(/[^0-9]/ig, "");
		// 	mOvr(num);
		// },function(){
		// 	mOut();
		// });
	}

});