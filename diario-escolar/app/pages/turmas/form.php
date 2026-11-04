<?php
require_role('admin', 'gestor');
$u = user();
$id = gi('id');
if ($id) $t = turma_ou_404($id, true);
else $t = ['escola_id' => $u['perfil'] === 'gestor' ? $u['escola_id'] : gi('escola_id'), 'nome' => '', 'turno' => 'matutino', 'sala' => '', 'vagas' => 25, 'ativo' => 1, 'ano_letivo_id' => ano_id()];
$title = $id ? 'Editar turma' : 'Nova turma';
$sel = $id ? array_column(turma_etapas($id), 'id') : [];

if (is_post()) {
    if (p('excluir') && $id) {
        if (val('SELECT 1 FROM matriculas WHERE turma_id = ?', [$id])) { flash('error', 'A turma possui matrículas e não pode ser excluída. Desative-a.'); back(); }
        q('DELETE FROM turmas WHERE id = ?', [$id]);
        audit('turma_excluir', $t['nome']);
        flash('success', 'Turma excluída.');
        redirect('turmas');
    }
    $etapas = array_map('intval', (array) p('etapas', []));
    if (!$etapas) { flash('error', 'Selecione ao menos uma etapa/série.'); back(); }
    $escolaId = $u['perfil'] === 'gestor' ? (int) $u['escola_id'] : (int) p('escola_id');
    $d = ['escola_id' => $escolaId, 'nome' => p('nome'), 'turno' => isset(TURNOS[p('turno')]) ? p('turno') : 'matutino', 'sala' => nn(p('sala')), 'vagas' => max(1, (int) p('vagas')), 'ativo' => p('ativo') ? 1 : 0];
    db()->beginTransaction();
    if ($id) {
        update('turmas', $d, 'id = ?', [$id]);
        $emUso = array_column(all("SELECT DISTINCT etapa_id FROM matriculas WHERE turma_id = ?", [$id]), 'etapa_id');
        $etapas = array_unique(array_merge($etapas, array_map('intval', $emUso)));
    } else {
        $id = insert('turmas', $d + ['ano_letivo_id' => ano_id()]);
    }
    q('DELETE FROM turma_etapas WHERE turma_id = ?', [$id]);
    foreach ($etapas as $eid) insert('turma_etapas', ['turma_id' => $id, 'etapa_id' => $eid]);
    db()->commit();
    audit('turma_salvar', $d['nome']);
    flash('success', count($etapas) > 1 ? 'Turma multisseriada salva.' : 'Turma salva.');
    redirect('turmas/ver', ['id' => $id]);
}
$etapasAll = all('SELECT * FROM etapas WHERE ativo = 1 ORDER BY ordem');
$porNivel = [];
foreach ($etapasAll as $e) $porNivel[$e['nivel']][] = $e;
?>
<div class="page-head"><h1><?= $title ?></h1><span class="sub">Ano letivo <?= ano()['ano'] ?? '' ?>. Marque mais de uma etapa para criar uma turma <b>multisseriada</b>.</span></div>
<form method="post" class="card">
  <?= csrf_field() ?>
  <div class="row">
    <?php if ($u['perfil'] === 'admin'): ?><label>Escola<select name="escola_id" required><?= options(escolas_visiveis(), $t['escola_id'], 'id', 'nome', 'Selecione...') ?></select></label><?php endif ?>
    <label>Nome da turma<input name="nome" required value="<?= e($t['nome']) ?>" placeholder="Ex.: 1º Ano A, Multi 1º/2º/3º"></label>
  </div>
  <div class="row"><label>Turno<select name="turno"><?= options(TURNOS, $t['turno']) ?></select></label><label>Sala<input name="sala" value="<?= e($t['sala']) ?>"></label><label>Vagas<input type="number" name="vagas" min="1" value="<?= e($t['vagas']) ?>"></label></div>
  <fieldset><legend>Etapas / séries atendidas</legend>
    <?php foreach ($porNivel as $nv => $lista): ?>
      <p style="margin:6px 0 4px"><b><?= e(NIVEIS[$nv]) ?></b></p>
      <div class="actions"><?php foreach ($lista as $e): ?><label class="check"><input type="checkbox" name="etapas[]" value="<?= $e['id'] ?>"<?= checked(in_array($e['id'], $sel)) ?>> <?= e($e['nome']) ?></label><?php endforeach ?></div>
    <?php endforeach ?>
  </fieldset>
  <label class="check"><input type="checkbox" name="ativo" value="1"<?= checked($t['ativo']) ?>> Turma ativa</label>
  <div class="form-foot">
    <?php if ($id): ?><button class="btn danger" name="excluir" value="1" formnovalidate data-confirm="Excluir esta turma?">Excluir</button><?php endif ?>
    <a class="btn" href="<?= $id ? url('turmas/ver', ['id' => $id]) : url('turmas') ?>">Cancelar</a><button class="btn primary">Salvar</button></div>
</form>
