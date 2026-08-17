# Remote Assist — FR-9.4

> Troubleshooting tanpa dateng ke lokasi. Hanya untuk managed support client.

## Opsi: Tailscale (Recommended)

Tailscale = VPN peer-to-peer. Setup sekali, akses permanen selama aktif.

### Setup di Server Client

```bash
# Install tailscale
curl -fsSL https://tailscale.com/install.sh | sh
sudo tailscale up

# Catat IP tailscale (cth: 100.x.x.x)
tailscale ip -4
```

### Setup di Support Kami

```bash
# Install tailscale di mesin support
curl -fsSL https://tailscale.com/install.sh | sh
sudo tailscale up

# SSH ke server client via tailscale
ssh user@100.x.x.x
```

### Port Forwarding (jika butuh akses web)

```bash
# Di server client, forward port 80 ke tailscale
ssh -L 8080:localhost:80 user@100.x.x.x

# Akses via browser: http://localhost:8080
```

## Opsi: Reverse Tunnel (Alternatif)

Jika tailscale tidak memungkinkan:

```bash
# Di server client (cron @reboot)
ssh -R 8080:localhost:80 support@our-server.com -N

# Akses: support:8080
```

## Protokol Keamanan

1. **Session hanya atas persetujuan client** — tidak boleh auto-connect
2. **Tutup tunnel setelah selesai** — `exit` atau `kill` SSH session
3. **Log semua akses** — catat kapan, siapa, akses apa
4. **Jangan simpan credentials** — gunakan SSH key, bukan password
