<?php
namespace eGamings\WLC;

use eGamings\WLC\Twig\TokenParserPlugin;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TwigTest;


class Template extends System
{
    public $twig;
    protected static $templateDirs = null;

    /**
     * @var array<string>
     */
    public $templatesForCounters = ['index', 'index_m'];

    public function __construct()
    {
        $is_dev = (_cfg('env') === 'dev');
        $is_qa = (_cfg('env') === 'qa' || _cfg('env') === 'dev');

        if (self::$templateDirs === null) {
            self::$templateDirs = [];
            $templateDirsDefault = [
                _cfg('template'),
                _cfg('core') . '/template',
                _cfg('root') . '/static'
            ];
            
            foreach($templateDirsDefault as $templateDir) {
                if (!is_dir($templateDir) || array_search($templateDir, self::$templateDirs) !== false) {
                    continue;
                }
                self::$templateDirs[] = $templateDir;
            }
                
        }

        //Making main variables
        $loader = new FilesystemLoader(self::$templateDirs, getcwd());
        
        $this->twig = new Environment($loader, [
            'cache' => ($is_dev ? false : _cfg('template_c')),
            'debug' => $is_qa,
        ]);

        $this->twig->addTokenParser(new TokenParserPlugin());
        $this->twig->addTokenParser(new \Twig_Extensions_TokenParser_Trans());
        $this->twig->addExtension(new \Twig_Extensions_Extension_I18n());

        $testOnDisk = new TwigTest('ondisk', function ($file) {
            return file_exists($file);
        });
        $this->twig->addTest($testOnDisk);

        if ($is_dev || $is_qa) {
            $this->twig->addExtension(new DebugExtension());
        }
    }

    public function parse($page, $return = false)
    {
        try {
            $msg = $this->twig->render($page['tpl'] . '.tpl', $page['context']);

            $msg = $this->addCounters($page['tpl'], $msg, $page);

            if ($return) {
                return $msg;
            }

            $msg = $this->addSiteRenderFinishTag($msg);
            echo $msg;
        } catch (\Exception $e) {
            error_log("Template error: " . $e->getMessage() . '<br/>' . str_replace('#', '<br/>#', $e->getTraceAsString()));
            if (_cfg('env') == 'dev') {
                die($e->getMessage() . '<br/>' . str_replace('#', '<br/>#', $e->getTraceAsString()));
            }
            die('Template error: ' . $page['tpl']);
        }

        return true;
    }

    public function addSiteRenderFinishTag(string $text): string
    {
        return str_replace('</body>', '<div style="display: none">site-render-finish</div></body>', $text);
    }

    /**
     * @param string $templateName
     * @param string $html
     * @param array $data
     *
     * @return string
     */
    public function addCounters($templateName, $html, $data = []) {
        $countersConfig = _cfg('counters');

        if ($countersConfig && is_array($countersConfig) && in_array($templateName, $this->templatesForCounters)) {
            $dom = new \DOMDocument();
            $dom->formatOutput = true;

            $dom->loadHTML($html);

            Counters::addConfig($countersConfig);
            Counters::insert($dom);

            return $dom->saveHTML();
        }

        return $html;
    }

    public function getMailTemplate($fileName, $context = [])
    {
    	$tplFile = 'mail/'.$fileName;
    	$templateLoader = $this->twig->getLoader();

        $file = '/mail/' . $fileName . '-' . _cfg('language') . '.html';

        if (_cfg('mobile') == 1) {
            $file = str_replace('/m', '', _cfg('template')) . $file; //removing mobile directory
        } else {
            $file = _cfg('template') . $file;
        }

        if (file_exists($file)) {
            return file_get_contents($file);
        } else if (file_exists(str_replace(_cfg('language'), 'ru', $file))) { //Checking if RU file exists, because it was the main from the start
            return file_get_contents(str_replace(_cfg('language'), 'ru', $file));
        } else if (is_object($templateLoader) && method_exists($templateLoader, 'exists') && $templateLoader->exists($tplFile.'.tpl')) {
        	$template = ['tpl' => $tplFile, 'context' => (array) $context + [
        	    'env' => _cfg('env'),
        	    'language' => _cfg('language'),
        	    'href' => _cfg('href'),
        	    'img' => _cfg('img'),
        	    'js' => _cfg('js'),
        	    'css' => _cfg('css')
        	]];
        	return $this->parse($template, true);
        } else {
            echo '0;File <b>' . $fileName . '-' . _cfg('language') . '.html</b> not found under the directory <b>' . $file . '</b><br />';
            return false;
        }
    }
}
