<?php

require_once('lib/db.inc.php');
require_once('lib/items.inc.php');
require_once('lib/people.inc.php');
require_once('lib/comments.inc.php');
require_once('lib/tags.inc.php');

class search{

    /**
     * Add data to search index
     *
     * @param  $table [string] data type
     * @param  $id    [int]  data id
     * @param  $data  [string]  data to index
     * @param  $append [bool] Append instead of update (optional def=false) 
     */
    function index($table, $id, $data, $append=false){
        $table = db_sanitize($table);
        $id = db_sanitize($id);
        $data = db_sanitize($data);
        $data = strtolower($data);
        
        $query = 'INSERT INTO search_index (datatype, data_id, data) ';
        $query.= " VALUES ('$table', '$id', '$data')";
        if ($append){
            $query.= " ON DUPLICATE KEY UPDATE data=concat(data,'$data')";
        }else{
            $query.= " ON DUPLICATE KEY UPDATE data='$data'";
        }
        $r = db_query($query);
        if (!$r) trigger_error("Index query failed: ".db_error().'<br>'.$query, E_USER_WARNING);
        return $r;
    }

   
    /**
     * Blow index
     *
     * @param  $table [string] data/object type
     * @param  $id    [int]    data/object ID
     * @return meh
     */
    function delete($table, $id){
        $table = db_sanitize($table);
        $id = db_sanitize($id);
        $query = "DELETE FROM search_index WHERE datatype='$table' and data_id='$id'";
        db_query($query);
    }

    /**
     * Search index for a single word
     *
     * @param  $word  [string]
     * @param  $type  [string]  DAta type {comments,items,people} (optional)
     * @return [array] 
     */
    function word_search($word, $type=false){
        $word = db_sanitize($word);
        $word = strtolower($word);
        $query = "SELECT datatype as t,data_id as i,data as d ";
        $query.= " FROM search_index WHERE data like '%$word%' ";
        $query.= ($type ? "and datatype='$type'" : '');
        $query.= ' LIMIT 1000';
        $r = db_query($query);
        return db_fetch_all($r);
    }


    /**
     * Create a hash table of results
     *
     * @param $rows [array]  Results as returned by word_search() 
     * @param $word [string] Query word, if we want to count matches
     * @return [array] Hash 
     */
    function hash_result($rows,$word=false,$weight=1.0){
        $word = strtolower($word);
        if (!is_array($rows)) return array();
        $hash = array(); 
        foreach($rows as $a){
            $key = $a['t'].'.'.$a['i'];
            $hash[$key] = (float)substr_count($a['d'],$word) * $weight;
        }
        return $hash;
    }


    /**
     * Add two result sets together and increment score
     *
     * @param $hash1 [array]  Array of reversed results
     * @param $hash2 [array]  Array of reversed results
     * @param $weight [float] Weight to apply to hash2 
     * @return [array] Union of hash1 and hash2
     */
    function add_result($hash1, $hash2, $weight=1.0){
        if (!is_array($hash1) || empty($hash1)) return $hash2;
        else if (!is_array($hash2) || empty($hash2)) return $hash1;

        foreach($hash2 as $key=>$val){
            if (isset($hash1[$key])) $hash1[$key] += $val*$weight;
            else $hash1[$key] = $val*$weight;
        }
        return $hash1; 
    }


    /** 
     * Does a compound word search, returns results sorted by 'relevance'
     * Brain-dead simple and inefficient, but it'll get the job done.
     * Relevance: results that contain more of the words score higher.  
     *
     * @param $words [array] Array of words
     * @param $type  [string] Data type to search {items,comments,people} (optional)
     * @return [array] Array of rows containing data type, index, and score
     */
    function compound_search($words, $type=false){
        $tag = ($type=='tags');
        $index = array();
        $ww = 1.0;
        foreach($words as $word){
            if (empty($word)) continue;
            if ($tag) $r = tags::tag_search($word);
            else $r = search::word_search($word,$type);
            $n = count($r);
            if (!$n) continue;
            $new = search::hash_result($r,$word,(1.0/(float)$n));
            if ($ww>0.4) $ww-=0.1;
            $index = search::add_result($index, $new, $ww);
        }
        arsort($index);
        $out = array();
        foreach($index as $key=>$rel){
            list($table,$id) = explode('.',$key);
            $out[] = array('t'=>$table, 'i'=>$id, 'r'=>$rel); 
        }
        return $out;
    } 

    /**
     * Get result data
     *
     * @param $results [array] Results as returned by compound_search()
     * @param $start   [int]   Offset to fetch
     * @param $num     [int]   Number of rows to return
     * @return [array] $results array with data merged into each row
     */
    function get_result_data($results, $start=0, $num=1000){
        if (!is_array($results)) throw new Exception('results not an array!');

        $results = array_slice($results, $start, $num);
        foreach($results as $i=>$row){
            $table = $row['t'];
            $id = $row['i'];
            switch($table){
                case 'items':
                    $data = items::get_info($id,true);
                    break;
                case 'people':
                    $data = people::get_basic_info($id);
                    break;
                case 'comments':
                    $data = comments::get_comment($id);
                    break;
                default:
                    $data = false;
            }
            if (!$data){
                unset($results[$i]);
                continue;
            }
            $results[$i] = $data;
            $results[$i]['_type'] = $row['t'];
            $results[$i]['_rel'] = $row['r'];
        }
        return $results;
    }
}

?>
