# Production Environment Checklist

Use these values before public launch.

## Core App

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://your-domain.com`
- `COMING_SOON=false`
- `ALLOW_SETUP_ROUTE=false`

## Database

- `DB_CONNECTION=mysql`
- `DB_HOST=...`
- `DB_PORT=3306`
- `DB_DATABASE=...`
- `DB_USERNAME=...`
- `DB_PASSWORD=...`

## Mail

- `MAIL_MAILER=smtp`
- `MAIL_HOST=...`
- `MAIL_PORT=587`
- `MAIL_USERNAME=...`
- `MAIL_PASSWORD=...`
- `MAIL_ENCRYPTION=tls`
- `MAIL_FROM_ADDRESS=support@your-domain.com`
- `MAIL_FROM_NAME="GharKaam"`

Recommended SMTP providers:

- Postmark
- Resend SMTP
- SendGrid SMTP
- Google Workspace SMTP for low-volume launch

## OpenAI

- `OPENAI_API_KEY=rotate-and-replace-with-new-secret`
- `OPENAI_BASE_URL=https://api.openai.com/v1`
- `OPENAI_MODEL=gpt-5-mini`
- `OPENAI_TIMEOUT=30`

If the old key was ever exposed, revoke it first and then save a fresh key.

## Support Details

- `SUPPORT_EMAIL=help@your-domain.com`
- `SUPPORT_PHONE=+92...`
- `SUPPORT_WHATSAPP=92...`
- `SUPPORT_HOURS=Mon-Sat, 9 AM - 7 PM`

## Moderation / Monitoring

- `CONTENT_BLOCKED_TERMS=porn,nude,escort,casino,betting,cocaine,heroin,weapon,hate`
- `CONTENT_BLOCK_CONTACT=true`
- `MODERATION_IMAGE_MIN_WIDTH=400`
- `MODERATION_IMAGE_MIN_HEIGHT=300`
- `MODERATION_IMAGE_MAX_WIDTH=6000`
- `MODERATION_IMAGE_MAX_HEIGHT=6000`
- `DASHBOARD_LOG_LINES=40`

## Launch Commands

Run after deploy:

```bash
php artisan config:clear
php artisan cache:clear
php artisan migrate --force
php artisan marketplace:seed-launch
php artisan marketplace:secure-admin --password="set-a-new-strong-password"
```
