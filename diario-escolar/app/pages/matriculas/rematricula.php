<?php
require_role('admin', 'gestor');
require __DIR__ . '/_lib.php';
$title = 'Rematrícula em lote';
$u = user();
$anoAtual = ano();
$anoAnt = one('SELECT * FROM anos_letivos WHERE ano < ? ORDER BY ano DESC LIMIT 1', [$anoAtual['ano'] ?? 0]);
$escolaFiltro = $u['perfil'] === 'gestor' ? (int) $u['escola_id'] : null;

if (is_post()) {
    $ok = 0;
    $erros = [];
    db()->beginTransaction();
    foreach ((array) p('sel', []) as $mid => $on) {
        $mid = (int) $mid;
        $orig = one('SELECT m.*, t.escola_id, a.nome FROM matriculas m JOIN turmas t ON t.id = m.turma_id JOIN alunos a ON a.id = m.aluno_id WHERE m.id = ?', [$mid]);
        $turmaDest = (int) ($_POST['turma'][$mid] ?? 0);
        $etapaDest = (int) ($_POST['etapa'][$mid] ?? 0);
        if (!$orig || !$turmaDest || !$etapaDest) { $erros[] = ($orig['nome'] ?? "#$mid") . ': selecione turma e etapa'; continue; }
        if ($escolaFiltro && $orig['escola_id'] != $escolaFiltro) continue;
        $dest = one('SELECT escola_id FROM turmas WHERE id = ? AND ano_letivo_id = ?', [$turmaDest, ano_id()]);
        if (!$dest || ($escolaFiltro && $dest['escola_id'] != $escolaFiltro)) { $erros[] = $orig['nome'] . ': turma de destino inválida'; continue; }
        try {
            criar_matricula((int) $orig['aluno_id'], $turmaDest, $etapaDest, (string) p('data', date('Y-m-d')), 'rematricula');
            $ok++;
        } catch (RuntimeException $e) {
            $erros[] = $orig['nome'] . ': ' . $e->getMessage();
        }
    }
    db()->commit();
    audit('rematricula_lote', "$ok alunos");
    flash($ok ? 'success' : 'warn', "$ok aluno(s) rematriculado(s).");
    foreach (array_slice($erros, 0, 10) as $er) flash('error', $er);
    back('matriculas/rematricula');
}

$turmaOrig = gi('turma_id');
$turmasAnt = $anoAnt ? all('SELECT t.*, s.nome escola_nome FROM turmas t JOIN escolas s ON s.id = t.escola_id WHERE t.ano_letivo_id = ?' . ($escolaFiltro ? ' AND t.escola_id = ' . $escolaFiltro : '') . ' ORDER BY s.nome, t.nome', [$anoAnt['id']]) : [];
foreach ($turmasAnt as &$t) $t['label'] = ($escolaFiltro ? '' : $t['escola_nome'] . ' · ') . turma_nome($t);
unset($t);
$alunos = [];
$destinos = [];
if ($turmaOrig) {
    $to = turma_ou_404($turmaOrig, true);
    $alunos = all("SELECT m.*, a.nome, a.nome_social, e.nome etapa_nome, e.ordem,
        (SELECT id FROM etapas e2 WHERE e2.ordem > e.ordem AND e2.ativo = 1 ORDER BY e2.ordem LIMIT 1) prox_etapa,
        (SELECT 1 FROM matriculas m2 WHERE m2.aluno_id = m.aluno_id AND m2.ano_letivo_id = ? AND m2.situacao = 'cursando') ja
        FROM matriculas m JOIN alunos a ON a.id = m.aluno_id JOIN etapas e ON e.id = m.etapa_id
        WHERE m.turma_id = ? AND m.situacao IN ('cursando','aprovado','reprovado','concluido') ORDER BY a.nome", [ano_id(), $turmaOrig]);
    $destinos = turmas_para_matricula((int) $to['escola_id']);
    foreach ($destinos as &$d) $d['etapas'] = array_column(turma_etapas((int) $d['id']), 'id');
    unset($d);
}
$etapasAll = all('SELECT id, nome FROM etapas WHERE ativo = 1 ORDER BY ordem');
?>
<div class="page-head"><h1>Rematrícula em lote</h1>
  <span class="sub">Copia alunos de uma turma de <?= $anoAnt['ano'] ?? '—' ?> para turmas de <?= $anoAtual['ano'] ?? '—' ?>. Aprovados/concluintes avançam de etapa; reprovados permanecem. Faça o fechamento do ano anterior antes.</span></div>
<?php if (!$anoAnt): ?><div class="alert info">Não há ano letivo anterior ao selecionado. Selecione o ano de destino no topo (ex.: <?= date('Y') + 1 ?>) após cadastrá-lo em Cadastros → Anos letivos.</div><?php else: ?>
<form class="filters" method="get"><input type="hidden" name="r" value="matriculas/rematricula">
  <label>Turma de origem (<?= $anoAnt['ano'] ?>)<select name="turma_id" data-autosubmit><?= options($turmasAnt, $turmaOrig, 'id', 'label', 'Selecione...') ?></select></label></form>
<?php if ($turmaOrig): ?>
<form method="post">
  <?= csrf_field() ?>
  <div class="table-wrap"><table><thead><tr><th><input type="checkbox" onclick="document.querySelectorAll('.selrem').forEach(c=>c.checked=this.checked)"></th><th>Aluno</th><th>Situação <?= $anoAnt['ano'] ?></th><th>Etapa destino</th><th>Turma destino</th></tr></thead><tbody>
  <?php foreach ($alunos as $a):
      $etDest = in_array($a['situacao'], ['aprovado', 'concluido'], true) ? ($a['prox_etapa'] ?: null) : $a['etapa_id'];
      $sug = null;
      foreach ($destinos as $d) if ($etDest && in_array($etDest, $d['etapas'])) { $sug = $d['id']; break; } ?>
    <tr><td><?php if (!$a['ja'] && $etDest): ?><input class="selrem" type="checkbox" name="sel[<?= $a['id'] ?>]" value="1"<?= checked($a['situacao'] !== 'cursando') ?>><?php endif ?></td>
      <td><?= e(nome_aluno($a)) ?><span class="etapa-tag"><?= e($a['etapa_nome']) ?></span></td><td><?= situacao_badge($a['situacao']) ?></td>
      <?php if ($a['ja']): ?><td colspan="2"><span class="badge ok">Já matriculado em <?= $anoAtual['ano'] ?></span></td>
      <?php elseif (!$etDest): ?><td colspan="2"><span class="badge muted">Concluiu a última etapa da rede</span></td>
      <?php else: ?><td><select name="etapa[<?= $a['id'] ?>]"><?= options($etapasAll, $etDest) ?></select></td><td><select name="turma[<?= $a['id'] ?>]"><?= options($destinos, $sug, 'id', 'label', 'Selecione...') ?></select></td><?php endif ?></tr>
  <?php endforeach ?>
  </tbody></table></div>
  <?php if (array_filter($alunos, fn($a) => $a['situacao'] === 'cursando')): ?><div class="alert warn">Há alunos ainda "cursando" no ano anterior (fechamento não realizado). Eles não vêm marcados.</div><?php endif ?>
  <div class="form-foot"><label>Data<input type="date" name="data" value="<?= date('Y-m-d') ?>"></label><button class="btn primary">Rematricular selecionados</button></div>
</form>
<?php endif; endif;
