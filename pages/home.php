<?php
// Home page content with updated branding
?>
<div class="hero-section">
    <div class="hero-content">
        <h1>Forda Print</h1>
        <p>Print your documents from anywhere on campus. Fast, reliable, and affordable.</p>
        <?php if (!isLoggedIn()): ?>
            <div class="hero-buttons">
                <a href="index.php?page=login" class="btn btn-primary">Login</a>
                <a href="index.php?page=register" class="btn btn-outline">Register</a>
            </div>
        <?php else: ?>
            <div class="hero-buttons">
                <a href="index.php?page=upload" class="btn btn-primary">New Print Job</a>
                <a href="index.php?page=dashboard" class="btn btn-outline">Dashboard</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="features-section">
    <h2>Features</h2>
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-upload"></i></div>
            <h3>Easy Uploads</h3>
            <p>Upload your documents in various formats including PDF, Word, PowerPoint, and images.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-print"></i></div>
            <h3>Multiple Options</h3>
            <p>Choose from various print options including color, paper size, and double-sided printing.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-map-marker-alt"></i></div>
            <h3>Multiple Locations</h3>
            <p>Pick up your prints from various locations across campus including the library and IT lab.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon"><i class="fas fa-money-bill-wave"></i></div>
            <h3>Affordable Pricing</h3>
            <p>Pay only for what you print with our affordable pricing and discounted rates for bulk printing.</p>
        </div>
    </div>
</div>

<div class="how-it-works-section">
    <h2>How It Works</h2>
    <div class="steps">
        <div class="step">
            <div class="step-number">1</div>
            <h3>Upload</h3>
            <p>Upload your document and select your print options</p>
        </div>
        <div class="step-arrow"><i class="fas fa-arrow-right"></i></div>
        <div class="step">
            <div class="step-number">2</div>
            <h3>Submit</h3>
            <p>Review your order and submit for printing</p>
        </div>
        <div class="step-arrow"><i class="fas fa-arrow-right"></i></div>
        <div class="step">
            <div class="step-number">3</div>
            <h3>Print</h3>
            <p>We'll process your document and notify you when it's ready</p>
        </div>
        <div class="step-arrow"><i class="fas fa-arrow-right"></i></div>
        <div class="step">
            <div class="step-number">4</div>
            <h3>Pickup</h3>
            <p>Pick up your printed document from your chosen location</p>
        </div>
    </div>
</div>

<div class="pricing-section">
    <h2>Pricing</h2>
    <div class="pricing-grid">
        <div class="price-card">
            <div class="price-header">
                <h3>Black & White</h3>
                <div class="price">$0.05<span>/page</span></div>
            </div>
            <ul class="price-features">
                <li><i class="fas fa-check"></i> Standard A4 paper</li>
                <li><i class="fas fa-check"></i> Fast printing</li>
                <li><i class="fas fa-check"></i> Available at all locations</li>
                <li><i class="fas fa-check"></i> Bulk discounts available</li>
            </ul>
        </div>
        <div class="price-card featured">
            <div class="price-header">
                <h3>Color</h3>
                <div class="price">$0.15<span>/page</span></div>
            </div>
            <ul class="price-features">
                <li><i class="fas fa-check"></i> High-quality color prints</li>
                <li><i class="fas fa-check"></i> Standard A4 paper</li>
                <li><i class="fas fa-check"></i> Fast printing</li>
                <li><i class="fas fa-check"></i> Available at select locations</li>
            </ul>
        </div>
        <div class="price-card">
            <div class="price-header">
                <h3>Double-sided</h3>
                <div class="price-discount">$0.02<span>/page discount</span></div>
            </div>
            <ul class="price-features">
                <li><i class="fas fa-check"></i> Eco-friendly option</li>
                <li><i class="fas fa-check"></i> Available for B&W and color</li>
                <li><i class="fas fa-check"></i> Saves paper and money</li>
                <li><i class="fas fa-check"></i> Available at all locations</li>
            </ul>
        </div>
    </div>
