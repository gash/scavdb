<?php

$prefix = db_get_prefix();
//$db_name = 'scavdb_gash_test';
$db_name = 'scavdb_gashorg_2009';

//establish db connection
global $conn;
$conn = mysqli_connect('localhost', 'scavdb_gash_test', 'PASSWORD_GOES_HERE', $db_name);
if (!$conn){
    echo "Failed to connect to DB:".mysqli_connect_error();
    exit;
}

//utility functions

/**
 * get prefix from host name
 */
function db_get_prefix(){
    $host = $_SERVER['HTTP_HOST'];
    $parts = explode('.', $host);

    if ($parts[0]=='www' || count($parts)==2) return '';
    return $parts[0].'_';
}

/**
 * sanitize string for insertion in sql query
 *
 * @param $string [string]  Input string, shouldn't be escaped
 * @param $num    [bool]    Make sure value is numeric
 * @return [string]
 */
function db_sanitize_string($string,$num=false){
    global $conn;

    if (!$conn) throw new Exception('no conn!');
    $val = mysqli_real_escape_string($conn,$string); 
    if ($num && !is_numeric($val)) throw new Exception('Invalid numeric value: '.$val); 
    return $val;
} 


/**
 * sanitize single dimensional array of inputs
 *
 * @param $a  [array]  Array of strings, if not array, error
 * @return  [array]  Idential array with each element escaped
 */
function db_sanitize_array($a){
    if (!is_array($a)){
        if (is_string($a)) return db_sanitize_string($a); 
        else return $a;
    }

    foreach($a as $key=>$val){
        $a[$key] = db_sanitize($val);
    }
    return $a;
}


/**
 * sanitize mixed input (array or string)
 *
 * @param  $in  [mixed]  Array or string to be sanitized
 * @param  $num [string]  Type check  (optional, def=false);
 * @return  [mixed]  Sanitized input
 */
function db_sanitize($in, $num=false){
    if (is_array($in)) return db_sanitize_array($in);
    else if (is_string($in)) return db_sanitize_string($in,$num);
    else return $in;
}


/**
 * form insert query strings out of assoc array
 * 
 * @param  $a  [array]  Associative array, with keys corresponding to db columns
 * @return [array]  Array with 2 strings keyed 'columns' and 'values'
 */
function db_array2insert($a){
    $columns = implode(',', array_keys($a));
    foreach($a as $key=>$value){
        $a[$key] = "'".$value."'";
    }
    $values = implode(',', $a);
    return array('columns'=>$columns, 'values'=>$values);
}


/**
 * form upate query strings out of assoc array
 *
 * @param  $a  [array]  Associative array, with keys corresponding to db columns
 * @return [string]  SET clause of an update query
 */
function db_array2update($a){
    $out = '';
    foreach($a as $key=>$val){
        $out.= ($out?',':'')."$key='$val'";
    }
    return $out;
}


/**
 * wrapper for fetch_assoc
 *
 * @param  $result  [mysqli result] 
 * @return [array]  One row of results
 */
function db_fetch($result){
    return mysqli_fetch_assoc($result);
}


/**
 * fetch all rows in result
 *
 * @param  $result  [mysqli result]
 * @return [array]  Array of assoc arrays
 */
function db_fetch_all($result){
    $data = array();
    while($a=db_fetch($result)){
        $data[] = $a;
    }
    return $data;
}

function db_query($query){
    global $conn;

    $r =mysqli_query($conn, $query);
    if (!$r) throw new Exception('Query failed: '.db_error()."<br>\n".$query);
    return $r;
}

function db_insert_id(){
    global $conn;

    return mysqli_insert_id($conn);
}


function db_error(){
    global $conn;
    return mysqli_error($conn);
}

?>
