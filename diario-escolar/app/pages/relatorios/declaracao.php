<?php
require_role('admin', 'gestor');
$tpl = 'print';
$m = one('SELECT m.*, a.*, m.id mid, e.nome etapa_nome, al.ano FROM matriculas m JOIN alunos a ON a.id = m.aluno_id JOIN etapas e ON e.id = m.etapa_id JOIN anos_letivos al ON al.id = m.ano_letivo_id WHERE m.id = ?', [gi('id')]) ?? not_found();
$t = turma_ou_404((int) $m['turma_id'], true);
$cabEscola = one('SELECT * FROM escolas WHERE id = ?', [$t['escola_id']]);
$title = 'Declaração · ' . $m['nome'];
$freq = perc_freq(frequencia_resumo([$m['mid']])[$m['mid']] ?? null);
?>
<div class="doc"><?php require ROOT . '/app/views/_cab.php' ?>
<h2 style="margin-top:40px">Declaração de <?= $m['situacao'] === 'cursando' ? 'matrícula e frequência' : 'escolaridade' ?></h2>
<p class="text">Declaramos, para os devidos fins, que <b><?= e($m['nome']) ?></b>, nascido(a) em <?= fdate($m['data_nascimento']) ?>, filho(a) de <?= e($m['mae'] ?: '—') ?><?= $m['pai'] ? ' e ' . e($m['pai']) : '' ?>,
<?= $m['situacao'] === 'cursando' ? 'encontra-se regularmente matriculado(a)' : 'esteve matriculado(a)' ?> nesta unidade de ensino no ano letivo de <b><?= $m['ano'] ?></b>, na etapa <b><?= e($m['etapa_nome']) ?></b>, turma <b><?= e(turma_nome($t)) ?></b>, sob a matrícula nº <?= e($m['numero']) ?>,
<?= $m['situacao'] === 'cursando' ? ($freq !== null ? "com frequência de " . fnum($freq) . '% até a presente data.' : 'frequentando as aulas regularmente.') : 'com situação final: <b>' . e(SITUACOES[$m['situacao']]) . '</b>' . ($m['data_saida'] ? ' em ' . fdate($m['data_saida']) : '') . '.' ?></p>
<p class="text" style="text-align:right"><?= e(setting('municipio')) ?>, <?= date('d/m/Y') ?>.</p>
<div class="sign"><div>Secretário(a) escolar</div><div>Diretor(a)</div></div>
</div>
