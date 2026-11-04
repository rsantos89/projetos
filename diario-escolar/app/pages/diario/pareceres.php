<?php
$tid = gi('turma_id');
$t = turma_ou_404($tid);
$title = 'Pareceres · ' . $t['nome'];
$anoRow = one('SELECT * FROM anos_letivos WHERE id = ?', [$t['ano_letivo_id']]);
$pers = periodos((int) $t['ano_letivo_id']);
if (!$pers) { echo diario_tabs($t, 'pareceres') . '<div class="card empty">Cadastre os períodos do ano letivo.</div>'; return; }
$atual = periodo_da_data(date('Y-m-d'), (int) $t['ano_letivo_id']);
$pid = gi('periodo_id') ?: (int) ($atual['id'] ?? $pers[0]['id']);
$per = one('SELECT * FROM periodos WHERE id = ? AND ano_letivo_id = ?', [$pid, $t['ano_letivo_id']]) ?? not_found();
$editavel = pode_editar($per, $anoRow);
$alunos = array_values(array_filter(turma_alunos($tid), fn($a) => $a['avaliacao'] === 'parecer' && $a['situacao'] === 'cursando'));

if (is_post()) {
    if (!$editavel) forbidden();
    $st = db()->prepare('INSERT INTO pareceres (matricula_id, periodo_id, texto) VALUES (?,?,?) ON DUPLICATE KEY UPDATE texto = VALUES(texto)');
    foreach ($alunos as $a) if (isset($_POST['pt'][$a['id']])) $st->execute([$a['id'], $pid, nn(trim((string) $_POST['pt'][$a['id']]))]);
    audit('pareceres_salvar', "turma $tid periodo $pid");
    flash('success', 'Pareceres salvos.');
    redirect('diario/pareceres', ['turma_id' => $tid, 'periodo_id' => $pid]);
}
$txt = array_column(all('SELECT * FROM pareceres WHERE periodo_id = ? AND matricula_id IN (' . in_list(array_column($alunos, 'id')) . ')', [$pid]), 'texto', 'matricula_id');
echo diario_tabs($t, 'pareceres');
?>
<form class="filters" method="get"><input type="hidden" name="r" value="diario/pareceres"><input type="hidden" name="turma_id" value="<?= $tid ?>">
  <label>Período<select name="periodo_id" data-autosubmit><?= options($pers, $pid) ?></select></label></form>
<p class="muted">Relatório descritivo do desenvolvimento da criança (campos de experiência da BNCC: O eu, o outro e o nós; Corpo, gestos e movimentos; Traços, sons, cores e formas; Escuta, fala, pensamento e imaginação; Espaços, tempos, quantidades, relações e transformações).</p>
<?php if (!$editavel): ?><div class="alert warn">Período fechado: somente leitura.</div><?php endif ?>
<form method="post" data-dirty>
  <?= csrf_field() ?>
  <?php foreach ($alunos as $a): ?>
    <div class="card"><label><span><b><?= e(nome_aluno($a)) ?></b> <span class="muted">· <?= e($a['etapa_nome']) ?></span></span>
      <textarea name="pt[<?= $a['id'] ?>]" rows="5"<?= $editavel ? '' : ' disabled' ?>><?= e($txt[$a['id']] ?? '') ?></textarea></label></div>
  <?php endforeach ?>
  <?php if (!$alunos): ?><div class="card empty">Nenhum aluno com avaliação descritiva nesta turma.</div><?php else: ?>
  <div class="form-foot"><button class="btn primary"<?= $editavel ? '' : ' disabled' ?>>Salvar pareceres</button></div><?php endif ?>
</form>
