<?php

require_once('lib/missions.inc.php');

function build_events_html($data){
    return snaf_build_bottom_up($data, 'events', 'html');
}

function build_events_css($data){
    return snaf_build_bottom_up($data, 'events', 'css');
}

function build_events_js($data){
    return snaf_build_bottom_up($data, 'events', 'js');
}

function get_events_data($request){
    process_events_form($request);

    $data = is_array($request) ? $request : array();
    $events = get_events($request);    
    foreach ($events as $id=>$evt) {
      $events[$id]['description'] = text2markup($evt['description']);
    }
    $data['events'] = $events;
    return snaf_wrap_data($data, 'events', 'events');
}

function process_events_form($request){
    if (!isset($request['post'])) return false;

    extract($request);
    if (empty($description)){
        snaf_add_error('Please enter a description for the event');
        return false;
    }
 
    //mktime ( [int hour [, int minute [, int second [, int month [, int day [, int year
    $ts = mktime($h, $m, 0, 5, $date, 2006); 
    $base = mktime(0, 0, 0, 5, 10, 2006);
    if ($ts < $base){
        snaf_add_error('Invalid date');
        return false;
    }

    try{
        missions::add($sig, $ts, $duration, $priority, $place, $description);
    }catch(Exception $e){ 
        snaf_add_error($e->getMessage());
        return false;
    }
    return true;
}


function get_events($request){
    $events = missions::get_missions();
    foreach($events as $i=>$event){
        $events[$i] = snaf_wrap_data($event, 'event', 'events');
    }
    return $events;
}

?>
