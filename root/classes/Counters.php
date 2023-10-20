<?php
namespace eGamings\WLC;

// @TODO: Need to use caching, but you need to watch the settings change, postpone until using laravel
// @TODO: Separate data and logic, postpone until using laravel
class Counters {
    private static $countersConfigTemplate = [
        'google_analytics' => [
            'html' => <<<COUNTER_HTML
<script async src="https://www.googletagmanager.com/gtag/js?id={{ google_analytics.key }}"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', '{{ google_analytics.key }}');
</script>
COUNTER_HTML,
            'insert' => 'head',
            'order' => 99999
        ],
        'google_tag_manager_noscript' => [
            'html' => <<<COUNTER_HTML
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ google_tag_manager.key }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
COUNTER_HTML,
            'insert' => 'body'
        ],
        'google_tag_manager' => [
            'html' => <<<COUNTER_HTML
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','{{ google_tag_manager.key }}');
</script>
COUNTER_HTML,
            'required' => ['google_tag_manager_noscript'],
            'insert' => 'head'
        ]
    ];

    private static $configVariables = [];

    /**
     * Add variables for templates from config
     * 
     * @param array $config [some => [thing => value]] -> [some.thing => value]
     * 
     * @return void
     */
    public static function addConfig(array $config): void {
        self::$configVariables = array_merge(self::$configVariables, self::flatArray($config));
    }

    /**
     * Inserts counters into the DOM of the page according to the configuration
     * 
     * @param \DOMDocument $dom
     * 
     * @return \DOMDocument
     */
    public static function insert(\DOMDocument $dom): \DOMDocument {
        $counterForInsert = [];
        $countersConfig = _cfg('counters');

        foreach ($countersConfig as $configKey => $configItem) {
            if (isset(self::$countersConfigTemplate[$configKey]) && isset($configItem['key'])) {
                $insert = self::$countersConfigTemplate[$configKey];
                $counterForInsert[] = $configKey;

                // Add required counters (such as noscript versions)
                if (isset($insert['required']) && is_array($insert['required'])) {
                    $counterForInsert = array_merge($counterForInsert, $insert['required']);
                }
            }
        }

        $counterForInsert = array_flip(array_unique($counterForInsert));

        // Sorting so that the highest priority is inserted last 
        uasort(self::$countersConfigTemplate, function($a, $b) {
            if (!isset($a['order'])) $a['order'] = 0;
            if (!isset($b['order'])) $b['order'] = 0;
            
            return $a['order'] - $b['order'];
        });

        foreach(self::$countersConfigTemplate as $counterID => $counter) {
            if (!isset($counterForInsert[$counterID])) continue;

            if (isset($counter['insert'])) {
                $tag = $counter['insert'];
                
                self::insertCounter($counter, $dom, $tag);
            }
        }

        return $dom;
    }

    /**
     * Expands the keys in such a way that the output will be a flat array, where the nesting will be mapped by the keys through a dot
     * E. g. [some => [thing => value]] -> [some.thing => value]
     * 
     * @param array $config Nested array
     * 
     * @return array
     */
    private static function flatArray(array $config): array {
        $ritit = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($config));
        $result = [];
        foreach ($ritit as $leafValue) {
            $keys = [];
            foreach (range(0, $ritit->getDepth()) as $depth) {
                $keys[] = $ritit->getSubIterator($depth)->key();
            }
            $result[join('.', $keys)] = $leafValue;
        }

        return $result;
    }

    /**
     * Replaces keys in the template with values from the config (@see self::$configVariables)
     * 
     * @param string $template "My key is {{ key }} and this is {{ notFoundKey }}"
     * 
     * @return string "My key is keyFromConfig and this is {{ notFoundKey }}"
     */
    private static function parseVars(string $template): string {
        return preg_replace_callback("|\{\{\s*([a-z\d\._]+)\s*\}\}|i", function($full) {
            list($full, $key) = $full;

            if (!isset(self::$configVariables[$key])) return $full;
            
            return self::$configVariables[$key];
        }, $template);
    }

    /**
     * Parses and inserts counter code into the DOM
     * 
     * @param array        $self    Counter array from (@see self::$countersConfigTemplate)
     * @param \DOMDocument $dom     DOM of the page where you need to insert counters
     * @param string       $tagName Tag to insert
     * 
     * @return \DOMDocument
     */
    private static function insertCounter(array &$self, \DOMDocument &$dom, string $tagName): \DOMDocument {
        $code = self::parseVars($self['html']);

        $node = $dom->createCDATASection($code);
        $tagNode = $dom->getElementsByTagName($tagName);

        if ($tagNode->length) {
            $tagNode = $tagNode[0];
            $tagNode->insertBefore($node, $tagNode->firstChild);
        }
        
        return $dom;
    }
}
