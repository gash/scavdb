<?php
require_once('snaf/all.php');
require_once('lib/messages.inc.php');

snaf_load_components(array('people','layout'));

$request = $_REQUEST;
if ($request['sig'] && $request['lookup']) {
	$person = people::sig_lookup($request['sig']);
	if ($person) $request['id'] = $person['person_id'];
}

if ($request['id'] && $request['add_tags']) {
	tags::add_tag('people', $request['id'], explode(' ', implode(' ', $request['add_tags'])));
	snaf_bounce();
}

if ($request['id'] && $request['comment'] && !empty($request['comment'])) {
	try {
		list($id, $sig, $text) = array($request['id'],$request['sig'],form2comment($request['comment']));
		comments::add_comment('people', $id, $sig, $text);
		messages::send($sig, $id, 'New comment on your profile', $text, '');
		$bounce = true;
	} catch(Exception $e) {
		snaf_add_error($e->getMessage());
	}
}

$data = get_people_data($request);
if ($bounce) snaf_bounce();

//var_dump($data);
$layout = array();
$layout['TITLE'] = 'People'; 
$layout['BODY'] = array($data);

$top_layout = array('BODY'=>array($layout));

echo build_layout_html($layout);

?>
