<?php
	//update_option( 'twitterInitialised', '0' );
	// FUNCTION to see if checkboxes should be checked
	function su_checkCheckbox( $theFieldname ) {
		if( get_option( $theFieldname ) == '1'){
			echo 'checked="checked"';
		}
	}	
	$message = "";
	//SETS DEFAULT OPTIONS
	if( get_option( 'twitterInitialised') != '1' ) {

		$message = __("Set your su.pr login and API key to use this plugin! ");
	}
	
	if ( isset($_POST['submit-type']) && $_POST['submit-type'] == 'clear-error' ) {
		$message =  __("supr Errors Cleared");
	}


	if ( isset($_POST['submit-type']) && $_POST['submit-type'] == 'options' ) {
		//UPDATE OPTIONS
		update_option( 'supr-api-key', $_POST['supr-api-key'] );
		update_option( 'supr-login', $_POST['supr-login'] );
		update_option( 'supr-enable', $_POST['supr-enable'] );
		update_option( 'twitter-default', $_POST['twitter-default'] );
		update_option( 'fb-default', $_POST['fb-default'] );
		update_option( 'supr-redirect', $_POST['supr-redirect'] );
		update_option( 'supr-toolbar', $_POST['supr-toolbar'] );
		update_option( 'supr-shorten', $_POST['supr-shorten'] );
		
		$message = "Su.pr Options Updated";

	}

	// Check whether the server has supported for needed functions.


?>
<?php if ($message) { ?>
<div id="message" class="updated fade"><p><?php echo $message; ?></p></div>
<?php } ?>
<div id="dropmessage" class="updated" style="display:none;"></div>

<div class="wrap">

<h2><?php _e("Su.pr by StumbleUpon"); ?></h2>
		
		
	<form method="post" action="">
	<h3 style="margin-bottom: 0;"><?php _e('Posting to Twitter'); ?></h3>
	<table class="form-table">
	<tr>
	<th scope="row" class="th-full">
		<label for="supr-enable">
		<?php _e("Post to Twitter"); ?></label>
	</th><td>
		<label for="supr-enable">
		<input type="checkbox" name="supr-enable" id="supr-enable" value="1" <?php su_checkCheckbox('supr-enable')?> />
		<?php _e("Display option to customize posts to Twitter or Facebook when I add a new blog"); ?></label>
	</th>
	</tr>
	<tr>
	<th scope="row" class="th-full">
		<label for="twitter-default">
		<?php _e("Default Twitter setting"); ?></label>
	</th><td>
		<label for="twitter-default">
		<input type="checkbox" name="twitter-default" id="twitter-default" value="1" <?php su_checkCheckbox('twitter-default')?> />
		<?php _e("Post to Twitter when I add a new blog"); ?></label>
	</th>
	</tr>
