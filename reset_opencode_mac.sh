#!/bin/bash
echo "🔄 Memulai reset Opencode App di macOS..."

# 1. Matikan aplikasi secara paksa jika sedang berjalan
echo "🛑 Menghentikan proses Opencode..."
pkill -f "Opencode" 2>/dev/null || killall "Opencode" 2>/dev/null

# 2. Hapus Cache dan Application Support
echo "🧹 Menghapus cache dan data aplikasi..."
rm -rf ~/Library/Application\ Support/[Oo]pencode*
rm -rf ~/Library/Caches/[Oo]pencode*
rm -rf ~/Library/Preferences/*[Oo]pencode*.plist
rm -rf ~/Library/Saved\ Application\ State/*[Oo]pencode*.savedState

echo "✅ Opencode App berhasil di-reset ke kondisi pabrik!"
echo "🚀 Silakan buka kembali aplikasimu."
