#!/usr/bin/env bash

# Laravel Package Compatibility Testing Script
# Usage: ./test-versions.sh [version]
# Example: ./test-versions.sh 8    (test only Laravel 8)
# Example: ./test-versions.sh      (test all versions)

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Test configurations using functions instead of associative arrays
get_laravel_version() {
    case $1 in
        8) echo "^8.0" ;;
        9) echo "^9.0" ;;
        10) echo "^10.0" ;;
        11) echo "^11.0" ;;
        12) echo "^12.0" ;;
        *) echo "" ;;
    esac
}

get_testbench_version() {
    case $1 in
        8) echo "^6.0" ;;
        9) echo "^7.0" ;;
        10) echo "^8.0" ;;
        11) echo "^9.0" ;;
        12) echo "^10.0" ;;
        *) echo "" ;;
    esac
}

get_phpunit_version() {
    case $1 in
        8) echo "^9.0" ;;
        9) echo "^9.0" ;;
        10) echo "^10.0" ;;
        11) echo "^10.0" ;;
        12) echo "^11.0" ;;
        *) echo "" ;;
    esac
}

print_header() {
    echo -e "${BLUE}🧪 Laravel Package Compatibility Testing${NC}"
    echo -e "${BLUE}==========================================${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ️  $1${NC}"
}

backup_composer() {
    print_info "Backing up composer files..."
    cp composer.json composer.json.backup
    cp composer.lock composer.lock.backup 2>/dev/null || true
}

restore_composer() {
    print_info "Restoring original composer files..."
    cp composer.json.backup composer.json
    cp composer.lock.backup composer.lock 2>/dev/null || true
    rm -f composer.json.backup composer.lock.backup
    composer install --no-interaction --quiet
}

test_laravel_version() {
    local version=$1
    local laravel_constraint=$(get_laravel_version $version)
    local testbench_constraint=$(get_testbench_version $version)
    local phpunit_constraint=$(get_phpunit_version $version)

    echo ""
    echo -e "${BLUE}=========================================${NC}"
    echo -e "${BLUE}Testing Laravel $version ($laravel_constraint)${NC}"
    echo -e "${BLUE}=========================================${NC}"

    print_info "Installing Laravel $version dependencies..."
    composer require \
        "laravel/framework:$laravel_constraint" \
        "orchestra/testbench:$testbench_constraint" \
        "phpunit/phpunit:$phpunit_constraint" \
        --no-update --quiet

    print_info "Updating dependencies..."
    if ! composer update --no-interaction --quiet; then
        print_error "Laravel $version: Dependency resolution failed"
        return 1
    fi

    print_info "Installed versions:"
    composer show laravel/framework orchestra/testbench phpunit/phpunit 2>/dev/null | \
        grep -E "(laravel/framework|orchestra/testbench|phpunit/phpunit)" || true

    print_info "Running tests..."
    if make test >/dev/null 2>&1; then
        print_success "Laravel $version: ALL TESTS PASSED"
        return 0
    else
        print_error "Laravel $version: TESTS FAILED"
        return 1
    fi
}

main() {
    local specific_version=$1
    local failed_versions=()
    local passed_versions=()

    print_header

    # Check if make command exists
    if ! command -v make &> /dev/null; then
        print_error "Make command not found. Please install make or run tests manually."
        exit 1
    fi

    # Backup composer files
    backup_composer
    trap restore_composer EXIT

    if [ -n "$specific_version" ]; then
        # Test specific version
        if [ -n "$(get_laravel_version $specific_version)" ]; then
            test_laravel_version "$specific_version"
        else
            print_error "Invalid Laravel version. Use: 8, 9, 10, 11, or 12"
            exit 1
        fi
    else
        # Test all versions
        for version in 8 9 10 11 12; do
            if test_laravel_version "$version"; then
                passed_versions+=("$version")
            else
                failed_versions+=("$version")
            fi
        done

        # Summary
        echo ""
        echo -e "${BLUE}=========================================${NC}"
        echo -e "${BLUE}COMPATIBILITY TEST SUMMARY${NC}"
        echo -e "${BLUE}=========================================${NC}"

        if [ ${#passed_versions[@]} -gt 0 ]; then
            print_success "Passed: Laravel ${passed_versions[*]}"
        fi

        if [ ${#failed_versions[@]} -gt 0 ]; then
            print_error "Failed: Laravel ${failed_versions[*]}"
            echo ""
            print_info "To test a specific version: ./test-versions.sh [version]"
            exit 1
        else
            echo ""
            print_success "🎉 ALL Laravel versions passed!"
            print_info "Ready for GitHub Actions setup!"
        fi
    fi
}

main "$@"
