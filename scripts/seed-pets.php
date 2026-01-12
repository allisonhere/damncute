<?php
// Run from theme dir:
//   php scripts/seed-pets.php --count=30 --wipe
//   php scripts/seed-pets.php --fill-missing
// Optional: --local-only to use uploads/damncute-seed images only.

define('WP_USE_THEMES', false);
$root = dirname(__DIR__, 4);
require_once $root . '/wp-load.php';

function die_with(string $message): void {
    fwrite(STDERR, $message . "\n");
    exit(1);
}

if (!function_exists('wp_insert_post')) {
    die_with('WordPress not loaded.');
}

$args = $argv ?? [];
$should_wipe = in_array('--wipe', $args, true);
$local_only = in_array('--local-only', $args, true);
$fill_missing = in_array('--fill-missing', $args, true);
$count = 30;

foreach ($args as $arg) {
    if (strpos($arg, '--count=') === 0) {
        $count = max(1, (int) substr($arg, 8));
    }
}

$seed_dir = WP_CONTENT_DIR . '/uploads/damncute-seed';
$local_images = glob($seed_dir . '/*.{jpg,jpeg,png}', GLOB_BRACE);
sort($local_images);

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

function ensure_term(string $name, string $taxonomy): int {
    $existing = term_exists($name, $taxonomy);
    if (is_array($existing)) {
        return (int) $existing['term_id'];
    }
    if ($existing) {
        return (int) $existing;
    }

    $result = wp_insert_term($name, $taxonomy);
    if (is_wp_error($result)) {
        return 0;
    }
    return (int) $result['term_id'];
}

function build_image_url(string $keyword): string {
    $keyword = strtolower(trim($keyword));
    return sprintf('https://loremflickr.com/1200/900/%s', rawurlencode($keyword));
}

function attach_image_from_local(string $local_path, int $post_id): int {
    $contents = file_get_contents($local_path);
    if ($contents === false) {
        return 0;
    }

    $upload = wp_upload_bits(basename($local_path), null, $contents);
    if (!empty($upload['error'])) {
        return 0;
    }

    $attachment_id = wp_insert_attachment([
        'guid' => $upload['url'],
        'post_mime_type' => wp_check_filetype($upload['file'], null)['type'] ?? '',
        'post_title' => preg_replace('/\\.[^.]+$/', '', basename($local_path)),
        'post_content' => '',
        'post_status' => 'inherit',
    ], $upload['file'], $post_id);

    if (is_wp_error($attachment_id)) {
        return 0;
    }

    $metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
    if ($metadata) {
        wp_update_attachment_metadata($attachment_id, $metadata);
    }

    return (int) $attachment_id;
}

function attach_image_from_remote(string $url, int $post_id, string $title): int {
    $tmp = download_url($url);

    if (is_wp_error($tmp)) {
        return 0;
    }

    $file_array = [
        'name' => sanitize_file_name($title . '.jpg'),
        'tmp_name' => $tmp,
    ];

    $attachment_id = media_handle_sideload($file_array, $post_id);
    if (is_wp_error($attachment_id)) {
        @unlink($tmp);
        return 0;
    }

    return (int) $attachment_id;
}

