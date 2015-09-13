define(function(require,exports,module){
	var $ = require('jquery');
	require("selectByM")($);
	require("radio")($);
	require("placeholder")($);
	
	var modelInit = function(){
		
	};
	
	//模块初始化
	exports.inite = function(__html__){
		modelInit(__html__);
	};
	
	
});