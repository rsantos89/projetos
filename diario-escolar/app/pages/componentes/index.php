<?php
require_role('admin');
$title = 'Componentes curriculares';
if (is_post()) {
    if (p('excluir')) {
        try { q('DELETE FROM componentes WHERE id = ?', [(int) p('excluir')]); flash('success', 'Componente excluído.'); }
        catch (PDOException $e) { flash('error', 'Componente já possui registros (aulas/notas). Desative-o.'); }
    } else {
        $d = ['nome' => p('nome'), 'abreviacao' => nn(p('abreviacao')), 'ativo' => p('ativo') ? 1 : 0];
        if ($d['nome'] !== '') {
            if (p('id')) update('componentes', $d, 'id = ?', [(int) p('id')]); else insert('componentes', $d);
            flash('success', 'Componente salvo.');
        }
    }
    redirect('componentes');
}
$comps = all('SELECT c.*, (SELECT COUNT(*) FROM etapa_componentes ec WHERE ec.componente_id = c.id) etapas FROM componentes c ORDER BY c.ativo DESC, c.nome');
$ed = gi('id') ? one('SELECT * FROM componentes WHERE id = ?', [gi('id')]) : null;
?>
<div class="page-head"><h1>Componentes curriculares</h1><span class="sub">Vincule os componentes às etapas em <a href="<?= url('etapas') ?>">Etapas / séries</a>.</span></div>
<form method="post" class="card">
  <?= csrf_field() ?><input type="hidden" name="id" value="<?= $ed['id'] ?? '' ?>">
  <div class="row"><label>Nome<input name="nome" required value="<?= e($ed['nome'] ?? '') ?>"></label><label>Abreviação<input name="abreviacao" maxlength="10" value="<?= e($ed['abreviacao'] ?? '') ?>"></label>
  <label class="check" style="align-self:end"><input type="checkbox" name="ativo" value="1"<?= checked($ed['ativo'] ?? 1) ?>> Ativo</label></div>
  <div class="actions"><button class="btn primary"><?= $ed ? 'Salvar alterações' : '+ Adicionar' ?></button><?php if ($ed): ?><a class="btn" href="<?= url('componentes') ?>">Cancelar</a><?php endif ?></div>
</form>
<div class="table-wrap"><table><thead><tr><th>Componente</th><th>Abrev.</th><th class="num">Etapas</th><th></th></tr></thead><tbody>
<?php foreach ($comps as $c): ?><tr><td><?= e($c['nome']) ?> <?= $c['ativo'] ? '' : '<span class="badge muted">inativo</span>' ?></td><td><?= e($c['abreviacao']) ?></td><td class="num"><?= $c['etapas'] ?></td>
<td class="actions"><a class="btn sm" href="<?= url('componentes', ['id' => $c['id']]) ?>">Editar</a><form method="post"><?= csrf_field() ?><button class="btn sm danger" name="excluir" value="<?= $c['id'] ?>" data-confirm="Excluir componente?">Excluir</button></form></td></tr><?php endforeach ?>
</tbody></table></div>
