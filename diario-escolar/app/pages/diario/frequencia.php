<?php
$tid = gi('turma_id');
$t = turma_ou_404($tid);
$title = 'Frequência · ' . $t['nome'];
$u = user();
$comps = componentes_permitidos($tid);
$polivalente = $u['perfil'] !== 'professor' || val('SELECT 1 FROM lotacoes WHERE turma_id = ? AND usuario_id = ? AND componente_id IS NULL', [$tid, $u['id']]);
$opcoes = $polivalente ? ['' => 'Diária (todos os componentes)'] : [];
foreach ($comps as $c) $opcoes[$c['id']] = $c['nome'];
if (!$opcoes) forbidden();
$anosIniciais = !val("SELECT 1 FROM turma_etapas te JOIN etapas e ON e.id = te.etapa_id WHERE te.turma_id = ? AND e.nivel NOT IN ('infantil','fund1')", [$tid]);
$default = ($polivalente && ($anosIniciais || !$comps)) ? '' : (string) array_key_first(array_diff_key($opcoes, ['' => 1]));
$comp = array_key_exists((string) g('componente_id', $default), $opcoes) ? (string) g('componente_id', $default) : $default;
$compId = $comp === '' ? null : (int) $comp;
$data = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) g('data')) ? g('data') : date('Y-m-d');
$aula = one('SELECT * FROM aulas WHERE turma_id = ? AND componente_id <=> ? AND data = ?', [$tid, $compId, $data]);
$periodo = periodo_da_data($data, (int) $t['ano_letivo_id']);
$editavel = pode_editar($periodo, one('SELECT * FROM anos_letivos WHERE id = ?', [$t['ano_letivo_id']]));
$params = ['turma_id' => $tid, 'componente_id' => $comp, 'data' => $data];

if (is_post()) {
    if (!$editavel) { flash('error', 'Período fechado para lançamentos.'); redirect('diario/frequencia', $params); }
    if (p('excluir') && $aula) {
        q('DELETE FROM aulas WHERE id = ?', [$aula['id']]);
        audit('aula_excluir', "turma $tid $data");
        flash('success', 'Registro de aula excluído.');
        redirect('diario/frequencia', $params);
    }
    $qtd = max(1, min(10, (int) p('quantidade', 1)));
    db()->beginTransaction();
    $dados = ['quantidade' => $qtd, 'conteudo' => nn(p('conteudo')), 'observacao' => nn(p('observacao'))];
    if ($aula) { update('aulas', $dados, 'id = ?', [$aula['id']]); $aulaId = (int) $aula['id']; }
    else $aulaId = insert('aulas', $dados + ['turma_id' => $tid, 'componente_id' => $compId, 'usuario_id' => $u['id'], 'data' => $data]);
    q('DELETE FROM frequencias WHERE aula_id = ?', [$aulaId]);
    $st = db()->prepare('INSERT INTO frequencias (aula_id, matricula_id, faltas, justificada) VALUES (?, ?, ?, ?)');
    $validos = array_column(turma_alunos($tid), 'id');
    foreach ((array) p('f', []) as $mid => $v) {
        if (!in_array((int) $mid, array_map('intval', $validos), true)) continue;
        $st->execute([$aulaId, (int) $mid, $v === '0' ? 0 : $qtd, $v === 'J' ? 1 : 0]);
    }
    db()->commit();
    audit('frequencia_salvar', "turma $tid comp " . ($compId ?? 'diaria') . " $data");
    flash('success', 'Frequência e aula registradas em ' . fdate($data) . '.');
    redirect('diario/frequencia', $params);
}

$freq = $aula ? array_column(all('SELECT * FROM frequencias WHERE aula_id = ?', [$aula['id']]), null, 'matricula_id') : [];
$alunos = array_filter(turma_alunos($tid), fn($a) => isset($freq[$a['id']]) || ($a['situacao'] === 'cursando' && $a['data_matricula'] <= $data));
$grupos = [];
foreach ($alunos as $a) $grupos[$a['etapa_nome']][] = $a;
$multi = count($grupos) > 1;
$recentes = all('SELECT data, quantidade FROM aulas WHERE turma_id = ? AND componente_id <=> ? ORDER BY data DESC LIMIT 8', [$tid, $compId]);
$resumo = frequencia_resumo(array_column($alunos, 'id'), $compId);
$prev = date('Y-m-d', strtotime("$data -1 day"));
$next = date('Y-m-d', strtotime("$data +1 day"));
echo diario_tabs($t, 'frequencia');
?>
<form class="filters" method="get"><input type="hidden" name="r" value="diario/frequencia"><input type="hidden" name="turma_id" value="<?= $tid ?>">
  <label>Componente<select name="componente_id" data-autosubmit><?= options($opcoes, $comp) ?></select></label>
  <label>Data<input type="date" name="data" value="<?= e($data) ?>" data-autosubmit></label>
  <div class="actions"><a class="btn" href="<?= url('diario/frequencia', ['data' => $prev] + $params) ?>" aria-label="Dia anterior">‹</a><a class="btn" href="<?= url('diario/frequencia', ['data' => date('Y-m-d')] + $params) ?>">Hoje</a><a class="btn" href="<?= url('diario/frequencia', ['data' => $next] + $params) ?>" aria-label="Próximo dia">›</a></div>
