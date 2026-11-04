<?php
/* Regras do diário: frequência, médias e situação final */

function periodo_da_data(string $data, ?int $anoId = null): ?array
{
    return one('SELECT * FROM periodos WHERE ano_letivo_id = ? AND ? BETWEEN data_inicio AND data_fim', [$anoId ?? ano_id(), $data]);
}

/** Professor só edita com ano aberto e período não fechado; gestão edita sempre (admin inclusive ano encerrado). */
function pode_editar(?array $periodo = null, ?array $anoRow = null): bool
{
    $u = user();
    $anoRow ??= ano();
    if ($u['perfil'] === 'admin') return true;
    if (($anoRow['status'] ?? '') === 'encerrado') return false;
    if ($u['perfil'] === 'gestor') return true;
    return !($periodo['fechado'] ?? 0);
}

/** [matricula_id => ['aulas'=>, 'faltas'=>, 'justificadas'=>]] considerando somente aulas em que o aluno constava */
/** Mapa [matrícula (inclusive as de origem, em remanejamentos/transferências) => matrícula solicitada] */
function matricula_cadeia(array $mids): array
{
    $map = [];
    foreach ($mids as $m) $map[(int) $m] = (int) $m;
    $atual = array_keys($map);
    for ($i = 0; $i < 10 && $atual; $i++) {
        $prox = [];
        foreach (all('SELECT id, origem_id FROM matriculas WHERE origem_id IS NOT NULL AND id IN (' . in_list($atual) . ')') as $r) {
            if (isset($map[(int) $r['origem_id']])) continue;
            $map[(int) $r['origem_id']] = $map[(int) $r['id']];
            $prox[] = (int) $r['origem_id'];
        }
        $atual = $prox;
    }
    return $map;
}
function frequencia_resumo(array $mids, ?int $componenteId = null, ?array $periodo = null): array
{
    if (!$mids) return [];
    $cadeia = matricula_cadeia($mids);
    $mids = array_keys($cadeia);
    $sql = 'SELECT f.matricula_id, SUM(a.quantidade) aulas, SUM(CASE WHEN f.justificada = 0 THEN f.faltas ELSE 0 END) faltas,
        SUM(CASE WHEN f.justificada = 1 THEN f.faltas ELSE 0 END) justificadas
        FROM frequencias f JOIN aulas a ON a.id = f.aula_id WHERE f.matricula_id IN (' . in_list($mids) . ')';
    $p = [];
    if ($componenteId) { $sql .= ' AND a.componente_id = ?'; $p[] = $componenteId; }
    if ($periodo) { $sql .= ' AND a.data BETWEEN ? AND ?'; $p[] = $periodo['data_inicio']; $p[] = $periodo['data_fim']; }
    $out = [];
    foreach (all($sql . ' GROUP BY f.matricula_id', $p) as $r) {
        $dest = $cadeia[(int) $r['matricula_id']];
        $o = $out[$dest] ?? ['matricula_id' => $dest, 'aulas' => 0, 'faltas' => 0, 'justificadas' => 0];
        foreach (['aulas', 'faltas', 'justificadas'] as $k) $o[$k] += (int) $r[$k];
        $out[$dest] = $o;
    }
    return $out;
}
function perc_freq(?array $r): ?float
{
    if (!$r || !$r['aulas']) return null;
    return round(100 * ($r['aulas'] - $r['faltas']) / $r['aulas'], 1);
}

/**
 * Consolida notas/frequência por matrícula.
 * Retorna [mid => ['comps' => [cid => ['p' => [pid => nota efetiva], 'media', 'rf', 'final', 'faltas']], 'freq' => %, 'situacao' => calculada, 'pendente' => bool]]
 */