$pet_seed = [
    [
        'name' => 'Milo',
        'species' => 'Dog',
        'breed' => 'Golden Retriever',
        'vibe' => 'Sunbeam',
        'age' => '2 years',
        'owner' => '@milopals',
        'cute' => 'He sneezes every time he gets a belly rub.',
        'about' => 'Milo greets every neighbor with a tennis ball and a wiggly tail.',
        'snack' => 'Frozen blueberries',
        'adoption' => 'Looking for a home',
        'image_keyword' => 'golden retriever dog',
    ],
    [
        'name' => 'Luna',
        'species' => 'Cat',
        'breed' => 'British Shorthair',
        'vibe' => 'Velvet',
        'age' => '3 years',
        'owner' => '@lunapurrs',
        'cute' => 'She folds her paws like a tiny loaf and chirps at birds.',
        'about' => 'Luna is calm, curious, and insists on supervised sunbeam naps.',
        'snack' => 'Churu tuna tubes',
        'adoption' => 'Not listed',
        'image_keyword' => 'british shorthair cat',
    ],
    [
        'name' => 'Biscuit',
        'species' => 'Dog',
        'breed' => 'Corgi',
        'vibe' => 'Bouncy',
        'age' => '1 year',
        'owner' => '@biscuitbooty',
        'cute' => 'He wiggles so hard his whole body does a tiny hop.',
        'about' => 'Biscuit is a social butterfly who lives for squeaky toys.',
        'snack' => 'Mini carrot sticks',
        'adoption' => 'Already adopted',
        'image_keyword' => 'corgi',
    ],
    [
        'name' => 'Poppy',
        'species' => 'Rabbit',
        'breed' => 'Mini Lop',
        'vibe' => 'Sweet',
        'age' => '10 months',
        'owner' => '@poppylop',
        'cute' => 'She gives tiny nose kisses when you bring parsley.',
        'about' => 'Poppy loves tunnels, quiet music, and morning stretches.',
        'snack' => 'Fresh parsley',
        'adoption' => 'Looking for a home',
        'image_keyword' => 'rabbit',
    ],
    [
        'name' => 'Theo',
        'species' => 'Dog',
        'breed' => 'Border Collie',
        'vibe' => 'Bright-eyed',
        'age' => '4 years',
        'owner' => '@theocollie',
        'cute' => 'He tilts his head dramatically when you ask questions.',
        'about' => 'Theo is brilliant at puzzles and loves a good trail run.',
        'snack' => 'Peanut butter biscuits',
        'adoption' => 'Already adopted',
        'image_keyword' => 'border collie',
    ],
    [
        'name' => 'Maple',
        'species' => 'Cat',
        'breed' => 'Maine Coon',
        'vibe' => 'Gentle giant',
        'age' => '5 years',
        'owner' => '@maplemoon',
        'cute' => 'She trills when you brush her fluffy tail.',
        'about' => 'Maple is affectionate and insists on being near the action.',
        'snack' => 'Salmon flakes',
        'adoption' => 'Not listed',
        'image_keyword' => 'maine coon cat',
    ],
    [
        'name' => 'Sunny',
        'species' => 'Bird',
        'breed' => 'Cockatiel',
        'vibe' => 'Whistly',
        'age' => '2 years',
        'owner' => '@sunnywhistles',
        'cute' => 'He whistles along whenever the kettle starts humming.',
        'about' => 'Sunny is social, curious, and loves shoulder rides.',
        'snack' => 'Millet spray',
        'adoption' => 'Not listed',
        'image_keyword' => 'cockatiel',
    ],
    [
        'name' => 'Nori',
        'species' => 'Cat',
        'breed' => 'Siamese',
        'vibe' => 'Chatty',
        'age' => '2 years',
        'owner' => '@noriwhispers',
        'cute' => 'She narrates your day with polite little meows.',
        'about' => 'Nori is a loyal shadow who loves warm blankets.',
        'snack' => 'Chicken bites',
        'adoption' => 'Already adopted',
        'image_keyword' => 'siamese cat',
    ],
    [
        'name' => 'Finn',
        'species' => 'Dog',
        'breed' => 'Australian Shepherd',
        'vibe' => 'Bold',
        'age' => '3 years',
        'owner' => '@finnfetch',
        'cute' => 'He prances when you grab the leash.',
        'about' => 'Finn is energetic, loyal, and loves agility courses.',
        'snack' => 'Apple slices',
        'adoption' => 'Looking for a home',
        'image_keyword' => 'australian shepherd',
    ],
    [
        'name' => 'Mochi',
        'species' => 'Cat',
        'breed' => 'Ragdoll',
        'vibe' => 'Cloud',
        'age' => '1 year',
        'owner' => '@mochimelt',
        'cute' => 'She goes limp like a plushie when picked up.',
        'about' => 'Mochi is gentle and loves slow, steady pets.',
        'snack' => 'Duck pate',
        'adoption' => 'Not listed',
        'image_keyword' => 'ragdoll cat',
    ],
    [
        'name' => 'Pepper',
        'species' => 'Guinea Pig',
        'breed' => 'Abyssinian',
        'vibe' => 'Squeaky',
        'age' => '1 year',
        'owner' => '@pepperpigs',
        'cute' => 'She popcorns when she hears the fridge open.',
        'about' => 'Pepper is brave, friendly, and loves cozy hideouts.',
        'snack' => 'Romaine leaves',
        'adoption' => 'Looking for a home',
        'image_keyword' => 'guinea pig',
    ],
    [
        'name' => 'Waffles',
        'species' => 'Dog',
        'breed' => 'Dachshund',
        'vibe' => 'Comfy',
        'age' => '6 years',
        'owner' => '@wafflesroll',
        'cute' => 'He rolls himself into a blanket burrito.',
        'about' => 'Waffles loves naps, cozy laps, and slow strolls.',
        'snack' => 'Cheddar cubes',
        'adoption' => 'Already adopted',
        'image_keyword' => 'dachshund',
    ],
    [
        'name' => 'Olive',
        'species' => 'Cat',
        'breed' => 'Calico',
        'vibe' => 'Bright',
        'age' => '4 years',
        'owner' => '@olivepatch',
        'cute' => 'She taps your shoulder with a soft paw for attention.',
        'about' => 'Olive is playful, sweet, and loves chasing feather wands.',
        'snack' => 'Turkey morsels',
        'adoption' => 'Not listed',
        'image_keyword' => 'calico cat',
    ],
    [
        'name' => 'Koda',
        'species' => 'Dog',
        'breed' => 'Husky',
        'vibe' => 'Frosty',
        'age' => '2 years',
        'owner' => '@kodahowl',
        'cute' => 'He talks back with playful woo-woos.',
        'about' => 'Koda loves snow, long walks, and dramatic zoomies.',
        'snack' => 'Beef jerky bites',
        'adoption' => 'Looking for a home',
        'image_keyword' => 'siberian husky',
    ],
    [
        'name' => 'Clover',
        'species' => 'Rabbit',
        'breed' => 'Dutch',
        'vibe' => 'Shy',
        'age' => '1 year',
        'owner' => '@cloverbun',
        'cute' => 'She flops sideways when she feels safe.',
        'about' => 'Clover is gentle and enjoys quiet cuddle time.',
        'snack' => 'Dill sprigs',
        'adoption' => 'Looking for a home',
        'image_keyword' => 'dutch rabbit',
    ],
    [
        'name' => 'Juno',
        'species' => 'Cat',
        'breed' => 'Tabby',
        'vibe' => 'Mischief',
        'age' => '2 years',
        'owner' => '@junotabby',
        'cute' => 'She pounces on shadows like they are mice.',
        'about' => 'Juno is curious, quick, and loves window perches.',
        'snack' => 'Crunchy salmon kibble',
        'adoption' => 'Already adopted',
        'image_keyword' => 'tabby cat',
    ],
    [
        'name' => 'Atlas',
        'species' => 'Dog',
        'breed' => 'Great Dane',
        'vibe' => 'Gentle giant',
        'age' => '5 years',
        'owner' => '@atlasdane',
        'cute' => 'He thinks he is a lap dog.',
        'about' => 'Atlas is calm, sweet, and loves slow couch cuddles.',
        'snack' => 'Peanut butter biscuits',
        'adoption' => 'Already adopted',
        'image_keyword' => 'great dane',
    ],
    [
        'name' => 'Skye',
        'species' => 'Bird',
        'breed' => 'Budgie',
        'vibe' => 'Bright',
        'age' => '8 months',
        'owner' => '@skyebudgie',
        'cute' => 'She does tiny head bobs when she is excited.',
        'about' => 'Skye loves chatter, mirrors, and gentle whistling.',
        'snack' => 'Seed mix',
        'adoption' => 'Not listed',
        'image_keyword' => 'budgie',
    ],
    [
        'name' => 'Hazel',
        'species' => 'Cat',
        'breed' => 'Persian',
        'vibe' => 'Plush',
        'age' => '6 years',
        'owner' => '@hazelcloud',
        'cute' => 'She snores softly during naps.',
        'about' => 'Hazel prefers calm rooms and slow brush sessions.',
        'snack' => 'Turkey pate',
        'adoption' => 'Not listed',
        'image_keyword' => 'persian cat',
    ],
    [
        'name' => 'Rio',
        'species' => 'Dog',
        'breed' => 'Beagle',
        'vibe' => 'Sniffy',
        'age' => '3 years',
        'owner' => '@rioruns',
        'cute' => 'He does a happy spin before every walk.',
        'about' => 'Rio is playful and loves scent games.',
        'snack' => 'Sweet potato chews',
        'adoption' => 'Looking for a home',
        'image_keyword' => 'beagle',
    ],
    [
        'name' => 'Mabel',
        'species' => 'Cat',
        'breed' => 'American Shorthair',
        'vibe' => 'Cozy',
        'age' => '4 years',
        'owner' => '@mabelmews',
        'cute' => 'She tucks her paws under her chin when she sleeps.',
        'about' => 'Mabel is affectionate and loves gentle head scratches.',
        'snack' => 'Roasted chicken shreds',
        'adoption' => 'Not listed',
        'image_keyword' => 'american shorthair cat',
    ],
    [
        'name' => 'Nova',
        'species' => 'Dog',
        'breed' => 'Dalmatian',
        'vibe' => 'Spark',
        'age' => '2 years',
        'owner' => '@novaspots',
        'cute' => 'She leans into hugs like a warm blanket.',
        'about' => 'Nova is energetic, smart, and loves long runs.',
        'snack' => 'Pumpkin treats',
        'adoption' => 'Looking for a home',
        'image_keyword' => 'dalmatian',
    ],
    [
        'name' => 'Pickles',
        'species' => 'Guinea Pig',
        'breed' => 'Peruvian',
        'vibe' => 'Floof',
        'age' => '1 year',
        'owner' => '@picklespigs',
        'cute' => 'His long hair bounces when he popcorns.',
        'about' => 'Pickles is calm and loves gentle nose boops.',
        'snack' => 'Cucumber slices',
        'adoption' => 'Looking for a home',
        'image_keyword' => 'guinea pig',
    ],
    [
        'name' => 'Remy',
        'species' => 'Dog',
        'breed' => 'French Bulldog',
        'vibe' => 'Chunky',
        'age' => '4 years',
        'owner' => '@remyrolls',
        'cute' => 'He snores like a tiny motor during naps.',
        'about' => 'Remy is laid-back and loves couch cuddles.',
        'snack' => 'Banana slices',
        'adoption' => 'Already adopted',
        'image_keyword' => 'french bulldog',
    ],
    [
        'name' => 'Willow',
        'species' => 'Cat',
        'breed' => 'Russian Blue',
        'vibe' => 'Silky',
        'age' => '3 years',
        'owner' => '@willowblue',
        'cute' => 'She slow-blinks like she is sending love letters.',
        'about' => 'Willow is gentle and adores quiet mornings.',
        'snack' => 'Tuna flakes',
        'adoption' => 'Not listed',
        'image_keyword' => 'russian blue cat',
    ],
    [
        'name' => 'Hopper',
        'species' => 'Rabbit',
        'breed' => 'Rex',
        'vibe' => 'Velvety',
        'age' => '2 years',
        'owner' => '@hopperhop',
        'cute' => 'His fur feels like plush velvet.',
        'about' => 'Hopper loves cardboard castles and gentle pets.',
        'snack' => 'Cilantro sprigs',
        'adoption' => 'Looking for a home',
        'image_keyword' => 'rex rabbit',
    ],
    [
        'name' => 'Scout',
        'species' => 'Dog',
        'breed' => 'Labrador',
        'vibe' => 'Sunny',
        'age' => '5 years',
        'owner' => '@scoutfetch',
        'cute' => 'He carries his favorite toy everywhere.',
        'about' => 'Scout is friendly, loyal, and loves lake swims.',
        'snack' => 'Cheese cubes',
        'adoption' => 'Already adopted',
        'image_keyword' => 'labrador dog',
    ],
    [
        'name' => 'Saffron',
        'species' => 'Cat',
        'breed' => 'Bengal',
        'vibe' => 'Wild',
        'age' => '2 years',
        'owner' => '@saffronspots',
        'cute' => 'She does parkour off the couch.',
        'about' => 'Saffron loves playtime and puzzle toys.',
        'snack' => 'Chicken shreds',
        'adoption' => 'Not listed',
        'image_keyword' => 'bengal cat',
    ],
    [
        'name' => 'Orbit',
        'species' => 'Hamster',
        'breed' => 'Syrian',
        'vibe' => 'Tiny rocket',
        'age' => '7 months',
        'owner' => '@orbitwheel',
        'cute' => 'He stuffs his cheeks like a tiny chipmunk.',
        'about' => 'Orbit loves running marathons on his wheel.',
        'snack' => 'Sunflower seeds',
        'adoption' => 'Looking for a home',
        'image_keyword' => 'hamster',
    ],
    [
        'name' => 'Rumi',
        'species' => 'Dog',
        'breed' => 'Shiba Inu',
        'vibe' => 'Foxy',
        'age' => '2 years',
        'owner' => '@rumishiba',
        'cute' => 'He does a proud little strut after treats.',
        'about' => 'Rumi is independent, playful, and loves snow.',
        'snack' => 'Salmon bites',
        'adoption' => 'Already adopted',
        'image_keyword' => 'shiba inu',
    ],
    [
        'name' => 'Tilly',
        'species' => 'Cat',
        'breed' => 'Scottish Fold',
        'vibe' => 'Gentle',
        'age' => '1 year',
        'owner' => '@tillyfold',
        'cute' => 'She sits like a tiny owl with folded paws.',
        'about' => 'Tilly is sweet and loves forehead kisses.',
        'snack' => 'Turkey strips',
        'adoption' => 'Not listed',
        'image_keyword' => 'scottish fold cat',
    ],
    [
        'name' => 'Rocco',
        'species' => 'Dog',
        'breed' => 'Boxer',
        'vibe' => 'Goofy',
        'age' => '4 years',
        'owner' => '@roccoruns',
        'cute' => 'He does a happy wiggle before every meal.',
        'about' => 'Rocco is playful, loyal, and loves tug toys.',
        'snack' => 'Pumpkin bites',
        'adoption' => 'Looking for a home',
        'image_keyword' => 'boxer dog',
    ],
    [
        'name' => 'Pearl',
        'species' => 'Cat',
        'breed' => 'Turkish Angora',
        'vibe' => 'Elegant',
        'age' => '3 years',
        'owner' => '@pearlwhiskers',
        'cute' => 'She flutters her tail like a feather boa.',
        'about' => 'Pearl is graceful and loves soft blankets.',
        'snack' => 'Whitefish bites',
        'adoption' => 'Not listed',
        'image_keyword' => 'turkish angora cat',
    ],
    [
        'name' => 'Bowie',
        'species' => 'Bird',
        'breed' => 'Conure',
        'vibe' => 'Spicy',
        'age' => '2 years',
        'owner' => '@bowiechirp',
        'cute' => 'He dances when music starts.',
        'about' => 'Bowie loves shoulder time and puzzle toys.',
        'snack' => 'Dried mango bits',
        'adoption' => 'Not listed',
        'image_keyword' => 'conure',
    ],
    [
        'name' => 'Loki',
        'species' => 'Dog',
        'breed' => 'Pomeranian',
        'vibe' => 'Fluffy',
        'age' => '2 years',
        'owner' => '@lokifluff',
        'cute' => 'He spins in circles when excited.',
        'about' => 'Loki loves cozy pillows and lap naps.',
        'snack' => 'Apple chips',
        'adoption' => 'Already adopted',
        'image_keyword' => 'pomeranian',
    ],
    [
        'name' => 'Juniper',
        'species' => 'Cat',
        'breed' => 'Norwegian Forest Cat',
        'vibe' => 'Adventure',
        'age' => '5 years',
        'owner' => '@juniperwild',
        'cute' => 'She chirps when she spots squirrels.',
        'about' => 'Juniper is confident and loves tall cat trees.',
        'snack' => 'Salmon nibs',
        'adoption' => 'Not listed',
        'image_keyword' => 'norwegian forest cat',
    ],
];

