<?php
$u = user();
$rota = (string) ($_GET['r'] ?? 'painel');
$mod = explode('/', $rota)[0];
$anos = all('SELECT id, ano, status FROM anos_letivos ORDER BY ano DESC');
$menu = [
    ['painel', 'Início', 'M3 12l9-8 9 8M5 10v10h5v-6h4v6h5V10', ['admin', 'gestor', 'professor']],
    ['diario', 'Diário de classe', 'M4 4h12a4 4 0 014 4v12H8a4 4 0 01-4-4zM8 8h8M8 12h8', ['admin', 'gestor', 'professor']],
    ['turmas', 'Turmas', 'M3 7h18M3 12h18M3 17h18', ['admin', 'gestor']],
    ['alunos', 'Alunos', 'M12 12a4 4 0 100-8 4 4 0 000 8zM4 21a8 8 0 0116 0', ['admin', 'gestor']],
    ['matriculas', 'Matrículas', 'M9 12l2 2 4-4M5 4h14v16H5z', ['admin', 'gestor']],
    ['relatorios', 'Relatórios', 'M6 20V10M12 20V4M18 20v-6', ['admin', 'gestor', 'professor']],
    ['usuarios', 'Usuários', 'M16 11a3 3 0 100-6 3 3 0 000 6zM8 11a3 3 0 100-6 3 3 0 000 6zM2 20a6 6 0 0112 0M14 20a6 6 0 018-5.6', ['admin', 'gestor']],
    ['cadastros', 'Cadastros da rede', 'M4 6h16M4 12h16M4 18h10', ['admin']],
];
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#1d4ed8">
<title><?= e($title ? "$title · " : '') . e(setting('nome_sistema', 'Diário Escolar')) ?></title>
<link rel="icon" href="<?= asset('icon.svg') ?>">
<link rel="stylesheet" href="<?= asset('app.css') ?>">
<script src="<?= asset('app.js') ?>" defer></script>
</head>
<body>
<header class="topbar">
  <button class="menu-btn" type="button" aria-label="Menu" data-toggle-menu><svg viewBox="0 0 24 24"><path d="M3 6h18M3 12h18M3 18h18"/></svg></button>
  <a class="brand" href="<?= url('painel') ?>"><img src="<?= asset('icon.svg') ?>" alt=""><span><?= e(setting('nome_sistema', 'Diário Escolar')) ?></span></a>
  <form class="ano-sel" method="get" action="<?= url() ?>">
    <input type="hidden" name="r" value="<?= e($rota) ?>">
    <?php foreach ($_GET as $k => $v) if (!in_array($k, ['r', 'ano_id'], true) && is_scalar($v)): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endif ?>
    <label>Ano letivo
      <select name="ano_id" data-autosubmit><?php foreach ($anos as $a): ?><option value="<?= $a['id'] ?>"<?= $a['id'] == ano_id() ? ' selected' : '' ?>><?= $a['ano'] . ($a['status'] === 'encerrado' ? ' (encerrado)' : '') ?></option><?php endforeach ?></select>
    </label>
  </form>
  <div class="user-box">
    <span class="user-name"><?= e($u['nome']) ?><small><?= e(PERFIS[$u['perfil']]) ?></small></span>
    <a class="btn sm ghost" href="<?= url('auth/logout') ?>">Sair</a>
  </div>
</header>
<nav class="sidebar" id="sidebar">
  <?php foreach ($menu as [$m, $label, $icon, $roles]): if (!in_array($u['perfil'], $roles, true)) continue; ?>
    <a href="<?= url($m) ?>" class="<?= $mod === $m || ($m === 'cadastros' && in_array($mod, ['escolas', 'anos', 'etapas', 'componentes', 'config', 'logs'], true)) ? 'active' : '' ?>"><svg viewBox="0 0 24 24"><path d="<?= $icon ?>"/></svg><?= e($label) ?></a>
  <?php endforeach ?>
  <div class="sidebar-foot"><?= e(setting('municipio')) ?> · v<?= APP_VERSION ?></div>
</nav>
<div class="backdrop" data-toggle-menu></div>
<main class="main">
  <?php foreach (flashes() as [$t, $m]): ?><div class="alert <?= e($t) ?>"><?= e($m) ?></div><?php endforeach ?>
  <?php if (!ano() && $u['perfil'] === 'admin' && $mod !== 'anos'): ?><div class="alert warn">Nenhum ano letivo cadastrado. <a href="<?= url('anos/form') ?>">Cadastre um ano letivo</a>.</div><?php endif ?>
  <?= $content ?>
</main>
</body>
</html>
