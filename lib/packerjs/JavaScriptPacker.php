<?php

/* By Dean Edwards then Nicolas Martin.
 * KNOWN BUG: erroneous behavior when using escapeChar with a replacement
 * value that is a function
 *
 * # The packed result may be different than with the Dean Edwards
 *   version, but with the same length. The reason is that the PHP
 *   function usort to sort array don't necessarily preserve the
 *   original order of two equal member. The Javascript sort function
 *   in fact preserve this order (but that's not require by the
 *   ECMAScript standard). So the encoded keywords order can be
 *   different in the two results.
 *
 * # Be careful with the 'High ASCII' Level encoding if you use
 *   UTF-8 in your files...
 */
namespace lib\packerjs;

class JavaScriptPacker
{
  // constants
  const IGNORE = '$1';

  // validate parameters
  private $_script = '',
    $_encoding = 62,
    $_fastDecode = true,
    $_specialChars = false,
    $LITERAL_ENCODING = array(
      'None' => 0,
      'Numeric' => 10,
      'Normal' => 62,
      'High ASCII' => 95
    ),
  // keep a list of parsing functions, they'll be executed all at once
    $_parsers = array(),
    $_count = array();

  /**
   * Constructor
   *
   * @param string     $script       The JavaScript to pack
   * @param int|string $encoding     Level of encoding 0,10,62,95 or 'None', 'Numeric', 'Normal', 'High ASCII'.
   * @param bool       $fastDecode   If true, includes the fast decoder in the packed result
   * @param bool       $specialChars If you have flagged your private and local variables in the script
   *
   * @return JavaScriptPacker
   */
  public function __construct($_script, $_encoding = 62, $_fastDecode = true, $_specialChars = false)
  {
    $this->_script = $_script . "\n";
    if (array_key_exists($_encoding, $this->LITERAL_ENCODING))
      $_encoding = $this->LITERAL_ENCODING[$_encoding];
    $this->_encoding = min((int) $_encoding, 95);
    $this->_fastDecode = $_fastDecode;
    $this->_specialChars = $_specialChars;
  }

  public function pack()
  {
    $this->_addParser('_basicCompression');
    if ($this->_specialChars)
      $this->_addParser('_encodeSpecialChars');
    if ($this->_encoding)
      $this->_addParser('_encodeKeywords');

    return $this->_pack($this->_script);
  }

  // apply all parsing routines
  private function _pack($script)
  {
    for ($i = 0; isset($this->_parsers[$i]); $i+=1)
    {
      $script = call_user_func(array(&$this, $this->_parsers[$i]), $script);
    }

    return $script;
  }

  private function _addParser($parser) { $this->_parsers[] = $parser; }

  // zero encoding - just removal of white space and comments
  private function _basicCompression($script)
  {
    $parser = new ParseMaster();
    // make safe
    $parser->escapeChar = '\\';
    // protect strings
    $parser->add('/\'[^\'\\n\\r]*\'/', self::IGNORE);
    $parser->add('/"[^"\\n\\r]*"/', self::IGNORE);
    // remove comments
    $parser->add('/\\/\\/[^\\n\\r]*[\\n\\r]/', ' ');
    $parser->add('/\\/\\*[^*]*\\*+([^\\/][^*]*\\*+)*\\//', ' ');
    // protect regular expressions
    $parser->add('/\\s+(\\/[^\\/\\n\\r\\*][^\\/\\n\\r]*\\/g?i?)/', '$2'); // IGNORE
    $parser->add('/[^\\w\\x24\\/\'"*)\\?:]\\/[^\\/\\n\\r\\*][^\\/\\n\\r]*\\/g?i?/', self::IGNORE);
    // remove: ;;; doSomething();
    if ($this->_specialChars)
      $parser->add('/;;;[^\\n\\r]+[\\n\\r]/');
    // remove redundant semi-colons
    $parser->add('/\\(;;\\)/', self::IGNORE); // protect for (;;) loops
    $parser->add('/;+\\s*([};])/', '$2');
    // apply the above
    $script = $parser->exec($script);

    // remove white-space
    $parser->add('/(\\b|\\x24)\\s+(\\b|\\x24)/', '$2 $3');
    $parser->add('/([+\\-])\\s+([+\\-])/', '$2 $3');
    $parser->add('/\\s+/', '');

    return $parser->exec($script);
  }

