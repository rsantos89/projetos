<?php
$title = 'Diário de classe';
$u = user();
if ($u['perfil'] === 'professor') {
    $turmas = all('SELECT DISTINCT t.*, s.nome AS escola_nome FROM lotacoes l JOIN turmas t ON t.id = l.turma_id JOIN escolas s ON s.id = t.escola_id
        WHERE l.usuario_id = ? AND t.ano_letivo_id = ? AND t.ativo = 1 ORDER BY s.nome, t.nome', [$u['id'], ano_id()]);
} else {
    $escolaId = $u['perfil'] === 'gestor' ? (int) $u['escola_id'] : gi('escola_id');
    $turmas = $escolaId ? all('SELECT t.*, s.nome escola_nome FROM turmas t JOIN escolas s ON s.id = t.escola_id WHERE t.ano_letivo_id = ? AND t.escola_id = ? AND t.ativo = 1 ORDER BY t.nome', [ano_id(), $escolaId]) : [];
}
?>
<div class="page-head"><h1>Diário de classe <?= ano()['ano'] ?? '' ?></h1></div>
<?php if ($u['perfil'] === 'admin'): ?>
<form class="filters" method="get"><input type="hidden" name="r" value="diario"><label>Escola<select name="escola_id" data-autosubmit><?= options(escolas_visiveis(), $escolaId ?: '', 'id', 'nome', 'Selecione a escola...') ?></select></label></form>
<?php if (!$escolaId) return; endif ?>
<?php require ROOT . '/app/views/_turma_cards.php';
