<?php
require_role('admin', 'gestor');
require __DIR__ . '/_lib.php';
$id = gi('id');
$m = one('SELECT m.*, a.nome, a.nome_social, t.nome turma, t.turno, t.escola_id, s.nome escola, e.nome etapa FROM matriculas m JOIN alunos a ON a.id = m.aluno_id
    JOIN turmas t ON t.id = m.turma_id JOIN escolas s ON s.id = t.escola_id JOIN etapas e ON e.id = m.etapa_id WHERE m.id = ?', [$id]);
if (!$m) not_found();
if (!matricula_editavel($m)) forbidden();
$title = 'Movimentar matrícula';

if (is_post()) {
    $acao = (string) p('acao');
    $data = (string) (p('data') ?: date('Y-m-d'));
    $obs = nn(p('observacao'));
    try {
        db()->beginTransaction();
        switch ($acao) {
            case 'remanejar':
            case 'transferir_rede':
                if ($m['situacao'] !== 'cursando') throw new RuntimeException('Somente matrículas ativas podem ser movimentadas.');
                $novaTurma = (int) p('turma_id');
                $nt = one('SELECT * FROM turmas WHERE id = ? AND ano_letivo_id = ?', [$novaTurma, $m['ano_letivo_id']]);
                if (!$nt || $novaTurma === (int) $m['turma_id']) throw new RuntimeException('Selecione outra turma do mesmo ano letivo.');
                if ($acao === 'remanejar' && user()['perfil'] === 'gestor' && $nt['escola_id'] != $m['escola_id']) throw new RuntimeException('Remanejamento é dentro da escola. Use transferência na rede.');
                update('matriculas', ['situacao' => $acao === 'remanejar' ? 'remanejado' : 'transferido', 'data_saida' => $data, 'observacao' => $obs ?? ('Para ' . $nt['nome'])], 'id = ?', [$id]);
                $nova = criar_matricula((int) $m['aluno_id'], $novaTurma, (int) p('etapa_id'), $data, $acao === 'remanejar' ? 'nova' : 'transferencia_recebida', 'Vindo de ' . $m['escola'] . ' · ' . $m['turma']);
                copiar_registros($id, $nova);
                $msg = $acao === 'remanejar' ? 'Aluno remanejado.' : 'Aluno transferido dentro da rede.';
                break;
            case 'transferido':
            case 'abandono':
            case 'falecido':
            case 'cancelado':
                if ($m['situacao'] !== 'cursando') throw new RuntimeException('Somente matrículas ativas podem ser encerradas.');
                update('matriculas', ['situacao' => $acao, 'data_saida' => $data, 'observacao' => $obs], 'id = ?', [$id]);
                $msg = 'Situação atualizada: ' . SITUACOES[$acao] . '.';
                break;
            case 'reativar':
                if (aluno_cursando((int) $m['aluno_id'], (int) $m['ano_letivo_id'])) throw new RuntimeException('O aluno já tem outra matrícula ativa no ano.');
                update('matriculas', ['situacao' => 'cursando', 'data_saida' => null, 'observacao' => $obs], 'id = ?', [$id]);
                $msg = 'Matrícula reativada.';
                break;
            case 'etapa':
                if (!val('SELECT 1 FROM turma_etapas WHERE turma_id = ? AND etapa_id = ?', [$m['turma_id'], (int) p('etapa_id')])) throw new RuntimeException('Etapa inválida.');
                update('matriculas', ['etapa_id' => (int) p('etapa_id')], 'id = ?', [$id]);
                $msg = 'Etapa alterada.';
                break;
            default:
                throw new RuntimeException('Ação inválida.');
        }
        db()->commit();
        audit('matricula_' . $acao, "matricula $id");
        flash('success', $msg);
        redirect('turmas/ver', ['id' => $m['turma_id']]);
    } catch (RuntimeException $e) {
        if (db()->inTransaction()) db()->rollBack();
        flash('error', $e->getMessage());
        back();
    }
}

$u = user();
$turmasEscola = array_filter(turmas_para_matricula((int) $m['escola_id']), fn($t) => $t['id'] != $m['turma_id']);
$turmasRede = array_filter(turmas_para_matricula(), fn($t) => $t['escola_id'] != $m['escola_id']);
$etapasTodas = all('SELECT * FROM etapas WHERE ativo = 1 ORDER BY ordem');
$etapasTurma = turma_etapas((int) $m['turma_id']);
?>
<div class="page-head"><h1>Movimentar matrícula</h1>
  <span class="sub"><b><?= e(nome_aluno($m)) ?></b> · <?= e($m['escola']) ?> · <?= e($m['turma']) ?> · <?= e($m['etapa']) ?> · <?= situacao_badge($m['situacao']) ?></span></div>

<?php if ($m['situacao'] === 'cursando'): ?>
<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(320px,1fr))">
  <form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="acao" value="remanejar">
    <h2>Remanejar (mesma escola)</h2>
    <p class="muted">Muda o aluno de turma. Notas e pareceres são copiados.</p>
    <label>Nova turma<select name="turma_id" required><?= options($turmasEscola, null, 'id', 'label', 'Selecione...') ?></select></label>
    <label>Etapa<select name="etapa_id" required><?= options($etapasTodas, $m['etapa_id']) ?></select></label>
    <label>Data<input type="date" name="data" value="<?= date('Y-m-d') ?>"></label>
    <div class="actions" style="margin-top:12px"><button class="btn primary">Remanejar</button></div>
  </form>
  <form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="acao" value="transferir_rede">
    <h2>Transferir para outra escola da rede</h2>
    <p class="muted">Encerra aqui como "transferido" e matricula na escola de destino, levando notas e pareceres.</p>
    <label>Turma de destino<select name="turma_id" required><?= options($turmasRede, null, 'id', 'label', 'Selecione...') ?></select></label>
    <label>Etapa<select name="etapa_id" required><?= options($etapasTodas, $m['etapa_id']) ?></select></label>
    <label>Data<input type="date" name="data" value="<?= date('Y-m-d') ?>"></label>
    <div class="actions" style="margin-top:12px"><button class="btn primary">Transferir</button></div>
  </form>
  <form method="post" class="card"><?= csrf_field() ?>
    <h2>Encerrar matrícula</h2>
    <label>Motivo<select name="acao"><?= options(['transferido' => 'Transferência para fora da rede', 'abandono' => 'Abandono / deixou de frequentar', 'falecido' => 'Falecimento', 'cancelado' => 'Cancelamento (matrícula indevida)'], 'transferido') ?></select></label>
    <label>Data<input type="date" name="data" value="<?= date('Y-m-d') ?>"></label>
    <label>Destino / observação<input name="observacao" maxlength="255" placeholder="Ex.: Escola Estadual X - Município Y"></label>
    <div class="actions" style="margin-top:12px"><button class="btn danger" data-confirm="Confirmar encerramento da matrícula?">Encerrar</button></div>
  </form>
  <?php if (count($etapasTurma) > 1): ?>
  <form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="acao" value="etapa">
    <h2>Corrigir etapa (multisseriada)</h2>
    <label>Etapa<select name="etapa_id"><?= options($etapasTurma, $m['etapa_id']) ?></select></label>
    <div class="actions" style="margin-top:12px"><button class="btn">Salvar etapa</button></div>
  </form>
  <?php endif ?>
</div>
<?php else: ?>
<form method="post" class="card"><?= csrf_field() ?><input type="hidden" name="acao" value="reativar">
  <p>Matrícula encerrada em <?= fdate($m['data_saida']) ?>. <?= e($m['observacao']) ?></p>
  <label>Observação<input name="observacao" maxlength="255"></label>
  <div class="actions" style="margin-top:12px"><button class="btn primary" data-confirm="Reativar esta matrícula como cursando?">Reativar matrícula</button></div>
</form>
<?php endif;
