<?php
declare(strict_types=1);

const APP_VERSION = '1.0.0';
define('ROOT', dirname(__DIR__));

$GLOBALS['config'] = is_file(ROOT . '/config.php') ? require ROOT . '/config.php' : null;
date_default_timezone_set($GLOBALS['config']['timezone'] ?? 'America/Sao_Paulo');
mb_internal_encoding('UTF-8');

/* ---------- Banco de dados ---------- */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = $GLOBALS['config']['db'];
        $dsn = 'mysql:host=' . $c['host'] . ';dbname=' . $c['name'] . ';charset=utf8mb4' . (!empty($c['port']) ? ';port=' . $c['port'] : '');
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '" . date('P') . "'");
    }
    return $pdo;
}

function q(string $sql, array $p = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($p);
    return $st;
}
function all(string $sql, array $p = []): array { return q($sql, $p)->fetchAll(); }
function one(string $sql, array $p = []): ?array { $r = q($sql, $p)->fetch(); return $r ?: null; }
function val(string $sql, array $p = []) { $r = q($sql, $p)->fetchColumn(); return $r === false ? null : $r; }

function insert(string $table, array $data): int
{
    $cols = array_keys($data);
    q('INSERT INTO ' . $table . ' (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')', array_values($data));
    return (int) db()->lastInsertId();
}
function update(string $table, array $data, string $where, array $params = []): void
{
    $set = implode(',', array_map(fn($c) => "$c = ?", array_keys($data)));
    q("UPDATE $table SET $set WHERE $where", array_merge(array_values($data), $params));
}
function in_list(array $ids): string
{
    $ids = array_map('intval', $ids);
    return $ids ? implode(',', $ids) : '0';
}

/* ---------- Utilidades ---------- */
function e($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
function g(string $k, $d = null) { return $_GET[$k] ?? $d; }
function p(string $k, $d = null) { $v = $_POST[$k] ?? $d; return is_string($v) ? trim($v) : $v; }
function nn($v) { return ($v === '' || $v === null) ? null : $v; }
function gi(string $k): int { return (int) ($_GET[$k] ?? 0); }

function base_path(): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $dir = preg_replace('#/install$#', '', $dir);
    return rtrim($dir, '/') . '/';
}
function base_url(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . base_path();
}
function url(string $route = '', array $params = []): string
{
    $qs = $route !== '' ? array_merge(['r' => $route], $params) : $params;
    return base_path() . ($qs ? '?' . http_build_query($qs) : '');
}
function asset(string $f): string
{
    $v = @filemtime(ROOT . '/assets/' . $f) ?: APP_VERSION;
    return base_path() . 'assets/' . $f . '?v=' . $v;
}
function redirect(string $route = '', array $params = []): never
{
    header('Location: ' . url($route, $params));
    exit;
}
function back(string $fallback = 'painel'): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    if ($ref && parse_url($ref, PHP_URL_HOST) === ($_SERVER['HTTP_HOST'] ?? '')) {
        header('Location: ' . $ref);
        exit;
    }
    redirect($fallback);
}

function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void
{
    if (!hash_equals(csrf_token(), (string) ($_POST['_csrf'] ?? ''))) {
        http_response_code(419);
        exit('Sessão expirada. Volte e tente novamente.');
    }
}
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }

function fdate(?string $d): string { return $d ? date('d/m/Y', strtotime($d)) : ''; }
function fnum($n, int $dec = 1): string { return $n === null || $n === '' ? '' : number_format((float) $n, $dec, ',', '.'); }
function parse_num($v): ?float
{
    $v = trim((string) $v);
    if ($v === '') return null;
    return (float) str_replace(',', '.', $v);
}
function idade(?string $nasc): string
{
    if (!$nasc) return '';
    return (string) (new DateTime($nasc))->diff(new DateTime())->y;
}

function options(array $rows, $selected = null, string $k = 'id', string $label = 'nome', ?string $empty = null): string
{
    $h = $empty !== null ? '<option value="">' . e($empty) . '</option>' : '';
    foreach ($rows as $key => $r) {
        $v = is_array($r) ? $r[$k] : $key;
        $l = is_array($r) ? $r[$label] : $r;
        $sel = (string) $v === (string) $selected ? ' selected' : '';
        $h .= '<option value="' . e($v) . "\"$sel>" . e($l) . '</option>';
    }
    return $h;
}
function checked($v): string { return $v ? ' checked' : ''; }

