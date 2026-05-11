<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Support\Translation;

class MultilanguageContentSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->tableColumns() as $table => $columns) {
            DB::table($table)->orderBy('id')->chunkById(100, function ($rows) use ($table, $columns) {
                foreach ($rows as $row) {
                    $updates = [];

                    foreach ($columns as $column) {
                        if (!property_exists($row, $column)) {
                            continue;
                        }

                        $updates[$column] = $this->json($row->{$column});
                    }

                    if ($updates) {
                        DB::table($table)->where('id', $row->id)->update($updates);
                    }
                }
            });
        }

        $this->translateSettings();
        $this->ensureFooterDefaults();
    }

    private function tableColumns(): array
    {
        return [
            'products' => ['name', 'subtitle', 'short_description', 'description'],
            'product_categories' => ['name', 'description'],
            'product_subcategories' => ['name'],
            'industries' => ['title', 'name', 'description'],
            'posts' => ['title', 'excerpt', 'content', 'category'],
            'product_specifications' => ['name', 'value'],
            'product_downloads' => ['name'],
            'downloads' => ['name', 'category', 'description'],
            'hero_slides' => ['title', 'subtitle', 'content', 'button_text'],
            'page_sections' => ['title', 'subtitle', 'content', 'button_text'],
            'seo_metas' => ['title', 'description', 'keywords'],
            'technical_fields' => ['label'],
        ];
    }

    private function translateSettings(): void
    {
        DB::table('settings')->orderBy('key')->chunk(100, function ($settings) {
            foreach ($settings as $setting) {
                $decoded = json_decode($setting->value, true);

                if (!is_array($decoded)) {
                    continue;
                }

                DB::table('settings')
                    ->where('key', $setting->key)
                    ->update(['value' => json_encode($this->translateRecursive($decoded), JSON_UNESCAPED_UNICODE)]);
            }
        });
    }

    private function ensureFooterDefaults(): void
    {
        $setting = DB::table('settings')->where('key', 'footer')->first();
        $footer = $setting ? json_decode($setting->value, true) : [];

        if (!is_array($footer)) {
            $footer = [];
        }

        $footer['fixed_phone'] = $footer['fixed_phone'] ?? '1147530403';
        $footer['mail_recipients'] = $footer['mail_recipients'] ?? 'admin@conar.com,soporte@conar.com.ar,dda@conar.com.ar';

        if (empty($footer['contact_items']) || !is_array($footer['contact_items'])) {
            $footer['contact_items'] = array_values(array_filter([
                [
                    'type' => 'email',
                    'label' => 'Email',
                    'value' => $footer['email'] ?? 'mailbox@conar.com',
                    'is_active' => true,
                ],
                [
                    'type' => 'email',
                    'label' => 'Soporte',
                    'value' => 'soporte@conar.com.ar',
                    'is_active' => true,
                ],
                [
                    'type' => 'email',
                    'label' => 'Administración',
                    'value' => 'dda@conar.com.ar',
                    'is_active' => true,
                ],
                [
                    'type' => 'whatsapp',
                    'label' => 'WhatsApp',
                    'value' => $footer['phone'] ?? '',
                    'is_active' => !empty($footer['phone']),
                ],
                [
                    'type' => 'phone',
                    'label' => 'Teléfono fijo',
                    'value' => $footer['fixed_phone'],
                    'is_active' => true,
                ],
            ], fn ($item) => trim((string) ($item['value'] ?? '')) !== ''));
        }

        foreach (['soporte@conar.com.ar' => 'Soporte', 'dda@conar.com.ar' => 'Administración'] as $email => $label) {
            $index = collect($footer['contact_items'])->search(fn ($item) => ($item['value'] ?? '') === $email);

            if ($index === false) {
                $footer['contact_items'][] = [
                    'type' => 'email',
                    'label' => $label,
                    'value' => $email,
                    'is_active' => true,
                ];
            } else {
                $footer['contact_items'][$index]['type'] = 'email';
                $footer['contact_items'][$index]['is_active'] = true;
            }
        }

        DB::table('settings')->updateOrInsert(
            ['key' => 'footer'],
            ['value' => json_encode($footer, JSON_UNESCAPED_UNICODE)]
        );
    }

    private function translateRecursive(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->looksTranslated($item)
                    ? $this->payload($item)
                    : $this->translateRecursive($item);
            } elseif (is_string($item) && $this->shouldTranslateSettingKey((string) $key)) {
                $value[$key] = $this->payload($item);
            }
        }

        return $value;
    }

    private function shouldTranslateSettingKey(string $key): bool
    {
        return !in_array($key, ['email', 'phone', 'fixed_phone', 'mail_recipients', 'url', 'button_link', 'type', 'value', 'is_active'], true)
            && !str_contains($key, 'image')
            && !str_contains($key, 'logo');
    }

    private function looksTranslated(array $value): bool
    {
        return array_intersect(array_keys($value), Translation::LOCALES) !== [];
    }

    private function normalize(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xc2\xa0", ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return mb_strtolower(trim($text));
    }

    private function json(mixed $value): string
    {
        return json_encode($this->payload($value), JSON_UNESCAPED_UNICODE);
    }

    private function payload(mixed $value): array
    {
        $decoded = Translation::decode($value);
        $payload = is_array($decoded) ? $decoded : ['es' => (string) ($decoded ?? '')];
        $es = trim((string) ($payload['es'] ?? ''));

        return [
            'es' => $es,
            'pt' => $this->translate($es, 'pt'),
            'en' => $this->translate($es, 'en'),
        ];
    }

    private function translate(string $text, string $locale): string
    {
        if ($text === '') {
            return '';
        }

        $exact = $this->exactTranslations()[$locale] ?? [];
        $normalized = $this->normalize($text);

        if (isset($exact[$normalized])) {
            return $exact[$normalized];
        }

        $translated = $text;

        $phrases = $this->phraseTranslations()[$locale] ?? [];
        uksort($phrases, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        foreach ($phrases as $from => $to) {
            if (trim($from) !== $from) {
                $translated = str_ireplace($from, $to, $translated);
                continue;
            }

            $pattern = '/(?<![\pL\pN])' . preg_quote($from, '/') . '(?![\pL\pN])/iu';
            $translated = preg_replace($pattern, $to, $translated);
        }

        return $translated;
    }

    private function exactTranslations(): array
    {
        return [
            'pt' => [
                'caudal' => 'Vazão',
                'nivel' => 'Nível',
                'presion' => 'Pressão',
                'presión' => 'Pressão',
                'temperatura' => 'Temperatura',
                'analisis' => 'Análise',
                'análisis' => 'Análise',
                'controladores de procesos' => 'Controladores de processos',
                'indicacion y registro de datos' => 'Indicação e registro de dados',
                'indicación y registro de datos' => 'Indicação e registro de dados',
                'productos especiales y servicios' => 'Produtos especiais e serviços',
                'empresa' => 'Empresa',
                'productos' => 'Produtos',
                'eventos' => 'Eventos',
                'ficha técnica' => 'Ficha técnica',
                'manual' => 'Manual',
                'catálogo' => 'Catálogo',
                'más info' => 'Mais informações',
                'más información' => 'Mais informações',
                'quienes somos' => 'Quem somos',
                'misión' => 'Missão',
                'visión' => 'Visão',
                'calidad' => 'Qualidade',
                'somos una empresa dedicada a ofrecer las mejores soluciones disponibles en el sector de la instrumentación y control de procesos industriales.' => 'Somos uma empresa dedicada a oferecer as melhores soluções disponíveis no setor de Instrumentação e Controle de Processos Industriais.',
                'somos una empresa dedicada a ofrecer las mejores soluciones disponibles en el sector de la instrumentación y control de procesos industriales. somos fabricantes de instrumental primario de medición para variables tales como caudal, nivel, presión y temperatura. asimismo fabricamos equipos vinculados con las áreas de iluminación de procesos y educación en energías renovables.' => "Somos uma empresa dedicada a oferecer as melhores soluções disponíveis no setor de Instrumentação e Controle de Processos Industriais.\nSomos fabricantes de instrumentação primária de medição para variáveis como vazão, nível, pressão e temperatura. Também fabricamos equipamentos vinculados às áreas de iluminação de processos e educação em energias renováveis.",
                'somos una empresa dedicada a ofrecer soluciones confiables e innovadoras en el sector de la instrumentación y control de procesos industriales, acompañando a nuestros clientes en la mejora continua de sus procesos productivos. nos especializamos en la fabricación de instrumental primario de medición para variables clave como caudal, nivel, presión y temperatura, desarrollando equipos precisos, robustos y adaptados a las exigencias de cada aplicación. además, diseñamos y fabricamos equipos vinculados a las áreas de iluminación de procesos y educación en energías renovables, contribuyendo al desarrollo tecnológico, la eficiencia operativa y la formación técnica.' => "Somos uma empresa dedicada a oferecer soluções confiáveis e inovadoras no setor de Instrumentação e Controle de Processos Industriais, acompanhando nossos clientes na melhoria contínua de seus processos produtivos.\nEspecializamo-nos na fabricação de instrumentação primária de medição para variáveis-chave como vazão, nível, pressão e temperatura, desenvolvendo equipamentos precisos, robustos e adaptados às exigências de cada aplicação.\nAlém disso, projetamos e fabricamos equipamentos vinculados às áreas de iluminação de processos e educação em energias renováveis, contribuindo para o desenvolvimento tecnológico, a eficiência operacional e a formação técnica.",
                'brindar soluciones de instrumentación y control industrial que garanticen precisión, confiabilidad y eficiencia, acompañando a nuestros clientes con productos de calidad y soporte técnico especializado.' => 'Oferecer soluções de instrumentação e controle industrial que garantam precisão, confiabilidade e eficiência, acompanhando nossos clientes com produtos de qualidade e suporte técnico especializado.',
                'ser una empresa referente en instrumentación industrial, reconocida por la innovación de sus desarrollos, la calidad de sus productos y su compromiso con el crecimiento tecnológico y sostenible.' => 'Ser uma empresa de referência em instrumentação industrial, reconhecida pela inovação de seus desenvolvimentos, pela qualidade de seus produtos e por seu compromisso com o crescimento tecnológico e sustentável.',
                'desarrollamos y fabricamos nuestros equipos bajo estrictos estándares de calidad, asegurando precisión, durabilidad y un desempeño confiable en cada aplicación industrial.' => 'Desenvolvemos e fabricamos nossos equipamentos sob rigorosos padrões de qualidade, assegurando precisão, durabilidade e desempenho confiável em cada aplicação industrial.',
                'nueva serie de caudalímetros a desplazamiento positivo.' => 'Nova série de medidores de vazão de deslocamento positivo.',
                'lanzamos una nueva serie de caudalímetros a desplazamiento positivo.' => 'Lançamos uma nova série de medidores de vazão de deslocamento positivo.',
                'conar re-certifica exitosamente la norma iso-9001:2015' => 'A CONAR recertifica com sucesso a norma ISO 9001:2015',
                'conar re-certifica exitosamente la norma iso-9001:2015.' => 'A CONAR recertifica com sucesso a norma ISO 9001:2015.',
                'conar muestra sus logros en la expo-industria gas y petroleo' => 'A CONAR apresenta suas conquistas na Expo-Indústria Gás e Petróleo',
                'conar presenta su nueva línea de caudalímetros a desplazamiento positivo diseñada para aplicaciones industriales que requieren alta precisión en la medición de caudal.estos instrumentos permiten medir caudales de líquidos con gran exactitud incluso en condiciones de operación exigentes. gracias a su diseño robusto y confiable, son ideales para industrias químicas, petroleras, alimenticias y farmacéuticas.los nuevos modelos incorporan mejoras en materiales, sellado y compatibilidad con sistemas de control modernos.' => 'A CONAR apresenta sua nova linha de medidores de vazão de deslocamento positivo, projetada para aplicações industriais que exigem alta precisão na medição de vazão. Estes instrumentos permitem medir vazões de líquidos com grande exatidão, mesmo em condições de operação exigentes. Graças ao seu projeto robusto e confiável, são ideais para indústrias químicas, petrolíferas, alimentícias e farmacêuticas. Os novos modelos incorporam melhorias em materiais, vedação e compatibilidade com sistemas de controle modernos.',
                'conar ha obtenido nuevamente la certificación del sistema de gestión de calidad bajo la norma iso 9001:2015.esta certificación confirma el compromiso de nuestra empresa con la mejora continua, la calidad en nuestros procesos productivos y la satisfacción de nuestros clientes.el proceso de auditoría fue llevado a cabo por el organismo certificador internacional, evaluando los sistemas de diseño, fabricación y control de nuestros instrumentos industriales.' => 'A CONAR obteve novamente a certificação do sistema de gestão da qualidade conforme a norma ISO 9001:2015. Esta certificação confirma o compromisso da empresa com a melhoria contínua, a qualidade dos processos produtivos e a satisfação dos clientes. O processo de auditoria foi realizado por um organismo certificador internacional, avaliando os sistemas de projeto, fabricação e controle dos nossos instrumentos industriais.',
                'conar participó en la expo-industria gas y petróleo presentando sus soluciones tecnológicas para medición y control de procesos industriales.durante el evento se exhibieron equipos de medición de caudal, presión, nivel y temperatura desarrollados para aplicaciones en la industria energética.la feria permitió fortalecer vínculos con clientes, distribuidores y socios estratégicos del sector industrial.' => 'A CONAR participou da Expo-Indústria Gás e Petróleo apresentando suas soluções tecnológicas para medição e controle de processos industriais. Durante o evento foram exibidos equipamentos de medição de vazão, pressão, nível e temperatura desenvolvidos para aplicações na indústria energética. A feira permitiu fortalecer vínculos com clientes, distribuidores e parceiros estratégicos do setor industrial.',
                'este caudalímetro opera en base a tecnología por inducción electro-magnética. solo es apto para medir líquidos conductores de la electricidad, o sea normalmente aquellos que contienen agua. la conductividad eléctrica debe ser mínimo de 5...20 usiemens .' => 'Este medidor de vazão opera com tecnologia de indução eletromagnética. É adequado apenas para medir líquidos condutores de eletricidade, normalmente aqueles que contêm água. A condutividade elétrica mínima deve ser de 5...20 uSiemens.',
                'este caudalímetro no presenta ningún tipo de restricción al pasaje del fluido (sección de paso total). es por lo tanto un instrumento de pérdida de carga "cero".' => 'Este medidor de vazão não apresenta nenhum tipo de restrição à passagem do fluido (seção de passagem total). Portanto, é um instrumento com perda de carga "zero".',
                'este caudalímetro electro-magnético puede ser provisto con electrodos fabicados en materiales especiales (ej.: hastelloy c, titanio, tantalio, etc) o con funciones especiales (ej.: diseño auto-limpiante)' => 'Este medidor de vazão eletromagnético pode ser fornecido com eletrodos fabricados em materiais especiais (ex.: Hastelloy C, titânio, tântalo etc.) ou com funções especiais (ex.: projeto autolimpante).',
                'este caudalímetro opera en base a una tecnología térmico. según los casos específicos puede ser por calentamiento del fluido (cuerpo primario con flujo laminar) o por dispersión térmica.' => 'Este medidor de vazão opera com tecnologia térmica. Conforme os casos específicos, pode ser por aquecimento do fluido (corpo primário com fluxo laminar) ou por dispersão térmica.',
                'este caudalímetro opera en base a una tecnología térmica. según los casos específicos puede ser por calentamiento del fluido (cuerpo primario con flujo laminar) o por dispersión térmica.' => 'Este medidor de vazão opera com tecnologia térmica. Conforme os casos específicos, pode ser por aquecimento do fluido (corpo primário com fluxo laminar) ou por dispersão térmica.',
                'transmisor con circuitos independientes para la alimentación (que tipicamente puede ser 24 v, 110 v o 220 v) y para la señal de salida, que habitualmente es (0)4..20 ma, 0...5 vcc, 1...5 vcc o 0...10 vcc.' => 'Transmissor com circuitos independentes para alimentação (tipicamente 24 V, 110 V ou 220 V) e para o sinal de saída, normalmente (0)4..20 mA, 0...5 VCC, 1...5 VCC ou 0...10 VCC.',
                'nueva serie de caudalímetros a desplazamiento positivo.' => 'Nova série de medidores de vazão de deslocamento positivo.',
                'sobre nosotros' => 'Sobre nós',
                'alimentos y bebidas' => 'Alimentos e bebidas',
                'aguas & efluentes' => 'Águas e efluentes',
                'farmacéutica' => 'Farmacêutica',
                'minería' => 'Mineração',
                'siderurgica' => 'Siderúrgica',
                'siderúrgica' => 'Siderúrgica',
                'energía' => 'Energia',
                'pulpa & papel' => 'Celulose e papel',
                'química' => 'Química',
                'biocombutible' => 'Biocombustível',
                'este producto es nuevo y ha sido lanzado al mercado o renovado totalmente hace menos de un año.' => 'Este produto é novo e foi lançado no mercado ou totalmente renovado há menos de um ano.',
                'este instrumento es apto para medir todo tipo de fluidos. según las distintas especificaciones, puede operar con: líquidos (preferentemente poco viscosos), gases o vapor de agua.' => 'Este instrumento é adequado para medir todos os tipos de fluidos. Conforme as especificações, pode operar com líquidos, gases ou vapor de água.',
                'este instrumento es apto para medir líquidos (tipicamente de baja viscosidad) y todo tipo de gases.' => 'Este instrumento é adequado para medir líquidos, tipicamente de baixa viscosidade, e todos os tipos de gases.',
                'este instrumento es apto para medir líquidos de baja viscosidad (normalmente <= 10 cpoise) y todo tipo de gases.' => 'Este instrumento é adequado para medir líquidos de baixa viscosidade (normalmente <= 10 cPoise) e todos os tipos de gases.',
                'este instrumento es apto para medir todo tipo de gases que sean compatibles con sus materiales de construcción.' => 'Este instrumento é adequado para medir todos os tipos de gases que sejam compatíveis com seus materiais de construção.',
                'este instrumento esta disponible con certificación de seguridad intrínseca. según los casos especificos puede ser ex ia iib+h2 t6 (atex, inti o equivalente) y/o clase i, ii, iii; div. 1; grupos b, c, d, e, f, g (fm, csa o equivalente)' => 'Este instrumento está disponível com certificação de segurança intrínseca. Conforme o caso específico, pode ser Ex ia IIB+H2 T6 (ATEX, INTI ou equivalente) e/ou Classe I, II, III; Div. 1; Grupos B, C, D, E, F, G (FM, CSA ou equivalente).',
                'instrumento disponible con certificación anti-deflagrante (anti-explosiva). según los casos puede ser ex d iib+h2 (atex, inti o equivalente) y/o clase ii, iii; div 1; grupos b, c, d, e, f, g (fm, csa o equivalente)' => 'Instrumento disponível com certificação antideflagrante (antiexplosiva). Conforme o caso, pode ser Ex d IIB+H2 (ATEX, INTI ou equivalente) e/ou Classe II, III; Div. 1; Grupos B, C, D, E, F, G (FM, CSA ou equivalente).',
                'además de la clásica gabinetería en aluminio, este instrumento esta disponible con gabineteria construida en materiales especiales, tales como: acero inoxidable, plasticos sanitarios (aprobados por fda) u otros.' => 'Além da caixa clássica em alumínio, este instrumento está disponível com gabinete construído em materiais especiais, como aço inoxidável, plásticos sanitários aprovados pela FDA ou outros.',
                'este instrumento está disponible con protocolo de comunicaciones digitales hart sobrepuesto a la señal de corriente de 4...20 ma. hart es una herramienta muy conveniente para re-calibración, re-configuración o diagnóstico remoto del instrumento.' => 'Este instrumento está disponível com protocolo de comunicação digital HART sobreposto ao sinal de corrente de 4...20 mA. HART é uma ferramenta muito conveniente para recalibração, reconfiguração ou diagnóstico remoto do instrumento.',
                'este transmisor cuenta con salida bifilar en 4...20 ma (o 4...20 ma + hart) y alimentación con una tensión nominal de 24 vcc. (tipicamente se admite que la alimentación varie de 12 a 36 vcc).' => 'Este transmissor conta com saída bifilar de 4...20 mA (ou 4...20 mA + HART) e alimentação com tensão nominal de 24 VCC. Tipicamente admite-se que a alimentação varie de 12 a 36 VCC.',
                'instrumento muy conveniente para medir vapor de agua seco, húmedo o inclusive sobre-calentado.' => 'Instrumento muito conveniente para medir vapor de água seco, úmido ou superaquecido.',
                'tipicamente este instrumento está en stock y disponible para entrega dentro de las 24 hs de colocada y aceptada la orden de compra correspondiente.' => 'Tipicamente este instrumento está em estoque e disponível para entrega em até 24 horas após a ordem de compra ser emitida e aceita.',
                'este instrumento, equipo u opción es uno de los mas populares dentro de su categoría.' => 'Este instrumento, equipamento ou opção é um dos mais populares dentro de sua categoria.',
                'este producto es uno de los que posee menor costo total de compra cuando se lo compara con otros dentro de su categoría.' => 'Este produto possui um dos menores custos totais de compra quando comparado com outros de sua categoria.',
                'este producto posee un costo total de compra intermedio cuando se lo compara con otros dentro de su categoría.' => 'Este produto possui um custo total de compra intermediário quando comparado com outros de sua categoria.',
                'este producto presenta uno de los costos de mantenimiento y calibración mas bajos cuando se lo compara con otros dentro de su categoría o con otros productos de tecnologías alternativas.' => 'Este produto apresenta um dos menores custos de manutenção e calibração quando comparado com outros de sua categoria ou com produtos de tecnologias alternativas.',
                'este producto presenta uno de los costos totales de propiedad mas bajos dentro de su categoría. incluyendo costos tales como: compra, instalación, mantenimiento, operación, calibración, etc' => 'Este produto apresenta um dos menores custos totais de propriedade dentro de sua categoria, incluindo compra, instalação, manutenção, operação, calibração etc.',
                'emg-f caudalimetro electro-magnetico standard' => 'EMG-F Medidor de Vazão Eletromagnético Padrão',
                'revestimientos en ptfe o ebonita. electrodos standard en hastelloy c.' => 'Revestimentos em PTFE ou ebonite. Eletrodos padrão em Hastelloy C.',
                'revestimientos en ptfe o ebonita. electrodos standard en hastelloy c..' => 'Revestimentos em PTFE ou ebonite. Eletrodos padrão em Hastelloy C.',
                't-10 valvula reguladora de presion de aire de precision' => 'T-10 Válvula Reguladora de Pressão de Ar de Precisão',
                'presiones reguladas de salida en rangos desde 0...25 a 0...120 psig.' => 'Pressões reguladas de saída em faixas de 0...25 a 0...120 psig.',
                'cp-300 transmisor de presion diferencial para ventilacion y aire acondicionado' => 'CP-300 Transmissor de Pressão Diferencial para Ventilação e Ar Condicionado',
                'eg-delta-t caudalimetro tipo vortex compacto' => 'Eg-Delta-T Medidor de Vazão Tipo Vortex Compacto',
                'ex-delta-w caudalimetro vortex estilo wafer' => 'Ex-Delta-W Medidor de Vazão Vortex Estilo Wafer',
                'lcc-m caudalimetro a desplazamiento positivo de alta precision' => 'LCC-M Medidor de Vazão de Deslocamento Positivo de Alta Precisão',
                'ngr-n caudalimetro a disco oscilante' => 'NGR-N Medidor de Vazão a Disco Oscilante',
                't-50 valvula reguladora de presion de aire standard' => 'T-50 Válvula Reguladora de Pressão de Ar Padrão',
                't-51 valvula reguladora de presion de aire de proposito general' => 'T-51 Válvula Reguladora de Pressão de Ar de Uso Geral',
                't-51s valvula reguladora de presion de aire anti-corrosiva' => 'T-51S Válvula Reguladora de Pressão de Ar Anticorrosiva',
                't-70 valvulas reguladoras de presion de aire para servicios especiales' => 'T-70 Válvulas Reguladoras de Pressão de Ar para Serviços Especiais',
                'th-300 transmisor de humedad para ventilacion y aire acondicionado' => 'TH-300 Transmissor de Umidade para Ventilação e Ar Condicionado',
                'tpx-770-r transmisor industrial de presion manometrica roscado' => 'TPX-770-R Transmissor Industrial de Pressão Manométrica Roscado',
                'ultra-ol caudalimetro a ruedas ovales de alta precision' => 'Ultra-OL Medidor de Vazão de Rodas Ovais de Alta Precisão',
                'a-mass transmisores avanzados de caudal masico' => 'A-Mass Transmissores Avançados de Vazão Mássica',
                'sr-90s controladores avanzados de temperatura' => 'SR-90s Controladores Avançados de Temperatura',
                'a-dfc controlador digital de caudal masico de gases' => 'A-DFC Controlador Digital de Vazão Mássica de Gases',
                'tres distintos modelos cubren el rango de 10 cm3/min a 100 lts/min.' => 'Três modelos diferentes cobrem a faixa de 10 cm3/min a 100 l/min.',
                'a-dfm caudalimetro masico multi-parametrico para gases' => 'A-DFM Medidor de Vazão Mássica Multiparamétrico para Gases',
                'fex-210 primario caudalimetrico tipo placa orificio' => 'FEX-210 Elemento Primário de Vazão Tipo Placa de Orifício',
                'fex-270 tubo pitot convencional.' => 'FEX-270 Tubo Pitot Convencional',
                'fex-290 primario caudalimetrico tipo vertedero "v"' => 'FEX-290 Elemento Primário de Vazão Tipo Vertedouro "V"',
                'fex-292 primario caudalimetrico tipo canaleta parshall' => 'FEX-292 Elemento Primário de Vazão Tipo Calha Parshall',
                'k-244x anemometro termico industrial' => 'K-244x Anemômetro Térmico Industrial',
                'f-zre analizador multigas tipo ndir' => 'F-ZRE Analisador Multigás Tipo NDIR',
                'este analizador esta disponible para determinación de múltiples gases tales como: co, co2, ch4, sh2, o2, etc' => 'Este analisador está disponível para determinação de múltiplos gases, tais como: CO, CO2, CH4, SH2, O2 etc.',
                'este analizador esta disponible para determinación de múltiples gases tales como: co, co2, ch4, sh2, o2, etc.' => 'Este analisador está disponível para determinação de múltiplos gases, tais como: CO, CO2, CH4, SH2, O2 etc.',
                'i/p-1000 transductor electro-neumatico clasico' => 'I/P-1000 Transdutor Eletropneumático Clássico',
                'i/p-2000 transductor electro-neumatico antiexplosivo' => 'I/P-2000 Transdutor Eletropneumático Antiexplosivo',
                'eh2fc ciclo integrado del hidrogeno' => 'EH2FC Ciclo Integrado do Hidrogênio',
                'muy sensible: mide desde 0.6 m/s en agua.' => 'Muito sensível: mede a partir de 0,6 m/s em água.',
                'precision nominal 1.5% de la lectura. variedad de materiales.' => 'Precisão nominal de 1,5% da leitura. Variedade de materiais.',
                'doce medidas para cubrir el rango de 0.01 a 10 lts/min.' => 'Doze tamanhos para cobrir a faixa de 0,01 a 10 l/min.',
                'incluye display local de volumen acumulado. ideal para combustibles livianos.' => 'Inclui display local de volume acumulado. Ideal para combustíveis leves.',
                'no requiere elemento primario. el sensor se ubica en el piso del canal.' => 'Não requer elemento primário. O sensor é instalado no fundo do canal.',
                'admite todo tipo de elemento primario.' => 'Aceita todos os tipos de elemento primário.',
                'medidas nominales en el rango de 1" a 16". montaje "wafer".' => 'Diâmetros nominais na faixa de 1" a 16". Montagem "wafer".',
                'opera en un rango de 0.15 a 35 m/seg.' => 'Opera em uma faixa de 0,15 a 35 m/s.',
                'similar al anterior, pero incluye una baliza led tricolor para indicar el estado del proceso.' => 'Similar ao anterior, mas inclui uma baliza LED tricolor para indicar o estado do processo.',
                'similar al anterior, pero incluye una baliza led bicolor para indicar el estado del proceso.' => 'Similar ao anterior, mas inclui uma baliza LED bicolor para indicar o estado do processo.',
                'disponibles en acero al carbono on en acero inoxidable aisi-316 (cf8m).' => 'Disponíveis em aço carbono ou em aço inoxidável AISI-316 (CF8M).',
                'construccion integral que no requiere termovaina adicional.' => 'Construção integral que não requer poço termométrico adicional.',
                'programable vias usb. entradas para rtd (pt-100). rangos configurables por el usuario.' => 'Programável via USB. Entradas para RTD (Pt-100). Faixas configuráveis pelo usuário.',
                'alta velocidad de respuesta: menor a 4...7 segs.' => 'Alta velocidade de resposta: inferior a 4...7 s.',
                'funciona mediante la interfase rs-485 de los controladores shimaden. muy versatil.' => 'Funciona por meio da interface RS-485 dos controladores Shimaden. Muito versátil.',
                'toda la arquitectura interna esta basada en modbus-rtu.' => 'Toda a arquitetura interna é baseada em Modbus-RTU.',
                'unidad remota "todo en uno". especial para implementar sistemas de control en areas geograficamente dispersas.' => 'Unidade remota "tudo em um". Especial para implementar sistemas de controle em áreas geograficamente dispersas.',
                'muy bajo consumo propio de aire.' => 'Consumo próprio de ar muito baixo.',
                'igual a la anterior, pero construida en acero inoxidable.' => 'Igual à anterior, mas construída em aço inoxidável.',
                'similar a los anteriores pero tipo "disco completo" para reemplazar iluminadores convencionales.' => 'Similar aos anteriores, mas do tipo "disco completo" para substituir iluminadores convencionais.',
                'incluye sistema para efectuar el balance de energia.' => 'Inclui sistema para realizar o balanço de energia.',
                'idem anterior, pero provistos en las instalaciones del cliente.' => 'Idem ao anterior, mas fornecidos nas instalações do cliente.',
                'contactese al +54 - (0)11 - 4753 - 0403, de lunes a viernes de 8:30 a 17:30 hs.' => 'Entre em contato pelo +54 - (0)11 - 4753 - 0403, de segunda a sexta-feira, das 8:30 às 17:30.',
                'fabricados en base a normas internacionales (iso, isa, aga, api, etc.)' => 'Fabricados com base em normas internacionais (ISO, ISA, AGA, API etc.).',
                'controladores de proceso' => 'Controladores de Processo',
                'transductores electro-neumáticos (i/p)' => 'Transdutores Eletropneumáticos (I/P)',
                'controladores de potencia eléctrica tipo scr' => 'Controladores de Potência Elétrica Tipo SCR',
                'componentes neumáticos' => 'Componentes Pneumáticos',
                'este instrumento es ideal para medir gas natural. tipicamente puede estar diseñado y/o dimensionado según normas aga (ameriacn gas association).' => 'Este instrumento é ideal para medir gás natural. Tipicamente pode ser projetado e/ou dimensionado conforme as normas AGA (American Gas Association).',
                'este instrumento no es afectado por la densidad del líquido que se esta midiendo.' => 'Este instrumento não é afetado pela densidade do líquido que está sendo medido.',
            ],
            'en' => [
                'caudal' => 'Flow',
                'nivel' => 'Level',
                'presion' => 'Pressure',
                'presión' => 'Pressure',
                'temperatura' => 'Temperature',
                'analisis' => 'Analysis',
                'análisis' => 'Analysis',
                'controladores de procesos' => 'Process controllers',
                'indicacion y registro de datos' => 'Indication and data recording',
                'indicación y registro de datos' => 'Indication and data recording',
                'productos especiales y servicios' => 'Special products and services',
                'empresa' => 'Company',
                'productos' => 'Products',
                'eventos' => 'Events',
                'ficha técnica' => 'Technical sheet',
                'manual' => 'Manual',
                'catálogo' => 'Catalog',
                'más info' => 'More info',
                'más información' => 'More information',
                'quienes somos' => 'Who we are',
                'misión' => 'Mission',
                'visión' => 'Vision',
                'calidad' => 'Quality',
                'somos una empresa dedicada a ofrecer las mejores soluciones disponibles en el sector de la instrumentación y control de procesos industriales.' => 'We are a company dedicated to offering the best available solutions in the field of industrial process instrumentation and control.',
                'somos una empresa dedicada a ofrecer las mejores soluciones disponibles en el sector de la instrumentación y control de procesos industriales. somos fabricantes de instrumental primario de medición para variables tales como caudal, nivel, presión y temperatura. asimismo fabricamos equipos vinculados con las áreas de iluminación de procesos y educación en energías renovables.' => "We are a company dedicated to offering the best available solutions in the field of Industrial Process Instrumentation and Control.\nWe manufacture primary measuring instruments for variables such as flow, level, pressure and temperature. We also manufacture equipment related to process lighting and renewable energy education.",
                'somos una empresa dedicada a ofrecer soluciones confiables e innovadoras en el sector de la instrumentación y control de procesos industriales, acompañando a nuestros clientes en la mejora continua de sus procesos productivos. nos especializamos en la fabricación de instrumental primario de medición para variables clave como caudal, nivel, presión y temperatura, desarrollando equipos precisos, robustos y adaptados a las exigencias de cada aplicación. además, diseñamos y fabricamos equipos vinculados a las áreas de iluminación de procesos y educación en energías renovables, contribuyendo al desarrollo tecnológico, la eficiencia operativa y la formación técnica.' => "We are a company dedicated to offering reliable and innovative solutions in the field of Industrial Process Instrumentation and Control, supporting our customers in the continuous improvement of their production processes.\nWe specialize in the manufacture of primary measuring instruments for key variables such as flow, level, pressure and temperature, developing precise, robust equipment adapted to the requirements of each application.\nWe also design and manufacture equipment for process lighting and renewable energy education, contributing to technological development, operational efficiency and technical training.",
                'brindar soluciones de instrumentación y control industrial que garanticen precisión, confiabilidad y eficiencia, acompañando a nuestros clientes con productos de calidad y soporte técnico especializado.' => 'Provide industrial instrumentation and control solutions that ensure precision, reliability and efficiency, supporting our customers with quality products and specialized technical support.',
                'ser una empresa referente en instrumentación industrial, reconocida por la innovación de sus desarrollos, la calidad de sus productos y su compromiso con el crecimiento tecnológico y sostenible.' => 'To be a leading company in industrial instrumentation, recognized for the innovation of its developments, the quality of its products and its commitment to technological and sustainable growth.',
                'desarrollamos y fabricamos nuestros equipos bajo estrictos estándares de calidad, asegurando precisión, durabilidad y un desempeño confiable en cada aplicación industrial.' => 'We develop and manufacture our equipment under strict quality standards, ensuring precision, durability and reliable performance in every industrial application.',
                'nueva serie de caudalímetros a desplazamiento positivo.' => 'New series of positive displacement flow meters.',
                'lanzamos una nueva serie de caudalímetros a desplazamiento positivo.' => 'We launched a new series of positive displacement flow meters.',
                'conar re-certifica exitosamente la norma iso-9001:2015' => 'CONAR successfully recertifies ISO 9001:2015',
                'conar re-certifica exitosamente la norma iso-9001:2015.' => 'CONAR successfully recertifies ISO 9001:2015.',
                'conar muestra sus logros en la expo-industria gas y petroleo' => 'CONAR showcases its achievements at the Gas and Oil Industry Expo',
                'conar presenta su nueva línea de caudalímetros a desplazamiento positivo diseñada para aplicaciones industriales que requieren alta precisión en la medición de caudal.estos instrumentos permiten medir caudales de líquidos con gran exactitud incluso en condiciones de operación exigentes. gracias a su diseño robusto y confiable, son ideales para industrias químicas, petroleras, alimenticias y farmacéuticas.los nuevos modelos incorporan mejoras en materiales, sellado y compatibilidad con sistemas de control modernos.' => 'CONAR presents its new line of positive displacement flow meters designed for industrial applications that require high flow measurement accuracy. These instruments measure liquid flow rates with excellent precision, even under demanding operating conditions. Thanks to their robust and reliable design, they are ideal for chemical, oil and gas, food and pharmaceutical industries. The new models include improvements in materials, sealing and compatibility with modern control systems.',
                'conar ha obtenido nuevamente la certificación del sistema de gestión de calidad bajo la norma iso 9001:2015.esta certificación confirma el compromiso de nuestra empresa con la mejora continua, la calidad en nuestros procesos productivos y la satisfacción de nuestros clientes.el proceso de auditoría fue llevado a cabo por el organismo certificador internacional, evaluando los sistemas de diseño, fabricación y control de nuestros instrumentos industriales.' => 'CONAR has once again obtained certification of its quality management system under ISO 9001:2015. This certification confirms the company’s commitment to continuous improvement, quality in production processes and customer satisfaction. The audit process was carried out by an international certification body, evaluating the design, manufacturing and control systems for our industrial instruments.',
                'conar participó en la expo-industria gas y petróleo presentando sus soluciones tecnológicas para medición y control de procesos industriales.durante el evento se exhibieron equipos de medición de caudal, presión, nivel y temperatura desarrollados para aplicaciones en la industria energética.la feria permitió fortalecer vínculos con clientes, distribuidores y socios estratégicos del sector industrial.' => 'CONAR participated in the Gas and Oil Industry Expo, presenting its technological solutions for industrial process measurement and control. During the event, flow, pressure, level and temperature measurement equipment developed for energy industry applications was exhibited. The trade show helped strengthen relationships with customers, distributors and strategic partners in the industrial sector.',
                'este caudalímetro opera en base a tecnología por inducción electro-magnética. solo es apto para medir líquidos conductores de la electricidad, o sea normalmente aquellos que contienen agua. la conductividad eléctrica debe ser mínimo de 5...20 usiemens .' => 'This flow meter operates using electromagnetic induction technology. It is suitable only for measuring electrically conductive liquids, usually those containing water. Minimum electrical conductivity must be 5...20 uSiemens.',
                'este caudalímetro no presenta ningún tipo de restricción al pasaje del fluido (sección de paso total). es por lo tanto un instrumento de pérdida de carga "cero".' => 'This flow meter creates no restriction to fluid passage (full-bore section). It is therefore a "zero" pressure-loss instrument.',
                'este caudalímetro electro-magnético puede ser provisto con electrodos fabicados en materiales especiales (ej.: hastelloy c, titanio, tantalio, etc) o con funciones especiales (ej.: diseño auto-limpiante)' => 'This electromagnetic flow meter can be supplied with electrodes made from special materials (e.g. Hastelloy C, titanium, tantalum, etc.) or with special functions (e.g. self-cleaning design).',
                'este caudalímetro opera en base a una tecnología térmico. según los casos específicos puede ser por calentamiento del fluido (cuerpo primario con flujo laminar) o por dispersión térmica.' => 'This flow meter operates using thermal technology. Depending on the specific case, it may operate by heating the fluid (primary body with laminar flow) or by thermal dispersion.',
                'este caudalímetro opera en base a una tecnología térmica. según los casos específicos puede ser por calentamiento del fluido (cuerpo primario con flujo laminar) o por dispersión térmica.' => 'This flow meter operates using thermal technology. Depending on the specific case, it may operate by heating the fluid (primary body with laminar flow) or by thermal dispersion.',
                'transmisor con circuitos independientes para la alimentación (que tipicamente puede ser 24 v, 110 v o 220 v) y para la señal de salida, que habitualmente es (0)4..20 ma, 0...5 vcc, 1...5 vcc o 0...10 vcc.' => 'Transmitter with independent circuits for power supply (typically 24 V, 110 V or 220 V) and for the output signal, usually (0)4..20 mA, 0...5 VDC, 1...5 VDC or 0...10 VDC.',
                'nueva serie de caudalímetros a desplazamiento positivo.' => 'New series of positive displacement flow meters.',
                'sobre nosotros' => 'About us',
                'alimentos y bebidas' => 'Food and beverages',
                'aguas & efluentes' => 'Water and effluents',
                'farmacéutica' => 'Pharmaceutical',
                'minería' => 'Mining',
                'siderurgica' => 'Steel industry',
                'siderúrgica' => 'Steel industry',
                'energía' => 'Energy',
                'pulpa & papel' => 'Pulp and paper',
                'química' => 'Chemical',
                'biocombutible' => 'Biofuel',
                'este producto es nuevo y ha sido lanzado al mercado o renovado totalmente hace menos de un año.' => 'This product is new and was launched or fully renewed less than one year ago.',
                'este instrumento es apto para medir todo tipo de fluidos. según las distintas especificaciones, puede operar con: líquidos (preferentemente poco viscosos), gases o vapor de agua.' => 'This instrument is suitable for measuring all types of fluids. Depending on the specifications, it can operate with liquids, gases or steam.',
                'este instrumento es apto para medir líquidos (tipicamente de baja viscosidad) y todo tipo de gases.' => 'This instrument is suitable for measuring liquids, typically low-viscosity liquids, and all types of gases.',
                'este instrumento es apto para medir líquidos de baja viscosidad (normalmente <= 10 cpoise) y todo tipo de gases.' => 'This instrument is suitable for measuring low-viscosity liquids (normally <= 10 cPoise) and all types of gases.',
                'este instrumento es apto para medir todo tipo de gases que sean compatibles con sus materiales de construcción.' => 'This instrument is suitable for measuring all types of gases that are compatible with its construction materials.',
                'este instrumento esta disponible con certificación de seguridad intrínseca. según los casos especificos puede ser ex ia iib+h2 t6 (atex, inti o equivalente) y/o clase i, ii, iii; div. 1; grupos b, c, d, e, f, g (fm, csa o equivalente)' => 'This instrument is available with intrinsic safety certification. Depending on the specific case, it can be Ex ia IIB+H2 T6 (ATEX, INTI or equivalent) and/or Class I, II, III; Div. 1; Groups B, C, D, E, F, G (FM, CSA or equivalent).',
                'instrumento disponible con certificación anti-deflagrante (anti-explosiva). según los casos puede ser ex d iib+h2 (atex, inti o equivalente) y/o clase ii, iii; div 1; grupos b, c, d, e, f, g (fm, csa o equivalente)' => 'Instrument available with flameproof (explosion-proof) certification. Depending on the case, it can be Ex d IIB+H2 (ATEX, INTI or equivalent) and/or Class II, III; Div. 1; Groups B, C, D, E, F, G (FM, CSA or equivalent).',
                'además de la clásica gabinetería en aluminio, este instrumento esta disponible con gabineteria construida en materiales especiales, tales como: acero inoxidable, plasticos sanitarios (aprobados por fda) u otros.' => 'In addition to the classic aluminum enclosure, this instrument is available with enclosures made from special materials such as stainless steel, FDA-approved sanitary plastics or others.',
                'este instrumento está disponible con protocolo de comunicaciones digitales hart sobrepuesto a la señal de corriente de 4...20 ma. hart es una herramienta muy conveniente para re-calibración, re-configuración o diagnóstico remoto del instrumento.' => 'This instrument is available with HART digital communication protocol overlaid on the 4...20 mA current signal. HART is a very convenient tool for remote recalibration, reconfiguration or diagnostics.',
                'este transmisor cuenta con salida bifilar en 4...20 ma (o 4...20 ma + hart) y alimentación con una tensión nominal de 24 vcc. (tipicamente se admite que la alimentación varie de 12 a 36 vcc).' => 'This transmitter has a two-wire 4...20 mA output (or 4...20 mA + HART) and a nominal 24 VDC power supply. The power supply typically accepts a 12 to 36 VDC range.',
                'instrumento muy conveniente para medir vapor de agua seco, húmedo o inclusive sobre-calentado.' => 'A very convenient instrument for measuring dry, wet or superheated steam.',
                'tipicamente este instrumento está en stock y disponible para entrega dentro de las 24 hs de colocada y aceptada la orden de compra correspondiente.' => 'This instrument is typically in stock and available for delivery within 24 hours after the purchase order is placed and accepted.',
                'este instrumento, equipo u opción es uno de los mas populares dentro de su categoría.' => 'This instrument, equipment or option is one of the most popular in its category.',
                'este producto es uno de los que posee menor costo total de compra cuando se lo compara con otros dentro de su categoría.' => 'This product has one of the lowest total purchase costs compared with others in its category.',
                'este producto posee un costo total de compra intermedio cuando se lo compara con otros dentro de su categoría.' => 'This product has an intermediate total purchase cost compared with others in its category.',
                'este producto presenta uno de los costos de mantenimiento y calibración mas bajos cuando se lo compara con otros dentro de su categoría o con otros productos de tecnologías alternativas.' => 'This product has one of the lowest maintenance and calibration costs compared with others in its category or with alternative technologies.',
                'este producto presenta uno de los costos totales de propiedad mas bajos dentro de su categoría. incluyendo costos tales como: compra, instalación, mantenimiento, operación, calibración, etc' => 'This product has one of the lowest total ownership costs in its category, including purchase, installation, maintenance, operation, calibration and related costs.',
                'emg-f caudalimetro electro-magnetico standard' => 'EMG-F Standard Electromagnetic Flow Meter',
                'revestimientos en ptfe o ebonita. electrodos standard en hastelloy c.' => 'PTFE or ebonite linings. Standard Hastelloy C electrodes.',
                'revestimientos en ptfe o ebonita. electrodos standard en hastelloy c..' => 'PTFE or ebonite linings. Standard Hastelloy C electrodes.',
                't-10 valvula reguladora de presion de aire de precision' => 'T-10 Precision Air Pressure Regulating Valve',
                'presiones reguladas de salida en rangos desde 0...25 a 0...120 psig.' => 'Regulated outlet pressure ranges from 0...25 to 0...120 psig.',
                'cp-300 transmisor de presion diferencial para ventilacion y aire acondicionado' => 'CP-300 Differential Pressure Transmitter for Ventilation and Air Conditioning',
                'eg-delta-t caudalimetro tipo vortex compacto' => 'Eg-Delta-T Compact Vortex Flow Meter',
                'ex-delta-w caudalimetro vortex estilo wafer' => 'Ex-Delta-W Wafer-Style Vortex Flow Meter',
                'lcc-m caudalimetro a desplazamiento positivo de alta precision' => 'LCC-M High-Precision Positive Displacement Flow Meter',
                'ngr-n caudalimetro a disco oscilante' => 'NGR-N Oscillating Disc Flow Meter',
                't-50 valvula reguladora de presion de aire standard' => 'T-50 Standard Air Pressure Regulating Valve',
                't-51 valvula reguladora de presion de aire de proposito general' => 'T-51 General-Purpose Air Pressure Regulating Valve',
                't-51s valvula reguladora de presion de aire anti-corrosiva' => 'T-51S Anti-Corrosive Air Pressure Regulating Valve',
                't-70 valvulas reguladoras de presion de aire para servicios especiales' => 'T-70 Air Pressure Regulating Valves for Special Services',
                'th-300 transmisor de humedad para ventilacion y aire acondicionado' => 'TH-300 Humidity Transmitter for Ventilation and Air Conditioning',
                'tpx-770-r transmisor industrial de presion manometrica roscado' => 'TPX-770-R Threaded Industrial Gauge Pressure Transmitter',
                'ultra-ol caudalimetro a ruedas ovales de alta precision' => 'Ultra-OL High-Precision Oval Gear Flow Meter',
                'a-mass transmisores avanzados de caudal masico' => 'A-Mass Advanced Mass Flow Transmitters',
                'sr-90s controladores avanzados de temperatura' => 'SR-90s Advanced Temperature Controllers',
                'a-dfc controlador digital de caudal masico de gases' => 'A-DFC Digital Mass Flow Controller for Gases',
                'tres distintos modelos cubren el rango de 10 cm3/min a 100 lts/min.' => 'Three different models cover the range from 10 cm3/min to 100 l/min.',
                'a-dfm caudalimetro masico multi-parametrico para gases' => 'A-DFM Multiparameter Mass Flow Meter for Gases',
                'fex-210 primario caudalimetrico tipo placa orificio' => 'FEX-210 Orifice Plate Primary Flow Element',
                'fex-270 tubo pitot convencional.' => 'FEX-270 Conventional Pitot Tube',
                'fex-290 primario caudalimetrico tipo vertedero "v"' => 'FEX-290 "V" Weir Primary Flow Element',
                'fex-292 primario caudalimetrico tipo canaleta parshall' => 'FEX-292 Parshall Flume Primary Flow Element',
                'k-244x anemometro termico industrial' => 'K-244x Industrial Thermal Anemometer',
                'f-zre analizador multigas tipo ndir' => 'F-ZRE NDIR Multigas Analyzer',
                'este analizador esta disponible para determinación de múltiples gases tales como: co, co2, ch4, sh2, o2, etc' => 'This analyzer is available for detecting multiple gases such as CO, CO2, CH4, SH2, O2, etc.',
                'este analizador esta disponible para determinación de múltiples gases tales como: co, co2, ch4, sh2, o2, etc.' => 'This analyzer is available for detecting multiple gases such as CO, CO2, CH4, SH2, O2, etc.',
                'i/p-1000 transductor electro-neumatico clasico' => 'I/P-1000 Classic Electropneumatic Transducer',
                'i/p-2000 transductor electro-neumatico antiexplosivo' => 'I/P-2000 Explosion-Proof Electropneumatic Transducer',
                'eh2fc ciclo integrado del hidrogeno' => 'EH2FC Integrated Hydrogen Cycle',
                'muy sensible: mide desde 0.6 m/s en agua.' => 'Very sensitive: measures from 0.6 m/s in water.',
                'precision nominal 1.5% de la lectura. variedad de materiales.' => 'Nominal accuracy of 1.5% of reading. Variety of materials.',
                'doce medidas para cubrir el rango de 0.01 a 10 lts/min.' => 'Twelve sizes cover the range from 0.01 to 10 l/min.',
                'incluye display local de volumen acumulado. ideal para combustibles livianos.' => 'Includes local accumulated volume display. Ideal for light fuels.',
                'no requiere elemento primario. el sensor se ubica en el piso del canal.' => 'No primary element required. The sensor is installed on the channel floor.',
                'admite todo tipo de elemento primario.' => 'Supports all types of primary elements.',
                'medidas nominales en el rango de 1" a 16". montaje "wafer".' => 'Nominal sizes from 1" to 16". Wafer mounting.',
                'opera en un rango de 0.15 a 35 m/seg.' => 'Operates in a range from 0.15 to 35 m/s.',
                'similar al anterior, pero incluye una baliza led tricolor para indicar el estado del proceso.' => 'Similar to the previous model, but includes a tricolor LED beacon to indicate process status.',
                'similar al anterior, pero incluye una baliza led bicolor para indicar el estado del proceso.' => 'Similar to the previous model, but includes a bicolor LED beacon to indicate process status.',
                'disponibles en acero al carbono on en acero inoxidable aisi-316 (cf8m).' => 'Available in carbon steel or AISI-316 stainless steel (CF8M).',
                'construccion integral que no requiere termovaina adicional.' => 'Integral construction that requires no additional thermowell.',
                'programable vias usb. entradas para rtd (pt-100). rangos configurables por el usuario.' => 'Programmable via USB. Inputs for RTD (Pt-100). User-configurable ranges.',
                'alta velocidad de respuesta: menor a 4...7 segs.' => 'High response speed: less than 4...7 seconds.',
                'funciona mediante la interfase rs-485 de los controladores shimaden. muy versatil.' => 'Works through the RS-485 interface of Shimaden controllers. Very versatile.',
                'toda la arquitectura interna esta basada en modbus-rtu.' => 'The entire internal architecture is based on Modbus-RTU.',
                'unidad remota "todo en uno". especial para implementar sistemas de control en areas geograficamente dispersas.' => 'All-in-one remote unit. Specially suited for implementing control systems in geographically dispersed areas.',
                'muy bajo consumo propio de aire.' => 'Very low internal air consumption.',
                'igual a la anterior, pero construida en acero inoxidable.' => 'Same as the previous model, but made of stainless steel.',
                'similar a los anteriores pero tipo "disco completo" para reemplazar iluminadores convencionales.' => 'Similar to the previous models, but full-disc type for replacing conventional illuminators.',
                'incluye sistema para efectuar el balance de energia.' => 'Includes a system for performing the energy balance.',
                'idem anterior, pero provistos en las instalaciones del cliente.' => 'Same as the previous service, but provided at the customer’s facilities.',
                'contactese al +54 - (0)11 - 4753 - 0403, de lunes a viernes de 8:30 a 17:30 hs.' => 'Contact +54 - (0)11 - 4753 - 0403, Monday to Friday from 8:30 to 17:30.',
                'fabricados en base a normas internacionales (iso, isa, aga, api, etc.)' => 'Manufactured according to international standards (ISO, ISA, AGA, API, etc.).',
                'controladores de proceso' => 'Process Controllers',
                'transductores electro-neumáticos (i/p)' => 'Electropneumatic Transducers (I/P)',
                'controladores de potencia eléctrica tipo scr' => 'SCR-Type Electric Power Controllers',
                'componentes neumáticos' => 'Pneumatic Components',
                'este instrumento es ideal para medir gas natural. tipicamente puede estar diseñado y/o dimensionado según normas aga (ameriacn gas association).' => 'This instrument is ideal for measuring natural gas. It can typically be designed and/or sized according to AGA (American Gas Association) standards.',
                'este instrumento no es afectado por la densidad del líquido que se esta midiendo.' => 'This instrument is not affected by the density of the liquid being measured.',
            ],
        ];
    }

    private function phraseTranslations(): array
    {
        return [
            'pt' => [
                'medición' => 'medição',
                'Este producto' => 'Este produto',
                'este producto' => 'este produto',
                'Este instrumento' => 'Este instrumento',
                'es nuevo' => 'é novo',
                'ha sido lanzado' => 'foi lançado',
                'al mercado' => 'no mercado',
                'renovado totalmente' => 'totalmente renovado',
                'hace menos de un año' => 'há menos de um ano',
                'disponible' => 'disponível',
                'Certificación' => 'Certificação',
                'certificación' => 'certificação',
                'Seguridad Intrínseca' => 'Segurança Intrínseca',
                'seguridad intrínseca' => 'segurança intrínseca',
                'Según los casos' => 'Conforme os casos',
                'Según las distintas especificaciones' => 'Conforme as diferentes especificações',
                'puede operar' => 'pode operar',
                'apto para medir' => 'adequado para medir',
                'todo tipo de fluidos' => 'todos os tipos de fluidos',
                'Líquidos' => 'Líquidos',
                'líquidos' => 'líquidos',
                'Gases' => 'Gases',
                'gases' => 'gases',
                'Vapor de Agua' => 'vapor de água',
                'vapor de agua' => 'vapor de água',
                'salida' => 'saída',
                'alimentación' => 'alimentação',
                'tensión nominal' => 'tensão nominal',
                'corriente' => 'corrente',
                'herramienta' => 'ferramenta',
                'conveniente' => 'conveniente',
                'diagnóstico remoto' => 'diagnóstico remoto',
                'Orden de Compra' => 'ordem de compra',
                'entrega' => 'entrega',
                'stock' => 'estoque',
                'costo total de compra' => 'custo total de compra',
                'costos' => 'custos',
                'Mantenimiento' => 'Manutenção',
                'mantenimiento' => 'manutenção',
                'Calibración' => 'Calibração',
                'calibración' => 'calibração',
                'Instalación' => 'Instalação',
                'instalación' => 'instalação',
                'Operación' => 'Operação',
                'operación' => 'operação',
                'categoría' => 'categoria',
                'otros' => 'outros',
                'otras' => 'outras',
                'compra' => 'compra',
                'menor' => 'menor',
                'bajos' => 'baixos',
                'baja viscosidad' => 'baixa viscosidade',
                'alta pressão' => 'alta pressão',
                'Nueva Serie' => 'Nova série',
                'Caudalímetros' => 'Medidores de vazão',
                'MASICO' => 'MÁSSICO',
                'masico' => 'mássico',
                'CONTROLADOR DIGITAL' => 'CONTROLADOR DIGITAL',
                'controlador digital' => 'controlador digital',
                'CAUDAL MASICO' => 'VAZÃO MÁSSICA',
                'caudal masico' => 'vazão mássica',
                'DE GASES' => 'DE GASES',
                'de gases' => 'de gases',
                'modelos cubren el rango' => 'modelos cobrem a faixa',
                'Tres distintos' => 'Três diferentes',
                'tres distintos' => 'três diferentes',
                'rango' => 'faixa',
                'lts/min' => 'l/min',
                'CONDUCTOS' => 'DUTOS',
                'conductos' => 'dutos',
                'VENTILACION' => 'VENTILAÇÃO',
                'ventilacion' => 'ventilação',
                'AIRE ACONDICIONADO' => 'AR CONDICIONADO',
                'aire acondicionado' => 'ar condicionado',
                'AVANZADO' => 'AVANÇADO',
                'avanzado' => 'avançado',
                'MANOMETRICA' => 'MANOMÉTRICA',
                'manometrica' => 'manométrica',
                'ABSOLUTA' => 'ABSOLUTA',
                'absoluta' => 'absoluta',
                'PROPOSITO GENERAL' => 'USO GERAL',
                'proposito general' => 'uso geral',
                'MAGNETICO' => 'MAGNÉTICO',
                'magnetico' => 'magnético',
                'BRIDADO' => 'FLANGEADO',
                'bridado' => 'flangeado',
                'SANITARIO' => 'SANITÁRIO',
                'sanitario' => 'sanitário',
                'DIAFRAGMA AL RAS' => 'DIAFRAGMA RENTE',
                'diafragma al ras' => 'diafragma rente',
                'SELLOS REMOTOS' => 'SELOS REMOTOS',
                'sellos remotos' => 'selos remotos',
                'MULTIPROPOSITO' => 'MULTIPROPÓSITO',
                'multiproposito' => 'multipropósito',
                'Opera en rango desde' => 'Opera em faixa de',
                'opera en rango desde' => 'opera em faixa de',
                'hasta' => 'até',
                'canales independientes' => 'canais independentes',
                'montaje en panel' => 'montagem em painel',
                'Entradas y rangos programables' => 'Entradas e faixas programáveis',
                'CAUDALIMETRO' => 'MEDIDOR DE VAZÃO',
                'CAUDALÍMETRO' => 'MEDIDOR DE VAZÃO',
                'Caudalimetro' => 'Medidor de vazão',
                'Caudalímetro' => 'Medidor de vazão',
                'caudalimetro' => 'medidor de vazão',
                'caudalímetro' => 'medidor de vazão',
                'ELECTRO-MAGNETICO' => 'ELETROMAGNÉTICO',
                'ELECTRO-MAGNÉTICO' => 'ELETROMAGNÉTICO',
                'Electro-Magnetico' => 'Eletromagnético',
                'Electro-Magnético' => 'Eletromagnético',
                'electro-magnetico' => 'eletromagnético',
                'electro-magnético' => 'eletromagnético',
                'STANDARD' => 'PADRÃO',
                'Standard' => 'Padrão',
                'standard' => 'padrão',
                'Revestimientos' => 'Revestimentos',
                'revestimientos' => 'revestimentos',
                'Ebonita' => 'ebonite',
                'ebonita' => 'ebonite',
                'Electrodos' => 'Eletrodos',
                'electrodos' => 'eletrodos',
                'en Hastelloy' => 'em Hastelloy',
                'en PTFE' => 'em PTFE',
                ' o ' => ' ou ',
                'Desplazamiento Positivo' => 'deslocamento positivo',
                'medicion' => 'medição',
                'control industrial' => 'controle industrial',
                'control de procesos' => 'controle de processos',
                'instrumentación' => 'instrumentação',
                'instrumentacion' => 'instrumentação',
                'transmisor' => 'transmissor',
                'transmisores' => 'transmissores',
                'sensor' => 'sensor',
                'sensores' => 'sensores',
                'indicador' => 'indicador',
                'indicadores' => 'indicadores',
                'registrador' => 'registrador',
                'registradores' => 'registradores',
                'válvula' => 'válvula',
                'valvula' => 'válvula',
                'válvulas' => 'válvulas',
                'valvulas' => 'válvulas',
                'presión' => 'pressão',
                'presion' => 'pressão',
                'caudal' => 'vazão',
                'nivel' => 'nível',
                'temperatura' => 'temperatura',
                'humedad' => 'umidade',
                'producto' => 'produto',
                'productos' => 'produtos',
                'industria' => 'indústria',
                'industrias' => 'indústrias',
                'servicio' => 'serviço',
                'servicios' => 'serviços',
                'solución' => 'solução',
                'soluciones' => 'soluções',
                'equipo' => 'equipamento',
                'equipos' => 'equipamentos',
                'aplicación' => 'aplicação',
                'aplicaciones' => 'aplicações',
                'datos' => 'dados',
                'descarga' => 'download',
                'descargas' => 'downloads',
                'novedad' => 'novidade',
                'novedades' => 'novidades',
                'calidad' => 'qualidade',
                'seguridad' => 'segurança',
                'empresa' => 'empresa',
                'contacto' => 'contato',
                'consulta' => 'consulta',
                'cotización' => 'orçamento',
                'cotizacion' => 'orçamento',
                'ficha técnica' => 'ficha técnica',
                'características' => 'características',
                'caracteristicas' => 'características',
                'para' => 'para',
                'con' => 'com',
                'de ' => 'de ',
                ' y ' => ' e ',
                ' o ' => ' ou ',
            ],
            'en' => [
                'medición' => 'measurement',
                'Este producto' => 'This product',
                'este producto' => 'this product',
                'Este instrumento' => 'This instrument',
                'este instrumento' => 'this instrument',
                'es nuevo' => 'is new',
                'ha sido lanzado' => 'has been launched',
                'al mercado' => 'to the market',
                'renovado totalmente' => 'fully renewed',
                'hace menos de un año' => 'less than one year ago',
                'disponible' => 'available',
                'Certificación' => 'Certification',
                'certificación' => 'certification',
                'Seguridad Intrínseca' => 'Intrinsic Safety',
                'seguridad intrínseca' => 'intrinsic safety',
                'Según los casos' => 'Depending on the case',
                'Según las distintas especificaciones' => 'Depending on the specifications',
                'puede operar' => 'can operate',
                'apto para medir' => 'suitable for measuring',
                'medir' => 'measure',
                'todo tipo de fluidos' => 'all types of fluids',
                'Líquidos' => 'Liquids',
                'líquidos' => 'liquids',
                'Gases' => 'Gases',
                'gases' => 'gases',
                'Vapor de Agua' => 'steam',
                'vapor de agua' => 'steam',
                'salida' => 'output',
                'alimentación' => 'power supply',
                'tensión nominal' => 'nominal voltage',
                'corriente' => 'current',
                'herramienta' => 'tool',
                'conveniente' => 'convenient',
                'diagnóstico remoto' => 'remote diagnostics',
                'Orden de Compra' => 'purchase order',
                'entrega' => 'delivery',
                'stock' => 'stock',
                'costo total de compra' => 'total purchase cost',
                'costos' => 'costs',
                'Mantenimiento' => 'Maintenance',
                'mantenimiento' => 'maintenance',
                'Calibración' => 'Calibration',
                'calibración' => 'calibration',
                'Instalación' => 'Installation',
                'instalación' => 'installation',
                'Operación' => 'Operation',
                'operación' => 'operation',
                'categoría' => 'category',
                'otros' => 'others',
                'otras' => 'others',
                'compra' => 'purchase',
                'menor' => 'lower',
                'bajos' => 'low',
                'baja viscosidad' => 'low viscosity',
                'Nueva Serie' => 'New series',
                'Caudalímetros' => 'Flow meters',
                'MASICO' => 'MASS',
                'masico' => 'mass',
                'CONTROLADOR DIGITAL' => 'DIGITAL CONTROLLER',
                'controlador digital' => 'digital controller',
                'CAUDAL MASICO' => 'MASS FLOW',
                'caudal masico' => 'mass flow',
                'DE GASES' => 'FOR GASES',
                'de gases' => 'for gases',
                'modelos cubren el rango' => 'models cover the range',
                'Tres distintos' => 'Three different',
                'tres distintos' => 'three different',
                'rango' => 'range',
                'lts/min' => 'l/min',
                'CONDUCTOS' => 'DUCTS',
                'conductos' => 'ducts',
                'VENTILACION' => 'VENTILATION',
                'ventilacion' => 'ventilation',
                'AIRE ACONDICIONADO' => 'AIR CONDITIONING',
                'aire acondicionado' => 'air conditioning',
                'AVANZADO' => 'ADVANCED',
                'avanzado' => 'advanced',
                'MANOMETRICA' => 'GAUGE',
                'manometrica' => 'gauge',
                'ABSOLUTA' => 'ABSOLUTE',
                'absoluta' => 'absolute',
                'PROPOSITO GENERAL' => 'GENERAL-PURPOSE',
                'proposito general' => 'general-purpose',
                'MAGNETICO' => 'MAGNETIC',
                'magnetico' => 'magnetic',
                'BRIDADO' => 'FLANGED',
                'bridado' => 'flanged',
                'SANITARIO' => 'SANITARY',
                'sanitario' => 'sanitary',
                'DIAFRAGMA AL RAS' => 'FLUSH DIAPHRAGM',
                'diafragma al ras' => 'flush diaphragm',
                'SELLOS REMOTOS' => 'REMOTE SEALS',
                'sellos remotos' => 'remote seals',
                'MULTIPROPOSITO' => 'MULTIPURPOSE',
                'multiproposito' => 'multipurpose',
                'Opera en rango desde' => 'Operates in a range from',
                'opera en rango desde' => 'operates in a range from',
                'hasta' => 'to',
                'canales independientes' => 'independent channels',
                'montaje en panel' => 'panel mounting',
                'Entradas y rangos programables' => 'Programmable inputs and ranges',
                'CAUDALIMETRO' => 'FLOW METER',
                'CAUDALÍMETRO' => 'FLOW METER',
                'Caudalimetro' => 'Flow meter',
                'Caudalímetro' => 'Flow meter',
                'caudalimetro' => 'flow meter',
                'caudalímetro' => 'flow meter',
                'ELECTRO-MAGNETICO' => 'ELECTROMAGNETIC',
                'ELECTRO-MAGNÉTICO' => 'ELECTROMAGNETIC',
                'Electro-Magnetico' => 'Electromagnetic',
                'Electro-Magnético' => 'Electromagnetic',
                'electro-magnetico' => 'electromagnetic',
                'electro-magnético' => 'electromagnetic',
                'STANDARD' => 'STANDARD',
                'Standard' => 'Standard',
                'standard' => 'standard',
                'Revestimientos' => 'Linings',
                'revestimientos' => 'linings',
                'Ebonita' => 'ebonite',
                'ebonita' => 'ebonite',
                'Electrodos' => 'Electrodes',
                'electrodos' => 'electrodes',
                'en Hastelloy' => 'in Hastelloy',
                'en PTFE' => 'in PTFE',
                'Desplazamiento Positivo' => 'positive displacement',
                'medicion' => 'measurement',
                'control industrial' => 'industrial control',
                'control de procesos' => 'process control',
                'instrumentación' => 'instrumentation',
                'instrumentacion' => 'instrumentation',
                'transmisor' => 'transmitter',
                'transmisores' => 'transmitters',
                'sensor' => 'sensor',
                'sensores' => 'sensors',
                'indicador' => 'indicator',
                'indicadores' => 'indicators',
                'registrador' => 'recorder',
                'registradores' => 'recorders',
                'válvula' => 'valve',
                'valvula' => 'valve',
                'válvulas' => 'valves',
                'valvulas' => 'valves',
                'presión' => 'pressure',
                'presion' => 'pressure',
                'caudal' => 'flow',
                'nivel' => 'level',
                'temperatura' => 'temperature',
                'humedad' => 'humidity',
                'producto' => 'product',
                'productos' => 'products',
                'industria' => 'industry',
                'industrias' => 'industries',
                'servicio' => 'service',
                'servicios' => 'services',
                'solución' => 'solution',
                'soluciones' => 'solutions',
                'equipo' => 'equipment',
                'equipos' => 'equipment',
                'aplicación' => 'application',
                'aplicaciones' => 'applications',
                'datos' => 'data',
                'descarga' => 'download',
                'descargas' => 'downloads',
                'novedad' => 'news',
                'novedades' => 'news',
                'calidad' => 'quality',
                'seguridad' => 'safety',
                'empresa' => 'company',
                'contacto' => 'contact',
                'consulta' => 'inquiry',
                'cotización' => 'quote',
                'cotizacion' => 'quote',
                'ficha técnica' => 'technical sheet',
                'características' => 'features',
                'caracteristicas' => 'features',
                'para' => 'for',
                'con' => 'with',
                ' de ' => ' of ',
                ' y ' => ' and ',
                ' o ' => ' or ',
            ],
        ];
    }
}
