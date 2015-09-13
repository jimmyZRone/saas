define(function(require,exports,module){
  var ajax=require("Ajax"),
      sms=require("sms"),XHR,
     dialog = require("dialog"),
     template=require("artTemp");
  var CACHEFEENAMEUNIT=[],
      CACHESMSRECEIVER=[],//短信发送对象缓存
      CACEHSMSTEMPL=[],//短信发送模板缓存
      TEXTUNIT={};
  var  moduleInite=function($$){
   var  iaSms=function(){
       this.init();
   }
   iaSms.prototype={
       init:function(){
          var that=this;
          that.tabSwitch();
          that.iniSetselEvt();
          that.closeActApply();
          that.cacheFeeNames();
          that.addFeesItem();
          that.delFeeItem();
          that.cacheSmsTempdata();
       },
       //缓存发送对象和短信模板数据 建立对应关系
       cacheSmsTempdata:function(){
           CACHESMSRECEIVER=[];
           CACEHSMSTEMPL=[];
          var  guests_src=$("#sms-guests",$$).find(".selectByMO ul li"),
               sms_temp=$("#render-guest-tp",$$).find(".selectByMO ul li");
            $.each(guests_src,function(i,o){
                var _pt={};
                _pt["id"]=$(o).attr("selectVal");
                _pt["text"]=$(o).text();
                CACHESMSRECEIVER.push(_pt);
            });
            $.each(sms_temp,function(i,o){
                var _pt={};
                _pt["id"]=$(o).attr("selectVal");
                _pt["pid"]=$(o).attr("pid");
                _pt["text"]=$(o).text();
                CACEHSMSTEMPL.push(_pt);
            });
       },
       //获取对应对象模板
       getDxTemp:function(id){
          var dt=[];
          //未选择
          if(id=="-1"){
             dt=CACEHSMSTEMPL;
          }else{
            $.each(CACEHSMSTEMPL,function(i,o){
               if(id==o.pid){
                  dt.push(o);
               }
               if(o.pid==0 && id!=0){
                  dt.push(o);
               }
            });
          }
          return dt;
       },
       //选择租客来源回调，获取对应租客人数 统计发送短信
       choseGuestsResrcCb:function(ev){

       },
       /*
        *@func 选择短信群发对象回调，展示对应的短信模板
       */
       choseGuestsCb:function(ev,obj){
          var  that=iaSms.prototype;
          var dt=that.getDxTemp(ev);
          var li="";
          if(ev!="-1"){
            li='<li selectVal="-1" pid="-1">选择短信模板</li>';
          }
          $.each(dt,function(j,item){
               li+='<li selectVal="'+item.id+'" pid="'+item.pid+'">'+item.text+'</li>';
          });
          var _tar=$("#render-guest-tp",$$);//目标模板元素
          _tar.find(".selectByMO ul").html(li);
          that.iniSetselEvt();
          _tar.find(".selectByMO ul").find("li:eq(0)").trigger("click");
       },
       /*
        *@func 关闭智能短信功能
       */
       closeActApply:function(){
          $(".btn-close-allpy",$$).off().on("click",function(){
             var d=dialog({
                title:"关闭智能短信",
                content:"确认关闭智能短信?",
                ok:function(){
                  var tag1= WindowTag.getCurrentTag();
                  WindowTag.closeTag(tag1.find('>a:first').attr('url'));//关闭当前tag
                  var ctag = WindowTag.getTagByUrlHash("/index.php?c=plugins-sms&a=index");
                  if(ctag){
                     window.WindowTag.selectTag(ctag.find(' > a:first').attr('href'));
                     window.WindowTag.loadTag(ctag.find(' > a:first').attr('href'),'get',function(){});
                  }else{
                     window.WindowTag.openTag("#"+"/index.php?c=plugins-sms&a=index");
                  }
                  //关闭智能短信请求
                  ajax.doAjax("post","url","",function(json){

                  });
                },
                okValue:"确定"
             });
             d.showModal();
          });
       },
       /*
 			 *@func 下拉事件绑定
 			 * */
 			iniSetselEvt:function(){
 				var par=$(".selectByM",$$),that=this;
 				$.each(par,function(i,o){
 					if($(o).attr("hasevent") && $(o).attr("hasevent")=="3"){
 						 $(o).selectObjM(1,that.chosePayNameCb);//费用名称回调
 					}else if($(o).attr("hasevent") && $(o).attr("hasevent")=="2"){
 						 $(o).selectObjM(1,that.choseGuestsCb);//选择群发对象回调函数
 					}else if($(o).attr("hasevent") && $(o).attr("hasevent")=="4"){
 						 $(o).selectObjM(1,that.choseGuestsResrcCb);//选择租客来源回调函数
 					}else{
 						$(o).selectObjM();
 					}
 				});
 			},
       tabSwitch:function(){
         var that=this;
         $(".smart-msg-nav li",$$).off().on("click",function(){
             var cur=$(this);
             if(!cur.hasClass("current")){
               cur.parent().find(".current").removeClass("current");
               cur.addClass("current");
               var  index=cur.index();
               $(".box",$$).hide();
               switch (index) {
                 case 0:
                  that.getTemplates();//获取模板
                   break;
                 case 1:
                  that.showTab(index);
                   break;
                 case 2:
                  that.showTab(index);
                   break;
                 case 3:
                  that.getSendRecords();//获取列表数据
                   break;
                 case 4:
                  that.getChargeRecords();//获取列表数据
                   break;
                 default:
                   break;
               }
             }
         });
       },
       /*展示隐藏模板处理*/
       showTab:function(index){
         $(".box:eq("+index+")").show();
       },
       //获取模板列表
       getTemplates:function(){
         var that=this;
         var data=[];
         ajax.doAjax("POST","","",function(json){

         });
         var temp=template('sms-temp-render',data);
         $("#temp-list").html(temp);

         that.showTab(0);
         that.bindEditEvt();
        //  sms.showTemp();
       },
       //绑定智能短信编辑模板事件
       bindEditEvt:function(){
         var that=this;
          $("#list-fee-distri",$$).find(".edit-box").off().on("click",function(){
              var  cur=$(this),
                   par=cur.parents(".send-condetion-box");
                cur.find("span").text("保存").attr("data-status","save");
                par.find(".inactive").removeClass("inactive");
                that.iniSetselEvt();
          });
          //编辑短信模板
          $("#temp-list li").on("click",".edit-box",function(){
              var cur=$(this);
              var opts={
        				isAdd:0,//是否是添加模板
        				name:"租客交租",//模板名称
        				content:"模板内容",//模板内容
        				tempId:"100",//模板id
        				isDefault:0,//是否是默认模板
        				url:"",//请求路径
        				defaultName:"默认模板名称",//默认模板名称
        				defaultContent:"默认模板内容"//默认模板内容
        			}
              sms.showTemp(opts);
          });

          var delTemp='<div class="sms-del-wrap">'
              +'<p>确认删除该模板?</p>'
              +'<p class="del-btn-group">'
              + '<a href="javascript:;" class="btn btn4 cancle-over-trigger mr10">取消</a>'
              + '<a href="javascript:;" class="btn btn2" id="del-sms-tmp">确认</a>'
              +'</p>'
              +'</div>';
          //删除短信模板
          $("#temp-list li").on("click",".del-box",function(){
              var cur=$(this);
              $(".delStatusActive").removeClass("delStatusActive");
              cur.parents("li").addClass("delStatusActive");
              var d=dialog({
                 title:"删除模板",
                 content:delTemp,
                 	cancelValue: '取消',
                 cancel:function(){
                   $(".delStatusActive").removeClass("delStatusActive");
                 }
              });
              d.showModal();
              $(".ui-dialog-button").parent().parent().remove();
              that.closeConfirmWd(d);
              that.sendDelRequest(d);
          });

          //添加模板事件
          $("#temp-list").on("click",".li-spe",function(){
              var cur=$(this);
              var opts={
                isAdd:1,//是否是添加模板
                name:"租客交租",//模板名称
                content:"模板内容",//模板内容
                tempId:"100",//模板id
                isDefault:0,//是否是默认模板
                url:"",//请求路径
                defaultName:"默认模板名称",//默认模板名称
                defaultContent:"默认模板内容"//默认模板内容
              }
              sms.showTemp(opts);
          });
       },
       //关闭弹窗
       closeConfirmWd:function(d){
         $(".cancle-over-trigger").off().on("click",function(){
           if(XHR) XHR.abort();
            $(".delStatusActive").removeClass("delStatusActive");
            d.close().remove();
         });
       },
       //删除模板请求
       sendDelRequest:function(d){
         $("#del-sms-tmp").off().on("click",function(){
              var cur=$(this);
              if(!cur.hasClass("clicked")){
                cur.text("删除中...").addClass("clicked");
                XHR=ajax.doAjax("post","url","",function(json){
                    cur.text("确认").removeClass("clicked");
                    $(".delStatusActive").removeClass("delStatusActive");
                    if(json.status==1){
                        // d.close().remove();
                    }
                    var da=dialog({
                      title:"删除模板",
                      content:json.message
                    });
                    da.showModal();
                });
              }
         });
       },
       //获取短信发送记录
       getSendRecords:function(page){
          var that=this;
          if(!!!page) page=0;
          page+=1;
         that.showTab(3);
       },
       //获取充值记录
       getChargeRecords:function(page){
         var that=this;
         if(!!!page) page=0;
         page+=1;
         that.showTab(4);
       },

 			/*
 			 * @func 添加费用交互处理
 			 */
 			addFeesItem:function(){
 				var that=this;
 				$(".send-condetion-add",$$).off().on("click",function(){
 					var cur=$(this);
 					var dynamicTemp=that.genFeeItems();//动态生成费用名称模板
 					if(dynamicTemp!=""){
 						$("#temp-add-fee",$$).find(".triger-drag-down .selectByMO").html(dynamicTemp);
 					}
 					var temLen=$("#list-fee-distri",$$).find(".data-fee-li").length,
 						dataLen=CACHEFEENAMEUNIT.length;
 					if(temLen==dataLen){
 						cur.addClass("none");
 						return;
 					}else{
 						cur.removeClass("none");
 					}
 					var emtyTemp=$("#temp-add-fee",$$).html();
 					var  _li=document.createElement("div");
 					_li.className="send-condetion data-fee-li clearfix";
 					_li.innerHTML=emtyTemp;
 					cur.before(_li);
 					that.iniSetselEvt();
 					that.delFeeItem();
          that.addFeesItem();
          that.bindEditEvt();
 					//默认选中下拉框第一项选项,只触发刚添加的那条
          var _nextPar=cur.prev();
 					$.each(_nextPar.find(".selectByMO ul").find("li:eq(0)"),function(j,k){
 						$(k).trigger("click");
 					});
 					var s=that.genFeeItems("secFee");//每生成一次模板都要重新覆盖之前的
 					that.setFeeItemtemp(s);
          var temLen=$("#list-fee-distri",$$).find(".data-fee-li").length;
 					if(temLen==dataLen){
 						cur.addClass("none");
 						return;
 					}else{
 						cur.removeClass("none");
 					}
 				});
 			},/*
 			 *@func 遍历费用名称模板重新赋值
 			 * */
 			setFeeItemtemp:function(tp){
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
        var TEXTS=[
          "租客预约提醒","租客预定提醒","向业主交租提醒","租客交租提醒","租客合同到期提醒"
        ]
        $.each(CACHEFEENAMEUNIT,function(j,k){
            TEXTUNIT[k.id]=TEXTS[j];
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
 			 *
       */
 			chosePayNameCb:function(ev,obj){
 				var that=iaSms.prototype;
        var txt=that.getOnlyTemp(ev);
        $(obj).parents(".send-condetion-fm").find(".selectByM:eq(1)").find(".selectByMT").val(txt);
 				var s=that.genFeeItems();//每生成一次模板都要重新覆盖之前的
 				that.setFeeItemtemp(s);
 			},
 			/*
 			 *@func 获取消息发送对应模板
 			 *
       */
      getOnlyTemp:function(ev){
         return TEXTUNIT[ev];
      },
 			/*
 			 * @func 删除费用选项
 			 */
 			delFeeItem:function(){
 				var that=this;
 				$(".del-box",$$).off().on("click",function(){
 					var  cur=$(this),par=cur.parents(".data-fee-li");
 					par.remove();
 					var temLen=$("#list-fee-distri",$$).find(".data-fee-li").length,
 						dataLen=CACHEFEENAMEUNIT.length;
 					if(temLen==dataLen){
 						$(".send-condetion-add",$$).addClass("none");
 						return;
 					}else{
 						$(".send-condetion-add",$$).removeClass("none");
 					}
 					var s=that.genFeeItems();//每生成一次模板都要重新覆盖之前的
 					that.setFeeItemtemp(s);
 				});
 			}
   }
   new iaSms();
 }
  exports.inite=function(_html_,data){
     moduleInite(_html_,data);
   }
});
