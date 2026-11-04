<?php
require_role('admin', 'gestor');
$id = gi('id');
$campos = ['nome', 'nome_social', 'data_nascimento', 'sexo', 'cor_raca', 'naturalidade', 'cpf', 'nis', 'rg', 'certidao', 'cartao_sus', 'inep_aluno',
    'mae', 'pai', 'responsavel', 'responsavel_parentesco', 'responsavel_cpf', 'telefone', 'telefone2', 'email', 'cep', 'endereco', 'bairro', 'cidade', 'zona',
    'nee', 'deficiencia', 'transporte_escolar', 'bolsa_familia', 'saude', 'autoriza_imagem', 'observacoes'];
$flags = ['nee', 'transporte_escolar', 'bolsa_familia', 'autoriza_imagem'];
$a = $id ? one('SELECT * FROM alunos WHERE id = ?', [$id]) : array_fill_keys($campos, '') + ['zona' => 'urbana', 'codigo' => '', 'cidade' => setting('municipio') . (setting('uf') ? '/' . setting('uf') : '')];
if (!$a) not_found();
if (!$id && g('nome')) $a['nome'] = g('nome');
$title = $id ? 'Editar aluno' : 'Novo aluno';

if (is_post()) {
    $d = [];
    foreach ($campos as $c) $d[$c] = in_array($c, $flags, true) ? (p($c) ? 1 : 0) : nn(p($c));
    $d['nome'] = mb_strtoupper((string) $d['nome']);
    $d['zona'] = $d['zona'] === 'rural' ? 'rural' : 'urbana';
    if (!in_array($d['sexo'], ['F', 'M'], true)) $d['sexo'] = null;
    if (!$d['nome']) { flash('error', 'Informe o nome do aluno.'); back(); }
    if ($d['cpf'] && val('SELECT id FROM alunos WHERE cpf = ? AND id <> ?', [$d['cpf'], $id])) { flash('error', 'Já existe aluno com este CPF.'); back(); }
    if (!$id && !p('confirmar') && ($dup = one('SELECT id, nome, data_nascimento FROM alunos WHERE nome = ? AND (data_nascimento <=> ?)', [$d['nome'], $d['data_nascimento']]))) {
        flash('warn', 'Já existe um aluno com mesmo nome e data de nascimento (código ' . $dup['id'] . '). Marque "confirmar" para cadastrar mesmo assim.');
        $_SESSION['old'] = $_POST;
        back();
    }
    if ($id) {
        update('alunos', $d, 'id = ?', [$id]);
    } else {
        $id = insert('alunos', $d);
        update('alunos', ['codigo' => date('Y') . str_pad((string) $id, 6, '0', STR_PAD_LEFT)], 'id = ?', [$id]);
    }
    audit('aluno_salvar', $d['nome']);
    flash('success', 'Aluno salvo.');
    if (!val("SELECT 1 FROM matriculas WHERE aluno_id = ? AND ano_letivo_id = ? AND situacao = 'cursando'", [$id, ano_id()]))
        redirect('matriculas/nova', ['aluno_id' => $id]);
    redirect('alunos/ver', ['id' => $id]);
}
if (!empty($_SESSION['old'])) { $a = array_merge($a, array_intersect_key($_SESSION['old'], $a)); $dupWarn = true; unset($_SESSION['old']); }
$in = fn($c, $label, $extra = '') => '<label>' . $label . '<input name="' . $c . '" value="' . e($a[$c]) . '" ' . $extra . '></label>';
?>
<div class="page-head"><h1><?= $title ?></h1><?= $id ? '<span class="sub">Código ' . e($a['codigo']) . '</span>' : '' ?></div>
<form method="post" data-dirty>
  <?= csrf_field() ?>
  <fieldset class="card"><legend>Identificação</legend>
    <div class="row"><?= $in('nome', 'Nome completo', 'required') ?><?= $in('nome_social', 'Nome social') ?></div>
    <div class="row"><?= $in('data_nascimento', 'Data de nascimento', 'type="date" required') ?>
      <label>Sexo<select name="sexo"><?= options(['F' => 'Feminino', 'M' => 'Masculino'], $a['sexo'], 'id', 'nome', '—') ?></select></label>
      <label>Cor/raça<select name="cor_raca"><?= options(array_combine($cr = ['Não declarada', 'Branca', 'Preta', 'Parda', 'Amarela', 'Indígena'], $cr), $a['cor_raca']) ?></select></label>
      <?= $in('naturalidade', 'Naturalidade') ?></div>
  </fieldset>
  <fieldset class="card"><legend>Documentos</legend>
    <div class="row"><?= $in('cpf', 'CPF', 'data-mask="cpf" inputmode="numeric"') ?><?= $in('nis', 'NIS', 'inputmode="numeric"') ?><?= $in('rg', 'RG') ?></div>
    <div class="row"><?= $in('certidao', 'Certidão de nascimento (matrícula)') ?><?= $in('cartao_sus', 'Cartão SUS') ?><?= $in('inep_aluno', 'ID INEP do aluno') ?></div>
  </fieldset>
  <fieldset class="card"><legend>Filiação e responsável</legend>
    <div class="row"><?= $in('mae', 'Nome da mãe') ?><?= $in('pai', 'Nome do pai') ?></div>
    <div class="row"><?= $in('responsavel', 'Responsável legal') ?><?= $in('responsavel_parentesco', 'Parentesco') ?><?= $in('responsavel_cpf', 'CPF do responsável', 'data-mask="cpf"') ?></div>
    <div class="row"><?= $in('telefone', 'Telefone', 'data-mask="tel" inputmode="tel"') ?><?= $in('telefone2', 'Telefone 2', 'data-mask="tel" inputmode="tel"') ?><?= $in('email', 'E-mail', 'type="email"') ?></div>
  </fieldset>
  <fieldset class="card"><legend>Endereço</legend>
    <div class="row"><?= $in('cep', 'CEP', 'data-mask="cep" data-cep inputmode="numeric"') ?><?= $in('endereco', 'Endereço') ?><?= $in('bairro', 'Bairro/Localidade') ?></div>
    <div class="row"><?= $in('cidade', 'Cidade/UF') ?><label>Zona<select name="zona"><?= options(['urbana' => 'Urbana', 'rural' => 'Rural'], $a['zona']) ?></select></label></div>
  </fieldset>
  <fieldset class="card"><legend>Informações complementares</legend>
    <div class="actions" style="margin-bottom:12px">
      <label class="check"><input type="checkbox" name="nee" value="1"<?= checked($a['nee']) ?>> Necessidades educacionais especiais</label>
      <label class="check"><input type="checkbox" name="transporte_escolar" value="1"<?= checked($a['transporte_escolar']) ?>> Usa transporte escolar</label>
      <label class="check"><input type="checkbox" name="bolsa_familia" value="1"<?= checked($a['bolsa_familia']) ?>> Bolsa Família</label>
      <label class="check"><input type="checkbox" name="autoriza_imagem" value="1"<?= checked($a['autoriza_imagem']) ?>> Autoriza uso de imagem</label>
    </div>
    <div class="row"><?= $in('deficiencia', 'Deficiência / TGD / altas habilidades') ?><?= $in('saude', 'Saúde (alergias, medicamentos)') ?></div>
    <label>Observações<textarea name="observacoes"><?= e($a['observacoes']) ?></textarea></label>
  </fieldset>
  <?php if (!empty($dupWarn)): ?><label class="check alert warn"><input type="checkbox" name="confirmar" value="1"> Confirmo que é outro aluno com mesmo nome e nascimento</label><?php endif ?>
  <div class="form-foot"><a class="btn" href="<?= $id ? url('alunos/ver', ['id' => $id]) : url('alunos') ?>">Cancelar</a><button class="btn primary">Salvar<?= $id ? '' : ' e matricular' ?></button></div>
</form>
