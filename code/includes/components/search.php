<?php

require_once('lib/search.inc.php');

function build_search_html($data){
    return snaf_build_bottom_up($data, 'search', 'html');
}

function build_search_css($data){
    return snaf_build_bottom_up($data, 'search', 'css');
}

function get_search_data($request){
    $top = array('results'=>'','query'=>'');
    $top['results'] = snaf_wrap_data(array(), 'tips','search');

    $query = (isset($request['q']) ? $request['q'] : false);
    $query = trim($query);
    $query = ereg_replace("[ \t]+", ' ', $query);
    
    if (!$query){
        return snaf_wrap_data($top, 'search', 'search');
    }

    //if query is an int, assume it's an item number
    if (is_numeric($query)){
        header('Location: /item.php?id='.$query);
    }

    $search_type = false;

    //special case 'page n' and 'item n'
    $words = explode(' ',$query);
    if ($words[0]=='page' && is_numeric($words[1])){
        header('Location: /page.php?id='.$words[1]);
    }else if (($words[0]=='item' || $words[0]=='items') && is_numeric($words[1])){
        header('Location: /item.php?id='.$words[1]);
    }else if (($words[0]=='people' || $words[0]=='person') && is_numeric($words[1])){
        header('Location: /people.php?id='.$words[1]);
    }

    //see if it's a prefix search (i.e. "command: query")
    $prefix = get_search_prefix($query);
    if ($prefix){
        $search_type = get_prefix_search_type($prefix);
    }    
    
    //tokenize query string
    $tokens = explode(' ',$query);
    if (!count($tokens)) return snaf_wrap_data($top, 'search', 'search');

    $start = microtime(true);
    $results = search::compound_search($tokens, $search_type);
    $num_results = count($results);
    $results = search::get_result_data($results);
    $finish = microtime(true);

    $data = array();
    $data['num_rows'] = $num_results;
    $data['results'] = array();
    $data['time'] = substr(($finish - $start),0,5);
    foreach($results as $i=>$row){
        $row['i'] = $i+1;
		$tagid = null;
		if ($row['_type'] == 'items') { $tagid = $row['item_id']; }
		if ($row['_type'] == 'people') { $tagid = $row['person_id']; }
		if ($tagid) {
			$row['tags'] = tag2html(tags::get_tags($row['_type'], $tagid));
		}
//		$row['tags'] = tags::get_tags($row['_type'], )
        $data['results'][] = snaf_wrap_data($row, $row['_type'].'_row', 'search');
    }
    $data = snaf_wrap_data($data, 'results', 'search');
    $top['results'] = $data;
    $top['query'] = $request['q'];
    return snaf_wrap_data($top, 'search','search');
}


function get_search_prefix(&$query){
    $pos = strpos($query, ':');
    if (!$pos) return false;

    $prefix = substr($query, 0, $pos);
    if (strpos($prefix,' ')!==false) return false;

    $query = substr($query, $pos+1);
    return $prefix;
}


function get_prefix_search_type($prefix){
    $prefixes = array('t'=>'tags', 'tag'=>'tags', 'tags'=>'tags',
                      'p'=>'people', 'peep'=>'people', 'people'=>'people',
                      'person'=>'people','user'=>'people','users'=>'people',
                      'c'=>'comments', 'comment'=>'comments', 'comments'=>'comments',
                      'i'=>'items', 'items'=>'items', 'item'=>'items');
    if (isset($prefixes[$prefix])) return $prefixes[$prefix]; 
    else return false;
}

?>
