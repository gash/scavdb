<?php

require_once('lib/items.inc.php');

snaf_load_components(array('tags'));

/* Set up functions */

function build_item_html($data, $section=FALSE) {
	$data['statuses'] = '';
	$statuses = items::get_statuses();
	foreach ($statuses as $s=>$status) {
		if ($s == $data['status']) {
			$v = 'checked="checked" value=""';
		} else {
			$v = "value='$s'";
		}
		$data['statuses'] .= "<label class='status'><input type='radio' name='status' $v /><img src='img/status-$s.png' /> $status</label>";
	}
    $data['statuslabel'] = $statuses[$data['status']];
	$data['update_status'] = '';
	//unset($statuses['Z']);
	//unset($statuses['x']);
	//unset($statuses['n']);
	foreach ($statuses as $s=>$status) {
		if ($s == $data['status']) {
			$v = 'checked="checked" value=""';
		} else {
			$v = "value='$s'";
		}
		$data['update_status'] .= "<label class='status'><input type='radio' name='change_status' $v /><img src='img/status-$s.png' /> $status</label>";
	}
	if ($data[SNAF_SECTION] == 'edit') {
		$users = array();
		foreach($data['users'] as $user) {
			if ($user['status']=='o') $user[SNAF_SECTION]='owner_edit';
			else $user[SNAF_SECTION]='user_edit';
			$users[] = snaf_build_bottom_up($user, 'item');
		}
		if (count($users)) $data['users'] = $users;
		else $data['users'] = '<em>None</em>';

		$files = array();
		foreach ($data['files'] as $file) {
			$files[] = "<label><input type='checkbox' name='deletefile[]' value='$file' /> $file</a></label>";
		}
		if (count($files)) $data['files'] = $files;
		else $data['files'] = '<em>None</em>';

		$tags = array();
		foreach($data['tags'] as $tag) {
			$tags[] = "<label><input type='checkbox' name='removetag[]' value='$tag' />".tag2html($tag,false)."</label>";
		}
		if (count($tags)) $data['tags'] = $tags;
		else $data['tags'] = '<em>None</em>';
	} else {
		$users = array();
		$owners = array();
		$roles = people::get_roles();
		foreach ($data['people'] as $user) {
			$html = "<a href='/people.php?id={$user['person_id']}'>{$user['nickname']}</a>";
			if (is_null($user['status'])) $users[] = $html;
			else $owners[] = $html.' <span class="small">('.$roles[$user['status']].')</span>';
		}
		if (count($owners)==0) {
			$owners = people::get_page_captains($data['page']);
			foreach ($owners as $idx=>$user) {
				$owners[$idx] = "<a href='/people.php?id={$user['person_id']}'>{$user['nickname']}</a>";
			}
		}
		$files = array();
		foreach ($data['files'] as $file) {
			$files[] = "<a href='/files/{$data['item_id']}/$file'><img src='/img/disk.png' /> $file</a>";
		}
		$data['files'] = $files;

		$data['users'] = join(', ',$users);
		$data['owners'] = join(', ',$owners);
		$data['tags'] = build_tags_html($data['tags']);
		$data['description'] = text2markup($data['description']);
        $data['due'] = items::due_int2string($data['due']);
	}
    $data['prev_id'] = $data['item_id']>1 ? (int)$data['item_id']-1 : '';
    $data['next_id'] = (int)$data['item_id']+1;
    $data['prev_label'] = $data['prev_id'] ? '&lt;&lt;'.$data['prev_id'] : '';
    $data['next_label'] = $data['next_id'] ? $data['next_id'].'&gt;&gt;' : '';
	return snaf_build_bottom_up($data, 'item');
}

function build_item_css($data, $section=FALSE) {
	return snaf_build_bottom_up($data, 'item', 'css');
}

function build_item_js($data, $section=FALSE) {
	if ($data['jsfocus']) {
		return 'onload = function(){document.getElementById("'.$data['jsfocus'].'").focus();};';
	}
}

