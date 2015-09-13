define(function(require,exports,module){
  var ajax=require("Ajax");
 var  bindEvt=function($$){
    $(".apply-open-act",$$).off().on("click",function(){
        var  cur=$(this);
        if(!cur.hasClass("clicked")){
           cur.addClass("clicked");
           var tag1= WindowTag.getCurrentTag();
           WindowTag.closeTag(tag1.find('>a:first').attr('url'));//关闭当前tag
           var ctag = WindowTag.getTagByUrlHash(cur.attr("data-url"));
           if(ctag){
              window.WindowTag.selectTag(ctag.find(' > a:first').attr('href'));
              window.WindowTag.loadTag(ctag.find(' > a:first').attr('href'),'get',function(){});
           }else{
              window.WindowTag.openTag("#"+cur.attr("data-url"));
           }
           ajax.doAjax("post","url","",function(json){

           });
        }
    });
 }
  exports.inite=function(_html_,data){
     bindEvt(_html_,data);
   }
});
