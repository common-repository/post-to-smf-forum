<?php

/*
Plugin Name: Post to SimpleMachines Forum SMF 
Plugin URI: http://www.zonca.org/wordpress-post-to-smf
Description: Automatically posts each new wordpress post to a SMF board 
Version: 1.5
Author: Zonca Webdesign
Author URI: http://www.zonca.org
*/
// paths to forum

$post_to_smf_db_version = "1.0";

function post_to_smf_install () {
   global $wpdb;
   global $post_to_smf_db_version;

   $table_name = $wpdb->prefix . "post_to_smf";
   if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
      
      $sql = "CREATE TABLE " . $table_name . " (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      wp_id bigint(11) NOT NULL,
      smf_topic_id bigint(11) NOT NULL,
      smf_msg_id bigint(11) NOT NULL,
      UNIQUE KEY id (id)
    );";
     require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);


      add_option("post_to_smf_db_version", $post_to_smf_db_version);

   }
}

function post_to_smf_catexcluded($postid) {
   $postcats = get_the_category($postid);
   $excludedcats = explode(",",get_option( 'post_to_smf_excludecats' )); 
   foreach($postcats as $category) {     
    $catid=$category->cat_ID;
     if (in_array($catid,$excludedcats)) return true;
   } 
 return false;
 }

function post_to_smf($post_ID) {
    global $wpdb;
$smfpath = get_option('post_to_smf_smfpath');
    $post = get_post($post_ID); 
  if (!file_exists($smfpath . '/SSI.php')) {return $post->post_content;}

  $props = get_post_custom($id);

    require_once("$smfpath/SSI.php");
    require_once("$smfpath/Sources/Subs-Post.php");
    $table_name = $wpdb->prefix . "post_to_smf";
    if (get_option('post_to_smf_excerpt_length')=='') {
        $max_length = 0;
    } else {
        $max_length = get_option('post_to_smf_excerpt_length');
    }

  $ID_BOARD=$props['forum'][0];
  if ($ID_BOARD == '') {
    $ID_BOARD = get_option('post_to_smf_defforum'); // id of the board to post to
  }
  if (($ID_BOARD)&&($ID_BOARD!='0')&&($post->post_status=='publish')&& (!post_to_smf_catexcluded($post_ID))) {

    $subject = $wpdb->escape($post->post_title);

 if (empty($post->post_excerpt)) {
     if ($max_length>0){
        $excerpt = $post->post_content;
        $excerpt = trim(strip_tags($excerpt));
        str_replace("&#8212;", "-", $excerpt);

        $words = preg_split("/(?<=(\.|!|\?)+)\s/", $excerpt, -1, PREG_SPLIT_NO_EMPTY);

        foreach ( $words as $word )
        {
            $new_text = $text . substr($excerpt, 0, strpos($excerpt, $word) + strlen($word));
            $excerpt = substr($excerpt, strpos($excerpt, $word) + strlen($word), strlen($excerpt));

            if ( ( strlen($text) != 0 ) && ( strlen($new_text) > $max_length ) )
            {
                break;
            }

            $text = $new_text;
        }
        $post->post_excerpt = $text;
     } else {
        $post->post_excerpt = $post->post_content;
     }
 }
    
    $output = $wpdb->escape($post->post_excerpt . "<br /><a href=\"" .  get_permalink($post->ID) . "\">" . get_option('post_to_smf_msgtext') . "</a>");
    $ID_MEMBER = get_option('post_to_smf_username'); // id of the user
    // Collect all necessary parameters for the creation of the post.
    $msgOptions = array(
       'id' =>  0,
       'subject' => $subject,
       'body' => $output,
       'smileys_enabled' => true,
    );
    
    $topicOptions = array(
       'id' => 0,
       'board' => $ID_BOARD,
       'mark_as_read' => true,
    );
    
    $posterOptions = array(
       'id' => $ID_MEMBER,
    );
    
    // check if post already exists
    if ($wpdb->get_row("SELECT * FROM $table_name WHERE wp_id = $post_ID")) {

        //exist, update post
        $row = $wpdb->get_row("SELECT * FROM $table_name WHERE wp_id = $post_ID");

        $topicOptions['id'] = $row->smf_topic_id;
        $msgOptions['id'] = $row->smf_msg_id;

        modifyPost($msgOptions, $topicOptions, $posterOptions);

    } else { //doesn't exist create a new post

      createPost($msgOptions, $topicOptions, $posterOptions);

      $insert = "INSERT INTO " . $table_name .
              " (wp_id, smf_topic_id, smf_msg_id) " .
              "VALUES ('" . $post_ID . "','" . $topicOptions['id'] . "','" . $msgOptions['id'] . "')";
      $results = $wpdb->query( $insert );
   
    }
    }

    return $post_ID;

}

