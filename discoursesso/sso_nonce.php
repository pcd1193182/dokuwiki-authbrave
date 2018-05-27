<?php

require('vendor/autoload.php');

/*
 * The first thing this function does is clean up all nonces older
 * than ten minutes, for security. Then, it checks that the nonce is
 * present in the db and that it is less than 10 minutes old. It then
 * retrieves the stored redirect URL and return it.
 */
function check_nonce($db, $nonce) {
    $stm = $db->prepare('DELETE nonce FROM nonce WHERE time < :tenmin');
    $stm->bindValue(':tenmin', time() - $cfg_discourse_nonce_timeout);
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
function gen_nonce($cb) {
    require('config.php');
    try {
        $db = new PDO($cfg_sql_url, $cfg_sql_user, $cfg_sql_pass);
    } catch (PDOException $e) {
        require('sso_internal_error.php');
        die('auth DB init failed ' . $e->getMessage());
    }

    try {
        $nonce = Ramsey\Uuid\Uuid::uuid4()->toString();
    } catch (Ramsey\Uuid\Exception\UnsatisfiedDependencyException $e) {
        require('sso_internal_error.php');
        die('nonce generation failed');
    }
    $stm = $db->prepare('INSERT INTO nonce (nonce, cb, time) VALUES (:nonce, :cb, :now)');
    $stm->bindValue(':nonce', $nonce, PDO::PARAM_STR);
    $stm->bindValue(':cb', $cb, PDO::PARAM_STR);
    $stm->bindValue(':now', time(), PDO::PARAM_STR);
    if (!$stm->execute()) {
        require('sso_internal_error.php');
        die('nonce insertion failed');
    }
    return $nonce;
}

