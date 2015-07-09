<?php
define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';

$app = new Illuminate\Foundation\Application(
	realpath(__DIR__)
);
$app->instance('path.public', __DIR__.'/..');

// TODO: Remove
$app->detectEnvironment(function()
{
	return 'production';
});

// LoadConfiguration
if (file_exists($configFile = __DIR__.'/../config.php')) {
	$app->instance('flarum.config', $configFile);
}

$app->instance('config', $config = new \Illuminate\Config\Repository([
	'view' => [
		'paths' => [
			realpath(base_path('resources/views'))
		],
		'compiled' => realpath(storage_path().'/framework/views'),
	],
	'mail' => [
		'driver' => 'smtp',
		'host' => 'smtp.mailgun.org',
		'port' => 587,
		'from' => ['address' => 'noreply@localhost', 'name' => 'Flarum Demo Forum'],
		'encryption' => 'tls',
		'username' => null,
		'password' => null,
		'sendmail' => '/usr/sbin/sendmail -bs',
		'pretend' => false,
	],
	'queue' => [
		'default' => 'sync',
		'connections' => [
			'sync' => [
				'driver' => 'sync',
			],
		],
	],
	'session' => [
		'driver' => 'file',
		'lifetime' => 120,
		'expire_on_close' => false,
		'encrypt' => false,
		'files' => storage_path().'/framework/sessions',
		'connection' => null,
		'table' => 'sessions',
		'lottery' => [2, 100],
		'cookie' => 'laravel_session',
		'path' => '/',
		'domain' => null,
		'secure' => false,
	],
	'cache' => [
		'default' => 'file',
		'stores' => [
			'file' => [
				'driver' => 'file',
				'path'   => storage_path().'/framework/cache',
			],
		],
		'prefix' => 'flarum',
	],
	'filesystems' => [
		'default' => 'local',
		'cloud' => 's3',
		'disks' => [
			'flarum-avatars' => [
				'driver' => 'local',
				'root'   => public_path('assets/avatars')
			],
		],
	],
]));

// ConfigureLogging
$logger = new Monolog\Logger($app->environment());
$logPath = $app->storagePath().'/logs/flarum.log';
$handler = new \Monolog\Handler\StreamHandler($logPath, Monolog\Logger::DEBUG);
$handler->setFormatter(new \Monolog\Formatter\LineFormatter(null, null, true, true));
$logger->pushHandler($handler);

$app->instance('log', $logger);
$app->alias('log', 'Psr\Log\LoggerInterface');

// RegisterFacades
use Illuminate\Support\Facades\Facade;

Facade::clearResolvedInstances();
Facade::setFacadeApplication($app);

// RegisterProviders
$serviceProviders = [

	'Illuminate\Bus\BusServiceProvider',
	'Illuminate\Cache\CacheServiceProvider',
	'Illuminate\Foundation\Providers\ConsoleSupportServiceProvider',
	'Illuminate\Cookie\CookieServiceProvider',
	'Illuminate\Encryption\EncryptionServiceProvider',
	'Illuminate\Filesystem\FilesystemServiceProvider',
	'Illuminate\Foundation\Providers\FoundationServiceProvider',
	'Illuminate\Hashing\HashServiceProvider',
	'Illuminate\Mail\MailServiceProvider',
	'Illuminate\Pagination\PaginationServiceProvider',
	'Illuminate\Pipeline\PipelineServiceProvider',
	'Illuminate\Queue\QueueServiceProvider',
	'Illuminate\Session\SessionServiceProvider',
	'Illuminate\Translation\TranslationServiceProvider',
	'Illuminate\Validation\ValidationServiceProvider',
	'Illuminate\View\ViewServiceProvider',

	'Flarum\Core\CoreServiceProvider',
	'Flarum\Core\DatabaseServiceProvider',
	'Flarum\Console\ConsoleServiceProvider',

];

foreach ($serviceProviders as $provider) {
	$app->register(new $provider($app));
}

// BootProviders
$app->boot();

use Illuminate\Foundation\Console\Kernel as IlluminateConsoleKernel;

class ConsoleKernel extends IlluminateConsoleKernel {
	protected $commands = [];
}

$app->singleton(
	'Illuminate\Contracts\Http\Kernel',
	'HttpKernel'
);

$app->singleton(
	'Illuminate\Contracts\Console\Kernel',
	'ConsoleKernel'
);

return $app;
