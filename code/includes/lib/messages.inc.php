<?php

require_once('lib/db.inc.php');
require_once('lib/people.inc.php');


class messages{

     /**
      * Send a message
      *
      * @param  $sig  [string]  Sender's sig
      * @param  $recipient_id [int]  Recipient's user id
      * @param  $subject [string]  Message subject
      * @param  $message [string]  Message content
      * @param  $flag    [char]    Flag. 'u' for urgent
      * @param  $in_reply_to [int] Original message ID 
      */
     function send($sig, $recipient_id, $subject, $message, $flag, $in_reply_to=false){
         $sender = people::sig_lookup($sig,'*'); 
         if (!$sender) throw new Exception('Unknown or incorrect signature');
 
         $recipient = people::get_basic_info($recipient_id);
         if (!$recipient) throw new Exception('Unknown recipient');

         $raw_subject = $subject;
         $raw_message = $message;
         $subject = db_sanitize($subject);
         $message = db_sanitize($message);

         if (empty($subject) && empty($message)){
             throw new Exception('Empty subject and message... what kind of message is that?'); 
         }

         $data = array('sender'=>$sender['person_id'], 'recipient'=>$recipient['person_id'],
                       'title'=>$subject, 'message'=>$message, 'flag'=>$flag, 'ts'=>time());
         $parts = db_array2insert($data);
         $query = 'INSERT INTO messages ('.$parts['columns'].') VALUES ('.$parts['values'].')';
         db_query($query);
         $id = db_insert_id();

         messages::cc_email($sender, $recipient, $id, $raw_subject, $raw_message, $flag);
         return $id;
     }


     /**
      * Send message CC to recipient's email
      *
      * @param  $sender    [array]  Sender's data
      * @param  $recipient [array]  Recipient's data
      * @param  $id        [int]    Message ID
      * @param  $subject   [string]
      * @param  $message   [string]
      * @param  $flag      [char] 
      */
     function cc_email($sender, $recipient, $id, $subject, $message, $flag){
         $subject = '[scavdb] '.($flag=='u'?'URGENT: ' : '').$subject;
         $header = 'From: '.$sender['email'];

         $LINK = 'http://'.$_SERVER['HTTP_HOST'].'/messages.php';
         $RECIPIENT = $recipient['nickname'];
         $SENDER = $sender['nickname'];
         $MESSAGE = $message;
         $body = include('tpl/email/messages.email.tpl.php');
 
         $success = mail($recipient['email'], $subject, $body, $header);
	 if (!$success) error_log('mail sending failed');
     }


     /** 
      * Send registration confirmation message
      *
      * @param  $data [array]
      */
     function send_reg_conf($data){
         if (!is_array($data)) throw new Exception('Ack.  Bad input to send_reg_conf');

         extract($data);
         $subject = '[scavdb] You are teh regist0red';
         $header = "From: \"The System\" <ryo@iloha.net>\r\n";
         
         $link = 'http://'.$_SERVER['HTTP_HOST'].'/reg.php?id='.$person_id;
         $body = include('tpl/email/messages.regconf.tpl.php');

         $success = mail($email, $subject, $body, $header);
	 if (!$success) error_log('mail sending failed');
     }

     function send_forgotten_sig($user) {
       if (!is_array($user)) throw new Exception('Ack.  Bad input to send_forgotten_sig');
       extract($user);

       $subject = '[scavdb] Forgotten sig?';
       $header = "From: \"The System\" <agnoster@gmail.com>\r\n";

       $link = 'http://'.$_SERVER['HTTP_HOST'].'/reg.php?id='.$person_id;
       $body = include('tpl/email/messages.forgotsig.tpl.php');

       $success = mail($email, $subject, $body, $header);
       if (!$success) error_log('mail sending failed');       
       return $success;
     }

     /**
      * Get messages for specified user
      *
      * @param  $sig  [string]  User's sig
      * @return [array] Array of messages, sorted in revers chronological, grouped by tag
      */
     function get_messages($sig){
         $user = people::sig_lookup($sig);
         if (!$user) throw new Exception('Invalid or unknown sig');

         $id = $user['person_id'];
         $query = "SELECT m.*,p.nickname,p.person_id,";
         $query.= " from_unixtime(ts,'%a %h:%i %p') as time";
         $query.= " FROM messages m, people p ";
         $query.= " WHERE m.recipient='$id' and m.sender=p.person_id";
         $query.= " ORDER BY tag ASC,ts DESC";
         $r = db_query($query);
         return db_fetch_all($r);
     }


     /**
      * Flag message as {important, seen, trashed}
      *
      * @param  $sig  [stirng]
      * @param  $ids  [mixed]  Single message id, or array of ids
      * @param  $flag [string] Call messages::get_flags() for list of valid flags
      * @return [int]
      */
     function flag_message($sig, $ids, $flag){
         $user = people::sig_lookup($sig);
         if (!$user) throw new Exception('Invalid or unknown sig');
         $uid = $user['person_id'];

         if (!is_array($ids)) $ids = array($ids);
         $ids = db_sanitize($ids);
    
         $valid_flags = messages::get_flags();
         if (!isset($valid_flags[$flag])) throw new Exception("Invalid flag: $flag");

         foreach($ids as $id){
             $query = "UPDATE messages SET tag='$flag' WHERE recipient='$uid' and mesg_id='$id'";
             db_query($query);
         }
         return 0;
     } 
      

     /**
      * Get array of valid message flags
      *
      * @return [array]  Array as flag=>label
      */
     function get_flags(){
         return array(''=>'New', 'i'=>'Important', 's'=>'Seen', 't'=>'Trashed');
     }


     /**
      * Email everybody
      *
      * @param  $sig     [sender's sig]
      * @param  $subject [subject]
      * @param  $comment [comment]
      */
     function email_everybody($sig, $subject, $body){
         $subject = '[scavdb][broadcast] '.$subject;
         $user = people::sig_lookup($sig,'*');
         if (!$sig) throw new Exception('Unknow/incorrect signature');
         $uid = $user['person_id'];
         $nick = $user['nickname'];
         $email = $user['email'];

         $everybody = people::get_all_names('email');
         $bcc = '';
         foreach($everybody as $i=>$row){
             if ($bcc) $bcc.=", ";
             if ($i && $i%5==0) $bcc.="\n\t";
             $bcc.= $row['email'];
         } 
         $header = "From: \"$nick\" <$email>\n"; 
         $header.= 'Bcc: '.$bcc; 
         
         $success = mail('', $subject, $body, $header);
	 if (!$success) error_log('mail sending failed');
     }

}

?>
