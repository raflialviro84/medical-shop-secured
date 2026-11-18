<?php

namespace CryptographicSession\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'csb:install {--force : Overwrite existing files}';

    protected $description = 'Instalasi dan publikasi aset Cryptographic Session Binding';

    public function handle(): int
    {
        $this->info('Menginstall Cryptographic Session Binding...');

        $params = ['--tag' => 'csb-config'];
        if ($this->option('force')) {
            $params['--force'] = true;
        }
        $this->call('vendor:publish', $params);

        $params = ['--tag' => 'csb-assets'];
        if ($this->option('force')) {
            $params['--force'] = true;
        }
        $this->call('vendor:publish', $params);

        if ($this->confirm('Apakah Anda ingin menjalankan database migrations sekarang?', true)) {
            $this->call('migrate');
        }

        $this->newLine();
        $this->info('✅ Cryptographic Session Binding berhasil diinstall!');
        $this->line('Tambahkan directive Blade <info>@cryptographicSession</info> di layout utama Anda.');
        $this->line('Lindungi route sensitif dengan middleware <info>cryptographic.session</info>.');

        return self::SUCCESS;
    }
}

