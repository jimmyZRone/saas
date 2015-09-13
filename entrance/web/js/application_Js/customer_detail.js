define(function(require,exports,module){
	var $ = require("jquery"),
	loading=require("loading"),
	template=require("artTemp"),
		hash=require("child_data_hash"),
	 	 ajax=require("Ajax"),ajaxLoading;
	 	 dialog = require("dialog"); //弹窗插件
	 	 require("radio")($);
	 	 require("validForm")($);
		 var d;
	var customerModule=function($$){
		function customer(){
			this.init();
		}
		customer.prototype={
		init:function(){
			var that=this;
			that.bind();
			that.checkForm();
			that.judgeShowCover();//初始化判断是否读取用户评分记录
			var _form=$(".cutm-form",$$);
//			md5Fom.inite(_form);
			hash.hash.savehash("cutm-form",$(":first",$$));
			$(".btn-cancel",$$).off("click").on("click",function(){
				if(hash.hash.ischange('cutm-form',$(':first',$$)) == true){
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
			that.cacheFormFileDta(_form);
			that.bindSel();
		},
			/*
			 *@func 下拉事件绑定
			 * */
			bindSel:function(){
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
				var _p=$(".cutm-form",$$);
         		len1=_p.find(".selectByMT").parent().find(".selectByMO ul").find(".selectedLi").length;
         		if(len1>0){
         			_p.find(".selectByMT").parent().find(".selectByMO ul").find(".selectedLi").trigger("click");
         		}else{
         			_p.find(".selectByMT").parent().find(".selectByMO ul").find("li:eq(0)").trigger("click");
         		}
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
					  console.log($(o).data("inited"));
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
		*@func 判断页面入口
		*/
		judgeShowCover:function(){
			var type=$(".show-custm-trig",$$).attr("type"),that=this;
			if(type!=undefined && type=="jumpFromCheck"){
				that.listCommtRecords();
			}
		},
		/*
		*@func 页码跳转事件绑定跳转
		*/
		jumpPage:function(totlaPage){
			var that=this;
			$(".get-record-trigger").off().on("click",function(){
				var cur=$(this),c=$.trim(cur.prev().val());
				that.getRecords(c,totlaPage);
			});
			$(".get-record-trigger").prev().keyup(function(e){
				if(e.keyCode=="13"){
					var cv=$(this).val();
					that.getRecords(cv,totlaPage);
				}
			});
			
		},
		/*
		*@func 页码跳转事件处理
		*/
		getRecords:function(c,totlaPage){
			var that=this;
			var reg=/^[1-9]\d*$/;
			if(c!="" && reg.test(c)==true && c<=totlaPage){
				that.genList(c-1);
			}else{
				var da=dialog({
					title:"提示",
					content:"请输入正确的页码"
				});
				da.showModal();
				setTimeout(function(){
					da.close().remove();
					$(".get-record-trigger").prev().val("");
				},1200);
			}	
		},
		bind:function(){
			var that=this;
			//单选框
			$.each($(".radio",$$),function(i,o){
				$(o).click(function(){
					$(this).Radios();
				})
			});
			//查看评分记录
			$(".show-custm-trig",$$).off().on("click",function(){
				var cur=$(this);
				if(!cur.hasClass("clicked")){
					cur.parent().find(".clicked").removeClass("clicked");
					cur.addClass("clicked");
					that.listCommtRecords();
				}
			});
		},
		listCommtRecords:function(){
			var scoreTem=$("#custm-hideTemp",$$).html(),that=this;
			d = dialog({
				title: '<i class="ifont">&#xf0077;</i><span>评价记录</span>',
				content: scoreTem,
				cancle:function(){
					$(".show-custm-trig",$$).removeClass("clicked");
					d.close().remove();
				}
			});
			d.showModal();
			that.genList(0);
		},
	 	genList:function(page){
			$(".show-custm-trig",$$).addClass("clicked");
			var that=customer.prototype,cur=$(".show-custm-trig",$$);
			if(!!!page) page=0;
			page+=1;
			var setting={
			  type:"GET",
			 url:cur.attr("data-info-action")+"&idcard="+$("#renter_id_card").val()+"&current_page="+page
			};
			$(".record-list-tb").remove();
			ajaxLoading=ajax.doAjax(setting.type,setting.url,"",function(json){
				$(".show-custm-trig",$$).removeClass("clicked");
				var len=json.data.page.count;
				$("#cmt-total-count",$$).text(len);
				if(json.status==1){
					if(json.data.data.length==0){
						$(".crm-custm-wrap").find(".jzf-pagination").css({"display":"none"});
					}else{
						ajax.iniPagination(len,".record_pagination:eq(1)",that.genList,5,(page-1));
						var totlaPage=1;
						if(len>5){
							if(len%5==0){
								totlaPage=parseInt(len/5);
							}else{
								totlaPage=parseInt(len/5)+1;
							}
						}
						var temp=template("data-temp-record",json.data);
						$(".crm-custm-wrap").find(".col-custm").after(temp);
						$(".crm-custm-wrap").find(".jzf-pagination").css({"display":"block"});
						that.jumpPage(totlaPage);
					}
				}else{
					$("#record_pagination").parent().hide();
				}
			});
		},
		
		/*
		 *@func 表单数据提交事件绑定
		 * */
		subCustminfoForm:function(){
			var that=this;
			$("#add_Customer",$$).off().on("click",function(){
				
			});
		},
		/*
		 *@func:自动搜集表单提交数据
		 * *
		 */
		getParams:function(form){
			var jzf_form=$(form),that=this,
			jzf_input_txt=jzf_form.find("input[type='text']"),
			jzf_input_radio=jzf_form.find("input[type='radio']:checked"),
			jzf_textarea=jzf_form.find("textarea"),
			parmUnits="",
			parm_txt="",parm_radio="",parm_textar="";
		//input[type='text']
		 $.each(jzf_input_txt, function(i,o){
		 	var name=o.getAttribute("name");
		 	if(!!name){
		 		var sv=o.value;
		 		if(name=="from"){
		 			sv=o.getAttribute("selectval");
		 		}
		 		if(i==0){
		 			parm_txt+=""+name+"="+sv;
		 		}else{
		 			parm_txt+="&"+name+"="+sv;
		 		} 
		 	}                                                  
		 });

		 //textarea
		 $.each(jzf_textarea,function(i,o){
		 	var name=o.getAttribute("name");
		 		if(i==0){
		 			parm_textar+=""+name+"="+o.value;
		 		}else{
		 			parm_textar+="&"+name+"="+o.value;
		 		}
		 });

		 //input[type='radio']
		 $.each(jzf_input_radio,function(i,o){
		 	var name=o.getAttribute("name");
		 		if(i==0){
		 			parm_radio+=""+name+"="+o.value;
		 		}else{
		 			parm_radio+="&"+name+"="+o.value;
		 		}
		 });
		//组装整个参数集合
		parmUnits=parm_txt+"&"+parm_textar+"&"+parm_radio;

		parmUnits=parmUnits.replace("&&","&");
		var isF=parmUnits.substr(0,1);
		if(isF=="&"){
			parmUnits=parmUnits.substr(1,parmUnits.length-1);
		}
//		console.log(parmUnits);
//		alert("当前参数："+parmUnits);	
		var editId=$(jzf_form).find("input[name='tid']").val();
		if(editId && editId!=undefined){
			parmUnits+="&tid="+editId;
		}
			return parmUnits;
		},
			/*
			 *@func：表单提交
			 * */
			submitForm : function(form){
				var that=this;
				var  url=form.attr("action"),
					 dataUrl=url,
					 editUrl=form.attr("actionEdit");//编辑请求路径
					$(".validInput",$$).trigger("blur");
					var len=form.find(".jzf-error-box").length,
						param=that.getParams(form),
						isEdit=form.find("input[name='tid']").val();
					if(isEdit && isEdit !=undefined){
						dataUrl=editUrl;
					}
					dataUrl=encodeURI(dataUrl);
					var _btn=$("#add_Customer",$$);
					ajaxLoading=ajax.doAjax("post",dataUrl,param,function(json){
						_btn.removeAttr("request");
						if(json.status==0){
							_btn.attr("request",Math.floor(Math.random()*1000));
						}
						that.callback(json);
					});
			},
			/*
			 *@func：表单提交成功回调函数
			 * */
			callback : function(data){
				if(data.status == 1){
					var d = dialog({
						title: '提示信息',
						content:typeof data['data'] == 'string' ? data.message : '操作成功',
						okValue: '确定',
						ok: function () {
							d.close();
							//关闭当前标签
							var tag = WindowTag.getCurrentTag();
							if(typeof data['url'] == 'string'){
			    				window.WindowTag.openTag('#'+data.url);
			    			}else if(typeof data['tag'] == 'string'){
			    				var ctag = WindowTag.getTagByUrlHash(data['tag']);
			    				if(ctag){
			    					window.WindowTag.selectTag(ctag.find(' > a:first').attr('url'));
			    					window.WindowTag.loadTag(ctag.find(' > a:first').attr('href'),'get',function(){});
			    				}
			    			}
							WindowTag.closeTag(tag.find('>a:first').attr('url'));
						}
					});
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
			},
			/*
			 *@func:初始化表单验证
			 * */
			checkForm : function(){
				var that=this;
				$(".cutm-form",$$).Validform({
					btnSubmit : "#add_Customer",
					showAllError : true,
					tiptype : function(msg,o,cssctl){
		                var objtip=o.obj.parents(".jzf-col-r").find(".check-error");
		                cssctl(objtip,o.type);
		                objtip.text(msg);
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
		               }
		            },
		            callback : function(form){
		            		var bl=hash.hash.ischange("cutm-form",$(":first",$$));
	    					var bl1=that.differCacheFile(form);
	            			if(bl==true || bl1==false){
	            				that.submitForm(form);
	    					}else{
	    						var tag = WindowTag.getCurrentTag();
							WindowTag.closeTag(tag.find('>a:first').attr('url'));
	    					}
			            	return false;
		            }
				});
				$(":input",$$).focus(function(){
					if($(this).hasClass("Validform_error")){
						$(this).css("background","none");
						$(this).siblings(".check-error").hide();
					}
				}).blur(function(){
					$(this).removeAttr("style");
					$(this).siblings(".check-error").show();
				});
			}
		};
		new customer();
	}
	exports.inite=function(_html_,data){
//		new customer();
		customerModule(_html_,data);
	}
});
