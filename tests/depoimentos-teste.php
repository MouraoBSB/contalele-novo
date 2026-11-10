<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/depoimentos.php';

// texto_em_paragrafos() — cada quebra de linha do painel vira um parágrafo
afirmar_igual(['Primeiro.', 'Segundo.'], texto_em_paragrafos("Primeiro.\r\n\r\nSegundo."), 'linha em branco separa parágrafos');
afirmar_igual(['Um.', 'Dois.'], texto_em_paragrafos("Um.\nDois."), 'quebra simples também separa parágrafos');
afirmar_igual(['Oi Lelê.', 'Tchau.'], texto_em_paragrafos("  Oi   Lelê.  \n\n\n  Tchau. "), 'apara e colapsa espaços, ignora linhas vazias');
afirmar_igual([], texto_em_paragrafos(" \n \n"), 'texto vazio não gera parágrafo');

// texto_corrido() — versão numa linha só, para o cartão do topo da home
afirmar_igual('Um. Dois.', texto_corrido("Um.\n\nDois."), 'parágrafos viram uma linha só');

// texto_resumido() — corta entre palavras, nunca no meio de uma
afirmar_igual('A gente termina…', texto_resumido('A gente termina uma aula', 18), 'corta no último espaço antes do limite');
afirmar_igual('Curto.', texto_resumido('Curto.', 18), 'texto dentro do limite fica inteiro');
afirmar_igual('Um. Dois.', texto_resumido("Um.\n\nDois.", 18), 'parágrafos viram uma linha antes do corte');
afirmar_igual('Olá, mundo…', texto_resumido('Olá, mundo, tudo bem', 12), 'tira a vírgula que sobraria antes das reticências');
