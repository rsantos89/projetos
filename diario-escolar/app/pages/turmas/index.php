<?php
require_role('admin', 'gestor');
$title = 'Turmas';
$u = user();
$escolas = escolas_visiveis();
$escolaId = $u['perfil'] === 'gestor' ? (int) $u['escola_id'] : gi('escola_id');
$where = 't.ano_letivo_id = ?';
$params = [ano_id()];
if ($escolaId) { $where .= ' AND t.escola_id = ?'; $params[] = $escolaId; }
if (g('nivel') && isset(NIVEIS[g('nivel')])) { $where .= ' AND EXISTS (SELECT 1 FROM turma_etapas te JOIN etapas e ON e.id = te.etapa_id WHERE te.turma_id = t.id AND e.nivel = ?)'; $params[] = g('nivel'); }
if (g('multi')) $where .= ' AND (SELECT COUNT(*) FROM turma_etapas te WHERE te.turma_id = t.id) > 1';
$turmas = all("SELECT t.*, s.nome escola_nome,
    (SELECT GROUP_CONCAT(e.nome ORDER BY e.ordem SEPARATOR ' + ') FROM turma_etapas te JOIN etapas e ON e.id = te.etapa_id WHERE te.turma_id = t.id) etapas,
    (SELECT COUNT(*) FROM turma_etapas te WHERE te.turma_id = t.id) netapas,
    (SELECT COUNT(*) FROM matriculas m WHERE m.turma_id = t.id AND m.situacao = 'cursando') alunos,
    (SELECT GROUP_CONCAT(DISTINCT us.nome SEPARATOR ', ') FROM lotacoes l JOIN usuarios us ON us.id = l.usuario_id WHERE l.turma_id = t.id) profs
    FROM turmas t JOIN escolas s ON s.id = t.escola_id WHERE $where ORDER BY s.nome, t.nome", $params);
?>
<div class="page-head"><h1>Turmas <?= ano()['ano'] ?? '' ?></h1><a class="btn primary" href="<?= url('turmas/form', $escolaId ? ['escola_id' => $escolaId] : []) ?>">+ Nova turma</a></div>
<form class="filters" method="get"><input type="hidden" name="r" value="turmas">
  <?php if ($u['perfil'] === 'admin'): ?><label>Escola<select name="escola_id" data-autosubmit><?= options($escolas, $escolaId ?: '', 'id', 'nome', 'Todas') ?></select></label><?php endif ?>
  <label>Etapa de ensino<select name="nivel" data-autosubmit><?= options(NIVEIS, g('nivel'), 'id', 'nome', 'Todas') ?></select></label>
  <label class="check"><input type="checkbox" name="multi" value="1" data-autosubmit<?= checked(g('multi')) ?>> Só multisseriadas</label>
</form>
<div class="table-wrap"><table><thead><tr><th>Turma</th><?php if (!$escolaId): ?><th>Escola</th><?php endif ?><th>Etapas</th><th>Professores</th><th class="num">Alunos/Vagas</th><th></th></tr></thead><tbody>
<?php foreach ($turmas as $t): ?>
<tr><td><a href="<?= url('turmas/ver', ['id' => $t['id']]) ?>"><b><?= e($t['nome']) ?></b></a><span class="etapa-tag"><?= e(TURNOS[$t['turno']]) ?><?= $t['sala'] ? ' · Sala ' . e($t['sala']) : '' ?></span></td>
<?php if (!$escolaId): ?><td><?= e($t['escola_nome']) ?></td><?php endif ?>
<td><?= e($t['etapas']) ?> <?= $t['netapas'] > 1 ? '<span class="badge warn">Multisseriada</span>' : '' ?></td><td><small><?= e($t['profs'] ?: '—') ?></small></td>
<td class="num"><span class="badge <?= $t['alunos'] > $t['vagas'] ? 'bad' : 'ok' ?>"><?= $t['alunos'] ?>/<?= $t['vagas'] ?></span></td>
<td class="actions"><a class="btn sm" href="<?= url('turmas/ver', ['id' => $t['id']]) ?>">Abrir</a></td></tr>
<?php endforeach; if (!$turmas): ?><tr><td colspan="6" class="empty">Nenhuma turma neste ano letivo.</td></tr><?php endif ?>
</tbody></table></div>
