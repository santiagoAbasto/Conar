<?php

namespace Tests\Unit;

use App\Parsers\ConarCategoryParser;
use PHPUnit\Framework\TestCase;

class ConarCategoryParserTest extends TestCase
{
    public function test_it_keeps_no_contact_technical_icon_for_portaflow_sensor(): void
    {
        $html = <<<'HTML'
        <html>
            <body>
                <h1>Caudalimetros Portatiles</h1>
                <table class="categoria">
                    <tr>
                        <td class="foto">
                            <img src="fotos/productos/97.jpg" title="F-FSD - SENSORES SIN CONTACTO PARA CAUDALIMETRO PORTAFLOW">
                        </td>
                        <td class="texto">
                            <h3 class="codigo">F-FSD</h3>
                            <h1 class="titulo">SENSORES SIN CONTACTO PARA CAUDALIMETRO PORTAFLOW</h1>
                            <h2 class="linea1">Cuatro medidas de sensores para cubrir el rango entre 1/2&quot; y 240&quot;.</h2>
                        </td>
                        <td class="iconos_tecnicos">
                            <div class="categoria_icono" onmouseover="javascript:mostrar_ayuda_icono_tecnico('&lt;img class=&quot;iconoimg&quot; src=&quot;/iconos/27.gif&quot; alt=&quot;El dispositivo sensor o partes activas de este instrumento no tienen contacto directo con el fluido de proceso.&quot;&gt;', 'The sensing device or active parts of this instrument have direct no contact with the process fluid.', 'El dispositivo sensor o partes activas de este instrumento no tienen contacto directo con el fluido de proceso.', 'O dispositivo sensor ou partes ativas deste instrumento n&amp;atilde;o t&amp;ecirc;m contato direto com o fluido de processo.');">
                                <img class="iconoimg" src="/iconos/27.gif" alt="El dispositivo sensor o partes activas de este instrumento no tienen contacto directo con el fluido de proceso.">
                            </div>
                            <div class="categoria_icono">
                                <img class="iconoimg" src="/iconos/47.gif" alt="Ideal para medir l&iacute;quidos limpios y poco aereados.">
                            </div>
                        </td>
                        <td class="archivos"></td>
                    </tr>
                </table>
            </body>
        </html>
        HTML;

        $category = (new ConarCategoryParser())->parse(
            $html,
            'https://www.conar.com/es/productos_y_servicios/6/Caudalimetros_Portatiles.html',
            'Caudal'
        );

        $this->assertCount(1, $category->products);
        $specifications = array_map(
            fn ($specification) => $specification->name,
            $category->products[0]->specifications
        );

        $this->assertContains(
            'El dispositivo sensor o partes activas de este instrumento no tienen contacto directo con el fluido de proceso.',
            $specifications
        );
    }

    public function test_it_keeps_gas_vapor_technical_icon_for_level_transmitters(): void
    {
        $html = <<<'HTML'
        <html>
            <body>
                <h1>Transmisores Magneticos de Nivel</h1>
                <table class="categoria">
                    <tr>
                        <td class="foto">
                            <img src="fotos/productos/113.jpg" title="LTS-1620-H - TRANSMISOR MAGNETICO DE NIVEL PARA SERVICIO PESADO">
                        </td>
                        <td class="texto">
                            <h3 class="codigo">LTS-1620-H</h3>
                            <h1 class="titulo">TRANSMISOR MAGNETICO DE NIVEL PARA SERVICIO PESADO</h1>
                        </td>
                        <td class="iconos_tecnicos">
                            <div class="categoria_icono" onmouseover="javascript:mostrar_ayuda_icono_tecnico('&lt;img class=&quot;iconoimg&quot; src=&quot;/iconos/63.gif&quot; alt=&quot;La medici&amp;oacute;n de este instrumento NO es afectada por la composici&amp;oacute;n, densidad o estratificaci&amp;oacute;n de los gases o vapores que puedan estar presentes sobre la superficie del l&amp;iacute;quido.&quot;&gt;', 'The accuracy and reliability of this instrument is NOT affected by the composition, density or stratification of the gases or vapours above the liquid surface.', 'La medici&amp;oacute;n de este instrumento NO es afectada por la composici&amp;oacute;n, densidad o estratificaci&amp;oacute;n de los gases o vapores que puedan estar presentes sobre la superficie del l&amp;iacute;quido.', 'A medi&amp;ccedil;&amp;atilde;o deste instrumento N&amp;Atilde;O &amp;eacute; afetada pela composi&amp;ccedil;&amp;atilde;o, densidade ou estratifica&amp;ccedil;&amp;atilde;o dos gases ou vapores que possam estar presentes sobre a superf&amp;iacute;cie do l&amp;iacute;quido.');">
                                <img class="iconoimg" src="/iconos/63.gif" alt="La medici&oacute;n de este instrumento NO es afectada por la composici&oacute;n, densidad o estratificaci&oacute;n de los gases o vapores que puedan estar presentes sobre la superficie del l&iacute;quido.">
                            </div>
                        </td>
                        <td class="archivos"></td>
                    </tr>
                </table>
            </body>
        </html>
        HTML;

        $category = (new ConarCategoryParser())->parse(
            $html,
            'https://www.conar.com/es/productos_y_servicios/8/Transmisores_Magneticos_de_Nivel.html',
            'Nivel'
        );

        $this->assertCount(1, $category->products);
        $specifications = array_map(
            fn ($specification) => $specification->name,
            $category->products[0]->specifications
        );

        $this->assertContains(
            'La medición de este instrumento NO es afectada por la composición, densidad o estratificación de los gases o vapores que puedan estar presentes sobre la superficie del líquido.',
            $specifications
        );
    }