  private function _encodeSpecialChars($script)
  {
    $parser = new ParseMaster();
    // replace: $name -> n, $$name -> na
    $parser->add('/((\\x24+)([a-zA-Z$_]+))(\\d*)/', array('fn' => '_replace_name'));
    // replace: _name -> _0, double-underscore (__name) is ignored
    $regexp = '/\\b_[A-Za-z\\d]\\w*/';
    // build the word list
    $keywords = $this->_analyze($script, $regexp, '_encodePrivate');
    // quick ref
    $encoded = $keywords['encoded'];

    $parser->add($regexp, array(
      'fn' => '_replace_encoded',
      'data' => $encoded
      )
    );

    return $parser->exec($script);
  }

  private function _encodeKeywords($script)
  {
    // escape high-ascii values already in the script (i.e. in strings)
    if ($this->_encoding > 62)
      $script = $this->_escape95($script);
    // create the parser
    $parser = new ParseMaster();
    $encode = $this->_getEncoder($this->_encoding);
    // for high-ascii, don't encode single character low-ascii
    $regexp = ($this->_encoding > 62) ? '/\\w\\w+/' : '/\\w+/';
    // build the word list
    $keywords = $this->_analyze($script, $regexp, $encode);
    $encoded = $keywords['encoded'];

    // encode
    $parser->add($regexp, array(
      'fn' => '_replace_encoded',
      'data' => $encoded
      )
    );

    return (empty($script)) ? $script : $this->_bootStrap($parser->exec($script), $keywords);
  }

  private function _analyze($script, $regexp, $encode)
  {
    /* analyse and retreive all words in the script
     *
     * instances of "protected" words
     * dictionary of word->encoding
     * list of words sorted by frequency
     */
    $_protected = $_encoded = $_sorted = $all = array();
    preg_match_all($regexp, $script, $all);
    $all = $all[0]; // simulate the javascript comportement of global match
    if (!empty($all))
    {
      /* word->count
       * same list, not sorted
       * "protected" words (dictionary of word->"word")
       * dictionary of charCode->encoding (eg. 256->ff)
       */
      $this->_count = $unsorted = $protected = $value = array();

      $i = count($all);
      $j = 0; //$word = null;
      // count the occurrences - used for sorting later
      do
      {
        --$i;
        $word = '$' . $all[$i];
        if (!isset($this->_count[$word]))
        {
          $this->_count[$word] = 0;
          $unsorted[$j] = $word;
          // make a dictionary of all of the protected words in this script
          //  these are words that might be mistaken for encoding
          //if (is_string($encode) && method_exists($this, $encode))
          $values[$j] = call_user_func(array(&$this, $encode), $j);
          $protected['$' . $values[$j]] = $j++;
        }
        // increment the word counter
        $this->_count[$word]++;
      } while ($i > 0);
      // prepare to sort the word list, first we must protect
      //  words that are also used as codes. we assign them a code
      //  equivalent to the word itself.
      // e.g. if "do" falls within our encoding range
      //      then we store keywords["do"] = "do";
      // this avoids problems when decoding
      $i = count($unsorted);
      do
      {
        $word = $unsorted[--$i];
        if (isset($protected[$word]) /* != null */)
        {
          $_sorted[$protected[$word]] = substr($word, 1);
          $_protected[$protected[$word]] = true;
          $this->_count[$word] = 0;
        }
      } while ($i);

      usort($unsorted, array(&$this, '_sortWords'));
      $j = 0;
      // because there are "protected" words in the list
      //  we must add the sorted words around them
      do
      {
        if (!isset($_sorted[$i]))
          $_sorted[$i] = substr($unsorted[$j++], 1);
        $_encoded[$_sorted[$i]] = $values[$i];
      } while (++$i < count($unsorted));
    }

    return array(
      'sorted' => $_sorted,
      'encoded' => $_encoded,
      'protected' => $_protected);
  }

