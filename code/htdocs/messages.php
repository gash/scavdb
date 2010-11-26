<?php
require_once('snaf/all.php');

snaf_load_components(array('recent','messages','layout'));

$data = get_messages_data($_REQUEST);
$recent = get_recent_data(array('mode'=>'b'));
//print_r($data);
$layout = array();
$layout['TITLE'] = 'Messages'; 
$layout['BODY'] = array($data,$recent);

$top_layout = array('BODY'=>array($layout));

echo build_layout_html($layout);

?>
