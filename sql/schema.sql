-- Schema do site Conta Lelê — Fase 1
-- Thiago Mourão — https://github.com/MouraoBSB
-- Codificação: utf8mb4 / InnoDB

CREATE TABLE IF NOT EXISTS usuarios_admin (
    id                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome                 VARCHAR(120) NOT NULL,
    email                VARCHAR(160) NOT NULL,
    senha_hash           VARCHAR(255) NOT NULL,
    google_id            VARCHAR(40)  NULL,
    precisa_trocar_senha TINYINT(1)   NOT NULL DEFAULT 1,
    tentativas_login     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado_ate        DATETIME     NULL,
    ultimo_acesso        DATETIME     NULL,
    ativo                TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_admin_email (email),
    UNIQUE KEY uq_usuarios_admin_google (google_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cordeis (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    titulo        VARCHAR(160) NOT NULL,
    slug          VARCHAR(180) NOT NULL,
    sinopse       TEXT         NOT NULL,
    video_youtube VARCHAR(255) NULL,
    imagem_capa   VARCHAR(255) NULL,
    ordem         INT          NOT NULL DEFAULT 0,
    publicado     TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cordeis_slug (slug),
    KEY ix_cordeis_listagem (publicado, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ebooks (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    titulo        VARCHAR(160) NOT NULL,
    slug          VARCHAR(180) NOT NULL,
    sinopse       TEXT         NOT NULL,
    arquivo_pdf   VARCHAR(255) NULL,
    imagem_capa   VARCHAR(255) NULL,
    ordem         INT          NOT NULL DEFAULT 0,
    publicado     TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ebooks_slug (slug),
    KEY ix_ebooks_listagem (publicado, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS noticias (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    titulo          VARCHAR(200) NOT NULL,
    veiculo         VARCHAR(120) NULL,
    url             VARCHAR(255) NOT NULL,
    imagem          VARCHAR(255) NULL,
    data_publicacao DATE         NULL,
    ordem           INT          NOT NULL DEFAULT 0,
    publicado       TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_noticias_listagem (publicado, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS depoimentos (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    autor     VARCHAR(120) NOT NULL,
    texto     TEXT         NOT NULL,
    foto      VARCHAR(255) NULL,
    ordem     INT          NOT NULL DEFAULT 0,
    publicado TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_depoimentos_listagem (publicado, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS festivais (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    titulo    VARCHAR(200) NOT NULL,
    descricao TEXT         NOT NULL,
    ano       SMALLINT     NULL,
    videos    TEXT         NULL,
    imagem    VARCHAR(255) NULL,
    ordem     INT          NOT NULL DEFAULT 0,
    publicado TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_festivais_listagem (publicado, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS premios (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    titulo    VARCHAR(200) NOT NULL,
    ano       SMALLINT     NULL,
    ordem     INT          NOT NULL DEFAULT 0,
    publicado TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY ix_premios_listagem (publicado, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mensagens_contato (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome      VARCHAR(120) NOT NULL,
    email     VARCHAR(160) NOT NULL,
    telefone  VARCHAR(40)  NULL,
    assunto   VARCHAR(160) NULL,
    mensagem  TEXT         NOT NULL,
    lida      TINYINT(1)   NOT NULL DEFAULT 0,
    ip        VARCHAR(45)  NULL,
    criado_em DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_mensagens_lida (lida, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configuracoes (
    chave     VARCHAR(60)  NOT NULL,
    valor     TEXT         NULL,
    descricao VARCHAR(200) NULL,
    PRIMARY KEY (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cursistas (usuários comuns / alunos) — Fase 2
CREATE TABLE IF NOT EXISTS usuarios_cursistas (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome              VARCHAR(120) NOT NULL,
    email             VARCHAR(160) NOT NULL,
    senha_hash        VARCHAR(255) NULL,
    google_id         VARCHAR(40)  NULL,
    email_verificado  TINYINT(1)   NOT NULL DEFAULT 0,
    verificado_em     DATETIME     NULL,
    tentativas_login  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado_ate     DATETIME     NULL,
    ultimo_acesso     DATETIME     NULL,
    ativo             TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_cursistas_email (email),
    UNIQUE KEY uq_usuarios_cursistas_google (google_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tokens de uso único: verificação de e-mail e recuperação de senha (cursista e admin)
CREATE TABLE IF NOT EXISTS tokens_autenticacao (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    escopo      ENUM('cursista','admin')           NOT NULL,
    usuario_id  INT UNSIGNED                        NOT NULL,
    finalidade  ENUM('verificacao','recuperacao')  NOT NULL,
    token_hash  CHAR(64)     NOT NULL,
    expira_em   DATETIME     NOT NULL,
    usado_em    DATETIME     NULL,
    criado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_token_hash (token_hash),
    KEY ix_token_lookup (escopo, usuario_id, finalidade)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Limites de ação por IP (anti-abuso) — Fase 2
CREATE TABLE IF NOT EXISTS limites_acao (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    acao      VARCHAR(40)  NOT NULL,
    ip        VARCHAR(45)  NOT NULL,
    criado_em DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_limites_lookup (acao, ip, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
