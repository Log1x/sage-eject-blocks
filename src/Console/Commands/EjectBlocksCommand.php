<?php

namespace Log1x\EjectBlocks\Console\Commands;

use Exception;
use WP_Filesystem_Base;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Roots\Acorn\Console\Commands\Command;

use function Roots\asset;
use function Roots\public_path;

class EjectBlocksCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'eject:blocks
                            {--defaults : Use default configuration skipping user interaction}
                            {--skip-yarn : Skip building assets for production with Yarn}';

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
     * @var string|array
     */
    protected $js = [
        'scripts',
        'js',
    ];

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
     * Create a new Eject Blocks command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;

        $this->plugins = Str::finish(WP_CONTENT_DIR, '/plugins');
        $this->js = Str::finish($this->findAssets(), '/');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->logo();

        $this->task('Setting up plugin', function () {
            if ($this->option('defaults')) {
                return;
            }

            $this->label = $this->anticipate('Plugin name?', [], $this->label);
            $this->js = Str::finish($this->anticipate('Plugin assets location?', [], $this->js), '/');
            $this->assets = explode(
                ', ',
                $this->anticipate('Plugin assets?', [], implode(', ', $this->assets))
            );
        });

        $this->task('Verifying permissions', function () {
            if (! $this->verifyPermissions()) {
                throw new Exception('Unable to write to ' . $this->plugins);
            }
        });

        if (! $this->option('skip-yarn')) {
            $this->task('Building assets for production', function () {
                return $this->exec("yarn --cwd {$this->app->basePath()} run build:production");
            }, 'running Yarn...');
        }

        $this->task('Verifying editor assets', function () {
            if (! $this->verifyAssets()) {
                throw new Exception('Editor assets missing: ' . $this->assets->implode(', '));
            }
        });

        $this->task('Verifying editor manifest', function () {
            if (! $this->verifyManifest()) {
                throw new Exception('Asset manifest missing: ' . $this->manifest);
            }
        });

        $this->task('Checking for webpack manifest', function () {
            if (! $this->verifyManifestJs()) {
                return false;
            }
        });

        $this->task('Creating plugin directory', function () {
            if (! $this->createDirectory()) {
                throw new Exception('The operation has been canceled.');
            }
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
     * Return existing asset directories.
     *
     * @return void
     */
    public function findAssets()
    {
        return collect($this->js)
            ->map(function ($directory) {
                if ($this->files->exists(public_path($directory))) {
                    return Str::finish($directory, '/');
                }
            })->implode('') ?? 'scripts/';
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
     * Displays the Eject Blocks logo and version.
     *
     * @return mixed
     */
    public function logo()
    {
        $this->line('<fg=blue;options=bold>
  ______ _           _     ____  _            _
 |  ____(_)         | |   |  _ \| |          | |
 | |__   _  ___  ___| |_  | |_) | | ___   ___| | _____
 |  __| | |/ _ \/ __| __| |  _ <| |/ _ \ / __| |/ / __|
 | |____| |  __/ (__| |_  | |_) | | (_) | (__|   <\__ \
 |______| |\___|\___|\__| |____/|_|\___/ \___|_|\_\___/
       _/ |
      |__/</>                                      <fg=white;options=bold>v1.0.0</>');

        $this->line('');
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
        $this->line("✨  The following files have been created in <info>{$this->path}</info>:");

        $this->line('');
        $this->table(
            ['File', 'Size', 'Type'],
            $files
        );
        $this->line('');

        // phpcs:ignore
        $this->line('⚠️  <options=bold>Please Note:</> <info>' . $this->label . '</info> will not enqueue assets if it detects the same assets are being ran by <info>' . wp_get_theme()->get('Name') . '</info>.');
    }
}