function consolidar(array $matriculas, array $componentes, array $periodos, array $anoRow): array
{
    $mids = array_column($matriculas, 'id');
    if (!$mids) return [];
    $notas = [];
    foreach (all('SELECT * FROM notas WHERE matricula_id IN (' . in_list($mids) . ')') as $n) $notas[$n['matricula_id']][$n['componente_id']][$n['periodo_id']] = $n;
    $rfs = [];
    foreach (all('SELECT * FROM recuperacoes_finais WHERE matricula_id IN (' . in_list($mids) . ')') as $r) $rfs[$r['matricula_id']][$r['componente_id']] = $r['nota'];
    $faltasComp = [];
    $cadeia = matricula_cadeia($mids);
    foreach (all('SELECT f.matricula_id, a.componente_id, SUM(f.faltas) faltas FROM frequencias f JOIN aulas a ON a.id = f.aula_id
        WHERE f.matricula_id IN (' . in_list(array_keys($cadeia)) . ') AND f.justificada = 0 GROUP BY f.matricula_id, a.componente_id') as $r) {
        $dest = $cadeia[(int) $r['matricula_id']];
        $faltasComp[$dest][(int) $r['componente_id']] = ($faltasComp[$dest][(int) $r['componente_id']] ?? 0) + (int) $r['faltas'];
    }
    $matriz = [];
    foreach (all('SELECT etapa_id, componente_id FROM etapa_componentes') as $r) $matriz[(int) $r['etapa_id']][] = (int) $r['componente_id'];
    $freq = frequencia_resumo($mids);
    $media = (float) $anoRow['media_aprovacao'];
    $nPer = count($periodos);
    $out = [];
    foreach ($matriculas as $m) {
        $mid = $m['id'];
        $res = ['comps' => [], 'freq' => perc_freq($freq[$mid] ?? null), 'faltas' => $freq[$mid]['faltas'] ?? 0, 'aulas' => $freq[$mid]['aulas'] ?? 0, 'pendente' => false];
        $reprovaNota = false;
        if (($m['avaliacao'] ?? 'nota') === 'nota') {
            foreach ($componentes as $c) {
                $cid = $c['id'];
                if (isset($m['etapa_id']) && !in_array((int) $cid, $matriz[(int) $m['etapa_id']] ?? [], true)) continue;
                $ps = [];
                foreach ($periodos as $per) {
                    $n = $notas[$mid][$cid][$per['id']] ?? null;
                    $vals = array_filter([$n['nota'] ?? null, $n['recuperacao'] ?? null], fn($v) => $v !== null);
                    $ps[$per['id']] = $vals ? max(array_map('floatval', $vals)) : null;
                }
                $lanc = array_filter($ps, fn($v) => $v !== null);
                $med = $lanc ? round(array_sum($lanc) / count($lanc), 2) : null;
                $rf = isset($rfs[$mid][$cid]) ? (float) $rfs[$mid][$cid] : null;
                $final = $med;
                if ($med !== null && $med < $media && $rf !== null) $final = max($med, $rf);
                if (count($lanc) < $nPer) $res['pendente'] = true;
                if ($final !== null && $final < $media) $reprovaNota = true;
                $res['comps'][$cid] = ['p' => $ps, 'media' => $med, 'rf' => $rf, 'final' => $final, 'faltas' => $faltasComp[$mid][$cid] ?? 0];
            }
        }
        if (($m['avaliacao'] ?? 'nota') === 'parecer') $sit = 'concluido';
        elseif ($res['pendente']) $sit = null;
        else $sit = ($reprovaNota || ($res['freq'] !== null && $res['freq'] < (float) $anoRow['frequencia_minima'])) ? 'reprovado' : 'aprovado';
        $res['situacao'] = $sit;
        $out[$mid] = $res;
    }
    return $out;
}

function diario_tabs(array $t, string $ativa): string
{
    $tid = (int) $t['id'];
    $parecer = turma_so_parecer($tid);
    $temParecer = (bool) val("SELECT 1 FROM turma_etapas te JOIN etapas e ON e.id = te.etapa_id WHERE te.turma_id = ? AND e.avaliacao = 'parecer'", [$tid]);
    $tabs = ['frequencia' => 'Frequência', 'aulas' => 'Aulas / conteúdos'];
    if (!$parecer) $tabs['notas'] = 'Notas';
    if ($temParecer) $tabs['pareceres'] = 'Pareceres';
    $tabs['ocorrencias'] = 'Ocorrências';
    $h = '<div class="page-head"><h1>' . e($t['nome']) . '</h1><span class="sub">' . e($t['escola_nome']) . ' · ' . e(TURNOS[$t['turno']]) . ' · ';
    $et = turma_etapas($tid);
    $h .= e(implode(' + ', array_column($et, 'nome'))) . (count($et) > 1 ? ' <span class="badge warn">Multisseriada</span>' : '') . '</span></div><nav class="tabs">';
    foreach ($tabs as $k => $l) $h .= '<a href="' . url("diario/$k", ['turma_id' => $tid]) . '" class="' . ($k === $ativa ? 'active' : '') . '">' . $l . '</a>';
    $h .= '<a href="' . url('fechamento', ['turma_id' => $tid]) . '" class="' . ($ativa === 'fechamento' ? 'active' : '') . '">Fechamento</a>';
    $h .= '<a href="' . url('relatorios', ['turma_id' => $tid]) . '">Relatórios</a></nav>';
    if (($GLOBALS['_ano_aviso'] ?? true) && (ano()['status'] ?? '') === 'encerrado') $h .= '<div class="alert warn">Ano letivo encerrado: somente leitura.</div>';
    return $h;
}
