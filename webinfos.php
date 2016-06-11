<?php
/*
Plugin Name: Webinfos Plugin
Plugin URI:
Description: A plugin showing a customisable welcome message in the dashboard.
Version: 1.2
Author: ymjid
Author URI:
License: GPLv2
Text Domain: webinfos
Domain Path: /languages
 
** 
** Webinfos plugin, a software to show a custom message in the Wordpress dashboard using his own dashboard widget.
** This plugin have been programmed by  ymjid <m.jid.yannis@gmail.com>
** Copyright (C) 2016  ymjid
**
** This program is free software; you can redistribute it and/or
** modify it under the terms of the GNU General Public License
** as published by the Free Software Foundation; either version 2
** of the License, or any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**
*/

class Webinfos_Plugin
{
    public function __construct()
    {
    	register_activation_hook(__FILE__, array('Webinfos_Plugin', 'webinfos_install'));
   		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('plugins_loaded', array($this, 'webinfos_textdomain'));
		add_action('wp_loaded', array($this, 'save_data'));
		add_action('wp_loaded', array($this, 'send_feedback'));
		add_action('admin_init', array($this, 'register_feedback'));
		add_action('admin_init', array($this, 'register_active_msg'));
		add_action('admin_init', array($this, 'register_actions'));
		add_action('admin_init', array($this, 'register_settings'));
		add_action( 'wp_ajax_webinfos_action', array($this,  'webinfos_action_callback' ));
		include(plugin_dir_path(__FILE__).'/custom_welcome.php');
		new Webinfos_Welcome();
		register_uninstall_hook(__FILE__, array('Webinfos_Plugin', 'webinfos_uninstall'));
    }

/*
** At the installation of the plugin, create a database table needed to save messages
*/
    public function webinfos_install()
    {
    	global $wpdb;
    	
    	$wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}webinfos (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, website VARCHAR(255) NOT NULL,imgurl VARCHAR(255) NOT NULL, imgalt VARCHAR(255) NOT NULL, msgtxt VARCHAR(500) NOT NULL, contact BOOL NOT NULL, contactimgurl VARCHAR(255) NOT NULL, contactimgalt VARCHAR(255) NOT NULL, tel VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, video VARCHAR(255) NOT NULL, attachment VARCHAR(5000) NOT NULL, state ENUM('alive', 'dead', 'birth') NOT NULL);");
    	
    	$upload_loc=wp_upload_dir();
    	if (!file_exists($upload_loc['basedir'].'/webinfos/')) {
    		mkdir($upload_loc['basedir'].'/webinfos', 0755, true);
   		 	mkdir($upload_loc['basedir'].'/webinfos/img', 0755, true);
   		 	mkdir($upload_loc['basedir'].'/webinfos/attachment', 0755, true);
   		 	mkdir($upload_loc['basedir'].'/webinfos/video', 0755, true);
		}
		
		add_option( 'webinfos_active_msg', '0');
		add_option( 'webinfos_active_msg_all', '0');
    }

/*
** At the Uninstallation of the plugin, remove the database and its data.
*/
    public function webinfos_uninstall()
    {
    	global $wpdb;
    	
		$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}webinfos;");
		
		 function rrmdir($dir) { 
   		if (is_dir($dir)) { 
   		  $objects = scandir($dir); 
     		foreach ($objects as $object) { 
       			if ($object != "." && $object != "..") { 
         			if (is_dir($dir."/".$object))
          				 rrmdir($dir."/".$object);
        			 else
           				unlink($dir."/".$object); 
      				 } 
     		}
     		rmdir($dir); 
   		} 
 		}
 		$upload_loc=wp_upload_dir();
		$dir=$upload_loc['basedir'].'/webinfos/';
		if (file_exists($dir)) {
			rrmdir($dir);
		}
		delete_option('webinfos_active_msg');
		delete_option('webinfos_active_msg_all');


    }
    
/*
** Load the language files which translate settings text
*/
    public function webinfos_textdomain() {
    	$plugin_dir = basename(dirname(__FILE__)).'/languages';
    	load_plugin_textdomain('webinfos', false, $plugin_dir);	
    }

