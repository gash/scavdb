<?php

/* Set up functions */

function build_ttj_html($data, $section=FALSE) {
	return snaf_build_bottom_up($data, 'ttj');
}

function build_ttj_css($data, $section=FALSE) {
	return snaf_build_bottom_up($data, 'ttj', 'css');
}

function get_ttj_data($request=FALSE) {
    $data = array();
    $data['TIME_REMAINING'] = time();
    return snaf_wrap_data($data, 'ttj', 'ttj');
}

/* Init code goes here */

?>