<?php
require_role('admin');
$id = gi('id');
$a = $id ? one('SELECT * FROM anos_letivos WHERE id = ?', [$id]) : ['ano' => (int) date('Y') + (val('SELECT 1 FROM anos_letivos WHERE ano = ?', [date('Y')]) ? 1 : 0), 'tipo_periodo' => 'bimestre', 'media_aprovacao' => 6, 'nota_maxima' => 10, 'frequencia_minima' => 75, 'dias_letivos' => 200, 'status' => 'aberto'];
if (!$a) not_found();
$title = $id ? 'Ano letivo ' . $a['ano'] : 'Novo ano letivo';
$qtd = ['bimestre' => 4, 'trimestre' => 3, 'semestre' => 2];
$pers = $id ? periodos($id) : [];

if (is_post()) {
    $d = ['ano' => (int) p('ano'), 'tipo_periodo' => array_key_exists(p('tipo_periodo'), $qtd) ? p('tipo_periodo') : 'bimestre',
        'media_aprovacao' => parse_num(p('media_aprovacao')) ?? 6, 'nota_maxima' => parse_num(p('nota_maxima')) ?? 10,
        'frequencia_minima' => (int) p('frequencia_minima'), 'dias_letivos' => (int) p('dias_letivos'), 'status' => p('status') === 'encerrado' ? 'encerrado' : 'aberto'];
    try {
        db()->beginTransaction();
        if ($id) update('anos_letivos', $d, 'id = ?', [$id]); else $id = insert('anos_letivos', $d);
        $n = $qtd[$d['tipo_periodo']];
        $nomes = ['bimestre' => 'Bimestre', 'trimestre' => 'Trimestre', 'semestre' => 'Semestre'];
        for ($i = 1; $i <= $n; $i++) {
            $row = ['nome' => p("per_nome_$i") ?: "{$i}º " . $nomes[$d['tipo_periodo']], 'data_inicio' => nn(p("per_ini_$i")), 'data_fim' => nn(p("per_fim_$i")), 'fechado' => p("per_fechado_$i") ? 1 : 0];
            if (val('SELECT 1 FROM periodos WHERE ano_letivo_id = ? AND numero = ?', [$id, $i])) update('periodos', $row, 'ano_letivo_id = ? AND numero = ?', [$id, $i]);
            else insert('periodos', $row + ['ano_letivo_id' => $id, 'numero' => $i]);
        }
        q('DELETE FROM periodos WHERE ano_letivo_id = ? AND numero > ?', [$id, $n]);
        db()->commit();
        audit('ano_salvar', (string) $d['ano']);
        flash('success', 'Ano letivo salvo.');
        $_SESSION['ano_id'] = $id;
        redirect('anos');
    } catch (PDOException $e) {
        db()->rollBack();
        flash('error', $e->getCode() == 23000 ? 'Já existe esse ano letivo, ou há notas lançadas nos períodos removidos.' : 'Erro ao salvar.');
        back('anos');
    }
}
$n = $qtd[$a['tipo_periodo']];
$byNum = array_column($pers, null, 'numero');
?>
<div class="page-head"><h1><?= e($title) ?></h1></div>
<form method="post" class="card">
  <?= csrf_field() ?>
  <div class="row"><label>Ano<input type="number" name="ano" required value="<?= e($a['ano']) ?>"></label>
    <label>Divisão<select name="tipo_periodo"><?= options(['bimestre' => 'Bimestral (4)', 'trimestre' => 'Trimestral (3)', 'semestre' => 'Semestral (2)'], $a['tipo_periodo']) ?></select></label>
    <label>Status<select name="status"><?= options(['aberto' => 'Aberto', 'encerrado' => 'Encerrado (somente leitura)'], $a['status']) ?></select></label></div>
  <div class="row"><label>Média para aprovação<input name="media_aprovacao" inputmode="decimal" value="<?= fnum($a['media_aprovacao']) ?>"></label><label>Nota máxima<input name="nota_maxima" inputmode="decimal" value="<?= fnum($a['nota_maxima']) ?>"></label>
    <label>Frequência mínima (%)<input type="number" name="frequencia_minima" min="0" max="100" value="<?= e($a['frequencia_minima']) ?>"></label><label>Dias letivos previstos<input type="number" name="dias_letivos" value="<?= e($a['dias_letivos']) ?>"></label></div>
  <h3>Períodos</h3>
  <p class="muted">Ao mudar a divisão, salve para ajustar a quantidade de períodos. Período fechado bloqueia lançamentos de professores.</p>
  <div class="table-wrap compact"><table><thead><tr><th>#</th><th>Nome</th><th>Início</th><th>Fim</th><th>Fechado</th></tr></thead><tbody>
  <?php for ($i = 1; $i <= max($n, count($pers)); $i++): $pp = $byNum[$i] ?? []; ?>
    <tr><td><?= $i ?></td><td><input name="per_nome_<?= $i ?>" value="<?= e($pp['nome'] ?? '') ?>" placeholder="<?= $i ?>º período"></td><td><input type="date" name="per_ini_<?= $i ?>" value="<?= e($pp['data_inicio'] ?? '') ?>"></td><td><input type="date" name="per_fim_<?= $i ?>" value="<?= e($pp['data_fim'] ?? '') ?>"></td><td><input type="checkbox" name="per_fechado_<?= $i ?>" value="1"<?= checked($pp['fechado'] ?? 0) ?>></td></tr>
  <?php endfor ?>
  </tbody></table></div>
  <div class="form-foot"><a class="btn" href="<?= url('anos') ?>">Cancelar</a><button class="btn primary">Salvar</button></div>
</form>
