<?php
class torrentgalaxy implements ISite, ISearch {
    const SITE = 'https://torrentgalaxy.one';
    const MAX_PAGES = 20;

    public function __construct($url = null, $username = null, $password = null, $meta = null) {}

    private static function text($html) {
        return trim(preg_replace('/\s+/u', ' ', str_replace("\xC2\xA0", ' ',
            html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8'))));
    }

    private static function fetch($ajax, $url) {
        $body = false;
        $success = function ($_1, $_2, $_3, $html, $_4) use (&$body) {
            $body = $html;
        };
        if (!$ajax->request(array('url' => $url, 'body' => true), $success)) {
            return false;
        }
        return $body;
    }

    public static function ParseMagnet($html) {
        // Only actual anchor hrefs, never scripts or unrelated download URLs.
        if (!preg_match_all('~<a\b[^>]*\shref\s*=\s*([\x22\x27])(magnet:\?.*?)\1~is', $html, $matches)) {
            return false;
        }
        foreach ($matches[2] as $value) {
            $magnet = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
            if (preg_match('~[?&]xt=urn:btih:([a-f0-9]{40}|[a-z2-7]{32})(?:&|$)~i', $magnet)) {
                return $magnet;
            }
        }
        return false;
    }

    public static function ParseList($html) {
        $items = array();
        // Split at result rows rather than matching nested div closing tags.
        $rows = preg_split('~<div\b[^>]*\bclass\s*=\s*[\x22\x27]tgxtablerow\b[^\x22\x27]*[\x22\x27]~i', $html);
        array_shift($rows);
        foreach ($rows as $row) {
            if (!preg_match('~<a\b[^>]*\shref=[\x22\x27](/post-detail/[^\x22\x27?#]+)[\x22\x27][^>]*>(.*?)</a>~is', $row, $link)) {
                continue;
            }
            $name = self::text($link[2]);
            if ($name === '') { continue; }
            $item = new SearchLink;
            $item->src = 'torrentgalaxy.one';
            $item->link = self::SITE . html_entity_decode($link[1], ENT_QUOTES, 'UTF-8');
            $item->name = $name;
            $item->category = '';
            if (preg_match('~<a\b[^>]*href=[\x22\x27]/get-posts/category:[^\x22\x27]+[\x22\x27][^>]*>(.*?)</a>~is', $row, $cat)) {
                $item->category = self::text($cat[1]);
            }
            $item->size = 0;
            $plain = self::text($row);
            if (preg_match('~\b([0-9]+(?:[.,][0-9]+)?)\s*([KMGT]?I?B)\b~i', $plain, $size)) {
                $unit = strtoupper($size[2]);
                $power = strpos('BKMGT', substr($unit, 0, 1));
                $base = strpos($unit, 'I') !== false ? 1024 : 1000;
                $item->size = (float) str_replace(',', '.', $size[1]) * pow($base, $power);
            }
            $item->seeds = 0;
            $item->peers = 0;
            if (preg_match('~title=[\x22\x27]Seeders/Leechers[\x22\x27][^>]*>(.*?)</span>~is', $row, $health)
                && preg_match('~\[\s*([\d,]+)\s*/\s*([\d,]+)\s*\]~', self::text($health[1]), $counts)) {
                $item->seeds = (int) str_replace(',', '', $counts[1]);
                $item->peers = (int) str_replace(',', '', $counts[2]);
            }
            $item->time = null;
            if (preg_match('~<td\b[^>]*>\s*Added\s+(.*?)</td>~is', $row, $age)) {
                preg_match_all('~(\d+)\s*(year|month|week|day|hour|minute|second)s?\b~i', self::text($age[1]), $parts, PREG_SET_ORDER);
                if (count($parts)) {
                    $item->time = new DateTime();
                    foreach ($parts as $part) {
                        $item->time->modify('-' . $part[1] . ' ' . strtolower($part[2]));
                    }
                }
            }
            $items[] = $item;
        }
        return $items;
    }

    public function Search($keyword, $limit, $category) {
        $found = array();
        $seen = array();
        $limit = (int) $limit;
        if ($limit <= 0 || trim($keyword) === '') { return $found; }
        $ajax = new Ajax();
        for ($page = 1; $page <= self::MAX_PAGES && count($found) < $limit; ++$page) {
            $html = self::fetch($ajax, self::SITE . '/get-posts/?keywords=' . rawurlencode($keyword) . '&page=' . $page);
            if ($html === false) { break; }
            $items = self::ParseList($html);
            $new = 0;
            foreach ($items as $item) {
                if (isset($seen[$item->link])) { continue; }
                $seen[$item->link] = true;
                ++$new;
                $detail = self::fetch($ajax, $item->link);
                if ($detail === false) { continue; }
                $magnet = self::ParseMagnet($detail);
                if ($magnet === false) { continue; }
                $item->enclosure_url = $magnet;
                $found[] = $item;
                if (count($found) >= $limit) { break; }
            }
            // Also stops when the server ignores page and repeats results.
            if (!$new) { break; }
        }
        // Category filtering is not mapped: same behavior as existing providers.
        return $found;
    }
}
