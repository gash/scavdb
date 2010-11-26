<?php

require_once('lib/tags.inc.php');
require_once('lib/items.inc.php');

/* Set up functions */

function get_tag_glossary() {
  return array('art' => 'Something involving artistic creation',
	       'big' => 'Anything worth a large point total',
	       'bigprojects' => 'Large construction projects',
	       'computer' => 'Involving feats of hackery and programming',
	       'design' => 'Photoshop and so forth',
           'easy' => 'We know what we need, just have some simple work left',
	       'fineprojects' => 'Arts and crafts-style projects',
	       'friday' => 'Item to be completed or event occurring on Friday',
	       'money' => 'Significant finincial investment',
	       'moving' => 'Requires moving large objects',
	       'music' => 'Musical talent or knowledge',
	       'key' => 'People who have the key to HQ',
	       'roadtrip' => 'Nuff said',
           'remote' => 'Can be done outside of Chicago',
	       'scavolympics' => 'ScavOlympics on Saturday',
	       'shopping' => 'Requires a shopping trip',
	       'showcase' => 'Showcase item to be judged early on Sunday by all judges',
	       'thursday' => 'Item to be completed or event occurring on Thursday',
	       'time' => 'An item with a timed deadline',
	       'video' => 'Requires video-recording or editing',
	       'wtf' => 'We have no idea what this means');
}

function build_taginfo_html($data, $section=FALSE) {
  global $specialtags;
	extract($data);
	$vars = array();
	if ($view == 'glossary') {
	  $glossary = array();
	  $tags = get_tag_glossary();
	  foreach ($specialtags as $tag) {
	    $desc = $tags[$tag];
	    $glossary[] = snaf_wrap_data(array('tag' => $tag, 'definition' => $desc), 'tag_definition', 'taginfo');
	  }
	  $vars['tag_glossary'] = $glossary;
	  $vars = snaf_wrap_data($vars, 'taginfo', 'taginfo');
	} else if ($view == 'tag') {
	  $glossary = get_tag_glossary();
	  $desc = $glossary[$tag];
	  $vars['tag_image'] = $desc ? ('/img/tags/' . $tag . '.png') : '/img/tag.png';
	  $vars['tag'] = $tag;
	  $vars['desc'] = $desc;
	  $results = tags::tag_search($tag);
	  $list = array();
	  foreach ($results as $r) {
	    $list[$r['t']][] = $r['i'];
	  }
	  $items = items::get_match(array('tag'=>$tag));
	  foreach ($items as $idx=>$item) {
	    $item['item_id'] = (int)($item['item_id']);
        $item['due'] = items::due_int2string($item['due']);
	    $items[$idx] = snaf_wrap_data($item, 'item', 'itemlist');
	  }
	  $people = people::get_people($list['people']);
	  foreach ($people as $idx=>$person) {	  
	    $people[$idx] = snaf_wrap_data($person, 'row', 'people');
	  }
	  
	  $vars['items'] = $items;
	  $vars['people'] = $people;
	  $vars = snaf_wrap_data($vars, 'view', 'taginfo');
	}
	return snaf_build_bottom_up($vars, 'taginfo');
}

function get_taginfo_data($request=FALSE) {
  extract($request);
  $data = array();
  if ($tag) {
    $data['view'] = 'tag';
    $data['tag'] = $tag;
  } else {
    $data['view'] = 'glossary';
  }
  return snaf_wrap_data($data, 'taginfo', 'taginfo');
}

/* Init code goes here */

?>
