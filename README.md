<div align="center">

# ðŸš— VehicSmart

### Professional Vehicle Management System

*A modern, full-featured vehicle rental and sales management platform*

[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
[![TailwindCSS](https://img.shields.io/badge/Tailwind-CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)

[Features](#-features) â€¢ [Demo](#-demo) â€¢ [Installation](#-installation) â€¢ [Usage](#-usage) â€¢ [Tech Stack](#-tech-stack)

---

![VehicSmart Banner](https://via.placeholder.com/1200x400/3B82F6/ffffff?text=VehicSmart+-+Vehicle+Management+System)

</div>

---

## ðŸŒŸ Features

<table>
<tr>
<td width="50%">

### ðŸ‘¤ Client Portal
- âœ¨ **Secure Authentication** - Registration, Login, Password Reset with OTP
- ðŸ” **Advanced Search** - Filter by type, price, fuel, brand
- ðŸ“¸ **Dynamic Images** - BLOB storage with automatic illustrations
- ðŸ“Š **Dashboard** - Statistics and rental history
- ðŸš— **My Vehicles** - Personal fleet management
- ðŸ›’ **Rent/Buy** - Complete booking system

</td>
<td width="50%">

### ðŸ”§ Admin Panel
- ðŸ“Š **Dashboard** - Complete system overview
- ðŸš™ **Vehicle Management** - Full CRUD with image upload
- ðŸ‘¥ **Client Management** - User administration
- ðŸ–¼ï¸ **Image Gallery** - BLOB system with API
- ðŸ“¥ **Auto Import** - 3 methods (SVG, Placeholder, Unsplash)
- ðŸ” **Security** - Role-based access control
- ðŸ—ƒï¸ **DB Maintenance** - Migration and diagnostic tools

</td>
</tr>
</table>

---

## ðŸŽ¨ Advanced Image System

<div align="center">

| Feature | Description | Performance |
|---------|-------------|-------------|
| ðŸ’¾ **BLOB Storage** | Images stored in MySQL/MariaDB | Ultra Fast |
| ðŸŽ¨ **Auto SVG** | Generate custom illustrations | < 5 KB each |
| ðŸš€ **HTTP Cache** | 24-hour caching | 80% faster |
| ðŸ“± **Responsive** | All screen sizes | Mobile-first |
| ðŸ”„ **REST API** | `/api/vehicles/image.php?id=X` | Optimized |

</div>

---

## ðŸš€ Tech Stack

<div align="center">

### Backend
![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![PDO](https://img.shields.io/badge/PDO-Database-orange?style=for-the-badge)

### Frontend
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)

### Tools & Server
![XAMPP](https://img.shields.io/badge/XAMPP-FB7A24?style=for-the-badge&logo=xampp&logoColor=white)
![Apache](https://img.shields.io/badge/Apache-D22128?style=for-the-badge&logo=apache&logoColor=white)
![Git](https://img.shields.io/badge/Git-F05032?style=for-the-badge&logo=git&logoColor=white)

</div>

---

## ðŸ“ Project Structure

```
VehicSmart/
â”œâ”€â”€ ðŸ“‚ admin/                    # Admin Interface
â”‚   â”œâ”€â”€ dashboard.php            # Admin Dashboard
â”‚   â”œâ”€â”€ vehicles_manage.php      # Vehicle Management
â”‚   â”œâ”€â”€ vehicle_images.php       # Image Manager
â”‚   â””â”€â”€ import_vehicle_illustrations.php  # Auto Image Import
â”‚
â”œâ”€â”€ ðŸ“‚ client/                   # Client Interface
â”‚   â”œâ”€â”€ client_dashboard.php    # Client Dashboard
â”‚   â”œâ”€â”€ select_vehicle.php      # Vehicle Catalog
â”‚   â””â”€â”€ my_vehicles.php         # Personal Fleet
â”‚
â”œâ”€â”€ ðŸ“‚ api/                      # REST API
â”‚   â”œâ”€â”€ auth/                   # Authentication
â”‚   â””â”€â”€ vehicles/               # Vehicle Operations
â”‚       â””â”€â”€ image.php          # Image Endpoint
â”‚
â”œâ”€â”€ ðŸ“‚ config/                   # Configuration
â”‚   â”œâ”€â”€ config.example.php      # Config Template
â”‚   â”œâ”€â”€ database.example.php    # DB Template
â”‚   â”œâ”€â”€ ImageManager.php        # BLOB Manager Class
â”‚   â””â”€â”€ security.php            # Security Functions
â”‚
â”œâ”€â”€ ðŸ“‚ database/                 # Database
â”‚   â”œâ”€â”€ vehicsmart.sql          # Full Structure
â”‚   â””â”€â”€ migrations/             # SQL Migrations
â”‚
â””â”€â”€ ðŸ“‚ assets/                   # Static Assets
    â”œâ”€â”€ css/                    # Stylesheets
    â”œâ”€â”€ js/                     # JavaScript
    â””â”€â”€ images/                 # Images
```

---

## ðŸ“¦ Installation

### Prerequisites

<div align="center">

| Requirement | Version | Download |
|-------------|---------|----------|
| ðŸŸ¦ **PHP** | 8.2+ | [Download](https://www.php.net/downloads) |
| ðŸŸ§ **MySQL/MariaDB** | 8.0+ / 10.x | [Download](https://www.mysql.com/downloads/) |
| ðŸŸ¨ **XAMPP** (Optional) | Latest | [Download](https://www.apachefriends.org/) |
| ðŸŸ© **Git** | Latest | [Download](https://git-scm.com/) |

</div>

### Step 1: Clone Repository

```bash
# Clone the project
git clone https://github.com/your-username/VehicSmart.git

# Navigate to directory
cd VehicSmart
```

### Step 2: Database Setup

```bash
# Create database
mysql -u root -p
```

```sql
CREATE DATABASE vehicsmart CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;
```

```bash
# Import structure
mysql -u root -p vehicsmart < database/vehicsmart.sql
```

### Step 3: Configuration

```bash
# Copy configuration files
cp config/config.example.php config/config.php
cp config/database.example.php config/database.php
```

**Edit `config/config.php`:**
```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');        // Use 3307 for some XAMPP
define('DB_NAME', 'vehicsmart');
define('DB_USER', 'root');
define('DB_PASS', '');            // Your MySQL password
```

### Step 4: Image System Migration

Open in browser:
```
http://localhost/VehicSmart/admin/migrate_images_blob.php
```
âœ… Check the box and click **"Execute Migration"**

### Step 5: Create Admin Account

```sql
INSERT INTO users (username, email, password, role, is_verified)
VALUES ('admin', 'admin@vehicsmart.com', SHA2('Admin123!', 256), 'admin', 1);
```

### Step 6: Import Vehicle Illustrations (Optional)

Open in browser:
```
http://localhost/VehicSmart/admin/import_vehicle_illustrations.php
```
âœ… Choose **"Custom SVG Illustrations"** (Recommended)  
âœ… Click **"Import Illustrations"**

### Step 7: Access Application

<div align="center">

| Portal | URL | Role |
|--------|-----|------|
| ðŸ  **Homepage** | `http://localhost/VehicSmart/` | Public |
| ðŸ‘¤ **Client Portal** | `http://localhost/VehicSmart/client/` | Client |
| ðŸ”§ **Admin Panel** | `http://localhost/VehicSmart/admin/` | Admin |

</div>

---

## ðŸ” Default Accounts

<div align="center">

| Role | Email | Password | Access |
|------|-------|----------|--------|
| ðŸ‘¨â€ðŸ’¼ **Admin** | admin@vehicsmart.com | Admin123! | Full Access |
| ðŸ‘¤ **Client** | client@example.com | Client123! | Client Portal |

âš ï¸ **Important:** Change these passwords after installation!

</div>

---

## ðŸŽ¯ Usage

### For Administrators

<table>
<tr>
<td>

**1. Login**
```
admin/dashboard.php
```

</td>
<td>

**2. Add Vehicle**
```
admin/vehicle_form.php
```

</td>
</tr>
<tr>
<td>

**3. Manage Images**
```
admin/vehicle_images.php?vehicle_id=X
```

</td>
<td>

**4. View Gallery**
```
admin/vehicle_gallery.php
```

</td>
</tr>
</table>

### For Clients

<table>
<tr>
<td width="50%">

**ðŸ“ Register**
1. Go to `auth/register.php`
2. Fill registration form
3. Verify email with OTP
4. Login to your account

</td>
<td width="50%">

**ðŸš— Rent a Vehicle**
1. Browse `client/select_vehicle.php`
2. Use filters to find vehicle
3. Click on vehicle card
4. Click "Rent or Buy"

</td>
</tr>
</table>

---

## ðŸŽ¨ BLOB Image System

<div align="center">

### Why BLOB Storage?

| Advantage | Description |
|-----------|-------------|
| âœ… **No Upload Folder** | Everything in database |
| âœ… **Simple Backup** | One database backup |
| âœ… **Better Security** | Centralized access control |
| âœ… **Easy Deployment** | No file permissions issues |

</div>

### API Endpoint

```http
GET /api/vehicles/image.php?id={image_id}

Headers:
  Content-Type: image/jpeg|png|svg+xml
  Cache-Control: public, max-age=86400

Response: Binary image data
```

### ImageManager Class

```php
$imageManager = new ImageManager($db);

// Save image
$imageManager->saveVehicleImage($vehicle_id, $file_data, $is_primary);

// Get display image
$image = $imageManager->getVehicleDisplayImage($vehicle_id);

// Get image URL
$url = $imageManager->getImageUrl($image_id);
```

---

## ðŸ”§ Advanced Configuration

### Change MySQL Port

```php
// config/config.php
define('DB_PORT', '3307');  // For XAMPP with port 3307
```

### Increase Upload Limit

```php
// config/config.php
define('MAX_UPLOAD_SIZE', 10485760);  // 10 MB
```

### Enable Production Mode

```php
// config/config.php
define('APP_ENV', 'production');
```

---

## ðŸ› Troubleshooting

<details>
<summary><b>âŒ Error: "Connection failed"</b></summary>

**Solution:** Check credentials in `config/config.php`
```php
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
```
</details>

<details>
<summary><b>ðŸ–¼ï¸ Images not displaying</b></summary>

**Solution:** Run the migration
```
http://localhost/VehicSmart/admin/migrate_images_blob.php
```
</details>

<details>
<summary><b>ðŸ”Œ Wrong MySQL port</b></summary>

**Solution:** Update port in config
```php
define('DB_PORT', '3307');  // Change to your port
```
</details>

<details>
<summary><b>ðŸ”’ Session errors</b></summary>

**Solution:** Already fixed in latest version. Clear browser cache.
</details>

---

## ðŸ“Š Database Schema

<div align="center">

### Main Tables

| Table | Description | Relations |
|-------|-------------|-----------|
| `users` | Users (admin/clients) | â†’ rentals |
| `vehicles` | Available vehicles | â†’ rentals, images |
| `vehicle_categories` | Vehicle categories | â†’ vehicles |
| `vehicle_images_blob` | Images (BLOB) | â† vehicles |
| `rentals` | Rental history | â† users, vehicles |
| `maintenance_records` | Maintenance logs | â† vehicles |

</div>

---

## ðŸ”’ Security Features

<div align="center">

| Feature | Implementation |
|---------|----------------|
| ðŸ›¡ï¸ **SQL Injection** | Prepared Statements (PDO) |
| ðŸ” **Password Security** | SHA256 Hashing |
| ðŸŽ« **Session Security** | CSRF Tokens, Strict Validation |
| âœ… **Input Validation** | All inputs filtered |
| ðŸ‘® **Access Control** | Role-based (Admin/Client) |
| ðŸ”’ **HTTPS Ready** | SSL Configuration available |

</div>

---

## âš¡ Performance Metrics

<div align="center">

| Page | Load Time | Status |
|------|-----------|--------|
| ðŸ  Homepage | ~0.5s | âœ… Fast |
| ðŸ“Š Dashboard | ~1.0s | âœ… Good |
| ðŸ–¼ï¸ Images | ~0.3s | âœ… Fast |
| ðŸ” Search | ~0.8s | âœ… Good |

### Optimizations
ðŸš€ HTTP Caching (24h)  
ðŸš€ Lazy Loading  
ðŸš€ SVG Images (<5KB)  
ðŸš€ Database Indexes  
ðŸš€ PDO Prepared Statements

</div>

---

## ðŸš€ Deploying to GitHub

### Step-by-Step Guide

**Step 1: Initialize Git**
```bash
cd C:\xampp\htdocs\VehicSmart
git init
```

**Step 2: Add Files**
```bash
git add .
```

**Step 3: Commit**
```bash
git commit -m "Initial commit - VehicSmart v1.0"
```

**Step 4: Create GitHub Repository**
1. Go to [GitHub](https://github.com)
2. Click **"New Repository"**
3. Name it **"VehicSmart"**
4. Don't initialize with README
5. Click **"Create Repository"**

**Step 5: Connect to GitHub**
```bash
git remote add origin https://github.com/YOUR-USERNAME/VehicSmart.git
```

**Step 6: Push to GitHub**
```bash
# For main branch
git branch -M main
git push -u origin main

# OR for master branch
git branch -M master
git push -u origin master
```

**Step 7: Verify**
Visit: `https://github.com/YOUR-USERNAME/VehicSmart`

---

### ðŸ”— Share Your Project

**Direct Link:**
```
https://github.com/YOUR-USERNAME/VehicSmart
```

**Clone Command for Others:**
```bash
git clone https://github.com/YOUR-USERNAME/VehicSmart.git
```

**Add to Your Portfolio:**
```markdown
## VehicSmart - Vehicle Management System
ðŸš— Full-featured vehicle rental platform built with PHP & MySQL
[View Project](https://github.com/YOUR-USERNAME/VehicSmart)
```

---

<div align="center">

## ðŸŒŸ Show Your Support

**Give a â­ï¸ if this project helped you!**

### Made with â¤ï¸ by VehicSmart Team

---

**[â¬† Back to Top](#-vehicsmart)**

</div>

---

## âœ¨ FonctionnalitÃ©s Principales

### ðŸ‘¥ Espace Client
- ðŸ” **Authentification sÃ©curisÃ©e** - Inscription, connexion, rÃ©initialisation de mot de passe avec OTP
- ðŸš™ **Catalogue de vÃ©hicules** - Recherche avancÃ©e avec filtres (type, prix, carburant)
- ðŸ“¸ **Illustrations dynamiques** - Images stockÃ©es en base de donnÃ©es (BLOB)
- ðŸ“Š **Tableau de bord** - Statistiques et historique des locations
- ðŸ  **Gestion des vÃ©hicules personnels** - Suivi de sa propre flotte
- ðŸ›’ **Location/Achat** - SystÃ¨me complet de rÃ©servation

### ðŸ”§ Espace Administration
- ðŸ“Š **Dashboard administrateur** - Vue d'ensemble complÃ¨te
- ðŸš— **Gestion des vÃ©hicules** - CRUD complet avec upload d'images
- ðŸ‘¤ **Gestion des clients** - Liste, Ã©dition, suppression
- ðŸ–¼ï¸ **Galerie d'images** - SystÃ¨me BLOB avec API optimisÃ©e
- ðŸ“¥ **Import d'illustrations** - 3 mÃ©thodes (SVG, Placeholder, Unsplash)
- ðŸ” **SÃ©curitÃ© renforcÃ©e** - Sessions protÃ©gÃ©es, validation des accÃ¨s
- ðŸ—ƒï¸ **Maintenance base de donnÃ©es** - Outils de migration et diagnostic

### ðŸŽ¨ SystÃ¨me d'Images AvancÃ©
- ðŸ’¾ **Stockage BLOB** - Images en base de donnÃ©es MySQL/MariaDB
- ðŸŽ¨ **SVG personnalisÃ©s** - Illustrations gÃ©nÃ©rÃ©es automatiquement
- ðŸš€ **Cache HTTP** - Performance optimale (24h)
- ðŸ“± **Responsive** - Adaptatif tous Ã©crans
- ðŸ”„ **API REST** - Endpoint `/api/vehicles/image.php?id=X`

---

## ðŸ› ï¸ Technologies UtilisÃ©es

| Technologie | Version | Usage |
|------------|---------|-------|
| **PHP** | 8.2+ | Backend, logique mÃ©tier |
| **MySQL/MariaDB** | 8.0+ / 10.x | Base de donnÃ©es |
| **Tailwind CSS** | 2.2+ | Framework CSS |
| **JavaScript** | ES6+ | InteractivitÃ© frontend |
| **PDO** | - | Connexion base de donnÃ©es sÃ©curisÃ©e |
| **SVG** | - | Illustrations vectorielles |

---

## ðŸ“ Structure du Projet

```
VehicSmart/
â”œâ”€â”€ ðŸ“‚ admin/                    # Interface d'administration
â”‚   â”œâ”€â”€ dashboard.php            # Tableau de bord admin
â”‚   â”œâ”€â”€ vehicles_manage.php      # Gestion des vÃ©hicules
â”‚   â”œâ”€â”€ vehicle_form.php         # Formulaire ajout/Ã©dition vÃ©hicule
â”‚   â”œâ”€â”€ vehicle_images.php       # Gestion des images par vÃ©hicule
â”‚   â”œâ”€â”€ vehicle_gallery.php      # Galerie complÃ¨te
â”‚   â”œâ”€â”€ import_vehicle_illustrations.php  # Import automatique d'images
â”‚   â”œâ”€â”€ clients_manage.php       # Gestion des clients
â”‚   â”œâ”€â”€ maintenance.php          # Maintenance vÃ©hicules
â”‚   â””â”€â”€ database_maintenance.php # Maintenance base de donnÃ©es
â”‚
â”œâ”€â”€ ðŸ“‚ api/                      # API REST
â”‚   â”œâ”€â”€ auth/                    # Authentification (login, register, OTP)
â”‚   â”œâ”€â”€ vehicles/                # Gestion vÃ©hicules
â”‚   â”‚   â””â”€â”€ image.php           # Endpoint pour servir images BLOB
â”‚   â”œâ”€â”€ maintenance/             # Maintenance
â”‚   â””â”€â”€ config/                  # Configuration API
â”‚
â”œâ”€â”€ ðŸ“‚ auth/                     # Pages d'authentification
â”‚   â”œâ”€â”€ login.php               # Connexion
â”‚   â”œâ”€â”€ register.php            # Inscription
â”‚   â”œâ”€â”€ forgot_password.php     # Mot de passe oubliÃ©
â”‚   â””â”€â”€ verify_code.php         # VÃ©rification OTP
â”‚
â”œâ”€â”€ ðŸ“‚ client/                   # Interface client
â”‚   â”œâ”€â”€ client_dashboard.php    # Tableau de bord client
â”‚   â”œâ”€â”€ select_vehicle.php      # Catalogue de vÃ©hicules
â”‚   â”œâ”€â”€ my_vehicles.php         # Mes vÃ©hicules
â”‚   â”œâ”€â”€ rent_or_buy.php         # Location/Achat
â”‚   â””â”€â”€ rentals.php             # Historique locations
â”‚
â”œâ”€â”€ ðŸ“‚ config/                   # Configuration systÃ¨me
â”‚   â”œâ”€â”€ config.example.php      # Configuration exemple
â”‚   â”œâ”€â”€ database.example.php    # Connexion DB exemple
â”‚   â”œâ”€â”€ session.php             # Gestion des sessions
â”‚   â”œâ”€â”€ security.php            # Fonctions de sÃ©curitÃ©
â”‚   â”œâ”€â”€ helpers.php             # Fonctions utilitaires
â”‚   â””â”€â”€ ImageManager.php        # Classe de gestion images BLOB
â”‚
â”œâ”€â”€ ðŸ“‚ database/                 # Base de donnÃ©es
â”‚   â”œâ”€â”€ vehicsmart.sql          # Structure complÃ¨te
â”‚   â”œâ”€â”€ init_db.php             # Initialisation
â”‚   â””â”€â”€ migrations/             # Migrations SQL
â”‚       â””â”€â”€ add_blob_images.sql # Migration systÃ¨me BLOB
â”‚
â”œâ”€â”€ ðŸ“‚ assets/                   # Ressources statiques
â”‚   â”œâ”€â”€ css/                    # Styles CSS
â”‚   â”œâ”€â”€ js/                     # JavaScript
â”‚   â””â”€â”€ images/                 # Images statiques
â”‚
â”œâ”€â”€ .gitignore                   # Fichiers ignorÃ©s par Git
â”œâ”€â”€ README.md                    # Ce fichier
â””â”€â”€ index.php                    # Page d'accueil

```

---

## ðŸš€ Installation

### PrÃ©requis
- **XAMPP** / **WAMP** / **MAMP** ou tout serveur PHP
- **PHP** 8.2 ou supÃ©rieur
- **MySQL** 8.0+ ou **MariaDB** 10.x
- **Composer** (optionnel)
- Navigateur moderne (Chrome, Firefox, Edge)

### Ã‰tapes d'Installation

#### 1. Cloner le projet
```bash
git clone https://github.com/votre-username/VehicSmart.git
cd VehicSmart
```

#### 2. Configuration de la base de donnÃ©es
```bash
# CrÃ©er la base de donnÃ©es
mysql -u root -p
CREATE DATABASE vehicsmart CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;

# Importer la structure
mysql -u root -p vehicsmart < database/vehicsmart.sql
```

#### 3. Configuration des fichiers
```bash
# Copier les fichiers de configuration
cp config/config.example.php config/config.php
cp config/database.example.php config/database.php

# Ã‰diter config/config.php avec vos paramÃ¨tres
nano config/config.php
```

**Modifier les valeurs:**
```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');        // 3307 pour certains XAMPP
define('DB_NAME', 'vehicsmart');
define('DB_USER', 'root');
define('DB_PASS', '');            // Votre mot de passe MySQL
```

#### 4. Migration systÃ¨me d'images BLOB
```bash
# Ouvrir dans le navigateur:
http://localhost/VehicSmart/admin/migrate_images_blob.php

# Cocher la case et cliquer "ExÃ©cuter la Migration"
```

#### 5. CrÃ©er un compte administrateur
```bash
# Via phpMyAdmin ou ligne de commande:
INSERT INTO users (username, email, password, role, is_verified)
VALUES ('admin', 'admin@vehicsmart.com', SHA2('Admin123!', 256), 'admin', 1);
```

#### 6. Importer des illustrations (optionnel)
```bash
# Ouvrir dans le navigateur:
http://localhost/VehicSmart/admin/import_vehicle_illustrations.php

# Choisir "Illustrations SVG PersonnalisÃ©es" (recommandÃ©)
# Cliquer "Importer les Illustrations"
```

#### 7. AccÃ©der Ã  l'application
```
ðŸŒ Page d'accueil: http://localhost/VehicSmart/
ðŸ‘¤ Espace client:  http://localhost/VehicSmart/client/client_dashboard.php
ðŸ”§ Administration: http://localhost/VehicSmart/admin/dashboard.php
```

---

## ðŸ” Comptes par DÃ©faut

| RÃ´le | Email | Mot de passe | AccÃ¨s |
|------|-------|-------------|--------|
| **Admin** | admin@vehicsmart.com | Admin123! | Panel d'administration |
| **Client** | client@example.com | Client123! | Espace client |

âš ï¸ **Important:** Changez ces mots de passe aprÃ¨s l'installation!

---

## ðŸ“– Utilisation

### Administration
1. **Connexion:** `admin/dashboard.php`
2. **Ajouter un vÃ©hicule:** `admin/vehicle_form.php`
3. **GÃ©rer les images:** `admin/vehicle_images.php?vehicle_id=X`
4. **Voir la galerie:** `admin/vehicle_gallery.php`

### Client
1. **Inscription:** `auth/register.php`
2. **Connexion:** `auth/login.php`
3. **Parcourir vÃ©hicules:** `client/select_vehicle.php`
4. **RÃ©server:** Cliquer sur un vÃ©hicule â†’ "Rent or Buy"

---

## ðŸŽ¨ SystÃ¨me d'Images BLOB

### Avantages
- âœ… **Pas de dossier uploads** - Tout en base de donnÃ©es
- âœ… **Sauvegarde simplifiÃ©e** - Une seule base de donnÃ©es
- âœ… **SÃ©curitÃ© renforcÃ©e** - ContrÃ´le d'accÃ¨s centralisÃ©
- âœ… **PortabilitÃ© totale** - DÃ©ploiement facile

### API Endpoint
```php
GET /api/vehicles/image.php?id={image_id}

Headers:
  Content-Type: image/jpeg|png|svg+xml
  Cache-Control: public, max-age=86400

Response: Binary image data
```

### Classe ImageManager
```php
$imageManager = new ImageManager($db);

// Sauvegarder une image
$imageManager->saveVehicleImage($vehicle_id, $file_data, $is_primary);

// RÃ©cupÃ©rer l'image d'affichage
$image = $imageManager->getVehicleDisplayImage($vehicle_id);

// Obtenir l'URL de l'image
$url = $imageManager->getImageUrl($image_id);
```

---

## ðŸ”§ Configuration AvancÃ©e

### Modifier le port MySQL
Si vous utilisez XAMPP avec MySQL sur le port 3307:
```php
// config/config.php
define('DB_PORT', '3307');
```

### Augmenter la limite d'upload
```php
// config/config.php
define('MAX_UPLOAD_SIZE', 10485760); // 10 MB
```

### Activer le mode production
```php
// config/config.php
define('APP_ENV', 'production');
```

---

## ðŸ› DÃ©pannage

### Erreur: "Connection failed"
âœ… **Solution:** VÃ©rifiez les identifiants dans `config/config.php`

### Images ne s'affichent pas
âœ… **Solution:** ExÃ©cutez la migration `migrate_images_blob.php`

### Erreur: "Session already active"
âœ… **Solution:** DÃ©jÃ  corrigÃ© dans la derniÃ¨re version

### Port MySQL incorrect
âœ… **Solution:** Changez `DB_PORT` dans `config/config.php`

---

## ðŸ“Š Base de DonnÃ©es

### Tables Principales
- `users` - Utilisateurs (admin, clients)
- `vehicles` - VÃ©hicules disponibles
- `vehicle_categories` - CatÃ©gories de vÃ©hicules
- `vehicle_images_blob` - Images en BLOB
- `rentals` - Historique des locations
- `maintenance_records` - Entretiens

### Relations
```
users (1) ----< (N) rentals
vehicles (1) ----< (N) rentals
vehicles (1) ----< (N) vehicle_images_blob
vehicle_categories (1) ----< (N) vehicles
```

---

## ðŸ”’ SÃ©curitÃ©

- âœ… **Prepared Statements** - Protection contre injection SQL
- âœ… **Password Hashing** - SHA256 pour les mots de passe
- âœ… **Session Security** - Tokens CSRF, validation stricte
- âœ… **Input Validation** - Filtrage de toutes les entrÃ©es
- âœ… **Role-Based Access** - ContrÃ´le d'accÃ¨s par rÃ´le (admin/client)
- âœ… **HTTPS Ready** - Configuration SSL disponible

---

## ðŸš€ Performance

### Optimisations ImplÃ©mentÃ©es
- ðŸš€ **Cache HTTP** - 24h pour les images
- ðŸš€ **Lazy Loading** - Images chargÃ©es Ã  la demande
- ðŸš€ **SVG LÃ©ger** - <5 KB par illustration
- ðŸš€ **Index Database** - RequÃªtes optimisÃ©es
- ðŸš€ **PDO Prepared** - RequÃªtes prÃ©-compilÃ©es

### MÃ©triques
- Page d'accueil: ~0.5s
- Dashboard: ~1s
- Affichage images: ~0.3s

---

## ðŸ“ Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de dÃ©tails.

---

## ðŸ‘¨â€ðŸ’» Auteur

**VehicSmart Development Team**

---

## ðŸ¤ Contribution

Les contributions sont les bienvenues! Pour contribuer:

1. Fork le projet
2. CrÃ©er une branche (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

---

## ðŸ“ž Support

Pour toute question ou problÃ¨me:
- ðŸ“§ Email: support@vehicsmart.com
- ðŸ› Issues: [GitHub Issues](https://github.com/votre-username/VehicSmart/issues)

---

## ðŸ“… Changelog

### Version 1.0.0 (2025-11-05)
- âœ… SystÃ¨me de gestion de vÃ©hicules complet
- âœ… Authentification sÃ©curisÃ©e avec OTP
- âœ… SystÃ¨me d'images BLOB
- âœ… Import automatique d'illustrations
- âœ… Interface admin complÃ¨te
- âœ… Espace client moderne
- âœ… API REST optimisÃ©e

---

**â­ N'oubliez pas de mettre une Ã©toile si ce projet vous aide!**
   npm install -g typescript
   
   # Compile TypeScript to JavaScript
   tsc app.ts
   ```

3. **Development**:
   - For TypeScript changes, edit `app.ts` and recompile:
     ```bash
     tsc app.ts
     ```
   - For automatic compilation on file changes:
     ```bash
     tsc --watch app.ts
     ```

4. **Accessing the Site**:
   - Start your XAMPP server
   - Navigate to `http://localhost/VehicSmart/`

## Sections Overview

### 1. Hero Section
- Dark background (#1c1c1e)
- Bold white headline with accent color highlight
- Call-to-action button with hover effects

### 2. About Section
- Light background (#f4f4f5)
- Company mission and values
- Professional tone and messaging

### 3. Services Section
- Grid layout showcasing 6 core services
- Hover animations on service cards
- Comprehensive service descriptions

### 4. Vehicles Section
- 8-vehicle grid with placeholder images
- Hover scale animations
- Different vehicle categories

### 5. Testimonials Section
- 3 customer testimonials
- Social proof for credibility
- Clean, readable format

### 6. Contact Section
- Contact form with real-time validation
- Google Maps integration
- Company contact information

### 7. Footer
- Dark background matching navigation
- Centered white text
- Copyright and legal links

## TypeScript Features

The `app.ts` file includes:

- **Form Validation**: Real-time and submission validation
- **Scroll Animations**: Intersection Observer API for reveal animations
- **Smooth Scrolling**: Navigation link smooth scrolling
- **Type Safety**: Full TypeScript typing for all functions
- **Error Handling**: Comprehensive form error management

## Customization

### Colors
Modify the Tailwind configuration in `index.php`:
```javascript
tailwind.config = {
    theme: {
        extend: {
            colors: {
                'primary-dark': '#1c1c1e',
                'primary-light': '#f4f4f5',
                'accent': '#ff7849',
                'neutral': '#a1a1aa'
            }
        }
    }
}
```

### Content
- Update text content directly in `index.php`
- Modify form fields in the contact section
- Replace placeholder images with real vehicle photos

### Animations
- Adjust scroll reveal timing in `app.ts`
- Modify hover effects in the CSS classes
- Customize transition durations

## Browser Support

- Modern browsers supporting ES2020
- Internet Explorer 11+ (with polyfills)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Performance

- Tailwind CSS loaded from CDN
- Optimized images (placeholders used)
- Minimal JavaScript footprint
- Semantic HTML for accessibility

## License

This project is open source. Feel free to use and modify as needed.

## Support

For questions or issues, please contact the development team.

