if(Drupal.jsEnabled){$(function(){$("span."+Drupal.settings.spamspan.m).each(function(_1){var _2=($("span."+Drupal.settings.spamspan.u,this).text()+"@"+$("span."+Drupal.settings.spamspan.d,this).text()).replace(/\s+/g,"").replace(/[\[\(\{]?[dD][oO0][tT][\}\)\]]?/g,".");var _3=$("span."+Drupal.settings.spamspan.t,this).text();$(this).after($("<a></a>").attr("href","mailto:"+_2).html(_3?_3:_2).addClass("spamspan")).remove();});});}