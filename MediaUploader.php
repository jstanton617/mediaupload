<?php

/*  Copyright (c) 2013, James Stanton
  All rights reserved.

  Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
   following conditions are met:

  Redistributions of source code must retain the above copyright notice, this list of conditions and the following
   disclaimer.
  Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following
   disclaimer in the documentation and/or other materials provided with the distribution.
  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
   INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
   DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
   SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
   SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
   WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
   OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/

/**
 * Root directory of Drupal installation.
 */
define('DRUPAL_ROOT', getcwd());

require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

if ($uid = user_authenticate($_POST['username'], $_POST['password'])) {
  global $user;
  $user = user_load($uid);

  $login_array = array ('name' => $_POST['username']);
  user_login_finalize($login_array);
} else {
  drupal_access_denied();
}

$destdir = 'public://media/' . date('Y/m/d');
drupal_mkdir(drupal_realpath($destdir));
file_prepare_directory($destdir);

watchdog(WATCHDOG_DEBUG, print_r($_FILES, true));


// Begin building file object.
$file = new stdClass();
$file->uid = $uid;
$file->status = 1;
$file->filename = $_FILES['file']['name'];
$file->uri = file_destination( $destdir . '/' .$_FILES['file']['name'], FILE_EXISTS_RENAME);
$file->filemime = $_FILES['file']['type'];
$file->filesize = $_FILES['file']['size'];
$file->field_description = array('und' => array(0 => array('value' => $_POST['description'], 'format' => 'plain_text'), 'safe_value' => check_plain($_POST['description'])));
$file->media_description = $file->field_description;
$file->field_authors = array('und' => array(0 => array('target_id' => $uid)));

if(strpos($file->filemime, "image") !== FALSE) {
  _imagemagick_convert($_FILES['file']['tmp_name'], $_FILES['file']['tmp_name'], array("-auto-orient"));
}

drupal_move_uploaded_file($_FILES['file']['tmp_name'], $file->uri);
drupal_chmod($file->uri);

// Change the status
// Update the file status into the database
file_save($file);