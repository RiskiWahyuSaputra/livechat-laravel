# Entity Relationship Diagram (ERD) — LiveChat Laravel

## 📊 Diagram Relasi Database

```mermaid
erDiagram
    %% ==================== CORE MODULE: USER & CUSTOMER ====================
    USERS {
        bigint id PK
        string name
        string email UK
        timestamp email_verified_at "nullable"
        string contact
        string origin
        string password
        boolean is_online
        boolean is_blocked
        string registration_token "nullable"
        string remember_token
        timestamp created_at
        timestamp updated_at
    }

    CUSTOMERS {
        bigint id PK
        string session_token UK "indexed"
        string name
        string contact
        string origin
        boolean is_blocked
        timestamp created_at
        timestamp updated_at
    }

    %% ==================== CORE MODULE: ADMIN & ROLE & DIVISION ====================
    ADMINS {
        bigint id PK
        string username UK
        string email UK
        string password
        bigint role_id FK "nullable"
        string role "super_admin | agent"
        boolean is_superadmin
        string permissions "json nullable"
        string status "online | busy | offline"
        int max_active_chats "default 5"
        int level "nullable"
        string division "nullable → divisions.slug"
        string remember_token
        timestamp created_at
        timestamp updated_at
    }

    ROLES {
        bigint id PK
        string name
        string slug UK
        text description "nullable"
        int level "nullable"
        timestamp created_at
        timestamp updated_at
    }

    DIVISIONS {
        bigint id PK
        string name
        string slug UK
        text description "nullable"
        bigint supervisor_id FK "nullable → admins.id"
        timestamp created_at
        timestamp updated_at
    }

    %% ==================== CORE MODULE: CONVERSATION ====================
    CONVERSATIONS {
        bigint id PK
        bigint user_id FK "→ users.id"
        bigint admin_id FK "nullable → admins.id"
        string status "pending | active | closed | queued"
        string bot_phase "nullable"
        int queue_position "nullable"
        string problem_category "nullable"
        text summary "nullable"
        string feedback_status "nullable"
        timestamp feedback_requested_at "nullable"
        int selected_menu_id "nullable"
        int reminder_count "nullable"
        timestamp last_message_at "nullable"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "nullable softDeletes"
    }

    MESSAGES {
        bigint id PK
        bigint conversation_id FK "→ conversations.id"
        bigint sender_id "polymorphic"
        string sender_type "user | admin | system"
        string message_type "text | image | file | whisper"
        text content
        boolean is_read
        timestamp created_at
        timestamp updated_at
    }

    CONVERSATION_RATINGS {
        bigint id PK
        bigint conversation_id FK UK "→ conversations.id"
        bigint user_id FK "nullable → users.id"
        bigint admin_id FK "nullable → admins.id"
        int rating "tinyint 1-5"
        text comment "nullable"
        timestamp created_at
        timestamp updated_at
    }

    TAGS {
        bigint id PK
        string name UK
        string color "nullable"
        timestamp created_at
        timestamp updated_at
    }

    CONVERSATION_TAG {
        bigint id PK
        bigint conversation_id FK "→ conversations.id"
        bigint tag_id FK "→ tags.id"
        timestamp created_at
        timestamp updated_at
    }

    %% ==================== MODULE: BOT / AI ====================
    BOT_MENUS {
        bigint id PK
        bigint parent_id FK "nullable → bot_menus.id self"
        string flow_type "nullable"
        string label
        text message_response "nullable"
        string action_type "default: submenu"
        string action_value "nullable"
        int order_index "default 0"
        timestamp created_at
        timestamp updated_at
    }

    %% ==================== MODULE: INTERNAL ADMIN CHAT ====================
    INTERNAL_CONVERSATIONS {
        bigint id PK
        bigint user_one_id FK "→ admins.id"
        bigint user_two_id FK "→ admins.id"
        timestamp last_message_at "nullable"
        timestamp created_at
        timestamp updated_at
    }

    INTERNAL_MESSAGES {
        bigint id PK
        bigint internal_conversation_id FK "→ internal_conversations.id"
        bigint sender_id FK "→ admins.id"
        text content
        string message_type "default: text"
        boolean is_read
        timestamp created_at
        timestamp updated_at
    }

    ADMIN_CONVERSATIONS {
        bigint id PK
        bigint admin_1_id FK "→ admins.id"
        bigint admin_2_id FK "→ admins.id"
        timestamp last_message_at "nullable"
        timestamp created_at
        timestamp updated_at
    }

    ADMIN_MESSAGES {
        bigint id PK
        bigint admin_conversation_id FK "→ admin_conversations.id"
        bigint sender_id FK "→ admins.id"
        string message_type
        text content
        boolean is_read
        timestamp created_at
        timestamp updated_at
    }

    %% ==================== MODULE: SUPPORTING ====================
    QUICK_REPLIES {
        bigint id PK
        string title
        string command "nullable"
        text content
        timestamp created_at
        timestamp updated_at
    }

    CATEGORIES {
        bigint id PK
        string name
        string slug UK
        string icon_image "nullable"
        boolean is_featured
        timestamp created_at
        timestamp updated_at
    }

    PRODUCTS {
        bigint id PK
        bigint category_id FK "→ categories.id"
        string name
        string price "decimal 15,2"
        text description "nullable"
        string image "nullable"
        timestamp created_at
        timestamp updated_at
    }

    CHATS {
        bigint id PK
        string whatsapp_id
        string name "nullable"
        text message
        text response "nullable"
        timestamp created_at
        timestamp updated_at
    }

    SETTINGS {
        bigint id PK
        string key UK
        text value "nullable"
        string group "default: general"
        timestamp created_at
        timestamp updated_at
    }

    %% ==================== RELATIONSHIPS ====================

    %% --- User Relations ---
    USERS ||--o{ CONVERSATIONS : "memiliki"
    USERS ||--o{ CONVERSATION_RATINGS : "memberi rating"

    %% --- Customer Relations (standalone, no FK to other tables) ---

    %% --- Admin Relations ---
    ADMINS ||--o{ CONVERSATIONS : "menangani"
    ADMINS ||--o{ CONVERSATION_RATINGS : "di-rating"
    ADMINS }o--|| ROLES : "memiliki role"
    ADMINS ||--o{ INTERNAL_CONVERSATIONS : "sebagai user_one"
    ADMINS ||--o{ INTERNAL_CONVERSATIONS : "sebagai user_two"
    ADMINS ||--o{ INTERNAL_MESSAGES : "mengirim internal"
    ADMINS ||--o{ ADMIN_CONVERSATIONS : "sebagai admin_1"
    ADMINS ||--o{ ADMIN_CONVERSATIONS : "sebagai admin_2"
    ADMINS ||--o{ ADMIN_MESSAGES : "mengirim admin message"
    ADMINS ||--o{ DIVISIONS : "mensupervisi"

    %% --- Division Relations ---
    DIVISIONS ||--o{ ADMINS : "memiliki agent (via slug)"

    %% --- Conversation Relations ---
    CONVERSATIONS ||--o{ MESSAGES : "berisi"
    CONVERSATIONS ||--|| CONVERSATION_RATINGS : "memiliki rating"
    CONVERSATIONS }o--o{ TAGS : "di-tag (via conversation_tag)"

    %% --- Pivot: Conversation <-> Tag ---
    CONVERSATION_TAG }o--|| CONVERSATIONS : "milik"
    CONVERSATION_TAG }o--|| TAGS : "milik"

    %% --- Internal Chat Relations ---
    INTERNAL_CONVERSATIONS ||--o{ INTERNAL_MESSAGES : "berisi"
    INTERNAL_CONVERSATIONS }o--|| ADMINS : "user_one_id"
    INTERNAL_CONVERSATIONS }o--|| ADMINS : "user_two_id"

    %% --- Admin Chat Relations ---
    ADMIN_CONVERSATIONS ||--o{ ADMIN_MESSAGES : "berisi"
    ADMIN_CONVERSATIONS }o--|| ADMINS : "admin_1_id"
    ADMIN_CONVERSATIONS }o--|| ADMINS : "admin_2_id"

    %% --- Bot Menu Relations ---
    BOT_MENUS ||--o{ BOT_MENUS : "parent-child (recursive)"

    %% --- Category & Product ---
    CATEGORIES ||--o{ PRODUCTS : "memiliki"
```

