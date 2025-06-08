# Guide de résolution des problèmes d'installation

## Erreur rencontrée
```
Erreur lors de la mise à jour de block_managepages à la version 2025060801. 
Impossible de continuer.
Error code: upgradeerror
```

## Solutions à essayer dans l'ordre

### 1. Vider le cache Moodle
1. Aller dans **Administration du site > Développement > Purger tous les caches**
2. Cliquer sur "Purger tous les caches"
3. Retenter l'installation

### 2. Supprimer et réinstaller le plugin
1. **Administration du site > Plugins > Aperçu des plugins**
2. Chercher `block_managepages`
3. Cliquer sur "Désinstaller" si présent
4. Supprimer physiquement le dossier `blocks/managepages/`
5. Remettre le dossier du plugin
6. Aller sur **Administration du site > Notifications**

### 3. Installation manuelle via base de données
Si le problème persiste, vous pouvez forcer l'enregistrement de la version :

```sql
-- Vérifier si le plugin existe dans la table config_plugins
SELECT * FROM mdl_config_plugins WHERE plugin = 'block_managepages';

-- Si il existe, mettre à jour la version
UPDATE mdl_config_plugins 
SET value = '2025060802' 
WHERE plugin = 'block_managepages' AND name = 'version';

-- Si il n'existe pas, l'insérer
INSERT INTO mdl_config_plugins (plugin, name, value) 
VALUES ('block_managepages', 'version', '2025060802');
```

### 4. Vérification des fichiers obligatoires
Assurez-vous que ces fichiers existent :
- ✅ `block_managepages.php`
- ✅ `version.php` (version 2025060802)
- ✅ `db/access.php`
- ✅ `db/install.xml`
- ✅ `db/upgrade.php`
- ✅ `lang/en/block_managepages.php`

### 5. Mode debug pour plus d'informations
1. **Administration du site > Développement > Débogage**
2. Activer "Debug messages = DEVELOPER"
3. Retenter l'installation pour voir les erreurs détaillées

### 6. Installation en mode sécurisé
Si rien ne fonctionne, créer une version minimaliste :

1. Renommer le plugin temporairement : `block_managepages_old`
2. Créer un nouveau dossier `block_managepages` avec seulement :
   - `block_managepages.php` (version simplifiée)
   - `version.php` (version 2025060803)
   - `db/access.php`
   - `lang/en/block_managepages.php`
3. Installer cette version minimale
4. Ensuite remplacer par la version complète

## Contact support
Si le problème persiste :
- Vérifier les logs Apache/Nginx
- Consulter les logs Moodle dans `/var/log/` ou équivalent
- Tester sur un environnement de développement d'abord
