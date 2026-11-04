<?php
$tid = gi('turma_id');
$t = turma_ou_404($tid);
$title = 'Ocorrências · ' . $t['nome'];
$u = user();
$alunos = turma_alunos($tid);
$mids = array_map('intval', array_column($alunos, 'id'));
if (is_post()) {
    if (p('excluir')) {
        $o = one('SELECT * FROM ocorrencias WHERE id = ?', [(int) p('excluir')]);
        if ($o && in_array((int) $o['matricula_id'], $mids, true) && ($u['perfil'] !== 'professor' || $o['usuario_id'] == $u['id'])) {
            q('DELETE FROM ocorrencias WHERE id = ?', [$o['id']]);
            flash('success', 'Ocorrência excluída.');
        }
    } else {
        $mid = (int) p('matricula_id');
        if (!in_array($mid, $mids, true) || p('descricao') === '') { flash('error', 'Selecione o aluno e descreva a ocorrência.'); back(); }
        insert('ocorrencias', ['matricula_id' => $mid, 'usuario_id' => $u['id'], 'data' => p('data') ?: date('Y-m-d'), 'tipo' => in_array(p('tipo'), TIPOS_OCORRENCIA, true) ? p('tipo') : 'Outro', 'descricao' => p('descricao')]);
        audit('ocorrencia_add', "matricula $mid");
        flash('success', 'Ocorrência registrada.');
    }
    redirect('diario/ocorrencias', ['turma_id' => $tid]);
}
$ocs = all('SELECT o.*, a.nome, a.nome_social, u.nome autor FROM ocorrencias o JOIN matriculas m ON m.id = o.matricula_id JOIN alunos a ON a.id = m.aluno_id LEFT JOIN usuarios u ON u.id = o.usuario_id WHERE m.turma_id = ? ORDER BY o.data DESC, o.id DESC', [$tid]);
$ativos = array_filter($alunos, fn($a) => $a['situacao'] === 'cursando');
foreach ($ativos as &$a) $a['label'] = nome_aluno($a);
unset($a);
echo diario_tabs($t, 'ocorrencias');
?>
<form method="post" class="card">
  <?= csrf_field() ?>
  <div class="row"><label>Aluno<select name="matricula_id" required><?= options($ativos, null, 'id', 'label', 'Selecione...') ?></select></label>
    <label>Data<input type="date" name="data" value="<?= date('Y-m-d') ?>"></label><label>Tipo<select name="tipo"><?= options(array_combine(TIPOS_OCORRENCIA, TIPOS_OCORRENCIA)) ?></select></label></div>
  <label>Descrição<textarea name="descricao" required></textarea></label>
  <div class="actions" style="margin-top:10px"><button class="btn primary">Registrar ocorrência</button></div>
</form>
<div class="table-wrap"><table><thead><tr><th>Data</th><th>Aluno</th><th>Tipo</th><th>Descrição</th><th>Por</th><th></th></tr></thead><tbody>
<?php foreach ($ocs as $o): ?><tr><td><?= fdate($o['data']) ?></td><td><?= e(nome_aluno($o)) ?></td><td><span class="badge"><?= e($o['tipo']) ?></span></td><td><?= nl2br(e($o['descricao'])) ?></td><td><small><?= e($o['autor']) ?></small></td>
<td><?php if ($u['perfil'] !== 'professor' || $o['usuario_id'] == $u['id']): ?><form method="post"><?= csrf_field() ?><button class="btn sm danger" name="excluir" value="<?= $o['id'] ?>" data-confirm="Excluir ocorrência?">Excluir</button></form><?php endif ?></td></tr><?php endforeach ?>
<?php if (!$ocs): ?><tr><td colspan="6" class="empty">Nenhuma ocorrência registrada.</td></tr><?php endif ?>
</tbody></table></div>
