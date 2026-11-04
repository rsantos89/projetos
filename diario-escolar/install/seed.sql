INSERT INTO etapas (nivel, nome, ordem, avaliacao) VALUES
('infantil','Creche I (0-1 ano)',1,'parecer'),
('infantil','Creche II (1-2 anos)',2,'parecer'),
('infantil','Creche III (2-3 anos)',3,'parecer'),
('infantil','Pré-escola I (4 anos)',4,'parecer'),
('infantil','Pré-escola II (5 anos)',5,'parecer'),
('fund1','1º Ano',11,'nota'),
('fund1','2º Ano',12,'nota'),
('fund1','3º Ano',13,'nota'),
('fund1','4º Ano',14,'nota'),
('fund1','5º Ano',15,'nota'),
('fund2','6º Ano',21,'nota'),
('fund2','7º Ano',22,'nota'),
('fund2','8º Ano',23,'nota'),
('fund2','9º Ano',24,'nota');

INSERT INTO componentes (nome, abreviacao) VALUES
('Língua Portuguesa','LP'),
('Matemática','MAT'),
('Ciências','CIE'),
('História','HIS'),
('Geografia','GEO'),
('Arte','ART'),
('Educação Física','EF'),
('Ensino Religioso','ER'),
('Língua Inglesa','ING');

-- Matriz curricular padrão (BNCC): Fund. I sem Inglês, Fund. II com todos
INSERT INTO etapa_componentes (etapa_id, componente_id)
SELECT e.id, c.id FROM etapas e JOIN componentes c
WHERE e.nivel='fund1' AND c.abreviacao <> 'ING';
INSERT INTO etapa_componentes (etapa_id, componente_id)
SELECT e.id, c.id FROM etapas e JOIN componentes c
WHERE e.nivel='fund2';
