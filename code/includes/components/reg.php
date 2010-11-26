<?php

require_once('lib/people.inc.php');
require_once('lib/messages.inc.php');

function build_reg_html($data){
    return snaf_build_bottom_up($data, 'reg', 'html');
}

function build_reg_css($data){
    return snaf_build_bottom_up($data, 'reg', 'css');
}


function get_reg_data($request=array()){
    if (isset($request['id'])) $request['person_id'] = $request['id'];
 
    $data = process_reg_form($request);
    if (!$data){
         $data = array('person_id'=>'', 'name'=>'', 'nickname'=>'', 'email'=>'', 'sig'=>'',
                  'cell'=>'','skillz'=>'', 'ERRORS'=>'');
    }

    if ($data['person_id']){
        $data['button'] = 'Update!';
        $data['new'] = 'Register new user instead';
    }else{
        foreach($data as $k=>$v) if (isset($request[$k])) $data[$k]=$request[$k];
        $data['button'] = 'Sign me up, yo!';
        $data['person_id'] = '';
        $data['new'] = '';
    } 
    return snaf_wrap_data($data,'reg','reg');
}

function process_reg_form(&$request){
    if (empty($request['submit'])){
        if ($request['person_id']) return people::get_basic_info($request['person_id']);
        else return false;
    }

    $data = array();
    $fields = array('name','nickname','email','cell','skillz','sig','twitter');
    foreach($fields as $i=>$key){
        $data[$key] = $request[$key];
    }

    if ($_POST['person_id']){
        try{
            people::update($request['sig'],$data);
            snaf_add_error("Updated.");
        }catch(Exception $e){
            snaf_add_error($e->getMessage());
        }
        return $request;
    }else{
        try{
            $person_id = people::add($data);
            $request['person_id'] = $person_id;
            snaf_add_error("You're registered.  If you entered a valid email address, you should get an email shortly.  If not, make sure the email address below is correct.");
            messages::send_reg_conf($request);
        }catch(Exception $e){
            snaf_add_error($e->getMessage());
        }
        return $request; 
    }
    return false;
}
?>
