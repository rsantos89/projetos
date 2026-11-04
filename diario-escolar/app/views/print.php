<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<link rel="stylesheet" href="<?= asset('print.css') ?>">
</head>
<body>
<div class="noprint toolbar"><button onclick="window.print()">Imprimir / Salvar PDF</button> <button onclick="history.back()">Voltar</button></div>
<?= $content ?>
</body>
</html>
