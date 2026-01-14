<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/header.php';
?>

<section class="container page-header">
    <h1>Our Story</h1>
    <p class="breadcrumbs"><a href="<?php echo site_url('index.php'); ?>">Home</a> / Our Story</p>
</section>

<section class="container content-card">
    <div class="two-column">
        <div>
            <h2>Preserving Craft Heritage</h2>
            <p>Heritage Craft Marketplace began as a community-driven initiative to help artisans in our region showcase their handcrafted works without the complexity of large e-commerce platforms. We observed talented makers who were experts in clay, textiles, wood, and paper, yet lacked digital tools to reach new customers. Our mission is to bridge that gap.</p>
            <p>We believe that every handmade product carries a story—one rooted in culture, tradition, and the hands that crafted it. By documenting artisan backgrounds, filming their processes, and gathering product details, we create a digital storytelling experience that honours each craft.</p>
        </div>
        <div>
            <h2>How the Platform Works</h2>
            <ul>
                <li><strong>Curated Listings:</strong> Artisans provide photos, cultural context, and pricing. Our team reviews for quality and authenticity.</li>
                <li><strong>User-Friendly Interface:</strong> Customers browse crafts, add items to a cart, and submit orders without needing advanced tech skills.</li>
                <li><strong>Admin Support:</strong> Our admin dashboard lets students manage users, products, and orders with ease.</li>
            </ul>
            <p>Built with PHP, MySQL, and open-source tools, the platform is intentionally lightweight so it can run on free hosting services while remaining accessible to student developers.</p>
        </div>
    </div>

    <div class="info-banner" style="margin-top:2.5rem;">
        <div>
            <h3>50+ Artisans</h3>
            <p>Representing pottery, weaving, woodcraft, and paper art disciplines.</p>
        </div>
        <div>
            <h3>12-Week Roadmap</h3>
            <p>Structured milestones ensure consistent progress from setup to launch.</p>
        </div>
        <div>
            <h3>$0 Budget</h3>
            <p>Free tools and hosting make the project sustainable for student teams.</p>
        </div>
    </div>

    <div style="margin-top:2.5rem;">
        <h2>Visit Our Community Hub</h2>
        <p>Our pilot artisan hub is based in a cultural district, surrounded by studios and small family-run workshops. The map below shows an example location; in a real deployment, this would be replaced with the vendor&apos;s actual address.</p>
        <div style="margin-top:1rem; border-radius:var(--radius-medium); overflow:hidden; box-shadow:var(--shadow-medium);">
            <!-- Baidu Map iframe - Melbourne, Australia location -->
            <iframe
                title="Heritage Craft Marketplace Map"
                src="https://api.map.baidu.com/marker?location=-37.8183,144.9631&title=Heritage Craft Marketplace&content=Federation Square, Melbourne, Victoria, Australia&output=html&src=webapp"
                width="100%"
                height="360"
                style="border:0;"
                allowfullscreen=""
                loading="lazy"
            ></iframe>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

