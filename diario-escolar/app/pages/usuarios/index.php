<?php
require_role('admin', 'gestor');
$title = 'Usuários';
$u = user();
$where = '1=1';
$params = [];
if ($u['perfil'] === 'gestor') {
    $where = "(u.escola_id = ? OR u.id IN (SELECT l.usuario_id FROM lotacoes l JOIN turmas t ON t.id = l.turma_id WHERE t.escola_id = ?))";
    $params = [$u['escola_id'], $u['escola_id']];
}
if (g('perfil') && isset(PERFIS[g('perfil')])) { $where .= ' AND u.perfil = ?'; $params[] = g('perfil'); }
if (g('busca')) { $where .= ' AND (u.nome LIKE ? OR u.email LIKE ?)'; $params[] = '%' . g('busca') . '%'; $params[] = '%' . g('busca') . '%'; }
$lista = all("SELECT u.*, s.nome escola_nome FROM usuarios u LEFT JOIN escolas s ON s.id = u.escola_id WHERE $where ORDER BY u.ativo DESC, u.nome LIMIT 500", $params);
?>
<div class="page-head"><h1>Usuários</h1><a class="btn primary" href="<?= url('usuarios/form') ?>">+ Novo usuário</a>
<span class="sub">Cadastre o e-mail Google de cada professor(a): o acesso é liberado somente para e-mails cadastrados.</span></div>
<form class="filters" method="get"><input type="hidden" name="r" value="usuarios">
  <label>Buscar<input name="busca" value="<?= e(g('busca')) ?>" placeholder="Nome ou e-mail"></label>
  <label>Perfil<select name="perfil"><?= options(PERFIS, g('perfil'), 'id', 'nome', 'Todos') ?></select></label><button class="btn">Filtrar</button></form>
<div class="table-wrap"><table><thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Escola</th><th>Último acesso</th><th></th></tr></thead><tbody>
<?php foreach ($lista as $x): ?><tr><td><?= e($x['nome']) ?> <?= $x['ativo'] ? '' : '<span class="badge muted">inativo</span>' ?></td><td><?= e($x['email']) ?></td><td><?= e(PERFIS[$x['perfil']]) ?></td><td><?= e($x['escola_nome']) ?></td>
<td><?= $x['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($x['ultimo_acesso'])) : '—' ?></td><td><?php if ($u['perfil'] === 'admin' || ($x['perfil'] === 'professor' && $x['escola_id'] == $u['escola_id'])): ?><a class="btn sm" href="<?= url('usuarios/form', ['id' => $x['id']]) ?>">Editar</a><?php endif ?></td></tr><?php endforeach ?>
<?php if (!$lista): ?><tr><td colspan="6" class="empty">Nenhum usuário encontrado.</td></tr><?php endif ?>
</tbody></table></div>
