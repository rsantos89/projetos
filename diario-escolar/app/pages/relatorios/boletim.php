<?php
$tpl = 'print';
if (gi('matricula_id')) {
    $m = one('SELECT turma_id FROM matriculas WHERE id = ?', [gi('matricula_id')]) ?? not_found();
    $t = turma_ou_404((int) $m['turma_id']);
    $mats = array_filter(turma_alunos((int) $t['id']), fn($a) => $a['id'] == gi('matricula_id'));
} else {
    $t = turma_ou_404(gi('turma_id'));
    $mats = array_filter(turma_alunos((int) $t['id']), fn($a) => in_array($a['situacao'], ['cursando', 'aprovado', 'reprovado', 'concluido'], true));
}
$title = 'Boletim · ' . $t['nome'];
$cabEscola = one('SELECT * FROM escolas WHERE id = ?', [$t['escola_id']]);
$anoRow = one('SELECT * FROM anos_letivos WHERE id = ?', [$t['ano_letivo_id']]);
$pers = periodos((int) $anoRow['id']);
$comps = turma_componentes((int) $t['id']);
$cons = consolidar(array_values($mats), $comps, $pers, $anoRow);
$pareceres = [];
foreach (all('SELECT p.*, pe.nome FROM pareceres p JOIN periodos pe ON pe.id = p.periodo_id WHERE p.matricula_id IN (' . in_list(array_column($mats, 'id')) . ') ORDER BY pe.numero') as $p) $pareceres[$p['matricula_id']][] = $p;
foreach ($mats as $m):
    $r = $cons[$m['id']];
    $ecomp = array_column(all('SELECT componente_id FROM etapa_componentes WHERE etapa_id = ?', [$m['etapa_id']]), 'componente_id');
    $freqPer = [];
    foreach ($pers as $per) $freqPer[$per['id']] = frequencia_resumo([$m['id']], null, $per)[$m['id']] ?? null; ?>
<div class="doc"><?php require ROOT . '/app/views/_cab.php' ?>
<h2><?= $m['avaliacao'] === 'parecer' ? 'Relatório de desenvolvimento' : 'Boletim escolar' ?> · <?= $anoRow['ano'] ?></h2>
<div class="info"><span><b>Aluno(a):</b> <?= e(nome_aluno($m)) ?></span><span><b>Matrícula:</b> <?= e($m['numero']) ?></span><span><b>Turma:</b> <?= e(turma_nome($t)) ?></span><span><b>Etapa:</b> <?= e($m['etapa_nome']) ?></span><span><b>Situação:</b> <?= e(SITUACOES[$m['situacao']]) ?></span></div>
<?php if ($m['avaliacao'] === 'nota'): ?>
<table><thead><tr><th>Componente</th><?php foreach ($pers as $per): ?><th class="c"><?= e($per['nome']) ?></th><?php endforeach ?><th class="c">Média</th><th class="c">Rec. final</th><th class="c">Média final</th><th class="c">Faltas</th></tr></thead><tbody>
<?php foreach ($comps as $c): if (!in_array($c['id'], $ecomp)) continue; $x = $r['comps'][$c['id']]; ?>
<tr><td><?= e($c['nome']) ?></td><?php foreach ($pers as $per): $v = $x['p'][$per['id']]; ?><td class="c<?= $v !== null && $v < $anoRow['media_aprovacao'] ? ' bad' : '' ?>"><?= fnum($v) ?></td><?php endforeach ?>
<td class="c"><?= fnum($x['media']) ?></td><td class="c"><?= fnum($x['rf']) ?></td><td class="c<?= $x['final'] !== null && $x['final'] < $anoRow['media_aprovacao'] ? ' bad' : '' ?>"><b><?= fnum($x['final']) ?></b></td><td class="c"><?= $x['faltas'] ?></td></tr>
<?php endforeach ?></tbody></table>
<?php endif ?>
<table><thead><tr><th>Frequência</th><?php foreach ($pers as $per): ?><th class="c"><?= e($per['nome']) ?></th><?php endforeach ?><th class="c">Total</th></tr></thead><tbody>
<tr><td>Faltas / aulas</td><?php foreach ($pers as $per): $f = $freqPer[$per['id']]; ?><td class="c"><?= $f ? $f['faltas'] . ' / ' . $f['aulas'] : '-' ?></td><?php endforeach ?><td class="c"><?= $r['faltas'] ?> / <?= $r['aulas'] ?></td></tr>
<tr><td>% de frequência</td><?php foreach ($pers as $per): ?><td class="c"><?= fnum(perc_freq($freqPer[$per['id']])) ?></td><?php endforeach ?><td class="c"><b><?= fnum($r['freq']) ?>%</b></td></tr></tbody></table>
<?php foreach ($pareceres[$m['id']] ?? [] as $p) if ($p['texto']): ?><p><b><?= e($p['nome']) ?>:</b><br><?= nl2br(e($p['texto'])) ?></p><?php endif ?>
<div class="sign"><div>Direção / Secretaria escolar</div><div>Responsável</div></div>
</div>
<?php endforeach;
if (!$mats) echo '<p>Nenhum aluno.</p>';
