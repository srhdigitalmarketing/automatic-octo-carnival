<?php echo'<?xml version="1.0" encoding="UTF-8" ?>' ?>

<urlset
        <?= 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' ?>
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
    <!-- created with Free VIPEmbed Sitemap Generator https://vipembed.com -->

    <!-- home page-->
    <url>
        <loc><?= site_url() ?></loc>
        <priority>1.0</priority>
        <changefreq>daily</changefreq>
    </url>

    <!-- API page -->
    <url>
        <loc><?= site_url('/api') ?></loc>
        <priority>0.8</priority>
        <changefreq>monthly</changefreq>
    </url>

    <!-- main pages-->
    <?php foreach($mainPages as $page): ?>

    <url>
        <loc><?= $page ?></loc>
        <priority>0.8</priority>
        <changefreq>daily</changefreq>
    </url>

    <?php endforeach; ?>


    <!-- Sitemap for movies/episodes -->
    <?php if(! empty( $movies )): ?>
        <?php foreach($movies as $movie): ?>

            <url>
                <loc> <?= $movie->getEmbedLink(true) ?> </loc>
                <lastmod><?= date('c', strtotime( $movie->updated_at )); ?></lastmod>
                <priority>0.5</priority>

            </url>
            <url>
                <loc> <?= $movie->getDownloadLink(true) ?> </loc>
                <lastmod>2021-12-28T04:13:31+00:00</lastmod>
                <priority>0.5</priority>
            </url>

        <?php endforeach; ?>
    <?php endif; ?>

</urlset>
