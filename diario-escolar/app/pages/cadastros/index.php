<?php
require_role('admin');
$title = 'Cadastros da rede';
$itens = [
    ['escolas', 'Escolas', 'Unidades da rede municipal, INEP, direção.', (int) val('SELECT COUNT(*) FROM escolas')],
    ['anos', 'Anos letivos e períodos', 'Bimestres/trimestres, média e frequência mínima.', (int) val('SELECT COUNT(*) FROM anos_letivos')],
    ['etapas', 'Etapas / séries', 'Creche, Pré-escola, 1º ao 9º ano, matriz curricular.', (int) val('SELECT COUNT(*) FROM etapas')],
    ['componentes', 'Componentes curriculares', 'Disciplinas da BNCC e parte diversificada.', (int) val('SELECT COUNT(*) FROM componentes')],
    ['config', 'Configurações', 'Identidade da rede e login com Google.', null],
    ['logs', 'Registro de atividades', 'Auditoria de acessos e alterações.', null],
];
?>
<div class="page-head"><h1>Cadastros da rede</h1></div>
<div class="grid cards">
<?php foreach ($itens as [$r, $n, $d, $c]): ?>
  <a class="tcard" href="<?= url($r) ?>" style="color:inherit;text-decoration:none"><h3><?= e($n) ?><?= $c !== null ? " <span class=\"badge\">$c</span>" : '' ?></h3><span class="muted"><?= e($d) ?></span></a>
<?php endforeach ?>
</div>
