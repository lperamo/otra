<?php declare(strict_types=1);
namespace otra\cache\php;use \Exception; use \Error; use \Throwable; use \stdClass; use \RecursiveDirectoryIterator; use \RecursiveIteratorIterator; use Phar; use \PharData; use \DateTime; use \Redis;const DOUBLE_ERASE_SEQUENCE=ERASE_SEQUENCE.ERASE_SEQUENCE,ADD_BOLD="\e[1m",REMOVE_BOLD_INTENSITY="\e[22m",ADD_UNDERLINE="\e[4m",REMOVE_UNDERLINE="\e[24m",END_COLOR="\e[0m",CLI_BASE="\e[38;2;190;190;190m",CLI_SUCCESS="\e[38;2;100;200;100m",CLI_INFO="\e[38;2;100;150;200m",CLI_ERROR="\e[38;2;255;100;100m",CLI_INFO_HIGHLIGHT="\e[38;2;100;200;200m",CLI_TABLE="\e[38;2;100;180;255m",CLI_TABLE_HEADER="\e[38;2;75;100;255m",CLI_WARNING="\e[38;2;190;190;100m",CLI_GRAY="\e[38;2;160;160;160m",CLI_INDENT_COLOR_FIRST=ADD_BOLD.CLI_INFO,CLI_INDENT_COLOR_SECOND=ADD_BOLD.CLI_ERROR,CLI_INDENT_COLOR_FOURTH=ADD_BOLD.CLI_INFO_HIGHLIGHT,CLI_INDENT_COLOR_FIFTH="\e[38;2;150;0;255m",CLI_DUMP_LINE_HIGHLIGHT="\e[38;2;140;200;255m",CLI_LINE_DUMP="\e[38;2;83;148;236m",ERASE_SEQUENCE="\033[1A\r\033[K",SUCCESS=CLI_SUCCESS.' ✔'.END_COLOR.PHP_EOL,OTRA_KEY_PERMISSIONS_POLICY='permissionsPolicy',OTRA_KEY_CONTENT_SECURITY_POLICY='csp',OTRA_KEY_SCRIPT_SRC_DIRECTIVE='script-src',OTRA_KEY_STYLE_SRC_DIRECTIVE='style-src',OTRA_LABEL_SECURITY_NONE="'none'",OTRA_LABEL_SECURITY_SELF="'self'",OTRA_LABEL_SECURITY_STRICT_DYNAMIC="'strict-dynamic'",OTRA_POLICY=0,OTRA_POLICIES=[OTRA_KEY_PERMISSIONS_POLICY=>'Permissions-Policy: ',OTRA_KEY_CONTENT_SECURITY_POLICY=>'Content-Security-Policy: '],OTRA_ROUTES_PREFIX='otra',CSP_ARRAY=['base-uri'=>OTRA_LABEL_SECURITY_SELF,'form-action'=>OTRA_LABEL_SECURITY_SELF,'frame-ancestors'=>OTRA_LABEL_SECURITY_NONE,'default-src'=>OTRA_LABEL_SECURITY_NONE,'font-src'=>OTRA_LABEL_SECURITY_SELF,'img-src'=>OTRA_LABEL_SECURITY_SELF,'object-src'=>OTRA_LABEL_SECURITY_SELF,'connect-src'=>OTRA_LABEL_SECURITY_SELF,'child-src'=>OTRA_LABEL_SECURITY_SELF,'manifest-src'=>OTRA_LABEL_SECURITY_SELF,OTRA_KEY_STYLE_SRC_DIRECTIVE=>OTRA_LABEL_SECURITY_SELF,OTRA_KEY_SCRIPT_SRC_DIRECTIVE=>OTRA_LABEL_SECURITY_STRICT_DYNAMIC],CONTENT_SECURITY_POLICY=[DEV=>CSP_ARRAY,PROD=>CSP_ARRAY],PERMISSIONS_POLICY=[DEV=>['sync-xhr'=>''],PROD=>[]],CACHE_TIME=300;abstract class AllConfig
{
  public static int $verbose = 0;
  public static bool $cssSourceMaps = true;
  public static string
    $cachePath = CACHE_PATH,
    $defaultConn = 'CMS',  $nodeBinariesPath = '/home/lionel/.nvm/versions/node/v20.5.0/bin/';  public static array
    $debugConfig = [
      'maxData' => 514,
      'maxDepth' => 6
    ], $deployment = [
      'domainName' => 'otra.tech',
      'server' => 'lionelp@vps812032.ovh.net',
      'port' => 49153,
      'folder' => '/var/www/html/test/',
      'privateSshKey' => '~/.ssh/id_rsa',
      'gcc' => true
    ],
    $dbConnections = [
      'CMS' => [
        'driver' => '\PDOMySQL',
        'host' => 'localhost',
        'port' => '',
        'db' => 'lpcms',
        'motor' => 'InnoDB'
      ]
    ], $sassLoadPaths = [BUNDLES_PATH . 'HelloWorld/resources/']; }/**
 * THE framework global config
 *
 * @author Lionel Péramo
 */

const
  VERSION = '2025.0.0',
  RESOURCE_FILE_MIN_SIZE = 21000; // n characters

// require_once 'cause maybe the class OtraException will attempt to load it too !
/** THE framework production config
 *
 * @author Lionel Péramo
 */ /**
 * @package config
 */

AllConfig::$dbConnections['CMS']['login'] = $_SERVER['TEST_LOGIN'];
AllConfig::$dbConnections['CMS']['password'] = $_SERVER['TEST_PASSWORD'];

$externalConfigFile = BUNDLES_PATH . 'config/Config.php';

if (file_exists($externalConfigFile))
  require $externalConfigFile;

if ($_SERVER[APP_ENV] === PROD && PHP_SAPI !== 'cli')
  class_alias('otra\\cache\\php\\Router', 'otra\\Router');


/**
 * Utility class to minify HTML
 */
class HtmlMinifier
{
  private const
    INLINE_TAGS =
    [
      'a', 'abbr', 'acronym', 'audio', 'b', 'bdo', 'br', 'button', 'canvas', 'cite', 'dfn', 'em', 'font', 'i',
      'img', 'input', 'kbd', 'label', 'q', 'samp', 'select', 'small', 'span', 'strong', 'sub', 'sup',
      'textarea', 'time', 'var', 'video'
    ],
    PRESERVED_TAGS = ['code', 'pre', 'script', 'svg', 'textarea'],
    STATE_CLOSING_TAG = 'CLOSING_TAG',
    STATE_IN_HTML_ATTRIBUTE_VALUE = 'IN HTML ATTRIBUTE VALUE',
    STATE_INSIDE_CDATA = 'INSIDE_CDATA',
    STATE_INSIDE_COMMENT = 'INSIDE_COMMENT',
    STATE_INSIDE_DOCTYPE = 'INSIDE_DOCTYPE',
    STATE_INSIDE_PRESERVED_TAG_CONTENT = 'INSIDE_PRESERVED_TAG_CONTENT',
    STATE_OUTSIDE_OR_INSIDE_TAG_CONTENT = 'OUTSIDE_OR_INSIDE_TAG_CONTENT',
    STATE_INSIDE_TAG_TAG_NAME = 'INSIDE TAG - TAG NAME',
    STATE_INSIDE_TAG_TAG_NAME_NOT_FOUND = 'INSIDE TAG - TAG NAME NOT FOUND',
    STATE_INSIDE_TAG_SEARCHING_ATTRIBUTES = 'INSIDE TAG - SEARCHING ATTRIBUTES',
    STATE_OUTSIDE_CLOSING_AUTO_CLOSED_TAG = 'OUTSIDE - CLOSING AUTO-CLOSED TAG',
    DEFAULT_CONFIGURATION =
    [
      'comments' => true,
      'spaces' => 'consecutiveSpaces'
    ];

