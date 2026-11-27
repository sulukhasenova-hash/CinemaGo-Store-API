CREATE TABLE categories (
    id INT NOT NULL PRIMARY KEY,
    name VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customer_groups (
    id INT NOT NULL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE preferences (
    id INT NOT NULL PRIMARY KEY,
    code VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id INT NOT NULL PRIMARY KEY,
    login VARCHAR(191) NOT NULL UNIQUE,
    display_name VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    group_id INT NOT NULL,
    CONSTRAINT fk_users_group FOREIGN KEY (group_id) REFERENCES customer_groups(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE subcategories (
    id INT NOT NULL PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    CONSTRAINT fk_subcategories_category FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
    id INT NOT NULL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DOUBLE NOT NULL,
    stock INT NOT NULL,
    product_type VARCHAR(100) NOT NULL,
    category_id INT NOT NULL,
    subcategory_id INT NOT NULL,
    image TEXT NOT NULL,
    is_active TINYINT NOT NULL DEFAULT 1,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id),
    CONSTRAINT fk_products_subcategory FOREIGN KEY (subcategory_id) REFERENCES subcategories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE group_categories (
    group_id INT NOT NULL,
    category_id INT NOT NULL,
    weight DOUBLE NOT NULL,
    PRIMARY KEY (group_id, category_id),
    CONSTRAINT fk_gc_group FOREIGN KEY (group_id) REFERENCES customer_groups(id),
    CONSTRAINT fk_gc_category FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE group_preferences (
    group_id INT NOT NULL,
    preference_id INT NOT NULL,
    weight DOUBLE NOT NULL,
    PRIMARY KEY (group_id, preference_id),
    CONSTRAINT fk_gp_group FOREIGN KEY (group_id) REFERENCES customer_groups(id),
    CONSTRAINT fk_gp_preference FOREIGN KEY (preference_id) REFERENCES preferences(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_preferences (
    user_id INT NOT NULL,
    preference_id INT NOT NULL,
    weight DOUBLE NOT NULL,
    PRIMARY KEY (user_id, preference_id),
    CONSTRAINT chk_user_pref_weight CHECK (weight BETWEEN -5 AND 5),
    CONSTRAINT fk_up_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_up_preference FOREIGN KEY (preference_id) REFERENCES preferences(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_preferences (
    product_id INT NOT NULL,
    preference_id INT NOT NULL,
    weight DOUBLE NOT NULL,
    PRIMARY KEY (product_id, preference_id),
    CONSTRAINT fk_pp_product FOREIGN KEY (product_id) REFERENCES products(id),
    CONSTRAINT fk_pp_preference FOREIGN KEY (preference_id) REFERENCES preferences(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_similarity (
    product_id INT NOT NULL,
    related_product_id INT NOT NULL,
    weight DOUBLE NOT NULL,
    PRIMARY KEY (product_id, related_product_id),
    CONSTRAINT fk_ps_product FOREIGN KEY (product_id) REFERENCES products(id),
    CONSTRAINT fk_ps_related FOREIGN KEY (related_product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE events (
    id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    event_id VARCHAR(80) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_events_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
    id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(32) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    total DOUBLE NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
    id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    unit_price DOUBLE NOT NULL,
    quantity INT NOT NULL,
    subtotal DOUBLE NOT NULL,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id),
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;