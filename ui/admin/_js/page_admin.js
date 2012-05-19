/*
 * Date: 2012/05/15 - 12:48 PM
 */
$(document).ready(function(){
	$("input:checkbox").change(function(){

		$("input:checkbox:checked").not(this).removeAttr("checked");
	});
});