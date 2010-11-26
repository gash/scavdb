<?php
require_once('snaf/all.php');

snaf_load_components(array('recent','layout'));

$data = get_recent_data($_REQUEST);
//print_r($data);
$layout = array();
$layout['TITLE'] = 'Recent'; 
$layout['BODY'] = array($data);

$top_layout = array('BODY'=>array($layout));

echo build_layout_html($layout);

?>
