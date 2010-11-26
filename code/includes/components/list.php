<?php

/* Dependencies */

require_once('lib/items.inc.php');

/* Set up functions */

function build_list_table_html($data, $statuses){
	$pages = array();
	foreach ($data as $idx=>$page) {
		$page['num'] = $idx;
		if ($idx == 100) $page['name'] = 'ScavOlympics';
		else $page['name'] = 'Page '.$idx;
		$status = array();
		foreach ($statuses as $s=>$name) {
			$status[] = "<td><a href='/page.php?status=$s&id=$idx'>{$page[$s]}</a></td>";
		}
		$page['status'] = $status;
		$captains = array();
		foreach ($page['captains'] as $captain) {
			$captains[] = "<a href='/people.php?id={$captain['person_id']}'>{$captain['nickname']}</a>";
		}
		$page['captains'] = $captains;
		$pages[] = snaf_wrap_data($page, 'page', 'list');
	}
	return $pages;
}

function build_list_html($data, $section=FALSE) {
	$statuses = $data['status'];
	$status = array();
	foreach ($statuses as $s=>$name) {
		$status[] = "<td><a href='/page.php?status=$s'><img src='/img/status-$s.png' /> $name</a></td>";
	}
	$data['status'] = $status;
	$data['pages'] = build_list_table_html($data['pages'], $statuses);
	return snaf_build_bottom_up($data, 'list');
}

function get_list_data($request=FALSE) {
    $data = array();
	$data['status'] = items::get_statuses();
	$data['pages'] = items::get_pages($request['points']);
    return snaf_wrap_data($data, 'list', 'list');
}

/* Init code goes here */


?>
