<?php
$t = turma_ou_404(gi('turma_id'));
$tpl = 'print';
$cabEscola = one('SELECT * FROM escolas WHERE id = ?', [$t['escola_id']]);
$mes = preg_match('/^\d{4}-\d{2}$/', (string) g('mes')) ? g('mes') : date('Y-m');
$ini = "$mes-01";
$fim = date('Y-m-t', strtotime($ini));
$cf = (string) g('componente_id', '');
$comps = componentes_permitidos((int) $t['id']);
$where = 'turma_id = ? AND data BETWEEN ? AND ?';
$p = [$t['id'], $ini, $fim];
if ($cf === '') $where .= ' AND componente_id IS NULL';
elseif ($cf !== 'todos') { if (!in_array((int) $cf, array_map('intval', array_column($comps, 'id')), true)) forbidden(); $where .= ' AND componente_id = ?'; $p[] = (int) $cf; }
else $where .= ' AND (componente_id IS NULL OR componente_id IN (' . in_list(array_column($comps, 'id')) . '))';
$aulas = all("SELECT a.*, c.abreviacao, c.nome comp FROM aulas a LEFT JOIN componentes c ON c.id = a.componente_id WHERE $where ORDER BY data, c.nome", $p);
$title = 'Diário de classe · ' . $t['nome'] . ' · ' . $mes;
$freq = [];
foreach (all('SELECT f.* FROM frequencias f WHERE f.aula_id IN (' . in_list(array_column($aulas, 'id')) . ')') as $f) $freq[$f['matricula_id']][$f['aula_id']] = $f;
$alunos = array_filter(turma_alunos((int) $t['id']), fn($a) => isset($freq[$a['id']]) || $a['situacao'] === 'cursando');
$compNome = $cf === '' ? 'Frequência diária' : ($cf === 'todos' ? 'Todos os componentes' : (one('SELECT nome FROM componentes WHERE id = ?', [(int) $cf])['nome'] ?? ''));
$meses = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
$profs = implode(', ', array_column(all('SELECT DISTINCT u.nome FROM lotacoes l JOIN usuarios u ON u.id = l.usuario_id WHERE l.turma_id = ?', [$t['id']]), 'nome'));
?>
<div class="doc landscape"><?php require ROOT . '/app/views/_cab.php' ?>
<h2>Diário de classe · <?= $meses[(int) substr($mes, 5)] ?>/<?= substr($mes, 0, 4) ?></h2>
<div class="info"><span><b>Turma:</b> <?= e(turma_nome($t)) ?></span><span><b>Etapa(s):</b> <?= e(implode(' + ', array_column(turma_etapas((int) $t['id']), 'nome'))) ?></span><span><b>Componente:</b> <?= e($compNome) ?></span><span><b>Professor(es):</b> <?= e($profs) ?></span></div>
<table class="small"><thead><tr><th class="c">Nº</th><th>Aluno</th><?php foreach ($aulas as $a): ?><th class="c"><span class="v"><?= date('d/m', strtotime($a['data'])) ?><?= $cf === 'todos' && $a['abreviacao'] ? ' ' . e($a['abreviacao']) : '' ?></span></th><?php endforeach ?><th class="c">F</th><th class="c">FJ</th></tr></thead><tbody>
<?php $i = 0; foreach ($alunos as $al): $tf = $tj = 0; ?><tr><td class="c"><?= ++$i ?></td><td><?= e(nome_aluno($al)) ?><?= $al['situacao'] !== 'cursando' ? ' (' . e(SITUACOES[$al['situacao']]) . ')' : '' ?></td>
<?php foreach ($aulas as $a): $f = $freq[$al['id']][$a['id']] ?? null;
    if (!$f) { echo '<td class="c">-</td>'; continue; }
    if ($f['faltas'] && $f['justificada']) { $tj += $f['faltas']; echo '<td class="c">FJ</td>'; }
    elseif ($f['faltas']) { $tf += $f['faltas']; echo '<td class="c bad">F' . ($f['faltas'] > 1 ? $f['faltas'] : '') . '</td>'; }
    else echo '<td class="c">•</td>';
endforeach ?><td class="c"><b><?= $tf ?></b></td><td class="c"><?= $tj ?></td></tr><?php endforeach ?>
</tbody></table>
<h2>Registro de conteúdos</h2>
<table><thead><tr><th class="c" style="width:70px">Data</th><?php if ($cf === 'todos'): ?><th>Componente</th><?php endif ?><th class="c">Aulas</th><th>Conteúdo / objetos de conhecimento</th></tr></thead><tbody>
<?php foreach ($aulas as $a): ?><tr><td class="c"><?= fdate($a['data']) ?></td><?php if ($cf === 'todos'): ?><td><?= e($a['comp'] ?? 'Diária') ?></td><?php endif ?><td class="c"><?= $a['quantidade'] ?></td><td><?= nl2br(e($a['conteudo'])) ?></td></tr><?php endforeach ?>
<?php if (!$aulas): ?><tr><td colspan="4">Nenhuma aula registrada no mês.</td></tr><?php endif ?>
</tbody></table>
<div class="sign"><div>Professor(a)</div><div>Coordenação pedagógica</div><div>Direção</div></div>
</div>
