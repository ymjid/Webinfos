<?php
require_once('../../../../../wp-load.php');
		global $wpdb;
		$dirattach='../../attachment/';
    			$filelist2="";
    			// upload attachments and created the list to save in database
    			for ($i=0; $i< sizeof($_FILES['dragfiles']['name']); $i++) {
    				move_uploaded_file ( $_FILES['dragfiles']['tmp_name'][$i] , $dirattach.$_FILES['dragfiles']['name'][$i] );
    				if ($i+1 == sizeof($_FILES['dragfiles']['name'])) {
    					$filelist2=$filelist2.$_FILES['dragfiles']['name'][$i];
    				}
    				else {
    					$filelist2=$filelist2.$_FILES['dragfiles']['name'][$i].',';	
    				}
    			}
    			// edit attachment files in the database if we edit the msg
    			if ($_POST['editmsg'] != null || $_POST['editmsg'] > 0) {
    				$wpdb->update("{$wpdb->prefix}webinfos", 
						array('attachment' => $filelist2), array('id' => $_POST['editmsg'])
						);
    			}
    			// save attachment files in the database if we add a msg
    			// birth state is there to inform that the msg isn't completely saved (only attachments are saved)
    			if ($_POST['editmsg'] == 0) {
    				$wpdb->insert("{$wpdb->prefix}webinfos", 
					array('attachment' => $filelist2, 'state' => 'birth')
					);
    			}
    	echo 'upload complete';
    	
	


?>