#!/usr/bin/env bash
# ==============================================================================
# NatConnect Foundation — Local Development Server Launcher
# ==============================================================================

set -e

# Check if PHP CLI is installed
if ! command -v php &> /dev/null; then
    echo "================================================================="
    echo "⚠️  PHP is not installed on this system."
    echo "Please install PHP (8.2+ or 8.3) by running:"
    echo ""
    echo "  sudo apt update && sudo apt install -y php8.3-cli php8.3-mysql php8.3-mbstring php8.3-curl"
    echo "================================================================="
    exit 1
fi

echo "================================================================="
echo "🌱 Starting NatConnect Foundation Local Server"
echo "================================================================="
echo "PHP Version: $(php -v | head -n 1)"
echo "Document Root: $(pwd)/public_html"
echo "URL: http://127.0.0.1:8000"
echo "Press Ctrl+C to stop the server."
echo "================================================================="

# Start PHP built-in web server pointing at public_html
exec php -S 127.0.0.1:8000 -t public_html
