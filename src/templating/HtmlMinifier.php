<?php
declare(strict_types=1);
namespace otra\templating;

/**
 * Utility class to minify HTML
 */
class HtmlMinifier
{
  private const array
    INLINE_TAGS =
    [
      'a', 'abbr', 'acronym', 'audio', 'b', 'bdo', 'br', 'button', 'canvas', 'cite', 'dfn', 'em', 'font', 'i',
      'img', 'input', 'kbd', 'label', 'q', 'samp', 'select', 'small', 'span', 'strong', 'sub', 'sup',
      'textarea', 'time', 'var', 'video'
    ],
    PRESERVED_TAGS = ['code', 'pre', 'script', 'svg', 'textarea'],
    DEFAULT_CONFIGURATION =
    [
      'comments' => true,
      'spaces' => 'consecutiveSpaces'
    ];

  private const string
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
    STATE_OUTSIDE_CLOSING_AUTO_CLOSED_TAG = 'OUTSIDE - CLOSING AUTO-CLOSED TAG';

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
   * It must minify the HTML by removing whitespaces (spaces, tabulations, etc.) and quotes while preserving spaces in
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
            // we do not add '/' in auto-closing tags if it is not XML (SVG files, for example)
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
              // Otherwise, it skips them.
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
