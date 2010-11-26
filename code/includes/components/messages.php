<?php

require_once('lib/messages.inc.php');

function build_messages_html($data){
    return snaf_build_bottom_up($data, 'messages', 'html');
}

function build_messages_css($data){
    return snaf_build_bottom_up($data, 'messages', 'css');
}

function build_messages_js($data){
    return snaf_build_bottom_up($data, 'messages', 'js');
}

function get_messages_data($request){
    $sig = !empty($request['sig']) ? $request['sig'] : '';

    if (!$sig){
        return snaf_wrap_data(array(), 'sig', 'messages');
    }

    $mode = !empty($request['mode']) ? $request['mode'] : 'in';

    $data = array('sig'=>$sig);
    if ($mode=='in'){
        $data['messages'] = get_messages_inbox($sig,$request);
    }else{
        $data['messages'] = get_messages_outbox($sig,$request);
    }
    return snaf_wrap_data($data, 'messages', 'messages');
}


function get_messages_inbox($sig,$request){
    if (isset($request['s'])){
        process_messages_form($request);
    }

    $msgs = messages::get_messages($sig);
    foreach($msgs as $i=>$msg){
        $msg['message'] = text2markup($msg['message']);
        $msg['disp'] = empty($msg['tag']) ? '' : 'none';
        $msg['toggle'] = empty($msg['disp']) ? 'Hide' : 'Show';
        $msg['flags'] = get_messages_select($msg['tag']);
        $msgs[$i] = snaf_wrap_data($msg, 'message','messages'); 
    }
    $msgs[$i]['last'] = 'last';
    return $msgs;
}

function process_messages_form($request){
    global $_RAW_POST, $_RAW_REQUEST;

    if (!is_array($_RAW_POST['flags']) || !is_array($_RAW_POST['prevflags'])){
        return false;
    }
    if (empty($request['sig'])) return false;

    $new = $_RAW_POST['flags'];
    $old = $_RAW_POST['prevflags'];
    $sig = $request['sig'];

    foreach($old as $id=>$oldval){
        if (!isset($new[$id])) continue;
        $newval = $new[$id];
        if ($newval==$oldval) continue;
        messages::flag_message($sig, $id, $newval);
    }
    return true;
}


function get_messages_outbox($sig,$request){

}

function get_messages_select($cur){
    static $flags;

    if (!isset($flags)) $flags = messages::get_flags();

    $out = '';
    foreach($flags as $code=>$label){
        $sel = $code==$cur ? 'selected':'';
        $out.="<option value='$code' $sel>$label</option>\n"; 
    }
    return $out;
}


?>
