-- Wave 2: variants, attributes, coupons, reviews, wishlist, roles, mail-ready orders

CREATE TABLE IF NOT EXISTS attributes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS attributeValues (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    attributeId INTEGER NOT NULL REFERENCES attributes(id) ON DELETE CASCADE,
    value TEXT NOT NULL,
    slug TEXT NOT NULL,
    position INTEGER NOT NULL DEFAULT 0
);
CREATE UNIQUE INDEX IF NOT EXISTS idxAttributeValueUnique ON attributeValues(attributeId, slug);

CREATE TABLE IF NOT EXISTS productVariants (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    productId INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    sku TEXT NOT NULL UNIQUE,
    priceCents INTEGER NOT NULL DEFAULT 0,
    stockQuantity INTEGER NOT NULL DEFAULT 0,
    isActive INTEGER NOT NULL DEFAULT 1,
    createdAt TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idxVariantsProduct ON productVariants(productId);

CREATE TABLE IF NOT EXISTS variantOptions (
    variantId INTEGER NOT NULL REFERENCES productVariants(id) ON DELETE CASCADE,
    attributeId INTEGER NOT NULL REFERENCES attributes(id) ON DELETE CASCADE,
    attributeValueId INTEGER NOT NULL REFERENCES attributeValues(id) ON DELETE CASCADE,
    PRIMARY KEY (variantId, attributeId)
);
CREATE INDEX IF NOT EXISTS idxVariantOptionsValue ON variantOptions(attributeValueId);

CREATE TABLE IF NOT EXISTS coupons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,
    type TEXT NOT NULL DEFAULT 'percent',
    value INTEGER NOT NULL DEFAULT 0,
    minSubtotalCents INTEGER NOT NULL DEFAULT 0,
    maxRedemptions INTEGER,
    redeemedCount INTEGER NOT NULL DEFAULT 0,
    startsAt TEXT,
    expiresAt TEXT,
    isActive INTEGER NOT NULL DEFAULT 1,
    createdAt TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    productId INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    userId INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    rating INTEGER NOT NULL,
    title TEXT NOT NULL DEFAULT '',
    body TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL DEFAULT 'pending',
    createdAt TEXT NOT NULL,
    moderatedAt TEXT
);
CREATE INDEX IF NOT EXISTS idxReviewsProduct ON reviews(productId, status);
CREATE UNIQUE INDEX IF NOT EXISTS idxReviewsUserProduct ON reviews(userId, productId);

CREATE TABLE IF NOT EXISTS wishlistItems (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    userId INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    productId INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    createdAt TEXT NOT NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS idxWishlistUserProduct ON wishlistItems(userId, productId);

ALTER TABLE users ADD COLUMN role TEXT NOT NULL DEFAULT 'customer';
UPDATE users SET role = 'admin' WHERE isAdmin = 1;

ALTER TABLE orders ADD COLUMN discountCents INTEGER NOT NULL DEFAULT 0;
ALTER TABLE orders ADD COLUMN couponCode TEXT;

ALTER TABLE orderItems ADD COLUMN variantId INTEGER;
ALTER TABLE orderItems ADD COLUMN variantLabel TEXT NOT NULL DEFAULT '';