  /**
   * @param type $match1
   * @param type $match2
   * @return type
   */
  private function _sortWords($match1, $match2) { return $this->_count[$match2] - $this->_count[$match1]; }

  /**
   * Builds the boot function used for loading and decoding
   *
   * @param string $packed
   * @param type $keywords
   *
   * @return type
   */
  private function _bootStrap($packed, $keywords)
  {
    $ENCODE = $this->_safeRegExp('$encode\\($count\\)');

    // $packed: the packed script
    $packed = "'" . $this->_escape($packed) . "'";

    // $ascii: base for encoding
    $ascii = min(count($keywords['sorted']), $this->_encoding);
    if (0 == $ascii)
      $ascii = 1;

    // $count: number of words contained in the script
    $count = count($keywords['sorted']);

    // $keywords: list of words contained in the script
    foreach ($keywords['protected'] as $i => $value)
    {
      $keywords['sorted'][$i] = '';
    }
    // convert from a string to an array
    ksort($keywords['sorted']);
    $keywords = "'" . implode('|', $keywords['sorted']) . "'.split('|')";

    $encode = ($this->_encoding > 62) ? '_encode95' : $this->_getEncoder($ascii);
    $encode = $this->_getJSFunction($encode);
    $encode = preg_replace('/_encoding/', '$ascii', $encode);
    $encode = preg_replace('/arguments\\.callee/', '$encode', $encode);
    $inline = '\\$count' . ($ascii > 10 ? '.toString(\\$ascii)' : '');

    // $decode: code snippet to speed up decoding
    if ($this->_fastDecode)
    {
      // create the decoder
      $decode = $this->_getJSFunction('_decodeBody');
      if ($this->_encoding > 62)
        $decode = preg_replace('/\\\\w/', '[\\xa1-\\xff]', $decode);
      // perform the encoding inline for lower ascii values
      elseif ($ascii < 36)
        $decode = preg_replace($ENCODE, $inline, $decode);
      // special case: when $count==0 there are no keywords. I want to keep
      //  the basic shape of the unpacking funcion so i'll frig the code...
      if (0 == $count)
        $decode = preg_replace($this->_safeRegExp('($count)\\s*=\\s*1'), '$1=0', $decode, 1);
    }

    // boot function
    $unpack = $this->_getJSFunction('_unpack');
    if ($this->_fastDecode)
    {
      // insert the decoder
      $this->buffer = $decode;
      $unpack = preg_replace_callback('/\\{/', array(&$this, '_insertFastDecode'), $unpack, 1);
    }
    $unpack = preg_replace('/"/', "'", $unpack);
    if ($this->_encoding > 62)
    // high-ascii, get rid of the word-boundaries for regexp matches
      $unpack = preg_replace('/\'\\\\\\\\b\'\s*\\+|\\+\s*\'\\\\\\\\b\'/', '', $unpack);

    if ($ascii > 36 || $this->_encoding > 62 || $this->_fastDecode)
    {
      // insert the encode function
      $this->buffer = $encode;
      $unpack = preg_replace_callback('/\\{/', array(&$this, '_insertFastEncode'), $unpack, 1);
    } else
    // perform the encoding inline
      $unpack = preg_replace($ENCODE, $inline, $unpack);

    // pack the boot function too
    $unpackPacker = new JavaScriptPacker($unpack, 0, false, true);
    $unpack = $unpackPacker->pack();

    // arguments
    $params = array($packed, $ascii, $count, $keywords);
    if ($this->_fastDecode)
    {
      $params[] = 0;
      $params[] = '{}';
    }
    $params = implode(',', $params);

    // the whole thing
    return 'eval(' . $unpack . '(' . $params . "))\n";
  }

