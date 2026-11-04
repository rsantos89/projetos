<?php
require_role('admin');
$title = 'Configurações';
$keys = ['nome_sistema', 'secretaria', 'municipio', 'uf', 'google_client_id', 'google_client_secret', 'google_dominio'];
if (is_post()) {
    foreach ($keys as $k) {
        if ($k === 'google_client_secret' && p($k) === '') continue;
        set_setting($k, (string) p($k));
    }
    audit('config_salvar');
    flash('success', 'Configurações salvas.');
    redirect('config');
}
?>
<div class="page-head"><h1>Configurações</h1></div>
<form method="post">
  <?= csrf_field() ?>
  <div class="card"><h2>Identificação</h2>
    <div class="row"><label>Nome do sistema<input name="nome_sistema" value="<?= e(setting('nome_sistema')) ?>"></label><label>Secretaria<input name="secretaria" value="<?= e(setting('secretaria')) ?>"></label></div>
    <div class="row"><label>Município<input name="municipio" value="<?= e(setting('municipio')) ?>"></label><label>UF<input name="uf" maxlength="2" value="<?= e(setting('uf')) ?>"></label></div>
  </div>
  <div class="card"><h2>Login com Google</h2>
    <ol class="muted">
      <li>Acesse <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener">Google Cloud Console → Credenciais</a> e crie um <b>ID do cliente OAuth</b> do tipo <i>Aplicativo da Web</i>.</li>
      <li>Em <b>URIs de redirecionamento autorizados</b>, adicione: <code><?= e(base_url() . 'login-google.php') ?></code></li>
      <li>Cole abaixo o ID e a chave secreta. Usuários entram somente se o e-mail estiver cadastrado em <a href="<?= url('usuarios') ?>">Usuários</a>.</li>
    </ol>
    <div class="row"><label>Client ID<input name="google_client_id" value="<?= e(setting('google_client_id')) ?>"></label>
      <label>Client secret <?= setting('google_client_secret') ? '(salvo — deixe em branco para manter)' : '' ?><input type="password" name="google_client_secret" autocomplete="off"></label></div>
    <div class="row"><label>Restringir ao domínio (opcional, ex.: educacao.municipio.gov.br)<input name="google_dominio" value="<?= e(setting('google_dominio')) ?>"></label></div>
  </div>
  <div class="form-foot"><button class="btn primary">Salvar</button></div>
</form>
