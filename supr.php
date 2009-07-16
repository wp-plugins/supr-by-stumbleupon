<?php
/*
Plugin Name: Supr by StumbleUpon 
Plugin URI: http://su.pr/developers/Supr:WordPress_Plugin/ 
Description: Su.pr is the only URL shortener that gets you more traffic and save time posting to Twitter, Facebook and StumbleUpon. Get short URLs on your very own domain!
Version: 0.2
Author: StumbleUpon 
Author URI: http://www.stumbleupon.com/
*/
/*   
  

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

function su_dd()
{
	$args  = func_get_args();
	$pargs = array();
	foreach($args as $arg)
		array_push($pargs, print_r($arg, true));
	$line = join(' ', $pargs);
	su_syslog($line, 'debug', 'debug');
}

function su_syslog($msg, $ident, $prefix = "")
{
        global $SU_ENV;
        if ($prefix)
                $msg = "[$prefix] $msg";

        if ($SU_ENV === 'prd' && $prefix === 'debug') {
                $trace = debug_backtrace();
                if (isset($trace[1]['file']))
                {
                        $caller = basename($trace[1]['file']) . ':' . $trace[1]['line'];
                        $msg = "($caller) $msg";
                }
        }

        if ($SU_ENV !== 'prd' && ($ident == 'debug'))
        {
                $user = get_current_user();
                $msg = "($user) $msg";
        }
        // LOG_DEBUG makes more sense than LOG_INFO, but results in duplicated lines in /var/log/debug.
        $log = openlog($ident, LOG_NDELAY, LOG_LOCAL5);
        syslog(LOG_INFO, $msg);
        if($log)
                closelog();
}


global $wp_version,$version,$su_plugin_url;	

define('JDWP_API_POST_STATUS', 'http://twitter.com/statuses/update.json');

$version = "1.0";
$su_plugin_url = "http://su.pr/";

require_once( ABSPATH.WPINC.'/class-snoopy.php' );


if(isset($_GET['check_supr_install']))
{
	        supr_check_install();
}

if(isset($_GET['supr_settings_json']))
{
	supr_settings_json();
}



if(isset($_GET['supr'])) {
	$supr_hash = $_GET['supr'];
	custom_supr_redirect($supr_hash);
}



$exit_msg='Su.pr for Wordpress requires WordPress 2.5 or a more recent version. <a href="http://codex.wordpress.org/Upgrading_WordPress">Please update your WordPress version!</a>';

	if ( version_compare( $wp_version,"2.5","<" )) {
	exit ($exit_msg);
	}

function supr_check_install()
{
	$headers = new stdClass;
	$headers->version = "1";
	$headers->is_301 = get_option('supr-redirect');
	$headers->is_shorturl = get_option('supr-shorten');
	$settings = json_encode($headers);

	header("Status: 200");
	header("X-Supr-Settings: " . $settings);
	exit();
}

function supr_settings_json()
{
	$headers = new stdClass;
	$headers->version = "1";
	$headers->is_301 = get_option('supr-redirect');
	$headers->is_shorturl = get_option('supr-shorten');
	$settings = json_encode($headers);

	//header("Status: 200");
	//header("X-Supr-Settings: " . $settings);
	print($settings);
	exit();
}


// Function checks for an alternate URL to be tweeted. Contribution by Bill Berry.	
function external_or_permalink( $post_ID ) {
       $perma_link = get_permalink( $post_ID );
       return $perma_link;
}
	
// cURL query contributed by Thor Erik (http://thorerik.net)
function getfilefromurl($url) {
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_HEADER, 0 );
	curl_setopt( $ch, CURLOPT_VERBOSE, 0 );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $ch, CURLOPT_URL, $url );
	$output = curl_exec( $ch );
	curl_close( $ch );
	return $output;
}


function supr_post( $msg, $link ) {
	$apikey = get_option('supr-api-key');
	$login = get_option('supr-login');
        $snoopy = new Snoopy;

	if ((get_option( 'twitter-default' ) =='1' && get_option( 'fb-default' ) =='1') || (isset($_POST['twitter-this']) && isset($_POST['fb-this'])))
		$posturl = "http://su.pr/api/post?msg=".$msg."%20".$link."&login=".$login."&apiKey=".$apikey;
	elseif ((get_option( 'twitter-default' ) =='1') || (isset($_POST['twitter-this'])))
		$posturl = "http://su.pr/api/post?msg=".$msg."%20".$link."&login=".$login."&apiKey=".$apikey."&services[]=twitter";
	elseif ((get_option( 'fb-default' ) =='1') || (isset($_POST['fb-this'])))
	                $posturl = "http://su.pr/api/post?msg=".$msg."%20".$link."&login=".$login."&apiKey=".$apikey."&services[]=facebook";
	else
	{
		su_dd("FAIL");
		return 0;
        }
	su_dd("supr_post: $posturl"); 
	if ( $snoopy->fetchtext( $posturl ) ) {
                $results = json_decode($snoopy->results, true);                

                $shrink = $results['results'][urldecode($thispostlink)]['shortUrl'];

        } else {
               return 0; 
        }
        return $shrink;
}





function supr_publish( $post_ID )  {
	if ($_POST['action'] == 'post-quickpress-publish' || ($_POST['action'] == 'editpost' && $_POST['originalaction'] == "post"))
	{
		if (get_option( 'supr-enable' ) =='1')
		{
			$get_post_info = get_post( $post_ID );
			$authID = $get_post_info->post_author;
			$thisposttitle = urlencode( stripcslashes( strip_tags( $_POST['post_title'] ) ) );
			$thispostlink = urlencode( external_or_permalink( $post_ID ) );
			$thisblogtitle = urlencode( get_bloginfo( 'name' ) );
			$sentence = '';
			$customTweet = urlencode( stripcslashes( strip_tags( $_POST['su_twitter'] ) ) );                

			if ( ( strlen( $thisposttitle ) + 21) > 140 ) {
				$thisposttitle = substr( $thisposttitle, 0, 119 );
			}


			$tweet = !(empty($customTweet)) ? $customTweet : $thisposttitle;

			$shrink = supr_post($tweet, $thispostlink);
			return $post_ID;
		}
	}
	else
		return 0;
} 

function supr_publish_future( $post_obj )  {
        $supr_this = get_post_meta( $post_obj->ID, 'supr-this', TRUE );
                if ( (get_option( 'twitter-default' ) =='1' && get_option( 'supr-enable' ) !=='1') || (get_option( 'supr-enable' ) =='1' && $supr_this == '1') )
                {
                        $get_post_info = get_post( $post_obj->ID );
                        $authID = $get_post_info->post_author;
                        $thisposttitle = urlencode( stripcslashes( $get_post_info->post_title ) );
                        $thispostlink = urlencode( $post_obj->guid  );
                        $thisblogtitle = urlencode( get_bloginfo( 'name' ) );
                        $sentence = '';
			$supr_tweet = get_post_meta ($post_obj->ID, 'supr-tweet', TRUE);
                        $customTweet = urlencode( stripcslashes( strip_tags( $supr_tweet ) ) );                

                        if ( ( strlen( $thisposttitle ) + 21) > 140 ) {
                                $thisposttitle = substr( $thisposttitle, 0, 119 );
                        }


                        $tweet = !(empty($customTweet)) ? $customTweet : $thisposttitle;

                        $shrink = supr_post($tweet, $thispostlink);
                        return $post_obj->ID;
                }
                else
                        return 0;
}


function supr_quickpress( $post_ID ) {
	if (get_option( 'supr-enabled' ) =='1')
	{
		$post_ID = $post_ID->ID;

		$get_post_info = get_post( $post_ID );
		$post_status = $get_post_info->post_status;

		$thispostlink = urlencode( external_or_permalink( $post_ID ) );
		$thisposttitle = urlencode( strip_tags( $get_post_info->post_title ) );


		if ( ( strlen( $thisposttitle ) + 21) > 140 ) {
			$thisposttitle = substr( $thisposttitle, 0, 119 );
		}                


		$shrink = supr_post($thisposttitle, $thispostlink);
		return $post_ID;
	} 
	else
		return 0;
} 



// Add custom Tweet field on Post & Page write/edit forms
function su_add_twitter_textinput() {
	global $post, $su_plugin_url;
	$post_id = $post;
	if (is_object($post_id)) {
		$post_id = $post_id->ID;
	}
	$su_twitter = htmlspecialchars(stripcslashes(get_post_meta($post_id, 'su_twitter', true)));
	$su_tweet_this = get_post_meta($post_id, 'su_tweet_this', true);
		if ($su_tweet_this == 'no' || get_option( 'twitter-default' ) == '1' ) {
		$su_selected = ' checked="checked"';
		}
	?>
	<script type="text/javascript">
	<!-- Begin
	function countChars(field,cntfield) {
	cntfield.value = field.value.length;
	}
	//  End -->
	</script>
	<?php /* Compatibility with version 2.3 and below (needs to be tested.) */ ?>
	<?php if (get_option('supr-enable') == 1) { ?>
		<?php if (substr(get_bloginfo('version'), 0, 3) >= '2.5') { ?>
		<div id="supr" class="postbox closed">
			<h3 class="hndle"><?php _e('Post on Twitter &amp; Facebook with Su.pr', 'supr') ?></h3>
			<div class="inside">
			<div id="supr-twitter">
			<?php } else { ?>
				<div class="dbx-b-ox-wrapper">
					<fieldset id="twitdiv" class="dbx-box">
					<div class="dbx-h-andle-wrapper">
					<h3 class="dbx-handle"><?php _e('supr', 'supr') ?></h3>
					</div>
					<div class="dbx-c-ontent-wrapper">
					<div class="dbx-content">
					<?php } ?>
    <p>
	<textarea name="su_twitter" id="su_twitter" rows="2" cols="60"
	onKeyDown="countChars(document.post.su_twitter,document.post.twitlength)"
	onKeyUp="countChars(document.post.su_twitter,document.post.twitlength)"><?php echo $su_twitter ?></textarea>
	</p>
	<p><input readonly type="text" name="twitlength" size="3" maxlength="3" value="<?php echo strlen( $description); ?>" />
	<?php _e(' characters.') ?> 
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label for="supr-this"><input type="checkbox" name="supr-this" <?if (get_option( 'twitter-default' ) =='1') echo(" checked "); ?>  id="supr-this" value="1"  /> Post this on Twitter</label>&nbsp;&nbsp;<label for="fb-this"><input type="checkbox" name="fb-this" <?if (get_option( 'fb-default' ) =='1') echo(" checked "); ?>  id="fb-this" value="1"  /> Post this on Facebook</label><br />
	<?php _e('Twitter posts are a maximum of 140 characters; if your su.pr URL is appended to the end of your document, you have 119 characters available.', 'supr') ?> <a target="__blank" href="<?php echo $su_plugin_url; ?>"><?php _e('Get Support', 'supr') ?></a> &raquo;
</p>
	<?php if (substr(get_bloginfo('version'), 0, 3) >= '2.5') { ?>
	</div></div></div>
	<?php } else { ?>
	</div>
	</fieldset>
	</div>
	<?php } ?>
	<?php } ?>

	<?php
}
// Post the Custom Tweet into the post meta table


	

