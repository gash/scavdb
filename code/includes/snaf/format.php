<?php

/* Data formatting functions */

function snaf_build_thyself($data, $component=FALSE, $format='html'){
	if (!is_array($data)) return $data;
	if (!isset($data[SNAF_COMPONENT])) return FALSE;

	$component = $data[SNAF_COMPONENT];
	$function = 'build_'.$component.'_'.$format;
	if (!function_exists($function)) return FALSE;
	$output = $function($data);

	if (SNAF_DEBUG && $format == 'html'){
		$output = '<!-- '.$component.' -->'.$output.'<!-- /'.$component.'-->';
	}
	
	return $output;
}

function snaf_build_bottom_up($data, $component=FALSE, $format='html'){
	if (!is_array($data)) return $data;
	if (isset($data[SNAF_COMPONENT])) $component = $data[SNAF_COMPONENT];
	$section = $data[SNAF_SECTION];
	
	$contents = array();
	foreach ($data as $key=>$val){
		if ($key !== SNAF_COMPONENT && $key !== SNAF_SECTION){
			$contents[$key] = snaf_build_bottom_up($val, $component);
		}
	}

	if (!$component) return FALSE;
	$function = 'get_'.$component.'_'.$format;

	if (function_exists($function)) return $$function($contents, $section);
	return call_user_func('get_'.$format, $contents, $component, $section);
}

/* Private builders.  NOT FOR YOU!!!  Just kidding. */

function get_html($data, $component, $section=FALSE){
	if (!is_array($data)) return $data;
	if (!$section) return implode("\n", $data);
	extract($data);

	$path = 'tpl/'.$component.'/'.$section.'.html';
	return @include($path);
}

function get_css($data, $component, $section=FALSE){
	if (!is_array($data)) return $data;
	if (!$section) return implode("\n", $data);
	extract($data);

	$path = 'tpl/'.$component.'/'.$section.'.css';
	return @include($path);

}

function get_js($data, $component, $section=FALSE){
	if (!is_array($data)) return $data;
	if (!$section) return implode("\n", $data);
	extract($data);

	$path = 'tpl/'.$component.'/'.$section.'.js';
	return @include($path);

}

function get_json($data, $component, $section=FALSE){
	if (!is_array($data)) return "'$data'";
	if (numeric_array($data)) return '[ '.implode(', ', $data).' ]';
	$json = '{ ';
	if ($section) $json = "{ '_section': '".addslashes($section)."', ";
	foreach ($data as $key=>$val) {
		if (is_array($val)) {
			$val = get_json($val, $component);
		} else if (is_int($val)) {
			$val = $val;
		} else {
			$val = str_replace("\n", '\n', $val);
			$val = str_replace("'", "\\'", $val);
			$val = "'$val'";
		}
		$json.= "'$key': $val, ";
	}
	return $json.'}';
}

?>