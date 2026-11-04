<?php
require_role('admin', 'gestor');
$title = 'Alunos';
$u = user();
$busca = trim((string) g('busca'));
$params = [ano_id()];
$where = '1=1';
if ($busca !== '') {
    $where .= ' AND (a.nome LIKE ? OR a.nome_social LIKE ? OR a.cpf = ? OR a.codigo = ? OR a.nis = ? OR a.mae LIKE ?)';
    array_push($params, "%$busca%", "%$busca%", $busca, $busca, $busca, "%$busca%");
} elseif ($u['perfil'] === 'gestor') {
    $where .= ' AND t.escola_id = ?';
    $params[] = $u['escola_id'];
} else {
    $where .= ' AND m.id IS NOT NULL';
}
if (g('sem_matricula')) $where .= ' AND m.id IS NULL';
$alunos = all("SELECT a.id, a.nome, a.nome_social, a.codigo, a.data_nascimento, a.mae, a.nee, m.id mid, t.nome turma, t.turno, s.nome escola, e.nome etapa
    FROM alunos a
    LEFT JOIN matriculas m ON m.aluno_id = a.id AND m.ano_letivo_id = ? AND m.situacao = 'cursando'
    LEFT JOIN turmas t ON t.id = m.turma_id LEFT JOIN escolas s ON s.id = t.escola_id LEFT JOIN etapas e ON e.id = m.etapa_id
    WHERE $where ORDER BY a.nome LIMIT 300", $params);
?>
<div class="page-head"><h1>Alunos</h1><a class="btn primary" href="<?= url('alunos/form') ?>">+ Novo aluno</a>
<span class="sub">A busca procura em toda a rede municipal (nome, nome da mãe, CPF, NIS ou código) para evitar cadastros duplicados.</span></div>
<form class="filters" method="get"><input type="hidden" name="r" value="alunos">
  <label>Buscar<input name="busca" value="<?= e($busca) ?>" placeholder="Nome, mãe, CPF, NIS ou código" autofocus></label>
  <label class="check"><input type="checkbox" name="sem_matricula" value="1"<?= checked(g('sem_matricula')) ?>> Sem matrícula em <?= ano()['ano'] ?? '' ?></label>
  <button class="btn">Buscar</button></form>
<div class="table-wrap"><table><thead><tr><th>Aluno</th><th>Nascimento</th><th>Mãe</th><th>Matrícula <?= ano()['ano'] ?? '' ?></th><th></th></tr></thead><tbody>
<?php foreach ($alunos as $a): ?>
<tr><td><a href="<?= url('alunos/ver', ['id' => $a['id']]) ?>"><?= e(nome_aluno($a)) ?></a> <?= $a['nee'] ? '<span class="badge">NEE</span>' : '' ?><span class="etapa-tag">Cód. <?= e($a['codigo']) ?></span></td>
<td><?= fdate($a['data_nascimento']) ?></td><td><?= e($a['mae']) ?></td>
<td><?php if ($a['mid']): ?><?= e($a['etapa']) ?> · <?= e($a['turma']) ?><span class="etapa-tag"><?= e($a['escola']) ?></span><?php else: ?><a class="btn sm primary" href="<?= url('matriculas/nova', ['aluno_id' => $a['id']]) ?>">Matricular</a><?php endif ?></td>
<td><a class="btn sm" href="<?= url('alunos/ver', ['id' => $a['id']]) ?>">Abrir</a></td></tr>
<?php endforeach; if (!$alunos): ?><tr><td colspan="5" class="empty"><?= $busca ? 'Nenhum aluno encontrado. <a href="' . url('alunos/form', ['nome' => $busca]) . '">Cadastrar novo</a>' : 'Nenhum aluno.' ?></td></tr><?php endif ?>
</tbody></table></div>
