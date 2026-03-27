<?php
/**
 * GitHub OAuth handler for Decap CMS
 *
 * This script runs on your cPanel server and handles the OAuth handshake
 * between Decap CMS and GitHub. It needs two values from your GitHub OAuth App:
 *
 *   OAUTH_CLIENT_ID     — the "Client ID" from your GitHub OAuth App settings
 *   OAUTH_CLIENT_SECRET — the "Client secret" from your GitHub OAuth App settings
 *
 * Set these as cPanel environment variables, or replace the getenv() calls
 * below with the values directly (keep this file private if you do so).
 *
 * GitHub OAuth App setup:
 *   github.com → Settings → Developer settings → OAuth Apps → New OAuth App
 *   Homepage URL:       https://yourdomain.com
 *   Callback URL:       https://yourdomain.com/oauth/callback
 */

define('OAUTH_CLIENT_ID',     getenv('OAUTH_CLIENT_ID')     ?: 'Ov23liloSHwASg3FnFD8');
define('OAUTH_CLIENT_SECRET', getenv('OAUTH_CLIENT_SECRET') ?: '069e9dfb826a458f58e349add2bf249be65c72f7');
define('OAUTH_PROVIDER',      'github');
define('OAUTH_SCOPE',         'repo,user');

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$action = basename($path);  // 'auth' or 'callback'

// ── Step 1: redirect to GitHub ──────────────────────────────────────────────
if ($action === 'auth' || $action === 'index.php') {
    $state = bin2hex(random_bytes(16));
    setcookie('oauth_state', $state, time() + 600, '/', '', true, true);

    $params = http_build_query([
        'client_id'    => OAUTH_CLIENT_ID,
        'scope'        => OAUTH_SCOPE,
        'state'        => $state,
        'redirect_uri' => (isset($_SERVER['HTTPS']) ? 'https' : 'http')
                          . '://' . $_SERVER['HTTP_HOST'] . '/oauth/callback',
    ]);

    header('Location: https://github.com/login/oauth/authorize?' . $params);
    exit;
}

// ── Step 2: GitHub redirects back here ─────────────────────────────────────
if ($action === 'callback') {
    $code  = $_GET['code']  ?? '';
    $state = $_GET['state'] ?? '';

    // CSRF check
    if (empty($code) || $state !== ($_COOKIE['oauth_state'] ?? '')) {
        sendMessage('error', 'Invalid state or missing code.');
        exit;
    }

    // Exchange code for token
    $response = file_get_contents('https://github.com/login/oauth/access_token', false,
        stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\nAccept: application/json\r\n",
            'content' => http_build_query([
                'client_id'     => OAUTH_CLIENT_ID,
                'client_secret' => OAUTH_CLIENT_SECRET,
                'code'          => $code,
                'redirect_uri'  => (isset($_SERVER['HTTPS']) ? 'https' : 'http')
                                   . '://' . $_SERVER['HTTP_HOST'] . '/oauth/callback',
            ]),
        ]])
    );

    $data = json_decode($response, true);

    if (!empty($data['access_token'])) {
        sendMessage('success', $data['access_token']);
    } else {
        sendMessage('error', $data['error_description'] ?? 'Unknown error from GitHub.');
    }
    exit;
}

// ── Helper: post message back to Decap CMS ─────────────────────────────────
function sendMessage(string $status, string $content): void {
    $message = json_encode([
        'token'    => $status === 'success' ? $content : null,
        'provider' => OAUTH_PROVIDER,
        'status'   => $status,
    ]);
    ?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Authenticating…</title></head>
<body>
<script>
  (function () {
    function receiveMessage(e) {
      console.log("receiveMessage %o", e);
      window.opener.postMessage(
        'authorization:github:<?= $status ?>:<?= addslashes($message) ?>',
        e.origin
      );
    }
    window.addEventListener("message", receiveMessage, false);
    window.opener.postMessage("authorizing:github", "*");
  })();
</script>
</body>
</html>
<?php
}