<tr>
        <th scope="row" class="th-full">
                <label for="fb-default">
                <?php _e("Default Facebook setting"); ?></label>
        </th><td>
                <label for="fb-default">
                <input type="checkbox" name="fb-default" id="fb-default" value="1" <?php su_checkCheckbox('fb-default')?> />
                <?php _e("Post to Facebook when I add a new blog"); ?></label>
        </th>
        </tr>
	<tr valign="top">
        <th scope="row">
                <label for="supr-shorten">
                <?php _e("My own short-urls"); ?></label>
        </th><td>
                <label for="supr-shorten">
                <input type="checkbox" name="supr-shorten" id="supr-shorten" value="1" <?php su_checkCheckbox('supr-shorten')?> />
                <?php _e("Host short URLs on my own domain"); ?></label>
                <span class="setting-description"><?php _e('(i.e., http://yourdomain.com/ASDF)'); ?></span>
                <div style="background-color:#FFFBE4;border-color:#DFDFDF;-moz-border-radius:5px;border-style:solid;border-width:1px;margin:5px;padding:3px 5px;">
                	Please follow these <a target="_blank" href="http://stumbleupon.com/developers/Supr:WordPress_Plugin/">3 easy steps</a> to setup short URLs on your domain.
                </div>
        </td>
        </tr>
        <tr valign="top">
        <th scope="row">
        <label for="supr-toolbar">
        <?php _e("StumbleUpon toolbar"); ?></label>
        </th><td>
        <label for="supr-toolbar">
        <input type="checkbox" name="supr-toolbar" id="supr-toolbar" value="1" <?php su_checkCheckbox('supr-toolbar')?> />
        <?php _e("Get more traffic and show the StumbleUpon toolbar when visitors clicks on a short URL"); ?></label>
        <span class="setting-description"><?php _e('(The more visitors thumb-up the more traffic you will receive)'); ?></span>
        </td>
        </tr>
	<tr valign="top">
	<th scope="row">
	<label for="supr-redirect">
	<?php _e("Search engine friendly links"); ?></label>
	</th><td>
	<label for="supr-redirect">
	<input type="checkbox" name="supr-redirect" id="supr-redirect" value="1" <?php su_checkCheckbox('supr-redirect')?> />
	<?php _e("Use search engine friendly short URLs (301 redirect)"); ?></label>
	</td>
	</tr>


	<tr valign="top">
		<th scope="row"><label for="supr-api-key"><?php _e("Su.pr API Key"); ?></label></th>
		<td><input type="text" name="supr-api-key" id="supr-api-key" size="60" maxlength="120" value="<?php echo(get_option('supr-api-key')) ?>" class="regular-text code" />
		<span class="setting-description"><?php _e('Don\'t have one?  <a href="http://su.pr/settings/" target="_blank">Get one here</a>'); ?></span>
		</td>
	</tr>
    <tr valign="top">
        <th scope="row"><label for="supr-login"><?php _e("Your Su.pr/StumbleUpon username"); ?></label></th>
        <td>
			<input type="text" name="supr-login" id="supr-login" size="60" maxlength="120" value="<?php echo(get_option('supr-login')) ?>" class="regular-text code"/>
			<span class="setting-description"><?php _e('<a href="http://su.pr/join/" target="_blank">Create an account</a>'); ?></span>
        </td>
    </tr>
	</table>
	<!-- !!! not supported yet
	<h3 style="margin: 20px 0 0 0;"><?php _e('Scheduling Options'); ?></h3>
	<table class="form-table">
	<tr valign="top">
	<th scope="row">
		<label for="supr-besttime">
		<?php _e("Schedule for best times"); ?></label>
	</th><td>
		<label for="supr-besttime">
		<input type="checkbox" name="supr-besttime" id="supr-besttime" value="1" <?php su_checkCheckbox('supr-besttime')?> />
		<?php _e("Default to post tweet at the best times to get more clicks"); ?></label>
	</td>
	</tr>
	<tr valign="top">
	<th scope="row">
		<label for="supr-dailylimit">
		<?php _e("Daily limit"); ?></label>
	</th><td>
		<label for="supr-dailylimit">
		<?php _e("Schedule no more than"); ?> 
		<select name="supr-dailylimit" id="supr-dailylimit">
			<option value="1">1 post</option>
			<option value="2">2 posts</option>
			<option value="5" default>5 posts</option>
			<option value="10">10 posts</option>
			<option value="20">20 posts</option>
			<option value="50">50 posts</option>
			<option value="90">90 posts</option>
		</select>
		<?php _e("per day"); ?> </label>
	</td>
	</tr>
	</table>
	<h3 style="margin:20px 0 0 0;"><?php _e('Share my best content'); ?></h3>
	<table class="form-table">
	<tr valign="top">
	<th scope="row">
		<label for="supr-besttime">
		<?php _e("My best content"); ?></label>
	</th><td>
		<label for="supr-besttime">
		<input type="checkbox" name="supr-besttime" id="supr-besttime" value="1" <?php su_checkCheckbox('supr-besttime')?> />
		<?php _e("Display my best content in the sidebar"); ?></label>
		<span class="setting-description"><?php _e('based on user ratings on StumbleUpon, views and retweets'); ?></span>
	</td>
	</tr>
	</table>

	<h3 style="margin: 20px 0 0 0;"><?php _e('Advanced options'); ?></h3>
	-->
	<div style="padding-top: 20px;">
		<input type="hidden" name="submit-type" value="options" />
		<input type="submit" name="submit" value="<?php _e("Save options"); ?>" class="button-primary" />
	</div>
	</form>


<div class="wrap">
		
</div>
