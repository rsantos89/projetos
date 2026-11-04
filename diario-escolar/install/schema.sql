-- Diário Escolar Municipal - esquema MySQL/MariaDB (utf8mb4)
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS configuracoes (
  chave VARCHAR(60) PRIMARY KEY,
  valor TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS escolas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(160) NOT NULL,
  inep VARCHAR(12) NULL,
  endereco VARCHAR(200) NULL,
  bairro VARCHAR(80) NULL,
  zona ENUM('urbana','rural') NOT NULL DEFAULT 'urbana',
  telefone VARCHAR(30) NULL,
  email VARCHAR(120) NULL,
  diretor VARCHAR(120) NULL,
  secretario VARCHAR(120) NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL,
  senha_hash VARCHAR(255) NULL,
  perfil ENUM('admin','gestor','professor') NOT NULL DEFAULT 'professor',
  escola_id INT UNSIGNED NULL,
  telefone VARCHAR(30) NULL,
  matricula_funcional VARCHAR(30) NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  google_sub VARCHAR(64) NULL,
  ultimo_acesso DATETIME NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_email (email),
  KEY idx_escola (escola_id),
  CONSTRAINT fk_usuario_escola FOREIGN KEY (escola_id) REFERENCES escolas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS anos_letivos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ano SMALLINT UNSIGNED NOT NULL,
  tipo_periodo ENUM('bimestre','trimestre','semestre') NOT NULL DEFAULT 'bimestre',
  media_aprovacao DECIMAL(4,2) NOT NULL DEFAULT 6.00,
  nota_maxima DECIMAL(4,2) NOT NULL DEFAULT 10.00,
  frequencia_minima TINYINT UNSIGNED NOT NULL DEFAULT 75,
  dias_letivos SMALLINT UNSIGNED NOT NULL DEFAULT 200,
  status ENUM('aberto','encerrado') NOT NULL DEFAULT 'aberto',
  UNIQUE KEY uk_ano (ano)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS periodos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ano_letivo_id INT UNSIGNED NOT NULL,
  numero TINYINT UNSIGNED NOT NULL,
  nome VARCHAR(40) NOT NULL,
  data_inicio DATE NULL,
  data_fim DATE NULL,
  fechado TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uk_periodo (ano_letivo_id, numero),
  CONSTRAINT fk_periodo_ano FOREIGN KEY (ano_letivo_id) REFERENCES anos_letivos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS etapas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nivel ENUM('infantil','fund1','fund2','eja') NOT NULL,
  nome VARCHAR(60) NOT NULL,
  ordem SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  avaliacao ENUM('nota','parecer') NOT NULL DEFAULT 'nota',
  ativo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS componentes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(80) NOT NULL,
  abreviacao VARCHAR(10) NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS etapa_componentes (
  etapa_id INT UNSIGNED NOT NULL,
  componente_id INT UNSIGNED NOT NULL,
  carga_horaria SMALLINT UNSIGNED NULL,
  PRIMARY KEY (etapa_id, componente_id),
  CONSTRAINT fk_ec_etapa FOREIGN KEY (etapa_id) REFERENCES etapas(id) ON DELETE CASCADE,
  CONSTRAINT fk_ec_comp FOREIGN KEY (componente_id) REFERENCES componentes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS turmas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  escola_id INT UNSIGNED NOT NULL,
  ano_letivo_id INT UNSIGNED NOT NULL,
  nome VARCHAR(60) NOT NULL,
  turno ENUM('matutino','vespertino','noturno','integral') NOT NULL DEFAULT 'matutino',
  sala VARCHAR(30) NULL,
  vagas SMALLINT UNSIGNED NOT NULL DEFAULT 30,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  KEY idx_turma_escola_ano (escola_id, ano_letivo_id),
  CONSTRAINT fk_turma_escola FOREIGN KEY (escola_id) REFERENCES escolas(id),
  CONSTRAINT fk_turma_ano FOREIGN KEY (ano_letivo_id) REFERENCES anos_letivos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Uma turma com mais de uma etapa é multisseriada
CREATE TABLE IF NOT EXISTS turma_etapas (
  turma_id INT UNSIGNED NOT NULL,
  etapa_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (turma_id, etapa_id),
  CONSTRAINT fk_te_turma FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  CONSTRAINT fk_te_etapa FOREIGN KEY (etapa_id) REFERENCES etapas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- componente_id NULL = professor polivalente (todos os componentes da turma)
CREATE TABLE IF NOT EXISTS lotacoes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  turma_id INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NOT NULL,
  componente_id INT UNSIGNED NULL,
  KEY idx_lot_usuario (usuario_id),
  KEY idx_lot_turma (turma_id),
  CONSTRAINT fk_lot_turma FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  CONSTRAINT fk_lot_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_lot_comp FOREIGN KEY (componente_id) REFERENCES componentes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS alunos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(20) NULL,
  nome VARCHAR(160) NOT NULL,
  nome_social VARCHAR(160) NULL,
  data_nascimento DATE NULL,
  sexo ENUM('F','M') NULL,
  cor_raca VARCHAR(20) NULL,
  naturalidade VARCHAR(80) NULL,
  cpf VARCHAR(14) NULL,
  nis VARCHAR(14) NULL,
  rg VARCHAR(20) NULL,
  certidao VARCHAR(60) NULL,
  cartao_sus VARCHAR(20) NULL,
  inep_aluno VARCHAR(14) NULL,
  mae VARCHAR(160) NULL,
  pai VARCHAR(160) NULL,
  responsavel VARCHAR(160) NULL,
  responsavel_parentesco VARCHAR(30) NULL,
  responsavel_cpf VARCHAR(14) NULL,
  telefone VARCHAR(30) NULL,
  telefone2 VARCHAR(30) NULL,
  email VARCHAR(120) NULL,
  cep VARCHAR(10) NULL,
  endereco VARCHAR(200) NULL,
  bairro VARCHAR(80) NULL,
  cidade VARCHAR(80) NULL,
  zona ENUM('urbana','rural') NOT NULL DEFAULT 'urbana',
  nee TINYINT(1) NOT NULL DEFAULT 0,
  deficiencia VARCHAR(200) NULL,
  transporte_escolar TINYINT(1) NOT NULL DEFAULT 0,
  bolsa_familia TINYINT(1) NOT NULL DEFAULT 0,
  saude VARCHAR(255) NULL,
  autoriza_imagem TINYINT(1) NOT NULL DEFAULT 0,
  observacoes TEXT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_codigo (codigo),
  KEY idx_aluno_nome (nome),
  KEY idx_aluno_cpf (cpf)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS matriculas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  origem_id INT UNSIGNED NULL,
  aluno_id INT UNSIGNED NOT NULL,
  turma_id INT UNSIGNED NOT NULL,
  etapa_id INT UNSIGNED NOT NULL,
  ano_letivo_id INT UNSIGNED NOT NULL,
  numero VARCHAR(20) NULL,
  data_matricula DATE NOT NULL,
  tipo ENUM('nova','rematricula','transferencia_recebida') NOT NULL DEFAULT 'nova',
  situacao ENUM('cursando','transferido','remanejado','abandono','falecido','cancelado','aprovado','reprovado','concluido') NOT NULL DEFAULT 'cursando',
  data_saida DATE NULL,
  observacao VARCHAR(255) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_mat_turma (turma_id, situacao),
  KEY idx_mat_aluno (aluno_id),
  KEY idx_mat_ano (ano_letivo_id),
  CONSTRAINT fk_mat_aluno FOREIGN KEY (aluno_id) REFERENCES alunos(id),
  CONSTRAINT fk_mat_turma FOREIGN KEY (turma_id) REFERENCES turmas(id),
  CONSTRAINT fk_mat_etapa FOREIGN KEY (etapa_id) REFERENCES etapas(id),
  CONSTRAINT fk_mat_ano FOREIGN KEY (ano_letivo_id) REFERENCES anos_letivos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registro de aula (conteúdo). componente_id NULL = aula polivalente/campos de experiência
CREATE TABLE IF NOT EXISTS aulas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  turma_id INT UNSIGNED NOT NULL,
  componente_id INT UNSIGNED NULL,
  usuario_id INT UNSIGNED NULL,
  data DATE NOT NULL,
  quantidade TINYINT UNSIGNED NOT NULL DEFAULT 1,
  conteudo TEXT NULL,
  observacao TEXT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_aula_turma (turma_id, componente_id, data),
  CONSTRAINT fk_aula_turma FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
  CONSTRAINT fk_aula_comp FOREIGN KEY (componente_id) REFERENCES componentes(id),
  CONSTRAINT fk_aula_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS frequencias (
  aula_id INT UNSIGNED NOT NULL,
  matricula_id INT UNSIGNED NOT NULL,
  faltas TINYINT UNSIGNED NOT NULL DEFAULT 0,
  justificada TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (aula_id, matricula_id),
  KEY idx_freq_mat (matricula_id),
  CONSTRAINT fk_freq_aula FOREIGN KEY (aula_id) REFERENCES aulas(id) ON DELETE CASCADE,
  CONSTRAINT fk_freq_mat FOREIGN KEY (matricula_id) REFERENCES matriculas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notas (
  matricula_id INT UNSIGNED NOT NULL,
  componente_id INT UNSIGNED NOT NULL,
  periodo_id INT UNSIGNED NOT NULL,
  nota DECIMAL(5,2) NULL,
  recuperacao DECIMAL(5,2) NULL,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (matricula_id, componente_id, periodo_id),
  CONSTRAINT fk_nota_mat FOREIGN KEY (matricula_id) REFERENCES matriculas(id) ON DELETE CASCADE,
  CONSTRAINT fk_nota_comp FOREIGN KEY (componente_id) REFERENCES componentes(id),
  CONSTRAINT fk_nota_periodo FOREIGN KEY (periodo_id) REFERENCES periodos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recuperacoes_finais (
  matricula_id INT UNSIGNED NOT NULL,
  componente_id INT UNSIGNED NOT NULL,
  nota DECIMAL(5,2) NULL,
  PRIMARY KEY (matricula_id, componente_id),
  CONSTRAINT fk_rf_mat FOREIGN KEY (matricula_id) REFERENCES matriculas(id) ON DELETE CASCADE,
  CONSTRAINT fk_rf_comp FOREIGN KEY (componente_id) REFERENCES componentes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pareceres (
  matricula_id INT UNSIGNED NOT NULL,
  periodo_id INT UNSIGNED NOT NULL,
  texto TEXT NULL,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (matricula_id, periodo_id),
  CONSTRAINT fk_par_mat FOREIGN KEY (matricula_id) REFERENCES matriculas(id) ON DELETE CASCADE,
  CONSTRAINT fk_par_periodo FOREIGN KEY (periodo_id) REFERENCES periodos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ocorrencias (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  matricula_id INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NULL,
  data DATE NOT NULL,
  tipo VARCHAR(40) NOT NULL,
  descricao TEXT NOT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_oc_mat (matricula_id),
  CONSTRAINT fk_oc_mat FOREIGN KEY (matricula_id) REFERENCES matriculas(id) ON DELETE CASCADE,
  CONSTRAINT fk_oc_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NULL,
  acao VARCHAR(60) NOT NULL,
  detalhe VARCHAR(255) NULL,
  ip VARCHAR(45) NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_log_data (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
