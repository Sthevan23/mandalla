<?php

/**
 * Popula/atualiza a Coleção Bali (1 foto única por look).
 * Usado pelo seed inicial e pelo atualizador da Hostinger.
 *
 * @return array{created:int, removed:int, dups:int}
 */
if (!function_exists('mandalla_seed_bali_catalog')) {
function mandalla_seed_bali_catalog(PDO $pdo, string $root): array
{
    $manifestFile = $root . '/public/uploads/products/bali/manifest.json';
    if (!is_file($manifestFile)) {
        return ['created' => 0, 'removed' => 0, 'dups' => 0];
    }

    $manifest = json_decode((string) file_get_contents($manifestFile), true);
    if (!is_array($manifest)) {
        return ['created' => 0, 'removed' => 0, 'dups' => 0];
    }

    // Remove catálogo antigo (demo + bali anterior)
    $ids = $pdo->query('SELECT id FROM products')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id) {
        $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([(int) $id]);
    }
    $removed = count($ids);

    $cats = [];
    foreach ($pdo->query('SELECT id, slug FROM categories') as $c) {
        $cats[$c['slug']] = (int) $c['id'];
    }

    // Garante categoria colecao-bali (filtro virtual usa slug bali-look-*)
    if (!isset($cats['colecao-bali'])) {
        $pdo->prepare(
            'INSERT INTO categories (name, slug, image, description, sort_order, is_active)
             VALUES (?, ?, ?, ?, ?, 1)'
        )->execute([
            'Coleção Bali',
            'colecao-bali',
            '/uploads/products/bali/bali-001.jpg',
            'Campanha Bali · Verão 27',
            0,
        ]);
        $cats['colecao-bali'] = (int) $pdo->lastInsertId();
    } else {
        $pdo->prepare('UPDATE categories SET image = ?, name = ? WHERE slug = ?')->execute([
            '/uploads/products/bali/bali-001.jpg',
            'Coleção Bali',
            'colecao-bali',
        ]);
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
        $idx = (int) ($item['index'] ?? 0);
        if ($idx <= 2) {
            continue;
        }
        $path = (string) ($item['file'] ?? '');
        $abs = $root . '/public' . $path;
        if ($path === '' || !is_file($abs)) {
            continue;
        }
        $hash = md5_file($abs);
        if (isset($seenHash[$hash])) {
            continue;
        }
        $seenHash[$hash] = true;

        $n++;
        $type = $typeCycle[($n - 1) % count($typeCycle)];
        if (!isset($cats[$type])) {
            $type = 'vestidos';
        }
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
        $sold = $best ? random_int(10, 35) : random_int(0, 8);
        $desc = "Peça da Coleção Bali — Verão 27. Look {$n} da campanha Mandalla.";

        $insP->execute([$catId, $name, $slug, $desc, $price, $compare, $featured, $isNew, $isSale, $best, $cost, $sold]);
        $pid = (int) $pdo->lastInsertId();
        $insI->execute([$pid, $path, $name]);
        $color = $colors[($n - 1) % count($colors)];
        foreach ($sizes as $size) {
            $stock = $size === 'M' ? random_int(5, 12) : random_int(2, 8);
            $sku = 'BALI-' . str_pad((string) $n, 3, '0', STR_PAD_LEFT) . "-{$size}";
            $insV->execute([$pid, $size, $color[0], $color[1], $sku, $stock]);
        }
    }

    // Atualiza imagens de categorias principais com fotos Bali
    $catImages = [
        'vestidos' => '/uploads/products/bali/bali-003.jpg',
        'blusas' => '/uploads/products/bali/bali-010.jpg',
        'calcas' => '/uploads/products/bali/bali-020.jpg',
        'conjuntos' => '/uploads/products/bali/bali-008.jpg',
        'saias' => '/uploads/products/bali/bali-015.jpg',
        'novidades' => '/uploads/products/bali/bali-005.jpg',
        'promocoes' => '/uploads/products/bali/bali-012.jpg',
    ];
    $updCat = $pdo->prepare('UPDATE categories SET image = ? WHERE slug = ?');
    foreach ($catImages as $slug => $img) {
        if (is_file($root . '/public' . $img)) {
            $updCat->execute([$img, $slug]);
        }
    }

    $set = $pdo->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)');
    $set->execute(['catalog_version', 'bali-v1']);
    $set->execute(['hero_image', '/uploads/products/bali/bali-001.jpg']);
    $set->execute(['hero_title', 'Coleção Bali']);
    $set->execute(['hero_subtitle', 'Verão 27 — leveza, texturas artesanais e tons neutros inspirados em Bali.']);
    $ig = [];
    foreach ([3, 8, 15, 25, 40, 55] as $i) {
        $f = '/uploads/products/bali/bali-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT) . '.jpg';
        if (is_file($root . '/public' . $f)) {
            $ig[] = $f;
        }
    }
    $set->execute(['instagram_feed', json_encode($ig, JSON_UNESCAPED_SLASHES)]);

    $hashes = [];
    $dups = 0;
    foreach ($pdo->query('SELECT path FROM product_images WHERE sort_order = 0') as $r) {
        $file = $root . '/public' . $r['path'];
        if (!is_file($file)) {
            continue;
        }
        $h = md5_file($file);
        if (isset($hashes[$h])) {
            $dups++;
        }
        $hashes[$h] = true;
    }

    return ['created' => $n, 'removed' => $removed, 'dups' => $dups];
}
} // function_exists
