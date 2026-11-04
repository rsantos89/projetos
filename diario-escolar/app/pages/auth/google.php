<?php
$clientId = setting('google_client_id');
$secret = setting('google_client_secret');
$redirectUri = base_url() . 'login-google.php';
if ($clientId === '' || $secret === '') {
    flash('error', 'Login com Google não configurado.');
    redirect('auth/login');
}

if (!isset($_GET['code'])) {
    if (isset($_GET['error'])) {
        flash('error', 'Login com Google cancelado.');
        redirect('auth/login');
    }
    $_SESSION['oauth_state'] = bin2hex(random_bytes(16));
    $params = [
        'client_id' => $clientId, 'redirect_uri' => $redirectUri, 'response_type' => 'code',
        'scope' => 'openid email profile', 'state' => $_SESSION['oauth_state'], 'prompt' => 'select_account',
    ];
    if (setting('google_dominio') !== '') $params['hd'] = setting('google_dominio');
    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
    exit;
}

$state = $_SESSION['oauth_state'] ?? '';
unset($_SESSION['oauth_state']);
if ($state === '' || !hash_equals($state, (string) g('state'))) {
    flash('error', 'Falha de segurança no login (state inválido). Tente novamente.');
    redirect('auth/login');
}

$tok = http_post('https://oauth2.googleapis.com/token', [
    'code' => (string) g('code'), 'client_id' => $clientId, 'client_secret' => $secret,
    'redirect_uri' => $redirectUri, 'grant_type' => 'authorization_code',
]);
$jwks = http_get('https://www.googleapis.com/oauth2/v3/certs');
$claims = $jwks ? jwt_verificar((string) ($tok['id_token'] ?? ''), $jwks) : null;
$ok = is_array($claims)
    && ($claims['aud'] ?? '') === $clientId
    && in_array($claims['iss'] ?? '', ['https://accounts.google.com', 'accounts.google.com'], true)
    && ($claims['exp'] ?? 0) > time()
    && !empty($claims['email_verified']);
if (!$ok) {
    flash('error', 'Não foi possível validar o login com Google.');
    redirect('auth/login');
}
$email = mb_strtolower($claims['email']);
$dom = setting('google_dominio');
if ($dom !== '' && !str_ends_with($email, '@' . $dom)) {
    flash('error', "Use uma conta do domínio @$dom.");
    redirect('auth/login');
}
$u = one('SELECT * FROM usuarios WHERE email = ? AND ativo = 1', [$email]);
if (!$u) {
    audit('login_negado', $email);
    flash('error', "O e-mail $email não está cadastrado. Procure a secretaria da sua escola.");
    redirect('auth/login');
}
update('usuarios', ['google_sub' => $claims['sub'] ?? null], 'id = ?', [$u['id']]);
login_user($u, 'google');
redirect('painel');
