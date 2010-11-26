<?php

/* Set up functions */

function build_layout_html($data, $section=FALSE) {
	global $ERRORS;

	$tpl = array();
	$tpl['TITLE'] = $data['TITLE'];
	$tpl['CSS'] = '';
	$tpl['JS'] = '';
	if (is_array($ERRORS)) {
		$tpl['ERRORS'] = "<div class='errors'>".join("<br />\n",$ERRORS)."</div>";
	}
    $tpl['HEADER'] = snaf_wrap_data($data['HEADER'], 'header', 'layout');
	$tpl['BODY'] = '';

	foreach ($data['BODY'] as $component){
		$tpl['CSS'].= snaf_build_thyself($component, FALSE, 'css');
		$tpl['JS'].= snaf_build_thyself($component, FALSE, 'js');
		$tpl['BODY'].= snaf_build_thyself($component);
	}
	$tpl = snaf_wrap_data($tpl, 'layout', 'layout');

	return snaf_build_bottom_up($tpl, 'layout');
}

function build_layout_css($data=false){
    return snaf_build_bottom_up($data, 'layout', 'css');
}

function get_layout_data($request=FALSE) {
    return snaf_wrap_data($request, 'layout', 'layout');
}

/* Init code goes here */

?>
