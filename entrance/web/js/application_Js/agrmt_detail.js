/*
 *@func 合同详情页面数据交互
 * */
define(function(require,exports,module){
	var $ = require("jquery"),
	loading=require("loading"),
	template=require("artTemp"),
	dpk=require("calendar"),
		hash = require('child_data_hash'),
	 	 ajax=require("Ajax"),ajaxLoading;
	 	 dialog = require("dialog"); //弹窗插件
	 	 require("radio")($);
	 	 require("validForm")($);
		 require("combobox")($);
	var uplodify = require("uplodify");  //图片上传
	 var CACHEPAYWAYHZ=[],//缓存计费方式合租数据
		CACHEPAYWAYZZ=[],//缓存计费方式整租数据
		CACHEFEENAMEUNIT=[],//缓存费用名称数据
		CACHETEMPFEE={};
	 var moduleInte=function($$,data){
		function Agrmt(){
			this.init();
		}
		Agrmt.prototype={
			init:function(){
				var that=this;
				that.checkForm();
				that.addDptClient();
				that.addDptFee();
				that.iniRadio();
				that.setSeperateLine();
				that.checkRentLen();
				that.bindDelRenter();
				that.closeUserScore();//关闭用户评分信息展示事件绑定
//				that.checkFeeLen();//删除按钮显示/隐藏判断
				that.iniSetselEvt();
				that.setCachePayway();//缓存计费方式
				that.bindDelFee();//绑定删除费用按钮事件
				that.bind();
				var _form=$(".agrmtForm",$$);
				hash.hash.savehash('formValid',$(':first',$$));
//				md5Fom.inite(_form);
				that.cacheFormFileDta(_form);//缓存图片文件
				that.cacheFeeNames();
				that.bindCancle(_form);//绑定取消点击事件
				uplodify.uploadifyInits($('#file_upload_hz',$$),$("#js-uploaderArea-hz",$$),'*.gif; *.jpg; *.jpeg; *.png; *.doc; *.docx; *.pdf');
			},
			bindCancle:function(form){
				$(form).find(".btn-cancel").off().on("click",function(){
					var  bl=hash.hash.ischange('formValid',$(':first',$$));
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
             * @func 缓存计费方式数据
             */
			setCachePayway:function(){
				CACHEPAYWAYHZ=[];
				CACHEPAYWAYZZ=[];
				var _par=$(".emptyFeeTemp",$$).find(".cache-pay-way-data").find(".selectByMO ul li"),that=this,
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
					//配置整租只有前面两个选项，数据库有修改的话这里也需要对应做下修改
					var k=n.id;
					//配置整租只有前面两个选项，数据库有修改的话这里也需要对应做下修改
					if(k==1 || k==2 || k==5 || k==6 || k==7){
						CACHEPAYWAYZZ.push(n);
					}
				});
//				console.log(CACHEPAYWAYHZ);//合租计费方式数据
//				console.log(CACHEPAYWAYZZ);//整租计费方式数据
				that.renderCacheWayJson();//重新渲染添加计费方式的基本模板
			},
			/*
			 *@func 重新渲染添加计费方式的基本模板
			 * */
			renderCacheWayJson:function(cb){
				var type=$(".emptyFeeTemp",$$).attr("data-type"),that=this;
				if(type==2){
					cacheData=CACHEPAYWAYZZ;
				}else{
					cacheData=CACHEPAYWAYHZ;
				}
				var temp='';
				$.each(cacheData,function(i,o){
					temp+='<li selectval="'+o.id+'">'+o.text+'</li>';
				});
				$(".emptyFeeTemp",$$).find(".cache-pay-way-data").find(".selectByMO ul").html("").html(temp);
				if(cb && cb!=undefined){
					var len=$("#dpt-fee-list",$$).find(".dataForm").length;
					if(len>0){
						var _ele=$("#dpt-fee-list",$$).find(".dataForm").find(".cache-pay-way-data").find(".selectByMO ul");
						$.each(_ele,function(j,item){
							$(item).html("").html(temp);
						});
						that.iniSetselEvt();
						$.each(_ele,function(i,o){
							$(o).find("li:eq(0)").addClass("selectedLi");
							var _txt=$(o).find("li.selectedLi").text(),
							_seVal=$(o).find("li.selectedLi").attr("selectval");
							$(o).find("li.selectedLi").parent().parent().prev().prev().val(_txt).attr("selectval",_seVal);
							that.chosePaywayCb(_seVal,$(o).find("li.selectedLi").parent().parent().prev().prev());
						});
					}
				}
			},
			/*
			 *@func 租客条数显示/隐藏 删除
			 * */
			checkRentLen:function(){
				var  len=$("#cutm-list",$$).find(".dataForm").length;
				if(len==1){
					$("#cutm-list",$$).find(".dataForm").find(".del-custmer").hide();
				}else{
					$("#cutm-list",$$).find(".dataForm").find(".del-custmer").show();
				}
			},
			/*
			 *@func 绑定删除租客信息事件
			 * */
			bindDelRenter:function(){
				var that=this;
				$(".del-custmer",$$).off().on("click",function(){
					var  cur=$(this);
					cur.parent().remove();
					that.setSeperateLine();
					that.checkRentLen();
				});
			},
			/*
			 *@func:循环多条租客信息 最后条数据去掉下划线
			 * */
			setSeperateLine:function(){
				var _ele=$("#cutm-list",$$).find(".dataForm"),
					_len=_ele.length;
				$("#cutm-list",$$).find(".dataForm:eq("+(_len-1)+")").addClass("lst-child");
			},
			/*
			 *@func:单选框事件绑定
			 * */
			iniRadio:function(){
				// $.each($(".radio",$$),function(i,o){
				// 	$(o).click(function(){
				// 		$(this).Radios();
				// 	})
				// });
				$(".radio-box").off("click").on("click",function(){
					var obj = $(this).find("label");
					obj.addClass("checked").parent().siblings(".radio-box").find("label").removeClass("checked");
					obj.find(".r-default").hide().parents(".radio-box").siblings(".radio-box").find(".r-default").show();
					obj.find(".r-select").show().parents(".radio-box").siblings(".radio-box").find(".r-select").hide();
					obj.siblings("input").attr("checked",true).parent().siblings(".radio-box").find("input").attr("checked",false);

				});
			},


			/*
			 *@func:事件绑定
			 * */
			bind:function(){
				var that=this;
         	var _p=$(".agrmtForm",$$);
         		len1=_p.find(".dpt-house-ya").parent().find(".selectByMO ul").find(".selectedLi").length,
         		len2=_p.find(".dpt-house-pay").parent().find(".selectByMO ul").find(".selectedLi").length;
         		if(len1>0){
         			_p.find(".dpt-house-ya").parent().find(".selectByMO ul").find(".selectedLi").trigger("click");
         		}else{
         			_p.find(".dpt-house-ya").parent().find(".selectByMO ul").find("li:eq(0)").trigger("click");
         		}
         		if(len2>0){
         			_p.find(".dpt-house-pay").parent().find(".selectByMO ul").find(".selectedLi").trigger("click");
         		}else{
         			_p.find(".dpt-house-pay").parent().find(".selectByMO ul").find("li:eq(0)").trigger("click");
         		}
         		var _ee=_p.find(".dataForm").find("input[name='from[]']");
         		$.each(_ee,function(i,o){
         			var len=$(o).parent().find(".selectByMO ul").find(".selectedLi").length;
         			if(len>0){
         				$(o).parent().find(".selectByMO ul").find(".selectedLi").trigger("click");
	         		}else{
	         			$(o).parent().find(".selectByMO ul").find("li:eq(0)").trigger("click");
         			}
         		});
       		  $('#houses',$$).each(function(){
	            var input = $(this);
	            var dom = input.parent('.jzf-col-r');
	            input.keyup(function(){
	            	$(this).removeAttr("record_id");
	            });
            	input.combobox({
	                url:input.attr("actionurl"),
	                param_name: 'search',
	                title_name: 'name',
	                commbox_selector: '.commbox',
	                width: 498,
	                result:"room",
	                item_template:'<span record_id=":record_id"  hourse_type=":house_type"  area_id=":house_id">:house_name</span>',
	                //height: 200,
	                min_char:1,
	                padding: 10,
	                offset: { top: 32},
	                callback: {
	                    init:function(obj){},
	                    select: function(obj) {

	                    },
	                    choose: function(obj) {
	                    		if(obj && obj!=undefined){
	                    		$("#houses",dom).val(obj.house_name)
	                    						.attr("area_id",obj.house_id)
	                    						.attr("house_type",obj.house_type)
	                    						.attr("record_id",obj.record_id)
	                    						.attr("rental_way",obj.rental_way)
	                    						.removeAttr("style");
	                    			that.setSelVal(obj);
	                    		}
	                    },
		                notchoose: function(obj){
		                },
		                notdata: function(obj){
		                		var vv=$.trim($(obj).val());
		                		if(vv==""){
		                			$(".emptyFeeTemp",$$).attr("data-type",1);
		                			that.renderCacheWayJson(1);
		                			var  par=$(".agrmtForm",$$);
									//房屋租金
								par.find("input[name='rent'").val("");
								par.find("input[name='deposit']").val("");
								$("#dpt-fee-list").prev().hide();
								$("#dpt-fee-list").html("").hide();
		                			//没有数据清空对应字段
		               	 		$(obj).removeAttr("rental_way").removeAttr("area_id").removeAttr("house_type").removeAttr("style");
		                		}else{
								$("#dpt-fee-list").prev().hide();
								$("#dpt-fee-list").html("").hide();
		                		}
		               	 	that.emptySetSelVal();
		                }
		                }
		          });
	        });
				//终止合同
			 $(".agmt-end",$$).off().on("click",function(){
					var  cur=$(this),
						 data={
						 	url:cur.attr("data-url"),
						 	id:cur.attr("data-contract"),
						 	jump:cur.attr("data-jump-url")
						 };
					var d = dialog({
						title: '提示信息',
						content:'<div class="agmt-end-btns">'
								+'<p>确认终止该合同?</p>'
								+'<a href="javascript:;" class="btn btn4 ml10 close-agmt-mask">取消</a>'
								+'<a href="javascript:;" class="btn btn2" id="end-agmt">确认</a>'
								+'</div>'
					});
						d.showModal();
						that.endAgmt(data,d);
				});
				//收费
				 $(".get-fee-trigger",$$).off().on("click",function(){
					var  cur=$(this),
						 data={
						 	url:cur.attr("data-url")
						 };
						 if(!cur.hasClass("clicked")){
						 	cur.addClass("clicked");
							 ajax.doAjax("get",data.url,"",function(json){
							 	 cur.removeClass("clicked");
							 	 if(json.status==1){
//							 	 	cur.remove();
							 	 	 window.location.href="#"+json.jumpurl;
							 	 }else{
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
						 }
				});
				//发送短信
				$(".agrmtForm",$$).find(".notice-msg").off().on("click",function(){
					var  cur=$(this),aurl=cur.attr("actionurl"),
					isSent=cur.attr("isSent");
					if(isSent!=1){
					if(!cur.hasClass("clicked")){
						cur.parent().find(".clicked").removeClass("clicked");
						cur.addClass("clicked");
						ajax.doAjax("get",aurl,"",function(json){
							cur.removeClass("clicked");
							if(json.status==1){
								cur.attr("isSent",1);//标记发送状态
							}
							var da=dialog({
								title:"提示",
								content:json.message,
								okValue:"确定",
								ok:function(){
									da.close().remove();
								}
							});
							da.showModal();
						});
					}
					}else{
						var da=dialog({
							title:"提示",
							content:"通知已发送",
							okValue:"确定",
							ok:function(){
								da.close().remove();
							}
						});
						da.showModal();
					}

				});
			},
			endAgmt:function(data,d){
				$(".close-agmt-mask").off().on("click",function(){
					if(ajaxLoading) ajaxLoading.abort();
					d.close().remove();
				});
				$("#end-agmt").off().on("click",function(){
					 var  cur=$(this);
					 if(!cur.hasClass("clicked")){
					 	cur.parent().find(".clicked").removeClass("clicked");
					 	cur.addClass("clicked").text("保存中...");
					 	ajaxLoading=ajax.doAjax("get",data.url,"",function(json){
					 		cur.removeClass("clicked").text("保存");
					 		var  da=dialog({
								title: '提示',
								content:json.message
							});
							da.showModal();
							setTimeout(function(){
								da.close().remove();
								$(".close-agmt-mask").trigger("click");
							},1200);
							if(json.status==1){
								$(".agmt-end").remove();
								window.location.href="#"+data.jump;
							}
					 	});
					 }
				});
			},
			/*
			 * @func:选中房源赋值对应关联字段
			 */
			setSelVal:function(obj){
				var  par=$(".agrmtForm",$$),that=this;
//				console.log(obj);//2-整租 1-合租
				if(obj.feeabout && obj.feeabout!=undefined && obj.feeabout.length>0){
					var ftemp=template("feeListTemp",obj);
					$("#dpt-fee-list").prev().show();
					$("#dpt-fee-list").html(ftemp).show();
					that.iniSetselEvt();
				}else{
					$("#dpt-fee-list").prev().hide();
					$("#dpt-fee-list").html("").hide();
				}
				//房屋租金
				par.find("input[name='rent']").val(obj.money).trigger("blur");
				//押/付赋值
				var _target01=par.find(".dpt-house-ya").parent().find("ul").find("li"),
					_target02=par.find(".dpt-house-pay").parent().find("ul").find("li");
				$.each(_target01,function(i,o){
					if(obj.detain==$(o).attr("selectval")){
						$(o).trigger("click");
					}
				});
				$.each(_target02,function(i,o){
					if(obj.pay==$(o).attr("selectval")){
						$(o).trigger("click");
					}
				});
				var payM=obj.money*obj.detain;
				par.find("input[name='deposit']").val(payM);
				//重新渲染计费方式模板
				var type=obj.rental_way,
					_etype=$(".emptyFeeTemp",$$).attr("data-type");
				if(type && type!="" && type!=_etype){
					$(".emptyFeeTemp",$$).attr("data-type",type);
					that.renderCacheWayJson(1);//重新渲染
				}
			},
			/*
			 * @func:清空选中房源赋值对应值
			 */
			emptySetSelVal:function(){
				var  par=$(".agrmtForm",$$);
				//房屋租金
				par.find("input[name='rent'").val("");
				//押/付
				par.find(".dpt-house-ya").val("零").attr("selectval","0");
				par.find(".dpt-house-ya").parent().find(".selectByMO").find("li[selectval=0]").addClass("selectedLi").siblings().removeClass("selectedLi");
				par.find(".dpt-house-pay").val("一").attr("selectval","0");
				par.find(".dpt-house-pay").parent().find(".selectByMO").find("li[selectval=1]").addClass("selectedLi").siblings().removeClass("selectedLi");
			},
			/*
			 * @func:添加租客
			 */
			addDptClient:function(){
				var that=this;
				$("#dpt-add-client",$$).off().on("click",function(){
					var cur=$(this),
						_par=cur.parent();
					$(".emptyTemp",$$).find("input").trigger("focus");
					var emtyTemp=$(".emptyTemp",$$).html().replace("{cls}","dataForm");

					$("#cutm-list",$$).find("#dpt-add-client").before(emtyTemp);
					_par.find(".lst-child").removeClass("lst-child");
					var len=_par.find(".dpt-custm-item").length;
					_par.find(".dpt-custm-item:eq("+(len-1)+")").addClass("lst-child");
					that.checkForm();//初始验证
					that.iniRadio();//单选框初始
					that.setSeperateLine();
					that.bindDelRenter();
					that.checkRentLen();
					that.closeUserScore();
					that.iniSetselEvt();
					$.each(cur.prev().find(".selectByMO ul").find("li:eq(0)"),function(j,k){
						$(k).trigger("click");
					});
				});
			},

			/*
			 * @func:添加费用
			 */
			addDptFee:function(){
				var that=this;
				$("#dpt-add-fee",$$).off().on("click",function(){
					var cur=$(this),
						_par=cur.parent();
					$(".emptyFeeTemp",$$).find("input").trigger("focus");
					var dynamicTemp=that.genFeeItems();//动态生成费用名称模板
					if(dynamicTemp!=""){
						$(".emptyFeeTemp",$$).find(".triger-drag-down .selectByMO").html(dynamicTemp);
					}
					var temLen=$("#dpt-fee-list",$$).find(".dataForm").length,
						dataLen=CACHEFEENAMEUNIT.length;
					if(temLen==dataLen){
						cur.addClass("none");
					}else{
						cur.removeClass("none");
					}
					var emtyTemp=$(".emptyFeeTemp",$$).html().replace("{cls}","dataForm");
					$("#dpt-fee-list",$$).find("#dpt-add-fee").before(emtyTemp);
					that.checkForm();//初始验证
					that.setFeeSeperateLine();
					that.bindDelFee();
//					that.checkFeeLen();
					that.iniSetselEvt();//下拉事件绑定
					//默认选中下拉框第一项选项,只触发刚添加的那条
					$.each(cur.prev().find(".selectByMO ul").find("li:eq(0)"),function(j,k){
						$(k).trigger("click");
					});

					var s=that.genFeeItems("secFee");//每生成一次模板都要重新覆盖之前的
					that.setFeeItemtemp(s);
				});
			},
			/*
			 *@func 遍历费用名称模板重新赋值
			 * */
			setFeeItemtemp:function(tp){
//				console.log(tp);
				var that=this,
					_par=$("#dpt-fee-list",$$).find(".triger-drag-down");
				$.each(_par,function(j,k){
					$(k).find(".selectByMO").html("").html(tp);
				});
				var _ele=$("#dpt-fee-list",$$).find(".triger-drag-down ul");
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
				var _par=$(".emptyFeeTemp",$$).find(".triger-drag-down").find(".selectByMO ul li"),that=this,
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
//				console.log(CACHEFEENAMEUNIT);//计费方式数据
			},

			/*
			 *@func 生成添加的费用模板的费用名称模板
			 * */

			genFeeItems:function(){
				var json={},_gen="";
				var _newCache=[],_dynamicJson={};
				var len=$("#dpt-fee-list",$$).find(".dataForm").length;
				 if(len>0){
				 	var _par=$("#dpt-fee-list",$$).find(".triger-drag-down").find(".selectByMT");
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
				 	_gen=template("fee-dynamic-temp",json);
				 }
				 return _gen;
			},
			/*
			 *@func 费用名称回调
			 * */
			chosePayNameCb:function(){
				var that=Agrmt.prototype;
				var s=that.genFeeItems();//每生成一次模板都要重新覆盖之前的
				that.setFeeItemtemp(s);
			},
			/*
			 *@func 下拉事件绑定
			 * */
			iniSetselEvt:function(){
				var par=$(".selectByM",$$),that=this;
				$.each(par,function(i,o){
					if($(o).attr("hasevent") && $(o).attr("hasevent")==2){
						 $(o).selectObjM(1,that.chosePaywayCb);//计费方式回调
					}else if($(o).attr("hasevent") && $(o).attr("hasevent")=="3"){
						 $(o).selectObjM(1,that.chosePayNameCb);//费用名称回调
					}else{
						$(o).selectObjM();
					}
				});
				$(".wdate").click(function(){
					dpk.inite();
				});
				var minDateCache=null;
				$(".maxWdate",$$).click(function(){
					  var cur=$(this),minDate=cur.val(),maxDate=cur.attr("max_date");
						if(minDateCache==null) minDateCache=minDate;
					 dpk.inite({
						 	dateFmt:'yyyy-MM-dd',
							minDate:minDateCache,
							maxDate:maxDate
						});
				});
			},
			/*
			 *@func 选择计费方式回调
			 * */
			chosePaywayCb:function(cc,obj){
				var _el=obj.parents(".dataForm").find(".data-cb-opts");
				if(cc==3 || cc==4 || cc==5){
					_el.show();
					_el.find("input").removeAttr("ignore");
					_el.find(".check-error").show();
					$.each(_el.find("input"),function(m,n){
						$(n).attr("name",$(n).attr("data-name"));
					});
				}else{
					_el.find("input").removeClass("Validform_error").attr("ignore","ignore");
					_el.find(".check-error").text("").hide();;
					_el.hide();
					_el.find("input").removeAttr("name");
				}
			},
			/*
			 *@func 费用条数显示/隐藏 删除
			 * */
			checkFeeLen:function(){
				var  len=$("#dpt-fee-list",$$).find(".dataForm").length;
				if(len==1){
					$("#dpt-fee-list",$$).find(".dataForm").find(".del-fee-trigger").hide();
				}else{
					$("#dpt-fee-list",$$).find(".dataForm").find(".del-fee-trigger").show();
				}
			},
			/*
			 *@func 绑定删除租客信息事件
			 * */
			bindDelFee:function(){
				var that=this;
				$(".del-fee-trigger",$$).off().on("click",function(){
					var  cur=$(this);
					cur.parent().remove();
					that.setFeeSeperateLine();
					var temLen=$("#dpt-fee-list",$$).find(".dataForm").length,
						dataLen=CACHEFEENAMEUNIT.length;
					if(temLen==dataLen){
						cur.addClass("none");
					}else{
						cur.removeClass("none");
					}
					var s=that.genFeeItems();//每生成一次模板都要重新覆盖之前的
					that.setFeeItemtemp(s);
//					that.checkFeeLen();
				});
			},
			/*
			 *@func:循环多条租客信息 最后条数据去掉下划线
			 * */
			setFeeSeperateLine:function(){
				var _ele=$("#dpt-fee-list",$$).find(".dataForm"),
					_len=_ele.length;
					$("#dpt-fee-list",$$).find(".lst-child").removeClass("lst-child");
				$("#dpt-fee-list",$$).find(".dataForm:eq("+(_len-1)+")").addClass("lst-child");
			},

			/*
			 *@func 获取提交参数收集
			 * */
			getParamData:function(form){
				var _ele=$(form).find(".dataForm"),
					_txts=_ele.find("input[type='text']"),
					_radios=$(form).find(".dpt-cutm-list").find(".dataForm"),
					_textarea=_ele.find("textarea"),
					_photos = _ele.find(".upload-imgview"),
					_paramTxt="",_paramArea="",_paramRadio="",_params="",_paramAuto="",_paramPhoto=[];
				 //textara
				 $.each(_textarea,function(i,o){
				 	var name=o.getAttribute("name");
				 	if(name){
				 		if(i==0){
				 			_paramArea+=""+name+"="+o.value;
				 		}else{
				 			_paramArea+="&"+name+"="+o.value;
				 		}
				 	}
				 });
				 //photo
				 var check_img = true;
				 _photos.each(function(i,o){
				 	var img= $(this).attr("filename");
				 	if(img == ""){
				 		var d = dialog({
							title: '提示信息',
							content:'请等待图片上传'
						});
						d.showModal();
						  setTimeout(function(){
							d.close().remove();
						},1500);
						check_img = false;
						return false;
				 	}
				 	_paramPhoto.push(img);
				 });
				 if(check_img == false) return false;
//				 console.log(_paramArea);
				 //radio
				 $.each(_radios,function(i,o){
				 	var rdo=$(o).find("input[name='gender[]']"),_s="";
				 	$.each(rdo,function(j,item){
				 		var sdt="";
//				 		console.log(item.getAttribute("checked"));
				 		if(item.getAttribute("checked") == 'checked'){
				 			var name=item.getAttribute("name");
				 			if(j==0){
						 		sdt+=""+name+"="+item.value;
							}else{
						 		sdt+="&"+""+name+"="+item.value;
							}
				 		}
				 		_s+=sdt;
				 	});
				 	if(i==0){
						_paramRadio+=_s;
				 	}else{
						_paramRadio+="&"+_s;
				 	}
				 });
				 //text
				 $.each(_txts,function(i,o){
				 	 var name=o.getAttribute("name");
				 	 if(name && name!=undefined){
				 	 	if(name!="housename"){
					 		if(i==0){
					 			_paramTxt+=""+name+"="+o.value;
					 		}else{
					 			if(name =="detain" || name =="pay" || name == "fee_name[]" || name == "fee_type[]"|| name == "from[]"){
					 				_paramTxt+="&"+name+"="+$(o).attr("selectval");
					 			}else{
					 				_paramTxt+="&"+name+"="+o.value;
					 			}
					 		}
				 	 	}else{
				 	 		 var area_id=o.getAttribute("area_id"),
				 	 		 	 record_id=o.getAttribute("record_id"),
				 	 		 	 rental_way=o.getAttribute("rental_way"),
				 	 		 	 house_type=o.getAttribute("house_type");
				 	 		_paramAuto="&housename="+o.value+"&house_id="+area_id+"&house_type="+house_type+"&record_id="+record_id+"&rental_way="+rental_way;
				 	 	}
				 	 }
				 });
				var isXz=$(form).find(".subArmtForm").attr("data-xz");
            		if(isXz && isXz== "relet"){
            			_paramAuto="&house_id="+$(".rel-parm-hid").val()+"&room_id="+$(".rel-parm-rid").val()+"&house_type="+$(".rel-parm-htyp").val();
            		}
            		var isReserve = $(form).find(".subArmtForm").attr("reserveurl");
            		if(typeof(isReserve) != "undefined"){
            			_paramAuto="&house_id="+$("input[name='house_id']",$$).val()+"&room_id="+$("input[name='room_id']",$$).val()+"&house_type="+$("input[name='house_type']",$$).val()+"&reserve_id="+$("input[name='reserve_id']",$$).val();
            		}
            		var contract_id = document.URL.split("&contract_id=")[1];
				_params=_paramTxt+"&"+_paramRadio+"&"+_paramArea+"&contract_id="+contract_id+"&photolist="+_paramPhoto+_paramAuto;
//          		console.log(_params);
				return _params;
			},
			/*
			 *@func：表单提交
			 * */
			submitForm : function(form){
				var that=this;
				var type = "post",
					url=form.attr("actionUrl"),
					data=that.getParamData(form);
					var _ele=$("#editContract",$$),
					isXz=$(form).find(".subArmtForm").attr("data-xz"),
					isReserve = $(form).find(".subArmtForm").attr("reserveurl");
            		if(_ele.length > 0){
					url=$(form).find(".subArmtForm").attr("editurl");
	                }
            		if(isXz && isXz== "relet"){
            			url=$(form).find(".subArmtForm").attr("xzurl");
            		}
            		if(typeof(isReserve) != "undefined"){
            			url = isReserve;
            		}
            		var isStr=data.substr(0,1);
            		if(isStr == "&"){
            			data=data.replace(isStr,"");
            		}
            		if($(".subArmtForm",$$).hasClass("stop-click")) return false;
            		$(".subArmtForm",$$).addClass("stop-click");
				ajax.doAjax(type,url,data,that.callback);
			},
			/*
			 *@func：表单提交成功回调函数
			 * */
			callback : function(data){
				$(".subArmtForm",$$).removeClass("stop-click");
				var isReserve = $(".agrmtForm",$$).find(".subArmtForm").attr("reserveurl"),
					_btn = $(".agrmtForm",$$).find(".subArmtForm");
				_btn.removeAttr("request");
				if(data.status==0){
					_btn.attr("request",Math.floor(Math.random()*1000));
				}
				if(data.status == 1){
					var d = dialog({
						title: '提示信息',
						content:data.message,
						okValue: '确定',
						ok: function () {
							d.close().remove();
								//关闭当前标签
							var tag = WindowTag.getCurrentTag();
							if(typeof data['tag'] == 'string'){
			    				var ctag = WindowTag.getTagByUrlHash(data['tag']);
			    				if(ctag){
			    					window.WindowTag.selectTag(ctag.find(' > a:first').attr('url'));
			    					window.WindowTag.loadTag(ctag.find(' > a:first').attr('href'),'get',function(){});
			    				}
			    				}
							if(typeof data['url'] == 'string'){
			    				window.WindowTag.openTag('#'+data.url);
			    				}
							if(typeof data['refresh_url'] == 'string'){
								var ctag_Auto = WindowTag.getTagByUrlHash(data['refresh_url']);
								if(ctag_Auto){
									window.WindowTag.loadTag(ctag_Auto.find(' > a:first').attr('href'),'get',function(){});
								}
							}
							if(typeof data['contract_url'] == 'string'){
								var ctag_Auto = WindowTag.getTagByUrlHash(data['contract_url']);
								if(ctag_Auto){
									window.WindowTag.loadTag(ctag_Auto.find(' > a:first').attr('href'),'get',function(){});
								}
							}
							WindowTag.closeTag(tag.find('>a:first').attr('url'));
						}
					});
					d.showModal();
				}else{
					var d = dialog({
						title: '提示信息',
						content:data.message,
						okValue: '确定',
						ok: function () {
							d.close();
						}
					});
				}
				d.showModal();
//				setTimeout(function(){
//					d.close().remove();
//				},2000);
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
			 *@func:初始化表单验证
			 * */
			checkForm : function(){
				var that=this;
				$(".agrmtForm",$$).Validform({
					btnSubmit : ".subArmtForm",
					showAllError : true,
					tiptype : function(msg,o,cssctl){
		                var objtip=o.obj.parents(".jzf-col-r").find(".check-error");
		                cssctl(objtip,o.type);
		                objtip.text(msg);
		           },
		            beforeCheck:function(){
		            		$("input,textarea",$$).trigger("blur");
		            },
		            datatype : {
		              "floatGroup":function(gets,obj,curform,regxp) {
		                    var reg1=/^([1-9][0-9]*)|0$/,//匹配正整数
		                   	   reg2=/^[0-9]+(.[0-9]{1,5})?$/;//匹配正负浮点数
			                   if($.trim(gets)=="") return false;
			                   else if(reg1.test(gets)==true){
			                   		return true;
			                   }else if(gets.indexOf(".")!='-1'){
			                   		if(reg2.test(gets)) return true;
			                   		else return false;
			                   }else{
			                   	return false;
			                   }
		               },
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
		               "housename":function(gets,obj,curform,regxp){
		               		if(typeof obj.attr("record_id") == "undefined"){
		                    		return "房源不存在，请先添加";
		                    	}
		               },
		                "checkstart":function(gets,obj,curform,regxp){
						              	  var endtime = $(obj).parents(".col-box").siblings(".col-box").find("input[name='dead_line']").val();
						              	  if(endtime != ""){
						              	  	endtime = changetodate(endtime);
						              	  	gets =  changetodate(gets);
						              	  	if(gets > endtime){return false;}
						              	  	else{
						              	  		$(obj).parents(".col-box").siblings(".col-box").find("input[name='dead_line']").removeClass("Validform_error").siblings(".check-error-auto").removeAttr("style");
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
						              	 var starttime = $(obj).parents(".col-box").siblings(".col-box").find("input[name='signing_time']").val();
						              	 var today = gettoday();
						              	 if(starttime == ""){
						              	 	starttime = today;
						              	 	$(obj).parents(".col-box").siblings(".col-box").find("input[name='signing_time']").val(today);
						              	 }
						              	 starttime = changetodate(starttime);
						              	 gets = changetodate(gets);
						              	 if(starttime > gets) {return false;}
						              	 else{
						              	 	$(obj).parents(".col-box").siblings(".col-box").find("input[name='signing_time']").removeClass("Validform_error").siblings(".check-error-auto").removeAttr("style");
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
						              },
						              "checkgender":function(gets,obj,curform,regxp){
						              	var neibuger = $(obj).parent(".radio-box").siblings(".radio-box").find("input");
						              	console.log(gets);
						              	if(obj.attr("checked") || neibuger.attr("checked")){
						              		return true;
						              	}else{
						              		return false;
						              	}
						              }
		            },
		            callback : function(form){
		            		//判断当前页面
		            		var _ele=$("#editContract",$$);
		            		if(_ele && _ele.val()!=""){
	            					var bl=hash.hash.ischange('formValid',$(':first',$$));
	            					var bl1=that.differCacheFile(form);
	            					if(bl==true || bl1==false){
	            						that.beforeSubFom(form);
	            					}else{
	            						that.stayInCurentPage(form);
//	            						var tag = WindowTag.getCurrentTag();
//									WindowTag.closeTag(tag.find('>a:first').attr('url'));
	            					}
		            		}else{
			            		var _hasAid=$(form).find("#houses").attr("area_id"),
			            			_hasHst=$(form).find("#houses").attr("house_type");
			            		if(_hasAid!="" && _hasAid!=undefined && _hasHst!="" && _hasHst!=undefined ){
			            			var bl=hash.hash.ischange('formValid',$(':first',$$));
	            					var bl1=that.differCacheFile(form);
	            					if(bl==true || bl1==false){
	            						 that.beforeSubFom(form);
	            					}else{
	            						that.stayInCurentPage(form);
	            					}
					            	return false;
			            		}else{
			            			$(form).find("#houses").addClass("Validform_error").next().text("请输入正确的房源名称").removeClass("Validform_right").addClass("Validform_wrong").show();
			            			return false;
			            		}
		            		}
		            }
				});
				var _inputs=$(".agrmtForm",$$).find("input[type='text']");
				$.each(_inputs,function(i,o){
					$(o).focus(function(){
						var cur=$(this);
						if(cur.hasClass("Validform_error")){
//							$(this).removeClass("Validform_error");
							cur.css("background","none");
							cur.parent().find(".check-error").hide();
						}
						if(cur.attr("name") == "idcard[]"){
							cur.parent().find(".tip-black-list").hide();
						}
					}).blur(function(){
						var cur=$(this);
						cur.removeAttr("style");
						cur.parent().find(".check-error").removeAttr("style");
						if(cur.attr("name") == "idcard[]"){
							var _v=$.trim(cur.val()),
							nxt=cur.next();
							if(_v!="" && !nxt.hasClass("Validform_wrong")){
								that.isUserBlackList(cur);//根据身份证获取用户全部信息
							}else{
								cur.removeClass("isRequesting");
								cur.parent().find(".tip-black-list").find(".tip-msg .red").text(0);
								cur.parent().find(".tip-black-list").hide();
							}
						}
					});
				});
			},
			/*
			 *@func 提交前的验证
			 * */
			beforeSubFom:function(form){
				var that=Agrmt.prototype,
					_fm=$(".agrmtForm",$$);
				var sDate=_fm.find("input[name='signing_time']").val(),
					eDate=_fm.find("input[name='dead_line']").val(),
					stimeStap=that.DateToUnix(sDate),
					etimeStap=that.DateToUnix(eDate);
				if(stimeStap>etimeStap){
					var da=dialog({
						title:"提示",
						content:"开始日期不能大于结束日期",
						okValue:"确定",
						ok:function(){
							da.close().remove();
						}
					});
					da.showModal();
					// setTimeout(function(){
					// 	da.close().remove();
					// },1200);
				}else{
					var bl=false;
					var _el=$("#cutm-list",$$).find(".dpt-custm-item:eq(0)").find(".jzf-col:eq(0)").find("input[name='gender[]']");
					$.each(_el,function(i,o){
						if(o.getAttribute("checked")=="checked" || o.getAttribute("checked")==true){
							bl=true;
							return;
						}
					});
					if(bl==true){
						that.submitForm(form);
					}else{
						var da=dialog({
							title:"提示",
							content:"请选择第一位租客的性别",
							okValue:"确定",
							ok:function(){
								da.close().remove();
							}
						});
						da.showModal();
					}
				}
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
			*@func 用户评分展示
			*/
			isUserBlackList:function(cur){
				var url=cur.attr("data-score-action"),that=this,
				data={idcard:cur.val()};
				if(!cur.hasClass("isRequesting")){
					cur.addClass("isRequesting");
					ajax.doAjax("post",url,data,function(json){
						cur.removeClass("isRequesting");
						if(json.status==1){
							cur.attr("data-tenant_id",json.tdata.tenant_id);
							cur.attr("data-avgscore",json.avgscore.avgscore);
							var score=json.avgscore.avgscore,
							allComment=json.avgscore.allComment,
							form=cur.parents(".dataForm");
							that.renderTargetUser(form,json.tdata);
							if(allComment > 0){
								cur.parent().find(".tip-black-list").find(".tip-msg .red").text(score);
								cur.parent().find(".tip-black-list").show();
							}
						}else{
							cur.parent().find(".tip-black-list").hide();
						}
					});
				}
			},
			/*
			*@func 渲染指定用户信息
			*/
			renderTargetUser:function(form,json){
				var dt={
					name:json.name,
					phone:json.phone,
					gender:json.gender
				};
				form.find("input[name='name[]']").val(dt.name).focus().blur();
				form.find("input[name='phone[]']").val(dt.phone).focus().blur();
				var _sex=form.find("input[type='radio']");
				$.each(_sex,function(i,o){
					var sv=o.value;
					if(dt.gender==sv){
						$(o).prev().trigger("click");
					}
				});
			},
			/*
			*@func 关闭用户信息展示事件绑定
			*/
			closeUserScore:function(){
				$(".close-user-score-triger",$$).off().on("click",function(){
					$(this).parent().parent().hide();
				});
				$(".tip-black-list .tip-msg a").off().on("click",function(){
					var cur=$(this),
					durl=cur.parents(".tip-black-list").prev().prev().attr("data-info-action"),
					avgscore=cur.parents(".tip-black-list").prev().prev().attr("data-avgscore"),
					tenant_id=cur.parents(".tip-black-list").prev().prev().attr("data-tenant_id");
					if(!cur.hasClass("clicked")){
						cur.parent().find(".clicked").removeClass("clicked");
						cur.addClass("clicked");
						ajax.doAjax("post",durl,data,function(json){
							cur.removeClass("clicked");
							window.location.href="#"+durl+"&tid="+tenant_id+"&avgscore="+avgscore;
						});
					}

				});
			}

		};
		new Agrmt();//初始化
	 };
	 exports.inite=function(_html_,data){
		moduleInte(_html_,data);
	}
});
