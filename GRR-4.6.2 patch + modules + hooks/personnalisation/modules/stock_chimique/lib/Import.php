<?php

class StockChimiqueImport
{
    const CSV_FILE = 'import_stock_chimique.csv';
    const BATCH_SIZE = 50;

    private static $requiredHeaders = array(
        'action',
        'source_row',
        'product_key',
        'create_product',
        'reference_interne',
        'nom_commercial',
        'synonymes',
        'formule_chimique',
        'numero_cas',
        'fournisseur',
        'reference_fournisseur',
        'mentions_h',
        'etat_physique',
        'conditionnement',
        'nombre_conditionnements',
        'create_container',
        'quantite_stock',
        'unite_stock',
        'statut_cmr',
        'emplacement',
        'date_reception',
        'fds_importer',
        'fds_path',
        'fds_revision',
        'fds_revision_source',
        'fds_courante',
        'import_status',
        'import_notes',
    );

    public static function importStorageDir()
    {
        return dirname(__DIR__).'/storage/import';
    }

    public static function ensureImportStorage()
    {
        $directory = self::importStorageDir();
        return is_dir($directory) ? is_readable($directory) : @mkdir($directory, 0750, true);
    }

    public static function availablePackages()
    {
        if (!self::ensureImportStorage()) {
            return array();
        }
        $packages = array();
        $entries = @scandir(self::importStorageDir());
        if (!is_array($entries)) {
            return array();
        }
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..' || !self::validPackageName($entry)) {
                continue;
            }
            $directory = self::importStorageDir().'/'.$entry;
            if (is_dir($directory) && is_file($directory.'/'.self::CSV_FILE)) {
                $packages[] = $entry;
            }
        }
        natcasesort($packages);
        return array_values($packages);
    }

    public static function preview($package)
    {
        $loaded = self::loadPackage($package);
        if (!$loaded['ok']) {
            return $loaded;
        }
        $errors = array();
        $warnings = array();
        $products = array();
        $suppliers = array();
        $locations = array();
        $documents = array();
        $importRows = 0;
        $containerRows = 0;
        $documentRows = 0;
        $skippedRows = 0;
        $sourceRows = array();
        $units = StockChimiqueRepository::productUnits();
        $productUnits = array();
        $referenceProducts = array();
        $currentDocuments = array();
        $documentCounts = array();

        foreach ($loaded['rows'] as $index => $row) {
            $csvLine = $index + 2;
            $action = self::value($row, 'action');
            if (!in_array($action, array('importer', 'ignorer'), true)) {
                $errors[] = self::issue($csvLine, 'action invalide : '.$action);
                continue;
            }
            if ($action === 'ignorer') {
                $skippedRows++;
                continue;
            }
            $importRows++;
            $sourceRow = (int) self::value($row, 'source_row');
            $name = self::value($row, 'nom_commercial');
            $productKey = self::value($row, 'product_key');
            $reference = self::value($row, 'reference_interne');
            $unit = strtolower(self::value($row, 'unite_stock'));
            if (!preg_match('/^[1-9]\d*$/', self::value($row, 'source_row'))) {
                $errors[] = self::issue($csvLine, 'source_row invalide');
            } elseif (isset($sourceRows[$sourceRow])) {
                $errors[] = self::issue($csvLine, 'source_row dupliquée avec la ligne CSV '.$sourceRows[$sourceRow]);
            } else {
                $sourceRows[$sourceRow] = $csvLine;
            }
            if ($name === '' || $productKey === '' || $reference === '') {
                $errors[] = self::issue($csvLine, 'produit, clé produit ou référence interne manquant');
            }
            foreach (array(
                'product_key' => 100,
                'reference_interne' => 100,
                'nom_commercial' => 190,
                'fournisseur' => 190,
                'reference_fournisseur' => 100,
                'conditionnement' => 190,
                'emplacement' => 190,
            ) as $field => $maximum) {
                if (mb_strlen(self::value($row, $field), 'UTF-8') > $maximum) {
                    $errors[] = self::issue($csvLine, $field.' dépasse '.$maximum.' caractères');
                }
            }
            if (!isset($units[$unit])) {
                $errors[] = self::issue($csvLine, 'unité de stock invalide : '.$unit);
            }
            if (mb_strlen(self::value($row, 'numero_cas'), 'UTF-8') > 190) {
                $errors[] = self::issue($csvLine, 'liste CAS trop longue');
            }
            if (!in_array(self::value($row, 'etat_physique'), array('non_renseigne', 'solide', 'liquide', 'gaz', 'autre'), true)) {
                $errors[] = self::issue($csvLine, 'état physique invalide');
            }
            if (!in_array(self::value($row, 'statut_cmr'), array('non_renseigne', 'non', 'oui'), true)) {
                $errors[] = self::issue($csvLine, 'statut CMR invalide');
            }
            foreach (array('create_product', 'create_container', 'fds_importer', 'fds_courante') as $flag) {
                if (!self::validFlag(self::value($row, $flag))) {
                    $errors[] = self::issue($csvLine, $flag.' doit valoir 0 ou 1');
                }
            }
            if (isset($productUnits[$productKey]) && $productUnits[$productKey] !== $unit) {
                $errors[] = self::issue($csvLine, 'un même produit utilise plusieurs unités de stock');
            }
            $productUnits[$productKey] = $unit;
            if (isset($referenceProducts[$reference]) && $referenceProducts[$reference] !== $productKey) {
                $errors[] = self::issue($csvLine, 'référence interne utilisée par plusieurs clés produit');
            }
            $referenceProducts[$reference] = $productKey;
            $products[$productKey] = true;
            if (self::value($row, 'fournisseur') !== '') {
                $suppliers[mb_strtolower(self::value($row, 'fournisseur'), 'UTF-8')] = true;
            }

            if (self::truthy(self::value($row, 'create_container'))) {
                $containerRows++;
                $quantity = self::decimal(self::value($row, 'quantite_stock'));
                if ($quantity === null || $quantity <= 0) {
                    $errors[] = self::issue($csvLine, 'quantité de stock invalide');
                }
                $location = self::value($row, 'emplacement');
                if ($location === '') {
                    $errors[] = self::issue($csvLine, 'emplacement manquant pour le contenant');
                } else {
                    $locations[mb_strtolower($location, 'UTF-8')] = true;
                }
                if (!self::validDate(self::value($row, 'date_reception'), true)) {
                    $errors[] = self::issue($csvLine, 'date de réception invalide');
                }
            }

            if (self::truthy(self::value($row, 'fds_importer'))) {
                $documentRows++;
                $relative = self::value($row, 'fds_path');
                $file = self::safePackageFile($loaded['directory'], $relative);
                if ($file === '' || !is_file($file)) {
                    $errors[] = self::issue($csvLine, 'FDS introuvable ou chemin interdit : '.$relative);
                } elseif (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'pdf') {
                    $errors[] = self::issue($csvLine, 'une FDS doit être un PDF : '.$relative);
                } elseif (!self::isPdfFile($file)) {
                    $errors[] = self::issue($csvLine, 'le contenu de la FDS n est pas un PDF valide : '.$relative);
                } else {
                    $documentKey = $productKey.'|'.strtolower(str_replace('\\', '/', $relative));
                    if (isset($documents[$documentKey])) {
                        $errors[] = self::issue($csvLine, 'FDS dupliquée pour le même produit : '.$relative);
                    }
                    $documents[$documentKey] = true;
                    $documentCounts[$productKey] = isset($documentCounts[$productKey])
                        ? $documentCounts[$productKey] + 1
                        : 1;
                }
                if (!self::validDate(self::value($row, 'fds_revision'), false)) {
                    $errors[] = self::issue($csvLine, 'date de révision FDS manquante ou invalide');
                }
                if (self::truthy(self::value($row, 'fds_courante'))) {
                    $currentDocuments[$productKey] = isset($currentDocuments[$productKey])
                        ? $currentDocuments[$productKey] + 1
                        : 1;
                }
            }

            if (self::value($row, 'import_status') === 'AVERTISSEMENT') {
                $warnings[] = self::issue($csvLine, self::value($row, 'import_notes'));
            }
        }
        foreach ($documentCounts as $productKey => $documentCount) {
            $currentCount = isset($currentDocuments[$productKey]) ? $currentDocuments[$productKey] : 0;
            if ($documentCount > 0 && $currentCount !== 1) {
                $errors[] = 'Produit '.$productKey.' : une seule FDS doit être marquée comme courante.';
            }
        }

        return array(
            'ok' => count($errors) === 0,
            'package' => $loaded['package'],
            'directory' => $loaded['directory'],
            'csv' => $loaded['csv'],
            'hash' => $loaded['hash'],
            'rows' => $loaded['rows'],
            'total_rows' => count($loaded['rows']),
            'import_rows' => $importRows,
            'skipped_rows' => $skippedRows,
            'products' => count($products),
            'suppliers' => count($suppliers),
            'locations' => count($locations),
            'containers' => $containerRows,
            'documents' => count($documents),
            'document_rows' => $documentRows,
            'errors' => $errors,
            'warnings' => $warnings,
        );
    }

    public static function execute($package, $expectedHash, $login)
    {
        $result = array(
            'ok' => false,
            'processed' => 0,
            'already_imported' => 0,
            'products_created' => 0,
            'containers_created' => 0,
            'documents_created' => 0,
            'attempted' => 0,
            'remaining' => 0,
            'completed' => false,
            'errors' => array(),
        );
        $preview = self::preview($package);
        if (!$preview['ok']) {
            $result['errors'] = array_merge(array('Le paquet contient des erreurs.'), $preview['errors']);
            return $result;
        }
        $result['remaining'] = $preview['import_rows'];
        if (!preg_match('/^[a-f0-9]{64}$/', (string) $expectedHash) || !hash_equals($preview['hash'], (string) $expectedHash)) {
            $result['errors'] = array('Le fichier CSV a changé depuis la prévisualisation.');
            return $result;
        }

        $result['ok'] = true;
        foreach ($preview['rows'] as $row) {
            if (self::value($row, 'action') !== 'importer') {
                continue;
            }
            $sourceRow = (int) self::value($row, 'source_row');
            if (StockChimiqueRepository::importRowStatus($preview['hash'], $sourceRow) === 'success') {
                $result['already_imported']++;
                continue;
            }
            if ($result['attempted'] >= self::BATCH_SIZE) {
                continue;
            }
            $result['attempted']++;
            $productId = 0;
            $containerId = 0;
            $documentId = 0;
            try {
                $supplierId = self::resolveSupplier($row, $login);
                $productId = self::resolveProduct($row, $supplierId, $login, $result);
                if ($productId <= 0) {
                    throw new RuntimeException('Création ou recherche du produit impossible.');
                }
                if (self::truthy(self::value($row, 'create_container'))) {
                    $locationId = self::resolveLocation($row, $login);
                    $containerId = self::resolveContainer(
                        $row,
                        $productId,
                        $supplierId,
                        $locationId,
                        $preview['package'],
                        $login,
                        $result
                    );
                    if ($containerId <= 0) {
                        throw new RuntimeException('Création du contenant impossible.');
                    }
                }
                if (self::truthy(self::value($row, 'fds_importer'))) {
                    $documentId = self::resolveDocument(
                        $row,
                        $productId,
                        $preview['directory'],
                        $login,
                        $result
                    );
                    if ($documentId <= 0) {
                        throw new RuntimeException('Import de la FDS impossible.');
                    }
                }
                if (!StockChimiqueRepository::logImportRow(
                    $preview['hash'],
                    $preview['package'],
                    $sourceRow,
                    $productId,
                    $containerId,
                    $documentId,
                    'success',
                    self::value($row, 'import_notes'),
                    $login
                )) {
                    throw new RuntimeException('Journalisation de la ligne importée impossible.');
                }
                $result['processed']++;
            } catch (Throwable $exception) {
                $result['ok'] = false;
                $message = 'Ligne source '.$sourceRow.' : '.$exception->getMessage();
                $result['errors'][] = $message;
                StockChimiqueRepository::logImportRow(
                    $preview['hash'],
                    $preview['package'],
                    $sourceRow,
                    $productId,
                    $containerId,
                    $documentId,
                    'error',
                    $message,
                    $login
                );
            }
        }
        $result['remaining'] = max(0, $preview['import_rows'] - $result['already_imported'] - $result['processed']);
        $result['completed'] = $result['remaining'] === 0;
        StockChimiqueRepository::log(
            'import_execute',
            'import',
            0,
            $preview['package'].' : '.$result['processed'].' ligne(s) traitée(s), '
                .$result['remaining'].' restante(s), '.count($result['errors']).' erreur(s)',
            $login
        );
        return $result;
    }

    private static function resolveSupplier($row, $login)
    {
        $name = self::value($row, 'fournisseur');
        if ($name === '') {
            return 0;
        }
        $id = StockChimiqueRepository::supplierIdByName($name);
        if ($id > 0) {
            return $id;
        }
        $created = StockChimiqueRepository::saveSupplier(array('nom' => $name), $login);
        if (empty($created['ok'])) {
            throw new RuntimeException(isset($created['error']) ? $created['error'] : 'Création du fournisseur impossible.');
        }
        return (int) $created['id'];
    }

    private static function resolveLocation($row, $login)
    {
        $name = self::value($row, 'emplacement');
        $id = StockChimiqueRepository::locationIdByName($name);
        if ($id > 0) {
            return $id;
        }
        $code = 'IMP-'.strtoupper(substr(hash('sha256', mb_strtolower($name, 'UTF-8')), 0, 12));
        $created = StockChimiqueRepository::saveLocation(array(
            'code' => $code,
            'nom' => $name,
            'type_emplacement' => self::locationType($name),
            'description' => 'Créé par import de l inventaire historique.',
        ), $login);
        if (empty($created['ok'])) {
            throw new RuntimeException(isset($created['error']) ? $created['error'] : 'Création de l emplacement impossible.');
        }
        return (int) $created['id'];
    }

    private static function resolveProduct($row, $supplierId, $login, &$result)
    {
        $reference = self::value($row, 'reference_interne');
        $id = StockChimiqueRepository::productIdByReference($reference);
        if ($id > 0) {
            $existing = StockChimiqueRepository::product($id);
            $existingSupplierReference = mb_strtolower(trim((string) (isset($existing['reference_fournisseur']) ? $existing['reference_fournisseur'] : '')), 'UTF-8');
            $importSupplierReference = mb_strtolower(self::value($row, 'reference_fournisseur'), 'UTF-8');
            $existingCas = trim((string) (isset($existing['numero_cas']) ? $existing['numero_cas'] : ''));
            $importCas = self::value($row, 'numero_cas');
            if (
                !$existing
                || (string) $existing['unite_stock'] !== strtolower(self::value($row, 'unite_stock'))
                || (int) $existing['fournisseur_id'] !== (int) $supplierId
                || $existingSupplierReference !== $importSupplierReference
                || ($existingCas !== '' && $importCas !== '' && $existingCas !== $importCas)
            ) {
                throw new RuntimeException('La référence interne existe déjà pour un produit incompatible.');
            }
            return $id;
        }
        $description = array();
        if (self::value($row, 'synonymes') !== '') {
            $description[] = 'Synonymes : '.self::value($row, 'synonymes');
        }
        if (self::value($row, 'formule_chimique') !== '') {
            $description[] = 'Formule : '.self::value($row, 'formule_chimique');
        }
        $description[] = 'Import inventaire historique - ligne source '.(int) self::value($row, 'source_row');
        $created = StockChimiqueRepository::saveProduct(array(
            'reference_interne' => $reference,
            'nom_commercial' => self::value($row, 'nom_commercial'),
            'fournisseur_id' => $supplierId,
            'reference_fournisseur' => self::value($row, 'reference_fournisseur'),
            'numero_cas' => self::value($row, 'numero_cas'),
            'etat_physique' => self::value($row, 'etat_physique'),
            'unite_stock' => strtolower(self::value($row, 'unite_stock')),
            'mentions_h' => self::value($row, 'mentions_h'),
            'statut_cmr' => self::value($row, 'statut_cmr'),
            'description' => implode("\n", $description),
            'notes' => self::value($row, 'import_notes'),
            'seuil_minimal' => '0',
        ), $login);
        if (empty($created['ok'])) {
            throw new RuntimeException(isset($created['error']) ? $created['error'] : 'Création du produit impossible.');
        }
        $result['products_created']++;
        return (int) $created['id'];
    }

    private static function resolveContainer($row, $productId, $supplierId, $locationId, $packageName, $login, &$result)
    {
        $sourceRow = (int) self::value($row, 'source_row');
        $packageKey = hash('sha256', mb_strtolower((string) $packageName, 'UTF-8'));
        $code = 'IMP-'.strtoupper(substr($packageKey, 0, 8)).'-R'.$sourceRow;
        $id = StockChimiqueRepository::containerIdByCode($code);
        if ($id > 0) {
            $existing = StockChimiqueRepository::container($id);
            if (!$existing || (int) $existing['produit_id'] !== (int) $productId) {
                throw new RuntimeException('Le code de contenant existe déjà pour un autre produit.');
            }
            return $id;
        }
        $notes = array(
            'Import inventaire historique.',
            'Nombre de conditionnements source : '.self::value($row, 'nombre_conditionnements'),
        );
        if (self::value($row, 'import_notes') !== '') {
            $notes[] = self::value($row, 'import_notes');
        }
        $created = StockChimiqueRepository::createContainer(array(
            'produit_id' => $productId,
            'fournisseur_id' => $supplierId,
            'emplacement_id' => $locationId,
            'code_interne' => $code,
            'conditionnement' => self::value($row, 'conditionnement'),
            'quantite' => self::value($row, 'quantite_stock'),
            'date_reception' => self::value($row, 'date_reception'),
            'notes' => implode("\n", $notes),
            'request_token' => hash('sha256', $packageKey.':container:'.$sourceRow),
        ), $login);
        if (empty($created['ok'])) {
            throw new RuntimeException(isset($created['error']) ? $created['error'] : 'Création du contenant impossible.');
        }
        $result['containers_created']++;
        return (int) $created['id'];
    }

    private static function resolveDocument($row, $productId, $packageDirectory, $login, &$result)
    {
        $source = self::safePackageFile($packageDirectory, self::value($row, 'fds_path'));
        if ($source === '' || !is_file($source)) {
            throw new RuntimeException('FDS source introuvable.');
        }
        if (!self::isPdfFile($source)) {
            throw new RuntimeException('Le contenu du fichier indiqué comme FDS n est pas un PDF valide.');
        }
        $sha256 = hash_file('sha256', $source);
        $existing = StockChimiqueRepository::documentIdByHash($productId, $sha256);
        if ($existing > 0) {
            return $existing;
        }
        if (!StockChimiqueRepository::ensureDocumentStorage()) {
            throw new RuntimeException('Stockage documentaire non accessible.');
        }
        try {
            $storedName = bin2hex(random_bytes(32));
        } catch (Throwable $exception) {
            $storedName = hash('sha256', uniqid('', true).mt_rand());
        }
        $target = StockChimiqueRepository::documentPath($storedName);
        if ($target === '' || !@copy($source, $target)) {
            throw new RuntimeException('Copie de la FDS impossible.');
        }
        $detectedMime = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected = finfo_file($finfo, $target);
                finfo_close($finfo);
                if (is_string($detected) && $detected !== '') {
                    $detectedMime = $detected;
                }
            }
        }
        if (!self::isPdfFile($target)) {
            @unlink($target);
            throw new RuntimeException('La copie de la FDS ne contient plus un PDF valide.');
        }
        $mime = 'application/pdf';
        $documentId = StockChimiqueRepository::addDocument(array(
            'produit_id' => $productId,
            'type_document' => 'fds',
            'langue' => 'fr',
            'emetteur' => self::value($row, 'fournisseur'),
            'date_revision' => self::value($row, 'fds_revision'),
            'numero_version' => '',
            'est_courant' => self::truthy(self::value($row, 'fds_courante')) ? 1 : 0,
            'description' => 'Import historique. Source date : '.self::value($row, 'fds_revision_source')
                .($detectedMime !== '' && $detectedMime !== 'application/pdf' ? '. MIME détecté : '.$detectedMime : ''),
            'original_name' => basename($source),
            'stored_name' => $storedName,
            'mime_type' => $mime,
            'taille' => (int) filesize($target),
            'sha256' => $sha256,
        ), $login);
        if ($documentId <= 0) {
            @unlink($target);
            throw new RuntimeException('Enregistrement de la FDS impossible.');
        }
        $result['documents_created']++;
        return $documentId;
    }

    private static function loadPackage($package)
    {
        if (!self::validPackageName($package) || !self::ensureImportStorage()) {
            return array('ok' => false, 'errors' => array('Nom de paquet invalide.'));
        }
        $directory = realpath(self::importStorageDir().'/'.$package);
        $root = realpath(self::importStorageDir());
        if ($directory === false || $root === false || !self::pathIsInside($directory, $root)) {
            return array('ok' => false, 'errors' => array('Dossier d import introuvable.'));
        }
        $csv = $directory.'/'.self::CSV_FILE;
        if (!is_file($csv) || !is_readable($csv)) {
            return array('ok' => false, 'errors' => array('CSV d import introuvable.'));
        }
        $handle = fopen($csv, 'rb');
        if (!$handle) {
            return array('ok' => false, 'errors' => array('Lecture du CSV impossible.'));
        }
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }
        $headers = fgetcsv($handle, 0, ';', '"', '\\');
        if (!is_array($headers)) {
            fclose($handle);
            return array('ok' => false, 'errors' => array('En-tête CSV invalide.'));
        }
        $headers = array_map('trim', $headers);
        $missing = array_diff(self::$requiredHeaders, $headers);
        if (count($missing) > 0) {
            fclose($handle);
            return array('ok' => false, 'errors' => array('Colonnes manquantes : '.implode(', ', $missing)));
        }
        $rows = array();
        while (($values = fgetcsv($handle, 0, ';', '"', '\\')) !== false) {
            if (count($values) === 1 && trim((string) $values[0]) === '') {
                continue;
            }
            if (count($values) !== count($headers)) {
                fclose($handle);
                return array('ok' => false, 'errors' => array('Nombre de colonnes incohérent dans le CSV.'));
            }
            $rows[] = array_combine($headers, $values);
        }
        fclose($handle);
        return array(
            'ok' => true,
            'package' => $package,
            'directory' => $directory,
            'csv' => $csv,
            'hash' => hash_file('sha256', $csv),
            'rows' => $rows,
            'errors' => array(),
        );
    }

    private static function safePackageFile($packageDirectory, $relative)
    {
        $relative = str_replace('\\', '/', trim((string) $relative));
        if (
            $relative === ''
            || strpos($relative, "\0") !== false
            || preg_match('~(^|/)\.\.(/|$)~', $relative)
            || preg_match('~^[A-Za-z]:~', $relative)
            || substr($relative, 0, 1) === '/'
        ) {
            return '';
        }
        $file = realpath($packageDirectory.'/'.str_replace('/', DIRECTORY_SEPARATOR, $relative));
        return $file !== false && self::pathIsInside($file, $packageDirectory) ? $file : '';
    }

    private static function isPdfFile($file)
    {
        if (!is_file($file) || !is_readable($file) || filesize($file) < 16) {
            return false;
        }
        $handle = @fopen($file, 'rb');
        if (!$handle) {
            return false;
        }
        $start = fread($handle, 1024);
        $size = (int) filesize($file);
        $tailLength = min(4096, $size);
        fseek($handle, -$tailLength, SEEK_END);
        $end = fread($handle, $tailLength);
        fclose($handle);
        return is_string($start)
            && is_string($end)
            && strpos($start, '%PDF-') !== false
            && strpos($end, '%%EOF') !== false;
    }

    private static function pathIsInside($path, $root)
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');
        $root = rtrim(str_replace('\\', '/', $root), '/');
        if (DIRECTORY_SEPARATOR === '\\') {
            $path = strtolower($path);
            $root = strtolower($root);
        }
        return $path === $root || strpos($path, $root.'/') === 0;
    }

    private static function validPackageName($package)
    {
        return preg_match('/^[a-zA-Z0-9._ -]{1,100}$/', trim((string) $package)) === 1
            && $package !== '.'
            && $package !== '..';
    }

    private static function locationType($name)
    {
        $name = mb_strtolower((string) $name, 'UTF-8');
        if (strpos($name, 'armoire') !== false) {
            return 'armoire';
        }
        if (strpos($name, 'frigo') !== false || strpos($name, 'réfrig') !== false) {
            return 'refrigerateur';
        }
        if (
            strpos($name, 'laboratoire') !== false
            || strpos($name, 'atelier') !== false
            || strpos($name, 'soute') !== false
            || strpos($name, 'stockage') !== false
        ) {
            return 'local';
        }
        return 'autre';
    }

    private static function decimal($value)
    {
        $value = str_replace(',', '.', trim((string) $value));
        return preg_match('/^\d+(?:\.\d{1,4})?$/', $value) ? (float) $value : null;
    }

    private static function validDate($value, $allowEmpty)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $allowEmpty;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return false;
        }
        $parts = array_map('intval', explode('-', $value));
        return checkdate($parts[1], $parts[2], $parts[0]);
    }

    private static function truthy($value)
    {
        return in_array(strtolower(trim((string) $value)), array('1', 'oui', 'yes', 'true'), true);
    }

    private static function validFlag($value)
    {
        return in_array(trim((string) $value), array('0', '1'), true);
    }

    private static function value($row, $key)
    {
        return isset($row[$key]) ? trim((string) $row[$key]) : '';
    }

    private static function issue($line, $message)
    {
        return 'Ligne CSV '.$line.' : '.$message;
    }
}
