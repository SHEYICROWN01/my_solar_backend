# Local Development Setup - Quick Reference

## Environment Configuration

### Current Setup (.env)
```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=solar_db
DB_USERNAME=postgres
DB_PASSWORD=Adeshile15.Com

MAIL_MAILER=log  # For local development - no real emails sent
FRONTEND_URL=http://localhost:5173
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:5173,127.0.0.1,127.0.0.1:5173,127.0.0.1:8000
```

## Starting the Server

```bash
# Start the Laravel development server
php artisan serve

# The server will run at: http://127.0.0.1:8000
```

## Common Commands

```bash
# Clear caches (run after .env changes)
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Run migrations
php artisan migrate

# Run seeders
php artisan db:seed --class=SuperAdminSeeder

# View routes
php artisan route:list

# Check logs
tail -f storage/logs/laravel.log
```

## Superadmin Login

**URL:** `http://localhost:5173/admin/login` (or your admin panel route)
**Email:** `admin@gifamz.com`
**Password:** `Admin@123`

⚠️ **Change password after first login!**

## API Endpoints

All endpoints are prefixed with `/api/`

### Authentication
- POST `/api/register` - User registration
- POST `/api/login` - User login
- POST `/api/admins/login` - Admin login

### Categories
- GET `/api/categories` - List all categories
- POST `/api/categories` - Create category
- PUT `/api/categories/{id}` - Update category
- DELETE `/api/categories/{id}` - Delete category

### Products
- GET `/api/products` - List all products
- POST `/api/products` - Create product (with images)
- PUT `/api/products/{id}` - Update product
- DELETE `/api/products/{id}` - Delete product

### Orders
- GET `/api/orders` - List all orders
- POST `/api/orders/initialize-payment` - Initialize payment
- POST `/api/orders/verify-payment` - Verify payment

## Frontend Configuration

Make sure your frontend `.env` has:

```env
VITE_API_URL=http://127.0.0.1:8000/api
# OR
VITE_API_URL=http://localhost:8000/api
```

**Important:** No trailing slash in API URL!

## Troubleshooting

### Server Won't Start
```bash
# Check if port 8000 is in use
lsof -i :8000

# Kill any process using port 8000
pkill -f "php artisan serve"

# Start fresh
php artisan serve
```

### Connection Refused Error
1. Make sure the server is running: `php artisan serve`
2. Check server is listening: `lsof -i :8000`
3. Verify frontend is using correct API URL
4. Check CORS settings in `config/cors.php`

### Mail Timeout Errors
- For local development, use `MAIL_MAILER=log`
- Emails will be written to `storage/logs/laravel.log`
- For production, configure valid SMTP credentials

### Database Connection Issues
```bash
# Test PostgreSQL connection
psql -U postgres -c "SELECT 1"

# List databases
psql -U postgres -l

# Create database if needed
createdb -U postgres solar_db
```

### Image Upload Issues
1. Verify PHP limits: `php -i | grep -E "upload_max_filesize|post_max_size"`
2. Should see:
   - `upload_max_filesize` = 10M
   - `post_max_size` = 50M
3. Check storage link: `ls -la public/storage`
4. Verify products directory: `ls -la storage/app/public/products`

### CORS Issues
1. Check `FRONTEND_URL` in `.env` matches your frontend URL
2. Verify `SANCTUM_STATEFUL_DOMAINS` includes your frontend domain
3. Run `php artisan config:clear` after changes
4. Check browser console for specific CORS errors

## File Upload Limits

- **Max file size:** 10MB per file
- **Max POST size:** 50MB total
- **Max images per product:** 10 images
- **Allowed formats:** jpeg, png, jpg, gif, webp
- **Backend validation:** 5MB per image

## Development vs Production

### Local Development
```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
MAIL_MAILER=log
FRONTEND_URL=http://localhost:5173
```

### Production (Railway/Vercel)
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.ggtl.com
MAIL_MAILER=smtp
FRONTEND_URL=https://ggtl.com
SANCTUM_STATEFUL_DOMAINS=ggtl.com,www.ggtl.com,api.ggtl.com
```

## Testing API Endpoints

### Test Registration
```bash
curl -X POST http://127.0.0.1:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Test",
    "last_name": "User",
    "email": "test@example.com",
    "phone_number": "+2348012345678",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### Test Login
```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

### Test Admin Login
```bash
curl -X POST http://127.0.0.1:8000/api/admins/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@gifamz.com",
    "password": "Admin@123"
  }'
```

## Need Help?

1. Check `storage/logs/laravel.log` for detailed errors
2. Enable debug mode: Set `APP_DEBUG=true` in `.env`
3. Clear all caches: `php artisan optimize:clear`
4. Review this guide's troubleshooting section
5. Check `TROUBLESHOOTING.md` for detailed issue resolution

## Quick Recovery

If something goes wrong, run these commands:

```bash
# Stop the server
pkill -f "php artisan serve"

# Clear everything
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Restart
php artisan serve
```
