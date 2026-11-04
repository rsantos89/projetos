<?php
require_role('admin', 'gestor');
$tpl = 'print';
$a = one('SELECT * FROM alunos WHERE id = ?', [gi('id')]) ?? not_found();
$title = 'Ficha individual · ' . $a['nome'];
$mats = all('SELECT m.*, t.nome turma, t.turno, s.nome escola, e.nome etapa, al.ano FROM matriculas m JOIN turmas t ON t.id = m.turma_id JOIN escolas s ON s.id = t.escola_id JOIN etapas e ON e.id = m.etapa_id JOIN anos_letivos al ON al.id = m.ano_letivo_id WHERE m.aluno_id = ? ORDER BY al.ano, m.id', [$a['id']]);
$cabEscola = null;
$rows = ['Nome' => $a['nome'], 'Nome social' => $a['nome_social'], 'Código' => $a['codigo'], 'Nascimento' => fdate($a['data_nascimento']), 'Sexo' => $a['sexo'], 'Cor/raça' => $a['cor_raca'], 'Naturalidade' => $a['naturalidade'],
    'CPF' => $a['cpf'], 'NIS' => $a['nis'], 'RG' => $a['rg'], 'Certidão' => $a['certidao'], 'Cartão SUS' => $a['cartao_sus'], 'ID INEP' => $a['inep_aluno'], 'Mãe' => $a['mae'], 'Pai' => $a['pai'],
    'Responsável' => $a['responsavel'] . ($a['responsavel_parentesco'] ? " ({$a['responsavel_parentesco']})" : ''), 'CPF responsável' => $a['responsavel_cpf'], 'Telefones' => trim($a['telefone'] . ' ' . $a['telefone2']), 'E-mail' => $a['email'],
    'Endereço' => $a['endereco'], 'Bairro' => $a['bairro'], 'Cidade' => $a['cidade'], 'CEP' => $a['cep'], 'Zona' => $a['zona'], 'NEE' => $a['nee'] ? 'Sim - ' . $a['deficiencia'] : 'Não', 'Transporte escolar' => $a['transporte_escolar'] ? 'Sim' : 'Não',
    'Bolsa Família' => $a['bolsa_familia'] ? 'Sim' : 'Não', 'Saúde' => $a['saude'], 'Uso de imagem' => $a['autoriza_imagem'] ? 'Autorizado' : 'Não autorizado', 'Observações' => $a['observacoes']];
?>
<div class="doc"><?php require ROOT . '/app/views/_cab.php' ?>
<h2>Ficha individual do aluno</h2>
<table><tbody><?php foreach (array_chunk($rows, 2, true) as $par): ?><tr><?php foreach ($par as $k => $v): ?><th style="width:16%"><?= e($k) ?></th><td><?= e($v) ?></td><?php endforeach ?></tr><?php endforeach ?></tbody></table>
<h2>Vida escolar na rede</h2>
<table><thead><tr><th>Ano</th><th>Escola</th><th>Etapa</th><th>Turma</th><th>Matrícula</th><th>Situação</th></tr></thead><tbody>
<?php foreach ($mats as $m): ?><tr><td><?= $m['ano'] ?></td><td><?= e($m['escola']) ?></td><td><?= e($m['etapa']) ?></td><td><?= e(turma_nome($m)) ?></td><td><?= fdate($m['data_matricula']) ?></td><td><?= e(SITUACOES[$m['situacao']]) ?><?= $m['data_saida'] ? ' em ' . fdate($m['data_saida']) : '' ?></td></tr><?php endforeach ?>
</tbody></table>
<div class="sign"><div>Secretário(a) escolar</div></div>
</div>
