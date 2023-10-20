<?php
namespace eGamings\WLC;

class FixAdditionalFieldsUnicode
{
    private $fields = [
        'middleName',
        'city',
        'address',
    ];
    
    private $request;
    
    /**
     * @param  mixed $request
     * @return void
     */
    public function __construct(array $request)
    {
        $this->request = $request;
    }
    
    /**
     *
     * @return array
     */
    public function run(): array
    {
        foreach ($this->fields as $field) {
            if (
                array_key_exists($field, $this->request) 
                && $this->checkUnicodeSymbols($this->request[$field])
            ) {
                $this->request[$field] = $this->decodeUnicodeString($this->request[$field]);
            }
        }
        
        return $this->request;
    }
    
    /**
     * @param  string $text
     * @return bool
     */
    public function checkUnicodeSymbols(string $text): bool 
    {
        return preg_match('/[uU][0-9a-fA-F]{4}/', $text);
    }
        
    /**
     * @param  string $unicodeString
     * @return void
     */
    function decodeUnicodeString($unicodeString) 
    {
        $decodedString = Utils::decodeUnicode($unicodeString);

        $decodedString = preg_replace_callback('/[uU]([0-9a-fA-F]{4})/', function($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UTF-16BE');
        }, $decodedString);
        
        return $decodedString;
    }
}
