<?php
require_role('admin', 'gestor');
$id = gi('id');
$a = one('SELECT * FROM alunos WHERE id = ?', [$id]);
if (!$a) not_found();
$title = nome_aluno($a);
$mats = all('SELECT m.*, t.nome turma, t.turno, s.nome escola, e.nome etapa, al.ano FROM matriculas m JOIN turmas t ON t.id = m.turma_id JOIN escolas s ON s.id = t.escola_id
    JOIN etapas e ON e.id = m.etapa_id JOIN anos_letivos al ON al.id = m.ano_letivo_id WHERE m.aluno_id = ? ORDER BY al.ano DESC, m.id DESC', [$id]);
$ocs = all('SELECT o.*, u.nome autor FROM ocorrencias o JOIN matriculas m ON m.id = o.matricula_id LEFT JOIN usuarios u ON u.id = o.usuario_id WHERE m.aluno_id = ? ORDER BY o.data DESC', [$id]);
$atual = null;
foreach ($mats as $m) if ($m['situacao'] === 'cursando' && $m['ano_letivo_id'] == ano_id()) $atual = $m;
$kv = ['Código' => $a['codigo'], 'Nascimento' => fdate($a['data_nascimento']) . ($a['data_nascimento'] ? ' (' . idade($a['data_nascimento']) . ' anos)' : ''), 'Sexo' => ['F' => 'Feminino', 'M' => 'Masculino'][$a['sexo']] ?? '',
    'Cor/raça' => $a['cor_raca'], 'CPF' => $a['cpf'], 'NIS' => $a['nis'], 'Certidão' => $a['certidao'], 'Cartão SUS' => $a['cartao_sus'], 'Mãe' => $a['mae'], 'Pai' => $a['pai'],
    'Responsável' => trim($a['responsavel'] . ($a['responsavel_parentesco'] ? " ({$a['responsavel_parentesco']})" : '')), 'Telefones' => trim($a['telefone'] . ' ' . $a['telefone2']),
    'Endereço' => trim($a['endereco'] . ', ' . $a['bairro'] . ' - ' . $a['cidade'], ' ,-'), 'Zona' => ucfirst($a['zona']),
    'Transporte escolar' => $a['transporte_escolar'] ? 'Sim' : 'Não', 'Bolsa Família' => $a['bolsa_familia'] ? 'Sim' : 'Não', 'NEE' => $a['nee'] ? ('Sim' . ($a['deficiencia'] ? ' - ' . $a['deficiencia'] : '')) : 'Não', 'Saúde' => $a['saude']];
?>
<div class="page-head"><h1><?= e(nome_aluno($a)) ?></h1>
  <div class="actions">
    <?php if (!$atual): ?><a class="btn primary" href="<?= url('matriculas/nova', ['aluno_id' => $id]) ?>">Matricular em <?= ano()['ano'] ?? '' ?></a><?php else: ?>
      <a class="btn" href="<?= url('relatorios/declaracao', ['id' => $atual['id']]) ?>">Declaração</a><a class="btn" href="<?= url('relatorios/boletim', ['matricula_id' => $atual['id']]) ?>">Boletim</a><?php endif ?>
    <a class="btn" href="<?= url('relatorios/ficha', ['id' => $id]) ?>">Ficha individual</a>
    <a class="btn" href="<?= url('alunos/form', ['id' => $id]) ?>">Editar</a>
  </div>
  <?php if ($a['nome_social']): ?><span class="sub">Nome civil: <?= e($a['nome']) ?></span><?php endif ?>
</div>
<div class="card"><dl class="kv"><?php foreach ($kv as $k => $v) if ($v !== '' && $v !== null): ?><div><dt><?= e($k) ?></dt><dd><?= e($v) ?></dd></div><?php endif ?></dl>
<?php if ($a['observacoes']): ?><p><b>Observações:</b> <?= nl2br(e($a['observacoes'])) ?></p><?php endif ?></div>
<h2>Histórico de matrículas</h2>
<div class="table-wrap"><table><thead><tr><th>Ano</th><th>Escola</th><th>Turma / etapa</th><th>Matrícula</th><th>Situação</th><th></th></tr></thead><tbody>
<?php foreach ($mats as $m): ?><tr><td><?= $m['ano'] ?></td><td><?= e($m['escola']) ?></td><td><?= e($m['etapa']) ?> · <a href="<?= url('turmas/ver', ['id' => $m['turma_id']]) ?>"><?= e($m['turma']) ?></a></td>
<td><?= e($m['numero']) ?><span class="etapa-tag"><?= fdate($m['data_matricula']) ?></span></td><td><?= situacao_badge($m['situacao']) ?><?= $m['observacao'] ? '<span class="etapa-tag">' . e($m['observacao']) . '</span>' : '' ?></td>
<td class="actions"><a class="btn sm" href="<?= url('matriculas/mover', ['id' => $m['id']]) ?>">Movimentar</a><a class="btn sm" href="<?= url('relatorios/boletim', ['matricula_id' => $m['id']]) ?>">Boletim</a></td></tr><?php endforeach ?>
<?php if (!$mats): ?><tr><td colspan="6" class="empty">Sem matrículas.</td></tr><?php endif ?>
</tbody></table></div>
<?php if ($ocs): ?><h2>Ocorrências</h2>
<div class="table-wrap"><table><thead><tr><th>Data</th><th>Tipo</th><th>Descrição</th><th>Registrado por</th></tr></thead><tbody>
<?php foreach ($ocs as $o): ?><tr><td><?= fdate($o['data']) ?></td><td><?= e($o['tipo']) ?></td><td><?= nl2br(e($o['descricao'])) ?></td><td><?= e($o['autor']) ?></td></tr><?php endforeach ?></tbody></table></div><?php endif;
