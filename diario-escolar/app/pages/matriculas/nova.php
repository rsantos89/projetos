<?php
require_role('admin', 'gestor');
require __DIR__ . '/_lib.php';
$title = 'Nova matrícula';
$u = user();
$alunoId = gi('aluno_id') ?: (int) p('aluno_id');
$turmaId = gi('turma_id') ?: (int) p('turma_id');
$aluno = $alunoId ? one('SELECT * FROM alunos WHERE id = ?', [$alunoId]) : null;
$turma = $turmaId ? turma_ou_404($turmaId, true) : null;

if (is_post() && $aluno && $turma) {
    try {
        if (vagas_livres($turmaId) <= 0 && !p('exceder')) throw new RuntimeException('Turma sem vagas. Marque a opção para exceder o limite, se autorizado.');
        db()->beginTransaction();
        $mid = criar_matricula($alunoId, $turmaId, (int) p('etapa_id'), (string) p('data_matricula'), p('tipo') === 'transferencia_recebida' ? 'transferencia_recebida' : 'nova', nn(p('observacao')));
        db()->commit();
        audit('matricula_nova', "aluno $alunoId turma $turmaId");
        flash('success', 'Matrícula realizada com sucesso.');
        redirect('turmas/ver', ['id' => $turmaId]);
    } catch (RuntimeException $e) {
        if (db()->inTransaction()) db()->rollBack();
        flash('error', $e->getMessage());
        back();
    }
}

$atual = $aluno ? aluno_cursando($alunoId, ano_id()) : null;
$busca = trim((string) g('busca'));
$achados = (!$aluno && $busca !== '') ? all('SELECT id, nome, data_nascimento, mae, codigo FROM alunos WHERE nome LIKE ? OR cpf = ? OR codigo = ? ORDER BY nome LIMIT 30', ["%$busca%", $busca, $busca]) : [];
$turmas = turmas_para_matricula($u['perfil'] === 'gestor' ? (int) $u['escola_id'] : null);
$etapas = $turma ? turma_etapas($turmaId) : [];
?>
<div class="page-head"><h1>Nova matrícula · <?= ano()['ano'] ?? '' ?></h1></div>
<?php if ((ano()['status'] ?? '') === 'encerrado'): ?><div class="alert warn">Atenção: o ano letivo selecionado está encerrado.</div><?php endif ?>

<div class="card">
  <h2>1. Aluno</h2>
  <?php if ($aluno): ?>
    <p><b><?= e(nome_aluno($aluno)) ?></b> · Nasc. <?= fdate($aluno['data_nascimento']) ?> · Mãe: <?= e($aluno['mae']) ?>
      <a class="btn sm ghost" href="<?= url('matriculas/nova', array_filter(['turma_id' => $turmaId])) ?>">Trocar</a></p>
    <?php if ($atual): ?><div class="alert error">Este aluno já está matriculado em <?= e($atual['escola']) ?> · <?= e($atual['turma']) ?>. Para mudar de turma use <a href="<?= url('matriculas/mover', ['id' => $atual['id']]) ?>">Movimentar</a>.</div><?php endif ?>
  <?php else: ?>
    <form class="filters" method="get"><input type="hidden" name="r" value="matriculas/nova"><?php if ($turmaId): ?><input type="hidden" name="turma_id" value="<?= $turmaId ?>"><?php endif ?>
      <label>Buscar aluno na rede<input name="busca" value="<?= e($busca) ?>" placeholder="Nome, CPF ou código" autofocus></label><button class="btn">Buscar</button>
      <a class="btn primary" href="<?= url('alunos/form', $busca ? ['nome' => $busca] : []) ?>">+ Cadastrar novo aluno</a></form>
    <?php if ($achados): ?><div class="table-wrap"><table><tbody><?php foreach ($achados as $x): ?><tr><td><?= e($x['nome']) ?><span class="etapa-tag">Nasc. <?= fdate($x['data_nascimento']) ?> · Mãe: <?= e($x['mae']) ?></span></td>
      <td><a class="btn sm primary" href="<?= url('matriculas/nova', array_filter(['aluno_id' => $x['id'], 'turma_id' => $turmaId])) ?>">Selecionar</a></td></tr><?php endforeach ?></tbody></table></div>
    <?php elseif ($busca): ?><p class="muted">Nenhum aluno encontrado.</p><?php endif ?>
  <?php endif ?>
</div>

<?php if ($aluno && !$atual): ?>
<div class="card">
  <h2>2. Turma</h2>
  <form method="get" class="filters"><input type="hidden" name="r" value="matriculas/nova"><input type="hidden" name="aluno_id" value="<?= $alunoId ?>">
    <label>Turma<select name="turma_id" data-autosubmit><?= options($turmas, $turmaId, 'id', 'label', 'Selecione a turma...') ?></select></label></form>
  <?php if ($turma): $livres = vagas_livres($turmaId); ?>
  <form method="post">
    <?= csrf_field() ?><input type="hidden" name="aluno_id" value="<?= $alunoId ?>"><input type="hidden" name="turma_id" value="<?= $turmaId ?>">
    <div class="row">
      <label>Etapa / série <?= count($etapas) > 1 ? '(turma multisseriada)' : '' ?><select name="etapa_id" required><?= options($etapas, count($etapas) === 1 ? $etapas[0]['id'] : null, 'id', 'nome', count($etapas) > 1 ? 'Selecione...' : null) ?></select></label>
      <label>Data da matrícula<input type="date" name="data_matricula" value="<?= date('Y-m-d') ?>" required></label>
      <label>Tipo<select name="tipo"><?= options(['nova' => 'Matrícula nova', 'transferencia_recebida' => 'Transferência recebida (outra rede)'], 'nova') ?></select></label>
    </div>
    <label>Observação<input name="observacao" maxlength="255"></label>
    <?php if ($livres <= 0): ?><label class="check alert warn" style="margin-top:12px"><input type="checkbox" name="exceder" value="1"> Turma sem vagas (<?= $livres ?>). Autorizo exceder o limite.</label><?php else: ?><p class="muted"><?= $livres ?> vaga(s) disponível(is).</p><?php endif ?>
    <div class="form-foot"><button class="btn primary">Confirmar matrícula</button></div>
  </form>
  <?php endif ?>
</div>
<?php endif;
