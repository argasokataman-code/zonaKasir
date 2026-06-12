#!/bin/bash
# Baca file secrets dan set ke GitHub Actions secrets
# Usage: bash scripts/setup-deploy-secrets.sh

set -a
source .deploy/secrets.sh
set +a

if [ -z "$SSH_USERNAME" ] || [ -z "$SSH_HOST" ] || [ -z "$DEPLOY_PATH" ] || [ -z "$SSH_PRIVATE_KEY" ]; then
  echo "ERROR: Ada variable yang kosong di .deploy/secrets.sh"
  echo "Pastikan SSH_USERNAME, SSH_HOST, SSH_PORT, DEPLOY_PATH, dan SSH_PRIVATE_KEY sudah diisi"
  exit 1
fi

SSH_PORT="${SSH_PORT:-2223}"

echo "Setting GitHub secrets..."
gh secret set SSH_HOST --body "$SSH_HOST" --repo argasokataman-code/zonaKasir
gh secret set SSH_PORT --body "$SSH_PORT" --repo argasokataman-code/zonaKasir
gh secret set SSH_USERNAME --body "$SSH_USERNAME" --repo argasokataman-code/zonaKasir
gh secret set DEPLOY_PATH --body "$DEPLOY_PATH" --repo argasokataman-code/zonaKasir
gh secret set SSH_PRIVATE_KEY --body "$SSH_PRIVATE_KEY" --repo argasokataman-code/zonaKasir

echo "Done! Secrets berhasil di-set."
