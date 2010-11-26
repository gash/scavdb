<?php
require_once('snaf/all.php');

snaf_load_components(array('events','layout'));

$data = get_events_data($_REQUEST);
$layout = array();
$layout['TITLE'] = 'Events'; 
$layout['BODY'] = array($data);

$top_layout = array('BODY'=>array($layout));

echo build_layout_html($layout);

?>