  private $buffer;

  private function _insertFastDecode($match) { return '{' . $this->buffer . ';'; }

  private function _insertFastEncode($match) { return '{$encode=' . $this->buffer . ';'; }

  /**
   * @param int $ascii
   *
   * @return string
   */
  private function _getEncoder($ascii)
  {
    return $ascii > 10 ? $ascii > 36 ? $ascii > 62 ?
          '_encode95' : '_encode62'  : '_encode36'  : '_encode10';
  }

  /**
   * No encoding
   *
   * @param type $charCode
   *
   * @return type
   */
  private function _encode10($charCode) { return $charCode; }

  /**
   * inherent base36 support
   * characters: 0123456789abcdefghijklmnopqrstuvwxyz
   *
   * @param type $charCode
   *
   * @return type
   */
  private function _encode36($charCode) { return base_convert($charCode, 10, 36); }

  /**
   * Hitch a ride on base36 and add the upper case alpha characters
   * characters: 0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ
   *
   * @param type $charCode
   *
   * @return type
   */
  private function _encode62($charCode)
  {
    $res = '';
    if ($charCode >= $this->_encoding)
      $res = $this->_encode62((int) ($charCode / $this->_encoding));

    $charCode = $charCode % $this->_encoding;

    return ($charCode > 35) ? $res . chr($charCode + 29) : $res . base_convert($charCode, 10, 36);
  }

  /**
   * use high-ascii values
   * characters: ¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶·¸¹º»¼½¾¿ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþ
   *
   * @param type $charCode
   *
   * @return type
   */
  private function _encode95($charCode)
  {
    $res = '';
    if ($charCode >= $this->_encoding)
      $res = $this->_encode95($charCode / $this->_encoding);

    return $res . chr(($charCode % $this->_encoding) + 161);
  }

  /**
   *
   * @param string $string
   *
   * @return string
   */
  private function _safeRegExp($string) { return '/' . preg_replace('/\$/', '\\\$', $string) . '/'; }

  /**
   *
   * @param string $charCode
   *
   * @return string The charCode preceded by '_'
   */
  private function _encodePrivate($charCode) { return '_' . $charCode; }

  // protect characters used by the parser
  private function _escape($script) { return preg_replace('/([\\\\\'])/', '\\\$1', $script); }

  // protect high-ascii characters already in the script
  private function _escape95($script)
  {
    return preg_replace_callback(
        '/[\\xa1-\\xff]/', array(&$this, '_escape95Bis'), $script
    );
  }

  private function _escape95Bis($match) { return '\x' . ((string) dechex(ord($match))); }

  private function _getJSFunction($aName)
  {
    return (defined('self::JSFUNCTION' . $aName)) ? constant('self::JSFUNCTION' . $aName) : '';
  }

