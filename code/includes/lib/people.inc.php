<?php

require_once('lib/db.inc.php');
require_once('lib/search.inc.php');

class people{

    /**
     * Register new user
     *
     * @param  $data [array]  Associative array of data, keys should match db columns
     * @return [int]  Positive user ID if successful, negative error code otherwise
     */
    function add($data){
        $data = db_sanitize($data); 

        //validate fields
        people::validate($data);
        if (empty($data['sig']) || strlen($data['sig'])<4 || strlen($data['sig']>10)){
            throw new Exception('Invalid signature.  Must be 4 to 10 characters long.');
        }
        if (people::simple_search('sig', $data['sig'])){
            throw new Exception('Invalid signature.  Please try a different one.', -2);
        }

        //check to unique fields
        if (people::simple_search('nickname', $data['nickname'])){
            throw new Exception('User with that nickname already exists. ', -1);
        }
        if (people::simple_search('email', $data['email'])){
            throw new Exception('Someone has already registered that email address.', -3);
        }

        //form query
        $parts = db_array2insert($data);
        $query = 'insert into people ('.$parts['columns'].') values ('.$parts['values'].')';

        //run query
        $r = db_query($query);
        $id = db_insert_id();

        //update index 
        $index_data = $data['name'].' '.$data['nickname'].' '.$data['email'].' '.$data['skillz'];
        search::index('people', $id, $index_data);

        return $id;
    }


    /**
     * Simple (single column) search for people
     * 
     * @param  $field  [string]  DB field
     * @param  $value  [string]  Value to search for 
     * @return [array]  Array of matching rows (includes name,nickname,email,cell,status,role), or false
     */
    function simple_search($field, $value){
        $field = db_sanitize($field);
        $value = db_sanitize($value);

        $query = "SELECT person_id,name,nickname,email,cell,status,role FROM people WHERE $field like '$value'";
        $r = db_query($query);

        return db_fetch_all($r);
    }

    function get_people($ids) {
      if (!$ids) return array();
      $ids = implode(',', $ids);
      $query = "SELECT person_id,skillz,name,nickname,email,cell,status,role FROM people WHERE person_id IN ($ids)";
      $r = db_query($query);
      
      return db_fetch_all($r);
    }


    /**
     * Update user data
     *
     * @param  $sig  [string]  User's sig
     * @param  $data [array]   Updated data
     * @return [int] 0 if successful, negative error code otherwise
     */
    function update($sig, $data){
        //look up person by sig
        $user = people::sig_lookup($sig);
        if (!$user) throw new Exception('Invalid or unknown signature', -5);
        $user_id = $user['person_id'];

        //sanitize data and update
        $data = db_sanitize($data);
        people::validate($data);

        //format query
        $set = db_array2update($data);
        $query = "UPDATE people SET $set WHERE person_id='$user_id'";
        db_query($query);

        //update index 
        $index_data = $data['name'].' '.$data['nickname'].' '.$data['email'].' '.$data['skillz'];
        search::index('people', $user_id, $index_data);

        return 0;
    }


    /** 
     * Validate user data
     * @param $data [array]
     * @return [bool]
     */
    function validate($data){
        //make sure required fields are there
        if (empty($data['nickname'])){
            throw new Exception('Please enter a nickname');
        }
        if (empty($data['name'])){
            throw new Exception('Yo No-name.  No, not you.  You have a name.');
        }
        if (empty($data['email'])){
            throw new Exception('Please enter your email address.  It\'s for your own good.');
        }

        return true;
    }


    /**
     * Get basic user info
     *
     * @param  $id  [int]  Id of person
     * @return [array]
     */
    function get_basic_info($id, $return_sig=false){
        $id = db_sanitize($id);

        $query = "SELECT * FROM people WHERE person_id='$id'";
        $r = db_query($query);

        $data = db_fetch($r);
        if (!$return_sig && isset($data['sig'])) unset($data['sig']);
        return $data; 
    }


    /**
     * Set page captain status for a user.
     * 
     * @param  $sig [string]  Sig of user performing this action
     * @param  $user_id [int]  User ID of user getting set
     * @param  $page    [int]  Page
     * @param  $action  [bool] True to set, false to unset (optional, default true)
     * @return [int] 0 if successful, negative error code otherwise
     */
    function set_page_captain($sig, $user_id, $page, $action=true){
        $sig = db_sanitize($sig);
        $user_id = db_sanitize($user_id, true);
        $page = db_sanitize($page, true);

        $user = people::sig_lookup($sig);
        if ($user['role']!='c'){
            throw new Exception('Page captains can only be desginated by captains');
        }

        if ($action){
            $query = "INSERT INTO page_captains (person_id,page) values ('$user_id','$page')";    
            try{
                db_query($query);
            }catch(Exception $e){
            }
        }else{
            $query = "DELETE FROM page_captains WHERE page='$page' and person_id='$user_id'";
            db_query($query);
        }
        return 0;
    }
    

    /**
     * Update user status
     * 
     * @param  $sig  [string]  User's sig
     * @param  $status  [string]  New status
     * @return  [int] 0 if successful, negative error code otherwise (maybe)
     */
    function set_status($sig, $status){
        $sig = db_sanitize($sig);
        $status = db_sanitize($status); 

        $user = people::sig_lookup($sig);
        if (!$user) throw new Exception('Invalid or unknown signature');
        $user_id = $user['person_id'];

        if (strlen($status)>150) throw new Exception('Status message must be less than 150 characters');

        $query = "UPDATE people SET status='$status' WHERE person_id='$user_id'";
        db_query($query);
		$ts = time();
        $query = "INSERT INTO status VALUES(NULL,'$status','$user_id','$ts')";
        db_query($query);

        return mysqli_insert_id();
    } 