$local_index = 0;
$used_local = 0;
$created = 0;
$updated = 0;

if ($fill_missing) {
    $missing = get_posts([
        'post_type' => 'pets',
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields' => 'ids',
        'meta_query' => [
            [
                'key' => '_thumbnail_id',
                'compare' => 'NOT EXISTS',
            ],
        ],
    ]);

    foreach ($missing as $post_id) {
        $title = get_the_title($post_id);
        $keyword = 'pet';
        $species_terms = get_the_terms($post_id, 'species');
        if (is_array($species_terms) && !empty($species_terms)) {
            $keyword = $species_terms[0]->name;
        }

        $attachment_id = 0;
        if (!empty($local_images) && isset($local_images[$local_index])) {
            $attachment_id = attach_image_from_local($local_images[$local_index], $post_id);
            $local_index++;
            if ($attachment_id) {
                $used_local++;
            }
        }

        if (!$attachment_id && !$local_only) {
            $attachment_id = attach_image_from_remote(build_image_url($keyword), $post_id, $title);
        }

        if ($attachment_id) {
            set_post_thumbnail($post_id, $attachment_id);
            $updated++;
        }
    }

    printf("Updated %d pets with missing images. Local images used: %d.\n", $updated, $used_local);
    exit(0);
}

if ($should_wipe) {
    $posts = get_posts([
        'post_type' => 'pets',
        'post_status' => 'any',
        'numberposts' => -1,
        'fields' => 'ids',
    ]);

    foreach ($posts as $post_id) {
        wp_delete_post($post_id, true);
    }
}

