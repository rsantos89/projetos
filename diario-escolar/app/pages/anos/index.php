<?php
require_role('admin');
$title = 'Anos letivos';
$anos = all('SELECT a.*, (SELECT COUNT(*) FROM periodos p WHERE p.ano_letivo_id = a.id) nper, (SELECT COUNT(*) FROM turmas t WHERE t.ano_letivo_id = a.id) nturmas FROM anos_letivos a ORDER BY ano DESC');
?>
<div class="page-head"><h1>Anos letivos</h1><a class="btn primary" href="<?= url('anos/form') ?>">+ Novo ano letivo</a></div>
<div class="table-wrap"><table><thead><tr><th>Ano</th><th>Períodos</th><th>Média</th><th>Freq. mínima</th><th>Dias letivos</th><th class="num">Turmas</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($anos as $a): ?><tr><td><b><?= $a['ano'] ?></b></td><td><?= $a['nper'] ?> <?= e($a['tipo_periodo']) ?>(s)</td><td><?= fnum($a['media_aprovacao']) ?> / <?= fnum($a['nota_maxima']) ?></td><td><?= $a['frequencia_minima'] ?>%</td><td><?= $a['dias_letivos'] ?></td><td class="num"><?= $a['nturmas'] ?></td>
<td><span class="badge <?= $a['status'] === 'aberto' ? 'ok' : 'muted' ?>"><?= $a['status'] ?></span></td><td><a class="btn sm" href="<?= url('anos/form', ['id' => $a['id']]) ?>">Editar</a></td></tr><?php endforeach ?>
</tbody></table></div>
