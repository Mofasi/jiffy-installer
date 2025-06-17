<?php

namespace Mofasi\JiffyInstaller;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use GuzzleHttp\Client;

class Installer
{
    private $filesystem;
    private $client;
    private $projectName;
    private $output;
    private $tailwindBinary;

    public function __construct(string $projectName, $output)
    {
        $this->filesystem = new Filesystem();
        $this->client = new Client();
        $this->projectName = $projectName;
        $this->output = $output;
    }

    public function install()
    {
        $this->validateProjectName();
        $this->createProjectStructure();
        $this->downloadTailwindBinary();
        $this->createInputCss();
        $this->makeProjectCliExecutable();
        $this->generateAppKey();
        $this->showSuccessMessage();
    }

    private function validateProjectName()
    {
        if (!preg_match('/^[a-z0-9_\-]+$/i', $this->projectName)) {
            throw new \InvalidArgumentException(
                'Invalid project name. Use only letters, numbers, hyphens and underscores.'
            );
        }

        if ($this->filesystem->exists($this->projectName)) {
            throw new \RuntimeException(
                "Directory '{$this->projectName}' already exists!"
            );
        }
    }

    private function createProjectStructure()
    {
        $this->output->writeln('Creating project structure...');

        // Clone from GitHub
        $process = new Process([
            'git', 'clone', 
            'https://github.com/Mofasi/Jiffy.git', 
            $this->projectName
        ]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Failed to clone Jiffy repository');
        }

        // Remove .git directory
        $this->filesystem->remove($this->projectName . '/.git');
    }

    private function downloadTailwindBinary()
    {
        $this->output->writeln('Downloading Tailwind CSS CLI...');
        
        $os = strtolower(PHP_OS);
        $url = 'https://github.com/tailwindlabs/tailwindcss/releases/latest/download/';
        
        if (strpos($os, 'win') !== false) {
            $url .= 'tailwindcss-windows-x64.exe';
            $this->tailwindBinary = 'tailwindcss.exe';
        } elseif (strpos($os, 'darwin') !== false) {
            $url .= 'tailwindcss-macos-arm64';
            $this->tailwindBinary = 'tailwindcss';
        } else {
            $url .= 'tailwindcss-linux-x64';
            $this->tailwindBinary = 'tailwindcss';
        }

        $response = $this->client->get($url);
        $this->filesystem->dumpFile(
            $this->projectName . '/' . $this->tailwindBinary,
            $response->getBody()
        );
        
        $this->filesystem->chmod($this->projectName . '/' . $this->tailwindBinary, 0755);
    }

    private function createInputCss()
    {
        $this->filesystem->dumpFile(
            $this->projectName . '/resources/css/input.css',
            '@import "tailwindcss";'
        );
    }

    private function makeProjectCliExecutable()
    {
        $this->filesystem->chmod($this->projectName . '/jiffy', 0755);
    }

    private function generateAppKey()
    {
        $key = bin2hex(random_bytes(16));
        $this->filesystem->dumpFile(
            $this->projectName . '/.env',
            "APP_KEY=$key\n"
        );
    }

    private function showSuccessMessage()
    {
        $this->output->writeln("\n<info>Jiffy project created successfully!</info>");
        $this->output->writeln("Next steps:");
        $this->output->writeln("  cd {$this->projectName}");
        $this->output->writeln("  ./jiffy serve");
        $this->output->writeln("  ./jiffy tailwind:watch");
    }
}