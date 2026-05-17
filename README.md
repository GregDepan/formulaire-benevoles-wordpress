# Formulaire Bénévoles

**Version:** 1.0.0  
**WordPress:** 6.0+  
**PHP:** 8.0+

Système complet de gestion d'inscriptions bénévoles pour événements (kermesses, lotos, vide-greniers, etc.).

---

## 🚀 Fonctionnalités

### Pour les organisateurs (admin WordPress)
- ✅ Création d'événements avec dates, lieu, description
- ✅ Gestion dynamique des stands (nom, quota, couleur, description)
- ✅ Gestion des créneaux horaires par stand (30min par défaut)
- ✅ Quotas variables par stand et par créneau
- ✅ Détection automatique des conflits d'horaires
- ✅ Liste d'attente automatique quand un créneau est complet
- ✅ Clonage d'événements (copie stands + créneaux + quotas)
- ✅ Export CSV des inscriptions (global, par stand, par créneau)
- ✅ Statistiques : taux de remplissage, stands populaires, pics d'affluence
- ✅ Génération de badges nominatifs (format A4 imprimable)
- ✅ Emails de confirmation automatiques

### Pour les bénévoles (frontend)
- ✅ Formulaire d'inscription responsive mobile
- ✅ Sélection multiple de créneaux (avec validation anti-conflit)
- ✅ Créneaux complets masqués automatiquement
- ✅ Option de création de compte WordPress
- ✅ Espace "Mon profil" pour gérer ses réservations
- ✅ Modification/annulation possible (selon délai configuré)
- ✅ Emails de confirmation et de promotion liste d'attente

---

## 📦 Installation

### 1. Upload du plugin

```bash
# Compress the plugin directory
cd /root
zip -r formulaire-benevoles.zip formulaire-benevoles/

# Upload via WordPress admin or FTP
# WordPress Admin → Extensions → Ajouter → Téléverser une extension
```

### 2. Activation

1. Allez dans **Extensions** dans WordPress admin
2. Activez **Formulaire Bénévoles**
3. Un nouveau menu **Bénévoles** apparaît dans la sidebar

### 3. Configuration initiale

Allez dans **Bénévoles → Réglages** pour configurer :
- Durée par défaut des créneaux
- Délai de modification des réservations
- Expéditeur des emails
- Format de badges

---

## 🎯 Utilisation

### Créer un premier événement

1. **Bénévoles → Événements → Ajouter**
2. Remplissez :
   - Titre (ex: "Kermesse Juin 2026")
   - Date de début/fin
   - Délai d'inscription (date limite)
   - Lieu
   - Description
3. Publiez l'événement

### Ajouter des stands

1. Dans la page d'édition de l'événement, section **Stands de cet événement**
2. Cliquez sur **Ajouter un stand**
3. Remplissez :
   - Nom (ex: "Buvette", "Pêche à la ligne")
   - Quota par créneau (ex: 5 bénévoles)
   - Couleur (pour identification visuelle)
   - Description (optionnel)

### Ajouter des créneaux

1. Cliquez sur un stand pour l'éditer
2. Section **Créneaux horaires** → **Ajouter un créneau**
3. Définissez :
   - Heure de début (ex: 18:15)
   - Heure de fin (ex: 18:45)
   - Quota spécifique (optionnel, sinon hérite du stand)

### Cloner un événement

Pour réutiliser la même structure d'année en année :

1. Ouvrez un événement existant
2. Dans la sidebar **Actions**, cliquez sur **Cloner l'événement**
3. Donnez un nom au nouvel événement
4. Tous les stands et créneaux sont copiés (pas les inscriptions)

---

## 🔌 Shortcodes

### Formulaire d'inscription sur une page dédiée

Le formulaire s'affiche automatiquement sur la page de l'événement :
```
/evenements/kermesse-juin-2026/
```

### Liste des événements
```
[fb_liste_evenements]
```

### Profil bénévole
```
[fb_profil]
```

---

## 📧 Emails

Les emails suivants sont envoyés automatiquement :

| Type | Déclencheur | Destinataire |
|------|-------------|--------------|
| Confirmation | Nouvelle inscription | Bénévole |
| Promotion liste d'attente | Place libérée | Bénévole en attente |
| Modification | Changement de créneau | Bénévole |
| Annulation | Annulation réservation | Bénévole |

---

## 🗄️ Base de données

Le plugin crée 3 tables personnalisées :

```sql
wp_fb_inscriptions    -- Inscriptions confirmées
wp_fb_waitlist        -- Liste d'attente
wp_fb_stats_logs      -- Logs pour statistiques
```

Et 3 Custom Post Types :

```
fb_evenement  -- Événements (public)
fb_stand      -- Stands (admin only)
fb_creneau    -- Créneaux horaires (admin only)
```

---

## 🔧 Développement

### Structure du plugin

```
formulaire-benevoles/
├── formulaire-benevoles.php    # Main file
├── uninstall.php               # Nettoyage
├── includes/
│   ├── class-fb-loader.php
│   ├── class-fb-activator.php
│   ├── class-fb-deactivator.php
│   ├── class-fb-post-types.php
│   ├── class-fb-admin.php
│   ├── class-fb-public.php
│   ├── class-fb-form-handler.php
│   ├── class-fb-emails.php
│   └── class-fb-ajax.php
├── admin/
│   ├── css/fb-admin.css
│   ├── js/fb-admin.js
│   └── partials/
├── public/
│   ├── css/fb-public.css
│   ├── js/fb-public.js
│   └── partials/
└── templates/
    ├── single-fb_evenement.php
    └── emails/
```

### Hooks disponibles

```php
// Actions
do_action('fb_inscription_created', $inscription_id, $data);
do_action('fb_inscription_cancelled', $inscription_id);
do_action('fb_slot_full', $creneau_id, $stand_id);

// Filters
apply_filters('fb_quota_default', 5, $stand_id);
apply_filters('fb_email_confirmation_subject', $subject, $data);
apply_filters('fb_allow_modification', $allowed, $event_id);
```

---

## 🐛 Dépannage

### Le formulaire ne s'affiche pas
- Vérifiez que l'événement est **publié** (pas brouillon)
- Vérifiez que l'événement n'est pas **archivé**
- Vérifiez que le délai d'inscription n'est pas dépassé

### Les emails ne partent pas
- Vérifiez la configuration SMTP de WordPress
- Testez avec un plugin comme WP Mail Logging

### Erreur "Table n'existe pas"
- Désactivez et réactivez le plugin pour recréer les tables

---

## 📝 Changelog

### 1.0.0 (2026-05-17)
- Version initiale
- Création événements, stands, créneaux
- Formulaire frontend avec validation
- Liste d'attente automatique
- Export CSV
- Emails de confirmation
- Clonage d'événements
- Statistiques de base

---

## 📄 Licence

GPL v2 or later

---

## 👨‍💻 Support

Pour toute question ou bug report, contactez l'administrateur du site.
