<?php
require_role('admin');
$title = 'Escolas';
if (is_post() && p('excluir')) {
    try { q('DELETE FROM escolas WHERE id = ?', [(int) p('excluir')]); audit('escola_excluir', p('excluir')); flash('success', 'Escola excluída.'); }
    catch (PDOException $e) { flash('error', 'A escola possui turmas vinculadas. Desative-a em vez de excluir.'); }
    redirect('escolas');
}
$escolas = all('SELECT s.*, (SELECT COUNT(*) FROM turmas t WHERE t.escola_id = s.id AND t.ano_letivo_id = ?) turmas FROM escolas s ORDER BY s.ativo DESC, s.nome', [ano_id()]);
?>
<div class="page-head"><h1>Escolas</h1><a class="btn primary" href="<?= url('escolas/form') ?>">+ Nova escola</a></div>
<div class="table-wrap"><table>
<thead><tr><th>Nome</th><th>INEP</th><th>Zona</th><th>Direção</th><th class="num">Turmas</th><th></th></tr></thead>
<tbody>
<?php foreach ($escolas as $s): ?>
<tr><td><?= e($s['nome']) ?> <?= $s['ativo'] ? '' : '<span class="badge muted">inativa</span>' ?></td><td><?= e($s['inep']) ?></td><td><?= ucfirst(e($s['zona'])) ?></td><td><?= e($s['diretor']) ?></td><td class="num"><?= $s['turmas'] ?></td>
<td class="actions"><a class="btn sm" href="<?= url('escolas/form', ['id' => $s['id']]) ?>">Editar</a>
<form method="post"><?= csrf_field() ?><button class="btn sm danger" name="excluir" value="<?= $s['id'] ?>" data-confirm="Excluir esta escola?">Excluir</button></form></td></tr>
<?php endforeach; if (!$escolas): ?><tr><td colspan="6" class="empty">Nenhuma escola cadastrada.</td></tr><?php endif ?>
</tbody></table></div>
