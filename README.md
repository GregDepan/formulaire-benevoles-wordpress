# Plugin WordPress - Formulaire Bénévoles

Plugin WordPress complet pour la gestion des bénévoles d'événements.

## 🎯 Fonctionnalités

### Gestion des événements
- ✅ Création, modification et duplication d'événements
- ✅ Date limite d'inscription configurable
- ✅ Description personnalisable
- ✅ Support des événements passés et futurs

### Gestion des stands et créneaux
- ✅ Stands multiples par événement (Buvette, Librairie, Papeterie, etc.)
- ✅ Créneaux horaires avec quotas par stand
- ✅ Groupes d'exclusion pour éviter les conflits
- ✅ Interface d'édition visuelle drag-and-drop

### Inscriptions
- ✅ Formulaire public responsive
- ✅ Inscriptions multiples par bénévole
- ✅ Détection automatique des conflits de créneaux
- ✅ Modification possible avant la date limite
- ✅ Emails de confirmation automatiques

### Administration
- ✅ Dashboard unifié avec statistiques
- ✅ Export CSV pivoté (une ligne par bénévole, une colonne par stand)
- ✅ Gestion des inscriptions (confirmation, liste d'attente)
- ✅ Vue détaillée par événement

## 📦 Installation

1. Télécharger le plugin
2. Dans WordPress : **Extensions → Ajouter → Téléverser une extension**
3. Activer le plugin
4. Configurer dans le menu **Bénévoles**

## 🚀 Utilisation

### Créer un événement
1. Menu **Bénévoles → 📊 Tableau de bord**
2. Cliquer sur **"➕ Nouvel événement"**
3. Remplir : titre, date, description, date limite
4. Cliquer sur **"Éditer"** pour ajouter stands et créneaux

### Dupliquer un événement
- Bouton **"📋 Dupliquer"** sur chaque événement (futur ou passé)
- Copie : titre, description, stands, créneaux, groupes d'exclusion
- Date mise à aujourd'hui (à modifier)

### Export CSV
1. Menu **Bénévoles → Exports**
2. Choisir l'événement
3. Télécharger le CSV
4. Format : une ligne par bénévole, colonnes par stand avec horaires

## 📊 Structure de la base de données

### Tables créées
- `wp_fb_inscriptions` : Inscriptions des bénévoles
- `wp_fb_stand` : Stands (CPT WordPress)
- `wp_fb_creneau` : Créneaux horaires (CPT WordPress)
- `wp_fb_evenement` : Événements (CPT WordPress)

### Champs personnalisés
- `_fb_date_limite` : Date limite d'inscription
- `_fb_quota_par_creneau` : Quota de bénévoles par créneau
- `_fb_exclusion_group` : Groupe d'exclusion pour conflits
- `_fb_heure_debut` / `_fb_heure_fin` : Horaires des créneaux

## 🛠️ Développement

### Structure du plugin
```
formulaire-benevoles/
├── admin/                    # Interface d'administration
│   ├── class-fb-event-editor.php
│   ├── css/
│   ├── js/
│   └── partials/
├── includes/                 # Classes principales
│   ├── class-fb-admin.php
│   ├── class-fb-ajax.php
│   ├── class-fb-form-handler.php
│   └── class-fb-post-types.php
├── templates/                # Templates frontend
│   ├── form-benevoles.php
│   └── single-fb_evenement.php
└── formulaire-benevoles.php  # Fichier principal
```

### Hooks WordPress utilisés
- `template_redirect` : Gestion des soumissions de formulaire
- `wp_ajax_*` : Requêtes AJAX admin
- `save_post_fb_evenement` : Sauvegarde des événements
- `admin_menu` : Menus d'administration

## 📝 License

MIT License

## 👨‍💻 Auteur

Développé pour Dépanordi Bordeaux - Gestion des bénévoles pour la Kermesse
