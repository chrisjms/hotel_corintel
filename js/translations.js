/**
 * Hotel - Translations
 * Supported languages: French (fr), English (en), Spanish (es), Italian (it)
 * To add a new language: add a new key to the translations object with all required keys
 */

const translations = {
  // Available languages configuration
  languages: {
    fr: { name: 'Français', flag: '🇫🇷' },
    en: { name: 'English', flag: '🇬🇧' },
    es: { name: 'Español', flag: '🇪🇸' },
    it: { name: 'Italiano', flag: '🇮🇹' }
  },

  // French (default)
  fr: {
    // Navigation
    nav: {
      home: 'Accueil',
      services: 'Services',
      roomService: 'Room Service',
      activities: 'À découvrir',
      discover: 'À découvrir',
      contact: 'Contact'
    },

    // Header
    header: {
      logoSubtitle: 'Bordeaux Est',
      contactReception: 'Contacter la réception'
    },

    // Contact Reception Modal
    modal: {
      contactReceptionTitle: 'Contacter la réception',
      roomNumber: 'Numéro de chambre *',
      roomNumberPlaceholder: 'Ex: 101',
      guestName: 'Votre nom',
      guestNamePlaceholder: 'Optionnel',
      category: 'Catégorie',
      subject: 'Objet',
      subjectPlaceholder: 'Résumé du problème',
      message: 'Votre message *',
      messagePlaceholder: 'Décrivez votre demande ou problème...',
      sendMessage: 'Envoyer le message',
      successTitle: 'Message envoyé',
      successMessage: 'Votre message a bien été transmis à la réception. Nous vous répondrons dans les meilleurs délais.',
      newMessage: 'Envoyer un autre message',
      errorGeneric: 'Une erreur est survenue. Veuillez réessayer.'
    },

    // Room Service page
    roomService: {
      // Hero
      heroSubtitle: '{hotelName}',
      heroTitle: 'Room Service',
      heroDescription: 'Commandez depuis votre chambre',

      // Order success
      orderConfirmed: 'Commande confirmée',
      orderSuccessMessage: 'Votre commande a été enregistrée avec succès. Notre équipe va la préparer et vous la livrer dans les meilleurs délais.',
      orderNumber: 'Commande #',
      newOrder: 'Passer une nouvelle commande',

      // No items
      serviceUnavailable: 'Service actuellement indisponible',
      serviceUnavailableMessage: 'Le room service n\'est pas disponible pour le moment. Veuillez réessayer ultérieurement ou appeler la réception au +33 5 57 34 13 95.',

      // Cart
      yourOrder: 'Votre commande',
      cartEmpty: 'Sélectionnez des articles pour commencer',
      total: 'Total',

      // Form
      roomNumber: 'Numéro de chambre *',
      roomNumberPlaceholder: 'Ex: 101',
      yourName: 'Votre nom',
      optionalPlaceholder: 'Optionnel',
      phone: 'Téléphone',
      phonePlaceholder: 'Pour vous joindre si nécessaire',
      deliveryDateTime: 'Date et heure de livraison *',
      deliveryMinTime: 'Minimum 30 minutes à l\'avance',
      paymentMethod: 'Mode de paiement',
      notes: 'Remarques',
      notesPlaceholder: 'Allergies, préférences...',
      orderButton: 'Commander',

      // Availability
      available24h: '24h/24',

      // Validation errors
      errorSelectItem: 'Veuillez sélectionner au moins un article.',
      errorRoomNumber: 'Veuillez indiquer votre numéro de chambre.',
      errorDeliveryTime: 'Veuillez indiquer la date et heure de livraison.',
      errorMinDeliveryTime: 'La livraison doit être prévue au moins 30 minutes à l\'avance.',

      // Categories
      categories: {
        breakfast: 'Petit-déjeuner',
        lunch: 'Déjeuner',
        dinner: 'Dîner',
        snacks: 'Snacks',
        drinks: 'Boissons',
        desserts: 'Desserts',
        general: 'Général'
      }
    },

    // Home page
    home: {
      heroSubtitle: 'Bienvenue à {hotelName}',
      heroTitle: 'Un havre de paix<br>aux portes de Bordeaux',
      heroDescription: 'Découvrez notre hôtel de charme 3 étoiles, niché dans la campagne bordelaise, à quelques minutes de Bordeaux et Saint-Émilion.',

      // Introduction section
      introSubtitle: 'Notre philosophie',
      introTitle: 'Une atmosphère chaleureuse et conviviale',
      introText1: '{hotelName} vous accueille dans un cadre paisible et verdoyant, où se mêlent le charme de la campagne bordelaise et le confort d\'un établissement 3 étoiles.',
      introText2: 'Entouré de nature, notre hôtel offre une expérience de détente authentique. Profitez de notre jardin, de notre terrasse ombragée et de notre salon commun pour des moments de quiétude loin du tumulte de la ville.',
      featureGarden: 'Jardin paisible',
      featureTerrace: 'Terrasse ombragée',
      featureLounge: 'Salon commun',
      featureParking: 'Parking gratuit',

      // Services preview
      servicesSubtitle: 'Nos services',
      servicesTitle: 'Tout pour votre confort',
      servicesDescription: 'De la table d\'hôtes au boulodrome, découvrez tous les services qui rendront votre séjour inoubliable.',
      serviceRestaurant: 'Table d\'hôtes',
      serviceRestaurantDesc: 'Savourez une cuisine régionale authentique pour le petit-déjeuner et le dîner, préparée avec des produits locaux.',
      serviceBar: 'Bar',
      serviceBarDesc: 'Détendez-vous dans notre bar chaleureux et dégustez une sélection de vins de Bordeaux et de cocktails.',
      serviceBoulodrome: 'Boulodrome',
      serviceBoulodromeDesc: 'Profitez de notre terrain de pétanque pour des moments conviviaux entre amis ou en famille.',
      serviceParkingTitle: 'Parking gratuit',
      serviceParkingDesc: 'Stationnement privé et sécurisé offert à tous nos clients, pour un séjour en toute tranquillité.',
      discoverServices: 'Découvrir tous nos services',

      // CTA
      ctaTitle: 'Découvrez notre hôtel',
      ctaText: 'Offrez-vous un séjour ressourçant au cœur de la campagne bordelaise'
    },

    // Services page
    services: {
      heroSubtitle: '{hotelName}',
      heroTitle: 'Nos Services',
      heroDescription: 'Tout pour un séjour inoubliable',

      introSubtitle: 'À votre service',
      introTitle: 'Une expérience complète',
      introDescription: '{hotelName} met à votre disposition une gamme de services pensés pour votre confort et votre détente. Découvrez tout ce qui rendra votre séjour mémorable.',

      // Restaurant
      restaurantSubtitle: 'Restauration',
      restaurantTitle: 'Table d\'hôtes',
      restaurantText1: 'Notre restaurant vous invite à découvrir une cuisine régionale authentique, préparée avec passion à partir de produits locaux soigneusement sélectionnés. Dans une ambiance conviviale de table d\'hôtes, partagez des repas savoureux qui célèbrent les saveurs du terroir bordelais.',
      restaurantText2: 'Le petit-déjeuner et le dîner vous sont proposés dans notre salle chaleureuse ou en terrasse aux beaux jours, avec vue sur le jardin.',
      tagLocalProducts: 'Produits locaux',
      tagRegionalCuisine: 'Cuisine régionale',
      tagBreakfast: 'Petit-déjeuner',
      tagDinner: 'Dîner',

      galleryRoom: 'Salle du restaurant',
      galleryRoomDesc: 'Ambiance chaleureuse',
      galleryDecor: 'Décoration soignée',
      galleryDecorDesc: 'Charme authentique',
      galleryService: 'Service attentionné',
      galleryServiceDesc: 'À votre écoute',

      // Bar
      barSubtitle: 'Détente',
      barTitle: 'Le Bar',
      barText1: 'Prolongez vos soirées dans notre bar chaleureux, véritable lieu de convivialité où se croisent les voyageurs du monde entier. Installez-vous confortablement et savourez un moment de détente.',
      barText2: 'Notre carte met à l\'honneur les vins de Bordeaux et de Saint-Émilion, accompagnés d\'une sélection de spiritueux et de cocktails préparés avec soin par notre équipe.',
      tagBordeauxWines: 'Vins de Bordeaux',
      tagCocktails: 'Cocktails',
      tagConvivial: 'Ambiance conviviale',

      // Boulodrome
      boulodromeSubtitle: 'Loisirs',
      boulodromeTitle: 'Boulodrome',
      boulodromeText1: 'À {hotelName}, nous cultivons l\'art de vivre à la française. Notre terrain de pétanque vous attend pour des parties mémorables, que vous soyez joueur aguerri ou simple amateur de moments conviviaux.',
      boulodromeText2: 'Sous le soleil de Gironde, lancez vos boules et profitez de l\'esprit détendu de la campagne bordelaise. Un apéritif à la main, en famille ou entre amis, c\'est le bonheur simple des vacances.',
      tagPetanque: 'Terrain de pétanque',
      tagBowlsAvailable: 'Boules disponibles',
      tagFreeAccess: 'Accès libre',

      // Parking
      parkingSubtitle: 'Pratique',
      parkingTitle: 'Parking privé gratuit',
      parkingText1: 'Votre tranquillité commence dès votre arrivée. {hotelName} dispose d\'un parking privé et sécurisé, entièrement gratuit pour tous nos clients.',
      parkingText2: 'Idéalement situé à l\'est de Bordeaux, notre établissement vous permet de rayonner facilement vers les vignobles, Bordeaux ou Saint-Émilion, tout en profitant du calme de la campagne pour votre repos.',
      tagFree: 'Gratuit',
      tagSecure: 'Privé et sécurisé',
      tag24h: 'Accès 24h/24',

      // Additional services
      additionalSubtitle: 'Et aussi',
      additionalTitle: 'Services complémentaires',
      garden: 'Jardin',
      gardenDesc: 'Promenez-vous dans notre jardin verdoyant et profitez du calme de la nature environnante.',
      terrace: 'Terrasse',
      terraceDesc: 'Détendez-vous sur notre terrasse ombragée, idéale pour les petits-déjeuners ensoleillés.',
      lounge: 'Salon commun',
      loungeDesc: 'Espace convivial pour lire, se détendre ou partager un moment avec d\'autres voyageurs.',
      wifi: 'Wi-Fi gratuit',
      wifiDesc: 'Connexion internet haut débit disponible gratuitement dans tout l\'établissement.',

      ctaTitle: 'Prêt à vivre l\'expérience {hotelShortName} ?',
      ctaText: 'Contactez-nous pour plus d\'informations'
    },

    // Activities page
    activities: {
      heroSubtitle: 'Explorez la région',
      heroTitle: 'À Découvrir',
      heroDescription: 'Bordeaux, Saint-Émilion et les vignobles',

      introSubtitle: 'Votre point de départ',
      introTitle: 'Au cœur d\'une région exceptionnelle',
      introDescription: 'Idéalement situé entre Bordeaux et Saint-Émilion, {hotelName} est le point de départ parfait pour explorer les trésors de la Gironde. Vignobles prestigieux, patrimoine historique et douceur de vivre vous attendent.',

      // Bordeaux
      bordeauxSubtitle: 'Patrimoine mondial UNESCO',
      bordeauxTitle: 'Bordeaux',
      bordeauxText1: 'À seulement quelques minutes de l\'hôtel, la ville de Bordeaux vous ouvre ses portes. Classée au patrimoine mondial de l\'UNESCO, elle séduit par son architecture du XVIIIe siècle, ses quais animés et sa vie culturelle bouillonnante.',
      bordeauxText2: 'Flânez sur la place de la Bourse et son miroir d\'eau, explorez le quartier Saint-Pierre, visitez la Cité du Vin ou déambulez dans la rue Sainte-Catherine, plus longue rue commerçante d\'Europe.',
      bordeauxDistance: '~15 min en voiture',
      bordeauxCiteVin: 'Cité du Vin',
      bordeauxPlace: 'Place de la Bourse',

      // Saint-Emilion
      saintEmilionSubtitle: 'Village médiéval',
      saintEmilionTitle: 'Saint-Émilion',
      saintEmilionText1: 'Joyau du patrimoine français, Saint-Émilion est un village médiéval perché au milieu des vignes. Ses ruelles pavées, son église monolithe creusée dans la roche et ses remparts centenaires vous transportent dans un autre temps.',
      saintEmilionText2: 'Au-delà de son charme historique, Saint-Émilion est le berceau de vins parmi les plus réputés au monde. Dégustations dans les châteaux, visites de caves et balades dans les vignobles rythmeront votre découverte.',
      saintEmilionDistance: '~25 min en voiture',
      saintEmilionChurch: 'Église monolithe',
      saintEmilionWines: 'Grands crus classés',

      // Wine tourism
      wineSubtitle: 'Oenotourisme',
      wineTitle: 'La route des vins',
      wineDescription: 'La Gironde compte parmi les plus prestigieuses appellations viticoles du monde. Partez à la découverte des châteaux et de leurs secrets.',

      tastingTitle: 'Dégustations',
      tastingText: 'Les châteaux de la région vous accueillent pour des dégustations de leurs meilleurs crus. Découvrez les secrets de la vinification et repartez avec vos bouteilles préférées.',
      cellarTitle: 'Visites de caves',
      cellarText: 'Pénétrez dans les chais séculaires où vieillissent les grands vins de Bordeaux. Une expérience sensorielle unique entre tradition et savoir-faire.',
      vineyardTitle: 'Balades dans les vignes',
      vineyardText: 'À pied, à vélo ou en voiture, parcourez les routes sinueuses entre les rangs de vigne. Le paysage viticole de la Gironde est inscrit au patrimoine mondial.',
      gastronomyTitle: 'Gastronomie locale',
      gastronomyText: 'Accompagnez vos découvertes viticoles de la riche cuisine du Sud-Ouest : canard, cèpes, huîtres du bassin d\'Arcachon et desserts traditionnels.',

      // Countryside
      countrysideSubtitle: 'Nature & détente',
      countrysideTitle: 'Échappées en campagne',
      countrysideText1: 'Au-delà des vignobles, la campagne girondine offre mille occasions de se ressourcer. Forêts de pins, rivières paisibles et villages de caractère ponctuent un paysage préservé.',
      countrysideText2: 'Partez en randonnée sur les sentiers balisés, louez un vélo pour explorer les petites routes, ou simplement profitez du calme environnant depuis notre jardin.',
      hikingTrails: 'Sentiers de randonnée',
      cyclingPaths: 'Pistes cyclables',
      villages: 'Villages pittoresques',
      markets: 'Marchés locaux',

      // Other attractions
      otherSubtitle: 'Et aussi',
      otherTitle: 'Autres sites à découvrir',
      arcachon: 'Bassin d\'Arcachon',
      arcachonDesc: 'La Dune du Pilat, les villages ostréicoles et les plages océanes à environ 1h de route.',
      medoc: 'Châteaux du Médoc',
      medocDesc: 'Margaux, Pauillac, Saint-Julien : les plus grands noms du vin vous ouvrent leurs portes.',
      libourne: 'Libourne',
      libourneDesc: 'Bastide médiévale au confluent de la Dordogne et de l\'Isle, à proximité immédiate.',
      marketsTitle: 'Marchés locaux',
      marketsDesc: 'Produits du terroir, fromages, charcuteries et spécialités régionales chaque semaine.',

      ctaTitle: 'Prêt pour l\'aventure ?',
      ctaText: 'Contactez-nous pour découvrir la région bordelaise'
    },

    // Contact page
    contact: {
      heroSubtitle: 'Nous contacter',
      heroTitle: 'Contact',
      heroDescription: 'Nous sommes à votre écoute',

      introSubtitle: 'Restons en contact',
      introTitle: 'Comment nous joindre',
      introDescription: 'Une question, une demande de renseignements ou une réservation ? N\'hésitez pas à nous contacter. Notre équipe se fera un plaisir de vous répondre dans les plus brefs délais.',

      // Contact info
      infoTitle: 'Nos coordonnées',
      addressLabel: 'Adresse',
      addressValue: '{hotelName}<br>Tresses, Bordeaux Est<br>33370 Gironde, France',
      phoneLabel: 'Téléphone',
      emailLabel: 'Email',
      receptionLabel: 'Réception',
      receptionValue: 'Ouverte 7j/7<br>7h00 - 22h00',

      findUs: 'Nous trouver',

      // Contact form
      formTitle: 'Envoyez-nous un message',
      firstName: 'Prénom',
      lastName: 'Nom',
      email: 'Email',
      phone: 'Téléphone',
      subject: 'Objet',
      message: 'Message',
      send: 'Envoyer le message',
      formSuccess: 'Merci pour votre message ! Nous vous répondrons dans les plus brefs délais.',

      placeholderFirstName: 'Votre prénom',
      placeholderLastName: 'Votre nom',
      placeholderEmail: 'votre@email.com',
      placeholderPhone: '+33 6 XX XX XX XX',
      placeholderSubject: 'Objet de votre message',
      placeholderMessage: 'Votre message...',

      // Access
      accessSubtitle: 'Comment venir',
      accessTitle: 'Accès à l\'hôtel',
      byCar: 'En voiture',
      byCarDesc: 'Depuis Bordeaux, prenez la rocade direction Libourne/Paris. Sortie Tresses/Artigues. Parking gratuit sur place.',
      byTrain: 'En train',
      byTrainDesc: 'Gare de Bordeaux Saint-Jean à 15 km. Taxis et VTC disponibles. Nous pouvons organiser votre transfert sur demande.',
      byPlane: 'En avion',
      byPlaneDesc: 'Aéroport de Bordeaux-Mérignac à 25 km. Navettes et location de voitures disponibles à l\'aéroport.',
      byBike: 'À vélo',
      byBikeDesc: 'Pistes cyclables depuis Bordeaux. Local vélo sécurisé disponible pour nos clients cyclotouristes.',

      ctaTitle: 'Des questions ?',
      ctaText: 'N\'hésitez pas à nous contacter, notre équipe est à votre écoute',
      callUs: 'Appelez-nous'
    },

    // Footer
    footer: {
      description: 'Un havre de paix aux portes de Bordeaux, où charme et authenticité vous attendent pour un séjour inoubliable.',
      navigation: 'Navigation',
      services: 'Services',
      contactTitle: 'Contact',
      copyright: '© 2024 {hotelName}. Tous droits réservés.',
      // Footer links
      home: 'Accueil',
      discover: 'À découvrir',
      restaurant: 'Restaurant',
      bar: 'Bar',
      roomService: 'Room Service',
      parking: 'Parking'
    },

    // Common
    common: {
      learnMore: 'En savoir plus',
      backToTop: 'Retour en haut'
    }
  },

  // English
  en: {
    nav: {
      home: 'Home',
      services: 'Services',
      roomService: 'Room Service',
      activities: 'Discover',
      discover: 'Discover',
      contact: 'Contact'
    },

    header: {
      logoSubtitle: 'Bordeaux East',
      contactReception: 'Contact reception'
    },

    modal: {
      contactReceptionTitle: 'Contact reception',
      roomNumber: 'Room number *',
      roomNumberPlaceholder: 'e.g.: 101',
      guestName: 'Your name',
      guestNamePlaceholder: 'Optional',
      category: 'Category',
      subject: 'Subject',
      subjectPlaceholder: 'Summary of the issue',
      message: 'Your message *',
      messagePlaceholder: 'Describe your request or issue...',
      sendMessage: 'Send message',
      successTitle: 'Message sent',
      successMessage: 'Your message has been sent to reception. We will respond as soon as possible.',
      newMessage: 'Send another message',
      errorGeneric: 'An error occurred. Please try again.'
    },

    // Room Service page
    roomService: {
      // Hero
      heroSubtitle: '{hotelName}',
      heroTitle: 'Room Service',
      heroDescription: 'Order from your room',

      // Order success
      orderConfirmed: 'Order confirmed',
      orderSuccessMessage: 'Your order has been successfully registered. Our team will prepare it and deliver it to you as soon as possible.',
      orderNumber: 'Order #',
      newOrder: 'Place a new order',

      // No items
      serviceUnavailable: 'Service currently unavailable',
      serviceUnavailableMessage: 'Room service is not available at the moment. Please try again later or call reception at +33 5 57 34 13 95.',

      // Cart
      yourOrder: 'Your order',
      cartEmpty: 'Select items to start',
      total: 'Total',

      // Form
      roomNumber: 'Room number *',
      roomNumberPlaceholder: 'e.g.: 101',
      yourName: 'Your name',
      optionalPlaceholder: 'Optional',
      phone: 'Phone',
      phonePlaceholder: 'To reach you if needed',
      deliveryDateTime: 'Delivery date and time *',
      deliveryMinTime: 'Minimum 30 minutes in advance',
      paymentMethod: 'Payment method',
      notes: 'Notes',
      notesPlaceholder: 'Allergies, preferences...',
      orderButton: 'Order',

      // Availability
      available24h: '24/7',

      // Validation errors
      errorSelectItem: 'Please select at least one item.',
      errorRoomNumber: 'Please enter your room number.',
      errorDeliveryTime: 'Please enter the delivery date and time.',
      errorMinDeliveryTime: 'Delivery must be scheduled at least 30 minutes in advance.',

      // Categories
      categories: {
        breakfast: 'Breakfast',
        lunch: 'Lunch',
        dinner: 'Dinner',
        snacks: 'Snacks',
        drinks: 'Drinks',
        desserts: 'Desserts',
        general: 'General'
      }
    },

    home: {
      heroSubtitle: 'Welcome to {hotelName}',
      heroTitle: 'A peaceful retreat<br>at the gates of Bordeaux',
      heroDescription: 'Discover our charming 3-star hotel, nestled in the Bordeaux countryside, just minutes from Bordeaux and Saint-Émilion.',

      introSubtitle: 'Our philosophy',
      introTitle: 'A warm and friendly atmosphere',
      introText1: '{hotelName} welcomes you in a peaceful and green setting, where the charm of the Bordeaux countryside meets the comfort of a 3-star establishment.',
      introText2: 'Surrounded by nature, our hotel offers an authentic relaxation experience. Enjoy our garden, shaded terrace, and common lounge for moments of tranquility away from the city bustle.',
      featureGarden: 'Peaceful garden',
      featureTerrace: 'Shaded terrace',
      featureLounge: 'Common lounge',
      featureParking: 'Free parking',

      servicesSubtitle: 'Our services',
      servicesTitle: 'Everything for your comfort',
      servicesDescription: 'From the table d\'hôtes to the pétanque court, discover all the services that will make your stay unforgettable.',
      serviceRestaurant: 'Table d\'hôtes',
      serviceRestaurantDesc: 'Savor authentic regional cuisine for breakfast and dinner, prepared with local products.',
      serviceBar: 'Bar',
      serviceBarDesc: 'Relax in our warm bar and enjoy a selection of Bordeaux wines and cocktails.',
      serviceBoulodrome: 'Pétanque court',
      serviceBoulodromeDesc: 'Enjoy our pétanque court for convivial moments with friends or family.',
      serviceParkingTitle: 'Free parking',
      serviceParkingDesc: 'Private and secure parking offered to all our guests for a peaceful stay.',
      discoverServices: 'Discover all our services',

      ctaTitle: 'Discover our hotel',
      ctaText: 'Treat yourself to a rejuvenating stay in the heart of the Bordeaux countryside'
    },

    services: {
      heroSubtitle: '{hotelName}',
      heroTitle: 'Our Services',
      heroDescription: 'Everything for an unforgettable stay',

      introSubtitle: 'At your service',
      introTitle: 'A complete experience',
      introDescription: '{hotelName} offers you a range of services designed for your comfort and relaxation. Discover everything that will make your stay memorable.',

      restaurantSubtitle: 'Dining',
      restaurantTitle: 'Table d\'hôtes',
      restaurantText1: 'Our restaurant invites you to discover authentic regional cuisine, prepared with passion from carefully selected local products. In a convivial table d\'hôtes atmosphere, share delicious meals that celebrate the flavors of Bordeaux terroir.',
      restaurantText2: 'Breakfast and dinner are served in our warm dining room or on the terrace on sunny days, with a view of the garden.',
      tagLocalProducts: 'Local products',
      tagRegionalCuisine: 'Regional cuisine',
      tagBreakfast: 'Breakfast',
      tagDinner: 'Dinner',

      galleryRoom: 'Dining room',
      galleryRoomDesc: 'Warm atmosphere',
      galleryDecor: 'Refined decor',
      galleryDecorDesc: 'Authentic charm',
      galleryService: 'Attentive service',
      galleryServiceDesc: 'At your service',

      barSubtitle: 'Relaxation',
      barTitle: 'The Bar',
      barText1: 'Extend your evenings in our warm bar, a true place of conviviality where travelers from around the world meet. Settle in comfortably and enjoy a moment of relaxation.',
      barText2: 'Our menu honors the wines of Bordeaux and Saint-Émilion, accompanied by a selection of spirits and cocktails carefully prepared by our team.',
      tagBordeauxWines: 'Bordeaux wines',
      tagCocktails: 'Cocktails',
      tagConvivial: 'Friendly atmosphere',

      boulodromeSubtitle: 'Leisure',
      boulodromeTitle: 'Pétanque Court',
      boulodromeText1: 'At {hotelName}, we cultivate the French art of living. Our pétanque court awaits you for memorable games, whether you\'re an experienced player or simply looking for convivial moments.',
      boulodromeText2: 'Under the Gironde sun, throw your boules and enjoy the relaxed spirit of the Bordeaux countryside. With an aperitif in hand, with family or friends, it\'s the simple happiness of vacation.',
      tagPetanque: 'Pétanque court',
      tagBowlsAvailable: 'Bowls available',
      tagFreeAccess: 'Free access',

      parkingSubtitle: 'Practical',
      parkingTitle: 'Free private parking',
      parkingText1: 'Your peace of mind begins upon arrival. {hotelName} has private and secure parking, completely free for all our guests.',
      parkingText2: 'Ideally located east of Bordeaux, our establishment allows you to easily explore the vineyards, Bordeaux, or Saint-Émilion, while enjoying the calm of the countryside for your rest.',
      tagFree: 'Free',
      tagSecure: 'Private and secure',
      tag24h: '24/7 access',

      additionalSubtitle: 'And also',
      additionalTitle: 'Additional services',
      garden: 'Garden',
      gardenDesc: 'Stroll through our lush garden and enjoy the calm of the surrounding nature.',
      terrace: 'Terrace',
      terraceDesc: 'Relax on our shaded terrace, ideal for sunny breakfasts.',
      lounge: 'Common lounge',
      loungeDesc: 'Friendly space to read, relax, or share a moment with other travelers.',
      wifi: 'Free Wi-Fi',
      wifiDesc: 'High-speed internet connection available for free throughout the property.',

      ctaTitle: 'Ready for the {hotelShortName} experience?',
      ctaText: 'Contact us for more information'
    },

    activities: {
      heroSubtitle: 'Explore the region',
      heroTitle: 'Discover',
      heroDescription: 'Bordeaux, Saint-Émilion and the vineyards',

      introSubtitle: 'Your starting point',
      introTitle: 'In the heart of an exceptional region',
      introDescription: 'Ideally located between Bordeaux and Saint-Émilion, {hotelName} is the perfect starting point to explore the treasures of Gironde. Prestigious vineyards, historical heritage, and the good life await you.',

      bordeauxSubtitle: 'UNESCO World Heritage',
      bordeauxTitle: 'Bordeaux',
      bordeauxText1: 'Just a few minutes from the hotel, the city of Bordeaux opens its doors to you. Listed as a UNESCO World Heritage Site, it seduces with its 18th-century architecture, lively quays, and vibrant cultural life.',
      bordeauxText2: 'Stroll around the Place de la Bourse and its water mirror, explore the Saint-Pierre district, visit the Cité du Vin, or wander down rue Sainte-Catherine, Europe\'s longest shopping street.',
      bordeauxDistance: '~15 min by car',
      bordeauxCiteVin: 'Cité du Vin',
      bordeauxPlace: 'Place de la Bourse',

      saintEmilionSubtitle: 'Medieval village',
      saintEmilionTitle: 'Saint-Émilion',
      saintEmilionText1: 'A jewel of French heritage, Saint-Émilion is a medieval village perched amid the vines. Its cobbled streets, monolithic church carved into the rock, and centuries-old ramparts transport you to another time.',
      saintEmilionText2: 'Beyond its historical charm, Saint-Émilion is the birthplace of some of the world\'s most renowned wines. Tastings at châteaux, cellar visits, and walks through the vineyards will punctuate your discovery.',
      saintEmilionDistance: '~25 min by car',
      saintEmilionChurch: 'Monolithic church',
      saintEmilionWines: 'Grand crus classés',

      wineSubtitle: 'Wine tourism',
      wineTitle: 'The wine route',
      wineDescription: 'Gironde is home to some of the world\'s most prestigious wine appellations. Set off to discover the châteaux and their secrets.',

      tastingTitle: 'Tastings',
      tastingText: 'The region\'s châteaux welcome you for tastings of their finest vintages. Discover the secrets of winemaking and take home your favorite bottles.',
      cellarTitle: 'Cellar visits',
      cellarText: 'Enter the centuries-old cellars where the great wines of Bordeaux age. A unique sensory experience between tradition and expertise.',
      vineyardTitle: 'Vineyard walks',
      vineyardText: 'On foot, by bike, or by car, travel the winding roads between the rows of vines. The vineyard landscape of Gironde is a UNESCO World Heritage Site.',
      gastronomyTitle: 'Local gastronomy',
      gastronomyText: 'Accompany your wine discoveries with the rich cuisine of the Southwest: duck, cèpes, Arcachon Bay oysters, and traditional desserts.',

      countrysideSubtitle: 'Nature & relaxation',
      countrysideTitle: 'Countryside escapes',
      countrysideText1: 'Beyond the vineyards, the Gironde countryside offers countless opportunities to recharge. Pine forests, peaceful rivers, and characterful villages dot a preserved landscape.',
      countrysideText2: 'Set off on marked hiking trails, rent a bike to explore the small roads, or simply enjoy the surrounding calm from our garden.',
      hikingTrails: 'Hiking trails',
      cyclingPaths: 'Cycling paths',
      villages: 'Picturesque villages',
      markets: 'Local markets',

      otherSubtitle: 'And also',
      otherTitle: 'Other sites to discover',
      arcachon: 'Arcachon Bay',
      arcachonDesc: 'The Dune of Pilat, oyster villages, and ocean beaches about 1 hour away.',
      medoc: 'Médoc Châteaux',
      medocDesc: 'Margaux, Pauillac, Saint-Julien: the greatest names in wine open their doors to you.',
      libourne: 'Libourne',
      libourneDesc: 'Medieval bastide at the confluence of the Dordogne and Isle rivers, in immediate proximity.',
      marketsTitle: 'Local markets',
      marketsDesc: 'Local products, cheeses, charcuterie, and regional specialties every week.',

      ctaTitle: 'Ready for adventure?',
      ctaText: 'Contact us to discover the Bordeaux region'
    },

    contact: {
      heroSubtitle: 'Contact us',
      heroTitle: 'Contact',
      heroDescription: 'We are here to help',

      introSubtitle: 'Stay in touch',
      introTitle: 'How to reach us',
      introDescription: 'A question, a request for information, or a reservation? Don\'t hesitate to contact us. Our team will be happy to respond as soon as possible.',

      infoTitle: 'Our contact details',
      addressLabel: 'Address',
      addressValue: '{hotelName}<br>Tresses, Bordeaux East<br>33370 Gironde, France',
      phoneLabel: 'Phone',
      emailLabel: 'Email',
      receptionLabel: 'Reception',
      receptionValue: 'Open 7 days a week<br>7:00 AM - 10:00 PM',

      findUs: 'Find us',

      formTitle: 'Send us a message',
      firstName: 'First name',
      lastName: 'Last name',
      email: 'Email',
      phone: 'Phone',
      subject: 'Subject',
      message: 'Message',
      send: 'Send message',
      formSuccess: 'Thank you for your message! We will respond as soon as possible.',

      placeholderFirstName: 'Your first name',
      placeholderLastName: 'Your last name',
      placeholderEmail: 'your@email.com',
      placeholderPhone: '+33 6 XX XX XX XX',
      placeholderSubject: 'Subject of your message',
      placeholderMessage: 'Your message...',

      accessSubtitle: 'How to get here',
      accessTitle: 'Access to the hotel',
      byCar: 'By car',
      byCarDesc: 'From Bordeaux, take the ring road towards Libourne/Paris. Exit Tresses/Artigues. Free parking on site.',
      byTrain: 'By train',
      byTrainDesc: 'Bordeaux Saint-Jean station 15 km away. Taxis and VTC available. We can arrange your transfer on request.',
      byPlane: 'By plane',
      byPlaneDesc: 'Bordeaux-Mérignac airport 25 km away. Shuttles and car rentals available at the airport.',
      byBike: 'By bike',
      byBikeDesc: 'Bike paths from Bordeaux. Secure bike storage available for our cycling guests.',

      ctaTitle: 'Have questions?',
      ctaText: 'Don\'t hesitate to contact us, our team is at your service',
      callUs: 'Call us'
    },

    footer: {
      description: 'A peaceful retreat at the gates of Bordeaux, where charm and authenticity await you for an unforgettable stay.',
      navigation: 'Navigation',
      services: 'Services',
      contactTitle: 'Contact',
      copyright: '© 2024 {hotelName}. All rights reserved.',
      // Footer links
      home: 'Home',
      discover: 'Discover',
      restaurant: 'Restaurant',
      bar: 'Bar',
      roomService: 'Room Service',
      parking: 'Parking'
    },

    common: {
      learnMore: 'Learn more',
      backToTop: 'Back to top'
    }
  },

  // Spanish
  es: {
    nav: {
      home: 'Inicio',
      services: 'Servicios',
      roomService: 'Room Service',
      activities: 'Descubrir',
      discover: 'Descubrir',
      contact: 'Contacto'
    },

    header: {
      logoSubtitle: 'Burdeos Este',
      contactReception: 'Contactar recepción'
    },

    modal: {
      contactReceptionTitle: 'Contactar recepción',
      roomNumber: 'Número de habitación *',
      roomNumberPlaceholder: 'Ej: 101',
      guestName: 'Su nombre',
      guestNamePlaceholder: 'Opcional',
      category: 'Categoría',
      subject: 'Asunto',
      subjectPlaceholder: 'Resumen del problema',
      message: 'Su mensaje *',
      messagePlaceholder: 'Describa su solicitud o problema...',
      sendMessage: 'Enviar mensaje',
      successTitle: 'Mensaje enviado',
      successMessage: 'Su mensaje ha sido enviado a recepción. Le responderemos lo antes posible.',
      newMessage: 'Enviar otro mensaje',
      errorGeneric: 'Se produjo un error. Por favor, inténtelo de nuevo.'
    },

    // Room Service page
    roomService: {
      // Hero
      heroSubtitle: '{hotelName}',
      heroTitle: 'Room Service',
      heroDescription: 'Pida desde su habitación',

      // Order success
      orderConfirmed: 'Pedido confirmado',
      orderSuccessMessage: 'Su pedido ha sido registrado con éxito. Nuestro equipo lo preparará y se lo entregará lo antes posible.',
      orderNumber: 'Pedido #',
      newOrder: 'Hacer un nuevo pedido',

      // No items
      serviceUnavailable: 'Servicio actualmente no disponible',
      serviceUnavailableMessage: 'El room service no está disponible en este momento. Por favor, inténtelo más tarde o llame a recepción al +33 5 57 34 13 95.',

      // Cart
      yourOrder: 'Su pedido',
      cartEmpty: 'Seleccione artículos para comenzar',
      total: 'Total',

      // Form
      roomNumber: 'Número de habitación *',
      roomNumberPlaceholder: 'Ej: 101',
      yourName: 'Su nombre',
      optionalPlaceholder: 'Opcional',
      phone: 'Teléfono',
      phonePlaceholder: 'Para contactarle si es necesario',
      deliveryDateTime: 'Fecha y hora de entrega *',
      deliveryMinTime: 'Mínimo 30 minutos de antelación',
      paymentMethod: 'Método de pago',
      notes: 'Notas',
      notesPlaceholder: 'Alergias, preferencias...',
      orderButton: 'Pedir',

      // Availability
      available24h: '24h/24',

      // Validation errors
      errorSelectItem: 'Por favor, seleccione al menos un artículo.',
      errorRoomNumber: 'Por favor, indique su número de habitación.',
      errorDeliveryTime: 'Por favor, indique la fecha y hora de entrega.',
      errorMinDeliveryTime: 'La entrega debe programarse con al menos 30 minutos de antelación.',

      // Categories
      categories: {
        breakfast: 'Desayuno',
        lunch: 'Almuerzo',
        dinner: 'Cena',
        snacks: 'Aperitivos',
        drinks: 'Bebidas',
        desserts: 'Postres',
        general: 'General'
      }
    },

    home: {
      heroSubtitle: 'Bienvenido a {hotelName}',
      heroTitle: 'Un remanso de paz<br>a las puertas de Burdeos',
      heroDescription: 'Descubra nuestro encantador hotel de 3 estrellas, enclavado en la campiña bordelesa, a pocos minutos de Burdeos y Saint-Émilion.',

      introSubtitle: 'Nuestra filosofía',
      introTitle: 'Un ambiente cálido y acogedor',
      introText1: '{hotelName} le da la bienvenida en un entorno tranquilo y verde, donde el encanto del campo bordelés se combina con el confort de un establecimiento de 3 estrellas.',
      introText2: 'Rodeado de naturaleza, nuestro hotel ofrece una experiencia de relajación auténtica. Disfrute de nuestro jardín, terraza sombreada y salón común para momentos de tranquilidad lejos del bullicio de la ciudad.',
      featureGarden: 'Jardín tranquilo',
      featureTerrace: 'Terraza sombreada',
      featureLounge: 'Salón común',
      featureParking: 'Aparcamiento gratuito',

      servicesSubtitle: 'Nuestros servicios',
      servicesTitle: 'Todo para su comodidad',
      servicesDescription: 'Desde la mesa de huéspedes hasta la cancha de petanca, descubra todos los servicios que harán de su estancia algo inolvidable.',
      serviceRestaurant: 'Mesa de huéspedes',
      serviceRestaurantDesc: 'Saboree una auténtica cocina regional para el desayuno y la cena, preparada con productos locales.',
      serviceBar: 'Bar',
      serviceBarDesc: 'Relájese en nuestro acogedor bar y disfrute de una selección de vinos de Burdeos y cócteles.',
      serviceBoulodrome: 'Cancha de petanca',
      serviceBoulodromeDesc: 'Disfrute de nuestra cancha de petanca para momentos de convivencia con amigos o familia.',
      serviceParkingTitle: 'Aparcamiento gratuito',
      serviceParkingDesc: 'Aparcamiento privado y seguro ofrecido a todos nuestros clientes para una estancia tranquila.',
      discoverServices: 'Descubrir todos nuestros servicios',

      ctaTitle: 'Descubra nuestro hotel',
      ctaText: 'Regálese una estancia rejuvenecedora en el corazón del campo bordelés'
    },

    services: {
      heroSubtitle: '{hotelName}',
      heroTitle: 'Nuestros Servicios',
      heroDescription: 'Todo para una estancia inolvidable',

      introSubtitle: 'A su servicio',
      introTitle: 'Una experiencia completa',
      introDescription: '{hotelName} pone a su disposición una gama de servicios pensados para su comodidad y relajación. Descubra todo lo que hará de su estancia algo memorable.',

      restaurantSubtitle: 'Restauración',
      restaurantTitle: 'Mesa de huéspedes',
      restaurantText1: 'Nuestro restaurante le invita a descubrir una auténtica cocina regional, preparada con pasión a partir de productos locales cuidadosamente seleccionados. En un ambiente convivial de mesa de huéspedes, comparta deliciosas comidas que celebran los sabores del terroir bordelés.',
      restaurantText2: 'El desayuno y la cena se sirven en nuestro acogedor comedor o en la terraza en los días soleados, con vistas al jardín.',
      tagLocalProducts: 'Productos locales',
      tagRegionalCuisine: 'Cocina regional',
      tagBreakfast: 'Desayuno',
      tagDinner: 'Cena',

      galleryRoom: 'Comedor',
      galleryRoomDesc: 'Ambiente cálido',
      galleryDecor: 'Decoración cuidada',
      galleryDecorDesc: 'Encanto auténtico',
      galleryService: 'Servicio atento',
      galleryServiceDesc: 'A su escucha',

      barSubtitle: 'Relajación',
      barTitle: 'El Bar',
      barText1: 'Prolongue sus veladas en nuestro acogedor bar, un verdadero lugar de convivencia donde se encuentran viajeros de todo el mundo. Acomódese y disfrute de un momento de relajación.',
      barText2: 'Nuestra carta honra los vinos de Burdeos y Saint-Émilion, acompañados de una selección de licores y cócteles preparados con esmero por nuestro equipo.',
      tagBordeauxWines: 'Vinos de Burdeos',
      tagCocktails: 'Cócteles',
      tagConvivial: 'Ambiente acogedor',

      boulodromeSubtitle: 'Ocio',
      boulodromeTitle: 'Cancha de petanca',
      boulodromeText1: 'En {hotelName} cultivamos el arte de vivir a la francesa. Nuestra cancha de petanca le espera para partidas memorables, ya sea un jugador experimentado o simplemente busque momentos de convivencia.',
      boulodromeText2: 'Bajo el sol de Gironda, lance sus bolas y disfrute del espíritu relajado del campo bordelés. Con un aperitivo en la mano, en familia o entre amigos, es la felicidad simple de las vacaciones.',
      tagPetanque: 'Cancha de petanca',
      tagBowlsAvailable: 'Bolas disponibles',
      tagFreeAccess: 'Acceso libre',

      parkingSubtitle: 'Práctico',
      parkingTitle: 'Aparcamiento privado gratuito',
      parkingText1: 'Su tranquilidad comienza desde su llegada. {hotelName} dispone de un aparcamiento privado y seguro, totalmente gratuito para todos nuestros clientes.',
      parkingText2: 'Idealmente situado al este de Burdeos, nuestro establecimiento le permite explorar fácilmente los viñedos, Burdeos o Saint-Émilion, mientras disfruta de la calma del campo para su descanso.',
      tagFree: 'Gratuito',
      tagSecure: 'Privado y seguro',
      tag24h: 'Acceso 24h/24',

      additionalSubtitle: 'Y también',
      additionalTitle: 'Servicios adicionales',
      garden: 'Jardín',
      gardenDesc: 'Pasee por nuestro frondoso jardín y disfrute de la calma de la naturaleza circundante.',
      terrace: 'Terraza',
      terraceDesc: 'Relájese en nuestra terraza sombreada, ideal para desayunos soleados.',
      lounge: 'Salón común',
      loungeDesc: 'Espacio acogedor para leer, relajarse o compartir un momento con otros viajeros.',
      wifi: 'Wi-Fi gratuito',
      wifiDesc: 'Conexión a internet de alta velocidad disponible gratuitamente en todo el establecimiento.',

      ctaTitle: '¿Listo para la experiencia {hotelShortName}?',
      ctaText: 'Contáctenos para más información'
    },

    activities: {
      heroSubtitle: 'Explore la región',
      heroTitle: 'Descubrir',
      heroDescription: 'Burdeos, Saint-Émilion y los viñedos',

      introSubtitle: 'Su punto de partida',
      introTitle: 'En el corazón de una región excepcional',
      introDescription: 'Idealmente situado entre Burdeos y Saint-Émilion, {hotelName} es el punto de partida perfecto para explorar los tesoros de Gironda. Viñedos prestigiosos, patrimonio histórico y la buena vida le esperan.',

      bordeauxSubtitle: 'Patrimonio Mundial UNESCO',
      bordeauxTitle: 'Burdeos',
      bordeauxText1: 'A solo unos minutos del hotel, la ciudad de Burdeos le abre sus puertas. Declarada Patrimonio de la Humanidad por la UNESCO, seduce con su arquitectura del siglo XVIII, sus animados muelles y su vibrante vida cultural.',
      bordeauxText2: 'Pasee por la Place de la Bourse y su espejo de agua, explore el barrio de Saint-Pierre, visite la Cité du Vin o recorra la rue Sainte-Catherine, la calle comercial más larga de Europa.',
      bordeauxDistance: '~15 min en coche',
      bordeauxCiteVin: 'Cité du Vin',
      bordeauxPlace: 'Place de la Bourse',

      saintEmilionSubtitle: 'Pueblo medieval',
      saintEmilionTitle: 'Saint-Émilion',
      saintEmilionText1: 'Joya del patrimonio francés, Saint-Émilion es un pueblo medieval encaramado en medio de las viñas. Sus calles empedradas, su iglesia monolítica excavada en la roca y sus murallas centenarias le transportan a otro tiempo.',
      saintEmilionText2: 'Más allá de su encanto histórico, Saint-Émilion es la cuna de algunos de los vinos más renombrados del mundo. Degustaciones en los châteaux, visitas a bodegas y paseos por los viñedos marcarán su descubrimiento.',
      saintEmilionDistance: '~25 min en coche',
      saintEmilionChurch: 'Iglesia monolítica',
      saintEmilionWines: 'Grands crus classés',

      wineSubtitle: 'Enoturismo',
      wineTitle: 'La ruta del vino',
      wineDescription: 'Gironda alberga algunas de las denominaciones de vino más prestigiosas del mundo. Parta a descubrir los châteaux y sus secretos.',

      tastingTitle: 'Degustaciones',
      tastingText: 'Los châteaux de la región le reciben para degustaciones de sus mejores cosechas. Descubra los secretos de la vinificación y llévese sus botellas favoritas.',
      cellarTitle: 'Visitas a bodegas',
      cellarText: 'Entre en las bodegas centenarias donde envejecen los grandes vinos de Burdeos. Una experiencia sensorial única entre tradición y saber hacer.',
      vineyardTitle: 'Paseos por los viñedos',
      vineyardText: 'A pie, en bicicleta o en coche, recorra los caminos sinuosos entre las hileras de viñas. El paisaje vitícola de Gironda es Patrimonio de la Humanidad.',
      gastronomyTitle: 'Gastronomía local',
      gastronomyText: 'Acompañe sus descubrimientos vinícolas con la rica cocina del suroeste: pato, cèpes, ostras de la bahía de Arcachon y postres tradicionales.',

      countrysideSubtitle: 'Naturaleza y relajación',
      countrysideTitle: 'Escapadas al campo',
      countrysideText1: 'Más allá de los viñedos, el campo de Gironda ofrece innumerables oportunidades para recargar energías. Bosques de pinos, ríos tranquilos y pueblos con carácter salpican un paisaje preservado.',
      countrysideText2: 'Salga de excursión por los senderos señalizados, alquile una bicicleta para explorar los caminos pequeños, o simplemente disfrute de la calma circundante desde nuestro jardín.',
      hikingTrails: 'Senderos de senderismo',
      cyclingPaths: 'Carriles bici',
      villages: 'Pueblos pintorescos',
      markets: 'Mercados locales',

      otherSubtitle: 'Y también',
      otherTitle: 'Otros lugares por descubrir',
      arcachon: 'Bahía de Arcachon',
      arcachonDesc: 'La Duna de Pilat, pueblos ostrícolas y playas oceánicas a aproximadamente 1 hora.',
      medoc: 'Châteaux del Médoc',
      medocDesc: 'Margaux, Pauillac, Saint-Julien: los grandes nombres del vino le abren sus puertas.',
      libourne: 'Libourne',
      libourneDesc: 'Bastida medieval en la confluencia del Dordoña y el Isle, en proximidad inmediata.',
      marketsTitle: 'Mercados locales',
      marketsDesc: 'Productos del terroir, quesos, embutidos y especialidades regionales cada semana.',

      ctaTitle: '¿Listo para la aventura?',
      ctaText: 'Contáctenos para descubrir la región bordelesa'
    },

    contact: {
      heroSubtitle: 'Contáctenos',
      heroTitle: 'Contacto',
      heroDescription: 'Estamos a su disposición',

      introSubtitle: 'Mantengámonos en contacto',
      introTitle: 'Cómo contactarnos',
      introDescription: '¿Una pregunta, una solicitud de información o una reserva? No dude en contactarnos. Nuestro equipo estará encantado de responderle lo antes posible.',

      infoTitle: 'Nuestros datos de contacto',
      addressLabel: 'Dirección',
      addressValue: '{hotelName}<br>Tresses, Burdeos Este<br>33370 Gironda, Francia',
      phoneLabel: 'Teléfono',
      emailLabel: 'Email',
      receptionLabel: 'Recepción',
      receptionValue: 'Abierta 7 días a la semana<br>7:00 - 22:00',

      findUs: 'Encuéntrenos',

      formTitle: 'Envíenos un mensaje',
      firstName: 'Nombre',
      lastName: 'Apellido',
      email: 'Email',
      phone: 'Teléfono',
      subject: 'Asunto',
      message: 'Mensaje',
      send: 'Enviar mensaje',
      formSuccess: '¡Gracias por su mensaje! Le responderemos lo antes posible.',

      placeholderFirstName: 'Su nombre',
      placeholderLastName: 'Su apellido',
      placeholderEmail: 'su@email.com',
      placeholderPhone: '+33 6 XX XX XX XX',
      placeholderSubject: 'Asunto de su mensaje',
      placeholderMessage: 'Su mensaje...',

      accessSubtitle: 'Cómo llegar',
      accessTitle: 'Acceso al hotel',
      byCar: 'En coche',
      byCarDesc: 'Desde Burdeos, tome la circunvalación dirección Libourne/París. Salida Tresses/Artigues. Aparcamiento gratuito en el lugar.',
      byTrain: 'En tren',
      byTrainDesc: 'Estación Bordeaux Saint-Jean a 15 km. Taxis y VTC disponibles. Podemos organizar su traslado a petición.',
      byPlane: 'En avión',
      byPlaneDesc: 'Aeropuerto de Bordeaux-Mérignac a 25 km. Lanzaderas y alquiler de coches disponibles en el aeropuerto.',
      byBike: 'En bicicleta',
      byBikeDesc: 'Carriles bici desde Burdeos. Almacenamiento seguro de bicicletas disponible para nuestros clientes ciclistas.',

      ctaTitle: '¿Tiene preguntas?',
      ctaText: 'No dude en contactarnos, nuestro equipo está a su servicio',
      callUs: 'Llámenos'
    },

    footer: {
      description: 'Un remanso de paz a las puertas de Burdeos, donde el encanto y la autenticidad le esperan para una estancia inolvidable.',
      navigation: 'Navegación',
      services: 'Servicios',
      contactTitle: 'Contacto',
      copyright: '© 2024 {hotelName}. Todos los derechos reservados.',
      // Footer links
      home: 'Inicio',
      discover: 'Descubrir',
      restaurant: 'Restaurante',
      bar: 'Bar',
      roomService: 'Room Service',
      parking: 'Aparcamiento'
    },

    common: {
      learnMore: 'Saber más',
      backToTop: 'Volver arriba'
    }
  },

  // Italian
  it: {
    nav: {
      home: 'Home',
      services: 'Servizi',
      roomService: 'Room Service',
      activities: 'Da scoprire',
      discover: 'Da scoprire',
      contact: 'Contatti'
    },

    header: {
      logoSubtitle: 'Bordeaux Est',
      contactReception: 'Contatta la reception'
    },

    modal: {
      contactReceptionTitle: 'Contatta la reception',
      roomNumber: 'Numero di camera *',
      roomNumberPlaceholder: 'Es: 101',
      guestName: 'Il tuo nome',
      guestNamePlaceholder: 'Opzionale',
      category: 'Categoria',
      subject: 'Oggetto',
      subjectPlaceholder: 'Riassunto del problema',
      message: 'Il tuo messaggio *',
      messagePlaceholder: 'Descrivi la tua richiesta o problema...',
      sendMessage: 'Invia messaggio',
      successTitle: 'Messaggio inviato',
      successMessage: 'Il tuo messaggio è stato inviato alla reception. Ti risponderemo il prima possibile.',
      newMessage: 'Invia un altro messaggio',
      errorGeneric: 'Si è verificato un errore. Per favore riprova.'
    },

    // Room Service page
    roomService: {
      // Hero
      heroSubtitle: '{hotelName}',
      heroTitle: 'Room Service',
      heroDescription: 'Ordina dalla tua camera',

      // Order success
      orderConfirmed: 'Ordine confermato',
      orderSuccessMessage: 'Il tuo ordine è stato registrato con successo. Il nostro team lo preparerà e te lo consegnerà il prima possibile.',
      orderNumber: 'Ordine #',
      newOrder: 'Effettua un nuovo ordine',

      // No items
      serviceUnavailable: 'Servizio attualmente non disponibile',
      serviceUnavailableMessage: 'Il room service non è disponibile al momento. Per favore riprova più tardi o chiama la reception al +33 5 57 34 13 95.',

      // Cart
      yourOrder: 'Il tuo ordine',
      cartEmpty: 'Seleziona articoli per iniziare',
      total: 'Totale',

      // Form
      roomNumber: 'Numero di camera *',
      roomNumberPlaceholder: 'Es: 101',
      yourName: 'Il tuo nome',
      optionalPlaceholder: 'Opzionale',
      phone: 'Telefono',
      phonePlaceholder: 'Per contattarti se necessario',
      deliveryDateTime: 'Data e ora di consegna *',
      deliveryMinTime: 'Minimo 30 minuti in anticipo',
      paymentMethod: 'Metodo di pagamento',
      notes: 'Note',
      notesPlaceholder: 'Allergie, preferenze...',
      orderButton: 'Ordina',

      // Availability
      available24h: '24h/24',

      // Validation errors
      errorSelectItem: 'Per favore seleziona almeno un articolo.',
      errorRoomNumber: 'Per favore inserisci il numero della tua camera.',
      errorDeliveryTime: 'Per favore inserisci la data e l\'ora di consegna.',
      errorMinDeliveryTime: 'La consegna deve essere programmata con almeno 30 minuti di anticipo.',

      // Categories
      categories: {
        breakfast: 'Colazione',
        lunch: 'Pranzo',
        dinner: 'Cena',
        snacks: 'Snack',
        drinks: 'Bevande',
        desserts: 'Dolci',
        general: 'Generale'
      }
    },

    home: {
      heroSubtitle: 'Benvenuti a {hotelName}',
      heroTitle: 'Un\'oasi di pace<br>alle porte di Bordeaux',
      heroDescription: 'Scoprite il nostro affascinante hotel 3 stelle, immerso nella campagna bordolese, a pochi minuti da Bordeaux e Saint-Émilion.',

      introSubtitle: 'La nostra filosofia',
      introTitle: 'Un\'atmosfera calda e accogliente',
      introText1: '{hotelName} vi accoglie in un ambiente tranquillo e verde, dove il fascino della campagna bordolese si unisce al comfort di una struttura 3 stelle.',
      introText2: 'Circondato dalla natura, il nostro hotel offre un\'esperienza di relax autentica. Godetevi il nostro giardino, la terrazza ombreggiata e il salotto comune per momenti di tranquillità lontano dal trambusto della città.',
      featureGarden: 'Giardino tranquillo',
      featureTerrace: 'Terrazza ombreggiata',
      featureLounge: 'Salotto comune',
      featureParking: 'Parcheggio gratuito',

      servicesSubtitle: 'I nostri servizi',
      servicesTitle: 'Tutto per il vostro comfort',
      servicesDescription: 'Dalla table d\'hôtes al campo da bocce, scoprite tutti i servizi che renderanno il vostro soggiorno indimenticabile.',
      serviceRestaurant: 'Table d\'hôtes',
      serviceRestaurantDesc: 'Gustate un\'autentica cucina regionale per colazione e cena, preparata con prodotti locali.',
      serviceBar: 'Bar',
      serviceBarDesc: 'Rilassatevi nel nostro accogliente bar e gustate una selezione di vini di Bordeaux e cocktail.',
      serviceBoulodrome: 'Campo da bocce',
      serviceBoulodromeDesc: 'Godetevi il nostro campo da bocce per momenti conviviali con amici o famiglia.',
      serviceParkingTitle: 'Parcheggio gratuito',
      serviceParkingDesc: 'Parcheggio privato e sicuro offerto a tutti i nostri clienti per un soggiorno tranquillo.',
      discoverServices: 'Scopri tutti i nostri servizi',

      ctaTitle: 'Scoprite il nostro hotel',
      ctaText: 'Concedetevi un soggiorno rigenerante nel cuore della campagna bordolese'
    },

    services: {
      heroSubtitle: '{hotelName}',
      heroTitle: 'I Nostri Servizi',
      heroDescription: 'Tutto per un soggiorno indimenticabile',

      introSubtitle: 'Al vostro servizio',
      introTitle: 'Un\'esperienza completa',
      introDescription: '{hotelName} mette a vostra disposizione una gamma di servizi pensati per il vostro comfort e relax. Scoprite tutto ciò che renderà il vostro soggiorno memorabile.',

      restaurantSubtitle: 'Ristorazione',
      restaurantTitle: 'Table d\'hôtes',
      restaurantText1: 'Il nostro ristorante vi invita a scoprire un\'autentica cucina regionale, preparata con passione da prodotti locali accuratamente selezionati. In un\'atmosfera conviviale di table d\'hôtes, condividete pasti deliziosi che celebrano i sapori del terroir bordolese.',
      restaurantText2: 'La colazione e la cena vengono servite nella nostra calda sala da pranzo o in terrazza nelle belle giornate, con vista sul giardino.',
      tagLocalProducts: 'Prodotti locali',
      tagRegionalCuisine: 'Cucina regionale',
      tagBreakfast: 'Colazione',
      tagDinner: 'Cena',

      galleryRoom: 'Sala ristorante',
      galleryRoomDesc: 'Atmosfera calda',
      galleryDecor: 'Arredamento curato',
      galleryDecorDesc: 'Fascino autentico',
      galleryService: 'Servizio attento',
      galleryServiceDesc: 'A vostra disposizione',

      barSubtitle: 'Relax',
      barTitle: 'Il Bar',
      barText1: 'Prolungate le vostre serate nel nostro accogliente bar, un vero luogo di convivialità dove si incontrano viaggiatori da tutto il mondo. Accomodatevi e godetevi un momento di relax.',
      barText2: 'Il nostro menu onora i vini di Bordeaux e Saint-Émilion, accompagnati da una selezione di liquori e cocktail preparati con cura dal nostro team.',
      tagBordeauxWines: 'Vini di Bordeaux',
      tagCocktails: 'Cocktail',
      tagConvivial: 'Atmosfera conviviale',

      boulodromeSubtitle: 'Svago',
      boulodromeTitle: 'Campo da bocce',
      boulodromeText1: 'A {hotelName} coltiviamo l\'arte del vivere alla francese. Il nostro campo da bocce vi aspetta per partite memorabili, che siate giocatori esperti o semplicemente alla ricerca di momenti conviviali.',
      boulodromeText2: 'Sotto il sole della Gironda, lanciate le vostre bocce e godetevi lo spirito rilassato della campagna bordolese. Con un aperitivo in mano, in famiglia o tra amici, è la felicità semplice delle vacanze.',
      tagPetanque: 'Campo da bocce',
      tagBowlsAvailable: 'Bocce disponibili',
      tagFreeAccess: 'Accesso libero',

      parkingSubtitle: 'Pratico',
      parkingTitle: 'Parcheggio privato gratuito',
      parkingText1: 'La vostra tranquillità inizia al vostro arrivo. {hotelName} dispone di un parcheggio privato e sicuro, completamente gratuito per tutti i nostri clienti.',
      parkingText2: 'Idealmente situato a est di Bordeaux, il nostro stabilimento vi permette di esplorare facilmente i vigneti, Bordeaux o Saint-Émilion, godendo della calma della campagna per il vostro riposo.',
      tagFree: 'Gratuito',
      tagSecure: 'Privato e sicuro',
      tag24h: 'Accesso 24h/24',

      additionalSubtitle: 'E anche',
      additionalTitle: 'Servizi aggiuntivi',
      garden: 'Giardino',
      gardenDesc: 'Passeggiate nel nostro rigoglioso giardino e godetevi la calma della natura circostante.',
      terrace: 'Terrazza',
      terraceDesc: 'Rilassatevi sulla nostra terrazza ombreggiata, ideale per colazioni soleggiate.',
      lounge: 'Salotto comune',
      loungeDesc: 'Spazio accogliente per leggere, rilassarsi o condividere un momento con altri viaggiatori.',
      wifi: 'Wi-Fi gratuito',
      wifiDesc: 'Connessione internet ad alta velocità disponibile gratuitamente in tutta la struttura.',

      ctaTitle: 'Pronti per l\'esperienza {hotelShortName}?',
      ctaText: 'Contattateci per maggiori informazioni'
    },

    activities: {
      heroSubtitle: 'Esplorate la regione',
      heroTitle: 'Da Scoprire',
      heroDescription: 'Bordeaux, Saint-Émilion e i vigneti',

      introSubtitle: 'Il vostro punto di partenza',
      introTitle: 'Nel cuore di una regione eccezionale',
      introDescription: 'Idealmente situato tra Bordeaux e Saint-Émilion, {hotelName} è il punto di partenza perfetto per esplorare i tesori della Gironda. Vigneti prestigiosi, patrimonio storico e dolce vita vi attendono.',

      bordeauxSubtitle: 'Patrimonio UNESCO',
      bordeauxTitle: 'Bordeaux',
      bordeauxText1: 'A pochi minuti dall\'hotel, la città di Bordeaux vi apre le sue porte. Dichiarata Patrimonio dell\'Umanità UNESCO, seduce con la sua architettura del XVIII secolo, i suoi moli animati e la sua vibrante vita culturale.',
      bordeauxText2: 'Passeggiate in Place de la Bourse e il suo specchio d\'acqua, esplorate il quartiere Saint-Pierre, visitate la Cité du Vin o camminate per rue Sainte-Catherine, la via commerciale più lunga d\'Europa.',
      bordeauxDistance: '~15 min in auto',
      bordeauxCiteVin: 'Cité du Vin',
      bordeauxPlace: 'Place de la Bourse',

      saintEmilionSubtitle: 'Villaggio medievale',
      saintEmilionTitle: 'Saint-Émilion',
      saintEmilionText1: 'Gioiello del patrimonio francese, Saint-Émilion è un villaggio medievale arroccato tra le vigne. Le sue stradine acciottolate, la sua chiesa monolitica scavata nella roccia e le sue mura centenarie vi trasportano in un altro tempo.',
      saintEmilionText2: 'Oltre al suo fascino storico, Saint-Émilion è la culla di alcuni dei vini più rinomati al mondo. Degustazioni nei châteaux, visite alle cantine e passeggiate nei vigneti scandiscono la vostra scoperta.',
      saintEmilionDistance: '~25 min in auto',
      saintEmilionChurch: 'Chiesa monolitica',
      saintEmilionWines: 'Grands crus classés',

      wineSubtitle: 'Enoturismo',
      wineTitle: 'La strada del vino',
      wineDescription: 'La Gironda ospita alcune delle denominazioni vinicole più prestigiose del mondo. Partite alla scoperta dei châteaux e dei loro segreti.',

      tastingTitle: 'Degustazioni',
      tastingText: 'I châteaux della regione vi accolgono per degustazioni delle loro migliori annate. Scoprite i segreti della vinificazione e portate a casa le vostre bottiglie preferite.',
      cellarTitle: 'Visite alle cantine',
      cellarText: 'Entrate nelle cantine centenarie dove invecchiano i grandi vini di Bordeaux. Un\'esperienza sensoriale unica tra tradizione e savoir-faire.',
      vineyardTitle: 'Passeggiate nei vigneti',
      vineyardText: 'A piedi, in bicicletta o in auto, percorrete le strade sinuose tra i filari di viti. Il paesaggio viticolo della Gironda è Patrimonio dell\'Umanità.',
      gastronomyTitle: 'Gastronomia locale',
      gastronomyText: 'Accompagnate le vostre scoperte vinicole con la ricca cucina del Sud-Ovest: anatra, porcini, ostriche della baia di Arcachon e dolci tradizionali.',

      countrysideSubtitle: 'Natura e relax',
      countrysideTitle: 'Fughe in campagna',
      countrysideText1: 'Oltre ai vigneti, la campagna della Gironda offre innumerevoli opportunità per ricaricarsi. Pinete, fiumi tranquilli e villaggi caratteristici punteggiano un paesaggio preservato.',
      countrysideText2: 'Partite per un\'escursione sui sentieri segnalati, noleggiate una bicicletta per esplorare le stradine, o semplicemente godetevi la calma circostante dal nostro giardino.',
      hikingTrails: 'Sentieri escursionistici',
      cyclingPaths: 'Piste ciclabili',
      villages: 'Villaggi pittoreschi',
      markets: 'Mercati locali',

      otherSubtitle: 'E anche',
      otherTitle: 'Altri luoghi da scoprire',
      arcachon: 'Baia di Arcachon',
      arcachonDesc: 'La Duna di Pilat, villaggi di ostriche e spiagge oceaniche a circa 1 ora.',
      medoc: 'Châteaux del Médoc',
      medocDesc: 'Margaux, Pauillac, Saint-Julien: i più grandi nomi del vino vi aprono le loro porte.',
      libourne: 'Libourne',
      libourneDesc: 'Bastide medievale alla confluenza della Dordogna e dell\'Isle, nelle immediate vicinanze.',
      marketsTitle: 'Mercati locali',
      marketsDesc: 'Prodotti del territorio, formaggi, salumi e specialità regionali ogni settimana.',

      ctaTitle: 'Pronti per l\'avventura?',
      ctaText: 'Contattateci per scoprire la regione bordolese'
    },

    contact: {
      heroSubtitle: 'Contattateci',
      heroTitle: 'Contatti',
      heroDescription: 'Siamo a vostra disposizione',

      introSubtitle: 'Restiamo in contatto',
      introTitle: 'Come contattarci',
      introDescription: 'Una domanda, una richiesta di informazioni o una prenotazione? Non esitate a contattarci. Il nostro team sarà lieto di rispondervi il prima possibile.',

      infoTitle: 'I nostri recapiti',
      addressLabel: 'Indirizzo',
      addressValue: '{hotelName}<br>Tresses, Bordeaux Est<br>33370 Gironda, Francia',
      phoneLabel: 'Telefono',
      emailLabel: 'Email',
      receptionLabel: 'Reception',
      receptionValue: 'Aperta 7 giorni su 7<br>7:00 - 22:00',

      findUs: 'Trovateci',

      formTitle: 'Inviateci un messaggio',
      firstName: 'Nome',
      lastName: 'Cognome',
      email: 'Email',
      phone: 'Telefono',
      subject: 'Oggetto',
      message: 'Messaggio',
      send: 'Invia messaggio',
      formSuccess: 'Grazie per il vostro messaggio! Vi risponderemo il prima possibile.',

      placeholderFirstName: 'Il vostro nome',
      placeholderLastName: 'Il vostro cognome',
      placeholderEmail: 'vostro@email.com',
      placeholderPhone: '+33 6 XX XX XX XX',
      placeholderSubject: 'Oggetto del vostro messaggio',
      placeholderMessage: 'Il vostro messaggio...',

      accessSubtitle: 'Come arrivare',
      accessTitle: 'Accesso all\'hotel',
      byCar: 'In auto',
      byCarDesc: 'Da Bordeaux, prendete la tangenziale direzione Libourne/Parigi. Uscita Tresses/Artigues. Parcheggio gratuito in loco.',
      byTrain: 'In treno',
      byTrainDesc: 'Stazione Bordeaux Saint-Jean a 15 km. Taxi e VTC disponibili. Possiamo organizzare il vostro trasferimento su richiesta.',
      byPlane: 'In aereo',
      byPlaneDesc: 'Aeroporto di Bordeaux-Mérignac a 25 km. Navette e noleggio auto disponibili in aeroporto.',
      byBike: 'In bicicletta',
      byBikeDesc: 'Piste ciclabili da Bordeaux. Deposito bici sicuro disponibile per i nostri clienti ciclisti.',

      ctaTitle: 'Avete domande?',
      ctaText: 'Non esitate a contattarci, il nostro team è a vostra disposizione',
      callUs: 'Chiamateci'
    },

    footer: {
      description: 'Un\'oasi di pace alle porte di Bordeaux, dove fascino e autenticità vi attendono per un soggiorno indimenticabile.',
      navigation: 'Navigazione',
      services: 'Servizi',
      contactTitle: 'Contatti',
      copyright: '© 2024 {hotelName}. Tutti i diritti riservati.',
      // Footer links
      home: 'Home',
      discover: 'Da scoprire',
      restaurant: 'Ristorante',
      bar: 'Bar',
      roomService: 'Room Service',
      parking: 'Parcheggio'
    },

    common: {
      learnMore: 'Scopri di più',
      backToTop: 'Torna su'
    }
  }
};

// Make translations available globally
window.translations = translations;
