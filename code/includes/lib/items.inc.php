<?php

define('CZAR_POINTS', 40);

require_once('lib/db.inc.php');
require_once('lib/search.inc.php');
require_once('lib/people.inc.php');
require_once('lib/comments.inc.php');
require_once('lib/tags.inc.php');
require_once('lib/messages.inc.php');

class items{

    /**
     * Add item
     *
     * @param  $data  [array]  Assoc array of data, keys should match db columns
     * @return [int]  Positive int if successful, negative error otherwise
     */
    function add($data){
        //sanitize input and form query
        $data = db_sanitize($data);
        $data['mtime'] = time();
        $parts = db_array2insert($data);
        $query = 'insert into items ('.$parts['columns'].') values ('.$parts['values'].')';

        //run query
        db_query($query);
        
        //update query
        search::index('items', $data['item_id'], $data['description']);

        return $data['item_id'];
    }


     /**
      * Update item (for updating status, use update_status())
      * 
      * @param $id    [int]  Item ID (item number)
      * @param $data  [array]  Assoc array of updated data, keys should match db columns
      * @param $sig   [string]  Sig of user performing this action
      * @return [int] 0 on success, negative error code otherwise
      */
     function update($id, $data, $sig){
         //check perm
         if (!items::check_access($id, $sig)){
             throw new Exception('User does not have permission to update this item.', -1);
         }

         //add mtime
         $data['mtime'] = time();

         //sanitize input and form query
         $id = db_sanitize($id);
         $data = db_sanitize($data);
         $set= db_array2update($data);
         $query = 'UPDATE items SET '.$set.' WHERE item_id='.$id;
         
         db_query($query);

         $idx_id = isset($data['item_id']) ? $data['item_id'] : $id;
         search::index('items', $idx_id , $data['description']);
         if ($idx_id!=$id) search::delete('items',$id); //blow old index 
         return 0;
     } 


     /**
      * Update item (for updating status, use update_status())
      * 
      * @param $id    [int]  Item ID (item number)
      * @param $data  [array]  Assoc array of updated data, keys should match db columns
      * @param $sig   [string]  Sig of user performing this action
      * @return [int] 0 on success, negative error code otherwise
      */
     function update_role($id, $user, $sig, $role){
         //check perm
         if (!items::check_access($id, $sig)){
             throw new Exception('User does not have permission to update this item.', -1);
         }

         //sanitize input and form query
         $id = db_sanitize($id);
         $user = db_sanitize($user);
         $role = db_sanitize($role);
         $query = "REPLACE item_people VALUES ('$id','$user','$role')";
         
         db_query($query);
         return 0;
     }


     /**
      * Update item status
      * 
      * @param  $id   [int]  Item ID
      * @param  $status [char]  New status (use items::get_statuses() for list of valid statuses)
      * @param  $sig    [string]  Sig of user performing action
      * @return [int]  0 on success, negative error code otherwise
      */
     function update_status($id, $status, $sig){
         $id = db_sanitize($id);

         //check perm
		 $perm = items::check_access($id, $sig);
//         if (!$perm) {
//             throw new Exception('User does not have permission to update this item.', -1);
//         }
 		 if ($status=='Z' && (!$perm || $perm=='o')) {
			 throw new Exception('Only the page captain can mark something as "in the box".', -1);
		 }

         //sanitize input and form query
         $statuses = items::get_statuses();        
         if (empty($statuses[$status])) throw new Exception('Invalid status code: '.$status);
         $time = time();

         //run update query
         $query = "UPDATE items SET status='$status',mtime='$time' WHERE item_id='$id'";
         db_query($query);
         return 0;
     }

     /**
      * Update modtime for item
      *
      * @param  $id [int] Item ID
      */
     function update_mtime($id){
         $id = db_sanitize($id);
         $query = 'UPDATE items SET mtime='.time().' WHERE item_id='.$id;
         db_query($query);
     }


     /**
      * Get array of valid status codes and labels
      *
      * @return [array]  Associative array, keyed by status code
      */
     function get_statuses(){
         return array(
                 'n' => 'New',
                 'c' => 'Research',
                 'd' => 'Do it',
                 's' => 'HELP!',
                 'A' => 'Accepted',
                 'I' => 'In progress',
                 'r'  => 'Road Trip',
                 'P' => 'Completed',
                 'Z' => 'In box',
                 'u' => 'Uploaded',
				 'x' => 'Impossible',
                );
     }


