<?php

namespace App\Console\Generators;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class GenerateModels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:models {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create model and move it to relative directory with proper namespaces';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Current root application namespace.
     *
     * @var string
     */
    protected $currentRoot;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->callSilent('make:model', [
            'name' => $this->argument('model'),
        ]);

        $this->replaceNamespace( $this->laravel->path( $this->argument('model').'.php') );

        $this->files->move( $this->laravel->path( $this->argument('model').'.php'), $this->laravel->path( 'Models' . DIRECTORY_SEPARATOR . $this->argument('model').'.php') );

        $this->line( $this->argument('model') . ' model created successfully and moved.' );
    }

    /**
     * Replace the App namespace at the given path.
     *
     * @param  string  $path
     * @return void
     */
    protected function replaceNamespace($path)
    {
        $this->currentRoot = trim($this->laravel->getNamespace(), '\\');

        $search = [
            'namespace '.$this->currentRoot.';',
        ];

        $replace = [
            'namespace '.$this->currentRoot.'\\Models;',
        ];

        $this->replaceIn($path, $search, $replace);
    }

    /**
     * Replace the given string in the given file.
     *
     * @param  string  $path
     * @param  string|array  $search
     * @param  string|array  $replace
     * @return void
     */
    protected function replaceIn($path, $search, $replace)
    {
        if ($this->files->exists($path)) {
            $this->files->put($path, str_replace($search, $replace, $this->files->get($path)));
        }
    }

}
