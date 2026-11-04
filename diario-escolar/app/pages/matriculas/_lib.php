<?php
function gerar_numero_matricula(int $id, ?int $ano = null): string { return ($ano ?? ano()['ano'] ?? date('Y')) . str_pad((string) $id, 5, '0', STR_PAD_LEFT); }

function aluno_cursando(int $alunoId, int $anoId): ?array
{
    return one("SELECT m.*, t.nome turma, s.nome escola FROM matriculas m JOIN turmas t ON t.id = m.turma_id JOIN escolas s ON s.id = t.escola_id
        WHERE m.aluno_id = ? AND m.ano_letivo_id = ? AND m.situacao = 'cursando'", [$alunoId, $anoId]);
}

function vagas_livres(int $turmaId): int
{
    return (int) val("SELECT t.vagas - (SELECT COUNT(*) FROM matriculas m WHERE m.turma_id = t.id AND m.situacao = 'cursando') FROM turmas t WHERE t.id = ?", [$turmaId]);
}

function criar_matricula(int $alunoId, int $turmaId, int $etapaId, string $data, string $tipo, ?string $obs = null): int
{
    $t = one('SELECT * FROM turmas WHERE id = ?', [$turmaId]);
    if (!val('SELECT 1 FROM turma_etapas WHERE turma_id = ? AND etapa_id = ?', [$turmaId, $etapaId])) throw new RuntimeException('A etapa não pertence à turma.');
    if (aluno_cursando($alunoId, (int) $t['ano_letivo_id'])) throw new RuntimeException('O aluno já possui matrícula ativa neste ano letivo.');
    $id = insert('matriculas', ['aluno_id' => $alunoId, 'turma_id' => $turmaId, 'etapa_id' => $etapaId, 'ano_letivo_id' => $t['ano_letivo_id'],
        'data_matricula' => $data ?: date('Y-m-d'), 'tipo' => $tipo, 'observacao' => $obs]);
    update('matriculas', ['numero' => gerar_numero_matricula($id, (int) val('SELECT ano FROM anos_letivos WHERE id = ?', [$t['ano_letivo_id']]))], 'id = ?', [$id]);
    return $id;
}

/** Copia notas, pareceres e frequência registrada (como faltas) para nova matrícula (remanejamento/transferência na rede) */
function copiar_registros(int $de, int $para): void
{
    q('INSERT IGNORE INTO notas (matricula_id, componente_id, periodo_id, nota, recuperacao) SELECT ?, componente_id, periodo_id, nota, recuperacao FROM notas WHERE matricula_id = ?', [$para, $de]);
    update('matriculas', ['origem_id' => $de], 'id = ?', [$para]);
    q('INSERT IGNORE INTO recuperacoes_finais (matricula_id, componente_id, nota) SELECT ?, componente_id, nota FROM recuperacoes_finais WHERE matricula_id = ?', [$para, $de]);
    q('INSERT IGNORE INTO pareceres (matricula_id, periodo_id, texto) SELECT ?, periodo_id, texto FROM pareceres WHERE matricula_id = ?', [$para, $de]);
}

function matricula_editavel(array $m): bool
{
    $u = user();
    if ($u['perfil'] === 'admin') return true;
    return $u['perfil'] === 'gestor' && (int) $m['escola_id'] === (int) $u['escola_id'];
}

/** Turmas do ano corrente com etapas para selects */
function turmas_para_matricula(?int $escolaId = null): array
{
    $sql = "SELECT t.id, t.nome, t.turno, t.vagas, s.nome escola_nome, t.escola_id,
        (SELECT COUNT(*) FROM matriculas m WHERE m.turma_id = t.id AND m.situacao = 'cursando') ocupadas
        FROM turmas t JOIN escolas s ON s.id = t.escola_id WHERE t.ano_letivo_id = ? AND t.ativo = 1";
    $p = [ano_id()];
    if ($escolaId) { $sql .= ' AND t.escola_id = ?'; $p[] = $escolaId; }
    $rows = all($sql . ' ORDER BY s.nome, t.nome', $p);
    foreach ($rows as &$r) $r['label'] = ($escolaId ? '' : $r['escola_nome'] . ' · ') . turma_nome($r) . " ({$r['ocupadas']}/{$r['vagas']})";
    return $rows;
}
