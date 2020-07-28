# Broadlink

Plugin permettant de s’interfacer avec des équipements broadlink

# Configuration du plugin

Après avoir installé le plugin, il faut l’activer et s’assurer de la bonne installation des dépendances.

> **Important**
>
> Il est déconseillé de changer le port

# Inclusion des équipements

Aller dans le menu Plugins => Protocole Domotique => Broadlink. Une fois sur la page cliquez sur MODE INCLUSION. En toute logique l’ensemble des produits Broadlink de votre réseau sera reconnu.

# Configuration des équipements

Sélectionnez un de vos équipements. Si ce sont des produits de type Infra rouge, un bouton apprendre une commande apparait en haut. Il vous suffit de cliquer dessus, vous avez ensuite 5 secondes pour apprendre une commande infrarouge ou 433 a votre broadlink. Suite à cela le plugin vous confirmera le succès de l’opération et créera la commande associée qu’il vous suffira simplement de nommer.

> **Tip**
>
> Pour éviter d’apprendre ce qui pourrait venir de l’environnement (télécommande d’un voisin ou autre), le broadlink en mode apprentissage se met VOLONTAIREMENT en niveau deéception très faible, il faut donc être TRES proche du broadlink pour apprendre une commande surtout si c’est une commande 433Mhz.

# Synchroniser

Sur l’onglet Commandes d’un équipement vous trouvez le bouton Synchroniser. Si vous possédez plusieurs broadlink vous pouvez transférer les commandes apprises d’un broadlink à un autre.

> **Tip**
>
> Cela marche d’un rm-pro vers un mini ou vice versa.

Vous pouvez choisir les commandes à transférer ainsi que les broadlink vers lesquels vous voulez les transférer. (Le nom de la commande sera bien évidemment convervé (ON télévision, ventilateur off, etc.)

> **Tip**
>
> Si vous ajouteé manuellement votre device car pas autodétecté. La MAC a renseigner est l'adresse MAC inversé par paquet de deux en minuscules sans les : exemple AA:BB:CC:DD:EE devient eeddccbbaa