  /** @var array<int,string> $actualTags  */
  private static array $actualTags = [];
  /** @var array{
   * comments: bool,
   * spaces: 'allSpaces'|'consecutiveSpaces'|'none'
   * } $configuration
   */
  private static array $configuration;
  /** @var array<int,string> $minifiedHtml  */
  private static array $minifiedHtml = [];
  private static string
    $actualTag = '',
    $attributeValueBuffer = '',
    $character = '',
    $htmlCode = '',
    $quote = '',
    $state = self::STATE_OUTSIDE_OR_INSIDE_TAG_CONTENT,
    $tagType = 'block',
    $actualMarkupContent = '',
    $lastState = '';

  private static int $index;

  private static function updateInformations(string $state, string $tag = ''): void
  {
    self::$lastState = self::$state;
    self::$state = $state;
    self::$actualTag = $tag;
    self::$actualMarkupContent.= self::$character;
  }

  private static function setTagType(): string
  {
    if (in_array(self::$actualTag, self::PRESERVED_TAGS))
      return 'pre';
    else
      return in_array(self::$actualTag, self::INLINE_TAGS) ? 'inline' : 'block';
  }

  private static function getPreviousActualTag(?int $lastKey): string
  {
    return $lastKey !== null ? self::$actualTags[$lastKey] : '';
  }

  private static function getLastTagStartBracketPosition(): int
  {
    return (int) strrpos(substr(self::$htmlCode, 0, self::$index), '<');
  }

  private static function exitingTag(): void
  {
    $lastTagStartBracketPosition = self::getLastTagStartBracketPosition();

    if (// if we find a self-closing tag (e.g., `/>`)
      self::$htmlCode[self::$index - 1] === '/'
      // or end of tag (e.g., `</`)
      || str_contains(substr(self::$htmlCode, $lastTagStartBracketPosition, self::$index - $lastTagStartBracketPosition), '/')
    )
    {
      // We move out from a tag, and we set the parent tag as the new actual tag
      // We fix the tag type accordingly
      array_pop(self::$actualTags);

      self::$actualTag = self::getPreviousActualTag(array_key_last(self::$actualTags));
      self::$state = self::STATE_CLOSING_TAG;
    } else
      self::$actualTags[] = self::$actualTag;

    self::$tagType = self::setTagType();
  }

