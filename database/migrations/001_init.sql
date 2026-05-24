CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    passwordHash TEXT NOT NULL,
    name TEXT NOT NULL DEFAULT '',
    isAdmin INTEGER NOT NULL DEFAULT 0,
    createdAt TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    description TEXT NOT NULL DEFAULT ''
);

CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    categoryId INTEGER REFERENCES categories(id) ON DELETE SET NULL,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    description TEXT NOT NULL DEFAULT '',
    priceCents INTEGER NOT NULL DEFAULT 0,
    stockQuantity INTEGER NOT NULL DEFAULT 0,
    imageUrl TEXT NOT NULL DEFAULT '',
    isActive INTEGER NOT NULL DEFAULT 1,
    createdAt TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    userId INTEGER NOT NULL REFERENCES users(id),
    status TEXT NOT NULL DEFAULT 'pending',
    totalCents INTEGER NOT NULL DEFAULT 0,
    currency TEXT NOT NULL DEFAULT 'usd',
    stripeSessionId TEXT,
    createdAt TEXT NOT NULL,
    paidAt TEXT,
    fulfilledAt TEXT,
    cancelledAt TEXT
);

CREATE TABLE IF NOT EXISTS orderItems (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    orderId INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    productId INTEGER REFERENCES products(id) ON DELETE SET NULL,
    productName TEXT NOT NULL,
    unitPriceCents INTEGER NOT NULL,
    quantity INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS idxProductsCategory ON products(categoryId);
CREATE INDEX IF NOT EXISTS idxOrdersUser ON orders(userId);
CREATE INDEX IF NOT EXISTS idxOrderItemsOrder ON orderItems(orderId);
