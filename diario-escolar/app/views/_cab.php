<div class="head"><img src="<?= asset('icon.svg') ?>" alt=""><div>
  <h1><?= e(setting('secretaria', 'Secretaria Municipal de Educação')) ?> · <?= e(setting('municipio')) ?><?= setting('uf') ? '/' . e(setting('uf')) : '' ?></h1>
  <?php if (!empty($cabEscola)): ?><p><b><?= e($cabEscola['nome']) ?></b><?= $cabEscola['inep'] ? ' · INEP ' . e($cabEscola['inep']) : '' ?><?= $cabEscola['endereco'] ? ' · ' . e($cabEscola['endereco']) : '' ?></p><?php endif ?>
</div></div>
