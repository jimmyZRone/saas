/*
===========================================================
* @func:表单模块方法封装
* @desc:主要方法：
*	1.表单参数自动搜集
*	2.表单数据修改检测提交
*	3.自定义异步提交参数
*	4.自定义异步提交回调参数
* @date:2015-3-9
* @author:Jack
* @version:v0.0.1
=============================================================
* */
define(function(require,exports,module){
	var $=require("jquery"),//基本库
	 	ajax=require("ajax"),//异步请求模块
		loading=require("loading"),//页面加载等待模块
		dialog=require("dialog");//页面弹窗模块
		require("validform")($);//表单验证模块
		
		
		
	var emptyFun=function(){};
	
	//全局函数
	var callback={
		ajaxCb:emptyFun,
		error:emptyFun,
		pagecallBack:emptyFun
	};
	/*
	 * @func:按钮事件绑定
	 * @param:外部自定义函数参数，默认为空
	 * }
	 */
	exports.init=function(func){
		if(func && func.ajaxCallback){callback.ajaxCb=func.ajaxCallback}//自定义异步回调函数
		var that=this;
		that.iniForm($(".jzf-form"));//初始表单初始数据存储
		loading.removeLoading($(".jzf-form"));//初始化关闭遮罩层关闭
		$.each($(".jzf-form-btn"),function(i,o){
			$(o).click(function(){
				var cur=$(this),
					form=cur.parents(".jzf-form"),
					result=true,//数据修改检测,修改页进行验证,添加页默认为true
					pageType=$(form).attr("isEditpage"),//是否是修改页面,涉及是否进行数据修改检测 1--是修改页 0--不是
					bl=form.Validform().check();//表单验证--公用
				if(pageType==1 || pageType=="1"){
					result=that.checkFormData(form);//验证数据更改检测 --true/数据被修改 -false/数据未作变化
					// console.log(result);
					if(!!bl){
						if(result==true) {
							alert("检测数据已作修改");
							that.iniFormdataUnit(form,func);
						}else{
							// alert("未检测到数据修改,执行默认函数方法");
							loading.genLoading($(".jzf-form"));
							// loading.pageLoading("loading-bar");
							//....执行默认方法/关闭页面/返回上一级页/返回指定页面
						}
					}
				}else{
					if(!!bl){
						that.iniFormdataUnit(form,func);
					}
				}
			});
		});
	}
	/*
	 * @func---发送异步请求
	 * @param:settings={
	 * 	 url:请求路径
	 *	 data:请求参数,
	 *   type:请求方式 post/get 
	 * 	 callback:回调函数
	 * }
	 */
	exports.sendAjaxRequest=function(settings){
		ajax.doAjax(settings.type,settings.url,settings.data,settings.callback);//调用异步请求模块方法
	}
	/*
	 *@func: 表单提交数据
	 */
	exports.formSubmit=function(ele){
		$(ele).submit();
	}
	/*
	 *@func:表单数据搜集
	 * @参数：form --当前需要提交数据的表单数据
	 * 		  func--自定义参数收集方法 ，返回需要加到基本表单元素上的自定义参数
	 * 		  默认为空
	 */
	exports.iniFormdataUnit=function(form,func){
		var jzf_form=$(form),that=this,subWay=$(form).attr("subWay"),
			jzf_input_txt=jzf_form.find("input[type='text']"),
			jzf_input_radio=jzf_form.find("input[type='radio']:checked"),
			jzf_select=jzf_form.find(".selectByMT"),
			jzf_textarea=jzf_form.find("textarea"),
			parmUnits="",
			parm_txt="",parm_radio="",parm_cbox="",parm_sel="",parm_textar="";
		//input[type='text']
		 $.each(jzf_input_txt, function(i,o){
		 	var name=o.getAttribute("name");
		 		if(i==0){
		 			parm_txt+=""+name+"="+o.value;
		 		}else{
		 			parm_txt+="&"+name+"="+o.value;
		 		}                                                   
		 });
//		 console.log(parm_txt);
		 //textarea
		 $.each(jzf_textarea,function(i,o){
		 	var name=o.getAttribute("name");
		 		if(i==0){
		 			parm_textar+=""+name+"="+o.value;
		 		}else{
		 			parm_textar+="&"+name+"="+o.value;
		 		}
		 });
//		 console.log(parm_textar);
		 //input[type='radio']
		 $.each(jzf_input_radio,function(i,o){
		 	var name=o.getAttribute("name");
		 		if(i==0){
		 			parm_radio+=""+name+"="+o.value;
		 		}else{
		 			parm_radio+="&"+name+"="+o.value;
		 		}
		 });
//		 console.log(parm_radio);
		 //input[type='checkbox']
		 //多个情况默认以“逗号,”进行分隔
		 //需要进行处理的复选框选择：class="jzf-checbox-area"
		 var  jzf_input_checkbox_parent=jzf_form.find(".jzf-checbox-area");
		 $.each(jzf_input_checkbox_parent,function(i,o){
		 	var name=o.getAttribute("fm-name"),//参数提交名称
		 		signal_item="",sdt="";
		 		cboxUnits=$(o).find("input[type='checkbox']:checked");
		 	$.each(cboxUnits,function(j,item){
		 		var item=cboxUnits[j],dt="";
		 			if(j==0){
		 				dt+=item.value;
		 			}else{
		 				dt+=","+item.value;
		 			}
		 			sdt+=dt;
		 	});	
		 	signal_item+=""+name+"="+sdt;
			if(i==0){
		 		parm_cbox+=signal_item;
			}else{
				parm_cbox+="&"+signal_item;
			}
		 	
		 });
//		 console.log(parm_cbox);

		 //select
		 $.each(jzf_select,function(i,o){
		  var  name=o.getAttribute("name");
		 	if(i==0){
		 			parm_sel+=""+name+"="+o.value;
		 		}else{
		 			parm_sel+="&"+name+"="+o.value;
		 		}
		 });
//		 console.log(parm_sel);

		 //图片文件处理
		 //多个情况默认以“逗号,”进行分隔
		 //需要进行处理的文件选择：class="uploader-area"
		var imgAreas=$(".uploader-area"),
			imgFiles="";
		$.each(imgAreas, function(i,o) {    
			  var name=o.getAttribute("fm-name"),
			  	  signal_item="",sdt="";
			  	  imgUnits=$(o).find(".upload-imgview");
			  	 $.each(imgUnits,function(j,item){
			  	 	 var  dt="";
			  	 	 if(j==0){
		 				dt+=item.getAttribute("filename");
		 			}else{
		 				dt+=","+item.getAttribute("filename");
		 			}
		 			sdt+=dt;
			  	 });
		 	signal_item+=""+name+"="+sdt;
		 	if(i==0){
		 		imgFiles+=signal_item;
			}else{
				imgFiles+="&"+signal_item;
			}
		});
//		console.log(imgFiles);
		//增加自定义参数
		if(func && func.getOtherparam) var extra=func.getOtherparam();
		//组装整个参数集合
		parmUnits=parm_txt+"&"+parm_textar+"&"+parm_cbox+"&"+parm_radio+"&"+parm_sel;
		if(extra) parmUnits+="&"+extra;
		parmUnits=parmUnits.replace("&&","&");
		var isF=parmUnits.substr(0,1);
		if(isF=="&"){
			parmUnits=parmUnits.substr(1,parmUnits.length-1);
		}
//		console.log(parmUnits);
		alert("当前参数："+parmUnits);
		//提交方式获取
		if(subWay=="ajax"){
			var durl=$(jzf_form).attr("ajaxUrl"),
				data=parmUnits,
				type=$(jzf_form).attr("method")?$(jzf_form).attr("method"):"post";
			if(type=="get"){
				durl+="?"+data;
				data="";
			}
			var settings={
				url:durl,//请求路径
				data:data,
				type:type,//请求方式,默认post
				callback:that.ajaxRequestCb
			};
			that.sendAjaxRequest(settings);//异步提交
		}else{
			that.formSubmit(jzf_form);//表单提交
		}
	}
	/*
	 * @func:异步提交数据处理回调方法
	 * @param:异步请求返回结果
	 */
	exports.ajaxRequestCb=function(json){
		//自定义异步回调函数
		if(callback.ajaxCb!=emptyFun){
			callback.ajaxCb(json);
			return;
		}
		//......通用异步请求回调函数
		//console.log(json.status);
	}
	/*
	 * @func:初始化表单原始数据存储
	 * @return:无
	 */
	exports.iniForm=function(form){
		var jzf_form=$(form),that=this,
			fm_area_input=$(jzf_form).find("input[type='text']"),
			fm_area_textara=$(jzf_form).find("textarea"),
			fm_area_select=$(jzf_form).find(".selectByMT"),
			fm_area_checkbox=$(jzf_form).find(".jzf-checbox-area"),
			fm_area_radio=$(jzf_form).find("input[type='radio']:checked"),
			fm_area_img=$(jzf_form).find(".uploader-area");
			$.each(fm_area_input,function(i,o){
				$(o).data("inited",$(o).val());
				that.iniTxtFblur(o);
			});
			$.each(fm_area_textara,function(i,o){
				$(o).data("inited",$(o).val());
				that.iniTxtFblur(o);
			});
			$.each(fm_area_select,function(i,o){
				$(o).data("inited",$(o).val());
			});
			$.each(fm_area_radio,function(i,o){
				$(o).data("inited",$(o).val());
			});
			//checkbox 数据存储
			$.each(fm_area_checkbox,function(i,o){
				 var objs=$(o).find("input[type='checkbox']:checked"),
				 	 datasUnit="";
				 $.each(objs,function(j,item){
				 	 var  cm="";
				 	 if(j==0){
				 	 	cm+=$(item).val();
				 	 }else{
				 	 	cm+=","+$(item).val();
				 	 }
				 	 datasUnit+=cm;
				 });
				 $(o).data("inited",datasUnit);
				 // console.log($(o).data("inited"));
			});
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
				 // console.log($(o).data("inited"));
			});
	}
	/*
	 * @func:表单输入框元素焦点事件绑定
	 * @param:ele-当前处理表单元素
	 * @return:无
	 */
	exports.iniTxtFblur=function(ele){
		var  that=this;
		$(ele).keyup(function(){
			that.formPubFunc(ele,this);
		});
		$(ele).focus(function(){
			var  cur=$(this),cv=cur.val();
		    cur.val($.trim(cv));
		}).blur(function(){
			that.formPubFunc(ele,this);
		});
	}
	
	/*
	 * @func:表单输入框事件处理提取
	 * @return:无
	 */
	exports.formPubFunc=function(item,o){
			var cur=$(o),cv=cur.val(),
				corg=cur.attr("jzf-origin");
			if($.trim(cv)=="" && $.trim(corg)!=""){
				cur.val(corg);
			}
	}
	
	/*
	 * @func:表单数据更改检测
	 * @return:true/false
	 */
	exports.checkFormData=function(form){
		var jzf_form=$(form),that=this,
			fm_area_input=$(jzf_form).find("input[type='text']"),
			fm_area_textara=$(jzf_form).find("textarea"),
			fm_area_select=$(jzf_form).find(".selectByMT"),
			fm_area_checkbox=$(jzf_form).find(".jzf-checbox-area"),
			fm_area_radio=$(jzf_form).find("input[type='radio']:checked"),
			fm_area_img=$(jzf_form).find(".uploader-area"),
			result=false;
			$.each(fm_area_input,function(i,o){
				var data_inited=$(o).data("inited"),
					fv=$(o).val();
				if($.trim(data_inited)!=$.trim(fv)){
					result=true;
				}
			});
			$.each(fm_area_textara,function(i,o){
				var data_inited=$(o).data("inited"),
					fv=$(o).val();
				if($.trim(data_inited)!=$.trim(fv)){
					result=true;
				}
			});
			$.each(fm_area_select,function(i,o){
				var data_inited=$(o).data("inited"),
					fv=$(o).val();
				if($.trim(data_inited)!=$.trim(fv)){
					result=true;
				}
			});
			$.each(fm_area_radio,function(i,o){
				var data_inited=$(o).data("inited"),
					fv=$(o).val();
				if($.trim(data_inited)!=$.trim(fv)){
					result=true;
				}
			});
			//checkbox 区域验证
			$.each(fm_area_checkbox,function(i,o){
				 var objs=$(o).find("input[type='checkbox']:checked"),
				 	 datasUnit="";
				 $.each(objs,function(j,item){
				 	 var  cm="";
				 	 if(j==0){
				 	 	cm+=$(item).val();
				 	 }else{
				 	 	cm+=","+$(item).val();
				 	 }
				 	 datasUnit+=cm;
				 });
				 // console.log(datasUnit);
				 var inited_data=$(o).data("inited");
				 // console.log(inited_data);
				 if(datasUnit!=inited_data){
				 	result=true;
				 }
			});
			//图片文件 区域验证
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
				 	result=true;
				 }
			});
			return result;
	}
});
