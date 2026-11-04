<?php
require_role('admin', 'gestor');
$tpl = 'print';
$title = 'Alunos infrequentes';
$u = user();
$f = $u['perfil'] === 'gestor' ? ' AND t.escola_id = ' . (int) $u['escola_id'] : '';
$cabEscola = $u['perfil'] === 'gestor' ? one('SELECT * FROM escolas WHERE id = ?', [$u['escola_id']]) : null;
$min = (float) (ano()['frequencia_minima'] ?? 75);
$mats = all("SELECT m.id, a.nome, a.nome_social, a.responsavel, a.mae, a.telefone, t.nome turma, t.turno, s.nome escola FROM matriculas m JOIN alunos a ON a.id = m.aluno_id JOIN turmas t ON t.id = m.turma_id JOIN escolas s ON s.id = t.escola_id
    WHERE m.ano_letivo_id = ? AND m.situacao = 'cursando' $f ORDER BY s.nome, t.nome, a.nome", [ano_id()]);
$res = frequencia_resumo(array_column($mats, 'id'));
$lista = array_filter($mats, fn($m) => ($p = perc_freq($res[$m['id']] ?? null)) !== null && $p < $min);
?>
<div class="doc"><?php require ROOT . '/app/views/_cab.php' ?>
<h2>Alunos com frequência abaixo de <?= fnum($min, 0) ?>% · <?= ano()['ano'] ?></h2>
<table><thead><tr><th>Escola</th><th>Turma</th><th>Aluno</th><th>Responsável</th><th>Telefone</th><th class="c">Faltas</th><th class="c">Freq.</th></tr></thead><tbody>
<?php foreach ($lista as $m): $r = $res[$m['id']]; ?><tr><td><?= e($m['escola']) ?></td><td><?= e(turma_nome($m)) ?></td><td><?= e(nome_aluno($m)) ?></td><td><?= e($m['responsavel'] ?: $m['mae']) ?></td><td><?= e($m['telefone']) ?></td><td class="c"><?= $r['faltas'] ?>/<?= $r['aulas'] ?></td><td class="c bad"><?= fnum(perc_freq($r)) ?>%</td></tr><?php endforeach ?>
<?php if (!$lista): ?><tr><td colspan="7">Nenhum aluno abaixo da frequência mínima.</td></tr><?php endif ?>
</tbody></table></div>
