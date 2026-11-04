<?php
require_role('admin', 'gestor');
$me = user();
$admin = $me['perfil'] === 'admin';
$id = gi('id');
$x = $id ? one('SELECT * FROM usuarios WHERE id = ?', [$id]) : ['nome' => '', 'email' => '', 'perfil' => 'professor', 'escola_id' => $admin ? null : $me['escola_id'], 'telefone' => '', 'matricula_funcional' => '', 'ativo' => 1];
if (!$x) not_found();
if (!$admin && $id && ($x['perfil'] !== 'professor' || $x['escola_id'] != $me['escola_id'])) forbidden();
$title = $id ? 'Editar usuário' : 'Novo usuário';

if (is_post()) {
    $d = ['nome' => p('nome'), 'email' => mb_strtolower((string) p('email')), 'telefone' => nn(p('telefone')), 'matricula_funcional' => nn(p('matricula_funcional')), 'ativo' => p('ativo') ? 1 : 0];
    if ($admin) {
        $d['perfil'] = isset(PERFIS[p('perfil')]) ? p('perfil') : 'professor';
        $d['escola_id'] = nn(p('escola_id'));
        if ($id == $me['id']) { $d['perfil'] = 'admin'; $d['ativo'] = 1; }
    } else {
        $d['perfil'] = 'professor';
        $d['escola_id'] = $me['escola_id'];
    }
    if ($d['perfil'] === 'gestor' && !$d['escola_id']) { flash('error', 'Informe a escola do gestor.'); back(); }
    if (p('senha') !== '') {
        if (strlen(p('senha')) < 8) { flash('error', 'A senha deve ter ao menos 8 caracteres.'); back(); }
        $d['senha_hash'] = password_hash(p('senha'), PASSWORD_DEFAULT);
    }
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) { flash('error', 'E-mail inválido.'); back(); }
    if (val('SELECT id FROM usuarios WHERE email = ? AND id <> ?', [$d['email'], $id])) { flash('error', 'Já existe um usuário com este e-mail.'); back(); }
    if ($id) update('usuarios', $d, 'id = ?', [$id]); else $id = insert('usuarios', $d);
    audit('usuario_salvar', $d['email']);
    flash('success', 'Usuário salvo.');
    redirect('usuarios');
}
$lot = $id ? all('SELECT t.nome, t.turno, s.nome escola, c.nome comp, a.ano FROM lotacoes l JOIN turmas t ON t.id = l.turma_id JOIN escolas s ON s.id = t.escola_id JOIN anos_letivos a ON a.id = t.ano_letivo_id LEFT JOIN componentes c ON c.id = l.componente_id WHERE l.usuario_id = ? ORDER BY a.ano DESC, s.nome, t.nome', [$id]) : [];
?>
<div class="page-head"><h1><?= $title ?></h1></div>
<form method="post" class="card">
  <?= csrf_field() ?>
  <div class="row"><label>Nome completo<input name="nome" required value="<?= e($x['nome']) ?>"></label><label>E-mail (conta Google)<input type="email" name="email" required value="<?= e($x['email']) ?>"></label></div>
  <div class="row">
    <?php if ($admin): ?>
    <label>Perfil<select name="perfil"><?= options(PERFIS, $x['perfil']) ?></select></label>
    <label>Escola (obrigatório para gestão)<select name="escola_id"><?= options(all('SELECT id, nome FROM escolas ORDER BY nome'), $x['escola_id'], 'id', 'nome', '— Rede / várias escolas —') ?></select></label>
    <?php endif ?>
    <label>Telefone<input name="telefone" data-mask="tel" value="<?= e($x['telefone']) ?>"></label><label>Matrícula funcional<input name="matricula_funcional" value="<?= e($x['matricula_funcional']) ?>"></label>
  </div>
  <div class="row"><label>Senha (opcional; deixe em branco para manter / usar só Google)<input type="password" name="senha" minlength="8" autocomplete="new-password"></label></div>
  <label class="check"><input type="checkbox" name="ativo" value="1"<?= checked($x['ativo']) ?>> Ativo</label>
  <?php if ($lot): ?><h3 style="margin-top:16px">Lotações</h3><ul><?php foreach ($lot as $l): ?><li><?= $l['ano'] ?> · <?= e($l['escola']) ?> · <?= e(turma_nome($l)) ?> · <?= e($l['comp'] ?? 'Polivalente') ?></li><?php endforeach ?></ul><?php endif ?>
  <div class="form-foot"><a class="btn" href="<?= url('usuarios') ?>">Cancelar</a><button class="btn primary">Salvar</button></div>
</form>
