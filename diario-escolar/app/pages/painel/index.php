<?php
$title = 'Início';
$u = user();
$anoId = ano_id();
$escolaFiltro = $u['perfil'] === 'gestor' ? ' AND t.escola_id = ' . (int) $u['escola_id'] : '';

if ($u['perfil'] === 'professor') {
    $turmas = all("SELECT DISTINCT t.*, s.nome AS escola_nome FROM lotacoes l JOIN turmas t ON t.id = l.turma_id JOIN escolas s ON s.id = t.escola_id
        WHERE l.usuario_id = ? AND t.ano_letivo_id = ? AND t.ativo = 1 ORDER BY s.nome, t.nome", [$u['id'], $anoId]);
    $hoje = (int) val('SELECT COUNT(*) FROM aulas WHERE usuario_id = ? AND data = CURDATE()', [$u['id']]);
    ?>
    <div class="page-head"><h1>Olá, <?= e(explode(' ', $u['nome'])[0]) ?>!</h1><span class="sub"><?= fdate(date('Y-m-d')) ?> · <?= $hoje ?> aula(s) registrada(s) hoje</span></div>
    <?php require ROOT . '/app/views/_turma_cards.php';
    return;
}

$stats = [
    'Escolas' => $u['perfil'] === 'gestor' ? 1 : (int) val('SELECT COUNT(*) FROM escolas WHERE ativo = 1'),
    'Turmas' => (int) val("SELECT COUNT(*) FROM turmas t WHERE t.ano_letivo_id = ? AND t.ativo = 1 $escolaFiltro", [$anoId]),
    'Alunos cursando' => (int) val("SELECT COUNT(*) FROM matriculas m JOIN turmas t ON t.id = m.turma_id WHERE m.ano_letivo_id = ? AND m.situacao = 'cursando' $escolaFiltro", [$anoId]),
    'Professores' => (int) val("SELECT COUNT(DISTINCT l.usuario_id) FROM lotacoes l JOIN turmas t ON t.id = l.turma_id WHERE t.ano_letivo_id = ? $escolaFiltro", [$anoId]),
    'Aulas nos últimos 7 dias' => (int) val("SELECT COUNT(*) FROM aulas a JOIN turmas t ON t.id = a.turma_id WHERE a.data >= CURDATE() - INTERVAL 7 DAY $escolaFiltro"),
];
$porEscola = all("SELECT s.id, s.nome, COUNT(DISTINCT t.id) turmas, COALESCE(SUM(t.vagas),0) vagas,
    (SELECT COUNT(*) FROM matriculas m JOIN turmas t2 ON t2.id = m.turma_id WHERE t2.escola_id = s.id AND m.ano_letivo_id = ? AND m.situacao = 'cursando') alunos
    FROM escolas s LEFT JOIN turmas t ON t.escola_id = s.id AND t.ano_letivo_id = ? AND t.ativo = 1
    WHERE s.ativo = 1" . ($u['perfil'] === 'gestor' ? ' AND s.id = ' . (int) $u['escola_id'] : '') . ' GROUP BY s.id ORDER BY s.nome', [$anoId, $anoId]);
$porNivel = all("SELECT e.nivel, COUNT(*) n FROM matriculas m JOIN etapas e ON e.id = m.etapa_id JOIN turmas t ON t.id = m.turma_id
    WHERE m.ano_letivo_id = ? AND m.situacao = 'cursando' $escolaFiltro GROUP BY e.nivel", [$anoId]);
$movs = all("SELECT m.situacao, COUNT(*) n FROM matriculas m JOIN turmas t ON t.id = m.turma_id WHERE m.ano_letivo_id = ? $escolaFiltro GROUP BY m.situacao", [$anoId]);
$multis = (int) val("SELECT COUNT(*) FROM (SELECT te.turma_id FROM turma_etapas te JOIN turmas t ON t.id = te.turma_id WHERE t.ano_letivo_id = ? $escolaFiltro GROUP BY te.turma_id HAVING COUNT(*) > 1) x", [$anoId]);
$semAula = all("SELECT t.id, t.nome, t.turno, s.nome escola_nome, MAX(a.data) ultima FROM turmas t JOIN escolas s ON s.id = t.escola_id
    LEFT JOIN aulas a ON a.turma_id = t.id WHERE t.ano_letivo_id = ? AND t.ativo = 1 $escolaFiltro GROUP BY t.id
    HAVING ultima IS NULL OR ultima < CURDATE() - INTERVAL 5 DAY ORDER BY ultima LIMIT 10", [$anoId]);
?>
<div class="page-head"><h1>Painel <?= ano() ? ano()['ano'] : '' ?></h1>
  <div class="actions"><a class="btn primary" href="<?= url('alunos/form') ?>">+ Novo aluno</a><a class="btn" href="<?= url('turmas/form') ?>">+ Nova turma</a></div>
</div>
<div class="grid stats" style="margin-bottom:18px">
  <?php foreach ($stats as $k => $v): ?><div class="stat"><b><?= number_format($v, 0, ',', '.') ?></b><span><?= e($k) ?></span></div><?php endforeach ?>
  <div class="stat"><b><?= $multis ?></b><span>Turmas multisseriadas</span></div>
</div>
<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(320px,1fr))">
  <div class="card"><h2>Alunos por etapa de ensino</h2>
    <?php $tot = array_sum(array_column($porNivel, 'n')) ?: 1; foreach ($porNivel as $n): ?>
      <p style="margin:0 0 4px"><?= e(NIVEIS[$n['nivel']]) ?> <b style="float:right"><?= $n['n'] ?></b></p><div class="bar" style="margin-bottom:10px"><i style="width:<?= round($n['n'] / $tot * 100) ?>%"></i></div>
    <?php endforeach; if (!$porNivel): ?><p class="muted">Nenhuma matrícula no ano.</p><?php endif ?>
  </div>
  <div class="card"><h2>Situação das matrículas</h2>
    <div class="actions"><?php foreach ($movs as $m): ?><span><?= situacao_badge($m['situacao']) ?> <?= $m['n'] ?></span><?php endforeach ?></div>
    <?php if (!$movs): ?><p class="muted">Sem dados.</p><?php endif ?>
  </div>
</div>
<h2>Escolas</h2>
<div class="table-wrap"><table>
  <thead><tr><th>Escola</th><th class="num">Turmas</th><th class="num">Alunos</th><th class="num">Vagas</th><th class="num">Ocupação</th></tr></thead>
  <tbody><?php foreach ($porEscola as $s): $oc = $s['vagas'] ? round($s['alunos'] / $s['vagas'] * 100) : 0 ?>
    <tr><td><a href="<?= url('turmas', ['escola_id' => $s['id']]) ?>"><?= e($s['nome']) ?></a></td><td class="num"><?= $s['turmas'] ?></td><td class="num"><?= $s['alunos'] ?></td><td class="num"><?= $s['vagas'] ?></td>
      <td class="num"><span class="badge <?= $oc > 100 ? 'bad' : ($oc > 90 ? 'warn' : 'ok') ?>"><?= $oc ?>%</span></td></tr>
  <?php endforeach; if (!$porEscola): ?><tr><td colspan="5" class="empty">Nenhuma escola cadastrada.</td></tr><?php endif ?></tbody>
</table></div>
<?php if ($semAula): ?>
<h2>Turmas sem registro de aula há mais de 5 dias</h2>
<div class="table-wrap"><table><thead><tr><th>Turma</th><th>Escola</th><th>Último registro</th></tr></thead><tbody>
<?php foreach ($semAula as $t): ?><tr><td><a href="<?= url('turmas/ver', ['id' => $t['id']]) ?>"><?= e(turma_nome($t)) ?></a></td><td><?= e($t['escola_nome']) ?></td><td><?= $t['ultima'] ? fdate($t['ultima']) : '<span class="badge warn">Nunca</span>' ?></td></tr><?php endforeach ?>
</tbody></table></div>
<?php endif;
