#!/bin/bash

# Ubuntu Git Installation and Setup Script
# This script installs git and pulls the latest media server code

set -e

echo "════════════════════════════════════════════════════════════"
echo "🐧 Ubuntu Git Installation & Media Server Setup"
echo "════════════════════════════════════════════════════════════"
echo ""

# Update package list
echo "📦 Updating package list..."
sudo apt-get update -y
echo "✅ Done"
echo ""

# Install git
echo "📥 Installing git..."
sudo apt-get install -y git
echo "✅ Git installed"
echo ""

# Verify git installation
echo "🔍 Verifying git installation..."
git --version
echo "✅ Git is ready"
echo ""

# Navigate to media server directory
echo "📂 Setting up media server directory..."
cd /var/www/mediaserver || mkdir -p /var/www/mediaserver
echo "✅ Directory ready: $(pwd)"
echo ""

# Pull latest code
echo "📡 Pulling latest code from GitHub..."
git pull origin master 2>/dev/null || git clone https://github.com/basilkewir/media-server1.git .
echo "✅ Code pulled successfully"
echo ""

# Configure git (optional but recommended)
echo "⚙️ Configuring git (optional)..."
git config --global user.email "admin@mediaserver.local"
git config --global user.name "Media Server Admin"
echo "✅ Git configured"
echo ""

# Show git status
echo "📊 Current git status:"
git status
echo ""

echo "════════════════════════════════════════════════════════════"
echo "✅ Git installation complete!"
echo "════════════════════════════════════════════════════════════"
echo ""
echo "Next steps:"
echo "1. Import existing channels:"
echo "   php artisan srt:import-existing-channels"
echo ""
echo "2. Clear cache:"
echo "   php artisan cache:clear"
echo ""
echo "3. Access dashboard:"
echo "   http://your-server/admin/srt-streams"
echo ""
