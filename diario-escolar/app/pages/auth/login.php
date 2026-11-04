<?php
if (user()) redirect('painel');
$tpl = 'auth';
$title = 'Entrar';

if (is_post()) {
    $email = mb_strtolower((string) p('email'));
    $_SESSION['login_tentativas'] = ($_SESSION['login_tentativas'] ?? 0) + 1;
    if ($_SESSION['login_tentativas'] > 8) {
        flash('error', 'Muitas tentativas. Aguarde alguns minutos.');
        if (($_SESSION['login_bloqueio'] ??= time()) + 300 < time()) unset($_SESSION['login_tentativas'], $_SESSION['login_bloqueio']);
        redirect('auth/login');
    }
    $u = one('SELECT * FROM usuarios WHERE email = ? AND ativo = 1', [$email]);
    if ($u && $u['senha_hash'] && password_verify((string) p('senha'), $u['senha_hash'])) {
        unset($_SESSION['login_tentativas'], $_SESSION['login_bloqueio']);
        login_user($u, 'senha');
        redirect('painel');
    }
    flash('error', 'E-mail ou senha inválidos.');
    redirect('auth/login');
}
$google = setting('google_client_id') !== '' && setting('google_client_secret') !== '';
?>
<div class="auth-card">
  <img src="<?= asset('icon.svg') ?>" alt="" class="auth-logo">
  <h1><?= e(setting('nome_sistema', 'Diário Escolar')) ?></h1>
  <p class="muted"><?= e(setting('secretaria', 'Secretaria Municipal de Educação')) ?><?= setting('municipio') ? ' · ' . e(setting('municipio')) : '' ?></p>
  <?php if ($google): ?>
    <a class="btn google" href="<?= url('auth/google') ?>">
      <svg viewBox="0 0 48 48" width="20" height="20"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z"/></svg>
      Entrar com Google
    </a>
    <div class="divider"><span>ou</span></div>
  <?php endif ?>
  <form method="post" class="stack">
    <?= csrf_field() ?>
    <label>E-mail<input type="email" name="email" required autocomplete="username"></label>
    <label>Senha<input type="password" name="senha" required autocomplete="current-password"></label>
    <button class="btn primary block">Entrar</button>
  </form>
</div>