</form>
<?php if (!$periodo): ?><div class="alert warn">A data <?= fdate($data) ?> está fora dos períodos letivos cadastrados.</div><?php endif ?>
<?php if (in_array(date('N', strtotime($data)), ['6', '7'], true)): ?><div class="alert info">Atenção: <?= fdate($data) ?> é fim de semana.</div><?php endif ?>
<?php if (!$editavel): ?><div class="alert warn">Período fechado: somente leitura.</div><?php endif ?>
<?php if ($recentes): ?><p class="muted">Últimos registros: <?php foreach ($recentes as $r): ?><a href="<?= url('diario/frequencia', ['data' => $r['data']] + $params) ?>" class="badge<?= $r['data'] === $data ? ' ok' : '' ?>"><?= date('d/m', strtotime($r['data'])) ?></a> <?php endforeach ?></p><?php endif ?>

<form method="post" data-dirty>
  <?= csrf_field() ?>
  <fieldset <?= $editavel ? '' : 'disabled' ?> style="border:0;padding:0;margin:0">
  <div class="card">
    <div class="row">
      <label>Quantidade de aulas<input type="number" name="quantidade" min="1" max="10" value="<?= e($aula['quantidade'] ?? 1) ?>"></label>
      <label style="grid-column:span 2">Conteúdo / objetos de conhecimento <?= $multi ? '(descreva por etapa)' : '' ?><textarea name="conteudo" rows="3" placeholder="<?= $multi ? implode(': ...&#10;', array_keys($grupos)) . ': ...' : 'Ex.: Leitura e interpretação de texto (EF15LP03)' ?>"><?= e($aula['conteudo'] ?? '') ?></textarea></label>
    </div>
    <label>Observações da aula<input name="observacao" value="<?= e($aula['observacao'] ?? '') ?>"></label>
  </div>
  <div class="actions" style="margin-bottom:10px"><b><?= count($alunos) ?> aluno(s)</b> · Toque para alternar: <span class="badge ok">P</span> presente <span class="badge bad">F</span> falta <span class="badge warn">FJ</span> falta justificada
    <span style="margin-left:auto" class="actions"><button type="button" class="btn sm" data-all="P">Todos presentes</button><button type="button" class="btn sm" data-all="F">Todos faltaram</button></span></div>
  <?php foreach ($grupos as $etapa => $lista): ?>
  <div class="table-wrap att"><table>
    <?php if ($multi): ?><thead><tr><th colspan="4"><?= e($etapa) ?></th></tr></thead><?php endif ?>
    <tbody>
    <?php foreach ($lista as $i => $a): $f = $freq[$a['id']] ?? null; $v = !$f ? '0' : ($f['justificada'] ? 'J' : ($f['faltas'] ? '1' : '0')); $r = $resumo[$a['id']] ?? null; ?>
      <tr><td class="n"><?= $i + 1 ?></td><td><?= e(nome_aluno($a)) ?> <?= $a['situacao'] !== 'cursando' ? situacao_badge($a['situacao']) : '' ?><span class="etapa-tag"><?= $r ? $r['faltas'] . ' falta(s) · ' . fnum(perc_freq($r)) . '%' : '' ?></span></td>
        <td style="width:70px"><button type="button" class="att-btn<?= $v === '1' ? ' f' : ($v === 'J' ? ' j' : '') ?>"><?= $v === '1' ? 'F' : ($v === 'J' ? 'FJ' : 'P') ?></button><input type="hidden" name="f[<?= $a['id'] ?>]" value="<?= $v ?>"></td></tr>
    <?php endforeach ?>
    </tbody></table></div>
  <?php endforeach ?>
  <?php if (!$alunos): ?><div class="card empty">Nenhum aluno ativo nesta data.</div><?php endif ?>
  <div class="form-foot">
    <?php if ($aula): ?><button class="btn danger" name="excluir" value="1" data-confirm="Excluir o registro desta aula e a frequência?">Excluir aula</button><?php endif ?>
    <button class="btn primary"><?= $aula ? 'Atualizar' : 'Salvar' ?> frequência</button></div>
  </fieldset>
</form>