if ($count > count($pet_seed)) {
    $count = count($pet_seed);
}

foreach (array_slice($pet_seed, 0, $count) as $pet) {
    $title = $pet['name'];
    $content = $pet['cute'];

    $post_id = wp_insert_post([
        'post_type' => 'pets',
        'post_title' => $title,
        'post_content' => $content,
        'post_status' => 'publish',
        'comment_status' => 'open',
    ], true);

    if (is_wp_error($post_id)) {
        fwrite(STDERR, "Failed to create post {$title}: " . $post_id->get_error_message() . "\n");
        continue;
    }

    update_post_meta($post_id, 'cute_description', $pet['cute']);
    update_post_meta($post_id, 'about', $pet['about']);
    update_post_meta($post_id, 'favorite_snack', $pet['snack']);
    update_post_meta($post_id, 'age', $pet['age']);
    update_post_meta($post_id, 'owner_social', $pet['owner']);
    update_post_meta($post_id, 'adoption_status', $pet['adoption']);

    $species_id = ensure_term($pet['species'], 'species');
    if ($species_id) {
        wp_set_object_terms($post_id, [$species_id], 'species');
    }

    if (!empty($pet['breed'])) {
        $breed_id = ensure_term($pet['breed'], 'breed');
        if ($breed_id) {
            wp_set_object_terms($post_id, [$breed_id], 'breed');
        }
    }

    if (!empty($pet['vibe'])) {
        $vibe_id = ensure_term($pet['vibe'], 'vibe');
        if ($vibe_id) {
            wp_set_object_terms($post_id, [$vibe_id], 'vibe');
        }
    }

    $attachment_id = 0;
    if (!empty($local_images) && isset($local_images[$local_index])) {
        $attachment_id = attach_image_from_local($local_images[$local_index], $post_id);
        $local_index++;
        if ($attachment_id) {
            $used_local++;
        }
    }

    if (!$attachment_id && !$local_only) {
        $attachment_id = attach_image_from_remote(build_image_url($pet['image_keyword'] ?? 'pet'), $post_id, $title);
    }

    if ($attachment_id) {
        set_post_thumbnail($post_id, $attachment_id);
    }

    $created++;
}

printf("Seeded %d pets. Local images used: %d.\n", $created, $used_local);