     /**
      * Get item info
      *
      * @param $id  [int]  Item ID
      * @param $basic  [bool]  Set to true to return just basic info 
      * @return [array]  Assoc array containing item info
      */
     function get_info($id,$basic=false){
         $id = db_sanitize($id);

         $query = "SELECT * FROM items WHERE item_id='$id'";
         $r = db_query($query);

         $a = db_fetch($r);
         if (is_array($a) && !$basic){
             $a['comments'] = items::get_item_comments($id);         
             $a['tags'] = items::get_item_tags($id);
             $a['people'] = items::get_people($id);
         }
         return $a;
     }

     function get_items($ids) {
       if (!$ids) return array();
       $ids = implode(',', $ids);
       $query = "SELECT * FROM items WHERE item_id IN ($ids)";
       $r = db_query($query);
       return db_fetch_all($r);
     }


     /**
      * Get nickname, ids, roles of item participants
      *
      * @param  $id [int]  Item ID
      * @return [array]
      */
     function get_item_participants($id){
         $id = db_sanitize($id,true);
         $query = 'select ip.person_id,ip.status,p.nickname from item_people ip, people p ';
         $query.= "where ip.item_id='$id' and ip.person_id=p.person_id";
         $r = db_query($query);
         return db_fetch_all($r);
     }


     /** 
      * Get all items in a page
      * 
      * @param  $page  [int]  Page number
      * @param  $sort  [string]  Column to sort by (optional, def='item_id')
      * @param  $start [int]  Offset to start with (optional, def=1)
      * @param  $num   [int]  Number of rows to return (optional, def=25)
      * @return [array]  Array of items in the page
      */
     function get_page($page=false,$sort='item_id',$start=0,$num=1000){
         $a = array();
         if ($page) $a = array('page'=>$page);
		 return items::get_match($a,$sort,$start,$num);
     }

     /** 
      * Get all items with a condition
      * 
      * @param  $page  [int]  Page number
      * @param  $sort  [string]  Column to sort by (optional, def='item_id')
      * @param  $start [int]  Offset to start with (optional, def=1)
      * @param  $num   [int]  Number of rows to return (optional, def=25)
      * @return [array]  Array of items in the page
      */
     function get_match($match,$sort='item_id',$start=0,$num=1000){
         $sort = db_sanitize($sort);
         $start = db_sanitize($start, true);
         $num = db_sanitize($num, true);

         $sort = db_sanitize($sort); 
         $cond = array();
		 foreach($match as $k=>$v) {
		   if ($k == 'tag') {
		     $tag = $v;
		     $cond[] = 'i.item_id=t.data_id AND t.datatype="items" AND t.tag="'.$v.'"';
		   } else {
		     $cond[] = "`$k`='$v'";
		   }
		 }
         $where = count($cond) ? 'WHERE '.implode(' and ',$cond) : ''; 
         $query = "SELECT i.*,from_unixtime(mtime,'%a %h:%i %p') as ftime, ";
         $query.= ' count(c.comment_id) as nc FROM ';
	 if ($tag) $query .= "tags t, ";
	 $query.= " items as i LEFT OUTER JOIN comments c ON c.data_id=item_id and c.datatype='items' ";
         $query.= " $where GROUP BY i.item_id ORDER BY $sort LIMIT $start,$num";
         $r = db_query($query);

         return db_fetch_all($r);
     }
     /**
      * Get pages
      *
      * @return [array] Array of pages
      */
     function get_page_nums(){
         $query = 'SELECT DISCTINCT(page) FROM items ORDER BY page';
         $r = db_query($query);
         return db_fetch_all($r);
     }


     /**
      * Find items matching a query
      *
      * @param  $query  [string]  Query string
      * @return [array]  Array of matches
      */
     function search($query){
         return array(array());
     }


     /**
      * Add tag(s) to an item
      *
      * @param  $id  [int]  Item ID
      * @param  $tag [mixed]  String (single tag) or array (multiple tags)
      * @return [int] 0 if successful, negative error code otherwise
      */
     function tag_item($id, $tags){
         tags::add_tag('items', $id, $tags);
         items::update_mtime($id);
         return 0;
     }


     /**
      * Get tags for an item
      *
      * @param  $id  [int] item ID
      * @return [array]  Array of tags
      */
     function get_item_tags($id){
         $r = tags::get_tags('items', $id);
         if (!is_array($r)) return array();

         $tags = array();
         foreach($r as $i=>$a){
             $tags[] = $a['tag']; 
         }
         return $tags;
     }


