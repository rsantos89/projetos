<?php
$t = turma_ou_404(gi('turma_id'));
$tpl = 'print';
$title = 'Ata de resultados finais · ' . $t['nome'];
$cabEscola = one('SELECT * FROM escolas WHERE id = ?', [$t['escola_id']]);
$anoRow = one('SELECT * FROM anos_letivos WHERE id = ?', [$t['ano_letivo_id']]);
$pers = periodos((int) $anoRow['id']);
$comps = componentes_permitidos((int) $t['id']);
$alunos = array_values(array_filter(turma_alunos((int) $t['id']), fn($a) => !in_array($a['situacao'], ['cancelado', 'remanejado'], true)));
$cons = consolidar($alunos, $comps, $pers, $anoRow);
$cont = array_count_values(array_column($alunos, 'situacao'));
?>
<div class="doc landscape"><?php require ROOT . '/app/views/_cab.php' ?>
<h2>Ata de resultados finais · <?= $anoRow['ano'] ?></h2>
<p>Aos <?= date('d') ?> dias do mês de <?= ['', 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'][(int) date('m')] ?> de <?= date('Y') ?>, concluiu-se o processo de avaliação da turma <b><?= e(turma_nome($t)) ?></b> (<?= e(implode(' + ', array_column(turma_etapas((int) $t['id']), 'nome'))) ?>), com os seguintes resultados:</p>
<table class="small"><thead><tr><th class="c">Nº</th><th>Aluno</th><th>Etapa</th><?php foreach ($comps as $c): ?><th class="c"><span class="v"><?= e($c['nome']) ?></span></th><?php endforeach ?><th class="c">Freq. %</th><th>Resultado</th></tr></thead><tbody>
<?php foreach ($alunos as $i => $a): $r = $cons[$a['id']]; ?><tr><td class="c"><?= $i + 1 ?></td><td><?= e($a['nome']) ?></td><td><?= e($a['etapa_nome']) ?></td>
<?php foreach ($comps as $c): $x = $r['comps'][$c['id']] ?? null; ?><td class="c<?= $x && $x['final'] !== null && $x['final'] < $anoRow['media_aprovacao'] ? ' bad' : '' ?>"><?= $a['avaliacao'] === 'parecer' ? 'PD' : fnum($x['final'] ?? null) ?></td><?php endforeach ?>
<td class="c"><?= fnum($r['freq']) ?></td><td><?= e(SITUACOES[$a['situacao']]) ?></td></tr><?php endforeach ?>
</tbody></table>
<p><b>Resumo:</b> <?php foreach ($cont as $s => $n): ?><?= e(SITUACOES[$s]) ?>: <?= $n ?> &nbsp; <?php endforeach ?> · PD = parecer descritivo.</p>
<div class="sign"><div>Secretário(a) escolar</div><div>Diretor(a)</div></div>
</div>