// Add the administrative settings to the "Settings" menu.
function supr_addTwitterAdminPages() {
    if ( function_exists( 'add_submenu_page' ) ) {
		 $plugin_page = add_options_page( 'supr', 'supr', 8, __FILE__, 'supr_wp_Twitter_manage_page' );
		 add_action( 'admin_head-'. $plugin_page, 'supr_addTwitterAdminStyles' );
    }
 }


function supr_insert_tb() {
	echo '<script src="http://su.pr/hosted_js" type="text/javascript"></script>';
}
 
function supr_addTwitterAdminStyles() {
 $supr_directory = get_bloginfo( 'wpurl' ) . '/' . PLUGINDIR . '/' . dirname( plugin_basename(__FILE__) );
	echo "
<style type=\"text/css\">
<!--
#supr h2 {
background: #fff url(http://su.pr/images/supr_logo_emboss.png) right center no-repeat;
padding: 16px 2px;
margin: 25px 0;
border: 1px solid #ddd;
-moz-border-radius: 3px;
-webkit-border-radius: 3px;
border-radius: 3px;
} 
#supr fieldset {
margin: 0;
padding:0;
border: none;
}
#supr form p {
background: #eaf3fa;
padding: 10px 5px;
margin: 4px 0;
border: 1px solid #eee;
}
#supr form .error p {
background: none;
border: none;
}
.floatright {
float: right;
}
.supr {
padding: 2px!important;
margin-top: 1.5em!important;
}
.twitter {
background: url($supr_directory/twitter.png)  right 50% no-repeat;
padding: 2px!important;
margin-top: 1.5em!important;
}
-->
</style>";
 }