  /**
   * It must minify the HTML by removing whitespaces (spaces, tabulations etc.) and quotes while preserving spaces in
   * pre and code markups, between block type markups and attributes' and preserving attributes values' quotes when we
   * cannot remove them.
   * The HTML code must be valid as the minification function does not fix bugs into the code.
   *
   * @param string $htmlCode
   * @param array{
   *   comments?: bool,
   *   spaces?: 'allSpaces'|'consecutiveSpaces'|'none'
   * }             $configuration $configuration Which tags to minify and how
   *
   * @return string
   */
  public static function minifyHTML(string $htmlCode, array $configuration = []) : string
  {
    // reinitializing variables to prevent side effects on multiple uses
    self::$minifiedHtml = self::$actualTags =  [];
    self::$actualTag =
      self::$attributeValueBuffer =
      self::$character =
      self::$quote = '';

    self::$tagType = 'block';
    self::$state = self::STATE_OUTSIDE_OR_INSIDE_TAG_CONTENT;

    self::$configuration = [...self::DEFAULT_CONFIGURATION, ...$configuration];
    self::$htmlCode = $htmlCode;
    $cDataState = false;

    for (self::$index = 0, $htmlLength = strlen(self::$htmlCode);
         self::$index < $htmlLength;
         self::$index++)
    {
      self::$character = self::$htmlCode[self::$index];

      switch(self::$state)
      {
        case self::STATE_INSIDE_TAG_TAG_NAME_NOT_FOUND:
          self::$actualTag = '';

          if (!ctype_space(self::$character))
          {
            self::$lastState = self::$state;
            self::$state = self::STATE_INSIDE_TAG_TAG_NAME;
            self::$actualMarkupContent.= self::$character;

            // not a whitespace, `/` or `>`
            if (!in_array(self::$character, ['/', '>']))
              self::$actualTag .= self::$character;
          }
          break;

        case self::STATE_INSIDE_TAG_TAG_NAME:
          if (!ctype_space(self::$character))
          {
            // we do not add '/' in auto-closing tags if it is not XML (SVG files for example)
            if (self::$character === '/')
            {
              self::$lastState = self::$state;
              self::$state = self::STATE_CLOSING_TAG;
              break;
            }

            if (self::$character === '>')
            {
                self::$minifiedHtml[]= self::$actualMarkupContent . self::$character;
                self::$actualMarkupContent = '';
              self::$lastState = self::$state;
              self::$state = in_array(self::$actualTag, self::PRESERVED_TAGS)
                ? self::STATE_INSIDE_PRESERVED_TAG_CONTENT
                : self::STATE_OUTSIDE_OR_INSIDE_TAG_CONTENT;
              self::exitingTag();
            } elseif(self::$character !== '/')
            {
              self::$actualMarkupContent.= self::$character;
              self::$actualTag .= self::$character;
            }

            break;
          }

          // Now we know that it's a white space
          if (isset(self::$htmlCode[self::$index + 1]))
          {
            $nextCharacter = self::$htmlCode[self::$index + 1];

            // We add a space only if we have attributes to add to the tag
            if (!ctype_space($nextCharacter)
              && $nextCharacter !== '/'
              && $nextCharacter !== '>'
              && self::$character !== '/'
              && self::$character !== '>'
            )
              self::$actualMarkupContent .= ' ';
          }

          self::$lastState = self::$state;
          self::$state = self::STATE_INSIDE_TAG_SEARCHING_ATTRIBUTES;
          self::$tagType = self::setTagType();
          break;

        case self::STATE_INSIDE_TAG_SEARCHING_ATTRIBUTES:
          // If the actual character is a whitespace
          if (ctype_space(self::$character))
          {
            if (isset(self::$htmlCode[self::$index + 1]))
            {
              $nextCharacter = self::$htmlCode[self::$index + 1];
              // if neither '  ', nor ' /', nor ' >', nor ' =', nor '= ', nor ' ' . EOF
              // then we add a space otherwise we do not add anything
              if (!ctype_space($nextCharacter)
                && $nextCharacter !== '/'
                && $nextCharacter !== '>'
                && $nextCharacter !== '='
                && $nextCharacter !== '"'
                && $nextCharacter !== "'"
              )
                self::$actualMarkupContent .= ' ';
            } elseif (self::$htmlCode[self::$index - 1] !== '=')
              self::$actualMarkupContent .= ' ';
          } elseif (self::$character === '/' && self::$htmlCode[self::$index + 1] === '>')
          {
            self::$lastState = self::$state;
            self::$state = self::STATE_OUTSIDE_CLOSING_AUTO_CLOSED_TAG;
            self::$actualTag = '';
          }
          elseif(self::$character === '>')
          {
            self::$tagType = self::setTagType();
            self::updateInformations(
              self::$tagType === 'pre'
                ? self::STATE_INSIDE_PRESERVED_TAG_CONTENT
                : self::STATE_OUTSIDE_OR_INSIDE_TAG_CONTENT,
              self::$actualTag
            );
          } elseif (self::$character === '"' || self::$character === "'")
          {
            self::$quote = self::$character;
            self::$lastState = self::$state;
            self::$state = self::STATE_IN_HTML_ATTRIBUTE_VALUE;
            self::$attributeValueBuffer .= self::$quote;
          } else
            self::$actualMarkupContent .= self::$character;

          break;

        case self::STATE_OUTSIDE_OR_INSIDE_TAG_CONTENT:
          if (ctype_space(self::$character))
          {
            if (self::$configuration['spaces'] === 'none')
              self::$actualMarkupContent.= self::$character;
            else
            {
              if (isset(self::$htmlCode[self::$index + 1]) && ctype_space(self::$htmlCode[self::$index + 1]))
                break;

              $lastMinifiedHtmlChunk = array_key_last(self::$minifiedHtml);

              if (self::$configuration['spaces'] === 'allSpaces'
                && ((self::$actualMarkupContent === ''
                    && $lastMinifiedHtmlChunk !== null
                    && str_ends_with(self::$minifiedHtml[$lastMinifiedHtmlChunk], '>'))
                  || (isset(self::$htmlCode[self::$index + 1]) && self::$htmlCode[self::$index + 1] === '<'))
              )  // 'allSpaces'
                break;

              self::$actualMarkupContent .= ' ';
            }
          } elseif (self::$character === '<') // The actual character is not a whitespace
          {
            $position = self::$index + 1;

            // If the next character is a space or the end of the string, it's not the start of a tag.
            if ($position >= $htmlLength || ctype_space(self::$htmlCode[$position]))
              $isValidTagStart = false;
            else
              // Check if the next character is valid for the start of a tag (letter, digit, -, _).
              $isValidTagStart = ctype_alpha(self::$htmlCode[$position])
                || self::$htmlCode[$position] === '-'
                || self::$htmlCode[$position] === '_'
                || self::$htmlCode[$position] === '/'
                || self::$htmlCode[$position] === '!';

            if ($isValidTagStart)
            {
              if (self::$actualMarkupContent !== '')
              {
                self::$minifiedHtml[] = self::$actualMarkupContent;
                self::$actualMarkupContent = '';
              }

              // searching closing tag
              self::$lastState = self::$state;

              if (self::$htmlCode[self::$index + 1] === '/')
              {
                self::$state = self::STATE_CLOSING_TAG;
                self::$actualMarkupContent = '<';
              } elseif (self::$htmlCode[self::$index + 1] !== '!') // it is not a comment
              {
                self::$state = self::STATE_INSIDE_TAG_TAG_NAME_NOT_FOUND;
                self::$actualMarkupContent = '<';
              } elseif (self::$htmlCode[self::$index + 2] === '-' && self::$htmlCode[self::$index + 3] === '-')
              {
                // We are at the beginning of an HTML comment.
                // Check the next two characters to see if they are '--'.
                // If they are, this is the start of a comment.
                self::$state = self::STATE_INSIDE_COMMENT;

                if (!self::$configuration['comments'])
                  self::$actualMarkupContent .= '<';
              }
              // If the next two characters are not '--', this is not a comment.
              // It could be a DOCTYPE declaration or a CDATA section.
              // We need to decide how we want to handle these cases.
              elseif (strtolower(substr(self::$htmlCode, self::$index + 2, 3)) === 'doc')
              {
                // We are at the beginning of a DOCTYPE declaration.
                self::$state = self::STATE_INSIDE_DOCTYPE;
                self::$actualMarkupContent .= self::$character;
              } else
              {
                // We are at the beginning of a CDATA section.
                self::$state = self::STATE_INSIDE_CDATA;
                self::$actualMarkupContent .= self::$character;
              }
            } else
              self::$actualMarkupContent.= self::$character;
          } elseif (self::$character === '>')
          {
            $position = self::$index - 1;
            $isEndOfValidTag = false;

            // Ignore white spaces
            while ($position >= 0)
            {
              $character = self::$htmlCode[$position];

              if ($character === '<')
              {
                $isEndOfValidTag = true;
                break;
              } elseif ($character === '>')
                break;

              --$position;
            }

            if ($isEndOfValidTag)
            {
              self::exitingTag();
              self::$minifiedHtml[] = '>';
            } else
              self::$actualMarkupContent.= self::$character;
          } else
            self::$actualMarkupContent.= self::$character;
          break;

        case self::STATE_OUTSIDE_CLOSING_AUTO_CLOSED_TAG:
          self::$minifiedHtml[]= self::$actualMarkupContent . '>';
          self::$actualMarkupContent = '';
          self::$lastState = self::$state;
          self::$state = self::STATE_OUTSIDE_OR_INSIDE_TAG_CONTENT;
          break;

        case self::STATE_IN_HTML_ATTRIBUTE_VALUE:
         if (self::$character === self::$quote)
          {
            self::$lastState = self::$state;
            self::$state = self::STATE_INSIDE_TAG_SEARCHING_ATTRIBUTES;

            if (self::$attributeValueBuffer === self::$character)
            {
              // removes the first quote AND the equal sign as the value is empty
              self::$actualMarkupContent = substr(self::$actualMarkupContent, 0,-1);
            } elseif (strpbrk(self::$attributeValueBuffer, ' =<>') !== false)
              // If there is one of the forbidden special characters in the attribute value, we need the quotes.
              // Otherwise, skips them.
              self::$actualMarkupContent .= self::$attributeValueBuffer . self::$character;
            else
            {
              self::$actualMarkupContent .= substr(
                self::$attributeValueBuffer,
                1,
                strlen(self::$attributeValueBuffer) - 1
              );
            }

            self::$attributeValueBuffer = '';
          } else
            self::$attributeValueBuffer .= self::$character;

          break;

        case self::STATE_INSIDE_CDATA:
          // If we encounter the end of the CDATA section (']]>'), we return to the last state
          if (self::$character === ']'
            && isset(self::$htmlCode[self::$index + 2])
            && substr(self::$htmlCode, self::$index + 1, 2) === ']>')
          {
            $cDataState = true;
          } elseif (!$cDataState)
          {
            self::$actualMarkupContent .= self::$character;
          } elseif(self::$character === '>')
          {
            $tempState = self::$state;
            self::$state = self::$lastState;
            self::$lastState = $tempState;
            array_pop(self::$actualTags);
            self::$actualTag = self::getPreviousActualTag(array_key_last(self::$actualTags));
            self::$minifiedHtml[]= self::$actualMarkupContent . ']]>';
            self::$actualMarkupContent = '';
            $cDataState = false;
          }

          break;

        case self::STATE_INSIDE_COMMENT:
          if (!self::$configuration['comments'])
            self::$actualMarkupContent .= self::$character;

          if (self::$character === '>'
            && $htmlCode[self::$index - 1] === '-'
            && $htmlCode[self::$index - 2] === '-')
          {
            self::$state = self::$lastState;
            self::$lastState = self::STATE_INSIDE_COMMENT;

            // if we have a white space before and after the comment, remove the last one
            $lastMinifiedHtmlKey = array_key_last(self::$minifiedHtml);

            if (isset($lastMinifiedHtmlKey)
              && ctype_space(substr(self::$minifiedHtml[$lastMinifiedHtmlKey], -1))
              && ctype_space(self::$htmlCode[self::$index + 1])
            )
              self::$minifiedHtml[$lastMinifiedHtmlKey] = substr(
                self::$minifiedHtml[$lastMinifiedHtmlKey],
                0,
                -1
              );
          }

          break;

        case self::STATE_INSIDE_DOCTYPE:
          // If we encounter the end of the DOCTYPE `>`, we return to the OUTSIDE state
          if (self::$character === '>')
          {
            self::$lastState = self::$state;
            self::$state = self::STATE_OUTSIDE_OR_INSIDE_TAG_CONTENT;
            self::$actualTag = '';
            self::$minifiedHtml[]= self::$actualMarkupContent . self::$character;
            self::$actualMarkupContent = '';
          } elseif (!ctype_space(self::$character) ||
            (ctype_space(self::$character)
              && self::$htmlCode[self::$index + 1] !== '>'
              && !ctype_space(self::$htmlCode[self::$index + 1])))
            // If the character is not a whitespace, or if it is a whitespace not followed by another whitespace
            // or the end of the DOCTYPE `>` we append it to the minified HTML.
            // This will remove unnecessary whitespaces inside the DOCTYPE.
            self::$actualMarkupContent .= self::$character;
          break;

        case self::STATE_INSIDE_PRESERVED_TAG_CONTENT:
          if (self::$character === '<' && self::$htmlCode[self::$index + 1] === '/')
          {
            self::$actualMarkupContent .= '</';
            self::$index += 2;
            self::$character = $htmlCode[self::$index];

            while (ctype_space(self::$character))
            {
              self::$actualMarkupContent .= self::$character;
              ++self::$index;
              self::$character = $htmlCode[self::$index];
            }

            if (substr(self::$htmlCode, self::$index, strlen(self::$actualTag)) === self::$actualTag)
            {
              if (self::$actualMarkupContent !== '')
              {
                self::$minifiedHtml[] = self::$actualMarkupContent;
                self::$actualMarkupContent = '';
              }

              // We move out from a tag, and we set the parent tag as the new actual tag
              // We fix the tag type accordingly
              array_pop(self::$actualTags);

              self::$actualTag = self::getPreviousActualTag(array_key_last(self::$actualTags));
              self::$tagType = self::setTagType();
              self::$lastState = self::$state;
              self::$state = self::STATE_CLOSING_TAG;
            }
          }

          self::$actualMarkupContent .= self::$character;

          if (self::$character === '>' && self::$lastState === self::STATE_CLOSING_TAG)
          {
            self::$minifiedHtml[] = self::$actualMarkupContent;
            self::$actualMarkupContent = '';
            self::$lastState = self::$state;
            self::$state = self::STATE_OUTSIDE_OR_INSIDE_TAG_CONTENT;
          }

          break;

        case self::STATE_CLOSING_TAG:
          if (self::$character === '>')
          {
            self::$lastState = self::$state;
            self::$state = self::STATE_OUTSIDE_OR_INSIDE_TAG_CONTENT;
            self::$minifiedHtml[]= self::$actualMarkupContent . '>';
            self::$actualMarkupContent = '';
          } else
            self::$actualMarkupContent .= self::$character;

          break;

        default:
          break;
      }
    }

    if (self::$actualMarkupContent !== '')
      self::$minifiedHtml[]= self::$actualMarkupContent;

    return implode('', self::$minifiedHtml);
  }
}
function isSerialized(mixed $dataToCheck) : bool
{
  if (!is_string($dataToCheck))
    return false;

  $dataToCheck = trim($dataToCheck);

  if ('N;' === $dataToCheck)
    return true;

  if (strlen($dataToCheck) < 4
    || ':' !== $dataToCheck[1]
    || (';' !== $dataToCheck[-1] && '}' !== $dataToCheck[-1])
  )
    return false;

  $token = $dataToCheck[0];

  if ($token === 's' && '"' !== $dataToCheck[-2])
    return false;

  if (in_array($token, ['s', 'a', 'O']))
    return (bool)preg_match('/^' . $token . ':[0-9]+:/s', $dataToCheck);

  if (in_array($token, ['b', 'i', 'd']))
    return (bool) preg_match( "/^' . $token . ':[0-9.E+-]+;$/", $dataToCheck );

  return false;
}
/** Description of Session
 *
 * @author Lionel Péramo */

