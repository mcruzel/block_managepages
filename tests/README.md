# Tests unitaires pour block_managepages

Ce dossier contient les tests unitaires pour le plugin block_managepages de Moodle.

## Structure des tests

- `block_managepages_test.php` - Tests pour la classe principale du bloc
- `exporter_test.php` - Tests pour la classe d'export des pages
- `main_test.php` - Tests pour la classe de rendu des templates

## Exécution des tests

### Prérequis
- Installation Moodle avec le plugin block_managepages
- PHPUnit configuré dans Moodle
- Accès en ligne de commande au serveur Moodle

### Commandes pour exécuter les tests

```bash
# Depuis le répertoire racine de Moodle
php admin/tool/phpunit/cli/init.php

# Exécuter tous les tests du plugin
vendor/bin/phpunit blocks/managepages/tests/

# Exécuter un test spécifique
vendor/bin/phpunit blocks/managepages/tests/block_managepages_test.php
vendor/bin/phpunit blocks/managepages/tests/exporter_test.php
vendor/bin/phpunit blocks/managepages/tests/main_test.php
```

### Tests couverts

#### block_managepages_test.php
- ✅ Création et instanciation du bloc
- ✅ Formats applicables (cours uniquement)
- ✅ Permissions d'export (professeurs vs étudiants)
- ✅ Génération du contenu du bloc avec pages
- ✅ Gestion des cours sans pages

#### exporter_test.php
- ✅ Instanciation de la classe exporter
- ✅ Récupération du contenu des pages
- ✅ Récupération des pages sélectionnées
- ✅ Conversion en markdown
- ✅ Nettoyage des noms de fichiers
- ✅ Génération de markdown structuré
- ✅ Création de fichiers ZIP
- ✅ Gestion des chemins de sections
- ✅ Gestion des erreurs (ID invalides)

#### main_test.php
- ✅ Instanciation de la classe main
- ✅ Export pour template sur page cours (/course/view.php)
- ✅ Export pour template sur page section (/course/section.php)
- ✅ Capacités d'édition selon les permissions
- ✅ Gestion des cours sans pages
- ✅ Validation de la structure des données exportées

## Couverture fonctionnelle

Les tests couvrent :
- 🔒 **Sécurité** : Vérification des permissions et capacités
- 📄 **Fonctionnalité principale** : Export des pages en markdown
- 🔧 **Filtrage par section** : Affichage conditionnel selon la page
- ✏️ **Édition** : Boutons d'édition selon les permissions
- 📦 **Export multiple** : Création de fichiers ZIP
- 🎯 **Robustesse** : Gestion des cas d'erreur et des cours vides

## Notes importantes

- Les tests utilisent `$this->resetAfterTest()` pour isoler chaque test
- Les tests créent des cours et utilisateurs temporaires
- La base de données de test est automatiquement nettoyée
- Les tests sont compatibles avec Moodle 4.0+ et 5.0+

## Maintenance

Pour ajouter de nouveaux tests :
1. Créer une nouvelle méthode `test_*` dans la classe appropriée
2. Utiliser `setUp()` pour l'initialisation commune
3. Utiliser les générateurs Moodle pour créer les données de test
4. Nettoyer avec `resetAfterTest()` si nécessaire