function setting(string $k, string $d = ''): string
{
    static $cache = null;
    if ($cache === null) {
        try { $cache = array_column(all('SELECT chave, valor FROM configuracoes'), 'valor', 'chave'); }
        catch (Throwable $t) { $cache = []; }
    }
    return (string) ($cache[$k] ?? $d);
}
function set_setting(string $k, ?string $v): void
{
    q('INSERT INTO configuracoes (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)', [$k, $v]);
}

function audit(string $acao, string $detalhe = ''): void
{
    try {
        insert('logs', ['usuario_id' => $_SESSION['uid'] ?? null, 'acao' => $acao, 'detalhe' => mb_substr($detalhe, 0, 255), 'ip' => $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Throwable $t) {
    }
}

/* ---------- Domínio ---------- */
const PERFIS = ['admin' => 'Secretaria Municipal', 'gestor' => 'Gestão Escolar', 'professor' => 'Professor(a)'];
const NIVEIS = ['infantil' => 'Educação Infantil', 'fund1' => 'Ensino Fundamental I', 'fund2' => 'Ensino Fundamental II', 'eja' => 'EJA'];
const TURNOS = ['matutino' => 'Matutino', 'vespertino' => 'Vespertino', 'noturno' => 'Noturno', 'integral' => 'Integral'];
const SITUACOES = [
    'cursando' => 'Cursando', 'transferido' => 'Transferido', 'remanejado' => 'Remanejado', 'abandono' => 'Abandono',
    'falecido' => 'Falecido', 'cancelado' => 'Cancelado', 'aprovado' => 'Aprovado', 'reprovado' => 'Reprovado', 'concluido' => 'Concluído',
];
const TIPOS_OCORRENCIA = ['Pedagógica', 'Disciplinar', 'Saúde', 'Elogio', 'Comunicado à família', 'Outro'];

function situacao_badge(string $s): string
{
    $cls = ['cursando' => 'ok', 'aprovado' => 'ok', 'concluido' => 'ok', 'reprovado' => 'bad', 'abandono' => 'bad', 'falecido' => 'muted', 'cancelado' => 'muted'][$s] ?? 'warn';
    return '<span class="badge ' . $cls . '">' . e(SITUACOES[$s] ?? $s) . '</span>';
}

/** Ano letivo em uso na sessão */
function ano(): ?array
{
    static $ano = false;
    if ($ano !== false) return $ano;
    $id = (int) ($_SESSION['ano_id'] ?? 0);
    $ano = $id ? one('SELECT * FROM anos_letivos WHERE id = ?', [$id]) : null;
    if (!$ano) {
        $ano = one("SELECT * FROM anos_letivos ORDER BY (status = 'aberto' AND ano <= ?) DESC, (status = 'aberto') DESC, ano DESC LIMIT 1", [(int) date('Y')]);
        if ($ano) $_SESSION['ano_id'] = $ano['id'];
    }
    return $ano;
}
function ano_id(): int { return (int) (ano()['id'] ?? 0); }
function periodos(?int $anoId = null): array
{
    return all('SELECT * FROM periodos WHERE ano_letivo_id = ? ORDER BY numero', [$anoId ?? ano_id()]);
}

function turma_etapas(int $turmaId): array
{
    return all('SELECT e.* FROM turma_etapas te JOIN etapas e ON e.id = te.etapa_id WHERE te.turma_id = ? ORDER BY e.ordem', [$turmaId]);
}
function turma_nome(array $t): string { return $t['nome'] . ' - ' . (TURNOS[$t['turno']] ?? $t['turno']); }

/** Componentes da turma (união das matrizes das etapas) */
function turma_componentes(int $turmaId): array
{
    return all('SELECT DISTINCT c.* FROM turma_etapas te JOIN etapa_componentes ec ON ec.etapa_id = te.etapa_id
        JOIN componentes c ON c.id = ec.componente_id WHERE te.turma_id = ? AND c.ativo = 1 ORDER BY c.nome', [$turmaId]);
}

/** Alunos da turma; $ativos=true só cursando */
function turma_alunos(int $turmaId, bool $ativos = false): array
{
    return all('SELECT m.*, a.nome, a.nome_social, a.data_nascimento, a.sexo, a.nee, e.nome AS etapa_nome, e.avaliacao, e.nivel
        FROM matriculas m JOIN alunos a ON a.id = m.aluno_id JOIN etapas e ON e.id = m.etapa_id
        WHERE m.turma_id = ?' . ($ativos ? " AND m.situacao = 'cursando'" : '') . ' ORDER BY a.nome', [$turmaId]);
}
function nome_aluno(array $a): string { return !empty($a['nome_social']) ? $a['nome_social'] : $a['nome']; }

/** Valida turma e permissão (gestor da escola ou professor lotado) */
function turma_ou_404(int $id, bool $somenteGestao = false): array
{
    $t = one('SELECT t.*, s.nome AS escola_nome FROM turmas t JOIN escolas s ON s.id = t.escola_id WHERE t.id = ?', [$id]);
    if (!$t) not_found();
    $u = user();
    if ($u['perfil'] === 'gestor' && (int) $u['escola_id'] !== (int) $t['escola_id']) forbidden();
    if ($u['perfil'] === 'professor') {
        if ($somenteGestao) forbidden();
        if (!val('SELECT 1 FROM lotacoes WHERE turma_id = ? AND usuario_id = ?', [$id, $u['id']])) forbidden();
    }
    return $t;
}

/** Componentes que o usuário pode lançar na turma. Retorna [] para professor polivalente com acesso total. */
function componentes_permitidos(int $turmaId): array
{
    $comps = turma_componentes($turmaId);
    $u = user();
    if ($u['perfil'] !== 'professor') return $comps;
    $lot = all('SELECT componente_id FROM lotacoes WHERE turma_id = ? AND usuario_id = ?', [$turmaId, $u['id']]);
    $ids = array_column($lot, 'componente_id');
    if (in_array(null, $ids, true)) return $comps;
    return array_values(array_filter($comps, fn($c) => in_array($c['id'], $ids)));
}
function turma_so_parecer(int $turmaId): bool
{
    return !val("SELECT 1 FROM turma_etapas te JOIN etapas e ON e.id = te.etapa_id WHERE te.turma_id = ? AND e.avaliacao = 'nota'", [$turmaId]);
}

/** Escolas visíveis ao usuário */
function escolas_visiveis(): array
{
    $u = user();
    if ($u['perfil'] === 'gestor') return all('SELECT * FROM escolas WHERE id = ?', [$u['escola_id']]);
    return all('SELECT * FROM escolas WHERE ativo = 1 ORDER BY nome');
}

/* ---------- Autenticação ---------- */
function user(): ?array
{
    static $u = false;
    if ($u !== false) return $u;
    $u = null;
    if (!empty($_SESSION['uid'])) {
        $u = one('SELECT * FROM usuarios WHERE id = ? AND ativo = 1', [$_SESSION['uid']]);
        if (!$u) unset($_SESSION['uid']);
    }
    return $u;
}
function is_role(string ...$roles): bool { $u = user(); return $u && in_array($u['perfil'], $roles, true); }
function require_role(string ...$roles): void { if (!is_role(...$roles)) forbidden(); }

function login_user(array $u, string $via): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $u['id'];
    update('usuarios', ['ultimo_acesso' => date('Y-m-d H:i:s')], 'id = ?', [$u['id']]);
    audit('login', "via $via");
}

function forbidden(): never
{
    http_response_code(403);
    render_error('Acesso negado', 'Você não tem permissão para acessar esta página.');
}
function not_found(): never
{
    http_response_code(404);
    render_error('Não encontrado', 'O registro ou página solicitada não existe.');
}
function render_error(string $title, string $msg): never
{
    while (ob_get_level()) ob_end_clean();
    $GLOBALS['title'] = $title;
    $content = '<div class="card"><h2>' . e($title) . '</h2><p>' . e($msg) . '</p><p><a class="btn" href="' . url('painel') . '">Voltar ao início</a></p></div>';
    if (user()) layout($content);
    else echo $content;
    exit;
}

/* ---------- Renderização ---------- */
function layout(string $content, string $tpl = 'layout'): void
{
    $title = $GLOBALS['title'] ?? '';
    require ROOT . "/app/views/$tpl.php";
}

/* ---------- HTTP (Google OAuth) ---------- */
function http_post(string $url, array $data): ?array
{
    $body = http_build_query($data);
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']]);
        $res = curl_exec($ch);
        curl_close($ch);
    } else {
        $res = @file_get_contents($url, false, stream_context_create(['http' => [
            'method' => 'POST', 'header' => 'Content-Type: application/x-www-form-urlencoded', 'content' => $body, 'timeout' => 15, 'ignore_errors' => true]]));
    }
    return $res ? json_decode($res, true) : null;
}
require_once __DIR__ . '/diario.php';

function b64url_decode(string $s): string { return base64_decode(strtr($s, '-_', '+/') . str_repeat('=', (4 - strlen($s) % 4) % 4)); }
