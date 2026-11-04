<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#1d4ed8">
<title><?= e($title) ?> · <?= e(setting('nome_sistema', 'Diário Escolar')) ?></title>
<link rel="icon" href="<?= asset('icon.svg') ?>">
<link rel="stylesheet" href="<?= asset('app.css') ?>">
</head>
<body class="auth-page">
<?php foreach (flashes() as [$t, $m]): ?><div class="alert <?= e($t) ?>"><?= e($m) ?></div><?php endforeach ?>
<?= $content ?>
</body>
</html>
