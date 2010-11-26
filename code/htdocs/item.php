<?php
require_once('snaf/all.php');

snaf_load_components(array('item','comment','layout'));

$request = $_REQUEST;
if ($request['id']=='') unset($request['id']);
if ($request['save']) $request['method'] = 'save';

if ($request['add_tags']) {
	items::tag_item($request['id'], explode(' ', implode(' ', $request['add_tags'])));
	snaf_bounce();
}
try {
  $info = array();
  if (!empty($request['change_status'])) {
    $item = items::get_info($request['id'], true);
    //    var_dump($item);
    //    exit;
    $oldstatus = $item['status'];
    $newstatus = $request['change_status'];
    if ($oldstatus != $newstatus) {
      if (empty($request['comment'])) $request['comment'] = 'Status update';
      $info['status'] = array( 'old' => $oldstatus, 'new' => $newstatus );
      items::update_status($request['id'], $newstatus, $request['sig']);
    }
  }
  if ($request['comment'] && !empty($request['comment'])) {
    list($id,$text,$sig,$type) = array($request['id'],form2comment($request['comment']),$request['sig'],$request['ctype']);
    items::post_item_comment($id,$text,$sig,$type,$request['btitle'],$info);
    $bounce = true;
  }
} catch(Exception $e) {
  snaf_add_error($e->getMessage());
}
if ($file = $_FILES['file']) {
	if ($file['error']) snaf_add_error($file['error']);
	else {
		$name = $file['name'];
		$tmp = $file['tmp_name'];
		$id = (int)($request['id']);
		if (!file_exists("files/$id")) mkdir("files/$id");
		`mv '$tmp' 'files/$id/$name'`;
		$bounce = true;
	}
}
if ($request['edit'] && is_array(items::get_info($request['id']))) {
	if (items::check_access($request['id'], $request['sig'])) {
		$request['method'] = 'edit';
	} else {
		snaf_add_error('You are not authorized to edit this item.');
	}
}
$item = get_item_data($request);
if ($bounce) snaf_bounce();

$layout = array();
$layout['TITLE'] = 'Item #'.$request['id'];
$layout['BODY'] = array($item);

$top_layout = array('BODY'=>array($layout));

echo build_layout_html($layout);

?>
