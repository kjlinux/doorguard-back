# DoorGuard - Guide utilisateur

## Sommaire

1. [Connexion](#connexion)
2. [Tableau de bord](#tableau-de-bord)
3. [Gestion des capteurs](#gestion-des-capteurs)
4. [Gestion des portes](#gestion-des-portes)
5. [Gestion des badges](#gestion-des-badges)
6. [Historique des acces](#historique-des-acces)
7. [Cas d'usage courants](#cas-dusage-courants)

---

## Connexion

### Premiere connexion

1. Ouvrez votre navigateur et accedez a l'adresse de l'application (ex: `http://localhost:3000`)
2. Saisissez votre **email** et votre **mot de passe**
3. Cliquez sur **Se connecter**

Une fois connecte, vous arrivez sur le **Tableau de bord**.

---

## Tableau de bord

Le tableau de bord est la page d'accueil de l'application. Il affiche :

### Indicateurs cles (en haut)

| Indicateur | Description |
|------------|-------------|
| **Acces total** | Nombre total de tentatives d'acces (acceptees + refusees + rejetees) |
| **Acces refuses** | Nombre de badges refuses ou rejetes |
| **Portes actives** | Nombre de portes configurees dans le systeme |
| **Capteurs en ligne** | Nombre de capteurs ESP32 actuellement connectes |

### Graphiques

- **Activite horaire** : Nombre d'evenements par heure (derniere 24h)
- **Activite par porte** : Nombre d'evenements par porte

### Derniers evenements

Tableau en temps reel affichant les 10 derniers acces :
- Nom du porteur du badge (ou "Inconnu")
- UID du badge
- Porte concernee
- Statut (Accepte, Refuse, Rejete, Force)
- Date et heure

💡 **Les donnees se mettent a jour automatiquement en temps reel** grace au WebSocket.

---

## Gestion des capteurs

**Menu** : Cliquez sur **Capteurs** dans la barre de navigation.

### Voir la liste des capteurs

La liste affiche pour chaque capteur :
- **Nom** du capteur (ex: "Capteur entree principale")
- **Emplacement** (ex: "Batiment A - RDC")
- **Statut** : 🟢 En ligne / 🔴 Hors ligne
- **Derniere activite** : Date et heure du dernier message recu
- **Topic MQTT** : Topic utilise pour communiquer avec ce capteur

### Ajouter un nouveau capteur

1. Remplissez le formulaire dans le panneau de gauche :
   - **Nom du capteur** : Nom descriptif (ex: "Capteur bureau direction")
   - **Emplacement** : Ou se trouve le capteur (ex: "Etage 2 - Bureau 205")
   - **Topic MQTT** : Format `doorguard/sensor/{unique_id}/event`
     - `{unique_id}` doit etre **unique** et correspondre a l'ID programme dans l'ESP32
     - Exemple : `doorguard/sensor/ESP32_001/event`

2. Cliquez sur **Ajouter le capteur**

3. Le capteur apparait dans la liste avec le statut **Hors ligne**

4. Des que l'ESP32 publie un message, le statut passe a **En ligne** 🟢

### Modifier un capteur

1. Cliquez sur l'icone **crayon** ✏️ a droite du capteur
2. Modifiez le **nom** ou l'**emplacement**
3. Cliquez sur **Enregistrer**

⚠️ Le **topic MQTT** est en lecture seule et ne peut pas etre modifie (il est lie a la configuration de l'ESP32).

### Supprimer un capteur

1. Cliquez sur l'icone **poubelle** 🗑️ a droite du capteur
2. Confirmez la suppression dans la boite de dialogue

⚠️ **Attention** : Si le capteur est rattache a une porte, il sera automatiquement detache. Les logs d'acces historiques seront conserves.

---

## Gestion des portes

**Menu** : Cliquez sur **Portes** dans la barre de navigation.

### Ajouter une nouvelle porte

1. Dans le panneau de gauche, **selectionnez un capteur** dans la liste deroulante
   - Seuls les capteurs **non rattaches** a une porte sont proposes
   - Le nom et l'emplacement de la porte sont **automatiquement remplis** depuis le capteur
   - Ces champs sont **grises** car ils proviennent du capteur

2. Cliquez sur **Ajouter la porte**

3. La porte apparait dans la liste avec :
   - Le nom et l'emplacement
   - Le capteur associe avec son statut (🟢 En ligne / 🔴 Hors ligne)
   - Le nombre de badges autorises

### Detacher un capteur d'une porte

1. Cliquez sur l'icone **lien brise** 🔗❌ a cote du nom du capteur
2. Le capteur est detache et la porte affiche **"Aucun capteur"** ⚠️

### Assigner un capteur a une porte sans capteur

1. Si une porte affiche **"Aucun capteur"**, cliquez sur l'icone **lien** 🔗 a cote
2. Selectionnez un capteur disponible dans la liste
3. Cliquez sur **Assigner**

### Assigner des badges a une porte

1. Dans la section **Badges assignes** en bas de chaque carte de porte, cliquez sur **+ Assigner**
2. Cochez les badges que vous souhaitez autoriser pour cette porte
   - Seuls les badges **actifs** sont proposes
3. Cliquez sur **Assigner X badge(s)**

Les badges autorises apparaissent sous forme de pastilles bleues.

### Retirer un badge d'une porte

1. Cliquez sur le **X** a cote du nom du badge dans la liste
2. Le badge est immediatement retire (il ne pourra plus ouvrir cette porte)

### Envoyer des commandes a distance

Chaque porte avec un capteur associe propose 3 boutons de commande :

| Bouton | Commande | Description |
|--------|----------|-------------|
| 🔓 | **Ouverture forcee** | Ouvre la porte immediatement sans badge |
| 🔄 | **Reboot** | Redemarre l'ESP32 |
| ⚡ | **Status** | Demande l'etat actuel du capteur |

Cliquez sur le bouton pour envoyer la commande. Un message de confirmation apparait en haut a droite.

### Supprimer une porte

1. Cliquez sur l'icone **poubelle** 🗑️ en haut a droite de la carte de porte
2. Confirmez la suppression

⚠️ La suppression d'une porte detache automatiquement le capteur et supprime les permissions des badges. Les logs d'acces historiques sont conserves.

---

## Gestion des badges

**Menu** : Cliquez sur **Badges** dans la barre de navigation.

### Voir la liste des badges

La liste affiche pour chaque badge :
- **Nom du porteur** (ex: "Jean Dupont")
- **UID du badge** : Code unique du badge RFID (ex: "A1B2C3D4")
- **Statut** : 🟢 Actif / 🔴 Inactif (interrupteur)
- **Nombre de portes** autorisees

### Ajouter un nouveau badge

1. Remplissez le formulaire dans le panneau de gauche :
   - **UID du badge** : Code unique du badge RFID
     - Format hexadecimal (ex: `A1B2C3D4`, `1234ABCD`)
     - Ce code est lu par le lecteur RFID de l'ESP32
   - **Nom du porteur** : Nom de la personne (ex: "Marie Martin")

2. Cliquez sur **Ajouter le badge**

💡 **Comment obtenir l'UID d'un badge ?**
- Utilisez un lecteur RFID pour scanner le badge
- Ou consultez les logs d'acces : un badge inconnu apparaitra avec son UID et le statut "Rejete"

### Activer / Desactiver un badge

Cliquez sur l'**interrupteur** 🔘 a droite du badge :
- **Vert (ON)** : Badge actif, peut ouvrir les portes autorisees
- **Gris (OFF)** : Badge desactive, sera refuse meme s'il a les permissions

💡 **Cas d'usage** : Desactiver temporairement un badge sans supprimer ses permissions (ex: employe en conge, badge perdu).

### Modifier un badge

1. Cliquez sur l'icone **crayon** ✏️ a droite du badge
2. Modifiez le **nom du porteur** ou l'**UID**
3. Cliquez sur **Enregistrer**

### Supprimer un badge

1. Cliquez sur l'icone **poubelle** 🗑️ a droite du badge
2. Confirmez la suppression

⚠️ La suppression retire automatiquement le badge de toutes les portes. Les logs d'acces historiques sont conserves.

---

## Historique des acces

**Menu** : Cliquez sur **Historique** dans la barre de navigation.

### Consulter les logs

Le tableau affiche tous les evenements d'acces avec :

| Colonne | Description |
|---------|-------------|
| **Utilisateur** | Nom du porteur du badge (ou "Inconnu" si badge non enregistre) |
| **Badge UID** | UID du badge scanne (ou "⚡ Commande distante" pour une ouverture forcee) |
| **Porte** | Nom et emplacement de la porte |
| **Statut** | Resultat de la tentative d'acces (voir ci-dessous) |
| **Date & Heure** | Moment de l'evenement |

### Statuts possibles

| Statut | Couleur | Signification |
|--------|---------|---------------|
| **Accepte** | 🟢 Vert | Badge autorise, porte ouverte |
| **Refuse** | 🔴 Rouge | Badge connu mais pas autorise sur cette porte, ou badge desactive |
| **Rejete** | 🔴 Rouge | Badge inconnu (pas enregistre dans le systeme) |
| **Force** | 🟠 Orange | Ouverture forcee depuis l'interface (commande admin) |

### Filtrer les logs

En haut a droite, selectionnez un filtre dans le menu deroulant :
- **Tous** : Tous les evenements
- **Acceptes** : Seulement les acces autorises
- **Refuses** : Seulement les acces refuses
- **Rejetes** : Seulement les badges inconnus
- **Forces** : Seulement les ouvertures forcees

### Temps reel

Les logs se mettent a jour **automatiquement en temps reel** :
- Un badge "🟢 En direct" apparait en haut a droite quand la connexion WebSocket est active
- Les nouveaux evenements apparaissent instantanement en haut de la liste
- Une notification apparait en haut a droite pour chaque nouvel evenement

---

## Cas d'usage courants

### 1. Premier demarrage du systeme

**Objectif** : Configurer le systeme de zero.

1. **Ajouter un capteur**
   - Allez dans **Capteurs**
   - Renseignez le nom, l'emplacement et le topic MQTT
   - Exemple : `doorguard/sensor/ESP32_001/event`

2. **Creer une porte**
   - Allez dans **Portes**
   - Selectionnez le capteur que vous venez d'ajouter
   - Cliquez sur **Ajouter la porte**

3. **Enregistrer un badge**
   - Allez dans **Badges**
   - Renseignez l'UID du badge et le nom du porteur
   - Cliquez sur **Ajouter le badge**

4. **Autoriser le badge sur la porte**
   - Retournez dans **Portes**
   - Cliquez sur **+ Assigner** sous la porte
   - Cochez le badge
   - Cliquez sur **Assigner**

5. **Tester**
   - Scannez le badge avec le lecteur RFID de l'ESP32
   - Consultez l'**Historique** : vous devriez voir un evenement "Accepte" 🟢
   - La porte devrait s'ouvrir

---

### 2. Ajouter un nouvel employe

**Objectif** : Donner acces a une ou plusieurs portes a un nouvel employe.

1. **Enregistrer le badge**
   - Allez dans **Badges**
   - Saisissez l'UID du badge et le nom de l'employe
   - Cliquez sur **Ajouter le badge**

2. **Autoriser sur les portes**
   - Allez dans **Portes**
   - Pour chaque porte ou l'employe doit avoir acces :
     - Cliquez sur **+ Assigner**
     - Cochez le badge de l'employe
     - Cliquez sur **Assigner**

3. **Verifier**
   - Consultez la fiche du badge : le compteur "X portes" doit correspondre
   - Testez le badge sur une porte autorisee

---

### 3. Suspendre temporairement un badge

**Objectif** : Empecher un badge d'ouvrir les portes sans supprimer ses permissions (conge, badge perdu, enquete...).

1. Allez dans **Badges**
2. Trouvez le badge concerne
3. Cliquez sur l'**interrupteur** pour le **desactiver** (gris 🔴)
4. Le badge sera refuse sur toutes les portes

Pour le reactiver :
- Cliquez a nouveau sur l'interrupteur pour le **reactiver** (vert 🟢)

---

### 4. Retirer un acces a une porte specifique

**Objectif** : Un employe ne doit plus acceder a une salle specifique.

1. Allez dans **Portes**
2. Trouvez la porte concernee
3. Dans la section **Badges assignes**, cliquez sur le **X** a cote du badge
4. Le badge ne pourra plus ouvrir cette porte (mais garde ses acces aux autres portes)

---

### 5. Ouvrir une porte a distance

**Objectif** : Debloquer une porte depuis l'interface sans badge (visiteur, urgence...).

1. Allez dans **Portes**
2. Trouvez la porte a ouvrir
3. Cliquez sur le bouton **🔓 Ouverture forcee**
4. La porte s'ouvre immediatement
5. Un log "Force" apparait dans l'historique avec le badge UID "⚡ Commande distante"

---

### 6. Diagnostiquer un probleme d'acces

**Scenario** : Un employe se plaint que son badge ne fonctionne pas.

#### Etape 1 : Verifier dans l'historique

1. Allez dans **Historique**
2. Cherchez les evenements recents avec le nom de l'employe
3. Regardez le statut :

   - **"Rejete" 🔴** → Le badge n'est pas enregistre
     - Solution : Allez dans **Badges** et ajoutez le badge avec l'UID affiche dans l'historique

   - **"Refuse" 🔴** → Le badge est enregistre mais :
     - Soit il est **desactive** → Allez dans **Badges** et activez-le
     - Soit il n'a **pas la permission** sur cette porte → Allez dans **Portes** et assignez le badge

   - **Aucun evenement** → Le capteur ne recoit rien
     - Verifiez que le capteur est **En ligne** 🟢 dans **Capteurs**
     - Verifiez la connexion MQTT de l'ESP32

#### Etape 2 : Verifier le badge

1. Allez dans **Badges**
2. Trouvez le badge de l'employe
3. Verifiez :
   - ✅ Le badge est **actif** (interrupteur vert)
   - ✅ Le compteur affiche au moins **1 porte**

#### Etape 3 : Verifier la porte

1. Allez dans **Portes**
2. Trouvez la porte concernee
3. Verifiez :
   - ✅ Un **capteur est associe** (pas de message "Aucun capteur")
   - ✅ Le capteur est **En ligne** 🟢
   - ✅ Le badge de l'employe apparait dans la liste des **badges assignes**

---

### 7. Gerer un badge perdu

**Objectif** : Securiser le systeme apres la perte d'un badge.

**Option 1 : Desactivation temporaire** (si le badge peut etre retrouve)

1. Allez dans **Badges**
2. Trouvez le badge perdu
3. **Desactivez-le** avec l'interrupteur
4. Le badge ne peut plus ouvrir aucune porte
5. Si le badge est retrouve, reactivez-le

**Option 2 : Suppression definitive** (si le badge ne sera pas retrouve)

1. Allez dans **Badges**
2. Trouvez le badge perdu
3. Cliquez sur l'icone **poubelle** 🗑️
4. Confirmez la suppression
5. Enregistrez un nouveau badge pour l'employe

---

### 8. Voir l'activite d'une porte specifique

**Objectif** : Consulter l'historique d'acces d'une porte (audit, statistiques...).

1. Allez dans **Historique**
2. Reparez la colonne **Porte**
3. Trouvez visuellement les evenements de la porte concernee

💡 Les evenements sont tries du plus recent au plus ancien.

---

### 9. Remplacer un capteur defectueux

**Objectif** : Changer l'ESP32 d'une porte sans perdre la configuration.

#### Si le nouveau capteur a le MEME unique_id

1. Flashez le nouveau ESP32 avec le **meme unique_id** que l'ancien
2. Connectez-le au broker MQTT
3. Le systeme detecte automatiquement le nouveau capteur
4. Aucune modification n'est necessaire dans l'interface

#### Si le nouveau capteur a un unique_id DIFFERENT

1. **Ajoutez le nouveau capteur**
   - Allez dans **Capteurs**
   - Ajoutez le nouveau capteur avec son nouveau topic

2. **Reassignez le capteur a la porte**
   - Allez dans **Portes**
   - Sur la porte concernee, cliquez sur l'icone **lien brise** 🔗❌ pour detacher l'ancien capteur
   - Cliquez sur l'icone **lien** 🔗 a cote de "Aucun capteur"
   - Selectionnez le nouveau capteur
   - Cliquez sur **Assigner**

3. **Supprimez l'ancien capteur** (optionnel)
   - Allez dans **Capteurs**
   - Supprimez l'ancien capteur

---

### 10. Surveiller le systeme en temps reel

**Objectif** : Suivre l'activite du systeme en direct.

1. Ouvrez le **Tableau de bord** (page d'accueil)
2. Le tableau **Derniers evenements** se met a jour automatiquement
3. Une notification apparait en haut a droite pour chaque nouvel acces

**Ou**

1. Ouvrez l'**Historique**
2. Verifiez que le badge "🟢 En direct" est affiche en haut a droite
3. Les nouveaux evenements apparaissent en haut de la liste en temps reel

---

## Conseils et bonnes pratiques

### Nommage

- **Capteurs** : Utilisez des noms descriptifs incluant l'emplacement
  - ✅ "Capteur entree principale - Batiment A"
  - ❌ "Capteur 1"

- **Badges** : Utilisez le nom complet de l'employe
  - ✅ "Marie Martin"
  - ❌ "MM"

### Securite

- **Desactivez** les badges au lieu de les supprimer si vous pensez les reactiver plus tard
- **Consultez regulierement** l'historique pour detecter des tentatives d'acces suspectes (badges rejetes repetes)
- **Verifiez** que les capteurs sont en ligne regulierement

### Maintenance

- **Surveillez** le statut des capteurs dans la page **Capteurs**
- Si un capteur est **Hors ligne** 🔴 pendant longtemps :
  - Verifiez la connexion reseau de l'ESP32
  - Verifiez la configuration MQTT
  - Rebootez l'ESP32 avec le bouton **🔄 Reboot** depuis la page **Portes**

---

## Questions frequentes (FAQ)

### Un badge refuse alors qu'il devrait etre autorise

1. Verifiez que le badge est **actif** (interrupteur vert) dans **Badges**
2. Verifiez que le badge est **assigne a la porte** dans **Portes**
3. Consultez l'**Historique** pour voir le statut exact :
   - "Refuse" = badge enregistre mais pas autorise ou desactive
   - "Rejete" = badge pas enregistre (mauvais UID ?)

### Un capteur reste "Hors ligne"

1. Verifiez que l'ESP32 est allume et connecte au WiFi
2. Verifiez la configuration MQTT dans le firmware ESP32 (host, port, username, password)
3. Verifiez que le **topic MQTT** dans l'interface correspond au topic du firmware
4. Testez la connexion MQTT avec un outil externe (MQTTX, mosquitto_pub...)

### Je ne vois pas les evenements en temps reel

1. Verifiez que le badge "🟢 En direct" apparait en haut a droite de l'**Historique**
2. Si le badge affiche "Connexion...", rafraichissez la page
3. Verifiez que le serveur WebSocket (Reverb) est lance : `php artisan reverb:start`

### Comment obtenir l'UID d'un badge ?

**Methode 1** : Scanner avec un lecteur RFID

**Methode 2** : Utiliser le systeme
1. Scannez le badge inconnu sur un capteur
2. Allez dans l'**Historique**
3. Cherchez un evenement avec le statut "Rejete" 🔴
4. L'UID du badge apparait dans la colonne **Badge UID**
5. Copiez cet UID et enregistrez le badge

### Puis-je supprimer un capteur rattache a une porte ?

Oui. Le capteur sera automatiquement detache de la porte. La porte affichera "Aucun capteur" ⚠️ et ne pourra plus ouvrir tant qu'un nouveau capteur n'est pas assigne.

### Les logs d'acces sont-ils supprimes quand je supprime un badge ou une porte ?

Non. Les logs d'acces sont **historiques** et sont conserves indefiniment. Seules les nouvelles tentatives d'acces seront bloquees.

---

## Support

En cas de probleme technique, consultez :
- Le **Guide MQTT pour l'ingenieur IoT** (`GUIDE_MQTT_IOT.md`) pour les details techniques
- Les logs du serveur Laravel : `storage/logs/laravel.log`
- Les logs du listener MQTT : console ou `php artisan mqtt:listen`
