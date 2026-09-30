# Production deployment checklist

This workspace points to a local XAMPP site. It does not contain the production hostname, TLS certificate, production DB credentials, Windows task account, or an approved remote backup destination, so those host-specific steps cannot be applied from this checkout. Complete these steps on the actual host before launch.

## 1. Database account and application secrets

1. For a fresh installation, import `riz_catering.sql`; it contains the delivery and cancellation fields. For an existing installation, apply migrations in the order documented by `README.txt`, including `Application/Database/delivery_cancellation_policy.sql` once after the owner and role migrations.
2. As the database administrator, copy `Application/Database/production_account.template.sql`, replace its example password with a unique secret, and run it. The application account only needs normal CRUD and routine execution; run later schema migrations with a separate deployment administrator.
3. Configure the values in `.env.production.example` in the PHP/Apache service environment, outside the document root. Generate independent long random values for `RIZ_DB_PASSWORD` and `RIZ_OWNER_SETUP_KEY`. Do not use MySQL root for the application.
4. Restart Apache/PHP and confirm the owner setup page reports setup is configured. Create the owner account once; setup locks after that account exists. Remove `RIZ_OWNER_SETUP_KEY` from the running environment after setup.

## 2. HTTPS

Point the production domain to the host, install a valid certificate and private key from the host's certificate manager, configure Apache `:443` with the correct `ServerName` and certificate paths, and enable the SSL module. Redirect HTTP to HTTPS only after the certificate works. Verify the owner and customer sessions are served over HTTPS; the app marks session cookies Secure when HTTPS is detected. Do not force HTTPS on local XAMPP unless local TLS is configured.

## 3. PHP uploads

The local XAMPP configuration currently reports `upload_max_filesize=40M` and `post_max_size=40M`. On the production host, set the active PHP SAPI to the values in `php-upload-settings.ini` (minimum 6M upload and 8M POST), restart PHP/Apache, then verify those active values through the web SAPI. Feedback photo validation remains capped at 5 MB.

## 4. Database backups

The selected default backup destination is `C:\RizCateringBackups`, outside the web root, with 30-day retention. Change `RIZ_BACKUP_DIR` if the production host has a dedicated persistent backup volume. Create the restricted database account from `Application/Database/backup_account.template.sql`. Create a private MySQL option file readable only by the scheduled-task account, for example:

```ini
[client]
user=backup_reader
password=REPLACE_WITH_SECRET
host=localhost
```

The backup account has only the SELECT, SHOW VIEW, TRIGGER, EVENT, and LOCK TABLES permissions needed by `mysqldump`. Restrict the backup folder and option file with Windows ACLs. Set `RIZ_MYSQL_DEFAULTS_FILE` for the task account, then test:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File C:\path\to\riz_catering\deployment\backup-riz.ps1
```

The script writes a compressed dump and removes backups older than 30 days. After copying the script and option file to protected paths outside the website, an administrator can install the daily 2:00 AM task (run the following in elevated PowerShell after replacing paths):

```powershell
$action = New-ScheduledTaskAction -Execute 'powershell.exe' -Argument '-NoProfile -ExecutionPolicy Bypass -File C:\ProgramData\RizCatering\backup-riz.ps1 -BackupDirectory C:\RizCateringBackups -DefaultsFile C:\ProgramData\RizCatering\backup.cnf'
$trigger = New-ScheduledTaskTrigger -Daily -At '2:00 AM'
$principal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Limited
Register-ScheduledTask -TaskName 'Riz Catering database backup' -Action $action -Trigger $trigger -Principal $principal
```

Grant the scheduled-task identity and administrators access to the option file and backup folder only. Restore a recent dump into a staging database and verify it. Keep an additional encrypted/off-host copy if the host volume is the only destination.

## 5. Go-live checks

- Confirm the public website, owner sign-in, customer sign-in, reservation, payment ledger, delivery updates, feedback approval and notification badges over HTTPS.
- Confirm the owner can update a reservation to Preparing, On the Way and Delivered and the customer sees both the current progress and its in-app notification. The system requires Delivered before an order can be marked Completed.
- Confirm cancellation shows a 50% deposit and a 10% fee included within that deposit; paid amounts above the deposit are shown as refund due and are paid manually outside the site.
- Verify the backup task history and perform a restore test before relying on production data.
