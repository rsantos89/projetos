<?php /** @var array $turmas */ ?>
<?php if (!$turmas): ?><div class="card empty">Nenhuma turma vinculada a você neste ano letivo.</div><?php endif ?>
<div class="grid cards">
<?php foreach ($turmas as $t): $et = turma_etapas((int) $t['id']); $n = (int) val("SELECT COUNT(*) FROM matriculas WHERE turma_id = ? AND situacao = 'cursando'", [$t['id']]); $parecer = turma_so_parecer((int) $t['id']); ?>
  <div class="tcard">
    <h3><?= e(turma_nome($t)) ?></h3>
    <div class="muted"><?= e($t['escola_nome']) ?></div>
    <div><?php foreach ($et as $e): ?><span class="badge"><?= e($e['nome']) ?></span> <?php endforeach ?><?php if (count($et) > 1): ?><span class="badge warn">Multisseriada</span><?php endif ?></div>
    <div class="muted"><?= $n ?> aluno(s)</div>
    <div class="actions">
      <a class="btn primary sm" href="<?= url('diario/frequencia', ['turma_id' => $t['id']]) ?>">Frequência</a>
      <a class="btn sm" href="<?= url('diario/aulas', ['turma_id' => $t['id']]) ?>">Aulas</a>
      <?php if (!$parecer): ?><a class="btn sm" href="<?= url('diario/notas', ['turma_id' => $t['id']]) ?>">Notas</a><?php endif ?>
      <?php if ($parecer || count(array_filter($et, fn($e) => $e['avaliacao'] === 'parecer'))): ?><a class="btn sm" href="<?= url('diario/pareceres', ['turma_id' => $t['id']]) ?>">Pareceres</a><?php endif ?>
      <a class="btn sm" href="<?= url('diario/ocorrencias', ['turma_id' => $t['id']]) ?>">Ocorrências</a>
    </div>
  </div>
<?php endforeach ?>
</div>
