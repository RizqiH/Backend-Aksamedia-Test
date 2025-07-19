# 🚀 Panduan Deployment Laravel API ke Wasmer.io

## Persiapan Sebelum Deploy

### 1. Install Wasmer CLI
```bash
# Windows (PowerShell)
iwr https://win.wasmer.io -useb | iex

# macOS/Linux
curl https://get.wasmer.io -sSfL | sh
```

### 2. Login ke Wasmer
```bash
# Buat akun di https://wasmer.io terlebih dahulu
wasmer login
```

### 3. Setup Database di Cloud
Anda perlu database MySQL di cloud. Rekomendasi:
- **PlanetScale** (gratis): https://planetscale.com
- **Railway** (gratis): https://railway.app  
- **Aiven** (gratis): https://aiven.io

Atau gunakan Wasmer MySQL (50MB gratis): akan otomatis tersedia saat deploy.

## 🔧 Konfigurasi Secrets

Setelah database siap, setup secrets di Wasmer:

```bash
# APP Key (dari .env Anda)
wasmer app secrets create APP_KEY "base64:HPKMgeG/wrD2EFLDqDBmKIbl0brqKe1QLz0f0mj7juM="

# Database Configuration (ganti dengan credentials cloud database)
wasmer app secrets create DB_HOST "your-database-host"
wasmer app secrets create DB_PORT "3306"
wasmer app secrets create DB_DATABASE "employeee_db"  
wasmer app secrets create DB_USERNAME "your-db-username"
wasmer app secrets create DB_PASSWORD "your-db-password"

# App URL (akan diupdate setelah deploy)
wasmer app secrets create APP_URL "https://backend-YOUR_USERNAME.wasmer.app"
```

## 📦 Deploy ke Wasmer

### 1. Test Lokal Dulu
```bash
# Test dengan Wasmer runtime
wasmer run .

# Buka di browser: http://localhost:8080/api/health
```

### 2. Deploy ke Production
```bash
# Deploy ke Wasmer Edge
wasmer deploy

# Follow prompts:
# - App owner: [your-username]  
# - App name: backend
# - Confirm deployment: yes
```

### 3. Setup Database Schema
```bash
# SSH ke app untuk migration (jika diperlukan)
wasmer app exec backend -- php artisan migrate --force
```

## 🌐 Update URL & Testing

Setelah deploy berhasil, Anda akan dapat URL seperti:
`https://backend-[username].wasmer.app`

### Test Endpoints:
```bash
# Health check
curl https://backend-[username].wasmer.app/api/health

# Login test  
curl -X POST https://backend-[username].wasmer.app/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@test.com","password":"password"}'
```

## 🔄 Update Aplikasi

Untuk update aplikasi:
```bash
# Setelah ada perubahan kode
wasmer deploy

# Akan otomatis update deployment yang sudah ada
```

## 🎯 Environment Variables yang Sudah Diset

Berdasarkan .env Anda, ini sudah dikonfigurasi:
- ✅ APP_KEY: Sudah ada dan valid
- ✅ Database MySQL: employeee_db
- ✅ Cache & Session: Dioptimalkan untuk serverless
- ✅ Logging: Stderr untuk Wasmer

## 📊 Monitoring & Logs

```bash
# Lihat logs aplikasi
wasmer app logs backend

# Lihat status app
wasmer app info backend

# Lihat metrics
wasmer app metrics backend
```

## 🐛 Troubleshooting

### Error "Database connection refused"
- Pastikan database cloud sudah running
- Cek kredensial database di secrets
- Test koneksi database dari lokal

### Error "APP_KEY not set"
- Jalankan: `wasmer app secrets create APP_KEY "base64:HPKMgeG/wrD2EFLDqDBmKIbl0brqKe1QLz0f0mj7juM="`

### Slow cold start
- InstaBoot sudah dikonfigurasi di `app.yaml`
- Endpoint `/api/health` dan `/api/login` akan di-pre-warm

## 💡 Tips Optimasi

1. **Gunakan array cache** untuk performance terbaik
2. **Set LOG_LEVEL=error** di production untuk logs minimal  
3. **Monitor usage** di dashboard Wasmer
4. **Custom domain** bisa disetup gratis di dashboard

## 🎉 Selesai!

URL API Anda: `https://backend-[username].wasmer.app`

Dokumentasi lengkap: https://docs.wasmer.io/edge/guides/laravel/ 
