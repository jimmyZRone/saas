 /**
 * @license jQuery paging plugin v1.2.0 23/06/2014
 * http://www.xarg.org/2011/09/jquery-pagination-revised/
 *
 * Copyright (c) 2011, Robert Eisele (robert@xarg.org)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 **/
define(function(require, exports, module) {
//  (function(a) {
//      a.PaginationCalculator = function(b, c) {
//          this.maxentries = b;
//          this.opts = c
//      };
//      a.extend(a.PaginationCalculator.prototype, {numPages: function() {
//              return Math.ceil(this.maxentries / this.opts.items_per_page)
//          },getInterval: function(f) {
//              var d = Math.floor(this.opts.num_display_entries / 2);
//              var e = this.numPages();
//              var c = e - this.opts.num_display_entries;
//              var g = f > d ? Math.max(Math.min(f - d, c), 0) : 0;
//              var b = f > d ? Math.min(f + d + (this.opts.num_display_entries % 2), e) : Math.min(this.opts.num_display_entries, e);
//              return {start: g,end: b}
//          }});
//      a.PaginationRenderers = {};
//      a.PaginationRenderers.defaultRenderer = function(b, c) {
//          this.maxentries = b;
//          this.opts = c;
//          this.pc = new a.PaginationCalculator(b, c)
//      };
//      a.extend(a.PaginationRenderers.defaultRenderer.prototype, {createLink: function(b, e, d) {
//              var f, c = this.pc.numPages();
//              b = b < 0 ? 0 : (b < c ? b : c - 1);
//              d = a.extend({text: b + 1,classes: ""}, d || {});
//              if (b == e) {
//                  f = a("<span class='current'>" + d.text + "</span>")
//              } else {
//                  f = a("<a>" + d.text + "</a>").attr("href", this.opts.link_to.replace(/__id__/, b))
//              }
//              if (d.classes) {
//                  f.addClass(d.classes)
//              }
//              f.data("page_id", b);
//              return f
//          },appendRange: function(c, f, g, b, e) {
//              var d;
//              for (d = g; d < b; d++) {
//                  this.createLink(d, f, e).appendTo(c)
//              }
//          },getLinks: function(h, e) {
//              var f, b, c = this.pc.getInterval(h), g = this.pc.numPages(), d = a("<div class='pagination'></div>");
//              if (this.opts.first_text && (h > 0 || this.opts.prev_show_always)) {
//                  d.append(this.createLink(0, h, {text: this.opts.first_text,classes: "first"}))
//              }
//              if (this.opts.prev_text && (h > 0 || this.opts.prev_show_always)) {
//                  d.append(this.createLink(h - 1, h, {text: this.opts.prev_text,classes: "prev"}))
//              }
//              if (c.start > 0 && this.opts.num_edge_entries > 0) {
//                  b = Math.min(this.opts.num_edge_entries, c.start);
//                  this.appendRange(d, h, 0, b, {classes: "sp"});
//                  if (this.opts.num_edge_entries < c.start && this.opts.ellipse_text) {
//                      jQuery("<span>" + this.opts.ellipse_text + "</span>").appendTo(d)
//                  }
//              }
//              this.appendRange(d, h, c.start, c.end);
//              if (c.end < g && this.opts.num_edge_entries > 0) {
//                  if (g - this.opts.num_edge_entries > c.end && this.opts.ellipse_text) {
//                      jQuery("<span>" + this.opts.ellipse_text + "</span>").appendTo(d)
//                  }
//                  f = Math.max(g - this.opts.num_edge_entries, c.end);
//                  this.appendRange(d, h, f, g, {classes: "ep"})
//              }
//              if (this.opts.next_text && (h < g - 1 || this.opts.next_show_always)) {
//                  d.append(this.createLink(h + 1, h, {text: this.opts.next_text,classes: "next"}))
//              }
//              if (this.opts.last_text && (h < g - 1 || this.opts.next_show_always)) {
//                  d.append(this.createLink(g, h, {text: this.opts.last_text,classes: "last"}))
//              }
//              a("a", d).click(e);
//              return d
//          }});
//      a.fn.pagination = function(i, b) {
//          b = jQuery.extend({items_per_page: 10,num_display_entries: 11,current_page: 0,num_edge_entries: 0,link_to: "#",prev_text: "Prev",next_text: "Next",ellipse_text: "...",prev_show_always: true,next_show_always: true,renderer: "defaultRenderer",load_first_page: false,callback: function() {
//                  return false
//              }}, b || {});
//          var c = this, f, k, e;
//          function d(m) {
//              var n, l = a(m.target).data("page_id"), o = g(l);
//              if (!o) {
//                  m.stopPropagation()
//              }
//              return o
//          }
//          function g(l) {
//              c.data("current_page", l);
//              k = f.getLinks(l, d);
//              c.empty();
//              k.appendTo(c);
//              var m = b.callback(l, c);
//              return m
//          }
//          e = b.current_page;
//          c.data("current_page", e);
//          i = (!i || i < 0) ? 1 : i;
//          b.items_per_page = (!b.items_per_page || b.items_per_page < 0) ? 1 : b.items_per_page;
//          if (!a.PaginationRenderers[b.renderer]) {
//              throw new ReferenceError("Pagination renderer '" + b.renderer + "' was not found in jQuery.PaginationRenderers object.")
//          }
//          f = new a.PaginationRenderers[b.renderer](i, b);
//          var h = new a.PaginationCalculator(i, b);
//          var j = h.numPages();
//          c.bind("setPage", {numPages: j}, function(m, l) {
//              if (l >= 0 && l < m.data.numPages) {
//                  g(l);
//                  return false
//              }
//          });
//          c.bind("prevPage", function(l) {
//              var m = a(this).data("current_page");
//              if (m > 0) {
//                  g(m - 1)
//              }
//              return false
//          });
//          c.bind("nextPage", {numPages: j}, function(l) {
//              var m = a(this).data("current_page");
//              if (m < l.data.numPages - 1) {
//                  g(m + 1)
//              }
//              return false
//          });
//          k = f.getLinks(e, d);
//          c.empty();
//          k.appendTo(c);
//          if (b.load_first_page) {
//              b.callback(e, c)
//          }
//      }
//  })(jQuery);
(function(b){b.PaginationCalculator=function(a,d){this.maxentries=a;this.opts=d};b.extend(b.PaginationCalculator.prototype,{numPages:function(){return Math.ceil(this.maxentries/this.opts.items_per_page)},getInterval:function(i){var k=Math.floor(this.opts.num_display_entries/2);var j=this.numPages();var l=j-this.opts.num_display_entries;var h=i>k?Math.max(Math.min(i-k,l),0):0;var a=i>k?Math.min(i+k+(this.opts.num_display_entries%2),j):Math.min(this.opts.num_display_entries,j);return{start:h,end:a}}});b.PaginationRenderers={};b.PaginationRenderers.defaultRenderer=function(a,d){this.maxentries=a;this.opts=d;this.pc=new b.PaginationCalculator(a,d)};b.extend(b.PaginationRenderers.defaultRenderer.prototype,{createLink:function(a,h,i){var g,j=this.pc.numPages();a=a<0?0:(a<j?a:j-1);i=b.extend({text:a+1,classes:""},i||{});if(a==h){g=b("<span class='current'>"+i.text+"</span>")}else{g=b("<a>"+i.text+"</a>").attr("href",this.opts.link_to.replace(/__id__/,a))}if(i.classes){g.addClass(i.classes)}g.data("page_id",a);return g},appendRange:function(l,i,h,a,j){var k;for(k=h;k<a;k++){this.createLink(k,i,j).appendTo(l)}},getLinks:function(i,l){var k,a,n=this.pc.getInterval(i),j=this.pc.numPages(),m=b("<div class='pagination'></div>");if(this.opts.first_text&&(i>0||this.opts.prev_show_always)){m.append(this.createLink(0,i,{text:this.opts.first_text,classes:"first"}))}if(this.opts.prev_text&&(i>0||this.opts.prev_show_always)){m.append(this.createLink(i-1,i,{text:this.opts.prev_text,classes:"prev"}))}if(n.start>0&&this.opts.num_edge_entries>0){a=Math.min(this.opts.num_edge_entries,n.start);this.appendRange(m,i,0,a,{classes:"sp"});if(this.opts.num_edge_entries<n.start&&this.opts.ellipse_text){jQuery("<span>"+this.opts.ellipse_text+"</span>").appendTo(m)}}this.appendRange(m,i,n.start,n.end);if(n.end<j&&this.opts.num_edge_entries>0){if(j-this.opts.num_edge_entries>n.end&&this.opts.ellipse_text){jQuery("<span>"+this.opts.ellipse_text+"</span>").appendTo(m)}k=Math.max(j-this.opts.num_edge_entries,n.end);this.appendRange(m,i,k,j,{classes:"ep"})}if(this.opts.next_text&&(i<j-1||this.opts.next_show_always)){m.append(this.createLink(i+1,i,{text:this.opts.next_text,classes:"next"}))}if(this.opts.last_text&&(i<j-1||this.opts.next_show_always)){m.append(this.createLink(j,i,{text:this.opts.last_text,classes:"last"}))}b("a",m).click(l);return m}});b.fn.pagination=function(m,t){t=jQuery.extend({items_per_page:10,num_display_entries:11,current_page:0,num_edge_entries:0,link_to:"#",prev_text:"Prev",next_text:"Next",ellipse_text:"...",prev_show_always:true,next_show_always:true,renderer:"defaultRenderer",load_first_page:false,callback:function(){return false}},t||{});var s=this,p,a,q;function r(c){var f,d=b(c.target).data("page_id"),e=o(d);if(!e){c.stopPropagation()}return e}function o(d){s.data("current_page",d);a=p.getLinks(d,r);s.empty();a.appendTo(s);var c=t.callback(d,s);return c}q=t.current_page;s.data("current_page",q);m=(!m||m<0)?1:m;t.items_per_page=(!t.items_per_page||t.items_per_page<0)?1:t.items_per_page;if(!b.PaginationRenderers[t.renderer]){throw new ReferenceError("Pagination renderer '"+t.renderer+"' was not found in jQuery.PaginationRenderers object.")}p=new b.PaginationRenderers[t.renderer](m,t);var n=new b.PaginationCalculator(m,t);var l=n.numPages();s.bind("setPage",{numPages:l},function(c,d){if(d>=0&&d<c.data.numPages){o(d);return false}});s.bind("prevPage",function(d){var c=b(this).data("current_page");if(c>0){o(c-1)}return false});s.bind("nextPage",{numPages:l},function(d){var c=b(this).data("current_page");if(c<d.data.numPages-1){o(c+1)}return false});a=p.getLinks(q,r);s.empty();a.appendTo(s);if(t.load_first_page){t.callback(q,s)}}})(jQuery);



});
