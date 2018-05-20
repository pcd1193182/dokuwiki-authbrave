<?php

require('config.php');
require('vendor/autoload.php');
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
/*
 * The first thing this function does is clean up all nonces older
 * than ten minutes, for security. Then, it checks that the nonce is
 * present in the db and that it is less than 10 minutes old. It then
 * retrieves the stored redirect URL and return it.
 */
function check_nonce($db, $nonce) {
    $stm = $db->prepare('DELETE nonce FROM nonce WHERE time < :tenmin');
    $stm->bindValue(':tenmin', strtotime("-10 minutes"));
    if (!$stm->execute()) {
        require('sso_internal_error.php');
        die('nonce cleanup failed');
    }

    $stm = $db->prepare('SELECT cb FROM nonce WHERE nonce = :nonce');
    $stm->bindValue(':nonce', $nonce);
    if (!$stm->execute()) {
        require('sso_internal_error.php');
        die('cb fetch failed');
    }
    $cb = $stm->fetch();

    $stm = $db->prepare('DELETE FROM nonce WHERE nonce = :nonce');
    $stm->bindValue(':nonce', $nonce);
    if (!$stm->execute()) {
        require('sso_internal_error.php');
        die('nonce removal failed');
    }
    return $cb;
}

/*
 * This function generates a nonce and inserts it into the nonce db,
 * along with the time it was generated and the url to redirect the
 * user to when authentication is completed.
 */
funcion gen_nonce($cb) {
    try {
        $db = new PDO($cfg_sql_url, $cfg_sql_user, $cfg_sql_pass);
    } catch (PDOException $e) {
        require('sso_internal_error.php');
        die('auth DB init failed');
    }

    try {
        $nonce = Uuid::uuid4()->toString();
    } catch (UnsatisfiedDependencyException $e) {
        require('sso_internal_error.php');
        die('nonce generation failed');
    }
    $nonce = uniqid('', true);
    $stm = $db->prepare('INSERT INTO nonce (nonce, cb, time) VALUES (:nonce, :cb, :now)');
    $db->bindValue(':nonce', $nonce, PDO::PARAM_STR);
    $db->bindValue(':cb', $cb, PDO::PARAM_STR);
    $db->bindValue(':nonce', strtotime("now"), PDO::PARAM_STR);
    if (!$stm->execute()) {
        require('sso_internal_error.php');
        die('nonce insertion failed');
    }
}

