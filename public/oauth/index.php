<?php
/**
 * GitHub OAuth handler for Decap CMS
 */

define('OAUTH_CLIENT_ID',     getenv('OAUTH_CLIENT_ID')     ?: 'Ov23liloSHwASg3FnFD8');
define('OAUTH_CLIENT_SECRET', getenv('OAUTH_CLIENT_SECRET') ?: '069e9dfb826a458f58e349add2bf249be65c72f7');
define('OAUTH_PROVIDER',      'github');
define('OAUTH_SCOPE',         'repo,user');
define('REDIRECT_URI',        'https://jwaite.com/musicintheflesh/oauth/callback');

$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$action = basename($path);  // 'auth' or 'callback'

// ── Step 1: redirect to GitHub ──────────────────────────────────────────────
if ($action === 'auth' || $action === 'index.php') {
    $state = bin2hex(random_bytes(16));
    setcookie('oauth_state', $state, [
        'expires'  => time() + 600,
        'path'     => '/',
        'secure'   => false,   // allow over http during testing
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    $params = http_build_query([
        'client_id'    => OAUTH_CLIENT_ID,
        'scope'        => OAUTH_SCOPE,
        'state'        => $state,
        'redirect_uri' => REDIRECT_URI,
    ]);

    header('Location: https://github.com/login/oauth/authorize?' . $params);
    exit;
}

// ── Step 2: GitHub redirects back here ──────────────────────────────────────
if ($action === 'callback') {
    $code  = $_GET['code']  ?? '';
    $state = $_GET['state'] ?? '';

    // CSRF check
    if (empty($code) || $state !== ($_COOKIE['oauth_state'] ?? '')) {
        sendMessage('error', 'State mismatch or missing code. Cookie: ' . (isset($_COOKIE['oauth_state']) ? 'present' : 'MISSING'));
        exit;
    }

    // Exchange code for token using cURL
    $ch = curl_init('https://github.com/login/oauth/access_token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'client_id'     => OAUTH_CLIENT_ID,
            'client_secret' => OAUTH_CLIENT_SECRET,
            'code'          => $code,
            'redirect_uri'  => REDIRECT_URI,
        ]),
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        sendMessage('error', 'cURL error: ' . $curlError);
        exit;
    }

    $data = json_decode($response, true);

    if (!empty($data['access_token'])) {
        sendMessage('success', $data['access_token']);
    } else {
        sendMessage('error', 'GitHub error: ' . ($data['error_description'] ?? $response ?? 'empty response'));
    }
    exit;
}

// Unknown path — show debug info
http_response_code(404);
echo '<pre>OAuth handler reached. Action: ' . htmlspecialchars($action) . "\nURI: " . htmlspecialchars($_SERVER['REQUEST_URI']) . '</pre>';

// ── Helper: post message back to Decap CMS ───────────────────────────────────
function sendMessage(string $status, string $content): void {
    $message = json_encode([
        'token'    => $status === 'success' ? $content : null,
        'provider' => OAUTH_PROVIDER,
        'status'   => $status,
        'errorMessage' => $status !== 'success' ? $content : null,
    ]);
    ?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Authenticating…</title></head>
<body>
<p id="msg">Completing sign-in…</p>
<script>
(function () {
    var status  = <?= json_encode($status) ?>;
    var message = <?= json_encode('authorization:github:' . $status . ':' . addslashes($message)) ?>;

    if (!window.opener) {
        document.getElementById('msg').textContent =
            'Sign-in ' + (status === 'success' ? 'succeeded' : 'failed') +
            ' but this window has no opener. ' +
            <?= json_encode($status !== 'success' ? 'Error: ' . $content : 'Please close this tab and try again.') ?>;
        return;
    }

    function receiveMessage(e) {
        window.opener.postMessage(message, e.origin);
    }
    window.addEventListener('message', receiveMessage, false);
    window.opener.postMessage('authorizing:github', '*');
})();
</script>
</body>
</html>
<?php
}
