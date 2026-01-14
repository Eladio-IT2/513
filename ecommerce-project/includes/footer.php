<?php
declare(strict_types=1);
?>
</main>
<footer class="site-footer">
    <div class="container footer-simple">
        <div class="footer-content">
            <span>&copy; <?php echo date('Y'); ?> Heritage Craft Marketplace</span>
            <span class="footer-links">
                <a href="<?php echo site_url('about.php'); ?>">About</a> |
                <a href="<?php echo site_url('support.php'); ?>">Support</a> |
                <a href="mailto:hello@heritagecrafts.local">Contact</a>
            </span>
            <span class="footer-student">Student: 881382404 Eladio</span>
        </div>
    </div>
</footer>

<!-- Product Modal -->
<div id="productModal" class="product-modal">
    <div class="product-modal__content">
        <button class="product-modal__close" aria-label="Close modal">&times;</button>
        <div class="product-modal__body" id="productModalBody">
            <div style="text-align: center; padding: 2rem;">
                <p>Loading...</p>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const modal = document.getElementById('productModal');
    const modalBody = document.getElementById('productModalBody');
    const closeBtn = document.querySelector('.product-modal__close');
    
    // Open modal function
    function openProductModal(productId) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Load product data
        fetch('<?php echo site_url("api/product.php"); ?>?id=' + productId)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.product) {
                    const product = data.product;
                    modalBody.innerHTML = `
                        <div class="product-modal__header">
                            <h2 class="product-modal__title">${escapeHtml(product.name)}</h2>
                        </div>
                        <div class="product-modal__grid">
                            <div>
                                <img src="${(product.image_url || '').replace(/\\s/g, '%20')}" alt="${escapeHtml(product.name)}" class="product-modal__image" onerror="this.src='<?php echo media_url("images/logo.png"); ?>'">
                            </div>
                            <div class="product-modal__info">
                                <div class="product-modal__price">$${parseFloat(product.price).toFixed(2)}</div>
                                <div class="product-modal__section">
                                    <h3>Product Description</h3>
                                    <p>${escapeHtml(product.description).replace(/\n/g, '<br>')}</p>
                                </div>
                                <div class="product-modal__actions">
                                    <div class="product-modal__quantity">
                                        <label for="modalQuantity">Quantity:</label>
                                        <input type="number" id="modalQuantity" min="1" value="1">
                                    </div>
                                    <button class="btn btn-primary" id="modalAddToCartBtn" onclick="addToCartFromModal(${product.id}, this)">Add to Cart</button>
                                </div>
                                <div id="modalMessage" class="product-modal__message"></div>
                            </div>
                        </div>
                    `;
                } else {
                    modalBody.innerHTML = '<div style="text-align: center; padding: 2rem;"><p>Product not found.</p></div>';
                }
            })
            .catch(error => {
                console.error('Error loading product:', error);
                modalBody.innerHTML = '<div style="text-align: center; padding: 2rem;"><p>Error loading product details.</p></div>';
            });
    }
    
    // Close modal function
    function closeProductModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        modalBody.innerHTML = '<div style="text-align: center; padding: 2rem;"><p>Loading...</p></div>';
    }
    
    // Close modal events
    closeBtn.addEventListener('click', closeProductModal);
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeProductModal();
        }
    });
    
    // ESC key to close
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            closeProductModal();
        }
    });
    
    // Make openProductModal globally available
    window.openProductModal = openProductModal;
    
    // Add to cart from modal
    window.addToCartFromModal = function(productId, buttonElement) {
        const quantity = parseInt(document.getElementById('modalQuantity').value) || 1;
        const messageDiv = document.getElementById('modalMessage');
        const addButton = buttonElement || document.getElementById('modalAddToCartBtn');
        
        // Disable button during request
        addButton.disabled = true;
        addButton.textContent = 'Adding...';
        
        // Create form data
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', quantity);
        
        fetch('<?php echo site_url("api/add-to-cart.php"); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageDiv.className = 'product-modal__message success';
                messageDiv.textContent = data.message || 'Added to your cart!';
                messageDiv.style.display = 'block';
                
                // Update cart count in navigation
                const cartLink = document.querySelector('a[href*="cart"]');
                if (cartLink && data.cart_total !== undefined) {
                    const currentText = cartLink.textContent;
                    const newText = currentText.replace(/\(\d+\)/, '(' + data.cart_total + ')');
                    cartLink.textContent = newText || 'Cart (' + data.cart_total + ')';
                }
                
                // Hide message after 3 seconds
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 3000);
            } else {
                messageDiv.className = 'product-modal__message';
                messageDiv.textContent = data.error || 'Could not add to cart. Please try again.';
                messageDiv.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error adding to cart:', error);
            messageDiv.className = 'product-modal__message';
            messageDiv.textContent = 'Error adding to cart. Please try again.';
            messageDiv.style.display = 'block';
        })
        .finally(() => {
            // Re-enable button
            addButton.disabled = false;
            addButton.textContent = 'Add to Cart';
        });
    };
    
    // Escape HTML function
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
})();
</script>

</body>
</html>

