<?php
require_role('admin');
$id = gi('id');
$s = $id ? one('SELECT * FROM escolas WHERE id = ?', [$id]) : ['nome' => '', 'inep' => '', 'endereco' => '', 'bairro' => '', 'zona' => 'urbana', 'telefone' => '', 'email' => '', 'diretor' => '', 'secretario' => '', 'ativo' => 1];
if (!$s) not_found();
$title = $id ? 'Editar escola' : 'Nova escola';
if (is_post()) {
    $d = ['nome' => p('nome'), 'inep' => nn(p('inep')), 'endereco' => nn(p('endereco')), 'bairro' => nn(p('bairro')), 'zona' => p('zona') === 'rural' ? 'rural' : 'urbana',
        'telefone' => nn(p('telefone')), 'email' => nn(p('email')), 'diretor' => nn(p('diretor')), 'secretario' => nn(p('secretario')), 'ativo' => p('ativo') ? 1 : 0];
    if ($id) update('escolas', $d, 'id = ?', [$id]); else $id = insert('escolas', $d);
    audit('escola_salvar', $d['nome']);
    flash('success', 'Escola salva.');
    redirect('escolas');
}
?>
<div class="page-head"><h1><?= $title ?></h1></div>
<form method="post" class="card">
  <?= csrf_field() ?>
  <div class="row"><label>Nome da escola<input name="nome" required value="<?= e($s['nome']) ?>"></label><label>Código INEP<input name="inep" value="<?= e($s['inep']) ?>"></label></div>
  <div class="row"><label>Endereço<input name="endereco" value="<?= e($s['endereco']) ?>"></label><label>Bairro/Localidade<input name="bairro" value="<?= e($s['bairro']) ?>"></label>
    <label>Zona<select name="zona"><?= options(['urbana' => 'Urbana', 'rural' => 'Rural'], $s['zona']) ?></select></label></div>
  <div class="row"><label>Telefone<input name="telefone" data-mask="tel" value="<?= e($s['telefone']) ?>"></label><label>E-mail<input type="email" name="email" value="<?= e($s['email']) ?>"></label></div>
  <div class="row"><label>Diretor(a)<input name="diretor" value="<?= e($s['diretor']) ?>"></label><label>Secretário(a) escolar<input name="secretario" value="<?= e($s['secretario']) ?>"></label></div>
  <label class="check"><input type="checkbox" name="ativo" value="1"<?= checked($s['ativo']) ?>> Escola ativa</label>
  <div class="form-foot"><a class="btn" href="<?= url('escolas') ?>">Cancelar</a><button class="btn primary">Salvar</button></div>
</form>
