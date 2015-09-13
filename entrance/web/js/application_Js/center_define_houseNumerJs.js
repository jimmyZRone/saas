define(function(require,exports){
	var $ = require('jquery');
	var navigators = require("navigatortest");  //浏览器版本检测
	var ajax=require("Ajax");
	var	dialog = require("dialog"); //弹窗插件
	var hash = require('child_data_hash');
	require("validForm")($);
	var $$ = null;
	
	//ie6 7 window.resize兼容
	function debounce(callback, delay, context){  
	    if (typeof(callback) !== "function") {  
	        return;  
	    }  
	    delay = delay || 150;  
	    context = context || null;  
	    var timeout;  
	    var runIt = function(){  
	            callback.apply(context);  
	        };  
	    return (function(){  
	        window.clearTimeout(timeout);  
	        timeout = window.setTimeout(runIt, delay);  
	    });  
	}  
	
	//获取楼层距离楼层列表层顶部位移
	function getFloorTop(){
		var num_Position = [];
		var top = $(".center-dpt-row-auto",$$).scrollTop();
		var floor_num = null;
		$(".center-dpt-row-auto",$$).find("tr").each(function(){
			var cur_floor = $(this).attr("floor_num");
			if(cur_floor != floor_num){
				num_Position.push($(this).position().top+top);
				floor_num = cur_floor;
			}
		});
		
		return num_Position;
	}
	
	var center_define_houseNumerJs = {
		checkVal : function(){
			$(".dpt-floor-rooms .ipt",$$).keyup(function(){
				var key_array = [];
				$(":checkbox:checked",$$).each(function(){
					key_array.push($(this).val());
				});
				var val = $(this).val();
				for(var n in key_array){
					if(val.indexOf(key_array[n]) >= 0){
						val = val.replace(key_array[n],"");
					}
				}
				$(this).val(val);
			});
		},
		checkBox : function(){//生成房源编号
			$(".checkBox",$$).off("click").on("click",function(){
				$(this).toggleClass("checked");
				if($(this).hasClass("checked")){
					$(this).children(".choose").show();
					$(this).next().attr("checked",true);
				}else{
					$(this).children(".choose").hide();
					$(this).next().removeAttr("checked");
				}
				var index = 1;//基本索引
				var index_number = {};//已经存在的
				var shield_index = {};//屏蔽的索引
				if($(".checkBox:first",$$).hasClass('checked')){
					shield_index['4'] = true;
				}
				if($(".checkBox:last",$$).hasClass('checked')){
					shield_index['7'] = true;
				}
				var floor_index = 1;
				var get_val = function(val){//计算值
					val = parseInt(val);
					val = isNaN(val) ? 1 : val;
					if(typeof index_number[val+''] != 'undefined'){//已经存在于上面的索引中
						index++;
						return get_val(val+1);
					}
					val = (val+'').split('');
					for(var i in val){
						if(typeof shield_index[val[i]+''] != 'undefined'){//当前有值存在于屏蔽编号中
							var temp = '';
							for(var j=0;j<i;j++){//恢复高位
								temp += val[j];
							}
							temp += (parseInt(val[i])+1);//如果有屏蔽9导致进位就有BUG
							for(var j=i+1;j<val.length;j++){//恢复底位
								temp += val[j];
							}
							return get_val(temp);
							break;
						}
					}
					if(typeof val == 'object'){
						val = val.join('');
					}
					//有正确的值了
					index++;
					index_number[val+''] = index;
					val = val < 10 ? 0+''+val : val;
					return val;
				};
				$('.center-dpt-row-auto .dpt-floor-rooms tr',$$).each(function(){
					var self = $(this);
					var floor = parseInt(self.attr('floor_num'));
					if(floor != floor_index){//进入新楼层，重置一些索引
						index = 1;
						index_number = {};
						floor_index = floor;
					}
					var ipt = self.find('.ipt');
					ipt.removeClass('Validform_error');
					ipt.parent().find('span').removeClass('Validform_checktip').removeClass('Validform_wrong').hide();
					var	val = parseInt(ipt.val());
					ipt.val(get_val(index));
				});
			});
		},
		submitForm : function(){
			var type = "post";
			var url = $(".btn2",$$).attr("url");
			var rooms_numdiy = [];
			$(".dpt-floor-rooms tr:gt(0)",$$).each(function(){
				var floor_num = $(this).attr("floor_num");
				var room_numdiy = $(this).find(".ipt").val();
				var floors_num = {};
				floors_num[floor_num] = room_numdiy;
				rooms_numdiy.push(floors_num);
			});
			var data = {
				rooms_numdiy : rooms_numdiy
			}
			if(hash.hash.ischange('center_define_house_numer',$(':first',$$)) === false){
				var d = dialog({
						title: '提示信息',
						content:'数据没有发生修改，无法提交！',
						okValue: '确定',
						ok: function () {
							$(".btn2",$$).removeClass("none-click");
							d.close();
						}
					});
					d.showModal();
					$(".ui-dialog-close",".ui-dialog-header").hide();
			}else{
				ajax.doAjax(type,url,data,function(data){
				if(data.status == 1){
					var dd = dialog({
						title: '提示信息',
						content:'保存成功',
						okValue: '确定',
						ok: function () {
							$(".btn2",$$).removeClass("none-click");
							dd.close();
							//关闭当前标签
							var tag = WindowTag.getCurrentTag();
							WindowTag.closeTag(tag.find('>a:first').attr('url'));
							window.location.href = "#"+data.p_url;
							var parent_page = $(".jooozo_Page:visible");
							parent_page.find("input[name='centralized_Depart_InfoJs_group_number']").attr("isedit",1);
						}
					});
					dd.showModal();
					$(".ui-dialog-close",".ui-dialog-header").hide();
				}else{
					var dd = dialog({
						title: '提示信息',
						content:data.data,
						okValue: '确定',
						ok: function () {
							$(".btn2",$$).removeClass("none-click");
							dd.close();
						}
					});
					dd.showModal();
					$(".ui-dialog-close",".ui-dialog-header").hide();
				}
			});	
			}
		},
		checkForm : function(){
			var that = this;
			$(".center-dpt-body",$$).Validform({
					btnSubmit : ".btn2",
					showAllError : true,
					tiptype : function(msg,o,cssctl){
		                var objtip=o.obj.parent().find(".check-error");
		                cssctl(objtip,o.type);
		                objtip.text(msg);
		            },
		            datatype : {
		            	"chooseGroup":function(gets,obj,curform,regxp) {
		                   if(obj.attr("selectVal") == ''){
		                   	 return obj.attr("nullmsg");
		                   }
	
		               },
		               "float":function(gets,obj,curform,regxp) {
		                    var reg=/^\d+(\.\d+)?$/;
		                    if(reg.test(gets)){return true;}
		                    return false;
		               },
		               "zf8":function(gets,obj,curform,regxp){
		               		var reg = /.*\..*/;
		               		if(reg.test(gets)) return false;
		               	   var bbb = Math.abs(gets);
		               	   if(gets.indexOf('-') == 0){
		               		   bbb = 0-bbb;
		               	   }
		               	   if(isNaN(bbb) || bbb == 0 || bbb > 99999999 || bbb<0) return false;
		               	   var floor_num = $(obj).parents("tr").attr("floor_num");
		               	   var check_reslut = true;
		               	   $(obj).parents("tr").siblings("tr[floor_num="+floor_num+"]").each(function(){
		               	   	   var inp = Math.abs($(this).find(":text").val());
		               	   	   if(inp == bbb){
		               	   		   check_reslut = false; return false;
		               	   	   }
		               	   });
		               	  if(check_reslut === false) return "同一层楼房源编号不能相同";
		               }
		            },
		            callback : function(form){
		            	if($(".btn2",form).hasClass("none-click")) return false;
	           			$(".btn2",form).addClass("none-click");
		            	that.submitForm();
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
		},
		//设置数据层高度
		setdataH : function(){
			var obj = $(".center-dpt-row-auto",$$);
			var h_top1 = $(".head").height()+$(".tag").height()+80+5;
			var h_top2 = $(".center-dpt-top",$$).height()+24+15+34+30+36+25+15;
			var h_bottom = 36+40;
			obj.height($(window).height()-h_top1-h_top2-h_bottom+20);
		},
		//楼层滑动效果
		floorscroll : function(){
			var top_list = getFloorTop();
			$(".center-dpt-row-auto",$$).scroll(function(){
				var h = $(this).scrollTop();
				for(var n in top_list){
//					console.log(h);
//					var ss = top_list[n] - top_list[0];
//					alert(ss);
					if(h >= top_list[n] - top_list[0]){
						$(".center-dpt-nav a:eq("+n+")",$$).addClass("current").siblings().removeClass("current");
					}
				}
			}).trigger("scroll");
			$(".center-dpt-nav a",$$).off("click").on("click",function(){
				var num = $(this).index();
				$(this).addClass("current").siblings().removeClass("current");
				$(".center-dpt-row-auto",$$).animate({"scrollTop":top_list[num]-top_list[0]+"px"},300);
			});
		}
	}
	
	exports.inite = function(__html__){
		$$ = __html__;
		var that = center_define_houseNumerJs;
		//屏蔽隐藏指定楼层
		that.checkBox();
		//设置数据层高度实现滚动
		that.setdataH();
		if(sys.ie && sys.ie < 8){  
			window.onresize= debounce(that.setdataH, 300);
		}else{
			$(window).resize(that.setdataH);	
		}
		//楼层滑动效果
		that.floorscroll();
		//初始化hash
		hash.hash.savehash('center_define_house_numer',$(':first',$$));
		$(".btn-cancel",$$).off("click").on("click",function(){
			if(hash.hash.ischange('center_define_house_numer',$(':first',$$)) === true){
				var d = dialog({
							title: '提示信息',
							content:'数据已发生修改，确认取消？',
							okValue: '确定',
							ok: function () {
								d.close();
								//关闭当前标签
								var tag = WindowTag.getCurrentTag();
								WindowTag.closeTag(tag.find('>a:first').attr('url'));
							},
							cancelValue: '取消',
							cancel: function () {
								
							}
						});
						d.showModal();
			}else{
				//关闭当前标签
				var tag = WindowTag.getCurrentTag();
				WindowTag.closeTag(tag.find('>a:first').attr('url'));
			}
		});
		//验证表单
		that.checkForm();
		//填写楼层编号验证
		that.checkVal();
	}
});