function post_to_smf_link($link_text) {
    global $post;
    global $wpdb;
    $post_ID = $post->ID;
    $table_name = $wpdb->prefix . "post_to_smf";
    if ($wpdb->get_row("SELECT * FROM $table_name WHERE wp_id = $post_ID")) {
        //exist, update post
        $row = $wpdb->get_row("SELECT * FROM $table_name WHERE wp_id = $post_ID");
        $topic = $row->smf_topic_id;
        $forumurl = get_option('post_to_smf_smfurl');

        echo "<a href=\"$forumurl/index.php?topic=$topic.0\">$link_text</a>";
    }
    return true;
}
    
add_action('publish_post', 'post_to_smf');
register_activation_hook(__FILE__,'post_to_smf_install');

///////////////////////////////////////////////////////////////////////////////////////////////////////////////
// ADMIN PAGE

 function post_to_smf_admin() {
   add_options_page('post_to_smf Configuration', 'post_to_smf', 8, 'post_to_smf.php', 'post_to_smf_options');
 }

 function post_to_smf_options() {

  // variables for the field and option names 

  $hidden_field_name = 'mt_submit_hidden';

  $opt_smfpath = 'post_to_smf_smfpath';
  $opt_smfurl = 'post_to_smf_smfurl';
  $opt_defforum  = 'post_to_smf_defforum';
  $opt_username= 'post_to_smf_username';
  $opt_msgtext  = 'post_to_smf_msgtext';
  $opt_comments0 = 'post_to_smf_comments0';
  $opt_comments1 = 'post_to_smf_comments1';
  $opt_commentsx = 'post_to_smf_commentsx';
  $opt_reply  = 'post_to_smf_reply';
  $opt_onthefly = 'post_to_smf_onthefly';
  $opt_excludecats = 'post_to_smf_excludecats' ;
  $opt_excerpt_length = 'post_to_smf_excerpt_length' ;

  // Read in existing option value from database

  $opt_smfpath_val = get_option( $opt_smfpath );
  $opt_smfurl_val     = get_option( $opt_smfurl);
  $opt_defforum_val  = get_option( $opt_defforum );
  $opt_username_val = get_option( $opt_username );
  $opt_msgtext_val = get_option( $opt_msgtext);
  $opt_comments0_val = get_option( $opt_comments0);
  $opt_comments1_val = get_option( $opt_comments1);
  $opt_commentsx_val = get_option( $opt_commentsx);
  $opt_reply_val = get_option( $opt_reply);
  $opt_onthefly_val = get_option( $opt_onthefly);
  $opt_excludecats_val = get_option( $opt_excludecats);
  $opt_excerpt_length_val = get_option( $opt_excerpt_length);

  if( $_POST[ $hidden_field_name ] == 'Y' ) {

    $opt_smfpath_val = $_POST[ $opt_smfpath];
    $opt_smfurl_val     = $_POST[ $opt_smfurl];
    $opt_defforum_val  = $_POST[ $opt_defforum];
    $opt_username_val = $_POST[ $opt_username];
    $opt_msgtext_val = $_POST[ $opt_msgtext];
    $opt_comments0_val = $_POST[ $opt_comments0];
    $opt_comments1_val = $_POST[ $opt_comments1];
    $opt_commentsx_val = $_POST[ $opt_commentsx];
    $opt_reply_val = $_POST[ $opt_reply];
    $opt_onthefly_val = $_POST[ $opt_onthefly];
    $opt_excludecats_val = $_POST[ $opt_excludecats];
    $opt_excerpt_length_val = $_POST[ $opt_excerpt_length];

    $opt_msgtext_val = str_replace('\"','"',$opt_msgtext_val);
    $opt_msgtext_val = str_replace("\'","'",$opt_msgtext_val);
    $opt_msgtext_val = str_replace('\\\\','\\',$opt_msgtext_val);

    $opt_comments0_val = str_replace('\"','"',$opt_comments0_val);
    $opt_comments0_val = str_replace("\'","'",$opt_comments0_val);
    $opt_comments0_val = str_replace('\\\\','\\',$opt_comments0_val);

    $opt_comments1_val = str_replace('\"','"',$opt_comments1_val);
    $opt_comments1_val = str_replace("\'","'",$opt_comments1_val);
    $opt_comments1_val = str_replace('\\\\','\\',$opt_comments1_val);

    $opt_commentsx_val = str_replace('\"','"',$opt_commentsx_val);
    $opt_commentsx_val = str_replace("\'","'",$opt_commentsx_val);
    $opt_commentsx_val = str_replace('\\\\','\\',$opt_commentsx_val);

    $opt_reply_val = str_replace('\"','"',$opt_reply_val);
    $opt_reply_val = str_replace("\'","'",$opt_reply_val);
    $opt_reply_val = str_replace('\\\\','\\',$opt_reply_val);

    update_option( $opt_smfpath, $opt_smfpath_val );
    update_option( $opt_smfurl, $opt_smfurl_val );
    update_option( $opt_defforum, $opt_defforum_val );
    update_option( $opt_username, $opt_username_val );
    update_option( $opt_msgtext, $opt_msgtext_val );
    update_option( $opt_comments0, $opt_comments0_val );
    update_option( $opt_comments1, $opt_comments1_val );
    update_option( $opt_commentsx, $opt_commentsx_val );
    update_option( $opt_reply, $opt_reply_val );
    update_option( $opt_onthefly, $opt_onthefly_val );
    update_option( $opt_excludecats, $opt_excludecats_val );
    update_option( $opt_excerpt_length, $opt_excerpt_length_val );

  ?>

  <div class="updated"><p><strong><?php _e('Options saved.', 'mt_trans_domain' ); ?></strong></p></div>
  <?php }
     echo '<div class="wrap">';    
     echo "<h2>" . __( 'post_to_smf Plugin Options', 'mt_trans_domain' ) . "</h2>";
     echo '<a href="http://www.zonca.org/wp-post-to-smf">post_to_smf</a> by <a href="http://www.zonca.org">Zonca Webdesign</a>';

    if (!file_exists($opt_smfpath_val . '/SSI.php')) {
      echo '<div style="border:5px solid #aa0000; background:#feeeee; margin:0px;margin-top:10px; padding:4px;clear:both;"><p>';
      echo '&nbsp;&nbsp;<b>smf not found!</b> Please set smf local path before creating or updating posts</p></div>';
    }

  ?>

  <!-- Here is the form -->     
  <form name="form1" method="post" 
   action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
  <input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">

  <table class="form-table">

  <tr valign="top">
    <th scope="row"><label><?php _e("smf local path:", 'mt_trans_domain' ); ?></label></th>
    <td><input type="text" name="post_to_smf_smfpath" value="<?php echo $opt_smfpath_val; ?>" size="60">
    <?php 
        if (file_exists($opt_smfpath_val . '/SSI.php')) echo ('<br/>smf found'); 
        else echo ('<br><strong>smf NOT FOUND!</strong>');
     ?>
    </td>
    <td>Local path of your smf installation<br/>(probably <?php
          $wpl=substr(__FILE__,0,stripos(__FILE__, '/wp-content'));
          $bbl= substr($wpl,0,stripos($wpl,strrchr($wpl,'/'))) . '<em>/myforum</em>';
          echo ('<strong>' . $bbl . '</strong>'); 
         ?>)</td>
  </tr>

  <tr valign="top">
    <th scope="row"><label><?php _e("smf forum URL:", 'mt_trans_domain' ); ?></label></th>
    <td><input type="text" name="post_to_smf_smfurl" value="<?php echo $opt_smfurl_val; ?>" size="60"></td>
    <td>Public URL of your forum<br/>(i.e. "http://www.mydomain.com/myforum")</td>
  </tr>

  <tr valign="top">
    <th scope="row"><label><?php _e("Default forum for posts:", 'mt_trans_domain' ); ?></label></th>
    <td><input type="text" name="post_to_smf_defforum" value="<?php echo $opt_defforum_val; ?>" size="60"></td>
    <td>Numeric ID of the smf forum where topics for new posts will be created (if none specified in the 'forum' custom field of the WP post). If blank, no topics will be created unless 'forum' custom field of the post is set</td>
  </tr>

<!--  <tr valign="top">
    <th scope="row"><label><?php _e("Exclude categories", 'mt_trans_domain' ); ?></label></th>
    <td><input type="text" name="post_to_smf_excludecats" value="<?php echo $opt_excludecats_val;?>" size="60"></td>
    <td>Comma separated list of Wordpress categories ID's to be excluded. Any post in this categories will not have correspondent messages in the forum</td>
  </tr>
  --!>

  <tr valign="top">
    <th scope="row"><label><?php _e("Forum user ID:", 'mt_trans_domain' ); ?></label></th>
    <td><input type="text" name="post_to_smf_username" value="<?php echo $opt_username_val;?>" size="60"></td>
    <td>ID of the smf existing user used for creating new topics (i.e. 1 should be your admin account)</td>
  </tr>

  <tr valign="top">
    <th scope="row"><label><?php _e("Topic message text for posts:", 'mt_trans_domain' ); ?></label></th>
    <td><textarea id="details" name="post_to_smf_msgtext" rows="5" cols="60"><?php echo $opt_msgtext_val;?></textarea></td>
    <td>Message text for the link at the end of the forum post which links to the wordpress post, it should be something like "Read the full article"</td>
  </tr>

  <tr valign="top">
    <th scope="row"><label><?php _e("Maximum number of characters in the forum post:", 'mt_trans_domain' ); ?></label></th>
    <td><input type="text" name="post_to_smf_excerpt_length" value="<?php echo $opt_excerpt_length_val;?>" size="60"></td>
    <td>Maximum number of characters used in the forum post (using the {$post} variable, leave blank to have the full wordpress post in the forum. The plugin tries automatically to stop after a full sentence.</td>
  </tr>

<!--  <tr valign="top">
    <th scope="row"><label><?php _e("'0 messages in the forum' text:", 'mt_trans_domain' ); ?></label></th>
    <td><textarea id="comments0" name="post_to_smf_comments0" rows="3" cols="60"><?php echo $opt_comments0_val;?></textarea></td>
    <td>Text displayed when there are no comments in the forum for the post</tr>

  <tr valign="top">
    <th scope="row"><label><?php _e("'1 message in the forum' text:", 'mt_trans_domain' ); ?></label></th>
    <td><textarea id="comments1" name="post_to_smf_comments1" rows="3" cols="60"><?php echo $opt_comments1_val;?></textarea></td>
    <td>Text displayed when there is 1 comment in the forum for the post</tr>

  <tr valign="top">
    <th scope="row"><label><?php _e("'x messages in the forum' text:", 'mt_trans_domain' ); ?></label></th>
    <td><textarea id="commentsx" name="post_to_smf_commentsx" rows="3" cols="60"><?php echo $opt_commentsx_val;?></textarea></td>
    <td>Text displayed when there is more than 1 comments in the forum for the post<br/>
     Some text replacement keywords can be used:<br/>
    {$x} Actual number of messages in the forum</tr>
--!>
<!--
  <tr valign="top">
    <th scope="row"><label><?php _e("'Add a message in the forum' text:", 'mt_trans_domain' ); ?></label></th>
    <td><textarea id="textreply" name="post_to_smf_reply" rows="3" cols="60"><?php echo $opt_reply_val;?></textarea></td>
    <td>Text displayed in the 'add a reply in the forum' quick link</tr>
--!>


  </table>

  <p class="submit">
    <input type="submit" name="Submit" value="<?php _e('Update Options', 'mt_trans_domain' ); ?>" />
  </p>

  </form>
  </div>

<?php } 


 add_action('admin_menu',   'post_to_smf_admin');

?>
