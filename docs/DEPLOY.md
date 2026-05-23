# Deployment

## SSH Access

Hostname, IP, user, and password in `docs/_secrets.md` (Production SSH section).

From **Linux/Mac/WSL**: `ssh root@<host-ip>`

From **Windows (PuTTY)**: `plink -ssh -batch -pw '<password>' root@<host-ip> "<command>"`

## Gitignored Files

These files are not in git and must be created manually on each deployment.

| File | Notes |
|------|-------|
| `config/database.php` | DB credentials — copy from existing or create from `config/database.php.sample` |
| `config/google-calendar.php` | Calendar ID + service account key path — create from `config/google-calendar.php.sample` |
| `config/oauth.php` | OAuth provider credentials — create from `config/oauth.php.sample` with actual client IDs/secrets from Google Cloud and Meta Developer consoles |
| `lrv/.env` | Laravel env — copy from existing |
| `lrv/storage/google-calendar-key.json` | Google service account key JSON |
| `log/app.log` | Auto-created by PHP on first request |
| `log/error.log` | Auto-created by PHP on first request |

## Steps

1. `git pull`
2. For each missing file in the table above, create or copy from existing
3. `cd lrv && composer install --no-dev`
4. `cd lrv && php artisan migrate`

## Schema Changes

All schema changes use Laravel migrations (see `docs/DEVELOP.md`). After pulling new code:

```bash
cd /home/vagrant/code/lrv
php artisan migrate
```

This creates the `magic_link_tokens` table (for passwordless login) and any other new tables.

## OAuth Social Login Deployment

The OAuth flow requires:

| Change | Notes |
|--------|-------|
| `config/oauth.php` | Create from `config/oauth.php.sample` with actual credentials |
| `oauth-login.php` | New file — redirects to provider consent screen |
| `oauth-callback.php` | New file — handles provider callback, creates session |
| `oauth-link.php` | New file — account linking page |
| `oauth-link-action.php` | New file — process link form |
| `Login.php` | Modified — social login buttons added |
| `.htaccess` | Routes added for OAuth endpoints |
| `user_providers` table | Created by `php artisan migrate` |


### Provider Setup: Detailed Steps

Both providers need a **redirect URI** pointing to your callback handler: `https://gops.wwgc.co.nz/oauth-callback`

#### Google

1. Go to [Google Cloud Console](https://console.cloud.google.com) and select or create a project
2. Navigate to **APIs & Services > OAuth consent screen**
   - Choose **External** user type (unless you have Google Workspace)
   - Fill in: App name ("Gliding Ops"), User support email (your email), Developer contact info (your email)
   - **Scopes:** Add `.../auth/userinfo.email` and `.../auth/userinfo.profile` (or just select `openid`, `email`, `profile`)
   - **Test users:** Add your own Google account email for testing while the app is in "Testing" publishing state
3. Navigate to **APIs & Services > Credentials**
   - Click **Create Credentials > OAuth client ID**
   - Application type: **Web application**
   - Name: "Gliding Ops Web"
   - **Authorized redirect URI:** `https://gops.wwgc.co.nz/oauth-callback`
   - Click **Create**
4. Note the **Client ID** and **Client Secret** — put these in `config/oauth.php` under `google`

**config/oauth.php entry:**
```php
'google' => [
    'client_id' => 'YOUR_CLIENT_ID.apps.googleusercontent.com',
    'client_secret' => 'YOUR_CLIENT_SECRET',
    // ... other fields stay as-is from oauth.php.sample
],
```

5. (Optional but recommended) Click **Publish App** on the OAuth consent screen when ready for production, otherwise only test users can log in.

---

#### Facebook (Meta)

1. Go to [Meta for Developers](https://developers.facebook.com)
2. Click **My Apps > Create App**
   - Choose "Consumer" as the app type
   - Name: "Gliding Ops Login"
   - Add your email as contact
3. In the app dashboard, click **Add Product** and select **Facebook Login**
   - Choose **Web** as the platform
4. Under **Facebook Login > Settings**:
   - **Valid OAuth Redirect URI:** `https://gops.wwgc.co.nz/oauth-callback`
   - Leave other settings at defaults
5. In the left menu, go to **Settings > Basic**
   - Note the **App ID** and **App Secret**
   - Copy these to `config/oauth.php` under `facebook`
6. **Making it public:** By default the app is in "Development" mode — only admins/testers can log in. To go live:
   - Switch the toggle at the top from "In development" to "Live"
   - You may need to complete the "App Review" process if asked, but for basic `email` and `public_profile` scopes it should work without review.

**config/oauth.php entry:**
```php
'facebook' => [
    'app_id' => 'YOUR_APP_ID',
    'app_secret' => 'YOUR_APP_SECRET',
    // ... other fields stay as-is
],
```

**Important:** Facebook's App Secret is sensitive — never commit it. `config/oauth.php` is gitignored.

---

### config/oauth.php Reference

Here is the complete structure:

```php
<?php
return [
    // Base URL for redirect URIs:
    //   Production: 'https://gops.wwgc.co.nz'
    'redirect_base' => 'https://gops.wwgc.co.nz',

    'google' => [
        'client_id' => '',        // From Google Cloud Console
        'client_secret' => '',    // From Google Cloud Console
        'scope' => 'openid email profile',
        'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_url' => 'https://oauth2.googleapis.com/token',
        'userinfo_url' => 'https://www.googleapis.com/oauth2/v2/userinfo',
    ],

    'facebook' => [
        'app_id' => '',           // From Meta Developer Console
        'app_secret' => '',       // From Meta Developer Console
        'scope' => 'email public_profile',
        'auth_url' => 'https://www.facebook.com/v19.0/dialog/oauth',
        'token_url' => 'https://graph.facebook.com/v19.0/oauth/access_token',
        'userinfo_url' => 'https://graph.facebook.com/me?fields=id,name,email',
    ],

];
```

The `auth_url`, `token_url`, `userinfo_url`, and `scope` values are standard and do not need to change between environments.

## Magic Link Login Deployment

The magic link flow involves these changes out of git:

| Change | Notes |
|--------|-------|
| `api/magic-link-request.php` | New file |
| `api/magic-link-verify.php` | New file |
| `login.php` | Modified (tabbed UI) |
| `.htaccess` | Routes added for `^api/magic-link-request` and `^api/magic-link-verify` |
| `Forgotten.php` | **Deleted** (replaced by magic link) |
| `Register.php` | **Deleted** (absorbed into Email or Register tab) |
| `magic_link_tokens` table | Created by `php artisan migrate` |

## Member Photos Migration

Photos were migrated from `img/members/{displayname}.jpg` to `img/members/{member_id}.jpg`:

1. Migration script (`maintenance/migrate-photos.php`) ran on production
2. Google Drive cron sync removed (was overwriting photos hourly)
3. `img/noprofile.png` used as fallback when no photo exists
4. GD extension installed on production for photo resize (max 400px, JPEG q80)
