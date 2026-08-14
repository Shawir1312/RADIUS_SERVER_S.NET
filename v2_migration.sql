-- MIGRATION S.NET V2 (PPPoE, GenieACS, Portal)

-- 1. GenieACS Server
CREATE TABLE IF NOT EXISTS genie_config (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) DEFAULT 'GenieACS',
    url        VARCHAR(255) DEFAULT 'http://localhost:7557',
    username   VARCHAR(100) DEFAULT '',
    password   VARCHAR(255) DEFAULT '',
    is_active  TINYINT(1)  DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Portal Customers (ONT linkage)
CREATE TABLE IF NOT EXISTS customers (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    customer_id      VARCHAR(30)  NOT NULL UNIQUE,
    password         VARCHAR(255) NOT NULL,
    full_name        VARCHAR(150) NOT NULL,
    phone            VARCHAR(20)  DEFAULT '',
    address          TEXT,
    genie_device_id  VARCHAR(255) DEFAULT '',
    device_serial    VARCHAR(100) DEFAULT '',
    device_brand     ENUM('FiberHome','CData','Huawei','ZTE','Unknown') DEFAULT 'Unknown',
    device_model     VARCHAR(100) DEFAULT '',
    ont_tag          VARCHAR(100) DEFAULT '',
    router_id        INT DEFAULT NULL,
    is_active        TINYINT(1) DEFAULT 1,
    notes            TEXT,
    created_by       INT DEFAULT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (router_id)  REFERENCES routers(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES admins(id)  ON DELETE SET NULL
);

-- 3. ONT Configs
CREATE TABLE IF NOT EXISTS ont_configs (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT NOT NULL,
    genie_device_id VARCHAR(255) NOT NULL,
    config_type     ENUM('wifi','wan','binding') NOT NULL,
    config_name     VARCHAR(150) DEFAULT '',
    config_data     TEXT NOT NULL COMMENT 'JSON semua parameter konfigurasi',
    push_status     ENUM('success','failed','pending') DEFAULT 'success',
    push_count      INT DEFAULT 1,
    last_pushed     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by      INT DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- 4. Audit Log (V2)
CREATE TABLE IF NOT EXISTS audit_log (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    actor_type ENUM('admin','customer') DEFAULT 'admin',
    actor_id   INT DEFAULT NULL,
    actor_name VARCHAR(150) DEFAULT '',
    action     VARCHAR(100) NOT NULL,
    target     VARCHAR(255) DEFAULT '',
    detail     TEXT,
    ip_address VARCHAR(50) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. PPPoE Customers
CREATE TABLE IF NOT EXISTS pppoe_customers (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    router_id       INT NOT NULL,
    pppoe_username  VARCHAR(100) NOT NULL,
    full_name       VARCHAR(150) NOT NULL DEFAULT '',
    phone           VARCHAR(25)  DEFAULT '',
    address         TEXT,
    profile         VARCHAR(100) DEFAULT '',
    -- Billing
    monthly_price   INT DEFAULT 0,
    due_day         TINYINT DEFAULT 1,          -- tanggal jatuh tempo per bulan (1-28)
    -- Status
    status          ENUM('active','isolated','suspended') DEFAULT 'active',
    isolated_at     DATETIME DEFAULT NULL,
    isolated_reason VARCHAR(255) DEFAULT '',
    -- Payment
    last_paid_at    DATE DEFAULT NULL,
    last_paid_amount INT DEFAULT 0,
    -- Meta
    notes           TEXT,
    created_by      INT DEFAULT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_router_user (router_id, pppoe_username),
    FOREIGN KEY (router_id)  REFERENCES routers(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
);

-- 6. PPPoE Payments
CREATE TABLE IF NOT EXISTS pppoe_payments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    customer_id     INT NOT NULL,
    amount          INT NOT NULL,
    payment_method  VARCHAR(50) DEFAULT 'cash',  -- cash/midtrans/transfer
    midtrans_order_id VARCHAR(100) DEFAULT NULL,
    midtrans_tx_id    VARCHAR(100) DEFAULT NULL,
    midtrans_status   VARCHAR(50)  DEFAULT NULL,
    period_month    TINYINT NOT NULL,            -- bulan pembayaran (1-12)
    period_year     SMALLINT NOT NULL,           -- tahun pembayaran
    paid_at         DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes           VARCHAR(255) DEFAULT '',
    created_by      INT DEFAULT NULL,
    FOREIGN KEY (customer_id) REFERENCES pppoe_customers(id) ON DELETE CASCADE
);

-- 7. PPPoE Settings
CREATE TABLE IF NOT EXISTS pppoe_settings (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    setting_key     VARCHAR(100) NOT NULL UNIQUE,
    setting_value   TEXT,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT IGNORE INTO pppoe_settings (setting_key, setting_value) VALUES
('midtrans_server_key', ''),
('midtrans_client_key', ''),
('midtrans_mode', 'sandbox'),              -- sandbox / production
('isolir_profile', 'isolir'),              -- nama profile MikroTik untuk isolir
('isolir_redirect_url', '/portal/isolir'), -- URL redirect saat isolir
('isolir_grace_days', '3'),               -- toleransi hari setelah jatuh tempo
('company_name', 'S.NET Internet'),
('company_phone', ''),
('company_address', '');