     /**
      * Tag search
      *
      * @param $tag  [string]  Tag to search for
      * @param $sort [string]  Item column to sort by (optional def=status)
      * @return [array]  Array of items
      */
     function tag_search($tag, $sort='status'){
         $tag = db_sanitize($tag);
         $tag = tags::normalize($tag);

         $query = 'SELECT i.*,t.tag FROM items i, tags t ';
         $query.= " WHERE t.datatype='items' and t.tag like '$tag' ";
         $query.= " and t.data_id=i.item_id ORDER by i.$sort";
         $r = db_query($query);
         return db_fetch_all($r);
     }


     /**
      * Remove tag from item
      * 
      * @param  $id  [int]  Item ID
      * @param  $tag [string]  Tag to remove
      * @param  $sig [string]  Sig of user performing action
      */
     function remove_tag($id, $tag, $sig){
         if (!items::check_access($id,$sig)){
             throw new Exception('Insufficient access privileges');
         }

         tags::remove_tag('items',$id,$tag); 
         items::update_mtime($id);
         return 0;
     }


     /**
      * Make sure user has write permissions to item.  Write permissions needed for:
      *    -modifying item
      *    -updating item status
      *    -removing tags
      *    -delegating ownership
      * 
      * @param  $id  [int]  Item ID
      * @param  $sig [string]  Sig of user to check permissions for
      * @return [bool]  True if has access, false otherwise
      */
     function check_access($id, $sig){
          if (WORLD_WRITABLE) return true;

          $sig = db_sanitize($sig);
          $id = db_sanitize($id);

          $user = people::sig_lookup($sig);
          if (!$user) return false;
          $user_id = $user['person_id'];
          $role = $user['role'];
          
          //if captain, allow
          if ($role=='c') return 'c';

		  //if admin, allow
          if ($role=='a') return 'a';

		  //see if user is Large Point Czar
		  if ($role=='l') {
			  $query = 'SELECT max_pt FROM items ';
	          $query.= ' WHERE item_id='.$id;
	          $r = db_query($query);
	          if (!$r) throw new Exception('Large Points Czar check failed: '.db_error(), -2);
			  $points = db_fetch($r);
	          if ($points['max_pt']>=CZAR_POINTS) return 'l';
		  }

          //see if user is page captain for item
          $query = 'SELECT items.page FROM items,page_captains ';
          $query.= ' WHERE items.item_id='.$id.' and items.page=page_captains.page';
          $query.= ' and page_captains.person_id='.$user_id;
          $r = db_query($query);
          if (!$r) throw new Exception('Page captain check failed: '.db_error(), -2);
          if (mysqli_num_rows($r)>0) return 'p';

          //see if user has "owner" privileges
          $query = 'SELECT status FROM item_people WHERE item_id='.$id.' and person_id='.$user_id;
          $query.= " and status='o'"; 
          $r = db_query($query);
          if (!$r) throw new Exception('User ownership check failed: '.db_error(), -3);
          if (mysqli_num_rows($r)>0) return 'o';
    
          return false;
     }


     /**
      * Get item comments
      * 
      * @param  $id  [int]  Item ID
      * @return [array]  Array of comments
      */
     function get_item_comments($id){
         return comments::get_comments('items', $id);
     } 


     /**
      * Post item comment
      *
      * @param  $id [int]  Item ID
      * @param  $comment [string]  comment content 
      * @param  $sig  [string] Signature of commenter
      * @param  $ctype[int]  Comment type (optional)
      * @param  $subject [string] Subject of broadcast message
      * @return [int]  Positive int if successful, negative otherwise
      */ 
     function post_item_comment($id, $comment, $sig, $ctype=null, $subject=null, $info=null){
         $cid = comments::add_comment('items',$id, $sig, $comment, null, $ctype, $info);
         if (!$cid) return false;

         items::update_mtime($id);
         if ($ctype=='b'){
             $body = comments::get_email_content('items',$id, $sig, $comment);
             messages::email_everybody($sig, $subject, $body);
         } else {
			 if (!$subject) $subject = 'new comment';
			 $subject = 'Item '.$id.': '.$subject;
			 $item = '';
			 $people = items::get_people($id);

             //append link to item page
             $comment.="\r\n\r\n";
             $comment.='http://'.$_SERVER['HTTP_HOST'].'/item.php?id='.$id."\r\n";
             
			 foreach ($people as $p) {
			 	 messages::send($sig, $p['person_id'], $subject, $comment, '');
			 }
		 }
         
     }