    /**
     * Update user status from a tweet
     * 
     * @param  $twitterer  [string] Twitter user
     * @param  $status  [string]  New status
     * @return  [int] 0 if successful, negative error code otherwise (maybe)
     */
    function tweet_status($twitterer, $status){
        $twitterer = db_sanitize($twitterer);
        $status = db_sanitize($status);

        $user = people::twitter_lookup($twitterer);
        if (!$user) throw new Exception('Twitter username is not registered');
        $user_id = $user['person_id'];

        if (strlen($status)>150) throw new Exception('Status message must be less than 150 characters');

        $query = "UPDATE people SET status='$status' WHERE person_id='$user_id'";
        db_query($query);
		$ts = time();
        $query = "INSERT INTO status VALUES(NULL,'$status','$user_id','$ts')";
        db_query($query);

        return mysqli_insert_id();
    } 

    /**
     * Get user statuses
     * 
	 * @param	$offset	[int]	Starting offset for statuses
	 * @param	$limit	[int]	Number of statuses to retrieve
     * @return  [array]	Statuses with author data
     */
    function get_status($offset=null, $limit=null){
        $offset = db_sanitize($offset);
        $limit = db_sanitize($limit); 

        $query = "SELECT text,written,person_id,nickname,name,from_unixtime(written,'%a %h:%i %p') as time FROM status,people WHERE person_id=author ORDER BY written DESC";
        $r = db_query($query);

		if (!$r) return array();
		return db_fetch_all($r);
    } 

    /**
     * Find people
     *
     * @param  $query  [string]  Search query
     * @return [array]  array of people records
     */
    function find($query){
        return array(array('name'=>'foo','email'=>'foo@foo.com'));
    }


    /**
     * Lookup user ID from sig
     *
     * @param  $sig  [string]  Sig to lookup
     * @param  $fields [string] Fields to retrieve (optional, default=person_id,role) 
     * @return [int] Positive user_id if found, 0 otherwise
     */
    function sig_lookup($sig, $fields='person_id,role'){
        $sig = db_sanitize($sig);
        $query = "SELECT $fields FROM people WHERE sig='$sig'";
        $r = db_query($query);
        if (!$r) return 0;

        return db_fetch($r);
    }

    /**
     * Lookup user ID from twitter username
     *
     * @param  $twitterer  [string]  Twitter username to lookup
     * @param  $fields [string] Fields to retrieve (optional, default=person_id,role) 
     * @return [int] Positive user_id if found, 0 otherwise
     */
    function twitter_lookup($twitterer, $fields='person_id,role'){
        $twitterer = db_sanitize($twitterer);
        $query = "SELECT $fields FROM people WHERE twitter='$twitterer'";
        $r = db_query($query);
        if (!$r) return 0;

        return db_fetch($r);
    }


    /**
     * Subscribe user to item
     *
     * @param $sig  [string]  Sig of user
     * @param $id   [int]     item ID
     */
    function subscribe_item($sig, $id){
        $user = people::sig_lookup($sig);
        if (!$user) throw new Exception('Invalid or unknown sig');

        $uid = $user['person_id'];
        $id = db_sanitize($id,true);
        $query = "INSERT INTO item_people (item_id,person_id) VALUES ('$id','$uid')";
        try{
            db_query($query);
        }catch(Exception $e){}
        return true;
    }


    /**
     * Get all names 
     * 
     * @param  [$field] list of fields
     * @return [array]  Array of people's names and ids
     */
    function get_all_names($fields='name,person_id'){
        $query = "SELECT $fields FROM people ORDER BY name";
        $r = db_query($query);
        return db_fetch_all($r);
    }

    function forgot_sig($person_id) {
      $person_id = db_sanitize($person_id);
      $query = "SELECT * FROM people WHERE person_id='$person_id'";
      $r = db_query($query);
      $result = db_fetch_all($r);
      if (count($result) != 1) {
	throw new Exception('Error finding this person');
      }
      $user = $result[0];
      if (messages::send_forgotten_sig($user)) {
	return $user['email'];
      } else {
	return false;
      }
    }

    /**
     * Get all page captains (for a page)
     *
     * @param $page [int] Page number (optional)
     * @return array 
     */
    function get_page_captains($page=false){
        $page = db_sanitize($page);

        $query = 'SELECT p.person_id,p.name,p.nickname,c.page';
        $query.= '  FROM people p, page_captains c';
        $query.= ' WHERE c.person_id=p.person_id ';
        if ($page) $query.= " and c.page='$page'";
        $r = db_query($query);
        return db_fetch_all($r);
    }

    function get_page_captain_ids(){
        $a = people::get_page_captains(false);
        $ids = array();
        foreach($a as $i=>$person){
            $ids[] = $person['person_id'];
        }
        return $ids;
    }

	function get_items($id=false){
		$id = db_sanitize($id);
		
		$query = 'SELECT p.item_id,p.status as role,i.status FROM item_people p,items i WHERE i.item_id=p.item_id';
		if ($id) $query.=' AND person_id='.$id;
        $r = db_query($query);
        return db_fetch_all($r);
	}
	
	function get_roles(){
		return array(
			'a' => 'Administrator',
			'c' => 'Captain',
			'p' => 'Page Captain',
			'l' => 'Large Point Czar',
			'r' => 'Road Trip Coordinator'
			);
	}
}


?>
