#!/bin/bash

# Test Laravel Compatibility Script
# Usage: ./scripts/test-laravel-versions.sh [version]
# Example: ./scripts/test-laravel-versions.sh 8
# Or run all: ./scripts/test-laravel-versions.sh

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Laravel versions to test
LARAVEL_VERSIONS=("8.*" "9.*" "10.*" "11.*" "12.*")
TESTBENCH_VERSIONS=("^6.0" "^7.0" "^8.0" "^9.0" "^10.0")
PHPUNIT_VERSIONS=("^9.0" "^9.0" "^10.0" "^10.0" "^11.0")

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to backup composer files
backup_composer() {
    print_status "Backing up composer files..."
    cp composer.json composer.json.backup
    cp composer.lock composer.lock.backup 2>/dev/null || true
}

# Function to restore composer files
restore_composer() {
    print_status "Restoring composer files..."
    cp composer.json.backup composer.json
    cp composer.lock.backup composer.lock 2>/dev/null || true
    rm -f composer.json.backup composer.lock.backup
}

# Function to test specific Laravel version
test_laravel_version() {
    local version_index=$1
    local laravel_version=${LARAVEL_VERSIONS[$version_index]}
    local testbench_version=${TESTBENCH_VERSIONS[$version_index]}
    local phpunit_version=${PHPUNIT_VERSIONS[$version_index]}
    
    print_status "Testing Laravel $laravel_version with Testbench $testbench_version"
    
    # Install specific versions
    composer require "laravel/framework:$laravel_version" "orchestra/testbench:$testbench_version" "phpunit/phpunit:$phpunit_version" --no-interaction --no-update
    
    # Update dependencies
    composer update --prefer-stable --no-interaction
    
    # Show installed versions
    print_status "Installed versions:"
    composer show laravel/framework orchestra/testbench phpunit/phpunit | grep -E "(laravel/framework|orchestra/testbench|phpunit/phpunit)"
    
    # Run tests
    print_status "Running tests for Laravel $laravel_version..."
    if vendor/bin/phpunit --testdox; then
        print_status "✅ Laravel $laravel_version tests PASSED"
        return 0
    else
        print_error "❌ Laravel $laravel_version tests FAILED"
        return 1
    fi
}

# Main execution
main() {
    local specific_version=$1
    local failed_versions=()
    
    print_status "Starting Laravel compatibility testing..."
    
    # Backup original composer files
    backup_composer
    
    # Trap to ensure cleanup on exit
    trap restore_composer EXIT
    
    if [ -n "$specific_version" ]; then
        # Test specific version
        case $specific_version in
            8) test_laravel_version 0 ;;
            9) test_laravel_version 1 ;;
            10) test_laravel_version 2 ;;
            11) test_laravel_version 3 ;;
            12) test_laravel_version 4 ;;
            *) 
                print_error "Invalid Laravel version. Use: 8, 9, 10, 11, or 12"
                exit 1
                ;;
        esac
    else
        # Test all versions
        for i in "${!LARAVEL_VERSIONS[@]}"; do
            print_status "========================================="
            print_status "Testing Laravel ${LARAVEL_VERSIONS[$i]}"
            print_status "========================================="
            
            if ! test_laravel_version $i; then
                failed_versions+=("${LARAVEL_VERSIONS[$i]}")
            fi
            
            print_status ""
        done
        
        # Summary
        print_status "========================================="
        print_status "COMPATIBILITY TEST SUMMARY"
        print_status "========================================="
        
        if [ ${#failed_versions[@]} -eq 0 ]; then
            print_status "🎉 ALL Laravel versions passed!"
        else
            print_error "❌ Failed versions: ${failed_versions[*]}"
            exit 1
        fi
    fi
}

# Run main function with all arguments
main "$@"
