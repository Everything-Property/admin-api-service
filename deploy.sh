#!/bin/bash

# Deploy script for Laravel application to Hostinger
# Usage: ./deploy.sh

set -e

echo "🚀 Starting deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
FTP_SERVER="your-ftp-server.com"
FTP_USERNAME="your-username"
FTP_PASSWORD="your-password"
FTP_DIR="/public_html"

# Check if required tools are installed
command -v composer >/dev/null 2>&1 || { echo -e "${RED}Composer is required but not installed.${NC}" >&2; exit 1; }
command -v php >/dev/null 2>&1 || { echo -e "${RED}PHP is required but not installed.${NC}" >&2; exit 1; }

echo -e "${YELLOW}📦 Installing dependencies...${NC}"
composer install --no-dev --optimize-autoloader --no-interaction

echo -e "${YELLOW}🔧 Setting up environment...${NC}"
if [ ! -f .env ]; then
    cp .env.example .env
    echo -e "${GREEN}✅ Created .env file from .env.example${NC}"
fi

echo -e "${YELLOW}🔑 Generating application key...${NC}"
php artisan key:generate --force

echo -e "${YELLOW}🧹 Clearing caches...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

echo -e "${YELLOW}⚡ Optimizing for production...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo -e "${YELLOW}📁 Setting permissions...${NC}"
chmod -R 755 storage bootstrap/cache

echo -e "${YELLOW}🌐 Deploying to Hostinger...${NC}"
# Using lftp for better FTP handling
lftp -c "
set ssl:verify-certificate no;
open -u $FTP_USERNAME,$FTP_PASSWORD $FTP_SERVER;
cd $FTP_DIR;
mirror --reverse --delete --verbose --exclude .git --exclude node_modules --exclude vendor --exclude .env --exclude storage/logs --exclude storage/framework/cache --exclude storage/framework/sessions --exclude storage/framework/views --exclude tests --exclude .github --exclude README.md --exclude phpunit.xml --exclude .gitignore --exclude .gitattributes --exclude .DS_Store --exclude Thumbs.db . .;
"

echo -e "${GREEN}✅ Deployment completed successfully!${NC}"
echo -e "${YELLOW}📝 Don't forget to:${NC}"
echo -e "   - Update your .env file on the server with production settings"
echo -e "   - Run database migrations: php artisan migrate --force"
echo -e "   - Set up your web server configuration" 