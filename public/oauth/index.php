<?php
/**
 * GitHub OAuth handler for Sveltia/Decap CMS
 * Handshake pattern matches sveltia-cms-auth reference implementation.
 *
 * CREDENTIALS
 * -----------
 * This script needs a GitHub OAuth App Client ID and Secret.
 * Two ways to provide them (in order of preference):
 *
 * 1. Server environment variables (recommended):
 *    Set OAUTH_CLIENT_ID and OAUTH_CLIENT_SECRET via your server config,
 *    cPanel Environment Variables, or .htaccess SetEnv directives.
 *
 * 2. Local credentials file (simpler for shared hosting):
 *    Copy credentials.php.example → credentials.php in this directory,
 *    fill in the values, and ensure credentials.php is NOT committed to git.
 *
 * REDIRECT URI (update when moving to a new domain)
 * -----------
 * Also set OAUTH_REDIRECT_URI as an environment variable, or update
 * credentials.php. Must match the callback URL in the GitHub OAuth App.
 */

// ── Load credentials ─────────────────────────────────────────────────────────
$clientId     = getenv('OAUTH_CLIENT_ID');
$clientSecret = getenv('OAUTH_CLIENT_SECRET');
$redirectUri  = getenv('OAUTH_REDIRECT_URI');

// Fall back to local credentials file if env vars not set
$credFile = __DIR__ . '/credentials.php';
if ((!$clientId || !$clientSecret) && file_exists($credFile)) {
    require_once $credFile;
}

if (!$clientId || !$clientSecret) {
    http_response_code(500);
    die('OAuth credentials not configured. See comments in index.php.');
}

if (!$redirectUri) {
    http_response_code(500);
    die('OAUTH_REDIRECT_URI not configured. See comments in index.php.');
}

define('OAUTH_SCOPE', 'repo,user');

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
        'client_id'    => $clientId,
        'scope'        => OAUTH_SCOPE,
        'state'        => $state,
        'redirect_uri' => $redirectUri,
    ]);

    header('Location: https://github.com/login/oauth/authorize?' . $params);
    exit;
}

// ── Step 2: GitHub redirects back here ──────────────────────────────────────
if ($action === 'callback') {
    $code  = $_GET['code']  ?? '';
    $state = $_GET['state'] ?? '';

    if (empty($code) || $state !== ($_COOKIE['oauth_state'] ?? '')) {
        renderCallback('error', null, 'State check failed — please try again.');
        exit;
    }

    $ch = curl_init('https://github.com/login/oauth/access_token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
        ]),
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response  = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        renderCallback('error', null, 'Connection error: ' . $curlError);
        exit;
    }

    $data = json_decode($response, true);

    if (!empty($data['access_token'])) {
        renderCallback('success', $data['access_token'], null);
    } else {
        renderCallback('error', null, $data['error_description'] ?? 'Unknown error from GitHub');
    }
    exit;
}

// ── Render callback page ─────────────────────────────────────────────────────
function renderCallback(string $status, ?string $token, ?string $error): void {
    $provider = 'github';

    if ($status === 'success') {
        $content = json_encode(['provider' => $provider, 'token' => $token]);
    } else {
        $content = json_encode(['provider' => $provider, 'error' => $error]);
    }

    $message = 'authorization:' . $provider . ':' . $status . ':' . $content;
?>
<!doctype html><html><head><meta charset="utf-8"><title>Authenticating…</title></head><body><script>
(() => {
  const msg = <?= json_encode($message) ?>;
  window.addEventListener('message', ({ data, origin }) => {
    if (data === 'authorizing:github') {
      window.opener?.postMessage(msg, origin);
    }
  });
  window.opener?.postMessage('authorizing:github', '*');
})();
</script></body></html>
<?php
}
