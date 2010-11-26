<?php
require_once('snaf/all.php');

snaf_load_components(array('sendmsg','layout'));

$data = get_sendmsg_data($_REQUEST);
$layout = array();
$layout['TITLE'] = 'Send'; 
$layout['BODY'] = array($data);

$top_layout = array('BODY'=>array($layout));

echo build_layout_html($layout);

?>