    public function test_it_keeps_product_rows_that_have_code_but_no_title(): void
    {
        $html = <<<'HTML'
        <html>
            <body>
                <h1>Indicadores de Nivel Tipo Camara en By-Pass</h1>
                <table class="categoria">
                    <tr>
                        <td class="foto"><img src="fotos/productos/128.jpg" title="LIX-30-C/D - INDICADOR + INTERRUPTOR + TRANSMISOR MAGNETICO DE NIVEL"></td>
                        <td class="texto">
                            <h3 class="codigo">LIX-30-C/D</h3>
                            <h1 class="titulo">INDICADOR + INTERRUPTOR + TRANSMISOR MAGNETICO DE NIVEL</h1>
                        </td>
                        <td class="iconos_tecnicos"></td>
                        <td class="archivos"></td>
                    </tr>
                    <tr>
                        <td class="foto"><img src="fotos/productos/129.gif" title="LIX-30-D - "></td>
                        <td class="texto">
                            <h3 class="codigo">LIX-30-D</h3>
                        </td>
                        <td class="iconos_tecnicos"></td>
                        <td class="archivos"></td>
                    </tr>
                </table>
            </body>
        </html>
        HTML;

        $category = (new ConarCategoryParser())->parse(
            $html,
            'https://www.conar.com/es/productos_y_servicios/9/Indicadores_de_Nivel_Tipo_Camara_en_By-Pass.html',
            'Nivel'
        );

        $this->assertCount(2, $category->products);
        $this->assertSame('LIX-30-D', $category->products[1]->name);
    }

    public function test_it_keeps_ssr_technical_icon_for_temperature_controllers(): void
    {
        $html = <<<'HTML'
        <html>
            <body>
                <h1>Controladores de Temperatura</h1>
                <table class="categoria">
                    <tr>
                        <td class="foto">
                            <img src="fotos/productos/301.jpg" title="SR-90s - CONTROLADORES AVANZADOS DE TEMPERATURA">
                        </td>
                        <td class="texto">
                            <h3 class="codigo">SR-90s</h3>
                            <h1 class="titulo">CONTROLADORES AVANZADOS DE TEMPERATURA</h1>
                        </td>
                        <td class="iconos_tecnicos">
                            <div class="categoria_icono" onmouseover="javascript:mostrar_ayuda_icono_tecnico('&lt;img class=&quot;iconoimg&quot; src=&quot;/iconos/88.gif&quot; alt=&quot;Controlador disponible con salida digital apta para comandar Relays de Estado Solido (SSR) externos.&quot;&gt;', 'Controller available with digital output compatible with external Solid State Relays (SSR)', 'Controlador disponible con salida digital apta para comandar Relays de Estado Solido (SSR) externos.', 'Controlador dispon&amp;iacute;vel com sa&amp;iacute;da digital apta para comandar Relays de Estado S&amp;oacute;lido (SSR) externos.');">
                                <img class="iconoimg" src="/iconos/88.gif" alt="Controlador disponible con salida digital apta para comandar Relays de Estado Solido (SSR) externos.">
                            </div>
                        </td>
                        <td class="archivos"></td>
                    </tr>
                </table>
            </body>
        </html>
        HTML;

        $category = (new ConarCategoryParser())->parse(
            $html,
            'https://www.conar.com/es/productos_y_servicios/62/Controladores_de_Temperatura.html',
            'Temperatura'
        );

        $this->assertCount(1, $category->products);
        $specifications = array_map(
            fn ($specification) => $specification->name,
            $category->products[0]->specifications
        );

        $this->assertContains(
            'Controlador disponible con salida digital apta para comandar Relays de Estado Solido (SSR) externos.',
            $specifications
        );
    }
}
