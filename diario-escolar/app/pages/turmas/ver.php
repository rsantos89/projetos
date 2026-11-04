<?php
require_role('admin', 'gestor');
$id = gi('id');
$t = turma_ou_404($id, true);
$title = $t['nome'];
$etapas = turma_etapas($id);
$comps = turma_componentes($id);

if (is_post()) {
    if (p('add_lotacao')) {
        $prof = (int) p('usuario_id');
        $cids = (array) p('componentes', []);
        $pu = $prof ? one("SELECT * FROM usuarios WHERE id = ? AND perfil = 'professor' AND ativo = 1", [$prof]) : null;
        if (!$pu) { flash('error', 'Selecione um professor ativo.'); back(); }
        if (user()['perfil'] === 'gestor' && (int) $pu['escola_id'] !== (int) $t['escola_id']) forbidden();
        $validos = array_map('intval', array_column($comps, 'id'));
        foreach ($cids as $cid) if ($cid !== '' && !in_array((int) $cid, $validos, true)) { flash('error', 'Componente inválido para a turma.'); back(); }
        if (!$cids || in_array('', $cids, true)) {
            q('DELETE FROM lotacoes WHERE turma_id = ? AND usuario_id = ?', [$id, $prof]);
            insert('lotacoes', ['turma_id' => $id, 'usuario_id' => $prof, 'componente_id' => null]);
        } else {
            foreach ($cids as $cid) {
                if (!val('SELECT 1 FROM lotacoes WHERE turma_id = ? AND usuario_id = ? AND componente_id = ?', [$id, $prof, (int) $cid]))
                    insert('lotacoes', ['turma_id' => $id, 'usuario_id' => $prof, 'componente_id' => (int) $cid]);
            }
        }
        audit('lotacao_add', "turma $id prof $prof");
        flash('success', 'Professor vinculado à turma.');
    }
    if (p('del_lotacao')) {
        q('DELETE FROM lotacoes WHERE id = ? AND turma_id = ?', [(int) p('del_lotacao'), $id]);
        flash('success', 'Vínculo removido.');
    }
    redirect('turmas/ver', ['id' => $id]);
}

$alunos = turma_alunos($id);
$ativos = array_filter($alunos, fn($a) => $a['situacao'] === 'cursando');
$lot = all('SELECT l.*, u.nome, u.email, c.nome comp FROM lotacoes l JOIN usuarios u ON u.id = l.usuario_id LEFT JOIN componentes c ON c.id = l.componente_id WHERE l.turma_id = ? ORDER BY u.nome, c.nome', [$id]);
$profs = all("SELECT id, nome FROM usuarios WHERE perfil = 'professor' AND ativo = 1 ORDER BY nome");
$parecer = turma_so_parecer($id);
$porEtapa = [];
foreach ($ativos as $a) $porEtapa[$a['etapa_nome']] = ($porEtapa[$a['etapa_nome']] ?? 0) + 1;
?>
<div class="page-head">
  <h1><?= e($t['nome']) ?></h1>
  <div class="actions">
    <a class="btn primary" href="<?= url('matriculas/nova', ['turma_id' => $id]) ?>">+ Matricular aluno</a>
    <a class="btn" href="<?= url('turmas/form', ['id' => $id]) ?>">Editar</a>
  </div>
  <span class="sub"><?= e($t['escola_nome']) ?> · <?= e(TURNOS[$t['turno']]) ?><?= $t['sala'] ? ' · Sala ' . e($t['sala']) : '' ?> · <?= count($ativos) ?>/<?= $t['vagas'] ?> vagas ocupadas</span>
