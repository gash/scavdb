<?php
require_once('snaf/all.php');

snaf_load_components(array('regcta','layout'));

$regcta = get_regcta_data($_REQUEST);

$layout = array();
$layout['TITLE'] = 'Home';
$layout['BODY'] = array($regcta);

$top_layout = array('BODY'=>array($layout));

echo build_layout_html($layout);

?>