// Include the Manager page
function supr_wp_Twitter_manage_page() {
	if ( file_exists ( dirname(__FILE__).'/supr-manager.php' )) {
    include( dirname(__FILE__).'/supr-manager.php' );
	} else {
	echo "<p>Couldn't locate the settings page.</p>";
	}
}

function plugin_action($links, $file) {
	if ($file == plugin_basename(dirname(__FILE__).'/supr.php'))
		$links[] = "<a href='options-general.php?page=supr/supr.php'>" . __('Settings', 'supr') . "</a>";
	return $links;
}


function custom_supr_redirect($hash)
{
	$blogurl = get_bloginfo('wpurl');
	$apikey = get_option('supr-api-key');
	$login = get_option('supr-login');
	$snoopy = new Snoopy;

	if ( $snoopy->fetchtext( "http://su.pr/api/forward?domain=".urlencode($blogurl)."&hash=".$hash."&login=".$login."&apiKey=".$apikey ) ) 
	{
		$results = json_decode($snoopy->results, true);
		if ($results['statusCode'] == "ERROR")
			return 0;
		$shrink = $results['results'][$hash]['forwardUrl'];

		header("Location: $shrink");
		exit;
	
	} else {
		return 0;
	}
	return 0;

}


function supr_rewriterules($current){
	$therule = '';
	$therule .= "\nRewriteCond %{REQUEST_FILENAME} !-f\n";
	$therule .= "\nRewriteCond %{REQUEST_FILENAME} !-d\n";	 
	$therule .= "\nRewriteRule ^[a-zA-Z0-9]{1,4}$ ";
	$therule .= "?supr=$0\n"; 
	$therule .= "\nRewriteRule ^check_supr_install$ ";
	$therule .= "?check_install=$0\n";
	$therule .= "\nRewriteRule ^supr_settings.json$ ";
	$therule .= "?supr_settings_json=$0\n";
	//}
      $broken = explode("\n", $current);
         foreach ($broken as $value) {
            if(strpos($value, "RewriteBase") !== false){ $value .= $therule; }
         else { $value .= "\n"; }
         $rules .= $value;
         }
   return $rules;
}



