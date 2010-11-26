<?php

function get_comment_data($request=FALSE){
	$data = array();
	$data['comments'] = array();
	if ($request['id'] && $request['type']) {
		$comments = comments::get_comments($request['type'], $request['id']);
		foreach ($comments as $comment) {
			if ($comment['ctype']=='b') $comment['type'] = 'broadcast';
			else $comment['type'] = 'normal';
			$comment['time'] = ts2interval($comment['ts']);
			if (!empty($comment['info'])) {
				$comment['info'] = format_comment_info($comment['info']);
			}
			$comment['comment'] = text2markup($comment['comment']);
			$data['comments'][] = snaf_wrap_data($comment, 'comment', 'comment');
		}
	}
	$data['num'] = count($data['comments']);
    if ($data['num']==0) return '';
	return snaf_wrap_data($data, 'list', 'comment');
}

function build_comment_html($data){
	return snaf_build_bottom_up($data, 'comment');
}

function build_comment_css($data){
	return snaf_build_bottom_up($data, 'comment', 'css');
}

function format_comment_info($info) {
  $ret = array();
  if ($info['status']) {
    $statuses = items::get_statuses();
    $status = array('old' => $statuses[$info['status']['old']], 'new' => $statuses[$info['status']['new']]);
    $text = snaf_wrap_data($status, 'info_status', 'comment');
    $ret = snaf_wrap_data(array('text'=>$text), 'info', 'comment');
  }
  return $ret;
}

?>