/**
 * @package otra
 */
abstract class Session
{
  public static string $sessionsCachePath = CACHE_PATH . 'php/sessions/';
  private static bool $initialized = false;
  private static ?string
    $identifier,
    $blowfishAlgorithm,
    $sessionId,
    $sessionFile;
  private static array $matches = [];
  final public const
    SESSION_KEY_EXISTS = 0,
    SESSION_KEY_VALUE = 1;

  /**
   * @param int $rounds Number of rounds for the blowfish algorithm that protects the session
   *
   * @throws OtraException
   * @throws ReflectionException
   */
  public static function init(int $rounds = 7) : void
  {
    if ($rounds < 4 || $rounds > 31)
      throw new OtraException('Rounds must be in the range 4-31');

    self::$sessionId = session_id();
    self::$sessionFile = self::$sessionsCachePath . sha1('ca' . self::$sessionId . VERSION . 'che') . '.php';

    // blowfish algorithm must put leading zeros if the rounds are less than 10
    if ($rounds < 10)
      $rounds = '0' . $rounds;

    self::$blowfishAlgorithm = '$2y$' . $rounds . '$';

    if (!file_exists(self::$sessionFile))
    {
      if (!touch(self::$sessionFile))
        throw new OtraException('Cannot create the session file.');

      // will produce a string of 11 * 2 = 22 characters which is the minimum (?) for the
      // Blowfish algorithm of the kind $2y$
      self::$identifier = bin2hex(openssl_random_pseudo_bytes(11));
      self::toFile();
    } else
    {
      $sessionData = require self::$sessionFile;
      self::$identifier = $sessionData['otra_i'];
      

      if (!self::$initialized)
      {
        self::$matches = [];

        foreach ($sessionData as $sessionDatumKey => $sessionDatum)
        {
          // only objects are serialized in session files, that's why we deserialize then enforce serialization
          if (!str_starts_with($sessionDatumKey, 'otra_'))
            self::set($sessionDatumKey, isSerialized($sessionDatum) ? unserialize($sessionDatum) : $sessionDatum);
        }
      }
    }

    self::$initialized = true;
  }

  public static function set(string $sessionKey, mixed $value) : void
  {
    self::$matches[$sessionKey] = [
      'hashed' => crypt(serialize($value), self::$blowfishAlgorithm . self::$identifier),
      'notHashed' => $value
    ];

    $_SESSION[$sessionKey] = self::$matches[$sessionKey]['hashed'];
  }

  /**
   * Puts all the value associated with the keys of the array into the session
   */
  public static function sets(array $array) : void
  {
    foreach($array as $sessionKey => $value)
    {
      self::$matches[$sessionKey] = [
        'hashed' => crypt(serialize($value), self::$blowfishAlgorithm . self::$identifier),
        'notHashed' => $value
      ];

      $_SESSION[$sessionKey] = self::$matches[$sessionKey]['hashed'];
    }
  }

  /**
   * Returns false if it does not exist in the cache...should not occur.
   *
   *
   * @return mixed
   */
  public static function get(string $sessionKey) : mixed
  {
    return self::$matches[$sessionKey]['notHashed'];
  }

  /**
   * @return array[bool, mixed] [doesItExist, value]
   */
  public static function getIfExists(string $sessionKey) : array
  {
    // We test self::$matches in case the OTRA session file has been remove or altered in any way
    return isset($_SESSION[$sessionKey], self::$matches[$sessionKey])
      ? [true, self::$matches[$sessionKey]['notHashed']]
      : [false, null];
  }

  /**
   * If the first key exists, get it and the other keys. Otherwise, returns false.
   *
   *
   * @throws OtraException
   * @return array[bool, array|bool] [doesItExist, value]
   */
  public static function getArrayIfExists(array $sessionKeys) : array
  {
    if (!isset(self::$identifier))
      throw new OtraException('You must initialize OTRA session before using "getArrayIfExists"');

    $firstKey = $sessionKeys[0];

    // We test self::$matches in case the OTRA session file has been remove or altered in any way
    if (!(isset($_SESSION[$firstKey], self::$matches[$firstKey])))
      return [false, null];

    $result = [
      $firstKey => self::$matches[$firstKey]['notHashed']
    ];
    array_shift($sessionKeys);

    foreach($sessionKeys as $sessionKey)
    {
      $result[$sessionKey] = self::$matches[$sessionKey]['notHashed'];
    }

    return [true, $result];
  }

  public static function getAll(): array
  {
    $result = [];

    foreach(self::$matches as $sessionKey => $sessionValueInformation)
    {
      $result[$sessionKey] = $sessionValueInformation['notHashed'];
    }

    return $result;
  }

