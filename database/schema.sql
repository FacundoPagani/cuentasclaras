PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE COLLATE NOCASE,
    password_hash TEXT NOT NULL,
    name TEXT NOT NULL,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    type TEXT NOT NULL CHECK (type IN ('common_expense', 'fixed_expense', 'credit_card')),
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (name, type)
);

CREATE TABLE IF NOT EXISTS daily_expenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    date TEXT NOT NULL,
    user_id INTEGER NOT NULL,
    category_id INTEGER NOT NULL,
    description TEXT,
    amount_cents INTEGER NOT NULL CHECK (amount_cents > 0),
    month_cycle TEXT NOT NULL CHECK (month_cycle GLOB '[0-9][0-9][0-9][0-9]-[0-9][0-9]'),
    created_by INTEGER NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS monthly_obligations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    month_cycle TEXT NOT NULL CHECK (month_cycle GLOB '[0-9][0-9][0-9][0-9]-[0-9][0-9]'),
    user_id INTEGER,
    category_id INTEGER NOT NULL,
    description TEXT NOT NULL,
    amount_cents INTEGER NOT NULL CHECK (amount_cents > 0),
    created_by INTEGER NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS credit_card_drafts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    purchase_date TEXT NOT NULL,
    user_id INTEGER NOT NULL,
    description TEXT NOT NULL,
    amount_cents INTEGER NOT NULL CHECK (amount_cents > 0),
    installments INTEGER NOT NULL DEFAULT 1 CHECK (installments >= 1),
    current_installment INTEGER NOT NULL DEFAULT 1 CHECK (current_installment >= 1),
    expected_statement_cycle TEXT NOT NULL CHECK (expected_statement_cycle GLOB '[0-9][0-9][0-9][0-9]-[0-9][0-9]'),
    reconciled INTEGER NOT NULL DEFAULT 0,
    created_by INTEGER NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS settlements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    month_cycle TEXT NOT NULL UNIQUE CHECK (month_cycle GLOB '[0-9][0-9][0-9][0-9]-[0-9][0-9]'),
    common_cycle TEXT NOT NULL CHECK (common_cycle GLOB '[0-9][0-9][0-9][0-9]-[0-9][0-9]'),
    closed_by INTEGER NOT NULL,
    closed_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_common_cents INTEGER NOT NULL,
    total_obligations_cents INTEGER NOT NULL,
    snapshot_json TEXT NOT NULL,
    FOREIGN KEY (closed_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS settlement_user_lines (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    settlement_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    common_paid_cents INTEGER NOT NULL,
    common_share_cents INTEGER NOT NULL,
    common_balance_cents INTEGER NOT NULL,
    obligation_share_cents INTEGER NOT NULL,
    final_transfer_cents INTEGER NOT NULL,
    FOREIGN KEY (settlement_id) REFERENCES settlements(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE (settlement_id, user_id)
);

CREATE INDEX IF NOT EXISTS idx_daily_expenses_cycle_user ON daily_expenses(month_cycle, user_id);
CREATE INDEX IF NOT EXISTS idx_obligations_cycle_user ON monthly_obligations(month_cycle, user_id);
CREATE INDEX IF NOT EXISTS idx_card_drafts_cycle_user ON credit_card_drafts(expected_statement_cycle, user_id);
CREATE INDEX IF NOT EXISTS idx_categories_type ON categories(type);

INSERT OR IGNORE INTO categories (name, type) VALUES
('Supermercado', 'common_expense'),
('Farmacia', 'common_expense'),
('Transporte', 'common_expense'),
('Comida', 'common_expense'),
('Varios', 'common_expense'),
('Alquiler / Cuota', 'fixed_expense'),
('Expensas', 'fixed_expense'),
('Servicios', 'fixed_expense'),
('Internet', 'fixed_expense'),
('Tarjeta de credito', 'credit_card');
