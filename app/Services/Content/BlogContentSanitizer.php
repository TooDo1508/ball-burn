<?php

namespace App\Services\Content;

use Illuminate\Validation\ValidationException;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class BlogContentSanitizer
{
    private readonly HtmlSanitizer $sanitizer;

    public function __construct()
    {
        $config = (new HtmlSanitizerConfig())
            ->allowElement('p')
            ->allowElement('h2')
            ->allowElement('h3')
            ->allowElement('h4')
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li')
            ->allowElement('blockquote')
            ->allowElement('strong')
            ->allowElement('b')
            ->allowElement('em')
            ->allowElement('i')
            ->allowElement('u')
            ->allowElement('s')
            ->allowElement('small')
            ->allowElement('br')
            ->allowElement('hr')
            ->allowElement('figure')
            ->allowElement('figcaption')
            ->allowElement('a', ['href', 'title'])
            ->allowElement('img', ['src', 'alt', 'title'])
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
            ->allowRelativeLinks()
            ->allowMediaSchemes(['https'])
            ->allowRelativeMedias();

        $this->sanitizer = new HtmlSanitizer($config);
    }

    public function sanitize(string $html): string
    {
        if (strlen($html) > 20_000) {
            throw ValidationException::withMessages([
                'body' => 'Nội dung bài viết vượt quá giới hạn 20 KB. Hãy rút gọn hoặc chia thành nhiều bài.',
            ]);
        }

        return $this->sanitizer->sanitize($html);
    }
}