  // JavaScript Functions used.
  // Note : In Dean's version, these functions are converted
  // with 'String(aFunctionName);'.
  // This internal conversion complete the original code, ex :
  // 'while (aBool) anAction();' is converted to
  // 'while (aBool) { anAction(); }'.
  // The JavaScript functions below are corrected.
  // unpacking function - this is the boot strap function
  //  data extracted from this packing routine is passed to
  //  this function when decoded in the target
  // NOTE ! : without the ';' final.
  const JSFUNCTION_unpack =
    'function($packed, $ascii, $count, $keywords, $encode, $decode) {
    while ($count--) {
        if ($keywords[$count]) {
            $packed = $packed.replace(new RegExp(\'\\\\b\' + $encode($count) + \'\\\\b\', \'g\'), $keywords[$count]);
        }
    }
    return $packed;
}';

  // code-snippet inserted into the unpacker to speed up decoding
  const JSFUNCTION_decodeBody =
    '    if (!\'\'.replace(/^/, String)) {
        // decode all the values we need
        while ($count--) {
            $decode[$encode($count)] = $keywords[$count] || $encode($count);
        }
        // global replacement function
        $keywords = [function ($encoded) {return $decode[$encoded]}];
        // generic match
        $encode = function () {return \'\\\\w+\'};
        // reset the loop counter -  we are now doing a global replace
        $count = 1;
    }
';
  // zero encoding
  // characters: 0123456789
  const JSFUNCTION_encode10 =
    'function($charCode) {
    return $charCode;
}'; //;';
  // inherent base36 support
  // characters: 0123456789abcdefghijklmnopqrstuvwxyz
  const JSFUNCTION_encode36 =
    'function($charCode) {
    return $charCode.toString(36);
}'; //;';
  // hitch a ride on base36 and add the upper case alpha characters
  // characters: 0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ
  const JSFUNCTION_encode62 =
    'function($charCode) {
    return ($charCode < _encoding ? \'\' : arguments.callee(parseInt($charCode / _encoding))) +
    (($charCode = $charCode % _encoding) > 35 ? String.fromCharCode($charCode + 29) : $charCode.toString(36));
}';

  // use high-ascii values
  // characters: ¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶·¸¹º»¼½¾¿ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõö÷øùúûüýþ
  const JSFUNCTION_encode95 =
    'function($charCode) {
    return ($charCode < _encoding ? \'\' : arguments.callee($charCode / _encoding)) +
        String.fromCharCode($charCode % _encoding + 161);
  }';

}

class ParseMaster
{
  public $ignoreCase = false,
    $escapeChar = '';

  // constants
  const EXPRESSION = 0,
    REPLACEMENT = 1,
    LENGTH = 2;

  // used to determine nesting levels
  private $GROUPS = '/\\(/', //g
    $SUB_REPLACE = '/\\$\\d/',
    $INDEXED = '/^\\$\\d+$/',
    $TRIM = '/([\'"])\\1\\.(.*)\\.\\1\\1$/',
    $ESCAPE = '/\\\./', //g
    $QUOTE = '/\'/',
    $DELETED = '/\\x01[^\\x01]*\\x01/', //g
    $_escaped = array(),  // escaped characters
    $_patterns = array(), // patterns stored by index
    $buffer;

  public function add($expression, $replacement = '')
  {
    // count the number of sub-expressions
    //  - add one because each pattern is itself a sub-expression
    $length = 1 + preg_match_all($this->GROUPS, $this->_internalEscape((string) $expression), $out);

    // treat only strings $replacement
    if (is_string($replacement))
    {
      // does the pattern deal with sub-expressions?
      if (preg_match($this->SUB_REPLACE, $replacement))
      {
        // a simple lookup? (e.g. "$2")
        if (preg_match($this->INDEXED, $replacement))
        {
          // store the index (used for fast retrieval of matched strings)
          $replacement = (int) (substr($replacement, 1)) - 1;
        } else
        { // a complicated lookup (e.g. "Hello $2 $1")
          // build a function to do the lookup
          $quote = preg_match($this->QUOTE, $this->_internalEscape($replacement)) ? '"' : "'";
          $replacement = array(
            'fn' => '_backReferences',
            'data' => array(
              'replacement' => $replacement,
              'length' => $length,
              'quote' => $quote
            )
          );
        }
      }
    }
    // pass the modified arguments
    if (!empty($expression))
      $this->_add($expression, $replacement, $length);
    else
      $this->_add('/^$/', $replacement, $length);
  }

  public function exec($string)
  {
    // execute the global replacement
    $this->_escaped = array();

    // simulate the _patterns.toSTring of Dean
    $regexp = '/';
    foreach ($this->_patterns as $reg)
    {
      $regexp .= '(' . substr($reg[self::EXPRESSION], 1, -1) . ')|';
    }
    $regexp = substr($regexp, 0, -1) . '/';
    $regexp .= ($this->ignoreCase) ? 'i' : '';

    $string = $this->_escape($string, $this->escapeChar);
    $string = preg_replace_callback(
      $regexp, array(
      &$this,
      '_replacement'
      ), $string
    );
    $string = $this->_unescape($string, $this->escapeChar);

    return preg_replace($this->DELETED, '', $string);
  }

