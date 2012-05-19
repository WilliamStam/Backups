/*
 * Date: 2012/05/15 - 12:48 PM
 */
$(document).ready(function(){
	var w = $(window).height();
	var p = $("#pane").outerHeight();

	var t = (w - p)/2;
	t = (t>0)?t:0;

	//console.log(t);

	$("#pane").css("margin-top",t).fadeIn(500);
});