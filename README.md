# FormCraft — PHP Form Builder

A dynamic PHP Form Builder that allows admins to create, manage, and publish forms without writing code.

---

## 🛠 Tech Stack

| Layer      | Technology                              |
|------------|------------------------------------------|
| Backend    | Core PHP 8.1+ (No Frameworks)           |
| Database   | MySQL 8.0+                               |
| Auth       | JWT (HS256, access + refresh tokens)    |
| Frontend   | Vanilla HTML / CSS / JavaScript         |
| Server     | Apache via Laragon                      |

---

## 📁 Folder Structure

```
php-form-builder/
├── .env                        # Environment variables
├── .htaccess                   # Apache rewrite rules
├── README.md
│
├── migrations/
│   └── 001_create_tables.sql   # Full DB schema + default admin seed
│
├── config/
│   ├── env.php                 # .env loader
│   └── database.php            # PDO singleton
│
├── includes/
│   ├── JWT.php                 # JWT encode/verify
│   └── helpers.php             # Response, Request, sanitize, requireAuth
│
├── api/                        # REST API (all routes via index.php)
│   ├── .htaccess
│   ├── index.php               # Router
│   ├── auth/
│   │   ├── login.php
│   │   ├── logout.php
│   │   ├── refresh.php
│   │   └── me.php
│   ├── forms/
│   │   ├── index.php           # GET    /forms
│   │   ├── store.php           # POST   /forms
│   │   ├── show.php            # GET    /forms/{id}
│   │   ├── update.php          # PUT    /forms/{id}
│   │   └── destroy.php         # DELETE /forms/{id}
│   ├── fields/
│   │   ├── index.php           # GET    /forms/{id}/fields
│   │   ├── store.php           # POST   /forms/{id}/fields
│   │   ├── update.php          # PUT    /forms/{id}/fields/{fid}
│   │   ├── destroy.php         # DELETE /forms/{id}/fields/{fid}
│   │   └── reorder.php         # PUT    /forms/{id}/fields/reorder
│   └── submissions/
│       ├── index.php           # GET    /forms/{id}/submissions
│       ├── store.php           # POST   /forms/{id}/submissions (public)
│       └── export.php          # GET    /forms/{id}/submissions/export
│
├── public/
│   ├── form.html               # Public-facing form renderer
│   └── form-data.php           # Public API — returns form + fields
│
├── admin/
│   ├── login.html              # Login page
│   ├── dashboard.html          # Forms list
│   ├── forms/
│   │   └── builder.html        # Drag-and-drop field builder
│   └── submissions/
│       └── index.html          # View + export submissions
│
└── assets/
    ├── css/admin.css
    └── js/api.js               # API client with auto token refresh
```

---

## ⚡ Setup Instructions

### 1. Place files
Copy the entire `php-form-builder/` folder into:
```
D:\laragon\www\
```
So the project root is:
```
D:\laragon\www\php-form-builder\
```

### 2. Configure environment
Edit `.env` in the project root:
```env
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=php_form_builder
DB_USERNAME=root
DB_PASSWORD=

APP_URL=http://localhost/php-form-builder

JWT_SECRET=change-this-to-a-random-32-char-string
JWT_EXPIRY=3600
JWT_REFRESH_EXPIRY=604800
```

### 3. Create the database
Open **phpMyAdmin** or Laragon's HeidiSQL and run:
```
D:\laragon\www\php-form-builder\migrations\001_create_tables.sql
```
This creates all tables and seeds the default admin user.

### 4. Enable mod_rewrite (Laragon)
Laragon enables `mod_rewrite` by default. If not:
- Open Laragon → Menu → Apache → httpd.conf
- Uncomment `LoadModule rewrite_module modules/mod_rewrite.so`
- Set `AllowOverride All` for your `www` directory

### 5. Start Laragon
Click **Start All** in Laragon.

### 6. Open the admin panel
```
http://localhost/php-form-builder/admin/login.html
https://priyeshsurti-phpformbuilder.infinityfree.me/php-form-builder/admin/login.html
```

**Default credentials:**
- Email: `admin@admin.com`
- Password: `Admin@1234`

---

## 🔑 REST API Reference

Base URL: `http://localhost/php-form-builder/api/index.php`

All protected routes require:
```
Authorization: Bearer <access_token>
```

### Auth
| Method | Endpoint           | Auth | Description            |
|--------|--------------------|------|------------------------|
| POST   | /auth/login        | No   | Login, returns tokens  |
| POST   | /auth/logout       | Yes  | Revoke refresh token   |
| POST   | /auth/refresh      | No   | Rotate tokens          |
| GET    | /auth/me           | Yes  | Get current user       |

### Forms
| Method | Endpoint     | Description        |
|--------|--------------|--------------------|
| GET    | /forms       | List all forms     |
| POST   | /forms       | Create form        |
| GET    | /forms/{id}  | Get form + fields  |
| PUT    | /forms/{id}  | Update form        |
| DELETE | /forms/{id}  | Delete form        |

### Fields
| Method | Endpoint                          | Description        |
|--------|-----------------------------------|--------------------|
| GET    | /forms/{id}/fields                | List fields        |
| POST   | /forms/{id}/fields                | Add field          |
| PUT    | /forms/{id}/fields/{fid}          | Update field       |
| DELETE | /forms/{id}/fields/{fid}          | Delete field       |
| PUT    | /forms/{id}/fields/reorder        | Reorder fields     |

### Submissions
| Method | Endpoint                              | Auth | Description          |
|--------|---------------------------------------|------|----------------------|
| POST   | /forms/{id}/submissions               | No   | Submit form (public) |
| GET    | /forms/{id}/submissions               | Yes  | List submissions     |
| GET    | /forms/{id}/submissions/export        | Yes  | Export CSV           |

---

## 🔒 Security Features

- **Prepared statements** everywhere — no raw SQL interpolation
- **Password hashing** with `password_hash()` / `password_verify()` (bcrypt, cost 12)
- **JWT authentication** with HS256 — access + refresh token rotation
- **Input sanitization** via `htmlspecialchars` + `strip_tags`
- **XSS protection** — all output escaped in frontend templates
- **SQL injection prevention** — PDO parameterized queries throughout
- **Security headers** — X-Content-Type-Options, X-Frame-Options, X-XSS-Protection
- **Directory listing disabled** via `.htaccess Options -Indexes`
- **Sensitive file blocking** — `.env`, `.sql`, `.json` blocked from web access

---

## 🎁 Bonus Features Implemented

- ✅ Drag-and-drop field reordering (canvas)
- ✅ CSV export of submissions
- ✅ JWT refresh token rotation
- ✅ Public form URL with shareable link + copy button
- ✅ Form active/inactive toggle
- ✅ Client-side + server-side validation
- ✅ Field types: Text, Email, Number, Textarea, Dropdown, Radio, Checkbox, File

---

## 🧪 Quick API Test (curl)

```bash
# Login
curl -X POST http://localhost/php-form-builder/api/index.php/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@admin.com","password":"Admin@1234"}'

# Create a form (replace TOKEN)
curl -X POST http://localhost/php-form-builder/api/index.php/forms \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer TOKEN" \
  -d '{"name":"Contact Us","description":"Get in touch"}'
```
