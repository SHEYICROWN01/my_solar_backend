# Gmail SMTP Setup Guide for Laravel

## Option 1: Using Gmail App Passwords (Recommended)

### Prerequisites
- A Gmail account
- 2-Step Verification enabled on your Google account

### Step-by-Step Instructions

#### Step 1: Enable 2-Step Verification
1. Go to your Google Account: https://myaccount.google.com/
2. Click on **Security** in the left sidebar
3. Under "Signing in to Google", click on **2-Step Verification**
4. Follow the prompts to enable it (you'll need your phone)

#### Step 2: Generate App Password
1. After enabling 2-Step Verification, go back to **Security**
2. Under "Signing in to Google", click on **App passwords**
   - URL: https://myaccount.google.com/apppasswords
3. You might need to sign in again
4. In the "Select app" dropdown, choose **Mail**
5. In the "Select device" dropdown, choose **Other (Custom name)**
6. Type a name like "Laravel Solar App" or "My Solar Backend"
7. Click **Generate**
8. Google will show you a 16-character password (like: `abcd efgh ijkl mnop`)
9. **IMPORTANT:** Copy this password immediately - you won't be able to see it again!

#### Step 3: Update Your .env File

Open `/Users/quovatech/my_solar_backend/.env` and update the mail settings:

```env
# Gmail SMTP Settings
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-16-char-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Gifamz Store"
```

**Replace:**
- `your-email@gmail.com` with your actual Gmail address
- `your-16-char-app-password` with the password from Step 2 (remove spaces)

**Example:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=johndoe@gmail.com
MAIL_PASSWORD=abcdefghijklmnop
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=johndoe@gmail.com
MAIL_FROM_NAME="Gifamz Store"
```

#### Step 4: Clear Config Cache & Restart Server

```bash
# Clear configuration cache
php artisan config:clear
php artisan cache:clear

# Restart the server
# First stop it (Ctrl+C or kill the process)
pkill -f "php artisan serve"

# Then start it again
php artisan serve
```

#### Step 5: Test Email Sending

```bash
# Test registration with a real email
curl -X POST http://127.0.0.1:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Test",
    "last_name": "User",
    "email": "your-email@gmail.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

Check your Gmail inbox for the verification email!

---

## Option 2: Using OAuth2 (More Secure, More Complex)

This is more secure but requires more setup. Only use this if you want production-level security.

### Step 1: Create Google Cloud Project
1. Go to: https://console.cloud.google.com/
2. Create a new project
3. Enable Gmail API
4. Create OAuth2 credentials
5. Download credentials JSON

### Step 2: Install OAuth Package
```bash
composer require league/oauth2-google
```

### Step 3: Configure Laravel
This requires additional configuration in `config/mail.php` and creating a custom transport.

**For local development, App Passwords (Option 1) is recommended.**

---

## Troubleshooting

### Error: "Username and Password not accepted"
- Double-check you enabled 2-Step Verification
- Make sure you're using the App Password, not your regular Gmail password
- Remove any spaces from the App Password

### Error: "Could not open socket"
- Check if port 587 is blocked by your firewall
- Try port 465 with `MAIL_ENCRYPTION=ssl`

### Error: "Connection timed out"
- Your ISP might be blocking SMTP ports
- Try using a VPN
- Contact your ISP about SMTP restrictions

### Emails Going to Spam
- Add SPF, DKIM, and DMARC records to your domain
- Use a verified "from" address
- Don't send too many emails at once

---

## Alternative: Use Mailtrap for Testing

For development/testing, you can use Mailtrap (free):

1. Sign up at https://mailtrap.io/
2. Get your credentials from the demo inbox
3. Update `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@gifamz.com
MAIL_FROM_NAME="Gifamz Store"
```

**Advantages:**
- No real emails sent (safe for testing)
- View all emails in web interface
- Test email rendering
- Free for development

---

## Security Best Practices

1. **Never commit credentials to Git**
   - `.env` file is already in `.gitignore`
   - For production, use environment variables

2. **Use App Passwords, not your main password**
   - More secure
   - Can be revoked independently
   - Doesn't expose your main account

3. **For Production:**
   - Use a professional email service (SendGrid, AWS SES, Mailgun)
   - Set up proper DNS records (SPF, DKIM, DMARC)
   - Monitor email delivery rates

4. **Rate Limiting:**
   - Gmail has sending limits (500/day for free accounts)
   - For bulk emails, use a dedicated service

---

## Quick Setup Checklist

- [ ] Enable 2-Step Verification on Gmail
- [ ] Generate App Password
- [ ] Copy the 16-character password
- [ ] Update `.env` file with Gmail credentials
- [ ] Clear config cache: `php artisan config:clear`
- [ ] Restart server: `php artisan serve`
- [ ] Test registration with real email
- [ ] Check inbox for verification email
- [ ] Click verification link to verify email

---

## Production Recommendations

For production, consider these alternatives to Gmail:

1. **SendGrid** (https://sendgrid.com/)
   - Free: 100 emails/day
   - Easy Laravel integration
   - Good deliverability

2. **AWS SES** (https://aws.amazon.com/ses/)
   - Very cheap ($0.10 per 1,000 emails)
   - Requires AWS account
   - Excellent deliverability

3. **Mailgun** (https://www.mailgun.com/)
   - Free: 5,000 emails/month
   - Easy setup
   - Good documentation

4. **Postmark** (https://postmarkapp.com/)
   - Free: 100 emails/month
   - Best for transactional emails
   - Excellent deliverability

---

## Next Steps

1. Follow the steps above to get Gmail App Password
2. Update your `.env` file
3. Test the registration
4. You should receive a verification email!

Let me know if you need help with any step! 🚀
