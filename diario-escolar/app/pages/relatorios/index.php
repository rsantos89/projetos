<?php
$title = 'Relatórios';
$u = user();
if ($u['perfil'] === 'professor') $turmas = all('SELECT DISTINCT t.*, s.nome escola_nome FROM lotacoes l JOIN turmas t ON t.id = l.turma_id JOIN escolas s ON s.id = t.escola_id WHERE l.usuario_id = ? AND t.ano_letivo_id = ? ORDER BY s.nome, t.nome', [$u['id'], ano_id()]);
else $turmas = all('SELECT t.*, s.nome escola_nome FROM turmas t JOIN escolas s ON s.id = t.escola_id WHERE t.ano_letivo_id = ?' . ($u['perfil'] === 'gestor' ? ' AND t.escola_id = ' . (int) $u['escola_id'] : '') . ' ORDER BY s.nome, t.nome', [ano_id()]);
foreach ($turmas as &$t) $t['label'] = ($u['perfil'] === 'admin' ? $t['escola_nome'] . ' · ' : '') . turma_nome($t);
unset($t);
$tid = gi('turma_id');
$comps = $tid ? componentes_permitidos($tid) : [];
?>
<div class="page-head"><h1>Relatórios</h1><span class="sub">Os relatórios abrem prontos para impressão ou para salvar em PDF.</span></div>
<form class="filters" method="get"><input type="hidden" name="r" value="relatorios"><label>Turma<select name="turma_id" data-autosubmit><?= options($turmas, $tid, 'id', 'label', 'Selecione a turma...') ?></select></label></form>
<?php if ($tid): turma_ou_404($tid); ?>
<div class="grid cards" style="margin-bottom:18px">
  <div class="tcard"><h3>Lista de alunos</h3><span class="muted">Relação nominal com etapa, nascimento e responsável.</span><div class="actions"><a class="btn sm primary" href="<?= url('relatorios/lista', ['turma_id' => $tid]) ?>">Abrir</a></div></div>
  <form class="tcard" method="get"><h3>Diário de classe (frequência mensal)</h3><input type="hidden" name="r" value="relatorios/diario"><input type="hidden" name="turma_id" value="<?= $tid ?>">
    <div class="row"><label>Mês<input type="month" name="mes" value="<?= date('Y-m') ?>"></label><label>Componente<select name="componente_id"><?php if (professor_polivalente($tid)): ?><option value="">Diária</option><?php endif ?><option value="todos">Todos</option><?= options($comps) ?></select></label></div>
    <div class="actions"><button class="btn sm primary">Abrir</button></div></form>
  <div class="tcard"><h3>Boletins da turma</h3><span class="muted">Notas/pareceres e frequência por período.</span><div class="actions"><a class="btn sm primary" href="<?= url('relatorios/boletim', ['turma_id' => $tid]) ?>">Abrir</a></div></div>
  <div class="tcard"><h3>Ata de resultados finais</h3><span class="muted">Médias finais, frequência e resultado.</span><div class="actions"><a class="btn sm primary" href="<?= url('relatorios/ata', ['turma_id' => $tid]) ?>">Abrir</a></div></div>
</div>
<?php endif ?>
<?php if ($u['perfil'] !== 'professor'): ?>
<h2>Relatórios da rede / escola</h2>
<div class="grid cards">
  <div class="tcard"><h3>Quantitativo de matrículas</h3><span class="muted">Por escola e etapa, sexo, zona, NEE e transporte.</span><div class="actions"><a class="btn sm primary" href="<?= url('relatorios/rede') ?>">Abrir</a></div></div>
  <div class="tcard"><h3>Alunos infrequentes</h3><span class="muted">Frequência abaixo do mínimo (busca ativa / Conselho Tutelar).</span><div class="actions"><a class="btn sm primary" href="<?= url('relatorios/infrequentes') ?>">Abrir</a></div></div>
</div>
<?php endif;