</div>
<div class="card">
  <div class="actions" style="margin-bottom:10px"><?php foreach ($etapas as $e): ?><span class="badge"><?= e($e['nome']) ?>: <?= $porEtapa[$e['nome']] ?? 0 ?></span><?php endforeach ?><?php if (count($etapas) > 1): ?><span class="badge warn">Multisseriada</span><?php endif ?></div>
  <div class="actions">
    <a class="btn sm" href="<?= url('diario/frequencia', ['turma_id' => $id]) ?>">Frequência</a>
    <a class="btn sm" href="<?= url('diario/aulas', ['turma_id' => $id]) ?>">Aulas / conteúdos</a>
    <?php if (!$parecer): ?><a class="btn sm" href="<?= url('diario/notas', ['turma_id' => $id]) ?>">Notas</a><?php endif ?>
    <?php if (count(array_filter($etapas, fn($e) => $e['avaliacao'] === 'parecer'))): ?><a class="btn sm" href="<?= url('diario/pareceres', ['turma_id' => $id]) ?>">Pareceres</a><?php endif ?>
    <a class="btn sm" href="<?= url('diario/ocorrencias', ['turma_id' => $id]) ?>">Ocorrências</a>
    <a class="btn sm" href="<?= url('fechamento', ['turma_id' => $id]) ?>">Fechamento</a>
    <a class="btn sm" href="<?= url('relatorios', ['turma_id' => $id]) ?>">Relatórios</a>
  </div>
</div>

<h2>Alunos</h2>
<div class="table-wrap"><table><thead><tr><th class="num">Nº</th><th>Aluno</th><th>Etapa</th><th>Matrícula</th><th>Situação</th><th></th></tr></thead><tbody>
<?php $n = 0; foreach ($alunos as $a): ?>
<tr><td class="num"><?= $a['situacao'] === 'cursando' ? ++$n : '' ?></td><td><a href="<?= url('alunos/ver', ['id' => $a['aluno_id']]) ?>"><?= e(nome_aluno($a)) ?></a> <?= $a['nee'] ? '<span class="badge" title="Necessidades educacionais especiais">NEE</span>' : '' ?><span class="etapa-tag"><?= idade($a['data_nascimento']) ? idade($a['data_nascimento']) . ' anos' : '' ?></span></td>
<td><?= e($a['etapa_nome']) ?></td><td><?= e($a['numero']) ?><span class="etapa-tag"><?= fdate($a['data_matricula']) ?></span></td><td><?= situacao_badge($a['situacao']) ?><?= $a['data_saida'] ? '<span class="etapa-tag">' . fdate($a['data_saida']) . '</span>' : '' ?></td>
<td class="actions"><a class="btn sm" href="<?= url('matriculas/mover', ['id' => $a['id']]) ?>">Movimentar</a></td></tr>
<?php endforeach; if (!$alunos): ?><tr><td colspan="6" class="empty">Nenhum aluno matriculado.</td></tr><?php endif ?>
</tbody></table></div>

<h2>Professores</h2>
<div class="table-wrap"><table><thead><tr><th>Professor(a)</th><th>Componente</th><th></th></tr></thead><tbody>
<?php foreach ($lot as $l): ?><tr><td><?= e($l['nome']) ?><span class="etapa-tag"><?= e($l['email']) ?></span></td><td><?= e($l['comp'] ?? 'Polivalente (todos)') ?></td>
<td><form method="post"><?= csrf_field() ?><button class="btn sm danger" name="del_lotacao" value="<?= $l['id'] ?>" data-confirm="Remover vínculo?">Remover</button></form></td></tr><?php endforeach ?>
<?php if (!$lot): ?><tr><td colspan="3" class="empty">Nenhum professor vinculado.</td></tr><?php endif ?>
</tbody></table></div>
<form method="post" class="card">
  <?= csrf_field() ?>
  <h3>Vincular professor(a)</h3>
  <div class="row">
    <label>Professor(a)<select name="usuario_id" required><?= options($profs, null, 'id', 'nome', 'Selecione...') ?></select></label>
    <label>Componentes (Ctrl/toque para vários)<select name="componentes[]" multiple size="<?= min(6, count($comps) + 1) ?>"><option value="" selected>Polivalente (todos)</option><?= options($comps) ?></select></label>
  </div>
  <p class="muted">Não encontrou o professor? <a href="<?= url('usuarios/form') ?>">Cadastre o usuário</a> com o e-mail Google dele.</p>
  <button class="btn primary" name="add_lotacao" value="1">Vincular</button>
</form>