  /**
   * Cleans OTRA session (but keeps PHP sessions keys that are not related to OTRA)
   *
   * @throws OtraException
   */
  public static function clean(): void
  {
    if (!isset(self::$matches))
      throw new OtraException('You cannot clean an OTRA session that is not initialized.');

    if (file_exists(self::$sessionFile) && !unlink(self::$sessionFile))
      throw new OtraException('Cannot remove session file!');

    self::$identifier = self::$blowfishAlgorithm = self::$sessionId = self::$sessionFile = null;
    self::$initialized = false;

    foreach (array_keys(self::$matches) as $sessionKey)
      unset($_SESSION[$sessionKey], self::$matches[$sessionKey]);
  }

  /**
   * @throws OtraException|ReflectionException
   * @return void
   */
  public static function toFile(): void
  {
    // Get in memory session data
    $actualSessionData = [];

    foreach(self::$matches as $sessionKey => $sessionValueInformation)
    {
      $actualSessionData[$sessionKey] = $sessionValueInformation['notHashed'];
    }

    $actualSessionData['otra_i'] = self::$identifier;
    $actualSessionData['otra_b'] = self::$blowfishAlgorithm;

    /** @author Lionel Péramo @package otra\console */
    

    $dataInformation = convertLongArrayToShort($actualSessionData);
    $fileContent = '<?php ';

    // Updates the file with the merged version
    if (
      file_put_contents(
        self::$sessionFile,
        $fileContent . 'return ' . $dataInformation . ';' . PHP_EOL
      ) === false
    )
      throw new OtraException(
        ($_SERVER[APP_ENV] === PROD)
          ? 'Problem on server side'
          : 'Cannot write the file ' . self::$sessionFile
      );

    // Those two lines, especially the call to opcache_invalidate, are there to prevent from using an old version of the
    // session when we initialize the session from this file. Do - not - touch - it.
    clearstatcache(true, self::$sessionFile);
    opcache_invalidate(self::$sessionFile);

    if ($_SERVER['REMOTE_ADDR'] === '::1')
      chmod(self::$sessionFile, 0775);
  }

  public static function getNativeSessionData(): array
  {
    $result = [];

    foreach($_SESSION as $sessionKey => $sessionValue)
    {
      if (!in_array($sessionKey, array_values(self::$matches)))
        $result[$sessionKey] = $sessionValue;
    }

    return $result;
  }
}
abstract class MasterController
{
  public static array $nonces = [
    'script-src' => [],
    'style-src' => []
  ];

  public static string
    $cacheUsed = 'Unused',
    $path;

  public ?string $routeSecurityFilePath = null;
  public string
    $bundle = '',
    $module = '',
    $route,
    $viewPath = DIR_SEPARATOR; protected string
    $action = '',
    $controller = '',
    $pattern = '', $response;

  protected array
    $params = [],
    $viewResourcePath = [
      'css' => DIR_SEPARATOR, 'js' => DIR_SEPARATOR  ];

  protected static array
    $javaScripts = [],
    /** @var array<string,string> */$rendered = [],
    $stylesheets = [];

  protected static bool
    $ajax = false,
    $hasCssToLoad,
    $hasJsToLoad;

  protected static string $layout;

  protected static bool|string|null $template;

  private static int
    $stylesheetFile = 0,
    $printStylesheet = 1;

  final public const
    INLINE_TAGS =
    [
      'a', 'abbr', 'acronym', 'audio', 'b', 'bdo', 'br', 'button', 'canvas', 'cite', 'code', 'dfn', 'em', 'font', 'head', 'i', 'img',
      'input', 'kbd', 'label', 'q', 'samp', 'script', 'select', 'small', 'span', 'strong', 'sub', 'sup', 'textarea',
      'time', 'title', 'var', 'video'
    ],
    OTRA_LABEL_ENDING_TITLE_TAG = '/title>',
    HTTP_CODES =
    [
      'HTTP_CONTINUE' => 100,
      'HTTP_SWITCHING_PROTOCOLS' => 101,
      'HTTP_PROCESSING' => 102,            'HTTP_OK' => 200,
      'HTTP_CREATED' => 201,
      'HTTP_ACCEPTED' => 202,
      'HTTP_NON_AUTHORITATIVE_INFORMATION' => 203,
      'HTTP_NO_CONTENT' => 204,
      'HTTP_RESET_CONTENT' => 205,
      'HTTP_PARTIAL_CONTENT' => 206,
      'HTTP_MULTI_STATUS' => 207,          'HTTP_ALREADY_REPORTED' => 208,      'HTTP_IM_USED' => 226,               'HTTP_MULTIPLE_CHOICES' => 300,
      'HTTP_MOVED_PERMANENTLY' => 301,
      'HTTP_FOUND' => 302,
      'HTTP_SEE_OTHER' => 303,
      'HTTP_NOT_MODIFIED' => 304,
      'HTTP_USE_PROXY' => 305,
      'HTTP_RESERVED' => 306,
      'HTTP_TEMPORARY_REDIRECT' => 307,
      'HTTP_PERMANENTLY_REDIRECT' => 308,  'HTTP_BAD_REQUEST' => 400,
      'HTTP_UNAUTHORIZED' => 401,
      'HTTP_PAYMENT_REQUIRED' => 402,
      'HTTP_FORBIDDEN' => 403,
      'HTTP_NOT_FOUND' => 404,
      'HTTP_METHOD_NOT_ALLOWED' => 405,
      'HTTP_NOT_ACCEPTABLE' => 406,
      'HTTP_PROXY_AUTHENTICATION_REQUIRED' => 407,
      'HTTP_REQUEST_TIMEOUT' => 408,
      'HTTP_CONFLICT' => 409,
      'HTTP_GONE' => 410,
      'HTTP_LENGTH_REQUIRED' => 411,
      'HTTP_PRECONDITION_FAILED' => 412,
      'HTTP_REQUEST_ENTITY_TOO_LARGE' => 413,
      'HTTP_REQUEST_URI_TOO_LONG' => 414,
      'HTTP_UNSUPPORTED_MEDIA_TYPE' => 415,
      'HTTP_REQUESTED_RANGE_NOT_SATISFIABLE' => 416,
      'HTTP_EXPECTATION_FAILED' => 417,
      'HTTP_I_AM_A_TEAPOT' => 418,                                               'HTTP_UNPROCESSABLE_ENTITY' => 422,                                        'HTTP_LOCKED' => 423,                                                      'HTTP_FAILED_DEPENDENCY' => 424,                                           'HTTP_RESERVED_FOR_WEBDAV_ADVANCED_COLLECTIONS_EXPIRED_PROPOSAL' => 425,   'HTTP_UPGRADE_REQUIRED' => 426,                                            'HTTP_PRECONDITION_REQUIRED' => 428,                                       'HTTP_TOO_MANY_REQUESTS' => 429,                                           'HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE' => 431,                             'HTTP_UNAVAILABLE_FOR_LEGAL_REASONS' => 451,
      'HTTP_INTERNAL_SERVER_ERROR' => 500,
      'HTTP_NOT_IMPLEMENTED' => 501,
      'HTTP_BAD_GATEWAY' => 502,
      'HTTP_SERVICE_UNAVAILABLE' => 503,
      'HTTP_GATEWAY_TIMEOUT' => 504,
      'HTTP_VERSION_NOT_SUPPORTED' => 505,
      'HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL' => 506,                        'HTTP_INSUFFICIENT_STORAGE' => 507,                                        'HTTP_LOOP_DETECTED' => 508,                                               'HTTP_NOT_EXTENDED' => 510,                                                'HTTP_NETWORK_AUTHENTICATION_REQUIRED' => 511                              ];

  protected const
    CSS_MEDIA_SCREEN = 0,
    LABEL_SCRIPT_NONCE = '<script nonce=';

