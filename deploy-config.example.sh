#!/bin/bash
# =============================================
# Template Konfigurasi Deploy
# =============================================
# 1. Isi file ini dengan credentials hosting kamu
# 2. Simpan copy ke .deploy/secrets.sh (jangan di-commit)
# 3. Jalankan: bash scripts/setup-deploy-secrets.sh
# =============================================

SSH_USERNAME=""          # Username cPanel
SSH_HOST=""              # Domain/IP hosting (contoh: zonakasir.com)
DEPLOY_PATH=""           # Path ke public_html (contoh: /home/userzonakasir/public_html)

# Paste di sini isi private key dari cPanel.
# Format: mulai dari "-----BEGIN OPENSSH PRIVATE KEY-----" sampai "-----END OPENSSH PRIVATE KEY-----"
SSH_PRIVATE_KEY=""
