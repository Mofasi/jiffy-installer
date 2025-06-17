#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Jiffy Global Installer
 * 
 * Install with: composer global require mofasi-pheeha/jiffy-installer
 * Then make sure ~/.composer/vendor/bin is in your PATH
 */

$version = "1.0.0";
$templateRepo = "https://github.com/Mofasi/Jiffy.git";

// Check command
if ($argc < 2 || $argv[1] !== 'new') {
    showHelp();
    exit(1);
}

// Get project name
$projectName = $argv[2] ?? null;
if (!$projectName) {
    echo "Error: Project name is required\n";
    showHelp();
    exit(1);
}

// Create project
echo "\033[34mCreating new Jiffy project: $projectName\033[0m\n";
createProject($projectName);
setupProject($projectName);

echo "\n\033[32mProject created successfully!\033[0m\n";
echo "Get started with:\n";
echo "  cd $projectName\n";
echo "  ./jiffy serve\n";

function createProject(string $name): void {
    global $templateRepo;
    
    if (is_dir($name)) {
        echo "\033[31mError: Directory '$name' already exists\033[0m\n";
        exit(1);
    }
    
    // Clone template repo
    $command = "git clone $templateRepo $name --quiet";
    exec($command, $output, $status);
    
    if ($status !== 0) {
        echo "\033[31mError cloning repository\033[0m\n";
        exit(1);
    }
    
    // Remove .git directory
    $gitDir = realpath($name) . '/.git';
    if (is_dir($gitDir)) {
        exec("rm -rf " . escapeshellarg($gitDir));
    }
}

function setupProject(string $name): void {
    $projectDir = realpath($name);
    
    // Generate app key
    $key = bin2hex(random_bytes(32));
    $envFile = "$projectDir/.env";
    if (!file_exists($envFile)) {
        file_put_contents($envFile, "APP_KEY=$key\n");
    }
    
    // Make jiffy executable
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        chmod("$projectDir/jiffy", 0755);
    }
}

function showHelp(): void {
    global $version;
    echo "Jiffy Installer (v$version)\n\n";
    echo "Usage:\n";
    echo "  jiffy new <project-name>\n\n";
    echo "Example:\n";
    echo "  jiffy new my-project\n";
}