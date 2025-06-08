# Changelog - Block ManagePages pour Moodle 5.0

## Version 1.2.0 (2025-06-08)

### ✅ Fonctionnalités implémentées
- **Affichage conditionnel** : Le bloc affiche toutes les sections sur `/course/view.php` et uniquement la section courante sur `/course/section.php`
- **Boutons d'édition** : Ajout des boutons ✏️ pour modifier les pages (avec vérification des permissions)
- **Export Markdown** : Fonctionnalité d'export complète avec support ZIP pour plusieurs pages
- **Copie presse-papier** : Bouton pour copier le contenu Markdown directement
- **Détection correcte de l'ID de cours** : Résolution du bug de confusion entre ID de section et ID de cours

### 🔧 Corrections techniques
- **Installation Moodle** : Ajout du fichier `db/install.xml` manquant
- **Syntaxe PHP** : Correction de `applicable_formats()` avec `'course' => true`
- **JavaScript** : Nettoyage des logs de débogage et amélioration de la détection d'ID de cours
- **Permissions** : Vérification correcte des capacités `moodle/course:manageactivities`

### 🧪 Tests unitaires
- **Tests complets** : 3 fichiers de tests couvrant toutes les fonctionnalités
  - `tests/block_managepages_test.php` : Tests du bloc principal
  - `tests/main_test.php` : Tests de la logique d'affichage et filtrage
  - `tests/exporter_test.php` : Tests des fonctionnalités d'export
- **Couverture** : Tests des permissions, du filtrage par section, de l'export, et de la structure des données

### 📁 Structure du projet
```
block_managepages/
├── block_managepages.php          # Bloc principal
├── export.php                     # Gestionnaire d'export
├── version.php                     # Version 1.2.0 (2025060801)
├── classes/
│   ├── exporter.php               # Logique d'export
│   └── output/main.php            # Logique d'affichage avec filtrage
├── db/
│   ├── access.php                 # Permissions
│   └── install.xml                # Configuration installation
├── templates/
│   └── block_managepages.mustache # Template avec JS corrigé
├── tests/                         # Tests unitaires complets
└── lang/                          # Traductions FR/EN
```

### 🎯 Fonctionnement
1. **Sur /course/view.php** : Affiche toutes les sections et leurs pages
2. **Sur /course/section.php** : Affiche uniquement les pages de la section courante
3. **Boutons d'édition** : Visibles uniquement pour les utilisateurs avec permissions d'édition
4. **Export** : Support single page (MD) ou multiple pages (ZIP)
5. **Presse-papier** : Copie structurée par sections

### 🔄 Migration depuis version précédente
- Mise à jour automatique via l'interface d'administration Moodle
- Aucune perte de données ou configuration
- Compatibilité maintenue avec Moodle 4.0+

### 📋 Tests d'installation
Pour tester l'installation :
1. Placer le dossier dans `/blocks/managepages/` 
2. Aller sur Administration > Notifications
3. Suivre la procédure de mise à jour
4. Ajouter le bloc à un cours pour tester

### 🧪 Exécution des tests
```bash
# Vérification syntaxe
php -l tests/*.php

# Tests avec PHPUnit (si configuré)
vendor/bin/phpunit tests/
```

---

## Développeur
**Maxime Cruzel** - Adaptation Moodle 5.0 avec filtrage par section et tests complets
