<?php

namespace Log1x\EjectBlocks\Console\Commands;

use WP_Filesystem_Base;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

use function Roots\asset;

class EjectBlocksCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'eject:blocks';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Eject the theme blocks into a plugin';

    /**
     * The filesystem instance.
     *
     * @var \Roots\Acorn\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The plugins directory.
     *
     * @var string
     */
    protected $plugins;

    /**
     * The plugin destination.
     *
     * @var string
     */
    protected $path;

    /**
     * The plugin stub.
     *
     * @var string
     */
    protected $stub = 'stubs/plugin.stub';

    /**
     * The plugin name.
     *
     * @var string
     */
    protected $label = 'sage-blocks';

    /**
     * The plugin assets location.
     *
     * @var string
     */
    protected $js = 'scripts/';

    /**
     * The plugin assets.
     *
     * @var array
     */
    protected $assets = [
        'editor.js',
    ];

    /**
     * The plugin asset manifest.
     *
     * @var string
     */
    protected $manifest = 'manifest.asset.php';

    /**
     * The plugin webpack manifest.
     *
     * @var string
     */
    protected $manifestJs = 'manifest.js';

    /**
     * Create a new controller creator command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
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
        $this->plugins = Str::finish(WP_CONTENT_DIR, '/plugins');

        $this->task('Setting up plugin', function () {
            $this->label = $this->anticipate('Plugin name?', [], $this->label);
            $this->js = Str::finish($this->anticipate('Plugin assets location?', [], $this->js), '/');
            $this->assets = explode(
                ', ',
                $this->anticipate('Plugin assets?', [], implode(', ', $this->assets))
            );
        });

        $this->task('Verifying permissions', function () {
            if (! $this->verifyPermissions()) {
                return $this->error('Unable to write to ' . $this->plugins);
            }
        });

        $this->task('Building assets for production', function () {
            return $this->exec("yarn --cwd {$this->app->basePath()} run build:production");
        }, 'running Yarn...');

        $this->task('Verifying editor assets', function () {
            if (! $this->verifyAssets()) {
                return $this->error('Editor assets missing: ' . $this->assets->implode(', '));
            }
        });

        $this->task('Verifying editor manifest', function () {
            if (! $this->verifyManifest()) {
                return $this->error('Asset manifest missing: ' . $this->manifest);
            }
        });

        $this->task('Checking for webpack manifest', function () {
            if (! $this->verifyManifestJs()) {
                return;
            }
        });

        $this->task('Creating plugin directory', function () {
            return $this->createDirectory();
        });

        $this->task('Generating plugin loader', function () {
            return $this->generateLoader();
        });

        $this->task('Ejecting plugin assets', function () {
            return $this->ejectAssets();
        });

        $this->task('Ejecting plugin asset manifest', function () {
            return $this->ejectManifest();
        });

        if ($this->verifyManifestJs()) {
            $this->task('Ejecting plugin webpack manifest', function () {
                return $this->ejectManifestJs();
            });
        }

        $this->task('Activating plugin', function () {
            return $this->exec('wp plugin activate ' . $this->label);
        }, 'using WP-CLI...');


        return $this->summary();
    }

    /**
     * Check that we have permission to create a directory.
     *
     * @return bool
     */
    public function verifyPermissions()
    {
        return $this->files->isWritable($this->plugins);
    }

    /**
     * Check that the editor assets exist to extract.
     *
     * @return array|bool
     */
    public function verifyAssets()
    {
        $assets = collect($this->assets)
            ->reject(function ($asset) {
                return asset(
                    Str::start($asset, $this->js)
                )->exists();
            });

        if ($assets->isEmpty()) {
            return true;
        }

        $this->assets = $assets;

        return false;
    }

    /**
     * Verify the asset manifest exists to eject.
     *
     * @return bool
     */
    public function verifyManifest()
    {
        return asset(
            Str::start($this->manifest, $this->js)
        )->exists();
    }

    /**
     * Verify the webpack manifest exists to eject.
     *
     * @return bool
     */
    public function verifyManifestJs()
    {
        return asset(
            Str::start($this->manifestJs, $this->js)
        )->exists();
    }

    /**
     * Create a plugin directory.
     *
     * @return bool
     */
    public function createDirectory()
    {
        $this->path = Str::finish($this->plugins, '/' . $this->label);

        if (
            $this->files->isDirectory($this->path) &&
            ! $this->confirm('A plugin containing block assets already exists. Do you wish to overwrite it?')
        ) {
                return false;
        }

        $this->files->deleteDirectory($this->path);

        return $this->files->makeDirectory($this->path);
    }

    /**
     * Eject the plugin assets to the defined path.
     *
     * @return bool
     */
    public function ejectAssets()
    {
        return collect($this->assets)->each(function ($asset) {
            return $this->files->copy(
                asset($this->js . $asset)->path(),
                Str::finish($this->path, '/' . basename($asset))
            );
        });
    }

    /**
     * Eject the asset manifest to the defined plugin path..
     *
     * @return bool
     */
    public function ejectManifest()
    {
        return $this->files->copy(
            asset(
                Str::start($this->manifest, $this->js)
            )->path(),
            Str::finish($this->path, '/' . basename($this->manifest))
        );
    }

    /**
     * Eject the asset manifest to the defined plugin path..
     *
     * @return bool
     */
    public function ejectManifestJs()
    {
        return $this->files->copy(
            asset(
                Str::start($this->manifestJs, $this->js)
            )->path(),
            Str::finish($this->path, '/' . basename($this->manifestJs))
        );
    }

    /**
     * Generate the plugin loader using the theme stylesheet and move it to the destination.
     *
     * @return bool
     */
    public function generateLoader()
    {
        $stub = $this->files->get(__DIR__ . '/' . $this->stub);
        $assets = "'" . implode("', '", $this->assets) . "'";

        $stub = str_replace([
            'DummyThemeName',
            'DummyThemeUri',
            'DummyAuthorName',
            'DummyAuthorUri',
            'DummyTextDomain',
            'DummyScripts',
            'DummyManifest'
        ], [
            wp_get_theme()->get('Name'),
            wp_get_theme()->get('ThemeURI'),
            wp_get_theme()->get('Author'),
            wp_get_theme()->get('AuthorURI'),
            wp_get_theme()->get('TextDomain'),
            $assets,
            $this->manifest
        ], $stub);

        return $this->files->put($this->path . '/plugin.php', $stub);
    }

    /**
     * Generate a table displaying the folder containing the plugin.
     *
     * @return mixed
     */
    public function summary()
    {
        $files = collect($this->files->files($this->path))->map(function ($file) {
            return [
                'name' => $file->getFilename(),
                'size' => number_format($file->getSize() / 1024, 2) . ' KiB',
                'type' => ucfirst($file->getType())
            ];
        })->all();

        $this->title('Your blocks have been ejected.');
        $this->line("The following assets have been created in <info>{$this->path}</info>:");
        $this->line('');
        $this->table(
            ['File', 'Size', 'Type'],
            $files
        );
        $this->line('');
        $this->warn("Note: <info>{$this->label}</info> will not enqueue assets if it detects the same assets are");
        $this->warn('being ran by <info>' . wp_get_theme()->get('Name') . '</info>.');
    }
}