/*
** Save or edit a message and its relative datas
*/
    public function save_data() {
    	global $wpdb;
    	
		global $error;
		$error= "";
		if (isset($_POST['testactive'])) {
			$safe_active = intval( $_POST['testactive'] );
			if ( ! $safe_active ) {
				$safe_active='';
  				$error= $error + __('An error occurs with message activation', 'webinfos') + "|";
			}
			if (isset($_POST['active_msg'])) {
				$test_active = is_numeric($_POST['active_msg']);
				if ($test_active == 0) {
					$error= $error + __('An error occurs the message id', 'webinfos') + "|";
				}
				else {
					update_option( 'webinfos_active_msg', $_POST['active_msg']);
					if ($_POST['active_msg'] != 0) {
						add_action( 'admin_notices', 'webinfos_admin_notice__success' );
				
						function webinfos_admin_notice__success() {
    						?>
   					 		<div class="notice notice-success is-dismissible">
       							 <p><?php _e( 'The message have been activated successfully', 'webinfos' ); ?></p>
    						</div>
   					 	<?php
						}	
					}
				}
			}
			if (isset($_POST['active_msg_all'])) {
				$testallmsg=sanitize_text_field($_POST['active_msg_all']);
				if (!$testallmsg) {
					/*error*/
					update_option( 'webinfos_active_msg_all', '0');
					$error= $error + __('An error occurs with messages checkbox', 'webinfos') + "|";
				}
				else {
    				update_option( 'webinfos_active_msg_all', '1');
					add_action( 'admin_notices', 'webinfos_admin_notice__success' );
				
					function webinfos_admin_notice__success() {
    					?>
   					 	<div class="notice notice-success is-dismissible">
       						 <p><?php _e( 'All messages have been activated successfully', 'webinfos' ); ?></p>
    					</div>
   					 <?php	
					}
				}
			}
			else {
				update_option( 'webinfos_active_msg_all', '0');
			}

		}
		if (get_option("webinfos_active_msg")== 0) {
			add_action( 'admin_notices', 'webinfos_admin_notice__warning' );
						
			function webinfos_admin_notice__warning() {
 				?>
 				<div class="update-nag notice">
    				<p><?php _e("None of messages have been activated. Dashboard won't show any of your message.", 'webinfos'); ?></p>
  				</div>
  				<?php
			}
		}
    	// if $_POST['test'] exist, message datas are fillable
    	if (isset($_POST['test'])) {
    		$safe_test = intval( $_POST['test'] );
			if ( ! $safe_test ) {
				$safe_test='';
  				$error= $error + __('An error occurs with form verification', 'webinfos') + "|";
			}
		
			$safe_drop = is_numeric($_POST['dropused']);
			if ($safe_drop == 0) {
				/*error*/
				$_POST['dropused']='0';
				$error= $error + __('An error occurs with attachment files', 'webinfos') + "|";
			}
			
			$safe_tel = is_numeric($_POST['webinfos_tel'] );
			if ( $safe_tel== 0 ) {
  				$tel = '';
  				$error= $error + __('An error occurs with contact phone number', 'webinfos') + "|";
			}
			else {
				$tel=$_POST['webinfos_tel'];
			}
			if ( strlen( $tel ) > 15 ) {
		  		$tel = substr( $tel, 0, 15 );
			}

			$title = sanitize_text_field($_POST['webinfos_title']);
		
			$imgalt = sanitize_text_field($_POST['webinfos_imgalt']);
		
			$contactimgalt = sanitize_text_field($_POST['webinfos_contactimgalt']);	
		
			$website = esc_url_raw($_POST['webinfos_website'], array('http', 'https'));	
		
			$msgtxt =  wp_kses_post($_POST['webinfos_msgtxt']);
		
			$email= sanitize_text_field($_POST['webinfos_email']);
			$testemail = is_email($email);
			if ($testemail == false) {
				/*error*/
				$email='';
				$error= $error + __('An error occurs with contact email', 'webinfos') + "|";
			}
		
    		// $contact = 1 -> show contact infos | $contact = 0 -> hide contact infos
    		if (isset($_POST['webinfos_contact'])) {
    			$testcontact = sanitize_text_field($_POST['webinfos_contact']);
				if (!$testcontact) {
					/*error*/
					$contact="0";
					$error= $error + __('An error occurs with contact checkbox', 'webinfos') + "|";
				}
				else {
    				$contact="1";
				}
    		}
    		else {
    			$contact="0";
    		}
    		// test variables to test if imgs/video/files have been changed and files have been drag | default values : false
    		$testimg1='false';
    		$testimg2='false';
    		$testvideo='false';
    		$testattach='false';
    		$testdrop='false';
    		// filelist is used to save attachment files in the database
    		$filelist="";
    		if (isset($_POST['dropused']) && $_POST['dropused']=="1") {
    			$testdrop='true';	
    		}
    		// if testdrop = false -> upload files from the file button | if testdrop = true -> files are already uploaded
    		if ($testdrop=='false') {
    			if(isset($_FILES['webinfos_attachment']) && is_array($_FILES['webinfos_attachment']['name']) && $_FILES['webinfos_attachment']['name'][0] != ""){
    				$upload_loc=wp_upload_dir();
    				$dirattach=$upload_loc['basedir'].'/webinfos/attachment/';
    				for ($i=0; $i< sizeof($_FILES['webinfos_attachment']['name']); $i++) {
    					$testattachmentfile = validate_file( $_FILES['webinfos_attachment']['tmp_name'][$i]);
						if ($testattachmentfile == 0) {
    						move_uploaded_file ( $_FILES['webinfos_attachment']['tmp_name'][$i] , $dirattach.$_FILES['webinfos_attachment']['name'][$i] );
    						if ($i+1 == sizeof($_FILES['webinfos_attachment']['name'])) {
    							$filelist=$filelist.$_FILES['webinfos_attachment']['name'][$i];
    						}
    						else {
    							$filelist=$filelist.$_FILES['webinfos_attachment']['name'][$i].',';	
    						}
						}
    				}
    				if ($filelist != "") {
    					$testattach='true';
    				}
    				else {
    					/*error*/
    					$error= $error + __('An error occurs with attachment file list', 'webinfos') + "|";
    				}
    			}
    		}
    		// upload logo img
    		if(isset($_FILES['webinfos_imgurl']) && $_FILES['webinfos_imgurl']['name']!=""){
    			$upload_loc=wp_upload_dir();
    			$dirimg=$upload_loc['basedir'].'/webinfos/img/';
    			$testimgurl= validate_file( $_FILES['webinfos_imgurl']['tmp_name']);
    			$filepath = pathinfo($_FILES['webinfos_imgurl']['name']);
    			if ($testimgurl==0 && $filepath['extension']=='png' || $filepath['extension']=='jpg') {
					move_uploaded_file ( $_FILES['webinfos_imgurl']['tmp_name'] , $dirimg.$_FILES['webinfos_imgurl']['name'] );
					$testimg1='true';
    			}
    			else {
    				/*error*/
    				$error= $error + __('An error occurs with uploaded logo', 'webinfos') + "|";
    			}
    		}
    		// upload cantact phpto
    		if(isset($_FILES['webinfos_contactimgurl']) && $_FILES['webinfos_contactimgurl']['name']!=""){
    			$upload_loc=wp_upload_dir();
    			$dirimg=$upload_loc['basedir'].'/webinfos/img/';
    			$testcontactimgurl = validate_file( $_FILES['webinfos_contactimgurl']['tmp_name']);
    			$filepath = pathinfo($_FILES['webinfos_contactimgurl']['name']);
    			if ($testcontactimgurl==0  && $filepath['extension']=='png' || $filepath['extension']=='jpg') {
					move_uploaded_file ( $_FILES['webinfos_contactimgurl']['tmp_name'] , $dirimg.$_FILES['webinfos_contactimgurl']['name'] );
					$testimg2='true';
    			}
    			else {
    				/*error*/
    				$error= $error + __('An error occurs with uploaded photo', 'webinfos') + "|";
    			}
    		}
    		// upload msg video
    		if(isset($_FILES['webinfos_video']) && $_FILES['webinfos_video']['name']!=""){
    			$upload_loc=wp_upload_dir();
    			$dirvid=$upload_loc['basedir'].'/webinfos/video/';
    			$testvideofile = validate_file( $_FILES['webinfos_video']['tmp_name']);
    			$filepath = pathinfo($_FILES['webinfos_video']['name']);
    			if ($testvideofile == 0 && $filepath['extension']=='mp4') {
					move_uploaded_file ( $_FILES['webinfos_video']['tmp_name'] , $dirvid.$_FILES['webinfos_video']['name'] );
					$testvideo='true';
    			}
    			else {
    				/*error*/
    				$error= $error + __('An error occurs with uploaded video', 'webinfos') + "|";
    			}
    		}
    		$imgurl=$_FILES['webinfos_imgurl']['name'];
			$contactimgurl=$_FILES['webinfos_contactimgurl']['name'];
			$video=$_FILES['webinfos_video']['name'];
			$attach=$filelist;
			$add_msg="false";
			$edit_msg="false";
			// if $_POST['webinfos_msg'] doesn't exist -> msg doesn't exist so we add it in the database
			if (!isset($_POST['webinfos_msg'])) {
				// if $testdrop = false -> we add the msg in the database with its data | if $testdrop = true -> msg attachment files already exist so we edit the msg in database with its missing datas 
				if ($testdrop=='false') {
					$wpdb->insert("{$wpdb->prefix}webinfos", 
					array('title' => $title, 'website' => $website, 'imgurl' => $_FILES['webinfos_imgurl']['name'], 'imgalt' => $imgalt, 'msgtxt' => $msgtxt, 'contact' => $contact, 'contactimgurl' => $_FILES['webinfos_contactimgurl']['name'], 'contactimgalt' => $contactimgalt, 'tel' => $tel, 'email' => $email, 'video' => $_FILES['webinfos_video']['name'], 'attachment' => $filelist, 'state' => 'alive')
					);
					$add_msg="true";
				}
				else {
					$state='birth';
					$updatesql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}webinfos WHERE state = %s", $state);
					$row = $wpdb->get_row($updatesql);
					$attach=$row->attachment;
						$wpdb->update("{$wpdb->prefix}webinfos", 
						array('title' => $title, 'website' => $website, 'imgurl' => $imgurl, 'imgalt' => $imgalt, 'msgtxt' => $msgtxt, 'contact' => $contact, 'contactimgurl' => $contactimgurl, 'contactimgalt' => $imgalt, 'tel' => $tel, 'email' => $email, 'video' => $video, 'attachment' => $attach, 'state' => 'alive'), array('state' => 'birth')
						);
						$add_msg="true";
				}
			}
			// if $_POST['webinfos_msg'] exists and is != 0 -> we want to edit message data
			if (isset($_POST['webinfos_msg']) &&  $_POST['webinfos_msg'] != '0') {
				$safe_nummsg = is_numeric($_POST['webinfos_msg']);
				if ( ! $safe_nummsg ) {
  					$safe_nummsg = '';
				}
				else {
					$num=$safe_nummsg;
					$updatesql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}webinfos WHERE id = %d", $num);
					$row = $wpdb->get_row($updatesql);
					if (!is_null($row)) {
							if ($testimg1=='false') {
								$imgurl=$row->imgurl;
							}
							if ($testimg2=='false') {
								$contactimgurl=$row->contactimgurl;
							}
							if ($testvideo=='false') {
								$video=$row->video;
							}
							if ($testattach=='false') {
								$attach=$row->attachment;
							}
							$wpdb->update("{$wpdb->prefix}webinfos", 
							array('title' => $title, 'website' => $website, 'imgurl' => $imgurl, 'imgalt' => $imgalt, 'msgtxt' => $msgtxt, 'contact' => $contact, 'contactimgurl' => $contactimgurl, 'contactimgalt' => $contactimgalt, 'tel' => $tel, 'email' => $email, 'video' => $video, 'attachment' => $attach), array('id' => $num)
							);
							$edit_msg="true";
					}
				}
			}
			if ($error != "") {
				/*error msg*/
				do_action('admin_notices',$error);
				add_action( 'admin_notices', 'webinfos_admin_notice__error');
				
				function webinfos_admin_notice__error($error) {
					global $error;
				?>
					<div class="notice notice-error">
					<?php
					$errors = explode("|", $error);
					for ($i=0; $i<sizeof($errors); $i++){
						?><p><?php echo $errors[$i]; ?></p>
					<?php
					}
					?>
					</div> 
				<?php
				}
				
			}
			else {
				add_action( 'admin_notices', 'webinfos_admin_notice__success' );
				if ($edit_msg == "true") {
			    	function webinfos_admin_notice__success() {
    					?>
   						 <div class="notice notice-success is-dismissible">
       					 	<p><?php _e( 'Modifications saved successfully', 'webinfos' ); ?></p>
    					</div>
   					 <?php
					}
				}
				if ($add_msg == "true") {
					function webinfos_admin_notice__success() {
    					?>
   						 <div class="notice notice-success is-dismissible">
       					 	<p><?php _e( 'Message added successfully', 'webinfos' ); ?></p>
    					</div>
   					 <?php
					}
				}
    		}
    	}
    	// if $_POST['test'] doesn't exist, message datas aren't fillable
    	else {
    		$error= "";
    		//variable to check msg state
    		$erase_msg='false';
    		if (isset($_POST['webinfos_nummsg']) ) {
    			$testnum = is_numeric($_POST['webinfos_nummsg']);
    			if ($testnum != 0) {
    				$num=abs($_POST['webinfos_nummsg']);
    			}
    			else {
    				/*error*/
    				$error= $error + __('An error occurs with message id', 'webinfos') + "|";
    			}
    		}
    		// if $_POST['webinfos_nummsg'] < 0 -> we want to erase the msg (for real we edit the msg state)
    		if (isset($num)  && $_POST['webinfos_nummsg'] < 0) {
    			$updatesql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}webinfos WHERE id = %d", $num);
				$row = $wpdb->get_row($updatesql);
				if (!is_null($row)) {
					$wpdb->update("{$wpdb->prefix}webinfos", 
					array('state' => 'dead'), array('id' => $num)
					);
					$erase_msg='true';
				}
			}
			// if we erased the active msg we change the active msg to the first usable msg or we don't active msg
			if (isset($num) && $num == get_option('webinfos_active_msg') && $erase_msg=='true') {
				$state='alive';
				$updatesql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}webinfos WHERE id = %s", $state);
				$row = $wpdb->get_row($updatesql);
				if (!is_null($row)) {
					update_option( 'webinfos_active_msg', $row->id );
				}
				else {
					update_option( 'webinfos_active_msg', '0');
				}
			}
			if ($erase_msg=='true'){
				if ($error !="") {
					/* error msg*/
					do_action('admin_notices',$error);
					add_action( 'admin_notices', 'webinfos_admin_notice__error');
				
					function webinfos_admin_notice__error($error) {
						global $error;
					?>
						<div class="notice notice-error">
						<?php
						$errors = explode("|", $error);
						for ($i=0; $i<sizeof($errors); $i++){
							?><p><?php echo $errors[$i]; ?></p>
						<?php
						}
						?>
						</div> 
					<?php
					}
				}
				else {
					add_action( 'admin_notices', 'webinfos_admin_notice__success' );
				
					function webinfos_admin_notice__success() {
    					?>
   					 	<div class="notice notice-success is-dismissible">
       						 <p><?php _e( 'The message have been erased successfully', 'webinfos' ); ?></p>
    					</div>
   					 <?php
					}
				}
			}		
    	}
    	
	}
	
	// add a webinfos setting button to the admin button
	public function add_admin_menu() {
		add_menu_page(__('Webinfos plugin', 'webinfos'), __('Webinfos Settings', 'webinfos'), 'manage_options', 'webinfos', array($this, 'menu_html'));

	}
	
	// webinfos setting html code
	public function menu_html(){
		global $wpdb;
		
			// define the required format for the logo
				$imgext= array('png', 'jpg');
			$imgformat="";
			for ($i=0; $i< sizeof($imgext); $i++) {
				if ($i+1 == sizeof($imgext)){
					$imgformat=$imgformat.$imgext[$i];
				}
				else {
					$imgformat=$imgformat.$imgext[$i]."/";
				}
			}
			// define the required format for the contact photo
			$imgext2= array('png', 'jpg');
			$imgformat2="";
			for ($i=0; $i< sizeof($imgext2); $i++) {
				if ($i+1 == sizeof($imgext2)){
					$imgformat2=$imgformat2.$imgext2[$i];
				}
				else {
					$imgformat2=$imgformat2.$imgext2[$i]."/";
				}
			}
			// define the required format for the video
			$vidext= array('mp4');
			$vidformat="";
			$vidformat=$vidformat.$vidext[0];	
		$GLOBALS['imgext']=$imgext;
		$GLOBALS['imgext2']=$imgext2;
		$GLOBALS['vidext']=$vidext;
		// Register the script first.
		wp_register_script( 'webinfos_handle', plugin_dir_url(__FILE__).'js/webinfos.js' );
		// Now we can localize the script with our data.
		$translation_array = array( 'imgerrormsg' => sprintf(__('The uploaded file must be in %s format', 'webinfos'), $imgformat ), 'imgerrormsg2' => sprintf(__('The uploaded file must be in %s format', 'webinfos'), $imgformat2 ), 'viderrormsg' => sprintf(__('The uploaded file must be in %s format', 'webinfos'), $vidformat ), 'charmsg' => sprintf(__('character left.', 'webinfos')), 'charsmsg' => sprintf(__('characters left.', 'webinfos')), 'newlist' => sprintf(__('New attachments list : ', 'webinfos')));
		wp_localize_script( 'webinfos_handle', 'object_name', $translation_array );
		// The script can be enqueued now or later.
		wp_enqueue_script( 'webinfos_handle' );
		$keys=array_keys($translation_array);
		$upload_loc=wp_upload_dir();
		$plugin_dir=$upload_loc['basedir'].'/webinfos/';
		$plugin_infos=get_plugin_data(plugin_dir_path(__FILE__).'webinfos.php'); 
		echo '<div class="wrap">';
		echo '<div class="webinfos_options_container">';
		echo '<h1>'.esc_html(get_admin_page_title()).'</h1>';
				?>
		<h2><?php echo sprintf(__('Version %s', 'webinfos'), $plugin_infos['Version']); ?></h2>
		<p><?php _e('Welcome to the setting panel of Webinfos plugin', 'webinfos'); ?></p>
		<!-- Custom menu to navigate easely through forms -->
		<div class="custommenu">
				<?php
				$state='alive';
				$selectsql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}webinfos WHERE state = %s", $state);
				$result = $wpdb->get_results($selectsql);
				if (!is_null($result)) {
				?>
					<div id="msgbutton2" class="custombutton" onClick="toggleForm('2');">
						<?php _e('Message Selection', 'webinfos'); ?>
					</div>
			<?php
				}
			?>
			<div id="msgbutton1" class="custombutton" onClick="toggleForm('1');">
				<?php _e('Message Options', 'webinfos'); ?>
			</div>
			<?php
			if ($_POST['webinfos_nummsg'] >= '0') {
				?>
			<div id="msgbutton3" class="custombuttonused" onClick="toggleForm('3');">
				<?php if ($_POST['webinfos_nummsg']== '0') { 
						_e('New Message Content', 'webinfos');
					} 
					else {
						_e('Edit Message Content', 'webinfos');
					}?>
			</div>
			<?php
			}
			?>
		</div>
		<form class="hide" method="post" id="form2" action="#">
			<?php 
			// we use only usable msg for activation
			$row = $wpdb->get_row("SELECT id FROM {$wpdb->prefix}webinfos WHERE state = 'alive'");
			if (!is_null($row)) { ?>
			<?php settings_fields('webinfos_active'); ?>
			<?php do_settings_sections('webinfos_active'); ?>
			<input class="hide" type="text" name="testactive" value="1" />
			<?php submit_button(__('Activate message', 'webinfos')) ?>
			<?php } ?>
		</form>
		<form class="hide" method="post" id="form1" action="">
			<?php settings_fields('webinfos_action'); ?>
			<?php do_settings_sections('webinfos_action'); ?>
			<?php submit_button(__('Choose action', 'webinfos')) ?>
		</form>
		<form id="form3" method="post" action="#" id="message_content" enctype="multipart/form-data">
			<?php settings_fields('webinfos_settings'); ?>
			<?php do_settings_sections('webinfos_settings'); ?>
		</form>
		</div>
		<!-- feedback section of the plugin -->
		<div class="feedback_container">
		<div class="feedback">
		<h1><?php _e('Feedback', 'webinfos');?></h2>
		<h2><?php _e('Did you find a bug ?', 'webinfos');?> <?php _e('Do you have suggestions?', 'webinfos');?></h2>
		<p><?php _e('There are several ways to report bugs and/or give feedback about the Webinfos plugins : ', 'webinfos')?>
		<ol>
			<li><?php echo sprintf(__('Use a GitHub ticket at this <a href="%s">link</a>.', 'webinfos'), 'https://github.com/ymjid/webinfos/issues/new');?></li>
			<li><?php echo sprintf(__('Use a Wordpress ticket at this <a href="%s">link</a>.', 'webinfos'), 'https://wordpress.org/support/plugin/webinfos');?></li>
			<li><?php _e('Use the form below to contact the plugin author.', 'webinfos');?></li>
		</ol>
		</p>
		<form>
			<?php settings_fields('webinfos_feedback'); ?>
			<?php do_settings_sections('webinfos_feedback'); ?>
			<input class="hide" type="text" name="testsend" value="1" />
			<?php submit_button(__('Send feedback', 'webinfos')) ?>
		</form>
		</div>
		</div>
		</div>
		<link rel="stylesheet" href="<?php echo plugin_dir_url(__FILE__).'css/webinfos.css'; ?>" />
		<!--<script type="text/javascript" src="http://code.jquery.com/jquery-2.2.2.min.js"></script>-->
		<!--<script src="<?php echo plugin_dir_url(__FILE__).'js/webinfos.js'; ?>" type="text/javascript" charset="utf-8"></script>-->
		<script type="text/javascript">
			var imgext = <?php echo json_encode($imgext).';'; ?>
			var imgext2 = <?php echo json_encode($imgext2).';'; ?>
			var vidext = <?php echo json_encode($vidext).';'; ?>
			var plug_dir = <?php echo json_encode($plugin_dir).';';?>
		</script>
		<script type="text/javascript">
			<?php 
			for ($i=0;$i< sizeof($keys); $i++){
			?>
			var <?php echo $keys[$i]; ?> = <?php echo '"'.$keys[$i].'"'; ?>;
			<?php
			}
			?>
		</script>
		<?php
	}
	/*
	** registered settings for msg activation
	*/
	public function register_active_msg () {
		register_setting('webinfos_active', 'webinfos_active_msg');
		register_setting('webinfos_active', 'webinfos_active_msg_all');

		add_settings_section('webinfos_section', __('Choose the message that will be shown in the dashboard', 'webinfos'), array($this, 'section_html'), 'webinfos_active');
		add_settings_field('webinfos_active_msg', __('Activate message :', 'webinfos'), array($this, 'activemsg_html'), 'webinfos_active', 'webinfos_section');
		add_settings_field('webinfos_active_msg_all', __('Activate all messages :', 'webinfos'), array($this, 'activemsgall_html'), 'webinfos_active', 'webinfos_section');
	}
	/*
	** registered settings for msg actions
	*/
	public function register_actions(){
		add_settings_section('webinfos_section0', __('Change welcome message', 'webinfos'), array($this, 'section_html'), 'webinfos_action');
		add_settings_field('webinfos_nummsg', __('Action :', 'webinfos'), array($this, 'nummsg_html'), 'webinfos_action', 'webinfos_section0');
	}
	/*
	** registered settings for msg data
	*/
	public function register_settings() {
		global $error;
		$error= "";
		// if $_POST['webinfos_nummsg'] exist, we test if it's an int
		if (isset($_POST['webinfos_nummsg'])) {
			$test_num_msg=is_numeric($_POST['webinfos_nummsg']);
			if ($test_num_msg != 0) {
				if ($_POST['webinfos_nummsg'] >=0) {
					add_settings_section('webinfos_section1', __('Welcome message settings', 'webinfos'), array($this, 'section_html'), 'webinfos_settings');
					add_settings_section('webinfos_section2', __('Contact informations', 'webinfos'), array($this, 'section_html'), 'webinfos_settings');
				}
				// if we edit a msg we show its id
				if ($_POST['webinfos_nummsg'] != '0') {
					add_settings_field('webinfos_msg', __('Message num:', 'webinfos'), array($this, 'editmsg_html'), 'webinfos_settings', 'webinfos_section1');
				}
				// we show msg datas form only if we want to add or edit a message
				if ($_POST['webinfos_nummsg'] >= '0') {
					add_settings_field('webinfos_title', __('Title :', 'webinfos'), array($this, 'title_html'), 'webinfos_settings', 'webinfos_section1');
					add_settings_field('webinfos_website', __('Website URL :', 'webinfos'), array($this, 'website_html'), 'webinfos_settings', 'webinfos_section1');
					add_settings_field('webinfos_imgurl', __('Upload logo :', 'webinfos'), array($this, 'imgurl_html'), 'webinfos_settings', 'webinfos_section1');
					add_settings_field('webinfos_imgalt', __('Logo alternate text :', 'webinfos'), array($this, 'imgalt_html'), 'webinfos_settings', 'webinfos_section1');
					add_settings_field('webinfos_msgtxt', __('Welcome message :', 'webinfos'), array($this, 'msgtxt_html'), 'webinfos_settings', 'webinfos_section1');
					add_settings_field('webinfos_attachment', __('Attachments :', 'webinfos'), array($this, 'attachment_html'), 'webinfos_settings', 'webinfos_section1');
					add_settings_field('webinfos_video', __('Upload video :', 'webinfos'), array($this, 'video_html'), 'webinfos_settings', 'webinfos_section1');
					add_settings_field('webinfos_contact', __('Show contact infos :', 'webinfos'), array($this, 'contact_html'), 'webinfos_settings', 'webinfos_section1');
					add_settings_field('webinfos_contactimgurl', __('Upload photo :', 'webinfos'), array($this, 'contactimgurl_html'), 'webinfos_settings', 'webinfos_section2');
					add_settings_field('webinfos_contactimgalt', __('Photo alternate text :', 'webinfos'), array($this, 'contactimgalt_html'), 'webinfos_settings', 'webinfos_section2');
					add_settings_field('webinfos_tel', __('Phone :', 'webinfos'), array($this, 'tel_html'), 'webinfos_settings', 'webinfos_section2');
					add_settings_field('webinfos_email', __('Email :', 'webinfos'), array($this, 'email_html'), 'webinfos_settings', 'webinfos_section2');
					add_settings_section('webinfos_section3', '', array($this, 'custom_submit_html'), 'webinfos_settings');
				}	
			}
			else {
				/*error*/
				$error = __('An error occurs with message id', 'webinfos');
				/* error msg*/	
				do_action('admin_notices', $error);
				add_action( 'admin_notices', 'webinfos_admin_notice__error');
				
				function webinfos_admin_notice__error($error) {
					global $error;
				?>
					<div class="notice notice-error">
					<?php
					$errors = explode("|", $error);
					for ($i=0; $i<sizeof($errors); $i++){
						?><p><?php echo $errors[$i]; ?></p>
					<?php
					}
					?>
					</div> 
				<?php
				}	

			}
		}		
	}
	/*
	** registered settings for feedback
	*/
	public function register_feedback () {
		
		add_settings_section('webinfos_feedback0', __('Fill the form below to send your feedback directly to the author', 'webinfos'), array($this, 'section_html'), 'webinfos_feedback');
		add_settings_field('webinfos_feedback_sender', __('Feedback sender :', 'webinfos'), array($this, 'feedback_sender_html'), 'webinfos_feedback', 'webinfos_feedback0');
		add_settings_field('webinfos_feedback_subject', __('Feedback subject :', 'webinfos'), array($this, 'feedback_subject_html'), 'webinfos_feedback', 'webinfos_feedback0');
		add_settings_field('webinfos_feedback_msg', __('Feedback message :', 'webinfos'), array($this, 'feedback_msg_html'), 'webinfos_feedback', 'webinfos_feedback0');
	}
	
	public function section_html() {
		
	}

	function webinfos_action_callback() {
		global $wpdb;
		$plug_dir= esc_url($_POST['plugin_dir']);
		$dirattach= $plug_dir.'attachment/';
		$msg=is_numeric($_POST['editmsg']);
		$data = is_array($_FILES['dragfiles']);
		$upload='false';
			if ($msg!=0 && $data!=false) {
    		$filelist2="";
    		$msgid=$_POST['editmsg'];
    			// upload attachments and created the list to save in database
    			for ($i=0; $i< sizeof($_FILES['dragfiles']['name']); $i++) {
    				$testfiles = validate_file($_FILES['dragfiles']['tmp_name'][$i]);
    				if ($testfiles == 0) {
    					move_uploaded_file ( $_FILES['dragfiles']['tmp_name'][$i] , $dirattach.$_FILES['dragfiles']['name'][$i] );
    					if ($i+1 == sizeof($_FILES['dragfiles']['name'])) {
    						$filelist2=$filelist2.$_FILES['dragfiles']['name'][$i];
    					}
    					else {
    						$filelist2=$filelist2.$_FILES['dragfiles']['name'][$i].',';	
    					}
    				}
    			}
    			if ($filelist2 != "") {
    				// edit attachment files in the database if we edit the msg
    				if ($msgid != null || $msgid > 0) {
    					$wpdb->update("{$wpdb->prefix}webinfos", 
							array('attachment' => $filelist2), array('id' => $msgid)
							);
							$upload='true';
    				}
    				// save attachment files in the database if we add a msg
    				// birth state is there to inform that the msg isn't completely saved (only attachments are saved)
    				if ($msgid == 0) {
    					$wpdb->insert("{$wpdb->prefix}webinfos", 
						array('attachment' => $filelist2, 'state' => 'birth')
						);
						$upload='true';
    				}
    			}
			}
			echo $upload;
		wp_die();
	}
	
	/*
	** html code used by msg activation settings
	*/
	public function activemsg_html() {
		global $wpdb;
		$state='alive';
		$selectsql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}webinfos WHERE state = %s", $state);
		$result = $wpdb->get_results($selectsql);
	?>
			<select id="active_msg" name="active_msg" size="1" <?php disabled(1 == get_option('webinfos_active_msg_all'));?> >
			<option value="0"></option>
			<?php
			if (!is_null($result)) {
				foreach ($result as $msg) {
				?>
					<option <?php if (get_option('webinfos_active_msg')==$msg->id) { echo 'selected'; }?> value="<?php echo esc_html($msg->id); ?>"><?php echo esc_html($msg->id); ?></option>
				<?php
				}
			}
			?>
			</select>
		<?php
	}
	
	public function activemsgall_html () {
		?>
		<input type="checkbox"  onclick="toggleOption();" id="active_msg_all" name="active_msg_all" <?php checked(1 == get_option('webinfos_active_msg_all'));?>/>
		<?php
	}
	/*
	** html code used by msg data settings
	*/
	public function nummsg_html(){
		global $wpdb;
		$state='alive';
		$selectsql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}webinfos WHERE state = %s", $state);
		$result = $wpdb->get_results($selectsql);
	?>
			<select name="webinfos_nummsg" size="1">
			<option value="0"><?php _e('Add message', 'webinfos');?></option>
			<?php
			if (!is_null($result)) {
			foreach ($result as $msg) {
			?>	
			 	<optgroup label="message <?php echo esc_html($msg->id); ?>">
				<option value="<?php echo esc_html($msg->id); ?>"><?php echo _e('Edit message ', 'webinfos').esc_html($msg->id); ?></option>
				<option value="<?php echo '-'.esc_html($msg->id); ?>"><?php echo _e('Erase message ', 'webinfos').esc_html($msg->id); ?></option>
				</optgroup>
			<?php
			}
			}
			?>
			</select>
	<?php	
		
	}
	
	public function editmsg_html(){
		global $wpdb;
		$test_num_msg = intval($_POST['webinfos_nummsg']);
		if ($test_num_msg == 0) {
			$data='';
		}
		else {
			$id=$_POST['webinfos_nummsg'];
			$selectsql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}webinfos WHERE id = %d", $id);
			$row = $wpdb->get_row($selectsql);
			$data=$row->id;
		}
	?>
	<p><?php echo esc_html($data);?></p>
	<input hidden type="text" id="webinfos_msg" name="webinfos_msg" value="<?php echo esc_html($data); ?>" />
	<?php
	}
	
	public function title_html(){
		global $wpdb;
		$test_num_msg = intval($_POST['webinfos_nummsg']);
		if ($test_num_msg == 0) {
			$data='';
		}
		else {
			$id=$_POST['webinfos_nummsg'];
			$selectsql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}webinfos WHERE id = %d", $id);
			$row = $wpdb->get_row($selectsql);
			$data=$row->title;
		}
	?>
	<input type="text" name="webinfos_title" value="<?php echo esc_html($data); ?>" />
	<?php	
	}
	
	public function imgurl_html(){
		global $wpdb;
		$test_num_msg = intval($_POST['webinfos_nummsg']);
		if ($test_num_msg == 0) {
		}
		else {
			$id=$_POST['webinfos_nummsg'];
			$selectsql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}webinfos WHERE id = %d", $id);
			$row = $wpdb->get_row($selectsql);
			$data=$row->imgurl;
			$dataalt=$row->imgalt;
			// if the logo exist we resize it
			if ($data !="") {
				$upload_loc=wp_upload_dir();
				$file_loc=$upload_loc['baseurl'].'/webinfos/img/'.$data;
				$img=getimagesize ($file_loc);
				$dwimg=$img[0];
				$dhimg=$img[1];
				if ($img[0]>'400') {
					$wp = ($dwimg/100);
					while ($img[0]>'400') {
						$img[0]= $img[0]-$wp;
					}
					$img[1]= ($dhimg/$dwimg)*$img[0];
				}
				if ($img[1]>'400') {
					$hp = ($dhimg/100);
					while ($img[1]>'400') {
						$img[1]= $img[1]-$hp;
					}
					$img[0]= $img[1]/($dhimg/$dwimg);
				}
				?>
				<p>
					<img src="<?php echo esc_url($file_loc); ?>" alt="<?php echo esc_html($dataalt); ?>" height="<?php echo esc_html($img[1]).'px'; ?>" width="<?php echo esc_html($img[0]).'px'; ?>"/>
				</p>
				<?php
			}
		}
	?>
	<input type="file" id="webinfos_imgurl" name="webinfos_imgurl" onchange="filechoosen('webinfos_imgurl', imgext, imgerrormsg);"/>
	<p class="warning_msg"><?php echo sprintf(__("Uploaded file must be in %s or %s format.", 'webinfos'), esc_html($GLOBALS['imgext'][0]), esc_html($GLOBALS['imgext'][1])); ?></p>
	<?php	
	}
	
	public function imgalt_html(){
		global $wpdb;
		$test_num_msg = intval($_POST['webinfos_nummsg']);
		if ($test_num_msg == 0) {
			$data='';
		}
		else {
			$id=$_POST['webinfos_nummsg'];
			$selectsql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}webinfos WHERE id = %d", $id);
			$row = $wpdb->get_row($selectsql);
			$data=$row->imgalt;
		}
	?>
	<input type="text" name="webinfos_imgalt" value="<?php echo esc_html($data); ?>" />
	<?php	
	}
	
	public function msgtxt_html(){
		global $wpdb;
		$test_num_msg = intval($_POST['webinfos_nummsg']);
		if ($test_num_msg == 0) {
			$data='';
		}
		else {
			$id=$_POST['webinfos_nummsg'];
			$selectsql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}webinfos WHERE id = %d", $id);
			$row = $wpdb->get_row($selectsql);
			$data=$row->msgtxt;
		}
	?>
	<textarea id="msgtxt" name="webinfos_msgtxt" maxlength="500" cols="50" rows="7" onkeyup="countchar('msgtxt', 'txtcounter');"><?php echo esc_textarea($data); ?></textarea>
	<p id="txtcounter"><?php 
	$nbchar= 500 - strlen($data);
	$txtcounter= sprintf(_n( '%d character left.', '%d characters left.', $nbchar, 'webinfos' ), $nbchar);
	?>
	<?php echo $txtcounter; ?>
	</p>
	<p class="info_msg"><?php _e('HTML tags are supported.', 'webinfos'); ?></p>
	<?php	
	}
	
	public function contact_html(){
		global $wpdb;
		$test_num_msg = intval($_POST['webinfos_nummsg']);
		if ($test_num_msg == 0) {
			$data='';
		}
		else {
			$id=$_POST['webinfos_nummsg'];
			$selectsql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}webinfos WHERE id = %d", $id);
			$row = $wpdb->get_row($selectsql);
			$data=$row->contact;
		}
	?>
	<input type="checkbox" name="webinfos_contact" <?php checked(1 == esc_html($data));?>/>
	<?php	
	}
	
	public function contactimgurl_html(){
		global $wpdb;
		$test_num_msg = intval($_POST['webinfos_nummsg']);
		if ($test_num_msg == 0) {
			$data='';
		}
		else {
			$id=$_POST['webinfos_nummsg'];
			$selectsql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}webinfos WHERE id = %d", $id);
			$row = $wpdb->get_row($selectsql);
			$data=$row->contactimgurl;
			$dataalt=$row->contactimgalt;
			// if the photo exist we resize it
			if ($data != "") {
				$upload_loc=wp_upload_dir();
				$file_loc=$upload_loc['baseurl'].'/webinfos/img/'.$data;
				$img=getimagesize (plugin_dir_url(__FILE__).'img/'.$data);
				$dwimg=$img[0];
				$dhimg=$img[1];
				if ($img[0]>'100') {
					$wp = ($dwimg/100);
					while ($img[0]>'100') {
						$img[0]= $img[0]-$wp;
					}
					$img[1]= ($dhimg/$dwimg)*$img[0];
				}
				if ($img[1]>'100') {
					$hp = ($dhimg/100);
					while ($img[1]>'100') {
						$img[1]= $img[1]-$hp;
					}
					$img[0]= $img[1]/($dhimg/$dwimg);
				}
				?>
				<p>
					<img src="<?php echo esc_url($file_loc); ?>" alt="<?php echo esc_html($dataalt); ?>" height="<?php echo esc_html($img[1]).'px'; ?>" width="<?php echo esc_html($img[0]).'px'; ?>"/>
				</p>
				<?php
			}
		}
	?>
	<input type="file" id="webinfos_contactimgurl" name="webinfos_contactimgurl" onchange="filechoosen('webinfos_contactimgurl', imgext2, imgerrormsg2);"/>
	<p class="warning_msg"><?php echo sprintf(__("Uploaded file must be in %s or %s format.", 'webinfos'), esc_html($GLOBALS['imgext2'][0]), esc_html($GLOBALS['imgext2'][1])); ?></p>
	<?php	
	}
	
	public function contactimgalt_html(){
		global $wpdb;
		$test_num_msg = intval($_POST['webinfos_nummsg']);
		if ($test_num_msg == 0) {
			$data='';
		}
		else {
			$id=$_POST['webinfos_nummsg'];
			$selectsql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}webinfos WHERE id = %d", $id);
			$row = $wpdb->get_row($selectsql);
			$data=$row->contactimgalt;
		}
	?>
	<input type="text" name="webinfos_contactimgalt" value="<?php echo esc_html($data); ?>" />
	<?php	
	}

	public function tel_html(){
		global $wpdb;
		$test_num_msg = intval($_POST['webinfos_nummsg']);
		if ($test_num_msg == 0) {
			$data='';
		}
		else {
			$id=$_POST['webinfos_nummsg'];
			$selectsql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}webinfos WHERE id = %d", $id);
			$row = $wpdb->get_row($selectsql);
			$data=$row->tel;
		}
	?>
	<input type="text" name="webinfos_tel" value="<?php echo esc_html($data); ?>" maxlength="15" pattern="\d*"/>
	<?php	
	}
	
	public function email_html(){
		global $wpdb;
		$test_num_msg = intval($_POST['webinfos_nummsg']);
		if ($test_num_msg == 0) {
			$data='';
		}
		else {
			$id=$_POST['webinfos_nummsg'];
			$selectsql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}webinfos WHERE id = %d", $id);
			$row = $wpdb->get_row($selectsql);
			$data=$row->email;
		}
	?>
	<input type="email" name="webinfos_email" value="<?php echo esc_html($data); ?>" maxlength="50" />
	<?php	
	}
	
	public function video_html(){
		global $wpdb;
		$test_num_msg = intval($_POST['webinfos_nummsg']);
		if ($test_num_msg == 0) {
			$data='';
		}
		else {
			$id=$_POST['webinfos_nummsg'];
			$selectsql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}webinfos WHERE id = %d", $id);
			$row = $wpdb->get_row($selectsql);
			$data=$row->video;
			if ($data != null && $data != "") {
				$upload_loc=wp_upload_dir();
				$file_loc=$upload_loc['baseurl'].'/webinfos/video/'.$data;
			?>
			<p>
				<video width="480" controls>
  					<source src="<?php echo esc_url($file_loc); ?>" type="video/mp4">
  					<?php _e('Your browser does not support HTML5 video.', 'webinfos'); ?>
				</video>
			</p>
			<?php
			}
		}
		?>
	<input type="file" id="webinfos_video" name="webinfos_video" onchange="filechoosen('webinfos_video', vidext, viderrormsg);"/>
	<p class="warning_msg"><?php echo sprintf(__("Uploaded file must be in %s format.", 'webinfos'), esc_html($GLOBALS['vidext'][0])); ?></p>
	<?php	
	}
	
	public function website_html(){
		global $wpdb;
		$test_num_msg = intval($_POST['webinfos_nummsg']);
		if ($test_num_msg == 0) {
			$data='';
		}
		else {
			$id=$_POST['webinfos_nummsg'];
			$selectsql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}webinfos WHERE id = %d", $id);
			$row = $wpdb->get_row($selectsql);
			$data=$row->website;
		}
	?>
	<input type="text"  name="webinfos_website" value="<?php echo esc_html($data); ?>" pattern="https?://.+" />
	<p class="info_msg"><?php _e('The website URL will be applied on the logo.', 'webinfos'); ?></p>
	<?php	
	}
	
	public function attachment_html(){
		global $wpdb;
		$test_num_msg = intval($_POST['webinfos_nummsg']);
		if ($test_num_msg == 0) {
			$data='';
		}
		else {
			$id=$_POST['webinfos_nummsg'];
			$selectsql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}webinfos WHERE id = %d", $id);
			$row = $wpdb->get_row($selectsql);
			$data=$row->attachment;
			if ($data != "") {
				$upload_loc=wp_upload_dir();
				$file_loc=$upload_loc['baseurl'].'/webinfos/attachment/';
			?>
				<?php _e('Attachments list : ', 'webinfos'); ?>
				<div> 
				<?php
				$filelist=explode(',' , $data);
				$nb=0;
				for ($i=0; $i<sizeof($filelist); $i++) {
					$nb++;
					if ($nb==1) {
					?>
						<p>
					<?php
					}
					?>
					<span>
						<a href="<?php echo esc_url($file_loc.$filelist[$i]); ?>"><?php echo esc_html($filelist[$i]); ?></a>
					<?php 
					if ($i+1 != sizeof($filelist))  {
						echo ' | ';	
					}
					?>
					</span>
					<?php
					if ($nb==3) {
					?>
						</p>
					<?php	
						$nb=0;
					}
			}
		?>
				</div>
		<?php
		}

	}
	?>
	<div class="droppable"><?php _e('Click or drop your files here', 'webinfos'); ?></div>
	<input class="hide" type="file" id="webinfos_attachment" multiple name="webinfos_attachment[]"/>
	<div class="output">
	</div>
	<input style="display:none;" type="text" name="dropused" id="dropused" value="0" />
	<p class="info_msg"><?php _e('You can upload several files as attachment.', 'webinfos'); ?></p>
	<?php	
	}
	
	public function custom_submit_html() { ?>
		<p class="submit">
			<input class="hide" type="text" name="test" value="1" />
			<input id="custom_submit" class="hide" type="submit" value="submit" name="submit" />
			<input class="button button-primary" type="button" id="dummy_submit" name="dummy_submit" value="<?php _e('Save changes', 'webinfos'); ?>" />
		</p>
		<?php
	}
	
	/*
	** send an email to the author
	*/
	public function send_feedback() {
		if (isset($_POST['testsend'])) {
			$safe_testsend = intval( $_POST['testsend'] );
			if ( !$safe_testsend ) {
  				$error= __('An error occurs with feedback form', 'webinfos');
  				add_action( 'admin_notices', 'webinfos_admin_notice__error');
				
				function webinfos_admin_notice__error($error) {
					global $error;
				?>
					<div class="notice notice-error">
					<?php
					$errors = explode("|", $error);
					for ($i=0; $i<sizeof($errors); $i++){
						?><p><?php echo $errors[$i]; ?></p>
					<?php
					}
					?>
					</div> 
				<?php
				}
			}
			else {
				$current_user=wp_get_current_user();
				$target_email="M.JID.Yannis@gmail.com";
				$object = $_POST['webinfos_feedback_subject'];
				$content = $_POST['webinfos_feedback_msg'];
				$sender = $current_user->user_email;
				$header = array('From: '.$sender);
				wp_mail($target_email, $object, $content, $header);
			}
		}
	}
	
	/*
	** html code used by feedback settings
	*/
	public function feedback_sender_html () {
			$current_user=wp_get_current_user();
			
			echo $current_user->user_email;	
	}
	
	public function feedback_subject_html () {
	?>
	<input type="text" name="webinfos_feedback_subject" size="52" value=""/>	
	<?php
	}
	
	public function feedback_msg_html () {
	?>
	<textarea name="webinfos_feedback_msg" cols="50" rows="7"></textarea>
	<?php
	}

}

new Webinfos_Plugin();
