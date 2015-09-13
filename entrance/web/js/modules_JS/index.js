define(function(require){
	var $ = require("jquery");
	require("selectByM")($);
	require("radio")($);
	var navigators = require("navigatortest");
	var tagCreate = require("tagCreate");
	var  ic=require("indexCalander"),
	 	 ms=require("mod_statics"),
		 dialog=require("dialog"),
		 ajax=require("Ajax"),
		 IMAGEGALLARY=[],//浏览大图图片缓存
		 FILESCACHE=[],//原始文件缓存
		 SORTCACHE=[],//索引缓存
		 SLIDEINDEX=0,
		 slideWidth=600,
	 	 calendar_Ind = require("calendar_Ind");

	var jooozo=window.jooozo=(function(){
		return{
			initPage:function(){
				var that=this;
				that.index();
				that.iniBoxHt();
				that.bind();
				that.getUserStatus();
				that.browserBigImage();//浏览大图
			},
		 /*
		 *@func-请求新版本弹窗提示
		 *@param 用户id是否已经提示过
		 */
			getUserStatus:function(){
					var _el=$(".help-jooozo"),
							url=_el.attr("popup_url");
					ajax.doAjax("GET",url,"",function(json){
						if(json.status==1){
							var showTemp='<div class="update-tips-wrap">'
									+json.data.content
									+'</div>';
								var d = dialog({
									title: '<span>SaaS版本更新说明  </span>' + '(' + json.data.pub_date + ')',
									content: showTemp,
									onclose: function () {
										 var theRequest = new Object();
										 if (url.indexOf("?") != -1) {
												var str = url.substr(1);
												strs = str.split("&");
												for(var i = 0; i < strs.length; i ++) {
													 theRequest[strs[i].split("=")[0]]=(strs[i].split("=")[1]);
												}
										 }
										 ajax.doAjax("POST",url,{user_id:theRequest.user_id,pub_log_id:json.data.pub_log_id},function(json){

										 });
						      },
								});
								d.showModal();
						}
					});
			},
			bind:function(){
				$(".trigerLogout").off().on("click",function(){
					var url=this.getAttribute("data-url");
	   				var da=dialog({
	   					title: '提示信息',
						content:"您将要退出系统，确认退出？",
						okValue: '确定',
						ok: function () {
							da.close().remove();
							//删除已经保存的标签
							if(window.sessionStorage){
								window.sessionStorage.removeItem('nav_tags');
							}
							window.location.href=url;
						},
						cancelValue:"取消",
						cancel:function(){
							da.close().remove();
						}
	   				});
	   				da.showModal();
				});
				$("#js-crt-city h3").click(function(){
					var cur=$(this),
						_nexp=cur.next();
					if(!cur.hasClass("current")){
						var _txt=cur.text();
						var  _par=$(".all-city-box").find("a").not(".more");
						$.each(_par,function(i,o){
							if(_txt==("["+$(o).text()+"]")){
								$(o).parents(".all-city-box").find(".current").removeClass("current");
								$(o).addClass("current");
							}
						});
						cur.addClass("current");
						_nexp.show();
					}else{
						cur.removeClass("current");
						_nexp.hide();
					}
				});
				$(".all-city-box .all-city-list a").click(function(){
					var  cur=$(this),
					    _par=cur.parents(".all-city-box");
					    if(!cur.hasClass("more")){
					    	  if(cur.hasClass("current")){
					    	  	$("#js-crt-city h3").trigger("click");
					    	  }else{
					    	  	var url=$("#js-crt-city").find("h3").attr("url");
					    	  	var data={city_id:cur.attr("city")};
					    	  	_par.find(".current").removeClass("current");
					    	  	cur.addClass("current");
							ajax.doAjax("POST",url,data,function(json){
								if(json.status==1){
							    	  	_par.prev().text("["+cur.text()+"]");
							    	  	window.location.reload();
							    	  	$("#js-crt-city h3").trigger("click");
								}
							});
					    	  }
					    }else{
					    		$("#js-crt-city h3").trigger("click");
					    		window.location.href="#"+cur.attr("data-url");
					    }
				});
				$(document).click(function(e){
//					e.stopPropagation();
					if(e.target.className!="evt-trig-ele"){
						var  cur=$("#js-crt-city h3");
						cur.removeClass("current");
						cur.next().slideUp();
					}
				});
				/*首页点击页面状态数据重置*/
				$(".tag").on('click',".irefresh",function(){
						$(".btn-b.eventTriger").trigger("click");
						$('.calendar_Ind').addClass("none");
						$("#show_Calendar").find("tr.col-gem").not("tr:eq(0)").remove();
						calendar_Ind.inite($("#show_Calendar"));
				        ms.regetList();
				});
			},
			index : function(){
				/*
				 * 个人信息下拉
				 */
				var msg = $('.msg');
				var help = $('.help-jooozo');
				var msg_c_1 = $('.msg-c-1');
				var msg_c_2 = $('.msg-c-2');
				msg.hover(function(){
					$(this).addClass('current').siblings().removeClass('current').parents('.head').find('.msg-c').hide();
					$(this).parents('.head').find('.msg-c-1').show();
					msg_c_1.mouseleave(function(){
						$(this).slideUp(200).siblings('.head-r').find('.msg').removeClass('current');
					});
				});
				help.hover(function(){
					$(this).addClass('current').siblings().removeClass('current').parents('.head').find('.msg-c').hide();
					$(this).parents('.head').find('.msg-c-2').show();
					msg_c_2.mouseleave(function(){
						$(this).slideUp(200).siblings('.head-r').find('.help-jooozo').removeClass('current');
					});
				});
				/*
				 *导航栏展开
				 */
				var oneNav = $('.one-nav');//一级菜单
				oneNav.click(function(){
					var that = $(this);
					if(!that.hasClass('current')){
						that.parent().addClass("nav-list-current").siblings().removeClass("nav-list-current");
						that.addClass('current');
						that.children('.ifont').addClass('current');
						that.next('.two-nav').slideDown(200);
						that.parents('.nav-list').siblings().find('.one-nav').removeClass('current');
						that.parents('.nav-list').siblings().find('.two-nav').slideUp(200);
					};
				});
				var twoNav = $('.two-nav').find('li');
				twoNav.each(function(){
					$(this).click(function(){
						$(this).addClass('current').siblings().removeClass('current');
					});
				});

				/*
				 * 导航栏高度自动
				 */
				if(sys.ie && sys.ie < 8){
					var setHeight = function(){
						var nav = $('.nav-box');
						var h = $(window).height() - 100;
						nav.css('height',h);
					}
					setHeight();
	//				$(window).resize(function(){
	//					setHeight();
	//				});

	//				$(window).unbind().bind('resize', function () {
	//			        if (resizeTimer) {
	//			            clearTimeout(resizeTimer)
	//			        }
	//			        resizeTimer = setTimeout(function(){
	//			           setHeight();
	//			        }, 400);
	//		   	 	}
				};

				$(".slt").selectObjM();

				/*
				 *计算日历模块内出滚动条的高度
				 */
	//			var memo = $('.c-memo'); //备忘录模块
	//			var memo_list = $('.cm-list');
	//			var memo_h = $(window).height() - 105 - 80;
	//			memo.css('height',memo_h);
	//			$(window).resize(function(){
	//				memo.css('height',memo_h);
	//			});
	//			var list_h = $(window).height() - 105 - 115 - 60 - 30;
	//			memo_list.css('height',list_h);
	//			$(window).resize(function(){
	//				memo_list.css('height',list_h);
	//			});
	//
	//			var needs = $('.c-needs'),   //待办事项模块
	//			 	needs_h = $(window).height() - 105 - 80 - 80;
	//			needs.css('height',needs_h);
	//			$(window).resize(function(){
	//				needs.css('height',needs_h);
	//			});
	//			var needs_list = $('.c-needs ul');
	//			var needs_b = needs_h - 60;
	//			needs_list.css('height',needs_b);
	//			$(window).resize(function(){
	//				needs_list.css('height',needs_b);
	//			});



	//			$(".two-nav,.msg-c").find("a").off("click").on("click",function(){
	//		    	var urls = $(this).attr("href").split("#")[1];
	//		    	var type = "get";
	//		    	tagCreate.inite(urls,type,$(this));
	//		    });

			    if(sys.ie && sys.ie < 10){
					require("placeholder")($);
					$(".pager").placeholder();
	//				$('input[autofocus=true]').focus();
				}
			   //首页地址初始化
			   //window.location.href = "#index";

			   /*
				 * 标签框架加载
				 */
			   tagCreate.inite();

			   //针对ie6 LOGO PNG图片兼容
			   if(sys.ie && sys.ie < 7){
					$(".logo").children("img").attr("src","images/logo.gif");
				}
			   //左侧侧边栏的开关
			   $(".icon-index").click(function(){
			   	 var navL = $(".nav-box");
			   	 var mainBox = $(".main-box");
			   	 if($(this).hasClass("icon-index-close")){
			   	 	navL.animate({"left":"0px"},500);
			   	 	mainBox.animate({"padding-left":"150px"},500);
			   	 	$(this).removeClass("icon-index-close");
			   	 }else{
			   	 	navL.animate({"left":"-150px"},500);
			   	 	mainBox.animate({"padding-left":"0px"},500);
			   	 	$(this).addClass("icon-index-close");
			   	 }
			   });
			},
		   /*
		    *@func 高度初始化
		    * */
			iniBoxHt:function(){
				var _sh=window.innerHeight ? window.innerHeight:document.documentElement.clientHeight;
			   	var fxHt=105;
			   _sh=_sh-fxHt;
			   $(".main-show").css({
			   		"height":_sh+"px",
			   		"overflow":"auto"
			   });
			   var that=this,resizeTimer=null;
				$(window).unbind().bind('resize', function () {
			        if (resizeTimer) {
			            clearTimeout(resizeTimer)
			        }
			        resizeTimer = setTimeout(function(){
			           that.iniBoxHt();
			           ms.iniPageH();
			           $(".main").removeAttr("style");
					   calendar_Ind.renderClaenBox();
					   ic.renderBoxH();
			        }, 500);

			        //隐藏回到本月
			        	if($(window).width()<1440){
			        		 $("#currentM").addClass("none");
			        	}else{
			        		var myDate = new Date();
			        		var curmonth = myDate.getMonth()+1;
			        		if($("#curr-mon-txt").text() != curmonth){
			        			$("#currentM").removeClass("none");
			        		}
			        	}

			    });
			},
			//浏览大图事件
			browserBigImage:function(){
				var that=this;
				$(document).on("click",".upload-imgview",function(e){
					 if($(e.target).hasClass("delfilebtn")||$(e.target).hasClass("deleteImg") ){ 
						 return;
					 }
						var  cur=$(this),
								 _src=cur.find("img").attr("src");
						var isUploading=cur.parents(".uploader-area").find(".uploadify-progress-percent").length;
						if(_src!=undefined && isUploading==0){
							if(!cur.hasClass("slide-active")){
								cur.addClass("slide-active");
								$(".overlay-mask").show();
								$("#siwp-image").show();
								that.getSlideImages(cur);
							}
						}
				});
				//关闭弹窗
				$(document).on("click",".cover-close",function(){
						$(".overlay-mask").hide();
						$("#siwp-image").hide();
						$(".slide-active").removeClass("slide-active");
				});
			},
			//缓存需要浏览的图片元素
			getSlideImages:function(cur){
				 var that=this;
					IMAGEGALLARY=[];//清空
					FILESCACHE=[];
					SORTCACHE=[];
					var par=cur.parents(".uploader-area"),
							imgs=par.find("img").parent().find("input");
						$.each(imgs,function(i,o){
							var key=o.value,src,vv;
							key = encodeURI(key);
							var ext = /\.[^\.]+$/.exec(key);
							if (ext) ext = ext.toString().toLocaleLowerCase();
							else ext = '';
							if(ext == '.doc' || ext == '.docx'){
								vv='word-big.png';
							}else if(ext == '.pdf'){
								vv='pdf-big.png';
							}else{
							  vv=o.value;
							}
							src=qiNiudomain+vv+"?"+"imageView2/1/w/600/h/400/q/100";
							IMAGEGALLARY.push(src);//图片数组
							FILESCACHE.push(qiNiudomain+o.value);//文件数组下载使用
							SORTCACHE.push(o.value);//索引号数组
						});
				 that.genSlidesTemp(cur);
			},
			//返回索引号
			getSort:function(file){
					var  sort;
					$.each(SORTCACHE,function(j,item){
							 if(file==item){
								  sort=j;
							 }
					});
					return sort;
			},
			//生成模板
			genSlidesTemp:function(cur){
				 var _par=$("#siwp-image"),that=this;
					var li='',_ilen=IMAGEGALLARY.length;
				 if(_ilen==1){
					  li='<li><img src="'+IMAGEGALLARY[0]+'"></li>';
						_par.find(".view-next").hide();
				 }else{
					  for(var i=0;i<IMAGEGALLARY.length;i++){
							li+='<li><img src="'+IMAGEGALLARY[i]+'"></li>';
						}
				 }
				 _par.find("ul").html(li);
				 //赋值全部数量和索引值
				  // var _slideIndex=$(".slide-active").index();
					var _slideIndex=that.getSort(cur.find("input").val());

					_par.find(".download-file").attr("href",FILESCACHE[_slideIndex]+"?download");

					if((_slideIndex+1)>1){
						_par.find(".view-prev").show();
					}else{
						_par.find(".view-prev").hide();
					}
				 _par.find(".current").text(_slideIndex+1);//当前页
				 _par.find("#total").text(_ilen);//图片数量

				 var sort=_slideIndex+1;
				 if(sort==1){
				 	 _par.find(".view-prev").hide();
				 }else{
				 	 _par.find(".view-prev").show();
				 }
				 if(sort==IMAGEGALLARY.length){
				 		_par.find(".view-next").hide();
				 }else{
				 		_par.find(".view-next").show();
				 }
				 _par.find("#image-box").css({
					  "width":_ilen*slideWidth
				 });

				 that.initSlide(_slideIndex);//初始滑动
			},
			//初始滑动
			initSlide:function(slide){
				 SLIDEINDEX=slide;
				 var _par=$("#siwp-image"),that=this;
				 $("#image-box").css({
					 	"margin-left":slide*slideWidth*(-1)+"px"
				 });
				 //上一张
				 _par.find(".view-prev").off().on("click",function(){
					   if(SLIDEINDEX>=1){
							 SLIDEINDEX--;
						 	that.slidePlay(-SLIDEINDEX);
					  }
				 });
				 //下一张
				 _par.find(".view-next").off().on("click",function(){
					 if(SLIDEINDEX<IMAGEGALLARY.length) {
						 SLIDEINDEX++;
					 	 that.slidePlay(-SLIDEINDEX);
				 	}
				 });

			},
			//滑动中
			slidePlay:function(slide){
				var _par=$("#siwp-image");
				var sort=slide<0?slide*(-1):slide;
				_par.find(".download-file").attr("href",FILESCACHE[sort]+"?download");
				sort=sort+1;
			  _par.find(".current").text(sort);//当前页
				if(sort==1){
					 _par.find(".view-prev").hide();
				}else{
					 _par.find(".view-prev").show();
				}
				if(sort==IMAGEGALLARY.length){
						_par.find(".view-next").hide();
				}else{
						_par.find(".view-next").show();
				}
				$("#image-box").animate({
					 "margin-left":slide*slideWidth+"px"
				},400);
			}
		}

	})();
	$(function(){
		jooozo.initPage();
		if($('.hide-register-suc').length > 0){//未审核弹窗
			var dialogHtml = $(".hide-register-suc");
			var d = dialog({
				title: '<span></span>',
				content: dialogHtml,
			});
			d.showModal();
			//$(d.node).find('.ui-dialog-close').remove();
		}
	})
});
