<?php
require_role('admin');
$title = 'Registro de atividades';
$logs = all('SELECT l.*, u.nome FROM logs l LEFT JOIN usuarios u ON u.id = l.usuario_id ORDER BY l.id DESC LIMIT 500');
?>
<div class="page-head"><h1>Registro de atividades</h1><span class="sub">Últimos 500 eventos.</span></div>
<input data-filter="#tlogs" placeholder="Filtrar..." style="max-width:320px;margin-bottom:12px">
<div class="table-wrap compact"><table id="tlogs"><thead><tr><th>Data</th><th>Usuário</th><th>Ação</th><th>Detalhe</th><th>IP</th></tr></thead><tbody>
<?php foreach ($logs as $l): ?><tr><td><?= date('d/m/Y H:i', strtotime($l['criado_em'])) ?></td><td><?= e($l['nome'] ?? '—') ?></td><td><?= e($l['acao']) ?></td><td><?= e($l['detalhe']) ?></td><td><?= e($l['ip']) ?></td></tr><?php endforeach ?>
</tbody></table></div>
