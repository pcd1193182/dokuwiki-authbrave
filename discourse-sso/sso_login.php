<?php

require('config.php');
require('sso_nonce.php');
$payload_enc=$_GET['payload'];
$sig = $_GET['sig'];
$hmac = hash_hmac('sha256', $payload_enc, $cfg_sso_secret, true);
if ($hmac != $sig) {
    require('sso_inval.php');
    die ('non-matching sig');
}

$payload_plain = base64_decode($payload_end);
parse_str($payload_plain, $output);
if (!isset($output['nonce'])) {
    require('sso_inval.php');
    die ('no nonce');
}

$nonce = $output['nonce'];

try {
    $db = new PDO($cfg_sql_url, $cfg_sql_user, $cfg_sql_pass);
} catch (PDOException $e) {
    require('sso_internal_error.php');
    die('auth DB init failed');
}

$cb = check_nonce($db, $nonce);

//At this point, we trust that the payload came from the sso provider, and its contents are valid.
$email = $output['email'];
$username = $output['username'];
$external_id = $output['external_id'];

require('../../../inc/PassHash.class.php');
$ph = new PassHash();
$cookie = $ph->gen_salt(32);
if (!headers_sent()) {
    setcookie($cfg_cookie_name, $cookie, time() + $cfg_expire_session, '/', null, $cfg_cookie_https_only, true);
}

$stm = $db->prepare('SELECT username FROM user where id = :id;');
$stm->bindValue(':id', $external_id, PDO::PARAM_INT);
if (!$stm->execute()) {
    require('sso_internal_error.php');
    die('user query failed');
}

if ($stm->fetch()) {
    $stm = $db->prepare('UPDATE user SET username = :username, email = :email, authlast = :now WHERE id = :id;');
} else {
    $stm = $db->prepare('INSERT INTO user (id, username, email, authcreated, authlast) VALUES (:id, :username, :email, :now, :now);');
}
$stm->bindValue(':id', $external_id, PDO::PARAM_INT);
$stm->bindValue(':username', $username, PDO::PARAM_STR);
$stm->bindValue(':email', $email, PDO::PARAM_STR);
$stm->bindValue(':now', time(), PDO::PARAM_INT);
if (!$stm->execute()) {
    require('sso_internal_error.php');
    die('user insert or update failed');
}

$stm = $db->prepare('DELETE from session where sessionid = :sessionid;');
$stm->bindValue(':sessionid', $cookie);
if (!$stm->execute()) {
    require('sso_internal_error.php');
    die('session cleanup failed');
}

$stm = $db->prepare('INSERT INTO session (sessionid, charid, created) VALUES (:sessionid, :charid, :created)');
$stm->bindValue(':sessionid', $cookie);
$stm->bindValue(':charid', $charid);
$stm->bindValue(':created', time());
if (!$stm->execute()) {
    require('sso_internal_error.php');
    die('session insert failed');
}

// -----------------------------------------------

header("Location: " . $cfg_url_base . '/' . $cb);

?>
