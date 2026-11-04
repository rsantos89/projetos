<?php
$t = turma_ou_404(gi('turma_id'));
$tpl = 'print';
$title = 'Lista de alunos · ' . $t['nome'];
$cabEscola = one('SELECT * FROM escolas WHERE id = ?', [$t['escola_id']]);
$alunos = all("SELECT m.*, a.*, m.id mid, e.nome etapa_nome FROM matriculas m JOIN alunos a ON a.id = m.aluno_id JOIN etapas e ON e.id = m.etapa_id WHERE m.turma_id = ? AND m.situacao = 'cursando' ORDER BY a.nome", [$t['id']]);
?>
<div class="doc"><?php require ROOT . '/app/views/_cab.php' ?>
<h2>Relação de alunos · <?= e((string) val('SELECT ano FROM anos_letivos WHERE id = ?', [$t['ano_letivo_id']])) ?></h2>
<div class="info"><span><b>Turma:</b> <?= e(turma_nome($t)) ?></span><span><b>Total:</b> <?= count($alunos) ?></span><span><b>Emitido em:</b> <?= date('d/m/Y') ?></span></div>
<table><thead><tr><th class="c">Nº</th><th>Aluno</th><th>Etapa</th><th class="c">Nasc.</th><th class="c">Sexo</th><th>Responsável</th><th>Telefone</th><th class="c">NEE</th><th class="c">Transp.</th></tr></thead><tbody>
<?php foreach ($alunos as $i => $a): ?><tr><td class="c"><?= $i + 1 ?></td><td><?= e(nome_aluno($a)) ?></td><td><?= e($a['etapa_nome']) ?></td><td class="c"><?= fdate($a['data_nascimento']) ?></td><td class="c"><?= e($a['sexo']) ?></td><td><?= e($a['responsavel'] ?: $a['mae']) ?></td><td><?= e($a['telefone']) ?></td><td class="c"><?= $a['nee'] ? 'S' : '' ?></td><td class="c"><?= $a['transporte_escolar'] ? 'S' : '' ?></td></tr><?php endforeach ?>
</tbody></table></div>