  /**
   * @param array{
   *  pattern?: string,
   *  bundle?: string,
   *  module?: string,
   *  controller?: string,
   *  action?: string,
   *  route?: string,
   *  js: bool,
   *  css: bool
   * } $otraParams
   *
   * @param array $params The params passed by GET method
   */public function __construct(array $otraParams = [], array $params = [])
  {
    if (!isset($otraParams['controller']))
    {
      if (isset($otraParams['route']) && $otraParams['route'] === 'otra_exception')
      {
        [
          $this->bundle,
          $this->module,
          $this->route,
          self::$hasCssToLoad,
          self::$hasJsToLoad
        ] = array_values($otraParams);

        /**
 * @author  Lionel Péramo
 * @package otra\services
 */ 

if (!function_exists(__NAMESPACE__ . '\\getRandomNonceForCSP'))
{
  if ($_SERVER[APP_ENV] === PROD && PHP_SAPI !== 'cli' && !defined('otra\\OTRA_STATIC'))
    class_alias('otra\cache\php\AllConfig', 'AllConfig');

  /**
   *
   * @throws Exception
   */function getRandomNonceForCSP(?string $directive = OTRA_KEY_SCRIPT_SRC_DIRECTIVE) : string
  {
    $nonce = bin2hex(random_bytes(32));
    MasterController::$nonces[$directive][] = $nonce;

    return $nonce;
  }

  /**
   * @param string $route
   * @param ?string $routeSecurityFilePath
   *
   * @return array{bool, array<string, array<string, string>>}
   */function getRoutePolicies(string $route, ?string $routeSecurityFilePath) : array
  {
    if (str_contains($route, OTRA_ROUTES_PREFIX) || $routeSecurityFilePath === null)
      return [
        true,
        [
          OTRA_KEY_CONTENT_SECURITY_POLICY => [],
          OTRA_KEY_PERMISSIONS_POLICY => []
        ]
      ]; /** @var array<string,array<string,string>> $policiesFromUserConfig */$policiesFromUserConfig = require $routeSecurityFilePath;

    if (!isset($policiesFromUserConfig[OTRA_KEY_CONTENT_SECURITY_POLICY]))
      $policiesFromUserConfig[OTRA_KEY_CONTENT_SECURITY_POLICY] = [];

    if (!isset($policiesFromUserConfig[OTRA_KEY_PERMISSIONS_POLICY]))
      $policiesFromUserConfig[OTRA_KEY_PERMISSIONS_POLICY] = [];

    return [false, $policiesFromUserConfig];
  }

  /**
   * Generates the security policy that will be added to the HTTP header.
   * We do not keep script-src and style-src directives that will be handled in handleStrictDynamic function.
   *
   * @param string                             $policyType              Can be 'csp' or 'permissionsPolicy'
   * @param bool                               $isOtraRoute             Is the route prefixed by OTRA_ROUTES_PREFIX?
   * @param array<string,array<string,string>> $customPolicyDirectives
   * @param array<string, string>              $defaultPolicyDirectives The default policy directives (csp or permissions policy)
   *                                                                    from MasterController
   *
   * @return array{0: string, 1: array<string, string>}|string The string is the policy that will be included in
   * the HTTP headers, the array is the array of policies needed to handle the `strict-dynamic` rules for the CSP
   * policies
   */function createPolicy(
    string $policyType,
    bool $isOtraRoute,
    array $customPolicyDirectives,
    array $defaultPolicyDirectives
  ) : array|string
  {
    $finalProcessedPolicies = $defaultPolicyDirectives;

    if (!$isOtraRoute)
    {
      if (empty($finalProcessedPolicies))
        $finalProcessedPolicies = $customPolicyDirectives;
      else
      {
        $common = array_intersect($finalProcessedPolicies, $customPolicyDirectives);
        $finalProcessedPolicies = [...$finalProcessedPolicies, ...$customPolicyDirectives];

        if (!empty($common))
        {
          foreach ($finalProcessedPolicies as $finalProcessedPolicyName => $finalProcessedPolicy)
          {
            if ($finalProcessedPolicy === '')
              unset($finalProcessedPolicies[$finalProcessedPolicyName]);
          }
        }
      }
    }

    $finalPolicy = OTRA_POLICIES[$policyType];
    $policySeparator = ($policyType === OTRA_KEY_CONTENT_SECURITY_POLICY) ? ' ' : '=(';

    foreach ($finalProcessedPolicies as $directive => $value)
    {
      if ($directive === OTRA_KEY_SCRIPT_SRC_DIRECTIVE)
        continue;

      $finalPolicy .= $directive . $policySeparator . $value . ($policyType === OTRA_KEY_PERMISSIONS_POLICY ? '),' : '; ');
    }

    if ($policyType === OTRA_KEY_PERMISSIONS_POLICY)
    {
      return substr(
        str_replace(
          [
            OTRA_LABEL_SECURITY_SELF,
            '(self)'
          ],
          [
            'self',
            'self'
          ],
          $finalPolicy
        ),
        0,
        -1
      );
    }

    return [$finalPolicy, $finalProcessedPolicies];
  }

  function addCspHeader(string $route, ?string $routeSecurityFilePath): void
  {
    if (!headers_sent())
    {
      [$isOtraRoute, $customPolicyDirectives] = getRoutePolicies($route, $routeSecurityFilePath);
      [$policy, $cspDirectives] = createPolicy(
        OTRA_KEY_CONTENT_SECURITY_POLICY,
        $isOtraRoute,
        $customPolicyDirectives[OTRA_KEY_CONTENT_SECURITY_POLICY],
        CONTENT_SECURITY_POLICY[$_SERVER[APP_ENV]]
      );
      handleStrictDynamic(OTRA_KEY_SCRIPT_SRC_DIRECTIVE, $policy, $cspDirectives);
      addNonces($policy);
      header($policy);
    }
  }

  function addPermissionsPoliciesHeader(string $route, ?string $routeSecurityFilePath) : void
  {
    if (!headers_sent())
    {
      [$isOtraRoute, $customPolicyDirectives] = getRoutePolicies($route, $routeSecurityFilePath);
      header(createPolicy(
        OTRA_KEY_PERMISSIONS_POLICY,
        $isOtraRoute,
        $customPolicyDirectives[OTRA_KEY_PERMISSIONS_POLICY],
        PERMISSIONS_POLICY[$_SERVER[APP_ENV]]
      ));
    }
  }

  function addNonces(string &$policy) : void
  {
    $directives = [OTRA_KEY_SCRIPT_SRC_DIRECTIVE, OTRA_KEY_STYLE_SRC_DIRECTIVE];

    foreach ($directives as $directive)
    {
      if (!empty(MasterController::$nonces[$directive]))
      {
        $nonceStr = '';

        foreach (MasterController::$nonces[$directive] as $nonce)
        {
          $nonceStr .= ' \'nonce-' . $nonce . '\'';
        }

        $policy = preg_replace(
          '/' .  $directive . '( |;)/',
          $directive . $nonceStr . '$1',
          $policy
        );
      }
    }
  }

  /**
   * Handles strict dynamic mode for CSP
   *
   * @param string  $directive     'strict-src' or 'style-src'
   * @param string  $policy
   * @param array   $cspDirectives Content Security Policy directives
   */function handleStrictDynamic(string $directive, string &$policy, array $cspDirectives) : void
  {
    if (!isset($cspDirectives[$directive]))
      $policy .= $directive . ' ' . CONTENT_SECURITY_POLICY[$_SERVER[APP_ENV]][$directive];
    elseif ($cspDirectives[$directive] !== '')
      $policy .= $directive . ' ' . $cspDirectives[$directive];
    else
      return;

    $policy .= ';';
  }
}
        $this->routeSecurityFilePath = CACHE_PATH . 'php/security/' . $this->route . '.php';
      }

      return;
    }

    [
      $this->pattern,
      $this->bundle,
      $this->module,
      $this->controller,
      ,
      $this->route,
      self::$hasJsToLoad,
      self::$hasCssToLoad
    ] = array_values($otraParams);

    require_once CORE_PATH . 'services/securityService.php';

    $this->routeSecurityFilePath = CACHE_PATH . 'php/security/' .  $_SERVER[APP_ENV] . DIR_SEPARATOR . $this->route .
      '.php';

    if (!file_exists($this->routeSecurityFilePath))
      $this->routeSecurityFilePath = null;

    $this->action = substr($otraParams['action'], 0, -6);
    $this->params = $params;
    $mainPath = 'bundles/' . $this->bundle . DIR_SEPARATOR . $this->module . DIR_SEPARATOR;
    $this->viewPath = BASE_PATH . $mainPath . 'views/' . str_replace('\\','/', $this->controller) .
      DIR_SEPARATOR;
    $this->viewResourcePath = [
      'css' => DIR_SEPARATOR . $mainPath . 'resources/css/',
      'js' => DIR_SEPARATOR . $mainPath . 'resources/js/'
    ];

    self::$path = $_SERVER['DOCUMENT_ROOT'] . '..';
  }

