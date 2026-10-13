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

    // Nomes únicos (sem numeração) — Coleção Bali
    $looks = [
        ['vestidos', 'Vestido Midi Ubud'],
        ['conjuntos', 'Conjunto Peplum Dourado'],
        ['blusas', 'Blusa Cetim Areia'],
        ['saias', 'Saia Linho Creme'],
        ['calcas', 'Calça Reta Areia'],
        ['vestidos', 'Vestido Neutro Seminyak'],
        ['conjuntos', 'Conjunto Off-White Canggu'],
        ['blusas', 'Top Cropped Linho'],
        ['vestidos', 'Vestido Curto Brisa'],
        ['conjuntos', 'Conjunto Resort Nusa'],
        ['blusas', 'Camisa Leve Jimbaran'],
        ['saias', 'Saia Envelope Natural'],
        ['calcas', 'Calça Wide Creme'],
        ['vestidos', 'Vestido Strapless Lua'],
        ['conjuntos', 'Conjunto Textura Artesanal'],
        ['blusas', 'Top Alça Fina Areia'],
        ['vestidos', 'Vestido Envelope Kuta'],
        ['conjuntos', 'Conjunto Linho Denpasar'],
        ['blusas', 'Blusa Ombro a Ombro'],
        ['saias', 'Saia Flores Tropicais'],
        ['calcas', 'Calça Linho Solta'],
        ['vestidos', 'Vestido Longo Brisa'],
        ['conjuntos', 'Conjunto Botões Ouro'],
        ['blusas', 'Blusa Bordada Verão'],
        ['vestidos', 'Vestido Midi Palmeira'],
        ['conjuntos', 'Conjunto Crochê Marfim'],
        ['blusas', 'Blusa Decote V Soft'],
        ['saias', 'Saia Midi Plissada Areia'],
        ['calcas', 'Pantalona Fluida Bali'],
        ['vestidos', 'Vestido Curto Renda'],
        ['conjuntos', 'Conjunto Short e Cropped'],
        ['blusas', 'Camisa Oversized Off'],
        ['vestidos', 'Vestido Longo Envelope'],
        ['conjuntos', 'Conjunto Saia e Top'],
        ['blusas', 'Regata Linho Natural'],
        ['saias', 'Saia Longa Fluida'],
        ['calcas', 'Calça Alfaiataria Neutra'],
        ['vestidos', 'Vestido Midi Alça Fina'],
        ['conjuntos', 'Conjunto Kimono Leve'],
        ['blusas', 'Blusa Manga Bufante'],
        ['vestidos', 'Vestido Slip Dress Nude'],
        ['conjuntos', 'Conjunto Tricot Areia'],
        ['blusas', 'Top Tomara que Caia'],
        ['saias', 'Saia Curta Evasê'],
        ['calcas', 'Shorts Linho Tropical'],
        ['vestidos', 'Vestido Chemise Creme'],
        ['conjuntos', 'Conjunto Bata e Calça'],
        ['blusas', 'Blusa Babados Suaves'],
        ['vestidos', 'Vestido Midi Costela'],
        ['conjuntos', 'Conjunto Listra Discreta'],
        ['blusas', 'Camisa Tricoline Off'],
        ['saias', 'Saia Midi Transpasse'],
        ['calcas', 'Calça Cargo Leve'],
        ['vestidos', 'Vestido Longo Fenda'],
        ['conjuntos', 'Conjunto Colete Linho'],
        ['blusas', 'Blusa Laço Delicado'],
        ['vestidos', 'Vestido Tubinho Areia'],
        ['conjuntos', 'Conjunto Pantalona'],
        ['blusas', 'Top Frente Única'],
        ['saias', 'Saia Godê Creme'],
        ['calcas', 'Calça Jogger Neutra'],
        ['vestidos', 'Vestido Midi Franzido'],
        ['conjuntos', 'Conjunto Blazer Soft'],
        ['blusas', 'Blusa Gola Alta Fina'],
        ['vestidos', 'Vestido Longo Alças'],
        ['conjuntos', 'Conjunto Saia Midi'],
        ['blusas', 'Camisa Cropped Areia'],
        ['saias', 'Saia Pareô Bali'],
        ['calcas', 'Calça Wide Off-White'],
        ['vestidos', 'Vestido Curto Alça Cruzada'],
        ['conjuntos', 'Conjunto Top e Saia'],
        ['blusas', 'Blusa Manga 3/4'],
        ['vestidos', 'Vestido Midi Torcido'],
        ['conjuntos', 'Conjunto Amplo Linho'],
        ['blusas', 'Top Decote Quadrado'],
        ['saias', 'Saia Longa Fenda'],
        ['calcas', 'Calça Reta Creme'],
        ['vestidos', 'Vestido Longo Drapeado'],
        ['conjuntos', 'Conjunto Básico Premium'],
        ['blusas', 'Blusa Assimétrica'],
        ['vestidos', 'Vestido Midi Botões'],
        ['conjuntos', 'Conjunto Verão Leve'],
        ['blusas', 'Camisa Amarração'],
        ['saias', 'Saia Midi Linho'],
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

        $look = $looks[$n] ?? ['vestidos', 'Peça Coleção Bali'];
        $n++;
        $type = $look[0];
        $name = $look[1];
        if (!isset($cats[$type])) {
            $type = 'vestidos';
        }
        $catId = $cats[$type];
        $slug = 'bali-look-' . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
        $price = [189.9, 219.9, 249.9, 279.9, 299.9, 329.9, 359.9][($n - 1) % 7];
        $featured = $n <= 8 ? 1 : 0;
        $isNew = $n <= 16 ? 1 : 0;
        $isSale = ($n % 5 === 0) ? 1 : 0;
        $best = ($n % 4 === 0) ? 1 : 0;
        $compare = $isSale ? round($price * 1.2, 2) : null;
        $cost = round($price * 0.42, 2);
        $sold = $best ? random_int(10, 35) : random_int(0, 8);
        $desc = "Peça da Coleção Bali — Verão 27. {$name}, em tons neutros e caimento leve para o dia a dia.";

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
    $set->execute(['catalog_version', 'bali-v2']);
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