</div>

<style>
/* Home page specific styles */
.hero-section {
    background: linear-gradient(rgba(30, 64, 175, 0.9), rgba(30, 64, 175, 0.8)), url('https://images.pexels.com/photos/159775/library-la-trobe-study-students-159775.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=1');
    background-size: cover;
    background-position: center;
    color: var(--white);
    padding: var(--spacing-16) 0;
    margin: calc(-1 * var(--spacing-8)) 0 var(--spacing-8);
    text-align: center;
}

.hero-content {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 var(--spacing-4);
}

.hero-content h1 {
    font-size: 2.5rem;
    margin-bottom: var(--spacing-4);
}

.hero-content p {
    font-size: 1.25rem;
    margin-bottom: var(--spacing-8);
    opacity: 0.9;
}

.hero-buttons {
    display: flex;
    justify-content: center;
    gap: var(--spacing-4);
}

.hero-buttons .btn {
    padding: var(--spacing-3) var(--spacing-8);
    font-size: 1.125rem;
}

.features-section,
.how-it-works-section,
.pricing-section {
    margin-bottom: var(--spacing-16);
    text-align: center;
}

.features-section h2,
.how-it-works-section h2,
.pricing-section h2 {
    margin-bottom: var(--spacing-8);
    position: relative;
    display: inline-block;
}

.features-section h2:after,
.how-it-works-section h2:after,
.pricing-section h2:after {
    content: '';
    display: block;
    width: 50%;
    height: 4px;
    background-color: var(--primary);
    margin: var(--spacing-2) auto 0;
    border-radius: 2px;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-6);
}

.feature-card {
    background-color: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    padding: var(--spacing-6);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.feature-icon {
    background-color: rgba(59, 130, 246, 0.1);
    color: var(--primary);
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    margin: 0 auto var(--spacing-4);
}

.steps {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    max-width: 900px;
    margin: 0 auto;
}

.step {
    background-color: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    padding: var(--spacing-6) var(--spacing-4);
    text-align: center;
    width: 180px;
}

.step-number {
    background-color: var(--primary);
    color: var(--white);
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0 auto var(--spacing-4);
}

.step-arrow {
    font-size: 1.5rem;
    color: var(--gray-400);
}

.pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--spacing-6);
    max-width: 900px;
    margin: 0 auto;
}

.price-card {
    background-color: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    padding: var(--spacing-6);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative;
    overflow: hidden;
}

.price-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.price-card.featured {
    border: 2px solid var(--primary);
    transform: scale(1.05);
}

.price-card.featured:hover {
    transform: scale(1.05) translateY(-5px);
}

.price-header {
    text-align: center;
    margin-bottom: var(--spacing-6);
    padding-bottom: var(--spacing-6);
    border-bottom: 1px solid var(--gray-200);
}

.price {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--primary);
}

.price-discount {
    font-size: 2rem;
    font-weight: 700;
    color: var(--success);
}

.price span, .price-discount span {
    font-size: 1rem;
    font-weight: 400;
    color: var(--gray-500);
}

.price-features {
    list-style: none;
    text-align: left;
}

.price-features li {
    margin-bottom: var(--spacing-3);
    display: flex;
    align-items: center;
}

.price-features i {
    color: var(--success);
    margin-right: var(--spacing-2);
}

@media (max-width: 768px) {
    .hero-content h1 {
        font-size: 2rem;
    }
    
    .hero-content p {
        font-size: 1rem;
    }
    
    .steps {
        flex-direction: column;
        gap: var(--spacing-4);
    }
    
    .step {
        width: 100%;
    }
    
    .step-arrow {
        transform: rotate(90deg);
        margin: var(--spacing-2) 0;
    }
    
    .price-card.featured {
        transform: none;
    }
    
    .price-card.featured:hover {
        transform: translateY(-5px);
    }
}
</style>