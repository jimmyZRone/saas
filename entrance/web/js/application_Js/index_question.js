//帮助中心
define(function(require,exports){
	exports.inite = function(__html__){
		$('.problem-title a',__html__).click(function(){
			var self = $(this);
			var dataId = self.attr('data-id');
			$('.problem-title a',__html__).removeClass('current');
			self.addClass('current');
			$('.view-con',__html__).html($('.category-data div[data-id='+dataId+']').html());
		});
		$('.category-data img',__html__).css({width:'100%',height:'auto',cursor:'pointer'}).attr('title','双击查看大图');
		$('.view-con',__html__).on('dblclick','img',function(){
			window.open(this.src);
		});
		$('.problem-title a:first',__html__).click();
	}
});