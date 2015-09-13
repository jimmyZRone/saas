/*
*表单模块调用
*/
seajs.use('module/formparam/formParam.js',function(form){
			//自定义函数说明
			//默认:不传
			//	getOtherparam---需要增加自定义参数
			//	ajaxCallback---自定义异步回调函数
			var func={
				//表单增加自定义参数
				 getOtherparam:function(){
				 	return "name=asdsadasd"+"&userpwd=asdasdasda"+"&age=19";//自定义参数
				 },
				 //自定义异步回调函数
	 	  	     ajaxCallback:function(status){
	 	  	     	alert("异步请求返回结果状态:"+status.status);
	 	  	     },
	 	  	     //数据未作修改直接执行关闭页面或者回到指定页面
	 	  	     pagecallBack:function(){
	 	  	     	//...页面关闭方法待定
	 	  	     }
			};
//			form.init(func);//需要自定义
			form.init();//默认
});