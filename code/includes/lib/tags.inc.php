<?php

require_once('lib/db.inc.php');


class tags{

    /**
     * Tag object
     *
     * @param  $table [string]  Table/object name
     * @param  $id    [int]     object ID
     * @param  $tag   [mixed]   String (one tag) or array (multiple tags)
     * @return [int]  positive if succesful
     */
    function add_tag($table, $id, $tags){
        $table = db_sanitize($table);
        $id = db_sanitize($id);
        $tags = db_sanitize($tags);
        if (!is_array($tags)) $tags = array($tags);

        if (empty($table)) return 0; 
        if (empty($id)) return 0; 

        //strip out white space
        foreach($tags as $i=>$tag){ 
            $tag = tags::normalize($tag); 
            if (empty($tag)) unset($tags[$i]);
            else $tags[$i] = $tag;
        }

        if (count($tags)==0) return 0;

        //save tags in db
        $idx = array();
        foreach($tags as $i=>$tag){
            $query = "INSERT INTO tags (datatype,data_id,tag) values ('$table','$id','$tag')";
            try{
                db_query($query);
                $idx[] = $tag;
            }catch(Exception $e){
                trigger_error($e->getMessage(), E_USER_WARNING);
            }
        }

        //update search index
        try{
            $idx = ' '.implode(' ',$idx);
            search::index($table,$id,$idx,true);
        }catch(Exception $e){
            trigger_error($e->getMessage(), E_USER_WARNING);
        }
        return count($idx);
    }


    /**
     * Get tags for an object
     *
     * @param  $table [string]  Table/object name
     * @param  $id    [int]     object id
     * @return [array]  Array of tags
     */
    function get_tags($table, $id){
        $table = db_sanitize($table);
        $id = db_sanitize($id);

        $query = "SELECT * FROM tags WHERE datatype='$table' and data_id='$id'";
        $r = db_query($query);

        return db_fetch_all($r);
    }


    /**
     * Remove tag
     *
     * @param  $table [string]
     * @param  $id    [int]
     * @param  $tag   [string]
     * @return [int]
     */
    function remove_tag($table, $id, $tag){
        $table = db_sanitize($table);
        $id = db_sanitize($id);
        $tag = db_sanitize($tag);
        $tag = tags::normalize($tag);

        $query = "DELETE FROM tags WHERE datatype='$table' and data_id='$id' and tag='$tag'";
        db_query($query); 
        return 0;
    }
   

    /**
     * Search index for a tag 
     *
     * @param  $tag [string]
     * @param  $type  [string]  DAta type {comments,items,people} (optional)
     * @return [array]
     */
    function tag_search($tag, $type=false){
        $tag = db_sanitize($tag);
        $query = "SELECT datatype as t,data_id as i, tag as d ";
        $query.= " FROM tags WHERE tag like '$tag' ";
        $query.= ' LIMIT 1000';
        $r = db_query($query);
        return db_fetch_all($r);
    }


    /**
     * Normalizes tag (basically stripped whitespace)
     *
     * @param  $tag  [string]  
     * @return [string]  Normalized tag
     */
    function normalize($tag){
        $tag = ereg_replace("[ \t\r\n]", '', $tag);
        return strtolower($tag);
    }
}

?>
