#!/usr/bin/env php
<?php
/**
 * Script de validation syntax PHP pour block_managepages
 *
 * @package     block_managepages
 * @copyright   2025 Maxime Cruzel
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Couleurs pour la sortie terminal
function color_output($text, $color = 'white') {
    $colors = [
        'red' => "\033[31m",
        'green' => "\033[32m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'white' => "\033[37m",
        'reset' => "\033[0m"
    ];
    
    return $colors[$color] . $text . $colors['reset'];
}

echo color_output("=== Validation syntaxique block_managepages ===\n", 'blue');
echo color_output("Date: " . date('Y-m-d H:i:s') . "\n\n", 'yellow');

// Liste des fichiers PHP à valider
$php_files = [
    'block_managepages.php',
    'export.php',
    'settings.php',
    'version.php',
    'classes/exporter.php',
    'classes/output/main.php',
    'classes/privacy/provider.php',
    'db/access.php',
    'db/upgrade.php',
    'lang/en/block_managepages.php',
    'lang/fr/block_managepages.php',
    'tests/block_managepages_test.php',
    'tests/exporter_test.php',
    'tests/main_test.php'
];

$errors = 0;
$checked = 0;

foreach ($php_files as $file) {
    if (!file_exists($file)) {
        echo color_output("⚠️  SKIP: $file (fichier non trouvé)\n", 'yellow');
        continue;
    }
    
    $output = [];
    $return_code = 0;
    exec("php -l \"$file\" 2>&1", $output, $return_code);
    
    $checked++;
    
    if ($return_code === 0) {
        echo color_output("✅ OK: $file\n", 'green');
    } else {
        echo color_output("❌ ERREUR: $file\n", 'red');
        foreach ($output as $line) {
            echo color_output("   $line\n", 'red');
        }
        $errors++;
    }
}

echo "\n" . color_output("=== Résumé ===\n", 'blue');
echo color_output("Fichiers vérifiés: $checked\n", 'white');

if ($errors === 0) {
    echo color_output("✅ Tous les fichiers sont syntaxiquement corrects!\n", 'green');
    exit(0);
} else {
    echo color_output("❌ $errors fichier(s) avec des erreurs de syntaxe\n", 'red');
    exit(1);
}