     /**
      * Get number of pages
      *
      * @return [int] Largest page number
      */
     function get_page_numbers(){
         $query = 'SELECT distinct(page) FROM items ORDER BY page';  
         $r = db_query($query);
         $data = db_fetch_all($r);
         if (!$data) return array();
         $ids = array();
         foreach($data as $i=>$a) $ids[] = $a['page'];
         return $ids;
     }

     /**
      * Get pages
      *
      * @return [int] Largest page number
      */
     function get_pages($points=false){
		$select = 'count(item_id)';
		if ($points) $select = 'sum(max_pt)';
         $query = 'SELECT '.$select.' as num,page,status FROM items GROUP BY page,status ORDER BY page';  
         $r = db_query($query);
         $data = db_fetch_all($r);
         if (!$data) $data = array();
		 $pages = array();
		 foreach ($data as $row) {
			$page = $row['page'];
			$pages[$page][$row['status']] = $row['num'];
			$pages[$page]['total'] += $row['num'];
		 }
		 foreach ($pages as $idx=>$page) {
			$pages[$idx]['captains'] = people::get_page_captains($idx);
		 }
         return $pages;
     }


	 function get_people($id){
		 $id = db_sanitize($id);
		 $query = 'SELECT p.person_id,i.status,p.nickname FROM item_people i,people p WHERE item_id='.$id.' AND p.person_id=i.person_id';
		 $r = db_query($query);
		 $users = db_fetch_all($r);
		 if (!$users) $users = array();
		
		 $query = 'SELECT p.person_id,"p" as status,p.nickname FROM page_captains c,items i,people p WHERE c.page=i.page AND item_id='.$id.' AND p.person_id=c.person_id';
		 $r = db_query($query);
		 $captains = db_fetch_all($r);
		 if (!$captains) $captains = array();
		
		 $query = 'SELECT p.person_id,"l" as status,p.nickname FROM people p,items i WHERE role="l" AND max_pt>='.CZAR_POINTS.' AND item_id='.$id;
		 $r = db_query($query);
		 if ($r && $czars = db_fetch_all($r)){
			$captains = array_merge($captains, $czars);
		 }

		 return array_merge($users,$captains);
	 }
	
	 function get_people_by_role($id){
		$people = self::get_people($id);
		$result = array();
		foreach ($people as $p){
			$result[$p['role']][] = $p;
		}
		return $result;
	}
  
     /**
      * status comparison func
      */
     function stat_sort($c1, $c2){
         return ord($c1)<ord($c2);
     }


     /**
      * Get item status summary
      */
     function get_status_summary(){
         $query = 'SELECT status,count(status) as num,sum(max_pt) as points FROM items GROUP BY status';
         $r = db_query($query);
         $data = db_fetch_all($r);

         if (!$data) return array();

         $out = array();
		 $total = array(0,0);
         foreach($data as $i=>$row){
             $out[$row['status']] = array($row['num'], $row['points']);
			$total[0] += $row['num'];
			$total[1] += $row['points'];
         } 
		$out['total'] = $total;
         return $out;
     }


    /**
     * Split integer value for due date/time.  Due time is in minutes from list
     * release.
     * @return array with keys 'day', 'hour', 'minutes'
     */
    function split_dueint($duetime){
        $mins_in_day = 24 * 60;
        $out = array();
        $out['day'] = floor($duetime/$mins_in_day);
        $out['hour'] = abs(floor(($duetime % $mins_in_day) / 60));
        $out['minutes'] = abs($duetime % 60); 
        return $out;
    }

    /**
     * Construct human readable due date/time string from int
     */
    function due_int2string($due){
        $a = self::split_dueint($due); 
        extract($a);
        $days = self::due_dates();
        $out = $days[$day]." ".str_pad($hour,2,'0',STR_PAD_LEFT); 
        $out.= ':'.str_pad($minutes,2,'0',STR_PAD_LEFT);
        return $out;
    }

    /**
     * retun array of due dates
     */
    function due_dates(){
        $days = array(
               '-30' => 'T - 30 days',
               '-14' => 'T - 2 weeks',
               '-7' => 'T - 1 week',
               '-3' => 'T - 3 days',
               '-2' => 'T - 2 days',
               '-1' => 'T - 1 day',
               '0'=>'Thursday',
               '1'=>'Friday',
               '2'=>'Saturday',
               '3'=>'Sunday',
               '4'=>'Monday',
               );
        return $days;
    }

}
?>
