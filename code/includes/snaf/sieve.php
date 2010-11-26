<?php
/* SIEVE - Snaf Input Entry Validation Engine
 * Clean things up *before* they get to the user.
 */

/**
 * Strip unallowed html tags from the text
 *
 * @param $string  [string]  Text to strip
 * @return [string]  The stripped result
 */
function sieve_strip_html($string) {
	return htmlentities($string);
}

/**
 * Normalize magic quotes
 *
 * @param $string  [string]  Text to normalize
 * @return [string]  The normalize result
 */
function sieve_normalize_quotes($string) {
	return get_magic_quotes_gpc()?stripslashes($string):$string;
}

/**
 * Strip unallowed html tags from the text
 *
 * @return [none]
 */
function sieve_filter_superglobals() {
    global $_RAW_REQUEST, $_RAW_GET, $_RAW_POST;
    
    $_RAW_REQUEST = $_REQUEST;
    $_RAW_GET = $_GET;
    $_RAW_POST = $_POST;

	foreach ($_REQUEST as $key=>$val) {
		if (!is_array($val)) $val = sieve_normalize_quotes(sieve_strip_html($val));
		$_REQUEST[$key] = $val;
	}
	foreach ($_GET as $key=>$val) {
		if (!is_array($val)) $val = sieve_normalize_quotes(sieve_strip_html($val));
		$_GET[$key] = $val;
	}
	foreach ($_POST as $key=>$val) {
		if (!is_array($val)) $val = sieve_normalize_quotes(sieve_strip_html($val));
		$_POST[$key] = $val;
	}
}

sieve_filter_superglobals();

?>