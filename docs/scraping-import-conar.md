# Scraping Controlado CONAR

## Objetivo

Importar productos y activos de una categoría externa de CONAR hacia el catálogo local sin renderizar HTML externo ni tocar la capa pública Blade/CSS.

## Arquitectura

```txt
app/
├── Console/Commands/
│   ├── ImportConarCategoryCommand.php
│   └── RollbackImportRunCommand.php
├── DTOs/Scraping/Conar/
│   ├── ConarCategoryData.php
│   ├── ConarDownloadData.php
│   ├── ConarImageData.php
│   ├── ConarProductData.php
│   └── ConarSpecificationData.php
├── Jobs/
│   ├── RollbackImportRunJob.php
│   └── RunConarCategoryImportJob.php
├── Parsers/
│   └── ConarCategoryParser.php
├── Services/
│   ├── Importers/
│   │   ├── ConarCatalogImporter.php
│   │   ├── ConarCategoryImportOrchestrator.php
│   │   ├── ImportRollbackService.php
│   │   └── ImportRunRecorder.php
│   └── Scraping/
│       ├── Conar/
│       │   ├── ConarCategoryCrawler.php
│       │   └── ConarCategoryNormalizer.php
│       ├── RemoteAssetDownloader.php
│       ├── ScraperHttpClient.php
│       └── Support/
│           └── HtmlDocument.php
└── Models/
    ├── ImportRun.php
    ├── ImportRunItem.php
    └── ImportRunLog.php

database/migrations/
├── 2026_05_06_090000_create_import_runs_table.php
├── 2026_05_06_090100_create_import_run_items_table.php
├── 2026_05_06_090200_create_import_run_logs_table.php
└── 2026_05_06_090300_add_source_tracking_to_catalog_tables.php
```

## Flujo

1. `catalog:import-conar-category` crea una corrida en `import_runs`.
2. `ConarCategoryCrawler` descarga la categoría externa.
3. `ConarCategoryParser` detecta subcategoría, productos, specs, imágenes y descargas.
4. `ConarCategoryNormalizer` limpia textos y deduplica.
5. `ConarCatalogImporter` hace `upsert` en:
   - `product_categories`
   - `product_subcategories`
   - `products`
   - `product_specifications`
   - `product_downloads`
   - `product_images`
6. `RemoteAssetDownloader` guarda imágenes/PDFs en `storage/app/public`.
7. `ImportRunRecorder` registra snapshots por entidad.
8. `catalog:rollback-import` revierte solo esa corrida.

## Comandos

```bash
php artisan migrate
php artisan catalog:import-conar-category "https://www.conar.com/es/productos_y_servicios/33/Caudalimetros_por_Presion_Diferencial.html" --family="Caudal"
php artisan catalog:import-conar-category "https://www.conar.com/es/productos_y_servicios/33/Caudalimetros_por_Presion_Diferencial.html" --family="Caudal" --dry-run
php artisan catalog:rollback-import 1
```

## Notas operativas

- `--dry-run` registra la corrida y valida parsing, pero no persiste catálogo.
- `--without-assets` evita descargar imágenes y PDFs.
- El rollback elimina registros creados en la corrida y restaura snapshots de registros actualizados.
- El frontend público no se modifica: la importación solo rellena modelos y storage locales.