  /** clear the patterns collection so that this object may be re-used */
  public function reset() { $this->_patterns = array(); }

  /** create and add a new pattern to the patterns collection */
  private function _add()
  {
    $arguments = func_get_args();
    $this->_patterns[] = $arguments;
  }

  /** this is the global replace function (it's quite complicated) */
  private function _replacement($arguments)
  {
    if (empty($arguments))
      return '';

    $i = 1;
    $j = 0;
    // loop through the patterns
    while (isset($this->_patterns[$j]))
    {
      $pattern = $this->_patterns[$j++];
      // do we have a result?
      if (isset($arguments[$i]) && ($arguments[$i] != ''))
      {
        $replacement = $pattern[self::REPLACEMENT];

        if (is_array($replacement) && isset($replacement['fn']))
        {
          if (isset($replacement['data']))
            $this->buffer = $replacement['data'];
          return call_user_func(array(&$this, $replacement['fn']), $arguments, $i);
        } else if (is_int($replacement))
          return $arguments[$replacement + $i];

        $delete = ('' == $this->escapeChar ||
          strpos($arguments[$i], $this->escapeChar) === false) ? '' : "\x01" . $arguments[$i] . "\x01";

        // skip over references to sub-expressions
        return $delete . $replacement;
      } else
        $i += $pattern[self::LENGTH];
    }
  }

  /**
   * @param type $match
   * @param type $offset
   *
   * @return type
   */
  private function _backReferences($match, $offset)
  {
    $replacement = $this->buffer['replacement'];
    $quote = $this->buffer['quote'];
    $i = $this->buffer['length'];
    while ($i)
    {
      $replacement = str_replace('$' . $i--, $match[$offset + $i], $replacement);
    }

    return $replacement;
  }

  /**
   * @param type $match
   * @param type $offset
   *
   * @return type
   */
  private function _replace_name($match, $offset)
  {
    $length = strlen($match[$offset + 2]);
    $start = $length - max($length - strlen($match[$offset + 3]), 0);

    return substr($match[$offset + 1], $start, $length) . $match[$offset + 4];
  }

  /**
   * @param type $match
   * @param type $offset
   *
   * @return type
   */
  private function _replace_encoded($match, $offset) { return $this->buffer[$match[$offset]]; }

  /* php : we cannot pass additional data to preg_replace_callback,
   * and we cannot use &$this in create_function, so let's go to lower level
   *
   * encode escaped characters
   */

  private function _escape($string, $escapeChar)
  {
    if ($escapeChar)
    {
      $this->buffer = $escapeChar;
      return preg_replace_callback(
          '/\\' . $escapeChar . '(.)' . '/', array(&$this, '_escapeBis'), $string
      );
    } else
      return $string;
  }

  /**
   * @param type $match
   *
   * @return type
   */
  private function _escapeBis($match)
  {
    $this->_escaped[] = $match[1];

    return $this->buffer;
  }

  /**
   * Decode escaped characters
   *
   * @param type $string
   * @param type $escapeChar
   *
   * @return type
   */
  private function _unescape($string, $escapeChar)
  {
    if ($escapeChar)
    {
      $regexp = '/' . '\\' . $escapeChar . '/';
      $this->buffer = array('escapeChar' => $escapeChar, 'i' => 0);

      return preg_replace_callback($regexp, array(&$this, '_unescapeBis'), $string);
    } else
      return $string;
  }

  private function _unescapeBis()
  {
    $temp = (isset($this->_escaped[$this->buffer['i']])
      && '' != $this->_escaped[$this->buffer['i']])
      ? $this->_escaped[$this->buffer['i']]
      : '';

    $this->buffer['i']++;

    return $this->buffer['escapeChar'] . $temp;
  }

  /**
   * @param type $string
   *
   * @return type
   */
  private function _internalEscape($string)
  {
    return preg_replace($this->ESCAPE, '', $string);
  }
}
?>
