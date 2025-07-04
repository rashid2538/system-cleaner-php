#!/bin/bash

# Auto-execute script for PHP project
# This script runs 'php index.php' and re-runs it whenever files change

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PHP_SCRIPT="index.php"
LOCK_FILE="/tmp/php_watcher.lock"
DIRECTORY="/Users/macexpert/workspace"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[$(date '+%H:%M:%S')]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[$(date '+%H:%M:%S')]${NC} $1"
}

print_error() {
    echo -e "${RED}[$(date '+%H:%M:%S')]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[$(date '+%H:%M:%S')]${NC} $1"
}

# Function to check if fswatch is installed
check_fswatch() {
    if ! command -v fswatch &> /dev/null; then
        print_error "fswatch is not installed. Installing via Homebrew..."
        if command -v brew &> /dev/null; then
            brew install fswatch
        else
            print_error "Homebrew is not installed. Please install fswatch manually:"
            print_error "Visit: https://github.com/emcrisostomo/fswatch"
            exit 1
        fi
    fi
}

# Function to run the PHP script
run_php_script() {
    print_status "Running: php $PHP_SCRIPT $DIRECTORY"
    echo "----------------------------------------"
    
    # Run the PHP script and capture exit code
    php "$PHP_SCRIPT" "$DIRECTORY"
    local exit_code=$?
    
    echo "----------------------------------------"
    if [ $exit_code -eq 0 ]; then
        print_success "Script completed successfully (exit code: $exit_code)"
    else
        print_error "Script failed with exit code: $exit_code"
    fi
    echo ""
}

# Function to cleanup on exit
cleanup() {
    print_warning "Stopping file watcher..."
    rm -f "$LOCK_FILE"
    exit 0
}

# Function to check if index.php exists
check_php_file() {
    if [ ! -f "$PHP_SCRIPT" ]; then
        print_error "Error: $PHP_SCRIPT not found in $PROJECT_DIR"
        print_error "Please make sure $PHP_SCRIPT exists in the project directory"
        exit 1
    fi
}

# Main function
main() {
    # Change to project directory
    cd "$PROJECT_DIR" || exit 1
    
    print_status "Starting PHP Auto-Execute Watcher"
    print_status "Project Directory: $PROJECT_DIR"
    print_status "PHP Script: $PHP_SCRIPT"
    print_status "Press Ctrl+C to stop watching"
    echo ""
    
    # Check if PHP script exists
    check_php_file
    
    # Check if fswatch is installed
    check_fswatch
    
    # Create lock file
    touch "$LOCK_FILE"
    
    # Set up signal handlers for cleanup
    trap cleanup INT TERM
    
    # Run the script initially
    run_php_script
    
    # Start watching for file changes
    print_status "Watching for file changes..."
    
    fswatch -o \
        --exclude='\.git' \
        --exclude='\.DS_Store' \
        --exclude='\.log' \
        --exclude='node_modules' \
        --exclude='vendor' \
        --exclude='\.tmp' \
        --exclude='\.cache' \
        . | while read num_changes
    do
        if [ -f "$LOCK_FILE" ]; then
            print_warning "File change detected! ($num_changes changes)"
            sleep 0.5  # Brief delay to allow file operations to complete
            run_php_script
        fi
    done
}

# Start the watcher
main "$@"
