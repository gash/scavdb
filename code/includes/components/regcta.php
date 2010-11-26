<?php

require_once('lib/items.inc.php');

function build_regcta_html($data, $section=FALSE) {
	return snaf_build_bottom_up($data, 'regcta');
}

function build_regcta_css($data, $section=FALSE) {
	return snaf_build_bottom_up($data, 'regcta', 'css');
}

function get_regcta_data($request=FALSE) {
    $data = array();
    $data['statuses'] = build_item_statuses();
    $data['additem'] = '';
    if (SHOW_ADDITEM){
        $data['additem'] = snaf_wrap_data(array(), 'additem', 'regcta');
    }
    return snaf_wrap_data($data, 'regcta', 'regcta');
}

function build_item_statuses(){
    $labels = items::get_statuses();
    $stat_a = items::get_status_summary();
	$stat_exp = array(
        'x' => "We've done everything in our power, but it looks impossible",
		'n' => 'Nobody is on this item (this is bad)',
        's' => "Something is blocking our progress (for example: we have no idea what the hell the item means)",
        'A' => 'Someone has "volunteered" to work on it',
        'I' => 'Someone is currently working on it',
        //		'r' => 'This item is on roadtrip',
        'P' => "We've done everything we need to",
        'Z' => 'Page captain has the final item in their possession',
		'total' => 'Total items'
		);
    $out = '';
	$labels['total'] = 'Total';
    foreach($labels as $status=>$label){
		$desc = $stat_exp[$status];
		list($number, $points) = $stat_a[$status];
        $out.= '<tr><td><a href="/page.php?status='.$status.'"><img src="/img/status-'.$status.'.png" /> '.$label.'</a></td><td>'.$number.'</td><td>'.$points.'</td>';
		$out.= "<td><span class='help'>$desc</span></td></tr>";
    }
    return $out;
}


/* Init code goes here */

?>
