<?php

$ERRORS = null;

function snaf_add_error($err) {
	global $ERRORS;
	if (!is_array($ERRORS)) $ERRORS = array();
	$ERRORS[] = $err;
}

function snaf_bounce($url=null) {
	if (!$url) {
		$url = $_SERVER['HTTP_REFERER'];
	}
	header('Location: '.$url);
	exit();
}
?>