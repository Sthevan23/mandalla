<?php

/**
 * Recria produtos Bali com 1 foto única por página renderizada (sem repetição).
 */
require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

$pdo = Database::connection();
$manifest = json_decode(file_get_contents(dirname(__DIR__) . '/public/uploads/products/bali/manifest.json'), true);

// Apaga produtos Bali antigos
$ids = $pdo->query("SELECT id FROM products WHERE slug LIKE 'bali-look-%'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($ids as $id) {
    $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([(int) $id]);
}
echo 'removidos ' . count($ids) . " produtos antigos\n";

$cats = [];
foreach ($pdo->query('SELECT id, slug FROM categories') as $c) {
    $cats[$c['slug']] = (int) $c['id'];
}

$typeCycle = ['vestidos', 'conjuntos', 'blusas', 'saias', 'calcas', 'vestidos', 'conjuntos', 'blusas'];
$namesByType = [
    'vestidos' => ['Vestido Bali Midi', 'Vestido Strapless Bali', 'Vestido Curto Verão 27', 'Vestido Longo Brisa', 'Vestido Envelope Bali', 'Vestido Neutro Areia'],
    'conjuntos' => ['Conjunto Bali Off-White', 'Conjunto Peplum Dourado', 'Conjunto Textura Artesanal', 'Conjunto Resort Wear', 'Conjunto Botões Ouro', 'Conjunto Linho Bali'],
    'blusas' => ['Blusa Ombro a Ombro Bali', 'Top Cropped Linho Bali', 'Blusa Cetim Areia Bali', 'Top Alça Fina Bali', 'Camisa Leve Bali', 'Blusa Bordada Verão'],
    'saias' => ['Saia Midi Bali Natural', 'Saia Flores Bali', 'Saia Plissada Verão', 'Saia Linho Creme', 'Saia Curta Tropical', 'Saia Envelope Bali'],
    'calcas' => ['Calça Wide Bali Creme', 'Calça Alfaiataria Neutra', 'Calça Linho Solta', 'Pantalona Bali', 'Calça Reta Areia', 'Shorts Linho Tropical'],
];
$colors = [
    ['Off-white', '#F5F0E8'],
    ['Areia', '#C4A484'],
    ['Creme', '#E8DFD4'],
    ['Natural', '#D4C4B0'],
    ['Branco', '#FFFFFF'],
];
$sizes = ['P', 'M', 'G', 'GG'];

$insP = $pdo->prepare(
    'INSERT INTO products (category_id, name, slug, description, price, compare_at_price, is_active, is_featured, is_new, is_sale, is_bestseller, cost_price, sold_count)
     VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?)'
);
$insI = $pdo->prepare('INSERT INTO product_images (product_id, path, alt, sort_order) VALUES (?, ?, ?, 0)');
$insV = $pdo->prepare('INSERT INTO product_variants (product_id, size, color, color_hex, sku, stock) VALUES (?, ?, ?, ?, ?, ?)');

$seenHash = [];
$n = 0;
foreach ($manifest as $item) {
    $idx = (int) $item['index'];
    if ($idx <= 2) {
        continue; // capas
    }
    $path = $item['file'];
    $abs = dirname(__DIR__) . '/public' . $path;
    if (!is_file($abs)) {
        continue;
    }
    $hash = md5_file($abs);
    if (isset($seenHash[$hash])) {
        echo "skip dup page #$idx\n";
        continue;
    }
    $seenHash[$hash] = true;

    $n++;
    $type = $typeCycle[($n - 1) % count($typeCycle)];
    $catId = $cats[$type];
    $nameList = $namesByType[$type];
    $name = $nameList[($n - 1) % count($nameList)] . ' ' . str_pad((string) $n, 2, '0', STR_PAD_LEFT);
    $slug = 'bali-look-' . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    $price = [189.9, 219.9, 249.9, 279.9, 299.9, 329.9, 359.9][($n - 1) % 7];
    $featured = $n <= 8 ? 1 : 0;
    $isNew = $n <= 16 ? 1 : 0;
    $isSale = ($n % 5 === 0) ? 1 : 0;
    $best = ($n % 4 === 0) ? 1 : 0;
    $compare = $isSale ? round($price * 1.2, 2) : null;
    $cost = round($price * 0.42, 2);
    $sold = $best ? rand(10, 35) : rand(0, 8);
    $desc = "Peça da Coleção Bali — Verão 27. Look {$n} da campanha Mandalla.";

    $insP->execute([$catId, $name, $slug, $desc, $price, $compare, $featured, $isNew, $isSale, $best, $cost, $sold]);
    $pid = (int) $pdo->lastInsertId();
    $insI->execute([$pid, $path, $name]);
    $color = $colors[($n - 1) % count($colors)];
    foreach ($sizes as $size) {
        $stock = $size === 'M' ? rand(5, 12) : rand(2, 8);
        $sku = 'BALI-' . str_pad((string) $n, 3, '0', STR_PAD_LEFT) . "-{$size}";
        $insV->execute([$pid, $size, $color[0], $color[1], $sku, $stock]);
    }
}

// Hero + IG
$set = $pdo->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)');
$set->execute(['hero_image', '/uploads/products/bali/bali-001.jpg']);
$set->execute(['hero_title', 'Coleção Bali']);
$set->execute(['hero_subtitle', 'Verão 27 — leveza, texturas artesanais e tons neutros inspirados em Bali.']);
$ig = [];
foreach ([3, 8, 15, 25, 40, 55] as $i) {
    $f = '/uploads/products/bali/bali-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT) . '.jpg';
    if (is_file(dirname(__DIR__) . '/public' . $f)) {
        $ig[] = $f;
    }
}
$set->execute(['instagram_feed', json_encode($ig, JSON_UNESCAPED_SLASHES)]);

// Confere hashes únicos
$hashes = [];
$dups = 0;
foreach ($pdo->query("SELECT i.path FROM product_images i JOIN products p ON p.id=i.product_id WHERE p.is_active=1 AND i.sort_order=0") as $r) {
    $h = md5_file(dirname(__DIR__) . '/public' . $r['path']);
    if (isset($hashes[$h])) {
        $dups++;
    }
    $hashes[$h] = true;
}

echo "criados=$n hashes_unicos=" . count($hashes) . " dups=$dups\n";
foreach ($pdo->query('SELECT c.slug, COUNT(p.id) n FROM categories c LEFT JOIN products p ON p.category_id=c.id AND p.is_active=1 GROUP BY c.id ORDER BY n DESC') as $r) {
    echo "  {$r['slug']}: {$r['n']}\n";
}
