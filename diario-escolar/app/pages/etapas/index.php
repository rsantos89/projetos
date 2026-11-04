<?php
require_role('admin');
$title = 'Etapas / séries';
$etapas = all('SELECT e.*, (SELECT GROUP_CONCAT(COALESCE(c.abreviacao, c.nome) ORDER BY c.nome SEPARATOR ", ") FROM etapa_componentes ec JOIN componentes c ON c.id = ec.componente_id WHERE ec.etapa_id = e.id) comps FROM etapas e ORDER BY e.ordem');
?>
<div class="page-head"><h1>Etapas / séries</h1><a class="btn primary" href="<?= url('etapas/form') ?>">+ Nova etapa</a>
  <span class="sub">A ordem define a progressão na rematrícula (ex.: 1º Ano → 2º Ano). Etapas com avaliação por parecer (Educação Infantil) não retêm alunos.</span></div>
<div class="table-wrap"><table><thead><tr><th>Ordem</th><th>Etapa</th><th>Nível</th><th>Avaliação</th><th>Componentes</th><th></th></tr></thead><tbody>
<?php foreach ($etapas as $e): ?><tr><td><?= $e['ordem'] ?></td><td><?= e($e['nome']) ?> <?= $e['ativo'] ? '' : '<span class="badge muted">inativa</span>' ?></td><td><?= e(NIVEIS[$e['nivel']]) ?></td>
<td><?= $e['avaliacao'] === 'nota' ? 'Nota' : 'Parecer descritivo' ?></td><td><small><?= e($e['comps'] ?: '—') ?></small></td><td><a class="btn sm" href="<?= url('etapas/form', ['id' => $e['id']]) ?>">Editar</a></td></tr><?php endforeach ?>
</tbody></table></div>