---

## 📋 Daftar Tabel & Deskripsi

| # | Tabel | Deskripsi |
|---|-------|-----------|
| 1 | **users** | Pelanggan utama (end user) yang melakukan chat |
| 2 | **customers** | Data customer via session (terpisah dari users) |
| 3 | **admins** | Agent / Admin Customer Service |
| 4 | **roles** | Role & permission untuk admin |
| 5 | **divisions** | Divisi/tim admin |
| 6 | **conversations** | Percakapan inti antara user dan admin |
| 7 | **messages** | Pesan-pesan dalam percakapan |
| 8 | **conversation_ratings** | Rating setelah chat selesai (1:1 dgn conversation) |
| 9 | **tags** | Label untuk tagging percakapan |
| 10 | **conversation_tag** | Pivot M:N conversations ↔ tags |
| 11 | **bot_menus** | Menu chatbot (struktur tree/pohon) |
| 12 | **internal_conversations** | Chat antar admin (via internal) |
| 13 | **internal_messages** | Pesan internal antar admin |
| 14 | **admin_conversations** | Chat antar admin (alternate) |
| 15 | **admin_messages** | Pesan admin_conversations |
| 16 | **quick_replies** | Template balasan cepat untuk admin |
| 17 | **categories** | Kategori produk/layanan |
| 18 | **products** | Produk/layanan |
| 19 | **chats** | Log chat dari WhatsApp |
| 20 | **settings** | Pengaturan aplikasi key-value |

