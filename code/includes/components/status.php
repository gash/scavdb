<?php

/* Dependencies */

require_once('lib/people.inc.php');

/* Set up functions */

function build_status_html($data, $section=FALSE) {
/*	$people = $data['people'];
	$data['people'] = array();
	
	foreach ($people as $person) {
		$person['tags'] = build_tags_html($person, 'short');
		$data['people'][] = snaf_wrap_data($person, 'row', 'people');
	}
	$data['tags'] = build_tags_html($data['tags']);
	if ($data['items']) {
		$items = array();
		foreach ($data['items'] as $item) {
			$id = float2num($item['item_id']);
			$name = ($item['role'])?"<b>$id</b>":$id;
			$items[] = "<a href='/item.php?id=$id'><img src='/img/status-{$item['status']}.png' />$name</a>";
		}
		$data['items'] = $items;
	}*/
	
	return snaf_build_bottom_up($data, 'status');
}

function get_status_data($request=FALSE) {
	extract($request);
	
    $data = array();
    if ($status){
      if ($sig) {
	try {
	  people::set_status($sig, $status);
	} catch(Exception $e) {
	  snaf_add_error($e->getMessage());
	}
      } else {
	$data['status'] = $status;
      }
    }

    $rows = people::get_status();
    foreach($rows as $i=>$row){
        $row['rtime'] = ts2interval($row['written']);
        $rows[$i] = snaf_wrap_data($row, 'update', 'status');
    }
    $data['statuses'] = $rows;

    return snaf_wrap_data($data, 'status', 'status');
}

/* Init code goes here */


?>
