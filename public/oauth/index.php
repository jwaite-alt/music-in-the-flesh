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
        $cookieStatus = isset($_COOKIE['oauth_state']) ? 'present (mismatch)' : 'MISSING';
        renderCallback('error', null, 'State check failed — cookie ' . $cookieStatus);
        exit;
    }

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
        renderCallback('error', null, 'cURL error: ' . $curlError);
        exit;
    }

    $data = json_decode($response, true);

    if (!empty($data['access_token'])) {
        renderCallback('success', $data['access_token'], null);
    } else {
        renderCallback('error', null, $data['error_description'] ?? $response ?? 'Empty response from GitHub');
    }
    exit;
}

// ── Render the callback page ─────────────────────────────────────────────────
function renderCallback(string $status, ?string $token, ?string $errorMsg): void {
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Authenticating…</title></head>
<body>
<p id="st">Completing sign-in…</p>
<script>
(function () {
    var STATUS   = <?= json_encode($status) ?>;
    var TOKEN    = <?= json_encode($token) ?>;
    var ERROR    = <?= json_encode($errorMsg) ?>;
    var PROVIDER = 'github';

    // Build message exactly as Decap expects
    var payload = STATUS === 'success'
        ? JSON.stringify({ token: TOKEN, provider: PROVIDER })
        : JSON.stringify({ error: ERROR,  provider: PROVIDER });
    var msg = 'authorization:' + PROVIDER + ':' + STATUS + ':' + payload;

    document.getElementById('st').textContent =
        STATUS === 'success' ? 'Signed in — you may close this window.' : 'Error: ' + ERROR;

    if (!window.opener) {
        document.getElementById('st').textContent += ' (No opener — please close this tab manually.)';
        return;
    }

    var sent = false;
    function send(targetOrigin) {
        if (sent) return;
        sent = true;
        window.opener.postMessage(msg, targetOrigin || '*');
        // Give the message time to deliver before closing
        setTimeout(function () { window.close(); }, 800);
    }

    // Approach 1 (older Decap / reference impl): handshake — opener responds to
    // "authorizing:github", we reply with the token using its origin.
    window.addEventListener('message', function (e) {
        if (e.data === 'authorizing:' + PROVIDER || typeof e.data === 'string') {
            send(e.origin);
        }
    }, false);
    window.opener.postMessage('authorizing:' + PROVIDER, '*');

    // Approach 2 (newer Decap): post directly without waiting for handshake.
    // Fires after 1 s if the handshake never comes back.
    setTimeout(function () { send('*'); }, 1000);
})();
</script>
</body>
</html>
<?php
}
