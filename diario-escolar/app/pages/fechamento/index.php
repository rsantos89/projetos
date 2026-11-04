<?php
$tid = gi('turma_id');
$t = turma_ou_404($tid);
$title = 'Fechamento · ' . $t['nome'];
$anoRow = one('SELECT * FROM anos_letivos WHERE id = ?', [$t['ano_letivo_id']]);
$pers = periodos((int) $t['ano_letivo_id']);
$comps = componentes_permitidos($tid);
$alunos = array_values(array_filter(turma_alunos($tid), fn($a) => in_array($a['situacao'], ['cursando', 'aprovado', 'reprovado', 'concluido'], true)));
$cons = consolidar($alunos, $comps, $pers, $anoRow);
$gestao = is_role('admin', 'gestor');

if (is_post()) {
    if (!$gestao || ($anoRow['status'] === 'encerrado' && !is_role('admin'))) forbidden();
    $n = 0;
    foreach ($alunos as $a) {
        $sit = (string) ($_POST['sit'][$a['id']] ?? '');
        if (!in_array($sit, ['cursando', 'aprovado', 'reprovado', 'concluido'], true) || $sit === $a['situacao']) continue;
        update('matriculas', ['situacao' => $sit], 'id = ?', [$a['id']]);
        $n++;
    }
    audit('fechamento', "turma $tid: $n alteradas");
    flash('success', "Resultado final aplicado ($n matrícula(s) atualizada(s)).");
    redirect('fechamento', ['turma_id' => $tid]);
}
echo diario_tabs($t, 'fechamento');
$pend = count(array_filter($cons, fn($c) => $c['situacao'] === null));
?>
<p class="muted">Média mínima <b><?= fnum($anoRow['media_aprovacao']) ?></b> por componente e frequência mínima de <b><?= $anoRow['frequencia_minima'] ?>%</b> sobre o total de aulas (LDB, art. 24). Educação Infantil não retém (LDB, art. 31).</p>
<?php if ($pend): ?><div class="alert warn"><?= $pend ?> aluno(s) com notas pendentes em algum período.</div><?php endif ?>
<form method="post">
  <?= csrf_field() ?>
  <div class="table-wrap compact"><table>
    <thead><tr><th>Aluno</th><?php foreach ($comps as $c): ?><th class="num" title="<?= e($c['nome']) ?>"><?= e($c['abreviacao'] ?: $c['nome']) ?></th><?php endforeach ?><th class="num">Freq.</th><th>Calculado</th><th>Resultado</th></tr></thead>
    <tbody>
    <?php foreach ($alunos as $a): $r = $cons[$a['id']]; ?>
      <tr><td><?= e(nome_aluno($a)) ?><span class="etapa-tag"><?= e($a['etapa_nome']) ?></span></td>
      <?php foreach ($comps as $c): $x = $r['comps'][$c['id']] ?? null; ?>
        <td class="num"><?php if ($a['avaliacao'] === 'parecer'): ?><span class="muted">—</span><?php elseif (!$x || $x['final'] === null): ?><span class="muted">·</span><?php else: ?><span class="<?= $x['final'] < $anoRow['media_aprovacao'] ? 'badge bad' : '' ?>"><?= fnum($x['final']) ?></span><?php endif ?></td>
      <?php endforeach ?>
        <td class="num"><?= $r['freq'] === null ? '—' : '<span class="badge ' . ($r['freq'] < $anoRow['frequencia_minima'] ? 'bad' : 'ok') . '">' . fnum($r['freq']) . '%</span>' ?></td>
        <td><?= $r['situacao'] ? situacao_badge($r['situacao']) : '<span class="badge muted">Pendente</span>' ?></td>
        <td><?php if ($gestao): ?><select name="sit[<?= $a['id'] ?>]" style="min-width:130px"><?= options(['cursando' => 'Cursando', 'aprovado' => 'Aprovado', 'reprovado' => 'Reprovado', 'concluido' => 'Concluído (Ed. Infantil)'], $a['situacao'] === 'cursando' && $r['situacao'] ? $r['situacao'] : $a['situacao']) ?></select><?php else: ?><?= situacao_badge($a['situacao']) ?><?php endif ?></td></tr>
    <?php endforeach ?>
    <?php if (!$alunos): ?><tr><td colspan="<?= 4 + count($comps) ?>" class="empty">Nenhum aluno.</td></tr><?php endif ?>
    </tbody></table></div>
  <?php if ($gestao): ?><div class="form-foot"><a class="btn" href="<?= url('relatorios/ata', ['turma_id' => $tid]) ?>">Ata de resultados</a><button class="btn primary" data-confirm="Aplicar o resultado final às matrículas?">Aplicar resultado final</button></div><?php endif ?>
</form>
