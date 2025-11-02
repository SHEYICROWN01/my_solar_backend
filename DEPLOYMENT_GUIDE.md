# Production Deployment Guide - Gifamz Store

## Architecture Overview
- **Frontend**: https://ggtl.com (Vercel)
- **Backend API**: https://api.ggtl.com (Railway)
- **Database**: PostgreSQL (Railway managed)

## Phase 1: Backend Deployment (Railway)

### Step 1: Deploy to Railway
1. Go to [Railway.app](https://railway.app) and sign up/login
2. Click "New Project" → "Deploy from GitHub repo"
3. Connect your GitHub account and select: `SHEYICROWN01/g-tech-solar-system-backend`
4. Railway will auto-detect Laravel and set up the environment

### Step 2: Add PostgreSQL Database
1. In your Railway project dashboard, click "New Service"
2. Select "Database" → "PostgreSQL"
3. Railway will automatically create database and provide connection variables

### Step 3: Configure Environment Variables
Add these environment variables in Railway dashboard:

```
APP_NAME=Gifamz Store
APP_ENV=production
APP_KEY=base64:l10TVwdbOUekdYskYxqFteUrsvEF8rK9lGIIe87R++k=
APP_DEBUG=false
APP_URL=https://api.ggtl.com

DB_CONNECTION=pgsql
# Railway auto-provides: PGHOST, PGPORT, PGDATABASE, PGUSER, PGPASSWORD

MAIL_MAILER=smtp
MAIL_HOST=quovatech.com
MAIL_PORT=587
MAIL_USERNAME=support@quovatech.com
MAIL_PASSWORD=Adeshile15.Com
MAIL_FROM_ADDRESS=support@quovatech.com
MAIL_FROM_NAME="Gifamz Store"

PAYSTACK_PUBLIC_KEY=pk_test_a88eed026b20662ed411de5ab2351008f35417d9
PAYSTACK_SECRET_KEY=sk_test_2361379376b2c63bd99fb2c448c15efe8b672524

FRONTEND_URL=https://ggtl.com
SANCTUM_STATEFUL_DOMAINS=ggtl.com,www.ggtl.com,api.ggtl.com
```

### Step 4: Custom Domain Setup
1. In Railway project → Settings → Custom Domain
2. Add domain: `api.ggtl.com`
3. Railway will provide CNAME record to add to Namecheap

## Phase 2: Domain Configuration (Namecheap)

### Frontend Domain (ggtl.com)
1. Login to Namecheap → Domain List → Manage
2. Go to Advanced DNS
3. Add these records:
```
Type: CNAME | Host: www | Value: cname.vercel-dns.com
Type: A | Host: @ | Value: 76.76.19.61 (Vercel IP)
```

### API Subdomain (api.ggtl.com)
4. Add Railway CNAME record:
```
Type: CNAME | Host: api | Value: [Railway-provided-CNAME]
```

## Phase 3: Frontend Configuration

### Update API Base URL
In your frontend project, update the API base URL to:
```javascript
const API_BASE_URL = 'https://api.ggtl.com'
```

### Vercel Custom Domain
1. In Vercel dashboard → Project Settings → Domains
2. Add custom domain: `ggtl.com` and `www.ggtl.com`
3. Follow Vercel's DNS setup instructions

## Phase 4: Database Migration

### Option A: Fresh Migration (Recommended)
The Procfile will automatically run migrations on deployment.

### Option B: Import Existing Data
1. Export local PostgreSQL data:
```bash
pg_dump -h localhost -U postgres -d gifamz_store > backup.sql
```

2. Import to Railway database:
```bash
psql [RAILWAY_DATABASE_URL] < backup.sql
```

## Phase 5: Testing & Verification

### Test API Endpoints
- https://api.ggtl.com/api/health
- https://api.ggtl.com/api/products
- https://api.ggtl.com/api/customer-addresses

### Test Frontend Integration
- Ensure frontend can communicate with API
- Test authentication flows
- Verify CORS is working properly

## Security Checklist
- ✅ APP_DEBUG=false in production
- ✅ Strong APP_KEY generated
- ✅ HTTPS enforced on both domains
- ✅ CORS properly configured
- ✅ Database credentials secured
- ✅ API rate limiting enabled

## Monitoring & Maintenance
- Railway provides built-in logs and metrics
- Set up uptime monitoring for both domains
- Regular database backups (Railway handles this)
- Monitor API response times

## Troubleshooting
- Check Railway deployment logs for errors
- Verify DNS propagation (can take up to 48 hours)
- Test API endpoints individually
- Check Vercel deployment logs for frontend issues