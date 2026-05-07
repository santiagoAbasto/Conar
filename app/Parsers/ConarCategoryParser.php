<?php

namespace App\Parsers;

use App\DTOs\Scraping\Conar\ConarCategoryData;
use App\DTOs\Scraping\Conar\ConarDownloadData;
use App\DTOs\Scraping\Conar\ConarImageData;
use App\DTOs\Scraping\Conar\ConarProductData;
use App\DTOs\Scraping\Conar\ConarSpecificationData;
use App\Services\Scraping\Support\HtmlDocument;
use App\Services\Scraping\Support\HtmlNode;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ConarCategoryParser
{
    public function parse(
        string $html,
        string $url,
        ?string $forcedFamilyName = null
    ): ConarCategoryData {
        $document = new HtmlDocument($html, $url);
        $sourceId = $this->extractCategoryId($url);
        $subcategoryName = $this->extractSubcategoryName($document) ?? 'Categoría importada';
        $familyName = $forcedFamilyName
            ?? $this->extractFamilyName($document)
            ?? 'Caudal';

        $products = collect($this->extractProductBlocks($document))
            ->map(fn (HtmlNode $block, int $index) => $this->parseProductBlock($block, $url, $index))
            ->filter()
            ->values()
            ->all();

        return new ConarCategoryData(
            sourceId: $sourceId,
            sourceUrl: $url,
            familyName: $familyName,
            subcategoryName: $subcategoryName,
            subcategoryImageUrl: $this->extractSubcategoryImage($document),
            products: $products,
            rawPayload: [
                'products_detected' => count($products),
            ],
        );
    }

    protected function extractCategoryId(string $url): string
    {
        if (preg_match('~/productos_y_servicios/(\d+)/~', $url, $matches)) {
            return $matches[1];
        }

        return sha1($url);
    }

    protected function extractSubcategoryName(HtmlDocument $document): ?string
    {
        return $document->first('h1')?->text();
    }

    protected function extractFamilyName(HtmlDocument $document): ?string
    {
        $breadcrumbs = collect($document->filter('a'))
            ->map(fn (HtmlNode $node) => $node->text())
            ->filter(fn (?string $text) => filled($text))
            ->values();

        return $breadcrumbs->first(fn (string $item) => Str::upper($item) === $item && mb_strlen($item) <= 40);
    }

    protected function extractSubcategoryImage(HtmlDocument $document): ?string
    {
        $image = collect($document->filter('img'))
            ->first(fn (HtmlNode $imageNode) => $this->isUsefulImage($imageNode));

        return $image?->resolvedAttr('src');
    }

    /**
     * @return HtmlNode[]
     */
    protected function extractProductBlocks(HtmlDocument $document): array
    {
        $tableRows = collect($document->filter('table.categoria tr'))
            ->filter(function (HtmlNode $row) {
                return $row->first('td.foto img') !== null
                    && $row->first('td.texto h3') !== null;
            })
            ->values()
            ->all();

        if ($tableRows !== []) {
            return $tableRows;
        }

        $blocks = [];
        $seen = [];

        foreach ($document->filter('h3') as $heading) {
            $headingText = $heading->text();

            if (!$headingText || !preg_match('/^[A-Za-z0-9][A-Za-z0-9\-\_\/\.]{2,}$/u', $headingText)) {
                continue;
            }

            $bestCandidate = null;
            $bestScore = -1;

            foreach ($heading->ancestors(10) as $ancestor) {
                $h1Count = count($ancestor->filter('h1'));
                $h3Count = count($ancestor->filter('h3'));

                if ($h1Count < 1 || $h3Count < 1) {
                    continue;
                }

                $score = $this->scoreProductBlockCandidate($ancestor);

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestCandidate = $ancestor;
                }
            }

            if (!$bestCandidate) {
                continue;
            }

            $key = spl_object_id($bestCandidate->domNode());

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $blocks[] = $bestCandidate;
        }

        return $blocks;
    }

    protected function scoreProductBlockCandidate(HtmlNode $candidate): int
    {
        $h1Count = count($candidate->filter('h1'));
        $h3Count = count($candidate->filter('h3'));
        $h2Count = count($candidate->filter('h2'));
        $pCount = count($candidate->filter('p'));
        $imgCount = count($candidate->filter('img'));
        $linkCount = count($candidate->filter('a'));
        $text = $candidate->text();
        $upperText = Str::upper($text);
        $textLength = mb_strlen($text);
        $score = 0;

        if ($h3Count !== 1) {
            return -1000;
        }

        if ($this->containsGlobalNoise($text)) {
            $score -= 80;
        }

        if ($h1Count === 1) {
            $score += 5;
        } elseif ($h1Count > 1) {
            $score -= 8 * ($h1Count - 1);
        }

        if ($h2Count <= 3) {
            $score += 4;
        } else {
            $score -= 3 * ($h2Count - 3);
        }

        if ($pCount >= 1 && $pCount <= 8) {
            $score += 6;
        } elseif ($pCount > 14) {
            $score -= 6;
        }

        if ($imgCount >= 1 && $imgCount <= 6) {
            $score += 4;
        } elseif ($imgCount > 8) {
            $score -= 6;
        }

        if ($linkCount >= 1 && $linkCount <= 12) {
            $score += 2;
        } elseif ($linkCount > 18) {
            $score -= 8;
        }

        foreach (['NEW', 'EX', 'IS', 'SEM', 'LGS', '2W', 'HRT', 'LP'] as $badge) {
            if (preg_match('/\b' . preg_quote($badge, '/') . '\b/u', $upperText) === 1) {
                $score += 2;
            }
        }

        if ($textLength >= 80 && $textLength <= 1800) {
            $score += 8;
        } elseif ($textLength > 2800) {
            $score -= 20;
        }

        $score -= min(max((int) floor($textLength / 1200), 0), 8);

        return $score;
    }

    protected function parseProductBlock(HtmlNode $block, string $categoryUrl, int $index): ?ConarProductData
    {
        $identity = $this->extractStructuredIdentity($block);
        $code = $identity['code'] ?? $block->first('h3')?->text();
        $name = $identity['title'] ?? $block->first('h1')?->text();

        if (!$name && !$code) {
            return null;
        }

        $subtitles = collect($block->filter('h2'))
            ->map(fn (HtmlNode $node) => $node->text())
            ->filter()
            ->unique()
            ->values();

        $summaryLines = $this->extractSummaryLines($block, array_filter([$code, $name]));

        $downloads = $this->extractDownloads($block);
        $images = $this->extractImages($block, $code, $name ?: 'Producto importado');
        $specifications = $this->extractSpecifications(
            $block,
            $code,
            $name ?: $code ?: 'Producto importado',
            array_merge($subtitles->all(), $summaryLines)
        );

        $mainImageUrl = $images[0]->url ?? null;
        $sourceUrl = $categoryUrl;
        $sourceId = $this->buildProductSourceId($code, $name ?: $code ?: 'producto', $index);

        $displayName = $name ?: $code ?: 'Producto importado';

        if ($code && $name && !Str::startsWith(Str::lower($displayName), Str::lower($code))) {
            $displayName = $code . ' ' . $name;
        }

        $subtitle = $summaryLines[0] ?? $subtitles->first();
        $description = collect($summaryLines)
            ->slice(1)
            ->merge($subtitles)
            ->unique()
            ->implode("\n");

        return new ConarProductData(
            sourceId: $sourceId,
            sourceUrl: $sourceUrl,
            code: $code,
            name: $displayName,
            subtitle: $subtitle,
            shortDescription: $summaryLines[0] ?? $subtitle,
            description: $description !== '' ? $description : null,
            mainImageUrl: $mainImageUrl,
            specifications: $specifications,
            downloads: $downloads,
            images: $images,
            rawPayload: [
                'block_preview' => Str::limit($block->text(), 600),
            ],
        );
    }

    /**
     * @return array{code:?string,title:?string}
     */
    protected function extractStructuredIdentity(HtmlNode $block): array
    {
        $values = collect($block->filter('input, textarea, option'))
            ->map(fn (HtmlNode $node) => $node->attr('value'))
            ->filter()
            ->merge(
                collect($block->filter('a, img'))
                    ->map(fn (HtmlNode $node) => $node->attr('onclick'))
                    ->filter()
            );

        foreach ($values as $value) {
            if (!is_string($value) || !str_contains($value, '|')) {
                continue;
            }

            $parts = array_map('trim', explode('|', $value));

            if (count($parts) < 4) {
                continue;
            }

            $code = $parts[1] ?? null;
            $spanishTitle = $parts[3] ?? null;

            if ($code && $spanishTitle) {
                return [
                    'code' => $code,
                    'title' => $spanishTitle,
                ];
            }
        }

        return [
            'code' => null,
            'title' => null,
        ];
    }

    protected function buildProductSourceId(?string $code, string $name, int $index): string
    {
        if (filled($code)) {
            return Str::slug($code);
        }

        return Str::slug($name) . '-' . $index;
    }

    /**
     * @return ConarDownloadData[]
     */
    protected function extractDownloads(HtmlNode $block): array
    {
        $anchors = $block->first('td.archivos')
            ? $block->filter('td.archivos a')
            : $block->filter('a');

        return collect($anchors)
            ->filter(function (HtmlNode $anchor) {
                $href = $this->extractDownloadUrl($anchor);
                $text = Str::lower($this->extractNodeMeaning($anchor));
                $onclick = Str::lower((string) $anchor->attr('onclick'));
                $title = Str::lower((string) $anchor->attr('title'));

                if (!filled($href) || $this->isIgnoredDownloadHref($href)) {
                    return false;
                }

                return filled($href) && (
                    str_contains($href, '.pdf')
                    || str_contains($href, '.doc')
                    || str_contains($href, '.docx')
                    || str_contains($text, 'descargar')
                    || str_contains($text, 'hoja de datos')
                    || str_contains($text, 'manual')
                    || str_contains($text, 'catalogo')
                    || str_contains($onclick, 'descargar')
                    || str_contains($onclick, 'hoja de datos')
                    || str_contains($onclick, 'manual')
                    || str_contains($title, 'descargar')
                );
            })
            ->values()
            ->map(function (HtmlNode $anchor, int $index) {
                $label = $this->normalizeDownloadLabel($this->extractNodeMeaning($anchor));
                $href = $this->extractDownloadUrl($anchor) ?? '';
                $title = $this->extractNodeMeaning($anchor);
                $type = strtoupper(pathinfo(parse_url($href, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'PDF');

                if ($type === 'HTML' && preg_match('/\.(pdf|docx?|xls|xlsx)/i', $title, $matches)) {
                    $type = strtoupper($matches[1]);
                }

                return new ConarDownloadData(
                    sourceId: sha1($href),
                    label: Str::limit($label ?: 'Descarga técnica', 190, ''),
                    url: $href,
                    type: $type,
                    size: null,
                    order: $this->alphaOrder($index),
                );
            })
            ->unique(fn (ConarDownloadData $download) => $download->url)
            ->values()
            ->all();
    }

    /**
     * @return ConarImageData[]
     */
    protected function extractImages(HtmlNode $block, ?string $code, string $name): array
    {
        $photoImages = $block->first('td.foto img')
            ? $block->filter('td.foto img')
            : [];
        $images = $photoImages !== [] ? $photoImages : $block->filter('img');

        return collect($images)
            ->filter(function (HtmlNode $image) use ($photoImages) {
                if ($photoImages !== []) {
                    return filled($image->attr('src'));
                }

                return $this->isUsefulImage($image);
            })
            ->sortByDesc(fn (HtmlNode $image) => $this->scoreUsefulImage($image, $code, $name))
            ->values()
            ->map(function (HtmlNode $image, int $index) use ($code, $name) {
                $src = $image->resolvedAttr('src') ?? '';
                $alt = $image->attr('alt') ?: $code ?: $name;

                return new ConarImageData(
                    sourceId: sha1($src),
                    url: $src,
                    alt: $alt,
                    order: $this->alphaOrder($index),
                );
            })
            ->unique(fn (ConarImageData $image) => $image->url)
            ->take(3)
            ->values()
            ->all();
    }

    /**
     * @return ConarSpecificationData[]
     */
    protected function extractSpecifications(
        HtmlNode $block,
        ?string $code,
        string $name,
        array $subtitles
    ): array {
        if ($block->first('td.iconos_tecnicos')) {
            $iconLines = collect($this->extractTechnicalIconLines($block))
                ->unique(fn (string $line) => $this->canonicalSpecKey($line))
                ->values();

            return $iconLines->map(function (string $line, int $index) {
                return new ConarSpecificationData(
                    sourceId: sha1($line),
                    name: $line,
                    value: $line,
                    order: $this->alphaOrder($index),
                );
            })->all();
        }

        $known = array_filter(array_merge([$code, $name], $subtitles, [
            'Agregar este producto a la Lista de Consulta.',
            'Me gusta !',
        ]));

        $textNodes = $block->first('td.texto')
            ? $block->filter('td.texto p, td.texto h2, td.texto li')
            : $block->filter('p, h2, li');

        $textLines = collect($textNodes)
            ->map(fn (HtmlNode $node) => $this->splitCandidateText($node->text()))
            ->flatten()
            ->values();

        $metadataNodes = $block->first('td.iconos_tecnicos')
            ? $block->filter('td.iconos_tecnicos div, td.iconos_tecnicos img')
            : $block->filter('a, img, input, area, button');

        $attributeLines = collect($metadataNodes)
            ->map(fn (HtmlNode $node) => $this->extractNodeMetadataLines($node))
            ->flatten()
            ->values();

        $lines = collect($subtitles)
            ->merge($textLines)
            ->merge($attributeLines)
            ->merge($this->badgeDescriptionsForBlock($block))
            ->map(fn (string $line) => $this->normalizeSpecLine($line))
            ->filter(function (?string $line) use ($known) {
                if (!$line || mb_strlen($line) < 12 || mb_strlen($line) > 320) {
                    return false;
                }

                if ($this->containsGlobalNoise($line) || !$this->isUsefulSpecificationLine($line)) {
                    return false;
                }

                if (preg_match('/^[A-Z]{1,6}-\d{2,}/', $line)) {
                    return false;
                }

                foreach ($known as $knownText) {
                    $knownText = trim((string) $knownText);

                    if ($knownText !== '' && trim($line) === $knownText) {
                        return false;
                    }
                }

                return true;
            })
            ->unique(fn (string $line) => $this->canonicalSpecKey($line))
            ->values();

        return $lines->map(function (string $line, int $index) {
            return new ConarSpecificationData(
                sourceId: sha1($line),
                name: $line,
                value: $line,
                order: $this->alphaOrder($index),
            );
        })->all();
    }

    /**
     * @return string[]
     */
    protected function extractTechnicalIconLines(HtmlNode $block): array
    {
        return collect($block->filter('td.iconos_tecnicos div.categoria_icono'))
            ->map(function (HtmlNode $iconNode) {
                $candidates = collect()
                    ->merge($this->extractTooltipSpanishStrings((string) $iconNode->attr('onmouseover')))
                    ->merge($this->extractTooltipSpanishStrings((string) $iconNode->attr('onmouseenter')));

                if ($image = $iconNode->first('img.iconoimg')) {
                    $candidates->push($image->attr('alt'));
                    $candidates->push($image->attr('title'));
                    $candidates->push($this->technicalIconFallbackBySrc((string) $image->attr('src')));
                }

                return $candidates
                    ->filter(fn ($value) => is_string($value) && trim($value) !== '')
                    ->flatMap(fn (string $value) => $this->extractSpanishSegments($value))
                    ->map(fn (string $line) => $this->normalizeSpecLine($line))
                    ->first(fn (?string $line) => $line && !$this->isPortugueseLine($line) && !$this->containsGlobalNoise($line));
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function technicalIconFallbackBySrc(string $src): ?string
    {
        if (!preg_match('~/iconos/(\d+)\.(?:gif|png|jpg|jpeg|webp)$~i', $src, $matches)) {
            return null;
        }

        return [
            '10' => 'Este instrumento, equipo u opción es uno de los mas populares dentro de su categoría.',
            '11' => 'Este producto es uno de los que posee menor costo total de compra cuando se lo compara con otros dentro de su categoría.',
            '15' => 'Este instrumento es uno de los que posée mayor Precisión Total de Medición cuando se lo compara con otros dentro de su categoría o con otros instrumentos de tecnologías competitivas.',
            '17' => 'Instrumento disponible con Certificación Anti-Deflagrante (Anti-Explosiva). Según los casos puede ser Ex d IIB+H2 (ATEX, INTI o equivalente) y/o Clase II, III; Div 1; Grupos B, C, D, E, F, G (FM, CSA o equivalente).',
            '18' => 'Este instrumento esta disponible con Certificación de Seguridad Intrínseca. Según los casos especificos puede ser Ex ia IIB+H2 T6 (ATEX, INTI o equivalente) y/o Clase I, II, III; Div. 1; Grupos B, C, D, E, F, G (FM, CSA o equivalente).',
            '24' => 'Certificado por diversos organismos legales de Pesas y Medidas con el objeto de ser usado en transacciones comerciales. (Aplicaciones de transferencia de custodia).',
            '25' => 'Certificado por organismos tales como 3A (USA), EHEDG (Europa) o equivalentes para ser usado en procesos sanitarios o estériles.',
            '27' => 'El dispositivo sensor o partes activas de este instrumento no tienen contacto directo con el fluido de proceso.',
            '34' => 'Adecuado para procesos sanitarios tipo "Clean in Place". Esto tipicamente significa operacion con T >= 80 °C y con fluidos tales como soluciones diluidas de HNO3, NaOH, detergentes, etc.',
            '35' => 'Instrumento adecuado para ser utilizado en procesos estériles sanitarios con procedimientos de tipo "Sterilize in Place". Esto normalmente significa operación con T >= 120 °C durante un mínimo de 30 minutos en contacto directo con vapor u otros químicos.',
            '37' => 'Este instrumento es apto para medir todo tipo de fluidos. Según las distintas especificaciones, puede operar con: Líquidos (preferentemente poco viscosos), Gases o Vapor de Agua.',
            '39' => 'Este instrumento es apto para medir líquidos de baja viscosidad (normalmente <= 10 cPoise) y todo tipo de gases.',
            '40' => 'Este instrumento es apto para medir liquidos de baja viscosidad (tipicamente con viscosidad <= 10 cPoise).',
            '42' => 'Este instrumento es apto para medir todo tipo de gases que sean compatibles con sus materiales de construcción.',
            '44' => 'Este instrumento es ideal para medir Líquidos Criogénicos tales como Oxígeno (LOX, aprox. -183°C), Nitrógeno (LN2, aprox. -196°C), Argón Líquidos (LAR, aprox. -186°C) o similares.',
            '47' => 'Ideal para medir líquidos limpios y poco aereados. Esta definición tipicamente incluye a líquidos con Turbidez <= 10 g/l y Gas <= 3% en volúmen. (Nota: La presencia de gas en el líquido va a generar un error positivo en un caudalímetro volumétrico).',
            '49' => 'Este instrumento es conveniente, económica y tecnicamente, para medir en grandes cañerías. Tipicamente para medidas nominales mayores a 10". Consulte el catálogo u hoja de datos para determinar la máximas medidas disponibles.',
            '50' => 'Este caudalímetro opera en base a una tecnología térmica. Según los casos específicos puede ser por calentamiento del fluido (cuerpo primario con flujo laminar) o por dispersión térmica.',
            '57' => 'Instrumento de inserción apto para ser montado en el proceso (sin interrupciones de servicio) mediante técnicas de Hot Tap.',
            '58' => 'Este caudalímetro puede incluir una salida adicional de 4...20 mA para ser asignada como Transmisor de Temperatura.',
            '61' => 'Este instrumento NO es afectado por la densidad del líquido que se esta midiendo.',
            '62' => 'Este instrumento de medición de nivel NO es afectado por la presencia de espuma sobre la superficie del líquido.',
            '63' => 'La medición de este instrumento NO es afectada por la composición, densidad o estratificación de los gases o vapores que puedan estar presentes sobre la superficie del líquido.',
            '73' => 'Este analizador esta disponible para determinación de múltiples gases tales como: CO, CO2, CH4, SH2, O2, etc.',
            '74' => 'Este transmisor cuenta con salida bifilar en 4...20 mA (o 4...20 mA + HART) y alimentación con una tensión nominal de 24 VCC. (Tipicamente se admite que la alimentación varie de 12 a 36 VCC).',
            '75' => 'Transmisor con circuitos independientes para la alimentación (que tipicamente puede ser 24 V, 110 V o 220 V) y para la señal de salida, que habitualmente es (0)4..20 mA, 0...5 VCC, 1...5 VCC o 0...10 VCC.',
            '76' => 'Este instrumento obtiene su alimentación eléctrica a partir de una batería de Litio de muy larga vida util (tipicamente entre 3 y 10 años). Optativamente, podria tambien tener alimentación externa.',
            '77' => 'Instrumento con señal de salida para transmisión (o re-transmisión) en 4...20 mACC.',
            '78' => 'Este instrumento está disponible con protocolo de comunicaciones digitales HART sobrepuesto a la señal de corriente de 4...20 mA. HART es una herramienta muy conveniente para re-calibración, re-configuración o diagnóstico remoto del instrumento.',
            '81' => 'Este instrumento está disponible con salida directa de pulsos digitales. Estos pulsos tipicamente pueden ser empleados para la totalización de volúmen o masa.',
            '82' => 'Instrumento disponible con comunicaciones digitales mediante protocolo ModBus-RTU sobre interfase de RS-485.',
            '87' => 'Este instrumento está disponible con salida auxiliar de alarma y/o control. Tipicamente son contactos SPDT libres de potencial configurables por Alto y/o Bajo nivel.',
            '88' => 'Controlador disponible con salida digital apta para comandar Relays de Estado Solido (SSR) externos.',
            '92' => 'Además de la clásica gabinetería en Aluminio, este Instrumento esta disponible con gabineteria construida en materiales especiales, tales como: Acero Inoxidable, Plasticos Sanitarios (Aprobados por FDA) u otros.',
            '94' => 'La unidad primaria (o sea, la parte "mojada" por los fluidos de proceso) de este instrumento está disponible con construcción en plásticos de ingeniería, tales como: PVC, CPVC, PP, PTFE, PVDF, PPS, etc.',
            '107' => 'Este producto es nuevo y ha sido lanzado al mercado o renovado totalmente hace menos de un año.',
            '115' => 'Este dispositivo esta disponible con entradas analógicas de control o set-point. Estas entradras pueden ser de corriente (4...20 mA) o de tensión (0...5 o 0...10 VCC).',
        ][$matches[1]] ?? null;
    }

    /**
     * @param  string[]  $known
     * @return string[]
     */
    protected function extractSummaryLines(HtmlNode $block, array $known): array
    {
        $summaryNodes = $block->first('td.texto')
            ? $block->filter('td.texto h2, td.texto p')
            : $block->filter('p, h2');

        return collect($summaryNodes)
            ->map(fn (HtmlNode $node) => $this->normalizeSpecLine($node->text()))
            ->filter(function (?string $line) use ($known) {
                if (!$line || mb_strlen($line) < 12 || mb_strlen($line) > 240) {
                    return false;
                }

                if ($this->containsGlobalNoise($line)) {
                    return false;
                }

                foreach ($known as $knownText) {
                    if ($knownText && trim($line) === trim((string) $knownText)) {
                        return false;
                    }
                }

                return true;
            })
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return string[]
     */
    protected function splitCandidateText(string $text): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        if ($text === '') {
            return [];
        }

        $chunks = preg_split('/(?<=[\.\!\?])\s+|\s{2,}|\|/u', $text) ?: [];

        return collect($chunks)
            ->map(fn (string $chunk) => trim($chunk))
            ->filter()
            ->values()
            ->all();
    }

    protected function normalizeSpecLine(string $line): ?string
    {
        $line = html_entity_decode($line, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $line = trim(preg_replace('/\s+/u', ' ', $line) ?? '');
        $line = trim($line, "- \t\n\r\0\x0B");

        return $line !== '' ? $line : null;
    }

    protected function extractNodeMeaning(HtmlNode $node): string
    {
        $parts = collect([
            $node->text(),
            $node->attr('title'),
            $node->attr('alt'),
            $node->attr('aria-label'),
            $node->attr('data-original-title'),
            $node->attr('data-title'),
            $node->attr('data-content'),
        ])->filter(fn (?string $value) => filled($value));

        foreach ($node->filter('img') as $image) {
            $parts->push($image->attr('alt'));
            $parts->push($image->attr('title'));
            $parts->push($image->attr('data-original-title'));
        }

        return $parts
            ->map(fn (?string $value) => trim((string) $value))
            ->filter()
            ->unique()
            ->implode(' | ');
    }

    /**
     * @return string[]
     */
    protected function extractNodeMetadataLines(HtmlNode $node): array
    {
        $candidates = collect([
            $node->attr('title'),
            $node->attr('alt'),
            $node->attr('aria-label'),
            $node->attr('data-original-title'),
            $node->attr('data-title'),
            $node->attr('data-content'),
            $this->extractTooltipSpanishStrings((string) $node->attr('onclick')),
            $this->extractTooltipSpanishStrings((string) $node->attr('onmouseover')),
            $this->extractTooltipSpanishStrings((string) $node->attr('onmouseenter')),
            $this->extractTooltipSpanishStrings((string) $node->attr('onfocus')),
        ]);

        foreach ($node->filter('img') as $image) {
            $candidates->push($image->attr('title'));
            $candidates->push($image->attr('alt'));
            $candidates->push($image->attr('data-original-title'));
            $candidates->push($this->extractTooltipSpanishStrings((string) $image->attr('onclick')));
            $candidates->push($this->extractTooltipSpanishStrings((string) $image->attr('onmouseover')));
            $candidates->push($this->extractTooltipSpanishStrings((string) $image->attr('onmouseenter')));
            $candidates->push($this->extractTooltipSpanishStrings((string) $image->attr('onfocus')));
        }

        return $candidates
            ->flatten()
            ->map(function ($candidate) {
                if (!is_string($candidate)) {
                    return null;
                }

                return $this->extractSpanishSegments($candidate);
            })
            ->flatten()
            ->filter(function (?string $candidate) {
                if (!$candidate) {
                    return false;
                }

                if ($this->containsGlobalNoise($candidate)) {
                    return false;
                }

                if (mb_strlen($candidate) <= 6 && strtoupper($candidate) === $candidate) {
                    return false;
                }

                $lower = Str::lower($candidate);

                if (
                    str_contains($lower, 'location:no')
                    || str_contains($lower, 'scrollbars=no')
                    || str_contains($lower, 'resizable=no')
                    || str_contains($lower, '/es/me_gusta')
                    || str_contains($lower, 'id_camino=')
                    || str_contains($lower, 'http://')
                    || str_contains($lower, 'https://')
                    || str_contains($candidate, '|')
                ) {
                    return false;
                }

                return true;
            })
            ->map(fn (string $candidate) => preg_replace('/\s*\[[^\]]+\]\s*/u', ' ', $candidate) ?? $candidate)
            ->map(fn (string $candidate) => $this->normalizeSpecLine($candidate))
            ->filter()
            ->unique(fn (string $candidate) => $this->canonicalSpecKey($candidate))
            ->values()
            ->all();
    }

    /**
     * @return string[]
     */
    protected function extractSpanishSegments(string $candidate): array
    {
        $candidate = str_replace(['<=', '>='], ['__LTE__', '__GTE__'], $candidate);
        $candidate = strip_tags($candidate);
        $candidate = html_entity_decode($candidate, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $candidate = str_replace(['__LTE__', '__GTE__'], ['<=', '>='], $candidate);
        $candidate = preg_replace('/\s+/u', ' ', $candidate) ?? $candidate;

        $parts = preg_split('/Image:\s*|(?:\s{2,})|(?:\s+\|\s+)/u', $candidate) ?: [];

        return collect($parts)
            ->map(fn (string $part) => $this->normalizeSpecLine($part))
            ->filter(function (?string $part) {
                if (!$part) {
                    return false;
                }

                if (preg_match('/^Este instrumento est[aá] disponible con protocolo de comunicaciones digitales HART/iu', $part) === 1) {
                    return true;
                }

                if (Str::startsWith($part, ['This ', 'Instrument available', 'This product', 'This instrument', 'Este produto', 'Este instrumento é', 'Instrumento disponível'])) {
                    return false;
                }

                if ($this->isPortugueseLine($part)) {
                    return false;
                }

                if ($this->containsGlobalNoise($part)) {
                    return false;
                }

                return preg_match('/^(Este|Esta|Este equipo|El dispositivo|Controlador|Instrumento|Transmisor|Tipicamente|Típicamente|Además|La medición|La unidad|La totalidad|Los|Adecuado|Ideal|Versiones|Conjuntos|Pueden incluir|Montaje|Medidas|Igual|Disponible|Apto|Certificado)/iu', $part) === 1;
            })
            ->values()
            ->all();
    }

    /**
     * @return string[]
     */
    protected function badgeDescriptionsForBlock(HtmlNode $block): array
    {
        $dictionary = [
            'NEW' => 'Este producto es nuevo y ha sido lanzado al mercado o renovado totalmente hace menos de un año.',
            'EX' => 'Instrumento disponible con Certificación Anti-Deflagrante (Anti-Explosiva). Según los casos puede ser Ex d IIB+H2 (ATEX, INTI o equivalente) y/o Clase II, III; Div 1; Grupos B, C, D, E, F, G (FM, CSA o equivalente).',
            'IS' => 'Este instrumento está disponible con Certificación de Seguridad Intrínseca. Según los casos específicos puede ser Ex ia IIB+H2 T6 (ATEX, INTI o equivalente) y/o Clase I, II, III; Div. 1; Grupos B, C, D, E, F, G (FM, CSA o equivalente).',
            'LGS' => 'Este instrumento posee Aislación Galvánica (típicamente 1500 VAC) entre sus circuitos de entrada y salida o entre los circuitos de I/O y la fuente de alimentación, en caso que esta sea independiente.',
            '2W' => 'Este transmisor cuenta con salida bifilar en 4...20 mA (o 4...20 mA + HART) y alimentación con una tensión nominal de 24 VCC. Típicamente se admite que la alimentación varíe de 12 a 36 VCC.',
            'HRT' => 'Este instrumento está disponible con protocolo de comunicaciones digitales HART sobrepuesto a la señal de corriente de 4...20 mA. HART es una herramienta muy conveniente para re-calibración, re-configuración o diagnóstico remoto del instrumento.',
        ];

        $tokens = collect($block->filter('*'))
            ->flatMap(function (HtmlNode $node) {
                return [
                    $node->text(),
                    $node->attr('title'),
                    $node->attr('alt'),
                    $node->attr('aria-label'),
                    $node->attr('src'),
                ];
            })
            ->filter()
            ->map(fn ($value) => Str::upper((string) $value))
            ->implode(' ');

        return collect($dictionary)
            ->filter(fn (string $description, string $code) => preg_match('/\b' . preg_quote($code, '/') . '\b/u', $tokens) === 1)
            ->values()
            ->all();
    }

    /**
     * @return string[]
     */
    protected function extractQuotedStrings(string $value): array
    {
        if ($value === '') {
            return [];
        }

        preg_match_all('/[\'"]([^\'"]{8,})[\'"]/u', $value, $matches);

        return $matches[1] ?? [];
    }

    /**
     * @return string[]
     */
    protected function extractTooltipSpanishStrings(string $value): array
    {
        if ($value === '') {
            return [];
        }

        if (str_contains($value, 'mostrar_ayuda_icono_tecnico')) {
            preg_match_all("/'((?:\\\\'|[^'])*)'/u", $value, $matches);
            $quoted = $matches[1] ?? [];

            if (count($quoted) >= 4) {
                return [html_entity_decode($quoted[2], ENT_QUOTES | ENT_HTML5, 'UTF-8')];
            }
        }

        return $this->extractQuotedStrings($value);
    }

    protected function canonicalSpecKey(string $line): string
    {
        $line = Str::lower($line);
        $line = preg_replace('/[[:punct:]]+/u', ' ', $line) ?? $line;
        $line = preg_replace('/\s+/u', ' ', $line) ?? $line;

        return trim($line);
    }

    protected function isPortugueseLine(string $line): bool
    {
        $lower = Str::lower($line);

        $tokens = [
            'disponível',
            'conforme os casos',
            'e/ou',
            'comunicações',
            'variação',
            're-configuração',
            'ferramenta muito conveniente',
            'instrumento é apto',
            'este transmissor conta com',
            'este produto é novo',
            'renovado totalmente há',
        ];

        foreach ($tokens as $token) {
            if (str_contains($lower, $token)) {
                return true;
            }
        }

        return false;
    }

    protected function normalizeDownloadLabel(string $meaning): ?string
    {
        $meaning = Str::lower($meaning);

        return match (true) {
            str_contains($meaning, 'hoja de datos') => 'Hoja de datos',
            str_contains($meaning, 'manual') => 'Manual',
            str_contains($meaning, 'catalogo') => 'Catálogo',
            str_contains($meaning, 'descargar') => 'Descarga técnica',
            default => null,
        };
    }

    protected function isUsefulImage(HtmlNode $image): bool
    {
        $src = Str::lower((string) $image->attr('src'));
        $alt = Str::lower((string) $image->attr('alt'));
        $title = Str::lower((string) $image->attr('title'));
        $width = (int) ($image->attr('width') ?: 0);
        $height = (int) ($image->attr('height') ?: 0);

        if ($src === '') {
            return false;
        }

        $ignoreTokens = [
            'like',
            'consulta',
            'pdf',
            'manual',
            'icon',
            'icons',
            'logo',
            'facebook',
            'twitter',
            'new',
            'ex',
            'is',
            'sem',
            'lgs',
            'hrt',
            '2w',
            'lp',
            'info',
            'me_gusta',
            'checkout',
            'carrito',
            'flag',
            'bandera',
            'espana',
            'brazil',
            'brasil',
            'usa',
            'menu',
            'search',
            'contact',
            'download',
        ];

        foreach ($ignoreTokens as $token) {
            if (str_contains($src, $token) || str_contains($alt, $token) || str_contains($title, $token)) {
                return false;
            }
        }

        if (($width > 0 && $width < 90) || ($height > 0 && $height < 90)) {
            return false;
        }

        if ($this->containsGlobalNoise($alt) || $this->containsGlobalNoise($title)) {
            return false;
        }

        if (preg_match('/\.(gif|svg)(\?|$)/i', $src) === 1) {
            return false;
        }

        return true;
    }

    protected function scoreUsefulImage(HtmlNode $image, ?string $code, string $name): int
    {
        $score = 0;
        $src = Str::lower((string) $image->attr('src'));
        $meaning = Str::lower(trim(implode(' ', array_filter([
            $image->attr('alt'),
            $image->attr('title'),
        ]))));

        if ($code && str_contains($meaning, Str::lower($code))) {
            $score += 8;
        }

        if ($name !== '' && str_contains($meaning, Str::lower(Str::limit($name, 24, '')))) {
            $score += 3;
        }

        if (preg_match('/(producto|product|foto|image|img|caudal)/i', $src) === 1) {
            $score += 2;
        }

        $width = (int) ($image->attr('width') ?: 0);
        $height = (int) ($image->attr('height') ?: 0);

        if ($width >= 180 || $height >= 180) {
            $score += 4;
        }

        return $score;
    }

    protected function containsGlobalNoise(?string $text): bool
    {
        if (!$text) {
            return false;
        }

        $normalized = Str::lower($text);
        $noiseTokens = [
            'ingresar / registrarse',
            'productos y servicios',
            'soluciones industriales',
            'noticias y eventos',
            'historias de exito',
            'notas de aplicación',
            'notas de aplicacion',
            'información y documentación de la empresa',
            'informacion y documentacion de la empresa',
            'documentación general de la empresa',
            'documentacion general de la empresa',
            'procedimientos y normas tecnicas',
            'enlaces con instituciones',
            'enlaces a instituciones',
            'nosotros',
            'contacto',
            'presupuesto',
            'checkout',
            'lista de consulta',
            'me gusta',
            '/es/me_gusta',
            'id_camino=',
            'scrollbars=no',
            'resizable=no',
            'location:no',
        ];

        foreach ($noiseTokens as $token) {
            if ($token === 'contacto') {
                if (trim($normalized) === 'contacto') {
                    return true;
                }

                continue;
            }

            if (str_contains($normalized, $token)) {
                return true;
            }
        }

        return false;
    }

    protected function isUsefulSpecificationLine(string $line): bool
    {
        if ($this->containsGlobalNoise($line)) {
            return false;
        }

        $lower = Str::lower($line);

        if (
            str_contains($lower, 'descargar')
            || str_contains($lower, 'agregar este producto')
            || str_contains($lower, 'lista de consulta')
            || str_contains($lower, 'me gusta')
            || str_contains($lower, 'checkout')
            || str_contains($lower, 'http://')
            || str_contains($lower, 'https://')
            || str_contains($line, '|')
        ) {
            return false;
        }

        return preg_match('/^(Este|Esta|Este equipo|El dispositivo|Controlador|Instrumento|Transmisor|Montaje|Medidas|Igual|Además|Disponible|Apto|Adecuado|Tipicamente|Típicamente|Certificado|La medición|La unidad|La totalidad|Los)/iu', $line) === 1;
    }

    protected function isIgnoredDownloadHref(string $href): bool
    {
        $lower = Str::lower($href);

        return str_contains($lower, '/me_gusta')
            || str_contains($lower, '/inicio')
            || str_contains($lower, '/productos_y_servicios/')
            || str_contains($lower, '#')
            || str_contains($lower, 'javascript:')
            || str_contains($lower, 'id_camino=');
    }

    protected function extractDownloadUrl(HtmlNode $anchor): ?string
    {
        $rawHref = $anchor->attr('href');
        $href = $anchor->resolvedAttr('href');

        if ($rawHref && !str_starts_with(Str::lower($rawHref), 'javascript:')) {
            return $href;
        }

        $onclick = (string) $anchor->attr('onclick');

        if ($onclick !== '' && preg_match('/window\.open\(\s*[\'"]([^\'"]+)[\'"]/i', $onclick, $matches) === 1) {
            return $anchor->resolvedAttrValue($matches[1]);
        }

        return $href;
    }

    protected function alphaOrder(int $index): string
    {
        $value = $index;
        $order = '';

        do {
            $order = chr(65 + ($value % 26)) . $order;
            $value = intdiv($value, 26) - 1;
        } while ($value >= 0);

        return $order;
    }
}
