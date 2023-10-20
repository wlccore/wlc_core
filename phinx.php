<?php
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

error_reporting(E_ALL);

global $cfg;
$cwd = getcwd();

$argvInput = new ArgvInput($_SERVER['argv'], new InputDefinition([
    new InputArgument('command', InputArgument::OPTIONAL),
    new InputArgument('name', InputArgument::OPTIONAL),
    new InputOption('configuration', 'c', InputOption::VALUE_OPTIONAL),
    new InputOption('data', 'd', InputOption::VALUE_OPTIONAL),
    new InputOption('environment', 'e', InputOption::VALUE_OPTIONAL, '', 'prod'),
    new InputOption('dry-run', 'x', InputOption::VALUE_OPTIONAL),
    new InputOption('force', 'f', InputOption::VALUE_OPTIONAL),
    new InputOption('parser', 'p', InputOption::VALUE_OPTIONAL),
    new InputOption('target', 't', InputOption::VALUE_OPTIONAL),
]));

$cfg['env'] = $argvInput->getOption('environment');
$command = $argvInput->hasArgument('command') ? $argvInput->getArgument('command') : '';

switch ($command) {
    case 'create':
        return [
            'paths' => [
                'migrations' => 'db/migrations'
            ]
        ];
}

$envConfigPath = $cwd . '/vendor/egamings/wlc_core/root/inc/siteconfig.php';
$envDefaultConfigPath = $cwd . '/vendor/egamings/wlc_core/root/inc/siteconfig-' . $cfg['env'] . '.php';
$wlcConfigPath = $cwd . '/roots/siteconfig.php';

try {

    if (file_exists($envConfigPath)) {
        require($envConfigPath);
    } else if (file_exists($envDefaultConfigPath)) {
    	require($envDefaultConfigPath);
    }

    if (!file_exists($wlcConfigPath)) {
        throw new Exception('Casino config file not found');
    }

    require($wlcConfigPath);

    // Load ovveride config
    eGamings\WLC\Config::load();

    if (empty($cfg['dbHost']) || empty($cfg['dbBase'])) {
        throw new Exception('Database config not found');
    }

    /**
     * Custom db params from env
     */
    $envVars = ['PHINX_DBHOST' => 'dbHost', 'PHINX_DBNAME' => 'dbBase', 'PHINX_DBUSER' => 'dbUser', 
                'PHINX_DBPASS' => 'dbPass', 'PHINX_DBPORT' => 'dbPort'];

    foreach ($envVars as $envVar => $cfgVar) {
        $envValue = getenv($envVar);
        if (!empty($envValue)) {
            $cfg[$cfgVar] = $envValue;
        }
    }

    if (empty($cfg['dbPort'])) {
        $cfg['dbPort'] = 3306;
    }

    $dsn = 'mysql:dbname=' . $cfg['dbBase'] . ';host=' . $cfg['dbHost'] . ';port=' . $cfg['dbPort'];
    $dbh = new PDO($dsn, $cfg['dbUser'], $cfg['dbPass']);

} catch (Exception $e) {
    throw new Exception("DB migration error: " . $e->getMessage());
}

$phinxConfig = [
    'paths' => [
        'migrations' => $cwd . '/vendor/egamings/wlc_core/db/migrations'
    ],
    'environments' => [
        'default_migration_table' => 'migrationlog',
        'default_database' => $cfg['env'],
        $cfg['env'] => [
            'name' => $cfg['dbBase'],
            'connection' => $dbh,
        ]
    ]
];

return $phinxConfig;
