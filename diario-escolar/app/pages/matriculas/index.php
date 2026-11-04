<?php
require_role('admin', 'gestor');
$title = 'Matrículas';
$u = user();
$escolaId = $u['perfil'] === 'gestor' ? (int) $u['escola_id'] : gi('escola_id');
$where = 'm.ano_letivo_id = ?';
$params = [ano_id()];
if ($escolaId) { $where .= ' AND t.escola_id = ?'; $params[] = $escolaId; }
if (g('situacao') && isset(SITUACOES[g('situacao')])) { $where .= ' AND m.situacao = ?'; $params[] = g('situacao'); }
if (g('busca')) { $where .= ' AND (a.nome LIKE ? OR m.numero = ?)'; $params[] = '%' . g('busca') . '%'; $params[] = g('busca'); }
$lista = all("SELECT m.*, a.nome, a.nome_social, t.nome turma, t.turno, s.nome escola, e.nome etapa FROM matriculas m JOIN alunos a ON a.id = m.aluno_id
    JOIN turmas t ON t.id = m.turma_id JOIN escolas s ON s.id = t.escola_id JOIN etapas e ON e.id = m.etapa_id WHERE $where ORDER BY m.id DESC LIMIT 300", $params);
?>
<div class="page-head"><h1>Matrículas <?= ano()['ano'] ?? '' ?></h1>
  <div class="actions"><a class="btn primary" href="<?= url('matriculas/nova') ?>">+ Nova matrícula</a><a class="btn" href="<?= url('matriculas/rematricula') ?>">Rematrícula em lote</a></div></div>
<form class="filters" method="get"><input type="hidden" name="r" value="matriculas">
  <?php if ($u['perfil'] === 'admin'): ?><label>Escola<select name="escola_id" data-autosubmit><?= options(escolas_visiveis(), $escolaId ?: '', 'id', 'nome', 'Todas') ?></select></label><?php endif ?>
  <label>Situação<select name="situacao" data-autosubmit><?= options(SITUACOES, g('situacao'), 'id', 'nome', 'Todas') ?></select></label>
  <label>Aluno ou nº<input name="busca" value="<?= e(g('busca')) ?>"></label><button class="btn">Filtrar</button></form>
<div class="table-wrap"><table><thead><tr><th>Nº</th><th>Aluno</th><th>Turma / etapa</th><th>Data</th><th>Situação</th><th></th></tr></thead><tbody>
<?php foreach ($lista as $m): ?><tr><td><?= e($m['numero']) ?></td><td><a href="<?= url('alunos/ver', ['id' => $m['aluno_id']]) ?>"><?= e(nome_aluno($m)) ?></a></td>
<td><?= e($m['etapa']) ?> · <a href="<?= url('turmas/ver', ['id' => $m['turma_id']]) ?>"><?= e($m['turma']) ?></a><?= $escolaId ? '' : '<span class="etapa-tag">' . e($m['escola']) . '</span>' ?></td>
<td><?= fdate($m['data_matricula']) ?></td><td><?= situacao_badge($m['situacao']) ?></td><td><a class="btn sm" href="<?= url('matriculas/mover', ['id' => $m['id']]) ?>">Movimentar</a></td></tr><?php endforeach ?>
<?php if (!$lista): ?><tr><td colspan="6" class="empty">Nenhuma matrícula.</td></tr><?php endif ?>
</tbody></table></div>
