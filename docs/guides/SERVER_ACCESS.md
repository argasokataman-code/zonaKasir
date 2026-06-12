# Server Access Guide

## Staging Server

| Detail | Value |
|--------|-------|
| Host | `jogjatourdrive.com` |
| Port | `2223` |
| User | `jogn3455` |
| SSH Key | `~/.ssh/id_zonakasir` |
| App Path | `/home/jogn3455/public_html/` |

### Quick Connect
```bash
ssh -p 2223 jogn3455@jogjatourdrive.com -i ~/.ssh/id_zonakasir
```

### Load SSH Agent (one-time per session)
```bash
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/id_zonakasir
```
After loading agent, connect with just:
```bash
ssh -p 2223 jogn3455@jogjatourdrive.com
```

### SSH Config Entry (optional)
Add to `~/.ssh/config`:
```ssh-config
Host staging
  HostName jogjatourdrive.com
  User jogn3455
  Port 2223
  IdentityFile ~/.ssh/id_zonakasir
  ServerAliveInterval 30
  ServerAliveCountMax 3
```
Then: `ssh staging`

## Common Commands

### View Application Logs
```bash
ssh staging 'tail -f /home/jogn3455/public_html/storage/logs/laravel*.log'
```

### Check Environment
```bash
ssh staging 'cat /home/jogn3455/public_html/.env'
```

### Run Artisan Commands
```bash
ssh staging 'cd /home/jogn3455/public_html && php artisan config:clear'
ssh staging 'cd /home/jogn3455/public_html && php artisan migrate --path=database/migrations/tenant'
```

### Git Pull & Deploy
```bash
ssh staging 'cd /home/jogn3455/public_html && git pull origin main'
```

## GitHub Actions Alternative

If SSH is unavailable, use the SSH Command workflow:

```bash
gh workflow run ssh-command.yml --ref main --field command="cd /home/jogn3455/public_html && php artisan config:clear"
```
View output at actions tab.

## Important Notes

- `.env` file is at `/home/jogn3455/public_html/.env`
- Never commit `.env` to git (already in `.gitignore`)
- SSH key (`id_zonakasir`) must be kept private - never commit it
- After changing `.env`, always run `php artisan config:clear`

## Troubleshooting

### Permission Denied
- Ensure `id_zonakasir` key exists in `~/.ssh/`
- Load key with `ssh-add ~/.ssh/id_zonakasir`
- Verify key is added to server's `~/.ssh/authorized_keys`

### Key Permissions
```bash
chmod 600 ~/.ssh/id_zonakasir
chmod 644 ~/.ssh/id_zonakasir.pub
```
