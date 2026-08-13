<?php
/**
 * Componente SVG do girassol da Conta Lelê.
 * É o único ornamento decorativo permitido pela marca.
 *
 * Thiago Mourão — https://github.com/MouraoBSB
 *
 * Uso:
 *   girassol(['tamanho' => 120, 'classe' => 'cl-card-tom__girassol cl-girassol--gira']);
 */

declare(strict_types=1);

if (!function_exists('girassol_sprite')) {
    /**
     * Emite o girassol uma única vez como <symbol>, para ser reaproveitado
     * com girassol_ref(). Use em páginas com muitos girassóis: evita repetir
     * as 24 elipses a cada ocorrência.
     */
    function girassol_sprite(): void
    {
        $petalas = '';
        for ($i = 0; $i < 12; $i++) {
            $petalas .= '<use href="#cl-petala-ext" transform="rotate(' . ($i * 30) . ' 100 100)"/>';
        }
        for ($i = 0; $i < 12; $i++) {
            $petalas .= '<use href="#cl-petala-int" transform="rotate(' . ($i * 30 + 15) . ' 100 100)"/>';
        }
        echo '<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false"><defs>'
            . '<ellipse id="cl-petala-ext" cx="100" cy="38" rx="13" ry="32" fill="#ffcc1f" stroke="#c4390e" stroke-width="2.4"/>'
            . '<ellipse id="cl-petala-int" cx="100" cy="62" rx="9" ry="20" fill="#f7a91a" stroke="#5a2a0a" stroke-width="1.2"/>'
            . '<symbol id="cl-girassol" viewBox="0 0 200 200">'
            . $petalas
            . '<circle cx="100" cy="100" r="32" fill="#5a2a0a"/>'
            . '<g fill="#3d1c06" opacity="0.65">'
            . '<circle cx="92" cy="92" r="2"/><circle cx="104" cy="90" r="2"/><circle cx="98" cy="100" r="2"/>'
            . '<circle cx="108" cy="104" r="2"/><circle cx="90" cy="106" r="2"/><circle cx="100" cy="112" r="2"/>'
            . '<circle cx="112" cy="98" r="2"/><circle cx="86" cy="100" r="2"/>'
            . '</g></symbol></defs></svg>';
    }

    /**
     * Referencia o girassol do sprite. Exige girassol_sprite() antes na página.
     *
     * @param array{tamanho?:int,classe?:string,estilo?:string} $opts
     */
    function girassol_ref(array $opts = []): void
    {
        $tamanho = (int) ($opts['tamanho'] ?? 120);
        $classe  = trim('cl-girassol ' . (string) ($opts['classe'] ?? ''));
        $estilo  = (string) ($opts['estilo'] ?? '');
        echo '<svg class="' . htmlspecialchars($classe, ENT_QUOTES) . '"'
            . ' width="' . $tamanho . '" height="' . $tamanho . '"'
            . ($estilo !== '' ? ' style="' . htmlspecialchars($estilo, ENT_QUOTES) . '"' : '')
            . ' aria-hidden="true" focusable="false"><use href="#cl-girassol"/></svg>';
    }
}

if (!function_exists('girassol')) {
    /**
     * Renderiza o girassol como SVG inline.
     *
     * @param array{tamanho?:int,classe?:string,gira?:bool,titulo?:string} $opts
     */
    function girassol(array $opts = []): void
    {
        $tamanho = (int) ($opts['tamanho'] ?? 120);
        $classe  = (string) ($opts['classe'] ?? '');
        $gira    = !empty($opts['gira']);
        $titulo  = (string) ($opts['titulo'] ?? '');

        $classeFinal = trim('cl-girassol ' . ($gira ? 'cl-girassol--gira ' : '') . $classe);

        $petalas = '';
        for ($i = 0; $i < 12; $i++) {
            $ang = $i * 30;
            $petalas .= '<ellipse cx="100" cy="38" rx="13" ry="32" '
                . 'fill="#ffcc1f" stroke="#c4390e" stroke-width="2.4" '
                . 'transform="rotate(' . $ang . ' 100 100)" />';
        }
        // Pétalas internas (em laranja, dão o "duplo anel")
        $petalas2 = '';
        for ($i = 0; $i < 12; $i++) {
            $ang = $i * 30 + 15;
            $petalas2 .= '<ellipse cx="100" cy="62" rx="9" ry="20" '
                . 'fill="#f7a91a" stroke="#5a2a0a" stroke-width="1.2" '
                . 'transform="rotate(' . $ang . ' 100 100)" />';
        }

        echo '<svg class="' . htmlspecialchars($classeFinal, ENT_QUOTES) . '" '
            . 'width="' . $tamanho . '" height="' . $tamanho . '" '
            . 'viewBox="0 0 200 200" aria-hidden="' . ($titulo ? 'false' : 'true') . '" '
            . 'focusable="false" xmlns="http://www.w3.org/2000/svg">';
        if ($titulo !== '') {
            echo '<title>' . htmlspecialchars($titulo, ENT_QUOTES) . '</title>';
        }
        echo '<g>'
            . $petalas
            . $petalas2
            . '<circle cx="100" cy="100" r="32" fill="#5a2a0a" />'
            // textura de sementes
            . '<g fill="#3d1c06" opacity="0.65">'
            . '<circle cx="92" cy="92" r="2" />'
            . '<circle cx="104" cy="90" r="2" />'
            . '<circle cx="98" cy="100" r="2" />'
            . '<circle cx="108" cy="104" r="2" />'
            . '<circle cx="90" cy="106" r="2" />'
            . '<circle cx="100" cy="112" r="2" />'
            . '<circle cx="112" cy="98" r="2" />'
            . '<circle cx="86" cy="100" r="2" />'
            . '</g>'
            . '</g>'
            . '</svg>';
    }
}
