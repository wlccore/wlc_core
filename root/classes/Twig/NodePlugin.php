<?php
namespace eGamings\WLC\Twig;

/*
 * This file is not part of Twig.
 *
 * (c) 2014 eGamings
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a plugin node.
 *
 * @package    twig
 * @subpackage Twig-extensions
 * @author     eGamings
 * @version    SVN: $Id$
 */
class NodePlugin extends \Twig_Node
{
    public function __construct($file, $lineno)
    {
        parent::__construct(array('expr' => null), array('file' => $file), $lineno);
    }

    /**
     * Compiles the node to PHP.
     *
     * @param $compiler Twig_Compiler A Twig_Compiler instance
     */
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        $file = $this->attributes['file'];

        if (strpos($file, '../') > -1) {
            $plugin = 'Scripts allowed only from plugin directory (' . _cfg('theme') . array_pop(explode(_cfg('theme'), _cfg('plugins'))) . ')';
        } else if (!file_exists(_cfg('plugins') . '/' . $file)) {
            $plugin = 'No plugin file ' . $file;
        } else {
            ob_start();
            include_once(_cfg('plugins') . '/' . $file);
            $plugin = ob_get_contents();
            ob_end_clean();
        }

        $compiler->write("?>" . $plugin . "<?php");

    }
}
