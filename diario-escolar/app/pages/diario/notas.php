<?php
$tid = gi('turma_id');
$t = turma_ou_404($tid);
$title = 'Notas · ' . $t['nome'];
$anoRow = one('SELECT * FROM anos_letivos WHERE id = ?', [$t['ano_letivo_id']]);
$comps = componentes_permitidos($tid);
if (!$comps) { echo diario_tabs($t, 'notas') . '<div class="card empty">Nenhum componente curricular disponível para você nesta turma. Verifique a matriz curricular das etapas.</div>'; return; }
$compId = gi('componente_id') ?: (int) $comps[0]['id'];
if (!in_array($compId, array_map('intval', array_column($comps, 'id')), true)) forbidden();
$pers = periodos((int) $t['ano_letivo_id']);
$max = (float) $anoRow['nota_maxima'];
$alunos = array_values(array_filter(turma_alunos($tid), fn($a) => $a['avaliacao'] === 'nota' && !in_array($a['situacao'], ['cancelado', 'remanejado'], true)
    && val('SELECT 1 FROM etapa_componentes WHERE etapa_id = ? AND componente_id = ?', [$a['etapa_id'], $compId])));
$editPer = [];
foreach ($pers as $per) $editPer[$per['id']] = pode_editar($per, $anoRow);
$editRf = pode_editar(end($pers) ?: null, $anoRow);

if (is_post()) {
    $erros = 0;
    $norm = function ($v) use ($max, &$erros) { $n = parse_num($v); if ($n !== null && ($n < 0 || $n > $max)) { $erros++; return null; } return $n; };
    db()->beginTransaction();
    $st = db()->prepare('INSERT INTO notas (matricula_id, componente_id, periodo_id, nota, recuperacao) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE nota = VALUES(nota), recuperacao = VALUES(recuperacao)');
    $strf = db()->prepare('INSERT INTO recuperacoes_finais (matricula_id, componente_id, nota) VALUES (?,?,?) ON DUPLICATE KEY UPDATE nota = VALUES(nota)');
    foreach ($alunos as $a) {
        if ($a['situacao'] !== 'cursando' && user()['perfil'] === 'professor') continue;
        $mid = $a['id'];
        foreach ($pers as $per) {
            if (!$editPer[$per['id']] || !isset($_POST['n'][$mid][$per['id']])) continue;
            $st->execute([$mid, $compId, $per['id'], $norm($_POST['n'][$mid][$per['id']]), $norm($_POST['rc'][$mid][$per['id']] ?? '')]);
        }
        if ($editRf && isset($_POST['rf'][$mid])) $strf->execute([$mid, $compId, $norm($_POST['rf'][$mid])]);
    }
    db()->commit();
    audit('notas_salvar', "turma $tid comp $compId");
    flash($erros ? 'warn' : 'success', $erros ? "Notas salvas. $erros valor(es) fora do intervalo 0–" . fnum($max) . ' foram ignorados.' : 'Notas salvas.');
    redirect('diario/notas', ['turma_id' => $tid, 'componente_id' => $compId]);
}

$notas = [];
foreach (all('SELECT * FROM notas WHERE componente_id = ? AND matricula_id IN (' . in_list(array_column($alunos, 'id')) . ')', [$compId]) as $n) $notas[$n['matricula_id']][$n['periodo_id']] = $n;
$cons = consolidar($alunos, array_filter($comps, fn($c) => $c['id'] == $compId), $pers, $anoRow);
$freqComp = frequencia_resumo(array_column($alunos, 'id'), $compId);
echo diario_tabs($t, 'notas');
?>
<form class="filters" method="get"><input type="hidden" name="r" value="diario/notas"><input type="hidden" name="turma_id" value="<?= $tid ?>">
  <label>Componente<select name="componente_id" data-autosubmit><?= options($comps, $compId) ?></select></label></form>
<p class="muted" data-max="<?= $max ?>" data-media="<?= (float) $anoRow['media_aprovacao'] ?>">Notas de 0 a <?= fnum($max) ?>. Média para aprovação: <b><?= fnum($anoRow['media_aprovacao']) ?></b>. "Rec." é a recuperação do período (substitui a nota se maior). "RF" é a recuperação final.</p>
<form method="post" data-dirty>
  <?= csrf_field() ?>
  <div class="table-wrap compact"><table>
    <thead><tr><th>Aluno</th><?php foreach ($pers as $per): ?><th class="num"><?= e($per['nome']) ?><?= $per['fechado'] ? ' 🔒' : '' ?></th><th class="num">Rec.</th><?php endforeach ?><th class="num">Média</th><th class="num">RF</th><th class="num">Final</th><th class="num">Faltas</th></tr></thead>
    <tbody>
    <?php foreach ($alunos as $a): $mid = $a['id']; $c = $cons[$mid]['comps'][$compId] ?? []; $ro = $a['situacao'] !== 'cursando' && user()['perfil'] === 'professor'; ?>
      <tr><td><?= e(nome_aluno($a)) ?> <?= $a['situacao'] !== 'cursando' ? situacao_badge($a['situacao']) : '' ?><span class="etapa-tag"><?= e($a['etapa_nome']) ?></span></td>
      <?php foreach ($pers as $per): $n = $notas[$mid][$per['id']] ?? []; $dis = (!$editPer[$per['id']] || $ro) ? ' disabled' : ''; ?>
        <td class="num"><input class="nota" name="n[<?= $mid ?>][<?= $per['id'] ?>]" value="<?= fnum($n['nota'] ?? null) ?>" inputmode="decimal"<?= $dis ?>></td>
        <td class="num"><input class="nota" name="rc[<?= $mid ?>][<?= $per['id'] ?>]" value="<?= fnum($n['recuperacao'] ?? null) ?>" inputmode="decimal"<?= $dis ?>></td>
      <?php endforeach ?>
        <td class="num"><b><?= fnum($c['media'] ?? null) ?></b></td>
        <td class="num"><input class="nota" name="rf[<?= $mid ?>]" value="<?= fnum($c['rf'] ?? null) ?>" inputmode="decimal"<?= (!$editRf || $ro) ? ' disabled' : '' ?>></td>
        <td class="num"><?php $f = $c['final'] ?? null; echo $f === null ? '' : '<span class="badge ' . ($f >= $anoRow['media_aprovacao'] ? 'ok' : 'bad') . '">' . fnum($f) . '</span>' ?></td>
        <td class="num"><?= $freqComp[$mid]['faltas'] ?? 0 ?></td></tr>
    <?php endforeach ?>
    <?php if (!$alunos): ?><tr><td colspan="<?= 5 + 2 * count($pers) ?>" class="empty">Nenhum aluno avaliado por nota neste componente.</td></tr><?php endif ?>
    </tbody></table></div>
  <div class="form-foot"><button class="btn primary">Salvar notas</button></div>
</form>
