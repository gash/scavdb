<?php
require_once('snaf/all.php');

snaf_load_components(array('reg'));

/*
$data = get_reg_data($_REQUEST);
print_r($data);
echo build_reg_html($data);
*/

snaf_load_components(array('reg','layout'));

$data = get_reg_data($_REQUEST);
//print_r($data);
$layout = array();
$layout['TITLE'] = 'Profile';
$layout['BODY'] = array($data);

$top_layout = array('BODY'=>array($layout));

echo build_layout_html($layout);

?>
