<?php
require_role('admin', 'gestor');
$tpl = 'print';
$title = 'Quantitativo de matrículas';
$u = user();
$f = $u['perfil'] === 'gestor' ? ' AND t.escola_id = ' . (int) $u['escola_id'] : '';
$cabEscola = $u['perfil'] === 'gestor' ? one('SELECT * FROM escolas WHERE id = ?', [$u['escola_id']]) : null;
$etapas = all('SELECT * FROM etapas WHERE ativo = 1 ORDER BY ordem');
$dados = [];
foreach (all("SELECT t.escola_id, m.etapa_id, COUNT(*) n FROM matriculas m JOIN turmas t ON t.id = m.turma_id WHERE m.ano_letivo_id = ? AND m.situacao = 'cursando' $f GROUP BY t.escola_id, m.etapa_id", [ano_id()]) as $r) $dados[$r['escola_id']][$r['etapa_id']] = $r['n'];
$escolas = all('SELECT * FROM escolas WHERE ativo = 1' . ($u['perfil'] === 'gestor' ? ' AND id = ' . (int) $u['escola_id'] : '') . ' ORDER BY nome');
$perfil = one("SELECT COUNT(*) total, SUM(a.sexo='F') fem, SUM(a.sexo='M') masc, SUM(a.zona='rural') rural, SUM(a.nee) nee, SUM(a.transporte_escolar) transp, SUM(a.bolsa_familia) bolsa
    FROM matriculas m JOIN alunos a ON a.id = m.aluno_id JOIN turmas t ON t.id = m.turma_id WHERE m.ano_letivo_id = ? AND m.situacao = 'cursando' $f", [ano_id()]);
$tot = [];
?>
<div class="doc landscape"><?php require ROOT . '/app/views/_cab.php' ?>
<h2>Quantitativo de matrículas ativas · <?= ano()['ano'] ?></h2>
<table class="small"><thead><tr><th>Escola</th><?php foreach ($etapas as $e): ?><th class="c"><span class="v"><?= e($e['nome']) ?></span></th><?php endforeach ?><th class="c">Total</th></tr></thead><tbody>
<?php foreach ($escolas as $s): $sum = 0; ?><tr><td><?= e($s['nome']) ?></td><?php foreach ($etapas as $e): $n = (int) ($dados[$s['id']][$e['id']] ?? 0); $sum += $n; $tot[$e['id']] = ($tot[$e['id']] ?? 0) + $n; ?><td class="c"><?= $n ?: '' ?></td><?php endforeach ?><td class="c"><b><?= $sum ?></b></td></tr><?php endforeach ?>
<tr><th>Total</th><?php foreach ($etapas as $e): ?><th class="c"><?= $tot[$e['id']] ?? 0 ?></th><?php endforeach ?><th class="c"><?= array_sum($tot) ?></th></tr>
</tbody></table>
<table><thead><tr><th>Total</th><th>Feminino</th><th>Masculino</th><th>Zona rural</th><th>NEE</th><th>Transporte escolar</th><th>Bolsa Família</th></tr></thead>
<tbody><tr><?php foreach (['total', 'fem', 'masc', 'rural', 'nee', 'transp', 'bolsa'] as $k): ?><td class="c"><?= (int) $perfil[$k] ?></td><?php endforeach ?></tr></tbody></table>
<p>Emitido em <?= date('d/m/Y H:i') ?>.</p>
</div>