function get_item_data($request=FALSE) {
	$keys = array('item_id', 'page', 'status', 'max_pt', 'points', 'description');
	$data = array();
	foreach ($keys as $key) {
		if (isset($request[$key])) $data[$key] = $request[$key];
	}

	$method = $request['method'];

    //modify or create item (from "edit item" page)
	if ($method == 'save') {

		$data['item_id'] = num2float($data['item_id']);
		try {
            //create item
			if (!isset($request['id'])) {
				items::add($data);
				$request['id'] = $data['item_id'];
				$next = $request['id']+1;
				snaf_bounce("/item.php?id=$next&page={$request['page']}&edit=1");

            //modify item
			} else {
                //construct due time
                if (isset($request['due_day']) 
                  && isset($request['due_hour']) 
                  && isset($request['due_min'])){
                    $due = $request['due_day'] * 24 * 60; //mins in a day
                    $due += $request['due_hour'] * 60;    //mins in hours
                    $due += $request['due_min']; 
                    $data['due'] = $due;
                }

                //save to backend
				if (empty($data['status'])) unset($data['status']);
				items::update($request['id'], $data, $request['sig']);
				foreach ($request['role'] as $idx=>$role) {
					if(!empty($role)) items::update_role($request['id'],$idx,$request['sig'],$role);
				}
				foreach ($request['removetag'] as $tag) {
					items::remove_tag($request['id'],$tag,$request['sig']);
				}
				foreach ($request['deletefile'] as $file) {
					`rm "files/{$request['id']}/$file"`;
				}
			}			
		} catch (Exception $e) {
			unset($request['id']);
			snaf_add_error($e->getMessage());
			$method = 'edit';
		}
	}

	if (isset($request['id'])) {
		$data = items::get_info($request['id']);
		if (!$data['item_id']) $method = 'edit';
	}
	if (isset($request['sub']) && !empty($request['sig'])) {
		people::subscribe_item($request['sig'], $request['id']);
	}
	if ($method == 'edit') {
		if (isset($request['id'])) {
			$data = items::get_info($request['id']);
			$data['jsfocus'] = 'desc-'.$request['id'];
		}
		if (isset($request['page'])) {
			$data['page'] = $request['page'];
		}
		$id = (int)$request['id'];
		if (is_dir("files/$id")) {
			$filenames = chop(`ls "files/$id"`);
			$data['files'] = explode("\n", $filenames);
		}
		$data['sig'] = $request['sig'];
		$data['users'] = items::get_item_participants($request['id']);
		$data['item_id'] = float2num($data['item_id']);
		$data['id'] = $request['id'];
        $data['due'] = get_due_form($data['due']); 
    	return snaf_wrap_data($data, 'edit', 'item');		
	}
	$id = (int)$request['id'];
	if (is_dir("files/$id")) {
		$filenames = chop(`ls "files/$id"`);
		if (!empty($filenames)) {
			$data['files'] = explode("\n", $filenames);			
		}
	}
	$data['comments'] = get_comment_data(array('type'=>'items', 'id'=>$request['id']));
	$data['users'] = items::get_item_participants($request['id']);
	$data['item_id'] = float2num($data['item_id']);
	$data['tags'] = get_tags_data(array('table'=>'items', 'id'=>$request['id']));
   	return snaf_wrap_data($data, 'item', 'item');
}

function get_due_form($duetime){
   $a = items::split_dueint($duetime);
    extract($a); //creates $day, $hour, $minutes

    $vars = array('day_opts'=>'','hour_opts'=>'','min_opts'=>'');

    //day drop down
    $days = items::due_dates();
    foreach($days as $code=>$label){
        $s = $code==$day ? 'SELECTED' : '';
        $vars['day_opts'].= "<option value=\"$code\" $s>$label</option>\n";
    }

    //hour menu
    for($i=0;$i<24;$i++){
        $s = $hour==$i ? 'SELECTED' : '';
        $vars['hour_opts'].= "<option value=\"$i\" $s>$i</option>\n";
    }

    //minute menu, 15-min intervals
    for($i=0;$i<60;$i+=15){
        $s = $minutes==$i ? 'SELECTED' : '';
        $vars['min_opts'].= "<option value=\"$i\" $s>$i</option>\n";
    }

    return snaf_wrap_data($vars, 'due_form', 'item');
}

/* Init code goes here */

?>
