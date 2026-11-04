<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/core.php';
session_start();

$cfgFile = ROOT . '/config.php';
$erro = '';
$ok = false;

function installed(): bool
{
    if (!$GLOBALS['config']) return false;
    try { return (bool) val('SELECT COUNT(*) FROM usuarios'); } catch (Throwable $t) { return false; }
}

if (installed()) {
    $ok = true;
} elseif (is_post()) {
    $f = array_map(fn($v) => is_string($v) ? trim($v) : $v, $_POST);
    try {
        if (version_compare(PHP_VERSION, '8.1', '<')) throw new RuntimeException('É necessário PHP 8.1 ou superior.');
        if (!filter_var($f['admin_email'] ?? '', FILTER_VALIDATE_EMAIL)) throw new RuntimeException('E-mail do administrador inválido.');
        if (strlen($f['admin_senha'] ?? '') < 8) throw new RuntimeException('A senha deve ter pelo menos 8 caracteres.');
        $config = [
            'db' => ['host' => $f['db_host'] ?: 'localhost', 'port' => $f['db_port'] ?? '', 'name' => $f['db_name'], 'user' => $f['db_user'], 'pass' => $f['db_pass']],
            'timezone' => $f['timezone'] ?: 'America/Sao_Paulo',
        ];
        $GLOBALS['config'] = $config;
        $pdo = db();
        foreach (['schema.sql', 'seed.sql'] as $sqlFile) {
            if ($sqlFile === 'seed.sql' && val('SELECT COUNT(*) FROM etapas')) continue;
            $sql = preg_replace('/^--.*$/m', '', file_get_contents(__DIR__ . '/' . $sqlFile));
            foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $stmt) $pdo->exec($stmt);
        }
        $ano = (int) date('Y');
        if (!val('SELECT COUNT(*) FROM anos_letivos')) {
            $anoId = insert('anos_letivos', ['ano' => $ano]);
            $datas = [["$ano-02-01", "$ano-04-15"], ["$ano-04-16", "$ano-07-10"], ["$ano-07-25", "$ano-09-30"], ["$ano-10-01", "$ano-12-15"]];
            foreach ($datas as $i => [$ini, $fim]) insert('periodos', ['ano_letivo_id' => $anoId, 'numero' => $i + 1, 'nome' => ($i + 1) . 'º Bimestre', 'data_inicio' => $ini, 'data_fim' => $fim]);
        }
        foreach (['nome_sistema' => $f['nome_sistema'] ?: 'Diário Escolar', 'municipio' => $f['municipio'], 'uf' => strtoupper($f['uf']), 'secretaria' => $f['secretaria'] ?: 'Secretaria Municipal de Educação'] as $k => $v) set_setting($k, $v);
        insert('usuarios', ['nome' => $f['admin_nome'], 'email' => mb_strtolower($f['admin_email']), 'senha_hash' => password_hash($f['admin_senha'], PASSWORD_DEFAULT), 'perfil' => 'admin']);
        $php = "<?php\n// Gerado pelo instalador em " . date('Y-m-d H:i') . "\nreturn " . var_export($config, true) . ";\n";
        if (@file_put_contents($cfgFile, $php) === false) {
            $erro = 'Banco instalado, mas não foi possível gravar config.php. Crie o arquivo manualmente na raiz com o conteúdo abaixo:';
            $manual = $php;
        } else {
            @chmod($cfgFile, 0640);
            $ok = true;
        }
    } catch (Throwable $t) {
        $erro = $t instanceof PDOException ? 'Erro no banco de dados: ' . $t->getMessage() : $t->getMessage();
    }
}
$v = fn($k, $d = '') => e($_POST[$k] ?? $d);
?><!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instalação · Diário Escolar</title><link rel="stylesheet" href="../assets/app.css"></head>
<body class="auth-page">
<div class="auth-card" style="max-width:640px;text-align:left">
  <h1>Instalação do Diário Escolar</h1>
  <?php if ($ok): ?>
    <div class="alert success">Sistema instalado com sucesso!</div>
    <p><b>Importante:</b> por segurança, apague a pasta <code>install/</code> do servidor.</p>
    <a class="btn primary" href="../">Acessar o sistema</a>
  <?php else: ?>
    <?php if ($erro): ?><div class="alert error"><?= e($erro) ?></div><?php endif ?>
    <?php if (!empty($manual)): ?><textarea readonly rows="12" style="font-family:monospace"><?= e($manual) ?></textarea><?php else: ?>
    <p class="muted">Crie um banco MySQL e um usuário no cPanel (<i>Bancos de dados MySQL</i>) e preencha abaixo.</p>
    <form method="post">
      <fieldset><legend>Banco de dados</legend>
        <div class="row"><label>Servidor<input name="db_host" value="<?= $v('db_host', 'localhost') ?>"></label><label>Porta<input name="db_port" value="<?= $v('db_port') ?>" placeholder="3306"></label></div>
        <div class="row"><label>Nome do banco<input name="db_name" required value="<?= $v('db_name') ?>"></label><label>Usuário<input name="db_user" required value="<?= $v('db_user') ?>"></label><label>Senha<input type="password" name="db_pass" value="<?= $v('db_pass') ?>"></label></div>
      </fieldset>
      <fieldset><legend>Rede municipal</legend>
        <div class="row"><label>Nome do sistema<input name="nome_sistema" value="<?= $v('nome_sistema', 'Diário Escolar') ?>"></label><label>Secretaria<input name="secretaria" value="<?= $v('secretaria', 'Secretaria Municipal de Educação') ?>"></label></div>
        <div class="row"><label>Município<input name="municipio" required value="<?= $v('municipio') ?>"></label><label>UF<input name="uf" maxlength="2" required value="<?= $v('uf') ?>"></label><label>Fuso horário<select name="timezone"><?= options(array_combine($tz = ['America/Sao_Paulo', 'America/Manaus', 'America/Belem', 'America/Fortaleza', 'America/Recife', 'America/Bahia', 'America/Cuiaba', 'America/Campo_Grande', 'America/Porto_Velho', 'America/Boa_Vista', 'America/Rio_Branco', 'America/Araguaina', 'America/Maceio', 'America/Noronha'], $tz), $_POST['timezone'] ?? 'America/Sao_Paulo') ?></select></label></div>
      </fieldset>
      <fieldset><legend>Administrador (Secretaria Municipal)</legend>
        <div class="row"><label>Nome<input name="admin_nome" required value="<?= $v('admin_nome') ?>"></label><label>E-mail (use o Gmail/Google Workspace para login com Google)<input type="email" name="admin_email" required value="<?= $v('admin_email') ?>"></label></div>
        <div class="row"><label>Senha (mín. 8)<input type="password" name="admin_senha" minlength="8" required></label></div>
      </fieldset>
      <button class="btn primary block">Instalar</button>
    </form>
    <?php endif ?>
  <?php endif ?>
</div>
</body></html>
