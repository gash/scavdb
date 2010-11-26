<?php
require_once('snaf/all.php');

snaf_load_components(array('status','layout'));

$page = isset($_REQUEST['id'])?$_REQUEST['id']:false;
$status = isset($_REQUEST['status'])?$_REQUEST['status']:false;

$status = get_status_data($_REQUEST);
$title = 'Statuses';

$layout = array();
$layout['TITLE'] = $title;
$itemlist['TITLE'] = $layout['TITLE'];
$layout['BODY'] = array($status);

$top_layout = array('BODY'=>array($layout));

echo build_layout_html($layout);

?>