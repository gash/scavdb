<?php

require_once('lib/items.inc.php');

function build_recent_html($data){
    return snaf_build_bottom_up($data, 'recent', 'html');
}

function build_recent_css($data){
    return snaf_build_bottom_up($data, 'recent', 'css');
}

function get_recent_data($request){
    if (is_array($request)) extract($request);

    $data = array('results'=>'');    

    if (!$mode) $mode = 'i';
    if ($mode=='i'){
        $data['results'] = get_recent_items_data($request);
        $data['isel'] = 'checked';
    }else if ($mode=='c'){
        $data['results'] = get_recent_comments_data($request);
        $data['csel'] = 'checked';
    }else if ($mode=='b'){
        $data['results'] = get_recent_comments_data($request,true);
        $data['bsel'] = 'checked';
    }
    $data['page'] = isset($request['page']) ? $request['page'] : '';
    return snaf_wrap_data($data, 'recent', 'recent');
}

function get_recent_items_data($request){
    $page = !empty($request['page']) ? $request['page'] : false;
    $rows = items::get_page($page, 'mtime DESC', 0, 20);
    foreach($rows as $i=>$row){
        $row['time'] = ts2interval($row['mtime']);
        $rows[$i] = snaf_wrap_data($row, 'item', 'recent');
    } 
    $data['label'] = 'Recently Updated Items';
    $data['rows'] = $rows; 
    return snaf_wrap_data($data, 'results', 'recent');
}

function get_recent_comments_data($request,$bcast=false){
    $rows = comments::get_comments('items', false, $bcast);
    foreach($rows as $i=>$row){
        $row['rtime'] = ts2interval($row['ts']);
        $rows[$i] = snaf_wrap_data($row, 'comment', 'recent');
    }
    if ($bcast){
        $data['label'] = 'Recent Broadcasts';
        $data['rows'] = $rows;
    }else{
        $data['label'] = 'Recent Comments';
        $data['rows'] = $rows;
    }
    return snaf_wrap_data($data, 'results', 'recent');
}

?>
