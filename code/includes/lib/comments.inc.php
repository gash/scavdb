<?php

require_once('lib/db.inc.php');
require_once('lib/people.inc.php');
require_once('lib/search.inc.php');

class comments{

     /**
      * Attach comment... to something.
      *
      * @param $table [string]  Table (object) being commented on
      * @param $id    [int]     Object ID being commented on
      * @param $sig   [string]  Signature of commenter
      * @param $comment [string] Comment 
      * @param $parent  [int]  Parent comment ID (optional)
      * @return [int]  Comment ID or exception
      */
     function add_comment($table, $id, $sig, $comment, $parent=null, $ctype=null, $info=null){
         //sanitize input
         $table = db_sanitize($table);
         $id = db_sanitize($id);
         $sig = db_sanitize($sig);
         $comment = db_sanitize($comment);
         if (isset($parent)) $parent = db_sanitize($parent);

         //figure out who commenter is
         if ($sig){
             $user = people::sig_lookup($sig); 
             if (!$user) throw new Exception("Incorrect or unknown sig");
             $user_id = is_array($user) ?  $user['person_id'] : 0;
         }else{
             $user_id = 0;
         }

         //put together query
         $data = array('datatype'=>$table, 'data_id'=>$id, 'commenter'=>$user_id,
                       'comment'=>$comment, 'ts'=>time());
         if ($parent) $data['parent_id'] = $parent;
         if ($ctype) $data['ctype'] = $ctype;
	 if ($info) $data['info'] = json_encode($info);

         $parts = db_array2insert($data);
         $query = 'INSERT INTO comments	('.$parts['columns'].') VALUES ('.$parts['values'].')';

         //execute query
         db_query($query);
         $id = db_insert_id();

         //index comment
         search::index('comments', $id, $comment);
         return $id; 
     }


     /**
      * Get comments for an object
      *
      * @param $table [string]  Table/object name
      * @param $id    [int]     Object ID (unset, returns for all objects)
      * @param $b_only [bool]   Broadcast only
      * @return [array]  Array of comments
      */
     function get_comments($table, $id=false, $b_only=false){
         $table = db_sanitize($table);
         $id = db_sanitize($id);

         $where_id = '';
         $where_id = ($id ? "and c.data_id='$id'" : '');
         if ($b_only) $where_id.= " and ctype='b'"; 
         $query = "SELECT c.*,from_unixtime(c.ts,'%a %h:%i %p') as time,p.person_id,p.name,p.role ";
         $query.= ' FROM comments c, people p ';
         $query.= " WHERE c.datatype='$table' $where_id and c.commenter=p.person_id";
         $query.= ' ORDER BY c.ts DESC';
         $r = db_query($query);
         $rows = db_fetch_all($r);
	 foreach ($rows as $idx=>$row) {
	   if (!empty($row['info'])) $rows[$idx]['info'] = json_decode($row['info'], 1);
	 }
	 return $rows;
     }

     /**
      * Get comment by id
      *
      * @param  $cid  [int]  Comment ID
      * @return [array]
      */
     function get_comment($cid){
         $cid = db_sanitize($cid,true);

         $query = "SELECT c.*,FROM_UNIXTIME(c.ts, '%a %h:%i %p') as time,p.name,p.person_id"; 
         $query.= " FROM comments c, people p WHERE c.comment_id='$cid' and c.commenter=p.person_id ";
         $r = db_query($query);
	$row = db_fetch($r);
	if (!empty($row['info'])) $row['info'] = json_decode($row['info'], 1);
	return $row;
     }

     /**
      * Get comment alert email content
      */
     function get_email_content($table, $id, $sig, $comment){
         $user = people::sig_lookup($sig, '*');
         if (!$user) throw new Exception('Invalid/unknown signature');

         $nickname = $user['name'];
         if ($table=='items'){
             return include('tpl/email/item_email.tpl.php');
         }
         return false;
     }

}


?>
