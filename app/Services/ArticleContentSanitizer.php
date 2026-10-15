<?php

namespace App\Services;

/**
 * حذف تبلیغات/CTAهای گهواره از HTML مقالات Crawl‌شده، بدون حذف لینک‌های محتوایی مقاله.
 */
class ArticleContentSanitizer
{
    public function removeGahvareAds(string $html): string
    {
        if (trim($html) === '' || stripos($html, 'gahvare') === false && mb_stripos($html, 'گهواره') === false) {
            return $html;
        }

        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML(
            '<?xml encoding="utf-8"?><div id="gahvare-clean-root">' . $html . '</div>',
            LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return $this->fallbackCleanup($html);
        }

        $xpath = new \DOMXPath($dom);
        $toRemove = [];

        // CTAهای شناخته‌شده‌ای که در Dump واقعی مشاهده شدند.
        foreach ($xpath->query('//*[@id="install-inline" or @id="install-screenshot"]') ?: [] as $node) {
            if ($node instanceof \DOMElement) {
                $toRemove[] = $this->promotionalContainer($node);
            }
        }

        // لینک landing و install گهواره؛ لینک‌های عادی مقاله gahvare.net/articles حذف نمی‌شوند.
        foreach ($xpath->query('//a[@href]') ?: [] as $node) {
            if (!($node instanceof \DOMElement)) {
                continue;
            }

            $href = html_entity_decode($node->getAttribute('href'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($this->isGahvareInstallUrl($href) || $this->looksLikeInstallText($node->textContent)) {
                $toRemove[] = $this->promotionalContainer($node);
            }
        }

        // تصاویر تبلیغ اپلیکیشن گهواره؛ تصاویر محتوایی عمومی گهواره کورکورانه حذف نمی‌شوند.
        foreach ($xpath->query('//img') ?: [] as $node) {
            if (!($node instanceof \DOMElement)) {
                continue;
            }

            $src = html_entity_decode($node->getAttribute('src'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $alt = $node->getAttribute('alt');
            if ($this->isGahvareAppImage($src, $alt)) {
                $toRemove[] = $this->promotionalContainer($node);
            }
        }

        // بلوک‌هایی که صریحاً متن تبلیغ اپلیکیشن گهواره دارند.
        foreach ($xpath->query('//p | //div') ?: [] as $node) {
            if (!($node instanceof \DOMElement)) {
                continue;
            }

            $text = $this->normalizeText($node->textContent);
            if ($this->looksLikePromotionalCopy($text)) {
                $toRemove[] = $this->promotionalContainer($node);
            }
        }

        $seen = [];
        foreach ($toRemove as $node) {
            if (!($node instanceof \DOMNode) || $node->parentNode === null) {
                continue;
            }
            $id = spl_object_id($node);
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $node->parentNode->removeChild($node);
        }

        $root = $dom->getElementById('gahvare-clean-root');
        if ($root === null) {
            return $this->fallbackCleanup($html);
        }

        $result = '';
        foreach ($root->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }

        return trim($result);
    }

    private function promotionalContainer(\DOMNode $node): \DOMNode
    {
        $current = $node;
        $best = $node;

        // حداکثر چند سطح بالا می‌رویم تا پاراگراف/بلوک CTA حذف شود، نه کل article.
        for ($i = 0; $i < 4 && $current->parentNode !== null; $i++) {
            $parent = $current->parentNode;
            if (!($parent instanceof \DOMElement)) {
                break;
            }

            $tag = strtolower($parent->tagName);
            if (in_array($tag, ['article', 'body', 'html'], true) || $parent->getAttribute('id') === 'gahvare-clean-root') {
                break;
            }

            if (in_array($tag, ['p', 'figure'], true)) {
                return $parent;
            }

            if ($tag === 'div') {
                $class = strtolower($parent->getAttribute('class'));
                if (
                    str_contains($class, 'phone-container') ||
                    str_contains($class, 'hidden-lg') ||
                    str_contains($class, 'cta') ||
                    $this->looksLikePromotionalCopy($this->normalizeText($parent->textContent))
                ) {
                    $best = $parent;
                }
            }

            $current = $parent;
        }

        return $best;
    }

    private function isGahvareInstallUrl(string $href): bool
    {
        $href = strtolower(trim($href));

        return str_contains($href, 'gahvare.net/landing')
            || preg_match('~^https?://(?:www\.)?gahvare\.net/i/~i', $href) === 1
            || preg_match('~^/i/[a-z0-9_-]+~i', $href) === 1;
    }

    private function isGahvareAppImage(string $src, string $alt): bool
    {
        $srcLower = strtolower($src);
        $altNormalized = $this->normalizeText($alt);

        return (
            str_contains($srcLower, 'gahvare.net/assets/images/home_screen/') ||
            str_contains($srcLower, 'gahvare.net/assets/images/app')
        ) || (
            mb_stripos($altNormalized, 'اپلیکیشن گهواره') !== false ||
            mb_stripos($altNormalized, 'اسکرین شات اپلیکیشن گهواره') !== false
        );
    }

    private function looksLikeInstallText(string $text): bool
    {
        $text = $this->normalizeText($text);

        return mb_stripos($text, 'نصب اپلیکیشن گهواره') !== false
            || mb_stripos($text, 'دانلود اپلیکیشن گهواره') !== false;
    }

    private function looksLikePromotionalCopy(string $text): bool
    {
        $text = $this->normalizeText($text);
        if ($text === '') {
            return false;
        }

        if ($this->looksLikeInstallText($text)) {
            return true;
        }

        $mentionsApp = mb_stripos($text, 'اپلیکیشن گهواره') !== false;
        $hasPromoVerb = preg_match('/(نصب|دانلود|دریافت|همراه ما|سیکل .* دنبال|روزانه .* پیام)/u', $text) === 1;

        return $mentionsApp && $hasPromoVerb;
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace("\u{200C}", ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return trim($text);
    }

    private function fallbackCleanup(string $html): string
    {
        // fallback محدود برای markup خراب؛ فقط شناسه‌های تبلیغاتی شناخته‌شده حذف می‌شوند.
        $html = preg_replace(
            '~<p\b[^>]*>[^<]*(?:<a\b[^>]*id=["\']install-inline["\'][^>]*>.*?</a>).*?</p>~isu',
            '',
            $html
        ) ?? $html;

        $html = preg_replace(
            '~<div\b[^>]*>.*?<a\b[^>]*id=["\']install-screenshot["\'][^>]*>.*?</a>.*?</div>~isu',
            '',
            $html
        ) ?? $html;

        return $html;
    }
}
