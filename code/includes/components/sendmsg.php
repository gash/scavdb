<?php

require_once('lib/people.inc.php');
require_once('lib/messages.inc.php');

function build_sendmsg_html($data){
    return snaf_build_bottom_up($data, 'sendmsg', 'html');
}

function build_sendmsg_css($data){
    return snaf_build_bottom_up($data, 'sendmsg', 'css');
}

function get_sendmsg_data($request){

    if (process_sendmsg_form($request)){
        snaf_add_error("Message sent successfully");
    }else{
        $data = $request;
        $data['ropts'] = get_recipient_options($request);
        return snaf_wrap_data($data,'sendmsg','sendmsg');
    }

}

function get_recipient_options($request){
    $selected = isset($request['recipients']) ? $request['recipients'] : array();
    if (isset($request['pgcapts'])) $selected = people::get_page_captain_ids();
    $everybody = people::get_all_names();

    $opts .= '';
    foreach($everybody as $i=>$person){
        $id = $person['person_id'];
        $name = $person['name'];
        $sel = array_search($id, $selected)===false ? '' : 'selected' ;
        $opts.= "<option value='$id' $sel>$name</option>\n";
    }
    return $opts;
}

function process_sendmsg_form($request){
    if (!isset($request['send'])) return false;

    extract($request);
    $recipients = $request['recipients']; 
    if (!isset($recipients) || !is_array($recipients)){
        snaf_add_error("No recipients specified");
        return false;
    }
    if (count($recipients)>20 && !$spam){
        snaf_add_error("Too many recipients!  Read the blurb in the red box below...");
        return false;
    }

    try{
        foreach($recipients as $i=>$recipient){
            messages::send($sig, $recipient, $subject, $message, $flag);
        }
        return true;
    }catch(Exception $e){
        snaf_add_error($e->getMessage());
        return false;
    }
}

?>