function add_futurepost_meta( $post_ID) {
	if ( (get_option( 'twitter-default' ) =='1' && get_option( 'supr-enable' ) !=='1') || (get_option( 'supr-enable' ) =='1' && $_POST['supr-this']))
	{
		add_post_meta ( $post_ID, 'supr-this', TRUE );
		if (isset($_POST['su_twitter']))
			add_post_meta ( $post_ID, 'supr-tweet', $_POST['su_twitter'] );
	}
	return $post_ID;

}
add_filter('mod_rewrite_rules', 'supr_rewriterules');

add_filter('plugin_action_links', 'plugin_action', -10, 2);

if ( get_option( 'supr-toolbar' )=='1' ) {
add_action( 'wp_head', 'supr_insert_tb' );
}

add_action( 'publish_post', 'supr_publish');
add_action( 'future_to_publish', 'supr_publish_future');
add_action( 'future_post', 'add_futurepost_meta' );




// only add supr box if enabled
if ( get_option( 'supr-enable' )=='1' ) {


	if ( substr( get_bloginfo( 'version' ), 0, 3 ) >= '2.5' ) {
		add_action( 'edit_form_advanced','su_add_twitter_textinput' );
		add_action( 'edit_page_form','su_add_twitter_textinput' );
	} else {
		add_action( 'dbx_post_advanced','su_add_twitter_textinput' );
		add_action( 'dbx_page_advanced','su_add_twitter_textinput' );
	}
}

add_action( 'admin_menu', 'supr_addTwitterAdminPages' );
?>
