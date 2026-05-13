<?php
/**
 * Industry (nganh-hang) options used by the [nganh-hang] shortcode and the
 * Settings page industry dropdown.
 *
 * HOW TO ADD MORE:
 *   Option A — Edit this file directly:
 *       Add new lines in the array below. Format: 'slug' => 'Display Label'.
 *       Slug rules: lowercase letters, digits, hyphen or underscore only.
 *
 *   Option B — Extend from theme / another plugin via filter:
 *       add_filter( 'cmc_nganh_hang_options', function ( $opts ) {
 *           $opts['jewelry'] = 'Jewelry';
 *           return $opts;
 *       } );
 *
 * The first entry becomes the default when no value is stored in Settings yet.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return [
    // ==================== FASHION & CLOTHING ====================
    'fashion-apparel'                 => 'Fashion & Apparel',
    'mens-clothing'                   => 'Men\'s Clothing',
    'womens-clothing'                 => 'Women\'s Clothing',
    'kids-baby-clothing'              => 'Kids & Baby Clothing',
    'plus-size-fashion'               => 'Plus Size Fashion',
    'petite-fashion'                  => 'Petite Fashion',
    'tall-fashion'                    => 'Tall Fashion',
    'maternity-wear'                  => 'Maternity Wear',
    'vintage-retro-fashion'           => 'Vintage & Retro Fashion',
    'sustainable-fashion'             => 'Sustainable Fashion',
    'modest-fashion'                  => 'Modest Fashion',
    'streetwear'                      => 'Streetwear',
    'formal-wear'                     => 'Formal Wear',
    'casual-wear'                     => 'Casual Wear',
    'business-attire'                 => 'Business Attire',
    'workwear-uniforms'               => 'Workwear & Uniforms',
    'costumes-cosplay'                => 'Costumes & Cosplay',
    'traditional-cultural-clothing'   => 'Traditional & Cultural Clothing',

    // Footwear
    'shoes-footwear'                  => 'Shoes & Footwear',
    'athletic-shoes'                  => 'Athletic Shoes',
    'boots-booties'                   => 'Boots & Booties',
    'sandals-flip-flops'              => 'Sandals & Flip Flops',
    'heels-pumps'                     => 'Heels & Pumps',
    'flats-loafers'                   => 'Flats & Loafers',
    'sneakers-trainers'               => 'Sneakers & Trainers',
    'dress-shoes'                     => 'Dress Shoes',
    'work-safety-boots'               => 'Work & Safety Boots',
    'slippers-house-shoes'            => 'Slippers & House Shoes',
    'kids-shoes'                      => 'Kids Shoes',

    // Accessories
    'bags-luggage'                    => 'Bags & Luggage',
    'handbags-purses'                 => 'Handbags & Purses',
    'backpacks-daypacks'              => 'Backpacks & Daypacks',
    'travel-luggage'                  => 'Travel Luggage',
    'wallets-card-holders'            => 'Wallets & Card Holders',
    'laptop-bags-cases'               => 'Laptop Bags & Cases',
    'accessories-jewelry'             => 'Accessories & Jewelry',
    'watches-smartwatches'            => 'Watches & Smartwatches',
    'sunglasses-eyewear'              => 'Sunglasses & Eyewear',
    'hats-caps'                       => 'Hats & Caps',
    'scarves-wraps'                   => 'Scarves & Wraps',
    'belts-suspenders'                => 'Belts & Suspenders',
    'gloves-mittens'                  => 'Gloves & Mittens',
    'hair-accessories'                => 'Hair Accessories',
    'fashion-jewelry'                 => 'Fashion Jewelry',
    'fine-jewelry'                    => 'Fine Jewelry',
    'mens-accessories'                => 'Men\'s Accessories',

    // Undergarments
    'lingerie-sleepwear'              => 'Lingerie & Sleepwear',
    'bras-bralettes'                  => 'Bras & Bralettes',
    'underwear-panties'               => 'Underwear & Panties',
    'shapewear'                       => 'Shapewear',
    'robes-loungewear'                => 'Robes & Loungewear',
    'mens-underwear'                  => 'Men\'s Underwear',
    'socks-hosiery'                   => 'Socks & Hosiery',
    'swimwear-beachwear'              => 'Swimwear & Beachwear',

    // Sportswear
    'sportswear-activewear'           => 'Sportswear & Activewear',
    'yoga-wear'                       => 'Yoga Wear',
    'running-apparel'                 => 'Running Apparel',
    'gym-training-wear'               => 'Gym & Training Wear',
    'compression-wear'                => 'Compression Wear',
    'dance-ballet-wear'               => 'Dance & Ballet Wear',
    'ski-snowboard-apparel'           => 'Ski & Snowboard Apparel',

    // ==================== ELECTRONICS & TECHNOLOGY ====================
    'electronics-gadgets'             => 'Electronics & Gadgets',
    'computers-laptops'               => 'Computers & Laptops',
    'desktop-computers'               => 'Desktop Computers',
    'gaming-pcs'                      => 'Gaming PCs',
    'chromebooks'                    => 'Chromebooks',
    'tablets-ereaders'                => 'Tablets & E-Readers',
    'mobile-phones-smartphones'       => 'Mobile Phones & Smartphones',
    'mobile-accessories'              => 'Mobile Accessories',
    'phone-cases-covers'              => 'Phone Cases & Covers',
    'screen-protectors'               => 'Screen Protectors',
    'chargers-cables'                 => 'Chargers & Cables',
    'power-banks'                     => 'Power Banks',

    // Audio
    'audio-headphones'                => 'Audio & Headphones',
    'wireless-earbuds'                => 'Wireless Earbuds',
    'over-ear-headphones'             => 'Over-Ear Headphones',
    'bluetooth-speakers'              => 'Bluetooth Speakers',
    'home-audio-systems'              => 'Home Audio Systems',
    'soundbars'                       => 'Soundbars',
    'microphones'                     => 'Microphones',
    'dj-equipment'                    => 'DJ Equipment',
    'musical-keyboards'               => 'Musical Keyboards',

    // Visual
    'cameras-photography'             => 'Cameras & Photography',
    'dslr-cameras'                    => 'DSLR Cameras',
    'mirrorless-cameras'              => 'Mirrorless Cameras',
    'action-cameras'                  => 'Action Cameras',
    'drones-aerial'                   => 'Drones & Aerial',
    'camera-lenses'                   => 'Camera Lenses',
    'tripods-stabilizers'             => 'Tripods & Stabilizers',
    'camera-bags-cases'               => 'Camera Bags & Cases',
    'photo-printers'                  => 'Photo Printers',
    'binoculars-scopes'               => 'Binoculars & Scopes',

    // Gaming
    'gaming-consoles'                 => 'Gaming & Consoles',
    'video-game-consoles'             => 'Video Game Consoles',
    'gaming-accessories'              => 'Gaming Accessories',
    'gaming-chairs'                   => 'Gaming Chairs',
    'gaming-keyboards'                => 'Gaming Keyboards',
    'gaming-mice'                     => 'Gaming Mice',
    'gaming-headsets'                 => 'Gaming Headsets',
    'vr-ar-headsets'                  => 'VR & AR Headsets',
    'retro-gaming'                    => 'Retro Gaming',

    // Smart Tech
    'smart-home-devices'              => 'Smart Home Devices',
    'smart-speakers'                  => 'Smart Speakers',
    'smart-displays'                  => 'Smart Displays',
    'smart-lighting'                  => 'Smart Lighting',
    'smart-thermostats'               => 'Smart Thermostats',
    'smart-locks'                     => 'Smart Locks',
    'smart-plugs'                     => 'Smart Plugs',
    'home-security-systems'           => 'Home Security Systems',
    'video-doorbells'                 => 'Video Doorbells',
    'security-cameras'                => 'Security Cameras',
    'robot-vacuums'                   => 'Robot Vacuums',

    // TV & Display
    'tv-home-theater'                 => 'TV & Home Theater',
    'smart-tvs'                       => 'Smart TVs',
    'oled-qled-tvs'                   => 'OLED & QLED TVs',
    'projectors'                      => 'Projectors',
    'streaming-devices'               => 'Streaming Devices',
    'tv-mounts-stands'                => 'TV Mounts & Stands',

    // Wearables
    'wearable-technology'             => 'Wearable Technology',
    'fitness-trackers'                => 'Fitness Trackers',
    'smart-rings'                     => 'Smart Rings',
    'gps-watches'                     => 'GPS Watches',

    // Computer Accessories
    'computer-accessories'            => 'Computer Accessories',
    'monitors-displays'               => 'Monitors & Displays',
    'keyboards'                       => 'Keyboards',
    'mice-trackpads'                  => 'Mice & Trackpads',
    'webcams'                         => 'Webcams',
    'external-hard-drives'            => 'External Hard Drives',
    'usb-flash-drives'                => 'USB Flash Drives',
    'memory-cards'                    => 'Memory Cards',
    'printers-scanners'               => 'Printers & Scanners',
    'networking-equipment'            => 'Networking Equipment',
    'routers-modems'                  => 'Routers & Modems',

    // ==================== HOME & LIVING ====================
    'home-garden'                     => 'Home & Garden',
    'furniture-decor'                 => 'Furniture & Decor',
    'living-room-furniture'           => 'Living Room Furniture',
    'bedroom-furniture'               => 'Bedroom Furniture',
    'dining-room-furniture'           => 'Dining Room Furniture',
    'home-office-furniture'           => 'Home Office Furniture',
    'outdoor-furniture'               => 'Outdoor Furniture',
    'kids-furniture'                  => 'Kids Furniture',
    'storage-furniture'               => 'Storage Furniture',

    // Kitchen
    'kitchen-dining'                  => 'Kitchen & Dining',
    'cookware-bakeware'               => 'Cookware & Bakeware',
    'kitchen-appliances'              => 'Kitchen Appliances',
    'coffee-makers'                   => 'Coffee Makers',
    'blenders-juicers'                => 'Blenders & Juicers',
    'air-fryers'                      => 'Air Fryers',
    'instant-pots-pressure-cookers'   => 'Instant Pots & Pressure Cookers',
    'toasters-ovens'                  => 'Toasters & Ovens',
    'dinnerware-serveware'            => 'Dinnerware & Serveware',
    'flatware-cutlery'                => 'Flatware & Cutlery',
    'glassware-drinkware'             => 'Glassware & Drinkware',
    'food-storage'                    => 'Food Storage',
    'kitchen-gadgets-tools'           => 'Kitchen Gadgets & Tools',
    'bar-accessories'                 => 'Bar Accessories',

    // Bedroom & Bath
    'bedding-bath'                    => 'Bedding & Bath',
    'sheets-pillowcases'              => 'Sheets & Pillowcases',
    'comforters-duvets'               => 'Comforters & Duvets',
    'blankets-throws'                 => 'Blankets & Throws',
    'pillows'                         => 'Pillows',
    'mattresses'                      => 'Mattresses',
    'mattress-toppers'                => 'Mattress Toppers',
    'towels-washcloths'               => 'Towels & Washcloths',
    'shower-curtains'                 => 'Shower Curtains',
    'bathroom-accessories'            => 'Bathroom Accessories',
    'bath-mats-rugs'                  => 'Bath Mats & Rugs',

    // Decor
    'home-decor'                      => 'Home Decor',
    'wall-art-prints'                 => 'Wall Art & Prints',
    'picture-frames'                  => 'Picture Frames',
    'mirrors'                         => 'Mirrors',
    'clocks'                          => 'Clocks',
    'vases-decorative-bowls'          => 'Vases & Decorative Bowls',
    'candles-holders'                 => 'Candles & Holders',
    'artificial-plants-flowers'       => 'Artificial Plants & Flowers',
    'rugs-carpets'                    => 'Rugs & Carpets',
    'curtains-drapes'                 => 'Curtains & Drapes',
    'throw-pillows-covers'            => 'Throw Pillows & Covers',

    // Lighting
    'lighting-ceiling-fans'           => 'Lighting & Ceiling Fans',
    'table-lamps'                     => 'Table Lamps',
    'floor-lamps'                     => 'Floor Lamps',
    'ceiling-lights'                  => 'Ceiling Lights',
    'pendant-lights'                  => 'Pendant Lights',
    'chandeliers'                     => 'Chandeliers',
    'led-strip-lights'                => 'LED Strip Lights',
    'outdoor-lighting'                => 'Outdoor Lighting',
    'smart-bulbs'                     => 'Smart Bulbs',

    // Organization
    'storage-organization'            => 'Storage & Organization',
    'closet-organization'             => 'Closet Organization',
    'garage-storage'                  => 'Garage Storage',
    'laundry-organization'            => 'Laundry Organization',
    'shelving-units'                  => 'Shelving Units',
    'storage-bins-baskets'            => 'Storage Bins & Baskets',

    // Home Improvement
    'home-improvement'                => 'Home Improvement',
    'paint-wall-treatments'           => 'Paint & Wall Treatments',
    'flooring'                        => 'Flooring',
    'door-hardware'                   => 'Door Hardware',
    'cabinet-hardware'                => 'Cabinet Hardware',
    'plumbing-fixtures'               => 'Plumbing Fixtures',
    'electrical-supplies'             => 'Electrical Supplies',

    // Tools
    'tools-hardware'                  => 'Tools & Hardware',
    'power-tools'                     => 'Power Tools',
    'hand-tools'                      => 'Hand Tools',
    'tool-storage'                    => 'Tool Storage',
    'measuring-tools'                 => 'Measuring Tools',
    'welding-equipment'               => 'Welding Equipment',

    // Appliances
    'appliances'                      => 'Appliances',
    'refrigerators'                   => 'Refrigerators',
    'washers-dryers'                  => 'Washers & Dryers',
    'dishwashers'                     => 'Dishwashers',
    'vacuum-cleaners'                 => 'Vacuum Cleaners',
    'air-purifiers'                   => 'Air Purifiers',
    'humidifiers-dehumidifiers'       => 'Humidifiers & Dehumidifiers',
    'fans-air-conditioners'           => 'Fans & Air Conditioners',
    'heaters'                         => 'Heaters',
    'irons-steamers'                  => 'Irons & Steamers',

    // ==================== HEALTH & BEAUTY ====================
    'health-wellness'                 => 'Health & Wellness',
    'beauty-cosmetics'                => 'Beauty & Cosmetics',
    'makeup-cosmetics'                => 'Makeup & Cosmetics',
    'face-makeup'                     => 'Face Makeup',
    'eye-makeup'                      => 'Eye Makeup',
    'lip-products'                    => 'Lip Products',
    'makeup-brushes-tools'            => 'Makeup Brushes & Tools',
    'makeup-bags-cases'               => 'Makeup Bags & Cases',

    // Skincare
    'skincare-haircare'               => 'Skincare & Haircare',
    'facial-cleansers'                => 'Facial Cleansers',
    'moisturizers-creams'             => 'Moisturizers & Creams',
    'serums-treatments'               => 'Serums & Treatments',
    'sunscreen-sun-care'              => 'Sunscreen & Sun Care',
    'anti-aging-products'             => 'Anti-Aging Products',
    'acne-treatments'                 => 'Acne Treatments',
    'face-masks-peels'                => 'Face Masks & Peels',
    'eye-creams'                      => 'Eye Creams',
    'body-lotions-creams'             => 'Body Lotions & Creams',

    // Haircare
    'shampoo-conditioner'             => 'Shampoo & Conditioner',
    'hair-styling-products'           => 'Hair Styling Products',
    'hair-color-dye'                  => 'Hair Color & Dye',
    'hair-tools-appliances'           => 'Hair Tools & Appliances',
    'hair-dryers'                     => 'Hair Dryers',
    'flat-irons-curling-irons'        => 'Flat Irons & Curling Irons',
    'hair-brushes-combs'              => 'Hair Brushes & Combs',
    'hair-extensions-wigs'            => 'Hair Extensions & Wigs',

    // Fragrance
    'fragrances-perfumes'             => 'Fragrances & Perfumes',
    'womens-perfume'                  => 'Women\'s Perfume',
    'mens-cologne'                    => 'Men\'s Cologne',
    'unisex-fragrances'               => 'Unisex Fragrances',
    'body-sprays-mists'               => 'Body Sprays & Mists',

    // Personal Care
    'personal-care'                   => 'Personal Care',
    'oral-care'                       => 'Oral Care',
    'toothbrushes-toothpaste'         => 'Toothbrushes & Toothpaste',
    'electric-toothbrushes'           => 'Electric Toothbrushes',
    'mouthwash-breath-fresheners'     => 'Mouthwash & Breath Fresheners',
    'teeth-whitening'                 => 'Teeth Whitening',
    'deodorants-antiperspirants'      => 'Deodorants & Antiperspirants',
    'shaving-grooming'                => 'Shaving & Grooming',
    'mens-razors'                     => 'Men\'s Razors',
    'womens-razors'                   => 'Women\'s Razors',
    'electric-shavers'                => 'Electric Shavers',
    'beard-care'                      => 'Beard Care',
    'feminine-care'                   => 'Feminine Care',
    'nail-care'                       => 'Nail Care',
    'nail-polish'                     => 'Nail Polish',
    'nail-tools'                      => 'Nail Tools',

    // Health
    'vitamins-supplements'            => 'Vitamins & Supplements',
    'multivitamins'                   => 'Multivitamins',
    'protein-supplements'             => 'Protein Supplements',
    'weight-management'               => 'Weight Management',
    'sports-nutrition'                => 'Sports Nutrition',
    'herbal-supplements'              => 'Herbal Supplements',
    'probiotics'                      => 'Probiotics',

    // Medical
    'medical-supplies'                => 'Medical Supplies',
    'first-aid-supplies'              => 'First Aid Supplies',
    'blood-pressure-monitors'         => 'Blood Pressure Monitors',
    'thermometers'                    => 'Thermometers',
    'mobility-aids'                   => 'Mobility Aids',
    'braces-supports'                 => 'Braces & Supports',
    'cpap-supplies'                   => 'CPAP Supplies',
    'diabetes-care'                   => 'Diabetes Care',

    // Wellness
    'massage-relaxation'              => 'Massage & Relaxation',
    'massage-chairs'                  => 'Massage Chairs',
    'massage-guns'                    => 'Massage Guns',
    'essential-oils-aromatherapy'     => 'Essential Oils & Aromatherapy',
    'diffusers'                       => 'Diffusers',
    'meditation-mindfulness'          => 'Meditation & Mindfulness',
    'sleep-aids'                      => 'Sleep Aids',

    // ==================== SPORTS & OUTDOORS ====================
    'sports-outdoors'                 => 'Sports & Outdoors',
    'camping-hiking'                  => 'Camping & Hiking',
    'tents-shelters'                  => 'Tents & Shelters',
    'sleeping-bags-pads'              => 'Sleeping Bags & Pads',
    'camping-furniture'               => 'Camping Furniture',
    'camping-cookware'                => 'Camping Cookware',
    'backpacking-gear'                => 'Backpacking Gear',
    'hiking-boots-shoes'              => 'Hiking Boots & Shoes',
    'trekking-poles'                  => 'Trekking Poles',
    'gps-navigation'                  => 'GPS & Navigation',

    // Cycling
    'cycling-biking'                  => 'Cycling & Biking',
    'bicycles'                        => 'Bicycles',
    'ebikes'                          => 'E-Bikes',
    'bike-accessories'                => 'Bike Accessories',
    'bike-helmets'                    => 'Bike Helmets',
    'cycling-apparel'                 => 'Cycling Apparel',
    'bike-lights'                     => 'Bike Lights',
    'bike-locks'                      => 'Bike Locks',
    'bike-racks-storage'              => 'Bike Racks & Storage',

    // Fishing & Hunting
    'fishing-hunting'                 => 'Fishing & Hunting',
    'fishing-rods-reels'              => 'Fishing Rods & Reels',
    'fishing-tackle'                  => 'Fishing Tackle',
    'fishing-electronics'             => 'Fishing Electronics',
    'hunting-gear'                    => 'Hunting Gear',
    'archery'                         => 'Archery',
    'shooting-gun-accessories'        => 'Shooting & Gun Accessories',

    // Golf
    'golf-equipment'                  => 'Golf Equipment',
    'golf-clubs'                      => 'Golf Clubs',
    'golf-bags'                       => 'Golf Bags',
    'golf-balls'                      => 'Golf Balls',
    'golf-apparel'                    => 'Golf Apparel',
    'golf-shoes'                      => 'Golf Shoes',
    'golf-carts-pushcarts'            => 'Golf Carts & Pushcarts',

    // Team Sports
    'team-sports'                     => 'Team Sports',
    'basketball'                      => 'Basketball',
    'football'                        => 'Football',
    'soccer'                          => 'Soccer',
    'baseball-softball'               => 'Baseball & Softball',
    'volleyball'                      => 'Volleyball',
    'hockey'                          => 'Hockey',
    'tennis-racquet-sports'           => 'Tennis & Racquet Sports',
    'lacrosse'                        => 'Lacrosse',
    'rugby'                           => 'Rugby',
    'cricket'                         => 'Cricket',

    // Water Sports
    'water-sports'                    => 'Water Sports',
    'swimming'                        => 'Swimming',
    'surfing'                         => 'Surfing',
    'paddleboarding'                  => 'Paddleboarding',
    'kayaking-canoeing'               => 'Kayaking & Canoeing',
    'scuba-diving'                    => 'Scuba Diving',
    'snorkeling'                      => 'Snorkeling',
    'water-skiing-wakeboarding'       => 'Water Skiing & Wakeboarding',
    'boating-accessories'             => 'Boating Accessories',

    // Winter Sports
    'winter-sports'                   => 'Winter Sports',
    'skiing'                          => 'Skiing',
    'snowboarding'                    => 'Snowboarding',
    'ice-skating'                     => 'Ice Skating',
    'snowshoeing'                     => 'Snowshoeing',
    'sleds-snow-tubes'                => 'Sleds & Snow Tubes',

    // Fitness
    'fitness-gym'                     => 'Fitness & Gym',
    'exercise-fitness-equipment'      => 'Exercise & Fitness Equipment',
    'treadmills'                      => 'Treadmills',
    'exercise-bikes'                  => 'Exercise Bikes',
    'ellipticals'                     => 'Ellipticals',
    'rowing machines'                 => 'Rowing Machines',
    'weight-training'                 => 'Weight Training',
    'dumbbells-weights'               => 'Dumbbells & Weights',
    'resistance-bands'                => 'Resistance Bands',
    'yoga-pilates'                    => 'Yoga & Pilates',
    'yoga-mats'                       => 'Yoga Mats',
    'yoga-blocks-props'               => 'Yoga Blocks & Props',
    'foam-rollers'                    => 'Foam Rollers',
    'jump-ropes'                      => 'Jump Ropes',
    'punching-bags'                   => 'Punching Bags',
    'boxing-mma'                      => 'Boxing & MMA',

    // ==================== TOYS & ENTERTAINMENT ====================
    'toys-games'                      => 'Toys & Games',
    'board-games-puzzles'             => 'Board Games & Puzzles',
    'card-games'                      => 'Card Games',
    'strategy-games'                  => 'Strategy Games',
    'jigsaw-puzzles'                  => 'Jigsaw Puzzles',
    'brain-teasers'                   => 'Brain Teasers',

    // Action & Collectibles
    'action-figures-collectibles'     => 'Action Figures & Collectibles',
    'dolls-accessories'               => 'Dolls & Accessories',
    'building-sets-blocks'            => 'Building Sets & Blocks',
    'lego-sets'                       => 'LEGO Sets',
    'model-kits'                      => 'Model Kits',
    'diecast-vehicles'                => 'Die-Cast Vehicles',
    'funko-pop-figures'               => 'Funko Pop Figures',

    // Educational
    'educational-toys'                => 'Educational Toys',
    'stem-toys'                       => 'STEM Toys',
    'learning-development'            => 'Learning & Development',
    'science-kits'                    => 'Science Kits',
    'coding-toys'                     => 'Coding Toys',

    // Outdoor Play
    'outdoor-play'                    => 'Outdoor Play',
    'playgrounds-play-sets'           => 'Playgrounds & Play Sets',
    'trampolines'                     => 'Trampolines',
    'water-toys'                      => 'Water Toys',
    'rideon-toys'                     => 'Ride-On Toys',
    'scooters-skateboards'            => 'Scooters & Skateboards',
    'sports-toys'                     => 'Sports Toys',

    // Creative
    'arts-crafts'                     => 'Arts & Crafts',
    'drawing-coloring'                => 'Drawing & Coloring',
    'craft-kits'                      => 'Craft Kits',
    'slime-putty'                     => 'Slime & Putty',
    'painting-sets'                   => 'Painting Sets',

    // Music
    'musical-instruments'             => 'Musical Instruments',
    'guitars-basses'                  => 'Guitars & Basses',
    'drums-percussion'                => 'Drums & Percussion',
    'pianos-keyboards'                => 'Pianos & Keyboards',
    'wind-instruments'                => 'Wind Instruments',
    'string-instruments'              => 'String Instruments',
    'band-orchestra'                  => 'Band & Orchestra',
    'music-accessories'               => 'Music Accessories',

    // Party & Events
    'party-supplies'                  => 'Party Supplies',
    'birthday-party-supplies'         => 'Birthday Party Supplies',
    'holiday-decorations'             => 'Holiday Decorations',
    'balloons'                        => 'Balloons',
    'party-favors'                    => 'Party Favors',
    'costumes-dress-up'               => 'Costumes & Dress Up',

    // Hobbies
    'hobby-models'                    => 'Hobby & Models',
    'rc-cars-trucks'                  => 'RC Cars & Trucks',
    'rc-planes-helicopters'           => 'RC Planes & Helicopters',
    'model-trains'                    => 'Model Trains',
    'coin-collecting'                 => 'Coin Collecting',
    'stamp-collecting'                => 'Stamp Collecting',

    // Video Games
    'video-games'                     => 'Video Games',
    'playstation-games'               => 'PlayStation Games',
    'xbox-games'                      => 'Xbox Games',
    'nintendo-games'                  => 'Nintendo Games',
    'pc-games'                        => 'PC Games',
    'mobile-games'                    => 'Mobile Games',

    // ==================== AUTOMOTIVE ====================
    'automotive-vehicles'             => 'Automotive & Vehicles',
    'car-parts-accessories'           => 'Car Parts & Accessories',
    'replacement-parts'               => 'Replacement Parts',
    'performance-parts'               => 'Performance Parts',
    'car-interior-accessories'        => 'Car Interior Accessories',
    'car-exterior-accessories'        => 'Car Exterior Accessories',
    'car-covers'                      => 'Car Covers',
    'floor-mats-liners'               => 'Floor Mats & Liners',
    'seat-covers'                     => 'Seat Covers',
    'steering-wheel-covers'           => 'Steering Wheel Covers',
    'car-organizers'                  => 'Car Organizers',

    // Electronics
    'car-electronics'                 => 'Car Electronics',
    'car-audio'                       => 'Car Audio',
    'car-speakers'                    => 'Car Speakers',
    'car-amplifiers'                  => 'Car Amplifiers',
    'car-gps-navigation'              => 'GPS Navigation',
    'dash-cams'                       => 'Dash Cams',
    'backup-cameras'                  => 'Backup Cameras',
    'car-phone-mounts'                => 'Car Phone Mounts',

    // Tires & Wheels
    'tires-wheels'                    => 'Tires & Wheels',
    'car-tires'                       => 'Car Tires',
    'truck-tires'                     => 'Truck Tires',
    'wheel-rims'                      => 'Wheel Rims',
    'tire-pressure-monitors'          => 'Tire Pressure Monitors',

    // Maintenance
    'car-care-maintenance'            => 'Car Care & Maintenance',
    'car-wash-wax'                    => 'Car Wash & Wax',
    'car-cleaning'                    => 'Car Cleaning',
    'motor-oil'                       => 'Motor Oil',
    'car-batteries'                   => 'Car Batteries',
    'automotive-tools'                => 'Automotive Tools',
    'jump-starters'                   => 'Jump Starters',

    // Motorcycle
    'motorcycle-parts'                => 'Motorcycle Parts',
    'motorcycle-accessories'          => 'Motorcycle Accessories',
    'motorcycle-helmets'              => 'Motorcycle Helmets',
    'motorcycle-gear-apparel'         => 'Motorcycle Gear & Apparel',

    // Other Vehicles
    'rv-camping'                      => 'RV & Camping',
    'rv-parts-accessories'            => 'RV Parts & Accessories',
    'boat-marine'                     => 'Boat & Marine',
    'boat-parts'                      => 'Boat Parts',
    'marine-electronics'              => 'Marine Electronics',
    'atv-powersports'                 => 'ATV & Powersports',
    'atv-parts-accessories'           => 'ATV Parts & Accessories',
    'snowmobile-parts'                => 'Snowmobile Parts',

    // ==================== PET SUPPLIES ====================
    'pet-supplies'                    => 'Pet Supplies',
    'dog-supplies'                    => 'Dog Supplies',
    'dog-food'                        => 'Dog Food',
    'dog-treats'                      => 'Dog Treats',
    'dog-toys'                        => 'Dog Toys',
    'dog-beds-furniture'              => 'Dog Beds & Furniture',
    'dog-collars-leashes'             => 'Dog Collars & Leashes',
    'dog-harnesses'                   => 'Dog Harnesses',
    'dog-clothing'                    => 'Dog Clothing',
    'dog-crates-kennels'              => 'Dog Crates & Kennels',
    'dog-grooming'                    => 'Dog Grooming',
    'dog-training'                    => 'Dog Training',

    // Cat
    'cat-supplies'                    => 'Cat Supplies',
    'cat-food'                        => 'Cat Food',
    'cat-treats'                      => 'Cat Treats',
    'cat-toys'                        => 'Cat Toys',
    'cat-beds-furniture'              => 'Cat Beds & Furniture',
    'cat-trees-scratchers'            => 'Cat Trees & Scratchers',
    'cat-litter-boxes'                => 'Cat Litter & Boxes',
    'cat-carriers'                    => 'Cat Carriers',

    // Other Pets
    'fish-aquarium'                   => 'Fish & Aquarium',
    'aquarium-tanks'                  => 'Aquarium Tanks',
    'aquarium-filters'                => 'Aquarium Filters',
    'fish-food'                       => 'Fish Food',
    'aquarium-decorations'            => 'Aquarium Decorations',
    'bird-supplies'                   => 'Bird Supplies',
    'bird-cages'                      => 'Bird Cages',
    'bird-food'                       => 'Bird Food',
    'bird-toys'                       => 'Bird Toys',
    'small-animal-supplies'           => 'Small Animal Supplies',
    'hamster-guinea-pig'              => 'Hamster & Guinea Pig',
    'rabbit-supplies'                 => 'Rabbit Supplies',
    'reptile-supplies'                => 'Reptile Supplies',

    // Pet Health
    'pet-food-treats'                 => 'Pet Food & Treats',
    'pet-health-wellness'             => 'Pet Health & Wellness',
    'pet-supplements'                 => 'Pet Supplements',
    'flea-tick-prevention'            => 'Flea & Tick Prevention',
    'pet-grooming-health'             => 'Pet Grooming',
    'pet-shampoo'                     => 'Pet Shampoo',
    'pet-brushes'                     => 'Pet Brushes',
    'pet-toys-accessories'            => 'Pet Toys & Accessories',

    // ==================== FOOD & GROCERIES ====================
    'food-beverages'                  => 'Food & Beverages',
    'groceries-gourmet'               => 'Groceries & Gourmet',
    'pantry-staples'                  => 'Pantry Staples',
    'cooking-baking'                  => 'Cooking & Baking',
    'spices-seasonings'               => 'Spices & Seasonings',
    'condiments-sauces'               => 'Condiments & Sauces',
    'pasta-grains'                    => 'Pasta & Grains',
    'canned-jarred-foods'             => 'Canned & Jarred Foods',

    // Specialty Foods
    'organic-natural-foods'           => 'Organic & Natural Foods',
    'gluten-free-foods'               => 'Gluten-Free Foods',
    'vegan-plant-based'               => 'Vegan & Plant-Based',
    'keto-low-carb'                   => 'Keto & Low Carb',
    'paleo-diet'                      => 'Paleo Diet',
    'kosher-foods'                    => 'Kosher Foods',
    'halal-foods'                     => 'Halal Foods',

    // Snacks
    'snacks-candy'                    => 'Snacks & Candy',
    'chips-crisps'                    => 'Chips & Crisps',
    'nuts-seeds'                      => 'Nuts & Seeds',
    'dried-fruits'                    => 'Dried Fruits',
    'chocolate-candy'                 => 'Chocolate & Candy',
    'cookies-crackers'                => 'Cookies & Crackers',
    'popcorn-pretzels'                => 'Popcorn & Pretzels',
    'protein-bars'                    => 'Protein Bars',
    'fruit-snacks'                    => 'Fruit Snacks',

    // Beverages
    'coffee-tea'                      => 'Coffee & Tea',
    'ground-coffee'                   => 'Ground Coffee',
    'coffee-beans'                    => 'Coffee Beans',
    'coffee-pods-capsules'            => 'Coffee Pods & Capsules',
    'tea-bags'                        => 'Tea Bags',
    'loose-leaf-tea'                  => 'Loose Leaf Tea',
    'energy-drinks'                   => 'Energy Drinks',
    'sports-drinks'                   => 'Sports Drinks',
    'soft-drinks-sodas'               => 'Soft Drinks & Sodas',
    'juice-smoothies'                 => 'Juice & Smoothies',
    'bottled-water'                   => 'Bottled Water',

    // Alcohol
    'wine-spirits'                    => 'Wine & Spirits',
    'red-wine'                        => 'Red Wine',
    'white-wine'                      => 'White Wine',
    'champagne-sparkling'             => 'Champagne & Sparkling',
    'beer-cider'                      => 'Beer & Cider',
    'whiskey-bourbon'                 => 'Whiskey & Bourbon',
    'vodka'                           => 'Vodka',
    'rum'                             => 'Rum',
    'tequila'                         => 'Tequila',
    'gin'                             => 'Gin',

    // Fresh & Specialty
    'health-foods'                    => 'Health Foods',
    'superfoods'                      => 'Superfoods',
    'international-foods'             => 'International Foods',
    'asian-foods'                     => 'Asian Foods',
    'latin-foods'                     => 'Latin Foods',
    'european-foods'                  => 'European Foods',
    'bakery-desserts'                 => 'Bakery & Desserts',
    'bread-baked-goods'               => 'Bread & Baked Goods',
    'cakes-pastries'                  => 'Cakes & Pastries',
    'ice-cream-frozen-desserts'       => 'Ice Cream & Frozen Desserts',
    'meal-kits-delivery'              => 'Meal Kits & Delivery',
    'fresh-produce'                   => 'Fresh Produce',
    'meat-seafood'                    => 'Meat & Seafood',
    'dairy-eggs'                      => 'Dairy & Eggs',
    'cheese'                          => 'Cheese',
    'deli-prepared-foods'             => 'Deli & Prepared Foods',

    // ==================== BABY & KIDS ====================
    'baby-products'                   => 'Baby Products',
    'baby-gear-furniture'             => 'Baby Gear & Furniture',
    'strollers'                       => 'Strollers',
    'car-seats'                       => 'Car Seats',
    'baby-carriers'                   => 'Baby Carriers',
    'high-chairs'                     => 'High Chairs',
    'cribs-bassinets'                 => 'Cribs & Bassinets',
    'changing-tables'                 => 'Changing Tables',
    'baby-swings-bouncers'            => 'Baby Swings & Bouncers',
    'playpens-play-yards'             => 'Playpens & Play Yards',

    // Diapering
    'diapers-potty'                   => 'Diapers & Potty',
    'disposable-diapers'              => 'Disposable Diapers',
    'cloth-diapers'                   => 'Cloth Diapers',
    'wipes'                           => 'Wipes',
    'diaper-bags'                     => 'Diaper Bags',
    'potty-training'                  => 'Potty Training',

    // Feeding
    'baby-feeding'                    => 'Baby Feeding',
    'baby-bottles'                    => 'Baby Bottles',
    'breast-pumps'                    => 'Breast Pumps',
    'baby-formula'                    => 'Baby Formula',
    'baby-food'                       => 'Baby Food',
    'sippy-cups'                      => 'Sippy Cups',
    'bibs-burp-cloths'                => 'Bibs & Burp Cloths',

    // Safety & Health
    'baby-safety'                     => 'Baby Safety',
    'baby-monitors'                   => 'Baby Monitors',
    'baby-gates'                      => 'Baby Gates',
    'cabinet-locks'                   => 'Cabinet Locks',
    'baby-health-safety'              => 'Baby Health',
    'baby-thermometers'               => 'Baby Thermometers',

    // Nursery
    'nursery-decor'                   => 'Nursery Decor',
    'nursery-bedding'                 => 'Nursery Bedding',
    'nursery-furniture'               => 'Nursery Furniture',
    'nursery-lighting'                => 'Nursery Lighting',

    // Maternity
    'maternity'                       => 'Maternity',
    'maternity-clothing'              => 'Maternity Clothing',
    'nursing-breastfeeding'           => 'Nursing & Breastfeeding',
    'pregnancy-pillows'               => 'Pregnancy Pillows',

    // Kids
    'kids-room'                       => 'Kids Room',
    'kids-bedding'                    => 'Kids Bedding',
    'kids-furniture-room'             => 'Kids Furniture',
    'kids-storage'                    => 'Kids Storage',
    'baby-clothing-kids'              => 'Baby Clothing',
    'newborn-clothing'                => 'Newborn Clothing',
    'toddler-clothing'                => 'Toddler Clothing',
    'baby-toys-kids'                  => 'Baby Toys',
    'baby-rattles'                    => 'Baby Rattles',
    'teethers'                        => 'Teethers',
    'soft-toys'                       => 'Soft Toys',

    // ==================== OFFICE & BUSINESS ====================
    'office-supplies'                 => 'Office Supplies',
    'office-furniture'                => 'Office Furniture',
    'desks'                           => 'Desks',
    'office-chairs'                   => 'Office Chairs',
    'filing-cabinets'                 => 'Filing Cabinets',
    'bookcases-shelving'              => 'Bookcases & Shelving',
    'conference-tables'               => 'Conference Tables',

    // Electronics
    'office-electronics'              => 'Office Electronics',
    'calculators'                     => 'Calculators',
    'label-makers'                    => 'Label Makers',
    'paper-shredders'                 => 'Paper Shredders',
    'laminators'                      => 'Laminators',
    'binding-machines'                => 'Binding Machines',

    // Writing & Paper
    'stationery-writing'              => 'Stationery & Writing',
    'pens-pencils'                    => 'Pens & Pencils',
    'markers-highlighters'            => 'Markers & Highlighters',
    'notebooks-notepads'              => 'Notebooks & Notepads',
    'sticky-notes'                    => 'Sticky Notes',
    'paper-pads'                      => 'Paper & Pads',
    'envelopes'                       => 'Envelopes',
    'business-cards'                  => 'Business Cards',

    // Presentation
    'printing-presentation'           => 'Printing & Presentation',
    'printer-paper'                   => 'Printer Paper',
    'printer-ink-toner'               => 'Printer Ink & Toner',
    'presentation-boards'             => 'Presentation Boards',
    'easels-stands'                   => 'Easels & Stands',

    // Organization
    'office-storage'                  => 'Office Storage',
    'desk-organizers'                 => 'Desk Organizers',
    'file-folders'                    => 'File Folders',
    'binders-accessories'             => 'Binders & Accessories',
    'staplers-punches'                => 'Staplers & Punches',
    'tape-adhesives'                  => 'Tape & Adhesives',
    'scissors-cutters'                => 'Scissors & Cutters',

    // Business
    'business-equipment'              => 'Business Equipment',
    'cash-registers'                  => 'Cash Registers',
    'pos-systems'                     => 'POS Systems',
    'shipping-packaging'              => 'Shipping & Packaging',
    'shipping-boxes'                  => 'Shipping Boxes',
    'packing-materials'               => 'Packing Materials',
    'shipping-labels'                 => 'Shipping Labels',
    'break-room-supplies'             => 'Break Room Supplies',
    'coffee-beverages-office'         => 'Coffee & Beverages',
    'snacks-food-office'              => 'Snacks & Food',
    'kitchen-supplies-office'         => 'Kitchen Supplies',
    'educational-supplies-office'     => 'Educational Supplies',
    'classroom-supplies'              => 'Classroom Supplies',
    'teaching-resources'              => 'Teaching Resources',

    // ==================== BOOKS & MEDIA ====================
    'books-literature'                => 'Books & Literature',
    'fiction-books'                   => 'Fiction Books',
    'nonfiction-books'                => 'Non-Fiction Books',
    'childrens-books'                 => 'Children\'s Books',
    'young-adult-books'               => 'Young Adult Books',
    'selfhelp-books'                  => 'Self-Help Books',
    'business-books'                  => 'Business Books',
    'cookbooks'                       => 'Cookbooks',
    'art-books'                       => 'Art Books',
    'travel-books'                    => 'Travel Books',
    'religion-spirituality'           => 'Religion & Spirituality',
    'ebooks-audiobooks'               => 'E-Books & Audiobooks',
    'kindle-books'                    => 'Kindle Books',
    'audible-audiobooks'              => 'Audible Audiobooks',

    // Magazines
    'magazines-newspapers'            => 'Magazines & Newspapers',
    'fashion-magazines'               => 'Fashion Magazines',
    'tech-magazines'                  => 'Tech Magazines',
    'sports-magazines'                => 'Sports Magazines',
    'news-magazines'                  => 'News Magazines',

    // Entertainment Media
    'movies-tv-shows'                 => 'Movies & TV Shows',
    'bluray-dvd'                      => 'Blu-ray & DVD',
    'streaming-services'              => 'Streaming Services',
    'music-vinyl'                     => 'Music & Vinyl',
    'vinyl-records'                   => 'Vinyl Records',
    'cds'                             => 'CDs',
    'digital-music'                   => 'Digital Music',

    // Software
    'software-apps'                   => 'Software & Apps',
    'operating-systems'               => 'Operating Systems',
    'office-software'                 => 'Office Software',
    'security-software'               => 'Security Software',
    'design-software'                 => 'Design Software',
    'education-software'              => 'Education Software',

    // Educational
    'textbooks'                       => 'Textbooks',
    'college-textbooks'               => 'College Textbooks',
    'k12-textbooks'                   => 'K-12 Textbooks',
    'comics-graphic-novels'           => 'Comics & Graphic Novels',
    'manga'                           => 'Manga',
    'superhero-comics'                => 'Superhero Comics',
    'calendars-planners'              => 'Calendars & Planners',
    'wall-calendars'                  => 'Wall Calendars',
    'desk-calendars'                  => 'Desk Calendars',
    'planners-organizers'             => 'Planners & Organizers',
    'educational-media'               => 'Educational Media',
    'language-learning'               => 'Language Learning',
    'test-prep'                       => 'Test Prep',

    // ==================== ARTS & CRAFTS ====================
    'arts-crafts-supplies'            => 'Arts & Crafts Supplies',
    'sewing-knitting'                 => 'Sewing & Knitting',
    'sewing-machines'                 => 'Sewing Machines',
    'sewing-notions'                  => 'Sewing Notions',
    'yarn-knitting-needles'           => 'Yarn & Knitting Needles',
    'crochet-supplies'                => 'Crochet Supplies',
    'quilting'                        => 'Quilting',

    // Art Supplies
    'painting-drawing'                => 'Painting & Drawing',
    'paints-mediums'                  => 'Paints & Mediums',
    'oil-paints'                      => 'Oil Paints',
    'acrylic-paints'                  => 'Acrylic Paints',
    'watercolors'                     => 'Watercolors',
    'paint-brushes'                   => 'Paint Brushes',
    'canvas-surfaces'                 => 'Canvas & Surfaces',
    'easels-art'                      => 'Easels',
    'colored-pencils'                 => 'Colored Pencils',
    'pastels-charcoal'                => 'Pastels & Charcoal',
    'sketch-pads'                     => 'Sketch Pads',

    // Paper Crafts
    'scrapbooking'                    => 'Scrapbooking',
    'scrapbook-albums'                => 'Scrapbook Albums',
    'decorative-paper'                => 'Decorative Paper',
    'stickers-embellishments'         => 'Stickers & Embellishments',
    'die-cutting'                     => 'Die Cutting',
    'stamping-embossing'              => 'Stamping & Embossing',
    'card-making'                     => 'Card Making',

    // Jewelry Making
    'beading-jewelry-making'          => 'Beading & Jewelry Making',
    'beads-findings'                  => 'Beads & Findings',
    'jewelry-tools'                   => 'Jewelry Tools',
    'wire-chain'                      => 'Wire & Chain',
    'jewelry-kits'                    => 'Jewelry Kits',

    // Other Crafts
    'printmaking'                     => 'Printmaking',
    'screen-printing'                 => 'Screen Printing',
    'block-printing'                  => 'Block Printing',
    'fabric-textile'                  => 'Fabric & Textile',
    'fabric'                          => 'Fabric',
    'dyes-fabric-paint'               => 'Dyes & Fabric Paint',
    'embroidery'                      => 'Embroidery',
    'woodworking'                     => 'Woodworking',
    'wood-carving'                    => 'Wood Carving',
    'woodburning'                     => 'Woodburning',
    'pottery-ceramics'                => 'Pottery & Ceramics',
    'clay-sculpting'                  => 'Clay & Sculpting',
    'pottery-tools'                   => 'Pottery Tools',
    'kilns-wheels'                    => 'Kilns & Wheels',
    'candle-soap-making'              => 'Candle & Soap Making',
    'candle-wax-wicks'                => 'Candle Wax & Wicks',
    'soap-making-supplies'            => 'Soap Making Supplies',
    'resin-art'                       => 'Resin Art',
    'leathercraft'                    => 'Leathercraft',
    'glass-art'                       => 'Glass Art',

    // ==================== INDUSTRIAL & SCIENTIFIC ====================
    'industrial-equipment'            => 'Industrial Equipment',
    'manufacturing-equipment'         => 'Manufacturing Equipment',
    'cnc-machines'                    => 'CNC Machines',
    '3d-printers'                     => '3D Printers',
    'industrial-machinery'            => 'Industrial Machinery',

    // Lab & Science
    'lab-scientific'                  => 'Lab & Scientific',
    'lab-equipment'                   => 'Lab Equipment',
    'microscopes'                     => 'Microscopes',
    'lab-chemicals'                   => 'Lab Chemicals',
    'lab-glassware'                   => 'Lab Glassware',
    'safety-equipment'                => 'Safety Equipment',
    'work-gloves'                     => 'Work Gloves',
    'safety-glasses'                  => 'Safety Glasses',
    'hard-hats'                       => 'Hard Hats',
    'safety-vests'                    => 'Safety Vests',
    'respirators-masks'               => 'Respirators & Masks',

    // Cleaning
    'cleaning-supplies-industrial'    => 'Cleaning Supplies',
    'janitorial-supplies'             => 'Janitorial Supplies',
    'commercial-cleaners'             => 'Commercial Cleaners',
    'floor-care'                      => 'Floor Care',
    'trash-bags'                      => 'Trash Bags',

    // Material Handling
    'material-handling'               => 'Material Handling',
    'carts-dollies'                   => 'Carts & Dollies',
    'pallet-jacks'                    => 'Pallet Jacks',
    'forklifts'                       => 'Forklifts',
    'shelving-racks'                  => 'Shelving & Racks',

    // Electrical
    'electrical-equipment-industrial' => 'Electrical Equipment',
    'wiring-cables'                   => 'Wiring & Cables',
    'switches-outlets'                => 'Switches & Outlets',
    'circuit-breakers'                => 'Circuit Breakers',
    'electrical-tools-industrial'     => 'Electrical Tools',

    // Industrial Components
    'hydraulics-pneumatics'           => 'Hydraulics & Pneumatics',
    'hydraulic-pumps'                 => 'Hydraulic Pumps',
    'pneumatic-tools'                 => 'Pneumatic Tools',
    'test-measurement'                => 'Test & Measurement',
    'multimeters'                     => 'Multimeters',
    'oscilloscopes'                   => 'Oscilloscopes',
    'calipers-micrometers'            => 'Calipers & Micrometers',
    'fasteners-hardware'              => 'Fasteners & Hardware',
    'bolts-nuts'                      => 'Bolts & Nuts',
    'screws-industrial'               => 'Screws',
    'anchors'                         => 'Anchors',
    'packaging-materials-industrial'  => 'Packaging Materials',
    'industrial-packaging'            => 'Industrial Packaging',
    'shrink-wrap'                     => 'Shrink Wrap',

    // ==================== GARDEN & OUTDOOR LIVING ====================
    'garden-plants'                   => 'Garden & Plants',
    'live-plants'                     => 'Live Plants',
    'indoor-plants'                   => 'Indoor Plants',
    'outdoor-plants'                  => 'Outdoor Plants',
    'succulents-cacti'                => 'Succulents & Cacti',
    'flower-bulbs'                    => 'Flower Bulbs',
    'trees-shrubs'                    => 'Trees & Shrubs',

    // Lawn & Garden
    'lawn-care'                       => 'Lawn Care',
    'lawn-mowers'                     => 'Lawn Mowers',
    'grass-seed'                      => 'Grass Seed',
    'fertilizers'                     => 'Fertilizers',
    'weed-control'                    => 'Weed Control',
    'leaf-blowers'                    => 'Leaf Blowers',
    'trimmers-edgers'                 => 'Trimmers & Edgers',

    // Outdoor Furniture
    'outdoor-furniture-garden'        => 'Outdoor Furniture',
    'patio-furniture-sets'            => 'Patio Furniture Sets',
    'outdoor-chairs'                  => 'Outdoor Chairs',
    'outdoor-tables'                  => 'Outdoor Tables',
    'hammocks'                        => 'Hammocks',
    'outdoor-umbrellas'               => 'Outdoor Umbrellas',
    'outdoor-cushions'                => 'Outdoor Cushions',

    // Outdoor Cooking
    'grills-outdoor-cooking'          => 'Grills & Outdoor Cooking',
    'gas-grills'                      => 'Gas Grills',
    'charcoal-grills'                 => 'Charcoal Grills',
    'pellet-grills'                   => 'Pellet Grills',
    'smokers'                         => 'Smokers',
    'grill-accessories'               => 'Grill Accessories',
    'fire-pits'                       => 'Fire Pits',
    'outdoor-pizza-ovens'             => 'Outdoor Pizza Ovens',

    // Pool & Spa
    'pool-spa'                        => 'Pool & Spa',
    'above-ground-pools'              => 'Above Ground Pools',
    'pool-accessories'                => 'Pool Accessories',
    'pool-chemicals'                  => 'Pool Chemicals',
    'hot-tubs-spas'                   => 'Hot Tubs & Spas',
    'pool-floats-toys'                => 'Pool Floats & Toys',

    // Garden Tools
    'garden-tools-lawn'               => 'Garden Tools',
    'shovels-spades'                  => 'Shovels & Spades',
    'rakes-garden'                    => 'Rakes',
    'pruning-tools'                   => 'Pruning Tools',
    'garden-hoses'                    => 'Garden Hoses',
    'wheelbarrows'                    => 'Wheelbarrows',
    'composters'                      => 'Composters',

    // Growing
    'seeds-bulbs'                     => 'Seeds & Bulbs',
    'vegetable-seeds'                 => 'Vegetable Seeds',
    'flower-seeds'                    => 'Flower Seeds',
    'herb-seeds'                      => 'Herb Seeds',
    'greenhouses'                     => 'Greenhouses',
    'grow-tents'                      => 'Grow Tents',
    'grow-lights'                     => 'Grow Lights',
    'hydroponics'                     => 'Hydroponics',
    'planters-pots'                   => 'Planters & Pots',
    'raised-garden-beds'              => 'Raised Garden Beds',

    // Irrigation
    'irrigation-watering'             => 'Irrigation & Watering',
    'sprinkler-systems'               => 'Sprinkler Systems',
    'drip-irrigation'                 => 'Drip Irrigation',
    'watering-cans'                   => 'Watering Cans',
    'garden-hose-reels'               => 'Garden Hose Reels',

    // Pest Control
    'pest-control-garden'             => 'Pest Control',
    'insect-repellents'               => 'Insect Repellents',
    'rodent-control'                  => 'Rodent Control',
    'animal-repellents'               => 'Animal Repellents',
    'weed-killers'                    => 'Weed Killers',

    // Outdoor Structures
    'outdoor-structures'              => 'Outdoor Structures',
    'sheds-storage'                   => 'Sheds & Storage',
    'gazebos'                         => 'Gazebos',
    'pergolas'                        => 'Pergolas',
    'canopies-tents'                  => 'Canopies & Tents',
    'fencing'                         => 'Fencing',
    'decking'                         => 'Decking',
    'outdoor-lighting-garden'         => 'Outdoor Lighting',
    'solar-lights'                    => 'Solar Lights',
    'path-lights'                     => 'Path Lights',
    'string-lights'                   => 'String Lights',
];