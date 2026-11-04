<?php
$tid = gi('turma_id');
$t = turma_ou_404($tid);
$title = 'Aulas · ' . $t['nome'];
$comps = componentes_permitidos($tid);
$pers = periodos((int) $t['ano_letivo_id']);
$where = 'a.turma_id = ?';
$params = [$tid];
$cf = (string) g('componente_id', 'todos');
if ($cf === '') $where .= ' AND a.componente_id IS NULL';
elseif ($cf !== 'todos') { $where .= ' AND a.componente_id = ?'; $params[] = (int) $cf; }
if (user()['perfil'] === 'professor' && !val('SELECT 1 FROM lotacoes WHERE turma_id = ? AND usuario_id = ? AND componente_id IS NULL', [$tid, user()['id']])) {
    $where .= ' AND a.componente_id IN (' . in_list(array_column($comps, 'id')) . ')';
}
$per = gi('periodo_id') ? one('SELECT * FROM periodos WHERE id = ?', [gi('periodo_id')]) : null;
if ($per) { $where .= ' AND a.data BETWEEN ? AND ?'; $params[] = $per['data_inicio']; $params[] = $per['data_fim']; }
$aulas = all("SELECT a.*, c.nome comp, u.nome prof, (SELECT SUM(f.faltas > 0) FROM frequencias f WHERE f.aula_id = a.id) ausentes, (SELECT COUNT(*) FROM frequencias f WHERE f.aula_id = a.id) total
    FROM aulas a LEFT JOIN componentes c ON c.id = a.componente_id LEFT JOIN usuarios u ON u.id = a.usuario_id WHERE $where ORDER BY a.data DESC, c.nome", $params);
$totais = all("SELECT COALESCE(c.nome, 'Diária') comp, SUM(a.quantidade) aulas, COUNT(DISTINCT a.data) dias FROM aulas a LEFT JOIN componentes c ON c.id = a.componente_id WHERE $where GROUP BY a.componente_id ORDER BY comp", $params);
echo diario_tabs($t, 'aulas');
?>
<form class="filters" method="get"><input type="hidden" name="r" value="diario/aulas"><input type="hidden" name="turma_id" value="<?= $tid ?>">
  <label>Componente<select name="componente_id" data-autosubmit><option value="todos">Todos</option><option value=""<?= $cf === '' ? ' selected' : '' ?>>Diária</option><?= options($comps, $cf) ?></select></label>
  <label>Período<select name="periodo_id" data-autosubmit><?= options($pers, gi('periodo_id') ?: '', 'id', 'nome', 'Ano todo') ?></select></label>
  <a class="btn primary" href="<?= url('diario/frequencia', ['turma_id' => $tid]) ?>">+ Registrar aula</a>
</form>
<div class="actions" style="margin-bottom:12px"><?php foreach ($totais as $x): ?><span class="badge"><?= e($x['comp']) ?>: <?= $x['aulas'] ?> aulas / <?= $x['dias'] ?> dias</span><?php endforeach ?></div>
<div class="table-wrap"><table><thead><tr><th>Data</th><th>Componente</th><th class="num">Aulas</th><th>Conteúdo</th><th class="num">Ausentes</th><th>Professor</th><th></th></tr></thead><tbody>
<?php foreach ($aulas as $a): ?><tr><td><?= fdate($a['data']) ?></td><td><?= e($a['comp'] ?? 'Diária') ?></td><td class="num"><?= $a['quantidade'] ?></td><td><?= nl2br(e($a['conteudo'])) ?: '<span class="badge warn">sem conteúdo</span>' ?></td>
<td class="num"><?= (int) $a['ausentes'] ?>/<?= $a['total'] ?></td><td><small><?= e($a['prof']) ?></small></td>
<td><a class="btn sm" href="<?= url('diario/frequencia', ['turma_id' => $tid, 'componente_id' => $a['componente_id'] ?? '', 'data' => $a['data']]) ?>">Abrir</a></td></tr><?php endforeach ?>
<?php if (!$aulas): ?><tr><td colspan="7" class="empty">Nenhuma aula registrada.</td></tr><?php endif ?>
</tbody></table></div>
