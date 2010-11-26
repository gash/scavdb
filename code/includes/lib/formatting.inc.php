<?php

$specialtags = explode(' ', 'wtf music computer scavolympics fineprojects bigprojects video roadtrip big art showcase design thursday friday time shopping moving money remote easy');
sort($specialtags);

function float2num($f) {
	$a = intval($f);
	if ($f == 0) $a = '';
	return $a;
}

function num2float($n) {
	return $n;
}

function ts2interval($ts) {
	$diff = time() - $ts;
	if ($diff < 60) {
		$unit = 'second';
	} else {
		$diff /= 60;
		if ($diff < 60) {
			$unit = 'minute';
		} else {
			$diff /= 60;
			if ($diff < 24) {
				$unit = 'hour';
			} else {
				$diff /= 24;
				$unit = 'day';
			}
		}
	}
	$diff = intval($diff);
	$time = $diff.' '.$unit.(($diff==1)?'':'s').' ago';
	$time .= " @ " . date("g:ia D", $ts - 3600) . " CDT";
	return $time;
}

function usercard($user) {
	if (!is_array($user)) {
		if (is_numeric($user)) {
			$user = people::get_basic_info($user);
		} else {
			$search = people::simple_search('nickname', $user);
			if (count($search)<1) return 'User not found.';
			$user = $search[0];
		}
	}
	return "<span class='nickname'>{$user['nickname']}</span> [<a href='/people.php?id={$user['person_id']}'>{$user['name']}</a>]";
}

function text2markup($text) {
	$markup = $text;
	$markup = preg_replace('/(item\s+#?(\d+))/i', '<a href="/item.php?id=$2">$1</a>', $markup);
	$markup = preg_replace('/<(\d+?)>/e', 'usercard("$1")', $markup);
	$markup = preg_replace('^&lt;(/?\w)&gt;^', '<$1>', $markup);
	$markup = preg_replace('/\b_([^_]+)_\b/', '<em>$1</em>', $markup);
	$markup = preg_replace('/\*([^\*]+)\*/', '<strong>$1</strong>', $markup);
	$markup = preg_replace('^\[(/?\w)\]^', '<$1>', $markup);
	$markup = preg_replace('^\(r\)^i', '&reg;', $markup);
	$markup = preg_replace('^\(c\)^i', '&copy;', $markup);
	$markup = preg_replace('^\(tm\)^i', '&trade;', $markup);
	$markup = preg_replace('^\n^', '<br />', $markup);
	$markup = preg_replace('^(http://\S+)^', '<a href="$1">$1</a>', $markup);
	return $markup;
}

function userstub($user) {
	$search = people::simple_search('nickname', $user);
	if (count($search)<1) return false;
	$user = $search[0];
	return "<{$user['person_id']}>";
}

function form2comment($text) {
	$text = preg_replace('/&lt;(.+?)&gt;/e', '(($user=userstub("$1"))?$user:"&lt;$1&gt;")', $text);
	return $text;
}

function tag2html($tag,$link=true) {
	global $specialtags;
	if (is_array($tag)) {
		if ($tag['tag']) {
			$tag = $tag['tag'];
		} else {
			$tags = array();
			foreach ($tag as $t) {
				$tags[] = tag2html($t);
			}
			return join(' ',$tags);			
		}
	}
	if (in_array($tag, $specialtags)) {
		$img = '<img src="/img/tags/'.$tag.'.png" />';
	} else {
		$img = '<img src="/img/tag.png" />';
	}
	if ($link) return "<a href='/tags.php?tag=$tag' class='tag'>$img $tag</a>";
	else return "<span class='tag'>$img $tag</span>";
}

?>