---

## 🔗 Ringkasan Relasi

| Relasi | Type | Penjelasan |
|--------|------|------------|
| `users` → `conversations` | 1:N | Satu user bisa punya banyak percakapan |
| `admins` → `conversations` | 1:N | Satu admin bisa menangani banyak percakapan |
| `roles` → `admins` | 1:N | Satu role bisa dimiliki banyak admin |
| `divisions` → `admins` | 1:N | Satu divisi memiliki banyak admin (via `slug`) |
| `conversations` → `messages` | 1:N | Satu percakapan berisi banyak pesan |
| `conversations` → `conversation_ratings` | 1:1 | Satu percakapan memiliki tepat satu rating |
| `conversations` ↔ `tags` | M:N | Banyak percakapan bisa punya banyak tag (via `conversation_tag`) |
| `bot_menus` → `bot_menus` | Recursive | Menu bot bisa punya sub-menu (parent_id) |
| `admins` ↔ `admins` | M:N | Chat internal antar admin (via `internal_conversations` & `admin_conversations`) |
| `categories` → `products` | 1:N | Satu kategori memiliki banyak produk |

---

## ⚠️ Catatan Penting

1. **Duplikasi Internal Chat**: `internal_conversations` + `internal_messages` mirip dengan `admin_conversations` + `admin_messages`. Keduanya adalah fitur chat admin-to-admin yang bisa di-refactor.

2. **Polymorphic Sender**: Kolom `sender_id` di `messages` bersifat polymorphic — bisa merujuk ke `users.id` atau `admins.id`, ditentukan oleh kolom `sender_type`.

3. **Relasi Division via Slug**: `admins.division` merujuk ke `divisions.slug` (bukan ID), sehingga perlu dijaga konsistensi slug-nya.

4. **Dua Tabel Customer**: Ada `users` dan `customers` — `users` untuk user terdaftar (dengan password/login), `customers` untuk pengunjung anonymous via session token.
