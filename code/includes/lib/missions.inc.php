<?php

require_once('lib/db.inc.php');
require_once('lib/people.inc.php');

class missions{

     function add($sig, $ts, $duration, $priority, $place, $description){
         $user = people::sig_lookup($sig);
         if (!$user) throw new Exception('Invalid/unknown signature');
         $uid = $user['person_id'];

         $ts = db_sanitize($ts, 1);
         $duration = db_sanitize($duration, 1);
         $priority = db_sanitize($priority, 1);
         $place = db_sanitize($place);
         $description = db_sanitize($description);

         $data = array('ts'=>$ts, 'duration'=>$duration, 'priority'=>$priority,
                       'place'=>$place, 'description'=>$description,'owner'=>$uid);

         $parts = db_array2insert($data);
         $query = 'INSERT INTO missions ('.$parts['columns'].')';
         $query.= ' VALUES ('.$parts['values'].')';

         db_query($query);
         return db_insert_id();
     }

     function get_missions(){
         $query = "SELECT m.*,from_unixtime(ts,'%a %h:%i %p') as time, ";
         $query.= 'p.nickname,p.person_id FROM missions m, people p ';
         $query.= 'WHERE m.owner=p.person_id ORDER BY m.ts';
         $r = db_query($query);
         return db_fetch_all($r);
     }

}

?>
