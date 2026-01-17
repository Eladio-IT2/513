<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/header.php';

$featuredProducts = get_products(3);
// Image mapping is now handled automatically by get_products() from products.json
?>

<section class="hero container">
    <div class="hero__content">
        <span class="badge">Supporting 50+ local artisans</span>
        <h1>Celebrate Heritage Crafts Crafted with Heart</h1>
        <p>Discover authentic handmade pottery, textiles, paper art, and wooden crafts from artisans in your community. Every purchase preserves cultural stories and supports sustainable livelihoods.</p>
        <div class="hero__actions">
            <a href="<?php echo site_url('products/index.php'); ?>" class="btn btn-primary">Browse Craft Catalog</a>
            <a href="<?php echo site_url('about.php'); ?>" class="btn btn-outline">Learn Our Mission</a>
        </div>
    </div>
    <div class="hero__visual">
        <img src="https://images.unsplash.com/photo-1565183997392-2f6f122e5912?w=900&q=80" alt="Artisan shaping clay pottery">
    </div>
</section>

<section class="container">
    <div class="section-title">
        <h2>Featured Craft Stories</h2>
        <p>Handpicked artifacts that showcase traditional techniques and the artisans behind them.</p>
    </div>
    <div class="grid grid-3">
        <?php foreach ($featuredProducts as $product): ?>
            <article class="product-card">
                <img src="<?php echo htmlspecialchars(media_url($product['image_url'])); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='<?php echo media_url('images/logo.png'); ?>'; this.onerror=null;">
                <div class="product-card__body">
                    <h3 class="product-card__title"><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p><?php echo nl2br(htmlspecialchars(substr($product['description'], 0, 150))); ?>...</p>
                    <div class="product-card__actions">
                        <span class="product-card__price">$<?php echo number_format((float) $product['price'], 2); ?></span>
                        <button class="btn btn-outline" onclick="openProductModal(<?php echo (int) $product['id']; ?>); return false;">View Story</button>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (empty($featuredProducts)): ?>
            <p>No products available yet. Check back soon!</p>
        <?php endif; ?>
    </div>
</section>

<section class="container info-banner">
    <div>
        <h3>Artisan-Led</h3>
        <p>We collaborate closely with artisans to capture their stories, techniques, and cultural heritage in every product listing.</p>
    </div>
    <div>
        <h3>Curated Collections</h3>
        <p>Each craft is photographed, documented, and cataloged with transparent pricing and authentic backstories.</p>
    </div>
    <div>
        <h3>Community Impact</h3>
        <p>Order records empower artisans to plan production while giving you direct access to handmade goods.</p>
    </div>
</section>

<section class="container testimonial">
    <h2>"The platform helped me reach new customers without leaving my studio."</h2>
    <p>— Ana Li, Third-generation weaving artisan</p>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