  /**
   * Encodes the value passed as parameter to create a cache file name
   *
   * @param string $path     File's path
   * @param string $suffix   Suffix of the file name
   *
   * @return string The cache file name version of the file
   */protected static function getCacheFileName(
    string $route,
    string $path = CACHE_PATH,
    string $suffix = VERSION,
    string $extension = '.cache'
  ) : string {
    return $path . sha1('ca' . $route . $suffix . 'che') . $extension;
  }

  /**
   * If the file is in the cache and is "fresh" then gets it.
   *
   * @param string $cachedFile The cache file name version of the file
   *
   * @throws Exception
   * @return bool|string $content The cached (and cleaned) content if exists, false otherwise
   */protected static function getCachedFileContent(string $cachedFile) : bool|string
  {
    return (!file_exists($cachedFile) || filemtime($cachedFile) + CACHE_TIME <= time())
      ? false
      : preg_replace(
        [
          '@(<script.*?nonce=")\w{64}@',
          '@(<link.*?nonce=")\w{64}@',
        ],
        [
          '${1}' . getRandomNonceForCSP(),
          '${1}' . getRandomNonceForCSP('style-src')
        ],
        file_get_contents($cachedFile)
      );
  }

  /**
   * Adds dynamically css script(s) (not coming from the routes' configuration) to the existing ones.
   *
   * @param array $stylesheets The css files to add [0 => File, 1 => Print]
   *                           Do not put '.css'.
   *                           /!\ DO NOT fill the second key if it is not needed
   * @param bool  $print       Does the stylesheet must be only used for a print usage ?
   */public static function css(array $stylesheets = [], bool $print = false) : void
  {
    array_push(
      self::$stylesheets,
      ...$stylesheets
    );
  }

  /**
   * Adds dynamically javascript script(s) (not coming from the routes' configuration) to the existing ones.
   * If the keys are string it will add the string to the link.
   *
   * @param array|string $js The javascript file to add (Array of strings)
   */public static function js(array|string $js = []) : void
  {
    self::$javaScripts = array_merge(self::$javaScripts, is_array($js) ? $js : [$js]);
  }

