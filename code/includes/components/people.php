<?php

/* Dependencies */

require_once('lib/people.inc.php');

snaf_load_components(array('comment','tags'));

/* Set up functions */

function build_people_html($data, $section=FALSE) {
	$people = $data['people'];
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
	}
	
	return snaf_build_bottom_up($data, 'people');
}

function build_people_css($data, $section=FALSE) {
	return snaf_build_bottom_up($data, 'people', 'css');
}

function get_people_data($request=FALSE) {
	extract($request);
	
    $data = array();
	if ($id) {
		$people = array(people::get_basic_info($id));
	} else {
		$people = people::get_all_names('email,cell,skillz,role,nickname,name,status,person_id');
	}
	
	
	if (count($people)>1) {
		$data['num'] = count($people);
		$data['people'] = array();
		foreach ($people as $idx=>$person) {
			$person['tags'] = tags::get_tags('people', $person['person_id']);
			$data['people'][] = $person;
		}

    	return snaf_wrap_data($data, 'people', 'people');
	} else {
		$person = $people[0];
		$person['comments'] = get_comment_data(array('type'=>'people', 'id'=>$request['id']));
		$person['tags'] = get_tags_data(array('table'=>'people', 'id'=>$person['person_id']));
		$person['items'] = people::get_items($request['id']);

		if ($request['forgot']) {
			$success = people::forgot_sig($id);
			if ($success) {
			  snaf_add_error("Sent an email to $success!  Check your email, if you don't get anything in a couple minutes, contact <a href='/people.php?id=1'>Yitz</a>.");
			} else {
			  snaf_add_error("Error sending email!  Contact <a href='/people.php?id=1'>Yitz</a>.");
			}
		}
		
		return snaf_wrap_data($person, 'person', 'people');
	}
}

/* Init code goes here */


?>
