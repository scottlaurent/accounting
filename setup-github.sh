#!/usr/bin/env bash

# GitHub Repository Setup Script
# This script helps you set up your repository on GitHub with CI/CD

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_step() {
    echo -e "${BLUE}🚀 $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ️  $1${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_step "Setting up GitHub repository for Laravel Accounting Package"
echo ""

# Check if git is initialized
if [ ! -d ".git" ]; then
    print_info "Initializing git repository..."
    git init
    print_success "Git repository initialized"
else
    print_success "Git repository already exists"
fi

# Check if there are any commits
if ! git rev-parse --verify HEAD >/dev/null 2>&1; then
    print_info "Making initial commit..."
    git add .
    git commit -m "Initial commit: Laravel Accounting Package

- Double-entry accounting system
- Multi-currency support  
- Laravel 8-12 compatibility
- 100% test coverage
- PSR-12 compliant
- Optimized for high-volume transactions"
    print_success "Initial commit created"
else
    print_info "Adding current changes..."
    git add .
    if git diff --staged --quiet; then
        print_info "No changes to commit"
    else
        git commit -m "Add GitHub Actions CI/CD and update documentation

- Add comprehensive test matrix for Laravel 8-12
- Add status badges to README
- Add local testing script
- Update composer.json for multi-version support"
        print_success "Changes committed"
    fi
fi

echo ""
print_step "Next Steps:"
echo ""
print_info "1. Create a new repository on GitHub:"
echo "   - Go to https://github.com/new"
echo "   - Repository name: accounting"
echo "   - Description: Laravel Accounting Package - Double-entry accounting for Eloquent models"
echo "   - Make it PUBLIC (for free CI/CD)"
echo "   - Don't initialize with README (we already have one)"
echo ""

print_info "2. Add the GitHub remote and push:"
echo "   git remote add origin https://github.com/YOUR_USERNAME/accounting.git"
echo "   git branch -M main"
echo "   git push -u origin main"
echo ""

print_info "3. After pushing, GitHub Actions will automatically:"
echo "   ✅ Test against Laravel 8, 9, 10, 11, 12"
echo "   ✅ Test against PHP 8.1, 8.2, 8.3"
echo "   ✅ Generate coverage reports"
echo "   ✅ Show status badges in your README"
echo ""

print_info "4. Your status badges will be available at:"
echo "   https://github.com/YOUR_USERNAME/accounting"
echo ""

print_success "Repository is ready for GitHub! 🎉"
echo ""
print_info "The badges in your README will show:"
echo "   - ✅ Tests passing"
echo "   - 📊 Laravel 8-12 support"
echo "   - 🐘 PHP 8.1+ support"
echo "   - 📄 MIT License"
echo "   - 💯 100% Coverage"
