<?php
declare(strict_types=1);

namespace src\templating;

use otra\templating\HtmlMinifier;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionMethod;

/**
 * @runTestsInSeparateProcesses
 */
class HtmlMinifierTest extends TestCase
{
  /**
   * It must minify the HTML while preserving spaces in pre and code markups, between block type markups and attributes'
   * values
   * @small
   * @dataProvider dataProvider
   *
   * @throws ReflectionException
   */
  public function testMinifyHTML(string $expectedHtmlCode, string $htmlCodeToTest, array $configuration = []) : void
  {
    self::assertSame(
      $expectedHtmlCode,
      new ReflectionMethod(
        HtmlMinifier::class,
        'minifyHTML'
      )->invokeArgs(null, [$htmlCodeToTest, $configuration]),
      'Data set "' . $this->dataName() . '"' . PHP_EOL . 'Passed code :\'' . $htmlCodeToTest . '\''
    );
  }

  /**
   * @return array<array-key, array{0:string, 1:string}>
   */
  public static function dataProvider(): array
  {
    return [
      'pre markups' => [<<<'HTMLCODE'
<pre>
  test </pre>
HTMLCODE,<<<'HTMLCODE'
<pre>
  test </pre>
HTMLCODE],
      'code markups' => [<<<'HTMLCODE'
<code>
  test </code>
HTMLCODE,<<<'HTMLCODE'
<code>
  test </code>
HTMLCODE],
      'script markups' =>[<<<'HTMLCODE'
<script>
  test </script>
HTMLCODE,<<<'HTMLCODE'
<script>
  test </script>
HTMLCODE],
      'textarea markups' =>[<<<'HTMLCODE'
<textarea>
  test </textarea>
HTMLCODE,<<<'HTMLCODE'
<textarea>
  test </textarea>
HTMLCODE],
      'SVG (XML syntax so cannot remove the slash)' => ['<svg><use />', '<svg><use />'],
      'preformatted markup with markup inside' =>
        [<<<'HTMLCODE'
<pre><span>test</span>
<span>div</span></pre>
HTMLCODE,
         <<<'HTMLCODE'
<pre><span>test</span>
<span>div</span></pre>
HTMLCODE],
      'auto closed tags' => ['<meta>', '<meta/>'],
      'auto closed tags with attributes' => ['<meta data-v=a>', '<meta data-v=a/>'],
      'tags with normal attributes' => ['<p id=test class="super cool"></p>', '<p id = "test" class="super cool"></p>'],
      'tags with attribute with an hyphen' => ['<p data-value=refresh></p>', '<p data-value="refresh"></p>'],
      'tags with attribute value containing a slash' => ['<link href=/favicon.png>', '<link href="/favicon.png">'],
      'attributes with empty value' => ['<b data-test></b>', '<b data-test=""></b>'],
      'space and attributes' => ['<html lang=fr class=hello>', '<html  lang="fr" class=\'hello\'>'],
      'attributes value with special characters' =>
      [
        '<p data-value="test=hello" data-test="test>hello" data-note="test<hello">',
        '<p data-value="test=hello" data-test="test>hello" data-note="test<hello">'
      ],
      'quotes in attribute values' =>
        [
          '<p title="The \'test\'">test</p>',
          '<p title="The \'test\'">test</p>'
        ],
      // aggressive minification
      'outside HTML comments' => ['', '<!-- comment -->'],
      'outside HTML comments (with no comments minification)' =>
        [
          '<!-- comment -->',
          '<!-- comment -->',
          ['comments' => false]
        ],
      'HTML comments' => ['super hello', 'super<!-- comment --> hello'],
      'HTML comments (with no comments minification)' =>
        [
          'super<!-- comment --> hello',
          'super<!-- comment --> hello',
          ['comments' => false]
        ],
      'HTML comments in markups' => ['<a>super hello</a>', '<a>super<!-- comment --> hello</a>'],
      'HTML comments in markups (with no comments minification)' =>
        [
          '<a>super<!-- comment --> hello</a>',
          '<a>super<!-- comment --> hello</a>',
          ['comments' => false]
        ],
      'space and comments' =>
      [
        '<!DOCTYPE html> <link href=/favicon.png rel=apple-touch-icon sizes=16x16>',
        '<!DOCTYPE html>  <!-- Favicons--> <link href="/favicon.png" rel=apple-touch-icon sizes=16x16>'
      ],
      'doctype declaration' => ['<!DOCTYPE html>', '<!DOCTYPE     html     >'],
      'doctype declaration - lowercase' => ['<!doctype html>', '<!doctype     html     >'],
      'cdata sections' => ['<svg><![CDATA[   content   ]]></svg>', '<svg><![CDATA[   content   ]]></svg>'],
      'no minification on spaces' =>
        [
          '<b>  alpha  test  </b>',
          '<b>  alpha  test  </b>',
          [
            'spaces' => 'none'
          ]
        ],
      'minify consecutive spaces' =>
        [
          '<p> alpha test </p>',
          '<p>  alpha  test  </p>',
          [
            'spaces' => 'consecutiveSpaces'
          ]
        ],
      'Minify start/end and consecutive spaces' =>
        [
          '<p>test<b>alpha test</b>and one</p>',
          '<p>  test  <b>  alpha  test  </b>  and  one </p>',
          [
            'spaces' => 'allSpaces',
            'level' =>
              [
                'allSpaces' => ['b', 'p']
              ]
          ]
        ],
      'new lines' => ['<meta charset=UTF-8> <meta>', '<meta charset="UTF-8"/>' . PHP_EOL . '<meta/>'],
      'inferior in content' =>
        [
          '<button type=button><</button>',
          '<button type=button><</button>'
        ],
      'superior in content' =>
      [
        '<button type=button>></button>',
        '<button type=button>></button>'
      ],
      'big test' =>
        [
          <<<'HTMLCODE'
<!doctype html> <html lang=fr class=hello> <head> <title> Test super </title> <meta charset=UTF-8> <meta> <script defer nonce=fc1e37d60c1bca248bf68554b56285a534b57c2d2849e79f53963d31930bb910>let  hello='test';</script> </head> <body>un super <p>test</p> <p class=test data-test data-attribute="test  coucou" title=test> one paragraph <a>alpha</a> and one <a>beta</a>.</p><br> <pre>
  Here is a paragraph, formatted by hand!
</pre> <code>
  Here is a paragraph, formatted by hand!
</code></body></html>
HTMLCODE,
          <<<'HTMLCODE'
<!doctype html> <html  lang="fr" class='hello'> <head> <title>  Test  super  </title>
 <meta  charset="UTF-8" />
 <meta />
 <script      defer  nonce="fc1e37d60c1bca248bf68554b56285a534b57c2d2849e79f53963d31930bb910"  >let  hello='test';</script> </head>

<body>un super
<p    >test</p> <p class=test data-test="" data-attribute="test  coucou" title="test">  one paragraph <a>alpha</a> and  one <a>beta</a>.</p><br/>
  <pre>
  Here is a paragraph, formatted by hand!
</pre>
  <code>
  Here is a paragraph, formatted by hand!
</code></body></html>
HTMLCODE
        ],
      'big test bis' =>
      [
          <<<'HTMLCODE'
<!doctype html> <html lang=fr class=hello> <head> <title> Super </title> <meta charset=UTF-8> <meta> <script defer nonce=fc1e37d60c1bca248bf68554b56285a534b57c2d2849e79f53963d31930bb910>let  hello='test';</script> </head> <body>un super <p>test</p> <p class=test data-test data-attribute="test  coucou" title=test> one paragraph <a>alpha</a> and one <a>beta</a>.</p><br> <pre>
  Here is a paragraph, formatted by hand!
</pre> <code>
  Here is a paragraph, formatted by hand!
</code></body></html>
HTMLCODE,
          <<<'HTMLCODE'
<!doctype html> <html  lang="fr" class='hello'> <head> <title>  Super  </title>
 <meta charset="UTF-8"/>
 <meta />
 <script      defer  nonce="fc1e37d60c1bca248bf68554b56285a534b57c2d2849e79f53963d31930bb910"  >let  hello='test';</script> </head>

<body>un super
<p    >test</p> <p class=test data-test="" data-attribute="test  coucou" title="test">  one paragraph <a>alpha</a> and  one <a>beta</a>.</p><br/>
  <pre>
  Here is a paragraph, formatted by hand!
</pre>
  <code>
  Here is a paragraph, formatted by hand!
</code></body></html>
HTMLCODE
      ]
    ];
  }
}