  /**
   * Use the template engine to render the final template. Fast if the blocks stack is not used.
   *
   * @throws OtraException|ReflectionException
   * @return string
   */protected static function processFinalTemplate(string $route, string $templateFilename, array $variables) : string
  {
    extract($variables);
    ob_start();
    require $templateFilename;

    if (!in_array(CORE_PATH . 'templating/blocks.php', get_included_files()))
    {
      return ob_get_clean();
    }

    if ($_SERVER[APP_ENV] === DEV && !str_contains($route, 'otra_'))
    {
      if (!in_array(CORE_PATH . 'templating/blocks.php', get_included_files()))
        return '';
      else
      {
        ob_start();

        /**
 * @author Lionel Péramo
 * @package otra\templating
 */if (!function_exists(__NAMESPACE__ . '\\showBlocksVisually'))
{
  /**
   * @param array{
   *   content: string,
   *   endingBlock: bool,
   *   index: int,
   *   name: string,
   *   parent?: array,
   *   replacedBy?: int
   * } $block
   */function showBlockTags(int|string $key, array $block) : void
  {?>
    <div class="otra--block-tags">
      <span class="otra--block-tags--key" title="Position of the block in the stack"><?= $key?></span>
      <span class="otra--block-tags--depth"
            title="Depth of the block in the OTRA template engine"><?= $block[BlocksSystem::OTRA_BLOCKS_KEY_INDEX]?></span>
      <span class="otra--block-tags--markup"
            title="Highest markup of the block or name of the block"><?=
        $block[BlocksSystem::OTRA_BLOCKS_KEY_NAME] !== '' ? '&lt;' . $block[BlocksSystem::OTRA_BLOCKS_KEY_NAME] .'&gt;' : 'No name'?></span>
      <span class="otra--block-tags--ending-block otra--block--ending--<?= $block['endingBlock'] ?? 'false'?>"
            title="Is this virtual block ending a template block?">Ending block</span>
    </div><?php }

  function showCode(string $code) : void
  {
    if ($code !== '')
    {?>
    <pre class="otra--code"><!--
    --><strong class="otra--code--container"><mark class="otra--code--container-highlight"><?= htmlentities($code)?></mark></strong><!--
 --></pre><?php } else {?><p>Empty block.</p><?php }
  }

  
  function showBlocksVisually(bool $page = true) : void
  {
    if ($page)
    {?>
    <link rel="stylesheet" href="<?= CORE_CSS_PATH . 'pages/templateStructure/templateStructure.css'?>"/><?php }?>
    <div<?php if (!$page) {?> class="template-structure--container"<?php }?>>
      <h1 class="otra--template-rendering--title">Template rendering</h1><?php $replacingBlocks = [];

      /**
       * @var int|string $blockKey
       * @var array{
       *   content: string,
       *   endingBlock: bool,
       *   index: int,
       *   name: string,
       *   parent?: array,
       *   replacedBy?: int
       * } $block
       */foreach (BlocksSystem::$blocksStack as $blockKey => $block)
      {?>
        <div id="block<?= $blockKey?>" class="otra-block--base"><?php showBlockTags($blockKey, $block);
        showCode($block[BlocksSystem::OTRA_BLOCKS_KEY_CONTENT]);

        if (isset($block[BlocksSystem::OTRA_BLOCKS_KEY_REPLACED_BY]))
        {
          $replacingBlocks[$block[BlocksSystem::OTRA_BLOCKS_KEY_REPLACED_BY]] = $blockKey;
          $templateEngineError = isset(BlocksSystem::$blocksStack[$block[BlocksSystem::OTRA_BLOCKS_KEY_REPLACED_BY]]);if ($templateEngineError) {?><br><span style="color: #f00 !important">Error - </span><?php }?><p>Replaced by the
          <a href="#block<?= $block[BlocksSystem::OTRA_BLOCKS_KEY_REPLACED_BY]?>" title="<?=
          $templateEngineError
            ? htmlentities(BlocksSystem::$blocksStack[$block[BlocksSystem::OTRA_BLOCKS_KEY_REPLACED_BY]][BlocksSystem::OTRA_BLOCKS_KEY_CONTENT])
            : 'Error!'?>
            ">block<?= $block[BlocksSystem::OTRA_BLOCKS_KEY_REPLACED_BY]?></a></p><?php }

        if (isset($replacingBlocks[$blockKey]))
          echo '<p>Replacing the <a href="#block' . $replacingBlocks[$blockKey] . '" title="' .
            htmlentities(BlocksSystem::$blocksStack[$replacingBlocks[$blockKey]][BlocksSystem::OTRA_BLOCKS_KEY_CONTENT]) .
            '">block ' . $replacingBlocks[$blockKey] . '</a></p>';

        $previousParentKeys = '';

          while ($block[BlocksSystem::OTRA_BLOCKS_KEY_PARENT] !== null)
          {
            $block = $block[BlocksSystem::OTRA_BLOCKS_KEY_PARENT];
            $parentBlockKey = array_search($block, BlocksSystem::$blocksStack);?>
            <details class="otra-block--parent--accordion">
              <summary>
                <a href="#block<?= $parentBlockKey?>">Parent block :<?= $previousParentKeys . $parentBlockKey?></a>
              </summary>
              <div class="otra-block--parent"><?php showBlockTags(0, $block);
                showCode($block[BlocksSystem::OTRA_BLOCKS_KEY_CONTENT]);?>
              </div>
            </details><?php $previousParentKeys .= $parentBlockKey . ' => ';
          }?>
        </div><?php }?></div><?php }
}
        ob_clean();
        showBlocksVisually(false);
        Session::init();
        Session::set('templateVisualization', base64_encode(ob_get_clean()));
      }
    }

    return BlocksSystem::getTemplate();
  }

  /**
   * @param string      $file     The file to render
   * @param bool|string $viewPath If true (default), we add the usual view path before `$file` otherwise,
   *                              we add `$viewPath` before `$file`
   *
   * @return array{0:string,1:string} [$templateFile, $otraRoute]
   */protected function getTemplateFile(string $file, bool|string $viewPath) : array
  {
    if (!str_contains($this->route, 'otra_'))
      return [
        ($viewPath === true ? $this->viewPath : $viewPath) . $file,
        false
      ];

    return [
      CORE_VIEWS_PATH . $this->controller . DIR_SEPARATOR . $file,
      true
    ];
  }

  /**
   * @param string $content     The main content of the template
   * @param string $cssResource The css resources to link to the template
   * @param string $jsResource  The js resources to link to the template
   */protected static function addResourcesToTemplate(string &$content, string $cssResource, string $jsResource) : void
  {
    $contentAndJs = str_replace(
      '</body',
      $jsResource . '</body',
      $content
    );

    $content = self::$ajax
      ? $cssResource . $contentAndJs
      : str_replace(
        self::OTRA_LABEL_ENDING_TITLE_TAG,
        self::OTRA_LABEL_ENDING_TITLE_TAG . $cssResource,
        $contentAndJs
      );
  }

  /**
   * @param array  $viewResourcePath Paths to CSS and JS files
   *
   * @throws Exception
   */protected static function handleCache(
    string $templateFile,
    array $variables,
    bool $ajax,
    string $route,
    array $viewResourcePath) : void
  {
    $cacheActivated = property_exists(AllConfig::class, 'cache') && AllConfig::$cache;

    if ($cacheActivated)
    {
      if (isset(self::$rendered[$templateFile]))
        self::$cacheUsed = 'memory';

      if (self::$cacheUsed === 'memory')
        self::$template = self::$rendered[$templateFile];
      else {
        $cachedFile = self::getCacheFileName($route);
        $cachedFileContent = self::getCachedFileContent($cachedFile);

        if (false === $cachedFileContent)
        {
          if ($ajax)
            self::$ajax = true;

          self::$template = self::render($templateFile, $variables, $route, $viewResourcePath);

          if (file_put_contents($cachedFile, self::$template) === false && $route !== 'otra_exception')
            throw new OtraException('We cannot create/update the cache for the route \'' . $route . '\'.' .
              PHP_EOL . 'This file is \'' . $cachedFile. '\'.');
        } else {
          self::$template = $cachedFileContent;
          self::$cacheUsed = '.cache file';
        }

        self::$rendered[$templateFile] = self::$template;
      }
    } else {
      if ($ajax)
        self::$ajax = true;

      self::$template = self::render($templateFile, $variables, $route, $viewResourcePath);
    }
  }

  /**
   * Parses the template file and updates parent::$template
   *
   * @param string $templateFile The file name
   * @param array  $variables    Variables to pass to the template
   *
   * @throws OtraException
   * @throws ReflectionException
   * @throws Exception
   * @return string
   */protected static function render(
    string $templateFile,
    array $variables,
    string $route,
    array $viewResourcePath = []) : string
  {
    $content = MasterController::processFinalTemplate($route, $templateFile, $variables);
    [$cssResource, $jsResource] = static::getTemplateResources($route, $viewResourcePath);

    if (self::$ajax)
    {
      $titlePosition = mb_strrpos($content, '</title>');
      $cssContent = $cssResource . self::addDynamicCSS();
      $tempContent = (false !== $titlePosition)
        ? substr_replace($content, $cssContent, $titlePosition + 8, 0) : $cssContent . $content;
      $bodyPosition = mb_strrpos($tempContent, '</body>');

      $content = (false !== $bodyPosition)
        ? substr_replace($tempContent, $jsResource, $bodyPosition, 0)
        : $tempContent . $jsResource;
    }
    else
    {
      $content = str_replace(
        self::OTRA_LABEL_ENDING_TITLE_TAG,
        self::OTRA_LABEL_ENDING_TITLE_TAG . self::addDynamicCSS(),
        str_replace(
          '</body>',
          self::addDynamicJS() . '</body>',
          $content
        )
      );
      self::addResourcesToTemplate($content, $cssResource, $jsResource);
    }

    $content = HtmlMinifier::minifyHTML($content);

    self::$javaScripts = self::$stylesheets = [];

    return $content;
  }

  /**
   * Adds extra CSS dynamically (needed for the debug bar for example).
   *
   * @throws Exception
   * @return string
   */public static function addDynamicCSS() : string
  {
    $cssContent = '';

    foreach(self::$stylesheets as $stylesheet)
    {
      $cssContent .= PHP_EOL . '<link rel=stylesheet href="' . $stylesheet[self::$stylesheetFile] .
        '.css" media=' . (isset($stylesheet[self::$printStylesheet]) && $stylesheet[self::$printStylesheet]
          ? 'print'
          : 'screen')
        . ' />';
    }

    return $cssContent;
  }

  /**
   * Adds extra JS dynamically (needed for the debug bar for example).
   *
   * @throws Exception
   * @return string
   */public static function addDynamicJS() : string
  {
    $jsContent = '';

    foreach(self::$javaScripts as $javaScript)
    {
      $jsContent .= self::LABEL_SCRIPT_NONCE .
        getRandomNonceForCSP() . ' src="' . $javaScript . '.js" ></script>';
    }

    return $jsContent;
  }

  /**
   * Gets AJAX CSS. Needed because if we put CSS in the body, it will be replaced on some page change.
   * We then need to put the AJAX CSS to the head as the head is never replaced.
   *
   * @throws Exception
   * @return array
   */public static function getAjaxCSS() : array
  {
    $cssContent = [];

    foreach(self::$stylesheets as $stylesheetType => $stylesheets)
    {
      foreach($stylesheets as $stylesheet)
      {
        $cssContent[] = [
          'href' => $stylesheet . '.css',
          'nonce' => getRandomNonceForCSP(),
          'media' => ($stylesheetType === self::CSS_MEDIA_SCREEN)
            ? 'screen'
            : 'print'
        ];
      }
    }

    return $cssContent;
  }

  /**
   * Gets AJAX JS
   *
   * @throws Exception
   * @return array
   */public static function getAjaxJS() : array
  {
    $jsContent = [];

    foreach(self::$javaScripts as $javaScript)
    {
      $jsContent[] = [
        'nonce' => getRandomNonceForCSP(),
        'src' => $javaScript . '.js'
      ];
    }

    return $jsContent;
  }
}class TestRequireMasterController
{
  public static function run(): void
  {
    /** MVC master controller class
 *
 * @author Lionel Péramo
 */ /**
 * @author Lionel Péramo
 * @package otra
 *
 * @method static string getTemplateResources() Annotation needed for static analysis tools
 */

if ($_SERVER[APP_ENV] === PROD && PHP_SAPI !== 'cli')
  class_alias('otra\cache\php\MasterController', '\otra\MasterController');
  }
}
