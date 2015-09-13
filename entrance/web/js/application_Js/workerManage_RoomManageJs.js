define(function(require,exports){
	var $$ = null;
	/*
	 * @desc:员工管理-房源分配
	 * @date:2015-4-1
	 */
	var $ = require("jquery");
			require("selectByM")($);		//自定义下拉菜单
	var navigators = require("navigatortest");  //浏览器版本检测
	var ajax=require("Ajax");
	var hash = require('child_data_hash');
	var prev_hash = null;
	var	dialog = require("dialog");    //弹窗插件
	
	var save = function(callback){//保存数据
		var worker_ul = $('.workerManage_RoomManage_B_b ul',$$);
		if(!hash.hash.ischange('workermanage_roommanagejs',worker_ul)){
			var da=dialog({
				title:"提示",
				content:"没有更改数据，不需要保存",
				okValue:"确定",
				ok:function(){
					da.close().remove();
					callback({status:0});
					return false;
				}
			});
			da.showModal();
			return false;
		}
		var type = worker_ul.attr('type');
		var area = worker_ul.attr('area');
		var url = $('.li_Submite .submite',$$).attr('url');
		url = url.replace('TYPE',type).replace('AREA',area);
		var data = {};
		var xq_id = [];
		var house_id = [];
		worker_ul.find('li > .checkBox').each(function(){
			if($(this).children("input:checked").size()>0){
				xq_id.push($(this).children("input:checked").val());
			}
		});
		worker_ul.find('li > dl >dd > .checkBox-houses').each(function(){
			if($(this).children("input:checked").size()>0){
				house_id.push($(this).children("input:checked").val());
			}
		});
		if($(".centralized_Ind_CuteType",$$).attr("selectval") == 0){
			data = {
				"xq_id" : xq_id
			};
		}else{
			data = {
				"xq_id" : xq_id,
				"house_id" : house_id
			};
		}
		ajax.doAjax('post',url,data,function(json){
			if(json.status != 1){
				var da=dialog({
					title:"提示",
					content:typeof json['message'] == 'string' ? json['message'] : '操作失败',
					okValue:"确定",
					ok:function(){
						da.close().remove();
						callback({status:0});
						return false;
					}
				});
				da.showModal();
			}else{
				//保存hash
				var worker_ul = $('.workerManage_RoomManage_B_b ul',$$);
				prev_hash = hash.hash.savehash('workermanage_roommanagejs',worker_ul);
				var da=dialog({
					title:"提示",
					content:'保存成功',
					okValue:"确定",
					ok:function(){
						da.close().remove();
						callback({status:0});
						var tag = WindowTag.getCurrentTag();
						WindowTag.closeTag(tag.find('>a:first').attr('url'));
						if(typeof json['tag'] == 'string'){
		    				var ctag = WindowTag.getTagByUrlHash(json['tag']);
		    				if(ctag){
		    					window.WindowTag.selectTag(ctag.find(' > a:first').attr('href'));
		    					window.WindowTag.loadTag(ctag.find(' > a:first').attr('url'),'get',function(){});
		    				}
		    			}
						return false;
					}
				});
				da.showModal();
			}
		});
	}
	
	//给所以li设置z-index
	function li_Zindex(){
		var obg = $(".workerManage_RoomManage_B_b > ul > li");
		var length = obg.length;
		var width_P = $(".workerManage_RoomManage_B_b",$$).width();
		
		//复选框点击效果
		$(".workerManage_RoomManage_B_b > ul > li > .name",$$).off("click").on("click",function(event){
			event.preventDefault();
			var self = $(this).siblings('.checkBox');
			var num_li = $(".workerManage_RoomManage_B_b > ul > li",$$).size();
			var data_dl = $(this).siblings("dl");
			self.toggleClass("checked");
			if(self.hasClass("checked")){
				self.children("label").show();
				self.children("input").attr("checked",true);
				data_dl.children("dd").each(function(){
					$(this).children(".checkBox-houses").children("label").show().siblings("input").attr("checked",true);
				});
				if($(".workerManage_RoomManage_B_b > ul > li > .checkBox > input:checked",$$).size() == num_li){
					$(".chooseHouses",$$).children().show();
					$(".checkAllHouses",$$).text('取消全选');
				}
				num_Houses();
			}else{
				self.children("label").hide();
				self.children("input").removeAttr("checked");
				$(".chooseHouses",$$).children().hide();
				$(".checkAllHouses",$$).text('全选');
				data_dl.children("dd").each(function(){
					$(this).children(".checkBox-houses").children("label").hide().siblings("input").removeAttr("checked");
				});
				num_Houses();
			}
		});
		$(".workerManage_RoomManage_B_b > ul",$$).off("click").on("click","li > .checkBox",function(event){
			event.preventDefault();
			var self = $(this);
			var data_dl = $(this).siblings("dl");
			var num_li = $(".workerManage_RoomManage_B_b > ul > li",$$).size();
			self.toggleClass("checked");
			if(self.hasClass("checked")){
				self.children("label").show();
				self.children("input").attr("checked",true);
				data_dl.children("dd").each(function(){
					$(this).children(".checkBox-houses").children("label").show().siblings("input").attr("checked",true);
				});
				if($(".workerManage_RoomManage_B_b > ul > li > .checkBox > input:checked",$$).size() == num_li){
					$(".chooseHouses",$$).children().show();
					$(".checkAllHouses",$$).text('取消全选');
				}
				num_Houses();
			}else{
				self.children("label").hide();
				self.children("input").removeAttr("checked");
				$(".chooseHouses",$$).children().hide();
				$(".checkAllHouses",$$).text('全选');
				data_dl.children("dd").each(function(){
					$(this).children(".checkBox-houses").children("label").hide().siblings("input").removeAttr("checked");
				});
			}
			num_Houses();
		});
		
		obg.each(function(){
			var num = $(this).index();
			var offset_Left = $(this).position().left;
			var this_dl = $(this).children("dl");
			var num_dd = this_dl.find("dd").size();
			var checkBox_All = this_dl.siblings(".checkBox");
			$(this).css("z-index",length - num);
			this_dl.width(width_P).css({"left":"-"+(offset_Left+1)+"px"});
			$(this).mouseenter(function(){
				width_P = $(".workerManage_RoomManage_B_b",$$).width();
				offset_Left = $(this).position().left;
				this_dl.width(width_P).css({"left":"-"+(offset_Left+1)+"px"});
				this_dl.show();
			});
			$(this).mouseleave(function(){
				this_dl.hide();
			});
			this_dl.children("dd").off("click").on("click",function(event){
				event.preventDefault();
				var checkBox = $(this).find(".checkBox-houses");
				checkBox.children("label").toggle();
				if(checkBox.children("label").is(":visible")){
					checkBox.children("label").show().siblings("input").attr("checked",true);
					if(num_dd == this_dl.find("input:checked").size()){
						checkBox_All.children("label").show().siblings("input").attr("checked",true);
						if($(".workerManage_RoomManage_B_b > ul > li > .checkBox > input:checked",$$).size() == $(".workerManage_RoomManage_B_b > ul > li",$$).size()){
							$(".chooseHouses",$$).children().show();
							$(".checkAllHouses",$$).text('取消全选');
						}
					}
					num_Houses();
				}else{
					checkBox.children("label").hide().siblings("input").removeAttr("checked");
					checkBox_All.children("label").hide().siblings("input").removeAttr("checked");
					$(".chooseHouses",$$).children().hide();
					$(".checkAllHouses",$$).text('全选');
					num_Houses();
				}
			});
		});
		//房间全选
		if($(".workerManage_RoomManage_B_b > ul > li > .checkBox > input:checked",$$).size() == $(".workerManage_RoomManage_B_b > ul > li",$$).size()){
			$(".chooseHouses",$$).children().show();
			$(".checkAllHouses",$$).text('取消全选');
		}
		obg.children("dl").children("dd.choose").hover(function(){
			$(this).children(".fp-worker").show();
		},function(){
			$(this).children(".fp-worker").hide();
		});
		
		//加载已分配房间数
		function num_Houses(){
			$(".workerManage_RoomManage_B_b > ul > li > .name > .num-yfp",$$).each(function(){
				var num = $(this).parent().siblings("dl").find("input:checked").size();
				$(this).text("已分配："+num);
			});
		}
		num_Houses();
	}
	//加载列表
	var loadlist = function(){//加载数据列表
		var worker_ul = $('.workerManage_RoomManage_B_b ul',$$);
		var loading = function(){
			//类型
			var type = parseInt($(".workerManage_RoomManage_B_a .selectByM:first .selectByMT",$$).attr('selectval'));
			type = isNaN(type) ? $(".workerManage_RoomManage_B_a .selectByM:first .selectByMO li:first",$$).attr('selectval') : type;
			type = isNaN(type) ? 0 : type;
			//区域
			var area = parseInt($(".workerManage_RoomManage_B_a .selectByM:last .selectByMT",$$).attr('selectval'));
			area = isNaN(area) ? $(".workerManage_RoomManage_B_a .selectByM:last .selectByMO li:first",$$).attr('selectval') : area;
			area = isNaN(area) ? 0 : area;
			var url = $('.workerManage_RoomManage_B_a',$$).attr('url');
			url = url.replace('TYPE',type).replace('AREA',area);
			worker_ul.html('');
			/** 
			 * js截取字符串，中英文都能用 
			 * @param str：需要截取的字符串 
			 * @param len: 需要截取的长度 
			 */  
			function cutstr(str,len){  
			   var str_length = 0;  
			   var str_len = 0;  
			      str_cut = new String();  
			      str_len = str.length;  
			      for(var i = 0;i<str_len;i++)  
			     {  
			        a = str.charAt(i);  
			        str_length++;  
			        if(escape(a).length > 4)  
			        {  
			         //中文字符的长度经编码之后大于4  
			         str_length++;  
			         }  
			         str_cut = str_cut.concat(a);  
			         if(str_length>=len)  
			         {  
			         str_cut = str_cut.concat("...");  
			         return str_cut;  
			         }  
			    }  
			    //如果给定字符串小于指定长度，则返回源字符串；  
			    if(str_length<len){  
			     return  str;  
			    }  
			}  
			ajax.doAjax('get',url,'',function(json){
//				var template = '<li>\
//									<span class="name">[NAME]</span><span class="checkBox"><label class="ifont1">&#xe60c;</label><input name="val[]" value="[ID]" type="checkbox"/></span>\
//								</li>';
				
				var html = '';
				var data = typeof json['data'] == 'object' ? json.data : [];
				for(var k in data){
					var o = data[k],
						  names_houses = o.name;
					names_houses = cutstr(names_houses,20);
					var houses = '';
					if(type == 1){
						var data_houses = o.house_list;
						houses += '<dl>';
						for(var z in data_houses){
							if(data_houses[z].is_self == 1 && data_houses[z].is_allot == 0){
								houses += '<dd><span class="dd-name" title="'+data_houses[z].house_name+'">'+data_houses[z].house_name+'</span><span class="checkBox-houses"><label class="ifont1">&#xe60c;</label><input name="vals[]" value="'+data_houses[z].house_id+'" type="checkbox" checked="checked"/></span></dd>'; 

							}else if(data_houses[z].is_self == 0 && data_houses[z].is_allot == 1){
								var allot = data_houses[z].allot;
								var names = [];
								for(var b in allot){
									names.push(allot[b].user_name);
								}
								houses += '<dd class="choose"><span class="dd-name" title="'+data_houses[z].house_name+'">'+data_houses[z].house_name+'</span><span class="checkBox-houses"><label class="ifont1" style="display:none">&#xe60c;</label><input name="vals[]" value="'+data_houses[z].house_id+'" type="checkbox"/></span><div class="fp-worker">已分配给：'+names+'</div></dd>'; 
							}else if(data_houses[z].is_self == 1 && data_houses[z].is_allot == 1){
								var allot = data_houses[z].allot;
								var names = [];
								for(var b in allot){
									names.push(allot[b].user_name);
								}
								houses += '<dd class="choose"><span class="dd-name" title="'+data_houses[z].house_name+'">'+data_houses[z].house_name+'</span><span class="checkBox-houses"><label class="ifont1">&#xe60c;</label><input name="vals[]" value="'+data_houses[z].house_id+'" type="checkbox" checked="checked"/></span><div class="fp-worker">已分配给：'+names+'</div></dd>'; 
							}else{
								houses += '<dd><span class="dd-name" title="'+data_houses[z].house_name+'">'+data_houses[z].house_name+'</span><span class="checkBox-houses"><label class="ifont1" style="display:none;">&#xe60c;</label><input name="vals[]" value="'+data_houses[z].house_id+'" type="checkbox"/></span></dd>'; 

							}
						}
						houses += '</dl>';
					}
					var li = o.is_self == 1 || o.is_allot == 1 ? '<li class="choose">' : '<li>';
					if(o.is_self == 1){
						if(type == 1){
							li += '<span class="name">'+names_houses+'<span class="num-yfp">已分配:</span></span><span class="checkBox checked"><label class="ifont1">&#xe60c;</label><input name="val[]" value="'+o.id+'" type="checkbox" checked="checked"/></span>';	
						}else{
							li += '<span class="name name-auto">'+names_houses+'</span><span class="checkBox checked"><label class="ifont1">&#xe60c;</label><input name="val[]" value="'+o.id+'" type="checkbox" checked="checked"/></span>';
						}
					}else{
						if(type == 1){
							li += '<span class="name">'+names_houses+'<span class="num-yfp">已分配:</span></span><span class="checkBox"><label class="ifont1" style="display: none;">&#xe60c;</label><input name="val[]" value="'+o.id+'" type="checkbox"/></span>';	
						}else{
							li += '<span class="name name-auto">'+names_houses+'</span><span class="checkBox"><label class="ifont1" style="display: none;">&#xe60c;</label><input name="val[]" value="'+o.id+'" type="checkbox"/></span>';
						}
					}
					li += houses;
					if(o.is_allot){
						li += '<div class="choosed_Detail">';
						li += '已分配给：';
						for(var j in o.allot){
							li += o.allot[j].user_name+',';
						}
						li = li.replace(/^(.*),$/,'$1');
						li += '</div>';
					}
					li += '</li>';
					html += li;
				}
				worker_ul.html(html);
				//保存hash
				prev_hash = hash.hash.savehash('workermanage_roommanagejs',worker_ul);
				worker_ul.attr('type',type).attr('area',area);
				//给所以li设置z-index
				li_Zindex();
			});	
		};
		if(prev_hash != null && hash.hash.ischange('workermanage_roommanagejs',worker_ul)){
			var da=dialog({
				title:"提示",
				content:"数据已发生修改,是否需要保存?",
				cancelValue:"不保存",
				cancel:function(){
					da.close().remove();
					loading();
					return false;
				},
				okValue:"保存",
				ok:function(){
					da.close().remove();
					save(loading);
					return false;
				}
			});
			da.showModal();
		}else{
			loading();
		}
	};
	exports.inite = function(html){
		prev_hash = null;
		$$ = html;
		//自定义下拉菜单
		$(".workerManage_RoomManage_B_a .selectByM:first",$$).selectObjM(1,function(val){
			$(".chooseHouses",$$).children().hide();
			$(".checkAllHouses",$$).text('全选');
			if(val == 0){
				$(".workerManage_RoomManage_B_a .selectByM:last",$$).hide();
				loadlist();
			}else{
				//默认选择一个区域
				var area = parseInt($(".workerManage_RoomManage_B_a .selectByM:last .selectByMT",$$).attr('selectval'));
				if(isNaN(area)){
					area = isNaN(area) ? $(".workerManage_RoomManage_B_a .selectByM:last .selectByMO li:first",$$).attr('selectval') : area;
					$(".workerManage_RoomManage_B_a .selectByM:last .selectByMO li[selectval="+area+"]",$$).click();
				}else{
					loadlist();
				}
				$(".workerManage_RoomManage_B_a .selectByM:last",$$).show();
			}
		});
		$(".workerManage_RoomManage_B_a .selectByM:last",$$).selectObjM(1,function(val){
			loadlist();
		});
		loadlist();
		
		//ie6鼠标掠过变色
		if(sys.ie && sys.ie < 7){
			$(".workerManage_RoomManage_B_b > ul > li",$$).hover(function(){
				$(this).addClass("ie6Hover");
			},function(){
				$(this).removeClass("ie6Hover");
			});
		}
		
		//弹出层效果
		$(".workerManage_RoomManage_B_b > ul",$$).on('mouseenter','li.choose',function(){
			$(this).children(".choosed_Detail").show();
		});
		//弹出层效果
		$(".workerManage_RoomManage_B_b > ul",$$).on('mouseleave','li.choose',function(){
			$(this).children(".choosed_Detail").hide();
		});
		$(".chooseHouses,.checkAllHouses",$$).off("click").on("click",function(){
			$(".chooseHouses",$$).children().toggle();
			if($(".chooseHouses",$$).children().is(":visible")){
				$(".checkAllHouses",$$).text('取消全选');
				$(".workerManage_RoomManage_B_b > ul > li > .checkBox",$$).addClass('checked').children("label").show().siblings("input").attr("checked",true);
				$(".workerManage_RoomManage_B_b > ul > li > dl >dd",$$).each(function(){
					$(this).children(".checkBox-houses").children("label").show().siblings("input").attr("checked",true);
				});
				num_Houses();
			}else{
				$(".checkAllHouses",$$).text('全选');
				$(".workerManage_RoomManage_B_b > ul > li > .checkBox",$$).removeClass('checked').children("label").hide().siblings("input").removeAttr("checked");
				$(".workerManage_RoomManage_B_b > ul > li > dl >dd",$$).each(function(){
					$(this).children(".checkBox-houses").children("label").hide().siblings("input").removeAttr("checked");
				});
				num_Houses();
			}
		});
		//加载已分配房间数
		function num_Houses(){
			$(".workerManage_RoomManage_B_b > ul > li > .name > .num-yfp",$$).each(function(){
				var num = $(this).parent().siblings("dl").find("input:checked").size();
				$(this).text("已分配："+num);
			});
		}
		//隐藏所以已配置房间
		$(".workerManage_RoomManage_A > .b > label",$$).off("click").on("click",function(){
			$(this).toggleClass("checked");
			if($(this).hasClass("checked")){
				$(this).children(".choose").show();
				$(".workerManage_RoomManage_B_b > ul > li.choose",$$).hide();
			}else{
				$(this).children(".choose").hide();
				$(".workerManage_RoomManage_B_b > ul > li.choose",$$).show();
			}
		});
		$(".workerManage_RoomManage_A > .b > a",$$).off('click').on('click',function(){
			$(this).parent().find('label').click();
		});
		
		var save_click = function(){
			save(function(){
				$('.li_Submite .submite',$$).off("click").on("click",save_click);
			});
			$('.li_Submite .submite',$$).off("click");
		};
		$('.li_Submite .submite',$$).off("click").on("click",save_click);
	}
});