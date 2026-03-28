<?php
/**
 * GitHub OAuth handler for Decap CMS
 */

define('OAUTH_CLIENT_ID',     getenv('OAUTH_CLIENT_ID')     ?: 'Ov23liloSHwASg3FnFD8');
define('OAUTH_CLIENT_SECRET', getenv('OAUTH_CLIENT_SECRET') ?: '069e9dfb826a458f58e349add2bf249be65c72f7');
define('OAUTH_SCOPE',         'repo,user');
define('REDIRECT_URI',        'https://jwaite.com/musicintheflesh/oauth/callback');

$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$action = basename($path);

// ── Step 1: redirect to GitHub ──────────────────────────────────────────────
if ($action === 'auth' || $action === 'index.php') {
    $state = bin2hex(random_bytes(16));
    setcookie('oauth_state', $state, [
        'expires'  => time() + 600,
        'path'     => '/',
        'secure'   => false,
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

    if (empty($code) || $state !== ($_COOKIE['oauth_state'] ?? '')) {
        postAndClose('error', null, 'State mismatch — cookie ' . (isset($_COOKIE['oauth_state']) ? 'present' : 'MISSING'));
        exit;
    }

    // Exchange code for token
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
    $response  = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        postAndClose('error', null, 'cURL error: ' . $curlError);
        exit;
    }

    $data = json_decode($response, true);

    if (!empty($data['access_token'])) {
        postAndClose('success', $data['access_token'], null);
    } else {
        postAndClose('error', null, $data['error_description'] ?? $response ?? 'empty response');
    }
    exit;
}

// ── Helper ───────────────────────────────────────────────────────────────────
function postAndClose(string $status, ?string $token, ?string $error): void {
    $payload = json_encode([
        'token'    => $token,
        'provider' => 'github',
        'status'   => $status,
    ]);
    $msg = 'authorization:github:' . $status . ':' . $payload;
    ?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Authenticating…</title></head>
<body>
<p id="status"><?= $status === 'success' ? 'Signing in…' : 'Error: ' . htmlspecialchars($error ?? '') ?></p>
<script>
(function () {
    var msg = <?= json_encode($msg) ?>;
    if (window.opener) {
        window.opener.postMessage(msg, '*');
        window.close();
    } else {
        document.getElementById('status').textContent = 'No opener window found — please close this tab and try again.';
    }
})();
</script>
</body>
</html>
<?php
}
