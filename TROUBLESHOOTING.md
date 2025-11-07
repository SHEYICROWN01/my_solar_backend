# Troubleshooting Guide

## Recent Fixes Applied

### 1. User Registration Connection Refused & Timeout Issues

**Problem:** 
- `ERR_CONNECTION_REFUSED` when trying to register users
- `Maximum execution time of 30 seconds exceeded` in SMTP mail transport

**Root Cause:** 
- Mail configuration was set to `MAIL_MAILER=smtp` but the SMTP server credentials were not valid/accessible
- This caused the registration process to hang for 30+ seconds trying to send verification emails
- Server would crash and become unreachable

**Solution Applied:**
- Changed `MAIL_MAILER` from `smtp` to `log` for local development
- Updated `FRONTEND_URL` to `http://localhost:5173`
- Updated `SANCTUM_STATEFUL_DOMAINS` to include localhost variants
- Cleared configuration cache with `php artisan config:clear`

**Current Mail Settings:**
```env
MAIL_MAILER=log  # Emails will be logged to storage/logs/laravel.log
FRONTEND_URL=http://localhost:5173
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:5173,127.0.0.1,127.0.0.1:5173,127.0.0.1:8000
```

**Note:** For production, change `MAIL_MAILER` back to `smtp` and provide valid SMTP credentials.

### 2. Product Image Upload Issues (422 Error)

**Problem:** Getting 422 errors when uploading product images, specifically "images.4 failed to upload"

**Root Cause:** PHP upload limits were too restrictive
- `upload_max_filesize` was 2MB
- `post_max_size` was 8MB

**Solution Applied:**
- Updated `/opt/homebrew/etc/php/8.3/php.ini`:
  ```ini
  upload_max_filesize = 10M
  post_max_size = 50M
  ```
- Created `/public/.user.ini` as backup:
  ```ini
  upload_max_filesize = 10M
  post_max_size = 50M
  max_file_uploads = 20
  memory_limit = 256M
  ```
- Created `storage/app/public/products` directory for uploads
- Linked storage: `php artisan storage:link`

**Note:** After changing php.ini, always restart the PHP server:
```bash
# Stop the server (Ctrl+C or)
pkill -f "php artisan serve"

# Start it again
php artisan serve
```

### 2. Category Creation (404 Error)

**Problem:** POST request to `api//categories` returned 404 (double slash issue)

**Solution:** Frontend issue - need to fix URL construction in `AddCategoryDialog.tsx`
- Either remove trailing slash from `API_URL` 
- Or remove leading slash from endpoint paths

**Backend Status:** ✅ Working correctly - tested with curl successfully

### 3. Development Environment Setup

**Changes made for local development:**
- Changed `APP_ENV` from `production` to `local`
- Changed `APP_DEBUG` from `false` to `true`
- Changed `APP_URL` from `https://api.ggtl.com` to `http://127.0.0.1:8000`
- Set database to localhost (127.0.0.1:5432)

## Testing Endpoints

### Test Category Creation
```bash
curl -X POST http://127.0.0.1:8000/api/categories \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"name":"Test Category","slug":"test-category","description":"Test"}'
```

### Test Product Creation (without images)
```bash
curl -X POST http://127.0.0.1:8000/api/products \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Test Product",
    "category_id": 1,
    "price": 99.99,
    "stock": 10,
    "description": "Test product description",
    "power": "100W",
    "warranty": "2 years"
  }'
```

## Common Issues

### File Upload Errors
1. Check PHP limits: `php -i | grep -E "upload_max_filesize|post_max_size"`
2. Verify storage link: `ls -la public/storage`
3. Check directory permissions: `ls -la storage/app/public/products`
4. Review Laravel logs: `tail -f storage/logs/laravel.log`

### Database Connection Issues
1. Verify PostgreSQL is running: `psql -U postgres -c "SELECT 1"`
2. Check database exists: `psql -U postgres -l | grep solar_db`
3. Test connection in .env file

### CORS Issues
If frontend can't connect:
1. Check `FRONTEND_URL` in .env
2. Verify CORS configuration in `config/cors.php`
3. Check browser console for CORS errors

## Superadmin Account

**Email:** admin@gifamz.com  
**Password:** Admin@123  

**Please change the password after first login!**

## Server Commands

```bash
# Start development server
php artisan serve

# Run migrations
php artisan migrate

# Create storage link
php artisan storage:link

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# View routes
php artisan route:list

# Check logs
tail -f storage/logs/laravel.log
```

## File Upload Limits Summary

Current settings (updated):
- **Max file upload size:** 10MB per file
- **Max POST size:** 50MB total
- **Max files:** 20 files
- **Supported formats:** jpeg, png, jpg, gif, webp

Backend validation (ProductController):
- Max images per product: 10
- Max image size: 5MB per image (5120KB)
