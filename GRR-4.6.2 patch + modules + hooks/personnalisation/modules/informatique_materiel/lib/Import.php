<?php

class InformatiqueMaterielImport
{
    const MAX_UPLOAD_BYTES = 5242880;
    const MAX_ROWS = 10000;

    public static function types()
    {
        return array(
            'categories' => 'Categories',
            'people' => 'Personnes',
            'items' => 'Materiels',
            'loans' => 'Prets',
        );
    }

    public static function storageDir()
    {
        $moduleDir = defined('GRR_INFORMATIQUE_MATERIEL_MODULE_DIR')
            ? GRR_INFORMATIQUE_MATERIEL_MODULE_DIR
            : dirname(__DIR__);

        return $moduleDir.'/storage/imports';
    }

    public static function ensureStorage()
    {
        $dir = self::storageDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }

        return is_dir($dir) && is_writable($dir);
    }

    public static function saveUpload($file, $type, $login)
    {
        $types = self::types();
        if (!isset($types[$type])) {
            return array('ok' => false, 'errors' => array('Type de donnees invalide.'));
        }
        if (!self::ensureStorage()) {
            return array('ok' => false, 'errors' => array('Le dossier de stockage des imports est indisponible.'));
        }
        if (!isset($file['error']) || (int) $file['error'] !== UPLOAD_ERR_OK) {
            return array('ok' => false, 'errors' => array('Fichier CSV introuvable ou transfert incomplet.'));
        }
        if (!isset($file['size']) || (int) $file['size'] <= 0 || (int) $file['size'] > self::MAX_UPLOAD_BYTES) {
            return array('ok' => false, 'errors' => array('Le fichier CSV doit faire moins de 5 Mo.'));
        }

        $originalName = self::cleanFileName(isset($file['name']) ? $file['name'] : 'import.csv');
        if (preg_match('/\.(csv|txt)$/i', $originalName) !== 1) {
            return array('ok' => false, 'errors' => array('Le fichier doit etre au format CSV.'));
        }

        $tmpName = isset($file['tmp_name']) ? (string) $file['tmp_name'] : '';
        if ($tmpName === '' || !is_file($tmpName)) {
            return array('ok' => false, 'errors' => array('Fichier temporaire introuvable.'));
        }

        $hash = hash_file('sha256', $tmpName);
        $storedName = $type.'_'.$hash.'.csv';
        $target = self::storageDir().'/'.$storedName;
        if (!is_file($target)) {
            $moved = is_uploaded_file($tmpName)
                ? move_uploaded_file($tmpName, $target)
                : copy($tmpName, $target);
            if (!$moved) {
                return array('ok' => false, 'errors' => array('Impossible de stocker le fichier CSV.'));
            }
        }

        InformatiqueMaterielRepository::log('import_charge', 'import', 0, $type.' '.$originalName, $login);

        return array(
            'ok' => true,
            'type' => $type,
            'hash' => $hash,
            'stored_name' => $storedName,
            'original_name' => $originalName,
            'errors' => array(),
        );
    }

    public static function preview($type, $hash, $storedName, $originalName)
    {
        $rows = self::parseStoredCsv($type, $hash, $storedName, $originalName);
        $summary = array(
            'total' => count($rows),
            'valid' => 0,
            'errors' => 0,
            'already_done' => 0,
        );

        foreach ($rows as $row) {
            if (!empty($row['already_done'])) {
                $summary['already_done']++;
            } elseif (count($row['errors']) > 0) {
                $summary['errors']++;
            } else {
                $summary['valid']++;
            }
        }

        return array(
            'type' => $type,
            'hash' => $hash,
            'stored_name' => $storedName,
            'original_name' => $originalName,
            'rows' => $rows,
            'summary' => $summary,
        );
    }

    public static function execute($type, $hash, $storedName, $originalName, $login)
    {
        $preview = self::preview($type, $hash, $storedName, $originalName);
        $result = array(
            'created' => 0,
            'conflicts' => 0,
            'errors' => 0,
            'skipped' => 0,
            'messages' => array(),
        );

        foreach ($preview['rows'] as $row) {
            if (!empty($row['already_done'])) {
                $result['skipped']++;
                continue;
            }

            if (count($row['errors']) > 0) {
                $result['errors']++;
                InformatiqueMaterielRepository::logImportLine(
                    $hash,
                    $originalName,
                    $type,
                    (int) $row['source_row'],
                    'error',
                    implode(' | ', $row['errors']),
                    array(),
                    $login
                );
                continue;
            }

            $save = self::saveRow(
                $type,
                $row['values'],
                $login,
                array(
                    'package_hash' => $hash,
                    'package_name' => $originalName,
                    'source_table' => $type,
                    'source_row' => (int) $row['source_row'],
                )
            );
            if (!empty($save['ok'])) {
                $ids = self::idsFromSave($type, $save);
                $status = !empty($save['conflict']) ? 'conflict' : 'success';
                $message = isset($save['message']) ? (string) $save['message'] : '';
                InformatiqueMaterielRepository::logImportLine(
                    $hash,
                    $originalName,
                    $type,
                    (int) $row['source_row'],
                    $status,
                    $message,
                    $ids,
                    $login
                );
                if (!empty($save['conflict'])) {
                    $result['conflicts']++;
                } else {
                    $result['created']++;
                }
            } else {
                $errors = isset($save['errors']) && is_array($save['errors']) ? $save['errors'] : array('Import impossible.');
                InformatiqueMaterielRepository::logImportLine(
                    $hash,
                    $originalName,
                    $type,
                    (int) $row['source_row'],
                    'error',
                    implode(' | ', $errors),
                    array(),
                    $login
                );
                $result['errors']++;
            }
        }

        InformatiqueMaterielRepository::log(
            'import_execute',
            'import',
            0,
            $type.' '.$originalName.' : '.$result['created'].' creees, '.$result['conflicts'].' conflits, '.$result['errors'].' erreurs',
            $login
        );

        return $result;
    }

    private static function parseStoredCsv($type, $hash, $storedName, $originalName)
    {
        $types = self::types();
        if (!isset($types[$type]) || !self::validHash($hash) || !self::validStoredName($storedName)) {
            return array();
        }
        if (strpos($storedName, $type.'_') !== 0) {
            return array();
        }

        $path = self::storageDir().'/'.$storedName;
        if (!is_file($path)) {
            return array();
        }

        $handle = fopen($path, 'r');
        if (!$handle) {
            return array();
        }

        $delimiter = self::detectDelimiter($path);
        $header = fgetcsv($handle, 0, $delimiter);
        if (!$header) {
            fclose($handle);
            return array();
        }
        $map = self::headerMap($header);
        $rows = array();
        $rowNumber = 1;

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNumber++;
            if ($rowNumber > self::MAX_ROWS + 1) {
                break;
            }
            if (self::emptyCsvRow($data)) {
                continue;
            }

            $raw = self::assocRow($map, $data);
            $values = self::normalizeRow($type, $raw);
            $errors = self::validateRow($type, $values);
            $status = InformatiqueMaterielRepository::importLineStatus($hash, $type, $rowNumber);
            $rows[] = array(
                'source_row' => $rowNumber,
                'raw' => $raw,
                'values' => $values,
                'errors' => $errors,
                'already_done' => isset($status['status']) && $status['status'] !== '',
                'status' => isset($status['status']) ? $status['status'] : '',
                'message' => isset($status['message']) ? $status['message'] : '',
            );
        }

        fclose($handle);

        return $rows;
    }

    private static function saveRow($type, $values, $login, $context = array())
    {
        if ($type === 'categories') {
            return InformatiqueMaterielRepository::saveCategory($values, $login);
        }
        if ($type === 'people') {
            return InformatiqueMaterielRepository::savePerson($values, $login);
        }
        if ($type === 'items') {
            return InformatiqueMaterielRepository::saveItem($values, $login);
        }
        if ($type === 'loans') {
            return InformatiqueMaterielRepository::importLoanRecord($values, $login, $context);
        }

        return array('ok' => false, 'errors' => array('Type de donnees invalide.'));
    }

    private static function idsFromSave($type, $save)
    {
        $id = isset($save['id']) ? (int) $save['id'] : 0;
        if ($type === 'people') {
            return array('personne_id' => $id);
        }
        if ($type === 'items') {
            return array('item_id' => $id);
        }
        if ($type === 'loans') {
            return array('pret_id' => $id);
        }

        return array();
    }

    private static function normalizeRow($type, $raw)
    {
        if ($type === 'categories') {
            return array(
                'prefixe' => self::value($raw, array('prefixe', 'prefix')),
                'designation' => self::value($raw, array('designation', 'categorie', 'type')),
                'description' => self::value($raw, array('description', 'commentaire')),
            );
        }

        if ($type === 'people') {
            return array(
                'legacy_id' => self::value($raw, array('id', 'legacy id', 'id excel')),
                'identifiant_legacy' => self::value($raw, array('identifiant personnel', 'identifiant legacy', 'identifiant')),
                'prenom' => self::value($raw, array('prenom')),
                'nom' => self::value($raw, array('nom')),
                'cadre_usage' => self::value($raw, array('cadre de l usage', 'cadre usage', 'cadre')),
                'date_depart' => self::normalizeDate(self::value($raw, array('date depart', 'date de depart'))),
                'login_grr' => self::value($raw, array('login grr', 'login')),
                'email' => self::value($raw, array('email', 'mail', 'courriel')),
                'notes' => self::value($raw, array('notes', 'commentaire')),
            );
        }

        if ($type === 'items') {
            $designation = self::value($raw, array('designation', 'type materiel'));
            $identifier = self::value($raw, array('identifiant materiel', 'identifiant', 'identifiant legacy'));
            $categoryId = (int) self::value($raw, array('categorie id', 'categorie_id'));
            if ($categoryId <= 0) {
                $categoryId = InformatiqueMaterielRepository::categoryIdByDesignation($designation);
            }
            if ($categoryId <= 0) {
                $categoryId = self::categoryIdByNormalizedDesignation($designation);
            }

            return array(
                'identifiant' => $identifier,
                'identifiant_legacy' => $identifier,
                'categorie_id' => $categoryId,
                'designation' => $designation,
                'precision_materiel' => self::value($raw, array('precision', 'precision materiel')),
                'mac' => self::value($raw, array('mac')),
                'marque' => self::value($raw, array('marque')),
                'numero_serie' => self::value($raw, array('numero de serie', 'numero serie', 'serie')),
                'code_barre_usmb' => self::value($raw, array('code barre usmb', 'code-barres usmb', 'code barres usmb')),
                'os' => self::value($raw, array('os')),
                'annee' => self::value($raw, array('annee')),
                'commentaire' => self::value($raw, array('commentaire')),
                'localisation_stockage' => self::value($raw, array('localisation', 'localisation stockage')),
                'statut' => self::value($raw, array('statut')) === '' ? 'stocke' : self::value($raw, array('statut')),
                'pret_multiple' => self::value($raw, array(
                    'pret multiple',
                    'pret_multiple',
                    'materiel generique',
                    'generique',
                    'multi pret',
                    'multi-pret',
                )),
                'notes' => self::value($raw, array('notes')),
            );
        }

        if ($type === 'loans') {
            $personIdentifier = self::value($raw, array('identifiant personnel', 'personne', 'personne identifiant'));
            $itemIdentifier = self::value($raw, array('identifiant materiel', 'materiel', 'item'));
            $personId = (int) self::value($raw, array('personne id', 'personne_id'));
            $itemId = (int) self::value($raw, array('item id', 'item_id', 'materiel id'));
            if ($personId <= 0) {
                $personId = InformatiqueMaterielRepository::personIdByLegacyIdentifier($personIdentifier);
            }
            if ($itemId <= 0) {
                $itemId = InformatiqueMaterielRepository::itemIdByIdentifier($itemIdentifier);
            }

            return array(
                'personne_id' => $personId,
                'item_id' => $itemId,
                'localisation' => self::value($raw, array('localisation')),
                'date_debut' => self::normalizeDate(self::value($raw, array('date debut', 'debut'))),
                'date_fin_prevue' => self::normalizeDate(self::value($raw, array('date fin prevue', 'fin prevue'))),
                'date_fin_effective' => self::normalizeDate(self::value($raw, array('date fin effective', 'retour', 'date retour'))),
                'commentaire' => self::value($raw, array('commentaire')),
                'motif_anomalie' => self::value($raw, array('motif anomalie', 'motif', 'anomalie')),
                'action_proposee' => self::value($raw, array('action proposee', 'action proposée')),
                'justification' => self::value($raw, array('justification')),
                'ligne_excel_source' => self::value($raw, array('ligne excel', 'ligne source')),
                'identifiant_personnel_source' => $personIdentifier,
                'identifiant_materiel_source' => $itemIdentifier,
                'identifiant_materiel_excel_source' => self::value($raw, array('identifiant materiel excel', 'identifiant matériel excel')),
            );
        }

        return array();
    }

    private static function validateRow($type, $values)
    {
        $errors = array();
        if ($type === 'categories') {
            if ($values['prefixe'] === '') {
                $errors[] = 'Prefixe manquant.';
            }
            if ($values['designation'] === '') {
                $errors[] = 'Designation manquante.';
            }
        } elseif ($type === 'people') {
            if ($values['prenom'] === '') {
                $errors[] = 'Prenom manquant.';
            }
            if ($values['nom'] === '') {
                $errors[] = 'Nom manquant.';
            }
        } elseif ($type === 'items') {
            if ((int) $values['categorie_id'] <= 0) {
                $errors[] = 'Categorie introuvable.';
            }
            if ($values['identifiant'] === '') {
                $errors[] = 'Identifiant materiel manquant.';
            }
            if ($values['designation'] === '') {
                $errors[] = 'Designation manquante.';
            }
        } elseif ($type === 'loans') {
            if ((int) $values['personne_id'] <= 0) {
                $errors[] = 'Personne introuvable.';
            }
            if ((int) $values['item_id'] <= 0) {
                $errors[] = 'Materiel introuvable.';
            }
            if ($values['date_debut'] === '') {
                $errors[] = 'Date de debut manquante.';
            }
        }

        return $errors;
    }

    private static function categoryIdByNormalizedDesignation($designation)
    {
        $needle = self::normalizeHeader($designation);
        if ($needle === '') {
            return 0;
        }

        foreach (InformatiqueMaterielRepository::categories(false) as $category) {
            if (self::normalizeHeader($category['designation']) === $needle) {
                return (int) $category['id'];
            }
        }

        return 0;
    }

    private static function detectDelimiter($path)
    {
        $line = '';
        $handle = fopen($path, 'r');
        if ($handle) {
            $line = (string) fgets($handle);
            fclose($handle);
        }

        $candidates = array(";" => substr_count($line, ';'), "," => substr_count($line, ','), "\t" => substr_count($line, "\t"));
        arsort($candidates);
        $keys = array_keys($candidates);

        return isset($keys[0]) ? $keys[0] : ';';
    }

    private static function headerMap($header)
    {
        $map = array();
        foreach ($header as $index => $name) {
            $key = self::normalizeHeader($name);
            if ($key !== '') {
                $map[$index] = $key;
            }
        }

        return $map;
    }

    private static function assocRow($map, $data)
    {
        $row = array();
        foreach ($map as $index => $key) {
            $row[$key] = isset($data[$index]) ? trim((string) $data[$index]) : '';
        }

        return $row;
    }

    private static function value($row, $keys)
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && trim((string) $row[$key]) !== '') {
                return trim((string) $row[$key]);
            }
        }

        return '';
    }

    private static function normalizeHeader($value)
    {
        $value = trim((string) $value);
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($converted !== false) {
                $value = $converted;
            }
        }
        $value = strtolower($value);
        $value = str_replace(array('_', '-', '/', '\\', "'", '"'), ' ', $value);
        $value = preg_replace('/[^a-z0-9 ]+/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value);
    }

    private static function normalizeDate($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value;
        }
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $value, $matches) === 1) {
            return sprintf('%04d-%02d-%02d', (int) $matches[3], (int) $matches[2], (int) $matches[1]);
        }
        if (is_numeric($value) && (float) $value > 20000) {
            $timestamp = ((float) $value - 25569) * 86400;
            return gmdate('Y-m-d', (int) $timestamp);
        }

        return $value;
    }

    private static function emptyCsvRow($data)
    {
        foreach ($data as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private static function cleanFileName($name)
    {
        $name = basename((string) $name);
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);

        return $name === '' ? 'import.csv' : substr($name, 0, 190);
    }

    private static function validHash($hash)
    {
        return preg_match('/^[a-f0-9]{64}$/', (string) $hash) === 1;
    }

    private static function validStoredName($name)
    {
        return preg_match('/^[A-Za-z0-9_]{1,30}_[a-f0-9]{64}\.csv$/', (string) $name) === 1;
    }
}
