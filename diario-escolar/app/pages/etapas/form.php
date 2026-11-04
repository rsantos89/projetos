<?php
require_role('admin');
$id = gi('id');
$e = $id ? one('SELECT * FROM etapas WHERE id = ?', [$id]) : ['nivel' => 'fund1', 'nome' => '', 'ordem' => 0, 'avaliacao' => 'nota', 'ativo' => 1];
if (!$e) not_found();
$title = $id ? 'Editar etapa' : 'Nova etapa';
if (is_post()) {
    $d = ['nivel' => array_key_exists(p('nivel'), NIVEIS) ? p('nivel') : 'fund1', 'nome' => p('nome'), 'ordem' => (int) p('ordem'),
        'avaliacao' => p('avaliacao') === 'parecer' ? 'parecer' : 'nota', 'ativo' => p('ativo') ? 1 : 0];
    db()->beginTransaction();
    if ($id) update('etapas', $d, 'id = ?', [$id]); else $id = insert('etapas', $d);
    q('DELETE FROM etapa_componentes WHERE etapa_id = ?', [$id]);
    foreach ((array) p('comp', []) as $cid => $on) {
        insert('etapa_componentes', ['etapa_id' => $id, 'componente_id' => (int) $cid, 'carga_horaria' => nn($_POST['ch'][$cid] ?? '')]);
    }
    db()->commit();
    flash('success', 'Etapa salva.');
    redirect('etapas');
}
$comps = all('SELECT c.*, ec.carga_horaria, ec.etapa_id FROM componentes c LEFT JOIN etapa_componentes ec ON ec.componente_id = c.id AND ec.etapa_id = ? WHERE c.ativo = 1 ORDER BY c.nome', [$id]);
?>
<div class="page-head"><h1><?= $title ?></h1></div>
<form method="post" class="card">
  <?= csrf_field() ?>
  <div class="row"><label>Nome<input name="nome" required value="<?= e($e['nome']) ?>"></label><label>Nível<select name="nivel"><?= options(NIVEIS, $e['nivel']) ?></select></label>
    <label>Ordem<input type="number" name="ordem" value="<?= e($e['ordem']) ?>"></label><label>Avaliação<select name="avaliacao"><?= options(['nota' => 'Nota', 'parecer' => 'Parecer descritivo'], $e['avaliacao']) ?></select></label></div>
  <label class="check"><input type="checkbox" name="ativo" value="1"<?= checked($e['ativo']) ?>> Ativa</label>
  <h3 style="margin-top:16px">Matriz curricular</h3>
  <p class="muted">Na Educação Infantil (campos de experiência) pode deixar sem componentes: a frequência é lançada por dia.</p>
  <div class="table-wrap compact"><table><thead><tr><th></th><th>Componente</th><th>Carga horária anual</th></tr></thead><tbody>
  <?php foreach ($comps as $c): ?><tr><td><input type="checkbox" name="comp[<?= $c['id'] ?>]" value="1"<?= checked($c['etapa_id']) ?>></td><td><?= e($c['nome']) ?></td><td><input type="number" name="ch[<?= $c['id'] ?>]" value="<?= e($c['carga_horaria']) ?>" style="max-width:120px"></td></tr><?php endforeach ?>
  </tbody></table></div>
  <div class="form-foot"><a class="btn" href="<?= url('etapas') ?>">Cancelar</a><button class="btn primary">Salvar</button></div>
</form>
