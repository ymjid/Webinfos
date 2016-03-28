<?php
/*
Plugin Name: Webinfos Plugin
Plugin URI:
Description: A plugin showing a customisable welcome message in the dashboard.
Version: 1.0
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
    	register_activation_hook(__FILE__, array('Webinfos_Plugin', 'install'));
   		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('plugins_loaded', array($this, 'webinfos_textdomain'));
		add_action('wp_loaded', array($this, 'save_data'));
		add_action('wp_loaded', array($this, 'send_feedback'));
		add_action('admin_init', array($this, 'register_feedback'));
		add_action('admin_init', array($this, 'register_active_msg'));
		add_action('admin_init', array($this, 'register_actions'));
		add_action('admin_init', array($this, 'register_settings'));
		include(plugin_dir_path(__FILE__).'/custom_welcome.php');
		new Webinfos_Welcome();
		register_uninstall_hook(__FILE__, array('Webinfos_Plugin', 'uninstall'));
    }

    public function install()
    {
    	global $wpdb;
    	
    	$wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}webinfos (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, website VARCHAR(255) NOT NULL,imgurl VARCHAR(255) NOT NULL, imgalt VARCHAR(255) NOT NULL, msgtxt VARCHAR(500) NOT NULL, contact BOOL NOT NULL, contactimgurl VARCHAR(255) NOT NULL, contactimgalt VARCHAR(255) NOT NULL, tel VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, video VARCHAR(255) NOT NULL, attachment VARCHAR(5000) NOT NULL, state ENUM('alive', 'dead') NOT NULL);");
    }
	
    public function uninstall()
    {
    	global $wpdb;
		
		$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}webinfos;");

    }
    
    public function webinfos_textdomain() {
    	$plugin_dir = basename(dirname(__FILE__)).'/languages';
    	load_plugin_textdomain('webinfos', false, $plugin_dir);	
    }

    public function save_data() {
    	global $wpdb;
    	if (isset($_POST['test'])) {
    		if (isset($_POST['webinfos_contact'])) {
    			$contact="1";
    		}
    		else {
    			$contact="0";
    		}
    		$testimg1='false';
    		$testimg2='false';
    		$testvideo='false';
    		$testattach='false';
    		$testdrop='false';
    		$filelist="";
    		if (isset($_POST['dropused']) && $_POST['dropused']=="1") {
    			$testdrop='true';	
    		}
    		if ($testdrop=='false') {
    			if(isset($_FILES['webinfos_attachment']) && is_array($_FILES['webinfos_attachment']['name']) && $_FILES['webinfos_attachment']['name'][0] != ""){
    				$dirattach=plugin_dir_path(__FILE__).'attachment/';
    				for ($i=0; $i< sizeof($_FILES['webinfos_attachment']['name']); $i++) {
    					move_uploaded_file ( $_FILES['webinfos_attachment']['tmp_name'][$i] , $dirattach.$_FILES['webinfos_attachment']['name'][$i] );
    					if ($i+1 == sizeof($_FILES['webinfos_attachment']['name'])) {
    						$filelist=$filelist.$_FILES['webinfos_attachment']['name'][$i];
    					}
    					else {
    						$filelist=$filelist.$_FILES['webinfos_attachment']['name'][$i].',';	
    					}
    				}
    				$testattach='true';
    			}
    		}
    		if(isset($_FILES['webinfos_imgurl']) && $_FILES['webinfos_imgurl']['name']!=""){
    			$dirimg=plugin_dir_path(__FILE__).'img/';
				move_uploaded_file ( $_FILES['webinfos_imgurl']['tmp_name'] , $dirimg.$_FILES['webinfos_imgurl']['name'] );
				$testimg1='true';
    		}
    		if(isset($_FILES['webinfos_contactimgurl']) && $_FILES['webinfos_contactimgurl']['name']!=""){
    			$dirimg=plugin_dir_path(__FILE__).'img/';
				move_uploaded_file ( $_FILES['webinfos_contactimgurl']['tmp_name'] , $dirimg.$_FILES['webinfos_contactimgurl']['name'] );
				$testimg2='true';
    		}
    		if(isset($_FILES['webinfos_video']) && $_FILES['webinfos_video']['name']!=""){
    			$dirvid=plugin_dir_path(__FILE__).'video/';
				move_uploaded_file ( $_FILES['webinfos_video']['tmp_name'] , $dirvid.$_FILES['webinfos_video']['name'] );
				$testvideo='true';
    		}
    		$imgurl=$_FILES['webinfos_imgurl']['name'];
			$contactimgurl=$_FILES['webinfos_contactimgurl']['name'];
			$video=$_FILES['webinfos_video']['name'];
			$attach=$filelist;
			if (!isset($_POST['webinfos_msg'])) {
				if ($testdrop=='false') {
					$wpdb->insert("{$wpdb->prefix}webinfos", 
					array('title' => $_POST['webinfos_title'], 'website' => $_POST['webinfos_website'], 'imgurl' => $_FILES['webinfos_imgurl']['name'], 'imgalt' => $_POST['webinfos_imgalt'], 'msgtxt' => $_POST['webinfos_msgtxt'], 'contact' => $contact, 'contactimgurl' => $_FILES['webinfos_contactimgurl']['name'], 'contactimgalt' => $_POST['webinfos_contactimgalt'], 'tel' => $_POST['webinfos_tel'], 'email' => $_POST['webinfos_email'], 'video' => $_FILES['webinfos_video']['name'], 'attachment' => $filelist, 'state' => 'alive')
					);
				}
				else {
					$row = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}webinfos WHERE state = 'birth'");
					$attach=$row->attachment;
						$wpdb->update("{$wpdb->prefix}webinfos", 
						array('title' => $_POST['webinfos_title'], 'website' => $_POST['webinfos_website'], 'imgurl' => $imgurl, 'imgalt' => $_POST['webinfos_imgalt'], 'msgtxt' => $_POST['webinfos_msgtxt'], 'contact' => $contact, 'contactimgurl' => $contactimgurl, 'contactimgalt' => $_POST['webinfos_contactimgalt'], 'tel' => $_POST['webinfos_tel'], 'email' => $_POST['webinfos_email'], 'video' => $video, 'attachment' => $attach, 'state' => 'alive'), array('state' => 'birth')
						);
				}
			}
			if (isset($_POST['webinfos_msg']) &&  $_POST['webinfos_msg'] != '0') {
				$num=$_POST['webinfos_msg'];
				$row = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}webinfos WHERE id = '$num'");
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
						array('title' => $_POST['webinfos_title'], 'website' => $_POST['webinfos_website'], 'imgurl' => $imgurl, 'imgalt' => $_POST['webinfos_imgalt'], 'msgtxt' => $_POST['webinfos_msgtxt'], 'contact' => $contact, 'contactimgurl' => $contactimgurl, 'contactimgalt' => $_POST['webinfos_contactimgalt'], 'tel' => $_POST['webinfos_tel'], 'email' => $_POST['webinfos_email'], 'video' => $video, 'attachment' => $attach), array('id' => $num)
						);
				}
			}
    	}
    	else {
    		$erase_msg='false';
    		if (isset($_POST['webinfos_nummsg']) ) {
    			$num=abs($_POST['webinfos_nummsg']);
    		}
    		if (isset($num)  && $_POST['webinfos_nummsg'] < 0) {
				$row = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}webinfos WHERE id = '$num'");
				if (!is_null($row)) {
					$wpdb->update("{$wpdb->prefix}webinfos", 
					array('state' => 'dead'), array('id' => $num)
					);
					$erase_msg='true';
				}
			}
			if (isset($num) && $num == get_option('active_msg') && $erase_msg=='true') {
				$row = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}webinfos WHERE state = 'alive'");
				if (!is_null($row)) {
					update_option( 'active_msg', $row->id );
				}
				else {
					update_option( 'active_msg', '0');
				}
			}	
    	}
	}
	
	public function add_admin_menu() {
		add_menu_page(__('Webinfos plugin', 'webinfos'), __('Webinfos Settings', 'webinfos'), 'manage_options', 'webinfos', array($this, 'menu_html'));

	}
	
	public function menu_html(){
		global $wpdb;
		
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
			$vidext= array('mp4');
			$vidformat="";
			$vidformat=$vidformat.$vidext[0];	
		$GLOBALS['imgext']=$imgext;
		$GLOBALS['imgext2']=$imgext2;
		$GLOBALS['vidext']=$vidext;
		// Register the script first.
		wp_register_script( 'webinfos_handle', plugin_dir_url(__FILE__).'/js/webinfos.js' );
		// Now we can localize the script with our data.
		$translation_array = array( 'imgerrormsg' => sprintf(__('The uploaded file must be in %s format', 'webinfos'), $imgformat ), 'imgerrormsg2' => sprintf(__('The uploaded file must be in %s format', 'webinfos'), $imgformat2 ), 'viderrormsg' => sprintf(__('The uploaded file must be in %s format', 'webinfos'), $vidformat ), 'charmsg' => sprintf(__('character left.', 'webinfos')), 'charsmsg' => sprintf(__('characters left.', 'webinfos')), 'newlist' => sprintf(__('New attachments list : ', 'webinfos')));
		wp_localize_script( 'webinfos_handle', 'object_name', $translation_array );
		// The script can be enqueued now or later.
		wp_enqueue_script( 'webinfos_handle' );
		$keys=array_keys($translation_array);
		$plugin_infos=get_plugin_data(plugin_dir_path(__FILE__).'webinfos.php'); 
		echo '<div class="webinfos_options_container">';
		echo '<h1>'.get_admin_page_title().'</h1>';
				?>
		<h2><?php echo sprintf(__('Version %s', 'webinfos'), $plugin_infos['Version']); ?></h2>
		<p><?php _e('Welcome to the setting panel of Webinfos plugin', 'webinfos'); ?></p>
		<form method="post" action="options.php">
			<?php 
			$row = $wpdb->get_row("SELECT id FROM {$wpdb->prefix}webinfos WHERE state = 'alive'");
			if (!is_null($row)) { ?>
			<?php settings_fields('webinfos_active'); ?>
			<?php do_settings_sections('webinfos_active'); ?>
			<?php submit_button(__('Activate message', 'webinfos')) ?>
			<?php } ?>
		</form>
		<form method="post" action="">
			<?php settings_fields('webinfos_action'); ?>
			<?php do_settings_sections('webinfos_action'); ?>
			<?php submit_button(__('Choose action', 'webinfos')) ?>
		</form>
		<?php
		if (isset($_POST['webinfos_nummsg']) && $_POST['webinfos_nummsg'] >= 0) {			
		?>
		<form method="post" action="#" id="message_content" enctype="multipart/form-data">
			<?php settings_fields('webinfos_settings'); ?>
			<?php do_settings_sections('webinfos_settings'); ?>
			<input class="hide" type="text" name="test" value="1" />
			<input id="custom_submit" class="hide" type="submit" value="submit" name="submit" />
			<input class="button button-primary" type="button" name="dummy_submit" value="<?php _e('Save changes', 'webinfos'); ?>" onclick="ajaxupload();" />
		</form>
		<script type="text/javascript" src="http://code.jquery.com/jquery-2.2.2.min.js"></script>
		<script src="<?php echo plugin_dir_url(__FILE__).'js/webinfos.js'; ?>" type="text/javascript" charset="utf-8"></script>
		<script type="text/javascript">
			var imgext = <?php echo json_encode($imgext).';'; ?>
			var imgext2 = <?php echo json_encode($imgext2).';'; ?>
			var vidext = <?php echo json_encode($vidext).';'; ?>
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
		?>
		</div>
		<div class="feedback_container">
		<div class="feedback">
		<h1><?php _e('Feedback', 'webinfos');?></h2>
		<h2><?php _e('Did you find a bug ?', 'webinfos');?> <?php _e('Do you have suggestions?', 'webinfos');?></h2>
		<p><?php _e('There are several ways to report bugs and/or give feedback about the Webinfos plugins : ', 'webinfos')?>
		<ol>
			<li><?php echo sprintf(__('Use a GitHub ticket at this <a href="%s">link</a>.', 'webinfos'), '#');?></li>
			<li><?php echo sprintf(__('Use a Wordpress ticket at this <a href="%s">link</a>.', 'webinfos'), '#');?></li>
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
		<link rel="stylesheet" href="<?php echo plugin_dir_url(__FILE__).'css/webinfos.css'; ?>" />
		<?php
	}
	
	public function register_active_msg () {
		register_setting('webinfos_active', 'active_msg');
		
		add_settings_section('webinfos_section', __('Choose the message that will be shown in the dashboard', 'webinfos'), array($this, 'section_html'), 'webinfos_active');
		add_settings_field('active_msg', __('Activate message :', 'webinfos'), array($this, 'activemsg_html'), 'webinfos_active', 'webinfos_section');
	}
	
	public function register_actions(){
		add_settings_section('webinfos_section0', __('Change welcome message', 'webinfos'), array($this, 'section_html'), 'webinfos_action');
		add_settings_field('webinfos_nummsg', __('Action :', 'webinfos'), array($this, 'nummsg_html'), 'webinfos_action', 'webinfos_section0');
	}
	
	public function register_settings() {
		
		add_settings_section('webinfos_section1', __('Welcome message settings', 'webinfos'), array($this, 'section_html'), 'webinfos_settings');
		add_settings_section('webinfos_section2', __('Contact informations', 'webinfos'), array($this, 'section_html'), 'webinfos_settings');
		if (isset($_POST['webinfos_nummsg']) && $_POST['webinfos_nummsg'] != '0') {
		add_settings_field('webinfos_msg', __('Message num:', 'webinfos'), array($this, 'editmsg_html'), 'webinfos_settings', 'webinfos_section1');
		}
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
	}
	
	public function register_feedback () {
		
		add_settings_section('webinfos_feedback0', __('Fill the form below to send your feedback directly to the author', 'webinfos'), array($this, 'section_html'), 'webinfos_feedback');
		add_settings_field('webinfos_feedback_sender', __('Feedback sender :', 'webinfos'), array($this, 'feedback_sender_html'), 'webinfos_feedback', 'webinfos_feedback0');
		add_settings_field('webinfos_feedback_subject', __('Feedback subject :', 'webinfos'), array($this, 'feedback_subject_html'), 'webinfos_feedback', 'webinfos_feedback0');
		add_settings_field('webinfos_feedback_msg', __('Feedback message :', 'webinfos'), array($this, 'feedback_msg_html'), 'webinfos_feedback', 'webinfos_feedback0');
	}
	
	public function section_html() {
		
	}
	
	public function activemsg_html() {
		global $wpdb;
		$result = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}webinfos WHERE state = 'alive'");
	?>
			<select name="active_msg" size="1">
			<?php
			if (!is_null($result)) {
				foreach ($result as $msg) {
				?>
					<option <?php if (get_option('active_msg')==$msg->id) { echo 'selected'; }?> value="<?php echo $msg->id; ?>"><?php echo $msg->id; ?></option>
				<?php
				}
			}
			?>
			</select>
		<?php
	}
	
	public function nummsg_html(){
		global $wpdb;
		$result = $wpdb->get_results("SELECT id FROM {$wpdb->prefix}webinfos WHERE state = 'alive'");
	?>
			<select name="webinfos_nummsg" size="1">
			<option value="0"><?php _e('Add message', 'webinfos');?></option>
			<?php
			if (!is_null($result)) {
			foreach ($result as $msg) {
			?>	
			 	<optgroup label="message <?php echo $msg->id; ?>">
				<option value="<?php echo $msg->id; ?>"><?php echo _e('Edit message ', 'webinfos').$msg->id; ?></option>
				<option value="<?php echo '-'.$msg->id; ?>"><?php echo _e('Erase message ', 'webinfos').$msg->id; ?></option>
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
		$id=$_POST['webinfos_nummsg'];
		$row = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}webinfos WHERE id ='$id'");
	?>
	<p><?php echo $row->id;?></p>
	<input hidden type="text" id="webinfos_msg" name="webinfos_msg" value="<?php echo $row->id; ?>" />
	<?php
	}
	
	public function title_html(){
		global $wpdb;
		$id=$_POST['webinfos_nummsg'];
		$row = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}webinfos WHERE id ='$id'");
	?>
	<input type="text" name="webinfos_title" value="<?php echo $row->title; ?>" />
	<?php	
	}
	
	public function imgurl_html(){
		global $wpdb;
		$id=$_POST['webinfos_nummsg'];
		$row = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}webinfos WHERE id ='$id'");
		if ($row->imgurl !="") {
			$img=getimagesize (plugin_dir_url(__FILE__).'img/'.$row->imgurl);
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
		}
	?>
	<p>
	<img src="<?php echo plugin_dir_url(__FILE__).'img/'.$row->imgurl; ?>" alt="<?php echo $row->imgalt; ?>" height="<?php echo $img[1].'px'; ?>" width="<?php echo $img[0].'px'; ?>"/>
	</p>
	<input type="file" id="webinfos_imgurl" name="webinfos_imgurl" onchange="filechoosen('webinfos_imgurl', imgext, imgerrormsg);"/>
	<p class="warning_msg"><?php echo sprintf(__("Uploaded file must be in %s or %s format.", 'webinfos'), $GLOBALS['imgext'][0], $GLOBALS['imgext'][1]); ?></p>
	<?php	
	}
	
	public function imgalt_html(){
		global $wpdb;
		$id=$_POST['webinfos_nummsg'];
		$row = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}webinfos WHERE id ='$id'");
	?>
	<input type="text" name="webinfos_imgalt" value="<?php echo $row->imgalt; ?>" />
	<?php	
	}
	
	public function msgtxt_html(){
		global $wpdb;
		$id=$_POST['webinfos_nummsg'];
		$row = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}webinfos WHERE id ='$id'");
	?>
	<textarea id="msgtxt" name="webinfos_msgtxt" maxlength="500" cols="50" rows="7" onkeyup="countchar('msgtxt', 'txtcounter');"><?php echo $row->msgtxt; ?></textarea>
	<p id="txtcounter"><?php 
	$nbchar= 500 - strlen($row->msgtxt);
	$txtcounter= sprintf(_n( '%d character left.', '%d characters left.', $nbchar, 'webinfos' ), $nbchar);
	?>
	<?php echo $txtcounter; ?>
	</p>
	<p class="info_msg"><?php _e('HTML tags are supported.', 'webinfos'); ?></p>
	<?php	
	}
	
	public function contact_html(){
		global $wpdb;
		$id=$_POST['webinfos_nummsg'];
		$row = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}webinfos WHERE id ='$id'");
	?>
	<input type="checkbox" name="webinfos_contact" <?php checked(1 == $row->contact);?>/>
	<?php	
	}
	
	public function contactimgurl_html(){
		global $wpdb;
		$id=$_POST['webinfos_nummsg'];
		$row = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}webinfos WHERE id ='$id'");
		if ($row->contactimgurl != "") {
			$img=getimagesize (plugin_dir_url(__FILE__).'img/'.$row->contactimgurl);
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
		}
	?>
	<p>
	<img src="<?php echo plugin_dir_url(__FILE__).'img/'.$row->contactimgurl; ?>" alt="<?php echo $row->contactimgalt; ?>" height="<?php echo $img[1].'px'; ?>" width="<?php echo $img[0].'px'; ?>"/>
	</p>
	<input type="file" id="webinfos_contactimgurl" name="webinfos_contactimgurl" onchange="filechoosen('webinfos_contactimgurl', imgext2, imgerrormsg2);"/>
	<p class="warning_msg"><?php echo sprintf(__("Uploaded file must be in %s or %s format.", 'webinfos'), $GLOBALS['imgext2'][0], $GLOBALS['imgext2'][1]); ?></p>
	<?php	
	}
	
	public function contactimgalt_html(){
		global $wpdb;
		$id=$_POST['webinfos_nummsg'];
		$row = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}webinfos WHERE id ='$id'");
	?>
	<input type="text" name="webinfos_contactimgalt" value="<?php echo $row->contactimgalt; ?>" />
	<?php	
	}

	public function tel_html(){
		global $wpdb;
		$id=$_POST['webinfos_nummsg'];
		$row = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}webinfos WHERE id ='$id'");
	?>
	<input type="text" name="webinfos_tel" value="<?php echo $row->tel; ?>" maxlength="15" pattern="\d*"/>
	<?php	
	}
	
	public function email_html(){
		global $wpdb;
		$id=$_POST['webinfos_nummsg'];
		$row = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}webinfos WHERE id ='$id'");
	?>
	<input type="email" name="webinfos_email" value="<?php echo $row->email; ?>" maxlength="50" />
	<?php	
	}
	
	public function video_html(){
		global $wpdb;
		$id=$_POST['webinfos_nummsg'];
		$row = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}webinfos WHERE id ='$id'");
	?>
	<p>
	<?php
	if ($row->video != null && $row->video != "") {
		?>
	<video width="480" controls>
  		<source src="<?php echo plugin_dir_url(__FILE__).'video/'.$row->video; ?>" type="video/mp4">
  		<?php _e('Your browser does not support HTML5 video.', 'webinfos'); ?>
	</video>
	<?php
	}
	?>
	</p>
	<input type="file" id="webinfos_video" name="webinfos_video" onchange="filechoosen('webinfos_video', vidext, viderrormsg);"/>
	<p class="warning_msg"><?php echo sprintf(__("Uploaded file must be in %s format.", 'webinfos'), $GLOBALS['vidext'][0]); ?></p>
	<?php	
	}
	
	public function website_html(){
		global $wpdb;
		$id=$_POST['webinfos_nummsg'];
		$row = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}webinfos WHERE id ='$id'");
	?>
	<input type="text"  name="webinfos_website" value="<?php echo $row->website; ?>" pattern="https?://.+" />
	<p class="info_msg"><?php _e('The website URL will be applied on the logo.', 'webinfos'); ?></p>
	<?php	
	}
	
	public function attachment_html(){
		global $wpdb;
		$id=$_POST['webinfos_nummsg'];
		$row = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}webinfos WHERE id ='$id'");
	?>
	<?php
	if ($row->attachment != "") {
	?>
	<?php _e('Attachments list : ', 'webinfos'); ?>
	<div> 
	<?php
	$filelist=explode(',' , $row->attachment);
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
		<a href="<?php echo plugin_dir_url(__FILE__).'attachment/'.$filelist[$i]; ?>"><?php echo $filelist[$i] ?></a>
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
	?>
	<div class="droppable"><?php _e('Click or drop your files here', 'webinfos'); ?></div>
	<input class="hide" type="file" id="webinfos_attachment" multiple name="webinfos_attachment[]"/>
	<div class="output">
	</div>
	<input style="display:none;" type="text" name="dropused" id="dropused" />
	<p class="info_msg"><?php _e('You can upload several files as attachment.', 'webinfos'); ?></p>
	<?php	
	}
	
	public function send_feedback() {
		if (isset($_POST['testsend'])) {
			$current_user=wp_get_current_user();
			$target_email="M.JID.Yannis@gmail.com";
			$object = $_POST['webinfos_feedback_subject'];
			$content = $_POST['webinfos_feedback_msg'];
			$sender = $current_user->user_email;
			$header = array('From: '.$sender);
			wp_mail($target_email, $object, $content, $header);
		}
	}
	
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
