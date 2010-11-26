<?php

/* Set up functions */

function build_tags_html($data, $section=FALSE) {
	global $specialtags;
	$vars = array();
	$vars['tags'] = array();
	foreach ($data['tags'] as $tag) {
		$vars['tags'][] = tag2html($tag['tag']);
	}
	if ($section == 'short') return join(' ', $vars['tags']);
	if (count($data['tags'])==0) {
		$vars['tags'] = 'none';
	}
	$random = $specialtags;
	for ($i=1; $i<4; $i++) {
		$n = rand(0, count($random)-1);
		$t = array_splice($random,$n,1);
		$vars["random$i"] = $t[0];
	}
	$vars['specialtags'] = '';
	foreach ($specialtags as $tag) {
		$vars['specialtags'] .= "<option value='$tag'>$tag</option>\n";
	}
	$vars = snaf_wrap_data($vars, 'tags', 'tags');
	return snaf_build_bottom_up($vars, 'tags');
}

function get_tags_data($request=FALSE) {
	extract($request);
    $data = array();
	if ($method=='add') {
		if (!is_array($tags)) {
			$tags = explode(' ', $tags);
		}
		tags::add_tag($table, $id, $tags);
	}
	$data['tags'] = tags::get_tags($table, $id);
    return snaf_wrap_data($data, 'tags', 'tags');
}

/* Init code goes here */

?>