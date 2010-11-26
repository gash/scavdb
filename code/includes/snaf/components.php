<?php

/* Component logic */

function snaf_load_components($components){
	if (!is_array($components)) $components = array($components);
	foreach ($components as $component){
		require_once('components/'.$component.'.php');
	}
}

/* Data wrapping functions */

define('SNAF_SECTION', '__section');
define('SNAF_COMPONENT', '__component');

function snaf_wrap_data($data, $section=FALSE, $component=FALSE){
	if ($section !== FALSE) $data[SNAF_SECTION] = $section;
	if ($component !== FALSE) $data[SNAF_COMPONENT] = $component;
	return $data;
}

function snaf_unwrap_data($data){
	if (array_key_exists(SNAF_SECTION)) unset($data[SNAF_SECTION]);
	if (array_key_exists(SNAF_COMPONENT)) unset($data[SNAF_COMPONENT]);
	return $data;
}

?>