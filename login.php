/**
 * The loginform
 *
 * @author   Andreas Gohr <andi@splitbrain.org>
 * @author   Paul Dagnelie <paulcd2000@gmail.com>
 *
 * @param bool $svg Whether to show svg icons in the register and resendpwd links or not
 */
function html_login($svg = false){
    global $ID
    require('lib/plugins/discourse-sso/config.php');
    require('lib/plugins/discourse-sso/sso_nonce.php');
    $payload_plain = "nonce=" . gen_nonce($ID);
    $payload = base64_encode($payload_plain);
    $sig = hash_hmac("sha256", $payload, $cfg_sso_secret, false);
    print p_locale_xhtml('login');
    print '<div class="centeralign">'.NL;
    print '<a href="' . $cfg_sso_url . '/sso?sso=' . $payload . '&sig=' . $sig . '>Log In with SSO</a>'
    print '</div>'.NL;
}
